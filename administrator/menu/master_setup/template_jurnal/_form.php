<?php
$detail_rows = $detail_rows ?? [];
$coa_options = $coa_options ?? [];
$jenis_transaksi_options = opsi_jenis_transaksi_template();
$isEdit = !empty($data_form['id_template_jurnal']);
?>

<div class="page-header mb-4">
    <h1 class="page-title"><?= esc($page_title ?? 'Form Template Jurnal') ?></h1>
    <p class="page-subtitle"><?= esc($page_subtitle ?? '') ?></p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= esc($form_action) ?>" id="form-template-jurnal">
            <?php if ($isEdit): ?>
                <input type="hidden" name="id_template_jurnal" value="<?= (int) $data_form['id_template_jurnal'] ?>">
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Kode Template</label>
                    <input type="text" class="form-control" value="<?= esc($data_form['kode_template_jurnal'] ?? 'Otomatis saat disimpan') ?>" readonly>
                </div>

                <div class="col-md-8">
                    <label class="form-label fw-semibold">Nama Template <span class="text-danger">*</span></label>
                    <input
                        type="text"
                        name="nama_template_jurnal"
                        class="form-control"
                        maxlength="150"
                        required
                        value="<?= esc($data_form['nama_template_jurnal'] ?? '') ?>"
                        placeholder="Contoh: Template Penjualan Tunai"
                    >
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Jenis Transaksi <span class="text-danger">*</span></label>
                    <select name="kode_jenis_transaksi" class="form-select" required>
                        <option value="">- Pilih Jenis Transaksi -</option>
                        <?php foreach ($jenis_transaksi_options as $kode => $label): ?>
                            <option value="<?= esc($kode) ?>" <?= (($data_form['kode_jenis_transaksi'] ?? '') === $kode) ? 'selected' : '' ?>>
                                <?= esc($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Field ini menentukan template dipakai di form transaksi apa.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Status Aktif</label>
                    <select name="status_aktif" class="form-select">
                        <option value="1" <?= ((string) ($data_form['status_aktif'] ?? '1') === '1') ? 'selected' : '' ?>>Aktif</option>
                        <option value="0" <?= ((string) ($data_form['status_aktif'] ?? '1') === '0') ? 'selected' : '' ?>>Nonaktif</option>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Keterangan</label>
                    <textarea name="keterangan" class="form-control" rows="3" placeholder="Keterangan template jurnal"><?= esc($data_form['keterangan'] ?? '') ?></textarea>
                </div>
            </div>

            <hr class="my-4">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="h5 mb-1">Detail Baris Template</h2>
                    <div class="text-muted small">Sumber akun selalu memakai COA Default.</div>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm" id="btn-tambah-baris">
                    <i class="bi bi-plus-circle me-1"></i>Tambah Baris
                </button>
            </div>

            <div id="detail-container">
                <?php if (count($detail_rows) === 0): ?>
                    <?php $detail_rows[] = [
                        'urutan' => 1,
                        'posisi_dc' => 'debit',
                        'id_coa_default' => '',
                        'sumber_nominal' => '',
                        'rumus_nominal' => '',
                        'keterangan_baris' => '',
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
                            <div class="col-md-2">
                                <label class="form-label fw-semibold">Urutan</label>
                                <input
                                    type="number"
                                    name="detail[<?= $i ?>][urutan]"
                                    class="form-control"
                                    min="1"
                                    value="<?= esc((string) ($row['urutan'] ?? ($i + 1))) ?>"
                                    required
                                >
                            </div>

                            <div class="col-md-2">
                                <label class="form-label fw-semibold">Posisi D/C</label>
                                <select name="detail[<?= $i ?>][posisi_dc]" class="form-select" required>
                                    <option value="debit" <?= (($row['posisi_dc'] ?? '') === 'debit') ? 'selected' : '' ?>>debit</option>
                                    <option value="kredit" <?= (($row['posisi_dc'] ?? '') === 'kredit') ? 'selected' : '' ?>>kredit</option>
                                </select>
                            </div>

                            <div class="col-md-5">
                                <label class="form-label fw-semibold">Cari COA Default</label>
                                <input
                                    type="text"
                                    class="form-control coa-filter-input"
                                    placeholder="Ketik kode akun / nama akun..."
                                    data-target="coa-select-<?= $i ?>"
                                >
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Sumber Akun</label>
                                <input type="text" class="form-control" value="coa_default" readonly>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-semibold">COA Default</label>
                                <select
                                    name="detail[<?= $i ?>][id_coa_default]"
                                    class="form-select coa-select"
                                    id="coa-select-<?= $i ?>"
                                >
                                    <option value="">- Pilih COA -</option>
                                    <?php foreach ($coa_options as $coa): ?>
                                        <option
                                            value="<?= (int) $coa->id_coa ?>"
                                            data-label="<?= esc(strtolower($coa->kode_coa . ' ' . $coa->nama_coa)) ?>"
                                            <?= ((string) ($row['id_coa_default'] ?? '') === (string) $coa->id_coa) ? 'selected' : '' ?>
                                        >
                                            <?= esc($coa->kode_coa . ' - ' . $coa->nama_coa) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Sumber Nominal</label>
                                <input
                                    type="text"
                                    name="detail[<?= $i ?>][sumber_nominal]"
                                    class="form-control"
                                    maxlength="50"
                                    value="<?= esc($row['sumber_nominal'] ?? '') ?>"
                                    placeholder="Contoh: total_transaksi"
                                    required
                                >
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Rumus Nominal</label>
                                <input
                                    type="text"
                                    name="detail[<?= $i ?>][rumus_nominal]"
                                    class="form-control"
                                    maxlength="255"
                                    value="<?= esc($row['rumus_nominal'] ?? '') ?>"
                                    placeholder="Contoh: total_transaksi * 0.11"
                                >
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Keterangan Baris</label>
                                <input
                                    type="text"
                                    name="detail[<?= $i ?>][keterangan_baris]"
                                    class="form-control"
                                    maxlength="255"
                                    value="<?= esc($row['keterangan_baris'] ?? '') ?>"
                                    placeholder="Contoh: Piutang penjualan"
                                >
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-gradient">
                    <i class="bi bi-check-circle me-1"></i><?= esc($button_label ?? 'Simpan') ?>
                </button>

                <a href="<?= esc(admin_page_url('master_setup/template_jurnal')) ?>" class="btn btn-outline-secondary">
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
            <div class="col-md-2">
                <label class="form-label fw-semibold">Urutan</label>
                <input type="number" data-name="urutan" class="form-control" min="1" value="1" required>
            </div>

            <div class="col-md-2">
                <label class="form-label fw-semibold">Posisi D/C</label>
                <select data-name="posisi_dc" class="form-select" required>
                    <option value="debit">debit</option>
                    <option value="kredit">kredit</option>
                </select>
            </div>

            <div class="col-md-5">
                <label class="form-label fw-semibold">Cari COA Default</label>
                <input
                    type="text"
                    class="form-control coa-filter-input"
                    placeholder="Ketik kode akun / nama akun..."
                >
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold">Sumber Akun</label>
                <input type="text" class="form-control" value="coa_default" readonly>
            </div>

            <div class="col-md-12">
                <label class="form-label fw-semibold">COA Default</label>
                <select data-name="id_coa_default" class="form-select coa-select">
                    <option value="">- Pilih COA -</option>
                    <?php foreach ($coa_options as $coa): ?>
                        <option value="<?= (int) $coa->id_coa ?>" data-label="<?= esc(strtolower($coa->kode_coa . ' ' . $coa->nama_coa)) ?>">
                            <?= esc($coa->kode_coa . ' - ' . $coa->nama_coa) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-semibold">Sumber Nominal</label>
                <input type="text" data-name="sumber_nominal" class="form-control" maxlength="50" placeholder="Contoh: total_transaksi" required>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-semibold">Rumus Nominal</label>
                <input type="text" data-name="rumus_nominal" class="form-control" maxlength="255" placeholder="Contoh: total_transaksi * 0.11">
            </div>

            <div class="col-md-4">
                <label class="form-label fw-semibold">Keterangan Baris</label>
                <input type="text" data-name="keterangan_baris" class="form-control" maxlength="255" placeholder="Contoh: Piutang penjualan">
            </div>
        </div>
    </div>
</template>

<script>
function refreshNomorBaris() {
    document.querySelectorAll('#detail-container .detail-row').forEach((row, index) => {
        row.querySelector('.nomor-baris').textContent = index + 1;
    });
}

function rebuildNames() {
    document.querySelectorAll('#detail-container .detail-row').forEach((row, index) => {
        row.querySelectorAll('[data-name], input[name^="detail["], select[name^="detail["]').forEach((field) => {
            const key = field.getAttribute('data-name') || field.name.match(/\]\[(.*?)\]$/)?.[1];
            if (!key) return;
            field.name = `detail[${index}][${key}]`;
        });

        const filterInput = row.querySelector('.coa-filter-input');
        const coaSelect = row.querySelector('.coa-select');

        if (coaSelect) {
            coaSelect.id = `coa-select-${index}`;
        }
        if (filterInput && coaSelect) {
            filterInput.setAttribute('data-target', `coa-select-${index}`);
        }
    });
}

function bindCoaFilter(scope) {
    scope.querySelectorAll('.coa-filter-input').forEach((input) => {
        input.addEventListener('input', function () {
            const targetId = this.getAttribute('data-target');
            const select = document.getElementById(targetId);
            if (!select) return;

            const keyword = this.value.toLowerCase().trim();

            Array.from(select.options).forEach((opt, idx) => {
                if (idx === 0) {
                    opt.hidden = false;
                    return;
                }

                const label = (opt.getAttribute('data-label') || '').toLowerCase();
                opt.hidden = keyword !== '' && !label.includes(keyword);
            });
        });
    });
}

document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('detail-container');
    const template = document.getElementById('template-detail-row');

    document.getElementById('btn-tambah-baris').addEventListener('click', function () {
        const clone = template.content.cloneNode(true);
        container.appendChild(clone);
        rebuildNames();
        refreshNomorBaris();
        bindCoaFilter(container);
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
        rebuildNames();
        refreshNomorBaris();
        bindCoaFilter(container);
    });

    rebuildNames();
    refreshNomorBaris();
    bindCoaFilter(document);
});
</script>