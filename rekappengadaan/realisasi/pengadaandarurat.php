<?php
$apiBaseUrl = "http://sipbanarnative.id/api/realisasi_pengadaandarurat.php";

// Ambil data options untuk dropdown
$optionsApiUrl = $apiBaseUrl . '?action=options';
$optionsResponse = @file_get_contents($optionsApiUrl);
$optionsData = json_decode($optionsResponse, true);
$options = $optionsData['options'] ?? [
    'satker' => [],
    'jenis_pengadaan' => [],
    'metode_pengadaan' => []
];

$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = $_GET['limit'] ?? 50;

$currentYear = date('Y');
$selectedBulan = $_GET['bulan'] ?? '07'; // Default Juli
$selectedTahun = $_GET['tahun'] ?? $currentYear;

$queryParams = $_GET;
$queryParams['page'] = $currentPage;
$queryParams['limit'] = $limit;
$queryParams['bulan'] = $selectedBulan;
$queryParams['tahun'] = $selectedTahun;

$queryParams = array_filter($queryParams, function ($value) {
    return $value !== '' && $value !== null;
});
$queryString = http_build_query($queryParams);
$apiUrl = $apiBaseUrl . '?' . $queryString;

$summaryParams = $queryParams;
unset($summaryParams['page']);
unset($summaryParams['limit']);
$summaryParams['action'] = 'summary';

$summaryQueryString = http_build_query($summaryParams);
$apiSummaryUrl = $apiBaseUrl . '?' . $summaryQueryString;

$response = @file_get_contents($apiUrl);
$data = json_decode($response, true);

$summaryResponse = @file_get_contents($apiSummaryUrl);
$summaryData = json_decode($summaryResponse, true);

// Inisialisasi variabel dengan nilai default
$totalPaket = 0;
$totalPagu = 0;
$totalRealisasi = 0;
$efisiensiAnggaran = 0;

// Ambil data dari API summary
if ($summaryData && isset($summaryData['success']) && $summaryData['success'] && isset($summaryData['summary'])) {
    $summary = $summaryData['summary'];
    $totalPaket = $summary['total_paket'] ?? 0;
    $totalPagu = $summary['total_pagu'] ?? 0;
    $totalRealisasi = $summary['total_realisasi'] ?? 0;
    $efisiensiAnggaran = $summary['efisiensi_anggaran'] ?? 0;
}

$totalPages = $data['pagination']['total_pages'] ?? 1;
$totalRecords = $data['pagination']['total_records'] ?? 0;
if ($totalPaket > 0) {
    $totalRecords = $totalPaket;
}

$page_title = "Realisasi Pengadaan Darurat - SIP BANAR";

$namaBulan = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
    '04' => 'April', '05' => 'Mei', '06' => 'Juni',
    '07' => 'Juli', '08' => 'Agustus', '09' => 'September',
    '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];

include '../../navbar/header.php';
?>

<script src="../../js/submenu.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

