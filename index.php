<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers/config.php';
require_once __DIR__ . '/helpers/koneksi.php';
require_once __DIR__ . '/helpers/fungsi.php';

require_once __DIR__ . '/orm/RoleORM.php';
require_once __DIR__ . '/orm/EntitasORM.php';
require_once __DIR__ . '/orm/PenggunaORM.php';
require_once __DIR__ . '/orm/PenggunaEntitasORM.php';
require_once __DIR__ . '/orm/MenuORM.php';
require_once __DIR__ . '/orm/RoleMenuORM.php';
require_once __DIR__ . '/helpers/auth.php';
require_once __DIR__ . '/helpers/menu.php';
require_once __DIR__ . '/helpers/render.php';

use Illuminate\Database\Capsule\Manager as Capsule;

if (is_login()) {
    redirect_admin(halaman_awal_role());
}

$show_login = isset($_GET['login']) && (string) $_GET['login'] === '1';

if ($show_login) {
    $page_title = 'Login Pengguna Sistem';
    render_guest(__DIR__ . '/administrator/auth/login.php', compact('page_title'));
    exit;
}

if (!function_exists('landing_money')) {
    function landing_money($value): string
    {
        return 'Rp ' . number_format((float) $value, 0, ',', '.');
    }
}

if (!function_exists('landing_img_url')) {
    function landing_img_url(?string $gambar): string
    {
        $gambar = trim((string) $gambar);
        if ($gambar === '') {
            return '';
        }
        if (preg_match('/^https?:\/\//i', $gambar)) {
            return $gambar;
        }
        $gambar = ltrim(str_replace('\\', '/', $gambar), '/');
        $candidates = [
            $gambar,
            'uploads/produk/' . basename($gambar),
            'upload/produk/' . basename($gambar),
            'assets/img/produk/' . basename($gambar),
            'assets/images/produk/' . basename($gambar),
            'administrator/uploads/produk/' . basename($gambar),
            'administrator/assets/img/produk/' . basename($gambar),
        ];
        foreach ($candidates as $path) {
            if (file_exists(__DIR__ . '/' . $path)) {
                return base_url($path);
            }
        }
        return base_url($gambar);
    }
}

$entitas = null;
$produk = collect();
$kategori = collect();
$totalProduk = 0;
$totalKategori = 0;
$totalStok = 0;

try {
    $entitas = Capsule::table('tb_entitas')
        ->where('status_aktif', 1)
        ->orderBy('id_entitas')
        ->first();

    $id_entitas = (int) ($entitas->id_entitas ?? 1);

    $produk = Capsule::table('tb_produk as p')
        ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'p.id_satuan')
        ->leftJoin('tb_kategori_produk as kp', 'kp.id_kategori_produk', '=', 'p.id_kategori_produk')
        ->leftJoin('tb_saldo_stok as ss', function ($join) use ($id_entitas) {
            $join->on('ss.id_referensi_barang', '=', 'p.id_produk')
                ->where('ss.jenis_barang', '=', 'produk')
                ->where('ss.id_entitas', '=', $id_entitas);
        })
        ->where('p.id_entitas', $id_entitas)
        ->where('p.status_produk', 1)
        ->whereIn('p.jenis_produk', ['barang_jadi', 'setengah_jadi', 'jasa', 'non_persediaan'])
        ->groupBy([
            'p.id_produk', 'p.kode_produk', 'p.nama_produk', 'p.harga_jual', 'p.gambar_produk',
            'p.berat_bersih', 'p.satuan_berat_bersih', 's.nama_satuan', 'kp.nama_kategori_produk'
        ])
        ->select([
            'p.id_produk', 'p.kode_produk', 'p.nama_produk', 'p.harga_jual', 'p.gambar_produk',
            'p.berat_bersih', 'p.satuan_berat_bersih', 's.nama_satuan', 'kp.nama_kategori_produk'
        ])
        ->selectRaw('COALESCE(SUM(ss.qty_saldo), 0) as stok_tersedia')
        ->orderBy('p.nama_produk')
        ->limit(12)
        ->get();

    $kategori = Capsule::table('tb_kategori_produk')
        ->where('id_entitas', $id_entitas)
        ->where('status_aktif', 1)
        ->orderBy('nama_kategori_produk')
        ->limit(4)
        ->get();

    $totalProduk = (int) Capsule::table('tb_produk')
        ->where('id_entitas', $id_entitas)
        ->where('status_produk', 1)
        ->count();

    $totalKategori = (int) Capsule::table('tb_kategori_produk')
        ->where('id_entitas', $id_entitas)
        ->where('status_aktif', 1)
        ->count();

    $totalStok = (int) Capsule::table('tb_saldo_stok')
        ->where('id_entitas', $id_entitas)
        ->where('jenis_barang', 'produk')
        ->sum('qty_saldo');
} catch (Throwable $e) {
    $entitas = (object) [
        'id_entitas' => 1,
        'nama_entitas' => APP_NAME,
        'jenis_usaha' => 'Produksi dan Penjualan',
        'alamat' => '-',
        'no_hp' => '-',
    ];
}

