<?php
// =================================================================
// == DASHBOARD EPURCHASING DENGAN PERBAIKAN ====================
// =================================================================

// Include model dan config dengan error handling
$configLoaded = false;
$modelLoaded = false;

try {
    // Coba include config database
    if (file_exists(__DIR__ . '../config/database.php')) {
        require_once __DIR__ . '/config/database.php';
        $configLoaded = true;
    } elseif (file_exists(__DIR__ . '/../config/database.php')) {
        require_once __DIR__ . '../../config/database.php';
        $configLoaded = true;
    }
    
    // Coba include model
    if (file_exists(__DIR__ . '/RealisasiEpurchasingModel.php')) {
        require_once __DIR__ . '/RealisasiEpurchasingModel.php';
        $modelLoaded = true;
    } elseif (file_exists(__DIR__ . '../includes/RealisasiEpurchasingModel.php')) {
        require_once __DIR__ . '/../includes/RealisasiEpurchasingModel.php';
        $modelLoaded = true;
    }
} catch (Exception $e) {
    error_log("Include error: " . $e->getMessage());
}

// Set page title untuk header
$page_title = "Dashboard E-Purchasing - SIP BANAR";

// Include header jika ada
if (file_exists('../../navbar/header.php')) {
    include '../../navbar/header.php';
}

// Helper classes yang diperlukan
if (!class_exists('InputValidator')) {
    class InputValidator {
        public static function sanitizeFilters($input) {
            $filters = [];
            if (isset($input['tahun_anggaran'])) $filters['tahun_anggaran'] = intval($input['tahun_anggaran']);
            if (isset($input['kd_klpd'])) $filters['kd_klpd'] = htmlspecialchars(trim($input['kd_klpd']));
            if (isset($input['status_paket'])) $filters['status_paket'] = htmlspecialchars(trim($input['status_paket']));
            if (isset($input['search'])) $filters['search'] = htmlspecialchars(trim($input['search']));
            if (isset($input['tanggal_awal'])) $filters['tanggal_awal'] = $input['tanggal_awal'];
            if (isset($input['tanggal_akhir'])) $filters['tanggal_akhir'] = $input['tanggal_akhir'];
            if (isset($input['min_total'])) $filters['min_total'] = floatval($input['min_total']);
            if (isset($input['max_total'])) $filters['max_total'] = floatval($input['max_total']);
            return $filters;
        }
        
        public static function sanitizePagination($input) {
            return [
                'page' => max(1, intval($input['page'] ?? 1)),
                'limit' => min(500, max(10, intval($input['limit'] ?? 100)))
            ];
        }
    }
}

