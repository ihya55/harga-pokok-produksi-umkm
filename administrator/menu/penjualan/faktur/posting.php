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
$id_faktur_penjualan = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

function faktur_posting_kembali(int $id): void
{
    header('Location: ' . admin_url('index.php?menu=penjualan/faktur/detail&id=' . $id));
    exit;
}

function faktur_generate_no_jurnal(int $id_entitas): string
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

function faktur_periode_terbuka(int $id_entitas, string $tanggal): object
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

function faktur_kode_template(string $jenis_pembayaran): string
{
    return match ($jenis_pembayaran) {
        'tunai' => 'FAKTUR_PENJUALAN_TUNAI',
        'transfer' => 'FAKTUR_PENJUALAN_TRANSFER',
        'kredit' => 'FAKTUR_PENJUALAN_KREDIT',
        default => throw new RuntimeException('Jenis pembayaran piutang tidak valid.'),
    };
}

function faktur_nominal_template(string $rumus, object $faktur): float
{
    $rumus = strtolower(trim($rumus));

    $subtotal = (float) $faktur->subtotal;
    $diskon = (float) $faktur->diskon;
    $subtotalSetelahDiskon = max(0, $subtotal - $diskon);

    return match ($rumus) {
        'subtotal' => $subtotal,
        'diskon' => $diskon,
        'subtotal_setelah_diskon' => $subtotalSetelahDiskon,
        'ppn' => (float) $faktur->ppn,
        'total' => (float) $faktur->total,
        default => throw new RuntimeException('Rumus nominal template tidak dikenali: ' . $rumus),
    };
}

if ($id_faktur_penjualan <= 0) {
    set_flash('error', 'ID piutang pelanggan tidak valid.');
    header('Location: ' . admin_url('index.php?menu=penjualan/faktur'));
    exit;
}

