<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';
require_once __DIR__ . '/../_fungsi_penjualan.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

$user_login = user_login();
$id_entitas = (int) ($user_login['id_entitas'] ?? 0);
$id_pengguna = (int) ($user_login['id_pengguna'] ?? 0);
$id_faktur_penjualan = (int) ($_GET['id'] ?? 0);

if ($id_faktur_penjualan <= 0) {
    echo 'ID faktur POS tidak valid.';
    exit;
}

if (!function_exists('struk_esc')) {
    function struk_esc($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('struk_uang')) {
    function struk_uang($value): string
    {
        return number_format((float) $value, 0, ',', '.');
    }
}

if (!function_exists('struk_qty')) {
    function struk_qty($value): string
    {
        $value = (float) $value;

        if (abs($value - round($value)) < 0.000001) {
            return number_format($value, 0, ',', '.');
        }

        return rtrim(rtrim(number_format($value, 3, ',', '.'), '0'), ',');
    }
}

if (!function_exists('struk_tanggal_jam')) {
    function struk_tanggal_jam($tanggal): string
    {
        if (empty($tanggal)) {
            return date('d/m/Y H:i');
        }

        return date('d/m/Y H:i', strtotime((string) $tanggal));
    }
}

if (!function_exists('struk_potong')) {
    function struk_potong(string $text, int $max): string
    {
        $text = trim($text);

        if (mb_strlen($text) <= $max) {
            return $text;
        }

        return mb_substr($text, 0, $max - 1) . '…';
    }
}

$selectFakturPos = [
    'f.*',
    'p.kode_pelanggan',
    'p.nama_pelanggan',
    'p.no_hp as no_hp_pelanggan',
    'p.alamat as alamat_pelanggan',
    'e.nama_entitas',
    'e.alamat',
    'e.no_hp',
    'e.email',
    'pn.no_penyerahan_penjualan',
    'u.nama_lengkap as nama_kasir',
];
if (Capsule::schema()->hasColumn('tb_entitas', 'logo_umkm')) {
    $selectFakturPos[] = 'e.logo_umkm';
}

$faktur = Capsule::table('tb_faktur_penjualan as f')
    ->leftJoin('tb_pelanggan as p', 'p.id_pelanggan', '=', 'f.id_pelanggan')
    ->leftJoin('tb_entitas as e', 'e.id_entitas', '=', 'f.id_entitas')
    ->leftJoin('tb_penyerahan_penjualan as pn', 'pn.id_penyerahan_penjualan', '=', 'f.id_penyerahan_penjualan')
    ->leftJoin('tb_pengguna as u', 'u.id_pengguna', '=', 'f.dibuat_oleh')
    ->where('f.id_entitas', $id_entitas)
    ->where('f.id_faktur_penjualan', $id_faktur_penjualan)
    ->select($selectFakturPos)
    ->first();

if (!$faktur) {
    echo 'Data faktur POS tidak ditemukan.';
    exit;
}

$detail_rows = Capsule::table('tb_faktur_penjualan_detail as d')
    ->leftJoin('tb_produk as p', 'p.id_produk', '=', 'd.id_produk')
    ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'p.id_satuan')
    ->where('d.id_faktur_penjualan', $id_faktur_penjualan)
    ->select([
        'd.*',
        'p.kode_produk',
        'p.barcode_produk',
        'p.nama_produk',
        's.nama_satuan',
    ])
    ->orderBy('d.id_faktur_penjualan_detail', 'asc')
    ->get();

$pembayaran_rows = Capsule::table('tb_pembayaran_penjualan as pp')
    ->leftJoin('tb_coa as c', 'c.id_coa', '=', 'pp.id_coa_kas_bank')
    ->where('pp.id_entitas', $id_entitas)
    ->where('pp.id_faktur_penjualan', $id_faktur_penjualan)
    ->where('pp.status_posting', '<>', 'batal')
    ->select([
        'pp.*',
        'c.kode_coa',
        'c.nama_coa',
    ])
    ->orderBy('pp.id_pembayaran_penjualan', 'asc')
    ->get();

$total_qty = 0.0;
$total_item = 0;
$total_diskon_item = 0.0;

foreach ($detail_rows as $row) {
    $total_qty += (float) ($row->qty ?? 0);
    $total_item++;
    $total_diskon_item += (float) ($row->diskon ?? 0);
}

$total_bayar = 0.0;

foreach ($pembayaran_rows as $p) {
    $total_bayar += (float) ($p->jumlah_bayar ?? 0);
}

