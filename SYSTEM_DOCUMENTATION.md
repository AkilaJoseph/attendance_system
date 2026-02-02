# Attendance Management System Documentation

## Complete Technical Documentation

**Project:** Attendance Management System
**Developer:** [Your Name]
**Date:** February 2026

---

## Table of Contents

1. [System Overview](#system-overview)
2. [Technologies Used](#technologies-used)
3. [System Architecture](#system-architecture)
4. [Database Design](#database-design)
5. [User Roles & Access Control](#user-roles--access-control)
6. [Core Features](#core-features)
7. [File Structure](#file-structure)
8. [How Each Technology Works](#how-each-technology-works)
9. [Responsive Design Implementation](#responsive-design-implementation)
10. [Security Measures](#security-measures)
11. [PWA Features](#pwa-features)

---

## System Overview

### What is This System?

The Attendance Management System is a web-based application designed to track and manage student attendance in educational institutions. It provides different interfaces for three types of users:

- **Administrators**: Manage users, courses, and view system-wide reports
- **Lecturers**: Take attendance, manage their courses, and create events
- **Students**: View their attendance records, schedules, and upcoming events

### Problem It Solves

| Traditional Method | This System |
|-------------------|-------------|
| Paper-based attendance sheets | Digital attendance tracking |
| Manual calculation of percentages | Automatic percentage calculation |
| Difficult to identify at-risk students | Automatic low-attendance alerts |
| No student visibility | Students can check their own records |
| Time-consuming reports | Instant report generation |

---

## Technologies Used

### Frontend Technologies

| Technology | Purpose | Files |
|------------|---------|-------|
| **HTML5** | Page structure and content | All `.php` files |
| **CSS3** | Visual styling and layout | `assets/css/style.css` |
| **JavaScript** | Interactivity and dynamic behavior | `assets/js/script.js` |

### Backend Technologies

| Technology | Purpose | Files |
|------------|---------|-------|
| **PHP** | Server-side logic and processing | All `.php` files |
| **MySQL** | Database storage | `database.sql` |

### Additional Technologies

| Technology | Purpose | Files |
|------------|---------|-------|
| **Service Worker** | Offline capability (PWA) | `sw.js` |
| **Web App Manifest** | PWA installation | `manifest.json` |

---

## System Architecture

### How the System Works

```
┌─────────────────────────────────────────────────────────────────┐
│                         USER'S BROWSER                          │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐                      │
│  │   HTML   │  │   CSS    │  │    JS    │                      │
│  │ Structure│  │  Styling │  │ Interact │                      │
│  └────┬─────┘  └────┬─────┘  └────┬─────┘                      │
└───────┼─────────────┼─────────────┼─────────────────────────────┘
        │             │             │
        └─────────────┴─────────────┘
                      │
                      ▼ HTTP Request
┌─────────────────────────────────────────────────────────────────┐
│                      WEB SERVER (XAMPP)                         │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │                     PHP PROCESSOR                         │  │
│  │  - Receives requests                                      │  │
│  │  - Checks session/authentication                          │  │
│  │  - Processes business logic                               │  │
│  │  - Generates HTML response                                │  │
│  └────────────────────────┬─────────────────────────────────┘  │
└───────────────────────────┼─────────────────────────────────────┘
                            │
                            ▼ SQL Queries
┌─────────────────────────────────────────────────────────────────┐
│                      MySQL DATABASE                             │
│  ┌────────┐ ┌────────┐ ┌───────────┐ ┌────────────┐           │
│  │ users  │ │courses │ │attendance │ │enrollments │  ...      │
│  └────────┘ └────────┘ └───────────┘ └────────────┘           │
└─────────────────────────────────────────────────────────────────┘
```

### Request Flow Example

When a lecturer takes attendance:

1. **Browser** sends form data to `actions/take_attendance.php`
2. **PHP** validates the session (is user logged in? is user a lecturer?)
3. **PHP** validates the data (correct course? valid student IDs?)
4. **PHP** sends SQL INSERT queries to the database
5. **MySQL** stores the attendance records
6. **PHP** redirects back with success message
7. **Browser** displays the updated page

---

## Database Design

### Entity Relationship

```
┌──────────────┐       ┌──────────────┐       ┌──────────────┐
│    USERS     │       │   COURSES    │       │  ATTENDANCE  │
├──────────────┤       ├──────────────┤       ├──────────────┤
│ user_id (PK) │──┐    │ course_id(PK)│──┐    │attendance_id │
│ name         │  │    │ course_code  │  │    │ student_id   │──┐
│ email        │  │    │ course_name  │  │    │ course_id    │  │
│ password     │  │    │ lecturer_id  │──┘    │ date         │  │
│ role         │  │    └──────────────┘       │ status       │  │
└──────────────┘  │                           └──────────────┘  │
                  │    ┌──────────────┐                         │
                  │    │ ENROLLMENTS  │                         │
                  │    ├──────────────┤                         │
                  └───►│ student_id   │◄────────────────────────┘
                       │ course_id    │
                       └──────────────┘
```

### Key Tables

| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `users` | Store all user accounts | user_id, name, email, password, role |
| `courses` | Store course information | course_id, course_code, course_name, lecturer_id |
| `enrollments` | Link students to courses | student_id, course_id |
| `attendance` | Store attendance records | student_id, course_id, date, status |
| `events` | Store course events | event_id, course_id, title, event_date, event_type |
| `personal_schedule` | Student personal timetables | schedule_id, user_id, day_of_week, start_time |

---

## User Roles & Access Control

### Role Hierarchy

```
┌─────────────────────────────────────────────────────────┐
│                      ADMIN                               │
│  - Full system access                                    │
│  - Manage all users                                      │
│  - Manage all courses                                    │
│  - View all reports                                      │
└─────────────────────────┬───────────────────────────────┘
                          │
┌─────────────────────────┴───────────────────────────────┐
│                     LECTURER                             │
│  - View assigned courses only                            │
│  - Take attendance for their courses                     │
│  - Create events for their courses                       │
│  - View reports for their students                       │
└─────────────────────────┬───────────────────────────────┘
                          │
┌─────────────────────────┴───────────────────────────────┐
│                      STUDENT                             │
│  - View enrolled courses only                            │
│  - View own attendance records                           │
│  - Manage personal schedule                              │
│  - View course events                                    │
└─────────────────────────────────────────────────────────┘
```

### Access Control Implementation

Each page checks user authentication:

```php
// At the top of every protected page
session_start();

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Check if correct role
if ($_SESSION['role'] != 'lecturer') {
    header("Location: ../index.php");
    exit();
}
```

---

## Core Features

### 1. Attendance Management

**For Lecturers:**
- Select course and date
- Mark students as Present/Absent
- View attendance history
- See class statistics

**For Students:**
- View attendance percentage per course
- See detailed attendance history
- Receive low-attendance warnings

### 2. Course Management (Admin)

- Add new courses
- Assign lecturers to courses
- Enroll students in courses
- Delete courses

### 3. User Management (Admin)

- Create user accounts (admin, lecturer, student)
- Edit user information
- Delete users
- Reset passwords

### 4. Reports & Analytics

- Low attendance alerts (below threshold)
- Course-wise attendance statistics
- Student-wise attendance records
- At-risk student identification

### 5. Events & Schedule

**Lecturers can create:**
- Unit Exams (UE)
- Continuous Assessments (CA)
- Assignments
- Class events

**Students can:**
- View upcoming course events
- Create personal schedule
- Set reminders

### 6. Notification System

- Reminder notifications for events
- Low attendance warnings
- Customizable notification settings

---

## File Structure

```
attendance_system/
│
├── index.php                 # Login page
├── database.sql              # Database schema
├── manifest.json             # PWA manifest
├── sw.js                     # Service Worker
│
├── config/
│   └── db_connect.php        # Database connection
│
├── includes/
│   ├── header.php            # Common header (sidebar, navigation)
│   ├── footer.php            # Common footer
│   └── functions.php         # Reusable PHP functions
│
├── assets/
│   ├── css/
│   │   └── style.css         # All CSS styles
│   └── js/
│       └── script.js         # All JavaScript
│
├── admin/                    # Admin pages
│   ├── dashboard.php
│   ├── users.php
│   ├── courses.php
│   └── reports.php
│
├── lecturer/                 # Lecturer pages
│   ├── dashboard.php
│   ├── take_attendance.php
│   ├── reports.php
│   ├── events.php
│   └── personal_schedule.php
│
├── student/                  # Student pages
│   ├── dashboard.php
│   ├── attendance.php
│   ├── schedule.php
│   ├── timetable.php
│   └── settings.php
│
└── actions/                  # Form processing
    ├── login.php
    ├── logout.php
    ├── take_attendance.php
    ├── save_event.php
    └── ...
```

---

## How Each Technology Works

### PHP - Server-Side Logic

**What PHP Does:**
- Processes form submissions
- Manages user sessions (login/logout)
- Queries the database
- Generates dynamic HTML content
- Handles security (authentication, authorization)

**Example - Getting Attendance Data:**

```php
<?php
// Connect to database
include 'config/db_connect.php';

// Prepare SQL query (prevents SQL injection)
$query = "SELECT * FROM attendance WHERE student_id = ? AND course_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $student_id, $course_id);
$stmt->execute();

// Get results
$result = $stmt->get_result();

// Loop through and display
while ($row = $result->fetch_assoc()) {
    echo $row['date'] . " - " . $row['status'];
}
?>
```

**Key PHP Concepts Used:**
| Concept | Purpose | Example |
|---------|---------|---------|
| Sessions | Track logged-in users | `$_SESSION['user_id']` |
| Prepared Statements | Prevent SQL injection | `$stmt->bind_param()` |
| Include/Require | Reuse code | `include 'header.php'` |
| Arrays | Store data | `$courses = []` |
| Functions | Reusable logic | `getStudentCourses()` |

---

### MySQL - Database

**What MySQL Does:**
- Stores all system data permanently
- Allows complex queries (joins, aggregations)
- Maintains data relationships
- Ensures data integrity

**Example - Calculating Attendance Percentage:**

```sql
SELECT
    u.name as student_name,
    c.course_name,
    COUNT(*) as total_classes,
    SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as attended,
    ROUND(
        (SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) / COUNT(*)) * 100
    ) as percentage
FROM users u
JOIN enrollments e ON u.user_id = e.student_id
JOIN courses c ON e.course_id = c.course_id
LEFT JOIN attendance a ON u.user_id = a.student_id AND c.course_id = a.course_id
WHERE u.user_id = 5
GROUP BY c.course_id;
```

**Key SQL Concepts Used:**
| Concept | Purpose | Example |
|---------|---------|---------|
| JOIN | Combine related tables | `JOIN courses ON...` |
| GROUP BY | Aggregate data | `GROUP BY course_id` |
| CASE WHEN | Conditional logic | `CASE WHEN status='present'` |
| Aggregate Functions | Calculate totals | `COUNT()`, `SUM()`, `ROUND()` |

---

### HTML - Page Structure

**What HTML Does:**
- Defines the structure of each page
- Creates forms for user input
- Displays data in tables and lists
- Provides semantic meaning to content

**Example - Attendance Form:**

```html
<form action="actions/take_attendance.php" method="POST">
    <input type="hidden" name="course_id" value="1">
    <input type="hidden" name="date" value="2026-02-02">

    <table>
        <thead>
            <tr>
                <th>Student</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>John Doe</td>
                <td>
                    <label>
                        <input type="radio" name="status[1]" value="present" checked>
                        Present
                    </label>
                    <label>
                        <input type="radio" name="status[1]" value="absent">
                        Absent
                    </label>
                </td>
            </tr>
        </tbody>
    </table>

    <button type="submit">Save Attendance</button>
</form>
```

---

### CSS - Visual Styling

**What CSS Does:**
- Controls colors, fonts, and spacing
- Creates layouts (sidebar, grid, flexbox)
- Handles responsive design (media queries)
- Adds animations and transitions

**Example - Card Component:**

```css
/* Card base styles */
.card {
    background-color: var(--dark-card);
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
}

/* Card hover effect */
.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 15px rgba(0, 0, 0, 0.4);
}

/* Responsive - stack on mobile */
@media (max-width: 768px) {
    .card {
        padding: 15px;
    }
}
```

**Key CSS Concepts Used:**
| Concept | Purpose | Example |
|---------|---------|---------|
| CSS Variables | Reusable values | `var(--primary-color)` |
| Flexbox | One-dimensional layout | `display: flex` |
| Grid | Two-dimensional layout | `display: grid` |
| Media Queries | Responsive design | `@media (max-width: 768px)` |
| Transitions | Smooth animations | `transition: all 0.3s` |
| Transforms | Move/rotate elements | `transform: translateX()` |

---

### JavaScript - Interactivity

**What JavaScript Does:**
- Handles user interactions (clicks, form submissions)
- Updates page content without reload
- Manages the mobile menu
- Shows notifications
- Validates forms

**Example - Mobile Menu Toggle:**

```javascript
function toggleMobileSidebar() {
    // Get elements
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    // Toggle classes (add if not present, remove if present)
    sidebar.classList.toggle('mobile-open');
    overlay.classList.toggle('active');
}

// When hamburger button is clicked
document.getElementById('hamburgerBtn').addEventListener('click', toggleMobileSidebar);
```

**Key JavaScript Concepts Used:**
| Concept | Purpose | Example |
|---------|---------|---------|
| DOM Manipulation | Change page content | `document.getElementById()` |
| Event Listeners | Respond to user actions | `addEventListener('click')` |
| Class Toggle | Change element state | `classList.toggle()` |
| Fetch API | Make HTTP requests | `fetch('/api/reminders')` |
| JSON | Data exchange format | `JSON.parse()`, `JSON.stringify()` |

---

## Responsive Design Implementation

### What is Responsive Design?

Responsive design makes the website work well on all screen sizes - from large desktop monitors to small phone screens.

### Breakpoints Used

| Breakpoint | Target | Layout Changes |
|------------|--------|----------------|
| > 1024px | Desktop | Full sidebar (250px), table layouts |
| 769px - 1024px | Tablet | Collapsed sidebar (icons only) |
| ≤ 768px | Mobile | Hidden sidebar with hamburger menu |
| ≤ 480px | Small phones | Single column, larger touch targets |

### Mobile Navigation

On mobile devices, the sidebar is hidden and accessed via a hamburger menu:

```
Desktop:                          Mobile:
┌────────┬──────────────┐        ┌──────────────────┐
│        │              │        │ ☰  Attendance    │ <- Fixed header
│ Side   │   Content    │        ├──────────────────┤
│ bar    │              │        │                  │
│        │              │        │    Content       │
│        │              │        │                  │
└────────┴──────────────┘        └──────────────────┘

When hamburger (☰) clicked:
┌──────────────────┐
│ ╳  Menu          │ <- Sidebar slides in
├──────────────────┤
│ Dashboard        │
│ Attendance       │
│ Schedule         │
│ ...              │
└──────────────────┘
```

### Table to Card Conversion

Tables are difficult to read on mobile, so they convert to cards:

```
Desktop Table:                    Mobile Cards:
┌────┬────────┬────────┐         ┌──────────────────┐
│ ID │ Name   │ Email  │         │ John Doe         │
├────┼────────┼────────┤         │ john@email.com   │
│ 1  │ John   │ j@...  │         │ [Edit] [Delete]  │
│ 2  │ Jane   │ ja@... │         └──────────────────┘
└────┴────────┴────────┘         ┌──────────────────┐
                                 │ Jane Smith       │
                                 │ jane@email.com   │
                                 │ [Edit] [Delete]  │
                                 └──────────────────┘
```

---

## Security Measures

### 1. Password Hashing

Passwords are never stored in plain text:

```php
// When creating user
$hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);

// When verifying login
if (password_verify($input_password, $stored_hash)) {
    // Login successful
}
```

### 2. SQL Injection Prevention

Using prepared statements instead of string concatenation:

```php
// UNSAFE - Don't do this
$query = "SELECT * FROM users WHERE id = " . $_GET['id'];

// SAFE - Always do this
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $_GET['id']);
```

### 3. XSS Prevention

Escaping output with `htmlspecialchars()`:

```php
// UNSAFE
echo $user['name'];

// SAFE
echo htmlspecialchars($user['name']);
```

### 4. Session Security

- Sessions regenerated on login
- Session timeout after inactivity
- Role verification on each page

---

## PWA Features

### What is a PWA?

A Progressive Web App (PWA) allows the website to be installed on devices and work offline like a native app.

### Files Involved

| File | Purpose |
|------|---------|
| `manifest.json` | App metadata (name, icon, colors) |
| `sw.js` | Service Worker (caching, offline support) |

### Manifest Configuration

```json
{
    "name": "Attendance Management System",
    "short_name": "Attendance",
    "start_url": "/attendance_system/",
    "display": "standalone",
    "theme_color": "#4f46e5",
    "background_color": "#1e1e2e",
    "icons": [...]
}
```

### Service Worker

The service worker caches static assets for offline use:

```javascript
// Cache static files
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open('attendance-v1').then(cache => {
            return cache.addAll([
                '/attendance_system/',
                '/attendance_system/assets/css/style.css',
                '/attendance_system/assets/js/script.js'
            ]);
        })
    );
});
```

---

## Summary

### Technology Responsibilities

| Technology | What It Does |
|------------|--------------|
| **PHP** | Server-side logic, database queries, security, session management |
| **MySQL** | Data storage, relationships, complex queries |
| **HTML** | Page structure, forms, semantic content |
| **CSS** | Visual styling, layouts, responsive design, animations |
| **JavaScript** | User interactions, dynamic updates, mobile menu, notifications |

### How They Work Together

1. **User requests a page** → PHP processes the request
2. **PHP queries MySQL** → Gets required data
3. **PHP generates HTML** → Includes the data in the page
4. **Browser receives HTML** → Renders the structure
5. **CSS is applied** → Page is styled
6. **JavaScript runs** → Adds interactivity

This collaboration creates a complete, functional web application that manages attendance efficiently across all devices.

---

## Conclusion

The Attendance Management System demonstrates how multiple web technologies work together to create a functional, secure, and responsive application. Each technology has a specific role:

- **PHP** handles all the server-side logic and security
- **MySQL** stores and retrieves data efficiently
- **HTML** provides the structural foundation
- **CSS** makes everything visually appealing and responsive
- **JavaScript** adds the interactive elements that improve user experience

Understanding how these technologies interact is fundamental to web development and building modern web applications.
