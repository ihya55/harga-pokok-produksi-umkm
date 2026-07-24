<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

require_once __DIR__ . '/../../../../orm/StokOpnameORM.php';
require_once __DIR__ . '/../../../../orm/StokOpnameDetailORM.php';
require_once __DIR__ . '/../../../../orm/SaldoStokORM.php';
require_once __DIR__ . '/../../../../orm/MutasiStokORM.php';
require_once __DIR__ . '/../../../../orm/BahanBakuORM.php';
require_once __DIR__ . '/../../../../orm/ProdukORM.php';
require_once __DIR__ . '/../../../../orm/PeriodeAkuntansiORM.php';
require_once __DIR__ . '/../../../../orm/JurnalORM.php';
require_once __DIR__ . '/../../../../orm/JurnalDetailORM.php';
require_once __DIR__ . '/../../../../orm/LogJurnalSumberORM.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

$user_login = user_login();
$id_entitas = (int) ($user_login['id_entitas'] ?? 0);
$id_pengguna = (int) ($user_login['id_pengguna'] ?? 0);

$id_stok_opname = (int) ($_GET['id'] ?? 0);
$back_url = trim((string) ($_GET['back_url'] ?? ''));

if ($back_url === '') {
    $back_url = admin_page_url('persediaan/stok-opname');
}

function generate_no_jurnal_stok_opname(int $id_entitas): string
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

function get_or_create_coa_stok_opname(
    int $id_entitas,
    string $kode_coa,
    string $nama_coa,
    ?string $kode_induk,
    int $level_coa,
    string $kategori_coa,
    string $posisi_saldo_normal,
    int $id_pengguna
): int {
    $coa = Capsule::table('tb_coa')
        ->where('id_entitas', $id_entitas)
        ->where('kode_coa', $kode_coa)
        ->first();

    if ($coa) {
        return (int) $coa->id_coa;
    }

    $id_coa_induk = null;

    if ($kode_induk !== null && $kode_induk !== '') {
        $induk = Capsule::table('tb_coa')
            ->where('id_entitas', $id_entitas)
            ->where('kode_coa', $kode_induk)
            ->first();

        if ($induk) {
            $id_coa_induk = (int) $induk->id_coa;
        }
    }

    return (int) Capsule::table('tb_coa')->insertGetId([
        'id_entitas' => $id_entitas,
        'kode_coa' => $kode_coa,
        'nama_coa' => $nama_coa,
        'id_coa_induk' => $id_coa_induk,
        'level_coa' => $level_coa,
        'kategori_coa' => $kategori_coa,
        'posisi_saldo_normal' => $posisi_saldo_normal,
        'boleh_transaksi' => 1,
        'status_aktif' => 1,
        'tanggal_dibuat' => date('Y-m-d H:i:s'),
        'dibuat_oleh' => $id_pengguna ?: null,
    ]);
}

