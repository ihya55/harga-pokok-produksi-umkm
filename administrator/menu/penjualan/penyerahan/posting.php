<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

require_once __DIR__ . '/../_fungsi_penjualan.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_pengguna = (int) (user_login()['id_pengguna'] ?? 0);
$id_penyerahan_penjualan = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$back_url = trim((string) ($_GET['back_url'] ?? $_POST['back_url'] ?? ''));

function penyerahan_posting_kembali(int $id = 0, string $back_url = ''): void
{
    if ($id > 0) {
        header('Location: ' . admin_url('index.php?menu=penjualan/penyerahan/detail&id=' . $id));
        exit;
    }

    if ($back_url !== '') {
        header('Location: ' . $back_url);
        exit;
    }

    header('Location: ' . admin_url('index.php?menu=penjualan/penyerahan'));
    exit;
}

function penyerahan_posting_generate_no_jurnal(int $id_entitas): string
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

function penyerahan_posting_periode_terbuka(int $id_entitas, string $tanggal): object
{
    $periode = Capsule::table('tb_periode_akuntansi')
        ->where('id_entitas', $id_entitas)
        ->where('tanggal_mulai', '<=', $tanggal)
        ->where('tanggal_selesai', '>=', $tanggal)
        ->whereIn('status_periode', ['terbuka', 'aktif'])
        ->first();

    if (!$periode) {
        throw new RuntimeException('Periode akuntansi untuk tanggal ' . $tanggal . ' belum terbuka.');
    }

    return $periode;
}

function penyerahan_posting_template(int $id_entitas, string $kode_jenis_transaksi): object
{
    $template = Capsule::table('tb_template_jurnal')
        ->where('id_entitas', $id_entitas)
        ->where('kode_jenis_transaksi', $kode_jenis_transaksi)
        ->where('status_aktif', 1)
        ->first();

    if (!$template) {
        throw new RuntimeException('Template jurnal ' . $kode_jenis_transaksi . ' belum tersedia atau belum aktif.');
    }

    return $template;
}

function penyerahan_posting_detail_template(int $id_template_jurnal)
{
    $detail = Capsule::table('tb_template_jurnal_detail')
        ->where('id_template_jurnal', $id_template_jurnal)
        ->orderBy('urutan', 'asc')
        ->get();

    if ($detail->count() === 0) {
        throw new RuntimeException('Detail template jurnal belum tersedia.');
    }

    return $detail;
}


function penyerahan_posting_akun_pendapatan_diterima_dimuka(int $id_entitas): int
{
    $akun = Capsule::table('tb_coa')
        ->where('id_entitas', $id_entitas)
        ->where('boleh_transaksi', 1)
        ->where('status_aktif', 1)
        ->where(function ($q) {
            $q->where('nama_coa', 'like', '%Pendapatan Diterima Dimuka%')
              ->orWhere('nama_coa', 'like', '%Uang Muka Penjualan%')
              ->orWhere('kode_coa', '2150');
        })
        ->orderBy('kode_coa')
        ->first();

    if (!$akun) {
        $akun = Capsule::table('tb_coa')
            ->where('id_entitas', $id_entitas)
            ->where('kategori_coa', 'liabilitas')
            ->where('boleh_transaksi', 1)
            ->where('status_aktif', 1)
            ->orderBy('kode_coa')
            ->first();
    }

    if (!$akun) {
        throw new RuntimeException('Akun Pendapatan Diterima Dimuka / uang muka penjualan belum tersedia di COA.');
    }

    return (int) $akun->id_coa;
}

