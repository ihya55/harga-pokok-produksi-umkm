<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../orm/TemplateJurnalORM.php';
require_once __DIR__ . '/../../../../orm/TemplateJurnalDetailORM.php';
require_once __DIR__ . '/../../../../orm/CoaORM.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_admin('master_setup/template_jurnal');
}

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_pengguna = (int) (user_login()['id_pengguna'] ?? 0);
$template = $_POST['template'] ?? [];
$templateKode = $_POST['template_kode'] ?? [];
$tab = trim((string) ($_POST['tab'] ?? 'semua'));
$q = trim((string) ($_POST['q'] ?? ''));

$redirectParams = ['menu' => 'master_setup/template_jurnal'];
if ($tab !== '' && $tab !== 'semua') {
    $redirectParams['tab'] = $tab;
}
if ($q !== '') {
    $redirectParams['q'] = $q;
}
$redirectUrl = admin_url('index.php?' . http_build_query($redirectParams));

if (!is_array($template) || count($template) === 0) {
    set_flash('error', 'Tidak ada data template jurnal yang dikirim.');
    redirect_url($redirectUrl);
}

try {
    Capsule::connection()->transaction(function () use ($template, $templateKode, $id_entitas, $id_pengguna) {
        foreach ($template as $idDetail => $idCoaRaw) {
            $idDetail = (int) $idDetail;
            $idCoa = (int) $idCoaRaw;
            $kodeCoa = trim((string) ($templateKode[$idDetail] ?? ''));

            $row = Capsule::table('tb_template_jurnal_detail as d')
                ->join('tb_template_jurnal as t', 't.id_template_jurnal', '=', 'd.id_template_jurnal')
                ->where('t.id_entitas', $id_entitas)
                ->where('d.id_template_jurnal_detail', $idDetail)
                ->select('d.*', 't.nama_template_jurnal')
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

            $idCoaOrNull = $idCoa > 0 ? $idCoa : null;

            if ($idCoaOrNull !== null) {
                $coa = CoaORM::query()
                    ->where('id_entitas', $id_entitas)
                    ->where('status_aktif', 1)
                    ->where('boleh_transaksi', 1)
                    ->where('id_coa', $idCoaOrNull)
                    ->first();

                if (!$coa) {
                    throw new RuntimeException('Kode akun tidak valid pada template: ' . (string) ($row->nama_template_jurnal ?? '-'));
                }
            }

            Capsule::table('tb_template_jurnal_detail')
                ->where('id_template_jurnal_detail', $idDetail)
                ->update([
                    'sumber_akun' => 'coa_default',
                    'id_coa_default' => $idCoaOrNull,
                ]);

            Capsule::table('tb_template_jurnal')
                ->where('id_template_jurnal', (int) $row->id_template_jurnal)
                ->where('id_entitas', $id_entitas)
                ->update([
                    'tanggal_diubah' => date('Y-m-d H:i:s'),
                    'diubah_oleh' => $id_pengguna > 0 ? $id_pengguna : null,
                ]);
        }
    });

    set_flash('success', 'Akun default template jurnal berhasil diperbarui. Tidak ada template atau baris jurnal baru yang dibuat.');
    redirect_url($redirectUrl);
} catch (Throwable $e) {
    set_flash('error', $e->getMessage());
    redirect_url($redirectUrl);
}
