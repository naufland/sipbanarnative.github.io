<?php
// File: dokumen.php
require_once 'config.php';
require_once 'notion_api.php';
require_once 'categories.php';

// Inisialisasi Notion API
$notionAPI = new NotionAPI();

// Ambil daftar kategori
$availableCategories = getCategories();

$uploadDir = UPLOAD_DIR;
$allowedTypes = ALLOWED_MIME_TYPES;
$allowedExtensions = ALLOWED_EXTENSIONS;
$maxFileSize = MAX_FILE_SIZE;

// Buat folder uploads jika belum ada
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Proses Upload
$uploadMessage = '';
$uploadStatus = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document'])) {
    $file = $_FILES['document'];
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileError = $file['error'];
    
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    if ($fileError === 0) {
        if (in_array($fileExt, $allowedExtensions)) {
            if ($fileSize <= $maxFileSize) {
                $newFileName = uniqid('doc_', true) . '.' . $fileExt;
                $fileDestination = $uploadDir . $newFileName;
                
                if (move_uploaded_file($fileTmpName, $fileDestination)) {
                    $metadata = [
                        'original_name' => $fileName,
                        'stored_name' => $newFileName,
                        'size' => $fileSize,
                        'type' => $fileExt,
                        'upload_date' => date('Y-m-d H:i:s'),
                        'uploader' => $_POST['uploader'] ?? 'Anonymous',
                        'category' => $_POST['kategori'] ?? 'Dokumen strategi'
                    ];
                    
                    $metadataFile = $uploadDir . 'metadata.json';
                    $allMetadata = [];
                    
                    if (file_exists($metadataFile)) {
                        $allMetadata = json_decode(file_get_contents($metadataFile), true) ?? [];
                    }
                    
                    $allMetadata[$newFileName] = $metadata;
                    file_put_contents($metadataFile, json_encode($allMetadata, JSON_PRETTY_PRINT));
                    
                    $notionResult = $notionAPI->saveDocument($metadata);
                    
                    if ($notionResult['success']) {
                        $metadata['notion_page_id'] = $notionResult['data']['id'] ?? null;
                        $allMetadata[$newFileName] = $metadata;
                        file_put_contents($metadataFile, json_encode($allMetadata, JSON_PRETTY_PRINT));
                        
                        $uploadMessage = 'Dokumen berhasil diupload!';
                        $uploadStatus = 'success';
                    } else {
                        $uploadMessage = 'Dokumen tersimpan lokal, gagal sync ke Notion';
                        $uploadStatus = 'warning';
                    }
                } else {
                    $uploadMessage = 'Gagal mengupload dokumen!';
                    $uploadStatus = 'error';
                }
            } else {
                $uploadMessage = 'Ukuran file terlalu besar! Max ' . ($maxFileSize / 1024 / 1024) . 'MB';
                $uploadStatus = 'error';
            }
        } else {
            $uploadMessage = 'Tipe file tidak diizinkan!';
            $uploadStatus = 'error';
        }
    } else {
        $uploadMessage = 'Terjadi kesalahan saat upload!';
        $uploadStatus = 'error';
    }
}

// Proses Delete
if (isset($_GET['delete'])) {
    $fileToDelete = basename($_GET['delete']);
    $filePath = $uploadDir . $fileToDelete;
    
    if (file_exists($filePath)) {
        unlink($filePath);
        
        $metadataFile = $uploadDir . 'metadata.json';
        if (file_exists($metadataFile)) {
            $allMetadata = json_decode(file_get_contents($metadataFile), true) ?? [];
            if (isset($allMetadata[$fileToDelete]['notion_page_id'])) {
                $pageId = $allMetadata[$fileToDelete]['notion_page_id'];
                $notionAPI->archiveDocument($pageId);
            }
            unset($allMetadata[$fileToDelete]);
            file_put_contents($metadataFile, json_encode($allMetadata, JSON_PRETTY_PRINT));
        }
        
        $uploadMessage = 'Dokumen berhasil dihapus!';
        $uploadStatus = 'success';
    }
}

