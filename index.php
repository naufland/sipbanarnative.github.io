<?php
// Set judul halaman
$page_title = "Dashboard Utama - SIP BANAR";

// Include header
include 'navbar/header.php';

// URL API Anda. Ganti jika perlu.
$apiPengadaanUrl = "http://sipbanar-phpnative.id/api/pengadaan.php?action=summary";
$apiTenderUrl = "http://sipbanar-phpnative.id/api/realisasi_tender.php?action=summary";
$apiSwakelolaUrl = "http://sipbanar-phpnative.id/api/realisasi_swakelola.php?action=summary";

// Ambil data dari API
$pengadaanData = json_decode(@file_get_contents($apiPengadaanUrl), true);
$tenderData = json_decode(@file_get_contents($apiTenderUrl), true);
$swakelolaData = json_decode(@file_get_contents($apiSwakelolaUrl), true);

// Proses data untuk ditampilkan
$totalPaketPengadaan = $pengadaanData['summary']['total_paket'] ?? 0;
$totalPaketTender = $tenderData['summary']['total_paket'] ?? 0;
$totalPaketSwakelola = $swakelolaData['summary']['total_paket'] ?? 0;

$totalKeseluruhan = $totalPaketPengadaan + $totalPaketTender + $totalPaketSwakelola;

