<?php
// File: config.php
// Konfigurasi API Notion - JANGAN UPLOAD KE GIT!

// ============================================
// NOTION API CONFIGURATION
// ============================================
// Cara mendapatkan API Key dan Database ID:
// 1. Buka https://www.notion.so/my-integrations
// 2. Klik "+ New integration"
// 3. Copy "Internal Integration Token" (secret_xxxxx...)
// 4. Buat database di Notion dengan kolom:
//    - Nama dokumen (Title)
//    - Kategori (Select)
//    - Dibuat oleh (Rich Text)
//    - Waktu dibuat (Date)
//    - Terakhir diedit oleh (Rich Text)
//    - Waktu terakhir diperbarui (Date)
// 5. Share database ke integration Anda
// 6. Copy Database ID dari URL (32 karakter setelah nama workspace)

define('NOTION_API_KEY', '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');
define('NOTION_DATABASE_ID', '2b997e8e9c19801d93b7c81cc67c1595');
define('NOTION_API_VERSION', '2022-06-28');
define('NOTION_API_URL', 'https://api.notion.com/v1');

// ============================================
// UPLOAD CONFIGURATION
// ============================================
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB (ubah sesuai kebutuhan)

// Ekstensi file yang diizinkan
define('ALLOWED_EXTENSIONS', ['pdf', 'doc', 'docx']);

// MIME types yang diizinkan
define('ALLOWED_MIME_TYPES', [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
]);

// ============================================
// APPLICATION SETTINGS
// ============================================
define('APP_NAME', 'Sistem Penyimpanan Dokumen');
define('APP_TIMEZONE', 'Asia/Jakarta');
date_default_timezone_set(APP_TIMEZONE);

// ============================================
// ERROR REPORTING
// ============================================
// Set ke true untuk development, false untuk production
define('DEBUG_MODE', true);

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/error.log');
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// ============================================
// SECURITY SETTINGS
// ============================================
// Session configuration (jika diperlukan di masa depan)
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set ke 1 jika menggunakan HTTPS

// ============================================
// VALIDATION FUNCTIONS
// ============================================

/**
 * Validasi apakah Notion API Key sudah diisi
 */
function validateNotionConfig() {
    if (NOTION_API_KEY === 'secret_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx') {
        return [
            'valid' => false,
            'message' => 'NOTION_API_KEY belum diisi! Edit file config.php dan masukkan API Key Anda.'
        ];
    }
    
    if (NOTION_DATABASE_ID === 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx') {
        return [
            'valid' => false,
            'message' => 'NOTION_DATABASE_ID belum diisi! Edit file config.php dan masukkan Database ID Anda.'
        ];
    }
    
    if (strlen(NOTION_DATABASE_ID) !== 32) {
        return [
            'valid' => false,
            'message' => 'NOTION_DATABASE_ID tidak valid! Harus 32 karakter.'
        ];
    }
    
    return ['valid' => true, 'message' => 'Konfigurasi Notion valid'];
}

/**
 * Cek apakah PHP extension yang dibutuhkan sudah terinstall
 */
function checkRequirements() {
    $requirements = [
        'curl' => extension_loaded('curl'),
        'json' => extension_loaded('json'),
        'fileinfo' => extension_loaded('fileinfo')
    ];
    
    $missing = [];
    foreach ($requirements as $ext => $loaded) {
        if (!$loaded) {
            $missing[] = $ext;
        }
    }
    
    if (!empty($missing)) {
        return [
            'valid' => false,
            'message' => 'PHP Extension yang dibutuhkan belum terinstall: ' . implode(', ', $missing)
        ];
    }
    
    return ['valid' => true, 'message' => 'Semua requirement terpenuhi'];
}

/**
 * Cek permission folder uploads
 */
function checkUploadDirectory() {
    if (!file_exists(UPLOAD_DIR)) {
        if (!@mkdir(UPLOAD_DIR, 0755, true)) {
            return [
                'valid' => false,
                'message' => 'Tidak bisa membuat folder uploads! Cek permission.'
            ];
        }
    }
    
    if (!is_writable(UPLOAD_DIR)) {
        return [
            'valid' => false,
            'message' => 'Folder uploads tidak bisa ditulis! Jalankan: chmod 755 uploads/'
        ];
    }
    
    return ['valid' => true, 'message' => 'Folder uploads siap digunakan'];
}

// ============================================
// AUTO CHECK (Optional)
// ============================================
// Uncomment baris di bawah untuk auto-check saat config dimuat
/*
$configCheck = validateNotionConfig();
if (!$configCheck['valid']) {
    die('<h2>Configuration Error</h2><p>' . $configCheck['message'] . '</p>');
}

$reqCheck = checkRequirements();
if (!$reqCheck['valid']) {
    die('<h2>System Requirements Error</h2><p>' . $reqCheck['message'] . '</p>');
}
*/
?>