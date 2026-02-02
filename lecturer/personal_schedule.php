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

// Load lecturer's courses for dropdown + validation
$lecturer_courses_result = getLecturerCourses($conn, $lecturer_id);
$lecturer_courses = [];
while ($lc = $lecturer_courses_result->fetch_assoc()) {
    $lecturer_courses[] = $lc;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_slot' || $_POST['action'] === 'edit_slot') {
            $title = sanitize($_POST['title']);
            $schedule_type = $_POST['schedule_type'];
            $day_of_week = $_POST['day_of_week'];
            $start_time = $_POST['start_time'];
            $end_time = $_POST['end_time'];
            $location = sanitize($_POST['location'] ?? '');
            $description = sanitize($_POST['description'] ?? '');
            $course_id = isset($_POST['course_id']) && !empty($_POST['course_id']) ? intval($_POST['course_id']) : null;
            $schedule_id = isset($_POST['schedule_id']) && !empty($_POST['schedule_id']) ? intval($_POST['schedule_id']) : null;

            // Validate lecturer can only link to their own courses
            if ($course_id !== null) {
                $validL = false;
                foreach ($lecturer_courses as $lc) {
                    if (intval($lc['course_id']) === $course_id) { $validL = true; break; }
                }
                if (!$validL) {
                    $_SESSION['flash_message'] = "Invalid course selection. You can only link to courses you teach.";
                    $_SESSION['flash_type'] = 'error';
                    header("Location: personal_schedule.php");
                    exit();
                }
            }

            if (savePersonalScheduleSlot($conn, $lecturer_id, $title, $schedule_type, $day_of_week, $start_time, $end_time, $location, $description, $course_id, $schedule_id)) {
                $_SESSION['flash_message'] = $schedule_id ? "Schedule updated successfully!" : "Schedule slot added successfully!";
                $_SESSION['flash_type'] = 'success';
            } else {
                $_SESSION['flash_message'] = "Failed to save schedule. The time slot may already exist.";
                $_SESSION['flash_type'] = 'error';
            }
        } elseif ($_POST['action'] === 'delete_slot') {
            $schedule_id = intval($_POST['schedule_id']);

            if (deletePersonalScheduleSlot($conn, $schedule_id, $lecturer_id)) {
                $_SESSION['flash_message'] = "Schedule slot deleted successfully!";
                $_SESSION['flash_type'] = 'success';
            } else {
                $_SESSION['flash_message'] = "Failed to delete schedule slot.";
                $_SESSION['flash_type'] = 'error';
            }
        }

        header("Location: personal_schedule.php");
        exit();
    }
}

// Get personal schedule
$schedule_result = getPersonalSchedule($conn, $lecturer_id);
$schedule_data = [];
while ($row = $schedule_result->fetch_assoc()) {
    $schedule_data[] = $row;
}

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
$schedule_types = [
    'class' => 'Class',
    'ue' => 'Unit Exam',
    'ca' => 'CA',
    'assignment' => 'Assignment',
    'study' => 'Preparation',
    'personal' => 'Personal',
    'other' => 'Other'
];
?>

