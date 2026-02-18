<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#4f46e5">
    <meta name="description" content="Attendance Management System for Educational Institutions">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Attendance">
    <link rel="manifest" href="../manifest.json">
    <link rel="apple-touch-icon" href="../assets/images/icon-192.png">
    <link rel="icon" type="image/png" sizes="192x192" href="../assets/images/icon-192.png">
    <title>Attendance System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <!-- Register Service Worker & PWA install prompt -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('../sw.js')
                    .then(function(registration) {
                        console.log('Service Worker registered successfully:', registration.scope);
                    })
                    .catch(function(error) {
                        console.log('Service Worker registration failed:', error);
                    });
            });
        }

        // Capture the install prompt and show "Install App" button
        let deferredInstallPrompt = null;
        window.addEventListener('beforeinstallprompt', function(e) {
            e.preventDefault();
            deferredInstallPrompt = e;
            var btn = document.getElementById('pwaInstallBtn');
            if (btn) btn.style.display = 'flex';
        });

        window.addEventListener('appinstalled', function() {
            deferredInstallPrompt = null;
            var btn = document.getElementById('pwaInstallBtn');
            if (btn) btn.style.display = 'none';
        });

        function triggerPWAInstall() {
            if (!deferredInstallPrompt) return;
            deferredInstallPrompt.prompt();
            deferredInstallPrompt.userChoice.then(function(result) {
                deferredInstallPrompt = null;
                var btn = document.getElementById('pwaInstallBtn');
                if (btn) btn.style.display = 'none';
            });
        }
    </script>
</head>
<body>
    <?php if(isset($_SESSION['user_id'])): ?>
    <div class="app-layout">
        <!-- Mobile Header -->
        <header class="mobile-header">
            <button class="hamburger-btn" id="hamburgerBtn" onclick="toggleMobileSidebar()">
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
            </button>
            <div class="mobile-logo">Attendance</div>
            <div class="mobile-actions">
                <div class="notification-bell" onclick="toggleNotificationPanel()">
                    <span class="bell-icon">&#128276;</span>
                    <span class="badge" id="reminderBadgeMobile" style="display: none;">0</span>
                </div>
            </div>
        </header>

        <!-- Sidebar Overlay -->
        <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleMobileSidebar()"></div>

        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo">Attendance</div>
                <div class="notification-bell desktop-only" id="notificationBell" onclick="toggleNotificationPanel()">
                    <span class="bell-icon">&#128276;</span>
                    <span class="badge" id="reminderBadge" style="display: none;">0</span>
                </div>
                <button class="sidebar-close-btn mobile-only" onclick="toggleMobileSidebar()">&#10005;</button>
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
                    <a href="../admin/dashboard.php" class="nav-item">
                        <span class="nav-icon">&#9783;</span>
                        Dashboard
                    </a>
                    <a href="../admin/users.php" class="nav-item">
                        <span class="nav-icon">&#128100;</span>
                        Manage Users
                    </a>
                    <a href="../admin/courses.php" class="nav-item">
                        <span class="nav-icon">&#128218;</span>
                        Manage Courses
                    </a>
                    <a href="../admin/reports.php" class="nav-item">
                        <span class="nav-icon">&#128202;</span>
                        Reports
                    </a>
                <?php elseif($_SESSION['role'] == 'lecturer'): ?>
                    <a href="../lecturer/dashboard.php" class="nav-item">
                        <span class="nav-icon">&#9783;</span>
                        Dashboard
                    </a>
                    <a href="../lecturer/take_attendance.php" class="nav-item">
                        <span class="nav-icon">&#10003;</span>
                        Take Attendance
                    </a>
                    <a href="../lecturer/schedule.php" class="nav-item">
                        <span class="nav-icon">&#128336;</span>
                        Class Schedule
                    </a>
                    <a href="../lecturer/personal_schedule.php" class="nav-item">
                        <span class="nav-icon">&#128197;</span>
                        My Schedule
                    </a>
                    <a href="../lecturer/events.php" class="nav-item">
                        <span class="nav-icon">&#128221;</span>
                        Manage Events
                    </a>
                    <a href="../lecturer/reports.php" class="nav-item">
                        <span class="nav-icon">&#128202;</span>
                        View Reports
                    </a>
                    <a href="../lecturer/settings.php" class="nav-item">
                        <span class="nav-icon">&#9881;</span>
                        Settings
                    </a>
                <?php elseif($_SESSION['role'] == 'student'): ?>
                    <a href="../student/dashboard.php" class="nav-item">
                        <span class="nav-icon">&#9783;</span>
                        My Attendance
                    </a>
                    <a href="../student/timetable.php" class="nav-item">
                        <span class="nav-icon">&#128197;</span>
                        Timetable
                    </a>
                    <a href="../student/schedule.php" class="nav-item">
                        <span class="nav-icon">&#128336;</span>
                        My Schedule
                    </a>
                    <a href="../student/courses.php" class="nav-item">
                        <span class="nav-icon">&#128218;</span>
                        My Courses
                    </a>
                    <a href="../student/settings.php" class="nav-item">
                        <span class="nav-icon">&#9881;</span>
                        Settings
                    </a>
                <?php endif; ?>
            </nav>

            <div class="sidebar-footer">
                <button id="pwaInstallBtn" onclick="triggerPWAInstall()" class="nav-item pwa-install-btn" style="display:none;">
                    <span class="nav-icon">📱</span>
                    Install App
                </button>
                <a href="../logout.php" class="nav-item logout">
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
