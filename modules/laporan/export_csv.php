<?php
// /modules/laporan/export_csv.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../../includes/db.php';    // Path relatif dari /modules/laporan/
require_once '../../includes/auth.php'; // Path relatif dari /modules/laporan/

require_login('../../pages/login.php'); // Pastikan hanya user login
// restrict_access(['Admin'], '../../pages/dashboard.php'); // Jika hanya Admin yang boleh export

// Ambil parameter filter tanggal dari GET request
$tanggal_awal_filter = isset($_GET['tanggal_awal']) && !empty($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : null;
$tanggal_akhir_filter = isset($_GET['tanggal_akhir']) && !empty($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : null;

// Query untuk mengambil data transaksi (sama atau mirip dengan di laporan.php)
$sql_export = "SELECT t.id_transaksi, t.kode_transaksi, t.waktu_transaksi, u.nama_lengkap AS nama_kasir, 
                      t.total_setelah_diskon_pajak AS total_bayar, t.uang_diterima, t.kembalian, t.catatan_transaksi,
                      (SELECT SUM(td.jumlah_beli) FROM transaksi_detail td WHERE td.id_transaksi = t.id_transaksi) AS total_item
               FROM transaksi t
               JOIN users u ON t.id_user = u.id_user";

$params_type_export = "";
$params_value_export = [];

if ($tanggal_awal_filter && $tanggal_akhir_filter) {
    $sql_export .= " WHERE DATE(t.waktu_transaksi) BETWEEN ? AND ?";
    $params_type_export = "ss";
    $params_value_export[] = $tanggal_awal_filter;
    $params_value_export[] = $tanggal_akhir_filter;
}

$sql_export .= " ORDER BY t.waktu_transaksi ASC"; // Urutkan dari yang paling lama untuk CSV, atau DESC jika preferensi

$stmt_export = mysqli_prepare($koneksi, $sql_export);

if (!empty($params_type_export)) {
    mysqli_stmt_bind_param($stmt_export, $params_type_export, ...$params_value_export);
}

mysqli_stmt_execute($stmt_export);
$result_export = mysqli_stmt_get_result($stmt_export);

// --- Persiapan untuk Membuat File CSV ---

// Nama file CSV yang akan diunduh
$nama_file_csv = "Laporan_Transaksi";
if ($tanggal_awal_filter && $tanggal_akhir_filter) {
    $nama_file_csv .= "_dari_" . str_replace('-', '', $tanggal_awal_filter) . "_sampai_" . str_replace('-', '', $tanggal_akhir_filter);
} else {
    $nama_file_csv .= "_SemuaPeriode";
}
$nama_file_csv .= "_" . date('YmdHis') . ".csv";

// Set HTTP Headers untuk download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $nama_file_csv . '"');

// Buka output stream PHP untuk menulis CSV
$output_stream = fopen('php://output', 'w');

// Tulis baris Header CSV
$header_csv = [
    'No.',
    'Kode Transaksi',
    'Waktu Transaksi',
    'Kasir',
    'Total Item',
    'Total Bayar (Rp)',
    'Uang Diterima (Rp)',
    'Kembalian (Rp)',
    'Catatan'
];
fputcsv($output_stream, $header_csv);

// Tulis baris Data CSV
$nomor_baris = 1;
if ($result_export && mysqli_num_rows($result_export) > 0) {
    while ($row = mysqli_fetch_assoc($result_export)) {
        $data_baris_csv = [
            $nomor_baris++,
            $row['kode_transaksi'],
            date("d/m/Y H:i:s", strtotime($row['waktu_transaksi'])), // Format tanggal & waktu
            $row['nama_kasir'],
            $row['total_item'] ?? 0,
            $row['total_bayar'],        // Angka murni untuk CSV
            $row['uang_diterima'],      // Angka murni
            $row['kembalian'],          // Angka murni
            $row['catatan_transaksi']
        ];
        fputcsv($output_stream, $data_baris_csv);
    }
} else {
    // Jika tidak ada data, bisa tulis satu baris pesan atau biarkan kosong setelah header
    fputcsv($output_stream, ['Tidak ada data transaksi untuk periode yang dipilih.']);
}

// Tutup statement database
mysqli_stmt_close($stmt_export);

// fclose($output_stream); // Tidak wajib untuk php://output, akan otomatis tertutup

exit; // Hentikan eksekusi skrip agar tidak ada output lain
?>