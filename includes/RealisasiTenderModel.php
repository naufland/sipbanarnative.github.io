<?php
// =================================================================
// == RealisasiTenderModel.php (MODEL) - FIXED =====================
// =================================================================

class RealisasiTenderModel
{
    private $conn;
    private $table_name = "realisasi_tender";

    public function __construct($db)
    {
        $this->conn = $db;
    }

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

        // Filter bulan dan tahun (kolom: bulan ENUM dan tahun YEAR)
        if (!empty($filters['bulan']) && !empty($filters['tahun'])) {
            $bulanNama = $this->getBulanNama($filters['bulan']);
            if ($bulanNama) {
                $whereClause .= " AND bulan = :bulan AND tahun = :tahun";
                $params[':bulan'] = $bulanNama;
                $params[':tahun'] = (int)$filters['tahun'];
            }
        } elseif (!empty($filters['tahun'])) {
            $whereClause .= " AND tahun = :tahun";
            $params[':tahun'] = (int)$filters['tahun'];
        }

        // Filter Tahun Anggaran
        if (!empty($filters['tahun_anggaran'])) {
            $whereClause .= " AND Tahun_Anggaran = :tahun_anggaran";
            $params[':tahun_anggaran'] = (int)$filters['tahun_anggaran'];
        }

        // Filter Jenis Pengadaan
        if (!empty($filters['jenis_pengadaan'])) {
            $whereClause .= " AND Jenis_Pengadaan = :jenis_pengadaan";
            $params[':jenis_pengadaan'] = $filters['jenis_pengadaan'];
        }

        // ✅ Filter Nama Satker - DITAMBAHKAN
        if (!empty($filters['nama_satker'])) {
            $whereClause .= " AND Nama_Satker = :nama_satker";
            $params[':nama_satker'] = $filters['nama_satker'];
        }

        // Filter KLPD (tetap ada untuk backward compatibility)
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

        // Filter Jenis Kontrak
        if (!empty($filters['jenis_kontrak'])) {
            $whereClause .= " AND Jenis_Kontrak = :jenis_kontrak";
            $params[':jenis_kontrak'] = $filters['jenis_kontrak'];
        }

        // Search: Nama Paket, Nama Pemenang, Nama Satker, Kode Tender
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
    
    public function getSummaryWithFilters($filters = [])
    {
        list($whereClause, $params) = $this->buildWhereClause($filters);
        
        $sql = "SELECT 
                    COUNT(No) as total_paket, 
                    SUM(Nilai_Pagu) as total_pagu, 
                    SUM(Nilai_HPS) as total_hps, 
                    SUM(Nilai_Kontrak) as total_kontrak,
                    SUM(Nilai_PDN) as total_pdn,
                    SUM(Nilai_UMK) as total_umk
                FROM " . $this->table_name . $whereClause;
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'total_paket' => (int)($result['total_paket'] ?? 0),
            'total_pagu' => (float)($result['total_pagu'] ?? 0),
            'total_hps' => (float)($result['total_hps'] ?? 0),
            'total_kontrak' => (float)($result['total_kontrak'] ?? 0),
            'total_pdn' => (float)($result['total_pdn'] ?? 0),
            'total_umk' => (float)($result['total_umk'] ?? 0)
        ];
    }

    public function getAllDataForSummary($filters = []) {
        list($whereClause, $params) = $this->buildWhereClause($filters);
        $sql = "SELECT Jenis_Pengadaan, KLPD, Nama_Satker, Metode_Pengadaan, Sumber_Dana, Jenis_Kontrak, Nilai_Pagu, Nilai_HPS, Nilai_Kontrak FROM " . $this->table_name . $whereClause;
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDistinctValues($column)
    {
        $allowedColumns = ['Jenis_Pengadaan', 'KLPD', 'Metode_Pengadaan', 'Sumber_Dana', 'Jenis_Kontrak', 'Nama_Satker'];
        if (!in_array($column, $allowedColumns)) return [];
        
        $sql = "SELECT DISTINCT $column FROM " . $this->table_name . " WHERE $column IS NOT NULL AND $column != '' ORDER BY $column ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    public function getAvailableYears()
    {
        $sql = "SELECT DISTINCT tahun FROM " . $this->table_name . " WHERE tahun IS NOT NULL ORDER BY tahun DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getAvailableMonths($tahun = null)
    {
        $sql = "SELECT DISTINCT bulan FROM " . $this->table_name . " WHERE bulan IS NOT NULL";
        $params = [];
        if (!empty($tahun)) {
            $sql .= " AND tahun = :tahun";
            $params[':tahun'] = (int)$tahun;
        }
        $sql .= " ORDER BY FIELD(bulan, 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember')";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getMonthlySummary($tahun)
    {
        $sql = "SELECT 
                    bulan, 
                    COUNT(No) as total_paket, 
                    SUM(Nilai_Pagu) as total_pagu, 
                    SUM(Nilai_HPS) as total_hps, 
                    SUM(Nilai_Kontrak) as total_kontrak 
                FROM " . $this->table_name . " 
                WHERE tahun = :tahun 
                GROUP BY bulan 
                ORDER BY FIELD(bulan, 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember')";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':tahun', (int)$tahun, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getEfficiencyStats($filters = [])
    {
        $summary = $this->getSummaryWithFilters($filters);
        $efisiensi = 0;
        if ($summary['total_pagu'] > 0) {
            $efisiensi = (($summary['total_pagu'] - $summary['total_kontrak']) / $summary['total_pagu']) * 100;
        }
        return ['efisiensi_persen' => round($efisiensi, 2)] + $summary;
    }

    public function getSummaryByJenisPengadaan($filters = [])
    {
        list($whereClause, $params) = $this->buildWhereClause($filters);
        $sql = "SELECT Jenis_Pengadaan, COUNT(No) as total_paket, SUM(Nilai_Pagu) as total_pagu, SUM(Nilai_Kontrak) as total_kontrak FROM " . $this->table_name . $whereClause . " GROUP BY Jenis_Pengadaan ORDER BY total_paket DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSummaryByKLPD($filters = [])
    {
        list($whereClause, $params) = $this->buildWhereClause($filters);
        $sql = "SELECT KLPD, COUNT(No) as total_paket, SUM(Nilai_Pagu) as total_pagu, SUM(Nilai_Kontrak) as total_kontrak FROM " . $this->table_name . $whereClause . " GROUP BY KLPD ORDER BY total_paket DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ✅ Fungsi baru untuk summary berdasarkan Satker
    public function getSummaryBySatker($filters = [])
    {
        list($whereClause, $params) = $this->buildWhereClause($filters);
        $sql = "SELECT Nama_Satker, COUNT(No) as total_paket, SUM(Nilai_Pagu) as total_pagu, SUM(Nilai_Kontrak) as total_kontrak FROM " . $this->table_name . $whereClause . " GROUP BY Nama_Satker ORDER BY total_paket DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function readByMonth($month, $year)
    {
        $bulanNama = $this->getBulanNama($month);
        if (!$bulanNama) return false;
        
        $query = "SELECT * FROM " . $this->table_name . " WHERE bulan = :bulan AND tahun = :tahun";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':bulan', $bulanNama);
        $stmt->bindParam(':tahun', $year);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotalByMonth($year)
    {
        $query = "SELECT 
                    bulan, 
                    tahun, 
                    SUM(Nilai_HPS) as total_hps, 
                    SUM(Nilai_Kontrak) as total_kontrak 
                  FROM " . $this->table_name . " 
                  WHERE tahun = :tahun 
                  GROUP BY bulan, tahun 
                  ORDER BY FIELD(bulan, 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember')";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':tahun', $year, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>