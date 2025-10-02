<?php
// =================================================================
// == FILE DASHBOARD UNTUK PENCATATAN NON-TENDER ==================
// =================================================================

// 1. URL API untuk Pencatatan Non-Tender
$apiBaseUrl = "http://sipbanar-phpnative.id/api/pencatatan_nontender.php";

// 2. Dapatkan parameter dari URL
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = $_GET['limit'] ?? 50;

// 3. Siapkan parameter query untuk API data tabel
$queryParams = array_filter($_GET); // Ambil semua filter dari URL
$queryParams['page'] = $currentPage;
$queryParams['limit'] = $limit;
$queryString = http_build_query($queryParams);
$apiUrl = $apiBaseUrl . '?' . $queryString;

// 4. Siapkan parameter untuk mengambil data SUMMARY
$summaryParams = $queryParams;
unset($summaryParams['page'], $summaryParams['limit']);
$summaryParams['action'] = 'summary';
$summaryQueryString = http_build_query($summaryParams);
$apiSummaryUrl = $apiBaseUrl . '?' . $summaryQueryString;

// 5. Panggil API untuk data tabel dan data summary
$response = @file_get_contents($apiUrl);
$data = json_decode($response, true);
$summaryResponse = @file_get_contents($apiSummaryUrl);
$summaryData = json_decode($summaryResponse, true);

// 6. Inisialisasi dan proses variabel statistik (disesuaikan untuk Pencatatan Non-Tender)
$totalPaket = 0;
$totalPagu = 0;
$totalRealisasi = 0;
$totalPDN = 0;
$totalUMK = 0;
$formattedTotalPagu = 'Rp 0';
$formattedTotalRealisasi = 'Rp 0';
$formattedTotalPDN = 'Rp 0';
$formattedTotalUMK = 'Rp 0';

if ($summaryData && ($summaryData['success'] ?? false) && isset($summaryData['summary'])) {
    $summary = $summaryData['summary'];
    $totalPaket = $summary['total_paket'] ?? 0;
    $totalPagu = $summary['total_pagu'] ?? 0;
    $totalRealisasi = $summary['total_realisasi'] ?? 0;
    $totalPDN = $summary['total_pdn'] ?? 0;
    $totalUMK = $summary['total_umk'] ?? 0;

    $formattedTotalPagu = 'Rp ' . number_format($totalPagu, 0, ',', '.');
    $formattedTotalRealisasi = 'Rp ' . number_format($totalRealisasi, 0, ',', '.');
    $formattedTotalPDN = 'Rp ' . number_format($totalPDN, 0, ',', '.');
    $formattedTotalUMK = 'Rp ' . number_format($totalUMK, 0, ',', '.');
}

// 7. Siapkan variabel untuk paginasi
$tableData = $data['data'] ?? [];
$totalPages = $data['pagination']['total_pages'] ?? 1;
$totalRecords = $data['pagination']['total_records'] ?? 0;
if ($totalPaket > 0) {
    $totalRecords = $totalPaket;
}

// 8. Set judul halaman
$page_title = "Data Pencatatan Non-Tender - SIP BANAR";

