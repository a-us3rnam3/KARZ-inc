<?php

/**
 * Name: Marcus Rotaru
 * Date: 2026-04-04
 * Description: API endpoint for sharing SyncSpace events with groups. Manages
 *              records in the event_groups junction table.
 *              POST   /api/event_groups.php  — share an event with a group
 *              GET /api/event_groups.php?group_id=#  — get events for a group OR unshare
 */

session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

try {

    // get all events associated with a group_id
    if ($method === 'GET') {

        if (!isset($_GET['group_id'])) {
            echo json_encode([
                "success" => false,
                "message" => "group_id is required"
            ]);
            exit;
        }

        $group_id = (int) $_GET['group_id'];

        //AUTH CHECK — must be group member
        $stmt = $pdo->prepare("
        SELECT 1 
        FROM group_members 
        WHERE group_id = ? AND user_id = ?
        LIMIT 1
    ");
        $stmt->execute([$group_id, $_SESSION['user_id']]);

        if ($stmt->rowCount() === 0) {
            http_response_code(403);
            echo json_encode([
                "success" => false,
                "message" => "Forbidden: not a group member"
            ]);
            exit;
        }

        // Fetch events
        $stmt = $pdo->prepare("
        SELECT e.*, eg.anonymous
        FROM event_groups eg
        JOIN events e ON e.event_id = eg.event_id
        WHERE eg.group_id = ?
        ORDER BY e.start_time ASC
    ");
        $stmt->execute([$group_id]);

        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            "success" => true,
            "events" => $events
        ]);
        exit;
    }



    // POST then Existing logic 
    if ($method === 'POST') {

        $data = json_decode(file_get_contents("php://input"), true);

        $event_id = isset($data['event_id']) ? (int)$data['event_id'] : null;
        $group_id = isset($data['group_id']) ? (int)$data['group_id'] : null;
        $anonymous = isset($data['anonymous']) ? (bool)$data['anonymous'] : false;
        $action = $data['action'] ?? 'share';

        // UNSHARE
        if ($action === 'unshare') {

            if (!$event_id || !$group_id) {
                echo json_encode([
                    "success" => false,
                    "message" => "event_id and group_id are required"
                ]);
                exit;
            }

            // AUTH CHECK
            $stmt = $pdo->prepare("
            SELECT 1 
            FROM group_members 
            WHERE group_id = ? AND user_id = ?
            LIMIT 1
        ");
            $stmt->execute([$group_id, $_SESSION['user_id']]);

            if ($stmt->rowCount() === 0) {
                http_response_code(403);
                echo json_encode([
                    "success" => false,
                    "message" => "Forbidden: not a group member"
                ]);
                exit;
            }

            $stmt = $pdo->prepare("
            DELETE FROM event_groups
            WHERE event_id = ? AND group_id = ?
        ");
            $stmt->execute([$event_id, $group_id]);

            echo json_encode([
                "success" => true,
                "message" => "Event removed from group"
            ]);
            exit;
        }

        // SHARE
        if ($action === 'share') {

            if (!$event_id || !$group_id) {
                echo json_encode([
                    "success" => false,
                    "message" => "Valid event_id and group_id are required"
                ]);
                exit;
            }

            // Check event exists
            $stmt = $pdo->prepare("SELECT event_id FROM events WHERE event_id = ? LIMIT 1");
            $stmt->execute([$event_id]);

            if ($stmt->rowCount() === 0) {
                echo json_encode([
                    "success" => false,
                    "message" => "Event not found"
                ]);
                exit;
            }

            // Check group exists
            $stmt = $pdo->prepare("SELECT group_id FROM user_groups WHERE group_id = ? LIMIT 1");
            $stmt->execute([$group_id]);

            if ($stmt->rowCount() === 0) {
                echo json_encode([
                    "success" => false,
                    "message" => "Group not found"
                ]);
                exit;
            }

            // Prevent duplicates
            $stmt = $pdo->prepare("
            SELECT event_group_id 
            FROM event_groups 
            WHERE event_id = ? AND group_id = ?
        ");
            $stmt->execute([$event_id, $group_id]);

            if ($stmt->rowCount() > 0) {
                echo json_encode([
                    "success" => false,
                    "message" => "Event already shared with this group"
                ]);
                exit;
            }

            $stmt = $pdo->prepare("
            INSERT INTO event_groups (event_id, group_id, anonymous)
            VALUES (?, ?, ?)
        ");
            $stmt->execute([$event_id, $group_id, (int)$anonymous]);

            echo json_encode([
                "success" => true,
                "message" => "Event shared with group successfully"
            ]);
            exit;
        }

        // Unknown action
        echo json_encode([
            "success" => false,
            "message" => "Unknown action"
        ]);
        exit;
    }

    // Fallback
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Method not allowed"
    ]);
} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Database error"
    ]);
}
