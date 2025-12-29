<?php
// =================================================================
// == FRONTEND DASHBOARD - REALISASI TENDER ========================
// =================================================================
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Logika pemilihan header
if (isset($_SESSION['username']) && !empty($_SESSION['username'])) {
    // Jika ada session login, panggil header khusus login
    include '../../navbar/header_login.php';
} else {
    // Jika tidak ada session, panggil header biasa/umum
    include '../../navbar/header.php'; 
}
// 1. Konfigurasi URL API
// Pastikan URL ini mengarah ke file API yang baru saja Anda revisi
$apiBaseUrl = "http://sipbanarnative.id/api/realisasi_tender.php";

// 2. Ambil Data Options (Dropdown)
$optionsApiUrl = $apiBaseUrl . '?action=options';
$optionsResponse = @file_get_contents($optionsApiUrl);
$optionsData = json_decode($optionsResponse, true);

// Default array jika API gagal
$options = $optionsData['options'] ?? [
    'nama_satker' => [],      // Sesuai API baru
    'jenis_pengadaan' => [],
    'metode_pengadaan' => [],
    'sumber_dana' => [],
    'jenis_kontrak' => []
];

// 3. Tangkap Parameter Filter dari URL
$currentPage = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = $_GET['limit'] ?? 50;

// Filter Waktu Default (Juli Tahun Ini)
$currentYear = date('Y');
$selectedBulan = $_GET['bulan'] ?? '07';
$selectedTahun = $_GET['tahun'] ?? $currentYear;

// 4. Siapkan Query Parameter Dasar
$queryParams = $_GET;
$queryParams['page'] = $currentPage;
$queryParams['limit'] = $limit;
$queryParams['bulan'] = $selectedBulan;
$queryParams['tahun'] = $selectedTahun;

// Bersihkan parameter kosong agar URL lebih bersih
$queryParams = array_filter($queryParams, function ($value) {
    return $value !== '' && $value !== null;
});

// A. URL untuk Data Tabel (List)
// API Action: list
$listParams = $queryParams;
$listParams['action'] = 'list'; // Pastikan action eksplisit
$queryString = http_build_query($listParams);
$apiUrl = $apiBaseUrl . '?' . $queryString;

// B. URL untuk Data Ringkasan (Summary/Total)
// Kita gunakan filter yang sama, tapi hapus page dan limit
$summaryParams = $queryParams;
unset($summaryParams['page']);
unset($summaryParams['limit']);
$summaryParams['action'] = 'summary'; // Action khusus summary

$summaryQueryString = http_build_query($summaryParams);
$apiSummaryUrl = $apiBaseUrl . '?' . $summaryQueryString;

// 5. Eksekusi Request ke API
// Menggunakan @ untuk menyembunyikan warning jika koneksi gagal
$response = @file_get_contents($apiUrl);
$data = json_decode($response, true);

$summaryResponse = @file_get_contents($apiSummaryUrl);
$summaryData = json_decode($summaryResponse, true);

// 6. Inisialisasi Variabel Statistik (Default 0)
$totalPaket = 0;
$totalPagu = 0;
$totalHPS = 0;
$totalKontrak = 0;
$efisiensi = 0; // Dalam persen

// Format string default
$formattedTotalPagu = 'Rp 0';
$formattedTotalHPS = 'Rp 0';
$formattedTotalKontrak = 'Rp 0';

// 7. Proses Data Statistik dari API Summary
// Bagian ini memastikan nilai diambil dari perhitungan backend
if ($summaryData && isset($summaryData['success']) && $summaryData['success'] && isset($summaryData['summary'])) {
    $summary = $summaryData['summary'];

    // Ambil data dan pastikan tipe datanya float/int
    $totalPaket = (int) ($summary['total_paket'] ?? 0);
    $totalPagu = (float) ($summary['total_pagu'] ?? 0);
    $totalHPS = (float) ($summary['total_hps'] ?? 0);
    $totalKontrak = (float) ($summary['total_kontrak'] ?? 0);
    $efisiensi = (float) ($summary['efisiensi_persen'] ?? 0);

    // Format Rupiah
    $formattedTotalPagu = 'Rp ' . number_format($totalPagu, 0, ',', '.');
    $formattedTotalHPS = 'Rp ' . number_format($totalHPS, 0, ',', '.');
    $formattedTotalKontrak = 'Rp ' . number_format($totalKontrak, 0, ',', '.');
}

// 8. Variabel Paginasi untuk Tabel
$totalPages = $data['pagination']['total_pages'] ?? 1;
$totalRecords = $data['pagination']['total_records'] ?? 0;
// Fallback jika API list kosong tapi summary ada (jarang terjadi)
if ($totalRecords == 0 && $totalPaket > 0 && empty($data['data'])) {
    // Biarkan 0 jika memang tidak ada data list yang dikembalikan
}

