<?php
// FILE: dashboard_rekapitulasi_bulan.php

// 1. KONEKSI & PENGOLAHAN DATA DARI TABEL ASLI
require_once '../config/database.php';

// Ambil bulan yang dipilih dari parameter GET, default ke bulan saat ini
$bulan_dipilih = isset($_GET['bulan']) ? $_GET['bulan'] : date('F'); // Format: January, February, dll
$tahun_dipilih = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');

// Mapping bulan Indonesia ke Inggris
$bulan_mapping = [
    'Januari' => 'January',
    'Februari' => 'February',
    'Maret' => 'March',
    'April' => 'April',
    'Mei' => 'May',
    'Juni' => 'June',
    'Juli' => 'July',
    'Agustus' => 'August',
    'September' => 'September',
    'Oktober' => 'October',
    'November' => 'November',
    'Desember' => 'December'
];
$bulan_mapping_reverse = array_flip($bulan_mapping);

// Daftar bulan untuk dropdown
$daftar_bulan = array_keys($bulan_mapping);

// Inisialisasi array
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

    // Cari tabel utama untuk data PENYEDIA (utamakan yang bukan swakelola)
    $possible_tables = ['rup_keseluruhan', 'procurement_data', 'pengadaan', 'rup'];
    $main_table = null;
    foreach ($possible_tables as $table) {
        if (in_array($table, $available_tables)) {
            $main_table = $table;
            break;
        }
    }
    if (!$main_table) {
        // Fallback jika tidak ada tabel ideal, cari tabel apapun yang ada data
        foreach ($available_tables as $table) {
            if ($table !== 'rup_swakelola') { // Jangan jadikan rup_swakelola sebagai tabel utama
                try {
                    $check_data = "SELECT COUNT(*) as total FROM $table";
                    $check_stmt = $conn->prepare($check_data);
                    $check_stmt->execute();
                    if ($check_stmt->fetch()['total'] > 0) {
                        $main_table = $table;
                        break;
                    }
                } catch (Exception $e) {
                    continue;
                }
            }
        }
    }

    if (!$main_table) {
        throw new Exception("Tidak ada tabel data utama (Penyedia) yang ditemukan.");
    }

    // Cek kolom yang tersedia di tabel utama
    $describe_sql = "DESCRIBE $main_table";
    $describe_stmt = $conn->prepare($describe_sql);
    $describe_stmt->execute();
    $columns = $describe_stmt->fetchAll(PDO::FETCH_COLUMN);

    // Opsi nama kolom yang mungkin
    $bulan_options = ['Bulan', 'bulan', 'month', 'Month'];
    $tahun_options = ['Tahun', 'tahun', 'year', 'Year'];
    $metode_options = ['Metode', 'metode', 'cara_pengadaan'];
    $pagu_options = ['Pagu_Rp', 'pagu_rp', 'pagu', 'nilai_kontrak'];
    $jenis_options = ['Jenis_Pengadaan', 'jenis_pengadaan', 'jenis', 'kategori'];

    // Fungsi untuk mencari kolom
    function find_column($options, $columns_list)
    {
        foreach ($options as $col) {
            if (in_array($col, $columns_list)) {
                return $col;
            }
        }
        return null;
    }

    // Tentukan kolom yang akan digunakan untuk tabel utama
    $metode_col = find_column($metode_options, $columns);
    $pagu_col = find_column($pagu_options, $columns);
    $jenis_col = find_column($jenis_options, $columns);
    $bulan_col = find_column($bulan_options, $columns);
    $tahun_col = find_column($tahun_options, $columns);

    if (!$metode_col || !$pagu_col) {
        throw new Exception("Kolom metode atau pagu tidak ditemukan di tabel utama '$main_table'. Kolom tersedia: " . implode(', ', $columns));
    }

    // ==================================================================
    // 1. PROSES DATA PENYEDIA DARI TABEL UTAMA ($main_table)
    // ==================================================================
    $where_condition = "WHERE $metode_col IS NOT NULL AND TRIM($metode_col) != '' AND $pagu_col IS NOT NULL AND $pagu_col > 0";
    if ($bulan_col) {
        $bulan_indo = array_search($bulan_dipilih, $bulan_mapping_reverse) ?: $bulan_dipilih;
        $bulan_eng = $bulan_mapping[$bulan_indo] ?? $bulan_dipilih;
        $where_condition .= " AND (LOWER($bulan_col) = LOWER('$bulan_indo') OR LOWER($bulan_col) = LOWER('$bulan_eng'))";
    }
    if ($tahun_col) {
        $where_condition .= " AND $tahun_col = '$tahun_dipilih'";
    }

    $sql_penyedia = "
        SELECT 
            CASE 
                WHEN LOWER($metode_col) LIKE '%e-purchasing%' THEN 'E-Purchasing'
                WHEN LOWER($metode_col) LIKE '%pengadaan langsung%' THEN 'Pengadaan Langsung'
                WHEN LOWER($metode_col) LIKE '%penunjukan langsung%' THEN 'Penunjukan Langsung'
                WHEN LOWER($metode_col) LIKE '%seleksi%' THEN 'Seleksi'
                WHEN LOWER($metode_col) LIKE '%tender cepat%' THEN 'Tender Cepat'
                WHEN LOWER($metode_col) LIKE '%tender%' THEN 'Tender'
                WHEN LOWER($metode_col) LIKE '%dikecualikan%' THEN 'Dikecualikan'
                ELSE TRIM($metode_col)
            END as metode,
            COUNT(*) as jumlah_paket,
            COALESCE(SUM(CAST($pagu_col as DECIMAL(20,2))), 0) as total_pagu
        FROM $main_table 
        $where_condition
        GROUP BY metode
        ORDER BY total_pagu DESC";

    $stmt_penyedia = $conn->prepare($sql_penyedia);
    $stmt_penyedia->execute();

    $total_penyedia_paket = 0;
    $total_penyedia_pagu = 0;
    if ($stmt_penyedia->rowCount() > 0) {
        foreach ($stmt_penyedia->fetchAll() as $row) {
            $metode = trim($row['metode']);
            $paket = (int)$row['jumlah_paket'];
            $pagu = (float)$row['total_pagu'];
            $rekap_metode[$metode] = ['paket' => $paket, 'pagu' => $pagu];
            $total_penyedia_paket += $paket;
            $total_penyedia_pagu += $pagu;
        }
    }
    $rekap_cara['Penyedia'] = ['paket' => $total_penyedia_paket, 'pagu' => $total_penyedia_pagu];


    // ==================================================================
    // 2. PROSES DATA SWAKELOLA DARI TABEL `rup_swakelola` (KHUSUS)
    // ==================================================================
    if (in_array('rup_swakelola', $available_tables)) {
        try {
            $swakelola_table = 'rup_swakelola';
            $describe_swakelola_sql = "DESCRIBE $swakelola_table";
            $stmt_desc_swakelola = $conn->prepare($describe_swakelola_sql);
            $stmt_desc_swakelola->execute();
            $swakelola_columns = $stmt_desc_swakelola->fetchAll(PDO::FETCH_COLUMN);

            $swakelola_pagu_col = find_column($pagu_options, $swakelola_columns);
            $swakelola_bulan_col = find_column($bulan_options, $swakelola_columns);
            $swakelola_tahun_col = find_column($tahun_options, $swakelola_columns);

            $select_pagu_clause = "0 as total_pagu";
            $where_swakelola = "WHERE 1=1";

            if ($swakelola_pagu_col) {
                $select_pagu_clause = "COALESCE(SUM(CAST($swakelola_pagu_col as DECIMAL(20,2))), 0) as total_pagu";
            }

            if ($swakelola_bulan_col) {
                $bulan_indo = array_search($bulan_dipilih, $bulan_mapping_reverse) ?: $bulan_dipilih;
                $bulan_eng = $bulan_mapping[$bulan_indo] ?? $bulan_dipilih;
                $where_swakelola .= " AND (LOWER($swakelola_bulan_col) = LOWER('$bulan_indo') OR LOWER($swakelola_bulan_col) = LOWER('$bulan_eng'))";
            }

            if ($swakelola_tahun_col) {
                $where_swakelola .= " AND $swakelola_tahun_col = '$tahun_dipilih'";
            }

            $sql_swakelola = "
                SELECT 
                    COUNT(*) as jumlah_paket,
                    $select_pagu_clause
                FROM $swakelola_table 
                $where_swakelola";

            $stmt_swakelola = $conn->prepare($sql_swakelola);
            $stmt_swakelola->execute();
            $swakelola_data = $stmt_swakelola->fetch();

            if ($swakelola_data) {
                $rekap_cara['Swakelola']['paket'] = (int)$swakelola_data['jumlah_paket'];
                $rekap_cara['Swakelola']['pagu'] = (float)$swakelola_data['total_pagu'];
            }
        } catch (Exception $e) {
            error_log("Error saat memproses tabel rup_swakelola: " . $e->getMessage());
        }
    } else {
        error_log("Tabel 'rup_swakelola' tidak ditemukan.");
    }


    // ==================================================================
    // 3. PROSES DATA JENIS PENGADAAN (DARI TABEL UTAMA)
    // ==================================================================
    if ($jenis_col) {
        $where_jenis = "WHERE $jenis_col IS NOT NULL AND TRIM($jenis_col) != '' AND $pagu_col IS NOT NULL AND $pagu_col > 0";
        if ($bulan_col) {
            $bulan_indo = array_search($bulan_dipilih, $bulan_mapping_reverse) ?: $bulan_dipilih;
            $bulan_eng = $bulan_mapping[$bulan_indo] ?? $bulan_dipilih;
            $where_jenis .= " AND (LOWER($bulan_col) = LOWER('$bulan_indo') OR LOWER($bulan_col) = LOWER('$bulan_eng'))";
        }
        if ($tahun_col) {
            $where_jenis .= " AND $tahun_col = '$tahun_dipilih'";
        }

        $sql_jenis = "
            SELECT 
                CASE 
                    WHEN LOWER($jenis_col) LIKE '%barang%' THEN 'Barang'
                    WHEN LOWER($jenis_col) LIKE '%konsultansi%' THEN 'Jasa Konsultansi'
                    WHEN LOWER($jenis_col) LIKE '%konstruksi%' THEN 'Pekerjaan Konstruksi'
                    WHEN LOWER($jenis_col) LIKE '%jasa lainnya%' THEN 'Jasa Lainnya'
                    ELSE 'Lainnya'
                END as jenis_kategori,
                COUNT(*) as jumlah_paket,
                COALESCE(SUM(CAST($pagu_col as DECIMAL(20,2))), 0) as total_pagu
            FROM $main_table 
            $where_jenis
            GROUP BY jenis_kategori ORDER BY total_pagu DESC";

        $stmt_jenis = $conn->prepare($sql_jenis);
        $stmt_jenis->execute();
        if ($stmt_jenis->rowCount() > 0) {
            foreach ($stmt_jenis->fetchAll() as $row) {
                $jenis = trim($row['jenis_kategori']);
                if ($jenis && $jenis !== 'Lainnya') {
                    $rekap_jenis[$jenis] = ['paket' => (int)$row['jumlah_paket'], 'pagu' => (float)$row['total_pagu']];
                }
            }
        }
    }

    // Pastikan semua metode standar ada dalam rekap untuk tampilan
    $metode_standar = ['E-Purchasing', 'Pengadaan Langsung', 'Penunjukan Langsung', 'Seleksi', 'Tender', 'Tender Cepat', 'Dikecualikan'];
    foreach ($metode_standar as $metode) {
        if (!isset($rekap_metode[$metode])) {
            $rekap_metode[$metode] = ['paket' => 0, 'pagu' => 0];
        }
    }
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $rekap_metode = [];
    $rekap_jenis = [];
    $rekap_cara = ['Penyedia' => ['paket' => 0, 'pagu' => 0], 'Swakelola' => ['paket' => 0, 'pagu' => 0]];
}

