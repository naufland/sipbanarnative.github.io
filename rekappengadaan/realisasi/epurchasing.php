<?php
// =================================================================
// == FIXED VERSION WITH PROPER ERROR HANDLING ===================
// =================================================================

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define database configuration variables BEFORE including the config
$host = "localhost";
$dbname = "sipbanar";
$username = "root";
$password = "";

// Alternative: Include config file if it exists
if (file_exists('../../config/database.php')) {
    include '../../config/database.php';
}

// Create database connection with error handling
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("
    <div style='background: #f8d7da; color: #721c24; padding: 20px; border: 1px solid #f5c6cb; border-radius: 5px; margin: 20px; font-family: Arial, sans-serif;'>
        <h3>Database Connection Error</h3>
        <p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
        <p><strong>Possible solutions:</strong></p>
        <ul>
            <li>Check if your database server (XAMPP/WAMP) is running</li>
            <li>Verify database name: <code>$dbname</code></li>
            <li>Check username: <code>$username</code></li>
            <li>Verify password: <code>" . (empty($password) ? '(empty)' : '(hidden)') . "</code></li>
            <li>Make sure the database 'sipbanar' exists</li>
        </ul>
    </div>");
}

// 1. Get parameters from URL
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;

// 2. Prepare WHERE conditions based on filters
$whereConditions = [];
$params = [];

// Date filter
if (!empty($_GET['tanggal_awal']) && !empty($_GET['tanggal_akhir'])) {
    $whereConditions[] = "tanggal_buat_paket BETWEEN :tanggal_awal AND :tanggal_akhir";
    $params['tanggal_awal'] = $_GET['tanggal_awal'];
    $params['tanggal_akhir'] = $_GET['tanggal_akhir'];
} elseif (!empty($_GET['tanggal_awal'])) {
    $whereConditions[] = "tanggal_buat_paket >= :tanggal_awal";
    $params['tanggal_awal'] = $_GET['tanggal_awal'];
} elseif (!empty($_GET['tanggal_akhir'])) {
    $whereConditions[] = "tanggal_buat_paket <= :tanggal_akhir";
    $params['tanggal_akhir'] = $_GET['tanggal_akhir'];
}

// Type filter
if (!empty($_GET['jenis_pengadaan'])) {
    $whereConditions[] = "jnt_jenis_produk = :jenis_pengadaan";
    $params['jenis_pengadaan'] = $_GET['jenis_pengadaan'];
}

// KLPD filter
if (!empty($_GET['klpd'])) {
    $whereConditions[] = "kd_klpd = :klpd";
    $params['klpd'] = $_GET['klpd'];
}

// Method filter
if (!empty($_GET['metode'])) {
    $whereConditions[] = "nama_sumber_dana = :metode";
    $params['metode'] = $_GET['metode'];
}

// Search filter
if (!empty($_GET['search'])) {
    $whereConditions[] = "(nama_paket LIKE :search OR deskripsi LIKE :search)";
    $params['search'] = '%' . $_GET['search'] . '%';
}

// Build WHERE clause
$whereClause = '';
if (!empty($whereConditions)) {
    $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
}

// 3. Check if table exists and get total records
$totalRecords = 0;
$totalPages = 1;
try {
    $countSql = "SELECT COUNT(*) as total FROM realisasi_epurchasing $whereClause";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetchColumn();
    $totalPages = ceil($totalRecords / $limit);
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Error counting records: " . htmlspecialchars($e->getMessage()) . "</div>";
}

// 4. Get data with pagination
$data = [];
$offset = ($currentPage - 1) * $limit;

