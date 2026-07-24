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
$id_gudang = (int) ($_GET['id_gudang'] ?? 0);

$perintah = PerintahProduksiORM::query()
    ->where('id_entitas', $id_entitas)
    ->where('id_perintah_produksi', $id_perintah_produksi)
    ->where('status_produksi', 'posted')
    ->first();

if (!$perintah) {
    echo json_encode([
        'success' => false,
        'message' => 'Perintah produksi tidak valid atau belum posted.',
        'rows' => [],
    ]);
    exit;
}

$rows = Capsule::table('tb_resep_detail as rd')
    ->leftJoin('tb_bahan_baku as b', 'b.id_bahan_baku', '=', 'rd.id_bahan_baku')
    ->where('rd.id_resep', (int) $perintah->id_resep)
    ->select([
        'rd.id_bahan_baku',
        'rd.jumlah_pakai',
        'b.harga_standar',
    ])
    ->orderBy('rd.id_resep_detail', 'asc')
    ->get();

$data = [];

$jumlah_hasil_resep = (float) Capsule::table('tb_resep')
    ->where('id_entitas', $id_entitas)
    ->where('id_resep', (int) $perintah->id_resep)
    ->value('jumlah_hasil');

foreach ($rows as $item) {
    $qty_rencana = (float) ($perintah->qty_rencana ?? 0);
    $qty_resep = (float) ($item->jumlah_pakai ?? 0);

    // Rumus benar: jumlah pakai resep dikali perbandingan qty produksi terhadap hasil resep.
    // Contoh resep hasil 45 kg butuh kelapa 50 biji, produksi 45 kg => 50 * (45/45) = 50.
    // Jika produksi 90 kg => 50 * (90/45) = 100.
    $faktor = $jumlah_hasil_resep > 0 ? ($qty_rencana / $jumlah_hasil_resep) : $qty_rencana;
    $qty = round($qty_resep * $faktor, 3);

    if ($qty <= 0) {
        continue;
    }

    $harga_satuan = 0.0;
    if ($id_gudang > 0) {
        $saldo = Capsule::table('tb_saldo_stok')
            ->where('id_entitas', $id_entitas)
            ->where('jenis_barang', 'bahan_baku')
            ->where('id_referensi_barang', (int) $item->id_bahan_baku)
            ->where('id_gudang', $id_gudang)
            ->first();

        if ($saldo && (float) ($saldo->hpp_rata_rata ?? 0) > 0) {
            $harga_satuan = (float) $saldo->hpp_rata_rata;
        }
    }

    if ($harga_satuan <= 0) {
        $harga_satuan = (float) ($item->harga_standar ?? 0);
    }

    $data[] = [
        'id_bahan_baku' => (int) $item->id_bahan_baku,
        'qty'           => $qty,
        'harga_satuan'  => round($harga_satuan, 2),
        'subtotal'      => round($qty * $harga_satuan, 2),
    ];
}

echo json_encode([
    'success' => true,
    'message' => 'Detail resep berhasil dimuat.',
    'rows' => $data,
], JSON_UNESCAPED_UNICODE);
exit;