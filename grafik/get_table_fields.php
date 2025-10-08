<?php
// Memastikan semua error ditampilkan untuk debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/database.php';

header('Content-Type: application/json');

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['table_name'])) {
    $database = new Database();
    $conn = $database->getConnection();
    
    $tableName = $_POST['table_name'];
    
    try {
        $query = "DESCRIBE " . $tableName;
        $stmt = $conn->prepare($query);
        $stmt->execute();
        
        $fields = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Skip auto increment fields
            if($row['Extra'] != 'auto_increment') {
                $fields[] = $row['Field'];
            }
        }
        
        echo json_encode([
            'success' => true,
            'fields' => $fields
        ]);
    } catch(PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
}
?>