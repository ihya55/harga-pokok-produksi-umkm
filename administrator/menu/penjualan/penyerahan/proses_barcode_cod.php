<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';
require_once __DIR__ . '/../_fungsi_penjualan.php';
require_once __DIR__ . '/../_template_jurnal_penjualan.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_pengguna = (int) (user_login()['id_pengguna'] ?? 0);
$kode = trim((string) ($_POST['kode_pesanan_online'] ?? ''));
$id_gudang = (int) ($_POST['id_gudang'] ?? 0);
$jumlah_diterima = penjualan_parse_number($_POST['jumlah_diterima'] ?? 0);
$id_penyerahan_baru = 0;


function cod_default_akun_kas(int $id_entitas): int
{
    $akun = Capsule::table('tb_coa')
        ->where('id_entitas', $id_entitas)
        ->where('boleh_transaksi', 1)
        ->where('status_aktif', 1)
        ->where(function ($q) {
            $q->where('nama_coa', 'like', '%Kas%')
              ->orWhere('kode_coa', '1110')
              ->orWhere('kode_coa', '1101');
        })
        ->orderByRaw("CASE WHEN kode_coa = '1110' THEN 0 WHEN nama_coa LIKE '%Kas%' THEN 1 ELSE 2 END")
        ->first();
    if (!$akun) {
        $akun = Capsule::table('tb_coa')
            ->where('id_entitas', $id_entitas)
            ->where('kategori_coa', 'aset')
            ->where('boleh_transaksi', 1)
            ->where('status_aktif', 1)
            ->orderBy('kode_coa')
            ->first();
    }
    if (!$akun) {
        throw new RuntimeException('Akun kas untuk pembayaran COD belum tersedia di COA.');
    }
    return (int) $akun->id_coa;
}

function cod_redirect_error(string $message): void
{
    set_flash('error', $message);
    header('Location: ' . admin_url('index.php?menu=penjualan/penyerahan/tambah'));
    exit;
}

