<?php
// =================================================================
// == BLOK PHP LENGKAP UNTUK REALISASI TENDER ====================
// =================================================================

// URL API dasar untuk realisasi tender
$apiBaseUrl = "http://sipbanar-phpnative.id/api/realisasi_tender.php";

// 1. Dapatkan parameter dari URL, termasuk halaman saat ini
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = $_GET['limit'] ?? 100; // Default limit 100 data

// 2. Siapkan parameter query untuk API
// Ambil semua parameter filter dari URL
$queryParams = $_GET;
$queryParams['page'] = $currentPage;
$queryParams['limit'] = $limit;

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
// @ digunakan untuk menekan warning jika API gagal, akan dicek manual di bawah
$response = @file_get_contents($apiUrl);
$data = json_decode($response, true);

$summaryResponse = @file_get_contents($apiSummaryUrl);
$summaryData = json_decode($summaryResponse, true);

// 5. Inisialisasi variabel statistik dengan nilai default
$totalNilaiKontrak = 0;
$totalRealisasi = 0;
$totalPaket = 0;
$formattedTotalKontrak = 'Rp 0';
$formattedTotalRealisasi = 'Rp 0';
$avgPersentaseRealisasi = 0;
$klpdCount = 0;

// 6. Proses data statistik dari API summary
// API Anda HARUS mengembalikan struktur seperti: { "success": true, "summary": { "total_paket": 123, "total_nilai_kontrak": 4560000, ... } }
if ($summaryData && isset($summaryData['success']) && $summaryData['success'] && isset($summaryData['summary'])) {
    $summary = $summaryData['summary'];

    $totalPaket = $summary['total_paket'] ?? 0;
    $totalNilaiKontrak = $summary['total_nilai_kontrak'] ?? 0;
    $totalRealisasi = $summary['total_realisasi'] ?? 0;
    $avgPersentaseRealisasi = $summary['avg_persentase_realisasi'] ?? 0;

    // Format nilai untuk ditampilkan
    $formattedTotalKontrak = 'Rp ' . number_format($totalNilaiKontrak, 0, ',', '.');
    $formattedTotalRealisasi = 'Rp ' . number_format($totalRealisasi, 0, ',', '.');
}

// 7. Siapkan variabel untuk paginasi
$totalPages = $data['pagination']['total_pages'] ?? 1;
$totalRecords = $data['pagination']['total_records'] ?? 0;
// Jika summary memberikan total paket, gunakan itu karena lebih akurat
if ($totalPaket > 0) {
    $totalRecords = $totalPaket;
}

// Set page title untuk header
$page_title = "Data Realisasi Tender - SIP BANAR";

// Include header
include '../../navbar/header.php';

?>

