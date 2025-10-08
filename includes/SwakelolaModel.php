<?php
class SwakelolaModel
{
    private $conn;
    private $table_name = "rup_swakelola"; // ganti sesuai nama tabel di DB

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

    public function getSummary($filters)
    {
        // GUNAKAN QUERY AGREGASI LANGSUNG SEPERTI PengadaanModel
        $sql = "SELECT 
                    COUNT(*) as total_paket,
                    SUM(Pagu_Rp) as total_pagu,
                    AVG(Pagu_Rp) as avg_pagu,
                    COUNT(DISTINCT KLPD) as total_klpd
                FROM " . $this->table_name . " WHERE 1=1";
        $params = [];

        // Filter bulan dan tahun (SAMA SEPERTI PengadaanModel)
        if (!empty($filters['bulan']) && !empty($filters['tahun'])) {
            $bulanNama = $this->getBulanNama($filters['bulan']);
            if ($bulanNama) {
                $sql .= " AND bulan = :bulan AND tahun = :tahun";
                $params[':bulan'] = $bulanNama;
                $params[':tahun'] = $filters['tahun'];
            }
        } elseif (!empty($filters['tahun'])) {
            $sql .= " AND tahun = :tahun";
            $params[':tahun'] = $filters['tahun'];
        } elseif (!empty($filters['bulan'])) {
            $bulanNama = $this->getBulanNama($filters['bulan']);
            if ($bulanNama) {
                $sql .= " AND bulan = :bulan AND tahun = :tahun_sekarang";
                $params[':bulan'] = $bulanNama;
                $params[':tahun_sekarang'] = date('Y');
            }
        }

        // Filter range tanggal
        if (!empty($filters['tanggal_awal']) && !empty($filters['tanggal_akhir'])) {
            $sql .= " AND Pemilihan BETWEEN :tanggal_awal AND :tanggal_akhir";
            $params[':tanggal_awal'] = $filters['tanggal_awal'];
            $params[':tanggal_akhir'] = $filters['tanggal_akhir'];
        }

        // Filter tipe swakelola
        if (!empty($filters['tipe_swakelola'])) {
            $sql .= " AND Tipe_Swakelola = :tipe_swakelola";
            $params[':tipe_swakelola'] = $filters['tipe_swakelola'];
        }

        // Filter KLPD
        if (!empty($filters['klpd'])) {
            $sql .= " AND KLPD = :klpd";
            $params[':klpd'] = $filters['klpd'];
        }

        // Filter Satuan Kerja
        if (!empty($filters['satuan_kerja'])) {
            $sql .= " AND Satuan_Kerja = :satuan_kerja";
            $params[':satuan_kerja'] = $filters['satuan_kerja'];
        }

        // Filter range pagu
        if (!empty($filters['pagu_min'])) {
            $sql .= " AND Pagu_Rp >= :pagu_min";
            $params[':pagu_min'] = (int)preg_replace('/[^\d]/', '', $filters['pagu_min']);
        }

        if (!empty($filters['pagu_max'])) {
            $sql .= " AND Pagu_Rp <= :pagu_max";
            $params[':pagu_max'] = (int)preg_replace('/[^\d]/', '', $filters['pagu_max']);
        }

        // Filter pencarian
        if (!empty($filters['search'])) {
            $sql .= " AND (Paket LIKE :search OR Lokasi LIKE :search OR ID LIKE :search)";
            $params[':search'] = "%" . $filters['search'] . "%";
        }

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total_paket' => (int)($result['total_paket'] ?? 0),
            'total_pagu'  => (float)($result['total_pagu'] ?? 0),
            'avg_pagu'    => (float)($result['avg_pagu'] ?? 0),
            'total_klpd'  => (int)($result['total_klpd'] ?? 0)
        ];
    }

    // Ambil data utama dengan filter + pagination
    public function getSwakelolaData($filters = [], $limit = 50, $offset = 0)
    {
        $sql = "SELECT * FROM " . $this->table_name . " WHERE 1=1";
        $params = [];

        // Filter bulan dan tahun (SAMA SEPERTI PengadaanModel)
        if (!empty($filters['bulan']) && !empty($filters['tahun'])) {
            $bulanNama = $this->getBulanNama($filters['bulan']);
            if ($bulanNama) {
                $sql .= " AND bulan = :bulan AND tahun = :tahun";
                $params[':bulan'] = $bulanNama;
                $params[':tahun'] = $filters['tahun'];
            }
        } elseif (!empty($filters['tahun'])) {
            $sql .= " AND tahun = :tahun";
            $params[':tahun'] = $filters['tahun'];
        } elseif (!empty($filters['bulan'])) {
            $bulanNama = $this->getBulanNama($filters['bulan']);
            if ($bulanNama) {
                $sql .= " AND bulan = :bulan AND tahun = :tahun_sekarang";
                $params[':bulan'] = $bulanNama;
                $params[':tahun_sekarang'] = date('Y');
            }
        }

        // Filter range tanggal (opsional)
        if (!empty($filters['tanggal_awal']) && !empty($filters['tanggal_akhir'])) {
            $sql .= " AND Pemilihan BETWEEN :tanggal_awal AND :tanggal_akhir";
            $params[':tanggal_awal'] = $filters['tanggal_awal'];
            $params[':tanggal_akhir'] = $filters['tanggal_akhir'];
        }

        // Filter tipe swakelola
        if (!empty($filters['tipe_swakelola'])) {
            $sql .= " AND Tipe_Swakelola = :tipe_swakelola";
            $params[':tipe_swakelola'] = $filters['tipe_swakelola'];
        }

        // Filter KLPD
        if (!empty($filters['klpd'])) {
            $sql .= " AND KLPD = :klpd";
            $params[':klpd'] = $filters['klpd'];
        }

        // Filter Satuan Kerja
        if (!empty($filters['satuan_kerja'])) {
            $sql .= " AND Satuan_Kerja = :satuan_kerja";
            $params[':satuan_kerja'] = $filters['satuan_kerja'];
        }

        // Filter range pagu
        if (!empty($filters['pagu_min'])) {
            $sql .= " AND Pagu_Rp >= :pagu_min";
            $params[':pagu_min'] = (int)preg_replace('/[^\d]/', '', $filters['pagu_min']);
        }

        if (!empty($filters['pagu_max'])) {
            $sql .= " AND Pagu_Rp <= :pagu_max";
            $params[':pagu_max'] = (int)preg_replace('/[^\d]/', '', $filters['pagu_max']);
        }

        // Filter pencarian bebas
        if (!empty($filters['search'])) {
            $sql .= " AND (Paket LIKE :search OR Lokasi LIKE :search OR ID LIKE :search)";
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
    public function getTotalCount($filters = [])
    {
        $sql = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE 1=1";
        $params = [];

        // Filter bulan dan tahun (SAMA SEPERTI PengadaanModel)
        if (!empty($filters['bulan']) && !empty($filters['tahun'])) {
            $bulanNama = $this->getBulanNama($filters['bulan']);
            if ($bulanNama) {
                $sql .= " AND bulan = :bulan AND tahun = :tahun";
                $params[':bulan'] = $bulanNama;
                $params[':tahun'] = $filters['tahun'];
            }
        } elseif (!empty($filters['tahun'])) {
            $sql .= " AND tahun = :tahun";
            $params[':tahun'] = $filters['tahun'];
        } elseif (!empty($filters['bulan'])) {
            $bulanNama = $this->getBulanNama($filters['bulan']);
            if ($bulanNama) {
                $sql .= " AND bulan = :bulan AND tahun = :tahun_sekarang";
                $params[':bulan'] = $bulanNama;
                $params[':tahun_sekarang'] = date('Y');
            }
        }

        if (!empty($filters['tanggal_awal']) && !empty($filters['tanggal_akhir'])) {
            $sql .= " AND Pemilihan BETWEEN :tanggal_awal AND :tanggal_akhir";
            $params[':tanggal_awal'] = $filters['tanggal_awal'];
            $params[':tanggal_akhir'] = $filters['tanggal_akhir'];
        }

        if (!empty($filters['tipe_swakelola'])) {
            $sql .= " AND Tipe_Swakelola = :tipe_swakelola";
            $params[':tipe_swakelola'] = $filters['tipe_swakelola'];
        }

        if (!empty($filters['klpd'])) {
            $sql .= " AND KLPD = :klpd";
            $params[':klpd'] = $filters['klpd'];
        }

        if (!empty($filters['satuan_kerja'])) {
            $sql .= " AND Satuan_Kerja = :satuan_kerja";
            $params[':satuan_kerja'] = $filters['satuan_kerja'];
        }

        // Filter range pagu
        if (!empty($filters['pagu_min'])) {
            $sql .= " AND Pagu_Rp >= :pagu_min";
            $params[':pagu_min'] = (int)preg_replace('/[^\d]/', '', $filters['pagu_min']);
        }

        if (!empty($filters['pagu_max'])) {
            $sql .= " AND Pagu_Rp <= :pagu_max";
            $params[':pagu_max'] = (int)preg_replace('/[^\d]/', '', $filters['pagu_max']);
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (Paket LIKE :search OR Lokasi LIKE :search OR ID LIKE :search)";
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

    // Ambil nilai unik untuk dropdown (misalnya tipe swakelola, klpd, satuan kerja)
    public function getDistinctValues($column, $filters = [])
    {
        $sql = "SELECT DISTINCT $column FROM " . $this->table_name . " 
                WHERE $column IS NOT NULL AND $column != ''";
        $params = [];

        // Filter berdasarkan bulan dan tahun jika ada
        if (!empty($filters['bulan'])) {
            $sql .= " AND MONTH(Pemilihan) = :bulan";
            $params[':bulan'] = (int)$filters['bulan'];
        }

        if (!empty($filters['tahun'])) {
            $sql .= " AND YEAR(Pemilihan) = :tahun";
            $params[':tahun'] = $filters['tahun'];
        }

        $sql .= " ORDER BY $column ASC";
        
        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Ambil tahun yang tersedia
    public function getAvailableYears()
    {
        $sql = "SELECT DISTINCT YEAR(Pemilihan) as tahun FROM " . $this->table_name . " 
                WHERE Pemilihan IS NOT NULL 
                ORDER BY tahun DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Ambil bulan yang tersedia untuk tahun tertentu
    public function getAvailableMonths($tahun = null)
    {
        $sql = "SELECT DISTINCT MONTH(Pemilihan) as bulan FROM " . $this->table_name . " 
                WHERE Pemilihan IS NOT NULL";
        $params = [];

        if ($tahun) {
            $sql .= " AND YEAR(Pemilihan) = :tahun";
            $params[':tahun'] = $tahun;
        }

        $sql .= " ORDER BY bulan ASC";
        
        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Statistik per tipe swakelola dengan filter bulan
    public function getStatistics($filters = [])
    {
        $sql = "SELECT Tipe_Swakelola, COUNT(*) as total, SUM(CAST(REPLACE(REPLACE(Pagu_Rp, '.', ''), ',', '') AS UNSIGNED)) as total_pagu 
                FROM " . $this->table_name . " WHERE 1=1";
        $params = [];

        // Filter bulan
        if (!empty($filters['bulan'])) {
            $sql .= " AND MONTH(Pemilihan) = :bulan";
            $params[':bulan'] = (int)$filters['bulan'];
        }

        // Filter tahun
        if (!empty($filters['tahun'])) {
            $sql .= " AND YEAR(Pemilihan) = :tahun";
            $params[':tahun'] = $filters['tahun'];
        }

        if (!empty($filters['tanggal_awal']) && !empty($filters['tanggal_akhir'])) {
            $sql .= " AND Pemilihan BETWEEN :tanggal_awal AND :tanggal_akhir";
            $params[':tanggal_awal'] = $filters['tanggal_awal'];
            $params[':tanggal_akhir'] = $filters['tanggal_akhir'];
        }

        $sql .= " GROUP BY Tipe_Swakelola ORDER BY total DESC";
        $stmt = $this->conn->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Statistik per bulan untuk tahun tertentu
    public function getMonthlyStatistics($tahun)
    {
        $sql = "SELECT 
                    MONTH(Pemilihan) as bulan,
                    MONTHNAME(Pemilihan) as nama_bulan,
                    COUNT(*) as total_paket,
                    SUM(CAST(REPLACE(REPLACE(Pagu_Rp, '.', ''), ',', '') AS UNSIGNED)) as total_pagu
                FROM " . $this->table_name . " 
                WHERE YEAR(Pemilihan) = :tahun
                GROUP BY MONTH(Pemilihan), MONTHNAME(Pemilihan)
                ORDER BY bulan ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':tahun', $tahun);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Statistik per KLPD dengan filter bulan
    public function getKLPDStatistics($filters = [])
    {
        $sql = "SELECT 
                    KLPD,
                    COUNT(*) as total_paket,
                    SUM(CAST(REPLACE(REPLACE(Pagu_Rp, '.', ''), ',', '') AS UNSIGNED)) as total_pagu
                FROM " . $this->table_name . " 
                WHERE KLPD IS NOT NULL AND KLPD != ''";
        $params = [];

        if (!empty($filters['bulan'])) {
            $sql .= " AND MONTH(Pemilihan) = :bulan";
            $params[':bulan'] = (int)$filters['bulan'];
        }

        if (!empty($filters['tahun'])) {
            $sql .= " AND YEAR(Pemilihan) = :tahun";
            $params[':tahun'] = $filters['tahun'];
        }

        $sql .= " GROUP BY KLPD ORDER BY total_pagu DESC";
        
        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Cek apakah ada data untuk bulan tertentu
    public function hasDataForMonth($bulan, $tahun)
    {
        $sql = "SELECT COUNT(*) as total FROM " . $this->table_name . " 
                WHERE MONTH(Pemilihan) = :bulan AND YEAR(Pemilihan) = :tahun";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':bulan', (int)$bulan, PDO::PARAM_INT);
        $stmt->bindValue(':tahun', $tahun);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return ($row['total'] ?? 0) > 0;
    }

    // Export data dengan filter bulan
    public function exportData($filters = [])
    {
        $sql = "SELECT * FROM " . $this->table_name . " WHERE 1=1";
        $params = [];

        if (!empty($filters['bulan'])) {
            $sql .= " AND MONTH(Pemilihan) = :bulan";
            $params[':bulan'] = (int)$filters['bulan'];
        }

        if (!empty($filters['tahun'])) {
            $sql .= " AND YEAR(Pemilihan) = :tahun";
            $params[':tahun'] = $filters['tahun'];
        }

        if (!empty($filters['tipe_swakelola'])) {
            $sql .= " AND Tipe_Swakelola = :tipe_swakelola";
            $params[':tipe_swakelola'] = $filters['tipe_swakelola'];
        }

        if (!empty($filters['klpd'])) {
            $sql .= " AND KLPD = :klpd";
            $params[':klpd'] = $filters['klpd'];
        }

        $sql .= " ORDER BY No ASC";
        
        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}