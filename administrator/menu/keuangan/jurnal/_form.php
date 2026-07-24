<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

require_once __DIR__ . '/../_keuangan_helper.php';

$id_entitas = keu_id_entitas();

$data = $data ?? null;
$detail = $detail ?? collect();
$is_edit = (bool) ($is_edit ?? false);

$akun_options = Capsule::table('tb_coa')
    ->where('id_entitas', $id_entitas)
    ->where('boleh_transaksi', 1)
    ->where('status_aktif', 1)
    ->orderBy('kode_coa', 'asc')
    ->get();

$form_action = $is_edit
    ? admin_page_url('keuangan/jurnal/update')
    : admin_page_url('keuangan/jurnal/simpan');

$id_jurnal = (int) ($data->id_jurnal ?? 0);
$tanggal_jurnal = (string) ($data->tanggal_jurnal ?? date('Y-m-d'));
$kode_jenis_transaksi = (string) ($data->kode_jenis_transaksi ?? ($_GET['jenis'] ?? 'JURNAL_MANUAL'));
$keterangan = (string) ($data->keterangan ?? '');

if (!in_array($kode_jenis_transaksi, ['JURNAL_MANUAL', 'SALDO_AWAL_COA'], true)) {
    $kode_jenis_transaksi = 'JURNAL_MANUAL';
}

$detail_rows = [];

if ($detail && $detail->count() > 0) {
    foreach ($detail as $d) {
        $detail_rows[] = [
            'id_coa' => (int) ($d->id_coa ?? 0),
            'debit' => (float) ($d->debit ?? 0),
            'kredit' => (float) ($d->kredit ?? 0),
            'keterangan_baris' => (string) ($d->keterangan_baris ?? ''),
        ];
    }
}

if (empty($detail_rows)) {
    $detail_rows = [
        [
            'id_coa' => 0,
            'debit' => 0,
            'kredit' => 0,
            'keterangan_baris' => '',
        ],
        [
            'id_coa' => 0,
            'debit' => 0,
            'kredit' => 0,
            'keterangan_baris' => '',
        ],
    ];
}
?>

