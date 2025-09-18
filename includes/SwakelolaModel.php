<?php
class SwakelolaModel {
    private $conn;
    private $table_name = "swakelola"; // Sesuaikan dengan nama tabel Anda

    public function __construct($db) {
        $this->conn = $db;
    }

    // Ambil data utama dengan filter + pagination
    public function getPengadaanData($filters = [], $limit = 50, $offset = 0) {
        $sql = "SELECT * FROM " . $this->table_name . " WHERE 1=1";
        $params = [];

        // Filter tahun
        if (!empty($filters['tahun'])) {
            $sql .= " AND YEAR(Pemilihan) = :tahun"; 
            $params[':tahun'] = $filters['tahun'];
        }

        // Filter range tanggal
        if (!empty($filters['tanggal_awal']) && !empty($filters['tanggal_akhir'])) {
            $sql .= " AND Pemilihan BETWEEN :tanggal_awal AND :tanggal_akhir";
            $params[':tanggal_awal'] = $filters['tanggal_awal'];
            $params[':tanggal_akhir'] = $filters['tanggal_akhir'];
        }

        // Filter berdasarkan kolom yang disesuaikan dengan struktur database baru
        if (!empty($filters['tipe_swakelola'])) {
            $sql .= " AND Tipe_Swakelola = :tipe_swakelola";
            $params[':tipe_swakelola'] = $filters['tipe_swakelola'];
        }
        if (!empty($filters['klpd'])) {
            $sql .= " AND KLPD = :klpd";
            $params[':klpd'] = $filters['klpd'];
        }
        if (!empty($filters['satuan_kerja'])) {
            $sql .= " AND Satuan_Kerja = :satuan_kerja";
            $params[':satuan_kerja'] = $filters['satuan_kerja'];
        }
        if (!empty($filters['lokasi'])) {
            $sql .= " AND Lokasi = :lokasi";
            $params[':lokasi'] = $filters['lokasi'];
        }
        
        // Backward compatibility - tetap mendukung filter lama jika ada
        if (!empty($filters['jenis_pengadaan'])) {
            $sql .= " AND Tipe_Swakelola = :jenis_pengadaan";
            $params[':jenis_pengadaan'] = $filters['jenis_pengadaan'];
        }
        if (!empty($filters['usaha_kecil'])) {
            // Jika masih ada kolom ini di database, gunakan. Jika tidak, abaikan
            $sql .= " AND (Usaha_Kecil = :usaha_kecil OR 1=1)"; // Fallback jika kolom tidak ada
            $params[':usaha_kecil'] = $filters['usaha_kecil'];
        }
        if (!empty($filters['metode'])) {
            // Jika masih ada kolom ini di database, gunakan. Jika tidak, abaikan
            $sql .= " AND (Metode = :metode OR 1=1)"; // Fallback jika kolom tidak ada
            $params[':metode'] = $filters['metode'];
        }
        
        // Search functionality - diperluas untuk mencakup kolom baru
        if (!empty($filters['search'])) {
            $sql .= " AND (Paket LIKE :search OR Lokasi LIKE :search OR KLPD LIKE :search OR Satuan_Kerja LIKE :search OR Tipe_Swakelola LIKE :search)";
            $params[':search'] = "%" . $filters['search'] . "%";
        }

        $sql .= " ORDER BY No ASC LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Hitung total data (untuk pagination)
    public function getTotalCount($filters = []) {
        $sql = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE 1=1";
        $params = [];

        if (!empty($filters['tahun'])) {
            $sql .= " AND YEAR(Pemilihan) = :tahun";
            $params[':tahun'] = $filters['tahun'];
        }

        if (!empty($filters['tanggal_awal']) && !empty($filters['tanggal_akhir'])) {
            $sql .= " AND Pemilihan BETWEEN :tanggal_awal AND :tanggal_akhir";
            $params[':tanggal_awal'] = $filters['tanggal_awal'];
            $params[':tanggal_akhir'] = $filters['tanggal_akhir'];
        }

        if (!empty($filters['tipe_swakelola'])) {
            $sql .= " AND Tipe_Swakelola = :tipe_swakelola";
            $params[':tipe_swakelola'] = $filters['tipe_swakelola'];
        }
        if (!empty($filters['klpd'])) {
            $sql .= " AND KLPD = :klpd";
            $params[':klpd'] = $filters['klpd'];
        }
        if (!empty($filters['satuan_kerja'])) {
            $sql .= " AND Satuan_Kerja = :satuan_kerja";
            $params[':satuan_kerja'] = $filters['satuan_kerja'];
        }
        if (!empty($filters['lokasi'])) {
            $sql .= " AND Lokasi = :lokasi";
            $params[':lokasi'] = $filters['lokasi'];
        }
        
        // Backward compatibility
        if (!empty($filters['jenis_pengadaan'])) {
            $sql .= " AND Tipe_Swakelola = :jenis_pengadaan";
            $params[':jenis_pengadaan'] = $filters['jenis_pengadaan'];
        }
        if (!empty($filters['usaha_kecil'])) {
            $sql .= " AND (Usaha_Kecil = :usaha_kecil OR 1=1)";
            $params[':usaha_kecil'] = $filters['usaha_kecil'];
        }
        if (!empty($filters['metode'])) {
            $sql .= " AND (Metode = :metode OR 1=1)";
            $params[':metode'] = $filters['metode'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (Paket LIKE :search OR Lokasi LIKE :search OR KLPD LIKE :search OR Satuan_Kerja LIKE :search OR Tipe_Swakelola LIKE :search)";
            $params[':search'] = "%" . $filters['search'] . "%";
        }

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row['total'] ?? 0;
    }

    // Ambil nilai unik untuk dropdown - mendukung kolom lama dan baru
    public function getDistinctValues($column) {
        // Mapping kolom lama ke kolom baru untuk backward compatibility
        $columnMapping = [
            'Jenis_Pengadaan' => 'Tipe_Swakelola',
            'jenis_pengadaan' => 'Tipe_Swakelola'
        ];
        
        // Gunakan mapping jika tersedia
        $actualColumn = $columnMapping[$column] ?? $column;
        
        // Validasi kolom yang diizinkan
        $allowedColumns = [
            'Tipe_Swakelola', 'KLPD', 'Satuan_Kerja', 'Lokasi', 
            'Usaha_Kecil', 'Metode' // Tetap mendukung kolom lama jika masih ada
        ];
        
        if (!in_array($actualColumn, $allowedColumns)) {
            return [];
        }

        try {
            $sql = "SELECT DISTINCT $actualColumn FROM " . $this->table_name . " WHERE $actualColumn IS NOT NULL AND $actualColumn != '' ORDER BY $actualColumn ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            // Jika kolom tidak ada, return array kosong
            return [];
        }
    }

    // Ambil tahun yang tersedia
    public function getAvailableYears() {
        $sql = "SELECT DISTINCT YEAR(Pemilihan) as tahun FROM " . $this->table_name . " WHERE Pemilihan IS NOT NULL ORDER BY tahun DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Statistik sederhana - disesuaikan dengan struktur baru tapi tetap kompatibel
    public function getStatistics($filters = []) {
        // Statistik utama berdasarkan Tipe Swakelola (menggantikan Jenis Pengadaan)
        $sql = "SELECT Tipe_Swakelola as jenis, COUNT(*) as total FROM " . $this->table_name . " WHERE 1=1";
        $params = [];

        if (!empty($filters['tahun'])) {
            $sql .= " AND YEAR(Pemilihan) = :tahun";
            $params[':tahun'] = $filters['tahun'];
        }

        if (!empty($filters['tanggal_awal']) && !empty($filters['tanggal_akhir'])) {
            $sql .= " AND Pemilihan BETWEEN :tanggal_awal AND :tanggal_akhir";
            $params[':tanggal_awal'] = $filters['tanggal_awal'];
            $params[':tanggal_akhir'] = $filters['tanggal_akhir'];
        }

        $sql .= " GROUP BY Tipe_Swakelola ORDER BY total DESC";
        $stmt = $this->conn->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        $mainStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Statistik tambahan berdasarkan KLPD
        $sql2 = "SELECT KLPD, COUNT(*) as total FROM " . $this->table_name . " WHERE 1=1";
        $params2 = [];

        if (!empty($filters['tahun'])) {
            $sql2 .= " AND YEAR(Pemilihan) = :tahun";
            $params2[':tahun'] = $filters['tahun'];
        }

        if (!empty($filters['tanggal_awal']) && !empty($filters['tanggal_akhir'])) {
            $sql2 .= " AND Pemilihan BETWEEN :tanggal_awal AND :tanggal_akhir";
            $params2[':tanggal_awal'] = $filters['tanggal_awal'];
            $params2[':tanggal_akhir'] = $filters['tanggal_akhir'];
        }

        $sql2 .= " GROUP BY KLPD ORDER BY total DESC LIMIT 10";
        $stmt2 = $this->conn->prepare($sql2);

        foreach ($params2 as $key => $value) {
            $stmt2->bindValue($key, $value);
        }

        $stmt2->execute();
        $klpdStats = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        // Statistik berdasarkan Satuan Kerja
        $sql3 = "SELECT Satuan_Kerja, COUNT(*) as total FROM " . $this->table_name . " WHERE 1=1";
        $params3 = [];

        if (!empty($filters['tahun'])) {
            $sql3 .= " AND YEAR(Pemilihan) = :tahun";
            $params3[':tahun'] = $filters['tahun'];
        }

        if (!empty($filters['tanggal_awal']) && !empty($filters['tanggal_akhir'])) {
            $sql3 .= " AND Pemilihan BETWEEN :tanggal_awal AND :tanggal_akhir";
            $params3[':tanggal_awal'] = $filters['tanggal_awal'];
            $params3[':tanggal_akhir'] = $filters['tanggal_akhir'];
        }

        $sql3 .= " GROUP BY Satuan_Kerja ORDER BY total DESC LIMIT 10";
        $stmt3 = $this->conn->prepare($sql3);

        foreach ($params3 as $key => $value) {
            $stmt3->bindValue($key, $value);
        }

        $stmt3->execute();
        $satuanKerjaStats = $stmt3->fetchAll(PDO::FETCH_ASSOC);

        // Total keseluruhan
        $totalSql = "SELECT COUNT(*) as total_records FROM " . $this->table_name . " WHERE 1=1";
        $totalParams = [];

        if (!empty($filters['tahun'])) {
            $totalSql .= " AND YEAR(Pemilihan) = :tahun";
            $totalParams[':tahun'] = $filters['tahun'];
        }

        if (!empty($filters['tanggal_awal']) && !empty($filters['tanggal_akhir'])) {
            $totalSql .= " AND Pemilihan BETWEEN :tanggal_awal AND :tanggal_akhir";
            $totalParams[':tanggal_awal'] = $filters['tanggal_awal'];
            $totalParams[':tanggal_akhir'] = $filters['tanggal_akhir'];
        }

        $totalStmt = $this->conn->prepare($totalSql);
        foreach ($totalParams as $key => $value) {
            $totalStmt->bindValue($key, $value);
        }
        $totalStmt->execute();
        $totalRecords = $totalStmt->fetch(PDO::FETCH_ASSOC)['total_records'];

        // Statistik nilai pagu jika ada
        $totalPagu = 0;
        try {
            $paguSql = "SELECT SUM(CAST(REPLACE(REPLACE(Pagu_Rp, '.', ''), ',', '.') AS DECIMAL(20,2))) as total_pagu FROM " . $this->table_name . " WHERE Pagu_Rp IS NOT NULL AND Pagu_Rp != ''";
            $paguParams = [];

            if (!empty($filters['tahun'])) {
                $paguSql .= " AND YEAR(Pemilihan) = :tahun";
                $paguParams[':tahun'] = $filters['tahun'];
            }

            if (!empty($filters['tanggal_awal']) && !empty($filters['tanggal_akhir'])) {
                $paguSql .= " AND Pemilihan BETWEEN :tanggal_awal AND :tanggal_akhir";
                $paguParams[':tanggal_awal'] = $filters['tanggal_awal'];
                $paguParams[':tanggal_akhir'] = $filters['tanggal_akhir'];
            }

            $paguStmt = $this->conn->prepare($paguSql);
            foreach ($paguParams as $key => $value) {
                $paguStmt->bindValue($key, $value);
            }
            $paguStmt->execute();
            $totalPagu = $paguStmt->fetch(PDO::FETCH_ASSOC)['total_pagu'] ?? 0;
        } catch (Exception $e) {
            $totalPagu = 0;
        }

        // Return format yang kompatibel dengan yang lama tapi dengan data tambahan
        return [
            // Format lama untuk backward compatibility
            'main_stats' => $mainStats,
            // Format baru dengan fitur tambahan
            'total_records' => $totalRecords,
            'total_pagu' => $totalPagu,
            'by_tipe_swakelola' => $mainStats,
            'by_klpd' => $klpdStats,
            'by_satuan_kerja' => $satuanKerjaStats,
            // Alias untuk kompatibilitas dengan sistem lama
            'by_jenis_pengadaan' => $mainStats
        ];
    }

    // Method tambahan untuk mendapatkan detail berdasarkan ID
    public function getDetailById($id) {
        $sql = "SELECT * FROM " . $this->table_name . " WHERE ID = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Method untuk mendapatkan statistik berdasarkan lokasi
    public function getStatisticsByLocation($filters = []) {
        $sql = "SELECT Lokasi, COUNT(*) as total FROM " . $this->table_name . " WHERE Lokasi IS NOT NULL AND Lokasi != ''";
        $params = [];

        if (!empty($filters['tahun'])) {
            $sql .= " AND YEAR(Pemilihan) = :tahun";
            $params[':tahun'] = $filters['tahun'];
        }

        $sql .= " GROUP BY Lokasi ORDER BY total DESC LIMIT 15";
        $stmt = $this->conn->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Method untuk mendapatkan ringkasan bulanan
    public function getMonthlySummary($filters = []) {
        $sql = "SELECT 
                    MONTH(Pemilihan) as bulan,
                    YEAR(Pemilihan) as tahun,
                    COUNT(*) as total,
                    SUM(CAST(REPLACE(REPLACE(COALESCE(Pagu_Rp, '0'), '.', ''), ',', '.') AS DECIMAL(20,2))) as total_pagu
                FROM " . $this->table_name . " 
                WHERE Pemilihan IS NOT NULL";
        $params = [];

        if (!empty($filters['tahun'])) {
            $sql .= " AND YEAR(Pemilihan) = :tahun";
            $params[':tahun'] = $filters['tahun'];
        }

        $sql .= " GROUP BY YEAR(Pemilihan), MONTH(Pemilihan) ORDER BY tahun DESC, bulan DESC";
        $stmt = $this->conn->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}