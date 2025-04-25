<?php
define('DB_TYPE', 'sqlite');
define('DB_PATH', __DIR__ . '/../database.sqlite');

// Email configuration for password reset
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password');
define('SMTP_PORT', 587);

// Application configuration
define('SITE_URL', 'http://localhost:8000');
define('SITE_NAME', 'School Fees Management System');
?>
