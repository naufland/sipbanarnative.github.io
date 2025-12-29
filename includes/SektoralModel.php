<?php
class SektoralModel {
    private $conn;
    private $table_name = "statistik_sektoral";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Ambil data sektoral dengan filter + pagination
    public function getSektoralData($filters = [], $limit = 50, $offset = 0) {
        $sql = "SELECT * FROM " . $this->table_name . " WHERE 1=1";
        $params = [];

        // Filter Tahun Anggaran
        if (!empty($filters['tahun_anggaran'])) {
            $sql .= " AND Tahun_Anggaran = :tahun_anggaran";
            $params[':tahun_anggaran'] = $filters['tahun_anggaran'];
        }

        // Filter Nama Satker (SKPD)
        if (!empty($filters['nama_satker'])) {
            $sql .= " AND Nama_Satker = :nama_satker";
            $params[':nama_satker'] = $filters['nama_satker'];
        }

        // Filter Kategori
        if (!empty($filters['kategori'])) {
            $sql .= " AND Kategori = :kategori";
            $params[':kategori'] = $filters['kategori'];
        }

        // Filter Kode RUP
        if (!empty($filters['kode_rup'])) {
            $sql .= " AND Kode_RUP = :kode_rup";
            $params[':kode_rup'] = $filters['kode_rup'];
        }

        // Search (pencarian nama paket)
        if (!empty($filters['search'])) {
            $sql .= " AND (Nama_Paket LIKE :search OR Nama_Satker LIKE :search OR Kategori LIKE :search)";
            $params[':search'] = "%" . $filters['search'] . "%";
        }

        // Filter range Total Perencanaan
        if (!empty($filters['min_total'])) {
            $sql .= " AND Total_Perencanaan_Rp >= :min_total";
            $params[':min_total'] = $filters['min_total'];
        }
        if (!empty($filters['max_total'])) {
            $sql .= " AND Total_Perencanaan_Rp <= :max_total";
            $params[':max_total'] = $filters['max_total'];
        }

        $sql .= " ORDER BY Tahun_Anggaran DESC, Total_Perencanaan_Rp DESC LIMIT :limit OFFSET :offset";
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

        if (!empty($filters['tahun_anggaran'])) {
            $sql .= " AND Tahun_Anggaran = :tahun_anggaran";
            $params[':tahun_anggaran'] = $filters['tahun_anggaran'];
        }

        if (!empty($filters['nama_satker'])) {
            $sql .= " AND Nama_Satker = :nama_satker";
            $params[':nama_satker'] = $filters['nama_satker'];
        }

        if (!empty($filters['kategori'])) {
            $sql .= " AND Kategori = :kategori";
            $params[':kategori'] = $filters['kategori'];
        }

        if (!empty($filters['kode_rup'])) {
            $sql .= " AND Kode_RUP = :kode_rup";
            $params[':kode_rup'] = $filters['kode_rup'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (Nama_Paket LIKE :search OR Nama_Satker LIKE :search OR Kategori LIKE :search)";
            $params[':search'] = "%" . $filters['search'] . "%";
        }

        if (!empty($filters['min_total'])) {
            $sql .= " AND Total_Perencanaan_Rp >= :min_total";
            $params[':min_total'] = $filters['min_total'];
        }
        if (!empty($filters['max_total'])) {
            $sql .= " AND Total_Perencanaan_Rp <= :max_total";
            $params[':max_total'] = $filters['max_total'];
        }

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row['total'] ?? 0;
    }

    // Ambil summary data sektoral
    public function getSummaryData($filters = []) {
        $sql = "SELECT 
                COUNT(*) as total_paket,
                SUM(Total_Perencanaan_Rp) as total_perencanaan,
                AVG(Total_Perencanaan_Rp) as avg_perencanaan,
                SUM(PDN_Rp) as total_pdn,
                COUNT(DISTINCT Nama_Satker) as total_skpd,
                COUNT(DISTINCT Kategori) as total_kategori
                FROM " . $this->table_name . " WHERE 1=1";
        $params = [];

        if (!empty($filters['tahun_anggaran'])) {
            $sql .= " AND Tahun_Anggaran = :tahun_anggaran";
            $params[':tahun_anggaran'] = $filters['tahun_anggaran'];
        }

        if (!empty($filters['nama_satker'])) {
            $sql .= " AND Nama_Satker = :nama_satker";
            $params[':nama_satker'] = $filters['nama_satker'];
        }

        if (!empty($filters['kategori'])) {
            $sql .= " AND Kategori = :kategori";
            $params[':kategori'] = $filters['kategori'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (Nama_Paket LIKE :search OR Nama_Satker LIKE :search OR Kategori LIKE :search)";
            $params[':search'] = "%" . $filters['search'] . "%";
        }

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Breakdown berdasarkan SKPD (Nama Satker)
    public function getBreakdownBySKPD($filters = []) {
        $sql = "SELECT 
                Nama_Satker,
                COUNT(*) as count,
                SUM(Total_Perencanaan_Rp) as total_perencanaan,
                SUM(PDN_Rp) as total_pdn
                FROM " . $this->table_name . " WHERE 1=1";
        $params = [];

        if (!empty($filters['tahun_anggaran'])) {
            $sql .= " AND Tahun_Anggaran = :tahun_anggaran";
            $params[':tahun_anggaran'] = $filters['tahun_anggaran'];
        }

        if (!empty($filters['kategori'])) {
            $sql .= " AND Kategori = :kategori";
            $params[':kategori'] = $filters['kategori'];
        }

        $sql .= " GROUP BY Nama_Satker ORDER BY total_perencanaan DESC";

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[$row['Nama_Satker']] = [
                'count' => (int)$row['count'],
                'total_perencanaan' => (float)$row['total_perencanaan'],
                'total_pdn' => (float)$row['total_pdn']
            ];
        }

        return $result;
    }

    // Breakdown berdasarkan Kategori
    public function getBreakdownByKategori($filters = []) {
        $sql = "SELECT 
                Kategori,
                COUNT(*) as count,
                SUM(Total_Perencanaan_Rp) as total_perencanaan,
                SUM(PDN_Rp) as total_pdn
                FROM " . $this->table_name . " WHERE 1=1";
        $params = [];

        if (!empty($filters['tahun_anggaran'])) {
            $sql .= " AND Tahun_Anggaran = :tahun_anggaran";
            $params[':tahun_anggaran'] = $filters['tahun_anggaran'];
        }

        if (!empty($filters['nama_satker'])) {
            $sql .= " AND Nama_Satker = :nama_satker";
            $params[':nama_satker'] = $filters['nama_satker'];
        }

        $sql .= " GROUP BY Kategori ORDER BY total_perencanaan DESC";

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[$row['Kategori']] = [
                'count' => (int)$row['count'],
                'total_perencanaan' => (float)$row['total_perencanaan'],
                'total_pdn' => (float)$row['total_pdn']
            ];
        }

        return $result;
    }

    // Breakdown berdasarkan Tahun Anggaran
    public function getBreakdownByTahun($filters = []) {
        $sql = "SELECT 
                Tahun_Anggaran,
                COUNT(*) as count,
                SUM(Total_Perencanaan_Rp) as total_perencanaan,
                SUM(PDN_Rp) as total_pdn
                FROM " . $this->table_name . " WHERE 1=1";
        $params = [];

        if (!empty($filters['nama_satker'])) {
            $sql .= " AND Nama_Satker = :nama_satker";
            $params[':nama_satker'] = $filters['nama_satker'];
        }

        if (!empty($filters['kategori'])) {
            $sql .= " AND Kategori = :kategori";
            $params[':kategori'] = $filters['kategori'];
        }

        $sql .= " GROUP BY Tahun_Anggaran ORDER BY Tahun_Anggaran DESC";

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[$row['Tahun_Anggaran']] = [
                'count' => (int)$row['count'],
                'total_perencanaan' => (float)$row['total_perencanaan'],
                'total_pdn' => (float)$row['total_pdn']
            ];
        }

        return $result;
    }

