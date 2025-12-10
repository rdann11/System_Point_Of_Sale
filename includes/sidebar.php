<?php
// /includes/sidebar.php

$current_page = basename($_SERVER['PHP_SELF']);

$logo_sidebar = null;
$nama_toko_sidebar = 'Sistem POS'; 
if (isset($koneksi)) {
    $sql_pengaturan_sidebar = "SELECT nama_toko, logo_toko FROM pengaturan_toko LIMIT 1";
    $query_sidebar = mysqli_query($koneksi, $sql_pengaturan_sidebar);
    if ($query_sidebar && mysqli_num_rows($query_sidebar) > 0) {
        $data_toko_sidebar = mysqli_fetch_assoc($query_sidebar);
        if (!empty($data_toko_sidebar['logo_toko'])) {
            $logo_sidebar = '../uploads/logo/' . htmlspecialchars($data_toko_sidebar['logo_toko']);
        }
        $nama_toko_sidebar = htmlspecialchars($data_toko_sidebar['nama_toko']);
    }
}
?>
<div class="sidebar border-end bg-dark" data-bs-theme="dark" style="min-width: 280px;" id="appSidebar">
    <div class="p-3">
        <a href="../pages/dashboard.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
            <?php if ($logo_sidebar): ?>
                <img src="<?php echo $logo_sidebar; ?>?t=<?php echo time();?>" alt="Logo" style="max-height: 32px; margin-right: 10px;" class="rounded">
            <?php endif; ?>
            <span class="fs-4 fw-semibold"><?php echo $nama_toko_sidebar; ?></span>
        </a>
        <hr>
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active' : 'text-white'; ?>">
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="produk.php" class="nav-link <?php echo ($current_page == 'produk.php' || $current_page == 'produk_form.php') ? 'active' : 'text-white'; ?>">
                    Produk
                </a>
            </li>
            <li class="nav-item">
                <a href="transaksi.php" class="nav-link <?php echo ($current_page == 'transaksi.php') ? 'active' : 'text-white'; ?>">
                    Transaksi
                </a>
            </li>
            <li class="nav-item">
                <a href="laporan.php" class="nav-link <?php echo ($current_page == 'laporan.php') ? 'active' : 'text-white'; ?>">
                    Laporan
                </a>
            </li>
            
            <?php if (function_exists('get_current_user_role') && get_current_user_role() == 'Admin'): ?>
            <li class="nav-item mt-2 pt-2 border-top">
                <span class="nav-link disabled" style="color: #6c757d;"><small>ADMINISTRASI</small></span>
            </li>
            <li class="nav-item">
                <a href="pengguna.php" class="nav-link <?php echo ($current_page == 'pengguna.php' || $current_page == 'pengguna_form.php') ? 'active' : 'text-white'; ?>">
                    Pengguna
                </a>
            </li>
            <li class="nav-item">
                <a href="pengaturan.php" class="nav-link <?php echo ($current_page == 'pengaturan.php') ? 'active' : 'text-white'; ?>">
                    Pengaturan Toko
                </a>
            </li>
            <?php endif; ?>
        </ul>
        <hr>
        <div class="dropdown">
            <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                <strong><?php echo htmlspecialchars(function_exists('get_current_user_nama') ? (get_current_user_nama() ?? 'Pengguna') : 'Pengguna'); ?></strong>
            </a>
            <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
                <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</div>