$idEntitas = (int) ($entitas->id_entitas ?? 1);
$namaEntitas = (string) ($entitas->nama_entitas ?? APP_NAME);
$jenisUsaha = (string) ($entitas->jenis_usaha ?? 'Produksi dan Penjualan');
$alamat = (string) ($entitas->alamat ?? '');
$noHp = (string) ($entitas->no_hp ?? '');
$flash_success = get_flash('success');
$flash_error = get_flash('error');
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($namaEntitas) ?> - Pesanan Online</title>
    <link rel="stylesheet" href="<?= esc(base_url('assets/css/landing.css')) ?>">

<style>
.lp-top-card{background:#fff;border:1px solid rgba(226,232,240,.9);border-radius:32px;padding:24px;box-shadow:0 24px 60px rgba(15,23,42,.10);transition:.2s ease;animation:lpFloat 5s ease-in-out infinite}.lp-top-card:hover{transform:translateY(-6px);box-shadow:0 30px 76px rgba(37,99,235,.16)}.lp-top-head h2{margin:12px 0 8px;font-size:34px;line-height:1.05}.lp-top-head p{color:#64748b;line-height:1.65}.lp-feature-products-wide{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.lp-mini-product-link{text-decoration:none;color:inherit;transition:.18s ease}.lp-mini-product-link:hover{transform:translateY(-4px)}.lp-mini-img img,.lp-product-img img{width:100%;height:100%;object-fit:cover;display:block}.lp-img-fallback{width:100%;height:100%;display:grid;place-items:center;font-size:34px;background:linear-gradient(135deg,#eef2ff,#fff7ed)}@keyframes lpFloat{50%{transform:translateY(10px)}}@media(max-width:900px){.lp-feature-products-wide{grid-template-columns:1fr}}

.lp-btn .btn-label{display:inline}.lp-btn .ico-only{font-size:17px}.lp-img-fallback{color:#6d5dfc;font-weight:900}.lp-img-fallback .fallback-name{display:block;font-size:12px;margin-top:6px}@media(max-width:640px){.lp-btn{padding:12px 13px}.lp-btn .btn-label{display:none}.lp-product-card .lp-btn{width:48px;height:48px;border-radius:16px;padding:0}.lp-img-fallback .fallback-name{display:none}.lp-feature-products-wide{grid-template-columns:repeat(2,minmax(0,1fr));}.lp-top-head h2{font-size:26px}}

/* Header consistency with Pesanan Online page */
.lp-navbar{min-height:72px}.lp-nav-inner{min-height:72px}.lp-brand span:not(.lp-logo){line-height:1.15}.lp-nav-actions .lp-btn{min-height:44px;border-radius:999px;box-shadow:0 10px 22px rgba(37,99,235,.12)}.lp-nav-actions .lp-btn-soft{background:#fff;border:1px solid rgba(79,70,229,.16);color:#1f2a44}.lp-nav-actions .lp-btn-primary{background:linear-gradient(135deg,#4f46e5,#2563eb)}.lp-nav-actions .lp-btn-orange{background:linear-gradient(135deg,#f97316,#ea580c)}.lp-btn .ico-only{width:18px;text-align:center;display:inline-block}.lp-nav-actions .btn-label{font-weight:900}@media(max-width:720px){.lp-nav-inner{align-items:center}.lp-nav-actions{display:flex;width:auto;gap:8px;flex-wrap:nowrap}.lp-nav-actions .lp-btn{width:44px;height:44px;padding:0}.lp-nav-actions .btn-label{display:none}.lp-nav-actions .ico-only{font-size:17px}}

/* =========================
   FOOTER BARU - TEMA LANDING PAGE
   ========================= */
.lp-footer{
    margin-top:48px;
    padding:0 0 28px;
    background:linear-gradient(180deg,#ffffff 0%,#f8fafc 100%);
    border-top:1px solid rgba(226,232,240,.95);
    color:#ffffff;
}

.lp-footer-shell{
    display:grid;
    grid-template-columns:1fr 1.15fr 1fr;
    gap:28px;
    align-items:center;
    background:
        radial-gradient(circle at 12% 10%, rgba(255,255,255,.18), transparent 28%),
        radial-gradient(circle at 92% 16%, rgba(249,115,22,.28), transparent 32%),
        linear-gradient(135deg,#4f46e5 0%, #2563eb 42%, #7c3aed 100%);
    border:1px solid rgba(255,255,255,.28);
    border-radius:30px;
    padding:32px 34px 22px;
    box-shadow:0 26px 70px rgba(37,99,235,.24);
    position:relative;
    overflow:hidden;
}

.lp-footer-shell::before{
    content:"";
    position:absolute;
    inset:auto -80px -120px auto;
    width:280px;
    height:280px;
    background:radial-gradient(circle,rgba(249,115,22,.35),transparent 68%);
    pointer-events:none;
}

.lp-footer-shell::after{
    content:"";
    position:absolute;
    inset:-90px auto auto -80px;
    width:260px;
    height:260px;
    background:radial-gradient(circle,rgba(255,255,255,.18),transparent 70%);
    pointer-events:none;
}

.lp-footer-contact,
.lp-footer-center,
.lp-footer-dev,
.lp-footer-copy{
    position:relative;
    z-index:1;
}

.lp-footer h4{
    margin:0 0 14px;
    color:#ffffff;
    font-size:14px;
    letter-spacing:.08em;
    text-transform:uppercase;
    font-weight:1000;
}

.lp-footer p{
    margin:6px 0;
    color:#eef2ff;
    line-height:1.55;
    font-size:13px;
}

.lp-footer a{
    color:#ffffff !important;
    font-weight:900;
    text-decoration:none;
}

.lp-footer a:hover{
    color:#ffedd5 !important;
    text-decoration:underline;
}

.lp-footer-center{
    text-align:center;
}

.lp-footer-logos{
    display:flex;
    justify-content:center;
    align-items:center;
    gap:18px;
    margin-bottom:14px;
}

.lp-footer-logo-apk{
    width:150px;
    height:150px;
    object-fit:contain;
    filter:drop-shadow(0 14px 24px rgba(15,23,42,.26));
}

.lp-footer-logo-poliban{
    width:130px;
    height:130px;
    object-fit:contain;
    background:transparent !important;
    border:none !important;
    border-radius:0 !important;
    padding:0 !important;
    box-shadow:none !important;
    filter:drop-shadow(0 12px 20px rgba(15,23,42,.22));
}

.lp-footer-x{
    font-size:34px;
}

.lp-footer-brand-title{
    color:#ffffff;
    font-size:28px;
    font-weight:1000;
    letter-spacing:.02em;
    line-height:1.08;
    text-shadow:0 8px 18px rgba(15,23,42,.22);
}

.lp-footer-tagline{
    color:#e0f2fe;
    font-size:13px;
    font-weight:900;
    margin-top:7px;
}

.lp-footer-line{
    display:flex;
    align-items:flex-start;
    gap:10px;
    margin:9px 0;
    color:#eef2ff;
    font-size:13px;
    line-height:1.5;
}

.lp-footer-icon{
    flex:0 0 30px;
    width:30px;
    height:30px;
    border-radius:999px;
    display:inline-grid;
    place-items:center;
    background:rgba(255,255,255,.18);
    color:#ffffff;
    font-size:11px;
    font-weight:1000;
    border:1px solid rgba(255,255,255,.30);
    box-shadow:0 8px 18px rgba(15,23,42,.12);
}

.lp-footer-copy{
    grid-column:1 / -1;
    border-top:1px solid rgba(255,255,255,.22);
    padding-top:15px;
    margin-top:6px;
    color:#e0e7ff;
    text-align:center;
    font-size:12px;
}

@media(max-width:900px){
    .lp-footer-shell{
        grid-template-columns:1fr;
        text-align:center;
        padding:28px 20px 20px;
    }

    .lp-footer-line{
        justify-content:center;
    }

    .lp-footer-logo-apk{
        width:104px;
        height:104px;
    }

    .lp-footer-logo-poliban{
        width:88px;
        height:88px;
    }

    .lp-footer-brand-title{
        font-size:24px;
    }
}

@media(max-width:520px){
    .lp-footer-shell{
        border-radius:24px;
    }

    .lp-footer-logos{
        gap:12px;
    }

    .lp-footer-logo-apk{
        width:88px;
        height:88px;
    }

    .lp-footer-logo-poliban{
        width:74px;
        height:74px;
    }

    .lp-footer-x{
        font-size:22px;
    }
}

</style>
    <link rel="icon" type="image/png" href="<?= esc(base_url('assets/logo/icon-logo-apk.png')) ?>">
</head>
<body class="landing-body">
    <header class="lp-navbar">
        <div class="lp-container lp-nav-inner">
            <a href="<?= esc(base_url('index.php')) ?>" class="lp-brand">
                <img class="lp-logo app-logo-img" src="<?= esc(base_url('assets/logo/icon-logo-apk.png')) ?>" alt="Dodol Kapuh Online">
                <span><?= esc($namaEntitas) ?><small>Pesanan Online</small></span>
            </a>
            <div class="lp-nav-actions">
                <a href="<?= esc(base_url('pesanan-online/index.php?entitas=' . $idEntitas)) ?>" class="lp-btn lp-btn-primary"><span class="ico-only">🛒</span><span class="btn-label">Pesanan Online</span></a>
                <a href="<?= esc(base_url('pesanan-online/cek.php?entitas=' . $idEntitas)) ?>" class="lp-btn lp-btn-soft"><span class="ico-only">🔎</span><span class="btn-label">Cek Pesanan</span></a>
                <a href="<?= esc(base_url('index.php?login=1')) ?>" class="lp-btn lp-btn-orange"><span class="ico-only">🔐</span><span class="btn-label">Login Sistem</span></a>
            </div>
        </div>
    </header>

    <?php if ($flash_success): ?><div class="lp-alert lp-alert-success"><?= esc($flash_success) ?></div><?php endif; ?>
    <?php if ($flash_error): ?><div class="lp-alert lp-alert-error"><?= esc($flash_error) ?></div><?php endif; ?>

    <main>
        <section class="lp-hero">
            <div class="lp-container lp-hero-grid">
                <div class="lp-hero-card">
                    <div class="lp-hero-content">
                        <span class="lp-badge">✨ Dodol Kandangan asli, nyaman dipesan online</span>
                        <h1 class="lp-title">Handak dodol nyaman? Pesan haja dari sini.</h1>
                        <p class="lp-desc">
                            Pilih dodol favorit pian, masukkan ke keranjang, lalu bayar lewat transfer atau QRIS. Kada perlu repot, pesanan pian langsung masuk ke sistem dan bisa dicek statusnya kapan saja.
                        </p>
                        <div class="lp-hero-buttons">
                            <a href="<?= esc(base_url('pesanan-online/index.php?entitas=' . $idEntitas)) ?>" class="lp-btn lp-btn-orange"><span class="ico-only">🛍️</span><span class="btn-label">Pesan Dodol Sekarang</span></a>
                            <a href="#produk" class="lp-btn lp-btn-soft"><span class="ico-only">👀</span><span class="btn-label">Lihat Pilihan Dodol</span></a>
                        </div>
                        <div class="lp-stats">
                            <div class="lp-stat"><strong><?= number_format($totalProduk, 0, ',', '.') ?></strong><span>Produk Aktif</span></div>
                            <div class="lp-stat"><strong><?= number_format($totalKategori, 0, ',', '.') ?></strong><span>Kategori</span></div>
                            <div class="lp-stat"><strong><?= number_format($totalStok, 0, ',', '.') ?></strong><span>Stok Produk</span></div>
                        </div>
                    </div>
                </div>

                <div class="lp-top-products">
                    <div class="lp-top-card">
                        <div class="lp-top-head">
                            <span class="lp-badge">Paling dicari</span>
                            <h2>Dodol favorit urang Kandangan</h2>
                            <p>Pilihan dodol manis, legit, dan cocok gasan oleh-oleh atau cemilan di rumah.</p>
                        </div>
                        <div class="lp-feature-products lp-feature-products-wide">
                            <?php foreach ($produk->take(4) as $item): ?>
                                <?php $img = landing_img_url((string) ($item->gambar_produk ?? '')); ?>
                                <a href="<?= esc(base_url('pesanan-online/index.php?entitas=' . $idEntitas)) ?>" class="lp-mini-product lp-mini-product-link">
                                    <div class="lp-mini-img"><?php if ($img): ?><img src="<?= esc($img) ?>" alt="<?= esc((string) $item->nama_produk) ?>" onerror="this.style.display='none';this.parentNode.querySelector('.lp-img-fallback').style.display='grid';"><span class="lp-img-fallback" style="display:none"><span>🍬</span><span class="fallback-name">Dodol</span></span><?php else: ?><span class="lp-img-fallback"><span>🍬</span><span class="fallback-name">Dodol</span></span><?php endif; ?></div>
                                    <div class="lp-mini-body">
                                        <div class="lp-mini-name"><?= esc((string) $item->nama_produk) ?></div>
                                        <div class="lp-mini-price"><?= esc(landing_money($item->harga_jual ?? 0)) ?></div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                            <?php if ($produk->count() === 0): ?>
                                <div class="lp-mini-product"><div class="lp-mini-img"><span class="lp-img-fallback"><span>🍬</span><span class="fallback-name">Dodol</span></span></div><div class="lp-mini-body"><div class="lp-mini-name">Produk belum tersedia</div><div class="lp-mini-price">Input dari menu Produk</div></div></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="lp-section">
            <div class="lp-container">
                <div class="lp-section-head">
                    <div>
                        <h2 class="lp-section-title">Alur halaman awal</h2>
                        <p class="lp-section-subtitle">Dibuat terpisah antara pelanggan dan pengguna sistem pihak usaha.</p>
                    </div>
                </div>
                <div class="lp-flow-grid">
                    <div class="lp-flow-card"><div class="lp-flow-icon">🛍️</div><h3>Pelanggan pesan online</h3><p>Pelanggan masuk ke halaman pesanan online, memilih produk, lalu mengirim pesanan tanpa masuk ke dashboard admin.</p></div>
                    <div class="lp-flow-card"><div class="lp-flow-icon">🧾</div><h3>Cek status pesanan</h3><p>Pelanggan dapat mengecek nomor pesanan melalui halaman cek pesanan yang sudah ada di aplikasi.</p></div>
                    <div class="lp-flow-card"><div class="lp-flow-icon">🔐</div><h3>Pengguna usaha login</h3><p>Admin, gudang, pembelian, penjualan, produksi, dan keuangan tetap login ke sistem internal.</p></div>
                </div>
            </div>
        </section>

        <section class="lp-section" id="produk">
            <div class="lp-container">
                <div class="lp-section-head">
                    <div>
                        <h2 class="lp-section-title">Daftar produk</h2>
                        <p class="lp-section-subtitle">Pilih produk favorit, lalu lanjutkan pemesanan online.</p>
                    </div>
                    <a href="<?= esc(base_url('pesanan-online/index.php?entitas=' . $idEntitas)) ?>" class="lp-btn lp-btn-primary"><span class="ico-only">🛒</span><span class="btn-label">Lihat Semua Produk</span></a>
                </div>
                <?php if ($produk->count() > 0): ?>
                    <div class="lp-product-grid">
                        <?php foreach ($produk as $item): ?>
                            <?php $img = landing_img_url((string) ($item->gambar_produk ?? '')); ?>
                            <article class="lp-product-card">
                                <div class="lp-product-img"><?php if ($img): ?><img src="<?= esc($img) ?>" alt="<?= esc((string) $item->nama_produk) ?>" onerror="this.style.display='none';this.parentNode.querySelector('.lp-img-fallback').style.display='grid';"><span class="lp-img-fallback" style="display:none"><span>🍬</span><span class="fallback-name">Dodol</span></span><?php else: ?><span class="lp-img-fallback"><span>🍬</span><span class="fallback-name">Dodol</span></span><?php endif; ?></div>
                                <div class="lp-product-body">
                                    <span class="lp-chip"><?= esc((string) ($item->nama_kategori_produk ?? 'Produk')) ?></span>
                                    <div class="lp-product-name"><?= esc((string) $item->nama_produk) ?></div>
                                    <div class="lp-product-meta"><span><?= esc((string) ($item->kode_produk ?? '-')) ?></span><span>Stok <?= number_format((float) ($item->stok_tersedia ?? 0), 0, ',', '.') ?></span></div>
                                    <div class="lp-product-price"><?= esc(landing_money($item->harga_jual ?? 0)) ?></div>
                                    <a href="<?= esc(base_url('pesanan-online/index.php?entitas=' . $idEntitas)) ?>" class="lp-btn lp-btn-primary"><span class="ico-only">🛒</span><span class="btn-label">Tambah ke Pesanan</span></a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="lp-empty">Belum ada produk aktif. Setelah produk diinput pada menu Produk, daftar barang akan muncul otomatis di halaman ini.</div>
                <?php endif; ?>
            </div>
        </section>

        <section class="lp-login-strip" id="akses">
            <div class="lp-container lp-login-grid">
                <div class="lp-access-card">
                    <div class="lp-access-icon">👤</div>
                    <div><h3>Akses pelanggan</h3><p>Untuk pelanggan yang ingin membuat pesanan atau mengecek status pesanan online.</p><a href="<?= esc(base_url('pesanan-online/index.php?entitas=' . $idEntitas)) ?>" class="lp-btn lp-btn-primary">Buka Pesanan Online</a></div>
                </div>
                <div class="lp-access-card">
                    <div class="lp-access-icon">🔒</div>
                    <div><h3>Login pengguna sistem</h3><p>Untuk pihak usaha/admin yang mengelola pembelian, penjualan, produksi, gudang, keuangan, dan laporan.</p><a href="<?= esc(base_url('index.php?login=1')) ?>" class="lp-btn lp-btn-orange">Login ke Sistem</a></div>
                </div>
            </div>
        </section>
    </main>

    <?php
$waUmkm = preg_replace('/[^0-9]/', '', str_replace('+', '', (string) $noHp));
if ($waUmkm !== '' && substr($waUmkm, 0, 1) === '0') {
    $waUmkm = '62' . substr($waUmkm, 1);
}
?>
<footer class="lp-footer">
    <div class="lp-container lp-footer-shell">

        <div class="lp-footer-contact">
            <h4>Informasi UMKM</h4>
            <p><strong><?= esc($namaEntitas) ?></strong></p>

            <?php if ($alamat !== ''): ?>
                <div class="lp-footer-line">
                    <span class="lp-footer-icon">📍</span>
                    <span><?= esc($alamat) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($noHp !== ''): ?>
                <div class="lp-footer-line">
                    <span class="lp-footer-icon">WA</span>
                    <a href="https://wa.me/<?= esc($waUmkm) ?>" target="_blank" rel="noopener">
                        <?= esc($noHp) ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <div class="lp-footer-center">
            <div class="lp-footer-logos">
                <img class="lp-footer-logo-apk" src="<?= esc(base_url('assets/logo/icon-logo-apk.png')) ?>" alt="Dodol Kapuh Online">
                <span class="lp-footer-x">×</span>
                <img class="lp-footer-logo-poliban" src="<?= esc(base_url('assets/logo/icon-logo-poliban.png')) ?>" alt="Politeknik Negeri Banjarmasin">
            </div>
            <div class="lp-footer-brand-title">Dodol Kapuh Online</div>
            <div class="lp-footer-tagline">Pengabdian Masyarakat • Digitalisasi UMKM • Pemasaran Online</div>
        </div>

        <div class="lp-footer-dev">
            <h4>Pengembang</h4>
            <p><strong>Ihya - D020323006</strong></p>
            <p>Mahasiswa D3 Sistem Informasi Akuntansi<br>POLITEKNIK NEGERI BANJARMASIN</p>
            <p>Pelaksana Pengabdian Masyarakat – Digitalisasi UMKM Berkat Guru Kapuh</p>

            <div class="lp-footer-line">
                <span class="lp-footer-icon">WA</span>
                <a href="https://wa.me/62895325944002" target="_blank" rel="noopener">
                    +62 895-3259-44002
                </a>
            </div>

            <div class="lp-footer-line">
                <span class="lp-footer-icon">IG</span>
                <a href="https://www.instagram.com/nanaomissu" target="_blank" rel="noopener">
                    nanaomissu
                </a>
            </div>

            <div class="lp-footer-line">
                <span class="lp-footer-icon">@</span>
                <a href="mailto:winter.blue215@gmail.com">
                    winter.blue215@gmail.com
                </a>
            </div>
        </div>

        <div class="lp-footer-copy">
            © <?= date('Y') ?> <?= esc($namaEntitas) ?>. Dikembangkan sebagai bagian dari Program Pengabdian Masyarakat Politeknik Negeri Banjarmasin untuk mendukung digitalisasi pencatatan keuangan dan pemasaran online UMKM Berkat Guru Kapuh.
        </div>

    </div>
</footer>

</body>
</html>