function penyerahan_posting_akun_penjualan_online(int $id_entitas): int
{
    if (Capsule::schema()->hasTable('tb_konfigurasi_akun')) {
        $id = Capsule::table('tb_konfigurasi_akun')
            ->where('id_entitas', $id_entitas)
            ->where('status_aktif', 1)
            ->whereIn('kode_konfigurasi', ['penjualan_produk', 'PENJUALAN_PRODUK', 'PENDAPATAN_PENJUALAN'])
            ->value('id_coa');
        if ($id) {
            return (int) $id;
        }
    }

    $akun = Capsule::table('tb_coa')
        ->where('id_entitas', $id_entitas)
        ->where('boleh_transaksi', 1)
        ->where('status_aktif', 1)
        ->where(function ($q) {
            $q->where('nama_coa', 'like', '%Penjualan Produk%')
              ->orWhere('nama_coa', 'like', '%Penjualan Tunai%')
              ->orWhere('kode_coa', '4100')
              ->orWhere('kode_coa', '4110');
        })
        ->orderByRaw("CASE WHEN kode_coa = '4100' THEN 0 WHEN nama_coa LIKE '%Penjualan Produk%' THEN 1 WHEN kode_coa = '4110' THEN 2 ELSE 3 END")
        ->first();

    if (!$akun) {
        $akun = Capsule::table('tb_coa')
            ->where('id_entitas', $id_entitas)
            ->where('kategori_coa', 'pendapatan')
            ->where('boleh_transaksi', 1)
            ->where('status_aktif', 1)
            ->orderBy('kode_coa')
            ->first();
    }

    if (!$akun) {
        throw new RuntimeException('Akun penjualan/pendapatan belum tersedia di COA.');
    }

    return (int) $akun->id_coa;
}

function penyerahan_posting_akun_pajak_keluaran(int $id_entitas): int
{
    $akun = Capsule::table('tb_coa')
        ->where('id_entitas', $id_entitas)
        ->where('boleh_transaksi', 1)
        ->where('status_aktif', 1)
        ->where(function ($q) {
            $q->where('nama_coa', 'like', '%Pajak Keluaran%')
              ->orWhere('nama_coa', 'like', '%PPN Keluaran%')
              ->orWhere('kode_coa', '2141');
        })
        ->orderBy('kode_coa')
        ->first();

    if (!$akun) {
        throw new RuntimeException('Akun Pajak/PPN Keluaran belum tersedia di COA.');
    }

    return (int) $akun->id_coa;
}

