<?php
$perintah_options = $perintah_options ?? [];
$template_biaya_options = $template_biaya_options ?? [];
$detail_rows = $detail_rows ?? [];

$back_url = (string) ($data_form['back_url'] ?? admin_page_url('produksi/biaya'));

$template_js = [];

foreach ($template_biaya_options as $tpl) {
    $template_js[] = [
        'id_coa'                  => (int) $tpl->id_coa,
        'jenis_biaya_produksi'    => (string) ($tpl->jenis_biaya_produksi ?? ''),
        'label'                   => (string) (($tpl->nama_template_jurnal ?? '-') . ' | Kredit: ' . ($tpl->kode_coa ?? '-') . ' - ' . ($tpl->nama_coa ?? '-')),
        'nama_template_jurnal'    => (string) ($tpl->nama_template_jurnal ?? ''),
        'kode_jenis_transaksi'    => (string) ($tpl->kode_jenis_transaksi ?? ''),
        'coa'                     => (string) (($tpl->kode_coa ?? '-') . ' - ' . ($tpl->nama_coa ?? '-')),
    ];
}
?>

<div class="page-header mb-4">
    <h1 class="page-title"><?= esc($page_title ?? 'Form Biaya Produksi') ?></h1>
    <p class="page-subtitle"><?= esc($page_subtitle ?? '') ?></p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= esc($form_action) ?>" id="form-biaya-produksi" enctype="multipart/form-data">
            <input type="hidden" name="back_url" value="<?= esc($back_url) ?>">

            <?php if (!empty($data_form['id_biaya_produksi'])): ?>
                <input type="hidden" name="id_biaya_produksi" value="<?= (int) $data_form['id_biaya_produksi'] ?>">
            <?php endif; ?>

            <input type="hidden" name="jumlah_biaya" id="jumlah-biaya-input" value="<?= esc(number_format((float) ($data_form['jumlah_biaya'] ?? 0), 2, '.', '')) ?>">

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">No Biaya</label>
                    <input type="text" class="form-control" value="<?= esc($data_form['no_biaya_produksi'] ?? 'Otomatis saat disimpan') ?>" readonly>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Tanggal Biaya <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal_biaya" class="form-control" required value="<?= esc($data_form['tanggal_biaya'] ?? date('Y-m-d')) ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Status Posting</label>
                    <input type="text" class="form-control" value="<?= esc(ucfirst((string) ($data_form['status_posting'] ?? 'draft'))) ?>" readonly>
                </div>

                <div class="col-md-12">
                    <label class="form-label fw-semibold">Perintah Produksi <span class="text-danger">*</span></label>
                    <select name="id_perintah_produksi" class="form-select" required>
                        <option value="">- Pilih Perintah Produksi -</option>
                        <?php foreach ($perintah_options as $p): ?>
                            <option value="<?= (int) $p->id_perintah_produksi ?>" <?= ((string) ($data_form['id_perintah_produksi'] ?? '') === (string) $p->id_perintah_produksi) ? 'selected' : '' ?>>
                                <?= esc(
                                    ($p->no_perintah_produksi ?? '-') .
                                    ' - ' .
                                    ($p->kode_produk ?? '-') .
                                    ' - ' .
                                    ($p->nama_produk ?? '-') .
                                    ' - Qty ' .
                                    number_format((int) ($p->qty_rencana ?? 0), 0, '.', ',') .
                                    ' - ' .
                                    ucfirst((string) ($p->status_produksi ?? '-'))
                                ) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Perintah produksi yang sudah memiliki biaya produksi posted tidak ditampilkan lagi.</div>
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Keterangan Header</label>
                    <textarea name="keterangan" class="form-control" rows="2"><?= esc($data_form['keterangan'] ?? '') ?></textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">No Nota / Referensi</label>
                    <input type="text" name="no_nota" class="form-control" maxlength="100" value="<?= esc($data_form['no_nota'] ?? '') ?>" placeholder="Nomor nota biaya, opsional">
                </div>
                <div class="col-md-8">
                    <label class="form-label fw-semibold">Upload Nota Biaya</label>
                    <input type="file" name="file_nota" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                    <?php if (!empty($data_form['file_nota'])): ?>
                        <div class="form-text">File saat ini: <a target="_blank" href="<?= esc(base_url($data_form['file_nota'])) ?>">Lihat nota</a></div>
                    <?php else: ?>
                        <div class="form-text">Upload foto/PDF nota listrik, air, kuota, upah, atau overhead lain.</div>
                    <?php endif; ?>
                </div>
            </div>

            <hr class="my-4">

            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <h2 class="h5 mb-1">Detail Jenis Biaya</h2>
                    <div class="text-muted small">Satu perintah produksi dapat memiliki banyak jenis biaya. Jurnal mengikuti template sesuai jenis biaya.</div>
                </div>

                <button type="button" class="btn btn-outline-primary btn-sm" onclick="tambahBarisBiaya()">
                    <i class="bi bi-plus-circle me-1"></i>Tambah Baris
                </button>
            </div>

            <div class="table-responsive border rounded">
                <table class="table align-middle mb-0" id="table-detail-biaya">
                    <thead class="table-light">
                        <tr>
                            <th width="55" class="text-center">No</th>
                            <th width="190">Jenis Biaya</th>
                            <th>Template Jurnal / Sumber Biaya</th>
                            <th width="220" class="text-end">Jumlah Biaya</th>
                            <th>Keterangan</th>
                            <th width="80" class="text-center">Aksi</th>
                        </tr>
                    </thead>

                    <tbody id="detail-body">
                        <?php foreach ($detail_rows as $i => $d): ?>
                            <tr class="detail-row">
                                <td class="text-center row-no"><?= $i + 1 ?></td>

                                <td>
                                    <select name="jenis_biaya_produksi_detail[]" class="form-select detail-jenis" required onchange="refreshTemplateRow(this)">
                                        <?php
                                        $jenis_options = [
                                            'tenaga_kerja' => 'Tenaga Kerja',
                                            'overhead'     => 'Overhead Pabrik',
                                        ];
                                        ?>
                                        <?php foreach ($jenis_options as $key => $label): ?>
                                            <option value="<?= esc($key) ?>" <?= (($d['jenis_biaya_produksi'] ?? '') === $key) ? 'selected' : '' ?>>
                                                <?= esc($label) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>

                                <td>
                                    <select name="id_coa_lawan_detail[]" class="form-select detail-template" required data-selected="<?= esc($d['id_coa_lawan'] ?? '') ?>" onchange="refreshTemplateNote(this)">
                                        <option value="">- Pilih Template Jurnal -</option>
                                    </select>
                                    <input type="hidden" name="kode_jenis_transaksi_template_detail[]" class="detail-template-code" value="<?= esc($d['kode_jenis_transaksi_template'] ?? '') ?>">
                                    <div class="form-text detail-template-note">Template jurnal mengikuti jenis biaya.</div>
                                </td>

                                <td>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input
                                            type="text"
                                            name="jumlah_biaya_detail_display[]"
                                            class="form-control text-end detail-jumlah-display"
                                            value="<?= esc(number_format((float) ($d['jumlah_biaya'] ?? 0), 2, '.', ',')) ?>"
                                            onblur="formatDetailJumlah(this)"
                                            required>
                                    </div>
                                    <input type="hidden" name="jumlah_biaya_detail[]" class="detail-jumlah-input" value="<?= esc(number_format((float) ($d['jumlah_biaya'] ?? 0), 2, '.', '')) ?>">
                                </td>

                                <td>
                                    <input type="text" name="keterangan_detail[]" class="form-control" value="<?= esc($d['keterangan'] ?? '') ?>" placeholder="Opsional">
                                </td>

                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="hapusBarisBiaya(this)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>

                    <tfoot class="table-light">
                        <tr>
                            <th colspan="3" class="text-end">Total Biaya</th>
                            <th class="text-end" id="total-biaya-display">Rp 0.00</th>
                            <th colspan="2"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-gradient">
                    <i class="bi bi-check-circle me-1"></i><?= esc($button_label ?? 'Simpan') ?>
                </button>

                <a href="<?= esc($back_url) ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Kembali
                </a>
            </div>
        </form>
    </div>
