<?php
declare(strict_types=1);

if (!function_exists('pesanan_form_rupiah')) {
    function pesanan_form_rupiah($value): string
    {
        return number_format((float) $value, 2, '.', ',');
    }
}

if (!function_exists('pesanan_form_qty')) {
    function pesanan_form_qty($value): string
    {
        return number_format((int) round((float) $value), 0, '.', '');
    }
}

if (!function_exists('pesanan_form_diskon_persen')) {
    function pesanan_form_diskon_persen($subtotal_kotor, $diskon): string
    {
        $subtotal_kotor = (float) $subtotal_kotor;
        $diskon = (float) $diskon;

        if ($subtotal_kotor <= 0 || $diskon <= 0) {
            return '0';
        }

        return (string) (int) round(($diskon / $subtotal_kotor) * 100);
    }
}

$produk_js = [];

foreach ($produk_options as $p) {
    $kodeProduk = trim((string) ($p->kode_produk ?? ''));
    $barcodeProduk = trim((string) ($p->barcode_produk ?? ''));
    $namaProduk = trim((string) ($p->nama_produk ?? ''));
    $namaSatuan = trim((string) ($p->nama_satuan ?? ''));
    $namaKategori = trim((string) ($p->nama_kategori_produk ?? ''));

    $produk_js[] = [
        'id_produk' => (int) $p->id_produk,
        'kode_produk' => $kodeProduk,
        'barcode_produk' => $barcodeProduk,
        'nama_produk' => $namaProduk,
        'nama_satuan' => $namaSatuan,
        'jenis_produk' => (string) ($p->jenis_produk ?? ''),
        'nama_kategori_produk' => $namaKategori,
        'harga_jual' => (float) ($p->harga_jual ?? 0),
        'hpp_standar' => (float) ($p->hpp_standar ?? 0),
        'label' => trim((string) (($kodeProduk !== '' ? $kodeProduk : '-') . ' - ' . ($namaProduk !== '' ? $namaProduk : '-') . ' / ' . ($namaSatuan !== '' ? $namaSatuan : '-'))),
        'search_text' => trim((string) ($kodeProduk . ' ' . $barcodeProduk . ' ' . $namaProduk . ' ' . $namaSatuan . ' ' . $namaKategori)),
    ];
}

$detail_js = [];

if (!empty($detail_rows)) {
    foreach ($detail_rows as $d) {
        $qty = (int) round((float) ($d->qty ?? 0));
        $harga = (float) ($d->harga ?? 0);
        $diskon = (float) ($d->diskon ?? 0);
        $subtotal_kotor = $qty * $harga;

        $detail_js[] = [
            'id_produk' => (int) ($d->id_produk ?? 0),
            'qty' => $qty,
            'harga' => $harga,
            'diskon_persen' => (int) pesanan_form_diskon_persen($subtotal_kotor, $diskon),
            'diskon' => $diskon,
            'subtotal' => (float) ($d->subtotal ?? 0),
            'hpp_standar' => (float) ($d->hpp_standar ?? $d->hpp_standar_produk ?? 0),
            'catatan' => (string) ($d->catatan ?? ''),
        ];
    }
}

$subtotal_header = (float) ($pesanan->subtotal ?? 0);
$diskon_header = (float) ($pesanan->diskon ?? 0);
$diskon_header_persen = pesanan_form_diskon_persen($subtotal_header, $diskon_header);
?>

<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h1 class="page-title"><?= esc($judul_halaman) ?></h1>
            <p class="page-subtitle"><?= esc($subtitle_halaman) ?></p>
        </div>

    </div>
</div>

