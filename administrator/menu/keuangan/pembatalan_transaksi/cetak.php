<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';
require_once __DIR__ . '/../_keuangan_helper.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

$id_entitas_login = keu_id_entitas();
$entitas = keu_entitas();

$id = (int) ($_GET['id'] ?? 0);

if ($id > 0) {
    $rows = Capsule::table('tb_pembatalan_transaksi as p')
        ->join('tb_jurnal as ja', 'ja.id_jurnal', '=', 'p.id_jurnal_asal')
        ->leftJoin('tb_jurnal as jr', 'jr.id_jurnal', '=', 'p.id_jurnal_reversal')
        ->where('p.id_pembatalan_transaksi', $id)
        ->where('p.id_entitas', $id_entitas_login)
        ->select([
            'p.*',
            'ja.total_debit as total_debit_asal',
            'ja.total_kredit as total_kredit_asal',
            'jr.total_debit as total_debit_reversal',
            'jr.total_kredit as total_kredit_reversal',
        ])
        ->get();
} else {
    $tanggal_awal = keu_tanggal_mysql($_GET['tanggal_awal'] ?? null, date('Y-m-01'));
    $tanggal_akhir = keu_tanggal_mysql($_GET['tanggal_akhir'] ?? null, date('Y-m-t'));

    $rows = Capsule::table('tb_pembatalan_transaksi as p')
        ->join('tb_jurnal as ja', 'ja.id_jurnal', '=', 'p.id_jurnal_asal')
        ->leftJoin('tb_jurnal as jr', 'jr.id_jurnal', '=', 'p.id_jurnal_reversal')
        ->where('p.id_entitas', $id_entitas_login)
        ->whereBetween('p.tanggal_pembatalan', [$tanggal_awal, $tanggal_akhir])
        ->select([
            'p.*',
            'ja.total_debit as total_debit_asal',
            'ja.total_kredit as total_kredit_asal',
            'jr.total_debit as total_debit_reversal',
            'jr.total_kredit as total_kredit_reversal',
        ])
        ->orderBy('p.tanggal_pembatalan', 'asc')
        ->orderBy('p.id_pembatalan_transaksi', 'asc')
        ->get();
}

keu_print_head('Cetak Pembatalan Transaksi');
?>

<div class="sheet">
    <?php keu_print_kop($entitas, 'PEMBATALAN TRANSAKSI', isset($tanggal_awal) ? keu_periode_label($tanggal_awal, $tanggal_akhir, true) : 'Detail Pembatalan', $id > 0 ? 'Detail Pembatalan' : 'Daftar Pembatalan'); ?>

    <table>
        <thead>
            <tr>
                <th width="35" class="text-center">No</th>
                <th>Tanggal</th>
                <th>No Pembatalan</th>
                <th>Jurnal Asal</th>
                <th>Jurnal Reversal</th>
                <th>Sumber</th>
                <th>Alasan</th>
                <th class="text-end">Nominal</th>
            </tr>
        </thead>

        <tbody>
            <?php if ($rows->count() === 0): ?>
                <tr>
                    <td colspan="8" class="text-center">Data tidak tersedia.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $i => $row): ?>
                    <tr>
                        <td class="text-center"><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars(keu_tanggal($row->tanggal_pembatalan), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) $row->no_pembatalan, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) $row->no_jurnal_asal, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($row->no_jurnal_reversal ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($row->no_sumber ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) $row->alasan_pembatalan, ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-end"><?= keu_uang($row->total_debit_asal ?? 0) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
window.addEventListener('load', function () {
    setTimeout(function () {
        window.print();
    }, 300);
});
</script>
</body>
</html>