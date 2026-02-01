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
            $schedule_id = isset($_POST['schedule_id']) && !empty($_POST['schedule_id']) ? intval($_POST['schedule_id']) : null;

            if (savePersonalScheduleSlot($conn, $student_id, $title, $schedule_type, $day_of_week, $start_time, $end_time, $location, $description, $schedule_id)) {
                $_SESSION['flash_message'] = $schedule_id ? "Schedule updated successfully!" : "Schedule slot added successfully!";
                $_SESSION['flash_type'] = 'success';
            } else {
                $_SESSION['flash_message'] = "Failed to save schedule. The time slot may already exist.";
                $_SESSION['flash_type'] = 'error';
            }
        } elseif ($_POST['action'] === 'delete_slot') {
            $schedule_id = intval($_POST['schedule_id']);

            if (deletePersonalScheduleSlot($conn, $schedule_id, $student_id)) {
                $_SESSION['flash_message'] = "Schedule slot deleted successfully!";
                $_SESSION['flash_type'] = 'success';
            } else {
                $_SESSION['flash_message'] = "Failed to delete schedule slot.";
                $_SESSION['flash_type'] = 'error';
            }
        }

        header("Location: schedule.php");
        exit();
    }
}

// Get personal schedule
$schedule_result = getPersonalSchedule($conn, $student_id);
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
    'study' => 'Study Time',
    'personal' => 'Personal',
    'other' => 'Other'
];
?>

<div class="container">
    <div class="page-header">
        <h2>My Schedule</h2>
        <p>Create and manage your personal weekly timetable</p>
    </div>

    <?php displayFlashMessage(); ?>

    <!-- Add Schedule Button -->
    <div class="action-bar">
        <button type="button" class="btn btn-primary" onclick="openAddModal()">
            + Add Schedule Slot
        </button>
        <a href="timetable.php" class="btn btn-secondary">View in Timetable Grid</a>
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
                        // Find items in this slot
                        $slot_items = array_filter($schedule_data, function($item) use ($day, $hour) {
                            $start_hour = (int)date('H', strtotime($item['start_time']));
                            return $item['day_of_week'] === $day && $start_hour === $hour;
                        });
                    ?>
                    <td class="schedule-cell <?php echo !empty($slot_items) ? 'has-class' : ''; ?>">
                        <?php foreach ($slot_items as $item): ?>
                        <div class="schedule-item <?php echo getPersonalScheduleTypeClass($item['schedule_type']); ?>"
                             onclick="openEditModal(<?php echo htmlspecialchars(json_encode($item)); ?>)">
                            <span class="schedule-title"><?php echo htmlspecialchars($item['title']); ?></span>
                            <span class="schedule-type"><?php echo getPersonalScheduleTypeName($item['schedule_type']); ?></span>
                            <span class="schedule-time">
                                <?php echo date('g:i', strtotime($item['start_time'])); ?> -
                                <?php echo date('g:i A', strtotime($item['end_time'])); ?>
                            </span>
                            <?php if ($item['location']): ?>
                            <span class="schedule-room"><?php echo htmlspecialchars($item['location']); ?></span>
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

    <!-- Schedule List View -->
    <div class="schedule-list-view">
        <h3>Schedule List</h3>
        <?php if (empty($schedule_data)): ?>
        <div class="alert info">No schedule items yet. Click "Add Schedule Slot" to create your timetable.</div>
        <?php else: ?>
        <div class="schedule-list">
            <?php foreach ($schedule_data as $item): ?>
            <div class="schedule-list-item">
                <div class="schedule-list-info">
                    <span class="schedule-list-title"><?php echo htmlspecialchars($item['title']); ?></span>
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
                <input type="text" name="title" id="modal_title" required placeholder="e.g., Database Lecture, Study Session">
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
                <input type="text" name="location" id="modal_location" placeholder="e.g., Room 201, Library, Home">
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