try {
    Capsule::connection()->transaction(function () use ($id_entitas, $id_pengguna, $id_faktur_penjualan) {
        $faktur = Capsule::table('tb_faktur_penjualan')
            ->where('id_entitas', $id_entitas)
            ->where('id_faktur_penjualan', $id_faktur_penjualan)
            ->lockForUpdate()
            ->first();

        if (!$faktur) {
            throw new RuntimeException('Data piutang pelanggan tidak ditemukan.');
        }

        if ((string) $faktur->status_faktur !== 'draft') {
            throw new RuntimeException('Hanya faktur draft yang bisa diposting.');
        }

        if ((float) $faktur->total <= 0) {
            throw new RuntimeException('Total faktur harus lebih besar dari 0.');
        }

        $penyerahan = Capsule::table('tb_penyerahan_penjualan')
            ->where('id_entitas', $id_entitas)
            ->where('id_penyerahan_penjualan', (int) $faktur->id_penyerahan_penjualan)
            ->where('status_penyerahan', 'posted')
            ->first();

        if (!$penyerahan) {
            throw new RuntimeException('Penyerahan sumber tidak ditemukan atau belum posted.');
        }

        $detailCount = Capsule::table('tb_faktur_penjualan_detail')
            ->where('id_faktur_penjualan', $id_faktur_penjualan)
            ->count();

        if ($detailCount <= 0) {
            throw new RuntimeException('Detail faktur masih kosong.');
        }

        $kodeTemplate = faktur_kode_template((string) $faktur->jenis_pembayaran);

        $sudahAdaJurnal = Capsule::table('tb_log_jurnal_sumber')
            ->where('id_entitas', $id_entitas)
            ->where('tabel_sumber', 'tb_faktur_penjualan')
            ->where('id_sumber', $id_faktur_penjualan)
            ->where('kode_jenis_transaksi', $kodeTemplate)
            ->exists();

        if ($sudahAdaJurnal) {
            throw new RuntimeException('Faktur ini sudah pernah diposting.');
        }

        $periode = faktur_periode_terbuka($id_entitas, (string) $faktur->tanggal_faktur);

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
            $sumberNominal = strtolower(trim((string) $td->sumber_nominal));
            $rumusNominal = trim((string) $td->rumus_nominal);

            if (!in_array($posisi, ['debit', 'kredit'], true)) {
                throw new RuntimeException('Posisi debit/kredit template jurnal tidak valid.');
            }

            if ($sumberAkun !== 'coa_default' || empty($td->id_coa_default)) {
                throw new RuntimeException('Template piutang pelanggan harus memakai sumber_akun coa_default.');
            }

            if ($sumberNominal !== 'rumus_nominal') {
                throw new RuntimeException('Template piutang pelanggan harus memakai sumber_nominal rumus_nominal.');
            }

            $nominal = faktur_nominal_template($rumusNominal, $faktur);

            if ($nominal <= 0) {
                continue;
            }

            $debit = $posisi === 'debit' ? $nominal : 0;
            $kredit = $posisi === 'kredit' ? $nominal : 0;

            $totalDebit += $debit;
            $totalKredit += $kredit;

            $barisJurnal[] = [
                'urutan' => (int) $td->urutan,
                'id_coa' => (int) $td->id_coa_default,
                'debit' => round($debit, 2),
                'kredit' => round($kredit, 2),
                'keterangan_baris' => $td->keterangan_baris ?: ('Piutang pelanggan ' . (string) $faktur->no_faktur_penjualan),
            ];
        }

        $totalDebit = round($totalDebit, 2);
        $totalKredit = round($totalKredit, 2);

        if (count($barisJurnal) < 2) {
            throw new RuntimeException('Template jurnal piutang pelanggan belum lengkap.');
        }

        if (abs($totalDebit - $totalKredit) > 0.01) {
            throw new RuntimeException('Jurnal tidak balance. Debit: ' . $totalDebit . ', Kredit: ' . $totalKredit);
        }

        $idJurnal = Capsule::table('tb_jurnal')->insertGetId([
            'id_entitas' => $id_entitas,
            'no_jurnal' => faktur_generate_no_jurnal($id_entitas),
            'tanggal_jurnal' => (string) $faktur->tanggal_faktur,
            'id_periode' => (int) $periode->id_periode,
            'kode_jenis_transaksi' => $kodeTemplate,
            'keterangan' => 'Posting piutang pelanggan ' . (string) $faktur->no_faktur_penjualan,
            'tabel_sumber' => 'tb_faktur_penjualan',
            'id_sumber' => $id_faktur_penjualan,
            'no_sumber' => (string) $faktur->no_faktur_penjualan,
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
                'id_pelanggan' => (int) $faktur->id_pelanggan,
                'id_pemasok' => null,
                'id_produk' => null,
                'id_bahan_baku' => null,
                'id_gudang' => null,
            ]);
        }

        Capsule::table('tb_log_jurnal_sumber')->insert([
            'id_entitas' => $id_entitas,
            'id_jurnal' => $idJurnal,
            'tabel_sumber' => 'tb_faktur_penjualan',
            'id_sumber' => $id_faktur_penjualan,
            'no_sumber' => (string) $faktur->no_faktur_penjualan,
            'kode_jenis_transaksi' => $kodeTemplate,
            'tanggal_dibuat' => date('Y-m-d H:i:s'),
        ]);

        $sisaPiutang = (string) $faktur->jenis_pembayaran === 'kredit'
            ? (float) $faktur->total
            : 0;

        Capsule::table('tb_faktur_penjualan')
            ->where('id_entitas', $id_entitas)
            ->where('id_faktur_penjualan', $id_faktur_penjualan)
            ->update([
                'status_faktur' => 'posted',
                'sisa_piutang' => round($sisaPiutang, 2),
                'tanggal_posting' => date('Y-m-d H:i:s'),
                'diposting_oleh' => $id_pengguna ?: null,
                'tanggal_diubah' => date('Y-m-d H:i:s'),
                'diubah_oleh' => $id_pengguna ?: null,
            ]);
    });

    set_flash('success', 'Piutang pelanggan berhasil diposting dan jurnal penjualan sudah dibuat.');
} catch (Throwable $e) {
    set_flash('error', $e->getMessage());
}

faktur_posting_kembali($id_faktur_penjualan);