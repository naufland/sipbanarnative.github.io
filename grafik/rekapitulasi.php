<?php
// FILE: dashboard_rekapitulasi_bulan.php
// VERSI TERINTEGRASI: PERENCANAAN + REALISASI + PENCATATAN + GRAFIK LENGKAP

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

    // ==================== DATA UNTUK GRAFIK ====================
    
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

    // Data untuk Grafik 3: REKAP BERDASARKAN METODE PENGADAAN
    $grafik_metode = [];
    foreach ($rekap_metode as $metode => $stats) {
        if ($stats['pagu'] > 0) {
            $grafik_metode[$metode] = $stats['pagu'];
        }
    }

    // ==================== DATA EFISIENSI DARI TABEL REALISASI ====================
    
    $data_efisiensi_realisasi = [];

    $config_efisiensi = [
        'realisasi_tender' => [
            'metode' => 'Tender',
            'nilai_pagu_col' => 'Nilai_Pagu',
            'nilai_realisasi_col' => 'Nilai_Kontrak',
            'tahun_col' => 'Tahun_Anggaran',
            'is_swakelola' => false
        ],
        'realisasi_seleksi' => [
            'metode' => 'Seleksi',
            'nilai_pagu_col' => 'Nilai_Pagu',
            'nilai_realisasi_col' => 'Nilai_Kontrak',
            'tahun_col' => 'Tahun_Anggaran',
            'is_swakelola' => false
        ],
        'realisasi_nontender' => [
            'metode' => 'Pengadaan Langsung',
            'nilai_pagu_col' => 'Nilai_Pagu',
            'nilai_realisasi_col' => 'Nilai_Kontrak',
            'tahun_col' => 'Tahun_Anggaran',
            'is_swakelola' => false
        ],
        'realisasi_epurchasing' => [
            'metode' => 'E-Purchasing',
            'nilai_pagu_col' => 'Nilai_Pagu',
            'nilai_realisasi_col' => 'total_harga',
            'tahun_col' => 'tahun_anggaran',
            'is_swakelola' => false
        ],
        'realisasi_penunjukanlangsung' => [
            'metode' => 'Penunjukan Langsung',
            'nilai_pagu_col' => 'Nilai_Pagu',
            'nilai_realisasi_col' => 'Nilai_Kontrak',
            'tahun_col' => 'Tahun_Anggaran',
            'is_swakelola' => false,
            'force_cast' => true
        ],
        'realisasi_swakelola' => [
            'metode' => 'Swakelola',
            'nilai_pagu_col' => 'Nilai_Pagu',
            'nilai_realisasi_col' => 'Nilai_Total_Realisasi',
            'tahun_col' => 'Tahun_Anggaran',
            'is_swakelola' => true
        ],
        'pencatatan_nontender' => [
            'metode' => 'Pencatatan Non Tender',
            'nilai_pagu_col' => 'Nilai_Pagu',
            'nilai_realisasi_col' => 'Nilai_Total_Realisasi',
            'tahun_col' => 'Tahun_Anggaran',
            'is_swakelola' => false
        ]
    ];

    foreach ($config_efisiensi as $tabel => $config) {
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

            $pagu_col = null;
            $possible_pagu = [$config['nilai_pagu_col'], 'Nilai_Pagu', 'nilai_pagu', 'Pagu_Rp', 'pagu_rp'];
            foreach ($possible_pagu as $p) {
                if (in_array($p, $cols)) {
                    $pagu_col = $p;
                    break;
                }
            }

            $realisasi_col = null;
            $possible_realisasi = [
                $config['nilai_realisasi_col'],
                'Nilai_Kontrak',
                'nilai_kontrak',
                'Nilai_Total_Realisasi',
                'nilai_total_realisasi',
                'total_harga',
                'Total_Harga'
            ];
            foreach ($possible_realisasi as $r) {
                if (in_array($r, $cols)) {
                    $realisasi_col = $r;
                    break;
                }
            }

            if (!$tahun_col || !$pagu_col || !$realisasi_col) {
                continue;
            }

            if (isset($config['force_cast']) && $config['force_cast']) {
                $pagu_expression = "CAST(CAST($pagu_col as CHAR) as DECIMAL(20,2))";
                $realisasi_expression = "CAST(CAST($realisasi_col as CHAR) as DECIMAL(20,2))";
            } else {
                $pagu_expression = "CAST($pagu_col as DECIMAL(20,2))";
                $realisasi_expression = "CAST($realisasi_col as DECIMAL(20,2))";
            }

            $sql_efisiensi = "SELECT 
                COUNT(*) as jumlah_paket,
                COALESCE(SUM($pagu_expression), 0) as total_pagu,
                COALESCE(SUM($realisasi_expression), 0) as total_realisasi
                FROM $tabel 
                WHERE $pagu_col IS NOT NULL 
                AND $pagu_col != '' 
                AND $pagu_col != '0'
                AND $realisasi_col IS NOT NULL 
                AND $realisasi_col != '' 
                AND $realisasi_col != '0'
                AND (LOWER($bulan_col) = LOWER(:bulan_indo) OR LOWER($bulan_col) = LOWER(:bulan_eng))
                AND $tahun_col = :tahun";

            $stmt_efisiensi = $conn->prepare($sql_efisiensi);
            $stmt_efisiensi->bindParam(':bulan_indo', $bulan_indo);
            $stmt_efisiensi->bindParam(':bulan_eng', $bulan_eng);
            $stmt_efisiensi->bindParam(':tahun', $tahun_dipilih);
            $stmt_efisiensi->execute();

            $data = $stmt_efisiensi->fetch();

            if ($data && ($data['jumlah_paket'] > 0)) {
                $metode = $config['metode'];
                $total_pagu = (float)$data['total_pagu'];
                $total_realisasi = (float)$data['total_realisasi'];

                if (!isset($data_efisiensi_realisasi[$metode])) {
                    $data_efisiensi_realisasi[$metode] = [
                        'paket' => 0,
                        'pagu' => 0,
                        'realisasi' => 0
                    ];
                }

                $data_efisiensi_realisasi[$metode]['paket'] += (int)$data['jumlah_paket'];
                $data_efisiensi_realisasi[$metode]['pagu'] += $total_pagu;
                $data_efisiensi_realisasi[$metode]['realisasi'] += $total_realisasi;
            }
        } catch (Exception $e) {
            error_log("Error querying $tabel for efisiensi: " . $e->getMessage());
        }
    }

    foreach ($data_efisiensi_realisasi as $metode => &$data) {
        $pagu = $data['pagu'];
        $realisasi = $data['realisasi'];
        $selisih = $pagu - $realisasi;
        
        if ($pagu > 0) {
            $data['efisiensi'] = ($selisih / $pagu) * 100;
            $data['persentase_realisasi'] = ($realisasi / $pagu) * 100;
        } else {
            $data['efisiensi'] = 0;
            $data['persentase_realisasi'] = 0;
        }
        
        $data['selisih'] = $selisih;
    }
    unset($data);

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

