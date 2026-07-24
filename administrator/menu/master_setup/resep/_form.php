<?php
$data_form = $data_form ?? [];
$detail_rows = $detail_rows ?? [];

if (!isset($form_action) || $form_action === '') {
    $form_action = $_SERVER['REQUEST_URI'] ?? '';
}

if (!function_exists('resep_jumlah_pakai_int')) {
    function resep_jumlah_pakai_int($value): int
    {
        if ($value === null || $value === '') {
            return 1;
        }

        return max(0, (int) $value);
    }
}
?>

<div class="page-header mb-4">
    <h1 class="page-title"><?= esc($page_title ?? 'Form Resep / BOM') ?></h1>
    <p class="page-subtitle"><?= esc($page_subtitle ?? '') ?></p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= esc($form_action) ?>" id="form-resep">
            <?php if (!empty($data_form['id_resep'])): ?>
                <input type="hidden" name="id_resep" value="<?= (int) $data_form['id_resep'] ?>">
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Kode Resep</label>
                    <input type="text" class="form-control" value="<?= esc($data_form['kode_resep'] ?? 'Otomatis saat disimpan') ?>" readonly>
                </div>

                <div class="col-md-8">
                    <label class="form-label fw-semibold">Nama Resep <span class="text-danger">*</span></label>
                    <input type="text" name="nama_resep" class="form-control" maxlength="150" required value="<?= esc($data_form['nama_resep'] ?? '') ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Produk <span class="text-danger">*</span></label>
                    <select name="id_produk" class="form-select" required>
                        <option value="">- Pilih Produk -</option>
                        <?php foreach (($produk_options ?? []) as $item): ?>
                            <option value="<?= (int) $item->id_produk ?>" <?= ((string) ($data_form['id_produk'] ?? '') === (string) $item->id_produk) ? 'selected' : '' ?>>
                                <?= esc($item->kode_produk . ' - ' . $item->nama_produk) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Jumlah Hasil <span class="text-danger">*</span></label>
                    <input type="number" name="jumlah_hasil" class="form-control" min="0.01" step="0.01" required value="<?= esc((string) ($data_form['jumlah_hasil'] ?? '1.00')) ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Versi Resep</label>
                    <input type="text" name="versi_resep" class="form-control" maxlength="30" value="<?= esc($data_form['versi_resep'] ?? '') ?>" placeholder="Contoh: V1">
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Status Aktif</label>
                    <select name="status_aktif" class="form-select">
                        <option value="1" <?= ((string) ($data_form['status_aktif'] ?? '1') === '1') ? 'selected' : '' ?>>Aktif</option>
                        <option value="0" <?= ((string) ($data_form['status_aktif'] ?? '1') === '0') ? 'selected' : '' ?>>Nonaktif</option>
                    </select>
                </div>
            </div>

            <hr class="my-4">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="h5 mb-1">Detail Bahan Resep</h2>
                    <div class="text-muted small">Isi bahan baku yang dipakai untuk 1 resep / 1 batch hasil.</div>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm" id="btn-tambah-baris">
                    <i class="bi bi-plus-circle me-1"></i>Tambah Baris
                </button>
            </div>

            <div id="detail-container">
                <?php if (count($detail_rows) === 0): ?>
                    <?php $detail_rows[] = [
                        'id_bahan_baku' => '',
                        'jumlah_pakai' => '1',
                        'id_satuan' => '',
                        'keterangan' => '',
                    ]; ?>
                <?php endif; ?>

                <?php foreach ($detail_rows as $i => $row): ?>
                    <div class="detail-row border rounded p-3 mb-3 bg-light-subtle">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <strong>Baris <span class="nomor-baris"><?= $i + 1 ?></span></strong>
                            <button type="button" class="btn btn-outline-danger btn-sm btn-hapus-baris">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Bahan Baku <span class="text-danger">*</span></label>
                                <select name="detail[<?= $i ?>][id_bahan_baku]" class="form-select" required>
                                    <option value="">- Pilih Bahan -</option>
                                    <?php foreach (($bahan_options ?? []) as $item): ?>
                                        <option value="<?= (int) $item->id_bahan_baku ?>" <?= ((string) ($row['id_bahan_baku'] ?? '') === (string) $item->id_bahan_baku) ? 'selected' : '' ?>>
                                            <?= esc($item->kode_bahan_baku . ' - ' . $item->nama_bahan_baku) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Jumlah Pakai <span class="text-danger">*</span></label>
                                <input type="number" name="detail[<?= $i ?>][jumlah_pakai]" class="form-control jumlah-pakai-input" min="0" step="1" inputmode="numeric" pattern="[0-9]*" required value="<?= esc((string) resep_jumlah_pakai_int($row['jumlah_pakai'] ?? 1)) ?>">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Satuan <span class="text-danger">*</span></label>
                                <select name="detail[<?= $i ?>][id_satuan]" class="form-select" required>
                                    <option value="">- Pilih Satuan -</option>
                                    <?php foreach (($satuan_options ?? []) as $item): ?>
                                        <option value="<?= (int) $item->id_satuan ?>" <?= ((string) ($row['id_satuan'] ?? '') === (string) $item->id_satuan) ? 'selected' : '' ?>>
                                            <?= esc($item->nama_satuan) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label fw-semibold">Keterangan</label>
                                <input type="text" name="detail[<?= $i ?>][keterangan]" class="form-control" maxlength="255" value="<?= esc($row['keterangan'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-gradient">
                    <i class="bi bi-check-circle me-1"></i><?= esc($button_label ?? 'Simpan') ?>
                </button>
                <a href="<?= esc(admin_page_url('master_setup/resep')) ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Kembali
                </a>
            </div>
        </form>
    </div>
