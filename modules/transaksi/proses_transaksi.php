<?php
// /modules/transaksi/proses_transaksi.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../../includes/db.php';      // Path relatif dari /modules/transaksi/
require_once '../../includes/auth.php';   // Path relatif dari /modules/transaksi/

require_login('../../pages/login.php'); // Pastikan hanya user login yang bisa akses

// Fungsi redirect bantuan (sudah ada di proses_produk.php, bisa dijadikan global include nanti)
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
        redirect_with_message('../../pages/transaksi.php', 'CSRF token tidak valid. Transaksi dibatalkan.');
    }

    // 2. Ambil Data dari Form POST
    $id_user_kasir = (int)($_POST['id_user_kasir'] ?? 0);
    $total_harga_produk_form = (float)($_POST['total_harga_produk_hidden'] ?? 0); // Subtotal dari semua item
    $total_bayar_form = (float)($_POST['total_setelah_diskon_pajak_hidden'] ?? 0); // Total akhir yg harus dibayar
    $uang_diterima = (float)($_POST['uang_diterima'] ?? 0);
    $kembalian_form = (float)($_POST['kembalian_hidden'] ?? 0);
    $catatan_transaksi = trim($_POST['catatan_transaksi'] ?? '');
    $detail_item_json = $_POST['detail_item_json'] ?? '[]';

    $items_keranjang = json_decode($detail_item_json, true);

    // 3. Validasi Data Dasar
    if ($id_user_kasir <= 0) {
        redirect_with_message('../../pages/transaksi.php', 'ID Kasir tidak valid. Transaksi dibatalkan.');
    }
    if (empty($items_keranjang)) {
        redirect_with_message('../../pages/transaksi.php', 'Keranjang belanja kosong. Tidak ada transaksi untuk disimpan.');
    }
    if ($uang_diterima < $total_bayar_form) {
        redirect_with_message('../../pages/transaksi.php', 'Jumlah uang diterima kurang dari total bayar. Transaksi dibatalkan.');
    }

    // --- Mulai Transaksi Database ---
    mysqli_autocommit($koneksi, false); // atau mysqli_begin_transaction($koneksi);

    $error_flag = false;
    $id_transaksi_baru = null;

    try {
        // 4. Validasi Stok Produk dan Hitung Ulang Total Harga Produk di Server
        $recalculated_total_harga_produk = 0;
        $validated_items = [];

        foreach ($items_keranjang as $item) {
            $id_produk = (int)($item['id_produk'] ?? 0);
            $jumlah_beli = (int)($item['jumlah_beli'] ?? 0);

            if ($id_produk <= 0 || $jumlah_beli <= 0) {
                throw new Exception("Data item produk tidak valid di keranjang.");
            }

            // Ambil data produk terbaru dari DB untuk validasi harga dan stok
            $sql_cek_produk = "SELECT nama_produk, harga_jual, stok FROM produk WHERE id_produk = ?";
            $stmt_cek_produk = mysqli_prepare($koneksi, $sql_cek_produk);
            mysqli_stmt_bind_param($stmt_cek_produk, "i", $id_produk);
            mysqli_stmt_execute($stmt_cek_produk);
            $result_produk = mysqli_stmt_get_result($stmt_cek_produk);
            $produk_db = mysqli_fetch_assoc($result_produk);
            mysqli_stmt_close($stmt_cek_produk);

            if (!$produk_db) {
                throw new Exception("Produk dengan ID $id_produk tidak ditemukan.");
            }
            if ($produk_db['stok'] < $jumlah_beli) {
                throw new Exception("Stok produk \"{$produk_db['nama_produk']}\" tidak mencukupi (tersisa: {$produk_db['stok']}, diminta: $jumlah_beli).");
            }

            // Gunakan harga dari database untuk perhitungan di server (lebih aman)
            $harga_jual_db = (float)$produk_db['harga_jual'];
            $subtotal_item_server = $harga_jual_db * $jumlah_beli;
            $recalculated_total_harga_produk += $subtotal_item_server;

            $validated_items[] = [
                'id_produk' => $id_produk,
                'nama_produk_saat_transaksi' => $produk_db['nama_produk'], // Ambil nama dari DB
                'harga_produk_saat_transaksi' => $harga_jual_db, // Ambil harga dari DB
                'jumlah_beli' => $jumlah_beli,
                'subtotal_produk' => $subtotal_item_server,
                'subtotal_setelah_diskon_item' => $subtotal_item_server // Asumsi belum ada diskon item
            ];
        }
        
        // Validasi ulang total jika ada perbedaan signifikan (misal karena harga berubah cepat)
        // Untuk sekarang, kita percaya total_bayar_form jika tidak ada diskon/pajak global.
        // Jika ada diskon/pajak global, total_bayar_form harus dihitung ulang di server.
        // Asumsi: total_bayar_form == recalculated_total_harga_produk (karena belum ada diskon/pajak global)

        if (abs($recalculated_total_harga_produk - $total_harga_produk_form) > 0.01) { // Toleransi kecil untuk float
             // Ini bisa berarti harga produk berubah antara frontend dan backend, atau manipulasi.
             // throw new Exception("Terjadi perbedaan total harga produk. Silakan ulangi transaksi.");
             // Untuk saat ini, kita gunakan total yang dihitung server jika ada diskon/pajak.
             // Jika tidak ada, total bayar harusnya sama.
        }
        $final_total_bayar_server = $recalculated_total_harga_produk; // Akan disesuaikan jika ada diskon/pajak
        $final_kembalian_server = $uang_diterima - $final_total_bayar_server;

        // 5. Generate Kode Transaksi
        // Format: INV-YYYYMMDD-XXXX (4 digit random atau sequence harian)
        $tanggal_kode = date('Ymd');
        $nomor_urut_query = "SELECT COUNT(*) as total_transaksi_hari_ini FROM transaksi WHERE DATE(waktu_transaksi) = CURDATE()";
        $hasil_nomor_urut = mysqli_query($koneksi, $nomor_urut_query);
        $data_nomor_urut = mysqli_fetch_assoc($hasil_nomor_urut);
        $nomor_urut_berikutnya = $data_nomor_urut['total_transaksi_hari_ini'] + 1;
        $kode_transaksi = "INV-" . $tanggal_kode . "-" . str_pad($nomor_urut_berikutnya, 4, "0", STR_PAD_LEFT);


        // 6. Simpan ke Tabel `transaksi`
        $sql_transaksi = "INSERT INTO transaksi (kode_transaksi, id_user, total_harga_produk, total_setelah_diskon_pajak, uang_diterima, kembalian, catatan_transaksi, status_transaksi) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, 'Selesai')";
        $stmt_transaksi = mysqli_prepare($koneksi, $sql_transaksi);
        mysqli_stmt_bind_param($stmt_transaksi, "siddsss", 
            $kode_transaksi, $id_user_kasir, $recalculated_total_harga_produk, $final_total_bayar_server, $uang_diterima, $final_kembalian_server, $catatan_transaksi
        );
        
        if (!mysqli_stmt_execute($stmt_transaksi)) {
            throw new Exception("Gagal menyimpan data transaksi utama: " . mysqli_stmt_error($stmt_transaksi));
        }
        $id_transaksi_baru = mysqli_insert_id($koneksi); // Ambil ID transaksi yang baru dibuat
        mysqli_stmt_close($stmt_transaksi);

        if ($id_transaksi_baru <= 0) {
             throw new Exception("Gagal mendapatkan ID transaksi baru.");
        }

        // 7. Simpan ke Tabel `transaksi_detail` dan Update Stok Produk
        foreach ($validated_items as $v_item) {
            $sql_detail = "INSERT INTO transaksi_detail (id_transaksi, id_produk, nama_produk_saat_transaksi, harga_produk_saat_transaksi, jumlah_beli, subtotal_produk, subtotal_setelah_diskon_item)
                           VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt_detail = mysqli_prepare($koneksi, $sql_detail);
            mysqli_stmt_bind_param($stmt_detail, "iissddd", 
                $id_transaksi_baru, $v_item['id_produk'], $v_item['nama_produk_saat_transaksi'], 
                $v_item['harga_produk_saat_transaksi'], $v_item['jumlah_beli'], 
                $v_item['subtotal_produk'], $v_item['subtotal_setelah_diskon_item']
            );
            if (!mysqli_stmt_execute($stmt_detail)) {
                throw new Exception("Gagal menyimpan detail transaksi untuk produk \"{$v_item['nama_produk_saat_transaksi']}\": " . mysqli_stmt_error($stmt_detail));
            }
            mysqli_stmt_close($stmt_detail);

            // Update stok produk
            $sql_update_stok = "UPDATE produk SET stok = stok - ? WHERE id_produk = ? AND stok >= ?";
            $stmt_update_stok = mysqli_prepare($koneksi, $sql_update_stok);
            mysqli_stmt_bind_param($stmt_update_stok, "iii", 
                $v_item['jumlah_beli'], $v_item['id_produk'], $v_item['jumlah_beli'] // Kondisi stok >= jumlah_beli untuk double check
            );
            if (!mysqli_stmt_execute($stmt_update_stok)) {
                throw new Exception("Gagal mengupdate stok produk \"{$v_item['nama_produk_saat_transaksi']}\": " . mysqli_stmt_error($stmt_update_stok));
            }
            if (mysqli_stmt_affected_rows($stmt_update_stok) == 0) {
                 // Ini bisa terjadi jika stok tiba-tiba habis (race condition walau sudah dicek)
                 throw new Exception("Stok produk \"{$v_item['nama_produk_saat_transaksi']}\" tidak mencukupi saat proses update.");
            }
            mysqli_stmt_close($stmt_update_stok);
        }

        // Jika semua berhasil
        mysqli_commit($koneksi);
        $error_flag = false;

    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        $error_flag = true;
        $error_message_transaksi = $e->getMessage();
    }

    // Kembalikan ke mode autocommit
    mysqli_autocommit($koneksi, true);

    // 8. Feedback dan Redirect
    if (!$error_flag && $id_transaksi_baru) {
        // Transaksi sukses
        $_SESSION['last_transaction_id'] = $id_transaksi_baru; // Simpan ID untuk struk nanti
        $_SESSION['last_transaction_code'] = $kode_transaksi;
        // Redirect ke halaman struk
$_SESSION['flash_message'] = "Transaksi dengan kode $kode_transaksi berhasil disimpan!"; // Pesan tetap bisa dibawa jika struk.php menampilkannya
$_SESSION['flash_message_type'] = "success";
header("Location: ../../pages/struk.php?id_transaksi=" . $id_transaksi_baru);
exit;
    } else {
        // Transaksi gagal
        redirect_with_message('../../pages/transaksi.php', 'Transaksi GAGAL: ' . ($error_message_transaksi ?? 'Terjadi kesalahan tidak diketahui.'));
    }

} else {
    // Bukan metode POST
    redirect_with_message('../../pages/transaksi.php', 'Metode pengiriman data tidak valid.');
}
?>