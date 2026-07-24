<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

require_once __DIR__ . '/_keuangan_helper.php';

$mode_kas = $mode_kas ?? 'masuk';
$is_masuk = $mode_kas === 'masuk';

$page_label = $is_masuk ? 'Kas Masuk' : 'Kas Keluar';
$menu_base = $is_masuk ? 'keuangan/kas-masuk' : 'keuangan/kas-keluar';

$id_entitas = keu_id_entitas();

$data = $data ?? null;
$is_edit = (bool) ($is_edit ?? false);

$kas_bank_options = Capsule::table('tb_mapping_akun as ma')
    ->join('tb_coa as c', 'c.id_coa', '=', 'ma.id_coa')
    ->where('ma.id_entitas', $id_entitas)
    ->where('ma.kode_jenis_transaksi', 'ARUS_KAS')
    ->where('ma.peran_akun', 'akun_kas_bank')
    ->where('ma.status_aktif', 1)
    ->where('c.status_aktif', 1)
    ->select(['c.id_coa', 'c.kode_coa', 'c.nama_coa'])
    ->orderBy('c.kode_coa', 'asc')
    ->get();

$beban_options = collect();

if (!$is_masuk) {
    $beban_options = Capsule::table('tb_coa')
        ->where('id_entitas', $id_entitas)
        ->where('boleh_transaksi', 1)
        ->where('status_aktif', 1)
        ->whereIn('kategori_coa', ['beban', 'hpp'])
        ->orderBy('kode_coa', 'asc')
        ->get();
}

$sumber_masuk_options = [
    'KAS_MASUK' => 'Kas Masuk Lain',
    'KAS_MASUK_LAIN' => 'Penerimaan Kas Lainnya',
];

$sumber_keluar_options = [
    'KAS_KELUAR' => 'Kas Keluar Lain',
    'KAS_KELUAR_LAIN' => 'Pengeluaran Kas Lainnya',
];

$jenis_options = $is_masuk ? $sumber_masuk_options : $sumber_keluar_options;

$form_action = $is_masuk
    ? ($is_edit ? admin_page_url('keuangan/kas-masuk/update') : admin_page_url('keuangan/kas-masuk/simpan'))
    : ($is_edit ? admin_page_url('keuangan/kas-keluar/update') : admin_page_url('keuangan/kas-keluar/simpan'));

$id_value = $is_masuk ? (int) ($data->id_kas_masuk ?? 0) : (int) ($data->id_kas_keluar ?? 0);

$tanggal_value = $is_masuk
    ? (string) ($data->tanggal_kas_masuk ?? date('Y-m-d'))
    : (string) ($data->tanggal_kas_keluar ?? date('Y-m-d'));

$jenis_value = $is_masuk
    ? (string) ($data->sumber_kas_masuk ?? 'KAS_MASUK')
    : (string) ($data->jenis_kas_keluar ?? 'KAS_KELUAR');

$id_coa_kas_value = (int) ($data->id_coa_kas_bank ?? 0);
$id_coa_beban_value = (int) ($data->id_coa_beban ?? 0);
$jumlah_value = (float) ($data->jumlah ?? 0);
$keterangan_value = (string) ($data->keterangan ?? '');
?>

<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h1 class="page-title"><?= esc($is_edit ? 'Edit ' . $page_label : 'Tambah ' . $page_label) ?></h1>
            <p class="page-subtitle">Data manual disimpan draft, lalu bisa diposting ke jurnal.</p>
        </div>

        <a href="<?= esc(admin_page_url($menu_base)) ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= esc($form_action) ?>">
            <?php if ($is_edit): ?>
                <input type="hidden" name="id" value="<?= $id_value ?>">
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Tanggal <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal" class="form-control" value="<?= esc($tanggal_value) ?>" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">
                        <?= $is_masuk ? 'Sumber Kas Masuk' : 'Sumber Kas Keluar' ?> <span class="text-danger">*</span>
                    </label>
                    <select name="jenis" class="form-select" required>
                        <option value="">- Pilih Sumber -</option>
                        <?php foreach ($jenis_options as $kode => $label): ?>
                            <option value="<?= esc($kode) ?>" <?= $jenis_value === $kode ? 'selected' : '' ?>>
                                <?= esc($label) ?> (<?= esc($kode) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Sumber ini ikut masuk ke kode jenis transaksi jurnal saat posting.</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Akun Kas/Bank <span class="text-danger">*</span></label>
                    <select name="id_coa_kas_bank" class="form-select" required>
                        <option value="">- Pilih Akun Kas/Bank -</option>
                        <?php foreach ($kas_bank_options as $a): ?>
                            <option value="<?= (int) $a->id_coa ?>" <?= $id_coa_kas_value === (int) $a->id_coa ? 'selected' : '' ?>>
                                <?= esc($a->kode_coa . ' - ' . $a->nama_coa) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if (!$is_masuk): ?>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Akun Beban/HPP <span class="text-danger">*</span></label>
                        <select name="id_coa_beban" class="form-select" required>
                            <option value="">- Pilih Akun Beban/HPP -</option>
                            <?php foreach ($beban_options as $a): ?>
                                <option value="<?= (int) $a->id_coa ?>" <?= $id_coa_beban_value === (int) $a->id_coa ? 'selected' : '' ?>>
                                    <?= esc($a->kode_coa . ' - ' . $a->nama_coa) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="col-md-<?= $is_masuk ? '6' : '6' ?>">
                    <label class="form-label fw-semibold">Jumlah <span class="text-danger">*</span></label>
                    <input type="text" name="jumlah" class="form-control input-uang" value="<?= esc(number_format($jumlah_value, 0, ',', '.')) ?>" required>
                </div>

                <div class="col-md-12">
                    <label class="form-label fw-semibold">Keterangan</label>
                    <textarea name="keterangan" class="form-control" rows="3"><?= esc($keterangan_value) ?></textarea>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center gap-2 mt-4">
                <a href="<?= esc(admin_page_url($menu_base)) ?>" class="btn btn-outline-secondary">
                    Batal
                </a>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check2-circle me-1"></i>Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('.input-uang').forEach(function (input) {
    input.addEventListener('input', function () {
        let raw = this.value.replace(/[^0-9]/g, '');
        this.value = raw.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    });
});
</script>