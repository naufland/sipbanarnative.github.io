<?php
// =================================================================
// == FILE DASHBOARD UNTUK REALISASI NON-TENDER DENGAN FILTER BULAN
// =================================================================

// 1. URL API untuk Non-Tender
$apiBaseUrl = "http://sipbanarnative.id/api/pencatatan_nontender.php";

// 2. Dapatkan parameter dari URL, termasuk halaman saat ini
$currentPage = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = $_GET['limit'] ?? 50;

// BARU: Dapatkan filter bulan dan tahun
// Default bulan Juli (07) dan tahun sekarang
$currentYear = date('Y');
$selectedBulan = $_GET['bulan'] ?? '07'; // Default Juli
$selectedTahun = $_GET['tahun'] ?? $currentYear;

// 3. Siapkan parameter query untuk API
$queryParams = $_GET;
$queryParams['page'] = $currentPage;
$queryParams['limit'] = $limit;
$queryParams['bulan'] = $selectedBulan;
$queryParams['tahun'] = $selectedTahun;

// Hapus parameter kosong agar URL bersih
$queryParams = array_filter($queryParams, function ($value) {
    return $value !== '' && $value !== null;
});
$queryString = http_build_query($queryParams);
$apiUrl = $apiBaseUrl . '?' . $queryString;

// 4. Siapkan parameter untuk mengambil data SUMMARY
$summaryParams = $queryParams;
unset($summaryParams['page']);
unset($summaryParams['limit']);
$summaryParams['action'] = 'summary';

$summaryQueryString = http_build_query($summaryParams);
$apiSummaryUrl = $apiBaseUrl . '?' . $summaryQueryString;

// 5. Panggil API: satu untuk data tabel, satu untuk statistik
$response = @file_get_contents($apiUrl);
$data = json_decode($response, true);

$summaryResponse = @file_get_contents($apiSummaryUrl);
$summaryData = json_decode($summaryResponse, true);

// BARU: Ambil data options untuk dropdown dari API
$optionsUrl = $apiBaseUrl . '?action=options';
$optionsResponse = @file_get_contents($optionsUrl);
$optionsData = json_decode($optionsResponse, true);

// Inisialisasi options
$metodePengadaanOptions = $optionsData['options']['metode_pengadaan'] ?? [];
$jenisPengadaanOptions = $optionsData['options']['jenis_pengadaan'] ?? [];
$satkerOptions = $optionsData['options']['nama_satker'] ?? [];
$yearsOptions = $optionsData['options']['years'] ?? [];

// 6. Inisialisasi variabel statistik dengan nilai default
$totalPaket = 0;
$totalPagu = 0;
$total_realisasi = 0;
$efisiensi = 0;
$formattedTotalPagu = 'Rp 0';
$formattedTotalRealisasi = 'Rp 0';
$formattedEfisiensi = '0%';

// 7. Proses data statistik dari API summary
if ($summaryData && isset($summaryData['success']) && $summaryData['success'] && isset($summaryData['summary'])) {
    $summary = $summaryData['summary'];
    $totalPaket = $summary['total_paket'] ?? 0;
    $totalPagu = $summary['total_pagu'] ?? 0;
    $total_realisasi = $summary['total_realisasi'] ?? 0;

    if ($totalPagu > 0) {
        $efisiensi = (($totalPagu - $total_realisasi) / $totalPagu) * 100;
    }

    $formattedTotalPagu = 'Rp ' . number_format($totalPagu, 0, ',', '.');
    $formattedTotalRealisasi = 'Rp ' . number_format($total_realisasi, 0, ',', '.');
    $formattedEfisiensi = number_format($efisiensi, 2, ',', '.') . '%';
}

// 8. Siapkan variabel untuk paginasi
$tableData = $data['data'] ?? [];
$totalPages = $data['pagination']['total_pages'] ?? 1;
$totalRecords = $data['pagination']['total_records'] ?? 0;
if ($totalPaket > 0) {
    $totalRecords = $totalPaket;
}

// Set page title untuk header
$page_title = "Data Realisasi Non-Tender - SIP BANAR";

// Array nama bulan untuk tampilan
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

