<?php
// Set page title untuk header
$page_title = "SIP BANAR - Sistem Informasi Pengadaan Barang dan Jasa";

// Include header
include 'navbar/header.php';
?>
<script src="js/submenu.js"></script><!-- Bootstrap JS harus dimuat dulu -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<!-- Kemudian submenu script -->

<!-- Custom CSS khusus untuk halaman index -->
<style>
    /* Override beberapa style yang konflik dengan header */
    .dropdown-item {
        padding: 12px 20px;
        /* Konsisten dengan header */
        color: #2c3e50;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .dropdown-item:hover {
        background: #f8f9fa;
        color: #dc3545;
        transform: translateX(5px);
    }

    /* Hero Section */
    .hero {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
        padding: 80px 0 60px 0;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .hero::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-image:
            radial-gradient(circle at 20% 20%, rgba(255, 255, 255, 0.1) 20%, transparent 21%),
            radial-gradient(circle at 80% 80%, rgba(255, 255, 255, 0.1) 15%, transparent 16%),
            radial-gradient(circle at 40% 60%, rgba(255, 255, 255, 0.05) 25%, transparent 26%);
        pointer-events: none;
    }

    .hero-content {
        position: relative;
        z-index: 2;
    }

    .hero h1 {
        font-size: 3rem;
        font-weight: bold;
        margin-bottom: 40px;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
    }

    .stats-container {
        display: flex;
        justify-content: center;
        gap: 80px;
        margin-top: 30px;
    }

    .stat-item h2 {
        font-size: 3.5rem;
        font-weight: bold;
        margin-bottom: 10px;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
    }

    .stat-item p {
        font-size: 1rem;
        opacity: 0.9;
        font-weight: 600;
    }

    /* Kategori Section */
    .kategori {
        padding: 60px 0;
        background: #f8f9fa;
    }

    .kategori h2 {
        color: #333;
        font-size: 2.5rem;
        font-weight: bold;
        margin-bottom: 50px;
    }

    .kategori-card {
        background: #78909c;
        color: white;
        border: none;
        transition: all 0.3s ease;
        cursor: pointer;
        height: 140px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        border-radius: 8px;
        margin-bottom: 20px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .kategori-card:hover {
        background: #607d8b;
        transform: translateY(-5px);
        color: white;
        text-decoration: none;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
    }

    .kategori-icon {
        font-size: 2.5rem;
        margin-bottom: 10px;
    }

    .kategori-title {
        font-size: 0.85rem;
        font-weight: bold;
        text-align: center;
        line-height: 1.2;
        margin-bottom: 5px;
    }

    .kategori-subtitle {
        font-size: 0.7rem;
        opacity: 0.9;
        text-align: center;
    }

    /* Mobile Responsiveness */
    @media (max-width: 768px) {
        .hero h1 {
            font-size: 2.2rem;
        }

        .stats-container {
            flex-direction: column;
            gap: 30px;
        }

        .stat-item h2 {
            font-size: 2.5rem;
        }
    }
</style>

<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <div class="hero-content">
            <h1>Sistem Informasi Pengadaan Barang Dan Jasa Banjarmasin Maju Dan Sejahtera</h1>
            <div class="stats-container">
                <div class="stat-item">
                    <h2>2749</h2>
                    <p>DATA</p>
                </div>
                <div class="stat-item">
                    <h2>107</h2>
                    <p>MAKRO</p>
                </div>
                <div class="stat-item">
                    <h2>2642</h2>
                    <p>SEKTORAL</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Kategori Section -->
<section class="kategori">
    <div class="container">
        <h2 class="text-center mb-5">KATEGORI</h2>
        <div class="row">
            <!-- Baris Pertama -->
            <div class="col-lg-2 col-md-4 col-sm-6 col-6 mb-3">
                <a href="rup/pengadaanlangsung.php" class="kategori-card">
                    <div class="kategori-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="kategori-title">EKONOMI</div>
                    <div class="kategori-subtitle">420 DATA</div>
                </a>
            </div>

            <div class="col-lg-2 col-md-4 col-sm-6 col-6 mb-3">
                <a href="#" class="kategori-card">
                    <div class="kategori-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="kategori-title">KEPENDUDUKAN</div>
                    <div class="kategori-subtitle">233 DATA</div>
                </a>
            </div>

            <div class="col-lg-2 col-md-4 col-sm-6 col-6 mb-3">
                <a href="rekapitulasi/import.php" class="kategori-card">
                    <div class="kategori-icon">
                        <i class="fas fa-hospital"></i>
                    </div>
                    <div class="kategori-title">KESEHATAN</div>
                    <div class="kategori-subtitle">185 DATA</div>
                </a>
            </div>

            <div class="col-lg-2 col-md-4 col-sm-6 col-6 mb-3">
                <a href="rekappengadaan/rup/seluruhpengadaan.php" class="kategori-card">
                    <div class="kategori-icon">
                        <i class="fas fa-eye"></i>
                    </div>
                    <div class="kategori-title">PENGAWASAN</div>
                    <div class="kategori-subtitle">142 DATA</div>
                </a>
            </div>

            <div class="col-lg-2 col-md-4 col-sm-6 col-6 mb-3">
                <a href="#" class="kategori-card">
                    <div class="kategori-icon">
                        <i class="fas fa-masks-theater"></i>
                    </div>
                    <div class="kategori-title">PARIWISATA BUDAYA</div>
                    <div class="kategori-subtitle">98 DATA</div>
                </a>
            </div>

            <div class="col-lg-2 col-md-4 col-sm-6 col-6 mb-3">
                <a href="#" class="kategori-card">
                    <div class="kategori-icon">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <div class="kategori-title">PEMERINTAHAN</div>
                    <div class="kategori-subtitle">76 DATA</div>
                </a>
            </div>

            <!-- Baris Kedua -->
            <div class="col-lg-2 col-md-4 col-sm-6 col-6 mb-3">
                <a href="#" class="kategori-card">
                    <div class="kategori-icon">
                        <i class="fas fa-tools"></i>
                    </div>
                    <div class="kategori-title">PENANGGULANGAN BENCANA</div>
                    <div class="kategori-subtitle">54 DATA</div>
                </a>
            </div>

            <div class="col-lg-2 col-md-4 col-sm-6 col-6 mb-3">
                <a href="swakelola/tes.php" class="kategori-card">
                    <div class="kategori-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div class="kategori-title">PENDIDIKAN</div>
                    <div class="kategori-subtitle">321 DATA</div>
                </a>
            </div>

            <div class="col-lg-2 col-md-4 col-sm-6 col-6 mb-3">
                <a href="rekapitulasi/input.php" class="kategori-card">
                    <div class="kategori-icon">
                        <i class="fas fa-seedling"></i>
                    </div>
                    <div class="kategori-title">PERTANIAN</div>
                    <div class="kategori-subtitle">167 DATA</div>
                </a>
            </div>

            <div class="col-lg-2 col-md-4 col-sm-6 col-6 mb-3">
                <a href="#" class="kategori-card">
                    <div class="kategori-icon">
                        <i class="fas fa-people-group"></i>
                    </div>
                    <div class="kategori-title">SOSIAL</div>
                    <div class="kategori-subtitle">89 DATA</div>
                </a>
            </div>

            <div class="col-lg-2 col-md-4 col-sm-6 col-6 mb-3">
                <a href="#" class="kategori-card">
                    <div class="kategori-icon">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <div class="kategori-title">TENAGA KERJA</div>
                    <div class="kategori-subtitle">112 DATA</div>
                </a>
            </div>

            <div class="col-lg-2 col-md-4 col-sm-6 col-6 mb-3">
                <a href="#" class="kategori-card">
                    <div class="kategori-icon">
                        <i class="fas fa-truck"></i>
                    </div>
                    <div class="kategori-title">TRANSPORTASI</div>
                    <div class="kategori-subtitle">45 DATA</div>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Custom JavaScript khusus untuk halaman index -->
<script>
    // HANYA JavaScript yang spesifik untuk index, tidak conflict dengan header
    document.addEventListener('DOMContentLoaded', function() {
        // Smooth hover effects untuk kategori cards
        const cards = document.querySelectorAll('.kategori-card');
        cards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-8px) scale(1.02)';
            });

            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });

        // Counter animation untuk stats
        function animateCounter(element, target) {
            let count = 0;
            const increment = target / 100;
            const timer = setInterval(() => {
                count += increment;
                if (count >= target) {
                    element.textContent = target;
                    clearInterval(timer);
                } else {
                    element.textContent = Math.floor(count);
                }
            }, 20);
        }

        // Animate counters when hero section is visible
        const observerOptions = {
            threshold: 0.5
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const counters = entry.target.querySelectorAll('.stat-item h2');
                    counters.forEach(counter => {
                        const target = parseInt(counter.textContent);
                        animateCounter(counter, target);
                    });
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        const heroSection = document.querySelector('.hero');
        if (heroSection) {
            observer.observe(heroSection);
        }
    });
</script>

<?php
// Include footer
include 'navbar/footer.php';
?>