<?php
class PengadaanModel {
    private $conn;
    private $table_name = "rup_keseluruhan";

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

        if (!empty($filters['jenis_pengadaan'])) {
            $sql .= " AND Jenis_Pengadaan = :jenis_pengadaan";
            $params[':jenis_pengadaan'] = $filters['jenis_pengadaan'];
        }
        if (!empty($filters['klpd'])) {
            $sql .= " AND KLPD = :klpd";
            $params[':klpd'] = $filters['klpd'];
        }
        if (!empty($filters['usaha_kecil'])) {
            $sql .= " AND Usaha_Kecil = :usaha_kecil";
            $params[':usaha_kecil'] = $filters['usaha_kecil'];
        }
        if (!empty($filters['metode'])) {
            $sql .= " AND Metode = :metode";
            $params[':metode'] = $filters['metode'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (Paket LIKE :search OR Lokasi LIKE :search)";
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

        if (!empty($filters['jenis_pengadaan'])) {
            $sql .= " AND Jenis_Pengadaan = :jenis_pengadaan";
            $params[':jenis_pengadaan'] = $filters['jenis_pengadaan'];
        }
        if (!empty($filters['klpd'])) {
            $sql .= " AND KLPD = :klpd";
            $params[':klpd'] = $filters['klpd'];
        }
        if (!empty($filters['usaha_kecil'])) {
            $sql .= " AND Usaha_Kecil = :usaha_kecil";
            $params[':usaha_kecil'] = $filters['usaha_kecil'];
        }
        if (!empty($filters['metode'])) {
            $sql .= " AND Metode = :metode";
            $params[':metode'] = $filters['metode'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (Paket LIKE :search OR Lokasi LIKE :search)";
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

    // Ambil nilai unik untuk dropdown
    public function getDistinctValues($column) {
        $sql = "SELECT DISTINCT $column FROM " . $this->table_name . " WHERE $column IS NOT NULL AND $column != '' ORDER BY $column ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Ambil tahun yang tersedia
    public function getAvailableYears() {
        $sql = "SELECT DISTINCT YEAR(Pemilihan) as tahun FROM " . $this->table_name . " WHERE Pemilihan IS NOT NULL ORDER BY tahun DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Statistik sederhana
    public function getStatistics($filters = []) {
        $sql = "SELECT Jenis_Pengadaan, COUNT(*) as total FROM " . $this->table_name . " WHERE 1=1";
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

        $sql .= " GROUP BY Jenis_Pengadaan ORDER BY total DESC";
        $stmt = $this->conn->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
