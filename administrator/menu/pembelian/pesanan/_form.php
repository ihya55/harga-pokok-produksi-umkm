<?php
$detail_rows = $detail_rows ?? [];

if (count($detail_rows) === 0) {
    $detail_rows[] = [
        'id_bahan_baku' => '',
        'qty'           => '1',
        'harga'         => '0.00',
        'tipe_diskon'   => 'nominal',
        'diskon'        => '0.00',
        'subtotal'      => '0.00',
    ];
}
?>

<div class="page-header mb-4">
    <h1 class="page-title"><?= esc($page_title ?? 'Form Pesanan Pembelian') ?></h1>
    <p class="page-subtitle"><?= esc($page_subtitle ?? '') ?></p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= esc($form_action) ?>" id="form-pesanan-pembelian">
            <?php if (!empty($data_form['id_pesanan_pembelian'])): ?>
                <input type="hidden" name="id_pesanan_pembelian" value="<?= (int) $data_form['id_pesanan_pembelian'] ?>">
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">No Pesanan Pembelian</label>
                    <input
                        type="text"
                        class="form-control"
                        value="<?= esc($data_form['no_pesanan_pembelian'] ?? 'Otomatis saat disimpan') ?>"
                        readonly>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Tanggal Pesanan <span class="text-danger">*</span></label>
                    <input
                        type="date"
                        name="tanggal_pesanan"
                        class="form-control"
                        required
                        value="<?= esc($data_form['tanggal_pesanan'] ?? date('Y-m-d')) ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Status Pesanan</label>
                    <input
                        type="text"
                        class="form-control"
                        value="<?= esc(ucfirst((string) ($data_form['status_pesanan'] ?? 'draft'))) ?>"
                        readonly>
                    <input type="hidden" name="status_pesanan" value="draft">
                    <div class="form-text">Status awal selalu <strong>draft</strong>. Konfirmasi lewat tombol Konfirmasi.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Pemasok <span class="text-danger">*</span></label>
                    <select name="id_pemasok" class="form-select" required>
                        <option value="">- Pilih Pemasok -</option>
                        <?php foreach (($pemasok_options ?? []) as $item): ?>
                            <option
                                value="<?= (int) $item->id_pemasok ?>"
                                <?= ((string) ($data_form['id_pemasok'] ?? '') === (string) $item->id_pemasok) ? 'selected' : '' ?>>
                                <?= esc($item->kode_pemasok . ' - ' . $item->nama_pemasok) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Tanggal Datang Rencana</label>
                    <input
                        type="date"
                        name="tanggal_datang_rencana"
                        class="form-control"
                        value="<?= esc($data_form['tanggal_datang_rencana'] ?? '') ?>">
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Catatan</label>
                    <textarea name="catatan" class="form-control" rows="3"><?= esc($data_form['catatan'] ?? '') ?></textarea>
                </div>
            </div>

            <hr class="my-4">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="h5 mb-1">Detail Bahan Baku</h2>
                    <div class="text-muted small">
                        Qty menggunakan bilangan bulat / INT. Diskon persen hanya untuk hitung form, yang disimpan tetap nominal.
                    </div>
                </div>

                <button type="button" class="btn btn-outline-primary btn-sm" id="btn-tambah-baris">
                    <i class="bi bi-plus-circle me-1"></i>Tambah Baris
                </button>
            </div>

            <div class="table-responsive border rounded">
                <table class="table table-bordered align-middle mb-0" id="table-detail-pesanan">
                    <thead class="table-light">
                        <tr>
                            <th width="50" class="text-center">No</th>
                            <th style="min-width: 260px;">Bahan Baku</th>
                            <th style="min-width: 100px;" class="text-end">Qty</th>
                            <th style="min-width: 120px;">Satuan</th>
                            <th style="min-width: 160px;" class="text-end">Harga</th>
                            <th style="min-width: 140px;">Tipe Diskon</th>
                            <th style="min-width: 160px;" class="text-end">Diskon</th>
                            <th style="min-width: 170px;" class="text-end">Subtotal</th>
                            <th width="80" class="text-center">Aksi</th>
                        </tr>
                    </thead>

                    <tbody id="detail-container">
                        <?php foreach ($detail_rows as $i => $row): ?>
                            <tr class="detail-row">
                                <td class="text-center nomor-baris"><?= $i + 1 ?></td>

                                <td>
                                    <select name="detail[<?= $i ?>][id_bahan_baku]" class="form-select bahan-select" required>
                                        <option value="">- Pilih Bahan -</option>
                                        <?php foreach (($bahan_options ?? []) as $item): ?>
                                            <option
                                                value="<?= (int) $item->id_bahan_baku ?>"
                                                data-harga="<?= esc(number_format((float) $item->harga_standar, 2, '.', '')) ?>"
                                                data-satuan="<?= esc($item->nama_satuan ?? '-') ?>"
                                                <?= ((string) ($row['id_bahan_baku'] ?? '') === (string) $item->id_bahan_baku) ? 'selected' : '' ?>>
                                                <?= esc($item->kode_bahan_baku . ' - ' . $item->nama_bahan_baku) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>

                                <td>
                                    <input
                                        type="number"
                                        name="detail[<?= $i ?>][qty]"
                                        class="form-control text-end qty-input"
                                        min="1"
                                        step="1"
                                        required
                                        value="<?= esc((string) ((int) ($row['qty'] ?? 1))) ?>">
                                </td>

                                <td>
                                    <input
                                        type="text"
                                        class="form-control satuan-display"
                                        value=""
                                        readonly>
                                </td>

                                <td>
                                    <input
                                        type="text"
                                        class="form-control text-end harga-display"
                                        value="Rp <?= esc(number_format((float) ($row['harga'] ?? 0), 2, '.', ',')) ?>"
                                        autocomplete="off">
                                    <input
                                        type="hidden"
                                        name="detail[<?= $i ?>][harga]"
                                        class="harga-input"
                                        value="<?= esc(number_format((float) ($row['harga'] ?? 0), 2, '.', '')) ?>">
                                </td>

                                <td>
                                    <select name="detail[<?= $i ?>][tipe_diskon]" class="form-select tipe-diskon-select">
                                        <option value="nominal" <?= (($row['tipe_diskon'] ?? 'nominal') === 'nominal') ? 'selected' : '' ?>>Nominal</option>
                                        <option value="persen" <?= (($row['tipe_diskon'] ?? '') === 'persen') ? 'selected' : '' ?>>Persen</option>
                                    </select>
                                </td>

                                <td>
                                    <input
                                        type="text"
                                        class="form-control text-end diskon-display"
                                        value="<?= (($row['tipe_diskon'] ?? 'nominal') === 'persen')
                                            ? esc(number_format((float) ($row['diskon'] ?? 0), 2, '.', ','))
                                            : 'Rp ' . esc(number_format((float) ($row['diskon'] ?? 0), 2, '.', ',')) ?>"
                                        autocomplete="off">
                                    <input
                                        type="hidden"
                                        name="detail[<?= $i ?>][diskon]"
                                        class="diskon-input"
                                        value="<?= esc(number_format((float) ($row['diskon'] ?? 0), 2, '.', '')) ?>">
                                </td>

                                <td>
                                    <input
                                        type="text"
                                        class="form-control text-end subtotal-display"
                                        value="Rp <?= esc(number_format((float) ($row['subtotal'] ?? 0), 2, '.', ',')) ?>"
                                        readonly>
                                    <input
                                        type="hidden"
                                        name="detail[<?= $i ?>][subtotal]"
                                        class="subtotal-input"
                                        value="<?= esc(number_format((float) ($row['subtotal'] ?? 0), 2, '.', '')) ?>">
                                </td>

                                <td class="text-center">
                                    <button type="button" class="btn btn-outline-danger btn-sm btn-hapus-baris">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <hr class="my-4">

            <div class="row g-3 justify-content-end">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Subtotal</label>
                    <input
                        type="text"
                        class="form-control text-end"
                        id="subtotal-display"
                        value="Rp <?= esc(number_format((float) ($data_form['subtotal'] ?? 0), 2, '.', ',')) ?>"
                        readonly>
                    <input
                        type="hidden"
                        name="subtotal"
                        id="subtotal-input"
                        value="<?= esc(number_format((float) ($data_form['subtotal'] ?? 0), 2, '.', '')) ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Diskon Header</label>
                    <input
                        type="text"
                        class="form-control text-end"
                        id="diskon-header-display"
                        value="Rp <?= esc(number_format((float) ($data_form['diskon'] ?? 0), 2, '.', ',')) ?>"
                        autocomplete="off">
                    <input
                        type="hidden"
                        name="diskon_header"
                        id="diskon-header-input"
                        value="<?= esc(number_format((float) ($data_form['diskon'] ?? 0), 2, '.', '')) ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Total</label>
                    <input
                        type="text"
                        class="form-control text-end fw-semibold"
                        id="total-display"
                        value="Rp <?= esc(number_format((float) ($data_form['total'] ?? 0), 2, '.', ',')) ?>"
                        readonly>
                    <input
                        type="hidden"
                        name="total"
                        id="total-input"
                        value="<?= esc(number_format((float) ($data_form['total'] ?? 0), 2, '.', '')) ?>">
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-gradient">
                    <i class="bi bi-check-circle me-1"></i><?= esc($button_label ?? 'Simpan') ?>
                </button>

                <a href="<?= esc(admin_page_url('pembelian/pesanan')) ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Kembali
                </a>
            </div>
        </form>
    </div>