$total_real_pagu_dengan_pencatatan = $total_real_pagu + $pencatatan_total_pagu;
$total_real_paket_dengan_pencatatan = $total_real_paket + $pencatatan_total_paket;

$total_efisiensi_pagu = 0;
$total_efisiensi_realisasi = 0;
$total_efisiensi_paket = 0;

foreach ($data_efisiensi_realisasi as $data) {
    $total_efisiensi_pagu += $data['pagu'];
    $total_efisiensi_realisasi += $data['realisasi'];
    $total_efisiensi_paket += $data['paket'];
}

$total_efisiensi_selisih = $total_efisiensi_pagu - $total_efisiensi_realisasi;

if ($total_efisiensi_pagu > 0) {
    $total_efisiensi_persen = ($total_efisiensi_selisih / $total_efisiensi_pagu) * 100;
    $total_efisiensi_persentase_realisasi = ($total_efisiensi_realisasi / $total_efisiensi_pagu) * 100;
} else {
    $total_efisiensi_persen = 0;
    $total_efisiensi_persentase_realisasi = 0;
}

$bulan_tampil = $bulan_indo;
$daftar_perubahan = ['', 'Perubahan', 'Tidak'];
$perubahan_label = $perubahan_ke === '' ? 'Semua Data' : ($perubahan_ke === 'Perubahan' ? 'Data Perubahan' : 'Data Tidak Perubahan');

$all_metode = array_unique(array_merge(
    array_keys($rekap_metode_display),
    array_keys($realisasi_metode)
));

$grafik_perbandingan_metode = [];
foreach ($metode_order as $metode) {
    if (in_array($metode, $all_metode)) {
        $grafik_perbandingan_metode[] = $metode;
    }
}

$grafik_perbandingan_perencanaan = [];
$grafik_perbandingan_realisasi = [];

foreach ($grafik_perbandingan_metode as $metode) {
    $grafik_perbandingan_perencanaan[] = isset($rekap_metode_display[$metode]) ? $rekap_metode_display[$metode]['pagu'] : 0;
    $grafik_perbandingan_realisasi[] = isset($realisasi_metode[$metode]) ? $realisasi_metode[$metode]['pagu'] : 0;
}

