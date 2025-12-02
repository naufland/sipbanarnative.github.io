<?php
// ====================================================
// HEADER KHUSUS LOGIN (TAMPILAN ADMIN/OPERATOR)
// ====================================================

// 1. Ambil Data User dari Session
$nama_tampil = !empty($_SESSION['full_name']) ? $_SESSION['full_name'] : ($_SESSION['username'] ?? 'Admin');
$role_tampil = ucfirst($_SESSION['role'] ?? 'User');
// Ambil inisial untuk avatar (misal: "Admin Super" -> "AS")
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
        * { font-family: 'Inter', sans-serif; }
        body { margin: 0; padding: 0; background-color: #f8f9fa; }

        /* HEADER GRADASI MERAH */
        .main-header {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white; padding: 0;
            box-shadow: 0 4px 20px rgba(220, 53, 69, 0.3);
            position: sticky; top: 0; z-index: 1000;
            border-bottom: 3px solid #b21e2f;
        }

        .navbar { padding: 6px 0; } /* Padding diperkecil sedikit biar compact */

        .navbar-brand {
            font-weight: 700; font-size: 22px; color: white !important; text-decoration: none;
            display: flex; align-items: center; gap: 10px; margin-right: 30px;
        }
        .navbar-brand i {
            font-size: 24px; background: rgba(255, 255, 255, 0.15); padding: 8px; border-radius: 8px; backdrop-filter: blur(10px);
        }

        /* MENU NAVIGATION */
        .navbar-nav { gap: 5px; align-items: center; }
        
        .nav-item .nav-link {
            color: rgba(255, 255, 255, 0.9) !important; font-weight: 500; font-size: 13px;
            padding: 8px 12px !important; border-radius: 6px; display: flex; align-items: center; gap: 6px;
            transition: all 0.2s ease; white-space: nowrap;
        }
        .nav-item .nav-link:hover { background: rgba(255,255,255,0.1); color: white !important; }
        .nav-item .nav-link.active { background: rgba(255,255,255,0.2); color: white !important; font-weight: 600; }

        /* DROPDOWN MENU */
        .dropdown-menu {
            border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.15); border-radius: 10px; margin-top: 8px; padding: 8px 0;
        }
        .dropdown-item { padding: 8px 20px; font-size: 13px; color: #444; }
        .dropdown-item:hover { background: #fef2f2; color: #dc3545; }
        .dropdown-item i { width: 20px; text-align: center; margin-right: 5px; }

        /* SUBMENU (NESTED DROPDOWN) */
        .dropdown-submenu { position: relative; }
        .dropdown-submenu>.dropdown-menu {
            top: 0; left: 100%; margin-top: -5px; margin-left: 0; display: none;
        }
        .dropdown-submenu:hover>.dropdown-menu { display: block; }
        .dropdown-submenu>.dropdown-item::after {
            content: "\f054"; font-family: "Font Awesome 5 Free"; font-weight: 900; margin-left: auto; font-size: 10px; opacity: 0.5;
        }

        /* --- STYLE KHUSUS AVATAR PROFILE (BULAT) --- */
        .user-avatar-btn {
            padding: 2px !important; /* Reset padding link */
            border-radius: 50%;
            border: 2px solid rgba(255,255,255,0.5);
            transition: all 0.3s;
            margin-left: 10px;
        }
        .user-avatar-btn:hover {
            border-color: white;
            box-shadow: 0 0 10px rgba(255,255,255,0.3);
        }
        .user-avatar-img {
            width: 36px; height: 36px; border-radius: 50%; object-fit: cover;
        }
        /* Hilangkan panah dropdown di avatar */
        .user-avatar-btn::after { display: none !important; }

        /* INFO USER DI DALAM DROPDOWN */
        .user-info-header {
            padding: 15px 20px; background: #f8f9fa; border-bottom: 1px solid #eee; text-align: center; margin-bottom: 5px;
        }
        .user-name { font-weight: 700; color: #333; font-size: 14px; margin-bottom: 2px; }
        .user-role { font-size: 11px; color: #777; text-transform: uppercase; letter-spacing: 0.5px; background: #e9ecef; padding: 2px 8px; border-radius: 10px; display: inline-block; }

        /* MOBILE RESPONSIVE */
        .navbar-toggler { border: none; color: white; font-size: 20px; }
        .navbar-toggler:focus { box-shadow: none; }
        
        @media (max-width: 991.98px) {
            .navbar-collapse {
                background: white; margin-top: 15px; border-radius: 10px; padding: 15px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            }
            .nav-item .nav-link { color: #333 !important; justify-content: flex-start; }
            .nav-item .nav-link:hover { background: #f8f9fa; color: #dc3545 !important; }
            
            /* Menu Import di Mobile jadi merah biar beda */
            .text-warning { color: #dc3545 !important; font-weight: bold; }

            /* Fix Submenu Mobile */
            .dropdown-submenu>.dropdown-menu { position: static; margin-left: 20px; border: none; box-shadow: none; padding-left: 0; }
            
            /* Profile di Mobile */
            .user-avatar-btn { margin-left: 0; border-color: #dc3545; display: flex; align-items: center; gap: 10px; border-radius: 8px; padding: 8px !important; border: none; }
            .user-avatar-btn::after { display: inline-block !important; margin-left: auto; color: #333; } 
        }

        .content-wrapper { min-height: calc(100vh - 70px); }
    </style>
</head>

<body>
    <header class="main-header">
        <nav class="navbar navbar-expand-xl"> 
            <div class="container-fluid px-4">
                
                <a class="navbar-brand" href="index.php">
                    <i class="fas fa-database"></i>
                    <span>SIP BANAR</span>
                </a>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <i class="fas fa-bars"></i>
                </button>

                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle text-warning" href="#" data-bs-toggle="dropdown">
                                <i class="fas fa-file-import"></i> Import
                            </a>
                            <ul class="dropdown-menu">  
                                <li><a class="dropdown-item" href="import/import_rup.php">Import RUP</a></li>
                                <li><a class="dropdown-item" href="import/import_realisasi.php">Import Realisasi</a></li>
                            </ul>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle text-warning" href="#" data-bs-toggle="dropdown">
                                <i class="fas fa-file-import"></i> Dokumen Onlune
                            </a>
                            
                        </li>

                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"><i class="fas fa-chart-line"></i> Data PBJ</a>
                            <ul class="dropdown-menu">
                                <li class="dropdown-submenu">
                                    <a class="dropdown-item" href="#"> RUP </a>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="rekappengadaan/rup/pengadaanlangsung.php">Penyedia</a></li>
                                        <li><a class="dropdown-item" href="rekappengadaan/rup/swakelola.php">Swakelola</a></li>
                                    </ul>
                                </li>
                                <li class="dropdown-submenu">
                                    <a class="dropdown-item" href="#"> Realisasi </a>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="rekappengadaan/realisasi/tender.php">Tender</a></li>
                                        <li><a class="dropdown-item" href="rekappengadaan/realisasi/nontender.php">Non Tender</a></li>
                                        <li><a class="dropdown-item" href="rekappengadaan/realisasi/epurchasing.php">E-Purchasing</a></li>
                                        </ul>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="grafik/rekapitulasi.php">Grafik</a></li>
                            </ul>
                        </li>

                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"><i class="fas fa-folder"></i> Pembukuan</a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="rekappembukuan/lppd.php">LPPD</a></li>
                                <li><a class="dropdown-item" href="rekappengadaan/pembukaan/lkpj.php">LKPJ</a></li>
                            </ul>
                        </li>

                        <li class="nav-item"><a class="nav-link" href="formsatudata/formsatudata.php"><i class="fas fa-file-invoice"></i> Satu Data</a></li>
                        <li class="nav-item"><a class="nav-link" href="data/statistik-sektoral"><i class="fas fa-chart-bar"></i> Statistik</a></li>
                        
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"><i class="fas fa-building"></i> Vendor</a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="vendor/daftar">Daftar Vendor</a></li>
                                <li><a class="dropdown-item" href="vendor/verifikasi">Verifikasi</a></li>
                            </ul>
                        </li>

                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"><i class="fas fa-file-alt"></i> Data Khusus</a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="datakhususpbj/datapbj.php">Efisiensi</a></li>
                                <li><a class="dropdown-item" href="laporan/custom">Custom Report</a></li>
                            </ul>
                        </li>
                    </ul>

                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle user-avatar-btn" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <img src="https://ui-avatars.com/api/?name=<?= $inisial ?>&background=fff&color=dc3545&bold=true&size=128" 
                                     alt="User" class="user-avatar-img">
                            </a>
                            
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown" style="min-width: 240px;">
                                <li>
                                    <div class="user-info-header">
                                        <div class="user-name"><?= htmlspecialchars($nama_tampil) ?></div>
                                        <div class="user-role"><?= htmlspecialchars($role_tampil) ?></div>
                                    </div>
                                </li>
                                <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-circle"></i> Profil Saya</a></li>
                                <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog"></i> Pengaturan</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger fw-bold" href="api/logout.php">
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
        <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
        
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('.dropdown-submenu .dropdown-item').forEach(function(element) {
                    element.addEventListener('click', function(e) {
                        // Hanya jalankan logic ini di desktop jika perlu, atau biarkan default
                        if (window.innerWidth < 1200) { 
                            // Fix untuk mobile/tablet agar submenu terbuka
                            var nextEl = this.nextElementSibling;
                            if(nextEl && nextEl.classList.contains('dropdown-menu')){
                                e.preventDefault();
                                e.stopPropagation();
                                nextEl.style.display = (nextEl.style.display === 'block') ? 'none' : 'block';
                            }
                        }
                    });
                });
            });
        </script>