<?php
// /pages/dashboard.php

$page_title = "Dashboard";
require_once '../includes/auth.php'; 
require_login(); // Letakkan eksplisit sebelum header agar tidak bisa di-bypass
require_once '../includes/header.php';

// Ambil informasi pengguna dari session (fungsi dari auth.php)
$nama_pengguna = get_current_user_nama();
$peran_pengguna = get_current_user_role();

// --- MULAI BLOK BARU: Persiapan Data untuk Grafik Penjualan Harian ---

// --- MULAI BLOK BARU: Persiapan Data untuk Kartu Statistik ---
$total_penjualan_bulan_ini = 0;
$sql_penjualan_bulan = "SELECT SUM(total_setelah_diskon_pajak) as total
                        FROM transaksi
                        WHERE MONTH(waktu_transaksi) = MONTH(CURDATE())
                          AND YEAR(waktu_transaksi) = YEAR(CURDATE())
                          AND status_transaksi = 'Selesai'";
$query_penjualan_bulan = mysqli_query($koneksi, $sql_penjualan_bulan);
if ($query_penjualan_bulan) {
    $hasil_penjualan_bulan = mysqli_fetch_assoc($query_penjualan_bulan);
    $total_penjualan_bulan_ini = (float)($hasil_penjualan_bulan['total'] ?? 0);

}

$jumlah_total_produk = 0;
$sql_total_produk = "SELECT COUNT(id_produk) as total FROM produk";
$query_total_produk = mysqli_query($koneksi, $sql_total_produk);
if ($query_total_produk) {
    $hasil_total_produk = mysqli_fetch_assoc($query_total_produk);
    $jumlah_total_produk = $hasil_total_produk['total'] ?? 0;
}
// --- SELESAI BLOK BARU ---
$labels_grafik = [];
$data_penjualan_grafik = [];
$tanggal_sekarang = date('Y-m-d');

// Siapkan array untuk 7 hari terakhir sebagai label, dengan penjualan awal 0
for ($i = 6; $i >= 0; $i--) {
    $tanggal = date('Y-m-d', strtotime("-$i days", strtotime($tanggal_sekarang)));
    $labels_grafik[] = date('d M', strtotime($tanggal)); // Format tanggal untuk label (misal: 24 Mei)
    $data_penjualan_grafik[date('Y-m-d', strtotime($tanggal))] = 0; // Inisialisasi penjualan 0 untuk tanggal ini
}

// Query untuk mengambil total penjualan per hari selama 7 hari terakhir
$sql_penjualan_harian = "SELECT DATE(waktu_transaksi) as tanggal_transaksi, 
                                SUM(total_setelah_diskon_pajak) as total_harian
                         FROM transaksi
                         WHERE waktu_transaksi >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) 
                               AND waktu_transaksi < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
                               AND status_transaksi = 'Selesai' 
                         GROUP BY DATE(waktu_transaksi)
                         ORDER BY tanggal_transaksi ASC";

$query_penjualan_harian = mysqli_query($koneksi, $sql_penjualan_harian);

if ($query_penjualan_harian) {
    while ($row = mysqli_fetch_assoc($query_penjualan_harian)) {
        // Update array data_penjualan_grafik dengan total penjualan aktual dari DB
        if (isset($data_penjualan_grafik[$row['tanggal_transaksi']])) {
            $data_penjualan_grafik[$row['tanggal_transaksi']] = (float)$row['total_harian'];
        }
    }
} else {
    // Handle error query jika perlu
    // echo "Error query penjualan harian: " . mysqli_error($koneksi);
}

// Ubah array asosiatif $data_penjualan_grafik menjadi array numerik biasa sesuai urutan $labels_grafik
// (Karena $data_penjualan_grafik diindeks dengan Y-m-d, kita perlu memastikan urutannya benar)
$data_penjualan_final = [];
foreach ($data_penjualan_grafik as $tanggal_key => $penjualan) {
    $data_penjualan_final[] = $penjualan;
}
// --- SELESAI BLOK BARU ---

?>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4">Selamat Datang di Dashboard!</h1>
        <p class="lead">Halo, <strong><?php echo htmlspecialchars($nama_pengguna ?? 'Pengguna'); ?></strong>!</p>
        <p>Peran Anda saat ini adalah: <strong><?php echo htmlspecialchars($peran_pengguna ?? 'Tidak diketahui'); ?></strong>.</p>
        <hr>
    </div>
</div>

