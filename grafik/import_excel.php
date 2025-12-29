<?php
require_once '../navbar/header_login.php';
?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="../../js/submenu.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet"></style>

<style>
    /* Font Poppins untuk seluruh halaman */
    body {
        font-family: 'Poppins', 'Inter', sans-serif;
        background-color: #f8f9fa;
    }

    /* Card Styling */
    .card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
    }

    /* Header dengan Gradien Merah */
    .card-header-gradient {
        background: linear-gradient(135deg, #e53e3e 0%, #c53030 100%) !important;
        border-bottom: none;
        padding: 1.5rem;
    }

    .card-header-gradient h4 {
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin: 0;
    }

    .card-header-gradient h4 i {
        font-size: 1.5rem;
    }

    /* Form Controls */
    .form-control, .custom-select {
        border-radius: 8px;
        border: 1px solid #ced4da;
        padding: 0.75rem 1rem;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        font-size: 0.95rem;
    }

    .form-control:focus, .custom-select:focus {
        border-color: #f56565;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.2);
        outline: none;
    }

    .form-group label {
        font-weight: 500;
        margin-bottom: 0.5rem;
        color: #4a5568;
        font-size: 0.9rem;
    }

    .form-text {
        font-size: 0.8rem;
        color: #6c757d;
    }

    /* Custom File Input */
    .custom-file-label {
        background-color: #fff;
        border-radius: 8px;
        border: 1px solid #ced4da;
        padding: 0.75rem 1rem;
        height: calc(1.5em + 1.5rem + 2px);
        display: flex;
        align-items: center;
        color: #6c757d;
        transition: all 0.15s ease;
    }

    .custom-file-label::after {
        content: "Pilih File";
        background-color: #e2e8f0;
        border-left: 1px solid #ced4da;
        border-radius: 0 8px 8px 0;
        padding: 0.75rem 1rem;
        height: 100%;
        color: #4a5568;
        display: flex;
        align-items: center;
        font-weight: 500;
    }

    .custom-file-input:focus ~ .custom-file-label {
        border-color: #f56565;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.2);
    }

    .custom-file-label.selected {
        color: #2d3748;
        font-weight: 500;
    }

    /* Buttons */
    .btn {
        border-radius: 8px;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        transition: all 0.2s ease;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        font-size: 0.95rem;
    }

    .btn-primary {
        background-color: #dc3545;
        border-color: #dc3545;
    }

    .btn-primary:hover:not(:disabled) {
        background-color: #c82333;
        border-color: #bd2130;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
    }

    .btn-secondary {
        background-color: #6c757d;
        border-color: #6c757d;
    }

    .btn-secondary:hover {
        background-color: #5a6268;
        border-color: #545b62;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(108, 117, 125, 0.3);
    }

    .btn:disabled {
        opacity: 0.65;
        transform: none;
        box-shadow: none;
        cursor: not-allowed;
    }

    /* Alert Info */
    .alert-info {
        background-color: #fff5f5;
        border-color: #fed7d7;
        color: #742a2a;
        border-radius: 8px;
        border-left: 5px solid #e53e3e;
    }

    .alert-info .alert-heading {
        color: #c53030;
        font-weight: 600;
        font-size: 0.95rem;
    }

    .alert-info ul {
        margin-bottom: 0;
        padding-left: 1.5rem;
    }

    .alert-info li {
        margin-bottom: 0.25rem;
    }

    /* Progress Bar */
    .progress {
        height: 1.5rem;
        border-radius: 8px;
        background-color: #e9ecef;
        overflow: hidden;
    }

    .progress-bar {
        background-color: #dc3545;
        font-weight: 500;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.9rem;
    }

    /* Result Card */
    #resultCard .card-header {
        background: linear-gradient(135deg, #38a169 0%, #2f855a 100%) !important;
    }

    #resultCard .card-body ul {
        background-color: #f7fafc;
        border: 1px solid #e2e8f0;
        padding: 1rem;
        border-radius: 6px;
        max-height: 300px;
        overflow-y: auto;
    }

    #resultCard .card-body li {
        margin-bottom: 0.5rem;
    }

    /* Scrollbar Styling */
    #resultCard .card-body ul::-webkit-scrollbar {
        width: 8px;
    }

    #resultCard .card-body ul::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }

    #resultCard .card-body ul::-webkit-scrollbar-thumb {
        background: #cbd5e0;
        border-radius: 4px;
    }

    #resultCard .card-body ul::-webkit-scrollbar-thumb:hover {
        background: #a0aec0;
    }

    /* Validation Feedback */
    .invalid-feedback {
        font-size: 0.85rem;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .btn {
            width: 100%;
            margin-top: 0.5rem;
        }

        .text-right {
            text-align: center !important;
        }

        .card-body {
            padding: 1.5rem !important;
        }

        .col-md-6, .col-md-3 {
            margin-bottom: 1rem;
        }
    }

    /* Animation */
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    #resultCard {
        animation: fadeIn 0.3s ease;
    }