$page_title = "Dashboard Perencanaan & Realisasi - " . htmlspecialchars($bulan_tampil . ' ' . $tahun_dipilih);
include '../navbar/header.php';
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
        position: relative;
    }

    .chart-fullscreen-btn {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(255, 255, 255, 0.2);
        border: 2px solid rgba(255, 255, 255, 0.5);
        color: white;
        padding: 8px 12px;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 16px;
        z-index: 10;
    }

    .chart-fullscreen-btn:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: translateY(-50%) scale(1.1);
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

    .chart-fullscreen-modal {
        display: none;
        position: fixed;
        z-index: 9999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.95);
        animation: fadeIn 0.3s ease;
    }

    .chart-fullscreen-modal.active {
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .chart-fullscreen-content {
        background: white;
        border-radius: 20px;
        padding: 30px;
        width: 95%;
        height: 90%;
        max-width: 1800px;
        display: flex;
        flex-direction: column;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
        animation: slideIn 0.3s ease;
    }

    .chart-fullscreen-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 3px solid #dc3545;
    }

    .chart-fullscreen-title {
        font-size: 24px;
        font-weight: 700;
        color: #dc3545;
    }

    .chart-fullscreen-close {
        background: #dc3545;
        color: white;
        border: none;
        padding: 12px 20px;
        border-radius: 10px;
        cursor: pointer;
        font-size: 16px;
        font-weight: 600;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .chart-fullscreen-close:hover {
        background: #c82333;
        transform: scale(1.05);
    }

    .chart-fullscreen-body {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .chart-fullscreen-body canvas {
        max-width: 100%;
        max-height: 100%;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @keyframes slideIn {
        from { 
            opacity: 0;
            transform: scale(0.9) translateY(20px);
        }
        to { 
            opacity: 1;
            transform: scale(1) translateY(0);
        }
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

    <div class="comparison-section">
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
    <div style="margin-bottom: 20px; padding: 15px; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; border-radius: 12px; text-align: center; font-weight: bold; font-size: 16px;">
        <i class="fas fa-tasks"></i> GRAFIK PERENCANAAN PENGADAAN
    </div>
    <div class="charts-section">
        <div class="chart-card">
            <div class="chart-card-header">
                REKAP BERDASARKAN CARA PENGADAAN
                <button class="chart-fullscreen-btn" onclick="openFullscreen('chartCara', 'REKAP BERDASARKAN CARA PENGADAAN')">
                    <i class="fas fa-expand"></i>
                </button>
            </div>
            <div class="chart-container">
                <canvas id="chartCara" class="chart-canvas"></canvas>
            </div>
        </div>

        <div class="chart-card">
            <div class="chart-card-header">
                REKAP BERDASARKAN JENIS PENGADAAN
                <button class="chart-fullscreen-btn" onclick="openFullscreen('chartJenis', 'REKAP BERDASARKAN JENIS PENGADAAN')">
                    <i class="fas fa-expand"></i>
                </button>
            </div>
            <div class="chart-container">
                <canvas id="chartJenis" class="chart-canvas"></canvas>
            </div>
        </div>

        <div class="chart-card">
            <div class="chart-card-header">
                REKAP BERDASARKAN METODE PENGADAAN
                <button class="chart-fullscreen-btn" onclick="openFullscreen('chartMetode', 'REKAP BERDASARKAN METODE PENGADAAN')">
                    <i class="fas fa-expand"></i>
                </button>
            </div>
            <div class="chart-container">
                <canvas id="chartMetode" class="chart-canvas"></canvas>
            </div>
        </div>
    </div>

    <div style="margin: 30px 0 20px 0; padding: 15px; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; border-radius: 12px; text-align: center; font-weight: bold; font-size: 16px;">
        <i class="fas fa-check-circle"></i> GRAFIK REALISASI PENGADAAN
    </div>
    <div class="charts-section">
        <div class="chart-card">
            <div class="chart-card-header" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                REALISASI BERDASARKAN CARA PENGADAAN
                <button class="chart-fullscreen-btn" onclick="openFullscreen('chartRealisasiCara', 'REALISASI BERDASARKAN CARA PENGADAAN')">
                    <i class="fas fa-expand"></i>
                </button>
            </div>
            <div class="chart-container">
                <canvas id="chartRealisasiCara" class="chart-canvas"></canvas>
            </div>
        </div>

        <div class="chart-card">
            <div class="chart-card-header" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                REALISASI BERDASARKAN METODE PENGADAAN
                <button class="chart-fullscreen-btn" onclick="openFullscreen('chartRealisasiMetode', 'REALISASI BERDASARKAN METODE PENGADAAN')">
                    <i class="fas fa-expand"></i>
                </button>
            </div>
            <div class="chart-container">
                <canvas id="chartRealisasiMetode" class="chart-canvas"></canvas>
            </div>
        </div>

        </div>

    <div class="chart-card" style="margin-bottom: 30px;">
        <div class="chart-card-header">
            <i class="fas fa-chart-bar"></i> REKAP PERENCANAAN DAN REALISASI PBJ BERDASARKAN METODE PENGADAAN
            <button class="chart-fullscreen-btn" onclick="openFullscreen('chartPerbandingan', 'REKAP PERENCANAAN DAN REALISASI PBJ BERDASARKAN METODE PENGADAAN')">
                <i class="fas fa-expand"></i>
            </button>
        </div>
        <div class="chart-container" style="padding: 30px;">
            <canvas id="chartPerbandingan" style="height: 400px; max-height: 400px;"></canvas>
        </div>
    </div>

    <div class="chart-card" style="margin-bottom: 30px;">
        <div class="chart-card-header" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
            PERBANDINGAN PERENCANAAN DAN REALISASI SWAKELOLA
            <button class="chart-fullscreen-btn" onclick="openFullscreen('chartSwakelola', 'PERBANDINGAN PERENCANAAN DAN REALISASI SWAKELOLA')">
                <i class="fas fa-expand"></i>
            </button>
        </div>
        <div class="chart-container">
            <canvas id="chartSwakelola" class="chart-canvas"></canvas>
        </div>
    </div>
    <div id="chartFullscreenModal" class="chart-fullscreen-modal">
        <div class="chart-fullscreen-content">
            <div class="chart-fullscreen-header">
                <div class="chart-fullscreen-title" id="fullscreenChartTitle"></div>
                <button class="chart-fullscreen-close" onclick="closeFullscreen()">
                    <i class="fas fa-times"></i> Tutup
                </button>
            </div>
            <div class="chart-fullscreen-body">
                <canvas id="fullscreenCanvas"></canvas>
            </div>
        </div>
    </div>

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

    <?php if (!empty($data_efisiensi_realisasi)): ?>
        <div class="card" style="margin-top: 30px;">
            <div class="card-header" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                <i class="fas fa-chart-line"></i> ANALISIS EFISIENSI ANGGARAN BERDASARKAN DATA REALISASI
            </div>
            <div class="table-container">
                <div class="table-controls">
                    <div style="font-size: 12px;">
                        <strong>Total Paket: <?= number_format($total_efisiensi_paket, 0, ',', '.') ?> Paket</strong><br>
                        <strong>Efisiensi Keseluruhan: <?= number_format($total_efisiensi_persen, 2, ',', '.') ?>%</strong><br>
                        <strong>Penghematan: Rp <?= number_format($total_efisiensi_selisih, 0, ',', '.') ?></strong>
                    </div>
                </div>

                <table class="styled-table">
                    <thead>
                        <tr style="background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);">
                            <th rowspan="2" style="vertical-align: middle;">NO</th>
                            <th rowspan="2" style="vertical-align: middle;">METODE PENGADAAN</th>
                            <th rowspan="2" style="vertical-align: middle;">PAKET</th>
                            <th colspan="2" style="text-align: center;">NILAI (Rp)</th>
                            <th rowspan="2" style="vertical-align: middle;">SELISIH (Rp)</th>
                            <th rowspan="2" style="vertical-align: middle;">% REALISASI</th>
                            <th rowspan="2" style="vertical-align: middle;">EFISIENSI (%)</th>
                            <th rowspan="2" style="vertical-align: middle;">STATUS</th>
                        </tr>
                        <tr style="background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);">
                            <th>PAGU</th>
                            <th>REALISASI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; ?>
                        <?php foreach ($data_efisiensi_realisasi as $metode => $data): ?>
                            <?php
                                $status = '';
                                $status_color = '';
                                $status_icon = '';
                                
                                if ($data['efisiensi'] > 0) {
                                    $status = 'EFISIEN';
                                    $status_color = '#059669';
                                    $status_icon = '✓';
                                } elseif ($data['efisiensi'] < 0) {
                                    $status = 'OVERBUDGET';
                                    $status_color = '#dc2626';
                                    $status_icon = '✗';
                                } else {
                                    $status = 'SESUAI';
                                    $status_color = '#3b82f6';
                                    $status_icon = '=';
                                }
                                
                                $row_bg = '';
                                if ($data['efisiensi'] > 10) {
                                    $row_bg = 'background-color: #ecfdf5;';
                                } elseif ($data['efisiensi'] < 0) {
                                    $row_bg = 'background-color: #fef2f2;';
                                }
                            ?>
                            <tr style="<?= $row_bg ?>">
                                <td><?= $no++ ?></td>
                                <td style="text-align: left; font-weight: 600;"><?= htmlspecialchars($metode) ?></td>
                                <td><?= number_format($data['paket'], 0, ',', '.') ?></td>
                                <td><?= number_format($data['pagu'], 0, ',', '.') ?></td>
                                <td><?= number_format($data['realisasi'], 0, ',', '.') ?></td>
                                <td style="color: <?= $data['selisih'] >= 0 ? '#059669' : '#dc2626' ?>; font-weight: bold;">
                                    <?= number_format($data['selisih'], 0, ',', '.') ?>
                                </td>
                                <td style="font-weight: bold;">
                                    <?= number_format($data['persentase_realisasi'], 2, ',', '.') ?>%
                                </td>
                                <td style="color: <?= $status_color ?>; font-weight: bold; font-size: 14px;">
                                    <?= number_format($data['efisiensi'], 2, ',', '.') ?>%
                                </td>
                                <td style="text-align: center;">
                                    <span style="background: <?= $status_color ?>; color: white; padding: 5px 12px; border-radius: 20px; font-weight: bold; font-size: 11px; display: inline-block;">
                                        <?= $status_icon ?> <?= $status ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <tr style="background-color: #d1fae5; border-top: 3px solid #059669;">
                            <td colspan="2" style="font-weight: bold; font-size: 14px;">TOTAL KESELURUHAN</td>
                            <td style="font-weight: bold;"><?= number_format($total_efisiensi_paket, 0, ',', '.') ?></td>
                            <td style="font-weight: bold;"><?= number_format($total_efisiensi_pagu, 0, ',', '.') ?></td>
                            <td style="font-weight: bold;"><?= number_format($total_efisiensi_realisasi, 0, ',', '.') ?></td>
                            <td style="color: <?= $total_efisiensi_selisih >= 0 ? '#059669' : '#dc2626' ?>; font-weight: bold;">
                                <?= number_format($total_efisiensi_selisih, 0, ',', '.') ?>
                            </td>
                            <td style="font-weight: bold;">
                                <?= number_format($total_efisiensi_persentase_realisasi, 2, ',', '.') ?>%
                            </td>
                            <td style="color: <?= $total_efisiensi_persen >= 0 ? '#059669' : '#dc2626' ?>; font-weight: bold; font-size: 14px;">
                                <?= number_format($total_efisiensi_persen, 2, ',', '.') ?>%
                            </td>
                            <td style="text-align: center;">
                                <span style="background: <?= $total_efisiensi_persen >= 0 ? '#059669' : '#dc2626' ?>; color: white; padding: 5px 12px; border-radius: 20px; font-weight: bold; font-size: 11px; display: inline-block;">
                                    <?= $total_efisiensi_persen >= 0 ? '✓ EFISIEN' : '✗ OVERBUDGET' ?>
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div style="background: #ecfdf5; padding: 20px; margin-top: 20px; border-radius: 12px; border-left: 5px solid #059669;">
                    <div style="font-size: 14px; margin-bottom: 15px;">
                        <strong>📊 KETERANGAN ANALISIS EFISIENSI:</strong>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px; font-size: 13px;">
                        <div>
                            <strong style="color: #059669;">✓ EFISIEN:</strong> Realisasi lebih rendah dari pagu (ada penghematan anggaran)
                        </div>
                        <div>
                            <strong style="color: #3b82f6;">= SESUAI:</strong> Realisasi sama dengan pagu (100%)
                        </div>
                        <div>
                            <strong style="color: #dc2626;">✗ OVERBUDGET:</strong> Realisasi melebihi pagu (perlu perhatian khusus)
                        </div>
                    </div>
                    <div style="margin-top: 15px; padding-top: 15px; border-top: 2px solid #a7f3d0; font-size: 13px;">
                        <strong>📈 Rumus Perhitungan:</strong><br>
                        • <strong>Efisiensi (%)</strong> = ((Pagu - Realisasi) / Pagu) × 100%<br>
                        • <strong>% Realisasi</strong> = (Realisasi / Pagu) × 100%<br>
                        • <strong>Selisih</strong> = Pagu - Realisasi<br><br>
                        <strong style="color: #059669;">ℹ️ Catatan:</strong> Data diambil dari tabel realisasi yang memiliki kolom Nilai Pagu dan Nilai Realisasi/Kontrak
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// GRAFIK 1: REKAP BERDASARKAN CARA PENGADAAN
const dataCara = {
    labels: <?php echo json_encode(array_keys($grafik_cara)); ?>,
    datasets: [{
        label: 'Pagu (Rp)',
        data: <?php echo json_encode(array_values($grafik_cara)); ?>,
        backgroundColor: [
            'rgba(220, 53, 69, 0.8)',
            'rgba(13, 110, 253, 0.8)'
        ],
        borderColor: [
            'rgb(220, 53, 69)',
            'rgb(13, 110, 253)'
        ],
        borderWidth: 2
    }]
};

const configCara = {
    type: 'doughnut',
    data: dataCara,
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    font: {
                        size: 12,
                        weight: 'bold'
                    },
                    padding: 15
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.label || '';
                        let value = context.parsed || 0;
                        let total = context.dataset.data.reduce((a, b) => a + b, 0);
                        let percentage = ((value / total) * 100).toFixed(2);
                        
                        return label + ': Rp ' + value.toLocaleString('id-ID') + ' (' + percentage + '%)';
                    }
                }
            }
        }
    }
};