// Ambil daftar dokumen
$documents = [];
$metadataFile = $uploadDir . 'metadata.json';

if (file_exists($metadataFile)) {
    $allMetadata = json_decode(file_get_contents($metadataFile), true) ?? [];
    
    foreach ($allMetadata as $storedName => $metadata) {
        if (file_exists($uploadDir . $storedName)) {
            $documents[] = $metadata;
        }
    }
    
    usort($documents, function($a, $b) {
        return strtotime($b['upload_date']) - strtotime($a['upload_date']);
    });
}

$totalDocuments = count($documents);
$page_title = "Penyimpanan Dokumen";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            color: #2c3e50;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        /* Header Minimal */
        .header {
            text-align: center;
            margin-bottom: 50px;
        }

        .header h1 {
            font-size: 32px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 8px;
        }

        .header p {
            color: #6c757d;
            font-size: 15px;
        }

        /* Alert Minimal */
        .alert {
            padding: 14px 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
        }

        .alert-warning {
            background: #fff3cd;
            color: #856404;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
        }

        /* Upload Card */
        .upload-card {
            background: white;
            border-radius: 12px;
            padding: 40px;
            margin-bottom: 40px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .upload-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 25px;
            color: #1a1a1a;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-size: 13px;
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
        }

        .form-group input,
        .form-group select {
            padding: 11px 14px;
            border: 1.5px solid #dee2e6;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.2s;
            background: #fff;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #4a90e2;
        }

        .file-input-wrapper {
            position: relative;
        }

        .file-input-wrapper input[type="file"] {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-input-display {
            padding: 11px 14px;
            border: 1.5px dashed #dee2e6;
            border-radius: 6px;
            text-align: center;
            color: #6c757d;
            cursor: pointer;
            transition: all 0.2s;
        }

        .file-input-display:hover {
            border-color: #4a90e2;
            color: #4a90e2;
        }

        .file-input-display.active {
            border-color: #28a745;
            color: #28a745;
            border-style: solid;
        }

        .btn-upload {
            background: #4a90e2;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 10px;
        }

        .btn-upload:hover {
            background: #357abd;
        }

        /* Documents Section */
        .docs-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .docs-title {
            font-size: 18px;
            font-weight: 600;
            color: #1a1a1a;
        }

        .docs-count {
            font-size: 13px;
            color: #6c757d;
            background: #f1f3f5;
            padding: 6px 12px;
            border-radius: 20px;
        }

        /* Document List - Minimal Grid */
        .docs-grid {
            display: grid;
            gap: 12px;
        }

        .doc-item {
            background: white;
            border-radius: 8px;
            padding: 20px;
            display: grid;
            grid-template-columns: auto 1fr auto auto;
            gap: 16px;
            align-items: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            transition: all 0.2s;
        }

        .doc-item:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        .doc-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: white;
        }

        .doc-icon.pdf { background: #e74c3c; }
        .doc-icon.doc, .doc-icon.docx { background: #3498db; }

        .doc-info {
            min-width: 0;
        }

        .doc-name {
            font-weight: 600;
            font-size: 14px;
            color: #1a1a1a;
            margin-bottom: 4px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .doc-meta {
            font-size: 12px;
            color: #868e96;
            display: flex;
            gap: 12px;
        }

        .doc-category {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            background: #e9ecef;
            color: #495057;
        }

        .doc-actions {
            display: flex;
            gap: 8px;
        }

        .btn-icon {
            width: 36px;
            height: 36px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-download {
            background: #e8f5e9;
            color: #27ae60;
        }

        .btn-download:hover {
            background: #27ae60;
            color: white;
        }

        .btn-delete {
            background: #ffebee;
            color: #e74c3c;
        }

        .btn-delete:hover {
            background: #e74c3c;
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #868e96;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.3;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .doc-item {
                grid-template-columns: auto 1fr;
                gap: 12px;
            }

            .doc-category {
                grid-column: 1 / -1;
            }

            .doc-actions {
                grid-column: 1 / -1;
                justify-content: stretch;
            }

            .btn-icon {
                flex: 1;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><?= $page_title ?></h1>
            <p>Upload dan kelola dokumen dengan mudah</p>
        </div>

        <!-- Alert -->
        <?php if ($uploadMessage): ?>
            <div class="alert alert-<?= $uploadStatus ?>">
                <i class="fas fa-<?= $uploadStatus === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                <?= $uploadMessage ?>
            </div>
        <?php endif; ?>

        <!-- Upload Card -->
        <div class="upload-card">
            <div class="upload-title">Upload Dokumen Baru</div>
            <form method="POST" enctype="multipart/form-data" id="uploadForm">
                <div class="form-row">
                    <div class="form-group">
                        <label>Nama Pengunggah</label>
                        <input type="text" name="uploader" placeholder="Masukkan nama Anda" required>
                    </div>

                    <div class="form-group">
                        <label>Kategori</label>
                        <select name="kategori" required>
                            <option value="">Pilih kategori</option>
                            <?php foreach ($availableCategories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Pilih File (PDF, DOC, DOCX - Max <?= $maxFileSize / 1024 / 1024 ?>MB)</label>
                    <div class="file-input-wrapper">
                        <input type="file" name="document" accept=".pdf,.doc,.docx" required id="fileInput">
                        <div class="file-input-display" id="fileDisplay">
                            <i class="fas fa-cloud-upload-alt"></i> Klik atau drag file kesini
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-upload">
                    <i class="fas fa-upload"></i> Upload Dokumen
                </button>
            </form>
        </div>

        <!-- Documents List -->
        <div class="docs-header">
            <div class="docs-title">Dokumen Tersimpan</div>
            <div class="docs-count"><?= $totalDocuments ?> dokumen</div>
        </div>

        <div class="docs-grid">
            <?php if (count($documents) > 0): ?>
                <?php foreach ($documents as $doc): ?>
                    <div class="doc-item">
                        <div class="doc-icon <?= $doc['type'] ?>">
                            <i class="fas fa-file-<?= $doc['type'] === 'pdf' ? 'pdf' : 'word' ?>"></i>
                        </div>
                        
                        <div class="doc-info">
                            <div class="doc-name" title="<?= htmlspecialchars($doc['original_name']) ?>">
                                <?= htmlspecialchars($doc['original_name']) ?>
                            </div>
                            <div class="doc-meta">
                                <span><?= htmlspecialchars($doc['uploader']) ?></span>
                                <span>•</span>
                                <span><?= number_format($doc['size'] / 1024, 0) ?> KB</span>
                                <span>•</span>
                                <span><?= date('d/m/Y', strtotime($doc['upload_date'])) ?></span>
                            </div>
                        </div>

                        <div class="doc-category">
                            <?= htmlspecialchars($doc['category'] ?? 'Tanpa kategori') ?>
                        </div>

                        <div class="doc-actions">
                            <a href="download.php?file=<?= urlencode($doc['stored_name']) ?>" 
                               class="btn-icon btn-download" 
                               title="Download">
                                <i class="fas fa-download"></i>
                            </a>
                            <a href="?delete=<?= urlencode($doc['stored_name']) ?>" 
                               class="btn-icon btn-delete"
                               onclick="return confirm('Yakin ingin menghapus dokumen ini?')"
                               title="Hapus">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-folder-open"></i>
                    <p>Belum ada dokumen. Upload dokumen pertama Anda!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // File input handler
        const fileInput = document.getElementById('fileInput');
        const fileDisplay = document.getElementById('fileDisplay');

        fileInput.addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name;
            if (fileName) {
                fileDisplay.innerHTML = `<i class="fas fa-check-circle"></i> ${fileName}`;
                fileDisplay.classList.add('active');
            }
        });

        // Drag and drop
        fileDisplay.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.borderColor = '#4a90e2';
        });

        fileDisplay.addEventListener('dragleave', function(e) {
            this.style.borderColor = '#dee2e6';
        });

        // Auto hide alert
        setTimeout(function() {
            const alert = document.querySelector('.alert');
            if (alert) {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.3s';
                setTimeout(() => alert.remove(), 300);
            }
        }, 4000);
    </script>
</body>
</html>