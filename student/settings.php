<?php
session_start();
include '../config/db_connect.php';
include '../includes/functions.php';

// Security: Check if user is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: ../index.php");
    exit();
}

include '../includes/header.php';

$user_id = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'update_settings') {
            $notification_types = [
                'class_reminder', 'ca_reminder', 'ue_reminder', 'assignment_reminder',
                'event_reminder', 'attendance_alert', 'low_attendance_warning', 'course_announcement'
            ];

            foreach ($notification_types as $type) {
                $is_enabled = isset($_POST['enabled_' . $type]) ? 1 : 0;
                $remind_before = isset($_POST['remind_before_' . $type]) ? intval($_POST['remind_before_' . $type]) : 1;
                $remind_unit = isset($_POST['remind_unit_' . $type]) ? $_POST['remind_unit_' . $type] : 'days';

                // Validate
                if ($remind_before < 1) $remind_before = 1;
                if ($remind_before > 365) $remind_before = 365;
                if (!in_array($remind_unit, ['hours', 'days'])) $remind_unit = 'days';

                updateNotificationSetting($conn, $user_id, $type, $is_enabled, $remind_before, $remind_unit);
            }

            $_SESSION['flash_message'] = "Settings saved successfully!";
            $_SESSION['flash_type'] = 'success';
        } elseif ($_POST['action'] === 'reset_defaults') {
            // Delete existing settings
            $delete_query = "DELETE FROM user_notification_settings WHERE user_id = ?";
            $stmt = $conn->prepare($delete_query);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();

            // Re-initialize with defaults
            initializeUserNotificationSettings($conn, $user_id, 'student');

            $_SESSION['flash_message'] = "Settings reset to defaults!";
            $_SESSION['flash_type'] = 'success';
        }
        header("Location: settings.php");
        exit();
    }
}

// Get current settings
$settings_result = getUserNotificationSettings($conn, $user_id);
$settings = [];
while ($row = $settings_result->fetch_assoc()) {
    $settings[$row['notification_type']] = $row;
}

// If no settings exist, initialize them
if (empty($settings)) {
    initializeUserNotificationSettings($conn, $user_id, 'student');
    $settings_result = getUserNotificationSettings($conn, $user_id);
    while ($row = $settings_result->fetch_assoc()) {
        $settings[$row['notification_type']] = $row;
    }
}

// Define notification types for students
$notification_types = [
    'class_reminder' => ['icon' => '&#128218;', 'has_timing' => true],
    'ca_reminder' => ['icon' => '&#128221;', 'has_timing' => true],
    'ue_reminder' => ['icon' => '&#128214;', 'has_timing' => true],
    'assignment_reminder' => ['icon' => '&#128203;', 'has_timing' => true],
    'event_reminder' => ['icon' => '&#128197;', 'has_timing' => true],
    'attendance_alert' => ['icon' => '&#9989;', 'has_timing' => false],
    'low_attendance_warning' => ['icon' => '&#9888;', 'has_timing' => false],
    'course_announcement' => ['icon' => '&#128227;', 'has_timing' => false]
];
?>

<div class="container">
    <div class="page-header">
        <h2>Notification Settings</h2>
        <p>Customize how and when you receive notifications</p>
    </div>

    <?php displayFlashMessage(); ?>

    <form method="POST" class="settings-form">
        <input type="hidden" name="action" value="update_settings">

        <div class="settings-section">
            <h3>Reminder Notifications</h3>
            <p class="settings-description">Choose which reminders you want to receive and when</p>

            <?php foreach ($notification_types as $type => $config):
                $setting = $settings[$type] ?? ['is_enabled' => 1, 'default_remind_before' => 1, 'default_remind_unit' => 'days'];
                $name = getNotificationTypeName($type);
                $description = getNotificationTypeDescription($type);
            ?>
            <div class="setting-row">
                <div class="setting-info">
                    <span class="setting-icon"><?php echo $config['icon']; ?></span>
                    <div class="setting-text">
                        <span class="setting-name"><?php echo htmlspecialchars($name); ?></span>
                        <span class="setting-desc"><?php echo htmlspecialchars($description); ?></span>
                    </div>
                </div>
                <div class="setting-controls">
                    <label class="toggle-switch">
                        <input type="checkbox" name="enabled_<?php echo $type; ?>"
                               <?php echo $setting['is_enabled'] ? 'checked' : ''; ?>>
                        <span class="toggle-slider"></span>
                    </label>

                    <?php if ($config['has_timing']): ?>
                    <div class="timing-controls">
                        <span class="timing-label">Remind</span>
                        <input type="number" name="remind_before_<?php echo $type; ?>"
                               value="<?php echo $setting['default_remind_before']; ?>"
                               min="1" max="365" class="timing-input">
                        <select name="remind_unit_<?php echo $type; ?>" class="timing-select">
                            <option value="hours" <?php echo $setting['default_remind_unit'] === 'hours' ? 'selected' : ''; ?>>hours</option>
                            <option value="days" <?php echo $setting['default_remind_unit'] === 'days' ? 'selected' : ''; ?>>days</option>
                        </select>
                        <span class="timing-label">before</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="settings-actions">
            <button type="submit" class="btn btn-primary">Save Settings</button>
            <button type="submit" name="action" value="reset_defaults" class="btn btn-secondary"
                    onclick="return confirm('Are you sure you want to reset all settings to defaults?');">
                Reset to Defaults
            </button>
        </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
