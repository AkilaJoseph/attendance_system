<?php
session_start();
include '../config/db_connect.php';

// Security: Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$redirect = isset($_POST['redirect']) ? $_POST['redirect'] : 'student/timetable.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['event_id'])) {
    $event_id = intval($_POST['event_id']);

    // Verify user owns this event (or is admin)
    $check = $conn->prepare("SELECT user_id, is_course_event FROM events WHERE event_id = ?");
    $check->bind_param("i", $event_id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $event = $result->fetch_assoc();

        // Allow deletion if user owns the event or is admin
        if ($event['user_id'] == $user_id || $_SESSION['role'] == 'admin') {
            // Delete reminders first (due to foreign key)
            $delete_reminders = $conn->prepare("DELETE FROM reminders WHERE event_id = ?");
            $delete_reminders->bind_param("i", $event_id);
            $delete_reminders->execute();

            // Delete the event
            $delete_event = $conn->prepare("DELETE FROM events WHERE event_id = ?");
            $delete_event->bind_param("i", $event_id);

            if ($delete_event->execute()) {
                $_SESSION['flash_message'] = "Event deleted successfully";
                $_SESSION['flash_type'] = "success";
            } else {
                $_SESSION['flash_message'] = "Error deleting event";
                $_SESSION['flash_type'] = "error";
            }
        } else {
            $_SESSION['flash_message'] = "You can only delete your own events";
            $_SESSION['flash_type'] = "error";
        }
    } else {
        $_SESSION['flash_message'] = "Event not found";
        $_SESSION['flash_type'] = "error";
    }
}

header("Location: ../" . $redirect);
exit();
?>
