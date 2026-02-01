<?php
session_start();
header('Content-Type: application/json');
include '../config/db_connect.php';
include '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Get week start date
$week_offset = isset($_GET['week']) ? intval($_GET['week']) : 0;
$week_start = getWeekStart(date('Y-m-d', strtotime("$week_offset weeks")));

try {
    $timetable = buildTimetableGrid($conn, $user_id, $week_start, $role);

    echo json_encode([
        'success' => true,
        'timetable' => $timetable
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>
