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
require_once '../includes/RealisasiSeleksiModel.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $realisasiTender = new RealisasiSeleksiModel($db);
    $action = $_GET['action'] ?? 'list';

    $filters = array_filter([
        'tanggal_awal' => $_GET['tanggal_awal'] ?? '',
        'tanggal_akhir' => $_GET['tanggal_akhir'] ?? '',
        'jenis_pengadaan' => $_GET['jenis_pengadaan'] ?? '',
        'klpd' => $_GET['klpd'] ?? '',
        'metode_pengadaan' => $_GET['metode_pengadaan'] ?? '',
        'search' => $_GET['search'] ?? ''
    ]);

    switch ($action) {
        case 'list':
            $page = intval($_GET['page'] ?? 1);
            $limit = intval($_GET['limit'] ?? 100000);
            $offset = ($page - 1) * $limit;

            $data = $realisasiTender->getRealisasiSeleksiData($filters, $limit, $offset);
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
                ]
            ]);
            break;

        case 'summary':
            // MEMANGGIL METODE YANG BENAR
            $allData = $realisasiTender->getAllDataForSummary($filters);
            
            $summary = [
                'total_paket' => count($allData),
                'total_pagu' => 0, 'total_hps' => 0, 'total_kontrak' => 0,
                'breakdown' => [
                    'jenis_pengadaan' => [], 'klpd' => [], 'metode_pengadaan' => []
                ]
            ];

            foreach ($allData as $row) {
                $pagu = (float)($row['Nilai_Pagu'] ?? 0);
                $hps = (float)($row['Nilai_HPS'] ?? 0);
                $kontrak = (float)($row['Nilai_Kontrak'] ?? 0);

                $summary['total_pagu'] += $pagu;
                $summary['total_hps'] += $hps;
                $summary['total_kontrak'] += $kontrak;

                // Proses breakdown
                $breakdownKeys = [
                    'jenis_pengadaan' => 'Jenis_Pengadaan',
                    'klpd' => 'KLPD',
                    'metode_pengadaan' => 'Metode_Pengadaan'
                ];
                foreach ($breakdownKeys as $key => $dbKey) {
                    if (!empty($row[$dbKey])) {
                        $value = $row[$dbKey];
                        if (!isset($summary['breakdown'][$key][$value])) {
                            $summary['breakdown'][$key][$value] = ['count' => 0, 'total_pagu' => 0];
                        }
                        $summary['breakdown'][$key][$value]['count']++;
                        $summary['breakdown'][$key][$value]['total_pagu'] += $pagu;
                    }
                }
            }

            $summary['avg_pagu'] = $summary['total_paket'] > 0 ? $summary['total_pagu'] / $summary['total_paket'] : 0;
            
            foreach ($summary['breakdown'] as &$breakdown) {
                uasort($breakdown, fn($a, $b) => $b['total_pagu'] <=> $a['total_pagu']);
            }

            echo json_encode(['success' => true, 'summary' => $summary]);
            break;
        
        case 'options':
            echo json_encode([
                'success' => true,
                'options' => [
                    'jenis_pengadaan' => $realisasiTender->getDistinctValues('Jenis_Pengadaan'),
                    'klpd' => $realisasiTender->getDistinctValues('KLPD'),
                    'metode_pengadaan' => $realisasiTender->getDistinctValues('Metode_Pengadaan'),
                    'years' => $realisasiTender->getAvailableYears() // Pemanggilan ini sekarang sudah valid
                ]
            ]);
            break;
    }
} catch (Exception $e) {
    error_log("API Error in realisasi_tender: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}