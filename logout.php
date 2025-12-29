<?php
/**
 * SIP BANAR - Logout Script
 * Standar Keamanan Tinggi: Penghapusan Sesi & Cache
 */

// 1. Inisialisasi Sesi
session_start();

// 2. Unset semua variabel sesi
$_SESSION = array();

// 3. Hapus cookie sesi jika ada (Penting untuk keamanan client-side)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Hancurkan sesi di server
session_destroy();

// 5. Header untuk mencegah browser menyimpan cache halaman sebelumnya
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// 6. Redirect ke halaman login atau homepage
// Sesuaikan index.php atau login.php sesuai struktur Anda
header("Location: login.php?status=success_logout");
exit;
?>