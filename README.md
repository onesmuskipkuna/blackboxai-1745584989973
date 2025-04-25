
Built by https://www.blackbox.ai

---

```markdown
# School Management Dashboard

## Project Overview
The School Management Dashboard is a PHP-based web application that provides an overview of student and financial data for a school. The dashboard offers insights into total students, fees collected this month, monthly expenses, and outstanding fees. It also displays recent payment and expense activities.

## Installation
To set up the School Management Dashboard, follow the steps below:

1. **Clone the Repository:**
   ```bash
   git clone https://github.com/yourusername/school-management-dashboard.git
   cd school-management-dashboard
   ```

2. **Set up a Web Server:**
   You need a web server that supports PHP and SQLite. You can use XAMPP, MAMP, or deploy it on a live server.

3. **Configure Database:**
   1. Open `init_db.php` and run it in your browser (e.g., http://localhost/school-management-dashboard/init_db.php) to create the required tables and insert a default admin user into the database.
   2. Modify the database configuration in `includes/config.php` if necessary.

4. **Access the Application:**
   After initializing the database, navigate to `index.php` (e.g., http://localhost/school-management-dashboard/index.php) to access the dashboard.

## Usage
Upon accessing the dashboard, you will see:
- **Total Students** count
- **Fees Collected** for the current month
- **Monthly Expenses**
- **Outstanding Fees**
- **Recent Payments** and **Expenses** with their respective details

The default admin credentials are:
- **Username:** admin
- **Password:** admin123

## Features
- Real-time insight into school data.
- Displays total active students.
- Shows fees collected and expenses for the current month.
- Lists outstanding fees.
- Recent activity tracking for payments and expenses.

## Dependencies
The project utilizes the following dependencies:
- PHP (>= 7.0)
- SQLite (built-in with PHP)
- A web server (Apache, Nginx, etc.)

## Project Structure
```
school-management-dashboard/
│
├── index.php                # Main dashboard displaying key statistics
├── init_db.php              # Script to initialize the database and create default user
└── includes/                
    ├── config.php           # Configuration file for database connection
    ├── db.php               # Database connection handling
    ├── footer.php           # Footer file included in the dashboard
    ├── functions.php        # Functions used throughout the application
    └── header.php           # Header file included in the dashboard
    └── navigation.php       # Navigation menu for the application
```

Feel free to modify and extend the application as per your needs!
```