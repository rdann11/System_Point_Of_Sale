<?php
// /pages/pengaturan.php

$page_title = "Pengaturan Toko";
require_once '../includes/header.php'; // Header akan menjalankan require_login() dan auth.php

// Hanya Admin yang boleh mengakses halaman ini
restrict_access(['Admin'], '../pages/dashboard.php'); // Redirect ke dashboard jika bukan Admin

// $koneksi sudah tersedia dari db.php yang di-include oleh auth.php -> header.php

// Ambil data pengaturan toko saat ini (diasumsikan hanya ada 1 baris, atau ambil yang pertama)
$pengaturan = null;
$sql_get_pengaturan = "SELECT * FROM pengaturan_toko LIMIT 1";
$query_pengaturan = mysqli_query($koneksi, $sql_get_pengaturan);
if ($query_pengaturan && mysqli_num_rows($query_pengaturan) > 0) {
    $pengaturan = mysqli_fetch_assoc($query_pengaturan);
} else {
    // Jika tidak ada data sama sekali, kita bisa set default atau berikan pesan.
    // Untuk form, kita bisa set nilai default agar tidak error.
    // Kita juga bisa melakukan INSERT data default di sini jika tabel kosong,
    // tapi karena kita sudah INSERT di awal, seharusnya ini tidak terjadi.
    $pengaturan = [
        'id_pengaturan' => null, // Atau 1 jika kita selalu menargetkan ID tertentu
        'nama_toko' => 'Nama Toko Anda',
        'alamat_toko' => '',
        'telepon_toko' => '',
        'email_toko' => '',
        'logo_toko' => null,
        'catatan_struk' => 'Terima kasih telah berbelanja!'
    ];
    // Jika tabel benar-benar kosong dan ini pertama kali, mungkin lebih baik insert row default di sini.
    // Untuk saat ini, kita asumsikan data sudah ada dari Langkah 1.4
}

$csrf_token = generate_csrf_token();
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?php echo $page_title; ?></h1>
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
            <h6 class="m-0 font-weight-bold text-primary">Formulir Pengaturan Toko</h6>
        </div>
        <div class="card-body">
            <form action="../modules/pengaturan/proses_pengaturan.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <?php if ($pengaturan && isset($pengaturan['id_pengaturan'])): ?>
                    <input type="hidden" name="id_pengaturan" value="<?php echo htmlspecialchars($pengaturan['id_pengaturan']); ?>">
                <?php endif; ?>

                <div class="mb-3">
                    <label for="nama_toko" class="form-label">Nama Toko <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nama_toko" name="nama_toko" value="<?php echo htmlspecialchars($pengaturan['nama_toko'] ?? ''); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="alamat_toko" class="form-label">Alamat Toko</label>
                    <textarea class="form-control" id="alamat_toko" name="alamat_toko" rows="3"><?php echo htmlspecialchars($pengaturan['alamat_toko'] ?? ''); ?></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="telepon_toko" class="form-label">Nomor Telepon</label>
                            <input type="text" class="form-control" id="telepon_toko" name="telepon_toko" value="<?php echo htmlspecialchars($pengaturan['telepon_toko'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="email_toko" class="form-label">Alamat Email</label>
                            <input type="email" class="form-control" id="email_toko" name="email_toko" value="<?php echo htmlspecialchars($pengaturan['email_toko'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="catatan_struk" class="form-label">Catatan Kaki Struk</label>
                    <textarea class="form-control" id="catatan_struk" name="catatan_struk" rows="2"><?php echo htmlspecialchars($pengaturan['catatan_struk'] ?? ''); ?></textarea>
                    <small class="form-text text-muted">Misal: "Terima kasih atas kunjungan Anda!"</small>
                </div>
                
                <hr>
                <h6 class="mb-3">Logo Toko</h6>
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="logo_toko_file" class="form-label">Unggah Logo Baru</label>
                            <input class="form-control" type="file" id="logo_toko_file" name="logo_toko_file" accept="image/png, image/jpeg, image/gif">
                            <small class="form-text text-muted">Format yang didukung: PNG, JPG, GIF. Maksimal 2MB. Biarkan kosong jika tidak ingin mengubah logo.</small>
                        </div>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <?php if (!empty($pengaturan['logo_toko'])): ?>
                            <p class="mb-1">Logo Saat Ini:</p>
                            <img src="../uploads/logo/<?php echo htmlspecialchars($pengaturan['logo_toko']); ?>?t=<?php echo time(); // Cache buster ?>" alt="Logo Toko" class="img-thumbnail mb-2" style="max-height: 100px; max-width: 200px;">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="hapus_logo_sekarang" name="hapus_logo_sekarang">
                                <label class="form-check-label" for="hapus_logo_sekarang">
                                    Hapus logo saat ini
                                </label>
                            </div>
                        <?php else: ?>
                            <p>Belum ada logo.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <hr>
                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">
                        Simpan Pengaturan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>