<style>
    #table-detail-pesanan tfoot th,
    #table-detail-pesanan tfoot td {
        padding-top: 8px !important;
        padding-bottom: 8px !important;
        vertical-align: middle !important;
    }

    #table-detail-pesanan tfoot .form-control,
    #table-detail-pesanan tfoot .input-group-text {
        height: 36px !important;
        min-height: 36px !important;
        padding-top: 6px !important;
        padding-bottom: 6px !important;
        font-size: 14px !important;
    }

    #table-detail-pesanan tfoot .input-group {
        width: 150px;
        margin-left: auto;
    }

    #table-detail-pesanan tfoot .summary-input {
        width: 150px;
        margin-left: auto;
    }

    #table-detail-pesanan tfoot tr.summary-total-row th,
    #table-detail-pesanan tfoot tr.summary-total-row td {
        padding-top: 10px !important;
        padding-bottom: 10px !important;
    }


    .pesanan-scan-panel {
        border: 1px solid rgba(79, 70, 229, .18);
        background: #f8f9ff;
        border-radius: 14px;
        padding: 12px;
    }

    .pesanan-scan-status {
        min-height: 20px;
        font-size: 12px;
    }

    .pesanan-product-cell {
        min-width: 320px;
        position: relative;
    }

    .pesanan-product-search {
        position: relative;
    }

    .pesanan-product-search .detail-produk-search {
        padding-right: 36px;
    }

    .pesanan-product-search::after {
        content: "⌕";
        position: absolute;
        top: 8px;
        right: 12px;
        color: #6b7280;
        pointer-events: none;
        font-size: 14px;
    }

    .pesanan-product-dropdown {
        position: absolute;
        z-index: 1050;
        top: calc(100% + 4px);
        left: 0;
        right: 0;
        max-height: 260px;
        overflow-y: auto;
        background: #fff;
        border: 1px solid rgba(15, 23, 42, .12);
        border-radius: 12px;
        box-shadow: 0 12px 30px rgba(15, 23, 42, .14);
        padding: 6px;
    }

    .pesanan-product-option {
        width: 100%;
        border: 0;
        background: transparent;
        display: block;
        text-align: left;
        padding: 8px 10px;
        border-radius: 10px;
        color: #111827;
    }

    .pesanan-product-option:hover,
    .pesanan-product-option.active {
        background: #eef2ff;
    }

    .pesanan-product-option .produk-title {
        font-weight: 600;
        font-size: 13px;
        line-height: 1.25;
    }

    .pesanan-product-option .produk-meta {
        color: #6b7280;
        font-size: 12px;
        line-height: 1.25;
        margin-top: 2px;
    }

    .pesanan-selected-meta {
        margin-top: 3px;
        color: #6b7280;
        font-size: 12px;
        line-height: 1.25;
    }
</style>

