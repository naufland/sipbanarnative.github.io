<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

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
                    $filters = [
                        'bulan' => $_GET['bulan'] ?? '',
                        'tahun' => $_GET['tahun'] ?? '',
                        'tanggal_awal' => $_GET['tanggal_awal'] ?? '',
                        'tanggal_akhir' => $_GET['tanggal_akhir'] ?? '',
                        'tipe_swakelola' => $_GET['tipe_swakelola'] ?? ($_GET['jenis_pengadaan'] ?? ''),
                        'klpd' => $_GET['klpd'] ?? '',
                        'satuan_kerja' => $_GET['satuan_kerja'] ?? '',
                        'perubahan' => $_GET['perubahan'] ?? '',
                        'pagu_min' => $_GET['pagu_min'] ?? '',
                        'pagu_max' => $_GET['pagu_max'] ?? '',
                        'search' => $_GET['search'] ?? ''
                    ];

                    $filters = array_filter($filters, fn($value) => $value !== '' && $value !== null);

                    $page = intval($_GET['page'] ?? 1);
                    $limit = intval($_GET['limit'] ?? 25);
                    $offset = ($page - 1) * $limit;

                    $data = $swakelola->getSwakelolaData($filters, $limit, $offset);
                    $total = $swakelola->getTotalCount($filters);
                    $totalPages = ceil($total / $limit);

                    $optionFilters = [];
                    if (!empty($filters['bulan'])) {
                        $optionFilters['bulan'] = $filters['bulan'];
                    }
                    if (!empty($filters['tahun'])) {
                        $optionFilters['tahun'] = $filters['tahun'];
                    }

                    echo json_encode([
                        'success' => true,
                        'data' => $data,
                        'filters' => $filters,
                        'options' => [
                            'jenis_pengadaan' => $swakelola->getDistinctValues('Tipe_Swakelola', $optionFilters),
                            'klpd' => $swakelola->getDistinctValues('KLPD', $optionFilters),
                            'satuan_kerja' => $swakelola->getDistinctValues('Satuan_Kerja', $optionFilters),
                            'perubahan' => $swakelola->getDistinctValues('perubahan', $optionFilters),
                        ],
                        'pagination' => [
                            'current_page' => $page,
                            'total_pages' => $totalPages,
                            'total_records' => $total,
                            'per_page' => $limit
                        ],
                        'period' => [
                            'bulan' => $filters['bulan'] ?? null,
                            'tahun' => $filters['tahun'] ?? null,
                            'perubahan' => $filters['perubahan'] ?? null
                        ]
                    ], JSON_PRETTY_PRINT);
                    break;

                case 'summary':
                    $filters = [
                        'bulan' => $_GET['bulan'] ?? '',
                        'tahun' => $_GET['tahun'] ?? '',
                        'tanggal_awal' => $_GET['tanggal_awal'] ?? '',
                        'tanggal_akhir' => $_GET['tanggal_akhir'] ?? '',
                        'tipe_swakelola' => $_GET['tipe_swakelola'] ?? ($_GET['jenis_pengadaan'] ?? ''),
                        'klpd' => $_GET['klpd'] ?? '',
                        'satuan_kerja' => $_GET['satuan_kerja'] ?? '',
                        'perubahan' => $_GET['perubahan'] ?? '',
                        'pagu_min' => $_GET['pagu_min'] ?? '',
                        'pagu_max' => $_GET['pagu_max'] ?? '',
                        'search' => $_GET['search'] ?? ''
                    ];
                    $filters = array_filter($filters, fn($value) => $value !== '' && $value !== null);

                    $summary = $swakelola->getSummary($filters);

                    echo json_encode([
                        'success' => true,
                        'summary' => $summary,
                        'period' => [
                            'bulan' => $filters['bulan'] ?? null,
                            'tahun' => $filters['tahun'] ?? null,
                            'perubahan' => $filters['perubahan'] ?? null
                        ]
                    ], JSON_PRETTY_PRINT);
                    break;

                case 'options':
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
                            'klpd' => $swakelola->getDistinctValues('KLPD', $filters),
                            'satuan_kerja' => $swakelola->getDistinctValues('Satuan_Kerja', $filters),
                            'perubahan' => $swakelola->getDistinctValues('perubahan', $filters),
                        ],
                        'years' => $swakelola->getAvailableYears(),
                        'period' => [
                            'bulan' => $filters['bulan'] ?? null,
                            'tahun' => $filters['tahun'] ?? null
                        ]
                    ], JSON_PRETTY_PRINT);
                    break;

                case 'statistics':
                    $filters = [
                        'bulan' => $_GET['bulan'] ?? '',
                        'tahun' => $_GET['tahun'] ?? '',
                        'tanggal_awal' => $_GET['tanggal_awal'] ?? '',
                        'tanggal_akhir' => $_GET['tanggal_akhir'] ?? '',
                        'perubahan' => $_GET['perubahan'] ?? ''
                    ];
                    $filters = array_filter($filters, fn($value) => $value !== '' && $value !== null);

                    $stats = $swakelola->getStatistics($filters);

                    echo json_encode([
                        'success' => true,
                        'statistics' => $stats,
                        'period' => [
                            'bulan' => $filters['bulan'] ?? null,
                            'tahun' => $filters['tahun'] ?? null,
                            'perubahan' => $filters['perubahan'] ?? null
                        ]
                    ], JSON_PRETTY_PRINT);
                    break;

                case 'monthly':
                    $tahun = $_GET['tahun'] ?? date('Y');
                    $monthlyStats = $swakelola->getMonthlyStatistics($tahun);

                    echo json_encode([
                        'success' => true,
                        'data' => $monthlyStats,
                        'tahun' => $tahun
                    ], JSON_PRETTY_PRINT);
                    break;

                case 'klpd_statistics':
                    $filters = [
                        'bulan' => $_GET['bulan'] ?? '',
                        'tahun' => $_GET['tahun'] ?? '',
                        'perubahan' => $_GET['perubahan'] ?? ''
                    ];
                    $filters = array_filter($filters, fn($value) => $value !== '' && $value !== null);

                    $klpdStats = $swakelola->getKLPDStatistics($filters);

                    echo json_encode([
                        'success' => true,
                        'data' => $klpdStats,
                        'period' => [
                            'bulan' => $filters['bulan'] ?? null,
                            'tahun' => $filters['tahun'] ?? null,
                            'perubahan' => $filters['perubahan'] ?? null
                        ]
                    ], JSON_PRETTY_PRINT);
                    break;

                case 'check_data':
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
                    $tahun = $_GET['tahun'] ?? date('Y');
                    $months = $swakelola->getAvailableMonths($tahun);

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
                    $filters = [
                        'bulan' => $_GET['bulan'] ?? '',
                        'tahun' => $_GET['tahun'] ?? '',
                        'tipe_swakelola' => $_GET['tipe_swakelola'] ?? '',
                        'klpd' => $_GET['klpd'] ?? '',
                        'perubahan' => $_GET['perubahan'] ?? ''
                    ];
                    $filters = array_filter($filters, fn($value) => $value !== '' && $value !== null);

                    $exportData = $swakelola->exportData($filters);

                    echo json_encode([
                        'success' => true,
                        'data' => $exportData,
                        'total_records' => count($exportData),
                        'period' => [
                            'bulan' => $filters['bulan'] ?? null,
                            'tahun' => $filters['tahun'] ?? null,
                            'perubahan' => $filters['perubahan'] ?? null
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
            'line' => $e->getLine()
        ]
    ], JSON_PRETTY_PRINT);
}