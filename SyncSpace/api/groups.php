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
$DEBUG = true; // ⚠️ set to false in production

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

// // ─── DELETE: Leave group ─────────────────────────────
// if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {

//     $data = json_decode(file_get_contents("php://input"), true);
//     $group_id = $data['group_id'] ?? null;

//     $debug = [
//         "user_id" => $user_id,
//         "group_id" => $group_id,
//     ];

//     if (!$group_id) {
//         echo json_encode([
//             "success" => false,
//             "message" => "group_id required",
//             "debug" => $debug
//         ]);
//         exit;
//     }

//     // Get membership info
//     $stmt = $pdo->prepare("
//         SELECT role 
//         FROM group_members
//         WHERE group_id = ? AND user_id = ?
//     ");
//     $stmt->execute([$group_id, $user_id]);
//     $member = $stmt->fetch();

//     $debug["membership_row"] = $member;

//     if (!$member) {
//         echo json_encode([
//             "success" => false,
//             "message" => "Not a member",
//             "debug" => $debug
//         ]);
//         exit;
//     }

//     // OWNER BLOCK CHECK
//     if ($member['role'] === 'owner') {

//         $debug["blocked_reason"] = "user_is_owner";

//         echo json_encode([
//             "success" => false,
//             "message" => "Owner cannot leave group",
//             "debug" => $debug
//         ]);
//         exit;
//     }

//     // Delete membership
//     $stmt = $pdo->prepare("
//         DELETE FROM group_members
//         WHERE group_id = ? AND user_id = ?
//     ");
//     $stmt->execute([$group_id, $user_id]);

//     $debug["deleted"] = $stmt->rowCount();

//     echo json_encode([
//         "success" => true,
//         "message" => "Left group",
//         "debug" => $debug
//     ]);
//     exit;
// }