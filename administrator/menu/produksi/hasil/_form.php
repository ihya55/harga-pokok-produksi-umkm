<?php
$perintah_options = $perintah_options ?? [];
$gudang_options = $gudang_options ?? [];

$back_url = (string) ($data_form['back_url'] ?? admin_page_url('produksi/hasil'));

$perintah_js = [];

foreach ($perintah_options as $p) {
    $perintah_js[] = [
        'id_perintah_produksi' => (int) $p->id_perintah_produksi,
        'id_produk'            => (int) $p->id_produk,
        'no_perintah_produksi' => (string) ($p->no_perintah_produksi ?? ''),
        'produk'               => (string) (($p->kode_produk ?? '-') . ' - ' . ($p->nama_produk ?? '-')),
        'qty_rencana'          => (int) ($p->qty_rencana ?? 0),
        'qty_hasil'            => (int) ($p->qty_hasil ?? 0),
        'resep'                => (string) (($p->kode_resep ?? '-') . ' - ' . ($p->nama_resep ?? '-')),
    ];
}
?>

<div class="page-header mb-4">
    <h1 class="page-title"><?= esc($page_title ?? 'Form Hasil Produksi') ?></h1>
    <p class="page-subtitle"><?= esc($page_subtitle ?? '') ?></p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= esc($form_action) ?>" id="form-hasil-produksi">
            <input type="hidden" name="back_url" value="<?= esc($back_url) ?>">

            <?php if (!empty($data_form['id_hasil_produksi'])): ?>
                <input type="hidden" name="id_hasil_produksi" value="<?= (int) $data_form['id_hasil_produksi'] ?>">
            <?php endif; ?>

            <input type="hidden" name="id_produk" id="id-produk" value="<?= esc($data_form['id_produk'] ?? '') ?>">

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">No Hasil Produksi</label>
                    <input
                        type="text"
                        class="form-control"
                        value="<?= esc($data_form['no_hasil_produksi'] ?? 'Otomatis saat disimpan') ?>"
                        readonly>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Tanggal Hasil <span class="text-danger">*</span></label>
                    <input
                        type="date"
                        name="tanggal_hasil"
                        class="form-control"
                        required
                        value="<?= esc($data_form['tanggal_hasil'] ?? date('Y-m-d')) ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Status Posting</label>
                    <input
                        type="text"
                        class="form-control"
                        value="<?= esc(ucfirst((string) ($data_form['status_posting'] ?? 'draft'))) ?>"
                        readonly>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Perintah Produksi <span class="text-danger">*</span></label>
                    <select name="id_perintah_produksi" id="id-perintah-produksi" class="form-select" required>
                        <option value="">- Pilih Perintah Produksi Posted -</option>
                        <?php foreach ($perintah_options as $p): ?>
                            <option
                                value="<?= (int) $p->id_perintah_produksi ?>"
                                data-produk="<?= (int) $p->id_produk ?>"
                                <?= ((string) ($data_form['id_perintah_produksi'] ?? '') === (string) $p->id_perintah_produksi) ? 'selected' : '' ?>>
                                <?= esc(
                                    ($p->no_perintah_produksi ?? '-') .
                                    ' - ' .
                                    ($p->kode_produk ?? '-') .
                                    ' - ' .
                                    ($p->nama_produk ?? '-') .
                                    ' - Qty Rencana ' .
                                    number_format((int) ($p->qty_rencana ?? 0), 0, '.', ',')
                                ) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Hanya perintah produksi posted yang belum memiliki hasil produksi.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Produk</label>
                    <input type="text" class="form-control" id="produk-display" readonly>
                    <div class="form-text" id="perintah-note">Produk otomatis mengikuti perintah produksi.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Gudang Hasil <span class="text-danger">*</span></label>
                    <select name="id_gudang" id="id-gudang" class="form-select" required>
                        <option value="">- Pilih Gudang -</option>
                        <?php foreach ($gudang_options as $g): ?>
                            <option
                                value="<?= (int) $g->id_gudang ?>"
                                <?= ((string) ($data_form['id_gudang'] ?? '') === (string) $g->id_gudang) ? 'selected' : '' ?>>
                                <?= esc(($g->kode_gudang ?? '-') . ' - ' . ($g->nama_gudang ?? '-') . ' - ' . str_replace('_', ' ', (string) ($g->jenis_gudang ?? '-'))) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Stok produk jadi akan masuk ke gudang ini saat posting.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Qty Hasil <span class="text-danger">*</span></label>
                    <input
                        type="number"
                        name="qty_hasil"
                        id="qty-hasil"
                        class="form-control text-end"
                        min="1"
                        step="1"
                        required
                        value="<?= esc((string) ((int) ($data_form['qty_hasil'] ?? 0))) ?>">
                    <div class="form-text">Qty hasil disimpan sebagai angka bulat.</div>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Biaya Bahan</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input
                            type="text"
                            id="total-biaya-bahan-display"
                            class="form-control text-end"
                            value="<?= esc(number_format((float) ($data_form['total_biaya_bahan'] ?? 0), 2, '.', ',')) ?>"
                            readonly>
                    </div>
                    <input type="hidden" name="total_biaya_bahan" id="total-biaya-bahan" value="<?= esc(number_format((float) ($data_form['total_biaya_bahan'] ?? 0), 2, '.', '')) ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Biaya Tenaga Kerja</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input
                            type="text"
                            id="total-biaya-tenaga-kerja-display"
                            class="form-control text-end"
                            value="<?= esc(number_format((float) ($data_form['total_biaya_tenaga_kerja'] ?? 0), 2, '.', ',')) ?>"
                            readonly>
                    </div>
                    <input type="hidden" name="total_biaya_tenaga_kerja" id="total-biaya-tenaga-kerja" value="<?= esc(number_format((float) ($data_form['total_biaya_tenaga_kerja'] ?? 0), 2, '.', '')) ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Biaya BOP</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input
                            type="text"
                            id="total-biaya-bop-display"
                            class="form-control text-end"
                            value="<?= esc(number_format((float) ($data_form['total_biaya_bop'] ?? 0), 2, '.', ',')) ?>"
                            readonly>
                    </div>
                    <input type="hidden" name="total_biaya_bop" id="total-biaya-bop" value="<?= esc(number_format((float) ($data_form['total_biaya_bop'] ?? 0), 2, '.', '')) ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Total HPP</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input
                            type="text"
                            id="total-hpp-display"
                            class="form-control text-end fw-semibold"
                            value="<?= esc(number_format((float) ($data_form['total_hpp'] ?? 0), 2, '.', ',')) ?>"
                            readonly>
                    </div>
                    <input type="hidden" name="total_hpp" id="total-hpp" value="<?= esc(number_format((float) ($data_form['total_hpp'] ?? 0), 2, '.', '')) ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">HPP per Unit</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input
                            type="text"
                            id="hpp-per-unit-display"
                            class="form-control text-end fw-semibold"
                            value="<?= esc(number_format((float) ($data_form['hpp_per_unit'] ?? 0), 2, '.', ',')) ?>"
                            readonly>
                    </div>
                    <input type="hidden" name="hpp_per_unit" id="hpp-per-unit" value="<?= esc(number_format((float) ($data_form['hpp_per_unit'] ?? 0), 2, '.', '')) ?>">
                </div>

                <div class="col-md-8 d-flex align-items-end">
                    <button type="button" class="btn btn-outline-primary" onclick="hitungUlangHpp()">
                        <i class="bi bi-calculator me-1"></i>Hitung Ulang HPP dari Transaksi Produksi
                    </button>
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Catatan</label>
                    <textarea name="catatan" class="form-control" rows="3"><?= esc($data_form['catatan'] ?? '') ?></textarea>
                </div>
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
const perintahData = <?= json_encode($perintah_js, JSON_UNESCAPED_UNICODE) ?>;

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

