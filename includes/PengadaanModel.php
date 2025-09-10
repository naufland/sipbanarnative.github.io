<?php
require_once '../config/database.php';

/**
 * Model untuk mengelola data pengadaan
 */
class PengadaanModel {
    private $conn;
    private $table = "tes_ruphuhuhud"; // Sesuaikan nama tabel

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Get all pengadaan data dengan filter
     */
    public function getPengadaanData($filters = [], $limit = 50, $offset = 0) {
        $sql = "SELECT * FROM " . $this->table . " WHERE 1=1";
        $params = [];

        // Filter berdasarkan tahun
        if (!empty($filters['tahun'])) {
            $sql .= " AND YEAR(STR_TO_DATE(Pemilihan, '%M %Y')) = :tahun";
            $params[':tahun'] = $filters['tahun'];
        }

        // Filter berdasarkan range bulan
        if (!empty($filters['bulan_awal']) && !empty($filters['bulan_akhir'])) {
            $sql .= " AND MONTH(STR_TO_DATE(Pemilihan, '%M %Y')) BETWEEN :bulan_awal AND :bulan_akhir";
            $params[':bulan_awal'] = $filters['bulan_awal'];
            $params[':bulan_akhir'] = $filters['bulan_akhir'];
        } elseif (!empty($filters['bulan_awal'])) {
            $sql .= " AND MONTH(STR_TO_DATE(Pemilihan, '%M %Y')) >= :bulan_awal";
            $params[':bulan_awal'] = $filters['bulan_awal'];
        } elseif (!empty($filters['bulan_akhir'])) {
            $sql .= " AND MONTH(STR_TO_DATE(Pemilihan, '%M %Y')) <= :bulan_akhir";
            $params[':bulan_akhir'] = $filters['bulan_akhir'];
        }

        // Filter berdasarkan jenis pengadaan
        if (!empty($filters['jenis_pengadaan'])) {
            $sql .= " AND Jenis_Pengadaan = :jenis_pengadaan";
            $params[':jenis_pengadaan'] = $filters['jenis_pengadaan'];
        }

        // Filter berdasarkan KLPD
        if (!empty($filters['klpd'])) {
            $sql .= " AND KLPD = :klpd";
            $params[':klpd'] = $filters['klpd'];
        }

        // Filter berdasarkan pencarian
        if (!empty($filters['search'])) {
            $sql .= " AND (Paket LIKE :search OR Satuan_Kerja LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        // Filter berdasarkan usaha kecil
        if (!empty($filters['usaha_kecil'])) {
            $sql .= " AND Usaha_Kecil = :usaha_kecil";
            $params[':usaha_kecil'] = $filters['usaha_kecil'];
        }

        // Filter berdasarkan metode
        if (!empty($filters['metode'])) {
            $sql .= " AND Metode = :metode";
            $params[':metode'] = $filters['metode'];
        }

        // Order by
        $sql .= " ORDER BY STR_TO_DATE(Pemilihan, '%M %Y') DESC, Pagu_Rp DESC";

        // Limit and offset
        $sql .= " LIMIT :limit OFFSET :offset";

        try {
            $stmt = $this->conn->prepare($sql);
            
            // Bind parameters
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error in getPengadaanData: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get total count untuk pagination
     */
    public function getTotalCount($filters = []) {
        $sql = "SELECT COUNT(*) as total FROM " . $this->table . " WHERE 1=1";
        $params = [];

        // Apply same filters as getPengadaanData
        if (!empty($filters['tahun'])) {
            $sql .= " AND YEAR(STR_TO_DATE(Pemilihan, '%M %Y')) = :tahun";
            $params[':tahun'] = $filters['tahun'];
        }

        if (!empty($filters['bulan_awal']) && !empty($filters['bulan_akhir'])) {
            $sql .= " AND MONTH(STR_TO_DATE(Pemilihan, '%M %Y')) BETWEEN :bulan_awal AND :bulan_akhir";
            $params[':bulan_awal'] = $filters['bulan_awal'];
            $params[':bulan_akhir'] = $filters['bulan_akhir'];
        } elseif (!empty($filters['bulan_awal'])) {
            $sql .= " AND MONTH(STR_TO_DATE(Pemilihan, '%M %Y')) >= :bulan_awal";
            $params[':bulan_awal'] = $filters['bulan_awal'];
        } elseif (!empty($filters['bulan_akhir'])) {
            $sql .= " AND MONTH(STR_TO_DATE(Pemilihan, '%M %Y')) <= :bulan_akhir";
            $params[':bulan_akhir'] = $filters['bulan_akhir'];
        }

        if (!empty($filters['jenis_pengadaan'])) {
            $sql .= " AND Jenis_Pengadaan = :jenis_pengadaan";
            $params[':jenis_pengadaan'] = $filters['jenis_pengadaan'];
        }

        if (!empty($filters['klpd'])) {
            $sql .= " AND KLPD = :klpd";
            $params[':klpd'] = $filters['klpd'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (Paket LIKE :search OR Satuan_Kerja LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        try {
            $stmt = $this->conn->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $result = $stmt->fetch();
            return $result['total'];
        } catch (PDOException $e) {
            error_log("Error in getTotalCount: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get distinct values untuk dropdown filter
     */
    public function getDistinctValues($column) {
        $allowedColumns = ['Jenis_Pengadaan', 'KLPD', 'Usaha_Kecil', 'Metode', 'Lokasi'];
        
        if (!in_array($column, $allowedColumns)) {
            return [];
        }

        $sql = "SELECT DISTINCT " . $column . " as value FROM " . $this->table . " 
                WHERE " . $column . " IS NOT NULL AND " . $column . " != '' 
                ORDER BY " . $column;

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error in getDistinctValues: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get years untuk dropdown tahun
     */
    public function getAvailableYears() {
        $sql = "SELECT DISTINCT YEAR(STR_TO_DATE(Pemilihan, '%M %Y')) as year 
                FROM " . $this->table . " 
                WHERE Pemilihan IS NOT NULL AND Pemilihan != ''
                ORDER BY year DESC";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error in getAvailableYears: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get statistics untuk dashboard
     */
    public function getStatistics($filters = []) {
        $sql = "SELECT 
                    COUNT(*) as total_pengadaan,
                    SUM(Pagu_Rp) as total_pagu,
                    AVG(Pagu_Rp) as avg_pagu,
                    COUNT(DISTINCT KLPD) as total_klpd,
                    COUNT(DISTINCT Jenis_Pengadaan) as total_jenis
                FROM " . $this->table . " WHERE 1=1";
        
        $params = [];

        // Apply filters if provided
        if (!empty($filters['tahun'])) {
            $sql .= " AND YEAR(STR_TO_DATE(Pemilihan, '%M %Y')) = :tahun";
            $params[':tahun'] = $filters['tahun'];
        }

        try {
            $stmt = $this->conn->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error in getStatistics: " . $e->getMessage());
            return [];
        }
    }
}