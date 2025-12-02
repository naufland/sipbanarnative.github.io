<?php
// File: categories.php

// Tentukan path file penyimpanan kategori
define('CATEGORIES_FILE', __DIR__ . '/categories.json');

/**
 * Mengambil daftar kategori.
 * Jika file belum ada, gunakan default.
 */
function getCategories() {
    // Kategori default awal
    $defaults = ['Umum', 'Pribadi', 'Pekerjaan', 'Kuliah', 'Project'];

    if (file_exists(CATEGORIES_FILE)) {
        $json = file_get_contents(CATEGORIES_FILE);
        $data = json_decode($json, true);
        if (is_array($data) && !empty($data)) {
            return $data;
        }
    }

    // Jika file tidak ada, buat file dengan default
    saveCategories($defaults);
    return $defaults;
}

/**
 * Menyimpan array kategori ke file JSON
 */
function saveCategories($categories) {
    // Pastikan array unik dan urut abjad
    $categories = array_unique($categories);
    sort($categories);
    file_put_contents(CATEGORIES_FILE, json_encode($categories, JSON_PRETTY_PRINT));
}

/**
 * Menambah kategori baru jika belum ada
 */
function addCategory($newCategory) {
    $current = getCategories();
    // Bersihkan input
    $newCategory = trim($newCategory);
    $newCategory = ucfirst($newCategory); // Huruf depan besar

    if (!empty($newCategory) && !in_array($newCategory, $current)) {
        $current[] = $newCategory;
        saveCategories($current);
        return true;
    }
    return false;
}
?>