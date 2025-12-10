<?php
// /includes/auth.php

// Pastikan session dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Sertakan file koneksi database karena mungkin dibutuhkan
include_once 'db.php'; // Sesuaikan path jika auth.php dipanggil dari direktori berbeda (relatif terhadap pemanggil)
                      // Untuk pemanggilan dari header.php yang ada di /includes/, path ini sudah benar.
                      // Jika dipanggil dari file di /pages/, path db.php perlu '../includes/db.php'

// Fungsi untuk memeriksa apakah pengguna sudah login
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Fungsi untuk mendapatkan ID pengguna yang sedang login
function get_current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

// Fungsi untuk mendapatkan nama lengkap pengguna yang sedang login
function get_current_user_nama() {
    return $_SESSION['nama_lengkap'] ?? null;
}

// Fungsi untuk mendapatkan peran pengguna yang sedang login
function get_current_user_role() {
    return $_SESSION['role'] ?? null;
}

// Fungsi untuk mengharuskan login
// $redirect_url adalah halaman tujuan jika belum login
function require_login($redirect_url = '../pages/login.php') { // Default redirect ke login.php dari dalam folder pages
    if (!is_logged_in()) {
        // Simpan halaman tujuan agar bisa redirect kembali setelah login (opsional)
        // $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        
        header("Location: " . $redirect_url);
        exit;
    }
}

// Fungsi untuk membatasi akses berdasarkan peran
// $allowed_roles adalah array peran yang diizinkan, contoh: ['Admin'] atau ['Admin', 'Kasir']
function restrict_access($allowed_roles = [], $redirect_url = '../pages/dashboard.php') { // Default redirect ke dashboard jika tidak diizinkan
    if (!is_logged_in()) {
        require_login(); // Jika belum login, paksa login dulu
        return;
    }

    $current_role = get_current_user_role();
    if (!in_array($current_role, $allowed_roles)) {
        // Opsi: Tampilkan pesan error atau log
        // $_SESSION['error_message'] = "Anda tidak memiliki hak akses ke halaman ini.";
        header("Location: " . $redirect_url); // Arahkan ke halaman default jika akses ditolak
        exit;
    }
}

// Fungsi untuk menghasilkan token CSRF
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Fungsi untuk memverifikasi token CSRF
function verify_csrf_token($token_from_form) {
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token_from_form)) {
        return false;
    }
    return true;
}

?>