<?php
/**
 * Date: 2026-04-05
 * Author: Taewoo Kim
 * Description: Login page for SyncSpace. Redirects already-authenticated users
 *              to the dashboard. Displays the login form and shows error or
 *              success messages passed via query string parameters.
 */
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width">
    <title>Login - SyncSpace</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="shortcut icon" href="images/croppedLogo.png">
</head>

<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="logo-area auth-logo">
                <img src="images/logo.png" alt="SyncSpace Logo" class="logo-img large-logo">
            </div>

            <h1>Welcome Back</h1>
            <p class="auth-subtext">Log in to access your SyncSpace calendar.</p>

            <?php if ($error === 'invalid'): ?>
                <div class="auth-message auth-error">Invalid email or password.</div>
            <?php endif; ?>

            <?php if ($success === 'registered'): ?>
                <div class="auth-message auth-success">Account created successfully. Please log in.</div>
            <?php endif; ?>

            <form action="api/login_handler.php" method="post">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" maxlength="100" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" required>
                </div>

                <div class="modal-actions">
                    <button type="submit" class="primary-btn">Log In</button>
                </div>
            </form>

            <div class="auth-footer">
                <a href="register.php">Create an account</a>
            </div>
        </div>
    </div>
</body>

</html>