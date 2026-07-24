<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';
require_once __DIR__ . '/../_keuangan_helper.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

$aksi_jurnal = $aksi_jurnal ?? 'simpan';

$id_entitas = keu_id_entitas();
$id_pengguna = keu_id_pengguna();

if (!function_exists('jurnal_clean_nominal')) {
    function jurnal_clean_nominal($value): float
    {
        return (float) str_replace(['.', ','], ['', '.'], (string) $value);
    }
}

if (!function_exists('jurnal_validate_and_collect_details')) {
    function jurnal_validate_and_collect_details(array $inputDetails, int $id_entitas): array
    {
        $details = [];
        $totalDebit = 0.0;
        $totalKredit = 0.0;
        $urutan = 1;

        foreach ($inputDetails as $row) {
            $id_coa = (int) ($row['id_coa'] ?? 0);
            $debit = jurnal_clean_nominal($row['debit'] ?? 0);
            $kredit = jurnal_clean_nominal($row['kredit'] ?? 0);
            $keterangan_baris = trim((string) ($row['keterangan_baris'] ?? ''));

            if ($id_coa <= 0 && $debit <= 0 && $kredit <= 0) {
                continue;
            }

            if ($id_coa <= 0) {
                throw new RuntimeException('Semua baris yang memiliki nominal wajib memilih akun.');
            }

            if ($debit > 0 && $kredit > 0) {
                throw new RuntimeException('Satu baris jurnal tidak boleh memiliki debit dan kredit sekaligus.');
            }

            if ($debit <= 0 && $kredit <= 0) {
                throw new RuntimeException('Setiap baris jurnal wajib memiliki nominal debit atau kredit.');
            }

            $akunAda = Capsule::table('tb_coa')
                ->where('id_entitas', $id_entitas)
                ->where('id_coa', $id_coa)
                ->where('boleh_transaksi', 1)
                ->where('status_aktif', 1)
                ->exists();

            if (!$akunAda) {
                throw new RuntimeException('Akun COA tidak valid atau tidak boleh transaksi.');
            }

            $details[] = [
                'urutan' => $urutan++,
                'id_coa' => $id_coa,
                'debit' => $debit,
                'kredit' => $kredit,
                'keterangan_baris' => $keterangan_baris !== '' ? $keterangan_baris : null,
            ];

            $totalDebit += $debit;
            $totalKredit += $kredit;
        }

        if (count($details) < 2) {
            throw new RuntimeException('Minimal harus ada 2 baris jurnal.');
        }

        if ($totalDebit <= 0 || $totalKredit <= 0) {
            throw new RuntimeException('Total debit dan kredit harus lebih dari 0.');
        }

        if (abs($totalDebit - $totalKredit) >= 1) {
            throw new RuntimeException('Total debit dan kredit harus sama.');
        }

        return [
            'details' => $details,
            'total_debit' => $totalDebit,
            'total_kredit' => $totalKredit,
        ];
    }
}

