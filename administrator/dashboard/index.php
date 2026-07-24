<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

$user_login = $user ?? user_login();
$id_entitas = (int) ($user_login['id_entitas'] ?? 0);
$nama_pengguna = (string) ($user_login['nama_lengkap'] ?? $user_login['nama_pengguna'] ?? $user_login['username'] ?? 'Pengguna');
$nama_role = (string) ($user_login['nama_role'] ?? $user_login['role'] ?? '-');

$hari_ini = date('Y-m-d');
$awal_bulan = date('Y-m-01');
$akhir_bulan = date('Y-m-t');
$tahun_ini = date('Y');

if (!function_exists('dashboard_table_exists')) {
    function dashboard_table_exists(string $table): bool
    {
        try {
            return Capsule::schema()->hasTable($table);
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('dashboard_col_exists')) {
    function dashboard_col_exists(string $table, string $column): bool
    {
        try {
            return Capsule::schema()->hasColumn($table, $column);
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('dashboard_count')) {
    function dashboard_count(string $table, callable $callback = null): int
    {
        try {
            if (!dashboard_table_exists($table)) {
                return 0;
            }

            $q = Capsule::table($table);

            if ($callback) {
                $callback($q);
            }

            return (int) $q->count();
        } catch (Throwable $e) {
            return 0;
        }
    }
}

if (!function_exists('dashboard_sum')) {
    function dashboard_sum(string $table, string $column, callable $callback = null): float
    {
        try {
            if (!dashboard_table_exists($table) || !dashboard_col_exists($table, $column)) {
                return 0.0;
            }

            $q = Capsule::table($table);

            if ($callback) {
                $callback($q);
            }

            return (float) $q->sum($column);
        } catch (Throwable $e) {
            return 0.0;
        }
    }
}

if (!function_exists('dashboard_rows')) {
    function dashboard_rows(string $table, callable $callback = null, int $limit = 5)
    {
        try {
            if (!dashboard_table_exists($table)) {
                return collect();
            }

            $q = Capsule::table($table);

            if ($callback) {
                $callback($q);
            }

            return $q->limit($limit)->get();
        } catch (Throwable $e) {
            return collect();
        }
    }
}

if (!function_exists('dashboard_uang')) {
    function dashboard_uang($value): string
    {
        return 'Rp ' . number_format((float) $value, 2, ',', '.');
    }
}

if (!function_exists('dashboard_angka')) {
    function dashboard_angka($value): string
    {
        return number_format((float) $value, 0, ',', '.');
    }
}

if (!function_exists('dashboard_qty')) {
    function dashboard_qty($value): string
    {
        $number = (float) $value;

        if (abs($number - round($number)) < 0.000001) {
            return number_format($number, 0, ',', '.');
        }

        return rtrim(rtrim(number_format($number, 3, ',', '.'), '0'), ',');
    }
}

if (!function_exists('dashboard_tanggal')) {
    function dashboard_tanggal($tanggal): string
    {
        if (empty($tanggal)) {
            return '-';
        }

        return date('d/m/Y', strtotime((string) $tanggal));
    }
}

if (!function_exists('dashboard_badge_status')) {
    function dashboard_badge_status($status): string
    {
        $status = strtolower(trim((string) $status));

        return match ($status) {
            'posted', 'selesai', 'aktif', 'terkonfirmasi' => '<span class="badge text-bg-success">' . esc(ucfirst($status)) . '</span>',
            'draft' => '<span class="badge text-bg-secondary">Draft</span>',
            'diproses', 'berjalan' => '<span class="badge text-bg-warning">Diproses</span>',
            'batal', 'nonaktif' => '<span class="badge text-bg-danger">' . esc(ucfirst($status)) . '</span>',
            default => '<span class="badge text-bg-light">' . esc($status !== '' ? ucfirst($status) : '-') . '</span>',
        };
    }
}

if (!function_exists('dashboard_monthly_sum')) {
    function dashboard_monthly_sum(string $table, string $dateColumn, string $sumColumn, int $id_entitas, int $year, callable $callback = null): array
    {
        $data = array_fill(1, 12, 0.0);

        try {
            if (!dashboard_table_exists($table) || !dashboard_col_exists($table, $dateColumn) || !dashboard_col_exists($table, $sumColumn)) {
                return $data;
            }

            $q = Capsule::table($table)
                ->selectRaw('MONTH(' . $dateColumn . ') as bulan')
                ->selectRaw('COALESCE(SUM(' . $sumColumn . '), 0) as total')
                ->where('id_entitas', $id_entitas)
                ->whereYear($dateColumn, $year)
                ->groupByRaw('MONTH(' . $dateColumn . ')');

            if ($callback) {
                $callback($q);
            }

            foreach ($q->get() as $row) {
                $bulan = (int) ($row->bulan ?? 0);
                if ($bulan >= 1 && $bulan <= 12) {
                    $data[$bulan] = (float) ($row->total ?? 0);
                }
            }
        } catch (Throwable $e) {
            return $data;
        }

        return $data;
    }
}

if (!function_exists('dashboard_chart_svg')) {
    function dashboard_chart_svg(string $id, array $labels, array $series, string $height = '250px'): string
    {
        $max = 0.0;

        foreach ($series as $s) {
            foreach ($s['data'] as $value) {
                $max = max($max, (float) $value);
            }
        }

        if ($max <= 0) {
            $max = 1;
        }

        ob_start();
        ?>
        <div class="dashboard-chart" id="<?= esc($id) ?>" style="height:<?= esc($height) ?>">
            <div class="chart-grid">
                <?php foreach ($labels as $idx => $label): ?>
                    <div class="chart-col">
                        <div class="chart-bars">
                            <?php foreach ($series as $s): ?>
                                <?php
                                $value = (float) ($s['data'][$idx] ?? 0);
                                $percent = max(2, min(100, ($value / $max) * 100));
                                ?>
                                <div
                                    class="chart-bar <?= esc($s['class']) ?>"
                                    style="height: <?= esc((string) $percent) ?>%;"
                                    title="<?= esc($s['label'] . ': ' . dashboard_uang($value)) ?>">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="chart-label"><?= esc($label) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="chart-legend mt-3">
            <?php foreach ($series as $s): ?>
                <span><i class="<?= esc($s['class']) ?>"></i><?= esc($s['label']) ?></span>
            <?php endforeach; ?>
        </div>
        <?php
        return (string) ob_get_clean();
    }
}

$bulan_labels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

$total_penjualan_bulan = dashboard_sum('tb_faktur_penjualan', 'total', function ($q) use ($id_entitas, $awal_bulan, $akhir_bulan) {
    $q->where('id_entitas', $id_entitas)
        ->where('status_faktur', 'posted')
        ->whereBetween('tanggal_faktur', [$awal_bulan, $akhir_bulan]);
});

$total_pembelian_bulan = dashboard_sum('tb_faktur_pembelian', 'total', function ($q) use ($id_entitas, $awal_bulan, $akhir_bulan) {
    $q->where('id_entitas', $id_entitas)
        ->where('status_faktur', 'posted')
        ->whereBetween('tanggal_faktur', [$awal_bulan, $akhir_bulan]);
});

$total_biaya_produksi_bulan = dashboard_sum('tb_biaya_produksi', 'jumlah_biaya', function ($q) use ($id_entitas, $awal_bulan, $akhir_bulan) {
    $q->where('id_entitas', $id_entitas)
        ->where('status_posting', 'posted')
        ->whereBetween('tanggal_biaya', [$awal_bulan, $akhir_bulan]);
});

$total_piutang = dashboard_sum('tb_faktur_penjualan', 'sisa_piutang', function ($q) use ($id_entitas) {
    $q->where('id_entitas', $id_entitas)
        ->where('status_faktur', 'posted')
        ->where('sisa_piutang', '>', 0);
});

$total_utang = dashboard_sum('tb_faktur_pembelian', 'sisa_utang', function ($q) use ($id_entitas) {
    $q->where('id_entitas', $id_entitas)
        ->where('status_faktur', 'posted')
        ->where('sisa_utang', '>', 0);
});

$pesanan_baru = dashboard_count('tb_pesanan_penjualan', function ($q) use ($id_entitas) {
    $q->where('id_entitas', $id_entitas)
        ->whereIn('status_pesanan', ['draft', 'terkonfirmasi']);
});

$produksi_berjalan = dashboard_count('tb_perintah_produksi', function ($q) use ($id_entitas) {
    $q->where('id_entitas', $id_entitas)
        ->where('status_produksi', 'posted');
});

$jurnal_draft = dashboard_count('tb_jurnal', function ($q) use ($id_entitas) {
    $q->where('id_entitas', $id_entitas)
        ->where('status_jurnal', 'draft');
});

$item_stok_minimum = 0;

try {
    if (dashboard_table_exists('tb_saldo_stok')) {
        $produk_min = dashboard_table_exists('tb_produk')
            ? Capsule::table('tb_saldo_stok as ss')
                ->join('tb_produk as p', 'p.id_produk', '=', 'ss.id_referensi_barang')
                ->where('ss.id_entitas', $id_entitas)
                ->where('ss.jenis_barang', 'produk')
                ->whereRaw('ss.qty_saldo <= p.stok_minimum')
                ->count()
            : 0;

        $bahan_min = dashboard_table_exists('tb_bahan_baku')
            ? Capsule::table('tb_saldo_stok as ss')
                ->join('tb_bahan_baku as b', 'b.id_bahan_baku', '=', 'ss.id_referensi_barang')
                ->where('ss.id_entitas', $id_entitas)
                ->where('ss.jenis_barang', 'bahan_baku')
                ->whereRaw('ss.qty_saldo <= b.stok_minimum')
                ->count()
            : 0;

        $item_stok_minimum = (int) $produk_min + (int) $bahan_min;
    }
} catch (Throwable $e) {
    $item_stok_minimum = 0;
}

$penjualan_chart = array_values(dashboard_monthly_sum('tb_faktur_penjualan', 'tanggal_faktur', 'total', $id_entitas, (int) $tahun_ini, function ($q) {
    $q->where('status_faktur', 'posted');
}));

$pembelian_chart = array_values(dashboard_monthly_sum('tb_faktur_pembelian', 'tanggal_faktur', 'total', $id_entitas, (int) $tahun_ini, function ($q) {
    $q->where('status_faktur', 'posted');
}));

$produksi_chart = array_values(dashboard_monthly_sum('tb_biaya_produksi', 'tanggal_biaya', 'jumlah_biaya', $id_entitas, (int) $tahun_ini, function ($q) {
    $q->where('status_posting', 'posted');
}));

$latest_penjualan = dashboard_rows('tb_faktur_penjualan as f', function ($q) use ($id_entitas) {
    $q->leftJoin('tb_pelanggan as p', 'p.id_pelanggan', '=', 'f.id_pelanggan')
        ->where('f.id_entitas', $id_entitas)
        ->select([
            'f.id_faktur_penjualan',
            'f.no_faktur_penjualan',
            'f.tanggal_faktur',
            'f.status_faktur',
            'f.total',
            'f.sisa_piutang',
            'p.nama_pelanggan',
        ])
        ->orderBy('f.tanggal_faktur', 'desc')
        ->orderBy('f.id_faktur_penjualan', 'desc');
}, 5);

$latest_pembelian = dashboard_rows('tb_faktur_pembelian as f', function ($q) use ($id_entitas) {
    $q->leftJoin('tb_pemasok as p', 'p.id_pemasok', '=', 'f.id_pemasok')
        ->where('f.id_entitas', $id_entitas)
        ->select([
            'f.id_faktur_pembelian',
            'f.no_faktur_pembelian',
            'f.tanggal_faktur',
            'f.status_faktur',
            'f.total',
            'f.sisa_utang',
            'p.nama_pemasok',
        ])
        ->orderBy('f.tanggal_faktur', 'desc')
        ->orderBy('f.id_faktur_pembelian', 'desc');
}, 5);

$latest_produksi = dashboard_rows('tb_perintah_produksi as pp', function ($q) use ($id_entitas) {
    $q->leftJoin('tb_produk as pr', 'pr.id_produk', '=', 'pp.id_produk')
        ->where('pp.id_entitas', $id_entitas)
        ->select([
            'pp.id_perintah_produksi',
            'pp.no_perintah_produksi',
            'pp.tanggal_perintah',
            'pp.qty_rencana',
            'pp.status_produksi',
            'pr.nama_produk',
        ])
        ->orderBy('pp.tanggal_perintah', 'desc')
        ->orderBy('pp.id_perintah_produksi', 'desc');
}, 5);

$latest_stok = dashboard_rows('tb_saldo_stok as ss', function ($q) use ($id_entitas) {
    $q->leftJoin('tb_gudang as g', 'g.id_gudang', '=', 'ss.id_gudang')
        ->where('ss.id_entitas', $id_entitas)
        ->select([
            'ss.id_saldo_stok',
            'ss.jenis_barang',
            'ss.id_referensi_barang',
            'ss.qty_saldo',
            'ss.hpp_rata_rata',
            'g.nama_gudang',
        ])
        ->orderBy('ss.tanggal_diubah', 'desc')
        ->orderBy('ss.id_saldo_stok', 'desc');
}, 5);

$latest_jurnal = dashboard_rows('tb_jurnal', function ($q) use ($id_entitas) {
    $q->where('id_entitas', $id_entitas)
        ->select([
            'id_jurnal',
            'no_jurnal',
            'tanggal_jurnal',
            'kode_jenis_transaksi',
            'status_jurnal',
            'total_debit',
            'total_kredit',
        ])
        ->orderBy('tanggal_jurnal', 'desc')
        ->orderBy('id_jurnal', 'desc');
}, 5);

$stok_nama_map = [];

try {
    if ($latest_stok->count() > 0) {
        $produk_ids = [];
        $bahan_ids = [];

        foreach ($latest_stok as $s) {
            if ((string) $s->jenis_barang === 'produk') {
                $produk_ids[] = (int) $s->id_referensi_barang;
            } elseif ((string) $s->jenis_barang === 'bahan_baku') {
                $bahan_ids[] = (int) $s->id_referensi_barang;
            }
        }

        if (!empty($produk_ids) && dashboard_table_exists('tb_produk')) {
            $produk_rows = Capsule::table('tb_produk')
                ->whereIn('id_produk', array_unique($produk_ids))
                ->get();

            foreach ($produk_rows as $p) {
                $stok_nama_map['produk_' . (int) $p->id_produk] = (string) $p->nama_produk;
            }
        }

        if (!empty($bahan_ids) && dashboard_table_exists('tb_bahan_baku')) {
            $bahan_rows = Capsule::table('tb_bahan_baku')
                ->whereIn('id_bahan_baku', array_unique($bahan_ids))
                ->get();

            foreach ($bahan_rows as $b) {
                $stok_nama_map['bahan_baku_' . (int) $b->id_bahan_baku] = (string) $b->nama_bahan_baku;
            }
        }
    }
} catch (Throwable $e) {
    $stok_nama_map = [];
}

$top_produk = collect();

try {
    if (dashboard_table_exists('tb_faktur_penjualan_detail') && dashboard_table_exists('tb_faktur_penjualan')) {
        $top_produk = Capsule::table('tb_faktur_penjualan_detail as d')
            ->join('tb_faktur_penjualan as f', 'f.id_faktur_penjualan', '=', 'd.id_faktur_penjualan')
            ->leftJoin('tb_produk as p', 'p.id_produk', '=', 'd.id_produk')
            ->where('f.id_entitas', $id_entitas)
            ->where('f.status_faktur', 'posted')
            ->whereBetween('f.tanggal_faktur', [$awal_bulan, $akhir_bulan])
            ->select([
                'p.nama_produk',
            ])
            ->selectRaw('COALESCE(SUM(d.qty), 0) as total_qty')
            ->selectRaw('COALESCE(SUM(d.subtotal), 0) as total_nilai')
            ->groupBy('d.id_produk', 'p.nama_produk')
            ->orderByRaw('SUM(d.qty) DESC')
            ->limit(5)
            ->get();
    }
} catch (Throwable $e) {
    $top_produk = collect();
}

$stok_ringkas_produk = dashboard_sum('tb_saldo_stok', 'nilai_saldo', function ($q) use ($id_entitas) {
    $q->where('id_entitas', $id_entitas)
        ->where('jenis_barang', 'produk');
});

$stok_ringkas_bahan = dashboard_sum('tb_saldo_stok', 'nilai_saldo', function ($q) use ($id_entitas) {
    $q->where('id_entitas', $id_entitas)
        ->where('jenis_barang', 'bahan_baku');
});

if ($stok_ringkas_produk <= 0) {
    $stok_ringkas_produk = dashboard_sum('tb_saldo_stok', 'hpp_rata_rata', function ($q) use ($id_entitas) {
        $q->where('id_entitas', $id_entitas)
            ->where('jenis_barang', 'produk');
    });
}

if ($stok_ringkas_bahan <= 0) {
    $stok_ringkas_bahan = dashboard_sum('tb_saldo_stok', 'hpp_rata_rata', function ($q) use ($id_entitas) {
        $q->where('id_entitas', $id_entitas)
            ->where('jenis_barang', 'bahan_baku');
    });
}
?>

<style>
    .dashboard-hero {
        border: 0;
        border-radius: 24px;
        color: #fff;
        background:
            radial-gradient(circle at top left, rgba(255, 255, 255, .25), transparent 30%),
            linear-gradient(135deg, #2563eb 0%, #7c3aed 58%, #f97316 130%);
        overflow: hidden;
        position: relative;
    }

    .dashboard-hero::after {
        content: "";
        position: absolute;
        right: -90px;
        top: -90px;
        width: 240px;
        height: 240px;
        border-radius: 50%;
        background: rgba(255, 255, 255, .14);
    }

    .dashboard-card {
        border: 0;
        border-radius: 20px;
        box-shadow: 0 12px 34px rgba(15, 23, 42, .08);
    }

    .dashboard-stat {
        border: 0;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 12px 34px rgba(15, 23, 42, .08);
    }

    .stat-icon {
        width: 46px;
        height: 46px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 16px;
        color: #fff;
        font-size: 22px;
    }

    .bg-blue-gradient {
        background: linear-gradient(135deg, #2563eb, #38bdf8);
    }

    .bg-purple-gradient {
        background: linear-gradient(135deg, #7c3aed, #c084fc);
    }

    .bg-orange-gradient {
        background: linear-gradient(135deg, #f97316, #fbbf24);
    }

    .bg-dark-gradient {
        background: linear-gradient(135deg, #111827, #475569);
    }

    .dashboard-chart {
        width: 100%;
        border-radius: 18px;
        background:
            linear-gradient(to bottom, rgba(37, 99, 235, .06), rgba(124, 58, 237, .04)),
            repeating-linear-gradient(to bottom, transparent 0, transparent 47px, rgba(15, 23, 42, .06) 48px);
        padding: 16px 14px 34px;
        position: relative;
    }

    .chart-grid {
        height: 100%;
        display: grid;
        grid-template-columns: repeat(12, 1fr);
        gap: 10px;
        align-items: end;
    }

    .chart-col {
        height: 100%;
        min-width: 0;
        display: flex;
        flex-direction: column;
        justify-content: end;
        align-items: center;
        gap: 6px;
    }

    .chart-bars {
        height: calc(100% - 25px);
        width: 100%;
        display: flex;
        align-items: end;
        justify-content: center;
        gap: 3px;
    }

    .chart-bar {
        width: 8px;
        min-height: 3px;
        border-radius: 999px 999px 3px 3px;
        transition: .2s;
    }

    .chart-bar:hover {
        transform: translateY(-4px);
        opacity: .86;
    }

    .chart-bar.blue {
        background: linear-gradient(180deg, #38bdf8, #2563eb);
    }

    .chart-bar.purple {
        background: linear-gradient(180deg, #c084fc, #7c3aed);
    }

    .chart-bar.orange {
        background: linear-gradient(180deg, #fbbf24, #f97316);
    }

    .chart-label {
        font-size: 11px;
        color: #64748b;
        white-space: nowrap;
    }

    .chart-legend {
        display: flex;
        gap: 14px;
        flex-wrap: wrap;
        font-size: 12px;
        color: #64748b;
    }

    .chart-legend span {
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .chart-legend i {
        width: 12px;
        height: 12px;
        border-radius: 999px;
        display: inline-block;
    }

    .chart-legend i.blue {
        background: #2563eb;
    }

    .chart-legend i.purple {
        background: #7c3aed;
    }

    .chart-legend i.orange {
        background: #f97316;
    }

    .module-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        gap: 12px;
        margin-bottom: 14px;
    }

    .module-title {
        margin: 0;
        font-size: 17px;
        font-weight: 700;
    }

    .module-subtitle {
        color: #64748b;
        font-size: 12px;
    }

    .table-dashboard th {
        font-size: 12px;
        color: #64748b;
        white-space: nowrap;
    }

    .table-dashboard td {
        font-size: 13px;
        vertical-align: middle;
    }

    .soft-badge {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 5px 10px;
        font-size: 12px;
        background: #f1f5f9;
        color: #475569;
    }

    @media (max-width: 768px) {
        .chart-grid {
            gap: 5px;
        }

        .chart-bar {
            width: 5px;
        }

        .chart-label {
            font-size: 9px;
        }
    }
</style>


<style>
    /* HPP dashboard unified theme */
    :root {
        --hpp-blue: #2563eb;
        --hpp-purple: #7c3aed;
        --hpp-orange: #f97316;
        --hpp-slate: #0f172a;
        --hpp-muted: #64748b;
        --hpp-soft: #f8fafc;
        --hpp-border: #e5e7eb;
        --hpp-shadow: 0 14px 34px rgba(15, 23, 42, .08);
        --hpp-shadow-soft: 0 8px 22px rgba(15, 23, 42, .06);
        --hpp-radius: 22px;
    }

    .page-header {
        position: relative;
        overflow: hidden;
        border-radius: 26px;
        padding: 24px 26px;
        margin-bottom: 24px !important;
        color: #fff;
        background:
            radial-gradient(circle at 8% 0%, rgba(255,255,255,.30), transparent 28%),
            radial-gradient(circle at 92% 12%, rgba(249,115,22,.38), transparent 30%),
            linear-gradient(135deg, var(--hpp-blue) 0%, var(--hpp-purple) 58%, var(--hpp-orange) 135%);
        box-shadow: 0 18px 44px rgba(37, 99, 235, .20);
    }

    .page-header::after {
        content: "";
        position: absolute;
        width: 240px;
        height: 240px;
        right: -95px;
        top: -110px;
        border-radius: 999px;
        background: rgba(255,255,255,.14);
    }

    .page-header > * { position: relative; z-index: 1; }
    .page-title { margin: 0 0 6px; color: #fff; font-weight: 800; letter-spacing: -.03em; }
    .page-subtitle { margin: 0; color: rgba(255,255,255,.82); }

    .dashboard-hero,
    .keu-hero {
        border: 0 !important;
        border-radius: 26px !important;
        color: #fff !important;
        background:
            radial-gradient(circle at 8% 0%, rgba(255,255,255,.30), transparent 28%),
            radial-gradient(circle at 92% 12%, rgba(249,115,22,.38), transparent 30%),
            linear-gradient(135deg, var(--hpp-blue) 0%, var(--hpp-purple) 58%, var(--hpp-orange) 135%) !important;
        box-shadow: 0 18px 44px rgba(37, 99, 235, .20) !important;
        overflow: hidden;
    }

    .card {
        border: 0 !important;
        border-radius: var(--hpp-radius) !important;
        box-shadow: var(--hpp-shadow-soft) !important;
    }

    .dashboard-card,
    .dashboard-stat,
    .keu-card,
    .card.border-0.shadow-sm {
        border: 0 !important;
        border-radius: var(--hpp-radius) !important;
        box-shadow: var(--hpp-shadow) !important;
        transition: transform .18s ease, box-shadow .18s ease;
    }

    .dashboard-card:hover,
    .dashboard-stat:hover,
    .keu-card:hover,
    .card.border-0.shadow-sm:hover {
        transform: translateY(-2px);
        box-shadow: 0 18px 40px rgba(15, 23, 42, .10) !important;
    }

    .card .card-body { padding: 1.2rem; }

    .h3, .h4, .h5, .fw-bold, .fw-semibold { letter-spacing: -.02em; }
    .text-muted { color: var(--hpp-muted) !important; }

    .stat-icon,
    .keu-icon,
    .dashboard-gudang-card-icon,
    .rounded-circle[class*="bg-"] {
        width: 46px !important;
        height: 46px !important;
        border-radius: 16px !important;
        box-shadow: inset 0 1px 0 rgba(255,255,255,.28), 0 10px 18px rgba(15,23,42,.10);
    }

    .bg-blue-gradient, .bg-keu-blue { background: linear-gradient(135deg, #2563eb, #38bdf8) !important; }
    .bg-purple-gradient, .bg-keu-purple { background: linear-gradient(135deg, #7c3aed, #c084fc) !important; }
    .bg-orange-gradient, .bg-keu-orange { background: linear-gradient(135deg, #f97316, #fbbf24) !important; }
    .bg-dark-gradient, .bg-keu-dark { background: linear-gradient(135deg, #111827, #64748b) !important; }

    .bg-primary-subtle, .bg-info-subtle, .bg-success-subtle, .bg-warning-subtle, .bg-danger-subtle {
        background: linear-gradient(135deg, rgba(37,99,235,.12), rgba(124,58,237,.08)) !important;
        color: var(--hpp-blue) !important;
    }

    .table-responsive {
        border-radius: 18px !important;
        border-color: var(--hpp-border) !important;
        background: #fff;
    }

    .table { --bs-table-hover-bg: rgba(37, 99, 235, .035); }
    .table thead th {
        background: #f8fafc !important;
        color: #475569 !important;
        border-bottom: 1px solid var(--hpp-border) !important;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .04em;
        white-space: nowrap;
    }

    .table tbody td { border-color: #eef2f7 !important; }
    .table tfoot th, .table tfoot td { background: #f8fafc !important; }

    .badge { border-radius: 999px; padding: .42em .72em; font-weight: 700; }
    .btn { border-radius: 12px; font-weight: 700; }
    .btn-primary {
        background: linear-gradient(135deg, var(--hpp-blue), var(--hpp-purple)) !important;
        border: 0 !important;
        box-shadow: 0 10px 20px rgba(37,99,235,.18);
    }

    .btn-outline-primary {
        border-color: rgba(37,99,235,.35) !important;
        color: var(--hpp-blue) !important;
        background: #fff !important;
    }

    .btn-outline-primary:hover {
        background: linear-gradient(135deg, var(--hpp-blue), var(--hpp-purple)) !important;
        color: #fff !important;
    }

    .dashboard-chart,
    .keu-chart {
        border-radius: 22px !important;
        border: 1px solid #e5e7eb;
        background:
            linear-gradient(to bottom, rgba(37,99,235,.06), rgba(249,115,22,.035)),
            repeating-linear-gradient(to bottom, transparent 0, transparent 46px, rgba(15,23,42,.055) 47px) !important;
    }

    .chart-bar.blue, .keu-bar.masuk { background: linear-gradient(180deg, #38bdf8, #2563eb) !important; }
    .chart-bar.purple { background: linear-gradient(180deg, #c084fc, #7c3aed) !important; }
    .chart-bar.orange, .keu-bar.keluar { background: linear-gradient(180deg, #fbbf24, #f97316) !important; }

    .list-group-item { border-color: #eef2f7 !important; }
    a.card { color: inherit; }

    @media (max-width: 768px) {
        .page-header { padding: 20px; border-radius: 22px; }
        .card .card-body { padding: 1rem; }
    }
</style>

<div class="card dashboard-hero mb-4">
    <div class="card-body p-4 p-lg-5 position-relative">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <div class="soft-badge bg-white bg-opacity-25 text-white mb-3">
                    Dashboard Utama · <?= esc(date('d/m/Y')) ?>
                </div>
                <h1 class="display-6 fw-bold mb-2">Halo, <?= esc($nama_pengguna) ?></h1>
                <p class="mb-0 opacity-75">
                    Ringkasan aktivitas penjualan, pembelian, produksi, gudang, dan keuangan pada entitas aktif.
                </p>
            </div>

            <div class="col-lg-4 text-lg-end">
                <div class="fs-6 opacity-75">Role Login</div>
                <div class="fs-4 fw-bold"><?= esc($nama_role) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="card dashboard-stat h-100">
            <div class="card-body d-flex gap-3 align-items-center">
                <div class="stat-icon bg-blue-gradient">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <div>
                    <div class="text-muted small">Penjualan Bulan Ini</div>
                    <div class="h5 mb-0"><?= dashboard_uang($total_penjualan_bulan) ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card dashboard-stat h-100">
            <div class="card-body d-flex gap-3 align-items-center">
                <div class="stat-icon bg-purple-gradient">
                    <i class="bi bi-basket"></i>
                </div>
                <div>
                    <div class="text-muted small">Pembelian Bulan Ini</div>
                    <div class="h5 mb-0"><?= dashboard_uang($total_pembelian_bulan) ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card dashboard-stat h-100">
            <div class="card-body d-flex gap-3 align-items-center">
                <div class="stat-icon bg-orange-gradient">
                    <i class="bi bi-gear-wide-connected"></i>
                </div>
                <div>
                    <div class="text-muted small">Biaya Produksi Bulan Ini</div>
                    <div class="h5 mb-0"><?= dashboard_uang($total_biaya_produksi_bulan) ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card dashboard-stat h-100">
            <div class="card-body d-flex gap-3 align-items-center">
                <div class="stat-icon bg-dark-gradient">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <div>
                    <div class="text-muted small">Item Stok Minimum</div>
                    <div class="h5 mb-0"><?= dashboard_angka($item_stok_minimum) ?> Item</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="card dashboard-card h-100">
            <div class="card-body">
                <div class="text-muted small">Pesanan Perlu Diproses</div>
                <div class="h4 mb-1"><?= dashboard_angka($pesanan_baru) ?></div>
                <a href="<?= esc(admin_page_url('penjualan/pesanan')) ?>" class="small text-decoration-none">Selengkapnya</a>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card dashboard-card h-100">
            <div class="card-body">
                <div class="text-muted small">Produksi Berjalan</div>
                <div class="h4 mb-1"><?= dashboard_angka($produksi_berjalan) ?></div>
                <a href="<?= esc(admin_page_url('produksi/perintah')) ?>" class="small text-decoration-none">Selengkapnya</a>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card dashboard-card h-100">
            <div class="card-body">
                <div class="text-muted small">Piutang Belum Lunas</div>
                <div class="h4 mb-1"><?= dashboard_uang($total_piutang) ?></div>
                <a href="<?= esc(admin_page_url('penjualan/faktur')) ?>" class="small text-decoration-none">Selengkapnya</a>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card dashboard-card h-100">
            <div class="card-body">
                <div class="text-muted small">Utang Belum Lunas</div>
                <div class="h4 mb-1"><?= dashboard_uang($total_utang) ?></div>
                <a href="<?= esc(admin_page_url('pembelian/faktur')) ?>" class="small text-decoration-none">Selengkapnya</a>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-8">
        <div class="card dashboard-card h-100">
            <div class="card-body">
                <div class="module-header">
                    <div>
                        <h2 class="module-title">Grafik Transaksi Tahun <?= esc($tahun_ini) ?></h2>
                        <div class="module-subtitle">Penjualan, pembelian, dan biaya produksi per bulan.</div>
                    </div>
                </div>

                <?= dashboard_chart_svg('chart-transaksi', $bulan_labels, [
                    [
                        'label' => 'Penjualan',
                        'class' => 'blue',
                        'data' => $penjualan_chart,
                    ],
                    [
                        'label' => 'Pembelian',
                        'class' => 'purple',
                        'data' => $pembelian_chart,
                    ],
                    [
                        'label' => 'Produksi',
                        'class' => 'orange',
                        'data' => $produksi_chart,
                    ],
                ]) ?>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card dashboard-card h-100">
            <div class="card-body">
                <div class="module-header">
                    <div>
                        <h2 class="module-title">Komposisi Persediaan</h2>
                        <div class="module-subtitle">Ringkasan nilai stok bahan dan produk.</div>
                    </div>
                    <a href="<?= esc(admin_page_url('persediaan/saldo-stok')) ?>" class="btn btn-sm btn-outline-primary">Selengkapnya</a>
                </div>

                <?php
                $total_stok_nilai = max(1, $stok_ringkas_produk + $stok_ringkas_bahan);
                $persen_produk = min(100, max(0, ($stok_ringkas_produk / $total_stok_nilai) * 100));
                $persen_bahan = min(100, max(0, ($stok_ringkas_bahan / $total_stok_nilai) * 100));
                ?>

                <div class="mb-4">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="fw-semibold">Produk Jadi</span>
                        <span><?= dashboard_uang($stok_ringkas_produk) ?></span>
                    </div>
                    <div class="progress" style="height:12px;">
                        <div class="progress-bar bg-blue-gradient" style="width: <?= esc((string) $persen_produk) ?>%"></div>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="fw-semibold">Bahan Baku</span>
                        <span><?= dashboard_uang($stok_ringkas_bahan) ?></span>
                    </div>
                    <div class="progress" style="height:12px;">
                        <div class="progress-bar bg-orange-gradient" style="width: <?= esc((string) $persen_bahan) ?>%"></div>
                    </div>
                </div>

                <div class="alert alert-light border mb-0">
                    <div class="text-muted small">Jurnal Draft</div>
                    <div class="h5 mb-1"><?= dashboard_angka($jurnal_draft) ?> Jurnal</div>
                    <a href="<?= esc(admin_page_url('keuangan/jurnal')) ?>" class="text-decoration-none small">Periksa jurnal</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-6">
        <div class="card dashboard-card h-100">
            <div class="card-body">
                <div class="module-header">
                    <div>
                        <h2 class="module-title">Penjualan Terbaru</h2>
                        <div class="module-subtitle">Maksimal 5 faktur terakhir.</div>
                    </div>
                    <a href="<?= esc(admin_page_url('penjualan/faktur')) ?>" class="btn btn-sm btn-outline-primary">Selengkapnya</a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-dashboard align-middle mb-0">
                        <thead>
                            <tr>
                                <th>No Faktur</th>
                                <th>Pelanggan</th>
                                <th>Tanggal</th>
                                <th class="text-end">Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($latest_penjualan->count() === 0): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Belum ada faktur penjualan.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($latest_penjualan as $row): ?>
                                    <tr>
                                        <td class="fw-semibold"><?= esc((string) ($row->no_faktur_penjualan ?? '-')) ?></td>
                                        <td><?= esc((string) ($row->nama_pelanggan ?? '-')) ?></td>
                                        <td><?= esc(dashboard_tanggal($row->tanggal_faktur ?? null)) ?></td>
                                        <td class="text-end"><?= dashboard_uang($row->total ?? 0) ?></td>
                                        <td><?= dashboard_badge_status($row->status_faktur ?? '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card dashboard-card h-100">
            <div class="card-body">
                <div class="module-header">
                    <div>
                        <h2 class="module-title">Pembelian Terbaru</h2>
                        <div class="module-subtitle">Maksimal 5 faktur terakhir.</div>
                    </div>
                    <a href="<?= esc(admin_page_url('pembelian/faktur')) ?>" class="btn btn-sm btn-outline-primary">Selengkapnya</a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-dashboard align-middle mb-0">
                        <thead>
                            <tr>
                                <th>No Faktur</th>
                                <th>Pemasok</th>
                                <th>Tanggal</th>
                                <th class="text-end">Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($latest_pembelian->count() === 0): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Belum ada faktur pembelian.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($latest_pembelian as $row): ?>
                                    <tr>
                                        <td class="fw-semibold"><?= esc((string) ($row->no_faktur_pembelian ?? '-')) ?></td>
                                        <td><?= esc((string) ($row->nama_pemasok ?? '-')) ?></td>
                                        <td><?= esc(dashboard_tanggal($row->tanggal_faktur ?? null)) ?></td>
                                        <td class="text-end"><?= dashboard_uang($row->total ?? 0) ?></td>
                                        <td><?= dashboard_badge_status($row->status_faktur ?? '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card dashboard-card h-100">
            <div class="card-body">
                <div class="module-header">
                    <div>
                        <h2 class="module-title">Produksi Terbaru</h2>
                        <div class="module-subtitle">Maksimal 5 perintah produksi terakhir.</div>
                    </div>
                    <a href="<?= esc(admin_page_url('produksi/perintah')) ?>" class="btn btn-sm btn-outline-primary">Selengkapnya</a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-dashboard align-middle mb-0">
                        <thead>
                            <tr>
                                <th>No Produksi</th>
                                <th>Produk</th>
                                <th>Tanggal</th>
                                <th class="text-end">Qty</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($latest_produksi->count() === 0): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Belum ada perintah produksi.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($latest_produksi as $row): ?>
                                    <tr>
                                        <td class="fw-semibold"><?= esc((string) ($row->no_perintah_produksi ?? '-')) ?></td>
                                        <td><?= esc((string) ($row->nama_produk ?? '-')) ?></td>
                                        <td><?= esc(dashboard_tanggal($row->tanggal_perintah ?? null)) ?></td>
                                        <td class="text-end"><?= esc(dashboard_qty($row->qty_rencana ?? 0)) ?></td>
                                        <td><?= dashboard_badge_status($row->status_produksi ?? '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card dashboard-card h-100">
            <div class="card-body">
                <div class="module-header">
                    <div>
                        <h2 class="module-title">Saldo Stok Terbaru</h2>
                        <div class="module-subtitle">Maksimal 5 saldo stok terakhir.</div>
                    </div>
                    <a href="<?= esc(admin_page_url('persediaan/saldo-stok')) ?>" class="btn btn-sm btn-outline-primary">Selengkapnya</a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-dashboard align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Barang</th>
                                <th>Jenis</th>
                                <th>Gudang</th>
                                <th class="text-end">Saldo</th>
                                <th class="text-end">HPP Rata-rata</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($latest_stok->count() === 0): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Belum ada saldo stok.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($latest_stok as $row): ?>
                                    <?php
                                    $jenis = (string) ($row->jenis_barang ?? '-');
                                    $key = $jenis . '_' . (int) ($row->id_referensi_barang ?? 0);
                                    ?>
                                    <tr>
                                        <td class="fw-semibold"><?= esc($stok_nama_map[$key] ?? '-') ?></td>
                                        <td><?= esc(str_replace('_', ' ', ucfirst($jenis))) ?></td>
                                        <td><?= esc((string) ($row->nama_gudang ?? '-')) ?></td>
                                        <td class="text-end"><?= esc(dashboard_qty($row->qty_saldo ?? 0)) ?></td>
                                        <td class="text-end"><?= dashboard_uang($row->hpp_rata_rata ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card dashboard-card h-100">
            <div class="card-body">
                <div class="module-header">
                    <div>
                        <h2 class="module-title">Jurnal Terbaru</h2>
                        <div class="module-subtitle">Maksimal 5 jurnal terakhir.</div>
                    </div>
                    <a href="<?= esc(admin_page_url('keuangan/jurnal')) ?>" class="btn btn-sm btn-outline-primary">Selengkapnya</a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-dashboard align-middle mb-0">
                        <thead>
                            <tr>
                                <th>No Jurnal</th>
                                <th>Tanggal</th>
                                <th>Jenis</th>
                                <th class="text-end">Debit</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($latest_jurnal->count() === 0): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Belum ada jurnal.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($latest_jurnal as $row): ?>
                                    <tr>
                                        <td class="fw-semibold"><?= esc((string) ($row->no_jurnal ?? '-')) ?></td>
                                        <td><?= esc(dashboard_tanggal($row->tanggal_jurnal ?? null)) ?></td>
                                        <td><?= esc((string) ($row->kode_jenis_transaksi ?? '-')) ?></td>
                                        <td class="text-end"><?= dashboard_uang($row->total_debit ?? 0) ?></td>
                                        <td><?= dashboard_badge_status($row->status_jurnal ?? '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card dashboard-card h-100">
            <div class="card-body">
                <div class="module-header">
                    <div>
                        <h2 class="module-title">Top Produk Bulan Ini</h2>
                        <div class="module-subtitle">Maksimal 5 produk terjual.</div>
                    </div>
                    <a href="<?= esc(admin_page_url('penjualan/faktur')) ?>" class="btn btn-sm btn-outline-primary">Selengkapnya</a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-dashboard align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Nilai</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($top_produk->count() === 0): ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">Belum ada penjualan produk bulan ini.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($top_produk as $row): ?>
                                    <tr>
                                        <td class="fw-semibold"><?= esc((string) ($row->nama_produk ?? '-')) ?></td>
                                        <td class="text-end"><?= esc(dashboard_qty($row->total_qty ?? 0)) ?></td>
                                        <td class="text-end"><?= dashboard_uang($row->total_nilai ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>