<?php
// =================================================================
// == realisasi_tender_view.php (TAMPILAN) - DENGAN FILTER BULAN ==
// =================================================================

// URL API dasar
$apiBaseUrl = "http://sipbanar-phpnative.id/api/realisasi_tender.php";

// 1. Dapatkan parameter dari URL
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = $_GET['limit'] ?? 50;

// FILTER BULAN DEFAULT JULI
$currentYear = date('Y');
$selectedBulan = $_GET['bulan'] ?? '07'; // Default Juli
$selectedTahun = $_GET['tahun'] ?? $currentYear;

// 2. Siapkan parameter query untuk API
$queryParams = $_GET;
$queryParams['page'] = $currentPage;
$queryParams['limit'] = $limit;
$queryParams['bulan'] = $selectedBulan;
$queryParams['tahun'] = $selectedTahun;

// Hapus parameter kosong
$queryParams = array_filter($queryParams, function ($value) {
    return $value !== '' && $value !== null;
});
$queryString = http_build_query($queryParams);
$apiUrl = $apiBaseUrl . '?' . $queryString;

// 3. Siapkan parameter untuk SUMMARY
$summaryParams = $queryParams;
unset($summaryParams['page']);
unset($summaryParams['limit']);
$summaryParams['action'] = 'summary';

$summaryQueryString = http_build_query($summaryParams);
$apiSummaryUrl = $apiBaseUrl . '?' . $summaryQueryString;

// 4. Panggil API
$response = @file_get_contents($apiUrl);
$data = json_decode($response, true);

$summaryResponse = @file_get_contents($apiSummaryUrl);
$summaryData = json_decode($summaryResponse, true);

// 5. Inisialisasi variabel statistik
$totalPaket = 0;
$totalPagu = 0;
$totalHPS = 0;
$totalKontrak = 0;
$efisiensi = 0;
$formattedTotalPagu = 'Rp 0';
$formattedTotalHPS = 'Rp 0';
$formattedTotalKontrak = 'Rp 0';

// 6. Proses data statistik
if ($summaryData && isset($summaryData['success']) && $summaryData['success'] && isset($summaryData['summary'])) {
    $summary = $summaryData['summary'];
    $totalPaket = $summary['total_paket'] ?? 0;
    $totalPagu = $summary['total_pagu'] ?? 0;
    $totalHPS = $summary['total_hps'] ?? 0;
    $totalKontrak = $summary['total_kontrak'] ?? 0;
    $efisiensi = $summary['efisiensi_persen'] ?? 0;

    $formattedTotalPagu = 'Rp ' . number_format($totalPagu, 0, ',', '.');
    $formattedTotalHPS = 'Rp ' . number_format($totalHPS, 0, ',', '.');
    $formattedTotalKontrak = 'Rp ' . number_format($totalKontrak, 0, ',', '.');
}

// 7. Variabel paginasi
$totalPages = $data['pagination']['total_pages'] ?? 1;
$totalRecords = $data['pagination']['total_records'] ?? 0;
if ($totalPaket > 0) {
    $totalRecords = $totalPaket;
}

// Set page title
$page_title = "Realisasi Tender - SIP BANAR";

// Array nama bulan
$namaBulan = [
    '01' => 'Januari',
    '02' => 'Februari',
    '03' => 'Maret',
    '04' => 'April',
    '05' => 'Mei',
    '06' => 'Juni',
    '07' => 'Juli',
    '08' => 'Agustus',
    '09' => 'September',
    '10' => 'Oktober',
    '11' => 'November',
    '12' => 'Desember'
];

// Include header
include '../../navbar/header.php';
?>

