<?php
/**
 * Date: 2026-04-05
 * Description: Handles the login form POST for SyncSpace. Validates the submitted
 *              email and password against the database, creates a PHP session on
 *              success, and redirects back to the login page with an error on
 *              failure.
 */
session_start();
require 'db.php';

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    header('Location: ../login.php?error=invalid');
    exit;
}

$stmt = $pdo->prepare("
    SELECT user_id, username, email, password_hash
    FROM users
    WHERE email = ?
    LIMIT 1
");
$stmt->execute([$email]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user['password_hash'])) {
    header('Location: ../login.php?error=invalid');
    exit;
}

$_SESSION['user_id'] = (int)$user['user_id'];
$_SESSION['username'] = $user['username'];
$_SESSION['email'] = $user['email'];

header('Location: ../index.php');
exit;