<?php
// File: api/realisasi_penunjukan_langsung.php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../includes/RealisasiPenunjukanLangsungModel.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $realisasiPenunjukanLangsung = new RealisasiPenunjukanLangsungModel($db);
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
                'jenis_pengadaan' => $_GET['jenis_pengadaan'] ?? '',
                'status_paket' => $_GET['status_paket'] ?? '',
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
            $data = $realisasiPenunjukanLangsung->getRealisasiPenunjukanLangsungData($filters, $limit, $offset);
            $total = $realisasiPenunjukanLangsung->getTotalCount($filters);
            $totalPages = $total > 0 ? ceil($total / $limit) : 1;

            // Pastikan $data adalah array
            if (!is_array($data)) {
                $data = [];
            }

            // Add row numbers
            foreach ($data as $key => $row) {
                $data[$key]['No_Urut'] = $offset + $key + 1;
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
                'jenis_pengadaan' => $_GET['jenis_pengadaan'] ?? '',
                'status_paket' => $_GET['status_paket'] ?? '',
                'search' => $_GET['search'] ?? ''
            ];

            // Remove empty filters
            $filters = array_filter($filters, function ($value) {
                return $value !== '' && $value !== null;
            });

            // Get all data for summary calculation
            $allData = $realisasiPenunjukanLangsung->getAllDataForSummary($filters);
            
            // Pastikan $allData adalah array
            if (!is_array($allData)) {
                $allData = [];
            }
            
            $totalRecords = count($allData);
            
            // Initialize summary statistics
            $summary = [
                'total_paket' => $totalRecords,
                'total_pagu' => 0,
                'total_hps' => 0,
                'total_kontrak' => 0,
                'total_pdn' => 0,
                'total_umk' => 0,
                'breakdown' => [
                    'jenis_pengadaan' => [],
                    'satker' => [],              // DIGANTI: dari klpd ke satker
                    'status_paket' => []
                ]
            ];

            // Calculate statistics
            foreach ($allData as $row) {
                $pagu = (float)($row['Nilai_Pagu'] ?? 0);
                $hps = (float)($row['Nilai_HPS'] ?? 0);
                $kontrak = (float)($row['Nilai_Kontrak'] ?? 0);
                $pdn = (float)($row['Nilai_PDN'] ?? 0);
                $umk = (float)($row['Nilai_UMK'] ?? 0);

                $summary['total_pagu'] += $pagu;
                $summary['total_hps'] += $hps;
                $summary['total_kontrak'] += $kontrak;
                $summary['total_pdn'] += $pdn;
                $summary['total_umk'] += $umk;

                // Breakdown by Jenis Pengadaan
                if (!empty($row['Jenis_Pengadaan'])) {
                    $jenis = $row['Jenis_Pengadaan'];
                    if (!isset($summary['breakdown']['jenis_pengadaan'][$jenis])) {
                        $summary['breakdown']['jenis_pengadaan'][$jenis] = [
                            'count' => 0, 'total_pagu' => 0, 'total_hps' => 0,
                            'total_kontrak' => 0, 'total_pdn' => 0, 'total_umk' => 0
                        ];
                    }
                    $summary['breakdown']['jenis_pengadaan'][$jenis]['count']++;
                    $summary['breakdown']['jenis_pengadaan'][$jenis]['total_pagu'] += $pagu;
                    $summary['breakdown']['jenis_pengadaan'][$jenis]['total_hps'] += $hps;
                    $summary['breakdown']['jenis_pengadaan'][$jenis]['total_kontrak'] += $kontrak;
                    $summary['breakdown']['jenis_pengadaan'][$jenis]['total_pdn'] += $pdn;
                    $summary['breakdown']['jenis_pengadaan'][$jenis]['total_umk'] += $umk;
                }

                // DIGANTI: Breakdown by Satker (bukan KLPD)
                if (!empty($row['Nama_Satker'])) {
                    $satker = $row['Nama_Satker'];
                    if (!isset($summary['breakdown']['satker'][$satker])) {
                        $summary['breakdown']['satker'][$satker] = [
                            'count' => 0, 'total_pagu' => 0, 'total_hps' => 0,
                            'total_kontrak' => 0, 'total_pdn' => 0, 'total_umk' => 0
                        ];
                    }
                    $summary['breakdown']['satker'][$satker]['count']++;
                    $summary['breakdown']['satker'][$satker]['total_pagu'] += $pagu;
                    $summary['breakdown']['satker'][$satker]['total_hps'] += $hps;
                    $summary['breakdown']['satker'][$satker]['total_kontrak'] += $kontrak;
                    $summary['breakdown']['satker'][$satker]['total_pdn'] += $pdn;
                    $summary['breakdown']['satker'][$satker]['total_umk'] += $umk;
                }

                // Breakdown by Status Paket
                if (!empty($row['Status_Paket'])) {
                    $status = $row['Status_Paket'];
                    if (!isset($summary['breakdown']['status_paket'][$status])) {
                        $summary['breakdown']['status_paket'][$status] = [
                            'count' => 0, 'total_pagu' => 0, 'total_hps' => 0,
                            'total_kontrak' => 0, 'total_pdn' => 0, 'total_umk' => 0
                        ];
                    }
                    $summary['breakdown']['status_paket'][$status]['count']++;
                    $summary['breakdown']['status_paket'][$status]['total_pagu'] += $pagu;
                    $summary['breakdown']['status_paket'][$status]['total_hps'] += $hps;
                    $summary['breakdown']['status_paket'][$status]['total_kontrak'] += $kontrak;
                    $summary['breakdown']['status_paket'][$status]['total_pdn'] += $pdn;
                    $summary['breakdown']['status_paket'][$status]['total_umk'] += $umk;
                }
            }

            // Calculate percentages
            $persentase_realisasi = 0;
            if ($summary['total_pagu'] > 0) {
                $persentase_realisasi = ($summary['total_kontrak'] / $summary['total_pagu']) * 100;
            }
            
            $persentase_pdn = 0;
            if ($summary['total_kontrak'] > 0) {
                $persentase_pdn = ($summary['total_pdn'] / $summary['total_kontrak']) * 100;
            }
            
            $persentase_umk = 0;
            if ($summary['total_kontrak'] > 0) {
                $persentase_umk = ($summary['total_umk'] / $summary['total_kontrak']) * 100;
            }
            
            $summary['persentase_realisasi'] = round($persentase_realisasi, 2);
            $summary['persentase_pdn'] = round($persentase_pdn, 2);
            $summary['persentase_umk'] = round($persentase_umk, 2);
            
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
            $jenisPengadaan = $realisasiPenunjukanLangsung->getDistinctValues('Jenis_Pengadaan');
            $satker = $realisasiPenunjukanLangsung->getDistinctValues('Nama_Satker');  // DIGANTI
            $statusPaket = $realisasiPenunjukanLangsung->getDistinctValues('Status_Paket');
            $years = $realisasiPenunjukanLangsung->getAvailableYears();

            echo json_encode([
                'success' => true,
                'options' => [
                    'jenis_pengadaan' => $jenisPengadaan,
                    'nama_satker' => $satker,                    // DIGANTI: dari klpd ke satker
                    'status_paket' => $statusPaket,
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
                'jenis_pengadaan' => $_GET['jenis_pengadaan'] ?? '',
                'status_paket' => $_GET['status_paket'] ?? '',
                'search' => $_GET['search'] ?? ''
            ];
            $filters = array_filter($filters);

            $format = $_GET['format'] ?? 'csv';
            $data = $realisasiPenunjukanLangsung->getRealisasiPenunjukanLangsungData($filters, 10000, 0);

            if ($format == 'csv') {
                // Tambahkan info bulan di nama file jika ada filter bulan
                $fileName = 'data_realisasi_penunjukan_langsung';
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
                    'Nama Satker', 'Jenis Pengadaan', 'Nilai Pagu',
                    'Nilai HPS', 'Nilai Kontrak', 'Nilai PDN', 'Nilai UMK',
                    'Nama Pemenang', 'Status Paket'
                ]);

                foreach ($data as $index => $row) {
                    fputcsv($output, [
                        $index + 1,
                        $row['Nama_Paket'] ?? '',
                        $row['Kode_Paket'] ?? '',
                        $row['Kode_RUP'] ?? '',
                        $row['Nama_Satker'] ?? '',           // DIGANTI
                        $row['Jenis_Pengadaan'] ?? '',
                        $row['Nilai_Pagu'] ?? 0,
                        $row['Nilai_HPS'] ?? 0,
                        $row['Nilai_Kontrak'] ?? 0,
                        $row['Nilai_PDN'] ?? 0,
                        $row['Nilai_UMK'] ?? 0,
                        $row['Nama_Pemenang'] ?? '',
                        $row['Status_Paket'] ?? ''
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
    error_log("API Error in realisasi_penunjukan_langsung: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}