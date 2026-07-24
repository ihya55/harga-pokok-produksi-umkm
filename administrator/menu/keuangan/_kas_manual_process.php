<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../helpers/config.php';
require_once __DIR__ . '/../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../helpers/auth.php';
require_once __DIR__ . '/_keuangan_helper.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

$mode_kas = $mode_kas ?? 'masuk';
$aksi = $aksi ?? 'simpan';

$is_masuk = $mode_kas === 'masuk';

$table = $is_masuk ? 'tb_kas_masuk' : 'tb_kas_keluar';
$pk = $is_masuk ? 'id_kas_masuk' : 'id_kas_keluar';
$no_col = $is_masuk ? 'no_kas_masuk' : 'no_kas_keluar';
$tgl_col = $is_masuk ? 'tanggal_kas_masuk' : 'tanggal_kas_keluar';
$jenis_col = $is_masuk ? 'sumber_kas_masuk' : 'jenis_kas_keluar';
$prefix = $is_masuk ? 'KSM-' : 'KSK-';
$menu_base = $is_masuk ? 'keuangan/kas-masuk' : 'keuangan/kas-keluar';
$tabel_sumber = $is_masuk ? 'tb_kas_masuk' : 'tb_kas_keluar';
$kode_jurnal_default = $is_masuk ? 'KAS_MASUK' : 'KAS_KELUAR';
$kode_jurnal = $kode_jurnal_default;

$id_entitas = keu_id_entitas();
$id_pengguna = keu_id_pengguna();

function kas_clean_nominal($value): float
{
    return (float) str_replace(['.', ','], ['', '.'], (string) $value);
}