function cariPerintah(id) {
    return perintahData.find(function(row) {
        return String(row.id_perintah_produksi) === String(id);
    });
}

function refreshPerintahInfo() {
    const id = document.getElementById('id-perintah-produksi').value;
    const row = cariPerintah(id);

    if (!row) {
        document.getElementById('id-produk').value = '';
        document.getElementById('produk-display').value = '';
        document.getElementById('perintah-note').textContent = 'Produk otomatis mengikuti perintah produksi.';
        return;
    }

    document.getElementById('id-produk').value = row.id_produk;
    document.getElementById('produk-display').value = row.produk;
    document.getElementById('perintah-note').textContent =
        'Qty rencana: ' + row.qty_rencana + ' | Resep: ' + row.resep;
}

function refreshHppPerUnit() {
    const qty = parseInt(document.getElementById('qty-hasil').value || '0', 10) || 0;
    const totalHpp = parseMoney(document.getElementById('total-hpp').value);

    const hppPerUnit = qty > 0 ? (totalHpp / qty) : 0;

    document.getElementById('hpp-per-unit').value = hppPerUnit.toFixed(2);
    document.getElementById('hpp-per-unit-display').value = formatMoney(hppPerUnit);
}

function setHppData(data) {
    const bahan = parseFloat(data.total_biaya_bahan || 0);
    const tenaga = parseFloat(data.total_biaya_tenaga_kerja || 0);
    const bop = parseFloat(data.total_biaya_bop || 0);
    const total = parseFloat(data.total_hpp || 0);

    document.getElementById('total-biaya-bahan').value = bahan.toFixed(2);
    document.getElementById('total-biaya-bahan-display').value = formatMoney(bahan);

    document.getElementById('total-biaya-tenaga-kerja').value = tenaga.toFixed(2);
    document.getElementById('total-biaya-tenaga-kerja-display').value = formatMoney(tenaga);

    document.getElementById('total-biaya-bop').value = bop.toFixed(2);
    document.getElementById('total-biaya-bop-display').value = formatMoney(bop);

    document.getElementById('total-hpp').value = total.toFixed(2);
    document.getElementById('total-hpp-display').value = formatMoney(total);

    refreshHppPerUnit();
}

