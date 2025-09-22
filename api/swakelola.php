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
require_once '../includes/SwakelolaModel.php'; // Pastikan model ini ada dan benar

try {
    $database = new Database();
    $db = $database->getConnection();
    $swakelola = new SwakelolaModel($db); // Menggunakan SwakelolaModel

    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? 'list';

    switch ($method) {
        case 'GET':
            switch ($action) {
                case 'list':
                    // Filter (menambahkan semua filter yang relevan dari front-end)
                    $filters = [
                        'tanggal_awal'    => $_GET['tanggal_awal'] ?? '',
                        'tanggal_akhir'   => $_GET['tanggal_akhir'] ?? '',
                        // PERBAIKAN: API menerima 'jenis_pengadaan', front-end mengirim 'tipe_swakelola'. Kita tangani keduanya.
                        'jenis_pengadaan' => $_GET['tipe_swakelola'] ?? ($_GET['jenis_pengadaan'] ?? ''),
                        'klpd'            => $_GET['klpd'] ?? '',
                        'satuan_kerja'    => $_GET['satuan_kerja'] ?? '', // Tambahkan filter satuan kerja
                        'pagu_min'        => $_GET['pagu_min'] ?? '',     // Tambahkan filter pagu min
                        'pagu_max'        => $_GET['pagu_max'] ?? '',     // Tambahkan filter pagu max
                        'search'          => $_GET['search'] ?? ''
                    ];
                    $filters = array_filter($filters, fn($value) => $value !== '' && $value !== null);

                    // Pagination
                    $page   = intval($_GET['page'] ?? 1);
                    $limit  = intval($_GET['limit'] ?? 25);
                    $offset = ($page - 1) * $limit;

                    // Query
                    $data  = $swakelola->getSwakelolaData($filters, $limit, $offset);
                    $total = $swakelola->getTotalCount($filters);
                    $totalPages = ceil($total / $limit);

                    echo json_encode([
                        'success' => true,
                        'data'    => $data,
                        'options' => [ // Kirim juga options agar front-end tidak perlu request lagi
                             'jenis_pengadaan' => $swakelola->getDistinctValues('Tipe_Swakelola'),
                             'klpd' => $swakelola->getDistinctValues('KLPD'),
                             'satuan_kerja' => $swakelola->getDistinctValues('Satuan_Kerja'),
                        ],
                        'pagination' => [
                            'current_page'  => $page,
                            'total_pages'   => $totalPages,
                            'total_records' => $total,
                            'per_page'      => $limit
                        ]
                    ]);
                    break;

                // === PENAMBAHAN BAGIAN SUMMARY DIMULAI DI SINI ===
                case 'summary':
                    // Ambil semua filter yang sama persis seperti 'list'
                    $filters = [
                        'tanggal_awal'    => $_GET['tanggal_awal'] ?? '',
                        'tanggal_akhir'   => $_GET['tanggal_akhir'] ?? '',
                        'jenis_pengadaan' => $_GET['tipe_swakelola'] ?? ($_GET['jenis_pengadaan'] ?? ''),
                        'klpd'            => $_GET['klpd'] ?? '',
                        'satuan_kerja'    => $_GET['satuan_kerja'] ?? '',
                        'pagu_min'        => $_GET['pagu_min'] ?? '',
                        'pagu_max'        => $_GET['pagu_max'] ?? '',
                        'search'          => $_GET['search'] ?? ''
                    ];
                    $filters = array_filter($filters, fn($value) => $value !== '' && $value !== null);

                    // Panggil fungsi baru di model untuk menghitung summary
                    $summary = $swakelola->getSummary($filters);

                    echo json_encode([
                        'success' => true,
                        'summary' => $summary
                    ]);
                    break;
                // === AKHIR BAGIAN SUMMARY ===

                case 'options':
                    echo json_encode([
                        'success' => true,
                        'options' => [
                            'jenis_pengadaan' => $swakelola->getDistinctValues('Tipe_Swakelola'), // Sesuaikan nama kolom
                            'klpd'            => $swakelola->getDistinctValues('KLPD'),
                            'satuan_kerja'    => $swakelola->getDistinctValues('Satuan_Kerja')
                        ]
                    ]);
                    break;

                default:
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Invalid action specified']);
                    break;
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log("API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}