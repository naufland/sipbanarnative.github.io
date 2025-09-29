<?php
// =================================================================
// == LOGIC BLOCK REFACTORED TO MATCH PENGADAAN.PHP ================
// =================================================================

// 1. SETUP API URLS
$apiBaseUrl = "http://sipbanar-phpnative.id/api/swakelola.php";

// 2. GET PARAMETERS FROM URL
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = $_GET['limit'] ?? 25; // Default limit 25 data

// 3. PREPARE QUERY PARAMS FOR MAIN DATA (TABLE)
$queryParams = $_GET;
$queryParams['page'] = $currentPage;
$queryParams['limit'] = $limit;
$queryParams['action'] = 'list'; // Explicitly ask for list data

// Remove empty/null parameters to keep the URL clean
$queryParams = array_filter($queryParams, fn($value) => $value !== '' && $value !== null);
$queryString = http_build_query($queryParams);
$apiUrl = $apiBaseUrl . '?' . $queryString;

// 4. PREPARE QUERY PARAMS FOR SUMMARY DATA (STATISTICS)
$summaryParams = $queryParams;
unset($summaryParams['page'], $summaryParams['limit']); // Summary doesn't need pagination
$summaryParams['action'] = 'summary'; // Tell the API we only want stats

$summaryQueryString = http_build_query($summaryParams);
$apiSummaryUrl = $apiBaseUrl . '?' . $summaryQueryString;

// 5. API CALL FUNCTION (Using cURL for robustness)
function makeApiRequest($url, $timeout = 30)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    // Add other cURL options if needed
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    if ($error) {
        // Fallback to file_get_contents on cURL error
        $response = @file_get_contents($url);
    }
    return json_decode($response, true);
}

// 6. EXECUTE API CALLS
$data = makeApiRequest($apiUrl);
$summaryData = makeApiRequest($apiSummaryUrl);

// 7. INITIALIZE VARIABLES TO PREVENT ERRORS
$totalPaket = 0;
$totalPagu = 0;
$avgPagu = 0;
$klpdCount = 0;
$formattedTotalPagu = 'Rp 0';
$formattedAvgPagu = 'Rp 0';

// 8. PROCESS SUMMARY DATA
if ($summaryData && ($summaryData['success'] ?? false) && isset($summaryData['summary'])) {
    $summary = $summaryData['summary'];

    $totalPaket = $summary['total_paket'] ?? 0;
    $totalPagu = $summary['total_pagu'] ?? 0;
    $avgPagu = $summary['avg_pagu'] ?? 0;
    $klpdCount = $summary['total_klpd'] ?? 0;

    // Format numbers for display
    $formattedTotalPagu = 'Rp ' . number_format($totalPagu, 0, ',', '.');
    $formattedAvgPagu = 'Rp ' . number_format($avgPagu, 0, ',', '.');
}

// 9. PREPARE PAGINATION VARIABLES
$totalPages = $data['pagination']['total_pages'] ?? 1;
$totalRecords = $data['pagination']['total_records'] ?? $totalPaket; // Use summary total if available

// 10. DYNAMICALLY GET OPTIONS FOR DROPDOWNS (from API response if available)
$options = [
    'tipe_swakelola' => $data['options']['jenis_pengadaan'] ?? [],
    'klpd' => $data['options']['klpd'] ?? [],
    'satuan_kerja' => $data['options']['satuan_kerja'] ?? []
];

// Set page title for header
$page_title = "Data Swakelola - SIP BANAR";

// Include header
include '../../navbar/header.php';

// =================================================================
// == END OF REFACTORED LOGIC BLOCK ================================
// =================================================================
?>

<script src="../../js/submenu.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

