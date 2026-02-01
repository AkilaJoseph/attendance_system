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

// Get lecturer's courses
$courses_result = getLecturerCourses($conn, $lecturer_id);
$courses = [];
while ($row = $courses_result->fetch_assoc()) {
    $courses[] = $row;
}

// Get selected course
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

// Get events for selected course
$events = [];
if ($selected_course > 0) {
    $query = "SELECT * FROM events WHERE course_id = ? AND is_course_event = TRUE ORDER BY event_date DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $selected_course);
    $stmt->execute();
    $events_result = $stmt->get_result();
    while ($row = $events_result->fetch_assoc()) {
        $events[] = $row;
    }
}
?>

<div class="container">
    <div class="page-header">
        <h2>Manage Course Events</h2>
        <p>Create exams, assignments, and other events for your students</p>
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

    <!-- Add Event Form -->
    <div class="card" style="margin-bottom: 30px;">
        <h3>Add Course Event</h3>
        <form action="../actions/save_event.php" method="POST" style="margin-top: 15px;">
            <input type="hidden" name="redirect" value="lecturer/events.php?course_id=<?php echo $selected_course; ?>">
            <input type="hidden" name="course_id" value="<?php echo $selected_course; ?>">
            <input type="hidden" name="is_course_event" value="1">

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div class="form-group">
                    <label for="title">Event Title</label>
                    <input type="text" name="title" id="title" placeholder="e.g., Mid-term Exam" required>
                </div>
                <div class="form-group">
                    <label for="event_type">Type</label>
                    <select name="event_type" id="event_type" required>
                        <option value="ue">Unit Exam (UE)</option>
                        <option value="ca">Continuous Assessment (CA)</option>
                        <option value="assignment">Assignment</option>
                        <option value="class">Class</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="event_date">Date</label>
                    <input type="date" name="event_date" id="event_date" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label for="event_time">Time</label>
                    <input type="time" name="event_time" id="event_time">
                </div>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea name="description" id="description" rows="2" placeholder="Additional details..."></textarea>
            </div>

            <div style="display: flex; align-items: center; gap: 20px; margin-top: 15px;">
                <label class="checkbox-label">
                    <input type="checkbox" name="add_reminder" checked>
                    Send reminder to students
                </label>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <input type="number" name="remind_before" value="3" min="1" max="30" style="width: 70px;">
                    <select name="remind_unit" style="width: 100px;">
                        <option value="days">Days</option>
                        <option value="hours">Hours</option>
                    </select>
                    <span>before</span>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="margin-top: 15px;">Add Event</button>
        </form>
    </div>

    <!-- Events List -->
    <h3>Course Events</h3>
    <?php if (count($events) > 0): ?>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($events as $event): ?>
                <tr>
                    <td><?php echo date('M j, Y', strtotime($event['event_date'])); ?></td>
                    <td><?php echo $event['event_time'] ? date('g:i A', strtotime($event['event_time'])) : '-'; ?></td>
                    <td><?php echo htmlspecialchars($event['title']); ?></td>
                    <td>
                        <span class="event-type-badge <?php echo getEventTypeClass($event['event_type']); ?>">
                            <?php echo getEventTypeName($event['event_type']); ?>
                        </span>
                    </td>
                    <td>
                        <form action="../actions/delete_event.php" method="POST" style="display: inline;" onsubmit="return confirm('Delete this event? Students will no longer see it.');">
                            <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                            <input type="hidden" name="redirect" value="lecturer/events.php?course_id=<?php echo $selected_course; ?>">
                            <button type="submit" class="btn btn-danger" style="padding: 6px 12px; font-size: 0.85rem;">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <div class="alert warning">No events created for this course yet.</div>
    <?php endif; ?>

    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
