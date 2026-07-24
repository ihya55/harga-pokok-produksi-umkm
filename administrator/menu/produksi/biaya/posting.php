<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/kode.php';

require_once __DIR__ . '/../../../../orm/BiayaProduksiORM.php';
require_once __DIR__ . '/../../../../orm/BiayaProduksiDetailORM.php';
require_once __DIR__ . '/../../../../orm/JurnalORM.php';
require_once __DIR__ . '/../../../../orm/JurnalDetailORM.php';
require_once __DIR__ . '/../../../../orm/LogJurnalSumberORM.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_pengguna = (int) (user_login()['id_pengguna'] ?? 0);
$id_biaya_produksi = (int) ($_GET['id'] ?? 0);

$back_url = trim((string) ($_GET['back_url'] ?? ''));
if ($back_url === '') {
    $back_url = admin_url('index.php?menu=produksi/biaya');
}

function kode_template_biaya_produksi_posting(string $jenis): string
{
    $map = [
        'tenaga_kerja' => 'BIAYA_TENAGA_KERJA_LANGSUNG',
        'overhead'     => 'BIAYA_OVERHEAD_PABRIK',
    ];

    return $map[$jenis] ?? '';
}

$row = BiayaProduksiORM::query()
    ->where('id_entitas', $id_entitas)
    ->find($id_biaya_produksi);

if (!$row) {
    set_flash('error', 'Data biaya produksi tidak ditemukan.');
    header('Location: ' . $back_url);
    exit;
}

if ((string) $row->status_posting !== 'draft') {
    set_flash('error', 'Hanya biaya produksi draft yang bisa diposting.');
    header('Location: ' . admin_url('index.php?menu=produksi/biaya/detail&id=' . $id_biaya_produksi . '&back_url=' . urlencode($back_url)));
    exit;
}

$detail_rows = BiayaProduksiDetailORM::query()
    ->where('id_biaya_produksi', $id_biaya_produksi)
    ->get();

if ($detail_rows->count() === 0) {
    set_flash('error', 'Detail biaya produksi belum ada.');
    header('Location: ' . admin_url('index.php?menu=produksi/biaya/detail&id=' . $id_biaya_produksi . '&back_url=' . urlencode($back_url)));
    exit;
}

$total_detail = 0;

foreach ($detail_rows as $d) {
    $total_detail += (float) $d->jumlah_biaya;
}

if ($total_detail <= 0 || round($total_detail, 2) !== round((float) $row->jumlah_biaya, 2)) {
    set_flash('error', 'Total detail biaya tidak sesuai dengan total header.');
    header('Location: ' . admin_url('index.php?menu=produksi/biaya/detail&id=' . $id_biaya_produksi . '&back_url=' . urlencode($back_url)));
    exit;
}

