<?php
// Razorpay Payment Integration
require_once 'config.php';

// Create Razorpay Order
function createRazorpayOrder($amount, $event_id, $user_data = []) {
    try {
        // Convert amount to paise (Razorpay expects amount in smallest currency unit)
        $amount_paise = $amount * 100;
        
        $order_data = [
            'amount' => $amount_paise,
            'currency' => 'INR',
            'receipt' => 'event_' . $event_id . '_' . time(),
            'notes' => [
                'event_id' => $event_id,
                'customer_name' => $user_data['name'] ?? '',
                'customer_email' => $user_data['email'] ?? '',
                'customer_phone' => $user_data['phone'] ?? ''
            ]
        ];
        
        // Create order via Razorpay API
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.razorpay.com/v1/orders');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($order_data));
        curl_setopt($ch, CURLOPT_USERPWD, RAZORPAY_KEY_ID . ':' . RAZORPAY_KEY_SECRET);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log("Razorpay API Error: " . $response);
            return false;
        }
        
        $order = json_decode($response, true);
        if (!$order) {
            error_log("Invalid Razorpay response: " . $response);
            return false;
        }
        
        // Log order creation for debugging
        if (EMAIL_DEBUG) {
            error_log("Razorpay Order Created: " . json_encode($order));
        }
        
        return $order;
        
    } catch (Exception $e) {
        error_log("Razorpay Order Creation Error: " . $e->getMessage());
        return false;
    }
}

// Verify Razorpay Payment
function verifyRazorpayPayment($payment_id, $order_id, $signature) {
    try {
        // Generate expected signature
        $expected_signature = hash_hmac('sha256', $order_id . '|' . $payment_id, RAZORPAY_KEY_SECRET);
        
        // Verify signature for production
        $is_valid = hash_equals($expected_signature, $signature);
        
        if (!APP_DEBUG) {
            error_log("Payment Verification - Payment ID: $payment_id, Order ID: $order_id, Valid: " . ($is_valid ? 'Yes' : 'No'));
        }
        
        return $is_valid;
        
    } catch (Exception $e) {
        error_log("Razorpay Verification Error: " . $e->getMessage());
        return false;
    }
}

// Get Payment Details
function getPaymentDetails($payment_id) {
    try {
        // In real implementation:
        // $api = new Razorpay\Api\Api(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);
        // $payment = $api->payment->fetch($payment_id);
        
        // Simulate payment details
        $payment = [
            'id' => $payment_id,
            'amount' => 249900, // Example amount in paise
            'currency' => 'INR',
            'status' => 'captured',
            'method' => 'card',
            'created_at' => time()
        ];
        
        return $payment;
        
    } catch (Exception $e) {
        error_log("Get Payment Details Error: " . $e->getMessage());
        return false;
    }
}

// Refund Payment
function refundPayment($payment_id, $amount = null) {
    try {
        // In real implementation:
        // $api = new Razorpay\Api\Api(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);
        // $refund = $api->payment->fetch($payment_id)->refund(['amount' => $amount]);
        
        // Simulate refund
        $refund = [
            'id' => 'rfnd_' . uniqid(),
            'payment_id' => $payment_id,
            'amount' => $amount,
            'status' => 'processed',
            'created_at' => time()
        ];
        
        if (EMAIL_DEBUG) {
            error_log("Refund Processed: " . json_encode($refund));
        }
        
        return $refund;
        
    } catch (Exception $e) {
        error_log("Refund Error: " . $e->getMessage());
        return false;
    }
}

// Handle Razorpay Webhook (for payment status updates)
function handleRazorpayWebhook($webhook_data) {
    try {
        $event = $webhook_data['event'];
        $payment = $webhook_data['payload']['payment']['entity'];
        
        switch ($event) {
            case 'payment.captured':
                // Payment successful
                updatePaymentStatus($payment['id'], 'completed');
                break;
                
            case 'payment.failed':
                // Payment failed
                updatePaymentStatus($payment['id'], 'failed');
                break;
                
            default:
                error_log("Unhandled webhook event: " . $event);
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Webhook Handler Error: " . $e->getMessage());
        return false;
    }
}

// Update Payment Status in Database
function updatePaymentStatus($payment_id, $status) {
    require_once 'db.php';
    $db = getDB();
    
    try {
        $sql = "UPDATE registrations SET payment_status = ? WHERE payment_id = ?";
        $result = $db->query($sql, [$status, $payment_id]);
        
        if ($result) {
            error_log("Payment status updated: $payment_id -> $status");
        }
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Update Payment Status Error: " . $e->getMessage());
        return false;
    }
}
?>