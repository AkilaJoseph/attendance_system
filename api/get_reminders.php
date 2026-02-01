<?php
session_start();
header('Content-Type: application/json');

// Security: Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not authenticated', 'reminders' => [], 'count' => 0]);
    exit();
}

include '../config/db_connect.php';
include '../includes/functions.php';

$user_id = $_SESSION['user_id'];

// Get active reminders
$reminders_result = getActiveReminders($conn, $user_id);
$reminders = [];

while ($row = $reminders_result->fetch_assoc()) {
    $reminders[] = [
        'reminder_id' => $row['reminder_id'],
        'event_id' => $row['event_id'],
        'title' => $row['title'],
        'event_type' => $row['event_type'],
        'event_type_name' => getEventTypeName($row['event_type']),
        'event_date' => $row['event_date'],
        'event_time' => $row['event_time'],
        'description' => $row['description'],
        'time_until' => getTimeUntilEvent($row['event_date'], $row['event_time']),
        'formatted_date' => date('M j, Y', strtotime($row['event_date'])),
        'formatted_time' => $row['event_time'] ? date('g:i A', strtotime($row['event_time'])) : null
    ];
}

// Get total count
$count = getReminderCount($conn, $user_id);

echo json_encode([
    'success' => true,
    'reminders' => $reminders,
    'count' => $count
]);

$conn->close();
?>
