<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/otp.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$email = sanitize($_POST['email'] ?? '');
$event_id = intval($_POST['event_id'] ?? 0);

if (!$email || !$event_id) {
    echo json_encode(['success' => false, 'message' => 'Email and event ID are required']);
    exit;
}

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

// Get event details
$event = getEventById($event_id);
if (!$event) {
    echo json_encode(['success' => false, 'message' => 'Event not found']);
    exit;
}

// Check if user is registered for this event
$db = getDB();
$sql = "SELECT id FROM registrations WHERE email = ? AND event_id = ?";
$registration = $db->fetchOne($sql, [$email, $event_id]);

if (!$registration) {
    echo json_encode(['success' => false, 'message' => 'No registration found for this email']);
    exit;
}

// Resend OTP
if (sendOTP($email, $event_id, $event['title'])) {
    $result = ['success' => true, 'message' => 'New verification code sent to your email'];
} else {
    $result = ['success' => false, 'message' => 'Failed to send verification code'];
}

echo json_encode($result);
?>