<script src="../../js/submenu.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<style>
    /* Custom CSS untuk halaman realisasi tender */
    .container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 20px;
    }

    /* Filter Section Styles */
    .filter-section {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        margin-bottom: 30px;
        overflow: hidden;
        border: 1px solid #e9ecef;
    }

    .filter-header {
        background: linear-gradient(135deg, #28a745 0%, #218838 100%);
        color: white;
        padding: 20px 25px;
        display: flex;
        align-items: center;
        gap: 12px;
        position: relative;
    }

    .filter-header::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, #28a745, #20c997, #28a745);
    }

    .filter-header i {
        font-size: 20px;
    }

    .filter-header h3 {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
        letter-spacing: 0.5px;
    }

    .filter-content {
        padding: 30px 25px;
    }

    /* Grid Layout untuk Filter */
    .filter-row {
        display: grid;
        gap: 25px;
        margin-bottom: 25px;
    }

    .filter-row:nth-child(1) {
        grid-template-columns: 1fr 1fr 1fr;
    }

    .filter-row:nth-child(2) {
        grid-template-columns: 1fr 1fr 1fr;
    }

    .filter-row:nth-child(3) {
        grid-template-columns: 1fr 1fr 1fr;
    }

    .filter-group {
        position: relative;
    }

    .filter-group label {
        display: block;
        margin-bottom: 10px;
        font-weight: 600;
        color: #2c3e50;
        font-size: 14px;
        letter-spacing: 0.3px;
    }

    .filter-group label .badge-default {
        background: #ffc107;
        color: #000;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 700;
        margin-left: 8px;
    }

    .filter-group select,
    .filter-group input[type="text"] {
        width: 100%;
        padding: 14px 16px;
        border: 2px solid #e9ecef;
        border-radius: 10px;
        font-size: 14px;
        background: white;
        transition: all 0.3s ease;
        color: #2c3e50;
    }

    .filter-group select:focus,
    .filter-group input[type="text"]:focus {
        outline: none;
        border-color: #28a745;
        box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.15);
        transform: translateY(-1px);
    }

    .filter-group select:hover,
    .filter-group input[type="text"]:hover {
        border-color: #28a745;
    }

    /* Search Input dengan Icon */
    .search-input-wrapper {
        position: relative;
    }

    .search-input-wrapper i {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: #6c757d;
        font-size: 16px;
        z-index: 2;
    }

    .search-input-wrapper input[type="text"] {
        padding-left: 45px !important;
    }

    /* Search Row - Tombol di kanan */
    .search-row {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        padding-top: 25px;
        border-top: 2px solid #f1f3f4;
        gap: 15px;
    }

    .search-btn {
        background: linear-gradient(135deg, #28a745 0%, #218838 100%);
        color: white;
        border: none;
        padding: 14px 30px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
        min-width: 140px;
        justify-content: center;
    }

    .search-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
    }

    .reset-btn {
        background: transparent;
        color: #6c757d;
        border: 2px solid #e9ecef;
        padding: 12px 25px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
        min-width: 120px;
        justify-content: center;
    }

    .reset-btn:hover {
        border-color: #28a745;
        color: #28a745;
        background: #f0fff4;
    }

    /* Summary Section Styles */
    /* Summary Section - Horizontal Layout dengan Warna Merah */
.summary-section {
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
    margin-bottom: 30px;
    overflow: hidden;
    border: 1px solid #e9ecef;
}

.summary-header-red {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
    padding: 18px 25px;
    display: flex;
    align-items: center;
    gap: 12px;
    position: relative;
}

.summary-header-red::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
}

.summary-header-red i {
    font-size: 20px;
}

.summary-header-red h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    letter-spacing: 0.5px;
}

.summary-content-horizontal {
    padding: 30px 25px;
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}

/* Summary Cards - Horizontal Style */
.summary-card-horizontal {
    background: white;
    border-radius: 12px;
    padding: 25px 20px;
    display: flex;
    align-items: center;
    gap: 18px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    border: 2px solid transparent;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.summary-card-horizontal::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
}

