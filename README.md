**1** Kebutuhan Sistem (System Requirements)
Sebelum melakukan instalasi, pastikan komputer atau server telah memenuhi kebutuhan perangkat lunak berikut:

●	PHP versi 8.4 atau lebih baru.

●	Web Server menggunakan XAMPP / Laragon (atau sejenisnya yang mendukung Apache dan MySQL)

●	Database menggunakan MySQL/MariaDB (via XAMPP atau Laragon)

**2** Langkah Instalasi
Ikuti Langkah-langkah berikut untuk melakukan instalasi proyek:
1)	Mengunduh Source Code
1.	Buka halaman repository GitHub proyek.
2.	Klik tombol Code (warna hijau).
3.	Pilih Download ZIP.
4.	Setelah file ZIP berhasil diunduh, lakukan extract.
5.	Pindahkan folder hasil extract ke direktori server lokal:
•	Untuk XAMPP → C:\xampp\htdocs\
•	Untuk Laragon → C:\laragon\www\
Contoh:
C:\xampp\htdocs\pos_project
2)	Konfigurasi Database
Karena proyek bersifat PHP native, database harus disiapkan secara manual melalui phpMyAdmin.
1.	Jalankan Apache dan MySQL melalui XAMPP/Laragon.
2.	Buka browser dan akses: http://localhost/phpmyadmin Atau Click bagian admin di xampp
3.	Klik Database → buat database baru, misalnya: pos_db
4.	Pilih database tersebut → klik tab Import.
5.	Upload file SQL bawaan proyek, bernama: db_pos_website.sql.sql
6.	Klik Go untuk menyelesaikan proses import.

3)	Penyesuaian Konfigurasi Koneksi
Sesuaikan pengaturan koneksi seperti berikut:
$host = "localhost";
$user = "root"; // Ganti dengan user anda
$pass = ""; // Ganti dengan Password anda
$db   = "pos_db"; // Ganti dengan nama database yang telah di buat
Pastikan nama database sama dengan yang dibuat di phpMyAdmin.
4)	Menjalankan Proyek
Setelah semua konfigurasi selesai, proyek dapat dijalankan melalui browser dengan format: http://localhost/nama_folder_project
Contoh: http://localhost/pos_project
Sistem POS sekarang siap digunakan.

**3** Panduan Penggunaan (Akun Demo)
Untuk keperluan pengujian sistem, berikut adalah daftar akun default yang telah disediakan berdasarkan hak aksesnya:

Peran (Role)

Admin	= user : admin	 pw : admin123

Kasir	= user : kasir   pw :	kasir1234
