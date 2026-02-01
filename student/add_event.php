<?php
session_start();
include '../config/db_connect.php';
include '../includes/header.php';
include '../includes/functions.php';

// Security: Check if user is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: ../index.php");
    exit();
}

$student_id = $_SESSION['user_id'];

// Get student's courses for optional linking
$courses = getStudentCourses($conn, $student_id);
?>

<div class="container">
    <div class="page-header">
        <h2>Add New Event</h2>
        <p>Create a personal event with optional reminder</p>
    </div>

    <?php displayFlashMessage(); ?>

    <div class="card form-card">
        <form action="../actions/save_event.php" method="POST" id="eventForm">
            <input type="hidden" name="redirect" value="student/timetable.php">

            <div class="form-row">
                <div class="form-group">
                    <label for="title">Event Title *</label>
                    <input type="text" name="title" id="title" placeholder="e.g., Database Systems Exam" required>
                </div>
            </div>

            <div class="form-row two-cols">
                <div class="form-group">
                    <label for="event_type">Event Type *</label>
                    <select name="event_type" id="event_type" required>
                        <option value="class">Class</option>
                        <option value="ue">Unit Exam (UE)</option>
                        <option value="ca">Continuous Assessment (CA)</option>
                        <option value="assignment">Assignment</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="course_id">Related Course (Optional)</label>
                    <select name="course_id" id="course_id">
                        <option value="">-- None --</option>
                        <?php while ($course = $courses->fetch_assoc()): ?>
                            <option value="<?php echo $course['course_id']; ?>">
                                <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <div class="form-row two-cols">
                <div class="form-group">
                    <label for="event_date">Date *</label>
                    <input type="date" name="event_date" id="event_date" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label for="event_time">Time (Optional)</label>
                    <input type="time" name="event_time" id="event_time">
                </div>
            </div>

            <div class="form-group">
                <label for="description">Description (Optional)</label>
                <textarea name="description" id="description" rows="3" placeholder="Add any notes or details..."></textarea>
            </div>

            <div class="reminder-section">
                <h3>Reminder Settings</h3>
                <p class="hint">Get notified before your event</p>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="add_reminder" id="add_reminder" checked>
                        Enable reminder for this event
                    </label>
                </div>

                <div class="reminder-options" id="reminderOptions">
                    <div class="form-row two-cols">
                        <div class="form-group">
                            <label for="remind_before">Remind me</label>
                            <input type="number" name="remind_before" id="remind_before" value="1" min="1" max="365">
                        </div>
                        <div class="form-group">
                            <label for="remind_unit">Before event</label>
                            <select name="remind_unit" id="remind_unit">
                                <option value="days">Days</option>
                                <option value="hours">Hours</option>
                            </select>
                        </div>
                    </div>

                    <div class="quick-options">
                        <button type="button" class="quick-btn" onclick="setReminder(1, 'hours')">1 hour</button>
                        <button type="button" class="quick-btn" onclick="setReminder(3, 'hours')">3 hours</button>
                        <button type="button" class="quick-btn" onclick="setReminder(1, 'days')">1 day</button>
                        <button type="button" class="quick-btn" onclick="setReminder(3, 'days')">3 days</button>
                        <button type="button" class="quick-btn" onclick="setReminder(7, 'days')">1 week</button>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Event</button>
                <a href="timetable.php" class="btn">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('add_reminder').addEventListener('change', function() {
    document.getElementById('reminderOptions').style.display = this.checked ? 'block' : 'none';
});

function setReminder(value, unit) {
    document.getElementById('remind_before').value = value;
    document.getElementById('remind_unit').value = unit;

    // Highlight selected quick button
    document.querySelectorAll('.quick-btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
}

// Set default date to today
document.getElementById('event_date').value = new Date().toISOString().split('T')[0];
</script>

<?php include '../includes/footer.php'; ?>
