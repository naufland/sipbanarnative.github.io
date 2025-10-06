<?php
// File: api/perencanaan.php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../includes/SektoralModel.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $perencanaan = new SektoralModel($db);
    $action = $_GET['action'] ?? 'list';

    // Build filters untuk Perencanaan
    $filters = array_filter([
        'tahun' => $_GET['tahun'] ?? '',
        'kategori' => $_GET['kategori'] ?? '',
        'search' => $_GET['search'] ?? ''
    ], function($value) {
        return $value !== null && $value !== '';
    });

    switch ($action) {
        case 'list':
            $page = intval($_GET['page'] ?? 1);
            $limit = intval($_GET['limit'] ?? 50);
            $offset = ($page - 1) * $limit;

            $data = $perencanaan->getPerencanaanData($filters, $limit, $offset);
            $total = $perencanaan->getTotalCount($filters);
            $totalPages = $total > 0 ? ceil($total / $limit) : 1;
            
            // Pastikan $data adalah array
            if (!is_array($data)) {
                $data = [];
            }
            
            // Tambahkan nomor urut
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
            $summary = $perencanaan->getSummaryData($filters);
            
            // Ambil data detail untuk breakdown
            $allData = $perencanaan->getAllDataForSummary($filters);
            
            // Pastikan $allData adalah array
            if (!is_array($allData)) {
                $allData = [];
            }
            
            // Breakdown berdasarkan kategori
            $breakdown = [
                'kategori' => [],
                'nama_satker' => []
            ];
            
            foreach ($allData as $row) {
                $totalPerencanaan = (float)($row['Total_Perencanaan_Rp'] ?? 0);
                $pdn = (float)($row['PDN_Rp'] ?? 0);
                
                // Breakdown Kategori
                $kategori = $row['Kategori'] ?? 'Tidak Diketahui';
                if (!isset($breakdown['kategori'][$kategori])) {
                    $breakdown['kategori'][$kategori] = [
                        'count' => 0,
                        'total_perencanaan' => 0,
                        'total_pdn' => 0
                    ];
                }
                $breakdown['kategori'][$kategori]['count']++;
                $breakdown['kategori'][$kategori]['total_perencanaan'] += $totalPerencanaan;
                $breakdown['kategori'][$kategori]['total_pdn'] += $pdn;
                
                // Breakdown Nama Satker
                $satker = $row['Nama_Satker'] ?? 'Tidak Diketahui';
                if (!isset($breakdown['nama_satker'][$satker])) {
                    $breakdown['nama_satker'][$satker] = [
                        'count' => 0,
                        'total_perencanaan' => 0,
                        'total_pdn' => 0
                    ];
                }
                $breakdown['nama_satker'][$satker]['count']++;
                $breakdown['nama_satker'][$satker]['total_perencanaan'] += $totalPerencanaan;
                $breakdown['nama_satker'][$satker]['total_pdn'] += $pdn;
            }
            
            // Urutkan breakdown berdasarkan total_perencanaan
            foreach ($breakdown as $key => $group) {
                uasort($breakdown[$key], function($a, $b) {
                    return $b['total_perencanaan'] <=> $a['total_perencanaan'];
                });
            }
            
            // Hitung persentase PDN
            $persentase_pdn = 0;
            if ($summary['total_perencanaan'] > 0) {
                $persentase_pdn = ($summary['total_pdn'] / $summary['total_perencanaan']) * 100;
            }
            
            echo json_encode([
                'success' => true,
                'summary' => [
                    'total_paket' => $summary['total_paket'],
                    'total_perencanaan' => $summary['total_perencanaan'],
                    'total_pdn' => $summary['total_pdn'],
                    'persentase_pdn' => round($persentase_pdn, 2),
                    'breakdown' => $breakdown
                ]
            ], JSON_PRETTY_PRINT);
            break;
        
        case 'options':
            echo json_encode([
                'success' => true,
                'options' => [
                    'kategori' => $perencanaan->getDistinctValues('Kategori'),
                    'nama_satker' => $perencanaan->getDistinctValues('Nama_Satker'),
                    'years' => $perencanaan->getAvailableYears()
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
    error_log("API Error in perencanaan: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ], JSON_PRETTY_PRINT);
}