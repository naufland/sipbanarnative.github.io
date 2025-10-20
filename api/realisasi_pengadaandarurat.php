<?php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../includes/RealisasiPengadaanDaruratModel.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $realisasi = new RealisasiPengadaanDaruratModel($db);

    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? 'list';

    switch ($method) {
        case 'GET':
            switch ($action) {
                case 'list':
                    $filters = [
                        'bulan' => $_GET['bulan'] ?? '',
                        'tahun' => $_GET['tahun'] ?? '',
                        'jenis_pengadaan' => $_GET['jenis_pengadaan'] ?? '',
                        'satker' => $_GET['satker'] ?? '',
                        'metode_pengadaan' => $_GET['metode_pengadaan'] ?? '',
                        'search' => $_GET['search'] ?? ''
                    ];

                    $filters = array_filter($filters, function ($value) {
                        return $value !== '' && $value !== null;
                    });

                    $page = intval($_GET['page'] ?? 1);
                    $limit = intval($_GET['limit'] ?? 50);
                    $offset = ($page - 1) * $limit;

                    $data = $realisasi->getRealisasiData($filters, $limit, $offset);
                    $total = $realisasi->getTotalCount($filters);
                    $totalPages = ceil($total / $limit);

                    foreach ($data as $key => $row) {
                        $data[$key]['No'] = $offset + $key + 1;
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
                    ]);
                    break;

                case 'summary':
                    $filters = [
                        'bulan' => $_GET['bulan'] ?? '',
                        'tahun' => $_GET['tahun'] ?? '',
                        'jenis_pengadaan' => $_GET['jenis_pengadaan'] ?? '',
                        'satker' => $_GET['satker'] ?? '',
                        'metode_pengadaan' => $_GET['metode_pengadaan'] ?? '',
                        'search' => $_GET['search'] ?? ''
                    ];

                    $filters = array_filter($filters, function ($value) {
                        return $value !== '' && $value !== null;
                    });

                    $allData = $realisasi->getRealisasiData($filters, 1000000, 0);
                    $totalRecords = count($allData);

                    $totalPagu = 0;
                    $totalRealisasi = 0;
                    $totalPDN = 0;
                    $totalUMK = 0;
                    $jenisPengadaanStats = [];
                    $satkerStats = [];
                    $metodeStats = [];

                    foreach ($allData as $row) {
                        $totalPagu += (float)($row['Nilai_Pagu'] ?? 0);
                        $totalRealisasi += (float)($row['Nilai_Total_Realisasi'] ?? 0);
                        $totalPDN += (float)($row['Nilai_PDN'] ?? 0);
                        $totalUMK += (float)($row['Nilai_UMK'] ?? 0);

                        $jenis = $row['Jenis_Pengadaan'];
                        if (!isset($jenisPengadaanStats[$jenis])) {
                            $jenisPengadaanStats[$jenis] = ['count' => 0, 'total_realisasi' => 0];
                        }
                        $jenisPengadaanStats[$jenis]['count']++;
                        $jenisPengadaanStats[$jenis]['total_realisasi'] += (float)($row['Nilai_Total_Realisasi'] ?? 0);

                        $satker = $row['Nama_Satker'];
                        if (!isset($satkerStats[$satker])) {
                            $satkerStats[$satker] = ['count' => 0, 'total_realisasi' => 0];
                        }
                        $satkerStats[$satker]['count']++;
                        $satkerStats[$satker]['total_realisasi'] += (float)($row['Nilai_Total_Realisasi'] ?? 0);

                        $metode = $row['Metode_pengadaan'];
                        if (!isset($metodeStats[$metode])) {
                            $metodeStats[$metode] = ['count' => 0, 'total_realisasi' => 0];
                        }
                        $metodeStats[$metode]['count']++;
                        $metodeStats[$metode]['total_realisasi'] += (float)($row['Nilai_Total_Realisasi'] ?? 0);
                    }

                    // Hitung Efisiensi Anggaran
                    $efisiensiAnggaran = 0;
                    if ($totalPagu > 0) {
                        $efisiensiAnggaran = (($totalPagu - $totalRealisasi) / $totalPagu) * 100;
                    }

                    uasort($jenisPengadaanStats, function ($a, $b) {
                        return $b['total_realisasi'] - $a['total_realisasi'];
                    });
                    uasort($satkerStats, function ($a, $b) {
                        return $b['total_realisasi'] - $a['total_realisasi'];
                    });
                    uasort($metodeStats, function ($a, $b) {
                        return $b['total_realisasi'] - $a['total_realisasi'];
                    });

                    echo json_encode([
                        'success' => true,
                        'summary' => [
                            'total_paket' => $totalRecords,
                            'total_pagu' => $totalPagu,
                            'total_realisasi' => $totalRealisasi,
                            'total_pdn' => $totalPDN,
                            'total_umk' => $totalUMK,
                            'efisiensi_anggaran' => round($efisiensiAnggaran, 2), // Tambahan baru
                            'total_satker' => count($satkerStats)
                        ],
                        'breakdown' => [
                            'jenis_pengadaan' => $jenisPengadaanStats,
                            'satker' => $satkerStats,
                            'metode' => $metodeStats
                        ],
                        'filters_applied' => $filters,
                        'period_info' => [
                            'bulan' => $filters['bulan'] ?? null,
                            'tahun' => $filters['tahun'] ?? null,
                            'bulan_nama' => isset($filters['bulan']) ? [
                                '01' => 'Januari',
                                '02' => 'Februari',
                                '03' => 'Maret',
                                '04' => 'April',
                                '05' => 'Mei',
                                '06' => 'Juni',
                                '07' => 'Juli',
                                '08' => 'Agustus',
                                '09' => 'September',
                                '10' => 'Oktober',
                                '11' => 'November',
                                '12' => 'Desember'
                            ][$filters['bulan']] ?? null : null
                        ]
                    ]);
                    break;

                case 'options':
                    $jenisPengadaan = $realisasi->getDistinctValues('Jenis_Pengadaan');
                    $satker = $realisasi->getDistinctValues('Nama_Satker');
                    $metodePengadaan = $realisasi->getDistinctValues('Metode_pengadaan');
                    $years = $realisasi->getAvailableYears();

                    $tahunFilter = $_GET['tahun'] ?? null;
                    $months = $realisasi->getAvailableMonths($tahunFilter);

                    echo json_encode([
                        'success' => true,
                        'options' => [
                            'jenis_pengadaan' => $jenisPengadaan,
                            'satker' => $satker,
                            'metode_pengadaan' => $metodePengadaan,
                            'years' => $years,
                            'months' => $months
                        ]
                    ]);
                    break;

                case 'statistics':
                    $filters = [
                        'bulan' => $_GET['bulan'] ?? '',
                        'tahun' => $_GET['tahun'] ?? ''
                    ];
                    $filters = array_filter($filters);

                    $stats = $realisasi->getStatistics($filters);

                    echo json_encode([
                        'success' => true,
                        'statistics' => $stats,
                        'filters_applied' => $filters
                    ]);
                    break;

                case 'months':
                    $tahun = $_GET['tahun'] ?? null;
                    $months = $realisasi->getAvailableMonths($tahun);

                    $monthsWithNames = [];
                    $namaBulan = [
                        1 => 'Januari',
                        2 => 'Februari',
                        3 => 'Maret',
                        4 => 'April',
                        5 => 'Mei',
                        6 => 'Juni',
                        7 => 'Juli',
                        8 => 'Agustus',
                        9 => 'September',
                        10 => 'Oktober',
                        11 => 'November',
                        12 => 'Desember'
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
                    ]);
                    break;

                case 'export':
                    $filters = [
                        'bulan' => $_GET['bulan'] ?? '',
                        'tahun' => $_GET['tahun'] ?? '',
                        'jenis_pengadaan' => $_GET['jenis_pengadaan'] ?? '',
                        'satker' => $_GET['satker'] ?? '',
                        'metode_pengadaan' => $_GET['metode_pengadaan'] ?? '',
                        'search' => $_GET['search'] ?? ''
                    ];
                    $filters = array_filter($filters);

                    $format = $_GET['format'] ?? 'csv';
                    $data = $realisasi->getRealisasiData($filters, 10000, 0);

                    if ($format == 'csv') {
                        $fileName = 'realisasi_pengadaan_darurat';
                        if (!empty($filters['bulan']) && !empty($filters['tahun'])) {
                            $namaBulan = [
                                '01' => 'januari',
                                '02' => 'februari',
                                '03' => 'maret',
                                '04' => 'april',
                                '05' => 'mei',
                                '06' => 'juni',
                                '07' => 'juli',
                                '08' => 'agustus',
                                '09' => 'september',
                                '10' => 'oktober',
                                '11' => 'november',
                                '12' => 'desember'
                            ];
                            $fileName .= '_' . $namaBulan[$filters['bulan']] . '_' . $filters['tahun'];
                        } else {
                            $fileName .= '_' . date('Y-m-d');
                        }

                        header('Content-Type: text/csv; charset=utf-8');
                        header('Content-Disposition: attachment; filename="' . $fileName . '.csv"');

                        $output = fopen('php://output', 'w');
                        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

                        fputcsv($output, [
                            'No',
                            'Kode Paket',
                            'Nama Paket',
                            'Kode RUP',
                            'Nilai Pagu',
                            'Realisasi',
                            'PDN',
                            'UMK',
                            'Pemenang',
                            'Jenis Pengadaan',
                            'Metode',
                            'Satker'
                        ]);

                        foreach ($data as $index => $row) {
                            fputcsv($output, [
                                $index + 1,
                                $row['Kode_Paket'],
                                $row['Nama_Paket'],
                                $row['Kode_RUP'],
                                $row['Nilai_Pagu'],
                                $row['Nilai_Total_Realisasi'],
                                $row['Nilai_PDN'],
                                $row['Nilai_UMK'],
                                $row['Nama_Pemenang'],
                                $row['Jenis_Pengadaan'],
                                $row['Metode_pengadaan'],
                                $row['Nama_Satker']
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
            break;

        default:
            echo json_encode([
                'success' => false,
                'message' => 'Method not allowed'
            ]);
            break;
    }
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
