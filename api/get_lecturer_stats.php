<?php
session_start();
header('Content-Type: application/json');
include '../config/db_connect.php';
include '../includes/functions.php';

// Check if user is logged in and is a lecturer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'lecturer') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$lecturer_id = $_SESSION['user_id'];

try {
    // Get courses taught by this lecturer
    $courses_result = getLecturerCourses($conn, $lecturer_id);
    $courses_data = [];
    $total_students = 0;
    $total_at_risk = 0;
    $total_avg_attendance = 0;
    $course_count = 0;

    while ($course = $courses_result->fetch_assoc()) {
        $stats = getCourseStatistics($conn, $course['course_id']);
        $stats['trend'] = 'stable';

        // Calculate trend
        if ($stats['total_students'] > 0) {
            $enrolled = getEnrolledStudents($conn, $course['course_id']);
            $trends = ['improving' => 0, 'declining' => 0, 'stable' => 0, 'new' => 0];
            while ($student = $enrolled->fetch_assoc()) {
                $trend = calculateAttendanceTrend($conn, $student['user_id'], $course['course_id']);
                $trends[$trend]++;
            }
            if ($trends['improving'] > $trends['declining'] + 2) {
                $stats['trend'] = 'improving';
            } elseif ($trends['declining'] > $trends['improving'] + 2) {
                $stats['trend'] = 'declining';
            }
        }

        $courses_data[] = $stats;
        $total_students += $stats['total_students'];
        $total_at_risk += $stats['at_risk_count'];
        $total_avg_attendance += $stats['avg_attendance'] ?? 0;
        $course_count++;
    }

    $overall_avg = $course_count > 0 ? round($total_avg_attendance / $course_count, 1) : 0;

    // Get weekly classes count
    $week_start = getWeekStart();
    $classes_query = "SELECT COUNT(DISTINCT cs.schedule_id) as weekly_classes
                      FROM class_schedule cs
                      JOIN courses c ON cs.course_id = c.course_id
                      WHERE c.lecturer_id = ? AND cs.is_active = TRUE";
    $classes_stmt = $conn->prepare($classes_query);
    $classes_stmt->bind_param("i", $lecturer_id);
    $classes_stmt->execute();
    $weekly_classes = $classes_stmt->get_result()->fetch_assoc()['weekly_classes'] ?? 0;

    echo json_encode([
        'success' => true,
        'overview' => [
            'total_students' => $total_students,
            'total_at_risk' => $total_at_risk,
            'overall_avg' => $overall_avg,
            'weekly_classes' => $weekly_classes
        ],
        'courses' => $courses_data
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>
