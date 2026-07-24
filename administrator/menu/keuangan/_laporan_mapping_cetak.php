<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../helpers/config.php';
require_once __DIR__ . '/../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../helpers/auth.php';
require_once __DIR__ . '/_keuangan_helper.php';

harus_login();

$kode_laporan = $kode_laporan ?? 'LABA_RUGI';
$judul_laporan = $judul_laporan ?? 'Laporan';
$gunakan_periode_awal = $gunakan_periode_awal ?? true;

$tanggal_awal = keu_tanggal_mysql($_GET['tanggal_awal'] ?? null, date('Y-m-01'));
$tanggal_akhir = keu_tanggal_mysql($_GET['tanggal_akhir'] ?? null, date('Y-m-t'));

$rows = keu_laporan_mapping_rows($kode_laporan, $tanggal_awal, $tanggal_akhir);
$entitas = keu_entitas();

$renderRows = function (array $items): void {
    $lastKategori = null;
    foreach ($items as $row) {
        if ($lastKategori !== $row['kategori_kode'] && $row['tipe_baris'] === 'DETAIL') {
            echo '<tr class="section-row"><td colspan="2">' . htmlspecialchars($row['kategori_label'], ENT_QUOTES, 'UTF-8') . '</td></tr>';
            $lastKategori = $row['kategori_kode'];
        }

        $class = '';
        if ($row['tipe_baris'] === 'SUBTOTAL') {
            $class = 'subtotal-row';
        } elseif ($row['tipe_baris'] === 'TOTAL') {
            $class = 'total-row';
        }

        $nilaiTampil = (float) $row['nilai'];
        if (in_array((string) $row['kategori_kode'], ['HPP', 'BEBAN_OPERASIONAL', 'TOTAL_BEBAN'], true)) {
            $nilaiTampil = abs($nilaiTampil);
        }

        echo '<tr class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '">';
        echo '<td>' . ($row['tipe_baris'] === 'DETAIL' ? '&nbsp;&nbsp;&nbsp;' : '') . htmlspecialchars($row['sub_label'], ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td class="text-end">' . keu_uang($nilaiTampil) . '</td>';
        echo '</tr>';
    }
};

keu_print_head('Cetak ' . $judul_laporan);
?>

<div class="sheet">
    <?php keu_print_kop($entitas, $judul_laporan, keu_periode_label($tanggal_awal, $tanggal_akhir, (bool) $gunakan_periode_awal), ''); ?>

    <?php if (!empty($entitas->logo_umkm)): ?>
                <img class="kop-logo" src="<?= htmlspecialchars(base_url((string) $entitas->logo_umkm), ENT_QUOTES, 'UTF-8') ?>" alt="Logo UMKM">
            <?php endif; ?>
            <div>
                <h1><?= htmlspecialchars((string) ($entitas->nama_entitas ?? 'Entitas'), ENT_QUOTES, 'UTF-8') ?></h1>
                <div class="meta"><?= nl2br(htmlspecialchars((string) ($entitas->alamat ?? '-'), ENT_QUOTES, 'UTF-8')) ?></div>
            </div>
        </div>

        <div class="doc-title">
            <h2><?= htmlspecialchars(strtoupper($judul_laporan), ENT_QUOTES, 'UTF-8') ?></h2>
            <div class="meta">
                <?php if ($gunakan_periode_awal): ?>
                    <?= keu_tanggal($tanggal_awal) ?> s/d <?= keu_tanggal($tanggal_akhir) ?>
                <?php else: ?>
                    Per <?= keu_tanggal($tanggal_akhir) ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (empty($rows)): ?>
        <table><tbody><tr><td class="text-center">Mapping laporan belum tersedia.</td></tr></tbody></table>
    <?php elseif ($kode_laporan === 'NERACA'): ?>
        <?php
        $asetRows = array_values(array_filter($rows, fn($r) => ((int) ($r['urutan'] ?? 0)) < 40));
        $pasivaRows = array_values(array_filter($rows, fn($r) => ((int) ($r['urutan'] ?? 0)) >= 40));
        ?>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;align-items:start">
            <table>
                <thead><tr><th>Aset</th><th width="150" class="text-end">Nilai</th></tr></thead>
                <tbody><?php $renderRows($asetRows); ?></tbody>
            </table>
            <table>
                <thead><tr><th>Liabilitas dan Ekuitas</th><th width="150" class="text-end">Nilai</th></tr></thead>
                <tbody><?php $renderRows($pasivaRows); ?></tbody>
            </table>
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Uraian</th>
                    <th width="220" class="text-end">Nilai</th>
                </tr>
            </thead>
            <tbody><?php $renderRows($rows); ?></tbody>
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