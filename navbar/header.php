<?php
    // Set default page title jika tidak didefinisikan
    if (!isset($page_title)) {
        $page_title = "SIP BANAR - Sistem Informasi Pengadaan";
    }
    ?>
    <!DOCTYPE html>
    <html lang="id">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($page_title) ?></title>
    
        <!-- Bootstrap CSS -->
        <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">

        <!-- Font Awesome -->
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

        <!-- Google Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

        <!-- Custom CSS untuk Header -->
        <style>
            * {
                font-family: 'Inter', sans-serif;
            }

            body {
                margin: 0;
                padding: 0;
                background-color: #f8f9fa;
            }

            /* Header Styles */
            .main-header {
                background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
                color: white;
                padding: 0;
                box-shadow: 0 4px 20px rgba(220, 53, 69, 0.3);
                position: sticky;
                top: 0;
                z-index: 1000;
                border-bottom: 3px solid #b21e2f;
            }

            .navbar {
                padding: 12px 0;
            }

            .navbar-brand {
                font-weight: 700;
                font-size: 24px;
                color: white !important;
                text-decoration: none;
                display: flex;
                align-items: center;
                gap: 12px;
            }

            .navbar-brand:hover {
                color: #f8f9fa !important;
            }

            .navbar-brand i {
                font-size: 28px;
                background: rgba(255, 255, 255, 0.15);
                padding: 8px;
                border-radius: 10px;
                backdrop-filter: blur(10px);
            }

            /* Navigation Menu */
            .navbar-nav {
                gap: 20px;
            }

            .nav-item .nav-link {
                color: rgba(255, 255, 255, 0.9) !important;
                font-weight: 500;
                font-size: 15px;
                padding: 10px 18px !important;
                border-radius: 8px;
                display: flex;
                align-items: center;
                gap: 8px;
                text-decoration: none;
                border: 2px solid transparent;
                transition: all 0.3s ease;
            }

            .nav-item .nav-link:hover {
                background: rgba(255, 255, 255, 0.15);
                color: white !important;
                border-color: rgba(255, 255, 255, 0.2);
            }

            .nav-item .nav-link.active {
                background: rgba(255, 255, 255, 0.2);
                color: white !important;
                font-weight: 600;
            }

            .nav-item .nav-link i {
                font-size: 16px;
            }

            /* Dropdown Menu */
            .dropdown-menu {
                background: white;
                border: none;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
                border-radius: 12px;
                padding: 8px 0;
                margin-top: 8px;
                min-width: 220px;
                z-index: 1020;
            }

            .dropdown-item {
                padding: 12px 20px;
                font-size: 14px;
                color: #2c3e50;
                display: flex;
                align-items: center;
                gap: 10px;
                transition: all 0.3s ease;
                text-decoration: none;
            }

            .dropdown-item:hover {
                background: #f8f9fa;
                color: #dc3545;
            }

            .dropdown-item i {
                font-size: 14px;
                width: 16px;
                text-align: center;
            }

            /* Search Form */
            .search-form {
                position: relative;
                max-width: 300px;
            }

            .search-form .form-control {
                background: rgba(255, 255, 255, 0.15);
                border: 2px solid rgba(255, 255, 255, 0.2);
                color: white;
                padding: 10px 45px 10px 15px;
                border-radius: 25px;
                font-size: 14px;
                backdrop-filter: blur(10px);
                transition: all 0.3s ease;
            }

            .search-form .form-control::placeholder {
                color: rgba(255, 255, 255, 0.7);
            }

            .search-form .form-control:focus {
                background: rgba(255, 255, 255, 0.25);
                border-color: rgba(255, 255, 255, 0.5);
                box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.1);
                color: white;
            }

            .search-form .btn {
                position: absolute;
                right: 5px;
                top: 50%;
                transform: translateY(-50%);
                background: rgba(255, 255, 255, 0.2);
                border: none;
                color: white;
                width: 35px;
                height: 35px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.3s ease;
            }

            .search-form .btn:hover {
                background: rgba(255, 255, 255, 0.3);
            }

            /* Mobile Toggle Button */
            .navbar-toggler {
                border: 2px solid rgba(255, 255, 255, 0.3);
                border-radius: 8px;
                padding: 6px 10px;
            }

            .navbar-toggler:focus {
                box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.1);
            }

            .navbar-toggler-icon {
                background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 0.8%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='m4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
            }

            /* Submenu dropdown */
            .dropdown-submenu {
                position: relative;
            }

            .dropdown-submenu > .dropdown-menu {
                position: absolute;
                top: 0;
                left: 100%;
                margin-top: 0;
                margin-left: 0;
                display: none !important;
                z-index: 99999;
                min-width: 200px;
                background: white;
                border: 1px solid rgba(0,0,0,.15);
                border-radius: 8px;
                box-shadow: 0 6px 12px rgba(0,0,0,.175);
            }

            .dropdown-submenu:hover > .dropdown-menu {
                display: block !important;
            }

            .dropdown-submenu > .dropdown-item::after {
                content: "▶";
                float: right;
                margin-left: 10px;
                font-size: 12px;
                color: #999;
            }

            .dropdown-submenu > .dropdown-item:hover::after {
                color: #dc3545;
            }

            /* Submenu items */
            .dropdown-submenu .dropdown-menu .dropdown-item {
                padding: 8px 16px;
                font-size: 13px;
                color: #333;
            }

            .dropdown-submenu .dropdown-menu .dropdown-item:hover {
                background-color: #dc3545;
                color: white;
            }

            .dropdown-submenu > .dropdown-item {
                position: relative;
                transition: all 0.3s ease;
            }

            .dropdown-submenu > .dropdown-item::after {
                content: "\f054";
                font-family: "Font Awesome 5 Free";
                font-weight: 900;
                margin-left: auto;
                font-size: 11px;
                color: #6c757d;
                transition: all 0.3s ease;
            }

            .dropdown-submenu > .dropdown-item:hover {
                background: linear-gradient(90deg, #f8f9fa 0%, #e9ecef 100%);
                color: #dc3545;
                padding-left: 24px;
                border-radius: 8px;
                margin: 2px 8px;
            }

            .dropdown-submenu > .dropdown-item:hover::after {
                color: #dc3545;
                transform: translateX(2px);
            }

            /* Submenu items styling */
            .dropdown-submenu .dropdown-menu .dropdown-item {
                transition: all 0.3s ease;
                border-radius: 6px;
                margin: 2px 8px;
            }

            .dropdown-submenu .dropdown-menu .dropdown-item:hover {
                background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
                color: white;
                transform: translateX(3px);
                box-shadow: 0 3px 10px rgba(220, 53, 69, 0.3);
            }

            .dropdown-submenu .dropdown-menu .dropdown-item:hover i {
                color: white;
                transform: scale(1.1);
            }

            /* Fix untuk dropdown arrow */
            .dropdown-toggle::after {
                margin-left: auto;
            }

            /* Responsive Design */
            @media (max-width: 991.98px) {
                .navbar-collapse {
                    background: rgba(255, 255, 255, 0.1);
                    backdrop-filter: blur(15px);
                    margin-top: 15px;
                    border-radius: 12px;
                    padding: 20px;
                    border: 1px solid rgba(255, 255, 255, 0.2);
                }

                .navbar-nav {
                    gap: 5px;
                }

                .nav-item .nav-link {
                    margin: 3px 0;
                    justify-content: flex-start;
                }

                .search-form {
                    max-width: 100%;
                    margin-top: 15px;
                }

                /* Responsive submenu */
                .dropdown-submenu > .dropdown-menu {
                    position: static;
                    left: auto;
                    margin-top: 8px;
                    margin-left: 15px;
                    border-left: 3px solid #dc3545;
                    border-radius: 8px;
                    background: linear-gradient(135deg, rgba(248, 249, 250, 0.95) 0%, rgba(233, 236, 239, 0.9) 100%);
                    backdrop-filter: blur(5px);
                    transform: none;
                    opacity: 0;
                    visibility: hidden;
                    max-height: 0;
                    overflow: hidden;
                    transition: all 0.3s ease;
                }

                .dropdown-submenu.active > .dropdown-menu {
                    opacity: 1;
                    visibility: visible;
                    max-height: 200px;
                }

                .dropdown-submenu > .dropdown-menu .dropdown-item {
                    color: rgba(255, 255, 255, 0.95);
                    border-radius: 5px;
                    margin: 2px 8px;
                    padding: 10px 12px;
                    font-size: 13px;
                    position: relative;
                    overflow: hidden;
                }

                .dropdown-submenu > .dropdown-menu .dropdown-item::before {
                    left: -100%;
                }

                .dropdown-submenu > .dropdown-menu .dropdown-item:hover {
                    background: rgba(220, 53, 69, 0.8);
                    color: white;
                    padding-left: 16px;
                    transform: translateX(2px);
                }

                .dropdown-submenu > .dropdown-menu .dropdown-item:hover::before {
                    left: 100%;
                }

                .dropdown-submenu > .dropdown-item::after {
                    content: "\f107";
                    transition: transform 0.3s ease;
                }

                .dropdown-submenu.active > .dropdown-item::after {
                    transform: rotate(180deg);
                }

                .dropdown-menu {
                    position: static;
                    float: none;
                    width: 100%;
                    margin-top: 0;
                    background: transparent;
                    border: none;
                    box-shadow: none;
                }

                .dropdown-item {
                    color: rgba(255, 255, 255, 0.9);
                    border-radius: 6px;
                    margin: 2px 0;
                }

                .dropdown-item:hover {
                    background: rgba(255, 255, 255, 0.15);
                    color: white;
                }
            }

            @media (max-width: 576px) {
                .navbar-brand {
                    font-size: 20px;
                }

                .navbar-brand i {
                    font-size: 22px;
                    padding: 6px;
                }

                .navbar-nav {
                    width: 100%;
                }

                .nav-item {
                    width: 100%;
                }

                .nav-item .nav-link {
                    width: 100%;
                    text-align: left;
                }
            }

            /* Content margin untuk header sticky */
            .content-wrapper {
                margin-top: 0;
                min-height: calc(100vh - 80px);
            }
            
        </style>
    </head>

    <body>
        <!-- Main Header -->
        <header class="main-header">
            <nav class="navbar navbar-expand-lg">
                <div class="container">
                    <!-- Brand -->
                    <a class="navbar-brand" href="<?= $_SERVER['REQUEST_SCHEME'] ?>://<?= $_SERVER['HTTP_HOST'] ?>">
                        <i class="fas fa-database"></i>
                        <span>SIP BANAR</span>
                    </a>

                    <!-- Mobile Toggle Button -->
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                        aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>

                    <!-- Navigation Menu -->
                    <div class="collapse navbar-collapse" id="navbarNav">
                        <ul class="navbar-nav me-auto">
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="rekapDropdown" role="button"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-chart-line"></i>
                                    Rekap Pengadaan
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="rekapDropdown">
                                    <!-- Submenu RUP -->
                                    <li class="dropdown-submenu">
                                        <a class="dropdown-item" href="#" role="button">
                                            <i class="fas fa-folder-open"></i>
                                            RUP
                                        </a>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" href="<?= $_SERVER['REQUEST_SCHEME'] ?>://<?= $_SERVER['HTTP_HOST'] ?>/rekappengadaan/rup/pengadaanlangsung.php">
                                                    <i class="fas fa-bolt"></i>
                                                    Pengadaan Langsung
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="<?= $_SERVER['REQUEST_SCHEME'] ?>://<?= $_SERVER['HTTP_HOST'] ?>/rekappengadaan/rup/swakelola.php">
                                                    <i class="fas fa-people-carry"></i>
                                                    Swakelola
                                                </a>
                                            </li>
                                        </ul>
                                    </li>
                                    <!-- End submenu RUP -->

                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="<?= $_SERVER['REQUEST_SCHEME'] ?>://<?= $_SERVER['HTTP_HOST'] ?>/grafik/grafik_rup.php">
                                            <i class="fas fa-chart-pie"></i>
                                            Grafik
                                        </a>
                                        
                                    </li>
                                </ul>
                            </li>

                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="laporanDropdown" role="button"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-file-alt"></i>
                                    Laporan
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="laporanDropdown">
                                    <li>
                                        <a class="dropdown-item" href="<?= $_SERVER['REQUEST_SCHEME'] ?>://<?= $_SERVER['HTTP_HOST'] ?>/laporan/excel">
                                            <i class="fas fa-file-excel"></i>
                                            Export Excel
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="<?= $_SERVER['REQUEST_SCHEME'] ?>://<?= $_SERVER['HTTP_HOST'] ?>/laporan/pdf">
                                            <i class="fas fa-file-pdf"></i>
                                            Export PDF
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="<?= $_SERVER['REQUEST_SCHEME'] ?>://<?= $_SERVER['HTTP_HOST'] ?>/laporan/print">
                                            <i class="fas fa-print"></i>
                                            Print Laporan
                                        </a>
                                    </li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="<?= $_SERVER['REQUEST_SCHEME'] ?>://<?= $_SERVER['HTTP_HOST'] ?>/laporan/custom">
                                            <i class="fas fa-cog"></i>
                                            Custom Report
                                        </a>
                                    </li>
                                </ul>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link" href="<?= $_SERVER['REQUEST_SCHEME'] ?>://<?= $_SERVER['HTTP_HOST'] ?>/tentang">
                                    <i class="fas fa-info-circle"></i>
                                    Tentang
                                </a>
                            </li>
                        </ul>

                        <!-- Search Form -->
                        <form class="search-form ms-auto" method="GET" action="<?= $_SERVER['REQUEST_SCHEME'] ?>://<?= $_SERVER['HTTP_HOST'] ?>/pencarian">
                            <div class="position-relative">
                                <input class="form-control" type="search" name="q" placeholder="Cari data..."
                                    aria-label="Search" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                                <button class="btn" type="submit" aria-label="Search">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </nav>
        </header>

        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <!-- Content dari file lain akan dimuat di sini -->

        <!-- Bootstrap JS -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle submenu toggle for mobile
            const dropdownSubmenus = document.querySelectorAll('.dropdown-submenu');
            
            dropdownSubmenus.forEach(function(submenu) {
                const submenuLink = submenu.querySelector('.dropdown-item');
                const submenuDropdown = submenu.querySelector('.dropdown-menu');
                
                // Mobile behavior - toggle on click
                submenuLink.addEventListener('click', function(e) {
                    if (window.innerWidth <= 991.98) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        // Close other submenus
                        dropdownSubmenus.forEach(function(otherSubmenu) {
                            if (otherSubmenu !== submenu) {
                                otherSubmenu.classList.remove('active');
                            }
                        });
                        
                        // Toggle current submenu
                        submenu.classList.toggle('active');
                    }
                });
            });
            
            // Close submenus when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.dropdown-submenu')) {
                    dropdownSubmenus.forEach(function(submenu) {
                        submenu.classList.remove('active');
                    });
                }
            });
            
            // Reset submenu state on window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth > 991.98) {
                    dropdownSubmenus.forEach(function(submenu) {
                        submenu.classList.remove('active');
                    });
                }
            });
            
            // Smooth scroll for submenu items
            const submenuItems = document.querySelectorAll('.dropdown-submenu .dropdown-menu .dropdown-item');
            submenuItems.forEach(function(item) {
                item.addEventListener('click', function(e) {
                    // Add ripple effect
                    const ripple = document.createElement('span');
                    ripple.classList.add('ripple-effect');
                    this.appendChild(ripple);
                    
                    setTimeout(() => {
                        ripple.remove();
                    }, 600);
                });
            });
        });
        </script>