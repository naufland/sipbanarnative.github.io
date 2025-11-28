<?php
// ---------------------------------------------------------
// BARIS PERTAMA WAJIB SESSION_START()
// ---------------------------------------------------------
session_start(); 

// Debugging Sementara (Hapus baris ini nanti jika sudah fix)
// echo "Status Login: " . (isset($_SESSION['user_id']) ? "Sudah Login (ID: ".$_SESSION['user_id'].")" : "Belum Login");

// =========================================================
// 1. LOGIKA PEMILIHAN HEADER
// =========================================================
$page_title = "Dashboard SIP BANAR";

// Cek apakah user sudah login?
if (isset($_SESSION['user_id'])) {
    // JIKA SUDAH LOGIN -> Panggil Header Admin
    include 'navbar/header_login.php'; 
} else {
    // JIKA BELUM LOGIN -> Panggil Header Tamu
    include 'navbar/header.php';
}

    // -------------------------------
    if (file_exists('config/database.php')) {
        include 'config/database.php';
    } elseif (file_exists('koneksi.php')) {
        include 'koneksi.php';
    } else {
        // Coba path alternatif
        if (file_exists('../config/database.php')) {
            include '../config/database.php';
        } elseif (file_exists('../koneksi.php')) {
            include '../koneksi.php';
        }
    }

    // Buat koneksi MySQLi
    if (!isset($conn) || $conn === null) {
        if (isset($host) && isset($dbname) && isset($username)) {
            $conn = mysqli_connect($host, $username, $password, $dbname);
            if (!$conn) {
                die("Koneksi database gagal: " . mysqli_connect_error());
            }
            mysqli_set_charset($conn, "utf8mb4");
        } 
    }

    // Fungsi Helper
    function getTableCount($tableName) {
        global $conn;
        if (!isset($conn) || $conn === null) return 0;
        try {
            $query = "SELECT COUNT(*) as total FROM `$tableName`";
            $result = mysqli_query($conn, $query);
            if ($result) {
                $row = mysqli_fetch_assoc($result);
                return intval($row['total']);
            }
            return 0;
        } catch (Exception $e) { return 0; }
    }

    // Hitung Data
    $totalKeseluruhan = getTableCount('rup_keseluruhan');
    $totalSwakelolaRUP = getTableCount('rup_swakelola');
    $totalRUP = $totalKeseluruhan + $totalSwakelolaRUP;

    $realisasiDikecualikan = getTableCount('realisasi_dikecualikan');
    $realisasiEpurchasing = getTableCount('realisasi_epurchasing');
    $realisasiNontender = getTableCount('realisasi_nontender');
    $realisasiPengadaanLangsung = getTableCount('realisasi_pengadaanlangsung');
    $realisasiPenunjukanLangsung = getTableCount('realisasi_penunjukanlangsung');
    $realisasiSeleksi = getTableCount('realisasi_seleksi');
    $realisasiTender = getTableCount('realisasi_tender');
    $pencatatanNontender = getTableCount('pencatatan_nontender');

    $totalRealisasi = $realisasiDikecualikan + $realisasiEpurchasing + $realisasiNontender + 
                    $realisasiPengadaanLangsung + $realisasiPenunjukanLangsung + 
                    $realisasiSeleksi + $realisasiTender + $pencatatanNontender;

    $totalSwakelolaRealisasi = getTableCount('realisasi_swakelola');
    $totalDataSet = 11;
    ?>

    <script src="../../js/submenu.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <style>
        /* Reset & Base */
        body {
            margin: 0; padding: 0;
            font-family: 'Segoe UI', 'Roboto', sans-serif;
            background: #f8f9fa;
            overflow-x: hidden;
        }

        /* --- HERO SECTION --- */
        .hero-section {
            position: relative;
            min-height: 90vh; /* Tinggi Hero */
            display: flex;
            align-items: center; /* Vertikal Tengah */
            justify-content: flex-start; /* Horizontal Kiri */
            overflow: hidden;
            padding: 0;
        }

        /* Background Slider - Full Screen */
        .background-slider {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 0;
        }

        .slider-image {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            object-fit: cover; /* Gambar Full tanpa gepeng */
            /* BERIKUT INI PERUBAHANNYA: */
            /* Menggeser fokus gambar 20px ke kiri, sehingga gambar terlihat bergeser ke kanan */
            object-position: calc(50% - 20px) center; 
            
            opacity: 0; transform: scale(1);
            transition: opacity 1.5s ease-in-out, transform 6s linear;
        }
        .slider-image.active { opacity: 1; transform: scale(1.1); }

        /* OVERLAY GRADASI (DIKEMBALIKAN & DIPERPENDEK) */
        .overlay {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            /* Gradasi diperpendek agar gambar lebih terlihat */
            background: linear-gradient(90deg, 
                #ffffff 0%, 
                #ffffff 25%,  /* Putih solid hanya sampai 25% */
                rgba(255,255,255,0.7) 45%, /* Mulai pudar lebih cepat */
                rgba(255,255,255,0.0) 65%); /* Transparan penuh pada 65% */
            z-index: 1;
        }

        /* Container Konten (Kembali Transparan) */
        .hero-container {
            position: relative;
            z-index: 2;
            /* Ukuran & Posisi kembali seperti semula agar pas dengan overlay */
            width: 100%;
            max-width: 1400px;
            padding: 40px 5%; /* Jarak teks dari pinggir kiri */
            
            /* Latar belakang dan shadow dihapus karena sudah ada overlay */
            background: transparent;
            box-shadow: none;
            border-radius: 0;
            
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            text-align: left;
        }

        .logo-pemko { height: 80px; width: auto; margin-bottom: 20px; }
        
        .main-title {
            font-size: 3rem; font-weight: 800; color: #1a1a1a;
            line-height: 1.1; margin: 0 0 15px 0; letter-spacing: -1px;
        }
        .main-title .highlight { color: #dc3545; display: block; }

        .subtitle {
            font-size: 1rem; color: #555; line-height: 1.6;
            margin-bottom: 30px; max-width: 600px; /* Batasi lebar teks deskripsi */
        }

        /* Hero Stats Grid */
        .stats-grid {
            display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;
            width: 100%; max-width: 650px; /* Batasi lebar grid statistik */
        }

        .stat-card {
            background: #ffffff; /* Kartu tetap putih solid */
            padding: 20px; border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            border-left: 4px solid #dc3545;
        }
        
        .stat-label { font-size: 0.75rem; color: #666; font-weight: 700; text-transform: uppercase; margin-bottom: 5px; }
        .stat-number { font-size: 2rem; font-weight: 800; color: #dc3545; line-height: 1; }

        /* Slider Dots */
        .slider-dots {
            position: absolute; bottom: 30px; left: 50%; transform: translateX(-50%);
            display: flex; gap: 12px; z-index: 3;
        }
        .slider-dot { width: 12px; height: 12px; border-radius: 50%; background: rgba(0,0,0,0.3); cursor: pointer; transition: all 0.3s; }
        .slider-dot.active { background: #dc3545; width: 30px; border-radius: 10px; }

        /* --- DATA SECTION (GRID BAWAH) --- */
        .data-section {
            padding: 80px 5%; background: #ffffff; max-width: 1400px; margin: 0 auto;
        }
        .section-header {
            display: flex; justify-content: space-between; align-items: flex-end;
            margin-bottom: 40px; border-bottom: 2px solid #f1f1f1; padding-bottom: 20px;
        }
        .section-title h2 { font-size: 2rem; color: #1a1a1a; margin: 0; }
        .section-title p { color: #6c757d; margin: 5px 0 0 0; }

        .data-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 25px;
        }
        .data-item {
            background: #fff; border: 1px solid #eee; border-radius: 12px;
            padding: 25px; text-align: center; text-decoration: none; color: inherit;
            transition: all 0.3s;
        }
        .data-item:hover {
            border-color: #dc3545; transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(220, 53, 69, 0.1);
        }
        .data-item i { font-size: 2.5rem; color: #dc3545; margin-bottom: 15px; opacity: 0.9; }
        .data-item h4 { margin: 10px 0; font-size: 1.1rem; color: #333; }
        .data-item .count { font-size: 1.8rem; font-weight: 700; color: #dc3545; }

        /* Responsive */
        @media (max-width: 992px) {
            .main-title { font-size: 2.5rem; }
            /* Di layar kecil, gradasi sedikit lebih lebar agar teks terbaca */
            .overlay { background: linear-gradient(90deg, #fff 30%, rgba(255,255,255,0) 80%); }
        }
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>

    <section class="hero-section">
        <div class="background-slider">
            <img src="../images/WALI.png" class="slider-image active" alt="Foto 1">
            <img src="../images/foto2.jpg" class="slider-image" alt="Foto 2">
            <img src="../images/foto3.png" class="slider-image" alt="Foto 3">
            <img src="../images/PEMKO 2.jpg" class="slider-image" alt="PEMKO 2">
            <img src="../images/PEMKO1.jpg" class="slider-image" alt="PEMKO 1">
        </div>

        <div class="overlay"></div>

        <div class="slider-dots">
            <span class="slider-dot active" data-slide="0"></span>
            <span class="slider-dot" data-slide="1"></span>
            <span class="slider-dot" data-slide="2"></span>
            <span class="slider-dot" data-slide="3"></span>
            <span class="slider-dot" data-slide="4"></span>
        </div>

        <div class="hero-container">
            <img src="https://bagianpbj.sidoarjokab.go.id/public/uploads/settings/thumbs/1639041632_982896673090d9d74e9c.png" alt="Logo Pemko" class="logo-pemko">
            
            <h1 class="main-title">
                Dashboard SIPBANAR
                <span class="highlight">Dalam Satu Portal</span>
            </h1>
            <p class="subtitle">
                Sistem Informasi Pengadaan Banjarmasin. Transparansi data pengadaan untuk pembangunan kota yang lebih baik dan akuntabel.
            </p>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Paket RUP</div>
                    <div class="stat-number" data-target="<?php echo $totalRUP; ?>"><?php echo number_format($totalRUP, 0, ',', '.'); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Realisasi</div>
                    <div class="stat-number" data-target="<?php echo $totalRealisasi; ?>"><?php echo number_format($totalRealisasi, 0, ',', '.'); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Swakelola RUP</div>
                    <div class="stat-number" data-target="<?php echo $totalSwakelolaRUP; ?>"><?php echo number_format($totalSwakelolaRUP, 0, ',', '.'); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Swakelola Realisasi</div>
                    <div class="stat-number" data-target="<?php echo $totalSwakelolaRealisasi; ?>"><?php echo number_format($totalSwakelolaRealisasi, 0, ',', '.'); ?></div>
                </div>
            </div>
        </div>
    </section>

    <section class="data-section">
        <div class="section-header">
            <div class="section-title">
                <h2>Kategori Data Pengadaan</h2>
                <p>Rincian data berdasarkan jenis paket dan metode pengadaan.</p>
            </div>
            <div><strong><?php echo $totalDataSet; ?></strong> Dataset Terhubung</div>
        </div>

        <div class="data-grid">
            <a href="rekappengadaan/rup/rup_keseluruhan.php" class="data-item">
                <i class="fas fa-file-contract"></i>
                <h4>RUP Keseluruhan</h4>
                <div class="count" data-count="<?php echo $totalKeseluruhan; ?>"><?php echo number_format($totalKeseluruhan, 0, ',', '.'); ?></div>
            </a>
            <a href="rekappengadaan/rup/rup_swakelola.php" class="data-item">
                <i class="fas fa-people-carry"></i>
                <h4>RUP Swakelola</h4>
                <div class="count" data-count="<?php echo $totalSwakelolaRUP; ?>"><?php echo number_format($totalSwakelolaRUP, 0, ',', '.'); ?></div>
            </a>
            <a href="rekappengadaan/realisasi/realisasi_tender.php" class="data-item">
                <i class="fas fa-gavel"></i>
                <h4>Realisasi Tender</h4>
                <div class="count" data-count="<?php echo $realisasiTender; ?>"><?php echo number_format($realisasiTender, 0, ',', '.'); ?></div>
            </a>
            <a href="rekappengadaan/realisasi/realisasi_nontender.php" class="data-item">
                <i class="fas fa-hand-holding-usd"></i>
                <h4>Non Tender</h4>
                <div class="count" data-count="<?php echo $realisasiNontender; ?>"><?php echo number_format($realisasiNontender, 0, ',', '.'); ?></div>
            </a>
            <a href="rekappengadaan/realisasi/realisasi_epurchasing.php" class="data-item">
                <i class="fas fa-shopping-cart"></i>
                <h4>E-Purchasing</h4>
                <div class="count" data-count="<?php echo $realisasiEpurchasing; ?>"><?php echo number_format($realisasiEpurchasing, 0, ',', '.'); ?></div>
            </a>
            <a href="rekappengadaan/realisasi/realisasi_swakelola.php" class="data-item">
                <i class="fas fa-users-cog"></i>
                <h4>Realisasi Swakelola</h4>
                <div class="count" data-count="<?php echo $totalSwakelolaRealisasi; ?>"><?php echo number_format($totalSwakelolaRealisasi, 0, ',', '.'); ?></div>
            </a>
            <a href="rekappengadaan/pencatatan/pencatatan_nontender.php" class="data-item">
                <i class="fas fa-clipboard-list"></i>
                <h4>Pencatatan Non Tender</h4>
                <div class="count" data-count="<?php echo $pencatatanNontender; ?>"><?php echo number_format($pencatatanNontender, 0, ',', '.'); ?></div>
            </a>
            <a href="rekappengadaan/realisasi/realisasi_penunjukanlangsung.php" class="data-item">
                <i class="fas fa-hand-point-right"></i>
                <h4>Penunjukan Langsung</h4>
                <div class="count" data-count="<?php echo $realisasiPenunjukanLangsung; ?>"><?php echo number_format($realisasiPenunjukanLangsung, 0, ',', '.'); ?></div>
            </a>
        </div>
    </section>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // SLIDER
        const slides = document.querySelectorAll('.slider-image');
        const dots = document.querySelectorAll('.slider-dot');
        let currentSlide = 0;
        const slideInterval = 5000;

        function showSlide(index) {
            slides.forEach(s => s.classList.remove('active'));
            dots.forEach(d => d.classList.remove('active'));
            if (index >= slides.length) currentSlide = 0;
            else if (index < 0) currentSlide = slides.length - 1;
            else currentSlide = index;
            slides[currentSlide].classList.add('active');
            dots[currentSlide].classList.add('active');
        }

        let slideTimer = setInterval(() => showSlide(currentSlide + 1), slideInterval);
        dots.forEach((dot, idx) => {
            dot.addEventListener('click', () => {
                clearInterval(slideTimer);
                showSlide(idx);
                slideTimer = setInterval(() => showSlide(currentSlide + 1), slideInterval);
            });
        });

        // COUNTER ANIMATION
        const animateValue = (obj, start, end, duration) => {
            if (!obj) return;
            let startTimestamp = null;
            const step = (timestamp) => {
                if (!startTimestamp) startTimestamp = timestamp;
                const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                obj.innerHTML = new Intl.NumberFormat('id-ID').format(Math.floor(progress * (end - start) + start));
                if (progress < 1) window.requestAnimationFrame(step);
            };
            window.requestAnimationFrame(step);
        };

        const statsObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const targetEl = entry.target;
                    const rawVal = targetEl.getAttribute('data-target') || targetEl.getAttribute('data-count');
                    const endVal = parseInt(rawVal);
                    if (!isNaN(endVal) && endVal > 0) {
                        animateValue(targetEl, 0, endVal, 1500);
                    }
                    observer.unobserve(targetEl);
                }
            });
        }, { threshold: 0.5 });

        document.querySelectorAll('.stat-number, .count').forEach(el => statsObserver.observe(el));
    });
    </script>

    <?php include 'navbar/footer.php'; ?>