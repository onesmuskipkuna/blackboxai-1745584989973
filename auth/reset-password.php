<?php
$page_title = 'Reset Password';
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('/index.php');
}

$error = '';
$success = '';
$token = isset($_GET['token']) ? sanitize($_GET['token']) : '';
$valid_token = false;
$user_id = null;

if (!$token) {
    redirect('login.php');
}

// Verify token
$db = Database::getInstance();
$conn = $db->getConnection();

$stmt = $conn->prepare("SELECT id FROM users WHERE password_reset_token = ? AND password_reset_expires > NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    $valid_token = true;
    $user_id = $user['id'];
} else {
    $error = 'Invalid or expired reset token. Please request a new password reset.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (!checkPasswordStrength($password)) {
        $error = 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character';
    } else {
        // Hash new password and update user
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("UPDATE users SET password = ?, password_reset_token = NULL, password_reset_expires = NULL WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $user_id);
        
        if ($stmt->execute()) {
            $success = 'Your password has been successfully reset. You can now login with your new password.';
            // Clear token from URL to prevent reuse
            header("Location: login.php");
            exit();
        } else {
            $error = 'An error occurred. Please try again later.';
        }
    }
}

require_once '../includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Reset Password
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Enter your new password
            </p>
        </div>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline"><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline"><?php echo $success; ?></span>
            </div>
        <?php endif; ?>

        <?php if ($valid_token): ?>
            <form class="mt-8 space-y-6" method="POST">
                <div class="rounded-md shadow-sm -space-y-px">
                    <div>
                        <label for="password" class="sr-only">New Password</label>
                        <input id="password" name="password" type="password" required 
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
                               placeholder="New Password">
                    </div>
                    <div>
                        <label for="confirm_password" class="sr-only">Confirm New Password</label>
                        <input id="confirm_password" name="confirm_password" type="password" required 
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
                               placeholder="Confirm New Password">
                    </div>
                </div>

                <div>
                    <button type="submit" 
                            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <i class="fas fa-key"></i>
                        </span>
                        Reset Password
                    </button>
                </div>
            </form>
        <?php endif; ?>

        <div class="text-center">
            <a href="login.php" class="font-medium text-blue-600 hover:text-blue-500">
                <i class="fas fa-arrow-left mr-2"></i>Back to Login
            </a>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
