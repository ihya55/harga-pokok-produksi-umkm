<?php
$data_form = $data_form ?? [];
$detail_rows = $detail_rows ?? [];
$po_options = $po_options ?? [];

if (!isset($form_action) || $form_action === '') {
    $form_action = $_SERVER['REQUEST_URI'] ?? '';
}

/*
 * Filter PO di form penerimaan:
 * Jika PO sudah dibuatkan penerimaan barang dan penerimaannya sudah posted,
 * maka PO tersebut tidak ditampilkan lagi di dropdown Pesanan Pembelian.
 */
$current_penerimaan_id = (int) ($data_form['id_penerimaan_pembelian'] ?? 0);
$posted_po_ids = [];

if (class_exists('PenerimaanPembelianORM')) {
    $posted_query = PenerimaanPembelianORM::query()
        ->where('status_penerimaan', 'posted');

    if (!empty($id_entitas)) {
        $posted_query->where('id_entitas', (int) $id_entitas);
    }

    if ($current_penerimaan_id > 0) {
        $posted_query->where('id_penerimaan_pembelian', '!=', $current_penerimaan_id);
    }

    foreach ($posted_query->get() as $posted_row) {
        $posted_po_id = (int) ($posted_row->id_pesanan_pembelian ?? 0);
        if ($posted_po_id > 0) {
            $posted_po_ids[$posted_po_id] = true;
        }
    }
}

$po_options_filtered = [];
foreach ($po_options as $po) {
    $po_id = (int) ($po->id_pesanan_pembelian ?? 0);
    $has_posted_penerimaan = !empty($posted_po_ids[$po_id])
        || (isset($po->jumlah_penerimaan_posted) && (int) $po->jumlah_penerimaan_posted > 0)
        || (isset($po->ada_penerimaan_posted) && (int) $po->ada_penerimaan_posted === 1)
        || (isset($po->status_penerimaan) && (string) $po->status_penerimaan === 'posted');

    if ($has_posted_penerimaan) {
        continue;
    }

    $po_options_filtered[] = $po;
}

if (count($detail_rows) === 0) {
    $detail_rows[] = [
        'id_bahan_baku' => '',
        'qty'           => '1',
        'harga'         => '0.00',
        'subtotal'      => '0.00',
    ];
}
?>

