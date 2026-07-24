<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

$id_entitas = (int) ($user['id_entitas'] ?? 0);

function rupiah_dashboard_produksi($value): string
{
    return 'Rp ' . number_format((float) $value, 2, '.', ',');
}

function qty_dashboard_produksi($value): string
{
    return number_format((float) $value, 0, '.', ',');
}

function count_dashboard_produksi(callable $callback): int
{
    try {
        return (int) $callback();
    } catch (Throwable $e) {
        return 0;
    }
}

function sum_dashboard_produksi(callable $callback): float
{
    try {
        return (float) $callback();
    } catch (Throwable $e) {
        return 0.0;
    }
}

function label_status_perintah_dashboard_produksi(?string $status): string
{
    $status = strtolower(trim((string) $status));

    if ($status === 'draft') {
        return 'Draft';
    }

    if ($status === 'posted') {
        return 'Posted';
    }

    return $status === '' ? '-' : ucwords(str_replace('_', ' ', $status));
}

function badge_status_perintah_dashboard_produksi(?string $status): string
{
    $status = strtolower(trim((string) $status));

    if ($status === 'posted') {
        return 'primary';
    }

    if ($status === 'draft') {
        return 'secondary';
    }

    return 'warning';
}

function label_status_hasil_dashboard_produksi(?string $status): string
{
    $status = strtolower(trim((string) $status));

    if ($status === 'draft') {
        return 'Draft';
    }

    if ($status === 'posted') {
        return 'Posted';
    }

    return $status === '' ? '-' : ucwords(str_replace('_', ' ', $status));
}

function badge_status_hasil_dashboard_produksi(?string $status): string
{
    $status = strtolower(trim((string) $status));

    if ($status === 'posted') {
        return 'success';
    }

    if ($status === 'draft') {
        return 'warning';
    }

    return 'secondary';
}

$tanggal_hari_ini = date('Y-m-d');
$tanggal_awal_bulan = date('Y-m-01');
$tanggal_akhir_bulan = date('Y-m-t');

/*
|--------------------------------------------------------------------------
| Perintah Produksi
|--------------------------------------------------------------------------
*/

$total_perintah = count_dashboard_produksi(function () use ($id_entitas) {
    return Capsule::table('tb_perintah_produksi')
        ->where('id_entitas', $id_entitas)
        ->count();
});

$perintah_hari_ini = count_dashboard_produksi(function () use ($id_entitas, $tanggal_hari_ini) {
    return Capsule::table('tb_perintah_produksi')
        ->where('id_entitas', $id_entitas)
        ->whereDate('tanggal_perintah', $tanggal_hari_ini)
        ->count();
});

$perintah_draft = count_dashboard_produksi(function () use ($id_entitas) {
    return Capsule::table('tb_perintah_produksi')
        ->where('id_entitas', $id_entitas)
        ->where('status_produksi', 'draft')
        ->count();
});

/*
 * Produksi berjalan:
 * perintah produksi sudah posted, tetapi belum ada hasil produksi posted.
 * Kalau hasil produksi masih draft, tetap dihitung berjalan karena belum masuk persediaan.
 */
$produksi_berjalan = count_dashboard_produksi(function () use ($id_entitas) {
    return Capsule::table('tb_perintah_produksi as pp')
        ->where('pp.id_entitas', $id_entitas)
        ->where('pp.status_produksi', 'posted')
        ->whereNotExists(function ($sub) {
            $sub->select(Capsule::raw(1))
                ->from('tb_hasil_produksi as hp')
                ->whereColumn('hp.id_perintah_produksi', 'pp.id_perintah_produksi')
                ->where('hp.status_posting', 'posted');
        })
        ->count();
});

/*
 * Produksi selesai:
 * perintah produksi sudah posted dan sudah ada hasil produksi posted.
 */
$produksi_selesai = count_dashboard_produksi(function () use ($id_entitas) {
    return Capsule::table('tb_perintah_produksi as pp')
        ->where('pp.id_entitas', $id_entitas)
        ->where('pp.status_produksi', 'posted')
        ->whereExists(function ($sub) {
            $sub->select(Capsule::raw(1))
                ->from('tb_hasil_produksi as hp')
                ->whereColumn('hp.id_perintah_produksi', 'pp.id_perintah_produksi')
                ->where('hp.status_posting', 'posted');
        })
        ->count();
});

$total_qty_rencana = sum_dashboard_produksi(function () use ($id_entitas, $tanggal_awal_bulan, $tanggal_akhir_bulan) {
    return Capsule::table('tb_perintah_produksi')
        ->where('id_entitas', $id_entitas)
        ->whereBetween('tanggal_perintah', [$tanggal_awal_bulan, $tanggal_akhir_bulan])
        ->sum('qty_rencana');
});

/*
|--------------------------------------------------------------------------
| Pengambilan Bahan
|--------------------------------------------------------------------------
*/

$total_pengambilan = count_dashboard_produksi(function () use ($id_entitas) {
    return Capsule::table('tb_pengambilan_bahan')
        ->where('id_entitas', $id_entitas)
        ->count();
});