</div>

<script>
const templateOptions = <?= json_encode($template_js, JSON_UNESCAPED_UNICODE) ?>;

function parseMoneyBiaya(value) {
    return parseFloat(String(value || '0').replace(/Rp/gi, '').replace(/,/g, '').replace(/[^0-9.]/g, '')) || 0;
}

function formatMoneyBiaya(value) {
    const num = parseFloat(value || 0);
    return num.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function formatMoneyBiayaWithRp(value) {
    return 'Rp ' + formatMoneyBiaya(value);
}

function escapeHtml(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function optionTemplateHtml(jenis, selectedId = '') {
    let html = '<option value="">- Pilih Template Jurnal -</option>';

    templateOptions.forEach(function(item) {
        if (item.jenis_biaya_produksi !== jenis) {
            return;
        }

        const selected = String(selectedId) === String(item.id_coa) ? 'selected' : '';
        html += '<option value="' + item.id_coa + '" data-kode-template="' + escapeHtml(item.kode_jenis_transaksi || '') + '" data-template="' + escapeHtml(item.nama_template_jurnal) + '" data-coa="' + escapeHtml(item.coa) + '" ' + selected + '>' + escapeHtml(item.label) + '</option>';
    });

    return html;
}

function refreshTemplateRow(selectJenis) {
    const row = selectJenis.closest('tr');
    const jenis = selectJenis.value;
    const templateSelect = row.querySelector('.detail-template');
    const selectedBefore = templateSelect.getAttribute('data-selected') || templateSelect.value || '';

    templateSelect.innerHTML = optionTemplateHtml(jenis, selectedBefore);

    if (!templateSelect.value) {
        const firstAvailable = Array.from(templateSelect.options).find(function(opt) {
            return opt.value;
        });

        if (firstAvailable) {
            firstAvailable.selected = true;
        }
    }

    templateSelect.setAttribute('data-selected', templateSelect.value);
    refreshTemplateNote(templateSelect);
}

function refreshTemplateNote(templateSelect) {
    const row = templateSelect.closest('tr');
    const note = row.querySelector('.detail-template-note');
    const selected = templateSelect.options[templateSelect.selectedIndex];

    templateSelect.setAttribute('data-selected', templateSelect.value);

    const hidden = row.querySelector('.detail-template-code');
    if (selected && selected.value) {
        if (hidden) hidden.value = selected.getAttribute('data-kode-template') || '';
        note.textContent = 'Template: ' + (selected.getAttribute('data-template') || '-') + ' | Akun kredit: ' + (selected.getAttribute('data-coa') || '-');
    } else {
        if (hidden) hidden.value = '';
        note.textContent = 'Template jurnal untuk jenis biaya ini belum tersedia.';
    }
}

function tambahBarisBiaya() {
    const tbody = document.getElementById('detail-body');

    const tr = document.createElement('tr');
    tr.className = 'detail-row';

    tr.innerHTML = `
        <td class="text-center row-no"></td>
        <td>
            <select name="jenis_biaya_produksi_detail[]" class="form-select detail-jenis" required onchange="refreshTemplateRow(this)">
                <option value="tenaga_kerja">Tenaga Kerja</option>
                <option value="overhead">Overhead Pabrik</option>
            </select>
        </td>
        <td>
            <select name="id_coa_lawan_detail[]" class="form-select detail-template" required data-selected="" onchange="refreshTemplateNote(this)">
                <option value="">- Pilih Template Jurnal -</option>
            </select>
            <input type="hidden" name="kode_jenis_transaksi_template_detail[]" class="detail-template-code" value="">
            <div class="form-text detail-template-note">Template jurnal mengikuti jenis biaya.</div>
        </td>
        <td>
            <div class="input-group">
                <span class="input-group-text">Rp</span>
                <input
                    type="text"
                    name="jumlah_biaya_detail_display[]"
                    class="form-control text-end detail-jumlah-display"
                    value="0.00"
                    onblur="formatDetailJumlah(this)"
                    required>
            </div>
            <input type="hidden" name="jumlah_biaya_detail[]" class="detail-jumlah-input" value="0.00">
        </td>
        <td>
            <input type="text" name="keterangan_detail[]" class="form-control" value="" placeholder="Opsional">
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="hapusBarisBiaya(this)">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;

    tbody.appendChild(tr);
    refreshTemplateRow(tr.querySelector('.detail-jenis'));
    refreshNomorBaris();
    refreshTotalBiaya();
}

function hapusBarisBiaya(button) {
    const rows = document.querySelectorAll('.detail-row');

    if (rows.length <= 1) {
        alert('Minimal harus ada 1 baris detail.');
        return;
    }

    button.closest('tr').remove();
    refreshNomorBaris();
    refreshTotalBiaya();
}

function refreshNomorBaris() {
    document.querySelectorAll('.detail-row').forEach(function(row, index) {
        row.querySelector('.row-no').textContent = index + 1;
    });
}

function formatDetailJumlah(input) {
    let jumlah = parseMoneyBiaya(input.value);

    if (jumlah < 0) {
        jumlah = 0;
    }

    input.value = formatMoneyBiaya(jumlah);

    const row = input.closest('tr');
    row.querySelector('.detail-jumlah-input').value = jumlah.toFixed(2);

    refreshTotalBiaya();
}

function refreshTotalBiaya() {
    let total = 0;

    document.querySelectorAll('.detail-row').forEach(function(row) {
        const display = row.querySelector('.detail-jumlah-display');
        const input = row.querySelector('.detail-jumlah-input');

        const jumlah = parseMoneyBiaya(display.value);
        input.value = jumlah.toFixed(2);
        total += jumlah;
    });

    document.getElementById('jumlah-biaya-input').value = total.toFixed(2);
    document.getElementById('total-biaya-display').textContent = formatMoneyBiayaWithRp(total);
}

document.getElementById('form-biaya-produksi').addEventListener('submit', function(e) {
    refreshTotalBiaya();

    const rows = document.querySelectorAll('.detail-row');
    const total = parseMoneyBiaya(document.getElementById('jumlah-biaya-input').value);
    let valid = true;

    rows.forEach(function(row) {
        const jenis = row.querySelector('.detail-jenis').value;
        const idCoa = row.querySelector('.detail-template').value;
        const jumlah = parseMoneyBiaya(row.querySelector('.detail-jumlah-display').value);

        if (!jenis || !idCoa || jumlah <= 0) {
            valid = false;
        }
    });

    if (!valid) {
        e.preventDefault();
        alert('Semua detail wajib memilih jenis biaya, template jurnal, dan jumlah biaya lebih dari 0.');
        return false;
    }

    if (total <= 0) {
        e.preventDefault();
        alert('Total biaya wajib lebih besar dari 0.');
        return false;
    }
});

document.querySelectorAll('.detail-row').forEach(function(row) {
    refreshTemplateRow(row.querySelector('.detail-jenis'));
});

refreshNomorBaris();
refreshTotalBiaya();
</script>