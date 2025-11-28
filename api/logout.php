<?php
session_start();
session_unset();
session_destroy(); // Hapus semua sesi
header("Location: ../index.php"); // Kembali ke halaman utama
exit();
?>