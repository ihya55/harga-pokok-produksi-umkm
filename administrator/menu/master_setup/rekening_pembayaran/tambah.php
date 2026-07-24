<?php
require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();
$user = user_login();
$id_entitas = $user['id_entitas'] ?? 0;

// Ambil daftar akun bank (COA kategori ASET dengan kode 1120 atau yang memiliki nomor rekening?)
$daftarCoa = Capsule::table('tb_coa')
    ->where('id_entitas', $id_entitas)
    ->where('kode_coa', 'LIKE', '1120%') // Sesuaikan dengan kode bank Anda
    ->orWhere('kategori_coa', 'aset')
    ->where('boleh_transaksi', 1)
    ->orderBy('kode_coa')
    ->get();
?>
<form method="POST" action="simpan.php" enctype="multipart/form-data">
    <div class="card">
        <div class="card-header">Tambah Rekening / QRIS</div>
        <div class="card-body">
            <div class="form-group">
                <label>Akun COA (Kas/Bank)</label>
                <select name="id_coa" class="form-control" required>
                    <option value="">Pilih Akun</option>
                    <?php foreach ($daftarCoa as $coa): ?>
                        <option value="<?= $coa->id_coa ?>"><?= $coa->kode_coa ?> - <?= $coa->nama_coa ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Nama Tampilan</label>
                <input type="text" name="nama_tampilan" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Jenis Pembayaran</label>
                <select name="jenis_pembayaran" id="jenis_pembayaran" class="form-control" required>
                    <option value="transfer">Transfer Bank</option>
                    <option value="qris">QRIS</option>
                    <option value="ewallet">E-Wallet</option>
                </select>
            </div>
            <div id="field_transfer" class="fields-group">
                <div class="form-group"><label>Nama Bank</label><input type="text" name="nama_bank" class="form-control"></div>
                <div class="form-group"><label>Nomor Rekening</label><input type="text" name="nomor_rekening" class="form-control"></div>
                <div class="form-group"><label>Atas Nama</label><input type="text" name="atas_nama_rekening" class="form-control"></div>
            </div>
            <div id="field_qris" class="fields-group" style="display:none">
                <div class="form-group"><label>Nama Merchant QRIS</label><input type="text" name="qris_nama_merchant" class="form-control"></div>
                <div class="form-group"><label>Static Payload (opsional)</label><textarea name="qris_static_payload" rows="2" class="form-control"></textarea></div>
                <div class="form-group"><label>Gambar QRIS</label><input type="file" name="qris_image" class="form-control" accept="image/*"></div>
            </div>
            <div id="field_ewallet" class="fields-group" style="display:none">
                <div class="form-group"><label>ID/Nomor E-Wallet</label><input type="text" name="nomor_rekening" class="form-control"></div>
                <div class="form-group"><label>Atas Nama</label><input type="text" name="atas_nama_rekening" class="form-control"></div>
            </div>
            <div class="form-group">
                <label><input type="checkbox" name="is_default" value="1"> Jadikan Default</label>
            </div>
            <div class="form-group">
                <label><input type="checkbox" name="aktif" value="1" checked> Aktif</label>
            </div>
            <div class="form-group">
                <label>Urutan</label>
                <input type="number" name="urutan" class="form-control" value="0">
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="index.php" class="btn btn-secondary">Batal</a>
        </div>
    </div>
</form>
<script>
    const jenis = document.getElementById('jenis_pembayaran');
    const transferDiv = document.getElementById('field_transfer');
    const qrisDiv = document.getElementById('field_qris');
    const ewalletDiv = document.getElementById('field_ewallet');
    function toggleFields() {
        transferDiv.style.display = 'none';
        qrisDiv.style.display = 'none';
        ewalletDiv.style.display = 'none';
        if (jenis.value === 'transfer') transferDiv.style.display = 'block';
        else if (jenis.value === 'qris') qrisDiv.style.display = 'block';
        else if (jenis.value === 'ewallet') ewalletDiv.style.display = 'block';
    }
    jenis.addEventListener('change', toggleFields);
    toggleFields();
</script>