<style>
    /* Paste the entire CSS from your pengadaan.php file here */
    /* This ensures the visual style is identical */
    /* ... (omitted for brevity, but you should copy it here) ... */
    .container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 20px;
    }

    .filter-section,
    .summary-section,
    .results-section {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        margin-bottom: 30px;
        overflow: hidden;
        border: 1px solid #e9ecef;
        animation: fadeInUp 0.6s ease-out;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .filter-header {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
        padding: 20px 25px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .filter-header h3 {
        margin: 0;
        font-size: 18px;
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
        grid-template-columns: 2fr 1fr;
    }

    .filter-row:nth-child(2) {
        grid-template-columns: 1fr 1fr 1fr;
    }

    .filter-row:nth-child(3) {
        grid-template-columns: 2fr 1fr;
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
        padding: 14px 16px;
        border: 2px solid #e9ecef;
        border-radius: 10px;
        font-size: 14px;
    }

    .date-range-container {
        display: grid;
        grid-template-columns: 1fr auto 1fr;
        align-items: center;
        gap: 15px;
        background: #f8f9fa;
        padding: 8px 20px;
        border-radius: 10px;
        border: 2px solid #e9ecef;
    }

    .date-range-container input[type="date"] {
        border: none;
        background: transparent;
        padding: 10px 12px;
    }

    .date-separator {
        color: #dc3545;
        font-weight: 700;
    }

    .pagu-range-container {
        display: grid;
        grid-template-columns: 1fr auto 1fr;
        align-items: center;
        gap: 10px;
        background: #f8f9fa;
        padding: 8px 15px;
        border-radius: 10px;
        border: 2px solid #e9ecef;
    }

    .pagu-range-container input[type="text"] {
        border: none;
        background: transparent;
        padding: 8px 10px;
        text-align: center;
    }

    .pagu-separator {
        color: #dc3545;
        font-weight: 600;
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
        padding: 14px 30px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
    }

    .search-btn {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
        border: none;
    }

    .reset-btn {
        background: transparent;
        color: #6c757d;
        border: 2px solid #e9ecef;
    }

    .summary-header {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
        padding: 20px 25px;
        display: flex;
        align-items: center;
        gap: 12px;
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
        position: relative;
    }

    .summary-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
    }

    .summary-card.primary::before {
        background: #3498db;
    }

    .summary-card.success::before {
        background: #27ae60;
    }

    .summary-card.info::before {
        background: #17a2b8;
    }

    .summary-card.warning::before {
        background: #f39c12;
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
        background: linear-gradient(135deg, #3498db, #5dade2);
    }

    .summary-card.success .card-icon {
        background: linear-gradient(135deg, #27ae60, #58d68d);
    }

    .summary-card.info .card-icon {
        background: linear-gradient(135deg, #17a2b8, #5dccda);
    }

    .summary-card.warning .card-icon {
        background: linear-gradient(135deg, #f39c12, #f8c471);
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

    .results-header {
        background: #f8f9fa;
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

    .pagination a {
        text-decoration: none;
        width: 40px;
        height: 40px;
        border: 2px solid #e9ecef;
        background: white;
        border-radius: 8px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        color: #6c757d;
    }

    .pagination a.active {
        background: #dc3545;
        border-color: #dc3545;
        color: white;
    }

    .pagination a.disabled {
        pointer-events: none;
        opacity: 0.6;
    }

    .table-container {
        overflow-x: auto;
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
        border-bottom: 3px solid #dc3545;
    }

    table td {
        padding: 18px 15px;
        border-bottom: 1px solid #f1f1f1;
        vertical-align: top;
    }

    table tr:hover {
        background: #f8f9fa;
    }

    .price {
        font-weight: 700;
        color: #27ae60;
        white-space: nowrap;
    }

    .badge {
        display: inline-block;
        padding: 6px 12px;
        font-size: 11px;
        font-weight: 600;
        border-radius: 20px;
        color: white;
    }

    .badge-success {
        background: #28a745;
    }

    .empty-state {
        text-align: center;
        padding: 60px 40px;
    }

    .table-footer {
        padding: 20px 25px;
        border-top: 2px solid #e9ecef;
        background: #f8f9fa;
        display: flex;
        justify-content: space-between;
    }

    @media (max-width: 992px) {

        .filter-row:nth-child(1),
        .filter-row:nth-child(2),
        .filter-row:nth-child(3) {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="container">
    <div class="filter-section">
        <div class="filter-header">
            <i class="fas fa-filter"></i>
            <h3>Filter Data Swakelola</h3>
        </div>
        <div class="filter-content">
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="date-range-group">
                        <label><i class="fas fa-calendar-alt"></i> Periode Tanggal</label>
                        <div class="date-range-container">
                            <input type="date" name="tanggal_awal" value="<?= htmlspecialchars($_GET['tanggal_awal'] ?? '') ?>">
                            <span class="date-separator">S/D</span>
                            <input type="date" name="tanggal_akhir" value="<?= htmlspecialchars($_GET['tanggal_akhir'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-tools"></i> Tipe Swakelola</label>
                        <select name="tipe_swakelola">
                            <option value="">Semua Tipe</option>
                            <?php foreach ($options['tipe_swakelola'] as $tipe): ?>
                                <option value="<?= htmlspecialchars($tipe) ?>" <?= ($_GET['tipe_swakelola'] ?? '') == $tipe ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($tipe) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="filter-row">
                    <div class="filter-group">
                        <label><i class="fas fa-building"></i> KLPD</label>
                        <select name="klpd">
                            <option value="">Semua KLPD</option>
                            <?php foreach ($options['klpd'] as $klpd): ?>
                                <option value="<?= htmlspecialchars($klpd) ?>" <?= ($_GET['klpd'] ?? '') == $klpd ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($klpd) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-sitemap"></i> Satuan Kerja</label>
                        <select name="satuan_kerja">
                            <option value="">Semua Satuan Kerja</option>
                            <?php foreach ($options['satuan_kerja'] as $satker): ?>
                                <option value="<?= htmlspecialchars($satker) ?>" <?= ($_GET['satuan_kerja'] ?? '') == $satker ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($satker) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-money-bill-wave"></i> Range Pagu (Rp)</label>
                        <div class="pagu-range-container">
                            <input type="text" name="pagu_min" placeholder="Min Pagu" value="<?= htmlspecialchars($_GET['pagu_min'] ?? '') ?>">
                            <span class="pagu-separator">S/D</span>
                            <input type="text" name="pagu_max" placeholder="Max Pagu" value="<?= htmlspecialchars($_GET['pagu_max'] ?? '') ?>">
                        </div>
                    </div>
                </div>
                <div class="filter-row">
                    <div class="filter-group">
                        <label><i class="fas fa-search"></i> Pencarian Paket</label>
                        <div class="search-input-wrapper">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" placeholder="Cari nama paket..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-list"></i> Limit Data</label>
                        <select name="limit">
                            <option value="10" <?= ($limit == 10) ? 'selected' : '' ?>>10 Data</option>
                            <option value="25" <?= ($limit == 25) ? 'selected' : '' ?>>25 Data</option>
                            <option value="50" <?= ($limit == 50) ? 'selected' : '' ?>>50 Data</option>
                            <option value="100" <?= ($limit == 100) ? 'selected' : '' ?>>100 Data</option>
                        </select>
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
            <h3>Ringkasan Data Swakelola</h3>
        </div>
        <div class="summary-content">
            <div class="summary-cards">
                <div class="summary-card primary">
                    <div class="card-icon"><i class="fas fa-boxes"></i></div>
                    <div class="card-content">
                        <div class="card-value"><?= number_format($totalPaket, 0, ',', '.') ?></div>
                        <div class="card-label">Total Paket</div>
                        <div class="card-subtitle">Swakelola</div>
                    </div>
                </div>
                <div class="summary-card success">
                    <div class="card-icon"><i class="fas fa-money-bill-wave"></i></div>
                    <div class="card-content">
                        <div class="card-value"><?= $formattedTotalPagu ?></div>
                        <div class="card-label">Total Pagu</div>
                        <div class="card-subtitle">Keseluruhan</div>
                    </div>
                </div>
                <div class="summary-card info">
                    <div class="card-icon"><i class="fas fa-calculator"></i></div>
                    <div class="card-content">
                        <div class="card-value"><?= $formattedAvgPagu ?></div>
                        <div class="card-label">Rata-rata Pagu</div>
                        <div class="card-subtitle">Per Paket</div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="results-section">
        <div class="results-header">
            <div>
                <div class="results-title"><i class="fas fa-table"></i> Hasil Pencarian Data Swakelola</div>
                <?php if ($data && ($data['success'] ?? false)) : ?>
                    <div class="results-subtitle">
                        <strong>Menampilkan <?= count($data['data']) ?> dari <?= number_format($totalRecords, 0, ',', '.') ?> total data</strong>
                    </div>
                <?php endif; ?>
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
                <?php for ($i = 1; $i <= $totalPages; $i++) : ?>
                    <?php if ($i == $currentPage) : ?>
                        <a href="#" class="btn-pagination active"><?= $i ?></a>
                    <?php elseif (abs($i - $currentPage) < 3 || $i <= 2 || $i > $totalPages - 2) : ?>
                        <a href="?<?= $paginationQuery ?>&page=<?= $i ?>" class="btn-pagination"><?= $i ?></a>
                    <?php elseif ($i == $currentPage - 3 || $i == $currentPage + 3) : ?>
                        <span class="btn-pagination-dots">...</span>
                    <?php endif; ?>
                <?php endfor; ?>
                <a href="?<?= $paginationQuery ?>&page=<?= min($totalPages, $currentPage + 1) ?>" class="btn-pagination <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        </div>

        <div class="table-container">
            <?php if ($data && ($data['success'] ?? false) && count($data['data']) > 0) : ?>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 300px;">Paket Swakelola</th>
                            <th style="width: 140px;">Pagu (Rp)</th>
                            <th style="width: 140px;">Tipe Swakelola</th>
                            <th style="width: 120px;">Pemilihan</th>
                            <th style="width: 140px;">KLPD</th>
                            <th style="width: 200px;">Satuan Kerja</th>
                            <th style="width: 150px;">Lokasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['data'] as $row) : ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 700; color: #2c3e50;"><?= htmlspecialchars($row['Paket'] ?? 'N/A') ?></div>
                                    <small class="text-muted">ID: <?= htmlspecialchars($row['ID'] ?? 'N/A') ?></small>
                                </td>
                                <td class="price">
                                    <?= 'Rp ' . number_format($row['Pagu_Rp'] ?? 0, 0, ',', '.') ?>
                                </td>
                                <td><span class="badge badge-success"><?= htmlspecialchars($row['Tipe_Swakelola'] ?? 'N/A') ?></span></td>
                                <td><small><?= htmlspecialchars($row['Pemilihan'] ?? 'N/A') ?></small></td>
                                <td><small><?= htmlspecialchars($row['KLPD'] ?? 'N/A') ?></small></td>
                                <td><small><?= htmlspecialchars($row['Satuan_Kerja'] ?? 'N/A') ?></small></td>
                                <td><small><?= htmlspecialchars($row['Lokasi'] ?? 'N/A') ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <div class="empty-state">
                    <i class="fas fa-search-minus"></i>
                    <p><strong>Tidak ada data swakelola yang ditemukan.</strong></p>
                    <small class="text-muted">Coba ubah kriteria pencarian atau filter yang Anda gunakan.</small>
                </div>
            <?php endif; ?>
        </div>

        <div class="table-footer">
            <div>
                <strong><i class="fas fa-info-circle"></i> Informasi Halaman:</strong>
                Halaman <?= $currentPage ?> dari <?= $totalPages ?>
            </div>
            <div>
                <strong>Total Data: <?= number_format($totalRecords, 0, ',', '.') ?></strong> swakelola
            </div>
        </div>
    </div>
</div>

<script>
    // You can keep your enhanced JavaScript from the original swakelola.php here.
    // The main logic is now handled by PHP, so the JS can focus on UI enhancements.

    // =================================================================
    // == JAVASCRIPT LENGKAP UNTUK HALAMAN SWAKELOLA ===================
    // =================================================================

    document.addEventListener('DOMContentLoaded', function() {

        // --- VALIDASI DAN INTERAKSI FORM FILTER ---

        // 1. Validasi Rentang Tanggal (Date Range)
        const tanggalAwal = document.querySelector('input[name="tanggal_awal"]');
        const tanggalAkhir = document.querySelector('input[name="tanggal_akhir"]');

        if (tanggalAwal && tanggalAkhir) {
            tanggalAwal.addEventListener('change', function() {
                // Tanggal akhir tidak boleh sebelum tanggal awal
                tanggalAkhir.min = this.value;
                if (tanggalAkhir.value && tanggalAkhir.value < this.value) {
                    tanggalAkhir.value = this.value;
                }
            });
            tanggalAkhir.addEventListener('change', function() {
                // Tanggal awal tidak boleh setelah tanggal akhir
                tanggalAwal.max = this.value;
                if (tanggalAwal.value && tanggalAwal.value > this.value) {
                    tanggalAwal.value = this.value;
                }
            });
        }

        // 2. Validasi Rentang Pagu (Pagu Range)
        const paguMinInput = document.querySelector('input[name="pagu_min"]');
        const paguMaxInput = document.querySelector('input[name="pagu_max"]');

        if (paguMinInput && paguMaxInput) {
            const validatePagu = () => {
                const minVal = parseInt(paguMinInput.value.replace(/\./g, '')) || 0;
                const maxVal = parseInt(paguMaxInput.value.replace(/\./g, '')) || 0;

                if (minVal > 0 && maxVal > 0 && minVal > maxVal) {
                    paguMaxInput.style.borderColor = '#dc3545';
                    paguMaxInput.title = 'Pagu maks tidak boleh lebih kecil dari pagu min';
                } else {
                    paguMaxInput.style.borderColor = '';
                    paguMaxInput.title = '';
                }
            };
            paguMinInput.addEventListener('input', validatePagu);
            paguMaxInput.addEventListener('input', validatePagu);
        }

        // 3. Interaksi Input Pencarian (Search Input)
        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput) {
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault(); // Mencegah submit ganda
                    this.form.submit();
                }
            });
        }

        // 4. Penanganan Form Saat Submit
        const filterForm = document.querySelector('form');
        if (filterForm) {
            filterForm.addEventListener('submit', function(e) {
                // Hapus format titik dari input pagu sebelum submit
                if (paguMinInput) paguMinInput.value = paguMinInput.value.replace(/\./g, '');
                if (paguMaxInput) paguMaxInput.value = paguMaxInput.value.replace(/\./g, '');

                // Tampilkan status loading pada tombol
                const submitBtn = this.querySelector('.search-btn');
                if (submitBtn) {
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mencari...';
                    submitBtn.disabled = true;
                }
            });
        }

        // --- EFEK VISUAL DAN INTERAKSI HALAMAN ---

        // 5. Gulir Otomatis ke Hasil Pencarian (Auto-scroll)
        const urlParams = new URLSearchParams(window.location.search);
        // Cek jika ada parameter filter selain 'page'
        if (Array.from(urlParams.keys()).some(key => key !== 'page')) {
            const resultsSection = document.querySelector('.results-section');
            if (resultsSection) {
                resultsSection.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }

        // 6. Navigasi Paginasi dengan Keyboard (CTRL + Panah Kiri/Kanan)
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey) {
                if (e.key === 'ArrowLeft') {
                    const prevButton = document.querySelector('.pagination a:first-child:not(.disabled)');
                    if (prevButton) prevButton.click();
                } else if (e.key === 'ArrowRight') {
                    const nextButton = document.querySelector('.pagination a:last-child:not(.disabled)');
                    if (nextButton) nextButton.click();
                }
            }
        });

    });

    // --- FUNGSI BANTUAN (HELPER FUNCTIONS) ---

    /**
     * Fungsi untuk mereset semua filter dan kembali ke tampilan awal.
     * Ini adalah cara paling bersih karena me-reload halaman tanpa parameter.
     */
    function resetForm() {
        window.location.href = window.location.pathname;
    }

    /**
     * (Opsional) Fungsi untuk mengekspor data berdasarkan filter saat ini.
     * Membutuhkan penyesuaian di sisi API (backend) untuk menangani parameter 'export=csv'.
     */
    function exportData(format = 'csv') {
        const url = new URL(window.location);
        url.searchParams.set('export', format);
        url.searchParams.delete('page'); // Ekspor semua data, bukan hanya satu halaman
        url.searchParams.set('limit', 10000); // Set limit tinggi untuk ekspor

        // Buka di tab baru
        window.open(url.toString(), '_blank');
    }

    /**
     * (Opsional) Fungsi untuk mencetak tabel hasil pencarian.
     */
    function printResults() {
        const tableHtml = document.querySelector('.table-container').innerHTML;
        const subtitleHtml = document.querySelector('.results-subtitle').innerHTML;

        const printWindow = window.open('', '', 'height=600,width=800');
        printWindow.document.write('<html><head><title>Cetak Data Swakelola</title>');
        printWindow.document.write(`
        <style>
            body { font-family: Arial, sans-serif; }
            table { width: 100%; border-collapse: collapse; font-size: 10px; }
            th, td { border: 1px solid #ccc; padding: 5px; text-align: left; }
            th { background-color: #f2f2f2; }
            h2, p { text-align: center; }
        </style>
    `);
        printWindow.document.write('</head><body>');
        printWindow.document.write('<h2>Laporan Data Swakelola</h2>');
        printWindow.document.write(`<p>${subtitleHtml}</p>`);
        printWindow.document.write(tableHtml);
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
    }
    document.addEventListener('DOMContentLoaded', function() {
        // Example: Client-side validation for date range
        const tanggalAwal = document.querySelector('input[name="tanggal_awal"]');
        const tanggalAkhir = document.querySelector('input[name="tanggal_akhir"]');

        if (tanggalAwal && tanggalAkhir) {
            tanggalAwal.addEventListener('change', function() {
                tanggalAkhir.min = this.value;
            });
        }

        // Example: Add loading indicator on form submit
        document.querySelector('form').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('.search-btn');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mencari...';
            submitBtn.disabled = true;
        });
    });
</script>

<?php
// Include footer
include '../../navbar/footer.php';
?>