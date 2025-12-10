<?php
// /includes/footer.php
?>
            </div> </div> </div> </div> <footer class="footer mt-auto py-3 bg-body-tertiary border-top"> <?php // Pastikan ada class "mt-auto" di sini ?>
    <div class="container text-center">
        <span class="text-muted">&copy; <?php echo date("Y"); ?> <?php echo htmlspecialchars($nama_toko ?? 'Sistem POS'); ?>. All rights reserved.</span>
    </div>
</footer>

<script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>

<script src="../assets/js/script.js"></script>

<?php
// Ini adalah contoh jika Anda ingin menampilkan flash message standar Bootstrap dari session
// Jika Anda sudah punya cara lain atau menampilkannya di tiap halaman /pages/, ini bisa disesuaikan atau dihapus.
// Untuk sekarang, kita biarkan agar ada contoh penanganan flash message jika SweetAlert tidak digunakan.
if (isset($_SESSION['flash_message']) && isset($_SESSION['flash_message_type'])) {
    echo '<div class="alert alert-'.htmlspecialchars($_SESSION['flash_message_type']).' alert-dismissible fade show m-3 position-fixed bottom-0 end-0" role="alert" style="z-index: 1050;">
            '.htmlspecialchars($_SESSION['flash_message']).'
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_message_type']);
}
?>
</body>
</html>