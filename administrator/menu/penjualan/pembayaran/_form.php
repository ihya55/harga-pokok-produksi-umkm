<?php
$faktur_options = $faktur_options ?? [];
$akun_options = $akun_options ?? [];

$faktur_js = [];

foreach ($faktur_options as $f) {
    $faktur_js[] = [
        'id_faktur_penjualan' => (int) $f->id_faktur_penjualan,
        'id_pelanggan' => (int) $f->id_pelanggan,
        'pelanggan_label' => (string) (($f->kode_pelanggan ?? '-') . ' - ' . ($f->nama_pelanggan ?? '-')),
        'total' => (float) ($f->total ?? 0),
        'sisa_piutang' => (float) ($f->sisa_piutang ?? 0),
    ];
}

if (!function_exists('pembayaran_form_uang_input')) {
    function pembayaran_form_uang_input($value): string
    {
        return number_format((float) $value, 2, '.', '');
    }
}

if (!function_exists('pembayaran_form_uang_display')) {
    function pembayaran_form_uang_display($value): string
    {
        return number_format((float) $value, 2, ',', '.');
    }
}

$sisa_piutang_awal = (float) ($data_form['sisa_piutang'] ?? 0);
$jumlah_bayar_awal = (float) ($data_form['jumlah_bayar'] ?? 0);
$sisa_setelah_bayar_awal = max(0, $sisa_piutang_awal - $jumlah_bayar_awal);
?>

