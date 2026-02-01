<?php
session_start();
header('Content-Type: application/json');
include '../config/db_connect.php';
include '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    // Fallback to POST data
    $input = $_POST;
}

$notification_type = $input['notification_type'] ?? null;
$is_enabled = isset($input['is_enabled']) ? (bool)$input['is_enabled'] : true;
$remind_before = isset($input['remind_before']) ? intval($input['remind_before']) : 1;
$remind_unit = $input['remind_unit'] ?? 'days';

// Validate notification type
$valid_types = [
    'class_reminder', 'ca_reminder', 'ue_reminder', 'assignment_reminder',
    'event_reminder', 'attendance_alert', 'low_attendance_warning', 'course_announcement'
];

if (!in_array($notification_type, $valid_types)) {
    echo json_encode(['success' => false, 'error' => 'Invalid notification type']);
    exit();
}

// Validate remind_before
if ($remind_before < 1) $remind_before = 1;
if ($remind_before > 365) $remind_before = 365;

// Validate remind_unit
if (!in_array($remind_unit, ['hours', 'days'])) {
    $remind_unit = 'days';
}

try {
    $result = updateNotificationSetting($conn, $user_id, $notification_type, $is_enabled, $remind_before, $remind_unit);

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Setting updated successfully',
            'setting' => [
                'notification_type' => $notification_type,
                'is_enabled' => $is_enabled,
                'remind_before' => $remind_before,
                'remind_unit' => $remind_unit
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update setting']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>
