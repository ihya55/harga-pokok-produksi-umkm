<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

function reset_data_is_super_admin(array $user_login): bool
{
    $username = strtolower(trim((string) ($user_login['username'] ?? '')));
    $nama_role_session = strtolower(trim((string) ($user_login['nama_role'] ?? $user_login['role'] ?? '')));

    if ($username === 'super_admin' || $nama_role_session === 'super_admin' || $nama_role_session === 'admin_entitas') {
        return true;
    }

    $id_role = (int) ($user_login['id_role'] ?? 0);
    if ($id_role > 0) {
        $role = Capsule::table('tb_role')->where('id_role', $id_role)->first();
        if ($role) {
            $nama_role = strtolower(trim((string) ($role->nama_role ?? '')));
            $kode_role = strtolower(trim((string) ($role->kode_role ?? '')));
            return in_array($nama_role, ['super_admin', 'admin_entitas'], true) || in_array($kode_role, ['super_admin', 'admin_entitas'], true);
        }
    }

    return false;
}

function reset_data_groups(): array
{
    return [
        'transaksi_stok_jurnal' => [
            'label' => 'Transaksi, stok, mutasi, dan jurnal',
            'tables' => [
                'tb_pembatalan_transaksi',
                'tb_log_jurnal_sumber',
                'tb_jurnal_detail',
                'tb_jurnal',
                'tb_kas_masuk',
                'tb_kas_keluar',
                'tb_saldo_stok',
                'tb_mutasi_stok',
                'tb_konversi_produk',
                'tb_saldo_awal_stok_detail',
                'tb_saldo_awal_stok',
                'tb_stok_opname_detail',
                'tb_stok_opname',
                'tb_pembayaran_pembelian',
                'tb_faktur_pembelian_detail',
                'tb_faktur_pembelian',
                'tb_penerimaan_pembelian_detail',
                'tb_penerimaan_pembelian',
                'tb_pesanan_pembelian_detail',
                'tb_pesanan_pembelian',
                'tb_pembayaran_penjualan',
                'tb_faktur_penjualan_detail',
                'tb_faktur_penjualan',
                'tb_penyerahan_penjualan_detail',
                'tb_penyerahan_penjualan',
                'tb_pesanan_penjualan_detail',
                'tb_pesanan_penjualan',
                'tb_biaya_produksi_detail',
                'tb_biaya_produksi',
                'tb_hasil_produksi',
                'tb_pengambilan_bahan_detail',
                'tb_pengambilan_bahan',
                'tb_perintah_produksi',
            ],
        ],
        'produk_bahan_resep' => [
            'label' => 'Produk, bahan baku, kategori, dan resep',
            'tables' => [
                'tb_resep_detail',
                'tb_resep',
                'tb_produk',
                'tb_bahan_baku',
                'tb_kategori_produk',
            ],
        ],
        'pelanggan' => [
            'label' => 'Pelanggan',
            'tables' => ['tb_pelanggan'],
        ],
        'pemasok' => [
            'label' => 'Pemasok',
            'tables' => ['tb_pemasok'],
        ],
        'gudang' => [
            'label' => 'Gudang',
            'tables' => ['tb_gudang'],
        ],
        'satuan' => [
            'label' => 'Satuan',
            'tables' => ['tb_satuan'],
        ],
        'pajak' => [
            'label' => 'Pajak / PPN',
            'tables' => ['tb_pajak'],
        ],
        'akun_template_jurnal' => [
            'label' => 'Akun COA, mapping, periode, dan template jurnal',
            'tables' => [
                'tb_konfigurasi_akun',
                'tb_mapping_akun',
                'tb_mapping_laporan_detail',
                'tb_mapping_laporan',
                'tb_template_jurnal_detail',
                'tb_template_jurnal',
                'tb_periode_akuntansi',
                'tb_coa',
            ],
        ],
    ];
}

$user_login = user_login();
if (!reset_data_is_super_admin($user_login)) {
    set_flash('error', 'Reset data percobaan hanya boleh dilakukan oleh super admin/admin utama.');
    redirect_admin('dashboard');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_admin('master_setup/reset_data');
}

