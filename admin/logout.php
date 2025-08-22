<?php
require_once '../config/config.php';

// Initialize authentication
$auth = new Auth();

// Perform logout
$auth->logout();

// Redirect to login page with message
redirect(ADMIN_URL . '/login.php', 'You have been logged out successfully.', 'info');
?>