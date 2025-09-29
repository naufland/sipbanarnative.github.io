<?php
// =================================================================
// == FILE DASHBOARD UNTUK REALISASI TENDER ========================
// =================================================================

// 1. GANTI URL API
$apiBaseUrl = "http://sipbanar-phpnative.id/api/realisasi_seleksi.php";

// 2. Dapatkan parameter dari URL
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = $_GET['limit'] ?? 100;

// 3. Siapkan parameter query untuk API
$queryParams = $_GET;
$queryParams['page'] = $currentPage;
$queryParams['limit'] = $limit;
$queryParams = array_filter($queryParams);
$queryString = http_build_query($queryParams);
$apiUrl = $apiBaseUrl . '?' . $queryString;

// 4. Siapkan parameter untuk mengambil data SUMMARY
$summaryParams = $queryParams;
unset($summaryParams['page'], $summaryParams['limit']);
$summaryParams['action'] = 'summary';
$summaryQueryString = http_build_query($summaryParams);
$apiSummaryUrl = $apiBaseUrl . '?' . $summaryQueryString;

// 5. Panggil API
$response = @file_get_contents($apiUrl);
$data = json_decode($response, true);
$summaryResponse = @file_get_contents($apiSummaryUrl);
$summaryData = json_decode($summaryResponse, true);

// 6. Inisialisasi variabel statistik
$totalPaket = 0;
$totalPagu = 0;
$totalHps = 0;
$totalKontrak = 0;
$efisiensi = 0;
$formattedTotalPagu = 'Rp 0';
$formattedTotalHps = 'Rp 0';
$formattedTotalKontrak = 'Rp 0';
$formattedEfisiensi = '0%';

// 7. Proses data statistik dari API summary
if ($summaryData && ($summaryData['success'] ?? false) && isset($summaryData['summary'])) {
    $summary = $summaryData['summary'];
    $totalPaket = $summary['total_paket'] ?? 0;
    $totalPagu = $summary['total_pagu'] ?? 0;
    $totalHps = $summary['total_hps'] ?? 0;
    $totalKontrak = $summary['total_kontrak'] ?? 0;

    if ($totalPagu > 0) {
        $efisiensi = (($totalPagu - $totalKontrak) / $totalPagu) * 100;
    }

    $formattedTotalPagu = 'Rp ' . number_format($totalPagu, 0, ',', '.');
    $formattedTotalHps = 'Rp ' . number_format($totalHps, 0, ',', '.');
    $formattedTotalKontrak = 'Rp ' . number_format($totalKontrak, 0, ',', '.');
    $formattedEfisiensi = number_format($efisiensi, 2, ',', '.') . '%';
}

// 8. Siapkan variabel untuk paginasi
$totalPages = $data['pagination']['total_pages'] ?? 1;
$totalRecords = $data['pagination']['total_records'] ?? 0;
if ($totalPaket > 0) {
    $totalRecords = $totalPaket;
}

$page_title = "Data Realisasi Tender - SIP BANAR";
include '../../navbar/header.php';
?>

