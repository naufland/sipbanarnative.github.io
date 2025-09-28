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
    
    // Sesuaikan nama tabel - PENTING: Ganti dengan nama tabel yang benar
    $table_name = 'realisasi_tender'; 

    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? 'list';

    switch ($method) {
        case 'GET':
            switch ($action) {
                case 'list':
                    // Build base query
                    $baseQuery = "FROM " . $table_name;
                    $whereConditions = [];
                    $params = [];

                    // Apply filters dengan named parameters
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
                        $whereConditions[] = "(Nama_Paket LIKE :search OR KLPD LIKE :search OR Nama_Satker LIKE :search)";
                        $params[':search'] = '%' . $_GET['search'] . '%';
                    }

                    $whereClause = "";
                    if (!empty($whereConditions)) {
                        $whereClause = " WHERE " . implode(" AND ", $whereConditions);
                    }

                    // Get total count first
                    $countQuery = "SELECT COUNT(*) as total " . $baseQuery . $whereClause;
                    $countStmt = $db->prepare($countQuery);
                    foreach ($params as $key => $value) {
                        $countStmt->bindValue($key, $value);
                    }
                    $countStmt->execute();
                    $totalResult = $countStmt->fetch(PDO::FETCH_ASSOC);
                    $total = $totalResult['total'];

                    // Pagination
                    $page = max(1, intval($_GET['page'] ?? 1));
                    $limit = max(1, min(1000, intval($_GET['limit'] ?? 100))); // Max 1000 per page
                    $offset = ($page - 1) * $limit;
                    $totalPages = ceil($total / $limit);

                    // Main data query
                    $dataQuery = "SELECT 
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
                    " . $baseQuery . $whereClause . " LIMIT " . intval($limit) . " OFFSET " . intval($offset);

                    $stmt = $db->prepare($dataQuery);
                    foreach ($params as $key => $value) {
                        $stmt->bindValue($key, $value);
                    }
                    $stmt->execute();
                    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    // Add row numbers and format currency
                    foreach ($data as $key => $row) {
                        $data[$key]['No'] = $offset + $key + 1;
                        
                        // Format currency values
                        $data[$key]['Nilai_Pagu_Formatted'] = formatCurrency($row['Nilai_Pagu'] ?? '0');
                        $data[$key]['Nilai_HPS_Formatted'] = formatCurrency($row['Nilai_HPS'] ?? '0');
                        $data[$key]['Nilai_Kontrak_Formatted'] = formatCurrency($row['Nilai_Kontrak'] ?? '0');
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
                    $options = [];
                    
                    try {
                        // Get Jenis Pengadaan
                        $stmt = $db->query("SELECT DISTINCT Jenis_Pengadaan FROM " . $table_name . " WHERE Jenis_Pengadaan IS NOT NULL AND Jenis_Pengadaan != '' ORDER BY Jenis_Pengadaan LIMIT 50");
                        $options['jenis_pengadaan'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

                        // Get KLPD (limit untuk performance)
                        $stmt = $db->query("SELECT DISTINCT KLPD FROM " . $table_name . " WHERE KLPD IS NOT NULL AND KLPD != '' ORDER BY KLPD LIMIT 100");
                        $options['klpd'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

                        // Get Metode
                        $stmt = $db->query("SELECT DISTINCT Metode_Pengadaan FROM " . $table_name . " WHERE Metode_Pengadaan IS NOT NULL AND Metode_Pengadaan != '' ORDER BY Metode_Pengadaan LIMIT 50");
                        $options['metode'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

                        // Get Years
                        $stmt = $db->query("SELECT DISTINCT Tahun_Anggaran FROM " . $table_name . " WHERE Tahun_Anggaran IS NOT NULL ORDER BY Tahun_Anggaran DESC LIMIT 10");
                        $options['years'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

                        // Get Jenis Kontrak
                        $stmt = $db->query("SELECT DISTINCT Jenis_Kontrak FROM " . $table_name . " WHERE Jenis_Kontrak IS NOT NULL AND Jenis_Kontrak != '' ORDER BY Jenis_Kontrak LIMIT 20");
                        $options['jenis_kontrak'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

                        echo json_encode([
                            'success' => true,
                            'options' => $options
                        ]);
                    } catch (Exception $e) {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Error getting options: ' . $e->getMessage()
                        ]);
                    }
                    break;

                case 'summary':
                    $whereConditions = [];
                    $params = [];

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

                    $whereClause = !empty($whereConditions) ? " WHERE " . implode(" AND ", $whereConditions) : "";

                    // Get basic statistics
                    $basicQuery = "SELECT 
                        COUNT(*) as total_paket,
                        COUNT(DISTINCT KLPD) as total_klpd,
                        COUNT(DISTINCT Nama_Satker) as total_satker
                    FROM " . $table_name . $whereClause;

                    $stmt = $db->prepare($basicQuery);
                    foreach ($params as $key => $value) {
                        $stmt->bindValue($key, $value);
                    }
                    $stmt->execute();
                    $basicStats = $stmt->fetch(PDO::FETCH_ASSOC);

                    // Get sample data for value calculations (limit to avoid memory issues)
                    $sampleQuery = "SELECT 
                        Nilai_Pagu, Nilai_HPS, Nilai_Kontrak, 
                        Jenis_Pengadaan, KLPD, Metode_Pengadaan 
                    FROM " . $table_name . $whereClause . " LIMIT 3000";
                    
                    $sampleStmt = $db->prepare($sampleQuery);
                    foreach ($params as $key => $value) {
                        $sampleStmt->bindValue($key, $value);
                    }
                    $sampleStmt->execute();
                    $sampleData = $sampleStmt->fetchAll(PDO::FETCH_ASSOC);

                    $totalPagu = 0;
                    $totalHPS = 0;
                    $totalKontrak = 0;
                    $validPaguCount = 0;
                    $jenisPengadaanStats = [];
                    $klpdStats = [];
                    $metodeStats = [];

                    foreach ($sampleData as $row) {
                        $paguValue = parseNumericValue($row['Nilai_Pagu'] ?? '0');
                        $hpsValue = parseNumericValue($row['Nilai_HPS'] ?? '0');
                        $kontrakValue = parseNumericValue($row['Nilai_Kontrak'] ?? '0');
                        
                        if ($paguValue > 0) {
                            $totalPagu += $paguValue;
                            $validPaguCount++;
                        }
                        $totalHPS += $hpsValue;
                        $totalKontrak += $kontrakValue;

                        // Group statistics
                        $jenis = $row['Jenis_Pengadaan'] ?? 'Tidak Diketahui';
                        if (!isset($jenisPengadaanStats[$jenis])) {
                            $jenisPengadaanStats[$jenis] = ['count' => 0, 'total_pagu' => 0];
                        }
                        $jenisPengadaanStats[$jenis]['count']++;
                        $jenisPengadaanStats[$jenis]['total_pagu'] += $paguValue;

                        $klpd = $row['KLPD'] ?? 'Tidak Diketahui';
                        if (!isset($klpdStats[$klpd])) {
                            $klpdStats[$klpd] = ['count' => 0, 'total_pagu' => 0];
                        }
                        $klpdStats[$klpd]['count']++;
                        $klpdStats[$klpd]['total_pagu'] += $paguValue;

                        $metode = $row['Metode_Pengadaan'] ?? 'Tidak Diketahui';
                        if (!isset($metodeStats[$metode])) {
                            $metodeStats[$metode] = ['count' => 0, 'total_pagu' => 0];
                        }
                        $metodeStats[$metode]['count']++;
                        $metodeStats[$metode]['total_pagu'] += $paguValue;
                    }

                    // Sort by total pagu
                    uasort($jenisPengadaanStats, function($a, $b) { return $b['total_pagu'] - $a['total_pagu']; });
                    uasort($klpdStats, function($a, $b) { return $b['total_pagu'] - $a['total_pagu']; });
                    uasort($metodeStats, function($a, $b) { return $b['total_pagu'] - $a['total_pagu']; });

                    $avgPagu = $validPaguCount > 0 ? $totalPagu / $validPaguCount : 0;

                    echo json_encode([
                        'success' => true,
                        'summary' => [
                            'total_paket' => intval($basicStats['total_paket']),
                            'total_pagu' => $totalPagu,
                            'total_hps' => $totalHPS,
                            'total_kontrak' => $totalKontrak,
                            'avg_pagu' => $avgPagu,
                            'total_klpd' => intval($basicStats['total_klpd']),
                            'total_satker' => intval($basicStats['total_satker']),
                            'breakdown' => [
                                'jenis_pengadaan' => $jenisPengadaanStats,
                                'klpd' => array_slice($klpdStats, 0, 15, true),
                                'metode' => $metodeStats
                            ]
                        ]
                    ]);
                    break;

                case 'export':
                    $whereConditions = [];
                    $params = [];

                    if (!empty($_GET['tahun'])) {
                        $whereConditions[] = "Tahun_Anggaran = :tahun";
                        $params[':tahun'] = $_GET['tahun'];
                    }

                    if (!empty($_GET['klpd'])) {
                        $whereConditions[] = "KLPD LIKE :klpd";
                        $params[':klpd'] = '%' . $_GET['klpd'] . '%';
                    }

                    $whereClause = !empty($whereConditions) ? " WHERE " . implode(" AND ", $whereConditions) : "";
                    
                    $query = "SELECT * FROM " . $table_name . $whereClause . " LIMIT 1000";

                    $stmt = $db->prepare($query);
                    foreach ($params as $key => $value) {
                        $stmt->bindValue($key, $value);
                    }
                    $stmt->execute();
                    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    header('Content-Type: text/csv; charset=utf-8');
                    header('Content-Disposition: attachment; filename="realisasi_tender_' . date('Y-m-d') . '.csv"');

                    $output = fopen('php://output', 'w');
                    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

                    if (!empty($data)) {
                        fputcsv($output, array_keys($data[0]));
                        foreach ($data as $row) {
                            fputcsv($output, $row);
                        }
                    }

                    fclose($output);
                    exit;
                    break;

                case 'test':
                    // Test endpoint untuk debugging
                    $result = $db->query("SELECT COUNT(*) as count FROM " . $table_name . " LIMIT 1");
                    $count = $result->fetch(PDO::FETCH_ASSOC);
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Connection successful',
                        'table_name' => $table_name,
                        'record_count' => $count['count']
                    ]);
                    break;

                default:
                    echo json_encode([
                        'success' => false,
                        'message' => 'Invalid action. Available: list, options, summary, export, test'
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

// Helper functions
function parseNumericValue($value) {
    if (empty($value) || $value === null) return 0;
    
    // Remove all non-numeric characters except dots and commas
    $cleanValue = preg_replace('/[^\d.,]/', '', strval($value));
    
    if (empty($cleanValue)) return 0;
    
    // Handle Indonesian number format
    $cleanValue = str_replace(',', '.', $cleanValue);
    
    // Remove extra dots (keep only the last one as decimal separator)
    $parts = explode('.', $cleanValue);
    if (count($parts) > 2) {
        $integer = implode('', array_slice($parts, 0, -1));
        $decimal = end($parts);
        $cleanValue = $integer . '.' . $decimal;
    }
    
    return floatval($cleanValue);
}

function formatCurrency($value) {
    $numericValue = parseNumericValue($value);
    if ($numericValue == 0) return 'Rp 0';
    
    return 'Rp ' . number_format($numericValue, 0, ',', '.');
}
?>