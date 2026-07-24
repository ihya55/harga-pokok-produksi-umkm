<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../helpers/config.php';
require_once __DIR__ . '/../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../helpers/auth.php';
require_once __DIR__ . '/_keuangan_helper.php';

harus_login();

$id_entitas = keu_id_entitas();
$entitas = keu_entitas();

$mode_kas = $mode_kas ?? 'masuk';
$is_masuk = $mode_kas === 'masuk';
$title = $is_masuk ? 'KAS MASUK' : 'KAS KELUAR';

$tanggal_awal = keu_tanggal_mysql($_GET['tanggal_awal'] ?? null, date('Y-m-01'));
$tanggal_akhir = keu_tanggal_mysql($_GET['tanggal_akhir'] ?? null, date('Y-m-t'));
$status = trim((string) ($_GET['status'] ?? 'semua'));
$q = trim((string) ($_GET['q'] ?? ''));
if (!in_array($status, ['semua', 'draft', 'posted'], true)) {
    $status = 'semua';
}

$rows = keu_kas_report_rows($is_masuk, $id_entitas, $tanggal_awal, $tanggal_akhir, $status, $q);
$total = array_sum(array_map(static fn ($r) => (float) $r['nominal'], $rows));
$periode = keu_periode_label($tanggal_awal, $tanggal_akhir, true);
if ($status !== 'semua') {
    $periode .= ' | Status: ' . ucfirst($status);
}
if ($q !== '') {
    $periode .= ' | Pencarian: ' . $q;
}

keu_print_head('Cetak ' . $title);
?>

<div class="sheet">
    <?php keu_print_kop($entitas, $title, $periode, 'Sumber data: transaksi kas/bank dari input manual dan seluruh jurnal posted yang memengaruhi kas/bank.'); ?>

    <table>
        <thead>
            <tr>
                <th width="35" class="text-center">No</th>
                <th>Tanggal</th>
                <th>Nomor</th>
                <th>Jenis Transaksi</th>
                <th>Sumber</th>
                <th>Akun Kas/Bank</th>
                <?php if (!$is_masuk): ?><th>Akun Lawan/Beban</th><?php endif; ?>
                <th>Keterangan</th>
                <th class="text-end">Nominal</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="<?= $is_masuk ? 9 : 10 ?>" class="text-center">Data tidak tersedia.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $i => $row): ?>
                    <tr>
                        <td class="text-center"><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars(keu_tanggal($row['tanggal']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) $row['nomor'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) $row['jenis'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) $row['sumber'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) $row['akun_kas'], ENT_QUOTES, 'UTF-8') ?></td>
                        <?php if (!$is_masuk): ?><td><?= htmlspecialchars((string) ($row['akun_lawan'] ?: '-'), ENT_QUOTES, 'UTF-8') ?></td><?php endif; ?>
                        <td><?= htmlspecialchars((string) ($row['keterangan'] ?: '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-end"><?= keu_uang($row['nominal'] ?? 0) ?></td>
                        <td><?= htmlspecialchars((string) $row['status'], ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="<?= $is_masuk ? 7 : 8 ?>" class="text-end">Total</th>
                <th class="text-end"><?= keu_uang($total) ?></th>
                <th></th>
            </tr>
        </tfoot>
    </table>
</div>

<script>
window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 300); });
</script>
</body>
</html>
