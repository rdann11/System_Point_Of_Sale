<?php
// /pos-website/logout.php

// Mulai atau lanjutkan sesi
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Hapus semua variabel sesi
$_SESSION = array();

// 2. Jika menggunakan cookie sesi, hapus juga cookie-nya.
// Ini penting untuk memastikan sesi benar-benar dihancurkan.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Hancurkan sesi di server
session_destroy();

// 4. (Opsional) Siapkan pesan sukses untuk ditampilkan di halaman login
session_start(); // Mulai sesi baru (singkat) hanya untuk membawa pesan
$_SESSION['success_message'] = "Anda telah berhasil logout.";

// 5. Arahkan pengguna ke halaman login
header("Location: pages/login.php");
exit;
?>