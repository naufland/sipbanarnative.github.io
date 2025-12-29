<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Logika pemilihan header
if (isset($_SESSION['username']) && !empty($_SESSION['username'])) {
    // Jika ada session login, panggil header khusus login
    include '../navbar/header_login.php';
} else {
    // Jika tidak ada session, panggil header biasa/umum
    include '../navbar/header.php'; 
}
// URL API dasar
$apiBaseUrl = "http://sipbanarnative.id/api/sektoral.php";

// 1. Dapatkan parameter dari URL
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = $_GET['limit'] ?? 100;

// Default tahun anggaran: tahun sekarang
$currentYear = date('Y');
$selectedTahun = $_GET['tahun_anggaran'] ?? $currentYear;

// 2. Siapkan parameter query untuk API
$queryParams = $_GET;
$queryParams['page'] = $currentPage;
$queryParams['limit'] = $limit;
$queryParams['tahun_anggaran'] = $selectedTahun;

// Hapus parameter kosong
$queryParams = array_filter($queryParams, function ($value) {
    return $value !== '' && $value !== null;
});
$queryString = http_build_query($queryParams);
$apiUrl = $apiBaseUrl . '?' . $queryString;

// 3. Siapkan parameter untuk summary
$summaryParams = $queryParams;
unset($summaryParams['page']);
unset($summaryParams['limit']);
$summaryParams['action'] = 'summary';

$summaryQueryString = http_build_query($summaryParams);
$apiSummaryUrl = $apiBaseUrl . '?' . $summaryQueryString;

// 4. Panggil API
$response = @file_get_contents($apiUrl);
$data = json_decode($response, true);

$summaryResponse = @file_get_contents($apiSummaryUrl);
$summaryData = json_decode($summaryResponse, true);

// 5. Inisialisasi variabel statistik
$totalPaket = 0;
$totalPerencanaan = 0;
$totalPDN = 0;
$persentasePDN = 0;
$totalSKPD = 0;
$formattedTotalPerencanaan = 'Rp 0';
$formattedTotalPDN = 'Rp 0';

// 6. Proses data summary
if ($summaryData && isset($summaryData['success']) && $summaryData['success'] && isset($summaryData['summary'])) {
    $summary = $summaryData['summary'];
    $totalPaket = $summary['total_paket'] ?? 0;
    $totalPerencanaan = $summary['total_perencanaan'] ?? 0;
    $totalPDN = $summary['total_pdn'] ?? 0;
    $persentasePDN = $summary['persentase_pdn'] ?? 0;
    $totalSKPD = $summary['total_skpd'] ?? 0;
    $formattedTotalPerencanaan = 'Rp ' . number_format($totalPerencanaan, 0, ',', '.');
    $formattedTotalPDN = 'Rp ' . number_format($totalPDN, 0, ',', '.');
}

// 7. Paginasi
$totalPages = $data['pagination']['total_pages'] ?? 1;
$totalRecords = $data['pagination']['total_records'] ?? 0;
if ($totalPaket > 0) {
    $totalRecords = $totalPaket;
}

// Set page title
$page_title = "Statistik Sektoral - SIP BANAR";

// 8. Ambil daftar SKPD dan Kategori untuk dropdown
$skpdList = [];
$kategoriList = [];
$tahunList = [];
$apiOptionsUrl = $apiBaseUrl . '?action=options';
$optionsResponse = @file_get_contents($apiOptionsUrl);
if ($optionsResponse) {
    $optionsData = json_decode($optionsResponse, true);
    if ($optionsData && isset($optionsData['success']) && $optionsData['success']) {
        $skpdList = $optionsData['options']['nama_satker'] ?? [];
        $kategoriList = $optionsData['options']['kategori'] ?? [];
        $tahunList = $optionsData['options']['tahun_anggaran'] ?? [];
    }
}

