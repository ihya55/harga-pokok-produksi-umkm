<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

require_once __DIR__ . '/../../../../orm/SaldoAwalStokORM.php';
require_once __DIR__ . '/../../../../orm/SaldoAwalStokDetailORM.php';
require_once __DIR__ . '/../../../../orm/SaldoStokORM.php';
require_once __DIR__ . '/../../../../orm/MutasiStokORM.php';
require_once __DIR__ . '/../../../../orm/BahanBakuORM.php';
require_once __DIR__ . '/../../../../orm/ProdukORM.php';
require_once __DIR__ . '/../../../../orm/JurnalORM.php';
require_once __DIR__ . '/../../../../orm/JurnalDetailORM.php';
require_once __DIR__ . '/../../../../orm/LogJurnalSumberORM.php';
require_once __DIR__ . '/../../../../orm/PeriodeAkuntansiORM.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

$user_login = user_login();
$id_entitas = (int) ($user_login['id_entitas'] ?? 0);
$id_pengguna = (int) ($user_login['id_pengguna'] ?? 0);

$id_saldo_awal_stok = (int) ($_GET['id'] ?? 0);
$back_url = trim((string) ($_GET['back_url'] ?? ''));

if ($back_url === '') {
    $back_url = admin_page_url('persediaan/saldo-stok/saldo-awal');
}

function generate_no_jurnal_saldo_awal_stok(int $id_entitas): string
{
    $prefix = 'JRN-';
    $rows = Capsule::table('tb_jurnal')
        ->where('id_entitas', $id_entitas)
        ->where('no_jurnal', 'like', $prefix . '%')
        ->pluck('no_jurnal');

    $max = 0;
    foreach ($rows as $code) {
        if (preg_match('/^JRN-(\d+)$/', (string) $code, $matches)) {
            $max = max($max, (int) $matches[1]);
        }
    }

    return $prefix . str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
}

if ($id_saldo_awal_stok <= 0) {
    set_flash('error', 'ID saldo awal stok tidak valid.');
    header('Location: ' . $back_url);
    exit;
}

