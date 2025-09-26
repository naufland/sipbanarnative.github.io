<?php
// FILE: dashboard_rekapitulasi.php

// 1. KONEKSI & PENGOLAHAN DATA DARI TABEL ASLI
require_once '../config/database.php';

// Inisialisasi array kosong - akan diisi dari database
$rekap_metode = [];
$rekap_jenis = [];
$rekap_cara = [
    'Penyedia' => ['paket' => 0, 'pagu' => 0],
    'Swakelola' => ['paket' => 0, 'pagu' => 0]
];

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Cek tabel yang tersedia di database
    $show_tables_sql = "SHOW TABLES";
    $tables_stmt = $conn->prepare($show_tables_sql);
    $tables_stmt->execute();
    $available_tables = $tables_stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Cari tabel yang cocok
    $possible_tables = ['rup_keseluruhan', 'rup_penyedia', 'procurement_data', 'pengadaan', 'rup'];
    $main_table = null;
    
    foreach ($possible_tables as $table) {
        if (in_array($table, $available_tables)) {
            // Cek apakah tabel memiliki data
            $check_data = "SELECT COUNT(*) as total FROM `$table`";
            $check_stmt = $conn->prepare($check_data);
            $check_stmt->execute();
            $count = $check_stmt->fetch()['total'];
            
            if ($count > 0) {
                $main_table = $table;
                break;
            }
        }
    }
    
    // Jika tidak ada tabel yang cocok, ambil tabel pertama yang ada data
    if (!$main_table && !empty($available_tables)) {
        foreach ($available_tables as $table) {
            try {
                $check_data = "SELECT COUNT(*) as total FROM `$table`";
                $check_stmt = $conn->prepare($check_data);
                $check_stmt->execute();
                $count = $check_stmt->fetch()['total'];
                
                if ($count > 0) {
                    $main_table = $table;
                    break;
                }
            } catch (Exception $e) {
                continue;
            }
        }
    }
    
    if (!$main_table) {
        throw new Exception("Tidak ada tabel dengan data yang ditemukan");
    }

    // Cek kolom yang tersedia di tabel
    $describe_sql = "DESCRIBE `$main_table`";
    $describe_stmt = $conn->prepare($describe_sql);
    $describe_stmt->execute();
    $columns = $describe_stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Tentukan kolom yang akan digunakan
    $metode_col = null;
    $pagu_col = null;
    $paket_col = null;
    $jenis_col = null;
    
    // Cari kolom metode
    $metode_options = ['Metode', 'metode', 'cara_pengadaan', 'jenis_tender', 'pemilihan'];
    foreach ($metode_options as $col) {
        if (in_array($col, $columns)) {
            $metode_col = $col;
            break;
        }
    }
    
    // Cari kolom pagu
    $pagu_options = ['Pagu_Rp', 'pagu_rp', 'pagu', 'nilai_kontrak', 'harga'];
    foreach ($pagu_options as $col) {
        if (in_array($col, $columns)) {
            $pagu_col = $col;
            break;
        }
    }
    
    // Cari kolom nama paket
    $paket_options = ['Paket', 'paket', 'nama_paket', 'judul', 'kegiatan'];
    foreach ($paket_options as $col) {
        if (in_array($col, $columns)) {
            $paket_col = $col;
            break;
        }
    }
    
    // Cari kolom jenis
    $jenis_options = ['Jenis_Pengadaan', 'jenis_pengadaan', 'jenis', 'kategori', 'tipe'];
    foreach ($jenis_options as $col) {
        if (in_array($col, $columns)) {
            $jenis_col = $col;
            break;
        }
    }
    
    if (!$metode_col || !$pagu_col) {
        throw new Exception("Kolom metode atau pagu tidak ditemukan. Kolom tersedia: " . implode(', ', $columns));
    }

    // Query untuk mendapatkan data metode pengadaan dari database
    // Termasuk semua metode yang mungkin ada termasuk Tender Cepat
    $sql_penyedia = "
        SELECT 
            CASE 
                WHEN LOWER(`$metode_col`) LIKE '%e-purchasing%' OR LOWER(`$metode_col`) LIKE '%epurchasing%' THEN 'E-Purchasing'
                WHEN LOWER(`$metode_col`) LIKE '%pengadaan langsung%' THEN 'Pengadaan Langsung'
                WHEN LOWER(`$metode_col`) LIKE '%penunjukan langsung%' THEN 'Penunjukan Langsung'
                WHEN LOWER(`$metode_col`) LIKE '%seleksi%' THEN 'Seleksi'
                WHEN LOWER(`$metode_col`) LIKE '%tender cepat%' OR LOWER(`$metode_col`) LIKE '%tender%cepat%' THEN 'Tender Cepat'
                WHEN LOWER(`$metode_col`) LIKE '%tender%' AND LOWER(`$metode_col`) NOT LIKE '%cepat%' THEN 'Tender'
                WHEN LOWER(`$metode_col`) LIKE '%dikecualikan%' OR LOWER(`$metode_col`) LIKE '%pengecualian%' THEN 'Dikecualikan'
                WHEN LOWER(`$metode_col`) LIKE '%swakelola%' THEN 'Swakelola'
                ELSE TRIM(`$metode_col`)
            END as metode,
            COUNT(*) as jumlah_paket,
            COALESCE(SUM(CAST(`$pagu_col` as DECIMAL(20,2))), 0) as total_pagu
        FROM `$main_table` 
        WHERE `$metode_col` IS NOT NULL 
        AND TRIM(`$metode_col`) != '' 
        AND `$metode_col` != 'NULL'
        AND `$pagu_col` IS NOT NULL
        AND `$pagu_col` > 0
        GROUP BY metode
        ORDER BY total_pagu DESC
    ";

    $stmt = $conn->prepare($sql_penyedia);
    $stmt->execute();
    
    $total_penyedia_paket = 0;
    $total_penyedia_pagu = 0;
    
    if ($stmt->rowCount() > 0) {
        $penyedia_data = $stmt->fetchAll();
        
        foreach ($penyedia_data as $row) {
            $metode = trim($row['metode']);
            $paket = (int)$row['jumlah_paket'];
            $pagu = (float)$row['total_pagu'];
            
            // Skip jika ini adalah swakelola (akan diproses terpisah)
            if (strtolower($metode) != 'swakelola') {
                $rekap_metode[$metode] = ['paket' => $paket, 'pagu' => $pagu];
                $total_penyedia_paket += $paket;
                $total_penyedia_pagu += $pagu;
            } else {
                // Jika ada swakelola di data utama, tambahkan ke rekap_cara
                $rekap_cara['Swakelola']['paket'] += $paket;
                $rekap_cara['Swakelola']['pagu'] += $pagu;
            }
        }
    }
    
    // Pastikan semua metode standar ada dalam rekap (dengan nilai 0 jika tidak ada data)
    $metode_standar = [
        'E-Purchasing',
        'Pengadaan Langsung', 
        'Penunjukan Langsung',
        'Seleksi',
        'Tender',
        'Tender Cepat',
        'Dikecualikan'
    ];
    
    foreach ($metode_standar as $metode) {
        if (!isset($rekap_metode[$metode])) {
            $rekap_metode[$metode] = ['paket' => 0, 'pagu' => 0];
        }
    }
    
    $rekap_cara['Penyedia'] = ['paket' => $total_penyedia_paket, 'pagu' => $total_penyedia_pagu];

    // Query untuk swakelola - cek apakah ada tabel terpisah atau dalam tabel utama
    $swakelola_tables = ['rup_swakelola', 'swakelola', 'procurement_swakelola'];
    $swakelola_found = false;
    
    foreach ($swakelola_tables as $sw_table) {
        if (in_array($sw_table, $available_tables)) {
            try {
                // Cek kolom pagu di tabel swakelola
                $describe_sw_sql = "DESCRIBE `$sw_table`";
                $describe_sw_stmt = $conn->prepare($describe_sw_sql);
                $describe_sw_stmt->execute();
                $sw_columns = $describe_sw_stmt->fetchAll(PDO::FETCH_COLUMN);
                
                $sw_pagu_col = null;
                foreach ($pagu_options as $col) {
                    if (in_array($col, $sw_columns)) {
                        $sw_pagu_col = $col;
                        break;
                    }
                }
                
                if ($sw_pagu_col) {
                    $sql_swakelola = "
                        SELECT 
                            COUNT(*) as jumlah_paket,
                            COALESCE(SUM(CAST(`$sw_pagu_col` as DECIMAL(20,2))), 0) as total_pagu
                        FROM `$sw_table` 
                        WHERE `$sw_pagu_col` IS NOT NULL AND `$sw_pagu_col` > 0
                    ";

                    $stmt = $conn->prepare($sql_swakelola);
                    $stmt->execute();
                    
                    if ($stmt->rowCount() > 0) {
                        $swakelola_data = $stmt->fetch();
                        $rekap_cara['Swakelola']['paket'] += (int)$swakelola_data['jumlah_paket'];
                        $rekap_cara['Swakelola']['pagu'] += (float)$swakelola_data['total_pagu'];
                        $swakelola_found = true;
                        break;
                    }
                }
            } catch (Exception $e) {
                continue;
            }
        }
    }
    
    // Jika tidak ada tabel swakelola terpisah, cari di tabel utama berdasarkan metode
    if (!$swakelola_found) {
        $sql_swakelola_alt = "
            SELECT 
                COUNT(*) as jumlah_paket,
                COALESCE(SUM(CAST(`$pagu_col` as DECIMAL(20,2))), 0) as total_pagu
            FROM `$main_table` 
            WHERE (LOWER(`$metode_col`) LIKE '%swakelola%'";
        
        // Tambahkan kondisi jika ada kolom pemilihan
        $pemilihan_options = ['Pemilihan', 'pemilihan', 'cara_pemilihan'];
        foreach ($pemilihan_options as $col) {
            if (in_array($col, $columns)) {
                $sql_swakelola_alt .= " OR LOWER(`$col`) LIKE '%swakelola%'";
                break;
            }
        }
        
        $sql_swakelola_alt .= ") AND `$pagu_col` IS NOT NULL AND `$pagu_col` > 0";
        
        try {
            $stmt = $conn->prepare($sql_swakelola_alt);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $swakelola_data = $stmt->fetch();
                $rekap_cara['Swakelola']['paket'] += (int)$swakelola_data['jumlah_paket'];
                $rekap_cara['Swakelola']['pagu'] += (float)$swakelola_data['total_pagu'];
            }
        } catch (Exception $e) {
            error_log("Swakelola alternative query error: " . $e->getMessage());
        }
    }

    // Query untuk jenis pengadaan berdasarkan kolom yang ditemukan
    if ($jenis_col) {
        $sql_jenis = "
            SELECT 
                CASE 
                    WHEN LOWER(`$jenis_col`) LIKE '%barang%' THEN 'Barang'
                    WHEN LOWER(`$jenis_col`) LIKE '%konsultansi%' THEN 'Jasa Konsultansi'
                    WHEN LOWER(`$jenis_col`) LIKE '%konstruksi%' OR LOWER(`$jenis_col`) LIKE '%pekerjaan konstruksi%' THEN 'Pekerjaan Konstruksi'
                    WHEN LOWER(`$jenis_col`) LIKE '%jasa%' THEN 'Jasa Lainnya'
                    ELSE COALESCE(`$jenis_col`, 'Lainnya')
                END as jenis_kategori,
                COUNT(*) as jumlah_paket,
                COALESCE(SUM(CAST(`$pagu_col` as DECIMAL(20,2))), 0) as total_pagu
            FROM `$main_table` 
            WHERE `$jenis_col` IS NOT NULL 
            AND TRIM(`$jenis_col`) != '' 
            AND `$jenis_col` != 'NULL'
            AND `$pagu_col` IS NOT NULL
            AND `$pagu_col` > 0
            GROUP BY jenis_kategori
            ORDER BY total_pagu DESC
        ";
        
        try {
            $stmt = $conn->prepare($sql_jenis);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $jenis_data = $stmt->fetchAll();
                
                foreach ($jenis_data as $row) {
                    $jenis = trim($row['jenis_kategori']);
                    if ($jenis && $jenis != 'Lainnya' && $jenis != '') {
                        $paket = (int)$row['jumlah_paket'];
                        $pagu = (float)$row['total_pagu'];
                        $rekap_jenis[$jenis] = ['paket' => $paket, 'pagu' => $pagu];
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Jenis pengadaan query error: " . $e->getMessage());
        }
    }
    
    // Fallback: analisis dari nama paket jika ada
    if (empty($rekap_jenis) && $paket_col) {
        try {
            $sql_jenis_fallback = "
                SELECT 
                    CASE 
                        WHEN LOWER(`$paket_col`) LIKE '%barang%' OR LOWER(`$paket_col`) LIKE '%alat%' OR LOWER(`$paket_col`) LIKE '%peralatan%' THEN 'Barang'
                        WHEN LOWER(`$paket_col`) LIKE '%konsultansi%' OR LOWER(`$paket_col`) LIKE '%konsultan%' OR LOWER(`$paket_col`) LIKE '%perencanaan%' THEN 'Jasa Konsultansi'
                        WHEN LOWER(`$paket_col`) LIKE '%konstruksi%' OR LOWER(`$paket_col`) LIKE '%pembangunan%' OR LOWER(`$paket_col`) LIKE '%renovasi%' OR LOWER(`$paket_col`) LIKE '%rehab%' THEN 'Pekerjaan Konstruksi'
                        ELSE 'Jasa Lainnya'
                    END as jenis_kategori,
                    COUNT(*) as jumlah_paket,
                    COALESCE(SUM(CAST(`$pagu_col` as DECIMAL(20,2))), 0) as total_pagu
                FROM `$main_table` 
                WHERE `$paket_col` IS NOT NULL 
                AND TRIM(`$paket_col`) != ''
                AND `$pagu_col` IS NOT NULL
                AND `$pagu_col` > 0
                GROUP BY jenis_kategori
                ORDER BY total_pagu DESC
            ";
            
            $stmt = $conn->prepare($sql_jenis_fallback);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $jenis_data = $stmt->fetchAll();
                
                foreach ($jenis_data as $row) {
                    $jenis = trim($row['jenis_kategori']);
                    $paket = (int)$row['jumlah_paket'];
                    $pagu = (float)$row['total_pagu'];
                    $rekap_jenis[$jenis] = ['paket' => $paket, 'pagu' => $pagu];
                }
            }
        } catch (Exception $e2) {
            error_log("Jenis pengadaan fallback error: " . $e2->getMessage());
        }
    }

    // Debug: Cek total records
    $debug_sql = "SELECT COUNT(*) as total FROM `$main_table`";
    $debug_stmt = $conn->prepare($debug_sql);
    $debug_stmt->execute();
    $total_records = $debug_stmt->fetch()['total'];
    
    // Sample data untuk debugging
    $sample_sql = "SELECT * FROM `$main_table` LIMIT 5";
    $sample_stmt = $conn->prepare($sample_sql);
    $sample_stmt->execute();
    $sample_data = $sample_stmt->fetchAll();
    
    error_log("Debug Info:");
    error_log("- Main table: $main_table");
    error_log("- Available tables: " . implode(', ', $available_tables));
    error_log("- Available columns: " . implode(', ', $columns));
    error_log("- Metode column: " . ($metode_col ?? 'NOT FOUND'));
    error_log("- Pagu column: " . ($pagu_col ?? 'NOT FOUND'));
    error_log("- Paket column: " . ($paket_col ?? 'NOT FOUND'));
    error_log("- Jenis column: " . ($jenis_col ?? 'NOT FOUND'));
    error_log("- Total records in $main_table: " . $total_records);
    error_log("- Rekap Metode count: " . count($rekap_metode));
    error_log("- Rekap Jenis count: " . count($rekap_jenis));
    
    if (!empty($sample_data)) {
        error_log("- Tender Cepat data: " . json_encode($rekap_metode['Tender Cepat'] ?? 'NOT SET'));
        error_log("- All metode data: " . json_encode($rekap_metode));
    }

} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    
    // Set data kosong jika error
    $rekap_metode = [];
    $rekap_jenis = [];
    $rekap_cara = [
        'Penyedia' => ['paket' => 0, 'pagu' => 0],
        'Swakelola' => ['paket' => 0, 'pagu' => 0]
    ];
}