<form method="post" action="<?= esc($action_url) ?>" id="form-pesanan-penjualan">
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h5 mb-3">Informasi Pesanan</h2>

            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Tanggal Pesanan <span class="text-danger">*</span></label>
                    <input
                        type="date"
                        name="tanggal_pesanan"
                        class="form-control"
                        value="<?= esc((string) ($pesanan->tanggal_pesanan ?? date('Y-m-d'))) ?>"
                        required>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Tanggal Kirim Rencana</label>
                    <input
                        type="date"
                        name="tanggal_kirim_rencana"
                        class="form-control"
                        value="<?= esc((string) ($pesanan->tanggal_kirim_rencana ?? '')) ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Sumber Pesanan <span class="text-danger">*</span></label>
                    <?php $sumber_selected = (string) ($pesanan->sumber_pesanan ?? 'toko'); ?>
                    <select name="sumber_pesanan" class="form-select" required>
                        <option value="toko" <?= $sumber_selected === 'toko' ? 'selected' : '' ?>>Toko</option>
                        <option value="whatsapp" <?= $sumber_selected === 'whatsapp' ? 'selected' : '' ?>>WhatsApp</option>
                        <option value="website" <?= $sumber_selected === 'website' ? 'selected' : '' ?>>Website</option>
                        <option value="reseller" <?= $sumber_selected === 'reseller' ? 'selected' : '' ?>>Reseller</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Status</label>
                    <input
                        type="text"
                        class="form-control"
                        value="<?= esc(ucwords((string) ($pesanan->status_pesanan ?? 'draft'))) ?>"
                        readonly>
                    <input type="hidden" name="status_pesanan" value="<?= esc((string) ($pesanan->status_pesanan ?? 'draft')) ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Pelanggan <span class="text-danger">*</span></label>
                    <select name="id_pelanggan" class="form-select" required>
                        <option value="">- Pilih Pelanggan -</option>
                        <?php foreach ($pelanggan_options as $p): ?>
                            <option
                                value="<?= (int) $p->id_pelanggan ?>"
                                <?= (int) ($pesanan->id_pelanggan ?? 0) === (int) $p->id_pelanggan ? 'selected' : '' ?>>
                                <?= esc((string) (($p->kode_pelanggan ?? '-') . ' - ' . ($p->nama_pelanggan ?? '-'))) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Catatan</label>
                    <textarea name="catatan" class="form-control" rows="2" placeholder="Catatan pesanan"><?= esc((string) ($pesanan->catatan ?? '')) ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <h2 class="h5 mb-1">Detail Produk</h2>
                    <div class="text-muted small">Qty memakai angka bulat dan diskon memakai persen.</div>
                </div>

                <button type="button" class="btn btn-outline-primary btn-sm" onclick="pesananTambahBaris()">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Produk
                </button>
            </div>

            <div class="pesanan-scan-panel mb-3">
                <label for="pesanan_scan_produk" class="form-label fw-semibold mb-1">Cari / Scan Barcode Produk</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-upc-scan"></i></span>
                    <input
                        type="text"
                        id="pesanan_scan_produk"
                        class="form-control"
                        placeholder="Scan barcode atau ketik kode produk lalu Enter..."
                        autocomplete="off">
                    <button class="btn btn-primary" type="button" onclick="pesananTambahProdukDariScan()">
                        <i class="bi bi-plus-circle me-1"></i> Tambah
                    </button>
                </div>
                <div id="pesanan_scan_status" class="pesanan-scan-status text-muted mt-1">
                    Bisa mencari berdasarkan barcode produk, kode produk, atau nama produk. Scan Enter akan langsung menambah qty.
                </div>
            </div>

            <div class="table-responsive border rounded">
                <table class="table table-hover align-middle mb-0" id="table-detail-pesanan">
                    <thead class="table-light">
                        <tr>
                            <th width="55" class="text-center">No</th>
                            <th>Produk</th>
                            <th width="105">Satuan</th>
                            <th width="100" class="text-end">Qty</th>
                            <th width="150" class="text-end">Harga</th>
                            <th width="115" class="text-end">Diskon %</th>
                            <th width="150" class="text-end">Diskon</th>
                            <th width="165" class="text-end">Subtotal</th>
                            <th width="70" class="text-center">Aksi</th>
                        </tr>
                    </thead>

                    <tbody id="detail-body"></tbody>

                    <tfoot class="table-light">
                            <tr>
                                <th colspan="7" class="text-end align-middle">Subtotal</th>
                                <th>
                                    <input type="text" id="subtotal_display" class="form-control text-end fw-semibold summary-input" value="0.00" readonly>
                                    <input type="hidden" name="subtotal" id="subtotal" value="0.00">
                                </th>
                                <th></th>
                            </tr>

                            <tr>
                                <th colspan="7" class="text-end align-middle">Diskon Header %</th>
                                <th>
                                    <div class="input-group">
                                        <input
                                            type="number"
                                            name="diskon_persen"
                                            id="diskon_persen"
                                            class="form-control text-end"
                                            value="<?= esc($diskon_header_persen) ?>"
                                            min="0"
                                            max="100"
                                            step="1"
                                            oninput="pesananHitungTotal()"
                                            onblur="pesananHitungTotal()">
                                        <span class="input-group-text">%</span>
                                    </div>
                                    <input type="hidden" name="diskon" id="diskon" value="0.00">
                                </th>
                                <th></th>
                            </tr>

                            <tr>
                                <th colspan="7" class="text-end align-middle">Nominal Diskon Header</th>
                                <th>
                                    <input type="text" id="diskon_display" class="form-control text-end summary-input" value="0.00" readonly>
                                </th>
                                <th></th>
                            </tr>

                            <tr class="summary-total-row">
                                <th colspan="7" class="text-end align-middle">Total</th>
                                <th>
                                    <input type="text" id="total_display" class="form-control text-end fw-bold summary-input" value="0.00" readonly>
                                    <input type="hidden" name="total" id="total" value="0.00">
                                </th>
                                <th></th>
                            </tr>
                        </tfoot>
                </table>
            </div>

            <div class="alert alert-info mt-3 mb-0">
                <i class="bi bi-info-circle me-1"></i>
                Pesanan penjualan belum mengurangi stok dan belum membuat jurnal. Stok akan berkurang saat penyerahan penjualan diposting.
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <a href="<?= esc(admin_page_url('penjualan/pesanan')) ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>

        <div class="d-flex gap-2 flex-wrap">
            <button type="reset" class="btn btn-outline-secondary" onclick="setTimeout(pesananResetForm, 50)">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
            </button>

            <button type="submit" class="btn btn-gradient" onclick="return pesananValidasiSubmit()">
                <i class="bi bi-save me-1"></i> Simpan
            </button>
        </div>
    </div>
