<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_pengguna = (int) (user_login()['id_pengguna'] ?? 0);
$id_pembayaran_penjualan = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

function pembayaran_penjualan_posting_kembali(int $id): void
{
    header('Location: ' . admin_url('index.php?menu=penjualan/pembayaran/detail&id=' . $id));
    exit;
}

function pembayaran_penjualan_generate_no_jurnal(int $id_entitas): string
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

function pembayaran_penjualan_periode_terbuka(int $id_entitas, string $tanggal): object
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

function pembayaran_penjualan_kode_template(string $metode): string
{
    return match ($metode) {
        'tunai' => 'PEMBAYARAN_PENJUALAN_TUNAI',
        'transfer' => 'PEMBAYARAN_PENJUALAN_TRANSFER',
        default => throw new RuntimeException('Metode pembayaran tidak valid.'),
    };
}

function pembayaran_penjualan_nominal_template(object $templateDetail, object $pembayaran): float
{
    $sumberNominal = strtolower(trim((string) ($templateDetail->sumber_nominal ?? '')));
    $rumusNominal = strtolower(trim((string) ($templateDetail->rumus_nominal ?? '')));

    if ($sumberNominal === 'jumlah_bayar') {
        return (float) $pembayaran->jumlah_bayar;
    }

    if ($sumberNominal === 'rumus_nominal' && in_array($rumusNominal, ['jumlah_bayar', 'total', 'total_pembayaran'], true)) {
        return (float) $pembayaran->jumlah_bayar;
    }

    if ($rumusNominal === 'jumlah_bayar') {
        return (float) $pembayaran->jumlah_bayar;
    }

    throw new RuntimeException('Rumus nominal template pembayaran tidak dikenali: ' . ($rumusNominal ?: $sumberNominal));
}

if ($id_pembayaran_penjualan <= 0) {
    set_flash('error', 'ID pembayaran penjualan tidak valid.');
    header('Location: ' . admin_url('index.php?menu=penjualan/pembayaran'));
    exit;
}