</div>

<template id="template-detail-row">
    <tr class="detail-row">
        <td class="text-center nomor-baris">1</td>

        <td>
            <select data-name="id_bahan_baku" class="form-select bahan-select" required>
                <option value="">- Pilih Bahan -</option>
                <?php foreach (($bahan_options ?? []) as $item): ?>
                    <option
                        value="<?= (int) $item->id_bahan_baku ?>"
                        data-harga="<?= esc(number_format((float) $item->harga_standar, 2, '.', '')) ?>"
                        data-satuan="<?= esc($item->nama_satuan ?? '-') ?>">
                        <?= esc($item->kode_bahan_baku . ' - ' . $item->nama_bahan_baku) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>

        <td>
            <input
                type="number"
                data-name="qty"
                class="form-control text-end qty-input"
                min="1"
                step="1"
                value="1"
                required>
        </td>

        <td>
            <input type="text" class="form-control satuan-display" value="" readonly>
        </td>

        <td>
            <input type="text" class="form-control text-end harga-display" value="Rp 0.00" autocomplete="off">
            <input type="hidden" data-name="harga" class="harga-input" value="0.00">
        </td>

        <td>
            <select data-name="tipe_diskon" class="form-select tipe-diskon-select">
                <option value="nominal">Nominal</option>
                <option value="persen">Persen</option>
            </select>
        </td>

        <td>
            <input type="text" class="form-control text-end diskon-display" value="Rp 0.00" autocomplete="off">
            <input type="hidden" data-name="diskon" class="diskon-input" value="0.00">
        </td>

        <td>
            <input type="text" class="form-control text-end subtotal-display" value="Rp 0.00" readonly>
            <input type="hidden" data-name="subtotal" class="subtotal-input" value="0.00">
        </td>

        <td class="text-center">
            <button type="button" class="btn btn-outline-danger btn-sm btn-hapus-baris">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    </tr>