$pengambilan_draft = count_dashboard_produksi(function () use ($id_entitas) {
    return Capsule::table('tb_pengambilan_bahan')
        ->where('id_entitas', $id_entitas)
        ->where('status_posting', 'draft')
        ->count();
});

$pengambilan_posted = count_dashboard_produksi(function () use ($id_entitas) {
    return Capsule::table('tb_pengambilan_bahan')
        ->where('id_entitas', $id_entitas)
        ->where('status_posting', 'posted')
        ->count();
});

$total_bahan_terpakai_bulan_ini = sum_dashboard_produksi(function () use ($id_entitas, $tanggal_awal_bulan, $tanggal_akhir_bulan) {
    return Capsule::table('tb_pengambilan_bahan_detail as d')
        ->join('tb_pengambilan_bahan as h', 'h.id_pengambilan_bahan', '=', 'd.id_pengambilan_bahan')
        ->where('h.id_entitas', $id_entitas)
        ->where('h.status_posting', 'posted')
        ->whereBetween('h.tanggal_pengambilan', [$tanggal_awal_bulan, $tanggal_akhir_bulan])
        ->sum('d.subtotal');
});

/*
|--------------------------------------------------------------------------
| Biaya Produksi
|--------------------------------------------------------------------------
*/

$total_biaya = count_dashboard_produksi(function () use ($id_entitas) {
    return Capsule::table('tb_biaya_produksi')
        ->where('id_entitas', $id_entitas)
        ->count();
});

$biaya_draft = count_dashboard_produksi(function () use ($id_entitas) {
    return Capsule::table('tb_biaya_produksi')
        ->where('id_entitas', $id_entitas)
        ->where('status_posting', 'draft')
        ->count();
});

$biaya_posted = count_dashboard_produksi(function () use ($id_entitas) {
    return Capsule::table('tb_biaya_produksi')
        ->where('id_entitas', $id_entitas)
        ->where('status_posting', 'posted')
        ->count();
});

$total_biaya_lain_bulan_ini = sum_dashboard_produksi(function () use ($id_entitas, $tanggal_awal_bulan, $tanggal_akhir_bulan) {
    return Capsule::table('tb_biaya_produksi_detail as d')
        ->join('tb_biaya_produksi as h', 'h.id_biaya_produksi', '=', 'd.id_biaya_produksi')
        ->where('h.id_entitas', $id_entitas)
        ->where('h.status_posting', 'posted')
        ->whereBetween('h.tanggal_biaya', [$tanggal_awal_bulan, $tanggal_akhir_bulan])
        ->sum('d.jumlah_biaya');
});

$biaya_tenaga_kerja_bulan_ini = sum_dashboard_produksi(function () use ($id_entitas, $tanggal_awal_bulan, $tanggal_akhir_bulan) {
    return Capsule::table('tb_biaya_produksi_detail as d')
        ->join('tb_biaya_produksi as h', 'h.id_biaya_produksi', '=', 'd.id_biaya_produksi')
        ->where('h.id_entitas', $id_entitas)
        ->where('h.status_posting', 'posted')
        ->whereBetween('h.tanggal_biaya', [$tanggal_awal_bulan, $tanggal_akhir_bulan])
        ->whereIn('d.jenis_biaya_produksi', ['tenaga_kerja', 'tenaga kerja', 'tkl', 'gaji'])
        ->sum('d.jumlah_biaya');
});

$biaya_bop_bulan_ini = sum_dashboard_produksi(function () use ($id_entitas, $tanggal_awal_bulan, $tanggal_akhir_bulan) {
    return Capsule::table('tb_biaya_produksi_detail as d')
        ->join('tb_biaya_produksi as h', 'h.id_biaya_produksi', '=', 'd.id_biaya_produksi')
        ->where('h.id_entitas', $id_entitas)
        ->where('h.status_posting', 'posted')
        ->whereBetween('h.tanggal_biaya', [$tanggal_awal_bulan, $tanggal_akhir_bulan])
        ->whereNotIn('d.jenis_biaya_produksi', ['tenaga_kerja', 'tenaga kerja', 'tkl', 'gaji'])
        ->sum('d.jumlah_biaya');
});

$total_biaya_produksi_bulan_ini = $total_bahan_terpakai_bulan_ini + $total_biaya_lain_bulan_ini;

/*
|--------------------------------------------------------------------------
| Hasil Produksi
|--------------------------------------------------------------------------
*/

$total_hasil = count_dashboard_produksi(function () use ($id_entitas) {
    return Capsule::table('tb_hasil_produksi')
        ->where('id_entitas', $id_entitas)
        ->count();
});

$hasil_draft = count_dashboard_produksi(function () use ($id_entitas) {
    return Capsule::table('tb_hasil_produksi')
        ->where('id_entitas', $id_entitas)
        ->where('status_posting', 'draft')
        ->count();
});

$hasil_posted = count_dashboard_produksi(function () use ($id_entitas) {
    return Capsule::table('tb_hasil_produksi')
        ->where('id_entitas', $id_entitas)
        ->where('status_posting', 'posted')
        ->count();
});

