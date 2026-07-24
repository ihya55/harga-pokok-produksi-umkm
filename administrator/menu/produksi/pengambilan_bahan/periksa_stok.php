<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';

require_once __DIR__ . '/../../../../orm/PengambilanBahanORM.php';
require_once __DIR__ . '/../../../../orm/PengambilanBahanDetailORM.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_pengguna = (int) (user_login()['id_pengguna'] ?? 0);
$id_pengambilan_bahan = (int) ($_GET['id'] ?? 0);

$back_url = trim((string) ($_GET['back_url'] ?? ''));

if ($back_url === '') {
    $back_url = admin_url('index.php?menu=produksi/pengambilan-bahan');
}

$detail_url = admin_url(
    'index.php?menu=produksi/pengambilan-bahan/detail&id=' .
    $id_pengambilan_bahan .
    '&back_url=' .
    urlencode($back_url) .
    '&show_modal=stok'
);

function set_modal_periksa_stok_pengambilan_bahan(array $payload): void
{
    $_SESSION['modal_periksa_stok'] = $payload;
}

$row = PengambilanBahanORM::query()
    ->where('id_entitas', $id_entitas)
    ->find($id_pengambilan_bahan);

if (!$row) {
    set_modal_periksa_stok_pengambilan_bahan([
        'status'  => 'error',
        'title'   => 'Data Tidak Ditemukan',
        'message' => 'Data pengambilan bahan tidak ditemukan.',
        'rows'    => [],
    ]);

    header('Location: ' . $back_url);
    exit;
}

$detail_url = admin_url(
    'index.php?menu=produksi/pengambilan-bahan/detail&id=' .
    (int) $row->id_pengambilan_bahan .
    '&back_url=' .
    urlencode($back_url) .
    '&show_modal=stok'
);

if ((string) $row->status_posting === 'posted') {
    set_modal_periksa_stok_pengambilan_bahan([
        'status'  => 'error',
        'title'   => 'Sudah Posted',
        'message' => 'Pengambilan bahan yang sudah posted tidak perlu diperiksa ulang.',
        'rows'    => [],
    ]);

    header('Location: ' . $detail_url);
    exit;
}

$detail_rows = PengambilanBahanDetailORM::query()
    ->from('tb_pengambilan_bahan_detail as pbd')
    ->leftJoin('tb_bahan_baku as b', 'b.id_bahan_baku', '=', 'pbd.id_bahan_baku')
    ->where('pbd.id_pengambilan_bahan', $id_pengambilan_bahan)
    ->select([
        'pbd.*',
        'b.kode_bahan_baku',
        'b.nama_bahan_baku',
    ])
    ->orderBy('pbd.id_pengambilan_bahan_detail', 'asc')
    ->get();

if ($detail_rows->count() === 0) {
    set_modal_periksa_stok_pengambilan_bahan([
        'status'  => 'error',
        'title'   => 'Detail Kosong',
        'message' => 'Detail bahan belum ada.',
        'rows'    => [],
    ]);

    header('Location: ' . $detail_url);
    exit;
}

$hasil = [];
$ada_kurang = false;

foreach ($detail_rows as $detail) {
    $saldo = Capsule::table('tb_saldo_stok')
        ->where('id_entitas', $id_entitas)
        ->where('jenis_barang', 'bahan_baku')
        ->where('id_referensi_barang', (int) $detail->id_bahan_baku)
        ->where('id_gudang', (int) $row->id_gudang)
        ->first();

    $qty_saldo = $saldo ? (float) $saldo->qty_saldo : 0;
    $qty_kebutuhan = (float) $detail->qty;
    $qty_kurang = max(0, $qty_kebutuhan - $qty_saldo);

    if ($qty_saldo < $qty_kebutuhan) {
        $ada_kurang = true;
    }

    $harga_pakai = $saldo ? (float) ($saldo->hpp_rata_rata ?? 0) : 0;
    if ($harga_pakai > 0) {
        Capsule::table('tb_pengambilan_bahan_detail')
            ->where('id_pengambilan_bahan_detail', (int) $detail->id_pengambilan_bahan_detail)
            ->update([
                'harga_satuan' => round($harga_pakai, 2),
                'subtotal' => round($qty_kebutuhan * $harga_pakai, 2),
            ]);
    }

    $hasil[] = [
        'bahan'     => (($detail->kode_bahan_baku ?? '-') . ' - ' . ($detail->nama_bahan_baku ?? '-')),
        'stok'      => $qty_saldo,
        'butuh'     => $qty_kebutuhan,
        'kurang'    => $qty_kurang,
        'status'    => $qty_saldo >= $qty_kebutuhan ? 'cukup' : 'kurang',
    ];
}

if ($ada_kurang) {
    set_modal_periksa_stok_pengambilan_bahan([
        'status'  => 'warning',
        'title'   => 'Stok Belum Cukup',
        'message' => 'Ada bahan baku yang stoknya belum cukup. Status bon tetap draft dan belum bisa diposting.',
        'rows'    => $hasil,
    ]);

    header('Location: ' . $detail_url);
    exit;
}

try {
    $row->update([
        'status_posting' => 'diperiksa',
        'tanggal_diubah' => date('Y-m-d H:i:s'),
        'diubah_oleh'    => $id_pengguna > 0 ? $id_pengguna : null,
    ]);

    set_modal_periksa_stok_pengambilan_bahan([
        'status'  => 'success',
        'title'   => 'Stok Cukup',
        'message' => 'Semua stok bahan mencukupi. Bon pengambilan bahan sudah diperiksa oleh gudang dan siap diposting.',
        'rows'    => $hasil,
    ]);
} catch (Throwable $e) {
    set_modal_periksa_stok_pengambilan_bahan([
        'status'  => 'error',
        'title'   => 'Gagal Memperbarui Status',
        'message' => 'Gagal memperbarui status pemeriksaan: ' . $e->getMessage(),
        'rows'    => $hasil,
    ]);
}

header('Location: ' . $detail_url);
exit;