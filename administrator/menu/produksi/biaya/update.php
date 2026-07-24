<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';

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

function angka_decimal_biaya_produksi_update($value): float
{
    return (float) preg_replace('/[^0-9.]/', '', (string) $value);
}

function redirect_back_biaya_produksi_update(string $back_url): void
{
    header('Location: ' . $back_url);
    exit;
}


function upload_nota_biaya_produksi_file(?string $file_lama = null): ?string
{
    if (empty($_FILES['file_nota']) || (int) ($_FILES['file_nota']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $file_lama ?: null;
    }
    if ((int) $_FILES['file_nota']['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload nota biaya gagal.');
    }
    $allowed = ['jpg','jpeg','png','pdf'];
    $ext = strtolower(pathinfo((string) ($_FILES['file_nota']['name'] ?? ''), PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) {
        throw new RuntimeException('Format nota hanya boleh JPG, PNG, atau PDF.');
    }
    $dir = __DIR__ . '/../../../../uploads/nota_produksi';
    if (!is_dir($dir)) { mkdir($dir, 0775, true); }
    $targetName = 'nota_biaya_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (!move_uploaded_file((string) $_FILES['file_nota']['tmp_name'], $dir . '/' . $targetName)) {
        throw new RuntimeException('Gagal menyimpan file nota biaya.');
    }
    return 'uploads/nota_produksi/' . $targetName;
}

function kode_template_biaya_produksi_update(string $jenis): string
{
    $map = [
        'tenaga_kerja' => 'BIAYA_TENAGA_KERJA_LANGSUNG',
        'overhead'     => 'BIAYA_OVERHEAD_PABRIK',
    ];

    return $map[$jenis] ?? '';
}

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_pengguna = (int) (user_login()['id_pengguna'] ?? 0);

$back_url = trim((string) ($_POST['back_url'] ?? ''));
if ($back_url === '') {
    $back_url = admin_url('index.php?menu=produksi/biaya');
}

$id_biaya_produksi = (int) ($_POST['id_biaya_produksi'] ?? 0);
$tanggal_biaya = trim((string) ($_POST['tanggal_biaya'] ?? ''));
$id_perintah_produksi = (int) ($_POST['id_perintah_produksi'] ?? 0);
$keterangan = trim((string) ($_POST['keterangan'] ?? ''));
$no_nota = trim((string) ($_POST['no_nota'] ?? ''));

$jenis_detail = $_POST['jenis_biaya_produksi_detail'] ?? [];
$id_coa_detail = $_POST['id_coa_lawan_detail'] ?? [];
$jumlah_detail = $_POST['jumlah_biaya_detail'] ?? [];
$keterangan_detail = $_POST['keterangan_detail'] ?? [];
$kode_template_detail = $_POST['kode_jenis_transaksi_template_detail'] ?? [];

$row = BiayaProduksiORM::query()
    ->where('id_entitas', $id_entitas)
    ->find($id_biaya_produksi);

if (!$row) {
    set_flash('error', 'Data biaya produksi tidak ditemukan.');
    redirect_back_biaya_produksi_update($back_url);
}

if ((string) $row->status_posting !== 'draft') {
    set_flash('error', 'Biaya produksi yang sudah diposting tidak bisa diubah.');
    header('Location: ' . admin_url('index.php?menu=produksi/biaya/detail&id=' . $id_biaya_produksi . '&back_url=' . urlencode($back_url)));
    exit;
}

if (!PerintahProduksiORM::query()->where('id_entitas', $id_entitas)->where('id_perintah_produksi', $id_perintah_produksi)->exists()) {
    set_flash('error', 'Perintah produksi tidak valid.');
    redirect_back_biaya_produksi_update($back_url);
}

$sudah_posted_lain = BiayaProduksiORM::query()
    ->where('id_entitas', $id_entitas)
    ->where('id_perintah_produksi', $id_perintah_produksi)
    ->where('status_posting', 'posted')
    ->where('id_biaya_produksi', '!=', $id_biaya_produksi)
    ->exists();

if ($sudah_posted_lain) {
    set_flash('error', 'Perintah produksi ini sudah memiliki biaya produksi posted.');
    redirect_back_biaya_produksi_update($back_url);
}

$jenis_valid = ['tenaga_kerja', 'overhead'];
$detail_valid = [];
$total_biaya = 0;

foreach ($jenis_detail as $i => $jenis) {
    $jenis = trim((string) $jenis);
    $id_coa_lawan = (int) ($id_coa_detail[$i] ?? 0);
    $jumlah = angka_decimal_biaya_produksi_update($jumlah_detail[$i] ?? 0);
    $ket = trim((string) ($keterangan_detail[$i] ?? ''));
    $kode_template_pilihan = strtoupper(trim((string) ($kode_template_detail[$i] ?? '')));

    if (!in_array($jenis, $jenis_valid, true) || $id_coa_lawan <= 0 || $jumlah <= 0) {
        continue;
    }

    $kode_template = $kode_template_pilihan !== '' ? $kode_template_pilihan : kode_template_biaya_produksi_update($jenis);

    if ($jenis === 'tenaga_kerja' && $kode_template !== 'BIAYA_TENAGA_KERJA_LANGSUNG') {
        set_flash('error', 'Jenis tenaga kerja hanya boleh memakai template tenaga kerja.');
        redirect_back_biaya_produksi_update($back_url);
    }
    if ($jenis === 'overhead' && $kode_template === 'BIAYA_TENAGA_KERJA_LANGSUNG') {
        set_flash('error', 'Jenis overhead tidak boleh memakai template tenaga kerja.');
        redirect_back_biaya_produksi_update($back_url);
    }

    $akun_valid = CoaORM::query()
        ->from('tb_template_jurnal as tj')
        ->join('tb_template_jurnal_detail as tjd', 'tjd.id_template_jurnal', '=', 'tj.id_template_jurnal')
        ->join('tb_coa as c', 'c.id_coa', '=', 'tjd.id_coa_default')
        ->where('tj.id_entitas', $id_entitas)
        ->where('tj.kode_jenis_transaksi', $kode_template)
        ->where('tj.status_aktif', 1)
        ->where('tjd.posisi_dc', 'kredit')
        ->where('c.id_coa', $id_coa_lawan)
        ->where('c.boleh_transaksi', 1)
        ->where('c.status_aktif', 1)
        ->exists();

    if (!$akun_valid) {
        set_flash('error', 'Ada akun lawan yang tidak sesuai dengan template jurnal jenis biaya.');
        redirect_back_biaya_produksi_update($back_url);
    }

    $total_biaya += $jumlah;

    $detail_valid[] = [
        'jenis_biaya_produksi' => $jenis,
        'id_coa_lawan'         => $id_coa_lawan,
        'kode_jenis_transaksi_template' => $kode_template,
        'jumlah_biaya'         => round($jumlah, 2),
        'keterangan'           => $ket !== '' ? $ket : null,
    ];
}

if (count($detail_valid) === 0 || $total_biaya <= 0) {
    set_flash('error', 'Detail biaya produksi belum valid.');
    redirect_back_biaya_produksi_update($back_url);
}

try {
    Capsule::connection()->transaction(function () use (
        $row,
        $id_biaya_produksi,
        $id_pengguna,
        $tanggal_biaya,
        $id_perintah_produksi,
        $keterangan,
        $total_biaya,
        $no_nota,
        $detail_valid
    ) {
        $file_nota = upload_nota_biaya_produksi_file((string) ($row->file_nota ?? ''));

        $first_detail = $detail_valid[0];

        $row->update([
            'tanggal_biaya'           => $tanggal_biaya,
            'id_perintah_produksi'    => $id_perintah_produksi,
            'jenis_biaya_produksi'    => $first_detail['jenis_biaya_produksi'],
            'keterangan'              => $keterangan !== '' ? $keterangan : null,
            'no_nota'                 => $no_nota !== '' ? $no_nota : null,
            'file_nota'               => $file_nota,
            'jumlah_biaya'            => round($total_biaya, 2),
            'id_coa_lawan'            => $first_detail['id_coa_lawan'],
            'tanggal_diubah'          => date('Y-m-d H:i:s'),
            'diubah_oleh'             => $id_pengguna > 0 ? $id_pengguna : null,
        ]);

        BiayaProduksiDetailORM::query()
            ->where('id_biaya_produksi', $id_biaya_produksi)
            ->delete();

        foreach ($detail_valid as $detail) {
            BiayaProduksiDetailORM::create([
                'id_biaya_produksi'    => $id_biaya_produksi,
                'jenis_biaya_produksi' => $detail['jenis_biaya_produksi'],
                'id_coa_lawan'         => $detail['id_coa_lawan'],
                'kode_jenis_transaksi_template' => $detail['kode_jenis_transaksi_template'],
                'jumlah_biaya'         => $detail['jumlah_biaya'],
                'keterangan'           => $detail['keterangan'],
            ]);
        }
    });

    set_flash('success', 'Biaya produksi berhasil diperbarui.');
    header('Location: ' . admin_url('index.php?menu=produksi/biaya/detail&id=' . $id_biaya_produksi . '&back_url=' . urlencode($back_url)));
    exit;
} catch (Throwable $e) {
    set_flash('error', $e->getMessage());
    redirect_back_biaya_produksi_update($back_url);
}