$batch_selesai_bulan_ini = count_dashboard_produksi(function () use ($id_entitas, $tanggal_awal_bulan, $tanggal_akhir_bulan) {
    return Capsule::table('tb_hasil_produksi')
        ->where('id_entitas', $id_entitas)
        ->where('status_posting', 'posted')
        ->whereBetween('tanggal_hasil', [$tanggal_awal_bulan, $tanggal_akhir_bulan])
        ->count();
});

$total_qty_hasil_bulan_ini = sum_dashboard_produksi(function () use ($id_entitas, $tanggal_awal_bulan, $tanggal_akhir_bulan) {
    return Capsule::table('tb_hasil_produksi')
        ->where('id_entitas', $id_entitas)
        ->where('status_posting', 'posted')
        ->whereBetween('tanggal_hasil', [$tanggal_awal_bulan, $tanggal_akhir_bulan])
        ->sum('qty_hasil');
});

$total_hpp_bulan_ini = sum_dashboard_produksi(function () use ($id_entitas, $tanggal_awal_bulan, $tanggal_akhir_bulan) {
    return Capsule::table('tb_hasil_produksi')
        ->where('id_entitas', $id_entitas)
        ->where('status_posting', 'posted')
        ->whereBetween('tanggal_hasil', [$tanggal_awal_bulan, $tanggal_akhir_bulan])
        ->sum('total_hpp');
});

$hpp_rata_rata = $total_qty_hasil_bulan_ini > 0
    ? $total_hpp_bulan_ini / $total_qty_hasil_bulan_ini
    : 0;

/*
|--------------------------------------------------------------------------
| Produksi Berjalan
|--------------------------------------------------------------------------
| Perintah posted, belum ada hasil produksi posted.
|--------------------------------------------------------------------------
*/

$perintah_berjalan_rows = collect();

