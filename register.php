<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/otp.php';
require_once 'includes/razorpay.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Handle payment verification
if (isset($_POST['razorpay_payment_id'])) {
    $razorpay_payment_id = $_POST['razorpay_payment_id'];
    $razorpay_order_id = $_POST['razorpay_order_id'];
    $razorpay_signature = $_POST['razorpay_signature'];
    
    if (verifyRazorpayPayment($razorpay_payment_id, $razorpay_order_id, $razorpay_signature)) {
        // Payment verified, update registration
        $db = getDB();
        
        $sql = "UPDATE registrations SET payment_id = ?, payment_status = 'completed' 
                WHERE payment_id IS NULL AND created_at > DATE_SUB(NOW(), INTERVAL 30 MINUTE) 
                ORDER BY created_at DESC LIMIT 1";
        
        $result = $db->query($sql, [$razorpay_payment_id]);
        
        if ($result) {
            echo json_encode([
                'success' => true, 
                'message' => 'Payment successful! Please verify your OTP.',
                'show_otp' => true
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Payment verification failed']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid payment signature']);
    }
    exit;
}

// Validate CSRF token
if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
    exit;
}

// Validate input
$event_id = intval($_POST['event_id'] ?? 0);
$name = sanitize($_POST['name'] ?? '');
$email = sanitize($_POST['email'] ?? '');
$phone = sanitize($_POST['phone'] ?? '');

if (!$event_id || !$name || !$email || !$phone) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

// Validate phone
if (!preg_match('/^[\+]?[1-9][\d]{9,14}$/', preg_replace('/[\s\-\(\)]/', '', $phone))) {
    echo json_encode(['success' => false, 'message' => 'Invalid phone number']);
    exit;
}

// Get event details
$event = getEventById($event_id);
if (!$event || $event['is_hidden'] || $event['status'] === 'past') {
    echo json_encode(['success' => false, 'message' => 'Event not available for registration']);
    exit;
}

// Check if already registered (by email or phone)
if (isAlreadyRegistered($event_id, $email, $phone)) {
    echo json_encode(['success' => false, 'message' => 'You are already registered for this event with this email or mobile number.']);
    exit;
}

// Check if event is full
$registration_count = getRegistrationCount($event_id);
if ($event['max_registrations'] && $registration_count >= $event['max_registrations']) {
    echo json_encode(['success' => false, 'message' => 'Event registration is full']);
    exit;
}

// Capture UTM parameters
$utm_source = $_POST['utm_source'] ?? $_GET['utm_source'] ?? null;
$utm_medium = $_POST['utm_medium'] ?? $_GET['utm_medium'] ?? null;
$utm_campaign = $_POST['utm_campaign'] ?? $_GET['utm_campaign'] ?? null;

$db = getDB();

try {
    $db->beginTransaction();
    
    // For paid events, create Razorpay order first
    if ($event['is_paid']) {
        $order_data = createRazorpayOrder($event['price'], $event_id, [
            'name' => $name,
            'email' => $email,
            'phone' => $phone
        ]);
        
        if (!$order_data) {
            throw new Exception('Failed to create payment order');
        }
        
        // Store registration with pending payment
        $sql = "INSERT INTO registrations (event_id, name, email, phone, payment_status, utm_source, utm_medium, utm_campaign, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?)";
        
        $result = $db->query($sql, [
            $event_id, $name, $email, $phone, 
            $utm_source, $utm_medium, $utm_campaign,
            $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
        
        if (!$result) {
            throw new Exception('Failed to create registration');
        }
        
        $db->commit();
        
        // Return Razorpay order details
        echo json_encode([
            'success' => true,
            'payment_required' => true,
            'payment_data' => [
                'key_id' => RAZORPAY_KEY_ID,
                'amount' => $order_data['amount'],
                'currency' => $order_data['currency'],
                'order_id' => $order_data['id'],
                'name' => SITE_NAME,
                'description' => 'Registration for ' . $event['title'],
                'prefill' => [
                    'name' => $name,
                    'email' => $email,
                    'contact' => $phone
                ]
            ]
        ]);
    } else {
        // Free event - direct registration
        $sql = "INSERT INTO registrations (event_id, name, email, phone, payment_status, utm_source, utm_medium, utm_campaign, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, 'completed', ?, ?, ?, ?, ?)";
        
        $result = $db->query($sql, [
            $event_id, $name, $email, $phone, 
            $utm_source, $utm_medium, $utm_campaign,
            $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
        
        if (!$result) {
            throw new Exception('Failed to create registration');
        }
        
        $db->commit();
        
        // Send OTP for verification
        if (sendOTP($email, $event_id, $event['title'])) {
            echo json_encode([
                'success' => true,
                'message' => 'Registration successful! Please check your email for OTP verification.',
                'show_otp' => true
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Registration failed. Please try again.'
            ]);
        }
    }
    
} catch (Exception $e) {
    $db->rollback();
    error_log("Registration Error: event_id={$event_id}, email={$email}, phone={$phone}, is_paid=" . ($event['is_paid'] ? '1' : '0') . ", message=" . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
}
?>
