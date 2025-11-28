<?php
// =================================================================
// == FILE DASHBOARD UNTUK REALISASI PENUNJUKAN LANGSUNG ==========
// =================================================================

// 1. URL API untuk Penunjukan Langsung
$apiBaseUrl = "http://sipbanarnative.id/api/realisasi_penunjukanlangsung.php";

// 2. Dapatkan parameter dari URL, termasuk halaman saat ini
$currentPage = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = $_GET['limit'] ?? 50;

// BARU: Dapatkan filter bulan dan tahun
// Default bulan Juli (07) dan tahun sekarang
$currentYear = date('Y');
$selectedBulan = $_GET['bulan'] ?? '07'; // Default Juli
$selectedTahun = $_GET['tahun'] ?? $currentYear;

// 3. Siapkan parameter query untuk API data tabel
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

// 5. Panggil API untuk data tabel dan data summary
$response = @file_get_contents($apiUrl);
$data = json_decode($response, true);

$summaryResponse = @file_get_contents($apiSummaryUrl);
$summaryData = json_decode($summaryResponse, true);

// BARU: Ambil data options untuk dropdown dari API
$optionsUrl = $apiBaseUrl . '?action=options';
$optionsResponse = @file_get_contents($optionsUrl);
$optionsData = json_decode($optionsResponse, true);

// Inisialisasi options
$jenisPengadaanOptions = $optionsData['options']['jenis_pengadaan'] ?? [];
$satkerOptions = $optionsData['options']['nama_satker'] ?? [];
$statusPaketOptions = $optionsData['options']['status_paket'] ?? [];
$yearsOptions = $optionsData['options']['years'] ?? [];

// 6. Inisialisasi dan proses variabel statistik (disesuaikan untuk Penunjukan Langsung)
$totalPaket = 0;
$totalPagu = 0;
$totalHPS = 0;
$totalKontrak = 0;
$totalPDN = 0;
$totalUMK = 0;
$efisiensi = 0;
$persentasePDN = 0;
$persentaseUMK = 0;
$formattedTotalPagu = 'Rp 0';
$formattedTotalHPS = 'Rp 0';
$formattedTotalKontrak = 'Rp 0';
$formattedTotalPDN = 'Rp 0';
$formattedTotalUMK = 'Rp 0';
$formattedEfisiensi = '0%';
$formattedPersentasePDN = '0%';
$formattedPersentaseUMK = '0%';

if ($summaryData && ($summaryData['success'] ?? false) && isset($summaryData['summary'])) {
    $summary = $summaryData['summary'];
    $totalPaket = $summary['total_paket'] ?? 0;
    $totalPagu = $summary['total_pagu'] ?? 0;
    $totalHPS = $summary['total_hps'] ?? 0;
    $totalKontrak = $summary['total_kontrak'] ?? 0;
    $totalPDN = $summary['total_pdn'] ?? 0;
    $totalUMK = $summary['total_umk'] ?? 0;

    $formattedTotalPagu = 'Rp ' . number_format($totalPagu, 0, ',', '.');
    $formattedTotalHPS = 'Rp ' . number_format($totalHPS, 0, ',', '.');
    $formattedTotalKontrak = 'Rp ' . number_format($totalKontrak, 0, ',', '.');
    $formattedTotalPDN = 'Rp ' . number_format($totalPDN, 0, ',', '.');
    $formattedTotalUMK = 'Rp ' . number_format($totalUMK, 0, ',', '.');

    // Hitung efisiensi anggaran
    if ($totalPagu > 0) {
        $efisiensi = (($totalPagu - $totalKontrak) / $totalPagu) * 100;
    }

    // Hitung persentase PDN dan UMK
    $persentasePDN = $summary['persentase_pdn'] ?? 0;
    $persentaseUMK = $summary['persentase_umk'] ?? 0;

    $formattedEfisiensi = number_format($efisiensi, 2, ',', '.') . '%';
    $formattedPersentasePDN = number_format($persentasePDN, 2, ',', '.') . '%';
    $formattedPersentaseUMK = number_format($persentaseUMK, 2, ',', '.') . '%';
}

// 7. Siapkan variabel untuk paginasi
$tableData = $data['data'] ?? [];
$totalPages = $data['pagination']['total_pages'] ?? 1;
$totalRecords = $data['pagination']['total_records'] ?? 0;
if ($totalPaket > 0) {
    $totalRecords = $totalPaket;
}

