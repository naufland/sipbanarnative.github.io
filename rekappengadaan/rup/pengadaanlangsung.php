<?php
// URL API (ganti sesuai lokasi file php API kamu)
$apiUrl = "http://sipbanar-phpnative.id/api/pengadaan.php";

// Ambil data dari API
$response = file_get_contents($apiUrl);
$data = json_decode($response, true);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Pengadaan</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        table {
            border-collapse: collapse;
            width: 110%;
        }
        th, td {
            border: 4px solid #ccc;
            padding: 8px;
            text-align: left;
        }
        th {
            background: #f4f4f4;
        }
        tr:nth-child(even) {
            background: #fafafa;
        }
    </style>
</head>
<body>
    <h2>Daftar Pengadaan</h2>

    <?php if ($data && $data['success'] && count($data['data']) > 0): ?>
        <table>
            <thead>
                <tr>
                    
                    <th>Paket</th>
                    <th>Pagu (Rp)</th>
                    <th>Jenis Pengadaan</th>
                    <th>Produk Dalam Negeri</th>
                    <th>Usaha Kecil</th>
                    <th>Metode</th>
                    <th>Pemilihan</th>
                    <th>KLPD</th>
                    <th>Satuan Kerja</th>
                    <th>Lokasi</th>
                    <th>ID</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data['data'] as $row): ?>
                <tr>
                    
                    <td><?= htmlspecialchars($row['Paket']) ?></td>
                    <td><?= htmlspecialchars($row['Pagu_Rp']) ?></td>
                    <td><?= htmlspecialchars($row['Jenis_Pengadaan']) ?></td>
                    <td><?= htmlspecialchars($row['Produk_Dalam_Negeri']) ?></td>
                    <td><?= htmlspecialchars($row['Usaha_Kecil']) ?></td>
                    <td><?= htmlspecialchars($row['Metode']) ?></td>
                    <td><?= htmlspecialchars($row['Pemilihan']) ?></td>
                    <td><?= htmlspecialchars($row['KLPD']) ?></td>
                    <td><?= htmlspecialchars($row['Satuan_Kerja']) ?></td>
                    <td><?= htmlspecialchars($row['Lokasi']) ?></td>
                    <td><?= htmlspecialchars($row['ID']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p>
            Halaman <?= $data['pagination']['current_page'] ?> 
            dari <?= $data['pagination']['total_pages'] ?> |
            Total Data: <?= $data['pagination']['total_records'] ?>
        </p>
    <?php else: ?>
        <p>Tidak ada data.</p>
    <?php endif; ?>
</body>
</html>
