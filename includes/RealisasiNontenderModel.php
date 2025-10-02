<?php
// File: includes/RealisasiNontenderModel.php

class RealisasiNontenderModel
{
    private $conn;
    private $table_name = "realisasi_nontender";

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

        // Filter berdasarkan KLPD
        if (!empty($filters['klpd'])) {
            $whereClause .= " AND KLPD = :klpd";
            $params[':klpd'] = $filters['klpd'];
        }

        // Filter berdasarkan Metode Pengadaan
        if (!empty($filters['metode_pengadaan'])) {
            $whereClause .= " AND Metode_Pengadaan = :metode_pengadaan";
            $params[':metode_pengadaan'] = $filters['metode_pengadaan'];
        }

        // Filter berdasarkan Jenis Pengadaan
        if (!empty($filters['jenis_pengadaan'])) {
            $whereClause .= " AND Jenis_Pengadaan = :jenis_pengadaan";
            $params[':jenis_pengadaan'] = $filters['jenis_pengadaan'];
        }

        // Filter berdasarkan pencarian
        if (!empty($filters['search'])) {
            $whereClause .= " AND (Nama_Paket LIKE :search OR Nama_Pemenang LIKE :search OR Nama_Satker LIKE :search OR Kode_Paket LIKE :search)";
            $params[':search'] = "%" . $filters['search'] . "%";
        }
        
        return [$whereClause, $params];
    }

    public function getRealisasiNontenderData($filters = [], $limit = 50, $offset = 0)
    {
        list($whereClause, $params) = $this->buildWhereClause($filters);
        
        $sql = "SELECT Tahun_Anggaran, Kode_Paket, Nama_Paket, Kode_RUP, KLPD, 
                Nama_Satker, Jenis_Pengadaan, Metode_Pengadaan, Nilai_Pagu, 
                Nilai_HPS, Nama_Pemenang, Nilai_Kontrak, Nilai_PDN, Nilai_UMK, 
                Sumber_Dana, Status_Paket 
                FROM " . $this->table_name . $whereClause . " 
                ORDER BY Tahun_Anggaran DESC, Kode_Paket ASC 
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
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
     * Fungsi untuk mendapatkan summary data (Total Paket, Total Pagu, Total Kontrak)
     */
    public function getSummaryData($filters = [])
    {
        list($whereClause, $params) = $this->buildWhereClause($filters);
        
        $sql = "SELECT 
                    COUNT(*) as total_paket,
                    COALESCE(SUM(Nilai_Pagu), 0) as total_pagu,
                    COALESCE(SUM(Nilai_Kontrak), 0) as total_kontrak
                FROM " . $this->table_name . $whereClause;
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'total_paket' => (int)($result['total_paket'] ?? 0),
            'total_pagu' => (float)($result['total_pagu'] ?? 0),
            'total_kontrak' => (float)($result['total_kontrak'] ?? 0)
        ];
    }

    public function getAllDataForSummary($filters = []) 
    {
        list($whereClause, $params) = $this->buildWhereClause($filters);
        $sql = "SELECT 
                    Metode_Pengadaan,
                    Jenis_Pengadaan,
                    KLPD, 
                    Nilai_Pagu, 
                    Nilai_Kontrak,
                    Nama_Pemenang
                FROM " . $this->table_name . $whereClause;
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDistinctValues($column)
    {
        $allowedColumns = ['Metode_Pengadaan', 'Jenis_Pengadaan', 'KLPD', 'Tahun_Anggaran'];
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