const chartCara = new Chart(
    document.getElementById('chartCara'),
    configCara
);

// GRAFIK 2: REKAP BERDASARKAN JENIS PENGADAAN
const dataJenis = {
    labels: <?php echo json_encode(array_keys($grafik_jenis)); ?>,
    datasets: [{
        label: 'Pagu (Rp)',
        data: <?php echo json_encode(array_values($grafik_jenis)); ?>,
        backgroundColor: [
            'rgba(220, 53, 69, 0.8)',
            'rgba(255, 193, 7, 0.8)',
            'rgba(13, 110, 253, 0.8)',
            'rgba(25, 135, 84, 0.8)',
            'rgba(108, 117, 125, 0.8)'
        ],
        borderColor: [
            'rgb(220, 53, 69)',
            'rgb(255, 193, 7)',
            'rgb(13, 110, 253)',
            'rgb(25, 135, 84)',
            'rgb(108, 117, 125)'
        ],
        borderWidth: 2
    }]
};

const configJenis = {
    type: 'pie',
    data: dataJenis,
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    font: {
                        size: 11,
                        weight: 'bold'
                    },
                    padding: 12
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.label || '';
                        let value = context.parsed || 0;
                        let total = context.dataset.data.reduce((a, b) => a + b, 0);
                        let percentage = ((value / total) * 100).toFixed(2);
                        
                        return label + ': Rp ' + value.toLocaleString('id-ID') + ' (' + percentage + '%)';
                    }
                }
            }
        }
    }
};

