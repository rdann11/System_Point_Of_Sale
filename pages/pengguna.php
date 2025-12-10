<?php
// /pages/pengguna.php

$page_title = "Manajemen Pengguna";
require_once '../includes/header.php'; // Header akan menjalankan require_login()

// Hanya Admin yang boleh mengakses halaman ini
restrict_access(['Admin'], '../pages/dashboard.php'); // Redirect ke dashboard jika bukan Admin

// $koneksi sudah tersedia dari db.php -> auth.php -> header.php

// Ambil semua pengguna dari database
// Hindari mengambil password, meskipun sudah di-hash, tidak perlu ditampilkan
$sql_users = "SELECT id_user, nama_lengkap, username, role, created_at FROM users ORDER BY nama_lengkap ASC";
$query_users = mysqli_query($koneksi, $sql_users);

if (!$query_users) {
    die("Query gagal mengambil data pengguna: " . mysqli_error($koneksi));
}

$current_user_id_logged_in = get_current_user_id(); // Untuk mencegah admin menghapus dirinya sendiri
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?php echo $page_title; ?></h1>
        <a href="pengguna_form.php" class="btn btn-success btn-icon-split">
            <span class="icon text-white-50">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-plus-fill" viewBox="0 0 16 16">
                  <path d="M1 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H1zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
                  <path fill-rule="evenodd" d="M13.5 5a.5.5 0 0 1 .5.5V7h1.5a.5.5 0 0 1 0 1H14v1.5a.5.5 0 0 1-1 0V8h-1.5a.5.5 0 0 1 0-1H13V5.5a.5.5 0 0 1 .5-.5z"/>
                </svg>
            </span>
            <span class="text">Tambah Pengguna Baru</span>
        </a>
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
            <h6 class="m-0 font-weight-bold text-primary">Daftar Pengguna Sistem</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTablePengguna" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Nama Lengkap</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Terdaftar Sejak</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($query_users) > 0): ?>
                            <?php $nomor = 1; ?>
                            <?php while($user = mysqli_fetch_assoc($query_users)): ?>
                            <tr>
                                <td><?php echo $nomor++; ?></td>
                                <td><?php echo htmlspecialchars($user['nama_lengkap']); ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><span class="badge bg-<?php echo ($user['role'] == 'Admin' ? 'danger' : 'primary'); ?>"><?php echo htmlspecialchars($user['role']); ?></span></td>
                                <td><?php echo date("d/m/Y H:i", strtotime($user['created_at'])); ?></td>
                                <td>
                                    <a href="pengguna_form.php?id_user=<?php echo $user['id_user']; ?>" class="btn btn-sm btn-warning mb-1" title="Edit">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16">
                                          <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/>
                                          <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5v11z"/>
                                        </svg> Edit
                                    </a>
                                    <?php if ($user['id_user'] != $current_user_id_logged_in): // Admin tidak bisa hapus diri sendiri ?>
                                        <a href="#" onclick="konfirmasiHapusUser(<?php echo $user['id_user']; ?>, '<?php echo htmlspecialchars(addslashes($user['nama_lengkap'])); ?>', '<?php echo htmlspecialchars(addslashes($user['username'])); ?>')" class="btn btn-sm btn-danger mb-1" title="Hapus">
                                             <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash3-fill" viewBox="0 0 16 16">
                                              <path d="M11 1.5v1h3.5a.5.5 0 0 1 0 1h-.538l-.853 10.66A2 2 0 0 1 11.115 16h-6.23a2 2 0 0 1-1.994-1.84L2.038 3.5H1.5a.5.5 0 0 1 0-1H5v-1A1.5 1.5 0 0 1 6.5 0h3A1.5 1.5 0 0 1 11 1.5Zm-5 0v1h4v-1a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5ZM4.5 5.029l.5 8.5a.5.5 0 1 0 .998-.06l-.5-8.5a.5.5 0 1 0-.998.06Zm6.53-.528a.5.5 0 0 0-.528.47l-.5 8.5a.5.5 0 0 0 .998.058l.5-8.5a.5.5 0 0 0-.47-.528ZM8 4.5a.5.5 0 0 0-.5.5v8.5a.5.5 0 0 0 1 0V5a.5.5 0 0 0-.5-.5Z"/>
                                            </svg> Hapus
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-secondary mb-1" disabled title="Tidak dapat menghapus akun sendiri">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash3-fill" viewBox="0 0 16 16"><path d="M11 1.5v1h3.5a.5.5 0 0 1 0 1h-.538l-.853 10.66A2 2 0 0 1 11.115 16h-6.23a2 2 0 0 1-1.994-1.84L2.038 3.5H1.5a.5.5 0 0 1 0-1H5v-1A1.5 1.5 0 0 1 6.5 0h3A1.5 1.5 0 0 1 11 1.5Zm-5 0v1h4v-1a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5ZM4.5 5.029l.5 8.5a.5.5 0 1 0 .998-.06l-.5-8.5a.5.5 0 1 0-.998.06Zm6.53-.528a.5.5 0 0 0-.528.47l-.5 8.5a.5.5 0 0 0 .998.058l.5-8.5a.5.5 0 0 0-.47-.528ZM8 4.5a.5.5 0 0 0-.5.5v8.5a.5.5 0 0 0 1 0V5a.5.5 0 0 0-.5-.5Z"/></svg> Hapus
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">Belum ada data pengguna.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Fungsi konfirmasi hapus pengguna (menggunakan POST seperti pada produk)
function konfirmasiHapusUser(idUser, namaLengkap, username) {
    if (confirm(`Apakah Anda yakin ingin menghapus pengguna "${namaLengkap}" (username: ${username})? Tindakan ini tidak dapat dibatalkan.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '../modules/user/proses_user.php'; // Akan kita buat

        const inputAksi = document.createElement('input');
        inputAksi.type = 'hidden';
        inputAksi.name = 'aksi';
        inputAksi.value = 'hapus';
        form.appendChild(inputAksi);

        const inputId = document.createElement('input');
        inputId.type = 'hidden';
        inputId.name = 'id_user';
        inputId.value = idUser;
        form.appendChild(inputId);

        const inputCsrf = document.createElement('input');
        inputCsrf.type = 'hidden';
        inputCsrf.name = 'csrf_token';
        inputCsrf.value = '<?php echo htmlspecialchars(generate_csrf_token()); ?>';
        form.appendChild(inputCsrf);

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    }
}
</script>

<?php
// Baris mysqli_stmt_close($stmt_transaksi ?? null); sudah dihapus atau dikomentari
if (isset($query_users)) { 
   // mysqli_free_result($query_users); // Baris ini juga opsional untuk mysqli_query
}
require_once '../includes/footer.php';
?>