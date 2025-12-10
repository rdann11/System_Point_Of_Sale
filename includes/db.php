<?php
// /includes/db.php

// Pengaturan database
$db_host = 'localhost';     // Biasanya 'localhost' jika XAMPP/Laragon di mesin yang sama
$db_user = 'root';          // User default MySQL di XAMPP/Laragon biasanya 'root'
$db_pass = '';              // Password default MySQL di XAMPP/Laragon biasanya kosong
$db_name = 'pos'; // Nama database yang akan kita buat nanti

// Membuat koneksi menggunakan MySQLi
$koneksi = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// Cek koneksi
if (!$koneksi) {
    // Jika koneksi gagal, tampilkan pesan error dan hentikan skrip
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Mengatur zona waktu default
date_default_timezone_set('Asia/Jakarta');

// Opsi: Mengatur karakter set ke utf8mb4 untuk dukungan emoji dan karakter internasional (jika diperlukan)
mysqli_set_charset($koneksi, "utf8mb4");

// // Baris ini bisa di-uncomment untuk pengujian awal koneksi
// echo "Koneksi database berhasil!";
// // Setelah pengujian, pastikan baris di atas di-comment atau dihapus agar tidak mengganggu output lain.
?>