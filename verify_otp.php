<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/otp.php';
require_once 'includes/email.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$email = sanitize($_POST['email'] ?? '');
$event_id = intval($_POST['event_id'] ?? 0);
$otp = sanitize($_POST['otp'] ?? '');

if (!$email || !$event_id || !$otp) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

// Validate OTP format
if (!preg_match('/^\d{6}$/', $otp)) {
    echo json_encode(['success' => false, 'message' => 'Invalid OTP format']);
    exit;
}

// Verify OTP
if (!verifyOTP($email, $event_id, $otp)) {
    echo json_encode(['success' => false, 'message' => 'Invalid or expired OTP']);
    exit;
}

$db = getDB();

try {
    $db->beginTransaction();
    
    // Update registration as verified
    $sql = "UPDATE registrations SET otp_verified = TRUE WHERE email = ? AND event_id = ?";
    $result = $db->query($sql, [$email, $event_id]);
    
    if (!$result) {
        throw new Exception('Failed to verify registration');
    }
    
    // Get registration and event details
    $sql = "SELECT r.*, e.title, e.date, e.time, e.venue 
            FROM registrations r 
            JOIN events e ON r.event_id = e.id 
            WHERE r.email = ? AND r.event_id = ? AND r.otp_verified = TRUE";
    
    $registration = $db->fetchOne($sql, [$email, $event_id]);
    
    if (!$registration) {
        throw new Exception('Registration not found');
    }
    
    $db->commit();
    
    // Send confirmation email
    sendRegistrationConfirmation($email, $registration['name'], $registration);
    
    // Generate success URL with registration details
    $success_url = 'success.php?event=' . encodeId($event_id) . '&reg=' . encodeId($registration['id']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Registration confirmed successfully! Welcome to the event.',
        'redirect_url' => $success_url
    ]);
    
} catch (Exception $e) {
    $db->rollback();
    error_log("OTP Verification Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Verification failed. Please try again.']);
}
?>
