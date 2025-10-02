<?php
// =================================================================
// == FILE DASHBOARD UNTUK E-PURCHASING ===========================
// =================================================================

// 1. URL API untuk E-Purchasing
$apiBaseUrl = "http://sipbanar-phpnative.id/api/epurchasing.php";

// 2. Dapatkan parameter dari URL
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = $_GET['limit'] ?? 50;

// 3. Siapkan parameter query untuk API data tabel
$queryParams = array_filter($_GET); // Ambil semua filter dari URL
$queryParams['page'] = $currentPage;
$queryParams['limit'] = $limit;
$queryParams['action'] = 'list';
$queryString = http_build_query($queryParams);
$apiUrl = $apiBaseUrl . '?' . $queryString;

// 4. Siapkan parameter untuk mengambil data SUMMARY
$summaryParams = $queryParams;
unset($summaryParams['page'], $summaryParams['limit']);
$summaryParams['action'] = 'summary';
$summaryQueryString = http_build_query($summaryParams);
$apiSummaryUrl = $apiBaseUrl . '?' . $summaryQueryString;

// 4a. Siapkan parameter untuk mengambil daftar status unik
$statusParams = ['action' => 'get_status_list'];
$statusQueryString = http_build_query($statusParams);
$apiStatusUrl = $apiBaseUrl . '?' . $statusQueryString;

// 5. Panggil API untuk data tabel dan data summary
$response = @file_get_contents($apiUrl);
$data = json_decode($response, true);
$summaryResponse = @file_get_contents($apiSummaryUrl);
$summaryData = json_decode($summaryResponse, true);
$statusResponse = @file_get_contents($apiStatusUrl);
$statusList = json_decode($statusResponse, true);

// 6. Inisialisasi dan proses variabel statistik
$totalPaket = 0;
$totalNilai = 0;
$totalKuantitas = 0;
$rataRataNilai = 0;
$formattedTotalNilai = 'Rp 0';
$formattedRataRataNilai = 'Rp 0';

if ($summaryData && ($summaryData['success'] ?? false) && isset($summaryData['summary'])) {
    $summary = $summaryData['summary'];
    $totalPaket = $summary['total_paket'] ?? 0;
    $totalNilai = $summary['total_nilai'] ?? 0;
    $totalKuantitas = $summary['total_kuantitas'] ?? 0;
    $rataRataNilai = $summary['rata_rata_nilai'] ?? 0;

    $formattedTotalNilai = 'Rp ' . number_format($totalNilai, 0, ',', '.');
    $formattedRataRataNilai = 'Rp ' . number_format($rataRataNilai, 0, ',', '.');
}

// 7. Siapkan variabel untuk paginasi
$tableData = $data['data'] ?? [];
$totalPages = $data['pagination']['total_pages'] ?? 1;
$totalRecords = $data['pagination']['total_records'] ?? 0;
if ($totalPaket > 0) {
    $totalRecords = $totalPaket;
}

// 8. Set judul halaman
$page_title = "Data E-Purchasing - SIP BANAR";

// 9. Fungsi helper untuk format status
function getStatusBadge($status) {
    $statusMap = [
        'proses_kontrak_ppk' => ['label' => 'Proses Kontrak PPK', 'class' => 'badge-warning'],
        'pending' => ['label' => 'Pending', 'class' => 'badge-warning'],
        'approved' => ['label' => 'Approved', 'class' => 'badge-success'],
        'completed' => ['label' => 'Selesai', 'class' => 'badge-success'],
        'cancelled' => ['label' => 'Dibatalkan', 'class' => 'badge-danger'],
    ];
    
    $statusInfo = $statusMap[$status] ?? ['label' => ucfirst(str_replace('_', ' ', $status)), 'class' => 'badge-primary'];
    return $statusInfo;
}

