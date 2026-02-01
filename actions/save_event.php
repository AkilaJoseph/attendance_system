<?php
session_start();
include '../config/db_connect.php';
include '../includes/functions.php';

// Security: Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$redirect = isset($_POST['redirect']) ? $_POST['redirect'] : 'student/timetable.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $title = trim($_POST['title']);
    $event_type = $_POST['event_type'];
    $event_date = $_POST['event_date'];
    $event_time = !empty($_POST['event_time']) ? $_POST['event_time'] : null;
    $description = trim($_POST['description'] ?? '');
    $course_id = !empty($_POST['course_id']) ? intval($_POST['course_id']) : null;
    $is_course_event = isset($_POST['is_course_event']) ? true : false;

    // Validate required fields
    if (empty($title) || empty($event_type) || empty($event_date)) {
        $_SESSION['flash_message'] = "Please fill in all required fields";
        $_SESSION['flash_type'] = "error";
        header("Location: ../" . $redirect);
        exit();
    }

    // Validate date is not in the past
    if (strtotime($event_date) < strtotime(date('Y-m-d'))) {
        $_SESSION['flash_message'] = "Event date cannot be in the past";
        $_SESSION['flash_type'] = "error";
        header("Location: ../" . $redirect);
        exit();
    }

    // If it's a course event, verify user is the lecturer
    if ($is_course_event && $course_id) {
        if ($_SESSION['role'] != 'lecturer' && $_SESSION['role'] != 'admin') {
            $_SESSION['flash_message'] = "Only lecturers can create course events";
            $_SESSION['flash_type'] = "error";
            header("Location: ../" . $redirect);
            exit();
        }

        // Verify lecturer owns this course
        $check = $conn->prepare("SELECT course_id FROM courses WHERE course_id = ? AND lecturer_id = ?");
        $check->bind_param("ii", $course_id, $user_id);
        $check->execute();
        if ($check->get_result()->num_rows == 0 && $_SESSION['role'] != 'admin') {
            $_SESSION['flash_message'] = "You can only create events for your own courses";
            $_SESSION['flash_type'] = "error";
            header("Location: ../" . $redirect);
            exit();
        }
    }

    // Add the event
    $event_id = addEvent($conn, $user_id, $title, $event_type, $event_date, $event_time, $description, $course_id, $is_course_event);

    if ($event_id) {
        // Add reminder if requested
        if (isset($_POST['add_reminder']) && $_POST['add_reminder']) {
            $remind_before = intval($_POST['remind_before']);
            $remind_unit = $_POST['remind_unit'];

            if ($remind_before > 0 && in_array($remind_unit, ['hours', 'days'])) {
                // For course events, add reminders for all enrolled students
                if ($is_course_event && $course_id) {
                    $students = getEnrolledStudents($conn, $course_id);
                    while ($student = $students->fetch_assoc()) {
                        addReminder($conn, $event_id, $student['user_id'], $remind_before, $remind_unit);
                    }
                    // Also add for the lecturer
                    addReminder($conn, $event_id, $user_id, $remind_before, $remind_unit);
                } else {
                    // Personal event - just add for the user
                    addReminder($conn, $event_id, $user_id, $remind_before, $remind_unit);
                }
            }
        }

        $_SESSION['flash_message'] = "Event added successfully";
        $_SESSION['flash_type'] = "success";
    } else {
        $_SESSION['flash_message'] = "Error adding event";
        $_SESSION['flash_type'] = "error";
    }
}

header("Location: ../" . $redirect);
exit();
?>
