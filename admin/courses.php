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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $course_name = trim($_POST['course_name']);
                $course_code = trim($_POST['course_code']);
                $lecturer_id = !empty($_POST['lecturer_id']) ? intval($_POST['lecturer_id']) : null;

                $stmt = $conn->prepare("INSERT INTO courses (course_name, course_code, lecturer_id) VALUES (?, ?, ?)");
                $stmt->bind_param("ssi", $course_name, $course_code, $lecturer_id);

                if ($stmt->execute()) {
                    $_SESSION['flash_message'] = "Course added successfully";
                    $_SESSION['flash_type'] = "success";
                } else {
                    $_SESSION['flash_message'] = "Error adding course: " . $conn->error;
                    $_SESSION['flash_type'] = "error";
                }
                $stmt->close();
                break;

            case 'edit':
                $course_id = intval($_POST['course_id']);
                $course_name = trim($_POST['course_name']);
                $course_code = trim($_POST['course_code']);
                $lecturer_id = !empty($_POST['lecturer_id']) ? intval($_POST['lecturer_id']) : null;

                $stmt = $conn->prepare("UPDATE courses SET course_name=?, course_code=?, lecturer_id=? WHERE course_id=?");
                $stmt->bind_param("ssii", $course_name, $course_code, $lecturer_id, $course_id);

                if ($stmt->execute()) {
                    $_SESSION['flash_message'] = "Course updated successfully";
                    $_SESSION['flash_type'] = "success";
                } else {
                    $_SESSION['flash_message'] = "Error updating course";
                    $_SESSION['flash_type'] = "error";
                }
                $stmt->close();
                break;

            case 'delete':
                $course_id = intval($_POST['course_id']);

                $stmt = $conn->prepare("DELETE FROM courses WHERE course_id=?");
                $stmt->bind_param("i", $course_id);

                if ($stmt->execute()) {
                    $_SESSION['flash_message'] = "Course deleted successfully";
                    $_SESSION['flash_type'] = "success";
                } else {
                    $_SESSION['flash_message'] = "Error deleting course";
                    $_SESSION['flash_type'] = "error";
                }
                $stmt->close();
                break;

            case 'enroll':
                $student_id = intval($_POST['student_id']);
                $course_id = intval($_POST['course_id']);

                $stmt = $conn->prepare("INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $student_id, $course_id);

                if ($stmt->execute()) {
                    $_SESSION['flash_message'] = "Student enrolled successfully";
                    $_SESSION['flash_type'] = "success";
                } else {
                    $_SESSION['flash_message'] = "Error enrolling student (may already be enrolled)";
                    $_SESSION['flash_type'] = "error";
                }
                $stmt->close();
                break;

            case 'unenroll':
                $student_id = intval($_POST['student_id']);
                $course_id = intval($_POST['course_id']);

                $stmt = $conn->prepare("DELETE FROM enrollments WHERE student_id=? AND course_id=?");
                $stmt->bind_param("ii", $student_id, $course_id);

                if ($stmt->execute()) {
                    $_SESSION['flash_message'] = "Student unenrolled successfully";
                    $_SESSION['flash_type'] = "success";
                } else {
                    $_SESSION['flash_message'] = "Error unenrolling student";
                    $_SESSION['flash_type'] = "error";
                }
                $stmt->close();
                break;
        }
        header("Location: courses.php");
        exit();
    }
}

// Get all lecturers for dropdown
$lecturers = $conn->query("SELECT user_id, name FROM users WHERE role='lecturer' ORDER BY name");

// Get all students for enrollment
$students = $conn->query("SELECT user_id, name FROM users WHERE role='student' ORDER BY name");

