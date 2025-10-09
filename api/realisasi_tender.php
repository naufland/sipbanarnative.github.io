<?php
// File: api/realisasi_tender.php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../includes/RealisasiTenderModel.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $realisasiTender = new RealisasiTenderModel($db);
    $action = $_GET['action'] ?? 'list';

    // Build filters array dengan bulan dan tahun
    $filters = [];
    
    // Filter Bulan - pastikan format 2 digit
    if (isset($_GET['bulan']) && $_GET['bulan'] !== '') {
        $filters['bulan'] = str_pad($_GET['bulan'], 2, '0', STR_PAD_LEFT);
    }
    
    // Filter Tahun
    if (isset($_GET['tahun']) && $_GET['tahun'] !== '') {
        $filters['tahun'] = $_GET['tahun'];
    }
    
    // Filter lainnya
    if (isset($_GET['tanggal_awal']) && $_GET['tanggal_awal'] !== '') {
        $filters['tanggal_awal'] = $_GET['tanggal_awal'];
    }
    if (isset($_GET['tanggal_akhir']) && $_GET['tanggal_akhir'] !== '') {
        $filters['tanggal_akhir'] = $_GET['tanggal_akhir'];
    }
    if (isset($_GET['jenis_pengadaan']) && $_GET['jenis_pengadaan'] !== '') {
        $filters['jenis_pengadaan'] = $_GET['jenis_pengadaan'];
    }
    if (isset($_GET['klpd']) && $_GET['klpd'] !== '') {
        $filters['klpd'] = $_GET['klpd'];
    }
    if (isset($_GET['metode_pengadaan']) && $_GET['metode_pengadaan'] !== '') {
        $filters['metode_pengadaan'] = $_GET['metode_pengadaan'];
    }
    if (isset($_GET['sumber_dana']) && $_GET['sumber_dana'] !== '') {
        $filters['sumber_dana'] = $_GET['sumber_dana'];
    }
    if (isset($_GET['search']) && $_GET['search'] !== '') {
        $filters['search'] = $_GET['search'];
    }

    switch ($action) {
        case 'list':
            $page = intval($_GET['page'] ?? 1);
            $limit = intval($_GET['limit'] ?? 50);
            $offset = ($page - 1) * $limit;

            $data = $realisasiTender->getRealisasiTenderData($filters, $limit, $offset);
            $total = $realisasiTender->getTotalCount($filters);
            $totalPages = ceil($total / $limit);
            
            echo json_encode([
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_records' => (int)$total,
                    'per_page' => $limit
                ],
                'filters_applied' => $filters // Debug: tampilkan filter yang diterapkan
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            break;

        case 'summary':
            // Gunakan metode baru yang lebih efisien
            $summary = $realisasiTender->getSummaryWithFilters($filters);
            
            // Tambahan: breakdown data untuk analisis
            $allData = $realisasiTender->getAllDataForSummary($filters);
            
            $breakdown = [
                'jenis_pengadaan' => [],
                'klpd' => [],
                'metode_pengadaan' => []
            ];

            foreach ($allData as $row) {
                $pagu = (float)($row['Nilai_Pagu'] ?? 0);
                $kontrak = (float)($row['Nilai_Kontrak'] ?? 0);

                // Proses breakdown
                $breakdownKeys = [
                    'jenis_pengadaan' => 'Jenis_Pengadaan',
                    'klpd' => 'KLPD',
                    'metode_pengadaan' => 'Metode_Pengadaan'
                ];
                
                foreach ($breakdownKeys as $key => $dbKey) {
                    if (!empty($row[$dbKey])) {
                        $value = $row[$dbKey];
                        if (!isset($breakdown[$key][$value])) {
                            $breakdown[$key][$value] = [
                                'count' => 0,
                                'total_pagu' => 0,
                                'total_kontrak' => 0
                            ];
                        }
                        $breakdown[$key][$value]['count']++;
                        $breakdown[$key][$value]['total_pagu'] += $pagu;
                        $breakdown[$key][$value]['total_kontrak'] += $kontrak;
                    }
                }
            }

            // Sort breakdown by total_pagu (descending)
            foreach ($breakdown as &$items) {
                uasort($items, function($a, $b) {
                    return $b['total_pagu'] <=> $a['total_pagu'];
                });
            }

            // Hitung rata-rata dan efisiensi
            $avg_pagu = $summary['total_paket'] > 0 ? $summary['total_pagu'] / $summary['total_paket'] : 0;
            $avg_kontrak = $summary['total_paket'] > 0 ? $summary['total_kontrak'] / $summary['total_paket'] : 0;
            
            $efisiensi = 0;
            if ($summary['total_pagu'] > 0) {
                $efisiensi = (($summary['total_pagu'] - $summary['total_kontrak']) / $summary['total_pagu']) * 100;
            }

            echo json_encode([
                'success' => true,
                'summary' => [
                    'total_paket' => $summary['total_paket'],
                    'total_pagu' => $summary['total_pagu'],
                    'total_hps' => $summary['total_hps'],
                    'total_kontrak' => $summary['total_kontrak'],
                    'avg_pagu' => $avg_pagu,
                    'avg_kontrak' => $avg_kontrak,
                    'efisiensi_persen' => round($efisiensi, 2),
                    'penghematan' => $summary['total_pagu'] - $summary['total_kontrak'],
                    'breakdown' => $breakdown
                ],
                'filters_applied' => $filters
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            break;
        
        case 'options':
            // Mendapatkan semua opsi untuk dropdown filter
            $tahun = $_GET['tahun'] ?? null;
            
            echo json_encode([
                'success' => true,
                'options' => [
                    'jenis_pengadaan' => $realisasiTender->getDistinctValues('Jenis_Pengadaan'),
                    'klpd' => $realisasiTender->getDistinctValues('KLPD'),
                    'metode_pengadaan' => $realisasiTender->getDistinctValues('Metode_Pengadaan'),
                    'sumber_dana' => $realisasiTender->getDistinctValues('Sumber_Dana'),
                    'years' => $realisasiTender->getAvailableYears(),
                    'months' => $tahun ? $realisasiTender->getAvailableMonths($tahun) : []
                ]
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            break;

        case 'monthly_summary':
            // BARU: Endpoint untuk mendapatkan summary per bulan dalam satu tahun
            $tahun = intval($_GET['tahun'] ?? date('Y'));
            $monthlySummary = $realisasiTender->getMonthlySummary($tahun);
            
            echo json_encode([
                'success' => true,
                'tahun' => $tahun,
                'data' => $monthlySummary
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            break;

        case 'efficiency_stats':
            // BARU: Endpoint untuk statistik efisiensi
            $stats = $realisasiTender->getEfficiencyStats($filters);
            
            echo json_encode([
                'success' => true,
                'stats' => $stats,
                'filters_applied' => $filters
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            break;

        case 'by_jenis':
            // BARU: Endpoint untuk summary berdasarkan jenis pengadaan
            $byJenis = $realisasiTender->getSummaryByJenisPengadaan($filters);
            
            echo json_encode([
                'success' => true,
                'data' => $byJenis,
                'filters_applied' => $filters
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            break;

        case 'by_klpd':
            // BARU: Endpoint untuk summary berdasarkan KLPD
            $byKLPD = $realisasiTender->getSummaryByKLPD($filters);
            
            echo json_encode([
                'success' => true,
                'data' => $byKLPD,
                'filters_applied' => $filters
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            break;

        case 'export':
            // BARU: Endpoint untuk export data (optional)
            // Ini bisa digunakan untuk export ke CSV atau Excel
            $data = $realisasiTender->getRealisasiTenderData($filters, 10000, 0);
            
            echo json_encode([
                'success' => true,
                'data' => $data,
                'total_records' => count($data),
                'filters_applied' => $filters
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            break;

        default:
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action. Available actions: list, summary, options, monthly_summary, efficiency_stats, by_jenis, by_klpd, export'
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            break;
    }

} catch (PDOException $e) {
    error_log("Database Error in realisasi_tender API: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error',
        'error' => $e->getMessage() // Hapus di production
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    error_log("API Error in realisasi_tender: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'error' => $e->getMessage() // Hapus di production
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}