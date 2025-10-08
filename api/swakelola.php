<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../includes/SwakelolaModel.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $swakelola = new SwakelolaModel($db);

    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? 'list';

    switch ($method) {
        case 'GET':
            switch ($action) {
                case 'list':
                    // FILTER LENGKAP DENGAN BULAN DAN TAHUN
                    $filters = [
                        // Filter Bulan & Tahun (PRIORITAS)
                        'bulan'           => $_GET['bulan'] ?? '',           // Format: 01-12 atau 1-12
                        'tahun'           => $_GET['tahun'] ?? '',           // Format: 2024
                        
                        // Filter Range Tanggal (Opsional)
                        'tanggal_awal'    => $_GET['tanggal_awal'] ?? '',
                        'tanggal_akhir'   => $_GET['tanggal_akhir'] ?? '',
                        
                        // Filter Tipe Swakelola (mendukung 2 parameter name)
                        'tipe_swakelola'  => $_GET['tipe_swakelola'] ?? ($_GET['jenis_pengadaan'] ?? ''),
                        
                        // Filter KLPD & Satuan Kerja
                        'klpd'            => $_GET['klpd'] ?? '',
                        'satuan_kerja'    => $_GET['satuan_kerja'] ?? '',
                        
                        // Filter Range Pagu
                        'pagu_min'        => $_GET['pagu_min'] ?? '',
                        'pagu_max'        => $_GET['pagu_max'] ?? '',
                        
                        // Filter Pencarian
                        'search'          => $_GET['search'] ?? ''
                    ];

                    // Hapus nilai kosong
                    $filters = array_filter($filters, fn($value) => $value !== '' && $value !== null);

                    // Pagination
                    $page   = intval($_GET['page'] ?? 1);
                    $limit  = intval($_GET['limit'] ?? 25);
                    $offset = ($page - 1) * $limit;

                    // Query data
                    $data  = $swakelola->getSwakelolaData($filters, $limit, $offset);
                    $total = $swakelola->getTotalCount($filters);
                    $totalPages = ceil($total / $limit);

                    // Get options berdasarkan filter bulan/tahun yang aktif
                    $optionFilters = [];
                    if (!empty($filters['bulan'])) {
                        $optionFilters['bulan'] = $filters['bulan'];
                    }
                    if (!empty($filters['tahun'])) {
                        $optionFilters['tahun'] = $filters['tahun'];
                    }

                    echo json_encode([
                        'success' => true,
                        'data'    => $data,
                        'filters' => $filters, // Kirim balik filter yang digunakan
                        'options' => [
                            'jenis_pengadaan' => $swakelola->getDistinctValues('Tipe_Swakelola', $optionFilters),
                            'klpd'            => $swakelola->getDistinctValues('KLPD', $optionFilters),
                            'satuan_kerja'    => $swakelola->getDistinctValues('Satuan_Kerja', $optionFilters),
                        ],
                        'pagination' => [
                            'current_page'  => $page,
                            'total_pages'   => $totalPages,
                            'total_records' => $total,
                            'per_page'      => $limit
                        ],
                        'period' => [ // Informasi periode
                            'bulan' => $filters['bulan'] ?? null,
                            'tahun' => $filters['tahun'] ?? null
                        ]
                    ], JSON_PRETTY_PRINT);
                    break;

                case 'summary':
                    // SUMMARY DENGAN FILTER BULAN DAN TAHUN
                    $filters = [
                        'bulan'           => $_GET['bulan'] ?? '',
                        'tahun'           => $_GET['tahun'] ?? '',
                        'tanggal_awal'    => $_GET['tanggal_awal'] ?? '',
                        'tanggal_akhir'   => $_GET['tanggal_akhir'] ?? '',
                        'tipe_swakelola'  => $_GET['tipe_swakelola'] ?? ($_GET['jenis_pengadaan'] ?? ''),
                        'klpd'            => $_GET['klpd'] ?? '',
                        'satuan_kerja'    => $_GET['satuan_kerja'] ?? '',
                        'pagu_min'        => $_GET['pagu_min'] ?? '',
                        'pagu_max'        => $_GET['pagu_max'] ?? '',
                        'search'          => $_GET['search'] ?? ''
                    ];
                    $filters = array_filter($filters, fn($value) => $value !== '' && $value !== null);

                    // Panggil fungsi summary dari model
                    $summary = $swakelola->getSummary($filters);

                    echo json_encode([
                        'success' => true,
                        'summary' => $summary,
                        'period' => [
                            'bulan' => $filters['bulan'] ?? null,
                            'tahun' => $filters['tahun'] ?? null
                        ]
                    ], JSON_PRETTY_PRINT);
                    break;

                case 'options':
                    // OPTIONS DENGAN FILTER BULAN/TAHUN (OPSIONAL)
                    $filters = [];
                    if (!empty($_GET['bulan'])) {
                        $filters['bulan'] = $_GET['bulan'];
                    }
                    if (!empty($_GET['tahun'])) {
                        $filters['tahun'] = $_GET['tahun'];
                    }

                    echo json_encode([
                        'success' => true,
                        'options' => [
                            'jenis_pengadaan' => $swakelola->getDistinctValues('Tipe_Swakelola', $filters),
                            'klpd'            => $swakelola->getDistinctValues('KLPD', $filters),
                            'satuan_kerja'    => $swakelola->getDistinctValues('Satuan_Kerja', $filters),
                        ],
                        'years' => $swakelola->getAvailableYears(),
                        'period' => [
                            'bulan' => $filters['bulan'] ?? null,
                            'tahun' => $filters['tahun'] ?? null
                        ]
                    ], JSON_PRETTY_PRINT);
                    break;

                case 'statistics':
                    // STATISTIK DENGAN FILTER BULAN DAN TAHUN
                    $filters = [
                        'bulan'        => $_GET['bulan'] ?? '',
                        'tahun'        => $_GET['tahun'] ?? '',
                        'tanggal_awal' => $_GET['tanggal_awal'] ?? '',
                        'tanggal_akhir'=> $_GET['tanggal_akhir'] ?? ''
                    ];
                    $filters = array_filter($filters, fn($value) => $value !== '' && $value !== null);

                    $stats = $swakelola->getStatistics($filters);

                    echo json_encode([
                        'success' => true,
                        'statistics' => $stats,
                        'period' => [
                            'bulan' => $filters['bulan'] ?? null,
                            'tahun' => $filters['tahun'] ?? null
                        ]
                    ], JSON_PRETTY_PRINT);
                    break;

                case 'monthly':
                    // STATISTIK BULANAN UNTUK TAHUN TERTENTU
                    $tahun = $_GET['tahun'] ?? date('Y');
                    $monthlyStats = $swakelola->getMonthlyStatistics($tahun);

                    echo json_encode([
                        'success' => true,
                        'data' => $monthlyStats,
                        'tahun' => $tahun
                    ], JSON_PRETTY_PRINT);
                    break;

                case 'klpd_statistics':
                    // STATISTIK PER KLPD DENGAN FILTER BULAN
                    $filters = [
                        'bulan' => $_GET['bulan'] ?? '',
                        'tahun' => $_GET['tahun'] ?? ''
                    ];
                    $filters = array_filter($filters, fn($value) => $value !== '' && $value !== null);

                    $klpdStats = $swakelola->getKLPDStatistics($filters);

                    echo json_encode([
                        'success' => true,
                        'data' => $klpdStats,
                        'period' => [
                            'bulan' => $filters['bulan'] ?? null,
                            'tahun' => $filters['tahun'] ?? null
                        ]
                    ], JSON_PRETTY_PRINT);
                    break;

                case 'check_data':
                    // CEK KETERSEDIAAN DATA UNTUK BULAN TERTENTU
                    $bulan = $_GET['bulan'] ?? '';
                    $tahun = $_GET['tahun'] ?? date('Y');

                    if (empty($bulan)) {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Parameter bulan diperlukan'
                        ]);
                        break;
                    }

                    $hasData = $swakelola->hasDataForMonth($bulan, $tahun);

                    echo json_encode([
                        'success' => true,
                        'has_data' => $hasData,
                        'bulan' => $bulan,
                        'tahun' => $tahun
                    ], JSON_PRETTY_PRINT);
                    break;

                case 'available_months':
                    // DAFTAR BULAN YANG MEMILIKI DATA
                    $tahun = $_GET['tahun'] ?? date('Y');
                    $months = $swakelola->getAvailableMonths($tahun);

                    // Mapping bulan ke nama
                    $namaBulan = [
                        '1' => 'Januari', '2' => 'Februari', '3' => 'Maret',
                        '4' => 'April', '5' => 'Mei', '6' => 'Juni',
                        '7' => 'Juli', '8' => 'Agustus', '9' => 'September',
                        '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
                    ];

                    $formattedMonths = array_map(function($month) use ($namaBulan) {
                        return [
                            'value' => str_pad($month, 2, '0', STR_PAD_LEFT),
                            'label' => $namaBulan[$month] ?? 'Unknown'
                        ];
                    }, $months);

                    echo json_encode([
                        'success' => true,
                        'tahun' => $tahun,
                        'months' => $formattedMonths
                    ], JSON_PRETTY_PRINT);
                    break;

                case 'export':
                    // EXPORT DATA DENGAN FILTER BULAN
                    $filters = [
                        'bulan'          => $_GET['bulan'] ?? '',
                        'tahun'          => $_GET['tahun'] ?? '',
                        'tipe_swakelola' => $_GET['tipe_swakelola'] ?? '',
                        'klpd'           => $_GET['klpd'] ?? ''
                    ];
                    $filters = array_filter($filters, fn($value) => $value !== '' && $value !== null);

                    $exportData = $swakelola->exportData($filters);

                    echo json_encode([
                        'success' => true,
                        'data' => $exportData,
                        'total_records' => count($exportData),
                        'period' => [
                            'bulan' => $filters['bulan'] ?? null,
                            'tahun' => $filters['tahun'] ?? null
                        ]
                    ], JSON_PRETTY_PRINT);
                    break;

                default:
                    http_response_code(400);
                    echo json_encode([
                        'success' => false, 
                        'message' => 'Invalid action specified',
                        'available_actions' => [
                            'list', 'summary', 'options', 'statistics', 
                            'monthly', 'klpd_statistics', 'check_data', 
                            'available_months', 'export'
                        ]
                    ], JSON_PRETTY_PRINT);
                    break;
            }
            break;

        default:
            http_response_code(405);
            echo json_encode([
                'success' => false, 
                'message' => 'Method not allowed. Only GET requests are supported.'
            ], JSON_PRETTY_PRINT);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log("API Error: " . $e->getMessage());
    error_log("Stack Trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(),
        'error_detail' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => explode("\n", $e->getTraceAsString())
        ]
    ], JSON_PRETTY_PRINT);
}