function penyerahan_posting_jurnal_pendapatan_online(object $penyerahan, object $pesanan, int $id_entitas, int $id_pengguna, object $periode): void
{
    $kode = 'PENYERAHAN_PENJUALAN_ONLINE_PENDAPATAN';
    $id_penyerahan = (int) $penyerahan->id_penyerahan_penjualan;

    $sudahAda = Capsule::table('tb_log_jurnal_sumber')
        ->where('id_entitas', $id_entitas)
        ->where('tabel_sumber', 'tb_penyerahan_penjualan')
        ->where('id_sumber', $id_penyerahan)
        ->where('kode_jenis_transaksi', $kode)
        ->exists();

    if ($sudahAda) {
        return;
    }

    $total = round((float) ($pesanan->total ?? 0), 2);
    $ppn = round((float) ($pesanan->ppn ?? 0), 2);
    $pendapatan = round(max(0, ((float) ($pesanan->subtotal ?? 0) - (float) ($pesanan->diskon ?? 0))), 2);

    // Jika data subtotal lama belum lengkap, pakai total dikurangi PPN agar jurnal tetap balance.
    if ($pendapatan <= 0) {
        $pendapatan = round(max(0, $total - $ppn), 2);
    }

    if ($total <= 0 || ($pendapatan + $ppn) <= 0) {
        return;
    }

    if (abs($total - ($pendapatan + $ppn)) > 0.01) {
        $pendapatan = round($total - $ppn, 2);
    }

    $idCoaUangMuka = penyerahan_posting_akun_pendapatan_diterima_dimuka($id_entitas);
    $idCoaPenjualan = penyerahan_posting_akun_penjualan_online($id_entitas);
    $idCoaPajak = $ppn > 0 ? penyerahan_posting_akun_pajak_keluaran($id_entitas) : null;

    $idJurnal = (int) Capsule::table('tb_jurnal')->insertGetId([
        'id_entitas' => $id_entitas,
        'no_jurnal' => penyerahan_posting_generate_no_jurnal($id_entitas),
        'tanggal_jurnal' => (string) $penyerahan->tanggal_penyerahan,
        'id_periode' => (int) $periode->id_periode,
        'kode_jenis_transaksi' => $kode,
        'keterangan' => 'Pengakuan pendapatan pesanan online saat penyerahan ' . (string) $penyerahan->no_penyerahan_penjualan,
        'tabel_sumber' => 'tb_penyerahan_penjualan',
        'id_sumber' => $id_penyerahan,
        'no_sumber' => (string) $penyerahan->no_penyerahan_penjualan,
        'status_jurnal' => 'posted',
        'total_debit' => $total,
        'total_kredit' => $total,
        'tanggal_dibuat' => date('Y-m-d H:i:s'),
        'dibuat_oleh' => $id_pengguna ?: null,
        'tanggal_posting' => date('Y-m-d H:i:s'),
        'diposting_oleh' => $id_pengguna ?: null,
    ]);

    Capsule::table('tb_jurnal_detail')->insert([
        'id_jurnal' => $idJurnal,
        'urutan' => 1,
        'id_coa' => $idCoaUangMuka,
        'debit' => $total,
        'kredit' => 0,
        'keterangan_baris' => 'Membalik uang muka/pendapatan diterima dimuka pesanan online',
        'id_pelanggan' => (int) $penyerahan->id_pelanggan,
        'id_pemasok' => null,
        'id_produk' => null,
        'id_bahan_baku' => null,
        'id_gudang' => null,
    ]);

    Capsule::table('tb_jurnal_detail')->insert([
        'id_jurnal' => $idJurnal,
        'urutan' => 2,
        'id_coa' => $idCoaPenjualan,
        'debit' => 0,
        'kredit' => $pendapatan,
        'keterangan_baris' => 'Pendapatan penjualan online diakui saat barang diserahkan',
        'id_pelanggan' => (int) $penyerahan->id_pelanggan,
        'id_pemasok' => null,
        'id_produk' => null,
        'id_bahan_baku' => null,
        'id_gudang' => null,
    ]);

    if ($ppn > 0 && $idCoaPajak) {
        Capsule::table('tb_jurnal_detail')->insert([
            'id_jurnal' => $idJurnal,
            'urutan' => 3,
            'id_coa' => $idCoaPajak,
            'debit' => 0,
            'kredit' => $ppn,
            'keterangan_baris' => 'Pajak keluaran pesanan online',
            'id_pelanggan' => (int) $penyerahan->id_pelanggan,
            'id_pemasok' => null,
            'id_produk' => null,
            'id_bahan_baku' => null,
            'id_gudang' => null,
        ]);
    }

    Capsule::table('tb_log_jurnal_sumber')->insert([
        'id_entitas' => $id_entitas,
        'id_jurnal' => $idJurnal,
        'tabel_sumber' => 'tb_penyerahan_penjualan',
        'id_sumber' => $id_penyerahan,
        'no_sumber' => (string) $penyerahan->no_penyerahan_penjualan,
        'kode_jenis_transaksi' => $kode,
        'tanggal_dibuat' => date('Y-m-d H:i:s'),
    ]);
}

function penyerahan_posting_nominal(string $rumus, array $context): float
{
    $rumus = strtolower(trim($rumus));

    return match ($rumus) {
        'total_hpp', 'hpp', 'total' => (float) ($context['total_hpp'] ?? 0),
        default => throw new RuntimeException('Rumus nominal template tidak dikenali: ' . $rumus),
    };
}

if ($id_penyerahan_penjualan <= 0) {
    set_flash('error', 'ID penyerahan penjualan tidak valid.');
    penyerahan_posting_kembali(0, $back_url);
}

