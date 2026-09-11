<?php
/**
 * SmartCart - Database Connection & Core Bootstrap
 * Edit the constants below to match your local XAMPP / MySQL setup.
 */

// ---- Database configuration ----
define('DB_HOST', 'localhost:3308');
define('DB_NAME', 'smartcart_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ---- App configuration ----
define('BASE_URL', '/smartcart/');           // change if your folder name differs
define('SITE_NAME', 'SmartCart');
define('UPLOAD_DIR', __DIR__ . '/../uploads/products/');
define('UPLOAD_URL', BASE_URL . 'uploads/products/');
define('MAX_UPLOAD_SIZE', 8 * 1024 * 1024);  // 8MB (matches the raised .htaccess upload_max_filesize)
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp']);
define('DEFAULT_DELIVERY_STANDARD', 350.00);
define('DEFAULT_DELIVERY_EXPRESS', 750.00);
define('FREE_DELIVERY_THRESHOLD', 25000.00);
define('LOW_STOCK_THRESHOLD', 10);

// ---- Secure session bootstrap ----
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Basic hardening headers (safe defaults for a local dev/academic deployment)
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Show friendly errors to users, log real errors for developers
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

/**
 * Global PDO connection (singleton).
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            http_response_code(500);
            die('<div style="font-family:sans-serif;padding:40px;text-align:center">
                    <h2>Service temporarily unavailable</h2>
                    <p>We could not connect to the database. Please check <code>config/database.php</code>
                    and make sure MySQL is running in XAMPP, then try again.</p>
                 </div>');
        }
    }
    return $pdo;
}