const chartJenis = new Chart(
    document.getElementById('chartJenis'),
    configJenis
);

// GRAFIK 3: REKAP BERDASARKAN METODE PENGADAAN
const dataMetode = {
    labels: <?php echo json_encode(array_keys($grafik_metode)); ?>,
    datasets: [{
        label: 'Pagu (Rp)',
        data: <?php echo json_encode(array_values($grafik_metode)); ?>,
        backgroundColor: 'rgba(220, 53, 69, 0.8)',
        borderColor: 'rgb(220, 53, 69)',
        borderWidth: 2
    }]
};

const configMetode = {
    type: 'bar',
    data: dataMetode,
    options: {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let value = context.parsed.x || 0;
                        return 'Pagu: Rp ' + value.toLocaleString('id-ID');
                    }
                }
            }
        },
        scales: {
            x: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        if (value >= 1000000000) {
                            return 'Rp ' + (value / 1000000000).toFixed(1) + 'M';
                        } else if (value >= 1000000) {
                            return 'Rp ' + (value / 1000000).toFixed(1) + 'Jt';
                        }
                        return 'Rp ' + value.toLocaleString('id-ID');
                    },
                    font: {
                        size: 10
                    }
                },
                grid: {
                    display: true,
                    drawBorder: false
                }
            },
            y: {
                ticks: {
                    font: {
                        size: 11,
                        weight: 'bold'
                    }
                },
                grid: {
                    display: false
                }
            }
        }
    }
};