// Filter metode yang akan ditampilkan (yang punya data)
$rekap_metode_display = array_filter($rekap_metode, function ($data) {
    return $data['paket'] > 0 || $data['pagu'] > 0;
});

// Urutkan metode sesuai standar
$metode_order = ['E-Purchasing', 'Pengadaan Langsung', 'Dikecualikan', 'Tender', 'Seleksi', 'Penunjukan Langsung', 'Tender Cepat'];
$rekap_metode_sorted = [];
foreach ($metode_order as $metode) {
    if (isset($rekap_metode_display[$metode])) {
        $rekap_metode_sorted[$metode] = $rekap_metode_display[$metode];
    }
}
$rekap_metode_display = $rekap_metode_sorted;


// Menyiapkan data untuk JavaScript Charts
$chart_cara_json = json_encode(['labels' => array_keys($rekap_cara), 'data' => array_column($rekap_cara, 'pagu')]);
$chart_metode_json = json_encode(['labels' => array_keys($rekap_metode_display), 'data' => array_column($rekap_metode_display, 'pagu')]);
$chart_jenis_json = json_encode(['labels' => array_keys($rekap_jenis), 'data' => array_column($rekap_jenis, 'pagu')]);

// Hitung total keseluruhan
$total_pagu = $rekap_cara['Penyedia']['pagu'] + $rekap_cara['Swakelola']['pagu'];
$total_paket = $rekap_cara['Penyedia']['paket'] + $rekap_cara['Swakelola']['paket'];

