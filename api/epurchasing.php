<?php
/**
 * API E-Purchasing untuk SIP BANAR - Fixed Version
 * Endpoint: /api/epurchasing.php
 * Menangani berbagai struktur tabel yang mungkin ada
 */

// Headers untuk CORS dan JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include dependencies dengan error handling
try {
    if (file_exists(__DIR__ . '/../config/database.php')) {
        require_once __DIR__ . '/../config/database.php';
    } else {
        throw new Exception('Database config file not found');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Configuration error: ' . $e->getMessage(),
        'error_code' => 500,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

// Helper classes
if (!class_exists('ApiLogger')) {
    class ApiLogger {
        public static function info($message, $data = []) {
            error_log("INFO: $message " . json_encode($data));
        }
        public static function error($message, $data = []) {
            error_log("ERROR: $message " . json_encode($data));
        }
    }
}

if (!class_exists('ApiResponse')) {
    class ApiResponse {
        public static function success($data, $message = 'Success') {
            return json_encode([
                'status' => 'success',
                'message' => $message,
                'data' => $data,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        }
        
        public static function error($message, $code = 400, $debug = null) {
            $response = [
                'status' => 'error',
                'message' => $message,
                'error_code' => $code,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            if ($debug) $response['debug'] = $debug;
            return json_encode($response);
        }
        
        public static function paginated($data, $pagination, $message = 'Success') {
            return json_encode([
                'status' => 'success',
                'message' => $message,
                'data' => $data,
                'pagination' => $pagination,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        }
    }
}

// Smart Table Detective - mencari tabel dan struktur yang tepat
class SmartTableDetective {
    private $pdo;
    private $tableInfo = null;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->detectTable();
    }
    
    private function detectTable() {
        try {
            // Cek tabel yang mungkin ada
            $possibleTables = [
                'pengadaan', 'epurchasing', 'realisasi_epurchasing', 
                'procurement', 'tender', 'paket'
            ];
            
            foreach ($possibleTables as $table) {
                if ($this->tableExists($table)) {
                    $columns = $this->getTableColumns($table);
                    $count = $this->getTableCount($table);
                    
                    $this->tableInfo = [
                        'name' => $table,
                        'columns' => $columns,
                        'count' => $count
                    ];
                    break;
                }
            }
        } catch (Exception $e) {
            ApiLogger::error("Table detection error: " . $e->getMessage());
        }
    }
    
    private function tableExists($tableName) {
        try {
            $stmt = $this->pdo->prepare("SELECT 1 FROM $tableName LIMIT 1");
            $stmt->execute();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function getTableColumns($tableName) {
        try {
            $stmt = $this->pdo->query("DESCRIBE $tableName");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            return [];
        }
    }
    
    private function getTableCount($tableName) {
        try {
            $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM $tableName");
            $result = $stmt->fetch();
            return $result['total'] ?? 0;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    public function getTableInfo() {
        return $this->tableInfo;
    }
    
    // Smart column mapping
    public function getColumnMap() {
        if (!$this->tableInfo) return [];
        
        $columns = $this->tableInfo['columns'];
        $map = [];
        
        // Map ID column
        if (in_array('id', $columns)) {
            $map['id'] = 'id';
        } elseif (in_array('no_paket', $columns)) {
            $map['id'] = 'no_paket';
        } elseif (in_array('kode_tender', $columns)) {
            $map['id'] = 'kode_tender';
        } else {
            $map['id'] = $columns[0] ?? 'id'; // fallback ke kolom pertama
        }
        
        // Map nama paket
        if (in_array('nama_paket', $columns)) {
            $map['nama_paket'] = 'nama_paket';
        } elseif (in_array('nama_pengadaan', $columns)) {
            $map['nama_paket'] = 'nama_pengadaan';
        } elseif (in_array('judul_paket', $columns)) {
            $map['nama_paket'] = 'judul_paket';
        }
        
        // Map nilai/harga
        if (in_array('total_harga', $columns)) {
            $map['nilai'] = 'total_harga';
        } elseif (in_array('nilai_pengadaan', $columns)) {
            $map['nilai'] = 'nilai_pengadaan';
        } elseif (in_array('pagu', $columns)) {
            $map['nilai'] = 'pagu';
        } elseif (in_array('hps', $columns)) {
            $map['nilai'] = 'hps';
        }
        
        // Map tahun
        if (in_array('tahun_anggaran', $columns)) {
            $map['tahun'] = 'tahun_anggaran';
        } elseif (in_array('tahun', $columns)) {
            $map['tahun'] = 'tahun';
        }
        
        // Map instansi/satker
        if (in_array('nama_satker', $columns)) {
            $map['instansi'] = 'nama_satker';
        } elseif (in_array('instansi', $columns)) {
            $map['instansi'] = 'instansi';
        } elseif (in_array('klpd', $columns)) {
            $map['instansi'] = 'klpd';
        }
        
        // Map tanggal
        if (in_array('tanggal_buat', $columns)) {
            $map['tanggal'] = 'tanggal_buat';
        } elseif (in_array('tanggal_pengadaan', $columns)) {
            $map['tanggal'] = 'tanggal_pengadaan';
        } elseif (in_array('created_at', $columns)) {
            $map['tanggal'] = 'created_at';
        }
        
        // Map status
        if (in_array('status_paket', $columns)) {
            $map['status'] = 'status_paket';
        } elseif (in_array('status', $columns)) {
            $map['status'] = 'status';
        }
        
        return $map;
    }
}

// Enhanced PengadaanModel dengan deteksi tabel otomatis
class SmartPengadaanModel {
    private $pdo;
    private $detective;
    private $tableName;
    private $columnMap;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->detective = new SmartTableDetective($pdo);
        
        $tableInfo = $this->detective->getTableInfo();
        $this->tableName = $tableInfo['name'] ?? 'pengadaan';
        $this->columnMap = $this->detective->getColumnMap();
    }
    
    public function getTableInfo() {
        return $this->detective->getTableInfo();
    }
    
    public function getPaginatedData($filters = [], $page = 1, $limit = 20) {
        try {
            if (!$this->detective->getTableInfo()) {
                return [
                    'data' => [],
                    'pagination' => [
                        'current_page' => 1,
                        'total_pages' => 0,
                        'total_records' => 0,
                        'limit' => $limit,
                        'has_next' => false,
                        'has_prev' => false
                    ]
                ];
            }
            
            $offset = ($page - 1) * $limit;
            
            // Build WHERE clause dengan column mapping
            $whereConditions = ['1=1'];
            $params = [];
            
            if (!empty($filters['tahun_anggaran']) && isset($this->columnMap['tahun'])) {
                $whereConditions[] = $this->columnMap['tahun'] . " = :tahun";
                $params[':tahun'] = $filters['tahun_anggaran'];
            }
            
            if (!empty($filters['search'])) {
                $searchFields = [];
                if (isset($this->columnMap['nama_paket'])) {
                    $searchFields[] = $this->columnMap['nama_paket'] . " LIKE :search";
                }
                if (isset($this->columnMap['instansi'])) {
                    $searchFields[] = $this->columnMap['instansi'] . " LIKE :search";
                }
                if (!empty($searchFields)) {
                    $whereConditions[] = "(" . implode(" OR ", $searchFields) . ")";
                    $params[':search'] = '%' . $filters['search'] . '%';
                }
            }
            
            $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
            
            // Count total records
            $countSql = "SELECT COUNT(*) as total FROM {$this->tableName} $whereClause";
            $countStmt = $this->pdo->prepare($countSql);
            $countStmt->execute($params);
            $totalRecords = $countStmt->fetch()['total'];
            
            // Get data dengan ORDER BY yang aman
            $orderBy = 'ORDER BY ';
            if (isset($this->columnMap['tanggal'])) {
                $orderBy .= $this->columnMap['tanggal'] . ' DESC';
            } elseif (isset($this->columnMap['id'])) {
                $orderBy .= $this->columnMap['id'] . ' DESC';
            } else {
                // Fallback ke kolom pertama
                $tableInfo = $this->detective->getTableInfo();
                $firstColumn = $tableInfo['columns'][0] ?? 'id';
                $orderBy .= "$firstColumn DESC";
            }
            
            $dataSql = "SELECT * FROM {$this->tableName} $whereClause $orderBy LIMIT :limit OFFSET :offset";
            
            $dataStmt = $this->pdo->prepare($dataSql);
            
            foreach ($params as $key => $value) {
                $dataStmt->bindValue($key, $value);
            }
            $dataStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $dataStmt->execute();
            $data = $dataStmt->fetchAll();
            
            // Format data
            $formattedData = [];
            foreach ($data as $row) {
                $formatted = $this->formatRow($row);
                $formattedData[] = $formatted;
            }
            
            $totalPages = ceil($totalRecords / $limit);
            
            return [
                'data' => $formattedData,
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
            ApiLogger::error("Error in getPaginatedData: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function getSummary($filters = []) {
        try {
            if (!$this->detective->getTableInfo()) {
                return [
                    'total_paket' => 0,
                    'total_pagu' => 0,
                    'avg_pagu' => 0,
                    'total_klpd' => 0
                ];
            }
            
            $whereConditions = ['1=1'];
            $params = [];
            
            if (!empty($filters['tahun_anggaran']) && isset($this->columnMap['tahun'])) {
                $whereConditions[] = $this->columnMap['tahun'] . " = :tahun";
                $params[':tahun'] = $filters['tahun_anggaran'];
            }
            
            if (!empty($filters['search'])) {
                $searchFields = [];
                if (isset($this->columnMap['nama_paket'])) {
                    $searchFields[] = $this->columnMap['nama_paket'] . " LIKE :search";
                }
                if (isset($this->columnMap['instansi'])) {
                    $searchFields[] = $this->columnMap['instansi'] . " LIKE :search";
                }
                if (!empty($searchFields)) {
                    $whereConditions[] = "(" . implode(" OR ", $searchFields) . ")";
                    $params[':search'] = '%' . $filters['search'] . '%';
                }
            }
            
            $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
            
            $nilaiColumn = $this->columnMap['nilai'] ?? 'nilai';
            $instansiColumn = $this->columnMap['instansi'] ?? 'instansi';
            
            $sql = "SELECT 
                        COUNT(*) as total_paket,
                        COALESCE(SUM($nilaiColumn), 0) as total_pagu,
                        COALESCE(AVG($nilaiColumn), 0) as avg_pagu,
                        COUNT(DISTINCT $instansiColumn) as total_klpd
                    FROM {$this->tableName} $whereClause";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetch();
            
        } catch (Exception $e) {
            ApiLogger::error("Error in getSummary: " . $e->getMessage());
            return [
                'total_paket' => 0,
                'total_pagu' => 0,
                'avg_pagu' => 0,
                'total_klpd' => 0
            ];
        }
    }
    
    public function search($keyword, $limit = 20) {
        try {
            if (!$this->detective->getTableInfo()) {
                return [];
            }
            
            $searchFields = [];
            $params = [':keyword' => '%' . $keyword . '%'];
            
            if (isset($this->columnMap['nama_paket'])) {
                $searchFields[] = $this->columnMap['nama_paket'] . " LIKE :keyword";
            }
            if (isset($this->columnMap['instansi'])) {
                $searchFields[] = $this->columnMap['instansi'] . " LIKE :keyword";
            }
            
            if (empty($searchFields)) {
                return [];
            }
            
            $whereClause = '(' . implode(' OR ', $searchFields) . ')';
            
            // Order by yang aman
            $orderBy = 'ORDER BY ';
            if (isset($this->columnMap['tanggal'])) {
                $orderBy .= $this->columnMap['tanggal'] . ' DESC';
            } elseif (isset($this->columnMap['id'])) {
                $orderBy .= $this->columnMap['id'] . ' DESC';
            } else {
                $tableInfo = $this->detective->getTableInfo();
                $firstColumn = $tableInfo['columns'][0] ?? 'id';
                $orderBy .= "$firstColumn DESC";
            }
            
            $sql = "SELECT * FROM {$this->tableName} WHERE $whereClause $orderBy LIMIT :limit";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':keyword', '%' . $keyword . '%');
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $data = $stmt->fetchAll();
            
            $formattedData = [];
            foreach ($data as $row) {
                $formattedData[] = $this->formatRow($row);
            }
            
            return $formattedData;
            
        } catch (Exception $e) {
            ApiLogger::error("Error in search: " . $e->getMessage());
            return [];
        }
    }
    
    private function formatRow($row) {
        $formatted = $row;
        
        // Format nilai jika ada
        if (isset($this->columnMap['nilai']) && isset($row[$this->columnMap['nilai']])) {
            $nilai = $row[$this->columnMap['nilai']];
            $formatted['formatted_total_harga'] = 'Rp ' . number_format($nilai, 0, ',', '.');
        }
        
        // Format tanggal jika ada
        if (isset($this->columnMap['tanggal']) && isset($row[$this->columnMap['tanggal']])) {
            $tanggal = $row[$this->columnMap['tanggal']];
            if ($tanggal) {
                $formatted['formatted_tanggal_buat'] = date('d/m/Y', strtotime($tanggal));
            }
        }
        
        // Standardisasi nama field
        if (isset($this->columnMap['nama_paket']) && isset($row[$this->columnMap['nama_paket']])) {
            $formatted['nama_paket'] = $row[$this->columnMap['nama_paket']];
        }
        
        if (isset($this->columnMap['instansi']) && isset($row[$this->columnMap['instansi']])) {
            $formatted['instansi'] = $row[$this->columnMap['instansi']];
            $formatted['nama_satker'] = $row[$this->columnMap['instansi']];
        }
        
        if (isset($this->columnMap['tahun']) && isset($row[$this->columnMap['tahun']])) {
            $formatted['tahun_anggaran'] = $row[$this->columnMap['tahun']];
        }
        
        return $formatted;
    }
    
    public function getFilterOptions() {
        try {
            if (!$this->detective->getTableInfo()) {
                return [];
            }
            
            $options = [];
            
            // Get unique years
            if (isset($this->columnMap['tahun'])) {
                $stmt = $this->pdo->query("SELECT DISTINCT {$this->columnMap['tahun']} FROM {$this->tableName} WHERE {$this->columnMap['tahun']} IS NOT NULL ORDER BY {$this->columnMap['tahun']} DESC");
                $options['tahun_anggaran'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
            }
            
            // Get unique instansi
            if (isset($this->columnMap['instansi'])) {
                $stmt = $this->pdo->query("SELECT DISTINCT {$this->columnMap['instansi']} FROM {$this->tableName} WHERE {$this->columnMap['instansi']} IS NOT NULL AND {$this->columnMap['instansi']} != '' ORDER BY {$this->columnMap['instansi']}");
                $options['kd_klpd'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
            }
            
            // Get unique status
            if (isset($this->columnMap['status'])) {
                $stmt = $this->pdo->query("SELECT DISTINCT {$this->columnMap['status']} FROM {$this->tableName} WHERE {$this->columnMap['status']} IS NOT NULL AND {$this->columnMap['status']} != '' ORDER BY {$this->columnMap['status']}");
                $options['status_paket'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
            }
            
            return $options;
            
        } catch (Exception $e) {
            ApiLogger::error("Error in getFilterOptions: " . $e->getMessage());
            return [];
        }
    }
}

// Error handling
set_error_handler(function($severity, $message, $file, $line) {
    ApiLogger::error("PHP Error: $message in $file:$line");
});

try {
    // Initialize database connection
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Initialize smart model
    $pengadaanModel = new SmartPengadaanModel($pdo);
    
    // Log API access
    ApiLogger::info('API Access', [
        'method' => $_SERVER['REQUEST_METHOD'],
        'params' => $_GET,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
    ]);
    
    // Get and validate parameters
    $action = $_GET['action'] ?? 'list';
    
    // Route to appropriate handler
    switch ($action) {
        case 'test':
            handleTest($pdo, $pengadaanModel);
            break;
            
        case 'list':
            handleList($pengadaanModel, $_GET);
            break;
            
        case 'summary':
            handleSummary($pengadaanModel, $_GET);
            break;
            
        case 'filter_options':
            handleFilterOptions($pengadaanModel);
            break;
            
        case 'search':
            handleSearch($pengadaanModel, $_GET);
            break;
            
        case 'export':
            handleExport($pengadaanModel, $_GET);
            break;
            
        default:
            http_response_code(400);
            echo ApiResponse::error('Invalid action parameter', 400);
    }
    
} catch (PDOException $e) {
    ApiLogger::error('Database Error: ' . $e->getMessage());
    http_response_code(500);
    echo ApiResponse::error('Database connection error', 500, $e->getMessage());
} catch (Exception $e) {
    ApiLogger::error('API Exception: ' . $e->getMessage(), [
        'trace' => $e->getTraceAsString()
    ]);
    
    http_response_code(500);
    echo ApiResponse::error('Internal server error', 500, $e->getMessage());
}

/**
 * Test database and table structure
 */
function handleTest($pdo, $model) {
    try {
        $version = $pdo->query('SELECT VERSION() as version')->fetch();
        $tableInfo = $model->getTableInfo();
        
        echo ApiResponse::success([
            'database_connection' => 'OK',
            'mysql_version' => $version['version'],
            'table_info' => $tableInfo,
            'column_mapping' => $model->detective->getColumnMap(),
            'server_time' => date('Y-m-d H:i:s')
        ], 'Database test completed');
        
    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Handle list request
 */
function handleList($model, $params) {
    try {
        $filters = [];
        if (isset($params['tahun_anggaran'])) $filters['tahun_anggaran'] = $params['tahun_anggaran'];
        if (isset($params['search'])) $filters['search'] = $params['search'];
        
        $page = max(1, intval($params['page'] ?? 1));
        $limit = min(500, max(5, intval($params['limit'] ?? 20)));
        
        $result = $model->getPaginatedData($filters, $page, $limit);
        
        if (empty($result['data'])) {
            echo ApiResponse::paginated([], $result['pagination'], 'No data found');
            return;
        }
        
        echo ApiResponse::paginated($result['data'], $result['pagination']);
        
    } catch (Exception $e) {
        ApiLogger::error('List handler error: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * Handle summary request
 */
function handleSummary($model, $params) {
    try {
        $filters = [];
        if (isset($params['tahun_anggaran'])) $filters['tahun_anggaran'] = $params['tahun_anggaran'];
        if (isset($params['search'])) $filters['search'] = $params['search'];
        
        $summary = $model->getSummary($filters);
        echo ApiResponse::success(['summary' => $summary]);
        
    } catch (Exception $e) {
        ApiLogger::error('Summary handler error: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * Handle filter options request
 */
function handleFilterOptions($model) {
    try {
        $options = $model->getFilterOptions();
        echo ApiResponse::success(['options' => $options]);
        
    } catch (Exception $e) {
        ApiLogger::error('Filter options handler error: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * Handle search request
 */
function handleSearch($model, $params) {
    try {
        $keyword = trim($params['q'] ?? $params['keyword'] ?? '');
        $limit = min(50, max(5, intval($params['limit'] ?? 20)));
        
        if (empty($keyword) || strlen($keyword) < 2) {
            echo ApiResponse::error('Search keyword must be at least 2 characters', 400);
            return;
        }
        
        $results = $model->search($keyword, $limit);
        
        echo ApiResponse::success([
            'results' => $results,
            'keyword' => $keyword,
            'count' => count($results)
        ]);
        
    } catch (Exception $e) {
        ApiLogger::error('Search handler error: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * Handle export request
 */
function handleExport($model, $params) {
    try {
        $format = strtolower($params['format'] ?? 'csv');
        
        if (!in_array($format, ['csv', 'json'])) {
            echo ApiResponse::error('Invalid export format. Supported: csv, json', 400);
            return;
        }
        
        $filters = [];
        if (isset($params['tahun_anggaran'])) $filters['tahun_anggaran'] = $params['tahun_anggaran'];
        if (isset($params['search'])) $filters['search'] = $params['search'];
        
        $result = $model->getPaginatedData($filters, 1, 5000); // Max 5000 records untuk export
        $data = $result['data'];
        
        if (empty($data)) {
            echo ApiResponse::error('No data to export', 400);
            return;
        }
        
        $filename = 'epurchasing_export_' . date('Y-m-d_H-i-s');
        
        switch ($format) {
            case 'csv':
                header('Content-Type: text/csv');
                header("Content-Disposition: attachment; filename=\"{$filename}.csv\"");
                
                $output = fopen('php://output', 'w');
                
                // Write header
                fputcsv($output, array_keys($data[0]));
                
                // Write data
                foreach ($data as $row) {
                    fputcsv($output, $row);
                }
                
                fclose($output);
                break;
                
            case 'json':
                header('Content-Type: application/json');
                header("Content-Disposition: attachment; filename=\"{$filename}.json\"");
                
                echo json_encode([
                    'export_date' => date('Y-m-d H:i:s'),
                    'total_records' => count($data),
                    'filters' => $filters,
                    'data' => $data
                ], JSON_PRETTY_PRINT);
                break;
        }
        
        ApiLogger::info('Export completed', [
            'format' => $format,
            'records' => count($data)
        ]);
        
    } catch (Exception $e) {
        ApiLogger::error('Export handler error: ' . $e->getMessage());
        throw $e;
    }
}
?>