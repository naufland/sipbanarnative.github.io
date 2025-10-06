<?php
// File: api/pencatatan_nontender.php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../includes/PencatatanNontenderModel.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $pencatatanNontender = new PencatatanNontenderModel($db);
    $action = $_GET['action'] ?? 'list';

    // Build filters untuk Pencatatan Non-Tender
    $filters = array_filter([
        'tahun' => $_GET['tahun'] ?? '',
        'klpd' => $_GET['klpd'] ?? '',
        'metode_pengadaan' => $_GET['metode_pengadaan'] ?? '',
        'jenis_pengadaan' => $_GET['jenis_pengadaan'] ?? '',
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

            $data = $pencatatanNontender->getPencatatanNontenderData($filters, $limit, $offset);
            $total = $pencatatanNontender->getTotalCount($filters);
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
            $summary = $pencatatanNontender->getSummaryData($filters);
            
            // Ambil data detail untuk breakdown
            $allData = $pencatatanNontender->getAllDataForSummary($filters);
            
            // Pastikan $allData adalah array
            if (!is_array($allData)) {
                $allData = [];
            }
            
            // Breakdown berdasarkan berbagai kategori
            $breakdown = [
                'metode_pengadaan' => [],
                'jenis_pengadaan' => [],
                'klpd' => [],
                'status_paket' => []
            ];
            
            foreach ($allData as $row) {
                $pagu = (float)($row['Nilai_Pagu'] ?? 0);
                $totalRealisasi = (float)($row['Nilai_Total_Realisasi'] ?? 0);
                $pdn = (float)($row['Nilai_PDN'] ?? 0);
                $umk = (float)($row['Nilai_UMK'] ?? 0);
                
                // Breakdown Metode Pengadaan
                $metode = $row['Metode_pengadaan'] ?? 'Tidak Diketahui';
                if (!isset($breakdown['metode_pengadaan'][$metode])) {
                    $breakdown['metode_pengadaan'][$metode] = [
                        'count' => 0,
                        'total_pagu' => 0,
                        'total_realisasi' => 0,
                        'total_pdn' => 0,
                        'total_umk' => 0
                    ];
                }
                $breakdown['metode_pengadaan'][$metode]['count']++;
                $breakdown['metode_pengadaan'][$metode]['total_pagu'] += $pagu;
                $breakdown['metode_pengadaan'][$metode]['total_realisasi'] += $totalRealisasi;
                $breakdown['metode_pengadaan'][$metode]['total_pdn'] += $pdn;
                $breakdown['metode_pengadaan'][$metode]['total_umk'] += $umk;
                
                // Breakdown Jenis Pengadaan
                $jenis = $row['Jenis_Pengadaan'] ?? 'Tidak Diketahui';
                if (!isset($breakdown['jenis_pengadaan'][$jenis])) {
                    $breakdown['jenis_pengadaan'][$jenis] = [
                        'count' => 0,
                        'total_pagu' => 0,
                        'total_realisasi' => 0,
                        'total_pdn' => 0,
                        'total_umk' => 0
                    ];
                }
                $breakdown['jenis_pengadaan'][$jenis]['count']++;
                $breakdown['jenis_pengadaan'][$jenis]['total_pagu'] += $pagu;
                $breakdown['jenis_pengadaan'][$jenis]['total_realisasi'] += $totalRealisasi;
                $breakdown['jenis_pengadaan'][$jenis]['total_pdn'] += $pdn;
                $breakdown['jenis_pengadaan'][$jenis]['total_umk'] += $umk;
                
                // Breakdown KLPD
                $klpd = $row['KLPD'] ?? 'Tidak Diketahui';
                if (!isset($breakdown['klpd'][$klpd])) {
                    $breakdown['klpd'][$klpd] = [
                        'count' => 0,
                        'total_pagu' => 0,
                        'total_realisasi' => 0,
                        'total_pdn' => 0,
                        'total_umk' => 0
                    ];
                }
                $breakdown['klpd'][$klpd]['count']++;
                $breakdown['klpd'][$klpd]['total_pagu'] += $pagu;
                $breakdown['klpd'][$klpd]['total_realisasi'] += $totalRealisasi;
                $breakdown['klpd'][$klpd]['total_pdn'] += $pdn;
                $breakdown['klpd'][$klpd]['total_umk'] += $umk;
                
                // Breakdown Status Paket
                $status = $row['Status_Paket'] ?? 'Tidak Diketahui';
                if (!isset($breakdown['status_paket'][$status])) {
                    $breakdown['status_paket'][$status] = [
                        'count' => 0,
                        'total_pagu' => 0,
                        'total_realisasi' => 0,
                        'total_pdn' => 0,
                        'total_umk' => 0
                    ];
                }
                $breakdown['status_paket'][$status]['count']++;
                $breakdown['status_paket'][$status]['total_pagu'] += $pagu;
                $breakdown['status_paket'][$status]['total_realisasi'] += $totalRealisasi;
                $breakdown['status_paket'][$status]['total_pdn'] += $pdn;
                $breakdown['status_paket'][$status]['total_umk'] += $umk;
            }
            
            // Urutkan breakdown berdasarkan total_pagu
            foreach ($breakdown as $key => $group) {
                uasort($breakdown[$key], function($a, $b) {
                    return $b['total_pagu'] <=> $a['total_pagu'];
                });
            }
            
            // Hitung persentase realisasi (untuk efisiensi)
            $persentase_realisasi = 0;
            if ($summary['total_pagu'] > 0) {
                $persentase_realisasi = ($summary['total_realisasi'] / $summary['total_pagu']) * 100;
            }
            
            // Hitung efisiensi anggaran
            $efisiensi = 0;
            if ($summary['total_pagu'] > 0) {
                $efisiensi = (($summary['total_pagu'] - $summary['total_realisasi']) / $summary['total_pagu']) * 100;
            }
            
            // Hitung persentase PDN
            $persentase_pdn = 0;
            if ($summary['total_realisasi'] > 0) {
                $persentase_pdn = ($summary['total_pdn'] / $summary['total_realisasi']) * 100;
            }
            
            // Hitung persentase UMK
            $persentase_umk = 0;
            if ($summary['total_realisasi'] > 0) {
                $persentase_umk = ($summary['total_umk'] / $summary['total_realisasi']) * 100;
            }
            
            echo json_encode([
                'success' => true,
                'summary' => [
                    'total_paket' => $summary['total_paket'],
                    'total_pagu' => $summary['total_pagu'],
                    'total_realisasi' => $summary['total_realisasi'],
                    'total_pdn' => $summary['total_pdn'],
                    'total_umk' => $summary['total_umk'],
                    'efisiensi' => round($efisiensi, 2),
                    'persentase_realisasi' => round($persentase_realisasi, 2),
                    'persentase_pdn' => round($persentase_pdn, 2),
                    'persentase_umk' => round($persentase_umk, 2),
                    'breakdown' => $breakdown
                ]
            ], JSON_PRETTY_PRINT);
            break;
        
        case 'options':
            echo json_encode([
                'success' => true,
                'options' => [
                    'metode_pengadaan' => $pencatatanNontender->getDistinctValues('Metode_pengadaan'),
                    'jenis_pengadaan' => $pencatatanNontender->getDistinctValues('Jenis_Pengadaan'),
                    'klpd' => $pencatatanNontender->getDistinctValues('KLPD'),
                    'status_paket' => $pencatatanNontender->getDistinctValues('Status_Paket'),
                    'years' => $pencatatanNontender->getAvailableYears()
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
    error_log("API Error in pencatatan_nontender: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ], JSON_PRETTY_PRINT);
}