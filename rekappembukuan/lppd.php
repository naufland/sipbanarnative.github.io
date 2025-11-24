<?php
// =================================================================
// == HALAMAN LPPD KONTRAK KESELURUHAN =============================
// =================================================================

$page_title = "LPPD - Jumlah Kontrak Keseluruhan";

// URL API
$apiBaseUrl = "http://sipbanarnative.id/api/lppd.php";

// Ambil parameter
$selectedTahun = $_GET['tahun'] ?? date('Y');
$selectedSatker = $_GET['satuan_kerja'] ?? '';

// Ambil daftar satker berdasarkan tahun
$satkerListUrl = $apiBaseUrl . "?action=options&tahun=" . $selectedTahun;
$satkerResponse = @file_get_contents($satkerListUrl);
$satkerData = json_decode($satkerResponse, true);
$satkerList = $satkerData['options']['satuan_kerja'] ?? [];

// Debug: Tampilkan error jika ada
$apiError = '';
if (empty($satkerList) && !empty($satkerResponse)) {
    $apiError = "API Response: " . $satkerResponse;
}

// Ambil data kontrak jika satker sudah dipilih
$kontrakData = [];
$totalKontrak = 0;
$statistik = [];
$provinsi = "KALIMANTAN SELATAN";

if (!empty($selectedSatker)) {
    $dataUrl = $apiBaseUrl . "?action=getData&satuan_kerja=" . urlencode($selectedSatker) . "&tahun=" . $selectedTahun;
    $dataResponse = @file_get_contents($dataUrl);
    $responseData = json_decode($dataResponse, true);
    
    // Debug
    if (empty($responseData)) {
        $apiError = "Data Response: " . $dataResponse;
    }
    
    if ($responseData && ($responseData['success'] ?? false)) {
        $kontrakData = $responseData['data'] ?? [];
        $statistik = $responseData['statistik'] ?? [];
        $totalKontrak = $statistik['total_paket'] ?? 0;
    }
}

