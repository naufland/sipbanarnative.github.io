// js/submenu.js - JavaScript untuk submenu dropdown
document.addEventListener('DOMContentLoaded', function() {
    // Handle submenu untuk desktop dan mobile
    const dropdownSubmenus = document.querySelectorAll('.dropdown-submenu');
    
    dropdownSubmenus.forEach(function(submenu) {
        const submenuToggle = submenu.querySelector('.dropdown-item.dropdown-toggle');
        const submenuDropdown = submenu.querySelector('.dropdown-menu');
        
        if (!submenuToggle || !submenuDropdown) return;
        
        // Untuk mobile - toggle dengan click
        submenuToggle.addEventListener('click', function(e) {
            if (window.innerWidth <= 991.98) {
                e.preventDefault();
                e.stopPropagation();
                
                // Toggle visibility
                if (submenuDropdown.style.display === 'block') {
                    submenuDropdown.style.display = 'none';
                } else {
                    // Hide other submenus
                    document.querySelectorAll('.dropdown-submenu .dropdown-menu').forEach(function(menu) {
                        if (menu !== submenuDropdown) {
                            menu.style.display = 'none';
                        }
                    });
                    submenuDropdown.style.display = 'block';
                }
            }
        });
        
        // Untuk desktop - show/hide dengan hover
        submenu.addEventListener('mouseenter', function() {
            if (window.innerWidth > 991.98) {
                submenuDropdown.style.display = 'block';
            }
        });
        
        submenu.addEventListener('mouseleave', function() {
            if (window.innerWidth > 991.98) {
                submenuDropdown.style.display = 'none';
            }
        });
    });

    // Close submenu when parent dropdown closes
    document.querySelectorAll('.dropdown-menu').forEach(function(dropdown) {
        dropdown.addEventListener('hidden.bs.dropdown', function() {
            this.querySelectorAll('.dropdown-submenu .dropdown-menu').forEach(function(submenu) {
                submenu.style.display = 'none';
            });
        });
    });

    // Close submenu when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown-submenu')) {
            document.querySelectorAll('.dropdown-submenu .dropdown-menu').forEach(function(submenu) {
                submenu.style.display = 'none';
            });
        }
    });
});