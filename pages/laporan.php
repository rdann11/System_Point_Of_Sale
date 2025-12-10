<?php
// /pages/laporan.php

$page_title = "Laporan Transaksi";
require_once '../includes/header.php'; // Header akan menjalankan require_login() dan auth.php

// $koneksi sudah tersedia dari db.php yang di-include oleh auth.php -> header.php
// restrict_access(['Admin', 'Kasir'], '../pages/dashboard.php'); // Contoh jika ingin dibatasi

// --- Logika untuk Filter Tanggal ---
$tanggal_awal = isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : date('Y-m-d'); // Default hari ini
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-d'); // Default hari ini

// --- Query untuk Mengambil Data Transaksi ---
$sql_transaksi = "SELECT t.id_transaksi, t.kode_transaksi, t.waktu_transaksi, u.nama_lengkap AS nama_kasir, 
                        t.total_setelah_diskon_pajak AS total_bayar, t.uang_diterima, t.kembalian,
                        (SELECT SUM(td.jumlah_beli) FROM transaksi_detail td WHERE td.id_transaksi = t.id_transaksi) AS total_item
                  FROM transaksi t
                  JOIN users u ON t.id_user = u.id_user";

// Tambahkan kondisi WHERE untuk filter tanggal jika ada
$params_type = "";
$params_value = [];

if (!empty($tanggal_awal) && !empty($tanggal_akhir)) {
    $sql_transaksi .= " WHERE DATE(t.waktu_transaksi) BETWEEN ? AND ?";
    $params_type = "ss"; // Dua string untuk tanggal
    $params_value[] = $tanggal_awal;
    $params_value[] = $tanggal_akhir;
}

$sql_transaksi .= " ORDER BY t.waktu_transaksi DESC"; // Urutkan berdasarkan waktu terbaru

$stmt_transaksi = mysqli_prepare($koneksi, $sql_transaksi);

if (!empty($params_type)) {
    mysqli_stmt_bind_param($stmt_transaksi, $params_type, ...$params_value);
}

mysqli_stmt_execute($stmt_transaksi);
$result_transaksi = mysqli_stmt_get_result($stmt_transaksi);

?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?php echo $page_title; ?></h1>
        <?php // --- MULAI TOMBOL EXPORT --- ?>
        <a href="../modules/laporan/export_csv.php?tanggal_awal=<?php echo htmlspecialchars($tanggal_awal); ?>&tanggal_akhir=<?php echo htmlspecialchars($tanggal_akhir); ?>" class="btn btn-sm btn-primary shadow-sm" id="tombolExportCsv">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-download me-1" viewBox="0 0 16 16">
              <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
              <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
            </svg> Export ke CSV
        </a>
        <?php // --- SELESAI TOMBOL EXPORT --- ?>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['flash_message_type']; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['flash_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_message']); unset($_SESSION['flash_message_type']); ?>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filter Laporan</h6>
        </div>
        <div class="card-body">
            <form action="laporan.php" method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="tanggal_awal" class="form-label">Tanggal Awal:</label>
                    <input type="date" class="form-control" id="tanggal_awal" name="tanggal_awal" value="<?php echo htmlspecialchars($tanggal_awal); ?>">
                </div>
                <div class="col-md-4">
                    <label for="tanggal_akhir" class="form-label">Tanggal Akhir:</label>
                    <input type="date" class="form-control" id="tanggal_akhir" name="tanggal_akhir" value="<?php echo htmlspecialchars($tanggal_akhir); ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Tampilkan</button>
                </div>
                <div class="col-md-2">
                    <a href="laporan.php" class="btn btn-secondary w-100">Reset Filter</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Transaksi</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTableLaporan" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Kode Transaksi</th>
                            <th>Waktu</th>
                            <th>Kasir</th>
                            <th class="text-center">Total Item</th>
                            <th class="text-end">Total Bayar</th>
                            <th class="text-end">Uang Diterima</th>
                            <th class="text-end">Kembalian</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result_transaksi && mysqli_num_rows($result_transaksi) > 0): ?>
                            <?php $nomor = 1; ?>
                            <?php $grand_total_bayar = 0; ?>
                            <?php while($trx = mysqli_fetch_assoc($result_transaksi)): ?>
                            <tr>
                                <td><?php echo $nomor++; ?></td>
                                <td><?php echo htmlspecialchars($trx['kode_transaksi']); ?></td>
                                <td><?php echo date("d/m/Y H:i", strtotime($trx['waktu_transaksi'])); ?></td>
                                <td><?php echo htmlspecialchars($trx['nama_kasir']); ?></td>
                                <td class="text-center"><?php echo htmlspecialchars($trx['total_item'] ?? 0); ?></td>
                                <td class="text-end"><?php echo "Rp " . number_format($trx['total_bayar'], 0, ',', '.'); ?></td>
                                <td class="text-end"><?php echo "Rp " . number_format($trx['uang_diterima'], 0, ',', '.'); ?></td>
                                <td class="text-end"><?php echo "Rp " . number_format($trx['kembalian'], 0, ',', '.'); ?></td>
                                <td>
                                    <a href="struk.php?id_transaksi=<?php echo $trx['id_transaksi']; ?>" class="btn btn-sm btn-info" title="Lihat Struk" target="_blank">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-receipt" viewBox="0 0 16 16">
                                          <path d="M1.92.506a.5.5 0 0 1 .434.14L3 1.293l.646-.647a.5.5 0 0 1 .708 0L5 1.293l.646-.647a.5.5 0 0 1 .708 0L7 1.293l.646-.647a.5.5 0 0 1 .708 0L9 1.293l.646-.647a.5.5 0 0 1 .708 0L11 1.293l.646-.647a.5.5 0 0 1 .708 0L13 1.293l.646-.647a.5.5 0 0 1 .638-.057l.05.05a.5.5 0 0 1 .11.53l-.5 8.5a.5.5 0 0 1-.998.06L14 5.707l-.555.832a.5.5 0 0 1-.832.06l-.588-.883a.5.5 0 0 0-.794 0l-.588.883a.5.5 0 0 1-.832-.06L9 5.707l-.555.832a.5.5 0 0 1-.832.06l-.588-.883a.5.5 0 0 0-.794 0l-.588.883a.5.5 0 0 1-.832-.06L5 5.707l-.555.832a.5.5 0 0 1-.832.06L3.042 5.707l-.555.832a.5.5 0 0 1-.832.06l-.528-.792a.5.5 0 0 1 .005-.629l.5-8.5a.5.5 0 0 1 .399-.44l.05-.05Z"/>
                                          <path d="M3 4.5a.5.5 0 0 1 .5-.5h6a.5.5 0 1 1 0 1h-6a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h6a.5.5 0 1 1 0 1h-6a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h6a.5.5 0 1 1 0 1h-6a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h6a.5.5 0 1 1 0 1h-6a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h6a.5.5 0 0 1 0 1h-6a.5.5 0 0 1-.5-.5zm8-8a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5Z"/>
                                        </svg> Struk
                                    </a>
                                </td>
                            </tr>
                            <?php $grand_total_bayar += $trx['total_bayar']; ?>
                            <?php endwhile; ?>
                            <tr>
                                <td colspan="5" class="text-end fw-bold">Grand Total:</td>
                                <td class="text-end fw-bold"><?php echo "Rp " . number_format($grand_total_bayar, 0, ',', '.'); ?></td>
                                <td colspan="3"></td>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center">Tidak ada data transaksi untuk periode yang dipilih.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
mysqli_stmt_close($stmt_transaksi); // Tutup statement setelah selesai digunakan
?>

<?php
require_once '../includes/footer.php';
?>