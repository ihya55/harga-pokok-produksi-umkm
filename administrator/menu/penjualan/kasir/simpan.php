<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';
require_once __DIR__ . '/../../../../helpers/pajak.php';

require_once __DIR__ . '/../_fungsi_penjualan.php';
require_once __DIR__ . '/../_template_jurnal_penjualan.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

$user_login = user_login();
$id_entitas = (int) ($user_login['id_entitas'] ?? 0);
$id_pengguna = (int) ($user_login['id_pengguna'] ?? 0);

$tanggal_transaksi = trim((string) ($_POST['tanggal_transaksi'] ?? ''));
$id_gudang = (int) ($_POST['id_gudang'] ?? 0);
$id_pelanggan = (int) ($_POST['id_pelanggan'] ?? 0);

$jenis_pembayaran = trim((string) ($_POST['jenis_pembayaran'] ?? 'tunai'));
$id_coa_kas_bank = (int) ($_POST['id_coa_kas_bank'] ?? 0);
$no_referensi_transfer = trim((string) ($_POST['no_referensi_transfer'] ?? ''));
$nama_pengirim_transfer = trim((string) ($_POST['nama_pengirim_transfer'] ?? ''));
$bukti_bayar_pos = null;

function pos_get_id_coa_debit_template(int $id_entitas, string $kode_jenis_transaksi): int
{
    $row = Capsule::table('tb_template_jurnal as t')
        ->join('tb_template_jurnal_detail as d', 'd.id_template_jurnal', '=', 't.id_template_jurnal')
        ->where('t.id_entitas', $id_entitas)
        ->where('t.kode_jenis_transaksi', $kode_jenis_transaksi)
        ->where('t.status_aktif', 1)
        ->where('d.posisi_dc', 'debit')
        ->where('d.sumber_akun', 'coa_default')
        ->orderBy('d.urutan', 'asc')
        ->select('d.id_coa_default')
        ->first();

    return $row ? (int) $row->id_coa_default : 0;
}

function pos_template_faktur_by_jenis(string $jenis_pembayaran): string
{
    if ($jenis_pembayaran === 'transfer') {
        return 'FAKTUR_PENJUALAN_TRANSFER';
    }

    if ($jenis_pembayaran === 'kredit') {
        return 'FAKTUR_PENJUALAN_KREDIT';
    }

    return 'FAKTUR_PENJUALAN_TUNAI';
}

function pos_template_has_ppn_simpan(int $id_entitas, string $kode_jenis_transaksi): bool
{
    return Capsule::table('tb_template_jurnal as t')
        ->join('tb_template_jurnal_detail as d', 'd.id_template_jurnal', '=', 't.id_template_jurnal')
        ->where('t.id_entitas', $id_entitas)
        ->where('t.kode_jenis_transaksi', $kode_jenis_transaksi)
        ->where('t.status_aktif', 1)
        ->where('d.sumber_nominal', 'rumus_nominal')
        ->where('d.rumus_nominal', 'ppn')
        ->exists();
}

function pos_simpan_bukti_transfer_pos(int $id_entitas): ?string
{
    if (empty($_FILES['bukti_bayar_pos']) || !is_array($_FILES['bukti_bayar_pos'])) {
        return null;
    }

    $file = $_FILES['bukti_bayar_pos'];

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload bukti transfer gagal. Silakan simpan tanpa foto atau coba upload ulang.');
    }

    $ukuran = (int) ($file['size'] ?? 0);
    if ($ukuran > 3 * 1024 * 1024) {
        throw new RuntimeException('Ukuran bukti transfer maksimal 3 MB.');
    }

    $nama_asli = (string) ($file['name'] ?? '');
    $ext = strtolower(pathinfo($nama_asli, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];

    if (!in_array($ext, $allowed, true)) {
        throw new RuntimeException('Format bukti transfer harus JPG, PNG, WEBP, atau PDF.');
    }

    $dir = __DIR__ . '/../../../../uploads/bukti_pembayaran_pos';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $filename = 'bukti_pos_' . $id_entitas . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $target = $dir . '/' . $filename;

    if (!move_uploaded_file((string) $file['tmp_name'], $target)) {
        throw new RuntimeException('Bukti transfer tidak berhasil disimpan.');
    }

    return 'uploads/bukti_pembayaran_pos/' . $filename;
}