try {
    Capsule::connection()->transaction(function () use (
        $row,
        $detail_rows,
        $id_biaya_produksi,
        $id_entitas,
        $id_pengguna
    ) {
        $cek_log = LogJurnalSumberORM::query()
            ->where('id_entitas', $id_entitas)
            ->where('tabel_sumber', 'tb_biaya_produksi')
            ->where('id_sumber', $id_biaya_produksi)
            ->first();

        if ($cek_log) {
            throw new RuntimeException('Biaya produksi ini sudah pernah dibuatkan jurnal.');
        }

        $periode = Capsule::table('tb_periode_akuntansi')
            ->where('id_entitas', $id_entitas)
            ->where('status_periode', 'terbuka')
            ->whereDate('tanggal_mulai', '<=', $row->tanggal_biaya)
            ->whereDate('tanggal_selesai', '>=', $row->tanggal_biaya)
            ->first();

        if (!$periode) {
            throw new RuntimeException('Periode akuntansi untuk tanggal biaya belum dibuka.');
        }

        $no_jurnal = generate_kode_master(
            'tb_jurnal',
            'no_jurnal',
            'JRN',
            4,
            $id_entitas
        );

        $jumlah_total = round((float) $row->jumlah_biaya, 2);

        $jurnal = JurnalORM::create([
            'id_entitas'             => $id_entitas,
            'no_jurnal'              => $no_jurnal,
            'tanggal_jurnal'         => $row->tanggal_biaya,
            'id_periode'             => $periode->id_periode,
            'kode_jenis_transaksi'   => 'BIAYA_PRODUKSI',
            'keterangan'             => 'Posting biaya produksi ' . $row->no_biaya_produksi,
            'tabel_sumber'           => 'tb_biaya_produksi',
            'id_sumber'              => $id_biaya_produksi,
            'no_sumber'              => $row->no_biaya_produksi,
            'status_jurnal'          => 'posted',
            'total_debit'            => $jumlah_total,
            'total_kredit'           => $jumlah_total,
            'tanggal_dibuat'         => date('Y-m-d H:i:s'),
            'dibuat_oleh'            => $id_pengguna > 0 ? $id_pengguna : null,
            'tanggal_posting'        => date('Y-m-d H:i:s'),
            'diposting_oleh'         => $id_pengguna > 0 ? $id_pengguna : null,
            'tanggal_diubah'         => null,
            'diubah_oleh'            => null,
        ]);

        $urutan = 1;
        $total_debit = 0;
        $total_kredit = 0;

        foreach ($detail_rows as $detail) {
            $kode_template = strtoupper(trim((string) ($detail->kode_jenis_transaksi_template ?? '')));
            if ($kode_template === '') {
                $kode_template = kode_template_biaya_produksi_posting((string) $detail->jenis_biaya_produksi);
            }

            if ($kode_template === '') {
                throw new RuntimeException('Kode template biaya produksi tidak valid.');
            }

            $template = Capsule::table('tb_template_jurnal')
                ->where('id_entitas', $id_entitas)
                ->where('kode_jenis_transaksi', $kode_template)
                ->where('status_aktif', 1)
                ->first();

            if (!$template) {
                throw new RuntimeException('Template jurnal ' . $kode_template . ' belum tersedia.');
            }

            $detail_template = Capsule::table('tb_template_jurnal_detail')
                ->where('id_template_jurnal', $template->id_template_jurnal)
                ->orderBy('urutan', 'asc')
                ->get();

            if ($detail_template->count() === 0) {
                throw new RuntimeException('Detail template jurnal ' . $kode_template . ' belum tersedia.');
            }

            $jumlah = round((float) $detail->jumlah_biaya, 2);

            foreach ($detail_template as $dt) {
                $debit = 0;
                $kredit = 0;

                if ((string) $dt->posisi_dc === 'debit') {
                    $debit = $jumlah;
                } elseif ((string) $dt->posisi_dc === 'kredit') {
                    $kredit = $jumlah;
                }

                $id_coa = (int) $dt->id_coa_default;

                if ((string) $dt->posisi_dc === 'kredit') {
                    $id_coa = (int) $detail->id_coa_lawan;
                }

                if ($id_coa <= 0) {
                    throw new RuntimeException('Akun template jurnal belum lengkap.');
                }

                JurnalDetailORM::create([
                    'id_jurnal'        => $jurnal->id_jurnal,
                    'urutan'           => $urutan++,
                    'id_coa'           => $id_coa,
                    'debit'            => $debit,
                    'kredit'           => $kredit,
                    'keterangan_baris' => ($dt->keterangan_baris ?? 'Biaya produksi') . ' - ' . $row->no_biaya_produksi,
                    'id_pelanggan'     => null,
                    'id_pemasok'       => null,
                    'id_produk'        => null,
                    'id_bahan_baku'    => null,
                    'id_gudang'        => null,
                ]);

                $total_debit += $debit;
                $total_kredit += $kredit;
            }
        }

        if (round($total_debit, 2) !== round($total_kredit, 2)) {
            throw new RuntimeException('Jurnal biaya produksi tidak balance.');
        }

        $jurnal->update([
            'total_debit'  => round($total_debit, 2),
            'total_kredit' => round($total_kredit, 2),
        ]);

        LogJurnalSumberORM::create([
            'id_entitas'             => $id_entitas,
            'id_jurnal'              => $jurnal->id_jurnal,
            'tabel_sumber'           => 'tb_biaya_produksi',
            'id_sumber'              => $id_biaya_produksi,
            'no_sumber'              => $row->no_biaya_produksi,
            'kode_jenis_transaksi'   => 'BIAYA_PRODUKSI',
            'tanggal_dibuat'         => date('Y-m-d H:i:s'),
        ]);

        $row->update([
            'status_posting' => 'posted',
            'tanggal_diubah' => date('Y-m-d H:i:s'),
            'diubah_oleh'    => $id_pengguna > 0 ? $id_pengguna : null,
        ]);
    });

    set_flash('success', 'Biaya produksi berhasil diposting dan jurnal otomatis sudah dibuat dari template.');
    header('Location: ' . admin_url('index.php?menu=produksi/biaya/detail&id=' . $id_biaya_produksi . '&back_url=' . urlencode($back_url)));
    exit;
} catch (Throwable $e) {
    set_flash('error', $e->getMessage());
    header('Location: ' . admin_url('index.php?menu=produksi/biaya/detail&id=' . $id_biaya_produksi . '&back_url=' . urlencode($back_url)));
    exit;
}