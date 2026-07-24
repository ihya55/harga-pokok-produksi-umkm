<?php
declare(strict_types=1);

require_once __DIR__ . '/_helper.php';

use Illuminate\Database\Capsule\Manager as Capsule;

$id_entitas = po_id_entitas();
$id = (int) ($_GET['id'] ?? 0);
$no = trim((string) ($_GET['no'] ?? ''));
$entitas = po_entitas($id_entitas);

$q = Capsule::table('tb_pesanan_penjualan as pp')
    ->leftJoin('tb_pelanggan as p', 'p.id_pelanggan', '=', 'pp.id_pelanggan')
    ->where('pp.id_entitas', $id_entitas);

if ($id > 0) {
    $q->where('pp.id_pesanan_penjualan', $id);
} elseif ($no !== '') {
    $q->where('pp.no_pesanan_penjualan', $no);
} else {
    $q->whereRaw('1=0');
}

$pesanan = $q->select(['pp.*', 'p.nama_pelanggan', 'p.no_hp', 'p.alamat'])->first();

if (!$pesanan) {
    http_response_code(404);
    echo 'Pesanan tidak ditemukan.';
    exit;
}

if (!function_exists('imagecreatetruecolor')) {
    header('Location: ' . po_url('cetak.php?id=' . (int) $pesanan->id_pesanan_penjualan . '&entitas=' . (int) $id_entitas));
    exit;
}

$noPesanan = trim((string) ($pesanan->no_pesanan_penjualan ?? ''));
$namaUmkm = trim((string) ($entitas->nama_entitas ?? 'UMKM')) ?: 'UMKM';
$namaPelanggan = trim((string) ($pesanan->nama_pelanggan ?? '-')) ?: '-';
$noHp = trim((string) ($pesanan->no_hp ?? '-')) ?: '-';
$noHpUmkm = trim((string) ($entitas->no_hp ?? ''));
if ($noHpUmkm === '') {
    $noHpUmkm = '-';
}

function pg_len(string $text): int
{
    return function_exists('mb_strlen') ? (int) mb_strlen($text) : strlen($text);
}

function pg_sub(string $text, int $start, int $length): string
{
    return function_exists('mb_substr') ? (string) mb_substr($text, $start, $length) : substr($text, $start, $length);
}

function pg_cut(string $text, int $max): string
{
    $text = trim($text);
    if ($text === '') {
        return '-';
    }
    return pg_len($text) > $max ? pg_sub($text, 0, max(1, $max - 3)) . '...' : $text;
}

function pg_color($img, int $r, int $g, int $b, int $a = 0): int
{
    return imagecolorallocatealpha($img, $r, $g, $b, $a);
}

function pg_font(string $type = 'regular'): ?string
{
    $type = strtolower($type);
    $regular = [
        __DIR__ . '/../assets/fonts/Poppins-Regular.ttf',
        __DIR__ . '/../assets/fonts/Inter-Regular.ttf',
        __DIR__ . '/../assets/fonts/Arial.ttf',
        'C:/Windows/Fonts/segoeui.ttf',
        'C:/Windows/Fonts/arial.ttf',
        'C:/Windows/Fonts/calibri.ttf',
        '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
        '/usr/share/fonts/truetype/liberation2/LiberationSans-Regular.ttf',
    ];
    $bold = [
        __DIR__ . '/../assets/fonts/Poppins-Bold.ttf',
        __DIR__ . '/../assets/fonts/Inter-Bold.ttf',
        __DIR__ . '/../assets/fonts/Arial-Bold.ttf',
        'C:/Windows/Fonts/arialbd.ttf',
        'C:/Windows/Fonts/seguisb.ttf',
        'C:/Windows/Fonts/calibrib.ttf',
        '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
        '/usr/share/fonts/truetype/liberation2/LiberationSans-Bold.ttf',
    ];
    foreach ($type === 'bold' ? $bold : $regular as $font) {
        if (is_file($font)) {
            return $font;
        }
    }
    return null;
}

