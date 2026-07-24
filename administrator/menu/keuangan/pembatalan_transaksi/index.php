<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

require_once __DIR__ . '/../_keuangan_helper.php';

$id_entitas_login = keu_id_entitas();
$user = keu_user();

$role_user = strtolower(trim((string) (
    $user['kode_role'] ??
    $user['role'] ??
    $user['nama_role'] ??
    ''
)));

$is_super_admin = $role_user === 'super_admin';
$is_admin_entitas = $role_user === 'admin_entitas';

if (!$is_super_admin && !$is_admin_entitas) {
    ?>
    <div class="alert alert-danger">Anda tidak memiliki akses ke menu Pembatalan Transaksi.</div>
    <?php
    return;
}

$tanggal_awal = keu_tanggal_mysql($_GET['tanggal_awal'] ?? null, date('Y-m-01'));
$tanggal_akhir = keu_tanggal_mysql($_GET['tanggal_akhir'] ?? null, date('Y-m-t'));
$q = trim((string) ($_GET['q'] ?? ''));
$mode = trim((string) ($_GET['mode'] ?? 'belum_batal'));
$perPage = (int) ($_GET['per_page'] ?? 25);
$page = max(1, (int) ($_GET['hal'] ?? 1));

if (!in_array($mode, ['belum_batal', 'sudah_batal'], true)) {
    $mode = 'belum_batal';
}

if (!in_array($perPage, [10, 25, 50, 100], true)) {
    $perPage = 25;
}

