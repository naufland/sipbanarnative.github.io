<?php
// FILE: models/RUPModel.php

class RekapitulasiModel {
    private $conn;
    
    public function __construct($database_connection) {
        $this->conn = $database_connection;
    }
    
    /**
     * Mengambil rekap data RUP berdasarkan metode pengadaan
     * @return array
     */
    public function getRekapRUP() {
        try {
            // Query untuk mengambil data dari tabel rup_keseluruhan dan rup_swakelola
            $sql = "
                SELECT 
                    'E-Purchasing' as Metode_Pengadaan,
                    COUNT(*) as Jumlah_Paket_RUP,
                    COALESCE(SUM(Pagu_Rp), 0) as Pagu
                FROM rup_keseluruhan 
                WHERE Metode = 'E-Purchasing'
                
                UNION ALL
                
                SELECT 
                    'Pengadaan Langsung' as Metode_Pengadaan,
                    COUNT(*) as Jumlah_Paket_RUP,
                    COALESCE(SUM(Pagu_Rp), 0) as Pagu
                FROM rup_keseluruhan 
                WHERE Metode = 'Pengadaan Langsung'
                
                UNION ALL
                
                SELECT 
                    'Penunjukan Langsung' as Metode_Pengadaan,
                    COUNT(*) as Jumlah_Paket_RUP,
                    COALESCE(SUM(Pagu_Rp), 0) as Pagu
                FROM rup_keseluruhan 
                WHERE Metode = 'Penunjukan Langsung'
                
                UNION ALL
                
                SELECT 
                    'Seleksi' as Metode_Pengadaan,
                    COUNT(*) as Jumlah_Paket_RUP,
                    COALESCE(SUM(Pagu_Rp), 0) as Pagu
                FROM rup_keseluruhan 
                WHERE Metode = 'Seleksi'
                
                UNION ALL
                
                SELECT 
                    'Tender' as Metode_Pengadaan,
                    COUNT(*) as Jumlah_Paket_RUP,
                    COALESCE(SUM(Pagu_Rp), 0) as Pagu
                FROM rup_keseluruhan 
                WHERE Metode = 'Tender'
                
                UNION ALL
                
                SELECT 
                    'Tender Cepat' as Metode_Pengadaan,
                    COUNT(*) as Jumlah_Paket_RUP,
                    COALESCE(SUM(Pagu_Rp), 0) as Pagu
                FROM rup_keseluruhan 
                WHERE Metode = 'Tender Cepat'
                
                UNION ALL
                
                SELECT 
                    'Dikecualikan' as Metode_Pengadaan,
                    COUNT(*) as Jumlah_Paket_RUP,
                    COALESCE(SUM(Pagu_Rp), 0) as Pagu
                FROM rup_keseluruhan 
                WHERE Metode = 'Dikecualikan'
                
                ORDER BY FIELD(Metode_Pengadaan, 
                    'E-Purchasing', 'Pengadaan Langsung', 'Penunjukan Langsung', 
                    'Seleksi', 'Tender', 'Tender Cepat', 'Dikecualikan'
                )
            ";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            throw new Exception("Error mengambil data rekap RUP: " . $e->getMessage());
        }
    }
    
    /**
     * Mengambil total data penyedia (dari rup_keseluruhan)
     * @return array
     */
    public function getPenyediaStats() {
        try {
            $sql = "
                SELECT 
                    COUNT(*) as paket,
                    COALESCE(SUM(Pagu_Rp), 0) as pagu
                FROM rup_keseluruhan
            ";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return [
                'paket' => (int)$result['paket'],
                'pagu' => (float)$result['pagu']
            ];
            
        } catch (Exception $e) {
            throw new Exception("Error mengambil data penyedia: " . $e->getMessage());
        }
    }
    
    /**
     * Mengambil total data swakelola
     * @return array
     */
    public function getSwakelola() {
        try {
            $sql = "
                SELECT 
                    COUNT(*) as paket,
                    COALESCE(SUM(Pagu_Rp), 0) as pagu
                FROM rup_swakelola
            ";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return [
                'paket' => (int)$result['paket'],
                'pagu' => (float)$result['pagu']
            ];
            
        } catch (Exception $e) {
            throw new Exception("Error mengambil data swakelola: " . $e->getMessage());
        }
    }
    
    /**
     * Mengambil rekap berdasarkan tipe swakelola
     * @return array
     */
    public function getRekapSwakelola() {
        try {
            $sql = "
                SELECT 
                    Tipe_Swakelola,
                    COUNT(*) as Jumlah_Paket_RUP,
                    COALESCE(SUM(Pagu_Rp), 0) as Pagu
                FROM rup_swakelola 
                GROUP BY Tipe_Swakelola
                ORDER BY Tipe_Swakelola
            ";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            throw new Exception("Error mengambil rekap swakelola: " . $e->getMessage());
        }
    }
    
    /**
     * Mengambil semua data lengkap untuk analisis
     * @return array
     */
    public function getFullRekapData() {
        try {
            // Ambil data metode pengadaan
            $metode_list = $this->getRekapRUP();
            
            // Ambil stats penyedia dan swakelola
            $penyedia_stats = $this->getPenyediaStats();
            $swakelola_stats = $this->getSwakelola();
            
            // Hitung grand total
            $grand_total = [
                'paket' => $penyedia_stats['paket'] + $swakelola_stats['paket'],
                'pagu' => $penyedia_stats['pagu'] + $swakelola_stats['pagu']
            ];
            
            return [
                'metode_list' => $metode_list,
                'penyedia_stats' => $penyedia_stats,
                'swakelola_stats' => $swakelola_stats,
                'grand_total' => $grand_total
            ];
            
        } catch (Exception $e) {
            throw new Exception("Error mengambil data lengkap: " . $e->getMessage());
        }
    }
    
    /**
     * Mengambil data untuk grafik berdasarkan jenis pengadaan
     * @return array
     */
    public function getChartData() {
        try {
            $data = $this->getFullRekapData();
            
            // Format data untuk chart
            $chart_data = [
                'penyedia_vs_swakelola' => [
                    'labels' => ['Penyedia', 'Swakelola'],
                    'data' => [
                        $data['penyedia_stats']['pagu'],
                        $data['swakelola_stats']['pagu']
                    ]
                ],
                'metode_paket' => [
                    'labels' => array_column($data['metode_list'], 'Metode_Pengadaan'),
                    'data' => array_column($data['metode_list'], 'Jumlah_Paket_RUP')
                ],
                'metode_pagu' => [
                    'labels' => array_column($data['metode_list'], 'Metode_Pengadaan'),
                    'data' => array_column($data['metode_list'], 'Pagu')
                ]
            ];
            
            return $chart_data;
            
        } catch (Exception $e) {
            throw new Exception("Error mengambil data chart: " . $e->getMessage());
        }
    }
}