try {
    $dataSql = "SELECT 
        id,
        tahun_anggaran,
        kd_klpd,
        satker_id,
        nama_satker,
        kd_paket,
        no_paket,
        nama_paket,
        kd_rup,
        nama_sumber_dana,
        kd_komoditas,
        kd_produk,
        jnt_penyedia_distributor,
        jnt_jenis_produk,
        kuantitas,
        harga_satuan,
        ongkos_kirim,
        total_harga,
        user_ppk,
        no_telp_user_ppkja,
        email_user_ppkja,
        kd_user_ppk,
        nip,
        jabatan_ppk,
        tanggal_buat_paket,
        tanggal_edit_paket,
        deskripsi,
        status_paket,
        paket_status_str,
        catatan_produk,
        kd_kabupaten_wilayah_harga
        FROM realisasi_epurchasing 
        $whereClause 
        ORDER BY tanggal_buat_paket DESC 
        LIMIT :limit OFFSET :offset";

    $dataStmt = $pdo->prepare($dataSql);
    foreach ($params as $key => $value) {
        $dataStmt->bindValue($key, $value);
    }
    $dataStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $dataStmt->execute();
    $data = $dataStmt->fetchAll();
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Error fetching data: " . htmlspecialchars($e->getMessage()) . "</div>";
}

// 5. Get statistics
$totalPaket = 0;
$totalPagu = 0;
$avgPagu = 0;

try {
    $summarySql = "SELECT 
        COUNT(*) as total_paket,
        COALESCE(SUM(total_harga), 0) as total_pagu,
        COALESCE(AVG(total_harga), 0) as avg_pagu
        FROM realisasi_epurchasing 
        $whereClause";

    $summaryStmt = $pdo->prepare($summarySql);
    $summaryStmt->execute($params);
    $summary = $summaryStmt->fetch();

    $totalPaket = $summary['total_paket'] ?? 0;
    $totalPagu = $summary['total_pagu'] ?? 0;
    $avgPagu = $summary['avg_pagu'] ?? 0;
} catch (PDOException $e) {
    echo "<div class='alert alert-warning'>Error getting statistics: " . htmlspecialchars($e->getMessage()) . "</div>";
}

// Format values
$formattedTotalPagu = 'Rp ' . number_format($totalPagu, 0, ',', '.');
$formattedAvgPagu = 'Rp ' . number_format($avgPagu, 0, ',', '.');

// 6. Get breakdown statistics
$breakdown = [];
$breakdownQueries = [
    'jenis_pengadaan' => "SELECT jnt_jenis_produk, COUNT(*) as count, COALESCE(SUM(total_harga), 0) as total_pagu FROM realisasi_epurchasing $whereClause GROUP BY jnt_jenis_produk",
    'klpd' => "SELECT kd_klpd, COUNT(*) as count, COALESCE(SUM(total_harga), 0) as total_pagu FROM realisasi_epurchasing $whereClause GROUP BY kd_klpd",
    'metode' => "SELECT nama_sumber_dana, COUNT(*) as count, COALESCE(SUM(total_harga), 0) as total_pagu FROM realisasi_epurchasing $whereClause GROUP BY nama_sumber_dana ORDER BY total_pagu DESC"
];

foreach ($breakdownQueries as $key => $sql) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $breakdown[$key] = $stmt->fetchAll();
    } catch (PDOException $e) {
        $breakdown[$key] = [];
        error_log("Error in breakdown query $key: " . $e->getMessage());
    }
}

// Set page title
$page_title = "Data Pengadaan - SIP BANAR";

// Include header if exists
if (file_exists('../../navbar/header.php')) {
    include '../../navbar/header.php';
} else {
    echo '<!DOCTYPE html><html><head><title>' . $page_title . '</title><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"></head><body>';
}

?>

