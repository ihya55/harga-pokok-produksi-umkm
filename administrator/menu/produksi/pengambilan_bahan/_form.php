<?php
$perintah_options = $perintah_options ?? [];
$gudang_options = $gudang_options ?? [];
$bahan_options = $bahan_options ?? [];
$detail_rows = $detail_rows ?? [];

$back_url = (string) ($data_form['back_url'] ?? admin_page_url('produksi/pengambilan-bahan'));

$bahan_js = [];
foreach ($bahan_options as $b) {
    $bahan_js[] = [
        'id_bahan_baku' => (int) $b->id_bahan_baku,
        'id_satuan'     => (int) ($b->id_satuan ?? 0),
        'kode'          => (string) ($b->kode_bahan_baku ?? ''),
        'nama'          => (string) ($b->nama_bahan_baku ?? ''),
        'satuan'        => (string) ($b->nama_satuan ?? ''),
        'harga_satuan'  => (float) ($b->harga_standar ?? 0),
        'label'         => (string) (($b->kode_bahan_baku ?? '-') . ' - ' . ($b->nama_bahan_baku ?? '-') . ' - ' . ($b->nama_satuan ?? '-')),
    ];
}
?>

<div class="page-header mb-4">
    <h1 class="page-title"><?= esc($page_title ?? 'Form Pengambilan Bahan') ?></h1>
    <p class="page-subtitle"><?= esc($page_subtitle ?? '') ?></p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= esc($form_action) ?>" id="form-pengambilan-bahan">
            <input type="hidden" name="back_url" value="<?= esc($back_url) ?>">

            <?php if (!empty($data_form['id_pengambilan_bahan'])): ?>
                <input type="hidden" name="id_pengambilan_bahan" value="<?= (int) $data_form['id_pengambilan_bahan'] ?>">
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">No Bon</label>
                    <input type="text" class="form-control" value="<?= esc($data_form['no_pengambilan_bahan'] ?? 'Otomatis saat disimpan') ?>" readonly>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Tanggal Pengambilan <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal_pengambilan" class="form-control" required value="<?= esc($data_form['tanggal_pengambilan'] ?? date('Y-m-d')) ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Status</label>
                    <input type="text" class="form-control" value="<?= esc(ucfirst((string) ($data_form['status_posting'] ?? 'draft'))) ?>" readonly>
                    <div class="form-text">Jika data diperiksa lalu diedit, status akan kembali menjadi draft.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Perintah Produksi <span class="text-danger">*</span></label>
                    <select name="id_perintah_produksi" id="id-perintah-produksi" class="form-select" required>
                        <option value="">- Pilih Perintah Produksi Posted -</option>
                        <?php foreach ($perintah_options as $p): ?>
                            <option value="<?= (int) $p->id_perintah_produksi ?>" <?= ((string) ($data_form['id_perintah_produksi'] ?? '') === (string) $p->id_perintah_produksi) ? 'selected' : '' ?>>
                                <?= esc(
                                    ($p->no_perintah_produksi ?? '-') .
                                    ' - ' .
                                    ($p->kode_produk ?? '-') .
                                    ' - ' .
                                    ($p->nama_produk ?? '-') .
                                    ' - Qty ' .
                                    number_format((float) ($p->qty_rencana ?? 0), 2, '.', ',') .
                                    ' - Resep ' .
                                    ($p->kode_resep ?? '-')
                                ) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Hanya perintah produksi status posted yang bisa digunakan.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Gudang Pengeluaran <span class="text-danger">*</span></label>
                    <select name="id_gudang" id="id-gudang" class="form-select" required>
                        <option value="">- Pilih Gudang -</option>
                        <?php foreach ($gudang_options as $g): ?>
                            <option value="<?= (int) $g->id_gudang ?>" <?= ((string) ($data_form['id_gudang'] ?? '') === (string) $g->id_gudang) ? 'selected' : '' ?>>
                                <?= esc(($g->kode_gudang ?? '-') . ' - ' . ($g->nama_gudang ?? '-')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Stok akan dicek dan dikeluarkan dari gudang ini.</div>
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Catatan</label>
                    <textarea name="catatan" class="form-control" rows="2"><?= esc($data_form['catatan'] ?? '') ?></textarea>
                </div>
            </div>

            <hr class="my-4">

            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <h2 class="h5 mb-1">Detail Bahan Baku</h2>
                    <div class="text-muted small">Satuan diambil otomatis dari master bahan baku. Database detail hanya menyimpan bahan, qty, harga satuan, dan subtotal.</div>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <button type="button" class="btn btn-outline-warning btn-sm" onclick="loadDetailResep()">
                        <i class="bi bi-arrow-repeat me-1"></i>Load dari Resep
                    </button>

                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="tambahBarisBahan()">
                        <i class="bi bi-plus-circle me-1"></i>Tambah Baris
                    </button>
                </div>
            </div>

            <div class="table-responsive border rounded">
                <table class="table align-middle mb-0" id="table-detail-bahan">
                    <thead class="table-light">
                        <tr>
                            <th width="55" class="text-center">No</th>
                            <th>Bahan Baku</th>
                            <th width="170" class="text-end">Qty</th>
                            <th width="130">Satuan</th>
                            <th width="200" class="text-end">Harga Satuan</th>
                            <th width="200" class="text-end">Subtotal</th>
                            <th width="80" class="text-center">Aksi</th>
                        </tr>
                    </thead>

                    <tbody id="detail-body">
                        <?php foreach ($detail_rows as $i => $d): ?>
                            <tr class="detail-row">
                                <td class="text-center row-no"><?= $i + 1 ?></td>

                                <td>
                                    <select name="id_bahan_baku[]" class="form-select detail-bahan" required onchange="refreshBahanRow(this)">
                                        <option value="">- Pilih Bahan Baku -</option>
                                        <?php foreach ($bahan_options as $b): ?>
                                            <option
                                                value="<?= (int) $b->id_bahan_baku ?>"
                                                data-nama-satuan="<?= esc($b->nama_satuan ?? '') ?>"
                                                data-harga-satuan="<?= esc(number_format((float) ($b->harga_standar ?? 0), 2, '.', '')) ?>"
                                                <?= ((string) ($d['id_bahan_baku'] ?? '') === (string) $b->id_bahan_baku) ? 'selected' : '' ?>>
                                                <?= esc(($b->kode_bahan_baku ?? '-') . ' - ' . ($b->nama_bahan_baku ?? '-') . ' - ' . ($b->nama_satuan ?? '-')) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>

                                <td>
                                    <input
                                        type="text"
                                        name="qty[]"
                                        class="form-control text-end detail-qty"
                                        value="<?= esc(number_format((float) ($d['qty'] ?? 0), 3, '.', ',')) ?>"
                                        onblur="refreshSubtotalRow(this)"
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
                                            class="form-control text-end detail-harga-satuan-display"
                                            value="<?= esc(number_format((float) ($d['harga_satuan'] ?? 0), 2, '.', ',')) ?>"
                                            onblur="refreshSubtotalRow(this)"
                                            required>
                                    </div>
                                    <input type="hidden" name="harga_satuan[]" class="detail-harga-satuan" value="<?= esc(number_format((float) ($d['harga_satuan'] ?? 0), 2, '.', '')) ?>">
                                </td>

                                <td>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="text" class="form-control text-end detail-subtotal-display" value="<?= esc(number_format((float) ($d['subtotal'] ?? 0), 2, '.', ',')) ?>" readonly>
                                    </div>
                                    <input type="hidden" name="subtotal[]" class="detail-subtotal" value="<?= esc(number_format((float) ($d['subtotal'] ?? 0), 2, '.', '')) ?>">
                                </td>

                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="hapusBarisBahan(this)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>

                    <tfoot class="table-light">
                        <tr>
                            <th colspan="5" class="text-end">Total Nilai Bahan</th>
                            <th class="text-end" id="total-subtotal-display">Rp 0.00</th>
                            <th></th>
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
const bahanOptions = <?= json_encode($bahan_js, JSON_UNESCAPED_UNICODE) ?>;

function parseNumber(value) {
    return parseFloat(String(value || '0').replace(/,/g, '').replace(/[^0-9.]/g, '')) || 0;
}

function parseQty(value) {
    return parseInt(String(value || '0').replace(/,/g, '').replace(/[^0-9]/g, ''), 10) || 0;
}

function formatQty(value) {
    const num = parseInt(value || 0, 10) || 0;
    return num.toLocaleString('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    });
}

function formatNumber(value, decimal = 3) {
    const num = parseFloat(value || 0);
    return num.toLocaleString('en-US', {
        minimumFractionDigits: decimal,
        maximumFractionDigits: decimal
    });
}

function formatMoney(value) {
    const num = parseFloat(value || 0);
    return num.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function escapeHtml(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function optionBahanHtml(selectedId = '') {
    let html = '<option value="">- Pilih Bahan Baku -</option>';

    bahanOptions.forEach(function(item) {
        const selected = String(selectedId) === String(item.id_bahan_baku) ? 'selected' : '';
        html += '<option value="' + item.id_bahan_baku + '" data-nama-satuan="' + escapeHtml(item.satuan) + '" data-harga-satuan="' + item.harga_satuan + '" ' + selected + '>' + escapeHtml(item.label) + '</option>';
    });

    return html;
}

function refreshBahanRow(select) {
    const row = select.closest('tr');
    const selected = select.options[select.selectedIndex];

    const namaSatuan = selected ? selected.getAttribute('data-nama-satuan') : '';
    const hargaSatuan = selected ? parseFloat(selected.getAttribute('data-harga-satuan') || '0') : 0;

    row.querySelector('.detail-satuan').value = namaSatuan || '';

    const hargaDisplay = row.querySelector('.detail-harga-satuan-display');
    const hargaHidden = row.querySelector('.detail-harga-satuan');

    if (hargaSatuan > 0 && parseNumber(hargaDisplay.value) <= 0) {
        hargaDisplay.value = formatMoney(hargaSatuan);
        hargaHidden.value = hargaSatuan.toFixed(2);
    }

    refreshSubtotalRow(select);
}

function refreshSubtotalRow(el) {
    const row = el.closest('tr');

    const qtyInput = row.querySelector('.detail-qty');
    const hargaDisplay = row.querySelector('.detail-harga-satuan-display');
    const hargaHidden = row.querySelector('.detail-harga-satuan');
    const subtotalDisplay = row.querySelector('.detail-subtotal-display');
    const subtotalHidden = row.querySelector('.detail-subtotal');

    const qty = parseQty(qtyInput.value);
    const hargaSatuan = parseNumber(hargaDisplay.value);
    const subtotal = qty * hargaSatuan;

    qtyInput.value = formatQty(qty);
    hargaDisplay.value = formatMoney(hargaSatuan);
    hargaHidden.value = hargaSatuan.toFixed(2);

    subtotalDisplay.value = formatMoney(subtotal);
    subtotalHidden.value = subtotal.toFixed(2);

    refreshNomorBaris();
    refreshTotal();
}

function tambahBarisBahan() {
    const tbody = document.getElementById('detail-body');

    const tr = document.createElement('tr');
    tr.className = 'detail-row';

    tr.innerHTML = `
        <td class="text-center row-no"></td>
        <td>
            <select name="id_bahan_baku[]" class="form-select detail-bahan" required onchange="refreshBahanRow(this)">
                ${optionBahanHtml('')}
            </select>
        </td>
        <td>
            <input type="text" name="qty[]" class="form-control text-end detail-qty" value="0" onblur="refreshSubtotalRow(this)" required>
        </td>
        <td>
            <input type="text" class="form-control detail-satuan" value="" readonly>
        </td>
        <td>
            <div class="input-group">
                <span class="input-group-text">Rp</span>
                <input type="text" name="harga_satuan_display[]" class="form-control text-end detail-harga-satuan-display" value="0.00" onblur="refreshSubtotalRow(this)" required>
            </div>
            <input type="hidden" name="harga_satuan[]" class="detail-harga-satuan" value="0.00">
        </td>
        <td>
            <div class="input-group">
                <span class="input-group-text">Rp</span>
                <input type="text" class="form-control text-end detail-subtotal-display" value="0.00" readonly>
            </div>
            <input type="hidden" name="subtotal[]" class="detail-subtotal" value="0.00">
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="hapusBarisBahan(this)">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;

    tbody.appendChild(tr);
    refreshNomorBaris();
    refreshTotal();
}

function hapusBarisBahan(button) {
    const rows = document.querySelectorAll('.detail-row');

    if (rows.length <= 1) {
        alert('Minimal harus ada 1 baris bahan.');
        return;
    }

    button.closest('tr').remove();
    refreshNomorBaris();
    refreshTotal();
}

function refreshNomorBaris() {
    document.querySelectorAll('.detail-row').forEach(function(row, index) {
        row.querySelector('.row-no').textContent = index + 1;
    });
}

function refreshTotal() {
    let total = 0;

    document.querySelectorAll('.detail-row').forEach(function(row) {
        total += parseNumber(row.querySelector('.detail-subtotal').value);
    });

    document.getElementById('total-subtotal-display').textContent = 'Rp ' + formatMoney(total);
}

function loadDetailResep() {
    const idPerintah = document.getElementById('id-perintah-produksi').value;

    if (!idPerintah) {
        alert('Pilih perintah produksi terlebih dahulu.');
        return;
    }

    if (!confirm('Load ulang detail bahan dari resep/BOM? Detail saat ini akan diganti.')) {
        return;
    }

    fetch('<?= esc(admin_url('index.php?menu=produksi/pengambilan-bahan/load-detail-resep')) ?>&id_perintah_produksi=' + encodeURIComponent(idPerintah) + '&id_gudang=' + encodeURIComponent(document.getElementById('id-gudang').value || ''))
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                alert(data.message || 'Gagal load detail resep.');
                return;
            }

            const tbody = document.getElementById('detail-body');
            tbody.innerHTML = '';

            data.rows.forEach(function(item) {
                tambahBarisBahan();

                const row = tbody.querySelector('tr:last-child');
                const bahanSelect = row.querySelector('.detail-bahan');

                bahanSelect.value = item.id_bahan_baku;
                refreshBahanRow(bahanSelect);

                row.querySelector('.detail-qty').value = formatQty(item.qty);
                row.querySelector('.detail-harga-satuan-display').value = formatMoney(item.harga_satuan);
                row.querySelector('.detail-harga-satuan').value = parseFloat(item.harga_satuan || 0).toFixed(2);

                refreshSubtotalRow(row.querySelector('.detail-qty'));
            });

            refreshNomorBaris();
            refreshTotal();
        })
        .catch(() => {
            alert('Gagal menghubungi server.');
        });
}

document.getElementById('form-pengambilan-bahan').addEventListener('submit', function(e) {
    const idPerintah = document.getElementById('id-perintah-produksi').value;
    const idGudang = document.getElementById('id-gudang').value;
    const rows = document.querySelectorAll('.detail-row');

    if (!idPerintah || !idGudang) {
        e.preventDefault();
        alert('Perintah produksi dan gudang wajib dipilih.');
        return false;
    }

    let valid = true;

    rows.forEach(function(row) {
        const bahan = row.querySelector('.detail-bahan').value;
        const qty = parseNumber(row.querySelector('.detail-qty').value);
        const hargaSatuan = parseNumber(row.querySelector('.detail-harga-satuan-display').value);

        if (!bahan || qty <= 0 || hargaSatuan < 0) {
            valid = false;
        }
    });

    if (!valid) {
        e.preventDefault();
        alert('Semua detail bahan wajib valid. Qty harus lebih besar dari 0.');
        return false;
    }
});

document.querySelectorAll('.detail-row').forEach(function(row) {
    refreshBahanRow(row.querySelector('.detail-bahan'));
    refreshSubtotalRow(row.querySelector('.detail-qty'));
});

refreshNomorBaris();
refreshTotal();
</script>