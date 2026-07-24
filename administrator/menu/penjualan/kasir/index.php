<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

require_once __DIR__ . '/../_fungsi_penjualan.php';
require_once __DIR__ . '/../../../../helpers/pajak.php';

$id_entitas = (int) ($user['id_entitas'] ?? 0);
$default_ppn_persen = pajak_default_persen($id_entitas, 'PPN', 11.0);

if (!function_exists('pos_get_template_akun_default')) {
    function pos_get_template_akun_default(int $id_entitas, string $kode_jenis_transaksi): array
    {
        $row = Capsule::table('tb_template_jurnal as t')
            ->join('tb_template_jurnal_detail as d', 'd.id_template_jurnal', '=', 't.id_template_jurnal')
            ->leftJoin('tb_coa as c', 'c.id_coa', '=', 'd.id_coa_default')
            ->where('t.id_entitas', $id_entitas)
            ->where('t.kode_jenis_transaksi', $kode_jenis_transaksi)
            ->where('t.status_aktif', 1)
            ->where('d.posisi_dc', 'debit')
            ->where('d.sumber_akun', 'coa_default')
            ->select([
                'c.id_coa',
                'c.kode_coa',
                'c.nama_coa',
            ])
            ->orderBy('d.urutan', 'asc')
            ->first();

        if (!$row) {
            return [
                'id_coa' => 0,
                'kode_coa' => '-',
                'nama_coa' => 'Template belum lengkap',
            ];
        }

        return [
            'id_coa' => (int) ($row->id_coa ?? 0),
            'kode_coa' => (string) ($row->kode_coa ?? '-'),
            'nama_coa' => (string) ($row->nama_coa ?? '-'),
        ];
    }
}

if (!function_exists('pos_template_has_ppn')) {
    function pos_template_has_ppn(int $id_entitas, string $kode_jenis_transaksi): bool
    {
        return Capsule::table('tb_template_jurnal as t')
            ->join('tb_template_jurnal_detail as d', 'd.id_template_jurnal', '=', 't.id_template_jurnal')
            ->where('t.id_entitas', $id_entitas)
            ->where('t.kode_jenis_transaksi', $kode_jenis_transaksi)
            ->where('t.status_aktif', 1)
            ->where('d.sumber_nominal', 'rumus_nominal')
            ->where('d.rumus_nominal', 'ppn')
            ->exists();
    }
}

$akun_template = [
    'tunai' => pos_get_template_akun_default($id_entitas, 'FAKTUR_PENJUALAN_TUNAI'),
    'transfer' => pos_get_template_akun_default($id_entitas, 'FAKTUR_PENJUALAN_TRANSFER'),
    'kredit' => pos_get_template_akun_default($id_entitas, 'FAKTUR_PENJUALAN_KREDIT'),
];

$ppn_template = [
    'tunai' => pos_template_has_ppn($id_entitas, 'FAKTUR_PENJUALAN_TUNAI'),
    'transfer' => pos_template_has_ppn($id_entitas, 'FAKTUR_PENJUALAN_TRANSFER'),
    'kredit' => pos_template_has_ppn($id_entitas, 'FAKTUR_PENJUALAN_KREDIT'),
];

$pelanggan_options = Capsule::table('tb_pelanggan')
    ->where('id_entitas', $id_entitas)
    ->where('status_aktif', 1)
    ->orderBy('nama_pelanggan', 'asc')
    ->get();

$gudang_options = Capsule::table('tb_gudang')
    ->where('id_entitas', $id_entitas)
    ->where('status_aktif', 1)
    ->orderBy('nama_gudang', 'asc')
    ->get();

$produk_rows = Capsule::table('tb_saldo_stok as ss')
    ->join('tb_produk as p', function ($join) {
        $join->on('p.id_produk', '=', 'ss.id_referensi_barang')
            ->where('ss.jenis_barang', '=', 'produk');
    })
    ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'p.id_satuan')
    ->leftJoin('tb_gudang as g', 'g.id_gudang', '=', 'ss.id_gudang')
    ->leftJoin('tb_kategori_produk as kp', 'kp.id_kategori_produk', '=', 'p.id_kategori_produk')
    ->where('ss.id_entitas', $id_entitas)
    ->where('p.id_entitas', $id_entitas)
    ->where('p.status_produk', 1)
    ->whereIn('p.jenis_produk', ['barang_jadi', 'setengah_jadi'])
    ->select([
        'ss.id_gudang',
        'ss.qty_saldo',
        'ss.hpp_rata_rata',
        'p.id_produk',
        'p.kode_produk',
        'p.barcode_produk',
        'p.nama_produk',
        'p.jenis_produk',
        'p.harga_jual',
        'p.gambar_produk',
        's.nama_satuan',
        'g.kode_gudang',
        'g.nama_gudang',
        'kp.nama_kategori_produk',
    ])
    ->orderBy('kp.nama_kategori_produk', 'asc')
    ->orderBy('p.nama_produk', 'asc')
    ->get();

$produk_js = [];
$kategori_map = [];

foreach ($produk_rows as $p) {
    $gambar_url = '';

    if (!empty($p->gambar_produk)) {
        $gambar_url = (string) $p->gambar_produk;

        if (!preg_match('/^(https?:)?\/\//i', $gambar_url) && strpos($gambar_url, '/') !== 0) {
            $gambar_url = '../uploads/produk/' . ltrim($gambar_url, '/');
        }
    }

    $kategori = (string) ($p->nama_kategori_produk ?? 'Tanpa Kategori');
    $kategori_map[$kategori] = true;

    $produk_js[] = [
        'id_gudang' => (int) $p->id_gudang,
        'id_produk' => (int) $p->id_produk,
        'kode_produk' => (string) ($p->kode_produk ?? ''),
        'barcode_produk' => (string) ($p->barcode_produk ?? ''),
        'nama_produk' => (string) ($p->nama_produk ?? ''),
        'jenis_produk' => (string) ($p->jenis_produk ?? ''),
        'kategori' => $kategori,
        'nama_satuan' => (string) ($p->nama_satuan ?? ''),
        'harga_jual' => (float) ($p->harga_jual ?? 0),
        'hpp_rata_rata' => (float) ($p->hpp_rata_rata ?? 0),
        'qty_saldo' => (float) ($p->qty_saldo ?? 0),
        'gambar_produk' => $gambar_url,
        'nama_gudang' => (string) ($p->nama_gudang ?? ''),
    ];
}

$kategori_options = array_keys($kategori_map);
sort($kategori_options);

$kategori_tampil = array_slice($kategori_options, 0, 4);
?>

