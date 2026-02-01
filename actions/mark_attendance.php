<?php
session_start();
include '../config/db_connect.php';
include '../includes/functions.php';

// Check if user is a lecturer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'lecturer') {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $course_id = intval($_POST['course_id']);
    $date = $_POST['date'];
    $status_array = $_POST['status']; // Array of student_id => status

    // Validate date
    if (empty($date)) {
        $_SESSION['error'] = "Please select a date";
        header("Location: ../lecturer/take_attendance.php?course_id=" . $course_id);
        exit();
    }

    // Check if attendance already exists for this date and course
    $check_sql = "SELECT id FROM attendance WHERE course_id = ? AND date = ? LIMIT 1";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("is", $course_id, $date);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    $students_updated = [];

    if ($check_result->num_rows > 0) {
        // Update existing attendance
        foreach ($status_array as $student_id => $status) {
            $student_id = intval($student_id);
            $status = ($status == 'present') ? 'present' : 'absent';

            $update_sql = "UPDATE attendance SET status = ? WHERE student_id = ? AND course_id = ? AND date = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("siis", $status, $student_id, $course_id, $date);
            $update_stmt->execute();

            $students_updated[] = $student_id;
        }
        $_SESSION['success'] = "Attendance updated successfully";
    } else {
        // Insert new attendance records
        foreach ($status_array as $student_id => $status) {
            $student_id = intval($student_id);
            $status = ($status == 'present') ? 'present' : 'absent';

            $insert_sql = "INSERT INTO attendance (student_id, course_id, date, status) VALUES (?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("iiss", $student_id, $course_id, $date, $status);
            $insert_stmt->execute();

            $students_updated[] = $student_id;
        }
        $_SESSION['success'] = "Attendance saved successfully";
    }

    // Record attendance snapshots for trend tracking
    foreach ($students_updated as $student_id) {
        recordAttendanceSnapshot($conn, $student_id, $course_id);
    }

    header("Location: ../lecturer/dashboard.php");
    exit();
}

$conn->close();
?>