include '../navbar/header.php';
?>

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<style>
    body {
        font-family: 'Times New Roman', Times, serif;
        background-color: #f8f9fa;
        padding: 20px;
    }

    .container {
        max-width: 1400px;
        margin: 0 auto;
    }

    .filter-section {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        margin-bottom: 30px;
        padding: 30px;
        border: 1px solid #e9ecef;
    }

    .filter-header {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        padding: 20px 25px;
        border-radius: 10px;
        margin-bottom: 25px;
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
        display: grid;
        grid-template-columns: 1fr 1fr 200px;
        gap: 20px;
        align-items: end;
    }

    .form-group label {
        display: block;
        margin-bottom: 10px;
        font-weight: 600;
        color: #2c3e50;
        font-size: 14px;
    }

    .form-group select {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.3s ease;
    }

    .form-group select:focus {
        outline: none;
        border-color: #3498db;
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.15);
    }

    /* Custom Select2 Styling */
    .select2-container--default .select2-selection--single {
        height: 48px;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        padding: 6px 16px;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 34px;
        padding-left: 0;
        font-size: 14px;
        color: #2c3e50;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 46px;
        right: 10px;
    }

    .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: #3498db;
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.15);
    }

    .select2-dropdown {
        border: 2px solid #e9ecef;
        border-radius: 8px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
    }

    .select2-search--dropdown .select2-search__field {
        border: 2px solid #e9ecef;
        border-radius: 6px;
        padding: 8px 12px;
        font-size: 14px;
    }

    .select2-search--dropdown .select2-search__field:focus {
        border-color: #3498db;
        outline: none;
    }

    .select2-results__option {
        padding: 10px 15px;
        font-size: 14px;
    }

    .select2-results__option--highlighted {
        background-color: #3498db !important;
    }

    .btn-generate {
        background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
        justify-content: center;
        height: 48px;
    }

    .btn-generate:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(52, 152, 219, 0.3);
    }

    .form-container {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        padding: 50px;
        border: 2px solid #2c3e50;
    }

    .form-header {
        display: flex;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 3px solid #000;
    }

    .form-header-logo {
        width: 100px;
        height: 100px;
        margin-right: 20px;
        flex-shrink: 0;
    }

    .form-header-logo img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    .form-header-text {
        flex: 1;
        text-align: center;
    }

    .form-header-text h2 {
        font-size: 18px;
        font-weight: bold;
        margin: 5px 0;
        text-transform: uppercase;
    }

    .form-header-text .subtitle {
        font-size: 14px;
        font-weight: bold;
        margin: 5px 0;
        text-transform: uppercase;
    }

    .form-header-text .address {
        font-size: 10px;
        margin: 5px 0;
        line-height: 1.4;
    }

    .form-title {
        text-align: center;
        margin: 20px 0;
        font-size: 13px;
        font-weight: bold;
        line-height: 1.6;
    }

    .form-table {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
        font-size: 11px;
    }

    .form-table th,
    .form-table td {
        border: 1px solid #000;
        padding: 8px;
        text-align: center;
    }

    .form-table th {
        background-color: #f8f9fa;
        font-weight: bold;
    }

    .form-table td:nth-child(1) { width: 5%; }
    .form-table td:nth-child(2) { width: 15%; }
    .form-table td:nth-child(3) { width: 25%; }
    .form-table td:nth-child(4) { width: 35%; }
    .form-table td:nth-child(5) { width: 10%; }
    .form-table td:nth-child(6) { width: 10%; }

    .form-footer {
        margin-top: 30px;
        font-size: 11px;
        page-break-inside: avoid;
    }

    .footer-note {
        margin: 10px 0;
        font-style: italic;
    }

    .footer-signature {
        margin-top: 40px;
        display: flex;
        justify-content: flex-end;
        page-break-inside: avoid;
    }

    .signature-block {
        text-align: center;
        width: 300px;
    }

    .signature-date {
        margin-bottom: 10px;
        text-align: left;
    }

    .signature-title {
        margin: 10px 0;
        font-weight: bold;
    }

    .signature-ttd {
        margin: 60px 0 20px 0;
        font-weight: bold;
    }

    .signature-name {
        margin: 5px 0;
        font-weight: bold;
    }

    .signature-nip {
        font-size: 10px;
        margin: 3px 0;
    }

    .btn-actions {
        display: flex;
        gap: 15px;
        justify-content: center;
        margin-top: 30px;
        padding-top: 30px;
        border-top: 2px solid #e9ecef;
    }

    .btn-action {
        padding: 12px 30px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .btn-print {
        background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
        color: white;
    }

    .btn-export {
        background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        color: white;
    }

    .btn-action:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #6c757d;
    }

    .empty-state i {
        font-size: 64px;
        margin-bottom: 20px;
        opacity: 0.3;
    }

    .alert-debug {
        background: #fff3cd;
        border: 1px solid #ffc107;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 12px;
        color: #856404;
    }

    @media print {
        body {
            background: white;
            padding: 0;
            margin: 0;
        }

        .filter-section,
        .btn-actions,
        .navbar,
        nav,
        header,
        aside,
        .sidebar,
        .alert-debug {
            display: none !important;
        }

        .form-container {
            box-shadow: none;
            border: none;
            padding: 20px;
            margin: 0;
            page-break-after: avoid;
        }

        .form-table {
            font-size: 10px;
        }

        .form-header {
            border-bottom: 2px solid #000;
        }

        .form-footer {
            display: block !important;
            page-break-inside: avoid;
        }

        .footer-signature {
            display: flex !important;
            page-break-inside: avoid;
        }

        .signature-block {
            display: block !important;
        }

        body > footer,
        body > .footer,
        #footer,
        .site-footer,
        .page-footer {
            display: none !important;
        }
    }
</style>

