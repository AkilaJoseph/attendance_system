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
$selected_course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_slot' || $_POST['action'] === 'edit_slot') {
            $course_id = intval($_POST['course_id']);
            $day_of_week = $_POST['day_of_week'];
            $start_time = $_POST['start_time'];
            $end_time = $_POST['end_time'];
            $room = sanitize($_POST['room']);
            $schedule_type = $_POST['schedule_type'];
            $schedule_id = isset($_POST['schedule_id']) ? intval($_POST['schedule_id']) : null;

            // Validate that lecturer owns this course
            $check_query = "SELECT course_id FROM courses WHERE course_id = ? AND lecturer_id = ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("ii", $course_id, $lecturer_id);
            $check_stmt->execute();

            if ($check_stmt->get_result()->num_rows > 0) {
                if (saveScheduleSlot($conn, $course_id, $day_of_week, $start_time, $end_time, $room, $schedule_type, $schedule_id)) {
                    $_SESSION['flash_message'] = $schedule_id ? "Schedule updated successfully!" : "Schedule slot added successfully!";
                    $_SESSION['flash_type'] = 'success';
                } else {
                    $_SESSION['flash_message'] = "Failed to save schedule. The time slot may already exist.";
                    $_SESSION['flash_type'] = 'error';
                }
            } else {
                $_SESSION['flash_message'] = "You don't have permission to modify this course.";
                $_SESSION['flash_type'] = 'error';
            }
        } elseif ($_POST['action'] === 'delete_slot') {
            $schedule_id = intval($_POST['schedule_id']);

            // Verify ownership
            $verify_query = "SELECT cs.schedule_id FROM class_schedule cs
                            JOIN courses c ON cs.course_id = c.course_id
                            WHERE cs.schedule_id = ? AND c.lecturer_id = ?";
            $verify_stmt = $conn->prepare($verify_query);
            $verify_stmt->bind_param("ii", $schedule_id, $lecturer_id);
            $verify_stmt->execute();

            if ($verify_stmt->get_result()->num_rows > 0) {
                if (deleteScheduleSlot($conn, $schedule_id)) {
                    $_SESSION['flash_message'] = "Schedule slot deleted successfully!";
                    $_SESSION['flash_type'] = 'success';
                } else {
                    $_SESSION['flash_message'] = "Failed to delete schedule slot.";
                    $_SESSION['flash_type'] = 'error';
                }
            } else {
                $_SESSION['flash_message'] = "You don't have permission to delete this slot.";
                $_SESSION['flash_type'] = 'error';
            }
        }

        $redirect_url = "schedule.php";
        if ($selected_course_id) {
            $redirect_url .= "?course_id=" . $selected_course_id;
        }
        header("Location: " . $redirect_url);
        exit();
    }
}

// Get lecturer's courses
$courses_result = getLecturerCourses($conn, $lecturer_id);
$courses = [];
while ($row = $courses_result->fetch_assoc()) {
    $courses[] = $row;
}

// Get schedule for selected course or all courses
$schedule_data = [];
if ($selected_course_id) {
    $schedule_result = getCourseSchedule($conn, $selected_course_id);
    while ($row = $schedule_result->fetch_assoc()) {
        $schedule_data[] = $row;
    }
} else {
    // Get all schedules for all courses
    $schedule_result = getLecturerWeeklySchedule($conn, $lecturer_id);
    while ($row = $schedule_result->fetch_assoc()) {
        $schedule_data[] = $row;
    }
}

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
$schedule_types = ['lecture' => 'Lecture', 'tutorial' => 'Tutorial', 'lab' => 'Lab', 'other' => 'Other'];
?>