<style>
    body {
        background: #f5f7fa;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .container {
        max-width: 1480px;
        margin: 0 auto;
        padding: 30px 20px;
    }

    /* Filter Section */
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
        grid-template-columns: 1fr 1fr 1fr;
    }

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

    .filter-group label i {
        margin-right: 6px;
        color: #6c757d;
    }

    .badge-default {
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
        color: #2c3e50;
        transition: all 0.3s ease;
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

    .btn-reset {
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

    .btn-reset:hover {
        border-color: #dc3545;
        color: #dc3545;
        background: #fff5f5;
    }

    .btn-search {
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

    .btn-search:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(220, 53, 69, 0.3);
    }

    /* Summary Section - 4 Cards */
    .summary-section {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        margin-bottom: 30px;
        overflow: hidden;
        border: 1px solid #e9ecef;
        animation: fadeInUp 0.6s ease-out;
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
        bottom: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, 
            #5B93D6 0%, #5B93D6 25%, 
            #F5A623 25%, #F5A623 50%, 
            #4CAF50 50%, #4CAF50 75%, 
            #00BCD4 75%, #00BCD4 100%
        );
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
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .summary-content {
        padding: 0;
        background: #fafbfc;
    }

    /* Summary Cards Horizontal Layout - 4 Cards */
    .summary-cards-horizontal {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 0;
        padding: 0;
    }

    .summary-card-horizontal {
        display: flex;
        align-items: center;
        gap: 20px;
        padding: 35px 25px;
        background: white;
        position: relative;
        transition: all 0.3s ease;
        border-right: 1px solid #e9ecef;
    }

    .summary-card-horizontal:last-child {
        border-right: none;
    }

    .summary-card-horizontal:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        z-index: 10;
        background: #f8f9fa;
    }

    /* Card Icon Horizontal */
    .card-icon-horizontal {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
        color: white;
        flex-shrink: 0;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        transition: all 0.3s ease;
    }

    .summary-card-horizontal:hover .card-icon-horizontal {
        transform: scale(1.1) rotate(5deg);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
    }

    .summary-card-horizontal.blue .card-icon-horizontal {
        background: linear-gradient(135deg, #5B93D6 0%, #4A7FC1 100%);
    }

    .summary-card-horizontal.orange .card-icon-horizontal {
        background: linear-gradient(135deg, #F5A623 0%, #E09200 100%);
    }

    .summary-card-horizontal.green .card-icon-horizontal {
        background: linear-gradient(135deg, #4CAF50 0%, #45A049 100%);
    }

    .summary-card-horizontal.cyan .card-icon-horizontal {
        background: linear-gradient(135deg, #00BCD4 0%, #00ACC1 100%);
    }

    /* Card Content Horizontal */
    .card-content-horizontal {
        flex: 1;
        min-width: 0;
    }

    .card-content-horizontal h4 {
        font-size: 26px;
        font-weight: 800;
        color: #2c3e50;
        margin: 0 0 8px 0;
        line-height: 1.2;
        letter-spacing: -0.5px;
        word-break: break-word;
    }

    .card-content-horizontal p {
        font-size: 11px;
        font-weight: 700;
        color: #90A4AE;
        margin: 0;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        line-height: 1.3;
    }

    /* Results Section - Simple Style */
    .results-section {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        margin-bottom: 30px;
    }

    .results-header {
        background: white;
        padding: 20px 25px;
        border-bottom: 2px solid #f1f3f5;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .results-title {
        font-size: 16px;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 5px;
    }

    .results-title i {
        color: #6c757d;
        margin-right: 8px;
    }

    .results-subtitle {
        font-size: 13px;
        color: #6c757d;
    }

    .pagination {
        display: flex;
        gap: 5px;
        align-items: center;
    }

    .btn-pagination {
        background: white;
        color: #495057;
        border: 1px solid #dee2e6;
        padding: 6px 12px;
        border-radius: 4px;
        font-weight: 500;
        font-size: 13px;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 35px;
        height: 35px;
    }

    .btn-pagination:hover:not(.disabled) {
        background: #f8f9fa;
        border-color: #adb5bd;
    }

    .btn-pagination.active {
        background: #007bff;
        color: white;
        border-color: #007bff;
    }

    .btn-pagination.disabled {
        opacity: 0.4;
        cursor: not-allowed;
        pointer-events: none;
    }

    .table-container {
        overflow-x: auto;
        position: relative;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        font-size: 13px;
    }

    table thead {
        background: #3d5170;
        color: white;
    }

    table th {
        padding: 14px 16px;
        text-align: left;
        font-weight: 600;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        white-space: nowrap;
        border-right: 1px solid rgba(255, 255, 255, 0.1);
    }

    table th:last-child {
        border-right: none;
    }

    table th i {
        margin-right: 5px;
        font-size: 11px;
    }

    table tbody tr {
        border-bottom: 1px solid #f1f3f5;
        transition: background 0.2s ease;
    }

    table tbody tr:hover {
        background: #f8f9fa;
    }

    table tbody tr:last-child {
        border-bottom: none;
    }

    table td {
        padding: 14px 16px;
        color: #495057;
        vertical-align: middle;
    }

    table td a {
        color: #007bff;
        text-decoration: none;
        font-weight: 600;
    }

    table td a:hover {
        text-decoration: underline;
    }

    .price {
        font-weight: 600;
        white-space: nowrap;
    }

    .badge {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .badge-primary {
        background: #4A90E2;
        color: white;
    }

    .badge-info {
        background: #17a2b8;
        color: white;
    }

    .table-footer {
        padding: 16px 25px;
        background: #f8f9fa;
        border-top: 1px solid #dee2e6;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 13px;
        color: #6c757d;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #6c757d;
    }

    .empty-state i {
        font-size: 48px;
        color: #dee2e6;
        margin-bottom: 15px;
    }

    .empty-state p {
        font-size: 16px;
        margin: 10px 0;
        color: #495057;
    }

    .empty-state small {
        font-size: 13px;
        color: #6c757d;
    }

    /* Responsive Design */
    @media (max-width: 1400px) {
        .summary-cards-horizontal {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .summary-card-horizontal:nth-child(2) {
            border-right: none;
        }
        
        .summary-card-horizontal:nth-child(3),
        .summary-card-horizontal:nth-child(4) {
            border-top: 1px solid #e9ecef;
        }
    }

    @media (max-width: 768px) {
        .summary-cards-horizontal {
            grid-template-columns: 1fr;
        }
        
        .summary-card-horizontal {
            border-right: none !important;
            border-bottom: 1px solid #e9ecef;
        }
        
        .summary-card-horizontal:last-child {
            border-bottom: none;
        }
        
        .card-content-horizontal h4 {
            font-size: 20px;
        }
        
        .card-icon-horizontal {
            width: 60px;
            height: 60px;
            font-size: 28px;
        }
        
        .summary-header {
            flex-direction: column;
            gap: 15px;
            align-items: flex-start;
        }
        
        .period-badge {
            align-self: flex-start;
        }

        .results-header {
            flex-direction: column;
            gap: 15px;
            align-items: flex-start;
        }
    }

    /* Animation */
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

    /* Loading State */
    .summary-card-horizontal.loading {
        pointer-events: none;
        opacity: 0.6;
    }

    @keyframes loading {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }

    @media print {
        .filter-section,
        .summary-section,
        .pagination,
        .btn-reset,
        .btn-search,
        button {
            display: none !important;
        }
        
        .results-section {
            box-shadow: none !important;
            border: 1px solid #ddd !important;
        }
        
        table {
            font-size: 10px !important;
        }
        
        table th,
        table td {
            padding: 8px 6px !important;
        }
    }
</style>

<div class="container">
    <!-- Filter Section -->
    <div class="filter-section">
        <div class="filter-header">
            <i class="fas fa-filter"></i>
            <h3>Filter Data Realisasi Pengadaan Darurat</h3>
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
                            <?php if (!empty($options['jenis_pengadaan'])): ?>
                                <?php foreach ($options['jenis_pengadaan'] as $jp): ?>
                                    <option value="<?= htmlspecialchars($jp) ?>" <?= ($_GET['jenis_pengadaan'] ?? '') == $jp ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($jp) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>

                <div class="filter-row">
                    <div class="filter-group">
                        <label><i class="fas fa-building"></i> Satuan Kerja</label>
                        <select name="satker">
                            <option value="">Semua Satker</option>
                            <?php if (!empty($options['satker'])): ?>
                                <?php foreach ($options['satker'] as $s): ?>
                                    <option value="<?= htmlspecialchars($s) ?>" <?= ($_GET['satker'] ?? '') == $s ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($s) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label><i class="fas fa-cogs"></i> Metode Pengadaan</label>
                        <select name="metode_pengadaan">
                            <option value="">Semua Metode</option>
                            <?php if (!empty($options['metode_pengadaan'])): ?>
                                <?php foreach ($options['metode_pengadaan'] as $mp): ?>
                                    <option value="<?= htmlspecialchars($mp) ?>" <?= ($_GET['metode_pengadaan'] ?? '') == $mp ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($mp) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label><i class="fas fa-search"></i> Pencarian Paket</label>
                        <div class="search-input-wrapper">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" placeholder="Cari nama paket, pemenang, atau kode..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <div class="search-row">
                    <button type="button" class="btn-reset" onclick="resetForm()">
                        <i class="fas fa-undo"></i>
                        Reset Filter
                    </button>
                    <button type="submit" class="btn-search">
                        <i class="fas fa-search"></i>
                        Cari Data
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Section - 4 Cards -->
    <div class="summary-section">
        <div class="summary-header">
            <div class="summary-header-left">
                <i class="fas fa-chart-bar"></i>
                <h3>Ringkasan Data Realisasi Pengadaan Darurat</h3>
            </div>
            <div class="period-badge">
                <i class="fas fa-calendar-check"></i> 
                <?= $namaBulan[$selectedBulan] ?> <?= $selectedTahun ?>
            </div>
        </div>
        <div class="summary-content">
            <div class="summary-cards-horizontal">
                <!-- Card 1: Total Paket -->
                <div class="summary-card-horizontal blue">
                    <div class="card-icon-horizontal">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div class="card-content-horizontal">
                        <h4><?= number_format($totalPaket, 0, ',', '.') ?></h4>
                        <p>TOTAL PAKET</p>
                    </div>
                </div>

                <!-- Card 2: Total Pagu -->
                <div class="summary-card-horizontal orange">
                    <div class="card-icon-horizontal">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="card-content-horizontal">
                        <h4>Rp <?= number_format($totalPagu, 0, ',', '.') ?></h4>
                        <p>TOTAL PAGU</p>
                    </div>
                </div>

                <!-- Card 3: Total Nilai Kontrak (Realisasi) -->
                <div class="summary-card-horizontal green">
                    <div class="card-icon-horizontal">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <div class="card-content-horizontal">
                        <h4>Rp <?= number_format($totalRealisasi, 0, ',', '.') ?></h4>
                        <p>TOTAL NILAI KONTRAK</p>
                    </div>
                </div>

                <!-- Card 4: Efisiensi Anggaran -->
                <div class="summary-card-horizontal cyan">
                    <div class="card-icon-horizontal">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div class="card-content-horizontal">
                        <h4><?= number_format($efisiensiAnggaran, 2, ',', '.') ?>%</h4>
                        <p>EFISIENSI ANGGARAN</p>
                    </div>
                </div>
            </div>
        </div>  
    </div>

    <!-- Results Section -->
    <div class="results-section">
        <div class="results-header">
            <div>
                <div class="results-title">
                    <i class="fas fa-table"></i> Hasil Data Realisasi Pengadaan Darurat
                </div>
                <?php if ($data && isset($data['success']) && $data['success']): ?>
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

        <?php if ($data && isset($data['success']) && $data['success'] && count($data['data']) > 0): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 120px;"><i class="fas fa-barcode"></i> Kode Paket</th>
                            <th style="width: 280px;"><i class="fas fa-box"></i> Nama Paket</th>
                            <th style="width: 130px;"><i class="fas fa-money-bill-wave"></i> Nilai Pagu</th>
                            <th style="width: 130px;"><i class="fas fa-chart-line"></i> Realisasi</th>
                            <th style="width: 120px;"><i class="fas fa-industry"></i> PDN</th>
                            <th style="width: 120px;"><i class="fas fa-handshake"></i> UMK</th>
                            <th style="width: 200px;"><i class="fas fa-trophy"></i> Pemenang</th>
                            <th style="width: 140px;"><i class="fas fa-tags"></i> Jenis</th>
                            <th style="width: 140px;"><i class="fas fa-cogs"></i> Metode</th>
                            <th style="width: 180px;"><i class="fas fa-building"></i> Satuan Kerja</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['data'] as $row): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 700; color: #4a90e2; font-size: 12px;">
                                        <?= htmlspecialchars($row['Kode_Paket'] ?? '-') ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 700; color: #2c3e50; margin-bottom: 5px;">
                                        <?= htmlspecialchars($row['Nama_Paket'] ?? '-') ?>
                                    </div>
                                    <div style="font-size: 11px; color: #6c757d;">
                                        <i class="fas fa-id-card"></i> RUP: <?= htmlspecialchars($row['Kode_RUP'] ?? '-') ?>
                                    </div>
                                </td>
                                <td class="price" style="color: #6c757d;">
                                    Rp <?= number_format((float)($row['Nilai_Pagu'] ?? 0), 0, ',', '.') ?>
                                </td>
                                <td class="price" style="color: #27ae60;">
                                    Rp <?= number_format((float)($row['Nilai_Total_Realisasi'] ?? 0), 0, ',', '.') ?>
                                </td>
                                <td class="price" style="color: #17a2b8;">
                                    Rp <?= number_format((float)($row['Nilai_PDN'] ?? 0), 0, ',', '.') ?>
                                </td>
                                <td class="price" style="color: #9b59b6;">
                                    Rp <?= number_format((float)($row['Nilai_UMK'] ?? 0), 0, ',', '.') ?>
                                </td>
                                <td style="font-weight: 600; color: #495057;">
                                    <?= htmlspecialchars($row['Nama_Pemenang'] ?? '-') ?>
                                </td>
                                <td>
                                    <span class="badge badge-primary">
                                        <?= htmlspecialchars($row['Jenis_Pengadaan'] ?? '-') ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-info">
                                        <?= htmlspecialchars($row['Metode_pengadaan'] ?? '-') ?>
                                    </span>
                                </td>
                                <td style="color: #495057; font-size: 13px;">
                                    <?= htmlspecialchars($row['Nama_Satker'] ?? '-') ?>
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
                    <strong>Total Data: <?= number_format($totalRecords, 0, ',', '.') ?></strong> paket
                </div>
            </div>

        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-search-minus"></i>
                <p><strong>Tidak ada data realisasi pengadaan darurat yang ditemukan</strong></p>
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
                if (input.name !== 'bulan' && input.name !== 'tahun' && !input.value) {
                    input.disabled = true;
                }
            });

            return true;
        });
    }

    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                this.form.submit();
            }
        });

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

    const tableRows = document.querySelectorAll('tbody tr');
    tableRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
        });

        row.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
});

