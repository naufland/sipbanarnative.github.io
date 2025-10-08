<?php
require_once __DIR__ . '/../config/database.php';

class ImportModel {
    private $conn;
    private $db;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    // Mendapatkan semua nama tabel dari database
    public function getAllTables() {
        try {
            $query = "SHOW TABLES";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            return $tables;
        } catch(PDOException $e) {
            return [];
        }
    }

    // Mendapatkan struktur kolom dari tabel tertentu
    public function getTableColumns($tableName) {
        try {
            $query = "SHOW COLUMNS FROM " . $tableName;
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $columns;
        } catch(PDOException $e) {
            return [];
        }
    }

    // Import data ke tabel
    public function importData($tableName, $data, $tahun = null, $bulan = null) {
        try {
            $this->conn->beginTransaction();
            
            $columns = $this->getTableColumns($tableName);
            $columnNames = array_map('strtolower', array_column($columns, 'Field'));
            
            $inserted = 0;
            $errors = [];

            foreach ($data as $index => $row) {
                try {
                    // Filter kolom yang ada di tabel
                    $validData = [];
                    foreach ($row as $key => $value) {
                        $key = strtolower(trim(str_replace(' ', '_', $key)));
                        if (in_array($key, $columnNames)) {
                            $validData[$key] = $value;
                        }
                    }

                    // Tambahkan tahun dan bulan jika kolom ada
                    if ($tahun !== null && in_array('tahun', $columnNames)) {
                        $validData['tahun'] = $tahun;
                    }
                    if ($bulan !== null && in_array('bulan', $columnNames)) {
                        $validData['bulan'] = $bulan;
                    }

                    if (!empty($validData)) {
                        $fields = implode(', ', array_keys($validData));
                        $placeholders = ':' . implode(', :', array_keys($validData));
                        
                        $query = "INSERT INTO " . $tableName . " (" . $fields . ") VALUES (" . $placeholders . ")";
                        $stmt = $this->conn->prepare($query);
                        
                        foreach ($validData as $key => $value) {
                            $stmt->bindValue(':' . $key, $value);
                        }
                        
                        $stmt->execute();
                        $inserted++;
                    }
                } catch(PDOException $e) {
                    $errors[] = "Baris " . ($index + 2) . ": " . $e->getMessage();
                }
            }

            $this->conn->commit();
            
            return [
                'success' => true,
                'inserted' => $inserted,
                'total' => count($data),
                'errors' => $errors
            ];
            
        } catch(PDOException $e) {
            $this->conn->rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    // Cek apakah tabel memiliki kolom tertentu
    public function hasColumn($tableName, $columnName) {
        $columns = $this->getTableColumns($tableName);
        $columnNames = array_column($columns, 'Field');
        return in_array($columnName, $columnNames);
    }
}
?>