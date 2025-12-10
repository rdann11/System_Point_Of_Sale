<?php
// /pos-website/index.php

// Mulai session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah pengguna sudah login (misalnya dengan memeriksa variabel session 'user_id')
if (isset($_SESSION['user_id'])) {
    // Jika sudah login, arahkan ke dashboard
    header("Location: pages/dashboard.php");
    exit;
} else {
    // Jika belum login, arahkan ke halaman login
    header("Location: pages/login.php");
    exit;
}
?>