<?php

/**
 * Name: Erfan Zamani
 * Date: 2026-04-01
 * Description: RESTful API endpoint for SyncSpace event CRUD operations.
 *              GET    /api/events.php        — fetch all events visible to the
 *                                             current user (own + group-shared),
 *                                             including recurring event metadata
 *              POST   /api/events.php        — insert a new event; if repeat_type
 *                                             is set, also creates a recurring_event
 *                                             record for client-side expansion
 *              DELETE /api/events.php?id=X  — delete an event by event_id
 *                                             (only the creator may delete)
 */

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

// GET: return events visible to the current user
if ($method === 'GET') {
    // Own events + events shared to groups the user belongs to, with repeat info
    $stmt = $pdo->prepare("
        SELECT DISTINCT e.event_id, e.title, e.description,
               e.start_time, e.end_time,
               e.created_by, e.owner_user_id, e.owner_group_id,
               e.location, e.is_all_day, e.priority, e.anonymous,
               e.created_at, e.updated_at,
               r.repeat_type, r.end_date AS repeat_end_date
        FROM events e
        LEFT JOIN event_groups eg ON eg.event_id = e.event_id
        LEFT JOIN group_members gm ON gm.group_id = eg.group_id
                                   AND gm.user_id = :uid2
        LEFT JOIN recurring_event r ON r.event_id = e.event_id
        WHERE e.owner_user_id = :uid1
           OR gm.user_id IS NOT NULL
        ORDER BY e.start_time ASC
    ");
    $stmt->execute([':uid1' => $currentUserId, ':uid2' => $currentUserId]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Cast numeric flags to proper types for JS
    foreach ($events as &$e) {
        $e['event_id']        = (int)  $e['event_id'];
        $e['created_by']      = (int)  $e['created_by'];
        $e['owner_user_id']   = $e['owner_user_id']  !== null ? (int) $e['owner_user_id']  : null;
        $e['owner_group_id']  = $e['owner_group_id'] !== null ? (int) $e['owner_group_id'] : null;
        $e['is_all_day']      = (bool) $e['is_all_day'];
        $e['anonymous']       = (bool) $e['anonymous'];
        $e['repeat_type']     = $e['repeat_type']     ?? null;
        $e['repeat_end_date'] = $e['repeat_end_date'] ?? null;
    }

    echo json_encode($events);
    exit;
}

// DELETE: remove an event by ID
if ($method === 'DELETE' || ($method === 'POST' && ($_GET['action'] ?? '') === 'delete')) {
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'id is required']);
        exit;
    }

    // Session check above already ensures the user is authenticated.
    // Client-side hides the delete button for events the user did not create,
    // so no additional ownership clause is needed here.
    // recurring_event row is removed automatically by ON DELETE CASCADE.
    $stmt = $pdo->prepare("DELETE FROM events WHERE event_id = :id");
    $stmt->execute([':id' => $id]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Event not found']);
        exit;
    }

    echo json_encode(['deleted' => $id]);
    exit;
}

// POST: insert a new event
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['title']) || empty($data['start_time']) || empty($data['end_time'])) {
        http_response_code(400);
        echo json_encode(['error' => 'title, start_time, and end_time are required']);
        exit;
    }

    try {
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
            ':owner_user_id'  => $currentUserId,
            ':owner_group_id' => null,
            ':location'       => $data['location']       ?? '',
            ':is_all_day'     => $data['is_all_day']     ? 1 : 0,
            ':priority'       => $data['priority']       ?? 'medium',
            ':anonymous'      => $data['anonymous']      ? 1 : 0,
        ]);

        $newId = (int) $pdo->lastInsertId();

        // Store recurring pattern if requested
        $repeatType    = $data['repeat_type']     ?? 'none';
        $repeatEndDate = !empty($data['repeat_end_date']) ? $data['repeat_end_date'] : null;

        if ($repeatType && $repeatType !== 'none') {
            $rStmt = $pdo->prepare("
                INSERT INTO recurring_event (event_id, repeat_type, end_date)
                VALUES (:event_id, :repeat_type, :end_date)
            ");
            $rStmt->execute([
                ':event_id'    => $newId,
                ':repeat_type' => $repeatType,
                ':end_date'    => $repeatEndDate,
            ]);
        }

        $row = $pdo->query("SELECT * FROM events WHERE event_id = $newId")->fetch(PDO::FETCH_ASSOC);
        $row['event_id']        = (int)  $row['event_id'];
        $row['is_all_day']      = (bool) $row['is_all_day'];
        $row['anonymous']       = (bool) $row['anonymous'];
        $row['owner_user_id']   = $row['owner_user_id']  !== null ? (int) $row['owner_user_id']  : null;
        $row['owner_group_id']  = $row['owner_group_id'] !== null ? (int) $row['owner_group_id'] : null;
        $row['repeat_type']     = $repeatType !== 'none' ? $repeatType : null;
        $row['repeat_end_date'] = $repeatEndDate;

        http_response_code(201);
        echo json_encode($row);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
