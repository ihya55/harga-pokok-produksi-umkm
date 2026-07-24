<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

$user_login = user_login();
$id_entitas_login = (int) ($user_login['id_entitas'] ?? 0);

if (!function_exists('entitas_detail_is_super_admin')) {
    function entitas_detail_is_super_admin(array $user_login): bool
    {
        $username = strtolower(trim((string) ($user_login['username'] ?? '')));
        $nama_role_session = strtolower(trim((string) ($user_login['nama_role'] ?? $user_login['role'] ?? '')));

        if ($username === 'super_admin' || $nama_role_session === 'super_admin') {
            return true;
        }

        $id_role = (int) ($user_login['id_role'] ?? 0);

        if ($id_role > 0) {
            $role = Capsule::table('tb_role')
                ->where('id_role', $id_role)
                ->first();

            if ($role) {
                $nama_role = strtolower(trim((string) ($role->nama_role ?? '')));
                $kode_role = strtolower(trim((string) ($role->kode_role ?? '')));

                return $nama_role === 'super_admin' || $kode_role === 'super_admin';
            }
        }

        return false;
    }
}

$is_super_admin = entitas_detail_is_super_admin($user_login);

$id_entitas = (int) ($_GET['id'] ?? 0);

if (!$is_super_admin) {
    $id_entitas = $id_entitas_login;
}

$row = Capsule::table('tb_entitas')
    ->where('id_entitas', $id_entitas)
    ->first();

if (!$row) {
    ?>
    <div class="alert alert-danger">Data entitas tidak ditemukan atau kamu tidak punya akses.</div>
    <a href="<?= esc(admin_page_url('master/entitas')) ?>" class="btn btn-outline-secondary">Kembali</a>
    <?php
    return;
}

$jumlah_pengguna = (int) Capsule::table('tb_pengguna')
    ->where('id_entitas', $id_entitas)
    ->count();

$jumlah_akses = (int) Capsule::table('tb_pengguna_entitas')
    ->where('id_entitas', $id_entitas)
    ->count();

$jumlah_gudang = (int) Capsule::table('tb_gudang')
    ->where('id_entitas', $id_entitas)
    ->count();
?>

<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h1 class="page-title">Detail Entitas</h1>
            <p class="page-subtitle">Informasi entitas dan ringkasan pemakaiannya</p>
        </div>

        <div class="d-flex gap-2">
            <a href="<?= esc(admin_page_url('master/entitas')) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>

            <a href="<?= esc(admin_page_url('master/entitas/edit') . '&id=' . (int) $row->id_entitas) ?>" class="btn btn-primary">
                <i class="bi bi-pencil-square me-1"></i>Edit
            </a>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Pengguna Utama</div>
                <div class="h4 mb-0"><?= number_format($jumlah_pengguna, 0, '.', ',') ?></div>
                <div class="text-muted small">tb_pengguna.id_entitas</div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Akses Pengguna</div>
                <div class="h4 mb-0"><?= number_format($jumlah_akses, 0, '.', ',') ?></div>
                <div class="text-muted small">tb_pengguna_entitas</div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Gudang</div>
                <div class="h4 mb-0"><?= number_format($jumlah_gudang, 0, '.', ',') ?></div>
                <div class="text-muted small">Master gudang entitas</div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h2 class="h5 mb-3">Informasi Entitas</h2>

        <table class="table table-sm table-borderless align-middle mb-0">
            <tr>
                <th width="180" class="text-muted">Kode Entitas</th>
                <td class="fw-semibold"><?= esc((string) $row->kode_entitas) ?></td>
            </tr>
            <tr>
                <th class="text-muted">Nama Entitas</th>
                <td class="fw-semibold"><?= esc((string) $row->nama_entitas) ?></td>
            </tr>
            <tr>
                <th class="text-muted">Pemilik</th>
                <td><?= esc((string) ($row->nama_pemilik ?? '-')) ?></td>
            </tr>
            <tr>
                <th class="text-muted">Jenis Usaha</th>
                <td><?= esc((string) ($row->jenis_usaha ?? '-')) ?></td>
            </tr>
            <tr>
                <th class="text-muted">No HP</th>
                <td><?= esc((string) ($row->no_hp ?? '-')) ?></td>
            </tr>
            <tr>
                <th class="text-muted">Email</th>
                <td><?= esc((string) ($row->email ?? '-')) ?></td>
            </tr>
            <tr>
                <th class="text-muted">Alamat</th>
                <td><?= nl2br(esc((string) ($row->alamat ?? '-'))) ?></td>
            </tr>
            <tr>
                <th class="text-muted">Status</th>
                <td>
                    <?= ((int) $row->status_aktif === 1)
                        ? '<span class="badge bg-success">Aktif</span>'
                        : '<span class="badge bg-secondary">Nonaktif</span>' ?>
                </td>
            </tr>
            <tr>
                <th class="text-muted">Dibuat</th>
                <td><?= esc($row->tanggal_dibuat ? date('d/m/Y H:i', strtotime((string) $row->tanggal_dibuat)) : '-') ?></td>
            </tr>
            <tr>
                <th class="text-muted">Diubah</th>
                <td><?= esc($row->tanggal_diubah ? date('d/m/Y H:i', strtotime((string) $row->tanggal_diubah)) : '-') ?></td>
            </tr>
        </table>
    </div>
</div>