<div class="page-header mb-4">
    <h1 class="page-title"><?= esc($page_title ?? 'Form Penerimaan Pembelian') ?></h1>
    <p class="page-subtitle"><?= esc($page_subtitle ?? '') ?></p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= esc($form_action) ?>" id="form-penerimaan-pembelian">
            <?php if (!empty($data_form['id_penerimaan_pembelian'])): ?>
                <input type="hidden" name="id_penerimaan_pembelian" value="<?= (int) $data_form['id_penerimaan_pembelian'] ?>">
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">No Penerimaan</label>
                    <input
                        type="text"
                        class="form-control"
                        value="<?= esc($data_form['no_penerimaan_pembelian'] ?? 'Otomatis saat disimpan') ?>"
                        readonly>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Tanggal Penerimaan <span class="text-danger">*</span></label>
                    <input
                        type="date"
                        name="tanggal_penerimaan"
                        class="form-control"
                        required
                        value="<?= esc($data_form['tanggal_penerimaan'] ?? date('Y-m-d')) ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Status</label>
                    <input
                        type="text"
                        class="form-control"
                        value="<?= esc(ucfirst((string) ($data_form['status_penerimaan'] ?? 'draft'))) ?>"
                        readonly>
                    <div class="form-text">Status awal draft. Stok masuk hanya saat posting.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Pesanan Pembelian</label>
                    <select name="id_pesanan_pembelian" class="form-select" id="id-pesanan-pembelian">
                        <option value="">- Tanpa PO / Manual -</option>
                        <?php foreach ($po_options_filtered as $item): ?>
                            <option
                                value="<?= (int) $item->id_pesanan_pembelian ?>"
                                data-pemasok="<?= (int) $item->id_pemasok ?>"
                                <?= ((string) ($data_form['id_pesanan_pembelian'] ?? '') === (string) $item->id_pesanan_pembelian) ? 'selected' : '' ?>>
                                <?= esc($item->no_pesanan_pembelian . ' - ' . $item->kode_pemasok . ' - ' . $item->nama_pemasok) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">
                        Saat tambah, pilih PO untuk memuat detail otomatis.
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Pemasok <span class="text-danger">*</span></label>
                    <select name="id_pemasok" class="form-select" id="id-pemasok" required>
                        <option value="">- Pilih Pemasok -</option>
                        <?php foreach (($pemasok_options ?? []) as $item): ?>
                            <option
                                value="<?= (int) $item->id_pemasok ?>"
                                <?= ((string) ($data_form['id_pemasok'] ?? '') === (string) $item->id_pemasok) ? 'selected' : '' ?>>
                                <?= esc($item->kode_pemasok . ' - ' . $item->nama_pemasok) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Jika manual tanpa PO, pilih pemasok dari master pemasok aktif.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Gudang <span class="text-danger">*</span></label>
                    <select name="id_gudang" class="form-select" required>
                        <option value="">- Pilih Gudang -</option>
                        <?php foreach (($gudang_options ?? []) as $item): ?>
                            <option
                                value="<?= (int) $item->id_gudang ?>"
                                <?= ((string) ($data_form['id_gudang'] ?? '') === (string) $item->id_gudang) ? 'selected' : '' ?>>
                                <?= esc($item->kode_gudang . ' - ' . $item->nama_gudang) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Catatan</label>
                    <textarea name="catatan" class="form-control" rows="2"><?= esc($data_form['catatan'] ?? '') ?></textarea>
                </div>
            </div>

            <hr class="my-4">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="h5 mb-1">Detail Bahan Diterima</h2>
                    <div class="text-muted small">
                        Qty diterima memakai bilangan bulat / INT. Tidak ada diskon di tabel penerimaan detail.
                    </div>
                </div>

                <button type="button" class="btn btn-outline-primary btn-sm" id="btn-tambah-baris">
                    <i class="bi bi-plus-circle me-1"></i>Tambah Baris
                </button>
            </div>

            <div class="table-responsive border rounded">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="50" class="text-center">No</th>
                            <th style="min-width: 280px;">Bahan Baku</th>
                            <th style="min-width: 100px;" class="text-end">Qty</th>
                            <th style="min-width: 120px;">Satuan</th>
                            <th style="min-width: 160px;" class="text-end">Harga</th>
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
                                    <input type="text" class="form-control satuan-display" value="" readonly>
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

            <div class="row g-3 justify-content-end mt-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Total Nilai Diterima</label>
                    <input
                        type="text"
                        class="form-control text-end fw-semibold"
                        id="total-display"
                        value="Rp 0.00"
                        readonly>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-gradient">
                    <i class="bi bi-check-circle me-1"></i><?= esc($button_label ?? 'Simpan') ?>
                </button>

                <a href="<?= esc(admin_page_url('pembelian/penerimaan')) ?>" class="btn btn-outline-secondary">
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
            <input type="number" data-name="qty" class="form-control text-end qty-input" min="1" step="1" value="1" required>
        </td>

        <td>
            <input type="text" class="form-control satuan-display" value="" readonly>
        </td>

        <td>
            <input type="text" class="form-control text-end harga-display" value="Rp 0.00" autocomplete="off">
            <input type="hidden" data-name="harga" class="harga-input" value="0.00">
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

    let qty = parseIntQty(qtyInput.value);
    if (qty < 1) qty = 1;
    qtyInput.value = qty;

    let harga = parseMoney(hargaDisplay.value);
    hargaInput.value = harga.toFixed(2);
    hargaDisplay.value = formatMoney(harga);

    const subtotal = qty * harga;

    row.querySelector('.subtotal-input').value = subtotal.toFixed(2);
    row.querySelector('.subtotal-display').value = formatMoney(subtotal);

    hitungTotal();
}

function hitungTotal() {
    let total = 0;

    document.querySelectorAll('.subtotal-input').forEach(function(input) {
        total += parseFloat(input.value || '0');
    });

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

document.getElementById('id-pesanan-pembelian')?.addEventListener('change', function() {
    const idPo = this.value;

    if (idPo) {
        window.location.href = '<?= esc(admin_page_url('pembelian/penerimaan/tambah')) ?>' + '&id_pesanan_pembelian=' + encodeURIComponent(idPo);
    }
});

document.querySelectorAll('#detail-container .detail-row').forEach(function(row) {
    bindRow(row);
});

document.getElementById('form-penerimaan-pembelian').addEventListener('submit', function(e) {
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