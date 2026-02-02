<?php
session_start();
include '../config/db_connect.php';
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
// Include header after authentication and any potential redirects
include '../includes/header.php';
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

        <div class="attendance-controls">
            <div class="form-group date-picker">
                <label for="date">Date</label>
                <input type="date" name="date" id="date" value="<?php echo date('Y-m-d'); ?>" required>
            </div>

            <div class="bulk-actions">
                <button type="button" id="selectAllPresent" class="btn btn-success">
                    <span class="btn-icon-text">✓</span> Mark All Present
                </button>
                <button type="button" id="selectAllAbsent" class="btn btn-danger">
                    <span class="btn-icon-text">✗</span> Mark All Absent
                </button>
            </div>
        </div>

        <div class="student-count">
            <span><?php echo $students_result->num_rows; ?> students enrolled</span>
        </div>

        <!-- Desktop Table View -->
        <div class="table-container desktop-view">
            <table class="attendance-table">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Email</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($student = $students_result->fetch_assoc()): ?>
                    <tr class="student-row" data-student-id="<?php echo $student['user_id']; ?>">
                        <td class="student-name"><?php echo htmlspecialchars($student['name']); ?></td>
                        <td class="student-email"><?php echo htmlspecialchars($student['email']); ?></td>
                        <td>
                            <div class="status-toggle">
                                <label class="status-option present">
                                    <input type="radio" name="status[<?php echo $student['user_id']; ?>]" value="present" checked>
                                    <span class="status-label">Present</span>
                                </label>
                                <label class="status-option absent">
                                    <input type="radio" name="status[<?php echo $student['user_id']; ?>]" value="absent">
                                    <span class="status-label">Absent</span>
                                </label>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Mobile Card View -->
        <?php
        $students_result->data_seek(0); // Reset result pointer
        ?>
        <div class="mobile-view">
            <div class="student-cards">
                <?php while($student = $students_result->fetch_assoc()): ?>
                <div class="student-card" data-student-id="<?php echo $student['user_id']; ?>">
                    <div class="student-info">
                        <div class="student-avatar">
                            <?php echo strtoupper(substr($student['name'], 0, 1)); ?>
                        </div>
                        <div class="student-details">
                            <span class="student-name"><?php echo htmlspecialchars($student['name']); ?></span>
                            <span class="student-email"><?php echo htmlspecialchars($student['email']); ?></span>
                        </div>
                    </div>
                    <div class="status-buttons">
                        <label class="status-btn present active">
                            <input type="radio" name="status[<?php echo $student['user_id']; ?>]" value="present" checked>
                            <span>✓ Present</span>
                        </label>
                        <label class="status-btn absent">
                            <input type="radio" name="status[<?php echo $student['user_id']; ?>]" value="absent">
                            <span>✗ Absent</span>
                        </label>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>

        <div class="form-actions attendance-submit">
            <button type="submit" class="btn btn-primary btn-lg">
                <span class="btn-icon-text">💾</span> Save Attendance
            </button>
            <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">👥</div>
            <h3>No Students Enrolled</h3>
            <p>There are no students enrolled in this course yet.</p>
            <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Status button toggle for mobile cards
    document.querySelectorAll('.status-btn input').forEach(function(radio) {
        radio.addEventListener('change', function() {
            const card = this.closest('.student-card');
            card.querySelectorAll('.status-btn').forEach(btn => btn.classList.remove('active'));
            this.closest('.status-btn').classList.add('active');
        });
    });

    // Status toggle for desktop view
    document.querySelectorAll('.status-option input').forEach(function(radio) {
        radio.addEventListener('change', function() {
            const toggle = this.closest('.status-toggle');
            toggle.querySelectorAll('.status-option').forEach(opt => opt.classList.remove('active'));
            this.closest('.status-option').classList.add('active');
        });
        // Set initial active state
        if (radio.checked) {
            radio.closest('.status-option').classList.add('active');
        }
    });

    // Bulk actions
    document.getElementById('selectAllPresent').addEventListener('click', function() {
        document.querySelectorAll('input[value="present"]').forEach(function(radio) {
            radio.checked = true;
            radio.dispatchEvent(new Event('change'));
        });
    });

    document.getElementById('selectAllAbsent').addEventListener('click', function() {
        document.querySelectorAll('input[value="absent"]').forEach(function(radio) {
            radio.checked = true;
            radio.dispatchEvent(new Event('change'));
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>
