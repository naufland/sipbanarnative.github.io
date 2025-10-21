<?php
$apiBaseUrl = "http://sipbanar-phpnative.id/api/swakelola.php";

$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = $_GET['limit'] ?? 25;

$currentYear = date('Y');
$selectedBulan = $_GET['bulan'] ?? '07';
$selectedTahun = $_GET['tahun'] ?? $currentYear;
$selectedPerubahan = $_GET['perubahan'] ?? 'Tidak';

$queryParams = $_GET;
$queryParams['page'] = $currentPage;
$queryParams['limit'] = $limit;
$queryParams['bulan'] = $selectedBulan;
$queryParams['tahun'] = $selectedTahun;

if (!empty($selectedPerubahan)) {
    $queryParams['perubahan'] = $selectedPerubahan;
}

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

$totalPagu = 0;
$totalPaket = 0;
$formattedTotalPagu = 'Rp 0';

if ($summaryData && isset($summaryData['success']) && $summaryData['success'] && isset($summaryData['summary'])) {
    $summary = $summaryData['summary'];
    $totalPaket = $summary['total_paket'] ?? 0;
    $totalPagu = $summary['total_pagu'] ?? 0;
    $formattedTotalPagu = 'Rp ' . number_format($totalPagu, 0, ',', '.');
}

$totalPages = $data['pagination']['total_pages'] ?? 1;
$totalRecords = $data['pagination']['total_records'] ?? 0;
if ($totalPaket > 0) {
    $totalRecords = $totalPaket;
}

$page_title = "Data Swakelola - SIP BANAR";

$namaBulan = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
    '04' => 'April', '05' => 'Mei', '06' => 'Juni',
    '07' => 'Juli', '08' => 'Agustus', '09' => 'September',
    '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];

// TAMBAHAN: Ambil daftar Satuan Kerja untuk dropdown
$satuanKerjaList = [];

// Metode 1: Coba dari API
$apiOptionsUrl = $apiBaseUrl . '?action=options';
$optionsResponse = @file_get_contents($apiOptionsUrl);
if ($optionsResponse) {
    $optionsData = json_decode($optionsResponse, true);
    if ($optionsData && isset($optionsData['success']) && $optionsData['success']) {
        $satuanKerjaList = $optionsData['options']['satuan_kerja'] ?? [];
    }
}

// Metode 2: Jika API gagal, query langsung ke database (FALLBACK)
if (empty($satuanKerjaList)) {
    try {
        // Include database connection jika belum ada
        if (!isset($db)) {
            include_once '../../config/database.php';
            $database = new Database();
            $db = $database->getConnection();
        }
        
        // Query langsung untuk ambil distinct Satuan_Kerja dari tabel swakelola
        $sql = "SELECT DISTINCT Satuan_Kerja FROM rup_keseluruhan_swakelola 
                WHERE Satuan_Kerja IS NOT NULL AND Satuan_Kerja != '' 
                ORDER BY Satuan_Kerja ASC 
                LIMIT 500";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $satuanKerjaList = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        // Log error tapi jangan stop eksekusi
        error_log("Error fetching Satuan Kerja (Swakelola): " . $e->getMessage());
        $satuanKerjaList = [];
    }
}

include '../../navbar/header.php';
?>

