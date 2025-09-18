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
                    // Get filters from query parameters - mendukung filter lama dan baru
                    $filters = [
                        'tahun' => $_GET['tahun'] ?? '',
                        'tanggal_awal' => $_GET['tanggal_awal'] ?? '',
                        'tanggal_akhir' => $_GET['tanggal_akhir'] ?? '',
                        // Filter baru
                        'tipe_swakelola' => $_GET['tipe_swakelola'] ?? '',
                        'klpd' => $_GET['klpd'] ?? '',
                        'satuan_kerja' => $_GET['satuan_kerja'] ?? '',
                        'lokasi' => $_GET['lokasi'] ?? '',
                        // Filter lama untuk backward compatibility
                        'jenis_pengadaan' => $_GET['jenis_pengadaan'] ?? '',
                        'usaha_kecil' => $_GET['usaha_kecil'] ?? '',
                        'metode' => $_GET['metode'] ?? '',
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
                        ]
                    ]);
                    break;

                case 'options':
                    // Get dropdown options - mendukung kolom lama dan baru
                    $tipeSwakelola = $pengadaan->getDistinctValues('Tipe_Swakelola');
                    $klpd = $pengadaan->getDistinctValues('KLPD');
                    $satuanKerja = $pengadaan->getDistinctValues('Satuan_Kerja');
                    $lokasi = $pengadaan->getDistinctValues('Lokasi');
                    $years = $pengadaan->getAvailableYears();
                    
                    // Untuk backward compatibility
                    $jenisPengadaan = $pengadaan->getDistinctValues('Jenis_Pengadaan'); // akan di-map ke Tipe_Swakelola
                    $usahaKecil = $pengadaan->getDistinctValues('Usaha_Kecil');
                    $metode = $pengadaan->getDistinctValues('Metode');

                    echo json_encode([
                        'success' => true,
                        'options' => [
                            // Opsi baru
                            'tipe_swakelola' => $tipeSwakelola,
                            'klpd' => $klpd,
                            'satuan_kerja' => $satuanKerja,
                            'lokasi' => $lokasi,
                            'years' => $years,
                            // Opsi lama untuk backward compatibility
                            'jenis_pengadaan' => $jenisPengadaan,
                            'usaha_kecil' => $usahaKecil,
                            'metode' => $metode
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
                    // Export functionality - mendukung filter lama dan baru
                    $filters = [
                        'tahun' => $_GET['tahun'] ?? '',
                        'tanggal_awal' => $_GET['tanggal_awal'] ?? '',
                        'tanggal_akhir' => $_GET['tanggal_akhir'] ?? '',
                        // Filter baru
                        'tipe_swakelola' => $_GET['tipe_swakelola'] ?? '',
                        'klpd' => $_GET['klpd'] ?? '',
                        'satuan_kerja' => $_GET['satuan_kerja'] ?? '',
                        'lokasi' => $_GET['lokasi'] ?? '',
                        // Filter lama untuk backward compatibility
                        'jenis_pengadaan' => $_GET['jenis_pengadaan'] ?? '',
                        'usaha_kecil' => $_GET['usaha_kecil'] ?? '',
                        'metode' => $_GET['metode'] ?? '',
                        'search' => $_GET['search'] ?? ''
                    ];
                    $filters = array_filter($filters);

                    $format = $_GET['format'] ?? 'csv';
                    $data = $pengadaan->getPengadaanData($filters, 10000, 0); // Get all data for export

                    if ($format == 'csv') {
                        header('Content-Type: text/csv; charset=utf-8');
                        header('Content-Disposition: attachment; filename="data_pengadaan_' . date('Y-m-d') . '.csv"');

                        $output = fopen('php://output', 'w');

                        // Add BOM for proper UTF-8 encoding in Excel
                        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

                        // Headers
                        fputcsv($output, [
                            'No',
                            'Paket',
                            'Pagu (Rp)',
                            'Tipe Swakelola',
                            'Pemilihan',
                            'KLPD',
                            'Satuan Kerja',
                            'Lokasi',
                            'ID'
                        ]);

                        // Data rows
                        foreach ($data as $index => $row) {
                            fputcsv($output, [
                                $index + 1,
                                $row['Paket'],
                                $row['Pagu_Rp'],
                                $row['Tipe_Swakelola'],
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
?>