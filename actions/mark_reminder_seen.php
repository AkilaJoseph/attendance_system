<?php
session_start();
header('Content-Type: application/json');

// Security: Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

include '../config/db_connect.php';

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Mark all reminders as seen
    if (isset($_POST['mark_all']) && $_POST['mark_all']) {
        $stmt = $conn->prepare("UPDATE reminders SET is_seen = TRUE, notified_at = NOW() WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'All reminders marked as seen']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error']);
        }
    }
    // Mark single reminder as seen
    elseif (isset($_POST['reminder_id'])) {
        $reminder_id = intval($_POST['reminder_id']);

        // Verify user owns this reminder
        $stmt = $conn->prepare("UPDATE reminders SET is_seen = TRUE, notified_at = NOW() WHERE reminder_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $reminder_id, $user_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Reminder marked as seen']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'No reminder specified']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}

$conn->close();
?>