$page_title = "Realisasi Tender - SIP BANAR";

// Array Nama Bulan untuk Dropdown
$namaBulan = [
    '01' => 'Januari',
    '02' => 'Februari',
    '03' => 'Maret',
    '04' => 'April',
    '05' => 'Mei',
    '06' => 'Juni',
    '07' => 'Juli',
    '08' => 'Agustus',
    '09' => 'September',
    '10' => 'Oktober',
    '11' => 'November',
    '12' => 'Desember'
];

?>

<script src="../../js/submenu.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
    body {
        background: #f5f7fa;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .container {
        max-width: 1480px;
        margin: 0 auto;
        padding: 30px 20px;
    }

    /* Filter Section */
    .filter-section {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        margin-bottom: 30px;
        overflow: hidden;
    }

    .filter-header {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
        padding: 20px 30px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .filter-header h3 {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
    }

    .filter-content {
        padding: 35px 30px;
        background: #fafbfc;
    }

    .filter-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 25px;
        margin-bottom: 25px;
    }

    .filter-group {
        position: relative;
    }

    .filter-group label {
        display: block;
        margin-bottom: 10px;
        font-weight: 600;
        color: #495057;
        font-size: 13px;
        text-transform: uppercase;
    }

    .filter-group select,
    .filter-group input[type="text"] {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #e1e8ed;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.2s ease;
    }

    .filter-group select:focus,
    .filter-group input:focus {
        outline: none;
        border-color: #dc3545;
        box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
    }

    .filter-actions {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        padding-top: 25px;
        border-top: 2px solid #e9ecef;
    }

    .btn-search {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
        border: none;
        padding: 12px 32px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-reset {
        background: white;
        color: #6c757d;
        border: 2px solid #dee2e6;
        padding: 12px 28px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
    }

    .search-input-wrapper {
        position: relative;
    }

    .search-input-wrapper i {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: #adb5bd;
        font-size: 14px;
    }

    .search-input-wrapper input[type="text"] {
        padding-left: 44px !important;
    }

    /* Summary Cards */
    .summary-section {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        margin-bottom: 30px;
        overflow: hidden;
    }

    .summary-header {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
        padding: 20px 30px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .summary-header h3 {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
    }

    .summary-cards {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        background: #fafbfc;
        padding: 35px 30px;
        border-top: 4px solid transparent;
        background-image: linear-gradient(white, white), linear-gradient(90deg, #4a90e2 0%, #4a90e2 25%, #f5a623 25%, #f5a623 50%, #27ae60 50%, #27ae60 75%, #17a2b8 75%, #17a2b8 100%);
        background-origin: border-box;
        background-clip: padding-box, border-box;
    }

    .summary-card {
        display: flex;
        align-items: center;
        gap: 20px;
        padding: 0 20px;
        position: relative;
    }

    .summary-card:not(:last-child)::after {
        content: '';
        position: absolute;
        right: 0;
        top: 50%;
        transform: translateY(-50%);
        height: 60%;
        width: 1px;
        background: #dee2e6;
    }

    .card-icon {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 26px;
        color: white;
        flex-shrink: 0;
    }

    .card-icon.blue {
        background: linear-gradient(135deg, #4a90e2 0%, #357abd 100%);
    }

    .card-icon.orange {
        background: linear-gradient(135deg, #f5a623 0%, #e09200 100%);
    }

    .card-icon.green {
        background: linear-gradient(135deg, #27ae60 0%, #1e8449 100%);
    }

    .card-icon.cyan {
        background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
    }

    .card-content h4 {
        font-size: 26px;
        font-weight: 800;
        color: #2c3e50;
        margin: 0 0 5px 0;
        line-height: 1;
    }

    .card-content p {
        font-size: 12px;
        font-weight: 600;
        color: #6c757d;
        margin: 0;
        text-transform: uppercase;
    }

    /* Table Styles */
    .results-section {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        overflow: hidden;
    }

    .results-header {
        background: #f8f9fa;
        padding: 25px 30px;
        border-bottom: 2px solid #e9ecef;
    }

    .results-title {
        font-size: 18px;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 6px;
    }

    .results-subtitle {
        font-size: 13px;
        color: #6c757d;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    table th {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        padding: 16px 14px;
        text-align: left;
        font-size: 11px;
        text-transform: uppercase;
    }

    table td {
        padding: 16px 14px;
        border-bottom: 1px solid #f1f3f5;
        font-size: 13px;
    }

    table tr:hover {
        background: #f8f9fa;
    }

    .price {
        font-weight: 700;
        white-space: nowrap;
    }

    .badge {
        padding: 5px 12px;
        font-size: 10px;
        font-weight: 600;
        border-radius: 12px;
        text-transform: uppercase;
    }

    .badge-primary {
        background: #4a90e2;
        color: white;
    }

    .badge-default {
        background: #6c757d;
        color: white;
    }

    /* Select2 Custom */
    .select2-container--default .select2-selection--single {
        height: 46px;
        border: 2px solid #e1e8ed;
        border-radius: 8px;
        padding: 6px 16px;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 44px;
        right: 10px;
    }

    @media (max-width: 1200px) {
        .summary-cards {
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
        }

        .summary-card:not(:last-child)::after {
            display: none;
        }
    }

    @media (max-width: 768px) {

        .filter-grid,
        .summary-cards {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="container">
    <div class="filter-section">
        <div class="filter-header">
            <i class="fas fa-filter"></i>
            <h3>Filter Data Realisasi Tender</h3>
        </div>
        <div class="filter-content">
            <form method="GET">
                <div class="filter-grid">
                    <div class="filter-group">
                        <label><i class="fas fa-calendar"></i> Bulan <span class="badge badge-default">DEFAULT: JULI</span></label>
                        <select name="bulan">
                            <?php foreach ($namaBulan as $kode => $nama): ?>
                                <option value="<?= $kode ?>" <?= $selectedBulan == $kode ? 'selected' : '' ?>>
                                    <?= $nama ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label><i class="fas fa-calendar-alt"></i> Tahun</label>
                        <select name="tahun">
                            <?php for ($y = $currentYear; $y >= 2020; $y--): ?>
                                <option value="<?= $y ?>" <?= $selectedTahun == $y ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label><i class="fas fa-tags"></i> Jenis Pengadaan</label>
                        <select name="jenis_pengadaan">
                            <option value="">Semua Jenis</option>
                            <?php if (!empty($options['jenis_pengadaan'])): ?>
                                <?php foreach ($options['jenis_pengadaan'] as $jp): ?>
                                    <option value="<?= htmlspecialchars($jp) ?>" <?= ($_GET['jenis_pengadaan'] ?? '') == $jp ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($jp) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label><i class="fas fa-building"></i> Satuan Kerja</label>
                        <select name="nama_satker" id="nama_satker" class="select2-searchable">
                            <option value="">Semua Satuan Kerja</option>
                            <?php if (!empty($options['nama_satker'])): ?>
                                <?php foreach ($options['nama_satker'] as $s): ?>
                                    <option value="<?= htmlspecialchars($s) ?>" <?= ($_GET['nama_satker'] ?? '') == $s ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($s) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label><i class="fas fa-cogs"></i> Metode Pengadaan</label>
                        <select name="metode_pengadaan">
                            <option value="">Semua Metode</option>
                            <?php if (!empty($options['metode_pengadaan'])): ?>
                                <?php foreach ($options['metode_pengadaan'] as $mp): ?>
                                    <option value="<?= htmlspecialchars($mp) ?>" <?= ($_GET['metode_pengadaan'] ?? '') == $mp ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($mp) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label><i class="fas fa-wallet"></i> Sumber Dana</label>
                        <select name="sumber_dana">
                            <option value="">Semua Sumber Dana</option>
                            <?php if (!empty($options['sumber_dana'])): ?>
                                <?php foreach ($options['sumber_dana'] as $sd): ?>
                                    <option value="<?= htmlspecialchars($sd) ?>" <?= ($_GET['sumber_dana'] ?? '') == $sd ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($sd) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label><i class="fas fa-file-contract"></i> Jenis Kontrak</label>
                        <select name="jenis_kontrak">
                            <option value="">Semua Jenis Kontrak</option>
                            <?php if (!empty($options['jenis_kontrak'])): ?>
                                <?php foreach ($options['jenis_kontrak'] as $jk): ?>
                                    <option value="<?= htmlspecialchars($jk) ?>" <?= ($_GET['jenis_kontrak'] ?? '') == $jk ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($jk) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="filter-group" style="grid-column: span 2;">
                        <label><i class="fas fa-search"></i> Pencarian</label>
                        <div class="search-input-wrapper">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search"
                                placeholder="Cari nama paket, pemenang, atau kode tender..."
                                value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <div class="filter-actions">
                    <button type="button" class="btn-reset" onclick="resetForm()">
                        <i class="fas fa-redo"></i> Reset Filter
                    </button>
                    <button type="submit" class="btn-search">
                        <i class="fas fa-search"></i> Cari Data
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="summary-section">
        <div class="summary-header">
            <i class="fas fa-chart-bar"></i>
            <h3>Ringkasan Data Realisasi Tender</h3>
        </div>
        <div class="summary-cards">
            <div class="summary-card">
                <div class="card-icon blue"><i class="fas fa-clipboard-list"></i></div>
                <div class="card-content">
                    <h4><?= number_format($totalPaket, 0, ',', '.') ?></h4>
                    <p>Total Paket</p>
                </div>
            </div>

            <div class="summary-card">
                <div class="card-icon orange"><i class="fas fa-money-bill-wave"></i></div>
                <div class="card-content">
                    <h4><?= $formattedTotalPagu ?></h4>
                    <p>Total Pagu</p>
                </div>
            </div>

            <div class="summary-card">
                <div class="card-icon green"><i class="fas fa-handshake"></i></div>
                <div class="card-content">
                    <h4><?= $formattedTotalKontrak ?></h4>
                    <p>Total Nilai Kontrak</p>
                </div>
            </div>

            <div class="summary-card">
                <div class="card-icon cyan"><i class="fas fa-percentage"></i></div>
                <div class="card-content">
                    <h4><?= number_format($efisiensi, 2) ?>%</h4>
                    <p>Efisiensi Anggaran</p>
                </div>
            </div>
        </div>
    </div>

    <div class="results-section">
        <div class="results-header">
            <div>
                <div class="results-title"><i class="fas fa-table"></i> Hasil Data Realisasi Tender</div>
                <?php if ($data && isset($data['success']) && $data['success']): ?>
                    <div class="results-subtitle">
                        Menampilkan <?= count($data['data'] ?? []) ?> dari <?= number_format($totalRecords, 0, ',', '.') ?> total data
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($data && isset($data['success']) && $data['success'] && count($data['data']) > 0): ?>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 120px;">Kode Tender</th>
                            <th style="width: 300px;">Nama Paket</th>
                            <th style="width: 130px;">Nilai Pagu</th>
                            <th style="width: 130px;">Nilai HPS</th>
                            <th style="width: 130px;">Nilai Kontrak</th>
                            <th style="width: 200px;">Pemenang</th>
                            <th style="width: 100px;">Jenis</th>
                            <th style="width: 180px;">Satuan Kerja</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['data'] as $index => $row): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 600; color: #4a90e2; font-size: 11px;">
                                        <?= htmlspecialchars($row['Kode_Tender'] ?? '-') ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: #2c3e50;">
                                        <?= htmlspecialchars($row['Nama_Paket'] ?? '-') ?>
                                    </div>
                                </td>
                                <td class="price" style="color: #6c757d;">
                                    Rp <?= number_format((float) ($row['Nilai_Pagu'] ?? 0), 0, ',', '.') ?>
                                </td>
                                <td class="price" style="color: #f5a623;">
                                    Rp <?= number_format((float) ($row['Nilai_HPS'] ?? 0), 0, ',', '.') ?>
                                </td>
                                <td class="price" style="color: #27ae60;">
                                    Rp <?= number_format((float) ($row['Nilai_Kontrak'] ?? 0), 0, ',', '.') ?>
                                </td>
                                <td style="font-weight: 600; color: #495057;">
                                    <?= htmlspecialchars($row['Nama_Pemenang'] ?? '-') ?>
                                </td>
                                <td>
                                    <span class="badge badge-primary">
                                        <?= htmlspecialchars($row['Jenis_Pengadaan'] ?? '-') ?>
                                    </span>
                                </td>
                                <td style="color: #495057;">
                                    <?= htmlspecialchars($row['Nama_Satker'] ?? '-') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state" style="padding: 80px 40px; text-align: center; color: #6c757d;">
                <i class="fas fa-folder-open" style="font-size: 64px; margin-bottom: 20px; opacity: 0.3; color: #dc3545;"></i>
                <p><strong>Tidak ada data realisasi tender yang ditemukan</strong></p>
                <small>Coba ubah kriteria pencarian atau filter</small>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    function resetForm() {
        window.location.href = window.location.pathname + '?bulan=07&tahun=<?= $currentYear ?>';
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Init search input enter key
        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput) {  
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    this.form.submit();
                }
            });
        }

        // Init Select2 for Nama Satker
        $('#nama_satker').select2({
            placeholder: 'Ketik untuk mencari Satuan Kerja...',
            allowClear: true,
            width: '100%',
            language: {
                noResults: function() {
                    return "Tidak ditemukan Satuan Kerja yang cocok";
                },
                searching: function() {
                    return "Mencari...";
                }
            }
        });
    });
</script>

<?php include '../../navbar/footer.php'; ?>