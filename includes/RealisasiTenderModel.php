<?php
// File: includes/RealisasiTenderModel.php

class RealisasiTenderModel
{
    private $conn;
    private $table_name = "realisasi_tender";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    private function buildWhereClause($filters)
    {
        $whereClause = " WHERE 1=1";
        $params = [];

        // Filter Bulan dan Tahun (PRIORITAS UTAMA)
        // Menggunakan kolom Tanggal_Kontrak untuk filter bulan/tahun
        if (!empty($filters['bulan']) && !empty($filters['tahun'])) {
            // Filter berdasarkan bulan dan tahun
            // Konversi bulan ke integer untuk menghilangkan leading zero
            $bulan = (int)ltrim($filters['bulan'], '0');
            $whereClause .= " AND MONTH(Tanggal_Kontrak) = :bulan AND YEAR(Tanggal_Kontrak) = :tahun";
            $params[':bulan'] = $bulan;
            $params[':tahun'] = (int)$filters['tahun'];
        } elseif (!empty($filters['tahun'])) {
            // Jika hanya tahun yang difilter
            $whereClause .= " AND YEAR(Tanggal_Kontrak) = :tahun";
            $params[':tahun'] = (int)$filters['tahun'];
        } elseif (!empty($filters['bulan'])) {
            // Jika hanya bulan yang difilter (jarang terjadi)
            $bulan = (int)ltrim($filters['bulan'], '0');
            $whereClause .= " AND MONTH(Tanggal_Kontrak) = :bulan";
            $params[':bulan'] = $bulan;
        }

        // Filter Tanggal Range (opsional, bisa dipakai bersamaan dengan bulan/tahun)
        if (!empty($filters['tanggal_awal'])) {
            $whereClause .= " AND Tanggal_Kontrak >= :tanggal_awal";
            $params[':tanggal_awal'] = $filters['tanggal_awal'];
        }
        if (!empty($filters['tanggal_akhir'])) {
            $whereClause .= " AND Tanggal_Kontrak <= :tanggal_akhir";
            $params[':tanggal_akhir'] = $filters['tanggal_akhir'];
        }

        // Filter Jenis Pengadaan
        if (!empty($filters['jenis_pengadaan'])) {
            $whereClause .= " AND Jenis_Pengadaan = :jenis_pengadaan";
            $params[':jenis_pengadaan'] = $filters['jenis_pengadaan'];
        }

        // Filter KLPD
        if (!empty($filters['klpd'])) {
            $whereClause .= " AND KLPD = :klpd";
            $params[':klpd'] = $filters['klpd'];
        }

        // Filter Metode Pengadaan
        if (!empty($filters['metode_pengadaan'])) {
            $whereClause .= " AND Metode_Pengadaan = :metode_pengadaan";
            $params[':metode_pengadaan'] = $filters['metode_pengadaan'];
        }

        // Filter Sumber Dana
        if (!empty($filters['sumber_dana'])) {
            $whereClause .= " AND Sumber_Dana = :sumber_dana";
            $params[':sumber_dana'] = $filters['sumber_dana'];
        }

        // Filter Pencarian (Search)
        if (!empty($filters['search'])) {
            $whereClause .= " AND (Nama_Paket LIKE :search OR Nama_Pemenang LIKE :search OR Nama_Satker LIKE :search OR Kode_Tender LIKE :search)";
            $params[':search'] = "%" . $filters['search'] . "%";
        }
        
        return [$whereClause, $params];
    }

