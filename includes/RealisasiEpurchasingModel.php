<?php
// =================================================================
// == EpurchasingModel.php (MODEL) - FIXED FILTER BULAN & TAHUN ===
// =================================================================

class EpurchasingModel
{
    private $conn;
    private $table = 'realisasi_epurchasing';

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Helper: Convert kode bulan ke nama
    private function getBulanNama($bulanAngka) {
        $mapping = [
            '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', 
            '04' => 'April', '05' => 'Mei', '06' => 'Juni',
            '07' => 'Juli', '08' => 'Agustus', '09' => 'September', 
            '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
        ];
        return $mapping[$bulanAngka] ?? null;
    }

    // Build WHERE clause dengan filter bulan & tahun
    private function buildWhereClause($filters)
    {
        $whereClause = " WHERE 1=1";
        $params = [];

        // Filter bulan dan tahun - AMBIL DARI KOLOM bulan dan tahun DI TABEL
        if (!empty($filters['bulan']) && !empty($filters['tahun'])) {
            $bulanNama = $this->getBulanNama($filters['bulan']);
            if ($bulanNama) {
                $whereClause .= " AND bulan = :bulan AND tahun = :tahun";
                $params[':bulan'] = $bulanNama;
                $params[':tahun'] = (int)$filters['tahun'];
            }
        } elseif (!empty($filters['tahun'])) {
            // Jika hanya tahun yang diisi
            $whereClause .= " AND tahun = :tahun";
            $params[':tahun'] = (int)$filters['tahun'];
        } elseif (!empty($filters['bulan'])) {
            // Jika hanya bulan yang diisi
            $bulanNama = $this->getBulanNama($filters['bulan']);
            if ($bulanNama) {
                $whereClause .= " AND bulan = :bulan";
                $params[':bulan'] = $bulanNama;
            }
        }

        // Filter Tahun Anggaran (kolom terpisah)
        if (!empty($filters['tahun_anggaran'])) {
            $whereClause .= " AND tahun_anggaran = :tahun_anggaran";
            $params[':tahun_anggaran'] = $filters['tahun_anggaran'];
        }

        // Filter Kode Anggaran
        if (!empty($filters['kode_anggaran'])) {
            $whereClause .= " AND kode_anggaran = :kode_anggaran";
            $params[':kode_anggaran'] = $filters['kode_anggaran'];
        }

        // Filter Kode Produk
        if (!empty($filters['kd_produk'])) {
            $whereClause .= " AND kd_produk LIKE :kd_produk";
            $params[':kd_produk'] = "%" . $filters['kd_produk'] . "%";
        }

        // Filter Kode Penyedia
        if (!empty($filters['kd_penyedia'])) {
            $whereClause .= " AND kd_penyedia LIKE :kd_penyedia";
            $params[':kd_penyedia'] = "%" . $filters['kd_penyedia'] . "%";
        }

        // Filter Status Paket
        if (!empty($filters['status_paket'])) {
            $whereClause .= " AND status_paket = :status_paket";
            $params[':status_paket'] = $filters['status_paket'];
        }

        // Search: No Paket, Nama Paket, Kode Produk, Penyedia
        if (!empty($filters['search'])) {
            $whereClause .= " AND (no_paket LIKE :search OR nama_paket LIKE :search OR kd_produk LIKE :search OR kd_penyedia LIKE :search)";
            $params[':search'] = "%" . $filters['search'] . "%";
        }
        
        return [$whereClause, $params];
    }

    // Get data paket dengan pagination dan filter
    public function getPaketData($filters = [], $limit = 50, $offset = 0)
    {
        list($whereClause, $params) = $this->buildWhereClause($filters);
        
        $sql = "SELECT 
                    no,
                    kd_paket,
                    no_paket,
                    nama_paket,
                    tahun_anggaran,
                    kode_anggaran,
                    kd_produk,
                    kd_penyedia,
                    kuantitas,
                    harga_satuan,
                    ongkos_kirim,
                    total_harga,
                    tanggal_buat_paket,
                    tanggal_edit_paket,
                    status_paket,
                    tahun,
                    bulan
                FROM " . $this->table . $whereClause . " ORDER BY no ASC LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($sql);
        
        foreach ($params as $key => $value) { 
            $stmt->bindValue($key, $value); 
        }
        
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get total count
    public function getTotalCount($filters = [])
    {
        list($whereClause, $params) = $this->buildWhereClause($filters);
        
        $sql = "SELECT COUNT(no) as total FROM " . $this->table . $whereClause;
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'] ?? 0;
    }

    // Get summary data
    public function getSummaryData($filters = [])
    {
        list($whereClause, $params) = $this->buildWhereClause($filters);
        
        $sql = "SELECT 
                    COUNT(*) as total_paket,
                    SUM((kuantitas * harga_satuan) + COALESCE(ongkos_kirim, 0)) as total_nilai,
                    SUM(kuantitas) as total_kuantitas
                FROM " . $this->table . $whereClause;

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total_paket' => (int)($result['total_paket'] ?? 0),
            'total_nilai' => (float)($result['total_nilai'] ?? 0),
            'total_kuantitas' => (float)($result['total_kuantitas'] ?? 0)
        ];
    }

