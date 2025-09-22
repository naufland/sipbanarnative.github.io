<?php
class SwakelolaModel
{
    private $conn;
    private $table_name = "rup_swakelola"; // ganti sesuai nama tabel di DB

    public function __construct($db)
    {
        $this->conn = $db;
    }
    public function getSummary($filters)
    {
        // Ambil semua data yang cocok dengan filter tanpa limit
        $allData = $this->getSwakelolaData($filters, 100000, 0); // Limit besar untuk mencakup semua data

        $totalPaket = count($allData);
        $totalPagu = 0;
        $klpdSet = [];

        foreach ($allData as $row) {
            // Pastikan Pagu_Rp adalah angka
            $paguValue = preg_replace('/[^\d]/', '', $row['Pagu_Rp']);
            $totalPagu += (int)$paguValue;

            // Kumpulkan KLPD unik
            if (!empty($row['KLPD'])) {
                $klpdSet[$row['KLPD']] = true;
            }
        }

        return [
            'total_paket' => $totalPaket,
            'total_pagu'  => $totalPagu,
            'avg_pagu'    => $totalPaket > 0 ? $totalPagu / $totalPaket : 0,
            'total_klpd'  => count($klpdSet)
        ];
    }
    // Ambil data utama dengan filter + pagination
    public function getSwakelolaData($filters = [], $limit = 50, $offset = 0)
    {
        $sql = "SELECT * FROM " . $this->table_name . " WHERE 1=1";
        $params = [];

        // Filter tahun
        if (!empty($filters['tahun'])) {
            $sql .= " AND YEAR(Pemilihan) = :tahun";
            $params[':tahun'] = $filters['tahun'];
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

        // Filter pencarian bebas
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
    public function getTotalCount($filters = [])
    {
        $sql = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE 1=1";
        $params = [];

        if (!empty($filters['tahun'])) {
            $sql .= " AND YEAR(Pemilihan) = :tahun";
            $params[':tahun'] = $filters['tahun'];
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

    // Ambil nilai unik untuk dropdown (misalnya tipe swakelola, klpd, satuan kerja)
    public function getDistinctValues($column)
    {
        $sql = "SELECT DISTINCT $column FROM " . $this->table_name . " 
                WHERE $column IS NOT NULL AND $column != '' 
                ORDER BY $column ASC";
        $stmt = $this->conn->prepare($sql);
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

    // Statistik sederhana (contoh grouping per tipe swakelola)
    public function getStatistics($filters = [])
    {
        $sql = "SELECT Tipe_Swakelola, COUNT(*) as total 
                FROM " . $this->table_name . " WHERE 1=1";
        $params = [];

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
}
