<?php
$perintah_options = $perintah_options ?? [];
$template_biaya_options = $template_biaya_options ?? [];
$detail_rows = $detail_rows ?? [];

$back_url = (string) ($data_form['back_url'] ?? admin_page_url('produksi/biaya'));

if (!function_exists('kode_template_biaya_produksi_form')) {
    function kode_template_biaya_produksi_form(string $jenis): string
    {
        $jenis = trim($jenis);

        $legacy = [
            'tenaga_kerja' => 'BIAYA_TENAGA_KERJA_LANGSUNG',
            'overhead'     => 'BIAYA_OVERHEAD_PABRIK',
            'listrik'      => 'BIAYA_LISTRIK_PRODUKSI',
            'kemasan'      => 'BIAYA_KEMASAN_PRODUKSI',
            'lain_lain'    => 'BIAYA_LAIN_LAIN_PRODUKSI',
        ];

        return $legacy[$jenis] ?? $jenis;
    }
}

$template_js = [];

foreach ($template_biaya_options as $tpl) {
    $kode_jenis = (string) ($tpl->kode_jenis_transaksi ?? '');
    $template_js[] = [
        'id_template_jurnal'      => (int) ($tpl->id_template_jurnal ?? 0),
        'kode_template_jurnal'    => (string) ($tpl->kode_template_jurnal ?? ''),
        'kode_jenis_transaksi'    => $kode_jenis,
        'nama_template_jurnal'    => (string) ($tpl->nama_template_jurnal ?? ''),
        'id_coa_kredit'           => (int) ($tpl->id_coa_kredit ?? ($tpl->id_coa ?? 0)),
        'akun_debit'              => (string) (($tpl->kode_coa_debit ?? '-') . ' - ' . ($tpl->nama_coa_debit ?? '-')),
        'akun_kredit'             => (string) (($tpl->kode_coa_kredit ?? ($tpl->kode_coa ?? '-')) . ' - ' . ($tpl->nama_coa_kredit ?? ($tpl->nama_coa ?? '-'))),
        'sumber_nominal'          => (string) ($tpl->sumber_nominal ?? 'total_biaya'),
        'rumus_nominal'           => (string) ($tpl->rumus_nominal ?? ''),
        'label'                   => (string) (($tpl->kode_template_jurnal ?? '-') . ' | ' . ($tpl->nama_template_jurnal ?? '-') . ' | Kredit: ' . ($tpl->kode_coa_kredit ?? ($tpl->kode_coa ?? '-')) . ' - ' . ($tpl->nama_coa_kredit ?? ($tpl->nama_coa ?? '-'))),
    ];
}
?>

<div class="page-header mb-4">
    <h1 class="page-title"><?= esc($page_title ?? 'Form Biaya Produksi') ?></h1>
    <p class="page-subtitle"><?= esc($page_subtitle ?? '') ?></p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= esc($form_action) ?>" id="form-biaya-produksi">
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
            </div>

            <hr class="my-4">

            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <h2 class="h5 mb-1">Detail Biaya Produksi</h2>
                    <div class="text-muted small">Template jurnal diambil langsung dari <code>tb_template_jurnal</code> dan <code>tb_template_jurnal_detail</code>. Akun debit, akun kredit, dan sumber nominal mengikuti template.</div>
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
                            <th width="600">Template Jurnal</th>
                            <th width="220" class="text-end">Jumlah Biaya</th>
                            <th>Keterangan</th>
                            <th width="80" class="text-center">Aksi</th>
                        </tr>
                    </thead>

                    <tbody id="detail-body">
                        <?php foreach ($detail_rows as $i => $d): ?>
                            <?php
                            $selected_kode = kode_template_biaya_produksi_form((string) ($d['jenis_biaya_produksi'] ?? ''));
                            $selected_coa = (int) ($d['id_coa_lawan'] ?? 0);
                            ?>
                            <tr class="detail-row">
                                <td class="text-center row-no"><?= $i + 1 ?></td>

                                <td>
                                    <select name="jenis_biaya_produksi_detail[]" class="form-select detail-template" required data-selected-kode="<?= esc($selected_kode) ?>" data-selected-coa="<?= (int) $selected_coa ?>" onchange="refreshTemplateInfo(this)">
                                        <option value="">- Pilih Template Jurnal -</option>
                                        <?php foreach ($template_js as $tpl): ?>
                                            <option
                                                value="<?= esc($tpl['kode_jenis_transaksi']) ?>"
                                                data-id-coa-kredit="<?= (int) $tpl['id_coa_kredit'] ?>"
                                                data-akun-debit="<?= esc($tpl['akun_debit']) ?>"
                                                data-akun-kredit="<?= esc($tpl['akun_kredit']) ?>"
                                                data-sumber-nominal="<?= esc($tpl['sumber_nominal']) ?>"
                                                data-rumus-nominal="<?= esc($tpl['rumus_nominal']) ?>"
                                                <?= ($selected_kode === $tpl['kode_jenis_transaksi']) ? 'selected' : '' ?>>
                                                <?= esc($tpl['label']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="hidden" name="id_coa_lawan_detail[]" class="detail-coa-kredit" value="<?= (int) $selected_coa ?>">
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

            <?php if (count($template_js) === 0): ?>
                <div class="alert alert-warning mt-3 mb-0">
                    Template jurnal biaya produksi belum tersedia atau belum aktif. Tambahkan template di menu Master Setup &gt; Template Jurnal.
                </div>
            <?php endif; ?>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-gradient" <?= count($template_js) === 0 ? 'disabled' : '' ?>>
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
}

