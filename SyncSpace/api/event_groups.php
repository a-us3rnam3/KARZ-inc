<!-- 
TODO
write up the visual aspect
implement GET and DELETE
-->

<?php
// SyncSpace — Events API
// Marcus Rotaru
//
// GET    /api/event_groups.php?group_id=X   — get events for a group  TO IMPLEMENT
// POST   /api/event_groups.php              — share event with group
// DELETE /api/event_groups.php              — unshare event from group TO IMPLEMENT

require_once 'db.php';

header('Content-Type: application/json');

// Get POST data
$data = json_decode(file_get_contents("php://input"), true);

$event_id = isset($data['event_id']) ? (int) $data['event_id'] : null;
$group_id = isset($data['group_id']) ? (int) $data['group_id'] : null;
$anonymous = isset($data['anonymous']) ? (bool) $data['anonymous'] : false;

// Validate input
if (
    $event_id === false || $event_id === null ||
    $group_id === false || $group_id === null
) {

    echo json_encode([
        "success" => false,
        "message" => "Valid event_id and group_id are required"
    ]);
    exit;
}

try {
    // 1. Check if event exists
    $stmt = $pdo->prepare("SELECT event_id FROM EVENTS WHERE event_id = ?");
    $stmt->execute([$event_id]);

    if ($stmt->rowCount() === 0) {
        echo json_encode([
            "success" => false,
            "message" => "Event not found"
        ]);
        exit;
    }

    // 2. Check if group exists
    $stmt = $pdo->prepare("SELECT group_id FROM GROUPS WHERE group_id = ?");
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
        FROM EVENT_GROUPS 
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

    // 4. Insert into EVENT_GROUPS
    $stmt = $pdo->prepare("
    INSERT INTO EVENT_GROUPS (event_id, group_id, anonymous)
    VALUES (?, ?, ?)
    ");
    $stmt->execute([$event_id, $group_id, $anonymous ? 1 : 0]);

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