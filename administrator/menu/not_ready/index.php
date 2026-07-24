<div class="page-header mb-4">
    <h1 class="page-title"><?= esc($page_title ?? 'Halaman') ?></h1>
    <p class="page-subtitle">Halaman ini sudah aktif di sistem</p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="mb-3">
            <span class="badge rounded-pill text-bg-success px-3 py-2">
                <i class="bi bi-check-circle-fill me-1"></i>Menu sudah aktif
            </span>
        </div>

        <p class="mb-2">
            Halaman ini sudah terdaftar di menu dan sudah bisa diakses sesuai role.
        </p>
        <p class="mb-3 text-muted">
            Tetapi detail coding, form, proses simpan, edit, hapus, dan tampil datanya belum dibuat pada tahap ini.
        </p>

        <div class="alert alert-warning mb-0">
            Menu aktif sekarang: <strong><?= esc($menu_aktif ?? '-') ?></strong>
        </div>
    </div>
</div>