const chartMetode = new Chart(
    document.getElementById('chartMetode'),
    configMetode
);

// GRAFIK 4: REALISASI BERDASARKAN CARA PENGADAAN
const dataRealisasiCara = {
    labels: ['Penyedia', 'Swakelola'],
    datasets: [{
        label: 'Nilai Realisasi (Rp)',
        data: [
            <?php echo $realisasi_cara['Penyedia']['pagu']; ?>,
            <?php echo $realisasi_cara['Swakelola']['pagu']; ?>
        ],
        backgroundColor: [
            'rgba(239, 68, 68, 0.8)',
            'rgba(59, 130, 246, 0.8)'
        ],
        borderColor: [
            'rgb(239, 68, 68)',
            'rgb(59, 130, 246)'
        ],
        borderWidth: 2
    }]
};

const configRealisasiCara = {
    type: 'doughnut',
    data: dataRealisasiCara,
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    font: {
                        size: 12,
                        weight: 'bold'
                    },
                    padding: 15
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.label || '';
                        let value = context.parsed || 0;
                        let total = context.dataset.data.reduce((a, b) => a + b, 0);
                        let percentage = ((value / total) * 100).toFixed(2);
                        
                        return label + ': Rp ' + value.toLocaleString('id-ID') + ' (' + percentage + '%)';
                    }
                }
            }
        }
    }
};

const chartRealisasiCara = new Chart(
    document.getElementById('chartRealisasiCara'),
    configRealisasiCara
);

// GRAFIK 5: REALISASI BERDASARKAN METODE PENGADAAN
const dataRealisasiMetode = {
    labels: <?php 
        $realisasi_metode_labels = array_keys($realisasi_metode);
        echo json_encode($realisasi_metode_labels); 
    ?>,
    datasets: [{
        label: 'Nilai Realisasi (Rp)',
        data: <?php 
            $realisasi_metode_values = array_map(function($item) {
                return $item['pagu'];
            }, array_values($realisasi_metode));
            echo json_encode($realisasi_metode_values); 
        ?>,
        backgroundColor: 'rgba(239, 68, 68, 0.8)',
        borderColor: 'rgb(239, 68, 68)',
        borderWidth: 2
    }]
};