</style>

<!-- Main Container -->
<div class="container mt-4 mb-5">
    <div class="row">
        <div class="col-md-12">
            <!-- Form Card -->
            <div class="card">
                <div class="card-header card-header-gradient text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-file-excel"></i> Import Data Excel
                    </h4>
                </div>
                <div class="card-body p-4">
                    <form id="importForm" enctype="multipart/form-data" class="needs-validation" novalidate>
                        <!-- Row 1: Pilih Tabel, Tahun, Bulan -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="table_name">Pilih Tabel <span class="text-danger">*</span></label>
                                    <select class="form-control custom-select" id="table_name" name="table_name" required>
                                        <option value="">-- Memuat Tabel --</option>
                                    </select>
                                    <small class="form-text text-muted">Pilih tabel tujuan untuk import data</small>
                                    <div class="invalid-feedback">
                                        Silakan pilih tabel tujuan.
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="tahun">Tahun</label>
                                    <select class="form-control custom-select" id="tahun" name="tahun">
                                        <option value="">-- Abaikan --</option>
                                        <?php
                                        $currentYear = date('Y');
                                        for ($i = $currentYear - 5; $i <= $currentYear + 1; $i++) {
                                            $selected = ($i == $currentYear) ? 'selected' : '';
                                            echo "<option value='$i' $selected>$i</option>";
                                        }
                                        ?>
                                    </select>
                                    <small class="form-text text-muted">Otomatis diisi jika dipilih</small>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="bulan">Bulan</label>
                                    <select class="form-control custom-select" id="bulan" name="bulan">
                                        <option value="">-- Abaikan --</option>
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
                                    <small class="form-text text-muted">Otomatis diisi jika dipilih</small>
                                </div>
                            </div>
                        </div>

                        <!-- Row 2: Perubahan -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="perubahan">Perubahan</label>
                                    <select class="form-control custom-select" id="perubahan" name="perubahan">
                                        <option value="">-- Abaikan --</option>
                                        <option value="Perubahan">Perubahan</option>
                                        <option value="Tidak">Tidak</option>
                                    </select>
                                    <small class="form-text text-muted">Otomatis diisi jika dipilih</small>
                                </div>
                            </div>
                        </div>

                        <!-- File Upload -->
                        <div class="form-group mb-4">
                            <label for="excel_file">File Excel <span class="text-danger">*</span></label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="excel_file" name="excel_file" accept=".xlsx,.xls,.csv" required>
                                <label class="custom-file-label" for="excel_file">Pilih file...</label>
                                <div class="invalid-feedback">
                                    Silakan pilih file Excel atau CSV yang valid (.xlsx, .xls, .csv maks 10MB).
                                </div>
                            </div>
                            <small class="form-text text-muted">
                                Format: .xlsx, .xls, .csv (Maksimal 10MB)
                            </small>
                        </div>

                        <!-- Petunjuk Import -->
                        <div class="alert alert-info py-3 mb-4">
                            <h6 class="alert-heading mb-2"><i class="fas fa-info-circle"></i> Petunjuk Import:</h6>
                            <ul class="mb-0 pl-3" style="font-size: 0.85rem;">
                                <li>Header Excel (baris 1) harus sesuai nama kolom database (tidak case-sensitive, spasi/simbol diabaikan).</li>
                                <li>Data dimulai dari baris kedua.</li>
                                <li>Kolom Excel yang tidak cocok akan diabaikan.</li>
                                <li>Jika tabel memiliki Primary Key (misal: Kode_Paket), data yang sudah ada akan di-update, bukan diduplikasi.</li>
                                <li>Tahun/Bulan/Perubahan di form ini akan menimpa data dari Excel jika kolomnya ada.</li>
                            </ul>
                        </div>

                        <!-- Progress Bar -->
                        <div id="progressArea" class="mb-3" style="display: none;">
                            <div class="progress">
                                <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%">
                                    <span id="progressText">Memulai proses import...</span>
                                </div>
                            </div>
                        </div>

                        <!-- Alert Area -->
                        <div id="alertArea" class="mb-3"></div>

                        <!-- Action Buttons -->
                        <div class="text-right">
                            <button type="submit" class="btn btn-primary" id="btnImport" disabled>
                                <i class="fas fa-upload"></i> <span id="btnImportText">Import Data</span>
                            </button>
                            <button type="reset" class="btn btn-secondary" id="btnReset">
                                <i class="fas fa-redo"></i> Reset
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Result Card -->
            <div class="card mt-4" id="resultCard" style="display: none;">
                <div class="card-header card-header-gradient text-white">
                    <h5 class="mb-0 d-flex align-items-center gap-2">
                        <i class="fas fa-check-circle"></i> Hasil Import
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div id="resultContent"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Update Progress Function
function updateProgress(text) {
    $('#progressText').text(text);
    const match = text.match(/(\d+)%/);
    if (match) {
        $('#progressBar').css('width', match[1] + '%');
    } else {
        $('#progressBar').css('width', '100%');
    }
}

