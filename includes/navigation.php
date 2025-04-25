<?php
if (!isLoggedIn()) {
    redirect('/auth/login.php');
}
?>
<nav class="bg-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <span class="text-white text-lg font-bold">School Fees MS</span>
                </div>
                <div class="hidden md:block">
                    <div class="ml-10 flex items-baseline space-x-4">
                        <a href="/index.php" class="<?php echo ($_SERVER['PHP_SELF'] == '/index.php') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-chart-line mr-2"></i>Dashboard
                        </a>
                        
                        <a href="/modules/students/index.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-user-graduate mr-2"></i>Students
                        </a>
                        
                        <a href="/modules/fees/index.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-money-bill mr-2"></i>Fees
                        </a>
                        
                        <a href="/modules/invoices/index.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-file-invoice-dollar mr-2"></i>Invoices
                        </a>
                        
                        <a href="/modules/payments/index.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-credit-card mr-2"></i>Payments
                        </a>
                        
                        <a href="/modules/expenses/index.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-receipt mr-2"></i>Expenses
                        </a>
                        
                        <a href="/modules/payroll/index.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-money-check-alt mr-2"></i>Payroll
                        </a>
                        
                        <a href="/modules/reports/index.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-chart-bar mr-2"></i>Reports
                        </a>
                        
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                        <a href="/modules/users/index.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-users-cog mr-2"></i>Users
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="hidden md:block">
                <div class="ml-4 flex items-center md:ml-6">
                    <div class="ml-3 relative">
                        <div class="flex items-center">
                            <span class="text-gray-300 mr-4">
                                <i class="fas fa-user mr-2"></i><?php echo $_SESSION['full_name']; ?>
                            </span>
                            <a href="/auth/logout.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">
                                <i class="fas fa-sign-out-alt mr-2"></i>Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Mobile menu button -->
            <div class="-mr-2 flex md:hidden">
                <button type="button" class="bg-gray-800 inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-800 focus:ring-white" aria-controls="mobile-menu" aria-expanded="false">
                    <span class="sr-only">Open main menu</span>
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile menu -->
    <div class="md:hidden" id="mobile-menu">
        <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
            <a href="/index.php" class="<?php echo ($_SERVER['PHP_SELF'] == '/index.php') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> block px-3 py-2 rounded-md text-base font-medium">
                <i class="fas fa-chart-line mr-2"></i>Dashboard
            </a>
            
            <a href="/modules/students/index.php" class="text-gray-300 hover:bg-gray-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">
                <i class="fas fa-user-graduate mr-2"></i>Students
            </a>
            
            <a href="/modules/fees/index.php" class="text-gray-300 hover:bg-gray-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">
                <i class="fas fa-money-bill mr-2"></i>Fees
            </a>
            
            <a href="/modules/invoices/index.php" class="text-gray-300 hover:bg-gray-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">
                <i class="fas fa-file-invoice-dollar mr-2"></i>Invoices
            </a>
            
            <a href="/modules/payments/index.php" class="text-gray-300 hover:bg-gray-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">
                <i class="fas fa-credit-card mr-2"></i>Payments
            </a>
            
            <a href="/modules/expenses/index.php" class="text-gray-300 hover:bg-gray-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">
                <i class="fas fa-receipt mr-2"></i>Expenses
            </a>
            
            <a href="/modules/payroll/index.php" class="text-gray-300 hover:bg-gray-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">
                <i class="fas fa-money-check-alt mr-2"></i>Payroll
            </a>
            
            <a href="/modules/reports/index.php" class="text-gray-300 hover:bg-gray-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">
                <i class="fas fa-chart-bar mr-2"></i>Reports
            </a>
            
            <?php if ($_SESSION['role'] === 'admin'): ?>
            <a href="/modules/users/index.php" class="text-gray-300 hover:bg-gray-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">
                <i class="fas fa-users-cog mr-2"></i>Users
            </a>
            <?php endif; ?>
            
            <div class="border-t border-gray-700 pt-4 pb-3">
                <div class="flex items-center px-5">
                    <div class="ml-3">
                        <div class="text-base font-medium leading-none text-white"><?php echo $_SESSION['full_name']; ?></div>
                    </div>
                </div>
                <div class="mt-3 px-2 space-y-1">
                    <a href="/auth/logout.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-400 hover:text-white hover:bg-gray-700">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuButton = document.querySelector('[aria-controls="mobile-menu"]');
    const mobileMenu = document.getElementById('mobile-menu');
    
    mobileMenuButton.addEventListener('click', function() {
        const expanded = this.getAttribute('aria-expanded') === 'true';
        this.setAttribute('aria-expanded', !expanded);
        mobileMenu.classList.toggle('hidden');
    });
});
</script>