function formatMoneyBiayaWithRp(value) {
    return 'Rp ' + formatMoneyBiaya(value);
}

function refreshTemplateInfo(templateSelect) {
    const row = templateSelect.closest('tr');
    const note = row.querySelector('.detail-template-note');
    const coaInput = row.querySelector('.detail-coa-kredit');
    const selected = templateSelect.options[templateSelect.selectedIndex];

    if (!selected || !selected.value) {
        coaInput.value = '';
        note.textContent = 'Template jurnal belum dipilih.';
        return;
    }

    const idCoaKredit = selected.getAttribute('data-id-coa-kredit') || '';
    const akunDebit = selected.getAttribute('data-akun-debit') || '-';
    const akunKredit = selected.getAttribute('data-akun-kredit') || '-';
    const sumberNominal = selected.getAttribute('data-sumber-nominal') || '-';
    const rumusNominal = selected.getAttribute('data-rumus-nominal') || '';

    coaInput.value = idCoaKredit;
    note.innerHTML =
        '<div><strong>Debit:</strong> ' + akunDebit + '</div>' +
        '<div><strong>Kredit:</strong> ' + akunKredit + '</div>' +
        '<div><strong>Sumber Nominal:</strong> ' + sumberNominal + (rumusNominal ? ' | <strong>Rumus:</strong> ' + rumusNominal : '') + '</div>';
}

function tambahBarisBiaya() {
    const tbody = document.getElementById('detail-body');

    const tr = document.createElement('tr');
    tr.className = 'detail-row';

    let templateOptionsHtml = '<option value="">- Pilih Template Jurnal -</option>';
    const firstTemplateSelect = document.querySelector('.detail-template');

    if (firstTemplateSelect) {
        Array.from(firstTemplateSelect.options).forEach(function(opt, idx) {
            if (idx === 0) {
                return;
            }
            templateOptionsHtml += opt.outerHTML;
        });
    }

    tr.innerHTML = `
        <td class="text-center row-no"></td>
        <td>
            <select name="jenis_biaya_produksi_detail[]" class="form-select detail-template" required onchange="refreshTemplateInfo(this)">
                ${templateOptionsHtml}
            </select>
            <input type="hidden" name="id_coa_lawan_detail[]" class="detail-coa-kredit" value="">
        </td>
        <td>
            <div class="small detail-template-note text-muted">Pilih template jurnal.</div>
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
    const select = tr.querySelector('.detail-template');
    if (select.options.length > 1) {
        select.selectedIndex = 1;
    }
    refreshTemplateInfo(select);
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
        const kodeTemplate = row.querySelector('.detail-template').value;
        const idCoa = row.querySelector('.detail-coa-kredit').value;
        const jumlah = parseMoneyBiaya(row.querySelector('.detail-jumlah-display').value);

        if (!kodeTemplate || !idCoa || jumlah <= 0) {
            valid = false;
        }
    });

    if (!valid) {
        e.preventDefault();
        alert('Semua detail wajib memilih template jurnal dari database dan jumlah biaya lebih dari 0.');
        return false;
    }

    if (total <= 0) {
        e.preventDefault();
        alert('Total biaya wajib lebih besar dari 0.');
        return false;
    }
});

document.querySelectorAll('.detail-row').forEach(function(row) {
    const select = row.querySelector('.detail-template');
    if (!select.value && select.options.length > 1) {
        select.selectedIndex = 1;
    }
    refreshTemplateInfo(select);
});

refreshNomorBaris();
refreshTotalBiaya();
</script>
