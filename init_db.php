<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Create users table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            email TEXT UNIQUE NOT NULL,
            first_name TEXT NOT NULL,
            last_name TEXT NOT NULL,
            role TEXT NOT NULL CHECK(role IN ('admin', 'accountant')),
            password_reset_token TEXT,
            password_reset_expires DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Insert default admin user if not exists
    // Password: admin123
    $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
    $conn->exec("
        INSERT OR REPLACE INTO users (id, username, password, email, first_name, last_name, role)
        VALUES (1, 'admin', '$password_hash', 'admin@school.com', 'System', 'Administrator', 'admin')
    ");
    
    echo "Database initialized successfully!\n";
    echo "Default admin credentials:\n";
    echo "Username: admin\n";
    echo "Password: admin123\n";
    
} catch (Exception $e) {
    die("Error initializing database: " . $e->getMessage() . "\n");
}
?>
