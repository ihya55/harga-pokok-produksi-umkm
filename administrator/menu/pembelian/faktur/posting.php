<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/kode.php';

require_once __DIR__ . '/../../../../orm/FakturPembelianORM.php';
require_once __DIR__ . '/../../../../orm/FakturPembelianDetailORM.php';
require_once __DIR__ . '/../../../../orm/PembayaranPembelianORM.php';
require_once __DIR__ . '/../../../../orm/JurnalORM.php';
require_once __DIR__ . '/../../../../orm/JurnalDetailORM.php';
require_once __DIR__ . '/../../../../orm/LogJurnalSumberORM.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_pengguna = (int) (user_login()['id_pengguna'] ?? 0);
$id_faktur_pembelian = (int) ($_GET['id'] ?? 0);

function normalisasi_jenis_pembayaran_faktur_pembelian_posting(string $jenis_pembayaran): string
{
    $jenis_pembayaran = strtolower(trim($jenis_pembayaran));

    if (!in_array($jenis_pembayaran, ['kredit', 'tunai', 'transfer'], true)) {
        return 'kredit';
    }

    return $jenis_pembayaran;
}

function kode_template_faktur_pembelian_posting(string $jenis_pembayaran): string
{
    $jenis_pembayaran = normalisasi_jenis_pembayaran_faktur_pembelian_posting($jenis_pembayaran);

    if ($jenis_pembayaran === 'tunai') {
        return 'FAKTUR_PEMBELIAN_TUNAI';
    }

    if ($jenis_pembayaran === 'transfer') {
        return 'FAKTUR_PEMBELIAN_TRANSFER';
    }

    return 'FAKTUR_PEMBELIAN_KREDIT';
}

function label_jurnal_faktur_pembelian_posting(string $jenis_pembayaran): string
{
    $jenis_pembayaran = normalisasi_jenis_pembayaran_faktur_pembelian_posting($jenis_pembayaran);

    if ($jenis_pembayaran === 'tunai') {
        return 'jurnal pembelian tunai';
    }

    if ($jenis_pembayaran === 'transfer') {
        return 'jurnal pembelian transfer';
    }

    return 'jurnal utang pembelian';
}

function hitung_rumus_template_faktur_pembelian_posting(
    string $rumus,
    float $subtotal,
    float $diskon,
    float $ppn,
    float $total
): float {
    $rumus = strtolower(trim($rumus));

    if ($rumus === 'subtotal') {
        return round($subtotal, 2);
    }

    if ($rumus === 'diskon') {
        return round($diskon, 2);
    }

    if ($rumus === 'subtotal_setelah_diskon') {
        return round(max(0, $subtotal - $diskon), 2);
    }

    if ($rumus === 'ppn') {
        return round($ppn, 2);
    }

    if ($rumus === 'total') {
        return round($total, 2);
    }

    return 0;
}

function coa_aktif_faktur_pembelian_posting(int $id_entitas, int $id_coa): bool
{
    return Capsule::table('tb_coa')
        ->where('id_entitas', $id_entitas)
        ->where('id_coa', $id_coa)
        ->where('boleh_transaksi', 1)
        ->where('status_aktif', 1)
        ->exists();
}


