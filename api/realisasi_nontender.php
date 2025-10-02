<?php
// File: api/realisasi_nontender.php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../includes/RealisasiNontenderModel.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $realisasiNontender = new RealisasiNontenderModel($db);
    $action = $_GET['action'] ?? 'list';

    // Build filters untuk Non-Tender
    $filters = array_filter([
        'tahun' => $_GET['tahun'] ?? '',
        'klpd' => $_GET['klpd'] ?? '',
        'metode_pengadaan' => $_GET['metode_pengadaan'] ?? '',
        'jenis_pengadaan' => $_GET['jenis_pengadaan'] ?? '',
        'search' => $_GET['search'] ?? ''
    ], function($value) {
        return $value !== null && $value !== '';
    });

    switch ($action) {
        case 'list':
            $page = intval($_GET['page'] ?? 1);
            $limit = intval($_GET['limit'] ?? 50);
            $offset = ($page - 1) * $limit;

            $data = $realisasiNontender->getRealisasiNontenderData($filters, $limit, $offset);
            $total = $realisasiNontender->getTotalCount($filters);
            $totalPages = $total > 0 ? ceil($total / $limit) : 1;
            
            // Pastikan $data adalah array
            if (!is_array($data)) {
                $data = [];
            }
            
            // Tambahkan nomor urut dengan key berbeda
            $startNumber = $offset + 1;
            $processedData = [];
            foreach ($data as $index => $row) {
                $row['No_Urut'] = $startNumber + $index;
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
            $summary = $realisasiNontender->getSummaryData($filters);
            
            // Ambil data detail untuk breakdown
            $allData = $realisasiNontender->getAllDataForSummary($filters);
            
            // Pastikan $allData adalah array
            if (!is_array($allData)) {
                $allData = [];
            }
            
            // Breakdown berdasarkan berbagai kategori
            $breakdown = [
                'metode_pengadaan' => [],
                'jenis_pengadaan' => [],
                'klpd' => []
            ];
            
            foreach ($allData as $row) {
                $pagu = (float)($row['Nilai_Pagu'] ?? 0);
                $kontrak = (float)($row['Nilai_Kontrak'] ?? 0);
                
                // Breakdown Metode Pengadaan
                $metode = $row['Metode_Pengadaan'] ?? 'Tidak Diketahui';
                if (!isset($breakdown['metode_pengadaan'][$metode])) {
                    $breakdown['metode_pengadaan'][$metode] = [
                        'count' => 0,
                        'total_pagu' => 0,
                        'total_kontrak' => 0
                    ];
                }
                $breakdown['metode_pengadaan'][$metode]['count']++;
                $breakdown['metode_pengadaan'][$metode]['total_pagu'] += $pagu;
                $breakdown['metode_pengadaan'][$metode]['total_kontrak'] += $kontrak;
                
                // Breakdown Jenis Pengadaan
                $jenis = $row['Jenis_Pengadaan'] ?? 'Tidak Diketahui';
                if (!isset($breakdown['jenis_pengadaan'][$jenis])) {
                    $breakdown['jenis_pengadaan'][$jenis] = [
                        'count' => 0,
                        'total_pagu' => 0,
                        'total_kontrak' => 0
                    ];
                }
                $breakdown['jenis_pengadaan'][$jenis]['count']++;
                $breakdown['jenis_pengadaan'][$jenis]['total_pagu'] += $pagu;
                $breakdown['jenis_pengadaan'][$jenis]['total_kontrak'] += $kontrak;
                
                // Breakdown KLPD
                $klpd = $row['KLPD'] ?? 'Tidak Diketahui';
                if (!isset($breakdown['klpd'][$klpd])) {
                    $breakdown['klpd'][$klpd] = [
                        'count' => 0,
                        'total_pagu' => 0,
                        'total_kontrak' => 0
                    ];
                }
                $breakdown['klpd'][$klpd]['count']++;
                $breakdown['klpd'][$klpd]['total_pagu'] += $pagu;
                $breakdown['klpd'][$klpd]['total_kontrak'] += $kontrak;
            }
            
            // Urutkan breakdown berdasarkan total_pagu
            foreach ($breakdown as $key => $group) {
                uasort($breakdown[$key], function($a, $b) {
                    return $b['total_pagu'] <=> $a['total_pagu'];
                });
            }
            
            // Hitung persentase realisasi
            $persentase_realisasi = 0;
            if ($summary['total_pagu'] > 0) {
                $persentase_realisasi = ($summary['total_kontrak'] / $summary['total_pagu']) * 100;
            }
            
            echo json_encode([
                'success' => true,
                'summary' => [
                    'total_paket' => $summary['total_paket'],
                    'total_pagu' => $summary['total_pagu'],
                    'total_kontrak' => $summary['total_kontrak'],
                    'persentase_realisasi' => round($persentase_realisasi, 2),
                    'breakdown' => $breakdown
                ]
            ], JSON_PRETTY_PRINT);
            break;
        
        case 'options':
            echo json_encode([
                'success' => true,
                'options' => [
                    'metode_pengadaan' => $realisasiNontender->getDistinctValues('Metode_Pengadaan'),
                    'jenis_pengadaan' => $realisasiNontender->getDistinctValues('Jenis_Pengadaan'),
                    'klpd' => $realisasiNontender->getDistinctValues('KLPD'),
                    'years' => $realisasiNontender->getAvailableYears()
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
    error_log("API Error in realisasi_nontender: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ], JSON_PRETTY_PRINT);
}