<?php
// File: includes/PencatatanNontenderModel.php

class PencatatanNontenderModel
{
    private $conn;
    private $table_name = "pencatatan_nontender";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Helper: Konversi angka bulan ke nama bulan Indonesia
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

        // Filter berdasarkan bulan dan tahun
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

        // Filter berdasarkan Nama_Satker (bukan KLPD)
        if (!empty($filters['nama_satker'])) {
            $whereClause .= " AND Nama_Satker = :nama_satker";
            $params[':nama_satker'] = $filters['nama_satker'];
        }

        // Filter berdasarkan Metode Pengadaan
        if (!empty($filters['metode_pengadaan'])) {
            $whereClause .= " AND Metode_pengadaan = :metode_pengadaan";
            $params[':metode_pengadaan'] = $filters['metode_pengadaan'];
        }

        // Filter berdasarkan Jenis Pengadaan
        if (!empty($filters['jenis_pengadaan'])) {
            $whereClause .= " AND Jenis_Pengadaan = :jenis_pengadaan";
            $params[':jenis_pengadaan'] = $filters['jenis_pengadaan'];
        }

        // Filter berdasarkan Status Paket
        if (!empty($filters['status_paket'])) {
            $whereClause .= " AND Status_Paket = :status_paket";
            $params[':status_paket'] = $filters['status_paket'];
        }

        // Filter berdasarkan pencarian
        if (!empty($filters['search'])) {
            $whereClause .= " AND (Nama_Paket LIKE :search OR Nama_Pemenang LIKE :search OR Nama_Satker LIKE :search OR Kode_Paket LIKE :search)";
            $params[':search'] = "%" . $filters['search'] . "%";
        }