// Data untuk Grafik (disiapkan untuk JavaScript)
$chartData = [
    'labels' => ['Pengadaan', 'Realisasi Tender', 'Realisasi Swakelola'],
    'data' => [$totalPaketPengadaan, $totalPaketTender, $totalPaketSwakelola]
];
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    body {
        background-color: #f4f7f6;
    }

    .hero {
        background: linear-gradient(135deg, #dc3545 0%, #a31a28 100%);
        color: white;
        padding: 100px 0;
        text-align: center;
        border-bottom: 5px solid #8B0000;
    }

    .hero h1 {
        font-size: 3.5rem;
        font-weight: 800;
        margin-bottom: 1rem;
        text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    }

    .hero p {
        font-size: 1.25rem;
        opacity: 0.9;
        max-width: 700px;
        margin: 0 auto 40px auto;
    }

    .stats-wrapper {
        display: flex;
        justify-content: center;
        gap: 30px;
        flex-wrap: wrap;
    }

    .stat-card {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        padding: 25px 40px;
        border-radius: 12px;
        transition: all 0.3s ease;
        text-align: center;
    }

    .stat-card:hover {
        transform: translateY(-10px);
        background: rgba(255, 255, 255, 0.2);
    }

    .stat-card h2 {
        font-size: 3.5rem;
        font-weight: 700;
        margin: 0;
    }

    .stat-card p {
        font-size: 1rem;
        margin: 5px 0 0 0;
        opacity: 0.8;
        font-weight: 500;
        letter-spacing: 1px;
    }

    .main-content {
        padding: 80px 0;
    }

    .section-title {
        font-size: 2.5rem;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 50px;
        text-align: center;
    }

    .kategori-card {
        background: #ffffff;
        border: 1px solid #e0e0e0;
        text-decoration: none;
        color: #34495e;
        padding: 30px;
        border-radius: 12px;
        text-align: center;
        transition: all 0.3s ease;
        display: block;
        height: 100%;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    }

    .kategori-card:hover {
        transform: translateY(-8px);
        border-color: #dc3545;
        box-shadow: 0 10px 30px rgba(220, 53, 69, 0.2);
    }

    .kategori-icon {
        font-size: 3rem;
        color: #dc3545;
        margin-bottom: 15px;
    }

    .kategori-title {
        font-size: 1.1rem;
        font-weight: 600;
    }

    .kategori-subtitle {
        font-size: 0.9rem;
        color: #6c757d;
    }

    .chart-container {
        background: #ffffff;
        padding: 40px;
        border-radius: 15px;
        box-shadow: 0 4px 25px rgba(0, 0, 0, 0.1);
    }

    @media (max-width: 768px) {
        .hero h1 {
            font-size: 2.5rem;
        }

        .stats-wrapper {
            gap: 15px;
        }

        .stat-card {
            padding: 20px;
        }

        .stat-card h2 {
            font-size: 2.5rem;
        }
    }
</style>

<section class="hero">
    <div class="container">
        <h1>Sistem Informasi Pengadaan Banjarmasin</h1>
        <p>Transparansi Data untuk Pembangunan yang Lebih Baik dan Kesejahteraan Masyarakat.</p>
        <div class="stats-wrapper">
            <div class="stat-card">
                <h2 data-target="<?= $totalPaketPengadaan ?>">0</h2>
                <p>PENGADAAN</p>
            </div>
            <div class="stat-card">
                <h2 data-target="<?= $totalPaketTender ?>">0</h2>
                <p>REALISASI TENDER</p>
            </div>
            <div class="stat-card">
                <h2 data-target="<?= $totalPaketSwakelola ?>">0</h2>
                <p>REALISASI SWAKELOLA</p>
            </div>
        </div>
    </div>
</section>

<main class="main-content">
    <div class="container">
        <div class="row gy-4 align-items-center">
            <div class="col-lg-7">
                <h2 class="section-title">Kategori Data</h2>
                <div class="row">
                    <div class="col-md-4 col-6 mb-4">
                        <a href="rekappengadaan/pengadaan/seluruhpengadaan.php" class="kategori-card">
                            <div class="kategori-icon"><i class="fas fa-boxes"></i></div>
                            <div class="kategori-title">Data Pengadaan</div>
                            <div class="kategori-subtitle"><?= number_format($totalPaketPengadaan, 0, ',', '.') ?> Paket</div>
                        </a>
                    </div>
                    <div class="col-md-4 col-6 mb-4">
                        <a href="rekappengadaan/tender/realisasi_tender.php" class="kategori-card">
                            <div class="kategori-icon"><i class="fas fa-gavel"></i></div>
                            <div class="kategori-title">Realisasi Tender</div>
                            <div class="kategori-subtitle"><?= number_format($totalPaketTender, 0, ',', '.') ?> Paket</div>
                        </a>
                    </div>
                    <div class="col-md-4 col-6 mb-4">
                        <a href="rekappengadaan/swakelola/realisasi_swakelola.php" class="kategori-card">
                            <div class="kategori-icon"><i class="fas fa-people-carry"></i></div>
                            <div class="kategori-title">Realisasi Swakelola</div>
                            <div class="kategori-subtitle"><?= number_format($totalPaketSwakelola, 0, ',', '.') ?> Paket</div>
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="chart-container">
                    <h3 class="text-center mb-4">Komposisi Data Paket</h3>
                    <canvas id="komposisiDataChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Animasi Counter Angka
        const counters = document.querySelectorAll('.stat-card h2');
        const speed = 200; // Semakin besar, semakin lambat

        const animateCounter = (counter) => {
            const target = +counter.getAttribute('data-target');
            const count = +counter.innerText;
            const increment = target / speed;

            if (count < target) {
                counter.innerText = Math.ceil(count + increment);
                setTimeout(() => animateCounter(counter), 10);
            } else {
                counter.innerText = new Intl.NumberFormat('id-ID').format(target);
            }
        };

        const observer = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateCounter(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.5
        });

        counters.forEach(counter => observer.observe(counter));

        // 2. Grafik Pie Chart dengan Chart.js
        const ctx = document.getElementById('komposisiDataChart');
        if (ctx) {
            // Ambil data dari PHP
            const chartData = <?= json_encode($chartData) ?>;

            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: chartData.labels,
                    datasets: [{
                        label: 'Jumlah Paket',
                        data: chartData.data,
                        backgroundColor: [
                            'rgba(52, 152, 219, 0.8)', // Biru
                            'rgba(231, 76, 60, 0.8)', // Merah
                            'rgba(241, 196, 15, 0.8)' // Kuning
                        ],
                        borderColor: [
                            'rgba(52, 152, 219, 1)',
                            'rgba(231, 76, 60, 1)',
                            'rgba(241, 196, 15, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed !== null) {
                                        label += new Intl.NumberFormat('id-ID').format(context.parsed) + ' Paket';
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });
        }
    });
</script>

<?php
// Include footer
include 'navbar/footer.php';
?>