<?php
// File: includes/PerencanaanModel.php

class SektoralModel
{
    private $conn;
    private $table_name = "data_sektoral";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    private function buildWhereClause($filters)
    {
        $whereClause = " WHERE 1=1";
        $params = [];

        // Filter berdasarkan tahun anggaran
        if (!empty($filters['tahun'])) {
            $whereClause .= " AND Tahun_Anggaran = :tahun";
            $params[':tahun'] = $filters['tahun'];
        }

        // Filter berdasarkan Kategori
        if (!empty($filters['kategori'])) {
            $whereClause .= " AND Kategori = :kategori";
            $params[':kategori'] = $filters['kategori'];
        }

        // Filter berdasarkan pencarian
        if (!empty($filters['search'])) {
            $whereClause .= " AND (Nama_Paket LIKE :search OR Nama_Satker LIKE :search OR Kode_RUP LIKE :search)";
            $params[':search'] = "%" . $filters['search'] . "%";
        }
        
        return [$whereClause, $params];
    }

    /**
     * Helper function untuk mengkonversi nilai string ke float
     */
    private function convertToFloat($value)
    {
        if (is_null($value) || $value === '') {
            return 0.0;
        }
        
        // Jika sudah numeric, langsung return
        if (is_numeric($value)) {
            return floatval($value);
        }
        
        // Jika string, bersihkan formatnya
        if (is_string($value)) {
            // Hapus titik sebagai pemisah ribuan
            $value = str_replace('.', '', $value);
            // Ubah koma desimal jadi titik
            $value = str_replace(',', '.', $value);
        }
        
        return floatval($value);
    }

    public function getPerencanaanData($filters = [], $limit = 50, $offset = 0)
    {
        list($whereClause, $params) = $this->buildWhereClause($filters);
        
        $sql = "SELECT 
                Tahun_Anggaran, 
                Nama_Satker, 
                Kategori, 
                Kode_RUP, 
                Nama_Paket, 
                Total_Perencanaan_Rp, 
                PDN_Rp 
                FROM " . $this->table_name . $whereClause . " 
                ORDER BY Tahun_Anggaran DESC, Kode_RUP ASC 
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($sql);
        
        // Bind semua parameter dari filter
        foreach ($params as $key => $value) { 
            $stmt->bindValue($key, $value); 
        }
        
        // Bind parameter limit dan offset
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Konversi semua nilai numerik ke float
        foreach ($results as &$row) {
            $row['Total_Perencanaan_Rp'] = $this->convertToFloat($row['Total_Perencanaan_Rp'] ?? 0);
            $row['PDN_Rp'] = $this->convertToFloat($row['PDN_Rp'] ?? 0);
        }
        
        return $results;
    }

    public function getTotalCount($filters = [])
    {
        list($whereClause, $params) = $this->buildWhereClause($filters);
        $sql = "SELECT COUNT(*) as total FROM " . $this->table_name . $whereClause;
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'] ?? 0;
    }

    /**
     * Fungsi untuk mendapatkan summary data
     */
    public function getSummaryData($filters = [])
    {
        list($whereClause, $params) = $this->buildWhereClause($filters);
        
        $sql = "SELECT 
                    COUNT(*) as total_paket,
                    COALESCE(SUM(
                        CASE 
                            WHEN Total_Perencanaan_Rp REGEXP '^[0-9.]+$' 
                            THEN CAST(REPLACE(Total_Perencanaan_Rp, '.', '') AS DECIMAL(20,2))
                            ELSE 0 
                        END
                    ), 0) as total_perencanaan,
                    COALESCE(SUM(
                        CASE 
                            WHEN PDN_Rp REGEXP '^[0-9.]+$' 
                            THEN CAST(REPLACE(PDN_Rp, '.', '') AS DECIMAL(20,2))
                            ELSE 0 
                        END
                    ), 0) as total_pdn
                FROM " . $this->table_name . $whereClause;
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'total_paket' => (int)($result['total_paket'] ?? 0),
            'total_perencanaan' => $this->convertToFloat($result['total_perencanaan'] ?? 0),
            'total_pdn' => $this->convertToFloat($result['total_pdn'] ?? 0)
        ];
    }

    public function getAllDataForSummary($filters = []) 
    {
        list($whereClause, $params) = $this->buildWhereClause($filters);
        $sql = "SELECT 
                    Kategori,
                    Nama_Satker,
                    Total_Perencanaan_Rp,
                    PDN_Rp
                FROM " . $this->table_name . $whereClause;
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Konversi nilai ke float
        foreach ($results as &$row) {
            $row['Total_Perencanaan_Rp'] = $this->convertToFloat($row['Total_Perencanaan_Rp'] ?? 0);
            $row['PDN_Rp'] = $this->convertToFloat($row['PDN_Rp'] ?? 0);
        }
        
        return $results;
    }

    public function getDistinctValues($column)
    {
        $allowedColumns = ['Kategori', 'Nama_Satker', 'Tahun_Anggaran'];
        if (!in_array($column, $allowedColumns)) return [];
        
        $sql = "SELECT DISTINCT $column FROM " . $this->table_name . " WHERE $column IS NOT NULL AND $column != '' ORDER BY $column ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Fungsi untuk mengambil semua tahun unik dari kolom Tahun_Anggaran
     */
    public function getAvailableYears()
    {
        $sql = "SELECT DISTINCT Tahun_Anggaran as tahun 
                FROM " . $this->table_name . " 
                WHERE Tahun_Anggaran IS NOT NULL 
                ORDER BY tahun DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}