function hitungUlangHpp() {
    const idPerintah = document.getElementById('id-perintah-produksi').value;

    if (!idPerintah) {
        alert('Pilih perintah produksi terlebih dahulu.');
        return;
    }

    fetch('<?= esc(admin_url('index.php?menu=produksi/hasil/hitung-hpp')) ?>&id_perintah_produksi=' + encodeURIComponent(idPerintah))
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                alert(data.message || 'Gagal menghitung HPP.');
                return;
            }

            setHppData(data.data);
        })
        .catch(() => {
            alert('Gagal menghubungi server.');
        });
}

document.getElementById('id-perintah-produksi').addEventListener('change', function() {
    refreshPerintahInfo();
    hitungUlangHpp();
});

document.getElementById('qty-hasil').addEventListener('input', function() {
    let qty = parseInt(this.value || '0', 10) || 0;
    if (qty < 0) qty = 0;
    this.value = qty;
    refreshHppPerUnit();
});

document.getElementById('form-hasil-produksi').addEventListener('submit', function(e) {
    refreshPerintahInfo();
    refreshHppPerUnit();

    const idPerintah = document.getElementById('id-perintah-produksi').value;
    const idProduk = document.getElementById('id-produk').value;
    const idGudang = document.getElementById('id-gudang').value;
    const qty = parseInt(document.getElementById('qty-hasil').value || '0', 10) || 0;
    const totalHpp = parseMoney(document.getElementById('total-hpp').value);

    if (!idPerintah || !idProduk || !idGudang) {
        e.preventDefault();
        alert('Perintah produksi, produk, dan gudang wajib valid.');
        return false;
    }

    if (qty <= 0) {
        e.preventDefault();
        alert('Qty hasil wajib lebih besar dari 0.');
        return false;
    }

    if (totalHpp <= 0) {
        e.preventDefault();
        alert('Total HPP masih 0. Pastikan pengambilan bahan / biaya produksi sudah posted atau hitung ulang HPP.');
        return false;
    }
});

refreshPerintahInfo();
refreshHppPerUnit();
</script>