-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 26 Jun 2025 pada 13.48
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_pos_website`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `kategori`
--

CREATE TABLE `kategori` (
  `id_kategori` int(11) NOT NULL,
  `nama_kategori` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `kategori`
--

INSERT INTO `kategori` (`id_kategori`, `nama_kategori`, `created_at`, `updated_at`) VALUES
(1, 'Makanan Ringan', '2025-05-22 13:06:22', '2025-05-22 13:06:22'),
(2, 'Minuman Dingin', '2025-05-22 13:06:22', '2025-05-22 13:06:22'),
(3, 'Perlengkapan Mandi', '2025-05-22 13:06:22', '2025-05-22 13:06:22'),
(4, 'Alat Tulis Kantor', '2025-05-22 13:06:22', '2025-05-22 13:06:22'),
(5, 'Sembako', '2025-05-22 13:06:22', '2025-05-22 13:06:22');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengaturan_toko`
--

CREATE TABLE `pengaturan_toko` (
  `id_pengaturan` int(11) NOT NULL,
  `nama_toko` varchar(100) NOT NULL DEFAULT 'Nama Toko Saya',
  `alamat_toko` text DEFAULT NULL,
  `telepon_toko` varchar(20) DEFAULT NULL,
  `email_toko` varchar(100) DEFAULT NULL,
  `logo_toko` varchar(255) DEFAULT NULL,
  `catatan_struk` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pengaturan_toko`
--

INSERT INTO `pengaturan_toko` (`id_pengaturan`, `nama_toko`, `alamat_toko`, `telepon_toko`, `email_toko`, `logo_toko`, `catatan_struk`) VALUES
(1, 'Toko Sejahtera', 'Jl. Pembangunan No. 2, Kota Kode', '081234567891', 'info@SejahteraMakmura.com', 'logo_toko_1747930170.png', 'Terima kasih atas kunjungan Anda!');

-- --------------------------------------------------------

--
-- Struktur dari tabel `produk`
--

CREATE TABLE `produk` (
  `id_produk` int(11) NOT NULL,
  `kode_produk` varchar(20) DEFAULT NULL,
  `nama_produk` varchar(255) NOT NULL,
  `id_kategori` int(11) NOT NULL,
  `harga_beli` decimal(10,2) DEFAULT 0.00,
  `harga_jual` decimal(10,2) NOT NULL,
  `stok` int(11) NOT NULL DEFAULT 0,
  `satuan` varchar(50) DEFAULT 'Pcs',
  `deskripsi_produk` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `produk`
--

INSERT INTO `produk` (`id_produk`, `kode_produk`, `nama_produk`, `id_kategori`, `harga_beli`, `harga_jual`, `stok`, `satuan`, `deskripsi_produk`, `created_at`, `updated_at`) VALUES
(2, 'AM001', 'Air Mineral Botol 600ml', 2, 2000.00, 3000.00, 292, 'Botol', '', '2025-05-22 13:06:57', '2025-06-14 16:36:36'),
(5, 'AM002', 'Kripik Kentang Pedas', 1, 3000.00, 4000.00, 2000, 'Pcs', 'kripik kentang enak', '2025-05-28 17:32:03', '2025-05-28 17:32:44');

-- --------------------------------------------------------

--
-- Struktur dari tabel `transaksi`
--

CREATE TABLE `transaksi` (
  `id_transaksi` int(11) NOT NULL,
  `kode_transaksi` varchar(30) NOT NULL,
  `id_user` int(11) NOT NULL,
  `waktu_transaksi` timestamp NOT NULL DEFAULT current_timestamp(),
  `total_harga_produk` decimal(12,2) NOT NULL DEFAULT 0.00,
  `diskon_persen_global` decimal(5,2) DEFAULT 0.00,
  `diskon_nominal_global` decimal(12,2) DEFAULT 0.00,
  `pajak_persen_global` decimal(5,2) DEFAULT 0.00,
  `total_setelah_diskon_pajak` decimal(12,2) NOT NULL DEFAULT 0.00,
  `uang_diterima` decimal(12,2) NOT NULL DEFAULT 0.00,
  `kembalian` decimal(12,2) NOT NULL DEFAULT 0.00,
  `catatan_transaksi` text DEFAULT NULL,
  `status_transaksi` enum('Selesai','Pending','Batal') DEFAULT 'Selesai',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `transaksi`
--

INSERT INTO `transaksi` (`id_transaksi`, `kode_transaksi`, `id_user`, `waktu_transaksi`, `total_harga_produk`, `diskon_persen_global`, `diskon_nominal_global`, `pajak_persen_global`, `total_setelah_diskon_pajak`, `uang_diterima`, `kembalian`, `catatan_transaksi`, `status_transaksi`, `created_at`, `updated_at`) VALUES
(1, 'INV-20250522-0001', 1, '2025-05-22 15:35:36', 33000.00, 0.00, 0.00, 0.00, 33000.00, 50000.00, 17000.00, 'terimakasih telah berbelanja di Toko Maju Jaya', 'Selesai', '2025-05-22 15:35:36', '2025-05-22 15:35:36'),
(2, 'INV-20250522-0002', 1, '2025-05-22 15:46:26', 3000.00, 0.00, 0.00, 0.00, 3000.00, 5000.00, 2000.00, '', 'Selesai', '2025-05-22 15:46:26', '2025-05-22 15:46:26'),
(3, 'INV-20250522-0003', 1, '2025-05-22 15:48:08', 3000.00, 0.00, 0.00, 0.00, 3000.00, 5000.00, 2000.00, '', 'Selesai', '2025-05-22 15:48:08', '2025-05-22 15:48:08'),
(4, 'INV-20250522-0004', 1, '2025-05-22 16:16:40', 3000.00, 0.00, 0.00, 0.00, 3000.00, 5000.00, 2000.00, '', 'Selesai', '2025-05-22 16:16:40', '2025-05-22 16:16:40'),
(5, 'INV-20250524-0001', 1, '2025-05-23 17:46:38', 3000.00, 0.00, 0.00, 0.00, 3000.00, 5000.00, 2000.00, '', 'Selesai', '2025-05-23 17:46:38', '2025-05-23 17:46:38'),
(6, 'INV-20250529-0001', 4, '2025-05-28 17:24:33', 3000.00, 0.00, 0.00, 0.00, 3000.00, 5000.00, 2000.00, '', 'Selesai', '2025-05-28 17:24:33', '2025-05-28 17:24:33'),
(7, 'INV-20250612-0001', 1, '2025-06-12 16:10:28', 3000.00, 0.00, 0.00, 0.00, 3000.00, 5000.00, 2000.00, 'bagus', 'Selesai', '2025-06-12 16:10:28', '2025-06-12 16:10:28'),
(8, 'INV-20250614-0001', 1, '2025-06-14 16:36:36', 3000.00, 0.00, 0.00, 0.00, 3000.00, 5000.00, 2000.00, '', 'Selesai', '2025-06-14 16:36:36', '2025-06-14 16:36:36');

-- --------------------------------------------------------

--
-- Struktur dari tabel `transaksi_detail`
--

CREATE TABLE `transaksi_detail` (
  `id_transaksi_detail` int(11) NOT NULL,
  `id_transaksi` int(11) NOT NULL,
  `id_produk` int(11) NOT NULL,
  `nama_produk_saat_transaksi` varchar(255) NOT NULL,
  `harga_produk_saat_transaksi` decimal(10,2) NOT NULL,
  `jumlah_beli` int(11) NOT NULL,
  `subtotal_produk` decimal(12,2) NOT NULL,
  `diskon_item_persen` decimal(5,2) DEFAULT 0.00,
  `diskon_item_nominal` decimal(10,2) DEFAULT 0.00,
  `subtotal_setelah_diskon_item` decimal(12,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `transaksi_detail`
--

INSERT INTO `transaksi_detail` (`id_transaksi_detail`, `id_transaksi`, `id_produk`, `nama_produk_saat_transaksi`, `harga_produk_saat_transaksi`, `jumlah_beli`, `subtotal_produk`, `diskon_item_persen`, `diskon_item_nominal`, `subtotal_setelah_diskon_item`, `created_at`) VALUES
(7, 6, 2, 'Air Mineral Botol 600ml', 3000.00, 1, 3000.00, 0.00, 0.00, 3000.00, '2025-05-28 17:24:33'),
(8, 7, 2, 'Air Mineral Botol 600ml', 3000.00, 1, 3000.00, 0.00, 0.00, 3000.00, '2025-06-12 16:10:28'),
(9, 8, 2, 'Air Mineral Botol 600ml', 3000.00, 1, 3000.00, 0.00, 0.00, 3000.00, '2025-06-14 16:36:36');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id_user` int(11) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Admin','Kasir') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id_user`, `nama_lengkap`, `username`, `password`, `role`, `created_at`, `updated_at`) VALUES
(1, 'Administrator Utama', 'admin', '$2a$12$p/42qy4.eAUb0mdxQfJ1yONmpk9qL4DBp3REgzMhy4Hl66bg/gR7W', 'Admin', '2025-05-22 12:32:17', '2025-05-22 13:01:46'),
(4, 'kasir', 'kasir', '$2y$10$Gj5cmwueMFuThJ13cx65YObZyc/pJk5j.KLZhQsfFWc9cw/ZuQClS', 'Kasir', '2025-05-28 17:24:02', '2025-06-26 11:37:46');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id_kategori`),
  ADD UNIQUE KEY `nama_kategori` (`nama_kategori`);

--
-- Indeks untuk tabel `pengaturan_toko`
--
ALTER TABLE `pengaturan_toko`
  ADD PRIMARY KEY (`id_pengaturan`);

--
-- Indeks untuk tabel `produk`
--
ALTER TABLE `produk`
  ADD PRIMARY KEY (`id_produk`),
  ADD UNIQUE KEY `kode_produk` (`kode_produk`),
  ADD KEY `id_kategori` (`id_kategori`);

--
-- Indeks untuk tabel `transaksi`
--
ALTER TABLE `transaksi`
  ADD PRIMARY KEY (`id_transaksi`),
  ADD UNIQUE KEY `kode_transaksi` (`kode_transaksi`),
  ADD KEY `id_user` (`id_user`);

--
-- Indeks untuk tabel `transaksi_detail`
--
ALTER TABLE `transaksi_detail`
  ADD PRIMARY KEY (`id_transaksi_detail`),
  ADD KEY `id_transaksi` (`id_transaksi`),
  ADD KEY `id_produk` (`id_produk`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id_kategori` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `pengaturan_toko`
--
ALTER TABLE `pengaturan_toko`
  MODIFY `id_pengaturan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `produk`
--
ALTER TABLE `produk`
  MODIFY `id_produk` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `transaksi`
--
ALTER TABLE `transaksi`
  MODIFY `id_transaksi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `transaksi_detail`
--
ALTER TABLE `transaksi_detail`
  MODIFY `id_transaksi_detail` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `produk`
--
ALTER TABLE `produk`
  ADD CONSTRAINT `produk_ibfk_1` FOREIGN KEY (`id_kategori`) REFERENCES `kategori` (`id_kategori`) ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `transaksi`
--
ALTER TABLE `transaksi`
  ADD CONSTRAINT `transaksi_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `transaksi_detail`
--
ALTER TABLE `transaksi_detail`
  ADD CONSTRAINT `transaksi_detail_ibfk_1` FOREIGN KEY (`id_transaksi`) REFERENCES `transaksi` (`id_transaksi`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `transaksi_detail_ibfk_2` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id_produk`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