if ($total_bayar <= 0 && in_array((string) ($faktur->jenis_pembayaran ?? ''), ['tunai', 'transfer'], true)) {
    $total_bayar = (float) ($faktur->total ?? 0);
}

$kembalian = max(0, $total_bayar - (float) ($faktur->total ?? 0));
$kurang_bayar = max(0, (float) ($faktur->total ?? 0) - $total_bayar);

$kasir = (string) ($faktur->nama_kasir ?? '');
if ($kasir === '') {
    $kasir = (string) ($user_login['nama_lengkap'] ?? $user_login['username'] ?? '-');
}

$nama_entitas = (string) ($faktur->nama_entitas ?? 'Nama Entitas');
$alamat_entitas = (string) ($faktur->alamat ?? '-');
$telp_entitas = (string) ($faktur->no_hp ?? '-');
$email_entitas = (string) ($faktur->email ?? '');
$logo_umkm = (string) ($faktur->logo_umkm ?? '');

$nama_pelanggan = (string) ($faktur->nama_pelanggan ?? '');
if ($nama_pelanggan === '') {
    $nama_pelanggan = 'UMUM';
}

$no_faktur = (string) ($faktur->no_faktur_penjualan ?? '-');
$tanggal_struk = struk_tanggal_jam($faktur->tanggal_posting ?? $faktur->tanggal_dibuat ?? $faktur->tanggal_faktur ?? null);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Struk POS - <?= struk_esc($no_faktur) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
    :root {
        --receipt-width: 58mm;
        --receipt-padding-screen: 2mm;
        --receipt-padding-print: 1.5mm;
    }

    * {
        box-sizing: border-box;
    }

    html,
    body {
        margin: 0;
        padding: 0;
        background: #f3f4f6;
        color: #111;
        font-family: "Courier New", Courier, monospace;
        font-size: 10px;
        line-height: 1.22;
    }

    .toolbar {
        width: var(--receipt-width);
        margin: 12px auto;
        display: flex;
        justify-content: center;
        gap: 6px;
        font-family: Arial, Helvetica, sans-serif;
    }

    .toolbar button,
    .toolbar a {
        border: 1px solid #333;
        background: #fff;
        color: #111;
        border-radius: 4px;
        padding: 7px 10px;
        font-size: 12px;
        line-height: 1;
        text-decoration: none;
        cursor: pointer;
    }

    .toolbar button.primary {
        background: #111827;
        border-color: #111827;
        color: #fff;
    }

    .receipt-wrap {
        width: var(--receipt-width);
        min-width: var(--receipt-width);
        max-width: var(--receipt-width);
        margin: 0 auto 24px;
        background: #fff;
        padding: var(--receipt-padding-screen);
        box-shadow: 0 8px 24px rgba(15, 23, 42, .12);
    }

    .center {
        text-align: center;
    }

    .right {
        text-align: right;
    }

    .bold {
        font-weight: bold;
    }

    .store-name {
        font-size: 13px;
        font-weight: bold;
        letter-spacing: .4px;
        text-transform: uppercase;
    }

    .store-info {
        margin-top: 2px;
        word-break: break-word;
    }

    .line {
        border-top: 1px dashed #111;
        margin: 7px 0;
    }

    .line-solid {
        border-top: 1px solid #111;
        margin: 7px 0;
    }

    .row {
        display: flex;
        justify-content: space-between;
        gap: 8px;
    }

    .row .label {
        flex: 0 0 auto;
    }

    .row .value {
        flex: 1;
        text-align: right;
        word-break: break-word;
    }

    .meta {
        margin-top: 1px;
    }

    .items {
        width: 100%;
        border-collapse: collapse;
    }

    .items td {
        padding: 1px 0;
        vertical-align: top;
    }

    .product-name {
        font-weight: bold;
        text-transform: uppercase;
        word-break: break-word;
    }

    .product-code {
        color: #333;
        font-size: 10px;
    }

    .item-calc {
        padding-left: 0;
        white-space: nowrap;
    }

    .item-total {
        text-align: right;
        white-space: nowrap;
    }

    .discount {
        color: #111;
    }

    .summary-row {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 8px;
        margin: 2px 0;
    }

    .summary-row .amount {
        text-align: right;
        white-space: nowrap;
    }

    .grand-total {
        font-size: 13px;
        font-weight: bold;
        padding-top: 2px;
    }

    .payment-box {
        margin-top: 2px;
    }

    .footer {
        margin-top: 8px;
        text-align: center;
    }

    .barcode-text {
        margin-top: 8px;
        text-align: center;
        letter-spacing: 2px;
        font-size: 18px;
        font-weight: 900;
        letter-spacing: 1px;
        word-break: break-word;
    }

    .logo-umkm {
        max-width: 17mm;
        max-height: 17mm;
        object-fit: contain;
        display: block;
        margin: 0 auto 3px;
    }

    .small {
        font-size: 10px;
    }

    .thanks {
        font-weight: bold;
        font-size: 12px;
        margin-top: 6px;
    }

    @media print {
            html,
            body {
                width: var(--receipt-width);
                min-width: var(--receipt-width);
                max-width: var(--receipt-width);
                background: #fff;
                margin: 0;
                padding: 0;
                overflow: visible;
            }

            .toolbar {
                display: none !important;
            }

            .receipt-wrap {
                width: var(--receipt-width);
                min-width: var(--receipt-width);
                max-width: var(--receipt-width);
                margin: 0;
                padding: var(--receipt-padding-print);
                box-shadow: none;
                page-break-inside: avoid;
            }

            @page {
                size: 58mm auto;
                margin: 0;
            }
        }
