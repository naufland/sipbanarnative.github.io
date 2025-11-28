<?php
// =================================================================
// == BLOK PHP DENGAN FILTER BULAN DEFAULT JULI + FILTER PERUBAHAN ==
// == FILTER KLPD DIGANTI MENJADI SATUAN_KERJA ==
// =================================================================

// URL API dasar
$apiBaseUrl = "http://sipbanarnative.id/api/pengadaan.php";

// 1. Dapatkan parameter dari URL, termasuk halaman saat ini
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = $_GET['limit'] ?? 100; // Default limit 100 data

// TAMBAHAN: Dapatkan filter bulan dan tahun
// Default bulan Juli (07) dan tahun sekarang
$currentYear = date('Y');
$selectedBulan = $_GET['bulan'] ?? '07'; // Default Juli
$selectedTahun = $_GET['tahun'] ?? $currentYear;

// TAMBAHAN: Filter Perubahan
$selectedPerubahan = $_GET['perubahan'] ?? 'Tidak'; // Default: Tidak

// 2. Siapkan parameter query untuk API
// Ambil semua parameter filter dari URL
$queryParams = $_GET;
$queryParams['page'] = $currentPage;
$queryParams['limit'] = $limit;
$queryParams['bulan'] = $selectedBulan;
$queryParams['tahun'] = $selectedTahun;

// Tambahkan filter perubahan jika ada
if (!empty($selectedPerubahan)) {
    $queryParams['perubahan'] = $selectedPerubahan;
}

// Hapus parameter kosong agar URL bersih
$queryParams = array_filter($queryParams, function ($value) {
    return $value !== '' && $value !== null;
});
$queryString = http_build_query($queryParams);
$apiUrl = $apiBaseUrl . '?' . $queryString;

// 3. Siapkan parameter untuk mengambil data SUMMARY (statistik)
// Gunakan semua filter KECUALI 'page' dan 'limit'
$summaryParams = $queryParams;
unset($summaryParams['page']);
unset($summaryParams['limit']);
// Tambahkan parameter 'action=summary' untuk memberitahu API kita hanya butuh statistik
$summaryParams['action'] = 'summary';

$summaryQueryString = http_build_query($summaryParams);
$apiSummaryUrl = $apiBaseUrl . '?' . $summaryQueryString;

// 4. Panggil API: satu untuk data tabel, satu untuk statistik
$response = @file_get_contents($apiUrl);
$data = json_decode($response, true);

$summaryResponse = @file_get_contents($apiSummaryUrl);
$summaryData = json_decode($summaryResponse, true);

// 5. Inisialisasi variabel statistik dengan nilai default
$totalPagu = 0;
$totalPaket = 0;
$formattedTotalPagu = 'Rp 0';

// 6. Proses data statistik dari API summary
if ($summaryData && isset($summaryData['success']) && $summaryData['success'] && isset($summaryData['summary'])) {
    $summary = $summaryData['summary'];
    $totalPaket = $summary['total_paket'] ?? 0;
    $totalPagu = $summary['total_pagu'] ?? 0;
    $formattedTotalPagu = '' . number_format($totalPagu, 0, ',', '.');
}

// 7. Siapkan variabel untuk paginasi
$totalPages = $data['pagination']['total_pages'] ?? 1;
$totalRecords = $data['pagination']['total_records'] ?? 0;
if ($totalPaket > 0) {
    $totalRecords = $totalPaket;
}

// Set page title untuk header
$page_title = "Data Pengadaan - SIP BANAR";

// Array nama bulan untuk tampilan
$namaBulan = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
    '04' => 'April', '05' => 'Mei', '06' => 'Juni',
    '07' => 'Juli', '08' => 'Agustus', '09' => 'September',
    '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];

// 8. Ambil daftar Satuan Kerja untuk dropdown
$satuanKerjaList = [];
$apiOptionsUrl = $apiBaseUrl . '?action=options';
$optionsResponse = @file_get_contents($apiOptionsUrl);
if ($optionsResponse) {
    $optionsData = json_decode($optionsResponse, true);
    if ($optionsData && isset($optionsData['success']) && $optionsData['success']) {
        $satuanKerjaList = $optionsData['options']['satuan_kerja'] ?? [];
    }
}

