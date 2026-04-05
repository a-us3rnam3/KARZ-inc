<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = $_GET['error'] ?? '';
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width">
    <title>Register - SyncSpace</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="shortcut icon" href="images/croppedLogo.png">
</head>

<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="logo-area auth-logo">
                <img src="images/logo.png" alt="SyncSpace Logo" class="logo-img large-logo">
            </div>

            <h1>Create Account</h1>
            <p class="auth-subtext">Sign up for SyncSpace.</p>

            <?php if ($error === 'empty'): ?>
                <div class="auth-message auth-error">Please fill in all fields.</div>
            <?php elseif ($error === 'email'): ?>
                <div class="auth-message auth-error">Invalid email format.</div>
            <?php elseif ($error === 'password'): ?>
                <div class="auth-message auth-error">Passwords do not match or are too short.</div>
            <?php elseif ($error === 'exists'): ?>
                <div class="auth-message auth-error">That username or email is already in use.</div>
            <?php endif; ?>

            <form action="api/register_handler.php" method="post">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input id="username" name="username" type="text" maxlength="50" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" maxlength="100" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input id="confirm_password" name="confirm_password" type="password" required>
                </div>

                <div class="modal-actions">
                    <button type="submit" class="primary-btn">Register</button>
                </div>
            </form>

            <div class="auth-footer">
                <a href="login.php">Already have an account?</a>
            </div>
        </div>
    </div>
</body>

</html>