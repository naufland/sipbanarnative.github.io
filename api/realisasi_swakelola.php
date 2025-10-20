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

    switch ($action) {
        case 'list':
            // Get filters from query parameters - DITAMBAHKAN BULAN DAN TAHUN
            $filters = [
                'bulan' => $_GET['bulan'] ?? '',               // BARU: Filter bulan
                'tahun' => $_GET['tahun'] ?? '',               // Filter tahun
                'tanggal_awal' => $_GET['tanggal_awal'] ?? '',
                'tanggal_akhir' => $_GET['tanggal_akhir'] ?? '',
                'nama_satker' => $_GET['nama_satker'] ?? '',   // DIGANTI: dari klpd ke nama_satker
                'tipe_swakelola' => $_GET['tipe_swakelola'] ?? '',
                'search' => $_GET['search'] ?? ''
            ];

            // Remove empty filters
            $filters = array_filter($filters, function ($value) {
                return $value !== '' && $value !== null;
            });

            // Pagination parameters
            $page = intval($_GET['page'] ?? 1);
            $limit = intval($_GET['limit'] ?? 50);
            $offset = ($page - 1) * $limit;

            // Get data and total count
            $data = $realisasiSwakelola->getRealisasiSeleksiData($filters, $limit, $offset);
            $total = $realisasiSwakelola->getTotalCount($filters);
            $totalPages = ceil($total / $limit);

            // Add row numbers
            foreach ($data as $key => $row) {
                $data[$key]['No'] = $offset + $key + 1;
            }
            
            echo json_encode([
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_records' => (int)$total,
                    'per_page' => $limit,
                    'has_next' => $page < $totalPages,
                    'has_prev' => $page > 1
                ],
                'filters_applied' => $filters  // BARU: Tampilkan filter yang diterapkan
            ]);
            break;

        case 'summary':
            // Get summary/statistics data dengan support filter bulan dan satker
            $filters = [
                'bulan' => $_GET['bulan'] ?? '',               // BARU: Filter bulan
                'tahun' => $_GET['tahun'] ?? '',
                'tanggal_awal' => $_GET['tanggal_awal'] ?? '',
                'tanggal_akhir' => $_GET['tanggal_akhir'] ?? '',
                'nama_satker' => $_GET['nama_satker'] ?? '',   // DIGANTI: dari klpd ke nama_satker
                'tipe_swakelola' => $_GET['tipe_swakelola'] ?? '',
                'search' => $_GET['search'] ?? ''
            ];

            // Remove empty filters
            $filters = array_filter($filters, function ($value) {
                return $value !== '' && $value !== null;
            });

            // Get all data for summary calculation
            $allData = $realisasiSwakelola->getAllDataForSummary($filters);
            $totalRecords = count($allData);
            
            // Initialize summary statistics
            $summary = [
                'total_paket' => $totalRecords,
                'total_pagu' => 0,
                'total_realisasi' => 0,
                'avg_pagu' => 0,
                'breakdown' => [
                    'tipe_swakelola' => [],
                    'satker' => [],              // DIGANTI: dari klpd ke satker
                ]
            ];

            // Calculate statistics
            foreach ($allData as $row) {
                $pagu = (float)($row['Nilai_Pagu'] ?? 0);
                $realisasi = (float)($row['Nilai_Total_Realisasi'] ?? 0);

                $summary['total_pagu'] += $pagu;
                $summary['total_realisasi'] += $realisasi;

                // Breakdown by Tipe Swakelola
                if (!empty($row['Tipe_Swakelola'])) {
                    $tipe = $row['Tipe_Swakelola'];
                    if (!isset($summary['breakdown']['tipe_swakelola'][$tipe])) {
                        $summary['breakdown']['tipe_swakelola'][$tipe] = ['count' => 0, 'total_pagu' => 0, 'total_realisasi' => 0];
                    }
                    $summary['breakdown']['tipe_swakelola'][$tipe]['count']++;
                    $summary['breakdown']['tipe_swakelola'][$tipe]['total_pagu'] += $pagu;
                    $summary['breakdown']['tipe_swakelola'][$tipe]['total_realisasi'] += $realisasi;
                }

                // DIGANTI: Breakdown by Satker (bukan KLPD)
                if (!empty($row['Nama_Satker'])) {
                    $satker = $row['Nama_Satker'];
                    if (!isset($summary['breakdown']['satker'][$satker])) {
                        $summary['breakdown']['satker'][$satker] = ['count' => 0, 'total_pagu' => 0, 'total_realisasi' => 0];
                    }
                    $summary['breakdown']['satker'][$satker]['count']++;
                    $summary['breakdown']['satker'][$satker]['total_pagu'] += $pagu;
                    $summary['breakdown']['satker'][$satker]['total_realisasi'] += $realisasi;
                }
            }

            // Calculate averages and percentage
            $summary['avg_pagu'] = $totalRecords > 0 ? $summary['total_pagu'] / $totalRecords : 0;
            $persentase_realisasi = 0;
            if ($summary['total_pagu'] > 0) {
                $persentase_realisasi = ($summary['total_realisasi'] / $summary['total_pagu']) * 100;
            }
            $summary['persentase_realisasi'] = round($persentase_realisasi, 2);
            
            // Sort breakdowns by total_pagu descending
            foreach ($summary['breakdown'] as &$breakdown) {
                uasort($breakdown, fn($a, $b) => $b['total_pagu'] <=> $a['total_pagu']);
            }

            echo json_encode([
                'success' => true,
                'summary' => $summary,
                'filters_applied' => $filters,
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
            ]);
            break;
        
        case 'options':
            // Get dropdown options - DIGANTI KLPD dengan Satker
            $tipeSwakelola = $realisasiSwakelola->getDistinctValues('Tipe_Swakelola');
            $satker = $realisasiSwakelola->getDistinctValues('Nama_Satker');  // DIGANTI
            $years = $realisasiSwakelola->getAvailableYears();

            echo json_encode([
                'success' => true,
                'options' => [
                    'tipe_swakelola' => $tipeSwakelola,
                    'satker' => $satker,                    // DIGANTI: dari klpd ke satker
                    'years' => $years
                ]
            ]);
            break;

        case 'export':
            // Export functionality dengan support filter bulan dan satker
            $filters = [
                'bulan' => $_GET['bulan'] ?? '',
                'tahun' => $_GET['tahun'] ?? '',
                'tanggal_awal' => $_GET['tanggal_awal'] ?? '',
                'tanggal_akhir' => $_GET['tanggal_akhir'] ?? '',
                'nama_satker' => $_GET['nama_satker'] ?? '',     // DIGANTI
                'tipe_swakelola' => $_GET['tipe_swakelola'] ?? '',
                'search' => $_GET['search'] ?? ''
            ];
            $filters = array_filter($filters);

            $format = $_GET['format'] ?? 'csv';
            $data = $realisasiSwakelola->getRealisasiSeleksiData($filters, 10000, 0);

            if ($format == 'csv') {
                // Tambahkan info bulan di nama file jika ada filter bulan
                $fileName = 'data_realisasi_swakelola';
                if (!empty($filters['bulan']) && !empty($filters['tahun'])) {
                    $namaBulan = [
                        '01' => 'januari', '02' => 'februari', '03' => 'maret',
                        '04' => 'april', '05' => 'mei', '06' => 'juni',
                        '07' => 'juli', '08' => 'agustus', '09' => 'september',
                        '10' => 'oktober', '11' => 'november', '12' => 'desember'
                    ];
                    $fileName .= '_' . $namaBulan[$filters['bulan']] . '_' . $filters['tahun'];
                } else {
                    $fileName .= '_' . date('Y-m-d');
                }
                
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="' . $fileName . '.csv"');

                $output = fopen('php://output', 'w');
                fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

                // Headers - DIGANTI KLPD dengan Satker
                fputcsv($output, [
                    'No', 'Nama Paket', 'Kode Paket', 'Kode RUP',
                    'Nama Satker', 'Tipe Swakelola', 'Nilai Pagu', 
                    'Nilai Realisasi', 'Nama Pelaksana'
                ]);

                foreach ($data as $index => $row) {
                    fputcsv($output, [
                        $index + 1,
                        $row['Nama_Paket'] ?? '',
                        $row['Kode_Paket'] ?? '',
                        $row['Kode_RUP'] ?? '',
                        $row['Nama_Satker'] ?? '',           // DIGANTI
                        $row['Tipe_Swakelola'] ?? '',
                        $row['Nilai_Pagu'] ?? 0,
                        $row['Nilai_Total_Realisasi'] ?? 0,
                        $row['Nama_Pelaksana'] ?? ''
                    ]);
                }

                fclose($output);
                exit;
            }
            break;

        default:
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action'
            ]);
            break;
    }
} catch (Exception $e) {
    error_log("API Error in realisasi_swakelola: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}