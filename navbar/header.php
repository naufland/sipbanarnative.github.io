<?php
// header.php
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'SIP BANAR'; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .navbar-custom {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            box-shadow: 0 2px 4px rgba(0,0,0,.1);
        }
        
        .navbar-brand {
            font-weight: bold;
            color: white !important;
            font-size: 1.5rem;
        }
        
        .navbar-nav .nav-link {
            color: white !important;
            font-weight: 500;
            padding: 10px 15px !important;
            border-radius: 5px;
            margin: 0 5px;
            transition: all 0.3s ease;
        }
        
        .navbar-nav .nav-link:hover {
            background-color: rgba(255,255,255,0.1);
            transform: translateY(-1px);
        }
        
        .navbar-nav .nav-link.active {
            background-color: rgba(255,255,255,0.2);
        }
        
        .search-container {
            position: relative;
        }
        
        .search-input {
            border: none;
            border-radius: 25px;
            padding: 8px 40px 8px 15px;
            width: 250px;
            transition: all 0.3s ease;
        }
        
        .search-input:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(255,255,255,0.3);
            width: 300px;
        }
        
        .search-btn {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #dc3545;
            padding: 5px 10px;
        }
        
        .dropdown-menu {
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,.1);
            border-radius: 10px;
        }
        
        .dropdown-item {
            padding: 10px 20px;
            transition: all 0.3s ease;
        }
        
        .dropdown-item:hover {
            background-color: #dc3545;
            color: white;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container">
            <!-- Brand/Logo -->
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-database me-2"></i>
                SIP BANAR
            </a>

            <!-- Mobile Toggle Button -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Navigation Items -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <!-- Rekap Pengadaan Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-chart-line me-1"></i>
                            Rekap Pengadaan
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="rekap-harian.php">
                                <i class="fas fa-calendar-day me-2"></i>Rekap Harian
                            </a></li>
                            <li><a class="dropdown-item" href="rekap-bulanan.php">
                                <i class="fas fa-calendar-alt me-2"></i>Rekap Bulanan
                            </a></li>
                            <li><a class="dropdown-item" href="rekap-tahunan.php">
                                <i class="fas fa-calendar me-2"></i>Rekap Tahunan
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="rekap-semua.php">
                                <i class="fas fa-list me-2"></i>Semua Rekap
                            </a></li>
                        </ul>
                    </li>

                    <!-- Kategori Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-tags me-1"></i>
                            Kategori
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="kategori-alat-tulis.php">
                                <i class="fas fa-pen me-2"></i>Alat Tulis
                            </a></li>
                            <li><a class="dropdown-item" href="kategori-elektronik.php">
                                <i class="fas fa-laptop me-2"></i>Elektronik
                            </a></li>
                            <li><a class="dropdown-item" href="kategori-furniture.php">
                                <i class="fas fa-chair me-2"></i>Furniture
                            </a></li>
                            <li><a class="dropdown-item" href="kategori-konsumsi.php">
                                <i class="fas fa-coffee me-2"></i>Konsumsi
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="kelola-kategori.php">
                                <i class="fas fa-cog me-2"></i>Kelola Kategori
                            </a></li>
                        </ul>
                    </li>

                    <!-- Laporan Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-file-alt me-1"></i>
                            Laporan
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="laporan-pengadaan.php">
                                <i class="fas fa-shopping-cart me-2"></i>Laporan Pengadaan
                            </a></li>
                            <li><a class="dropdown-item" href="laporan-stok.php">
                                <i class="fas fa-warehouse me-2"></i>Laporan Stok
                            </a></li>
                            <li><a class="dropdown-item" href="laporan-keuangan.php">
                                <i class="fas fa-money-bill me-2"></i>Laporan Keuangan
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="cetak-laporan.php">
                                <i class="fas fa-print me-2"></i>Cetak Laporan
                            </a></li>
                        </ul>
                    </li>

                    <!-- Tentang -->
                    <li class="nav-item">
                        <a class="nav-link" href="tentang.php">
                            <i class="fas fa-info-circle me-1"></i>
                            Tentang
                        </a>
                    </li>
                </ul>

                <!-- Search Form -->
                <form class="d-flex search-container">
                    <input class="form-control search-input" type="search" placeholder="Cari data..." aria-label="Search">
                    <button class="search-btn" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    <script>
        // Active menu highlighting
        document.addEventListener('DOMContentLoaded', function() {
            const currentPage = window.location.pathname.split('/').pop();
            const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
            
            navLinks.forEach(link => {
                if (link.getAttribute('href') === currentPage) {
                    link.classList.add('active');
                }
            });
        });

        // Search functionality
        document.querySelector('.search-container form').addEventListener('submit', function(e) {
            e.preventDefault();
            const searchTerm = document.querySelector('.search-input').value;
            if (searchTerm.trim()) {
                // Redirect to search page or perform search
                window.location.href = `search.php?q=${encodeURIComponent(searchTerm)}`;
            }
        });
    </script>