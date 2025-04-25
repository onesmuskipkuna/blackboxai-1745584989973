<footer class="bg-gray-800 text-white py-4 mt-auto">
        <div class="container mx-auto px-4">
            <div class="text-center">
                <p>&copy; <?php echo date('Y'); ?> School Fees Management System. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
    // Auto-hide flash messages after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            const alerts = document.querySelectorAll('[role="alert"]');
            alerts.forEach(function(alert) {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s ease-in-out';
                setTimeout(function() {
                    alert.remove();
                }, 500);
            });
        }, 5000);
    });
    </script>
</body>
</html>
