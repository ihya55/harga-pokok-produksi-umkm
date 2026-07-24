<?php
$penerimaan_options = $penerimaan_options ?? [];
$bahan_options = $bahan_options ?? [];
$detail_rows = $detail_rows ?? [];
$coa_kas_bank_options = $coa_kas_bank_options ?? [];

if (count($detail_rows) === 0) {
    $detail_rows[] = [
        'id_bahan_baku' => '',
        'qty'           => '1',
        'harga'         => '0.00',
        'diskon'        => '0.00',
        'subtotal'      => '0.00',
    ];
}

$penerimaan_js = [];
foreach ($penerimaan_options as $p) {
    $penerimaan_js[] = [
        'id_penerimaan_pembelian' => (int) $p->id_penerimaan_pembelian,
        'id_pemasok'              => (int) $p->id_pemasok,
        'pemasok'                 => (string) (($p->kode_pemasok ?? '-') . ' - ' . ($p->nama_pemasok ?? '-')),
    ];
}

$bahan_js = [];
foreach ($bahan_options as $b) {
    $label_bahan = trim((string) (($b->kode_bahan_baku ?? '-') . ' - ' . ($b->nama_bahan_baku ?? '-') . ' - ' . ($b->nama_satuan ?? '-')));

    $bahan_js[] = [
        'id_bahan_baku'   => (int) $b->id_bahan_baku,
        'kode_bahan_baku' => (string) ($b->kode_bahan_baku ?? ''),
        'nama_bahan_baku' => (string) ($b->nama_bahan_baku ?? ''),
        'harga'           => (float) ($b->harga_standar ?? 0),
        'satuan'          => (string) ($b->nama_satuan ?? ''),
        'label'           => $label_bahan,
    ];
}
?>

