<?php
// Include the required configuration files if not already included
if (!defined('APP_NAME')) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/jollibee-ordering-system/config/config.php';
}
?>
                </main>
            </div>
        </div>
    
    <!-- Admin Footer -->
    <footer class="bg-white shadow-inner py-3 mt-auto">
        <div class="container mx-auto px-4">
            <p class="text-center text-gray-600 text-sm">&copy; <?php echo date('Y'); ?> Jollibee Ordering System - Admin Panel</p>
        </div>
    </footer>

    <!-- Scripts -->
    <script>
        // Toggle user dropdown menu
        document.addEventListener('DOMContentLoaded', function() {
            const userMenuButton = document.getElementById('user-menu-button');
            const userDropdown = document.getElementById('user-dropdown');
            
            if (userMenuButton && userDropdown) {
                userMenuButton.addEventListener('click', function() {
                    userDropdown.classList.toggle('hidden');
                });
                
                // Close dropdown when clicking outside
                document.addEventListener('click', function(event) {
                    if (!userMenuButton.contains(event.target) && !userDropdown.contains(event.target)) {
                        userDropdown.classList.add('hidden');
                    }
                });
            }

            // Sidebar toggle functionality
            const sidebarToggle = document.getElementById('sidebar-toggle');
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.querySelector('.main-content');
            const toggleIcon = document.querySelector('.toggle-icon');
            
            // Check if sidebar state is stored in localStorage
            const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            
            // Set initial state based on localStorage or default to expanded
            if (sidebarCollapsed) {
                sidebar.classList.add('collapsed');
                toggleIcon.classList.add('rotated');
                mainContent.style.marginLeft = '0';
            }
            
            // Toggle sidebar when button is clicked
            if (sidebarToggle && sidebar) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('collapsed');
                    toggleIcon.classList.toggle('rotated');
                    
                    // Update main content margin
                    if (sidebar.classList.contains('collapsed')) {
                        mainContent.style.marginLeft = '0';
                        localStorage.setItem('sidebarCollapsed', 'true');
                    } else {
                        mainContent.style.marginLeft = '';
                        localStorage.setItem('sidebarCollapsed', 'false');
                    }
                });
            }
            
            // Handle responsive behavior
            function checkScreenSize() {
                if (window.innerWidth < 768) {
                    sidebar.classList.add('collapsed');
                    mainContent.style.marginLeft = '0';
                } else if (!sidebarCollapsed && sidebar.classList.contains('collapsed')) {
                    sidebar.classList.remove('collapsed');
                    mainContent.style.marginLeft = '';
                }
            }
            
            // Check on page load
            checkScreenSize();
            
            // Check when window is resized
            window.addEventListener('resize', checkScreenSize);
        });
        
        // Add any additional admin scripts here
        console.log('Admin panel loaded successfully');
    </script>
</body>
</html> 