// Filter out metode dengan 0 paket untuk tampilan tabel, KECUALI Tender Cepat
$rekap_metode_display = array_filter($rekap_metode, function($data, $key) {
    // Selalu tampilkan Tender Cepat meskipun 0 untuk kelengkapan
    return $data['paket'] > 0 || $key === 'Tender Cepat';
}, ARRAY_FILTER_USE_BOTH);

// Urutkan ulang agar Tender Cepat muncul di posisi yang tepat
$metode_order = [
    'E-Purchasing',
    'Pengadaan Langsung', 
    'Dikecualikan',
    'Tender',
    'Seleksi',
    'Penunjukan Langsung',
    'Tender Cepat'  // Posisi setelah metode lainnya
];

$rekap_metode_sorted = [];
foreach ($metode_order as $metode) {
    if (isset($rekap_metode_display[$metode])) {
        $rekap_metode_sorted[$metode] = $rekap_metode_display[$metode];
    }
}

// Tambahkan metode lain yang tidak ada dalam urutan standar
foreach ($rekap_metode_display as $metode => $data) {
    if (!in_array($metode, $metode_order)) {
        $rekap_metode_sorted[$metode] = $data;
    }
}

$rekap_metode_display = $rekap_metode_sorted;

// Menyiapkan data untuk JavaScript (Charts)
$chart_cara_json = json_encode([
    'labels' => array_keys($rekap_cara), 
    'data' => array_column($rekap_cara, 'pagu')
]);

