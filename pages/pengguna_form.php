<?php
// /pages/pengguna_form.php

$page_title = "Formulir Pengguna"; // Judul default
require_once '../includes/header.php'; // Header akan menjalankan require_login()

// Hanya Admin yang boleh mengakses halaman ini
restrict_access(['Admin'], '../pages/dashboard.php');

// $koneksi sudah tersedia

// Inisialisasi variabel untuk form
$mode = 'tambah'; // Default mode
$id_user_edit = null;
$user_data = [
    'nama_lengkap' => '',
    'username' => '',
    // Password tidak diambil untuk ditampilkan di form
    'role' => ''
];

// Cek apakah ini mode edit (ada parameter id_user di URL)
if (isset($_GET['id_user']) && !empty($_GET['id_user'])) {
    $mode = 'edit';
    $id_user_edit = (int)$_GET['id_user'];
    $page_title = "Edit Pengguna";

    // Tidak boleh edit diri sendiri (untuk mencegah terkunci dari akun admin)
    // Walaupun proses_user.php juga akan ada validasi, lebih baik cegah dari form
    if ($id_user_edit == get_current_user_id()) {
        $_SESSION['flash_message'] = 'Anda tidak dapat mengedit akun Anda sendiri melalui form ini.';
        $_SESSION['flash_message_type'] = 'warning';
        header("Location: pengguna.php");
        exit;
    }

    // Ambil data pengguna yang akan diedit
    $sql_user_edit = "SELECT id_user, nama_lengkap, username, role FROM users WHERE id_user = ?";
    $stmt_user_edit = mysqli_prepare($koneksi, $sql_user_edit);
    mysqli_stmt_bind_param($stmt_user_edit, "i", $id_user_edit);
    mysqli_stmt_execute($stmt_user_edit);
    $result_user_edit = mysqli_stmt_get_result($stmt_user_edit);
    
    if ($data = mysqli_fetch_assoc($result_user_edit)) {
        $user_data = $data;
        $page_title .= ": " . htmlspecialchars($user_data['nama_lengkap']);
    } else {
        $_SESSION['flash_message'] = 'Pengguna yang ingin Anda edit tidak ditemukan.';
        $_SESSION['flash_message_type'] = 'danger';
        header("Location: pengguna.php");
        exit;
    }
    mysqli_stmt_close($stmt_user_edit);
} else {
    $page_title = "Tambah Pengguna Baru";
}

$csrf_token = generate_csrf_token();
$roles = ['Admin', 'Kasir']; // Pilihan peran yang tersedia
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?php echo $page_title; ?></h1>
        <a href="pengguna.php" class="btn btn-secondary btn-icon-split">
            <span class="icon text-white-50">
                 <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left-short" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M12 8a.5.5 0 0 1-.5.5H5.707l2.147 2.146a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L5.707 7.5H11.5a.5.5 0 0 1 .5.5z"/></svg>
            </span>
            <span class="text">Kembali ke Daftar Pengguna</span>
        </a>
    </div>

    <?php if (isset($_SESSION['form_error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error!</strong> <?php echo htmlspecialchars($_SESSION['form_error_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['form_error_message']); ?>
    <?php endif; ?>
     <?php if (isset($_SESSION['validation_errors'])): ?>
        <div class="alert alert-danger" role="alert">
            <h4 class="alert-heading">Oops, ada kesalahan validasi!</h4>
            <ul>
                <?php foreach ($_SESSION['validation_errors'] as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php unset($_SESSION['validation_errors']); ?>
    <?php endif; ?>


    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Formulir Data Pengguna</h6>
        </div>
        <div class="card-body">
            <form action="../modules/user/proses_user.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="aksi" value="<?php echo $mode; ?>">
                <?php if ($mode == 'edit'): ?>
                    <input type="hidden" name="id_user" value="<?php echo htmlspecialchars($id_user_edit); ?>">
                <?php endif; ?>

                <div class="mb-3">
                    <label for="nama_lengkap" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" value="<?php echo htmlspecialchars($user_data['nama_lengkap']); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user_data['username']); ?>" required <?php echo ($mode == 'edit' && $user_data['username'] == 'admin' ? 'readonly' : ''); // Username 'admin' tidak boleh diubah ?>>
                     <?php if ($mode == 'edit' && $user_data['username'] == 'admin'): ?>
                        <small class="form-text text-muted">Username 'admin' tidak dapat diubah.</small>
                    <?php endif; ?>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="password" class="form-label">Password <?php echo ($mode == 'tambah' ? '<span class="text-danger">*</span>' : ''); ?></label>
                            <input type="password" class="form-control" id="password" name="password" <?php echo ($mode == 'tambah' ? 'required' : ''); ?>>
                            <?php if ($mode == 'edit'): ?>
                                <small class="form-text text-muted">Kosongkan jika tidak ingin mengubah password.</small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="konfirmasi_password" class="form-label">Konfirmasi Password <?php echo ($mode == 'tambah' ? '<span class="text-danger">*</span>' : ''); ?></label>
                            <input type="password" class="form-control" id="konfirmasi_password" name="konfirmasi_password" <?php echo ($mode == 'tambah' ? 'required' : ''); ?>>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="role" class="form-label">Peran (Role) <span class="text-danger">*</span></label>
                    <select class="form-select" id="role" name="role" required 
                        <?php echo ($mode == 'edit' && $id_user_edit == get_current_user_id() ? 'disabled' : ''); // Admin tidak bisa ubah role diri sendiri ?>>
                        <option value="">-- Pilih Peran --</option>
                        <?php foreach ($roles as $role_option): ?>
                            <option value="<?php echo $role_option; ?>" <?php echo ($user_data['role'] == $role_option) ? 'selected' : ''; ?>>
                                <?php echo $role_option; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                     <?php if ($mode == 'edit' && $id_user_edit == get_current_user_id()): ?>
                        <small class="form-text text-muted">Anda tidak dapat mengubah peran akun Anda sendiri.</small>
                         <input type="hidden" name="role" value="<?php echo htmlspecialchars($user_data['role']); // Kirim role lama jika disabled ?>">
                    <?php endif; ?>
                </div>

                <hr>
                <div class="d-flex justify-content-end">
                    <a href="pengguna.php" class="btn btn-secondary me-2">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        <?php echo ($mode == 'edit') ? 'Update Pengguna' : 'Simpan Pengguna Baru'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>