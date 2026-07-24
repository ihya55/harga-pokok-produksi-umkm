<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/kode.php';

require_once __DIR__ . '/../../../../orm/BiayaProduksiORM.php';
require_once __DIR__ . '/../../../../orm/BiayaProduksiDetailORM.php';
require_once __DIR__ . '/../../../../orm/PerintahProduksiORM.php';
require_once __DIR__ . '/../../../../orm/CoaORM.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_admin('produksi/biaya');
}

function angka_decimal_biaya_produksi($value): float
{
    return (float) preg_replace('/[^0-9.]/', '', (string) $value);
}

function redirect_back_biaya_produksi(string $back_url): void
{
    header('Location: ' . $back_url);
    exit;
}

function kode_template_biaya_produksi_normalisasi(string $kode): string
{
    $kode = trim($kode);

    $legacy = [
        'tenaga_kerja' => 'BIAYA_TENAGA_KERJA_LANGSUNG',
        'overhead'     => 'BIAYA_OVERHEAD_PABRIK',
        'listrik'      => 'BIAYA_LISTRIK_PRODUKSI',
        'kemasan'      => 'BIAYA_KEMASAN_PRODUKSI',
        'lain_lain'    => 'BIAYA_LAIN_LAIN_PRODUKSI',
    ];

    return $legacy[$kode] ?? $kode;
}

function template_biaya_produksi_db(int $id_entitas, string $kode_jenis_transaksi, int $id_coa_kredit = 0): ?object
{
    $query = Capsule::table('tb_template_jurnal as tj')
        ->join('tb_template_jurnal_detail as td', function ($join) {
            $join->on('td.id_template_jurnal', '=', 'tj.id_template_jurnal')
                ->where('td.posisi_dc', '=', 'debit');
        })
        ->join('tb_template_jurnal_detail as tk', function ($join) {
            $join->on('tk.id_template_jurnal', '=', 'tj.id_template_jurnal')
                ->where('tk.posisi_dc', '=', 'kredit');
        })
        ->join('tb_coa as cd', 'cd.id_coa', '=', 'td.id_coa_default')
        ->join('tb_coa as ck', 'ck.id_coa', '=', 'tk.id_coa_default')
        ->where('tj.id_entitas', $id_entitas)
        ->where('tj.kode_jenis_transaksi', $kode_jenis_transaksi)
        ->where('tj.status_aktif', 1)
        ->where('cd.boleh_transaksi', 1)
        ->where('ck.boleh_transaksi', 1)
        ->where('cd.status_aktif', 1)
        ->where('ck.status_aktif', 1);

    if ($id_coa_kredit > 0) {
        $query->where('tk.id_coa_default', $id_coa_kredit);
    }

    return $query->select([
            'tj.id_template_jurnal',
            'tj.kode_template_jurnal',
            'tj.nama_template_jurnal',
            'tj.kode_jenis_transaksi',
            'td.id_coa_default as id_coa_debit',
            'tk.id_coa_default as id_coa_kredit',
            'tk.sumber_nominal',
            'tk.rumus_nominal',
        ])
        ->first();
}

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_pengguna = (int) (user_login()['id_pengguna'] ?? 0);

$back_url = trim((string) ($_POST['back_url'] ?? ''));
if ($back_url === '') {
    $back_url = admin_url('index.php?menu=produksi/biaya');
}

$tanggal_biaya = trim((string) ($_POST['tanggal_biaya'] ?? ''));
$id_perintah_produksi = (int) ($_POST['id_perintah_produksi'] ?? 0);
$keterangan = trim((string) ($_POST['keterangan'] ?? ''));

$jenis_detail = $_POST['jenis_biaya_produksi_detail'] ?? [];
$id_coa_detail = $_POST['id_coa_lawan_detail'] ?? [];
$jumlah_detail = $_POST['jumlah_biaya_detail'] ?? [];
$keterangan_detail = $_POST['keterangan_detail'] ?? [];

if ($tanggal_biaya === '') {
    set_flash('error', 'Tanggal biaya wajib diisi.');
    redirect_back_biaya_produksi($back_url);
}

$perintah = PerintahProduksiORM::query()
    ->where('id_entitas', $id_entitas)
    ->where('id_perintah_produksi', $id_perintah_produksi)
    ->first();

if (!$perintah) {
    set_flash('error', 'Perintah produksi tidak valid.');
    redirect_back_biaya_produksi($back_url);
}