// Include heade
?>

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<script src="../../js/submenu.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<style>
    /* Gunakan CSS yang sama seperti pengadaan.php */
    .container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 20px;
    }

    .filter-section {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        margin-bottom: 30px;
        overflow: hidden;
        border: 1px solid #e9ecef;
    }

    .filter-header {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        padding: 20px 25px;
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
        padding: 30px 25px;
    }

    .filter-row {
        display: grid;
        gap: 25px;
        margin-bottom: 25px;
    }

    .filter-row:nth-child(1) {
        grid-template-columns: 1fr 1fr 1fr;
    }

    .filter-row:nth-child(2) {
        grid-template-columns: 1fr;
    }

    .filter-group {
        position: relative;
    }

    .filter-group label {
        display: block;
        margin-bottom: 10px;
        font-weight: 600;
        color: #2c3e50;
        font-size: 14px;
    }

    .filter-group select,
    .filter-group input[type="text"] {
        width: 100%;
        padding: 14px 16px;
        border: 2px solid #e9ecef;
        border-radius: 10px;
        font-size: 14px;
        background: white;
        transition: all 0.3s ease;
    }

    .filter-group select:focus,
    .filter-group input[type="text"]:focus {
        outline: none;
        border-color: #2c3e50;
        box-shadow: 0 0 0 3px rgba(44, 62, 80, 0.15);
    }

    .search-row {
        display: flex;
        justify-content: flex-end;
        gap: 15px;
        padding-top: 25px;
        border-top: 2px solid #f1f3f4;
    }

    .search-btn {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        border: none;
        padding: 14px 30px;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .reset-btn {
        background: transparent;
        color: #6c757d;
        border: 2px solid #e9ecef;
        padding: 12px 25px;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
    }

    .summary-section {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        margin-bottom: 30px;
    }

    .summary-header {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        padding: 20px 25px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .summary-content {
        padding: 30px 25px;
    }

    .summary-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
    }

    .summary-card {
        background: white;
        border-radius: 12px;
        padding: 25px;
        display: flex;
        align-items: center;
        gap: 20px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        border-top: 4px solid;
    }

    .summary-card.primary { border-top-color: #3498db; }
    .summary-card.success { border-top-color: #27ae60; }
    .summary-card.warning { border-top-color: #f39c12; }
    .summary-card.info { border-top-color: #17a2b8; }

    .card-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 24px;
    }

    .summary-card.primary .card-icon { background: linear-gradient(135deg, #3498db, #5dade2); }
    .summary-card.success .card-icon { background: linear-gradient(135deg, #27ae60, #58d68d); }
    .summary-card.warning .card-icon { background: linear-gradient(135deg, #f39c12, #f8b739); }
    .summary-card.info .card-icon { background: linear-gradient(135deg, #17a2b8, #5dade2); }

    .card-content {
        flex: 1;
    }

    .card-value {
        font-size: 24px;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 5px;
    }

    .card-label {
        font-size: 14px;
        font-weight: 600;
        color: #2c3e50;
    }

    .card-subtitle {
        font-size: 12px;
        color: #6c757d;
    }

    .results-section {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .results-header {
        background: #f8f9fa;
        padding: 25px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 2px solid #e9ecef;
    }

    .pagination {
        display: flex;
        gap: 8px;
    }

    .pagination a.btn-pagination {
        text-decoration: none;
        width: 40px;
        height: 40px;
        border: 2px solid #e9ecef;
        background: white;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        color: #6c757d;
    }

    .pagination a.btn-pagination.active {
        background: #2c3e50;
        border-color: #2c3e50;
        color: white;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    table th {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        padding: 18px 15px;
        text-align: left;
        font-weight: 600;
    }

    table td {
        padding: 18px 15px;
        border-bottom: 1px solid #f1f1f1;
    }

    table tr:hover {
        background: #f8f9fa;
    }

    .badge {
        display: inline-block;
        padding: 6px 12px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        border-radius: 20px;
    }

    .badge-primary { background: #3498db; color: white; }
    .badge-success { background: #27ae60; color: white; }
    .badge-warning { background: #f39c12; color: white; }

    .price {
        font-weight: 700;
        color: #27ae60;
        font-size: 15px;
    }

    .empty-state {
        padding: 60px 40px;
        text-align: center;
        color: #6c757d;
    }

    @media (max-width: 992px) {
        .filter-row:nth-child(1) {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="container">
    <!-- Filter Section -->
    <div class="filter-section">
        <div class="filter-header">
            <i class="fas fa-filter"></i>
            <h3>Filter Data Statistik Sektoral</h3>
        </div>
        <div class="filter-content">
            <form method="GET" action="" id="filterForm">
                <div class="filter-row">
                    <div class="filter-group">
                        <label><i class="fas fa-calendar-alt"></i> Tahun Anggaran</label>
                        <select name="tahun_anggaran">
                            <option value="">Semua Tahun</option>
                            <?php foreach ($tahunList as $tahun): ?>
                                <option value="<?= $tahun ?>" <?= $selectedTahun == $tahun ? 'selected' : '' ?>>
                                    <?= $tahun ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label><i class="fas fa-building"></i> SKPD (Satuan Kerja)</label>
                        <select name="nama_satker" id="nama_satker" class="select2-searchable">
                            <option value="">Semua SKPD</option>
                            <?php
                            if (!empty($skpdList)) {
                                $selectedSKPD = $_GET['nama_satker'] ?? '';
                                foreach ($skpdList as $skpd) {
                                    $selected = $selectedSKPD == $skpd ? 'selected' : '';
                                    echo '<option value="' . htmlspecialchars($skpd) . '" ' . $selected . '>';
                                    echo htmlspecialchars($skpd);
                                    echo '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label><i class="fas fa-tags"></i> Kategori</label>
                        <select name="kategori" id="kategori" class="select2-searchable">
                            <option value="">Semua Kategori</option>
                            <?php
                            if (!empty($kategoriList)) {
                                $selectedKategori = $_GET['kategori'] ?? '';
                                foreach ($kategoriList as $kategori) {
                                    $selected = $selectedKategori == $kategori ? 'selected' : '';
                                    echo '<option value="' . htmlspecialchars($kategori) . '" ' . $selected . '>';
                                    echo htmlspecialchars($kategori);
                                    echo '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="filter-row">
                    <div class="filter-group">
                        <label><i class="fas fa-search"></i> Pencarian Paket</label>
                        <input type="text" name="search" placeholder="Cari nama paket, SKPD, atau kategori..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    </div>
                </div>

                <div class="search-row">
                    <button type="button" class="reset-btn" onclick="resetForm()">
                        <i class="fas fa-undo"></i> Reset Filter
                    </button>
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i> Cari Data
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Section -->
    <div class="summary-section">
        <div class="summary-header">
            <div>
                <i class="fas fa-chart-bar"></i>
                <span style="font-size: 18px; font-weight: 600; margin-left: 10px;">Ringkasan Statistik Sektoral</span>
            </div>
            <div style="background: rgba(255,255,255,0.2); padding: 8px 16px; border-radius: 20px;">
                <i class="fas fa-calendar-check"></i> Tahun <?= $selectedTahun ?>
            </div>
        </div>
        <div class="summary-content">
            <div class="summary-cards">
                <div class="summary-card primary">
                    <div class="card-icon"><i class="fas fa-boxes"></i></div>
                    <div class="card-content">
                        <div class="card-value"><?= number_format($totalPaket, 0, ',', '.') ?></div>
                        <div class="card-label">Total Paket</div>
                        <div class="card-subtitle">Tahun <?= $selectedTahun ?></div>
                    </div>
                </div>

                <div class="summary-card success">
                    <div class="card-icon"><i class="fas fa-money-bill-wave"></i></div>
                    <div class="card-content">
                        <div class="card-value"><?= $formattedTotalPerencanaan ?></div>
                        <div class="card-label">Total Perencanaan</div>
                        <div class="card-subtitle">Keseluruhan</div>
                    </div>
                </div>

                <div class="summary-card warning">
                    <div class="card-icon"><i class="fas fa-flag"></i></div>
                    <div class="card-content">
                        <div class="card-value"><?= $formattedTotalPDN ?></div>
                        <div class="card-label">Total PDN</div>
                        <div class="card-subtitle"><?= number_format($persentasePDN, 1) ?>% dari Total</div>
                    </div>
                </div>

                <div class="summary-card info">
                    <div class="card-icon"><i class="fas fa-building"></i></div>
                    <div class="card-content">
                        <div class="card-value"><?= $totalSKPD ?></div>
                        <div class="card-label">Total SKPD</div>
                        <div class="card-subtitle">Satuan Kerja</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Results Section -->
    <div class="results-section">
        <div class="results-header">
            <div>
                <div style="font-size: 20px; font-weight: 700; color: #2c3e50;">
                    <i class="fas fa-table"></i> Data Statistik Sektoral
                </div>
                <?php if ($data && isset($data['success']) && $data['success']) : ?>
                    <div style="font-size: 14px; color: #6c757d; margin-top: 8px;">
                        Menampilkan <?= count($data['data']) ?> dari <?= number_format($totalRecords, 0, ',', '.') ?> total data
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="pagination">
                <?php
                $paginationParams = $_GET;
                unset($paginationParams['page']);
                $paginationQuery = http_build_query($paginationParams);
                ?>
                <a href="?<?= $paginationQuery ?>&page=<?= max(1, $currentPage - 1) ?>" class="btn-pagination">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <?php for ($i = 1; $i <= min($totalPages, 10); $i++): ?>
                    <a href="?<?= $paginationQuery ?>&page=<?= $i ?>" class="btn-pagination <?= $i == $currentPage ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
                <a href="?<?= $paginationQuery ?>&page=<?= min($totalPages, $currentPage + 1) ?>" class="btn-pagination">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        </div>

        <?php if ($data && isset($data['success']) && $data['success'] && count($data['data']) > 0) : ?>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tahun</th>
                            <th>SKPD</th>
                            <th>Kategori</th>
                            <th>Kode RUP</th>
                            <th>Nama Paket</th>
                            <th>Total Perencanaan</th>
                            <th>PDN</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['data'] as $row) : ?>
                            <tr>
                                <td><?= $row['No'] ?></td>
                                <td><?= htmlspecialchars($row['Tahun_Anggaran']) ?></td>
                                <td><small><?= htmlspecialchars($row['Nama_Satker']) ?></small></td>
                                <td><span class="badge badge-primary"><?= htmlspecialchars($row['Kategori']) ?></span></td>
                                <td><small><?= htmlspecialchars($row['Kode_RUP']) ?></small></td>
                                <td><?= htmlspecialchars($row['Nama_Paket']) ?></td>
                                <td class="price">Rp <?= number_format($row['Total_Perencanaan_Rp'], 0, ',', '.') ?></td>
                                <td class="price">Rp <?= number_format($row['PDN_Rp'], 0, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else : ?>
            <div class="empty-state">
                <i class="fas fa-search-minus" style="font-size: 64px; opacity: 0.3;"></i>
                <p><strong>Tidak ada data yang ditemukan</strong></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- jQuery & Select2 -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize Select2
    $('.select2-searchable').select2({
        placeholder: 'Ketik untuk mencari...',
        allowClear: true,
        width: '100%'
    });
});

function resetForm() {
    window.location.href = window.location.pathname + '?tahun_anggaran=<?= $currentYear ?>';
}
</script>

<?php
include '../navbar/footer.php';
?>