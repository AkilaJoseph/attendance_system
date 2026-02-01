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

// Get course ID from URL
if (!isset($_GET['course_id'])) {
    header("Location: dashboard.php");
    exit();
}

$course_id = intval($_GET['course_id']);

// Get course details
$course_query = "SELECT course_name, course_code FROM courses WHERE course_id = $course_id AND lecturer_id = " . $_SESSION['user_id'];
$course_result = $conn->query($course_query);

if ($course_result->num_rows == 0) {
    header("Location: dashboard.php");
    exit();
}

$course = $course_result->fetch_assoc();

// Get enrolled students
$students_result = getEnrolledStudents($conn, $course_id);
?>

<div class="container">
    <div class="page-header">
        <h2>Take Attendance</h2>
        <p><?php echo htmlspecialchars($course['course_code']); ?> - <?php echo htmlspecialchars($course['course_name']); ?></p>
    </div>

    <?php displayFlashMessage(); ?>

    <?php if($students_result->num_rows > 0): ?>
    <form action="../actions/mark_attendance.php" method="POST" id="attendanceForm">
        <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">

        <div class="form-group">
            <label for="date">Date</label>
            <input type="date" name="date" id="date" value="<?php echo date('Y-m-d'); ?>" required>
        </div>

        <div style="margin-bottom: 20px;">
            <button type="button" id="selectAllPresent" class="btn btn-success">Mark All Present</button>
            <button type="button" id="selectAllAbsent" class="btn btn-danger">Mark All Absent</button>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Email</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($student = $students_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($student['name']); ?></td>
                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                        <td>
                            <div class="radio-group">
                                <label>
                                    <input type="radio" name="status[<?php echo $student['user_id']; ?>]" value="present" checked>
                                    Present
                                </label>
                                <label>
                                    <input type="radio" name="status[<?php echo $student['user_id']; ?>]" value="absent">
                                    Absent
                                </label>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div style="margin-top: 20px;">
            <button type="submit" class="btn btn-primary">Save Attendance</button>
            <a href="dashboard.php" class="btn">Cancel</a>
        </div>
    </form>
    <?php else: ?>
        <div class="alert warning">
            No students are enrolled in this course yet.
        </div>
        <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
