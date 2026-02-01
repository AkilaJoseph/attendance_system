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

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

try {
    $settings_result = getUserNotificationSettings($conn, $user_id);
    $settings = [];

    while ($row = $settings_result->fetch_assoc()) {
        $settings[$row['notification_type']] = [
            'setting_id' => $row['setting_id'],
            'notification_type' => $row['notification_type'],
            'name' => getNotificationTypeName($row['notification_type']),
            'description' => getNotificationTypeDescription($row['notification_type']),
            'is_enabled' => (bool)$row['is_enabled'],
            'default_remind_before' => (int)$row['default_remind_before'],
            'default_remind_unit' => $row['default_remind_unit']
        ];
    }

    // If no settings exist, initialize them
    if (empty($settings)) {
        initializeUserNotificationSettings($conn, $user_id, $role);
        $settings_result = getUserNotificationSettings($conn, $user_id);
        while ($row = $settings_result->fetch_assoc()) {
            $settings[$row['notification_type']] = [
                'setting_id' => $row['setting_id'],
                'notification_type' => $row['notification_type'],
                'name' => getNotificationTypeName($row['notification_type']),
                'description' => getNotificationTypeDescription($row['notification_type']),
                'is_enabled' => (bool)$row['is_enabled'],
                'default_remind_before' => (int)$row['default_remind_before'],
                'default_remind_unit' => $row['default_remind_unit']
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'settings' => $settings
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>
