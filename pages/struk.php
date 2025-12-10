<?php
// /pages/struk.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/db.php';
require_once '../includes/auth.php'; // Untuk fungsi require_login jika diperlukan, meski struk bisa jadi publik jika linknya ada

// Cek apakah ada ID transaksi yang dikirim via GET atau dari session (setelah transaksi sukses)
$id_transaksi = null;
if (isset($_GET['id_transaksi']) && !empty($_GET['id_transaksi'])) {
    $id_transaksi = (int)$_GET['id_transaksi'];
} elseif (isset($_SESSION['last_transaction_id'])) {
    $id_transaksi = (int)$_SESSION['last_transaction_id'];
    unset($_SESSION['last_transaction_id']); // Hapus dari session setelah digunakan
}

if (!$id_transaksi) {
    // Jika tidak ada ID, mungkin tampilkan pesan atau redirect
    // Untuk sekarang, kita matikan saja jika tidak ada ID
    die("ID Transaksi tidak valid atau tidak ditemukan.");
}

// Ambil data Pengaturan Toko
$pengaturan_toko = null;
$sql_pengaturan = "SELECT nama_toko, alamat_toko, telepon_toko, email_toko, catatan_struk FROM pengaturan_toko LIMIT 1";
$query_pengaturan = mysqli_query($koneksi, $sql_pengaturan);
if ($query_pengaturan && mysqli_num_rows($query_pengaturan) > 0) {
    $pengaturan_toko = mysqli_fetch_assoc($query_pengaturan);
} else {
    // Default jika pengaturan toko tidak ada
    $pengaturan_toko = [
        'nama_toko' => 'Nama Toko Anda',
        'alamat_toko' => 'Alamat Toko Anda',
        'telepon_toko' => 'Telepon Toko',
        'email_toko' => '',
        'catatan_struk' => 'Terima kasih telah berbelanja!'
    ];
}


// Ambil data Header Transaksi
$transaksi_header = null;
$sql_header = "SELECT t.*, u.nama_lengkap as nama_kasir 
               FROM transaksi t 
               JOIN users u ON t.id_user = u.id_user 
               WHERE t.id_transaksi = ?";
$stmt_header = mysqli_prepare($koneksi, $sql_header);
mysqli_stmt_bind_param($stmt_header, "i", $id_transaksi);
mysqli_stmt_execute($stmt_header);
$result_header = mysqli_stmt_get_result($stmt_header);
if ($result_header && mysqli_num_rows($result_header) > 0) {
    $transaksi_header = mysqli_fetch_assoc($result_header);
} else {
    die("Data transaksi tidak ditemukan untuk ID: " . htmlspecialchars($id_transaksi));
}
mysqli_stmt_close($stmt_header);

// Ambil data Detail Transaksi
$transaksi_detail = [];
$sql_detail = "SELECT td.* FROM transaksi_detail td WHERE td.id_transaksi = ?";
$stmt_detail = mysqli_prepare($koneksi, $sql_detail);
mysqli_stmt_bind_param($stmt_detail, "i", $id_transaksi);
mysqli_stmt_execute($stmt_detail);
$result_detail = mysqli_stmt_get_result($stmt_detail);
if ($result_detail) {
    while ($row = mysqli_fetch_assoc($result_detail)) {
        $transaksi_detail[] = $row;
    }
}
mysqli_stmt_close($stmt_detail);

