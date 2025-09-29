<?php
/**
 * Pengadaan Model
 * models/PengadaanModel.php
 */



class RealisasiEpurchasingModel {
    private $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Get paginated data with filters
     * @param array $filters
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getPaginatedData($filters = [], $page = 1, $limit = 20) {
        try {
            $offset = ($page - 1) * $limit;
            
            // Build WHERE clause
            $whereClause = $this->buildWhereClause($filters);
            $params = $this->buildParams($filters);
            
            // Count total records
            $countSql = "SELECT COUNT(*) as total FROM realisasi_epurchasing" . $whereClause;
            $countStmt = $this->pdo->prepare($countSql);
            $countStmt->execute($params);
            $totalRecords = $countStmt->fetch()['total'];
            
            // Get data
            $dataSql = "SELECT * FROM realisasi_epurchasing" . $whereClause . " ORDER BY id DESC LIMIT :limit OFFSET :offset";
            $dataStmt = $this->pdo->prepare($dataSql);
            
            // Bind filter params
            foreach ($params as $key => $value) {
                $dataStmt->bindValue($key, $value);
            }
            // Bind pagination params
            $dataStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $dataStmt->execute();
            $data = $dataStmt->fetchAll();
            
            $totalPages = ceil($totalRecords / $limit);
            
            return [
                'data' => $data,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_records' => $totalRecords,
                    'limit' => $limit,
                    'has_next' => $page < $totalPages,
                    'has_prev' => $page > 1
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Error in getPaginatedData: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get summary statistics
     * @param array $filters
     * @return array
     */
    public function getSummary($filters = []) {
        try {
            $whereClause = $this->buildWhereClause($filters);
            $params = $this->buildParams($filters);
            
            $sql = "SELECT 
                        COUNT(*) as total_pengadaan,
                        SUM(nilai_pengadaan) as total_nilai,
                        AVG(nilai_pengadaan) as rata_rata_nilai
                    FROM realisasi_epurchasing" . $whereClause;
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetch();
            
        } catch (Exception $e) {
            error_log("Error in getSummary: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get filter options
     * @return array
     */
    public function getFilterOptions() {
        try {
            $options = [];
            
            // Get unique years
            $yearStmt = $this->pdo->query("SELECT DISTINCT YEAR(tanggal_pengadaan) as tahun FROM realisasi_epurchasing ORDER BY tahun DESC");
            $options['tahun'] = $yearStmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Get unique instansi
            $instansiStmt = $this->pdo->query("SELECT DISTINCT instansi FROM realisasi_epurchasing WHERE instansi IS NOT NULL ORDER BY instansi");
            $options['instansi'] = $instansiStmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Get unique status
            $statusStmt = $this->pdo->query("SELECT DISTINCT status FROM realisasi_epurchasing WHERE status IS NOT NULL ORDER BY status");
            $options['status'] = $statusStmt->fetchAll(PDO::FETCH_COLUMN);
            
            return $options;
            
        } catch (Exception $e) {
            error_log("Error in getFilterOptions: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Search data
     * @param string $keyword
     * @param int $limit
     * @return array
     */
    public function search($keyword, $limit = 20) {
        try {
            $sql = "SELECT * FROM realisasi_epurchasing 
                    WHERE nama_pengadaan LIKE :keyword 
                    OR instansi LIKE :keyword 
                    OR deskripsi LIKE :keyword
                    ORDER BY id DESC 
                    LIMIT :limit";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':keyword', '%' . $keyword . '%');
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Error in search: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get data by ID
     * @param int $id
     * @return array|false
     */
    public function getById($id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM realisasi_epurchasing WHERE id = :id");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch();
            
        } catch (Exception $e) {
            error_log("Error in getById: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get export data
     * @param array $filters
     * @return array
     */
    public function getExportData($filters = []) {
        try {
            $whereClause = $this->buildWhereClause($filters);
            $params = $this->buildParams($filters);
            
            $sql = "SELECT * FROM realisasi_epurchasing" . $whereClause . " ORDER BY id DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Error in getExportData: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get time statistics
     * @param string $period
     * @param array $filters
     * @return array
     */
    public function getTimeStatistics($period, $filters = []) {
        try {
            $whereClause = $this->buildWhereClause($filters);
            $params = $this->buildParams($filters);
            
            $dateFormat = $period === 'month' ? '%Y-%m' : '%Y';
            
            $sql = "SELECT 
                        DATE_FORMAT(tanggal_pengadaan, '$dateFormat') as periode,
                        COUNT(*) as jumlah,
                        SUM(nilai_pengadaan) as total_nilai
                    FROM realisasi_epurchasing" . $whereClause . "
                    GROUP BY periode
                    ORDER BY periode DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Error in getTimeStatistics: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get top packages
     * @param int $limit
     * @param array $filters
     * @return array
     */
    public function getTopPackages($limit, $filters = []) {
        try {
            $whereClause = $this->buildWhereClause($filters);
            $params = $this->buildParams($filters);
            
            $sql = "SELECT nama_pengadaan, nilai_pengadaan, instansi
                    FROM realisasi_epurchasing" . $whereClause . "
                    ORDER BY nilai_pengadaan DESC
                    LIMIT :limit";
            
            $stmt = $this->pdo->prepare($sql);
            
            // Bind filter params
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Error in getTopPackages: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Build WHERE clause from filters
     * @param array $filters
     * @return string
     */
    private function buildWhereClause($filters) {
        $conditions = [];
        
        if (!empty($filters['tahun'])) {
            $conditions[] = "YEAR(tanggal_pengadaan) = :tahun";
        }
        
        if (!empty($filters['instansi'])) {
            $conditions[] = "instansi = :instansi";
        }
        
        if (!empty($filters['status'])) {
            $conditions[] = "status = :status";
        }
        
        return empty($conditions) ? '' : ' WHERE ' . implode(' AND ', $conditions);
    }
    
    /**
     * Build parameters from filters
     * @param array $filters
     * @return array
     */
    private function buildParams($filters) {
        $params = [];
        
        if (!empty($filters['tahun'])) {
            $params[':tahun'] = $filters['tahun'];
        }
        
        if (!empty($filters['instansi'])) {
            $params[':instansi'] = $filters['instansi'];
        }
        
        if (!empty($filters['status'])) {
            $params[':status'] = $filters['status'];
        }
        
        return $params;
    }
}
?>