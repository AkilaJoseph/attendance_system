<?php
session_start();
include '../config/db_connect.php';
include '../includes/functions.php';

// Security: Check if user is a lecturer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'lecturer') {
    header("Location: ../index.php");
    exit();
}

include '../includes/header.php';

$lecturer_id = $_SESSION['user_id'];
$threshold = 75;

// Get courses taught by this lecturer
$courses_result = getLecturerCourses($conn, $lecturer_id);
$courses = [];
while ($row = $courses_result->fetch_assoc()) {
    $courses[] = $row;
}

// Get selected course (if any)
$selected_course = isset($_GET['course_id']) ? intval($_GET['course_id']) : (count($courses) > 0 ? $courses[0]['course_id'] : 0);

// Verify lecturer owns this course
$valid_course = false;
foreach ($courses as $c) {
    if ($c['course_id'] == $selected_course) {
        $valid_course = true;
        break;
    }
}
if (!$valid_course && count($courses) > 0) {
    $selected_course = $courses[0]['course_id'];
}

// Get attendance data for selected course
$attendance_data = [];
if ($selected_course > 0) {
    $query = "
        SELECT
            u.user_id,
            u.name as student_name,
            u.email,
            COUNT(DISTINCT a.date) as total_classes,
            SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as attended,
            CASE
                WHEN COUNT(DISTINCT a.date) > 0
                THEN ROUND((SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) / COUNT(DISTINCT a.date)) * 100)
                ELSE 100
            END as percentage
        FROM users u
        JOIN enrollments e ON u.user_id = e.student_id
        LEFT JOIN attendance a ON u.user_id = a.student_id AND a.course_id = e.course_id
        WHERE e.course_id = ? AND u.role = 'student'
        GROUP BY u.user_id
        ORDER BY percentage ASC, u.name
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $selected_course);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $attendance_data[] = $row;
    }
}

// Count at-risk students
$at_risk = 0;
foreach ($attendance_data as $student) {
    if ($student['percentage'] < $threshold && $student['total_classes'] > 0) {
        $at_risk++;
    }
}
?>

<div class="container">
    <div class="page-header">
        <h2>Attendance Reports</h2>
        <p>View attendance statistics for your courses</p>
    </div>

    <?php displayFlashMessage(); ?>

    <?php if (count($courses) == 0): ?>
        <div class="alert warning">You have no courses assigned yet.</div>
    <?php else: ?>

    <!-- Course Selector -->
    <div class="card" style="margin-bottom: 30px;">
        <h3>Select Course</h3>
        <form action="" method="GET" style="margin-top: 15px;">
            <div class="form-group">
                <select name="course_id" onchange="this.form.submit()">
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo $course['course_id']; ?>" <?php echo $selected_course == $course['course_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?php echo count($attendance_data); ?></div>
            <div class="stat-label">Enrolled Students</div>
        </div>
        <div class="stat-card" style="border: 2px solid var(--danger-color);">
            <div class="stat-number" style="color: var(--danger-color);"><?php echo $at_risk; ?></div>
            <div class="stat-label">Below <?php echo $threshold; ?>%</div>
        </div>
    </div>

    <?php if ($at_risk > 0): ?>
        <div class="alert danger" style="margin-bottom: 20px;">
            <strong>Alert:</strong> <?php echo $at_risk; ?> student(s) have attendance below <?php echo $threshold; ?>% and need attention.
        </div>
    <?php endif; ?>

    <!-- Attendance Table -->
    <h3>Student Attendance</h3>
    <?php if (count($attendance_data) > 0): ?>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Email</th>
                    <th>Classes Attended</th>
                    <th>Attendance %</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($attendance_data as $student): ?>
                <tr>
                    <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                    <td><?php echo $student['attended']; ?> / <?php echo $student['total_classes']; ?></td>
                    <td>
                        <strong style="color: <?php
                            if ($student['total_classes'] == 0) echo 'var(--dark-text)';
                            elseif ($student['percentage'] < 50) echo 'var(--danger-color)';
                            elseif ($student['percentage'] < $threshold) echo 'var(--warning-color)';
                            else echo 'var(--success-color)';
                        ?>">
                            <?php echo $student['percentage']; ?>%
                        </strong>
                    </td>
                    <td>
                        <?php if ($student['total_classes'] == 0): ?>
                            <span style="opacity: 0.6;">No classes yet</span>
                        <?php elseif ($student['percentage'] < 50): ?>
                            <span class="alert danger" style="display: inline-block; padding: 4px 8px; margin: 0;">Critical</span>
                        <?php elseif ($student['percentage'] < $threshold): ?>
                            <span class="alert warning" style="display: inline-block; padding: 4px 8px; margin: 0;">Warning</span>
                        <?php else: ?>
                            <span class="alert good" style="display: inline-block; padding: 4px 8px; margin: 0;">Good</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Mobile Card View -->
        <div class="table-responsive-cards">
            <?php foreach ($attendance_data as $student):
                $statusClass = '';
                $statusText = '';
                if ($student['total_classes'] == 0) {
                    $statusText = 'No classes yet';
                } elseif ($student['percentage'] < 50) {
                    $statusClass = 'danger';
                    $statusText = 'Critical';
                } elseif ($student['percentage'] < $threshold) {
                    $statusClass = 'warning';
                    $statusText = 'Warning';
                } else {
                    $statusClass = 'good';
                    $statusText = 'Good';
                }

                $percentColor = 'var(--dark-text)';
                if ($student['total_classes'] > 0) {
                    if ($student['percentage'] < 50) $percentColor = 'var(--danger-color)';
                    elseif ($student['percentage'] < $threshold) $percentColor = 'var(--warning-color)';
                    else $percentColor = 'var(--success-color)';
                }
            ?>
            <div class="table-card">
                <div class="table-card-header">
                    <div>
                        <div class="table-card-title"><?php echo htmlspecialchars($student['student_name']); ?></div>
                        <div class="table-card-subtitle"><?php echo htmlspecialchars($student['email']); ?></div>
                    </div>
                    <?php if ($statusClass): ?>
                    <span class="alert <?php echo $statusClass; ?>" style="display: inline-block; padding: 4px 10px; margin: 0; font-size: 0.8rem;">
                        <?php echo $statusText; ?>
                    </span>
                    <?php else: ?>
                    <span style="opacity: 0.6; font-size: 0.85rem;"><?php echo $statusText; ?></span>
                    <?php endif; ?>
                </div>
                <div class="table-card-body">
                    <div class="table-card-row">
                        <span class="table-card-label">Classes Attended</span>
                        <span class="table-card-value"><?php echo $student['attended']; ?> / <?php echo $student['total_classes']; ?></span>
                    </div>
                    <div class="table-card-row">
                        <span class="table-card-label">Attendance</span>
                        <span class="table-card-value" style="color: <?php echo $percentColor; ?>; font-weight: bold; font-size: 1.1rem;">
                            <?php echo $student['percentage']; ?>%
                        </span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php else: ?>
        <div class="alert warning">No students enrolled in this course.</div>
    <?php endif; ?>

    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
