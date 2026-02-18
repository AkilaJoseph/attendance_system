<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AttendTrack — Smart Attendance for Modern Institutions</title>
    <meta name="description" content="AttendTrack replaces paper registers and clunky spreadsheets with a beautiful, automated attendance platform built for students, lecturers, and administrators.">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary:        #4f46e5;
            --primary-hover:  #4338ca;
            --primary-glow:   rgba(79, 70, 229, 0.25);
            --secondary:      #6366f1;
            --success:        #22c55e;
            --warning:        #f59e0b;
            --bg:             #1e1e2e;
            --card:           #2d2d3f;
            --card-hover:     #343448;
            --border:         rgba(255,255,255,0.08);
            --text:           #e2e8f0;
            --text-muted:     #94a3b8;
            --radius:         12px;
            --radius-lg:      20px;
            --shadow:         0 4px 24px rgba(0,0,0,0.4);
            --shadow-sm:      0 2px 8px rgba(0,0,0,0.3);
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* ─── UTILITY ─────────────────────────────────────────── */
        .container { max-width: 1100px; margin: 0 auto; padding: 0 24px; }
        .btn {
            display: inline-block;
            padding: 12px 28px;
            border-radius: var(--radius);
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            text-decoration: none;
            border: none;
            transition: transform 0.15s, box-shadow 0.15s, background 0.15s;
        }
        .btn:hover { transform: translateY(-2px); }
        .btn-primary {
            background: var(--primary);
            color: #fff;
            box-shadow: 0 4px 16px var(--primary-glow);
        }
        .btn-primary:hover { background: var(--primary-hover); box-shadow: 0 6px 24px var(--primary-glow); }
        .btn-ghost {
            background: transparent;
            color: var(--text);
            border: 1.5px solid var(--border);
        }
        .btn-ghost:hover { border-color: var(--secondary); color: var(--secondary); }
        .section-label {
            display: inline-block;
            background: rgba(79,70,229,0.15);
            color: var(--secondary);
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 5px 14px;
            border-radius: 100px;
            margin-bottom: 14px;
        }
        .section-title {
            font-size: clamp(1.7rem, 4vw, 2.4rem);
            font-weight: 700;
            line-height: 1.25;
            margin-bottom: 14px;
        }
        .section-subtitle {
            font-size: 1.05rem;
            color: var(--text-muted);
            max-width: 560px;
            margin: 0 auto;
        }
        section { padding: 90px 0; }

        /* ─── NAVBAR ──────────────────────────────────────────── */
        nav {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(30,30,46,0.9);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
        }
        .nav-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 64px;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--text);
            text-decoration: none;
        }
        .logo-icon {
            width: 36px; height: 36px;
            background: var(--primary);
            border-radius: 9px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem;
        }
        .nav-actions { display: flex; align-items: center; gap: 12px; }
        .nav-link {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.92rem;
            font-weight: 500;
            transition: color 0.15s;
        }
        .nav-link:hover { color: var(--text); }

        /* ─── HERO ────────────────────────────────────────────── */
        #hero {
            padding: 100px 0 80px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        #hero::before {
            content: '';
            position: absolute;
            top: -120px; left: 50%; transform: translateX(-50%);
            width: 800px; height: 600px;
            background: radial-gradient(ellipse, rgba(79,70,229,0.18) 0%, transparent 70%);
            pointer-events: none;
        }
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(79,70,229,0.12);
            border: 1px solid rgba(79,70,229,0.3);
            color: var(--secondary);
            font-size: 0.85rem;
            font-weight: 600;
            padding: 6px 16px;
            border-radius: 100px;
            margin-bottom: 28px;
        }
        .hero-badge span { width: 6px; height: 6px; background: var(--success); border-radius: 50%; animation: pulse 2s infinite; }
        @keyframes pulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:0.6;transform:scale(1.4)} }
        .hero-title {
            font-size: clamp(2.2rem, 6vw, 3.8rem);
            font-weight: 800;
            line-height: 1.15;
            margin-bottom: 22px;
            letter-spacing: -0.02em;
        }
        .hero-title .accent { color: var(--primary); }
        .hero-subtitle {
            font-size: 1.15rem;
            color: var(--text-muted);
            max-width: 600px;
            margin: 0 auto 38px;
        }
        .hero-cta { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; }
        .hero-stats {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin-top: 64px;
            flex-wrap: wrap;
        }
        .hero-stat { text-align: center; }
        .hero-stat-value { font-size: 1.9rem; font-weight: 800; color: var(--primary); }
        .hero-stat-label { font-size: 0.85rem; color: var(--text-muted); margin-top: 2px; }

        /* ─── PROBLEM ─────────────────────────────────────────── */
        #problem { background: var(--card); }
        #problem .inner { text-align: center; }
        .problem-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-top: 48px;
        }
        .problem-card {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 24px;
            text-align: left;
        }
        .problem-card .icon { font-size: 1.8rem; margin-bottom: 12px; }
        .problem-card h4 { font-size: 1rem; font-weight: 600; margin-bottom: 8px; }
        .problem-card p { font-size: 0.88rem; color: var(--text-muted); line-height: 1.5; }
        .problem-card.solved {
            border-color: rgba(79,70,229,0.4);
            background: rgba(79,70,229,0.06);
        }
        .problem-card.solved h4 { color: var(--secondary); }

        /* ─── FEATURES ────────────────────────────────────────── */
        #features { text-align: center; }
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 52px;
            text-align: left;
        }
        .feature-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 28px;
            transition: border-color 0.2s, transform 0.2s, box-shadow 0.2s;
        }
        .feature-card:hover {
            border-color: rgba(79,70,229,0.5);
            transform: translateY(-4px);
            box-shadow: var(--shadow);
        }
        .feature-icon {
            width: 48px; height: 48px;
            background: rgba(79,70,229,0.15);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem;
            margin-bottom: 18px;
        }
        .feature-card h3 { font-size: 1.05rem; font-weight: 700; margin-bottom: 8px; }
        .feature-card p { font-size: 0.9rem; color: var(--text-muted); line-height: 1.6; }

        /* ─── HOW IT WORKS ────────────────────────────────────── */
        #how { background: var(--card); text-align: center; }
        .steps-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 32px;
            margin-top: 52px;
            position: relative;
        }
        .step { position: relative; }
        .step-number {
            width: 52px; height: 52px;
            background: var(--primary);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem;
            font-weight: 800;
            margin: 0 auto 18px;
            box-shadow: 0 4px 16px var(--primary-glow);
        }
        .step h3 { font-size: 1rem; font-weight: 700; margin-bottom: 8px; }
        .step p { font-size: 0.9rem; color: var(--text-muted); line-height: 1.55; }

        /* ─── ROLES ───────────────────────────────────────────── */
        #roles { text-align: center; }
        .roles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 48px;
            text-align: left;
        }
        .role-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 28px;
        }
        .role-header { display: flex; align-items: center; gap: 12px; margin-bottom: 18px; }
        .role-badge {
            padding: 4px 12px;
            border-radius: 100px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .role-badge.admin    { background: rgba(239,68,68,0.15);  color: #ef4444; }
        .role-badge.lecturer { background: rgba(245,158,11,0.15); color: #f59e0b; }
        .role-badge.student  { background: rgba(34,197,94,0.15);  color: #22c55e; }
        .role-icon { font-size: 1.6rem; }
        .role-card h3 { font-size: 1.05rem; font-weight: 700; }
        .role-card ul { list-style: none; }
        .role-card ul li {
            font-size: 0.9rem;
            color: var(--text-muted);
            padding: 7px 0;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: flex-start; gap: 8px;
        }
        .role-card ul li:last-child { border-bottom: none; }
        .role-card ul li::before { content: '✓'; color: var(--primary); font-weight: 700; flex-shrink: 0; }

        /* ─── WAITLIST ────────────────────────────────────────── */
        #waitlist {
            background: linear-gradient(135deg, #1e1e2e 0%, #1a1a2e 50%, #16213e 100%);
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        #waitlist::before {
            content: '';
            position: absolute;
            bottom: -100px; right: -100px;
            width: 500px; height: 500px;
            background: radial-gradient(ellipse, rgba(79,70,229,0.12) 0%, transparent 70%);
            pointer-events: none;
        }
        .waitlist-box {
            background: var(--card);
            border: 1px solid rgba(79,70,229,0.3);
            border-radius: var(--radius-lg);
            padding: 48px 40px;
            max-width: 580px;
            margin: 0 auto;
            box-shadow: 0 8px 48px rgba(79,70,229,0.15);
            position: relative;
        }
        .waitlist-box h2 { font-size: 1.9rem; font-weight: 800; margin-bottom: 10px; }
        .waitlist-box .sub { color: var(--text-muted); margin-bottom: 32px; font-size: 0.98rem; }
        .form-group { margin-bottom: 18px; text-align: left; }
        .form-group label { display: block; font-size: 0.88rem; font-weight: 600; margin-bottom: 7px; color: var(--text-muted); }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            background: var(--bg);
            border: 1.5px solid var(--border);
            border-radius: var(--radius);
            color: var(--text);
            font-size: 0.95rem;
            font-family: inherit;
            transition: border-color 0.15s, box-shadow 0.15s;
            outline: none;
        }
        .form-group input:focus,
        .form-group select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-glow);
        }
        .form-group select option { background: var(--card); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .waitlist-submit {
            width: 100%;
            padding: 14px;
            font-size: 1rem;
            margin-top: 6px;
            border-radius: var(--radius);
            font-family: inherit;
        }
        .waitlist-note {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 14px;
        }
        /* alerts */
        .alert {
            padding: 12px 18px;
            border-radius: var(--radius);
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 18px;
            display: none;
        }
        .alert.show { display: block; }
        .alert-success { background: rgba(34,197,94,0.12); border: 1px solid rgba(34,197,94,0.3); color: #4ade80; }
        .alert-error   { background: rgba(239,68,68,0.12);  border: 1px solid rgba(239,68,68,0.3);  color: #f87171; }

        /* ─── FOOTER ──────────────────────────────────────────── */
        footer {
            background: #15151f;
            border-top: 1px solid var(--border);
            padding: 36px 0;
            text-align: center;
        }
        footer .logo { justify-content: center; margin-bottom: 12px; }
        footer p { font-size: 0.85rem; color: var(--text-muted); }
        footer a { color: var(--secondary); text-decoration: none; }
        footer a:hover { text-decoration: underline; }

        /* ─── RESPONSIVE ──────────────────────────────────────── */
        @media (max-width: 600px) {
            section { padding: 64px 0; }
            .hero-stats { gap: 24px; }
            .hero-stat-value { font-size: 1.5rem; }
            .form-row { grid-template-columns: 1fr; }
            .waitlist-box { padding: 32px 20px; }
            .nav-link { display: none; }
        }
    </style>
</head>
<body>

<!-- ═══════════════════════════════════════════════ NAVBAR -->
<nav>
    <div class="container">
        <div class="nav-inner">
            <a href="landing.php" class="logo">
                <div class="logo-icon">📋</div>
                AttendTrack
            </a>
            <div class="nav-actions">
                <a href="#features" class="nav-link">Features</a>
                <a href="#how" class="nav-link">How it Works</a>
                <a href="#waitlist" class="nav-link">Join Waitlist</a>
                <a href="landing.php#waitlist" class="btn btn-ghost" style="padding:8px 18px;font-size:0.88rem;">Sign Up</a>
                <a href="login.php" class="btn btn-primary" style="padding:8px 18px;font-size:0.88rem;">Login</a>
            </div>
        </div>
    </div>
</nav>

<!-- ═══════════════════════════════════════════════ HERO -->
<section id="hero">
    <div class="container">
        <div class="hero-badge">
            <span></span> Now in active development &mdash; MVP launching soon
        </div>
        <h1 class="hero-title">
            Smarter Attendance<br>
            for <span class="accent">Modern Institutions</span>
        </h1>
        <p class="hero-subtitle">
            Replace paper registers, messy spreadsheets, and WhatsApp chaos with one elegant
            platform that keeps students, lecturers, and admins perfectly in sync.
        </p>
        <div class="hero-cta">
            <a href="#waitlist" class="btn btn-primary" style="font-size:1rem;padding:14px 32px;">
                Join the Waitlist &rarr;
            </a>
            <a href="#features" class="btn btn-ghost" style="font-size:1rem;padding:14px 32px;">
                See Features
            </a>
        </div>
        <div class="hero-stats">
            <div class="hero-stat">
                <div class="hero-stat-value">3</div>
                <div class="hero-stat-label">User Roles</div>
            </div>
            <div class="hero-stat">
                <div class="hero-stat-value">100%</div>
                <div class="hero-stat-label">Web-Based</div>
            </div>
            <div class="hero-stat">
                <div class="hero-stat-value">PWA</div>
                <div class="hero-stat-label">Works Offline</div>
            </div>
            <div class="hero-stat">
                <div class="hero-stat-value">0 ₦</div>
                <div class="hero-stat-label">Free for Early Access</div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════ PROBLEM -->
<section id="problem">
    <div class="container inner">
        <div class="section-label">The Problem</div>
        <h2 class="section-title">Attendance tracking is still broken</h2>
        <p class="section-subtitle">
            Most institutions still rely on outdated methods that waste time,
            breed errors, and leave students in the dark.
        </p>
        <div class="problem-grid">
            <div class="problem-card">
                <div class="icon">📄</div>
                <h4>Paper Registers</h4>
                <p>Easy to lose, damage, or forge. Counting absences manually takes hours every semester.</p>
            </div>
            <div class="problem-card">
                <div class="icon">📊</div>
                <h4>Spreadsheet Hell</h4>
                <p>Files shared over email, conflicting versions, broken formulas &mdash; no single source of truth.</p>
            </div>
            <div class="problem-card">
                <div class="icon">🔔</div>
                <h4>Zero Visibility</h4>
                <p>Students only discover they'll be barred from exams days before results. No early warnings.</p>
            </div>
            <div class="problem-card solved">
                <div class="icon">✅</div>
                <h4>AttendTrack Fixes This</h4>
                <p>Real-time tracking, instant alerts, beautiful dashboards &mdash; accessible from any device.</p>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════ FEATURES -->
<section id="features">
    <div class="container">
        <div class="section-label">Features</div>
        <h2 class="section-title">Everything you need. Nothing you don't.</h2>
        <p class="section-subtitle">
            Built specifically for educational institutions, with a role-aware system
            that gives each user exactly the tools they need.
        </p>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">⚡</div>
                <h3>One-Click Attendance</h3>
                <p>Lecturers mark an entire class present or absent in seconds. No more calling names one by one.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📈</div>
                <h3>Live Attendance Analytics</h3>
                <p>Per-student, per-course percentage automatically calculated. Spot at-risk students instantly on a dashboard.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🚨</div>
                <h3>Early Warning Alerts</h3>
                <p>Students get browser notifications the moment their attendance drops below the threshold &mdash; weeks before it matters.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📅</div>
                <h3>Smart Timetable</h3>
                <p>A weekly grid view showing all classes, exams, CAs, and assignments at a glance. Navigable by week.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📱</div>
                <h3>Works on Any Device</h3>
                <p>Fully responsive from desktop to the smallest phone. Install as a PWA for an app-like experience &mdash; even offline.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🔒</div>
                <h3>Role-Based Access</h3>
                <p>Admins, lecturers, and students each see only what's relevant to them. Secure, session-based authentication.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📋</div>
                <h3>Detailed Reports</h3>
                <p>Exportable, filterable attendance reports per course, per date range, or per student. Print-ready layouts included.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🗓️</div>
                <h3>Event &amp; Reminder System</h3>
                <p>Lecturers create course events (exams, CAs, assignments) and students get timely reminders with a beep notification.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">👤</div>
                <h3>Complete Admin Control</h3>
                <p>Create accounts, enroll students into courses, assign lecturers, and manage everything from one admin panel.</p>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════ HOW IT WORKS -->
<section id="how">
    <div class="container">
        <div class="section-label">How It Works</div>
        <h2 class="section-title">Up and running in minutes</h2>
        <p class="section-subtitle">
            No complex setup, no training required. Your institution can be fully
            operational in under an hour.
        </p>
        <div class="steps-grid">
            <div class="step">
                <div class="step-number">1</div>
                <h3>Admin Sets Up</h3>
                <p>The admin creates courses, adds lecturer and student accounts, and assigns enrollments &mdash; all from a simple dashboard.</p>
            </div>
            <div class="step">
                <div class="step-number">2</div>
                <h3>Lecturer Marks Attendance</h3>
                <p>After each class, the lecturer opens the course, marks present/absent for each student, and saves &mdash; done in under a minute.</p>
            </div>
            <div class="step">
                <div class="step-number">3</div>
                <h3>Students Stay Informed</h3>
                <p>Students log in anytime to see their attendance percentage, upcoming events, and receive alerts before they fall below threshold.</p>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════ ROLES -->
<section id="roles">
    <div class="container">
        <div class="section-label">Built for Everyone</div>
        <h2 class="section-title">One platform. Three perspectives.</h2>
        <p class="section-subtitle">
            AttendTrack is designed so every user gets a tailored experience.
        </p>
        <div class="roles-grid">
            <div class="role-card">
                <div class="role-header">
                    <span class="role-icon">🛡️</span>
                    <div>
                        <span class="role-badge admin">Admin</span>
                        <h3>Institution Admin</h3>
                    </div>
                </div>
                <ul>
                    <li>Create &amp; manage all user accounts</li>
                    <li>Create courses and assign lecturers</li>
                    <li>Enroll students into courses</li>
                    <li>View system-wide attendance reports</li>
                    <li>Full control over platform configuration</li>
                </ul>
            </div>
            <div class="role-card">
                <div class="role-header">
                    <span class="role-icon">🎓</span>
                    <div>
                        <span class="role-badge lecturer">Lecturer</span>
                        <h3>Lecturer / Tutor</h3>
                    </div>
                </div>
                <ul>
                    <li>Take attendance with one click per class</li>
                    <li>Create course events (exams, CAs, assignments)</li>
                    <li>View per-student attendance trends</li>
                    <li>Manage personal class schedule</li>
                    <li>Configure personal notification preferences</li>
                </ul>
            </div>
            <div class="role-card">
                <div class="role-header">
                    <span class="role-icon">📚</span>
                    <div>
                        <span class="role-badge student">Student</span>
                        <h3>Student</h3>
                    </div>
                </div>
                <ul>
                    <li>View real-time attendance percentage per course</li>
                    <li>Get early warnings before falling below threshold</li>
                    <li>Track upcoming course events and deadlines</li>
                    <li>Manage personal timetable and reminders</li>
                    <li>View full attendance history by date</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════ WAITLIST -->
<section id="waitlist">
    <div class="container">
        <div class="section-label">Early Access</div>
        <h2 class="section-title" style="margin-bottom:10px;">Be the first to know</h2>
        <p class="section-subtitle" style="margin-bottom:44px;">
            We're finalising the product. Join the waitlist to get early access,
            founding-user pricing, and updates straight to your inbox.
        </p>

        <div class="waitlist-box">
            <h2>Join the Waitlist</h2>
            <p class="sub">Secure your early access spot today &mdash; it's free.</p>

            <div class="alert alert-success" id="successMsg">
                🎉 You're on the list! We'll be in touch soon.
            </div>
            <div class="alert alert-error" id="errorMsg"></div>

            <form id="waitlistForm" novalidate>
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name *</label>
                        <input type="text" id="first_name" name="first_name" placeholder="Akila" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" placeholder="Joseph" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" placeholder="you@institution.edu" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number (optional)</label>
                    <input type="tel" id="phone" name="phone" placeholder="+234 800 000 0000">
                </div>
                <div class="form-group">
                    <label for="role">I am a&hellip; *</label>
                    <select id="role" name="role" required>
                        <option value="">Select your role</option>
                        <option value="student">Student</option>
                        <option value="lecturer">Lecturer / Tutor</option>
                        <option value="admin">School Administrator</option>
                        <option value="it_officer">IT Officer</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="institution">Institution Name (optional)</label>
                    <input type="text" id="institution" name="institution" placeholder="University of Lagos">
                </div>
                <button type="submit" class="btn btn-primary waitlist-submit">
                    Get Early Access &rarr;
                </button>
                <p class="waitlist-note">
                    🔒 Your information is safe with us. No spam, ever. Unsubscribe anytime.
                </p>
            </form>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════ FOOTER -->
<footer>
    <div class="container">
        <a href="landing.php" class="logo">
            <div class="logo-icon">📋</div>
            AttendTrack
        </a>
        <p style="margin-top:10px;">
            &copy; <?= date('Y') ?> AttendTrack. Built with purpose for educational institutions.
        </p>
        <p style="margin-top:6px;">
            Already have an account? <a href="login.php">Log in here</a>
        </p>
    </div>
</footer>

<script>
const form = document.getElementById('waitlistForm');
const successMsg = document.getElementById('successMsg');
const errorMsg   = document.getElementById('errorMsg');

form.addEventListener('submit', async (e) => {
    e.preventDefault();
    successMsg.classList.remove('show');
    errorMsg.classList.remove('show');

    const btn = form.querySelector('button[type="submit"]');
    const original = btn.textContent;
    btn.textContent = 'Submitting…';
    btn.disabled = true;

    const data = new FormData(form);

    try {
        const res  = await fetch('actions/waitlist_signup.php', { method: 'POST', body: data });
        const json = await res.json();

        if (json.success) {
            successMsg.classList.add('show');
            form.reset();
        } else {
            errorMsg.textContent = json.message || 'Something went wrong. Please try again.';
            errorMsg.classList.add('show');
        }
    } catch (err) {
        errorMsg.textContent = 'Network error. Please check your connection and try again.';
        errorMsg.classList.add('show');
    } finally {
        btn.textContent = original;
        btn.disabled = false;
    }
});
</script>

</body>
</html>
