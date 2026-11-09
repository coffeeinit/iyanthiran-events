<?php
// OTP Management Functions
require_once 'config.php';
require_once 'db.php';

// Generate and send OTP
function sendOTP($email, $event_id, $event_title = '') {
    $db = getDB();
    
    try {
        // Generate 6-digit OTP
        $otp = sprintf('%06d', mt_rand(0, 999999));
        $otp_hash = password_hash($otp, PASSWORD_DEFAULT);
        
        // Clean old OTPs for this email/event
        $db->query("DELETE FROM otp_verifications WHERE email = ? AND event_id = ?", [$email, $event_id]);
        
        // Store new OTP
        $expires_at = date('Y-m-d H:i:s', time() + OTP_EXPIRY);
        $sql = "INSERT INTO otp_verifications (email, event_id, otp_hash, expires_at) VALUES (?, ?, ?, ?)";
        
        $result = $db->query($sql, [$email, $event_id, $otp_hash, $expires_at]);
        
        if (!$result) {
            return false;
        }
        
        // Send OTP via email
        require_once 'email.php';
        return sendOTPEmail($email, $otp, $event_title);
        
    } catch (Exception $e) {
        error_log("OTP Generation Error: " . $e->getMessage());
        return false;
    }
}

// Verify OTP
function verifyOTP($email, $event_id, $otp) {
    $db = getDB();
    
    try {
        // Get stored OTP hash
        $sql = "SELECT otp_hash FROM otp_verifications 
                WHERE email = ? AND event_id = ? AND expires_at > NOW() 
                ORDER BY created_at DESC LIMIT 1";
        
        $result = $db->fetchOne($sql, [$email, $event_id]);
        
        if (!$result) {
            return false; // No valid OTP found
        }
        
        // Verify OTP
        if (password_verify($otp, $result['otp_hash'])) {
            // Clean up used OTP
            $db->query("DELETE FROM otp_verifications WHERE email = ? AND event_id = ?", [$email, $event_id]);
            return true;
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log("OTP Verification Error: " . $e->getMessage());
        return false;
    }
}

// Clean expired OTPs (should be run periodically)
if (!function_exists('cleanExpiredOTPs')) {
    function cleanExpiredOTPs() {
        $db = getDB();
        
        try {
            $db->query("DELETE FROM otp_verifications WHERE expires_at <= NOW()");
            return true;
        } catch (Exception $e) {
            error_log("OTP Cleanup Error: " . $e->getMessage());
            return false;
        }
    }
}

// Resend OTP (with rate limiting)
function resendOTP($email, $event_id, $event_title = '') {
    $db = getDB();
    
    try {
        // Check if last OTP was sent within 1 minute (rate limiting)
        $sql = "SELECT created_at FROM otp_verifications 
                WHERE email = ? AND event_id = ? 
                ORDER BY created_at DESC LIMIT 1";
        
        $result = $db->fetchOne($sql, [$email, $event_id]);
        
        if ($result) {
            $last_sent = strtotime($result['created_at']);
            $now = time();
            
            if (($now - $last_sent) < 60) {
                return ['success' => false, 'message' => 'Please wait before requesting another OTP'];
            }
        }
        
        // Send new OTP
        if (sendOTP($email, $event_id, $event_title)) {
            return ['success' => true, 'message' => 'OTP sent successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to send OTP'];
        }
        
    } catch (Exception $e) {
        error_log("OTP Resend Error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to resend OTP'];
    }
}
?>