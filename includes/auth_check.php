<?php
/**
 * ==================================================================================
 * SIMPLE AUTH CHECK - UNTUK PROTEKSI FITUR IMPORT DATA
 * ==================================================================================
 * Hanya untuk memastikan user sudah login sebelum mengakses fitur import
 * Tidak ada role-based access control (semua user yang login punya akses sama)
 * ==================================================================================
 */

// Mulai session jika belum
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==================================================================================
// KONFIGURASI
// ==================================================================================

// Timeout session (30 menit)
if (!defined('SESSION_TIMEOUT')) {
    define('SESSION_TIMEOUT', 1800);
}

// ==================================================================================
// CEK LOGIN
// ==================================================================================

// Cek apakah user sudah login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    // Simpan URL yang diminta untuk redirect setelah login
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    
    // Redirect ke login
    $currentPath = $_SERVER['PHP_SELF'];
    $depth = substr_count($currentPath, '/') - 1;
    $loginPath = str_repeat('../', $depth) . 'login.php';
    
    header("Location: " . $loginPath);
    exit();
}

// ==================================================================================
// CEK SESSION TIMEOUT
// ==================================================================================

// Cek apakah session sudah timeout
if (isset($_SESSION['last_activity'])) {
    $inactive_time = time() - $_SESSION['last_activity'];
    
    if ($inactive_time > SESSION_TIMEOUT) {
        // Session timeout - logout
        session_unset();
        session_destroy();
        
        $currentPath = $_SERVER['PHP_SELF'];
        $depth = substr_count($currentPath, '/') - 1;
        $loginPath = str_repeat('../', $depth) . 'login.php?timeout=1';
        
        header("Location: " . $loginPath);
        exit();
    }
}

// Update waktu aktivitas
$_SESSION['last_activity'] = time();

// ==================================================================================
// HELPER FUNCTIONS
// ==================================================================================

/**
 * Get current user info
 */
function getCurrentUser() {
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'full_name' => $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User'
    ];
}

/**
 * Get user ID
 */
function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get username
 */
function getUsername() {
    return $_SESSION['username'] ?? null;
}

/**
 * Get user full name
 */
function getUserFullName() {
    return $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User';
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

?>