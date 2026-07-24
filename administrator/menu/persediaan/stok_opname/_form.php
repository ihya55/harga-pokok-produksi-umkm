<?php
declare(strict_types=1);

$gudang_options = $gudang_options ?? [];
$saldo_options = $saldo_options ?? [];
$detail_rows = $detail_rows ?? [];
$data_form = $data_form ?? [];

$is_edit = !empty($data_form['id_stok_opname']);
$back_url = (string) ($data_form['back_url'] ?? admin_page_url('persediaan/stok-opname'));

function format_input_stok_opname($value, int $decimal = 2): string
{
    return number_format((float) $value, $decimal, '.', ',');
}

$saldo_js = [];

foreach ($saldo_options as $s) {
    $saldo_js[] = [
        'id_gudang' => (int) $s->id_gudang,
        'jenis_barang' => (string) $s->jenis_barang,
        'id_referensi_barang' => (int) $s->id_referensi_barang,
        'qty_sistem' => (float) $s->qty_saldo,
        'harga_satuan' => (float) $s->hpp_rata_rata,
        'kode_barang' => (string) ($s->kode_barang ?? ''),
        'nama_barang' => (string) ($s->nama_barang ?? ''),
        'nama_satuan' => (string) ($s->nama_satuan ?? ''),
        'label' => (string) (($s->kode_barang ?? '-') . ' - ' . ($s->nama_barang ?? '-') . ' - ' . ($s->nama_satuan ?? '-')),
    ];
}
?>

