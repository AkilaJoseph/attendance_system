<?php
session_start();
include '../config/db_connect.php';
include '../includes/header.php';
include '../includes/functions.php';

// Security: Check if user is a lecturer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'lecturer') {
    header("Location: ../index.php");
    exit();
}

$lecturer_id = $_SESSION['user_id'];

// Get courses taught by this lecturer
$courses_result = getLecturerCourses($conn, $lecturer_id);
$courses_data = [];
$total_students = 0;
$total_at_risk = 0;
$total_avg_attendance = 0;
$course_count = 0;

// Collect course statistics
while ($course = $courses_result->fetch_assoc()) {
    $stats = getCourseStatistics($conn, $course['course_id']);
    $stats['trend'] = 'stable'; // Default trend

    // Calculate trend based on recent snapshots
    if ($stats['total_students'] > 0) {
        // Get a sample student to check trend (or could average all)
        $enrolled = getEnrolledStudents($conn, $course['course_id']);
        $trends = ['improving' => 0, 'declining' => 0, 'stable' => 0, 'new' => 0];
        while ($student = $enrolled->fetch_assoc()) {
            $trend = calculateAttendanceTrend($conn, $student['user_id'], $course['course_id']);
            $trends[$trend]++;
        }
        // Determine overall course trend
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

// Get total classes this week
$week_start = getWeekStart();
$classes_query = "SELECT COUNT(DISTINCT cs.schedule_id) as weekly_classes
                  FROM class_schedule cs
                  JOIN courses c ON cs.course_id = c.course_id
                  WHERE c.lecturer_id = ? AND cs.is_active = TRUE";
$classes_stmt = $conn->prepare($classes_query);
$classes_stmt->bind_param("i", $lecturer_id);
$classes_stmt->execute();
$weekly_classes = $classes_stmt->get_result()->fetch_assoc()['weekly_classes'] ?? 0;
?>

<div class="container">
    <div class="page-header">
        <h2>Lecturer Dashboard</h2>
        <p>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</p>
    </div>

    <?php displayFlashMessage(); ?>

    <!-- Overview Stats Row -->
    <div class="overview-stats">
        <div class="stat-card">
            <div class="stat-icon">
                <span class="icon-users"></span>
            </div>
            <div class="stat-content">
                <span class="stat-value"><?php echo $total_students; ?></span>
                <span class="stat-label">Total Students</span>
            </div>
        </div>

        <div class="stat-card <?php echo $total_at_risk > 0 ? 'stat-warning' : ''; ?>">
            <div class="stat-icon">
                <span class="icon-alert"></span>
            </div>
            <div class="stat-content">
                <span class="stat-value"><?php echo $total_at_risk; ?></span>
                <span class="stat-label">At-Risk Students</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <span class="icon-percent"></span>
            </div>
            <div class="stat-content">
                <span class="stat-value"><?php echo $overall_avg; ?>%</span>
                <span class="stat-label">Avg Attendance</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <span class="icon-calendar"></span>
            </div>
            <div class="stat-content">
                <span class="stat-value"><?php echo $weekly_classes; ?></span>
                <span class="stat-label">Weekly Classes</span>
            </div>
        </div>
    </div>

    <h3>Your Courses</h3>

    <?php if(count($courses_data) > 0): ?>
        <div class="course-cards">
            <?php foreach($courses_data as $course): ?>
            <div class="course-summary-card">
                <div class="course-header">
                    <span class="course-code"><?php echo htmlspecialchars($course['course_code']); ?></span>
                    <span class="trend-indicator trend-<?php echo $course['trend']; ?>">
                        <?php
                        if ($course['trend'] === 'improving') echo '&#8599;'; // ↗
                        elseif ($course['trend'] === 'declining') echo '&#8600;'; // ↘
                        else echo '&#8594;'; // →
                        ?>
                    </span>
                </div>
                <div class="course-name"><?php echo htmlspecialchars($course['course_name']); ?></div>

                <div class="course-stats">
                    <div class="mini-stat">
                        <span class="mini-value"><?php echo $course['total_students']; ?></span>
                        <span class="mini-label">Students</span>
                    </div>
                    <div class="mini-stat <?php echo $course['at_risk_count'] > 0 ? 'mini-warning' : ''; ?>">
                        <span class="mini-value"><?php echo $course['at_risk_count']; ?></span>
                        <span class="mini-label">At-Risk</span>
                    </div>
                    <div class="mini-stat">
                        <span class="mini-value"><?php echo $course['avg_attendance'] ?? 0; ?>%</span>
                        <span class="mini-label">Avg</span>
                    </div>
                    <div class="mini-stat">
                        <span class="mini-value"><?php echo $course['total_classes']; ?></span>
                        <span class="mini-label">Classes</span>
                    </div>
                </div>

                <div class="course-actions">
                    <a href="take_attendance.php?course_id=<?php echo $course['course_id']; ?>" class="btn btn-primary btn-sm">
                        Take Attendance
                    </a>
                    <a href="reports.php?course_id=<?php echo $course['course_id']; ?>" class="btn btn-secondary btn-sm">
                        View Reports
                    </a>
                    <a href="schedule.php?course_id=<?php echo $course['course_id']; ?>" class="btn btn-secondary btn-sm">
                        Schedule
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert warning">
            You have no courses assigned yet.
        </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <h3>Quick Actions</h3>
    <div class="quick-actions">
        <a href="events.php" class="quick-action-card">
            <span class="quick-icon">&#128197;</span>
            <span class="quick-label">Manage Events</span>
        </a>
        <a href="schedule.php" class="quick-action-card">
            <span class="quick-icon">&#128336;</span>
            <span class="quick-label">Class Schedule</span>
        </a>
        <a href="reports.php" class="quick-action-card">
            <span class="quick-icon">&#128200;</span>
            <span class="quick-label">All Reports</span>
        </a>
        <a href="settings.php" class="quick-action-card">
            <span class="quick-icon">&#9881;</span>
            <span class="quick-label">Settings</span>
        </a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
