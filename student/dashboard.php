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

// Check for classes today (simple reminder system)
$today = date("l"); // e.g., "Monday"

// Get courses for this student
$sql = "SELECT c.course_name, c.course_id, c.course_code
        FROM courses c
        JOIN enrollments e ON c.course_id = e.course_id
        WHERE e.student_id = $student_id";
$result = $conn->query($sql);
?>

<div class="container">
    <div class="page-header">
        <h2>My Attendance</h2>
        <p>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</p>
    </div>

    <?php displayFlashMessage(); ?>

    <!-- Simple Reminder Banner -->
    <div class="notification-banner">
        Today is <?php echo $today; ?>. Don't forget to attend your classes!
    </div>

    <?php if($result->num_rows > 0): ?>
    <div class="cards-grid">
        <?php while($row = $result->fetch_assoc()): ?>
            <?php
                // --- AUTOMATIC CALCULATION LOGIC ---
                $course_id = $row['course_id'];
                $attendance = calculateAttendancePercentage($conn, $student_id, $course_id);
                $percentage = $attendance['percentage'];
                $total_classes = $attendance['total_classes'];
                $classes_attended = $attendance['classes_attended'];
            ?>

            <div class="card">
                <h3><?php echo htmlspecialchars($row['course_code']); ?></h3>
                <p><?php echo htmlspecialchars($row['course_name']); ?></p>

                <div class="percentage-circle" data-percentage="<?php echo $percentage; ?>">
                    <span><?php echo $percentage; ?>%</span>
                </div>

                <?php if(isLowAttendance($percentage)): ?>
                    <div class="alert warning">
                        Warning: Low Attendance!
                    </div>
                <?php else: ?>
                    <div class="alert good">
                        You are on track.
                    </div>
                <?php endif; ?>

                <p>Attended: <?php echo $classes_attended; ?> / <?php echo $total_classes; ?> classes</p>
            </div>
        <?php endwhile; ?>
    </div>
    <?php else: ?>
        <div class="alert warning">
            You are not enrolled in any courses yet.
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
