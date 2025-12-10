<?php
$page_title = "Manajemen Produk";
require_once '../includes/header.php';

$sql = "SELECT p.id_produk, p.kode_produk, p.nama_produk, k.nama_kategori, p.harga_jual, p.stok, p.satuan
        FROM produk p
        JOIN kategori k ON p.id_kategori = k.id_kategori
        ORDER BY p.nama_produk ASC";
$query = mysqli_query($koneksi, $sql);

if (!$query) {
    die("Query gagal: " . mysqli_error($koneksi));
}
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?php echo $page_title; ?></h1>
        <a href="produk_form.php" class="btn btn-success shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> Tambah Produk Baru
        </a>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['flash_message_type']; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['flash_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
        <?php unset($_SESSION['flash_message'], $_SESSION['flash_message_type']); ?>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Produk</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTableProduk" width="100%" cellspacing="0">
                    <thead class="table-light">
                        <tr class="text-center text-nowrap">
                            <th>No.</th>
                            <th>Kode</th>
                            <th>Nama Produk</th>
                            <th>Kategori</th>
                            <th>Harga Jual</th>
                            <th>Stok</th>
                            <th>Satuan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($query) > 0): ?>
                            <?php $nomor = 1; ?>
                            <?php while($produk = mysqli_fetch_assoc($query)): ?>
                                <tr class="align-middle text-nowrap">
                                    <td class="text-center"><?php echo $nomor++; ?></td>
                                    <td><?php echo htmlspecialchars($produk['kode_produk'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($produk['nama_produk']); ?></td>
                                    <td><?php echo htmlspecialchars($produk['nama_kategori']); ?></td>
                                    <td class="text-end"><?php echo "Rp " . number_format($produk['harga_jual'], 0, ',', '.'); ?></td>
                                    <td class="text-center"><?php echo htmlspecialchars($produk['stok']); ?></td>
                                    <td><?php echo htmlspecialchars($produk['satuan']); ?></td>
                                    <td class="text-center">
                                        <a href="produk_form.php?id=<?php echo $produk['id_produk']; ?>" class="btn btn-warning btn-sm me-1" title="Edit">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <button onclick="konfirmasiHapusProduk(<?php echo $produk['id_produk']; ?>, '<?php echo htmlspecialchars(addslashes($produk['nama_produk'])); ?>')" class="btn btn-danger btn-sm" title="Hapus">
                                            <i class="bi bi-trash3-fill"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">Belum ada data produk.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function konfirmasiHapusProduk(idProduk, namaProduk) {
    if (confirm(`Apakah Anda yakin ingin menghapus produk "${namaProduk}"? Tindakan ini tidak dapat dibatalkan.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '../modules/produk/proses_produk.php';

        const inputs = [
            { name: 'aksi', value: 'hapus' },
            { name: 'id_produk', value: idProduk },
            { name: 'csrf_token', value: '<?php echo htmlspecialchars(function_exists("generate_csrf_token") ? generate_csrf_token() : ""); ?>' }
        ];

        inputs.forEach(data => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = data.name;
            input.value = data.value;
            form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    }
}
</script>

<?php
require_once '../includes/footer.php';
?>
