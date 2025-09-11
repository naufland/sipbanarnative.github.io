<?php
// URL API (ganti sesuai lokasi file php API kamu)
$apiUrl = "http://sipbanar-phpnative.id/api/pengadaan.php";

// Ambil data dari API
$response = file_get_contents($apiUrl);
$data = json_decode($response, true);

// Set page title untuk header
$page_title = "Data Pengadaan - SIP BANAR";

// Include header
include '../../navbar/header.php';
?>

<!-- Custom CSS untuk halaman ini -->
<style>
    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    /* Filter Section Styles */
    .filter-section {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        margin-bottom: 30px;
        overflow: hidden;
    }

    .filter-header {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .filter-header i {
        font-size: 20px;
    }

    .filter-header h3 {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
    }

    .filter-content {
        padding: 25px;
    }

    .filter-row {
        display: flex;
        gap: 20px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .filter-group {
        flex: 1;
        min-width: 200px;
    }

    .filter-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #2c3e50;
        font-size: 14px;
    }

    .filter-group select,
    .filter-group input {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        font-size: 14px;
        background: white;
        transition: all 0.3s ease;
    }

    .filter-group select:focus,
    .filter-group input:focus {
        outline: none;
        border-color: #dc3545;
        box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
    }

    .search-row {
        display: flex;
        justify-content: flex-end;
        padding-top: 15px;
        border-top: 1px solid #e9ecef;
    }

    .search-btn {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
    }

    .search-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
    }

    /* Results Section Styles */
    .results-section {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        overflow: hidden;
    }

    .results-header {
        background: #f8f9fa;
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #e9ecef;
    }

    .results-title {
        font-size: 18px;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 5px;
    }

    .results-subtitle {
        font-size: 14px;
        color: #6c757d;
    }

    .pagination {
        display: flex;
        gap: 5px;
    }

    .pagination button {
        width: 40px;
        height: 40px;
        border: 2px solid #e9ecef;
        background: white;
        border-radius: 8px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        color: #6c757d;
        transition: all 0.3s ease;
    }

    .pagination button:hover {
        border-color: #dc3545;
        color: #dc3545;
    }

    .pagination button.active {
        background: #dc3545;
        border-color: #dc3545;
        color: white;
    }

    /* Table Styles */
    .table-container {
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
    }

    table th {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        padding: 15px 12px;
        text-align: left;
        font-weight: 600;
        border-bottom: 3px solid #dc3545;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    table th:first-child {
        border-top-left-radius: 0;
    }

    table th:last-child {
        border-top-right-radius: 0;
    }

    table td {
        padding: 15px 12px;
        border-bottom: 1px solid #f1f1f1;
        vertical-align: top;
    }

    table tr {
        transition: all 0.3s ease;
    }

    table tr:hover {
        background: #f8f9fa;
    }

    table tr:nth-child(even) {
        background: #fafafa;
    }

    table tr:nth-child(even):hover {
        background: #f0f0f0;
    }

    /* Badge Styles */
    .badge {
        display: inline-block;
        padding: 6px 12px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-radius: 20px;
        white-space: nowrap;
    }

    .badge-primary {
        background: #3498db;
        color: white;
    }

    .badge-success {
        background: #27ae60;
        color: white;
    }

    .badge-warning {
        background: #f39c12;
        color: white;
    }

    .badge-danger {
        background: #e74c3c;
        color: white;
    }

    /* Price Formatting */
    .price {
        font-weight: 700;
        color: #27ae60;
        white-space: nowrap;
    }

    /* Small Text */
    .small-text {
        font-size: 12px;
        color: #6c757d;
        margin-top: 3px;
    }

    .text-muted {
        color: #6c757d;
    }

    /* Empty State */
    .empty-state {
        padding: 60px 40px;
        text-align: center;
        color: #6c757d;
    }

    .empty-state i {
        font-size: 64px;
        margin-bottom: 20px;
        opacity: 0.3;
        color: #dc3545;
    }

    .empty-state p {
        font-size: 18px;
        margin: 0;
    }

    /* Loading State */
    .loading {
        text-align: center;
        padding: 40px;
    }

    .loading i {
        font-size: 32px;
        color: #dc3545;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* Footer Info */
    .table-footer {
        padding: 15px 20px;
        border-top: 1px solid #e9ecef;
        background: #f8f9fa;
        font-size: 13px;
        color: #6c757d;
    }

    /* Responsive Design */
    @media (max-width: 992px) {
        .filter-row {
            flex-direction: column;
        }

        .filter-group {
            min-width: 100%;
        }

        .results-header {
            flex-direction: column;
            gap: 15px;
            align-items: flex-start;
        }

        .table-container {
            border-radius: 0;
        }

        table {
            min-width: 800px;
        }
    }

    @media (max-width: 768px) {
        .container {
            padding: 15px;
        }

        .filter-content {
            padding: 20px 15px;
        }

        .search-row {
            justify-content: center;
        }

        .search-btn {
            width: 100%;
            justify-content: center;
        }

        table th,
        table td {
            padding: 10px 8px;
        }
    }

    /* Animation */
    .filter-section,
    .results-section {
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
</style>

<div class="container">
    <!-- Filter Section -->
    <div class="filter-section">
        <div class="filter-header">
            <i class="fas fa-filter"></i>
            <h3>Filter Data Pengadaan</h3>
        </div>
        <div class="filter-content">
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Tahun</label>
                        <select name="tahun">
                            <option value="2025">2025</option>
                            <option value="2024" selected>2024</option>
                            <option value="2023">2023</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Bulan Awal</label>
                        <select name="bulan_awal">
                            <option value="">Pilih Bulan</option>
                            <option value="01">Januari</option>
                            <option value="02">Februari</option>
                            <option value="03">Maret</option>
                            <option value="04">April</option>
                            <option value="05">Mei</option>
                            <option value="06">Juni</option>
                            <option value="07">Juli</option>
                            <option value="08">Agustus</option>
                            <option value="09">September</option>
                            <option value="10">Oktober</option>
                            <option value="11">November</option>
                            <option value="12">Desember</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Bulan Akhir</label>
                        <select name="bulan_akhir">
                            <option value="">Pilih Bulan</option>
                            <option value="01">Januari</option>
                            <option value="02">Februari</option>
                            <option value="03">Maret</option>
                            <option value="04">April</option>
                            <option value="05">Mei</option>
                            <option value="06">Juni</option>
                            <option value="07">Juli</option>
                            <option value="08">Agustus</option>
                            <option value="09">September</option>
                            <option value="10">Oktober</option>
                            <option value="11">November</option>
                            <option value="12">Desember</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Jenis Pengadaan</label>
                        <select name="jenis_pengadaan">
                            <option value="">Semua Jenis</option>
                            <option value="Jasa Lainnya">Jasa Lainnya</option>
                            <option value="Pengadaan Langsung">Pengadaan Langsung</option>
                            <option value="Barang">Barang</option>
                            <option value="Konstruksi">Konstruksi</option>
                        </select>
                    </div>
                </div>
                <div class="filter-row">
                    <div class="filter-group">
                        <label>KLPD</label>
                        <select name="klpd">
                            <option value="">Semua KLPD</option>
                            <option value="Kota Banjarmasin">Kota Banjarmasin</option>
                            <option value="Kabupaten Banjar">Kabupaten Banjar</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Pencarian Paket</label>
                        <input type="text" name="search" placeholder="Cari nama paket..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    </div>
                    <div class="filter-group">
                        <label>Limit Data</label>
                        <select name="limit">
                            <option value="10">10 Data</option>
                            <option value="25" selected>25 Data</option>
                            <option value="50">50 Data</option>
                            <option value="100">100 Data</option>
                        </select>
                    </div>
                </div>
                <div class="search-row">
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i>
                        Cari Data
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Results Section -->
    <div class="results-section">
        <div class="results-header">
            <div>
                <div class="results-title">Hasil Pencarian Data Pengadaan</div>
                <?php if ($data && isset($data['success']) && $data['success']): ?>
                <div class="results-subtitle">
                    Menampilkan <?= count($data['data']) ?> data pengadaan
                </div>
                <?php endif; ?>
            </div>
            <div class="pagination">
                <button title="Halaman Sebelumnya"><i class="fas fa-chevron-left"></i></button>
                <button class="active">1</button>
                <button>2</button>
                <button>3</button>
                <button title="Halaman Selanjutnya"><i class="fas fa-chevron-right"></i></button>
            </div>
        </div>

        <?php if ($data && isset($data['success']) && $data['success'] && count($data['data']) > 0): ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
    
                        <th style="width: 250px;">Paket Pengadaan</th>
                        <th style="width: 130px;">Pagu (Rp)</th>
                        <th style="width: 120px;">Jenis Pengadaan</th>
                        <th style="width: 100px;">Usaha Kecil</th>
                        <th style="width: 120px;">Metode</th>
                        <th style="width: 100px;">Pemilihan</th>
                        <th style="width: 120px;">KLPD</th>
                        <th style="width: 180px;">Satuan Kerja</th>
                        <th style="width: 130px;">Lokasi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    foreach ($data['data'] as $row): 
                    ?>
                    <tr>
                        <td>
                            <strong style="color: #2c3e50;"><?= htmlspecialchars($row['Paket']) ?></strong>
                            <div class="small-text">ID: <?= htmlspecialchars($row['ID']) ?></div>
                        </td>
                        <td class="price"><?= htmlspecialchars($row['Pagu_Rp']) ?></td>
                        <td>
                            <span class="badge badge-primary">
                                <?= htmlspecialchars($row['Jenis_Pengadaan']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-success">
                                <?= htmlspecialchars($row['Usaha_Kecil']) ?>
                            </span>
                        </td>
                        <td><small><?= htmlspecialchars($row['Metode']) ?></small></td>
                        <td><small><?= htmlspecialchars($row['Pemilihan']) ?></small></td>
                        <td><small><?= htmlspecialchars($row['KLPD']) ?></small></td>
                        <td><small><?= htmlspecialchars($row['Satuan_Kerja']) ?></small></td>
                        <td><small><?= htmlspecialchars($row['Lokasi']) ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <strong>Informasi Halaman:</strong>
            Halaman <?= $data['pagination']['current_page'] ?? 1 ?> 
            dari <?= $data['pagination']['total_pages'] ?? 1 ?> | 
            Total Data: <strong><?= $data['pagination']['total_records'] ?? count($data['data']) ?></strong> pengadaan
        </div>

        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-search-minus"></i>
            <p>Tidak ada data pengadaan yang ditemukan</p>
            <small class="text-muted">Coba ubah kriteria pencarian atau filter yang Anda gunakan</small>
        </div>
        <?php endif; ?>
        
    </div>
</div>

<script>
// JavaScript untuk interaktivitas
document.addEventListener('DOMContentLoaded', function() {
    // Auto-submit form when filter changes
    const filterSelects = document.querySelectorAll('.filter-group select:not([name="limit"])');
    filterSelects.forEach(select => {
        select.addEventListener('change', function() {
            // Optional: auto-submit form on filter change
            // this.form.submit();
        });
    });
    
    // Search input enter key
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                this.form.submit();
            }
        });
    }
    
    // Table row click (optional)
    const tableRows = document.querySelectorAll('tbody tr');
    tableRows.forEach(row => {
        row.addEventListener('click', function() {
            // Optional: handle row click for details view
            console.log('Row clicked:', this);
        });
    });
    
    // Pagination buttons
    const paginationButtons = document.querySelectorAll('.pagination button');
    paginationButtons.forEach(button => {
        button.addEventListener('click', function() {
            if (!this.classList.contains('active')) {
                // Handle pagination click
                console.log('Pagination clicked:', this.textContent);
            }
        });
    });
});

// Format numbers in price columns
document.querySelectorAll('.price').forEach(priceCell => {
    const text = priceCell.textContent.trim();
    if (text && !isNaN(text.replace(/[^\d]/g, ''))) {
        const number = parseInt(text.replace(/[^\d]/g, ''));
        priceCell.textContent = 'Rp ' + number.toLocaleString('id-ID');
    }
});
</script>

<?php 
// Include footer
include '../../navbar/footer.php'; 
?>