<style>
    :root {
        --pos-primary: #4f46e5;
        --pos-primary-dark: #3730a3;
        --pos-secondary: #2563eb;
        --pos-accent: #f97316;
        --pos-accent-dark: #ea580c;
        --pos-accent-soft: #fff7ed;
        --pos-blue-soft: #eff6ff;
        --pos-purple-soft: #f5f3ff;
        --pos-danger: #dc3545;
        --pos-dark: #1f2937;
        --pos-text: #212529;
        --pos-muted: #6c757d;
        --pos-line: #e9ecef;
        --pos-soft: #f8fafc;
        --pos-height: calc(100vh - 86px);
    }

    html,
    body {
        overflow: hidden !important;
        height: 100%;
    }

    body {
        margin: 0 !important;
    }

    #mainContent,
    .main-content,
    .main-content.bg-page,
    .bg-page,
    .content-wrapper,
    .page-content,
    .admin-content {
        padding: 0 !important;
        margin: 0 !important;
        max-width: none !important;
        overflow: hidden !important;
        background: #fff !important;
    }

    #mainContent > .container,
    #mainContent > .container-fluid,
    .main-content > .container,
    .main-content > .container-fluid,
    .bg-page > .container,
    .bg-page > .container-fluid {
        padding: 0 !important;
        margin: 0 !important;
        max-width: none !important;
    }

    .page-header {
        display: none !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .pos-root {
        width: 100%;
        height: calc(100vh - 64px);
        min-height: 0;
        display: grid;
        grid-template-columns: minmax(0, 1fr) 395px;
        gap: 14px;
        overflow: hidden;
        padding: 20px;
        background:
            radial-gradient(circle at top left, rgba(79, 70, 229, .12), transparent 34%),
            radial-gradient(circle at bottom right, rgba(249, 115, 22, .13), transparent 32%),
            #f7f8ff;
    }

    .pos-panel {
        background: #fff;
        border: 1px solid rgba(79, 70, 229, .12);
        border-radius: 16px;
        box-shadow: 0 10px 28px rgba(79, 70, 229, .08);
        min-height: 0;
        overflow: hidden;
    }

    .pos-left {
        min-width: 0;
        display: grid;
        grid-template-rows: auto auto auto minmax(0, 1fr) auto;
        padding: 14px;
    }

    .pos-right {
        min-width: 0;
        display: grid;
        grid-template-rows: auto minmax(0, 1fr) auto;
        padding: 14px;
    }

    .pos-header-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 10px;
        flex: 0 0 auto;
    }

    .pos-title h2,
    .pos-checkout-title h2 {
        margin: 0;
        font-size: 18px;
        font-weight: 700;
        color: #312e81;
    }

    .pos-title p {
        margin: 3px 0 0;
        font-size: 13px;
        color: var(--pos-muted);
    }

    .pos-toolbar {
        display: grid;
        grid-template-columns: 240px minmax(220px, 1fr) 44px;
        gap: 9px;
        margin-bottom: 10px;
        flex: 0 0 auto;
    }

    .pos-control {
        height: 40px;
        border: 1px solid var(--pos-line);
        border-radius: 11px;
        background: #fff;
        color: var(--pos-text);
        font-size: 14px;
        padding: 0 11px;
        outline: none;
        width: 100%;
    }

    .pos-control:focus {
        border-color: var(--pos-primary);
        box-shadow: 0 0 0 3px rgba(79, 70, 229, .12);
    }

    .pos-search {
        display: flex;
        align-items: center;
        gap: 9px;
        height: 40px;
        border: 1px solid var(--pos-line);
        border-radius: 11px;
        background: #fff;
        padding: 0 11px;
        min-width: 0;
    }

    .pos-search:focus-within {
        border-color: var(--pos-primary);
        box-shadow: 0 0 0 3px rgba(79, 70, 229, .12);
    }

    .pos-search i {
        color: var(--pos-secondary);
        flex: 0 0 auto;
    }

    .pos-search input {
        border: 0;
        outline: 0;
        width: 100%;
        min-width: 0;
        font-size: 14px;
        background: transparent;
        color: var(--pos-text);
    }

    .pos-search-button {
        height: 40px;
        width: 44px;
        border: 1px solid var(--pos-line);
        border-radius: 11px;
        background: #fff;
        color: var(--pos-secondary);
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .pos-search-button:hover {
        border-color: var(--pos-secondary);
        background: var(--pos-blue-soft);
    }

    .pos-scan-status {
        display: flex;
        align-items: center;
        gap: 7px;
        min-height: 30px;
        margin: -4px 0 10px;
        padding: 7px 10px;
        border-radius: 10px;
        background: #f8fafc;
        border: 1px dashed #dbe3ef;
        color: var(--pos-muted);
        font-size: 12px;
        line-height: 1.35;
        flex: 0 0 auto;
    }

    .pos-scan-status.success {
        color: #198754;
        background: #f0fdf4;
        border-color: #bbf7d0;
    }

    .pos-scan-status.warning {
        color: #b45309;
        background: #fffbeb;
        border-color: #fde68a;
    }

    .pos-scan-status.danger {
        color: #b91c1c;
        background: #fef2f2;
        border-color: #fecaca;
    }

    .pos-category-wrap {
        overflow-x: auto;
        overflow-y: hidden;
        padding-bottom: 4px;
        margin-bottom: 10px;
        flex: 0 0 auto;
    }

    .pos-category-wrap::-webkit-scrollbar {
        height: 6px;
    }

    .pos-category-wrap::-webkit-scrollbar-thumb {
        background: #d1d5db;
        border-radius: 99px;
    }

    .pos-category-list {
        display: flex;
        gap: 9px;
        min-width: max-content;
    }

    .pos-category-btn {
        border: 1px solid var(--pos-line);
        border-radius: 13px;
        background: #fff;
        color: var(--pos-text);
        min-width: 128px;
        height: 54px;
        padding: 8px 10px;
        display: flex;
        align-items: center;
        gap: 9px;
        text-align: left;
        cursor: pointer;
        transition: .15s ease;
    }

    .pos-category-btn:hover,
    .pos-category-btn.active {
        border-color: var(--pos-primary);
        background: linear-gradient(135deg, var(--pos-purple-soft), var(--pos-blue-soft));
        color: var(--pos-primary-dark);
    }

    .pos-category-icon {
        width: 31px;
        height: 31px;
        border-radius: 11px;
        background: linear-gradient(135deg, var(--pos-blue-soft), var(--pos-purple-soft));
        color: var(--pos-primary);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
    }

    .pos-category-btn.active .pos-category-icon,
    .pos-category-btn:hover .pos-category-icon {
        background: linear-gradient(135deg, var(--pos-primary), var(--pos-secondary));
        color: #fff;
    }

    .pos-category-text h4 {
        margin: 0;
        font-size: 13px;
        font-weight: 700;
        line-height: 1.2;
        max-width: 78px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .pos-category-text p {
        margin: 1px 0 0;
        font-size: 12px;
        color: var(--pos-muted);
        line-height: 1.2;
    }

    .pos-products-area {
        min-height: 0;
        overflow-y: auto;
        overflow-x: hidden;
        padding-right: 4px;
        padding-bottom: 4px;
    }

    .pos-products-area::-webkit-scrollbar,
    .pos-cart-list::-webkit-scrollbar {
        width: 7px;
    }

    .pos-products-area::-webkit-scrollbar-thumb,
    .pos-cart-list::-webkit-scrollbar-thumb {
        background: #d1d5db;
        border-radius: 99px;
    }

    .pos-product-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(128px, 1fr));
        gap: 10px;
    }

    .pos-product-card {
        border: 1px solid var(--pos-line);
        border-radius: 14px;
        background: #fff;
        padding: 9px;
        cursor: pointer;
        min-height: 164px;
        display: flex;
        flex-direction: column;
        transition: .15s ease;
    }

    .pos-product-card:hover {
        transform: translateY(-2px);
        border-color: var(--pos-primary);
        box-shadow: 0 12px 26px rgba(79, 70, 229, .14);
    }

    .pos-product-card.disabled {
        opacity: .55;
        cursor: not-allowed;
        filter: grayscale(.35);
    }

    .pos-product-img {
        width: 100%;
        height: 70px;
        border-radius: 12px;
        background: linear-gradient(135deg, var(--pos-blue-soft), var(--pos-purple-soft));
        color: var(--pos-primary);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        margin-bottom: 7px;
    }

    .pos-product-card:hover .pos-product-img {
        background: linear-gradient(135deg, rgba(79, 70, 229, .14), rgba(249, 115, 22, .14));
    }

    .pos-product-img i {
        font-size: 28px;
    }

    .pos-product-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .pos-product-card h4 {
        margin: 0 0 4px;
        color: var(--pos-text);
        font-size: 13px;
        font-weight: 700;
        line-height: 1.25;
        min-height: 31px;
        overflow: hidden;
    }

    .pos-product-meta {
        color: var(--pos-muted);
        font-size: 12px;
        line-height: 1.2;
        margin-bottom: 6px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .pos-product-foot {
        margin-top: auto;
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: 6px;
    }

    .pos-price {
        color: var(--pos-accent);
        font-size: 13px;
        font-weight: 700;
        white-space: nowrap;
    }

    .pos-stock {
        color: var(--pos-muted);
        font-size: 11px;
        text-align: right;
        white-space: nowrap;
    }

    .pos-product-empty {
        min-height: 260px;
        border: 1px dashed var(--pos-line);
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--pos-muted);
        font-size: 14px;
        text-align: center;
        padding: 22px;
        grid-column: 1 / -1;
    }

    .pos-footer-left {
        margin-top: 10px;
        padding-top: 10px;
        background: #fff;
        border-top: 1px solid var(--pos-line);
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        flex: 0 0 auto;
    }

    .pos-track {
        border: 0;
        border-radius: 11px;
        background: linear-gradient(135deg, var(--pos-secondary), var(--pos-primary));
        color: #fff;
        font-size: 14px;
        font-weight: 700;
        height: 40px;
        padding: 0 14px;
        box-shadow: 0 8px 18px rgba(37, 99, 235, .22);
    }

    .pos-track:hover {
        background: linear-gradient(135deg, var(--pos-primary), var(--pos-primary-dark));
    }

    .pos-count {
        color: var(--pos-muted);
        font-size: 14px;
    }

    .pos-checkout-title {
        display: flex;
        align-items: start;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 10px;
        flex: 0 0 auto;
    }

    .pos-checkout-title p {
        margin: 3px 0 0;
        color: var(--pos-muted);
        font-size: 13px;
    }

    .pos-customer-box {
        display: grid;
        gap: 9px;
        flex: 0 0 auto;
    }

    .pos-customer-pay-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 150px;
        gap: 9px;
    }

    .pos-template {
        min-height: 38px;
        padding: 9px 11px;
        border-radius: 11px;
        background: linear-gradient(135deg, var(--pos-blue-soft), var(--pos-purple-soft));
        color: #4338ca;
        border: 1px solid rgba(79, 70, 229, .12);
        font-size: 12px;
        line-height: 1.35;
        word-break: break-word;
    }

    .pos-cart-list {
        min-height: 0;
        overflow-y: auto;
        overflow-x: hidden;
        border: 1px solid var(--pos-line);
        border-radius: 14px;
        padding: 11px;
        background: #fff;
        margin-top: 10px;
    }

    .pos-empty-cart {
        min-height: 190px;
        border: 1px dashed var(--pos-line);
        border-radius: 13px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--pos-muted);
        font-size: 14px;
        text-align: center;
        padding: 18px;
    }

    .pos-cart-item {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 7px;
        padding: 10px 0;
        border-bottom: 1px solid var(--pos-line);
    }

    .pos-cart-item:first-child {
        padding-top: 0;
    }

    .pos-cart-item:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }

    .pos-cart-info h4 {
        margin: 0 0 3px;
        color: var(--pos-text);
        font-size: 13px;
        font-weight: 700;
        line-height: 1.3;
        word-break: break-word;
    }

    .pos-cart-info p {
        margin: 0;
        color: var(--pos-muted);
        font-size: 12px;
        line-height: 1.35;
    }

    .pos-cart-actions {
        display: grid;
        grid-template-columns: 112px minmax(85px, 1fr) 32px;
        gap: 7px;
        align-items: center;
    }

    .pos-qty {
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .pos-qty button {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        border: 1px solid var(--pos-primary);
        background: #fff;
        color: var(--pos-primary);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        padding: 0;
        line-height: 1;
        font-size: 15px;
        font-weight: 700;
    }

    .pos-qty button:hover {
        background: var(--pos-primary);
        color: #fff;
    }

    .pos-qty input {
        width: 38px;
        height: 30px;
        border: 1px solid var(--pos-line);
        outline: 0;
        background: #fff;
        border-radius: 9px;
        text-align: center;
        color: var(--pos-text);
        font-size: 13px;
        font-weight: 700;
    }

    .pos-cart-price {
        color: var(--pos-text);
        font-size: 13px;
        font-weight: 700;
        text-align: right;
        white-space: nowrap;
    }

    .pos-remove {
        width: 30px;
        height: 30px;
        border: 1px solid #f5c2c7;
        border-radius: 50%;
        background: #fff;
        color: var(--pos-danger);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
        font-weight: 700;
        cursor: pointer;
        padding: 0;
        line-height: 1;
    }

    .pos-checkout-bottom {
        background: #fff;
        padding-top: 8px;
        border-top: 1px solid var(--pos-line);
        flex: 0 0 auto;
    }

    .pos-summary-box {
        border: 1px solid var(--pos-line);
        border-radius: 14px;
        padding: 9px;
        margin-bottom: 9px;
        background: #fff;
    }

    .pos-summary-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 7px;
    }

    .pos-summary-cell {
        min-width: 0;
        border: 1px solid #f1f3f5;
        border-radius: 11px;
        padding: 7px 8px;
        background: #fbfcfd;
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 6px;
        align-items: center;
    }

    .pos-summary-cell label,
    .pos-summary-cell .pos-summary-label {
        margin: 0;
        color: var(--pos-muted);
        font-size: 11px;
        line-height: 1.15;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .pos-summary-value {
        color: var(--pos-text);
        font-size: 12px;
        font-weight: 800;
        text-align: right;
        white-space: nowrap;
    }

    .pos-summary-percent {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        justify-self: end;
    }

    .pos-summary-percent input {
        width: 42px;
        height: 25px;
        border: 1px solid var(--pos-line);
        border-radius: 8px;
        background: #fff;
        color: var(--pos-text);
        text-align: right;
        padding: 0 6px;
        font-size: 12px;
        font-weight: 800;
        outline: 0;
    }

    .pos-summary-percent input:focus {
        border-color: var(--pos-primary);
        box-shadow: 0 0 0 3px rgba(79, 70, 229, .12);
    }

    .pos-summary-percent span {
        font-size: 11px;
        color: var(--pos-muted);
        font-weight: 700;
    }

    .pos-summary-small {
        grid-column: 1 / -1;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 6px;
        margin-top: 6px;
        color: var(--pos-muted);
        font-size: 11px;
    }

    .pos-summary-small div {
        min-width: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .pos-summary-small strong {
        color: var(--pos-text);
        font-weight: 700;
    }

    .pos-total-cell {
        background: linear-gradient(135deg, var(--pos-purple-soft), var(--pos-accent-soft));
        border-color: rgba(249, 115, 22, .22);
    }

    .pos-total-cell .pos-summary-label {
        color: var(--pos-primary-dark);
        font-weight: 800;
    }

    .pos-total-cell .pos-summary-value {
        color: var(--pos-accent);
        font-size: 15px;
    }

    .pos-action-row {
        display: grid;
        grid-template-columns: 1fr 1.4fr;
        gap: 9px;
    }

    .pos-btn {
        height: 44px;
        border: 0;
        border-radius: 11px;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
    }

    .pos-cancel {
        background: #f1f3f5;
        color: var(--pos-dark);
    }

    .pos-cancel:hover {
        background: #fee2e2;
        color: #991b1b;
    }

    .pos-place {
        background: linear-gradient(135deg, var(--pos-primary), var(--pos-secondary));
        color: #fff;
        box-shadow: 0 10px 20px rgba(79, 70, 229, .22);
    }

    .pos-place:hover {
        background: linear-gradient(135deg, var(--pos-primary-dark), var(--pos-primary));
    }

    .pos-modal-backdrop {
        position: fixed;
        inset: 0;
        z-index: 9999;
        background: rgba(15, 23, 42, .55);
        display: none;
        align-items: center;
        justify-content: center;
        padding: 18px;
    }

    .pos-modal-backdrop.show {
        display: flex;
    }

    .pos-modal-card {
        width: min(820px, calc(100vw - 32px));
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 24px 60px rgba(15, 23, 42, .25);
        overflow: hidden;
    }

    .pos-modal-header {
        padding: 16px 18px;
        border-bottom: 1px solid var(--pos-line);
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
    }

    .pos-modal-header h3 {
        margin: 0;
        font-size: 18px;
        font-weight: 700;
        color: #312e81;
    }

    .pos-modal-close {
        width: 34px;
        height: 34px;
        border: 1px solid var(--pos-line);
        background: #fff;
        border-radius: 50%;
        font-size: 18px;
        line-height: 1;
    }

    .pos-modal-body {
        padding: 18px;
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px 16px;
        align-items: start;
    }

    .pos-modal-total {
        border-radius: 14px;
        background: linear-gradient(135deg, var(--pos-blue-soft), var(--pos-purple-soft));
        padding: 14px;
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: center;
    }

    .pos-modal-total {
        grid-column: 1 / -1;
    }

    .pos-transfer-box {
        grid-column: 1 / -1;
        display: grid;
        grid-template-columns: minmax(0, 1.15fr) minmax(0, .85fr);
        gap: 8px 16px;
        padding: 14px;
        border: 1px dashed rgba(79, 70, 229, .28);
        border-radius: 14px;
        background: #fbfbff;
    }

    .pos-transfer-box .pos-modal-label {
        margin-bottom: 0;
    }

    .pos-transfer-box .pos-modal-label:nth-child(1) {
        grid-column: 1;
        grid-row: 1;
    }

    .pos-transfer-box #no_referensi_transfer {
        grid-column: 1;
        grid-row: 2;
    }

    .pos-transfer-box .pos-transfer-help:nth-child(3) {
        grid-column: 1;
        grid-row: 3 / span 3;
    }

    .pos-transfer-box .pos-modal-label:nth-child(4) {
        grid-column: 2;
        grid-row: 1;
    }

    .pos-transfer-box #nama_pengirim_transfer {
        grid-column: 2;
        grid-row: 2;
    }

    .pos-transfer-box .pos-modal-label:nth-child(6) {
        grid-column: 2;
        grid-row: 3;
    }

    .pos-transfer-box #bukti_bayar_pos {
        grid-column: 2;
        grid-row: 4;
    }

    .pos-transfer-box .pos-transfer-help:nth-child(8) {
        grid-column: 2;
        grid-row: 5;
    }

    .pos-transfer-help {
        font-size: 12px;
        line-height: 1.45;
        color: var(--pos-muted);
    }

    .pos-modal-body > #modal_payment_note {
        grid-column: 1 / -1;
        margin-top: -2px;
    }

    .pos-modal-total span {
        color: var(--pos-muted);
        font-size: 14px;
    }

    .pos-modal-total strong {
        font-size: 20px;
        color: var(--pos-primary-dark);
    }

    .pos-modal-label {
        font-size: 14px;
        color: var(--pos-muted);
        margin-bottom: 6px;
    }

    .pos-modal-input {
        height: 46px;
        border: 1px solid var(--pos-line);
        border-radius: 12px;
        padding: 0 12px;
        width: 100%;
        text-align: right;
        font-size: 18px;
        font-weight: 700;
        outline: 0;
    }

    .pos-modal-input:focus {
        border-color: var(--pos-primary);
        box-shadow: 0 0 0 3px rgba(79, 70, 229, .12);
    }

    .pos-modal-change {
        border: 1px solid var(--pos-line);
        border-radius: 14px;
        padding: 14px;
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: center;
    }

    .pos-modal-change span {
        color: var(--pos-muted);
        font-size: 14px;
    }

    .pos-modal-change strong {
        font-size: 20px;
        color: var(--pos-accent);
    }

    .pos-modal-actions {
        padding: 14px 18px 18px;
        display: grid;
        grid-template-columns: 1fr 1.4fr;
        gap: 10px;
    }

    #pos-hidden-inputs {
        display: none;
    }

    @media (max-width: 1199px) {
        html,
        body,
        #mainContent,
        .main-content,
        .main-content.bg-page,
        .bg-page {
            overflow: auto !important;
        }

        .pos-root {
            height: auto;
            min-height: 0;
            overflow: visible;
            grid-template-columns: 1fr;
        }

        .pos-left,
        .pos-right {
            min-height: 620px;
        }

        .pos-products-area {
            max-height: 520px;
        }

        .pos-cart-list {
            max-height: 360px;
        }
    }

    @media (max-width: 767px) {
        .pos-root {
            padding: 8px;
        }

        .pos-left,
        .pos-right {
            padding: 12px;
            border-radius: 14px;
        }

        .pos-toolbar {
            grid-template-columns: 1fr;
        }

        .pos-search-button {
            width: 100%;
        }

        .pos-product-grid {
            grid-template-columns: repeat(auto-fill, minmax(126px, 1fr));
        }

        .pos-customer-pay-row {
            grid-template-columns: 1fr;
        }

        .pos-cart-actions {
            grid-template-columns: 1fr;
        }

        .pos-cart-price {
            text-align: left;
        }

        .pos-action-row,
        .pos-modal-actions {
            grid-template-columns: 1fr;
        }

        .pos-summary-grid {
            grid-template-columns: 1fr;
        }

        .pos-summary-small {
            grid-template-columns: 1fr;
        }

        .pos-modal-card {
            width: min(460px, 100%);
        }

        .pos-modal-body {
            grid-template-columns: 1fr;
        }

        .pos-transfer-box {
            grid-template-columns: 1fr;
        }

        .pos-transfer-box .pos-modal-label,
        .pos-transfer-box .pos-modal-input,
        .pos-transfer-box .pos-modal-file,
        .pos-transfer-box .pos-transfer-help,
        .pos-transfer-box #no_referensi_transfer,
        .pos-transfer-box #nama_pengirim_transfer,
        .pos-transfer-box #bukti_bayar_pos {
            grid-column: 1 !important;
            grid-row: auto !important;
        }
    }


    /* Revisi tampilan kasir: layout lebih padat, area keranjang tidak scroll saat kosong, dan elemen tetap proporsional di layar 1366px. */
    .pos-root {
        height: calc(100dvh - 78px);
        min-height: 520px;
        grid-template-columns: minmax(640px, 1fr) minmax(370px, 410px);
        gap: 12px;
        padding: 12px;
        background:
            radial-gradient(circle at top left, rgba(79, 70, 229, .10), transparent 30%),
            radial-gradient(circle at bottom right, rgba(249, 115, 22, .10), transparent 30%),
            #f6f7ff;
    }

    .pos-panel {
        border-radius: 14px;
        box-shadow: 0 8px 22px rgba(15, 23, 42, .06);
    }

    .pos-left {
        grid-template-rows: auto auto auto minmax(160px, 1fr) auto;
        gap: 8px;
        padding: 14px 14px 12px;
    }

    .pos-right {
        grid-template-rows: auto minmax(128px, 1fr) auto;
        gap: 8px;
        padding: 14px;
    }

    .pos-header-row,
    .pos-toolbar,
    .pos-scan-status,
    .pos-category-wrap,
    .pos-checkout-title {
        margin-bottom: 0;
    }

    .pos-title h2,
    .pos-checkout-title h2 {
        font-size: 17px;
        line-height: 1.15;
    }

    .pos-title p,
    .pos-checkout-title p {
        font-size: 12px;
        line-height: 1.25;
    }

    .pos-toolbar {
        grid-template-columns: minmax(180px, 240px) minmax(260px, 1fr) 44px;
        align-items: center;
    }

    .pos-control,
    .pos-search,
    .pos-search-button {
        height: 40px;
    }

    .pos-scan-status {
        min-height: 30px;
        padding: 6px 9px;
        font-size: 12px;
    }

    .pos-category-list {
        gap: 8px;
    }

    .pos-category-btn {
        height: 48px;
        min-width: 126px;
        padding: 7px 9px;
    }

    .pos-category-icon {
        width: 29px;
        height: 29px;
        border-radius: 10px;
    }

    .pos-products-area {
        border: 1px dashed #e5e7eb;
        border-radius: 14px;
        background: #fff;
        padding: 8px;
    }

    .pos-product-empty {
        min-height: 100%;
        height: 100%;
        border: 0;
        padding: 16px;
    }

    .pos-product-grid {
        grid-template-columns: repeat(auto-fill, minmax(136px, 1fr));
        gap: 9px;
    }

    .pos-product-card {
        min-height: 158px;
        padding: 8px;
    }

    .pos-product-img {
        height: 66px;
        margin-bottom: 6px;
    }

    .pos-footer-left {
        margin-top: 0;
        padding-top: 8px;
    }

    .pos-track,
    .pos-btn {
        height: 40px;
    }

    .pos-customer-box {
        gap: 8px;
    }

    .pos-customer-pay-row {
        grid-template-columns: minmax(0, 1fr) 148px;
        gap: 8px;
    }

    .pos-template {
        min-height: 34px;
        padding: 8px 10px;
    }

    .pos-cart-list {
        margin-top: 0;
        padding: 8px;
        display: flex;
        flex-direction: column;
    }

    .pos-empty-cart {
        height: 100%;
        min-height: 0;
        flex: 1 1 auto;
        padding: 12px;
    }

    .pos-checkout-bottom {
        padding-top: 8px;
    }

    .pos-summary-box {
        padding: 8px;
        margin-bottom: 8px;
    }

    .pos-summary-grid {
        gap: 6px;
    }

    .pos-summary-cell {
        padding: 7px 8px;
        min-height: 39px;
    }

    .pos-total-cell .pos-summary-value {
        font-size: 14px;
    }

    .pos-action-row {
        gap: 8px;
    }

    @media (max-width: 1366px) and (min-width: 1200px) {
        .pos-root {
            grid-template-columns: minmax(0, 1fr) 390px;
            padding: 10px;
            gap: 10px;
        }

        .pos-left,
        .pos-right {
            padding: 12px;
        }

        .pos-toolbar {
            grid-template-columns: minmax(170px, 240px) minmax(240px, 1fr) 44px;
        }

        .pos-category-btn {
            min-width: 122px;
        }
    }

    @media (max-height: 720px) and (min-width: 1200px) {
        .pos-root {
            height: calc(100dvh - 72px);
            min-height: 500px;
        }

        .pos-left,
        .pos-right {
            gap: 7px;
        }

        .pos-category-btn {
            height: 46px;
        }

        .pos-products-area {
            padding: 7px;
        }

        .pos-product-grid {
            grid-template-columns: repeat(auto-fill, minmax(128px, 1fr));
        }

        .pos-empty-cart .fs-2,
        .pos-product-empty .fs-1 {
            font-size: 1.7rem !important;
            margin-bottom: .25rem !important;
        }
    }


    /* Revisi 25/05/2026: rapatkan area produk agar card tidak turun terlalu jauh. */
    .pos-left {
        display: flex !important;
        flex-direction: column !important;
        gap: 8px !important;
    }

    .pos-header-row,
    .pos-toolbar,
    .pos-scan-status,
    .pos-category-wrap,
    .pos-footer-left {
        flex: 0 0 auto !important;
    }

    .pos-category-wrap {
        margin-bottom: 0 !important;
        padding-bottom: 2px !important;
    }

    .pos-products-area {
        flex: 1 1 auto !important;
        min-height: 0 !important;
        margin-top: 0 !important;
        align-self: stretch !important;
        display: block !important;
    }

    .pos-product-grid {
        align-content: start !important;
        align-items: start !important;
        justify-content: start !important;
    }

    @media (max-width: 1366px) and (min-width: 1200px) {
        .pos-left {
            gap: 7px !important;
        }

        .pos-products-area {
            padding-top: 7px !important;
        }
    }


    /* =========================================================
       REVISI KERANJANG POS
       - Kolom keranjang dibuat lebih lebar
       - Item keranjang sejajar: produk | qty | subtotal | hapus
       - Info template + aplikasi lain menjadi 1 baris 2 kolom
       ========================================================= */
    .pos-root {
        grid-template-columns: minmax(0, 1fr) minmax(530px, 560px) !important;
    }

    .pos-right {
        min-width: 0 !important;
    }

    .pos-checkout-title {
        align-items: center !important;
        margin-bottom: 0 !important;
    }

    .pos-checkout-title h2 {
        font-size: 18px !important;
        line-height: 1.15 !important;
    }

    .pos-checkout-title p {
        font-size: 12px !important;
        line-height: 1.2 !important;
    }

    .pos-customer-box {
        gap: 7px !important;
    }

    .pos-customer-pay-row {
        grid-template-columns: minmax(0, 1.35fr) 118px 150px !important;
        gap: 7px !important;
    }

    .pos-info-grid {
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) !important;
        gap: 7px !important;
        align-items: stretch !important;
    }

    .pos-template {
        min-height: auto !important;
        height: auto !important;
        padding: 8px 10px !important;
        font-size: 11.5px !important;
        line-height: 1.35 !important;
        white-space: normal !important;
        overflow: visible !important;
        text-overflow: unset !important;
        word-break: normal !important;
        overflow-wrap: anywhere !important;
    }

    .pos-template b {
        white-space: nowrap !important;
    }

    .pos-template-warning {
        background: #fff7ed !important;
        border-color: #fed7aa !important;
        color: #9a3412 !important;
    }

    .pos-cart-list {
        padding: 8px !important;
        margin-top: 0 !important;
    }

    .pos-cart-item {
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) auto !important;
        gap: 8px !important;
        align-items: center !important;
        padding: 8px 0 !important;
    }

    .pos-cart-info {
        min-width: 0 !important;
    }

    .pos-cart-info h4 {
        font-size: 13px !important;
        line-height: 1.2 !important;
        margin: 0 0 2px !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        word-break: normal !important;
    }

    .pos-cart-info p {
        font-size: 11.5px !important;
        line-height: 1.2 !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
    }

    .pos-cart-actions {
        display: grid !important;
        grid-template-columns: 104px 108px 30px !important;
        gap: 7px !important;
        align-items: center !important;
    }

    .pos-qty {
        gap: 4px !important;
        justify-content: center !important;
    }

    .pos-qty button {
        width: 27px !important;
        height: 27px !important;
        font-size: 14px !important;
    }

    .pos-qty input {
        width: 36px !important;
        height: 28px !important;
        font-size: 13px !important;
    }

    .pos-cart-price {
        font-size: 13px !important;
        text-align: right !important;
        white-space: nowrap !important;
    }

    .pos-remove {
        width: 29px !important;
        height: 29px !important;
        font-size: 14px !important;
    }

    .pos-summary-box {
        padding: 7px !important;
        margin-bottom: 7px !important;
    }

    .pos-summary-grid {
        gap: 6px !important;
    }

    .pos-summary-cell {
        min-height: 36px !important;
        padding: 6px 7px !important;
    }

    .pos-summary-cell label,
    .pos-summary-cell .pos-summary-label {
        font-size: 10.8px !important;
    }

    .pos-summary-value {
        font-size: 11.5px !important;
    }

    .pos-total-cell .pos-summary-value {
        font-size: 14px !important;
    }

    .pos-summary-small {
        font-size: 10.8px !important;
        gap: 5px !important;
        margin-top: 5px !important;
    }

    .pos-action-row {
        gap: 7px !important;
    }

    .pos-btn {
        height: 39px !important;
    }



    /* Ringkasan pembayaran dibuat 3 kolom: Subtotal | Diskon | PPN, lalu Total 1 baris penuh. */
    .pos-summary-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
        gap: 7px !important;
    }

    .pos-summary-cell {
        min-width: 0 !important;
    }

    .pos-total-cell {
        grid-column: 1 / -1 !important;
        grid-template-columns: minmax(0, 1fr) auto !important;
    }

    .pos-summary-small {
        grid-column: 1 / -1 !important;
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
    }

    @media (max-width: 1366px) and (min-width: 1200px) {
        .pos-root {
            grid-template-columns: minmax(0, 1fr) minmax(520px, 540px) !important;
            padding: 10px !important;
            gap: 10px !important;
        }

        .pos-left,
        .pos-right {
            padding: 12px !important;
        }

        .pos-customer-pay-row {
            grid-template-columns: minmax(0, 1.25fr) 112px 140px !important;
        }

        .pos-cart-actions {
            grid-template-columns: 100px 104px 30px !important;
        }


        .pos-summary-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
        }

        .pos-total-cell {
            grid-column: 1 / -1 !important;
        }
    }

    @media (max-width: 1199px) {
        .pos-root {
            grid-template-columns: 1fr !important;
        }

        .pos-customer-pay-row,
        .pos-info-grid {
            grid-template-columns: 1fr !important;
        }

        .pos-template {
            white-space: normal !important;
        }

        .pos-cart-item {
            grid-template-columns: 1fr !important;
            align-items: start !important;
        }

        .pos-cart-actions {
            grid-template-columns: 105px minmax(90px, 1fr) 32px !important;
            width: 100% !important;
        }

        .pos-cart-price {
            text-align: right !important;
        }

        .pos-summary-grid,
        .pos-summary-small {
            grid-template-columns: 1fr !important;
        }

        .pos-total-cell {
            grid-column: auto !important;
        }
    }

