<?php
declare(strict_types=1);

$gudang_options = $gudang_options ?? [];
$coa_options = $coa_options ?? [];
$bahan_options = $bahan_options ?? [];
$produk_options = $produk_options ?? [];
$detail_rows = $detail_rows ?? [];
$data_form = $data_form ?? [];

$back_url = (string) ($data_form['back_url'] ?? admin_page_url('persediaan/saldo-stok/saldo-awal'));

$is_edit = !empty($data_form['id_saldo_awal_stok']);

function format_decimal_input_saldo_awal($value, int $decimal = 2): string
{
    return number_format((float) $value, $decimal, '.', ',');
}

$bahan_js = [];
foreach ($bahan_options as $b) {
    $bahan_js[] = [
        'jenis_barang' => 'bahan_baku',
        'id_referensi_barang' => (int) $b->id_bahan_baku,
        'kode' => (string) ($b->kode_bahan_baku ?? ''),
        'nama' => (string) ($b->nama_bahan_baku ?? ''),
        'satuan' => (string) ($b->nama_satuan ?? ''),
        'harga_satuan' => (float) ($b->harga_standar ?? 0),
        'label' => (string) (($b->kode_bahan_baku ?? '-') . ' - ' . ($b->nama_bahan_baku ?? '-') . ' - ' . ($b->nama_satuan ?? '-')),
    ];
}

$produk_js = [];
foreach ($produk_options as $p) {
    $produk_js[] = [
        'jenis_barang' => 'produk',
        'id_referensi_barang' => (int) $p->id_produk,
        'kode' => (string) ($p->kode_produk ?? ''),
        'nama' => (string) ($p->nama_produk ?? ''),
        'satuan' => (string) ($p->nama_satuan ?? ''),
        'harga_satuan' => (float) ($p->hpp_standar ?? 0),
        'label' => (string) (($p->kode_produk ?? '-') . ' - ' . ($p->nama_produk ?? '-') . ' - ' . ($p->nama_satuan ?? '-')),
    ];
}

$barang_js = array_merge($bahan_js, $produk_js);
?>