<div class="container">
    <div class="page-header">
        <h2>My Personal Schedule</h2>
        <p>Create and manage your personal weekly timetable (in addition to course schedules)</p>
    </div>

    <?php displayFlashMessage(); ?>

    <!-- Add Schedule Button -->
    <div class="action-bar">
        <button type="button" class="btn btn-primary" onclick="openAddModal()">
            + Add Schedule Slot
        </button>
        <a href="schedule.php" class="btn btn-secondary">Course Schedules</a>
    </div>

    <!-- Schedule List View -->
    <div class="schedule-list-view">
        <h3>My Schedule Items</h3>
        <?php if (empty($schedule_data)): ?>
        <div class="alert info">No personal schedule items yet. Click "Add Schedule Slot" to create your timetable.</div>
        <?php else: ?>
        <div class="schedule-list">
            <?php foreach ($schedule_data as $item): ?>
            <div class="schedule-list-item">
                <div class="schedule-list-info">
                    <?php if (!empty($item['color'])): ?>
                    <span class="course-color-dot" style="background-color: <?php echo htmlspecialchars($item['color']); ?>;"></span>
                    <?php endif; ?>
                    <span class="schedule-list-title"><?php echo htmlspecialchars($item['title']); ?></span>
                    <?php if (!empty($item['course_code'])): ?>
                    <span class="schedule-list-course" title="<?php echo htmlspecialchars($item['course_name'] ?? $item['course_code']); ?>">(<?php echo htmlspecialchars($item['course_code']); ?>)</span>
                    <?php endif; ?>
                    <span class="schedule-list-day"><?php echo $item['day_of_week']; ?></span>
                    <span class="schedule-list-time">
                        <?php echo date('g:i A', strtotime($item['start_time'])); ?> -
                        <?php echo date('g:i A', strtotime($item['end_time'])); ?>
                    </span>
                    <span class="schedule-list-type badge <?php echo getPersonalScheduleTypeClass($item['schedule_type']); ?>">
                        <?php echo getPersonalScheduleTypeName($item['schedule_type']); ?>
                    </span>
                    <?php if ($item['location']): ?>
                    <span class="schedule-list-room"><?php echo htmlspecialchars($item['location']); ?></span>
                    <?php endif; ?>
                </div>
                <div class="schedule-list-actions">
                    <button type="button" class="btn btn-sm btn-secondary"
                            onclick="openEditModal(<?php echo htmlspecialchars(json_encode($item)); ?>)">
                        Edit
                    </button>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this schedule slot?');">
                        <input type="hidden" name="action" value="delete_slot">
                        <input type="hidden" name="schedule_id" value="<?php echo $item['schedule_id']; ?>">
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
            <h3 id="modalTitle">Add Schedule Slot</h3>
            <button type="button" class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST" id="scheduleForm">
            <input type="hidden" name="action" id="formAction" value="add_slot">
            <input type="hidden" name="schedule_id" id="scheduleId" value="">

            <div class="form-group">
                <label for="modal_title">Title *</label>
                <input type="text" name="title" id="modal_title" required placeholder="e.g., Office Hours, Meeting">
            </div>

            <div class="form-group">
                <label for="modal_type">Type *</label>
                <select name="schedule_type" id="modal_type" required>
                    <?php foreach ($schedule_types as $value => $label): ?>
                    <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="modal_course">Link to Course (optional)</label>
                <select name="course_id" id="modal_course">
                    <option value="">-- None --</option>
                    <?php foreach ($lecturer_courses as $c): ?>
                    <option value="<?php echo $c['course_id']; ?>"><?php echo htmlspecialchars($c['course_code'] . ' - ' . $c['course_name']); ?></option>
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
                <label for="modal_location">Location</label>
                <input type="text" name="location" id="modal_location" placeholder="e.g., Office, Room 201">
            </div>

            <div class="form-group">
                <label for="modal_description">Description</label>
                <textarea name="description" id="modal_description" rows="3" placeholder="Additional notes..."></textarea>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" id="submitBtn">Add Slot</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add Schedule Slot';
    document.getElementById('formAction').value = 'add_slot';
    document.getElementById('scheduleId').value = '';
    document.getElementById('submitBtn').textContent = 'Add Slot';
    document.getElementById('scheduleForm').reset();
    document.getElementById('scheduleModal').classList.add('show');
}

function openEditModal(data) {
    document.getElementById('modalTitle').textContent = 'Edit Schedule Slot';
    document.getElementById('formAction').value = 'edit_slot';
    document.getElementById('scheduleId').value = data.schedule_id;
    document.getElementById('submitBtn').textContent = 'Update Slot';

    document.getElementById('modal_title').value = data.title;
    document.getElementById('modal_type').value = data.schedule_type;
    document.getElementById('modal_day').value = data.day_of_week;
    document.getElementById('modal_start').value = data.start_time;
    document.getElementById('modal_end').value = data.end_time;
    document.getElementById('modal_location').value = data.location || '';
    document.getElementById('modal_description').value = data.description || '';
    document.getElementById('modal_course').value = data.course_id || '';

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

// Client-side validation: ensure selected course is from dropdown options (lecturer)
document.getElementById('scheduleForm').addEventListener('submit', function(e) {
    var select = document.getElementById('modal_course');
    if (select) {
        var val = select.value;
        if (val !== '') {
            var found = Array.from(select.options).some(function(opt) { return opt.value === val; });
            if (!found) {
                e.preventDefault();
                alert('Invalid course selection. Please choose from your courses.');
                return false;
            }
        }
    }
});
</script>

<?php include '../includes/footer.php'; ?>