function akun_persediaan_bahan_faktur_pembelian_posting(int $id_entitas, string $kode_jenis_transaksi): int
{
    /*
     * Tidak hardcode COA.
     * Akun diambil dari tabel Mapping Akun sesuai jenis transaksi faktur pembelian.
     * Prioritas:
     * 1. Mapping spesifik FAKTUR_PEMBELIAN_TUNAI / KREDIT / TRANSFER
     * 2. Mapping umum FAKTUR_PEMBELIAN
     * 3. Konfigurasi akun persediaan_bahan_baku sebagai pengaman lama
     */
    $kode_opsi = [$kode_jenis_transaksi, 'FAKTUR_PEMBELIAN'];

    foreach ($kode_opsi as $kode) {
        $id_coa = (int) (Capsule::table('tb_mapping_akun as ma')
            ->join('tb_coa as c', 'c.id_coa', '=', 'ma.id_coa')
            ->where('ma.id_entitas', $id_entitas)
            ->where('ma.kode_jenis_transaksi', $kode)
            ->where('ma.jenis_objek', 'global')
            ->where('ma.id_objek', 0)
            ->where('ma.peran_akun', 'akun_persediaan_bahan')
            ->where('ma.status_aktif', 1)
            ->where('c.boleh_transaksi', 1)
            ->where('c.status_aktif', 1)
            ->value('ma.id_coa') ?? 0);

        if ($id_coa > 0) {
            return $id_coa;
        }
    }

    $id_coa_konfig = (int) (Capsule::table('tb_konfigurasi_akun as ka')
        ->join('tb_coa as c', 'c.id_coa', '=', 'ka.id_coa')
        ->where('ka.id_entitas', $id_entitas)
        ->where('ka.kode_konfigurasi', 'persediaan_bahan_baku')
        ->where('ka.status_aktif', 1)
        ->where('c.boleh_transaksi', 1)
        ->where('c.status_aktif', 1)
        ->value('ka.id_coa') ?? 0);

    if ($id_coa_konfig > 0) {
        return $id_coa_konfig;
    }

    throw new RuntimeException('Mapping Akun persediaan bahan baku belum tersedia. Cek Mapping Akun kode ' . $kode_jenis_transaksi . ' dengan peran akun_persediaan_bahan.');
}

function kapitalisasi_biaya_kirim_ke_stok_faktur_pembelian_posting(
    object $row,
    int $id_entitas,
    int $id_pengguna,
    int $id_faktur_pembelian,
    float $biaya_kirim
): void {
    if ($biaya_kirim <= 0) {
        return;
    }

    $penerimaan = Capsule::table('tb_penerimaan_pembelian')
        ->where('id_entitas', $id_entitas)
        ->where('id_penerimaan_pembelian', (int) $row->id_penerimaan_pembelian)
        ->first();

    if (!$penerimaan) {
        throw new RuntimeException('Penerimaan pembelian tidak ditemukan, biaya kirim tidak bisa dikapitalisasi ke stok.');
    }

    $detail = Capsule::table('tb_faktur_pembelian_detail')
        ->where('id_faktur_pembelian', $id_faktur_pembelian)
        ->orderBy('id_faktur_pembelian_detail', 'asc')
        ->get();

    if ($detail->count() <= 0) {
        throw new RuntimeException('Detail faktur pembelian tidak ditemukan, biaya kirim tidak bisa dikapitalisasi ke stok.');
    }

    $dasar_alokasi = 0.0;
    foreach ($detail as $d) {
        $dasar_alokasi += max(0, (float) ($d->subtotal ?? 0));
    }

    if ($dasar_alokasi <= 0) {
        throw new RuntimeException('Subtotal detail faktur tidak valid untuk alokasi biaya kirim.');
    }

    $sisa_alokasi = round($biaya_kirim, 2);
    $jumlah_baris = $detail->count();
    $nomor_baris = 0;

    foreach ($detail as $d) {
        $nomor_baris++;
        $subtotal_detail = max(0, (float) ($d->subtotal ?? 0));
        $alokasi = ($nomor_baris === $jumlah_baris)
            ? $sisa_alokasi
            : round($biaya_kirim * ($subtotal_detail / $dasar_alokasi), 2);

        $sisa_alokasi = round($sisa_alokasi - $alokasi, 2);

        if ($alokasi <= 0) {
            continue;
        }

        $id_bahan_baku = (int) $d->id_bahan_baku;
        $id_gudang = (int) $penerimaan->id_gudang;
        $qty_detail = (float) ($d->qty ?? 0);
        $harga_satuan_alokasi = $qty_detail > 0 ? round($alokasi / $qty_detail, 2) : 0.00;

        $saldo = Capsule::table('tb_saldo_stok')
            ->where('id_entitas', $id_entitas)
            ->where('jenis_barang', 'bahan_baku')
            ->where('id_referensi_barang', $id_bahan_baku)
            ->where('id_gudang', $id_gudang)
            ->first();

        if (!$saldo) {
            throw new RuntimeException('Saldo stok bahan baku belum ada untuk alokasi biaya kirim. Posting penerimaan pembelian terlebih dahulu.');
        }

        $qty_saldo = (float) ($saldo->qty_saldo ?? 0);
        if ($qty_saldo <= 0) {
            throw new RuntimeException('Stok bahan baku sudah habis, biaya kirim tidak bisa dikapitalisasi otomatis. Posting faktur pembelian sebelum bahan dipakai produksi.');
        }

        Capsule::table('tb_mutasi_stok')->insert([
            'id_entitas'          => $id_entitas,
            'tanggal_mutasi'      => date('Y-m-d H:i:s'),
            'jenis_barang'        => 'bahan_baku',
            'id_referensi_barang' => $id_bahan_baku,
            'id_gudang'           => $id_gudang,
            'jenis_mutasi'        => 'biaya_kirim_pembelian',
            'qty_masuk'           => 0,
            'qty_keluar'          => 0,
            'harga_satuan'        => $harga_satuan_alokasi,
            'nilai_total'         => round($alokasi, 2),
            'tabel_sumber'        => 'tb_faktur_pembelian',
            'id_sumber'           => $id_faktur_pembelian,
            'no_sumber'           => (string) $row->no_faktur_pembelian,
            'keterangan'          => 'Kapitalisasi biaya kirim pembelian ke persediaan bahan baku',
            'tanggal_dibuat'      => date('Y-m-d H:i:s'),
            'dibuat_oleh'         => $id_pengguna > 0 ? $id_pengguna : null,
            'tanggal_diubah'      => null,
            'diubah_oleh'         => null,
        ]);

        $nilai_baru = round((float) ($saldo->nilai_saldo ?? 0) + $alokasi, 2);
        $hpp_baru = round($nilai_baru / $qty_saldo, 2);

        Capsule::table('tb_saldo_stok')
            ->where('id_saldo_stok', (int) $saldo->id_saldo_stok)
            ->update([
                'nilai_saldo'    => $nilai_baru,
                'hpp_rata_rata'  => $hpp_baru,
                'tanggal_update' => date('Y-m-d H:i:s'),
                'tanggal_diubah' => date('Y-m-d H:i:s'),
                'diubah_oleh'    => $id_pengguna > 0 ? $id_pengguna : null,
            ]);
    }
}

