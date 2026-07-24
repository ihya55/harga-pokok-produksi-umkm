<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../orm/MappingAkunORM.php';
require_once __DIR__ . '/../../../../orm/CoaORM.php';
require_once __DIR__ . '/../../../../helpers/auth.php';
require_once __DIR__ . '/helpers_mapping_akun.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_admin('master_setup/mapping_akun');
}

$id_entitas = mapping_akun_id_entitas();
$id_pengguna = mapping_akun_id_pengguna();
$mapping = $_POST['mapping'] ?? [];
$mappingKode = $_POST['mapping_kode'] ?? [];
$tab = trim((string) ($_POST['tab'] ?? 'semua'));
$q = trim((string) ($_POST['q'] ?? ''));

$redirectParams = ['menu' => 'master_setup/mapping_akun'];
if ($tab !== '' && $tab !== 'semua') {
    $redirectParams['tab'] = $tab;
}
if ($q !== '') {
    $redirectParams['q'] = $q;
}
$redirectUrl = admin_url('index.php?' . http_build_query($redirectParams));

if (!is_array($mapping) || count($mapping) === 0) {
    set_flash('error', 'Tidak ada data mapping akun yang dikirim.');
    redirect_url($redirectUrl);
}

try {
    Capsule::connection()->transaction(function () use ($mapping, $mappingKode, $id_entitas, $id_pengguna) {
        foreach ($mapping as $idMapping => $idCoaRaw) {
            $idMapping = (int) $idMapping;
            $idCoa = (int) $idCoaRaw;
            $kodeCoa = trim((string) ($mappingKode[$idMapping] ?? ''));

            $row = MappingAkunORM::query()
                ->where('id_entitas', $id_entitas)
                ->where('id_mapping_akun', $idMapping)
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
                throw new RuntimeException('Kode akun tidak valid pada mapping: ' . label_jenis_transaksi_mapping_akun($row->kode_jenis_transaksi ?? ''));
            }

            if ((int) $row->id_coa !== $idCoa) {
                $row->id_coa = $idCoa;
                $row->tanggal_diubah = date('Y-m-d H:i:s');
                $row->diubah_oleh = $id_pengguna > 0 ? $id_pengguna : null;
                $row->save();
            }
        }
    });

    set_flash('success', 'Mapping akun berhasil diperbarui. Tidak ada data mapping baru yang dibuat.');
    redirect_url($redirectUrl);
} catch (Throwable $e) {
    set_flash('error', $e->getMessage());
    redirect_url($redirectUrl);
}
