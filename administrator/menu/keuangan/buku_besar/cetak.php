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

$tanggal_awal = keu_tanggal_mysql($_GET['tanggal_awal'] ?? null, date('Y-m-01'));
$tanggal_akhir = keu_tanggal_mysql($_GET['tanggal_akhir'] ?? null, date('Y-m-t'));
$id_coa = (int) ($_GET['id_coa'] ?? 0);

$query = Capsule::table('vw_buku_besar')
    ->where('id_entitas', $id_entitas)
    ->whereBetween('tanggal_jurnal', [$tanggal_awal, $tanggal_akhir]);

if ($id_coa > 0) {
    $query->where('id_coa', $id_coa);
}

$rows = $query
    ->orderBy('tanggal_jurnal', 'asc')
    ->orderBy('id_jurnal', 'asc')
    ->orderBy('urutan', 'asc')
    ->get();

$total_debit = (float) $rows->sum('debit');
$total_kredit = (float) $rows->sum('kredit');
$rows_by_akun = [];
foreach ($rows as $r) {
    $key = (string) ($r->kode_coa ?? '') . ' - ' . (string) ($r->nama_coa ?? '');
    if (!isset($rows_by_akun[$key])) {
        $rows_by_akun[$key] = [];
    }
    $rows_by_akun[$key][] = $r;
}

keu_print_head('Cetak Buku Besar');
?>

<div class="sheet">
    <?php keu_print_kop($entitas, 'BUKU BESAR', keu_periode_label($tanggal_awal, $tanggal_akhir, true), $id_coa > 0 ? 'Dicetak sesuai akun yang dipilih' : 'Semua akun'); ?>

    <?php if ($rows->count() === 0): ?>
        <table><tbody><tr><td class="text-center">Data tidak tersedia.</td></tr></tbody></table>
    <?php else: ?>
        <?php foreach ($rows_by_akun as $akunLabel => $akunRows): ?>
            <?php
            $subDebit = array_sum(array_map(static fn($x) => (float) ($x->debit ?? 0), $akunRows));
            $subKredit = array_sum(array_map(static fn($x) => (float) ($x->kredit ?? 0), $akunRows));
            ?>
            <div class="summary" style="margin:14px 0 8px">
                <strong>Kode Akun: <?= htmlspecialchars($akunLabel, ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <table>
                <thead>
                    <tr>
                        <th width="35" class="text-center">No</th>
                        <th>Tanggal</th>
                        <th>No Jurnal</th>
                        <th>Sumber</th>
                        <th>Keterangan</th>
                        <th class="text-end">Debit</th>
                        <th class="text-end">Kredit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($akunRows as $i => $row): ?>
                        <tr>
                            <td class="text-center"><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars(keu_tanggal($row->tanggal_jurnal), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) $row->no_jurnal, ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($row->no_sumber ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($row->keterangan_baris ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-end"><?= keu_uang($row->debit ?? 0) ?></td>
                            <td class="text-end"><?= keu_uang($row->kredit ?? 0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="5" class="text-end">Subtotal <?= htmlspecialchars($akunLabel, ENT_QUOTES, 'UTF-8') ?></th>
                        <th class="text-end"><?= keu_uang($subDebit) ?></th>
                        <th class="text-end"><?= keu_uang($subKredit) ?></th>
                    </tr>
                </tfoot>
            </table>
        <?php endforeach; ?>

        <table style="margin-top:14px">
            <tfoot>
                <tr class="total-row">
                    <td class="text-end" colspan="5">TOTAL BUKU BESAR</td>
                    <td class="text-end"><?= keu_uang($total_debit) ?></td>
                    <td class="text-end"><?= keu_uang($total_kredit) ?></td>
                </tr>
            </tfoot>
        </table>
    <?php endif; ?>
</div>

<script>
window.addEventListener('load', function () {
    setTimeout(function () { window.print(); }, 300);
});
</script>
</body>
</html>