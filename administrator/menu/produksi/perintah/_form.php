<?php
$produk_options = $produk_options ?? [];
$resep_options = $resep_options ?? [];
$pesanan_options = $pesanan_options ?? [];
$back_url = (string) ($data_form['back_url'] ?? admin_page_url('produksi/perintah'));

$resep_js = [];

foreach ($resep_options as $r) {
    $resep_js[] = [
        'id_resep'     => (int) $r->id_resep,
        'id_produk'    => (int) $r->id_produk,
        'kode_resep'   => (string) ($r->kode_resep ?? ''),
        'nama_resep'   => (string) ($r->nama_resep ?? ''),
        'jumlah_hasil' => (int) ($r->jumlah_hasil ?? 0),
        'versi_resep'  => (string) ($r->versi_resep ?? ''),
        'label'        => (string) (
            ($r->kode_resep ?? '-') .
            ' - ' .
            ($r->nama_resep ?? '-') .
            ' - Hasil ' .
            number_format((int) ($r->jumlah_hasil ?? 0), 0, '.', ',') .
            (!empty($r->versi_resep) ? ' - ' . $r->versi_resep : '')
        ),
    ];
}
?>

<div class="page-header mb-4">
    <h1 class="page-title"><?= esc($page_title ?? 'Form Perintah Produksi') ?></h1>
    <p class="page-subtitle"><?= esc($page_subtitle ?? '') ?></p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= esc($form_action) ?>" id="form-perintah-produksi">
            <input type="hidden" name="back_url" value="<?= esc($back_url) ?>">

            <?php if (!empty($data_form['id_perintah_produksi'])): ?>
                <input type="hidden" name="id_perintah_produksi" value="<?= (int) $data_form['id_perintah_produksi'] ?>">
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">No Perintah</label>
                    <input
                        type="text"
                        class="form-control"
                        value="<?= esc($data_form['no_perintah_produksi'] ?? 'Otomatis saat disimpan') ?>"
                        readonly>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Tanggal Perintah <span class="text-danger">*</span></label>
                    <input
                        type="date"
                        name="tanggal_perintah"
                        class="form-control"
                        required
                        value="<?= esc($data_form['tanggal_perintah'] ?? date('Y-m-d')) ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Status Produksi</label>
                    <input
                        type="text"
                        class="form-control"
                        value="<?= esc(ucfirst((string) ($data_form['status_produksi'] ?? 'draft'))) ?>"
                        readonly>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Produk <span class="text-danger">*</span></label>
                    <select name="id_produk" id="id-produk" class="form-select" required>
                        <option value="">- Pilih Produk -</option>
                        <?php foreach ($produk_options as $p): ?>
                            <option
                                value="<?= (int) $p->id_produk ?>"
                                <?= ((string) ($data_form['id_produk'] ?? '') === (string) $p->id_produk) ? 'selected' : '' ?>>
                                <?= esc(($p->kode_produk ?? '-') . ' - ' . ($p->nama_produk ?? '-') . ' - ' . str_replace('_', ' ', (string) ($p->jenis_produk ?? '-'))) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Produk jadi / setengah jadi yang akan diproduksi.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Resep / BOM <span class="text-danger">*</span></label>
                    <select name="id_resep" id="id-resep" class="form-select" required data-selected="<?= esc($data_form['id_resep'] ?? '') ?>">
                        <option value="">- Pilih Resep -</option>
                    </select>
                    <div class="form-text" id="resep-note">Resep akan difilter sesuai produk.</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Qty Rencana <span class="text-danger">*</span></label>
                    <input
                        type="number"
                        name="qty_rencana"
                        id="qty-rencana"
                        class="form-control text-end"
                        min="1"
                        step="1"
                        required
                        value="<?= esc((string) ((int) ($data_form['qty_rencana'] ?? 0))) ?>">
                    <div class="form-text">Qty tampil dan disimpan sebagai bilangan bulat.</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Qty Hasil</label>
                    <input
                        type="number"
                        class="form-control text-end"
                        value="<?= esc((string) ((int) ($data_form['qty_hasil'] ?? 0))) ?>"
                        readonly>
                    <div class="form-text">Diisi otomatis dari modul Hasil Produksi.</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Pesanan Penjualan</label>
                    <select name="id_pesanan_penjualan" class="form-select">
                        <option value="">- Tanpa Pesanan Penjualan -</option>
                        <?php foreach ($pesanan_options as $ps): ?>
                            <option
                                value="<?= (int) $ps->id_pesanan_penjualan ?>"
                                <?= ((string) ($data_form['id_pesanan_penjualan'] ?? '') === (string) $ps->id_pesanan_penjualan) ? 'selected' : '' ?>>
                                <?= esc(($ps->no_pesanan_penjualan ?? '-') . ' - ' . ($ps->kode_pelanggan ?? '-') . ' - ' . ($ps->nama_pelanggan ?? '-') . ' - ' . ucfirst((string) ($ps->status_pesanan ?? '-'))) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Opsional jika produksi dibuat untuk pesanan tertentu.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Tanggal Mulai</label>
                    <input
                        type="date"
                        name="tanggal_mulai"
                        class="form-control"
                        value="<?= esc($data_form['tanggal_mulai'] ?? '') ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Tanggal Selesai Rencana</label>
                    <input
                        type="date"
                        name="tanggal_selesai"
                        class="form-control"
                        value="<?= esc($data_form['tanggal_selesai'] ?? '') ?>">
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
const resepData = <?= json_encode($resep_js, JSON_UNESCAPED_UNICODE) ?>;

