<?php
// Memastikan semua error ditampilkan untuk debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json');

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['excel_file'])) {
    $file = $_FILES['excel_file'];
    
    // Check file upload errors
    if($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode([
            'success' => false,
            'message' => 'Error uploading file'
        ]);
        exit;
    }
    
    // Check file extension
    $allowedExtensions = ['xls', 'xlsx'];
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if(!in_array($fileExtension, $allowedExtensions)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid file type. Only .xls and .xlsx allowed'
        ]);
        exit;
    }
    
    try {
        // Load Excel file
        $spreadsheet = IOFactory::load($file['tmp_name']);
        $worksheet = $spreadsheet->getActiveSheet();
        
        // Get first row (header)
        $highestColumn = $worksheet->getHighestColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
        
        $columns = [];
        
        for($col = 1; $col <= $highestColumnIndex; $col++) {
            $cellCoordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . '1';
            $value = $worksheet->getCell($cellCoordinate)->getValue();
            
            if(!empty($value)) {
                $columns[] = trim($value);
            } else {
                // If empty, use column letter
                $columns[] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            }
        }
        
        echo json_encode([
            'success' => true,
            'columns' => $columns
        ]);
    } catch(Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error reading Excel file: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No file uploaded'
    ]);
}
?>