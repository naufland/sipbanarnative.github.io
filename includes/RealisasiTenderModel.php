<?php
// =================================================================
// == RealisasiTenderModel.php (MODEL) - FINAL FIX PHP CALCULATION =
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

        // Filter bulan dan tahun
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

        // Filter Lainnya
        if (!empty($filters['tahun_anggaran'])) {
            $whereClause .= " AND Tahun_Anggaran = :tahun_anggaran";
            $params[':tahun_anggaran'] = (int)$filters['tahun_anggaran'];
        }
        if (!empty($filters['jenis_pengadaan'])) {
            $whereClause .= " AND Jenis_Pengadaan = :jenis_pengadaan";
            $params[':jenis_pengadaan'] = $filters['jenis_pengadaan'];
        }
        if (!empty($filters['nama_satker'])) {
            $whereClause .= " AND Nama_Satker = :nama_satker";
            $params[':nama_satker'] = $filters['nama_satker'];
        }
        // Filter KLPD (untuk backward compatibility)
        if (!empty($filters['klpd'])) {
            $whereClause .= " AND KLPD = :klpd";
            $params[':klpd'] = $filters['klpd'];
        }
        if (!empty($filters['metode_pengadaan'])) {
            $whereClause .= " AND Metode_Pengadaan = :metode_pengadaan";
            $params[':metode_pengadaan'] = $filters['metode_pengadaan'];
        }
        if (!empty($filters['sumber_dana'])) {
            $whereClause .= " AND Sumber_Dana = :sumber_dana";
            $params[':sumber_dana'] = $filters['sumber_dana'];
        }
        if (!empty($filters['jenis_kontrak'])) {
            $whereClause .= " AND Jenis_Kontrak = :jenis_kontrak";
            $params[':jenis_kontrak'] = $filters['jenis_kontrak'];
        }
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
    
    // =========================================================================
    // == PERBAIKAN UTAMA: HITUNG MANUAL DI PHP (BUKAN SQL SUM) ================
    // =========================================================================
    public function getSummaryWithFilters($filters = [])
    {
        // 1. Ambil data mentah (raw data) sesuai filter
        $allData = $this->getAllDataForSummary($filters);
        
        // 2. Inisialisasi variabel
        $totalPaket = 0;
        $totalPagu = 0;
        $totalHPS = 0;
        $totalKontrak = 0;
        $totalPDN = 0;
        $totalUMK = 0;

        // 3. Loop PHP untuk menjumlahkan (Lebih aman dari kesalahan tipe data SQL)
        foreach ($allData as $row) {
            $totalPaket++;

            // Bersihkan format angka (jika ada titik/koma) dan cast ke float
            // Fungsi cleanNumber untuk memastikan '1.000.000' dibaca 1 juta, bukan 1
            $pagu = $this->cleanNumber($row['Nilai_Pagu'] ?? 0);
            $hps = $this->cleanNumber($row['Nilai_HPS'] ?? 0);
            $kontrak = $this->cleanNumber($row['Nilai_Kontrak'] ?? 0);
            $pdn = $this->cleanNumber($row['Nilai_PDN'] ?? 0);
            $umk = $this->cleanNumber($row['Nilai_UMK'] ?? 0);

            $totalPagu += $pagu;
            $totalHPS += $hps;
            $totalKontrak += $kontrak;
            $totalPDN += $pdn;
            $totalUMK += $umk;
        }
        
        return [
            'total_paket' => $totalPaket,
            'total_pagu' => $totalPagu,
            'total_hps' => $totalHPS,
            'total_kontrak' => $totalKontrak,
            'total_pdn' => $totalPDN,
            'total_umk' => $totalUMK
        ];
    }

    // Helper untuk membersihkan angka
    private function cleanNumber($value) {
        if (is_numeric($value)) {
            return (float)$value;
        }
        // Jika string mengandung titik sebagai ribuan (format Indonesia), hilangkan titik
        // Contoh: "1.000.000" jadi "1000000"
        // Hati-hati: pastikan database Anda tidak menggunakan titik sebagai desimal
        $clean = str_replace('.', '', $value); 
        $clean = str_replace(',', '.', $clean); // Ubah koma jadi titik desimal jika ada
        return (float)$clean;
    }

    public function getAllDataForSummary($filters = []) {
        list($whereClause, $params) = $this->buildWhereClause($filters);
        // Pastikan kolom yang diambil lengkap
        $sql = "SELECT Jenis_Pengadaan, KLPD, Nama_Satker, Metode_Pengadaan, Sumber_Dana, Jenis_Kontrak, Nilai_Pagu, Nilai_HPS, Nilai_Kontrak, Nilai_PDN, Nilai_UMK FROM " . $this->table_name . $whereClause;
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEfficiencyStats($filters = [])
    {
        // Menggunakan perhitungan PHP yang baru
        $summary = $this->getSummaryWithFilters($filters);
        
        $efisiensi = 0;
        // Mencegah pembagian dengan nol
        if ($summary['total_pagu'] > 0) {
            $efisiensi = (($summary['total_pagu'] - $summary['total_kontrak']) / $summary['total_pagu']) * 100;
        }
        
        return ['efisiensi_persen' => round($efisiensi, 2)] + $summary;
    }

    // --- Fungsi Dropdown (Tetap Sama) ---
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

    // --- Fungsi Breakdown untuk Grafik (Tetap Sama, menggunakan data dari getAllDataForSummary) ---
    public function getSummaryByJenisPengadaan($filters = [])
    {
        // Kita gunakan logika PHP juga untuk konsistensi
        $data = $this->getAllDataForSummary($filters);
        $stats = [];
        
        foreach ($data as $row) {
            $key = $row['Jenis_Pengadaan'] ?? 'Lainnya';
            if (!isset($stats[$key])) $stats[$key] = ['total_paket' => 0, 'total_pagu' => 0, 'total_kontrak' => 0];
            
            $stats[$key]['total_paket']++;
            $stats[$key]['total_pagu'] += $this->cleanNumber($row['Nilai_Pagu']);
            $stats[$key]['total_kontrak'] += $this->cleanNumber($row['Nilai_Kontrak']);
        }
        
        // Ubah format array agar sesuai dengan ekspektasi frontend/chart (opsional)
        // Disini kita kembalikan array of values untuk diolah controller jika perlu
        // Tapi controller Anda pakai array key-value, jadi ini cukup.
        return $stats; 
    }
    
    public function getSummaryBySatker($filters = [])
    {
        $data = $this->getAllDataForSummary($filters);
        $stats = [];
        
        foreach ($data as $row) {
            $key = $row['Nama_Satker'] ?? 'Lainnya';
            if (!isset($stats[$key])) $stats[$key] = ['total_paket' => 0, 'total_pagu' => 0, 'total_kontrak' => 0];
            
            $stats[$key]['total_paket']++;
            $stats[$key]['total_pagu'] += $this->cleanNumber($row['Nilai_Pagu']);
            $stats[$key]['total_kontrak'] += $this->cleanNumber($row['Nilai_Kontrak']);
        }
        return $stats;
    }
}
?>