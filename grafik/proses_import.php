<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Sertakan header
include '../navbar/header.php';
include '../config/database.php';

// Daftar tabel yang diizinkan untuk diimpor (PENTING UNTUK KEAMANAN)
$tabel_diizinkan = [
    'data_sektoral', 
    'pencatatan_nontender', 
    'realisasi_dikecualikan', 
    'realisasi_epurchasing',
    'realisasi_nontender',
    'realisasi_pengadaandarurat',
    'realisasi_penunjukanlangsung',
    'realisasi_seleksi',
    'realisasi_swakelola',
    'realisasi_tender',
    'rup_keseluruhan',
    'rup_swakelola'
];

// Opsi untuk dropdown bulan
$daftar_bulan = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
    7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];
?>

<?php if (isset($_GET['status'])): ?>
    <?php if ($_GET['status'] == 'sukses'): ?>
        <div class="alert alert-success" role="alert">
            <strong>Berhasil!</strong> Data telah sukses diimpor ke database.
        </div>
    <?php elseif ($_GET['status'] == 'gagal'): ?>
        <div class="alert alert-danger" role="alert">
            <strong>Gagal!</strong> Terjadi kesalahan: <?php echo htmlspecialchars($_GET['pesan']); ?>
        </div>
    <?php endif; ?>
<?php endif; ?>


<div class="card">
    <div class="card-header">
        Formulir Import
    </div>
    <div class="card-body">
        <form action="proses_import.php" method="post" enctype="multipart/form-data">
            
            <div class="mb-3">
                <label for="tabel_tujuan" class="form-label">1. Pilih Tabel Tujuan</label>
                <select class="form-select" name="tabel_tujuan" id="tabel_tujuan" required>
                    <option value="">-- Pilih Tabel --</option>
                    <?php foreach ($tabel_diizinkan as $tabel): ?>
                        <option value="<?php echo $tabel; ?>"><?php echo ucwords(str_replace('_', ' ', $tabel)); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="tahun" class="form-label">2. Pilih Tahun</label>
                    <select class="form-select" name="tahun" id="tahun" required>
                        <?php
                        $tahun_sekarang = date('Y');
                        for ($i = $tahun_sekarang; $i >= $tahun_sekarang - 5; $i--) {
                            echo "<option value='{$i}'>{$i}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="bulan" class="form-label">3. Pilih Bulan</label>
                    <select class="form-select" name="bulan" id="bulan" required>
                        <?php foreach ($daftar_bulan as $angka => $nama): ?>
                            <option value="<?php echo $nama; ?>"><?php echo $nama; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label for="fileExcel" class="form-label">4. Pilih File Excel (.xlsx, .xls)</label>
                <input class="form-control" type="file" name="fileExcel" id="fileExcel" required accept=".xlsx, .xls">
            </div>

            <button type="submit" name="submit" class="btn btn-primary w-100">🚀 Mulai Proses Import</button>

        </form>
    </div>
</div>


<?php
// Sertakan footer
include '../navbar/footer.php';
?>