$row = FakturPembelianORM::query()
    ->where('id_entitas', $id_entitas)
    ->find($id_faktur_pembelian);

if (!$row) {
    set_flash('error', 'Data faktur pembelian tidak ditemukan.');
    redirect_admin('pembelian/faktur');
}

if ((string) $row->status_faktur !== 'draft') {
    set_flash('error', 'Hanya faktur draft yang bisa diposting.');
    header('Location: ' . admin_url('index.php?menu=pembelian/faktur/detail&id=' . $id_faktur_pembelian));
    exit;
}

$jumlah_detail = FakturPembelianDetailORM::query()
    ->where('id_faktur_pembelian', $id_faktur_pembelian)
    ->count();

if ($jumlah_detail <= 0) {
    set_flash('error', 'Faktur belum memiliki detail.');
    header('Location: ' . admin_url('index.php?menu=pembelian/faktur/detail&id=' . $id_faktur_pembelian));
    exit;
}

$jenis_pembayaran = normalisasi_jenis_pembayaran_faktur_pembelian_posting((string) ($row->jenis_pembayaran ?? 'kredit'));
$kode_jenis_transaksi = kode_template_faktur_pembelian_posting($jenis_pembayaran);
$label_jurnal = label_jurnal_faktur_pembelian_posting($jenis_pembayaran);

if (in_array($jenis_pembayaran, ['tunai', 'transfer'], true)) {
    $id_coa_kas_bank = (int) ($row->id_coa_kas_bank ?? 0);

    if ($id_coa_kas_bank <= 0) {
        set_flash('error', 'Akun kas/bank wajib dipilih untuk faktur pembelian tunai atau transfer.');
        header('Location: ' . admin_url('index.php?menu=pembelian/faktur/detail&id=' . $id_faktur_pembelian));
        exit;
    }

    if (!coa_aktif_faktur_pembelian_posting($id_entitas, $id_coa_kas_bank)) {
        set_flash('error', 'Akun kas/bank tidak aktif atau tidak boleh transaksi.');
        header('Location: ' . admin_url('index.php?menu=pembelian/faktur/detail&id=' . $id_faktur_pembelian));
        exit;
    }
}

