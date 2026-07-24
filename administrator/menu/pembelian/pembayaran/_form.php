<?php
$faktur_options = $faktur_options ?? [];
$coa_kas_bank_options = $coa_kas_bank_options ?? [];
$back_url = (string) ($data_form['back_url'] ?? admin_page_url('pembelian/pembayaran'));

$faktur_js = [];

foreach ($faktur_options as $f) {
    $faktur_js[] = [
        'id_faktur_pembelian' => (int) $f->id_faktur_pembelian,
        'id_pemasok'          => (int) $f->id_pemasok,
        'no_faktur_pembelian' => (string) $f->no_faktur_pembelian,
        'tanggal_faktur'      => (string) $f->tanggal_faktur,
        'total'               => (float) $f->total,
        'sisa_utang'          => (float) $f->sisa_utang,
        'pemasok'             => (string) (($f->kode_pemasok ?? '-') . ' - ' . ($f->nama_pemasok ?? '-')),
    ];
}
?>

<div class="page-header mb-4">
    <h1 class="page-title"><?= esc($page_title ?? 'Form Pembayaran Pembelian') ?></h1>
    <p class="page-subtitle"><?= esc($page_subtitle ?? '') ?></p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= esc($form_action) ?>" id="form-pembayaran-pembelian">
            <input type="hidden" name="back_url" value="<?= esc($back_url) ?>">

            <?php if (!empty($data_form['id_pembayaran_pembelian'])): ?>
                <input type="hidden" name="id_pembayaran_pembelian" value="<?= (int) $data_form['id_pembayaran_pembelian'] ?>">
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">No Pembayaran</label>
                    <input type="text" class="form-control" value="<?= esc($data_form['no_pembayaran_pembelian'] ?? 'Otomatis saat disimpan') ?>" readonly>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Tanggal Pembayaran <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal_pembayaran" class="form-control" required value="<?= esc($data_form['tanggal_pembayaran'] ?? date('Y-m-d')) ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Status Posting</label>
                    <input type="text" class="form-control" value="<?= esc(ucfirst((string) ($data_form['status_posting'] ?? 'draft'))) ?>" readonly>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Faktur Pembelian <span class="text-danger">*</span></label>
                    <select name="id_faktur_pembelian" id="id-faktur-pembelian" class="form-select" required>
                        <option value="">- Pilih Faktur Kredit Belum Lunas -</option>
                        <?php foreach ($faktur_options as $item): ?>
                            <option
                                value="<?= (int) $item->id_faktur_pembelian ?>"
                                data-pemasok="<?= (int) $item->id_pemasok ?>"
                                data-total="<?= esc(number_format((float) $item->total, 2, '.', '')) ?>"
                                data-sisa="<?= esc(number_format((float) $item->sisa_utang, 2, '.', '')) ?>"
                                <?= ((string) ($data_form['id_faktur_pembelian'] ?? '') === (string) $item->id_faktur_pembelian) ? 'selected' : '' ?>>
                                <?= esc($item->no_faktur_pembelian . ' - ' . $item->kode_pemasok . ' - ' . $item->nama_pemasok . ' - Sisa Rp ' . number_format((float) $item->sisa_utang, 2, '.', ',')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Hanya faktur kredit yang sudah posted dan masih memiliki sisa utang.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Pemasok</label>
                    <input type="text" class="form-control" id="pemasok-display" readonly>
                    <input type="hidden" name="id_pemasok" id="id-pemasok" value="<?= esc($data_form['id_pemasok'] ?? '') ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Metode Pembayaran <span class="text-danger">*</span></label>
                    <select name="metode_pembayaran" id="metode-pembayaran" class="form-select" required>
                        <option value="tunai" <?= (($data_form['metode_pembayaran'] ?? '') === 'tunai') ? 'selected' : '' ?>>Tunai</option>
                        <option value="transfer" <?= (($data_form['metode_pembayaran'] ?? 'transfer') === 'transfer') ? 'selected' : '' ?>>Transfer</option>
                    </select>
                    <div class="form-text">Akun kas/bank mengikuti template jurnal faktur pembelian.</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Akun Kas/Bank <span class="text-danger">*</span></label>
                    <select name="id_coa_kas_bank" id="id-coa-kas-bank" class="form-select" required>
                        <option value="">- Pilih Akun -</option>
                        <?php foreach ($coa_kas_bank_options as $coa): ?>
                            <option
                                value="<?= (int) $coa->id_coa ?>"
                                data-metode="<?= esc($coa->metode_pembayaran ?? '') ?>"
                                <?= ((string) ($data_form['id_coa_kas_bank'] ?? '') === (string) $coa->id_coa) ? 'selected' : '' ?>>
                                <?= esc(strtoupper((string) ($coa->metode_pembayaran ?? '-')) . ' - ' . $coa->kode_coa . ' - ' . $coa->nama_coa) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Sumber akun: baris kredit template FAKTUR_PEMBELIAN_TUNAI / TRANSFER.</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Jumlah Bayar <span class="text-danger">*</span></label>
                    <input
                        type="text"
                        id="jumlah-bayar-display"
                        class="form-control text-end"
                        value="Rp <?= esc(number_format((float) ($data_form['jumlah_bayar'] ?? 0), 2, '.', ',')) ?>"
                        autocomplete="off"
                        required>
                    <input
                        type="hidden"
                        name="jumlah_bayar"
                        id="jumlah-bayar-input"
                        value="<?= esc(number_format((float) ($data_form['jumlah_bayar'] ?? 0), 2, '.', '')) ?>">
                    <div class="form-text" id="sisa-utang-note">Sisa utang: Rp 0.00</div>
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
const fakturData = <?= json_encode($faktur_js, JSON_UNESCAPED_UNICODE) ?>;

function parseMoney(value) {
    return parseFloat(String(value || '0').replace(/Rp/gi, '').replace(/,/g, '').replace(/[^0-9.]/g, '')) || 0;
}

function formatMoney(value) {
    const num = parseFloat(value || 0);
    return 'Rp ' + num.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function cariFaktur(id) {
    return fakturData.find(function(row) {
        return String(row.id_faktur_pembelian) === String(id);
    });
}

function refreshFakturInfo(forceJumlah = false) {
    const select = document.getElementById('id-faktur-pembelian');
    const faktur = cariFaktur(select.value);

    if (!faktur) {
        document.getElementById('pemasok-display').value = '';
        document.getElementById('id-pemasok').value = '';
        document.getElementById('sisa-utang-note').textContent = 'Sisa utang: Rp 0.00';

        if (forceJumlah) {
            document.getElementById('jumlah-bayar-display').value = formatMoney(0);
            document.getElementById('jumlah-bayar-input').value = '0.00';
        }

        return;
    }

    document.getElementById('pemasok-display').value = faktur.pemasok;
    document.getElementById('id-pemasok').value = faktur.id_pemasok;
    document.getElementById('sisa-utang-note').textContent = 'Sisa utang: ' + formatMoney(faktur.sisa_utang);

    if (forceJumlah) {
        document.getElementById('jumlah-bayar-display').value = formatMoney(faktur.sisa_utang);
        document.getElementById('jumlah-bayar-input').value = parseFloat(faktur.sisa_utang || 0).toFixed(2);
    }
}

function filterAkunKasBank() {
    const metode = document.getElementById('metode-pembayaran').value;
    const selectAkun = document.getElementById('id-coa-kas-bank');
    let selectedStillVisible = false;

    Array.from(selectAkun.options).forEach(function(option) {
        if (!option.value) {
            option.hidden = false;
            return;
        }

        const metodeOption = option.getAttribute('data-metode');

        if (metodeOption === metode) {
            option.hidden = false;

            if (option.selected) {
                selectedStillVisible = true;
            }
        } else {
            option.hidden = true;
            option.selected = false;
        }
    });

    if (!selectedStillVisible) {
        const firstVisible = Array.from(selectAkun.options).find(function(option) {
            return option.value && !option.hidden;
        });

        if (firstVisible) {
            firstVisible.selected = true;
        } else {
            selectAkun.value = '';
        }
    }
}

document.getElementById('id-faktur-pembelian').addEventListener('change', function() {
    refreshFakturInfo(true);
});

document.getElementById('metode-pembayaran').addEventListener('change', function() {
    filterAkunKasBank();
});

document.getElementById('jumlah-bayar-display').addEventListener('blur', function() {
    const faktur = cariFaktur(document.getElementById('id-faktur-pembelian').value);
    let jumlah = parseMoney(this.value);

    if (faktur && jumlah > parseFloat(faktur.sisa_utang || 0)) {
        jumlah = parseFloat(faktur.sisa_utang || 0);
    }

    if (jumlah < 0) jumlah = 0;

    this.value = formatMoney(jumlah);
    document.getElementById('jumlah-bayar-input').value = jumlah.toFixed(2);
});

document.getElementById('form-pembayaran-pembelian').addEventListener('submit', function(e) {
    refreshFakturInfo(false);
    filterAkunKasBank();

    const idFaktur = document.getElementById('id-faktur-pembelian').value;
    const idPemasok = document.getElementById('id-pemasok').value;
    const idCoaKasBank = document.getElementById('id-coa-kas-bank').value;
    const jumlah = parseMoney(document.getElementById('jumlah-bayar-display').value);
    const faktur = cariFaktur(idFaktur);

    if (!idFaktur || !idPemasok) {
        e.preventDefault();
        alert('Faktur pembelian wajib dipilih.');
        return false;
    }

    if (!idCoaKasBank) {
        e.preventDefault();
        alert('Akun kas/bank belum tersedia untuk metode pembayaran ini. Cek template jurnal faktur pembelian tunai/transfer.');
        return false;
    }

    if (jumlah <= 0) {
        e.preventDefault();
        alert('Jumlah bayar wajib lebih besar dari 0.');
        return false;
    }

    if (faktur && jumlah > parseFloat(faktur.sisa_utang || 0)) {
        e.preventDefault();
        alert('Jumlah bayar tidak boleh melebihi sisa utang.');
        return false;
    }

    document.getElementById('jumlah-bayar-input').value = jumlah.toFixed(2);
});

refreshFakturInfo(false);
filterAkunKasBank();
</script>