<script src="../../js/submenu.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<style>
    /* All your existing CSS styles here - keeping them exactly the same */
    .container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 20px;
    }

    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border: 1px solid transparent;
        border-radius: 4px;
    }

    .alert-danger {
        color: #721c24;
        background-color: #f8d7da;
        border-color: #f5c6cb;
    }

    .alert-warning {
        color: #856404;
        background-color: #fff3cd;
        border-color: #ffecb5;
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
    }

    .filter-header h3 {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
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
        grid-template-columns: 300px 1fr;
    }

    .filter-group label {
        display: block;
        margin-bottom: 10px;
        font-weight: 600;
        color: #2c3e50;
        font-size: 14px;
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

    .date-range-group label {
        display: block;
        margin-bottom: 10px;
        font-weight: 600;
        color: #2c3e50;
        font-size: 14px;
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

    .date-separator {
        color: #dc3545;
        font-weight: 700;
        font-size: 14px;
        padding: 8px 12px;
        background: white;
        border-radius: 20px;
        border: 2px solid #dc3545;
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

    .search-btn,
    .reset-btn {
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
        border: none;
    }

    .reset-btn {
        background: transparent;
        color: #6c757d;
        border: 2px solid #e9ecef;
    }

    /* Summary section */
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
    }

    .summary-header h3 {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
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

    /* Results section */
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
        font-size: 13px;
        text-transform: uppercase;
    }

    table td {
        padding: 18px 15px;
        border-bottom: 1px solid #f1f1f1;
        vertical-align: top;
    }

    table tr:hover {
        background: #f8f9fa;
    }

    .badge {
        display: inline-block;
        padding: 6px 12px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
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

    /* Responsive */
    @media (max-width: 992px) {

        .filter-row:nth-child(1),
        .filter-row:nth-child(2),
        .filter-row:nth-child(3) {
            grid-template-columns: 1fr;
        }

        .results-header {
            flex-direction: column;
            gap: 15px;
        }
    }
</style>

<div class="container">
    <div class="filter-section">
        <div class="filter-header">
            <i class="fas fa-filter"></i>
            <h3>Filter Data Pengadaan</h3>
        </div>
        <div class="filter-content">
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="date-range-group">
                        <label><i class="fas fa-calendar-alt"></i> Periode Tanggal</label>
                        <div class="date-range-container">
                            <input type="date" name="tanggal_awal" value="<?= htmlspecialchars($_GET['tanggal_awal'] ?? '') ?>">
                            <span class="date-separator">S/D</span>
                            <input type="date" name="tanggal_akhir" value="<?= htmlspecialchars($_GET['tanggal_akhir'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="filter-group">
                        <label><i class="fas fa-tags"></i> Jenis Pengadaan</label>
                        <select name="jenis_pengadaan">
                            <option value="">Semua Jenis</option>
                            <?php
                            try {
                                $jenisStmt = $pdo->query("SELECT DISTINCT jnt_jenis_produk FROM realisasi_epurchasing WHERE jnt_jenis_produk IS NOT NULL ORDER BY jnt_jenis_produk");
                                while ($row = $jenisStmt->fetch()) {
                                    $selected = ($_GET['jenis_pengadaan'] ?? '') == $row['jnt_jenis_produk'] ? 'selected' : '';
                                    echo "<option value=\"{$row['jnt_jenis_produk']}\" $selected>{$row['jnt_jenis_produk']}</option>";
                                }
                            } catch (PDOException $e) {
                                echo "<option value=''>Error loading options</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="filter-row">
                    <div class="filter-group">
                        <label><i class="fas fa-building"></i> KLPD</label>
                        <select name="klpd">
                            <option value="">Semua KLPD</option>
                            <?php
                            try {
                                $klpdStmt = $pdo->query("SELECT DISTINCT kd_klpd FROM realisasi_epurchasing WHERE kd_klpd IS NOT NULL ORDER BY kd_klpd");
                                while ($row = $klpdStmt->fetch()) {
                                    $selected = ($_GET['klpd'] ?? '') == $row['kd_klpd'] ? 'selected' : '';
                                    echo "<option value=\"{$row['kd_klpd']}\" $selected>{$row['kd_klpd']}</option>";
                                }
                            } catch (PDOException $e) {
                                echo "<option value=''>Error loading options</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-cogs"></i> Metode</label>
                        <select name="metode">
                            <option value="">Semua Metode</option>
                            <?php
                            try {
                                $metodeStmt = $pdo->query("SELECT DISTINCT nama_sumber_dana FROM realisasi_epurchasing WHERE nama_sumber_dana IS NOT NULL ORDER BY nama_sumber_dana");
                                while ($row = $metodeStmt->fetch()) {
                                    $selected = ($_GET['metode'] ?? '') == $row['nama_sumber_dana'] ? 'selected' : '';
                                    echo "<option value=\"{$row['nama_sumber_dana']}\" $selected>{$row['nama_sumber_dana']}</option>";
                                }
                            } catch (PDOException $e) {
                                echo "<option value=''>Error loading options</option>";
                            }
                            ?>
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

                <div class="filter-row">
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
            <h3>Ringkasan Data Pengadaan</h3>
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
                        <div class="card-subtitle">Pengadaan</div>
                    </div>
                </div>

                <div class="summary-card success">
                    <div class="card-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="card-content">
                        <div class="card-value"><?= $formattedTotalPagu ?></div>
                        <div class="card-label">Total Pagu</div>
                        <div class="card-subtitle">Keseluruhan</div>
                    </div>
                </div>

                <div class="summary-card info">
                    <div class="card-icon">
                        <i class="fas fa-calculator"></i>
                    </div>
                    <div class="card-content">
                        <div class="card-value"><?= $formattedAvgPagu ?></div>
                        <div class="card-label">Rata-rata Pagu</div>
                        <div class="card-subtitle">Per Paket</div>
                    </div>
                </div>
            </div>

            <!-- Statistics Tables -->
            <?php if (!empty($breakdown['jenis_pengadaan']) || !empty($breakdown['klpd']) || !empty($breakdown['metode'])): ?>
                <div class="stats-tables">
                    <?php if (!empty($breakdown['jenis_pengadaan'])): ?>
                        <div class="stats-table">
                            <h4><i class="fas fa-tags"></i> Berdasarkan Jenis Pengadaan</h4>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Jenis Pengadaan</th>
                                        <th>Jumlah Paket</th>
                                        <th>Total Pagu</th>
                                        <th>Persentase</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($breakdown['jenis_pengadaan'] as $item): ?>
                                        <?php $percentage = $totalPagu > 0 ? ($item['total_pagu'] / $totalPagu * 100) : 0; ?>
                                        <tr>
                                            <td><span class="badge badge-primary"><?= htmlspecialchars($item['jnt_jenis_produk']) ?></span></td>
                                            <td><strong><?= number_format($item['count'], 0, ',', '.') ?> paket</strong></td>
                                            <td class="price">Rp <?= number_format($item['total_pagu'], 0, ',', '.') ?></td>
                                            <td><?= number_format($percentage, 1) ?>%</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($breakdown['klpd'])): ?>
                        <div class="stats-table">
                            <h4><i class="fas fa-building"></i> Berdasarkan KLPD</h4>
                            <table>
                                <thead>
                                    <tr>
                                        <th>KLPD</th>
                                        <th>Jumlah Paket</th>
                                        <th>Total Pagu</th>
                                        <th>Persentase</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($breakdown['klpd'] as $item): ?>
                                        <?php $percentage = $totalPagu > 0 ? ($item['total_pagu'] / $totalPagu * 100) : 0; ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($item['kd_klpd']) ?></strong></td>
                                            <td><?= number_format($item['count'], 0, ',', '.') ?> paket</td>
                                            <td class="price">Rp <?= number_format($item['total_pagu'], 0, ',', '.') ?></td>
                                            <td><?= number_format($percentage, 1) ?>%</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($breakdown['metode'])): ?>
                        <div class="stats-table">
                            <h4><i class="fas fa-cogs"></i> Top 5 Metode Pengadaan</h4>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Metode</th>
                                        <th>Jumlah Paket</th>
                                        <th>Total Pagu</th>
                                        <th>Persentase</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($breakdown['metode'], 0, 5) as $item): ?>
                                        <?php $percentage = $totalPagu > 0 ? ($item['total_pagu'] / $totalPagu * 100) : 0; ?>
                                        <tr>
                                            <td><?= htmlspecialchars($item['nama_sumber_dana']) ?></td>
                                            <td><?= number_format($item['count'], 0, ',', '.') ?> paket</td>
                                            <td class="price">Rp <?= number_format($item['total_pagu'], 0, ',', '.') ?></td>
                                            <td><?= number_format($percentage, 1) ?>%</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <style>
                    .stats-tables {
                        display: grid;
                        gap: 30px;
                    }

                    .stats-table {
                        background: #f8f9fa;
                        border-radius: 12px;
                        padding: 25px;
                        border: 1px solid #e9ecef;
                    }

                    .stats-table h4 {
                        margin: 0 0 20px 0;
                        color: #2c3e50;
                        font-size: 16px;
                        font-weight: 600;
                        display: flex;
                        align-items: center;
                        gap: 8px;
                    }

                    .stats-table h4 i {
                        color: #dc3545;
                    }

                    .stats-table table {
                        width: 100%;
                        background: white;
                        border-radius: 8px;
                        overflow: hidden;
                        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
                    }

                    .stats-table th {
                        background: #2c3e50;
                        color: white;
                        padding: 15px;
                        font-size: 13px;
                        text-transform: uppercase;
                        letter-spacing: 0.5px;
                        font-weight: 600;
                    }

                    .stats-table td {
                        padding: 15px;
                        border-bottom: 1px solid #f1f1f1;
                        vertical-align: middle;
                    }

                    .stats-table tr:hover {
                        background: #f8f9fa;
                    }
                </style>
            <?php endif; ?>
        </div>
    </div>

    <div class="results-section">
        <div class="results-header">
            <div>
                <div class="results-title">
                    <i class="fas fa-table"></i> Hasil Pencarian Data Pengadaan
                </div>
                <?php if (!empty($data)): ?>
                    <div class="results-subtitle">
                        <strong>Menampilkan <?= count($data) ?> dari <?= number_format($totalRecords, 0, ',', '.') ?> total data</strong>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php
                    // Prepare pagination parameters
                    $paginationParams = $_GET;
                    unset($paginationParams['page']);
                    $paginationQuery = http_build_query($paginationParams);
                    ?>

                    <a href="?<?= $paginationQuery ?>&page=<?= max(1, $currentPage - 1) ?>"
                        class="btn-pagination <?= $currentPage <= 1 ? 'disabled' : '' ?>"
                        title="Halaman Sebelumnya">
                        <i class="fas fa-chevron-left"></i>
                    </a>

                    <?php
                    for ($i = 1; $i <= min($totalPages, 10); $i++) {
                        if ($i == $currentPage) {
                            echo '<a href="?' . $paginationQuery . '&page=' . $i . '" class="btn-pagination active">' . $i . '</a>';
                        } else {
                            echo '<a href="?' . $paginationQuery . '&page=' . $i . '" class="btn-pagination">' . $i . '</a>';
                        }
                    }

                    if ($totalPages > 10) {
                        echo '<span class="btn-pagination-dots">...</span>';
                        echo '<a href="?' . $paginationQuery . '&page=' . $totalPages . '" class="btn-pagination">' . $totalPages . '</a>';
                    }
                    ?>

                    <a href="?<?= $paginationQuery ?>&page=<?= min($totalPages, $currentPage + 1) ?>"
                        class="btn-pagination <?= $currentPage >= $totalPages ? 'disabled' : '' ?>"
                        title="Halaman Selanjutnya">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>

                <style>
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
                </style>
            <?php endif; ?>
        </div>

        <?php if (!empty($data)): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 280px;"><i class="fas fa-box"></i> Paket Pengadaan</th>
                            <th style="width: 130px;"><i class="fas fa-money-bill-wave"></i> Total Harga (Rp)</th>
                            <th style="width: 140px;"><i class="fas fa-tags"></i> Jenis Pengadaan</th>
                            <th style="width: 120px;"><i class="fas fa-store"></i> Kuantitas</th>
                            <th style="width: 120px;"><i class="fas fa-cogs"></i> Metode</th>
                            <th style="width: 120px;"><i class="fas fa-calendar"></i> Tanggal Buat</th>
                            <th style="width: 120px;"><i class="fas fa-building"></i> KLPD</th>
                            <th style="width: 200px;"><i class="fas fa-sitemap"></i> Satuan Kerja</th>
                            <th style="width: 150px;"><i class="fas fa-user"></i> PPK</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data as $row): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 700; color: #2c3e50; margin-bottom: 5px;">
                                        <?= htmlspecialchars($row['nama_paket'] ?? 'N/A') ?>
                                    </div>
                                    <div class="small-text">
                                        <i class="fas fa-id-card"></i> ID: <?= htmlspecialchars($row['id'] ?? 'N/A') ?>
                                    </div>
                                    <?php if (!empty($row['kd_paket'])): ?>
                                        <div class="small-text">
                                            <i class="fas fa-code"></i> Kode: <?= htmlspecialchars($row['kd_paket']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="price">
                                    Rp <?= number_format($row['total_harga'] ?? 0, 0, ',', '.') ?>
                                    <?php if (!empty($row['harga_satuan']) && $row['harga_satuan'] > 0): ?>
                                        <div class="small-text">
                                            @Rp <?= number_format($row['harga_satuan'], 0, ',', '.') ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['jnt_jenis_produk'])): ?>
                                        <span class="badge badge-primary"><?= htmlspecialchars($row['jnt_jenis_produk']) ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-success"><?= number_format($row['kuantitas'] ?? 0, 0, ',', '.') ?></span>
                                </td>
                                <td>
                                    <small><?= htmlspecialchars($row['nama_sumber_dana'] ?? 'N/A') ?></small>
                                </td>
                                <td>
                                    <small>
                                        <?php
                                        if (!empty($row['tanggal_buat_paket'])) {
                                            echo date('d/m/Y', strtotime($row['tanggal_buat_paket']));
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </small>
                                </td>
                                <td><small><?= htmlspecialchars($row['kd_klpd'] ?? 'N/A') ?></small></td>
                                <td><small><?= htmlspecialchars($row['nama_satker'] ?? 'N/A') ?></small></td>
                                <td>
                                    <small><?= htmlspecialchars($row['user_ppk'] ?? 'N/A') ?></small>
                                    <?php if (!empty($row['jabatan_ppk'])): ?>
                                        <div class="small-text">
                                            <?= htmlspecialchars($row['jabatan_ppk']) ?>
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
                    <strong>Total Data: <?= number_format($totalRecords, 0, ',', '.') ?></strong> pengadaan
                </div>
            </div>

            <style>
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

                .badge-secondary {
                    background: #6c757d;
                    color: white;
                }

                @media (max-width: 768px) {
                    .table-footer {
                        flex-direction: column;
                        gap: 10px;
                        text-align: center;
                    }
                }
            </style>

        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-search-minus"></i>
                <p><strong>Tidak ada data pengadaan yang ditemukan</strong></p>
                <small class="text-muted">Coba ubah kriteria pencarian atau filter yang Anda gunakan</small>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
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
        }

        // Form submission handler with URL cleanup
        const filterForm = document.querySelector('form');
        if (filterForm) {
            filterForm.addEventListener('submit', function(e) {
                // Show loading state
                const submitBtn = this.querySelector('.search-btn');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mencari...';
                submitBtn.disabled = true;

                // Disable empty inputs to clean URL
                const inputs = this.querySelectorAll('input, select');
                inputs.forEach(input => {
                    if (!input.value) {
                        input.disabled = true;
                    }
                });

                // Validate date range
                const awal = tanggalAwal ? tanggalAwal.value : '';
                const akhir = tanggalAkhir ? tanggalAkhir.value : '';

                if (awal && akhir && awal > akhir) {
                    e.preventDefault();
                    alert('Tanggal awal tidak boleh lebih besar dari tanggal akhir!');
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                    // Re-enable inputs
                    inputs.forEach(input => {
                        input.disabled = false;
                    });
                    return false;
                }

                return true;
            });
        }

        // Copy ID functionality
        document.querySelectorAll('.small-text').forEach(smallText => {
            if (smallText.textContent.includes('ID:')) {
                smallText.style.cursor = 'pointer';
                smallText.title = 'Klik untuk copy ID';
                smallText.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const idText = this.textContent.replace('ID: ', '').trim();
                    if (navigator.clipboard) {
                        navigator.clipboard.writeText(idText).then(() => {
                            const originalText = this.textContent;
                            this.textContent = '✓ ID Copied!';
                            this.style.color = '#27ae60';
                            setTimeout(() => {
                                this.textContent = originalText;
                                this.style.color = '';
                            }, 1500);
                        });
                    }
                });
            }
        });
    });

    function resetForm() {
        window.location.href = window.location.pathname;
    }
</script>

<?php
// Include footer if exists
if (file_exists('../../navbar/footer.php')) {
    include '../../navbar/footer.php';
} else {
    echo '</body></html>';
}
?>