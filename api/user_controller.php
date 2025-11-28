<?php
/**
 * ==================================================================================
 * USER MANAGEMENT API CONTROLLER
 * ==================================================================================
 * RESTful API untuk manajemen user (Admin only)
 * 
 * Endpoints:
 * - GET    /api/user_controller.php?action=list          - List all users
 * - GET    /api/user_controller.php?action=get&id=1      - Get user by ID
 * - POST   /api/user_controller.php?action=create        - Create new user
 * - POST   /api/user_controller.php?action=update        - Update user
 * - POST   /api/user_controller.php?action=delete        - Delete user
 * - POST   /api/user_controller.php?action=status        - Change user status
 * ==================================================================================
 */

session_start();
header('Content-Type: application/json');

// ==================================================================================
// CHECK AUTHENTICATION & AUTHORIZATION
// ==================================================================================

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Tidak terautentikasi']);
    exit();
}

// Hanya admin yang bisa akses
$user_role = $_SESSION['role'] ?? 'user';
if (!in_array($user_role, ['admin', 'super_admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Akses ditolak. Hanya admin yang dapat mengakses.']);
    exit();
}

// ==================================================================================
// LOAD DATABASE CONNECTION
// ==================================================================================

function getDBConnection() {
    $conn = null;
    
    if (file_exists(__DIR__ . '/../config/database.php')) {
        include __DIR__ . '/../config/database.php';
    } elseif (file_exists(__DIR__ . '/../koneksi.php')) {
        include __DIR__ . '/../koneksi.php';
    }
    
    if (!isset($conn) || $conn === null) {
        if (isset($host) && isset($dbname) && isset($username)) {
            $conn = mysqli_connect($host, $username, $password, $dbname);
            if ($conn) {
                mysqli_set_charset($conn, "utf8mb4");
            }
        }
    }
    
    return $conn;
}

// ==================================================================================
// RESPONSE HELPERS
// ==================================================================================

function jsonResponse($success, $message, $data = null, $http_code = 200) {
    http_response_code($http_code);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit();
}

function errorResponse($message, $http_code = 400) {
    jsonResponse(false, $message, null, $http_code);
}

function successResponse($message, $data = null) {
    jsonResponse(true, $message, $data, 200);
}

// ==================================================================================
// API ENDPOINTS
// ==================================================================================

$action = $_GET['action'] ?? '';
$conn = getDBConnection();

if (!$conn) {
    errorResponse('Koneksi database gagal', 500);
}

