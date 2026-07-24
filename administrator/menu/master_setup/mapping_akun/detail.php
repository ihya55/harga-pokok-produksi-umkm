<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

require_once __DIR__ . '/helpers_mapping_akun.php';

$id_entitas = mapping_akun_id_entitas();
$id_mapping_akun = (int) ($_GET['id'] ?? 0);

$row = Capsule::table('tb_mapping_akun as m')
    ->leftJoin('tb_entitas as e', 'e.id_entitas', '=', 'm.id_entitas')
    ->leftJoin('tb_coa as c', 'c.id_coa', '=', 'm.id_coa')
    ->leftJoin('tb_pengguna as u1', 'u1.id_pengguna', '=', 'm.dibuat_oleh')
    ->leftJoin('tb_pengguna as u2', 'u2.id_pengguna', '=', 'm.diubah_oleh')
    ->where('m.id_entitas', $id_entitas)
    ->where('m.id_mapping_akun', $id_mapping_akun)
    ->select([
        'm.*',
        'e.nama_entitas',
        'c.kode_coa',
        'c.nama_coa',
        'c.kategori_coa',
        'u1.nama_lengkap as nama_pembuat',
        'u2.nama_lengkap as nama_pengubah',
    ])
    ->first();

if (!$row) {
    set_flash('error', 'Data mapping akun tidak ditemukan.');
    redirect_admin('master_setup/mapping_akun');
}
?>

<div class="page-header mb-4">
    <h1 class="page-title">Detail Mapping Akun</h1>
    <p class="page-subtitle">Informasi lengkap konfigurasi mapping akun transaksi dan laporan</p>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
            <div>
                <h2 class="h5 mb-1">Data Utama</h2>
                <div class="text-muted small">Detail konfigurasi mapping akun</div>
            </div>

            <div class="d-flex gap-2">
                <a href="<?= esc(admin_page_url('master_setup/mapping_akun')) ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Kembali
                </a>

                <a href="<?= esc(admin_page_url('master_setup/mapping_akun/edit') . '&id=' . (int) $row->id_mapping_akun) ?>" class="btn btn-gradient">
                    <i class="bi bi-pencil-square me-1"></i>Edit
                </a>
            </div>
        </div>

        <div class="table-responsive border rounded">
            <table class="table table-bordered align-middle mb-0">
                <tbody>
                    <tr>
                        <th width="220" class="table-light">Entitas</th>
                        <td><?= esc((string) ($row->nama_entitas ?? '-')) ?></td>
                    </tr>
                    <tr>
                        <th class="table-light">Jenis Transaksi</th>
                        <td>
                            <div class="fw-semibold"><?= esc((string) $row->kode_jenis_transaksi) ?></div>
                            <div class="text-muted small"><?= esc(label_jenis_transaksi_mapping_akun($row->kode_jenis_transaksi)) ?></div>
                        </td>
                    </tr>
                    <tr>
                        <th class="table-light">Jenis Objek</th>
                        <td>
                            <div class="fw-semibold"><?= esc(label_jenis_objek_mapping_akun($row->jenis_objek)) ?></div>
                            <div class="text-muted small"><?= esc((string) $row->jenis_objek) ?></div>
                        </td>
                    </tr>
                    <tr>
                        <th class="table-light">Objek</th>
                        <td><?= esc(mapping_akun_nama_objek((string) $row->jenis_objek, (int) $row->id_objek, $id_entitas)) ?></td>
                    </tr>
                    <tr>
                        <th class="table-light">Peran Akun</th>
                        <td>
                            <div class="fw-semibold"><?= esc(label_peran_akun_mapping($row->peran_akun)) ?></div>
                            <div class="text-muted small"><?= esc((string) $row->peran_akun) ?></div>
                        </td>
                    </tr>
                    <tr>
                        <th class="table-light">COA</th>
                        <td>
                            <div class="fw-semibold"><?= esc(($row->kode_coa ?? '-') . ' - ' . ($row->nama_coa ?? '-')) ?></div>
                            <div class="text-muted small"><?= esc((string) ($row->kategori_coa ?? '-')) ?></div>
                        </td>
                    </tr>
                    <tr>
                        <th class="table-light">Status</th>
                        <td><?= mapping_akun_status_badge($row->status_aktif) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h2 class="h5 mb-3">Informasi Audit</h2>

        <div class="table-responsive border rounded">
            <table class="table table-bordered align-middle mb-0">
                <tbody>
                    <tr>
                        <th width="220" class="table-light">Tanggal Dibuat</th>
                        <td><?= esc(!empty($row->tanggal_dibuat) ? date('d/m/Y H:i', strtotime((string) $row->tanggal_dibuat)) : '-') ?></td>
                    </tr>
                    <tr>
                        <th class="table-light">Dibuat Oleh</th>
                        <td><?= esc((string) ($row->nama_pembuat ?? '-')) ?></td>
                    </tr>
                    <tr>
                        <th class="table-light">Tanggal Diubah</th>
                        <td><?= esc(!empty($row->tanggal_diubah) ? date('d/m/Y H:i', strtotime((string) $row->tanggal_diubah)) : '-') ?></td>
                    </tr>
                    <tr>
                        <th class="table-light">Diubah Oleh</th>
                        <td><?= esc((string) ($row->nama_pengubah ?? '-')) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>