<div class="page-header mb-4">
    <h1 class="page-title"><?= $is_edit ? 'Edit Saldo Awal Stok' : 'Tambah Saldo Awal Stok' ?></h1>
    <p class="page-subtitle">
        Saldo awal akan diposting menjadi mutasi masuk, saldo stok, dan jurnal persediaan.
    </p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= esc($form_action) ?>" id="form-saldo-awal">
            <input type="hidden" name="back_url" value="<?= esc($back_url) ?>">

            <?php if ($is_edit): ?>
                <input type="hidden" name="id_saldo_awal_stok" value="<?= (int) $data_form['id_saldo_awal_stok'] ?>">
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div>
                    <h2 class="h5 mb-1">Informasi Saldo Awal</h2>
                    <div class="text-muted small">Isi tanggal, gudang, akun lawan ekuitas, lalu masukkan detail barang.</div>
                </div>

                <a href="<?= esc($back_url) ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Kembali
                </a>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">No Saldo Awal</label>
                    <input
                        type="text"
                        class="form-control"
                        value="<?= esc($data_form['no_saldo_awal_stok'] ?: 'Otomatis') ?>"
                        readonly>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Tanggal Saldo Awal <span class="text-danger">*</span></label>
                    <input
                        type="date"
                        name="tanggal_saldo_awal"
                        class="form-control"
                        value="<?= esc($data_form['tanggal_saldo_awal'] ?? date('Y-m-d')) ?>"
                        required>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Status</label>
                    <input
                        type="text"
                        class="form-control"
                        value="<?= esc(ucfirst((string) ($data_form['status_posting'] ?? 'draft'))) ?>"
                        readonly>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Gudang <span class="text-danger">*</span></label>
                    <select name="id_gudang" class="form-select" required>
                        <option value="">- Pilih Gudang -</option>
                        <?php foreach ($gudang_options as $g): ?>
                            <option
                                value="<?= (int) $g->id_gudang ?>"
                                <?= ((string) ($data_form['id_gudang'] ?? '') === (string) $g->id_gudang) ? 'selected' : '' ?>>
                                <?= esc(($g->kode_gudang ?? '-') . ' - ' . ($g->nama_gudang ?? '-')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Seluruh detail saldo awal akan masuk ke gudang ini.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Akun Lawan / Ekuitas <span class="text-danger">*</span></label>
                    <select name="id_coa_lawan" class="form-select" required>
                        <option value="">- Pilih Akun Ekuitas -</option>
                        <?php foreach ($coa_options as $c): ?>
                            <option
                                value="<?= (int) $c->id_coa ?>"
                                <?= ((string) ($data_form['id_coa_lawan'] ?? '') === (string) $c->id_coa) ? 'selected' : '' ?>>
                                <?= esc(($c->kode_coa ?? '-') . ' - ' . ($c->nama_coa ?? '-')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Jurnal: Debit Persediaan, Kredit akun ini.</div>
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Catatan</label>
                    <textarea name="catatan" class="form-control" rows="2"><?= esc($data_form['catatan'] ?? '') ?></textarea>
                </div>
            </div>

            <hr class="my-4">

            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <h2 class="h5 mb-1">Detail Barang</h2>
                    <div class="text-muted small">Qty dan harga satuan akan membentuk nilai saldo awal.</div>
                </div>

                <button type="button" class="btn btn-outline-primary btn-sm" onclick="tambahBarisSaldoAwal()">
                    <i class="bi bi-plus-circle me-1"></i>Tambah Baris
                </button>
            </div>

            <div class="table-responsive border rounded">
                <table class="table align-middle mb-0" id="table-detail-saldo-awal">
                    <thead class="table-light">
                        <tr>
                            <th width="55" class="text-center">No</th>
                            <th width="160">Jenis</th>
                            <th>Barang</th>
                            <th width="150" class="text-end">Qty</th>
                            <th width="120">Satuan</th>
                            <th width="190" class="text-end">Harga Satuan</th>
                            <th width="190" class="text-end">Nilai Total</th>
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
                                    <select name="jenis_barang[]" class="form-select detail-jenis" required onchange="refreshBarangOptionsSaldoAwal(this)">
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
                                        onchange="refreshBarangRowSaldoAwal(this)">
                                        <option value="">- Pilih Barang -</option>
                                    </select>
                                </td>

                                <td>
                                    <input
                                        type="text"
                                        name="qty_awal[]"
                                        class="form-control text-end detail-qty"
                                        value="<?= esc(format_decimal_input_saldo_awal($d->qty_awal ?? 0, 2)) ?>"
                                        onblur="refreshSubtotalSaldoAwal(this)"
                                        required>
                                </td>

                                <td>
                                    <input type="text" class="form-control detail-satuan" value="" readonly>
                                </td>

                                <td>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input
                                            type="text"
                                            name="harga_satuan_display[]"
                                            class="form-control text-end detail-harga-display"
                                            value="<?= esc(format_decimal_input_saldo_awal($d->harga_satuan ?? 0, 2)) ?>"
                                            onblur="refreshSubtotalSaldoAwal(this)"
                                            required>
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
                                            class="form-control text-end detail-nilai-display"
                                            value="<?= esc(format_decimal_input_saldo_awal($d->nilai_total ?? 0, 2)) ?>"
                                            readonly>
                                    </div>
                                    <input
                                        type="hidden"
                                        name="nilai_total[]"
                                        class="detail-nilai"
                                        value="<?= esc(number_format((float) ($d->nilai_total ?? 0), 2, '.', '')) ?>">
                                </td>

                                <td class="text-center">
                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="hapusBarisSaldoAwal(this)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>

                    <tfoot class="table-light">
                        <tr>
                            <th colspan="6" class="text-end">Total Nilai</th>
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

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="<?= esc($back_url) ?>" class="btn btn-outline-secondary">
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
const barangSaldoAwal = <?= json_encode($barang_js, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

function parseNumberSaldoAwal(value) {
    value = String(value || '0').replace(/,/g, '');
    const number = parseFloat(value);
    return isNaN(number) ? 0 : number;
}

function formatNumberSaldoAwal(value, decimal = 2) {
    const number = parseFloat(value || 0);
    return number.toLocaleString('en-US', {
        minimumFractionDigits: decimal,
        maximumFractionDigits: decimal
    });
}

function escapeHtmlSaldoAwal(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function getBarangSaldoAwal(jenis) {
    return barangSaldoAwal.filter(function(item) {
        return String(item.jenis_barang) === String(jenis);
    });
}

function buildBarangOptionsSaldoAwal(jenis, selectedId = '') {
    let html = '<option value="">- Pilih Barang -</option>';

    getBarangSaldoAwal(jenis).forEach(function(item) {
        const selected = String(selectedId) === String(item.id_referensi_barang) ? 'selected' : '';

        html += '<option ' +
            'value="' + item.id_referensi_barang + '" ' +
            'data-satuan="' + escapeHtmlSaldoAwal(item.satuan) + '" ' +
            'data-harga="' + item.harga_satuan + '" ' +
            selected + '>' +
            escapeHtmlSaldoAwal(item.label) +
            '</option>';
    });

    return html;
}

function tambahBarisSaldoAwal() {
    const tbody = document.getElementById('detail-body');
    const tr = document.createElement('tr');

    tr.className = 'detail-row';
    tr.innerHTML = `
        <td class="text-center row-no"></td>

        <td>
            <select name="jenis_barang[]" class="form-select detail-jenis" required onchange="refreshBarangOptionsSaldoAwal(this)">
                <option value="bahan_baku">Bahan Baku</option>
                <option value="produk">Produk</option>
            </select>
        </td>

        <td>
            <select name="id_referensi_barang[]" class="form-select detail-barang" required onchange="refreshBarangRowSaldoAwal(this)">
                ${buildBarangOptionsSaldoAwal('bahan_baku')}
            </select>
        </td>

        <td>
            <input type="text" name="qty_awal[]" class="form-control text-end detail-qty" value="0.00" onblur="refreshSubtotalSaldoAwal(this)" required>
        </td>

        <td>
            <input type="text" class="form-control detail-satuan" value="" readonly>
        </td>

        <td>
            <div class="input-group">
                <span class="input-group-text">Rp</span>
                <input type="text" name="harga_satuan_display[]" class="form-control text-end detail-harga-display" value="0.00" onblur="refreshSubtotalSaldoAwal(this)" required>
            </div>
            <input type="hidden" name="harga_satuan[]" class="detail-harga" value="0.00">
        </td>

        <td>
            <div class="input-group">
                <span class="input-group-text">Rp</span>
                <input type="text" class="form-control text-end detail-nilai-display" value="0.00" readonly>
            </div>
            <input type="hidden" name="nilai_total[]" class="detail-nilai" value="0.00">
        </td>

        <td class="text-center">
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="hapusBarisSaldoAwal(this)">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;

    tbody.appendChild(tr);
    refreshNomorSaldoAwal();
}

function refreshBarangOptionsSaldoAwal(selectJenis) {
    const row = selectJenis.closest('tr');
    const jenis = selectJenis.value;
    const barangSelect = row.querySelector('.detail-barang');

    barangSelect.innerHTML = buildBarangOptionsSaldoAwal(jenis);
    row.querySelector('.detail-satuan').value = '';
    row.querySelector('.detail-harga-display').value = '0.00';
    row.querySelector('.detail-harga').value = '0.00';

    refreshSubtotalSaldoAwal(row.querySelector('.detail-qty'));
}

function refreshBarangRowSaldoAwal(selectBarang) {
    const row = selectBarang.closest('tr');
    const selected = selectBarang.options[selectBarang.selectedIndex];

    const satuan = selected.getAttribute('data-satuan') || '';
    const harga = parseNumberSaldoAwal(selected.getAttribute('data-harga') || '0');

    row.querySelector('.detail-satuan').value = satuan;

    const hargaDisplay = row.querySelector('.detail-harga-display');
    const hargaHidden = row.querySelector('.detail-harga');

    if (parseNumberSaldoAwal(hargaDisplay.value) <= 0) {
        hargaDisplay.value = formatNumberSaldoAwal(harga, 2);
        hargaHidden.value = harga.toFixed(2);
    }

    refreshSubtotalSaldoAwal(selectBarang);
}

function refreshSubtotalSaldoAwal(element) {
    const row = element.closest('tr');

    const qtyInput = row.querySelector('.detail-qty');
    const hargaDisplay = row.querySelector('.detail-harga-display');
    const hargaHidden = row.querySelector('.detail-harga');
    const nilaiDisplay = row.querySelector('.detail-nilai-display');
    const nilaiHidden = row.querySelector('.detail-nilai');

    const qty = parseNumberSaldoAwal(qtyInput.value);
    const harga = parseNumberSaldoAwal(hargaDisplay.value);
    const nilai = qty * harga;

    qtyInput.value = formatNumberSaldoAwal(qty, 2);
    hargaDisplay.value = formatNumberSaldoAwal(harga, 2);
    hargaHidden.value = harga.toFixed(2);
    nilaiDisplay.value = formatNumberSaldoAwal(nilai, 2);
    nilaiHidden.value = nilai.toFixed(2);

    refreshGrandTotalSaldoAwal();
}

function refreshGrandTotalSaldoAwal() {
    let total = 0;

    document.querySelectorAll('.detail-nilai').forEach(function(input) {
        total += parseNumberSaldoAwal(input.value);
    });

    document.getElementById('grand-total-display').value = formatNumberSaldoAwal(total, 2);
}

function hapusBarisSaldoAwal(button) {
    const rows = document.querySelectorAll('#detail-body .detail-row');

    if (rows.length <= 1) {
        alert('Minimal harus ada 1 baris detail.');
        return;
    }

    button.closest('tr').remove();
    refreshNomorSaldoAwal();
    refreshGrandTotalSaldoAwal();
}

function refreshNomorSaldoAwal() {
    document.querySelectorAll('#detail-body .row-no').forEach(function(td, index) {
        td.textContent = index + 1;
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const rows = document.querySelectorAll('#detail-body .detail-row');

    if (rows.length === 0) {
        tambahBarisSaldoAwal();
    } else {
        rows.forEach(function(row) {
            const jenisSelect = row.querySelector('.detail-jenis');
            const barangSelect = row.querySelector('.detail-barang');
            const selectedId = barangSelect.getAttribute('data-selected') || '';

            barangSelect.innerHTML = buildBarangOptionsSaldoAwal(jenisSelect.value, selectedId);
            refreshBarangRowSaldoAwal(barangSelect);
        });
    }

    refreshNomorSaldoAwal();
    refreshGrandTotalSaldoAwal();
});
</script>