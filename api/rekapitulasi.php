<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once '../config/database.php';
require_once '../includes/RekapitulasiModel.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $model = new RekapitulasiModel
($db);

    // Ambil data rekapitulasi
    $rekap_data = $model->getRekapPerencanaan();

    if ($rekap_data) {
        echo json_encode([
            'success' => true,
            'data' => $rekap_data
        ]);
    } else {
        throw new Exception("Tidak dapat mengambil data rekapitulasi.");
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan: ' . $e->getMessage()
    ]);
}
?>