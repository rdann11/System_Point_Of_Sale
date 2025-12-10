<?php
// /pages/produk_form.php

require_once '../includes/header.php'; // Header akan menjalankan require_login() dan auth.php

// $koneksi sudah tersedia dari db.php yang di-include oleh auth.php -> header.php

// Inisialisasi variabel untuk form
$mode = 'tambah'; // Default mode adalah 'tambah'
$id_produk_edit = null;
$produk = [
    'kode_produk' => '',
    'nama_produk' => '',
    'id_kategori' => '',
    'harga_beli' => '',
    'harga_jual' => '',
    'stok' => '',
    'satuan' => 'Pcs', // Default satuan
    'deskripsi_produk' => ''
];
$page_title = "Tambah Produk Baru"; // Default judul halaman

// Cek apakah ini mode edit (ada parameter id di URL)
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $mode = 'edit';
    $id_produk_edit = (int)$_GET['id'];
    $page_title = "Edit Produk";

    // Ambil data produk yang akan diedit dari database
    $sql_produk_edit = "SELECT * FROM produk WHERE id_produk = ?";
    $stmt_produk_edit = mysqli_prepare($koneksi, $sql_produk_edit);
    mysqli_stmt_bind_param($stmt_produk_edit, "i", $id_produk_edit);
    mysqli_stmt_execute($stmt_produk_edit);
    $result_produk_edit = mysqli_stmt_get_result($stmt_produk_edit);
    
    if ($produk_data = mysqli_fetch_assoc($result_produk_edit)) {
        $produk = $produk_data; // Timpa array $produk dengan data dari database
        $page_title .= ": " . htmlspecialchars($produk['nama_produk']); // Tambahkan nama produk ke judul
    } else {
        // Produk tidak ditemukan, tampilkan pesan dan redirect atau matikan form
        $_SESSION['flash_message'] = 'Produk yang ingin Anda edit tidak ditemukan.';
        $_SESSION['flash_message_type'] = 'danger';
        header("Location: produk.php");
        exit;
    }
    mysqli_stmt_close($stmt_produk_edit);
}

// Ambil daftar kategori untuk dropdown
$kategori_list = [];
$sql_kategori = "SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori ASC";
$query_kategori = mysqli_query($koneksi, $sql_kategori);
if ($query_kategori) {
    while ($row_kategori = mysqli_fetch_assoc($query_kategori)) {
        $kategori_list[] = $row_kategori;
    }
}

// Generate CSRF token
$csrf_token = generate_csrf_token();
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?php echo $page_title; ?></h1>
        <a href="produk.php" class="btn btn-secondary btn-icon-split">
            <span class="icon text-white-50">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left-short" viewBox="0 0 16 16">
                  <path fill-rule="evenodd" d="M12 8a.5.5 0 0 1-.5.5H5.707l2.147 2.146a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L5.707 7.5H11.5a.5.5 0 0 1 .5.5z"/>
                </svg>
            </span>
            <span class="text">Kembali ke Daftar Produk</span>
        </a>
    </div>

    <?php if (isset($_SESSION['form_error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error!</strong> <?php echo htmlspecialchars($_SESSION['form_error_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['form_error_message']); ?>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Formulir Produk</h6>
        </div>
        <div class="card-body">
            <form action="../modules/produk/proses_produk.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="aksi" value="<?php echo $mode; ?>">
                <?php if ($mode == 'edit'): ?>
                    <input type="hidden" name="id_produk" value="<?php echo htmlspecialchars($id_produk_edit); ?>">
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="kode_produk" class="form-label">Kode Produk (SKU/Barcode)</label>
                            <input type="text" class="form-control" id="kode_produk" name="kode_produk" value="<?php echo htmlspecialchars($produk['kode_produk']); ?>">
                            <small class="form-text text-muted">Opsional. Biarkan kosong jika tidak ada.</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="nama_produk" class="form-label">Nama Produk <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nama_produk" name="nama_produk" value="<?php echo htmlspecialchars($produk['nama_produk']); ?>" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="id_kategori" class="form-label">Kategori Produk <span class="text-danger">*</span></label>
                            <select class="form-select" id="id_kategori" name="id_kategori" required>
                                <option value="">-- Pilih Kategori --</option>
                                <?php foreach ($kategori_list as $kategori_item): ?>
                                    <option value="<?php echo htmlspecialchars($kategori_item['id_kategori']); ?>" <?php echo ($produk['id_kategori'] == $kategori_item['id_kategori']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($kategori_item['nama_kategori']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="satuan" class="form-label">Satuan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="satuan" name="satuan" value="<?php echo htmlspecialchars($produk['satuan']); ?>" required>
                             <small class="form-text text-muted">Contoh: Pcs, Kg, Liter, Box, Bungkus.</small>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                     <div class="col-md-6">
                        <div class="mb-3">
                            <label for="harga_beli" class="form-label">Harga Beli (Modal)</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control" id="harga_beli" name="harga_beli" value="<?php echo htmlspecialchars($produk['harga_beli']); ?>" step="0.01" min="0">
                            </div>
                             <small class="form-text text-muted">Opsional. Digunakan untuk perhitungan laba.</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="harga_jual" class="form-label">Harga Jual <span class="text-danger">*</span></label>
                             <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control" id="harga_jual" name="harga_jual" value="<?php echo htmlspecialchars($produk['harga_jual']); ?>" step="0.01" min="0" required>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="stok" class="form-label">Stok Awal / Jumlah Stok <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="stok" name="stok" value="<?php echo htmlspecialchars($produk['stok']); ?>" min="0" required>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="deskripsi_produk" class="form-label">Deskripsi Produk</label>
                    <textarea class="form-control" id="deskripsi_produk" name="deskripsi_produk" rows="3"><?php echo htmlspecialchars($produk['deskripsi_produk']); ?></textarea>
                    <small class="form-text text-muted">Opsional. Informasi tambahan mengenai produk.</small>
                </div>

                <hr>
                <div class="d-flex justify-content-end">
                    <a href="produk.php" class="btn btn-secondary me-2">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        <?php echo ($mode == 'edit') ? 'Update Produk' : 'Simpan Produk Baru'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>