// Simple PengadaanModel jika tidak ada
if (!class_exists('PengadaanModel')) {
    class PengadaanModel {
        private $pdo;
        
        public function __construct(PDO $pdo) {
            $this->pdo = $pdo;
        }
        
        public function getPaginatedData($filters = [], $page = 1, $limit = 100) {
            try {
                $offset = ($page - 1) * $limit;
                
                // Build WHERE clause
                $whereConditions = ['1=1']; // Always true condition
                $params = [];
                
                if (!empty($filters['tahun_anggaran'])) {
                    $whereConditions[] = "tahun_anggaran = :tahun_anggaran";
                    $params[':tahun_anggaran'] = $filters['tahun_anggaran'];
                }
                
                if (!empty($filters['kd_klpd'])) {
                    $whereConditions[] = "kd_klpd = :kd_klpd";
                    $params[':kd_klpd'] = $filters['kd_klpd'];
                }
                
                if (!empty($filters['status_paket'])) {
                    $whereConditions[] = "status_paket = :status_paket";
                    $params[':status_paket'] = $filters['status_paket'];
                }
                
                if (!empty($filters['search'])) {
                    $whereConditions[] = "(nama_paket LIKE :search OR no_paket LIKE :search OR kode_anggaran LIKE :search)";
                    $params[':search'] = '%' . $filters['search'] . '%';
                }
                
                if (!empty($filters['tanggal_awal'])) {
                    $whereConditions[] = "tanggal_buat >= :tanggal_awal";
                    $params[':tanggal_awal'] = $filters['tanggal_awal'];
                }
                
                if (!empty($filters['tanggal_akhir'])) {
                    $whereConditions[] = "tanggal_buat <= :tanggal_akhir";
                    $params[':tanggal_akhir'] = $filters['tanggal_akhir'];
                }
                
                if (!empty($filters['min_total'])) {
                    $whereConditions[] = "total_harga >= :min_total";
                    $params[':min_total'] = $filters['min_total'];
                }
                
                if (!empty($filters['max_total'])) {
                    $whereConditions[] = "total_harga <= :max_total";
                    $params[':max_total'] = $filters['max_total'];
                }
                
                $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
                
                // Count total records
                $countSql = "SELECT COUNT(*) as total FROM pengadaan $whereClause";
                $countStmt = $this->pdo->prepare($countSql);
                $countStmt->execute($params);
                $totalRecords = $countStmt->fetch()['total'];
                
                // Get data with formatting
                $dataSql = "SELECT 
                                *,
                                CONCAT('Rp ', FORMAT(total_harga, 0, 'id_ID')) as formatted_total_harga,
                                DATE_FORMAT(tanggal_buat, '%d/%m/%Y') as formatted_tanggal_buat
                            FROM pengadaan 
                            $whereClause 
                            ORDER BY tanggal_buat DESC, id DESC 
                            LIMIT :limit OFFSET :offset";
                
                $dataStmt = $this->pdo->prepare($dataSql);
                
                foreach ($params as $key => $value) {
                    $dataStmt->bindValue($key, $value);
                }
                $dataStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                $dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
                
                $dataStmt->execute();
                $data = $dataStmt->fetchAll();
                
                $totalPages = ceil($totalRecords / $limit);
                
                return [
                    'data' => $data,
                    'pagination' => [
                        'current_page' => $page,
                        'total_pages' => $totalPages,
                        'total_records' => $totalRecords,
                        'limit' => $limit,
                        'has_next' => $page < $totalPages,
                        'has_prev' => $page > 1
                    ]
                ];
                
            } catch (Exception $e) {
                error_log("Error in getPaginatedData: " . $e->getMessage());
                return [
                    'data' => [],
                    'pagination' => [
                        'current_page' => 1,
                        'total_pages' => 0,
                        'total_records' => 0,
                        'limit' => $limit,
                        'has_next' => false,
                        'has_prev' => false
                    ]
                ];
            }
        }
        
        public function getSummary($filters = []) {
            try {
                // Build WHERE clause sama seperti di getPaginatedData
                $whereConditions = ['1=1'];
                $params = [];
                
                if (!empty($filters['tahun_anggaran'])) {
                    $whereConditions[] = "tahun_anggaran = :tahun_anggaran";
                    $params[':tahun_anggaran'] = $filters['tahun_anggaran'];
                }
                
                if (!empty($filters['kd_klpd'])) {
                    $whereConditions[] = "kd_klpd = :kd_klpd";
                    $params[':kd_klpd'] = $filters['kd_klpd'];
                }
                
                if (!empty($filters['status_paket'])) {
                    $whereConditions[] = "status_paket = :status_paket";
                    $params[':status_paket'] = $filters['status_paket'];
                }
                
                if (!empty($filters['search'])) {
                    $whereConditions[] = "(nama_paket LIKE :search OR no_paket LIKE :search OR kode_anggaran LIKE :search)";
                    $params[':search'] = '%' . $filters['search'] . '%';
                }
                
                if (!empty($filters['tanggal_awal'])) {
                    $whereConditions[] = "tanggal_buat >= :tanggal_awal";
                    $params[':tanggal_awal'] = $filters['tanggal_awal'];
                }
                
                if (!empty($filters['tanggal_akhir'])) {
                    $whereConditions[] = "tanggal_buat <= :tanggal_akhir";
                    $params[':tanggal_akhir'] = $filters['tanggal_akhir'];
                }
                
                if (!empty($filters['min_total'])) {
                    $whereConditions[] = "total_harga >= :min_total";
                    $params[':min_total'] = $filters['min_total'];
                }
                
                if (!empty($filters['max_total'])) {
                    $whereConditions[] = "total_harga <= :max_total";
                    $params[':max_total'] = $filters['max_total'];
                }
                
                $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
                
                $sql = "SELECT 
                            COUNT(*) as total_paket,
                            COALESCE(SUM(total_harga), 0) as total_pagu,
                            COALESCE(AVG(total_harga), 0) as avg_pagu,
                            COUNT(DISTINCT kd_klpd) as total_klpd
                        FROM pengadaan $whereClause";
                
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
                
                return $stmt->fetch();
                
            } catch (Exception $e) {
                error_log("Error in getSummary: " . $e->getMessage());
                return [
                    'total_paket' => 0,
                    'total_pagu' => 0,
                    'avg_pagu' => 0,
                    'total_klpd' => 0
                ];
            }
        }
        
        public function getFilterOptions() {
            try {
                $options = [];
                
                // Get unique years
                $yearStmt = $this->pdo->query("SELECT DISTINCT tahun_anggaran FROM pengadaan WHERE tahun_anggaran IS NOT NULL ORDER BY tahun_anggaran DESC");
                $options['tahun_anggaran'] = $yearStmt ? $yearStmt->fetchAll(PDO::FETCH_COLUMN) : [];
                
                // Get unique KLPD
                $klpdStmt = $this->pdo->query("SELECT DISTINCT kd_klpd FROM pengadaan WHERE kd_klpd IS NOT NULL AND kd_klpd != '' ORDER BY kd_klpd");
                $options['kd_klpd'] = $klpdStmt ? $klpdStmt->fetchAll(PDO::FETCH_COLUMN) : [];
                
                // Get unique status
                $statusStmt = $this->pdo->query("SELECT DISTINCT status_paket FROM pengadaan WHERE status_paket IS NOT NULL AND status_paket != '' ORDER BY status_paket");
                $options['status_paket'] = $statusStmt ? $statusStmt->fetchAll(PDO::FETCH_COLUMN) : [];
                
                return $options;
                
            } catch (Exception $e) {
                error_log("Error in getFilterOptions: " . $e->getMessage());
                return [
                    'tahun_anggaran' => [],
                    'kd_klpd' => [],
                    'status_paket' => []
                ];
            }
        }
    }
}