</template>

<script>
function parseMoney(value) {
    return parseFloat(String(value || '0').replace(/Rp/gi, '').replace(/,/g, '').replace(/[^0-9.]/g, '')) || 0;
}

function parseIntQty(value) {
    const cleaned = String(value || '').replace(/[^0-9]/g, '');
    const result = parseInt(cleaned || '0', 10);
    return isNaN(result) ? 0 : result;
}

function formatMoney(value) {
    const num = parseFloat(value || 0);
    return 'Rp ' + num.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function formatPercent(value) {
    const num = parseFloat(value || 0);
    return num.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function reindexRows() {
    document.querySelectorAll('#detail-container .detail-row').forEach(function(row, index) {
        row.querySelector('.nomor-baris').textContent = index + 1;

        row.querySelectorAll('[data-name]').forEach(function(input) {
            input.setAttribute('name', 'detail[' + index + '][' + input.getAttribute('data-name') + ']');
        });

        row.querySelectorAll('select[name^="detail"], input[name^="detail"]').forEach(function(input) {
            const match = input.name.match(/\]\[([^\]]+)\]$/);
            if (match) {
                input.name = 'detail[' + index + '][' + match[1] + ']';
            }
        });
    });
}

function updateSatuanDanHarga(row, forceHarga = true) {
    const select = row.querySelector('.bahan-select');
    const option = select.options[select.selectedIndex];

    if (!option || !option.value) {
        row.querySelector('.satuan-display').value = '';

        if (forceHarga) {
            row.querySelector('.harga-input').value = '0.00';
            row.querySelector('.harga-display').value = formatMoney(0);
        }

        return;
    }

    const satuan = option.getAttribute('data-satuan') || '-';
    const harga = parseFloat(option.getAttribute('data-harga') || '0');

    row.querySelector('.satuan-display').value = satuan;

    if (forceHarga) {
        row.querySelector('.harga-input').value = harga.toFixed(2);
        row.querySelector('.harga-display').value = formatMoney(harga);
    }
}