        return [$whereClause, $params];
    }

    private function convertToFloat($value)
    {
        if (is_null($value) || $value === '') {
            return 0.0;
        }

        if (is_numeric($value)) {
            return floatval($value);
        }

        if (is_string($value)) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        }

        return floatval($value);
    }

    public function getPencatatanNontenderData($filters = [], $limit = 50, $offset = 0)
    {
        list($whereClause, $params) = $this->buildWhereClause($filters);

        $sql = "SELECT 
                tahun,
                bulan,
                Kode_Paket, 
                Nama_Paket, 
                Kode_RUP, 
                KLPD, 
                Nama_Satker, 
                Jenis_Pengadaan, 
                Metode_pengadaan, 
                Nilai_Pagu, 
                Nama_Pemenang, 
                Nilai_Total_Realisasi, 
                Nilai_PDN, 
                Nilai_UMK, 
                Sumber_Dana, 
                Status_Paket 
                FROM " . $this->table_name . $whereClause . " 
                ORDER BY tahun DESC, bulan DESC, Kode_Paket ASC 
                LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as &$row) {
            $row['Nilai_Pagu'] = $this->convertToFloat($row['Nilai_Pagu'] ?? 0);
            $row['Nilai_Total_Realisasi'] = $this->convertToFloat($row['Nilai_Total_Realisasi'] ?? 0);
            $row['Nilai_PDN'] = $this->convertToFloat($row['Nilai_PDN'] ?? 0);
            $row['Nilai_UMK'] = $this->convertToFloat($row['Nilai_UMK'] ?? 0);
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

    public function getSummaryData($filters = [])
    {
        list($whereClause, $params) = $this->buildWhereClause($filters);

        $sql = "SELECT 
                    COUNT(*) as total_paket,
                    COALESCE(SUM(
                        CASE 
                            WHEN Nilai_Pagu REGEXP '^[0-9.]+$' 
                            THEN CAST(REPLACE(Nilai_Pagu, '.', '') AS DECIMAL(20,2))
                            ELSE 0 
                        END
                    ), 0) as total_pagu,
                    COALESCE(SUM(
                        CASE 
                            WHEN Nilai_Total_Realisasi REGEXP '^[0-9.]+$' 
                            THEN CAST(REPLACE(Nilai_Total_Realisasi, '.', '') AS DECIMAL(20,2))
                            ELSE 0 
                        END
                    ), 0) as total_nilai_realisasi,
                    COALESCE(SUM(
                        CASE 
                            WHEN Nilai_PDN REGEXP '^[0-9.]+$' 
                            THEN CAST(REPLACE(Nilai_PDN, '.', '') AS DECIMAL(20,2))
                            ELSE 0 
                        END
                    ), 0) as total_pdn,
                    COALESCE(SUM(
                        CASE 
                            WHEN Nilai_UMK REGEXP '^[0-9.]+$' 
                            THEN CAST(REPLACE(Nilai_UMK, '.', '') AS DECIMAL(20,2))
                            ELSE 0 
                        END
                    ), 0) as total_umk
                FROM " . $this->table_name . $whereClause;

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total_paket' => (int)($result['total_paket'] ?? 0),
            'total_pagu' => $this->convertToFloat($result['total_pagu'] ?? 0),
            'total_realisasi' => $this->convertToFloat($result['total_nilai_realisasi'] ?? 0),
            'total_pdn' => $this->convertToFloat($result['total_pdn'] ?? 0),
            'total_umk' => $this->convertToFloat($result['total_umk'] ?? 0)
        ];
    }

    public function getAllDataForSummary($filters = [])
    {
        list($whereClause, $params) = $this->buildWhereClause($filters);
        $sql = "SELECT 
                    Metode_pengadaan,
                    Jenis_Pengadaan,
                    Nama_Satker,
                    Status_Paket,
                    Nilai_Pagu, 
                    Nilai_Total_Realisasi,
                    Nilai_PDN,
                    Nilai_UMK,
                    Nama_Pemenang
                FROM " . $this->table_name . $whereClause;
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as &$row) {
            $row['Nilai_Pagu'] = $this->convertToFloat($row['Nilai_Pagu'] ?? 0);
            $row['Nilai_Total_Realisasi'] = $this->convertToFloat($row['Nilai_Total_Realisasi'] ?? 0);
            $row['Nilai_PDN'] = $this->convertToFloat($row['Nilai_PDN'] ?? 0);
            $row['Nilai_UMK'] = $this->convertToFloat($row['Nilai_UMK'] ?? 0);
        }

        return $results;
    }

    public function getDistinctValues($column)
    {
        $allowedColumns = ['Metode_pengadaan', 'Jenis_Pengadaan', 'Nama_Satker', 'Status_Paket'];
        if (!in_array($column, $allowedColumns)) return [];

        $sql = "SELECT DISTINCT $column FROM " . $this->table_name . " WHERE $column IS NOT NULL AND $column != '' ORDER BY $column ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getAvailableYears()
    {
        // 1. Ambil tahun yang sudah ada di database
        $sql = "SELECT DISTINCT tahun 
                FROM " . $this->table_name . " 
                WHERE tahun IS NOT NULL 
                ORDER BY tahun DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $dbYears = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // 2. Buat rentang tahun manual (Misal: dari 2023 sampai tahun depan)
        $tahunSekarang = date('Y');
        $tahunMulai = 2023; // Ubah ini jika ingin mundur lebih jauh
        $tahunAkhir = $tahunSekarang + 1; // Agar bisa input untuk tahun depan
        
        $manualYears = range($tahunAkhir, $tahunMulai); // Membuat array [2026, 2025, 2024, 2023]

        // 3. Gabungkan array database dan manual, lalu hapus duplikat
        $allYears = array_unique(array_merge($dbYears, $manualYears));

        // 4. Urutkan dari yang terbaru (Descending)
        rsort($allYears);

        return $allYears;
    }

    // BARU: Ambil bulan yang tersedia (dalam format nama Indonesia)
    public function getAvailableMonths($tahun = null)
    {
        $sql = "SELECT DISTINCT bulan FROM " . $this->table_name . " WHERE bulan IS NOT NULL";
        
        if ($tahun) {
            $sql .= " AND tahun = :tahun";
        }
        
        $sql .= " ORDER BY 
            CASE bulan
                WHEN 'Januari' THEN 1   
                WHEN 'Februari' THEN 2
                WHEN 'Maret' THEN 3
                WHEN 'April' THEN 4
                WHEN 'Mei' THEN 5
                WHEN 'Juni' THEN 6
                WHEN 'Juli' THEN 7
                WHEN 'Agustus' THEN 8
                WHEN 'September' THEN 9
                WHEN 'Oktober' THEN 10
                WHEN 'November' THEN 11
                WHEN 'Desember' THEN 12
            END ASC";
        
        $stmt = $this->conn->prepare($sql);
        
        if ($tahun) {
            $stmt->bindValue(':tahun', $tahun, PDO::PARAM_STR);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}