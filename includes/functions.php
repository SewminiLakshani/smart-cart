<?php
require_once __DIR__ . '/../config/database.php';

/* =========================================================
   GENERAL / SECURITY HELPERS
   ========================================================= */

/** Escape output to prevent XSS */
function e($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/** Generate / return CSRF token for the current session */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Output a hidden CSRF field */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/** Validate a submitted CSRF token, terminates request on failure */
function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        die('Security check failed. Please refresh the page and try again.');
    }
}

/** Slugify a string for URLs */
function slugify(string $text): string
{
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = trim($text, '-');
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text) ?: $text;
    $text = strtolower(preg_replace('~[^-\w]+~', '', $text));
    return $text ?: 'item-' . uniqid();
}

/** Redirect helper */
function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

/** Set a one-time flash message */
function set_flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/** Pull & clear flash messages */
function get_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

/** Format a numeric value as LKR currency */
function money($amount): string
{
    return 'Rs. ' . number_format((float)$amount, 2);
}

/** Format a date in a friendly readable way */
function friendly_date($datetime, string $format = 'M d, Y h:i A'): string
{
    if (!$datetime) return '-';
    return date($format, strtotime($datetime));
}

/** Basic phone validation (Sri Lankan style, flexible) */
function is_valid_phone(string $phone): bool
{
    return (bool)preg_match('/^[0-9+\-\s]{7,15}$/', $phone);
}

/** Strong-ish password rule: min 8 chars, at least one letter and one number */
function is_valid_password(string $password): bool
{
    return strlen($password) >= 8 && preg_match('/[A-Za-z]/', $password) && preg_match('/[0-9]/', $password);
}

/* =========================================================
   AUTH HELPERS  (see includes/auth.php for guards)
   ========================================================= */

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) return null;
    static $cached = null;
    if ($cached !== null) return $cached;
    $stmt = db()->prepare("SELECT u.*, r.role_name FROM users u JOIN roles r ON r.role_id = u.role_id WHERE u.user_id = ? LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $cached = $stmt->fetch() ?: null;
    return $cached;
}

function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

function has_role(string ...$roles): bool
{
    $user = current_user();
    return $user && in_array($user['role_name'], $roles, true);
}

/* =========================================================
   IMAGE UPLOAD HELPER
   ========================================================= */

function handle_image_upload(array $file, string $prefix = 'product'): ?string
{
    if (empty($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload failed with error code ' . $file['error']);
    }
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        throw new RuntimeException('File is too large. Maximum size is 3MB.');
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, ALLOWED_IMAGE_TYPES, true)) {
        throw new RuntimeException('Only JPG, PNG or WEBP images are allowed.');
    }
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime];
    $filename = $prefix . '_' . uniqid() . '_' . time() . '.' . $ext;
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }
    $destination = UPLOAD_DIR . $filename;
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Could not save the uploaded file.');
    }
    return 'uploads/products/' . $filename;
}

/* =========================================================
   PRODUCT / INVENTORY HELPERS
   ========================================================= */

/** Returns the current effective selling price for a product row (handles active flash sales) */
function effective_price(array $product): float
{
    if (!empty($product['is_flash_sale']) && !empty($product['flash_sale_price'])) {
        $now = time();
        $start = strtotime($product['flash_sale_start'] ?? 'now');
        $end = strtotime($product['flash_sale_end'] ?? 'now');
        if ($now >= $start && $now <= $end) {
            return (float)$product['flash_sale_price'];
        }
    }
    if (!empty($product['discount_price'])) {
        return (float)$product['discount_price'];
    }
    return (float)$product['price'];
}

function discount_percent(array $product): int
{
    $effective = effective_price($product);
    $original = (float)$product['price'];
    if ($original <= 0 || $effective >= $original) return 0;
    return (int)round((($original - $effective) / $original) * 100);
}

function is_flash_sale_active(array $product): bool
{
    if (empty($product['is_flash_sale'])) return false;
    $now = time();
    return $now >= strtotime($product['flash_sale_start'] ?? 'now') && $now <= strtotime($product['flash_sale_end'] ?? 'now');
}

