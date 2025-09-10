<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seluruh Pengadaan - Portal Pemerintah</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .header-section {
            background: linear-gradient(135deg, #dc3545, #b02a37);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        
        .filter-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .data-table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
            border: none;
            padding: 15px 12px;
        }
        
        .table td {
            padding: 12px;
            vertical-align: middle;
            border-color: #e9ecef;
        }
        
        .btn-filter {
            background: linear-gradient(135deg, #dc3545, #b02a37);
            border: none;
            color: white;
            padding: 10px 25px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-filter:hover {
            background: linear-gradient(135deg, #b02a37, #8d1e2a);
            color: white;
            transform: translateY(-2px);
        }
        
        .form-select, .form-control {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 10px 15px;
            transition: all 0.3s ease;
        }
        
        .form-select:focus, .form-control:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }
        
        .badge {
            font-size: 0.75em;
            padding: 6px 12px;
        }
        
        .currency {
            font-weight: 600;
            color: #28a745;
        }
        
        .loading {
            display: none;
            text-align: center;
            padding: 2rem;
        }
        
        .no-data {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }
    </style>
</head>
<body class="bg-light">
    <div class="header-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="mb-0"><i class="fas fa-clipboard-list me-3"></i>Data Pengadaan</h2>
                    <p class="mb-0 mt-2 opacity-75">Sistem Informasi Pengadaan Barang dan Jasa</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="d-flex justify-content-end gap-2">
                        <button class="btn btn-outline-light" id="exportBtn">
                            <i class="fas fa-download me-2"></i>Export
                        </button>
                        <button class="btn btn-outline-light" id="printBtn">
                            <i class="fas fa-print me-2"></i>Print
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Filter Section -->
        <div class="filter-card">
            <h5 class="mb-3"><i class="fas fa-filter me-2 text-danger"></i>Filter Data</h5>
            <form id="filterForm">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Tahun</label>
                        <select class="form-select" id="tahun" name="tahun">
                            <option value="">Semua Tahun</option>
                            <option value="2024">2024</option>
                            <option value="2025" selected>2025</option>
                            <option value="2026">2026</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Bulan Awal</label>
                        <select class="form-select" id="bulanAwal" name="bulan_awal">
                            <option value="">Pilih Bulan</option>
                            <option value="1">Januari</option>
                            <option value="2">Februari</option>
                            <option value="3">Maret</option>
                            <option value="4">April</option>
                            <option value="5">Mei</option>
                            <option value="6">Juni</option>
                            <option value="7">Juli</option>
                            <option value="8">Agustus</option>
                            <option value="9">September</option>
                            <option value="10">Oktober</option>
                            <option value="11">November</option>
                            <option value="12">Desember</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Bulan Akhir</label>
                        <select class="form-select" id="bulanAkhir" name="bulan_akhir">
                            <option value="">Pilih Bulan</option>
                            <option value="1">Januari</option>
                            <option value="2">Februari</option>
                            <option value="3">Maret</option>
                            <option value="4">April</option>
                            <option value="5">Mei</option>
                            <option value="6">Juni</option>
                            <option value="7">Juli</option>
                            <option value="8">Agustus</option>
                            <option value="9">September</option>
                            <option value="10">Oktober</option>
                            <option value="11">November</option>
                            <option value="12">Desember</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Jenis Pengadaan</label>
                        <select class="form-select" id="jenisPengadaan" name="jenis_pengadaan">
                            <option value="">Semua Jenis</option>
                            <option value="Jasa Lainnya">Jasa Lainnya</option>
                            <option value="Barang">Barang</option>
                            <option value="Konstruksi">Konstruksi</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">KLPD</label>
                        <select class="form-select" id="klpd" name="klpd">
                            <option value="">Semua KLPD</option>
                            <option value="Kota Banjarmasin">Kota Banjarmasin</option>
                            <option value="Kabupaten Banjar">Kabupaten Banjar</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Pencarian</label>
                        <input type="text" class="form-control" id="search" name="search" placeholder="Cari paket...">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-filter">
                                <i class="fas fa-search me-2"></i>Cari Data
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Data Table -->
        <div class="data-table">
            <div class="table-responsive">
                <div class="p-3 border-bottom bg-light">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h6 class="mb-0">Hasil Pencarian</h6>
                            <small class="text-muted" id="resultInfo">Menampilkan semua data</small>
                        </div>
                        <div class="col-md-6 text-end">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="prev">
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary active">1</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary">2</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="next">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="loading" id="loading">
                    <div class="spinner-border text-danger" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Memuat data...</p>
                </div>
                
                <table class="table table-hover mb-0" id="dataTable">
                    <thead>
                        <tr>
                            <th style="width: 50px">No</th>
                            <th>Paket</th>
                            <th style="width: 120px">Pagu (Rp)</th>
                            <th style="width: 130px">Jenis Pengadaan</th>
                            <th style="width: 130px">Usaha Kecil</th>
                            <th style="width: 100px">Metode</th>
                            <th style="width: 100px">Pemilihan</th>
                            <th style="width: 120px">KLPD</th>
                            <th>Satuan Kerja</th>
                            <th style="width: 120px">Lokasi</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <!-- Data akan dimuat via JavaScript -->
                    </tbody>
                </table>
                
                <div class="no-data d-none" id="noData">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Tidak ada data ditemukan</h5>
                    <p class="text-muted">Silakan ubah filter pencarian Anda</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
    <script>
        // Global variables
        let currentPage = 1;
        const itemsPerPage = 50;
        let totalPages = 1;

        // Format currency
        function formatCurrency(amount) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(amount).replace('IDR', 'Rp');
        }

        // Load dropdown options from API
        function loadOptions() {
            $.ajax({
                url: 'api/pengadaan.php?action=options',
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        // Populate dropdowns dengan data dari database
                        const jenisPengadaan = $('#jenisPengadaan');
                        const klpd = $('#klpd');
                        const tahun = $('#tahun');

                        // Clear existing options (keep first option)
                        jenisPengadaan.find('option:not(:first)').remove();
                        klpd.find('option:not(:first)').remove();
                        tahun.find('option:not(:first)').remove();

                        // Add jenis pengadaan options
                        response.options.jenis_pengadaan.forEach(item => {
                            jenisPengadaan.append(`<option value="${item.value}">${item.value}</option>`);
                        });

                        // Add KLPD options
                        response.options.klpd.forEach(item => {
                            klpd.append(`<option value="${item.value}">${item.value}</option>`);
                        });

                        // Add year options
                        response.options.years.forEach(item => {
                            tahun.append(`<option value="${item.year}">${item.year}</option>`);
                        });

                        // Set current year as default
                        const currentYear = new Date().getFullYear();
                        tahun.val(currentYear);
                    }
                },
                error: function() {
                    console.error('Failed to load dropdown options');
                    // Fallback to static options jika API gagal
                    loadStaticOptions();
                }
            });
        }

        // Fallback static options
        function loadStaticOptions() {
            const currentYear = new Date().getFullYear();
            $('#tahun').val(currentYear);
        }

        // Load table data from API
        function loadTableData(page = 1) {
            const formData = new FormData(document.getElementById('filterForm'));
            const params = new URLSearchParams();
            
            // Add form data to params
            for (let [key, value] of formData.entries()) {
                if (value) params.append(key, value);
            }
            
            // Add pagination params
            params.append('page', page);
            params.append('limit', itemsPerPage);
            params.append('action', 'list');

            // Show loading
            document.getElementById('loading').style.display = 'block';
            document.getElementById('dataTable').style.display = 'none';
            document.getElementById('noData').classList.add('d-none');

            $.ajax({
                url: 'api/pengadaan.php?' + params.toString(),
                method: 'GET',
                success: function(response) {
                    document.getElementById('loading').style.display = 'none';
                    
                    if (response.success && response.data.length > 0) {
                        displayTableData(response.data);
                        updatePagination(response.pagination);
                        document.getElementById('dataTable').style.display = 'table';
                    } else {
                        document.getElementById('noData').classList.remove('d-none');
                    }
                },
                error: function(xhr, status, error) {
                    document.getElementById('loading').style.display = 'none';
                    console.error('Error loading data:', error);
                    
                    // Show fallback sample data jika API tidak tersedia
                    console.warn('API not available, showing sample data');
                    displaySampleData();
                }
            });
        }

        // Display data in table
        function displayTableData(data) {
            const tbody = document.getElementById('tableBody');
            tbody.innerHTML = '';

            data.forEach(item => {
                const row = `
                    <tr>
                        <td>${item.No}</td>
                        <td>
                            <strong>${item.Paket}</strong>
                            <br><small class="text-muted">ID: ${item.ID}</small>
                        </td>
                        <td class="currency">${formatCurrency(item.Pagu_Rp)}</td>
                        <td>
                            <span class="badge bg-info">${item.Jenis_Pengadaan}</span>
                        </td>
                        <td>
                            <span class="badge bg-success">${item.Usaha_Kecil || 'N/A'}</span>
                        </td>
                        <td>${item.Metode}</td>
                        <td>${item.Pemilihan}</td>
                        <td>${item.KLPD}</td>
                        <td>
                            <small>${item.Satuan_Kerja}</small>
                        </td>
                        <td>
                            <small>${item.Lokasi}</small>
                        </td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
        }

        // Fallback sample data display
        function displaySampleData() {
            const sampleData = [
                {
                    No: 1,
                    Paket: "Jasa Tenaga Kebersihan (B)",
                    Pagu_Rp: 172800000,
                    Jenis_Pengadaan: "Jasa Lainnya",
                    Usaha_Kecil: "Usaha Kecil/Koperasi",
                    Metode: "E-Purchasing",
                    Pemilihan: "January 2025",
                    KLPD: "Kota Banjarmasin",
                    Satuan_Kerja: "Dinas Kebudayaan, Kepemudaan, Olahraga dan Pariwisata",
                    Lokasi: "Kalimantan Selatan, Banjarmasin (Kota)",
                    ID: "53807825"
                },
                {
                    No: 2,
                    Paket: "Belanja Pakaian Adat Daerah",
                    Pagu_Rp: 4000000,
                    Jenis_Pengadaan: "Jasa Lainnya",
                    Usaha_Kecil: "Usaha Kecil/Koperasi",
                    Metode: "Pengadaan Langsung",
                    Pemilihan: "August 2025",
                    KLPD: "Kota Banjarmasin",
                    Satuan_Kerja: "Badan Pengelolaan Keuangan, Pendapatan dan Aset Daerah",
                    Lokasi: "Kalimantan Selatan, Banjarmasin (Kota)",
                    ID: "54223419"
                }
            ];

            displayTableData(sampleData);
            document.getElementById('dataTable').style.display = 'table';
            document.getElementById('resultInfo').textContent = 
                `Menampilkan ${sampleData.length} data (sample data)`;
        }

        // Update pagination info
        function updatePagination(pagination) {
            const startRecord = pagination.per_page * (pagination.current_page - 1) + 1;
            const endRecord = Math.min(pagination.per_page * pagination.current_page, pagination.total_records);
            
            document.getElementById('resultInfo').textContent = 
                `Menampilkan ${startRecord}-${endRecord} dari ${pagination.total_records} data`;
            
            currentPage = pagination.current_page;
            totalPages = pagination.total_pages;

            // Update pagination buttons
            updatePaginationButtons(pagination);
        }

        // Update pagination buttons
        function updatePaginationButtons(pagination) {
            const paginationContainer = document.querySelector('.btn-group');
            const prevBtn = document.getElementById('prev');
            const nextBtn = document.getElementById('next');

            // Enable/disable prev button
            if (pagination.has_prev) {
                prevBtn.classList.remove('disabled');
                prevBtn.onclick = () => loadTableData(currentPage - 1);
            } else {
                prevBtn.classList.add('disabled');
                prevBtn.onclick = null;
            }

            // Enable/disable next button
            if (pagination.has_next) {
                nextBtn.classList.remove('disabled');
                nextBtn.onclick = () => loadTableData(currentPage + 1);
            } else {
                nextBtn.classList.add('disabled');
                nextBtn.onclick = null;
            }
        }

        // Filter form submission
        document.getElementById('filterForm').addEventListener('submit', function(e) {
            e.preventDefault();
            currentPage = 1; // Reset to first page
            loadTableData(currentPage);
        });

        // Export functionality dengan API
        document.getElementById('exportBtn').addEventListener('click', function() {
            const formData = new FormData(document.getElementById('filterForm'));
            const params = new URLSearchParams();
            
            // Add form data to params
            for (let [key, value] of formData.entries()) {
                if (value) params.append(key, value);
            }
            
            params.append('action', 'export');
            params.append('format', 'csv');
            
            // Download file
            const downloadUrl = 'api/pengadaan.php?' + params.toString();
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.download = 'data_pengadaan_' + new Date().toISOString().split('T')[0] + '.csv';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });

        // Print functionality
        document.getElementById('printBtn').addEventListener('click', function() {
            window.print();
        });

        // Initialize page
        $(document).ready(function() {
            loadOptions();
            loadTableData(1);
        });
    </script>
</body>
</html>