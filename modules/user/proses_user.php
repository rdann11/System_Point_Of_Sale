<?php
// /modules/user/proses_user.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../../includes/db.php';    // Path relatif dari /modules/user/
require_once '../../includes/auth.php'; // Path relatif dari /modules/user/

require_login('../../pages/login.php');
restrict_access(['Admin'], '../../pages/dashboard.php'); // Hanya Admin

// Fungsi redirect bantuan (bisa dibuat global jika sering dipakai)
if (!function_exists('redirect_with_message')) { // Cek agar tidak redeclare jika sudah ada
    function redirect_with_message($url, $message, $type = 'danger') {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_message_type'] = $type;
        header("Location: " . $url);
        exit;
    }
}

// Fungsi redirect dengan error form dan input lama (opsional untuk repopulate)
function redirect_with_form_errors($url, $errors_array, $old_input_array = []) {
    $_SESSION['validation_errors'] = $errors_array;
    if (!empty($old_input_array)) {
        $_SESSION['old_input'] = $old_input_array; // Untuk repopulate form jika diimplementasikan
    }
    header("Location: " . $url);
    exit;
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Validasi CSRF Token
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        redirect_with_message('../../pages/pengguna.php', 'CSRF token tidak valid. Aksi dibatalkan.');
    }

    $aksi = $_POST['aksi'] ?? '';
    $current_user_id_logged_in = get_current_user_id();

    // --- AKSI TAMBAH PENGGUNA ---
    if ($aksi == 'tambah') {
        $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $konfirmasi_password = $_POST['konfirmasi_password'] ?? '';
        $role = $_POST['role'] ?? '';
        $validation_errors = [];

        // Validasi Input
        if (empty($nama_lengkap)) $validation_errors[] = "Nama lengkap wajib diisi.";
        if (empty($username)) $validation_errors[] = "Username wajib diisi.";
        if (empty($password)) $validation_errors[] = "Password wajib diisi.";
        if (empty($konfirmasi_password)) $validation_errors[] = "Konfirmasi password wajib diisi.";
        if ($password !== $konfirmasi_password) $validation_errors[] = "Password dan konfirmasi password tidak cocok.";
        if (strlen($password) < 6 && !empty($password)) $validation_errors[] = "Password minimal harus 6 karakter."; // Contoh validasi panjang
        if (empty($role) || !in_array($role, ['Admin', 'Kasir'])) $validation_errors[] = "Peran pengguna tidak valid.";

        // Cek keunikan username
        if (!empty($username)) {
            $sql_cek_username = "SELECT id_user FROM users WHERE username = ?";
            $stmt_cek = mysqli_prepare($koneksi, $sql_cek_username);
            mysqli_stmt_bind_param($stmt_cek, "s", $username);
            mysqli_stmt_execute($stmt_cek);
            mysqli_stmt_store_result($stmt_cek);
            if (mysqli_stmt_num_rows($stmt_cek) > 0) {
                $validation_errors[] = "Username '$username' sudah digunakan. Silakan pilih username lain.";
            }
            mysqli_stmt_close($stmt_cek);
        }

        if (!empty($validation_errors)) {
            redirect_with_form_errors('../../pages/pengguna_form.php', $validation_errors, $_POST);
        }

        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Simpan ke database
        $sql_insert = "INSERT INTO users (nama_lengkap, username, password, role) VALUES (?, ?, ?, ?)";
        $stmt_insert = mysqli_prepare($koneksi, $sql_insert);
        mysqli_stmt_bind_param($stmt_insert, "ssss", $nama_lengkap, $username, $hashed_password, $role);

        if (mysqli_stmt_execute($stmt_insert)) {
            mysqli_stmt_close($stmt_insert);
            redirect_with_message('../../pages/pengguna.php', 'Pengguna baru berhasil ditambahkan!', 'success');
        } else {
            mysqli_stmt_close($stmt_insert);
            redirect_with_form_errors('../../pages/pengguna_form.php', ['Gagal menambahkan pengguna: ' . mysqli_error($koneksi)], $_POST);
        }

    // --- AKSI EDIT PENGGUNA ---
    } elseif ($aksi == 'edit') {
        $id_user_edit = isset($_POST['id_user']) ? (int)$_POST['id_user'] : 0;
        $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? ''; // Password bisa kosong (tidak diubah)
        $konfirmasi_password = $_POST['konfirmasi_password'] ?? '';
        $role = $_POST['role'] ?? '';
        $validation_errors = [];

        if ($id_user_edit <= 0) $validation_errors[] = "ID pengguna tidak valid untuk diedit.";
        if (empty($nama_lengkap)) $validation_errors[] = "Nama lengkap wajib diisi.";
        if (empty($username)) $validation_errors[] = "Username wajib diisi.";
        if (empty($role) || !in_array($role, ['Admin', 'Kasir'])) $validation_errors[] = "Peran pengguna tidak valid.";

        // Validasi password jika diisi
        if (!empty($password)) {
            if ($password !== $konfirmasi_password) $validation_errors[] = "Password dan konfirmasi password tidak cocok.";
            if (strlen($password) < 6) $validation_errors[] = "Password minimal harus 6 karakter.";
        }

        // Cek keunikan username jika diubah
        if (!empty($username) && $id_user_edit > 0) {
            $sql_cek_username_edit = "SELECT id_user FROM users WHERE username = ? AND id_user != ?";
            $stmt_cek_edit = mysqli_prepare($koneksi, $sql_cek_username_edit);
            mysqli_stmt_bind_param($stmt_cek_edit, "si", $username, $id_user_edit);
            mysqli_stmt_execute($stmt_cek_edit);
            mysqli_stmt_store_result($stmt_cek_edit);
            if (mysqli_stmt_num_rows($stmt_cek_edit) > 0) {
                $validation_errors[] = "Username '$username' sudah digunakan pengguna lain.";
            }
            mysqli_stmt_close($stmt_cek_edit);
        }
        
        // Mencegah admin mengubah username 'admin' utama (jika username itu adalah 'admin')
        // dan mencegah admin mengubah role diri sendiri menjadi Kasir
        if ($id_user_edit == $current_user_id_logged_in && $role == 'Kasir') {
             $validation_errors[] = "Anda tidak dapat mengubah peran akun Anda sendiri menjadi Kasir.";
             $role = 'Admin'; // Kembalikan ke Admin jika coba diubah
        }
        // Untuk username 'admin', di form sudah readonly. Di sini kita pastikan tidak diubah.
        // Jika ingin lebih ketat, bisa cek username lama dari DB dan bandingkan dengan yang baru.

        if (!empty($validation_errors)) {
            redirect_with_form_errors("../../pages/pengguna_form.php?id_user=$id_user_edit", $validation_errors, $_POST);
        }

        // Siapkan query update
        $sql_update_parts = [];
        $params_type_update = "";
        $params_value_update = [];

        $sql_update_parts[] = "nama_lengkap = ?"; $params_type_update .= "s"; $params_value_update[] = $nama_lengkap;
        $sql_update_parts[] = "username = ?"; $params_type_update .= "s"; $params_value_update[] = $username;
        $sql_update_parts[] = "role = ?"; $params_type_update .= "s"; $params_value_update[] = $role;

        if (!empty($password)) {
            $hashed_password_edit = password_hash($password, PASSWORD_DEFAULT);
            $sql_update_parts[] = "password = ?"; $params_type_update .= "s"; $params_value_update[] = $hashed_password_edit;
        }
        $sql_update_parts[] = "updated_at = NOW()";

        $params_value_update[] = $id_user_edit; // Untuk WHERE clause
        $params_type_update .= "i";

        $sql_update = "UPDATE users SET " . implode(", ", $sql_update_parts) . " WHERE id_user = ?";
        $stmt_update = mysqli_prepare($koneksi, $sql_update);
        mysqli_stmt_bind_param($stmt_update, $params_type_update, ...$params_value_update);

        if (mysqli_stmt_execute($stmt_update)) {
            mysqli_stmt_close($stmt_update);
            redirect_with_message('../../pages/pengguna.php', 'Data pengguna berhasil diperbarui!', 'success');
        } else {
            mysqli_stmt_close($stmt_update);
            redirect_with_form_errors("../../pages/pengguna_form.php?id_user=$id_user_edit", ['Gagal memperbarui data pengguna: ' . mysqli_error($koneksi)], $_POST);
        }
    
    // --- AKSI HAPUS PENGGUNA ---
    } elseif ($aksi == 'hapus') {
        $id_user_hapus = isset($_POST['id_user']) ? (int)$_POST['id_user'] : 0;

        if ($id_user_hapus <= 0) {
            redirect_with_message('../../pages/pengguna.php', 'ID pengguna tidak valid untuk dihapus.');
        }
        if ($id_user_hapus == $current_user_id_logged_in) {
            redirect_with_message('../../pages/pengguna.php', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        // PENTING: Cek apakah pengguna terkait dengan data lain (misal, transaksi)
        // Jika ada foreign key constraint ON DELETE RESTRICT, query DELETE akan gagal.
        $sql_cek_transaksi_user = "SELECT COUNT(*) as jumlah_transaksi FROM transaksi WHERE id_user = ?";
        $stmt_cek_trx = mysqli_prepare($koneksi, $sql_cek_transaksi_user);
        mysqli_stmt_bind_param($stmt_cek_trx, "i", $id_user_hapus);
        mysqli_stmt_execute($stmt_cek_trx);
        $result_cek_trx = mysqli_stmt_get_result($stmt_cek_trx);
        $data_cek_trx = mysqli_fetch_assoc($result_cek_trx);
        mysqli_stmt_close($stmt_cek_trx);

        if ($data_cek_trx['jumlah_transaksi'] > 0) {
            redirect_with_message('../../pages/pengguna.php', 'Gagal menghapus pengguna. Pengguna ini memiliki riwayat transaksi. Nonaktifkan akun jika perlu, atau hapus transaksinya terlebih dahulu (tidak disarankan).');
        }

        // Lanjutkan penghapusan jika tidak ada transaksi terkait
        $sql_delete = "DELETE FROM users WHERE id_user = ?";
        $stmt_delete = mysqli_prepare($koneksi, $sql_delete);
        mysqli_stmt_bind_param($stmt_delete, "i", $id_user_hapus);

        if (mysqli_stmt_execute($stmt_delete)) {
            if (mysqli_stmt_affected_rows($stmt_delete) > 0) {
                mysqli_stmt_close($stmt_delete);
                redirect_with_message('../../pages/pengguna.php', 'Pengguna berhasil dihapus!', 'success');
            } else {
                mysqli_stmt_close($stmt_delete);
                redirect_with_message('../../pages/pengguna.php', 'Pengguna tidak ditemukan atau sudah dihapus.');
            }
        } else {
            mysqli_stmt_close($stmt_delete);
            redirect_with_message('../../pages/pengguna.php', 'Gagal menghapus pengguna: ' . mysqli_error($koneksi));
        }

    } else {
        redirect_with_message('../../pages/pengguna.php', 'Aksi tidak dikenal.');
    }

} else {
    redirect_with_message('../../pages/pengguna.php', 'Metode pengiriman data tidak valid.');
}
?>