// 10. Fungsi helper untuk format label status
function getStatusLabel($status) {
    $statusLabels = [
        'proses_kontrak_ppk' => 'Proses Kontrak PPK',
        'pending' => 'Pending',
        'approved' => 'Approved',
        'completed' => 'Selesai',
        'cancelled' => 'Dibatalkan',
    ];
    
    return $statusLabels[$status] ?? ucfirst(str_replace('_', ' ', $status));
}

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
        background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
        color: white;
        padding: 20px 25px;
        border-radius: 15px 15px 0 0;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .filter-header h3,
    .summary-header h3 {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
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
        font-size: 14px;
    }

    .filter-group select,
    .filter-group input {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #e9ecef;
        border-radius: 10px;
        font-size: 14px;
        transition: all 0.3s ease;
        box-sizing: border-box;
    }

    .filter-group select:focus,
    .filter-group input:focus {
        outline: none;
        border-color: #0d6efd;
        box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.15);
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
        padding: 12px 28px;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
        font-size: 14px;
    }

    .search-btn {
        background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
        color: white;
    }

    .search-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(13, 110, 253, 0.3);
    }

    .reset-btn {
        background: transparent;
        color: #6c757d;
        border: 2px solid #e9ecef;
    }

    .reset-btn:hover {
        border-color: #0d6efd;
        color: #0d6efd;
    }

    .summary-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
    }

    .summary-card {
        padding: 25px;
        display: flex;
        align-items: center;
        gap: 20px;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        border: 1px solid #e9ecef;
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
        flex-shrink: 0;
    }

    .summary-card.primary .card-icon {
        background: linear-gradient(135deg, #0d6efd, #3d8bfd);
    }

    .summary-card.success .card-icon {
        background: linear-gradient(135deg, #198754, #20c997);
    }

    .summary-card.warning .card-icon {
        background: linear-gradient(135deg, #ffc107, #ffca2c);
    }

    .summary-card.info .card-icon {
        background: linear-gradient(135deg, #0dcaf0, #31d2f2);
    }

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
        font-size: 13px;
        font-weight: 600;
        color: #6c757d;
    }

    .results-header {
        padding: 25px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 2px solid #e9ecef;
        flex-wrap: wrap;
        gap: 15px;
    }

    .results-title {
        font-size: 18px;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 5px;
    }

    .results-subtitle {
        font-size: 13px;
        color: #6c757d;
    }

    .pagination {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .pagination a.btn-pagination {
        text-decoration: none;
        min-width: 40px;
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
        padding: 0 12px;
    }

    .pagination a.btn-pagination:hover {
        border-color: #0d6efd;
        color: #0d6efd;
        background: #f0f7ff;
    }

    .pagination a.btn-pagination.active {
        background: #0d6efd;
        border-color: #0d6efd;
        color: white;
    }

    .pagination a.btn-pagination.disabled {
        pointer-events: none;
        opacity: 0.4;
    }

    .pagination .btn-pagination-dots {
        min-width: 40px;
        height: 40px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #6c757d;
    }

    .table-container {
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
        min-width: 1600px;
    }

    table th {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        padding: 16px 12px;
        text-align: left;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
        white-space: nowrap;
    }

    table td {
        padding: 16px 12px;
        border-bottom: 1px solid #f1f1f1;
        vertical-align: middle;
    }

    table td.wrap-text {
        word-wrap: break-word;
        word-break: break-all;
        white-space: normal;
        max-width: 180px;
        line-height: 1.5;
    }

    table tr:nth-child(even) {
        background: #fafafa;
    }

    table tr:hover {
        background: #f0f7ff;
    }

    .badge {
        display: inline-block;
        padding: 6px 12px;
        font-size: 10px;
        font-weight: 600;
        border-radius: 20px;
        white-space: nowrap;
    }

    .badge-primary {
        background: #0d6efd;
        color: white;
    }

    .badge-success {
        background: #198754;
        color: white;
    }

    .badge-warning {
        background: #ffc107;
        color: #212529;
    }

    .badge-danger {
        background: #dc3545;
        color: white;
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
        color: #0d6efd;
    }

    .empty-state p {
        font-size: 18px;
        margin: 0 0 10px 0;
        font-weight: 600;
    }

    .empty-state small {
        font-size: 14px;
    }

    .table-footer {
        padding: 20px 25px;
        border-top: 2px solid #e9ecef;
        background: #f8f9fa;
        font-size: 13px;
        color: #6c757d;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
    }

    .text-right {
        text-align: right;
    }

    .text-center {
        text-align: center;
    }

    .text-primary {
        color: #0d6efd;
        font-weight: 700;
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

    @media (max-width: 768px) {
        .filter-row {
            grid-template-columns: 1fr !important;
        }
        
        .summary-cards {
            grid-template-columns: 1fr;
        }
        
        .results-header {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>

<div class="container">
    <div class="filter-section">
        <div class="filter-header">
            <i class="fas fa-filter"></i>
            <h3>Filter Data E-Purchasing</h3>
        </div>
        <div class="filter-content">
            <form id="filterForm" method="GET" action="">
                <div class="filter-row" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
                    <div class="filter-group">
                        <label><i class="fas fa-calendar-alt"></i> Tahun Anggaran</label>
                        <select name="tahun_anggaran">
                            <option value="">Semua Tahun</option>
                            <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                                <option value="<?= $y ?>" <?= ($_GET['tahun_anggaran'] ?? '') == $y ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-box"></i> Kode Produk</label>
                        <input type="text" name="kd_produk" placeholder="Masukkan kode produk..." value="<?= htmlspecialchars($_GET['kd_produk'] ?? '') ?>">
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-truck"></i> Kode Penyedia</label>
                        <input type="text" name="kd_penyedia" placeholder="Masukkan kode penyedia..." value="<?= htmlspecialchars($_GET['kd_penyedia'] ?? '') ?>">
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-info-circle"></i> Status Paket</label>
                        <select name="status_paket">
                            <option value="">Semua Status</option>
                            <?php 
                            // Jika API mengembalikan daftar status
                            if (isset($statusList['success']) && $statusList['success'] && !empty($statusList['data'])) {
                                foreach ($statusList['data'] as $statusItem) {
                                    $statusValue = $statusItem['status_paket'] ?? $statusItem;
                                    $selected = ($_GET['status_paket'] ?? '') == $statusValue ? 'selected' : '';
                                    echo '<option value="' . htmlspecialchars($statusValue) . '" ' . $selected . '>' 
                                         . htmlspecialchars(getStatusLabel($statusValue)) 
                                         . '</option>';
                                }
                            } else {
                                // Fallback ke opsi statis jika API tidak tersedia
                                $defaultStatuses = [
                                    'proses_kontrak_ppk' => 'Proses Kontrak PPK',
                                    'melakukan_pengiriman_dan_penerimaan' => 'Melakukan pengiriman dan penerimaan',
                                    'paket_selesai' => 'PaketSelesai'
                                    
                                ];
                                foreach ($defaultStatuses as $value => $label) {
                                    $selected = ($_GET['status_paket'] ?? '') == $value ? 'selected' : '';
                                    echo '<option value="' . htmlspecialchars($value) . '" ' . $selected . '>' 
                                         . htmlspecialchars($label) 
                                         . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-search"></i> Pencarian</label>
                        <div class="search-input-wrapper">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" placeholder="Cari No. Paket atau Nama Paket..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                        </div>
                    </div>
                </div>
                <div class="search-row">
                    <button type="button" class="reset-btn" onclick="window.location.href=window.location.pathname">
                        <i class="fas fa-undo"></i> Reset Filter
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
            <h3>Ringkasan Data E-Purchasing</h3>
        </div>
        <div class="summary-content">
            <div class="summary-cards">
                <div class="summary-card primary">
                    <div class="card-icon"><i class="fas fa-shopping-cart"></i></div>
                    <div class="card-content">
                        <div class="card-value"><?= number_format($totalPaket, 0, ',', '.') ?></div>
                        <div class="card-label">Total Paket</div>
                    </div>
                </div>
                <div class="summary-card success">
                    <div class="card-icon"><i class="fas fa-money-bill-wave"></i></div>
                    <div class="card-content">
                        <div class="card-value"><?= $formattedTotalNilai ?></div>
                        <div class="card-label">Total Nilai Belanja</div>
                    </div>
                </div>
                <div class="summary-card warning">
                    <div class="card-icon"><i class="fas fa-boxes"></i></div>
                    <div class="card-content">
                        <div class="card-value"><?= number_format($totalKuantitas, 0, ',', '.') ?></div>
                        <div class="card-label">Total Kuantitas</div>
                    </div>
                </div>
                <div class="summary-card info">
                    <div class="card-icon"><i class="fas fa-calculator"></i></div>
                    <div class="card-content">
                        <div class="card-value"><?= $formattedRataRataNilai ?></div>
                        <div class="card-label">Rata-rata Nilai</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="results-section">
        <div class="results-header">
            <div>
                <div class="results-title"><i class="fas fa-table"></i> Hasil Data E-Purchasing</div>
                <div class="results-subtitle">
                    Menampilkan <strong><?= count($tableData) ?></strong> dari <strong><?= number_format($totalRecords, 0, ',', '.') ?></strong> total data
                </div>
            </div>
            <div class="pagination">
                <?php
                $paginationParams = $_GET;
                unset($paginationParams['page']);
                $paginationQuery = http_build_query($paginationParams);
                ?>
                <a href="?<?= $paginationQuery ?>&page=<?= max(1, $currentPage - 1) ?>" class="btn-pagination <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <?php 
                $showDotsBefore = false;
                $showDotsAfter = false;
                
                for ($i = 1; $i <= $totalPages; $i++):
                    if ($i == 1 || $i == $totalPages || abs($i - $currentPage) <= 1): ?>
                        <a href="?<?= $paginationQuery ?>&page=<?= $i ?>" class="btn-pagination <?= $i == $currentPage ? 'active' : '' ?>"><?= $i ?></a>
                    <?php elseif ($i < $currentPage && !$showDotsBefore): 
                        $showDotsBefore = true; ?>
                        <span class="btn-pagination-dots">...</span>
                    <?php elseif ($i > $currentPage && !$showDotsAfter):
                        $showDotsAfter = true; ?>
                        <span class="btn-pagination-dots">...</span>
                    <?php endif;
                endfor; ?>
                <a href="?<?= $paginationQuery ?>&page=<?= min($totalPages, $currentPage + 1) ?>" class="btn-pagination <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        </div>

        <?php if (!empty($tableData)) : ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 50px;">NO</th>
                            <th style="width: 140px;">NO. PAKET</th>
                            <th style="width: 250px;">NAMA PAKET</th>
                            <th style="width: 70px;">TAHUN</th>
                            <th style="width: 180px;">KODE ANGGARAN</th>
                            <th style="width: 100px;">KD. PRODUK</th>
                            <th style="width: 100px;">KD. PENYEDIA</th>
                            <th style="width: 70px;" class="text-center">QTY</th>
                            <th style="width: 120px;" class="text-right">HARGA SATUAN</th>
                            <th style="width: 100px;" class="text-right">ONGKIR</th>
                            <th style="width: 130px;" class="text-right">TOTAL</th>
                            <th style="width: 120px;" class="text-center">STATUS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tableData as $row) : 
                            $statusInfo = getStatusBadge($row['status_paket'] ?? 'pending');
                        ?>
                            <tr>
                                <td class="text-center" style="font-weight: 700;">
                                    <?= htmlspecialchars($row['no_urut'] ?? '-') ?>
                                </td>
                                <td>
                                    <i class="fas fa-barcode" style="margin-right: 5px; color: #6c757d;"></i>
                                    <?= htmlspecialchars($row['no_paket'] ?? '-') ?>
                                </td>
                                <td style="font-weight: 500;">
                                    <?= htmlspecialchars($row['nama_paket'] ?? '-') ?>
                                </td>
                                <td class="text-center">
                                    <?= htmlspecialchars($row['tahun_anggaran'] ?? '-') ?>
                                </td>
                                <td class="wrap-text">
                                    <i class="fas fa-money-bill-wave" style="margin-right: 5px; color: #198754;"></i>
                                    <span style="font-size: 11px;"><?= htmlspecialchars($row['kode_anggaran'] ?? '-') ?></span>
                                </td>
                                <td class="text-center">
                                    <i class="fas fa-box" style="margin-right: 5px; color: #0d6efd;"></i>
                                    <?= htmlspecialchars($row['kd_produk'] ?? '-') ?>
                                </td>
                                <td class="text-center">
                                    <i class="fas fa-truck" style="margin-right: 5px; color: #fd7e14;"></i>
                                    <?= htmlspecialchars($row['kd_penyedia'] ?? '-') ?>
                                </td>
                                <td class="text-center" style="font-weight: 600;">
                                    <?= number_format($row['kuantitas'] ?? 0, 0, ',', '.') ?>
                                </td>
                                <td class="text-right">
                                    <?= 'Rp ' . number_format($row['harga_satuan'] ?? 0, 0, ',', '.') ?>
                                </td>
                                <td class="text-right">
                                    <?= 'Rp ' . number_format($row['ongkos_kirim'] ?? 0, 0, ',', '.') ?>
                                </td>
                                <td class="text-right text-primary">
                                    <?= 'Rp ' . number_format($row['total_keseluruhan'] ?? 0, 0, ',', '.') ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?= $statusInfo['class'] ?>">
                                        <?= $statusInfo['label'] ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="table-footer">
                <div><strong>Halaman:</strong> <?= $currentPage ?> dari <?= $totalPages ?></div>
                <div><strong>Total Data:</strong> <?= number_format($totalRecords, 0, ',', '.') ?> paket e-purchasing</div>
            </div>
        <?php else : ?>
            <div class="empty-state">
                <i class="fas fa-shopping-cart"></i>
                <p>Tidak ada data e-purchasing yang ditemukan</p>
                <small class="text-muted">Silakan ubah kriteria filter Anda atau hubungi administrator.</small>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Smooth scroll untuk anchor links
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

        // Auto-hide alert messages
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    });
</script>

<?php
include '../../navbar/footer.php';
?>