</style>

<form method="post" action="<?= esc(admin_url('index.php?menu=penjualan/kasir/simpan')) ?>" id="form-pos" enctype="multipart/form-data">
    <input type="hidden" name="tanggal_transaksi" value="<?= esc(date('Y-m-d')) ?>">
    <input type="hidden" name="jumlah_dibayar" id="jumlah_dibayar" value="0.00">
    <input type="hidden" name="kembalian" id="kembalian" value="0.00">
    <input type="hidden" name="sumber_pos" id="sumber_pos" value="toko">

    <div class="pos-root">
        <main class="pos-panel pos-left">
            <div class="pos-header-row">
                <div class="pos-title">
                    <h2>Semua Produk</h2>
                    <p><?= esc(date('d/m/Y H:i')) ?> · Klik produk atau pindai barcode.</p>
                </div>

                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="posResetCart()">
                    Reset Pesanan
                </button>
            </div>

            <div class="pos-toolbar">
                <select name="id_gudang" id="id_gudang" class="pos-control" onchange="posGantiGudang()" required>
                    <option value="">Pilih Gudang</option>
                    <?php foreach ($gudang_options as $g): ?>
                        <option value="<?= (int) $g->id_gudang ?>">
                            <?= esc((string) (($g->kode_gudang ?? '-') . ' - ' . ($g->nama_gudang ?? '-'))) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label class="pos-search">
                    <i class="bi bi-upc-scan"></i>
                    <input
                        type="text"
                        id="barcode_input"
                        placeholder="Cari produk atau pindai barcode lalu Enter..."
                        autocomplete="off"
                        inputmode="search"
                        oninput="posBarcodeInputChanged()"
                        onkeydown="posBarcodeKeydown(event)">
                </label>

                <button type="button" class="pos-search-button" onclick="posRenderProduk()" title="Cari">
                    <i class="bi bi-search"></i>
                </button>
            </div>

            <div class="pos-scan-status" id="pos_scan_status">
                <i class="bi bi-upc-scan"></i>
                <span>Siap scan. Arahkan scanner ke barcode produk; hasil scan akan langsung masuk ke keranjang.</span>
            </div>

            <div class="pos-category-wrap">
                <div class="pos-category-list">
                    <button type="button" class="pos-category-btn active" data-filter="semua" onclick="posSetFilter(this)">
                        <span class="pos-category-icon"><i class="bi bi-grid"></i></span>
                        <span class="pos-category-text">
                            <h4>Semua</h4>
                            <p>Produk</p>
                        </span>
                    </button>

                    <button type="button" class="pos-category-btn" data-filter="stok_tersedia" onclick="posSetFilter(this)">
                        <span class="pos-category-icon"><i class="bi bi-check2-circle"></i></span>
                        <span class="pos-category-text">
                            <h4>Stok Ada</h4>
                            <p>Siap Jual</p>
                        </span>
                    </button>

                    <button type="button" class="pos-category-btn" data-filter="barang_jadi" onclick="posSetFilter(this)">
                        <span class="pos-category-icon"><i class="bi bi-box-seam"></i></span>
                        <span class="pos-category-text">
                            <h4>Barang Jadi</h4>
                            <p>Produk</p>
                        </span>
                    </button>

                    <button type="button" class="pos-category-btn" data-filter="setengah_jadi" onclick="posSetFilter(this)">
                        <span class="pos-category-icon"><i class="bi bi-box"></i></span>
                        <span class="pos-category-text">
                            <h4>Setengah Jadi</h4>
                            <p>Produk</p>
                        </span>
                    </button>

                    <?php foreach ($kategori_tampil as $kategori): ?>
                        <button
                            type="button"
                            class="pos-category-btn"
                            data-filter="kategori:<?= esc((string) $kategori) ?>"
                            onclick="posSetFilter(this)">
                            <span class="pos-category-icon"><i class="bi bi-tag"></i></span>
                            <span class="pos-category-text">
                                <h4><?= esc((string) $kategori) ?></h4>
                                <p>Kategori</p>
                            </span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <section class="pos-products-area">
                <div id="pos-product-grid" class="pos-product-grid"></div>
            </section>

            <div class="pos-footer-left">
                <button type="button" class="pos-track" onclick="document.getElementById('barcode_input').focus()">
                    Fokus Pindai / Cari
                </button>

                <div class="pos-count" id="produk_count_text">0 Produk</div>
            </div>
        </main>

        <aside class="pos-panel pos-right">
            <div>
                <div class="pos-checkout-title">
                    <div style="margin-bottom: 10px;">
                        <h2>Keranjang</h2>
                        <p id="customer_id_text">Pelanggan belum dipilih</p>
                    </div>

                    <a href="<?= esc(admin_page_url('penjualan/kasir/riwayat')) ?>" class="btn btn-outline-secondary btn-sm">
                        Riwayat
                    </a>
                </div>

                <div class="pos-customer-box">
                    <div class="pos-customer-pay-row">
                        <select name="id_pelanggan" id="id_pelanggan" class="pos-control" onchange="posGantiPelanggan()" required>
                            <option value="">Pilih Pelanggan</option>
                            <?php foreach ($pelanggan_options as $p): ?>
                                <option
                                    value="<?= (int) $p->id_pelanggan ?>"
                                    data-tempo="<?= (int) ($p->tempo_hari ?? 0) ?>"
                                    data-kode="<?= esc((string) ($p->kode_pelanggan ?? '-')) ?>"
                                    data-nama="<?= esc((string) ($p->nama_pelanggan ?? '-')) ?>">
                                    <?= esc((string) (($p->kode_pelanggan ?? '-') . ' - ' . ($p->nama_pelanggan ?? '-'))) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <select name="jenis_pembayaran" id="jenis_pembayaran" class="pos-control" onchange="posGantiPembayaran()" required>
                            <option value="tunai">Tunai</option>
                            <option value="transfer">Transfer</option>
                            <option value="kredit">Kredit</option>
                        </select>

                        <select id="sumber_pos_select" class="pos-control" onchange="document.getElementById('sumber_pos').value=this.value; posGantiSumberPos();">
                            <option value="toko">Toko</option>
                            <option value="aplikasi_lain">Aplikasi lain / Driver</option>
                        </select>
                    </div>

                    <div class="pos-info-grid">
                        <div id="template_account_info" class="pos-template">
                            Template akun: -
                        </div>

                        <div id="sumber_pos_info" class="pos-template pos-template-warning" style="display:none;">
                            Aplikasi lain: <b>Tunai</b> driver, <b>Transfer</b> rekening/QRIS, <b>Kredit</b> piutang.
                        </div>
                    </div>
                </div>
            </div>

            <section id="pos-cart" class="pos-cart-list">
                <div class="pos-empty-cart">
                    <div>
                        <div class="fs-2 mb-2"><i class="bi bi-cart3"></i></div>
                        Keranjang kosong<br>
                        <small>Klik produk atau pindai barcode.</small>
                    </div>
                </div>
            </section>

            <div class="pos-checkout-bottom">
                <section class="pos-summary-box">
                    <div class="pos-summary-grid">
                        <div class="pos-summary-cell">
                            <span class="pos-summary-label">Subtotal</span>
                            <strong class="pos-summary-value" id="subtotal_text">Rp 0.00</strong>
                        </div>

                        <div class="pos-summary-cell">
                            <label for="diskon_persen">Diskon</label>
                            <span class="pos-summary-percent">
                                <input type="number" id="diskon_persen" value="0" min="0" max="100" step="1" oninput="posHitungTotal()" onblur="posHitungTotal()">
                                <span>%</span>
                            </span>
                        </div>

                        <div class="pos-summary-cell">
                            <label for="ppn_persen" id="ppn_label">PPN</label>
                            <span class="pos-summary-percent">
                                <input type="number" id="ppn_persen" value="<?= esc((string) $default_ppn_persen) ?>" min="0" max="100" step="0.01" data-default-ppn="<?= esc((string) $default_ppn_persen) ?>" oninput="posHitungTotal()" onblur="posHitungTotal()">
                                <span>%</span>
                            </span>
                        </div>

                        <div class="pos-summary-cell pos-total-cell">
                            <span class="pos-summary-label">Total</span>
                            <strong class="pos-summary-value" id="total_text">Rp 0.00</strong>
                        </div>

                        <div class="pos-summary-small">
                            <div>Diskon: <strong id="diskon_amount_text">Rp 0.00</strong></div>
                            <div>PPN: <strong id="ppn_amount_text">Rp 0.00</strong></div>
                        </div>
                    </div>

                    <div id="jatuh_tempo_text" class="pos-summary-small" style="display:none;">
                        <div>Jatuh Tempo: <strong>-</strong></div>
                    </div>

                    <input type="hidden" name="subtotal" id="subtotal" value="0.00">
                    <input type="hidden" name="total" id="total" value="0.00">
                    <input type="hidden" name="diskon_header" id="diskon_header" value="0.00">
                    <input type="hidden" name="ppn" id="ppn" value="0.00">
                    <input type="hidden" name="diskon_persen" id="diskon_persen_hidden" value="0">
                    <input type="hidden" name="ppn_persen" id="ppn_persen_hidden" value="<?= esc((string) $default_ppn_persen) ?>">
                    <input type="hidden" name="catatan" value="POS">
                    <input type="hidden" name="jatuh_tempo" id="jatuh_tempo">
                    <input type="hidden" name="id_coa_kas_bank" id="id_coa_kas_bank" value="0">

                    <div id="pos-hidden-inputs"></div>
                </section>

                <div class="pos-action-row">
                    <button type="button" class="pos-btn pos-cancel" onclick="posResetCart()">
                        Batal
                    </button>

                    <button type="button" class="pos-btn pos-place" onclick="posOpenPaymentModal()">
                        Proses Pesanan
                    </button>
                </div>
            </div>
        </aside>
    </div>