function resetForm() {
    window.location.href = window.location.pathname + '?bulan=07&tahun=<?= $currentYear ?>';
}

document.querySelector('form').addEventListener('submit', function(e) {
    const submitBtn = this.querySelector('.btn-search');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mencari...';
    submitBtn.disabled = true;

    setTimeout(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }, 5000);
});

window.addEventListener('load', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.toString() && urlParams.get('page') === null) {
        setTimeout(() => {
            const resultsSection = document.querySelector('.results-section');
            if (resultsSection) {
                resultsSection.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }, 100);
    }
});

document.querySelectorAll('tbody tr').forEach(row => {
    const kodeCell = row.querySelector('td:first-child div');
    if (kodeCell) {
        kodeCell.style.cursor = 'pointer';
        kodeCell.title = 'Klik untuk copy kode paket';
        kodeCell.addEventListener('click', function(e) {
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
            }).catch(err => {
                console.error('Failed to copy:', err);
            });
        });
    }
});

const searchTerm = new URLSearchParams(window.location.search).get('search');
if (searchTerm && searchTerm.trim() !== '') {
    const safeSearch = searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); // escape karakter regex
    const regex = new RegExp(`(${safeSearch})`, 'gi');
    document.querySelectorAll('tbody td').forEach(cell => {
        if (cell.textContent.toLowerCase().includes(searchTerm.toLowerCase())) {
            cell.innerHTML = cell.innerHTML.replace(
                regex,
                '<mark style="background: #fff3cd; padding: 2px 4px; border-radius: 3px;">$1</mark>'
            );
        }
    });
}


document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        document.querySelector('input[name="search"]').focus();
    }
    
    if (e.key === 'Escape') {
        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput === document.activeElement && searchInput.value) {
            searchInput.value = '';
            searchInput.blur();
        }
    }
});

function printTable() {
    window.print();
}

function exportToExcel() {
    const params = new URLSearchParams(window.location.search);
    params.set('action', 'export');
    params.set('format', 'csv');
    window.location.href = '<?= $apiBaseUrl ?>?' + params.toString();
}
</script>

<style>
    .btn-pagination-dots {
        width: 40px;
        height: 40px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #6c757d;
    }

    mark {
        animation: highlight 1s ease-in-out;
    }

    @keyframes highlight {
        0% { background: #fff3cd; }
        50% { background: #ffc107; }
        100% { background: #fff3cd; }
    }
</style>

<?php include '../../navbar/footer.php'; ?>