<?php
// File: api/login_process.php

session_start();
header('Content-Type: application/json');

// 1. PANGGIL FILE KONEKSI
if (file_exists('../config/database.php')) {
    include '../config/database.php';
} elseif (file_exists('../koneksi.php')) {
    include '../koneksi.php';
} else {
    echo json_encode(['success' => false, 'message' => 'File database tidak ditemukan!']);
    exit;
}

// Pastikan variabel $conn tersedia
if (!isset($conn) || !$conn) {
    if (isset($host) && isset($username) && isset($password) && isset($dbname)) {
        $conn = mysqli_connect($host, $username, $password, $dbname);
    }
    if (!$conn) {
        echo json_encode(['success' => false, 'message' => 'Gagal koneksi DB: ' . mysqli_connect_error()]);
        exit;
    }
}

// 2. AMBIL DATA INPUT
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';

if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Username dan Password wajib diisi!']);
    exit;
}

// 3. QUERY KE DATABASE (Sesuai kolom di Screenshot)
// Kita ambil: id, username, password, full_name, status
$query = "SELECT id, username, password, full_name, status FROM users WHERE username = ? LIMIT 1";

if ($stmt = $conn->prepare($query)) {
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // 4. CEK STATUS USER
        if ($user['status'] !== 'active') {
            echo json_encode(['success' => false, 'message' => 'Akun Anda dinonaktifkan.']);
            exit;
        }

        // 5. VERIFIKASI PASSWORD
        // Kita gunakan password_verify() untuk mencocokkan hash $2y$10$... di database
        // Kita juga tambahkan cek MD5 sebagai cadangan jika Anda me-reset password manual via SQL
        $isPasswordCorrect = password_verify($password, $user['password']) || (md5($password) === $user['password']);

        if ($isPasswordCorrect) {
            // --- LOGIN BERHASIL ---
            
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['username']   = $user['username'];
            $_SESSION['full_name']  = $user['full_name']; // Sesuai nama kolom di DB
            
            // Logika Role Sederhana (Karena kolom role tidak ada di screenshot)
            // Jika username mengandung 'admin', set role admin. Selain itu 'operator'.
            if (stripos($user['username'], 'admin') !== false) {
                $_SESSION['role'] = 'admin';
            } else {
                $_SESSION['role'] = 'operator';
            }
            
            $_SESSION['login_time'] = time();

            // Update last_login (Opsional, biar data di DB terupdate)
            $updateQ = "UPDATE users SET last_login = NOW() WHERE id = ?";
            $upStmt = $conn->prepare($updateQ);
            $upStmt->bind_param("i", $user['id']);
            $upStmt->execute();

            echo json_encode([
                'success'  => true,
                'message'  => 'Login berhasil! Masuk ke Dashboard...',
                'redirect' => 'index.php'
            ]);

        } else {
            echo json_encode(['success' => false, 'message' => 'Password salah!']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Username tidak ditemukan!']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Query Error: ' . $conn->error]);
}

$conn->close();
?>