.summary-card-horizontal.primary::before {
    background: linear-gradient(90deg, #3498db, #5dade2);
}

.summary-card-horizontal.warning::before {
    background: linear-gradient(90deg, #f39c12, #f8b739);
}

.summary-card-horizontal.success::before {
    background: linear-gradient(90deg, #27ae60, #58d68d);
}

.summary-card-horizontal.info::before {
    background: linear-gradient(90deg, #17a2b8, #3fbbc5);
}

.summary-card-horizontal:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.card-icon-horizontal {
    width: 65px;
    height: 65px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 28px;
    flex-shrink: 0;
}

.summary-card-horizontal.primary .card-icon-horizontal {
    background: linear-gradient(135deg, #3498db, #5dade2);
}

.summary-card-horizontal.warning .card-icon-horizontal {
    background: linear-gradient(135deg, #f39c12, #f8b739);
}

.summary-card-horizontal.success .card-icon-horizontal {
    background: linear-gradient(135deg, #27ae60, #58d68d);
}

.summary-card-horizontal.info .card-icon-horizontal {
    background: linear-gradient(135deg, #17a2b8, #3fbbc5);
}

.card-content-horizontal {
    flex: 1;
    min-width: 0;
}

.card-value-horizontal {
    font-size: 24px;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 5px;
    line-height: 1.2;
    word-break: break-word;
}

.card-label-horizontal {
    font-size: 13px;
    font-weight: 600;
    color: #6c757d;
    letter-spacing: 0.3px;
}

/* Responsive Design untuk Summary Cards */
@media (max-width: 1400px) {
    .summary-content-horizontal {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .summary-content-horizontal {
        grid-template-columns: 1fr;
    }
}

    /* Results Section */
    .results-section {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        border: 1px solid #e9ecef;
    }

    .results-header {
        background: #f8f9fa;
        padding: 25px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 2px solid #e9ecef;
    }

    .results-title {
        font-size: 20px;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 8px;
    }

    .results-subtitle {
        font-size: 14px;
        color: #6c757d;
        line-height: 1.5;
    }

    .pagination {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .pagination a.btn-pagination {
        text-decoration: none;
        width: 40px;
        height: 40px;
        border: 2px solid #e9ecef;
        background: white;
        border-radius: 8px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        color: #6c757d;
        transition: all 0.3s ease;
    }

    .pagination a.btn-pagination:hover {
        border-color: #28a745;
        color: #28a745;
        transform: translateY(-1px);
    }

    .pagination a.btn-pagination.active {
        background: #28a745;
        border-color: #28a745;
        color: white;
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
    }

    .pagination a.btn-pagination.disabled {
        pointer-events: none;
        opacity: 0.6;
    }

    .pagination .btn-pagination-dots {
        width: 40px;
        height: 40px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #6c757d;
    }

    /* Table Styles */
    .table-container {
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }

    table th {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        padding: 16px 12px;
        text-align: left;
        font-weight: 600;
        border-bottom: 3px solid #28a745;
        position: sticky;
        top: 0;
        z-index: 10;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    table td {
        padding: 16px 12px;
        border-bottom: 1px solid #f1f1f1;
        vertical-align: top;
    }

    table tr {
        transition: all 0.3s ease;
    }

    table tr:hover {
        background: #f8f9fa;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    table tr:nth-child(even) {
        background: #fafafa;
    }

    table tr:nth-child(even):hover {
        background: #f0f0f0;
    }

    /* Badge Styles */
    .badge {
        display: inline-block;
        padding: 5px 10px;
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        border-radius: 15px;
        white-space: nowrap;
    }

    .badge-primary {
        background: #3498db;
        color: white;
    }

    .badge-success {
        background: #27ae60;
        color: white;
    }

    .badge-warning {
        background: #f39c12;
        color: white;
    }

    .badge-info {
        background: #17a2b8;
        color: white;
    }

    /* Price Formatting */
    .price {
        font-weight: 700;
        color: #27ae60;
        white-space: nowrap;
        font-size: 14px;
    }

    .price.kontrak {
        color: #3498db;
    }

    .price.hps {
        color: #f39c12;
    }

    /* Small Text */
    .small-text {
        font-size: 11px;
        color: #6c757d;
        margin-top: 4px;
    }

    /* Empty State */
    .empty-state {
        padding: 60px 40px;
        text-align: center;
        color: #6c757d;
    }

    .empty-state i {
        font-size: 64px;
        margin-bottom: 20px;
        opacity: 0.3;
        color: #28a745;
    }

    .empty-state p {
        font-size: 18px;
        margin: 0;
    }

    /* Table Footer */
    .table-footer {
        padding: 20px 25px;
        border-top: 2px solid #e9ecef;
        background: #f8f9fa;
        font-size: 14px;
        color: #6c757d;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    /* Responsive Design */
    @media (max-width: 1200px) {

        .filter-row:nth-child(1),
        .filter-row:nth-child(2),
        .filter-row:nth-child(3) {
            grid-template-columns: 1fr 1fr;
        }
    }

    @media (max-width: 992px) {

        .filter-row:nth-child(1),
        .filter-row:nth-child(2),
        .filter-row:nth-child(3) {
            grid-template-columns: 1fr;
        }
    }

    /* Animation */
    .filter-section,
    .results-section,
    .summary-section {
        animation: fadeInUp 0.6s ease-out;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<div class="container">
    <div class="filter-section">
        <div class="filter-header">
            <i class="fas fa-filter"></i>
            <h3>Filter Realisasi Tender</h3>
        </div>
        <div class="filter-content">
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="filter-group">
                        <label>
                            <i class="fas fa-calendar"></i> Bulan
                            <span class="badge-default">DEFAULT: JULI</span>
                        </label>
                        <select name="bulan">
                            <?php foreach ($namaBulan as $kode => $nama): ?>
                                <option value="<?= $kode ?>" <?= $selectedBulan == $kode ? 'selected' : '' ?>>
                                    <?= $nama ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label><i class="fas fa-calendar-alt"></i> Tahun</label>
                        <select name="tahun">
                            <?php for ($y = $currentYear; $y >= 2020; $y--): ?>
                                <option value="<?= $y ?>" <?= $selectedTahun == $y ? 'selected' : '' ?>>
                                    <?= $y ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label><i class="fas fa-tags"></i> Jenis Pengadaan</label>
                        <select name="jenis_pengadaan">
                            <option value="">Semua Jenis</option>
                            <option value="Barang" <?= ($_GET['jenis_pengadaan'] ?? '') == 'Barang' ? 'selected' : '' ?>>Barang</option>
                            <option value="Konstruksi" <?= ($_GET['jenis_pengadaan'] ?? '') == 'Konstruksi' ? 'selected' : '' ?>>Konstruksi</option>
                            <option value="Jasa Konsultansi" <?= ($_GET['jenis_pengadaan'] ?? '') == 'Jasa Konsultansi' ? 'selected' : '' ?>>Jasa Konsultansi</option>
                            <option value="Jasa Lainnya" <?= ($_GET['jenis_pengadaan'] ?? '') == 'Jasa Lainnya' ? 'selected' : '' ?>>Jasa Lainnya</option>
                        </select>
                    </div>
                </div>

                <div class="filter-row">
                    <div class="filter-group">
                        <label><i class="fas fa-building"></i> KLPD</label>
                        <select name="klpd">
                            <option value="">Semua KLPD</option>
                            <option value="Kota Banjarmasin" <?= ($_GET['klpd'] ?? '') == 'Kota Banjarmasin' ? 'selected' : '' ?>>Kota Banjarmasin</option>
                            <option value="Kabupaten Banjar" <?= ($_GET['klpd'] ?? '') == 'Kabupaten Banjar' ? 'selected' : '' ?>>Kabupaten Banjar</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label><i class="fas fa-cogs"></i> Metode Pengadaan</label>
                        <select name="metode_pengadaan">
                            <option value="">Semua Metode</option>
                            <option value="Tender" <?= ($_GET['metode_pengadaan'] ?? '') == 'Tender' ? 'selected' : '' ?>>Tender</option>
                            <option value="Tender Cepat" <?= ($_GET['metode_pengadaan'] ?? '') == 'Tender Cepat' ? 'selected' : '' ?>>Tender Cepat</option>
                            <option value="Seleksi" <?= ($_GET['metode_pengadaan'] ?? '') == 'Seleksi' ? 'selected' : '' ?>>Seleksi</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label><i class="fas fa-wallet"></i> Sumber Dana</label>
                        <select name="sumber_dana">
                            <option value="">Semua Sumber Dana</option>
                            <option value="APBD" <?= ($_GET['sumber_dana'] ?? '') == 'APBD' ? 'selected' : '' ?>>APBD</option>
                            <option value="APBN" <?= ($_GET['sumber_dana'] ?? '') == 'APBN' ? 'selected' : '' ?>>APBN</option>
                        </select>
                    </div>
                </div>

                <div class="filter-row">
                    <div class="filter-group">
                        <label><i class="fas fa-file-contract"></i> Jenis Kontrak</label>
                        <select name="jenis_kontrak">
                            <option value="">Semua Jenis Kontrak</option>
                            <option value="Harga Satuan" <?= ($_GET['jenis_kontrak'] ?? '') == 'Harga Satuan' ? 'selected' : '' ?>>Harga Satuan</option>
                            <option value="Lumpsum" <?= ($_GET['jenis_kontrak'] ?? '') == 'Lumpsum' ? 'selected' : '' ?>>Lumpsum</option>
                            <option value="Gabungan Lumpsum dan Harga Satuan" <?= ($_GET['jenis_kontrak'] ?? '') == 'Gabungan Lumpsum dan Harga Satuan' ? 'selected' : '' ?>>Gabungan</option>
                        </select>
                    </div>

                    <div class="filter-group" style="grid-column: span 2;">
                        <label><i class="fas fa-search"></i> Pencarian</label>
                        <div class="search-input-wrapper">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" placeholder="Cari nama paket, pemenang, atau kode tender..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <div class="search-row">
                    <button type="button" class="reset-btn" onclick="resetForm()">
                        <i class="fas fa-undo"></i>
                        Reset Filter
                    </button>
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i>
                        Cari Data
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- GANTI BAGIAN SUMMARY SECTION DENGAN KODE INI -->
<div class="summary-section">
    <div class="summary-header-red">
        <i class="fas fa-chart-line"></i>
        <h3>Ringkasan Data Realisasi Tender</h3>
    </div>
    <div class="summary-content-horizontal">
        <div class="summary-card-horizontal primary">
            <div class="card-icon-horizontal">
                <i class="fas fa-clipboard-list"></i>
            </div>
            <div class="card-content-horizontal">
                <div class="card-value-horizontal"><?= number_format($totalPaket, 0, ',', '.') ?></div>
                <div class="card-label-horizontal">Total Paket</div>
            </div>
        </div>

        <div class="summary-card-horizontal warning">
            <div class="card-icon-horizontal">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="card-content-horizontal">
                <div class="card-value-horizontal"><?= $formattedTotalPagu ?></div>
                <div class="card-label-horizontal">Total Pagu</div>
            </div>
        </div>

        <div class="summary-card-horizontal success">
            <div class="card-icon-horizontal">
                <i class="fas fa-handshake"></i>
            </div>
            <div class="card-content-horizontal">
                <div class="card-value-horizontal"><?= $formattedTotalKontrak ?></div>
                <div class="card-label-horizontal">Total Nilai Kontrak</div>
            </div>
        </div>

        <div class="summary-card-horizontal info">
            <div class="card-icon-horizontal">
                <i class="fas fa-percentage"></i>
            </div>
            <div class="card-content-horizontal">
                <div class="card-value-horizontal"><?= number_format($efisiensi, 2) ?>%</div>
                <div class="card-label-horizontal">Efisiensi Anggaran</div>
            </div>
        </div>
    </div>
</div>
            <?php if ($efisiensi > 0): ?>
                <div style="background: #e8f5e9; padding: 15px 20px; border-radius: 10px; display: flex; align-items: center; gap: 12px; border-left: 4px solid #27ae60;">
                    <i class="fas fa-chart-pie" style="font-size: 24px; color: #27ae60;"></i>
                    <div>
                        <div style="font-size: 13px; color: #2c3e50; font-weight: 600;">Efisiensi Anggaran</div>
                        <div style="font-size: 20px; font-weight: 700; color: #27ae60;"><?= number_format($efisiensi, 2) ?>%</div>
                        <div style="font-size: 11px; color: #6c757d;">Hemat: Rp <?= number_format($totalPagu - $totalKontrak, 0, ',', '.') ?></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="results-section">
        <div class="results-header">
            <div>
                <div class="results-title">
                    <i class="fas fa-table"></i> Hasil Data Realisasi Tender
                </div>
                <?php if ($data && isset($data['success']) && $data['success']) : ?>
                    <div class="results-subtitle">
                        <strong>Menampilkan <?= count($data['data']) ?> dari <?= number_format($totalRecords, 0, ',', '.') ?> total data</strong>
                        | Periode: <?= $namaBulan[$selectedBulan] ?> <?= $selectedTahun ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="pagination">
                <?php
                $paginationParams = $_GET;
                unset($paginationParams['page']);
                $paginationQuery = http_build_query($paginationParams);
                ?>

                <a href="?<?= $paginationQuery ?>&page=<?= max(1, $currentPage - 1) ?>" class="btn-pagination <?= $currentPage <= 1 ? 'disabled' : '' ?>" title="Halaman Sebelumnya">
                    <i class="fas fa-chevron-left"></i>
                </a>

                <?php
                for ($i = 1; $i <= $totalPages; $i++) {
                    if ($i == $currentPage) {
                        echo '<a href="?' . $paginationQuery . '&page=' . $i . '" class="btn-pagination active">' . $i . '</a>';
                    } elseif (abs($i - $currentPage) < 3 || $i <= 2 || $i > $totalPages - 2) {
                        echo '<a href="?' . $paginationQuery . '&page=' . $i . '" class="btn-pagination">' . $i . '</a>';
                    } elseif ($i == $currentPage - 3 || $i == $currentPage + 3) {
                        echo '<span class="btn-pagination-dots">...</span>';
                    }
                }
                ?>

                <a href="?<?= $paginationQuery ?>&page=<?= min($totalPages, $currentPage + 1) ?>" class="btn-pagination <?= $currentPage >= $totalPages ? 'disabled' : '' ?>" title="Halaman Selanjutnya">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        </div>

        <?php if ($data && isset($data['success']) && $data['success'] && count($data['data']) > 0) : ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 80px;"><i class="fas fa-hashtag"></i> No</th>
                            <th style="width: 120px;"><i class="fas fa-barcode"></i> Kode Tender</th>
                            <th style="width: 280px;"><i class="fas fa-box"></i> Nama Paket</th>
                            <th style="width: 130px;"><i class="fas fa-money-bill"></i> Nilai Pagu</th>
                            <th style="width: 130px;"><i class="fas fa-calculator"></i> Nilai HPS</th>
                            <th style="width: 130px;"><i class="fas fa-file-invoice-dollar"></i> Nilai Kontrak</th>
                            <th style="width: 200px;"><i class="fas fa-user-tie"></i> Pemenang</th>
                            <th style="width: 120px;"><i class="fas fa-tags"></i> Jenis</th>
                            <th style="width: 100px;"><i class="fas fa-cogs"></i> Metode</th>
                            <th style="width: 150px;"><i class="fas fa-building"></i> KLPD</th>
                            <th style="width: 180px;"><i class="fas fa-sitemap"></i> Satuan Kerja</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['data'] as $index => $row) : ?>
                            <tr>
                                <td style="text-align: center; font-weight: 700; color: #6c757d;">
                                    <?= $index + 1 + (($currentPage - 1) * $limit) ?>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: #3498db; font-size: 11px;">
                                        <?= htmlspecialchars($row['Kode_Tender'] ?? '-') ?>
                                    </div>
                                    <div class="small-text">
                                        <i class="fas fa-calendar"></i> TA: <?= htmlspecialchars($row['Tahun_Anggaran'] ?? '-') ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 700; color: #2c3e50; margin-bottom: 5px; line-height: 1.4;">
                                        <?= htmlspecialchars($row['Nama_Paket'] ?? '-') ?>
                                    </div>
                                    <div class="small-text">
                                        <i class="fas fa-info-circle"></i> <?= htmlspecialchars($row['Tahap'] ?? '-') ?>
                                    </div>
                                </td>
                                <td class="price">
                                    <?php
                                    $paguValue = (float)($row['Nilai_Pagu'] ?? 0);
                                    echo 'Rp ' . number_format($paguValue, 0, ',', '.');
                                    ?>
                                </td>
                                <td class="price hps">
                                    <?php
                                    $hpsValue = (float)($row['Nilai_HPS'] ?? 0);
                                    echo 'Rp ' . number_format($hpsValue, 0, ',', '.');
                                    ?>
                                </td>
                                <td class="price kontrak">
                                    <?php
                                    $kontrakValue = (float)($row['Nilai_Kontrak'] ?? 0);
                                    echo 'Rp ' . number_format($kontrakValue, 0, ',', '.');
                                    ?>
                                    <?php if ($paguValue > 0 && $kontrakValue > 0): ?>
                                        <?php
                                        $efisiensiPaket = (($paguValue - $kontrakValue) / $paguValue) * 100;
                                        $badgeClass = $efisiensiPaket > 0 ? 'badge-success' : 'badge-warning';
                                        ?>
                                        <div style="margin-top: 5px;">
                                            <span class="badge <?= $badgeClass ?>" style="font-size: 9px;">
                                                <?= $efisiensiPaket > 0 ? '↓' : '↑' ?> <?= number_format(abs($efisiensiPaket), 1) ?>%
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: #2c3e50; font-size: 12px;">
                                        <?= htmlspecialchars($row['Nama_Pemenang'] ?? '-') ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-primary">
                                        <?= htmlspecialchars($row['Jenis_Pengadaan'] ?? '-') ?>
                                    </span>
                                </td>
                                <td>
                                    <small style="font-weight: 600;">
                                        <?= htmlspecialchars($row['Metode_Pengadaan'] ?? '-') ?>
                                    </small>
                                </td>
                                <td>
                                    <small><?= htmlspecialchars($row['KLPD'] ?? '-') ?></small>
                                </td>
                                <td>
                                    <small style="line-height: 1.4;">
                                        <?= htmlspecialchars($row['Nama_Satker'] ?? '-') ?>
                                    </small>
                                    <?php if (!empty($row['Sumber_Dana'])): ?>
                                        <div style="margin-top: 5px;">
                                            <span class="badge badge-info" style="font-size: 9px;">
                                                <?= htmlspecialchars($row['Sumber_Dana']) ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="table-footer">
                <div>
                    <strong><i class="fas fa-info-circle"></i> Informasi Halaman:</strong>
                    Halaman <?= $currentPage ?> dari <?= $totalPages ?>
                </div>
                <div>
                    <strong>Total Data: <?= number_format($totalRecords, 0, ',', '.') ?></strong> paket tender
                </div>
            </div>

        <?php else : ?>
            <div class="empty-state">
                <i class="fas fa-folder-open"></i>
                <p><strong>Tidak ada data realisasi tender yang ditemukan</strong></p>
                <small class="text-muted">
                    Untuk periode <?= $namaBulan[$selectedBulan] ?> <?= $selectedTahun ?>.
                    Coba ubah kriteria pencarian atau pilih bulan lain.
                </small>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const filterForm = document.querySelector('form');

        if (filterForm) {
            filterForm.addEventListener('submit', function(e) {
                const inputs = this.querySelectorAll('input, select');

                inputs.forEach(input => {
                    // Jangan disable bulan dan tahun karena ini filter wajib
                    if (input.name !== 'bulan' && input.name !== 'tahun' && !input.value) {
                        input.disabled = true;
                    }
                });

                return true;
            });
        }

        // Search input enter key
        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput) {
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    this.form.submit();
                }
            });

            // Clear search icon functionality
            searchInput.addEventListener('input', function() {
                const wrapper = this.closest('.search-input-wrapper');
                const icon = wrapper.querySelector('i');
                if (this.value) {
                    icon.className = 'fas fa-times';
                    icon.style.cursor = 'pointer';
                    icon.onclick = () => {
                        this.value = '';
                        icon.className = 'fas fa-search';
                        icon.style.cursor = 'default';
                        icon.onclick = null;
                    };
                } else {
                    icon.className = 'fas fa-search';
                    icon.style.cursor = 'default';
                    icon.onclick = null;
                }
            });
        }

        // Table row hover effects
        const tableRows = document.querySelectorAll('tbody tr');
        tableRows.forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
            });

            row.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });

        // Auto submit on month/year change (optional)
        const bulanSelect = document.querySelector('select[name="bulan"]');
        const tahunSelect = document.querySelector('select[name="tahun"]');

        if (bulanSelect) {
            bulanSelect.addEventListener('change', function() {
                // Optional: auto-submit when month changes
                // this.form.submit();
            });
        }

        if (tahunSelect) {
            tahunSelect.addEventListener('change', function() {
                // Optional: auto-submit when year changes
                // this.form.submit();
            });
        }

        // Highlight high efficiency
        document.querySelectorAll('.badge-success').forEach(badge => {
            const text = badge.textContent.trim();
            if (text.includes('↓') && parseFloat(text) > 10) {
                badge.style.animation = 'pulse 2s infinite';
            }
        });
    });

    // Reset form function - kembali ke default Juli tahun ini
    function resetForm() {
        window.location.href = window.location.pathname + '?bulan=07&tahun=<?= $currentYear ?>';
    }

    // Form validation before submit
    document.querySelector('form').addEventListener('submit', function(e) {
        // Show loading state
        const submitBtn = this.querySelector('.search-btn');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mencari...';
        submitBtn.disabled = true;

        // Reset button state after a delay
        setTimeout(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }, 5000);
    });

    // Add smooth scrolling to results when form is submitted
    window.addEventListener('load', function() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.toString()) {
            document.querySelector('.results-section').scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });

    // Copy functionality for Kode Tender
    document.querySelectorAll('td div[style*="color: #3498db"]').forEach(kodeElement => {
        kodeElement.style.cursor = 'pointer';
        kodeElement.title = 'Klik untuk copy Kode Tender';
        kodeElement.addEventListener('click', function(e) {
            e.stopPropagation();
            const kodeText = this.textContent.trim();
            navigator.clipboard.writeText(kodeText).then(() => {
                const originalText = this.textContent;
                const originalColor = this.style.color;
                this.textContent = '✓ Copied!';
                this.style.color = '#27ae60';
                setTimeout(() => {
                    this.textContent = originalText;
                    this.style.color = originalColor;
                }, 1500);
            });
        });
    });

    // Add pulse animation
    const style = document.createElement('style');
    style.textContent = `
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }
`;
    document.head.appendChild(style);

    // Export function (optional)
    function exportData() {
        const params = new URLSearchParams(window.location.search);
        params.set('action', 'export');
        params.set('format', 'csv');
        window.location.href = '<?= $apiBaseUrl ?>?' + params.toString();
    }

    // Add export button dynamically (optional)
    window.addEventListener('load', function() {
        const resultsHeader = document.querySelector('.results-header > div:first-child');
        if (resultsHeader && <?= $totalRecords ?> > 0) {
            const exportBtn = document.createElement('button');
            exportBtn.innerHTML = '<i class="fas fa-download"></i> Export CSV';
            exportBtn.className = 'search-btn';
            exportBtn.style.marginTop = '10px';
            exportBtn.onclick = exportData;
            resultsHeader.appendChild(exportBtn);
        }
    });
</script>

<?php
// Include footer
include '../../navbar/footer.php';
?>