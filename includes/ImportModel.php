<?php
require_once __DIR__ . '/../config/database.php';

class ImportModel
{
    private $conn;
    private $db;

    public function __construct()
    {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    // Mendapatkan semua nama tabel dari database
    public function getAllTables()
    {
        try {
            $query = "SHOW TABLES";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            return $tables;
        } catch (PDOException $e) {
            // Sebaiknya log error di sini
            error_log("Error getting tables: " . $e->getMessage());
            return [];
        }
    }

    /**
     * BARU: Nama fungsi diubah dari getTableColumns menjadi getColumns
     * Mengambil struktur kolom dari tabel tertentu.
     *
     * @param string $tableName Nama tabel
     * @return array Daftar nama kolom (Field)
     * @throws Exception Jika tabel tidak ditemukan atau error
     */
    public function getColumns($tableName)
    {
        try {
            // 1. Validasi dulu tabelnya ada
            $checkStmt = $this->conn->prepare("SHOW TABLES LIKE ?");
            $checkStmt->execute([$tableName]);
            if ($checkStmt->rowCount() == 0) {
                throw new Exception("Tabel '$tableName' tidak ditemukan di database.");
            }

            // 2. Ambil kolomnya
            // Menggunakan DESCRIBE lebih standar dan aman
            $stmt = $this->conn->prepare("DESCRIBE " . $tableName);
            $stmt->execute();
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0); // Ambil hanya kolom 'Field'
            
            if (empty($columns)) {
                 throw new Exception("Tidak ada kolom ditemukan untuk tabel '$tableName'.");
            }
            
            return $columns;

        } catch (PDOException $e) {
            // Log error asli untuk debugging
            error_log("PDOException in getColumns for table '$tableName': " . $e->getMessage());
            throw new Exception("Gagal mengambil kolom dari tabel '$tableName': " . $e->getMessage());
        } catch (Exception $e) {
            // Tangkap exception lain (misalnya dari validasi tabel)
             error_log("Exception in getColumns for table '$tableName': " . $e->getMessage());
             throw $e; // Lemparkan lagi exception asli
        }
    }