function ensure_template_jurnal_stok_opname(
    int $id_entitas,
    int $id_pengguna,
    int $id_coa_pendapatan_selisih,
    int $id_coa_beban_selisih
): void {
    $templatePlus = Capsule::table('tb_template_jurnal')
        ->where('id_entitas', $id_entitas)
        ->where('kode_jenis_transaksi', 'STOK_OPNAME_PLUS')
        ->first();

    if (!$templatePlus) {
        $id_template_plus = (int) Capsule::table('tb_template_jurnal')->insertGetId([
            'id_entitas' => $id_entitas,
            'kode_template_jurnal' => 'SOPP-0001',
            'nama_template_jurnal' => 'Template Stok Opname Selisih Lebih',
            'kode_jenis_transaksi' => 'STOK_OPNAME_PLUS',
            'keterangan' => 'Debit persediaan, kredit pendapatan selisih stok',
            'status_aktif' => 1,
            'tanggal_dibuat' => date('Y-m-d H:i:s'),
            'dibuat_oleh' => $id_pengguna ?: null,
        ]);

        Capsule::table('tb_template_jurnal_detail')->insert([
            [
                'id_template_jurnal' => $id_template_plus,
                'urutan' => 1,
                'posisi_dc' => 'debit',
                'sumber_akun' => 'akun_persediaan_barang',
                'id_coa_default' => null,
                'sumber_nominal' => 'rumus_nominal',
                'rumus_nominal' => 'nilai_selisih_abs',
                'keterangan_baris' => 'Persediaan bertambah karena stok opname',
            ],
            [
                'id_template_jurnal' => $id_template_plus,
                'urutan' => 2,
                'posisi_dc' => 'kredit',
                'sumber_akun' => 'coa_default',
                'id_coa_default' => $id_coa_pendapatan_selisih,
                'sumber_nominal' => 'rumus_nominal',
                'rumus_nominal' => 'nilai_selisih_abs',
                'keterangan_baris' => 'Pendapatan selisih stok',
            ],
        ]);
    }

    $templateMinus = Capsule::table('tb_template_jurnal')
        ->where('id_entitas', $id_entitas)
        ->where('kode_jenis_transaksi', 'STOK_OPNAME_MINUS')
        ->first();

    if (!$templateMinus) {
        $id_template_minus = (int) Capsule::table('tb_template_jurnal')->insertGetId([
            'id_entitas' => $id_entitas,
            'kode_template_jurnal' => 'SOPM-0001',
            'nama_template_jurnal' => 'Template Stok Opname Selisih Kurang',
            'kode_jenis_transaksi' => 'STOK_OPNAME_MINUS',
            'keterangan' => 'Debit beban selisih stok, kredit persediaan',
            'status_aktif' => 1,
            'tanggal_dibuat' => date('Y-m-d H:i:s'),
            'dibuat_oleh' => $id_pengguna ?: null,
        ]);

        Capsule::table('tb_template_jurnal_detail')->insert([
            [
                'id_template_jurnal' => $id_template_minus,
                'urutan' => 1,
                'posisi_dc' => 'debit',
                'sumber_akun' => 'coa_default',
                'id_coa_default' => $id_coa_beban_selisih,
                'sumber_nominal' => 'rumus_nominal',
                'rumus_nominal' => 'nilai_selisih_abs',
                'keterangan_baris' => 'Beban selisih stok opname',
            ],
            [
                'id_template_jurnal' => $id_template_minus,
                'urutan' => 2,
                'posisi_dc' => 'kredit',
                'sumber_akun' => 'akun_persediaan_barang',
                'id_coa_default' => null,
                'sumber_nominal' => 'rumus_nominal',
                'rumus_nominal' => 'nilai_selisih_abs',
                'keterangan_baris' => 'Persediaan berkurang karena stok opname',
            ],
        ]);
    }
}

function get_coa_konfigurasi_stok_opname(int $id_entitas, string $kode_konfigurasi): int
{
    $row = Capsule::table('tb_konfigurasi_akun')
        ->where('id_entitas', $id_entitas)
        ->where('kode_konfigurasi', $kode_konfigurasi)
        ->where('status_aktif', 1)
        ->first();

    return $row ? (int) $row->id_coa : 0;
}

function get_coa_by_kode_stok_opname(int $id_entitas, string $kode_coa): int
{
    $row = Capsule::table('tb_coa')
        ->where('id_entitas', $id_entitas)
        ->where('kode_coa', $kode_coa)
        ->where('status_aktif', 1)
        ->first();

    return $row ? (int) $row->id_coa : 0;
}

function get_info_barang_stok_opname(int $id_entitas, string $jenis_barang, int $id_referensi_barang): array
{
    if ($jenis_barang === 'bahan_baku') {
        $barang = BahanBakuORM::query()
            ->where('id_entitas', $id_entitas)
            ->where('id_bahan_baku', $id_referensi_barang)
            ->first();

        if (!$barang) {
            throw new Exception('Bahan baku pada detail stok opname tidak ditemukan.');
        }

        return [
            'nama_barang' => (string) ($barang->nama_bahan_baku ?? 'Bahan Baku'),
            'id_bahan_baku' => $id_referensi_barang,
            'id_produk' => null,
            'id_coa_persediaan' => (int) ($barang->id_coa_persediaan ?? 0),
        ];
    }

    if ($jenis_barang === 'produk') {
        $barang = ProdukORM::query()
            ->where('id_entitas', $id_entitas)
            ->where('id_produk', $id_referensi_barang)
            ->first();

        if (!$barang) {
            throw new Exception('Produk pada detail stok opname tidak ditemukan.');
        }

        return [
            'nama_barang' => (string) ($barang->nama_produk ?? 'Produk'),
            'id_bahan_baku' => null,
            'id_produk' => $id_referensi_barang,
            'id_coa_persediaan' => (int) ($barang->id_coa_persediaan ?? 0),
        ];
    }

    throw new Exception('Jenis barang tidak valid.');
}

