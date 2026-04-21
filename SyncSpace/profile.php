<?php
/**
 * Author: Taewoo Kim
 * Date: 2026-04-21
 * Description: Profile and account management page for SyncSpace. Displays
 *              user information (username and email) and allows users to
 *              update their password or delete their account. All actions
 *              require authentication.
 */
require_once 'api/auth.php';
require_login();
require_once 'api/db.php';

$userId = (int)($_SESSION['user_id'] ?? 0);

$stmt = $pdo->prepare("SELECT username, email FROM users WHERE user_id = ? LIMIT 1");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

$message = $_GET['message'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - SyncSpace</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="shortcut icon" href="images/croppedLogo.png">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="logo-area auth-logo">
                <img src="images/logo.png" alt="SyncSpace Logo" class="logo-img large-logo">
            </div>

            <h1>Profile</h1>
            <p class="auth-subtext">Manage your account settings.</p>

            <?php if ($message === 'password-updated'): ?>
                <div class="auth-message auth-success">Password updated successfully.</div>
            <?php endif; ?>

            <?php if ($error === 'wrong-password'): ?>
                <div class="auth-message auth-error">Current password is incorrect.</div>
            <?php elseif ($error === 'password-mismatch'): ?>
                <div class="auth-message auth-error">New passwords do not match or are too short.</div>
            <?php elseif ($error === 'delete-failed'): ?>
                <div class="auth-message auth-error">Could not delete account.</div>
            <?php endif; ?>

            <div class="profile-info-box">
                <p><strong>Username:</strong> <?= htmlspecialchars($user['username']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
            </div>

            <hr class="profile-divider">

            <h2 class="profile-section-title">Change Password</h2>
            <form action="api/update_password.php" method="post">
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input id="current_password" name="current_password" type="password" required>
                </div>

                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input id="new_password" name="new_password" type="password" required>
                </div>

                <div class="form-group">
                    <label for="confirm_new_password">Confirm New Password</label>
                    <input id="confirm_new_password" name="confirm_new_password" type="password" required>
                </div>

                <div class="modal-actions">
                    <button type="submit" class="primary-btn">Update Password</button>
                </div>
            </form>

            <hr class="profile-divider">

            <h2 class="profile-section-title">Delete Account</h2>
            <p class="delete-warning">
                This action cannot be undone.
            </p>

            <form action="api/delete_account.php" method="post"
                  onsubmit="return confirm('Are you sure you want to delete your account? This cannot be undone.');">
                <div class="form-group">
                    <label for="delete_password">Enter Password to Confirm</label>
                    <input id="delete_password" name="delete_password" type="password" required>
                </div>

                <div class="modal-actions">
                    <button type="submit" class="danger-btn">Delete Account</button>
                </div>
            </form>

            <div class="auth-footer">
                <a href="index.php">Back to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>