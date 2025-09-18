<?php
    // URL API (ganti sesuai lokasi file php API kamu)
    $baseApiUrl = "http://sipbanar-phpnative.id/api/swakelola.php";
    $apiUrl = $baseApiUrl;

    // Debug: tampilkan parameter yang diterima
    // error_log("GET Parameters: " . print_r($_GET, true));

    // Buat array parameter yang valid untuk API
    $validParams = [];
    
    // Filter dan validasi parameter - SESUAIKAN DENGAN API ANDA
    if (isset($_GET['tanggal_awal']) && !empty($_GET['tanggal_awal'])) {
        if (DateTime::createFromFormat('Y-m-d', $_GET['tanggal_awal'])) {
            $validParams['tanggal_awal'] = $_GET['tanggal_awal'];
        }
    }
    
    if (isset($_GET['tanggal_akhir']) && !empty($_GET['tanggal_akhir'])) {
        if (DateTime::createFromFormat('Y-m-d', $_GET['tanggal_akhir'])) {
            $validParams['tanggal_akhir'] = $_GET['tanggal_akhir'];
        }
    }
    
    // PERUBAHAN: Gunakan jenis_pengadaan sesuai API, bukan tipe_swakelola
    if (isset($_GET['tipe_swakelola']) && !empty($_GET['tipe_swakelola'])) {
        $validParams['jenis_pengadaan'] = htmlspecialchars($_GET['tipe_swakelola']);
    }
    
    if (isset($_GET['klpd']) && !empty($_GET['klpd'])) {
        $validParams['klpd'] = htmlspecialchars($_GET['klpd']);
    }
    
    // PERUBAHAN: API tidak mendukung satuan_kerja sebagai filter, hapus atau sesuaikan
    // if (isset($_GET['satuan_kerja']) && !empty($_GET['satuan_kerja'])) {
    //     $validParams['satuan_kerja'] = htmlspecialchars($_GET['satuan_kerja']);
    // }
    
    if (isset($_GET['pagu_min']) && !empty($_GET['pagu_min'])) {
        $pagu_min = preg_replace('/[^\d]/', '', $_GET['pagu_min']);
        if (is_numeric($pagu_min) && $pagu_min >= 0) {
            $validParams['pagu_min'] = $pagu_min;
        }
    }
    
    if (isset($_GET['pagu_max']) && !empty($_GET['pagu_max'])) {
        $pagu_max = preg_replace('/[^\d]/', '', $_GET['pagu_max']);
        if (is_numeric($pagu_max) && $pagu_max >= 0) {
            $validParams['pagu_max'] = $pagu_max;
        }
    }
    
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $validParams['search'] = htmlspecialchars($_GET['search']);
    }
    
    // Set default limit jika tidak ada
    $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int)$_GET['limit'] : 25;
    $validParams['limit'] = $limit;
    
    // Set page untuk pagination
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $validParams['page'] = $page;

    // PENTING: Tambahkan action=list untuk data utama
    $validParams['action'] = 'list';

    // Bangun URL API dengan parameter
    $apiUrl = $baseApiUrl . '?' . http_build_query($validParams);

    // Inisialisasi variabel untuk menghindari error
    $data = null;
    $options = null;
    $errorMessage = null;

    // Function untuk melakukan cURL request
    function makeApiRequest($url, $timeout = 30) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            throw new Exception("cURL Error: " . $curlError);
        }
        
        if ($httpCode !== 200) {
            throw new Exception("HTTP Error: " . $httpCode);
        }
        
        if (!$response) {
            throw new Exception("Empty response from API");
        }
        
        $decodedResponse = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON Decode Error: " . json_last_error_msg());
        }
        
        return $decodedResponse;
    }

    // Ambil data dari API dengan error handling
    try {
        $data = makeApiRequest($apiUrl);
    } catch (Exception $e) {
        error_log("API Error: " . $e->getMessage());
        $errorMessage = "Terjadi kesalahan saat mengambil data: " . $e->getMessage();
        
        // Fallback: gunakan file_get_contents jika cURL gagal
        if (function_exists('file_get_contents') && ini_get('allow_url_fopen')) {
            try {
                $context = stream_context_create([
                    'http' => [
                        'timeout' => 30,
                        'method' => 'GET',
                        'header' => "User-Agent: PHP\r\n"
                    ]
                ]);
                
                $response = file_get_contents($apiUrl, false, $context);
                if ($response) {
                    $data = json_decode($response, true);
                    $errorMessage = null;
                }
            } catch (Exception $fallbackError) {
                error_log("Fallback Error: " . $fallbackError->getMessage());
            }
        }
    }

    // PERBAIKAN: Ambil options dari data yang sudah berhasil diambil
    $options = [
        'success' => true,
        'options' => [
            'tipe_swakelola' => [],
            'klpd' => [],
            'satuan_kerja' => []
        ]
    ];
    
    // Jika data berhasil diambil, ekstrak unique values untuk dropdown
    if ($data && isset($data['success']) && $data['success'] && isset($data['data']) && is_array($data['data'])) {
        $uniqueTipe = [];
        $uniqueKlpd = [];
        $uniqueSatker = [];
        
        foreach ($data['data'] as $row) {
            // Ambil Jenis_Pengadaan untuk Tipe Swakelola
            $jenis = $row['Jenis_Pengadaan'] ?? null;
            if ($jenis && $jenis !== 'N/A' && $jenis !== '' && !in_array($jenis, $uniqueTipe)) {
                $uniqueTipe[] = $jenis;
            }
            
            // Ambil KLPD
            $klpd = $row['KLPD'] ?? null;
            if ($klpd && $klpd !== 'N/A' && $klpd !== '' && !in_array($klpd, $uniqueKlpd)) {
                $uniqueKlpd[] = $klpd;
            }
            
            // Ambil Satuan Kerja
            $satker = $row['Satuan_Kerja'] ?? null;
            if ($satker && $satker !== 'N/A' && $satker !== '' && !in_array($satker, $uniqueSatker)) {
                $uniqueSatker[] = $satker;
            }
        }
        
        // Sort arrays untuk tampilan yang rapi
        sort($uniqueTipe);
        sort($uniqueKlpd);  
        sort($uniqueSatker);
        
        $options['options']['tipe_swakelola'] = $uniqueTipe;
        $options['options']['klpd'] = $uniqueKlpd;
        $options['options']['satuan_kerja'] = $uniqueSatker;
        
        error_log("Extracted options - Tipe: " . count($uniqueTipe) . ", KLPD: " . count($uniqueKlpd) . ", Satker: " . count($uniqueSatker));
    }
    
    // FALLBACK: Coba ambil dari API options jika data ekstraksi tidak berhasil
    if (empty($options['options']['tipe_swakelola']) && empty($options['options']['klpd'])) {
        try {
            $optionsUrl = $baseApiUrl . "?action=options";
            $apiOptions = makeApiRequest($optionsUrl, 15);
            
            if (isset($apiOptions['success']) && $apiOptions['success'] && isset($apiOptions['options'])) {
                error_log("Using API options as fallback");
                $options['options']['tipe_swakelola'] = $apiOptions['options']['jenis_pengadaan'] ?? [];
                $options['options']['klpd'] = $apiOptions['options']['klpd'] ?? [];
                // satuan_kerja tetap dari ekstraksi data karena API tidak menyediakan
            }
        } catch (Exception $e) {
            error_log("Options API Fallback Error: " . $e->getMessage());
        }
    }

    // Set page title untuk header
    $page_title = "Data Swakelola - SIP BANAR";

    // Include header
    include '../../navbar/header.php';
    
    ?>
    <!-- Sisa kode HTML dan JavaScript tetap sama seperti sebelumnya -->

    <!-- Custom CSS untuk halaman ini -->
    <script src="../../js/submenu.js"></script>
    <!-- Bootstrap JS harus dimuat dulu -->
   <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
   <!-- Kemudian submenu script -->

    <!-- [CSS tetap sama seperti sebelumnya] -->
    <style>
    /* Custom CSS untuk halaman swakelola - Konsisten dengan desain pengadaan */
    .container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 20px;
    }

    /* Error message styles */
    .error-section {
        background: #f8d7da;
        border: 1px solid #f5c6cb;
        color: #721c24;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    /* Filter Section Styles */
    .filter-section {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
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

    /* Grid Layout untuk Filter */
    .filter-row {
        display: grid;
        gap: 25px;
        margin-bottom: 25px;
    }

    /* Baris pertama: Periode Tanggal (2 kolom) + Tipe Swakelola (1 kolom) */
    .filter-row:nth-child(1) {
        grid-template-columns: 2fr 1fr;
    }

    /* Baris kedua: KLPD + Satuan Kerja + Range Pagu (3 kolom sama) */
    .filter-row:nth-child(2) {
        grid-template-columns: 1fr 1fr 1fr;
    }

    /* Baris ketiga: Pencarian + Limit Data */
    .filter-row:nth-child(3) {
        grid-template-columns: 2fr 1fr;
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
    .filter-group input[type="text"],
    .filter-group input[type="number"] {
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
    .filter-group input[type="text"]:focus,
    .filter-group input[type="number"]:focus {
        outline: none;
        border-color: #dc3545;
        box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.15);
        transform: translateY(-1px);
    }

    .filter-group select:hover,
    .filter-group input[type="text"]:hover,
    .filter-group input[type="number"]:hover {
        border-color: #dc3545;
    }

    /* Date Range Styles */
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
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
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

    /* Pagu Range Styles */
    .pagu-range-container {
        display: grid;
        grid-template-columns: 1fr auto 1fr;
        align-items: center;
        gap: 10px;
        background: #f8f9fa;
        padding: 8px 15px;
        border-radius: 10px;
        border: 2px solid #e9ecef;
        transition: all 0.3s ease;
    }

    .pagu-range-container:focus-within {
        border-color: #dc3545;
        box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.15);
        background: white;
    }

    .pagu-range-container input[type="number"] {
        border: none;
        background: transparent;
        padding: 8px 10px;
        font-size: 13px;
        color: #2c3e50;
        border-radius: 6px;
        width: 100%;
    }

    .pagu-range-container input[type="number"]:focus {
        outline: none;
        background: white;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .pagu-separator {
        color: #dc3545;
        font-weight: 600;
        font-size: 12px;
        padding: 4px 8px;
        background: white;
        border-radius: 15px;
        border: 2px solid #dc3545;
        white-space: nowrap;
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

    /* Results Section */
    .results-section {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
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

    table th:first-child { border-top-left-radius: 0; }
    table th:last-child { border-top-right-radius: 0; }

    table td {
        padding: 18px 15px;
        border-bottom: 1px solid #f1f1f1;
        vertical-align: top;
    }

    table tr {
        transition: all 0.3s ease;
    }

    table tr:hover {
        background: #fff5f5;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    table tr:nth-child(even) {
        background: #fafafa;
    }

    table tr:nth-child(even):hover {
        background: #f8f0f0;
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

    .badge-success { background: #28a745; color: white; }
    .badge-info { background: #17a2b8; color: white; }
    .badge-warning { background: #ffc107; color: #212529; }
    .badge-secondary { background: #6c757d; color: white; }

    /* Price Formatting */
    .price {
        font-weight: 700;
        color: #dc3545;
        white-space: nowrap;
        font-size: 15px;
    }

    /* Small Text */
    .small-text {
        font-size: 12px;
        color: #6c757d;
        margin-top: 4px;
    }

    .text-muted { color: #6c757d; }

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

        .pagu-range-container {
            grid-template-columns: 1fr;
            gap: 10px;
            text-align: center;
        }

        .date-separator,
        .pagu-separator {
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
            min-width: 900px;
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
        <!-- Error Message Section -->
        <?php if ($errorMessage): ?>
        <div class="error-section">
            <i class="fas fa-exclamation-triangle"></i>
            <span><?= htmlspecialchars($errorMessage) ?></span>
        </div>
        <?php endif; ?>

        <!-- Filter Section -->
        <div class="filter-section">
            <div class="filter-header">
                <i class="fas fa-filter"></i>
                <h3>Filter Data Swakelola</h3>
            </div>
            <div class="filter-content">
                <form method="GET" action="" id="filterForm">
                    <!-- Baris 1: Periode Tanggal + Tipe Swakelola -->
                    <div class="filter-row">
                        <!-- Date Range Filter -->
                        <div class="date-range-group">
                            <label><i class="fas fa-calendar-alt"></i> Periode Tanggal</label>
                            <div class="date-range-container">
                                <input type="date" name="tanggal_awal" 
                                    value="<?= htmlspecialchars($_GET['tanggal_awal'] ?? '') ?>"
                                    max="<?= date('Y-m-d') ?>"
                                    placeholder="Tanggal Mulai">
                                <span class="date-separator">S/D</span>
                                <input type="date" name="tanggal_akhir" 
                                    value="<?= htmlspecialchars($_GET['tanggal_akhir'] ?? '') ?>"
                                    max="<?= date('Y-m-d') ?>"
                                    placeholder="Tanggal Akhir">
                            </div>
                        </div>

                        <div class="filter-group">
                            <label><i class="fas fa-tools"></i> Tipe Swakelola</label>
                            <select name="tipe_swakelola">
                                <option value="">Semua Tipe</option>
                                <?php if (isset($options['options']['tipe_swakelola']) && is_array($options['options']['tipe_swakelola'])): ?>
                                    <?php foreach ($options['options']['tipe_swakelola'] as $tipe): ?>
                                        <option value="<?= htmlspecialchars($tipe) ?>" 
                                            <?= ($_GET['tipe_swakelola'] ?? '') == $tipe ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($tipe) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="" disabled>Tidak ada data tipe swakelola</option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Baris 2: KLPD + Satuan Kerja + Range Pagu -->
                    <div class="filter-row">
                        <div class="filter-group">
                            <label><i class="fas fa-building"></i> KLPD</label>
                            <select name="klpd">
                                <option value="">Semua KLPD</option>
                                <?php if (isset($options['options']['klpd']) && is_array($options['options']['klpd'])): ?>
                                    <?php foreach ($options['options']['klpd'] as $klpd): ?>
                                        <option value="<?= htmlspecialchars($klpd) ?>" 
                                            <?= ($_GET['klpd'] ?? '') == $klpd ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($klpd) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="" disabled>Tidak ada data KLPD</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label><i class="fas fa-sitemap"></i> Satuan Kerja</label>
                            <select name="satuan_kerja">
                                <option value="">Semua Satuan Kerja</option>
                                <?php if (isset($options['options']['satuan_kerja']) && is_array($options['options']['satuan_kerja'])): ?>
                                    <?php foreach ($options['options']['satuan_kerja'] as $satker): ?>
                                        <option value="<?= htmlspecialchars($satker) ?>" 
                                            <?= ($_GET['satuan_kerja'] ?? '') == $satker ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($satker) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="" disabled>Tidak ada data satuan kerja</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label><i class="fas fa-money-bill-wave"></i> Range Pagu (Rp)</label>
                            <div class="pagu-range-container">
                                <input type="number" name="pagu_min" 
                                    value="<?= htmlspecialchars($_GET['pagu_min'] ?? '') ?>"
                                    placeholder="Min Pagu" min="0" step="1000000">
                                <span class="pagu-separator">S/D</span>
                                <input type="number" name="pagu_max" 
                                    value="<?= htmlspecialchars($_GET['pagu_max'] ?? '') ?>"
                                    placeholder="Max Pagu" min="0" step="1000000">
                            </div>
                        </div>
                    </div>

                    <!-- Baris 3: Pencarian + Limit Data -->
                    <div class="filter-row">
                        <div class="filter-group">
                            <label><i class="fas fa-search"></i> Pencarian</label>
                            <div class="search-input-wrapper">
                                <i class="fas fa-search"></i>
                                <input type="text" name="search" 
                                    placeholder="Cari paket, lokasi, atau satuan kerja..." 
                                    value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                                    maxlength="100">
                            </div>
                        </div>
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

        <!-- Results Section -->
        <div class="results-section">
            <div class="results-header">
                <div>
                    <div class="results-title">
                        <i class="fas fa-table"></i> Hasil Pencarian Data Swakelola
                    </div>
                    <?php if ($data && isset($data['success']) && $data['success']): ?>
                    <div class="results-subtitle">
                        <strong>Menampilkan <?= count($data['data']) ?> data swakelola</strong>
                        <?php if (!empty($_GET['tanggal_awal']) && !empty($_GET['tanggal_akhir'])): ?>
                        <br><small class="text-muted">
                            <i class="fas fa-calendar"></i> Periode: <?= date('d/m/Y', strtotime($_GET['tanggal_awal'])) ?> - <?= date('d/m/Y', strtotime($_GET['tanggal_akhir'])) ?>
                        </small>
                        <?php endif; ?>
                        <?php 
                        $activeFilters = array_filter($_GET, function($value, $key) { 
                            return $value !== '' && $value !== null && $key !== 'limit' && $key !== 'page'; 
                        }, ARRAY_FILTER_USE_BOTH);
                        if (!empty($activeFilters)): 
                        ?>
                        <br><small style="color: #dc3545;">
                            <i class="fas fa-filter"></i> Filter aktif: 
                            <?php 
                            $filterLabels = [
                                'tanggal_awal' => 'Tanggal Mulai',
                                'tanggal_akhir' => 'Tanggal Akhir', 
                                'tipe_swakelola' => 'Tipe',
                                'klpd' => 'KLPD',
                                'satuan_kerja' => 'Satuan Kerja',
                                'pagu_min' => 'Min Pagu',
                                'pagu_max' => 'Max Pagu',
                                'search' => 'Pencarian'
                            ];
                            $activeFilterNames = array_map(function($key) use ($filterLabels) {
                                return $filterLabels[$key] ?? $key;
                            }, array_keys($activeFilters));
                            echo implode(', ', $activeFilterNames);
                            ?>
                        </small>
                        <?php endif; ?>
                    </div>
                    <?php elseif ($data && isset($data['success']) && !$data['success']): ?>
                    <div class="results-subtitle">
                        <span style="color: #dc3545;">
                            <i class="fas fa-exclamation-triangle"></i> 
                            <?= htmlspecialchars($data['message'] ?? 'Terjadi kesalahan dalam mengambil data') ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php if ($data && isset($data['pagination']) && $data['pagination']['total_pages'] > 1): ?>
                <div class="pagination">
                    <?php if ($data['pagination']['has_prev']): ?>
                    <button onclick="changePage(<?= $data['pagination']['current_page'] - 1 ?>)" title="Halaman Sebelumnya">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <?php endif; ?>
                    
                    <?php 
                    $currentPage = $data['pagination']['current_page'];
                    $totalPages = $data['pagination']['total_pages'];
                    $startPage = max(1, $currentPage - 2);
                    $endPage = min($totalPages, $currentPage + 2);
                    ?>
                    
                    <?php if ($startPage > 1): ?>
                    <button onclick="changePage(1)">1</button>
                    <?php if ($startPage > 2): ?>
                    <span style="padding: 0 5px; color: #6c757d;">...</span>
                    <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <button onclick="changePage(<?= $i ?>)" <?= $i == $currentPage ? 'class="active"' : '' ?>>
                        <?= $i ?>
                    </button>
                    <?php endfor; ?>
                    
                    <?php if ($endPage < $totalPages): ?>
                    <?php if ($endPage < $totalPages - 1): ?>
                    <span style="padding: 0 5px; color: #6c757d;">...</span>
                    <?php endif; ?>
                    <button onclick="changePage(<?= $totalPages ?>)"><?= $totalPages ?></button>
                    <?php endif; ?>
                    
                    <?php if ($data['pagination']['has_next']): ?>
                    <button onclick="changePage(<?= $data['pagination']['current_page'] + 1 ?>)" title="Halaman Selanjutnya">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($data && isset($data['success']) && $data['success'] && isset($data['data']) && count($data['data']) > 0): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 300px;">
                                <i class="fas fa-box"></i> Paket Swakelola
                            </th>
                            <th style="width: 140px;">
                                <i class="fas fa-money-bill-wave"></i> Pagu (Rp)
                            </th>
                            <th style="width: 140px;">
                                <i class="fas fa-tools"></i> Tipe Swakelola
                            </th>
                            <th style="width: 120px;">
                                <i class="fas fa-calendar"></i> Pemilihan
                            </th>
                            <th style="width: 140px;">
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
                                    <?= htmlspecialchars($row['Paket'] ?? $row['paket'] ?? 'N/A') ?>
                                </div>
                                <div class="small-text">
                                    <i class="fas fa-id-card"></i> ID: <?= htmlspecialchars($row['ID'] ?? $row['id'] ?? 'N/A') ?>
                                </div>
                                <?php 
                                $pemilihanDate = $row['Pemilihan'] ?? $row['pemilihan'] ?? null;
                                if ($pemilihanDate && $pemilihanDate !== '0000-00-00'):
                                ?>
                                <div class="small-text">
                                    <i class="fas fa-clock"></i> <?= date('d/m/Y', strtotime($pemilihanDate)) ?>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td class="price">
                                <?php
                                $pagu = $row['Pagu_Rp'] ?? $row['pagu'] ?? $row['pagu_rp'] ?? 0;
                                if (isset($row['Pagu_Formatted'])) {
                                    echo htmlspecialchars($row['Pagu_Formatted']);
                                } elseif (is_numeric($pagu) && $pagu > 0) {
                                    echo 'Rp ' . number_format($pagu, 0, ',', '.');
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </td>
                            <td>
                                <?php $tipeSwakelola = $row['Tipe_Swakelola'] ?? $row['tipe_swakelola'] ?? 'N/A'; ?>
                                <span class="badge <?= $tipeSwakelola === 'N/A' ? 'badge-secondary' : 'badge-success' ?>">
                                    <?= htmlspecialchars($tipeSwakelola) ?>
                                </span>
                            </td>
                            <td>
                                <small>
                                    <?php
                                    $pemilihanDisplay = $row['Pemilihan'] ?? $row['pemilihan'] ?? 'N/A';
                                    if ($pemilihanDisplay !== 'N/A' && $pemilihanDisplay !== '0000-00-00') {
                                        echo date('d/m/Y', strtotime($pemilihanDisplay));
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </small>
                            </td>
                            <td><small><?= htmlspecialchars($row['KLPD'] ?? $row['klpd'] ?? 'N/A') ?></small></td>
                            <td><small><?= htmlspecialchars($row['Satuan_Kerja'] ?? $row['satuan_kerja'] ?? 'N/A') ?></small></td>
                            <td><small><?= htmlspecialchars($row['Lokasi'] ?? $row['lokasi'] ?? 'N/A') ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (isset($data['pagination'])): ?>
            <div class="table-footer">
                <div>
                    <strong><i class="fas fa-info-circle"></i> Informasi Halaman:</strong>
                    Halaman <?= $data['pagination']['current_page'] ?? 1 ?> 
                    dari <?= $data['pagination']['total_pages'] ?? 1 ?>
                </div>
                <div>
                    <strong>Total Data: <?= $data['pagination']['total_records'] ?? count($data['data']) ?></strong> swakelola
                </div>
            </div>
            <?php endif; ?>

            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-search-minus"></i>
                <p><strong>Tidak ada data swakelola yang ditemukan</strong></p>
                <small class="text-muted">
                    <?php if (!empty($activeFilters)): ?>
                        Coba ubah atau hapus filter yang Anda gunakan
                    <?php else: ?>
                        Sistem mungkin belum memiliki data atau terjadi masalah koneksi
                    <?php endif; ?>
                </small>
            </div>
            <?php endif; ?>
            
        </div>
    </div>

    <script>
    // JavaScript untuk interaktivitas - Enhanced version
    document.addEventListener('DOMContentLoaded', function() {
        // Enhanced date range validation
        const tanggalAwal = document.querySelector('input[name="tanggal_awal"]');
        const tanggalAkhir = document.querySelector('input[name="tanggal_akhir"]');
        
        if (tanggalAwal && tanggalAkhir) {
            tanggalAwal.addEventListener('change', function() {
                tanggalAkhir.min = this.value;
                if (tanggalAkhir.value && tanggalAkhir.value < this.value) {
                    tanggalAkhir.value = this.value;
                }
                validateDateRange();
            });
            
            tanggalAkhir.addEventListener('change', function() {
                tanggalAwal.max = this.value;
                if (tanggalAwal.value && tanggalAwal.value > this.value) {
                    tanggalAwal.value = this.value;
                }
                validateDateRange();
            });
        }
        
        function validateDateRange() {
            const awal = tanggalAwal.value;
            const akhir = tanggalAkhir.value;
            
            if (awal && akhir) {
                const startDate = new Date(awal);
                const endDate = new Date(akhir);
                const diffTime = Math.abs(endDate - startDate);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                
                // Warning untuk periode terlalu lama
                if (diffDays > 365) {
                    const warning = document.createElement('small');
                    warning.style.color = '#ffc107';
                    warning.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Periode lebih dari 1 tahun mungkin memerlukan waktu loading lama';
                    
                    // Remove existing warning
                    const existingWarning = document.querySelector('.date-warning');
                    if (existingWarning) {
                        existingWarning.remove();
                    }
                    
                    warning.className = 'date-warning';
                    tanggalAkhir.parentElement.parentElement.appendChild(warning);
                } else {
                    const existingWarning = document.querySelector('.date-warning');
                    if (existingWarning) {
                        existingWarning.remove();
                    }
                }
            }
        }
        
        // Enhanced pagu range validation
        const paguMin = document.querySelector('input[name="pagu_min"]');
        const paguMax = document.querySelector('input[name="pagu_max"]');
        
        if (paguMin && paguMax) {
            paguMin.addEventListener('input', function() {
                const minVal = parseInt(this.value) || 0;
                const maxVal = parseInt(paguMax.value) || 0;
                
                if (maxVal > 0 && minVal > maxVal) {
                    this.setCustomValidity('Pagu minimum tidak boleh lebih besar dari pagu maksimum');
                } else {
                    this.setCustomValidity('');
                }
            });
            
            paguMax.addEventListener('input', function() {
                const minVal = parseInt(paguMin.value) || 0;
                const maxVal = parseInt(this.value) || 0;
                
                if (minVal > 0 && maxVal > 0 && maxVal < minVal) {
                    this.setCustomValidity('Pagu maksimum tidak boleh lebih kecil dari pagu minimum');
                } else {
                    this.setCustomValidity('');
                }
            });
        }
        
        // Enhanced search input functionality
        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput) {
            let searchTimeout;
            
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                
                const wrapper = this.closest('.search-input-wrapper');
                const icon = wrapper.querySelector('i');
                
                if (this.value.trim()) {
                    icon.className = 'fas fa-times';
                    icon.style.cursor = 'pointer';
                    icon.onclick = () => {
                        this.value = '';
                        icon.className = 'fas fa-search';
                        icon.style.cursor = 'default';
                        icon.onclick = null;
                        this.focus();
                    };
                    
                    // Auto-search after 1 second (optional)
                    /*
                    searchTimeout = setTimeout(() => {
                        if (this.value.length > 2) {
                            this.form.submit();
                        }
                    }, 1000);
                    */
                } else {
                    icon.className = 'fas fa-search';
                    icon.style.cursor = 'default';
                    icon.onclick = null;
                }
            });
            
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.form.submit();
                }
            });
        }
        
        // Enhanced form validation
        const form = document.querySelector('#filterForm');
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('.search-btn');
            const originalText = submitBtn.innerHTML;
            
            // Basic validation
            let isValid = true;
            let errorMessage = '';
            
            // Date validation
            const tanggalAwal = this.querySelector('input[name="tanggal_awal"]').value;
            const tanggalAkhir = this.querySelector('input[name="tanggal_akhir"]').value;
            
            if (tanggalAwal && tanggalAkhir && tanggalAwal > tanggalAkhir) {
                isValid = false;
                errorMessage = 'Tanggal awal tidak boleh lebih besar dari tanggal akhir!';
            }
            
            // Pagu validation
            const paguMin = parseInt(this.querySelector('input[name="pagu_min"]').value) || 0;
            const paguMax = parseInt(this.querySelector('input[name="pagu_max"]').value) || 0;
            
            if (paguMin > 0 && paguMax > 0 && paguMin > paguMax) {
                isValid = false;
                errorMessage = 'Pagu minimum tidak boleh lebih besar dari pagu maksimum!';
            }
            
            // Search length validation
            const searchValue = this.querySelector('input[name="search"]').value.trim();
            if (searchValue.length > 0 && searchValue.length < 3) {
                isValid = false;
                errorMessage = 'Pencarian harus minimal 3 karakter!';
            }
            
            if (!isValid) {
                e.preventDefault();
                alert(errorMessage);
                return false;
            }
            
            // Show loading state
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mencari...';
            submitBtn.disabled = true;
            
            // Reset button state if form doesn't redirect
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 10000);
        });
        
        // Number formatting for pagu inputs
        document.querySelectorAll('input[name="pagu_min"], input[name="pagu_max"]').forEach(input => {
            input.addEventListener('blur', function() {
                const value = this.value.replace(/[^\d]/g, '');
                if (value && parseInt(value) > 0) {
                    // Format display
                    const formatted = parseInt(value).toLocaleString('id-ID');
                    this.dataset.originalValue = value;
                    this.title = 'Rp ' + formatted;
                }
            });
        });
        
        // Auto-scroll to results when filters are applied
        const urlParams = new URLSearchParams(window.location.search);
        const hasFilters = Array.from(urlParams.keys()).some(key => 
            key !== 'limit' && key !== 'page' && urlParams.get(key)
        );
        
        if (hasFilters) {
            setTimeout(() => {
                const resultsSection = document.querySelector('.results-section');
                if (resultsSection) {
                    resultsSection.scrollIntoView({ 
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }, 500);
        }
        
        // Enhanced table interactions
        document.querySelectorAll('tbody tr').forEach((row, index) => {
            // Add row number for accessibility
            row.setAttribute('data-row', index + 1);
            
            // Hover effects
            row.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
            });
            
            row.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '';
            });
        });
        
        // Add keyboard navigation for pagination
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey) {
                const currentPage = <?= $data['pagination']['current_page'] ?? 1 ?>;
                const totalPages = <?= $data['pagination']['total_pages'] ?? 1 ?>;
                
                if (e.key === 'ArrowLeft' && currentPage > 1) {
                    e.preventDefault();
                    changePage(currentPage - 1);
                } else if (e.key === 'ArrowRight' && currentPage < totalPages) {
                    e.preventDefault();
                    changePage(currentPage + 1);
                }
            }
        });
    });

    // Enhanced pagination function
    function changePage(page) {
        const url = new URL(window.location);
        url.searchParams.set('page', page);
        
        // Show loading indicator
        const pagination = document.querySelector('.pagination');
        if (pagination) {
            pagination.style.opacity = '0.5';
        }
        
        window.location.href = url.toString();
    }

    // Enhanced reset form function
    function resetForm() {
        const form = document.querySelector('#filterForm');
        const inputs = form.querySelectorAll('input, select');
        
        // Confirm reset if there are values
        const hasValues = Array.from(inputs).some(input => {
            return input.value && input.value !== '' && input.name !== 'limit';
        });
        
        if (hasValues) {
            const confirmReset = confirm('Apakah Anda yakin ingin menghapus semua filter?');
            if (!confirmReset) return;
        }
        
        inputs.forEach(input => {
            if (input.type === 'text' || input.type === 'date' || input.type === 'number') {
                input.value = '';
                input.setCustomValidity(''); // Clear validation messages
            } else if (input.tagName === 'SELECT' && input.name !== 'limit') {
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
        
        // Remove warnings
        const warnings = document.querySelectorAll('.date-warning');
        warnings.forEach(warning => warning.remove());
        
        // Focus on first input
        const firstInput = form.querySelector('input[name="tanggal_awal"]');
        if (firstInput) firstInput.focus();
    }

    // Add export functionality (if needed)
    function exportData(format = 'csv') {
        const currentUrl = new URL(window.location);
        currentUrl.searchParams.set('export', format);
        currentUrl.searchParams.delete('page'); // Remove pagination for export
        
        window.open(currentUrl.toString(), '_blank');
    }

    // Add print functionality
    function printResults() {
        const printWindow = window.open('', '_blank');
        const tableContent = document.querySelector('.table-container').innerHTML;
        const currentFilters = document.querySelector('.results-subtitle').innerHTML;
        
        printWindow.document.write(`
            <html>
            <head>
                <title>Data Swakelola - Hasil Pencarian</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    table { width: 100%; border-collapse: collapse; font-size: 12px; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; font-weight: bold; }
                    .header { margin-bottom: 20px; }
                    .filters { margin-bottom: 15px; font-size: 14px; }
                    @media print {
                        body { margin: 0; }
                        .no-print { display: none; }
                    }
                </style>
            </head>
            <body>
                <div class="header">
                    <h2>Data Swakelola - SIP BANAR</h2>
                    <div class="filters">${currentFilters}</div>
                    <p><small>Dicetak pada: ${new Date().toLocaleString('id-ID')}</small></p>
                </div>
                ${tableContent}
            </body>
            </html>
        `);
        
        printWindow.document.close();
        printWindow.print();
    }
    </script>

    <?php 
    // Include footer
    include '../../navbar/footer.php'; 
    ?>  