// 8. Set judul halaman
$page_title = "Data Realisasi Penunjukan Langsung - SIP BANAR";

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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script src="../../js/submenu.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

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
        overflow: hidden;
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

    /* Baris pertama: Bulan + Tahun + Satker */
    .filter-row:nth-child(1) {
        grid-template-columns: 1fr 1fr 1fr;
    }

    /* Baris kedua: Jenis + Status + Pencarian */
    .filter-row:nth-child(2) {
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
        padding: 0;
    }

    .summary-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 0;
    }

    .summary-card {
        padding: 30px 25px;
        display: flex;
        align-items: center;
        gap: 20px;
        border-right: 1px solid #e9ecef;
        border-bottom: 1px solid #e9ecef;
        background: white;
        transition: all 0.3s ease;
        position: relative;
    }

    .summary-card:last-child {
        border-right: none;
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

    .summary-card.danger::before {
        background: #e74c3c;
    }

    .summary-card.purple::before {
        background: #9b59b6;
    }

    .summary-card:hover {
        background: #f8f9fa;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
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
        flex-shrink: 0;
    }

    .summary-card.primary .card-icon {
        background: linear-gradient(135deg, #3498db, #2980b9);
    }

    .summary-card.success .card-icon {
        background: linear-gradient(135deg, #27ae60, #229954);
    }

    .summary-card.info .card-icon {
        background: linear-gradient(135deg, #17a2b8, #138496);
    }

    .summary-card.warning .card-icon {
        background: linear-gradient(135deg, #f39c12, #e67e22);
    }

    .summary-card.danger .card-icon {
        background: linear-gradient(135deg, #e74c3c, #c0392b);
    }

    .summary-card.purple .card-icon {
        background: linear-gradient(135deg, #9b59b6, #8e44ad);
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
        min-width: 1800px;
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

    .badge-info {
        background: #17a2b8;
        color: white;
    }

    .badge-success {
        background: #27ae60;
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

    .text-danger {
        color: #dc3545;
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

    @media (max-width: 1200px) {

        .filter-row:nth-child(1),
        .filter-row:nth-child(2) {
            grid-template-columns: 1fr 1fr;
        }

        .summary-cards {
            grid-template-columns: repeat(2, 1fr);
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

        .summary-cards {
            grid-template-columns: 1fr;
        }

        .summary-card {
            border-right: none;
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

    /* Custom Select2 Styling */
    .select2-container--default .select2-selection--single {
        height: 46px;
        border: 2px solid #e1e8ed;
        border-radius: 8px;
        padding: 6px 16px;
        transition: all 0.2s ease;
    }

    .select2-container--default .select2-selection--single:hover {
        border-color: #dc3545;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 32px;
        padding-left: 0;
        font-size: 14px;
        color: #495057;
        font-weight: 500;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 44px;
        right: 10px;
    }

    .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: #dc3545;
        box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
    }

    .select2-dropdown {
        border: 2px solid #e1e8ed;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .select2-search--dropdown .select2-search__field {
        border: 2px solid #e1e8ed;
        border-radius: 6px;
        padding: 8px 12px;
        font-size: 14px;
    }

    .select2-search--dropdown .select2-search__field:focus {
        border-color: #dc3545;
        outline: none;
    }

    .select2-results__option {
        padding: 10px 14px;
        font-size: 14px;
    }

    .select2-results__option--highlighted {
        background-color: #dc3545 !important;
    }
</style>

<div class="container">
    <div class="filter-section">
        <div class="filter-header">
            <i class="fas fa-filter"></i>
            <h3>Filter Data Realisasi Penunjukan Langsung</h3>
        </div>
        <div class="filter-content">
            <form id="filterForm" method="GET" action="">
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
                        <select name="nama_satker" id="nama_satker">
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
                                <option value="Barang" <?= ($_GET['jenis_pengadaan'] ?? '') == 'Barang' ? 'selected' : '' ?>>
                                    Barang</option>
                                <option value="Pekerjaan Konstruksi" <?= ($_GET['jenis_pengadaan'] ?? '') == 'Pekerjaan Konstruksi' ? 'selected' : '' ?>>Pekerjaan Konstruksi</option>
                                <option value="Jasa Konsultansi" <?= ($_GET['jenis_pengadaan'] ?? '') == 'Jasa Konsultansi' ? 'selected' : '' ?>>Jasa Konsultansi</option>
                                <option value="Jasa Lainnya" <?= ($_GET['jenis_pengadaan'] ?? '') == 'Jasa Lainnya' ? 'selected' : '' ?>>Jasa Lainnya</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label><i class="fas fa-info-circle"></i> Status Paket</label>
                        <select name="status_paket">
                            <option value="">Semua Status</option>
                            <?php if (!empty($statusPaketOptions)): ?>
                                <?php foreach ($statusPaketOptions as $status): ?>
                                    <option value="<?= htmlspecialchars($status) ?>" <?= ($_GET['status_paket'] ?? '') == $status ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($status) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="Aktif" <?= ($_GET['status_paket'] ?? '') == 'Aktif' ? 'selected' : '' ?>>Aktif
                                </option>
                                <option value="Selesai" <?= ($_GET['status_paket'] ?? '') == 'Selesai' ? 'selected' : '' ?>>
                                    Selesai</option>
                                <option value="Batal" <?= ($_GET['status_paket'] ?? '') == 'Batal' ? 'selected' : '' ?>>Batal
                                </option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label><i class="fas fa-search"></i> Pencarian</label>
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
                <h3>Ringkasan Data Realisasi Penunjukan Langsung</h3>
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
                        <div class="card-subtitle">Penunjukan Langsung - <?= $namaBulan[$selectedBulan] ?>
                            <?= $selectedTahun ?>
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
                        <div class="card-value"><?= $formattedTotalKontrak ?></div>
                        <div class="card-label">Total Nilai Kontrak</div>
                        <div class="card-subtitle">Terealisasi - <?= $namaBulan[$selectedBulan] ?> <?= $selectedTahun ?>
                        </div>
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
                    <i class="fas fa-table"></i> Hasil Data Realisasi Penunjukan Langsung
                </div>
                <?php if ($data && isset($data['success']) && $data['success']): ?>
                    <div class="results-subtitle">
                        <strong>Menampilkan <?= count($tableData) ?> dari <?= number_format($totalRecords, 0, ',', '.') ?>
                            total data</strong>
                        | Periode: <?= $namaBulan[$selectedBulan] ?>     <?= $selectedTahun ?>
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

        <?php if (!empty($tableData)): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 40px;"><i class="fas fa-hashtag"></i> No</th>
                            <th style="width: 280px;"><i class="fas fa-box"></i> Nama Paket</th>
                            <th style="width: 120px;"><i class="fas fa-barcode"></i> Kode Paket</th>
                            <th style="width: 120px;"><i class="fas fa-trophy"></i> Kode RUP</th>
                            <th style="width: 220px;"><i class="fas fa-building"></i> Satuan Kerja</th>
                            <th style="width: 140px;"><i class="fas fa-tags"></i> Jenis Pengadaan</th>
                            <th style="width: 130px;"><i class="fas fa-money-bill-wave"></i> Nilai Pagu</th>
                            <th style="width: 130px;"><i class="fas fa-file-invoice"></i> Nilai HPS</th>
                            <th style="width: 130px;"><i class="fas fa-handshake"></i> Nilai Kontrak</th>
                            <th style="width: 120px;"><i class="fas fa-flag"></i> Nilai PDN</th>
                            <th style="width: 120px;"><i class="fas fa-industry"></i> Nilai UMK</th>
                            <th style="width: 220px;"><i class="fas fa-trophy"></i> Pemenang</th>
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
                                    <?php if (!empty($row['Status_Paket'])): ?>
                                        <span
                                            class="badge <?= $row['Status_Paket'] == 'Aktif' ? 'badge-success' : ($row['Status_Paket'] == 'Selesai' ? 'badge-info' : 'badge-danger') ?>">
                                            <?= htmlspecialchars($row['Status_Paket']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small style="color: #6c757d;">
                                        <i class="fas fa-barcode"></i> <?= htmlspecialchars($row['Kode_Paket'] ?? '-') ?>
                                    </small>
                                </td>
                                <td>
                                    <small style="color: #f39c12; font-weight: 600;">
                                        <i class="fas fa-trophy"></i> <?= htmlspecialchars($row['Kode_RUP'] ?? '-') ?>
                                    </small>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: #34495e; line-height: 1.4;">
                                        <i class="fas fa-sitemap"></i> <?= htmlspecialchars($row['Nama_Satker'] ?? '-') ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-primary">
                                        <?= htmlspecialchars($row['Jenis_Pengadaan'] ?? '-') ?>
                                    </span>
                                </td>
                                <td class="text-right">
                                    <div class="price" style="color: #6c757d; font-size: 12px;">
                                        <?php
                                        $nilaiPagu = $row['Nilai_Pagu'] ?? 0;
                                        echo 'Rp ' . number_format($nilaiPagu, 0, ',', '.');
                                        ?>
                                    </div>
                                </td>
                                <td class="text-right">
                                    <div class="price" style="color: #e67e22; font-size: 12px;">
                                        <?php
                                        $nilaiHPS = $row['Nilai_HPS'] ?? 0;
                                        echo 'Rp ' . number_format($nilaiHPS, 0, ',', '.');
                                        ?>
                                    </div>
                                </td>
                                <td class="text-right">
                                    <div class="price" style="font-size: 13px;">
                                        <?php
                                        $nilaiKontrak = $row['Nilai_Kontrak'] ?? 0;
                                        echo 'Rp ' . number_format($nilaiKontrak, 0, ',', '.');
                                        ?>
                                    </div>
                                </td>
                                <td class="text-right">
                                    <div class="price" style="color: #e74c3c; font-size: 12px;">
                                        <?php
                                        $nilaiPDN = $row['Nilai_PDN'] ?? 0;
                                        echo 'Rp ' . number_format($nilaiPDN, 0, ',', '.');
                                        ?>
                                    </div>
                                </td>
                                <td class="text-right">
                                    <div class="price" style="color: #9b59b6; font-size: 12px;">
                                        <?php
                                        $nilaiUMK = $row['Nilai_UMK'] ?? 0;
                                        echo 'Rp ' . number_format($nilaiUMK, 0, ',', '.');
                                        ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 700; color: #2980b9; line-height: 1.4;">
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
                    <strong>Total Data: <?= number_format($totalRecords, 0, ',', '.') ?></strong> paket penunjukan langsung
                </div>
            </div>

        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-search-minus"></i>
                <p><strong>Tidak ada data penunjukan langsung yang ditemukan</strong></p>
                <small class="text-muted">
                    Untuk periode <?= $namaBulan[$selectedBulan] ?>     <?= $selectedTahun ?>.
                    Coba ubah kriteria pencarian atau pilih bulan lain.
                </small>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function resetForm() {
    window.location.href = window.location.pathname + '?bulan=07&tahun=<?= $currentYear ?>';
}

document.addEventListener('DOMContentLoaded', function () {
    // ===== INISIALISASI SELECT2 UNTUK SATUAN KERJA =====
    if (typeof jQuery !== 'undefined' && typeof jQuery.fn.select2 !== 'undefined') {
        $('#nama_satker').select2({
            placeholder: '🔍 Ketik untuk mencari Satuan Kerja...',
            allowClear: true,
            width: '100%',
            minimumResultsForSearch: 0, // Selalu tampilkan search box
            language: {
                noResults: function () {
                    return "❌ Tidak ditemukan Satuan Kerja yang cocok";
                },
                searching: function () {
                    return "⏳ Mencari...";
                },
                inputTooShort: function () {
                    return "Ketik minimal 1 karakter...";
                }
            }
        });
        console.log('✅ Select2 initialized untuk Satuan Kerja');
    } else {
        console.error('❌ jQuery atau Select2 tidak ter-load!');
    }

    // Search input enter key
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                this.form.submit();
            }
        });

        // Clear search icon functionality
        searchInput.addEventListener('input', function () {
            const wrapper = this.closest('.search-input-wrapper');
            if (wrapper) {
                const icon = wrapper.querySelector('i');
                if (icon) {
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
                }
            }
        });
    }

    // Table row hover effects
    const tableRows = document.querySelectorAll('tbody tr');
    tableRows.forEach(row => {
        row.addEventListener('mouseenter', function () {
            this.style.transform = 'translateY(-2px)';
        });

        row.addEventListener('mouseleave', function () {
            this.style.transform = 'translateY(0)';
        });
    });

    // Form validation before submit
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function (e) {
            const submitBtn = this.querySelector('.search-btn');
            if (submitBtn) {
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mencari...';
                submitBtn.disabled = true;

                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 5000);
            }
        });
    }

    // Highlight filter yang aktif
    const urlParams = new URLSearchParams(window.location.search);
    const filters = ['bulan', 'tahun', 'nama_satker', 'jenis_pengadaan', 'status_paket', 'search'];

    filters.forEach(filter => {
        if (urlParams.has(filter) && urlParams.get(filter) !== '') {
            const element = document.querySelector(`[name="${filter}"]`);
            if (element && element.tagName !== 'SELECT') {
                element.style.borderColor = '#dc3545';
                element.style.backgroundColor = '#fff5f5';
            }
        }
    });

    console.log('✅ Page loaded successfully');
});

// Smooth scrolling to results
window.addEventListener('load', function () {
    const urlParams = new URLSearchParams(window.location.search);
    const resultsSection = document.querySelector('.results-section');
    if (urlParams.toString() && resultsSection) {
        setTimeout(() => {
            resultsSection.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }, 300);
    }
});
</script>

<?php
include '../../navbar/footer.php';
?>