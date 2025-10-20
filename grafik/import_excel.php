<?php
require_once '../navbar/header.php';
?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<style>
.card-header-gradient {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
}
</style>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header card-header-gradient text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-file-excel"></i> Import Data Excel
                    </h4>
                </div>
                <div class="card-body">
                    <form id="importForm" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="table_name">Pilih Tabel <span class="text-danger">*</span></label>
                                    <select class="form-control" id="table_name" name="table_name" required>
                                        <option value="">-- Pilih Tabel --</option>
                                    </select>
                                    <small class="form-text text-muted">Pilih tabel tujuan untuk import data</small>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="form-group mb-3">
                                    <label for="tahun">Tahun</label>
                                    <select class="form-control" id="tahun" name="tahun">
                                        <option value="">-- Pilih Tahun --</option>
                                        <?php
                                        $currentYear = date('Y');
                                        for ($i = $currentYear - 5; $i <= $currentYear + 5; $i++) {
                                            $selected = ($i == $currentYear) ? 'selected' : '';
                                            echo "<option value='$i' $selected>$i</option>";
                                        }
                                        ?>
                                    </select>
                                    <small class="form-text text-muted">Opsional jika tabel memiliki kolom tahun</small>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="form-group mb-3">
                                    <label for="bulan">Bulan</label>
                                    <select class="form-control" id="bulan" name="bulan">
                                        <option value="">-- Pilih Bulan --</option>
                                        <option value="Januari">Januari</option>
                                        <option value="Februari">Februari</option>
                                        <option value="Maret">Maret</option>
                                        <option value="April">April</option>
                                        <option value="Mei">Mei</option>
                                        <option value="Juni">Juni</option>
                                        <option value="Juli">Juli</option>
                                        <option value="Agustus">Agustus</option>
                                        <option value="September">September</option>
                                        <option value="Oktober">Oktober</option>
                                        <option value="November">November</option>
                                        <option value="Desember">Desember</option>
                                    </select>
                                    <small class="form-text text-muted">Opsional jika tabel memiliki kolom bulan</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="perubahan">Perubahan</label>
                                    <select class="form-control" id="perubahan" name="perubahan">
                                        <option value="">-- Pilih Status --</option>
                                        <option value="Perubahan">Perubahan</option>
                                        <option value="Tidak">Tidak</option>
                                    </select>
                                    <small class="form-text text-muted">Opsional jika tabel memiliki kolom perubahan</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="excel_file">File Excel <span class="text-danger">*</span></label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="excel_file" name="excel_file" accept=".xlsx,.xls,.csv" required>
                                <label class="custom-file-label" for="excel_file">Pilih file...</label>
                            </div>
                            <small class="form-text text-muted">
                                Format yang didukung: .xlsx, .xls, .csv (Maksimal 10MB)
                            </small>
                        </div>

                        <div class="alert alert-info">
                            <h6 class="alert-heading"><i class="fas fa-info-circle"></i> Petunjuk Import:</h6>
                            <ul class="mb-0">
                                <li>Pastikan header/kolom pertama Excel sesuai dengan nama kolom di database</li>
                                <li>Nama kolom tidak case-sensitive (huruf besar/kecil tidak masalah)</li>
                                <li>Baris pertama harus berisi nama kolom</li>
                                <li>Data mulai dari baris kedua</li>
                                <li>Kolom yang tidak ada di database akan diabaikan</li>
                                <li>Proses import dapat memakan waktu untuk data dalam jumlah besar</li>
                            </ul>
                        </div>

                        <div id="progressArea" class="mb-3" style="display: none;">
                            <div class="progress">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%">
                                    Sedang mengimport data...
                                </div>
                            </div>
                        </div>

                        <div id="alertArea"></div>

                        <div class="text-right">
                            <button type="submit" class="btn btn-primary" id="btnImport">
                                <i class="fas fa-upload"></i> Import Data
                            </button>
                            <button type="reset" class="btn btn-secondary">
                                <i class="fas fa-redo"></i> Reset
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabel Hasil Import -->
            <div class="card mt-4" id="resultCard" style="display: none;">
                <div class="card-header card-header-gradient text-white">
                    <h5 class="mb-0"><i class="fas fa-check-circle"></i> Hasil Import</h5>
                </div>
                <div class="card-body">
                    <div id="resultContent"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Load daftar tabel
    loadTables();

    // Update label file input
    $('.custom-file-input').on('change', function() {
        let fileName = $(this).val().split('\\').pop();
        $(this).siblings('.custom-file-label').addClass('selected').html(fileName);
    });

    // Handle form submit
    $('#importForm').on('submit', function(e) {
        e.preventDefault();
        
        let formData = new FormData(this);
        
        // Validasi file size (10MB)
        let fileSize = $('#excel_file')[0].files[0].size;
        if (fileSize > 10 * 1024 * 1024) {
            showAlert('danger', 'Ukuran file terlalu besar! Maksimal 10MB');
            return;
        }

        // Show progress
        $('#progressArea').show();
        $('#btnImport').prop('disabled', true);
        $('#alertArea').html('');
        $('#resultCard').hide();

        $.ajax({
            url: '../api/import.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                $('#progressArea').hide();
                $('#btnImport').prop('disabled', false);

                if (response.success) {
                    showAlert('success', response.message);
                    
                    // Show result
                    let resultHtml = '<p><strong>' + response.message + '</strong></p>';
                    if (response.errors && response.errors.length > 0) {
                        resultHtml += '<hr><h6>Detail Error:</h6><ul>';
                        response.errors.forEach(function(error) {
                            resultHtml += '<li class="text-danger">' + error + '</li>';
                        });
                        resultHtml += '</ul>';
                    }
                    $('#resultContent').html(resultHtml);
                    $('#resultCard').show();
                    
                    // Reset form
                    $('#importForm')[0].reset();
                    $('.custom-file-label').html('Pilih file...');
                } else {
                    showAlert('danger', response.message);
                }
            },
            error: function(xhr, status, error) {
                $('#progressArea').hide();
                $('#btnImport').prop('disabled', false);
                
                console.log('Response Text:', xhr.responseText);
                
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.message) {
                        showAlert('danger', response.message);
                    } else {
                        showAlert('danger', 'Terjadi kesalahan saat import data');
                    }
                } catch(e) {
                    showAlert('danger', 'Terjadi kesalahan: Server mengembalikan response yang tidak valid. Silakan cek console untuk detail.');
                    console.error('Raw response:', xhr.responseText);
                }
            }
        });
    });

    function loadTables() {
        $.ajax({
            url: '../api/get_tables.php',
            type: 'GET',
            success: function(response) {
                if (response.success && response.tables) {
                    let options = '<option value="">-- Pilih Tabel --</option>';
                    response.tables.forEach(function(table) {
                        options += '<option value="' + table + '">' + table + '</option>';
                    });
                    $('#table_name').html(options);
                }
            },
            error: function() {
                showAlert('warning', 'Gagal memuat daftar tabel');
            }
        });
    }

    function showAlert(type, message) {
        let alertHtml = '<div class="alert alert-' + type + ' alert-dismissible fade show" role="alert">' +
                        message +
                        '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                        '<span aria-hidden="true">&times;</span>' +
                        '</button>' +
                        '</div>';
        $('#alertArea').html(alertHtml);
    }
});
</script>

<?php
require_once '../navbar/footer.php';
?>