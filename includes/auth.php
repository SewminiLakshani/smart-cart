<?php
require_once __DIR__ . '/functions.php';

/** Force login; redirect to login page and remember intended destination */
function require_login(): void
{
    if (!is_logged_in()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? BASE_URL;
        set_flash('warning', 'Please log in to continue.');
        redirect(BASE_URL . 'auth/login.php');
    }
}

/** Force one of the given roles; otherwise show 403 */
function require_role(string ...$roles): void
{
    require_login();
    if (!has_role(...$roles)) {
        http_response_code(403);
        die('<div style="font-family:sans-serif;padding:60px;text-align:center;background:#111;color:#eee;min-height:100vh">
                <h1 style="color:#d4af6a">403 - Access Denied</h1>
                <p>You do not have permission to view this page.</p>
                <a href="' . BASE_URL . '" style="color:#d4af6a">Return home</a>
             </div>');
    }
}

/** Attempt login. Returns [success(bool), message(string)] */
function attempt_login(string $email, string $password, ?string $expectedRole = null): array
{
    $stmt = db()->prepare("SELECT u.*, r.role_name FROM users u JOIN roles r ON r.role_id = u.role_id WHERE u.email = ? LIMIT 1");
    $stmt->execute([trim(strtolower($email))]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return [false, 'Invalid email or password.'];
    }
    if ($user['status'] !== 'active') {
        return [false, 'Your account is currently inactive. Please contact support.'];
    }
    if ($expectedRole && $user['role_name'] !== $expectedRole) {
        return [false, 'This login area is not available for your account type.'];
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['user_id'];
    $_SESSION['role'] = $user['role_name'];
    $_SESSION['full_name'] = $user['full_name'];

    db()->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?")->execute([$user['user_id']]);

    // Merge a guest session cart/wishlist could go here if implemented
    return [true, 'Welcome back, ' . $user['full_name'] . '!'];
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

/**
 * Register a new customer account.
 * Returns [success(bool), message(string)]
 */
function register_customer(string $fullName, string $email, string $phone, string $password): array
{
    $email = trim(strtolower($email));

    if (strlen($fullName) < 2) return [false, 'Please enter your full name.'];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return [false, 'Please enter a valid email address.'];
    if (!is_valid_phone($phone)) return [false, 'Please enter a valid phone number.'];
    if (!is_valid_password($password)) return [false, 'Password must be at least 8 characters and include a letter and a number.'];

    $pdo = db();
    $check = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) {
        return [false, 'An account with this email already exists.'];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $pdo->prepare("INSERT INTO users (role_id, full_name, email, phone, password_hash, status) VALUES (1, ?, ?, ?, ?, 'active')")
        ->execute([$fullName, $email, $phone, $hash]);
    $userId = (int)$pdo->lastInsertId();

    $pdo->prepare("INSERT INTO cart (user_id) VALUES (?)")->execute([$userId]);
    $pdo->prepare("INSERT INTO wishlist (user_id) VALUES (?)")->execute([$userId]);

    create_notification($userId, 'customer', 'Welcome to SmartCart!', 'Thanks for joining SmartCart, ' . $fullName . '. Start exploring our curated collection.', 'welcome', BASE_URL . 'products/shop.php');

    return [true, 'Account created successfully. You can now log in.'];
}