</style>
</head>
<body>

<div class="toolbar">
    <button type="button" class="primary" onclick="window.print()">Print</button>
    <a href="<?= struk_esc(admin_page_url('penjualan/kasir')) ?>">Kasir</a>
    <a href="<?= struk_esc(admin_page_url('penjualan/kasir/detail') . '&id=' . (int) $faktur->id_faktur_penjualan) ?>">Detail</a>
</div>

<div class="receipt-wrap">
    <div class="center">
        <?php if ($logo_umkm !== ''): ?>
            <img class="logo-umkm" src="<?= struk_esc(base_url($logo_umkm)) ?>" alt="Logo UMKM">
        <?php endif; ?>
        <div class="store-name"><?= struk_esc($nama_entitas) ?></div>
        <div class="store-info"><?= nl2br(struk_esc($alamat_entitas)) ?></div>
        <div class="store-info">TELP: <?= struk_esc($telp_entitas) ?></div>
        <?php if ($email_entitas !== ''): ?>
            <div class="store-info small"><?= struk_esc($email_entitas) ?></div>
        <?php endif; ?>
    </div>

    <div class="line"></div>

    <div class="meta">
        <div class="row">
            <div class="label">NO</div>
            <div class="value"><?= struk_esc($no_faktur) ?></div>
        </div>
        <div class="row">
            <div class="label">TGL</div>
            <div class="value"><?= struk_esc($tanggal_struk) ?></div>
        </div>
        <div class="row">
            <div class="label">KASIR</div>
            <div class="value"><?= struk_esc(struk_potong($kasir, 28)) ?></div>
        </div>
        <div class="row">
            <div class="label">CUSTOMER</div>
            <div class="value"><?= struk_esc(struk_potong($nama_pelanggan, 28)) ?></div>
        </div>
        <div class="row">
            <div class="label">BAYAR</div>
            <div class="value"><?= struk_esc(strtoupper((string) ($faktur->jenis_pembayaran ?? '-'))) ?></div>
        </div>
    </div>

    <div class="line-solid"></div>

    <table class="items">
        <tbody>
            <?php if ($detail_rows->count() === 0): ?>
                <tr>
                    <td class="center">Tidak ada item.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($detail_rows as $d): ?>
                    <?php
                    $nama_produk = (string) ($d->nama_produk ?? '-');
                    $kode_produk = (string) ($d->kode_produk ?? '');
                    $barcode_produk = (string) ($d->barcode_produk ?? '');
                    $qty = (float) ($d->qty ?? 0);
                    $satuan = (string) ($d->nama_satuan ?? '');
                    $harga = (float) ($d->harga ?? 0);
                    $diskon = (float) ($d->diskon ?? 0);
                    $subtotal = (float) ($d->subtotal ?? 0);
                    ?>
                    <tr>
                        <td colspan="2">
                            <div class="product-name"><?= struk_esc($nama_produk) ?></div>
                            <?php if ($kode_produk !== ''): ?>
                                <div class="product-code"><?= struk_esc($kode_produk) ?></div>
                            <?php endif; ?>
                            <?php if ($barcode_produk !== ''): ?>
                                <div class="product-code">Barcode: <?= struk_esc($barcode_produk) ?></div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="item-calc">
                            <?= struk_esc(struk_qty($qty)) ?>
                            <?= struk_esc($satuan) ?>
                            x
                            <?= struk_esc(struk_uang($harga)) ?>
                        </td>
                        <td class="item-total"><?= struk_esc(struk_uang($subtotal + $diskon)) ?></td>
                    </tr>
                    <?php if ($diskon > 0): ?>
                        <tr>
                            <td class="discount">DISC ITEM</td>
                            <td class="item-total discount">-<?= struk_esc(struk_uang($diskon)) ?></td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="line-solid"></div>

    <div class="summary-row">
        <div>TOTAL ITEM</div>
        <div class="amount"><?= struk_esc((string) $total_item) ?></div>
    </div>
    <div class="summary-row">
        <div>TOTAL QTY</div>
        <div class="amount"><?= struk_esc(struk_qty($total_qty)) ?></div>
    </div>
    <div class="summary-row">
        <div>SUBTOTAL</div>
        <div class="amount"><?= struk_esc(struk_uang($faktur->subtotal ?? 0)) ?></div>
    </div>

    <?php if ((float) ($faktur->diskon ?? 0) > 0): ?>
        <div class="summary-row">
            <div>DISKON</div>
            <div class="amount">-<?= struk_esc(struk_uang($faktur->diskon ?? 0)) ?></div>
        </div>
    <?php endif; ?>

    <?php if ((float) ($faktur->ppn ?? 0) > 0): ?>
        <div class="summary-row">
            <div>PPN</div>
            <div class="amount"><?= struk_esc(struk_uang($faktur->ppn ?? 0)) ?></div>
        </div>
    <?php endif; ?>

    <div class="line"></div>

    <div class="summary-row grand-total">
        <div>TOTAL</div>
        <div class="amount"><?= struk_esc(struk_uang($faktur->total ?? 0)) ?></div>
    </div>

    <div class="line"></div>

    <div class="payment-box">
        <?php if ($pembayaran_rows->count() > 0): ?>
            <?php foreach ($pembayaran_rows as $p): ?>
                <div class="summary-row">
                    <div><?= struk_esc(strtoupper((string) ($p->metode_pembayaran ?? 'BAYAR'))) ?></div>
                    <div class="amount"><?= struk_esc(struk_uang($p->jumlah_bayar ?? 0)) ?></div>
                </div>
                <?php if (!empty($p->nama_coa)): ?>
                    <div class="summary-row small">
                        <div>AKUN</div>
                        <div class="amount"><?= struk_esc(struk_potong((string) $p->nama_coa, 24)) ?></div>
                    </div>
                <?php endif; ?>
                <?php if ((string) ($p->metode_pembayaran ?? '') === 'transfer' && !empty($p->no_referensi_transfer)): ?>
                    <div class="summary-row small">
                        <div>REF</div>
                        <div class="amount"><?= struk_esc(struk_potong((string) $p->no_referensi_transfer, 24)) ?></div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="summary-row">
                <div><?= struk_esc(strtoupper((string) ($faktur->jenis_pembayaran ?? 'BAYAR'))) ?></div>
                <div class="amount"><?= struk_esc(struk_uang($total_bayar)) ?></div>
            </div>
        <?php endif; ?>

        <div class="summary-row">
            <div>DIBAYAR</div>
            <div class="amount"><?= struk_esc(struk_uang($total_bayar)) ?></div>
        </div>

        <?php if ($kurang_bayar > 0): ?>
            <div class="summary-row bold">
                <div>HUTANG</div>
                <div class="amount"><?= struk_esc(struk_uang($kurang_bayar)) ?></div>
            </div>
        <?php else: ?>
            <div class="summary-row">
                <div>KEMBALI</div>
                <div class="amount"><?= struk_esc(struk_uang($kembalian)) ?></div>
            </div>
        <?php endif; ?>
    </div>

    <div class="line"></div>

    <?php if (!empty($faktur->no_penyerahan_penjualan)): ?>
        <div class="row small">
            <div class="label">SURAT JALAN</div>
            <div class="value"><?= struk_esc((string) $faktur->no_penyerahan_penjualan) ?></div>
        </div>
    <?php endif; ?>

    <div class="row small">
        <div class="label">STATUS</div>
        <div class="value"><?= struk_esc(strtoupper((string) ($faktur->status_faktur ?? '-'))) ?></div>
    </div>

    <div class="barcode-text"><?= struk_esc($no_faktur) ?></div>

    <div class="footer">
        <div class="thanks">TERIMA KASIH</div>
        <div>Selamat berbelanja kembali</div>
        <div class="line"></div>
        <div class="small">
            Barang yang sudah dibeli tidak dapat ditukar/dikembalikan kecuali ada perjanjian sebelumnya.
        </div>
        <div class="small">
            Struk ini adalah bukti pembayaran yang sah.
        </div>
    </div>
</div>

<script>
window.addEventListener('load', function () {
    setTimeout(function () {
        window.print();
    }, 300);
});
</script>

</body>
</html>