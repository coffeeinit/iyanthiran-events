<?php
// Email Functions using PHPMailer
require_once 'config.php';

// Since we don't have PHPMailer installed, we'll use PHP's mail() function
// In production, replace this with PHPMailer for better reliability

// Send OTP Email
function sendOTPEmail($email, $otp, $event_title = '') {
    $subject = 'Email Verification - ' . SITE_NAME;
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #007bff; color: white; padding: 20px; text-align: center; }
            .content { padding: 30px; background: #f8f9fa; }
            .otp-box { background: white; padding: 20px; text-align: center; border: 2px dashed #007bff; margin: 20px 0; }
            .otp-code { font-size: 24px; font-weight: bold; color: #007bff; letter-spacing: 3px; }
            .footer { padding: 20px; text-align: center; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>" . SITE_NAME . "</h1>
                <p>Email Verification Required</p>
            </div>
            
            <div class='content'>
                <h2>Verify Your Email Address</h2>
                <p>Hello,</p>
                <p>Thank you for registering" . ($event_title ? " for <strong>" . htmlspecialchars($event_title) . "</strong>" : "") . ". To complete your registration, please verify your email address using the OTP below:</p>
                
                <div class='otp-box'>
                    <p>Your Verification Code:</p>
                    <div class='otp-code'>" . $otp . "</div>
                    <p><small>This code will expire in " . (OTP_EXPIRY/60) . " minutes</small></p>
                </div>
                
                <p>Enter this code on the verification page to complete your registration.</p>
                <p>If you didn't request this verification, please ignore this email.</p>
            </div>
            
            <div class='footer'>
                <p>&copy; " . date('Y') . " " . SITE_NAME . ". All rights reserved.</p>
                <p>This is an automated email. Please do not reply.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: " . FROM_NAME . " <" . FROM_EMAIL . ">" . "\r\n";
    $headers .= "Reply-To: " . FROM_EMAIL . "\r\n";
    
    try {
        $result = mail($email, $subject, $message, $headers);
        
        if (EMAIL_DEBUG) {
            error_log("OTP Email sent to: $email, OTP: $otp, Result: " . ($result ? 'Success' : 'Failed'));
        }
        
        return $result;
    } catch (Exception $e) {
        error_log("Email Error: " . $e->getMessage());
        return false;
    }
}

// Send Registration Confirmation Email
function sendRegistrationConfirmation($email, $name, $registration_data) {
    $subject = 'Registration Confirmed - ' . htmlspecialchars($registration_data['title']);
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #28a745; color: white; padding: 20px; text-align: center; }
            .content { padding: 30px; background: #f8f9fa; }
            .details-box { background: white; padding: 20px; border-left: 4px solid #28a745; margin: 20px 0; }
            .detail-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; }
            .detail-label { font-weight: bold; }
            .footer { padding: 20px; text-align: center; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>✓ Registration Confirmed</h1>
                <p>You're all set for the event!</p>
            </div>
            
            <div class='content'>
                <h2>Dear " . htmlspecialchars($name) . ",</h2>
                <p>Congratulations! Your registration has been confirmed for:</p>
                
                <div class='details-box'>
                    <h3>" . htmlspecialchars($registration_data['title']) . "</h3>
                    
                    <div class='detail-row'>
                        <span class='detail-label'>Date:</span>
                        <span>" . formatDate($registration_data['date'], 'l, F j, Y') . "</span>
                    </div>
                    
                    <div class='detail-row'>
                        <span class='detail-label'>Time:</span>
                        <span>" . formatTime($registration_data['time']) . "</span>
                    </div>
                    
                    <div class='detail-row'>
                        <span class='detail-label'>Venue:</span>
                        <span>" . htmlspecialchars($registration_data['venue']) . "</span>
                    </div>
                    
                    <div class='detail-row'>
                        <span class='detail-label'>Registration ID:</span>
                        <span>#" . str_pad($registration_data['id'], 6, '0', STR_PAD_LEFT) . "</span>
                    </div>
                    
                    " . (isset($registration_data['is_paid']) && $registration_data['is_paid'] ? "
                    <div class='detail-row'>
                        <span class='detail-label'>Payment Status:</span>
                        <span style='color: #28a745; font-weight: bold;'>Paid - ₹" . number_format($registration_data['price'], 2) . "</span>
                    </div>" : "") . "
                </div>
                
                <h3>Important Notes:</h3>
                <ul>
                    <li>Please arrive 15 minutes early for check-in</li>
                    <li>Bring a valid ID for verification</li>
                    <li>Keep this confirmation email for your records</li>
                    <li>You will receive a reminder email 24 hours before the event</li>
                </ul>
                
                <p>We look forward to seeing you at the event!</p>
            </div>
            
            <div class='footer'>
                <p>&copy; " . date('Y') . " " . SITE_NAME . ". All rights reserved.</p>
                <p>Event Management System</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: " . FROM_NAME . " <" . FROM_EMAIL . ">" . "\r\n";
    $headers .= "Reply-To: " . FROM_EMAIL . "\r\n";
    
    try {
        $result = mail($email, $subject, $message, $headers);
        
        if (EMAIL_DEBUG) {
            error_log("Confirmation email sent to: $email, Result: " . ($result ? 'Success' : 'Failed'));
        }
        
        return $result;
    } catch (Exception $e) {
        error_log("Confirmation Email Error: " . $e->getMessage());
        return false;
    }
}

// Send Admin Notification Email
function sendAdminNotification($registration_data) {
    if (!defined('ADMIN_EMAIL') || empty(ADMIN_EMAIL)) {
        return false; // No admin email configured
    }
    
    $subject = 'New Registration - ' . htmlspecialchars($registration_data['title']);
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #007bff; color: white; padding: 20px; text-align: center; }
            .content { padding: 30px; background: #f8f9fa; }
            .details-box { background: white; padding: 20px; border-left: 4px solid #007bff; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>New Event Registration</h1>
            </div>
            
            <div class='content'>
                <h2>Registration Details</h2>
                
                <div class='details-box'>
                    <p><strong>Event:</strong> " . htmlspecialchars($registration_data['title']) . "</p>
                    <p><strong>Name:</strong> " . htmlspecialchars($registration_data['name']) . "</p>
                    <p><strong>Email:</strong> " . htmlspecialchars($registration_data['email']) . "</p>
                    <p><strong>Phone:</strong> " . htmlspecialchars($registration_data['phone']) . "</p>
                    <p><strong>Registration Time:</strong> " . $registration_data['created_at'] . "</p>
                    " . (isset($registration_data['utm_source']) && $registration_data['utm_source'] ? "
                    <p><strong>Source:</strong> " . htmlspecialchars($registration_data['utm_source']) . "</p>" : "") . "
                </div>
                
                <p>A new registration has been received for your event. You can view all registrations in the admin panel.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: " . FROM_NAME . " <" . FROM_EMAIL . ">" . "\r\n";
    
    return mail(ADMIN_EMAIL, $subject, $message, $headers);
}
?>