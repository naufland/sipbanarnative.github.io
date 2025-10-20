<?php

class SwakelolaModel {
    private $conn;
    private $table = 'rup_swakelola';

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Helper: Konversi angka bulan (01-12) ke nama bulan Indonesia
     */
    private function getBulanNama($bulanAngka) {
        $mapping = [
            '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
            '04' => 'April', '05' => 'Mei', '06' => 'Juni',
            '07' => 'Juli', '08' => 'Agustus', '09' => 'September',
            '10' => 'Oktober', '11' => 'November', '12' => 'Desember',
            '1' => 'Januari', '2' => 'Februari', '3' => 'Maret',
            '4' => 'April', '5' => 'Mei', '6' => 'Juni',
            '7' => 'Juli', '8' => 'Agustus', '9' => 'September',
            '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
        ];
        return $mapping[$bulanAngka] ?? null;
    }

    /**
     * Ambil data swakelola dengan filter dan pagination
     */
    public function getSwakelolaData($filters = [], $limit = 25, $offset = 0) {
        $sql = "SELECT * FROM {$this->table} WHERE 1=1";
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

        // TAMBAHAN: Filter Perubahan
        if (!empty($filters['perubahan'])) {
            $sql .= " AND perubahan = :perubahan";
            $params[':perubahan'] = $filters['perubahan'];
        }

        // Filter range pagu
        if (!empty($filters['pagu_min'])) {
            $paguMin = preg_replace('/[^\d]/', '', $filters['pagu_min']);
            $sql .= " AND Pagu_Rp >= :pagu_min";
            $params[':pagu_min'] = (int)$paguMin;
        }
        if (!empty($filters['pagu_max'])) {
            $paguMax = preg_replace('/[^\d]/', '', $filters['pagu_max']);
            $sql .= " AND Pagu_Rp <= :pagu_max";
            $params[':pagu_max'] = (int)$paguMax;
        }

        // Filter pencarian
        if (!empty($filters['search'])) {
            $sql .= " AND (Paket LIKE :search OR Lokasi LIKE :search)";
            $params[':search'] = "%" . $filters['search'] . "%";
        }

        $sql .= " ORDER BY No ASC LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($sql);

        foreach ($params as $key => $value) {
            if (is_int($value)) {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value, PDO::PARAM_STR);
            }
        }

        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Hitung total record dengan filter
     */
    public function getTotalCount($filters = []) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE 1=1";
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

        // TAMBAHAN: Filter Perubahan
        if (!empty($filters['perubahan'])) {
            $sql .= " AND perubahan = :perubahan";
            $params[':perubahan'] = $filters['perubahan'];
        }

        if (!empty($filters['pagu_min'])) {
            $paguMin = preg_replace('/[^\d]/', '', $filters['pagu_min']);
            $sql .= " AND Pagu_Rp >= :pagu_min";
            $params[':pagu_min'] = (int)$paguMin;
        }