try {
    Capsule::connection()->transaction(function () use (
        $row,
        $id_entitas,
        $id_pengguna,
        $id_faktur_pembelian,
        $jenis_pembayaran,
        $kode_jenis_transaksi
    ) {
        $periode = Capsule::table('tb_periode_akuntansi')
            ->where('id_entitas', $id_entitas)
            ->where('status_periode', 'terbuka')
            ->whereDate('tanggal_mulai', '<=', $row->tanggal_faktur)
            ->whereDate('tanggal_selesai', '>=', $row->tanggal_faktur)
            ->first();

        if (!$periode) {
            throw new RuntimeException('Periode akuntansi untuk tanggal faktur belum dibuka.');
        }

        $cek_log = LogJurnalSumberORM::query()
            ->where('id_entitas', $id_entitas)
            ->where('tabel_sumber', 'tb_faktur_pembelian')
            ->where('id_sumber', $id_faktur_pembelian)
            ->first();

        if ($cek_log) {
            throw new RuntimeException('Faktur ini sudah pernah dibuatkan jurnal.');
        }

        $template = Capsule::table('tb_template_jurnal')
            ->where('id_entitas', $id_entitas)
            ->where('kode_jenis_transaksi', $kode_jenis_transaksi)
            ->where('status_aktif', 1)
            ->first();

        if (!$template) {
            throw new RuntimeException('Template jurnal ' . $kode_jenis_transaksi . ' belum dibuat atau belum aktif.');
        }

        $template_detail = Capsule::table('tb_template_jurnal_detail as tjd')
            ->where('tjd.id_template_jurnal', $template->id_template_jurnal)
            ->orderBy('tjd.urutan', 'asc')
            ->get();

        if ($template_detail->count() <= 0) {
            throw new RuntimeException('Detail template jurnal ' . $kode_jenis_transaksi . ' belum ada.');
        }

        $subtotal = round((float) $row->subtotal, 2);
        $diskon = round((float) $row->diskon, 2);
        $ppn = round((float) $row->ppn, 2);
        $biaya_kirim = round((float) ($row->biaya_kirim ?? 0), 2);
        $id_coa_biaya_kirim = (int) ($row->id_coa_biaya_kirim ?? 0);
        $total = round((float) $row->total, 2);

        if ($total <= 0) {
            throw new RuntimeException('Total faktur tidak valid.');
        }

        $no_jurnal = generate_kode_master(
            'tb_jurnal',
            'no_jurnal',
            'JRN',
            4,
            $id_entitas
        );

        $jurnal = JurnalORM::create([
            'id_entitas'             => $id_entitas,
            'no_jurnal'              => $no_jurnal,
            'tanggal_jurnal'         => $row->tanggal_faktur,
            'id_periode'             => $periode->id_periode,
            'kode_jenis_transaksi'   => $kode_jenis_transaksi,
            'keterangan'             => 'Posting faktur pembelian ' . $row->no_faktur_pembelian . ' (' . ucfirst($jenis_pembayaran) . ')',
            'tabel_sumber'           => 'tb_faktur_pembelian',
            'id_sumber'              => $id_faktur_pembelian,
            'no_sumber'              => $row->no_faktur_pembelian,
            'status_jurnal'          => 'posted',
            'total_debit'            => $total,
            'total_kredit'           => $total,
            'tanggal_dibuat'         => date('Y-m-d H:i:s'),
            'dibuat_oleh'            => $id_pengguna > 0 ? $id_pengguna : null,
            'tanggal_posting'        => date('Y-m-d H:i:s'),
            'diposting_oleh'         => $id_pengguna > 0 ? $id_pengguna : null,
            'tanggal_diubah'         => null,
            'diubah_oleh'            => null,
        ]);

        $total_debit = 0.0;
        $total_kredit = 0.0;
        $urutan_jurnal = 1;

        foreach ($template_detail as $td) {
            $sumber_akun = strtolower((string) ($td->sumber_akun ?? ''));
            $sumber_nominal = strtolower((string) ($td->sumber_nominal ?? ''));

            if ($sumber_akun !== 'coa_default') {
                throw new RuntimeException('Sumber akun selain coa_default belum didukung untuk posting faktur pembelian.');
            }

            if ($sumber_nominal !== 'rumus_nominal') {
                throw new RuntimeException('Sumber nominal selain rumus_nominal belum didukung untuk posting faktur pembelian.');
            }

            $id_coa = (int) ($td->id_coa_default ?? 0);
            $posisi_dc = strtolower((string) ($td->posisi_dc ?? ''));
            $rumus = (string) ($td->rumus_nominal ?? '');

            if ($id_coa <= 0) {
                throw new RuntimeException('Ada baris template jurnal tanpa akun COA.');
            }

            if (!in_array($posisi_dc, ['debit', 'kredit'], true)) {
                throw new RuntimeException('Posisi debit/kredit pada template jurnal tidak valid.');
            }

            /*
             * Untuk faktur tunai / transfer:
             * akun kredit kas/bank memakai akun yang dipilih di faktur,
             * agar jurnal dan tabel pembayaran memakai akun yang sama.
             */
            if (
                in_array($jenis_pembayaran, ['tunai', 'transfer'], true)
                && $posisi_dc === 'kredit'
                && strtolower(trim($rumus)) === 'total'
            ) {
                $id_coa = (int) ($row->id_coa_kas_bank ?? 0);
            }

            if (!coa_aktif_faktur_pembelian_posting($id_entitas, $id_coa)) {
                throw new RuntimeException('Akun COA pada template jurnal tidak aktif atau tidak boleh transaksi.');
            }

            $nilai = hitung_rumus_template_faktur_pembelian_posting($rumus, $subtotal, $diskon, $ppn, $total);

            if ($nilai <= 0) {
                continue;
            }

            $debit = $posisi_dc === 'debit' ? $nilai : 0;
            $kredit = $posisi_dc === 'kredit' ? $nilai : 0;

            $total_debit += $debit;
            $total_kredit += $kredit;

            JurnalDetailORM::create([
                'id_jurnal'        => $jurnal->id_jurnal,
                'urutan'           => $urutan_jurnal++,
                'id_coa'           => $id_coa,
                'debit'            => round($debit, 2),
                'kredit'           => round($kredit, 2),
                'keterangan_baris' => (string) ($td->keterangan_baris ?? ('Faktur pembelian ' . $row->no_faktur_pembelian)),
                'id_pelanggan'     => null,
                'id_pemasok'       => (int) $row->id_pemasok,
                'id_produk'        => null,
                'id_bahan_baku'    => null,
                'id_gudang'        => null,
            ]);
        }


        if ($biaya_kirim > 0) {
            $id_coa_persediaan_bahan = akun_persediaan_bahan_faktur_pembelian_posting($id_entitas, $kode_jenis_transaksi);

            if (!coa_aktif_faktur_pembelian_posting($id_entitas, $id_coa_persediaan_bahan)) {
                throw new RuntimeException('Akun persediaan bahan baku dari Mapping Akun tidak aktif atau tidak boleh transaksi.');
            }

            $total_debit += $biaya_kirim;
            JurnalDetailORM::create([
                'id_jurnal'        => $jurnal->id_jurnal,
                'urutan'           => $urutan_jurnal++,
                'id_coa'           => $id_coa_persediaan_bahan,
                'debit'            => round($biaya_kirim, 2),
                'kredit'           => 0,
                'keterangan_baris' => 'Biaya kirim pembelian dikapitalisasi ke persediaan bahan baku ' . $row->no_faktur_pembelian,
                'id_pelanggan'     => null,
                'id_pemasok'       => (int) $row->id_pemasok,
                'id_produk'        => null,
                'id_bahan_baku'    => null,
                'id_gudang'        => null,
            ]);

            kapitalisasi_biaya_kirim_ke_stok_faktur_pembelian_posting(
                $row,
                $id_entitas,
                $id_pengguna,
                $id_faktur_pembelian,
                $biaya_kirim
            );
        }

        $total_debit = round($total_debit, 2);
        $total_kredit = round($total_kredit, 2);

        if (abs($total_debit - $total_kredit) > 0.01) {
            throw new RuntimeException(
                'Jurnal tidak balance. Debit Rp ' .
                number_format($total_debit, 2, '.', ',') .
                ' Kredit Rp ' .
                number_format($total_kredit, 2, '.', ',')
            );
        }

        if (abs($total_debit - $total) > 0.01) {
            throw new RuntimeException(
                'Total jurnal tidak sama dengan total faktur. Jurnal Rp ' .
                number_format($total_debit, 2, '.', ',') .
                ' Faktur Rp ' .
                number_format($total, 2, '.', ',')
            );
        }

        LogJurnalSumberORM::create([
            'id_entitas'             => $id_entitas,
            'id_jurnal'              => $jurnal->id_jurnal,
            'tabel_sumber'           => 'tb_faktur_pembelian',
            'id_sumber'              => $id_faktur_pembelian,
            'no_sumber'              => $row->no_faktur_pembelian,
            'kode_jenis_transaksi'   => $kode_jenis_transaksi,
            'tanggal_dibuat'         => date('Y-m-d H:i:s'),
        ]);

        /*
         * Kalau faktur tunai / transfer:
         * otomatis isi tabel pembayaran pembelian sebagai bukti lunas.
         * Status dibuat langsung posted karena jurnal kas/bank sudah dibuat
         * di jurnal faktur pembelian ini, jadi tidak perlu posting pembayaran lagi.
         */
        if (in_array($jenis_pembayaran, ['tunai', 'transfer'], true)) {
            $cek_pembayaran = PembayaranPembelianORM::query()
                ->where('id_entitas', $id_entitas)
                ->where('id_faktur_pembelian', $id_faktur_pembelian)
                ->first();

            if ($cek_pembayaran) {
                throw new RuntimeException('Faktur tunai/transfer ini sudah memiliki data pembayaran.');
            }

            $no_pembayaran = generate_kode_master(
                'tb_pembayaran_pembelian',
                'no_pembayaran_pembelian',
                'PBP',
                4,
                $id_entitas
            );

            PembayaranPembelianORM::create([
                'id_entitas'                 => $id_entitas,
                'no_pembayaran_pembelian'    => $no_pembayaran,
                'tanggal_pembayaran'         => $row->tanggal_faktur,
                'id_faktur_pembelian'        => $id_faktur_pembelian,
                'id_pemasok'                 => (int) $row->id_pemasok,
                'metode_pembayaran'          => $jenis_pembayaran,
                'id_coa_kas_bank'            => (int) $row->id_coa_kas_bank,
                'jumlah_bayar'               => $total,
                'catatan'                    => 'Otomatis lunas dari posting faktur pembelian ' . $row->no_faktur_pembelian,
                'status_posting'             => 'posted',
                'tanggal_dibuat'             => date('Y-m-d H:i:s'),
                'dibuat_oleh'                => $id_pengguna > 0 ? $id_pengguna : null,
                'tanggal_diubah'             => date('Y-m-d H:i:s'),
                'diubah_oleh'                => $id_pengguna > 0 ? $id_pengguna : null,
            ]);
        }

        $row->update([
            'status_faktur'   => 'posted',
            'sisa_utang'      => $jenis_pembayaran === 'kredit' ? $total : 0,
            'tanggal_posting' => date('Y-m-d H:i:s'),
            'diposting_oleh'  => $id_pengguna > 0 ? $id_pengguna : null,
            'tanggal_diubah'  => date('Y-m-d H:i:s'),
            'diubah_oleh'     => $id_pengguna > 0 ? $id_pengguna : null,
        ]);
    });

    if (in_array($jenis_pembayaran, ['tunai', 'transfer'], true)) {
        set_flash('success', 'Faktur pembelian berhasil diposting. ' . ucfirst($label_jurnal) . ' berhasil dibuat dan pembayaran otomatis sudah tercatat lunas.');
    } else {
        set_flash('success', 'Faktur pembelian berhasil diposting. ' . ucfirst($label_jurnal) . ' berhasil dibuat.');
    }

    header('Location: ' . admin_url('index.php?menu=pembelian/faktur/detail&id=' . $id_faktur_pembelian));
    exit;
} catch (Throwable $e) {
    set_flash('error', $e->getMessage());
    header('Location: ' . admin_url('index.php?menu=pembelian/faktur/detail&id=' . $id_faktur_pembelian));
    exit;
}