// 2. VIEW
$page_title = "Dashboard Perencanaan - " . htmlspecialchars($bulan_dipilih . ' ' . $tahun_dipilih);
include '../navbar/header.php';
?>
<script src="../../js/submenu.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

<style>
    body {
        background: white;
        font-family: 'Inter', sans-serif;
    }

    .container {
        max-width: 1400px;
        margin: 30px auto;
        padding: 15px;
    }

    .filter-section {
        background: linear-gradient(135deg, #fff5f5 0%, #fed7d7 100%);
        border-radius: 15px;
        padding: 25px;
        margin-bottom: 25px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    }

    .filter-section h3 {
        color: #c53030;
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .filter-form {
        display: flex;
        gap: 15px;
        align-items: end;
        flex-wrap: wrap;
    }

    .form-group {
        flex: 1;
        min-width: 200px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #2d3748;
        font-size: 14px;
    }

    .form-group select {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        font-size: 14px;
        background: white;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .form-group select:focus {
        outline: none;
        border-color: #dc3545;
        box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
    }

    .btn-filter {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .btn-filter:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(220, 53, 69, 0.3);
    }

    .card {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        margin-bottom: 30px;
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
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
    }

    .table-container {
        padding: 25px;
        overflow-x: auto;
    }

    .table-controls {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 10px;
    }

    .btn {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white !important;
        border: none;
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 6px;
        text-decoration: none !important;
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(220, 53, 69, 0.3);
    }

    .styled-table {
        border-collapse: collapse;
        width: 100%;
        font-size: 14px;
    }

    .styled-table th,
    .styled-table td {
        padding: 15px;
        text-align: left;
        border-bottom: 1px solid #f0f0f0;
    }

    .styled-table th {
        background: linear-gradient(135deg, #fff5f5 0%, #fed7d7 100%);
        font-weight: 700;
        text-align: center;
        color: #c53030;
    }

    .styled-table tbody tr:hover {
        background: #f9f9f9;
    }

    .styled-table td:first-child,
    .styled-table td:nth-child(3) {
        text-align: center;
        font-weight: 600;
    }

    .styled-table td:last-child {
        text-align: right;
        font-weight: 600;
    }

    .penyedia-row td,
    .swakelola-row td,
    .total-row td {
        font-weight: bold !important;
    }

    .penyedia-row {
        background-color: #fef3c7 !important;
    }

    .swakelola-row {
        background-color: #dbeafe !important;
    }

    .total-row {
        background-color: #d1fae5 !important;
        border-top: 2px solid #059669;
    }

    .chart-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 30px;
        padding: 30px;
    }

    .chart-item {
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }

    .chart-item:hover {
        transform: translateY(-8px);
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
    }

    .chart-item-header {
        background: linear-gradient(135deg, #fff5f5 0%, #fed7d7 100%);
        color: #c53030;
        padding: 18px;
        font-weight: 700;
        text-align: center;
        font-size: 14px;
    }

    .chart-content {
        padding: 25px;
    }

    .chart-wrapper {
        position: relative;
        height: 350px;
    }

    .info-banner {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
        padding: 18px 25px;
        border-radius: 15px;
        margin-bottom: 25px;
        text-align: center;
        box-shadow: 0 8px 25px rgba(220, 53, 69, 0.3);
    }

    .no-data {
        text-align: center;
        padding: 60px;
        color: #718096;
        background: #f7fafc;
        border-radius: 12px;
        margin: 20px 0;
    }

    .no-data i {
        font-size: 48px;
        margin-bottom: 15px;
        opacity: 0.5;
    }

    @media (max-width: 768px) {
        .filter-form {
            flex-direction: column;
        }

        .form-group,
        .btn-filter {
            width: 100%;
        }
    }
</style>

<div class="container">
    <div class="info-banner">
        <i class="fas fa-database"></i> Data Periode: <strong><?= htmlspecialchars($bulan_dipilih . ' ' . $tahun_dipilih) ?></strong> | Update: <?= date('d/m/Y H:i:s') ?>
    </div>

    <div class="filter-section">
        <h3><i class="fas fa-filter"></i> Filter Data</h3>
        <form method="GET" class="filter-form">
            <div class="form-group">
                <label for="bulan"><i class="fas fa-calendar-alt"></i> Pilih Bulan</label>
                <select name="bulan" id="bulan" class="form-select">
                    <?php foreach ($daftar_bulan as $bulan): ?>
                        <option value="<?= $bulan ?>" <?= ($bulan_dipilih == $bulan || $bulan_dipilih == ($bulan_mapping[$bulan] ?? '')) ? 'selected' : '' ?>>
                            <?= $bulan ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="tahun"><i class="fas fa-calendar"></i> Pilih Tahun</label>
                <select name="tahun" id="tahun" class="form-select">
                    <?php $current_year = (int)date('Y');
                    for ($i = $current_year - 3; $i <= $current_year + 1; $i++): ?>
                        <option value="<?= $i ?>" <?= ($tahun_dipilih == $i) ? 'selected' : '' ?>><?= $i ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <button type="submit" class="btn-filter"><i class="fas fa-search"></i> Tampilkan</button>
        </form>
    </div>

    <div class="card">
        <div class="card-header"><i class="fas fa-table"></i> REKAPITULASI PERENCANAAN PENGADAAN</div>
        <div class="table-container">
            <div class="table-controls">
                <div>
                    <strong>Total: <?= number_format($total_paket, 0, ',', '.') ?> Paket</strong> |
                    <strong>Rp <?= number_format($total_pagu, 0, ',', '.') ?></strong>
                </div>
                <div class="btn-group">
                    <a href="export_excel.php?bulan=<?= urlencode($bulan_dipilih) ?>&tahun=<?= $tahun_dipilih ?>" class="btn">
                        <i class="fas fa-file-excel"></i> Export
                    </a>
                </div>
            </div>

            <?php if ($total_paket > 0): ?>
                <table class="styled-table">
                    <thead>
                        <tr>
                            <th>NO</th>
                            <th>METODE PENGADAAN</th>
                            <th>JUMLAH PAKET</th>
                            <th>PAGU (Rp)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; ?>
                        <?php foreach ($rekap_metode_display as $metode => $stats): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= htmlspecialchars($metode) ?></td>
                                <td><?= number_format($stats['paket'], 0, ',', '.') ?></td>
                                <td><?= number_format($stats['pagu'], 0, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>

                        <tr class="penyedia-row">
                            <td colspan="2"><strong>Total Penyedia</strong></td>
                            <td><strong><?= number_format($rekap_cara['Penyedia']['paket'], 0, ',', '.') ?></strong></td>
                            <td><strong><?= number_format($rekap_cara['Penyedia']['pagu'], 0, ',', '.') ?></strong></td>
                        </tr>
                        <tr class="swakelola-row">
                            <td colspan="2"><strong>Total Swakelola</strong></td>
                            <td><strong><?= number_format($rekap_cara['Swakelola']['paket'], 0, ',', '.') ?></strong></td>
                            <td><strong><?= number_format($rekap_cara['Swakelola']['pagu'], 0, ',', '.') ?></strong></td>
                        </tr>
                        <tr class="total-row">
                            <td colspan="2"><strong>TOTAL KESELURUHAN</strong></td>
                            <td><strong><?= number_format($total_paket, 0, ',', '.') ?></strong></td>
                            <td><strong><?= number_format($total_pagu, 0, ',', '.') ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-exclamation-triangle"></i><br>
                    Tidak ada data ditemukan untuk periode <strong><?= htmlspecialchars($bulan_dipilih . ' ' . $tahun_dipilih) ?></strong>.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($total_paket > 0): ?>
        <div class="card">
            <div class="card-header"><i class="fas fa-chart-pie"></i> VISUALISASI DATA</div>
            <div class="chart-grid">
                <div class="chart-item">
                    <div class="chart-item-header">CARA PENGADAAN</div>
                    <div class="chart-content">
                        <div class="chart-wrapper"><canvas id="chartCara"></canvas></div>
                    </div>
                </div>
                <div class="chart-item">
                    <div class="chart-item-header">JENIS PENGADAAN</div>
                    <div class="chart-content">
                        <div class="chart-wrapper"><canvas id="chartJenis"></canvas></div>
                    </div>
                </div>
                <div class="chart-item">
                    <div class="chart-item-header">METODE PENGADAAN (PENYEDIA)</div>
                    <div class="chart-content">
                        <div class="chart-wrapper"><canvas id="chartMetode"></canvas></div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Pastikan Chart.js sudah termuat
        if (typeof Chart === 'undefined') {
            console.error('Chart.js is not loaded.');
            return;
        }
        // Daftarkan plugin yang dibutuhkan
        Chart.register(ChartDataLabels);

        const formatRupiah = (value) => new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(value);

        // Opsi umum untuk semua chart
        const commonOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                        // Fungsi untuk generate label custom
                        generateLabels: function(chart) {
                            const data = chart.data;
                            if (data.labels.length && data.datasets.length) {
                                const dataset = data.datasets[0];
                                const total = dataset.data.reduce((a, b) => a + b, 0);
                                return data.labels.map((label, i) => {
                                    const value = dataset.data[i];
                                    const percentage = total > 0 ? ((value / total) * 100).toFixed(1) + '%' : '0%';
                                    return {
                                        text: `${label} (${percentage})`, // <-- PERBAIKAN DI SINI
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
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed;
                            return `${label}: ${formatRupiah(value)}`;
                        }
                    }
                },
                datalabels: {
                    color: '#fff',
                    font: {
                        weight: 'bold'
                    },
                    formatter: (value, context) => {
                        const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                        const percentage = total > 0 ? (value / total) * 100 : 0;
                        return percentage > 5 ? percentage.toFixed(1) + '%' : '';
                    }
                }
            }
        };

        // Ambil data dari PHP
        const caraData = <?= $chart_cara_json ?>;
        const jenisData = <?= $chart_jenis_json ?>;
        const metodeData = <?= $chart_metode_json ?>;

        // Chart 1: Cara Pengadaan (Pie)
        const chartCaraCanvas = document.getElementById('chartCara');
        if (chartCaraCanvas && caraData.data.some(d => d > 0)) {
            new Chart(chartCaraCanvas, {
                type: 'pie',
                data: {
                    labels: caraData.labels,
                    datasets: [{
                        data: caraData.data,
                        backgroundColor: ['#36A2EB', '#FF6384', '#FFCE56', '#4BC0C0']
                    }]
                },
                options: commonOptions
            });
        }

        // Chart 2: Jenis Pengadaan (Doughnut)
        const chartJenisCanvas = document.getElementById('chartJenis');
        if (chartJenisCanvas && jenisData.data.some(d => d > 0)) {
            new Chart(chartJenisCanvas, {
                type: 'doughnut',
                data: {
                    labels: jenisData.labels,
                    datasets: [{
                        data: jenisData.data,
                        backgroundColor: ['#FFCE56', '#4BC0C0', '#FF9F40', '#9966FF', '#C9CBCF']
                    }]
                },
                options: commonOptions
            });
        }

        // Chart 3: Metode Pengadaan (Bar)
        const chartMetodeCanvas = document.getElementById('chartMetode');
        if (chartMetodeCanvas && metodeData.data.some(d => d > 0)) {
            new Chart(chartMetodeCanvas, {
                type: 'bar',
                data: {
                    labels: metodeData.labels,
                    datasets: [{
                        label: 'Pagu (Rp)',
                        data: metodeData.data,
                        backgroundColor: '#4BC0C0'
                    }]
                },
                options: {
                    ...commonOptions,
                    indexAxis: 'y', // Membuat bar chart menjadi horizontal
                    plugins: {
                        ...commonOptions.plugins,
                        legend: {
                            display: false
                        }, // Sembunyikan legend untuk bar chart
                        datalabels: { // Custom datalabels untuk bar chart
                            color: '#333',
                            anchor: 'end',
                            align: 'end',
                            formatter: (value) => value > 0 ? new Intl.NumberFormat('id-ID').format(value) : '',
                        }
                    }
                }
            });
        }
    });
</script>

<?php include '../navbar/footer.php'; ?>