function pg_ttf_width(string $text, int $size, ?string $font): int
{
    if (!$font || !function_exists('imagettfbbox')) {
        return strlen($text) * $size;
    }
    $box = imagettfbbox($size, 0, $font, $text);
    return is_array($box) ? abs((int) $box[2] - (int) $box[0]) : strlen($text) * $size;
}

function pg_fit_size(string $text, int $maxWidth, int $startSize, int $minSize, string $type = 'bold'): int
{
    $font = pg_font($type);
    for ($size = $startSize; $size >= $minSize; $size--) {
        if (pg_ttf_width($text, $size, $font) <= $maxWidth) {
            return $size;
        }
    }
    return $minSize;
}

function pg_text_builtin($img, string $text, int $x, int $y, int $color, int $size = 22, string $align = 'left'): void
{
    $font = 5;
    $scale = max(2, (int) round($size / 10));
    $srcW = max(1, imagefontwidth($font) * max(1, strlen($text)) + 6);
    $srcH = max(1, imagefontheight($font) + 6);

    $tmp = imagecreatetruecolor($srcW, $srcH);
    imagesavealpha($tmp, true);
    imagealphablending($tmp, false);
    $trans = imagecolorallocatealpha($tmp, 0, 0, 0, 127);
    imagefill($tmp, 0, 0, $trans);
    imagealphablending($tmp, true);

    $rgba = imagecolorsforindex($img, $color);
    $tmpColor = imagecolorallocatealpha($tmp, $rgba['red'], $rgba['green'], $rgba['blue'], 0);
    imagestring($tmp, $font, 3, 3, $text, $tmpColor);

    $dw = $srcW * $scale;
    $dh = $srcH * $scale;
    if ($align === 'center') {
        $x -= (int) round($dw / 2);
    } elseif ($align === 'right') {
        $x -= $dw;
    }
    imagecopyresampled($img, $tmp, $x, $y - $dh, 0, 0, $dw, $dh, $srcW, $srcH);
    imagedestroy($tmp);
}

function pg_text($img, string $text, int $x, int $y, int $color, int $size = 24, string $type = 'regular', string $align = 'left', ?int $shadowColor = null): void
{
    $font = pg_font($type);
    if ($font && function_exists('imagettftext')) {
        $width = pg_ttf_width($text, $size, $font);
        if ($align === 'center') {
            $x -= (int) round($width / 2);
        } elseif ($align === 'right') {
            $x -= $width;
        }
        if ($shadowColor !== null) {
            imagettftext($img, $size, 0, $x + 2, $y + 3, $shadowColor, $font, $text);
        }
        imagettftext($img, $size, 0, $x, $y, $color, $font, $text);
        return;
    }
    pg_text_builtin($img, $text, $x, $y, $color, $size, $align);
}

function pg_fill_round_rect($img, int $x1, int $y1, int $x2, int $y2, int $r, int $color): void
{
    $r = max(0, min($r, (int) floor(($x2 - $x1) / 2), (int) floor(($y2 - $y1) / 2)));
    imagefilledrectangle($img, $x1 + $r, $y1, $x2 - $r, $y2, $color);
    imagefilledrectangle($img, $x1, $y1 + $r, $x2, $y2 - $r, $color);
    imagefilledellipse($img, $x1 + $r, $y1 + $r, $r * 2, $r * 2, $color);
    imagefilledellipse($img, $x2 - $r, $y1 + $r, $r * 2, $r * 2, $color);
    imagefilledellipse($img, $x1 + $r, $y2 - $r, $r * 2, $r * 2, $color);
    imagefilledellipse($img, $x2 - $r, $y2 - $r, $r * 2, $r * 2, $color);
}