try {
    if ($aksi_jurnal === 'simpan' || $aksi_jurnal === 'update') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect_admin('keuangan/jurnal');
        }

        $id_jurnal = (int) ($_POST['id_jurnal'] ?? 0);
        $tanggal_jurnal = keu_tanggal_mysql($_POST['tanggal_jurnal'] ?? null, date('Y-m-d'));
        $kode_jenis_transaksi = strtoupper(trim((string) ($_POST['kode_jenis_transaksi'] ?? 'JURNAL_MANUAL')));
        $keterangan = trim((string) ($_POST['keterangan'] ?? ''));
        $inputDetails = (array) ($_POST['detail'] ?? []);

        if (!in_array($kode_jenis_transaksi, ['JURNAL_MANUAL', 'SALDO_AWAL_COA'], true)) {
            throw new RuntimeException('Jenis jurnal tidak valid.');
        }

        /*
        |--------------------------------------------------------------------------
        | tb_jurnal.id_periode di database kamu NOT NULL.
        | Jadi draft sekalipun harus punya periode.
        |--------------------------------------------------------------------------
        */
        $periode = keu_periode_terbuka($id_entitas, $tanggal_jurnal);

        $result = jurnal_validate_and_collect_details($inputDetails, $id_entitas);
        $details = $result['details'];
        $totalDebit = (float) $result['total_debit'];
        $totalKredit = (float) $result['total_kredit'];

        if ($aksi_jurnal === 'simpan') {
            Capsule::connection()->transaction(function () use (
                $id_entitas,
                $id_pengguna,
                $tanggal_jurnal,
                $periode,
                $kode_jenis_transaksi,
                $keterangan,
                $details,
                $totalDebit,
                $totalKredit
            ) {
                $noJurnal = keu_generate_no_jurnal($id_entitas);

                $idJurnal = Capsule::table('tb_jurnal')->insertGetId([
                    'id_entitas' => $id_entitas,
                    'no_jurnal' => $noJurnal,
                    'tanggal_jurnal' => $tanggal_jurnal,
                    'id_periode' => (int) $periode->id_periode,
                    'kode_jenis_transaksi' => $kode_jenis_transaksi,
                    'keterangan' => $keterangan !== ''
                        ? $keterangan
                        : ($kode_jenis_transaksi === 'SALDO_AWAL_COA' ? 'Saldo awal akun COA' : 'Jurnal manual'),
                    'tabel_sumber' => 'tb_jurnal',
                    'id_sumber' => 0,
                    'no_sumber' => $noJurnal,
                    'status_jurnal' => 'draft',
                    'total_debit' => $totalDebit,
                    'total_kredit' => $totalKredit,
                    'tanggal_dibuat' => date('Y-m-d H:i:s'),
                    'dibuat_oleh' => $id_pengguna ?: null,
                ]);

                Capsule::table('tb_jurnal')
                    ->where('id_jurnal', $idJurnal)
                    ->where('id_entitas', $id_entitas)
                    ->update([
                        'id_sumber' => $idJurnal,
                    ]);

                foreach ($details as $d) {
                    $d['id_jurnal'] = $idJurnal;
                    Capsule::table('tb_jurnal_detail')->insert($d);
                }
            });

            set_flash('success', 'Jurnal berhasil disimpan sebagai draft.');
            redirect_admin('keuangan/jurnal');
        }

        $jurnal = Capsule::table('tb_jurnal')
            ->where('id_entitas', $id_entitas)
            ->where('id_jurnal', $id_jurnal)
            ->first();

        if (!$jurnal) {
            throw new RuntimeException('Jurnal tidak ditemukan.');
        }

        if ((string) $jurnal->status_jurnal === 'posted') {
            throw new RuntimeException('Jurnal sudah posted dan tidak bisa diedit.');
        }

        if (!in_array((string) $jurnal->kode_jenis_transaksi, ['JURNAL_MANUAL', 'SALDO_AWAL_COA'], true)) {
            throw new RuntimeException('Jurnal dari transaksi sumber tidak boleh diedit manual.');
        }

        Capsule::connection()->transaction(function () use (
            $id_entitas,
            $id_pengguna,
            $id_jurnal,
            $tanggal_jurnal,
            $periode,
            $kode_jenis_transaksi,
            $keterangan,
            $details,
            $totalDebit,
            $totalKredit
        ) {
            Capsule::table('tb_jurnal')
                ->where('id_jurnal', $id_jurnal)
                ->where('id_entitas', $id_entitas)
                ->update([
                    'tanggal_jurnal' => $tanggal_jurnal,
                    'id_periode' => (int) $periode->id_periode,
                    'kode_jenis_transaksi' => $kode_jenis_transaksi,
                    'keterangan' => $keterangan !== ''
                        ? $keterangan
                        : ($kode_jenis_transaksi === 'SALDO_AWAL_COA' ? 'Saldo awal akun COA' : 'Jurnal manual'),
                    'total_debit' => $totalDebit,
                    'total_kredit' => $totalKredit,
                    'tanggal_diubah' => date('Y-m-d H:i:s'),
                    'diubah_oleh' => $id_pengguna ?: null,
                ]);

            Capsule::table('tb_jurnal_detail')
                ->where('id_jurnal', $id_jurnal)
                ->delete();

            foreach ($details as $d) {
                $d['id_jurnal'] = $id_jurnal;
                Capsule::table('tb_jurnal_detail')->insert($d);
            }
        });

        set_flash('success', 'Jurnal berhasil diperbarui.');
            header('Location: ' . admin_url('index.php?menu=keuangan/jurnal/detail&id=' . (int) $id_jurnal));
            exit;
    }

    if ($aksi_jurnal === 'posting') {
        $id_jurnal = (int) ($_GET['id'] ?? 0);

        $jurnal = Capsule::table('tb_jurnal')
            ->where('id_entitas', $id_entitas)
            ->where('id_jurnal', $id_jurnal)
            ->first();

        if (!$jurnal) {
            throw new RuntimeException('Jurnal tidak ditemukan.');
        }

        if ((string) $jurnal->status_jurnal === 'posted') {
            throw new RuntimeException('Jurnal sudah posted.');
        }

        if (!in_array((string) $jurnal->kode_jenis_transaksi, ['JURNAL_MANUAL', 'SALDO_AWAL_COA'], true)) {
            throw new RuntimeException('Jurnal dari transaksi sumber tidak boleh diposting manual dari menu ini.');
        }

        $detailCount = Capsule::table('tb_jurnal_detail')
            ->where('id_jurnal', $id_jurnal)
            ->count();

        if ($detailCount < 2) {
            throw new RuntimeException('Detail jurnal tidak lengkap.');
        }

        $sum = Capsule::table('tb_jurnal_detail')
            ->where('id_jurnal', $id_jurnal)
            ->selectRaw('COALESCE(SUM(debit),0) as total_debit, COALESCE(SUM(kredit),0) as total_kredit')
            ->first();

        $totalDebit = (float) ($sum->total_debit ?? 0);
        $totalKredit = (float) ($sum->total_kredit ?? 0);

        if ($totalDebit <= 0 || $totalKredit <= 0 || abs($totalDebit - $totalKredit) >= 1) {
            throw new RuntimeException('Jurnal belum balance. Total debit dan kredit harus sama.');
        }

        $periode = keu_periode_terbuka($id_entitas, (string) $jurnal->tanggal_jurnal);

        Capsule::table('tb_jurnal')
            ->where('id_entitas', $id_entitas)
            ->where('id_jurnal', $id_jurnal)
            ->update([
                'id_periode' => (int) $periode->id_periode,
                'status_jurnal' => 'posted',
                'total_debit' => $totalDebit,
                'total_kredit' => $totalKredit,
                'tanggal_posting' => date('Y-m-d H:i:s'),
                'diposting_oleh' => $id_pengguna ?: null,
                'tanggal_diubah' => date('Y-m-d H:i:s'),
                'diubah_oleh' => $id_pengguna ?: null,
            ]);

        set_flash('success', 'Jurnal berhasil diposting.');
        redirect_admin('keuangan/jurnal/detail&id=' . $id_jurnal);
    }

    if ($aksi_jurnal === 'hapus') {
        $id_jurnal = (int) ($_GET['id'] ?? 0);

        $jurnal = Capsule::table('tb_jurnal')
            ->where('id_entitas', $id_entitas)
            ->where('id_jurnal', $id_jurnal)
            ->first();

        if (!$jurnal) {
            throw new RuntimeException('Jurnal tidak ditemukan.');
        }

        if ((string) $jurnal->status_jurnal === 'posted') {
            throw new RuntimeException('Jurnal sudah posted dan tidak bisa dihapus.');
        }

        if (!in_array((string) $jurnal->kode_jenis_transaksi, ['JURNAL_MANUAL', 'SALDO_AWAL_COA'], true)) {
            throw new RuntimeException('Jurnal dari transaksi sumber tidak boleh dihapus manual.');
        }

        Capsule::connection()->transaction(function () use ($id_entitas, $id_jurnal) {
            Capsule::table('tb_jurnal_detail')
                ->where('id_jurnal', $id_jurnal)
                ->delete();

            Capsule::table('tb_jurnal')
                ->where('id_entitas', $id_entitas)
                ->where('id_jurnal', $id_jurnal)
                ->delete();
        });

        set_flash('success', 'Jurnal berhasil diposting.');
            header('Location: ' . admin_url('index.php?menu=keuangan/jurnal/detail&id=' . (int) $id_jurnal));
            exit;
    }
} catch (Throwable $e) {
    set_flash('error', 'Proses gagal: ' . $e->getMessage());

    if (!empty($id_jurnal ?? 0)) {
        header('Location: ' . admin_url('index.php?menu=keuangan/jurnal/detail&id=' . (int) $id_jurnal));
        exit;
    }

    redirect_admin('keuangan/jurnal');
}

redirect_admin('keuangan/jurnal');