<?php
// File: api/realisasi_dikecualikan.php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../includes/RealisasiDikecualikanModel.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $realisasiDikecualikan = new RealisasiDikecualikanModel($db);
    $action = $_GET['action'] ?? 'list';

    // Build filters untuk Realisasi Dikecualikan dengan support bulan
    $filters = array_filter([
        'bulan' => $_GET['bulan'] ?? '',           // BARU: Filter bulan
        'tahun' => $_GET['tahun'] ?? '',
        'nama_satker' => $_GET['nama_satker'] ?? '',  // Ganti dari klpd ke nama_satker
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

            $data = $realisasiDikecualikan->getRealisasiDikecualikanData($filters, $limit, $offset);
            $total = $realisasiDikecualikan->getTotalCount($filters);
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
                ],
                'filters_applied' => $filters  // BARU: Tampilkan filter yang diterapkan
            ], JSON_PRETTY_PRINT);
            break;

        case 'summary':
            // Gunakan fungsi getSummaryData() dengan support filter bulan
            $summary = $realisasiDikecualikan->getSummaryData($filters);
            
            // Ambil data detail untuk breakdown
            $allData = $realisasiDikecualikan->getAllDataForSummary($filters);
            
            // Pastikan $allData adalah array
            if (!is_array($allData)) {
                $allData = [];
            }
            
            // Breakdown berdasarkan berbagai kategori (HAPUS status_paket)
            $breakdown = [
                'metode_pengadaan' => [],
                'jenis_pengadaan' => [],
                'nama_satker' => []  // Ganti dari klpd ke nama_satker
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
                
                // Breakdown Satker (ganti dari KLPD)
                $satker = $row['Nama_Satker'] ?? 'Tidak Diketahui';
                if (!isset($breakdown['nama_satker'][$satker])) {
                    $breakdown['nama_satker'][$satker] = [
                        'count' => 0,
                        'total_pagu' => 0,
                        'total_realisasi' => 0,
                        'total_pdn' => 0,
                        'total_umk' => 0
                    ];
                }
                $breakdown['nama_satker'][$satker]['count']++;
                $breakdown['nama_satker'][$satker]['total_pagu'] += $pagu;
                $breakdown['nama_satker'][$satker]['total_realisasi'] += $totalRealisasi;
                $breakdown['nama_satker'][$satker]['total_pdn'] += $pdn;
                $breakdown['nama_satker'][$satker]['total_umk'] += $umk;
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
                ],
                'filters_applied' => $filters,  // BARU: Tampilkan filter yang diterapkan
                'period_info' => [              // BARU: Info periode
                    'bulan' => $filters['bulan'] ?? null,
                    'tahun' => $filters['tahun'] ?? null,
                    'bulan_nama' => isset($filters['bulan']) ? [
                        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
                        '04' => 'April', '05' => 'Mei', '06' => 'Juni',
                        '07' => 'Juli', '08' => 'Agustus', '09' => 'September',
                        '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
                    ][$filters['bulan']] ?? null : null
                ]
            ], JSON_PRETTY_PRINT);
            break;
        
        case 'options':
            // BARU: Get available months
            $tahunFilter = $_GET['tahun'] ?? null;
            $months = $realisasiDikecualikan->getAvailableMonths($tahunFilter);
            
            echo json_encode([
                'success' => true,
                'options' => [
                    'metode_pengadaan' => $realisasiDikecualikan->getDistinctValues('Metode_pengadaan'),
                    'jenis_pengadaan' => $realisasiDikecualikan->getDistinctValues('Jenis_Pengadaan'),
                    'nama_satker' => $realisasiDikecualikan->getDistinctValues('Nama_Satker'),  // Ganti dari klpd
                    'years' => $realisasiDikecualikan->getAvailableYears(),
                    'months' => $months  // BARU: Daftar bulan yang tersedia
                ]
            ], JSON_PRETTY_PRINT);
            break;

        case 'months':
            // BARU: Endpoint khusus untuk mendapatkan daftar bulan
            $tahun = $_GET['tahun'] ?? null;
            $months = $realisasiDikecualikan->getAvailableMonths($tahun);
            
            // Format nama bulan
            $monthsWithNames = [];
            $namaBulan = [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
                4 => 'April', 5 => 'Mei', 6 => 'Juni',
                7 => 'Juli', 8 => 'Agustus', 9 => 'September',
                10 => 'Oktober', 11 => 'November', 12 => 'Desember'
            ];
            
            foreach ($months as $month) {
                $monthsWithNames[] = [
                    'value' => str_pad($month, 2, '0', STR_PAD_LEFT),
                    'label' => $namaBulan[$month]
                ];
            }

            echo json_encode([
                'success' => true,
                'months' => $monthsWithNames,
                'tahun' => $tahun
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
    error_log("API Error in realisasi_dikecualikan: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ], JSON_PRETTY_PRINT);
}