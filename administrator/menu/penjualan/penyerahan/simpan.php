<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/kode.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

require_once __DIR__ . '/../_fungsi_penjualan.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_admin('penjualan/penyerahan');
}

function penyerahan_redirect_tambah(?int $id_pesanan = null): void
{
    $url = admin_url('index.php?menu=penjualan/penyerahan/tambah');
    if ($id_pesanan !== null && $id_pesanan > 0) {
        $url .= '&id_pesanan_penjualan=' . $id_pesanan;
    }

    header('Location: ' . $url);
    exit;
}

function penyerahan_parse_int($value): int
{
    return max(0, (int) preg_replace('/[^0-9]/', '', (string) $value));
}

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_pengguna = (int) (user_login()['id_pengguna'] ?? 0);

$tanggal_penyerahan = trim((string) ($_POST['tanggal_penyerahan'] ?? ''));
$id_pesanan_penjualan = (int) ($_POST['id_pesanan_penjualan'] ?? 0);
$id_pelanggan = (int) ($_POST['id_pelanggan'] ?? 0);
$id_gudang = (int) ($_POST['id_gudang'] ?? 0);
$jenis_alamat_tujuan = strtolower(trim((string) ($_POST['jenis_alamat_tujuan'] ?? 'pelanggan')));
$alamat_tujuan = trim((string) ($_POST['alamat_tujuan'] ?? ''));
$kurir_pengiriman = trim((string) ($_POST['kurir_pengiriman'] ?? ''));
$no_resi_pengiriman = trim((string) ($_POST['no_resi_pengiriman'] ?? ''));
$biaya_pengiriman = penjualan_parse_decimal_umum($_POST['biaya_pengiriman'] ?? 0);
$id_coa_biaya_pengiriman = (int) ($_POST['id_coa_biaya_pengiriman'] ?? 0);
$catatan = trim((string) ($_POST['catatan'] ?? ''));
$detail = $_POST['detail'] ?? [];

if ($tanggal_penyerahan === '') {
    set_flash('error', 'Tanggal penyerahan wajib diisi.');
    penyerahan_redirect_tambah($id_pesanan_penjualan);
}

if ($id_pesanan_penjualan <= 0) {
    set_flash('error', 'Penyerahan penjualan wajib berasal dari Pesanan Penjualan.');
    penyerahan_redirect_tambah();
}

if ($id_gudang <= 0) {
    set_flash('error', 'Gudang wajib dipilih.');
    penyerahan_redirect_tambah($id_pesanan_penjualan);
}

if (!in_array($jenis_alamat_tujuan, ['pelanggan', 'toko', 'lain'], true)) {
    set_flash('error', 'Jenis alamat tujuan tidak valid.');
    penyerahan_redirect_tambah($id_pesanan_penjualan);
}

if ($alamat_tujuan === '') {
    set_flash('error', 'Alamat tujuan wajib diisi untuk surat jalan.');
    penyerahan_redirect_tambah($id_pesanan_penjualan);
}

$pesanan = Capsule::table('tb_pesanan_penjualan')
    ->where('id_entitas', $id_entitas)
    ->where('id_pesanan_penjualan', $id_pesanan_penjualan)
    ->where('status_pesanan', 'terkonfirmasi')
    ->first();

if (!$pesanan) {
    set_flash('error', 'Pesanan penjualan tidak valid, tidak ditemukan, atau belum terkonfirmasi.');
    penyerahan_redirect_tambah($id_pesanan_penjualan);
}

if ($id_pelanggan <= 0) {
    $id_pelanggan = (int) $pesanan->id_pelanggan;
}

if ((int) $pesanan->id_pelanggan !== $id_pelanggan) {
    set_flash('error', 'Pelanggan penyerahan tidak sesuai dengan pesanan penjualan.');
    penyerahan_redirect_tambah($id_pesanan_penjualan);
}

$gudangValid = Capsule::table('tb_gudang')
    ->where('id_entitas', $id_entitas)
    ->where('id_gudang', $id_gudang)
    ->where('status_aktif', 1)
    ->exists();

if (!$gudangValid) {
    set_flash('error', 'Gudang tidak valid atau tidak aktif.');
    penyerahan_redirect_tambah($id_pesanan_penjualan);
}

$sudahDipakai = Capsule::table('tb_penyerahan_penjualan')
    ->where('id_entitas', $id_entitas)
    ->where('id_pesanan_penjualan', $id_pesanan_penjualan)
    ->whereIn('status_penyerahan', ['draft', 'posted'])
    ->exists();

if ($sudahDipakai) {
    set_flash('error', 'Pesanan ini sudah dipakai pada penyerahan lain.');
    penyerahan_redirect_tambah($id_pesanan_penjualan);
}

if (!is_array($detail) || count($detail) === 0) {
    set_flash('error', 'Minimal harus ada 1 detail produk.');
    penyerahan_redirect_tambah($id_pesanan_penjualan);
}