<div class="page-header mb-4">
    <h1 class="page-title"><?= esc($page_title ?? 'Form Pembayaran Penjualan') ?></h1>
    <p class="page-subtitle"><?= esc($page_subtitle ?? '') ?></p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= esc($form_action) ?>" id="form-pembayaran-penjualan">
            <?php if (!empty($data_form['id_pembayaran_penjualan'])): ?>
                <input type="hidden" name="id_pembayaran_penjualan" value="<?= (int) $data_form['id_pembayaran_penjualan'] ?>">
            <?php endif; ?>

            <input type="hidden" name="id_pelanggan" id="id-pelanggan" value="<?= esc($data_form['id_pelanggan'] ?? '') ?>">
            <input type="hidden" id="sisa-piutang-hidden" value="<?= esc(pembayaran_form_uang_input($sisa_piutang_awal)) ?>">
            <input type="hidden" name="jumlah_bayar" id="jumlah-bayar-hidden" value="<?= esc(pembayaran_form_uang_input($jumlah_bayar_awal)) ?>">

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">No Pembayaran</label>
                    <input type="text" class="form-control" value="<?= esc($data_form['no_pembayaran_penjualan'] ?? 'Otomatis saat disimpan') ?>" readonly>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Tanggal Pembayaran <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal_pembayaran" class="form-control" required value="<?= esc($data_form['tanggal_pembayaran'] ?? date('Y-m-d')) ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Status</label>
                    <input type="text" class="form-control" value="<?= esc(ucfirst((string) ($data_form['status_posting'] ?? 'draft'))) ?>" readonly>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">No Piutang Pelanggan <span class="text-danger">*</span></label>
                    <select name="id_faktur_penjualan" id="id-faktur-penjualan" class="form-select" required>
                        <option value="">- Pilih Faktur Kredit Belum Lunas -</option>
                        <?php foreach ($faktur_options as $f): ?>
                            <option
                                value="<?= (int) $f->id_faktur_penjualan ?>"
                                data-id-pelanggan="<?= (int) $f->id_pelanggan ?>"
                                data-pelanggan="<?= esc(($f->kode_pelanggan ?? '-') . ' - ' . ($f->nama_pelanggan ?? '-')) ?>"
                                data-total="<?= esc(number_format((float) ($f->total ?? 0), 2, '.', '')) ?>"
                                data-sisa="<?= esc(number_format((float) ($f->sisa_piutang ?? 0), 2, '.', '')) ?>"
                                <?= ((string) ($data_form['id_faktur_penjualan'] ?? '') === (string) $f->id_faktur_penjualan) ? 'selected' : '' ?>>
                                <?= esc(($f->no_faktur_penjualan ?? '-') . ' - ' . ($f->kode_pelanggan ?? '-') . ' - Sisa: Rp ' . number_format((float) ($f->sisa_piutang ?? 0), 2, '.', ',')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Hanya piutang kredit posted yang masih punya sisa piutang.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Pelanggan</label>
                    <input type="text" class="form-control" id="pelanggan-display" value="<?= esc($data_form['pelanggan_label'] ?? '') ?>" readonly>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Total Belanja Kredit</label>
                    <input
                        type="text"
                        class="form-control text-end"
                        id="total-faktur-display"
                        value="Rp <?= esc(number_format((float) ($data_form['total_faktur'] ?? 0), 2, '.', ',')) ?>"
                        readonly>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Sisa Piutang Sebelum Bayar</label>
                    <input
                        type="text"
                        class="form-control text-end"
                        id="sisa-piutang-display"
                        value="Rp <?= esc(number_format($sisa_piutang_awal, 2, '.', ',')) ?>"
                        readonly>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Sisa Piutang Setelah Bayar</label>
                    <input
                        type="text"
                        class="form-control text-end fw-semibold"
                        id="sisa-setelah-bayar-display"
                        value="Rp <?= esc(number_format($sisa_setelah_bayar_awal, 2, '.', ',')) ?>"
                        readonly>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Jumlah Bayar <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input
                            type="text"
                            id="jumlah-bayar-display"
                            class="form-control text-end"
                            inputmode="decimal"
                            autocomplete="off"
                            required
                            value="<?= esc(pembayaran_form_uang_display($jumlah_bayar_awal)) ?>"
                            oninput="formatJumlahBayarSaatKetik(this)"
                            onblur="rapikanJumlahBayar()">
                    </div>
                    <div class="form-text">
                        Ketik nominal pembayaran. Sisa piutang akan dihitung otomatis.
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Metode Pembayaran <span class="text-danger">*</span></label>
                    <select name="metode_pembayaran" id="metode-pembayaran" class="form-select" required onchange="aturAkunKasBank()">
                        <option value="tunai" <?= ($data_form['metode_pembayaran'] ?? 'tunai') === 'tunai' ? 'selected' : '' ?>>Tunai</option>
                        <option value="transfer" <?= ($data_form['metode_pembayaran'] ?? '') === 'transfer' ? 'selected' : '' ?>>Transfer</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Akun Kas / Bank <span class="text-danger">*</span></label>
                    <select name="id_coa_kas_bank" id="id-coa-kas-bank" class="form-select" required>
                        <option value="">- Pilih Akun Kas/Bank -</option>
                        <?php foreach ($akun_options as $a): ?>
                            <?php
                            $namaAkun = strtolower((string) ($a->nama_coa ?? ''));
                            $kodeAkun = (string) ($a->kode_coa ?? '');
                            $jenisAkun = (str_contains($namaAkun, 'bank') || str_starts_with($kodeAkun, '1120')) ? 'transfer' : 'tunai';
                            ?>
                            <option
                                value="<?= (int) $a->id_coa ?>"
                                data-jenis="<?= esc($jenisAkun) ?>"
                                <?= ((string) ($data_form['id_coa_kas_bank'] ?? '') === (string) $a->id_coa) ? 'selected' : '' ?>>
                                <?= esc(($a->kode_coa ?? '-') . ' - ' . ($a->nama_coa ?? '-')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Tunai memakai akun kas, transfer memakai akun bank.</div>
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Catatan</label>
                    <textarea name="catatan" class="form-control" rows="3" placeholder="Catatan pembayaran..."><?= esc($data_form['catatan'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="alert alert-info mt-4 mb-0">
                <i class="bi bi-info-circle me-1"></i>
                Saat pembayaran diposting, sistem akan membuat jurnal penerimaan kas/bank dan mengurangi sisa piutang faktur.
            </div>

            <div class="d-flex justify-content-between align-items-center gap-2 mt-4">
                <a href="<?= esc(admin_page_url('penjualan/pembayaran')) ?>" class="btn btn-outline-secondary">
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
const fakturPembayaran = <?= json_encode($faktur_js, JSON_UNESCAPED_UNICODE) ?>;

function angkaPembayaran(value) {
    value = String(value ?? '0').trim();

    if (value === '') {
        return 0;
    }

    value = value.replace(/\s/g, '');

    const hasComma = value.includes(',');
    const hasDot = value.includes('.');

    if (hasComma && hasDot) {
        value = value.replace(/\./g, '').replace(',', '.');
    } else if (hasComma) {
        value = value.replace(',', '.');
    } else {
        const dotCount = (value.match(/\./g) || []).length;
        if (dotCount > 1) {
            value = value.replace(/\./g, '');
        }
    }

    value = value.replace(/[^0-9.]/g, '');

    const n = Number(value);
    return Number.isFinite(n) ? n : 0;
}

function formatRupiahPembayaran(value) {
    return 'Rp ' + angkaPembayaran(value).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function formatNominalInput(value) {
    return angkaPembayaran(value).toLocaleString('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
    });
}

function formatNominalInputDenganDesimal(value) {
    return angkaPembayaran(value).toLocaleString('id-ID', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function ambilAngkaDariInputNominal(value) {
    value = String(value ?? '').replace(/[^0-9,]/g, '');

    if (value === '') {
        return 0;
    }

    const parts = value.split(',');
    const angkaUtama = parts[0].replace(/\D/g, '');
    const desimal = parts.length > 1 ? parts[1].replace(/\D/g, '').slice(0, 2) : '';

    const normalized = angkaUtama + (desimal !== '' ? '.' + desimal : '');

    const number = Number(normalized);
    return Number.isFinite(number) ? number : 0;
}

function formatJumlahBayarSaatKetik(input) {
    const original = input.value;
    const adaKoma = original.includes(',');

    let value = original.replace(/[^0-9,]/g, '');

    if (value === '') {
        input.value = '';
        document.getElementById('jumlah-bayar-hidden').value = '0.00';
        hitungSisaSetelahBayar();
        return;
    }

    const parts = value.split(',');
    let angkaUtama = parts[0].replace(/\D/g, '');
    let desimal = parts.length > 1 ? parts[1].replace(/\D/g, '').slice(0, 2) : '';

    angkaUtama = angkaUtama.replace(/^0+(?=\d)/, '');

    if (angkaUtama === '') {
        angkaUtama = '0';
    }

    const angkaUtamaFormat = Number(angkaUtama).toLocaleString('id-ID');
    input.value = adaKoma ? angkaUtamaFormat + ',' + desimal : angkaUtamaFormat;

    let jumlah = ambilAngkaDariInputNominal(input.value);
    const sisa = angkaPembayaran(document.getElementById('sisa-piutang-hidden')?.value || 0);

    if (sisa > 0 && jumlah > sisa) {
        jumlah = sisa;
        input.value = formatNominalInput(jumlah);
    }

    document.getElementById('jumlah-bayar-hidden').value = jumlah.toFixed(2);

    hitungSisaSetelahBayar();
}

function rapikanJumlahBayar() {
    const input = document.getElementById('jumlah-bayar-display');
    const hidden = document.getElementById('jumlah-bayar-hidden');

    if (!input || !hidden) {
        return;
    }

    let jumlah = ambilAngkaDariInputNominal(input.value);
    const sisa = angkaPembayaran(document.getElementById('sisa-piutang-hidden')?.value || 0);

    if (jumlah < 0) {
        jumlah = 0;
    }

    if (sisa > 0 && jumlah > sisa) {
        jumlah = sisa;
    }

    hidden.value = jumlah.toFixed(2);
    input.value = formatNominalInputDenganDesimal(jumlah);

    hitungSisaSetelahBayar();
}

function hitungSisaSetelahBayar() {
    const sisaAwal = angkaPembayaran(document.getElementById('sisa-piutang-hidden')?.value || 0);
    const jumlahBayar = angkaPembayaran(document.getElementById('jumlah-bayar-hidden')?.value || 0);
    const sisaSetelah = Math.max(0, sisaAwal - jumlahBayar);

    const display = document.getElementById('sisa-setelah-bayar-display');
    if (display) {
        display.value = formatRupiahPembayaran(sisaSetelah);
    }
}

function validasiJumlahBayar() {
    const inputDisplay = document.getElementById('jumlah-bayar-display');
    const inputHidden = document.getElementById('jumlah-bayar-hidden');
    const sisa = angkaPembayaran(document.getElementById('sisa-piutang-hidden')?.value || 0);

    if (!inputDisplay || !inputHidden) {
        return;
    }

    let jumlah = angkaPembayaran(inputHidden.value || 0);

    if (jumlah < 0) {
        jumlah = 0;
    }

    if (sisa > 0 && jumlah > sisa) {
        jumlah = sisa;
    }

    inputHidden.value = jumlah.toFixed(2);
    inputDisplay.value = formatNominalInputDenganDesimal(jumlah);

    hitungSisaSetelahBayar();
}

function aturAkunKasBank() {
    const metode = document.getElementById('metode-pembayaran')?.value || 'tunai';
    const select = document.getElementById('id-coa-kas-bank');

    if (!select) {
        return;
    }

    let selectedVisible = false;

    Array.from(select.options).forEach((option) => {
        if (!option.value) {
            option.hidden = false;
            return;
        }

        const jenis = option.dataset.jenis || 'tunai';
        option.hidden = jenis !== metode;

        if (option.selected && !option.hidden) {
            selectedVisible = true;
        }
    });

    if (!selectedVisible) {
        select.value = '';

        const firstMatch = Array.from(select.options).find((option) => option.value && !option.hidden);
        if (firstMatch) {
            select.value = firstMatch.value;
        }
    }
}

async function muatDetailFaktur(idFaktur) {
    if (!idFaktur) {
        document.getElementById('id-pelanggan').value = '';
        document.getElementById('pelanggan-display').value = '';
        document.getElementById('total-faktur-display').value = 'Rp 0.00';
        document.getElementById('sisa-piutang-display').value = 'Rp 0.00';
        document.getElementById('sisa-piutang-hidden').value = '0.00';
        document.getElementById('jumlah-bayar-hidden').value = '0.00';
        document.getElementById('jumlah-bayar-display').value = '0,00';
        document.getElementById('sisa-setelah-bayar-display').value = 'Rp 0.00';
        return;
    }

    try {
        const url = '<?= admin_url('index.php?menu=penjualan/pembayaran/load-detail-faktur') ?>'
            + '&id_faktur_penjualan=' + encodeURIComponent(idFaktur);

        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json'
            }
        });

        const text = await response.text();

        let data;

        try {
            data = JSON.parse(text);
        } catch (parseError) {
            console.error('Response bukan JSON:', text);
            alert('Gagal memuat detail faktur. Server mengembalikan HTML, bukan JSON. Periksa route penjualan/pembayaran/load-detail-faktur di administrator/index.php.');
            return;
        }

        if (!data.success) {
            alert(data.message || 'Gagal memuat detail faktur.');
            document.getElementById('id-faktur-penjualan').value = '';
            return;
        }

        const total = Number(data.faktur.total || 0);
        const sisaPiutang = Number(data.faktur.sisa_piutang || 0);

        document.getElementById('id-pelanggan').value = data.faktur.id_pelanggan || '';
        document.getElementById('pelanggan-display').value = data.faktur.pelanggan_label || '';
        document.getElementById('total-faktur-display').value = formatRupiahPembayaran(total);
        document.getElementById('sisa-piutang-display').value = formatRupiahPembayaran(sisaPiutang);
        document.getElementById('sisa-piutang-hidden').value = sisaPiutang.toFixed(2);

        document.getElementById('jumlah-bayar-hidden').value = sisaPiutang.toFixed(2);
        document.getElementById('jumlah-bayar-display').value = formatNominalInputDenganDesimal(sisaPiutang);

        hitungSisaSetelahBayar();
    } catch (error) {
        alert('Gagal memuat detail faktur: ' + error.message);
    }
}

document.getElementById('id-faktur-penjualan')?.addEventListener('change', function () {
    muatDetailFaktur(this.value);
});

document.getElementById('form-pembayaran-penjualan')?.addEventListener('submit', function (event) {
    rapikanJumlahBayar();

    const jumlah = angkaPembayaran(document.getElementById('jumlah-bayar-hidden')?.value || 0);
    const sisa = angkaPembayaran(document.getElementById('sisa-piutang-hidden')?.value || 0);

    if (jumlah <= 0) {
        event.preventDefault();
        alert('Jumlah bayar wajib lebih besar dari 0.');
        return;
    }

    if (sisa > 0 && jumlah > sisa) {
        event.preventDefault();
        alert('Jumlah bayar tidak boleh melebihi sisa piutang.');
        return;
    }
});

document.addEventListener('DOMContentLoaded', function () {
    aturAkunKasBank();
    validasiJumlahBayar();
    hitungSisaSetelahBayar();
});
</script>