<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';

require_once __DIR__ . '/../../../../orm/PenerimaanPembelianORM.php';
require_once __DIR__ . '/../../../../orm/PenerimaanPembelianDetailORM.php';
require_once __DIR__ . '/../../../../orm/MutasiStokORM.php';
require_once __DIR__ . '/../../../../orm/SaldoStokORM.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_pengguna = (int) (user_login()['id_pengguna'] ?? 0);
$id_penerimaan_pembelian = (int) ($_GET['id'] ?? 0);

$row = PenerimaanPembelianORM::query()
    ->where('id_entitas', $id_entitas)
    ->find($id_penerimaan_pembelian);

if (!$row) {
    set_flash('error', 'Data penerimaan pembelian tidak ditemukan.');
    redirect_admin('pembelian/penerimaan');
}

if ((string) $row->status_penerimaan !== 'draft') {
    set_flash('error', 'Hanya penerimaan berstatus draft yang bisa diposting.');
    redirect_admin('pembelian/penerimaan/detail&id=' . $id_penerimaan_pembelian);
}

$detail = PenerimaanPembelianDetailORM::query()
    ->where('id_penerimaan_pembelian', $id_penerimaan_pembelian)
    ->get();

if ($detail->count() <= 0) {
    set_flash('error', 'Penerimaan belum memiliki detail.');
    redirect_admin('pembelian/penerimaan/detail&id=' . $id_penerimaan_pembelian);
}

try {
    Capsule::connection()->transaction(function () use (
        $row,
        $detail,
        $id_entitas,
        $id_pengguna,
        $id_penerimaan_pembelian
    ) {
        foreach ($detail as $item) {
            $qty_masuk = (int) $item->qty;
            $harga_satuan = (float) $item->harga;
            $nilai_total = round($qty_masuk * $harga_satuan, 2);

            if ($qty_masuk <= 0) {
                throw new RuntimeException('Qty detail tidak valid.');
            }

            MutasiStokORM::create([
                'id_entitas'           => $id_entitas,
                'tanggal_mutasi'       => date('Y-m-d H:i:s'),
                'jenis_barang'         => 'bahan_baku',
                'id_referensi_barang'  => (int) $item->id_bahan_baku,
                'id_gudang'            => (int) $row->id_gudang,
                'jenis_mutasi'         => 'penerimaan_pembelian',
                'qty_masuk'            => $qty_masuk,
                'qty_keluar'           => 0,
                'harga_satuan'         => $harga_satuan,
                'nilai_total'          => $nilai_total,
                'tabel_sumber'         => 'tb_penerimaan_pembelian',
                'id_sumber'            => (int) $row->id_penerimaan_pembelian,
                'no_sumber'            => (string) $row->no_penerimaan_pembelian,
                'keterangan'           => 'Posting penerimaan pembelian',
                'tanggal_dibuat'       => date('Y-m-d H:i:s'),
                'dibuat_oleh'          => $id_pengguna > 0 ? $id_pengguna : null,
                'tanggal_diubah'       => null,
                'diubah_oleh'          => null,
            ]);

            $saldo = SaldoStokORM::query()
                ->where('id_entitas', $id_entitas)
                ->where('jenis_barang', 'bahan_baku')
                ->where('id_referensi_barang', (int) $item->id_bahan_baku)
                ->where('id_gudang', (int) $row->id_gudang)
                ->first();

            if ($saldo) {
                $qty_lama = (float) $saldo->qty_saldo;
                $nilai_lama = (float) $saldo->nilai_saldo;

                $qty_baru = $qty_lama + $qty_masuk;
                $nilai_baru = $nilai_lama + $nilai_total;
                $hpp_rata_rata = $qty_baru > 0 ? round($nilai_baru / $qty_baru, 2) : 0;

                $saldo->update([
                    'qty_saldo'     => $qty_baru,
                    'nilai_saldo'   => $nilai_baru,
                    'hpp_rata_rata' => $hpp_rata_rata,
                    'tanggal_update'=> date('Y-m-d H:i:s'),
                ]);
            } else {
                SaldoStokORM::create([
                    'id_entitas'          => $id_entitas,
                    'jenis_barang'        => 'bahan_baku',
                    'id_referensi_barang' => (int) $item->id_bahan_baku,
                    'id_gudang'           => (int) $row->id_gudang,
                    'qty_saldo'           => $qty_masuk,
                    'nilai_saldo'         => $nilai_total,
                    'hpp_rata_rata'       => $harga_satuan,
                    'tanggal_update'      => date('Y-m-d H:i:s'),
                ]);
            }
        }

        $row->update([
            'status_penerimaan' => 'posted',
            'tanggal_posting'   => date('Y-m-d H:i:s'),
            'diposting_oleh'    => $id_pengguna > 0 ? $id_pengguna : null,
            'tanggal_diubah'    => date('Y-m-d H:i:s'),
            'diubah_oleh'       => $id_pengguna > 0 ? $id_pengguna : null,
        ]);
    });

    set_flash('success', 'Penerimaan pembelian berhasil diposting. Stok bahan baku sudah masuk.');
    header('Location: ' . admin_url('index.php?menu=pembelian/penerimaan/detail&id=' . $id_penerimaan_pembelian));
    exit;
} catch (Throwable $e) {
    set_flash('error', $e->getMessage());
        header('Location: ' . admin_url('index.php?menu=pembelian/penerimaan/detail&id=' . $id_penerimaan_pembelian));
    exit;
}