<?php
// /modules/pengaturan/proses_pengaturan.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../../includes/db.php';      // Path relatif dari /modules/pengaturan/
require_once '../../includes/auth.php';   // Path relatif dari /modules/pengaturan/

require_login('../../pages/login.php');
restrict_access(['Admin'], '../../pages/dashboard.php'); // Hanya Admin

// Definisi direktori upload logo (relatif dari file proses_pengaturan.php)
define('UPLOAD_DIR_LOGO', '../../uploads/logo/');

// Fungsi redirect bantuan
if (!function_exists('redirect_with_message')) {
    function redirect_with_message($url, $message, $type = 'danger') {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_message_type'] = $type;
        header("Location: " . $url);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Validasi CSRF Token
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        redirect_with_message('../../pages/pengaturan.php', 'CSRF token tidak valid. Aksi dibatalkan.');
    }

    // 2. Ambil Data dari Form POST
    $id_pengaturan = isset($_POST['id_pengaturan']) ? (int)$_POST['id_pengaturan'] : null;
    $nama_toko = trim($_POST['nama_toko'] ?? '');
    $alamat_toko = trim($_POST['alamat_toko'] ?? '');
    $telepon_toko = trim($_POST['telepon_toko'] ?? '');
    $email_toko = trim($_POST['email_toko'] ?? '');
    $catatan_struk = trim($_POST['catatan_struk'] ?? '');
    $hapus_logo_sekarang = isset($_POST['hapus_logo_sekarang']) ? (bool)$_POST['hapus_logo_sekarang'] : false;

    // 3. Validasi Data Dasar
    if (empty($nama_toko)) {
        redirect_with_message('../../pages/pengaturan.php', 'Nama toko tidak boleh kosong.');
    }
    if (!empty($email_toko) && !filter_var($email_toko, FILTER_VALIDATE_EMAIL)) {
        redirect_with_message('../../pages/pengaturan.php', 'Format alamat email tidak valid.');
    }

    // Ambil nama file logo lama dari database untuk proses hapus jika ada upload baru atau opsi hapus
    $logo_lama_db = null;
    if ($id_pengaturan) {
        $sql_get_logo_lama = "SELECT logo_toko FROM pengaturan_toko WHERE id_pengaturan = ?";
        $stmt_get_logo = mysqli_prepare($koneksi, $sql_get_logo_lama);
        mysqli_stmt_bind_param($stmt_get_logo, "i", $id_pengaturan);
        mysqli_stmt_execute($stmt_get_logo);
        $result_logo = mysqli_stmt_get_result($stmt_get_logo);
        if ($row_logo = mysqli_fetch_assoc($result_logo)) {
            $logo_lama_db = $row_logo['logo_toko'];
        }
        mysqli_stmt_close($stmt_get_logo);
    }
    
    $nama_file_logo_untuk_db = $logo_lama_db; // Defaultnya adalah logo lama (atau null jika belum ada)
    $logo_action_message = '';


    // 4. Penanganan File Logo
    if ($hapus_logo_sekarang) {
        if (!empty($logo_lama_db) && file_exists(UPLOAD_DIR_LOGO . $logo_lama_db)) {
            if (unlink(UPLOAD_DIR_LOGO . $logo_lama_db)) {
                $logo_action_message .= ' Logo lama berhasil dihapus.';
            } else {
                $logo_action_message .= ' Gagal menghapus file logo lama.';
            }
        }
        $nama_file_logo_untuk_db = null; // Set ke null di DB
    }

    // Cek apakah ada file baru yang diunggah
    if (isset($_FILES['logo_toko_file']) && $_FILES['logo_toko_file']['error'] == UPLOAD_ERR_OK) {
        $file_tmp_path = $_FILES['logo_toko_file']['tmp_name'];
        $file_name = basename($_FILES['logo_toko_file']['name']); // Gunakan basename untuk keamanan
        $file_size = $_FILES['logo_toko_file']['size'];
        $file_type = $_FILES['logo_toko_file']['type'];
        $file_ext_arr = explode('.', $file_name);
        $file_ext = strtolower(end($file_ext_arr));

        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        $allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_file_size = 2 * 1024 * 1024; // 2MB

        if (!in_array($file_ext, $allowed_extensions) || !in_array(mime_content_type($file_tmp_path), $allowed_mime_types)) {
            redirect_with_message('../../pages/pengaturan.php', 'Format file logo tidak didukung. Hanya JPG, PNG, GIF yang diizinkan.');
        }
        if ($file_size > $max_file_size) {
            redirect_with_message('../../pages/pengaturan.php', 'Ukuran file logo terlalu besar. Maksimal 2MB.');
        }

        // Buat nama file unik
        $new_file_name = 'logo_toko_' . time() . '.' . $file_ext;
        $target_path = UPLOAD_DIR_LOGO . $new_file_name;

        if (move_uploaded_file($file_tmp_path, $target_path)) {
            // Hapus logo lama jika ada dan berbeda dengan yang baru diupload (meski nama baru selalu unik)
            if (!empty($logo_lama_db) && $logo_lama_db != $new_file_name && file_exists(UPLOAD_DIR_LOGO . $logo_lama_db)) {
                unlink(UPLOAD_DIR_LOGO . $logo_lama_db);
            }
            $nama_file_logo_untuk_db = $new_file_name; // Update nama file untuk disimpan ke DB
            $logo_action_message .= ' Logo baru berhasil diunggah.';
        } else {
            redirect_with_message('../../pages/pengaturan.php', 'Gagal memindahkan file logo yang diunggah.');
        }
    }


    // 5. Update Database
    // Kita asumsikan selalu ada 1 baris data pengaturan, jadi kita UPDATE berdasarkan ID atau LIMIT 1
    // Jika id_pengaturan tidak ada (seharusnya tidak terjadi jika sudah di-setup awal), maka perlu INSERT.
    // Untuk kesederhanaan, kita fokus pada UPDATE karena sudah ada data awal.
    
    if ($id_pengaturan) {
        $sql_update = "UPDATE pengaturan_toko SET 
                        nama_toko = ?, 
                        alamat_toko = ?, 
                        telepon_toko = ?, 
                        email_toko = ?, 
                        catatan_struk = ?,
                        logo_toko = ? 
                       WHERE id_pengaturan = ?";
        $stmt_update = mysqli_prepare($koneksi, $sql_update);
        mysqli_stmt_bind_param($stmt_update, "ssssssi", 
            $nama_toko, $alamat_toko, $telepon_toko, $email_toko, $catatan_struk, $nama_file_logo_untuk_db, $id_pengaturan
        );

        if (mysqli_stmt_execute($stmt_update)) {
            mysqli_stmt_close($stmt_update);
            redirect_with_message('../../pages/pengaturan.php', 'Pengaturan toko berhasil disimpan.' . $logo_action_message, 'success');
        } else {
            $error_db = mysqli_error($koneksi); // atau mysqli_stmt_error($stmt_update)
            mysqli_stmt_close($stmt_update);
            redirect_with_message('../../pages/pengaturan.php', "Gagal menyimpan pengaturan: " . $error_db);
        }
    } else {
        // Skenario jika tidak ada id_pengaturan (misal tabel kosong & belum ada data awal)
        // Sebaiknya INSERT jika $id_pengaturan null (berarti data belum ada)
        $sql_insert = "INSERT INTO pengaturan_toko (nama_toko, alamat_toko, telepon_toko, email_toko, catatan_struk, logo_toko) 
                       VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_insert = mysqli_prepare($koneksi, $sql_insert);
        mysqli_stmt_bind_param($stmt_insert, "ssssss",
            $nama_toko, $alamat_toko, $telepon_toko, $email_toko, $catatan_struk, $nama_file_logo_untuk_db
        );
        if (mysqli_stmt_execute($stmt_insert)) {
            mysqli_stmt_close($stmt_insert);
            redirect_with_message('../../pages/pengaturan.php', 'Pengaturan toko berhasil disimpan (data baru dibuat).' . $logo_action_message, 'success');
        } else {
            $error_db = mysqli_error($koneksi);
            mysqli_stmt_close($stmt_insert);
            redirect_with_message('../../pages/pengaturan.php', "Gagal menyimpan pengaturan baru: " . $error_db);
        }
    }

} else {
    // Bukan metode POST
    redirect_with_message('../../pages/pengaturan.php', 'Metode pengiriman data tidak valid.');
}
?>