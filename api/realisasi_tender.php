<?php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $table_name = 'realisasi_tender'; // Sesuaikan dengan nama tabel Anda

    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? 'list';

    switch ($method) {
        case 'GET':
            switch ($action) {
                case 'list':
                    // Get filters from query parameters
                    $filters = [];
                    $whereConditions = [];
                    $params = [];

                    // Build base query
                    $query = "SELECT 
                        id,
                        Tahun_Anggaran,
                        Kode_Tender,
                        Nama_Paket,
                        Kode_RUP,
                        KLPD,
                        Nama_Satker,
                        Jenis_Pengadaan,
                        Metode_Pengadaan,
                        Nilai_Pagu,
                        Nilai_HPS,
                        Nama_Pemenang,
                        Nilai_Kontrak,
                        Nilai_PDN,
                        Nilai_UMK,
                        Sumber_Dana,
                        Jenis_Kontrak,
                        Tahap
                    FROM " . $table_name;

                    // Apply filters
                    if (!empty($_GET['tahun'])) {
                        $whereConditions[] = "Tahun_Anggaran = :tahun";
                        $params[':tahun'] = $_GET['tahun'];
                    }

                    if (!empty($_GET['jenis_pengadaan'])) {
                        $whereConditions[] = "Jenis_Pengadaan = :jenis_pengadaan";
                        $params[':jenis_pengadaan'] = $_GET['jenis_pengadaan'];
                    }

                    if (!empty($_GET['klpd'])) {
                        $whereConditions[] = "KLPD LIKE :klpd";
                        $params[':klpd'] = '%' . $_GET['klpd'] . '%';
                    }

                    if (!empty($_GET['metode'])) {
                        $whereConditions[] = "Metode_Pengadaan = :metode";
                        $params[':metode'] = $_GET['metode'];
                    }

                    if (!empty($_GET['search'])) {
                        $whereConditions[] = "(Nama_Paket LIKE :search OR KLPD LIKE :search2 OR Nama_Satker LIKE :search3)";
                        $params[':search'] = '%' . $_GET['search'] . '%';
                        $params[':search2'] = '%' . $_GET['search'] . '%';
                        $params[':search3'] = '%' . $_GET['search'] . '%';
                    }

                    if (!empty($whereConditions)) {
                        $query .= " WHERE " . implode(" AND ", $whereConditions);
                    }

                    // Pagination
                    $page = intval($_GET['page'] ?? 1);
                    $limit = intval($_GET['limit'] ?? 100);
                    $offset = ($page - 1) * $limit;

                    $query .= " ORDER BY id DESC LIMIT :limit OFFSET :offset";

                    $stmt = $db->prepare($query);
                    
                    foreach ($params as $key => $value) {
                        $stmt->bindValue($key, $value);
                    }
                    
                    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

                    $stmt->execute();   
                    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    // Get total count
                    $countQuery = "SELECT COUNT(*) as total FROM " . $table_name;
                    if (!empty($whereConditions)) {
                        $countQuery .= " WHERE " . implode(" AND ", $whereConditions);
                    }

                    $countStmt = $db->prepare($countQuery);
                    foreach ($params as $key => $value) {
                        if ($key !== ':limit' && $key !== ':offset') {
                            $countStmt->bindValue($key, $value);
                        }
                    }
                    $countStmt->execute();
                    $totalResult = $countStmt->fetch(PDO::FETCH_ASSOC);
                    $total = $totalResult['total'];
                    $totalPages = ceil($total / $limit);

                    // Add row numbers and format currency
                    foreach ($data as $key => $row) {
                        $data[$key]['No'] = $offset + $key + 1;
                        
                        // Format currency values safely
                        if (isset($row['Nilai_Pagu']) && !empty($row['Nilai_Pagu'])) {
                            $paguValue = preg_replace('/[^\d]/', '', $row['Nilai_Pagu']);
                            $data[$key]['Nilai_Pagu_Formatted'] = 'Rp ' . number_format(intval($paguValue), 0, ',', '.');
                        }
                        
                        if (isset($row['Nilai_HPS']) && !empty($row['Nilai_HPS'])) {
                            $hpsValue = preg_replace('/[^\d]/', '', $row['Nilai_HPS']);
                            $data[$key]['Nilai_HPS_Formatted'] = 'Rp ' . number_format(intval($hpsValue), 0, ',', '.');
                        }
                        
                        if (isset($row['Nilai_Kontrak']) && !empty($row['Nilai_Kontrak'])) {
                            $kontrakValue = preg_replace('/[^\d]/', '', $row['Nilai_Kontrak']);
                            $data[$key]['Nilai_Kontrak_Formatted'] = 'Rp ' . number_format(intval($kontrakValue), 0, ',', '.');
                        }
                    }

                    echo json_encode([
                        'success' => true,
                        'data' => $data,
                        'pagination' => [
                            'current_page' => $page,
                            'total_pages' => $totalPages,
                            'total_records' => $total,
                            'per_page' => $limit,
                            'has_next' => $page < $totalPages,
                            'has_prev' => $page > 1
                        ]
                    ]);
                    break;

                case 'options':
                    // Get distinct values for dropdowns
                    $options = [];
                    
                    // Get Jenis Pengadaan
                    $stmt = $db->prepare("SELECT DISTINCT Jenis_Pengadaan FROM " . $table_name . " WHERE Jenis_Pengadaan IS NOT NULL ORDER BY Jenis_Pengadaan");
                    $stmt->execute();
                    $options['jenis_pengadaan'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

                    // Get KLPD
                    $stmt = $db->prepare("SELECT DISTINCT KLPD FROM " . $table_name . " WHERE KLPD IS NOT NULL ORDER BY KLPD");
                    $stmt->execute();
                    $options['klpd'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

                    // Get Metode
                    $stmt = $db->prepare("SELECT DISTINCT Metode_Pengadaan FROM " . $table_name . " WHERE Metode_Pengadaan IS NOT NULL ORDER BY Metode_Pengadaan");
                    $stmt->execute();
                    $options['metode'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

                    // Get Years
                    $stmt = $db->prepare("SELECT DISTINCT Tahun_Anggaran FROM " . $table_name . " WHERE Tahun_Anggaran IS NOT NULL ORDER BY Tahun_Anggaran DESC");
                    $stmt->execute();
                    $options['years'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

                    echo json_encode([
                        'success' => true,
                        'options' => $options
                    ]);
                    break;

                case 'summary':
                    $filters = [];
                    $whereConditions = [];
                    $params = [];

                    // Apply same filters as list
                    if (!empty($_GET['tahun'])) {
                        $whereConditions[] = "Tahun_Anggaran = :tahun";
                        $params[':tahun'] = $_GET['tahun'];
                    }

                    if (!empty($_GET['jenis_pengadaan'])) {
                        $whereConditions[] = "Jenis_Pengadaan = :jenis_pengadaan";
                        $params[':jenis_pengadaan'] = $_GET['jenis_pengadaan'];
                    }

                    if (!empty($_GET['klpd'])) {
                        $whereConditions[] = "KLPD LIKE :klpd";
                        $params[':klpd'] = '%' . $_GET['klpd'] . '%';
                    }

                    $whereClause = !empty($whereConditions) ? " WHERE " . implode(" AND ", $whereConditions) : "";

                    // Get basic statistics
                    $query = "SELECT 
                        COUNT(*) as total_paket,
                        COUNT(DISTINCT KLPD) as total_klpd,
                        COUNT(DISTINCT Nama_Satker) as total_satker
                    FROM " . $table_name . $whereClause;

                    $stmt = $db->prepare($query);
                    foreach ($params as $key => $value) {
                        $stmt->bindValue($key, $value);
                    }
                    $stmt->execute();
                    $basicStats = $stmt->fetch(PDO::FETCH_ASSOC);

                    // Get all data for detailed calculations
                    $dataQuery = "SELECT Nilai_Pagu, Jenis_Pengadaan, KLPD, Metode_Pengadaan 
                                  FROM " . $table_name . $whereClause;
                    
                    $dataStmt = $db->prepare($dataQuery);
                    foreach ($params as $key => $value) {
                        $dataStmt->bindValue($key, $value);
                    }
                    $dataStmt->execute();
                    $allData = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

                    $totalPagu = 0;
                    $jenisPengadaanStats = [];
                    $klpdStats = [];
                    $metodeStats = [];

                    foreach ($allData as $row) {
                        // Calculate pagu
                        if (!empty($row['Nilai_Pagu'])) {
                            $paguValue = preg_replace('/[^\d]/', '', $row['Nilai_Pagu']);
                            $paguValue = intval($paguValue);
                            $totalPagu += $paguValue;

                            // Group by Jenis Pengadaan
                            $jenis = $row['Jenis_Pengadaan'] ?? 'Tidak Diketahui';
                            if (!isset($jenisPengadaanStats[$jenis])) {
                                $jenisPengadaanStats[$jenis] = ['count' => 0, 'total_pagu' => 0];
                            }
                            $jenisPengadaanStats[$jenis]['count']++;
                            $jenisPengadaanStats[$jenis]['total_pagu'] += $paguValue;

                            // Group by KLPD
                            $klpd = $row['KLPD'] ?? 'Tidak Diketahui';
                            if (!isset($klpdStats[$klpd])) {
                                $klpdStats[$klpd] = ['count' => 0, 'total_pagu' => 0];
                            }
                            $klpdStats[$klpd]['count']++;
                            $klpdStats[$klpd]['total_pagu'] += $paguValue;

                            // Group by Metode
                            $metode = $row['Metode_Pengadaan'] ?? 'Tidak Diketahui';
                            if (!isset($metodeStats[$metode])) {
                                $metodeStats[$metode] = ['count' => 0, 'total_pagu' => 0];
                            }
                            $metodeStats[$metode]['count']++;
                            $metodeStats[$metode]['total_pagu'] += $paguValue;
                        }
                    }

                    // Sort by total pagu descending
                    uasort($jenisPengadaanStats, function($a, $b) {
                        return $b['total_pagu'] - $a['total_pagu'];
                    });
                    uasort($klpdStats, function($a, $b) {
                        return $b['total_pagu'] - $a['total_pagu'];
                    });
                    uasort($metodeStats, function($a, $b) {
                        return $b['total_pagu'] - $a['total_pagu'];
                    });

                    $avgPagu = $basicStats['total_paket'] > 0 ? $totalPagu / $basicStats['total_paket'] : 0;

                    echo json_encode([
                        'success' => true,
                        'summary' => [
                            'total_paket' => $basicStats['total_paket'],
                            'total_pagu' => $totalPagu,
                            'avg_pagu' => $avgPagu,
                            'total_klpd' => $basicStats['total_klpd'],
                            'total_satker' => $basicStats['total_satker'],
                            'breakdown' => [
                                'jenis_pengadaan' => $jenisPengadaanStats,
                                'klpd' => array_slice($klpdStats, 0, 10, true), // Top 10
                                'metode' => $metodeStats
                            ]
                        ]
                    ]);
                    break;

                case 'export':
                    // Simple export functionality
                    $whereConditions = [];
                    $params = [];

                    if (!empty($_GET['tahun'])) {
                        $whereConditions[] = "Tahun_Anggaran = :tahun";
                        $params[':tahun'] = $_GET['tahun'];
                    }

                    $query = "SELECT * FROM " . $table_name;
                    if (!empty($whereConditions)) {
                        $query .= " WHERE " . implode(" AND ", $whereConditions);
                    }
                    $query .= " ORDER BY id DESC LIMIT 1000";

                    $stmt = $db->prepare($query);
                    foreach ($params as $key => $value) {
                        $stmt->bindValue($key, $value);
                    }
                    $stmt->execute();
                    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    header('Content-Type: text/csv; charset=utf-8');
                    header('Content-Disposition: attachment; filename="data_pengadaan_' . date('Y-m-d') . '.csv"');

                    $output = fopen('php://output', 'w');
                    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

                    if (!empty($data)) {
                        // Headers
                        fputcsv($output, array_keys($data[0]));
                        
                        // Data
                        foreach ($data as $row) {
                            fputcsv($output, $row);
                        }
                    }

                    fclose($output);
                    exit;
                    break;

                default:
                    echo json_encode([
                        'success' => false,
                        'message' => 'Invalid action'
                    ]);
                    break;
            }
            break;

        default:
            echo json_encode([
                'success' => false,
                'message' => 'Method not allowed'
            ]);
            break;
    }
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(),
        'error_details' => $e->getFile() . ':' . $e->getLine()
    ]);
}
?>