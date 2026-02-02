<?php
/**
 * Reusable PHP Functions for Attendance System
 */

/**
 * Calculate attendance percentage for a student in a course
 */
function calculateAttendancePercentage($conn, $student_id, $course_id) {
    // Get total classes held for this course
    $total_query = "SELECT COUNT(DISTINCT date) as total FROM attendance WHERE course_id = $course_id";
    $total_result = $conn->query($total_query)->fetch_assoc();
    $total_classes = $total_result['total'];

    // Get classes attended by this student
    $present_query = "SELECT COUNT(*) as present FROM attendance WHERE course_id = $course_id AND student_id = $student_id AND status = 'present'";
    $present_result = $conn->query($present_query)->fetch_assoc();
    $classes_attended = $present_result['present'];

    // Calculate percentage
    $percentage = ($total_classes > 0) ? round(($classes_attended / $total_classes) * 100) : 100;

    return [
        'percentage' => $percentage,
        'total_classes' => $total_classes,
        'classes_attended' => $classes_attended
    ];
}

/**
 * Check if a student has low attendance (below threshold)
 */
function isLowAttendance($percentage, $threshold = 75) {
    return $percentage < $threshold;
}

/**
 * Get all students enrolled in a course
 */
function getEnrolledStudents($conn, $course_id) {
    $query = "SELECT u.user_id, u.name, u.email
              FROM users u
              JOIN enrollments e ON u.user_id = e.student_id
              WHERE e.course_id = $course_id
              ORDER BY u.name";

    return $conn->query($query);
}

/**
 * Get courses taught by a lecturer
 */
function getLecturerCourses($conn, $lecturer_id) {
    $hasColor = coursesHasColumn($conn, 'color');
    $colorSelect = $hasColor ? 'color' : 'NULL AS color';
    $lecturer_id = (int)$lecturer_id;
    $query = "SELECT course_id, course_name, course_code, {$colorSelect} FROM courses WHERE lecturer_id = {$lecturer_id}";

    return $conn->query($query);
}

/**
 * Get courses a student is enrolled in
 */
function getStudentCourses($conn, $student_id) {
    $hasColor = coursesHasColumn($conn, 'color');
    $colorSelect = $hasColor ? 'c.color' : 'NULL AS color';
    $student_id = (int)$student_id;
    $query = "SELECT c.course_id, c.course_name, c.course_code, {$colorSelect}
              FROM courses c
              JOIN enrollments e ON c.course_id = e.course_id
              WHERE e.student_id = {$student_id}";

    return $conn->query($query);
}

/**
 * Sanitize user input
 */
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Display flash message if exists
 */
function displayFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $type = $_SESSION['flash_type'] ?? 'success';
        $class = ($type == 'error') ? 'danger' : (($type == 'warning') ? 'warning' : 'good');

        echo '<div class="alert ' . $class . '">' . htmlspecialchars($_SESSION['flash_message']) . '</div>';

        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
    }
}

/**
 * Check if `courses` table has a specific column (cached)
 */
function coursesHasColumn($conn, $column = 'color') {
    static $cache = [];
    if (isset($cache[$column])) return $cache[$column];

    $sql = "SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'courses' AND COLUMN_NAME = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $cache[$column] = false;
        return false;
    }
    $stmt->bind_param('s', $column);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $exists = ($res && $res['cnt'] > 0);
    $cache[$column] = $exists;
    return $exists;
}

/**
 * Get upcoming events for a user (personal + course events they're enrolled in)
 */
