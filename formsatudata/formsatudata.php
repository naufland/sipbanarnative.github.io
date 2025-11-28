<?php
// FILE: analisis_efisiensi.php
// DESKRIPSI: Halaman khusus analisis efisiensi anggaran (Pagu vs Realisasi)

require_once '../config/database.php';

// 1. INISIALISASI VARIABEL & FILTER
$bulan_dipilih = $_GET['bulan'] ?? 'Desember';
$tahun_dipilih = $_GET['tahun'] ?? date('Y');

$bulan_mapping = [
    'Januari' => 'January', 'Februari' => 'February', 'Maret' => 'March',
    'April' => 'April', 'Mei' => 'May', 'Juni' => 'June',
    'Juli' => 'July', 'Agustus' => 'August', 'September' => 'September',
    'Oktober' => 'October', 'November' => 'November', 'Desember' => 'December'  
];
$daftar_bulan = array_keys($bulan_mapping);

// 2. LOGIKA PENGAMBILAN DATA
$data_efisiensi_realisasi = [];
$total_efisiensi_pagu = 0;
$total_efisiensi_realisasi = 0;
$total_efisiensi_selisih = 0;
$total_efisiensi_persen = 0;
$total_efisiensi_persentase_realisasi = 0;

try {
    $database = new Database();
    $conn = $database->getConnection();

    $bulan_indo = $bulan_dipilih;
    $bulan_eng = $bulan_mapping[$bulan_indo] ?? $bulan_dipilih;

    $config_efisiensi = [
        'realisasi_tender' => ['metode' => 'Tender', 'pagu_col' => 'Nilai_Pagu', 'real_col' => 'Nilai_Kontrak', 'tahun_col' => 'Tahun_Anggaran'],
        'realisasi_seleksi' => ['metode' => 'Seleksi', 'pagu_col' => 'Nilai_Pagu', 'real_col' => 'Nilai_Kontrak', 'tahun_col' => 'Tahun_Anggaran'],
        'realisasi_nontender' => ['metode' => 'Pengadaan Langsung', 'pagu_col' => 'Nilai_Pagu', 'real_col' => 'Nilai_Kontrak', 'tahun_col' => 'Tahun_Anggaran'],
        'realisasi_epurchasing' => ['metode' => 'E-Purchasing', 'pagu_col' => 'Nilai_Pagu', 'real_col' => 'total_harga', 'tahun_col' => 'tahun_anggaran'],
        'realisasi_penunjukanlangsung' => ['metode' => 'Penunjukan Langsung', 'pagu_col' => 'Nilai_Pagu', 'real_col' => 'Nilai_Kontrak', 'tahun_col' => 'Tahun_Anggaran', 'force_cast' => true],
        'realisasi_swakelola' => ['metode' => 'Swakelola', 'pagu_col' => 'Nilai_Pagu', 'real_col' => 'Nilai_Total_Realisasi', 'tahun_col' => 'Tahun_Anggaran'],
        'pencatatan_nontender' => ['metode' => 'Pencatatan Non Tender', 'pagu_col' => 'Nilai_Pagu', 'real_col' => 'Nilai_Total_Realisasi', 'tahun_col' => 'Tahun_Anggaran']
    ];

    foreach ($config_efisiensi as $tabel => $conf) {
        try {
            $check = $conn->prepare("SHOW TABLES LIKE '$tabel'");
            $check->execute();
            if ($check->rowCount() === 0) continue;

            $cols = $conn->query("SHOW COLUMNS FROM $tabel")->fetchAll(PDO::FETCH_COLUMN);
            $bulan_col = in_array('bulan', $cols) ? 'bulan' : (in_array('Bulan', $cols) ? 'Bulan' : null);
            
            $pagu_col_name = null;
            $possible_pagu = [$conf['pagu_col'], 'Nilai_Pagu', 'Pagu_Rp'];
            foreach($possible_pagu as $p) { if(in_array($p, $cols)) { $pagu_col_name = $p; break; } }

            $real_col_name = null;
            $possible_real = [$conf['real_col'], 'Nilai_Kontrak', 'Nilai_Total_Realisasi', 'total_harga'];
            foreach($possible_real as $r) { if(in_array($r, $cols)) { $real_col_name = $r; break; } }

            $tahun_col_name = null;
            $possible_tahun = [$conf['tahun_col'], 'Tahun_Anggaran', 'tahun_anggaran', 'Tahun'];
            foreach($possible_tahun as $t) { if(in_array($t, $cols)) { $tahun_col_name = $t; break; } }

            if (!$bulan_col || !$pagu_col_name || !$real_col_name || !$tahun_col_name) continue;

            $cast_pagu = isset($conf['force_cast']) 
                ? "CAST(CAST($pagu_col_name as CHAR) as DECIMAL(20,2))" 
                : "CAST($pagu_col_name as DECIMAL(20,2))";
            $cast_real = isset($conf['force_cast']) 
                ? "CAST(CAST($real_col_name as CHAR) as DECIMAL(20,2))" 
                : "CAST($real_col_name as DECIMAL(20,2))";

            $sql = "SELECT 
                    COUNT(*) as jumlah_paket,
                    COALESCE(SUM($cast_pagu), 0) as total_pagu,
                    COALESCE(SUM($cast_real), 0) as total_realisasi
                    FROM $tabel 
                    WHERE $pagu_col_name > 0 AND $real_col_name > 0
                    AND (LOWER($bulan_col) = LOWER(:bln_indo) OR LOWER($bulan_col) = LOWER(:bln_eng))
                    AND $tahun_col_name = :tahun";

            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':bln_indo', $bulan_indo);
            $stmt->bindParam(':bln_eng', $bulan_eng);
            $stmt->bindParam(':tahun', $tahun_dipilih);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row && $row['jumlah_paket'] > 0) {
                $metode = $conf['metode'];
                if (!isset($data_efisiensi_realisasi[$metode])) {
                    $data_efisiensi_realisasi[$metode] = ['paket'=>0, 'pagu'=>0, 'realisasi'=>0];
                }
                $data_efisiensi_realisasi[$metode]['paket'] += $row['jumlah_paket'];
                $data_efisiensi_realisasi[$metode]['pagu'] += $row['total_pagu'];
                $data_efisiensi_realisasi[$metode]['realisasi'] += $row['total_realisasi'];
            }
        } catch (Exception $ex) {}
    }

    foreach ($data_efisiensi_realisasi as &$d) {
        $d['selisih'] = $d['pagu'] - $d['realisasi'];
        $d['efisiensi'] = ($d['pagu'] > 0) ? ($d['selisih'] / $d['pagu']) * 100 : 0;
        $d['persentase_realisasi'] = ($d['pagu'] > 0) ? ($d['realisasi'] / $d['pagu']) * 100 : 0;
        $total_efisiensi_pagu += $d['pagu'];
        $total_efisiensi_realisasi += $d['realisasi'];
    }
    unset($d);

    $total_efisiensi_selisih = $total_efisiensi_pagu - $total_efisiensi_realisasi;
    if ($total_efisiensi_pagu > 0) {
        $total_efisiensi_persen = ($total_efisiensi_selisih / $total_efisiensi_pagu) * 100;
        $total_efisiensi_persentase_realisasi = ($total_efisiensi_realisasi / $total_efisiensi_pagu) * 100;
    }

} catch (Exception $e) {
    $error_msg = $e->getMessage();
}