try {
    Capsule::connection()->transaction(function () use (
        $id_entitas,
        $id_pengguna,
        $id_penyerahan_penjualan
    ) {
        $penyerahan = Capsule::table('tb_penyerahan_penjualan')
            ->where('id_entitas', $id_entitas)
            ->where('id_penyerahan_penjualan', $id_penyerahan_penjualan)
            ->lockForUpdate()
            ->first();

        if (!$penyerahan) {
            throw new RuntimeException('Data penyerahan penjualan tidak ditemukan.');
        }

        if ((string) $penyerahan->status_penyerahan !== 'draft') {
            throw new RuntimeException('Hanya penyerahan berstatus draft yang bisa diposting.');
        }

        if (empty($penyerahan->id_pesanan_penjualan)) {
            throw new RuntimeException('Penyerahan wajib memiliki referensi Pesanan Penjualan.');
        }

        $sudahAdaJurnal = Capsule::table('tb_log_jurnal_sumber')
            ->where('id_entitas', $id_entitas)
            ->where('tabel_sumber', 'tb_penyerahan_penjualan')
            ->where('id_sumber', $id_penyerahan_penjualan)
            ->where('kode_jenis_transaksi', 'PENYERAHAN_PENJUALAN_HPP')
            ->exists();

        if ($sudahAdaJurnal) {
            throw new RuntimeException('Penyerahan ini sudah pernah dibuatkan jurnal HPP.');
        }

        $periode = penyerahan_posting_periode_terbuka($id_entitas, (string) $penyerahan->tanggal_penyerahan);

        $detail = Capsule::table('tb_penyerahan_penjualan_detail')
            ->where('id_penyerahan_penjualan', $id_penyerahan_penjualan)
            ->orderBy('id_penyerahan_penjualan_detail', 'asc')
            ->get();

        if ($detail->count() === 0) {
            throw new RuntimeException('Detail penyerahan masih kosong.');
        }

        $total_hpp = 0.0;

        foreach ($detail as $d) {
            $id_produk = (int) $d->id_produk;
            $qty = (float) $d->qty;

            if ($id_produk <= 0 || $qty <= 0) {
                throw new RuntimeException('Detail produk tidak valid.');
            }

            $saldo = Capsule::table('tb_saldo_stok')
                ->where('id_entitas', $id_entitas)
                ->where('jenis_barang', 'produk')
                ->where('id_referensi_barang', $id_produk)
                ->where('id_gudang', (int) $penyerahan->id_gudang)
                ->lockForUpdate()
                ->first();

            if (!$saldo) {
                throw new RuntimeException('Saldo stok produk tidak ditemukan untuk salah satu item.');
            }

            $qty_saldo_lama = (float) $saldo->qty_saldo;
            $nilai_saldo_lama = (float) $saldo->nilai_saldo;
            $hpp_rata_rata = (float) $saldo->hpp_rata_rata;

            if ($qty_saldo_lama < $qty) {
                throw new RuntimeException('Stok produk tidak cukup untuk diposting.');
            }

            if ($hpp_rata_rata <= 0) {
                throw new RuntimeException('HPP rata-rata produk belum tersedia di saldo stok.');
            }

            $hpp_total = round($qty * $hpp_rata_rata, 2);
            $qty_saldo_baru = $qty_saldo_lama - $qty;
            $nilai_saldo_baru = max(0, round($nilai_saldo_lama - $hpp_total, 2));
            $hpp_baru = $qty_saldo_baru > 0 ? round($nilai_saldo_baru / $qty_saldo_baru, 2) : $hpp_rata_rata;

            Capsule::table('tb_penyerahan_penjualan_detail')
                ->where('id_penyerahan_penjualan_detail', (int) $d->id_penyerahan_penjualan_detail)
                ->update([
                    'hpp_satuan' => round($hpp_rata_rata, 2),
                    'hpp_total' => $hpp_total,
                ]);

            Capsule::table('tb_saldo_stok')
                ->where('id_saldo_stok', (int) $saldo->id_saldo_stok)
                ->update([
                    'qty_saldo' => $qty_saldo_baru,
                    'nilai_saldo' => $nilai_saldo_baru,
                    'hpp_rata_rata' => $hpp_baru,
                    'tanggal_update' => date('Y-m-d H:i:s'),
                    'tanggal_diubah' => date('Y-m-d H:i:s'),
                    'diubah_oleh' => $id_pengguna ?: null,
                ]);

            Capsule::table('tb_mutasi_stok')->insert([
                'id_entitas' => $id_entitas,
                'tanggal_mutasi' => (string) $penyerahan->tanggal_penyerahan . ' ' . date('H:i:s'),
                'jenis_barang' => 'produk',
                'id_referensi_barang' => $id_produk,
                'id_gudang' => (int) $penyerahan->id_gudang,
                'jenis_mutasi' => 'penyerahan_penjualan',
                'qty_masuk' => 0,
                'qty_keluar' => $qty,
                'harga_satuan' => round($hpp_rata_rata, 2),
                'nilai_total' => $hpp_total,
                'tabel_sumber' => 'tb_penyerahan_penjualan',
                'id_sumber' => $id_penyerahan_penjualan,
                'no_sumber' => (string) $penyerahan->no_penyerahan_penjualan,
                'keterangan' => 'Posting penyerahan penjualan ' . (string) $penyerahan->no_penyerahan_penjualan,
                'tanggal_dibuat' => date('Y-m-d H:i:s'),
                'dibuat_oleh' => $id_pengguna ?: null,
            ]);

            $total_hpp += $hpp_total;
        }

        $total_hpp = round($total_hpp, 2);

        if ($total_hpp <= 0) {
            throw new RuntimeException('Total HPP penyerahan harus lebih besar dari 0.');
        }

        $template = penyerahan_posting_template($id_entitas, 'PENYERAHAN_PENJUALAN_HPP');
        $templateDetail = penyerahan_posting_detail_template((int) $template->id_template_jurnal);

        $context = [
            'total_hpp' => $total_hpp,
        ];

        $barisJurnal = [];
        $total_debit = 0.0;
        $total_kredit = 0.0;

        foreach ($templateDetail as $td) {
            $posisi = strtolower(trim((string) $td->posisi_dc));
            $sumber_akun = strtolower(trim((string) $td->sumber_akun));
            $sumber_nominal = strtolower(trim((string) $td->sumber_nominal));
            $rumus_nominal = trim((string) $td->rumus_nominal);

            if (!in_array($posisi, ['debit', 'kredit'], true)) {
                throw new RuntimeException('Posisi debit/kredit template jurnal tidak valid.');
            }

            if ($sumber_akun !== 'coa_default' || empty($td->id_coa_default)) {
                throw new RuntimeException('Template jurnal penyerahan harus memakai sumber_akun coa_default.');
            }

            if ($sumber_nominal !== 'rumus_nominal') {
                throw new RuntimeException('Template jurnal penyerahan harus memakai sumber_nominal rumus_nominal.');
            }

            $nominal = penyerahan_posting_nominal($rumus_nominal, $context);

            if ($nominal <= 0) {
                continue;
            }

            $debit = $posisi === 'debit' ? $nominal : 0;
            $kredit = $posisi === 'kredit' ? $nominal : 0;

            $total_debit += $debit;
            $total_kredit += $kredit;

            $barisJurnal[] = [
                'urutan' => (int) $td->urutan,
                'id_coa' => (int) $td->id_coa_default,
                'debit' => round($debit, 2),
                'kredit' => round($kredit, 2),
                'keterangan_baris' => $td->keterangan_baris ?: ('HPP penyerahan ' . (string) $penyerahan->no_penyerahan_penjualan),
            ];
        }

        $total_debit = round($total_debit, 2);
        $total_kredit = round($total_kredit, 2);

        if (count($barisJurnal) < 2) {
            throw new RuntimeException('Template jurnal penyerahan belum lengkap.');
        }

        if (abs($total_debit - $total_kredit) > 0.01) {
            throw new RuntimeException('Jurnal tidak balance. Debit: ' . $total_debit . ', Kredit: ' . $total_kredit);
        }

        $id_jurnal = Capsule::table('tb_jurnal')->insertGetId([
            'id_entitas' => $id_entitas,
            'no_jurnal' => penyerahan_posting_generate_no_jurnal($id_entitas),
            'tanggal_jurnal' => (string) $penyerahan->tanggal_penyerahan,
            'id_periode' => (int) $periode->id_periode,
            'kode_jenis_transaksi' => 'PENYERAHAN_PENJUALAN_HPP',
            'keterangan' => 'Posting HPP penyerahan penjualan ' . (string) $penyerahan->no_penyerahan_penjualan,
            'tabel_sumber' => 'tb_penyerahan_penjualan',
            'id_sumber' => $id_penyerahan_penjualan,
            'no_sumber' => (string) $penyerahan->no_penyerahan_penjualan,
            'status_jurnal' => 'posted',
            'total_debit' => $total_debit,
            'total_kredit' => $total_kredit,
            'tanggal_dibuat' => date('Y-m-d H:i:s'),
            'dibuat_oleh' => $id_pengguna ?: null,
            'tanggal_posting' => date('Y-m-d H:i:s'),
            'diposting_oleh' => $id_pengguna ?: null,
        ]);

        foreach ($barisJurnal as $baris) {
            Capsule::table('tb_jurnal_detail')->insert([
                'id_jurnal' => $id_jurnal,
                'urutan' => $baris['urutan'],
                'id_coa' => $baris['id_coa'],
                'debit' => $baris['debit'],
                'kredit' => $baris['kredit'],
                'keterangan_baris' => $baris['keterangan_baris'],
                'id_pelanggan' => (int) $penyerahan->id_pelanggan,
                'id_pemasok' => null,
                'id_produk' => null,
                'id_bahan_baku' => null,
                'id_gudang' => (int) $penyerahan->id_gudang,
            ]);
        }

        Capsule::table('tb_log_jurnal_sumber')->insert([
            'id_entitas' => $id_entitas,
            'id_jurnal' => $id_jurnal,
            'tabel_sumber' => 'tb_penyerahan_penjualan',
            'id_sumber' => $id_penyerahan_penjualan,
            'no_sumber' => (string) $penyerahan->no_penyerahan_penjualan,
            'kode_jenis_transaksi' => 'PENYERAHAN_PENJUALAN_HPP',
            'tanggal_dibuat' => date('Y-m-d H:i:s'),
        ]);

        $pesananSumber = Capsule::table('tb_pesanan_penjualan')
            ->where('id_entitas', $id_entitas)
            ->where('id_pesanan_penjualan', (int) $penyerahan->id_pesanan_penjualan)
            ->lockForUpdate()
            ->first();

        if ($pesananSumber
            && (string) ($pesananSumber->sumber_pesanan ?? '') === 'website'
            && in_array(strtolower((string) ($pesananSumber->status_pembayaran_online ?? '')), ['lunas', 'paid', 'settlement'], true)
        ) {
            penyerahan_posting_jurnal_pendapatan_online($penyerahan, $pesananSumber, $id_entitas, $id_pengguna, $periode);
        }

        if ((float) ($penyerahan->biaya_pengiriman ?? 0) > 0) {
            penjualan_buat_jurnal_biaya_pengiriman_penyerahan([
                'id_entitas' => $id_entitas,
                'id_pengguna' => $id_pengguna,
                'tanggal_penyerahan' => (string) $penyerahan->tanggal_penyerahan,
                'id_penyerahan_penjualan' => $id_penyerahan_penjualan,
                'no_penyerahan_penjualan' => (string) $penyerahan->no_penyerahan_penjualan,
                'id_pelanggan' => (int) $penyerahan->id_pelanggan,
                'id_gudang' => (int) $penyerahan->id_gudang,
                'biaya_pengiriman' => (float) $penyerahan->biaya_pengiriman,
                'id_coa_biaya_pengiriman' => (int) ($penyerahan->id_coa_biaya_pengiriman ?? 0),
            ]);
        }

        Capsule::table('tb_penyerahan_penjualan')
            ->where('id_entitas', $id_entitas)
            ->where('id_penyerahan_penjualan', $id_penyerahan_penjualan)
            ->update([
                'status_penyerahan' => 'posted',
                'tanggal_posting' => date('Y-m-d H:i:s'),
                'diposting_oleh' => $id_pengguna ?: null,
                'tanggal_diubah' => date('Y-m-d H:i:s'),
                'diubah_oleh' => $id_pengguna ?: null,
            ]);

        Capsule::table('tb_pesanan_penjualan')
            ->where('id_entitas', $id_entitas)
            ->where('id_pesanan_penjualan', (int) $penyerahan->id_pesanan_penjualan)
            ->update([
                'status_pesanan' => 'selesai',
                'tanggal_diubah' => date('Y-m-d H:i:s'),
                'diubah_oleh' => $id_pengguna ?: null,
            ]);
    });

    set_flash('success', 'Penyerahan penjualan berhasil diposting. Stok produk sudah berkurang dan jurnal HPP sudah dibuat.');
} catch (Throwable $e) {
    set_flash('error', $e->getMessage());
}

penyerahan_posting_kembali($id_penyerahan_penjualan, $back_url);