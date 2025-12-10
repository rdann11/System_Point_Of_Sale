<?php
// /includes/header.php

if (file_exists(__DIR__ . '/auth.php')) {
    require_once __DIR__ . '/auth.php';
} else {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    if (!function_exists('require_login')) { function require_login($url = '') { /* do nothing */ } }
    if (!function_exists('get_current_user_nama')) { function get_current_user_nama() { return 'Pengguna'; } }
    if (!function_exists('get_current_user_id')) { function get_current_user_id() { return 0; } }
}

require_login('../pages/login.php'); 

$nama_toko_default = 'Sistem POS';
$nama_toko = $nama_toko_default;
if (isset($koneksi)) { 
    $query_pengaturan_header = mysqli_query($koneksi, "SELECT nama_toko FROM pengaturan_toko LIMIT 1");
    if ($query_pengaturan_header && $row_pengaturan_header = mysqli_fetch_assoc($query_pengaturan_header)) {
        $nama_toko = htmlspecialchars($row_pengaturan_header['nama_toko']);
    }
}

$page_title = isset($page_title) ? htmlspecialchars($page_title) . " - " . $nama_toko : $nama_toko;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<div class="d-flex" id="wrapper">
    <?php include_once 'sidebar.php'; ?>
    
    <div id="page-content-wrapper" class="flex-grow-1">
        <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom px-3">
            <button class="btn btn-sm" id="menu-toggle">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-list" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z"/>
                </svg>
            </button>
            <div class="ms-auto">
                <span class="navbar-text">
                    Kasir: <?php echo htmlspecialchars(get_current_user_nama() ?? 'Pengguna'); ?>
                </span>
            </div>
        </nav>

        <div id="content-inside-wrapper" class="p-4">
            <div class="container-fluid">
<?php // Konten utama halaman akan dimulai di sini ?>