// Get all courses with lecturer info
$courses_result = $conn->query("SELECT c.course_id, c.course_name, c.course_code, c.lecturer_id, u.name as lecturer_name
                                FROM courses c
                                LEFT JOIN users u ON c.lecturer_id = u.user_id
                                ORDER BY c.course_name");
?>

<div class="container">
    <div class="page-header">
        <h2>Manage Courses</h2>
        <p>Add, edit, or remove courses and manage enrollments</p>
    </div>

    <?php displayFlashMessage(); ?>

    <!-- Add Course Form -->
    <div class="card" style="margin-bottom: 30px;">
        <h3>Add New Course</h3>
        <form action="" method="POST" style="margin-top: 15px;">
            <input type="hidden" name="action" value="add">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div class="form-group">
                    <label for="course_name">Course Name</label>
                    <input type="text" name="course_name" id="course_name" required>
                </div>
                <div class="form-group">
                    <label for="course_code">Course Code</label>
                    <input type="text" name="course_code" id="course_code" required>
                </div>
                <div class="form-group">
                    <label for="lecturer_id">Assigned Lecturer</label>
                    <select name="lecturer_id" id="lecturer_id">
                        <option value="">-- Select Lecturer --</option>
                        <?php
                        $lecturers->data_seek(0);
                        while($lec = $lecturers->fetch_assoc()): ?>
                            <option value="<?php echo $lec['user_id']; ?>"><?php echo htmlspecialchars($lec['name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="margin-top: 15px;">Add Course</button>
        </form>
    </div>

    <!-- Enroll Student Form -->
    <div class="card" style="margin-bottom: 30px;">
        <h3>Enroll Student in Course</h3>
        <form action="" method="POST" style="margin-top: 15px;">
            <input type="hidden" name="action" value="enroll">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div class="form-group">
                    <label for="enroll_student">Student</label>
                    <select name="student_id" id="enroll_student" required>
                        <option value="">-- Select Student --</option>
                        <?php
                        $students->data_seek(0);
                        while($stu = $students->fetch_assoc()): ?>
                            <option value="<?php echo $stu['user_id']; ?>"><?php echo htmlspecialchars($stu['name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="enroll_course">Course</label>
                    <select name="course_id" id="enroll_course" required>
                        <option value="">-- Select Course --</option>
                        <?php
                        $courses_result->data_seek(0);
                        while($crs = $courses_result->fetch_assoc()): ?>
                            <option value="<?php echo $crs['course_id']; ?>"><?php echo htmlspecialchars($crs['course_code'] . ' - ' . $crs['course_name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-success" style="margin-top: 15px;">Enroll Student</button>
        </form>
    </div>

    <!-- Courses Table -->
    <h3>All Courses</h3>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Lecturer</th>
                    <th>Students</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $courses_result->data_seek(0);
                while($course = $courses_result->fetch_assoc()):
                    // Get enrolled students count
                    $count_query = $conn->query("SELECT COUNT(*) as cnt FROM enrollments WHERE course_id=" . $course['course_id']);
                    $student_count = $count_query->fetch_assoc()['cnt'];
                ?>
                <tr>
                    <td><?php echo $course['course_id']; ?></td>
                    <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                    <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                    <td><?php echo htmlspecialchars($course['lecturer_name'] ?? 'Not Assigned'); ?></td>
                    <td><?php echo $student_count; ?> enrolled</td>
                    <td>
                        <button class="btn btn-primary" onclick='editCourse(<?php echo json_encode($course); ?>)' style="padding: 6px 12px; font-size: 0.85rem;">Edit</button>
                        <form action="" method="POST" style="display: inline;" onsubmit="return confirm('Delete this course? This will also remove all enrollments and attendance records.');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="course_id" value="<?php echo $course['course_id']; ?>">
                            <button type="submit" class="btn btn-danger" style="padding: 6px 12px; font-size: 0.85rem;">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 1000; align-items: center; justify-content: center;">
    <div class="card" style="width: 90%; max-width: 500px;">
        <h3>Edit Course</h3>
        <form action="" method="POST" style="margin-top: 15px;">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="course_id" id="edit_course_id">
            <div class="form-group">
                <label for="edit_course_name">Course Name</label>
                <input type="text" name="course_name" id="edit_course_name" required>
            </div>
            <div class="form-group">
                <label for="edit_course_code">Course Code</label>
                <input type="text" name="course_code" id="edit_course_code" required>
            </div>
            <div class="form-group">
                <label for="edit_lecturer_id">Assigned Lecturer</label>
                <select name="lecturer_id" id="edit_lecturer_id">
                    <option value="">-- Select Lecturer --</option>
                    <?php
                    $lecturers->data_seek(0);
                    while($lec = $lecturers->fetch_assoc()): ?>
                        <option value="<?php echo $lec['user_id']; ?>"><?php echo htmlspecialchars($lec['name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <button type="button" class="btn" onclick="closeModal()">Cancel</button>
        </form>
    </div>
</div>

<script>
function editCourse(course) {
    document.getElementById('edit_course_id').value = course.course_id;
    document.getElementById('edit_course_name').value = course.course_name;
    document.getElementById('edit_course_code').value = course.course_code;
    document.getElementById('edit_lecturer_id').value = course.lecturer_id || '';
    document.getElementById('editModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('editModal').style.display = 'none';
}

document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>

<?php include '../includes/footer.php'; ?>
