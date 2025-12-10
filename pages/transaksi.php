<?php
// /pages/transaksi.php

$page_title = "Input Transaksi Penjualan";
require_once '../includes/header.php'; // Header akan menjalankan require_login() dan auth.php

// $koneksi sudah tersedia
// $current_user_id = get_current_user_id(); // ID kasir yang login
// $current_user_nama = get_current_user_nama(); // Nama kasir

// Ambil daftar produk untuk pemilihan (untuk awal kita load semua, nanti bisa AJAX)
$produk_list = [];
$sql_produk = "SELECT id_produk, kode_produk, nama_produk, harga_jual, stok FROM produk WHERE stok > 0 ORDER BY nama_produk ASC";
$query_produk = mysqli_query($koneksi, $sql_produk);
if ($query_produk) {
    while ($row = mysqli_fetch_assoc($query_produk)) {
        $produk_list[] = $row;
    }
}

$csrf_token = generate_csrf_token();
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?php echo $page_title; ?></h1>
        <div>
            <span class="text-muted">Kasir: <?php echo htmlspecialchars(get_current_user_nama() ?? 'Unknown'); ?></span> |
            <span class="text-muted">Tanggal: <?php echo date("d M Y"); ?></span>
        </div>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['flash_message_type']; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['flash_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_message']); unset($_SESSION['flash_message_type']); ?>
    <?php endif; ?>

    <form id="formTransaksi" action="../modules/transaksi/proses_transaksi.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
        <input type="hidden" name="id_user_kasir" value="<?php echo htmlspecialchars(get_current_user_id() ?? 0); ?>">

        <div class="row">
            <div class="col-lg-7 mb-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Pilih Produk</h6>
                    </div>
                    <div class="card-body">
                        <div class="input-group mb-3">
                            <select class="form-select" id="pilihProduk">
                                <option value="">-- Ketik atau Pilih Produk --</option>
                                <?php foreach($produk_list as $p): ?>
                                    <option value="<?php echo $p['id_produk']; ?>" 
                                            data-nama="<?php echo htmlspecialchars($p['nama_produk']); ?>"
                                            data-harga="<?php echo $p['harga_jual']; ?>"
                                            data-stok="<?php echo $p['stok']; ?>">
                                        <?php echo htmlspecialchars($p['nama_produk']); ?> (Stok: <?php echo $p['stok']; ?>) - <?php echo "Rp " . number_format($p['harga_jual'], 0, ',', '.'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-primary" type="button" id="tombolTambahKeKeranjang">Tambah</button>
                        </div>
                        
                        <h6 class="mt-4">Keranjang Belanja</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="tabelKeranjang">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Produk</th>
                                        <th width="100px">Qty</th>
                                        <th>Harga</th>
                                        <th>Subtotal</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="keranjangItem">
                                    <tr>
                                        <td colspan="6" class="text-center">Keranjang masih kosong.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5 mb-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Pembayaran</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3 row">
                            <label class="col-sm-5 col-form-label">Total Item</label>
                            <div class="col-sm-7">
                                <input type="text" readonly class="form-control-plaintext fw-bold" id="displayTotalItem" value="0">
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-5 col-form-label">Subtotal Produk</label>
                            <div class="col-sm-7">
                                <input type="text" readonly class="form-control-plaintext fw-bold fs-5 text-danger" id="displaySubtotalProduk" value="Rp 0">
                            </div>
                        </div>
                        
                        <?php /* <div class="mb-3 row">
                            <label for="diskonGlobalPersen" class="col-sm-5 col-form-label">Diskon Global (%)</label>
                            <div class="col-sm-7">
                                <input type="number" class="form-control" id="diskonGlobalPersen" name="diskon_global_persen" value="0" min="0" max="100" step="0.01">
                            </div>
                        </div>
                         <div class="mb-3 row">
                            <label for="diskonGlobalNominal" class="col-sm-5 col-form-label">Diskon Global (Rp)</label>
                            <div class="col-sm-7">
                                <input type="number" class="form-control" id="diskonGlobalNominal" name="diskon_global_nominal" value="0" min="0" step="100">
                            </div>
                        </div>
                        */ ?>

                        <hr>
                        <div class="mb-3 row">
                            <label class="col-sm-5 col-form-label fw-bold fs-4">TOTAL BAYAR</label>
                            <div class="col-sm-7">
                                <input type="text" readonly class="form-control-plaintext fw-bold fs-4 text-success" id="displayTotalBayar" value="Rp 0">
                            </div>
                        </div>
                        <hr>

                        <div class="mb-3">
                            <label for="uangDiterima" class="form-label fw-bold">Uang Diterima (Rp) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control form-control-lg" id="uangDiterima" name="uang_diterima" required min="0" placeholder="0">
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-5 col-form-label">Kembalian</label>
                            <div class="col-sm-7">
                                <input type="text" readonly class="form-control-plaintext fw-bold fs-5 text-primary" id="displayKembalian" value="Rp 0">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="catatanTransaksi" class="form-label">Catatan Transaksi (Opsional)</label>
                            <textarea class="form-control" id="catatanTransaksi" name="catatan_transaksi" rows="2"></textarea>
                        </div>

                        <input type="hidden" name="total_harga_produk_hidden" id="totalHargaProdukHidden" value="0">
                        <input type="hidden" name="total_setelah_diskon_pajak_hidden" id="totalSetelahDiskonPajakHidden" value="0">
                        <input type="hidden" name="kembalian_hidden" id="kembalianHidden" value="0">
                        <input type="hidden" name="detail_item_json" id="detailItemJson">


                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-lg btn-success" id="tombolSimpanTransaksi" disabled>
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-save-fill me-2" viewBox="0 0 16 16">
                                  <path d="M8.5 1.5A1.5 1.5 0 0 1 10 0h4a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h6c-.314.418-.5.937-.5 1.5v7.793L6.354 9.146a.5.5 0 1 0-.708.708l3 3a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5z"/>
                                </svg>
                                Simpan Transaksi
                            </button>
                            <button type="button" class="btn btn-secondary" id="tombolResetTransaksi">Reset Transaksi</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php
// Kita akan tambahkan JavaScript di sini pada langkah berikutnya
// atau di file /assets/js/script_transaksi.js
?>

<script>
// Placeholder untuk JavaScript interaktif - akan diisi di langkah berikutnya
document.addEventListener('DOMContentLoaded', function() {
    console.log('Halaman transaksi siap untuk interaksi JavaScript.');

    // Contoh sederhana: menonaktifkan tombol simpan jika keranjang kosong
    const tombolSimpan = document.getElementById('tombolSimpanTransaksi');
    const keranjangItemTbody = document.getElementById('keranjangItem');

    function cekStatusTombolSimpan() {
        // Jika ada baris di tbody keranjang selain baris "Keranjang masih kosong"
        if (keranjangItemTbody.querySelector('tr:not(:only-child)')) { // kurang tepat jika baris kosongnya masih ada
             // Cek jika ada item yang valid (memiliki class atau data tertentu)
            const items = keranjangItemTbody.getElementsByClassName('item-keranjang-row');
            if (items.length > 0) {
                tombolSimpan.disabled = false;
            } else {
                tombolSimpan.disabled = true;
            }
        } else {
            tombolSimpan.disabled = true;
        }
    }
    
    // Panggil saat load dan setiap kali keranjang berubah (akan dihandle lebih baik nanti)
    cekStatusTombolSimpan(); 

    // Event listener untuk tombol reset (contoh)
    document.getElementById('tombolResetTransaksi').addEventListener('click', function() {
        if (confirm('Apakah Anda yakin ingin mereset transaksi ini? Semua item di keranjang akan dihapus.')) {
            // Logika reset (akan dikembangkan)
            // Hapus item dari keranjang (DOM dan data array JS)
            // Reset total, pembayaran, dll.
            keranjangItemTbody.innerHTML = '<tr><td colspan="6" class="text-center">Keranjang masih kosong.</td></tr>';
            document.getElementById('displayTotalItem').value = '0';
            document.getElementById('displaySubtotalProduk').value = 'Rp 0';
            document.getElementById('displayTotalBayar').value = 'Rp 0';
            document.getElementById('uangDiterima').value = '';
            document.getElementById('displayKembalian').value = 'Rp 0';
            // Reset hidden input juga
            document.getElementById('totalHargaProdukHidden').value = '0';
            document.getElementById('totalSetelahDiskonPajakHidden').value = '0';
            document.getElementById('kembalianHidden').value = '0';
            document.getElementById('detailItemJson').value = '';

            cekStatusTombolSimpan();
            console.log('Transaksi direset.');
        }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    // --- Inisialisasi Variabel dan Element DOM ---
    const pilihProdukSelect = document.getElementById('pilihProduk');
    const tombolTambahKeKeranjang = document.getElementById('tombolTambahKeKeranjang');
    const keranjangItemTbody = document.getElementById('keranjangItem');
    const displayTotalItem = document.getElementById('displayTotalItem');
    const displaySubtotalProduk = document.getElementById('displaySubtotalProduk');
    const displayTotalBayar = document.getElementById('displayTotalBayar');
    const uangDiterimaInput = document.getElementById('uangDiterima');
    const displayKembalian = document.getElementById('displayKembalian');
    const tombolSimpanTransaksi = document.getElementById('tombolSimpanTransaksi');
    const tombolResetTransaksi = document.getElementById('tombolResetTransaksi');
    
    // Hidden inputs for form submission
    const detailItemJsonInput = document.getElementById('detailItemJson');
    const totalHargaProdukHiddenInput = document.getElementById('totalHargaProdukHidden');
    const totalSetelahDiskonPajakHiddenInput = document.getElementById('totalSetelahDiskonPajakHidden');
    const kembalianHiddenInput = document.getElementById('kembalianHidden');

    let keranjangBelanja = []; // Array untuk menyimpan item di keranjang
    // produkListJS bisa di-generate dari PHP jika diperlukan untuk data yang lebih lengkap
    // const produkListJS = <?php echo json_encode($produk_list); ?>; // Opsi jika ingin data produk lengkap di JS

    // --- Fungsi-Fungsi Utama ---

    // Fungsi untuk merender ulang tabel keranjang belanja
    function renderKeranjang() {
        keranjangItemTbody.innerHTML = ''; // Kosongkan keranjang dulu
        let nomorItem = 0;

        if (keranjangBelanja.length === 0) {
            keranjangItemTbody.innerHTML = '<tr><td colspan="6" class="text-center">Keranjang masih kosong.</td></tr>';
        } else {
            keranjangBelanja.forEach((item, index) => {
                nomorItem++;
                const row = keranjangItemTbody.insertRow();
                row.classList.add('item-keranjang-row'); // Tambahkan class untuk identifikasi

                row.insertCell().textContent = nomorItem;
                row.insertCell().textContent = item.nama;
                
                const qtyCell = row.insertCell();
                const qtyInput = document.createElement('input');
                qtyInput.type = 'number';
                qtyInput.classList.add('form-control', 'form-control-sm', 'input-qty-keranjang');
                qtyInput.value = item.qty;
                qtyInput.min = 1;
                qtyInput.max = item.stokAsli; // Batasi dengan stok asli produk
                qtyInput.dataset.index = index; // Simpan index untuk update
                qtyCell.appendChild(qtyInput);

                row.insertCell().textContent = formatRupiah(item.harga);
                row.insertCell().textContent = formatRupiah(item.subtotal);

                const aksiCell = row.insertCell();
                const tombolHapus = document.createElement('button');
                tombolHapus.type = 'button';
                tombolHapus.classList.add('btn', 'btn-danger', 'btn-sm');
                tombolHapus.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash3" viewBox="0 0 16 16"><path d="M6.5 1h3a.5.5 0 0 1 .5.5v1H6v-1a.5.5 0 0 1 .5-.5ZM11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3A1.5 1.5 0 0 0 5 1.5v1H2.506a.58.58 0 0 0-.01 0H1.5a.5.5 0 0 0 0 1h.538l.853 10.66A2 2 0 0 0 4.885 16h6.23a2 2 0 0 0 1.994-1.84l.853-10.66h.538a.5.5 0 0 0 0-1h-.995a.59.59 0 0 0-.01 0H11Zm1.958 1-.846 10.58a1 1 0 0 1-.997.92h-6.23a1 1 0 0 1-.997-.92L3.042 3.5h9.916Zm-7.487 1a.5.5 0 0 1 .528.47l.5 8.5a.5.5 0 0 1-.998.06L5 5.03a.5.5 0 0 1 .47-.53Zm5.058 0a.5.5 0 0 1 .47.53l-.5 8.5a.5.5 0 1 1-.998-.06l.5-8.5a.5.5 0 0 1 .528-.47ZM8 4.5a.5.5 0 0 1 .5.5v8.5a.5.5 0 0 1-1 0V5a.5.5 0 0 1 .5-.5Z"/></svg>';
                tombolHapus.dataset.index = index; // Simpan index untuk hapus
                aksiCell.appendChild(tombolHapus);
            });
        }
        updateDetailItemJson();
        hitungTotalKeseluruhan();
        cekStatusTombolSimpan();
    }

    // Fungsi untuk menambah produk ke keranjang
    function tambahProdukKeKeranjang() {
        const selectedOption = pilihProdukSelect.options[pilihProdukSelect.selectedIndex];
        if (!selectedOption || !selectedOption.value) {
            alert('Silakan pilih produk terlebih dahulu.');
            return;
        }

        const idProduk = parseInt(selectedOption.value);
        const namaProduk = selectedOption.dataset.nama;
        const hargaProduk = parseFloat(selectedOption.dataset.harga);
        const stokAsli = parseInt(selectedOption.dataset.stok);

        if (stokAsli <= 0) {
            alert(`Stok produk ${namaProduk} sudah habis.`);
            return;
        }

        const itemAda = keranjangBelanja.find(item => item.id === idProduk);

        if (itemAda) {
            if (itemAda.qty < stokAsli) {
                itemAda.qty++;
                itemAda.subtotal = itemAda.qty * itemAda.harga;
            } else {
                alert(`Kuantitas untuk ${namaProduk} sudah mencapai stok maksimum (${stokAsli}).`);
            }
        } else {
            keranjangBelanja.push({
                id: idProduk,
                nama: namaProduk,
                harga: hargaProduk,
                qty: 1,
                stokAsli: stokAsli, // Simpan stok asli untuk validasi qty
                subtotal: hargaProduk 
            });
        }
        pilihProdukSelect.value = ''; // Reset dropdown
        renderKeranjang();
    }

    // Fungsi untuk update kuantitas item
    function updateKuantitasItem(index, newQty) {
        const item = keranjangBelanja[index];
        if (!item) return;

        newQty = parseInt(newQty);
        if (isNaN(newQty) || newQty < 1) {
            newQty = 1;
        } else if (newQty > item.stokAsli) {
            newQty = item.stokAsli;
            alert(`Kuantitas tidak boleh melebihi stok tersedia (${item.stokAsli}).`);
        }
        
        item.qty = newQty;
        item.subtotal = item.qty * item.harga;
        renderKeranjang(); // Re-render untuk update subtotal di tabel dan total keseluruhan
    }
    
    // Fungsi untuk menghapus item dari keranjang
    function hapusItemDariKeranjang(index) {
        keranjangBelanja.splice(index, 1); // Hapus 1 item pada index
        renderKeranjang();
    }

    // Fungsi untuk menghitung semua total
    function hitungTotalKeseluruhan() {
        let totalItem = 0;
        let subtotalProduk = 0;

        keranjangBelanja.forEach(item => {
            totalItem += item.qty;
            subtotalProduk += item.subtotal;
        });

        // Untuk saat ini, total bayar sama dengan subtotal produk (belum ada diskon/pajak global)
        const totalBayar = subtotalProduk;

        displayTotalItem.value = totalItem;
        displaySubtotalProduk.value = formatRupiah(subtotalProduk);
        displayTotalBayar.value = formatRupiah(totalBayar);

        // Update hidden input
        totalHargaProdukHiddenInput.value = subtotalProduk.toFixed(2);
        totalSetelahDiskonPajakHiddenInput.value = totalBayar.toFixed(2);
        
        hitungKembalian(); // Panggil hitung kembalian setiap total berubah
    }

    // Fungsi untuk menghitung kembalian
    function hitungKembalian() {
        const totalBayar = parseFloat(totalSetelahDiskonPajakHiddenInput.value) || 0;
        const uangDiterima = parseFloat(uangDiterimaInput.value) || 0;
        let kembalian = 0;

        if (uangDiterima >= totalBayar) {
            kembalian = uangDiterima - totalBayar;
        }
        
        displayKembalian.value = formatRupiah(kembalian);
        kembalianHiddenInput.value = kembalian.toFixed(2);
        cekStatusTombolSimpan(); // Cek status tombol simpan karena kembalian berubah
    }

    // Fungsi untuk mengupdate hidden input JSON item keranjang
    function updateDetailItemJson() {
        const detailUntukJson = keranjangBelanja.map(item => ({
            id_produk: item.id,
            nama_produk_saat_transaksi: item.nama, // Ambil nama saat ini
            harga_produk_saat_transaksi: item.harga, // Ambil harga saat ini
            jumlah_beli: item.qty,
            subtotal_produk: item.subtotal,
            // Nanti bisa ditambahkan diskon_item_persen, diskon_item_nominal, subtotal_setelah_diskon_item
            subtotal_setelah_diskon_item: item.subtotal // Asumsi belum ada diskon item
        }));
        detailItemJsonInput.value = JSON.stringify(detailUntukJson);
    }
    
    // Fungsi untuk format angka ke Rupiah
    function formatRupiah(angka) {
        return "Rp " + parseFloat(angka).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    }

    // Fungsi untuk mengecek status tombol simpan
    function cekStatusTombolSimpan() {
        const totalBayar = parseFloat(totalSetelahDiskonPajakHiddenInput.value) || 0;
        const uangDiterima = parseFloat(uangDiterimaInput.value) || 0;

        if (keranjangBelanja.length > 0 && uangDiterima >= totalBayar && totalBayar > 0) {
            tombolSimpanTransaksi.disabled = false;
        } else {
            tombolSimpanTransaksi.disabled = true;
        }
    }

    // Fungsi untuk mereset seluruh form transaksi
    function resetTransaksiForm() {
        if (confirm('Apakah Anda yakin ingin mereset transaksi ini? Semua item di keranjang akan dihapus.')) {
            keranjangBelanja = [];
            pilihProdukSelect.value = '';
            uangDiterimaInput.value = '';
            document.getElementById('catatanTransaksi').value = ''; // Reset catatan juga
            renderKeranjang(); // Ini akan memanggil hitungTotalKeseluruhan dan cekStatusTombolSimpan
            console.log('Transaksi direset.');
        }
    }

    // --- Event Listeners ---
    tombolTambahKeKeranjang.addEventListener('click', tambahProdukKeKeranjang);
    uangDiterimaInput.addEventListener('input', hitungKembalian);
    tombolResetTransaksi.addEventListener('click', resetTransaksiForm);

    // Event listener untuk input Qty dan tombol Hapus di keranjang (delegasi event)
    keranjangItemTbody.addEventListener('change', function(event) {
        if (event.target.classList.contains('input-qty-keranjang')) {
            const index = parseInt(event.target.dataset.index);
            const newQty = event.target.value;
            updateKuantitasItem(index, newQty);
        }
    });

    keranjangItemTbody.addEventListener('click', function(event) {
        // Cek apakah yang diklik adalah tombol hapus (atau ikon di dalamnya)
        let targetElement = event.target;
        while (targetElement != null && !targetElement.classList.contains('btn-danger')) {
            targetElement = targetElement.parentElement;
        }
        if (targetElement && targetElement.classList.contains('btn-danger')) {
            const index = parseInt(targetElement.dataset.index);
            hapusItemDariKeranjang(index);
        }
    });
    
    // Panggil sekali saat load untuk inisialisasi tampilan
    renderKeranjang();

});

</script>

<?php
require_once '../includes/footer.php';
?>