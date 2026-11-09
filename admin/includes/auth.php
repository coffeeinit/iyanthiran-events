<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Admin Authentication Check
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit;
}

// Check session timeout (2 hours)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
    session_destroy();
    header('Location: login.php?timeout=1');
    exit;
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Admin helper functions
function requireAdmin() {
    if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
        header('HTTP/1.1 403 Forbidden');
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit;
    }
}

function getAdminInfo() {
    return [
        'id' => $_SESSION['admin_id'],
        'username' => $_SESSION['admin_username']
    ];
}

// Set admin-specific configurations
define('EMAIL_DEBUG', true); // Log emails in admin mode for debugging
?>
