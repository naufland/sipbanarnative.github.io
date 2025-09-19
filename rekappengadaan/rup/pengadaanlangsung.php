    <?php
    // URL API (ganti sesuai lokasi file php API kamu)
    $apiUrl = "http://sipbanar-phpnative.id/api/pengadaan.php";

    // Tambahkan parameter GET ke URL API
    if (!empty($_GET)) {
        $queryParams = array_filter($_GET, function ($value) {
            return $value !== '' && $value !== null;
        });

        if (!empty($queryParams)) {
            $apiUrl .= '?' . http_build_query($queryParams);
        }
    }

    // Ambil data dari API
    $response = file_get_contents($apiUrl);
    $data = json_decode($response, true);

    if ($data && isset($data['success']) && $data['success'] && count($data['data']) > 0) {
        // Hitung total pagu dan total paket
        $totalPagu = 0;
        $totalPaket = count($data['data']);

        foreach ($data['data'] as $row) {
            // Bersihkan format rupiah dan konversi ke angka
            $paguValue = $row['Pagu_Rp'];
            // Hapus semua karakter non-digit
            $cleanPagu = preg_replace('/[^\d]/', '', $paguValue);
            $totalPagu += (int)$cleanPagu;
        }

        // Format angka untuk tampilan
        $formattedTotalPagu = 'Rp ' . number_format($totalPagu, 0, ',', '.');

        // Hitung rata-rata pagu per paket
        $avgPagu = $totalPaket > 0 ? $totalPagu / $totalPaket : 0;
        $formattedAvgPagu = 'Rp ' . number_format($avgPagu, 0, ',', '.');

        // Analisis berdasarkan jenis pengadaan
        $jenisPengadaanStats = [];
        $klpdStats = [];
        $metodeStats = [];

        foreach ($data['data'] as $row) {
            $jenis = $row['Jenis_Pengadaan'];
            $klpd = $row['KLPD'];
            $metode = $row['Metode'];
            $paguValue = (int)preg_replace('/[^\d]/', '', $row['Pagu_Rp']);

            // Statistik per jenis pengadaan
            if (!isset($jenisPengadaanStats[$jenis])) {
                $jenisPengadaanStats[$jenis] = ['count' => 0, 'total_pagu' => 0];
            }
            $jenisPengadaanStats[$jenis]['count']++;
            $jenisPengadaanStats[$jenis]['total_pagu'] += $paguValue;

            // Statistik per KLPD
            if (!isset($klpdStats[$klpd])) {
                $klpdStats[$klpd] = ['count' => 0, 'total_pagu' => 0];
            }
            $klpdStats[$klpd]['count']++;
            $klpdStats[$klpd]['total_pagu'] += $paguValue;

            // Statistik per metode
            if (!isset($metodeStats[$metode])) {
                $metodeStats[$metode] = ['count' => 0, 'total_pagu' => 0];
            }
            $metodeStats[$metode]['count']++;
            $metodeStats[$metode]['total_pagu'] += $paguValue;
        }

        // Sort berdasarkan total pagu (descending)
        uasort($jenisPengadaanStats, function ($a, $b) {
            return $b['total_pagu'] - $a['total_pagu'];
        });
        uasort($klpdStats, function ($a, $b) {
            return $b['total_pagu'] - $a['total_pagu'];
        });
        uasort($metodeStats, function ($a, $b) {
            return $b['total_pagu'] - $a['total_pagu'];
        });
    }

    // Set page title untuk header
    $page_title = "Data Pengadaan - SIP BANAR";

    // Include header
    include '../../navbar/header.php';

    ?>

    <!-- Custom CSS untuk halaman ini -->
    <script src="../../js/submenu.js"></script>
    <!-- Bootstrap JS harus dimuat dulu -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <!-- Kemudian submenu script -->

    <style>
        /* Custom CSS untuk halaman pengadaan - Perbaikan Filter Layout */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Filter Section Styles - Diperbaiki */
        .filter-section {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            overflow: hidden;
            border: 1px solid #e9ecef;
        }

        .filter-header {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 20px 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            position: relative;
        }

        .filter-header::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #dc3545, #e74c3c, #dc3545);
        }

        .filter-header i {
            font-size: 20px;
        }

        .filter-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .filter-content {
            padding: 30px 25px;
        }

        /* Grid Layout untuk Filter - Layout Rapi */
        .filter-row {
            display: grid;
            gap: 25px;
            margin-bottom: 25px;
        }

        /* Baris pertama: Periode Tanggal (2 kolom) + Jenis Pengadaan (1 kolom) */
        .filter-row:nth-child(1) {
            grid-template-columns: 2fr 1fr;
        }

        /* Baris kedua: KLPD + Metode + Pencarian Paket (3 kolom sama) */
        .filter-row:nth-child(2) {
            grid-template-columns: 1fr 1fr 1fr;
        }

        /* Baris ketiga: Limit Data (1 kolom, rata kiri) */
        .filter-row:nth-child(3) {
            grid-template-columns: 300px 1fr;
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
            letter-spacing: 0.3px;
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
            color: #2c3e50;
        }

        .filter-group select:focus,
        .filter-group input[type="text"]:focus {
            outline: none;
            border-color: #dc3545;
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.15);
            transform: translateY(-1px);
        }

        .filter-group select:hover,
        .filter-group input[type="text"]:hover {
            border-color: #dc3545;
        }

        /* Date Range Styles - Diperbaiki */
        .date-range-group {
            position: relative;
        }

        .date-range-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #2c3e50;
            font-size: 14px;
            letter-spacing: 0.3px;
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
            transition: all 0.3s ease;
        }

        .date-range-container:focus-within {
            border-color: #dc3545;
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.15);
            background: white;
        }

        .date-range-container input[type="date"] {
            border: none;
            background: transparent;
            padding: 10px 12px;
            font-size: 14px;
            color: #2c3e50;
            border-radius: 6px;
            min-width: 140px;
        }

        .date-range-container input[type="date"]:focus {
            outline: none;
            background: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .date-separator {
            color: #dc3545;
            font-weight: 700;
            font-size: 14px;
            padding: 8px 12px;
            background: white;
            border-radius: 20px;
            border: 2px solid #dc3545;
            white-space: nowrap;
            min-width: 50px;
            text-align: center;
        }

        /* Search Input dengan Icon */
        .search-input-wrapper {
            position: relative;
        }

        .search-input-wrapper i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            font-size: 16px;
            z-index: 2;
        }

        .search-input-wrapper input[type="text"] {
            padding-left: 45px !important;
        }

        /* Search Row - Tombol di kanan */
        .search-row {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            padding-top: 25px;
            border-top: 2px solid #f1f3f4;
            gap: 15px;
        }

        .search-btn {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            border: none;
            padding: 14px 30px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            min-width: 140px;
            justify-content: center;
        }

        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.3);
        }

        .reset-btn {
            background: transparent;
            color: #6c757d;
            border: 2px solid #e9ecef;
            padding: 12px 25px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            min-width: 120px;
            justify-content: center;
        }

        .reset-btn:hover {
            border-color: #dc3545;
            color: #dc3545;
            background: #fff5f5;
        }

        /* Summary Section Styles */
        .summary-section {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            overflow: hidden;
            border: 1px solid #e9ecef;
        }

        .summary-header {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            color: white;
            padding: 20px 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            position: relative;
        }

        .summary-header::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #27ae60, #2ecc71, #27ae60);
        }

        .summary-header i {
            font-size: 20px;
        }

        .summary-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .summary-content {
            padding: 30px 25px;
        }

        /* Summary Cards */
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .summary-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            border: 2px solid transparent;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
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

        .summary-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
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

        .card-content {
            flex: 1;
        }

        .card-value {
            font-size: 24px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
            line-height: 1;
        }

        .card-label {
            font-size: 14px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 2px;
        }

        .card-subtitle {
            font-size: 12px;
            color: #6c757d;
        }

        /* Statistics Tables */
        .stats-tables {
            display: grid;
            gap: 30px;
        }

        .stats-table {
            background: black;
            border-radius: 12px;
            padding: 25px;
            border: 1px solid #e9ecef;
        }

        .stats-table h4 {
            margin: 0 0 20px 0;
            color: red;
            font-size: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .stats-table h4 i {
            color: #dc3545;
        }

        .stats-table table {
            width: 100%;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .stats-table th {
            background: #2c3e50;
            color: white;
            padding: 15px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .stats-table td {
            padding: 15px;
            border-bottom: 1px solid #f1f1f1;
            vertical-align: middle;
        }

        .stats-table tr:hover {
            background: #f8f9fa;
        }

        /* Progress Bar */
        .progress-bar {
            position: relative;
            background: #e9ecef;
            border-radius: 10px;
            height: 24px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .progress-fill {
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            background: #dc3545;
            border-radius: 10px;
            transition: width 0.5s ease;
        }

        .progress-fill.success {
            background: #27ae60;
        }

        .progress-fill.info {
            background: #17a2b8;
        }

        .progress-bar span {
            position: relative;
            z-index: 2;
            font-size: 12px;
            font-weight: 600;
            color: white;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .summary-cards {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 15px;
            }

            .summary-card {
                padding: 20px;
                flex-direction: column;
                text-align: center;
            }

            .card-icon {
                width: 50px;
                height: 50px;
                font-size: 20px;
            }

            .card-value {
                font-size: 20px;
            }
        }

        @media (max-width: 768px) {
            .summary-cards {
                grid-template-columns: 1fr;
            }

            .stats-table {
                padding: 15px;
            }

            .stats-table th,
            .stats-table td {
                padding: 10px 8px;
                font-size: 12px;
            }
        }

        /* Animation */
        .summary-section {
            animation: fadeInUp 0.6s ease-out;
        }

        .summary-card {
            animation: slideInUp 0.6s ease-out forwards;
            opacity: 0;
        }

        .summary-card:nth-child(1) {
            animation-delay: 0.1s;
        }

        .summary-card:nth-child(2) {
            animation-delay: 0.2s;
        }

        .summary-card:nth-child(3) {
            animation-delay: 0.3s;
        }

        .summary-card:nth-child(4) {
            animation-delay: 0.4s;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    /* Results Section - Diperbaiki */
    .results-section {
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    border: 1px solid #e9ecef;
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
    margin-bottom: 8px;
    }

    .results-subtitle {
    font-size: 14px;
    color: #6c757d;
    line-height: 1.5;
    }

    .pagination {
    display: flex;
    gap: 8px;
    align-items: center;
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
    transform: translateY(-1px);
    }

    .pagination button.active {
    background: #dc3545;
    border-color: #dc3545;
    color: white;
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
    }

    /* Table Styles - Diperbaiki */
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
    padding: 18px 15px;
    text-align: left;
    font-weight: 600;
    border-bottom: 3px solid #dc3545;
    position: sticky;
    top: 0;
    z-index: 10;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    }

    table th:first-child {
    border-top-left-radius: 0;
    }

    table th:last-child {
    border-top-right-radius: 0;
    }

    table td {
    padding: 18px 15px;
    border-bottom: 1px solid #f1f1f1;
    vertical-align: top;
    }

    table tr {
    transition: all 0.3s ease;
    }

    table tr:hover {
    background: #f8f9fa;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
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
    font-size: 15px;
    }

    /* Small Text */
    .small-text {
    font-size: 12px;
    color: #6c757d;
    margin-top: 4px;
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
    0% {
    transform: rotate(0deg);
    }

    100% {
    transform: rotate(360deg);
    }
    }

    /* Footer Info */
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

    /* Responsive Design */
    @media (max-width: 1200px) {
    .filter-row:nth-child(1) {
    grid-template-columns: 1fr;
    gap: 20px;
    }

    .filter-row:nth-child(2) {
    grid-template-columns: 1fr 1fr;
    }

    .filter-row:nth-child(3) {
    grid-template-columns: 1fr;
    }
    }

    @media (max-width: 992px) {

    .filter-row:nth-child(1),
    .filter-row:nth-child(2),
    .filter-row:nth-child(3) {
    grid-template-columns: 1fr;
    }

    .date-range-container {
    grid-template-columns: 1fr;
    gap: 15px;
    padding: 15px;
    text-align: center;
    }

    .date-separator {
    transform: rotate(90deg);
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    }

    .results-header {
    flex-direction: column;
    gap: 15px;
    align-items: flex-start;
    }

    .pagination {
    align-self: center;
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
    flex-direction: column;
    gap: 12px;
    }

    .search-btn,
    .reset-btn {
    width: 100%;
    min-width: auto;
    }

    .table-footer {
    flex-direction: column;
    gap: 10px;
    text-align: center;
    }

    table th,
    table td {
    padding: 12px 8px;
    font-size: 13px;
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
                    <!-- Baris 1: Periode Tanggal + Jenis Pengadaan -->
                    <div class="filter-row">
                        <!-- Date Range Filter -->
                        <div class="date-range-group">
                            <label><i class="fas fa-calendar-alt"></i> Periode Tanggal</label>
                            <div class="date-range-container">
                                <input type="date" name="tanggal_awal"
                                    value="<?= htmlspecialchars($_GET['tanggal_awal'] ?? '') ?>"
                                    placeholder="Tanggal Mulai">
                                <span class="date-separator">S/D</span>
                                <input type="date" name="tanggal_akhir"
                                    value="<?= htmlspecialchars($_GET['tanggal_akhir'] ?? '') ?>"
                                    placeholder="Tanggal Akhir">
                            </div>
                        </div>

                        <div class="filter-group">
                            <label><i class="fas fa-tags"></i> Jenis Pengadaan</label>
                            <select name="jenis_pengadaan">
                                <option value="">Semua Jenis</option>
                                <option value="Jasa Lainnya" <?= ($_GET['jenis_pengadaan'] ?? '') == 'Jasa Lainnya' ? 'selected' : '' ?>>Jasa Lainnya</option>
                                <option value="Pengadaan Langsung" <?= ($_GET['jenis_pengadaan'] ?? '') == 'Pengadaan Langsung' ? 'selected' : '' ?>>Pengadaan Langsung</option>
                                <option value="Barang" <?= ($_GET['jenis_pengadaan'] ?? '') == 'Barang' ? 'selected' : '' ?>>Barang</option>
                                <option value="Konstruksi" <?= ($_GET['jenis_pengadaan'] ?? '') == 'Konstruksi' ? 'selected' : '' ?>>Konstruksi</option>
                            </select>
                        </div>
                    </div>

                    <!-- Baris 2: KLPD + Metode + Pencarian -->
                    <div class="filter-row">
                        <div class="filter-group">
                            <label><i class="fas fa-building"></i> KLPD</label>
                            <select name="klpd">
                                <option value="">Semua KLPD</option>
                                <option value="Kota Banjarmasin" <?= ($_GET['klpd'] ?? '') == 'Kota Banjarmasin' ? 'selected' : '' ?>>Kota Banjarmasin</option>
                                <option value="Kabupaten Banjar" <?= ($_GET['klpd'] ?? '') == 'Kabupaten Banjar' ? 'selected' : '' ?>>Kabupaten Banjar</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label><i class="fas fa-cogs"></i> Metode</label>
                            <select name="metode">
                                <option value="">Semua Metode</option>
                                <option value="E-Purchasing" <?= ($_GET['metode'] ?? '') == 'E-Purchasing' ? 'selected' : '' ?>>E-Purchasing</option>
                                <option value="Pengadaan Langsung" <?= ($_GET['metode'] ?? '') == 'Pengadaan Langsung' ? 'selected' : '' ?>>Pengadaan Langsung</option>
                                <option value="Tender" <?= ($_GET['metode'] ?? '') == 'Tender' ? 'selected' : '' ?>>Tender</option>
                                <option value="Dikecualikan" <?= ($_GET['metode'] ?? '') == 'Dikecualikan' ? 'selected' : '' ?>>Dikecualikan</option>
                                <option value="Penunjukan Langsung" <?= ($_GET['metode'] ?? '') == 'Penunjukan Langsung' ? 'selected' : '' ?>>Penunjukan Langsung</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label><i class="fas fa-search"></i> Pencarian Paket</label>
                            <div class="search-input-wrapper">
                                <i class="fas fa-search"></i>
                                <input type="text" name="search" placeholder="Cari nama paket..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Baris 3: Limit Data -->
                    <div class="filter-row">
                        <div class="filter-group">
                            <label><i class="fas fa-list"></i> Limit Data</label>
                            <select name="limit">
                                <option value="10" <?= ($_GET['limit'] ?? '25') == '10' ? 'selected' : '' ?>>10 Data</option>
                                <option value="25" <?= ($_GET['limit'] ?? '25') == '25' ? 'selected' : '' ?>>25 Data</option>
                                <option value="50" <?= ($_GET['limit'] ?? '25') == '50' ? 'selected' : '' ?>>50 Data</option>
                                <option value="100" <?= ($_GET['limit'] ?? '25') == '100' ? 'selected' : '' ?>>100 Data</option>
                            </select>
                        </div>
                    </div>

                    <!-- Search Row - Tombol -->
                    <div class="search-row">
                        <button type="button" class="reset-btn" onclick="resetForm()">
                            <i class="fas fa-undo"></i>
                            Reset Filter
                        </button>
                        <button type="submit" class="search-btn">
                            <i class="fas fa-search"></i>
                            Cari Data
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <!-- Tambahkan section ini setelah filter-section dan sebelum results-section -->
        <div class="summary-section">
            <div class="summary-header">
                <i class="fas fa-chart-bar"></i>
                <h3>Ringkasan Data Pengadaan</h3>
            </div>
            <div class="summary-content">
                <!-- Total Cards -->
                <div class="summary-cards">
                    <div class="summary-card primary">
                        <div class="card-icon">
                            <i class="fas fa-boxes"></i>
                        </div>
                        <div class="card-content">
                            <div class="card-value"><?= number_format($totalPaket, 0, ',', '.') ?></div>
                            <div class="card-label">Total Paket</div>
                            <div class="card-subtitle">Pengadaan</div>
                        </div>
                    </div>

                    <div class="summary-card success">
                        <div class="card-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="card-content">
                            <div class="card-value"><?= $formattedTotalPagu ?></div>
                            <div class="card-label">Total Pagu</div>
                            <div class="card-subtitle">Keseluruhan</div>
                        </div>
                    </div>

                    <div class="summary-card info">
                        <div class="card-icon">
                            <i class="fas fa-calculator"></i>
                        </div>
                        <div class="card-content">
                            <div class="card-value"><?= $formattedAvgPagu ?></div>
                            <div class="card-label">Rata-rata Pagu</div>
                            <div class="card-subtitle">Per Paket</div>
                        </div>
                    </div>

                    <div class="summary-card warning">
                        <div class="card-icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="card-content">
                            <div class="card-value"><?= count($klpdStats) ?></div>
                            <div class="card-label">KLPD Terlibat</div>
                            <div class="card-subtitle">Instansi</div>
                        </div>
                    </div>
                </div>
                <!-- Results Section -->
                <div class="results-section">
                    <div class="results-header">
                        <div>
                            <div class="results-title">
                                <i class="fas fa-table"></i> Hasil Pencarian Data Pengadaan
                            </div>
                            <?php if ($data && isset($data['success']) && $data['success']): ?>
                                <div class="results-subtitle">
                                    <strong>Menampilkan <?= count($data['data']) ?> data pengadaan</strong>
                                    <?php if (!empty($_GET['tanggal_awal']) && !empty($_GET['tanggal_akhir'])): ?>
                                        <br><small class="text-muted">
                                            <i class="fas fa-calendar"></i> Periode: <?= date('d/m/Y', strtotime($_GET['tanggal_awal'])) ?> - <?= date('d/m/Y', strtotime($_GET['tanggal_akhir'])) ?>
                                        </small>
                                    <?php endif; ?>
                                    <?php
                                    $activeFilters = array_filter($_GET, function ($value, $key) {
                                        return $value !== '' && $value !== null && $key !== 'limit';
                                    }, ARRAY_FILTER_USE_BOTH);
                                    if (!empty($activeFilters)):
                                    ?>
                                        <br><small style="color: #dc3545;">
                                            <i class="fas fa-filter"></i> Filter aktif:
                                            <?php
                                            $filterLabels = [
                                                'tanggal_awal' => 'Tanggal Mulai',
                                                'tanggal_akhir' => 'Tanggal Akhir',
                                                'jenis_pengadaan' => 'Jenis',
                                                'klpd' => 'KLPD',
                                                'metode' => 'Metode',
                                                'search' => 'Pencarian'
                                            ];
                                            $activeFilterNames = array_map(function ($key) use ($filterLabels) {
                                                return $filterLabels[$key] ?? $key;
                                            }, array_keys($activeFilters));
                                            echo implode(', ', $activeFilterNames);
                                            ?>
                                        </small>
                                    <?php endif; ?>
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
                                        <th style="width: 280px;">
                                            <i class="fas fa-box"></i> Paket Pengadaan
                                        </th>
                                        <th style="width: 130px;">
                                            <i class="fas fa-money-bill-wave"></i> Pagu (Rp)
                                        </th>
                                        <th style="width: 140px;">
                                            <i class="fas fa-tags"></i> Jenis Pengadaan
                                        </th>
                                        <th style="width: 120px;">
                                            <i class="fas fa-store"></i> Usaha Kecil
                                        </th>
                                        <th style="width: 120px;">
                                            <i class="fas fa-cogs"></i> Metode
                                        </th>
                                        <th style="width: 120px;">
                                            <i class="fas fa-calendar"></i> Pemilihan
                                        </th>
                                        <th style="width: 120px;">
                                            <i class="fas fa-building"></i> KLPD
                                        </th>
                                        <th style="width: 200px;">
                                            <i class="fas fa-sitemap"></i> Satuan Kerja
                                        </th>
                                        <th style="width: 150px;">
                                            <i class="fas fa-map-marker-alt"></i> Lokasi
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $no = 1;
                                    foreach ($data['data'] as $row):
                                    ?>
                                        <tr>
                                            <td>
                                                <div style="font-weight: 700; color: #2c3e50; margin-bottom: 5px;">
                                                    <?= htmlspecialchars($row['Paket']) ?>
                                                </div>
                                                <div class="small-text">
                                                    <i class="fas fa-id-card"></i> ID: <?= htmlspecialchars($row['ID']) ?>
                                                </div>
                                                <?php if (isset($row['Pemilihan']) && !empty($row['Pemilihan'])): ?>
                                                    <div class="small-text">
                                                        <i class="fas fa-clock"></i> <?= date('d/m/Y', strtotime($row['Pemilihan'])) ?>
                                                    </div>
                                                <?php endif; ?>
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
                            <div>
                                <strong><i class="fas fa-info-circle"></i> Informasi Halaman:</strong>
                                Halaman <?= $data['pagination']['current_page'] ?? 1 ?>
                                dari <?= $data['pagination']['total_pages'] ?? 1 ?>
                            </div>
                            <div>
                                <strong>Total Data: <?= $data['pagination']['total_records'] ?? count($data['data']) ?></strong> pengadaan
                            </div>
                        </div>

                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-search-minus"></i>
                            <p><strong>Tidak ada data pengadaan yang ditemukan</strong></p>
                            <small class="text-muted">Coba ubah kriteria pencarian atau filter yang Anda gunakan</small>
                        </div>
                    <?php endif; ?>

                </div>
            </div>

            <script>
                // JavaScript untuk interaktivitas - Diperbaiki
                document.addEventListener('DOMContentLoaded', function() {
                    // Date range validation
                    const tanggalAwal = document.querySelector('input[name="tanggal_awal"]');
                    const tanggalAkhir = document.querySelector('input[name="tanggal_akhir"]');

                    if (tanggalAwal && tanggalAkhir) {
                        tanggalAwal.addEventListener('change', function() {
                            tanggalAkhir.min = this.value;
                            if (tanggalAkhir.value && tanggalAkhir.value < this.value) {
                                tanggalAkhir.value = this.value;
                            }
                        });

                        tanggalAkhir.addEventListener('change', function() {
                            tanggalAwal.max = this.value;
                            if (tanggalAwal.value && tanggalAwal.value > this.value) {
                                tanggalAwal.value = this.value;
                            }
                        });
                    }

                    // Set today's date as max for date inputs
                    const today = new Date().toISOString().split('T')[0];
                    if (tanggalAwal) tanggalAwal.max = today;
                    if (tanggalAkhir) tanggalAkhir.max = today;

                    // Auto-submit form when filter changes (optional)
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

                        // Clear search icon functionality
                        searchInput.addEventListener('input', function() {
                            const wrapper = this.closest('.search-input-wrapper');
                            const icon = wrapper.querySelector('i');
                            if (this.value) {
                                icon.className = 'fas fa-times';
                                icon.style.cursor = 'pointer';
                                icon.onclick = () => {
                                    this.value = '';
                                    icon.className = 'fas fa-search';
                                    icon.style.cursor = 'default';
                                    icon.onclick = null;
                                };
                            } else {
                                icon.className = 'fas fa-search';
                                icon.style.cursor = 'default';
                                icon.onclick = null;
                            }
                        });
                    }

                    // Table row hover effects
                    const tableRows = document.querySelectorAll('tbody tr');
                    tableRows.forEach(row => {
                        row.addEventListener('click', function() {
                            // Optional: handle row click for details view
                            console.log('Row clicked:', this);
                        });

                        // Add subtle hover animation
                        row.addEventListener('mouseenter', function() {
                            this.style.transform = 'translateY(-2px)';
                        });

                        row.addEventListener('mouseleave', function() {
                            this.style.transform = 'translateY(0)';
                        });
                    });

                    // Pagination buttons functionality
                    const paginationButtons = document.querySelectorAll('.pagination button');
                    paginationButtons.forEach((button, index) => {
                        button.addEventListener('click', function() {
                            if (!this.classList.contains('active')) {
                                // Remove active class from all buttons
                                paginationButtons.forEach(btn => btn.classList.remove('active'));
                                // Add active class to clicked button (except nav buttons)
                                if (!this.innerHTML.includes('fa-chevron')) {
                                    this.classList.add('active');
                                }
                                console.log('Pagination clicked:', this.textContent || 'Navigation');
                            }
                        });
                    });

                    // Format numbers in price columns
                    document.querySelectorAll('.price').forEach(priceCell => {
                        const text = priceCell.textContent.trim();
                        if (text && !isNaN(text.replace(/[^\d]/g, ''))) {
                            const number = parseInt(text.replace(/[^\d]/g, ''));
                            if (number > 0) {
                                priceCell.innerHTML = '<i class="fas fa-rupiah-sign" style="font-size: 12px; margin-right: 3px;"></i>Rp ' + number.toLocaleString('id-ID');
                            }
                        }
                    });
                });

                // Reset form function
                function resetForm() {
                    const form = document.querySelector('form');
                    const inputs = form.querySelectorAll('input, select');

                    inputs.forEach(input => {
                        if (input.type === 'text' || input.type === 'date') {
                            input.value = '';
                        } else if (input.tagName === 'SELECT') {
                            input.selectedIndex = 0;
                        }
                    });

                    // Reset search icon
                    const searchIcon = document.querySelector('.search-input-wrapper i');
                    if (searchIcon) {
                        searchIcon.className = 'fas fa-search';
                        searchIcon.style.cursor = 'default';
                        searchIcon.onclick = null;
                    }

                    // Optional: auto-submit after reset
                    // form.submit();
                }

                // Form validation before submit
                document.querySelector('form').addEventListener('submit', function(e) {
                    const tanggalAwal = document.querySelector('input[name="tanggal_awal"]').value;
                    const tanggalAkhir = document.querySelector('input[name="tanggal_akhir"]').value;

                    // Show loading state
                    const submitBtn = this.querySelector('.search-btn');
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mencari...';
                    submitBtn.disabled = true;

                    // Validate date range
                    if (tanggalAwal && tanggalAkhir && tanggalAwal > tanggalAkhir) {
                        e.preventDefault();
                        alert('Tanggal awal tidak boleh lebih besar dari tanggal akhir!');
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                        return false;
                    }

                    // Check if date range is too wide (optional: limit to 1 year)
                    if (tanggalAwal && tanggalAkhir) {
                        const startDate = new Date(tanggalAwal);
                        const endDate = new Date(tanggalAkhir);
                        const diffTime = Math.abs(endDate - startDate);
                        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

                        if (diffDays > 365) {
                            const confirm = window.confirm('Periode pencarian lebih dari 1 tahun. Ini mungkin membutuhkan waktu loading yang lama. Lanjutkan?');
                            if (!confirm) {
                                e.preventDefault();
                                submitBtn.innerHTML = originalText;
                                submitBtn.disabled = false;
                                return false;
                            }
                        }
                    }

                    // Reset button state after a delay if form doesn't redirect
                    setTimeout(() => {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }, 5000);
                });

                // Add smooth scrolling to results when form is submitted
                window.addEventListener('load', function() {
                    const urlParams = new URLSearchParams(window.location.search);
                    if (urlParams.toString()) {
                        // If there are URL parameters, scroll to results
                        document.querySelector('.results-section').scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });

                // Add tooltips to badges
                document.querySelectorAll('.badge').forEach(badge => {
                    badge.addEventListener('mouseenter', function() {
                        const text = this.textContent;
                        this.title = `Kategori: ${text}`;
                    });
                });

                // Add copy functionality to ID
                document.querySelectorAll('.small-text').forEach(smallText => {
                    if (smallText.textContent.includes('ID:')) {
                        smallText.style.cursor = 'pointer';
                        smallText.title = 'Klik untuk copy ID';
                        smallText.addEventListener('click', function(e) {
                            e.stopPropagation();
                            const idText = this.textContent.replace('ID: ', '').trim();
                            navigator.clipboard.writeText(idText).then(() => {
                                // Show temporary feedback
                                const originalText = this.textContent;
                                this.textContent = '✓ ID Copied!';
                                this.style.color = '#27ae60';
                                setTimeout(() => {
                                    this.textContent = originalText;
                                    this.style.color = '';
                                }, 1500);
                            });
                        });
                    }
                });
            </script>

            <?php
            // Include footer
            include '../../navbar/footer.php';
            ?>