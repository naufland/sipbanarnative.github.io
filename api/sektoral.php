<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../includes/SektoralModel.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $sektoral = new SektoralModel($db);

    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? 'list';

    switch ($method) {
        case 'GET':
            switch ($action) {
                case 'list':
                    // Get filters from query parameters
                    $filters = [
                        'tahun_anggaran' => $_GET['tahun_anggaran'] ?? '',
                        'nama_satker' => $_GET['nama_satker'] ?? '',
                        'kategori' => $_GET['kategori'] ?? '',
                        'kode_rup' => $_GET['kode_rup'] ?? '',
                        'min_total' => $_GET['min_total'] ?? '',
                        'max_total' => $_GET['max_total'] ?? '',
                        'search' => $_GET['search'] ?? ''
                    ];

                    // Remove empty filters
                    $filters = array_filter($filters, function ($value) {
                        return $value !== '' && $value !== null;
                    });

                    // Pagination parameters
                    $page = intval($_GET['page'] ?? 1);
                    $limit = intval($_GET['limit'] ?? 100);
                    $offset = ($page - 1) * $limit;

                    // Get data and total count
                    $data = $sektoral->getSektoralData($filters, $limit, $offset);
                    $total = $sektoral->getTotalCount($filters);
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
                    // Get summary/statistics data
                    $filters = [
                        'tahun_anggaran' => $_GET['tahun_anggaran'] ?? '',
                        'nama_satker' => $_GET['nama_satker'] ?? '',
                        'kategori' => $_GET['kategori'] ?? '',
                        'search' => $_GET['search'] ?? ''
                    ];

                    // Remove empty filters
                    $filters = array_filter($filters, function ($value) {
                        return $value !== '' && $value !== null;
                    });

                    // Get all data for summary calculation
                    $allData = $sektoral->getSektoralData($filters, 1000000, 0);
                    $totalRecords = count($allData);
                    
                    // Calculate summary statistics
                    $totalPerencanaan = 0;
                    $totalPDN = 0;
                    $skpdStats = [];
                    $kategoriStats = [];
                    $tahunStats = [];
                    
                    foreach ($allData as $row) {
                        $totalPerencanaan += (float)$row['Total_Perencanaan_Rp'];
                        $totalPDN += (float)$row['PDN_Rp'];
                        
                        // Count by SKPD
                        $skpd = $row['Nama_Satker'];
                        if (!isset($skpdStats[$skpd])) {
                            $skpdStats[$skpd] = [
                                'count' => 0,
                                'total_perencanaan' => 0,
                                'total_pdn' => 0
                            ];
                        }
                        $skpdStats[$skpd]['count']++;
                        $skpdStats[$skpd]['total_perencanaan'] += (float)$row['Total_Perencanaan_Rp'];
                        $skpdStats[$skpd]['total_pdn'] += (float)$row['PDN_Rp'];
                        
                        // Count by Kategori
                        $kategori = $row['Kategori'];
                        if (!isset($kategoriStats[$kategori])) {
                            $kategoriStats[$kategori] = [
                                'count' => 0,
                                'total_perencanaan' => 0,
                                'total_pdn' => 0
                            ];
                        }
                        $kategoriStats[$kategori]['count']++;
                        $kategoriStats[$kategori]['total_perencanaan'] += (float)$row['Total_Perencanaan_Rp'];
                        $kategoriStats[$kategori]['total_pdn'] += (float)$row['PDN_Rp'];
                        
                        // Count by Tahun
                        $tahun = $row['Tahun_Anggaran'];
                        if (!isset($tahunStats[$tahun])) {
                            $tahunStats[$tahun] = [
                                'count' => 0,
                                'total_perencanaan' => 0,
                                'total_pdn' => 0
                            ];
                        }
                        $tahunStats[$tahun]['count']++;
                        $tahunStats[$tahun]['total_perencanaan'] += (float)$row['Total_Perencanaan_Rp'];
                        $tahunStats[$tahun]['total_pdn'] += (float)$row['PDN_Rp'];
                    }
                    
                    // Sort arrays by total_perencanaan descending
                    uasort($skpdStats, function($a, $b) {
                        return $b['total_perencanaan'] - $a['total_perencanaan'];
                    });
                    uasort($kategoriStats, function($a, $b) {
                        return $b['total_perencanaan'] - $a['total_perencanaan'];
                    });
                    uksort($tahunStats, function($a, $b) {
                        return $b - $a;
                    });
                    
                    // Calculate averages
                    $avgPerencanaan = $totalRecords > 0 ? $totalPerencanaan / $totalRecords : 0;
                    $avgPDN = $totalRecords > 0 ? $totalPDN / $totalRecords : 0;
                    $persentasePDN = $totalPerencanaan > 0 ? ($totalPDN / $totalPerencanaan) * 100 : 0;
                    
                    // Prepare response
                    echo json_encode([
                        'success' => true,
                        'summary' => [
                            'total_paket' => $totalRecords,
                            'total_perencanaan' => $totalPerencanaan,
                            'total_pdn' => $totalPDN,
                            'avg_perencanaan' => $avgPerencanaan,
                            'avg_pdn' => $avgPDN,
                            'persentase_pdn' => round($persentasePDN, 2),
                            'total_skpd' => count($skpdStats),
                            'total_kategori' => count($kategoriStats)
                        ],
                        'breakdown' => [
                            'skpd' => $skpdStats,
                            'kategori' => $kategoriStats,
                            'tahun' => $tahunStats
                        ],
                        'filters_applied' => $filters
                    ]);
                    break;

                case 'options':
                    // Get dropdown options
                    $tahunAnggaran = $sektoral->getAvailableYears();
                    $namaSatker = $sektoral->getAvailableSKPD();
                    $kategori = $sektoral->getAvailableKategori();

                    echo json_encode([
                        'success' => true,
                        'options' => [
                            'tahun_anggaran' => $tahunAnggaran,
                            'nama_satker' => $namaSatker,
                            'kategori' => $kategori
                        ]
                    ]);
                    break;

                case 'statistics':
                    // Get statistics by SKPD
                    $filters = [
                        'tahun_anggaran' => $_GET['tahun_anggaran'] ?? '',
                        'nama_satker' => $_GET['nama_satker'] ?? ''
                    ];
                    $filters = array_filter($filters);

                    $stats = $sektoral->getStatisticsBySKPD($filters);

                    echo json_encode([
                        'success' => true,
                        'statistics' => $stats,
                        'filters_applied' => $filters
                    ]);
                    break;

                case 'top_skpd':
                    // Get top N SKPD
                    $limit = intval($_GET['limit'] ?? 10);
                    $filters = [
                        'tahun_anggaran' => $_GET['tahun_anggaran'] ?? ''
                    ];
                    $filters = array_filter($filters);

                    $topSKPD = $sektoral->getTopSKPD($limit, $filters);

                    echo json_encode([
                        'success' => true,
                        'data' => $topSKPD,
                        'limit' => $limit,
                        'filters_applied' => $filters
                    ]);
                    break;

                case 'export':
                    // Export functionality
                    $filters = [
                        'tahun_anggaran' => $_GET['tahun_anggaran'] ?? '',
                        'nama_satker' => $_GET['nama_satker'] ?? '',
                        'kategori' => $_GET['kategori'] ?? '',
                        'search' => $_GET['search'] ?? ''
                    ];
                    $filters = array_filter($filters);

                    $format = $_GET['format'] ?? 'csv';
                    $data = $sektoral->getSektoralData($filters, 10000, 0);

                    if ($format == 'csv') {
                        $fileName = 'statistik_sektoral';
                        if (!empty($filters['tahun_anggaran'])) {
                            $fileName .= '_' . $filters['tahun_anggaran'];
                        }
                        if (!empty($filters['nama_satker'])) {
                            $fileName .= '_' . str_replace(' ', '_', strtolower($filters['nama_satker']));
                        }
                        
                        header('Content-Type: text/csv; charset=utf-8');
                        header('Content-Disposition: attachment; filename="' . $fileName . '.csv"');

                        $output = fopen('php://output', 'w');
                        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

                        // Headers
                        fputcsv($output, [
                            'No',
                            'Tahun Anggaran',
                            'Nama Satker (SKPD)',
                            'Kategori',
                            'Kode RUP',
                            'Nama Paket',
                            'Total Perencanaan (Rp)',
                            'PDN (Rp)'
                        ]);

                        // Data rows
                        foreach ($data as $index => $row) {
                            fputcsv($output, [
                                $index + 1,
                                $row['Tahun_Anggaran'],
                                $row['Nama_Satker'],
                                $row['Kategori'],
                                $row['Kode_RUP'],
                                $row['Nama_Paket'],
                                $row['Total_Perencanaan_Rp'],
                                $row['PDN_Rp']
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