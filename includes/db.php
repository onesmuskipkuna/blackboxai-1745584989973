<?php
require_once 'config.php';

class Database {
    private $connection;
    private static $instance = null;

    private function __construct() {
        try {
            $this->connection = new SQLite3(DB_PATH);
            $this->connection->enableExceptions(true);
            $this->createTables();
        } catch (Exception $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    public function query($sql) {
        return $this->connection->query($sql);
    }

    public function escape($value) {
        return SQLite3::escapeString($value);
    }

    private function createTables() {
        // Create users table
        $this->connection->exec("
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

        // Create students table
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS students (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                admission_number TEXT UNIQUE NOT NULL,
                first_name TEXT NOT NULL,
                last_name TEXT NOT NULL,
                guardian_name TEXT NOT NULL,
                phone_number TEXT NOT NULL,
                education_level TEXT NOT NULL CHECK(education_level IN ('primary', 'junior_secondary')),
                class TEXT NOT NULL,
                status TEXT DEFAULT 'active' CHECK(status IN ('active', 'inactive', 'graduated', 'transferred')),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Create fee_structure table
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS fee_structure (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                class TEXT NOT NULL,
                education_level TEXT NOT NULL CHECK(education_level IN ('primary', 'junior_secondary')),
                fee_item TEXT NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                term INTEGER NOT NULL,
                academic_year TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Create invoices table
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS invoices (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                student_id INTEGER NOT NULL,
                invoice_number TEXT UNIQUE NOT NULL,
                total_amount DECIMAL(10,2) NOT NULL,
                paid_amount DECIMAL(10,2) DEFAULT 0,
                balance DECIMAL(10,2) NOT NULL,
                status TEXT DEFAULT 'due' CHECK(status IN ('due', 'partially_paid', 'fully_paid')),
                term INTEGER NOT NULL,
                academic_year TEXT NOT NULL,
                due_date DATE NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (student_id) REFERENCES students(id)
            )
        ");

        // Create invoice_items table
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS invoice_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                invoice_id INTEGER NOT NULL,
                fee_structure_id INTEGER NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (invoice_id) REFERENCES invoices(id),
                FOREIGN KEY (fee_structure_id) REFERENCES fee_structure(id)
            )
        ");

        // Create payments table
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS payments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                invoice_id INTEGER NOT NULL,
                payment_number TEXT UNIQUE NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                payment_mode TEXT NOT NULL CHECK(payment_mode IN ('cash', 'mpesa', 'bank')),
                reference_number TEXT,
                remarks TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (invoice_id) REFERENCES invoices(id)
            )
        ");

        // Create payment_items table
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS payment_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                payment_id INTEGER NOT NULL,
                invoice_item_id INTEGER NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (payment_id) REFERENCES payments(id),
                FOREIGN KEY (invoice_item_id) REFERENCES invoice_items(id)
            )
        ");

        // Create expense_categories table
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS expense_categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                description TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Create expenses table
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS expenses (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                category_id INTEGER NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                description TEXT,
                date DATE NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (category_id) REFERENCES expense_categories(id)
            )
        ");

        // Insert default admin user if not exists
        $this->connection->exec("
            INSERT OR IGNORE INTO users (username, password, email, first_name, last_name, role)
            VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@school.com', 'System', 'Administrator', 'admin')
        ");
    }
}
?>
