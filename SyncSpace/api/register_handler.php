<?php
session_start();
require 'db.php';

$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

if ($username === '' || $email === '' || $password === '' || $confirm === '') {
    header('Location: ../register.php?error=empty');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../register.php?error=email');
    exit;
}

if (strlen($password) < 8 || $password !== $confirm) {
    header('Location: ../register.php?error=password');
    exit;
}

$stmt = $pdo->prepare("
    SELECT user_id
    FROM users
    WHERE email = ? OR username = ?
    LIMIT 1
");
$stmt->execute([$email, $username]);

if ($stmt->fetch(PDO::FETCH_ASSOC)) {
    header('Location: ../register.php?error=exists');
    exit;
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("
    INSERT INTO users (username, email, password_hash)
    VALUES (?, ?, ?)
");
$stmt->execute([$username, $email, $passwordHash]);

header('Location: ../login.php?success=registered');
exit;