<!-- Select2 CSS & JS untuk searchable dropdown -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
    body {
        font-family: 'Inter', sans-serif;
        background-color: #f8f9fa;
    }

    .container {
        max-width: 1600px;
        margin: 0 auto;
        padding: 20px;
    }

    .filter-section,
    .summary-section,
    .results-section {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        margin-bottom: 30px;
        border: 1px solid #e9ecef;
        animation: fadeInUp 0.6s ease-out;
    }

    .filter-header,
    .summary-header {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
        padding: 20px 25px;
        display: flex;
        align-items: center;
        gap: 12px;
        position: relative;
        border-top-left-radius: 15px;
        border-top-right-radius: 15px;
        clip-path: none !important;
        overflow: hidden;
    }

    .filter-header::after,
    .summary-header::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, #dc3545, #e74c3c, #dc3545);
    }

    .filter-header i,
    .summary-header i {
        font-size: 20px;
    }

    .filter-header h3,
    .summary-header h3 {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
        letter-spacing: 0.5px;
    }

    .summary-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .summary-header-left {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .period-badge {
        background: rgba(255, 255, 255, 0.2);
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
        border: 2px solid rgba(255, 255, 255, 0.3);
    }

    .filter-content,
    .summary-content {
        padding: 30px 25px;
    }

    .filter-row {
        display: grid;
        gap: 25px;
        margin-bottom: 25px;
    }

    .filter-row:nth-child(1) {
        grid-template-columns: 1fr 1fr 1fr;
    }

    .filter-row:nth-child(2) {
        grid-template-columns: 1fr 1fr 2fr;
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
    .filter-group input {
        width: 100%;
        padding: 14px 16px;
        border: 2px solid #e9ecef;
        border-radius: 10px;
        font-size: 14px;
        background: white;
        transition: all 0.3s ease;
        color: #2c3e50;
        box-sizing: border-box;
    }

    /* Style untuk Select2 agar sesuai dengan design */
    .select2-container--default .select2-selection--single {
        height: 50px;
        padding: 14px 16px;
        border: 2px solid #e9ecef;
        border-radius: 10px;
        background: white;
        transition: all 0.3s ease;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 22px;
        color: #2c3e50;
        padding-left: 0;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 48px;
        right: 10px;
    }

    .select2-container--default.select2-container--open .select2-selection--single,
    .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: #dc3545;
        box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.15);
    }

    .select2-container--default .select2-selection--single:hover {
        border-color: #dc3545;
    }

    .select2-dropdown {
        border: 2px solid #dc3545;
        border-radius: 10px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }

    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: #dc3545;
    }

    .select2-search--dropdown .select2-search__field {
        border: 2px solid #e9ecef;
        border-radius: 8px;
        padding: 8px 12px;
    }

    .select2-search--dropdown .select2-search__field:focus {
        border-color: #dc3545;
        outline: none;
    }

    .filter-group select:focus,
    .filter-group input:focus {
        outline: none;
        border-color: #dc3545;
        box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.15);
        transform: translateY(-1px);
    }

    .filter-group select:hover,
    .filter-group input:hover {
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

    .search-input-wrapper input {
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

    .search-btn,
    .reset-btn {
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

    .search-btn {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
    }

    .search-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(220, 53, 69, 0.3);
    }

    .reset-btn {
        background: transparent;
        color: #6c757d;
        border: 2px solid #e9ecef;
        min-width: 120px;
    }

    .reset-btn:hover {
        border-color: #dc3545;
        color: #dc3545;
        background: #fff5f5;
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

    .summary-card.warning::before {
        background: #f39c12;
    }

    .summary-card.success::before {
        background: #27ae60;
    }

    .summary-card.info::before {
        background: #17a2b8;
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

    .summary-card.warning .card-icon {
        background: linear-gradient(135deg, #f39c12, #f8c471);
    }

    .summary-card.success .card-icon {
        background: linear-gradient(135deg, #27ae60, #58d68d);
    }

    .summary-card.info .card-icon {
        background: linear-gradient(135deg, #17a2b8, #5dccda);
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
        min-width: 1600px;
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

    .badge-success {
        background: #27ae60;
        color: white;
    }

    .badge-info {
        background: #17a2b8;
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

    .text-right {
        text-align: right;
    }

    .text-success {
        color: #27ae60;
        font-weight: 600;
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
            grid-template-columns: 1fr 1fr;
        }

        .filter-row:nth-child(2) {
            grid-template-columns: 1fr 1fr;
        }
    }

    @media (max-width: 992px) {

        .filter-row:nth-child(1),
        .filter-row:nth-child(2) {
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
            <h3>Filter Data Pencatatan Non-Tender</h3>
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
                            <?php if (!empty($yearsOptions)): ?>
                                <?php foreach ($yearsOptions as $year): ?>
                                    <option value="<?= $year ?>" <?= $selectedTahun == $year ? 'selected' : '' ?>>
                                        <?= $year ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <?php for ($y = $currentYear; $y >= 2020; $y--): ?>
                                    <option value="<?= $y ?>" <?= $selectedTahun == $y ? 'selected' : '' ?>>
                                        <?= $y ?>
                                    </option>
                                <?php endfor; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label><i class="fas fa-building"></i> Satuan Kerja</label>
                        <select name="nama_satker" id="select-satker" class="searchable-select">
                            <option value="">Semua Satuan Kerja</option>
                            <?php if (!empty($satkerOptions)): ?>
                                <?php foreach ($satkerOptions as $satker): ?>
                                    <option value="<?= htmlspecialchars($satker) ?>" <?= ($_GET['nama_satker'] ?? '') == $satker ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($satker) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>

                <div class="filter-row">
                    <div class="filter-group">
                        <label><i class="fas fa-handshake"></i> Metode Pengadaan</label>
                        <select name="metode_pengadaan">
                            <option value="">Semua Metode</option>
                            <?php if (!empty($metodePengadaanOptions)): ?>
                                <?php foreach ($metodePengadaanOptions as $metode): ?>
                                    <option value="<?= htmlspecialchars($metode) ?>" <?= ($_GET['metode_pengadaan'] ?? '') == $metode ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($metode) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="Penunjukan Langsung">Penunjukan Langsung</option>
                                <option value="Pengadaan Langsung">Pengadaan Langsung</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label><i class="fas fa-box"></i> Jenis Pengadaan</label>
                        <select name="jenis_pengadaan">
                            <option value="">Semua Jenis</option>
                            <?php if (!empty($jenisPengadaanOptions)): ?>
                                <?php foreach ($jenisPengadaanOptions as $jenis): ?>
                                    <option value="<?= htmlspecialchars($jenis) ?>" <?= ($_GET['jenis_pengadaan'] ?? '') == $jenis ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($jenis) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="Barang">Barang</option>
                                <option value="Pekerjaan Konstruksi">Pekerjaan Konstruksi</option>
                                <option value="Jasa Konsultansi">Jasa Konsultansi</option>
                                <option value="Jasa Lainnya">Jasa Lainnya</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label><i class="fas fa-search"></i> Pencarian Paket</label>
                        <div class="search-input-wrapper">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" placeholder="Cari Nama Paket, Satker, atau Pemenang..."
                                value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
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
                <h3>Ringkasan Data Pencatatan Non-Tender</h3>
            </div>
            <div class="period-badge">
                <i class="fas fa-calendar-check"></i>
                <?= $namaBulan[$selectedBulan] ?> <?= $selectedTahun ?>
            </div>
        </div>
        <div class="summary-content">
            <div class="summary-cards">
                <div class="summary-card primary">
                    <div class="card-icon"><i class="fas fa-boxes"></i></div>
                    <div class="card-content">
                        <div class="card-value"><?= number_format($totalPaket, 0, ',', '.') ?></div>
                        <div class="card-label">Total Paket</div>
                        <div class="card-subtitle">Realisasi - <?= $namaBulan[$selectedBulan] ?> <?= $selectedTahun ?>
                        </div>
                    </div>
                </div>
                <div class="summary-card warning">
                    <div class="card-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                    <div class="card-content">
                        <div class="card-value"><?= $formattedTotalPagu ?></div>
                        <div class="card-label">Total Pagu</div>
                        <div class="card-subtitle">Keseluruhan - <?= $namaBulan[$selectedBulan] ?> <?= $selectedTahun ?>
                        </div>
                    </div>
                </div>

                <div class="summary-card success">
                    <div class="card-icon"><i class="fas fa-handshake"></i></div>
                    <div class="card-content">
                        <div class="card-value"><?= $formattedTotalRealisasi ?></div>
                        <div class="card-label">Total Realisasi</div>
                        <div class="card-subtitle">Nilai Terealisasi - <?= $namaBulan[$selectedBulan] ?>
                            <?= $selectedTahun ?></div>
                    </div>
                </div>

                <div class="summary-card info">
                    <div class="card-icon"><i class="fas fa-percent"></i></div>
                    <div class="card-content">
                        <div class="card-value"><?= $formattedEfisiensi ?></div>
                        <div class="card-label">Efisiensi Anggaran</div>
                        <div class="card-subtitle">Periode <?= $namaBulan[$selectedBulan] ?> <?= $selectedTahun ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="results-section">
        <div class="results-header">
            <div>
                <div class="results-title">
                    <i class="fas fa-table"></i> Hasil Data Realisasi Non-Tender
                </div>
                <?php if ($data && isset($data['success']) && $data['success']): ?>
                    <div class="results-subtitle">
                        <strong>Menampilkan <?= count($tableData) ?> dari <?= number_format($totalRecords, 0, ',', '.') ?>
                            total data</strong>
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

                <a href="?<?= $paginationQuery ?>&page=<?= max(1, $currentPage - 1) ?>"
                    class="btn-pagination <?= $currentPage <= 1 ? 'disabled' : '' ?>" title="Halaman Sebelumnya">
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

                <a href="?<?= $paginationQuery ?>&page=<?= min($totalPages, $currentPage + 1) ?>"
                    class="btn-pagination <?= $currentPage >= $totalPages ? 'disabled' : '' ?>"
                    title="Halaman Selanjutnya">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        </div>
    </div>

    <?php if (!empty($tableData)): ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th style="width: 40px;"><i class="fas fa-hashtag"></i> No</th>
                        <th style="width: 280px;"><i class="fas fa-box"></i> Nama Paket</th>
                        <th style="width: 220px;"><i class="fas fa-building"></i> Satuan Kerja</th>
                        <th style="width: 120px;"><i class="fas fa-barcode"></i> Kode Paket</th>
                        <th style="width: 120px;"><i class="fas fa-trophy"></i> Kode RUP</th>
                        <th style="width: 100px;"><i class="fas fa-handshake"></i> Metode</th>
                        <th style="width: 100px;"><i class="fas fa-box"></i> Jenis</th>
                        <th style="width: 130px;"><i class="fas fa-money-bill-wave"></i> Nilai Pagu</th>
                        <th style="width: 130px;"><i class="fas fa-file-contract"></i> Nilai Realisasi</th>
                        <th style="width: 200px;"><i class="fas fa-trophy"></i> Pemenang</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tableData as $row): ?>
                        <tr>
                            <td style="text-align: center; font-weight: 700; color: #2c3e50;">
                                <?= htmlspecialchars($row['No_Urut'] ?? '-') ?>
                            </td>
                            <td>
                                <div style="font-weight: 700; color: #2c3e50; margin-bottom: 5px; line-height: 1.4;">
                                    <?= htmlspecialchars($row['Nama_Paket'] ?? '-') ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 600; color: #34495e; line-height: 1.4;">
                                    <i class="fas fa-sitemap"></i> <?= htmlspecialchars($row['Nama_Satker'] ?? '-') ?>
                                </div>
                            </td>
                            <td>
                                <div class="small-text">
                                    <i class="fas fa-barcode"></i> <?= htmlspecialchars($row['Kode_Paket'] ?? '-') ?>
                                </div>
                            </td>
                            <td>
                                <div class="small-text">
                                    <i class="fas fa-trophy" style="color: #f39c12;"></i>
                                    <?= htmlspecialchars($row['Kode_RUP'] ?? '-') ?>
                                </div>
                            </td>
                            <td style="text-align: center;">
                                <span class="badge badge-success">
                                    <?= htmlspecialchars($row['Metode_pengadaan'] ?? '-') ?>
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <span class="badge badge-info">
                                    <?= htmlspecialchars($row['Jenis_Pengadaan'] ?? '-') ?>
                                </span>
                            </td>
                            <td>
                                <div class="price" style="font-size: 13px; color: #6c757d;">
                                    <?= 'Rp ' . number_format($row['Nilai_Pagu'] ?? 0, 0, ',', '.') ?>
                                </div>
                            </td>
                            <td>
                                <div class="price" style="font-size: 13px;">
                                    <?= 'Rp ' . number_format($row['Nilai_Total_Realisasi'] ?? 0, 0, ',', '.') ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 600; color: #2980b9; line-height: 1.4;">
                                    <i class="fas fa-trophy"></i> <?= htmlspecialchars($row['Nama_Pemenang'] ?? '-') ?>
                                </div>
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
                <strong>Total Data: <?= number_format($totalRecords, 0, ',', '.') ?></strong> paket non-tender
            </div>
        </div>

    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-search-minus"></i>
            <p><strong>Tidak ada data realisasi non-tender yang ditemukan</strong></p>
            <small class="text-muted">
                Untuk periode <?= $namaBulan[$selectedBulan] ?> <?= $selectedTahun ?>.
                Coba ubah kriteria pencarian atau pilih bulan lain.
            </small>
        </div>
    <?php endif; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Inisialisasi Select2 untuk dropdown Satuan Kerja
        $('#select-satker').select2({
            placeholder: 'Ketik untuk mencari satuan kerja...',
            allowClear: true,
            width: '100%',
            language: {
                noResults: function () {
                    return "Satuan kerja tidak ditemukan";
                },
                searching: function () {
                    return "Mencari...";
                }
            }
        });

        const filterForm = document.querySelector('form');

        if (filterForm) {
            filterForm.addEventListener('submit', function (e) {
                const inputs = this.querySelectorAll('input, select');

                inputs.forEach(input => {
                    if (input.name !== 'bulan' && input.name !== 'tahun' && !input.value) {
                        input.disabled = true;
                    }
                });

                return true;
            });
        }

        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput) {
            searchInput.addEventListener('keypress', function (e) {
                if (e.key === 'Enter') {
                    this.form.submit();
                }
            });

            searchInput.addEventListener('input', function () {
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

        const tableRows = document.querySelectorAll('tbody tr');
        tableRows.forEach(row => {
            row.addEventListener('mouseenter', function () {
                this.style.transform = 'translateY(-2px)';
            });

            row.addEventListener('mouseleave', function () {
                this.style.transform = 'translateY(0)';
            });
        });

        const bulanSelect = document.querySelector('select[name="bulan"]');
        const tahunSelect = document.querySelector('select[name="tahun"]');

        if (bulanSelect) {
            bulanSelect.addEventListener('change', function () {
                // Optional: auto-submit ketika bulan berubah
                // this.form.submit();
            });
        }

        if (tahunSelect) {
            tahunSelect.addEventListener('change', function () {
                // Optional: auto-submit ketika tahun berubah
                // this.form.submit();
            });
        }
    });

    function resetForm() {
        window.location.href = window.location.pathname + '?bulan=07&tahun=<?= $currentYear ?>';
    }

    document.querySelector('form').addEventListener('submit', function (e) {
        const submitBtn = this.querySelector('.search-btn');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mencari...';
        submitBtn.disabled = true;

        setTimeout(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }, 5000);
    });

    window.addEventListener('load', function () {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.toString()) {
            document.querySelector('.results-section').scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });

    document.querySelectorAll('.small-text').forEach(smallText => {
        if (smallText.textContent.includes('Kode') || smallText.textContent.includes('RUP')) {
            smallText.style.cursor = 'pointer';
            smallText.title = 'Klik untuk copy kode';
            smallText.addEventListener('click', function (e) {
                e.stopPropagation();
                const code = this.textContent.replace(/.*:\s*/, '').trim();
                navigator.clipboard.writeText(code).then(() => {
                    const originalText = this.textContent;
                    this.textContent = '✓ Kode Copied!';
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