    // Ambil nilai unik untuk dropdown
    public function getDistinctValues($column) {
        $allowedColumns = ['Tahun_Anggaran', 'Nama_Satker', 'Kategori'];
        
        if (!in_array($column, $allowedColumns)) {
            return [];
        }

        $sql = "SELECT DISTINCT $column FROM " . $this->table_name . " WHERE $column IS NOT NULL AND $column != '' ORDER BY $column ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Ambil daftar SKPD yang unik
    public function getAvailableSKPD() {
        $sql = "SELECT DISTINCT Nama_Satker FROM " . $this->table_name . " WHERE Nama_Satker IS NOT NULL AND Nama_Satker != '' ORDER BY Nama_Satker ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Ambil tahun anggaran yang tersedia
    public function getAvailableYears() {
        $sql = "SELECT DISTINCT Tahun_Anggaran FROM " . $this->table_name . " WHERE Tahun_Anggaran IS NOT NULL ORDER BY Tahun_Anggaran DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Ambil kategori yang tersedia
    public function getAvailableKategori() {
        $sql = "SELECT DISTINCT Kategori FROM " . $this->table_name . " WHERE Kategori IS NOT NULL AND Kategori != '' ORDER BY Kategori ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Statistik per SKPD
    public function getStatisticsBySKPD($filters = []) {
        $sql = "SELECT 
                Nama_Satker,
                Tahun_Anggaran,
                COUNT(*) as total_paket,
                SUM(Total_Perencanaan_Rp) as total_perencanaan,
                SUM(PDN_Rp) as total_pdn
                FROM " . $this->table_name . " WHERE 1=1";
        $params = [];

        if (!empty($filters['tahun_anggaran'])) {
            $sql .= " AND Tahun_Anggaran = :tahun_anggaran";
            $params[':tahun_anggaran'] = $filters['tahun_anggaran'];
        }

        if (!empty($filters['nama_satker'])) {
            $sql .= " AND Nama_Satker = :nama_satker";
            $params[':nama_satker'] = $filters['nama_satker'];
        }

        $sql .= " GROUP BY Nama_Satker, Tahun_Anggaran ORDER BY total_perencanaan DESC";
        
        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Top N SKPD berdasarkan total perencanaan
    public function getTopSKPD($limit = 10, $filters = []) {
        $sql = "SELECT 
                Nama_Satker,
                COUNT(*) as total_paket,
                SUM(Total_Perencanaan_Rp) as total_perencanaan,
                SUM(PDN_Rp) as total_pdn
                FROM " . $this->table_name . " WHERE 1=1";
        $params = [];

        if (!empty($filters['tahun_anggaran'])) {
            $sql .= " AND Tahun_Anggaran = :tahun_anggaran";
            $params[':tahun_anggaran'] = $filters['tahun_anggaran'];
        }

        $sql .= " GROUP BY Nama_Satker ORDER BY total_perencanaan DESC LIMIT :limit";
        
        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}