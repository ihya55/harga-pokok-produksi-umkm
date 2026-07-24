<?php
require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();
$user = user_login();
$id_entitas = $user['id_entitas'] ?? 0;

$daftar = Capsule::table('tb_rekening_pembayaran_online')
    ->where('id_entitas', $id_entitas)
    ->orderBy('urutan')
    ->orderBy('id_rekening_pembayaran')
    ->get();
?>
<div class="card">
    <div class="card-header">
        <h3>Rekening & QRIS untuk Pembayaran Online</h3>
        <a href="tambah.php" class="btn btn-primary">Tambah Rekening / QRIS</a>
    </div>
    <div class="card-body">
        <table class="table table-bordered">
            <thead>
                <tr><th>Nama Tampilan</th><th>Jenis</th><th>Detail</th><th>Default</th><th>Aktif</th><th>Aksi</th></tr>
            </thead>
            <tbody>
            <?php foreach ($daftar as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r->nama_tampilan) ?></td>
                    <td><?= $r->jenis_pembayaran ?></td>
                    <td>
                        <?php if ($r->jenis_pembayaran == 'transfer'): ?>
                            <?= htmlspecialchars($r->nama_bank) ?> - <?= $r->nomor_rekening ?><br>
                            a.n. <?= htmlspecialchars($r->atas_nama_rekening) ?>
                        <?php elseif ($r->jenis_pembayaran == 'qris'): ?>
                            Merchant: <?= htmlspecialchars($r->qris_nama_merchant) ?><br>
                            <img src="<?= base_url($r->qris_image_path) ?>" width="100">
                        <?php else: ?>
                            E-wallet: <?= htmlspecialchars($r->nomor_rekening) ?>
                        <?php endif; ?>
                    </td>
                    <td><?= $r->is_default ? 'Ya' : '-' ?></td>
                    <td><?= $r->aktif ? 'Aktif' : 'Nonaktif' ?></td>
                    <td>
                        <a href="edit.php?id=<?= $r->id_rekening_pembayaran ?>" class="btn btn-sm btn-warning">Edit</a>
                        <a href="hapus.php?id=<?= $r->id_rekening_pembayaran ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus?')">Hapus</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>