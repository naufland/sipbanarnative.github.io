<?php
// ====================================================
// BAGIAN 1: LOGIKA SESI & USER
// ====================================================
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Deteksi parameter logout dari URL
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $_SESSION = array(); // Kosongkan session
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy(); // Hancurkan session
    
    // Redirect ke root domain agar tidak terjadi 404
    header("Location: /index.php"); 
    exit;
}
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$nama_tampil = !empty($_SESSION['full_name']) ? $_SESSION['full_name'] : ($_SESSION['username'] ?? 'Administrator');
$role_tampil = ucfirst($_SESSION['role'] ?? 'ADMIN');
$inisial = urlencode($nama_tampil);

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

    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* --- GLOBAL STYLES --- */
        * {
            font-family: 'Inter', sans-serif;
        }

        body {
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }

        /* --- HEADER MAIN STYLE --- */
        .main-header {
            background: linear-gradient(135deg, rgba(44, 62, 80, 0.88) 0%, rgba(52, 73, 94, 0.88) 100%),
                url('/images/sasirangan balik.webp') center center;
            background-size: cover;
            background-position: 0px -5px;
            color: white;
            padding: 0;
            box-shadow: 0 4px 20px rgba(220, 53, 69, 0.3);
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 3px solid #b21e2f;
        }

        .navbar {
            padding: 8px 0;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 24px;
            color: white !important;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-right: 40px;
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

        /* --- NAVIGATION MENU --- */
        .navbar-nav {
            gap: 15px;
            align-items: center;
        }

        .nav-item .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 500;
            font-size: 14px;
            padding: 8px 0 !important;
            border-radius: 0;
            display: flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            border: none;
            transition: all 0.3s ease;
            white-space: nowrap;
            min-width: fit-content;
            border-bottom: 2px solid transparent;
        }

        .nav-item .nav-link:hover {
            background: transparent;
            color: white !important;
            border-bottom-color: rgba(255, 255, 255, 0.5);
        }

        .nav-item .nav-link.active {
            background: transparent;
            color: white !important;
            font-weight: 600;
            border-bottom-color: white;
        }

        /* --- DROPDOWN DEFAULT --- */
        .dropdown-menu {
            background: white;
            border: 1px solid rgba(0, 0, 0, 0.1);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            margin-top: 5px;
            min-width: 200px;
            z-index: 1020;
        }

        .dropdown-item {
            padding: 10px 16px;
            font-size: 14px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.2s ease;
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

        /* --- GAYA KHUSUS HEADER USER --- */
        .user-dropdown-header {
            background-color: #f1f3f5;
            padding: 15px 20px;
            text-align: center;
            border-bottom: 1px solid #e9ecef;
        }

        /* Avatar Bulat */
        .user-avatar-btn {
            padding: 2px !important;
            border: 2px solid rgba(255, 255, 255, 0.5);
            border-radius: 50%;
            transition: all 0.3s;
            margin-left: 10px;
        }

        .user-avatar-btn:hover {
            border-color: white;
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.3);
        }

        .user-avatar-btn::after {
            display: none !important;
        }

        .user-name {
            font-weight: 700;
            color: #212529;
            font-size: 15px;
            margin-bottom: 6px;
        }

        .user-role-badge {
            background-color: #dc3545;
            color: white;
            padding: 4px 16px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .user-menu-item {
            color: #495057;
            font-weight: 500;
        }

        .user-menu-item i {
            color: #dc3545;
            width: 20px;
            text-align: center;
        }

        .logout-item {
            color: #dc3545 !important;
            font-weight: 700;
        }

        .logout-item:hover {
            background-color: #fff5f5;
        }

        /* --- SUBMENU DROPDOWN --- */
        .dropdown-submenu {
            position: relative;
        }

        .dropdown-submenu>.dropdown-menu {
            position: absolute;
            top: 0;
            left: 100%;
            margin-top: 0;
            margin-left: 2px;
            display: none !important;
            min-width: 180px;
            border-radius: 8px;
        }

        .dropdown-submenu:hover>.dropdown-menu {
            display: block !important;
        }

        .dropdown-submenu>.dropdown-item::after {
            content: "\f054";
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            margin-left: auto;
            font-size: 10px;
            color: #999;
        }

        /* --- MOBILE RESPONSIVE --- */
        .navbar-toggler {
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            padding: 6px 10px;
        }

        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 0.8%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='m4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        @media (max-width: 991.98px) {
            .navbar-collapse {
                background: rgba(44, 62, 80, 0.98);
                backdrop-filter: blur(15px);
                margin-top: 15px;
                border-radius: 12px;
                padding: 20px;
                border: 1px solid rgba(255, 255, 255, 0.2);
            }

            .navbar-nav {
                gap: 5px;
                width: 100%;
            }

            .nav-item .nav-link {
                margin: 3px 0;
                justify-content: flex-start;
                width: 100%;
            }

            .navbar-nav.ms-auto {
                margin-top: 15px;
                border-top: 1px solid rgba(255, 255, 255, 0.2);
                padding-top: 10px;
                margin-left: 0 !important;
            }

            .user-avatar-btn {
                border-radius: 8px;
                width: 100%;
                text-align: left;
                padding: 10px !important;
                display: flex;
                align-items: center;
                gap: 10px;
                background: rgba(0, 0, 0, 0.2);
                border: none;
            }

            .user-avatar-btn::after {
                display: inline-block !important;
                margin-left: auto;
            }

            .dropdown-submenu>.dropdown-menu {
                position: static;
                margin-left: 15px;
                border-left: 3px solid #dc3545;
                background: rgba(255, 255, 255, 0.95);
                opacity: 1;
                display: none !important;
            }

            .dropdown-submenu.active>.dropdown-menu {
                display: block !important;
            }
        }
    </style>