<div class="page-header mb-4">
    <h1 class="page-title"><?= esc($page_title ?? 'Form Faktur Pembelian') ?></h1>
    <p class="page-subtitle"><?= esc($page_subtitle ?? '') ?></p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= esc($form_action) ?>" id="form-faktur-pembelian">
            <?php if (!empty($data_form['id_faktur_pembelian'])): ?>
                <input type="hidden" name="id_faktur_pembelian" value="<?= (int) $data_form['id_faktur_pembelian'] ?>">
            <?php endif; ?>

            <input type="hidden" name="id_pemasok" id="id-pemasok" value="<?= esc($data_form['id_pemasok'] ?? '') ?>">

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">No Faktur</label>
                    <input type="text" class="form-control" value="<?= esc($data_form['no_faktur_pembelian'] ?? 'Otomatis saat disimpan') ?>" readonly>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Tanggal Faktur <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal_faktur" class="form-control" required value="<?= esc($data_form['tanggal_faktur'] ?? date('Y-m-d')) ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Status</label>
                    <input type="text" class="form-control" value="<?= esc(ucfirst((string) ($data_form['status_faktur'] ?? 'draft'))) ?>" readonly>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Penerimaan Pembelian <span class="text-danger">*</span></label>
                    <select name="id_penerimaan_pembelian" id="id-penerimaan-pembelian" class="form-select" required>
                        <option value="">- Pilih Penerimaan -</option>
                        <?php foreach ($penerimaan_options as $p): ?>
                            <option
                                value="<?= (int) $p->id_penerimaan_pembelian ?>"
                                <?= ((string) ($data_form['id_penerimaan_pembelian'] ?? '') === (string) $p->id_penerimaan_pembelian) ? 'selected' : '' ?>>
                                <?= esc(($p->no_penerimaan_pembelian ?? '-') . ' - ' . ($p->kode_pemasok ?? '-') . ' - ' . ($p->nama_pemasok ?? '-')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Pemasok</label>
                    <input type="text" class="form-control" id="pemasok-display" readonly>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Jenis Pembayaran <span class="text-danger">*</span></label>
                    <select name="jenis_pembayaran" id="jenis-pembayaran" class="form-select" required>
                        <option value="kredit" <?= (($data_form['jenis_pembayaran'] ?? 'kredit') === 'kredit') ? 'selected' : '' ?>>Kredit</option>
                        <option value="tunai" <?= (($data_form['jenis_pembayaran'] ?? '') === 'tunai') ? 'selected' : '' ?>>Tunai</option>
                        <option value="transfer" <?= (($data_form['jenis_pembayaran'] ?? '') === 'transfer') ? 'selected' : '' ?>>Transfer</option>
                    </select>
                    <div class="form-text">Jurnal mengikuti template sesuai jenis pembayaran.</div>
                </div>

                <div class="col-md-4" id="wrapper-akun-kas-bank">
                    <label class="form-label fw-semibold">Akun Kas/Bank</label>
                    <select name="id_coa_kas_bank" id="id-coa-kas-bank" class="form-select">
                        <option value="">- Otomatis dari Template -</option>
                        <?php foreach ($coa_kas_bank_options as $coa): ?>
                            <option
                                value="<?= (int) $coa->id_coa ?>"
                                data-metode="<?= esc($coa->metode_pembayaran ?? '') ?>"
                                <?= ((string) ($data_form['id_coa_kas_bank'] ?? '') === (string) $coa->id_coa) ? 'selected' : '' ?>>
                                <?= esc(strtoupper((string) ($coa->metode_pembayaran ?? '-')) . ' - ' . $coa->kode_coa . ' - ' . $coa->nama_coa) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Ditampilkan dari baris kredit template jurnal tunai/transfer.</div>
                </div>

                <div class="col-md-4" id="wrapper-jatuh-tempo">
                    <label class="form-label fw-semibold">Jatuh Tempo</label>
                    <input type="date" name="jatuh_tempo" id="jatuh-tempo" class="form-control" value="<?= esc($data_form['jatuh_tempo'] ?? '') ?>">
                </div>

                <div class="col-md-12">
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" id="ada-biaya-kirim" name="ada_biaya_kirim" value="1" <?= !empty($data_form['ada_biaya_kirim']) ? 'checked' : '' ?> onchange="refreshBiayaKirim()">
                        <label class="form-check-label fw-semibold" for="ada-biaya-kirim">Ada biaya kirim / ongkir pembelian</label>
                    </div>
                    <div class="form-text">Biaya kirim akan ditambahkan ke <strong>Persediaan Bahan Baku</strong> memakai akun dari <strong>Mapping Akun</strong> sesuai metode perpetual.</div>
                </div>
                <div class="col-md-4" id="wrapper-biaya-kirim">
                    <label class="form-label fw-semibold">Biaya Kirim / Ongkir Pembelian</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="text" name="biaya_kirim_display" id="biaya-kirim-display" class="form-control text-end" value="<?= esc(number_format((float) ($data_form['biaya_kirim'] ?? 0), 2, '.', ',')) ?>" onblur="refreshBiayaKirim()">
                    </div>
                    <input type="hidden" name="biaya_kirim" id="biaya-kirim-input" value="<?= esc(number_format((float) ($data_form['biaya_kirim'] ?? 0), 2, '.', '')) ?>">
                </div>
                <input type="hidden" name="id_coa_biaya_kirim" value="<?= esc($data_form['id_coa_biaya_kirim'] ?? '') ?>">

                <div class="col-12">
                    <label class="form-label fw-semibold">Catatan</label>
                    <textarea name="catatan" class="form-control" rows="2"><?= esc($data_form['catatan'] ?? '') ?></textarea>
                </div>
            </div>

            <hr class="my-4">

            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <h2 class="h5 mb-1">Detail Bahan Baku</h2>
                    <div class="text-muted small">Qty tampil sebagai bilangan bulat. Diskon per baris diinput dalam persen, disimpan sebagai nominal.</div>
                </div>

                <button type="button" class="btn btn-outline-primary btn-sm" onclick="tambahBarisDetail()">
                    <i class="bi bi-plus-circle me-1"></i>Tambah Baris
                </button>
            </div>

            <div class="table-responsive border rounded">
                <table class="table align-middle mb-0" id="table-detail">
                    <thead class="table-light">
                        <tr>
                            <th width="60" class="text-center">No</th>
                            <th>Bahan Baku</th>
                            <th width="120" class="text-end">Qty</th>
                            <th width="170" class="text-end">Harga</th>
                            <th width="140" class="text-end">Diskon</th>
                            <th width="180" class="text-end">Subtotal</th>
                            <th width="70" class="text-center">Aksi</th>
                        </tr>
                    </thead>

                    <tbody id="detail-body">
                        <?php foreach ($detail_rows as $i => $d): ?>
                            <?php
                            $qty_row = (int) ($d['qty'] ?? 1);
                            $harga_row = (float) ($d['harga'] ?? 0);
                            $diskon_nominal_row = (float) ($d['diskon'] ?? 0);
                            $bruto_row = $qty_row * $harga_row;
                            $diskon_persen_row = $bruto_row > 0 ? (int) round($diskon_nominal_row / $bruto_row * 100) : 0;
                            ?>
                            <tr class="detail-row">
                                <td class="text-center row-no"><?= $i + 1 ?></td>

                                <td>
                                    <select name="detail[<?= $i ?>][id_bahan_baku]" class="form-select detail-bahan" required onchange="refreshBahan(this)">
                                        <option value="">- Pilih Bahan -</option>
                                        <?php foreach ($bahan_options as $b): ?>
                                            <option
                                                value="<?= (int) $b->id_bahan_baku ?>"
                                                data-harga="<?= esc(number_format((float) ($b->harga_standar ?? 0), 2, '.', '')) ?>"
                                                <?= ((string) ($d['id_bahan_baku'] ?? '') === (string) $b->id_bahan_baku) ? 'selected' : '' ?>>
                                                <?= esc(($b->kode_bahan_baku ?? '-') . ' - ' . ($b->nama_bahan_baku ?? '-') . ' - ' . ($b->nama_satuan ?? '-')) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>

                                <td>
                                    <input type="number" name="detail[<?= $i ?>][qty]" class="form-control text-end detail-qty" min="1" step="1" value="<?= esc((string) $qty_row) ?>" oninput="refreshSubtotal(this)" required>
                                </td>

                                <td>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="text" name="detail[<?= $i ?>][harga]" class="form-control text-end detail-harga" value="<?= esc(number_format($harga_row, 2, '.', ',')) ?>" onblur="refreshSubtotal(this)" required>
                                    </div>
                                </td>

                                <td>
                                    <div class="input-group">
                                        <input
                                            type="number"
                                            class="form-control text-end detail-diskon-persen"
                                            min="0"
                                            max="100"
                                            step="1"
                                            value="<?= esc((string) (int) $diskon_persen_row) ?>"
                                            oninput="refreshSubtotal(this)">
                                        <span class="input-group-text">%</span>
                                    </div>

                                    <input
                                        type="hidden"
                                        name="detail[<?= $i ?>][diskon]"
                                        class="detail-diskon"
                                        value="<?= esc(number_format($diskon_nominal_row, 2, '.', '')) ?>">
                                </td>

                                <td>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="text" class="form-control text-end detail-subtotal-display" value="<?= esc(number_format((float) ($d['subtotal'] ?? 0), 2, '.', ',')) ?>" readonly>
                                    </div>
                                    <input type="hidden" class="detail-subtotal" value="<?= esc(number_format((float) ($d['subtotal'] ?? 0), 2, '.', '')) ?>">
                                </td>

                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="hapusBarisDetail(this)">
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
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Subtotal</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="text" id="subtotal-display" class="form-control text-end" readonly value="<?= esc(number_format((float) ($data_form['subtotal'] ?? 0), 2, '.', ',')) ?>">
                    </div>
                    <input type="hidden" name="subtotal" id="subtotal-input" value="<?= esc(number_format((float) ($data_form['subtotal'] ?? 0), 2, '.', '')) ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Diskon (%)</label>
                    <div class="input-group">
                        <input type="number" name="diskon_persen" id="diskon-persen" class="form-control text-end" min="0" max="100" step="1" value="<?= esc((string) ((int) ($data_form['diskon_persen'] ?? 0))) ?>">
                        <span class="input-group-text">%</span>
                    </div>
                    <div class="form-text" id="diskon-nominal-note">Diskon: Rp 0.00</div>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">PPN (%)</label>
                    <div class="input-group">
                        <input type="number" name="ppn_persen" id="ppn-persen" class="form-control text-end" min="0" max="100" step="0.01" value="<?= esc((string) ($data_form['ppn_persen'] ?? pajak_default_persen((int) ($user['id_entitas'] ?? 0), 'PPN', 11.0))) ?>">
                        <span class="input-group-text">%</span>
                    </div>
                    <div class="form-text" id="ppn-nominal-note">PPN: Rp 0.00</div>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Total</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="text" id="total-display" class="form-control text-end fw-semibold" readonly value="<?= esc(number_format((float) ($data_form['total'] ?? 0), 2, '.', ',')) ?>">
                    </div>
                    <input type="hidden" name="total" id="total-input" value="<?= esc(number_format((float) ($data_form['total'] ?? 0), 2, '.', '')) ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Sisa Utang</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="text" id="sisa-utang-display" class="form-control text-end" readonly value="<?= esc(number_format((float) ($data_form['sisa_utang'] ?? 0), 2, '.', ',')) ?>">
                    </div>
                    <input type="hidden" name="sisa_utang" id="sisa-utang-input" value="<?= esc(number_format((float) ($data_form['sisa_utang'] ?? 0), 2, '.', '')) ?>">
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-gradient">
                    <i class="bi bi-check-circle me-1"></i><?= esc($button_label ?? 'Simpan') ?>
                </button>

                <a href="<?= esc(admin_page_url('pembelian/faktur')) ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Kembali
                </a>
            </div>
        </form>
    </div>
</div>

<script>
const penerimaanData = <?= json_encode($penerimaan_js, JSON_UNESCAPED_UNICODE) ?>;
const bahanOptions = <?= json_encode($bahan_js, JSON_UNESCAPED_UNICODE) ?>;

function parseMoney(value) {
    return parseFloat(String(value || '0').replace(/,/g, '').replace(/[^0-9.]/g, '')) || 0;
}

function formatMoney(value) {
    const num = parseFloat(value || 0);
    return num.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function cariPenerimaan(id) {
    return penerimaanData.find(row => String(row.id_penerimaan_pembelian) === String(id));
}

function refreshPenerimaan() {
    const selected = cariPenerimaan(document.getElementById('id-penerimaan-pembelian').value);

    if (!selected) {
        document.getElementById('id-pemasok').value = '';
        document.getElementById('pemasok-display').value = '';
        return;
    }

    document.getElementById('id-pemasok').value = selected.id_pemasok;
    document.getElementById('pemasok-display').value = selected.pemasok;
}

function refreshPembayaran() {
    const jenis = document.getElementById('jenis-pembayaran').value;
    const akunWrapper = document.getElementById('wrapper-akun-kas-bank');
    const akunSelect = document.getElementById('id-coa-kas-bank');
    const jatuhTempoWrapper = document.getElementById('wrapper-jatuh-tempo');

    if (jenis === 'kredit') {
        akunWrapper.style.display = 'none';
        akunSelect.value = '';
        akunSelect.required = false;
        jatuhTempoWrapper.style.display = '';
    } else {
        akunWrapper.style.display = '';
        akunSelect.required = true;
        jatuhTempoWrapper.style.display = 'none';

        let selectedStillVisible = false;

        Array.from(akunSelect.options).forEach(function(option) {
            if (!option.value) {
                option.hidden = false;
                return;
            }

            const metode = option.getAttribute('data-metode');

            if (metode === jenis) {
                option.hidden = false;
                if (option.selected) selectedStillVisible = true;
            } else {
                option.hidden = true;
                option.selected = false;
            }
        });

        if (!selectedStillVisible) {
            const firstVisible = Array.from(akunSelect.options).find(option => option.value && !option.hidden);
            akunSelect.value = firstVisible ? firstVisible.value : '';
        }
    }

    refreshTotalHeader();
}

function refreshBiayaKirim() {
    const cb = document.getElementById('ada-biaya-kirim');
    const wrapper = document.getElementById('wrapper-biaya-kirim');
    const display = document.getElementById('biaya-kirim-display');
    const input = document.getElementById('biaya-kirim-input');
    if (!cb || !wrapper || !display || !input) return;
    wrapper.style.display = cb.checked ? '' : 'none';
    let nilai = cb.checked ? parseMoney(display.value) : 0;
    if (nilai < 0) nilai = 0;
    display.value = formatMoney(nilai);
    input.value = nilai.toFixed(2);
    refreshTotalHeader();
}

function refreshBahan(select) {
    const row = select.closest('tr');
    const selected = select.options[select.selectedIndex];

    if (selected) {
        const harga = parseFloat(selected.getAttribute('data-harga') || '0');
        const inputHarga = row.querySelector('.detail-harga');

        if (harga > 0 && parseMoney(inputHarga.value) <= 0) {
            inputHarga.value = formatMoney(harga);
        }
    }

    refreshSubtotal(select);
}

function refreshSubtotal(el) {
    const row = el.closest('tr');
    const qtyInput = row.querySelector('.detail-qty');
    const hargaInput = row.querySelector('.detail-harga');
    const diskonPersenInput = row.querySelector('.detail-diskon-persen');
    const diskonNominalInput = row.querySelector('.detail-diskon');
    const subtotalDisplay = row.querySelector('.detail-subtotal-display');
    const subtotalHidden = row.querySelector('.detail-subtotal');

    let qty = parseInt(qtyInput.value || '0', 10) || 0;
    if (qty < 0) qty = 0;

    let harga = parseMoney(hargaInput.value);

    let diskonPersen = parseInt(diskonPersenInput.value || '0', 10) || 0;
    if (diskonPersen < 0) diskonPersen = 0;
    if (diskonPersen > 100) diskonPersen = 100;

    const bruto = qty * harga;
    const diskonNominal = bruto * diskonPersen / 100;
    const subtotal = bruto - diskonNominal;

    qtyInput.value = qty;
    hargaInput.value = formatMoney(harga);
    diskonPersenInput.value = String(diskonPersen);
    diskonNominalInput.value = diskonNominal.toFixed(2);

    subtotalDisplay.value = formatMoney(subtotal);
    subtotalHidden.value = subtotal.toFixed(2);

    refreshNomor();
    refreshNameIndex();
    refreshTotalHeader();
}

function refreshTotalHeader() {
    let subtotal = 0;

    document.querySelectorAll('.detail-subtotal').forEach(function(input) {
        subtotal += parseMoney(input.value);
    });

    let diskonPersen = parseInt(document.getElementById('diskon-persen').value || '0', 10) || 0;
    let ppnPersen = parseFloat(document.getElementById('ppn-persen').value || '0') || 0;

    if (diskonPersen < 0) diskonPersen = 0;
    if (diskonPersen > 100) diskonPersen = 100;
    if (ppnPersen < 0) ppnPersen = 0;
    if (ppnPersen > 100) ppnPersen = 100;

    document.getElementById('diskon-persen').value = diskonPersen;
    document.getElementById('ppn-persen').value = ppnPersen;

    const diskonNominal = subtotal * diskonPersen / 100;
    const dasarPpn = Math.max(0, subtotal - diskonNominal);
    const ppnNominal = dasarPpn * ppnPersen / 100;
    const biayaKirimInput = document.getElementById('biaya-kirim-input');
    const biayaKirim = biayaKirimInput ? parseMoney(biayaKirimInput.value) : 0;
    const total = subtotal - diskonNominal + ppnNominal + biayaKirim;

    document.getElementById('subtotal-display').value = formatMoney(subtotal);
    document.getElementById('subtotal-input').value = subtotal.toFixed(2);

    document.getElementById('diskon-nominal-note').textContent = 'Diskon: Rp ' + formatMoney(diskonNominal);
    document.getElementById('ppn-nominal-note').textContent = 'PPN: Rp ' + formatMoney(ppnNominal);

    document.getElementById('total-display').value = formatMoney(total);
    document.getElementById('total-input').value = total.toFixed(2);

    const jenis = document.getElementById('jenis-pembayaran').value;
    const sisaUtang = jenis === 'kredit' ? total : 0;

    document.getElementById('sisa-utang-display').value = formatMoney(sisaUtang);
    document.getElementById('sisa-utang-input').value = sisaUtang.toFixed(2);
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function optionBahanHtml() {
    let html = '<option value="">- Pilih Bahan -</option>';

    bahanOptions.forEach(function(item) {
        const idBahan = String(item.id_bahan_baku || '');
        if (!idBahan) return;

        const harga = Number(item.harga || 0).toFixed(2);
        const label = item.label || [
            item.kode_bahan_baku || '-',
            item.nama_bahan_baku || '-',
            item.satuan || '-'
        ].join(' - ');

        html += '<option value="' + escapeHtml(idBahan) + '" data-harga="' + escapeHtml(harga) + '">' + escapeHtml(label) + '</option>';
    });

    return html;
}

function tambahBarisDetail() {
    const tbody = document.getElementById('detail-body');
    const tr = document.createElement('tr');
    tr.className = 'detail-row';

    tr.innerHTML = `
        <td class="text-center row-no"></td>
        <td>
            <select class="form-select detail-bahan" required onchange="refreshBahan(this)">
                ${optionBahanHtml()}
            </select>
        </td>
        <td>
            <input type="number" class="form-control text-end detail-qty" min="1" step="1" value="1" oninput="refreshSubtotal(this)" required>
        </td>
        <td>
            <div class="input-group">
                <span class="input-group-text">Rp</span>
                <input type="text" class="form-control text-end detail-harga" value="0.00" onblur="refreshSubtotal(this)" required>
            </div>
        </td>
        <td>
            <div class="input-group">
                <input type="number" class="form-control text-end detail-diskon-persen" min="0" max="100" step="1" value="0" oninput="refreshSubtotal(this)">
                <span class="input-group-text">%</span>
            </div>
            <input type="hidden" class="detail-diskon" value="0.00">
        </td>
        <td>
            <div class="input-group">
                <span class="input-group-text">Rp</span>
                <input type="text" class="form-control text-end detail-subtotal-display" value="0.00" readonly>
            </div>
            <input type="hidden" class="detail-subtotal" value="0.00">
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="hapusBarisDetail(this)">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;

    tbody.appendChild(tr);
    refreshNomor();
    refreshNameIndex();
    refreshTotalHeader();
}

function tambahBarisDetailDenganData(item) {
    tambahBarisDetail();

    const row = document.querySelector('#detail-body tr:last-child');
    const selectBahan = row.querySelector('.detail-bahan');
    const idBahan = String(item.id_bahan_baku || '');

    if (idBahan && !Array.from(selectBahan.options).some(option => String(option.value) === idBahan)) {
        const option = document.createElement('option');
        option.value = idBahan;
        option.setAttribute('data-harga', Number(item.harga || 0).toFixed(2));
        option.textContent = [
            item.kode_bahan_baku || '-',
            item.nama_bahan_baku || '-',
            item.nama_satuan || item.satuan || '-'
        ].join(' - ');
        selectBahan.appendChild(option);
    }

    selectBahan.value = idBahan;

    const qty = parseInt(item.qty || 0, 10) || 0;
    const harga = parseMoney(item.harga || 0);
    const diskonNominal = parseMoney(item.diskon || 0);
    const bruto = qty * harga;
    const diskonPersen = bruto > 0 ? Math.round(diskonNominal / bruto * 100) : 0;

    row.querySelector('.detail-qty').value = qty;
    row.querySelector('.detail-harga').value = formatMoney(harga);
    row.querySelector('.detail-diskon-persen').value = String(diskonPersen);
    row.querySelector('.detail-diskon').value = diskonNominal.toFixed(2);

    refreshSubtotal(row.querySelector('.detail-qty'));
}

function loadDetailPenerimaan() {
    const idPenerimaan = document.getElementById('id-penerimaan-pembelian').value;

    if (!idPenerimaan) {
        return;
    }

    fetch('<?= esc(admin_url('index.php?menu=pembelian/faktur/load-detail-penerimaan')) ?>&id_penerimaan_pembelian=' + encodeURIComponent(idPenerimaan))
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                alert(data.message || 'Gagal memuat detail penerimaan.');
                return;
            }

            const tbody = document.getElementById('detail-body');
            tbody.innerHTML = '';

            if (!data.rows || data.rows.length === 0) {
                tambahBarisDetail();
                alert('Detail penerimaan kosong.');
                return;
            }

            data.rows.forEach(function(item) {
                tambahBarisDetailDenganData(item);
            });

            refreshNomor();
            refreshNameIndex();
            refreshTotalHeader();
        })
        .catch(error => {
            alert('Gagal menghubungi server: ' + error.message);
        });
}

function hapusBarisDetail(button) {
    const rows = document.querySelectorAll('.detail-row');

    if (rows.length <= 1) {
        alert('Minimal harus ada 1 baris detail.');
        return;
    }

    button.closest('tr').remove();
    refreshNomor();
    refreshNameIndex();
    refreshTotalHeader();
}

function refreshNomor() {
    document.querySelectorAll('.detail-row').forEach(function(row, index) {
        row.querySelector('.row-no').textContent = index + 1;
    });
}

function refreshNameIndex() {
    document.querySelectorAll('.detail-row').forEach(function(row, index) {
        row.querySelector('.detail-bahan').setAttribute('name', 'detail[' + index + '][id_bahan_baku]');
        row.querySelector('.detail-qty').setAttribute('name', 'detail[' + index + '][qty]');
        row.querySelector('.detail-harga').setAttribute('name', 'detail[' + index + '][harga]');
        row.querySelector('.detail-diskon').setAttribute('name', 'detail[' + index + '][diskon]');
    });
}

document.getElementById('id-penerimaan-pembelian').addEventListener('change', function() {
    refreshPenerimaan();
    loadDetailPenerimaan();
});

document.getElementById('jenis-pembayaran').addEventListener('change', refreshPembayaran);
document.getElementById('diskon-persen').addEventListener('input', refreshTotalHeader);
document.getElementById('ppn-persen').addEventListener('input', refreshTotalHeader);

document.getElementById('form-faktur-pembelian').addEventListener('submit', function(e) {
    refreshPenerimaan();
    refreshPembayaran();
refreshBiayaKirim();
    refreshTotalHeader();
    refreshNameIndex();

    const idPenerimaan = document.getElementById('id-penerimaan-pembelian').value;
    const idPemasok = document.getElementById('id-pemasok').value;
    const jenis = document.getElementById('jenis-pembayaran').value;
    const akun = document.getElementById('id-coa-kas-bank').value;

    if (!idPenerimaan || !idPemasok) {
        e.preventDefault();
        alert('Penerimaan pembelian wajib dipilih.');
        return false;
    }

    if ((jenis === 'tunai' || jenis === 'transfer') && !akun) {
        e.preventDefault();
        alert('Akun kas/bank wajib tersedia untuk pembayaran tunai atau transfer.');
        return false;
    }

    let valid = true;

    document.querySelectorAll('.detail-row').forEach(function(row) {
        const bahan = row.querySelector('.detail-bahan').value;
        const qty = parseInt(row.querySelector('.detail-qty').value || '0', 10) || 0;
        const harga = parseMoney(row.querySelector('.detail-harga').value);
        const diskonPersen = parseInt(row.querySelector('.detail-diskon-persen').value || '0', 10) || 0;

        if (!bahan || qty <= 0 || harga < 0 || diskonPersen < 0 || diskonPersen > 100) {
            valid = false;
        }
    });

    if (!valid) {
        e.preventDefault();
        alert('Detail bahan belum valid. Bahan wajib dipilih, qty harus lebih dari 0, dan diskon harus 0 sampai 100%.');
        return false;
    }
});

document.querySelectorAll('.detail-row').forEach(function(row) {
    refreshSubtotal(row.querySelector('.detail-qty'));
});

refreshPenerimaan();
refreshPembayaran();
refreshBiayaKirim();
refreshNomor();
refreshNameIndex();
refreshTotalHeader();
</script>