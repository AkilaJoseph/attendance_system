<?php
session_start();
include '../config/db_connect.php';
include '../includes/functions.php';

// Security: Check if user is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

include '../includes/header.php';

$threshold = isset($_GET['threshold']) ? intval($_GET['threshold']) : 75;

// Get all students with low attendance
$low_attendance_query = "
    SELECT
        u.user_id,
        u.name as student_name,
        u.email,
        c.course_id,
        c.course_name,
        c.course_code,
        COUNT(DISTINCT a.date) as total_classes,
        SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as attended,
        ROUND((SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) / COUNT(DISTINCT a.date)) * 100) as percentage
    FROM users u
    JOIN enrollments e ON u.user_id = e.student_id
    JOIN courses c ON e.course_id = c.course_id
    LEFT JOIN attendance a ON u.user_id = a.student_id AND c.course_id = a.course_id
    WHERE u.role = 'student'
    GROUP BY u.user_id, c.course_id
    HAVING total_classes > 0 AND percentage < ?
    ORDER BY percentage ASC, u.name
";

$stmt = $conn->prepare($low_attendance_query);
$stmt->bind_param("i", $threshold);
$stmt->execute();
$low_attendance = $stmt->get_result();

// Get overall statistics
$total_students = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE role='student'")->fetch_assoc()['cnt'];
$total_courses = $conn->query("SELECT COUNT(*) as cnt FROM courses")->fetch_assoc()['cnt'];

// Count students at risk (below threshold in any course)
$at_risk_query = "
    SELECT COUNT(DISTINCT u.user_id) as cnt
    FROM users u
    JOIN enrollments e ON u.user_id = e.student_id
    JOIN courses c ON e.course_id = c.course_id
    LEFT JOIN attendance a ON u.user_id = a.student_id AND c.course_id = a.course_id
    WHERE u.role = 'student'
    GROUP BY u.user_id, c.course_id
    HAVING COUNT(DISTINCT a.date) > 0 AND
           ROUND((SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) / COUNT(DISTINCT a.date)) * 100) < $threshold
";
$at_risk_result = $conn->query($at_risk_query);
$at_risk_count = $at_risk_result ? $at_risk_result->num_rows : 0;
?>

<div class="container">
    <div class="page-header">
        <h2>Attendance Reports</h2>
        <p>Monitor student attendance and identify at-risk students</p>
    </div>

    <?php displayFlashMessage(); ?>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?php echo $total_students; ?></div>
            <div class="stat-label">Total Students</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $total_courses; ?></div>
            <div class="stat-label">Total Courses</div>
        </div>
        <div class="stat-card" style="border: 2px solid var(--danger-color);">
            <div class="stat-number" style="color: var(--danger-color);"><?php echo $at_risk_count; ?></div>
            <div class="stat-label">Students At Risk</div>
        </div>
    </div>

    <!-- Filter -->
    <div class="card" style="margin-bottom: 30px;">
        <h3>Filter Settings</h3>
        <form action="" method="GET" style="margin-top: 15px; display: flex; flex-wrap: wrap; align-items: end; gap: 15px;">
            <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 150px;">
                <label for="threshold">Attendance Threshold (%)</label>
                <input type="number" name="threshold" id="threshold" value="<?php echo $threshold; ?>" min="1" max="100">
            </div>
            <button type="submit" class="btn btn-primary">Apply Filter</button>
        </form>
    </div>

    <!-- Low Attendance Alerts -->
    <div class="alert danger" style="margin-bottom: 20px;">
        <strong>Attendance Alert:</strong> Students below <?php echo $threshold; ?>% attendance require immediate attention.
    </div>

    <?php if($low_attendance->num_rows > 0): ?>
    <h3>Students Below <?php echo $threshold; ?>% Attendance</h3>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Email</th>
                    <th>Course</th>
                    <th>Attended</th>
                    <th>Percentage</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $reports_array = [];
                while($row = $low_attendance->fetch_assoc()) {
                    $reports_array[] = $row;
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><?php echo htmlspecialchars($row['course_code'] . ' - ' . $row['course_name']); ?></td>
                    <td><?php echo $row['attended']; ?> / <?php echo $row['total_classes']; ?></td>
                    <td>
                        <strong style="color: <?php echo $row['percentage'] < 50 ? 'var(--danger-color)' : 'var(--warning-color)'; ?>">
                            <?php echo $row['percentage']; ?>%
                        </strong>
                    </td>
                    <td>
                        <?php if($row['percentage'] < 50): ?>
                            <span class="alert danger" style="display: inline-block; padding: 4px 8px; margin: 0;">Critical</span>
                        <?php else: ?>
                            <span class="alert warning" style="display: inline-block; padding: 4px 8px; margin: 0;">Warning</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>

        <!-- Mobile Card View -->
        <div class="table-responsive-cards">
            <?php foreach($reports_array as $row):
                $statusClass = $row['percentage'] < 50 ? 'danger' : 'warning';
                $statusText = $row['percentage'] < 50 ? 'Critical' : 'Warning';
                $percentColor = $row['percentage'] < 50 ? 'var(--danger-color)' : 'var(--warning-color)';
            ?>
            <div class="table-card">
                <div class="table-card-header">
                    <div>
                        <div class="table-card-title"><?php echo htmlspecialchars($row['student_name']); ?></div>
                        <div class="table-card-subtitle"><?php echo htmlspecialchars($row['email']); ?></div>
                    </div>
                    <span class="alert <?php echo $statusClass; ?>" style="display: inline-block; padding: 4px 10px; margin: 0; font-size: 0.8rem;">
                        <?php echo $statusText; ?>
                    </span>
                </div>
                <div class="table-card-body">
                    <div class="table-card-row">
                        <span class="table-card-label">Course</span>
                        <span class="table-card-value"><?php echo htmlspecialchars($row['course_code']); ?></span>
                    </div>
                    <div class="table-card-row">
                        <span class="table-card-label">Attended</span>
                        <span class="table-card-value"><?php echo $row['attended']; ?> / <?php echo $row['total_classes']; ?></span>
                    </div>
                    <div class="table-card-row">
                        <span class="table-card-label">Attendance</span>
                        <span class="table-card-value" style="color: <?php echo $percentColor; ?>; font-weight: bold; font-size: 1.1rem;">
                            <?php echo $row['percentage']; ?>%
                        </span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php else: ?>
        <div class="alert good">
            All students are meeting the <?php echo $threshold; ?>% attendance threshold.
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
