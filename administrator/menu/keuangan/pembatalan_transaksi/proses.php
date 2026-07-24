<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';
require_once __DIR__ . '/../_keuangan_helper.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

$id_entitas_login = keu_id_entitas();
$id_pengguna = keu_id_pengguna();
$user = keu_user();

$role_user = strtolower(trim((string) (
    $user['kode_role'] ??
    $user['role'] ??
    $user['nama_role'] ??
    ''
)));

$is_super_admin = $role_user === 'super_admin';
$is_admin_entitas = $role_user === 'admin_entitas';

if (!$is_super_admin && !$is_admin_entitas) {
    set_flash('error', 'Anda tidak memiliki akses untuk membatalkan transaksi.');
    redirect_admin('keuangan/pembatalan-transaksi');
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect_admin('keuangan/pembatalan-transaksi');
    }

    $id_jurnal = (int) ($_POST['id_jurnal'] ?? 0);
    $tanggal_pembatalan = keu_tanggal_mysql($_POST['tanggal_pembatalan'] ?? null, date('Y-m-d'));
    $alasan = trim((string) ($_POST['alasan'] ?? ''));

    if ($id_jurnal <= 0) {
        throw new RuntimeException('Jurnal asal tidak valid.');
    }

    if ($alasan === '') {
        throw new RuntimeException('Alasan pembatalan wajib diisi.');
    }

    $jurnal = Capsule::table('tb_jurnal')
        ->where('id_jurnal', $id_jurnal)
        ->first();

    if (!$jurnal) {
        throw new RuntimeException('Jurnal asal tidak ditemukan.');
    }

    if (!$is_super_admin && (int) $jurnal->id_entitas !== $id_entitas_login) {
        throw new RuntimeException('Anda hanya boleh membatalkan transaksi milik entitas sendiri.');
    }

    if ((string) $jurnal->status_jurnal !== 'posted') {
        throw new RuntimeException('Hanya jurnal posted yang bisa dibatalkan.');
    }

    if (str_starts_with((string) $jurnal->kode_jenis_transaksi, 'REVERSAL_')) {
        throw new RuntimeException('Jurnal reversal tidak boleh dibatalkan dari menu ini.');
    }

    if ((string) $jurnal->kode_jenis_transaksi === 'SALDO_AWAL_COA') {
        throw new RuntimeException('Saldo awal COA tidak dibatalkan dari menu ini. Gunakan jurnal penyesuaian.');
    }

    $sudahBatal = Capsule::table('tb_pembatalan_transaksi')
        ->where('id_entitas', (int) $jurnal->id_entitas)
        ->where('id_jurnal_asal', $id_jurnal)
        ->where('status_pembatalan', 'posted')
        ->exists();

    if ($sudahBatal) {
        throw new RuntimeException('Transaksi ini sudah pernah dibatalkan.');
    }

    $detail = Capsule::table('tb_jurnal_detail')
        ->where('id_jurnal', $id_jurnal)
        ->orderBy('urutan', 'asc')
        ->orderBy('id_jurnal_detail', 'asc')
        ->get();

    if ($detail->count() === 0) {
        throw new RuntimeException('Detail jurnal asal tidak ditemukan.');
    }

    $periode = keu_periode_terbuka((int) $jurnal->id_entitas, $tanggal_pembatalan);

    Capsule::connection()->transaction(function () use (
        $jurnal,
        $detail,
        $tanggal_pembatalan,
        $alasan,
        $periode,
        $id_pengguna
    ) {
        $id_entitas = (int) $jurnal->id_entitas;

        $noPembatalan = keu_generate_nomor(
            'tb_pembatalan_transaksi',
            'no_pembatalan',
            $id_entitas,
            'BTL-'
        );

        $idPembatalan = Capsule::table('tb_pembatalan_transaksi')->insertGetId([
            'id_entitas' => $id_entitas,
            'no_pembatalan' => $noPembatalan,
            'tanggal_pembatalan' => $tanggal_pembatalan,

            'id_jurnal_asal' => (int) $jurnal->id_jurnal,
            'no_jurnal_asal' => (string) $jurnal->no_jurnal,

            'id_jurnal_reversal' => null,
            'no_jurnal_reversal' => null,

            'kode_jenis_transaksi_asal' => (string) ($jurnal->kode_jenis_transaksi ?? ''),
            'tabel_sumber' => $jurnal->tabel_sumber ?? null,
            'id_sumber' => $jurnal->id_sumber ?? null,
            'no_sumber' => $jurnal->no_sumber ?? null,

            'alasan_pembatalan' => $alasan,
            'status_pembatalan' => 'posted',

            'tanggal_dibuat' => date('Y-m-d H:i:s'),
            'dibuat_oleh' => $id_pengguna ?: null,
        ]);

        $noJurnalReversal = keu_generate_no_jurnal($id_entitas);

        $kodeReversal = 'REVERSAL_' . strtoupper((string) ($jurnal->kode_jenis_transaksi ?? 'JURNAL'));
        $kodeReversal = substr($kodeReversal, 0, 80);

        $idJurnalReversal = Capsule::table('tb_jurnal')->insertGetId([
            'id_entitas' => $id_entitas,
            'no_jurnal' => $noJurnalReversal,
            'tanggal_jurnal' => $tanggal_pembatalan,
            'id_periode' => (int) $periode->id_periode,
            'kode_jenis_transaksi' => $kodeReversal,
            'keterangan' => 'Pembatalan ' . $jurnal->no_jurnal . ' - ' . $alasan,
            'tabel_sumber' => 'tb_pembatalan_transaksi',
            'id_sumber' => $idPembatalan,
            'no_sumber' => $noPembatalan,
            'status_jurnal' => 'posted',
            'total_debit' => (float) $jurnal->total_debit,
            'total_kredit' => (float) $jurnal->total_kredit,
            'tanggal_dibuat' => date('Y-m-d H:i:s'),
            'dibuat_oleh' => $id_pengguna ?: null,
            'tanggal_posting' => date('Y-m-d H:i:s'),
            'diposting_oleh' => $id_pengguna ?: null,
        ]);

        foreach ($detail as $d) {
            Capsule::table('tb_jurnal_detail')->insert([
                'id_jurnal' => $idJurnalReversal,
                'urutan' => (int) $d->urutan,
                'id_coa' => (int) $d->id_coa,
                'debit' => (float) $d->kredit,
                'kredit' => (float) $d->debit,
                'keterangan_baris' => 'Reversal ' . $jurnal->no_jurnal . ' - ' . (string) ($d->keterangan_baris ?? ''),
            ]);
        }

        Capsule::table('tb_pembatalan_transaksi')
            ->where('id_pembatalan_transaksi', $idPembatalan)
            ->update([
                'id_jurnal_reversal' => $idJurnalReversal,
                'no_jurnal_reversal' => $noJurnalReversal,
                'tanggal_diubah' => date('Y-m-d H:i:s'),
                'diubah_oleh' => $id_pengguna ?: null,
            ]);
    });

    set_flash('success', 'Transaksi posted berhasil dibatalkan dengan jurnal reversal.');
        header('Location: ' . admin_url('index.php?menu=keuangan/pembatalan-transaksi&mode=sudah_batal'));
        exit;
} catch (Throwable $e) {
    set_flash('error', 'Pembatalan gagal: ' . $e->getMessage());
    redirect_admin('keuangan/pembatalan-transaksi');
}