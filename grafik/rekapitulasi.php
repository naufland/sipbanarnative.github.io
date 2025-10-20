<?php
// FILE: dashboard_rekapitulasi_bulan.php
// VERSI TERINTEGRASI: PERENCANAAN + REALISASI + PENCATATAN

require_once '../config/database.php';

$bulan_dipilih = isset($_GET['bulan']) ? $_GET['bulan'] : 'Juli';
$tahun_dipilih = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');
$perubahan_ke = isset($_GET['perubahan']) ? $_GET['perubahan'] : '';

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
$daftar_bulan = array_keys($bulan_mapping);

// Initialize arrays
$rekap_metode = [];
$rekap_jenis = [];
$rekap_cara = ['Penyedia' => ['paket' => 0, 'pagu' => 0], 'Swakelola' => ['paket' => 0, 'pagu' => 0]];

// Arrays untuk realisasi
$realisasi_metode = [];
$realisasi_jenis = [];
$realisasi_cara = ['Penyedia' => ['paket' => 0, 'pagu' => 0], 'Swakelola' => ['paket' => 0, 'pagu' => 0]];

try {
    $database = new Database();
    $conn = $database->getConnection();

    $bulan_indo = $bulan_dipilih;
    $bulan_eng = $bulan_mapping[$bulan_indo] ?? $bulan_dipilih;

    if (in_array($bulan_dipilih, $bulan_mapping)) {
        $bulan_eng = $bulan_dipilih;
        $bulan_indo = array_search($bulan_dipilih, $bulan_mapping);
    }

    // ==================== DATA PERENCANAAN ====================

    // 1. Metode Pengadaan Perencanaan
    $where_conditions = "WHERE Metode IS NOT NULL AND TRIM(Metode) != '' AND Pagu_Rp IS NOT NULL AND Pagu_Rp > 0
        AND (LOWER(Bulan) = LOWER(:bulan_indo) OR LOWER(Bulan) = LOWER(:bulan_eng)) AND Tahun = :tahun";

    if (!empty($perubahan_ke)) {
        $where_conditions .= " AND Perubahan = :perubahan";
    }

    $sql_penyedia = "
        SELECT 
            CASE 
                WHEN LOWER(Metode) LIKE '%e-purchasing%' THEN 'E-Purchasing'
                WHEN LOWER(Metode) LIKE '%pengadaan langsung%' THEN 'Pengadaan Langsung'
                WHEN LOWER(Metode) LIKE '%penunjukan langsung%' THEN 'Penunjukan Langsung'
                WHEN LOWER(Metode) LIKE '%seleksi%' THEN 'Seleksi'
                WHEN LOWER(Metode) LIKE '%tender cepat%' THEN 'Tender Cepat'
                WHEN LOWER(Metode) LIKE '%tender%' THEN 'Tender'
                WHEN LOWER(Metode) LIKE '%dikecualikan%' THEN 'Dikecualikan'
                ELSE TRIM(Metode)
            END as metode,
            COUNT(*) as jumlah_paket,
            COALESCE(SUM(CAST(Pagu_Rp as DECIMAL(20,2))), 0) as total_pagu
        FROM rup_keseluruhan 
        $where_conditions
        GROUP BY metode
        ORDER BY total_pagu DESC";

    $stmt_penyedia = $conn->prepare($sql_penyedia);
    $stmt_penyedia->bindParam(':bulan_indo', $bulan_indo);
    $stmt_penyedia->bindParam(':bulan_eng', $bulan_eng);
    $stmt_penyedia->bindParam(':tahun', $tahun_dipilih);

    if (!empty($perubahan_ke)) {
        $stmt_penyedia->bindParam(':perubahan', $perubahan_ke);
    }

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

    // 2. Swakelola Perencanaan
    $check_swakelola_table = "SHOW TABLES LIKE 'rup_swakelola'";
    $check_swakelola_stmt = $conn->prepare($check_swakelola_table);
    $check_swakelola_stmt->execute();
    $has_swakelola_table = $check_swakelola_stmt->rowCount() > 0;

    if ($has_swakelola_table) {
        $check_swakelola_column = "SHOW COLUMNS FROM rup_swakelola LIKE 'Perubahan'";
        $check_swakelola_col_stmt = $conn->prepare($check_swakelola_column);
        $check_swakelola_col_stmt->execute();
        $has_swakelola_perubahan = $check_swakelola_col_stmt->rowCount() > 0;

        $params_swakelola = [':bulan_indo' => $bulan_indo, ':bulan_eng' => $bulan_eng, ':tahun' => $tahun_dipilih];
        $where_swakelola = "WHERE (LOWER(Bulan) = LOWER(:bulan_indo) OR LOWER(Bulan) = LOWER(:bulan_eng)) AND Tahun = :tahun";

        if ($has_swakelola_perubahan && !empty($perubahan_ke)) {
            $where_swakelola .= " AND Perubahan = :perubahan";
            $params_swakelola[':perubahan'] = $perubahan_ke;
        }

        $sql_swakelola = "SELECT COUNT(*) as jumlah_paket, COALESCE(SUM(CAST(Pagu_Rp as DECIMAL(20,2))), 0) as total_pagu
            FROM rup_swakelola $where_swakelola";

        $stmt_swakelola = $conn->prepare($sql_swakelola);
        $stmt_swakelola->execute($params_swakelola);
        $swakelola_data = $stmt_swakelola->fetch();

        if ($swakelola_data) {
            $rekap_cara['Swakelola']['paket'] = (int)$swakelola_data['jumlah_paket'];
            $rekap_cara['Swakelola']['pagu'] = (float)$swakelola_data['total_pagu'];
        }
    }

    // ==================== DATA REALISASI ====================

    $config_tabel_realisasi = [
        'realisasi_tender' => [
            'metode' => 'Tender',
            'nilai_col' => 'Nilai_Kontrak',
            'tahun_col' => 'Tahun_Anggaran',
            'is_swakelola' => false,
            'cast_to_decimal' => true
        ],
        'realisasi_seleksi' => [
            'metode' => 'Seleksi',
            'nilai_col' => 'Nilai_Kontrak',
            'tahun_col' => 'Tahun_Anggaran',
            'is_swakelola' => false,
            'cast_to_decimal' => true
        ],
        'realisasi_nontender' => [
            'metode' => 'Pengadaan Langsung',
            'nilai_col' => 'Nilai_Kontrak',
            'tahun_col' => 'Tahun_Anggaran',
            'is_swakelola' => false,
            'cast_to_decimal' => true
        ],
        'realisasi_epurchasing' => [
            'metode' => 'E-Purchasing',
            'nilai_col' => 'total_harga',
            'tahun_col' => 'tahun_anggaran',
            'is_swakelola' => false,
            'cast_to_decimal' => true
        ],
        'realisasi_penunjukanlangsung' => [
            'metode' => 'Penunjukan Langsung',
            'nilai_col' => 'Nilai_Kontrak',
            'tahun_col' => 'Tahun_Anggaran',
            'is_swakelola' => false,
            'cast_to_decimal' => true,
            'force_cast' => true
        ],
        'realisasi_dikecualikan' => [
            'metode' => 'Dikecualikan',
            'nilai_col' => 'Nilai_Total_Realisasi',
            'tahun_col' => 'Tahun_Anggaran',
            'is_swakelola' => false,
            'cast_to_decimal' => true
        ],
        'realisasi_pengadaandarurat' => [
            'metode' => 'Pengadaan Darurat',
            'nilai_col' => 'Nilai_Total_Realisasi',
            'tahun_col' => 'Tahun_Anggaran',
            'is_swakelola' => false,
            'cast_to_decimal' => true
        ],
        'realisasi_swakelola' => [
            'metode' => 'Swakelola',
            'nilai_col' => 'Nilai_Total_Realisasi',
            'tahun_col' => 'Tahun_Anggaran',
            'is_swakelola' => true,
            'cast_to_decimal' => true
        ]
    ];

    $config_pencatatan = [
        'pencatatan_nontender' => [
            'metode' => 'Pengadaan Langsung',
            'nilai_col' => 'Nilai_Total_Realisasi',
            'tahun_col' => 'Tahun_Anggaran',
            'cast_to_decimal' => true
        ]
    ];

    $debug_realisasi = [];
    $total_swakelola_real_paket = 0;
    $total_swakelola_real_pagu = 0;

    $pencatatan_metode = [];
    $pencatatan_total_paket = 0;
    $pencatatan_total_pagu = 0;

    // PROSES REALISASI BIASA
    foreach ($config_tabel_realisasi as $tabel => $config) {
        try {
            $check_table = "SHOW TABLES LIKE '$tabel'";
            $check_stmt = $conn->prepare($check_table);
            $check_stmt->execute();

            if ($check_stmt->rowCount() === 0) {
                continue;
            }

            $check_cols = "SHOW COLUMNS FROM $tabel";
            $check_cols_stmt = $conn->prepare($check_cols);
            $check_cols_stmt->execute();
            $cols = [];
            foreach ($check_cols_stmt->fetchAll() as $col_row) {
                $cols[] = $col_row['Field'];
            }

            $bulan_col = null;
            foreach (['bulan', 'Bulan', 'BULAN'] as $b) {
                if (in_array($b, $cols)) {
                    $bulan_col = $b;
                    break;
                }
            }

            if (!$bulan_col) {
                continue;
            }

            $tahun_col = null;
            $possible_tahun = [$config['tahun_col'], 'tahun', 'Tahun', 'TAHUN', 'Tahun_Anggaran', 'tahun_anggaran'];
            foreach ($possible_tahun as $t) {
                if (in_array($t, $cols)) {
                    $tahun_col = $t;
                    break;
                }
            }

            $nilai_col = null;
            $possible_nilai = [
                $config['nilai_col'],
                'Nilai_Kontrak',
                'nilai_kontrak',
                'Nilai_Total_Realisasi',
                'nilai_total_realisasi',
                'Nilai_Pagu',
                'nilai_pagu',
                'total_harga',
                'Total_Harga',
                'Pagu_Rp',
                'pagu_rp'
            ];
            foreach ($possible_nilai as $n) {
                if (in_array($n, $cols)) {
                    $nilai_col = $n;
                    break;
                }
            }

            if (!$tahun_col || !$nilai_col) {
                continue;
            }

            if (isset($config['force_cast']) && $config['force_cast']) {
                $nilai_expression = "CAST(CAST($nilai_col as CHAR) as DECIMAL(20,2))";
            } else {
                $nilai_expression = "CAST($nilai_col as DECIMAL(20,2))";
            }

            $sql_real = "SELECT 
                COUNT(*) as jumlah_paket,
                COALESCE(SUM($nilai_expression), 0) as total_pagu
                FROM $tabel 
                WHERE $nilai_col IS NOT NULL 
                AND $nilai_col != '' 
                AND $nilai_col != '0'
                AND (LOWER($bulan_col) = LOWER(:bulan_indo) OR LOWER($bulan_col) = LOWER(:bulan_eng))
                AND $tahun_col = :tahun";

            $stmt_real = $conn->prepare($sql_real);
            $stmt_real->bindParam(':bulan_indo', $bulan_indo);
            $stmt_real->bindParam(':bulan_eng', $bulan_eng);
            $stmt_real->bindParam(':tahun', $tahun_dipilih);
            $stmt_real->execute();

            $data = $stmt_real->fetch();

            if ($data) {
                $paket = (int)$data['jumlah_paket'];
                $pagu = (float)$data['total_pagu'];

                if ($paket > 0 || $pagu > 0) {
                    $metode = $config['metode'];

                    if ($config['is_swakelola']) {
                        $total_swakelola_real_paket += $paket;
                        $total_swakelola_real_pagu += $pagu;
                    } else {
                        if (isset($realisasi_metode[$metode])) {
                            $realisasi_metode[$metode]['paket'] += $paket;
                            $realisasi_metode[$metode]['pagu'] += $pagu;
                        } else {
                            $realisasi_metode[$metode] = [
                                'paket' => $paket,
                                'pagu' => $pagu
                            ];
                        }
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Error querying $tabel: " . $e->getMessage());
        }
    }

    // PROSES PENCATATAN NONTENDER
    foreach ($config_pencatatan as $tabel => $config) {
        try {
            $check_table = "SHOW TABLES LIKE '$tabel'";
            $check_stmt = $conn->prepare($check_table);
            $check_stmt->execute();

            if ($check_stmt->rowCount() === 0) {
                continue;
            }

            $check_cols = "SHOW COLUMNS FROM $tabel";
            $check_cols_stmt = $conn->prepare($check_cols);
            $check_cols_stmt->execute();
            $cols = [];
            foreach ($check_cols_stmt->fetchAll() as $col_row) {
                $cols[] = $col_row['Field'];
            }

            $bulan_col = null;
            foreach (['bulan', 'Bulan', 'BULAN'] as $b) {
                if (in_array($b, $cols)) {
                    $bulan_col = $b;
                    break;
                }
            }

            if (!$bulan_col) {
                continue;
            }

            $tahun_col = null;
            $possible_tahun = [$config['tahun_col'], 'tahun', 'Tahun', 'TAHUN', 'Tahun_Anggaran', 'tahun_anggaran'];
            foreach ($possible_tahun as $t) {
                if (in_array($t, $cols)) {
                    $tahun_col = $t;
                    break;
                }
            }

            $nilai_col = $config['nilai_col'];
            if (!in_array($nilai_col, $cols)) {
                $nilai_col = null;
            }

            if (!$tahun_col || !$nilai_col) {
                continue;
            }

            $sql_pencatatan = "SELECT 
                COUNT(*) as jumlah_paket,
                COALESCE(SUM(CAST($nilai_col as DECIMAL(20,2))), 0) as total_pagu
                FROM $tabel 
                WHERE $nilai_col IS NOT NULL AND $nilai_col > 0
                AND (LOWER($bulan_col) = LOWER(:bulan_indo) OR LOWER($bulan_col) = LOWER(:bulan_eng))
                AND $tahun_col = :tahun";

            $stmt_pencatatan = $conn->prepare($sql_pencatatan);
            $stmt_pencatatan->bindParam(':bulan_indo', $bulan_indo);
            $stmt_pencatatan->bindParam(':bulan_eng', $bulan_eng);
            $stmt_pencatatan->bindParam(':tahun', $tahun_dipilih);
            $stmt_pencatatan->execute();

            $data = $stmt_pencatatan->fetch();

            if ($data) {
                $paket = (int)$data['jumlah_paket'];
                $pagu = (float)$data['total_pagu'];

                if ($paket > 0 || $pagu > 0) {
                    $metode = $config['metode'];

                    if (isset($pencatatan_metode[$metode])) {
                        $pencatatan_metode[$metode]['paket'] += $paket;
                        $pencatatan_metode[$metode]['pagu'] += $pagu;
                    } else {
                        $pencatatan_metode[$metode] = [
                            'paket' => $paket,
                            'pagu' => $pagu
                        ];
                    }

                    $pencatatan_total_paket += $paket;
                    $pencatatan_total_pagu += $pagu;
                }
            }
        } catch (Exception $e) {
            error_log("Error querying $tabel: " . $e->getMessage());
        }
    }

    // Update total realisasi
    foreach ($realisasi_metode as $metode => $stats) {
        $realisasi_cara['Penyedia']['paket'] += $stats['paket'];
        $realisasi_cara['Penyedia']['pagu'] += $stats['pagu'];
    }

    $realisasi_cara['Swakelola']['paket'] = $total_swakelola_real_paket;
    $realisasi_cara['Swakelola']['pagu'] = $total_swakelola_real_pagu;

    // Sinkronkan metode standar
    $metode_standar = ['E-Purchasing', 'Pengadaan Langsung', 'Penunjukan Langsung', 'Seleksi', 'Tender', 'Tender Cepat', 'Dikecualikan'];
    foreach ($metode_standar as $metode) {
        if (!isset($rekap_metode[$metode])) {
            $rekap_metode[$metode] = ['paket' => 0, 'pagu' => 0];
        }
    }
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
}

// Filter dan urutkan
$rekap_metode_display = array_filter($rekap_metode, function ($data) {
    return $data['paket'] > 0 || $data['pagu'] > 0;
});

$metode_order = ['E-Purchasing', 'Pengadaan Langsung', 'Dikecualikan', 'Tender', 'Seleksi', 'Penunjukan Langsung', 'Tender Cepat'];
$rekap_metode_sorted = [];
foreach ($metode_order as $metode) {
    if (isset($rekap_metode_display[$metode])) {
        $rekap_metode_sorted[$metode] = $rekap_metode_display[$metode];
    }
}
$rekap_metode_display = $rekap_metode_sorted;

$total_pagu = $rekap_cara['Penyedia']['pagu'] + $rekap_cara['Swakelola']['pagu'];
$total_paket = $rekap_cara['Penyedia']['paket'] + $rekap_cara['Swakelola']['paket'];

$total_real_pagu = $realisasi_cara['Penyedia']['pagu'] + $realisasi_cara['Swakelola']['pagu'];
$total_real_paket = $realisasi_cara['Penyedia']['paket'] + $realisasi_cara['Swakelola']['paket'];

// Total dengan pencatatan (DITAMBAHKAN KE TOTAL UTAMA)
$total_real_pagu_dengan_pencatatan = $total_real_pagu + $pencatatan_total_pagu;
$total_real_paket_dengan_pencatatan = $total_real_paket + $pencatatan_total_paket;

$bulan_tampil = $bulan_indo;
$daftar_perubahan = ['', 'Perubahan', 'Tidak'];
$perubahan_label = $perubahan_ke === '' ? 'Semua Data' : ($perubahan_ke === 'Perubahan' ? 'Data Perubahan' : 'Data Tidak Perubahan');

$page_title = "Dashboard Perencanaan & Realisasi - " . htmlspecialchars($bulan_tampil . ' ' . $tahun_dipilih);
include '../navbar/header.php';

// ==================== TAMBAHAN KODE UNTUK GRAFIK ====================
// Letakkan kode ini sebelum penutup tag PHP terakhir di file Anda

// Data untuk Grafik 1: REKAP BERDASARKAN CARA PENGADAAN
$grafik_cara = [
    'Penyedia' => $rekap_cara['Penyedia']['pagu'],
    'Swakelola' => $rekap_cara['Swakelola']['pagu']
];

// Data untuk Grafik 2: REKAP BERDASARKAN JENIS PENGADAAN
$sql_jenis = "
    SELECT 
        CASE 
            WHEN LOWER(Jenis_Pengadaan) LIKE '%barang%' THEN 'Barang'
            WHEN LOWER(Jenis_Pengadaan) LIKE '%jasa konsultansi%' THEN 'Jasa Konsultansi'
            WHEN LOWER(Jenis_Pengadaan) LIKE '%jasa lainnya%' THEN 'Jasa Lainnya'
            WHEN LOWER(Jenis_Pengadaan) LIKE '%pekerjaan konstruksi%' THEN 'Pekerjaan Konstruksi'
            ELSE TRIM(Jenis_Pengadaan)
        END as jenis,
        COALESCE(SUM(CAST(Pagu_Rp as DECIMAL(20,2))), 0) as total_pagu
    FROM rup_keseluruhan 
    WHERE Jenis_Pengadaan IS NOT NULL 
    AND TRIM(Jenis_Pengadaan) != '' 
    AND Pagu_Rp IS NOT NULL 
    AND Pagu_Rp > 0
    AND (LOWER(Bulan) = LOWER(:bulan_indo) OR LOWER(Bulan) = LOWER(:bulan_eng)) 
    AND Tahun = :tahun";

if (!empty($perubahan_ke)) {
    $sql_jenis .= " AND Perubahan = :perubahan";
}

$sql_jenis .= " GROUP BY jenis ORDER BY total_pagu DESC";

$stmt_jenis = $conn->prepare($sql_jenis);
$stmt_jenis->bindParam(':bulan_indo', $bulan_indo);
$stmt_jenis->bindParam(':bulan_eng', $bulan_eng);
$stmt_jenis->bindParam(':tahun', $tahun_dipilih);

if (!empty($perubahan_ke)) {
    $stmt_jenis->bindParam(':perubahan', $perubahan_ke);
}

$stmt_jenis->execute();

$grafik_jenis = [];
foreach ($stmt_jenis->fetchAll() as $row) {
    $jenis = trim($row['jenis']);
    $pagu = (float)$row['total_pagu'];
    $grafik_jenis[$jenis] = $pagu;
}

// Data untuk Grafik 3: REKAP BERDASARKAN METODE PENGADAAN (sama dengan $rekap_metode_display)
$grafik_metode = [];
foreach ($rekap_metode_display as $metode => $stats) {
    $grafik_metode[$metode] = $stats['pagu'];
}

?>

<script src="../../js/submenu.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

<style>
    .charts-section {
    margin-top: 30px;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

.chart-card {
    background: rgba(255, 255, 255, 0.95);
    border-radius: 20px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    overflow: hidden;
}

.chart-card-header {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
    padding: 15px 20px;
    font-size: 14px;
    font-weight: 700;
    text-align: center;
}

.chart-container {
    padding: 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.chart-canvas {
    max-width: 100%;
    height: 300px;
}

@media (max-width: 1200px) {
    .charts-section {
        grid-template-columns: 1fr;
    }
}
    body {
        background: white;
        font-family: 'Inter', sans-serif;
    }

    .container {
        max-width: 1600px;
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
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        align-items: end;
    }

    .form-group {
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
        justify-content: center;
        gap: 8px;
        height: 48px;
        white-space: nowrap;
    }

    .btn-filter:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(220, 53, 69, 0.3);
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

    .comparison-section {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 30px;
    }

    .card {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        overflow: hidden;
    }

    .card-header {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
        padding: 20px 25px;
        font-size: 16px;
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

    .styled-table {
        border-collapse: collapse;
        width: 100%;
        font-size: 13px;
    }

    .styled-table th,
    .styled-table td {
        padding: 12px 10px;
        text-align: left;
        border-bottom: 1px solid #f0f0f0;
    }

    .styled-table th {
        background: linear-gradient(135deg, #fff5f5 0%, #fed7d7 100%);
        font-weight: 700;
        text-align: center;
        color: #c53030;
        font-size: 12px;
    }

    .styled-table tbody tr:hover {
        background: #f9f9f9;
    }

    .styled-table td:first-child {
        text-align: center;
        font-weight: 600;
        width: 40px;
    }

    .styled-table td:nth-child(2) {
        text-align: left;
    }

    .styled-table td:nth-child(3),
    .styled-table td:nth-child(4) {
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

    .pencatatan-row {
        background-color: #fee2e2 !important;
        border-top: 2px solid #dc2626;
    }

    .no-data {
        text-align: center;
        padding: 40px;
        color: #718096;
        background: #f7fafc;
        border-radius: 12px;
        margin: 20px 0;
    }

    .card-header-red {
        background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%) !important;
    }

    @media (max-width: 1024px) {
        .comparison-section {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .filter-form {
            grid-template-columns: 1fr;
        }

        .filter-form .btn-filter {
            width: 100%;
        }
    }
</style>

<div class="container">
    <div class="info-banner">
        <i class="fas fa-database"></i> Data Periode: <strong><?= htmlspecialchars($bulan_tampil . ' ' . $tahun_dipilih) ?></strong>
        <?php if ($perubahan_ke): ?>
            | Filter: <strong><?= htmlspecialchars($perubahan_label) ?></strong>
        <?php endif; ?>
        | Update: <?= date('d/m/Y H:i:s') ?>
    </div>

    <div class="filter-section">
        <h3><i class="fas fa-filter"></i> Filter Data</h3>
        <form method="GET" class="filter-form">
            <div class="form-group">
                <label for="bulan"><i class="fas fa-calendar-alt"></i> Pilih Bulan</label>
                <select name="bulan" id="bulan" class="form-select">
                    <?php foreach ($daftar_bulan as $bulan): ?>
                        <option value="<?= $bulan ?>" <?= ($bulan_tampil == $bulan) ? 'selected' : '' ?>>
                            <?= $bulan ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="tahun"><i class="fas fa-calendar"></i> Pilih Tahun</label>
                <select name="tahun" id="tahun" class="form-select">
                    <?php
                    $current_year = (int)date('Y');
                    for ($i = $current_year - 3; $i <= $current_year + 1; $i++):
                    ?>
                        <option value="<?= $i ?>" <?= ($tahun_dipilih == $i) ? 'selected' : '' ?>><?= $i ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="perubahan"><i class="fas fa-exchange-alt"></i> Status Perubahan</label>
                <select name="perubahan" id="perubahan" class="form-select">
                    <option value="" <?= ($perubahan_ke === '') ? 'selected' : '' ?>>Semua Data</option>
                    <option value="Perubahan" <?= ($perubahan_ke === 'Perubahan') ? 'selected' : '' ?>>Perubahan</option>
                    <option value="Tidak" <?= ($perubahan_ke === 'Tidak') ? 'selected' : '' ?>>Tidak Perubahan</option>
                </select>
            </div>
            <button type="submit" class="btn-filter"><i class="fas fa-search"></i> Tampilkan</button>
        </form>
    </div>
    <div class="charts-section">
    <!-- Grafik 1: Cara Pengadaan -->
    <div class="chart-card">
        <div class="chart-card-header">REKAP BERDASARKAN CARA PENGADAAN</div>
        <div class="chart-container">
            <canvas id="chartCara" class="chart-canvas"></canvas>
        </div>
    </div>

    <!-- Grafik 2: Jenis Pengadaan -->
    <div class="chart-card">
        <div class="chart-card-header">REKAP BERDASARKAN JENIS PENGADAAN</div>
        <div class="chart-container">
            <canvas id="chartJenis" class="chart-canvas"></canvas>
        </div>
    </div>

    <!-- Grafik 3: Metode Pengadaan -->
    <div class="chart-card">
        <div class="chart-card-header">REKAP BERDASARKAN METODE PENGADAAN</div>
        <div class="chart-container">
            <canvas id="chartMetode" class="chart-canvas"></canvas>
        </div>
    </div>
</div>

    <!-- PERBANDINGAN PERENCANAAN vs REALISASI -->
    <div class="comparison-section">
        <!-- PERENCANAAN -->
        <div class="card">
            <div class="card-header"><i class="fas fa-tasks"></i> PERENCANAAN PENGADAAN</div>
            <div class="table-container">
                <div class="table-controls">
                    <div style="font-size: 12px;">
                        <strong>Total: <?= number_format($total_paket, 0, ',', '.') ?> Paket</strong><br>
                        <strong>Rp <?= number_format($total_pagu, 0, ',', '.') ?></strong>
                    </div>
                </div>

                <?php if ($total_paket > 0): ?>
                    <table class="styled-table">
                        <thead>
                            <tr>
                                <th>NO</th>
                                <th>METODE</th>
                                <th>PAKET</th>
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
                                <td colspan="2">Total Penyedia</td>
                                <td><?= number_format($rekap_cara['Penyedia']['paket'], 0, ',', '.') ?></td>
                                <td><?= number_format($rekap_cara['Penyedia']['pagu'], 0, ',', '.') ?></td>
                            </tr>
                            <tr class="swakelola-row">
                                <td colspan="2">Total Swakelola</td>
                                <td><?= number_format($rekap_cara['Swakelola']['paket'], 0, ',', '.') ?></td>
                                <td><?= number_format($rekap_cara['Swakelola']['pagu'], 0, ',', '.') ?></td>
                            </tr>
                            <tr class="total-row">
                                <td colspan="2">TOTAL</td>
                                <td><?= number_format($total_paket, 0, ',', '.') ?></td>
                                <td><?= number_format($total_pagu, 0, ',', '.') ?></td>
                            </tr>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-exclamation-triangle"></i><br>
                        Tidak ada data perencanaan ditemukan
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- REALISASI -->
        <div class="card">
            <div class="card-header"><i class="fas fa-check-circle"></i> REALISASI PENGADAAN</div>
            <div class="table-container">
                <div class="table-controls">
                    <div style="font-size: 12px;">
                        <strong>Total: <?= number_format($total_real_paket_dengan_pencatatan, 0, ',', '.') ?> Paket</strong><br>
                        <strong>Rp <?= number_format($total_real_pagu_dengan_pencatatan, 0, ',', '.') ?></strong>
                    </div>
                </div>

                <?php if ($total_real_paket_dengan_pencatatan > 0): ?>
                    <table class="styled-table">
                        <thead>
                            <tr>
                                <th>NO</th>
                                <th>METODE</th>
                                <th>PAKET</th>
                                <th>PAGU (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; ?>
                            <?php foreach ($realisasi_metode as $metode => $stats): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= htmlspecialchars($metode) ?></td>
                                    <td><?= number_format($stats['paket'], 0, ',', '.') ?></td>
                                    <td><?= number_format($stats['pagu'], 0, ',', '.') ?></td>
                                </tr>
                            <?php endforeach; ?>

                            <tr class="penyedia-row">
                                <td colspan="2">Total Penyedia</td>
                                <td><?= number_format($realisasi_cara['Penyedia']['paket'], 0, ',', '.') ?></td>
                                <td><?= number_format($realisasi_cara['Penyedia']['pagu'], 0, ',', '.') ?></td>
                            </tr>
                            <tr class="swakelola-row">
                                <td colspan="2">Total Swakelola</td>
                                <td><?= number_format($realisasi_cara['Swakelola']['paket'], 0, ',', '.') ?></td>
                                <td><?= number_format($realisasi_cara['Swakelola']['pagu'], 0, ',', '.') ?></td>
                            </tr>
                            <?php if ($pencatatan_total_paket > 0): ?>
                                <tr class="pencatatan-row">
                                    <td colspan="2">Total Pencatatan Nontender</td>
                                    <td><?= number_format($pencatatan_total_paket, 0, ',', '.') ?></td>
                                    <td><?= number_format($pencatatan_total_pagu, 0, ',', '.') ?></td>
                                </tr>
                            <?php endif; ?>
                            <tr class="total-row">
                                <td colspan="2">TOTAL KESELURUHAN</td>
                                <td><?= number_format($total_real_paket_dengan_pencatatan, 0, ',', '.') ?></td>
                                <td><?= number_format($total_real_pagu_dengan_pencatatan, 0, ',', '.') ?></td>
                            </tr>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-exclamation-triangle"></i><br>
                        Tidak ada data realisasi ditemukan
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- TABEL PENCATATAN NONTENDER (TERPISAH - BAWAH) -->
    <?php if ($pencatatan_total_paket > 0): ?>
        <div class="card" style="margin-top: 30px;">
            <div class="card-header card-header-red">
                <i class="fas fa-file-alt"></i> REALISASI PENCATATAN NONTENDER
            </div>
            <div class="table-container">
                <div class="table-controls">
                    <div style="font-size: 12px;">
                        <strong>Total: <?= number_format($pencatatan_total_paket, 0, ',', '.') ?> Paket</strong><br>
                        <strong>Rp <?= number_format($pencatatan_total_pagu, 0, ',', '.') ?></strong>
                    </div>
                </div>

                <table class="styled-table">
                    <thead>
                        <tr style="background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);">
                            <th>NO</th>
                            <th>METODE PENGADAAN</th>
                            <th>JUMLAH PAKET</th>
                            <th>PAGU (Rp)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; ?>
                        <?php foreach ($pencatatan_metode as $metode => $stats): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= htmlspecialchars($metode) ?></td>
                                <td><?= number_format($stats['paket'], 0, ',', '.') ?></td>
                                <td><?= number_format($stats['pagu'], 0, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>

                        <tr class="pencatatan-row">
                            <td colspan="2"><strong>TOTAL PENCATATAN</strong></td>
                            <td><strong><?= number_format($pencatatan_total_paket, 0, ',', '.') ?></strong></td>
                            <td><strong><?= number_format($pencatatan_total_pagu, 0, ',', '.') ?></strong></td>
                        </tr>
                    </tbody>
                </table>

                <div style="background: #fee2e2; padding: 15px; margin-top: 15px; border-radius: 10px; font-size: 13px; border-left: 4px solid #dc2626;">
                    <strong>ℹ️ Catatan:</strong> Data pencatatan ini merupakan realisasi pengadaan langsung yang dicatat pada sistem pencatatan nontender SPSE,
                    terpisah dari realisasi pengadaan langsung melalui sistem e-tendering biasa. Data ini sudah dimasukkan ke dalam total keseluruhan realisasi pengadaan di atas.
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../navbar/footer.php'; ?>