    // Get all data for summary breakdown
    public function getAllDataForSummary($filters = [])
    {
        list($whereClause, $params) = $this->buildWhereClause($filters);
        
        $sql = "SELECT 
                    tahun_anggaran,
                    kode_anggaran,
                    kd_produk,
                    kd_penyedia,
                    status_paket,
                    kuantitas,
                    harga_satuan,
                    ongkos_kirim
                FROM " . $this->table . $whereClause;
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get paket detail
    public function getPaketDetail($kd_paket)
    {
        $sql = "SELECT * FROM {$this->table} WHERE kd_paket = :kd_paket";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':kd_paket', $kd_paket);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Create paket
    public function createPaket($data)
    {
        // Hitung total
        $kuantitas = floatval($data['kuantitas']);
        $harga_satuan = floatval($data['harga_satuan']);
        $ongkos_kirim = isset($data['ongkos_kirim']) ? floatval($data['ongkos_kirim']) : 0;
        $total_harga = ($kuantitas * $harga_satuan) + $ongkos_kirim;

        $sql = "INSERT INTO {$this->table} (
                    no_paket, nama_paket, tahun_anggaran, kode_anggaran,
                    kd_produk, kd_penyedia, kuantitas, harga_satuan,
                    ongkos_kirim, total_harga, tanggal_buat_paket, status_paket
                ) VALUES (
                    :no_paket, :nama_paket, :tahun_anggaran, :kode_anggaran,
                    :kd_produk, :kd_penyedia, :kuantitas, :harga_satuan,
                    :ongkos_kirim, :total_harga, NOW(), :status_paket
                )";

        $stmt = $this->conn->prepare($sql);

        try {
            $stmt->execute([
                ':no_paket' => $data['no_paket'],
                ':nama_paket' => $data['nama_paket'],
                ':tahun_anggaran' => $data['tahun_anggaran'],
                ':kode_anggaran' => $data['kode_anggaran'] ?? null,
                ':kd_produk' => $data['kd_produk'],
                ':kd_penyedia' => $data['kd_penyedia'] ?? null,
                ':kuantitas' => $kuantitas,
                ':harga_satuan' => $harga_satuan,
                ':ongkos_kirim' => $ongkos_kirim,
                ':total_harga' => $total_harga,
                ':status_paket' => $data['status_paket'] ?? 'pending'
            ]);

            return [
                'success' => true,
                'message' => 'Paket berhasil ditambahkan',
                'kd_paket' => $this->conn->lastInsertId(),
                'total_keseluruhan' => $total_harga
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Gagal menambahkan paket: ' . $e->getMessage()
            ];
        }
    }

    // Update paket
    public function updatePaket($kd_paket, $data)
    {
        // Hitung ulang total
        $kuantitas = floatval($data['kuantitas']);
        $harga_satuan = floatval($data['harga_satuan']);
        $ongkos_kirim = isset($data['ongkos_kirim']) ? floatval($data['ongkos_kirim']) : 0;
        $total_harga = ($kuantitas * $harga_satuan) + $ongkos_kirim;

        $sql = "UPDATE {$this->table} SET
                    no_paket = :no_paket,
                    nama_paket = :nama_paket,
                    tahun_anggaran = :tahun_anggaran,
                    kode_anggaran = :kode_anggaran,
                    kd_produk = :kd_produk,
                    kd_penyedia = :kd_penyedia,
                    kuantitas = :kuantitas,
                    harga_satuan = :harga_satuan,
                    ongkos_kirim = :ongkos_kirim,
                    total_harga = :total_harga,
                    tanggal_edit_paket = NOW(),
                    status_paket = :status_paket
                WHERE kd_paket = :kd_paket";

        $stmt = $this->conn->prepare($sql);

        try {
            $stmt->execute([
                ':no_paket' => $data['no_paket'],
                ':nama_paket' => $data['nama_paket'],
                ':tahun_anggaran' => $data['tahun_anggaran'],
                ':kode_anggaran' => $data['kode_anggaran'] ?? null,
                ':kd_produk' => $data['kd_produk'],
                ':kd_penyedia' => $data['kd_penyedia'] ?? null,
                ':kuantitas' => $kuantitas,
                ':harga_satuan' => $harga_satuan,
                ':ongkos_kirim' => $ongkos_kirim,
                ':total_harga' => $total_harga,
                ':status_paket' => $data['status_paket'] ?? 'pending',
                ':kd_paket' => $kd_paket
            ]);

            return [
                'success' => true,
                'message' => 'Paket berhasil diupdate',
                'total_keseluruhan' => $total_harga
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Gagal mengupdate paket: ' . $e->getMessage()
            ];
        }
    }

