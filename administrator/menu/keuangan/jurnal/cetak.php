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
    $rows_header = Capsule::table('tb_jurnal')
        ->where('id_entitas', $id_entitas)
        ->where('id_jurnal', $id_jurnal)
        ->orderBy('tanggal_jurnal', 'asc')
        ->get();
} else {
    $tanggal_awal = keu_tanggal_mysql($_GET['tanggal_awal'] ?? null, date('Y-m-01'));
    $tanggal_akhir = keu_tanggal_mysql($_GET['tanggal_akhir'] ?? null, date('Y-m-t'));
    $status = trim((string) ($_GET['status'] ?? 'semua'));
    $q = trim((string) ($_GET['q'] ?? ''));

    $query = Capsule::table('tb_jurnal')
        ->where('id_entitas', $id_entitas)
        ->whereBetween('tanggal_jurnal', [$tanggal_awal, $tanggal_akhir]);

    if (in_array($status, ['draft', 'posted', 'batal'], true)) {
        $query->where('status_jurnal', $status);
    }

    if ($q !== '') {
        $query->where(function ($sub) use ($q) {
            $sub->where('no_jurnal', 'like', '%' . $q . '%')
                ->orWhere('kode_jenis_transaksi', 'like', '%' . $q . '%')
                ->orWhere('tabel_sumber', 'like', '%' . $q . '%')
                ->orWhere('no_sumber', 'like', '%' . $q . '%')
                ->orWhere('keterangan', 'like', '%' . $q . '%');
        });
    }

    $rows_header = $query
        ->orderBy('tanggal_jurnal', 'asc')
        ->orderBy('id_jurnal', 'asc')
        ->get();
}

keu_print_head('Cetak Jurnal');
?>

<div class="sheet">
    <?php keu_print_kop($entitas, 'JURNAL', isset($tanggal_awal) ? keu_periode_label($tanggal_awal, $tanggal_akhir, true) : 'Detail Jurnal', $id_jurnal > 0 ? 'Detail Jurnal' : 'Daftar Jurnal'); ?>

    <?php if (!empty($entitas->logo_umkm)): ?>
                <img class="kop-logo" src="<?= htmlspecialchars(base_url((string) $entitas->logo_umkm), ENT_QUOTES, 'UTF-8') ?>" alt="Logo UMKM">
            <?php endif; ?>
            <div>
                <h1><?= htmlspecialchars((string) ($entitas->nama_entitas ?? 'Entitas'), ENT_QUOTES, 'UTF-8') ?></h1>
                <div class="meta"><?= nl2br(htmlspecialchars((string) ($entitas->alamat ?? '-'), ENT_QUOTES, 'UTF-8')) ?></div>
            </div>
        </div>
        <div class="doc-title">
            <h2>JURNAL</h2>
            <div class="meta"><?= $id_jurnal > 0 ? 'Detail Jurnal' : 'Daftar Jurnal' ?></div>
        </div>
    </div>

    <?php if ($rows_header->count() === 0): ?>
        <table>
            <tr>
                <td class="text-center">Data jurnal tidak tersedia.</td>
            </tr>
        </table>
    <?php else: ?>
        <?php foreach ($rows_header as $header): ?>
            <?php
            $detail = Capsule::table('tb_jurnal_detail as jd')
                ->join('tb_coa as c', 'c.id_coa', '=', 'jd.id_coa')
                ->where('jd.id_jurnal', (int) $header->id_jurnal)
                ->select([
                    'jd.*',
                    'c.kode_coa',
                    'c.nama_coa',
                ])
                ->orderBy('jd.urutan', 'asc')
                ->orderBy('jd.id_jurnal_detail', 'asc')
                ->get();
            ?>

            <div class="summary">
                <strong><?= htmlspecialchars((string) $header->no_jurnal, ENT_QUOTES, 'UTF-8') ?></strong><br>
                Tanggal: <?= keu_tanggal($header->tanggal_jurnal) ?><br>
                Jenis: <?= htmlspecialchars((string) ($header->kode_jenis_transaksi ?? '-'), ENT_QUOTES, 'UTF-8') ?><br>
                Sumber: <?= htmlspecialchars((string) ($header->no_sumber ?? '-'), ENT_QUOTES, 'UTF-8') ?><br>
                Keterangan: <?= htmlspecialchars((string) ($header->keterangan ?? '-'), ENT_QUOTES, 'UTF-8') ?>
            </div>

            <br>

            <table>
                <thead>
                    <tr>
                        <th width="35" class="text-center">No</th>
                        <th>Akun</th>
                        <th>Keterangan</th>
                        <th class="text-end">Debit</th>
                        <th class="text-end">Kredit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($detail->count() === 0): ?>
                        <tr><td colspan="5" class="text-center">Detail tidak tersedia.</td></tr>
                    <?php else: ?>
                        <?php foreach ($detail as $i => $d): ?>
                            <tr>
                                <td class="text-center"><?= $i + 1 ?></td>
                                <td><?= htmlspecialchars($d->kode_coa . ' - ' . $d->nama_coa, ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($d->keterangan_baris ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-end"><?= keu_uang($d->debit ?? 0) ?></td>
                                <td class="text-end"><?= keu_uang($d->kredit ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" class="text-end">Total</th>
                        <th class="text-end"><?= keu_uang($header->total_debit ?? 0) ?></th>
                        <th class="text-end"><?= keu_uang($header->total_kredit ?? 0) ?></th>
                    </tr>
                </tfoot>
            </table>

            <br><br>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
window.addEventListener('load', function () {
    setTimeout(function () { window.print(); }, 300);
});
</script>
</body>
</html>