<?php

/**
 * Date: 2026-04-05
 * Description: Handles group management for authenticated SyncSpace users.
 *              Supports fetching all groups that the current user belongs to
 *              and creating new groups with selected members. Group data is
 *              returned in structured JSON format, including group details
 *              and member usernames.
 */
// SyncSpace — Groups API
// Marcus Rotaru
//
// GET    /api/groups.php
// POST   /api/groups.php

session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$user_id = $_SESSION['user_id'];
$DEBUG = false;

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

// ─── POST: Router for group actions ────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $data = json_decode(file_get_contents("php://input"), true);

    $action = $data['action'] ?? 'create'; // default = create

    try {

        if ($action === 'check_role') {

            $group_id = (int)($data['group_id'] ?? 0);

            $stmt = $pdo->prepare("
        SELECT role
        FROM group_members
        WHERE group_id = ? AND user_id = ?
        LIMIT 1
    ");
            $stmt->execute([$group_id, $user_id]);
            $member = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$member) {
                echo json_encode([
                    "success" => false,
                    "message" => "Not a group member"
                ]);
                exit;
            }

            echo json_encode([
                "success" => true,
                "role" => $member['role']
            ]);
            exit;
        }
        // ─────────────────────────────
        // CREATE GROUP
        // ─────────────────────────────
        if ($action === 'create') {

            $name = trim($data['group_name'] ?? '');
            $desc = trim($data['description'] ?? '');
            $usernames = $data['usernames'] ?? [];

            if (!$name) {
                echo json_encode([
                    "success" => false,
                    "message" => "Group name required"
                ]);
                exit;
            }

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO user_groups (group_name, description, created_by)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$name, $desc, $user_id]);

            $group_id = $pdo->lastInsertId();

            // add owner
            $stmt = $pdo->prepare("
                INSERT INTO group_members (group_id, user_id, role)
                VALUES (?, ?, 'owner')
            ");
            $stmt->execute([$group_id, $user_id]);

            // add members
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
            exit;
        }

        // ─────────────────────────────
        // LEAVE GROUP
        // ─────────────────────────────
        if ($action === 'leave') {

            $group_id = (int)($data['group_id'] ?? 0);

            if (!$group_id) {
                echo json_encode([
                    "success" => false,
                    "message" => "group_id required"
                ]);
                exit;
            }

            $stmt = $pdo->prepare("
                SELECT role FROM group_members
                WHERE group_id = ? AND user_id = ?
            ");
            $stmt->execute([$group_id, $user_id]);
            $member = $stmt->fetch();

            if (!$member) {
                echo json_encode(["success" => false, "message" => "Not a member"]);
                exit;
            }

            if ($member['role'] === 'owner') {
                echo json_encode([
                    "success" => false,
                    "message" => "Owner cannot leave group"
                ]);
                exit;
            }

            $stmt = $pdo->prepare("
                DELETE FROM group_members
                WHERE group_id = ? AND user_id = ?
            ");
            $stmt->execute([$group_id, $user_id]);

            echo json_encode(["success" => true]);
            exit;
        }

        //DELETE GROUP
        if ($action === 'delete_group') {

            $group_id = (int)($data['group_id'] ?? 0);

            if (!$group_id) {
                echo json_encode([
                    "success" => false,
                    "message" => "group_id required"
                ]);
                exit;
            }

            // ensure owner
            $stmt = $pdo->prepare("
        SELECT role
        FROM group_members
        WHERE group_id = ? AND user_id = ?
        LIMIT 1
    ");
            $stmt->execute([$group_id, $user_id]);
            $member = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$member || $member['role'] !== 'owner') {
                echo json_encode([
                    "success" => false,
                    "message" => "Only owner can delete group"
                ]);
                exit;
            }

            try {
                $pdo->beginTransaction();

                // 1. remove event links (IMPORTANT)
                $stmt = $pdo->prepare("DELETE FROM event_groups WHERE group_id = ?");
                $stmt->execute([$group_id]);

                // 2. remove members
                $stmt = $pdo->prepare("DELETE FROM group_members WHERE group_id = ?");
                $stmt->execute([$group_id]);

                // 3. delete group
                $stmt = $pdo->prepare("DELETE FROM user_groups WHERE group_id = ?");
                $stmt->execute([$group_id]);

                $pdo->commit();

                echo json_encode([
                    "success" => true,
                    "message" => "Group deleted permanently"
                ]);
                exit;
            } catch (PDOException $e) {
                $pdo->rollBack();

                echo json_encode([
                    "success" => false,
                    "message" => "Delete failed"
                ]);
                exit;
            }
        }

        // fallback
        echo json_encode([
            "success" => false,
            "message" => "Unknown action"
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            "success" => false,
            "message" => "Database error"
        ]);
    }
}