<div class="container">
    <div class="page-header">
        <h2>Class Schedule</h2>
        <p>Manage your recurring weekly class schedule</p>
    </div>

    <?php displayFlashMessage(); ?>

    <!-- Course Filter -->
    <div class="filter-section">
        <label for="course_filter">Select Course:</label>
        <select id="course_filter" onchange="filterByCourse(this.value)">
            <option value="">All Courses</option>
            <?php foreach ($courses as $course): ?>
            <option value="<?php echo $course['course_id']; ?>"
                    <?php echo $selected_course_id == $course['course_id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Add Schedule Button -->
    <div class="action-bar">
        <button type="button" class="btn btn-primary" onclick="openAddModal()">
            + Add Class Slot
        </button>
    </div>

    <!-- Schedule Grid View -->
    <div class="schedule-grid-container">
        <table class="schedule-table">
            <thead>
                <tr>
                    <th class="time-column">Time</th>
                    <?php foreach ($days as $day): ?>
                    <th><?php echo $day; ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php
                // Generate time slots from 7 AM to 9 PM
                for ($hour = 7; $hour <= 21; $hour++):
                    $time_slot = sprintf('%02d:00', $hour);
                    $display_time = date('g:i A', strtotime($time_slot));
                ?>
                <tr>
                    <td class="time-cell"><?php echo $display_time; ?></td>
                    <?php foreach ($days as $day):
                        // Find classes in this slot
                        $slot_classes = array_filter($schedule_data, function($class) use ($day, $hour) {
                            $start_hour = (int)date('H', strtotime($class['start_time']));
                            return $class['day_of_week'] === $day && $start_hour === $hour;
                        });
                    ?>
                    <td class="schedule-cell <?php echo !empty($slot_classes) ? 'has-class' : ''; ?>">
                        <?php foreach ($slot_classes as $class): ?>
                        <div class="schedule-item <?php echo getScheduleTypeClass($class['schedule_type']); ?>"
                             onclick="openEditModal(<?php echo htmlspecialchars(json_encode($class)); ?>)">
                            <span class="schedule-course"><?php echo htmlspecialchars($class['course_code']); ?></span>
                            <span class="schedule-type"><?php echo getScheduleTypeName($class['schedule_type']); ?></span>
                            <span class="schedule-time">
                                <?php echo date('g:i', strtotime($class['start_time'])); ?> -
                                <?php echo date('g:i A', strtotime($class['end_time'])); ?>
                            </span>
                            <?php if ($class['room']): ?>
                            <span class="schedule-room"><?php echo htmlspecialchars($class['room']); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endfor; ?>
            </tbody>
        </table>
    </div>

    <!-- Schedule List View (for mobile or alternative) -->
    <div class="schedule-list-view">
        <h3>Schedule List</h3>
        <?php if (empty($schedule_data)): ?>
        <div class="alert info">No classes scheduled yet. Click "Add Class Slot" to create your first schedule.</div>
        <?php else: ?>
        <div class="schedule-list">
            <?php foreach ($schedule_data as $class): ?>
            <div class="schedule-list-item">
                <div class="schedule-list-info">
                    <span class="schedule-list-course"><?php echo htmlspecialchars($class['course_code']); ?></span>
                    <span class="schedule-list-day"><?php echo $class['day_of_week']; ?></span>
                    <span class="schedule-list-time">
                        <?php echo date('g:i A', strtotime($class['start_time'])); ?> -
                        <?php echo date('g:i A', strtotime($class['end_time'])); ?>
                    </span>
                    <span class="schedule-list-type badge <?php echo getScheduleTypeClass($class['schedule_type']); ?>">
                        <?php echo getScheduleTypeName($class['schedule_type']); ?>
                    </span>
                    <?php if ($class['room']): ?>
                    <span class="schedule-list-room"><?php echo htmlspecialchars($class['room']); ?></span>
                    <?php endif; ?>
                </div>
                <div class="schedule-list-actions">
                    <button type="button" class="btn btn-sm btn-secondary"
                            onclick="openEditModal(<?php echo htmlspecialchars(json_encode($class)); ?>)">
                        Edit
                    </button>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this schedule slot?');">
                        <input type="hidden" name="action" value="delete_slot">
                        <input type="hidden" name="schedule_id" value="<?php echo $class['schedule_id']; ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add/Edit Modal -->
<div id="scheduleModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Add Class Slot</h3>
            <button type="button" class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST" id="scheduleForm">
            <input type="hidden" name="action" id="formAction" value="add_slot">
            <input type="hidden" name="schedule_id" id="scheduleId" value="">

            <div class="form-group">
                <label for="modal_course">Course *</label>
                <select name="course_id" id="modal_course" required>
                    <option value="">Select Course</option>
                    <?php foreach ($courses as $course): ?>
                    <option value="<?php echo $course['course_id']; ?>">
                        <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="modal_day">Day of Week *</label>
                <select name="day_of_week" id="modal_day" required>
                    <?php foreach ($days as $day): ?>
                    <option value="<?php echo $day; ?>"><?php echo $day; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="modal_start">Start Time *</label>
                    <input type="time" name="start_time" id="modal_start" required>
                </div>
                <div class="form-group">
                    <label for="modal_end">End Time *</label>
                    <input type="time" name="end_time" id="modal_end" required>
                </div>
            </div>

            <div class="form-group">
                <label for="modal_type">Class Type *</label>
                <select name="schedule_type" id="modal_type" required>
                    <?php foreach ($schedule_types as $value => $label): ?>
                    <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="modal_room">Room/Location</label>
                <input type="text" name="room" id="modal_room" placeholder="e.g., Room 201, Lab A">
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" id="submitBtn">Add Slot</button>
            </div>
        </form>
    </div>
</div>

<script>
function filterByCourse(courseId) {
    if (courseId) {
        window.location.href = 'schedule.php?course_id=' + courseId;
    } else {
        window.location.href = 'schedule.php';
    }
}

function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add Class Slot';
    document.getElementById('formAction').value = 'add_slot';
    document.getElementById('scheduleId').value = '';
    document.getElementById('submitBtn').textContent = 'Add Slot';
    document.getElementById('scheduleForm').reset();

    // Pre-select course if filtered
    const courseFilter = document.getElementById('course_filter').value;
    if (courseFilter) {
        document.getElementById('modal_course').value = courseFilter;
    }

    document.getElementById('scheduleModal').classList.add('show');
}

function openEditModal(data) {
    document.getElementById('modalTitle').textContent = 'Edit Class Slot';
    document.getElementById('formAction').value = 'edit_slot';
    document.getElementById('scheduleId').value = data.schedule_id;
    document.getElementById('submitBtn').textContent = 'Update Slot';

    document.getElementById('modal_course').value = data.course_id;
    document.getElementById('modal_day').value = data.day_of_week;
    document.getElementById('modal_start').value = data.start_time;
    document.getElementById('modal_end').value = data.end_time;
    document.getElementById('modal_type').value = data.schedule_type;
    document.getElementById('modal_room').value = data.room || '';

    document.getElementById('scheduleModal').classList.add('show');
}

function closeModal() {
    document.getElementById('scheduleModal').classList.remove('show');
}

// Close modal on outside click
document.getElementById('scheduleModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>

<?php include '../includes/footer.php'; ?>
