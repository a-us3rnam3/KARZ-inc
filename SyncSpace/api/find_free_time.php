<?php
/**
 * Date: 2026-04-05
 * Description: Calculates common free time for all members of a selected group
 *              on a specific date. Retrieves all events for group members within
 *              the given day, constructs busy time intervals, merges overlapping
 *              intervals, and outputs available time slots where no member is busy.
 *              Supports all-day events and standard timed events.
 */
require_once 'db.php';

$group_id = $_POST['group_id'] ?? 0;
$date = $_POST['date'] ?? '';

if (!$group_id || !$date) {
    exit("Invalid input");
}

$day_start = "$date 08:00:00";
$day_end   = "$date 22:00:00";

/* 1. Get members */
$stmt = $pdo->prepare("
    SELECT user_id
    FROM group_members
    WHERE group_id = ?
");
$stmt->execute([$group_id]);
$members = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (!$members) {
    exit("No members in this group.");
}

$placeholders = implode(',', array_fill(0, count($members), '?'));

/* 2. Get events */
$params = $members;
$params[] = $day_end;
$params[] = $day_start;

$stmt = $pdo->prepare("
    SELECT start_time, end_time, is_all_day
    FROM events
    WHERE owner_user_id IN ($placeholders)
    AND start_time < ?
    AND end_time > ?
");
$stmt->execute($params);
$events = $stmt->fetchAll();

/* 3. Build busy intervals */
$busy = [];

foreach ($events as $ev) {
    if ($ev['is_all_day']) {
        $busy[] = [
            'start' => strtotime($day_start),
            'end' => strtotime($day_end)
        ];
    } else {
        $busy[] = [
            'start' => strtotime($ev['start_time']),
            'end' => strtotime($ev['end_time'])
        ];
    }
}

/* 4. Sort */
usort($busy, fn($a, $b) => $a['start'] <=> $b['start']);

/* 5. Merge */
$merged = [];

foreach ($busy as $b) {
    if (!$merged) {
        $merged[] = $b;
    } else {
        $last = &$merged[count($merged) - 1];
        if ($b['start'] <= $last['end']) {
            $last['end'] = max($last['end'], $b['end']);
        } else {
            $merged[] = $b;
        }
    }
}

/* 6. Find gaps */
$current = strtotime($day_start);
$end = strtotime($day_end);

echo "<h3>Available Times</h3>";

$found = false;

foreach ($merged as $m) {
    if ($m['start'] > $current) {
        echo "<p>" . date('g:i A', $current) . " - " . date('g:i A', $m['start']) . "</p>";
        $found = true;
    }
    $current = max($current, $m['end']);
}

if ($current < $end) {
    echo "<p>" . date('g:i A', $current) . " - " . date('g:i A', $end) . "</p>";
    $found = true;
}

if (!$found) {
    echo "<p>No common free time found.</p>";
}
