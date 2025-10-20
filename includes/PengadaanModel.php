<?php
class PengadaanModel {
    private $conn;
    private $table_name = "rup_keseluruhan";

    public function __construct($db) {
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

    // Ambil data utama dengan filter + pagination
    public function getPengadaanData($filters = [], $limit = 50, $offset = 0) {
        $sql = "SELECT * FROM " . $this->table_name . " WHERE 1=1";
        $params = [];

        // Filter bulan dan tahun
        // Konversi bulan angka (07) ke nama (Juli)
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

        if (!empty($filters['jenis_pengadaan'])) {
            $sql .= " AND Jenis_Pengadaan = :jenis_pengadaan";
            $params[':jenis_pengadaan'] = $filters['jenis_pengadaan'];
        }
        if (!empty($filters['klpd'])) {
            $sql .= " AND KLPD = :klpd";
            $params[':klpd'] = $filters['klpd'];
        }
        if (!empty($filters['usaha_kecil'])) {
            $sql .= " AND Usaha_Kecil = :usaha_kecil";
            $params[':usaha_kecil'] = $filters['usaha_kecil'];
        }
        if (!empty($filters['metode'])) {
            $sql .= " AND Metode = :metode";
            $params[':metode'] = $filters['metode'];
        }
        // TAMBAHAN: Filter Perubahan
        if (!empty($filters['perubahan'])) {
            $sql .= " AND perubahan = :perubahan";
            $params[':perubahan'] = $filters['perubahan'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (Paket LIKE :search OR Lokasi LIKE :search)";
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
    public function getTotalCount($filters = []) {
        $sql = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE 1=1";
        $params = [];

        // Filter bulan dan tahun
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

        if (!empty($filters['jenis_pengadaan'])) {
            $sql .= " AND Jenis_Pengadaan = :jenis_pengadaan";
            $params[':jenis_pengadaan'] = $filters['jenis_pengadaan'];
        }
        if (!empty($filters['klpd'])) {
            $sql .= " AND KLPD = :klpd";
            $params[':klpd'] = $filters['klpd'];
        }
        if (!empty($filters['usaha_kecil'])) {
            $sql .= " AND Usaha_Kecil = :usaha_kecil";
            $params[':usaha_kecil'] = $filters['usaha_kecil'];
        }
        if (!empty($filters['metode'])) {
            $sql .= " AND Metode = :metode";
            $params[':metode'] = $filters['metode'];
        }
        // TAMBAHAN: Filter Perubahan
        if (!empty($filters['perubahan'])) {
            $sql .= " AND perubahan = :perubahan";
            $params[':perubahan'] = $filters['perubahan'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (Paket LIKE :search OR Lokasi LIKE :search)";
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

    // BARU: Ambil summary data dengan filter bulan
    public function getSummaryData($filters = []) {
        $sql = "SELECT 
                COUNT(*) as total_paket,
                SUM(Pagu_Rp) as total_pagu,
                AVG(Pagu_Rp) as avg_pagu,
                COUNT(DISTINCT KLPD) as total_klpd
                FROM " . $this->table_name . " WHERE 1=1";
        $params = [];

        // Filter bulan dan tahun
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

        if (!empty($filters['jenis_pengadaan'])) {
            $sql .= " AND Jenis_Pengadaan = :jenis_pengadaan";
            $params[':jenis_pengadaan'] = $filters['jenis_pengadaan'];
        }
        if (!empty($filters['klpd'])) {
            $sql .= " AND KLPD = :klpd";
            $params[':klpd'] = $filters['klpd'];
        }
        if (!empty($filters['metode'])) {
            $sql .= " AND Metode = :metode";
            $params[':metode'] = $filters['metode'];
        }
        // TAMBAHAN: Filter Perubahan
        if (!empty($filters['perubahan'])) {
            $sql .= " AND perubahan = :perubahan";
            $params[':perubahan'] = $filters['perubahan'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (Paket LIKE :search OR Lokasi LIKE :search)";
            $params[':search'] = "%" . $filters['search'] . "%";
        }

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // BARU: Breakdown berdasarkan jenis pengadaan
    public function getBreakdownByJenis($filters = []) {
        $sql = "SELECT 
                Jenis_Pengadaan,
                COUNT(*) as count,
                SUM(Pagu_Rp) as total_pagu
                FROM " . $this->table_name . " WHERE 1=1";
        $params = [];

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

        if (!empty($filters['klpd'])) {
            $sql .= " AND KLPD = :klpd";
            $params[':klpd'] = $filters['klpd'];
        }

        // TAMBAHAN: Filter Perubahan
        if (!empty($filters['perubahan'])) {
            $sql .= " AND perubahan = :perubahan";
            $params[':perubahan'] = $filters['perubahan'];
        }

        $sql .= " GROUP BY Jenis_Pengadaan ORDER BY total_pagu DESC";

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[$row['Jenis_Pengadaan']] = [
                'count' => (int)$row['count'],
                'total_pagu' => (float)$row['total_pagu']
            ];
        }

        return $result;
    }

    // BARU: Breakdown berdasarkan KLPD
    public function getBreakdownByKLPD($filters = []) {
        $sql = "SELECT 
                KLPD,
                COUNT(*) as count,
                SUM(Pagu_Rp) as total_pagu
                FROM " . $this->table_name . " WHERE 1=1";
        $params = [];

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

        if (!empty($filters['jenis_pengadaan'])) {
            $sql .= " AND Jenis_Pengadaan = :jenis_pengadaan";
            $params[':jenis_pengadaan'] = $filters['jenis_pengadaan'];
        }

        // TAMBAHAN: Filter Perubahan
        if (!empty($filters['perubahan'])) {
            $sql .= " AND perubahan = :perubahan";
            $params[':perubahan'] = $filters['perubahan'];
        }

        $sql .= " GROUP BY KLPD ORDER BY total_pagu DESC";

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[$row['KLPD']] = [
                'count' => (int)$row['count'],
                'total_pagu' => (float)$row['total_pagu']
            ];
        }

        return $result;
    }

    // BARU: Breakdown berdasarkan Metode
    public function getBreakdownByMetode($filters = []) {
        $sql = "SELECT 
                Metode,
                COUNT(*) as count,
                SUM(Pagu_Rp) as total_pagu
                FROM " . $this->table_name . " WHERE 1=1";
        $params = [];

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

        if (!empty($filters['jenis_pengadaan'])) {
            $sql .= " AND Jenis_Pengadaan = :jenis_pengadaan";
            $params[':jenis_pengadaan'] = $filters['jenis_pengadaan'];
        }

        if (!empty($filters['klpd'])) {
            $sql .= " AND KLPD = :klpd";
            $params[':klpd'] = $filters['klpd'];
        }

        // TAMBAHAN: Filter Perubahan
        if (!empty($filters['perubahan'])) {
            $sql .= " AND perubahan = :perubahan";
            $params[':perubahan'] = $filters['perubahan'];
        }

        $sql .= " GROUP BY Metode ORDER BY total_pagu DESC";

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[$row['Metode']] = [
                'count' => (int)$row['count'],
                'total_pagu' => (float)$row['total_pagu']
            ];
        }

        return $result;
    }

    // BARU: Breakdown berdasarkan Status Perubahan
    public function getBreakdownByPerubahan($filters = []) {
        $sql = "SELECT 
                perubahan,
                COUNT(*) as count,
                SUM(Pagu_Rp) as total_pagu
                FROM " . $this->table_name . " WHERE 1=1";
        $params = [];

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

        if (!empty($filters['jenis_pengadaan'])) {
            $sql .= " AND Jenis_Pengadaan = :jenis_pengadaan";
            $params[':jenis_pengadaan'] = $filters['jenis_pengadaan'];
        }

        if (!empty($filters['klpd'])) {
            $sql .= " AND KLPD = :klpd";
            $params[':klpd'] = $filters['klpd'];
        }

        $sql .= " GROUP BY perubahan ORDER BY total_pagu DESC";

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[$row['perubahan']] = [
                'count' => (int)$row['count'],
                'total_pagu' => (float)$row['total_pagu']
            ];
        }

        return $result;
    }

    // Ambil nilai unik untuk dropdown
    public function getDistinctValues($column) {
        $sql = "SELECT DISTINCT $column FROM " . $this->table_name . " WHERE $column IS NOT NULL AND $column != '' ORDER BY $column ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Ambil tahun yang tersedia
    public function getAvailableYears() {
        $sql = "SELECT DISTINCT tahun FROM " . $this->table_name . " WHERE tahun IS NOT NULL ORDER BY tahun DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // BARU: Ambil bulan yang tersedia (dalam format nama Indonesia)
    public function getAvailableMonths($tahun = null) {
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

    // Statistik sederhana
    public function getStatistics($filters = []) {
        $sql = "SELECT Jenis_Pengadaan, COUNT(*) as total FROM " . $this->table_name . " WHERE 1=1";
        $params = [];

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
        }

        if (!empty($filters['tanggal_awal']) && !empty($filters['tanggal_akhir'])) {
            $sql .= " AND Pemilihan BETWEEN :tanggal_awal AND :tanggal_akhir";
            $params[':tanggal_awal'] = $filters['tanggal_awal'];
            $params[':tanggal_akhir'] = $filters['tanggal_akhir'];
        }

        // TAMBAHAN: Filter Perubahan
        if (!empty($filters['perubahan'])) {
            $sql .= " AND perubahan = :perubahan";
            $params[':perubahan'] = $filters['perubahan'];
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