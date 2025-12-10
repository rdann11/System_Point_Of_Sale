<?php
// /pages/login.php

// Pastikan session dimulai di awal, sebelum output apapun
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Sertakan file koneksi database dan fungsi autentikasi
require_once '../includes/db.php'; // $koneksi akan tersedia dari sini
require_once '../includes/auth.php'; // Untuk fungsi CSRF dan lainnya

// Jika pengguna sudah login, arahkan ke dashboard
if (is_logged_in()) { // Menggunakan fungsi dari auth.php
    header("Location: dashboard.php");
    exit;
}

$error_message = ''; // Variabel untuk menyimpan pesan error

// Proses form login jika metode adalah POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Verifikasi CSRF Token
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error_message = 'Terjadi masalah keamanan (CSRF token tidak valid). Silakan coba lagi.';
    } else {
        // 2. Ambil dan sanitasi input (username cukup di-trim)
        $username = trim($_POST['username']);
        $password = $_POST['password']; // Password tidak perlu sanitasi khusus sebelum password_verify

        // 3. Validasi dasar input server-side
        if (empty($username) || empty($password)) {
            $error_message = 'Username dan password tidak boleh kosong.';
        } else {
            // 4. Ambil data pengguna dari database menggunakan prepared statement
            $sql = "SELECT id_user, username, password, nama_lengkap, role FROM users WHERE username = ?";
            $stmt = mysqli_prepare($koneksi, $sql);
            
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "s", $username);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $user = mysqli_fetch_assoc($result);
                mysqli_stmt_close($stmt);

                if ($user) {
                    // 5. Verifikasi password
                    if (password_verify($password, $user['password'])) {
                        // 6. Password cocok, login berhasil!
                        session_regenerate_id(true); // Regenerasi ID session untuk keamanan

                        // Simpan informasi pengguna ke session
                        $_SESSION['user_id'] = $user['id_user'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                        $_SESSION['role'] = $user['role'];
                        
                        // Hapus CSRF token dari session setelah berhasil login
                        unset($_SESSION['csrf_token']); 

                        // Arahkan ke dashboard
                        header("Location: dashboard.php");
                        exit;
                    } else {
                        // Password tidak cocok
                        $error_message = 'Username atau password salah.';
                    }
                } else {
                    // Username tidak ditemukan
                    $error_message = 'Username atau password salah.';
                }
            } else {
                // Error pada prepared statement
                $error_message = 'Terjadi kesalahan pada sistem. Silakan coba lagi nanti.';
                // Sebaiknya log error ini: mysqli_error($koneksi)
            }
        }
    }
    // Setelah proses POST, selalu generate ulang CSRF token untuk form jika ada error
    // karena token lama mungkin sudah tidak valid atau untuk keamanan tambahan.
    // Namun, karena kita redirect atau exit, token baru akan digenerate saat halaman login dimuat ulang.
    // Jika tidak redirect dan hanya menampilkan error di halaman yang sama, maka perlu:
    // unset($_SESSION['csrf_token']); // Agar yang baru digenerate di bawah
}

// Selalu generate CSRF token untuk form login jika belum ada atau setelah diproses
// (Fungsi generate_csrf_token() di auth.php sudah menangani jika sudah ada)
$csrf_token = generate_csrf_token();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZULL STORE</title>
    <link href="../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            padding: 25px;
            border-radius: 8px;
        }
        .logo-bulat {
            width: 100px; /* Atur lebar sesuai keinginan */
            height: 100px; /* Atur tinggi sesuai keinginan */
            border-radius: 50%; /* Membuat gambar menjadi bulat */
            object-fit: cover; /* Memastikan gambar mengisi area tanpa terdistorsi */
       
            padding: 5px; /* Opsional: jarak antara gambar dan border */
            display: block;
        margin: 0 auto;
        }
    </style>
</head>
<body>
 <div class="card login-card shadow-sm">
 <div class="card-body">
             <img src="../gambar/logo.png" alt="Logo Zull Store" class="logo-bulat mt-2 mb-3"> 

             <h3 class="card-title text-center mb-4">ZULL STORE</h3>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required autofocus>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Login</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
</body>
</html>