</form>

<script>
const pesananProduk = <?= json_encode($produk_js, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const pesananDetailAwal = <?= json_encode($detail_js, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

function pesananParse(value) {
    value = String(value || '0').replace(/,/g, '').replace('Rp', '').trim();
    const n = parseFloat(value);
    return isNaN(n) ? 0 : n;
}

function pesananInt(value) {
    const n = parseInt(String(value || '0').replace(/,/g, ''), 10);
    return isNaN(n) ? 0 : n;
}

function pesananPercent(value) {
    let n = pesananInt(value);

    if (n < 0) n = 0;
    if (n > 100) n = 100;

    return n;
}

function pesananFormat(value, decimal = 2) {
    const n = parseFloat(value || 0);
    return n.toLocaleString('en-US', {
        minimumFractionDigits: decimal,
        maximumFractionDigits: decimal
    });
}

function pesananFormatInt(value) {
    const n = parseInt(value || 0, 10);
    return n.toLocaleString('en-US');
}

function pesananEscape(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function pesananNormalizeText(value) {
    return String(value || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/\s+/g, ' ')
        .trim();
}

function pesananNormalizeBarcode(value) {
    return String(value || '')
        .replace(/[\r\n\t]+/g, '')
        .replace(/\s+/g, '')
        .toUpperCase()
        .trim();
}

function pesananProdukById(idProduk) {
    return pesananProduk.find(function (p) {
        return parseInt(p.id_produk, 10) === parseInt(idProduk, 10);
    });
}

function pesananProdukByExactCode(value) {
    const kode = pesananNormalizeBarcode(value);

    if (!kode) {
        return null;
    }

    return pesananProduk.find(function (p) {
        return pesananNormalizeBarcode(p.barcode_produk || '') === kode
            || pesananNormalizeBarcode(p.kode_produk || '') === kode;
    }) || null;
}

function pesananProdukLabel(produk) {
    if (!produk) {
        return '';
    }

    const kode = produk.kode_produk || '-';
    const nama = produk.nama_produk || '-';

    return kode + ' - ' + nama;
}

function pesananProdukMeta(produk) {
    if (!produk) {
        return '';
    }

    const meta = [];
    meta.push('Barcode: ' + (produk.barcode_produk || '-'));
    meta.push('Satuan: ' + (produk.nama_satuan || '-'));

    if (produk.nama_kategori_produk) {
        meta.push('Kategori: ' + produk.nama_kategori_produk);
    }

    return meta.join(' · ');
}

function pesananSkorCariProduk(produk, queryText, queryBarcode) {
    const kode = pesananNormalizeText(produk.kode_produk || '');
    const barcode = pesananNormalizeText(produk.barcode_produk || '');
    const nama = pesananNormalizeText(produk.nama_produk || '');
    const searchText = pesananNormalizeText(produk.search_text || produk.label || '');

    if (queryBarcode && (
        pesananNormalizeBarcode(produk.barcode_produk || '') === queryBarcode
        || pesananNormalizeBarcode(produk.kode_produk || '') === queryBarcode
    )) {
        return 0;
    }

    if (kode.startsWith(queryText) || barcode.startsWith(queryText)) {
        return 1;
    }

    if (nama.startsWith(queryText)) {
        return 2;
    }

    if (searchText.includes(queryText)) {
        return 3;
    }

    return 9;
}

function pesananCariProduk(query, limit = 30) {
    const queryText = pesananNormalizeText(query);
    const queryBarcode = pesananNormalizeBarcode(query);

    let hasil = pesananProduk.slice();

    if (queryText) {
        const tokens = queryText.split(' ').filter(Boolean);

        hasil = hasil.filter(function (p) {
            const searchText = pesananNormalizeText(p.search_text || p.label || '');
            const barcodeNormal = pesananNormalizeBarcode(p.barcode_produk || '');
            const kodeNormal = pesananNormalizeBarcode(p.kode_produk || '');

            if (queryBarcode && (barcodeNormal === queryBarcode || kodeNormal === queryBarcode)) {
                return true;
            }

            return tokens.every(function (token) {
                return searchText.includes(token);
            });
        });

        hasil.sort(function (a, b) {
            const skorA = pesananSkorCariProduk(a, queryText, queryBarcode);
            const skorB = pesananSkorCariProduk(b, queryText, queryBarcode);

            if (skorA !== skorB) {
                return skorA - skorB;
            }

            return String(a.nama_produk || '').localeCompare(String(b.nama_produk || ''));
        });
    }

    return hasil.slice(0, limit);
}

function pesananKosongkanProdukRow(row, keepInput = true) {
    if (!row) {
        return;
    }

    row.querySelector('.detail-produk-id').value = '';
    row.querySelector('.detail-satuan').value = '';
    row.querySelector('.detail-hpp').value = '0.00';
    row.querySelector('.pesanan-selected-meta').textContent = '';

    if (!keepInput) {
        row.querySelector('.detail-produk-search').value = '';
    }
}

function pesananSetProdukRow(row, produk, isiHarga = true) {
    if (!row || !produk) {
        return;
    }

    row.querySelector('.detail-produk-id').value = produk.id_produk;
    row.querySelector('.detail-produk-search').value = pesananProdukLabel(produk);
    row.querySelector('.pesanan-selected-meta').textContent = pesananProdukMeta(produk);
    row.querySelector('.detail-satuan').value = produk.nama_satuan || '';
    row.querySelector('.detail-hpp').value = parseFloat(produk.hpp_standar || 0).toFixed(2);

    if (isiHarga) {
        row.querySelector('.detail-harga').value = pesananFormat(produk.harga_jual || 0);
    }

    pesananSembunyikanProdukDropdown(row.querySelector('.detail-produk-search'));
    pesananHitungBaris(row.querySelector('.detail-qty'));
}

function pesananRenderProdukDropdown(input) {
    const row = input.closest('tr');
    const dropdown = row.querySelector('.pesanan-product-dropdown');
    const hasil = pesananCariProduk(input.value, 30);

    if (hasil.length <= 0) {
        dropdown.innerHTML = '<div class="text-muted small px-2 py-2">Produk tidak ditemukan.</div>';
        dropdown.classList.remove('d-none');
        return;
    }

    dropdown.innerHTML = hasil.map(function (p, index) {
        return `
            <button
                type="button"
                class="pesanan-product-option ${index === 0 ? 'active' : ''}"
                data-id-produk="${p.id_produk}"
                onmousedown="pesananPilihProdukDariDropdown(event, this)">
                <div class="produk-title">${pesananEscape(pesananProdukLabel(p))}</div>
                <div class="produk-meta">${pesananEscape(pesananProdukMeta(p))}</div>
            </button>
        `;
    }).join('');

    dropdown.classList.remove('d-none');
}

function pesananTampilkanProdukDropdown(input) {
    pesananRenderProdukDropdown(input);
}

function pesananSembunyikanProdukDropdown(input) {
    const row = input.closest('tr');
    const dropdown = row ? row.querySelector('.pesanan-product-dropdown') : null;

    if (dropdown) {
        dropdown.classList.add('d-none');
    }
}

function pesananInputCariProduk(input) {
    const row = input.closest('tr');
    pesananKosongkanProdukRow(row, true);
    pesananRenderProdukDropdown(input);
}

function pesananBlurCariProduk(input) {
    setTimeout(function () {
        const row = input.closest('tr');
        const selectedId = row.querySelector('.detail-produk-id').value;

        if (selectedId) {
            const produk = pesananProdukById(selectedId);
            if (produk) {
                input.value = pesananProdukLabel(produk);
            }
        }

        pesananSembunyikanProdukDropdown(input);
    }, 160);
}

function pesananPilihProdukDariDropdown(event, button) {
    event.preventDefault();

    const row = button.closest('tr');
    const produk = pesananProdukById(button.getAttribute('data-id-produk'));

    if (produk) {
        pesananSetProdukRow(row, produk, true);
    }
}

function pesananKeydownCariProduk(event, input) {
    const row = input.closest('tr');
    const dropdown = row.querySelector('.pesanan-product-dropdown');
    const options = Array.from(dropdown.querySelectorAll('.pesanan-product-option'));

    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
        event.preventDefault();

        if (dropdown.classList.contains('d-none')) {
            pesananRenderProdukDropdown(input);
            return;
        }

        if (options.length <= 0) {
            return;
        }

        let activeIndex = options.findIndex(function (opt) {
            return opt.classList.contains('active');
        });

        if (activeIndex < 0) {
            activeIndex = 0;
        } else if (event.key === 'ArrowDown') {
            activeIndex = Math.min(options.length - 1, activeIndex + 1);
        } else {
            activeIndex = Math.max(0, activeIndex - 1);
        }

        options.forEach(function (opt, index) {
            opt.classList.toggle('active', index === activeIndex);
        });

        options[activeIndex].scrollIntoView({ block: 'nearest' });
        return;
    }

    if (event.key === 'Enter') {
        event.preventDefault();

        const exact = pesananProdukByExactCode(input.value);
        if (exact) {
            pesananSetProdukRow(row, exact, true);
            return;
        }

        const active = options.find(function (opt) {
            return opt.classList.contains('active');
        }) || options[0];

        if (active) {
            const produk = pesananProdukById(active.getAttribute('data-id-produk'));
            if (produk) {
                pesananSetProdukRow(row, produk, true);
            }
        }
    }
}

function pesananSetScanStatus(message, type = 'muted') {
    const status = document.getElementById('pesanan_scan_status');

    if (!status) {
        return;
    }

    status.className = 'pesanan-scan-status mt-1 text-' + type;
    status.textContent = message;
}

function pesananTambahAtauTambahQtyProduk(produk) {
    if (!produk) {
        return false;
    }

    const rows = Array.from(document.querySelectorAll('#detail-body .detail-row'));
    const existing = rows.find(function (row) {
        return String(row.querySelector('.detail-produk-id').value || '') === String(produk.id_produk || '');
    });

    if (existing) {
        const qtyInput = existing.querySelector('.detail-qty');
        qtyInput.value = pesananInt(qtyInput.value) + 1;
        pesananHitungBaris(qtyInput);
        existing.classList.add('table-success');
        setTimeout(function () { existing.classList.remove('table-success'); }, 650);
        return true;
    }

    let targetRow = rows.find(function (row) {
        return !row.querySelector('.detail-produk-id').value;
    });

    if (!targetRow) {
        pesananTambahBaris();
        targetRow = document.querySelector('#detail-body .detail-row:last-child');
    }

    pesananSetProdukRow(targetRow, produk, true);

    const qtyInput = targetRow.querySelector('.detail-qty');
    if (pesananInt(qtyInput.value) <= 0) {
        qtyInput.value = 1;
    }

    pesananHitungBaris(qtyInput);
    return true;
}

function pesananTambahProdukDariScan() {
    const input = document.getElementById('pesanan_scan_produk');
    const keyword = input ? input.value.trim() : '';

    if (!keyword) {
        pesananSetScanStatus('Masukkan atau scan barcode produk terlebih dahulu.', 'warning');
        return false;
    }

    let produk = pesananProdukByExactCode(keyword);

    if (!produk) {
        const hasil = pesananCariProduk(keyword, 2);

        if (hasil.length === 1) {
            produk = hasil[0];
        } else if (hasil.length > 1) {
            pesananSetScanStatus('Ditemukan lebih dari satu produk. Gunakan dropdown produk untuk memilih hasil yang paling sesuai.', 'warning');
            return false;
        }
    }

    if (!produk) {
        pesananSetScanStatus('Barcode/kode produk tidak ditemukan: ' + keyword, 'danger');
        return false;
    }

    pesananTambahAtauTambahQtyProduk(produk);
    pesananSetScanStatus('Produk ditambahkan: ' + pesananProdukLabel(produk), 'success');

    if (input) {
        input.value = '';
        input.focus();
    }

    return true;
}

function pesananTambahBaris(data = null) {
    const tbody = document.getElementById('detail-body');
    const tr = document.createElement('tr');
    tr.className = 'detail-row';

    const idProduk = data ? data.id_produk : '';
    const qty = data ? parseInt(data.qty || 1, 10) : 1;
    const harga = data ? parseFloat(data.harga || 0) : 0;
    const diskonPersen = data ? parseInt(data.diskon_persen || 0, 10) : 0;
    const hppStandar = data ? parseFloat(data.hpp_standar || 0) : 0;

    tr.innerHTML = `
        <td class="text-center row-no"></td>

        <td class="pesanan-product-cell">
            <div class="pesanan-product-search">
                <input type="hidden" name="id_produk[]" class="detail-produk-id" value="${idProduk}">
                <input
                    type="text"
                    class="form-control detail-produk-search"
                    value=""
                    placeholder="Ketik nama, kode, atau barcode produk..."
                    autocomplete="off"
                    onfocus="pesananTampilkanProdukDropdown(this)"
                    oninput="pesananInputCariProduk(this)"
                    onkeydown="pesananKeydownCariProduk(event, this)"
                    onblur="pesananBlurCariProduk(this)">
                <div class="pesanan-product-dropdown d-none"></div>
                <div class="pesanan-selected-meta"></div>
            </div>
        </td>

        <td>
            <input type="text" class="form-control detail-satuan" value="" readonly>
        </td>

        <td>
            <input
                type="number"
                name="qty[]"
                class="form-control text-end detail-qty"
                value="${qty}"
                min="1"
                step="1"
                oninput="pesananHitungBaris(this)"
                onblur="pesananHitungBaris(this)"
                required>
        </td>

        <td>
            <input
                type="text"
                name="harga[]"
                class="form-control text-end detail-harga"
                value="${pesananFormat(harga)}"
                onblur="pesananHitungBaris(this)"
                required>
        </td>

        <td>
            <div class="input-group">
                <input
                    type="number"
                    name="diskon_persen_detail[]"
                    class="form-control text-end detail-diskon-persen"
                    value="${diskonPersen}"
                    min="0"
                    max="100"
                    step="1"
                    oninput="pesananHitungBaris(this)"
                    onblur="pesananHitungBaris(this)">
                <span class="input-group-text">%</span>
            </div>
        </td>

        <td>
            <input type="text" class="form-control text-end detail-diskon-display" value="0.00" readonly>
            <input type="hidden" name="diskon_detail[]" class="detail-diskon" value="0.00">
        </td>

        <td>
            <input type="text" class="form-control text-end detail-subtotal-display" value="0.00" readonly>
            <input type="hidden" name="subtotal_detail[]" class="detail-subtotal" value="0.00">
            <input type="hidden" name="hpp_standar[]" class="detail-hpp" value="${hppStandar.toFixed(2)}">
            <input type="hidden" name="catatan_detail[]" value="">
        </td>

        <td class="text-center">
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="pesananHapusBaris(this)" title="Hapus">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;

    tbody.appendChild(tr);

    if (idProduk) {
        const produk = pesananProdukById(idProduk);
        if (produk) {
            pesananSetProdukRow(tr, produk, false);
        }
    }

    pesananHitungBaris(tr.querySelector('.detail-qty'));
    pesananRefreshNomor();
}

function pesananHitungBaris(element) {
    const row = element.closest('tr');

    let qty = pesananInt(row.querySelector('.detail-qty').value);
    let harga = pesananParse(row.querySelector('.detail-harga').value);
    let diskonPersen = pesananPercent(row.querySelector('.detail-diskon-persen').value);

    if (qty <= 0) qty = 1;
    if (harga < 0) harga = 0;

    row.querySelector('.detail-qty').value = qty;
    row.querySelector('.detail-harga').value = pesananFormat(harga);
    row.querySelector('.detail-diskon-persen').value = diskonPersen;

    const subtotalKotor = qty * harga;
    const diskonNominal = Math.round((subtotalKotor * diskonPersen / 100) * 100) / 100;
    const subtotal = Math.max(0, subtotalKotor - diskonNominal);

    row.querySelector('.detail-diskon-display').value = pesananFormat(diskonNominal);
    row.querySelector('.detail-diskon').value = diskonNominal.toFixed(2);

    row.querySelector('.detail-subtotal-display').value = pesananFormat(subtotal);
    row.querySelector('.detail-subtotal').value = subtotal.toFixed(2);

    pesananHitungTotal();
}

function pesananHitungTotal() {
    let subtotal = 0;

    document.querySelectorAll('.detail-subtotal').forEach(function (input) {
        subtotal += pesananParse(input.value);
    });

    const diskonPersenInput = document.getElementById('diskon_persen');
    let diskonPersen = pesananPercent(diskonPersenInput.value);

    diskonPersenInput.value = diskonPersen;

    const diskonHeader = Math.round((subtotal * diskonPersen / 100) * 100) / 100;
    const total = Math.max(0, subtotal - diskonHeader);

    document.getElementById('subtotal_display').value = pesananFormat(subtotal);
    document.getElementById('subtotal').value = subtotal.toFixed(2);

    document.getElementById('diskon_display').value = pesananFormat(diskonHeader);
    document.getElementById('diskon').value = diskonHeader.toFixed(2);

    document.getElementById('total_display').value = pesananFormat(total);
    document.getElementById('total').value = total.toFixed(2);
}

function pesananHapusBaris(button) {
    button.closest('tr').remove();
    pesananRefreshNomor();
    pesananHitungTotal();
}

function pesananRefreshNomor() {
    document.querySelectorAll('#detail-body .row-no').forEach(function (td, index) {
        td.textContent = index + 1;
    });
}

function pesananResetForm() {
    document.getElementById('detail-body').innerHTML = '';
    pesananTambahBaris();
    pesananHitungTotal();
}

function pesananValidasiSubmit() {
    const rows = document.querySelectorAll('#detail-body .detail-row');

    if (rows.length <= 0) {
        alert('Detail produk masih kosong.');
        return false;
    }

    const produkTerpilih = {};

    for (const row of rows) {
        const idProduk = row.querySelector('.detail-produk-id').value;
        const qty = pesananInt(row.querySelector('.detail-qty').value);
        const harga = pesananParse(row.querySelector('.detail-harga').value);

        if (!idProduk) {
            alert('Masih ada produk yang belum dipilih.');
            return false;
        }

        if (produkTerpilih[idProduk]) {
            alert('Produk tidak boleh dipilih lebih dari satu kali. Gabungkan qty pada baris yang sama.');
            return false;
        }

        produkTerpilih[idProduk] = true;

        if (qty <= 0) {
            alert('Qty harus lebih dari nol.');
            return false;
        }

        if (harga < 0) {
            alert('Harga tidak boleh minus.');
            return false;
        }
    }

    const total = pesananParse(document.getElementById('total').value);

    if (total <= 0) {
        alert('Total pesanan harus lebih dari nol.');
        return false;
    }

    return confirm('Simpan pesanan penjualan ini?');
}

document.addEventListener('DOMContentLoaded', function () {
    if (pesananDetailAwal.length > 0) {
        pesananDetailAwal.forEach(function (row) {
            pesananTambahBaris(row);
        });
    } else {
        pesananTambahBaris();
    }

    pesananHitungTotal();

    const scanInput = document.getElementById('pesanan_scan_produk');
    if (scanInput) {
        scanInput.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                pesananTambahProdukDariScan();
            }
        });
    }

    document.addEventListener('click', function (event) {
        if (!event.target.closest('.pesanan-product-search')) {
            document.querySelectorAll('.pesanan-product-dropdown').forEach(function (dropdown) {
                dropdown.classList.add('d-none');
            });
        }
    });
});
</script>