$konfirmasi = trim((string) ($_POST['konfirmasi'] ?? ''));
$alasan = trim((string) ($_POST['alasan'] ?? ''));
$setuju = (int) ($_POST['setuju'] ?? 0);
$selectedGroups = $_POST['reset_group'] ?? [];
if (!is_array($selectedGroups)) {
    $selectedGroups = [];
}
$selectedGroups = array_values(array_unique(array_map('strval', $selectedGroups)));

if ($konfirmasi !== 'RESET DATA' || $alasan === '' || $setuju !== 1) {
    set_flash('error', 'Reset dibatalkan. Konfirmasi, alasan, dan persetujuan wajib diisi dengan benar.');
    redirect_admin('master_setup/reset_data');
}

$groups = reset_data_groups();
$validSelectedGroups = array_values(array_intersect($selectedGroups, array_keys($groups)));

if (empty($validSelectedGroups)) {
    set_flash('error', 'Pilih minimal satu kelompok data yang ingin dihapus.');
    redirect_admin('master_setup/reset_data');
}

// Jika user menghapus master, transaksi/stok ikut dihapus dulu agar data tidak yatim/tidak nyambung.
if (!in_array('transaksi_stok_jurnal', $validSelectedGroups, true)) {
    $validSelectedGroups = array_merge(['transaksi_stok_jurnal'], $validSelectedGroups);
}

$tables = [];
$selectedLabels = [];
foreach ($validSelectedGroups as $groupKey) {
    $selectedLabels[] = $groups[$groupKey]['label'];
    foreach ($groups[$groupKey]['tables'] as $table) {
        $tables[] = $table;
    }
}
$tables = array_values(array_unique($tables));

$totalDeleted = 0;
$detailDeleted = [];
$connection = Capsule::connection();

try {
    $connection->statement('SET FOREIGN_KEY_CHECKS=0');

    foreach ($tables as $table) {
        if (!Capsule::schema()->hasTable($table)) {
            $detailDeleted[$table] = 'tabel tidak ditemukan';
            continue;
        }

        $count = (int) Capsule::table($table)->count();
        if ($count > 0) {
            Capsule::table($table)->delete();
            $totalDeleted += $count;
        }
        $detailDeleted[$table] = $count;
    }

    $connection->statement('SET FOREIGN_KEY_CHECKS=1');

    foreach ($tables as $table) {
        if (!Capsule::schema()->hasTable($table)) {
            continue;
        }

        try {
            $connection->statement('ALTER TABLE `' . str_replace('`', '``', $table) . '` AUTO_INCREMENT = 1');
        } catch (Throwable $e) {
            // Abaikan jika tabel tidak memiliki auto increment.
        }
    }

    try {
        Capsule::table('tb_reset_data_log')->insert([
            'id_pengguna' => (int) ($user_login['id_pengguna'] ?? 0) ?: null,
            'username' => (string) ($user_login['username'] ?? '-'),
            'tanggal_reset' => date('Y-m-d H:i:s'),
            'total_baris_dihapus' => $totalDeleted,
            'detail_tabel' => json_encode([
                'kelompok_dihapus' => $selectedLabels,
                'detail_tabel' => $detailDeleted,
            ], JSON_UNESCAPED_UNICODE),
            'alasan' => $alasan,
        ]);
    } catch (Throwable $e) {
        // Jika tabel log belum dibuat, reset tetap berjalan. Jalankan SQL update agar log tersedia.
    }

    set_flash('success', 'Reset data berhasil. Kelompok dihapus: ' . implode(', ', $selectedLabels) . '. Total baris dihapus: ' . number_format($totalDeleted, 0, ',', '.') . '.');
    redirect_admin('master_setup/reset_data');
} catch (Throwable $e) {
    try {
        $connection->statement('SET FOREIGN_KEY_CHECKS=1');
    } catch (Throwable $ignored) {}

    set_flash('error', 'Reset data gagal: ' . $e->getMessage());
    redirect_admin('master_setup/reset_data');
}
