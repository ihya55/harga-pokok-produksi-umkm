<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';
require_once __DIR__ . '/../_keuangan_helper.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

$id_entitas = keu_id_entitas();
$entitas = keu_entitas();
$id_jurnal = (int) ($_GET['id'] ?? 0);

if ($id_jurnal > 0) {
    $rows = Capsule::table('tb_jurnal')
        ->where('id_entitas', $id_entitas)
        ->where('id_jurnal', $id_jurnal)
        ->whereNotNull('tabel_sumber')
        ->where('tabel_sumber', '<>', '')
        ->orderBy('tanggal_jurnal', 'asc')
        ->get();
} else {
    $tanggal_awal = keu_tanggal_mysql($_GET['tanggal_awal'] ?? null, date('Y-m-01'));
    $tanggal_akhir = keu_tanggal_mysql($_GET['tanggal_akhir'] ?? null, date('Y-m-t'));
    $q = trim((string) ($_GET['q'] ?? ''));

    $query = Capsule::table('tb_jurnal')
        ->where('id_entitas', $id_entitas)
        ->whereBetween('tanggal_jurnal', [$tanggal_awal, $tanggal_akhir])
        ->whereNotNull('tabel_sumber')
        ->where('tabel_sumber', '<>', '');

    if ($q !== '') {
        $query->where(function ($sub) use ($q) {
            $sub->where('no_jurnal', 'like', '%' . $q . '%')
                ->orWhere('kode_jenis_transaksi', 'like', '%' . $q . '%')
                ->orWhere('tabel_sumber', 'like', '%' . $q . '%')
                ->orWhere('no_sumber', 'like', '%' . $q . '%')
                ->orWhere('keterangan', 'like', '%' . $q . '%');
        });
    }

    $rows = $query
        ->orderBy('tanggal_jurnal', 'asc')
        ->orderBy('id_jurnal', 'asc')
        ->get();
}

keu_print_head('Cetak Log Jurnal Sumber');
?>

<div class="sheet">
    <?php keu_print_kop($entitas, 'LOG JURNAL SUMBER', isset($tanggal_awal) ? keu_periode_label($tanggal_awal, $tanggal_akhir, true) : 'Detail Sumber', $id_jurnal > 0 ? 'Detail Sumber' : 'Daftar Sumber'); ?>

    <table>
        <thead>
            <tr>
                <th width="35" class="text-center">No</th>
                <th>Tanggal</th>
                <th>No Jurnal</th>
                <th>Jenis Transaksi</th>
                <th>Tabel Sumber</th>
                <th>No Sumber</th>
                <th>Keterangan</th>
                <th class="text-end">Debit</th>
                <th class="text-end">Kredit</th>
                <th>Status</th>
            </tr>
        </thead>

        <tbody>
            <?php if ($rows->count() === 0): ?>
                <tr>
                    <td colspan="10" class="text-center">Data tidak tersedia.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $i => $row): ?>
                    <tr>
                        <td class="text-center"><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars(keu_tanggal($row->tanggal_jurnal), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) $row->no_jurnal, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($row->kode_jenis_transaksi ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($row->tabel_sumber ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($row->no_sumber ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($row->keterangan ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-end"><?= keu_uang($row->total_debit ?? 0) ?></td>
                        <td class="text-end"><?= keu_uang($row->total_kredit ?? 0) ?></td>
                        <td><?= htmlspecialchars((string) ($row->status_jurnal ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
window.addEventListener('load', function () {
    setTimeout(function () { window.print(); }, 300);
});
</script>
</body>
</html>