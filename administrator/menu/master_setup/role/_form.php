<?php
$menu_rows = $menu_rows ?? collect();
$role_menu_map = $role_menu_map ?? [];

$akses_fields = [
    'boleh_lihat' => 'Lihat',
    'boleh_tambah' => 'Tambah',
    'boleh_ubah' => 'Ubah',
    'boleh_hapus' => 'Hapus',
    'boleh_posting' => 'Posting',
    'boleh_approve' => 'Approve',
    'boleh_cetak' => 'Cetak',
    'boleh_export' => 'Export',
];

$menu_grup = [];
$menu_anak = [];

foreach ($menu_rows as $m) {
    if ((int) ($m->id_menu_induk ?? 0) === 0) {
        $menu_grup[(int) $m->id_menu] = $m;
    } else {
        $menu_anak[(int) $m->id_menu_induk][] = $m;
    }
}

function role_form_checked(array $map, int $id_menu, string $field): string
{
    if (!isset($map[$id_menu])) {
        return '';
    }

    return ((int) ($map[$id_menu][$field] ?? 0) === 1) ? 'checked' : '';
}
?>

<div class="page-header mb-4">
    <h1 class="page-title"><?= esc($page_title ?? 'Form Role') ?></h1>
    <p class="page-subtitle"><?= esc($page_subtitle ?? '') ?></p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= esc($form_action) ?>">
            <?php if (!empty($data_form['id_role'])): ?>
                <input type="hidden" name="id_role" value="<?= (int) $data_form['id_role'] ?>">
            <?php endif; ?>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Nama Role <span class="text-danger">*</span></label>
                    <input
                        type="text"
                        name="nama_role"
                        class="form-control"
                        maxlength="100"
                        required
                        value="<?= esc((string) ($data_form['nama_role'] ?? '')) ?>"
                        placeholder="Contoh: admin_penjualan">
                    <div class="form-text">Gunakan huruf kecil dan underscore agar konsisten, contoh: admin_gudang.</div>
                </div>

                <div class="col-md-8">
                    <label class="form-label fw-semibold">Keterangan</label>
                    <input
                        type="text"
                        name="keterangan"
                        class="form-control"
                        maxlength="255"
                        value="<?= esc((string) ($data_form['keterangan'] ?? '')) ?>"
                        placeholder="Deskripsi role">
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <h2 class="h5 mb-1">Hak Akses Menu</h2>
                    <div class="text-muted small">Centang hak akses yang boleh digunakan role ini.</div>
                </div>

                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-success" onclick="centangSemuaAkses()">
                        Centang Semua
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="hapusSemuaAkses()">
                        Kosongkan
                    </button>
                </div>
            </div>

            <div class="table-responsive border rounded">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width:260px;">Menu</th>
                            <?php foreach ($akses_fields as $label): ?>
                                <th class="text-center" style="min-width:82px;"><?= esc($label) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($menu_grup as $id_grup => $grup): ?>
                            <tr class="table-secondary">
                                <td>
                                    <div class="fw-bold">
                                        <?= esc((string) $grup->nama_menu) ?>
                                    </div>
                                    <div class="text-muted small">
                                        <?= esc((string) $grup->kode_menu) ?>
                                    </div>
                                </td>

                                <?php foreach ($akses_fields as $field => $label): ?>
                                    <td class="text-center">
                                        <input
                                            type="checkbox"
                                            class="form-check-input akses-checkbox grup-<?= (int) $id_grup ?>"
                                            name="akses[<?= (int) $grup->id_menu ?>][<?= esc($field) ?>]"
                                            value="1"
                                            <?= role_form_checked($role_menu_map, (int) $grup->id_menu, $field) ?>>
                                    </td>
                                <?php endforeach; ?>
                            </tr>

                            <?php foreach (($menu_anak[$id_grup] ?? []) as $anak): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-semibold">
                                            <i class="bi bi-arrow-return-right me-1 text-muted"></i>
                                            <?= esc((string) $anak->nama_menu) ?>
                                        </div>
                                        <div class="text-muted small">
                                            <?= esc((string) $anak->kode_menu) ?>
                                            <?php if (!empty($anak->url)): ?>
                                                · <?= esc((string) $anak->url) ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <?php foreach ($akses_fields as $field => $label): ?>
                                        <td class="text-center">
                                            <input
                                                type="checkbox"
                                                class="form-check-input akses-checkbox"
                                                name="akses[<?= (int) $anak->id_menu ?>][<?= esc($field) ?>]"
                                                value="1"
                                                <?= role_form_checked($role_menu_map, (int) $anak->id_menu, $field) ?>>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="alert alert-info mt-3 mb-0">
                <i class="bi bi-info-circle me-1"></i>
                Minimal centang <strong>Lihat</strong> untuk menu yang ingin ditampilkan pada sidebar. Hak tambah/ubah/hapus/posting dipakai untuk validasi aksi berikutnya.
            </div>

            <div class="d-flex justify-content-between align-items-center gap-2 mt-4">
                <a href="<?= esc(admin_page_url('master/role')) ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Kembali
                </a>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check2-circle me-1"></i><?= esc($button_label ?? 'Simpan') ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function centangSemuaAkses() {
    document.querySelectorAll('.akses-checkbox').forEach(function (checkbox) {
        checkbox.checked = true;
    });
}

function hapusSemuaAkses() {
    document.querySelectorAll('.akses-checkbox').forEach(function (checkbox) {
        checkbox.checked = false;
    });
}
</script>