// Menyiapkan data untuk JavaScript (Charts) - sertakan semua metode termasuk yang 0
$chart_metode_json = json_encode([
    'labels' => array_keys($rekap_metode_display), 
    'data' => array_column($rekap_metode_display, 'pagu')
]);

$chart_jenis_json = json_encode([
    'labels' => array_keys($rekap_jenis), 
    'data' => array_column($rekap_jenis, 'pagu')
]);

// Hitung total untuk persentase
$total_pagu = $rekap_cara['Penyedia']['pagu'] + $rekap_cara['Swakelola']['pagu'];
$total_paket = $rekap_cara['Penyedia']['paket'] + $rekap_cara['Swakelola']['paket'];

// Debug information
$debug_info = [
    'total_records' => $total_records ?? 0,
    'metode_count' => count($rekap_metode),
    'jenis_count' => count($rekap_jenis),
    'table_used' => $main_table ?? 'unknown',
    'available_tables' => $available_tables ?? [],
    'available_columns' => $columns ?? [],
    'columns_found' => [
        'metode' => $metode_col ?? 'NOT FOUND',
        'pagu' => $pagu_col ?? 'NOT FOUND', 
        'paket' => $paket_col ?? 'NOT FOUND',
        'jenis' => $jenis_col ?? 'NOT FOUND'
    ],
    'sample_data' => $sample_data[0] ?? null
];