<div class="page-header mb-4">
    <h1 class="page-title"><?= $is_edit ? 'Edit Stok Opname' : 'Tambah Stok Opname' ?></h1>
    <p class="page-subtitle">
        Input hasil stok fisik. Selisih akan dihitung dari stok sistem dan stok fisik.
    </p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= esc((string) $form_action) ?>" id="form-stok-opname">
            <input type="hidden" name="back_url" value="<?= esc((string) $back_url) ?>">

            <?php if ($is_edit): ?>
                <input type="hidden" name="id_stok_opname" value="<?= (int) $data_form['id_stok_opname'] ?>">
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div>
                    <h2 class="h5 mb-1">Informasi Stok Opname</h2>
                    <div class="text-muted small">
                        Pilih gudang terlebih dahulu, lalu masukkan barang yang akan dihitung fisiknya.
                    </div>
                </div>

                <a href="<?= esc((string) $back_url) ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Kembali
                </a>
            </div>

            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">No Stok Opname</label>
                    <input
                        type="text"
                        class="form-control"
                        value="<?= esc((string) (($data_form['no_stok_opname'] ?? '') ?: 'Otomatis')) ?>"
                        readonly>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Tanggal Opname <span class="text-danger">*</span></label>
                    <input
                        type="date"
                        name="tanggal_stok_opname"
                        class="form-control"
                        value="<?= esc((string) ($data_form['tanggal_stok_opname'] ?? date('Y-m-d'))) ?>"
                        required>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Gudang <span class="text-danger">*</span></label>
                    <select
                        name="id_gudang"
                        id="id_gudang"
                        class="form-select"
                        onchange="refreshGudangStokOpname()"
                        required>
                        <option value="">- Pilih Gudang -</option>
                        <?php foreach ($gudang_options as $g): ?>
                            <option
                                value="<?= (int) $g->id_gudang ?>"
                                <?= ((string) ($data_form['id_gudang'] ?? '') === (string) $g->id_gudang) ? 'selected' : '' ?>>
                                <?= esc((string) (($g->kode_gudang ?? '-') . ' - ' . ($g->nama_gudang ?? '-'))) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Barang detail hanya bisa dari gudang yang dipilih.</div>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Status</label>
                    <input
                        type="text"
                        class="form-control"
                        value="<?= esc(ucfirst((string) ($data_form['status_posting'] ?? 'draft'))) ?>"
                        readonly>
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Catatan</label>
                    <textarea name="catatan" class="form-control" rows="2"><?= esc((string) ($data_form['catatan'] ?? '')) ?></textarea>
                </div>
            </div>

            <hr class="my-4">

            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <h2 class="h5 mb-1">Detail Barang</h2>
                    <div class="text-muted small">
                        Qty sistem dan HPP rata-rata diambil dari saldo stok gudang terpilih. Qty tidak ditotal lintas satuan.
                    </div>
                </div>

                <button type="button" class="btn btn-outline-primary btn-sm" onclick="tambahBarisStokOpname()">
                    <i class="bi bi-plus-circle me-1"></i>Tambah Baris
                </button>
            </div>

            <div class="table-responsive border rounded">
                <table class="table align-middle mb-0" id="table-detail-stok-opname">
                    <thead class="table-light">
                        <tr>
                            <th width="55" class="text-center">No</th>
                            <th width="150">Jenis</th>
                            <th>Barang</th>
                            <th width="105">Satuan</th>
                            <th width="135" class="text-end">Qty Sistem</th>
                            <th width="135" class="text-end">Qty Fisik</th>
                            <th width="135" class="text-end">Selisih</th>
                            <th width="165" class="text-end">Harga Satuan</th>
                            <th width="165" class="text-end">Nilai Selisih</th>
                            <th width="80" class="text-center">Aksi</th>
                        </tr>
                    </thead>

                    <tbody id="detail-body">
                        <?php foreach ($detail_rows as $i => $d): ?>
                            <?php
                            $jenis = (string) ($d->jenis_barang ?? 'bahan_baku');
                            $id_ref = (int) ($d->id_referensi_barang ?? 0);
                            ?>
                            <tr class="detail-row">
                                <td class="text-center row-no"><?= $i + 1 ?></td>

                                <td>
                                    <select name="jenis_barang[]" class="form-select detail-jenis" required onchange="refreshBarangOptionsStokOpname(this)">
                                        <option value="bahan_baku" <?= $jenis === 'bahan_baku' ? 'selected' : '' ?>>Bahan Baku</option>
                                        <option value="produk" <?= $jenis === 'produk' ? 'selected' : '' ?>>Produk</option>
                                    </select>
                                </td>

                                <td>
                                    <select
                                        name="id_referensi_barang[]"
                                        class="form-select detail-barang"
                                        data-selected="<?= (int) $id_ref ?>"
                                        required
                                        onchange="refreshBarangRowStokOpname(this)">
                                        <option value="">- Pilih Barang -</option>
                                    </select>
                                </td>

                                <td>
                                    <input type="text" class="form-control detail-satuan" value="" readonly>
                                </td>

                                <td>
                                    <input
                                        type="text"
                                        name="qty_sistem_display[]"
                                        class="form-control text-end detail-qty-sistem-display"
                                        value="<?= esc(format_input_stok_opname($d->qty_sistem ?? 0, 2)) ?>"
                                        readonly>
                                    <input
                                        type="hidden"
                                        name="qty_sistem[]"
                                        class="detail-qty-sistem"
                                        value="<?= esc(number_format((float) ($d->qty_sistem ?? 0), 3, '.', '')) ?>">
                                </td>

                                <td>
                                    <input
                                        type="text"
                                        name="qty_fisik[]"
                                        class="form-control text-end detail-qty-fisik"
                                        value="<?= esc(format_input_stok_opname($d->qty_fisik ?? 0, 2)) ?>"
                                        onblur="refreshSubtotalStokOpname(this)"
                                        required>
                                </td>

                                <td>
                                    <input
                                        type="text"
                                        name="selisih_qty_display[]"
                                        class="form-control text-end detail-selisih-display"
                                        value="<?= esc(format_input_stok_opname($d->selisih_qty ?? 0, 2)) ?>"
                                        readonly>
                                    <input
                                        type="hidden"
                                        name="selisih_qty[]"
                                        class="detail-selisih"
                                        value="<?= esc(number_format((float) ($d->selisih_qty ?? 0), 3, '.', '')) ?>">
                                </td>

                                <td>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input
                                            type="text"
                                            name="harga_satuan_display[]"
                                            class="form-control text-end detail-harga-display"
                                            value="<?= esc(format_input_stok_opname($d->harga_satuan ?? 0, 2)) ?>"
                                            readonly>
                                    </div>
                                    <input
                                        type="hidden"
                                        name="harga_satuan[]"
                                        class="detail-harga"
                                        value="<?= esc(number_format((float) ($d->harga_satuan ?? 0), 2, '.', '')) ?>">
                                </td>

                                <td>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input
                                            type="text"
                                            name="nilai_selisih_display[]"
                                            class="form-control text-end detail-nilai-display"
                                            value="<?= esc(format_input_stok_opname($d->nilai_selisih ?? 0, 2)) ?>"
                                            readonly>
                                    </div>
                                    <input
                                        type="hidden"
                                        name="nilai_selisih[]"
                                        class="detail-nilai"
                                        value="<?= esc(number_format((float) ($d->nilai_selisih ?? 0), 2, '.', '')) ?>">
                                </td>

                                <td class="text-center">
                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="hapusBarisStokOpname(this)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>

                    <tfoot class="table-light">
                        <tr>
                            <th colspan="8" class="text-end">Total Nilai Selisih</th>
                            <th>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" id="grand-total-display" class="form-control text-end fw-semibold" value="0.00" readonly>
                                </div>
                            </th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="alert alert-warning mt-3 mb-0">
                <i class="bi bi-exclamation-triangle me-1"></i>
                Saat posting, hanya baris yang memiliki selisih qty yang akan membuat mutasi stok dan jurnal koreksi.
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="<?= esc((string) $back_url) ?>" class="btn btn-outline-secondary">
                    Batal
                </a>

                <button type="submit" class="btn btn-gradient">
                    <i class="bi bi-save me-1"></i>Simpan Draft
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const saldoStokOpname = <?= json_encode($saldo_js, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

function parseNumberStokOpname(value) {
    value = String(value || '0').replace(/,/g, '');
    const number = parseFloat(value);
    return isNaN(number) ? 0 : number;
}

function formatNumberStokOpname(value, decimal = 2) {
    const number = parseFloat(value || 0);
    return number.toLocaleString('en-US', {
        minimumFractionDigits: decimal,
        maximumFractionDigits: decimal
    });
}

function escapeHtmlStokOpname(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function getGudangTerpilihStokOpname() {
    return parseInt(document.getElementById('id_gudang').value || '0', 10);
}

function getBarangOptionsStokOpname(jenis) {
    const idGudang = getGudangTerpilihStokOpname();

    return saldoStokOpname.filter(function(item) {
        return parseInt(item.id_gudang, 10) === idGudang
            && String(item.jenis_barang) === String(jenis);
    });
}

function buildBarangOptionsStokOpname(jenis, selectedId = '') {
    let html = '<option value="">- Pilih Barang -</option>';

    getBarangOptionsStokOpname(jenis).forEach(function(item) {
        const selected = String(selectedId) === String(item.id_referensi_barang) ? 'selected' : '';

        html += '<option ' +
            'value="' + item.id_referensi_barang + '" ' +
            'data-satuan="' + escapeHtmlStokOpname(item.nama_satuan) + '" ' +
            'data-qty="' + item.qty_sistem + '" ' +
            'data-harga="' + item.harga_satuan + '" ' +
            selected + '>' +
            escapeHtmlStokOpname(item.label) +
            '</option>';
    });

    return html;
}

function tambahBarisStokOpname() {
    const idGudang = getGudangTerpilihStokOpname();

    if (!idGudang) {
        alert('Pilih gudang terlebih dahulu.');
        return;
    }

    const tbody = document.getElementById('detail-body');
    const tr = document.createElement('tr');

    tr.className = 'detail-row';
    tr.innerHTML = `
        <td class="text-center row-no"></td>

        <td>
            <select name="jenis_barang[]" class="form-select detail-jenis" required onchange="refreshBarangOptionsStokOpname(this)">
                <option value="bahan_baku">Bahan Baku</option>
                <option value="produk">Produk</option>
            </select>
        </td>

        <td>
            <select name="id_referensi_barang[]" class="form-select detail-barang" required onchange="refreshBarangRowStokOpname(this)">
                ${buildBarangOptionsStokOpname('bahan_baku')}
            </select>
        </td>

        <td>
            <input type="text" class="form-control detail-satuan" value="" readonly>
        </td>

        <td>
            <input type="text" name="qty_sistem_display[]" class="form-control text-end detail-qty-sistem-display" value="0.00" readonly>
            <input type="hidden" name="qty_sistem[]" class="detail-qty-sistem" value="0.000">
        </td>

        <td>
            <input type="text" name="qty_fisik[]" class="form-control text-end detail-qty-fisik" value="0.00" onblur="refreshSubtotalStokOpname(this)" required>
        </td>

        <td>
            <input type="text" name="selisih_qty_display[]" class="form-control text-end detail-selisih-display" value="0.00" readonly>
            <input type="hidden" name="selisih_qty[]" class="detail-selisih" value="0.000">
        </td>

        <td>
            <div class="input-group">
                <span class="input-group-text">Rp</span>
                <input type="text" name="harga_satuan_display[]" class="form-control text-end detail-harga-display" value="0.00" readonly>
            </div>
            <input type="hidden" name="harga_satuan[]" class="detail-harga" value="0.00">
        </td>

        <td>
            <div class="input-group">
                <span class="input-group-text">Rp</span>
                <input type="text" name="nilai_selisih_display[]" class="form-control text-end detail-nilai-display" value="0.00" readonly>
            </div>
            <input type="hidden" name="nilai_selisih[]" class="detail-nilai" value="0.00">
        </td>

        <td class="text-center">
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="hapusBarisStokOpname(this)">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;

    tbody.appendChild(tr);
    refreshNomorStokOpname();
}

function refreshBarangOptionsStokOpname(selectJenis) {
    const row = selectJenis.closest('tr');
    const barangSelect = row.querySelector('.detail-barang');

    barangSelect.innerHTML = buildBarangOptionsStokOpname(selectJenis.value);

    row.querySelector('.detail-satuan').value = '';
    row.querySelector('.detail-qty-sistem-display').value = '0.00';
    row.querySelector('.detail-qty-sistem').value = '0.000';
    row.querySelector('.detail-qty-fisik').value = '0.00';
    row.querySelector('.detail-selisih-display').value = '0.00';
    row.querySelector('.detail-selisih').value = '0.000';
    row.querySelector('.detail-harga-display').value = '0.00';
    row.querySelector('.detail-harga').value = '0.00';
    row.querySelector('.detail-nilai-display').value = '0.00';
    row.querySelector('.detail-nilai').value = '0.00';

    refreshGrandTotalStokOpname();
}

function refreshBarangRowStokOpname(selectBarang) {
    const row = selectBarang.closest('tr');
    const selected = selectBarang.options[selectBarang.selectedIndex];

    const satuan = selected.getAttribute('data-satuan') || '';
    const qty = parseNumberStokOpname(selected.getAttribute('data-qty') || '0');
    const harga = parseNumberStokOpname(selected.getAttribute('data-harga') || '0');

    row.querySelector('.detail-satuan').value = satuan;
    row.querySelector('.detail-qty-sistem-display').value = formatNumberStokOpname(qty, 2);
    row.querySelector('.detail-qty-sistem').value = qty.toFixed(3);
    row.querySelector('.detail-qty-fisik').value = formatNumberStokOpname(qty, 2);
    row.querySelector('.detail-harga-display').value = formatNumberStokOpname(harga, 2);
    row.querySelector('.detail-harga').value = harga.toFixed(2);

    refreshSubtotalStokOpname(selectBarang);
}

function refreshSubtotalStokOpname(element) {
    const row = element.closest('tr');

    const qtySistem = parseNumberStokOpname(row.querySelector('.detail-qty-sistem').value);
    const qtyFisikInput = row.querySelector('.detail-qty-fisik');
    const harga = parseNumberStokOpname(row.querySelector('.detail-harga').value);

    const qtyFisik = parseNumberStokOpname(qtyFisikInput.value);
    const selisih = qtyFisik - qtySistem;
    const nilaiSelisih = selisih * harga;

    qtyFisikInput.value = formatNumberStokOpname(qtyFisik, 2);
    row.querySelector('.detail-selisih-display').value = formatNumberStokOpname(selisih, 2);
    row.querySelector('.detail-selisih').value = selisih.toFixed(3);
    row.querySelector('.detail-nilai-display').value = formatNumberStokOpname(nilaiSelisih, 2);
    row.querySelector('.detail-nilai').value = nilaiSelisih.toFixed(2);

    refreshGrandTotalStokOpname();
}

function refreshGrandTotalStokOpname() {
    let total = 0;

    document.querySelectorAll('.detail-nilai').forEach(function(input) {
        total += parseNumberStokOpname(input.value);
    });

    document.getElementById('grand-total-display').value = formatNumberStokOpname(total, 2);
}

function hapusBarisStokOpname(button) {
    const rows = document.querySelectorAll('#detail-body .detail-row');

    if (rows.length <= 1) {
        alert('Minimal harus ada 1 baris detail.');
        return;
    }

    button.closest('tr').remove();
    refreshNomorStokOpname();
    refreshGrandTotalStokOpname();
}

function refreshNomorStokOpname() {
    document.querySelectorAll('#detail-body .row-no').forEach(function(td, index) {
        td.textContent = index + 1;
    });
}

function refreshGudangStokOpname() {
    const rows = document.querySelectorAll('#detail-body .detail-row');

    if (rows.length > 0) {
        const ok = confirm('Mengubah gudang akan mengosongkan detail barang. Lanjutkan?');

        if (!ok) {
            return;
        }
    }

    document.getElementById('detail-body').innerHTML = '';
    tambahBarisStokOpname();
    refreshGrandTotalStokOpname();
}

document.addEventListener('DOMContentLoaded', function() {
    const rows = document.querySelectorAll('#detail-body .detail-row');

    if (rows.length === 0) {
        if (getGudangTerpilihStokOpname()) {
            tambahBarisStokOpname();
        }
    } else {
        rows.forEach(function(row) {
            const jenisSelect = row.querySelector('.detail-jenis');
            const barangSelect = row.querySelector('.detail-barang');
            const selectedId = barangSelect.getAttribute('data-selected') || '';

            barangSelect.innerHTML = buildBarangOptionsStokOpname(jenisSelect.value, selectedId);

            const selected = barangSelect.options[barangSelect.selectedIndex];
            if (selected && selected.value) {
                row.querySelector('.detail-satuan').value = selected.getAttribute('data-satuan') || '';
            }

            refreshSubtotalStokOpname(row.querySelector('.detail-qty-fisik'));
        });
    }

    refreshNomorStokOpname();
    refreshGrandTotalStokOpname();
});
</script>