try {
    $perintah_berjalan_rows = Capsule::table('tb_perintah_produksi as pp')
        ->leftJoin('tb_produk as p', 'p.id_produk', '=', 'pp.id_produk')
        ->leftJoin('tb_resep as r', 'r.id_resep', '=', 'pp.id_resep')
        ->where('pp.id_entitas', $id_entitas)
        ->where('pp.status_produksi', 'posted')
        ->whereNotExists(function ($sub) {
            $sub->select(Capsule::raw(1))
                ->from('tb_hasil_produksi as hp')
                ->whereColumn('hp.id_perintah_produksi', 'pp.id_perintah_produksi')
                ->where('hp.status_posting', 'posted');
        })
        ->select([
            'pp.id_perintah_produksi',
            'pp.no_perintah_produksi',
            'pp.tanggal_perintah',
            'pp.tanggal_mulai',
            'pp.tanggal_selesai',
            'pp.qty_rencana',
            'pp.qty_hasil',
            'pp.status_produksi',
            'p.kode_produk',
            'p.nama_produk',
            'r.kode_resep',
            'r.nama_resep',
        ])
        ->selectRaw("
            (
                SELECT COUNT(*)
                FROM tb_hasil_produksi hp_draft
                WHERE hp_draft.id_perintah_produksi = pp.id_perintah_produksi
                AND hp_draft.status_posting = 'draft'
            ) as jumlah_hasil_draft
        ")
        ->selectRaw("
            (
                SELECT MAX(hp_draft.tanggal_hasil)
                FROM tb_hasil_produksi hp_draft
                WHERE hp_draft.id_perintah_produksi = pp.id_perintah_produksi
                AND hp_draft.status_posting = 'draft'
            ) as tanggal_hasil_draft_terakhir
        ")
        ->orderBy('pp.tanggal_perintah', 'desc')
        ->orderBy('pp.id_perintah_produksi', 'desc')
        ->limit(8)
        ->get();
} catch (Throwable $e) {
    $perintah_berjalan_rows = collect();
}

/*
|--------------------------------------------------------------------------
| Produksi Selesai
|--------------------------------------------------------------------------
| Perintah posted, sudah ada hasil produksi posted.
|--------------------------------------------------------------------------
*/

$perintah_selesai_rows = collect();

try {
    $perintah_selesai_rows = Capsule::table('tb_perintah_produksi as pp')
        ->leftJoin('tb_produk as p', 'p.id_produk', '=', 'pp.id_produk')
        ->leftJoin('tb_resep as r', 'r.id_resep', '=', 'pp.id_resep')
        ->where('pp.id_entitas', $id_entitas)
        ->where('pp.status_produksi', 'posted')
        ->whereExists(function ($sub) {
            $sub->select(Capsule::raw(1))
                ->from('tb_hasil_produksi as hp')
                ->whereColumn('hp.id_perintah_produksi', 'pp.id_perintah_produksi')
                ->where('hp.status_posting', 'posted');
        })
        ->select([
            'pp.id_perintah_produksi',
            'pp.no_perintah_produksi',
            'pp.tanggal_perintah',
            'pp.qty_rencana',
            'pp.qty_hasil',
            'p.kode_produk',
            'p.nama_produk',
            'r.kode_resep',
            'r.nama_resep',
        ])
        ->selectRaw("
            (
                SELECT MAX(hp.tanggal_hasil)
                FROM tb_hasil_produksi hp
                WHERE hp.id_perintah_produksi = pp.id_perintah_produksi
                AND hp.status_posting = 'posted'
            ) as tanggal_hasil_terakhir
        ")
        ->selectRaw("
            (
                SELECT SUM(hp.qty_hasil)
                FROM tb_hasil_produksi hp
                WHERE hp.id_perintah_produksi = pp.id_perintah_produksi
                AND hp.status_posting = 'posted'
            ) as total_qty_hasil_posted
        ")
        ->selectRaw("
            (
                SELECT SUM(hp.total_hpp)
                FROM tb_hasil_produksi hp
                WHERE hp.id_perintah_produksi = pp.id_perintah_produksi
                AND hp.status_posting = 'posted'
            ) as total_hpp_posted
        ")
        ->orderByDesc('tanggal_hasil_terakhir')
        ->limit(8)
        ->get();
} catch (Throwable $e) {
    $perintah_selesai_rows = collect();
}

/*
|--------------------------------------------------------------------------
| Pengambilan Bahan Draft
|--------------------------------------------------------------------------
*/

$pengambilan_draft_rows = collect();

try {
    $pengambilan_draft_rows = Capsule::table('tb_pengambilan_bahan as pb')
        ->leftJoin('tb_perintah_produksi as pp', 'pp.id_perintah_produksi', '=', 'pb.id_perintah_produksi')
        ->leftJoin('tb_produk as p', 'p.id_produk', '=', 'pp.id_produk')
        ->leftJoin('tb_gudang as g', 'g.id_gudang', '=', 'pb.id_gudang')
        ->where('pb.id_entitas', $id_entitas)
        ->where('pb.status_posting', 'draft')
        ->select([
            'pb.id_pengambilan_bahan',
            'pb.no_pengambilan_bahan',
            'pb.tanggal_pengambilan',
            'pp.no_perintah_produksi',
            'p.kode_produk',
            'p.nama_produk',
            'g.kode_gudang',
            'g.nama_gudang',
        ])
        ->orderBy('pb.tanggal_pengambilan', 'desc')
        ->orderBy('pb.id_pengambilan_bahan', 'desc')
        ->limit(8)
        ->get();
} catch (Throwable $e) {
    $pengambilan_draft_rows = collect();
}

/*
|--------------------------------------------------------------------------
| Biaya Produksi Draft
|--------------------------------------------------------------------------
*/

$biaya_draft_rows = collect();

try {
    $biaya_draft_rows = Capsule::table('tb_biaya_produksi as bp')
        ->leftJoin('tb_perintah_produksi as pp', 'pp.id_perintah_produksi', '=', 'bp.id_perintah_produksi')
        ->leftJoin('tb_produk as p', 'p.id_produk', '=', 'pp.id_produk')
        ->where('bp.id_entitas', $id_entitas)
        ->where('bp.status_posting', 'draft')
        ->select([
            'bp.id_biaya_produksi',
            'bp.no_biaya_produksi',
            'bp.tanggal_biaya',
            'pp.no_perintah_produksi',
            'p.kode_produk',
            'p.nama_produk',
        ])
        ->selectRaw("
            (
                SELECT COALESCE(SUM(bpd.jumlah_biaya), 0)
                FROM tb_biaya_produksi_detail bpd
                WHERE bpd.id_biaya_produksi = bp.id_biaya_produksi
            ) as total_biaya_detail
        ")
        ->selectRaw("
            (
                SELECT COUNT(*)
                FROM tb_biaya_produksi_detail bpd
                WHERE bpd.id_biaya_produksi = bp.id_biaya_produksi
            ) as jumlah_detail
        ")
        ->orderBy('bp.tanggal_biaya', 'desc')
        ->orderBy('bp.id_biaya_produksi', 'desc')
        ->limit(8)
        ->get();
} catch (Throwable $e) {
    $biaya_draft_rows = collect();
}

/*
|--------------------------------------------------------------------------
| Hasil Produksi Terbaru
|--------------------------------------------------------------------------
*/

$hasil_terbaru_rows = collect();

try {
    $hasil_terbaru_rows = Capsule::table('tb_hasil_produksi as hp')
        ->leftJoin('tb_perintah_produksi as pp', 'pp.id_perintah_produksi', '=', 'hp.id_perintah_produksi')
        ->leftJoin('tb_produk as p', 'p.id_produk', '=', 'hp.id_produk')
        ->leftJoin('tb_gudang as g', 'g.id_gudang', '=', 'hp.id_gudang')
        ->where('hp.id_entitas', $id_entitas)
        ->select([
            'hp.id_hasil_produksi',
            'hp.no_hasil_produksi',
            'hp.tanggal_hasil',
            'hp.qty_hasil',
            'hp.total_biaya_bahan',
            'hp.total_biaya_tenaga_kerja',
            'hp.total_biaya_bop',
            'hp.total_hpp',
            'hp.hpp_per_unit',
            'hp.status_posting',
            'pp.no_perintah_produksi',
            'p.kode_produk',
            'p.nama_produk',
            'g.kode_gudang',
            'g.nama_gudang',
        ])
        ->orderBy('hp.tanggal_hasil', 'desc')
        ->orderBy('hp.id_hasil_produksi', 'desc')
        ->limit(8)
        ->get();
} catch (Throwable $e) {
    $hasil_terbaru_rows = collect();
}

/*
|--------------------------------------------------------------------------
| Top Produk Hasil Bulan Ini
|--------------------------------------------------------------------------
*/

$top_produk_hasil_rows = collect();

try {
    $top_produk_hasil_rows = Capsule::table('tb_hasil_produksi as hp')
        ->leftJoin('tb_produk as p', 'p.id_produk', '=', 'hp.id_produk')
        ->where('hp.id_entitas', $id_entitas)
        ->where('hp.status_posting', 'posted')
        ->whereBetween('hp.tanggal_hasil', [$tanggal_awal_bulan, $tanggal_akhir_bulan])
        ->select([
            'hp.id_produk',
            'p.kode_produk',
            'p.nama_produk',
            Capsule::raw('SUM(hp.qty_hasil) as total_qty'),
            Capsule::raw('SUM(hp.total_hpp) as total_hpp'),
            Capsule::raw('CASE WHEN SUM(hp.qty_hasil) > 0 THEN SUM(hp.total_hpp) / SUM(hp.qty_hasil) ELSE 0 END as avg_hpp'),
        ])
        ->groupBy('hp.id_produk', 'p.kode_produk', 'p.nama_produk')
        ->orderByDesc('total_qty')
        ->limit(8)
        ->get();
} catch (Throwable $e) {
    $top_produk_hasil_rows = collect();
}
?>


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

<div class="page-header mb-4">
    <h1 class="page-title">Dashboard Produksi</h1>
    <p class="page-subtitle">
        Ringkasan perintah produksi, pengambilan bahan, biaya produksi, hasil produksi, persediaan barang jadi, dan HPP
    </p>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small mb-1">Produksi Berjalan</div>
                        <div class="h3 mb-0"><?= (int) $produksi_berjalan ?></div>
                    </div>
                    <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width:42px;height:42px;">
                        <i class="bi bi-gear-wide-connected"></i>
                    </div>
                </div>

                <div class="text-muted small mt-3">
                    Perintah posted yang belum punya hasil produksi posted.
                </div>

                <a href="<?= esc(admin_page_url('produksi/perintah')) ?>" class="stretched-link"></a>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small mb-1">Produksi Selesai</div>
                        <div class="h3 mb-0"><?= (int) $produksi_selesai ?></div>
                    </div>
                    <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width:42px;height:42px;">
                        <i class="bi bi-check-circle"></i>
                    </div>
                </div>

                <div class="text-muted small mt-3">
                    Sudah ada hasil produksi posted.
                </div>

                <a href="<?= esc(admin_page_url('produksi/hasil')) ?>" class="stretched-link"></a>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small mb-1">Biaya Produksi Bulan Ini</div>
                        <div class="h5 mb-0 text-danger"><?= esc(rupiah_dashboard_produksi($total_biaya_produksi_bulan_ini)) ?></div>
                    </div>
                    <div class="rounded-circle bg-danger-subtle text-danger d-flex align-items-center justify-content-center" style="width:42px;height:42px;">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                </div>

                <div class="text-muted small mt-3">
                    Bahan: <?= esc(rupiah_dashboard_produksi($total_bahan_terpakai_bulan_ini)) ?><br>
                    TK + BOP: <?= esc(rupiah_dashboard_produksi($total_biaya_lain_bulan_ini)) ?>
                </div>

                <a href="<?= esc(admin_page_url('produksi/biaya')) ?>" class="stretched-link"></a>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small mb-1">HPP Rata-rata Produk</div>
                        <div class="h5 mb-0 text-warning"><?= esc(rupiah_dashboard_produksi($hpp_rata_rata)) ?></div>
                    </div>
                    <div class="rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center" style="width:42px;height:42px;">
                        <i class="bi bi-calculator"></i>
                    </div>
                </div>

                <div class="text-muted small mt-3">
                    Total HPP posted: <?= esc(rupiah_dashboard_produksi($total_hpp_bulan_ini)) ?>
                </div>

                <a href="<?= esc(admin_page_url('produksi/hasil')) ?>" class="stretched-link"></a>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">Total Perintah Produksi</div>
                <div class="h3 mb-0"><?= (int) $total_perintah ?></div>

                <div class="d-flex gap-2 mt-3 flex-wrap">
                    <span class="badge text-bg-secondary">Draft <?= (int) $perintah_draft ?></span>
                    <span class="badge text-bg-primary">Berjalan <?= (int) $produksi_berjalan ?></span>
                    <span class="badge text-bg-success">Selesai <?= (int) $produksi_selesai ?></span>
                </div>

                <div class="text-muted small mt-2">
                    Hari ini: <?= (int) $perintah_hari_ini ?><br>
                    Qty rencana bulan ini: <?= esc(qty_dashboard_produksi($total_qty_rencana)) ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">Pengambilan Bahan</div>
                <div class="h3 mb-0"><?= (int) $total_pengambilan ?></div>

                <div class="d-flex gap-2 mt-3 flex-wrap">
                    <span class="badge text-bg-secondary">Draft <?= (int) $pengambilan_draft ?></span>
                    <span class="badge text-bg-success">Posted <?= (int) $pengambilan_posted ?></span>
                </div>

                <div class="text-muted small mt-2">
                    Nilai bahan bulan ini: <?= esc(rupiah_dashboard_produksi($total_bahan_terpakai_bulan_ini)) ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">Biaya Produksi</div>
                <div class="h3 mb-0"><?= (int) $total_biaya ?></div>

                <div class="d-flex gap-2 mt-3 flex-wrap">
                    <span class="badge text-bg-secondary">Draft <?= (int) $biaya_draft ?></span>
                    <span class="badge text-bg-success">Posted <?= (int) $biaya_posted ?></span>
                </div>

                <div class="text-muted small mt-2">
                    TK: <?= esc(rupiah_dashboard_produksi($biaya_tenaga_kerja_bulan_ini)) ?><br>
                    BOP: <?= esc(rupiah_dashboard_produksi($biaya_bop_bulan_ini)) ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">Hasil Produksi</div>
                <div class="h3 mb-0"><?= (int) $total_hasil ?></div>

                <div class="d-flex gap-2 mt-3 flex-wrap">
                    <span class="badge text-bg-warning">Draft <?= (int) $hasil_draft ?></span>
                    <span class="badge text-bg-success">Posted <?= (int) $hasil_posted ?></span>
                </div>

                <div class="text-muted small mt-2">
                    Qty posted bulan ini: <?= esc(qty_dashboard_produksi($total_qty_hasil_bulan_ini)) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <h2 class="h5 mb-1">Shortcut Modul Produksi</h2>
                <div class="text-muted small">Akses cepat ke alur produksi</div>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="<?= esc(admin_page_url('master_setup/resep')) ?>" class="btn btn-outline-dark">
                <i class="bi bi-diagram-3 me-1"></i>Resep / BOM
            </a>

            <a href="<?= esc(admin_page_url('produksi/perintah')) ?>" class="btn btn-outline-primary">
                <i class="bi bi-list-task me-1"></i>Perintah Produksi
            </a>

            <a href="<?= esc(admin_page_url('produksi/pengambilan-bahan')) ?>" class="btn btn-outline-info">
                <i class="bi bi-box-arrow-up me-1"></i>Pengambilan Bahan
            </a>

            <a href="<?= esc(admin_page_url('produksi/biaya')) ?>" class="btn btn-outline-warning">
                <i class="bi bi-cash-stack me-1"></i>Biaya Produksi
            </a>

            <a href="<?= esc(admin_page_url('produksi/hasil')) ?>" class="btn btn-outline-success">
                <i class="bi bi-check-circle me-1"></i>Hasil Produksi
            </a>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <div>
                        <h2 class="h5 mb-1">Produksi Berjalan</h2>
                        <div class="text-muted small">
                            Perintah produksi posted yang belum punya hasil produksi posted
                        </div>
                    </div>

                    <a href="<?= esc(admin_page_url('produksi/perintah')) ?>" class="btn btn-sm btn-outline-primary">
                        Lihat Semua
                    </a>
                </div>

                <div class="table-responsive border rounded">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>No Perintah</th>
                                <th>Produk</th>
                                <th class="text-end">Qty Rencana</th>
                                <th class="text-end">Qty Hasil</th>
                                <th>Status</th>
                                <th width="120" class="text-center">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if ($perintah_berjalan_rows->count() > 0): ?>
                                <?php foreach ($perintah_berjalan_rows as $row): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?= esc($row->no_perintah_produksi ?? '-') ?></div>
                                            <div class="text-muted small"><?= esc($row->tanggal_perintah ?? '-') ?></div>
                                        </td>

                                        <td>
                                            <div><?= esc(($row->kode_produk ?? '-') . ' - ' . ($row->nama_produk ?? '-')) ?></div>
                                            <div class="text-muted small">
                                                <?= esc(($row->kode_resep ?? '-') . ' - ' . ($row->nama_resep ?? '-')) ?>
                                            </div>
                                        </td>

                                        <td class="text-end"><?= esc(qty_dashboard_produksi($row->qty_rencana ?? 0)) ?></td>
                                        <td class="text-end"><?= esc(qty_dashboard_produksi($row->qty_hasil ?? 0)) ?></td>

                                        <td>
                                            <?php if ((int) ($row->jumlah_hasil_draft ?? 0) > 0): ?>
                                                <span class="badge text-bg-warning">Menunggu Posting Hasil</span>
                                                <div class="small text-muted">
                                                    Draft hasil: <?= (int) ($row->jumlah_hasil_draft ?? 0) ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="badge text-bg-primary">Berjalan</span>
                                            <?php endif; ?>
                                        </td>

                                        <td class="text-center">
                                            <a href="<?= esc(admin_page_url('produksi/perintah/detail') . '&id=' . (int) $row->id_perintah_produksi) ?>" class="btn btn-sm btn-outline-info" title="Detail Perintah">
                                                <i class="bi bi-eye"></i>
                                            </a>

                                            <a href="<?= esc(admin_page_url('produksi/hasil/tambah') . '&id_perintah_produksi=' . (int) $row->id_perintah_produksi) ?>" class="btn btn-sm btn-outline-success" title="Input Hasil">
                                                <i class="bi bi-check-circle"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        Tidak ada produksi berjalan.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <div>
                        <h2 class="h5 mb-1">Hasil Produksi Terbaru</h2>
                        <div class="text-muted small">
                            Draft belum masuk persediaan, posted sudah masuk persediaan
                        </div>
                    </div>

                    <a href="<?= esc(admin_page_url('produksi/hasil')) ?>" class="btn btn-sm btn-outline-success">
                        Lihat Semua
                    </a>
                </div>

                <div class="table-responsive border rounded">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>No Hasil</th>
                                <th>Produk</th>
                                <th class="text-end">Qty</th>
                                <th>Status</th>
                                <th width="90" class="text-center">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if ($hasil_terbaru_rows->count() > 0): ?>
                                <?php foreach ($hasil_terbaru_rows as $row): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?= esc($row->no_hasil_produksi ?? '-') ?></div>
                                            <div class="text-muted small"><?= esc($row->tanggal_hasil ?? '-') ?></div>
                                        </td>

                                        <td>
                                            <div><?= esc(($row->kode_produk ?? '-') . ' - ' . ($row->nama_produk ?? '-')) ?></div>
                                            <div class="text-muted small">
                                                HPP/unit: <?= esc(rupiah_dashboard_produksi($row->hpp_per_unit ?? 0)) ?>
                                            </div>
                                        </td>

                                        <td class="text-end"><?= esc(qty_dashboard_produksi($row->qty_hasil ?? 0)) ?></td>

                                        <td>
                                            <span class="badge text-bg-<?= esc(badge_status_hasil_dashboard_produksi($row->status_posting ?? '')) ?>">
                                                <?= esc(label_status_hasil_dashboard_produksi($row->status_posting ?? '')) ?>
                                            </span>
                                        </td>

                                        <td class="text-center">
                                            <a href="<?= esc(admin_page_url('produksi/hasil/detail') . '&id=' . (int) $row->id_hasil_produksi) ?>" class="btn btn-sm btn-outline-info" title="Detail Hasil">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        Belum ada hasil produksi.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-xl-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <div>
                        <h2 class="h5 mb-1">Produksi Selesai Terbaru</h2>
                        <div class="text-muted small">
                            Perintah produksi yang sudah punya hasil produksi posted
                        </div>
                    </div>

                    <a href="<?= esc(admin_page_url('produksi/hasil')) ?>" class="btn btn-sm btn-outline-success">
                        Lihat Semua
                    </a>
                </div>

                <div class="table-responsive border rounded">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>No Perintah</th>
                                <th>Produk</th>
                                <th class="text-end">Qty Rencana</th>
                                <th class="text-end">Qty Hasil Posted</th>
                                <th class="text-end">Total HPP</th>
                                <th width="90" class="text-center">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if ($perintah_selesai_rows->count() > 0): ?>
                                <?php foreach ($perintah_selesai_rows as $row): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?= esc($row->no_perintah_produksi ?? '-') ?></div>
                                            <div class="text-muted small">
                                                Selesai: <?= esc($row->tanggal_hasil_terakhir ?? '-') ?>
                                            </div>
                                        </td>

                                        <td>
                                            <div><?= esc(($row->kode_produk ?? '-') . ' - ' . ($row->nama_produk ?? '-')) ?></div>
                                            <div class="text-muted small">
                                                <?= esc(($row->kode_resep ?? '-') . ' - ' . ($row->nama_resep ?? '-')) ?>
                                            </div>
                                        </td>

                                        <td class="text-end"><?= esc(qty_dashboard_produksi($row->qty_rencana ?? 0)) ?></td>
                                        <td class="text-end"><?= esc(qty_dashboard_produksi($row->total_qty_hasil_posted ?? 0)) ?></td>
                                        <td class="text-end"><?= esc(rupiah_dashboard_produksi($row->total_hpp_posted ?? 0)) ?></td>

                                        <td class="text-center">
                                            <a href="<?= esc(admin_page_url('produksi/perintah/detail') . '&id=' . (int) $row->id_perintah_produksi) ?>" class="btn btn-sm btn-outline-info" title="Detail Perintah">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        Belum ada produksi selesai.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <div>
                        <h2 class="h5 mb-1">Biaya Produksi Draft</h2>
                        <div class="text-muted small">Biaya produksi yang belum diposting</div>
                    </div>

                    <a href="<?= esc(admin_page_url('produksi/biaya')) ?>" class="btn btn-sm btn-outline-warning">
                        Lihat Semua
                    </a>
                </div>

                <div class="table-responsive border rounded">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>No Biaya</th>
                                <th>Perintah / Produk</th>
                                <th class="text-end">Total</th>
                                <th width="90" class="text-center">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if ($biaya_draft_rows->count() > 0): ?>
                                <?php foreach ($biaya_draft_rows as $row): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?= esc($row->no_biaya_produksi ?? '-') ?></div>
                                            <div class="text-muted small">
                                                <?= esc($row->tanggal_biaya ?? '-') ?>
                                                · <?= (int) ($row->jumlah_detail ?? 0) ?> detail
                                            </div>
                                        </td>

                                        <td>
                                            <div><?= esc($row->no_perintah_produksi ?? '-') ?></div>
                                            <div class="text-muted small">
                                                <?= esc(($row->kode_produk ?? '-') . ' - ' . ($row->nama_produk ?? '-')) ?>
                                            </div>
                                        </td>

                                        <td class="text-end"><?= esc(rupiah_dashboard_produksi($row->total_biaya_detail ?? 0)) ?></td>

                                        <td class="text-center">
                                            <a href="<?= esc(admin_page_url('produksi/biaya/detail') . '&id=' . (int) $row->id_biaya_produksi) ?>" class="btn btn-sm btn-outline-info" title="Detail">
                                                <i class="bi bi-eye"></i>
                                            </a>

                                            <a href="<?= esc(admin_url('menu/produksi/biaya/posting.php?id=' . (int) $row->id_biaya_produksi)) ?>"
                                               class="btn btn-sm btn-outline-success"
                                               onclick="return confirm('Posting biaya produksi ini?');"
                                               title="Posting">
                                                <i class="bi bi-journal-check"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        Tidak ada biaya produksi draft.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-xl-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <div>
                        <h2 class="h5 mb-1">Pengambilan Bahan Draft</h2>
                        <div class="text-muted small">Pengambilan bahan yang belum diposting</div>
                    </div>

                    <a href="<?= esc(admin_page_url('produksi/pengambilan-bahan')) ?>" class="btn btn-sm btn-outline-info">
                        Lihat Semua
                    </a>
                </div>

                <div class="table-responsive border rounded">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>No Pengambilan</th>
                                <th>Perintah / Produk</th>
                                <th>Gudang</th>
                                <th width="110" class="text-center">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if ($pengambilan_draft_rows->count() > 0): ?>
                                <?php foreach ($pengambilan_draft_rows as $row): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?= esc($row->no_pengambilan_bahan ?? '-') ?></div>
                                            <div class="text-muted small"><?= esc($row->tanggal_pengambilan ?? '-') ?></div>
                                        </td>

                                        <td>
                                            <div><?= esc($row->no_perintah_produksi ?? '-') ?></div>
                                            <div class="text-muted small"><?= esc(($row->kode_produk ?? '-') . ' - ' . ($row->nama_produk ?? '-')) ?></div>
                                        </td>

                                        <td><?= esc(($row->kode_gudang ?? '-') . ' - ' . ($row->nama_gudang ?? '-')) ?></td>

                                        <td class="text-center">
                                            <a href="<?= esc(admin_page_url('produksi/pengambilan-bahan/detail') . '&id=' . (int) $row->id_pengambilan_bahan) ?>" class="btn btn-sm btn-outline-info" title="Detail">
                                                <i class="bi bi-eye"></i>
                                            </a>

                                            <a href="<?= esc(admin_url('menu/produksi/pengambilan_bahan/posting.php?id=' . (int) $row->id_pengambilan_bahan)) ?>"
                                               class="btn btn-sm btn-outline-success"
                                               onclick="return confirm('Posting pengambilan bahan ini? Stok bahan akan berkurang.');"
                                               title="Posting">
                                                <i class="bi bi-journal-check"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        Tidak ada pengambilan bahan draft.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <div>
                        <h2 class="h5 mb-1">Top Produk Hasil Bulan Ini</h2>
                        <div class="text-muted small">Berdasarkan hasil produksi posted bulan berjalan</div>
                    </div>
                </div>

                <div class="table-responsive border rounded">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Produk</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">HPP Rata-rata</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if ($top_produk_hasil_rows->count() > 0): ?>
                                <?php foreach ($top_produk_hasil_rows as $row): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?= esc($row->nama_produk ?? '-') ?></div>
                                            <div class="text-muted small">
                                                <?= esc($row->kode_produk ?? '-') ?>
                                                · Total HPP <?= esc(rupiah_dashboard_produksi($row->total_hpp ?? 0)) ?>
                                            </div>
                                        </td>

                                        <td class="text-end"><?= esc(qty_dashboard_produksi($row->total_qty ?? 0)) ?></td>
                                        <td class="text-end"><?= esc(rupiah_dashboard_produksi($row->avg_hpp ?? 0)) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">
                                        Belum ada hasil produksi posted bulan ini.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>