<script src="../../js/submenu.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<style>
    .container {
        max-width: 1600px;
        margin: 0 auto;
        padding: 20px;
    }

    .filter-section {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        margin-bottom: 30px;
        overflow: hidden;
        border: 1px solid #e9ecef;
    }

    .filter-header {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
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
        background: linear-gradient(90deg, #dc3545, #e74c3c, #dc3545);
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

    .filter-row {
        display: grid;
        gap: 25px;
        margin-bottom: 25px;
    }

    .filter-row:nth-child(1) {
        grid-template-columns: 2fr 1fr;
    }

    .filter-row:nth-child(2) {
        grid-template-columns: 1fr 1fr 1fr;
    }

    .filter-row:nth-child(3) {
        grid-template-columns: 1fr;
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
        border-color: #dc3545;
        box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.15);
        transform: translateY(-1px);
    }

    .filter-group select:hover,
    .filter-group input[type="text"]:hover {
        border-color: #dc3545;
    }

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

    .search-row {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        padding-top: 25px;
        border-top: 2px solid #f1f3f4;
        gap: 15px;
    }

    .search-btn {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
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
        box-shadow: 0 8px 25px rgba(220, 53, 69, 0.3);
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
        border-color: #dc3545;
        color: #dc3545;
        background: #fff5f5;
    }

    .summary-section {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        margin-bottom: 30px;
        overflow: hidden;
        border: 1px solid #e9ecef;
    }

    .summary-header {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
        padding: 20px 25px;
        display: flex;
        align-items: center;
        gap: 12px;
        position: relative;
    }

    .summary-header::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    }

    .summary-header i {
        font-size: 20px;
    }

    .summary-header h3 {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
        letter-spacing: 0.5px;
    }

    .summary-content {
        padding: 30px 25px;
    }

    .summary-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .summary-card {
        background: white;
        border-radius: 12px;
        padding: 25px;
        display: flex;
        align-items: center;
        gap: 20px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        border: 2px solid transparent;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .summary-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
    }

    .summary-card.primary::before {
        background: #3498db;
    }

    .summary-card.success::before {
        background: #27ae60;
    }

    .summary-card.info::before {
        background: #17a2b8;
    }

    .summary-card.warning::before {
        background: #f39c12;
    }

    .summary-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }

    .card-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 24px;
    }

    .summary-card.primary .card-icon {
        background: linear-gradient(135deg, #3498db, #5dade2);
    }

    .summary-card.success .card-icon {
        background: linear-gradient(135deg, #27ae60, #58d68d);
    }

    .summary-card.info .card-icon {
        background: linear-gradient(135deg, #17a2b8, #5dccda);
    }

    .summary-card.warning .card-icon {
        background: linear-gradient(135deg, #f39c12, #f8c471);
    }

    .card-content {
        flex: 1;
    }

    .card-value {
        font-size: 24px;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 5px;
        line-height: 1;
    }

    .card-label {
        font-size: 14px;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 2px;
    }

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
        border-color: #dc3545;
        color: #dc3545;
        transform: translateY(-1px);
    }

    .pagination a.btn-pagination.active {
        background: #dc3545;
        border-color: #dc3545;
        color: white;
        box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
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

    .table-container {
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
        min-width: 1400px;
    }

    table th {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        padding: 16px 12px;
        text-align: left;
        font-weight: 600;
        border-bottom: 3px solid #dc3545;
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

    .badge {
        display: inline-block;
        padding: 6px 12px;
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-radius: 20px;
        white-space: nowrap;
    }

    .badge-primary {
        background: #3498db;
        color: white;
    }

    .price {
        font-weight: 700;
        color: #27ae60;
        white-space: nowrap;
        font-size: 13px;
    }

    .small-text {
        font-size: 11px;
        color: #6c757d;
        margin-top: 4px;
    }

    .text-muted {
        color: #6c757d;
    }

    .empty-state {
        padding: 60px 40px;
        text-align: center;
        color: #6c757d;
    }

    .empty-state i {
        font-size: 64px;
        margin-bottom: 20px;
        opacity: 0.3;
        color: #dc3545;
    }

    .empty-state p {
        font-size: 18px;
        margin: 0;
    }

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

    @media (max-width: 1200px) {
        .filter-row:nth-child(1) {
            grid-template-columns: 1fr;
        }

        .filter-row:nth-child(2) {
            grid-template-columns: 1fr 1fr;
        }
    }

    @media (max-width: 992px) {

        .filter-row:nth-child(1),
        .filter-row:nth-child(2),
        .filter-row:nth-child(3) {
            grid-template-columns: 1fr;
        }

        .results-header {
            flex-direction: column;
            gap: 15px;
            align-items: flex-start;
        }

        .pagination {
            align-self: center;
        }
    }

    @media (max-width: 768px) {
        .container {
            padding: 15px;
        }

        .filter-content {
            padding: 20px 15px;
        }

        .search-row {
            justify-content: center;
            flex-direction: column;
            gap: 12px;
        }

        .search-btn,
        .reset-btn {
            width: 100%;
            min-width: auto;
        }

        .table-footer {
            flex-direction: column;
            gap: 10px;
            text-align: center;
        }

        table th,
        table td {
            padding: 12px 8px;
            font-size: 12px;
        }
    }

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
            <h3>Filter Data Realisasi Tender</h3>
        </div>
        <div class="filter-content">
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="filter-group">
                        <label><i class="fas fa-calendar-alt"></i> Tahun Anggaran</label>
                        <select name="tahun">
                            <option value="">Semua Tahun</option>
                            <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                                <option value="<?= $y ?>" <?= ($_GET['tahun'] ?? '') == $y ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-tags"></i> Jenis Pengadaan</label>
                        <select name="jenis_pengadaan">
                            <option value="">Semua Jenis</option>
                            <option value="Jasa Lainnya" <?= ($_GET['jenis_pengadaan'] ?? '') == 'Jasa Lainnya' ? 'selected' : '' ?>>Jasa Lainnya</option>
                            <option value="Pekerjaan Konstruksi" <?= ($_GET['jenis_pengadaan'] ?? '') == 'Pekerjaan Konstruksi' ? 'selected' : '' ?>>Pekerjaan Konstruksi</option>
                            <option value="Barang" <?= ($_GET['jenis_pengadaan'] ?? '') == 'Barang' ? 'selected' : '' ?>>Barang</option>
                            <option value="Jasa Konsultansi" <?= ($_GET['jenis_pengadaan'] ?? '') == 'Jasa Konsultansi' ? 'selected' : '' ?>>Jasa Konsultansi</option>
                        </select>
                    </div>
                </div>

                <div class="filter-row">
                    <div class="filter-group">
                        <label><i class="fas fa-building"></i> KLPD</label>
                        <select name="klpd">
                            <option value="">Semua KLPD</option>
                            <option value="Pemerintah Daerah Kota Banjarmasin" <?= ($_GET['klpd'] ?? '') == 'Pemerintah Daerah Kota Banjarmasin' ? 'selected' : '' ?>>Pemerintah Daerah Kota Banjarmasin</option>
                            <option value="Pemerintah Daerah Kabupaten Banjar" <?= ($_GET['klpd'] ?? '') == 'Pemerintah Daerah Kabupaten Banjar' ? 'selected' : '' ?>>Pemerintah Daerah Kabupaten Banjar</option>
                            <option value="Pemerintah Daerah Kabupaten Barito Kuala" <?= ($_GET['klpd'] ?? '') == 'Pemerintah Daerah Kabupaten Barito Kuala' ? 'selected' : '' ?>>Pemerintah Daerah Kabupaten Barito Kuala</option>
                            <option value="Pemerintah Daerah Kabupaten Tapin" <?= ($_GET['klpd'] ?? '') == 'Pemerintah Daerah Kabupaten Tapin' ? 'selected' : '' ?>>Pemerintah Daerah Kabupaten Tapin</option>
                            <option value="Pemerintah Daerah Kabupaten Hulu Sungai Selatan" <?= ($_GET['klpd'] ?? '') == 'Pemerintah Daerah Kabupaten Hulu Sungai Selatan' ? 'selected' : '' ?>>Pemerintah Daerah Kabupaten Hulu Sungai Selatan</option>
                            <option value="Pemerintah Daerah Kabupaten Hulu Sungai Tengah" <?= ($_GET['klpd'] ?? '') == 'Pemerintah Daerah Kabupaten Hulu Sungai Tengah' ? 'selected' : '' ?>>Pemerintah Daerah Kabupaten Hulu Sungai Tengah</option>
                            <option value="Pemerintah Daerah Kabupaten Hulu Sungai Utara" <?= ($_GET['klpd'] ?? '') == 'Pemerintah Daerah Kabupaten Hulu Sungai Utara' ? 'selected' : '' ?>>Pemerintah Daerah Kabupaten Hulu Sungai Utara</option>
                            <option value="Pemerintah Daerah Kabupaten Balangan" <?= ($_GET['klpd'] ?? '') == 'Pemerintah Daerah Kabupaten Balangan' ? 'selected' : '' ?>>Pemerintah Daerah Kabupaten Balangan</option>
                            <option value="Pemerintah Daerah Kabupaten Tabalong" <?= ($_GET['klpd'] ?? '') == 'Pemerintah Daerah Kabupaten Tabalong' ? 'selected' : '' ?>>Pemerintah Daerah Kabupaten Tabalong</option>
                            <option value="Pemerintah Daerah Kabupaten Tanah Laut" <?= ($_GET['klpd'] ?? '') == 'Pemerintah Daerah Kabupaten Tanah Laut' ? 'selected' : '' ?>>Pemerintah Daerah Kabupaten Tanah Laut</option>
                            <option value="Pemerintah Daerah Kabupaten Tanah Bumbu" <?= ($_GET['klpd'] ?? '') == 'Pemerintah Daerah Kabupaten Tanah Bumbu' ? 'selected' : '' ?>>Pemerintah Daerah Kabupaten Tanah Bumbu</option>
                            <option value="Pemerintah Daerah Kabupaten Kotabaru" <?= ($_GET['klpd'] ?? '') == 'Pemerintah Daerah Kabupaten Kotabaru' ? 'selected' : '' ?>>Pemerintah Daerah Kabupaten Kotabaru</option>
                            <option value="Pemerintah Daerah Kota Banjarbaru" <?= ($_GET['klpd'] ?? '') == 'Pemerintah Daerah Kota Banjarbaru' ? 'selected' : '' ?>>Pemerintah Daerah Kota Banjarbaru</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-cogs"></i> Metode Pengadaan</label>
                        <select name="metode_pengadaan">
                            <option value="">Semua Metode</option>
                            <option value="Tender" <?= ($_GET['metode_pengadaan'] ?? '') == 'Tender' ? 'selected' : '' ?>>Tender</option>
                            <option value="Tender Cepat" <?= ($_GET['metode_pengadaan'] ?? '') == 'Tender Cepat' ? 'selected' : '' ?>>Tender Cepat</option>
                            <option value="Seleksi" <?= ($_GET['metode_pengadaan'] ?? '') == 'Seleksi' ? 'selected' : '' ?>>Seleksi</option>
                            <option value="Pascakualifikasi Satu File" <?= ($_GET['metode_pengadaan'] ?? '') == 'Pascakualifikasi Satu File' ? 'selected' : '' ?>>Pascakualifikasi Satu File</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-money-check-alt"></i> Sumber Dana</label>
                        <select name="sumber_dana">
                            <option value="">Semua Sumber</option>
                            <option value="APBD" <?= ($_GET['sumber_dana'] ?? '') == 'APBD' ? 'selected' : '' ?>>APBD</option>
                            <option value="APBN" <?= ($_GET['sumber_dana'] ?? '') == 'APBN' ? 'selected' : '' ?>>APBN</option>
                        </select>
                    </div>
                </div>

                <div class="filter-row">
                    <div class="filter-group">
                        <label><i class="fas fa-search"></i> Pencarian</label>
                        <div class="search-input-wrapper">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" placeholder="Cari Nama Paket, Satker, atau Pemenang..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <div class="search-row">
                    <button type="button" class="reset-btn" onclick="window.location.href=window.location.pathname">
                        <i class="fas fa-undo"></i> Reset Filter
                    </button>
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i> Cari Data
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="summary-section">
        <div class="summary-header">
            <i class="fas fa-chart-bar"></i>
            <h3>Ringkasan Data Realisasi Tender</h3>
        </div>
        <div class="summary-content">
            <div class="summary-cards">
                <div class="summary-card primary">
                    <div class="card-icon"><i class="fas fa-boxes"></i></div>
                    <div class="card-content">
                        <div class="card-value"><?= number_format($totalPaket, 0, ',', '.') ?></div>
                        <div class="card-label">Total Paket</div>
                    </div>
                </div>
                <div class="summary-card warning">
                    <div class="card-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                    <div class="card-content">
                        <div class="card-value"><?= $formattedTotalPagu ?></div>
                        <div class="card-label">Total Pagu</div>
                    </div>
                </div>
                <div class="summary-card success">
                    <div class="card-icon"><i class="fas fa-handshake"></i></div>
                    <div class="card-content">
                        <div class="card-value"><?= $formattedTotalKontrak ?></div>
                        <div class="card-label">Total Nilai Kontrak</div>
                    </div>
                </div>
                <div class="summary-card info">
                    <div class="card-icon"><i class="fas fa-percent"></i></div>
                    <div class="card-content">
                        <div class="card-value"><?= $formattedEfisiensi ?></div>
                        <div class="card-label">Efisiensi Anggaran</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="results-section">
        <div class="results-header">
            <div>
                <div class="results-title"><i class="fas fa-table"></i> Hasil Data Realisasi Tender</div>
                <div class="results-subtitle">
                    <strong>Menampilkan <?= count($data['data'] ?? []) ?> dari <?= number_format($totalRecords, 0, ',', '.') ?> total data</strong>
                </div>
            </div>
            <div class="pagination">
                <?php
                $paginationParams = $_GET;
                unset($paginationParams['page']);
                $paginationQuery = http_build_query($paginationParams);
                ?>
                <a href="?<?= $paginationQuery ?>&page=<?= max(1, $currentPage - 1) ?>" class="btn-pagination <?= $currentPage <= 1 ? 'disabled' : '' ?>"><i class="fas fa-chevron-left"></i></a>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i == $currentPage): ?>
                        <a href="?<?= $paginationQuery ?>&page=<?= $i ?>" class="btn-pagination active"><?= $i ?></a>
                    <?php elseif (abs($i - $currentPage) < 3 || $i <= 2 || $i > $totalPages - 2): ?>
                        <a href="?<?= $paginationQuery ?>&page=<?= $i ?>" class="btn-pagination"><?= $i ?></a>
                    <?php elseif ($i == $currentPage - 3 || $i == $currentPage + 3): ?>
                        <span class="btn-pagination-dots">...</span>
                    <?php endif; ?>
                <?php endfor; ?>
                <a href="?<?= $paginationQuery ?>&page=<?= min($totalPages, $currentPage + 1) ?>" class="btn-pagination <?= $currentPage >= $totalPages ? 'disabled' : '' ?>"><i class="fas fa-chevron-right"></i></a>
            </div>
        </div>

        <?php if ($data && ($data['success'] ?? false) && count($data['data']) > 0) : ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 40px;">No</th>
                            <th style="width: 280px;">Nama Paket</th>
                            <th style="width: 220px;">Satker</th>
                            <th style="width: 140px;">Pagu & HPS</th>
                            <th style="width: 220px;">Pemenang</th>
                            <th style="width: 130px;">Nilai Kontrak</th>
                            <th style="width: 140px;">Jenis Pengadaan</th>
                            <th style="width: 120px;">Metode</th>
                            <th>KLPD</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['data'] as $row) : ?>
                            <tr>
                                <td style="text-align: center; font-weight: 700; color: #2c3e50;">
                                    <?= htmlspecialchars($row['No']) ?>
                                </td>
                                <td>
                                    <div style="font-weight: 700; color: #2c3e50; margin-bottom: 5px; line-height: 1.4;">
                                        <?= htmlspecialchars($row['Nama_Paket']) ?>
                                    </div>
                                    <div class="small-text">
                                        <i class="fas fa-id-card"></i> Tender: <?= htmlspecialchars($row['Kode_Tender']) ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: #34495e; line-height: 1.4;">
                                        <i class="fas fa-sitemap"></i> <?= htmlspecialchars($row['Nama_Satker']) ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="small-text">Pagu:</div>
                                    <div class="price" style="color: #6c757d; font-size: 12px; margin-bottom: 6px;">
                                        <?= 'Rp ' . number_format($row['Nilai_Pagu'], 0, ',', '.') ?>
                                    </div>
                                    <div class="small-text">HPS:</div>
                                    <div class="price" style="color: #e67e22; font-size: 12px;">
                                        <?= 'Rp ' . number_format($row['Nilai_HPS'], 0, ',', '.') ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 700; color: #2980b9; line-height: 1.4;">
                                        <i class="fas fa-trophy"></i> <?= htmlspecialchars($row['Nama_Pemenang']) ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="price" style="font-size: 13px;">
                                        <?= 'Rp ' . number_format($row['Nilai_Kontrak'], 0, ',', '.') ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-primary">
                                        <?= htmlspecialchars($row['Jenis_Pengadaan']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: #555; font-size: 12px; line-height: 1.4;">
                                        <?= htmlspecialchars($row['Metode_Pengadaan']) ?>
                                    </div>
                                </td>
                                <td>
                                    <small style="line-height: 1.4;">
                                        <?= htmlspecialchars($row['KLPD']) ?>
                                    </small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="table-footer">
                <div><strong><i class="fas fa-info-circle"></i> Informasi Halaman:</strong> Halaman <?= $currentPage ?> dari <?= $totalPages ?></div>
                <div><strong>Total Data: <?= number_format($totalRecords, 0, ',', '.') ?></strong> realisasi</div>
            </div>
        <?php else : ?>
            <div class="empty-state">
                <i class="fas fa-search-minus"></i>
                <p><strong>Tidak ada data realisasi yang ditemukan</strong></p>
                <small class="text-muted">Coba ubah kriteria pencarian atau filter yang Anda gunakan</small>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const filterForm = document.querySelector('form');

        // Auto submit on select change (optional)
        const selects = filterForm.querySelectorAll('select');
        selects.forEach(select => {
            select.addEventListener('change', function() {
                // Uncomment line below to enable auto-submit
                // filterForm.submit();
            });
        });

        // Smooth scroll animation
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    });

    function resetForm() {
        window.location.href = window.location.pathname;
    }
</script>

<?php
include '../../navbar/footer.php';
?>