<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/kode.php';

require_once __DIR__ . '/../../../../orm/ResepORM.php';
require_once __DIR__ . '/../../../../orm/ResepDetailORM.php';
require_once __DIR__ . '/../../../../orm/ProdukORM.php';
require_once __DIR__ . '/../../../../orm/BahanBakuORM.php';
require_once __DIR__ . '/../../../../orm/SatuanORM.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_admin('master_setup/resep');
}

$id_entitas    = (int) (user_login()['id_entitas'] ?? 0);
$id_pengguna   = (int) (user_login()['id_pengguna'] ?? 0);
$id_produk     = (int) ($_POST['id_produk'] ?? 0);
$nama_resep    = trim((string) ($_POST['nama_resep'] ?? ''));
$jumlah_hasil  = (float) ($_POST['jumlah_hasil'] ?? 0);
$versi_resep   = trim((string) ($_POST['versi_resep'] ?? ''));
$status_aktif  = (int) ($_POST['status_aktif'] ?? 1);
$detail        = $_POST['detail'] ?? [];

if ($id_produk <= 0 || !ProdukORM::query()->where('id_entitas', $id_entitas)->where('id_produk', $id_produk)->exists()) {
    set_flash('error', 'Produk tidak valid.');
    redirect_admin('master_setup/resep/tambah');
}

if ($nama_resep === '') {
    set_flash('error', 'Nama resep wajib diisi.');
    redirect_admin('master_setup/resep/tambah');
}

if ($jumlah_hasil <= 0) {
    set_flash('error', 'Jumlah hasil wajib lebih besar dari 0.');
    redirect_admin('master_setup/resep/tambah');
}

if (!is_array($detail) || count($detail) === 0) {
    set_flash('error', 'Minimal harus ada 1 detail bahan resep.');
    redirect_admin('master_setup/resep/tambah');
}

try {
    Capsule::connection()->transaction(function () use ($id_entitas, $id_pengguna, $id_produk, $nama_resep, $jumlah_hasil, $versi_resep, $status_aktif, $detail) {
        $kode_resep = generate_kode_master('tb_resep', 'kode_resep', 'RSP', 4, $id_entitas);

        $header = ResepORM::create([
            'id_entitas'      => $id_entitas,
            'id_produk'       => $id_produk,
            'kode_resep'      => $kode_resep,
            'nama_resep'      => $nama_resep,
            'jumlah_hasil'    => $jumlah_hasil,
            'versi_resep'     => $versi_resep !== '' ? $versi_resep : null,
            'status_aktif'    => $status_aktif,
            'tanggal_dibuat'  => date('Y-m-d H:i:s'),
            'dibuat_oleh'     => $id_pengguna > 0 ? $id_pengguna : null,
            'tanggal_diubah'  => null,
            'diubah_oleh'     => null,
        ]);

        foreach ($detail as $baris) {
            $id_bahan_baku = (int) ($baris['id_bahan_baku'] ?? 0);
            $jumlah_pakai  = (float) ($baris['jumlah_pakai'] ?? 0);
            $id_satuan     = (int) ($baris['id_satuan'] ?? 0);
            $keterangan    = trim((string) ($baris['keterangan'] ?? ''));

            if ($id_bahan_baku <= 0 || !BahanBakuORM::query()->where('id_entitas', $id_entitas)->where('id_bahan_baku', $id_bahan_baku)->exists()) {
                throw new RuntimeException('Bahan baku tidak valid.');
            }

            if ($jumlah_pakai <= 0) {
                throw new RuntimeException('Jumlah pakai wajib lebih besar dari 0.');
            }

            if ($id_satuan <= 0 || !SatuanORM::query()->where('id_satuan', $id_satuan)->exists()) {
                throw new RuntimeException('Satuan tidak valid.');
            }

            ResepDetailORM::create([
                'id_resep'       => $header->id_resep,
                'id_bahan_baku'  => $id_bahan_baku,
                'jumlah_pakai'   => $jumlah_pakai,
                'id_satuan'      => $id_satuan,
                'keterangan'     => $keterangan !== '' ? $keterangan : null,
            ]);
        }
    });

    set_flash('success', 'Data resep berhasil disimpan.');
    redirect_admin('master_setup/resep');
} catch (Throwable $e) {
    set_flash('error', $e->getMessage());
    redirect_admin('master_setup/resep/tambah');
}