</form>

<div class="pos-modal-backdrop" id="posPaymentModal">
    <div class="pos-modal-card">
        <div class="pos-modal-header">
            <h3>Pembayaran</h3>
            <button type="button" class="pos-modal-close" onclick="posClosePaymentModal()">×</button>
        </div>

        <div class="pos-modal-body">
            <div class="pos-modal-total">
                <span>Total Bayar</span>
                <strong id="modal_total_text">Rp 0.00</strong>
            </div>

            <div>
                <div class="pos-modal-label">Uang Diterima</div>
                <input
                    type="text"
                    class="pos-modal-input"
                    id="modal_uang_diterima"
                    value="0.00"
                    oninput="posHitungKembalian()"
                    onblur="posHitungKembalian()">
            </div>

            <div class="pos-modal-change">
                <span>Kembalian</span>
                <strong id="modal_kembalian_text">Rp 0.00</strong>
            </div>

            <div class="pos-transfer-box" id="posTransferBox">
                <div class="pos-modal-label">Referensi Transfer / Kode Cek <span class="text-danger">*</span></div>
                <input
                    type="text"
                    class="pos-modal-input"
                    name="no_referensi_transfer"
                    id="no_referensi_transfer"
                    form="form-pos"
                    maxlength="100"
                    placeholder="Contoh: 883421 / BRIVA123 / 6 digit akhir mutasi">
                <div class="pos-transfer-help">
                    Supaya antrian cepat, kasir cukup isi nomor referensi atau 4-6 digit akhir yang terlihat di mutasi/struk transfer pelanggan. Foto bukti boleh dikosongkan jika belum sempat.
                </div>

                <div class="pos-modal-label mt-2">Nama Pengirim / Bank Pengirim</div>
                <input
                    type="text"
                    class="pos-modal-input"
                    name="nama_pengirim_transfer"
                    id="nama_pengirim_transfer"
                    form="form-pos"
                    maxlength="150"
                    placeholder="Opsional, contoh: Budi / BCA">

                <div class="pos-modal-label mt-2">Upload Bukti Transfer</div>
                <input
                    type="file"
                    class="pos-modal-file"
                    name="bukti_bayar_pos"
                    id="bukti_bayar_pos"
                    form="form-pos"
                    accept="image/*,application/pdf">
                <div class="pos-transfer-help">
                    Opsional. Cocok untuk kondisi kasir sepi atau pelanggan menunjukkan file dari HP.
                </div>
            </div>

            <div class="text-muted small" id="modal_payment_note">
                Untuk pembayaran kredit, transaksi akan dicatat sebagai piutang.
            </div>
        </div>

        <div class="pos-modal-actions">
            <button type="button" class="pos-btn pos-cancel" onclick="posClosePaymentModal()">
                Batal
            </button>

            <button type="button" class="pos-btn pos-place" onclick="posSubmitFromModal()">
                Proses Transaksi
            </button>
        </div>
    </div>