function getUpcomingEvents($conn, $user_id, $days = 30) {
    $query = "SELECT e.*, c.course_name, c.course_code
              FROM events e
              LEFT JOIN courses c ON e.course_id = c.course_id
              WHERE (e.user_id = ? OR (e.is_course_event = TRUE AND e.course_id IN (
                  SELECT course_id FROM enrollments WHERE student_id = ?
              )))
              AND e.event_date >= CURDATE()
              AND e.event_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
              ORDER BY e.event_date, e.event_time";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("iii", $user_id, $user_id, $days);
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Get all events for a user (for timetable view)
 */
function getUserEvents($conn, $user_id) {
    $query = "SELECT e.*, c.course_name, c.course_code
              FROM events e
              LEFT JOIN courses c ON e.course_id = c.course_id
              WHERE e.user_id = ? OR (e.is_course_event = TRUE AND e.course_id IN (
                  SELECT course_id FROM enrollments WHERE student_id = ?
              ))
              ORDER BY e.event_date DESC, e.event_time";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Get active reminders that should fire now
 */
function getActiveReminders($conn, $user_id) {
    $query = "SELECT r.*, e.title, e.event_type, e.event_date, e.event_time, e.description
              FROM reminders r
              JOIN events e ON r.event_id = e.event_id
              WHERE r.user_id = ?
              AND r.is_seen = FALSE
              AND e.event_date >= CURDATE()
              AND (
                  (r.remind_unit = 'days' AND DATE_SUB(e.event_date, INTERVAL r.remind_before DAY) <= CURDATE())
                  OR
                  (r.remind_unit = 'hours' AND DATE_SUB(CONCAT(e.event_date, ' ', COALESCE(e.event_time, '08:00:00')), INTERVAL r.remind_before HOUR) <= NOW())
              )
              ORDER BY e.event_date, e.event_time";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Get count of unseen reminders (for badge)
 */
function getReminderCount($conn, $user_id) {
    $query = "SELECT COUNT(*) as cnt
              FROM reminders r
              JOIN events e ON r.event_id = e.event_id
              WHERE r.user_id = ?
              AND r.is_seen = FALSE
              AND e.event_date >= CURDATE()
              AND (
                  (r.remind_unit = 'days' AND DATE_SUB(e.event_date, INTERVAL r.remind_before DAY) <= CURDATE())
                  OR
                  (r.remind_unit = 'hours' AND DATE_SUB(CONCAT(e.event_date, ' ', COALESCE(e.event_time, '08:00:00')), INTERVAL r.remind_before HOUR) <= NOW())
              )";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['cnt'];
}

/**
 * Add a new event
 */
function addEvent($conn, $user_id, $title, $event_type, $event_date, $event_time, $description, $course_id = null, $is_course_event = false) {
    $query = "INSERT INTO events (user_id, course_id, title, event_type, event_date, event_time, description, is_course_event)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($query);
    $is_course = $is_course_event ? 1 : 0;
    $stmt->bind_param("iisssssi", $user_id, $course_id, $title, $event_type, $event_date, $event_time, $description, $is_course);

    if ($stmt->execute()) {
        return $conn->insert_id;
    }
    return false;
}

/**
 * Add a reminder for an event
 */
function addReminder($conn, $event_id, $user_id, $remind_before, $remind_unit) {
    $query = "INSERT INTO reminders (event_id, user_id, remind_before, remind_unit)
              VALUES (?, ?, ?, ?)";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("iiis", $event_id, $user_id, $remind_before, $remind_unit);

    return $stmt->execute();
}

/**
 * Get time until event (human readable)
 */
function getTimeUntilEvent($event_date, $event_time = null) {
    $event_datetime = $event_time ? "$event_date $event_time" : "$event_date 00:00:00";
    $event = new DateTime($event_datetime);
    $now = new DateTime();

    $diff = $now->diff($event);

    if ($diff->invert) {
        return "Past";
    }

    if ($diff->days == 0) {
        if ($diff->h > 0) {
            return $diff->h . " hour" . ($diff->h > 1 ? "s" : "");
        }
        return "Today";
    } elseif ($diff->days == 1) {
        return "Tomorrow";
    } else {
        return $diff->days . " days";
    }
}

/**
 * Get event type label with color class
 */
function getEventTypeClass($type) {
    $classes = [
        'class' => 'event-type-class',
        'ue' => 'event-type-ue',
        'ca' => 'event-type-ca',
        'assignment' => 'event-type-assignment',
        'other' => 'event-type-other'
    ];
    return $classes[$type] ?? 'event-type-other';
}

/**
 * Get event type display name
 */
function getEventTypeName($type) {
    $names = [
        'class' => 'Class',
        'ue' => 'Unit Exam',
        'ca' => 'Continuous Assessment',
        'assignment' => 'Assignment',
        'other' => 'Other'
    ];
    return $names[$type] ?? 'Event';
}

// ============================================================
// FEATURE 1: TEACHER STATISTICS FUNCTIONS
// ============================================================

/**
 * Get attendance overview for all courses taught by a lecturer
 */
function getLecturerAttendanceOverview($conn, $lecturer_id) {
    $query = "SELECT
                c.course_id,
                c.course_name,
                c.course_code,
                COUNT(DISTINCT e.student_id) as total_students,
                COUNT(DISTINCT a.date) as total_classes,
                ROUND(AVG(
                    CASE
                        WHEN sub.total > 0 THEN (sub.present / sub.total) * 100
                        ELSE 100
                    END
                ), 1) as avg_attendance
              FROM courses c
              LEFT JOIN enrollments e ON c.course_id = e.course_id
              LEFT JOIN attendance a ON c.course_id = a.course_id
              LEFT JOIN (
                  SELECT
                      student_id,
                      course_id,
                      COUNT(DISTINCT date) as total,
                      SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present
                  FROM attendance
                  GROUP BY student_id, course_id
              ) sub ON e.student_id = sub.student_id AND c.course_id = sub.course_id
              WHERE c.lecturer_id = ?
              GROUP BY c.course_id";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $lecturer_id);
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Get total at-risk students across all lecturer's courses
 */
function getTotalAtRiskStudents($conn, $lecturer_id, $threshold = 75) {
    $query = "SELECT COUNT(DISTINCT combined.student_id) as at_risk_count
              FROM (
                  SELECT
                      e.student_id,
                      e.course_id,
                      CASE
                          WHEN COUNT(DISTINCT a.date) > 0
                          THEN (SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) / COUNT(DISTINCT a.date)) * 100
                          ELSE 100
                      END as percentage
                  FROM enrollments e
                  JOIN courses c ON e.course_id = c.course_id
                  LEFT JOIN attendance a ON e.student_id = a.student_id AND e.course_id = a.course_id
                  WHERE c.lecturer_id = ?
                  GROUP BY e.student_id, e.course_id
                  HAVING percentage < ?
              ) combined";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("id", $lecturer_id, $threshold);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['at_risk_count'] ?? 0;
}

/**
 * Get at-risk student count for a specific course
 */
function getCourseAtRiskCount($conn, $course_id, $threshold = 75) {
    $query = "SELECT COUNT(*) as at_risk
              FROM (
                  SELECT
                      e.student_id,
                      CASE
                          WHEN COUNT(DISTINCT a.date) > 0
                          THEN (SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) / COUNT(DISTINCT a.date)) * 100
                          ELSE 100
                      END as percentage
                  FROM enrollments e
                  LEFT JOIN attendance a ON e.student_id = a.student_id AND e.course_id = a.course_id
                  WHERE e.course_id = ?
                  GROUP BY e.student_id
                  HAVING percentage < ?
              ) sub";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("id", $course_id, $threshold);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['at_risk'] ?? 0;
}

/**
 * Calculate attendance trend for a student in a course
 * Returns: 'improving', 'declining', 'stable', or 'new'
 */
function calculateAttendanceTrend($conn, $student_id, $course_id, $weeks = 4) {
    $query = "SELECT percentage, snapshot_date
              FROM attendance_snapshots
              WHERE student_id = ? AND course_id = ?
              AND snapshot_date >= DATE_SUB(CURDATE(), INTERVAL ? WEEK)
              ORDER BY snapshot_date ASC";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("iii", $student_id, $course_id, $weeks);
    $stmt->execute();
    $result = $stmt->get_result();

    $snapshots = [];
    while ($row = $result->fetch_assoc()) {
        $snapshots[] = $row['percentage'];
    }

    if (count($snapshots) < 2) {
        return 'new';
    }

    $first_half = array_slice($snapshots, 0, ceil(count($snapshots) / 2));
    $second_half = array_slice($snapshots, ceil(count($snapshots) / 2));

    $first_avg = array_sum($first_half) / count($first_half);
    $second_avg = array_sum($second_half) / count($second_half);

    $diff = $second_avg - $first_avg;

    if ($diff > 5) {
        return 'improving';
    } elseif ($diff < -5) {
        return 'declining';
    }
    return 'stable';
}

/**
 * Record attendance snapshot (call after marking attendance)
 */
function recordAttendanceSnapshot($conn, $student_id, $course_id) {
    $attendance = calculateAttendancePercentage($conn, $student_id, $course_id);
    $percentage = $attendance['percentage'];

    $query = "INSERT INTO attendance_snapshots (student_id, course_id, percentage, snapshot_date)
              VALUES (?, ?, ?, CURDATE())
              ON DUPLICATE KEY UPDATE percentage = ?, created_at = CURRENT_TIMESTAMP";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("iidd", $student_id, $course_id, $percentage, $percentage);
    return $stmt->execute();
}

/**
 * Get statistics for a single course
 */
function getCourseStatistics($conn, $course_id) {
    $query = "SELECT
                c.course_id,
                c.course_name,
                c.course_code,
                COUNT(DISTINCT e.student_id) as total_students,
                COUNT(DISTINCT a.date) as total_classes
              FROM courses c
              LEFT JOIN enrollments e ON c.course_id = e.course_id
              LEFT JOIN attendance a ON c.course_id = a.course_id
              WHERE c.course_id = ?
              GROUP BY c.course_id";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();

    $stats['at_risk_count'] = getCourseAtRiskCount($conn, $course_id);

    // Calculate average attendance
    $avg_query = "SELECT ROUND(AVG(
                      CASE
                          WHEN sub.total > 0 THEN (sub.present / sub.total) * 100
                          ELSE 100
                      END
                  ), 1) as avg_attendance
                  FROM enrollments e
                  LEFT JOIN (
                      SELECT
                          student_id,
                          course_id,
                          COUNT(DISTINCT date) as total,
                          SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present
                      FROM attendance
                      WHERE course_id = ?
                      GROUP BY student_id, course_id
                  ) sub ON e.student_id = sub.student_id
                  WHERE e.course_id = ?";

    $avg_stmt = $conn->prepare($avg_query);
    $avg_stmt->bind_param("ii", $course_id, $course_id);
    $avg_stmt->execute();
    $avg_result = $avg_stmt->get_result()->fetch_assoc();
    $stats['avg_attendance'] = $avg_result['avg_attendance'] ?? 100;

    return $stats;
}

