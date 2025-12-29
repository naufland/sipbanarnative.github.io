<?php
// =================================================================
// == realisasi_tender.php (API) - FINAL FIX CALCULATION ==========
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
                // ==========================================================
                // == LIST DATA (TABEL) =====================================
                // ==========================================================
                case 'list':
                    // Ambil parameter filter
                    $filters = [
                        'bulan' => $_GET['bulan'] ?? '',
                        'tahun' => $_GET['tahun'] ?? '',
                        'tahun_anggaran' => $_GET['tahun_anggaran'] ?? '',
                        'jenis_pengadaan' => $_GET['jenis_pengadaan'] ?? '',
                        'nama_satker' => $_GET['nama_satker'] ?? '',
                        'metode_pengadaan' => $_GET['metode_pengadaan'] ?? '',
                        'sumber_dana' => $_GET['sumber_dana'] ?? '',
                        'jenis_kontrak' => $_GET['jenis_kontrak'] ?? '',
                        'search' => $_GET['search'] ?? ''
                    ];

                    // Bersihkan filter kosong
                    $filters = array_filter($filters, fn($v) => $v !== '' && $v !== null);

                    $page = intval($_GET['page'] ?? 1);
                    $limit = intval($_GET['limit'] ?? 50);
                    $offset = ($page - 1) * $limit;

                    // Ambil data tabel
                    $data = $realisasiTender->getRealisasiTenderData($filters, $limit, $offset);
                    $total = $realisasiTender->getTotalCount($filters);
                    $totalPages = ceil($total / $limit);

                    // Tambahkan nomor urut
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

                // ==========================================================
                // == SUMMARY (DASHBOARD) - HITUNG MANUAL PHP ===============
                // ==========================================================
                case 'summary':
                    $filters = [
                        'bulan' => $_GET['bulan'] ?? '',
                        'tahun' => $_GET['tahun'] ?? '',
                        'tahun_anggaran' => $_GET['tahun_anggaran'] ?? '',
                        'jenis_pengadaan' => $_GET['jenis_pengadaan'] ?? '',
                        'nama_satker' => $_GET['nama_satker'] ?? '',
                        'metode_pengadaan' => $_GET['metode_pengadaan'] ?? '',
                        'sumber_dana' => $_GET['sumber_dana'] ?? '',
                        'jenis_kontrak' => $_GET['jenis_kontrak'] ?? '',
                        'search' => $_GET['search'] ?? ''
                    ];
                    $filters = array_filter($filters);

                    // 1. AMBIL SEMUA DATA RAW (TANPA LIMIT)
                    // Kita akan menghitung total menggunakan looping PHP untuk menjamin akurasi
                    // dan menghindari kesalahan agregasi SQL tersembunyi.
                    $allData = $realisasiTender->getAllDataForSummary($filters);

                    // 2. INISIALISASI VARIABEL TOTAL
                    $totalPaket = count($allData);
                    $totalPagu = 0;
                    $totalHPS = 0;
                    $totalKontrak = 0;

                    // Variabel untuk Breakdown (Grafik)
                    $jenisPengadaanStats = [];
                    $satkerStats = [];
                    $metodeStats = [];
                    $sumberDanaStats = [];

                    // 3. LOOPING PERHITUNGAN (AGAR KONSISTEN)
                    foreach ($allData as $row) {
                        // Pastikan casting ke float agar angka string dari DB terbaca sebagai angka
                        $pagu = (float)($row['Nilai_Pagu'] ?? 0);
                        $hps = (float)($row['Nilai_HPS'] ?? 0);
                        $kontrak = (float)($row['Nilai_Kontrak'] ?? 0);

                        // Tambahkan ke Total Utama
                        $totalPagu += $pagu;
                        $totalHPS += $hps;
                        $totalKontrak += $kontrak;

                        // --- LOGIKA BREAKDOWN (RINCIAN) ---
                        
                        // Jenis Pengadaan
                        $jenis = $row['Jenis_Pengadaan'] ?? 'Lainnya';
                        if (!isset($jenisPengadaanStats[$jenis])) $jenisPengadaanStats[$jenis] = ['count' => 0, 'total_pagu' => 0, 'total_kontrak' => 0];
                        $jenisPengadaanStats[$jenis]['count']++;
                        $jenisPengadaanStats[$jenis]['total_pagu'] += $pagu;
                        $jenisPengadaanStats[$jenis]['total_kontrak'] += $kontrak;

                        // Satuan Kerja
                        $satker = $row['Nama_Satker'] ?? 'Tidak Diketahui';
                        if (!isset($satkerStats[$satker])) $satkerStats[$satker] = ['count' => 0, 'total_pagu' => 0, 'total_kontrak' => 0];
                        $satkerStats[$satker]['count']++;
                        $satkerStats[$satker]['total_pagu'] += $pagu;
                        $satkerStats[$satker]['total_kontrak'] += $kontrak;

                        // Metode Pengadaan
                        $metode = $row['Metode_Pengadaan'] ?? 'Lainnya';
                        if (!isset($metodeStats[$metode])) $metodeStats[$metode] = ['count' => 0, 'total_pagu' => 0, 'total_kontrak' => 0];
                        $metodeStats[$metode]['count']++;
                        $metodeStats[$metode]['total_pagu'] += $pagu;
                        $metodeStats[$metode]['total_kontrak'] += $kontrak;

                        // Sumber Dana
                        $sumberDana = $row['Sumber_Dana'] ?? 'Lainnya';
                        if (!isset($sumberDanaStats[$sumberDana])) $sumberDanaStats[$sumberDana] = ['count' => 0, 'total_pagu' => 0, 'total_kontrak' => 0];
                        $sumberDanaStats[$sumberDana]['count']++;
                        $sumberDanaStats[$sumberDana]['total_pagu'] += $pagu;
                        $sumberDanaStats[$sumberDana]['total_kontrak'] += $kontrak;
                    }

                    // 4. HITUNG EFISIENSI
                    $efisiensi = 0;
                    if ($totalPagu > 0) {
                        $efisiensi = (($totalPagu - $totalKontrak) / $totalPagu) * 100;
                    }

                    // 5. SORTING BREAKDOWN
                    uasort($jenisPengadaanStats, fn($a, $b) => $b['total_pagu'] - $a['total_pagu']);
                    uasort($satkerStats, fn($a, $b) => $b['total_pagu'] - $a['total_pagu']);
                    uasort($metodeStats, fn($a, $b) => $b['total_pagu'] - $a['total_pagu']);
                    uasort($sumberDanaStats, fn($a, $b) => $b['total_pagu'] - $a['total_pagu']);

                    echo json_encode([
                        'success' => true,
                        'summary' => [
                            'total_paket' => $totalPaket,
                            'total_pagu' => $totalPagu,
                            'total_hps' => $totalHPS,
                            'total_kontrak' => $totalKontrak,
                            'efisiensi_persen' => round($efisiensi, 2),
                            'total_satker' => count($satkerStats)
                        ],
                        'breakdown' => [
                            'jenis_pengadaan' => $jenisPengadaanStats,
                            'satker' => $satkerStats,
                            'metode_pengadaan' => $metodeStats,
                            'sumber_dana' => $sumberDanaStats
                        ],
                        'filters_applied' => $filters
                    ], JSON_UNESCAPED_UNICODE);
                    break;

                // ==========================================================
                // == OPTIONS (Dropdown) ====================================
                // ==========================================================
                case 'options':
                    $jenisPengadaan = $realisasiTender->getDistinctValues('Jenis_Pengadaan');
                    $satker = $realisasiTender->getDistinctValues('Nama_Satker');
                    $metodePengadaan = $realisasiTender->getDistinctValues('Metode_Pengadaan');
                    $sumberDana = $realisasiTender->getDistinctValues('Sumber_Dana');
                    $jenisKontrak = $realisasiTender->getDistinctValues('Jenis_Kontrak');
                    $years = $realisasiTender->getAvailableYears();
                    $tahunFilter = $_GET['tahun'] ?? null;
                    $months = $realisasiTender->getAvailableMonths($tahunFilter);

                    echo json_encode([
                        'success' => true,
                        'options' => [
                            'jenis_pengadaan' => $jenisPengadaan,
                            'nama_satker' => $satker,
                            'metode_pengadaan' => $metodePengadaan,
                            'sumber_dana' => $sumberDana,
                            'jenis_kontrak' => $jenisKontrak,
                            'years' => $years,
                            'months' => $months
                        ]
                    ], JSON_UNESCAPED_UNICODE);
                    break;

                default:
                    echo json_encode(['success' => false, 'message' => 'Invalid action']);
                    break;
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>