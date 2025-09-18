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
            }

            .dropdown-item {
                padding: 12px 20px;
                font-size: 14px;
                color: #2c3e50;
                display: flex;
                align-items: center;
                gap: 10px;
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

            /* Responsive Design */
            @media (max-width: 991.98px) {
                .navbar-collapse {
                    background: rgba(255, 255, 255, 0.1);
                    backdrop-filter: blur(15px);
                    margin-top: 15px;
                    border-radius: 12px;
                    padding: 20px;
                }

                .navbar-nav {
                    gap: 5px;
                }

                .nav-item .nav-link {
                    margin: 3px 0;
                }

                .search-form {
                    max-width: 100%;
                    margin-top: 15px;
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
            }

            /* Content margin untuk header sticky */
            .content-wrapper {
                margin-top: 0;
                min-height: calc(100vh - 80px);
            }

            /* Submenu dropdown */
            .dropdown-submenu {
                position: relative;
            }

            .dropdown-submenu>.dropdown-menu {
                top: 0;
                left: 100%;
                margin-top: -5px;
                border-radius: 10px;
                display: none;
            }

            .dropdown-submenu:hover > .dropdown-menu {
                display: block;
            }

            .dropdown-submenu > .dropdown-item::after {
                content: "\f054";
                font-family: "Font Awesome 5 Free";
                font-weight: 900;
                margin-left: auto;
                font-size: 12px;
            }

            /* Responsive submenu */
            @media (max-width: 991.98px) {
                .dropdown-submenu > .dropdown-menu {
                    position: static;
                    left: auto;
                    margin-top: 0;
                    margin-left: 20px;
                    border-left: 2px solid #dc3545;
                    border-radius: 0;
                    box-shadow: none;
                    background: rgba(248, 249, 250, 0.5);
                }
            }
            
        </style>
    </head>

    <body>
        <!-- Bootstrap JS -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
        <!-- Main Header -->
        <header class="main-header">
            <nav class="navbar navbar-expand-lg">
                <div class="container">
                    <!-- Brand -->
                    <a class="navbar-brand" href="<?= isset($_SERVER['HTTP_HOST']) ? '//' . $_SERVER['HTTP_HOST'] : '/' ?>">
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
                                    <!-- Tambahan submenu RUP -->
                                    <li class="dropdown-submenu">
                                        <a class="dropdown-item dropdown-toggle" href="#">
                                            <i class="fas fa-folder-open"></i>
                                            RUP
                                        </a>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" href="rekappengadaan/rup/pengadaanlangsung.php"">
                                                    <i class="fas fa-bolt"></i>
                                                    Pengadaan Langsung
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="rekappengadaan/rup/swakelola.php">
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
                                        <a class="dropdown-item" href="/rekap/analisis">
                                            <i class="fas fa-chart-pie"></i>
                                            Analisis Data
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
                                        <a class="dropdown-item" href="/laporan/excel">
                                            <i class="fas fa-file-excel"></i>
                                            Export Excel
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="/laporan/pdf">
                                            <i class="fas fa-file-pdf"></i>
                                            Export PDF
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="/laporan/print">
                                            <i class="fas fa-print"></i>
                                            Print Laporan
                                        </a>
                                    </li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="/laporan/custom">
                                            <i class="fas fa-cog"></i>
                                            Custom Report
                                        </a>
                                    </li>
                                </ul>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link" href="/tentang">
                                    <i class="fas fa-info-circle"></i>
                                    Tentang
                                </a>
                            </li>
                        </ul>

                        <!-- Search Form -->
                        <form class="search-form ms-auto" method="GET" action="/pencarian">
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
            <!-- Content akan dimuat di sini -->