try {
    if ($aksi === 'simpan' || $aksi === 'update') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect_admin($menu_base);
        }

        $id = (int) ($_POST['id'] ?? 0);
        $tanggal = keu_tanggal_mysql($_POST['tanggal'] ?? null, date('Y-m-d'));
        $jenis = trim((string) ($_POST['jenis'] ?? ''));
        $id_coa_kas_bank = (int) ($_POST['id_coa_kas_bank'] ?? 0);
        $id_coa_beban = (int) ($_POST['id_coa_beban'] ?? 0);
        $jumlah = kas_clean_nominal($_POST['jumlah'] ?? 0);
        $keterangan = trim((string) ($_POST['keterangan'] ?? ''));

        if ($tanggal === '' || $jenis === '' || $id_coa_kas_bank <= 0 || $jumlah <= 0) {
            set_flash('error', 'Tanggal, jenis/sumber, akun kas/bank, dan jumlah wajib diisi.');
            redirect_admin($aksi === 'update' ? $menu_base . '/edit&id=' . $id : $menu_base . '/tambah');
        }

        if (!$is_masuk && $id_coa_beban <= 0) {
            set_flash('error', 'Akun beban wajib dipilih.');
            redirect_admin($aksi === 'update' ? $menu_base . '/edit&id=' . $id : $menu_base . '/tambah');
        }

        $cekKas = Capsule::table('tb_mapping_akun')
            ->where('id_entitas', $id_entitas)
            ->where('kode_jenis_transaksi', 'ARUS_KAS')
            ->where('peran_akun', 'akun_kas_bank')
            ->where('id_coa', $id_coa_kas_bank)
            ->where('status_aktif', 1)
            ->exists();

        if (!$cekKas) {
            set_flash('error', 'Akun kas/bank tidak valid. Pastikan akun sudah dimapping di tb_mapping_akun ARUS_KAS.');
            redirect_admin($aksi === 'update' ? $menu_base . '/edit&id=' . $id : $menu_base . '/tambah');
        }

        if ($aksi === 'simpan') {
            $nomor = keu_generate_nomor($table, $no_col, $id_entitas, $prefix);

            $dataInsert = [
                'id_entitas' => $id_entitas,
                $no_col => $nomor,
                $tgl_col => $tanggal,
                $jenis_col => $jenis,
                'id_coa_kas_bank' => $id_coa_kas_bank,
                'jumlah' => $jumlah,
                'keterangan' => $keterangan !== '' ? $keterangan : null,
                'status_posting' => 'draft',
                'tanggal_dibuat' => date('Y-m-d H:i:s'),
                'dibuat_oleh' => $id_pengguna ?: null,
            ];

            if (!$is_masuk) {
                $dataInsert['id_coa_beban'] = $id_coa_beban;
            }

            Capsule::table($table)->insert($dataInsert);

            set_flash('success', 'Data berhasil disimpan.');
            redirect_admin($menu_base);
        }

        $row = Capsule::table($table)
            ->where('id_entitas', $id_entitas)
            ->where($pk, $id)
            ->first();

        if (!$row) {
            set_flash('error', 'Data tidak ditemukan.');
            redirect_admin($menu_base);
        }

        if ((string) $row->status_posting === 'posted') {
            set_flash('error', 'Data sudah posted dan tidak bisa diedit.');
            redirect_admin($menu_base . '/detail&id=' . $id);
        }

        $dataUpdate = [
            $tgl_col => $tanggal,
            $jenis_col => $jenis,
            'id_coa_kas_bank' => $id_coa_kas_bank,
            'jumlah' => $jumlah,
            'keterangan' => $keterangan !== '' ? $keterangan : null,
            'tanggal_diubah' => date('Y-m-d H:i:s'),
            'diubah_oleh' => $id_pengguna ?: null,
        ];

        if (!$is_masuk) {
            $dataUpdate['id_coa_beban'] = $id_coa_beban;
        }

        Capsule::table($table)
            ->where($pk, $id)
            ->where('id_entitas', $id_entitas)
            ->update($dataUpdate);

        set_flash('success', 'Data berhasil diperbarui.');
        redirect_admin($menu_base . '/detail&id=' . $id);
    }

    if ($aksi === 'hapus') {
        $id = (int) ($_GET['id'] ?? 0);

        $row = Capsule::table($table)
            ->where('id_entitas', $id_entitas)
            ->where($pk, $id)
            ->first();

        if (!$row) {
            set_flash('error', 'Data tidak ditemukan.');
            redirect_admin($menu_base);
        }

        if ((string) $row->status_posting === 'posted') {
            set_flash('error', 'Data sudah posted dan tidak bisa dihapus.');
            redirect_admin($menu_base . '/detail&id=' . $id);
        }

        Capsule::table($table)
            ->where($pk, $id)
            ->where('id_entitas', $id_entitas)
            ->delete();

        set_flash('success', 'Data berhasil dihapus.');
        redirect_admin($menu_base);
    }

    if ($aksi === 'posting') {
        $id = (int) ($_GET['id'] ?? 0);

        $row = Capsule::table($table)
            ->where('id_entitas', $id_entitas)
            ->where($pk, $id)
            ->first();

        if (!$row) {
            set_flash('error', 'Data tidak ditemukan.');
            redirect_admin($menu_base);
        }

        if ((string) $row->status_posting === 'posted') {
            set_flash('error', 'Data sudah posted.');
            redirect_admin($menu_base . '/detail&id=' . $id);
        }

        $tanggal = (string) $row->{$tgl_col};
        $periode = keu_periode_terbuka($id_entitas, $tanggal);
        $jumlah = (float) $row->jumlah;

        $akunKas = (int) $row->id_coa_kas_bank;

        $akunPendapatan = null;
        if ($is_masuk) {
            $akunPendapatan = keu_mapping_akun($kode_jurnal, 'akun_pendapatan_lain', 'global', 0, $id_entitas);

            if (!$akunPendapatan) {
                $akunPendapatan = keu_mapping_akun('KAS_MASUK', 'akun_pendapatan_lain', 'global', 0, $id_entitas);
            }

            if (!$akunPendapatan) {
                throw new RuntimeException('Mapping akun pendapatan lain untuk ' . $kode_jurnal . ' belum tersedia.');
            }
        }

        $kode_jurnal = strtoupper(trim((string) $row->{$jenis_col}));

        if ($kode_jurnal === '') {
            $kode_jurnal = $kode_jurnal_default;
        }

        Capsule::connection()->transaction(function () use (
            $row,
            $id,
            $id_entitas,
            $id_pengguna,
            $is_masuk,
            $table,
            $pk,
            $no_col,
            $tgl_col,
            $jenis_col,
            $tabel_sumber,
            $kode_jurnal,
            $periode,
            $jumlah,
            $akunKas,
            $akunPendapatan
        ) {
            $noJurnal = keu_generate_no_jurnal($id_entitas);

            $idJurnal = Capsule::table('tb_jurnal')->insertGetId([
                'id_entitas' => $id_entitas,
                'no_jurnal' => $noJurnal,
                'tanggal_jurnal' => $row->{$tgl_col},
                'id_periode' => (int) $periode->id_periode,
                'kode_jenis_transaksi' => $kode_jurnal,
                'keterangan' => ($is_masuk ? 'Posting kas masuk ' : 'Posting kas keluar ') . $row->{$no_col},
                'tabel_sumber' => $tabel_sumber,
                'id_sumber' => $id,
                'no_sumber' => $row->{$no_col},
                'status_jurnal' => 'posted',
                'total_debit' => $jumlah,
                'total_kredit' => $jumlah,
                'tanggal_dibuat' => date('Y-m-d H:i:s'),
                'dibuat_oleh' => $id_pengguna ?: null,
                'tanggal_posting' => date('Y-m-d H:i:s'),
                'diposting_oleh' => $id_pengguna ?: null,
            ]);

            if ($is_masuk) {
                Capsule::table('tb_jurnal_detail')->insert([
                    [
                        'id_jurnal' => $idJurnal,
                        'urutan' => 1,
                        'id_coa' => $akunKas,
                        'debit' => $jumlah,
                        'kredit' => 0,
                        'keterangan_baris' => 'Kas/bank masuk - ' . $row->{$no_col},
                    ],
                    [
                        'id_jurnal' => $idJurnal,
                        'urutan' => 2,
                        'id_coa' => (int) $akunPendapatan->id_coa,
                        'debit' => 0,
                        'kredit' => $jumlah,
                        'keterangan_baris' => 'Pendapatan lain - ' . $row->{$no_col},
                    ],
                ]);
            } else {
                Capsule::table('tb_jurnal_detail')->insert([
                    [
                        'id_jurnal' => $idJurnal,
                        'urutan' => 1,
                        'id_coa' => (int) $row->id_coa_beban,
                        'debit' => $jumlah,
                        'kredit' => 0,
                        'keterangan_baris' => 'Beban kas keluar - ' . $row->{$no_col},
                    ],
                    [
                        'id_jurnal' => $idJurnal,
                        'urutan' => 2,
                        'id_coa' => $akunKas,
                        'debit' => 0,
                        'kredit' => $jumlah,
                        'keterangan_baris' => 'Kas/bank keluar - ' . $row->{$no_col},
                    ],
                ]);
            }

            Capsule::table($table)
                ->where($pk, $id)
                ->where('id_entitas', $id_entitas)
                ->update([
                    'status_posting' => 'posted',
                    'tanggal_diubah' => date('Y-m-d H:i:s'),
                    'diubah_oleh' => $id_pengguna ?: null,
                ]);
        });

        set_flash('success', 'Data berhasil diposting ke jurnal.');
        redirect_admin($menu_base . '/detail&id=' . $id);
    }
} catch (Throwable $e) {
    set_flash('error', 'Proses gagal: ' . $e->getMessage());
    redirect_admin($menu_base);
}

redirect_admin($menu_base);