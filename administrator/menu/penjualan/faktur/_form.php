<?php
$penyerahan_options = $penyerahan_options ?? [];
$detail_rows = $detail_rows ?? [];

$penyerahan_js = [];

foreach ($penyerahan_options as $p) {
    $penyerahan_js[] = [
        'id_penyerahan_penjualan' => (int) $p->id_penyerahan_penjualan,
        'id_pelanggan' => (int) $p->id_pelanggan,
        'pelanggan_label' => (string) (($p->kode_pelanggan ?? '-') . ' - ' . ($p->nama_pelanggan ?? '-')),
    ];
}

if (!function_exists('faktur_form_uang_input')) {
    function faktur_form_uang_input($value): string
    {
        return number_format((float) $value, 2, '.', '');
    }
}
?>

<div class="page-header mb-4">
    <h1 class="page-title"><?= esc($page_title ?? 'Form Piutang Pelanggan') ?></h1>
    <p class="page-subtitle"><?= esc($page_subtitle ?? '') ?></p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= esc($form_action) ?>" id="form-faktur-penjualan">
            <?php if (!empty($data_form['id_faktur_penjualan'])): ?>
                <input type="hidden" name="id_faktur_penjualan" value="<?= (int) $data_form['id_faktur_penjualan'] ?>">
            <?php endif; ?>

            <input type="hidden" name="id_pelanggan" id="id-pelanggan" value="<?= esc($data_form['id_pelanggan'] ?? '') ?>">
            <input type="hidden" name="subtotal" id="subtotal-input" value="<?= esc(faktur_form_uang_input($data_form['subtotal'] ?? 0)) ?>">
            <input type="hidden" name="diskon" id="diskon-input" value="<?= esc(faktur_form_uang_input($data_form['diskon'] ?? 0)) ?>">
            <input type="hidden" name="ppn" id="ppn-input" value="<?= esc(faktur_form_uang_input($data_form['ppn'] ?? 0)) ?>">
            <input type="hidden" name="total" id="total-input" value="<?= esc(faktur_form_uang_input($data_form['total'] ?? 0)) ?>">

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">No Piutang</label>
                    <input type="text" class="form-control" value="<?= esc($data_form['no_faktur_penjualan'] ?? 'Otomatis saat disimpan') ?>" readonly>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Tanggal Piutang <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal_faktur" class="form-control" required value="<?= esc($data_form['tanggal_faktur'] ?? date('Y-m-d')) ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Status</label>
                    <input type="text" class="form-control" value="<?= esc(ucfirst((string) ($data_form['status_faktur'] ?? 'draft'))) ?>" readonly>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Penyerahan Penjualan <span class="text-danger">*</span></label>
                    <select name="id_penyerahan_penjualan" id="id-penyerahan-penjualan" class="form-select" required>
                        <option value="">- Pilih Penyerahan Posted -</option>
                        <?php foreach ($penyerahan_options as $p): ?>
                            <option
                                value="<?= (int) $p->id_penyerahan_penjualan ?>"
                                data-id-pelanggan="<?= (int) $p->id_pelanggan ?>"
                                data-pelanggan="<?= esc(($p->kode_pelanggan ?? '-') . ' - ' . ($p->nama_pelanggan ?? '-')) ?>"
                                <?= ((string) ($data_form['id_penyerahan_penjualan'] ?? '') === (string) $p->id_penyerahan_penjualan) ? 'selected' : '' ?>>
                                <?= esc(($p->no_penyerahan_penjualan ?? '-') . ' - ' . ($p->no_pesanan_penjualan ?? '-') . ' - ' . ($p->nama_pelanggan ?? '-')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Faktur wajib berasal dari penyerahan penjualan yang sudah posted.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Pelanggan</label>
                    <input type="text" class="form-control" id="pelanggan-display" value="<?= esc($data_form['pelanggan_label'] ?? '') ?>" readonly>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Jenis Pembayaran <span class="text-danger">*</span></label>
                    <select name="jenis_pembayaran" id="jenis-pembayaran" class="form-select" required onchange="aturJatuhTempo()">
                        <option value="tunai" <?= ($data_form['jenis_pembayaran'] ?? '') === 'tunai' ? 'selected' : '' ?>>Tunai</option>
                        <option value="transfer" <?= ($data_form['jenis_pembayaran'] ?? '') === 'transfer' ? 'selected' : '' ?>>Transfer</option>
                        <option value="kredit" <?= ($data_form['jenis_pembayaran'] ?? 'kredit') === 'kredit' ? 'selected' : '' ?>>Kredit</option>
                    </select>
                    <div class="form-text">Posting akan memilih template jurnal sesuai jenis pembayaran.</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Jatuh Tempo</label>
                    <input type="date" name="jatuh_tempo" id="jatuh-tempo" class="form-control" value="<?= esc($data_form['jatuh_tempo'] ?? '') ?>">
                    <div class="form-text">Wajib untuk piutang kredit.</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Catatan</label>
                    <textarea name="catatan" class="form-control" rows="2" placeholder="Catatan faktur..."><?= esc($data_form['catatan'] ?? '') ?></textarea>
                </div>
            </div>

            <hr class="my-4">

            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <h2 class="h5 mb-1">Detail Faktur</h2>
                    <div class="text-muted small">
                        Detail diambil dari penyerahan. Harga dan diskon awal mengikuti pesanan penjualan jika tersedia.
                    </div>
                </div>

                <button type="button" class="btn btn-outline-primary btn-sm" onclick="muatDetailPenyerahan()">
                    <i class="bi bi-arrow-repeat me-1"></i>Muat Detail Penyerahan
                </button>
            </div>

            <div class="table-responsive border rounded">
                <table class="table align-middle mb-0" id="table-detail">
                    <thead class="table-light">
                        <tr>
                            <th width="55" class="text-center">No</th>
                            <th>Produk</th>
                            <th width="100">Satuan</th>
                            <th width="110" class="text-end">Qty</th>
                            <th width="160" class="text-end">Harga</th>
                            <th width="150" class="text-end">Diskon</th>
                            <th width="170" class="text-end">Subtotal</th>
                        </tr>
                    </thead>

                    <tbody id="detail-body">
                        <?php foreach ($detail_rows as $i => $d): ?>
                            <tr class="detail-row">
                                <td class="text-center row-no"><?= $i + 1 ?></td>

                                <td>
                                    <input type="hidden" name="detail[<?= $i ?>][id_produk]" value="<?= (int) ($d['id_produk'] ?? 0) ?>">
                                    <div class="fw-semibold"><?= esc((string) ($d['nama_produk'] ?? '-')) ?></div>
                                    <div class="text-muted small"><?= esc((string) ($d['kode_produk'] ?? '-')) ?></div>
                                </td>

                                <td><?= esc((string) ($d['nama_satuan'] ?? '-')) ?></td>

                                <td>
                                    <input
                                        type="number"
                                        name="detail[<?= $i ?>][qty]"
                                        class="form-control text-end detail-qty"
                                        min="1"
                                        step="1"
                                        value="<?= esc((string) (int) ($d['qty'] ?? 1)) ?>"
                                        readonly>
                                </td>

                                <td>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input
                                            type="number"
                                            name="detail[<?= $i ?>][harga]"
                                            class="form-control text-end detail-harga"
                                            min="0"
                                            step="0.01"
                                            value="<?= esc(faktur_form_uang_input($d['harga'] ?? 0)) ?>"
                                            oninput="refreshSubtotalFaktur(this)"
                                            required>
                                    </div>
                                </td>

                                <td>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input
                                            type="number"
                                            name="detail[<?= $i ?>][diskon]"
                                            class="form-control text-end detail-diskon"
                                            min="0"
                                            step="0.01"
                                            value="<?= esc(faktur_form_uang_input($d['diskon'] ?? 0)) ?>"
                                            oninput="refreshSubtotalFaktur(this)">
                                    </div>
                                </td>

                                <td>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="text" class="form-control text-end detail-subtotal-display" value="<?= esc(number_format((float) ($d['subtotal'] ?? 0), 2, '.', ',')) ?>" readonly>
                                    </div>
                                    <input type="hidden" name="detail[<?= $i ?>][subtotal]" class="detail-subtotal" value="<?= esc(faktur_form_uang_input($d['subtotal'] ?? 0)) ?>">
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (count($detail_rows) === 0): ?>
                            <tr class="empty-row">
                                <td colspan="7" class="text-center text-muted py-4">
                                    Pilih Penyerahan Penjualan, lalu klik <strong>Muat Detail Penyerahan</strong>.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>

                    <tfoot class="table-light">
                        <tr>
                            <th colspan="3" class="text-end">Subtotal</th>
                            <th class="text-end" id="total-qty-display">0</th>
                            <th colspan="2"></th>
                            <th class="text-end" id="subtotal-display">Rp 0.00</th>
                        </tr>

                        <tr>
                            <th colspan="6" class="text-end">
                                Diskon Header
                                <div class="d-inline-flex align-items-center ms-2" style="width:120px;">
                                    <input type="number" name="diskon_persen_header" id="diskon-persen-header" class="form-control form-control-sm text-end" min="0" max="100" step="0.01" value="<?= esc((string) ($data_form['diskon_persen'] ?? 0)) ?>" oninput="refreshTotalFaktur()">
                                    <span class="ms-1">%</span>
                                </div>
                            </th>
                            <th class="text-end" id="diskon-display">Rp 0.00</th>
                        </tr>

                        <tr>
                            <th colspan="6" class="text-end">
                                PPN
                                <div class="d-inline-flex align-items-center ms-2" style="width:120px;">
                                    <input type="number" name="ppn_persen_header" id="ppn-persen-header" class="form-control form-control-sm text-end" min="0" max="100" step="0.01" value="<?= esc((string) ($data_form['ppn_persen'] ?? pajak_default_persen((int) ($user['id_entitas'] ?? 0), 'PPN', 11.0))) ?>" oninput="refreshTotalFaktur()">
                                    <span class="ms-1">%</span>
                                </div>
                            </th>
                            <th class="text-end" id="ppn-display">Rp 0.00</th>
                        </tr>

                        <tr>
                            <th colspan="6" class="text-end fs-5">Total</th>
                            <th class="text-end fs-5" id="total-display">Rp 0.00</th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="alert alert-info mt-3 mb-0">
                <i class="bi bi-info-circle me-1"></i>
                Untuk piutang kredit, saat posting nilai <strong>sisa_piutang</strong> akan diisi sebesar total faktur.
                Untuk tunai/transfer, sisa piutang akan menjadi 0.
            </div>

            <div class="d-flex justify-content-between align-items-center gap-2 mt-4">
                <a href="<?= esc(admin_page_url('penjualan/faktur')) ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check2-circle me-1"></i> Simpan Draft
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const dataPenyerahan = <?= json_encode($penyerahan_js, JSON_UNESCAPED_UNICODE) ?>;

function angkaFaktur(value) {
    value = String(value ?? '0').replace(/,/g, '');
    const n = Number(value);
    return Number.isFinite(n) ? n : 0;
}

function formatUangFaktur(value) {
    return 'Rp ' + angkaFaktur(value).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function formatAngkaUangFaktur(value) {
    return angkaFaktur(value).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function refreshNomorFaktur() {
    document.querySelectorAll('#detail-body .detail-row').forEach((row, index) => {
        row.querySelector('.row-no').textContent = index + 1;

        row.querySelectorAll('input').forEach((input) => {
            if (!input.name) return;
            input.name = input.name.replace(/detail\[\d+\]/, 'detail[' + index + ']');
        });
    });
}

function tambahBarisFaktur(row = {}) {
    const tbody = document.getElementById('detail-body');
    tbody.querySelector('.empty-row')?.remove();

    const index = tbody.querySelectorAll('.detail-row').length;

    const idProduk = row.id_produk || '';
    const kodeProduk = row.kode_produk || '-';
    const namaProduk = row.nama_produk || '-';
    const namaSatuan = row.nama_satuan || '-';
    const qty = parseInt(row.qty || 1, 10);
    const harga = angkaFaktur(row.harga || 0);
    const diskon = angkaFaktur(row.diskon || 0);
    const subtotal = Math.max(0, (qty * harga) - diskon);

    const tr = document.createElement('tr');
    tr.className = 'detail-row';

    tr.innerHTML = `
        <td class="text-center row-no">${index + 1}</td>

        <td>
            <input type="hidden" name="detail[${index}][id_produk]" value="${idProduk}">
            <div class="fw-semibold">${namaProduk}</div>
            <div class="text-muted small">${kodeProduk}</div>
        </td>

        <td>${namaSatuan}</td>

        <td>
            <input type="number" name="detail[${index}][qty]" class="form-control text-end detail-qty" min="1" step="1" value="${qty}" readonly>
        </td>

        <td>
            <div class="input-group">
                <span class="input-group-text">Rp</span>
                <input type="number" name="detail[${index}][harga]" class="form-control text-end detail-harga" min="0" step="0.01" value="${harga.toFixed(2)}" oninput="refreshSubtotalFaktur(this)" required>
            </div>
        </td>

        <td>
            <div class="input-group">
                <span class="input-group-text">Rp</span>
                <input type="number" name="detail[${index}][diskon]" class="form-control text-end detail-diskon" min="0" step="0.01" value="${diskon.toFixed(2)}" oninput="refreshSubtotalFaktur(this)">
            </div>
        </td>

        <td>
            <div class="input-group">
                <span class="input-group-text">Rp</span>
                <input type="text" class="form-control text-end detail-subtotal-display" value="${formatAngkaUangFaktur(subtotal)}" readonly>
            </div>
            <input type="hidden" name="detail[${index}][subtotal]" class="detail-subtotal" value="${subtotal.toFixed(2)}">
        </td>
    `;

    tbody.appendChild(tr);
    refreshSubtotalFaktur(tr.querySelector('.detail-harga'));
}

function refreshSubtotalFaktur(element) {
    const tr = element.closest('tr');

    const qty = parseInt(tr.querySelector('.detail-qty')?.value || '0', 10) || 0;
    const harga = angkaFaktur(tr.querySelector('.detail-harga')?.value || 0);
    const diskon = angkaFaktur(tr.querySelector('.detail-diskon')?.value || 0);

    const bruto = qty * harga;
    const diskonValid = Math.min(Math.max(0, diskon), bruto);
    const subtotal = Math.max(0, bruto - diskonValid);

    tr.querySelector('.detail-diskon').value = diskonValid.toFixed(2);
    tr.querySelector('.detail-subtotal-display').value = formatAngkaUangFaktur(subtotal);
    tr.querySelector('.detail-subtotal').value = subtotal.toFixed(2);

    refreshTotalFaktur();
}

function refreshTotalFaktur() {
    let totalQty = 0;
    let subtotal = 0;

    document.querySelectorAll('#detail-body .detail-row').forEach((tr) => {
        totalQty += parseInt(tr.querySelector('.detail-qty')?.value || '0', 10) || 0;
        subtotal += angkaFaktur(tr.querySelector('.detail-subtotal')?.value || 0);
    });

    const diskonPersen = Math.min(Math.max(0, parseFloat(document.getElementById('diskon-persen-header')?.value || '0') || 0), 100);
    const ppnPersen = Math.min(Math.max(0, parseFloat(document.getElementById('ppn-persen-header')?.value || '0') || 0), 100);

    document.getElementById('diskon-persen-header').value = diskonPersen;
    document.getElementById('ppn-persen-header').value = ppnPersen;

    const diskonHeader = Math.round((subtotal * diskonPersen / 100) * 100) / 100;
    const dasarPpn = Math.max(0, subtotal - diskonHeader);
    const ppn = Math.round((dasarPpn * ppnPersen / 100) * 100) / 100;
    const total = Math.max(0, dasarPpn + ppn);

    document.getElementById('total-qty-display').textContent = totalQty.toLocaleString('id-ID');
    document.getElementById('subtotal-display').textContent = formatUangFaktur(subtotal);
    document.getElementById('diskon-display').textContent = formatUangFaktur(diskonHeader);
    document.getElementById('ppn-display').textContent = formatUangFaktur(ppn);
    document.getElementById('total-display').textContent = formatUangFaktur(total);

    document.getElementById('subtotal-input').value = subtotal.toFixed(2);
    document.getElementById('diskon-input').value = diskonHeader.toFixed(2);
    document.getElementById('ppn-input').value = ppn.toFixed(2);
    document.getElementById('total-input').value = total.toFixed(2);
}

function aturJatuhTempo() {
    const jenis = document.getElementById('jenis-pembayaran').value;
    const jatuhTempo = document.getElementById('jatuh-tempo');

    if (jenis === 'kredit') {
        jatuhTempo.required = true;
    } else {
        jatuhTempo.required = false;
    }
}

async function muatDetailPenyerahan() {
    const idPenyerahan = document.getElementById('id-penyerahan-penjualan').value;

    if (!idPenyerahan) {
        alert('Pilih Penyerahan Penjualan terlebih dahulu.');
        return;
    }

    try {
        const url = '<?= esc(admin_url('index.php?menu=penjualan/faktur/load-detail-penyerahan')) ?>'
            + '&id_penyerahan_penjualan=' + encodeURIComponent(idPenyerahan)
            + '<?= !empty($data_form['id_faktur_penjualan']) ? '&id_faktur_penjualan=' . (int) $data_form['id_faktur_penjualan'] : '' ?>';

        const response = await fetch(url);
        const data = await response.json();

        if (!data.success) {
            alert(data.message || 'Gagal memuat detail penyerahan.');
            return;
        }

        document.getElementById('id-pelanggan').value = data.penyerahan.id_pelanggan || '';
        document.getElementById('pelanggan-display').value = data.penyerahan.pelanggan_label || '';

        const jenis = document.getElementById('jenis-pembayaran').value;
        if (jenis === 'kredit' && data.penyerahan.jatuh_tempo_default) {
            document.getElementById('jatuh-tempo').value = data.penyerahan.jatuh_tempo_default;
        }

        const tbody = document.getElementById('detail-body');
        tbody.innerHTML = '';

        data.rows.forEach((row) => tambahBarisFaktur(row));

        refreshNomorFaktur();
        refreshTotalFaktur();
    } catch (error) {
        alert('Gagal memuat detail penyerahan: ' + error.message);
    }
}

document.getElementById('id-penyerahan-penjualan')?.addEventListener('change', function () {
    const option = this.options[this.selectedIndex];

    document.getElementById('id-pelanggan').value = option.dataset.idPelanggan || '';
    document.getElementById('pelanggan-display').value = option.dataset.pelanggan || '';

    document.getElementById('detail-body').innerHTML = `
        <tr class="empty-row">
            <td colspan="7" class="text-center text-muted py-4">
                Klik <strong>Muat Detail Penyerahan</strong> untuk mengambil produk yang sudah diserahkan.
            </td>
        </tr>
    `;

    refreshTotalFaktur();
});

document.addEventListener('DOMContentLoaded', function () {
    aturJatuhTempo();
    refreshNomorFaktur();

    document.querySelectorAll('#detail-body .detail-row').forEach((tr) => {
        refreshSubtotalFaktur(tr.querySelector('.detail-harga'));
    });

    refreshTotalFaktur();
});
</script>