switch ($action) {
    
    // ==================================================================================
    // LIST USERS
    // ==================================================================================
    case 'list':
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
        $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
        $role_filter = isset($_GET['role']) ? mysqli_real_escape_string($conn, $_GET['role']) : '';
        $status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';
        
        $offset = ($page - 1) * $limit;
        
        // Build WHERE clause
        $where = [];
        if (!empty($search)) {
            $where[] = "(username LIKE '%$search%' OR full_name LIKE '%$search%' OR email LIKE '%$search%')";
        }
        if (!empty($role_filter)) {
            $where[] = "role = '$role_filter'";
        }
        if (!empty($status_filter)) {
            $where[] = "status = '$status_filter'";
        }
        
        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // Count total
        $count_query = "SELECT COUNT(*) as total FROM users $where_clause";
        $count_result = mysqli_query($conn, $count_query);
        $total = mysqli_fetch_assoc($count_result)['total'];
        
        // Get users
        $query = "SELECT id, username, full_name, email, role, status, last_login, created_at, updated_at 
                  FROM users 
                  $where_clause 
                  ORDER BY created_at DESC 
                  LIMIT $limit OFFSET $offset";
        
        $result = mysqli_query($conn, $query);
        
        if (!$result) {
            errorResponse('Query error: ' . mysqli_error($conn), 500);
        }
        
        $users = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $users[] = $row;
        }
        
        successResponse('Data user berhasil diambil', [
            'users' => $users,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => intval($total),
                'total_pages' => ceil($total / $limit)
            ]
        ]);
        
        break;
    
    // ==================================================================================
    // GET USER BY ID
    // ==================================================================================
    case 'get':
        $user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if ($user_id <= 0) {
            errorResponse('ID user tidak valid', 400);
        }
        
        $query = "SELECT id, username, full_name, email, role, status, last_login, created_at, updated_at 
                  FROM users WHERE id = $user_id LIMIT 1";
        
        $result = mysqli_query($conn, $query);
        
        if (!$result || mysqli_num_rows($result) === 0) {
            errorResponse('User tidak ditemukan', 404);
        }
        
        $user = mysqli_fetch_assoc($result);
        
        // Get login history
        $history_query = "SELECT login_time, logout_time, ip_address 
                          FROM login_logs 
                          WHERE user_id = $user_id 
                          ORDER BY login_time DESC 
                          LIMIT 5";
        $history_result = mysqli_query($conn, $history_query);
        
        $login_history = [];
        while ($row = mysqli_fetch_assoc($history_result)) {
            $login_history[] = $row;
        }
        
        $user['login_history'] = $login_history;
        
        successResponse('Data user berhasil diambil', ['user' => $user]);
        
        break;
    
    // ==================================================================================
    // CREATE USER
    // ==================================================================================
    case 'create':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            errorResponse('Method not allowed', 405);
        }
        
        // Validasi input
        $username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $password = isset($_POST['password']) ? trim($_POST['password']) : '';
        $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $role = isset($_POST['role']) ? trim($_POST['role']) : 'user';
        $status = isset($_POST['status']) ? trim($_POST['status']) : 'active';
        
        // Validasi required fields
        if (empty($username) || empty($password) || empty($full_name)) {
            errorResponse('Username, password, dan nama lengkap harus diisi', 400);
        }
        
        // Validasi panjang password
        if (strlen($password) < 6) {
            errorResponse('Password minimal 6 karakter', 400);
        }
        
        // Validasi email
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            errorResponse('Format email tidak valid', 400);
        }
        
        // Validasi role
        $valid_roles = ['super_admin', 'admin', 'editor', 'user'];
        if (!in_array($role, $valid_roles)) {
            errorResponse('Role tidak valid', 400);
        }
        
        // Hanya super_admin yang bisa create super_admin
        if ($role === 'super_admin' && $_SESSION['role'] !== 'super_admin') {
            errorResponse('Hanya super admin yang dapat membuat user super admin', 403);
        }
        
        // Escape input
        $username = mysqli_real_escape_string($conn, $username);
        $full_name = mysqli_real_escape_string($conn, $full_name);
        $email = mysqli_real_escape_string($conn, $email);
        $role = mysqli_real_escape_string($conn, $role);
        $status = mysqli_real_escape_string($conn, $status);
        
        // Check username exists
        $check_query = "SELECT id FROM users WHERE username = '$username' LIMIT 1";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            errorResponse('Username sudah digunakan', 409);
        }
        
        // Check email exists
        if (!empty($email)) {
            $email_check = "SELECT id FROM users WHERE email = '$email' LIMIT 1";
            $email_result = mysqli_query($conn, $email_check);
            
            if (mysqli_num_rows($email_result) > 0) {
                errorResponse('Email sudah digunakan', 409);
            }
        }
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $hashed_password = mysqli_real_escape_string($conn, $hashed_password);
        
        // Insert user
        $query = "INSERT INTO users (username, password, full_name, email, role, status, created_at) 
                  VALUES ('$username', '$hashed_password', '$full_name', '$email', '$role', '$status', NOW())";
        
        if (mysqli_query($conn, $query)) {
            $new_user_id = mysqli_insert_id($conn);
            
            successResponse('User berhasil ditambahkan', [
                'user' => [
                    'id' => $new_user_id,
                    'username' => $username,
                    'full_name' => $full_name,
                    'email' => $email,
                    'role' => $role,
                    'status' => $status
                ]
            ]);
        } else {
            errorResponse('Gagal menambahkan user: ' . mysqli_error($conn), 500);
        }
        
        break;
    
    // ==================================================================================
    // UPDATE USER
    // ==================================================================================
    case 'update':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            errorResponse('Method not allowed', 405);
        }
        
        $user_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if ($user_id <= 0) {
            errorResponse('ID user tidak valid', 400);
        }
        
        // Get existing user
        $check_query = "SELECT role FROM users WHERE id = $user_id LIMIT 1";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) === 0) {
            errorResponse('User tidak ditemukan', 404);
        }
        
        $existing_user = mysqli_fetch_assoc($check_result);
        
        // Ambil input
        $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $role = isset($_POST['role']) ? trim($_POST['role']) : '';
        $status = isset($_POST['status']) ? trim($_POST['status']) : '';
        $new_password = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';
        
        // Validasi
        if (empty($full_name)) {
            errorResponse('Nama lengkap harus diisi', 400);
        }
        
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            errorResponse('Format email tidak valid', 400);
        }
        
        // Validasi role change
        if (!empty($role)) {
            $valid_roles = ['super_admin', 'admin', 'editor', 'user'];
            if (!in_array($role, $valid_roles)) {
                errorResponse('Role tidak valid', 400);
            }
            
            // Hanya super_admin yang bisa change ke super_admin
            if ($role === 'super_admin' && $_SESSION['role'] !== 'super_admin') {
                errorResponse('Hanya super admin yang dapat mengubah role menjadi super admin', 403);
            }
        }
        
        // Build update query
        $updates = [];
        
        if (!empty($full_name)) {
            $full_name = mysqli_real_escape_string($conn, $full_name);
            $updates[] = "full_name = '$full_name'";
        }
        
        if (!empty($email)) {
            $email = mysqli_real_escape_string($conn, $email);
            // Check email unique
            $email_check = "SELECT id FROM users WHERE email = '$email' AND id != $user_id LIMIT 1";
            $email_result = mysqli_query($conn, $email_check);
            if (mysqli_num_rows($email_result) > 0) {
                errorResponse('Email sudah digunakan', 409);
            }
            $updates[] = "email = '$email'";
        }
        
        if (!empty($role)) {
            $role = mysqli_real_escape_string($conn, $role);
            $updates[] = "role = '$role'";
        }
        
        if (!empty($status)) {
            $status = mysqli_real_escape_string($conn, $status);
            $updates[] = "status = '$status'";
        }
        
        if (!empty($new_password)) {
            if (strlen($new_password) < 6) {
                errorResponse('Password minimal 6 karakter', 400);
            }
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $hashed_password = mysqli_real_escape_string($conn, $hashed_password);
            $updates[] = "password = '$hashed_password'";
        }
        
        if (empty($updates)) {
            errorResponse('Tidak ada data yang diupdate', 400);
        }
        
        $updates[] = "updated_at = NOW()";
        $update_string = implode(', ', $updates);
        
        $query = "UPDATE users SET $update_string WHERE id = $user_id";
        
        if (mysqli_query($conn, $query)) {
            successResponse('User berhasil diupdate');
        } else {
            errorResponse('Gagal update user: ' . mysqli_error($conn), 500);
        }
        
        break;
    
    // ==================================================================================
    // DELETE USER
    // ==================================================================================
    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            errorResponse('Method not allowed', 405);
        }
        
        $user_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if ($user_id <= 0) {
            errorResponse('ID user tidak valid', 400);
        }
        
        // Tidak bisa delete diri sendiri
        if ($user_id == $_SESSION['user_id']) {
            errorResponse('Anda tidak dapat menghapus akun Anda sendiri', 403);
        }
        
        // Get user info
        $check_query = "SELECT role FROM users WHERE id = $user_id LIMIT 1";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) === 0) {
            errorResponse('User tidak ditemukan', 404);
        }
        
        $user = mysqli_fetch_assoc($check_result);
        
        // Hanya super_admin yang bisa delete super_admin
        if ($user['role'] === 'super_admin' && $_SESSION['role'] !== 'super_admin') {
            errorResponse('Hanya super admin yang dapat menghapus user super admin', 403);
        }
        
        // Delete user
        $query = "DELETE FROM users WHERE id = $user_id";
        
        if (mysqli_query($conn, $query)) {
            // Optional: Delete related data
            mysqli_query($conn, "DELETE FROM login_logs WHERE user_id = $user_id");
            
            successResponse('User berhasil dihapus');
        } else {
            errorResponse('Gagal menghapus user: ' . mysqli_error($conn), 500);
        }
        
        break;
    
    // ==================================================================================
    // CHANGE USER STATUS
    // ==================================================================================
    case 'status':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            errorResponse('Method not allowed', 405);
        }
        
        $user_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $status = isset($_POST['status']) ? trim($_POST['status']) : '';
        
        if ($user_id <= 0) {
            errorResponse('ID user tidak valid', 400);
        }
        
        $valid_statuses = ['active', 'inactive', 'suspended'];
        if (!in_array($status, $valid_statuses)) {
            errorResponse('Status tidak valid', 400);
        }
        
        // Tidak bisa suspend/inactive diri sendiri
        if ($user_id == $_SESSION['user_id'] && $status !== 'active') {
            errorResponse('Anda tidak dapat menonaktifkan akun Anda sendiri', 403);
        }
        
        $status = mysqli_real_escape_string($conn, $status);
        
        $query = "UPDATE users SET status = '$status', updated_at = NOW() WHERE id = $user_id";
        
        if (mysqli_query($conn, $query)) {
            successResponse('Status user berhasil diubah');
        } else {
            errorResponse('Gagal mengubah status: ' . mysqli_error($conn), 500);
        }
        
        break;
    
    // ==================================================================================
    // DEFAULT
    // ==================================================================================
    default:
        errorResponse('Invalid action: ' . $action, 400);
        break;
}

?>