<div class="container">
    <?php if (!empty($apiError)): ?>
        <div class="alert-debug">
            <strong>Debug Info:</strong><br>
            API URL: <?= htmlspecialchars($satkerListUrl) ?><br>
            <?= htmlspecialchars($apiError) ?>
        </div>
    <?php endif; ?>

    <div class="filter-section">
        <div class="filter-header">
            <i class="fas fa-filter"></i>
            <h3>Filter Data LPPD</h3>
        </div>
        <form method="GET" action="" id="filterForm">
            <div class="filter-content">
                <div class="form-group">
                    <label><i class="fas fa-calendar-alt"></i> Tahun Anggaran</label>
                    <select name="tahun" id="selectTahun">
                        <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                            <option value="<?= $y ?>" <?= $selectedTahun == $y ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-building"></i> Satuan Kerja</label>
                    <select name="satuan_kerja" id="selectSatker" class="select2-satker">
                        <option value="">-- Pilih Satuan Kerja --</option>
                        <?php foreach ($satkerList as $satker): ?>
                            <option value="<?= htmlspecialchars($satker) ?>" 
                                    <?= $selectedSatker == $satker ? 'selected' : '' ?>>
                                <?= htmlspecialchars($satker) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn-generate">
                    <i class="fas fa-file-alt"></i> Generate Form
                </button>
            </div>
        </form>
    </div>

    <?php if (!empty($selectedSatker) && !empty($kontrakData)): ?>
        <div class="form-container" id="printableForm">
            <div class="form-header">
                <div class="form-header-logo">
                    <img src="../images/logobjm.png" alt="Logo Banjarmasin" onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%22 height=%22100%22%3E%3Crect fill=%22%23ddd%22 width=%22100%22 height=%22100%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 font-size=%2212%22 text-anchor=%22middle%22 dy=%22.3em%22%3ELogo%3C/text%3E%3C/svg%3E';">
                </div>
                <div class="form-header-text">
                    <h2>PEMERINTAH KOTA BANJARMASIN</h2>
                    <div class="subtitle">SEKRETARIAT DAERAH</div>
                    <div class="address">
                        Jl. RE. Martadinata No.1 Banjarmasin 70111 - (0511) 4368142 - 4368145 Fax. 3353933<br>
                        http://www.banjarmසinkota.go.id/
                    </div>
                </div>
            </div>

            <div class="form-title">
                <strong>JUMLAH KONTRAK KESELURUHAN TAHUN <?= $selectedTahun ?></strong><br>
                DI PROVINSI <?= strtoupper($provinsi) ?><br>
                TAHUN <?= $selectedTahun ?>
            </div>

            <table class="form-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kode RUP</th>
                        <th>Perangkat Daerah</th>
                        <th>Nama Paket</th>
                        <th>HPS</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    foreach ($kontrakData as $row): 
                    ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($row['Kode_RUP'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['Nama_Satker'] ?? '-') ?></td>
                            <td style="text-align: left; padding-left: 10px;">
                                <?= htmlspecialchars($row['Nama_Paket'] ?? '-') ?>
                            </td>
                            <td style="text-align: right; padding-right: 10px;">
                                Rp <?= number_format($row['Nilai_HPS'] ?? 0, 0, ',', '.') ?>
                            </td>
                            <td>Selesai</td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td colspan="6" style="text-align: left; padding: 10px;">
                            <strong>Dst.</strong>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div class="form-footer">
                <div class="footer-note">
                    <strong>Sumber Data:</strong> Biro Pengadaan Barang/Jasa Provinsi <?= $provinsi ?>
                </div>

                <div class="footer-signature">
                    <div class="signature-block">
                        <div class="signature-date">Banjarmasin, ............. <?= $selectedTahun ?></div>
                        <div class="signature-title">Kepala Biro Pengadaan Barang/Jasa</div>
                        <div class="signature-title">Provinsi <?= $provinsi ?></div>
                        <div class="signature-ttd">Ttd dan cap</div>
                        <div class="signature-name">( .................................. )</div>
                        <div class="signature-nip">Pangkat/Gol Ruang .....</div>
                        <div class="signature-nip">NIP. .....</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="btn-actions">
            <button class="btn-action btn-print" onclick="printForm()">
                <i class="fas fa-print"></i> Cetak Form
            </button>
            <button class="btn-action btn-export" onclick="exportPDF()">
                <i class="fas fa-file-pdf"></i> Export PDF
            </button>
        </div>
    <?php elseif (!empty($selectedSatker)): ?>
        <div class="form-container">
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p><strong>Tidak ada data kontrak</strong></p>
                <small>Untuk <?= htmlspecialchars($selectedSatker) ?> tahun <?= $selectedTahun ?></small>
            </div>
        </div>
    <?php else: ?>
        <div class="form-container">
            <div class="empty-state">
                <i class="fas fa-hand-pointer"></i>
                <p><strong>Silakan pilih Tahun dan Satuan Kerja</strong></p>
                <small>untuk menampilkan form LPPD</small>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- jQuery (required for Select2) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    function printForm() {
        window.print();
    }

    function exportPDF() {
        alert('Fitur export PDF sedang dalam pengembangan.\nSementara gunakan fungsi Print > Save as PDF dari browser.');
    }

    function initializeEventListeners() {
        // Initialize Select2 untuk Satuan Kerja
        $('#selectSatker').select2({
            placeholder: '-- Ketik atau pilih Satuan Kerja --',
            allowClear: true,
            width: '100%',
            language: {
                noResults: function() {
                    return "Tidak ada hasil yang cocok";
                },
                searching: function() {
                    return "Mencari...";
                },
                inputTooShort: function() {
                    return "Ketik untuk mencari...";
                }
            }
        });

        // Auto reload saat memilih tahun untuk update dropdown satker
        const selectTahun = document.getElementById('selectTahun');
        if (selectTahun) {
            selectTahun.addEventListener('change', function() {
                window.location.href = '?tahun=' + this.value;
            });
        }

        // Auto submit saat memilih satker
        $('#selectSatker').on('select2:select', function(e) {
            const tahun = document.getElementById('selectTahun').value;
            const satker = this.value;
            
            if (tahun && satker) {
                document.getElementById('filterForm').submit();
            }
        });
    }

    // Initialize on page load
    $(document).ready(function() {
        initializeEventListeners();
    });

    // Debug: Log saat halaman dimuat
    console.log('Selected Tahun:', '<?= $selectedTahun ?>');
    console.log('Selected Satker:', '<?= $selectedSatker ?>');
    console.log('Satker List Count:', <?= count($satkerList) ?>);
    console.log('Kontrak Data Count:', <?= count($kontrakData) ?>);
</script>

<?php include '../navbar/footer.php'; ?>