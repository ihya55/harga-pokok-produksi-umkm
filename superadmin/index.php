<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';
use Illuminate\Database\Capsule\Manager as Capsule;

if (isset($_GET['logout'])) { logout_user(); redirect_url(sa_url('index.php?login=1')); }
$error = '';
if (!is_login() && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    if (login_user($username, $password) && sa_is_allowed()) { redirect_url(sa_url('index.php')); }
    logout_user();
    $error = 'Login gagal atau akun tidak memiliki akses superadmin.';
}
if (!is_login() || isset($_GET['login'])):
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Login Superadmin</title><link rel="stylesheet" href="<?= sa_esc(sa_url('assets/superadmin.css')) ?>"></head><body><div class="login"><form class="login-card" method="post"><div class="sa-brand" style="color:#0f172a"><div class="sa-logo" style="color:#fff">SA</div><div><h1>Superadmin Pembuat Aplikasi</h1><small style="color:#64748b">Panel khusus pengaturan sistem</small></div></div><?php if($error): ?><div class="alert"><?= sa_esc($error) ?></div><?php endif; ?><div class="field"><label>Username</label><input name="username" required autofocus></div><div class="field"><label>Password</label><input name="password" type="password" required></div><button class="btn btn-primary" style="width:100%;justify-content:center">Masuk Superadmin</button><p class="muted" style="font-size:13px">Gunakan akun pembuat aplikasi/superadmin. Panel ini terpisah dari tampilan UMKM.</p></form></div></body></html>
<?php exit; endif; sa_require();
$entitas = sa_first_entitas();
$users = Capsule::table('tb_pengguna as p')->leftJoin('tb_role as r','r.id_role','=','p.id_role')->select('p.*','r.nama_role')->orderBy('p.id_pengguna')->limit(8)->get();
$entities = Capsule::table('tb_entitas')->orderBy('id_entitas')->limit(8)->get();
$stats = [
    ['UMKM/Entitas', sa_count('tb_entitas'), '🏪'],
    ['Pengguna', sa_count('tb_pengguna'), '👥'],
    ['Menu Sistem', sa_count('tb_menu'), '🧭'],
    ['Jurnal', sa_count('tb_jurnal'), '📒'],
];
$quick = [
    ['Kelola UMKM/Entitas', 'master_setup/entitas', 'Atur nama UMKM, alamat, telepon, logo, dan status.'],
    ['Kelola Pengguna', 'master_setup/pengguna', 'Tambah admin UMKM, reset password, aktif/nonaktif pengguna.'],
    ['Kelola Role dan Akses Menu', 'master_setup/role', 'Atur hak akses per jenis admin.'],
    ['Mapping Akun', 'master_setup/mapping_akun', 'Atur akun otomatis untuk penjualan, pembelian, produksi, kas, QRIS.'],
    ['COA / Akun Keuangan', 'master_setup/coa', 'Atur kode akun untuk laporan akuntansi.'],
    ['Reset Data Percobaan', 'master_setup/reset_data', 'Bersihkan data dummy tanpa menghapus master utama.'],
];
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Superadmin</title><link rel="stylesheet" href="<?= sa_esc(sa_url('assets/superadmin.css')) ?>"></head><body><div class="sa-wrap"><aside class="sa-side"><div class="sa-brand"><div class="sa-logo">SA</div><div><h1>Superadmin</h1><small>Pembuat Aplikasi HPP</small></div></div><nav class="sa-nav"><a class="active" href="<?= sa_esc(sa_url('index.php')) ?>">🏠 Dashboard</a><a href="<?= sa_esc(sa_admin_url('master_setup/entitas')) ?>">🏪 UMKM/Entitas</a><a href="<?= sa_esc(sa_admin_url('master_setup/pengguna')) ?>">👥 Pengguna</a><a href="<?= sa_esc(sa_admin_url('master_setup/coa')) ?>">📘 COA</a><a href="<?= sa_esc(sa_admin_url('master_setup/mapping_akun')) ?>">⚙️ Mapping Akun</a><a href="<?= sa_esc(sa_admin_url('keuangan/jurnal')) ?>">📒 Jurnal</a><a href="<?= sa_esc(base_url('administrator/index.php')) ?>">↩️ Tampilan UMKM</a><a href="<?= sa_esc(sa_url('index.php?logout=1')) ?>">🚪 Logout</a></nav></aside><main class="sa-main"><div class="sa-top"><div class="sa-title"><h2>Dashboard Superadmin</h2><p>Panel ini dibuat terpisah dari folder administrator, jadi tampilan UMKM tidak terganggu.</p></div><div class="sa-actions"><a class="btn" href="<?= sa_esc(base_url('index.php')) ?>" target="_blank">🌐 Lihat Website</a><a class="btn btn-primary" href="<?= sa_esc(sa_admin_url('dashboard')) ?>">Masuk Admin UMKM</a></div></div><div class="grid grid-4"><?php foreach($stats as $s): ?><div class="card stat"><div class="label"><?= sa_esc($s[2].' '.$s[0]) ?></div><div class="num"><?= sa_esc($s[1]) ?></div></div><?php endforeach; ?></div><div class="grid grid-3" style="margin-top:16px"><section class="card" style="grid-column:span 2"><h3>Pengaturan Cepat</h3><div class="quick"><?php foreach($quick as $q): ?><a href="<?= sa_esc(sa_admin_url($q[1])) ?>"><span><strong><?= sa_esc($q[0]) ?></strong><br><small class="muted"><?= sa_esc($q[2]) ?></small></span><b>›</b></a><?php endforeach; ?></div></section><section class="card"><h3>Identitas UMKM Aktif</h3><?php if($entitas): ?><p><strong><?= sa_esc($entitas->nama_entitas) ?></strong></p><p class="muted"><?= nl2br(sa_esc($entitas->alamat ?? '-')) ?><br>Telp/WA: <?= sa_esc($entitas->no_hp ?? '-') ?><br>Email: <?= sa_esc($entitas->email ?? '-') ?></p><a class="btn btn-primary" href="<?= sa_esc(sa_admin_url('master_setup/entitas/edit').'&id='.(int)$entitas->id_entitas) ?>">Edit Identitas</a><?php else: ?><p class="muted">Data entitas belum tersedia.</p><?php endif; ?></section></div><div class="grid grid-3" style="margin-top:16px"><section class="card" style="grid-column:span 2"><h3>Pengguna Sistem</h3><div style="overflow:auto"><table><thead><tr><th>Nama</th><th>Username</th><th>Role</th><th>Status</th></tr></thead><tbody><?php foreach($users as $u): ?><tr><td><?= sa_esc($u->nama_lengkap) ?></td><td><?= sa_esc($u->username) ?></td><td><?= sa_esc($u->nama_role) ?></td><td><span class="badge <?= ((int)$u->status_aktif===1?'on':'off') ?>"><?= ((int)$u->status_aktif===1?'Aktif':'Nonaktif') ?></span></td></tr><?php endforeach; ?></tbody></table></div></section><section class="card"><h3>Daftar UMKM</h3><div style="overflow:auto"><table><thead><tr><th>Nama</th><th>Status</th></tr></thead><tbody><?php foreach($entities as $e): ?><tr><td><?= sa_esc($e->nama_entitas) ?></td><td><span class="badge <?= ((int)$e->status_aktif===1?'on':'off') ?>"><?= ((int)$e->status_aktif===1?'Aktif':'Nonaktif') ?></span></td></tr><?php endforeach; ?></tbody></table></div></section></div></main></div></body></html>
