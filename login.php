<?php
session_start();

// If already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['role']) {
        case 'admin':    header("Location: admin/dashboard.php");    break;
        case 'lecturer': header("Location: lecturer/dashboard.php"); break;
        case 'student':  header("Location: student/dashboard.php");  break;
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#4f46e5">
    <title>Login — AttendTrack</title>
    <link rel="manifest" href="manifest.json">
    <link rel="stylesheet" href="assets/css/style.css">
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('sw.js')
                    .then(r => console.log('SW registered:', r.scope))
                    .catch(e => console.log('SW failed:', e));
            });
        }

        // Capture PWA install prompt
        let deferredPrompt;
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            const btn = document.getElementById('installBtn');
            if (btn) btn.style.display = 'inline-flex';
        });

        function installApp() {
            if (!deferredPrompt) return;
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then(() => { deferredPrompt = null; });
        }
    </script>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div style="text-align:center;margin-bottom:8px;">
                <a href="landing.php" style="color:var(--text-muted,#94a3b8);font-size:0.85rem;text-decoration:none;">&larr; Back to home</a>
            </div>
            <h1>AttendTrack</h1>

            <?php if(isset($_SESSION['error'])): ?>
                <div class="alert danger">
                    <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <form action="actions/login_process.php" method="POST" id="loginForm">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>

            <div style="text-align:center;margin-top:20px;">
                <p style="color:var(--text-muted,#94a3b8);font-size:0.88rem;">
                    Don't have an account?
                    <a href="landing.php#waitlist" style="color:#6366f1;font-weight:600;">Join the Waitlist</a>
                </p>
            </div>

            <button id="installBtn"
                onclick="installApp()"
                style="display:none;margin-top:16px;width:100%;align-items:center;justify-content:center;gap:8px;background:rgba(79,70,229,0.12);border:1.5px solid rgba(79,70,229,0.4);color:#6366f1;padding:10px;border-radius:8px;cursor:pointer;font-size:0.9rem;font-weight:600;">
                📱 Install App
            </button>
        </div>
    </div>
    <script src="assets/js/script.js"></script>
</body>
</html>