    // Delete paket
    public function deletePaket($kd_paket)
    {
        $sql = "DELETE FROM {$this->table} WHERE kd_paket = :kd_paket";
        $stmt = $this->conn->prepare($sql);

        try {
            $stmt->execute([':kd_paket' => $kd_paket]);

            return [
                'success' => true,
                'message' => 'Paket berhasil dihapus'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Gagal menghapus paket: ' . $e->getMessage()
            ];
        }
    }

    // Get distinct values untuk dropdown
    public function getDistinctValues($column)
    {
        $allowedColumns = ['kode_anggaran', 'kd_produk', 'kd_penyedia', 'status_paket', 'tahun_anggaran'];
        if (!in_array($column, $allowedColumns)) return [];
        
        $sql = "SELECT DISTINCT $column FROM " . $this->table . " WHERE $column IS NOT NULL AND $column != '' ORDER BY $column ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Get available years - AMBIL DARI KOLOM tahun
    public function getAvailableYears()
    {
        $sql = "SELECT DISTINCT tahun FROM " . $this->table . " WHERE tahun IS NOT NULL ORDER BY tahun DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Get available months - AMBIL DARI KOLOM bulan (dengan filter tahun opsional)
    public function getAvailableMonths($tahun = null)
    {
        $sql = "SELECT DISTINCT bulan FROM " . $this->table . " WHERE bulan IS NOT NULL";
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

    // Get monthly summary untuk chart - DARI KOLOM bulan dan tahun
    public function getMonthlySummary($tahun)
    {
        $sql = "SELECT 
                    bulan, 
                    COUNT(no) as total_paket, 
                    SUM((kuantitas * harga_satuan) + COALESCE(ongkos_kirim, 0)) as total_nilai,
                    SUM(kuantitas) as total_kuantitas
                FROM " . $this->table . " 
                WHERE tahun = :tahun 
                GROUP BY bulan 
                ORDER BY FIELD(bulan, 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember')";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':tahun', (int)$tahun, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get summary by kategori (untuk breakdown di view)
    public function getSummaryByKodeAnggaran($filters = [])
    {
        list($whereClause, $params) = $this->buildWhereClause($filters);
        
        $sql = "SELECT 
                    kode_anggaran, 
                    COUNT(no) as total_paket, 
                    SUM((kuantitas * harga_satuan) + COALESCE(ongkos_kirim, 0)) as total_nilai,
                    SUM(kuantitas) as total_kuantitas
                FROM " . $this->table . $whereClause . " 
                GROUP BY kode_anggaran 
                ORDER BY total_nilai DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSummaryByProduk($filters = [])
    {
        list($whereClause, $params) = $this->buildWhereClause($filters);
        
        $sql = "SELECT 
                    kd_produk, 
                    COUNT(no) as total_paket, 
                    SUM((kuantitas * harga_satuan) + COALESCE(ongkos_kirim, 0)) as total_nilai,
                    SUM(kuantitas) as total_kuantitas
                FROM " . $this->table . $whereClause . " 
                GROUP BY kd_produk 
                ORDER BY total_nilai DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSummaryByPenyedia($filters = [])
    {
        list($whereClause, $params) = $this->buildWhereClause($filters);
        
        $sql = "SELECT 
                    kd_penyedia, 
                    COUNT(no) as total_paket, 
                    SUM((kuantitas * harga_satuan) + COALESCE(ongkos_kirim, 0)) as total_nilai,
                    SUM(kuantitas) as total_kuantitas
                FROM " . $this->table . $whereClause . " 
                GROUP BY kd_penyedia 
                ORDER BY total_nilai DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSummaryByStatus($filters = [])
    {
        list($whereClause, $params) = $this->buildWhereClause($filters);
        
        $sql = "SELECT 
                    status_paket, 
                    COUNT(no) as total_paket, 
                    SUM((kuantitas * harga_satuan) + COALESCE(ongkos_kirim, 0)) as total_nilai,
                    SUM(kuantitas) as total_kuantitas
                FROM " . $this->table . $whereClause . " 
                GROUP BY status_paket 
                ORDER BY total_nilai DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get data by month - DARI KOLOM bulan dan tahun
    public function readByMonth($month, $year)
    {
        $bulanNama = $this->getBulanNama($month);
        if (!$bulanNama) return false;
        
        $query = "SELECT * FROM " . $this->table . " WHERE bulan = :bulan AND tahun = :tahun";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':bulan', $bulanNama);
        $stmt->bindParam(':tahun', $year);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>