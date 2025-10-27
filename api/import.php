<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../vendor/autoload.php'; // PhpSpreadsheet
require_once __DIR__ . '/../includes/ImportModel.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * BARU: Fungsi Normalisasi Kolom
 * Membersihkan nama kolom ke format standar (lowercase, underscore).
 * Contoh: "Pagu (Rp)" akan menjadi "pagu_rp".
 * Contoh: "Nilai Kontrak" akan menjadi "nilai_kontrak".
 */
function normalize_column_name($name) {
    // 1. Konversi null ke string kosong sebelum trim
    $name = (string)($name ?? '');
    
    // 2. Ubah ke huruf kecil dan hapus spasi di awal/akhir
    $name = strtolower(trim($name));
    
    // 3. Hapus semua karakter yang bukan huruf, angka, spasi, atau underscore
    // Ini akan menghapus tanda kurung (), titik ., dll.
    $name = preg_replace('/[^\w\s_]/', '', $name);
    
    // 4. Ganti satu atau lebih spasi/underscore dengan satu underscore
    $name = preg_replace('/[\s_]+/', '_', $name);
    
    // 5. Hapus underscore di awal atau akhir (jika ada)
    $name = trim($name, '_');
    
    return $name;
}


$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }

    if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File tidak ditemukan atau error saat upload');
    }

    $tableName = $_POST['table_name'] ?? '';
    $tahun = $_POST['tahun'] ?? null;
    $bulan = $_POST['bulan'] ?? null;
    $perubahan = $_POST['perubahan'] ?? null;

    if (empty($tableName)) {
        throw new Exception('Nama tabel harus dipilih');
    }

    $file = $_FILES['excel_file']['tmp_name'];
    $fileExtension = pathinfo($_FILES['excel_file']['name'], PATHINFO_EXTENSION);
    
    if (!in_array(strtolower($fileExtension), ['xlsx', 'xls', 'csv'])) {
        throw new Exception('Format file harus Excel (.xlsx, .xls) atau CSV');
    }

    // BARU: Inisialisasi model lebih awal untuk mendapatkan kolom DB
    $model = new ImportModel();

    // BARU: Ambil daftar kolom ASLI dari database
    $db_columns = $model->getColumns($tableName);
    if (empty($db_columns)) {
        throw new Exception("Tidak dapat mengambil struktur kolom untuk tabel: $tableName. Pastikan tabel ada.");
    }

    // BARU: Buat "Peta" normalisasi untuk kolom DB
    // Ini mengubah [ 'id', 'pagu_rp', 'nilai_kontrak' ]
    // menjadi [ 'id' => 'id', 'pagu_rp' => 'pagu_rp', 'nilai_kontrak' => 'nilai_kontrak' ]
    $normalized_db_map = [];
    foreach ($db_columns as $db_col) {
        $normalized_db_map[normalize_column_name($db_col)] = $db_col;
    }
    // Tambahkan kolom opsional jika tidak ada di tabel
    if (!isset($normalized_db_map['tahun'])) $normalized_db_map['tahun'] = 'tahun';
    if (!isset($normalized_db_map['bulan'])) $normalized_db_map['bulan'] = 'bulan';
    if (!isset($normalized_db_map['perubahan'])) $normalized_db_map['perubahan'] = 'perubahan';


    // Load spreadsheet
    $spreadsheet = IOFactory::load($file);
    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray();

    if (count($rows) < 2) {
        throw new Exception('File Excel kosong atau tidak memiliki data');
    }

    // Ambil header (baris pertama)
    $excel_headers = array_shift($rows);
    
    // BARU: Buat Peta pencocokan antara header Excel dan kolom DB
    $header_map = []; // Ini akan menyimpan peta: [ 'Pagu (Rp)' => 'pagu_rp' ]
    $found_headers = []; // Ini untuk melacak header Excel apa saja yang kita temukan

    foreach ($excel_headers as $index => $excel_header_raw) {
        $excel_header_clean = (string)($excel_header_raw ?? '');
        if (empty(trim($excel_header_clean))) continue; // Lewati header kosong

        $normalized_excel_header = normalize_column_name($excel_header_clean);
        
        // Cek apakah header Excel yang sudah dinormalisasi ada di peta kolom DB kita
        if (isset($normalized_db_map[$normalized_excel_header])) {
            // Cocok!
            $db_col_name = $normalized_db_map[$normalized_excel_header];
            $header_map[$index] = $db_col_name; // Peta: [ index_ke_0 => 'nama_kolom_db' ]
            $found_headers[] = $excel_header_clean;
        }
    }

    if (empty($header_map)) {
        throw new Exception('Tidak ada header Excel yang cocok dengan kolom database. Pastikan baris pertama Excel berisi nama kolom.');
    }
    
    // Konversi data ke array associative
    $data = [];
    foreach ($rows as $row) {
        $rowData = [];
        $is_row_empty = true;

        // BARU: Gunakan $header_map untuk memetakan data
        foreach ($header_map as $index => $db_col_name) {
            $cellValue = $row[$index] ?? null;
            $rowData[$db_col_name] = $cellValue; // Langsung gunakan nama kolom DB sebagai key
            
            if ($cellValue !== null && $cellValue !== '') {
                $is_row_empty = false;
            }
        }

        // Tambahkan data opsional (Tahun, Bulan, Perubahan)
        // Ini akan menimpa data dari Excel jika kolomnya juga ada,
        // atau menambahkannya jika tidak ada.
        if (!empty($tahun)) $rowData['tahun'] = $tahun;
        if (!empty($bulan)) $rowData['bulan'] = $bulan;
        if (!empty($perubahan)) $rowData['perubahan'] = $perubahan;
        
        if (!$is_row_empty) {
            $data[] = $rowData;
        }
    }

    if (empty($data)) {
        throw new Exception('Tidak ada data valid untuk diimport');
    }

    // Import data
    // $model sudah di-inisialisasi di atas
    $result = $model->importData($tableName, $data);

    if ($result['success']) {
        $response['success'] = true;
        $response['message'] = "Berhasil import {$result['inserted']} dari {$result['total']} data";
        $response['message'] .= ". Header Excel yang terdeteksi: " . implode(', ', $found_headers); // Pesan debug
        if (!empty($result['errors'])) {
            $response['errors'] = $result['errors'];
            $response['message'] .= " (ada " . count($result['errors']) . " error)";
        }
    } else {
        throw new Exception($result['message']);
    }

} catch (Exception $e) {
    // BARU: Tangkap error PDO untuk pesan yang lebih jelas
    if ($e instanceof PDOException) {
        $response['message'] = "Database Error: " . $e->getMessage();
    } else {
        $response['message'] = $e->getMessage();
    }
}

echo json_encode($response);
?>