if ($mode === 'belum_batal') {
    $query = Capsule::table('tb_jurnal as j')
        ->leftJoin('tb_pembatalan_transaksi as p', function ($join) {
            $join->on('p.id_jurnal_asal', '=', 'j.id_jurnal')
                ->where('p.status_pembatalan', '=', 'posted');
        })
        ->whereNull('p.id_pembatalan_transaksi')
        ->where('j.status_jurnal', 'posted')
        ->where('j.kode_jenis_transaksi', 'not like', 'REVERSAL_%')
        ->where('j.kode_jenis_transaksi', '<>', 'SALDO_AWAL_COA')
        ->whereBetween('j.tanggal_jurnal', [$tanggal_awal, $tanggal_akhir]);

    if (!$is_super_admin) {
        $query->where('j.id_entitas', $id_entitas_login);
    }

    if ($q !== '') {
        $query->where(function ($sub) use ($q) {
            $sub->where('j.no_jurnal', 'like', '%' . $q . '%')
                ->orWhere('j.kode_jenis_transaksi', 'like', '%' . $q . '%')
                ->orWhere('j.no_sumber', 'like', '%' . $q . '%')
                ->orWhere('j.tabel_sumber', 'like', '%' . $q . '%')
                ->orWhere('j.keterangan', 'like', '%' . $q . '%');
        });
    }

    $totalRows = (int) (clone $query)->count();
    $totalPages = max(1, (int) ceil($totalRows / $perPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;

    $rows = $query
        ->select(['j.*'])
        ->orderBy('j.tanggal_jurnal', 'desc')
        ->orderBy('j.id_jurnal', 'desc')
        ->skip($offset)
        ->take($perPage)
        ->get();
} else {
    $query = Capsule::table('tb_pembatalan_transaksi as p')
        ->join('tb_jurnal as ja', 'ja.id_jurnal', '=', 'p.id_jurnal_asal')
        ->leftJoin('tb_jurnal as jr', 'jr.id_jurnal', '=', 'p.id_jurnal_reversal')
        ->whereBetween('p.tanggal_pembatalan', [$tanggal_awal, $tanggal_akhir]);

    if (!$is_super_admin) {
        $query->where('p.id_entitas', $id_entitas_login);
    }

    if ($q !== '') {
        $query->where(function ($sub) use ($q) {
            $sub->where('p.no_pembatalan', 'like', '%' . $q . '%')
                ->orWhere('p.no_jurnal_asal', 'like', '%' . $q . '%')
                ->orWhere('p.no_jurnal_reversal', 'like', '%' . $q . '%')
                ->orWhere('p.no_sumber', 'like', '%' . $q . '%')
                ->orWhere('p.kode_jenis_transaksi_asal', 'like', '%' . $q . '%')
                ->orWhere('p.alasan_pembatalan', 'like', '%' . $q . '%');
        });
    }

    $totalRows = (int) (clone $query)->count();
    $totalPages = max(1, (int) ceil($totalRows / $perPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;

    $rows = $query
        ->select([
            'p.*',
            'ja.tanggal_jurnal as tanggal_jurnal_asal',
            'ja.total_debit as total_debit_asal',
            'ja.total_kredit as total_kredit_asal',
            'jr.no_jurnal as no_jurnal_reversal_real',
        ])
        ->orderBy('p.tanggal_pembatalan', 'desc')
        ->orderBy('p.id_pembatalan_transaksi', 'desc')
        ->skip($offset)
        ->take($perPage)
        ->get();
}

$params_cetak = $_GET;
$params_cetak['menu'] = 'keuangan/pembatalan-transaksi/cetak';
$url_cetak = admin_url('index.php?' . http_build_query($params_cetak));
?>

<style>
    .rev-hero {
        border: 0;
        border-radius: 24px;
        color: #fff;
        background:
            radial-gradient(circle at top left, rgba(255,255,255,.24), transparent 30%),
            linear-gradient(135deg, #2563eb 0%, #7c3aed 58%, #f97316 128%);
        box-shadow: 0 16px 38px rgba(37, 99, 235, .20);
    }

    .rev-card {
        border: 0;
        border-radius: 20px;
        box-shadow: 0 12px 30px rgba(15, 23, 42, .08);
    }

    .rev-table thead th {
        font-size: 12px;
        color: #64748b;
        white-space: nowrap;
        letter-spacing: .03em;
        text-transform: uppercase;
    }

    .rev-table td {
        vertical-align: middle;
    }

    .rev-badge-blue {
        background: rgba(37, 99, 235, .10);
        color: #2563eb;
        border: 1px solid rgba(37, 99, 235, .16);
    }

    .rev-badge-orange {
        background: rgba(249, 115, 22, .12);
        color: #f97316;
        border: 1px solid rgba(249, 115, 22, .18);
    }

    .rev-btn-cancel {
        border-color: #ef4444;
        color: #ef4444;
        border-radius: 12px;
    }

    .rev-btn-cancel:hover {
        background: #ef4444;
        color: #fff;
    }

    .rev-modal-head {
        color: #fff;
        background:
            radial-gradient(circle at top left, rgba(255,255,255,.22), transparent 30%),
            linear-gradient(135deg, #2563eb 0%, #7c3aed 58%, #f97316 128%);
    }

    .rev-info-box {
        border: 1px solid #e5e7eb;
        background: #f8fafc;
        border-radius: 16px;
        padding: 14px;
    }

    .rev-info-label {
        color: #64748b;
        font-size: 12px;
        margin-bottom: 4px;
    }

    .rev-info-value {
        color: #111827;
        font-weight: 700;
        word-break: break-word;
    }
</style>

<div class="card rev-hero mb-4">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="badge bg-white bg-opacity-25 mb-3">Keuangan / Akuntansi</div>
                <h1 class="fw-bold mb-1">Pembatalan Transaksi Posted</h1>
                <div class="opacity-75">
                    Batalkan transaksi posted dengan jurnal pembalik tanpa menghapus audit trail.
                </div>
            </div>

            <div class="text-end">
                <a href="<?= esc($url_cetak) ?>" target="_blank" class="btn btn-light">
                    <i class="bi bi-printer me-1"></i>Cetak
                </a>
            </div>
        </div>
    </div>
</div>

<div class="card rev-card mb-4">
    <div class="card-body">
        <form method="get" action="<?= esc(admin_url('index.php')) ?>" class="row g-2 align-items-end">
            <input type="hidden" name="menu" value="keuangan/pembatalan-transaksi">

            <div class="col-md-2">
                <label class="form-label">Tanggal Awal</label>
                <input type="date" name="tanggal_awal" class="form-control" value="<?= esc($tanggal_awal) ?>">
            </div>

            <div class="col-md-2">
                <label class="form-label">Tanggal Akhir</label>
                <input type="date" name="tanggal_akhir" class="form-control" value="<?= esc($tanggal_akhir) ?>">
            </div>

            <div class="col-md-2">
                <label class="form-label">Mode</label>
                <select name="mode" class="form-select">
                    <option value="belum_batal" <?= $mode === 'belum_batal' ? 'selected' : '' ?>>Belum Dibatalkan</option>
                    <option value="sudah_batal" <?= $mode === 'sudah_batal' ? 'selected' : '' ?>>Sudah Dibatalkan</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Pencarian</label>
                <input type="text" name="q" class="form-control" value="<?= esc($q) ?>" placeholder="Jurnal, sumber, alasan...">
            </div>

            <div class="col-md-1">
                <label class="form-label">Baris</label>
                <select name="per_page" class="form-select">
                    <?php foreach ([10, 25, 50, 100] as $limit): ?>
                        <option value="<?= $limit ?>" <?= $perPage === $limit ? 'selected' : '' ?>><?= $limit ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2 d-grid">
                <button class="btn btn-outline-primary" type="submit">
                    <i class="bi bi-search me-1"></i>Filter
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card rev-card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
            <div>
                <h2 class="h5 mb-1"><?= $mode === 'belum_batal' ? 'Transaksi Posted yang Bisa Dibatalkan' : 'Riwayat Pembatalan' ?></h2>
                <div class="text-muted small">
                    <?= $mode === 'belum_batal'
                        ? 'Pilih transaksi posted untuk dibuatkan jurnal reversal.'
                        : 'Daftar transaksi yang sudah dibatalkan dengan jurnal reversal.' ?>
                </div>
            </div>

            <div class="badge rounded-pill <?= $mode === 'belum_batal' ? 'rev-badge-blue' : 'rev-badge-orange' ?>">
                <?= keu_angka($totalRows) ?> data
            </div>
        </div>

        <div class="table-responsive border rounded">
            <?php if ($mode === 'belum_batal'): ?>
                <table class="table table-hover align-middle mb-0 rev-table">
                    <thead class="table-light">
                        <tr>
                            <th width="60" class="text-center">No</th>
                            <th>Tanggal</th>
                            <th>No Jurnal</th>
                            <th>Jenis Transaksi</th>
                            <th>Sumber</th>
                            <th>Keterangan</th>
                            <th class="text-end">Nominal</th>
                            <th width="120" class="text-center">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if ($rows->count() === 0): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">Tidak ada transaksi yang bisa dibatalkan.</td>
                            </tr>
                        <?php else: ?>
                            <?php $no = $offset + 1; ?>
                            <?php foreach ($rows as $row): ?>
                                <?php
                                $modalPayload = [
                                    'id_jurnal' => (int) $row->id_jurnal,
                                    'tanggal_pembatalan' => date('Y-m-d'),
                                    'no_jurnal' => (string) $row->no_jurnal,
                                    'tanggal_jurnal' => keu_tanggal($row->tanggal_jurnal),
                                    'kode_jenis_transaksi' => (string) $row->kode_jenis_transaksi,
                                    'no_sumber' => (string) ($row->no_sumber ?? '-'),
                                    'keterangan' => (string) ($row->keterangan ?? '-'),
                                    'nominal' => keu_uang($row->total_debit ?? 0),
                                ];
                                ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td><?= esc(keu_tanggal($row->tanggal_jurnal)) ?></td>
                                    <td>
                                        <a href="<?= esc(admin_page_url('keuangan/jurnal/detail') . '&id=' . (int) $row->id_jurnal) ?>" class="text-decoration-none fw-semibold">
                                            <?= esc((string) $row->no_jurnal) ?>
                                        </a>
                                    </td>
                                    <td><?= esc((string) $row->kode_jenis_transaksi) ?></td>
                                    <td><?= keu_sumber_link($row->tabel_sumber ?? null, $row->id_sumber ?? null, $row->no_sumber ?? null) ?></td>
                                    <td><?= esc((string) ($row->keterangan ?? '-')) ?></td>
                                    <td class="text-end fw-semibold"><?= keu_uang($row->total_debit ?? 0) ?></td>
                                    <td class="text-center">
                                        <button
                                            type="button"
                                            class="btn btn-sm rev-btn-cancel btn-open-batal"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalPembatalan"
                                            data-payload="<?= esc(json_encode($modalPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"
                                            title="Batalkan transaksi"
                                        >
                                            <i class="bi bi-arrow-counterclockwise me-1"></i>Batal
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <table class="table table-hover align-middle mb-0 rev-table">
                    <thead class="table-light">
                        <tr>
                            <th width="60" class="text-center">No</th>
                            <th>Tanggal Batal</th>
                            <th>No Pembatalan</th>
                            <th>Jurnal Asal</th>
                            <th>Jurnal Reversal</th>
                            <th>Sumber</th>
                            <th>Alasan</th>
                            <th class="text-end">Nominal</th>
                            <th width="90" class="text-center">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if ($rows->count() === 0): ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">Belum ada riwayat pembatalan.</td>
                            </tr>
                        <?php else: ?>
                            <?php $no = $offset + 1; ?>
                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td><?= esc(keu_tanggal($row->tanggal_pembatalan)) ?></td>
                                    <td class="fw-semibold"><?= esc((string) $row->no_pembatalan) ?></td>
                                    <td>
                                        <a href="<?= esc(admin_page_url('keuangan/jurnal/detail') . '&id=' . (int) $row->id_jurnal_asal) ?>" class="text-decoration-none fw-semibold">
                                            <?= esc((string) $row->no_jurnal_asal) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php if (!empty($row->id_jurnal_reversal)): ?>
                                            <a href="<?= esc(admin_page_url('keuangan/jurnal/detail') . '&id=' . (int) $row->id_jurnal_reversal) ?>" class="text-decoration-none fw-semibold">
                                                <?= esc((string) ($row->no_jurnal_reversal ?? $row->no_jurnal_reversal_real ?? '-')) ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= keu_sumber_link($row->tabel_sumber ?? null, $row->id_sumber ?? null, $row->no_sumber ?? null) ?></td>
                                    <td><?= esc((string) $row->alasan_pembatalan) ?></td>
                                    <td class="text-end fw-semibold"><?= keu_uang($row->total_debit_asal ?? 0) ?></td>
                                    <td class="text-center">
                                        <a href="<?= esc(admin_page_url('keuangan/pembatalan-transaksi/detail') . '&id=' . (int) $row->id_pembatalan_transaksi) ?>" class="btn btn-sm btn-outline-info">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <?php if ($totalPages > 1): ?>
            <nav class="mt-3">
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= esc(keu_pagination_url('keuangan/pembatalan-transaksi', max(1, $page - 1))) ?>">Prev</a>
                    </li>

                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="<?= esc(keu_pagination_url('keuangan/pembatalan-transaksi', $i)) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= esc(keu_pagination_url('keuangan/pembatalan-transaksi', min($totalPages, $page + 1))) ?>">Next</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="modalPembatalan" tabindex="-1" aria-labelledby="modalPembatalanLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="post" action="<?= esc(admin_page_url('keuangan/pembatalan-transaksi/proses')) ?>" class="modal-content border-0 rounded-4 overflow-hidden" id="formPembatalan">
            <div class="modal-header rev-modal-head border-0">
                <div>
                    <h5 class="modal-title fw-bold" id="modalPembatalanLabel">Konfirmasi Pembatalan Transaksi</h5>
                    <div class="small opacity-75">Sistem akan membuat jurnal reversal otomatis.</div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <div class="modal-body p-4">
                <input type="hidden" name="id_jurnal" id="modal_id_jurnal">

                <div class="alert alert-warning">
                    <strong>Perhatian:</strong> pembatalan tidak menghapus transaksi asli. Jurnal asal tetap tersimpan, lalu sistem membuat jurnal pembalik.
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <div class="rev-info-box">
                            <div class="rev-info-label">No Jurnal</div>
                            <div class="rev-info-value" id="modal_no_jurnal">-</div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="rev-info-box">
                            <div class="rev-info-label">Tanggal Jurnal</div>
                            <div class="rev-info-value" id="modal_tanggal_jurnal">-</div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="rev-info-box">
                            <div class="rev-info-label">Nominal</div>
                            <div class="rev-info-value" id="modal_nominal">-</div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="rev-info-box">
                            <div class="rev-info-label">Jenis Transaksi</div>
                            <div class="rev-info-value" id="modal_jenis_transaksi">-</div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="rev-info-box">
                            <div class="rev-info-label">No Sumber</div>
                            <div class="rev-info-value" id="modal_no_sumber">-</div>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="rev-info-box">
                            <div class="rev-info-label">Keterangan Jurnal</div>
                            <div class="rev-info-value" id="modal_keterangan">-</div>
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Tanggal Pembatalan <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_pembatalan" id="modal_tanggal_pembatalan" class="form-control" required>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Alasan Pembatalan <span class="text-danger">*</span></label>
                        <input type="text" name="alasan" id="modal_alasan" class="form-control" placeholder="Contoh: Salah input nominal / transaksi dibatalkan pelanggan" required>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-0 bg-light">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    Tutup
                </button>

                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>Batalkan & Buat Reversal
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const modal = document.getElementById('modalPembatalan');

    if (!modal) {
        return;
    }

    document.querySelectorAll('.btn-open-batal').forEach(function (button) {
        button.addEventListener('click', function () {
            let payload = {};

            try {
                payload = JSON.parse(this.getAttribute('data-payload') || '{}');
            } catch (e) {
                payload = {};
            }

            document.getElementById('modal_id_jurnal').value = payload.id_jurnal || '';
            document.getElementById('modal_tanggal_pembatalan').value = payload.tanggal_pembatalan || '<?= esc(date('Y-m-d')) ?>';
            document.getElementById('modal_alasan').value = '';

            document.getElementById('modal_no_jurnal').textContent = payload.no_jurnal || '-';
            document.getElementById('modal_tanggal_jurnal').textContent = payload.tanggal_jurnal || '-';
            document.getElementById('modal_nominal').textContent = payload.nominal || '-';
            document.getElementById('modal_jenis_transaksi').textContent = payload.kode_jenis_transaksi || '-';
            document.getElementById('modal_no_sumber').textContent = payload.no_sumber || '-';
            document.getElementById('modal_keterangan').textContent = payload.keterangan || '-';
        });
    });

    document.getElementById('formPembatalan').addEventListener('submit', function (e) {
        const alasan = document.getElementById('modal_alasan').value.trim();
        const tanggal = document.getElementById('modal_tanggal_pembatalan').value.trim();

        if (tanggal === '') {
            e.preventDefault();
            alert('Tanggal pembatalan wajib diisi.');
            return;
        }

        if (alasan === '') {
            e.preventDefault();
            alert('Alasan pembatalan wajib diisi.');
            return;
        }

        if (!confirm('Batalkan transaksi posted ini dan buat jurnal reversal?')) {
            e.preventDefault();
        }
    });
})();
</script>