// Inisialisasi model dengan handling yang tepat
$pengadaanModel = null;
if ($configLoaded) {
    try {
        // Gunakan class Database (sesuai config Anda)
        if (class_exists('Database')) {
            $database = new Database();
            $pdo = $database->getConnection();
            $pengadaanModel = new PengadaanModel($pdo);
        }
    } catch (Exception $e) {
        error_log("Database connection error: " . $e->getMessage());
        $pengadaanModel = null;
    }
}

// URL API dasar - disesuaikan dengan struktur Anda
$apiBaseUrl = "api/epurchasing.php";

// 1. Dapatkan parameter dari URL, termasuk halaman saat ini
$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = isset($_GET['limit']) ? max(10, min(500, (int)$_GET['limit'])) : 100;

// 2. Siapkan parameter query untuk API
$queryParams = array_filter($_GET, function ($value) {
    return $value !== '' && $value !== null;
});
$queryParams['page'] = $currentPage;
$queryParams['limit'] = $limit;
$queryParams['action'] = 'list'; // Tambahkan action untuk API

$queryString = http_build_query($queryParams);
$apiUrl = $apiBaseUrl . '?' . $queryString;

// 3. Siapkan parameter untuk summary
$summaryParams = $queryParams;
unset($summaryParams['page'], $summaryParams['limit']);
$summaryParams['action'] = 'summary';

$summaryQueryString = http_build_query($summaryParams);
$apiSummaryUrl = $apiBaseUrl . '?' . $summaryQueryString;

// 4. Ambil data dari API atau model (fallback)
$data = null;
$summaryData = null;

// Coba ambil data dari API dulu (jika diperlukan)
// Untuk saat ini, gunakan model langsung karena lebih reliable

if ($pengadaanModel) {
    try {
        $filters = InputValidator::sanitizeFilters($_GET);
        $pagination = InputValidator::sanitizePagination($_GET);
        
        $result = $pengadaanModel->getPaginatedData($filters, $pagination['page'], $pagination['limit']);
        $data = [
            'success' => true,
            'data' => $result['data'],
            'pagination' => $result['pagination']
        ];
        
        $summary = $pengadaanModel->getSummary($filters);
        $summaryData = [
            'success' => true,
            'summary' => $summary
        ];
    } catch (Exception $e) {
        error_log("Model error: " . $e->getMessage());
        $data = ['success' => false, 'data' => []];
        $summaryData = ['success' => false, 'summary' => []];
    }
} else {
    // Jika model tidak tersedia, coba API
    if (function_exists('curl_init')) {
        $data = fetchDataWithCurl($apiUrl);
        $summaryData = fetchDataWithCurl($apiSummaryUrl);
    } else {
        $response = @file_get_contents($apiUrl);
        $data = $response ? json_decode($response, true) : null;
        
        $summaryResponse = @file_get_contents($apiSummaryUrl);
        $summaryData = $summaryResponse ? json_decode($summaryResponse, true) : null;
    }
}

// 5. Inisialisasi variabel statistik dengan nilai default
$totalPagu = 0;
$totalPaket = 0;
$formattedTotalPagu = 'Rp 0';
$avgPagu = 0;
$formattedAvgPagu = 'Rp 0';
$totalKlpd = 0;

