<?php
// File: includes/RealisasiSeleksiModel.php

class RealisasiSeleksiModel
{
    private $conn;
    private $table_name = "realisasi_seleksi";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // BARU: Helper - Konversi angka bulan ke nama bulan Indonesia
    private function getBulanNama($bulanAngka) {
        $mapping = [
            '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
            '04' => 'April', '05' => 'Mei', '06' => 'Juni',
            '07' => 'Juli', '08' => 'Agustus', '09' => 'September',
            '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
        ];
        return $mapping[$bulanAngka] ?? null;
    }

    private function buildWhereClause($filters)
    {
        $whereClause = " WHERE 1=1";
        $params = [];

        // BARU: Filter bulan dan tahun
        // Konversi bulan angka (07) ke nama (Juli)
        if (!empty($filters['bulan']) && !empty($filters['tahun'])) {
            $bulanNama = $this->getBulanNama($filters['bulan']);
            if ($bulanNama) {
                $whereClause .= " AND bulan = :bulan AND tahun = :tahun";
                $params[':bulan'] = $bulanNama;
                $params[':tahun'] = $filters['tahun'];
            }
        } elseif (!empty($filters['tahun'])) {
            $whereClause .= " AND tahun = :tahun";
            $params[':tahun'] = $filters['tahun'];
        } elseif (!empty($filters['bulan'])) {
            $bulanNama = $this->getBulanNama($filters['bulan']);
            if ($bulanNama) {
                $whereClause .= " AND bulan = :bulan AND tahun = :tahun_sekarang";
                $params[':bulan'] = $bulanNama;
                $params[':tahun_sekarang'] = date('Y');
            }
        }

        // Filter range tanggal (opsional, untuk detail lebih spesifik)
        if (!empty($filters['tanggal_awal'])) {
            $whereClause .= " AND Tanggal_Kontrak >= :tanggal_awal";
            $params[':tanggal_awal'] = $filters['tanggal_awal'];
        }
        if (!empty($filters['tanggal_akhir'])) {
            $whereClause .= " AND Tanggal_Kontrak <= :tanggal_akhir";
            $params[':tanggal_akhir'] = $filters['tanggal_akhir'];
        }

        if (!empty($filters['jenis_pengadaan'])) {
            $whereClause .= " AND Jenis_Pengadaan = :jenis_pengadaan";
            $params[':jenis_pengadaan'] = $filters['jenis_pengadaan'];
        }

        // DIGANTI: dari klpd ke nama_satker
        if (!empty($filters['nama_satker'])) {
            $whereClause .= " AND Nama_Satker = :nama_satker";
            $params[':nama_satker'] = $filters['nama_satker'];
        }

        if (!empty($filters['metode_pengadaan'])) {
            $whereClause .= " AND Metode_Pengadaan = :metode_pengadaan";
            $params[':metode_pengadaan'] = $filters['metode_pengadaan'];
        }

        // DIUPDATE: Tambahkan Nama_Satker di pencarian
        if (!empty($filters['search'])) {
            $whereClause .= " AND (Nama_Paket LIKE :search OR Nama_Pemenang LIKE :search OR Nama_Satker LIKE :search)";
            $params[':search'] = "%" . $filters['search'] . "%";
        }
        
        return [$whereClause, $params];
    }

    // Ambil data utama dengan filter + pagination
    public function getRealisasiSeleksiData($filters = [], $limit = 50, $offset = 0)
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

    // Hitung total data (untuk pagination)
    public function getTotalCount($filters = [])
    {
        list($whereClause, $params) = $this->buildWhereClause($filters);
        $sql = "SELECT COUNT(No) as total FROM " . $this->table_name . $whereClause;
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'] ?? 0;
    }

    // DIUPDATE: Ambil semua data untuk summary dengan kolom Nama_Satker
    public function getAllDataForSummary($filters = [])
    {
        list($whereClause, $params) = $this->buildWhereClause($filters);
        
        // DIGANTI: KLPD menjadi Nama_Satker
        $sql = "SELECT 
                    Jenis_Pengadaan, 
                    Nama_Satker, 
                    Metode_Pengadaan, 
                    Nilai_Pagu, 
                    Nilai_HPS, 
                    Nilai_Kontrak 
                FROM " . $this->table_name . $whereClause;
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // DIUPDATE: Tambahkan Nama_Satker di allowed columns
    public function getDistinctValues($column)
    {
        // DIGANTI: KLPD menjadi Nama_Satker
        $allowedColumns = ['Jenis_Pengadaan', 'Nama_Satker', 'Metode_Pengadaan', 'Sumber_Dana'];
        
        if (!in_array($column, $allowedColumns)) {
            return [];
        }
        
        $sql = "SELECT DISTINCT $column 
                FROM " . $this->table_name . " 
                WHERE $column IS NOT NULL AND $column != '' 
                ORDER BY $column ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    // Ambil tahun yang tersedia - HANYA SATU FUNGSI INI
    public function getAvailableYears()
    {
        $sql = "SELECT DISTINCT tahun 
                FROM " . $this->table_name . " 
                WHERE tahun IS NOT NULL 
                ORDER BY tahun DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}