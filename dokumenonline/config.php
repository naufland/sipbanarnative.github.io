<?php
// File: config.php

// ============================================
// NOTION API CONFIGURATION
// ============================================

// API Key Anda (Sudah saya masukkan)
define('NOTION_API_KEY', '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');

// Database ID Anda (Sudah saya masukkan)
define('NOTION_DATABASE_ID', '2b997e8e9c198041a02ccef584546463');

define('NOTION_API_VERSION', '2022-06-28');
define('NOTION_API_URL', 'https://api.notion.com/v1');

// ============================================
// UPLOAD CONFIGURATION
// ============================================
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB

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
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0);

// ============================================
// VALIDATION FUNCTIONS
// ============================================

function validateNotionConfig() {
    if (empty(NOTION_API_KEY) || strpos(NOTION_API_KEY, 'secret_') === false && strpos(NOTION_API_KEY, 'ntn_') === false) {
        // Saya tambahkan pengecekan 'ntn_' karena key Anda berawalan ntn_
        return [
            'valid' => false,
            'message' => 'Format NOTION_API_KEY sepertinya salah. Cek kembali.'
        ];
    }
    
    if (strlen(NOTION_DATABASE_ID) !== 32) {
        return [
            'valid' => false,
            'message' => 'NOTION_DATABASE_ID tidak valid! Harus 32 karakter (tanpa tanda tanya).'
        ];
    }
    
    return ['valid' => true, 'message' => 'Konfigurasi Notion valid'];
}

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
            'message' => 'PHP Extension kurang: ' . implode(', ', $missing)
        ];
    }
    
    return ['valid' => true, 'message' => 'Requirements OK'];
}

function checkUploadDirectory() {
    if (!file_exists(UPLOAD_DIR)) {
        if (!@mkdir(UPLOAD_DIR, 0755, true)) {
            return [
                'valid' => false,
                'message' => 'Gagal membuat folder uploads.'
            ];
        }
    }
    
    if (!is_writable(UPLOAD_DIR)) {
        return [
            'valid' => false,
            'message' => 'Folder uploads tidak writable (chmod 755).'
        ];
    }
    
    return ['valid' => true, 'message' => 'Folder OK'];
}

// ============================================
// AUTO CHECK (Diaktifkan untuk memastikan aman)
// ============================================
// Saya aktifkan pengecekan folder otomatis agar tidak error saat upload pertama
checkUploadDirectory();

?>