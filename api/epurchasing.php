<?php
// =================================================================
// == epurchasing.php (API) - REVISI DENGAN FILTER BULAN & TAHUN ==
// =================================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../includes/RealisasiEpurchasingModel.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $epurchasing = new EpurchasingModel($db);
    
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? 'list';

    switch ($method) {
        case 'GET':
            switch ($action) {
                // ==========================================================
                // == LIST DATA =============================================
                // ==========================================================
                case 'list':
                    $filters = [
                        'bulan' => $_GET['bulan'] ?? '',
                        'tahun' => $_GET['tahun'] ?? '',
                        'tahun_anggaran' => $_GET['tahun_anggaran'] ?? '',
                        'kode_anggaran' => $_GET['kode_anggaran'] ?? '',
                        'kd_produk' => $_GET['kd_produk'] ?? '',
                        'kd_penyedia' => $_GET['kd_penyedia'] ?? '',
                        'status_paket' => $_GET['status_paket'] ?? '',
                        'search' => $_GET['search'] ?? ''
                    ];

                    $filters = array_filter($filters, fn($v) => $v !== '' && $v !== null);

                    $page = intval($_GET['page'] ?? 1);
                    $limit = intval($_GET['limit'] ?? 50);
                    $offset = ($page - 1) * $limit;

                    $data = $epurchasing->getPaketData($filters, $limit, $offset);
                    $total = $epurchasing->getTotalCount($filters);
                    $totalPages = ceil($total / $limit);

                    // Pastikan $data adalah array
                    if (!is_array($data)) {
                        $data = [];
                    }

                    // Proses data dengan nomor urut dan total keseluruhan
                    $processedData = [];
                    foreach ($data as $index => $row) {
                        $row['Row_Number'] = $offset + $index + 1;
                        
                        // Hitung total keseluruhan
                        $kuantitas = (float)($row['kuantitas'] ?? 0);
                        $harga_satuan = (float)($row['harga_satuan'] ?? 0);
                        $ongkos_kirim = (float)($row['ongkos_kirim'] ?? 0);
                        $row['total_keseluruhan'] = ($kuantitas * $harga_satuan) + $ongkos_kirim;
                        
                        $processedData[] = $row;
                    }

                    echo json_encode([
                        'success' => true,
                        'data' => $processedData,
                        'pagination' => [
                            'current_page' => $page,
                            'total_pages' => $totalPages,
                            'total_records' => $total,
                            'per_page' => $limit,
                            'has_next' => $page < $totalPages,
                            'has_prev' => $page > 1
                        ],
                        'filters_applied' => $filters
                    ], JSON_UNESCAPED_UNICODE);
                    break;

                // ==========================================================
                // == SUMMARY ===============================================
                // ==========================================================
                case 'summary':
                    $filters = [
                        'bulan' => $_GET['bulan'] ?? '',
                        'tahun' => $_GET['tahun'] ?? '',
                        'tahun_anggaran' => $_GET['tahun_anggaran'] ?? '',
                        'kode_anggaran' => $_GET['kode_anggaran'] ?? '',
                        'kd_produk' => $_GET['kd_produk'] ?? '',
                        'kd_penyedia' => $_GET['kd_penyedia'] ?? '',
                        'status_paket' => $_GET['status_paket'] ?? '',
                        'search' => $_GET['search'] ?? ''
                    ];
                    $filters = array_filter($filters);

                    $allData = $epurchasing->getAllDataForSummary($filters);
                    $totalRecords = count($allData);
                    
                    $totalNilai = 0;
                    $totalKuantitas = 0;
                    $tahunAnggaranStats = [];
                    $kodeAnggaranStats = [];
                    $produkStats = [];
                    $penyediaStats = [];
                    $statusPaketStats = [];
                    
                    foreach ($allData as $row) {
                        $kuantitas = (float)($row['kuantitas'] ?? 0);
                        $hargaSatuan = (float)($row['harga_satuan'] ?? 0);
                        $ongkosKirim = (float)($row['ongkos_kirim'] ?? 0);
                        $totalKeseluruhan = ($kuantitas * $hargaSatuan) + $ongkosKirim;

                        $totalNilai += $totalKeseluruhan;
                        $totalKuantitas += $kuantitas;

                        // Breakdown Tahun Anggaran
                        $tahun = $row['tahun_anggaran'] ?? 'Tidak Diketahui';
                        if (!isset($tahunAnggaranStats[$tahun])) {
                            $tahunAnggaranStats[$tahun] = ['count' => 0, 'total_nilai' => 0, 'total_kuantitas' => 0];
                        }
                        $tahunAnggaranStats[$tahun]['count']++;
                        $tahunAnggaranStats[$tahun]['total_nilai'] += $totalKeseluruhan;
                        $tahunAnggaranStats[$tahun]['total_kuantitas'] += $kuantitas;

                        // Breakdown Kode Anggaran
                        $kodeAnggaran = $row['kode_anggaran'] ?? 'Tidak Diketahui';
                        if (!isset($kodeAnggaranStats[$kodeAnggaran])) {
                            $kodeAnggaranStats[$kodeAnggaran] = ['count' => 0, 'total_nilai' => 0, 'total_kuantitas' => 0];
                        }
                        $kodeAnggaranStats[$kodeAnggaran]['count']++;
                        $kodeAnggaranStats[$kodeAnggaran]['total_nilai'] += $totalKeseluruhan;
                        $kodeAnggaranStats[$kodeAnggaran]['total_kuantitas'] += $kuantitas;

                        // Breakdown Produk
                        $produk = $row['kd_produk'] ?? 'Tidak Diketahui';
                        if (!isset($produkStats[$produk])) {
                            $produkStats[$produk] = ['count' => 0, 'total_nilai' => 0, 'total_kuantitas' => 0];
                        }
                        $produkStats[$produk]['count']++;
                        $produkStats[$produk]['total_nilai'] += $totalKeseluruhan;
                        $produkStats[$produk]['total_kuantitas'] += $kuantitas;

                        // Breakdown Penyedia
                        $penyedia = $row['kd_penyedia'] ?? 'Tidak Diketahui';
                        if (!isset($penyediaStats[$penyedia])) {
                            $penyediaStats[$penyedia] = ['count' => 0, 'total_nilai' => 0, 'total_kuantitas' => 0];
                        }
                        $penyediaStats[$penyedia]['count']++;
                        $penyediaStats[$penyedia]['total_nilai'] += $totalKeseluruhan;
                        $penyediaStats[$penyedia]['total_kuantitas'] += $kuantitas;

                        // Breakdown Status Paket
                        $status = $row['status_paket'] ?? 'Tidak Diketahui';
                        if (!isset($statusPaketStats[$status])) {
                            $statusPaketStats[$status] = ['count' => 0, 'total_nilai' => 0, 'total_kuantitas' => 0];
                        }
                        $statusPaketStats[$status]['count']++;
                        $statusPaketStats[$status]['total_nilai'] += $totalKeseluruhan;
                        $statusPaketStats[$status]['total_kuantitas'] += $kuantitas;
                    }

                    // Sort berdasarkan total_nilai
                    uasort($tahunAnggaranStats, fn($a, $b) => $b['total_nilai'] - $a['total_nilai']);
                    uasort($kodeAnggaranStats, fn($a, $b) => $b['total_nilai'] - $a['total_nilai']);
                    uasort($produkStats, fn($a, $b) => $b['total_nilai'] - $a['total_nilai']);
                    uasort($penyediaStats, fn($a, $b) => $b['total_nilai'] - $a['total_nilai']);
                    uasort($statusPaketStats, fn($a, $b) => $b['total_nilai'] - $a['total_nilai']);

                    $rataRataNilai = $totalRecords > 0 ? $totalNilai / $totalRecords : 0;

                    echo json_encode([
                        'success' => true,
                        'summary' => [
                            'total_paket' => $totalRecords,
                            'total_nilai' => $totalNilai,
                            'total_kuantitas' => $totalKuantitas,
                            'rata_rata_nilai' => round($rataRataNilai, 2)
                        ],
                        'breakdown' => [
                            'tahun_anggaran' => $tahunAnggaranStats,
                            'kode_anggaran' => $kodeAnggaranStats,
                            'produk' => $produkStats,
                            'penyedia' => $penyediaStats,
                            'status_paket' => $statusPaketStats
                        ],
                        'filters_applied' => $filters
                    ], JSON_UNESCAPED_UNICODE);
                    break;

                // ==========================================================
                // == DETAIL ================================================
                // ==========================================================
                case 'detail':
                    $kd_paket = $_GET['id'] ?? '';
                    if (empty($kd_paket)) {
                        echo json_encode([
                            'success' => false,
                            'message' => 'ID paket diperlukan'
                        ], JSON_UNESCAPED_UNICODE);
                        break;
                    }
                    
                    $detail = $epurchasing->getPaketDetail($kd_paket);
                    
                    if ($detail) {
                        // Hitung total keseluruhan
                        $kuantitas = (float)($detail['kuantitas'] ?? 0);
                        $harga_satuan = (float)($detail['harga_satuan'] ?? 0);
                        $ongkos_kirim = (float)($detail['ongkos_kirim'] ?? 0);
                        $detail['total_keseluruhan'] = ($kuantitas * $harga_satuan) + $ongkos_kirim;
                        
                        echo json_encode([
                            'success' => true,
                            'data' => $detail
                        ], JSON_UNESCAPED_UNICODE);
                    } else {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Paket tidak ditemukan'
                        ], JSON_UNESCAPED_UNICODE);
                    }
                    break;

                // ==========================================================
                // == OPTIONS (Dropdown Data) ==============================
                // ==========================================================
                case 'options':
                    $tahunFilter = $_GET['tahun'] ?? null;
                    
                    echo json_encode([
                        'success' => true,
                        'options' => [
                            'kode_anggaran' => $epurchasing->getDistinctValues('kode_anggaran'),
                            'produk' => $epurchasing->getDistinctValues('kd_produk'),
                            'penyedia' => $epurchasing->getDistinctValues('kd_penyedia'),
                            'status_paket' => $epurchasing->getDistinctValues('status_paket'),
                            'years' => $epurchasing->getAvailableYears(),
                            'months' => $epurchasing->getAvailableMonths($tahunFilter)
                        ]
                    ], JSON_UNESCAPED_UNICODE);
                    break;

                default:
                    echo json_encode(['success' => false, 'message' => 'Invalid action']);
                    break;
            }
            break;

        // ==========================================================
        // == CREATE ================================================
        // ==========================================================
        case 'POST':
            if ($action === 'create') {
                $input = json_decode(file_get_contents('php://input'), true);
                
                // Validasi field wajib
                $required = ['no_paket', 'nama_paket', 'tahun_anggaran', 'kd_produk', 'kuantitas', 'harga_satuan'];
                foreach ($required as $field) {
                    if (empty($input[$field])) {
                        echo json_encode([
                            'success' => false,
                            'message' => "Field $field wajib diisi"
                        ], JSON_UNESCAPED_UNICODE);
                        exit;
                    }
                }
                
                $result = $epurchasing->createPaket($input);
                
                if ($result['success']) {
                    echo json_encode($result, JSON_UNESCAPED_UNICODE);
                } else {
                    http_response_code(400);
                    echo json_encode($result, JSON_UNESCAPED_UNICODE);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
            }
            break;

        // ==========================================================
        // == UPDATE ================================================
        // ==========================================================
        case 'PUT':
            if ($action === 'update') {
                $kd_paket = $_GET['id'] ?? '';
                if (empty($kd_paket)) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'ID paket diperlukan'
                    ], JSON_UNESCAPED_UNICODE);
                    break;
                }
                
                $input = json_decode(file_get_contents('php://input'), true);
                $result = $epurchasing->updatePaket($kd_paket, $input);
                
                if ($result['success']) {
                    echo json_encode($result, JSON_UNESCAPED_UNICODE);
                } else {
                    http_response_code(400);
                    echo json_encode($result, JSON_UNESCAPED_UNICODE);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
            }
            break;

        // ==========================================================
        // == DELETE ================================================
        // ==========================================================
        case 'DELETE':
            if ($action === 'delete') {
                $kd_paket = $_GET['id'] ?? '';
                if (empty($kd_paket)) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'ID paket diperlukan'
                    ], JSON_UNESCAPED_UNICODE);
                    break;
                }
                
                $result = $epurchasing->deletePaket($kd_paket);
                
                if ($result['success']) {
                    echo json_encode($result, JSON_UNESCAPED_UNICODE);
                } else {
                    http_response_code(400);
                    echo json_encode($result, JSON_UNESCAPED_UNICODE);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    error_log("API Error in epurchasing: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>