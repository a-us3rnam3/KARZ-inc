<?php
/**
 * Date: 2026-04-04
 * Description: API endpoint for sharing SyncSpace events with groups. Manages
 *              records in the event_groups junction table.
 *              POST   /api/event_groups.php  — share an event with a group
 *              GET    /api/event_groups.php  — get events for a group (TODO)
 *              DELETE /api/event_groups.php  — unshare event from group (TODO)
 */

session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

// Get POST data
$data = json_decode(file_get_contents("php://input"), true);

$event_id = isset($data['event_id']) ? (int) $data['event_id'] : null;
$group_id = isset($data['group_id']) ? (int) $data['group_id'] : null;
$anonymous = isset($data['anonymous']) ? (bool) $data['anonymous'] : false;

// Validate input
if (!$event_id || !$group_id) {

    echo json_encode([
        "success" => false,
        "message" => "Valid event_id and group_id are required"
    ]);
    exit;
}

try {
    // 1. Check if event exists
    $stmt = $pdo->prepare("SELECT event_id FROM events WHERE event_id = ? LIMIT 1");
    $stmt->execute([$event_id]);

    if ($stmt->rowCount() === 0) {
        echo json_encode([
            "success" => false,
            "message" => "Event not found"
        ]);
        exit;
    }

    // 2. Check if group exists
    $stmt = $pdo->prepare("SELECT group_id FROM user_groups WHERE group_id = ? LIMIT 1");
    $stmt->execute([$group_id]);

    if ($stmt->rowCount() === 0) {
        echo json_encode([
            "success" => false,
            "message" => "Group not found"
        ]);
        exit;
    }

    // 3. Prevent duplicate sharing
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

    // 4. Insert into event_groups
    $stmt = $pdo->prepare("
        INSERT INTO event_groups (event_id, group_id, anonymous)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$event_id, $group_id, (int)$anonymous]);

    echo json_encode([
        "success" => true,
        "message" => "Event shared with group successfully"
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage() //CHANGE MESSAGE BEFORE FINISH
    ]);
}