// ============================================================
// FEATURE 2: NOTIFICATION SETTINGS FUNCTIONS
// ============================================================

/**
 * Get all notification settings for a user
 */
function getUserNotificationSettings($conn, $user_id) {
    $query = "SELECT * FROM user_notification_settings WHERE user_id = ? ORDER BY notification_type";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Update a specific notification setting
 */
function updateNotificationSetting($conn, $user_id, $type, $is_enabled, $remind_before, $remind_unit) {
    $query = "INSERT INTO user_notification_settings
              (user_id, notification_type, is_enabled, default_remind_before, default_remind_unit)
              VALUES (?, ?, ?, ?, ?)
              ON DUPLICATE KEY UPDATE
              is_enabled = VALUES(is_enabled),
              default_remind_before = VALUES(default_remind_before),
              default_remind_unit = VALUES(default_remind_unit)";

    $stmt = $conn->prepare($query);
    $enabled = $is_enabled ? 1 : 0;
    $stmt->bind_param("issis", $user_id, $type, $enabled, $remind_before, $remind_unit);
    return $stmt->execute();
}

/**
 * Initialize default notification settings for a new user
 */
function initializeUserNotificationSettings($conn, $user_id, $role) {
    $types = [
        'class_reminder' => ['before' => 1, 'unit' => 'hours'],
        'ca_reminder' => ['before' => 3, 'unit' => 'days'],
        'ue_reminder' => ['before' => 7, 'unit' => 'days'],
        'assignment_reminder' => ['before' => 2, 'unit' => 'days'],
        'event_reminder' => ['before' => 1, 'unit' => 'days'],
        'attendance_alert' => ['before' => 1, 'unit' => 'hours'],
        'low_attendance_warning' => ['before' => 1, 'unit' => 'days'],
        'course_announcement' => ['before' => 1, 'unit' => 'hours']
    ];

    // Lecturers only need some notification types
    if ($role === 'lecturer') {
        $types = [
            'class_reminder' => ['before' => 30, 'unit' => 'hours'],
            'event_reminder' => ['before' => 1, 'unit' => 'days'],
            'course_announcement' => ['before' => 1, 'unit' => 'hours']
        ];
    }

    foreach ($types as $type => $defaults) {
        updateNotificationSetting($conn, $user_id, $type, true, $defaults['before'], $defaults['unit']);
    }

    return true;
}

/**
 * Check if a notification type is enabled for a user
 */
function isNotificationEnabled($conn, $user_id, $notification_type) {
    $query = "SELECT is_enabled FROM user_notification_settings
              WHERE user_id = ? AND notification_type = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $user_id, $notification_type);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    return $result ? (bool)$result['is_enabled'] : true; // Default to enabled if not set
}

