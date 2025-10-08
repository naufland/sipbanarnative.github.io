<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../vendor/autoload.php'; // PhpSpreadsheet
require_once __DIR__ . '/../includes/ImportModel.php';


use PhpOffice\PhpSpreadsheet\IOFactory;

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

    if (empty($tableName)) {
        throw new Exception('Nama tabel harus dipilih');
    }

    $file = $_FILES['excel_file']['tmp_name'];
    $fileExtension = pathinfo($_FILES['excel_file']['name'], PATHINFO_EXTENSION);
    
    if (!in_array(strtolower($fileExtension), ['xlsx', 'xls', 'csv'])) {
        throw new Exception('Format file harus Excel (.xlsx, .xls) atau CSV');
    }

    // Load spreadsheet
    $spreadsheet = IOFactory::load($file);
    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray();

    if (count($rows) < 2) {
        throw new Exception('File Excel kosong atau tidak memiliki data');
    }

    // Ambil header (baris pertama)
    $headers = array_shift($rows);
    $headers = array_map('trim', $headers);
    
    // Konversi data ke array associative
    $data = [];
    foreach ($rows as $row) {
        $rowData = [];
        foreach ($headers as $index => $header) {
            if (!empty($header)) {
                $rowData[$header] = $row[$index] ?? null;
            }
        }
        if (!empty(array_filter($rowData))) { // Skip baris kosong
            $data[] = $rowData;
        }
    }

    // Import data
    $model = new ImportModel();
    $result = $model->importData($tableName, $data, $tahun, $bulan);

    if ($result['success']) {
        $response['success'] = true;
        $response['message'] = "Berhasil import {$result['inserted']} dari {$result['total']} data";
        if (!empty($result['errors'])) {
            $response['errors'] = $result['errors'];
            $response['message'] .= " (ada " . count($result['errors']) . " error)";
        }
    } else {
        throw new Exception($result['message']);
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>