const configRealisasiMetode = {
    type: 'bar',
    data: dataRealisasiMetode,
    options: {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let value = context.parsed.x || 0;
                        return 'Realisasi: Rp ' + value.toLocaleString('id-ID');
                    }
                }
            }
        },
        scales: {
            x: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        if (value >= 1000000000) {
                            return 'Rp ' + (value / 1000000000).toFixed(1) + 'M';
                        } else if (value >= 1000000) {
                            return 'Rp ' + (value / 1000000).toFixed(1) + 'Jt';
                        }
                        return 'Rp ' + value.toLocaleString('id-ID');
                    },
                    font: {
                        size: 10
                    }
                },
                grid: {
                    display: true,
                    drawBorder: false
                }
            },
            y: {
                ticks: {
                    font: {
                        size: 11,
                        weight: 'bold'
                    }
                },
                grid: {
                    display: false
                }
            }
        }
    }
};

const chartRealisasiMetode = new Chart(
    document.getElementById('chartRealisasiMetode'),
    configRealisasiMetode
);

// GRAFIK 6: PERBANDINGAN PERENCANAAN DAN REALISASI SWAKELOLA
const dataSwakelola = {
    labels: ['Perencanaan', 'Realisasi'],
    datasets: [{
        label: 'Nilai Swakelola (Rp)',
        data: [
            <?php echo $rekap_cara['Swakelola']['pagu']; ?>,
            <?php echo $realisasi_cara['Swakelola']['pagu']; ?>
        ],
        backgroundColor: [
            'rgba(59, 130, 246, 0.8)',
            'rgba(239, 68, 68, 0.8)'
        ],
        borderColor: [
            'rgb(59, 130, 246)',
            'rgb(239, 68, 68)'
        ],
        borderWidth: 2
    }]
};

const configSwakelola = {
    type: 'pie',
    data: dataSwakelola,
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    font: {
                        size: 12,
                        weight: 'bold'
                    },
                    padding: 15,
                    generateLabels: function(chart) {
                        const data = chart.data;
                        const total = data.datasets[0].data.reduce((a, b) => a + b, 0);
                        
                        return data.labels.map((label, i) => {
                            const value = data.datasets[0].data[i];
                            const percentage = total > 0 ? ((value / total) * 100).toFixed(2) : '0.00';
                            
                            return {
                                text: label + '\nRp ' + value.toLocaleString('id-ID') + '\n' + percentage + '%',
                                fillStyle: data.datasets[0].backgroundColor[i],
                                strokeStyle: data.datasets[0].borderColor[i],
                                lineWidth: 2,
                                hidden: false,
                                index: i
                            };
                        });
                    }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.label || '';
                        let value = context.parsed || 0;
                        let total = context.dataset.data.reduce((a, b) => a + b, 0);
                        let percentage = total > 0 ? ((value / total) * 100).toFixed(2) : '0.00';
                        
                        return [
                            label + ': Rp ' + value.toLocaleString('id-ID'),
                            'Persentase: ' + percentage + '%'
                        ];
                    }
                },
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                titleFont: {
                    size: 14,
                    weight: 'bold'
                },
                bodyFont: {
                    size: 13
                },
                padding: 12
            }
        }
    }
};

const chartSwakelola = new Chart(
    document.getElementById('chartSwakelola'),
    configSwakelola
);

// GRAFIK 7: PERBANDINGAN PERENCANAAN VS REALISASI
const dataPerbandingan = {
    labels: <?php echo json_encode($grafik_perbandingan_metode); ?>,
    datasets: [
        {
            label: 'Nilai Perencanaan',
            data: <?php echo json_encode($grafik_perbandingan_perencanaan); ?>,
            backgroundColor: 'rgba(59, 130, 246, 0.8)',
            borderColor: 'rgb(59, 130, 246)',
            borderWidth: 2
        },
        {
            label: 'Nilai Realisasi',
            data: <?php echo json_encode($grafik_perbandingan_realisasi); ?>,
            backgroundColor: 'rgba(239, 68, 68, 0.8)',
            borderColor: 'rgb(239, 68, 68)',
            borderWidth: 2
        }
    ]
};