// 6. Proses data statistik dari summary
if ($summaryData && isset($summaryData['success']) && $summaryData['success'] && isset($summaryData['summary'])) {
    $summary = $summaryData['summary'];

    $totalPaket = $summary['total_paket'] ?? 0;
    $totalPagu = $summary['total_pagu'] ?? 0;
    $avgPagu = $summary['avg_pagu'] ?? 0;
    $totalKlpd = $summary['total_klpd'] ?? 0;

    // Format nilai untuk ditampilkan
    $formattedTotalPagu = 'Rp ' . number_format($totalPagu, 0, ',', '.');
    $formattedAvgPagu = 'Rp ' . number_format($avgPagu, 0, ',', '.');
}

// 7. Siapkan variabel untuk paginasi
$totalPages = 1;
$totalRecords = 0;

if ($data && isset($data['pagination'])) {
    $totalPages = $data['pagination']['total_pages'] ?? 1;
    $totalRecords = $data['pagination']['total_records'] ?? 0;
}

// Jika summary memberikan total paket, gunakan itu karena lebih akurat
if ($totalPaket > 0 && $totalRecords == 0) {
    $totalRecords = $totalPaket;
}

// Function untuk cURL request
function fetchDataWithCurl($url, $timeout = 10) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'SIP-BANAR Dashboard/1.0');
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch) || $httpCode !== 200) {
        curl_close($ch);
        return null;
    }
    
    curl_close($ch);
    return json_decode($response, true);
}

// Ambil filter options
$filterOptions = [];
if ($pengadaanModel) {
    try {
        $filterOptions = $pengadaanModel->getFilterOptions();
    } catch (Exception $e) {
        error_log("Filter options error: " . $e->getMessage());
    }
}

?>

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">