/**
 * Get default reminder settings for a notification type
 */
function getDefaultReminderSettings($conn, $user_id, $notification_type) {
    $query = "SELECT default_remind_before, default_remind_unit
              FROM user_notification_settings
              WHERE user_id = ? AND notification_type = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $user_id, $notification_type);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if ($result) {
        return [
            'remind_before' => $result['default_remind_before'],
            'remind_unit' => $result['default_remind_unit']
        ];
    }

    // Default values if not set
    return ['remind_before' => 1, 'remind_unit' => 'days'];
}

/**
 * Get notification type display name
 */
function getNotificationTypeName($type) {
    $names = [
        'class_reminder' => 'Class Reminders',
        'ca_reminder' => 'CA Reminders',
        'ue_reminder' => 'Unit Exam Reminders',
        'assignment_reminder' => 'Assignment Reminders',
        'event_reminder' => 'Event Reminders',
        'attendance_alert' => 'Attendance Alerts',
        'low_attendance_warning' => 'Low Attendance Warnings',
        'course_announcement' => 'Course Announcements'
    ];
    return $names[$type] ?? $type;
}

/**
 * Get notification type description
 */
function getNotificationTypeDescription($type) {
    $descriptions = [
        'class_reminder' => 'Reminders before scheduled classes',
        'ca_reminder' => 'Reminders before continuous assessments',
        'ue_reminder' => 'Reminders before unit exams',
        'assignment_reminder' => 'Reminders before assignment deadlines',
        'event_reminder' => 'Reminders for general events',
        'attendance_alert' => 'Alerts when your attendance is marked',
        'low_attendance_warning' => 'Warnings when attendance falls below 75%',
        'course_announcement' => 'Announcements from lecturers'
    ];
    return $descriptions[$type] ?? '';
}

