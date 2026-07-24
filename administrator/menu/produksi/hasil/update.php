<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';

require_once __DIR__ . '/../../../../orm/HasilProduksiORM.php';
require_once __DIR__ . '/../../../../orm/PerintahProduksiORM.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_admin('produksi/hasil');
}

function angka_int_hasil_produksi_update($value): int
{
    return max(0, (int) preg_replace('/[^0-9]/', '', (string) $value));
}

function redirect_back_hasil_produksi_update(string $back_url): void
{
    header('Location: ' . $back_url);
    exit;
}

function hitung_hpp_update_hasil_produksi(int $id_entitas, int $id_perintah_produksi): array
{
    $total_bahan = (float) Capsule::table('tb_pengambilan_bahan as pb')
        ->join('tb_pengambilan_bahan_detail as pbd', 'pbd.id_pengambilan_bahan', '=', 'pb.id_pengambilan_bahan')
        ->where('pb.id_entitas', $id_entitas)
        ->where('pb.id_perintah_produksi', $id_perintah_produksi)
        ->where('pb.status_posting', 'posted')
        ->sum('pbd.subtotal');

    $total_tenaga = (float) Capsule::table('tb_biaya_produksi as bp')
        ->join('tb_biaya_produksi_detail as bpd', 'bpd.id_biaya_produksi', '=', 'bp.id_biaya_produksi')
        ->where('bp.id_entitas', $id_entitas)
        ->where('bp.id_perintah_produksi', $id_perintah_produksi)
        ->where('bp.status_posting', 'posted')
        ->where('bpd.jenis_biaya_produksi', 'tenaga_kerja')
        ->sum('bpd.jumlah_biaya');

    $total_bop = (float) Capsule::table('tb_biaya_produksi as bp')
        ->join('tb_biaya_produksi_detail as bpd', 'bpd.id_biaya_produksi', '=', 'bp.id_biaya_produksi')
        ->where('bp.id_entitas', $id_entitas)
        ->where('bp.id_perintah_produksi', $id_perintah_produksi)
        ->where('bp.status_posting', 'posted')
        ->where('bpd.jenis_biaya_produksi', '!=', 'tenaga_kerja')
        ->sum('bpd.jumlah_biaya');

    $total_hpp = $total_bahan + $total_tenaga + $total_bop;

    return [
        'total_biaya_bahan'        => round($total_bahan, 2),
        'total_biaya_tenaga_kerja' => round($total_tenaga, 2),
        'total_biaya_bop'          => round($total_bop, 2),
        'total_hpp'                => round($total_hpp, 2),
    ];
}

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_pengguna = (int) (user_login()['id_pengguna'] ?? 0);

$back_url = trim((string) ($_POST['back_url'] ?? ''));
if ($back_url === '') {
    $back_url = admin_url('index.php?menu=produksi/hasil');
}

$id_hasil_produksi = (int) ($_POST['id_hasil_produksi'] ?? 0);
$tanggal_hasil = trim((string) ($_POST['tanggal_hasil'] ?? ''));
$id_perintah_produksi = (int) ($_POST['id_perintah_produksi'] ?? 0);
$id_gudang = (int) ($_POST['id_gudang'] ?? 0);
$qty_hasil = angka_int_hasil_produksi_update($_POST['qty_hasil'] ?? 0);
$catatan = trim((string) ($_POST['catatan'] ?? ''));

$row = HasilProduksiORM::query()
    ->where('id_entitas', $id_entitas)
    ->find($id_hasil_produksi);

if (!$row) {
    set_flash('error', 'Data hasil produksi tidak ditemukan.');
    redirect_back_hasil_produksi_update($back_url);
}

if ((string) $row->status_posting !== 'draft') {
    set_flash('error', 'Hasil produksi yang sudah posted tidak bisa diubah.');
    header('Location: ' . admin_url('index.php?menu=produksi/hasil/detail&id=' . $id_hasil_produksi . '&back_url=' . urlencode($back_url)));
    exit;
}

if ($tanggal_hasil === '' || $qty_hasil <= 0) {
    set_flash('error', 'Tanggal hasil dan qty hasil wajib valid.');
    redirect_back_hasil_produksi_update($back_url);
}

$perintah = PerintahProduksiORM::query()
    ->where('id_entitas', $id_entitas)
    ->where('id_perintah_produksi', $id_perintah_produksi)
    ->where('status_produksi', 'posted')
    ->first();

if (!$perintah) {
    set_flash('error', 'Perintah produksi tidak valid atau belum posted.');
    redirect_back_hasil_produksi_update($back_url);
}

$sudah_ada_lain = HasilProduksiORM::query()
    ->where('id_entitas', $id_entitas)
    ->where('id_perintah_produksi', $id_perintah_produksi)
    ->where('id_hasil_produksi', '!=', $id_hasil_produksi)
    ->exists();

if ($sudah_ada_lain) {
    set_flash('error', 'Perintah produksi ini sudah digunakan di hasil produksi lain.');
    redirect_back_hasil_produksi_update($back_url);
}

$gudang_valid = Capsule::table('tb_gudang')
    ->where('id_entitas', $id_entitas)
    ->where('id_gudang', $id_gudang)
    ->where('status_aktif', 1)
    ->exists();

if (!$gudang_valid) {
    set_flash('error', 'Gudang tidak valid atau tidak aktif.');
    redirect_back_hasil_produksi_update($back_url);
}

$hpp = hitung_hpp_update_hasil_produksi($id_entitas, $id_perintah_produksi);

if ((float) $hpp['total_hpp'] <= 0) {
    set_flash('error', 'Total HPP masih 0. Pastikan pengambilan bahan atau biaya produksi sudah posted.');
    redirect_back_hasil_produksi_update($back_url);
}

$hpp_per_unit = $qty_hasil > 0 ? ((float) $hpp['total_hpp'] / $qty_hasil) : 0;

try {
    Capsule::connection()->transaction(function () use (
        $row,
        $id_pengguna,
        $tanggal_hasil,
        $perintah,
        $id_gudang,
        $qty_hasil,
        $hpp,
        $hpp_per_unit,
        $catatan
    ) {
        $row->update([
            'tanggal_hasil'               => $tanggal_hasil,
            'id_perintah_produksi'        => (int) $perintah->id_perintah_produksi,
            'id_produk'                   => (int) $perintah->id_produk,
            'id_gudang'                   => $id_gudang,
            'qty_hasil'                   => $qty_hasil,
            'total_biaya_bahan'           => $hpp['total_biaya_bahan'],
            'total_biaya_tenaga_kerja'    => $hpp['total_biaya_tenaga_kerja'],
            'total_biaya_bop'             => $hpp['total_biaya_bop'],
            'total_hpp'                   => $hpp['total_hpp'],
            'hpp_per_unit'                => round($hpp_per_unit, 2),
            'catatan'                     => $catatan !== '' ? $catatan : null,
            'tanggal_diubah'              => date('Y-m-d H:i:s'),
            'diubah_oleh'                 => $id_pengguna > 0 ? $id_pengguna : null,
        ]);
    });

    set_flash('success', 'Hasil produksi berhasil diperbarui.');
    header('Location: ' . admin_url('index.php?menu=produksi/hasil/detail&id=' . $id_hasil_produksi . '&back_url=' . urlencode($back_url)));
    exit;
} catch (Throwable $e) {
    set_flash('error', $e->getMessage());
    redirect_back_hasil_produksi_update($back_url);
}