// 2. VIEW
$page_title = "Dashboard Perencanaan";
include '../navbar/header.php';
?>
<script src="../../js/submenu.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

<style>
    body {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        min-height: 100vh;
        font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .container {
        max-width: 1400px;
        margin: 30px auto;
        padding: 15px;
    }

    .card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        margin-bottom: 30px;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.2);
        transition: all 0.3s ease;
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
    }

    .card-header {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
        padding: 20px 25px;
        font-size: 18px;
        font-weight: 700;
        text-align: center;
        letter-spacing: 1px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        position: relative;
        overflow: hidden;
    }

    .card-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        transition: left 0.5s;
    }

    .card-header:hover::before {
        left: 100%;
    }

    .table-container {
        padding: 25px;
        overflow-x: auto;
        position: relative;
    }

    .table-controls {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 10px;
    }

    .btn-group {
        display: flex;
        gap: 8px;
    }

    .btn {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(220, 53, 69, 0.3);
        color: white;
        text-decoration: none;
    }

    .btn:active {
        transform: translateY(0);
    }

    .styled-table {
        border-collapse: collapse;
        width: 100%;
        font-size: 14px;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        background: white;
    }

    .styled-table th,
    .styled-table td {
        border: none;
        padding: 15px;
        text-align: left;
        border-bottom: 1px solid #f0f0f0;
        position: relative;
    }

    .styled-table th {
        background: linear-gradient(135deg, #fff5f5 0%, #fed7d7 100%);
        font-weight: 700;
        text-align: center;
        color: #c53030;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-size: 12px;
    }

    .styled-table tbody tr {
        transition: all 0.3s ease;
    }

    .styled-table tbody tr:hover {
        background: linear-gradient(135deg, #fff5f5 0%, #fed7d7 100%);
        transform: scale(1.01);
    }

    .styled-table td:first-child,
    .styled-table td:nth-child(3) {
        text-align: center;
        font-weight: 600;
    }

    .styled-table td:last-child {
        text-align: right;
        font-weight: 600;
        color: #2d3748;
    }

    .summary-row, .penyedia-row, .swakelola-row {
        background: linear-gradient(135deg, #fed7d7 0%, #feb2b2 100%) !important;
        font-weight: 700;
    }

    .total-row {
        background: linear-gradient(135deg, #fbb6ce 0%, #f687b3 100%) !important;
        font-weight: 800;
        border-top: 3px solid #dc3545;
        color: #2d3748;
    }

    .chart-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 30px;
        padding: 30px;
    }

    .chart-item {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(15px);
        border: 1px solid rgba(255, 255, 255, 0.3);
        border-radius: 20px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        overflow: hidden;
        position: relative;
    }

    .chart-item:hover {
        transform: translateY(-8px);
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
    }

    .chart-item::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    }

    .chart-item-header {
        background: linear-gradient(135deg, #fff5f5 0%, #fed7d7 100%);
        color: #c53030;
        padding: 18px;
        font-weight: 700;
        text-align: center;
        font-size: 14px;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        position: relative;
    }

    .chart-content {
        padding: 25px;
        position: relative;
    }

    .chart-wrapper {
        position: relative;
        height: 350px;
    }

    .chart-controls {
        position: absolute;
        top: 10px;
        right: 10px;
        z-index: 10;
        display: flex;
        gap: 5px;
    }

    .chart-btn {
        background: rgba(220, 53, 69, 0.9);
        color: white;
        border: none;
        border-radius: 6px;
        padding: 6px 8px;
        font-size: 10px;
        cursor: pointer;
        transition: all 0.2s ease;
        opacity: 0;
        transform: translateY(-10px);
    }

    .chart-item:hover .chart-btn {
        opacity: 1;
        transform: translateY(0);
    }

    .chart-btn:hover {
        background: rgba(220, 53, 69, 1);
        transform: scale(1.1);
    }

    .info-banner {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
        padding: 18px 25px;
        border-radius: 15px;
        margin-bottom: 25px;
        font-size: 14px;
        text-align: center;
        font-weight: 500;
        box-shadow: 0 8px 25px rgba(220, 53, 69, 0.3);
        position: relative;
        overflow: hidden;
    }

    .info-banner::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
        animation: shimmer 2s infinite;
    }

    @keyframes shimmer {
        0% { left: -100%; }
        100% { left: 100%; }
    }

    .debug-info {
        background: rgba(248, 250, 252, 0.9);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(226, 232, 240, 0.8);
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 25px;
        font-size: 12px;
        color: #4a5568;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    }

    .no-data {
        text-align: center;
        padding: 60px;
        color: #718096;
        font-style: italic;
        background: linear-gradient(135deg, #fff5f5 0%, #fed7d7 100%);
        border-radius: 12px;
        margin: 20px 0;
    }

    .no-data i {
        font-size: 48px;
        margin-bottom: 15px;
        opacity: 0.5;
    }

    /* Modal Styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.8);
        backdrop-filter: blur(5px);
    }

    .modal-content {
        background: white;
        margin: 5% auto;
        padding: 0;
        border-radius: 20px;
        width: 90%;
        max-width: 1000px;
        max-height: 80vh;
        overflow-y: auto;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
        animation: modalSlideIn 0.3s ease-out;
    }

    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: translateY(-50px) scale(0.8);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .modal-header {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-radius: 20px 20px 0 0;
    }

    .close {
        color: white;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .close:hover {
        transform: scale(1.2);
        opacity: 0.8;
    }

    .modal-body {
        padding: 30px;
    }

    /* Loading Animation */
    .loading {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 200px;
    }

    .spinner {
        width: 40px;
        height: 40px;
        border: 4px solid #f3f4f6;
        border-top: 4px solid #dc3545;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* Responsive Design */
    @media (max-width: 1200px) {
        .chart-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .container {
            margin: 15px auto;
            padding: 10px;
        }

        .chart-grid {
            grid-template-columns: 1fr;
            gap: 20px;
            padding: 20px;
        }
        
        .chart-wrapper {
            height: 280px;
        }
        
        .chart-item-header {
            font-size: 12px;
            padding: 15px;
        }

        .table-container {
            padding: 15px;
        }

        .btn-group {
            flex-direction: column;
            width: 100%;
        }

        .btn {
            justify-content: center;
        }

        .styled-table {
            font-size: 12px;
        }

        .styled-table th,
        .styled-table td {
            padding: 10px 8px;
        }
    }

    /* Print Styles */
    @media print {
        .chart-controls, .btn-group, .debug-info {
            display: none !important;
        }

        .card {
            box-shadow: none;
            border: 1px solid #ddd;
        }
    }
</style>

<!-- Modal untuk Detail Chart -->
<div id="chartModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-chart-line"></i> Detail Grafik</h3>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <div id="modalChartContainer">
                <canvas id="modalChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="info-banner">
        <i class="fas fa-database"></i> Data real-time dari database - Terakhir diupdate: <?= date('d/m/Y H:i:s') ?>
    </div>

    <!-- Debug Information -->
    <div class="debug-info" style="font-size: 11px; line-height: 1.4;">
        <strong>Debug Info:</strong><br>
        <strong>Database:</strong> <?= $debug_info['table_used'] ?> | 
        <strong>Records:</strong> <?= $debug_info['total_records'] ?> | 
        <strong>Metode Found:</strong> <?= $debug_info['metode_count'] ?> | 
        <strong>Jenis Found:</strong> <?= $debug_info['jenis_count'] ?><br>
        
        <strong>Available Tables:</strong> <?= implode(', ', $debug_info['available_tables']) ?><br>
        
        <strong>Columns Found:</strong>
        Metode: <code><?= $debug_info['columns_found']['metode'] ?></code> | 
        Pagu: <code><?= $debug_info['columns_found']['pagu'] ?></code> | 
        Paket: <code><?= $debug_info['columns_found']['paket'] ?></code> | 
        Jenis: <code><?= $debug_info['columns_found']['jenis'] ?></code><br>
        
        <?php if ($debug_info['sample_data']): ?>
        <strong>Sample Data:</strong> 
        <details style="margin-top: 10px;">
            <summary>Click to expand</summary>
            <pre style="background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; font-size: 10px;">
<?= json_encode($debug_info['sample_data'], JSON_PRETTY_PRINT) ?>
            </pre>
        </details>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-table"></i> PERENCANAAN PENGADAAN
        </div>
        <div class="table-container">
            <div class="table-controls">
                <div>
                    <strong>Total: <?= number_format($total_paket, 0, ',', '.') ?> Paket</strong> | 
                    <strong>Rp <?= number_format($total_pagu, 0, ',', '.') ?></strong>
                </div>
                <div class="btn-group">
                    <button class="btn" onclick="exportTableToCSV()">
                        <i class="fas fa-download"></i> Export CSV
                    </button>
                    <button class="btn" onclick="printTable()">
                        <i class="fas fa-print"></i> Print
                    </button>
                    <button class="btn" onclick="refreshData()">
                        <i class="fas fa-refresh"></i> Refresh
                    </button>
                </div>
            </div>

            <?php if (!empty($rekap_metode_display)): ?>
            <table class="styled-table" id="mainTable">
                <thead>
                    <tr>
                        <th>NO</th>
                        <th>METODE PENGADAAN</th>
                        <th>JUMLAH PAKET RUP</th>
                        <th>PAGU</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; ?>
                    <?php foreach ($rekap_metode_display as $metode => $stats): ?>
                        <tr <?= ($stats['paket'] == 0) ? 'style="opacity: 0.6; font-style: italic;"' : '' ?>>
                            <td><?= $no++ ?></td>
                            <td>
                                <?= htmlspecialchars($metode) ?>
                                <?= ($stats['paket'] == 0) ? ' <small>(belum ada data)</small>' : '' ?>
                            </td>
                            <td><?= number_format($stats['paket'], 0, ',', '.') ?></td>
                            <td>Rp <?= number_format($stats['pagu'], 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <tr class="penyedia-row">
                        <td colspan="2"><strong>Penyedia</strong></td>
                        <td><strong><?= number_format($rekap_cara['Penyedia']['paket'], 0, ',', '.') ?></strong></td>
                        <td><strong>Rp <?= number_format($rekap_cara['Penyedia']['pagu'], 0, ',', '.') ?></strong></td>
                    </tr>
                    <tr class="swakelola-row">
                        <td colspan="2"><strong>Swakelola</strong></td>
                        <td><strong><?= number_format($rekap_cara['Swakelola']['paket'], 0, ',', '.') ?></strong></td>
                        <td><strong>Rp <?= number_format($rekap_cara['Swakelola']['pagu'], 0, ',', '.') ?></strong></td>
                    </tr>
                    <tr class="total-row">
                        <td colspan="2"><strong>TOTAL</strong></td>
                        <td><strong><?= number_format($total_paket, 0, ',', '.') ?></strong></td>
                        <td><strong>Rp <?= number_format($total_pagu, 0, ',', '.') ?></strong></td>
                    </tr>
                </tbody>
            </table>
            <?php else: ?>
            <div class="no-data">
                <i class="fas fa-exclamation-triangle"></i><br>
                Tidak ada data ditemukan dalam database.<br>
                <small>Periksa koneksi database dan struktur tabel.</small>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-chart-pie"></i> VISUALISASI DATA PENGADAAN
        </div>
        <div class="chart-grid">
            <div class="chart-item">
                <div class="chart-item-header">
                    <i class="fas fa-chart-pie"></i> CARA PENGADAAN
                </div>
                <div class="chart-content">
                    <div class="chart-controls">
                        <button class="chart-btn" onclick="openModal('chartCara', 'Cara Pengadaan')">
                            <i class="fas fa-expand"></i>
                        </button>
                        <button class="chart-btn" onclick="downloadChart('chartCara', 'cara-pengadaan')">
                            <i class="fas fa-download"></i>
                        </button>
                    </div>
                    <div class="chart-wrapper">
                        <canvas id="chartCara"></canvas>
                    </div>
                </div>
            </div>

            <div class="chart-item">
                <div class="chart-item-header">
                    <i class="fas fa-chart-doughnut"></i> JENIS PENGADAAN
                </div>
                <div class="chart-content">
                    <div class="chart-controls">
                        <button class="chart-btn" onclick="openModal('chartJenis', 'Jenis Pengadaan')">
                            <i class="fas fa-expand"></i>
                        </button>
                        <button class="chart-btn" onclick="downloadChart('chartJenis', 'jenis-pengadaan')">
                            <i class="fas fa-download"></i>
                        </button>
                    </div>
                    <div class="chart-wrapper">
                        <canvas id="chartJenis"></canvas>
                    </div>
                </div>
            </div>

            <div class="chart-item">
                <div class="chart-item-header">
                    <i class="fas fa-chart-bar"></i> METODE PENGADAAN
                </div>
                <div class="chart-content">
                    <div class="chart-controls">
                        <button class="chart-btn" onclick="openModal('chartMetode', 'Metode Pengadaan')">
                            <i class="fas fa-expand"></i>
                        </button>
                        <button class="chart-btn" onclick="downloadChart('chartMetode', 'metode-pengadaan')">
                            <i class="fas fa-download"></i>
                        </button>
                    </div>
                    <div class="chart-wrapper">
                        <canvas id="chartMetode"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Register Chart.js plugins
    Chart.register(ChartDataLabels);

    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        animation: {
            duration: 1500,
            easing: 'easeInOutQuart'
        },
        plugins: {
            legend: {
                display: true,
                position: 'bottom',
                labels: {
                    padding: 20,
                    font: {
                        size: 12,
                        weight: '600'
                    },
                    usePointStyle: true,
                    pointStyle: 'circle',
                    generateLabels: function(chart) {
                        const data = chart.data;
                        if (data.labels.length && data.datasets.length) {
                            const dataset = data.datasets[0];
                            return data.labels.map((label, i) => {
                                const value = dataset.data[i];
                                const total = dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) + '%' : '0%';
                                return {
                                    text: `${label} (${percentage})`,
                                    fillStyle: dataset.backgroundColor[i],
                                    hidden: isNaN(dataset.data[i]) || chart.getDatasetMeta(0).data[i].hidden,
                                    index: i
                                };
                            });
                        }
                        return [];
                    }
                }
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.9)',
                titleColor: '#fff',
                bodyColor: '#fff',
                borderColor: '#dc3545',
                borderWidth: 2,
                cornerRadius: 8,
                displayColors: true,
                callbacks: {
                    label: function(context) {
                        const label = context.label || '';
                        const value = context.parsed;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = total > 0 ? ((value / total) * 100).toFixed(1) + '%' : '0%';
                        const formattedValue = new Intl.NumberFormat('id-ID').format(value);
                        return `${label}: Rp ${formattedValue} (${percentage})`;
                    }
                }
            },
            datalabels: {
                color: '#fff',
                font: {
                    weight: 'bold',
                    size: 11
                },
                formatter: (value, context) => {
                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                    const percentage = total > 0 ? ((value / total) * 100) : 0;
                    return percentage > 5 ? percentage.toFixed(1) + '%' : '';
                },
                textAlign: 'center'
            }
        },
        interaction: {
            intersect: false,
            mode: 'nearest'
        }
    };

    // Enhanced color schemes with gradients simulation
    const caraColors = ['#dc3545', '#c82333'];
    const jenisColors = ['#dc3545', '#e74c3c', '#c0392b', '#a93226'];
    const metodeColors = ['#dc3545', '#e74c3c', '#c0392b', '#a93226', '#922b21', '#7b241c', '#641e16', '#5b1a14'];

    // Data dari PHP
    const dataCara = <?= $chart_cara_json ?>;
    const dataJenis = <?= $chart_jenis_json ?>;
    const dataMetode = <?= $chart_metode_json ?>;

    let charts = {};

    // 1. Chart Cara Pengadaan (Penyedia vs Swakelola)
    if (dataCara.data.some(val => val > 0)) {
        charts.cara = new Chart(document.getElementById('chartCara'), {
            type: 'doughnut',
            data: {
                labels: dataCara.labels,
                datasets: [{
                    data: dataCara.data,
                    backgroundColor: caraColors,
                    borderColor: '#fff',
                    borderWidth: 4,
                    hoverBorderWidth: 6,
                    hoverOffset: 10
                }]
            },
            options: {
                ...commonOptions,
                cutout: '60%',
                plugins: {
                    ...commonOptions.plugins,
                    datalabels: {
                        ...commonOptions.plugins.datalabels,
                        display: true
                    }
                }
            }
        });
    } else {
        document.getElementById('chartCara').parentElement.innerHTML = '<div class="no-data"><i class="fas fa-chart-pie"></i><br>Tidak ada data</div>';
    }

    // 2. Chart Jenis Pengadaan
    if (dataJenis.data.length > 0 && dataJenis.data.some(val => val > 0)) {
        charts.jenis = new Chart(document.getElementById('chartJenis'), {
            type: 'doughnut',
            data: {
                labels: dataJenis.labels,
                datasets: [{
                    data: dataJenis.data,
                    backgroundColor: jenisColors.slice(0, dataJenis.labels.length),
                    borderColor: '#fff',
                    borderWidth: 3,
                    hoverBorderWidth: 5,
                    hoverOffset: 8
                }]
            },
            options: {
                ...commonOptions,
                cutout: '55%',
                plugins: {
                    ...commonOptions.plugins,
                    datalabels: {
                        ...commonOptions.plugins.datalabels,
                        display: true
                    }
                }
            }
        });
    } else {
        document.getElementById('chartJenis').parentElement.innerHTML = '<div class="no-data"><i class="fas fa-chart-doughnut"></i><br>Tidak ada data</div>';
    }

    // 3. Chart Metode Pengadaan (Detail)
    if (dataMetode.data.length > 0) {
        const filteredData = {
            labels: [],
            data: []
        };
        
        dataMetode.labels.forEach((label, index) => {
            if (dataMetode.data[index] > 0) {
                filteredData.labels.push(label);
                filteredData.data.push(dataMetode.data[index]);
            }
        });
        
        if (filteredData.data.length > 0) {
            charts.metode = new Chart(document.getElementById('chartMetode'), {
                type: 'doughnut',
                data: {
                    labels: filteredData.labels,
                    datasets: [{
                        data: filteredData.data,
                        backgroundColor: metodeColors.slice(0, filteredData.labels.length),
                        borderColor: '#fff',
                        borderWidth: 3,
                        hoverBorderWidth: 5,
                        hoverOffset: 6
                    }]
                },
                options: {
                    ...commonOptions,
                    cutout: '50%',
                    plugins: {
                        ...commonOptions.plugins,
                        legend: {
                            display: true,
                            position: 'right',
                            labels: {
                                padding: 12,
                                font: {
                                    size: 10,
                                    weight: '600'
                                },
                                usePointStyle: true,
                                pointStyle: 'circle',
                                generateLabels: function(chart) {
                                    const data = chart.data;
                                    if (data.labels.length && data.datasets.length) {
                                        const dataset = data.datasets[0];
                                        return data.labels.map((label, i) => {
                                            const value = dataset.data[i];
                                            const total = dataset.data.reduce((a, b) => a + b, 0);
                                            const percentage = total > 0 ? ((value / total) * 100).toFixed(1) + '%' : '0%';
                                            return {
                                                text: `${label} (${percentage})`,
                                                fillStyle: dataset.backgroundColor[i],
                                                hidden: isNaN(dataset.data[i]) || chart.getDatasetMeta(0).data[i].hidden,
                                                index: i
                                            };
                                        });
                                    }
                                    return [];
                                }
                            }
                        },
                        datalabels: {
                            ...commonOptions.plugins.datalabels,
                            display: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((context.parsed / total) * 100) : 0;
                                return percentage > 3;
                            }
                        }
                    }
                }
            });
        } else {
            document.getElementById('chartMetode').parentElement.innerHTML = '<div class="no-data"><i class="fas fa-chart-bar"></i><br>Tidak ada data dengan nilai > 0</div>';
        }
    } else {
        document.getElementById('chartMetode').parentElement.innerHTML = '<div class="no-data"><i class="fas fa-chart-bar"></i><br>Tidak ada data</div>';
    }

    // Modal functionality
    const modal = document.getElementById('chartModal');
    const closeBtn = document.getElementsByClassName('close')[0];
    let modalChart = null;

    window.openModal = function(chartId, title) {
        const originalChart = charts[chartId.replace('chart', '').toLowerCase()];
        if (!originalChart) return;

        document.querySelector('.modal-header h3').innerHTML = `<i class="fas fa-chart-line"></i> ${title}`;
        modal.style.display = 'block';
        
        setTimeout(() => {
            const ctx = document.getElementById('modalChart');
            if (modalChart) {
                modalChart.destroy();
            }
            
            modalChart = new Chart(ctx, {
                type: originalChart.config.type,
                data: JSON.parse(JSON.stringify(originalChart.config.data)),
                options: {
                    ...originalChart.config.options,
                    plugins: {
                        ...originalChart.config.options.plugins,
                        legend: {
                            ...originalChart.config.options.plugins.legend,
                            position: 'bottom'
                        }
                    }
                }
            });
        }, 100);
    };

    closeBtn.onclick = function() {
        modal.style.display = 'none';
        if (modalChart) {
            modalChart.destroy();
            modalChart = null;
        }
    };

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
            if (modalChart) {
                modalChart.destroy();
                modalChart = null;
            }
        }
    };

    // Download chart functionality
    window.downloadChart = function(chartId, filename) {
        const canvas = document.getElementById(chartId);
        const link = document.createElement('a');
        link.download = filename + '.png';
        link.href = canvas.toDataURL();
        link.click();
    };

    // Export table to CSV
    window.exportTableToCSV = function() {
        // Buat struktur data yang sesuai dengan format Excel
        let csv = 'PERENCANAAN\n';
        csv += 'NO,METODE PENGADAAN,JUMLAH PAKET RUP,PAGU\n';
        
        // Data metode pengadaan dari PHP
        const metodeData = <?= json_encode($rekap_metode_display) ?>;
        let no = 1;
        
        // Tambahkan data metode satu per satu
        Object.entries(metodeData).forEach(([metode, stats]) => {
            const paket = stats.paket;
            const pagu = stats.pagu;
            csv += `${no},${metode},${paket},${pagu}\n`;
            no++;
        });
        
        // Tambahkan data summary tanpa nomor
        const penyediaData = <?= json_encode($rekap_cara['Penyedia']) ?>;
        const swakelolData = <?= json_encode($rekap_cara['Swakelola']) ?>;
        const totalPaket = <?= $total_paket ?>;
        const totalPagu = <?= $total_pagu ?>;
        
        csv += `Penyedia,${penyediaData.paket},${penyediaData.pagu}\n`;
        csv += `Swakelola,${swakelolData.paket},${swakelolData.pagu}\n`;
        csv += `TOTAL,${totalPaket},${totalPagu}\n`;
        
        // Buat dan download file
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        
        if (link.download !== undefined) {
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', 'PERENCANAAN-' + new Date().toISOString().slice(0, 10) + '.csv');
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    };

    // Print table
    window.printTable = function() {
        window.print();
    };

    // Refresh data
    window.refreshData = function() {
        location.reload();
    };

    // Console log untuk debugging
    console.log('Enhanced Dashboard Data Loaded from Database:');
    console.log('Debug Info:', <?= json_encode($debug_info) ?>);
    console.log('Cara Pengadaan:', dataCara);
    console.log('Jenis Pengadaan:', dataJenis);
    console.log('Metode Pengadaan:', dataMetode);
    
    const timestamp = new Date().toLocaleString('id-ID');
    console.log('Data updated at:', timestamp);
});
</script>

<?php include '../navbar/footer.php'; ?>    