<?php
// File: api/realisasi_swakelola.php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../includes/RealisasiSwakelolaModel.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $realisasiSwakelola = new RealisasiSwakelolaModel($db);
    $action = $_GET['action'] ?? 'list';

    // Build filters untuk Swakelola
    $filters = array_filter([
        'tahun' => $_GET['tahun'] ?? '',
        'klpd' => $_GET['klpd'] ?? '',
        'tipe_swakelola' => $_GET['tipe_swakelola'] ?? '',
        'search' => $_GET['search'] ?? ''
    ]);

    switch ($action) {
        case 'list':
            $page = intval($_GET['page'] ?? 1);
            $limit = intval($_GET['limit'] ?? 50);
            $offset = ($page - 1) * $limit;

            $data = $realisasiSwakelola->getRealisasiSeleksiData($filters, $limit, $offset);
            $total = $realisasiSwakelola->getTotalCount($filters);
            $totalPages = ceil($total / $limit);
            
            // Tambahkan nomor urut
            $startNumber = $offset + 1;
            foreach ($data as $index => &$row) {
                $row['No'] = $startNumber + $index;
            }
            
            echo json_encode([
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_records' => (int)$total,
                    'per_page' => $limit
                ]
            ], JSON_PRETTY_PRINT);
            break;

        case 'summary':
            // GUNAKAN FUNGSI getSummaryData() YANG SUDAH DIBUAT
            $summary = $realisasiSwakelola->getSummaryData($filters);
            
            // Tambahan: Ambil data detail untuk breakdown (opsional)
            $allData = $realisasiSwakelola->getAllDataForSummary($filters);
            
            // Breakdown berdasarkan Tipe Swakelola
            $breakdown = [
                'tipe_swakelola' => [],
                'klpd' => []
            ];
            
            foreach ($allData as $row) {
                $pagu = (float)($row['Nilai_Pagu'] ?? 0);
                $realisasi = (float)($row['Nilai_Total_Realisasi'] ?? 0);
                
                // Breakdown Tipe Swakelola
                $tipe = $row['Tipe_Swakelola'] ?? 'Tidak Diketahui';
                if (!isset($breakdown['tipe_swakelola'][$tipe])) {
                    $breakdown['tipe_swakelola'][$tipe] = [
                        'count' => 0,
                        'total_pagu' => 0,
                        'total_realisasi' => 0
                    ];
                }
                $breakdown['tipe_swakelola'][$tipe]['count']++;
                $breakdown['tipe_swakelola'][$tipe]['total_pagu'] += $pagu;
                $breakdown['tipe_swakelola'][$tipe]['total_realisasi'] += $realisasi;
                
                // Breakdown KLPD
                $klpd = $row['KLPD'] ?? 'Tidak Diketahui';
                if (!isset($breakdown['klpd'][$klpd])) {
                    $breakdown['klpd'][$klpd] = [
                        'count' => 0,
                        'total_pagu' => 0,
                        'total_realisasi' => 0
                    ];
                }
                $breakdown['klpd'][$klpd]['count']++;
                $breakdown['klpd'][$klpd]['total_pagu'] += $pagu;
                $breakdown['klpd'][$klpd]['total_realisasi'] += $realisasi;
            }
            
            // Urutkan breakdown berdasarkan total_pagu
            foreach ($breakdown as &$group) {
                uasort($group, fn($a, $b) => $b['total_pagu'] <=> $a['total_pagu']);
            }
            
            // Hitung persentase realisasi
            $persentase_realisasi = 0;
            if ($summary['total_pagu'] > 0) {
                $persentase_realisasi = ($summary['total_realisasi'] / $summary['total_pagu']) * 100;
            }
            
            echo json_encode([
                'success' => true,
                'summary' => [
                    'total_paket' => $summary['total_paket'],
                    'total_pagu' => $summary['total_pagu'],
                    'total_realisasi' => $summary['total_realisasi'],
                    'persentase_realisasi' => round($persentase_realisasi, 2),
                    'breakdown' => $breakdown
                ]
            ], JSON_PRETTY_PRINT);
            break;
        
        case 'options':
            echo json_encode([
                'success' => true,
                'options' => [
                    'tipe_swakelola' => $realisasiSwakelola->getDistinctValues('Tipe_Swakelola'),
                    'klpd' => $realisasiSwakelola->getDistinctValues('KLPD'),
                    'years' => $realisasiSwakelola->getAvailableYears()
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
    error_log("API Error in realisasi_swakelola: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ], JSON_PRETTY_PRINT);
}