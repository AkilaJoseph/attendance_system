<?php
session_start();
include '../config/db_connect.php';
include '../includes/functions.php';

// Security: Check if user is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: ../index.php");
    exit();
}

include '../includes/header.php';

$student_id = $_SESSION['user_id'];

// Get enrolled courses with lecturer info
$query = "
    SELECT
        c.course_id,
        c.course_name,
        c.course_code,
        u.name as lecturer_name,
        u.email as lecturer_email
    FROM courses c
    JOIN enrollments e ON c.course_id = e.course_id
    LEFT JOIN users u ON c.lecturer_id = u.user_id
    WHERE e.student_id = ?
    ORDER BY c.course_name
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$courses = $stmt->get_result();
?>

<div class="container">
    <div class="page-header">
        <h2>My Courses</h2>
        <p>View your enrolled courses and lecturers</p>
    </div>

    <?php displayFlashMessage(); ?>

    <?php if ($courses->num_rows > 0): ?>
    <div class="cards-grid">
        <?php while ($course = $courses->fetch_assoc()):
            $attendance = calculateAttendancePercentage($conn, $student_id, $course['course_id']);
        ?>
        <div class="card">
            <h3><?php echo htmlspecialchars($course['course_code']); ?></h3>
            <p style="font-size: 1.1rem; margin-bottom: 15px;"><?php echo htmlspecialchars($course['course_name']); ?></p>

            <div style="padding: 15px; background: var(--dark-bg); border-radius: 8px; margin-bottom: 15px;">
                <p style="opacity: 0.7; margin-bottom: 5px;">Lecturer</p>
                <p style="font-weight: 600;"><?php echo htmlspecialchars($course['lecturer_name'] ?? 'Not Assigned'); ?></p>
                <?php if ($course['lecturer_email']): ?>
                    <p style="font-size: 0.9rem; opacity: 0.7;"><?php echo htmlspecialchars($course['lecturer_email']); ?></p>
                <?php endif; ?>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <p style="opacity: 0.7; font-size: 0.9rem;">Attendance</p>
                    <p style="font-size: 1.5rem; font-weight: bold; color: <?php
                        if ($attendance['total_classes'] == 0) echo 'var(--dark-text)';
                        elseif ($attendance['percentage'] < 50) echo 'var(--danger-color)';
                        elseif ($attendance['percentage'] < 75) echo 'var(--warning-color)';
                        else echo 'var(--success-color)';
                    ?>">
                        <?php echo $attendance['percentage']; ?>%
                    </p>
                </div>
                <div style="text-align: right;">
                    <p style="opacity: 0.7; font-size: 0.9rem;">Classes</p>
                    <p><?php echo $attendance['classes_attended']; ?> / <?php echo $attendance['total_classes']; ?></p>
                </div>
            </div>

            <?php if ($attendance['total_classes'] > 0 && isLowAttendance($attendance['percentage'])): ?>
                <div class="alert warning" style="margin-top: 15px; margin-bottom: 0;">
                    Attendance below 75% - please attend more classes!
                </div>
            <?php endif; ?>
        </div>
        <?php endwhile; ?>
    </div>
    <?php else: ?>
        <div class="alert warning">
            You are not enrolled in any courses yet. Please contact your administrator.
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
