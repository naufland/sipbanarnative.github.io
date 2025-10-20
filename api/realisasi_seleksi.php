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

    switch ($action) {
        case 'list':
            // Get filters from query parameters - DITAMBAHKAN BULAN DAN TAHUN
            $filters = [
                'bulan' => $_GET['bulan'] ?? '',               // BARU: Filter bulan
                'tahun' => $_GET['tahun'] ?? '',               // Filter tahun
                'tanggal_awal' => $_GET['tanggal_awal'] ?? '',
                'tanggal_akhir' => $_GET['tanggal_akhir'] ?? '',
                'jenis_pengadaan' => $_GET['jenis_pengadaan'] ?? '',
                'nama_satker' => $_GET['nama_satker'] ?? '',   // DIGANTI: dari klpd ke nama_satker
                'metode_pengadaan' => $_GET['metode_pengadaan'] ?? '',
                'search' => $_GET['search'] ?? ''
            ];

            // Remove empty filters
            $filters = array_filter($filters, function ($value) {
                return $value !== '' && $value !== null;
            });

            // Pagination parameters
            $page = intval($_GET['page'] ?? 1);
            $limit = intval($_GET['limit'] ?? 1000000);
            $offset = ($page - 1) * $limit;

            // Get data and total count
            $data = $realisasiTender->getRealisasiSeleksiData($filters, $limit, $offset);
            $total = $realisasiTender->getTotalCount($filters);
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
                'jenis_pengadaan' => $_GET['jenis_pengadaan'] ?? '',
                'nama_satker' => $_GET['nama_satker'] ?? '',   // DIGANTI: dari klpd ke nama_satker
                'metode_pengadaan' => $_GET['metode_pengadaan'] ?? '',
                'search' => $_GET['search'] ?? ''
            ];

            // Remove empty filters
            $filters = array_filter($filters, function ($value) {
                return $value !== '' && $value !== null;
            });

            // Get all data for summary calculation
            $allData = $realisasiTender->getAllDataForSummary($filters);
            $totalRecords = count($allData);
            
            // Initialize summary statistics
            $summary = [
                'total_paket' => $totalRecords,
                'total_pagu' => 0,
                'total_hps' => 0,
                'total_kontrak' => 0,
                'avg_pagu' => 0,
                'breakdown' => [
                    'jenis_pengadaan' => [],
                    'satker' => [],              // DIGANTI: dari klpd ke satker
                    'metode_pengadaan' => []
                ]
            ];

            // Calculate statistics
            foreach ($allData as $row) {
                $pagu = (float)($row['Nilai_Pagu'] ?? 0);
                $hps = (float)($row['Nilai_HPS'] ?? 0);
                $kontrak = (float)($row['Nilai_Kontrak'] ?? 0);

                $summary['total_pagu'] += $pagu;
                $summary['total_hps'] += $hps;
                $summary['total_kontrak'] += $kontrak;

                // Breakdown by Jenis Pengadaan
                if (!empty($row['Jenis_Pengadaan'])) {
                    $jenis = $row['Jenis_Pengadaan'];
                    if (!isset($summary['breakdown']['jenis_pengadaan'][$jenis])) {
                        $summary['breakdown']['jenis_pengadaan'][$jenis] = ['count' => 0, 'total_pagu' => 0];
                    }
                    $summary['breakdown']['jenis_pengadaan'][$jenis]['count']++;
                    $summary['breakdown']['jenis_pengadaan'][$jenis]['total_pagu'] += $pagu;
                }

                // DIGANTI: Breakdown by Satker (bukan KLPD)
                if (!empty($row['Nama_Satker'])) {
                    $satker = $row['Nama_Satker'];
                    if (!isset($summary['breakdown']['satker'][$satker])) {
                        $summary['breakdown']['satker'][$satker] = ['count' => 0, 'total_pagu' => 0];
                    }
                    $summary['breakdown']['satker'][$satker]['count']++;
                    $summary['breakdown']['satker'][$satker]['total_pagu'] += $pagu;
                }

                // Breakdown by Metode Pengadaan
                if (!empty($row['Metode_Pengadaan'])) {
                    $metode = $row['Metode_Pengadaan'];
                    if (!isset($summary['breakdown']['metode_pengadaan'][$metode])) {
                        $summary['breakdown']['metode_pengadaan'][$metode] = ['count' => 0, 'total_pagu' => 0];
                    }
                    $summary['breakdown']['metode_pengadaan'][$metode]['count']++;
                    $summary['breakdown']['metode_pengadaan'][$metode]['total_pagu'] += $pagu;
                }
            }

            // Calculate averages
            $summary['avg_pagu'] = $totalRecords > 0 ? $summary['total_pagu'] / $totalRecords : 0;
            
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
            $jenisPengadaan = $realisasiTender->getDistinctValues('Jenis_Pengadaan');
            $satker = $realisasiTender->getDistinctValues('Nama_Satker');  // DIGANTI
            $metodePengadaan = $realisasiTender->getDistinctValues('Metode_Pengadaan');
            $years = $realisasiTender->getAvailableYears();

            echo json_encode([
                'success' => true,
                'options' => [
                    'jenis_pengadaan' => $jenisPengadaan,
                    'satker' => $satker,                    // DIGANTI: dari klpd ke satker
                    'metode_pengadaan' => $metodePengadaan,
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
                'jenis_pengadaan' => $_GET['jenis_pengadaan'] ?? '',
                'nama_satker' => $_GET['nama_satker'] ?? '',     // DIGANTI
                'metode_pengadaan' => $_GET['metode_pengadaan'] ?? '',
                'search' => $_GET['search'] ?? ''
            ];
            $filters = array_filter($filters);

            $format = $_GET['format'] ?? 'csv';
            $data = $realisasiTender->getRealisasiSeleksiData($filters, 10000, 0);

            if ($format == 'csv') {
                // Tambahkan info bulan di nama file jika ada filter bulan
                $fileName = 'data_realisasi_tender';
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
                    'No', 'Nama Paket', 'Jenis Pengadaan', 'Metode Pengadaan',
                    'Nama Satker', 'Nilai Pagu', 'Nilai HPS', 'Nilai Kontrak',
                    'Pemenang', 'Waktu Pemilihan', 'Tanggal Kontrak'
                ]);

                foreach ($data as $index => $row) {
                    fputcsv($output, [
                        $index + 1,
                        $row['Nama_Paket'] ?? '',
                        $row['Jenis_Pengadaan'] ?? '',
                        $row['Metode_Pengadaan'] ?? '',
                        $row['Nama_Satker'] ?? '',           // DIGANTI
                        $row['Nilai_Pagu'] ?? 0,
                        $row['Nilai_HPS'] ?? 0,
                        $row['Nilai_Kontrak'] ?? 0,
                        $row['Nama_Pemenang'] ?? '',
                        $row['Waktu_Pemilihan'] ?? '',
                        $row['Tanggal_Kontrak'] ?? ''
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
    error_log("API Error in realisasi_tender: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}