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
    $pengadaan = new SwakelolaModel($db);

    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? 'list';

    switch ($method) {
        case 'GET':
            switch ($action) {
                case 'list':
                    // Filter
                    $filters = [
                        'tahun'          => $_GET['tahun'] ?? '',
                        'tanggal_awal'   => $_GET['tanggal_awal'] ?? '',
                        'tanggal_akhir'  => $_GET['tanggal_akhir'] ?? '',
                        'jenis_pengadaan'=> $_GET['jenis_pengadaan'] ?? '',
                        'klpd'           => $_GET['klpd'] ?? '',
                        'usaha_kecil'    => $_GET['usaha_kecil'] ?? '',
                        'metode'         => $_GET['metode'] ?? '',
                        'search'         => $_GET['search'] ?? ''
                    ];
                    $filters = array_filter($filters);

                    // Pagination
                    $page   = intval($_GET['page'] ?? 1);
                    $limit  = intval($_GET['limit'] ?? 100);
                    $offset = ($page - 1) * $limit;

                    // Query
                    $data  = $pengadaan->getSwakelolaData($filters, $limit, $offset);
                    $total = $pengadaan->getTotalCount($filters);
                    $totalPages = ceil($total / $limit);

                    // Tambah nomor urut
                    foreach ($data as $key => $row) {
                        $data[$key]['No'] = $offset + $key + 1;
                    }

                    echo json_encode([
                        'success' => true,
                        'data'    => $data,
                        'pagination' => [
                            'current_page'  => $page,
                            'total_pages'   => $totalPages,
                            'total_records' => $total,
                            'per_page'      => $limit,
                            'has_next'      => $page < $totalPages,
                            'has_prev'      => $page > 1
                        ]
                    ]);
                    break;

                case 'options':
                    echo json_encode([
                        'success' => true,
                        'options' => [
                            'jenis_pengadaan' => $pengadaan->getDistinctValues('Jenis_Pengadaan'),
                            'klpd'            => $pengadaan->getDistinctValues('KLPD'),
                            'usaha_kecil'     => $pengadaan->getDistinctValues('Usaha_Kecil'),
                            'metode'          => $pengadaan->getDistinctValues('Metode'),
                            'years'           => $pengadaan->getAvailableYears()
                        ]
                    ]);
                    break;

                case 'statistics':
                    $filters = [
                        'tahun' => $_GET['tahun'] ?? ''
                    ];
                    $filters = array_filter($filters);

                    $stats = $pengadaan->getStatistics($filters);

                    echo json_encode([
                        'success' => true,
                        'statistics' => $stats
                    ]);
                    break;

                case 'export':
                    $filters = [
                        'tahun'          => $_GET['tahun'] ?? '',
                        'bulan_awal'     => $_GET['bulan_awal'] ?? '',
                        'bulan_akhir'    => $_GET['bulan_akhir'] ?? '',
                        'jenis_pengadaan'=> $_GET['jenis_pengadaan'] ?? '',
                        'klpd'           => $_GET['klpd'] ?? '',
                        'search'         => $_GET['search'] ?? ''
                    ];
                    $filters = array_filter($filters);

                    $format = $_GET['format'] ?? 'csv';
                    $data   = $pengadaan->getSwakelolaData($filters, 10000, 0);

                    if ($format == 'csv') {
                        header('Content-Type: text/csv; charset=utf-8');
                        header('Content-Disposition: attachment; filename="data_pengadaan_' . date('Y-m-d') . '.csv"');

                        $output = fopen('php://output', 'w');
                        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

                        // Header CSV
                        fputcsv($output, [
                            'No',
                            'Paket',
                            'Pagu (Rp)',
                            'Jenis Pengadaan',
                            'Produk Dalam Negeri',
                            'Usaha Kecil',
                            'Metode',
                            'Pemilihan',
                            'KLPD',
                            'Satuan Kerja',
                            'Lokasi',
                            'ID'
                        ]);

                        // Isi data
                        foreach ($data as $index => $row) {
                            fputcsv($output, [
                                $index + 1,
                                $row['Paket'],
                                $row['Pagu_Rp'],
                                $row['Jenis_Pengadaan'],
                                $row['Produk_Dalam_Negeri'],
                                $row['Usaha_Kecil'],
                                $row['Metode'],
                                $row['Pemilihan'],
                                $row['KLPD'],
                                $row['Satuan_Kerja'],
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