// Debug: Uncomment baris di bawah untuk melihat data yang diterima
// echo '<pre>'; print_r($satuanKerjaList); echo '</pre>'; exit;

// Include header
include '../../navbar/header.php';
?>

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<script src="../../js/submenu.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<style>
    /* Custom CSS untuk halaman pengadaan */
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

    /* Grid Layout untuk Filter */
    .filter-row {
        display: grid;
        gap: 25px;
        margin-bottom: 25px;
    }

    /* Baris pertama: Bulan + Tahun + Jenis Pengadaan + Perubahan */
    .filter-row:nth-child(1) {
        grid-template-columns: 1fr 1fr 1fr 1fr;
    }

    /* Baris kedua: Satuan Kerja + Metode + Pencarian Paket */
    .filter-row:nth-child(2) {
        grid-template-columns: 1fr 1fr 1fr;
    }

    /* Baris ketiga: Limit Data */
    .filter-row:nth-child(3) {
        grid-template-columns: 300px 1fr;
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
        border-color: #dc3545;
        box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.15);
        transform: translateY(-1px);
    }

    .filter-group select:hover,
    .filter-group input[type="text"]:hover {
        border-color: #dc3545;
    }

    /* Custom Select2 Styling */
    .select2-container--default .select2-selection--single {
        height: 50px;
        border: 2px solid #e9ecef;
        border-radius: 10px;
        padding: 6px 16px;
        transition: all 0.3s ease;
    }

    .select2-container--default .select2-selection--single:hover {
        border-color: #dc3545;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px;
        padding-left: 0;
        font-size: 14px;
        color: #2c3e50;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 48px;
        right: 10px;
    }

    .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: #dc3545;
        box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.15);
        transform: translateY(-1px);
    }

    .select2-dropdown {
        border: 2px solid #e9ecef;
        border-radius: 10px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
    }

    .select2-search--dropdown .select2-search__field {
        border: 2px solid #e9ecef;
        border-radius: 8px;
        padding: 10px 14px;
        font-size: 14px;
    }

    .select2-search--dropdown .select2-search__field:focus {
        border-color: #dc3545;
        outline: none;
    }

    .select2-results__option {
        padding: 12px 16px;
        font-size: 14px;
    }

    .select2-results__option--highlighted {
        background-color: #dc3545 !important;
    }

    /* Date Range Styles */
    .date-range-group {
        position: relative;
    }

    .date-range-group label {
        display: block;
        margin-bottom: 10px;
        font-weight: 600;
        color: #2c3e50;
        font-size: 14px;
        letter-spacing: 0.3px;
    }

    .date-range-container {
        display: grid;
        grid-template-columns: 1fr auto 1fr;
        align-items: center;
        gap: 15px;
        background: #f8f9fa;
        padding: 8px 20px;
        border-radius: 10px;
        border: 2px solid #e9ecef;
        transition: all 0.3s ease;
    }

    .date-range-container:focus-within {
        border-color: #dc3545;
        box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.15);
        background: white;
    }

    .date-range-container input[type="date"] {
        border: none;
        background: transparent;
        padding: 10px 12px;
        font-size: 14px;
        color: #2c3e50;
        border-radius: 6px;
        min-width: 140px;
    }

    .date-range-container input[type="date"]:focus {
        outline: none;
        background: white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .date-separator {
        color: #dc3545;
        font-weight: 700;
        font-size: 14px;
        padding: 8px 12px;
        background: white;
        border-radius: 20px;
        border: 2px solid #dc3545;
        white-space: nowrap;
        min-width: 50px;
        text-align: center;
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

    /* Summary Section Styles */
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
        justify-content: space-between;
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

    .summary-header-left {
        display: flex;
        align-items: center;
        gap: 12px;
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

    .period-badge {
        background: rgba(255, 255, 255, 0.2);
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
        border: 2px solid rgba(255, 255, 255, 0.3);
    }

    .summary-content {
        padding: 30px 25px;
    }

    /* Summary Cards */
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

    .card-subtitle {
        font-size: 12px;
        color: #6c757d;
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

    /* Table Styles */
    .table-container {
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
    }

    table th {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        padding: 18px 15px;
        text-align: left;
        font-weight: 600;
        border-bottom: 3px solid #dc3545;
        position: sticky;
        top: 0;
        z-index: 10;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    table td {
        padding: 18px 15px;
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
        padding: 6px 12px;
        font-size: 11px;
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
        font-size: 15px;
    }

    /* Small Text */
    .small-text {
        font-size: 12px;
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
        color: #dc3545;
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
        .date-range-container {
            grid-template-columns: 1fr;
            gap: 15px;
            padding: 15px;
            text-align: center;
        }
        .date-separator {
            transform: rotate(90deg);
        }
    }

    /* Animation */
    .filter-section, .results-section, .summary-section {
        animation: fadeInUp 0.6s ease-out;
    }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<div class="container">
    <div class="filter-section">
        <div class="filter-header">
            <i class="fas fa-filter"></i>
            <h3>Filter Data Pengadaan</h3>
        </div>
        <div class="filter-content">
            <form method="GET" action="" id="filterForm">
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
                            <option value="Jasa Lainnya" <?= ($_GET['jenis_pengadaan'] ?? '') == 'Jasa Lainnya' ? 'selected' : '' ?>>Jasa Lainnya</option>
                            <option value="Jasa Konsultansi" <?= ($_GET['jenis_pengadaan'] ?? '') == 'Jasa Konsultansi' ? 'selected' : '' ?>>Jasa Konsultansi</option>
                            <option value="Barang" <?= ($_GET['jenis_pengadaan'] ?? '') == 'Barang' ? 'selected' : '' ?>>Barang</option>
                            <option value="Konstruksi" <?= ($_GET['konstruksi'] ?? '') == 'Konstruksi' ? 'selected' : '' ?>>Konstruksi</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>
                            <i class="fas fa-exchange-alt"></i> Perubahan
                            <span class="badge-default">DEFAULT: TIDAK</span>
                        </label>
                        <select name="perubahan">
                            <option value="">Semua Status</option>
                            <option value="Perubahan" <?= $selectedPerubahan == 'Perubahan' ? 'selected' : '' ?>>Perubahan</option>
                            <option value="Tidak" <?= $selectedPerubahan == 'Tidak' ? 'selected' : '' ?>>Tidak</option>
                        </select>
                    </div>
                </div>

                <div class="filter-row">
                    <div class="filter-group">
                        <label><i class="fas fa-sitemap"></i> Satuan Kerja</label>
                        <select name="satuan_kerja" id="satuan_kerja" class="select2-searchable">
                            <option value="">Semua Satuan Kerja</option>
                            <?php
                            if (!empty($satuanKerjaList) && is_array($satuanKerjaList)) {
                                $selectedSatker = $_GET['satuan_kerja'] ?? '';
                                foreach ($satuanKerjaList as $satker) {
                                    if (!empty($satker)) {
                                        $selected = $selectedSatker == $satker ? 'selected' : '';
                                        echo '<option value="' . htmlspecialchars($satker) . '" ' . $selected . '>';
                                        echo htmlspecialchars($satker);
                                        echo '</option>';
                                    }
                                }
                            } else {
                                // Fallback: tampilkan pesan jika data tidak tersedia
                                echo '<option value="" disabled>Tidak ada data Satuan Kerja</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label><i class="fas fa-cogs"></i> Metode</label>
                        <select name="metode" id="metode" class="select2-searchable">
                            <option value="">Semua Metode</option>
                            <option value="E-Purchasing" <?= ($_GET['metode'] ?? '') == 'E-Purchasing' ? 'selected' : '' ?>>E-Purchasing</option>
                            <option value="Pengadaan Langsung" <?= ($_GET['metode'] ?? '') == 'Pengadaan Langsung' ? 'selected' : '' ?>>Pengadaan Langsung</option>
                            <option value="Tender" <?= ($_GET['metode'] ?? '') == 'Tender' ? 'selected' : '' ?>>Tender</option>
                            <option value="Dikecualikan" <?= ($_GET['metode'] ?? '') == 'Dikecualikan' ? 'selected' : '' ?>>Dikecualikan</option>
                            <option value="Penunjukan Langsung" <?= ($_GET['metode'] ?? '') == 'Penunjukan Langsung' ? 'selected' : '' ?>>Penunjukan Langsung</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label><i class="fas fa-search"></i> Pencarian Paket</label>
                        <div class="search-input-wrapper">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" placeholder="Cari nama paket..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
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

    <div class="summary-section">
        <div class="summary-header">
            <div class="summary-header-left">
                <i class="fas fa-chart-bar"></i>
                <h3>Ringkasan Data Pengadaan</h3>
            </div>
            <div class="period-badge">
                <i class="fas fa-calendar-check"></i> 
                <?= $namaBulan[$selectedBulan] ?> <?= $selectedTahun ?>
                <?php if (!empty($selectedPerubahan)): ?>
                    | <?= $selectedPerubahan ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="summary-content">
            <div class="summary-cards">
                <div class="summary-card primary">
                    <div class="card-icon">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div class="card-content">
                        <div class="card-value"><?= number_format($totalPaket, 0, ',', '.') ?></div>
                        <div class="card-label">Total Paket</div>
                        <div class="card-subtitle">Pengadaan - <?= $namaBulan[$selectedBulan] ?> <?= $selectedTahun ?></div>
                    </div>
                </div>

                <div class="summary-card success">
                    <div class="card-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="card-content">
                        <div class="card-value"><?= $formattedTotalPagu ?></div>
                        <div class="card-label">Total Pagu</div>
                        <div class="card-subtitle">Keseluruhan - <?= $namaBulan[$selectedBulan] ?> <?= $selectedTahun ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="results-section">
        <div class="results-header">
            <div>
                <div class="results-title">
                    <i class="fas fa-table"></i> Hasil Pencarian Data Pengadaan
                </div>
                <?php if ($data && isset($data['success']) && $data['success']) : ?>
                    <div class="results-subtitle">
                        <strong>Menampilkan <?= count($data['data']) ?> dari <?= number_format($totalRecords, 0, ',', '.') ?> total data</strong>
                        | Periode: <?= $namaBulan[$selectedBulan] ?> <?= $selectedTahun ?>
                        <?php if (!empty($selectedPerubahan)): ?>
                            | Status: <span class="badge <?= $selectedPerubahan == 'Perubahan' ? 'badge-warning' : 'badge-info' ?>"><?= $selectedPerubahan ?></span>
                        <?php endif; ?>
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
                        echo '<a href="?'. $paginationQuery .'&page='. $i .'" class="btn-pagination active">'. $i .'</a>';
                    } elseif (abs($i - $currentPage) < 3 || $i <= 2 || $i > $totalPages - 2) {
                        echo '<a href="?'. $paginationQuery .'&page='. $i .'" class="btn-pagination">'. $i .'</a>';
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
                            <th style="width: 280px;"><i class="fas fa-box"></i> Paket Pengadaan</th>
                            <th style="width: 130px;"><i class="fas fa-money-bill-wave"></i> Pagu (Rp)</th>
                            <th style="width: 140px;"><i class="fas fa-tags"></i> Jenis Pengadaan</th>
                            <th style="width: 100px;"><i class="fas fa-exchange-alt"></i> Perubahan</th>
                            <th style="width: 120px;"><i class="fas fa-store"></i> Usaha Kecil</th>
                            <th style="width: 120px;"><i class="fas fa-cogs"></i> Metode</th>
                            <th style="width: 120px;"><i class="fas fa-calendar"></i> Pemilihan</th>
                            <th style="width: 200px;"><i class="fas fa-sitemap"></i> Satuan Kerja</th>
                            <th style="width: 150px;"><i class="fas fa-map-marker-alt"></i> Lokasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['data'] as $row) : ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 700; color: #2c3e50; margin-bottom: 5px;">
                                        <?= htmlspecialchars($row['Paket']) ?>
                                    </div>
                                    <div class="small-text">
                                        <i class="fas fa-id-card"></i> ID: <?= htmlspecialchars($row['ID']) ?>
                                    </div>
                                </td>
                                <td class="price">
                                    <?php
                                        $paguValue = (int) preg_replace('/[^\d]/', '', $row['Pagu_Rp']);
                                        echo 'Rp ' . number_format($paguValue, 0, ',', '.');
                                    ?>
                                </td>
                                <td><span class="badge badge-primary"><?= htmlspecialchars($row['Jenis_Pengadaan']) ?></span></td>
                                <td>
                                    <span class="badge <?= $row['perubahan'] == 'Perubahan' ? 'badge-warning' : 'badge-info' ?>">
                                        <?= htmlspecialchars($row['perubahan']) ?>
                                    </span>
                                </td>
                                <td><span class="badge badge-success"><?= htmlspecialchars($row['Usaha_Kecil']) ?></span></td>
                                <td><small><?= htmlspecialchars($row['Metode']) ?></small></td>
                                <td><small><?= htmlspecialchars($row['Pemilihan']) ?></small></td>
                                <td><small><?= htmlspecialchars($row['Satuan_Kerja']) ?></small></td>
                                <td><small><?= htmlspecialchars($row['Lokasi']) ?></small></td>
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
                    <strong>Total Data: <?= number_format($totalRecords, 0, ',', '.') ?></strong> pengadaan
                </div>
            </div>

        <?php else : ?>
            <div class="empty-state">
                <i class="fas fa-search-minus"></i>
                <p><strong>Tidak ada data pengadaan yang ditemukan</strong></p>
                <small class="text-muted">
                    Untuk periode <?= $namaBulan[$selectedBulan] ?> <?= $selectedTahun ?>
                    <?php if (!empty($selectedPerubahan)): ?>
                        dengan status <strong><?= $selectedPerubahan ?></strong>
                    <?php endif; ?>. 
                    Coba ubah kriteria pencarian atau pilih bulan lain.
                </small>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- jQuery (required for Select2) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Select2 untuk Satuan Kerja dan Metode
    $('.select2-searchable').select2({
        placeholder: function() {
            return $(this).data('placeholder') || 'Ketik untuk mencari...';
        },
        allowClear: true,
        width: '100%',
        language: {
            noResults: function() {
                return "Tidak ada hasil yang cocok";
            },
            searching: function() {
                return "Mencari...";
            },
            inputTooShort: function() {
                return "Ketik untuk mencari...";
            }
        }
    });

    // Set placeholder khusus untuk masing-masing dropdown
    $('#satuan_kerja').data('placeholder', '-- Ketik atau pilih Satuan Kerja --');
    $('#metode').data('placeholder', '-- Ketik atau pilih Metode --');

    const filterForm = document.querySelector('#filterForm');

    // DEBUG: Check Satuan Kerja dropdown
    const satuanKerjaSelect = document.querySelector('#satuan_kerja');
    if (satuanKerjaSelect) {
        console.log('Satuan Kerja dropdown found');
        console.log('Total options:', satuanKerjaSelect.options.length);
        console.log('Options:', Array.from(satuanKerjaSelect.options).map(opt => opt.value));
        
        // Jika hanya ada 1 option (default), mungkin data tidak terload
        if (satuanKerjaSelect.options.length <= 1) {
            console.warn('⚠️ Satuan Kerja dropdown kosong! Cek API response.');
        }
    }

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

    // Set today's date as max for date inputs (jika ada)
    const today = new Date().toISOString().split('T')[0];

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

    // Format numbers in price columns
    document.querySelectorAll('.price').forEach(priceCell => {
        const text = priceCell.textContent.trim();
        if (text && !isNaN(text.replace(/[^\d]/g, ''))) {
            const number = parseInt(text.replace(/[^\d]/g, ''));
            if (number > 0) {
                priceCell.innerHTML = '<i class="fas fa-rupiah-sign" style="font-size: 12px; margin-right: 3px;"></i>Rp ' + number.toLocaleString('id-ID');
            }
        }
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
});

// Reset form function - kembali ke default Juli tahun ini + Perubahan: Tidak
function resetForm() {
    window.location.href = window.location.pathname + '?bulan=07&tahun=<?= $currentYear ?>&perubahan=Tidak';
}

// Form validation before submit
document.querySelector('#filterForm').addEventListener('submit', function(e) {
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

// Add copy functionality to ID
document.querySelectorAll('.small-text').forEach(smallText => {
    if (smallText.textContent.includes('ID:')) {
        smallText.style.cursor = 'pointer';
        smallText.title = 'Klik untuk copy ID';
        smallText.addEventListener('click', function(e) {
            e.stopPropagation();
            const idText = this.textContent.replace('ID: ', '').trim();
            navigator.clipboard.writeText(idText).then(() => {
                const originalText = this.textContent;
                this.textContent = '✓ ID Copied!';
                this.style.color = '#27ae60';
                setTimeout(() => {
                    this.textContent = originalText;
                    this.style.color = '';
                }, 1500);
            });
        });
    }
});
</script>

<?php
// Include footer
include '../../navbar/footer.php';
?>