$jatuh_tempo = trim((string) ($_POST['jatuh_tempo'] ?? ''));
$sumber_pos = strtolower(trim((string) ($_POST['sumber_pos'] ?? 'toko')));
$catatan = trim((string) ($_POST['catatan'] ?? ''));

$diskon_header = penjualan_parse_number($_POST['diskon_header'] ?? 0);
$ppn_input = penjualan_parse_number($_POST['ppn'] ?? 0);
$ppn_persen_input = penjualan_parse_number($_POST['ppn_persen'] ?? pajak_default_persen($id_entitas, 'PPN', 11.0));
$ppn_persen_input = max(0.0, min(100.0, $ppn_persen_input));

$id_produk_arr = $_POST['id_produk'] ?? [];
$qty_arr = $_POST['qty'] ?? [];
$harga_arr = $_POST['harga'] ?? [];
$diskon_detail_arr = $_POST['diskon_detail'] ?? [];

if ($jenis_pembayaran === 'transfer') {
    $bukti_bayar_pos = pos_simpan_bukti_transfer_pos($id_entitas);
}

if ($id_entitas <= 0) {
    set_flash('error', 'Sesi entitas tidak valid. Silakan login ulang.');
    penjualan_redirect(admin_page_url('penjualan/kasir'));
}

try {
    $id_faktur_baru = 0;

    Capsule::connection()->transaction(function () use (
        $id_entitas,
        $id_pengguna,
        $tanggal_transaksi,
        $id_gudang,
        $id_pelanggan,
        $jenis_pembayaran,
        $id_coa_kas_bank,
        $jatuh_tempo,
        $sumber_pos,
        $catatan,
        $diskon_header,
        $ppn_input,
        $ppn_persen_input,
        $id_produk_arr,
        $qty_arr,
        $harga_arr,
        $diskon_detail_arr,
        $no_referensi_transfer,
        $nama_pengirim_transfer,
        $bukti_bayar_pos,
        &$id_faktur_baru
    ) {
        if ($tanggal_transaksi === '') {
            throw new RuntimeException('Tanggal transaksi wajib diisi.');
        }

        if ($id_gudang <= 0) {
            throw new RuntimeException('Gudang wajib dipilih.');
        }

        penjualan_pastikan_pelanggan($id_entitas, $id_pelanggan);

        $jenis_pembayaran = penjualan_normalize_jenis_pembayaran($jenis_pembayaran);

        $id_coa_kas_bank = pos_get_id_coa_debit_template(
                $id_entitas,
                pos_template_faktur_by_jenis($jenis_pembayaran)
            );

            if (in_array($jenis_pembayaran, ['tunai', 'transfer'], true) && $id_coa_kas_bank <= 0) {
                throw new RuntimeException('COA kas/bank dari template jurnal belum lengkap.');
            }

        if ($jenis_pembayaran === 'transfer') {
            $no_referensi_transfer = strtoupper(trim($no_referensi_transfer));
            $nama_pengirim_transfer = trim($nama_pengirim_transfer);

            if (strlen($no_referensi_transfer) < 4) {
                throw new RuntimeException('Nomor referensi transfer atau minimal 4 digit akhir mutasi wajib diisi.');
            }
        }

        if ($jenis_pembayaran === 'kredit' && $jatuh_tempo === '') {
            throw new RuntimeException('Jatuh tempo wajib diisi untuk transaksi kredit.');
        }

        if (!is_array($id_produk_arr) || count($id_produk_arr) <= 0) {
            throw new RuntimeException('Keranjang produk masih kosong.');
        }

        $detail_valid = [];
        $produk_unik = [];

        foreach ($id_produk_arr as $i => $id_produk_raw) {
            $id_produk = (int) $id_produk_raw;
            $qty = penjualan_parse_number($qty_arr[$i] ?? 0);
            $harga = penjualan_parse_number($harga_arr[$i] ?? 0);
            $diskon_detail = penjualan_parse_number($diskon_detail_arr[$i] ?? 0);

            if ($id_produk <= 0) {
                continue;
            }

            if ($qty <= 0) {
                throw new RuntimeException('Qty produk harus lebih dari nol.');
            }

            if ($harga < 0 || $diskon_detail < 0) {
                throw new RuntimeException('Harga dan diskon tidak boleh minus.');
            }

            if (isset($produk_unik[$id_produk])) {
                throw new RuntimeException('Produk tidak boleh diinput lebih dari satu kali. Gabungkan qty pada baris yang sama.');
            }

            $produk_unik[$id_produk] = true;

            $produk = penjualan_pastikan_produk($id_entitas, $id_produk);
            $saldo = penjualan_pastikan_stok_produk_cukup($id_entitas, $id_gudang, $id_produk, $qty);

            $subtotal_detail = penjualan_hitung_subtotal_detail($qty, $harga, $diskon_detail);

            $detail_valid[] = [
                'id_produk' => $id_produk,
                'qty' => $qty,
                'harga' => $harga,
                'diskon' => $diskon_detail,
                'subtotal' => $subtotal_detail,
                'hpp_satuan' => (float) ($saldo->hpp_rata_rata ?? 0),
                'produk' => $produk,
            ];
        }

        if (count($detail_valid) <= 0) {
            throw new RuntimeException('Tidak ada produk valid di keranjang.');
        }

        $subtotal = 0.0;

        foreach ($detail_valid as $d) {
            $subtotal += (float) $d['subtotal'];
        }

        $kode_template = pos_template_faktur_by_jenis($jenis_pembayaran);
        $ppn_aktif = pos_template_has_ppn_simpan($id_entitas, $kode_template);
        $ppn_persen = $ppn_aktif ? $ppn_persen_input : 0.0;
        $dasar_ppn = max(0.0, $subtotal - $diskon_header);
        $ppn = $ppn_aktif ? round($dasar_ppn * $ppn_persen / 100, 2) : 0.0;
        if ($ppn <= 0 && $ppn_input > 0 && $ppn_aktif) {
            $ppn = round($ppn_input, 2);
        }

        $total = penjualan_hitung_total_header($subtotal, $diskon_header, $ppn);

        if ($total <= 0) {
            throw new RuntimeException('Total transaksi harus lebih dari nol.');
        }

        /*
        |--------------------------------------------------------------------------
        | 1. Buat penyerahan posted
        |--------------------------------------------------------------------------
        */

        $no_penyerahan = penjualan_generate_no_penyerahan($id_entitas);

        $id_penyerahan = (int) Capsule::table('tb_penyerahan_penjualan')->insertGetId([
            'id_entitas' => $id_entitas,
            'no_penyerahan_penjualan' => $no_penyerahan,
            'tanggal_penyerahan' => $tanggal_transaksi,
            'id_pesanan_penjualan' => null,
            'id_pelanggan' => $id_pelanggan,
            'id_gudang' => $id_gudang,
            'status_penyerahan' => 'posted',
            'catatan' => ($sumber_pos === 'aplikasi_lain' ? 'POS aplikasi lain/driver' : 'POS') . ($catatan !== '' ? ': ' . $catatan : ''),
            'tanggal_dibuat' => date('Y-m-d H:i:s'),
            'dibuat_oleh' => $id_pengguna ?: null,
            'tanggal_posting' => date('Y-m-d H:i:s'),
            'diposting_oleh' => $id_pengguna ?: null,
        ]);

        $total_hpp = 0.0;

        foreach ($detail_valid as $d) {
            $hasil_saldo = penjualan_update_saldo_produk_keluar(
                $id_entitas,
                $id_gudang,
                (int) $d['id_produk'],
                (float) $d['qty'],
                $id_pengguna
            );

            $hpp_satuan = (float) $hasil_saldo['hpp_satuan'];
            $hpp_total = (float) $hasil_saldo['hpp_total'];

            Capsule::table('tb_penyerahan_penjualan_detail')->insert([
                'id_penyerahan_penjualan' => $id_penyerahan,
                'id_produk' => (int) $d['id_produk'],
                'qty' => (float) $d['qty'],
                'hpp_satuan' => $hpp_satuan,
                'hpp_total' => $hpp_total,
                'catatan' => null,
            ]);

            penjualan_insert_mutasi_produk_keluar([
                'id_entitas' => $id_entitas,
                'tanggal_mutasi' => $tanggal_transaksi . ' 00:00:00',
                'id_produk' => (int) $d['id_produk'],
                'id_gudang' => $id_gudang,
                'jenis_mutasi' => 'penyerahan_penjualan',
                'qty_keluar' => (float) $d['qty'],
                'harga_satuan' => $hpp_satuan,
                'nilai_total' => $hpp_total,
                'tabel_sumber' => 'tb_penyerahan_penjualan',
                'id_sumber' => $id_penyerahan,
                'no_sumber' => $no_penyerahan,
                'keterangan' => 'POS - penyerahan penjualan',
                'dibuat_oleh' => $id_pengguna,
            ]);

            $total_hpp += $hpp_total;
        }

        if ($total_hpp > 0) {
            penjualan_buat_jurnal_penyerahan_hpp([
                'id_entitas' => $id_entitas,
                'id_pengguna' => $id_pengguna,
                'tanggal_penyerahan' => $tanggal_transaksi,
                'id_penyerahan_penjualan' => $id_penyerahan,
                'no_penyerahan_penjualan' => $no_penyerahan,
                'id_pelanggan' => $id_pelanggan,
                'id_gudang' => $id_gudang,
                'total_hpp' => $total_hpp,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Buat faktur posted
        |--------------------------------------------------------------------------
        */

        $no_faktur = penjualan_generate_no_faktur($id_entitas);
        $sisa_piutang = $jenis_pembayaran === 'kredit' ? $total : 0.0;

        $header_faktur = [
            'id_entitas' => $id_entitas,
            'no_faktur_penjualan' => $no_faktur,
            'tanggal_faktur' => $tanggal_transaksi,
            'id_pelanggan' => $id_pelanggan,
            'id_penyerahan_penjualan' => $id_penyerahan,
            'sumber_faktur' => $sumber_pos === 'aplikasi_lain' ? 'aplikasi_lain' : 'pos',
            'is_pos' => 1,
            'jenis_pembayaran' => $jenis_pembayaran,
            'status_faktur' => 'posted',
            'jatuh_tempo' => $jenis_pembayaran === 'kredit' ? $jatuh_tempo : null,
            'subtotal' => round($subtotal, 2),
            'diskon' => round($diskon_header, 2),
            'ppn' => round($ppn, 2),
            'total' => round($total, 2),
            'sisa_piutang' => round($sisa_piutang, 2),
            'catatan' => ($sumber_pos === 'aplikasi_lain' ? 'POS aplikasi lain/driver' : 'POS') . ($catatan !== '' ? ': ' . $catatan : ''),
            'tanggal_dibuat' => date('Y-m-d H:i:s'),
            'dibuat_oleh' => $id_pengguna ?: null,
            'tanggal_posting' => date('Y-m-d H:i:s'),
            'diposting_oleh' => $id_pengguna ?: null,
        ];

        if (Capsule::schema()->hasColumn('tb_faktur_penjualan', 'ppn_persen')) {
            $header_faktur['ppn_persen'] = round($ppn_persen, 2);
        }

        $id_faktur = (int) Capsule::table('tb_faktur_penjualan')->insertGetId($header_faktur);

        $id_faktur_baru = $id_faktur;

        foreach ($detail_valid as $d) {
            Capsule::table('tb_faktur_penjualan_detail')->insert([
                'id_faktur_penjualan' => $id_faktur,
                'id_produk' => (int) $d['id_produk'],
                'qty' => (float) $d['qty'],
                'harga' => (float) $d['harga'],
                'diskon' => (float) $d['diskon'],
                'subtotal' => (float) $d['subtotal'],
            ]);
        }

        penjualan_buat_jurnal_faktur([
            'id_entitas' => $id_entitas,
            'id_pengguna' => $id_pengguna,
            'tanggal_faktur' => $tanggal_transaksi,
            'id_faktur_penjualan' => $id_faktur,
            'no_faktur_penjualan' => $no_faktur,
            'id_pelanggan' => $id_pelanggan,
            'jenis_pembayaran' => $jenis_pembayaran,
            'subtotal' => $subtotal,
            'diskon' => $diskon_header,
            'ppn' => $ppn,
            'total' => $total,
        ]);

        /*
        |--------------------------------------------------------------------------
        | 3. Catat pembayaran POS tunai/transfer tanpa jurnal terpisah
        |--------------------------------------------------------------------------
        */

        if (in_array($jenis_pembayaran, ['tunai', 'transfer'], true)) {
            $no_pembayaran = penjualan_generate_no_pembayaran($id_entitas);

            $catatan_pembayaran = 'Pembayaran otomatis dari POS. Jurnal kas/bank berasal dari faktur POS.';
            if ($jenis_pembayaran === 'transfer') {
                $catatan_pembayaran .= ' Ref transfer/kode cek: ' . $no_referensi_transfer;
                if ($nama_pengirim_transfer !== '') {
                    $catatan_pembayaran .= '. Pengirim: ' . $nama_pengirim_transfer;
                }
            }

            $data_pembayaran = [
                'id_entitas' => $id_entitas,
                'no_pembayaran_penjualan' => $no_pembayaran,
                'tanggal_pembayaran' => $tanggal_transaksi,
                'id_faktur_penjualan' => $id_faktur,
                'id_pelanggan' => $id_pelanggan,
                'metode_pembayaran' => $jenis_pembayaran,
                'id_coa_kas_bank' => $id_coa_kas_bank > 0 ? $id_coa_kas_bank : null,
                'jumlah_bayar' => round($total, 2),
                'catatan' => $catatan_pembayaran,
                'status_posting' => 'posted',
                'tanggal_dibuat' => date('Y-m-d H:i:s'),
                'dibuat_oleh' => $id_pengguna ?: null,
                'tanggal_diubah' => date('Y-m-d H:i:s'),
                'diubah_oleh' => $id_pengguna ?: null,
            ];

            if ($jenis_pembayaran === 'transfer') {
                if (Capsule::schema()->hasColumn('tb_pembayaran_penjualan', 'no_referensi_transfer')) {
                    $data_pembayaran['no_referensi_transfer'] = $no_referensi_transfer;
                }
                if (Capsule::schema()->hasColumn('tb_pembayaran_penjualan', 'kode_cek_transfer')) {
                    $data_pembayaran['kode_cek_transfer'] = substr(preg_replace('/\D+/', '', $no_referensi_transfer), -6) ?: $no_referensi_transfer;
                }
                if (Capsule::schema()->hasColumn('tb_pembayaran_penjualan', 'nama_pengirim_transfer')) {
                    $data_pembayaran['nama_pengirim_transfer'] = $nama_pengirim_transfer !== '' ? $nama_pengirim_transfer : null;
                }
                if (Capsule::schema()->hasColumn('tb_pembayaran_penjualan', 'bukti_bayar_pos')) {
                    $data_pembayaran['bukti_bayar_pos'] = $bukti_bayar_pos;
                }
            }

            Capsule::table('tb_pembayaran_penjualan')->insert($data_pembayaran);
        }
    });

    set_flash('success', 'Transaksi POS berhasil disimpan dan diposting.');
    penjualan_redirect(admin_url('index.php?menu=penjualan/kasir/struk&id=' . $id_faktur_baru));
} catch (Throwable $e) {
    set_flash('error', 'Gagal menyimpan transaksi POS: ' . $e->getMessage());
    penjualan_redirect(admin_page_url('penjualan/kasir'));
}