try {
    $id_baru = null;

    Capsule::connection()->transaction(function () use (
        &$id_baru,
        $id_entitas,
        $id_pengguna,
        $tanggal_penyerahan,
        $id_pesanan_penjualan,
        $id_pelanggan,
        $id_gudang,
        $jenis_alamat_tujuan,
        $alamat_tujuan,
        $kurir_pengiriman,
        $no_resi_pengiriman,
        $biaya_pengiriman,
        $id_coa_biaya_pengiriman,
        $catatan,
        $detail
    ) {
        $detailValid = [];
        $cekDuplikat = [];

        foreach ($detail as $baris) {
            $id_produk = (int) ($baris['id_produk'] ?? 0);
            $qty = penyerahan_parse_int($baris['qty'] ?? 0);

            if ($id_produk <= 0) {
                throw new RuntimeException('Produk wajib dipilih.');
            }

            if (in_array($id_produk, $cekDuplikat, true)) {
                throw new RuntimeException('Produk tidak boleh duplikat dalam satu penyerahan.');
            }

            if ($qty <= 0) {
                throw new RuntimeException('Qty kirim wajib lebih besar dari 0.');
            }

            $detailPesanan = Capsule::table('tb_pesanan_penjualan_detail')
                ->where('id_pesanan_penjualan', $id_pesanan_penjualan)
                ->where('id_produk', $id_produk)
                ->first();

            if (!$detailPesanan) {
                throw new RuntimeException('Produk yang dipilih tidak ada pada pesanan penjualan.');
            }

            $qtyPesanan = (float) $detailPesanan->qty;
            if ($qty > $qtyPesanan) {
                throw new RuntimeException('Qty kirim tidak boleh melebihi qty pesanan.');
            }

            $saldo = Capsule::table('tb_saldo_stok')
                ->where('id_entitas', $id_entitas)
                ->where('jenis_barang', 'produk')
                ->where('id_referensi_barang', $id_produk)
                ->where('id_gudang', $id_gudang)
                ->where('qty_saldo', '>', 0)
                ->first();

            if (!$saldo) {
                throw new RuntimeException('Produk pada pesanan tidak memiliki saldo stok di gudang terpilih.');
            }

            $qtySaldo = (float) $saldo->qty_saldo;
            if ($qty > $qtySaldo) {
                throw new RuntimeException('Qty kirim tidak boleh melebihi stok gudang.');
            }

            $hppSatuan = (float) $saldo->hpp_rata_rata;
            $hppTotal = round($qty * $hppSatuan, 2);

            $detailValid[] = [
                'id_produk' => $id_produk,
                'qty' => $qty,
                'hpp_satuan' => round($hppSatuan, 2),
                'hpp_total' => $hppTotal,
                'catatan' => trim((string) ($baris['catatan'] ?? '')),
            ];

            $cekDuplikat[] = $id_produk;
        }

        if (count($detailValid) === 0) {
            throw new RuntimeException('Minimal harus ada 1 detail produk yang valid.');
        }

        $no_penyerahan = generate_kode_master(
            'tb_penyerahan_penjualan',
            'no_penyerahan_penjualan',
            'PNJ',
            4,
            $id_entitas
        );

        $id_baru = Capsule::table('tb_penyerahan_penjualan')->insertGetId([
            'id_entitas' => $id_entitas,
            'no_penyerahan_penjualan' => $no_penyerahan,
            'tanggal_penyerahan' => $tanggal_penyerahan,
            'id_pesanan_penjualan' => $id_pesanan_penjualan,
            'id_pelanggan' => $id_pelanggan,
            'id_gudang' => $id_gudang,
            'jenis_alamat_tujuan' => $jenis_alamat_tujuan,
            'alamat_tujuan' => $alamat_tujuan,
            'kurir_pengiriman' => $kurir_pengiriman !== '' ? $kurir_pengiriman : null,
            'no_resi_pengiriman' => $no_resi_pengiriman !== '' ? $no_resi_pengiriman : null,
            'biaya_pengiriman' => round($biaya_pengiriman, 2),
            'id_coa_biaya_pengiriman' => $biaya_pengiriman > 0 ? ($id_coa_biaya_pengiriman > 0 ? $id_coa_biaya_pengiriman : null) : null,
            'status_penyerahan' => 'draft',
            'catatan' => $catatan !== '' ? $catatan : null,
            'tanggal_dibuat' => date('Y-m-d H:i:s'),
            'dibuat_oleh' => $id_pengguna ?: null,
        ]);

        foreach ($detailValid as $d) {
            Capsule::table('tb_penyerahan_penjualan_detail')->insert([
                'id_penyerahan_penjualan' => $id_baru,
                'id_produk' => $d['id_produk'],
                'qty' => $d['qty'],
                'hpp_satuan' => $d['hpp_satuan'],
                'hpp_total' => $d['hpp_total'],
                'catatan' => $d['catatan'] !== '' ? $d['catatan'] : null,
            ]);
        }

        Capsule::table('tb_pesanan_penjualan')
            ->where('id_entitas', $id_entitas)
            ->where('id_pesanan_penjualan', $id_pesanan_penjualan)
            ->update([
                'status_pesanan' => 'diproses',
                'tanggal_diubah' => date('Y-m-d H:i:s'),
                'diubah_oleh' => $id_pengguna ?: null,
            ]);
    });

    set_flash('success', 'Draft penyerahan penjualan berhasil dibuat.');
    header('Location: ' . admin_url('index.php?menu=penjualan/penyerahan/detail&id=' . (int) $id_baru));
    exit;
} catch (Throwable $e) {
    set_flash('error', $e->getMessage());
    penyerahan_redirect_tambah($id_pesanan_penjualan);
}