// ============================================================
// FEATURE 3: TIMETABLE GRID FUNCTIONS
// ============================================================

/**
 * Get weekly class schedule for a student (from enrolled courses)
 */
function getStudentWeeklySchedule($conn, $student_id) {
    $hasColor = coursesHasColumn($conn, 'color');
    $colorSelect = $hasColor ? 'c.color' : 'NULL AS color';

    $query = "SELECT cs.*, c.course_name, c.course_code, {$colorSelect}
              FROM class_schedule cs
              JOIN courses c ON cs.course_id = c.course_id
              JOIN enrollments e ON c.course_id = e.course_id
              WHERE e.student_id = ? AND cs.is_active = TRUE
              ORDER BY FIELD(cs.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'), cs.start_time";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Get one-time events for a specific week
 */
function getWeekEvents($conn, $user_id, $week_start) {
    $week_end = date('Y-m-d', strtotime($week_start . ' +6 days'));

    $query = "SELECT e.*, c.course_name, c.course_code
              FROM events e
              LEFT JOIN courses c ON e.course_id = c.course_id
              WHERE (e.user_id = ? OR (e.is_course_event = TRUE AND e.course_id IN (
                  SELECT course_id FROM enrollments WHERE student_id = ?
              )))
              AND e.event_date BETWEEN ? AND ?
              ORDER BY e.event_date, e.event_time";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("iiss", $user_id, $user_id, $week_start, $week_end);
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Build timetable grid data structure
 * Merges recurring schedule with one-time events
 */
function buildTimetableGrid($conn, $user_id, $week_start, $role = 'student') {
    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    $time_slots = [];

    // Generate time slots from 7 AM to 9 PM
    for ($hour = 7; $hour <= 21; $hour++) {
        $time_slots[] = sprintf('%02d:00', $hour);
    }

    // Initialize grid
    $grid = [];
    foreach ($days as $day) {
        $grid[$day] = [];
        foreach ($time_slots as $slot) {
            $grid[$day][$slot] = [];
        }
    }

    // Get recurring schedule
    if ($role === 'student') {
        $schedule = getStudentWeeklySchedule($conn, $user_id);
    } else {
        // For lecturer, get schedule for all their courses
        $schedule = getLecturerWeeklySchedule($conn, $user_id);
    }

    while ($class = $schedule->fetch_assoc()) {
        $start_hour = (int)date('H', strtotime($class['start_time']));
        $slot_key = sprintf('%02d:00', $start_hour);

        if (isset($grid[$class['day_of_week']][$slot_key])) {
            $grid[$class['day_of_week']][$slot_key][] = [
                'type' => 'recurring',
                'schedule_type' => $class['schedule_type'],
                'course_code' => $class['course_code'],
                'course_name' => $class['course_name'],
                'course_color' => $class['color'] ?? null,
                'start_time' => $class['start_time'],
                'end_time' => $class['end_time'],
                'room' => $class['room'],
                'schedule_id' => $class['schedule_id']
            ];
        }
    }

    // Get one-time events for the week
    $events = getWeekEvents($conn, $user_id, $week_start);

    while ($event = $events->fetch_assoc()) {
        $day_name = date('l', strtotime($event['event_date']));
        if (!in_array($day_name, $days)) continue;

        $event_hour = $event['event_time'] ? (int)date('H', strtotime($event['event_time'])) : 8;
        $slot_key = sprintf('%02d:00', $event_hour);

        if (isset($grid[$day_name][$slot_key])) {
            $grid[$day_name][$slot_key][] = [
                'type' => 'event',
                'event_type' => $event['event_type'],
                'title' => $event['title'],
                'course_code' => $event['course_code'],
                'event_date' => $event['event_date'],
                'event_time' => $event['event_time'],
                'description' => $event['description'],
                'event_id' => $event['event_id']
            ];
        }
    }

    // Get personal schedule items
    $personal = getPersonalSchedule($conn, $user_id);

    while ($item = $personal->fetch_assoc()) {
        $start_hour = (int)date('H', strtotime($item['start_time']));
        $slot_key = sprintf('%02d:00', $start_hour);

            if (isset($grid[$item['day_of_week']][$slot_key])) {
            $grid[$item['day_of_week']][$slot_key][] = [
                'type' => 'personal',
                'schedule_type' => $item['schedule_type'],
                'title' => $item['title'],
                'start_time' => $item['start_time'],
                'end_time' => $item['end_time'],
                'location' => $item['location'],
                'description' => $item['description'],
                'schedule_id' => $item['schedule_id'],
                'course_id' => $item['course_id'] ?? null,
                'course_code' => $item['course_code'] ?? null,
                'course_name' => $item['course_name'] ?? null,
                'course_color' => $item['color'] ?? null
            ];
        }
    }

    return [
        'days' => $days,
        'time_slots' => $time_slots,
        'grid' => $grid,
        'week_start' => $week_start,
        'week_end' => date('Y-m-d', strtotime($week_start . ' +6 days'))
    ];
}

/**
 * Get weekly schedule for lecturer's courses
 */
function getLecturerWeeklySchedule($conn, $lecturer_id) {
    $hasColor = coursesHasColumn($conn, 'color');
    $colorSelect = $hasColor ? 'c.color' : 'NULL AS color';

    $query = "SELECT cs.*, c.course_name, c.course_code, {$colorSelect}
              FROM class_schedule cs
              JOIN courses c ON cs.course_id = c.course_id
              WHERE c.lecturer_id = ? AND cs.is_active = TRUE
              ORDER BY FIELD(cs.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'), cs.start_time";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $lecturer_id);
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Get class schedule for a specific course
 */
function getCourseSchedule($conn, $course_id) {
    $query = "SELECT * FROM class_schedule
              WHERE course_id = ? AND is_active = TRUE
              ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'), start_time";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Save a schedule slot (insert or update)
 */
function saveScheduleSlot($conn, $course_id, $day_of_week, $start_time, $end_time, $room, $schedule_type, $schedule_id = null) {
    if ($schedule_id) {
        // Update existing
        $query = "UPDATE class_schedule
                  SET day_of_week = ?, start_time = ?, end_time = ?, room = ?, schedule_type = ?
                  WHERE schedule_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssi", $day_of_week, $start_time, $end_time, $room, $schedule_type, $schedule_id);
    } else {
        // Insert new
        $query = "INSERT INTO class_schedule (course_id, day_of_week, start_time, end_time, room, schedule_type)
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("isssss", $course_id, $day_of_week, $start_time, $end_time, $room, $schedule_type);
    }

    return $stmt->execute();
}

/**
 * Delete a schedule slot
 */
function deleteScheduleSlot($conn, $schedule_id) {
    $query = "DELETE FROM class_schedule WHERE schedule_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $schedule_id);
    return $stmt->execute();
}

/**
 * Get schedule type display name
 */
function getScheduleTypeName($type) {
    $names = [
        'lecture' => 'Lecture',
        'tutorial' => 'Tutorial',
        'lab' => 'Lab',
        'other' => 'Other'
    ];
    return $names[$type] ?? 'Class';
}

/**
 * Get schedule type CSS class
 */
function getScheduleTypeClass($type) {
    $classes = [
        'lecture' => 'schedule-type-lecture',
        'tutorial' => 'schedule-type-tutorial',
        'lab' => 'schedule-type-lab',
        'other' => 'schedule-type-other'
    ];
    return $classes[$type] ?? 'schedule-type-other';
}

/**
 * Get week start date (Monday) for a given date
 */
function getWeekStart($date = null) {
    $date = $date ?? date('Y-m-d');
    $timestamp = strtotime($date);
    $day_of_week = date('N', $timestamp); // 1 (Monday) to 7 (Sunday)
    $monday = strtotime('-' . ($day_of_week - 1) . ' days', $timestamp);
    return date('Y-m-d', $monday);
}

// ============================================================
// PERSONAL SCHEDULE FUNCTIONS (For Students & Lecturers)
// ============================================================

/**
 * Get personal schedule for a user
 */
function getPersonalSchedule($conn, $user_id) {
    $hasColor = coursesHasColumn($conn, 'color');
    $colorSelect = $hasColor ? 'c.color' : 'NULL AS color';

    $query = "SELECT ps.*, c.course_name, c.course_code, {$colorSelect}
              FROM personal_schedule ps
              LEFT JOIN courses c ON ps.course_id = c.course_id
              WHERE ps.user_id = ? AND ps.is_active = TRUE
              ORDER BY FIELD(ps.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'), ps.start_time";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Save a personal schedule slot (insert or update)
 */
function savePersonalScheduleSlot($conn, $user_id, $title, $schedule_type, $day_of_week, $start_time, $end_time, $location, $description, $course_id = null, $schedule_id = null) {
    if ($schedule_id) {
        // Update existing
        $query = "UPDATE personal_schedule
                  SET title = ?, schedule_type = ?, day_of_week = ?, start_time = ?, end_time = ?, location = ?, description = ?, course_id = ?
                  WHERE schedule_id = ? AND user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssssiii", $title, $schedule_type, $day_of_week, $start_time, $end_time, $location, $description, $course_id, $schedule_id, $user_id);
    } else {
        // Insert new
        $query = "INSERT INTO personal_schedule (user_id, course_id, title, schedule_type, day_of_week, start_time, end_time, location, description)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iisssssss", $user_id, $course_id, $title, $schedule_type, $day_of_week, $start_time, $end_time, $location, $description);
    }

    return $stmt->execute();
}

/**
 * Delete a personal schedule slot
 */
function deletePersonalScheduleSlot($conn, $schedule_id, $user_id) {
    $query = "DELETE FROM personal_schedule WHERE schedule_id = ? AND user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $schedule_id, $user_id);
    return $stmt->execute();
}

/**
 * Get personal schedule type display name
 */
function getPersonalScheduleTypeName($type) {
    $names = [
        'class' => 'Class',
        'ue' => 'Unit Exam',
        'ca' => 'CA',
        'assignment' => 'Assignment',
        'study' => 'Study Time',
        'personal' => 'Personal',
        'other' => 'Other'
    ];
    return $names[$type] ?? 'Event';
}

/**
 * Get personal schedule type CSS class
 */
function getPersonalScheduleTypeClass($type) {
    $classes = [
        'class' => 'personal-type-class',
        'ue' => 'personal-type-ue',
        'ca' => 'personal-type-ca',
        'assignment' => 'personal-type-assignment',
        'study' => 'personal-type-study',
        'personal' => 'personal-type-personal',
        'other' => 'personal-type-other'
    ];
    return $classes[$type] ?? 'personal-type-other';
}
?>
