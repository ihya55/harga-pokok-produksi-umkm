<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';

require_once __DIR__ . '/../../../../orm/PembayaranPembelianORM.php';
require_once __DIR__ . '/../../../../orm/FakturPembelianORM.php';
require_once __DIR__ . '/../../../../orm/CoaORM.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_admin('pembelian/pembayaran');
}

function angka_decimal_bayar_beli_update($value): float
{
    return (float) preg_replace('/[^0-9.]/', '', (string) $value);
}

function redirect_back_bayar_update(string $back_url): void
{
    header('Location: ' . $back_url);
    exit;
}

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_pengguna = (int) (user_login()['id_pengguna'] ?? 0);

$back_url = trim((string) ($_POST['back_url'] ?? ''));
if ($back_url === '') {
    $back_url = admin_url('index.php?menu=pembelian/pembayaran');
}

$id_pembayaran_pembelian = (int) ($_POST['id_pembayaran_pembelian'] ?? 0);
$tanggal_pembayaran = trim((string) ($_POST['tanggal_pembayaran'] ?? ''));
$id_faktur_pembelian = (int) ($_POST['id_faktur_pembelian'] ?? 0);
$id_pemasok = (int) ($_POST['id_pemasok'] ?? 0);
$metode_pembayaran = trim((string) ($_POST['metode_pembayaran'] ?? 'transfer'));
$id_coa_kas_bank = (int) ($_POST['id_coa_kas_bank'] ?? 0);
$jumlah_bayar = angka_decimal_bayar_beli_update($_POST['jumlah_bayar'] ?? 0);
$catatan = trim((string) ($_POST['catatan'] ?? ''));

$row = PembayaranPembelianORM::query()
    ->where('id_entitas', $id_entitas)
    ->find($id_pembayaran_pembelian);

if (!$row) {
    set_flash('error', 'Data pembayaran pembelian tidak ditemukan.');
    redirect_back_bayar_update($back_url);
}

if ((string) $row->status_posting !== 'draft') {
    set_flash('error', 'Pembayaran yang sudah diposting tidak bisa diubah.');
    header('Location: ' . admin_url('index.php?menu=pembelian/pembayaran/detail&id=' . $id_pembayaran_pembelian . '&back_url=' . urlencode($back_url)));
    exit;
}

$faktur = FakturPembelianORM::query()
    ->where('id_entitas', $id_entitas)
    ->where('status_faktur', 'posted')
    ->where('jenis_pembayaran', 'kredit')
    ->where('id_faktur_pembelian', $id_faktur_pembelian)
    ->first();

if (!$faktur) {
    set_flash('error', 'Faktur pembelian tidak valid.');
    redirect_back_bayar_update($back_url);
}

$sisa_diperbolehkan = (float) $faktur->sisa_utang;

if ((int) $row->id_faktur_pembelian === (int) $faktur->id_faktur_pembelian) {
    $sisa_diperbolehkan += (float) $row->jumlah_bayar;
}

if ($jumlah_bayar <= 0 || $jumlah_bayar > $sisa_diperbolehkan) {
    set_flash('error', 'Jumlah bayar tidak valid atau melebihi sisa utang.');
    redirect_back_bayar_update($back_url);
}

if (!in_array($metode_pembayaran, ['tunai', 'transfer'], true)) {
    set_flash('error', 'Metode pembayaran tidak valid.');
    redirect_back_bayar_update($back_url);
}

if ($id_pemasok <= 0 || $id_pemasok !== (int) $faktur->id_pemasok) {
    set_flash('error', 'Pemasok tidak sesuai dengan faktur.');
    redirect_back_bayar_update($back_url);
}

$kode_template = $metode_pembayaran === 'tunai'
    ? 'FAKTUR_PEMBELIAN_TUNAI'
    : 'FAKTUR_PEMBELIAN_TRANSFER';

$akun_valid = CoaORM::query()
    ->from('tb_template_jurnal as tj')
    ->join('tb_template_jurnal_detail as tjd', 'tjd.id_template_jurnal', '=', 'tj.id_template_jurnal')
    ->join('tb_coa as c', 'c.id_coa', '=', 'tjd.id_coa_default')
    ->where('tj.id_entitas', $id_entitas)
    ->where('tj.kode_jenis_transaksi', $kode_template)
    ->where('tj.status_aktif', 1)
    ->where('tjd.posisi_dc', 'kredit')
    ->where('c.id_coa', $id_coa_kas_bank)
    ->where('c.boleh_transaksi', 1)
    ->where('c.status_aktif', 1)
    ->exists();

if (!$akun_valid) {
    set_flash('error', 'Akun kas/bank tidak sesuai dengan template jurnal metode pembayaran.');
    redirect_back_bayar_update($back_url);
}

try {
    Capsule::connection()->transaction(function () use (
        $row,
        $id_pengguna,
        $tanggal_pembayaran,
        $id_faktur_pembelian,
        $id_pemasok,
        $metode_pembayaran,
        $id_coa_kas_bank,
        $jumlah_bayar,
        $catatan
    ) {
        $row->update([
            'tanggal_pembayaran'      => $tanggal_pembayaran,
            'id_faktur_pembelian'     => $id_faktur_pembelian,
            'id_pemasok'              => $id_pemasok,
            'metode_pembayaran'       => $metode_pembayaran,
            'id_coa_kas_bank'         => $id_coa_kas_bank,
            'jumlah_bayar'            => round($jumlah_bayar, 2),
            'catatan'                 => $catatan !== '' ? $catatan : null,
            'tanggal_diubah'          => date('Y-m-d H:i:s'),
            'diubah_oleh'             => $id_pengguna > 0 ? $id_pengguna : null,
        ]);
    });

    set_flash('success', 'Pembayaran pembelian berhasil diperbarui.');
    header('Location: ' . admin_url('index.php?menu=pembelian/pembayaran/detail&id=' . $id_pembayaran_pembelian . '&back_url=' . urlencode($back_url)));
    exit;
} catch (Throwable $e) {
    set_flash('error', $e->getMessage());
    redirect_back_bayar_update($back_url);
}