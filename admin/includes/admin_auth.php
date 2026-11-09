<?php
// Admin Authentication Functions

function checkAdminAuth() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header('Location: login.php');
        exit;
    }
}

function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function adminLogin($username, $password, &$error = null) {
    $db = getDB();
    $sql = "SELECT * FROM users WHERE username = ? AND role = 'admin'";
    $user = $db->fetchOne($sql, [$username]);

    if (!$user) {
        $error = 'Invalid username';
        return false;
    }
    if (!$user['is_active']) {
        $error = 'Account is inactive. Please contact the administrator.';
        return false;
    }
    if (!password_verify($password, $user['password_hash'])) {
        $error = 'Invalid password';
        return false;
    }
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_user'] = [
        'id' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'role' => $user['role']
    ];
    // Update last login
    $db->query("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);
    return true;
}

function adminLogout() {
    unset($_SESSION['admin_logged_in']);
    unset($_SESSION['admin_user']);
    session_destroy();
}
?>