$page_title = "Analisis Efisiensi Anggaran – " . $bulan_dipilih . ' ' . $tahun_dipilih;
include '../navbar/header.php';
?>

<!-- Google Fonts: Inter (clean, modern sans-serif) -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<script src="../../js/submenu.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<!-- Tambahkan Select2 CSS dan JS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<style>
:root {
    --text-primary: #1f2937;
    --text-secondary: #6b7280;
    --border-color: #e5e7eb;
    --bg-card: #ffffff;
    --success: #10b981;
    --success-light: #ecfdf5;
    --success-dark: #059669;
    --danger: #ef4444;
    --danger-light: #fee2e2;
    --neutral-light: #f9fafb;
    --font-sans: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
}

body {
    font-family: var(--font-sans);
    background-color: #f8fafc;
    color: var(--text-primary);
}

.container {
    max-width: 1200px;
}

.card-minimal {
    background: var(--bg-card);
    border-radius: 14px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.03);
    overflow: hidden;
    margin-top: 2rem;
}

.header-section {
    background: linear-gradient(135deg, var(--success-dark), var(--success));
    color: white;
    padding: 24px 32px;
}

.header-section h1 {
    font-weight: 600;
    font-size: 1.5rem;
    margin: 0;
}

.header-section .subtitle {
    font-weight: 400;
    opacity: 0.85;
    margin-top: 4px;
}

.stats-row {
    display: flex;
    gap: 16px;
    margin-top: 16px;
    flex-wrap: wrap;
}

.stat-item {
    background: rgba(255,255,255,0.12);
    padding: 14px 20px;
    border-radius: 10px;
    min-width: 180px;
    text-align: center;
    flex: 1;
}

.stat-label {
    font-size: 0.85rem;
    opacity: 0.9;
    margin-bottom: 4px;
}

.stat-value {
    font-size: 1.25rem;
    font-weight: 600;
}

.filter-bar {
    display: flex;
    gap: 16px;
    margin-bottom: 24px;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
}

.filter-bar h2 {
    font-weight: 600;
    font-size: 1.4rem;
    margin: 0;
    flex: 1;
    min-width: 250px;
}

