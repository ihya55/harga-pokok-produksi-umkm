<div class="login-wrapper">
    <div class="card border-0 shadow-lg login-card">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <div class="login-logo mb-3"><img src="<?= esc(base_url('assets/logo/icon-logo-apk.png')) ?>" alt="Dodol Kapuh Online"></div>
                <h1 class="h4 fw-bold mb-1"><?= esc(APP_NAME) ?></h1>
                <p class="text-muted mb-0">Silakan masuk ke sistem</p>
            </div>

            <form method="post" action="<?= esc(admin_url('auth/proses_login.php')) ?>">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Username</label>
                    <input type="text" name="username" class="form-control form-control-lg" required autofocus>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Password</label>
                    <input type="password" name="password" class="form-control form-control-lg" required>
                </div>

                <button type="submit" class="btn btn-gradient w-100 btn-lg">
                    Masuk
                </button>
            </form>
        </div>
    </div>
</div>