<script src="../../js/submenu.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<style>
    /* Custom CSS untuk halaman realisasi tender - Perbaikan Filter Layout */
    .container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 20px;
    }

    /* Filter Section Styles - Diperbaiki */
    .filter-section {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        margin-bottom: 30px;
        overflow: hidden;
        border: 1px solid #e9ecef;
    }

    .filter-header {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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

    /* Grid Layout untuk Filter - Layout Rapi */
    .filter-row {
        display: grid;
        gap: 25px;
        margin-bottom: 25px;
    }

    /* Baris pertama: Periode Tanggal (2 kolom) + Jenis Pengadaan (1 kolom) */
    .filter-row:nth-child(1) {
        grid-template-columns: 2fr 1fr;
    }

    /* Baris kedua: KLPD + Metode + Status Realisasi (3 kolom sama) */
    .filter-row:nth-child(2) {
        grid-template-columns: 1fr 1fr 1fr;
    }

    /* Baris ketiga: Pencarian Paket + Limit Data */
    .filter-row:nth-child(3) {
        grid-template-columns: 2fr 1fr;
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
        border-color: #28a745;
        box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.15);
        transform: translateY(-1px);
    }

    .filter-group select:hover,
    .filter-group input[type="text"]:hover {
        border-color: #28a745;
    }

    /* Date Range Styles - Diperbaiki */
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
        border-color: #28a745;
        box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.15);
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
        color: #28a745;
        font-weight: 700;
        font-size: 14px;
        padding: 8px 12px;
        background: white;
        border-radius: 20px;
        border: 2px solid #28a745;
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
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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
        background: #f8fff9;
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
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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

    .card-subtitle {
        font-size: 12px;
        color: #6c757d;
    }

    /* Results Section - Diperbaiki */
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

    /* === CSS BARU UNTUK PAGINASI === */
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
    /* === AKHIR CSS BARU === */

    /* Table Styles - Diperbaiki untuk Realisasi */
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
        border-bottom: 3px solid #28a745;
        position: sticky;
        top: 0;
        z-index: 10;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    table th:first-child {
        border-top-left-radius: 0;
    }

    table th:last-child {
        border-top-right-radius: 0;
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

    .table-total-row {
        font-weight: 700;
        background-color: #f8f9fa;
        border-top: 3px solid #28a745;
        font-size: 15px;
    }

    .table-total-row td {
        color: #2c3e50;
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

    .badge-danger {
        background: #e74c3c;
        color: white;
    }

    .badge-info {
        background: #17a2b8;
        color: white;
    }

    /* Progress Bar untuk Persentase Realisasi */
    .progress-container {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .progress-bar-realisasi {
        flex: 1;
        height: 20px;
        background: #e9ecef;
        border-radius: 10px;
        position: relative;
        overflow: hidden;
    }

    .progress-fill-realisasi {
        height: 100%;
        border-radius: 10px;
        transition: width 0.5s ease;
        position: relative;
    }

    .progress-fill-realisasi.low {
        background: linear-gradient(90deg, #e74c3c, #c0392b);
    }

    .progress-fill-realisasi.medium {
        background: linear-gradient(90deg, #f39c12, #e67e22);
    }

    .progress-fill-realisasi.high {
        background: linear-gradient(90deg, #27ae60, #229954);
    }

    .progress-text {
        font-size: 12px;
        font-weight: 600;
        color: #2c3e50;
        min-width: 45px;
        text-align: right;
    }

    /* Price Formatting */
    .price {
        font-weight: 700;
        color: #27ae60;
        white-space: nowrap;
        font-size: 15px;
    }

    .price.kontrak {
        color: #3498db;
    }

    /* Small Text */
    .small-text {
        font-size: 12px;
        color: #6c757d;
        margin-top: 4px;
    }

    .text-muted {
        color: #6c757d;
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

    /* Loading State */
    .loading {
        text-align: center;
        padding: 40px;
    }

    .loading i {
        font-size: 32px;
        color: #28a745;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* Footer Info */
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
        .filter-row:nth-child(1) {
            grid-template-columns: 1fr;
            gap: 20px;
        }
        .filter-row:nth-child(2) {
            grid-template-columns: 1fr 1fr;
        }
        .filter-row:nth-child(3) {
            grid-template-columns: 1fr;
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
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
        }
        .results-header {
            flex-direction: column;
            gap: 15px;
            align-items: flex-start;
        }
        .pagination {
            align-self: center;
        }
        .table-container {
            border-radius: 0;
        }
        table {
            min-width: 1000px;
        }
    }

    @media (max-width: 768px) {
        .container { padding: 15px; }
        .filter-content { padding: 20px 15px; }
        .search-row {
            justify-content: center;
            flex-direction: column;
            gap: 12px;
        }
        .search-btn, .reset-btn { width: 100%; min-width: auto; }
        .table-footer {
            flex-direction: column;
            gap: 10px;
            text-align: center;
        }
        table th, table td { padding: 12px 8px; font-size: 13px; }
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
            <h3>Filter Data Realisasi Tender</h3>
        </div>
        <div class="filter-content">
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="date-range-group">
                        <label><i class="fas fa-calendar-alt"></i> Periode Tanggal</label>
                        <div class="date-range-container">
                            <input type="date" name="tanggal_awal" value="<?= htmlspecialchars($_GET['tanggal_awal'] ?? '') ?>" placeholder="Tanggal Mulai">
                            <span class="date-separator">S/D</span>
                            <input type="date" name="tanggal_akhir" value="<?= htmlspecialchars($_GET['tanggal_akhir'] ?? '') ?>" placeholder="Tanggal Akhir">
                        </div>
                    </div>

                    <div class="filter-group">
                        <label><i class="fas fa-tags"></i> Jenis Pengadaan</label>
                        <select name="jenis_pengadaan">
                            <option value="">Semua Jenis</option>
                            <option value="Jasa Lainnya" <?= ($_GET['jenis_pengadaan'] ?? '') == 'Jasa Lainnya' ? 'selected' : '' ?>>Jasa Lainnya</option>
                            <option value="Jasa Konsultansi" <?= ($_GET['jenis_pengadaan'] ?? '') == 'Jasa Konsultansi' ? 'selected' : '' ?>>Jasa Konsultansi</option>
                            <option value="Barang" <?= ($_GET['jenis_pengadaan'] ?? '') == 'Barang' ? 'selected' : '' ?>>Barang</option>
                            <option value="Konstruksi" <?= ($_GET['jenis_pengadaan'] ?? '') == 'Konstruksi' ? 'selected' : '' ?>>Konstruksi</option>
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
                        <label><i class="fas fa-cogs"></i> Metode</label>
                        <select name="metode">
                            <option value="">Semua Metode</option>
                            <option value="E-Purchasing" <?= ($_GET['metode'] ?? '') == 'E-Purchasing' ? 'selected' : '' ?>>E-Purchasing</option>
                            <option value="Pengadaan Langsung" <?= ($_GET['metode'] ?? '') == 'Pengadaan Langsung' ? 'selected' : '' ?>>Pengadaan Langsung</option>
                            <option value="Tender" <?= ($_GET['metode'] ?? '') == 'Tender' ? 'selected' : '' ?>>Tender</option>
                            <option value="Dikecualikan" <?= ($_GET['metode'] ?? '') == 'Dikecualikan' ? 'selected' : '' ?>>Dikecualikan</option>
                            <option value="Penunjukan Langsung" <?= ($_GET['metode'] ?? '') == 'Penunjukan Langsung' ? 'selected' : '' ?>>Penunjukan Langsung</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-chart-line"></i> Status Realisasi</label>
                        <select name="status_realisasi">
                            <option value="">Semua Status</option>
                            <option value="Rendah" <?= ($_GET['status_realisasi'] ?? '') == 'Rendah' ? 'selected' : '' ?>>Rendah (< 50%)</option>
                            <option value="Sedang" <?= ($_GET['status_realisasi'] ?? '') == 'Sedang' ? 'selected' : '' ?>>Sedang (50-80%)</option>
                            <option value="Tinggi" <?= ($_GET['status_realisasi'] ?? '') == 'Tinggi' ? 'selected' : '' ?>>Tinggi (> 80%)</option>
                        </select>
                    </div>
                </div>

                <div class="filter-row">
                    <div class="filter-group">
                        <label><i class="fas fa-search"></i> Pencarian Paket</label>
                        <div class="search-input-wrapper">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" placeholder="Cari nama paket..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="filter-group">
                        <label><i class="fas fa-list"></i> Limit Data</label>
                        <select name="limit">
                            <option value="10" <?= ($limit == '10') ? 'selected' : '' ?>>10 Data</option>
                            <option value="25" <?= ($limit == '25') ? 'selected' : '' ?>>25 Data</option>
                            <option value="50" <?= ($limit == '50') ? 'selected' : '' ?>>50 Data</option>
                            <option value="100" <?= ($limit == '100') ? 'selected' : '' ?>>100 Data</option>
                        </select>
                    </div>
                </div>

                <div class="search-row">
                    <button type="button" class="reset-btn" onclick="window.location.href=window.location.pathname">
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
            <i class="fas fa-chart-bar"></i>
            <h3>Ringkasan Data Realisasi Tender</h3>
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
                        <div class="card-subtitle">Tender</div>
                    </div>
                </div>

                <div class="summary-card success">
                    <div class="card-icon">
                        <i class="fas fa-file-contract"></i>
                    </div>
                    <div class="card-content">
                        <div class="card-value"><?= $formattedTotalKontrak ?></div>
                        <div class="card-label">Total Nilai Kontrak</div>
                        <div class="card-subtitle">Keseluruhan</div>
                    </div>
                </div>

                <div class="summary-card info">
                    <div class="card-icon">
                        <i class="fas fa-money-check"></i>
                    </div>
                    <div class="card-content">
                        <div class="card-value"><?= $formattedTotalRealisasi ?></div>
                        <div class="card-label">Total Realisasi</div>
                        <div class="card-subtitle">Terbayar</div>
                    </div>
                </div>

                <div class="summary-card warning">
                    <div class="card-icon">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div class="card-content">
                        <div class="card-value"><?= number_format($avgPersentaseRealisasi, 1) ?>%</div>
                        <div class="card-label">Rata-rata Realisasi</div>
                        <div class="card-subtitle">Persentase</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="results-section">
        <div class="results-header">
            <div>
                <div class="results-title">
                    <i class="fas fa-table"></i> Hasil Pencarian Data Realisasi Tender
                </div>
                <?php if ($data && isset($data['success']) && $data['success']) : ?>
                    <div class="results-subtitle">
                        <strong>Menampilkan <?= count($data['data']) ?> dari <?= number_format($totalRecords, 0, ',', '.') ?> total data</strong>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="pagination">
                <?php
                // Siapkan base URL untuk link paginasi, dengan semua filter kecuali 'page'
                $paginationParams = $_GET;
                unset($paginationParams['page']);
                $paginationQuery = http_build_query($paginationParams);
                ?>

                <a href="?<?= $paginationQuery ?>&page=<?= max(1, $currentPage - 1) ?>" class="btn-pagination <?= $currentPage <= 1 ? 'disabled' : '' ?>" title="Halaman Sebelumnya">
                    <i class="fas fa-chevron-left"></i>
                </a>

                <?php
                // Tampilkan link halaman
                for ($i = 1; $i <= $totalPages; $i++) {
                    // Tampilkan hanya beberapa halaman di sekitar halaman aktif jika terlalu banyak
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
                            <th style="width: 250px;"><i class="fas fa-box"></i> Paket Tender</th>
                            <th style="width: 130px;"><i class="fas fa-file-contract"></i> Nilai Kontrak (Rp)</th>
                            <th style="width: 120px;"><i class="fas fa-tags"></i> Jenis Pengadaan</th>
                            <th style="width: 100px;"><i class="fas fa-cogs"></i> Metode</th>
                            <th style="width: 120px;"><i class="fas fa-building"></i> KLPD</th>
                            <th style="width: 180px;"><i class="fas fa-sitemap"></i> Satuan Kerja</th>
                            <th style="width: 120px;"><i class="fas fa-map-marker-alt"></i> Lokasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['data'] as $row) : ?>
                            <?php
                            // Hitung persentase realisasi
                            $nilaiKontrak = (int) preg_replace('/[^\d]/', '', $row['Nilai_Kontrak'] ?? '0');
                            $nilaiRealisasi = (int) preg_replace('/[^\d]/', '', $row['Nilai_Realisasi'] ?? '0');
                            $persentaseRealisasi = $nilaiKontrak > 0 ? ($nilaiRealisasi / $nilaiKontrak * 100) : 0;
                            
                            // Tentukan class untuk progress bar
                            $progressClass = 'low';
                            if ($persentaseRealisasi >= 80) {
                                $progressClass = 'high';
                            } elseif ($persentaseRealisasi >= 50) {
                                $progressClass = 'medium';
                            }
                            ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 700; color: #2c3e50; margin-bottom: 5px;">
                                        <?= htmlspecialchars($row['Nama_Paket'] ?? $row['Paket'] ?? '') ?>
                                    </div>
                                    <div class="small-text">
                                        <i class="fas fa-id-card"></i> ID: <?= htmlspecialchars($row['ID'] ?? $row['Kode_Tender'] ?? '') ?>
                                    </div>
                                </td>
                                <td class="price kontrak">
                                    <?= 'Rp ' . number_format($nilaiKontrak, 0, ',', '.') ?>
                                </td>
                                <td class="price">
                                    <?= 'Rp ' . number_format($nilaiRealisasi, 0, ',', '.') ?>
                                </td>
                                <td>
                                    <div class="progress-container">
                                        <div class="progress-bar-realisasi">
                                            <div class="progress-fill-realisasi <?= $progressClass ?>" style="width: <?= min(100, $persentaseRealisasi) ?>%"></div>
                                        </div>
                                        <div class="progress-text"><?= number_format($persentaseRealisasi, 1) ?>%</div>
                                    </div>
                                </td>
                                <td><span class="badge badge-primary"><?= htmlspecialchars($row['Jenis_Pengadaan'] ?? '') ?></span></td>
                                <td><small><?= htmlspecialchars($row['Metode_Pengadaan'] ?? $row['Metode'] ?? '') ?></small></td>
                                <td><small><?= htmlspecialchars($row['KLPD'] ?? '') ?></small></td>
                                <td><small><?= htmlspecialchars($row['Satuan_Kerja'] ?? '') ?></small></td>
                                <td><small><?= htmlspecialchars($row['Lokasi'] ?? '') ?></small></td>
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
                    <strong>Total Data: <?= number_format($totalRecords, 0, ',', '.') ?></strong> tender
                </div>
            </div>

        <?php else : ?>
            <div class="empty-state">
                <i class="fas fa-search-minus"></i>
                <p><strong>Tidak ada data realisasi tender yang ditemukan</strong></p>
                <small class="text-muted">Coba ubah kriteria pencarian atau filter yang Anda gunakan</small>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // =================================================================
    // == SCRIPT UNTUK MEMPERPENDEK URL ================================
    // =================================================================
    document.addEventListener('DOMContentLoaded', function() {
        const filterForm = document.querySelector('form');

        if (filterForm) {
            filterForm.addEventListener('submit', function(e) {
                // Dapatkan semua elemen input dan select di dalam form
                const inputs = this.querySelectorAll('input, select');
                
                inputs.forEach(input => {
                    // Jika nilai input kosong, nonaktifkan (disable) elemen tersebut
                    // Elemen yang nonaktif tidak akan disertakan dalam URL
                    if (!input.value) {
                        input.disabled = true;
                    }
                });

                // Lanjutkan proses submit form
                return true;
            });
        }
    });

    function loadSummaryData() {
        const form = document.getElementById('filterForm');
        const formData = new FormData(form);
        const params = new URLSearchParams(formData);

        // Add action parameter for summary endpoint
        params.append('action', 'summary');

        // Show loading state
        document.getElementById('summarySection').style.display = 'block';
        document.getElementById('summaryLoading').style.display = 'flex';
        document.getElementById('summaryData').style.display = 'none';

        // Make AJAX request
        fetch('http://sipbanar-phpnative.id/api/realisasi_tender.php?' + params.toString())
            .then(response => response.json())
            .then(data => {
                if (data.success && data.summary) {
                    displaySummaryData(data.summary);
                } else {
                    console.error('Failed to load summary data');
                    document.getElementById('summarySection').style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Error loading summary:', error);
                document.getElementById('summarySection').style.display = 'none';
            });
    }

    // Function to display summary data
    function displaySummaryData(summary) {
        const summaryDataDiv = document.getElementById('summaryData');

        // Format numbers
        const formatNumber = (num) => new Intl.NumberFormat('id-ID').format(num);
        const formatRupiah = (num) => 'Rp ' + new Intl.NumberFormat('id-ID').format(num);

        // Create summary cards HTML
        let summaryHTML = `
        <div class="summary-cards">
            <div class="summary-card primary">
                <div class="card-icon">
                    <i class="fas fa-boxes"></i>
                </div>
                <div class="card-content">
                    <div class="card-value">${formatNumber(summary.total_paket)}</div>
                    <div class="card-label">Total Paket</div>
                    <div class="card-subtitle">Tender</div>
                </div>
            </div>
            
            <div class="summary-card success">
                <div class="card-icon">
                    <i class="fas fa-file-contract"></i>
                </div>
                <div class="card-content">
                    <div class="card-value">${formatRupiah(summary.total_nilai_kontrak)}</div>
                    <div class="card-label">Total Nilai Kontrak</div>
                    <div class="card-subtitle">Keseluruhan</div>
                </div>
            </div>
            
            <div class="summary-card info">
                <div class="card-icon">
                    <i class="fas fa-money-check"></i>
                </div>
                <div class="card-content">
                    <div class="card-value">${formatRupiah(summary.total_realisasi)}</div>
                    <div class="card-label">Total Realisasi</div>
                    <div class="card-subtitle">Terbayar</div>
                </div>
            </div>
            
            <div class="summary-card warning">
                <div class="card-icon">
                    <i class="fas fa-percentage"></i>
                </div>
                <div class="card-content">
                    <div class="card-value">${summary.avg_persentase_realisasi.toFixed(1)}%</div>
                    <div class="card-label">Rata-rata Realisasi</div>
                    <div class="card-subtitle">Persentase</div>
                </div>
            </div>
        </div>

        <div class="stats-tables">`;

        // Jenis Pengadaan table
        if (summary.breakdown.jenis_pengadaan && Object.keys(summary.breakdown.jenis_pengadaan).length > 0) {
            summaryHTML += `
            <div class="stats-table">
                <h4><i class="fas fa-tags"></i> Berdasarkan Jenis Pengadaan</h4>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Jenis Pengadaan</th>
                                <th>Jumlah Paket</th>
                                <th>Total Kontrak</th>
                                <th>Total Realisasi</th>
                                <th>% Realisasi</th>
                            </tr>
                        </thead>
                        <tbody>`;

            for (const [jenis, stats] of Object.entries(summary.breakdown.jenis_pengadaan)) {
                const persentaseRealisasi = stats.total_kontrak > 0 ? (stats.total_realisasi / stats.total_kontrak * 100).toFixed(1) : 0;
                summaryHTML += `
                <tr>
                    <td><span class="badge badge-primary">${jenis}</span></td>
                    <td><strong>${formatNumber(stats.count)} paket</strong></td>
                    <td class="price kontrak">${formatRupiah(stats.total_kontrak)}</td>
                    <td class="price">${formatRupiah(stats.total_realisasi)}</td>
                    <td>
                        <div class="progress-container">
                            <div class="progress-bar-realisasi">
                                <div class="progress-fill-realisasi ${persentaseRealisasi >= 80 ? 'high' : persentaseRealisasi >= 50 ? 'medium' : 'low'}" style="width: ${Math.min(100, persentaseRealisasi)}%"></div>
                            </div>
                            <div class="progress-text">${persentaseRealisasi}%</div>
                        </div>
                    </td>
                </tr>`;
            }

            summaryHTML += `
                        </tbody>
                    </table>
                </div>
            </div>`;
        }

        // KLPD table
        if (summary.breakdown.klpd && Object.keys(summary.breakdown.klpd).length > 0) {
            summaryHTML += `
            <div class="stats-table">
                <h4><i class="fas fa-building"></i> Berdasarkan KLPD</h4>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>KLPD</th>
                                <th>Jumlah Paket</th>
                                <th>Total Kontrak</th>
                                <th>Total Realisasi</th>
                                <th>% Realisasi</th>
                            </tr>
                        </thead>
                        <tbody>`;

            for (const [klpd, stats] of Object.entries(summary.breakdown.klpd)) {
                const persentaseRealisasi = stats.total_kontrak > 0 ? (stats.total_realisasi / stats.total_kontrak * 100).toFixed(1) : 0;
                summaryHTML += `
                <tr>
                    <td><strong>${klpd}</strong></td>
                    <td>${formatNumber(stats.count)} paket</td>
                    <td class="price kontrak">${formatRupiah(stats.total_kontrak)}</td>
                    <td class="price">${formatRupiah(stats.total_realisasi)}</td>
                    <td>
                        <div class="progress-container">
                            <div class="progress-bar-realisasi">
                                <div class="progress-fill-realisasi ${persentaseRealisasi >= 80 ? 'high' : persentaseRealisasi >= 50 ? 'medium' : 'low'}" style="width: ${Math.min(100, persentaseRealisasi)}%"></div>
                            </div>
                            <div class="progress-text">${persentaseRealisasi}%</div>
                        </div>
                    </td>
                </tr>`;
            }

            summaryHTML += `
                        </tbody>
                    </table>
                </div>
            </div>`;
        }

        summaryHTML += `</div>`;

        // Hide loading and show data
        document.getElementById('summaryLoading').style.display = 'none';
        summaryDataDiv.innerHTML = summaryHTML;
        summaryDataDiv.style.display = 'block';
    }

    // JavaScript untuk interaktivitas - Diperbaiki
    document.addEventListener('DOMContentLoaded', function() {
        // Date range validation
        const tanggalAwal = document.querySelector('input[name="tanggal_awal"]');
        const tanggalAkhir = document.querySelector('input[name="tanggal_akhir"]');

        if (tanggalAwal && tanggalAkhir) {
            tanggalAwal.addEventListener('change', function() {
                tanggalAkhir.min = this.value;
                if (tanggalAkhir.value && tanggalAkhir.value < this.value) {
                    tanggalAkhir.value = this.value;
                }
            });

            tanggalAkhir.addEventListener('change', function() {
                tanggalAwal.max = this.value;
                if (tanggalAwal.value && tanggalAwal.value > this.value) {
                    tanggalAwal.value = this.value;
                }
            });
        }

        // Set today's date as max for date inputs
        const today = new Date().toISOString().split('T')[0];
        if (tanggalAwal) tanggalAwal.max = today;
        if (tanggalAkhir) tanggalAkhir.max = today;

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
            row.addEventListener('click', function() {
                // Optional: handle row click for details view
                console.log('Row clicked:', this);
            });

            // Add subtle hover animation
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
    });

    // Reset form function
    function resetForm() {
        // Arahkan ke halaman yang sama tanpa parameter query
        window.location.href = window.location.pathname;
    }

    // Form validation before submit
    document.querySelector('form').addEventListener('submit', function(e) {
        const tanggalAwal = document.querySelector('input[name="tanggal_awal"]').value;
        const tanggalAkhir = document.querySelector('input[name="tanggal_akhir"]').value;

        // Show loading state
        const submitBtn = this.querySelector('.search-btn');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mencari...';
        submitBtn.disabled = true;

        // Validate date range
        if (tanggalAwal && tanggalAkhir && tanggalAwal > tanggalAkhir) {
            e.preventDefault();
            alert('Tanggal awal tidak boleh lebih besar dari tanggal akhir!');
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            return false;
        }

        // Reset button state after a delay if form doesn't redirect
        setTimeout(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }, 5000);
    });

    // Add smooth scrolling to results when form is submitted
    window.addEventListener('load', function() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.toString()) {
            // If there are URL parameters, scroll to results
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
                    // Show temporary feedback
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