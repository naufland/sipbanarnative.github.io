    <?php
    // File: includes/RealisasiPenunjukanLangsungModel.php

    class RealisasiPenunjukanLangsungModel
    {
        private $conn;
        private $table_name = "realisasi_penunjukanlangsung";

        public function __construct($db)
        {
            $this->conn = $db;
        }

        private function buildWhereClause($filters)
        {
            $whereClause = " WHERE 1=1";
            $params = [];

            // Filter berdasarkan tahun anggaran
            if (!empty($filters['tahun'])) {
                $whereClause .= " AND Tahun_Anggaran = :tahun";
                $params[':tahun'] = $filters['tahun'];
            }

            // Filter berdasarkan KLPD
            if (!empty($filters['klpd'])) {
                $whereClause .= " AND KLPD = :klpd";
                $params[':klpd'] = $filters['klpd'];
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

        /**
         * Helper function untuk mengkonversi nilai string ke float
         */
        private function convertToFloat($value)
        {
            if (is_null($value) || $value === '') {
                return 0.0;
            }
            
            // Jika sudah numeric, langsung return
            if (is_numeric($value)) {
                return floatval($value);
            }
            
            // Jika string, bersihkan formatnya
            if (is_string($value)) {
                // Hapus titik sebagai pemisah ribuan
                $value = str_replace('.', '', $value);
                // Ubah koma desimal jadi titik
                $value = str_replace(',', '.', $value);
            }
            
            return floatval($value);
        }

        public function getRealisasiPenunjukanLangsungData($filters = [], $limit = 50, $offset = 0)
        {
            list($whereClause, $params) = $this->buildWhereClause($filters);
            
            $sql = "SELECT Tahun_Anggaran, Kode_Paket, Nama_Paket, Kode_RUP, KLPD, 
                    Nama_Satker, Jenis_Pengadaan, Metode_pengadaan, Nilai_Pagu, 
                    Nilai_HPS, Nama_Pemenang, Nilai_Kontrak, Nilai_PDN, Nilai_UMK, 
                    Sumber_Dana, Status_Paket 
                    FROM " . $this->table_name . $whereClause . " 
                    ORDER BY Tahun_Anggaran DESC, Kode_Paket ASC 
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->conn->prepare($sql);
            
            // Bind semua parameter dari filter
            foreach ($params as $key => $value) { 
                $stmt->bindValue($key, $value); 
            }
            
            // Bind parameter limit dan offset
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Konversi semua nilai numerik ke float
            foreach ($results as &$row) {
                $row['Nilai_Pagu'] = $this->convertToFloat($row['Nilai_Pagu']);
                $row['Nilai_HPS'] = $this->convertToFloat($row['Nilai_HPS']);
                $row['Nilai_Kontrak'] = $this->convertToFloat($row['Nilai_Kontrak']);
                $row['Nilai_PDN'] = $this->convertToFloat($row['Nilai_PDN']);
                $row['Nilai_UMK'] = $this->convertToFloat($row['Nilai_UMK']);
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

        /**
         * Fungsi untuk mendapatkan summary data (Total Paket, Total Pagu, Total HPS, Total Kontrak, Total PDN, Total UMK)
         */
        public function getSummaryData($filters = [])
        {
            list($whereClause, $params) = $this->buildWhereClause($filters);
            
            $sql = "SELECT 
                        COUNT(*) as total_paket,
                        COALESCE(SUM(CAST(REPLACE(Nilai_Pagu, '.', '') AS DECIMAL(20,2))), 0) as total_pagu,
                        COALESCE(SUM(CAST(REPLACE(Nilai_HPS, '.', '') AS DECIMAL(20,2))), 0) as total_hps,
                        COALESCE(SUM(CAST(REPLACE(Nilai_Kontrak, '.', '') AS DECIMAL(20,2))), 0) as total_kontrak,
                        COALESCE(SUM(CAST(REPLACE(Nilai_PDN, '.', '') AS DECIMAL(20,2))), 0) as total_pdn,
                        COALESCE(SUM(CAST(REPLACE(Nilai_UMK, '.', '') AS DECIMAL(20,2))), 0) as total_umk
                    FROM " . $this->table_name . $whereClause;
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'total_paket' => (int)($result['total_paket'] ?? 0),
                'total_pagu' => $this->convertToFloat($result['total_pagu'] ?? 0),
                'total_hps' => $this->convertToFloat($result['total_hps'] ?? 0),
                'total_kontrak' => $this->convertToFloat($result['total_kontrak'] ?? 0),
                'total_pdn' => $this->convertToFloat($result['total_pdn'] ?? 0),
                'total_umk' => $this->convertToFloat($result['total_umk'] ?? 0)
            ];
        }

        public function getAllDataForSummary($filters = []) 
        {
            list($whereClause, $params) = $this->buildWhereClause($filters);
            $sql = "SELECT 
                        Jenis_Pengadaan,
                        KLPD, 
                        Status_Paket,
                        Nilai_Pagu, 
                        Nilai_HPS,
                        Nilai_Kontrak,
                        Nilai_PDN,
                        Nilai_UMK,
                        Nama_Pemenang
                    FROM " . $this->table_name . $whereClause;
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Konversi nilai ke float
            foreach ($results as &$row) {
                $row['Nilai_Pagu'] = $this->convertToFloat($row['Nilai_Pagu']);
                $row['Nilai_HPS'] = $this->convertToFloat($row['Nilai_HPS']);
                $row['Nilai_Kontrak'] = $this->convertToFloat($row['Nilai_Kontrak']);
                $row['Nilai_PDN'] = $this->convertToFloat($row['Nilai_PDN']);
                $row['Nilai_UMK'] = $this->convertToFloat($row['Nilai_UMK']);
            }
            
            return $results;
        }

        public function getDistinctValues($column)
        {
            $allowedColumns = ['Jenis_Pengadaan', 'KLPD', 'Status_Paket', 'Tahun_Anggaran'];
            if (!in_array($column, $allowedColumns)) return [];
            
            $sql = "SELECT DISTINCT $column FROM " . $this->table_name . " WHERE $column IS NOT NULL AND $column != '' ORDER BY $column ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
        
        /**
         * Fungsi untuk mengambil semua tahun unik dari kolom Tahun_Anggaran
         */
        public function getAvailableYears()
        {
            $sql = "SELECT DISTINCT Tahun_Anggaran as tahun 
                    FROM " . $this->table_name . " 
                    WHERE Tahun_Anggaran IS NOT NULL 
                    ORDER BY tahun DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
    }