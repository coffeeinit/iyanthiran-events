<?php
// Event Registration System Configuration

// Database Configuration
define('DB_HOST', 'srv1642.hstgr.io');
define('DB_NAME', 'u184589548_eventsphp');
define('DB_USER', 'u184589548_eventsphp');
define('DB_PASS', 'Eventsphp@2024');
define('DB_PORT', '3306');

// Site Configuration
define('SITE_NAME', 'Iyanthiran Events');
define('SITE_URL', 'https://events.iyanthiran.com');
define('ADMIN_URL', SITE_URL . '/admin');

// Email Configuration
define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_PORT', 465);
define('SMTP_USERNAME', 'noreply@iyanthiran.com'); // Your email ID
define('SMTP_PASSWORD', 'Iyanthiran@2024'); // Email password
define('FROM_EMAIL', 'noreply@iyanthiran.com'); // Sender email
define('FROM_NAME', 'Iyanthiran Events');
define('SMTP_ENCRYPTION', 'ssl'); // If used elsewhere in your mailing class


// Razorpay Configuration
define('RAZORPAY_KEY_ID', 'rzp_live_ml2Nci6cGeZxfX'); // Will be set from environment
define('RAZORPAY_KEY_SECRET', 'apEXa1Eb5ITOMZ1BxsB2CbiG'); // Will be set from environment

// File Upload Configuration
define('UPLOAD_PATH', __DIR__ . '/../assets/gallery/');
define('MAX_FILE_SIZE', 1024 * 1024 * 1024); // 1GB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif']);
define('ALLOWED_VIDEO_TYPES', ['video/mp4', 'video/avi', 'video/mov']);

// Security Configuration
define('SESSION_LIFETIME', 7200); // 2 hours
define('OTP_EXPIRY', 300); // 5 minutes
define('HASH_SALT', 'your-unique-salt-here');

// Pagination
define('EVENTS_PER_PAGE', 12);
define('REGISTRATIONS_PER_PAGE', 25);

// Date/Time Configuration
date_default_timezone_set('Asia/Kolkata');

// Error Reporting (disabled for production)
error_reporting(0);
ini_set('display_errors', 0);

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1); // Enabled for HTTPS

// Razorpay Configuration (Production)
if (!defined('RAZORPAY_KEY_ID')) {
    define('RAZORPAY_KEY_ID', 'rzp_live_ml2Nci6cGeZxfX');
}
if (!defined('RAZORPAY_KEY_SECRET')) {
    define('RAZORPAY_KEY_SECRET', 'apEXa1Eb5ITOMZ1BxsB2CbiG');
}

// Debug settings for production
define('EMAIL_DEBUG', false);
define('APP_DEBUG', false);
?>
