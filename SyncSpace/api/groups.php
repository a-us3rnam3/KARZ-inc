<?php
/**
 * Date: 2026-04-05
 * Description: Handles group management for authenticated SyncSpace users.
 *              Supports fetching all groups that the current user belongs to
 *              and creating new groups with selected members. Group data is
 *              returned in structured JSON format, including group details
 *              and member usernames.
 */
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$user_id = $_SESSION['user_id'];

// ─── GET: Fetch user's groups ─────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $stmt = $pdo->prepare("
        SELECT 
            g.group_id,
            g.group_name,
            g.description,
            u.username
        FROM user_groups g
        JOIN group_members gm ON g.group_id = gm.group_id
        JOIN users u ON gm.user_id = u.user_id
        WHERE g.group_id IN (
            SELECT group_id 
            FROM group_members 
            WHERE user_id = ?
        )
    ");

    $stmt->execute([$user_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group results into structured array
    $groups = [];

    foreach ($rows as $row) {
        $gid = $row['group_id'];

        if (!isset($groups[$gid])) {
            $groups[$gid] = [
                'group_id' => $gid,
                'group_name' => $row['group_name'],
                'description' => $row['description'],
                'members' => []
            ];
        }

        $groups[$gid]['members'][] = $row['username'];
    }

    echo json_encode(array_values($groups));
    exit;
}

// ─── POST: Create group with usernames ────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $data = json_decode(file_get_contents("php://input"), true);

    $name = trim($data['group_name'] ?? '');
    $desc = trim($data['description'] ?? '');
    $usernames = $data['usernames'] ?? [];

    if (!$name) {
        echo json_encode(["success" => false, "message" => "Group name required"]);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. Create group
        $stmt = $pdo->prepare("
            INSERT INTO user_groups (group_name, description, created_by)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$name, $desc, $user_id]);
        $group_id = $pdo->lastInsertId();

        // 2. Add creator as owner
        $stmt = $pdo->prepare("
            INSERT INTO group_members (group_id, user_id, role)
            VALUES (?, ?, 'owner')
        ");
        $stmt->execute([$group_id, $user_id]);

        // 3. Add users by username
        if (!empty($usernames)) {

            $stmtUser = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
            $stmtInsert = $pdo->prepare("
                INSERT IGNORE INTO group_members (group_id, user_id)
                VALUES (?, ?)
            ");

            foreach ($usernames as $uname) {
                $stmtUser->execute([$uname]);
                $user = $stmtUser->fetch();

                if ($user) {
                    $stmtInsert->execute([$group_id, $user['user_id']]);
                }
            }
        }

        $pdo->commit();

        echo json_encode([
            "success" => true,
            "group_id" => $group_id
        ]);

    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
}