try {
    Capsule::connection()->transaction(function () use ($id_entitas, $id_pengguna, $id_pembayaran_penjualan) {
        $pembayaran = Capsule::table('tb_pembayaran_penjualan')
            ->where('id_entitas', $id_entitas)
            ->where('id_pembayaran_penjualan', $id_pembayaran_penjualan)
            ->lockForUpdate()
            ->first();

        if (!$pembayaran) {
            throw new RuntimeException('Data pembayaran penjualan tidak ditemukan.');
        }

        if ((string) $pembayaran->status_posting !== 'draft') {
            throw new RuntimeException('Hanya pembayaran draft yang bisa diposting.');
        }

        if ((float) $pembayaran->jumlah_bayar <= 0) {
            throw new RuntimeException('Jumlah bayar harus lebih besar dari 0.');
        }

        $faktur = Capsule::table('tb_faktur_penjualan')
            ->where('id_entitas', $id_entitas)
            ->where('id_faktur_penjualan', (int) $pembayaran->id_faktur_penjualan)
            ->lockForUpdate()
            ->first();

        if (!$faktur) {
            throw new RuntimeException('Piutang pelanggan tidak ditemukan.');
        }

        if ((string) $faktur->status_faktur !== 'posted') {
            throw new RuntimeException('Faktur belum posted.');
        }

        if ((string) $faktur->jenis_pembayaran !== 'kredit') {
            throw new RuntimeException('Pembayaran manual hanya untuk piutang kredit.');
        }

        if ((float) $faktur->sisa_piutang <= 0) {
            throw new RuntimeException('Faktur ini sudah lunas.');
        }

        if ((float) $pembayaran->jumlah_bayar > (float) $faktur->sisa_piutang) {
            throw new RuntimeException('Jumlah bayar melebihi sisa piutang faktur.');
        }

        $coaKasBank = Capsule::table('tb_coa')
            ->where('id_entitas', $id_entitas)
            ->where('id_coa', (int) $pembayaran->id_coa_kas_bank)
            ->where('boleh_transaksi', 1)
            ->where('status_aktif', 1)
            ->first();

        if (!$coaKasBank) {
            throw new RuntimeException('Akun kas/bank pembayaran tidak valid.');
        }

        $kodeTemplate = pembayaran_penjualan_kode_template((string) $pembayaran->metode_pembayaran);

        $sudahAdaJurnal = Capsule::table('tb_log_jurnal_sumber')
            ->where('id_entitas', $id_entitas)
            ->where('tabel_sumber', 'tb_pembayaran_penjualan')
            ->where('id_sumber', $id_pembayaran_penjualan)
            ->where('kode_jenis_transaksi', $kodeTemplate)
            ->exists();

        if ($sudahAdaJurnal) {
            throw new RuntimeException('Pembayaran ini sudah pernah diposting.');
        }

        $periode = pembayaran_penjualan_periode_terbuka($id_entitas, (string) $pembayaran->tanggal_pembayaran);

        $template = Capsule::table('tb_template_jurnal')
            ->where('id_entitas', $id_entitas)
            ->where('kode_jenis_transaksi', $kodeTemplate)
            ->where('status_aktif', 1)
            ->first();

        if (!$template) {
            throw new RuntimeException('Template jurnal ' . $kodeTemplate . ' belum tersedia atau belum aktif.');
        }

        $templateDetail = Capsule::table('tb_template_jurnal_detail')
            ->where('id_template_jurnal', (int) $template->id_template_jurnal)
            ->orderBy('urutan', 'asc')
            ->get();

        if ($templateDetail->count() === 0) {
            throw new RuntimeException('Detail template jurnal ' . $kodeTemplate . ' belum tersedia.');
        }

        $barisJurnal = [];
        $totalDebit = 0.0;
        $totalKredit = 0.0;

        foreach ($templateDetail as $td) {
            $posisi = strtolower(trim((string) $td->posisi_dc));
            $sumberAkun = strtolower(trim((string) $td->sumber_akun));

            if (!in_array($posisi, ['debit', 'kredit'], true)) {
                throw new RuntimeException('Posisi debit/kredit template jurnal tidak valid.');
            }

            if ($sumberAkun !== 'coa_default' || empty($td->id_coa_default)) {
                throw new RuntimeException('Template pembayaran penjualan harus memakai sumber_akun coa_default.');
            }

            $nominal = pembayaran_penjualan_nominal_template($td, $pembayaran);

            if ($nominal <= 0) {
                continue;
            }

            $idCoa = (int) $td->id_coa_default;

            if ($posisi === 'debit') {
                $idCoa = (int) $pembayaran->id_coa_kas_bank;
            }

            $debit = $posisi === 'debit' ? $nominal : 0;
            $kredit = $posisi === 'kredit' ? $nominal : 0;

            $totalDebit += $debit;
            $totalKredit += $kredit;

            $barisJurnal[] = [
                'urutan' => (int) $td->urutan,
                'id_coa' => $idCoa,
                'debit' => round($debit, 2),
                'kredit' => round($kredit, 2),
                'keterangan_baris' => $td->keterangan_baris ?: ('Pembayaran penjualan ' . (string) $pembayaran->no_pembayaran_penjualan),
            ];
        }

        $totalDebit = round($totalDebit, 2);
        $totalKredit = round($totalKredit, 2);

        if (count($barisJurnal) < 2) {
            throw new RuntimeException('Template jurnal pembayaran penjualan belum lengkap.');
        }

        if (abs($totalDebit - $totalKredit) > 0.01) {
            throw new RuntimeException('Jurnal tidak balance. Debit: ' . $totalDebit . ', Kredit: ' . $totalKredit);
        }

        $idJurnal = Capsule::table('tb_jurnal')->insertGetId([
            'id_entitas' => $id_entitas,
            'no_jurnal' => pembayaran_penjualan_generate_no_jurnal($id_entitas),
            'tanggal_jurnal' => (string) $pembayaran->tanggal_pembayaran,
            'id_periode' => (int) $periode->id_periode,
            'kode_jenis_transaksi' => $kodeTemplate,
            'keterangan' => 'Posting pembayaran penjualan ' . (string) $pembayaran->no_pembayaran_penjualan,
            'tabel_sumber' => 'tb_pembayaran_penjualan',
            'id_sumber' => $id_pembayaran_penjualan,
            'no_sumber' => (string) $pembayaran->no_pembayaran_penjualan,
            'status_jurnal' => 'posted',
            'total_debit' => $totalDebit,
            'total_kredit' => $totalKredit,
            'tanggal_dibuat' => date('Y-m-d H:i:s'),
            'dibuat_oleh' => $id_pengguna ?: null,
            'tanggal_posting' => date('Y-m-d H:i:s'),
            'diposting_oleh' => $id_pengguna ?: null,
        ]);

        foreach ($barisJurnal as $baris) {
            Capsule::table('tb_jurnal_detail')->insert([
                'id_jurnal' => $idJurnal,
                'urutan' => $baris['urutan'],
                'id_coa' => $baris['id_coa'],
                'debit' => $baris['debit'],
                'kredit' => $baris['kredit'],
                'keterangan_baris' => $baris['keterangan_baris'],
                'id_pelanggan' => (int) $pembayaran->id_pelanggan,
                'id_pemasok' => null,
                'id_produk' => null,
                'id_bahan_baku' => null,
                'id_gudang' => null,
            ]);
        }

        Capsule::table('tb_log_jurnal_sumber')->insert([
            'id_entitas' => $id_entitas,
            'id_jurnal' => $idJurnal,
            'tabel_sumber' => 'tb_pembayaran_penjualan',
            'id_sumber' => $id_pembayaran_penjualan,
            'no_sumber' => (string) $pembayaran->no_pembayaran_penjualan,
            'kode_jenis_transaksi' => $kodeTemplate,
            'tanggal_dibuat' => date('Y-m-d H:i:s'),
        ]);

        $sisaBaru = max(0, round((float) $faktur->sisa_piutang - (float) $pembayaran->jumlah_bayar, 2));

        Capsule::table('tb_faktur_penjualan')
            ->where('id_entitas', $id_entitas)
            ->where('id_faktur_penjualan', (int) $faktur->id_faktur_penjualan)
            ->update([
                'sisa_piutang' => $sisaBaru,
                'tanggal_diubah' => date('Y-m-d H:i:s'),
                'diubah_oleh' => $id_pengguna ?: null,
            ]);

        Capsule::table('tb_pembayaran_penjualan')
            ->where('id_entitas', $id_entitas)
            ->where('id_pembayaran_penjualan', $id_pembayaran_penjualan)
            ->update([
                'status_posting' => 'posted',
                'tanggal_diubah' => date('Y-m-d H:i:s'),
                'diubah_oleh' => $id_pengguna ?: null,
            ]);
    });

    set_flash('success', 'Pembayaran penjualan berhasil diposting. Jurnal penerimaan kas/bank dibuat dan sisa piutang faktur sudah berkurang.');
} catch (Throwable $e) {
    set_flash('error', $e->getMessage());
}

pembayaran_penjualan_posting_kembali($id_pembayaran_penjualan);