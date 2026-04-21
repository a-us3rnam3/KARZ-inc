<?php
/**
 * Date: 2026-04-21
 * Author: Taewoo Kim
 * Description: Handles account deletion requests. Verifies the user's
 *              password, removes all associated data (events, memberships,
 *              and groups where applicable), and deletes the user account.
 *              Ends the session upon successful deletion.
 */
require_once 'auth.php';
require_login();
require_once 'db.php';

$userId = (int)($_SESSION['user_id'] ?? 0);
$password = $_POST['delete_password'] ?? '';

try {
    $pdo->beginTransaction();

    // 1. Verify password
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE user_id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        $pdo->rollBack();
        header('Location: ../profile.php?error=wrong-password');
        exit;
    }

    // 2. Find groups created by this user
    $stmt = $pdo->prepare("SELECT group_id FROM user_groups WHERE created_by = ?");
    $stmt->execute([$userId]);
    $groupIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // 3. Delete events owned by those groups
    if (!empty($groupIds)) {
        $placeholders = implode(',', array_fill(0, count($groupIds), '?'));

        $stmt = $pdo->prepare("DELETE FROM events WHERE owner_group_id IN ($placeholders)");
        $stmt->execute($groupIds);

        $stmt = $pdo->prepare("DELETE FROM group_members WHERE group_id IN ($placeholders)");
        $stmt->execute($groupIds);

        $stmt = $pdo->prepare("DELETE FROM user_groups WHERE group_id IN ($placeholders)");
        $stmt->execute($groupIds);
    }

    // 4. Delete memberships in other groups
    $stmt = $pdo->prepare("DELETE FROM group_members WHERE user_id = ?");
    $stmt->execute([$userId]);

    // 5. Delete user's own personal events
    $stmt = $pdo->prepare("DELETE FROM events WHERE created_by = ? OR owner_user_id = ?");
    $stmt->execute([$userId, $userId]);

    // 6. Delete user account
    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);

    $pdo->commit();

    session_unset();
    session_destroy();

    header('Location: ../login.php');
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    header('Location: ../profile.php?error=delete-failed');
    exit;
}