<?php
session_start();

// Redirect based on login state
if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['role']) {
        case 'admin':    header("Location: admin/dashboard.php");    break;
        case 'lecturer': header("Location: lecturer/dashboard.php"); break;
        case 'student':  header("Location: student/dashboard.php");  break;
        default:         header("Location: login.php");              break;
    }
} else {
    header("Location: landing.php");
}
exit();
