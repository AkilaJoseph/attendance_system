<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db_connect.php';

// ── Create table on first run if it doesn't exist ──────────────────────────
$conn->query("CREATE TABLE IF NOT EXISTS `waitlist` (
    `id`          INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `first_name`  VARCHAR(80)      NOT NULL,
    `last_name`   VARCHAR(80)      NOT NULL,
    `email`       VARCHAR(200)     NOT NULL,
    `phone`       VARCHAR(30)      DEFAULT NULL,
    `role`        VARCHAR(50)      DEFAULT NULL,
    `institution` VARCHAR(200)     DEFAULT NULL,
    `created_at`  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

// ── Validate input ─────────────────────────────────────────────────────────
$first_name  = trim($_POST['first_name']  ?? '');
$last_name   = trim($_POST['last_name']   ?? '');
$email       = trim($_POST['email']       ?? '');
$phone       = trim($_POST['phone']       ?? '') ?: null;
$role        = trim($_POST['role']        ?? '') ?: null;
$institution = trim($_POST['institution'] ?? '') ?: null;

if (empty($first_name) || empty($last_name)) {
    echo json_encode(['success' => false, 'message' => 'Please enter your full name.']);
    exit;
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

// Sanitise
$first_name  = htmlspecialchars($first_name,  ENT_QUOTES, 'UTF-8');
$last_name   = htmlspecialchars($last_name,   ENT_QUOTES, 'UTF-8');
$institution = $institution ? htmlspecialchars($institution, ENT_QUOTES, 'UTF-8') : null;

// ── Insert ─────────────────────────────────────────────────────────────────
$stmt = $conn->prepare(
    "INSERT INTO `waitlist` (first_name, last_name, email, phone, role, institution)
     VALUES (?, ?, ?, ?, ?, ?)"
);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Server error. Please try again later.']);
    exit;
}

$stmt->bind_param('ssssss', $first_name, $last_name, $email, $phone, $role, $institution);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    // Duplicate email (UNIQUE constraint)
    if ($conn->errno === 1062) {
        echo json_encode(['success' => false, 'message' => 'This email is already on the waitlist!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Could not save your details. Please try again.']);
    }
}

$stmt->close();
$conn->close();