function hitungRow(row) {
    const qtyInput = row.querySelector('.qty-input');
    const hargaInput = row.querySelector('.harga-input');
    const hargaDisplay = row.querySelector('.harga-display');
    const tipeDiskon = row.querySelector('.tipe-diskon-select').value;
    const diskonInput = row.querySelector('.diskon-input');
    const diskonDisplay = row.querySelector('.diskon-display');

    let qty = parseIntQty(qtyInput.value);
    if (qty < 1) qty = 1;
    qtyInput.value = qty;

    let harga = parseMoney(hargaDisplay.value);
    hargaInput.value = harga.toFixed(2);
    hargaDisplay.value = formatMoney(harga);

    let nilaiDiskonInput = parseMoney(diskonDisplay.value);
    let bruto = qty * harga;
    let diskonNominal = 0;

    if (tipeDiskon === 'persen') {
        if (nilaiDiskonInput > 100) nilaiDiskonInput = 100;
        diskonNominal = bruto * nilaiDiskonInput / 100;
        diskonDisplay.value = formatPercent(nilaiDiskonInput);
    } else {
        diskonNominal = nilaiDiskonInput;
        diskonDisplay.value = formatMoney(nilaiDiskonInput);
    }

    if (diskonNominal > bruto) {
        diskonNominal = bruto;
    }

    const subtotal = bruto - diskonNominal;

    diskonInput.value = diskonNominal.toFixed(2);
    row.querySelector('.subtotal-input').value = subtotal.toFixed(2);
    row.querySelector('.subtotal-display').value = formatMoney(subtotal);

    hitungTotal();
}

function hitungTotal() {
    let subtotal = 0;

    document.querySelectorAll('.subtotal-input').forEach(function(input) {
        subtotal += parseFloat(input.value || '0');
    });

    let diskonHeader = parseMoney(document.getElementById('diskon-header-display').value);

    if (diskonHeader > subtotal) {
        diskonHeader = subtotal;
    }

    const total = subtotal - diskonHeader;

    document.getElementById('subtotal-input').value = subtotal.toFixed(2);
    document.getElementById('subtotal-display').value = formatMoney(subtotal);

    document.getElementById('diskon-header-input').value = diskonHeader.toFixed(2);
    document.getElementById('diskon-header-display').value = formatMoney(diskonHeader);

    document.getElementById('total-input').value = total.toFixed(2);
    document.getElementById('total-display').value = formatMoney(total);
}

function bindRow(row) {
    updateSatuanDanHarga(row, false);

    row.querySelector('.bahan-select').addEventListener('change', function() {
        updateSatuanDanHarga(row, true);
        hitungRow(row);
    });

    row.querySelector('.qty-input').addEventListener('input', function() {
        this.value = parseIntQty(this.value);
        hitungRow(row);
    });

    row.querySelector('.harga-display').addEventListener('blur', function() {
        hitungRow(row);
    });

    row.querySelector('.tipe-diskon-select').addEventListener('change', function() {
        const diskonDisplay = row.querySelector('.diskon-display');
        const diskonInput = row.querySelector('.diskon-input');

        if (this.value === 'persen') {
            diskonDisplay.value = '0.00';
            diskonInput.value = '0.00';
        } else {
            diskonDisplay.value = 'Rp 0.00';
            diskonInput.value = '0.00';
        }

        hitungRow(row);
    });

    row.querySelector('.diskon-display').addEventListener('blur', function() {
        hitungRow(row);
    });

    row.querySelector('.btn-hapus-baris').addEventListener('click', function() {
        const totalRow = document.querySelectorAll('#detail-container .detail-row').length;

        if (totalRow <= 1) {
            alert('Minimal harus ada 1 baris detail.');
            return;
        }

        row.remove();
        reindexRows();
        hitungTotal();
    });

    hitungRow(row);
}

document.getElementById('btn-tambah-baris').addEventListener('click', function() {
    const template = document.getElementById('template-detail-row');
    const clone = template.content.firstElementChild.cloneNode(true);

    document.getElementById('detail-container').appendChild(clone);
    reindexRows();
    bindRow(clone);
});

document.getElementById('diskon-header-display').addEventListener('blur', function() {
    hitungTotal();
});

document.querySelectorAll('#detail-container .detail-row').forEach(function(row) {
    bindRow(row);
});

document.getElementById('form-pesanan-pembelian').addEventListener('submit', function(e) {
    let valid = true;

    document.querySelectorAll('#detail-container .detail-row').forEach(function(row) {
        const bahan = row.querySelector('.bahan-select').value;
        const qty = parseIntQty(row.querySelector('.qty-input').value);

        if (!bahan || qty < 1) {
            valid = false;
        }

        row.querySelector('.qty-input').value = qty;
        hitungRow(row);
    });

    if (!valid) {
        e.preventDefault();
        alert('Pastikan semua bahan dipilih dan qty minimal 1.');
        return false;
    }

    hitungTotal();
});
</script>