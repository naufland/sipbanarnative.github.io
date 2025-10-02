<?php
class EpurchasingModel
{
    private $conn;
    private $table = 'realisasi_epurchasing';

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Get data paket dengan pagination dan filter
    public function getPaketData($filters = [], $limit = 50, $offset = 0)
    {
        $sql = "SELECT 
                no,                      -- TAMBAHKAN INI
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
                status_paket
                
            FROM {$this->table}
            WHERE 1=1";

        $params = [];

        if (!empty($filters['tahun_anggaran'])) {
            $sql .= " AND tahun_anggaran = :tahun_anggaran";
            $params[':tahun_anggaran'] = $filters['tahun_anggaran'];
        }

        if (!empty($filters['kode_anggaran'])) {
            $sql .= " AND kode_anggaran = :kode_anggaran";
            $params[':kode_anggaran'] = $filters['kode_anggaran'];
        }

        if (!empty($filters['kd_produk'])) {
            $sql .= " AND kd_produk = :kd_produk";
            $params[':kd_produk'] = $filters['kd_produk'];
        }

        if (!empty($filters['kd_penyedia'])) {
            $sql .= " AND kd_penyedia = :kd_penyedia";
            $params[':kd_penyedia'] = $filters['kd_penyedia'];
        }

        if (!empty($filters['status_paket'])) {
            $sql .= " AND status_paket = :status_paket";
            $params[':status_paket'] = $filters['status_paket'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (no_paket LIKE :search OR nama_paket LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $sql .= " ORDER BY no ASC LIMIT :limit OFFSET :offset";  // Sort by no ASC

        $stmt = $this->conn->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // Get total count
    public function getTotalCount($filters = [])
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE 1=1";
        $params = [];

        if (!empty($filters['tahun_anggaran'])) {
            $sql .= " AND tahun_anggaran = :tahun_anggaran";
            $params[':tahun_anggaran'] = $filters['tahun_anggaran'];
        }

        if (!empty($filters['kode_anggaran'])) {
            $sql .= " AND kode_anggaran = :kode_anggaran";
            $params[':kode_anggaran'] = $filters['kode_anggaran'];
        }

        if (!empty($filters['kd_produk'])) {
            $sql .= " AND kd_produk = :kd_produk";
            $params[':kd_produk'] = $filters['kd_produk'];
        }

        if (!empty($filters['kd_penyedia'])) {
            $sql .= " AND kd_penyedia = :kd_penyedia";
            $params[':kd_penyedia'] = $filters['kd_penyedia'];
        }

        if (!empty($filters['status_paket'])) {
            $sql .= " AND status_paket = :status_paket";
            $params[':status_paket'] = $filters['status_paket'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (no_paket LIKE :search OR nama_paket LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $stmt = $this->conn->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result['total'] ?? 0;
    }

    // Get summary data
    public function getSummaryData($filters = [])
    {
        $sql = "SELECT 
                    COUNT(*) as total_paket,
                    SUM((kuantitas * harga_satuan) + COALESCE(ongkos_kirim, 0)) as total_nilai,
                    SUM(kuantitas) as total_kuantitas
                FROM {$this->table}
                WHERE 1=1";

        $params = [];

        if (!empty($filters['tahun_anggaran'])) {
            $sql .= " AND tahun_anggaran = :tahun_anggaran";
            $params[':tahun_anggaran'] = $filters['tahun_anggaran'];
        }

        if (!empty($filters['kode_anggaran'])) {
            $sql .= " AND kode_anggaran = :kode_anggaran";
            $params[':kode_anggaran'] = $filters['kode_anggaran'];
        }

        if (!empty($filters['kd_produk'])) {
            $sql .= " AND kd_produk = :kd_produk";
            $params[':kd_produk'] = $filters['kd_produk'];
        }

        if (!empty($filters['kd_penyedia'])) {
            $sql .= " AND kd_penyedia = :kd_penyedia";
            $params[':kd_penyedia'] = $filters['kd_penyedia'];
        }

        if (!empty($filters['status_paket'])) {
            $sql .= " AND status_paket = :status_paket";
            $params[':status_paket'] = $filters['status_paket'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (no_paket LIKE :search OR nama_paket LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $stmt = $this->conn->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
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
        $sql = "SELECT * FROM {$this->table} WHERE 1=1";
        $params = [];

        if (!empty($filters['tahun_anggaran'])) {
            $sql .= " AND tahun_anggaran = :tahun_anggaran";
            $params[':tahun_anggaran'] = $filters['tahun_anggaran'];
        }

        if (!empty($filters['kode_anggaran'])) {
            $sql .= " AND kode_anggaran = :kode_anggaran";
            $params[':kode_anggaran'] = $filters['kode_anggaran'];
        }

        if (!empty($filters['kd_produk'])) {
            $sql .= " AND kd_produk = :kd_produk";
            $params[':kd_produk'] = $filters['kd_produk'];
        }

        if (!empty($filters['kd_penyedia'])) {
            $sql .= " AND kd_penyedia = :kd_penyedia";
            $params[':kd_penyedia'] = $filters['kd_penyedia'];
        }

        if (!empty($filters['status_paket'])) {
            $sql .= " AND status_paket = :status_paket";
            $params[':status_paket'] = $filters['status_paket'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (no_paket LIKE :search OR nama_paket LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $stmt = $this->conn->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();

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

    // Get distinct values
    public function getDistinctValues($column)
    {
        $sql = "SELECT DISTINCT {$column} FROM {$this->table} WHERE {$column} IS NOT NULL ORDER BY {$column}";
        $stmt = $this->conn->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return $results;
    }

    // Get available years
    public function getAvailableYears()
    {
        $sql = "SELECT DISTINCT tahun_anggaran FROM {$this->table} ORDER BY tahun_anggaran DESC";
        $stmt = $this->conn->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return $results;
    }
}