<style>
    .journal-form-card {
        border: 0;
        border-radius: 20px;
        box-shadow: 0 12px 30px rgba(15, 23, 42, .08);
    }

    .journal-form-head {
        color: #fff;
        border-radius: 20px 20px 0 0;
        padding: 18px 20px;
        background:
            radial-gradient(circle at top left, rgba(255,255,255,.25), transparent 30%),
            linear-gradient(135deg, #2563eb 0%, #7c3aed 60%, #f97316 130%);
    }

    .journal-summary-box {
        border-radius: 16px;
        padding: 14px;
        background: #f8fafc;
        border: 1px solid #e5e7eb;
    }

    .text-debit {
        color: #2563eb !important;
    }

    .text-kredit {
        color: #f97316 !important;
    }

    .journal-table th {
        white-space: nowrap;
        font-size: 12px;
        color: #64748b;
    }

    .journal-table td {
        vertical-align: middle;
    }

    .row-invalid {
        background: rgba(239, 68, 68, .05);
    }
</style>

<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h1 class="page-title"><?= esc($is_edit ? 'Edit Jurnal' : 'Tambah Jurnal') ?></h1>
            <p class="page-subtitle">
                Input jurnal manual atau saldo awal akun COA. Total debit dan kredit harus seimbang.
            </p>
        </div>

        <a href="<?= esc(admin_page_url('keuangan/jurnal')) ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
    </div>
</div>

<div class="card journal-form-card">
    <div class="journal-form-head">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h2 class="h5 mb-1"><?= esc($is_edit ? 'Form Edit Jurnal' : 'Form Jurnal Baru') ?></h2>
                <div class="small opacity-75">
                    Gunakan <strong>JURNAL_MANUAL</strong> untuk penyesuaian umum, dan <strong>SALDO_AWAL_COA</strong> untuk saldo awal akun.
                </div>
            </div>
        </div>
    </div>

    <div class="card-body">
        <form method="post" action="<?= esc($form_action) ?>" id="formJurnal">
            <?php if ($is_edit): ?>
                <input type="hidden" name="id_jurnal" value="<?= $id_jurnal ?>">
            <?php endif; ?>

            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Tanggal Jurnal <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal_jurnal" class="form-control" value="<?= esc($tanggal_jurnal) ?>" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Jenis Jurnal <span class="text-danger">*</span></label>
                    <select name="kode_jenis_transaksi" class="form-select" required>
                        <option value="JURNAL_MANUAL" <?= $kode_jenis_transaksi === 'JURNAL_MANUAL' ? 'selected' : '' ?>>
                            Jurnal Manual
                        </option>
                        <option value="SALDO_AWAL_COA" <?= $kode_jenis_transaksi === 'SALDO_AWAL_COA' ? 'selected' : '' ?>>
                            Saldo Awal COA
                        </option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Keterangan</label>
                    <input type="text" name="keterangan" class="form-control" value="<?= esc($keterangan) ?>" placeholder="Contoh: Input saldo awal akun per 1 Januari">
                </div>
            </div>

            <div class="alert alert-info">
                <strong>Catatan saldo awal COA:</strong>
                masukkan akun-akun saldo awal dan akun penyeimbang seperti Modal Pemilik / Laba Ditahan agar total debit = total kredit.
            </div>

            <div class="d-flex justify-content-between align-items-center mb-2">
                <h3 class="h6 mb-0">Detail Jurnal</h3>

                <button type="button" class="btn btn-sm btn-outline-primary" onclick="tambahBarisJurnal()">
                    <i class="bi bi-plus-circle me-1"></i>Tambah Baris
                </button>
            </div>

            <div class="table-responsive border rounded">
                <table class="table table-hover journal-table align-middle mb-0" id="tabelJurnal">
                    <thead class="table-light">
                        <tr>
                            <th width="50" class="text-center">No</th>
                            <th style="min-width:280px;">Akun</th>
                            <th style="min-width:180px;">Keterangan Baris</th>
                            <th width="170" class="text-end">Debit</th>
                            <th width="170" class="text-end">Kredit</th>
                            <th width="70" class="text-center">Aksi</th>
                        </tr>
                    </thead>

                    <tbody id="detailBody">
                        <?php foreach ($detail_rows as $i => $d): ?>
                            <tr>
                                <td class="text-center nomor-baris"><?= $i + 1 ?></td>
                                <td>
                                    <select name="detail[<?= $i ?>][id_coa]" class="form-select akun-select" required>
                                        <option value="">- Pilih Akun -</option>
                                        <?php foreach ($akun_options as $akun): ?>
                                            <option value="<?= (int) $akun->id_coa ?>" <?= (int) $d['id_coa'] === (int) $akun->id_coa ? 'selected' : '' ?>>
                                                <?= esc($akun->kode_coa . ' - ' . $akun->nama_coa) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" name="detail[<?= $i ?>][keterangan_baris]" class="form-control" value="<?= esc($d['keterangan_baris']) ?>" placeholder="Keterangan baris">
                                </td>
                                <td>
                                    <input type="text" name="detail[<?= $i ?>][debit]" class="form-control text-end input-uang input-debit text-debit fw-semibold" value="<?= esc(number_format((float) $d['debit'], 0, ',', '.')) ?>">
                                </td>
                                <td>
                                    <input type="text" name="detail[<?= $i ?>][kredit]" class="form-control text-end input-uang input-kredit text-kredit fw-semibold" value="<?= esc(number_format((float) $d['kredit'], 0, ',', '.')) ?>">
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="hapusBarisJurnal(this)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>

                    <tfoot class="table-light">
                        <tr>
                            <th colspan="3" class="text-end">Total</th>
                            <th class="text-end text-debit" id="totalDebit">Rp 0,00</th>
                            <th class="text-end text-kredit" id="totalKredit">Rp 0,00</th>
                            <th></th>
                        </tr>
                        <tr>
                            <th colspan="3" class="text-end">Selisih</th>
                            <th colspan="2" class="text-end" id="totalSelisih">Rp 0,00</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="row g-3 mt-3">
                <div class="col-md-4">
                    <div class="journal-summary-box">
                        <div class="text-muted small">Total Debit</div>
                        <div class="h5 mb-0 text-debit" id="summaryDebit">Rp 0,00</div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="journal-summary-box">
                        <div class="text-muted small">Total Kredit</div>
                        <div class="h5 mb-0 text-kredit" id="summaryKredit">Rp 0,00</div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="journal-summary-box">
                        <div class="text-muted small">Status Balance</div>
                        <div class="h5 mb-0" id="summaryBalance">Belum dihitung</div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center gap-2 mt-4">
                <a href="<?= esc(admin_page_url('keuangan/jurnal')) ?>" class="btn btn-outline-secondary">
                    Batal
                </a>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check2-circle me-1"></i>Simpan Draft
                </button>
            </div>
        </form>
    </div>
</div>

<template id="templateAkunOptions">
    <option value="">- Pilih Akun -</option>
    <?php foreach ($akun_options as $akun): ?>
        <option value="<?= (int) $akun->id_coa ?>">
            <?= esc($akun->kode_coa . ' - ' . $akun->nama_coa) ?>
        </option>
    <?php endforeach; ?>
</template>

<script>
function onlyNumber(value) {
    return String(value || '').replace(/[^0-9]/g, '');
}

function parseUang(value) {
    const raw = onlyNumber(value);
    return raw === '' ? 0 : parseFloat(raw);
}

function formatRupiahNumber(value) {
    value = Math.round(Number(value || 0));
    return 'Rp ' + value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') + ',00';
}

function formatInputUang(input) {
    const raw = onlyNumber(input.value);
    input.value = raw.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

function hitungTotalJurnal() {
    let totalDebit = 0;
    let totalKredit = 0;

    document.querySelectorAll('.input-debit').forEach(function (input) {
        totalDebit += parseUang(input.value);
    });

    document.querySelectorAll('.input-kredit').forEach(function (input) {
        totalKredit += parseUang(input.value);
    });

    const selisih = totalDebit - totalKredit;

    document.getElementById('totalDebit').textContent = formatRupiahNumber(totalDebit);
    document.getElementById('totalKredit').textContent = formatRupiahNumber(totalKredit);
    document.getElementById('totalSelisih').textContent = formatRupiahNumber(selisih);

    document.getElementById('summaryDebit').textContent = formatRupiahNumber(totalDebit);
    document.getElementById('summaryKredit').textContent = formatRupiahNumber(totalKredit);

    const summaryBalance = document.getElementById('summaryBalance');

    if (totalDebit > 0 && totalKredit > 0 && Math.abs(selisih) < 1) {
        summaryBalance.textContent = 'Balance';
        summaryBalance.className = 'h5 mb-0 text-success';
    } else {
        summaryBalance.textContent = 'Belum balance';
        summaryBalance.className = 'h5 mb-0 text-danger';
    }

    document.querySelectorAll('#detailBody tr').forEach(function (tr) {
        const debit = parseUang(tr.querySelector('.input-debit')?.value || 0);
        const kredit = parseUang(tr.querySelector('.input-kredit')?.value || 0);

        tr.classList.remove('row-invalid');

        if (debit > 0 && kredit > 0) {
            tr.classList.add('row-invalid');
        }
    });
}

function reindexBarisJurnal() {
    document.querySelectorAll('#detailBody tr').forEach(function (tr, index) {
        tr.querySelector('.nomor-baris').textContent = index + 1;

        tr.querySelectorAll('select, input').forEach(function (field) {
            field.name = field.name.replace(/detail\[\d+\]/, 'detail[' + index + ']');
        });
    });
}

function tambahBarisJurnal() {
    const tbody = document.getElementById('detailBody');
    const index = tbody.querySelectorAll('tr').length;
    const options = document.getElementById('templateAkunOptions').innerHTML;

    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td class="text-center nomor-baris">${index + 1}</td>
        <td>
            <select name="detail[${index}][id_coa]" class="form-select akun-select" required>
                ${options}
            </select>
        </td>
        <td>
            <input type="text" name="detail[${index}][keterangan_baris]" class="form-control" placeholder="Keterangan baris">
        </td>
        <td>
            <input type="text" name="detail[${index}][debit]" class="form-control text-end input-uang input-debit text-debit fw-semibold" value="0">
        </td>
        <td>
            <input type="text" name="detail[${index}][kredit]" class="form-control text-end input-uang input-kredit text-kredit fw-semibold" value="0">
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="hapusBarisJurnal(this)">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;

    tbody.appendChild(tr);
    bindInputUang();
    hitungTotalJurnal();
}

function hapusBarisJurnal(button) {
    const rows = document.querySelectorAll('#detailBody tr');

    if (rows.length <= 2) {
        alert('Minimal harus ada 2 baris jurnal.');
        return;
    }

    button.closest('tr').remove();
    reindexBarisJurnal();
    hitungTotalJurnal();
}

function bindInputUang() {
    document.querySelectorAll('.input-uang').forEach(function (input) {
        if (input.dataset.bound === '1') {
            return;
        }

        input.dataset.bound = '1';

        input.addEventListener('input', function () {
            formatInputUang(this);
            hitungTotalJurnal();
        });

        input.addEventListener('focus', function () {
            if (this.value === '0') {
                this.value = '';
            }
        });

        input.addEventListener('blur', function () {
            if (onlyNumber(this.value) === '') {
                this.value = '0';
            }
            formatInputUang(this);
            hitungTotalJurnal();
        });
    });
}

document.getElementById('formJurnal').addEventListener('submit', function (e) {
    let totalDebit = 0;
    let totalKredit = 0;
    let validRows = 0;
    let invalidDouble = false;

    document.querySelectorAll('#detailBody tr').forEach(function (tr) {
        const idCoa = tr.querySelector('select')?.value || '';
        const debit = parseUang(tr.querySelector('.input-debit')?.value || 0);
        const kredit = parseUang(tr.querySelector('.input-kredit')?.value || 0);

        if (idCoa !== '' && (debit > 0 || kredit > 0)) {
            validRows++;
        }

        if (debit > 0 && kredit > 0) {
            invalidDouble = true;
        }

        totalDebit += debit;
        totalKredit += kredit;
    });

    if (validRows < 2) {
        e.preventDefault();
        alert('Minimal harus ada 2 baris jurnal yang memiliki akun dan nominal.');
        return;
    }

    if (invalidDouble) {
        e.preventDefault();
        alert('Satu baris jurnal tidak boleh memiliki debit dan kredit sekaligus.');
        return;
    }

    if (totalDebit <= 0 || totalKredit <= 0 || Math.abs(totalDebit - totalKredit) >= 1) {
        e.preventDefault();
        alert('Total debit dan kredit harus sama dan lebih dari 0.');
    }
});

bindInputUang();
hitungTotalJurnal();
</script>