</div>

<template id="template-detail-row">
    <div class="detail-row border rounded p-3 mb-3 bg-light-subtle">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <strong>Baris <span class="nomor-baris">1</span></strong>
            <button type="button" class="btn btn-outline-danger btn-sm btn-hapus-baris">
                <i class="bi bi-trash"></i>
            </button>
        </div>

        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label fw-semibold">Bahan Baku <span class="text-danger">*</span></label>
                <select data-name="id_bahan_baku" class="form-select" required>
                    <option value="">- Pilih Bahan -</option>
                    <?php foreach (($bahan_options ?? []) as $item): ?>
                        <option value="<?= (int) $item->id_bahan_baku ?>">
                            <?= esc($item->kode_bahan_baku . ' - ' . $item->nama_bahan_baku) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold">Jumlah Pakai <span class="text-danger">*</span></label>
                <input type="number" data-name="jumlah_pakai" class="form-control jumlah-pakai-input" min="0" step="1" inputmode="numeric" pattern="[0-9]*" value="1" required>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold">Satuan <span class="text-danger">*</span></label>
                <select data-name="id_satuan" class="form-select" required>
                    <option value="">- Pilih Satuan -</option>
                    <?php foreach (($satuan_options ?? []) as $item): ?>
                        <option value="<?= (int) $item->id_satuan ?>">
                            <?= esc($item->nama_satuan) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label fw-semibold">Keterangan</label>
                <input type="text" data-name="keterangan" class="form-control" maxlength="255">
            </div>
        </div>
    </div>
</template>

<script>
function refreshNomorBarisResep() {
    document.querySelectorAll('#detail-container .detail-row').forEach((row, index) => {
        row.querySelector('.nomor-baris').textContent = index + 1;
    });
}

function rebuildNamesResep() {
    document.querySelectorAll('#detail-container .detail-row').forEach((row, index) => {
        row.querySelectorAll('[data-name], input[name^="detail["], select[name^="detail["]').forEach((field) => {
            const key = field.getAttribute('data-name') || field.name.match(/\]\[(.*?)\]$/)?.[1];
            if (!key) return;
            field.name = `detail[${index}][${key}]`;
        });
    });
}

function bersihkanJumlahPakaiInt(field) {
    if (!field.classList.contains('jumlah-pakai-input')) return;

    const value = String(field.value || '').replace(/[^0-9]/g, '');
    field.value = value === '' ? '' : String(parseInt(value, 10));
}

document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('detail-container');
    const template = document.getElementById('template-detail-row');

    document.getElementById('btn-tambah-baris').addEventListener('click', function () {
        const clone = template.content.cloneNode(true);
        container.appendChild(clone);
        rebuildNamesResep();
        refreshNomorBarisResep();
    });

    container.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-hapus-baris');
        if (!btn) return;

        const rows = container.querySelectorAll('.detail-row');
        if (rows.length <= 1) {
            alert('Minimal harus ada 1 baris detail.');
            return;
        }

        btn.closest('.detail-row').remove();
        rebuildNamesResep();
        refreshNomorBarisResep();
    });

    container.addEventListener('input', function (e) {
        bersihkanJumlahPakaiInt(e.target);
    });

    document.getElementById('form-resep').addEventListener('submit', function () {
        document.querySelectorAll('.jumlah-pakai-input').forEach(bersihkanJumlahPakaiInt);
    });

    rebuildNamesResep();
    refreshNomorBarisResep();
});
</script>