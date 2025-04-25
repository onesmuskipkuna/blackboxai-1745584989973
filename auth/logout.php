<?php
require_once '../includes/functions.php';

// Clear session
session_start();
session_destroy();

// Clear remember me cookie if it exists
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Redirect to login page
redirect('login.php');
?>
