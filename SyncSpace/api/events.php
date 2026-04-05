<?php
// SyncSpace — Events API
// Erfan Zamani
//
// GET    /api/events.php          — fetch all events
// POST   /api/events.php          — insert a new event
// DELETE /api/events.php?id=X     — delete an event by event_id

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();
require 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$currentUserId = (int)$_SESSION['user_id'];

// ─── GET: return all events ───────────────────────────────────────────────────
if ($method === 'GET') {
    $stmt = $pdo->query("
        SELECT event_id, title, description,
               start_time, end_time,
               created_by, owner_user_id, owner_group_id,
               location, is_all_day, priority, anonymous,
               created_at, updated_at
        FROM events
        ORDER BY start_time ASC
    ");
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Cast numeric flags to proper types for JS
    foreach ($events as &$e) {
        $e['event_id']       = (int)  $e['event_id'];
        $e['created_by']     = (int)  $e['created_by'];
        $e['owner_user_id']  = $e['owner_user_id']  !== null ? (int) $e['owner_user_id']  : null;
        $e['owner_group_id'] = $e['owner_group_id'] !== null ? (int) $e['owner_group_id'] : null;
        $e['is_all_day']     = (bool) $e['is_all_day'];
        $e['anonymous']      = (bool) $e['anonymous'];
    }

    echo json_encode($events);
    exit;
}

// ─── POST: insert a new event ─────────────────────────────────────────────────
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['title']) || empty($data['start_time']) || empty($data['end_time'])) {
        http_response_code(400);
        echo json_encode(['error' => 'title, start_time, and end_time are required']);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO events
            (title, description, start_time, end_time,
             created_by, owner_user_id, owner_group_id,
             location, is_all_day, priority, anonymous)
        VALUES
            (:title, :description, :start_time, :end_time,
             :created_by, :owner_user_id, :owner_group_id,
             :location, :is_all_day, :priority, :anonymous)
    ");

    $stmt->execute([
        ':title'          => $data['title'],
        ':description'    => $data['description']    ?? '',
        ':start_time'     => $data['start_time'],
        ':end_time'       => $data['end_time'],
        ':created_by'     => $currentUserId,
        ':owner_user_id'  => $data['owner_group_id'] ? null : $currentUserId,
        ':owner_group_id' => $data['owner_group_id'] ?? null,
        ':location'       => $data['location']       ?? '',
        ':is_all_day'     => $data['is_all_day']     ? 1 : 0,
        ':priority'       => $data['priority']       ?? 'medium',
        ':anonymous'      => $data['anonymous']      ? 1 : 0,
    ]);

    $newId = (int) $pdo->lastInsertId();

    // Return the full inserted row so JS can update its local list
    $row = $pdo->query("SELECT * FROM events WHERE event_id = $newId")->fetch(PDO::FETCH_ASSOC);
    $row['event_id']       = (int)  $row['event_id'];
    $row['is_all_day']     = (bool) $row['is_all_day'];
    $row['anonymous']      = (bool) $row['anonymous'];
    $row['owner_user_id']  = $row['owner_user_id']  !== null ? (int) $row['owner_user_id']  : null;
    $row['owner_group_id'] = $row['owner_group_id'] !== null ? (int) $row['owner_group_id'] : null;

    http_response_code(201);
    echo json_encode($row);
    exit;
}

// ─── DELETE: remove an event by ID ───────────────────────────────────────────
if ($method === 'DELETE') {
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'id is required']);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM events WHERE event_id = :id");
    $stmt->execute([':id' => $id]);

    echo json_encode(['deleted' => $id]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