function product_main_image(int $productId): string
{
    $stmt = db()->prepare("SELECT image_path FROM product_images WHERE product_id = ? ORDER BY is_main DESC, sort_order ASC LIMIT 1");
    $stmt->execute([$productId]);
    $img = $stmt->fetchColumn();
    return $img ? BASE_URL . $img : BASE_URL . 'assets/images/products/placeholder.svg';
}

/** Recalculate & persist stock_status for a product based on quantity */
function sync_stock_status(int $productId): void
{
    $pdo = db();
    $stmt = $pdo->prepare("SELECT quantity, low_stock_threshold FROM inventory WHERE product_id = ?");
    $stmt->execute([$productId]);
    $row = $stmt->fetch();
    if (!$row) return;
    $status = 'available';
    if ($row['quantity'] <= 0) {
        $status = 'out_of_stock';
    } elseif ($row['quantity'] <= $row['low_stock_threshold']) {
        $status = 'low_stock';
    }
    $upd = $pdo->prepare("UPDATE inventory SET stock_status = ? WHERE product_id = ?");
    $upd->execute([$status, $productId]);
}

/** Maps a PHP file-upload error code to a human-readable message. */
function upload_error_message(int $code): string
{
    return match ($code) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'That image is too large for this server\'s upload limit (check php.ini upload_max_filesize / post_max_size, or resize the image and try again).',
        UPLOAD_ERR_PARTIAL => 'The image was only partially uploaded. Please try again.',
        UPLOAD_ERR_NO_TMP_DIR => 'Server is missing a temporary folder for uploads. Contact your administrator.',
        UPLOAD_ERR_CANT_WRITE => 'Server failed to write the uploaded file to disk.',
        UPLOAD_ERR_EXTENSION => 'A server extension blocked this upload.',
        default => 'The image could not be uploaded (error code ' . $code . ').',
    };
}

/** Adjust stock and write a stock_history record. $changeQty may be negative.
 *  Safe to call from within an already-open transaction (checkout, order cancellation, etc.) —
 *  it only opens/commits its own transaction when one isn't already active. */
function adjust_stock(int $productId, int $changeQty, string $type, ?int $userId = null, ?string $note = null): void
{
    $pdo = db();
    $ownTransaction = !$pdo->inTransaction();
    if ($ownTransaction) $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("SELECT quantity FROM inventory WHERE product_id = ? FOR UPDATE");
        $stmt->execute([$productId]);
        $current = (int)($stmt->fetchColumn() ?: 0);
        $new = max(0, $current + $changeQty);

        $pdo->prepare("UPDATE inventory SET quantity = ? WHERE product_id = ?")->execute([$new, $productId]);
        $pdo->prepare("INSERT INTO stock_history (product_id, user_id, previous_quantity, change_quantity, new_quantity, change_type, note) VALUES (?,?,?,?,?,?,?)")
            ->execute([$productId, $userId, $current, $changeQty, $new, $type, $note]);

        if ($ownTransaction) $pdo->commit();
        sync_stock_status($productId);

        if ($new <= LOW_STOCK_THRESHOLD) {
            notify_admins_and_staff('Low Stock Alert', "Product #$productId is running low on stock ($new left).", 'low_stock', '/admin/inventory/index.php');
        }
    } catch (Throwable $ex) {
        if ($ownTransaction && $pdo->inTransaction()) $pdo->rollBack();
        throw $ex;
    }
}

/* =========================================================
   NOTIFICATIONS
   ========================================================= */

function create_notification(?int $userId, string $audience, string $title, string $message, string $type = 'general', ?string $link = null): void
{
    $stmt = db()->prepare("INSERT INTO notifications (user_id, audience, title, message, type, link) VALUES (?,?,?,?,?,?)");
    $stmt->execute([$userId, $audience, $title, $message, $type, $link]);
}