.filter-group {
    display: flex;
    gap: 8px;
    align-items: center;
    background: white;
    padding: 8px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

.filter-group label {
    font-size: 0.85rem;
    font-weight: 500;
    color: var(--text-secondary);
    margin: 0 4px 0 0;
    white-space: nowrap;
}

.filter-group .form-select {
    width: auto;
    min-width: 140px;
    border-radius: 8px;
    border: 1px solid var(--border-color);
    padding: 8px 32px 8px 14px;
    font-size: 0.9rem;
    background-color: #fafbfc;
    transition: all 0.2s ease;
}

.filter-group .form-select:focus {
    border-color: var(--success);
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
    background-color: white;
}

.btn-filter {
    border-radius: 8px;
    padding: 8px 20px;
    font-size: 0.9rem;
    font-weight: 500;
    background: linear-gradient(135deg, var(--success-dark), var(--success));
    border: none;
    color: white;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 6px;
}

.btn-filter:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.btn-reset {
    border-radius: 8px;
    padding: 8px 16px;
    font-size: 0.9rem;
    font-weight: 500;
    background: white;
    border: 1px solid var(--border-color);
    color: var(--text-secondary);
    transition: all 0.2s ease;
}

.btn-reset:hover {
    background: #f9fafb;
    border-color: var(--text-secondary);
    color: var(--text-primary);
}

.table-minimal {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.95rem;
}

.table-minimal th,
.table-minimal td {
    padding: 14px 16px;
    text-align: left;
    border-bottom: 1px solid var(--border-color);
}

.table-minimal th {
    font-weight: 600;
    color: var(--text-secondary);
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    background-color: var(--neutral-light);
}

.table-minimal td {
    vertical-align: middle;
}

.table-minimal tbody tr:last-child td {
    border-bottom: none;
}

.text-money {
    font-family: 'Consolas', monospace;
    font-weight: 600;
    letter-spacing: -0.5px;
}

.progress-bar {
    height: 6px;
    background-color: #e5e7eb;
    border-radius: 4px;
    overflow: hidden;
    margin-top: 4px;
}

.progress-fill {
    height: 100%;
    border-radius: 4px;
}

.badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.badge-hemat {
    background-color: #d1fae5;
    color: #065f46;
}

.badge-over {
    background-color: #fee2e2;
    color: #991b1b;
}

.badge-pas {
    background-color: #f3f4f6;
    color: #374151;
}

.total-row {
    background-color: var(--success-light);
    font-weight: 600;
    border-top: 2px solid var(--success);
}

.total-row .text-money {
    color: var(--success-dark);
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: var(--text-secondary);
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.footer-note {
    padding: 16px 32px;
    background: #fafbfc;
    font-size: 0.85rem;
    color: var(--text-secondary);
    border-top: 1px solid var(--border-color);
}

@media (max-width: 992px) {
    .filter-bar {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-bar h2 {
        min-width: auto;
    }
    
    .filter-group {
        width: 100%;
        justify-content: space-between;
    }
    
    .filter-group .form-select {
        flex: 1;
        min-width: auto;
    }
}

@media (max-width: 768px) {
    .header-section {
        padding: 20px;
    }
    .stats-row {
        flex-direction: column;
    }
    .stat-item {
        min-width: auto;
    }
}

@media (max-width: 576px) {
    .filter-group {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-group label {
        text-align: left;
    }
    
    .filter-group .form-select {
        width: 100%;
    }
}
</style>

<div class="container">
    <div class="filter-bar">
        <h2><i class="fas fa-chart-pie me-2 text-success"></i> Analisis Efisiensi Anggaran</h2>
        
        <form method="GET" class="filter-group">
            <label for="bulan-select">
                <i class="fas fa-calendar-alt me-1"></i> Periode:
            </label>
            <select name="bulan" id="bulan-select" class="form-select">
                <?php foreach ($daftar_bulan as $b): ?>
                    <option value="<?= htmlspecialchars($b) ?>" <?= $b == $bulan_dipilih ? 'selected' : '' ?>>
                        <?= htmlspecialchars($b) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <select name="tahun" class="form-select">
                <?php for($i = date('Y'); $i >= date('Y')-3; $i--): ?>
                    <option value="<?= $i ?>" <?= $i == $tahun_dipilih ? 'selected' : '' ?>>
                        <?= $i ?>
                    </option>
                <?php endfor; ?>
            </select>
            
            <button type="submit" class="btn-filter">
                <i class="fas fa-search"></i>
                <span>Tampilkan</span>
            </button>
            
            <a href="?" class="btn-reset" title="Reset Filter">
                <i class="fas fa-redo-alt"></i>
            </a>
        </form>
    </div>

    <div class="card-minimal">
        <div class="header-section">
            <h1>Laporan Efisiensi Anggaran</h1>
            <div class="subtitle">Periode: <?= htmlspecialchars($bulan_dipilih . ' ' . $tahun_dipilih) ?></div>
            <div class="stats-row">
                <div class="stat-item">
                    <div class="stat-label">Total Pagu</div>
                    <div class="stat-value">Rp <?= number_format($total_efisiensi_pagu/1_000_000, 2, ',', '.') ?> M</div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Total Hemat</div>
                    <div class="stat-value"><?= $total_efisiensi_selisih >= 0 ? '+' : '' ?>Rp <?= number_format(abs($total_efisiensi_selisih), 0, ',', '.') ?></div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Efisiensi Global</div>
                    <div class="stat-value"><?= number_format($total_efisiensi_persen, 2, ',', '.') ?>%</div>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <?php if (empty($data_efisiensi_realisasi)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p class="mb-0">Tidak ada data efisiensi untuk periode ini.</p>
                </div>
            <?php else: ?>
                <table class="table-minimal">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Metode</th>
                            <th class="text-end">Pagu</th>
                            <th class="text-end">Realisasi</th>
                            <th class="text-end">Selisih</th>
                            <th class="text-center">Efisiensi</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; foreach ($data_efisiensi_realisasi as $metode => $d):
                            $is_over = $d['selisih'] < 0;
                            $bar_color = $is_over ? '#ef4444' : '#10b981';
                            $persen = min($d['persentase_realisasi'], 100);
                            $selisih_abs = abs($d['selisih']);
                        ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td>
                                <div class="fw-medium"><?= htmlspecialchars($metode) ?></div>
                                <small class="text-muted"><?= number_format($d['paket']) ?> paket</small>
                            </td>
                            <td class="text-end text-money text-secondary">
                                Rp <?= number_format($d['pagu'], 0, ',', '.') ?>
                            </td>
                            <td class="text-end text-money">
                                Rp <?= number_format($d['realisasi'], 0, ',', '.') ?>
                            </td>
                            <td class="text-end">
                                <div class="text-money" style="color: <?= $is_over ? '#ef4444' : '#10b981' ?>">
                                    <?= $d['selisih'] >= 0 ? '+' : '–' ?>Rp <?= number_format($selisih_abs, 0, ',', '.') ?>
                                </div>
                                <small class="mt-1 d-block" style="color: <?= $is_over ? '#dc2626' : '#059669' ?>;">
                                    <?= number_format($d['efisiensi'], 2, ',', '.') ?>%
                                </small>
                            </td>
                            <td class="text-center">
                                <div class="d-flex flex-column align-items-center">
                                    <div class="fw-medium"><?= number_format($d['persentase_realisasi'], 1) ?>%</div>
                                    <div class="progress-bar mt-1">
                                        <div class="progress-fill" style="width: <?= $persen ?>%; background-color: <?= $bar_color ?>;"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <?php if ($is_over): ?>
                                    <span class="badge badge-over">Over</span>
                                <?php elseif ($d['selisih'] == 0): ?>
                                    <span class="badge badge-pas">Pas</span>
                                <?php else: ?>
                                    <span class="badge badge-hemat">Hemat</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>

                        <tr class="total-row">
                            <td colspan="2" class="fw-bold">TOTAL</td>
                            <td class="text-end fw-bold text-money">
                                Rp <?= number_format($total_efisiensi_pagu, 0, ',', '.') ?>
                            </td>
                            <td class="text-end fw-bold text-money">
                                Rp <?= number_format($total_efisiensi_realisasi, 0, ',', '.') ?>
                            </td>
                            <td class="text-end">
                                <div class="fw-bold text-money" style="color: <?= $total_efisiensi_selisih >= 0 ? '#10b981' : '#ef4444' ?>;">
                                    <?= $total_efisiensi_selisih >= 0 ? '+' : '–' ?>Rp <?= number_format(abs($total_efisiensi_selisih), 0, ',', '.') ?>
                                </div>
                                <small class="d-block mt-1 fw-semibold" style="color: <?= $total_efisiensi_selisih >= 0 ? '#059669' : '#dc2626' ?>;">
                                    <?= number_format($total_efisiensi_persen, 2, ',', '.') ?>%
                                </small>
                            </td>
                            <td class="text-center">
                                <strong><?= number_format($total_efisiensi_persentase_realisasi, 1) ?>%</strong>
                            </td>   
                            <td class="text-center">
                                <i class="fas fa-check text-success"></i>
                            </td>
                        </tr>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="footer-note">
            <i class="fas fa-info-circle me-1"></i>
            Data diambil dari RUP (Pagu) dan realisasi kontrak/pembelian yang tervalidasi pada bulan
            <strong><?= htmlspecialchars($bulan_dipilih) ?></strong> <?= $tahun_dipilih ?>.
        </div>
    </div>
</div>

<?php include '../navbar/footer.php'; ?>