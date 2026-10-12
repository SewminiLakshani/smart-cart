<?php
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');

function json_out(array $data): void
{
    echo json_encode($data);
    exit;
}

function require_ajax_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        json_out(['success' => false, 'message' => 'Security token expired. Please refresh the page.']);
    }
}

function require_ajax_login(): array
{
    $user = current_user();
    if (!$user) {
        json_out(['success' => false, 'message' => 'Please log in to continue.', 'login_required' => true]);
    }
    return $user;
}