function notify_admins_and_staff(string $title, string $message, string $type, ?string $link = null): void
{
    create_notification(null, 'admin', $title, $message, $type, $link);
}

function unread_notification_count(int $userId, string $audience): int
{
    $stmt = db()->prepare("SELECT COUNT(*) FROM notifications WHERE is_read = 0 AND (user_id = ? OR (user_id IS NULL AND audience IN (?, 'all')))");
    $stmt->execute([$userId, $audience]);
    return (int)$stmt->fetchColumn();
}

/* =========================================================
   CART / WISHLIST HELPERS
   ========================================================= */

function get_or_create_cart(int $userId): int
{
    $pdo = db();
    $stmt = $pdo->prepare("SELECT cart_id FROM cart WHERE user_id = ?");
    $stmt->execute([$userId]);
    $id = $stmt->fetchColumn();
    if (!$id) {
        $pdo->prepare("INSERT INTO cart (user_id) VALUES (?)")->execute([$userId]);
        $id = $pdo->lastInsertId();
    }
    return (int)$id;
}

function get_or_create_wishlist(int $userId): int
{
    $pdo = db();
    $stmt = $pdo->prepare("SELECT wishlist_id FROM wishlist WHERE user_id = ?");
    $stmt->execute([$userId]);
    $id = $stmt->fetchColumn();
    if (!$id) {
        $pdo->prepare("INSERT INTO wishlist (user_id) VALUES (?)")->execute([$userId]);
        $id = $pdo->lastInsertId();
    }
    return (int)$id;
}