<style>
    :root {
        --primary-color: #dc3545;
        --secondary-color: #6c757d;
        --success-color: #28a745;
        --info-color: #17a2b8;
        --warning-color: #ffc107;
        --light-bg: #f8f9fa;
        --dark-text: #2c3e50;
    }

    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        min-height: 100vh;
    }

    .main-header {
        background: linear-gradient(135deg, var(--primary-color) 0%, #c82333 100%);
        color: white;
        padding: 2rem 0;
        margin-bottom: 2rem;
        box-shadow: 0 4px 20px rgba(220, 53, 69, 0.3);
    }

    .main-header h1 {
        font-weight: 700;
        margin: 0;
        letter-spacing: 1px;
    }

    .main-header p {
        margin: 0.5rem 0 0 0;
        opacity: 0.9;
    }

    .filter-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
        border: none;
        overflow: hidden;
    }

    .filter-header {
        background: linear-gradient(135deg, var(--primary-color) 0%, #c82333 100%);
        color: white;
        padding: 1.5rem;
        border: none;
    }

    .filter-header h3 {
        margin: 0;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .summary-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .summary-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .summary-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
    }

    .summary-card.primary::before { background: var(--info-color); }
    .summary-card.success::before { background: var(--success-color); }
    .summary-card.info::before { background: var(--warning-color); }
    .summary-card.warning::before { background: var(--primary-color); }

    .summary-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }

    .summary-card .icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: white;
        margin-bottom: 1rem;
    }

    .summary-card.primary .icon { background: linear-gradient(135deg, var(--info-color), #5dccda); }
    .summary-card.success .icon { background: linear-gradient(135deg, var(--success-color), #58d68d); }
    .summary-card.info .icon { background: linear-gradient(135deg, var(--warning-color), #f8c471); }
    .summary-card.warning .icon { background: linear-gradient(135deg, var(--primary-color), #e74c3c); }

    .summary-card .value {
        font-size: 2rem;
        font-weight: 700;
        color: var(--dark-text);
        margin-bottom: 0.5rem;
    }

    .summary-card .label {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--dark-text);
        margin-bottom: 0.25rem;
    }

    .summary-card .subtitle {
        font-size: 0.875rem;
        color: var(--secondary-color);
    }

    .data-table {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        overflow: hidden;
    }

    .table-header {
        background: var(--light-bg);
        padding: 1.5rem;
        border-bottom: 2px solid #e9ecef;
    }

    .table-header h3 {
        margin: 0;
        color: var(--dark-text);
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .table-responsive {
        max-height: 600px;
        overflow-y: auto;
    }

    .table {
        margin: 0;
        font-size: 0.875rem;
    }

    .table thead th {
        background: linear-gradient(135deg, var(--dark-text) 0%, #34495e 100%);
        color: white;
        border: none;
        font-weight: 600;
        padding: 1rem 0.75rem;
        position: sticky;
        top: 0;
        z-index: 10;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .table tbody tr:hover {
        background-color: rgba(220, 53, 69, 0.05);
        transform: translateY(-1px);
        transition: all 0.2s ease;
    }

    .table td {
        padding: 1rem 0.75rem;
        vertical-align: middle;
        border-bottom: 1px solid #f1f1f1;
    }

    .badge {
        font-size: 0.75rem;
        padding: 0.5rem 0.75rem;
        border-radius: 20px;
    }

    .price {
        font-weight: 700;
        color: var(--success-color);
        white-space: nowrap;
    }

    .btn-filter {
        background: linear-gradient(135deg, var(--primary-color) 0%, #c82333 100%);
        border: none;
        border-radius: 10px;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-filter:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(220, 53, 69, 0.3);
    }

    .btn-reset {
        background: transparent;
        border: 2px solid #e9ecef;
        border-radius: 10px;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        color: var(--secondary-color);
        transition: all 0.3s ease;
    }

    .btn-reset:hover {
        border-color: var(--primary-color);
        color: var(--primary-color);
        background: rgba(220, 53, 69, 0.05);
    }

    .pagination {
        justify-content: center;
        margin-top: 1.5rem;
    }

    .page-link {
        border: none;
        border-radius: 8px;
        margin: 0 2px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .page-link:hover {
        background: var(--primary-color);
        color: white;
        transform: translateY(-1px);
    }

    .page-item.active .page-link {
        background: var(--primary-color);
        border-color: var(--primary-color);
    }

    .empty-state {
        text-align: center;
        padding: 3rem;
        color: var(--secondary-color);
    }

    .empty-state i {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.3;
    }

    .alert-warning {
        border-radius: 15px;
        border: none;
        box-shadow: 0 4px 15px rgba(255, 193, 7, 0.2);
    }

    .form-control, .form-select {
        border-radius: 10px;
        border: 2px solid #e9ecef;
        padding: 0.75rem;
        transition: all 0.3s ease;
    }

    .form-control:focus, .form-select:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.15);
    }

    @media (max-width: 768px) {
        .main-header {
            padding: 1rem 0;
            margin-bottom: 1rem;
        }
        .summary-cards {
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        .table-responsive {
            max-height: 400px;
        }
        .table {
            font-size: 0.75rem;
        }
    }
</style>

<div class="main-header">
    <div class="container">
        <h1><i class="fas fa-chart-line"></i> Dashboard E-Purchasing</h1>
        <p>Sistem Informasi Pengadaan Barang dan Jasa</p>
    </div>
</div>

<div class="container">
    <?php if (!$pengadaanModel): ?>
    <div class="alert alert-warning" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Peringatan:</strong> Model database tidak dapat diinisialisasi. 
        <?php if (!$configLoaded): ?>
            File config database tidak ditemukan.
        <?php else: ?>
            Koneksi database bermasalah.
        <?php endif; ?>
        Beberapa fitur mungkin tidak berfungsi dengan optimal.
        <details class="mt-2">
            <summary>Detail Error</summary>
            <small>
                Config loaded: <?= $configLoaded ? 'Yes' : 'No' ?><br>
                Model loaded: <?= $modelLoaded ? 'Yes' : 'No' ?><br>
                Database class available: <?= class_exists('Database') ? 'Yes' : 'No' ?>
            </small>
        </details>
    </div>
    <?php endif; ?>

    <!-- Filter Section -->
    <div class="card filter-card">
        <div class="card-header filter-header">
            <h3><i class="fas fa-filter"></i> Filter Data E-Purchasing</h3>
        </div>
        <div class="card-body">
            <form method="GET" action="">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label"><i class="fas fa-calendar"></i> Tahun Anggaran</label>
                        <select class="form-select" name="tahun_anggaran">
                            <option value="">Semua Tahun</option>
                            <?php if (!empty($filterOptions['tahun_anggaran'])): ?>
                                <?php foreach ($filterOptions['tahun_anggaran'] as $year): ?>
                                    <option value="<?= htmlspecialchars($year) ?>" 
                                            <?= ($_GET['tahun_anggaran'] ?? '') == $year ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($year) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="2024" <?= ($_GET['tahun_anggaran'] ?? '') == '2024' ? 'selected' : '' ?>>2024</option>
                                <option value="2023" <?= ($_GET['tahun_anggaran'] ?? '') == '2023' ? 'selected' : '' ?>>2023</option>
                                <option value="2022" <?= ($_GET['tahun_anggaran'] ?? '') == '2022' ? 'selected' : '' ?>>2022</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label"><i class="fas fa-building"></i> KLPD</label>
                        <select class="form-select" name="kd_klpd">
                            <option value="">Semua KLPD</option>
                            <?php if (!empty($filterOptions['kd_klpd'])): ?>
                                <?php foreach ($filterOptions['kd_klpd'] as $klpd): ?>
                                    <option value="<?= htmlspecialchars($klpd) ?>" 
                                            <?= ($_GET['kd_klpd'] ?? '') == $klpd ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($klpd) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label"><i class="fas fa-tags"></i> Status Paket</label>
                        <select class="form-select" name="status_paket">
                            <option value="">Semua Status</option>
                            <?php if (!empty($filterOptions['status_paket'])): ?>
                                <?php foreach ($filterOptions['status_paket'] as $status): ?>
                                    <option value="<?= htmlspecialchars($status) ?>" 
                                            <?= ($_GET['status_paket'] ?? '') == $status ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($status) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label"><i class="fas fa-list"></i> Limit Data</label>
                        <select class="form-select" name="limit">
                            <option value="25" <?= $limit == 25 ? 'selected' : '' ?>>25 Data</option>
                            <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>50 Data</option>
                            <option value="100" <?= $limit == 100 ? 'selected' : '' ?>>100 Data</option>
                            <option value="200" <?= $limit == 200 ? 'selected' : '' ?>>200 Data</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label"><i class="fas fa-calendar-alt"></i> Tanggal Awal</label>
                        <input type="date" class="form-control" name="tanggal_awal" 
                               value="<?= htmlspecialchars($_GET['tanggal_awal'] ?? '') ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label"><i class="fas fa-calendar-alt"></i> Tanggal Akhir</label>
                        <input type="date" class="form-control" name="tanggal_akhir" 
                               value="<?= htmlspecialchars($_GET['tanggal_akhir'] ?? '') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><i class="fas fa-search"></i> Pencarian</label>
                        <input type="text" class="form-control" name="search" 
                               placeholder="Cari nama paket, kode anggaran, atau nomor paket..." 
                               value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><i class="fas fa-money-bill"></i> Total Harga (Min)</label>
                        <input type="number" class="form-control" name="min_total" 
                               placeholder="Minimal total harga" 
                               value="<?= htmlspecialchars($_GET['min_total'] ?? '') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><i class="fas fa-money-bill"></i> Total Harga (Max)</label>
                        <input type="number" class="form-control" name="max_total" 
                               placeholder="Maksimal total harga" 
                               value="<?= htmlspecialchars($_GET['max_total'] ?? '') ?>">
                    </div>
                </div>
                <div class="d-flex gap-2 justify-content-end">
                    <a href="<?= strtok($_SERVER["REQUEST_URI"], '?') ?>" class="btn btn-reset">
                        <i class="fas fa-undo"></i> Reset Filter
                    </a>
                    <button type="submit" class="btn btn-primary btn-filter">
                        <i class="fas fa-search"></i> Terapkan Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Cards -->
    <?php if ($totalPaket > 0 || array_filter($_GET)): ?>
    <div class="summary-cards">
        <div class="summary-card primary">
            <div class="icon">
                <i class="fas fa-boxes"></i>
            </div>
            <div class="value"><?= number_format($totalPaket, 0, ',', '.') ?></div>
            <div class="label">Total Paket</div>
            <div class="subtitle">E-Purchasing</div>
        </div>

        <div class="summary-card success">
            <div class="icon">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="value"><?= $formattedTotalPagu ?></div>
            <div class="label">Total Pagu</div>
            <div class="subtitle">Keseluruhan</div>
        </div>

        <div class="summary-card info">
            <div class="icon">
                <i class="fas fa-calculator"></i>
            </div>
            <div class="value"><?= $formattedAvgPagu ?></div>
            <div class="label">Rata-rata Pagu</div>
            <div class="subtitle">Per Paket</div>
        </div>

        <div class="summary-card warning">
            <div class="icon">
                <i class="fas fa-building"></i>
            </div>
            <div class="value"><?= number_format($totalKlpd, 0, ',', '.') ?></div>
            <div class="label">Total KLPD</div>
            <div class="subtitle">Unit Kerja</div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Data Table -->
    <div class="card data-table">
        <div class="table-header d-flex justify-content-between align-items-center flex-wrap">
            <h3><i class="fas fa-table"></i> Data E-Purchasing</h3>
            <div class="text-muted">
                <?php if ($data && isset($data['data']) && is_array($data['data'])): ?>
                    Menampilkan <strong><?= count($data['data']) ?></strong> dari 
                    <strong><?= number_format($totalRecords, 0, ',', '.') ?></strong> total data
                    <br><small>Halaman <?= $currentPage ?> dari <?= $totalPages ?></small>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($data && isset($data['success']) && $data['success'] && !empty($data['data'])): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 80px;">No</th>
                            <th style="width: 100px;">Tahun</th>
                            <th style="width: 300px;">Nama Paket</th>
                            <th style="width: 150px;">Total Harga</th>
                            <th style="width: 200px;">Satuan Kerja</th>
                            <th style="width: 120px;">KLPD</th>
                            <th style="width: 120px;">Status</th>
                            <th style="width: 120px;">Tanggal Buat</th>
                            <th style="width: 100px;">Kuantitas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['data'] as $index => $row): ?>
                            <?php $rowNumber = (($currentPage - 1) * $limit) + $index + 1; ?>
                            <tr>
                                <td><strong><?= $rowNumber ?></strong></td>
                                <td><span class="badge bg-primary"><?= htmlspecialchars($row['tahun_anggaran'] ?? '-') ?></span></td>
                                <td>
                                    <div class="fw-bold text-dark mb-1"><?= htmlspecialchars($row['nama_paket'] ?? $row['nama_pengadaan'] ?? '-') ?></div>
                                    <small class="text-muted">
                                        <i class="fas fa-hashtag"></i> <?= htmlspecialchars($row['no_paket'] ?? $row['id'] ?? '-') ?><br>
                                        <i class="fas fa-code"></i> <?= htmlspecialchars($row['kode_anggaran'] ?? '-') ?>
                                    </small>
                                </td>
                                <td class="price">
                                    <?= isset($row['formatted_total_harga']) ? 
                                        htmlspecialchars($row['formatted_total_harga']) : 
                                        'Rp ' . number_format($row['total_harga'] ?? $row['nilai_pengadaan'] ?? 0, 0, ',', '.') ?>
                                </td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($row['nama_satker'] ?? $row['instansi'] ?? '-') ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($row['alamat_satker'] ?? $row['deskripsi'] ?? '-') ?></small>
                                </td>
                                <td><span class="badge bg-info"><?= htmlspecialchars($row['kd_klpd'] ?? '-') ?></span></td>
                                <td>
                                    <?php 
                                        $status = $row['status_paket'] ?? $row['status'] ?? '-';
                                        $badgeClass = 'secondary';
                                        if (stripos($status, 'selesai') !== false || stripos($status, 'complete') !== false) {
                                            $badgeClass = 'success';
                                        } elseif (stripos($status, 'proses') !== false || stripos($status, 'progress') !== false) {
                                            $badgeClass = 'warning';
                                        } elseif (stripos($status, 'batal') !== false || stripos($status, 'cancel') !== false) {
                                            $badgeClass = 'danger';
                                        }
                                    ?>
                                    <span class="badge bg-<?= $badgeClass ?>"><?= htmlspecialchars($status) ?></span>
                                </td>
                                <td><small><?= htmlspecialchars($row['formatted_tanggal_buat'] ?? ($row['tanggal_pengadaan'] ? date('d/m/Y', strtotime($row['tanggal_pengadaan'])) : '-')) ?></small></td>
                                <td><strong><?= number_format($row['kuantitas'] ?? 1, 0, ',', '.') ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Pagination" class="p-3">
                    <ul class="pagination">
                        <!-- Previous Button -->
                        <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                            <?php 
                                $prevParams = $_GET;
                                $prevParams['page'] = max(1, $currentPage - 1);
                                $prevQuery = http_build_query(array_filter($prevParams));
                            ?>
                            <a class="page-link" href="?<?= $prevQuery ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>

                        <!-- Page Numbers -->
                        <?php 
                            $startPage = max(1, $currentPage - 2);
                            $endPage = min($totalPages, $currentPage + 2);
                            
                            // Show first page if not in range
                            if ($startPage > 1): ?>
                                <li class="page-item">
                                    <?php 
                                        $firstParams = $_GET;
                                        $firstParams['page'] = 1;
                                        $firstQuery = http_build_query(array_filter($firstParams));
                                    ?>
                                    <a class="page-link" href="?<?= $firstQuery ?>">1</a>
                                </li>
                                <?php if ($startPage > 2): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                <?php endif; ?>
                            <?php endif;
                            
                            // Show page range
                            for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                                    <?php 
                                        $pageParams = $_GET;
                                        $pageParams['page'] = $i;
                                        $pageQuery = http_build_query(array_filter($pageParams));
                                    ?>
                                    <a class="page-link" href="?<?= $pageQuery ?>"><?= $i ?></a>
                                </li>
                            <?php endfor;
                            
                            // Show last page if not in range
                            if ($endPage < $totalPages): ?>
                                <?php if ($endPage < $totalPages - 1): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                <?php endif; ?>
                                <li class="page-item">
                                    <?php 
                                        $lastParams = $_GET;
                                        $lastParams['page'] = $totalPages;
                                        $lastQuery = http_build_query(array_filter($lastParams));
                                    ?>
                                    <a class="page-link" href="?<?= $lastQuery ?>"><?= $totalPages ?></a>
                                </li>
                            <?php endif; ?>

                        <!-- Next Button -->
                        <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                            <?php 
                                $nextParams = $_GET;
                                $nextParams['page'] = min($totalPages, $currentPage + 1);
                                $nextQuery = http_build_query(array_filter($nextParams));
                            ?>
                            <a class="page-link" href="?<?= $nextQuery ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>

        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-search-minus"></i>
                <h4>Tidak ada data ditemukan</h4>
                <p>Coba ubah kriteria filter atau hapus beberapa filter untuk melihat lebih banyak data</p>
                <?php if (!$pengadaanModel): ?>
                    <small class="text-muted">
                        <i class="fas fa-exclamation-triangle"></i> 
                        Model database tidak tersedia. Pastikan tabel 'pengadaan' sudah dibuat dan konfigurasi database benar.
                    </small>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Date range validation
        const tanggalAwal = document.querySelector('input[name="tanggal_awal"]');
        const tanggalAkhir = document.querySelector('input[name="tanggal_akhir"]');

        if (tanggalAwal && tanggalAkhir) {
            tanggalAwal.addEventListener('change', function() {
                if (tanggalAkhir.value && this.value > tanggalAkhir.value) {
                    tanggalAkhir.value = this.value;
                }
                tanggalAkhir.min = this.value;
            });

            tanggalAkhir.addEventListener('change', function() {
                if (tanggalAwal.value && this.value < tanggalAwal.value) {
                    tanggalAwal.value = this.value;
                }
                tanggalAwal.max = this.value;
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

        // Auto-submit on select change (optional)
        const autoSubmitSelects = document.querySelectorAll('select[name="limit"]');
        autoSubmitSelects.forEach(select => {
            select.addEventListener('change', function() {
                // Remove page parameter when changing limit
                const form = this.form;
                const pageInput = form.querySelector('input[name="page"]');
                if (pageInput) pageInput.remove();
                
                form.submit();
            });
        });

        // Form validation
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const minTotal = parseFloat(form.querySelector('input[name="min_total"]').value) || 0;
                const maxTotal = parseFloat(form.querySelector('input[name="max_total"]').value) || 0;

                if (minTotal > 0 && maxTotal > 0 && minTotal > maxTotal) {
                    e.preventDefault();
                    alert('Nilai minimum tidak boleh lebih besar dari nilai maksimum!');
                    return false;
                }

                // Show loading state
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
                    submitBtn.disabled = true;

                    // Restore button after 5 seconds if no redirect happens
                    setTimeout(() => {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }, 5000);
                }
            });
        }

        // Table row hover effects
        document.querySelectorAll('tbody tr').forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
            });

            row.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    });

    // Export function
    function exportData(format = 'csv') {
        const currentParams = new URLSearchParams(window.location.search);
        currentParams.delete('page');
        currentParams.set('action', 'export');
        currentParams.set('format', format);
        
        const exportUrl = `api/epurchasing.php?${currentParams.toString()}`;
        window.open(exportUrl, '_blank');
    }

    // Print function
    function printTable() {
        const printContent = document.querySelector('.data-table').innerHTML;
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
                <head>
                    <title>Data E-Purchasing - <?= date('Y-m-d H:i:s') ?></title>
                    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
                    <style>
                        body { font-size: 12px; }
                        .table th { background: #2c3e50 !important; color: white !important; }
                        @media print {
                            .no-print { display: none !important; }
                            .table { font-size: 10px; }
                        }
                    </style>
                </head>
                <body>
                    <div class="container-fluid">
                        <h3>Data E-Purchasing</h3>
                        <p>Dicetak pada: <?= date('d/m/Y H:i:s') ?></p>
                        ${printContent}
                    </div>
                    <script>window.print(); window.onafterprint = function(){ window.close(); }</script>
                </body>
            </html>
        `);
        printWindow.document.close();
    }
</script>

<?php
// Include footer
if (file_exists('../../navbar/footer.php')) {
    include '../../navbar/footer.php';
}
?>