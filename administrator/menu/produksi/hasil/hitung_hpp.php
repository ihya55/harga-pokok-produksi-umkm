<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';

require_once __DIR__ . '/../../../../orm/PerintahProduksiORM.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

header('Content-Type: application/json; charset=utf-8');

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_perintah_produksi = (int) ($_GET['id_perintah_produksi'] ?? 0);

$perintah = PerintahProduksiORM::query()
    ->where('id_entitas', $id_entitas)
    ->where('id_perintah_produksi', $id_perintah_produksi)
    ->where('status_produksi', 'posted')
    ->first();

if (!$perintah) {
    echo json_encode([
        'success' => false,
        'message' => 'Perintah produksi tidak valid atau belum posted.',
        'data' => null,
    ]);
    exit;
}

$total_bahan = (float) Capsule::table('tb_pengambilan_bahan as pb')
    ->join('tb_pengambilan_bahan_detail as pbd', 'pbd.id_pengambilan_bahan', '=', 'pb.id_pengambilan_bahan')
    ->where('pb.id_entitas', $id_entitas)
    ->where('pb.id_perintah_produksi', $id_perintah_produksi)
    ->where('pb.status_posting', 'posted')
    ->sum('pbd.subtotal');

$total_tenaga_kerja = (float) Capsule::table('tb_biaya_produksi as bp')
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

$total_hpp = $total_bahan + $total_tenaga_kerja + $total_bop;

echo json_encode([
    'success' => true,
    'message' => 'HPP berhasil dihitung.',
    'data' => [
        'total_biaya_bahan'        => round($total_bahan, 2),
        'total_biaya_tenaga_kerja' => round($total_tenaga_kerja, 2),
        'total_biaya_bop'          => round($total_bop, 2),
        'total_hpp'                => round($total_hpp, 2),
    ],
], JSON_UNESCAPED_UNICODE);
exit;