try {
    if ($kode === '') {
        throw new RuntimeException('No pesanan online wajib diisi.');
    }
    if ($id_gudang <= 0) {
        throw new RuntimeException('Gudang wajib dipilih sebelum proses COD.');
    }

    Capsule::connection()->transaction(function () use ($id_entitas, $id_pengguna, $kode, $id_gudang, $jumlah_diterima, &$id_penyerahan_baru) {
        $pesanan = Capsule::table('tb_pesanan_penjualan as ps')
            ->join('tb_pelanggan as pl', 'pl.id_pelanggan', '=', 'ps.id_pelanggan')
            ->where('ps.id_entitas', $id_entitas)
            ->where('ps.sumber_pesanan', 'website')
            ->where('ps.no_pesanan_penjualan', $kode)
            ->lockForUpdate()
            ->select(['ps.*', 'pl.alamat as alamat_pelanggan'])
            ->first();

        if (!$pesanan) {
            throw new RuntimeException('Pesanan online tidak ditemukan.');
        }
        if ((string) ($pesanan->metode_pembayaran_online ?? '') !== 'cod') {
            throw new RuntimeException('Proses pembayaran kasir ini hanya untuk pesanan online COD.');
        }
        if (in_array((string) $pesanan->status_pesanan, ['batal', 'selesai'], true)) {
            throw new RuntimeException('Pesanan sudah batal/selesai.');
        }
        $total = round((float) ($pesanan->nominal_pembayaran_online ?? $pesanan->total ?? 0), 2);
        if ($total <= 0) {
            throw new RuntimeException('Total pesanan tidak valid.');
        }
        if ($jumlah_diterima + 0.000001 < $total) {
            throw new RuntimeException('Uang diterima kurang dari total pesanan.');
        }

        $gudang = Capsule::table('tb_gudang')
            ->where('id_entitas', $id_entitas)
            ->where('id_gudang', $id_gudang)
            ->where('status_aktif', 1)
            ->first();
        if (!$gudang) {
            throw new RuntimeException('Gudang tidak valid atau tidak aktif.');
        }

        $sudahAda = Capsule::table('tb_penyerahan_penjualan')
            ->where('id_entitas', $id_entitas)
            ->where('id_pesanan_penjualan', (int) $pesanan->id_pesanan_penjualan)
            ->whereIn('status_penyerahan', ['draft', 'posted'])
            ->exists();
        if ($sudahAda) {
            throw new RuntimeException('Pesanan ini sudah memiliki penyerahan penjualan.');
        }

        $detailPesanan = Capsule::table('tb_pesanan_penjualan_detail as d')
            ->join('tb_produk as p', 'p.id_produk', '=', 'd.id_produk')
            ->where('d.id_pesanan_penjualan', (int) $pesanan->id_pesanan_penjualan)
            ->select(['d.*', 'p.kode_produk', 'p.nama_produk'])
            ->orderBy('d.id_pesanan_penjualan_detail')
            ->get();
        if ($detailPesanan->count() === 0) {
            throw new RuntimeException('Detail pesanan masih kosong.');
        }

        $noPenyerahan = penjualan_generate_no_penyerahan($id_entitas);
        $tanggal = date('Y-m-d');
        $id_penyerahan_baru = (int) Capsule::table('tb_penyerahan_penjualan')->insertGetId([
            'id_entitas' => $id_entitas,
            'no_penyerahan_penjualan' => $noPenyerahan,
            'tanggal_penyerahan' => $tanggal,
            'id_pesanan_penjualan' => (int) $pesanan->id_pesanan_penjualan,
            'id_pelanggan' => (int) $pesanan->id_pelanggan,
            'id_gudang' => $id_gudang,
            'jenis_alamat_tujuan' => 'pelanggan',
            'alamat_tujuan' => (string) ($pesanan->alamat_pelanggan ?? '-'),
            'status_penyerahan' => 'posted',
            'catatan' => 'Otomatis dari scan barcode pesanan online COD ' . (string) $pesanan->no_pesanan_penjualan,
            'tanggal_dibuat' => date('Y-m-d H:i:s'),
            'dibuat_oleh' => $id_pengguna ?: null,
            'tanggal_posting' => date('Y-m-d H:i:s'),
            'diposting_oleh' => $id_pengguna ?: null,
        ]);

        $totalHpp = 0.0;
        foreach ($detailPesanan as $d) {
            $idProduk = (int) $d->id_produk;
            $qty = (float) $d->qty;
            $saldoInfo = penjualan_update_saldo_produk_keluar($id_entitas, $id_gudang, $idProduk, $qty, $id_pengguna);
            $hppSatuan = (float) $saldoInfo['hpp_satuan'];
            $hppTotal = (float) $saldoInfo['hpp_total'];
            $totalHpp += $hppTotal;

            Capsule::table('tb_penyerahan_penjualan_detail')->insert([
                'id_penyerahan_penjualan' => $id_penyerahan_baru,
                'id_produk' => $idProduk,
                'qty' => $qty,
                'hpp_satuan' => $hppSatuan,
                'hpp_total' => $hppTotal,
                'catatan' => $d->catatan ?? null,
            ]);

            penjualan_insert_mutasi_produk_keluar([
                'id_entitas' => $id_entitas,
                'tanggal_mutasi' => $tanggal . ' ' . date('H:i:s'),
                'id_produk' => $idProduk,
                'id_gudang' => $id_gudang,
                'qty_keluar' => $qty,
                'harga_satuan' => $hppSatuan,
                'nilai_total' => $hppTotal,
                'tabel_sumber' => 'tb_penyerahan_penjualan',
                'id_sumber' => $id_penyerahan_baru,
                'no_sumber' => $noPenyerahan,
                'keterangan' => 'Penyerahan otomatis COD pesanan online ' . (string) $pesanan->no_pesanan_penjualan,
                'dibuat_oleh' => $id_pengguna ?: null,
            ]);
        }

        penjualan_buat_jurnal_penyerahan_hpp([
            'id_entitas' => $id_entitas,
            'id_pengguna' => $id_pengguna,
            'tanggal_penyerahan' => $tanggal,
            'id_penyerahan_penjualan' => $id_penyerahan_baru,
            'no_penyerahan_penjualan' => $noPenyerahan,
            'id_pelanggan' => (int) $pesanan->id_pelanggan,
            'id_gudang' => $id_gudang,
            'total_hpp' => round($totalHpp, 2),
        ]);

        $noFaktur = penjualan_generate_no_faktur($id_entitas);
        $idFaktur = (int) Capsule::table('tb_faktur_penjualan')->insertGetId([
            'id_entitas' => $id_entitas,
            'no_faktur_penjualan' => $noFaktur,
            'tanggal_faktur' => $tanggal,
            'id_pelanggan' => (int) $pesanan->id_pelanggan,
            'id_penyerahan_penjualan' => $id_penyerahan_baru,
            'sumber_faktur' => 'online_cod',
            'is_pos' => 0,
            'jenis_pembayaran' => 'tunai',
            'status_faktur' => 'posted',
            'jatuh_tempo' => null,
            'subtotal' => (float) $pesanan->subtotal,
            'diskon' => (float) $pesanan->diskon,
            'ppn' => (float) $pesanan->ppn,
            'ppn_persen' => (float) $pesanan->ppn_persen,
            'total' => $total,
            'sisa_piutang' => 0,
            'catatan' => 'Transaksi penjualan otomatis dari COD pesanan online ' . (string) $pesanan->no_pesanan_penjualan,
            'tanggal_dibuat' => date('Y-m-d H:i:s'),
            'dibuat_oleh' => $id_pengguna ?: null,
            'tanggal_posting' => date('Y-m-d H:i:s'),
            'diposting_oleh' => $id_pengguna ?: null,
        ]);

        foreach ($detailPesanan as $d) {
            Capsule::table('tb_faktur_penjualan_detail')->insert([
                'id_faktur_penjualan' => $idFaktur,
                'id_produk' => (int) $d->id_produk,
                'qty' => (float) $d->qty,
                'harga' => (float) $d->harga,
                'diskon' => (float) $d->diskon,
                'subtotal' => (float) $d->subtotal,
            ]);
        }

        penjualan_buat_jurnal_faktur([
            'id_entitas' => $id_entitas,
            'id_pengguna' => $id_pengguna,
            'tanggal_faktur' => $tanggal,
            'id_faktur_penjualan' => $idFaktur,
            'no_faktur_penjualan' => $noFaktur,
            'id_pelanggan' => (int) $pesanan->id_pelanggan,
            'jenis_pembayaran' => 'tunai',
            'subtotal' => (float) $pesanan->subtotal,
            'diskon' => (float) $pesanan->diskon,
            'ppn' => (float) $pesanan->ppn,
            'total' => $total,
        ]);

        Capsule::table('tb_pembayaran_penjualan')->insert([
            'id_entitas' => $id_entitas,
            'no_pembayaran_penjualan' => penjualan_generate_no_pembayaran($id_entitas),
            'tanggal_pembayaran' => $tanggal,
            'id_faktur_penjualan' => $idFaktur,
            'id_pelanggan' => (int) $pesanan->id_pelanggan,
            'metode_pembayaran' => 'tunai',
            'id_coa_kas_bank' => cod_default_akun_kas($id_entitas),
            'jumlah_bayar' => $total,
            'catatan' => 'Pembayaran COD diterima kasir. Uang diterima: Rp ' . number_format($jumlah_diterima, 2, '.', ',') . ', kembalian: Rp ' . number_format($jumlah_diterima - $total, 2, '.', ','),
            'status_posting' => 'posted',
            'tanggal_dibuat' => date('Y-m-d H:i:s'),
            'dibuat_oleh' => $id_pengguna ?: null,
            'tanggal_diubah' => date('Y-m-d H:i:s'),
            'diubah_oleh' => $id_pengguna ?: null,
        ]);

        Capsule::table('tb_pesanan_penjualan')
            ->where('id_entitas', $id_entitas)
            ->where('id_pesanan_penjualan', (int) $pesanan->id_pesanan_penjualan)
            ->update([
                'status_pesanan' => 'selesai',
                'status_pembayaran_online' => 'lunas',
                'tanggal_diubah' => date('Y-m-d H:i:s'),
                'diubah_oleh' => $id_pengguna ?: null,
            ]);
    });

    set_flash('success', 'COD berhasil diproses. Uang sudah diterima, barang diserahkan, stok berkurang, dan jurnal otomatis dibuat.');
    header('Location: ' . admin_url('index.php?menu=penjualan/penyerahan/detail&id=' . $id_penyerahan_baru));
    exit;
} catch (Throwable $e) {
    cod_redirect_error($e->getMessage());
}