<script src="../../js/submenu.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<style>
    .container {
        max-width: 1400px;
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
        grid-template-columns: 1fr 1fr 1fr 1fr;
    }

    .filter-row:nth-child(2) {
        grid-template-columns: 1fr 1fr 1fr;
    }

    .filter-row:nth-child(3) {
        grid-template-columns: 2fr 1fr;
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

    .pagu-range-container {
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

    .pagu-range-container:focus-within {
        border-color: #dc3545;
        box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.15);
        background: white;
    }

    .pagu-range-container input[type="text"] {
        border: none;
        background: transparent;
        padding: 10px 12px;
        font-size: 14px;
        color: #2c3e50;
        border-radius: 6px;
    }

    .pagu-range-container input[type="text"]:focus {
        outline: none;
        background: white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .pagu-separator {
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
        padding: 30px 25px;
    }

    .summary-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
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

    .price {
        font-weight: 700;
        color: #27ae60;
        white-space: nowrap;
        font-size: 15px;
    }

    .small-text {
        font-size: 12px;
        color: #6c757d;
        margin-top: 4px;
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
        .pagu-range-container {
            grid-template-columns: 1fr;
            gap: 15px;
            padding: 15px;
            text-align: center;
        }
        .pagu-separator {
            transform: rotate(90deg);
        }
    }

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
            <h3>Filter Data Swakelola</h3>
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
                        <label><i class="fas fa-tools"></i> Tipe Swakelola</label>
                        <select name="tipe_swakelola">
                            <option value="">Semua Tipe</option>
                            <option value="Swakelola Penuh" <?= ($_GET['tipe_swakelola'] ?? '') == 'Swakelola Penuh' ? 'selected' : '' ?>>Swakelola Penuh</option>
                            <option value="Swakelola Sebagian" <?= ($_GET['tipe_swakelola'] ?? '') == 'Swakelola Sebagian' ? 'selected' : '' ?>>Swakelola Sebagian</option>
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
                        <label><i class="fas fa-building"></i> KLPD</label>
                        <select name="klpd">
                            <option value="">Semua KLPD</option>
                            <option value="Kota Banjarmasin" <?= ($_GET['klpd'] ?? '') == 'Kota Banjarmasin' ? 'selected' : '' ?>>Kota Banjarmasin</option>
                            <option value="Kabupaten Banjar" <?= ($_GET['klpd'] ?? '') == 'Kabupaten Banjar' ? 'selected' : '' ?>>Kabupaten Banjar</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label><i class="fas fa-sitemap"></i> Satuan Kerja</label>
                        <select name="satuan_kerja" id="satuan_kerja">
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
                        <label><i class="fas fa-money-bill-wave"></i> Range Pagu (Rp)</label>
                        <div class="pagu-range-container">
                            <input type="text" name="pagu_min" placeholder="Min Pagu" value="<?= htmlspecialchars($_GET['pagu_min'] ?? '') ?>">
                            <span class="pagu-separator">S/D</span>
                            <input type="text" name="pagu_max" placeholder="Max Pagu" value="<?= htmlspecialchars($_GET['pagu_max'] ?? '') ?>">
                        </div>
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
                            <option value="10" <?= ($limit == 10) ? 'selected' : '' ?>>10 Data</option>
                            <option value="25" <?= ($limit == 25) ? 'selected' : '' ?>>25 Data</option>
                            <option value="50" <?= ($limit == 50) ? 'selected' : '' ?>>50 Data</option>
                            <option value="100" <?= ($limit == 100) ? 'selected' : '' ?>>100 Data</option>
                        </select>
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
                <h3>Ringkasan Data Swakelola</h3>
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
                        <div class="card-subtitle">Swakelola - <?= $namaBulan[$selectedBulan] ?> <?= $selectedTahun ?></div>
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
                    <i class="fas fa-table"></i> Hasil Pencarian Data Swakelola
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
                            <th style="width: 280px;"><i class="fas fa-box"></i> Paket Swakelola</th>
                            <th style="width: 130px;"><i class="fas fa-money-bill-wave"></i> Pagu (Rp)</th>
                            <th style="width: 140px;"><i class="fas fa-tools"></i> Tipe Swakelola</th>
                            <th style="width: 100px;"><i class="fas fa-exchange-alt"></i> Perubahan</th>
                            <th style="width: 120px;"><i class="fas fa-calendar"></i> Pemilihan</th>
                            <th style="width: 120px;"><i class="fas fa-building"></i> KLPD</th>
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
                                <td><span class="badge badge-primary"><?= htmlspecialchars($row['Tipe_Swakelola']) ?></span></td>
                                <td>
                                    <span class="badge <?= ($row['perubahan'] ?? 'Tidak') == 'Perubahan' ? 'badge-warning' : 'badge-info' ?>">
                                        <?= htmlspecialchars($row['perubahan'] ?? 'Tidak') ?>
                                    </span>
                                </td>
                                <td><small><?= htmlspecialchars($row['Pemilihan']) ?></small></td>
                                <td><small><?= htmlspecialchars($row['KLPD']) ?></small></td>
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
                    <strong>Total Data: <?= number_format($totalRecords, 0, ',', '.') ?></strong> swakelola
                </div>
            </div>

        <?php else : ?>
            <div class="empty-state">
                <i class="fas fa-search-minus"></i>
                <p><strong>Tidak ada data swakelola yang ditemukan</strong></p>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.querySelector('form');

    // DEBUG: Check Satuan Kerja dropdown
    const satuanKerjaSelect = document.querySelector('#satuan_kerja');
    if (satuanKerjaSelect) {
        console.log('✅ Satuan Kerja dropdown found (Swakelola)');
        console.log('Total options:', satuanKerjaSelect.options.length);
        
        if (satuanKerjaSelect.options.length <= 1) {
            console.warn('⚠️ Satuan Kerja dropdown kosong (Swakelola)! Cek API response.');
        } else {
            console.log('✅ Satuan Kerja loaded:', satuanKerjaSelect.options.length - 1, 'items');
        }
    }

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

    const today = new Date().toISOString().split('T')[0];

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

    document.querySelectorAll('.price').forEach(priceCell => {
        const text = priceCell.textContent.trim();
        if (text && !isNaN(text.replace(/[^\d]/g, ''))) {
            const number = parseInt(text.replace(/[^\d]/g, ''));
            if (number > 0) {
                priceCell.innerHTML = '<i class="fas fa-rupiah-sign" style="font-size: 12px; margin-right: 3px;"></i>Rp ' + number.toLocaleString('id-ID');
            }
        }
    });

    const bulanSelect = document.querySelector('select[name="bulan"]');
    const tahunSelect = document.querySelector('select[name="tahun"]');
    
    if (bulanSelect) {
        bulanSelect.addEventListener('change', function() {
            // Optional: auto-submit when month changes
        });
    }
    
    if (tahunSelect) {
        tahunSelect.addEventListener('change', function() {
            // Optional: auto-submit when year changes
        });
    }

    const paguMinInput = document.querySelector('input[name="pagu_min"]');
    const paguMaxInput = document.querySelector('input[name="pagu_max"]');

    if (paguMinInput && paguMaxInput) {
        const validatePagu = () => {
            const minVal = parseInt(paguMinInput.value.replace(/\./g, '')) || 0;
            const maxVal = parseInt(paguMaxInput.value.replace(/\./g, '')) || 0;

            if (minVal > 0 && maxVal > 0 && minVal > maxVal) {
                paguMaxInput.style.borderColor = '#dc3545';
                paguMaxInput.title = 'Pagu maks tidak boleh lebih kecil dari pagu min';
            } else {
                paguMaxInput.style.borderColor = '';
                paguMaxInput.title = '';
            }
        };
        paguMinInput.addEventListener('input', validatePagu);
        paguMaxInput.addEventListener('input', validatePagu);
    }

    if (paguMinInput) {
        paguMinInput.addEventListener('blur', function() {
            if (this.value) {
                const number = parseInt(this.value.replace(/\D/g, ''));
                if (!isNaN(number)) {
                    this.value = number.toLocaleString('id-ID');
                }
            }
        });

        paguMinInput.addEventListener('focus', function() {
            this.value = this.value.replace(/\./g, '');
        });
    }

    if (paguMaxInput) {
        paguMaxInput.addEventListener('blur', function() {
            if (this.value) {
                const number = parseInt(this.value.replace(/\D/g, ''));
                if (!isNaN(number)) {
                    this.value = number.toLocaleString('id-ID');
                }
            }
        });

        paguMaxInput.addEventListener('focus', function() {
            this.value = this.value.replace(/\./g, '');
        });
    }
});

function resetForm() {
    window.location.href = window.location.pathname + '?bulan=07&tahun=<?= $currentYear ?>&perubahan=Tidak';
}

document.querySelector('form').addEventListener('submit', function(e) {
    const submitBtn = this.querySelector('.search-btn');
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
    if (urlParams.toString()) {
        const resultsSection = document.querySelector('.results-section');
        if (resultsSection) {
            setTimeout(() => {
                resultsSection.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }, 300);
        }
    }
});

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

console.log('🎯 Swakelola Page Loaded');
console.log('📅 Selected Period: <?= $namaBulan[$selectedBulan] ?> <?= $selectedTahun ?>');
console.log('📦 Total Records: <?= $totalRecords ?>');
console.log('📦 Total Paket: <?= $totalPaket ?>');
console.log('💰 Total Pagu: <?= $totalPagu ?>');
console.log('🔧 Satuan Kerja Options: <?= count($satuanKerjaList) ?>');
</script>

<?php
include '../../navbar/footer.php';
?>