const configPerbandingan = {
    type: 'bar',
    data: dataPerbandingan,
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
                labels: {
                    font: {
                        size: 13,
                        weight: 'bold'
                    },
                    padding: 20,
                    usePointStyle: true,
                    pointStyle: 'rectRounded'
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        let value = context.parsed.y || 0;
                        return label + ': Rp ' + value.toLocaleString('id-ID');
                    },
                    afterLabel: function(context) {
                        if (context.datasetIndex === 1) {
                            let perencanaan = context.chart.data.datasets[0].data[context.dataIndex];
                            let realisasi = context.parsed.y;
                            if (perencanaan > 0) {
                                let percentage = ((realisasi / perencanaan) * 100).toFixed(2);
                                return 'Capaian: ' + percentage + '%';
                            }
                        }
                        return '';
                    }
                },
                padding: 12,
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                titleFont: {
                    size: 13,
                    weight: 'bold'
                },
                bodyFont: {
                    size: 12
                }
            }
        },
        scales: {
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    font: {
                        size: 11,
                        weight: 'bold'
                    },
                    maxRotation: 45,
                    minRotation: 45
                }
            },
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        if (value >= 1000000000) {
                            return 'Rp ' + (value / 1000000000).toFixed(1) + 'M';
                        } else if (value >= 1000000) {
                            return 'Rp ' + (value / 1000000).toFixed(0) + 'Jt';
                        }
                        return 'Rp ' + value.toLocaleString('id-ID');
                    },
                    font: {
                        size: 10
                    }
                },
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)'
                }
            }
        }
    }
};

const chartPerbandingan = new Chart(
    document.getElementById('chartPerbandingan'),
    configPerbandingan
);

// FUNGSI FULLSCREEN UNTUK SEMUA GRAFIK
let fullscreenChart = null;
const chartInstances = {
    'chartCara': chartCara,
    'chartJenis': chartJenis,
    'chartMetode': chartMetode,
    'chartRealisasiCara': chartRealisasiCara,
    'chartRealisasiMetode': chartRealisasiMetode,
    'chartSwakelola': chartSwakelola, // Pastikan chartSwakelola ditambahkan di sini
    'chartPerbandingan': chartPerbandingan
};

function openFullscreen(chartId, title) {
    const modal = document.getElementById('chartFullscreenModal');
    const titleElement = document.getElementById('fullscreenChartTitle');
    const canvas = document.getElementById('fullscreenCanvas');
    
    titleElement.textContent = title;
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
    
    // Perbaikan: Ganti 'chartEfisiensi' dengan 'chartSwakelola' di list chartInstances
    // Cek chartInstances di atas, 'chartEfisiensi' tidak ada, tapi 'chartSwakelola' ada
    const originalChart = chartInstances[chartId]; 
    
    if (!originalChart) {
        console.error("Chart instance not found: ", chartId);
        closeFullscreen();
        return;
    }
    
    if (fullscreenChart) {
        fullscreenChart.destroy();
    }
    
    const chartType = originalChart.config.type;
    const chartData = originalChart.data;
    
    let fullscreenConfig = {
        type: chartType,
        data: chartData,
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: originalChart.config.options.plugins?.legend?.position || 'top',
                    labels: {
                        font: {
                            size: 16,
                            weight: 'bold'
                        },
                        padding: 20,
                        // Tambahkan generateLabels jika ada (untuk pie/doughnut)
                        generateLabels: originalChart.config.options.plugins?.legend?.labels?.generateLabels
                    }
                },
                tooltip: {
                    callbacks: originalChart.config.options.plugins?.tooltip?.callbacks || {},
                    titleFont: { size: 16, weight: 'bold' },
                    bodyFont: { size: 14 },
                    padding: 15,
                    backgroundColor: 'rgba(0, 0, 0, 0.8)'
                }
            }
        }
    };

    if (chartType === 'bar') {
        fullscreenConfig.options.indexAxis = originalChart.config.options.indexAxis || 'x';
        fullscreenConfig.options.scales = {
            x: {
                beginAtZero: true,
                grid: originalChart.config.options.scales?.x?.grid || {},
                ticks: {
                    callback: originalChart.config.options.scales?.x?.ticks?.callback || function(value) { return value; },
                    font: {
                        size: 14,
                        weight: 'bold'
                    },
                    maxRotation: originalChart.config.options.scales?.x?.ticks?.maxRotation || 0,
                    minRotation: originalChart.config.options.scales?.x?.ticks?.minRotation || 0
                }
            },
            y: {
                beginAtZero: true,
                grid: originalChart.config.options.scales?.y?.grid || {},
                ticks: {
                    callback: originalChart.config.options.scales?.y?.ticks?.callback || function(value) { return value; },
                    font: {
                        size: 14
                    }
                }
            }
        };
    }
    
    setTimeout(() => {
        fullscreenChart = new Chart(canvas, fullscreenConfig);
    }, 150);
}

function closeFullscreen() {
    const modal = document.getElementById('chartFullscreenModal');
    modal.classList.remove('active');
    document.body.style.overflow = 'auto';
    
    if (fullscreenChart) {
        fullscreenChart.destroy();
        fullscreenChart = null;
    }
}

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeFullscreen();
    }
});

document.getElementById('chartFullscreenModal').addEventListener('click', function(event) {
    if (event.target === this) {
        closeFullscreen();
    }
});
</script>

<?php include '../navbar/footer.php'; ?>