<?php // --- MULAI BLOK BARU: Tampilan Kartu Statistik & Grafik --- ?>
<div class="row">
    <?php // Kartu Statistik Sederhana ?>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Penjualan (Bulan Ini)</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php // --- GANTI BARIS INI --- ?>
                            Rp <?php echo number_format($total_penjualan_bulan_ini, 0, ',', '.'); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-calendar-check text-gray-300" viewBox="0 0 16 16">
                            <path d="M10.854 7.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7.5 9.793l2.646-2.647a.5.5 0 0 1 .708 0z"/>
                            <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Total Produk</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php // --- GANTI BARIS INI --- ?>
                            <?php echo number_format($jumlah_total_produk, 0, ',', '.'); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                         <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-box-seam text-gray-300" viewBox="0 0 16 16"><path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5l2.404.961L10.404 2l-2.218-.887zm3.564 1.426L5.596 5 8 5.961 14.154 3.5l-2.404-.961zm3.25 1.7-6.5 2.6v7.922l6.5-2.6V4.24zM7.5 14.762V6.838L1 4.239v7.923l6.5 2.6zM7.443.184a1.5 1.5 0 0 1 1.114 0l7.129 2.852A.5.5 0 0 1 16 3.5v8.662a1 1 0 0 1-.629.928l-7.185 2.874a.5.5 0 0 1-.372 0L.63 13.09a1 1 0 0 1-.63-.928V3.5a.5.5 0 0 1 .314-.464L7.443.184z"/></svg>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php // Anda bisa menambahkan kartu statistik lainnya jika ada ?>
</div>

<div class="row">
    <div class="col-lg-12 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Grafik Penjualan 7 Hari Terakhir</h6>
            </div>
            <div class="card-body">
                <div class="chart-area" style="height: 320px;"> <?php // Beri tinggi agar canvas terlihat ?>
                    <canvas id="grafikPenjualanHarian"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
<?php // --- SELESAI BLOK BARU --- ?>



<?php // --- MULAI BLOK BARU: JavaScript untuk Chart.js --- ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script> <?php // Sertakan Chart.js dari CDN ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctxPenjualanHarian = document.getElementById('grafikPenjualanHarian').getContext('2d');
    
    // Ambil data dari PHP dan ubah ke format yang bisa dibaca JS
    // Pastikan tidak ada karakter khusus yang merusak string JS saat echo
    const labelsGrafik = <?php echo json_encode($labels_grafik); ?>;
    const dataPenjualanGrafik = <?php echo json_encode($data_penjualan_final); ?>;

    if (ctxPenjualanHarian) {
        new Chart(ctxPenjualanHarian, {
            type: 'line', // Tipe grafik: line, bar, pie, dll.
            data: {
                labels: labelsGrafik,
                datasets: [{
                    label: 'Total Penjualan (Rp)',
                    data: dataPenjualanGrafik,
                    backgroundColor: 'rgba(78, 115, 223, 0.05)', // Warna area di bawah garis
                    borderColor: 'rgba(78, 115, 223, 1)', // Warna garis
                    borderWidth: 2,
                    pointRadius: 3,
                    pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                    pointBorderColor: 'rgba(78, 115, 223, 1)',
                    fill: true, // Untuk mengisi area di bawah garis
                    tension: 0.3 // Membuat garis sedikit melengkung
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false, // Penting agar grafik mengisi tinggi div pembungkus
                scales: {
                    x: {
                        grid: {
                            display: false // Sembunyikan grid vertikal
                        },
                        ticks: {
                            maxRotation: 0, // Agar label tanggal tidak miring jika cukup ruang
                            autoSkip: true,
                            maxTicksLimit: 7 // Tampilkan maksimal 7 label tanggal
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            // Format angka sebagai Rupiah di sumbu Y
                            callback: function(value, index, values) {
                                if (parseInt(value) >= 1000) {
                                    return 'Rp ' + value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                                } else {
                                    return 'Rp ' + value;
                                }
                            }
                        },
                        grid: {
                            color: "rgb(234, 236, 244)",
                            zeroLineColor: "rgb(234, 236, 244)",
                            drawBorder: false,
                            borderDash: [2],
                            zeroLineBorderDash: [2]
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false // Sembunyikan legenda jika hanya satu dataset
                    },
                    tooltip: {
                        backgroundColor: "rgb(255,255,255)",
                        bodyColor: "#858796",
                        borderColor: '#dddfeb',
                        borderWidth: 1,
                        padding: 15,
                        displayColors: false,
                        caretPadding: 10,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(context.parsed.y);
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });
    }
});


</script>
<?php // --- SELESAI BLOK BARU --- ?>

<?php
require_once '../includes/footer.php';
?>