$sudah_posted = BiayaProduksiORM::query()
    ->where('id_entitas', $id_entitas)
    ->where('id_perintah_produksi', $id_perintah_produksi)
    ->where('status_posting', 'posted')
    ->exists();

if ($sudah_posted) {
    set_flash('error', 'Perintah produksi ini sudah memiliki biaya produksi posted.');
    redirect_back_biaya_produksi($back_url);
}

if (!is_array($jenis_detail) || count($jenis_detail) === 0) {
    set_flash('error', 'Detail biaya produksi wajib diisi.');
    redirect_back_biaya_produksi($back_url);
}

$detail_valid = [];
$total_biaya = 0;

foreach ($jenis_detail as $i => $kode_template_input) {
    $kode_jenis_transaksi = kode_template_biaya_produksi_normalisasi((string) $kode_template_input);
    $id_coa_lawan = (int) ($id_coa_detail[$i] ?? 0);
    $jumlah = angka_decimal_biaya_produksi($jumlah_detail[$i] ?? 0);
    $ket = trim((string) ($keterangan_detail[$i] ?? ''));

    if ($kode_jenis_transaksi === '' || $jumlah <= 0) {
        continue;
    }

    $template = template_biaya_produksi_db($id_entitas, $kode_jenis_transaksi, $id_coa_lawan);

    if (!$template) {
        set_flash('error', 'Template jurnal biaya produksi tidak valid atau akun kredit tidak sesuai template: ' . $kode_jenis_transaksi);
        redirect_back_biaya_produksi($back_url);
    }

    $id_coa_lawan = (int) $template->id_coa_kredit;
    $total_biaya += $jumlah;

    $detail_valid[] = [
        'jenis_biaya_produksi' => $kode_jenis_transaksi,
        'id_coa_lawan'         => $id_coa_lawan,
        'jumlah_biaya'         => round($jumlah, 2),
        'keterangan'           => $ket !== '' ? $ket : null,
    ];
}

if (count($detail_valid) === 0 || $total_biaya <= 0) {
    set_flash('error', 'Detail biaya produksi belum valid.');
    redirect_back_biaya_produksi($back_url);
}

try {
    $id_baru = null;

    Capsule::connection()->transaction(function () use (
        &$id_baru,
        $id_entitas,
        $id_pengguna,
        $tanggal_biaya,
        $id_perintah_produksi,
        $keterangan,
        $total_biaya,
        $detail_valid
    ) {
        $no_biaya = generate_kode_master(
            'tb_biaya_produksi',
            'no_biaya_produksi',
            'BPR',
            4,
            $id_entitas
        );

        $first_detail = $detail_valid[0];

        $row = BiayaProduksiORM::create([
            'id_entitas'              => $id_entitas,
            'no_biaya_produksi'       => $no_biaya,
            'tanggal_biaya'           => $tanggal_biaya,
            'id_perintah_produksi'    => $id_perintah_produksi,
            'jenis_biaya_produksi'    => $first_detail['jenis_biaya_produksi'],
            'keterangan'              => $keterangan !== '' ? $keterangan : null,
            'jumlah_biaya'            => round($total_biaya, 2),
            'id_coa_lawan'            => $first_detail['id_coa_lawan'],
            'status_posting'          => 'draft',
            'tanggal_dibuat'          => date('Y-m-d H:i:s'),
            'dibuat_oleh'             => $id_pengguna > 0 ? $id_pengguna : null,
            'tanggal_diubah'          => null,
            'diubah_oleh'             => null,
        ]);

        $id_baru = (int) $row->id_biaya_produksi;

        foreach ($detail_valid as $detail) {
            BiayaProduksiDetailORM::create([
                'id_biaya_produksi'    => $id_baru,
                'jenis_biaya_produksi' => $detail['jenis_biaya_produksi'],
                'id_coa_lawan'         => $detail['id_coa_lawan'],
                'jumlah_biaya'         => $detail['jumlah_biaya'],
                'keterangan'           => $detail['keterangan'],
            ]);
        }
    });

    set_flash('success', 'Biaya produksi berhasil disimpan.');
    header('Location: ' . admin_url('index.php?menu=produksi/biaya/detail&id=' . $id_baru . '&back_url=' . urlencode($back_url)));
    exit;
} catch (Throwable $e) {
    set_flash('error', $e->getMessage());
    redirect_back_biaya_produksi($back_url);
}
