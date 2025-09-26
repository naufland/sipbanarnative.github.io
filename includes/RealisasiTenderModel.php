<?php

class RealisasiTenderModel
{
    private $conn;
    private $table_name = "realisasi_tender";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Get Realisasi Tender data with filters and pagination.
     * @param array $filters
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getRealisasiTenderData($filters = [], $limit = 50, $offset = 0)
    {
        $sql = "SELECT * FROM " . $this->table_name . " WHERE 1=1";
        $params = [];

        if (!empty($filters['tahun'])) {
            $sql .= " AND Tahun_Anggaran = :tahun";
            $params[':tahun'] = $filters['tahun'];
        }
        if (!empty($filters['jenis_pengadaan'])) {
            $sql .= " AND Jenis_Pengadaan = :jenis_pengadaan";
            $params[':jenis_pengadaan'] = $filters['jenis_pengadaan'];
        }
        if (!empty($filters['klpd'])) {
            $sql .= " AND KLPD = :klpd";
            $params[':klpd'] = $filters['klpd'];
        }
        if (!empty($filters['metode_pengadaan'])) {
            $sql .= " AND Metode_Pengadaan = :metode_pengadaan";
            $params[':metode_pengadaan'] = $filters['metode_pengadaan'];
        }
        if (!empty($filters['sumber_dana'])) {
            $sql .= " AND Sumber_Dana = :sumber_dana";
            $params[':sumber_dana'] = $filters['sumber_dana'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (Nama_Paket LIKE :search OR Nama_Pemenang LIKE :search OR Nama_Satker LIKE :search)";
            $params[':search'] = "%" . $filters['search'] . "%";
        }

        $sql .= " ORDER BY ID DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count total data for pagination.
     * @param array $filters
     * @return int
     */
    public function getTotalCount($filters = [])
    {
        $sql = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE 1=1";
        $params = [];

        if (!empty($filters['tahun'])) {
            $sql .= " AND Tahun_Anggaran = :tahun";
            $params[':tahun'] = $filters['tahun'];
        }
        if (!empty($filters['jenis_pengadaan'])) {
            $sql .= " AND Jenis_Pengadaan = :jenis_pengadaan";
            $params[':jenis_pengadaan'] = $filters['jenis_pengadaan'];
        }
        if (!empty($filters['klpd'])) {
            $sql .= " AND KLPD = :klpd";
            $params[':klpd'] = $filters['klpd'];
        }
        if (!empty($filters['metode_pengadaan'])) {
            $sql .= " AND Metode_Pengadaan = :metode_pengadaan";
            $params[':metode_pengadaan'] = $filters['metode_pengadaan'];
        }
        if (!empty($filters['sumber_dana'])) {
            $sql .= " AND Sumber_Dana = :sumber_dana";
            $params[':sumber_dana'] = $filters['sumber_dana'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (Nama_Paket LIKE :search OR Nama_Pemenang LIKE :search OR Nama_Satker LIKE :search)";
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

    /**
     * Get distinct values for dropdown filters.
     * @param string $column
     * @return array
     */
    public function getDistinctValues($column)
    {
        // Sanitize column name to prevent SQL injection
        $allowedColumns = ['Jenis_Pengadaan', 'KLPD', 'Metode_Pengadaan', 'Sumber_Dana', 'Jenis_Kontrak', 'Tahap'];
        if (!in_array($column, $allowedColumns)) {
            return []; // Return empty array if column is not allowed
        }

        $sql = "SELECT DISTINCT $column FROM " . $this->table_name . " WHERE $column IS NOT NULL AND $column != '' ORDER BY $column ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get available years.
     * @return array
     */
    public function getAvailableYears()
    {
        $sql = "SELECT DISTINCT Tahun_Anggaran as tahun FROM " . $this->table_name . " WHERE Tahun_Anggaran IS NOT NULL ORDER BY tahun DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get simple statistics.
     * @param array $filters
     * @return array
     */
    public function getStatistics($filters = [])
    {
        $sql = "SELECT Jenis_Pengadaan, COUNT(*) as total, SUM(Nilai_Kontrak) as total_nilai FROM " . $this->table_name . " WHERE 1=1";
        $params = [];

        if (!empty($filters['tahun'])) {
            $sql .= " AND Tahun_Anggaran = :tahun";
            $params[':tahun'] = $filters['tahun'];
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