</div>

<script>
const posProduk = <?= json_encode($produk_js, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const posAkunTemplate = <?= json_encode($akun_template, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const posPpnTemplate = <?= json_encode($ppn_template, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const posDefaultPpnPersen = <?= json_encode((float) $default_ppn_persen) ?>;
const posBarcodeLookupUrl = <?= json_encode(admin_url('menu/penjualan/kasir/cari_produk_barcode.php'), JSON_UNESCAPED_SLASHES) ?>;

let posFilter = 'semua';
let posCart = [];
let posScanTimer = null;
let posScanProcessing = false;
let posGlobalScanBuffer = '';
let posGlobalScanTimer = null;
let posScanQueue = [];
let posScanQueueWorking = false;
const posScanAutoDelay = 220;

function posParse(value) {
    value = String(value || '0').replace(/,/g, '').replace('Rp', '').trim();
    const n = parseFloat(value);
    return isNaN(n) ? 0 : n;
}

function posInt(value) {
    const n = parseInt(String(value || '0').replace(/,/g, ''), 10);
    return isNaN(n) ? 0 : n;
}

function posPercent(value) {
    let n = posInt(value);

    if (n < 0) n = 0;
    if (n > 100) n = 100;

    return n;
}

function posFormat(value, decimal = 2) {
    const n = parseFloat(value || 0);
    return n.toLocaleString('en-US', {
        minimumFractionDigits: decimal,
        maximumFractionDigits: decimal
    });
}

function posFormatInt(value) {
    const n = parseInt(value || 0, 10);
    return n.toLocaleString('en-US');
}

function posRupiah(value) {
    return 'Rp ' + posFormat(value, 2);
}

function posEscape(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function posNormalizeBarcode(value) {
    return String(value || '')
        .replace(/[\r\n\t]+/g, '')
        .replace(/\s+/g, '')
        .toUpperCase()
        .trim();
}

function posSplitBarcodeInput(value) {
    return String(value || '')
        .split(/[\r\n,;|]+/g)
        .map(function (item) {
            return posNormalizeBarcode(item);
        })
        .filter(function (item) {
            return item !== '';
        });
}

function posSleep(ms) {
    return new Promise(function (resolve) {
        setTimeout(resolve, ms);
    });
}

function posFocusBarcodeInput() {
    const input = document.getElementById('barcode_input');

    if (input) {
        input.focus();
    }
}

function posClearBarcodeInput(expectedValue = '') {
    const input = document.getElementById('barcode_input');

    if (!input) {
        return;
    }

    if (expectedValue === '' || posNormalizeBarcode(input.value) === posNormalizeBarcode(expectedValue)) {
        input.value = '';
    }

    input.focus();
}

function posQueueBarcodeScan(rawValue, tampilkanPeringatan = true) {
    const items = posSplitBarcodeInput(rawValue);

    if (items.length === 0) {
        return;
    }

    items.forEach(function (barcode) {
        posScanQueue.push({
            barcode: barcode,
            tampilkanPeringatan: tampilkanPeringatan
        });
    });

    if (items.length > 1) {
        posSetScanStatus(items.length + ' barcode masuk antrian scan.');
    }

    posProcessBarcodeQueue();
}

async function posProcessBarcodeQueue() {
    if (posScanQueueWorking) {
        return;
    }

    posScanQueueWorking = true;

    while (posScanQueue.length > 0) {
        const item = posScanQueue.shift();
        await posProsesBarcodeScan(item.barcode, item.tampilkanPeringatan);
        await posSleep(60);
    }

    posScanQueueWorking = false;
    posFocusBarcodeInput();
}

function posSetScanStatus(message, type = '') {
    const box = document.getElementById('pos_scan_status');

    if (!box) {
        return;
    }

    box.classList.remove('success', 'warning', 'danger');

    if (type) {
        box.classList.add(type);
    }

    const icon = type === 'success'
        ? 'bi-check-circle'
        : (type === 'danger' ? 'bi-x-circle' : (type === 'warning' ? 'bi-exclamation-triangle' : 'bi-upc-scan'));

    box.innerHTML = `<i class="bi ${icon}"></i><span>${posEscape(message)}</span>`;
}

function posGudang() {
    return parseInt(document.getElementById('id_gudang').value || '0', 10);
}

function posKeyword() {
    return String(document.getElementById('barcode_input').value || '').toLowerCase().trim();
}

function posProdukTampil() {
    const idGudang = posGudang();
    const keyword = posKeyword();

    return posProduk.filter(function (p) {
        if (!idGudang || parseInt(p.id_gudang, 10) !== idGudang) {
            return false;
        }

        if (posFilter === 'barang_jadi' && p.jenis_produk !== 'barang_jadi') {
            return false;
        }

        if (posFilter === 'setengah_jadi' && p.jenis_produk !== 'setengah_jadi') {
            return false;
        }

        if (posFilter === 'stok_tersedia' && parseFloat(p.qty_saldo || 0) <= 0) {
            return false;
        }

        if (posFilter.startsWith('kategori:')) {
            const kategori = posFilter.replace('kategori:', '');
            if (String(p.kategori) !== kategori) {
                return false;
            }
        }

        if (keyword !== '') {
            const haystack = String(
                p.kode_produk + ' ' +
                p.barcode_produk + ' ' +
                p.nama_produk + ' ' +
                p.kategori
            ).toLowerCase();

            if (!haystack.includes(keyword)) {
                return false;
            }
        }

        return true;
    });
}

function posRenderProduk() {
    const grid = document.getElementById('pos-product-grid');
    const countText = document.getElementById('produk_count_text');
    const rows = posProdukTampil();

    countText.textContent = rows.length + ' Produk';

    if (!posGudang()) {
        grid.innerHTML = `
            <div class="pos-product-empty">
                <div>
                    <div class="fs-1 mb-2"><i class="bi bi-shop"></i></div>
                    Pilih gudang terlebih dahulu.
                </div>
            </div>
        `;
        return;
    }

    if (rows.length === 0) {
        grid.innerHTML = `
            <div class="pos-product-empty">
                <div>
                    <div class="fs-1 mb-2"><i class="bi bi-search"></i></div>
                    Produk tidak ditemukan.
                </div>
            </div>
        `;
        return;
    }

    let html = '';

    rows.forEach(function (p) {
        const stok = Math.floor(parseFloat(p.qty_saldo || 0));
        const disabled = stok <= 0;
        const imgHtml = p.gambar_produk
            ? `<img src="${posEscape(p.gambar_produk)}" alt="">`
            : `<i class="bi bi-box-seam"></i>`;

        html += `
            <article class="pos-product-card ${disabled ? 'disabled' : ''}" onclick="${disabled ? '' : 'posTambahProduk(' + p.id_produk + ')'}">
                <div class="pos-product-img">${imgHtml}</div>
                <h4>${posEscape(p.nama_produk)}</h4>
                <div class="pos-product-meta">${posEscape(p.kode_produk)} · ${posEscape(p.barcode_produk || '-')} · ${posEscape(p.kategori)}</div>
                <div class="pos-product-foot">
                    <div class="pos-price">${posRupiah(p.harga_jual)}</div>
                </div>
                <div class="pos-product-foot">
                    <div class="pos-stock">Stok: ${posFormatInt(stok)} ${posEscape(p.nama_satuan)}</div>
                </div>
            </article>
        `;
    });

    grid.innerHTML = html;
}

function posCariProdukById(idProduk) {
    const idGudang = posGudang();

    return posProduk.find(function (p) {
        return parseInt(p.id_produk, 10) === parseInt(idProduk, 10)
            && parseInt(p.id_gudang, 10) === idGudang;
    });
}

function posCariProdukByBarcode(barcode) {
    const idGudang = posGudang();
    const barcodeNormal = posNormalizeBarcode(barcode);

    if (!barcodeNormal) {
        return null;
    }

    return posProduk.find(function (p) {
        return parseInt(p.id_gudang, 10) === idGudang
            && (
                posNormalizeBarcode(p.barcode_produk || '') === barcodeNormal ||
                posNormalizeBarcode(p.kode_produk || '') === barcodeNormal
            );
    }) || null;
}

function posSyncProdukFromLookup(produk) {
    if (!produk || !produk.id_produk || !produk.id_gudang) {
        return null;
    }

    const idProduk = parseInt(produk.id_produk, 10);
    const idGudang = parseInt(produk.id_gudang, 10);
    const index = posProduk.findIndex(function (p) {
        return parseInt(p.id_produk, 10) === idProduk
            && parseInt(p.id_gudang, 10) === idGudang;
    });

    const row = {
        id_gudang: idGudang,
        id_produk: idProduk,
        kode_produk: String(produk.kode_produk || ''),
        barcode_produk: String(produk.barcode_produk || ''),
        nama_produk: String(produk.nama_produk || ''),
        jenis_produk: String(produk.jenis_produk || ''),
        kategori: String(produk.kategori || 'Tanpa Kategori'),
        nama_satuan: String(produk.nama_satuan || ''),
        harga_jual: parseFloat(produk.harga_jual || 0),
        hpp_rata_rata: parseFloat(produk.hpp_rata_rata || 0),
        qty_saldo: parseFloat(produk.qty_saldo || 0),
        gambar_produk: String(produk.gambar_produk || ''),
        nama_gudang: String(produk.nama_gudang || '')
    };

    if (index >= 0) {
        posProduk[index] = Object.assign({}, posProduk[index], row);
        return posProduk[index];
    }

    posProduk.push(row);
    return row;
}

async function posLookupProdukBarcodeServer(barcode) {
    const idGudang = posGudang();

    if (!idGudang || !barcode) {
        return null;
    }

    const url = posBarcodeLookupUrl
        + '?id_gudang=' + encodeURIComponent(String(idGudang))
        + '&barcode=' + encodeURIComponent(String(barcode));

    try {
        const response = await fetch(url, {
            method: 'GET',
            headers: {'Accept': 'application/json'},
            credentials: 'same-origin'
        });

        const data = await response.json().catch(function () { return null; });

        if (!response.ok || !data || !data.success || !data.produk) {
            return null;
        }

        return posSyncProdukFromLookup(data.produk);
    } catch (error) {
        return null;
    }
}

function posBarcodeInputChanged() {
    posRenderProduk();

    if (posScanTimer) {
        clearTimeout(posScanTimer);
        posScanTimer = null;
    }

    const input = document.getElementById('barcode_input');
    const rawValue = input ? input.value : '';
    const items = posSplitBarcodeInput(rawValue);
    const barcode = items.length === 1 ? items[0] : '';

    if (!posGudang()) {
        return;
    }

    if (items.length > 1) {
        posScanTimer = setTimeout(function () {
            posClearBarcodeInput(rawValue);
            posRenderProduk();
            posQueueBarcodeScan(rawValue, false);
        }, posScanAutoDelay);
        return;
    }

    if (!barcode || barcode.length < 6) {
        return;
    }

    const produk = posCariProdukByBarcode(barcode);

    if (!produk) {
        return;
    }

    // Beberapa scanner, termasuk aplikasi scan via HP, kadang tidak mengirim suffix Enter/Tab.
    // Jika isi input cocok persis dengan barcode/kode produk, data masuk otomatis ke antrian scan.
    posScanTimer = setTimeout(function () {
        posClearBarcodeInput(barcode);
        posRenderProduk();
        posQueueBarcodeScan(barcode, false);
    }, posScanAutoDelay);
}

async function posProsesBarcodeScan(rawBarcode, tampilkanPeringatan = true) {
    if (posScanProcessing) {
        return;
    }

    const barcode = posNormalizeBarcode(rawBarcode);

    if (!posGudang()) {
        posSetScanStatus('Pilih gudang terlebih dahulu sebelum scan barcode.', 'warning');
        if (tampilkanPeringatan) alert('Pilih gudang terlebih dahulu.');
        return;
    }

    if (barcode === '') {
        return;
    }

    posScanProcessing = true;
    posSetScanStatus('Membaca barcode: ' + barcode + ' ...');

    let produk = posCariProdukByBarcode(barcode);

    if (!produk) {
        produk = await posLookupProdukBarcodeServer(barcode);
    }

    if (!produk) {
        const rows = posProdukTampil();

        if (rows.length === 1) {
            produk = rows[0];
        }
    }

    if (!produk) {
        posSetScanStatus('Barcode/kode produk tidak ditemukan: ' + barcode, 'danger');
        if (tampilkanPeringatan) alert('Barcode/kode produk tidak ditemukan.');
        posScanProcessing = false;
        return;
    }

    const stok = Math.floor(parseFloat(produk.qty_saldo || 0));

    if (stok <= 0) {
        posSetScanStatus('Produk ditemukan, tetapi stok kosong: ' + (produk.nama_produk || barcode), 'warning');
        if (tampilkanPeringatan) alert('Stok produk kosong.');
        posScanProcessing = false;
        return;
    }

    const berhasilDitambahkan = posTambahProduk(produk.id_produk);

    if (berhasilDitambahkan) {
        posSetScanStatus('Produk masuk keranjang: ' + (produk.nama_produk || produk.kode_produk || barcode), 'success');
        posClearBarcodeInput(barcode);
        posRenderProduk();
    } else {
        posSetScanStatus('Produk tidak bisa ditambahkan. Periksa stok/qty.', 'warning');
    }

    posScanProcessing = false;
}

function posBarcodeKeydown(event) {
    if (event.key !== 'Enter' && event.key !== 'Tab') {
        return;
    }

    const input = document.getElementById('barcode_input');
    const rawValue = input ? input.value : '';

    if (posSplitBarcodeInput(rawValue).length === 0) {
        return;
    }

    event.preventDefault();

    if (posScanTimer) {
        clearTimeout(posScanTimer);
        posScanTimer = null;
    }

    // Input dikosongkan sebelum proses async agar scan berikutnya dari Barcode to PC tidak menyatu dengan barcode sebelumnya.
    posClearBarcodeInput(rawValue);
    posRenderProduk();
    posQueueBarcodeScan(rawValue, true);
}


function posTambahProduk(idProduk) {
    if (!posGudang()) {
        alert('Pilih gudang terlebih dahulu.');
        return false;
    }

    const produk = posCariProdukById(idProduk);

    if (!produk) {
        alert('Produk tidak ditemukan di gudang terpilih.');
        return false;
    }

    const stok = Math.floor(parseFloat(produk.qty_saldo || 0));

    if (stok <= 0) {
        alert('Stok produk kosong.');
        return false;
    }

    const existing = posCart.find(function (item) {
        return parseInt(item.id_produk, 10) === parseInt(idProduk, 10);
    });

    if (existing) {
        if ((existing.qty + 1) > stok) {
            alert('Qty melebihi stok.');
            return false;
        }

        existing.qty += 1;
        existing.subtotal = Math.max(0, (existing.qty * existing.harga) - existing.diskon);
    } else {
        posCart.push({
            id_produk: parseInt(produk.id_produk, 10),
            kode_produk: produk.kode_produk,
            nama_produk: produk.nama_produk,
            barcode_produk: produk.barcode_produk || '',
            nama_satuan: produk.nama_satuan,
            gambar_produk: produk.gambar_produk,
            stok: stok,
            qty: 1,
            harga: parseFloat(produk.harga_jual || 0),
            diskon: 0,
            subtotal: parseFloat(produk.harga_jual || 0)
        });
    }

    posRenderCart();
    return true;
}

function posRenderCart() {
    const list = document.getElementById('pos-cart');

    if (posCart.length === 0) {
        list.innerHTML = `
            <div class="pos-empty-cart">
                <div>
                    <div class="fs-2 mb-2"><i class="bi bi-cart3"></i></div>
                    Keranjang kosong<br>
                    <small>Klik produk atau pindai barcode.</small>
                </div>
            </div>
        `;
        posRenderHiddenInputs();
        posHitungTotal();
        return;
    }

    let html = '';

    posCart.forEach(function (item, index) {
        html += `
            <div class="pos-cart-item">
                <div class="pos-cart-info">
                    <h4>${posEscape(item.nama_produk)}</h4>
                    <p>${posEscape(item.kode_produk)} · ${posEscape(item.barcode_produk || '-')} · ${posFormatInt(item.qty)} ${posEscape(item.nama_satuan)} × ${posRupiah(item.harga)}</p>
                </div>

                <div class="pos-cart-actions">
                    <div class="pos-qty">
                        <button type="button" onclick="posQtyMinus(${index})">−</button>
                        <input type="text" value="${posFormatInt(item.qty)}" onblur="posSetQty(${index}, this.value)">
                        <button type="button" onclick="posQtyPlus(${index})">+</button>
                    </div>

                    <div class="pos-cart-price">${posRupiah(item.subtotal)}</div>

                    <button type="button" class="pos-remove" onclick="posRemoveItem(${index})">×</button>
                </div>
            </div>
        `;
    });

    list.innerHTML = html;
    posRenderHiddenInputs();
    posHitungTotal();
}

function posQtyMinus(index) {
    if (!posCart[index]) return;

    posCart[index].qty -= 1;

    if (posCart[index].qty <= 0) {
        posCart.splice(index, 1);
    } else {
        posCart[index].subtotal = Math.max(0, (posCart[index].qty * posCart[index].harga) - posCart[index].diskon);
    }

    posRenderCart();
}

function posQtyPlus(index) {
    if (!posCart[index]) return;

    if ((posCart[index].qty + 1) > posCart[index].stok) {
        alert('Qty melebihi stok.');
        return;
    }

    posCart[index].qty += 1;
    posCart[index].subtotal = Math.max(0, (posCart[index].qty * posCart[index].harga) - posCart[index].diskon);
    posRenderCart();
}

function posSetQty(index, value) {
    if (!posCart[index]) return;

    let qty = posInt(value);

    if (qty <= 0) {
        qty = 1;
    }

    if (qty > posCart[index].stok) {
        alert('Qty melebihi stok.');
        qty = posCart[index].stok;
    }

    posCart[index].qty = qty;
    posCart[index].subtotal = Math.max(0, (posCart[index].qty * posCart[index].harga) - posCart[index].diskon);
    posRenderCart();
}

function posRemoveItem(index) {
    posCart.splice(index, 1);
    posRenderCart();
}

function posRenderHiddenInputs() {
    const wrap = document.getElementById('pos-hidden-inputs');
    let html = '';

    posCart.forEach(function (item) {
        html += `
            <input type="hidden" name="id_produk[]" value="${item.id_produk}">
            <input type="hidden" name="qty[]" value="${parseInt(item.qty, 10)}">
            <input type="hidden" name="harga[]" value="${item.harga.toFixed(2)}">
            <input type="hidden" name="diskon_detail[]" value="${item.diskon.toFixed(2)}">
            <input type="hidden" name="subtotal_detail[]" value="${item.subtotal.toFixed(2)}">
        `;
    });

    wrap.innerHTML = html;
}

function posHitungTotal() {
    let subtotal = 0;

    posCart.forEach(function (item) {
        subtotal += parseFloat(item.subtotal || 0);
    });

    const diskonPersenInput = document.getElementById('diskon_persen');
    const ppnPersenInput = document.getElementById('ppn_persen');

    let diskonPersen = posPercent(diskonPersenInput.value);
    let ppnPersen = posPercent(ppnPersenInput.value);

    diskonPersenInput.value = diskonPersen;
    ppnPersenInput.value = ppnPersen;

    const diskonNominal = Math.round((subtotal * diskonPersen / 100) * 100) / 100;
    const dasarPpn = Math.max(0, subtotal - diskonNominal);
    const ppnNominal = Math.round((dasarPpn * ppnPersen / 100) * 100) / 100;
    const total = Math.max(0, dasarPpn + ppnNominal);

    document.getElementById('subtotal').value = subtotal.toFixed(2);
    document.getElementById('total').value = total.toFixed(2);
    document.getElementById('diskon_header').value = diskonNominal.toFixed(2);
    document.getElementById('ppn').value = ppnNominal.toFixed(2);
    document.getElementById('diskon_persen_hidden').value = String(diskonPersen);
    document.getElementById('ppn_persen_hidden').value = String(ppnPersen);

    document.getElementById('subtotal_text').textContent = posRupiah(subtotal);
    document.getElementById('diskon_amount_text').textContent = posRupiah(diskonNominal);
    document.getElementById('ppn_amount_text').textContent = posRupiah(ppnNominal);
    document.getElementById('total_text').textContent = posRupiah(total);
}

function posResetCart() {
    if (posCart.length > 0 && !confirm('Kosongkan keranjang?')) {
        return;
    }

    posCart = [];
    posRenderCart();

    const input = document.getElementById('barcode_input');
    if (input) input.focus();
}

function posGantiGudang() {
    if (posCart.length > 0) {
        if (!confirm('Mengubah gudang akan mengosongkan keranjang. Lanjutkan?')) {
            return;
        }
    }

    posCart = [];
    posRenderCart();
    posRenderProduk();

    const input = document.getElementById('barcode_input');
    if (input) input.focus();
}

function posSetFilter(button) {
    document.querySelectorAll('.pos-category-btn').forEach(function (btn) {
        btn.classList.remove('active');
    });

    button.classList.add('active');
    posFilter = button.getAttribute('data-filter') || 'semua';
    posRenderProduk();
}

function posGantiPelanggan() {
    const pelanggan = document.getElementById('id_pelanggan');
    const opt = pelanggan.options[pelanggan.selectedIndex];

    if (!opt || !pelanggan.value) {
        document.getElementById('customer_id_text').textContent = 'Pelanggan belum dipilih';
    } else {
        const nama = opt.getAttribute('data-nama') || opt.textContent || '-';
        const kode = opt.getAttribute('data-kode') || pelanggan.value || '-';
        document.getElementById('customer_id_text').textContent = nama + ' · ' + kode;
    }

    posGantiPembayaran();
}

function posGantiPembayaran() {
    const jenis = document.getElementById('jenis_pembayaran').value;
    const akun = posAkunTemplate[jenis] || {id_coa: 0, kode_coa: '-', nama_coa: '-'};
    const hasPpn = !!posPpnTemplate[jenis];

    document.getElementById('id_coa_kas_bank').value = akun.id_coa || 0;

    let label = 'Template akun: ' + (akun.kode_coa || '-') + ' - ' + (akun.nama_coa || '-');

    if (jenis === 'kredit') {
        label += ' · Piutang';
    }

    label += hasPpn ? ' · PPN aktif dari template jurnal' : ' · PPN tidak ada di template';

    document.getElementById('template_account_info').textContent = label;

    const ppnInput = document.getElementById('ppn_persen');
    const ppnLabel = document.getElementById('ppn_label');

    if (hasPpn) {
        if (posPercent(ppnInput.value) === 0) {
            ppnInput.value = posDefaultPpnPersen;
        }

        ppnInput.readOnly = false;
        ppnLabel.textContent = 'PPN';
    } else {
        ppnInput.value = 0;
        ppnInput.readOnly = true;
        ppnLabel.textContent = 'PPN Off';
    }

    const jatuhTempoInput = document.getElementById('jatuh_tempo');
    const jatuhTempoText = document.getElementById('jatuh_tempo_text');

    if (jenis === 'kredit') {
        const pelanggan = document.getElementById('id_pelanggan');
        const opt = pelanggan.options[pelanggan.selectedIndex];
        const tempo = opt ? parseInt(opt.getAttribute('data-tempo') || '0', 10) : 0;

        const d = new Date();
        d.setDate(d.getDate() + Math.max(tempo, 1));

        jatuhTempoInput.value = d.toISOString().slice(0, 10);
        jatuhTempoText.style.display = '';
        jatuhTempoText.querySelector('strong').textContent = jatuhTempoInput.value;
    } else {
        jatuhTempoInput.value = '';
        jatuhTempoText.style.display = 'none';
        jatuhTempoText.querySelector('strong').textContent = '-';
    }

    posHitungTotal();
}

function posValidateBeforePayment() {
    if (!posGudang()) {
        alert('Pilih gudang terlebih dahulu.');
        return false;
    }

    if (posCart.length === 0) {
        alert('Keranjang masih kosong.');
        return false;
    }

    if (!document.getElementById('id_pelanggan').value) {
        alert('Pilih pelanggan terlebih dahulu.');
        return false;
    }

    const total = posParse(document.getElementById('total').value);

    if (total <= 0) {
        alert('Total transaksi harus lebih dari nol.');
        return false;
    }

    return true;
}

function posOpenPaymentModal() {
    if (!posValidateBeforePayment()) {
        return;
    }

    const jenis = document.getElementById('jenis_pembayaran').value;
    const total = posParse(document.getElementById('total').value);

    document.getElementById('modal_total_text').textContent = posRupiah(total);

    const transferBox = document.getElementById('posTransferBox');

    if (jenis === 'kredit') {
        document.getElementById('modal_uang_diterima').value = '0.00';
        document.getElementById('modal_uang_diterima').readOnly = true;
        document.getElementById('modal_payment_note').textContent = 'Pembayaran kredit akan dicatat sebagai piutang. Tidak ada uang diterima saat ini.';
        transferBox.classList.remove('show');
    } else if (jenis === 'transfer') {
        document.getElementById('modal_uang_diterima').value = posFormat(total, 2);
        document.getElementById('modal_uang_diterima').readOnly = true;
        document.getElementById('modal_payment_note').textContent = 'Transfer POS: cukup input referensi/kode cek mutasi. Upload bukti bersifat opsional agar kasir tetap cepat.';
        transferBox.classList.add('show');
    } else {
        document.getElementById('modal_uang_diterima').value = posFormat(total, 2);
        document.getElementById('modal_uang_diterima').readOnly = false;
        document.getElementById('modal_payment_note').textContent = 'Isi uang diterima untuk menghitung kembalian.';
        transferBox.classList.remove('show');
    }

    posHitungKembalian();

    document.getElementById('posPaymentModal').classList.add('show');

    setTimeout(function () {
        const input = jenis === 'transfer' ? document.getElementById('no_referensi_transfer') : document.getElementById('modal_uang_diterima');
        input.focus();
        if (typeof input.select === 'function') {
            input.select();
        }
    }, 100);
}

function posClosePaymentModal() {
    document.getElementById('posPaymentModal').classList.remove('show');
}

function posHitungKembalian() {
    const jenis = document.getElementById('jenis_pembayaran').value;
    const total = posParse(document.getElementById('total').value);

    let diterima = posParse(document.getElementById('modal_uang_diterima').value);

    if (jenis === 'kredit') {
        diterima = 0;
    }

    const kembali = Math.max(0, diterima - total);

    document.getElementById('modal_kembalian_text').textContent = posRupiah(kembali);
    document.getElementById('jumlah_dibayar').value = diterima.toFixed(2);
    document.getElementById('kembalian').value = kembali.toFixed(2);
}

function posSubmitFromModal() {
    const jenis = document.getElementById('jenis_pembayaran').value;
    const total = posParse(document.getElementById('total').value);
    const diterima = posParse(document.getElementById('modal_uang_diterima').value);

    if (jenis !== 'kredit' && diterima < total) {
        alert('Uang diterima kurang dari total transaksi.');
        return;
    }

    if (jenis === 'transfer') {
        const ref = document.getElementById('no_referensi_transfer').value.trim();
        if (ref.length < 4) {
            alert('Isi nomor referensi transfer atau minimal 4 digit akhir mutasi agar pembayaran mudah dicek.');
            document.getElementById('no_referensi_transfer').focus();
            return;
        }
    }

    posHitungKembalian();

    if (!confirm('Simpan dan posting transaksi POS ini?')) {
        return;
    }

    document.getElementById('form-pos').submit();
}

document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        posClosePaymentModal();
        return;
    }

    const modalOpen = document.getElementById('posPaymentModal').classList.contains('show');

    if (modalOpen) {
        return;
    }

    const target = event.target;
    const tag = target && target.tagName ? target.tagName.toLowerCase() : '';
    const fromEditable = tag === 'input' || tag === 'select' || tag === 'textarea' || (target && target.isContentEditable);

    if (target && target.id === 'barcode_input') {
        return;
    }

    // Scanner model keyboard-wedge tetap bisa dibaca walaupun fokus sedang berada di area kosong halaman kasir.
    if (!fromEditable && event.key.length === 1 && !event.ctrlKey && !event.altKey && !event.metaKey) {
        posGlobalScanBuffer += event.key;

        if (posGlobalScanTimer) {
            clearTimeout(posGlobalScanTimer);
        }

        posGlobalScanTimer = setTimeout(function () {
            posGlobalScanBuffer = '';
        }, 450);
        return;
    }

    if (!fromEditable && (event.key === 'Enter' || event.key === 'Tab') && posNormalizeBarcode(posGlobalScanBuffer) !== '') {
        event.preventDefault();
        const barcode = posGlobalScanBuffer;
        posGlobalScanBuffer = '';
        if (posGlobalScanTimer) {
            clearTimeout(posGlobalScanTimer);
            posGlobalScanTimer = null;
        }
        posQueueBarcodeScan(barcode, true);
    }
});

document.addEventListener('DOMContentLoaded', function () {
    posRenderProduk();
    posRenderCart();
    posGantiPelanggan();
    posGantiPembayaran();

    setTimeout(function () {
        const input = document.getElementById('barcode_input');
        if (input) input.focus();
    }, 200);
});

function posGantiSumberPos(){ const v=document.getElementById('sumber_pos_select')?.value||'toko'; const info=document.getElementById('sumber_pos_info'); if(info){ info.style.display = v==='aplikasi_lain' ? 'block' : 'none'; } }
document.addEventListener('DOMContentLoaded', posGantiSumberPos);
</script>