    public function getRealisasiTenderData($filters = [], $limit = 50, $offset = 0)
    {
        list($whereClause, $params) = $this->buildWhereClause($filters);
        $sql = "SELECT * FROM " . $this->table_name . $whereClause . " ORDER BY No ASC LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($sql);
        
        foreach ($params as $key => $value) { 
            $stmt->bindValue($key, $value); 
        }
        
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotalCount($filters = [])
    {
        list($whereClause, $params) = $this->buildWhereClause($filters);
        $sql = "SELECT COUNT(No) as total FROM " . $this->table_name . $whereClause;
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'] ?? 0;
    }

    public function getAllDataForSummary($filters = []) {
        list($whereClause, $params) = $this->buildWhereClause($filters);
        $sql = "SELECT Jenis_Pengadaan, KLPD, Metode_Pengadaan, Nilai_Pagu, Nilai_HPS, Nilai_Kontrak FROM " . $this->table_name . $whereClause;
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDistinctValues($column)
    {
        $allowedColumns = ['Jenis_Pengadaan', 'KLPD', 'Metode_Pengadaan', 'Sumber_Dana'];
        if (!in_array($column, $allowedColumns)) return [];
        
        $sql = "SELECT DISTINCT $column FROM " . $this->table_name . " WHERE $column IS NOT NULL AND $column != '' ORDER BY $column ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Mengambil semua tahun unik dari kolom Tahun_Anggaran
     * Digunakan untuk dropdown filter tahun
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

    /**
     * FUNGSI BARU: Mengambil bulan-bulan yang tersedia untuk tahun tertentu
     * Berguna jika ingin membuat dropdown bulan yang dinamis
     */
    public function getAvailableMonths($tahun = null)
    {
        $sql = "SELECT DISTINCT MONTH(Tanggal_Kontrak) as bulan 
                FROM " . $this->table_name . " 
                WHERE Tanggal_Kontrak IS NOT NULL";
        
        $params = [];
        if (!empty($tahun)) {
            $sql .= " AND YEAR(Tanggal_Kontrak) = :tahun";
            $params[':tahun'] = (int)$tahun;
        }
        
        $sql .= " ORDER BY bulan ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * FUNGSI BARU: Mendapatkan summary data per bulan
     * Berguna untuk grafik atau laporan bulanan
     */
    public function getMonthlySummary($tahun)
    {
        $sql = "SELECT 
                    MONTH(Tanggal_Kontrak) as bulan,
                    COUNT(No) as total_paket,
                    SUM(Nilai_Pagu) as total_pagu,
                    SUM(Nilai_HPS) as total_hps,
                    SUM(Nilai_Kontrak) as total_kontrak
                FROM " . $this->table_name . "
                WHERE YEAR(Tanggal_Kontrak) = :tahun
                GROUP BY MONTH(Tanggal_Kontrak)
                ORDER BY bulan ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':tahun', (int)$tahun, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * FUNGSI BARU: Mendapatkan summary total dengan filter bulan dan tahun
     * Menghitung total paket, pagu, HPS, dan kontrak
     */
    public function getSummaryWithFilters($filters = [])
    {
        list($whereClause, $params) = $this->buildWhereClause($filters);
        
        $sql = "SELECT 
                    COUNT(No) as total_paket,
                    SUM(Nilai_Pagu) as total_pagu,
                    SUM(Nilai_HPS) as total_hps,
                    SUM(Nilai_Kontrak) as total_kontrak
                FROM " . $this->table_name . $whereClause;
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'total_paket' => (int)($result['total_paket'] ?? 0),
            'total_pagu' => (float)($result['total_pagu'] ?? 0),
            'total_hps' => (float)($result['total_hps'] ?? 0),
            'total_kontrak' => (float)($result['total_kontrak'] ?? 0)
        ];
    }

    /**
     * FUNGSI BARU: Mendapatkan statistik efisiensi
     */
    public function getEfficiencyStats($filters = [])
    {
        $summary = $this->getSummaryWithFilters($filters);
        
        $efisiensi = 0;
        if ($summary['total_pagu'] > 0) {
            $efisiensi = (($summary['total_pagu'] - $summary['total_kontrak']) / $summary['total_pagu']) * 100;
        }
        
        return [
            'total_paket' => $summary['total_paket'],
            'total_pagu' => $summary['total_pagu'],
            'total_hps' => $summary['total_hps'],
            'total_kontrak' => $summary['total_kontrak'],
            'efisiensi_persen' => round($efisiensi, 2),
            'penghematan' => $summary['total_pagu'] - $summary['total_kontrak']
        ];
    }

    /**
     * FUNGSI BARU: Mendapatkan data untuk chart/grafik per jenis pengadaan
     */
    public function getSummaryByJenisPengadaan($filters = [])
    {
        list($whereClause, $params) = $this->buildWhereClause($filters);
        
        $sql = "SELECT 
                    Jenis_Pengadaan,
                    COUNT(No) as total_paket,
                    SUM(Nilai_Pagu) as total_pagu,
                    SUM(Nilai_Kontrak) as total_kontrak
                FROM " . $this->table_name . $whereClause . "
                GROUP BY Jenis_Pengadaan
                ORDER BY total_paket DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * FUNGSI BARU: Mendapatkan data untuk chart/grafik per KLPD
     */
    public function getSummaryByKLPD($filters = [])
    {
        list($whereClause, $params) = $this->buildWhereClause($filters);
        
        $sql = "SELECT 
                    KLPD,
                    COUNT(No) as total_paket,
                    SUM(Nilai_Pagu) as total_pagu,
                    SUM(Nilai_Kontrak) as total_kontrak
                FROM " . $this->table_name . $whereClause . "
                GROUP BY KLPD
                ORDER BY total_paket DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}