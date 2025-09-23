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
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

<style>
    body {
        background-color: #f4f7f6;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .container {
        max-width: 1400px;
        margin: 30px auto;
        padding: 15px;
    }

    .card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.07);
        margin-bottom: 30px;
        overflow: hidden;
    }

    .card-header {
        background: linear-gradient(135deg, #c82333 0%, #dc3545 100%);
        color: white;
        padding: 18px 25px;
        font-size: 18px;
        font-weight: 600;
        text-align: center;
        letter-spacing: 1px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .table-container {
        padding: 20px;
        overflow-x: auto;
    }

    .styled-table {
        border-collapse: collapse;
        width: 100%;
        font-size: 14px;
    }

    .styled-table th,
    .styled-table td {
        border: 1px solid #ddd;
        padding: 12px 15px;
        text-align: left;
    }

    .styled-table thead {
        background-color: #f2f2f2;
        font-weight: bold;
        text-align: center;
    }

    .styled-table td:first-child,
    .styled-table td:nth-child(3) {
        text-align: center;
    }

    .styled-table td:last-child {
        text-align: right;
    }

    .summary-row {
        font-weight: bold;
        background-color: #f8f9fa;
    }

    .penyedia-row {
        background-color: #e3f2fd;
        font-weight: bold;
    }

    .swakelola-row {
        background-color: #e8f5e8;
        font-weight: bold;
    }

    .total-row {
        font-weight: bold;
        background-color: #fff3cd;
        border-top: 2px solid #dc3545;
    }

    .chart-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 25px;
        padding: 25px;
    }

    .chart-item {
        background: #fff;
        border: 1px solid #f0f0f0;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .chart-item-header {
        background: #f8f9fa;
        color: #444;
        padding: 12px;
        font-weight: 600;
        text-align: center;
        border-bottom: 1px solid #eee;
        font-size: 13px;
    }

    .chart-content {
        padding: 15px;
    }

    .chart-wrapper {
        position: relative;
        height: 320px;
    }

    .info-banner {
        background: linear-gradient(135deg, #17a2b8, #138496);
        color: white;
        padding: 12px 20px;
        border-radius: 5px;
        margin-bottom: 20px;
        font-size: 14px;
        text-align: center;
    }

    .debug-info {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 5px;
        padding: 10px;
        margin-bottom: 20px;
        font-size: 12px;
        color: #666;
    }

    .no-data {
        text-align: center;
        padding: 50px;
        color: #666;
        font-style: italic;
    }

    @media (max-width: 1200px) {
        .chart-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .chart-grid {
            grid-template-columns: 1fr;
            gap: 15px;
            padding: 15px;
        }
        
        .chart-wrapper {
            height: 250px;
        }
        
        .chart-item-header {
            font-size: 12px;
            padding: 10px;
        }
    }
</style>

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
        <div class="card-header"><i class="fas fa-table"></i> PERENCANAAN</div>
        <div class="table-container">
            <?php if (!empty($rekap_metode_display)): ?>
            <table class="styled-table">
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
        <div class="card-header"><i class="fas fa-chart-pie"></i> REKAP BERDASARKAN KATEGORI PENGADAAN</div>
        <div class="chart-grid">
            <div class="chart-item">
                <div class="chart-item-header">
                    <i class="fas fa-chart-pie"></i> REKAP BERDASARKAN CARA PENGADAAN
                </div>
                <div class="chart-content">
                    <div class="chart-wrapper"><canvas id="chartCara"></canvas></div>
                </div>
            </div>
            <div class="chart-item">
                <div class="chart-item-header">
                    <i class="fas fa-chart-doughnut"></i> REKAP BERDASARKAN JENIS PENGADAAN
                </div>
                <div class="chart-content">
                    <div class="chart-wrapper"><canvas id="chartJenis"></canvas></div>
                </div>
            </div>
            <div class="chart-item">
                <div class="chart-item-header">
                    <i class="fas fa-chart-bar"></i> REKAP BERDASARKAN METODE PENGADAAN
                </div>
                <div class="chart-content">
                    <div class="chart-wrapper"><canvas id="chartMetode"></canvas></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const commonOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        font: {
                            size: 11
                        },
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: '#fff',
                    borderWidth: 1,
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
                }
            }
        };

        // Color schemes
        const caraColors = ['#5b9bd5', '#ff9900'];
        const jenisColors = ['#5b9bd5', '#ffff00', '#a5a5a5', '#ff9900'];
        const metodeColors = ['#ff9900', '#70ad47', '#5b9bd5', '#ffc000', '#c55a5a', '#843c0c', '#264478', '#9933cc'];

        // Data dari PHP
        const dataCara = <?= $chart_cara_json ?>;
        const dataJenis = <?= $chart_jenis_json ?>;
        const dataMetode = <?= $chart_metode_json ?>;

        // 1. Chart Cara Pengadaan (Penyedia vs Swakelola)
        if (dataCara.data.some(val => val > 0)) {
            new Chart(document.getElementById('chartCara'), {
                type: 'doughnut',
                data: {
                    labels: dataCara.labels,
                    datasets: [{
                        data: dataCara.data,
                        backgroundColor: caraColors,
                        borderColor: '#fff',
                        borderWidth: 3,
                        hoverBorderWidth: 5
                    }]
                },
                options: {
                    ...commonOptions,
                    cutout: '50%'
                }
            });
        } else {
            document.getElementById('chartCara').parentElement.innerHTML = '<div class="no-data">Tidak ada data</div>';
        }

        // 2. Chart Jenis Pengadaan
        if (dataJenis.data.length > 0 && dataJenis.data.some(val => val > 0)) {
            new Chart(document.getElementById('chartJenis'), {
                type: 'doughnut',
                data: {
                    labels: dataJenis.labels,
                    datasets: [{
                        data: dataJenis.data,
                        backgroundColor: jenisColors.slice(0, dataJenis.labels.length),
                        borderColor: '#fff',
                        borderWidth: 2,
                        hoverBorderWidth: 4
                    }]
                },
                options: {
                    ...commonOptions,
                    cutout: '45%'
                }
            });
        } else {
            document.getElementById('chartJenis').parentElement.innerHTML = '<div class="no-data">Tidak ada data</div>';
        }

        // 3. Chart Metode Pengadaan (Detail) - tampilkan semua termasuk yang 0
        if (dataMetode.data.length > 0) {
            // Filter data untuk chart - hanya tampilkan yang > 0 di chart untuk kejelasan visual
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
                new Chart(document.getElementById('chartMetode'), {
                    type: 'doughnut',
                    data: {
                        labels: filteredData.labels,
                        datasets: [{
                            data: filteredData.data,
                            backgroundColor: metodeColors.slice(0, filteredData.labels.length),
                            borderColor: '#fff',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        ...commonOptions,
                        plugins: {
                            ...commonOptions.plugins,
                            legend: {
                                display: true,
                                position: 'right',
                                labels: {
                                    padding: 8,
                                    font: {
                                        size: 9
                                    },
                                    usePointStyle: true,
                                    pointStyle: 'circle'
                                }
                            }
                        }
                    }
                });
            } else {
                document.getElementById('chartMetode').parentElement.innerHTML = '<div class="no-data">Tidak ada data dengan nilai > 0</div>';
            }
        } else {
            document.getElementById('chartMetode').parentElement.innerHTML = '<div class="no-data">Tidak ada data</div>';
        }

        // Console log untuk debugging
        console.log('Dashboard Data Loaded from Database:');
        console.log('Debug Info:', <?= json_encode($debug_info) ?>);
        console.log('Cara Pengadaan:', dataCara);
        console.log('Jenis Pengadaan:', dataJenis);
        console.log('Metode Pengadaan:', dataMetode);
        
        const timestamp = new Date().toLocaleString('id-ID');
        console.log('Data updated at:', timestamp);
    });
</script>

<?php include '../navbar/footer.php'; ?>    