if ($id_stok_opname <= 0) {
    set_flash('error', 'ID stok opname tidak valid.');
    header('Location: ' . $back_url);
    exit;
}

try {
    Capsule::connection()->transaction(function () use (
        $id_entitas,
        $id_pengguna,
        $id_stok_opname
    ) {
        $header = StokOpnameORM::query()
            ->where('id_entitas', $id_entitas)
            ->where('id_stok_opname', $id_stok_opname)
            ->lockForUpdate()
            ->first();

        if (!$header) {
            throw new Exception('Data stok opname tidak ditemukan.');
        }

        if ((string) $header->status_posting !== 'draft') {
            throw new Exception('Stok opname sudah pernah diposting.');
        }

        $detail_rows = StokOpnameDetailORM::query()
            ->where('id_stok_opname', $id_stok_opname)
            ->orderBy('id_stok_opname_detail', 'asc')
            ->get();

        if ($detail_rows->count() <= 0) {
            throw new Exception('Detail stok opname masih kosong.');
        }

        $periode = PeriodeAkuntansiORM::query()
            ->where('id_entitas', $id_entitas)
            ->where('tanggal_mulai', '<=', $header->tanggal_stok_opname)
            ->where('tanggal_selesai', '>=', $header->tanggal_stok_opname)
            ->where('status_periode', 'terbuka')
            ->first();

        if (!$periode) {
            throw new Exception('Periode akuntansi untuk tanggal stok opname belum dibuka atau sudah ditutup.');
        }

        $id_coa_pendapatan_selisih = get_or_create_coa_stok_opname(
            $id_entitas,
            '4210',
            'Pendapatan Selisih Stok',
            '4200',
            3,
            'pendapatan',
            'kredit',
            $id_pengguna
        );

        $id_coa_beban_selisih = get_or_create_coa_stok_opname(
            $id_entitas,
            '6800',
            'Beban Selisih Stok',
            '6000',
            2,
            'beban',
            'debit',
            $id_pengguna
        );

        ensure_template_jurnal_stok_opname(
            $id_entitas,
            $id_pengguna,
            $id_coa_pendapatan_selisih,
            $id_coa_beban_selisih
        );

        $total_plus = 0.0;
        $total_minus = 0.0;
        $jurnal_detail_rows = [];
        $urutan_jurnal = 1;

        foreach ($detail_rows as $detail) {
            $jenis_barang = (string) $detail->jenis_barang;
            $id_referensi_barang = (int) $detail->id_referensi_barang;
            $qty_sistem_dokumen = (float) $detail->qty_sistem;
            $qty_fisik = (float) $detail->qty_fisik;
            $selisih_qty = round((float) $detail->selisih_qty, 3);
            $harga_satuan = (float) $detail->harga_satuan;

            if (!in_array($jenis_barang, ['bahan_baku', 'produk'], true)) {
                throw new Exception('Jenis barang tidak valid pada detail stok opname.');
            }

            if ($id_referensi_barang <= 0) {
                throw new Exception('Barang pada detail stok opname tidak valid.');
            }

            if ($qty_fisik < 0) {
                throw new Exception('Qty fisik tidak boleh minus.');
            }

            $saldo = SaldoStokORM::query()
                ->where('id_entitas', $id_entitas)
                ->where('id_gudang', (int) $header->id_gudang)
                ->where('jenis_barang', $jenis_barang)
                ->where('id_referensi_barang', $id_referensi_barang)
                ->lockForUpdate()
                ->first();

            if (!$saldo) {
                throw new Exception('Saldo stok tidak ditemukan untuk salah satu barang di gudang opname.');
            }

            $qty_saldo_sekarang = (float) $saldo->qty_saldo;

            if (abs($qty_saldo_sekarang - $qty_sistem_dokumen) > 0.001) {
                throw new Exception(
                    'Saldo sistem untuk salah satu barang sudah berubah sejak draft opname dibuat. ' .
                    'Silakan edit stok opname agar qty sistem terbaru terambil ulang.'
                );
            }

            $info_barang = get_info_barang_stok_opname(
                $id_entitas,
                $jenis_barang,
                $id_referensi_barang
            );

            $id_coa_persediaan = (int) $info_barang['id_coa_persediaan'];

            if ($id_coa_persediaan <= 0) {
                if ($jenis_barang === 'bahan_baku') {
                    $id_coa_persediaan = get_coa_konfigurasi_stok_opname($id_entitas, 'persediaan_bahan_baku');
                    if ($id_coa_persediaan <= 0) {
                        $id_coa_persediaan = get_coa_by_kode_stok_opname($id_entitas, '1140');
                    }
                } else {
                    $id_coa_persediaan = get_coa_konfigurasi_stok_opname($id_entitas, 'persediaan_barang_jadi');
                    if ($id_coa_persediaan <= 0) {
                        $id_coa_persediaan = get_coa_by_kode_stok_opname($id_entitas, '1170');
                    }
                }
            }

            if ($id_coa_persediaan <= 0) {
                throw new Exception('Akun persediaan untuk barang "' . $info_barang['nama_barang'] . '" belum tersedia.');
            }

            if (abs($selisih_qty) <= 0.0001) {
                continue;
            }

            $nilai_selisih_abs = round(abs($selisih_qty * $harga_satuan), 2);

            if ($nilai_selisih_abs < 0) {
                throw new Exception('Nilai selisih tidak valid.');
            }

            if ($selisih_qty > 0) {
                MutasiStokORM::create([
                    'id_entitas' => $id_entitas,
                    'tanggal_mutasi' => $header->tanggal_stok_opname . ' 00:00:00',
                    'jenis_barang' => $jenis_barang,
                    'id_referensi_barang' => $id_referensi_barang,
                    'id_gudang' => (int) $header->id_gudang,
                    'jenis_mutasi' => 'opname_plus',
                    'qty_masuk' => $selisih_qty,
                    'qty_keluar' => 0,
                    'harga_satuan' => $harga_satuan,
                    'nilai_total' => $nilai_selisih_abs,
                    'tabel_sumber' => 'tb_stok_opname',
                    'id_sumber' => (int) $header->id_stok_opname,
                    'no_sumber' => (string) $header->no_stok_opname,
                    'keterangan' => 'Selisih lebih stok opname',
                    'tanggal_dibuat' => date('Y-m-d H:i:s'),
                    'dibuat_oleh' => $id_pengguna ?: null,
                ]);

                $jurnal_detail_rows[] = [
                    'urutan' => $urutan_jurnal++,
                    'id_coa' => $id_coa_persediaan,
                    'debit' => $nilai_selisih_abs,
                    'kredit' => 0,
                    'keterangan_baris' => 'Persediaan bertambah karena opname - ' . $info_barang['nama_barang'],
                    'id_pelanggan' => null,
                    'id_pemasok' => null,
                    'id_produk' => $info_barang['id_produk'],
                    'id_bahan_baku' => $info_barang['id_bahan_baku'],
                    'id_gudang' => (int) $header->id_gudang,
                ];

                $total_plus += $nilai_selisih_abs;
            } else {
                $qty_keluar = abs($selisih_qty);

                MutasiStokORM::create([
                    'id_entitas' => $id_entitas,
                    'tanggal_mutasi' => $header->tanggal_stok_opname . ' 00:00:00',
                    'jenis_barang' => $jenis_barang,
                    'id_referensi_barang' => $id_referensi_barang,
                    'id_gudang' => (int) $header->id_gudang,
                    'jenis_mutasi' => 'opname_minus',
                    'qty_masuk' => 0,
                    'qty_keluar' => $qty_keluar,
                    'harga_satuan' => $harga_satuan,
                    'nilai_total' => $nilai_selisih_abs,
                    'tabel_sumber' => 'tb_stok_opname',
                    'id_sumber' => (int) $header->id_stok_opname,
                    'no_sumber' => (string) $header->no_stok_opname,
                    'keterangan' => 'Selisih kurang stok opname',
                    'tanggal_dibuat' => date('Y-m-d H:i:s'),
                    'dibuat_oleh' => $id_pengguna ?: null,
                ]);

                $jurnal_detail_rows[] = [
                    'urutan' => $urutan_jurnal++,
                    'id_coa' => $id_coa_persediaan,
                    'debit' => 0,
                    'kredit' => $nilai_selisih_abs,
                    'keterangan_baris' => 'Persediaan berkurang karena opname - ' . $info_barang['nama_barang'],
                    'id_pelanggan' => null,
                    'id_pemasok' => null,
                    'id_produk' => $info_barang['id_produk'],
                    'id_bahan_baku' => $info_barang['id_bahan_baku'],
                    'id_gudang' => (int) $header->id_gudang,
                ];

                $total_minus += $nilai_selisih_abs;
            }

            $saldo->qty_saldo = $qty_fisik;
            $saldo->nilai_saldo = round($qty_fisik * $harga_satuan, 2);
            $saldo->hpp_rata_rata = $harga_satuan;
            $saldo->tanggal_update = date('Y-m-d H:i:s');
            $saldo->tanggal_diubah = date('Y-m-d H:i:s');
            $saldo->diubah_oleh = $id_pengguna ?: null;
            $saldo->save();
        }

        $total_debit = 0.0;
        $total_kredit = 0.0;

        if ($total_minus > 0) {
            $jurnal_detail_rows[] = [
                'urutan' => $urutan_jurnal++,
                'id_coa' => $id_coa_beban_selisih,
                'debit' => $total_minus,
                'kredit' => 0,
                'keterangan_baris' => 'Beban selisih kurang stok opname ' . $header->no_stok_opname,
                'id_pelanggan' => null,
                'id_pemasok' => null,
                'id_produk' => null,
                'id_bahan_baku' => null,
                'id_gudang' => (int) $header->id_gudang,
            ];
        }

        if ($total_plus > 0) {
            $jurnal_detail_rows[] = [
                'urutan' => $urutan_jurnal++,
                'id_coa' => $id_coa_pendapatan_selisih,
                'debit' => 0,
                'kredit' => $total_plus,
                'keterangan_baris' => 'Pendapatan selisih lebih stok opname ' . $header->no_stok_opname,
                'id_pelanggan' => null,
                'id_pemasok' => null,
                'id_produk' => null,
                'id_bahan_baku' => null,
                'id_gudang' => (int) $header->id_gudang,
            ];
        }

        foreach ($jurnal_detail_rows as $jd) {
            $total_debit += (float) $jd['debit'];
            $total_kredit += (float) $jd['kredit'];
        }

        if (round($total_debit, 2) !== round($total_kredit, 2)) {
            throw new Exception('Jurnal stok opname tidak balance.');
        }

        if ($total_debit > 0 || $total_kredit > 0) {
            $no_jurnal = generate_no_jurnal_stok_opname($id_entitas);

            $jurnal = JurnalORM::create([
                'id_entitas' => $id_entitas,
                'no_jurnal' => $no_jurnal,
                'tanggal_jurnal' => $header->tanggal_stok_opname,
                'id_periode' => (int) $periode->id_periode,
                'kode_jenis_transaksi' => 'STOK_OPNAME',
                'keterangan' => 'Posting stok opname ' . $header->no_stok_opname,
                'tabel_sumber' => 'tb_stok_opname',
                'id_sumber' => (int) $header->id_stok_opname,
                'no_sumber' => (string) $header->no_stok_opname,
                'status_jurnal' => 'posted',
                'total_debit' => round($total_debit, 2),
                'total_kredit' => round($total_kredit, 2),
                'tanggal_dibuat' => date('Y-m-d H:i:s'),
                'dibuat_oleh' => $id_pengguna ?: null,
                'tanggal_posting' => date('Y-m-d H:i:s'),
                'diposting_oleh' => $id_pengguna ?: null,
            ]);

            foreach ($jurnal_detail_rows as $jd) {
                $jd['id_jurnal'] = (int) $jurnal->id_jurnal;
                JurnalDetailORM::create($jd);
            }

            LogJurnalSumberORM::create([
                'id_entitas' => $id_entitas,
                'id_jurnal' => (int) $jurnal->id_jurnal,
                'tabel_sumber' => 'tb_stok_opname',
                'id_sumber' => (int) $header->id_stok_opname,
                'no_sumber' => (string) $header->no_stok_opname,
                'kode_jenis_transaksi' => 'STOK_OPNAME',
                'tanggal_dibuat' => date('Y-m-d H:i:s'),
            ]);
        }

        $header->status_posting = 'posted';
        $header->tanggal_diubah = date('Y-m-d H:i:s');
        $header->diubah_oleh = $id_pengguna ?: null;
        $header->save();
    });

    set_flash('success', 'Stok opname berhasil diposting. Mutasi stok, saldo stok, dan jurnal koreksi berhasil diproses.');
    header('Location: ' . admin_page_url('persediaan/stok-opname/detail') . '&id=' . $id_stok_opname . '&back_url=' . urlencode($back_url));
    exit;
} catch (Throwable $e) {
    set_flash('error', 'Gagal posting stok opname: ' . $e->getMessage());
    header('Location: ' . admin_page_url('persediaan/stok-opname/detail') . '&id=' . $id_stok_opname . '&back_url=' . urlencode($back_url));
    exit;
}