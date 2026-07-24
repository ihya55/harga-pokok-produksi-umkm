<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../orm/KonfigurasiAkunORM.php';
require_once __DIR__ . '/../../../../orm/CoaORM.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_admin('master_setup/konfigurasi_akun');
}

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_pengguna = (int) (user_login()['id_pengguna'] ?? 0);
$konfigurasi = $_POST['konfigurasi'] ?? [];
$konfigurasiKode = $_POST['konfigurasi_kode'] ?? [];
$tab = trim((string) ($_POST['tab'] ?? 'semua'));
$q = trim((string) ($_POST['q'] ?? ''));

$redirectParams = ['menu' => 'master_setup/konfigurasi_akun'];
if ($tab !== '' && $tab !== 'semua') {
    $redirectParams['tab'] = $tab;
}
if ($q !== '') {
    $redirectParams['q'] = $q;
}
$redirectUrl = admin_url('index.php?' . http_build_query($redirectParams));

if (!is_array($konfigurasi) || count($konfigurasi) === 0) {
    set_flash('error', 'Tidak ada data konfigurasi akun yang dikirim.');
    redirect_url($redirectUrl);
}

try {
    Capsule::connection()->transaction(function () use ($konfigurasi, $konfigurasiKode, $id_entitas, $id_pengguna) {
        foreach ($konfigurasi as $idKonfigurasi => $idCoaRaw) {
            $idKonfigurasi = (int) $idKonfigurasi;
            $idCoa = (int) $idCoaRaw;
            $kodeCoa = trim((string) ($konfigurasiKode[$idKonfigurasi] ?? ''));

            $row = KonfigurasiAkunORM::query()
                ->where('id_entitas', $id_entitas)
                ->where('id_konfigurasi_akun', $idKonfigurasi)
                ->first();

            if (!$row) {
                continue;
            }

            if ($kodeCoa !== '') {
                $kodeOnly = trim(explode(' - ', $kodeCoa)[0]);
                $coaByCode = CoaORM::query()
                    ->where('id_entitas', $id_entitas)
                    ->where('status_aktif', 1)
                    ->where('boleh_transaksi', 1)
                    ->where('kode_coa', $kodeOnly)
                    ->first();
                if (!$coaByCode) {
                    throw new RuntimeException('Kode akun ' . $kodeOnly . ' tidak ditemukan atau tidak aktif.');
                }
                $idCoa = (int) $coaByCode->id_coa;
            }

            $coa = CoaORM::query()
                ->where('id_entitas', $id_entitas)
                ->where('status_aktif', 1)
                ->where('boleh_transaksi', 1)
                ->where('id_coa', $idCoa)
                ->first();

            if (!$coa) {
                throw new RuntimeException('Kode akun tidak valid pada konfigurasi: ' . (string) $row->nama_konfigurasi);
            }

            if ((int) $row->id_coa !== $idCoa) {
                $row->id_coa = $idCoa;
                $row->tanggal_diubah = date('Y-m-d H:i:s');
                $row->diubah_oleh = $id_pengguna > 0 ? $id_pengguna : null;
                $row->save();
            }
        }
    });

    set_flash('success', 'Konfigurasi akun berhasil diperbarui. Tidak ada data konfigurasi baru yang dibuat.');
    redirect_url($redirectUrl);
} catch (Throwable $e) {
    set_flash('error', $e->getMessage());
    redirect_url($redirectUrl);
}
