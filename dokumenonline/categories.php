<?php
// File: categories.php
// Kelola daftar kategori dokumen - Bisa dikustomisasi sesuai kebutuhan

/**
 * Daftar kategori dokumen
 * Tambahkan atau edit sesuai kebutuhan organisasi Anda
 */
$categories = [
    'Dokumen strategi',
    'Proposal',
    'Riset pelanggan',
    'Laporan keuangan',
    'Kontrak',
    'SOP',
    'Notulen rapat',
    'Presentasi',
    'Template',
    'Arsip',
    'Surat resmi',
    'Invoice',
    'Penawaran',
    'MOU',
    'Peraturan',
    'Panduan',
    'Laporan tahunan',
    'Feasibility study',
    'Business plan',
    'Marketing material'
];

/**
 * Kategori dengan icon (opsional untuk tampilan)
 */
$categoriesWithIcons = [
    'Dokumen strategi' => 'fa-chess',
    'Proposal' => 'fa-file-invoice',
    'Riset pelanggan' => 'fa-chart-line',
    'Laporan keuangan' => 'fa-money-bill-wave',
    'Kontrak' => 'fa-file-contract',
    'SOP' => 'fa-book',
    'Notulen rapat' => 'fa-clipboard-list',
    'Presentasi' => 'fa-presentation',
    'Template' => 'fa-layer-group',
    'Arsip' => 'fa-archive'
];

/**
 * Warna badge untuk setiap kategori (opsional)
 */
$categoryColors = [
    'Dokumen strategi' => '#e74c3c',
    'Proposal' => '#3498db',
    'Riset pelanggan' => '#9b59b6',
    'Laporan keuangan' => '#27ae60',
    'Kontrak' => '#f39c12',
    'SOP' => '#16a085',
    'Notulen rapat' => '#34495e',
    'Presentasi' => '#e67e22',
    'Template' => '#95a5a6',
    'Arsip' => '#7f8c8d'
];

/**
 * Fungsi helper untuk mendapatkan kategori
 */
function getCategories() {
    global $categories;
    return $categories;
}

/**
 * Fungsi untuk mendapatkan icon kategori
 */
function getCategoryIcon($category) {
    global $categoriesWithIcons;
    return $categoriesWithIcons[$category] ?? 'fa-file';
}

/**
 * Fungsi untuk mendapatkan warna kategori
 */
function getCategoryColor($category) {
    global $categoryColors;
    return $categoryColors[$category] ?? '#6c757d';
}
?>