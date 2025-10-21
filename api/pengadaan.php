<?php

use App\Http\Controllers\PengadaanController;

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../includes/PengadaanModel.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $pengadaan = new PengadaanModel($db);

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
                        'tanggal_awal' => $_GET['tanggal_awal'] ?? '',
                        'tanggal_akhir' => $_GET['tanggal_akhir'] ?? '',
                        'jenis_pengadaan' => $_GET['jenis_pengadaan'] ?? '',
                        'satuan_kerja' => $_GET['satuan_kerja'] ?? '',  // PERUBAHAN: klpd → satuan_kerja
                        'usaha_kecil' => $_GET['usaha_kecil'] ?? '',
                        'metode' => $_GET['metode'] ?? '',
                        'perubahan' => $_GET['perubahan'] ?? '',
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
                    $data = $pengadaan->getPengadaanData($filters, $limit, $offset);
                    $total = $pengadaan->getTotalCount($filters);
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
                            'total_records' => $total,
                            'per_page' => $limit,
                            'has_next' => $page < $totalPages,
                            'has_prev' => $page > 1
                        ],
                        'filters_applied' => $filters
                    ]);
                    break;

                case 'summary':
                    // Get summary/statistics data dengan support filter bulan dan perubahan
                    $filters = [
                        'bulan' => $_GET['bulan'] ?? '',
                        'tahun' => $_GET['tahun'] ?? '',
                        'tanggal_awal' => $_GET['tanggal_awal'] ?? '',
                        'tanggal_akhir' => $_GET['tanggal_akhir'] ?? '',
                        'jenis_pengadaan' => $_GET['jenis_pengadaan'] ?? '',
                        'satuan_kerja' => $_GET['satuan_kerja'] ?? '',  // PERUBAHAN: klpd → satuan_kerja
                        'usaha_kecil' => $_GET['usaha_kecil'] ?? '',
                        'metode' => $_GET['metode'] ?? '',
                        'perubahan' => $_GET['perubahan'] ?? '',
                        'search' => $_GET['search'] ?? ''
                    ];

                    // Remove empty filters
                    $filters = array_filter($filters, function ($value) {
                        return $value !== '' && $value !== null;
                    });

                    // Get all data for summary calculation (without pagination)
                    $allData = $pengadaan->getPengadaanData($filters, 1000000, 0);
                    $totalRecords = count($allData);
                    
                    // Calculate summary statistics
                    $totalPagu = 0;
                    $jenisPengadaanStats = [];
                    $satuanKerjaStats = [];  // PERUBAHAN: klpdStats → satuanKerjaStats
                    $metodeStats = [];
                    $usahaKecilStats = [];
                    $perubahanStats = [];
                    
                    foreach ($allData as $row) {
                        // Calculate total pagu - remove non-numeric characters
                        $paguValue = preg_replace('/[^\d]/', '', $row['Pagu_Rp']);
                        $paguValue = (int)$paguValue;
                        $totalPagu += $paguValue;
                        
                        // Count by Jenis Pengadaan
                        $jenis = $row['Jenis_Pengadaan'];
                        if (!isset($jenisPengadaanStats[$jenis])) {
                            $jenisPengadaanStats[$jenis] = ['count' => 0, 'total_pagu' => 0];
                        }
                        $jenisPengadaanStats[$jenis]['count']++;
                        $jenisPengadaanStats[$jenis]['total_pagu'] += $paguValue;
                        
                        // PERUBAHAN: Count by Satuan Kerja (bukan KLPD)
                        $satuanKerja = $row['Satuan_Kerja'];
                        if (!isset($satuanKerjaStats[$satuanKerja])) {
                            $satuanKerjaStats[$satuanKerja] = ['count' => 0, 'total_pagu' => 0];
                        }
                        $satuanKerjaStats[$satuanKerja]['count']++;
                        $satuanKerjaStats[$satuanKerja]['total_pagu'] += $paguValue;
                        
                        // Count by Metode
                        $metode = $row['Metode'];
                        if (!isset($metodeStats[$metode])) {
                            $metodeStats[$metode] = ['count' => 0, 'total_pagu' => 0];
                        }
                        $metodeStats[$metode]['count']++;
                        $metodeStats[$metode]['total_pagu'] += $paguValue;
                        
                        // Count by Usaha Kecil
                        $usahaKecil = $row['Usaha_Kecil'];
                        if (!isset($usahaKecilStats[$usahaKecil])) {
                            $usahaKecilStats[$usahaKecil] = ['count' => 0, 'total_pagu' => 0];
                        }
                        $usahaKecilStats[$usahaKecil]['count']++;
                        $usahaKecilStats[$usahaKecil]['total_pagu'] += $paguValue;
                        
                        // Count by Perubahan
                        $perubahan = $row['perubahan'] ?? 'Tidak';
                        if (!isset($perubahanStats[$perubahan])) {
                            $perubahanStats[$perubahan] = ['count' => 0, 'total_pagu' => 0];
                        }
                        $perubahanStats[$perubahan]['count']++;
                        $perubahanStats[$perubahan]['total_pagu'] += $paguValue;
                    }
                    
                    // Sort arrays by total_pagu descending
                    uasort($jenisPengadaanStats, function($a, $b) {
                        return $b['total_pagu'] - $a['total_pagu'];
                    });
                    uasort($satuanKerjaStats, function($a, $b) {
                        return $b['total_pagu'] - $a['total_pagu'];
                    });
                    uasort($metodeStats, function($a, $b) {
                        return $b['total_pagu'] - $a['total_pagu'];
                    });
                    uasort($usahaKecilStats, function($a, $b) {
                        return $b['total_pagu'] - $a['total_pagu'];
                    });
                    uasort($perubahanStats, function($a, $b) {
                        return $b['total_pagu'] - $a['total_pagu'];
                    });
                    
                    // Calculate averages
                    $avgPagu = $totalRecords > 0 ? $totalPagu / $totalRecords : 0;
                    
                    // Prepare response
                    echo json_encode([
                        'success' => true,
                        'summary' => [
                            'total_paket' => $totalRecords,
                            'total_pagu' => $totalPagu,
                            'avg_pagu' => $avgPagu,
                            'total_satker' => count($satuanKerjaStats)  // PERUBAHAN: total_klpd → total_satker
                        ],
                        'breakdown' => [
                            'jenis_pengadaan' => $jenisPengadaanStats,
                            'satuan_kerja' => $satuanKerjaStats,  // PERUBAHAN: klpd → satuan_kerja
                            'metode' => $metodeStats,
                            'usaha_kecil' => $usahaKecilStats,
                            'perubahan' => $perubahanStats
                        ],
                        'filters_applied' => $filters,
                        'period_info' => [
                            'bulan' => $filters['bulan'] ?? null,
                            'tahun' => $filters['tahun'] ?? null,
                            'perubahan' => $filters['perubahan'] ?? null,
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
                    // Get dropdown options
                    $jenisPengadaan = $pengadaan->getDistinctValues('Jenis_Pengadaan');
                    $satuanKerja = $pengadaan->getAvailableSatuanKerja();  // PERUBAHAN: Gunakan method baru
                    $usahaKecil = $pengadaan->getDistinctValues('Usaha_Kecil');
                    $metode = $pengadaan->getDistinctValues('Metode');
                    $perubahan = $pengadaan->getDistinctValues('perubahan');
                    $years = $pengadaan->getAvailableYears();
                    
                    $tahunFilter = $_GET['tahun'] ?? null;
                    $months = $pengadaan->getAvailableMonths($tahunFilter);

                    echo json_encode([
                        'success' => true,
                        'options' => [
                            'jenis_pengadaan' => $jenisPengadaan,
                            'satuan_kerja' => $satuanKerja,  // PERUBAHAN: klpd → satuan_kerja
                            'usaha_kecil' => $usahaKecil,
                            'metode' => $metode,
                            'perubahan' => $perubahan,
                            'years' => $years,
                            'months' => $months
                        ]
                    ]);
                    break;

                case 'statistics':
                    $filters = [
                        'bulan' => $_GET['bulan'] ?? '',
                        'tahun' => $_GET['tahun'] ?? '',
                        'perubahan' => $_GET['perubahan'] ?? ''
                    ];
                    $filters = array_filter($filters);

                    $stats = $pengadaan->getStatistics($filters);

                    echo json_encode([
                        'success' => true,
                        'statistics' => $stats,
                        'filters_applied' => $filters
                    ]);
                    break;

                case 'months':
                    $tahun = $_GET['tahun'] ?? null;
                    $months = $pengadaan->getAvailableMonths($tahun);
                    
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
                    ]);
                    break;

                case 'export':
                    // Export functionality dengan support filter bulan dan perubahan
                    $filters = [
                        'bulan' => $_GET['bulan'] ?? '',
                        'tahun' => $_GET['tahun'] ?? '',
                        'tanggal_awal' => $_GET['tanggal_awal'] ?? '',
                        'tanggal_akhir' => $_GET['tanggal_akhir'] ?? '',
                        'jenis_pengadaan' => $_GET['jenis_pengadaan'] ?? '',
                        'satuan_kerja' => $_GET['satuan_kerja'] ?? '',  // PERUBAHAN: klpd → satuan_kerja
                        'metode' => $_GET['metode'] ?? '',
                        'perubahan' => $_GET['perubahan'] ?? '',
                        'search' => $_GET['search'] ?? ''
                    ];
                    $filters = array_filter($filters);

                    $format = $_GET['format'] ?? 'csv';
                    $data = $pengadaan->getPengadaanData($filters, 10000, 0);

                    if ($format == 'csv') {
                        $fileName = 'data_pengadaan';
                        if (!empty($filters['bulan']) && !empty($filters['tahun'])) {
                            $namaBulan = [
                                '01' => 'januari', '02' => 'februari', '03' => 'maret',
                                '04' => 'april', '05' => 'mei', '06' => 'juni',
                                '07' => 'juli', '08' => 'agustus', '09' => 'september',
                                '10' => 'oktober', '11' => 'november', '12' => 'desember'
                            ];
                            $fileName .= '_' . $namaBulan[$filters['bulan']] . '_' . $filters['tahun'];
                        }
                        // Tambahkan status perubahan di nama file
                        if (!empty($filters['perubahan'])) {
                            $fileName .= '_' . strtolower($filters['perubahan']);
                        }
                        
                        header('Content-Type: text/csv; charset=utf-8');
                        header('Content-Disposition: attachment; filename="' . $fileName . '.csv"');

                        $output = fopen('php://output', 'w');
                        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

                        // Headers
                        fputcsv($output, [
                            'No',
                            'Paket',
                            'Pagu (Rp)',
                            'Jenis Pengadaan',
                            'Perubahan',
                            'Produk Dalam Negeri',
                            'Usaha Kecil',
                            'Metode',
                            'Pemilihan',
                            'Satuan Kerja',  // PERUBAHAN: Hanya Satuan Kerja (KLPD dihapus)
                            'Lokasi',
                            'ID'
                        ]);

                        // Data rows
                        foreach ($data as $index => $row) {
                            fputcsv($output, [
                                $index + 1,
                                $row['Paket'],
                                $row['Pagu_Rp'],
                                $row['Jenis_Pengadaan'],
                                $row['perubahan'] ?? 'Tidak',
                                $row['Produk_Dalam_Negeri'],
                                $row['Usaha_Kecil'],
                                $row['Metode'],
                                $row['Pemilihan'],
                                $row['Satuan_Kerja'],  // PERUBAHAN: KLPD dihapus
                                $row['Lokasi'],
                                $row['ID']
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