        if (!empty($filters['pagu_max'])) {
            $paguMax = preg_replace('/[^\d]/', '', $filters['pagu_max']);
            $sql .= " AND Pagu_Rp <= :pagu_max";
            $params[':pagu_max'] = (int)$paguMax;
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (Paket LIKE :search OR Lokasi LIKE :search)";
            $params[':search'] = "%" . $filters['search'] . "%";
        }

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            if (is_int($value)) {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value, PDO::PARAM_STR);
            }
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row['total'] ?? 0;
    }

    /**
     * Ambil distinct values untuk dropdown
     */
    public function getDistinctValues($column, $filters = []) {
        $sql = "SELECT DISTINCT $column FROM {$this->table} WHERE 1=1 AND $column IS NOT NULL AND $column != ''";
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
                $sql .= " AND bulan = :bulan";
                $params[':bulan'] = $bulanNama;
            }
        }

        $sql .= " ORDER BY $column ASC";

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Ambil tahun yang tersedia
     */
    public function getAvailableYears() {
        $sql = "SELECT DISTINCT tahun FROM {$this->table} WHERE tahun IS NOT NULL ORDER BY tahun DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Ambil bulan yang tersedia untuk tahun tertentu
     */
    public function getAvailableMonths($tahun = null) {
        $sql = "SELECT DISTINCT MONTH(STR_TO_DATE(CONCAT(bulan), '%M')) as bulan_num FROM {$this->table} WHERE bulan IS NOT NULL";
        
        if ($tahun) {
            $sql .= " AND tahun = :tahun";
        }

        $sql .= " ORDER BY bulan_num ASC";

        $stmt = $this->conn->prepare($sql);
        if ($tahun) {
            $stmt->bindValue(':tahun', $tahun, PDO::PARAM_STR);
        }
        $stmt->execute();

        $result = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return array_filter(array_map('intval', $result));
    }

    /**
     * Ambil summary/ringkasan data
     */
    public function getSummary($filters = []) {
        $sql = "SELECT 
                COUNT(*) as total_paket,
                SUM(CAST(Pagu_Rp AS UNSIGNED)) as total_pagu,
                AVG(CAST(Pagu_Rp AS UNSIGNED)) as avg_pagu,
                MIN(CAST(Pagu_Rp AS UNSIGNED)) as min_pagu,
                MAX(CAST(Pagu_Rp AS UNSIGNED)) as max_pagu,
                COUNT(DISTINCT KLPD) as total_klpd,
                COUNT(DISTINCT Tipe_Swakelola) as total_tipe
                FROM {$this->table} WHERE 1=1";
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

        if (!empty($filters['tipe_swakelola'])) {
            $sql .= " AND Tipe_Swakelola = :tipe_swakelola";
            $params[':tipe_swakelola'] = $filters['tipe_swakelola'];
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

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Ambil statistik per bulan untuk tahun tertentu
     */
    public function getMonthlyStatistics($tahun = null) {
        $tahun = $tahun ?? date('Y');

        $sql = "SELECT 
                bulan,
                COUNT(*) as total_paket,
                SUM(CAST(Pagu_Rp AS UNSIGNED)) as total_pagu
                FROM {$this->table}
                WHERE tahun = :tahun
                GROUP BY bulan
                ORDER BY FIELD(bulan, 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember')";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':tahun', $tahun);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ambil statistik per KLPD
     */
    public function getKLPDStatistics($filters = []) {
        $sql = "SELECT 
                KLPD,
                COUNT(*) as total_paket,
                SUM(CAST(Pagu_Rp AS UNSIGNED)) as total_pagu,
                AVG(CAST(Pagu_Rp AS UNSIGNED)) as avg_pagu
                FROM {$this->table} WHERE 1=1";
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

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Cek apakah ada data untuk bulan tertentu
     */
    public function hasDataForMonth($bulan, $tahun) {
        $bulanNama = $this->getBulanNama($bulan);
        
        if (!$bulanNama) {
            return false;
        }

        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE bulan = :bulan AND tahun = :tahun";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':bulan', $bulanNama);
        $stmt->bindValue(':tahun', $tahun);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return ($result['total'] ?? 0) > 0;
    }

    /**
     * Ambil data untuk export
     */
    public function exportData($filters = []) {
        $sql = "SELECT * FROM {$this->table} WHERE 1=1";
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

        if (!empty($filters['tipe_swakelola'])) {
            $sql .= " AND Tipe_Swakelola = :tipe_swakelola";
            $params[':tipe_swakelola'] = $filters['tipe_swakelola'];
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

        $sql .= " ORDER BY No ASC";

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ambil statistik sederhana
     */
    public function getStatistics($filters = []) {
        $sql = "SELECT 
                Tipe_Swakelola,
                COUNT(*) as total
                FROM {$this->table} WHERE 1=1";
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

        $sql .= " GROUP BY Tipe_Swakelola ORDER BY total DESC";

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}