// --- Mulai Output HTML ---
include '../../navbar/header.php';
?>
<script src="../../js/submenu.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<style>
    body {
        font-family: 'Inter', sans-serif;
        background-color: #f8f9fa;
    }

    .container {
        max-width: 1600px;
        margin: 0 auto;
        padding: 20px;
    }

    .filter-section,
    .summary-section,
    .results-section {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        margin-bottom: 30px;
        border: 1px solid #e9ecef;
        animation: fadeInUp 0.6s ease-out;
    }

    .filter-header,
    .summary-header {
        background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%);
        color: white;
        padding: 20px 25px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .filter-header h3,
    .summary-header h3 {
        margin: 0;
        font-size: 18px;
    }

    .filter-content,
    .summary-content {
        padding: 30px 25px;
    }

    .filter-row {
        display: grid;
        gap: 25px;
        margin-bottom: 25px;
    }

    .filter-group label {
        display: block;
        margin-bottom: 10px;
        font-weight: 600;
        color: #2c3e50;
    }

    .filter-group select,
    .filter-group input {
        width: 100%;
        padding: 14px 16px;
        border: 2px solid #e9ecef;
        border-radius: 10px;
        font-size: 14px;
        transition: all 0.3s ease;
        box-sizing: border-box;
    }

    .filter-group select:focus,
    .filter-group input:focus {
        outline: none;
        border-color: #6f42c1;
        box-shadow: 0 0 0 3px rgba(111, 66, 193, 0.15);
    }

    .search-input-wrapper {
        position: relative;
    }

    .search-input-wrapper i {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: #6c757d;
    }

    .search-input-wrapper input {
        padding-left: 45px !important;
    }

    .search-row {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        padding-top: 25px;
        border-top: 2px solid #f1f3f4;
        gap: 15px;
    }

    .search-btn,
    .reset-btn {
        border: none;
        padding: 14px 30px;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
    }

    .search-btn {
        background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%);
        color: white;
    }

    .search-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(111, 66, 193, 0.3);
    }

    .reset-btn {
        background: transparent;
        color: #6c757d;
        border: 2px solid #e9ecef;
    }

    .reset-btn:hover {
        border-color: #6f42c1;
        color: #6f42c1;
    }

    .summary-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
    }

    .summary-card {
        padding: 25px;
        display: flex;
        align-items: center;
        gap: 20px;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    }

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

    .summary-card.primary .card-icon {
        background: linear-gradient(135deg, #6f42c1, #8b5cf6);
    }

    .summary-card.warning .card-icon {
        background: linear-gradient(135deg, #5e35b1, #7e57c2);
    }

    .summary-card.success .card-icon {
        background: linear-gradient(135deg, #512da8, #673ab7);
    }

    .summary-card.info .card-icon {
        background: linear-gradient(135deg, #4527a0, #5e35b1);
    }

    .summary-card.secondary .card-icon {
        background: linear-gradient(135deg, #6a1b9a, #8e24aa);
    }

    .card-value {
        font-size: 24px;
        font-weight: 700;
        color: #2c3e50;
    }

    .card-label {
        font-size: 14px;
        font-weight: 600;
        color: #6c757d;
    }

    .results-header {
        padding: 25px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 2px solid #e9ecef;
    }

    .results-title {
        font-size: 20px;
        font-weight: 700;
        color: #2c3e50;
    }

    .results-subtitle {
        font-size: 14px;
        color: #6c757d;
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
        transition: all 0.3s ease;
    }

    .pagination a.btn-pagination:hover {
        border-color: #6f42c1;
        color: #6f42c1;
    }

    .pagination a.btn-pagination.active {
        background: #6f42c1;
        border-color: #6f42c1;
        color: white;
    }

    .pagination a.btn-pagination.disabled {
        pointer-events: none;
        opacity: 0.6;
    }

    .pagination .btn-pagination-dots {
        width: 40px;
        height: 40px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .table-container {
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
        min-width: 1800px;
    }

    table th {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        padding: 16px 12px;
        text-align: left;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    table td {
        padding: 16px 12px;
        border-bottom: 1px solid #f1f1f1;
        vertical-align: middle;
    }

    table tr:nth-child(even) {
        background: #fafafa;
    }

    table tr:hover {
        background: #f0f0f0;
    }

    .badge {
        display: inline-block;
        padding: 6px 12px;
        font-size: 10px;
        font-weight: 600;
        border-radius: 20px;
    }

    .badge-purple {
        background: #6f42c1;
        color: white;
    }

    .badge-info {
        background: #17a2b8;
        color: white;
    }

    .badge-warning {
        background: #ffc107;
        color: #212529;
    }

    .empty-state {
        padding: 60px 40px;
        text-align: center;
        color: #6c757d;
    }

    .empty-state i {
        font-size: 64px;
        margin-bottom: 20px;
        opacity: 0.3;
        color: #6f42c1;
    }

    .empty-state p {
        font-size: 18px;
        margin: 0;
    }

    .table-footer {
        padding: 20px 25px;
        border-top: 2px solid #e9ecef;
        background: #f8f9fa;
        font-size: 14px;
        color: #6c757d;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .text-right {
        text-align: right;
    }

    .text-purple {
        color: #6f42c1;
        font-weight: 600;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<div class="container">
    <div class="filter-section">
        <div class="filter-header">
            <i class="fas fa-filter"></i>
            <h3>Filter Data Pencatatan Non-Tender</h3>
        </div>
        <div class="filter-content">
            <form id="filterForm" method="GET" action="">
                <div class="filter-row" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
                    <div class="filter-group">
                        <label><i class="fas fa-calendar-alt"></i> Tahun Anggaran</label>
                        <select name="tahun">
                            <option value="">Semua Tahun</option>
                            <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                                <option value="<?= $y ?>" <?= ($_GET['tahun'] ?? '') == $y ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-building"></i> KLPD</label>
                        <select name="klpd">
                            <option value="">Semua KLPD</option>
                            <option value="Pemerintah Daerah Kota Banjarmasin" <?= ($_GET['klpd'] ?? '') == 'Pemerintah Daerah Kota Banjarmasin' ? 'selected' : '' ?>>Kota Banjarmasin</option>
                            <option value="Pemerintah Daerah Kabupaten Banjar" <?= ($_GET['klpd'] ?? '') == 'Pemerintah Daerah Kabupaten Banjar' ? 'selected' : '' ?>>Kabupaten Banjar</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-handshake"></i> Metode Pengadaan</label>
                        <select name="metode_pengadaan">
                            <option value="">Semua Metode</option>
                            <option value="Penunjukan Langsung" <?= ($_GET['metode_pengadaan'] ?? '') == 'Penunjukan Langsung' ? 'selected' : '' ?>>Penunjukan Langsung</option>
                            <option value="Pengadaan Langsung" <?= ($_GET['metode_pengadaan'] ?? '') == 'Pengadaan Langsung' ? 'selected' : '' ?>>Pengadaan Langsung</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-box"></i> Jenis Pengadaan</label>
                        <select name="jenis_pengadaan">
                            <option value="">Semua Jenis</option>
                            <option value="Barang" <?= ($_GET['jenis_pengadaan'] ?? '') == 'Barang' ? 'selected' : '' ?>>Barang</option>
                            <option value="Pekerjaan Konstruksi" <?= ($_GET['jenis_pengadaan'] ?? '') == 'Pekerjaan Konstruksi' ? 'selected' : '' ?>>Pekerjaan Konstruksi</option>
                            <option value="Jasa Konsultansi" <?= ($_GET['jenis_pengadaan'] ?? '') == 'Jasa Konsultansi' ? 'selected' : '' ?>>Jasa Konsultansi</option>
                            <option value="Jasa Lainnya" <?= ($_GET['jenis_pengadaan'] ?? '') == 'Jasa Lainnya' ? 'selected' : '' ?>>Jasa Lainnya</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-info-circle"></i> Status Paket</label>
                        <select name="status_paket">
                            <option value="">Semua Status</option>
                            <option value="Aktif" <?= ($_GET['status_paket'] ?? '') == 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                            <option value="Selesai" <?= ($_GET['status_paket'] ?? '') == 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                            <option value="Batal" <?= ($_GET['status_paket'] ?? '') == 'Batal' ? 'selected' : '' ?>>Batal</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-search"></i> Pencarian</label>
                        <div class="search-input-wrapper">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" placeholder="Cari Nama Paket atau Pemenang..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                        </div>
                    </div>
                </div>
                <div class="search-row">
                    <button type="button" class="reset-btn" onclick="window.location.href=window.location.pathname">
                        <i class="fas fa-undo"></i> Reset
                    </button>
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i> Cari Data
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="summary-section">
        <div class="summary-header">
            <i class="fas fa-chart-bar"></i>
            <h3>Ringkasan Data Pencatatan Non-Tender</h3>
        </div>
        <div class="summary-content">
            <div class="summary-cards">
                <div class="summary-card primary">
                    <div class="card-icon"><i class="fas fa-boxes"></i></div>
                    <div class="card-content">
                        <div class="card-value"><?= number_format($totalPaket, 0, ',', '.') ?></div>
                        <div class="card-label">Total Paket</div>
                    </div>
                </div>
                <div class="summary-card warning">
                    <div class="card-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                    <div class="card-content">
                        <div class="card-value"><?= $formattedTotalPagu ?></div>
                        <div class="card-label">Total Pagu</div>
                    </div>
                </div>
                <div class="summary-card success">
                    <div class="card-icon"><i class="fas fa-file-contract"></i></div>
                    <div class="card-content">
                        <div class="card-value"><?= $formattedTotalRealisasi ?></div>
                        <div class="card-label">Total Realisasi</div>
                    </div>
                </div>
                <div class="summary-card info">
                    <div class="card-icon"><i class="fas fa-flag"></i></div>
                    <div class="card-content">
                        <div class="card-value"><?= $formattedTotalPDN ?></div>
                        <div class="card-label">Total PDN</div>
                    </div>
                </div>
                <div class="summary-card secondary">
                    <div class="card-icon"><i class="fas fa-industry"></i></div>
                    <div class="card-content">
                        <div class="card-value"><?= $formattedTotalUMK ?></div>
                        <div class="card-label">Total UMK</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="results-section">
        <div class="results-header">
            <div>
                <div class="results-title"><i class="fas fa-table"></i> Hasil Data Pencatatan Non-Tender</div>
                <div class="results-subtitle">
                    <strong>Menampilkan <?= count($tableData) ?> dari <?= number_format($totalRecords, 0, ',', '.') ?> total data</strong>
                </div>
            </div>
            <div class="pagination">
                <?php
                $paginationParams = $_GET;
                unset($paginationParams['page']);
                $paginationQuery = http_build_query($paginationParams);
                ?>
                <a href="?<?= $paginationQuery ?>&page=<?= max(1, $currentPage - 1) ?>" class="btn-pagination <?= $currentPage <= 1 ? 'disabled' : '' ?>"><i class="fas fa-chevron-left"></i></a>
                <?php for ($i = 1; $i <= $totalPages; $i++):
                    if ($i == $currentPage || abs($i - $currentPage) < 2 || $i <= 2 || $i > $totalPages - 2): ?>
                        <a href="?<?= $paginationQuery ?>&page=<?= $i ?>" class="btn-pagination <?= $i == $currentPage ? 'active' : '' ?>"><?= $i ?></a>
                    <?php elseif ($i == $currentPage - 2 || $i == $currentPage + 2): ?>
                        <span class="btn-pagination-dots">...</span>
                <?php endif;
                endfor; ?>
                <a href="?<?= $paginationQuery ?>&page=<?= min($totalPages, $currentPage + 1) ?>" class="btn-pagination <?= $currentPage >= $totalPages ? 'disabled' : '' ?>"><i class="fas fa-chevron-right"></i></a>
            </div>
        </div>

        <?php if (!empty($tableData)) : ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 3%;">No</th>
                            <th style="width: 18%;">Nama Paket</th>
                            <th style="width: 8%;">Kode Paket</th>
                            <th style="width: 8%;">Node RUP</th>
                            <th style="width: 9%;">KLPD</th>
                            <th style="width: 7%;">Metode</th>
                            <th style="width: 7%;">Jenis</th>
                            <th style="width: 8%;">Nilai Pagu</th>
                            <th style="width: 8%;">Total Realisasi</th>
                            <th style="width: 7%;">Nilai PDN</th>
                            <th style="width: 7%;">Nilai UMK</th>
                            <th style="width: 10%;">Nama Pemenang</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tableData as $row) : ?>
                            <tr>
                                <td style="text-align: center; font-weight: bold;">
                                    <?= htmlspecialchars($row['No_Urut'] ?? '-') ?>
                                </td>
                                <td><?= htmlspecialchars($row['Nama_Paket'] ?? '-') ?></td>
                                <td><i class="fas fa-barcode" style="margin-right: 5px; color: #6c757d;"></i> <?= htmlspecialchars($row['Kode_Paket'] ?? '-') ?></td>
                                <td><i class="fas fa-trophy" style="margin-right: 5px; color: #f39c12;"></i> <?= htmlspecialchars($row['Node_RUP'] ?? '-') ?></td>
                                <td><i class="fas fa-building" style="margin-right: 5px; color: #6f42c1;"></i> <?= htmlspecialchars($row['KLPD'] ?? '-') ?></td>
                                <td style="text-align: center;"><span class="badge badge-purple"><?= htmlspecialchars($row['Metode_pengadaan'] ?? '-') ?></span></td>
                                <td style="text-align: center;"><span class="badge badge-info"><?= htmlspecialchars($row['Jenis_Pengadaan'] ?? '-') ?></span></td>
                                <td class="text-right">
                                    <?php
                                    $nilaiPagu = $row['Nilai_Pagu'] ?? 0;
                                    echo 'Rp ' . number_format($nilaiPagu, 0, ',', '.');
                                    ?>
                                </td>
                                <td class="text-right text-purple">
                                    <?php
                                    $nilaiRealisasi = $row['Nilai_Total_Realisasi'] ?? 0;
                                    echo 'Rp ' . number_format($nilaiRealisasi, 0, ',', '.');
                                    ?>
                                </td>
                                <td class="text-right">
                                    <?php
                                    $nilaiPDN = $row['Nilai_PDN'] ?? 0;
                                    echo 'Rp ' . number_format($nilaiPDN, 0, ',', '.');
                                    ?>
                                </td>
                                <td class="text-right">
                                    <?php
                                    $nilaiUMK = $row['Nilai_UMK'] ?? 0;
                                    echo 'Rp ' . number_format($nilaiUMK, 0, ',', '.');
                                    ?>
                                </td>
                                <td><?= htmlspecialchars($row['Nama_Pemenang'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="table-footer">
                <div><strong>Halaman:</strong> <?= $currentPage ?> dari <?= $totalPages ?></div>
                <div><strong>Total Data:</strong> <?= number_format($totalRecords, 0, ',', '.') ?> paket pencatatan non-tender</div>
            </div>
        <?php else : ?>
            <div class="empty-state">
                <i class="fas fa-search-minus"></i>
                <p><strong>Tidak ada data pencatatan non-tender yang ditemukan</strong></p>
                <small class="text-muted">Silakan ubah kriteria filter Anda.</small>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    function resetForm() {
        window.location.href = window.location.pathname;
    }

    document.addEventListener('DOMContentLoaded', function() {
        const filterForm = document.querySelector('form');

        // Auto submit on select change (optional)
        const selects = filterForm.querySelectorAll('select');
        selects.forEach(select => {
            select.addEventListener('change', function() {
                // Uncomment line below to enable auto-submit
                // filterForm.submit();
            });
        });

        // Smooth scroll animation
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    });
</script>

<?php
include '../../navbar/footer.php';
?>