</head>

<body>
    <header class="main-header">
        <nav class="navbar navbar-expand-lg">
            <div class="container">
                <a class="navbar-brand" href="<?= $_SERVER['REQUEST_SCHEME'] ?>://<?= $_SERVER['HTTP_HOST'] ?>">
                    <i class="fas fa-database"></i>
                    <span>SIP BANAR</span>
                </a>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="rekapDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-chart-line"></i> Data PBJ
                            </a>
                            <ul class="dropdown-menu">
                                <li class="dropdown-submenu">
                                    <a class="dropdown-item" href="#"> <i class="fas fa-folder-open"></i> RUP </a>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="/rekappengadaan/rup/pengadaanlangsung.php"><i class="fas fa-bolt"></i> Penyedia</a></li>
                                        <li><a class="dropdown-item" href="/rekappengadaan/rup/swakelola.php"><i class="fas fa-people-carry"></i> Swakelola</a></li>
                                    </ul>
                                </li>
                                <li class="dropdown-submenu">
                                    <a class="dropdown-item" href="#"> <i class="fas fa-folder"></i> Realisasi </a>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="/rekappengadaan/realisasi/tender.php"><i class="fas fa-file-alt"></i> Tender</a></li>
                                        <li><a class="dropdown-item" href="/rekappengadaan/realisasi/seleksi.php"><i class="fas fa-file-alt"></i> Seleksi</a></li>
                                        <li><a class="dropdown-item" href="/rekappengadaan/realisasi/swakelola.php"><i class="fas fa-file-alt"></i> Swakelola</a></li>
                                        <li><a class="dropdown-item" href="/rekappengadaan/realisasi/nontender.php"><i class="fas fa-file-alt"></i> Non Tender</a></li>
                                        <li><a class="dropdown-item" href="/rekappengadaan/realisasi/penunjukanlangsung.php"><i class="fas fa-file-alt"></i> Penunjukkan Langsung</a></li>
                                        <li><a class="dropdown-item" href="/rekappengadaan/realisasi/pencatatan_nontender.php"><i class="fas fa-file-alt"></i> Pencatatan Nontender</a></li>
                                        <li><a class="dropdown-item" href="/rekappengadaan/realisasi/dikecualikan.php"><i class="fas fa-file-alt"></i> Dikecualikan</a></li>
                                        <li><a class="dropdown-item" href="/rekappengadaan/realisasi/pengadaandarurat.php"><i class="fas fa-file-alt"></i> Pengadaan Darurat</a></li>
                                        <li><a class="dropdown-item" href="/rekappengadaan/realisasi/epurchasing.php"><i class="fas fa-file-alt"></i> E-Purchasing</a></li>
                                    </ul>
                                </li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" href="/grafik/rekapitulasi.php"><i class="fas fa-chart-pie"></i> Grafik</a></li>
                            </ul>
                        </li>

                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="rekapPembukaanDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-folder"></i> Rekap Pembukuan
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/rekappembukuan/lppd.php"><i class="fas fa-file-contract"></i> LPPD</a></li>
                                <li><a class="dropdown-item" href="/rekappengadaan/pembukaan/lkpj.php"><i class="fas fa-clipboard-check"></i> LKPJ</a></li>
                            </ul>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" href="/formsatudata/formsatudata.php"><i class="fas fa-file-invoice"></i> Forum Satu Data</a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" href="grafik/import.php"><i class="fas fa-chart-bar"></i> Import</a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" href="/sektoral/sektoral.php"><i class="fas fa-chart-bar"></i> Statistik Sektoral</a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" href="/dokumenonline/dokumen.php"><i class="fas fa-cloud-download-alt"></i> Dokumen Online</a>
                        </li>

                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="vendorDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-building"></i> Vendor Manajemen
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/vendor/daftar"><i class="fas fa-list"></i> Daftar Vendor</a></li>
                                <li><a class="dropdown-item" href="/vendor/verifikasi"><i class="fas fa-check-circle"></i> Verifikasi Vendor</a></li>
                                <li><a class="dropdown-item" href="/vendor/pengadaan"><i class="fas fa-handshake"></i> Sistem Pengadaan</a></li>
                            </ul>
                        </li>

                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="laporanDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-file-alt"></i> Data Khusus PBJ
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/datakhususpbj/datapbj.php"><i class="fas fa-file-excel"></i> Efiesiensi</a></li>
                                <li><a class="dropdown-item" href="/laporan/custom"><i class="fas fa-cog"></i> Custom Report</a></li>
                            </ul>
                        </li>
                    </ul>

                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle user-avatar-btn" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <img src="https://ui-avatars.com/api/?name=<?= $inisial ?>&background=dc3545&color=fff&size=128&bold=true"
                                    alt="User" width="40" height="40" class="rounded-circle">
                                <span class="d-lg-none ms-2 text-white">Menu Pengguna</span>
                            </a>

                            <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown"
                                style="min-width: 220px; padding: 0 !important; overflow: hidden; border-radius: 8px;">

                                <li>
                                    <div class="user-dropdown-header">
                                        <div class="user-name"><?= htmlspecialchars($nama_tampil) ?></div>
                                        <span class="user-role-badge"><?= htmlspecialchars($role_tampil) ?></span>
                                    </div>
                                </li>

                                <li>
                                    <a class="dropdown-item user-menu-item" href="profil_saya.php">
                                        <i class="fas fa-user-circle"></i> Profil Saya
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item user-menu-item" href="pengaturan.php">
                                        <i class="fas fa-cog"></i> Pengaturan
                                    </a>
                                </li>

                                <li>
                                    <hr class="dropdown-divider" style="margin: 0;">
                                </li>

                                <li>
                                    <a class="dropdown-item logout-item" href="?action=logout" onclick="return confirm('Apakah Anda yakin ingin keluar?');">
                                        <i class="fas fa-sign-out-alt"></i> Keluar / Logout
                                    </a>
                                </li>
                            </ul>
                        </li>
                    </ul>

                </div>
            </div>
        </nav>
    </header>

    <div class="content-wrapper">
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dropdownSubmenus = document.querySelectorAll('.dropdown-submenu');
            dropdownSubmenus.forEach(function(submenu) {
                const submenuLink = submenu.querySelector('.dropdown-item');
                submenuLink.addEventListener('click', function(e) {
                    if (window.innerWidth <= 991.98) {
                        e.preventDefault();
                        e.stopPropagation();
                        const parentUl = submenu.parentElement;
                        parentUl.querySelectorAll('.dropdown-submenu').forEach(function(otherSubmenu) {
                            if (otherSubmenu !== submenu) otherSubmenu.classList.remove('active');
                        });
                        submenu.classList.toggle('active');
                    }
                });
            });

            document.addEventListener('click', function(e) {
                if (!e.target.closest('.nav-item.dropdown')) {
                    dropdownSubmenus.forEach(function(submenu) {
                        submenu.classList.remove('active');
                    });
                }
            });
        });
    </script>
</body>

</html>