<?php
// =================================================================
// == API UNTUK LPPD KONTRAK KESELURUHAN ===========================
// =================================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/database.php';

class LPPDApi {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Ambil daftar Satuan Kerja untuk dropdown dari semua tabel
    public function getSatuanKerjaList($tahun = '') {
        $satuanKerja = [];
        
        // Daftar semua tabel yang memiliki data satker
        $tables = [
            'realisasi_dikecualikan',
            'realisasi_epurchasing',
            'realisasi_nontender',
            'realisasi_pengadaanlangsung',
            'realisasi_penunjukanlangsung',
            'realisasi_seleksi',
            'realisasi_swakelola',
            'realisasi_tender',
            'pencatatan_nontender'
        ];
        
        foreach ($tables as $table) {
            try {
                $query = "SELECT DISTINCT Nama_Satker 
                          FROM $table 
                          WHERE Nama_Satker IS NOT NULL AND Nama_Satker != ''";
                
                // Filter berdasarkan tahun jika ada
                if (!empty($tahun)) {
                    // Cek apakah tabel memiliki kolom Tahun_Anggaran
                    $checkColumn = "SHOW COLUMNS FROM $table LIKE 'Tahun_Anggaran'";
                    $colStmt = $this->conn->prepare($checkColumn);
                    $colStmt->execute();
                    
                    if ($colStmt->rowCount() > 0) {
                        $query .= " AND Tahun_Anggaran = :tahun";
                    }
                }
                
                $query .= " ORDER BY Nama_Satker ASC";
                
                $stmt = $this->conn->prepare($query);
                
                if (!empty($tahun) && strpos($query, ':tahun') !== false) {
                    $stmt->bindValue(':tahun', $tahun);
                }
                
                $stmt->execute();
                
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    // Gunakan array key untuk menghindari duplikat
                    $satuanKerja[$row['Nama_Satker']] = $row['Nama_Satker'];
                }
            } catch (PDOException $e) {
                // Skip jika tabel tidak ada atau error
                continue;
            }
        }
        
        // Sort dan return sebagai array biasa
        ksort($satuanKerja);
        return array_values($satuanKerja);
    }
    
    // Ambil data kontrak dari semua tabel realisasi
    public function getDataKontrak($satker = '', $tahun = '') {
        $data = [];
        
        // Daftar tabel realisasi dan pencatatan
        $tables = [
            'realisasi_dikecualikan',
            'realisasi_epurchasing',
            'realisasi_nontender',
            'realisasi_penunjukanlangsung',
            'realisasi_seleksi',
            'realisasi_swakelola',
            'realisasi_tender',
            'pencatatan_nontender'
        ];
        
        foreach ($tables as $table) {
            $query = "SELECT 
                        Kode_RUP,
                        Nama_Paket,
                        Nilai_Kontrak,
                        Nama_Satker,
                        Nilai_HPS,
                        '$table' as sumber_tabel
                      FROM $table
                      WHERE 1=1";
            
            $params = [];
            
            // Filter berdasarkan Satuan Kerja
            if (!empty($satker)) {
                $query .= " AND Nama_Satker = :satker";
                $params[':satker'] = $satker;
            }
            
            // Filter berdasarkan Tahun
            if (!empty($tahun)) {
                // Cek apakah tabel memiliki kolom Tahun_Anggaran
                $checkColumn = "SHOW COLUMNS FROM $table LIKE 'Tahun_Anggaran'";
                $colStmt = $this->conn->prepare($checkColumn);
                $colStmt->execute();
                
                if ($colStmt->rowCount() > 0) {
                    $query .= " AND Tahun_Anggaran = :tahun";
                    $params[':tahun'] = $tahun;
                }
            }
            
            $query .= " ORDER BY Nama_Paket ASC";
            
            try {
                $stmt = $this->conn->prepare($query);
                
                // Bind parameters
                foreach ($params as $key => $value) {
                    $stmt->bindValue($key, $value);
                }
                
                $stmt->execute();
                
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $data[] = $row;
                }
            } catch (PDOException $e) {
                // Skip jika tabel bermasalah
                continue;
            }
        }
        
        return $data;
    }
    
    // Hitung statistik
    public function getStatistik($satker = '', $tahun = '') {
        $data = $this->getDataKontrak($satker, $tahun);
        
        $totalPaket = count($data);
        $totalNilaiKontrak = 0;
        $totalNilaiHPS = 0;
        
        foreach ($data as $row) {
            // Pastikan nilai tidak null sebelum preg_replace
            $nilaiKontrakStr = $row['Nilai_Kontrak'] ?? '0';
            $nilaiHPSStr = $row['Nilai_HPS'] ?? '0';
            
            $nilaiKontrak = (float) preg_replace('/[^\d.]/', '', (string)$nilaiKontrakStr);
            $nilaiHPS = (float) preg_replace('/[^\d.]/', '', (string)$nilaiHPSStr);
            
            $totalNilaiKontrak += $nilaiKontrak;
            $totalNilaiHPS += $nilaiHPS;
        }
        
        return [
            'total_paket' => $totalPaket,
            'total_nilai_kontrak' => $totalNilaiKontrak,
            'total_nilai_hps' => $totalNilaiHPS
        ];
    }
}

// Inisialisasi database
$database = new Database();
$db = $database->getConnection();

$api = new LPPDApi($db);

// Handle request
$action = $_GET['action'] ?? 'getData';

try {
    switch ($action) {
        case 'options':
            // Ambil daftar Satuan Kerja
            $tahun = $_GET['tahun'] ?? '';
            $satuanKerja = $api->getSatuanKerjaList($tahun);
            
            echo json_encode([
                'success' => true,
                'options' => [
                    'satuan_kerja' => $satuanKerja
                ]
            ]);
            break;
            
        case 'getData':
            // Ambil data kontrak
            $satker = $_GET['satuan_kerja'] ?? '';
            $tahun = $_GET['tahun'] ?? '';
            
            $data = $api->getDataKontrak($satker, $tahun);
            $statistik = $api->getStatistik($satker, $tahun);
            
            echo json_encode([
                'success' => true,
                'data' => $data,
                'statistik' => $statistik,
                'filter' => [
                    'satuan_kerja' => $satker,
                    'tahun' => $tahun
                ]
            ]);
            break;
            
        case 'summary':
            // Ambil statistik saja
            $satker = $_GET['satuan_kerja'] ?? '';
            $tahun = $_GET['tahun'] ?? '';
            
            $statistik = $api->getStatistik($satker, $tahun);
            
            echo json_encode([
                'success' => true,
                'summary' => $statistik
            ]);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Action tidak valid'
            ]);
            break;
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>