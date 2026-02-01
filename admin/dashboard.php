<?php
session_start();
include '../config/db_connect.php';
include '../includes/header.php';
include '../includes/functions.php';

// Security: Check if user is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

// Get counts for stats
$users_count = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$students_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'")->fetch_assoc()['count'];
$lecturers_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'lecturer'")->fetch_assoc()['count'];
$courses_count = $conn->query("SELECT COUNT(*) as count FROM courses")->fetch_assoc()['count'];

// Get all users
$users_result = $conn->query("SELECT user_id, name, email, role FROM users ORDER BY role, name");

// Get all courses
$courses_result = $conn->query("SELECT c.course_id, c.course_name, c.course_code, u.name as lecturer_name
                                 FROM courses c
                                 LEFT JOIN users u ON c.lecturer_id = u.user_id
                                 ORDER BY c.course_name");
?>

<div class="container">
    <div class="page-header">
        <h2>Admin Dashboard</h2>
        <p>Manage users and courses</p>
    </div>

    <?php displayFlashMessage(); ?>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?php echo $users_count; ?></div>
            <div class="stat-label">Total Users</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $students_count; ?></div>
            <div class="stat-label">Students</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $lecturers_count; ?></div>
            <div class="stat-label">Lecturers</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $courses_count; ?></div>
            <div class="stat-label">Courses</div>
        </div>
    </div>

    <!-- Users Table -->
    <h3>All Users</h3>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                </tr>
            </thead>
            <tbody>
                <?php while($user = $users_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $user['user_id']; ?></td>
                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><?php echo ucfirst($user['role']); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Courses Table -->
    <h3 style="margin-top: 40px;">All Courses</h3>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Course Code</th>
                    <th>Course Name</th>
                    <th>Lecturer</th>
                </tr>
            </thead>
            <tbody>
                <?php while($course = $courses_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $course['course_id']; ?></td>
                    <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                    <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                    <td><?php echo htmlspecialchars($course['lecturer_name'] ?? 'Not Assigned'); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