try {
    Capsule::connection()->transaction(function () use ($id_entitas, $id_pengguna, $id_saldo_awal_stok) {
        $header = SaldoAwalStokORM::query()
            ->where('id_entitas', $id_entitas)
            ->where('id_saldo_awal_stok', $id_saldo_awal_stok)
            ->lockForUpdate()
            ->first();

        if (!$header) {
            throw new Exception('Data saldo awal stok tidak ditemukan.');
        }

        if ((string) $header->status_posting !== 'draft') {
            throw new Exception('Saldo awal stok sudah pernah diposting.');
        }

        $detail_rows = SaldoAwalStokDetailORM::query()
            ->where('id_saldo_awal_stok', $id_saldo_awal_stok)
            ->orderBy('id_saldo_awal_stok_detail', 'asc')
            ->get();

        if ($detail_rows->count() <= 0) {
            throw new Exception('Detail saldo awal stok masih kosong.');
        }

        $periode = PeriodeAkuntansiORM::query()
            ->where('id_entitas', $id_entitas)
            ->where('tanggal_mulai', '<=', $header->tanggal_saldo_awal)
            ->where('tanggal_selesai', '>=', $header->tanggal_saldo_awal)
            ->where('status_periode', 'terbuka')
            ->first();

        if (!$periode) {
            throw new Exception('Periode akuntansi untuk tanggal saldo awal belum dibuka atau sudah ditutup.');
        }

        $total_nilai = 0;
        $urutan_jurnal = 1;
        $jurnal_detail_debit = [];

        foreach ($detail_rows as $detail) {
            $jenis_barang = (string) $detail->jenis_barang;
            $id_referensi_barang = (int) $detail->id_referensi_barang;
            $qty_awal = (float) $detail->qty_awal;
            $harga_satuan = (float) $detail->harga_satuan;
            $nilai_total = round((float) $detail->nilai_total, 2);

            if (!in_array($jenis_barang, ['bahan_baku', 'produk'], true)) {
                throw new Exception('Jenis barang tidak valid pada detail.');
            }

            if ($id_referensi_barang <= 0 || $qty_awal <= 0 || $nilai_total <= 0) {
                throw new Exception('Detail saldo awal tidak valid. Pastikan barang, qty, dan nilai terisi.');
            }

            $id_coa_persediaan = 0;
            $id_produk = null;
            $id_bahan_baku = null;
            $nama_barang = '';

            if ($jenis_barang === 'bahan_baku') {
                $barang = BahanBakuORM::query()
                    ->where('id_entitas', $id_entitas)
                    ->where('id_bahan_baku', $id_referensi_barang)
                    ->first();

                if (!$barang) {
                    throw new Exception('Bahan baku pada detail tidak ditemukan.');
                }

                $id_coa_persediaan = (int) ($barang->id_coa_persediaan ?? 0);
                $id_bahan_baku = $id_referensi_barang;
                $nama_barang = (string) ($barang->nama_bahan_baku ?? 'Bahan Baku');
            }

            if ($jenis_barang === 'produk') {
                $barang = ProdukORM::query()
                    ->where('id_entitas', $id_entitas)
                    ->where('id_produk', $id_referensi_barang)
                    ->first();

                if (!$barang) {
                    throw new Exception('Produk pada detail tidak ditemukan.');
                }

                $id_coa_persediaan = (int) ($barang->id_coa_persediaan ?? 0);
                $id_produk = $id_referensi_barang;
                $nama_barang = (string) ($barang->nama_produk ?? 'Produk');
            }

            if ($id_coa_persediaan <= 0) {
                throw new Exception('Akun persediaan untuk barang "' . $nama_barang . '" belum diatur.');
            }

            MutasiStokORM::create([
                'id_entitas' => $id_entitas,
                'tanggal_mutasi' => $header->tanggal_saldo_awal . ' 00:00:00',
                'jenis_barang' => $jenis_barang,
                'id_referensi_barang' => $id_referensi_barang,
                'id_gudang' => (int) $header->id_gudang,
                'jenis_mutasi' => 'saldo_awal',
                'qty_masuk' => $qty_awal,
                'qty_keluar' => 0,
                'harga_satuan' => $harga_satuan,
                'nilai_total' => $nilai_total,
                'tabel_sumber' => 'tb_saldo_awal_stok',
                'id_sumber' => (int) $header->id_saldo_awal_stok,
                'no_sumber' => (string) $header->no_saldo_awal_stok,
                'keterangan' => 'Posting saldo awal stok',
                'tanggal_dibuat' => date('Y-m-d H:i:s'),
                'dibuat_oleh' => $id_pengguna ?: null,
            ]);

            $saldo = SaldoStokORM::query()
                ->where('id_entitas', $id_entitas)
                ->where('jenis_barang', $jenis_barang)
                ->where('id_referensi_barang', $id_referensi_barang)
                ->where('id_gudang', (int) $header->id_gudang)
                ->lockForUpdate()
                ->first();

            if ($saldo) {
                $qty_lama = (float) $saldo->qty_saldo;
                $nilai_lama = (float) $saldo->nilai_saldo;

                $qty_baru = $qty_lama + $qty_awal;
                $nilai_baru = $nilai_lama + $nilai_total;
                $hpp_baru = $qty_baru > 0 ? round($nilai_baru / $qty_baru, 2) : 0;

                $saldo->qty_saldo = $qty_baru;
                $saldo->nilai_saldo = $nilai_baru;
                $saldo->hpp_rata_rata = $hpp_baru;
                $saldo->tanggal_update = date('Y-m-d H:i:s');
                $saldo->tanggal_diubah = date('Y-m-d H:i:s');
                $saldo->diubah_oleh = $id_pengguna ?: null;
                $saldo->save();
            } else {
                SaldoStokORM::create([
                    'id_entitas' => $id_entitas,
                    'jenis_barang' => $jenis_barang,
                    'id_referensi_barang' => $id_referensi_barang,
                    'id_gudang' => (int) $header->id_gudang,
                    'qty_saldo' => $qty_awal,
                    'nilai_saldo' => $nilai_total,
                    'hpp_rata_rata' => $qty_awal > 0 ? round($nilai_total / $qty_awal, 2) : 0,
                    'tanggal_update' => date('Y-m-d H:i:s'),
                    'tanggal_dibuat' => date('Y-m-d H:i:s'),
                    'dibuat_oleh' => $id_pengguna ?: null,
                ]);
            }

            $jurnal_detail_debit[] = [
                'urutan' => $urutan_jurnal++,
                'id_coa' => $id_coa_persediaan,
                'debit' => $nilai_total,
                'kredit' => 0,
                'keterangan_baris' => 'Saldo awal persediaan - ' . $nama_barang,
                'id_pelanggan' => null,
                'id_pemasok' => null,
                'id_produk' => $id_produk,
                'id_bahan_baku' => $id_bahan_baku,
                'id_gudang' => (int) $header->id_gudang,
            ];

            $total_nilai += $nilai_total;
        }

        if ($total_nilai <= 0) {
            throw new Exception('Total nilai saldo awal tidak valid.');
        }

        if ((int) $header->id_coa_lawan <= 0) {
            throw new Exception('Akun lawan / ekuitas belum dipilih.');
        }

        $no_jurnal = generate_no_jurnal_saldo_awal_stok($id_entitas);

        $jurnal = JurnalORM::create([
            'id_entitas' => $id_entitas,
            'no_jurnal' => $no_jurnal,
            'tanggal_jurnal' => $header->tanggal_saldo_awal,
            'id_periode' => (int) $periode->id_periode,
            'kode_jenis_transaksi' => 'SALDO_AWAL_STOK',
            'keterangan' => 'Posting saldo awal stok ' . $header->no_saldo_awal_stok,
            'tabel_sumber' => 'tb_saldo_awal_stok',
            'id_sumber' => (int) $header->id_saldo_awal_stok,
            'no_sumber' => (string) $header->no_saldo_awal_stok,
            'status_jurnal' => 'posted',
            'total_debit' => $total_nilai,
            'total_kredit' => $total_nilai,
            'tanggal_dibuat' => date('Y-m-d H:i:s'),
            'dibuat_oleh' => $id_pengguna ?: null,
            'tanggal_posting' => date('Y-m-d H:i:s'),
            'diposting_oleh' => $id_pengguna ?: null,
        ]);

        foreach ($jurnal_detail_debit as $baris) {
            $baris['id_jurnal'] = (int) $jurnal->id_jurnal;
            JurnalDetailORM::create($baris);
        }

        JurnalDetailORM::create([
            'id_jurnal' => (int) $jurnal->id_jurnal,
            'urutan' => $urutan_jurnal,
            'id_coa' => (int) $header->id_coa_lawan,
            'debit' => 0,
            'kredit' => $total_nilai,
            'keterangan_baris' => 'Lawan saldo awal stok ' . $header->no_saldo_awal_stok,
            'id_pelanggan' => null,
            'id_pemasok' => null,
            'id_produk' => null,
            'id_bahan_baku' => null,
            'id_gudang' => (int) $header->id_gudang,
        ]);

        LogJurnalSumberORM::create([
            'id_entitas' => $id_entitas,
            'id_jurnal' => (int) $jurnal->id_jurnal,
            'tabel_sumber' => 'tb_saldo_awal_stok',
            'id_sumber' => (int) $header->id_saldo_awal_stok,
            'no_sumber' => (string) $header->no_saldo_awal_stok,
            'kode_jenis_transaksi' => 'SALDO_AWAL_STOK',
            'tanggal_dibuat' => date('Y-m-d H:i:s'),
        ]);

        $header->total_nilai = $total_nilai;
        $header->status_posting = 'posted';
        $header->tanggal_posting = date('Y-m-d H:i:s');
        $header->diposting_oleh = $id_pengguna ?: null;
        $header->tanggal_diubah = date('Y-m-d H:i:s');
        $header->diubah_oleh = $id_pengguna ?: null;
        $header->save();
    });

    set_flash('success', 'Saldo awal stok berhasil diposting. Mutasi stok, saldo stok, dan jurnal berhasil dibuat.');
    header('Location: ' . admin_page_url('persediaan/saldo-stok/saldo-awal/detail') . '&id=' . $id_saldo_awal_stok . '&back_url=' . urlencode($back_url));
    exit;
} catch (Throwable $e) {
    set_flash('error', 'Gagal posting saldo awal stok: ' . $e->getMessage());
    header('Location: ' . admin_page_url('persediaan/saldo-stok/saldo-awal/detail') . '&id=' . $id_saldo_awal_stok . '&back_url=' . urlencode($back_url));
    exit;
}