function pg_round_border($img, int $x1, int $y1, int $x2, int $y2, int $r, int $color, int $thick = 1): void
{
    imagesetthickness($img, max(1, $thick));
    imageline($img, $x1 + $r, $y1, $x2 - $r, $y1, $color);
    imageline($img, $x1 + $r, $y2, $x2 - $r, $y2, $color);
    imageline($img, $x1, $y1 + $r, $x1, $y2 - $r, $color);
    imageline($img, $x2, $y1 + $r, $x2, $y2 - $r, $color);
    imagearc($img, $x1 + $r, $y1 + $r, $r * 2, $r * 2, 180, 270, $color);
    imagearc($img, $x2 - $r, $y1 + $r, $r * 2, $r * 2, 270, 360, $color);
    imagearc($img, $x1 + $r, $y2 - $r, $r * 2, $r * 2, 90, 180, $color);
    imagearc($img, $x2 - $r, $y2 - $r, $r * 2, $r * 2, 0, 90, $color);
    imagesetthickness($img, 1);
}

function pg_gradient($img, int $w, int $h): void
{
    for ($y = 0; $y < $h; $y++) {
        $t = $y / max(1, $h - 1);
        // biru tua atas ke cyan bawah
        $r = (int) (32 + (10 - 32) * $t);
        $g = (int) (92 + (199 - 92) * $t);
        $b = (int) (245 + (231 - 245) * $t);
        $c = imagecolorallocate($img, $r, $g, $b);
        imageline($img, 0, $y, $w, $y, $c);
    }

    // gradasi ungu halus kanan atas
    for ($x = 0; $x < $w; $x += 2) {
        $alpha = (int) min(92, 118 * ($x / max(1, $w)));
        $c = imagecolorallocatealpha($img, 86, 54, 214, 127 - $alpha);
        imageline($img, $x, 0, $x, $h, $c);
    }
}

function pg_qr_image($img, string $data, int $x, int $y, int $size, int $white, int $dark): void
{
    imagefilledrectangle($img, $x - 20, $y - 20, $x + $size + 20, $y + $size + 20, $white);
    $drawn = false;
    $qrUrl = po_qr_image_url($data, 900);
    if ($qrUrl !== '') {
        $raw = @file_get_contents($qrUrl);
        if ($raw !== false) {
            $qr = @imagecreatefromstring($raw);
            if ($qr) {
                imagecopyresampled($img, $qr, $x, $y, 0, 0, $size, $size, imagesx($qr), imagesy($qr));
                imagedestroy($qr);
                $drawn = true;
            }
        }
    }
    if (!$drawn) {
        pg_text($img, 'QR TIDAK TERSEDIA', $x + (int) ($size / 2), $y + (int) ($size / 2), $dark, 28, 'bold', 'center');
        pg_text($img, $data, $x + (int) ($size / 2), $y + (int) ($size / 2) + 45, $dark, 18, 'regular', 'center');
    }
}

$w = 1080;
$h = 1600;
$img = imagecreatetruecolor($w, $h);
imagesavealpha($img, true);
imageantialias($img, true);

pg_gradient($img, $w, $h);

$white = pg_color($img, 255, 255, 255, 0);
$whiteSoft = pg_color($img, 255, 255, 255, 52);
$whiteLine = pg_color($img, 255, 255, 255, 64);
$dark = pg_color($img, 8, 24, 76, 0);
$blue = pg_color($img, 34, 105, 244, 0);
$blue2 = pg_color($img, 29, 155, 232, 0);
$shadow = pg_color($img, 5, 23, 65, 90);
$shadow2 = pg_color($img, 5, 23, 65, 106);
$cardStroke = pg_color($img, 222, 242, 255, 0);
$soft = pg_color($img, 233, 247, 255, 18);
$soft2 = pg_color($img, 229, 245, 255, 30);

// HEADER - tanpa stiker, cukup teks
$namaUmkmText = strtoupper(pg_cut($namaUmkm, 28));
$namaUmkmSize = pg_fit_size($namaUmkmText, 940, 64, 34, 'bold');
pg_text($img, $namaUmkmText, (int) ($w / 2), 120, $white, $namaUmkmSize, 'bold', 'center', $shadow2);

pg_fill_round_rect($img, 205, 165, 875, 235, 34, $whiteSoft);
pg_round_border($img, 205, 165, 875, 235, 34, $whiteLine, 2);
pg_text($img, 'Kode Pengambilan Produk', (int) ($w / 2), 210, $dark, 30, 'bold', 'center');

