<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance System</title>
    <link rel="stylesheet" href="/attendance_system/assets/css/style.css">
</head>
<body>
    <?php if(isset($_SESSION['user_id'])): ?>
    <div class="app-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">Attendance</div>
                <div class="notification-bell" id="notificationBell" onclick="toggleNotificationPanel()">
                    <span class="bell-icon">&#128276;</span>
                    <span class="badge" id="reminderBadge" style="display: none;">0</span>
                </div>
            </div>

            <div class="user-profile">
                <div class="avatar"><?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?></div>
                <div class="user-details">
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['name']); ?></span>
                    <span class="user-role"><?php echo ucfirst($_SESSION['role']); ?></span>
                </div>
            </div>

            <nav class="sidebar-nav">
                <?php if($_SESSION['role'] == 'admin'): ?>
                    <a href="/attendance_system/admin/dashboard.php" class="nav-item">
                        <span class="nav-icon">&#9783;</span>
                        Dashboard
                    </a>
                    <a href="/attendance_system/admin/users.php" class="nav-item">
                        <span class="nav-icon">&#128100;</span>
                        Manage Users
                    </a>
                    <a href="/attendance_system/admin/courses.php" class="nav-item">
                        <span class="nav-icon">&#128218;</span>
                        Manage Courses
                    </a>
                    <a href="/attendance_system/admin/reports.php" class="nav-item">
                        <span class="nav-icon">&#128202;</span>
                        Reports
                    </a>
                <?php elseif($_SESSION['role'] == 'lecturer'): ?>
                    <a href="/attendance_system/lecturer/dashboard.php" class="nav-item">
                        <span class="nav-icon">&#9783;</span>
                        Dashboard
                    </a>
                    <a href="/attendance_system/lecturer/take_attendance.php" class="nav-item">
                        <span class="nav-icon">&#10003;</span>
                        Take Attendance
                    </a>
                    <a href="/attendance_system/lecturer/schedule.php" class="nav-item">
                        <span class="nav-icon">&#128336;</span>
                        Class Schedule
                    </a>
                    <a href="/attendance_system/lecturer/personal_schedule.php" class="nav-item">
                        <span class="nav-icon">&#128197;</span>
                        My Schedule
                    </a>
                    <a href="/attendance_system/lecturer/events.php" class="nav-item">
                        <span class="nav-icon">&#128221;</span>
                        Manage Events
                    </a>
                    <a href="/attendance_system/lecturer/reports.php" class="nav-item">
                        <span class="nav-icon">&#128202;</span>
                        View Reports
                    </a>
                    <a href="/attendance_system/lecturer/settings.php" class="nav-item">
                        <span class="nav-icon">&#9881;</span>
                        Settings
                    </a>
                <?php elseif($_SESSION['role'] == 'student'): ?>
                    <a href="/attendance_system/student/dashboard.php" class="nav-item">
                        <span class="nav-icon">&#9783;</span>
                        My Attendance
                    </a>
                    <a href="/attendance_system/student/timetable.php" class="nav-item">
                        <span class="nav-icon">&#128197;</span>
                        Timetable
                    </a>
                    <a href="/attendance_system/student/schedule.php" class="nav-item">
                        <span class="nav-icon">&#128336;</span>
                        My Schedule
                    </a>
                    <a href="/attendance_system/student/courses.php" class="nav-item">
                        <span class="nav-icon">&#128218;</span>
                        My Courses
                    </a>
                    <a href="/attendance_system/student/settings.php" class="nav-item">
                        <span class="nav-icon">&#9881;</span>
                        Settings
                    </a>
                <?php endif; ?>
            </nav>

            <div class="sidebar-footer">
                <a href="/attendance_system/logout.php" class="nav-item logout">
                    <span class="nav-icon">&#10140;</span>
                    Logout
                </a>
            </div>
        </aside>

        <!-- Notification Panel -->
        <div class="notification-panel" id="notificationPanel">
            <div class="notification-header">
                <h4>Reminders</h4>
                <button onclick="markAllSeen()" class="mark-all-btn">Mark all read</button>
            </div>
            <div class="notification-list" id="notificationList">
                <div class="notification-empty">No reminders</div>
            </div>
        </div>

        <!-- Main Content -->
        <main class="main-content">
    <?php endif; ?>
