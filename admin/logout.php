<?php
session_start();
require_once 'includes/auth.php';

// Logout admin
logout();

// Redirect to login page
header('Location: login.php');
exit;
?>