function escapeHtmlPerintah(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function refreshResepOptions() {
    const produkSelect = document.getElementById('id-produk');
    const resepSelect = document.getElementById('id-resep');
    const resepNote = document.getElementById('resep-note');

    const idProduk = produkSelect.value;
    const selectedBefore = resepSelect.getAttribute('data-selected') || resepSelect.value || '';

    let html = '<option value="">- Pilih Resep -</option>';
    let jumlahResep = 0;

    resepData.forEach(function(row) {
        if (String(row.id_produk) !== String(idProduk)) {
            return;
        }

        jumlahResep++;
        const selected = String(selectedBefore) === String(row.id_resep) ? 'selected' : '';
        html += '<option value="' + row.id_resep + '" data-jumlah-hasil="' + row.jumlah_hasil + '" ' + selected + '>' + escapeHtmlPerintah(row.label) + '</option>';
    });

    resepSelect.innerHTML = html;

    if (!resepSelect.value && jumlahResep === 1) {
        const first = Array.from(resepSelect.options).find(function(opt) {
            return opt.value;
        });

        if (first) {
            first.selected = true;
        }
    }

    resepSelect.setAttribute('data-selected', resepSelect.value);

    if (!idProduk) {
        resepNote.textContent = 'Pilih produk terlebih dahulu.';
    } else if (jumlahResep <= 0) {
        resepNote.textContent = 'Produk ini belum memiliki resep aktif.';
    } else {
        resepNote.textContent = jumlahResep + ' resep aktif tersedia untuk produk ini.';
    }
}

document.getElementById('id-produk').addEventListener('change', function() {
    document.getElementById('id-resep').setAttribute('data-selected', '');
    refreshResepOptions();
});

document.getElementById('id-resep').addEventListener('change', function() {
    this.setAttribute('data-selected', this.value);
});

document.getElementById('form-perintah-produksi').addEventListener('submit', function(e) {
    const qty = parseInt(document.getElementById('qty-rencana').value || '0', 10);
    const produk = document.getElementById('id-produk').value;
    const resep = document.getElementById('id-resep').value;

    if (!produk) {
        e.preventDefault();
        alert('Produk wajib dipilih.');
        return false;
    }

    if (!resep) {
        e.preventDefault();
        alert('Resep / BOM wajib dipilih.');
        return false;
    }

    if (!Number.isInteger(qty) || qty <= 0) {
        e.preventDefault();
        alert('Qty rencana wajib berupa bilangan bulat lebih besar dari 0.');
        return false;
    }

    document.getElementById('qty-rencana').value = qty;
});

refreshResepOptions();
</script>