<?php
require __DIR__ . '/_bootstrap.php';
require_ajax_csrf();

$email = trim(strtolower($_POST['email'] ?? ''));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_out(['success' => false, 'message' => 'Please enter a valid email address.']);
}

$pdo = db();
$stmt = $pdo->prepare("SELECT subscriber_id FROM newsletter_subscribers WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    json_out(['success' => false, 'message' => 'This email is already subscribed.']);
}

$pdo->prepare("INSERT INTO newsletter_subscribers (email) VALUES (?)")->execute([$email]);
json_out(['success' => true, 'message' => 'Thanks for subscribing to SmartCart!']);
