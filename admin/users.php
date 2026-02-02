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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = trim($_POST['name']);
                $email = trim($_POST['email']);
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $role = $_POST['role'];

                $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $name, $email, $password, $role);

                if ($stmt->execute()) {
                    $_SESSION['flash_message'] = "User added successfully";
                    $_SESSION['flash_type'] = "success";
                } else {
                    $_SESSION['flash_message'] = "Error adding user: " . $conn->error;
                    $_SESSION['flash_type'] = "error";
                }
                $stmt->close();
                break;

            case 'edit':
                $user_id = intval($_POST['user_id']);
                $name = trim($_POST['name']);
                $email = trim($_POST['email']);
                $role = $_POST['role'];

                if (!empty($_POST['password'])) {
                    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET name=?, email=?, password=?, role=? WHERE user_id=?");
                    $stmt->bind_param("ssssi", $name, $email, $password, $role, $user_id);
                } else {
                    $stmt = $conn->prepare("UPDATE users SET name=?, email=?, role=? WHERE user_id=?");
                    $stmt->bind_param("sssi", $name, $email, $role, $user_id);
                }

                if ($stmt->execute()) {
                    $_SESSION['flash_message'] = "User updated successfully";
                    $_SESSION['flash_type'] = "success";
                } else {
                    $_SESSION['flash_message'] = "Error updating user";
                    $_SESSION['flash_type'] = "error";
                }
                $stmt->close();
                break;

            case 'delete':
                $user_id = intval($_POST['user_id']);

                // Prevent self-deletion
                if ($user_id == $_SESSION['user_id']) {
                    $_SESSION['flash_message'] = "Cannot delete your own account";
                    $_SESSION['flash_type'] = "error";
                } else {
                    $stmt = $conn->prepare("DELETE FROM users WHERE user_id=?");
                    $stmt->bind_param("i", $user_id);

                    if ($stmt->execute()) {
                        $_SESSION['flash_message'] = "User deleted successfully";
                        $_SESSION['flash_type'] = "success";
                    } else {
                        $_SESSION['flash_message'] = "Error deleting user";
                        $_SESSION['flash_type'] = "error";
                    }
                    $stmt->close();
                }
                break;
        }
        header("Location: users.php");
        exit();
    }
}

// Get all users
$users_result = $conn->query("SELECT user_id, name, email, role FROM users ORDER BY role, name");
?>

<div class="container">
    <div class="page-header">
        <h2>Manage Users</h2>
        <p>Add, edit, or remove system users</p>
    </div>

    <?php displayFlashMessage(); ?>

    <!-- Add User Form -->
    <div class="card" style="margin-bottom: 30px;">
        <h3>Add New User</h3>
        <form action="" method="POST" style="margin-top: 15px;">
            <input type="hidden" name="action" value="add">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" name="name" id="name" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" required>
                </div>
                <div class="form-group">
                    <label for="role">Role</label>
                    <select name="role" id="role" required>
                        <option value="student">Student</option>
                        <option value="lecturer">Lecturer</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="margin-top: 15px;">Add User</button>
        </form>
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
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $users_array = [];
                while($user = $users_result->fetch_assoc()) {
                    $users_array[] = $user;
                ?>
                <tr>
                    <td><?php echo $user['user_id']; ?></td>
                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><?php echo ucfirst($user['role']); ?></td>
                    <td>
                        <button class="btn btn-primary" onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)" style="padding: 6px 12px; font-size: 0.85rem;">Edit</button>
                        <?php if($user['user_id'] != $_SESSION['user_id']): ?>
                        <form action="" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                            <button type="submit" class="btn btn-danger" style="padding: 6px 12px; font-size: 0.85rem;">Delete</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>

        <!-- Mobile Card View -->
        <div class="table-responsive-cards">
            <?php foreach($users_array as $user): ?>
            <div class="table-card">
                <div class="table-card-header">
                    <div>
                        <div class="table-card-title"><?php echo htmlspecialchars($user['name']); ?></div>
                        <div class="table-card-subtitle"><?php echo htmlspecialchars($user['email']); ?></div>
                    </div>
                    <span class="badge" style="background-color: var(--primary-color); color: white; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem;">
                        <?php echo ucfirst($user['role']); ?>
                    </span>
                </div>
                <div class="table-card-actions">
                    <button class="btn btn-primary" onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">Edit</button>
                    <?php if($user['user_id'] != $_SESSION['user_id']): ?>
                    <form action="" method="POST" style="flex: 1;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                        <button type="submit" class="btn btn-danger" style="width: 100%;">Delete</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 1000; align-items: center; justify-content: center;">
    <div class="card" style="width: 90%; max-width: 500px;">
        <h3>Edit User</h3>
        <form action="" method="POST" style="margin-top: 15px;">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="user_id" id="edit_user_id">
            <div class="form-group">
                <label for="edit_name">Name</label>
                <input type="text" name="name" id="edit_name" required>
            </div>
            <div class="form-group">
                <label for="edit_email">Email</label>
                <input type="email" name="email" id="edit_email" required>
            </div>
            <div class="form-group">
                <label for="edit_password">Password (leave blank to keep current)</label>
                <input type="password" name="password" id="edit_password">
            </div>
            <div class="form-group">
                <label for="edit_role">Role</label>
                <select name="role" id="edit_role" required>
                    <option value="student">Student</option>
                    <option value="lecturer">Lecturer</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <button type="button" class="btn" onclick="closeModal()">Cancel</button>
        </form>
    </div>
</div>

<script>
function editUser(user) {
    document.getElementById('edit_user_id').value = user.user_id;
    document.getElementById('edit_name').value = user.name;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_role').value = user.role;
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