// CARD UTAMA
$cardX1 = 70;
$cardY1 = 285;
$cardX2 = 1010;
$cardY2 = 1172;
pg_fill_round_rect($img, $cardX1 + 12, $cardY1 + 16, $cardX2 + 12, $cardY2 + 16, 52, $shadow);
pg_fill_round_rect($img, $cardX1, $cardY1, $cardX2, $cardY2, 52, $white);
pg_round_border($img, $cardX1, $cardY1, $cardX2, $cardY2, 52, $cardStroke, 3);

// QR besar
$qrSize = 610;
$qrX = (int) (($w - $qrSize) / 2);
$qrY = 345;
pg_qr_image($img, $noPesanan, $qrX, $qrY, $qrSize, $white, $dark);

// Label No Pesanan
$lineY = 990;
imagesetthickness($img, 2);
imageline($img, 150, $lineY, 350, $lineY, $blue);
imageline($img, 730, $lineY, 930, $lineY, $blue);
imagefilledellipse($img, 355, $lineY, 11, 11, $blue);
imagefilledellipse($img, 725, $lineY, 11, 11, $blue);
imagesetthickness($img, 1);
pg_text($img, 'No Pesanan', (int) ($w / 2), 1016, $blue, 30, 'bold', 'center');

// No pesanan gede
$orderText = pg_cut($noPesanan, 30);
$orderSize = pg_fit_size($orderText, 900, 60, 34, 'bold');
pg_text($img, $orderText, (int) ($w / 2), 1105, $dark, $orderSize, 'bold', 'center');

// Pelanggan + HP
$customerText = pg_cut($namaPelanggan, 22) . '  |  ' . pg_cut($noHp, 22);
$customerSize = pg_fit_size($customerText, 770, 34, 20, 'bold');
pg_fill_round_rect($img, 145, 1208, 935, 1285, 36, $soft);
pg_text($img, $customerText, (int) ($w / 2), 1257, $dark, $customerSize, 'bold', 'center');

// PANEL INSTRUKSI
$infoX1 = 85;
$infoY1 = 1320;
$infoX2 = 995;
$infoY2 = 1430;
pg_fill_round_rect($img, $infoX1 + 6, $infoY1 + 8, $infoX2 + 6, $infoY2 + 8, 28, $shadow2);
pg_fill_round_rect($img, $infoX1, $infoY1, $infoX2, $infoY2, 28, pg_color($img, 28, 147, 232, 24));
pg_round_border($img, $infoX1, $infoY1, $infoX2, $infoY2, 28, $whiteLine, 2);
pg_text($img, 'Tunjukkan barcode', (int) ($w / 2), 1363, $white, 34, 'bold', 'center', $shadow2);
pg_text($img, 'untuk pengambilan produk', (int) ($w / 2), 1405, $white, 24, 'regular', 'center', $shadow2);

// PANEL TERIMA KASIH
$helpX1 = 85;
$helpY1 = 1462;
$helpX2 = 995;
$helpY2 = 1566;
pg_fill_round_rect($img, $helpX1, $helpY1, $helpX2, $helpY2, 28, $soft2);
pg_round_border($img, $helpX1, $helpY1, $helpX2, $helpY2, 28, $cardStroke, 2);
pg_text($img, 'Terima kasih,', (int) ($w / 2), 1502, $dark, 29, 'bold', 'center');
$helpLine = 'jika ada kendala hubungi no ' . pg_cut($noHpUmkm, 22);
$helpSize = pg_fit_size($helpLine, 810, 22, 15, 'regular');
pg_text($img, $helpLine, (int) ($w / 2), 1538, $dark, $helpSize, 'regular', 'center');

$filename = preg_replace('/[^A-Za-z0-9_-]+/', '-', $noPesanan) ?: 'kode-pengambilan-produk';

if (ob_get_level() > 0) {
    @ob_clean();
}

header('Content-Type: image/jpeg');
header('Content-Disposition: attachment; filename="' . $filename . '.jpg"');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

imagejpeg($img, null, 95);
imagedestroy($img);
exit;