// SweetAlert Function
function showSweetAlert(icon, title, text = '') {
    Swal.fire({
        icon: icon,
        title: title,
        text: text,
        timer: icon === 'success' ? 3000 : 5000,
        timerProgressBar: true,
        showConfirmButton: false,
        customClass: {
            popup: 'swal2-popup-custom'
        }
    });
}

$(document).ready(function() {
    // Load Tables on Page Load
    loadTables();

    // File Input Change Handler
    $('.custom-file-input').on('change', function(e) {
        let file = e.target.files[0];
        if (!file) {
            $(this).siblings('.custom-file-label').removeClass("selected").html('Pilih file...');
            $(this).removeClass('is-valid').addClass('is-invalid');
            $('#btnImport').prop('disabled', true);
            return;
        }

        let fileName = file.name;
        let fileExtension = fileName.split('.').pop().toLowerCase();
        let allowedExtensions = ['xlsx', 'xls', 'csv'];

        if ($.inArray(fileExtension, allowedExtensions) == -1) {
            showSweetAlert('error', 'Format File Salah!', 'Hanya file .xlsx, .xls, atau .csv yang diizinkan.');
            $(this).val(null);
            $(this).siblings('.custom-file-label').removeClass("selected").html('Pilih file...');
            $(this).removeClass('is-valid').addClass('is-invalid');
            $('#btnImport').prop('disabled', true);
            return;
        }

        if (file.size > 10 * 1024 * 1024) { // 10MB
            showSweetAlert('error', 'Ukuran File Terlalu Besar!', 'Ukuran file maksimal adalah 10MB.');
            $(this).val(null);
            $(this).siblings('.custom-file-label').removeClass("selected").html('Pilih file...');
            $(this).removeClass('is-valid').addClass('is-invalid');
            $('#btnImport').prop('disabled', true);
            return;
        }

        $(this).siblings('.custom-file-label').addClass("selected").html(fileName);
        $(this).removeClass('is-invalid').addClass('is-valid');
        $('#btnImport').prop('disabled', false);
    });

    // Form Submit Handler
    $('#importForm').on('submit', function(e) {
        e.preventDefault();
        e.stopPropagation();

        $(this).addClass('was-validated');

        if (this.checkValidity() === false) {
            if ($('#excel_file').hasClass('is-invalid') || !$('#excel_file').val()) {
                showSweetAlert('warning', 'File Belum Dipilih', 'Silakan pilih file Excel atau CSV yang valid.');
            } else if ($('#table_name').val() === '') {
                showSweetAlert('warning', 'Tabel Belum Dipilih', 'Silakan pilih tabel tujuan.');
            }
            return;
        }

        let formData = new FormData(this);

        updateProgress('Mengunggah file...');
        $('#progressArea').show();
        $('#btnImport').prop('disabled', true).find('#btnImportText').text('Mengimport...');
        $('#btnReset').prop('disabled', true);
        $('#alertArea').html('');
        $('#resultCard').hide();

        $.ajax({
            url: '../api/import.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener("progress", function(evt) {
                    if (evt.lengthComputable) {
                        var percentComplete = Math.round((evt.loaded / evt.total) * 100);
                        updateProgress(`Mengunggah file... ${percentComplete}%`);
                    }
                }, false);
                return xhr;
            },
            beforeSend: function() {
                updateProgress('Memproses file di server...');
            },
            success: function(response) {
                $('#progressArea').hide();
                $('#btnImport').prop('disabled', false).find('#btnImportText').text('Import Data');
                $('#btnReset').prop('disabled', false);

                if (response.success) {
                    let successMsg = `Berhasil memproses ${response.total} baris data.`;
                    let details = [];
                    if (response.inserted > 0) details.push(`${response.inserted} data baru ditambahkan`);
                    if (response.updated > 0) details.push(`${response.updated} data diperbarui`);
                    if (details.length > 0) successMsg += ` (${details.join(', ')})`;

                    showSweetAlert('success', 'Import Selesai!', successMsg);

                    let resultHtml = `<p><strong>${successMsg}</strong></p>`;
                    if (response.message && response.message.includes('Header Excel')) {
                        resultHtml += `<p class="text-muted small">${response.message.split('. ')[1]}</p>`;
                    }

                    if (response.errors && response.errors.length > 0) {
                        resultHtml += `<hr><h6><i class="fas fa-exclamation-triangle text-danger"></i> Detail Error (${response.errors.length}):</h6><ul class="list-unstyled" style="font-size: 0.8rem;">`;
                        response.errors.forEach(function(error) {
                            resultHtml += `<li class="text-danger mb-1"><small>${error}</small></li>`;
                        });
                        resultHtml += '</ul>';
                    } else {
                        resultHtml += '<p class="text-success mt-3"><i class="fas fa-check-circle"></i> Tidak ada error ditemukan.</p>';
                    }
                    $('#resultContent').html(resultHtml);
                    $('#resultCard').show();

                    $('#importForm')[0].reset();
                    $('#importForm').removeClass('was-validated');
                    $('.custom-file-input').removeClass('is-valid is-invalid').val(null);
                    $('.custom-file-label').removeClass("selected").html('Pilih file...');
                    $('#btnImport').prop('disabled', true);

                } else {
                    showSweetAlert('error', 'Import Gagal!', response.message || 'Terjadi kesalahan yang tidak diketahui.');
                    $('#resultContent').html(`<p class="text-danger"><strong>Error:</strong> ${response.message || 'Terjadi kesalahan yang tidak diketahui.'}</p>`);
                    $('#resultCard').show().find('.card-header').css('background', 'linear-gradient(135deg, #f56565 0%, #e53e3e 100%)');
                }
            },
            error: function(xhr, status, error) {
                $('#progressArea').hide();
                $('#btnImport').prop('disabled', false).find('#btnImportText').text('Import Data');
                $('#btnReset').prop('disabled', false);

                console.error('AJAX Error:', status, error);
                console.error('Response Text:', xhr.responseText);
                let errorMsg = 'Terjadi kesalahan saat menghubungi server.';

                try {
                    var responseJson = JSON.parse(xhr.responseText);
                    if (responseJson.message) {
                        errorMsg = responseJson.message;
                    }
                } catch(e) {
                    if(xhr.responseText.includes('<')) {
                        errorMsg = 'Terjadi error internal server. Periksa log PHP.';
                        console.error("Server returned HTML, possibly a PHP error.");
                    } else if (xhr.responseText) {
                        errorMsg = `Server merespon: ${xhr.responseText.substring(0, 100)}...`;
                    }
                }
                showSweetAlert('error', 'Import Gagal!', errorMsg);
                $('#resultContent').html(`<p class="text-danger"><strong>Error:</strong> ${errorMsg}</p><p class="small text-muted">Cek console browser (F12) untuk detail teknis.</p>`);
                $('#resultCard').show().find('.card-header').css('background', 'linear-gradient(135deg, #f56565 0%, #e53e3e 100%)');
            }
        });
    });

    // Reset Button Handler
    $('#btnReset').on('click', function() {
        $('#importForm').removeClass('was-validated');
        $('.custom-file-input').removeClass('is-valid is-invalid').val(null);
        $('.custom-file-label').removeClass("selected").html('Pilih file...');
        $('#alertArea').html('');
        $('#resultCard').hide();
        $('#btnImport').prop('disabled', true);
        $('#tahun option[value="<?php echo date('Y'); ?>"]').prop('selected', true);
        $('#bulan option[value=""]').prop('selected', true);
        $('#perubahan option[value=""]').prop('selected', true);
    });

    // Load Tables Function
    function loadTables() {
        $.ajax({
            url: '../api/get_tables.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success && response.tables) {
                    let options = '<option value="">-- Pilih Tabel --</option>';
                    response.tables.forEach(function(table) {
                        options += `<option value="${table}">${table}</option>`;
                    });
                    $('#table_name').html(options);
                } else {
                    showSweetAlert('warning', 'Gagal Memuat Tabel', response.message || 'Tidak dapat mengambil daftar tabel.');
                    $('#table_name').html('<option value="">-- Gagal Memuat --</option>');
                }
            },
            error: function(xhr) {
                showSweetAlert('error', 'Error Koneksi', 'Gagal menghubungi server untuk memuat tabel.');
                $('#table_name').html('<option value="">-- Error Koneksi --</option>');
                console.error('Error loading tables:', xhr.responseText);
            }
        });
    }
});
</script>

<?php
require_once '../navbar/footer.php';
?>