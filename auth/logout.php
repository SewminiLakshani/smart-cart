<?php
require_once __DIR__ . '/../includes/auth.php';
logout_user();
// Start a fresh session (with the same secure cookie params) so we can show a flash message on redirect.
session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
session_start();
set_flash('success', 'You have been logged out successfully.');
redirect(BASE_URL . 'index.php');
