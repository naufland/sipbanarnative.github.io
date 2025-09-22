<?php
// FILE: rekapitulasi.php

// 1. KONEKSI & PENGOLAHAN DATA MENGGUNAKAN CLASS DATABASE (PDO)
// =================================================================

// Sertakan file Class Database Anda
require_once '../config/database.php';

// Inisialisasi variabel untuk menampung data dan statistik
$metode_list = [];
$penyedia_stats = ['paket' => 0, 'pagu' => 0];
$swakelola_stats = ['paket' => 0, 'pagu' => 0];
$grand_total = ['paket' => 0, 'pagu' => 0];

try {
    // Buat instance dari class Database dan dapatkan koneksi PDO
    $database = new Database();
    $conn = $database->getConnection();

    // Query SQL untuk mengambil data dari VIEW (tidak ada perubahan di sini)
    $sql = "
        SELECT 
            Metode_Pengadaan,
            Jumlah_Paket_RUP,
            Pagu 
        FROM 
            v_rekap_rup
        ORDER BY FIELD(Metode_Pengadaan, 
            'E-Purchasing', 'Pengadaan Langsung', 'Penunjukan Langsung', 'Seleksi', 
            'Tender', 'Tender Cepat', 'Dikecualikan', 'Penyedia', 'Swakelola', 'Total'
        )
    ";

    // Siapkan dan eksekusi statement menggunakan PDO
    $stmt = $conn->prepare($sql);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        // Ambil semua hasil
        $results = $stmt->fetchAll();

        foreach ($results as $row) {
            $metode = $row['Metode_Pengadaan'];

            // Simpan setiap baris ke dalam list, KECUALI baris total dari view
            if ($metode !== 'Penyedia' && $metode !== 'Swakelola' && $metode !== 'Total') {
                $metode_list[] = $row;
            }

            // Kelompokkan data untuk perhitungan subtotal
            if ($metode === 'Swakelola') {
                $swakelola_stats['paket'] += $row['Jumlah_Paket_RUP'];
                $swakelola_stats['pagu'] += $row['Pagu'];
            } elseif ($metode !== 'Total' && $metode !== 'Penyedia') {
                $penyedia_stats['paket'] += $row['Jumlah_Paket_RUP'];
                $penyedia_stats['pagu'] += $row['Pagu'];
            }
        }
    }
} catch (Exception $e) {
    // Tangani error jika koneksi atau query gagal
    die("Error: Tidak dapat mengambil data dari database. Pesan: " . $e->getMessage());
}

// Hitung total keseluruhan dari subtotal
$grand_total['paket'] = $penyedia_stats['paket'] + $swakelola_stats['paket'];
$grand_total['pagu'] = $penyedia_stats['pagu'] + $swakelola_stats['pagu'];

// 2. BAGIAN TAMPILAN (VIEW) - Tidak ada perubahan di bawah ini
// =================================================

// Set judul halaman dan sertakan header Anda
$page_title = "Laporan Perencanaan RUP";
include '../navbar/header.php'; // Path ke header Anda

?>
<script src="../../js/submenu.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<style>

    .nav-container {
    /* 1. Aktifkan mode Flexbox */
    display: flex;
    
    /* 2. Perintahkan untuk "merentangkan" item */
    /* Ini akan mendorong .nav-kiri ke paling kiri dan .nav-kanan ke paling kanan */
    justify-content: space-between;

    /* 3. Sejajarkan item secara vertikal (biar rapi di tengah) */
    align-items: center;

    /* 4. Pastikan container-nya lebar penuh */
    width: 100%;
    padding: 0 20px; /* Beri sedikit jarak dari tepi layar */
    box-sizing: border-box; /* Agar padding tidak menambah lebar */
}

/* Sedikit styling tambahan untuk item di dalamnya */
.nav-kiri, .nav-kanan {
    display: flex;
    align-items: center;
    gap: 20px; /* Memberi jarak antar menu */
}
    body {
        background-color: #f4f7f6;
        font-family: Arial, sans-serif;
    }

    .container {
        max-width: 900px;
        margin: 40px auto;
        padding: 20px;
    }

    .report-card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .report-header {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
        padding: 15px 25px;
        font-size: 20px;
        text-align: center;
        font-weight: bold;
    }

    .table-container {
        padding: 25px;
    }

    .styled-table {
        border-collapse: collapse;
        width: 100%;
        font-size: 14px;
    }

    .styled-table thead tr {
        background-color: #DDEBF7;
        color: #333;
        text-align: center;
    }

    .styled-table th,
    .styled-table td {
        border: 1px solid #cccccc;
        padding: 12px 15px;
    }

    .styled-table tbody tr:nth-of-type(even) {
        background-color: #f3f3f3;
    }

    .styled-table tbody tr:hover {
        background-color: #e6f7ff;
    }

    .styled-table td:first-child,
    .styled-table td:nth-child(3) {
        text-align: center;
    }

    .styled-table td:last-child {
        text-align: right;
    }

    .penyedia-row {
        background-color: #A3CCDA !important;
    }

    .swakelola-row {
        background-color: #708993 !important;
    }

    .total-row {
        background-color: #DDEBF7 !important;
        font-weight: bold;
    }
</style>

<div class="container">
    <div class="report-card">
        <div class="report-header">
            PERENCANAAN
        </div>
        <div class="table-container">
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>NO</th>
                        <th>METODE PENGADAAN</th>
                        <th>JUMLAH PAKET RUP</th>
                        <th>PAGU</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $nomor = 1;
                    foreach ($metode_list as $metode):
                    ?>
                        <tr>
                            <td><?= $nomor++ ?></td>
                            <td><?= htmlspecialchars($metode['Metode_Pengadaan']) ?></td>
                            <td><?= number_format($metode['Jumlah_Paket_RUP'], 0, ',', '.') ?></td>
                            <td><?= number_format($metode['Pagu'] ?? 0, 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>

                    <tr class="penyedia-row">
                        <td colspan="2">Penyedia</td>
                        <td><?= number_format($penyedia_stats['paket'], 0, ',', '.') ?></td>
                        <td><?= number_format($penyedia_stats['pagu'], 0, ',', '.') ?></td>
                    </tr>

                    <tr class="swakelola-row">
                        <td colspan="2">Swakelola</td>
                        <td><?= number_format($swakelola_stats['paket'], 0, ',', '.') ?></td>
                        <td><?= number_format($swakelola_stats['pagu'], 0, ',', '.') ?></td>
                    </tr>

                    <tr class="total-row">
                        <td colspan="2">Total</td>
                        <td><?= number_format($grand_total['paket'], 0, ',', '.') ?></td>
                        <td><?= number_format($grand_total['pagu'], 0, ',', '.') ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
// Sertakan footer Anda
include '../navbar/footer.php'; // Path ke footer Anda
?>