<?php
require_once '../config/database.php';
require_once 'header.php';

$database = new Database();
$db = $database->getConnection();

// Get statistics
$stats = [];
try {
    // Get all tables
    $query = "SHOW TABLES";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $tables = $stmt->fetchAll(PDO::FETCH_NUM);
    
    $totalTables = count($tables);
    $totalRecords = 0;
    
    // Count records in each table
    foreach($tables as $table) {
        try {
            $countQuery = "SELECT COUNT(*) as total FROM " . $table[0];
            $countStmt = $db->prepare($countQuery);
            $countStmt->execute();
            $result = $countStmt->fetch(PDO::FETCH_ASSOC);
            $totalRecords += $result['total'];
            
            $stats[$table[0]] = $result['total'];
        } catch(PDOException $e) {
            // Skip if error
        }
    }
    
} catch(PDOException $e) {
    $error = $e->getMessage();
}
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="jumbotron bg-gradient text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h1 class="display-4">
                    <i class="fas fa-tachometer-alt"></i> Dashboard Sistem Pengadaan
                </h1>
                <p class="lead">Sistem Import dan Manajemen Data Excel ke Database MySQL</p>
                <hr class="my-4" style="border-color: rgba(255,255,255,0.3);">
                <p>Kelola data pengadaan Anda dengan mudah menggunakan sistem import Excel yang powerful.</p>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Total Tabel</h6>
                            <h2 class="mb-0 mt-2"><?php echo $totalTables; ?></h2>
                        </div>
                        <div>
                            <i class="fas fa-database fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Total Data</h6>
                            <h2 class="mb-0 mt-2"><?php echo number_format($totalRecords); ?></h2>
                        </div>
                        <div>
                            <i class="fas fa-chart-line fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Import Hari Ini</h6>
                            <h2 class="mb-0 mt-2">0</h2>
                        </div>
                        <div>
                            <i class="fas fa-file-import fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Export Hari Ini</h6>
                            <h2 class="mb-0 mt-2">0</h2>
                        </div>
                        <div>
                            <i class="fas fa-file-export fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-bolt"></i> Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <a href="import_excel.php" class="btn btn-primary btn-block btn-lg">
                                <i class="fas fa-file-upload fa-2x d-block mb-2"></i>
                                Import Data Excel
                            </a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="view_data.php" class="btn btn-info btn-block btn-lg">
                                <i class="fas fa-eye fa-2x d-block mb-2"></i>
                                Lihat Data
                            </a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="view_data.php" class="btn btn-success btn-block btn-lg">
                                <i class="fas fa-file-download fa-2x d-block mb-2"></i>
                                Export Data
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Statistics -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-table"></i> Statistik Per Tabel</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th>No</th>
                                    <th>Nama Tabel</th>
                                    <th>Jumlah Data</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                arsort($stats); // Sort by record count
                                foreach($stats as $tableName => $count): 
                                ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td>
                                        <i class="fas fa-table text-primary"></i>
                                        <strong><?php echo $tableName; ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge badge-info badge-pill">
                                            <?php echo number_format($count); ?> records
                                        </span>
                                    </td>
                                    <td>
                                        <a href="view_data.php?table=<?php echo $tableName; ?>" 
                                           class="btn btn-sm btn-primary" title="Lihat Data">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="import_excel.php?table=<?php echo $tableName; ?>" 
                                           class="btn btn-sm btn-success" title="Import">
                                            <i class="fas fa-file-import"></i>
                                        </a>
                                        <a href="export_excel.php?table=<?php echo $tableName; ?>" 
                                           class="btn btn-sm btn-info" title="Export">
                                            <i class="fas fa-file-export"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-gradient text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <h5 class="mb-0"><i class="fas fa-star"></i> Fitur Sistem</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-upload fa-3x text-primary mb-3"></i>
                                    <h5>Import Excel</h5>
                                    <p class="text-muted">Upload file Excel (.xls/.xlsx) dan import data ke database dengan mudah</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-sync-alt fa-3x text-success mb-3"></i>
                                    <h5>Auto Mapping</h5>
                                    <p class="text-muted">Sistem otomatis memetakan kolom Excel dengan field database</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-download fa-3x text-info mb-3"></i>
                                    <h5>Export Excel</h5>
                                    <p class="text-muted">Export data dari database ke format Excel dengan format yang rapi</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-shield-alt fa-3x text-warning mb-3"></i>
                                    <h5>Transaction Safe</h5>
                                    <p class="text-muted">Menggunakan database transaction untuk keamanan data</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-table fa-3x text-danger mb-3"></i>
                                    <h5>Multi Table</h5>
                                    <p class="text-muted">Support untuk multiple tabel dalam satu database</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-edit fa-3x text-secondary mb-3"></i>
                                    <h5>CRUD Operations</h5>
                                    <p class="text-muted">Tambah, lihat, edit, dan hapus data dengan mudah</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>