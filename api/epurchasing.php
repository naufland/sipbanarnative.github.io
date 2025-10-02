<?php
// File: api/epurchasing.php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../includes/EpurchasingModel.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $epurchasing = new EpurchasingModel($db);
    $action = $_GET['action'] ?? 'list';

    // Build filters untuk E-Purchasing
    $filters = array_filter([
        'tahun_anggaran' => $_GET['tahun_anggaran'] ?? '',
        'kode_anggaran' => $_GET['kode_anggaran'] ?? '',
        'kd_produk' => $_GET['kd_produk'] ?? '',
        'kd_penyedia' => $_GET['kd_penyedia'] ?? '',
        'status_paket' => $_GET['status_paket'] ?? '',
        'search' => $_GET['search'] ?? ''
    ], function($value) {
        return $value !== null && $value !== '';
    });

    switch ($action) {
        case 'list':
            $page = intval($_GET['page'] ?? 1);
            $limit = intval($_GET['limit'] ?? 50);
            $offset = ($page - 1) * $limit;

            $data = $epurchasing->getPaketData($filters, $limit, $offset);
            $total = $epurchasing->getTotalCount($filters);
            $totalPages = $total > 0 ? ceil($total / $limit) : 1;
            
            // Pastikan $data adalah array
            if (!is_array($data)) {
                $data = [];
            }
            
            // Tambahkan nomor urut dan hitung total keseluruhan
            $startNumber = $offset + 1;
            $processedData = [];
            foreach ($data as $index => $row) {
                $row['no_urut'] = $startNumber + $index;
                
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
                    'total_records' => (int)$total,
                    'per_page' => $limit
                ]
            ], JSON_PRETTY_PRINT);
            break;

        case 'summary':
            // Gunakan fungsi getSummaryData()
            $summary = $epurchasing->getSummaryData($filters);
            
            // Ambil data detail untuk breakdown
            $allData = $epurchasing->getAllDataForSummary($filters);
            
            // Pastikan $allData adalah array
            if (!is_array($allData)) {
                $allData = [];
            }
            
            // Breakdown berdasarkan berbagai kategori
            $breakdown = [
                'tahun_anggaran' => [],
                'kode_anggaran' => [],
                'produk' => [],
                'penyedia' => [],
                'status_paket' => []
            ];
            
            foreach ($allData as $row) {
                $kuantitas = (float)($row['kuantitas'] ?? 0);
                $hargaSatuan = (float)($row['harga_satuan'] ?? 0);
                $ongkosKirim = (float)($row['ongkos_kirim'] ?? 0);
                $totalKeseluruhan = ($kuantitas * $hargaSatuan) + $ongkosKirim;
                
                // Breakdown Tahun Anggaran
                $tahun = $row['tahun_anggaran'] ?? 'Tidak Diketahui';
                if (!isset($breakdown['tahun_anggaran'][$tahun])) {
                    $breakdown['tahun_anggaran'][$tahun] = [
                        'count' => 0,
                        'total_nilai' => 0,
                        'total_kuantitas' => 0
                    ];
                }
                $breakdown['tahun_anggaran'][$tahun]['count']++;
                $breakdown['tahun_anggaran'][$tahun]['total_nilai'] += $totalKeseluruhan;
                $breakdown['tahun_anggaran'][$tahun]['total_kuantitas'] += $kuantitas;
                
                // Breakdown Kode Anggaran
                $kodeAnggaran = $row['kode_anggaran'] ?? 'Tidak Diketahui';
                if (!isset($breakdown['kode_anggaran'][$kodeAnggaran])) {
                    $breakdown['kode_anggaran'][$kodeAnggaran] = [
                        'count' => 0,
                        'total_nilai' => 0,
                        'total_kuantitas' => 0
                    ];
                }
                $breakdown['kode_anggaran'][$kodeAnggaran]['count']++;
                $breakdown['kode_anggaran'][$kodeAnggaran]['total_nilai'] += $totalKeseluruhan;
                $breakdown['kode_anggaran'][$kodeAnggaran]['total_kuantitas'] += $kuantitas;
                
                // Breakdown Produk
                $produk = $row['kd_produk'] ?? 'Tidak Diketahui';
                if (!isset($breakdown['produk'][$produk])) {
                    $breakdown['produk'][$produk] = [
                        'count' => 0,
                        'total_nilai' => 0,
                        'total_kuantitas' => 0
                    ];
                }
                $breakdown['produk'][$produk]['count']++;
                $breakdown['produk'][$produk]['total_nilai'] += $totalKeseluruhan;
                $breakdown['produk'][$produk]['total_kuantitas'] += $kuantitas;
                
                // Breakdown Penyedia
                $penyedia = $row['kd_penyedia'] ?? 'Tidak Diketahui';
                if (!isset($breakdown['penyedia'][$penyedia])) {
                    $breakdown['penyedia'][$penyedia] = [
                        'count' => 0,
                        'total_nilai' => 0,
                        'total_kuantitas' => 0
                    ];
                }
                $breakdown['penyedia'][$penyedia]['count']++;
                $breakdown['penyedia'][$penyedia]['total_nilai'] += $totalKeseluruhan;
                $breakdown['penyedia'][$penyedia]['total_kuantitas'] += $kuantitas;
                
                // Breakdown Status Paket
                $status = $row['status_paket'] ?? 'Tidak Diketahui';
                if (!isset($breakdown['status_paket'][$status])) {
                    $breakdown['status_paket'][$status] = [
                        'count' => 0,
                        'total_nilai' => 0,
                        'total_kuantitas' => 0
                    ];
                }
                $breakdown['status_paket'][$status]['count']++;
                $breakdown['status_paket'][$status]['total_nilai'] += $totalKeseluruhan;
                $breakdown['status_paket'][$status]['total_kuantitas'] += $kuantitas;
            }
            
            // Urutkan breakdown berdasarkan total_nilai
            foreach ($breakdown as $key => $group) {
                uasort($breakdown[$key], function($a, $b) {
                    return $b['total_nilai'] <=> $a['total_nilai'];
                });
            }
            
            // Hitung rata-rata
            $rata_rata_nilai = 0;
            if ($summary['total_paket'] > 0) {
                $rata_rata_nilai = $summary['total_nilai'] / $summary['total_paket'];
            }
            
            echo json_encode([
                'success' => true,
                'summary' => [
                    'total_paket' => $summary['total_paket'],
                    'total_nilai' => $summary['total_nilai'],
                    'total_kuantitas' => $summary['total_kuantitas'],
                    'rata_rata_nilai' => round($rata_rata_nilai, 2),
                    'breakdown' => $breakdown
                ]
            ], JSON_PRETTY_PRINT);
            break;

        case 'detail':
            $kd_paket = $_GET['id'] ?? '';
            if (empty($kd_paket)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ID paket diperlukan'
                ], JSON_PRETTY_PRINT);
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
                ], JSON_PRETTY_PRINT);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Paket tidak ditemukan'
                ], JSON_PRETTY_PRINT);
            }
            break;

        case 'create':
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validasi field wajib
            $required = ['no_paket', 'nama_paket', 'tahun_anggaran', 'kd_produk', 'kuantitas', 'harga_satuan'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    echo json_encode([
                        'success' => false,
                        'message' => "Field $field wajib diisi"
                    ], JSON_PRETTY_PRINT);
                    exit;
                }
            }
            
            $result = $epurchasing->createPaket($input);
            
            if ($result['success']) {
                echo json_encode($result, JSON_PRETTY_PRINT);
            } else {
                http_response_code(400);
                echo json_encode($result, JSON_PRETTY_PRINT);
            }
            break;

        case 'update':
            $kd_paket = $_GET['id'] ?? '';
            if (empty($kd_paket)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ID paket diperlukan'
                ], JSON_PRETTY_PRINT);
                break;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $result = $epurchasing->updatePaket($kd_paket, $input);
            
            if ($result['success']) {
                echo json_encode($result, JSON_PRETTY_PRINT);
            } else {
                http_response_code(400);
                echo json_encode($result, JSON_PRETTY_PRINT);
            }
            break;

        case 'delete':
            $kd_paket = $_GET['id'] ?? '';
            if (empty($kd_paket)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ID paket diperlukan'
                ], JSON_PRETTY_PRINT);
                break;
            }
            
            $result = $epurchasing->deletePaket($kd_paket);
            
            if ($result['success']) {
                echo json_encode($result, JSON_PRETTY_PRINT);
            } else {
                http_response_code(400);
                echo json_encode($result, JSON_PRETTY_PRINT);
            }
            break;
        
        case 'options':
            echo json_encode([
                'success' => true,
                'options' => [
                    'kode_anggaran' => $epurchasing->getDistinctValues('kode_anggaran'),
                    'produk' => $epurchasing->getDistinctValues('kd_produk'),
                    'penyedia' => $epurchasing->getDistinctValues('kd_penyedia'),
                    'status_paket' => $epurchasing->getDistinctValues('status_paket'),
                    'years' => $epurchasing->getAvailableYears()
                ]
            ], JSON_PRETTY_PRINT);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action'
            ], JSON_PRETTY_PRINT);
            break;
    }
} catch (Exception $e) {
    error_log("API Error in epurchasing: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>