    /**
     * Import data ke tabel.
     * BARU: Parameter $tahun, $bulan, $perubahan dihapus.
     *
     * @param string $tableName Nama tabel tujuan.
     * @param array $data Data yang akan diimpor (array of associative arrays).
     * Key dari associative array HARUS sudah cocok dengan nama kolom DB.
     * @return array Hasil import ['success', 'inserted', 'total', 'errors'].
     */
    public function importData($tableName, $data)
    {
        try {
            // BARU: Ambil nama kolom DB menggunakan fungsi yang sudah diperbarui
            $columnDetails = $this->conn->query("DESCRIBE " . $tableName)->fetchAll(PDO::FETCH_ASSOC);
            $columnNames = array_column($columnDetails, 'Field');
            $columnNamesLower = array_map('strtolower', $columnNames); // Untuk pencocokan case-insensitive jika perlu

             // BARU: Dapatkan kolom primary key (asumsi hanya satu)
            $primaryKey = null;
            foreach ($columnDetails as $col) {
                if (isset($col['Key']) && strtoupper($col['Key']) === 'PRI') {
                    $primaryKey = $col['Field'];
                    break;
                }
            }


            $inserted = 0;
            $updated = 0; // BARU: Lacak update
            $errors = [];
            $totalProcessed = count($data); // Total baris data yang diterima

            $this->conn->beginTransaction();

            foreach ($data as $index => $row) {
                try {
                    // BARU: Filter data agar hanya key yang ada di $columnNames yang diproses
                    $validData = [];
                    foreach ($row as $key => $value) {
                        // Cari nama kolom asli (case-insensitive)
                        $colIndex = array_search(strtolower($key), $columnNamesLower);
                        if ($colIndex !== false) {
                            $actualColName = $columnNames[$colIndex]; // Dapatkan nama kolom asli (dengan case yang benar)
                            // Handle null/empty values
                            $validData[$actualColName] = ($value === null || $value === '') ? null : $value;
                        }
                    }

                    if (empty($validData)) {
                         $errors[] = "Baris " . ($index + 2) . ": Tidak ada data valid yang cocok dengan kolom tabel.";
                        continue; // Lewati baris ini jika tidak ada data yang cocok
                    }

                    // --- BARU: Logika UPSERT (Update or Insert) ---
                    $updateFields = [];
                    $bindValues = [];
                    
                    if ($primaryKey && isset($validData[$primaryKey]) && $validData[$primaryKey] !== null) {
                        // 1. Cek apakah data dengan primary key ini sudah ada
                        $checkQuery = "SELECT COUNT(*) FROM " . $tableName . " WHERE " . $primaryKey . " = ?";
                        $checkStmt = $this->conn->prepare($checkQuery);
                        $checkStmt->execute([$validData[$primaryKey]]);
                        $exists = $checkStmt->fetchColumn() > 0;

                        if ($exists) {
                            // 2. Jika ADA -> UPDATE
                            $setClauses = [];
                            foreach ($validData as $key => $value) {
                                if ($key !== $primaryKey) { // Jangan update primary key itu sendiri
                                    $setClauses[] = "`" . $key . "` = :" . $key; // Gunakan backtick
                                    $bindValues[':' . $key] = $value;
                                }
                            }
                            // Tambahkan primary key untuk klausa WHERE
                            $bindValues[':primaryKeyValue'] = $validData[$primaryKey]; 
                            
                            if (!empty($setClauses)) {
                                $query = "UPDATE " . $tableName . " SET " . implode(', ', $setClauses) . " WHERE `" . $primaryKey . "` = :primaryKeyValue";
                                $stmt = $this->conn->prepare($query);
                                $stmt->execute($bindValues);
                                $updated++;
                            } else {
                                // Tidak ada kolom lain untuk diupdate selain PK, lewati
                                $errors[] = "Baris " . ($index + 2) . ": Data dengan kunci '" . $validData[$primaryKey] . "' sudah ada, tidak ada kolom lain untuk diupdate.";
                            }
                            continue; // Lanjut ke baris berikutnya setelah update
                        }
                    } 
                    
                    // 3. Jika TIDAK ADA atau TIDAK ADA Primary Key -> INSERT
                    $fields = implode(', ', array_map(function($k) { return "`" . $k . "`"; }, array_keys($validData))); // Gunakan backtick
                    $placeholders = ':' . implode(', :', array_keys($validData));
                    
                    $query = "INSERT INTO " . $tableName . " (" . $fields . ") VALUES (" . $placeholders . ")";
                    $stmt = $this->conn->prepare($query);

                    foreach ($validData as $key => $value) {
                         $bindValues[':' . $key] = $value; // Siapkan value untuk binding
                    }
                    
                    $stmt->execute($bindValues);
                    $inserted++;
                    // --- Akhir Logika UPSERT ---

                } catch (PDOException $e) {
                    $errors[] = "Baris " . ($index + 2) . ": " . $e->getMessage() . " (SQL: " . ($query ?? 'N/A') . ")";
                } catch (Exception $e) { // Tangkap error lain
                     $errors[] = "Baris " . ($index + 2) . ": Terjadi error - " . $e->getMessage();
                }
            }

            $this->conn->commit();

            return [
                'success' => true,
                'inserted' => $inserted,
                'updated' => $updated, // BARU
                'total' => $totalProcessed,
                'errors' => $errors
            ];
        } catch (PDOException $e) {
            $this->conn->rollBack();
            // Log error asli
            error_log("PDOException during import transaction for table '$tableName': " . $e->getMessage());
            return [
                'success' => false,
                'message' => "Database transaction error: " . $e->getMessage()
            ];
         } catch (Exception $e) { // Tangkap error dari getColumns
             $this->conn->rollBack(); // Pastikan rollback jika error terjadi sebelum transaksi dimulai
             error_log("Exception during import setup for table '$tableName': " . $e->getMessage());
             return [
                 'success' => false,
                 'message' => "Import setup error: " . $e->getMessage()
             ];
         }
    }

    // Fungsi hasColumn tidak lagi diperlukan karena validasi kolom ada di getColumns
    // public function hasColumn($tableName, $columnName) ...
}
?>