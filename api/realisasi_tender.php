<?php
// =================================================================
// == realisasi_tender.php (API) - LENGKAP ========================
// =================================================================

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
    
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? 'list';

    switch ($method) {
        case 'GET':
            switch ($action) {
                case 'list':
                    // Get filters from query parameters
                    $filters = [
                        'bulan' => $_GET['bulan'] ?? '',
                        'tahun' => $_GET['tahun'] ?? '',
                        'tahun_anggaran' => $_GET['tahun_anggaran'] ?? '',
                        'jenis_pengadaan' => $_GET['jenis_pengadaan'] ?? '',
                        'klpd' => $_GET['klpd'] ?? '',
                        'metode_pengadaan' => $_GET['metode_pengadaan'] ?? '',
                        'sumber_dana' => $_GET['sumber_dana'] ?? '',
                        'jenis_kontrak' => $_GET['jenis_kontrak'] ?? '',
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
                    $data = $realisasiTender->getRealisasiTenderData($filters, $limit, $offset);
                    $total = $realisasiTender->getTotalCount($filters);
                    $totalPages = ceil($total / $limit);

                    // Add row numbers
                    foreach ($data as $key => $row) {
                        $data[$key]['Row_Number'] = $offset + $key + 1;
                    }

                    echo json_encode([
                        'success' => true,
                        'data' => $data,
                        'pagination' => [
                            'current_page' => $page,
                            'total_pages' => $totalPages,
                            'total_records' => $total,
                            'per_page' => $limit,
                            'has_next' => $page < $totalPages,
                            'has_prev' => $page > 1
                        ],
                        'filters_applied' => $filters
                    ], JSON_UNESCAPED_UNICODE);
                    break;

                case 'summary':
                    // Get summary/statistics data dengan support filter bulan
                    $filters = [
                        'bulan' => $_GET['bulan'] ?? '',
                        'tahun' => $_GET['tahun'] ?? '',
                        'tahun_anggaran' => $_GET['tahun_anggaran'] ?? '',
                        'jenis_pengadaan' => $_GET['jenis_pengadaan'] ?? '',
                        'klpd' => $_GET['klpd'] ?? '',
                        'metode_pengadaan' => $_GET['metode_pengadaan'] ?? '',
                        'sumber_dana' => $_GET['sumber_dana'] ?? '',
                        'jenis_kontrak' => $_GET['jenis_kontrak'] ?? '',
                        'search' => $_GET['search'] ?? ''
                    ];

                    // Remove empty filters
                    $filters = array_filter($filters, function ($value) {
                        return $value !== '' && $value !== null;
                    });

                    // Get all data for summary calculation
                    $allData = $realisasiTender->getAllDataForSummary($filters);
                    $totalRecords = count($allData);
                    
                    // Calculate summary statistics
                    $totalPagu = 0;
                    $totalHPS = 0;
                    $totalKontrak = 0;
                    $totalPDN = 0;
                    $totalUMK = 0;
                    $jenisPengadaanStats = [];
                    $klpdStats = [];
                    $metodeStats = [];
                    $sumberDanaStats = [];
                    
                    foreach ($allData as $row) {
                        $paguValue = (float)($row['Nilai_Pagu'] ?? 0);
                        $hpsValue = (float)($row['Nilai_HPS'] ?? 0);
                        $kontrakValue = (float)($row['Nilai_Kontrak'] ?? 0);
                        
                        $totalPagu += $paguValue;
                        $totalHPS += $hpsValue;
                        $totalKontrak += $kontrakValue;
                        
                        // Count by Jenis Pengadaan
                        $jenis = $row['Jenis_Pengadaan'];
                        if (!isset($jenisPengadaanStats[$jenis])) {
                            $jenisPengadaanStats[$jenis] = ['count' => 0, 'total_pagu' => 0, 'total_kontrak' => 0];
                        }
                        $jenisPengadaanStats[$jenis]['count']++;
                        $jenisPengadaanStats[$jenis]['total_pagu'] += $paguValue;
                        $jenisPengadaanStats[$jenis]['total_kontrak'] += $kontrakValue;
                        
                        // Count by KLPD
                        $klpd = $row['KLPD'];
                        if (!isset($klpdStats[$klpd])) {
                            $klpdStats[$klpd] = ['count' => 0, 'total_pagu' => 0, 'total_kontrak' => 0];
                        }
                        $klpdStats[$klpd]['count']++;
                        $klpdStats[$klpd]['total_pagu'] += $paguValue;
                        $klpdStats[$klpd]['total_kontrak'] += $kontrakValue;
                        
                        // Count by Metode
                        $metode = $row['Metode_Pengadaan'];
                        if (!isset($metodeStats[$metode])) {
                            $metodeStats[$metode] = ['count' => 0, 'total_pagu' => 0, 'total_kontrak' => 0];
                        }
                        $metodeStats[$metode]['count']++;
                        $metodeStats[$metode]['total_pagu'] += $paguValue;
                        $metodeStats[$metode]['total_kontrak'] += $kontrakValue;
                        
                        // Count by Sumber Dana
                        $sumberDana = $row['Sumber_Dana'] ?? 'Tidak Diketahui';
                        if (!isset($sumberDanaStats[$sumberDana])) {
                            $sumberDanaStats[$sumberDana] = ['count' => 0, 'total_pagu' => 0, 'total_kontrak' => 0];
                        }
                        $sumberDanaStats[$sumberDana]['count']++;
                        $sumberDanaStats[$sumberDana]['total_pagu'] += $paguValue;
                        $sumberDanaStats[$sumberDana]['total_kontrak'] += $kontrakValue;
                    }
                    
                    // Sort arrays by total_pagu descending
                    uasort($jenisPengadaanStats, function($a, $b) {
                        return $b['total_pagu'] - $a['total_pagu'];
                    });
                    uasort($klpdStats, function($a, $b) {
                        return $b['total_pagu'] - $a['total_pagu'];
                    });
                    uasort($metodeStats, function($a, $b) {
                        return $b['total_pagu'] - $a['total_pagu'];
                    });
                    uasort($sumberDanaStats, function($a, $b) {
                        return $b['total_pagu'] - $a['total_pagu'];
                    });
                    
                    // Calculate efficiency
                    $efisiensi = 0;
                    if ($totalPagu > 0) {
                        $efisiensi = (($totalPagu - $totalKontrak) / $totalPagu) * 100;
                    }
                    
                    // Prepare response
                    echo json_encode([
                        'success' => true,
                        'summary' => [
                            'total_paket' => $totalRecords,
                            'total_pagu' => $totalPagu,
                            'total_hps' => $totalHPS,
                            'total_kontrak' => $totalKontrak,
                            'efisiensi_persen' => round($efisiensi, 2),
                            'total_klpd' => count($klpdStats)
                        ],
                        'breakdown' => [
                            'jenis_pengadaan' => $jenisPengadaanStats,
                            'klpd' => $klpdStats,
                            'metode_pengadaan' => $metodeStats,
                            'sumber_dana' => $sumberDanaStats
                        ],
                        'filters_applied' => $filters,
                        'period_info' => [
                            'bulan' => $filters['bulan'] ?? null,
                            'tahun' => $filters['tahun'] ?? null,
                            'bulan_nama' => isset($filters['bulan']) ? [
                                '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
                                '04' => 'April', '05' => 'Mei', '06' => 'Juni',
                                '07' => 'Juli', '08' => 'Agustus', '09' => 'September',
                                '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
                            ][$filters['bulan']] ?? null : null
                        ]
                    ], JSON_UNESCAPED_UNICODE);
                    break;

                case 'options':
                    // Get dropdown options
                    $jenisPengadaan = $realisasiTender->getDistinctValues('Jenis_Pengadaan');
                    $satker = $realisasiTender->getDistinctValues('Nama_Satker');
                    $metodePengadaan = $realisasiTender->getDistinctValues('Metode_Pengadaan');
                    $sumberDana = $realisasiTender->getDistinctValues('Sumber_Dana');
                    $jenisKontrak = $realisasiTender->getDistinctValues('Jenis_Kontrak');
                    $years = $realisasiTender->getAvailableYears();
                    
                    // Get available months
                    $tahunFilter = $_GET['tahun'] ?? null;
                    $months = $realisasiTender->getAvailableMonths($tahunFilter);

                    echo json_encode([
                        'success' => true,
                        'options' => [
                            'jenis_pengadaan' => $jenisPengadaan,
                            'Satker' => $satker,
                            'metode_pengadaan' => $metodePengadaan,
                            'sumber_dana' => $sumberDana,
                            'jenis_kontrak' => $jenisKontrak,
                            'years' => $years,
                            'months' => $months
                        ]
                    ], JSON_UNESCAPED_UNICODE);
                    break;

                case 'months':
                    // Endpoint khusus untuk mendapatkan daftar bulan
                    $tahun = $_GET['tahun'] ?? null;
                    $months = $realisasiTender->getAvailableMonths($tahun);
                    
                    // Format nama bulan
                    $monthsWithNames = [];
                    $namaBulan = [
                        1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
                        4 => 'April', 5 => 'Mei', 6 => 'Juni',
                        7 => 'Juli', 8 => 'Agustus', 9 => 'September',
                        10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                    ];
                    
                    foreach ($months as $month) {
                        $monthNum = array_search($month, $namaBulan);
                        if ($monthNum !== false) {
                            $monthsWithNames[] = [
                                'value' => str_pad($monthNum, 2, '0', STR_PAD_LEFT),
                                'label' => $month
                            ];
                        }
                    }

                    echo json_encode([
                        'success' => true,
                        'months' => $monthsWithNames,
                        'tahun' => $tahun
                    ], JSON_UNESCAPED_UNICODE);
                    break;

                case 'statistics':
                    $filters = [
                        'bulan' => $_GET['bulan'] ?? '',
                        'tahun' => $_GET['tahun'] ?? ''
                    ];
                    $filters = array_filter($filters);

                    $stats = $realisasiTender->getEfficiencyStats($filters);

                    echo json_encode([
                        'success' => true,
                        'statistics' => $stats,
                        'filters_applied' => $filters
                    ], JSON_UNESCAPED_UNICODE);
                    break;

                case 'export':
                    // Export functionality dengan support filter bulan
                    $filters = [
                        'bulan' => $_GET['bulan'] ?? '',
                        'tahun' => $_GET['tahun'] ?? '',
                        'jenis_pengadaan' => $_GET['jenis_pengadaan'] ?? '',
                        'klpd' => $_GET['klpd'] ?? '',
                        'metode_pengadaan' => $_GET['metode_pengadaan'] ?? '',
                        'sumber_dana' => $_GET['sumber_dana'] ?? '',
                        'search' => $_GET['search'] ?? ''
                    ];
                    $filters = array_filter($filters);

                    $format = $_GET['format'] ?? 'csv';
                    $data = $realisasiTender->getRealisasiTenderData($filters, 10000, 0);

                    if ($format == 'csv') {
                        $fileName = 'realisasi_tender';
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

                        // Headers
                        fputcsv($output, [
                            'No', 'Kode Tender', 'Nama Paket', 'Nilai Pagu', 'Nilai HPS', 'Nilai Kontrak',
                            'KLPD', 'Nama Satker', 'Jenis Pengadaan', 'Metode Pengadaan', 'Nama Pemenang',
                            'Sumber Dana', 'Jenis Kontrak', 'Tahap', 'Tahun Anggaran'
                        ]);

                        // Data rows
                        foreach ($data as $index => $row) {
                            fputcsv($output, [
                                $index + 1,
                                $row['Kode_Tender'] ?? '',
                                $row['Nama_Paket'] ?? '',
                                $row['Nilai_Pagu'] ?? 0,
                                $row['Nilai_HPS'] ?? 0,
                                $row['Nilai_Kontrak'] ?? 0,
                                $row['KLPD'] ?? '',
                                $row['Nama_Satker'] ?? '',
                                $row['Jenis_Pengadaan'] ?? '',
                                $row['Metode_Pengadaan'] ?? '',
                                $row['Nama_Pemenang'] ?? '',
                                $row['Sumber_Dana'] ?? '',
                                $row['Jenis_Kontrak'] ?? '',
                                $row['Tahap'] ?? '',
                                $row['Tahun_Anggaran'] ?? ''
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
                    ], JSON_UNESCAPED_UNICODE);
                    break;
            }
            break;

        default:
            echo json_encode([
                'success' => false,
                'message' => 'Method not allowed'
            ], JSON_UNESCAPED_UNICODE);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log("API Error in realisasi_tender: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>