<?php
require_once 'auth.php';
require_login();
require_once 'db.php';

$userId = (int)($_SESSION['user_id'] ?? 0);

$currentPassword = $_POST['current_password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
$confirmNewPassword = $_POST['confirm_new_password'] ?? '';

if (strlen($newPassword) < 8 || $newPassword !== $confirmNewPassword) {
    header('Location: ../profile.php?error=password-mismatch');
    exit;
}

$stmt = $pdo->prepare("SELECT password_hash FROM users WHERE user_id = ? LIMIT 1");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
    header('Location: ../profile.php?error=wrong-password');
    exit;
}

$newHash = password_hash($newPassword, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
$stmt->execute([$newHash, $userId]);

header('Location: ../profile.php?message=password-updated');
exit;