function cart_count(int $userId): int
{
    $stmt = db()->prepare("SELECT COALESCE(SUM(ci.quantity),0) FROM cart_items ci JOIN cart c ON c.cart_id = ci.cart_id WHERE c.user_id = ?");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

function wishlist_count(int $userId): int
{
    $stmt = db()->prepare("SELECT COUNT(*) FROM wishlist_items wi JOIN wishlist w ON w.wishlist_id = wi.wishlist_id WHERE w.user_id = ?");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

/* =========================================================
   RECENTLY VIEWED / RECOMMENDATIONS
   ========================================================= */

function track_recently_viewed(int $userId, int $productId): void
{
    $pdo = db();
    $pdo->prepare("DELETE FROM recently_viewed WHERE user_id = ? AND product_id = ?")->execute([$userId, $productId]);
    $pdo->prepare("INSERT INTO recently_viewed (user_id, product_id) VALUES (?, ?)")->execute([$userId, $productId]);
    // keep only the latest 20
    $pdo->prepare("DELETE FROM recently_viewed WHERE user_id = ? AND view_id NOT IN (SELECT view_id FROM (SELECT view_id FROM recently_viewed WHERE user_id = ? ORDER BY viewed_at DESC LIMIT 20) t)")
        ->execute([$userId, $userId]);
}

function fetch_products(array $ids): array
{
    if (empty($ids)) return [];
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = db()->prepare("SELECT * FROM products WHERE product_id IN ($placeholders) AND status = 'active'");
    $stmt->execute($ids);
    $rows = $stmt->fetchAll();
    // preserve requested order
    $byId = [];
    foreach ($rows as $r) $byId[$r['product_id']] = $r;
    $ordered = [];
    foreach ($ids as $id) if (isset($byId[$id])) $ordered[] = $byId[$id];
    return $ordered;
}

/* =========================================================
   PAGINATION
   ========================================================= */

function paginate(int $totalItems, int $currentPage, int $perPage = 12): array
{
    $totalPages = max(1, (int)ceil($totalItems / $perPage));
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;
    return compact('totalItems', 'totalPages', 'currentPage', 'perPage', 'offset');
}

function render_pagination(int $currentPage, int $totalPages, string $baseQuery = ''): string
{
    if ($totalPages <= 1) return '';
    $html = '<nav aria-label="Page navigation"><ul class="pagination sc-pagination justify-content-center">';
    $prevDisabled = $currentPage <= 1 ? ' disabled' : '';
    $html .= "<li class=\"page-item{$prevDisabled}\"><a class=\"page-link\" href=\"?page=" . ($currentPage - 1) . $baseQuery . "\">&laquo;</a></li>";
    for ($i = 1; $i <= $totalPages; $i++) {
        $active = $i === $currentPage ? ' active' : '';
        $html .= "<li class=\"page-item{$active}\"><a class=\"page-link\" href=\"?page={$i}{$baseQuery}\">{$i}</a></li>";
    }
    $nextDisabled = $currentPage >= $totalPages ? ' disabled' : '';
    $html .= "<li class=\"page-item{$nextDisabled}\"><a class=\"page-link\" href=\"?page=" . ($currentPage + 1) . $baseQuery . "\">&raquo;</a></li>";
    $html .= '</ul></nav>';
    return $html;
}

/* =========================================================
   CART TOTALS / COUPON CALCULATION
   ========================================================= */

/**
 * Compute full cart totals for a user, applying any coupon stored in session.
 * Returns: items[], subtotal, discount, delivery_fee, total, coupon (or null), delivery_method
 */
function compute_cart_totals(int $userId, string $deliveryMethod = 'standard'): array
{
    $pdo = db();
    $stmt = $pdo->prepare("
        SELECT ci.cart_item_id, ci.quantity, p.product_id, p.product_name, p.price, p.discount_price,
               p.is_flash_sale, p.flash_sale_price, p.flash_sale_start, p.flash_sale_end, p.slug,
               i.quantity AS stock_qty, i.stock_status
        FROM cart_items ci
        JOIN cart c ON c.cart_id = ci.cart_id
        JOIN products p ON p.product_id = ci.product_id
        LEFT JOIN inventory i ON i.product_id = p.product_id
        WHERE c.user_id = ?
        ORDER BY ci.added_at DESC");
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll();

    $subtotal = 0;
    foreach ($rows as &$row) {
        $unitPrice = effective_price($row);
        $row['unit_price'] = $unitPrice;
        $row['line_total'] = $unitPrice * $row['quantity'];
        $subtotal += $row['line_total'];
    }
    unset($row);

    $discount = 0;
    $coupon = null;
    if (!empty($_SESSION['applied_coupon_code'])) {
        [$valid, $msg, $couponRow, $discountAmt] = validate_and_calc_coupon($_SESSION['applied_coupon_code'], $subtotal);
        if ($valid) {
            $coupon = $couponRow;
            $discount = $discountAmt;
        } else {
            unset($_SESSION['applied_coupon_code']);
        }
    }

    $deliveryFee = $subtotal >= FREE_DELIVERY_THRESHOLD ? 0 : ($deliveryMethod === 'express' ? DEFAULT_DELIVERY_EXPRESS : DEFAULT_DELIVERY_STANDARD);
    $total = max(0, $subtotal - $discount) + $deliveryFee;

    return [
        'items' => $rows,
        'subtotal' => $subtotal,
        'discount' => $discount,
        'coupon' => $coupon,
        'delivery_fee' => $deliveryFee,
        'delivery_method' => $deliveryMethod,
        'total' => $total,
    ];
}

/**
 * Validate a coupon code against current order subtotal.
 * Returns [valid(bool), message(string), couponRow(?array), discountAmount(float)]
 */
function validate_and_calc_coupon(string $code, float $subtotal): array
{
    $stmt = db()->prepare("SELECT * FROM coupons WHERE coupon_code = ? LIMIT 1");
    $stmt->execute([strtoupper(trim($code))]);
    $coupon = $stmt->fetch();

    if (!$coupon) return [false, 'Invalid coupon code.', null, 0];
    if ($coupon['status'] !== 'active') return [false, 'This coupon is no longer active.', null, 0];
    $now = time();
    if ($now < strtotime($coupon['start_date']) || $now > strtotime($coupon['end_date'])) {
        return [false, 'This coupon has expired or is not yet valid.', null, 0];
    }
    if ($coupon['usage_limit'] !== null && $coupon['used_count'] >= $coupon['usage_limit']) {
        return [false, 'This coupon has reached its usage limit.', null, 0];
    }
    if ($subtotal < $coupon['min_order_amount']) {
        return [false, 'Minimum order amount for this coupon is ' . money($coupon['min_order_amount']) . '.', null, 0];
    }

    if ($coupon['discount_type'] === 'percentage') {
        $discount = $subtotal * ((float)$coupon['discount_value'] / 100);
        if ($coupon['max_discount_amount'] !== null) {
            $discount = min($discount, (float)$coupon['max_discount_amount']);
        }
    } else {
        $discount = (float)$coupon['discount_value'];
    }
    $discount = min($discount, $subtotal);

    return [true, 'Coupon applied successfully!', $coupon, round($discount, 2)];
}

function render_cart_summary_html(array $totals): string
{
    ob_start(); ?>
    <div class="d-flex justify-content-between mb-2"><span class="text-secondary">Subtotal</span><strong><?= money($totals['subtotal']) ?></strong></div>
    <?php if ($totals['discount'] > 0): ?>
    <div class="d-flex justify-content-between mb-2 text-success"><span>Discount <?= $totals['coupon'] ? '(' . e($totals['coupon']['coupon_code']) . ')' : '' ?></span><strong>-<?= money($totals['discount']) ?></strong></div>
    <?php endif; ?>
    <div class="d-flex justify-content-between mb-2"><span class="text-secondary">Delivery Fee</span><strong><?= $totals['delivery_fee'] > 0 ? money($totals['delivery_fee']) : 'FREE' ?></strong></div>
    <div class="sc-divider"></div>
    <div class="d-flex justify-content-between mb-0 fs-5"><span>Total</span><strong class="text-gold"><?= money($totals['total']) ?></strong></div>
    <?php return ob_get_clean();
}

/** Validates a card number's structure against the real-world standard: digits only,
 *  13–19 digits long, and passes the Luhn checksum algorithm used by all major card networks. */
function is_valid_card_number(string $digitsOnly): bool
{
    $len = strlen($digitsOnly);
    if ($len < 13 || $len > 19 || !ctype_digit($digitsOnly)) return false;

    $sum = 0;
    $alt = false;
    for ($i = $len - 1; $i >= 0; $i--) {
        $n = (int)$digitsOnly[$i];
        if ($alt) {
            $n *= 2;
            if ($n > 9) $n -= 9;
        }
        $sum += $n;
        $alt = !$alt;
    }
    return $sum % 10 === 0;
}

/** Validates MM/YY expiry format and that it isn't already in the past. */
function is_valid_card_expiry(string $expiry): bool
{
    if (!preg_match('/^(\d{2})\/(\d{2})$/', $expiry, $m)) return false;
    $month = (int)$m[1];
    if ($month < 1 || $month > 12) return false;
    $year = 2000 + (int)$m[2];
    $expiryTimestamp = mktime(0, 0, 0, $month + 1, 1, $year) - 1; // end of the expiry month
    return $expiryTimestamp >= time();
}

/* =========================================================
   ORDER HELPERS
   ========================================================= */

function generate_order_number(): string
{
    return 'SC' . date('Ymd') . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
}

function order_status_badge(string $status): string
{
    $map = [
        'pending' => 'secondary',
        'confirmed' => 'info',
        'processing' => 'warning',
        'shipped' => 'primary',
        'delivered' => 'success',
        'cancelled' => 'danger',
    ];
    $class = $map[$status] ?? 'secondary';
    return '<span class="badge sc-badge bg-' . $class . '">' . ucfirst($status) . '</span>';
}

function stock_status_badge(string $status): string
{
    $map = [
        'available' => ['success', 'In Stock'],
        'low_stock' => ['warning', 'Low Stock'],
        'out_of_stock' => ['danger', 'Out of Stock'],
    ];
    [$class, $label] = $map[$status] ?? ['secondary', ucfirst($status)];
    return '<span class="badge sc-badge bg-' . $class . '">' . $label . '</span>';
}