// Fungsi format Rupiah (bisa ditaruh di file helper nanti)
if (!function_exists('formatRupiahStruk')) {
    function formatRupiahStruk($angka, $prefix = true) {
        return ($prefix ? "Rp" : "") . number_format($angka, 0, ',', '.');
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Transaksi - <?php echo htmlspecialchars($transaksi_header['kode_transaksi']); ?></title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace; /* Font umum untuk struk */
            font-size: 10pt; /* Ukuran font kecil */
            color: #000;
            background-color: #fff;
            width: 280px; /* Perkiraan lebar kertas thermal 58mm, sesuaikan jika perlu (kurangi padding/margin) */
            margin: 0 auto; /* Untuk centering di browser, tidak pengaruh di print */
            padding: 5px;
        }
        .struk-container {
            width: 100%;
            padding: 10px;
            box-sizing: border-box;
        }
        .header-toko {
            text-align: center;
            margin-bottom: 10px;
            border-bottom: 1px dashed #000;
            padding-bottom: 5px;
        }
        .header-toko h1 {
            font-size: 12pt;
            margin: 0;
            font-weight: bold;
        }
        .header-toko p {
            font-size: 8pt;
            margin: 2px 0;
        }
        .info-transaksi {
            margin-bottom: 5px;
        }
        .info-transaksi table {
            width: 100%;
            font-size: 9pt;
        }
        .info-transaksi td:nth-child(2) {
            text-align: right;
        }
        .item-list table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
        }
        .item-list th, .item-list td {
            padding: 2px 0;
        }
        .item-list .nama-produk {
            /* word-break: break-all; */ /* Jika nama produk panjang */
        }
        .item-list .number {
            text-align: right;
        }
        .item-list .qty {
            text-align: center;
        }
        .summary table {
            width: 100%;
            font-size: 9pt;
        }
        .summary td:nth-child(1) {
            text-align: left;
        }
        .summary td:nth-child(2) {
            text-align: right;
            font-weight: bold;
        }
        .catatan-kaki {
            text-align: center;
            margin-top: 10px;
            padding-top: 5px;
            border-top: 1px dashed #000;
            font-size: 8pt;
        }
        .separator {
            border-top: 1px dashed #000;
            margin: 5px 0;
            height: 0;
            line-height: 0;
        }
        .no-print { /* Kelas untuk elemen yang tidak ingin dicetak */
            margin-top: 20px;
            text-align: center;
        }
        @media print {
            body {
                width: auto; /* Biarkan printer mengatur lebar */
                margin: 0;
                padding: 0;
            }
            .no-print {
                display: none;
            }
            /* Optional: Atur margin print jika diperlukan */
            @page {
                margin: 5mm; 
            }
        }

       
        .header-toko .logo-struk {
            max-height: 50px; /* Batasi tinggi logo di struk */
            max-width: 150px; /* Batasi lebar logo di struk */
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="struk-container">
        <div class="header-toko">
            
            <h1><?php echo htmlspecialchars($pengaturan_toko['nama_toko']); ?></h1>
            <p><?php echo htmlspecialchars($pengaturan_toko['alamat_toko']); ?></p>
            <?php if (!empty($pengaturan_toko['telepon_toko'])): ?>
                <p>Telp: <?php echo htmlspecialchars($pengaturan_toko['telepon_toko']); ?></p>
            <?php endif; ?>
            <?php if (!empty($pengaturan_toko['email_toko'])): ?>
                <p>Email: <?php echo htmlspecialchars($pengaturan_toko['email_toko']); ?></p>
            <?php endif; ?>
        </div>

        <div class="info-transaksi">
            <table>
                <tr>
                    <td>No. Transaksi</td>
                    <td><?php echo htmlspecialchars($transaksi_header['kode_transaksi']); ?></td>
                </tr>
                <tr>
                    <td>Tanggal</td>
                    <td><?php echo date("d/m/Y H:i", strtotime($transaksi_header['waktu_transaksi'])); ?></td>
                </tr>
                <tr>
                    <td>Kasir</td>
                    <td><?php echo htmlspecialchars($transaksi_header['nama_kasir']); ?></td>
                </tr>
            </table>
        </div>

        <div class="separator"></div>

        <div class="item-list">
            <table>
                <?php foreach ($transaksi_detail as $item): ?>
                <tr>
                    <td colspan="3" class="nama-produk"><?php echo htmlspecialchars($item['nama_produk_saat_transaksi']); ?></td>
                </tr>
                <tr>
                    <td class="qty" style="text-align:left; padding-left:10px;">
                        <?php echo htmlspecialchars($item['jumlah_beli']); ?> x 
                    </td>
                    <td class="number"><?php echo formatRupiahStruk($item['harga_produk_saat_transaksi'], false); ?></td>
                    <td class="number"><?php echo formatRupiahStruk($item['subtotal_setelah_diskon_item'], false); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div class="separator"></div>

        <div class="summary">
            <table>
                <tr>
                    <td>Subtotal Produk</td>
                    <td><?php echo formatRupiahStruk($transaksi_header['total_harga_produk']); ?></td>
                </tr>
                <?php if (isset($transaksi_header['diskon_nominal_global']) && $transaksi_header['diskon_nominal_global'] > 0): ?>
                <tr>
                    <td>Diskon Global</td>
                    <td>- <?php echo formatRupiahStruk($transaksi_header['diskon_nominal_global']); ?></td>
                </tr>
                <?php endif; ?>
                 <?php if (isset($transaksi_header['pajak_persen_global']) && $transaksi_header['pajak_persen_global'] > 0): 
                    // Hitung nominal pajak jika ada persennya
                    $nominal_pajak = ($transaksi_header['total_harga_produk'] - ($transaksi_header['diskon_nominal_global'] ?? 0)) * ($transaksi_header['pajak_persen_global'] / 100);
                ?>
                <tr>
                    <td>Pajak (<?php echo htmlspecialchars($transaksi_header['pajak_persen_global']); ?>%)</td>
                    <td><?php echo formatRupiahStruk($nominal_pajak); ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td><strong>TOTAL BAYAR</strong></td>
                    <td><strong><?php echo formatRupiahStruk($transaksi_header['total_setelah_diskon_pajak']); ?></strong></td>
                </tr>
                <tr>
                    <td>Uang Diterima</td>
                    <td><?php echo formatRupiahStruk($transaksi_header['uang_diterima']); ?></td>
                </tr>
                <tr>
                    <td>Kembalian</td>
                    <td><?php echo formatRupiahStruk($transaksi_header['kembalian']); ?></td>
                </tr>
            </table>
        </div>
        
        <?php if (!empty($transaksi_header['catatan_transaksi'])): ?>
        <div class="separator"></div>
        <div style="font-size:8pt; margin-top:5px;">
            Catatan: <?php echo nl2br(htmlspecialchars($transaksi_header['catatan_transaksi'])); ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($pengaturan_toko['catatan_struk'])): ?>
        <div class="catatan-kaki">
            <p><?php echo nl2br(htmlspecialchars($pengaturan_toko['catatan_struk'])); ?></p>
        </div>
        <?php endif; ?>
    </div>

    <div class="no-print">
        <button onclick="window.print();">Cetak Struk</button>
        <button onclick="window.location.href='transaksi.php';">Transaksi Baru</button>
        <button onclick="window.location.href='laporan.php';">Lihat Laporan</button> <?php // Nanti ke laporan.php ?>
    </div>

</body>
</html>