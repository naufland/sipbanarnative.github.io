<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/ImportModel.php';

try {
    $model = new ImportModel();
    $tables = $model->getAllTables();
    
    echo json_encode([
        'success' => true,
        'tables' => $tables
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>