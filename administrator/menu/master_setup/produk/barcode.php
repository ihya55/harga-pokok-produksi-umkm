<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../orm/ProdukORM.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

harus_login();

/**
 * Pola Code 128. Setiap angka menunjukkan lebar bar/space secara bergantian.
 */
function code128_patterns(): array
{
    return [
        '212222','222122','222221','121223','121322','131222','122213','122312','132212','221213',
        '221312','231212','112232','122132','122231','113222','123122','123221','223211','221132',
        '221231','213212','223112','312131','311222','321122','321221','312212','322112','322211',
        '212123','212321','232121','111323','131123','131321','112313','132113','132311','211313',
        '231113','231311','112133','112331','132131','113123','113321','133121','313121','211331',
        '231131','213113','213311','213131','311123','311321','331121','312113','312311','332111',
        '314111','221411','431111','111224','111422','121124','121421','141122','141221','112214',
        '112412','122114','122411','142112','142211','241211','221114','413111','241112','134111',
        '111242','121142','121241','114212','124112','124211','411212','421112','421211','212141',
        '214121','412121','111143','111341','131141','114113','114311','411113','411311','113141',
        '114131','311141','411131','211412','211214','211232','2331112',
    ];
}

function code128b_values(string $text): array
{
    $values = [104]; // Start Code B
    $checksum = 104;
    $position = 1;

    $length = strlen($text);
    for ($i = 0; $i < $length; $i++) {
        $ascii = ord($text[$i]);
        if ($ascii < 32 || $ascii > 126) {
            throw new InvalidArgumentException('Kode barcode hanya mendukung karakter ASCII 32-126.');
        }

        $value = $ascii - 32;
        $values[] = $value;
        $checksum += $value * $position;
        $position++;
    }

    $values[] = $checksum % 103;
    $values[] = 106; // Stop

    return $values;
}

function render_code128_png(string $text, bool $download = false, string $filename = 'barcode.png'): void
{
    if (!function_exists('imagecreatetruecolor')) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Ekstensi PHP GD belum aktif, sehingga barcode PNG tidak dapat dibuat.';
        exit;
    }

    $text = trim($text);
    if ($text === '') {
        http_response_code(404);
        exit;
    }

    $patterns = code128_patterns();
    $values = code128b_values($text);

    $moduleCount = 20; // quiet zone kiri dan kanan
    foreach ($values as $value) {
        $pattern = $patterns[$value] ?? '';
        for ($i = 0, $length = strlen($pattern); $i < $length; $i++) {
            $moduleCount += (int) $pattern[$i];
        }
    }

    $moduleWidth = 2;
    $barHeight = 96;
    $textHeight = 28;
    $paddingX = 18;
    $paddingY = 14;
    $width = ($moduleCount * $moduleWidth) + ($paddingX * 2);
    $height = $barHeight + $textHeight + ($paddingY * 2);

    $image = imagecreatetruecolor($width, $height);
    $white = imagecolorallocate($image, 255, 255, 255);
    $black = imagecolorallocate($image, 20, 20, 20);
    $border = imagecolorallocate($image, 220, 220, 220);

    imagefilledrectangle($image, 0, 0, $width, $height, $white);
    imagerectangle($image, 0, 0, $width - 1, $height - 1, $border);

    $x = $paddingX + (10 * $moduleWidth);
    $y = $paddingY;

    foreach ($values as $value) {
        $pattern = $patterns[$value];
        $drawBar = true;

        for ($i = 0, $length = strlen($pattern); $i < $length; $i++) {
            $segmentWidth = ((int) $pattern[$i]) * $moduleWidth;
            if ($drawBar) {
                imagefilledrectangle($image, $x, $y, $x + $segmentWidth - 1, $y + $barHeight, $black);
            }
            $x += $segmentWidth;
            $drawBar = !$drawBar;
        }
    }

    $font = 5;
    $textWidth = imagefontwidth($font) * strlen($text);
    $textX = max(2, (int) (($width - $textWidth) / 2));
    $textY = $paddingY + $barHeight + 6;
    imagestring($image, $font, $textX, $textY, $text, $black);

    header('Content-Type: image/png');
    header('Cache-Control: private, max-age=86400');

    if ($download) {
        $safeFilename = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename) ?: 'barcode.png';
        header('Content-Disposition: attachment; filename="' . $safeFilename . '"');
    }

    imagepng($image);
    imagedestroy($image);
    exit;
}

function ean13_checksum_valid(string $text): bool
{
    if (!preg_match('/^\d{13}$/', $text)) {
        return false;
    }

    $total = 0;
    for ($i = 0; $i < 12; $i++) {
        $digit = (int) $text[$i];
        $total += ($i % 2 === 0) ? $digit : ($digit * 3);
    }

    $checkDigit = (10 - ($total % 10)) % 10;

    return $checkDigit === (int) $text[12];
}

function render_ean13_png(string $text, bool $download = false, string $filename = 'barcode.png'): void
{
    if (!function_exists('imagecreatetruecolor')) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Ekstensi PHP GD belum aktif, sehingga barcode PNG tidak dapat dibuat.';
        exit;
    }

    $text = trim($text);
    if (!ean13_checksum_valid($text)) {
        render_code128_png($text, $download, $filename);
    }

    $leftOdd = [
        '0' => '0001101', '1' => '0011001', '2' => '0010011', '3' => '0111101', '4' => '0100011',
        '5' => '0110001', '6' => '0101111', '7' => '0111011', '8' => '0110111', '9' => '0001011',
    ];
    $leftEven = [
        '0' => '0100111', '1' => '0110011', '2' => '0011011', '3' => '0100001', '4' => '0011101',
        '5' => '0111001', '6' => '0000101', '7' => '0010001', '8' => '0001001', '9' => '0010111',
    ];
    $right = [
        '0' => '1110010', '1' => '1100110', '2' => '1101100', '3' => '1000010', '4' => '1011100',
        '5' => '1001110', '6' => '1010000', '7' => '1000100', '8' => '1001000', '9' => '1110100',
    ];
    $parity = [
        '0' => 'OOOOOO', '1' => 'OOEOEE', '2' => 'OOEEOE', '3' => 'OOEEEO', '4' => 'OEOOEE',
        '5' => 'OEEOOE', '6' => 'OEEEOO', '7' => 'OEOEOE', '8' => 'OEOEEO', '9' => 'OEEOEO',
    ];

    $pattern = '101';
    $parityPattern = $parity[$text[0]];

    for ($i = 1; $i <= 6; $i++) {
        $digit = $text[$i];
        $pattern .= ($parityPattern[$i - 1] === 'O') ? $leftOdd[$digit] : $leftEven[$digit];
    }

    $pattern .= '01010';

    for ($i = 7; $i <= 12; $i++) {
        $pattern .= $right[$text[$i]];
    }

    $pattern .= '101';

    $moduleWidth = 3;
    $barHeight = 104;
    $guardExtra = 9;
    $textHeight = 30;
    $quietModulesLeft = 11;
    $quietModulesRight = 8;
    $paddingY = 14;
    $moduleCount = 95 + $quietModulesLeft + $quietModulesRight;
    $width = $moduleCount * $moduleWidth;
    $height = $barHeight + $textHeight + ($paddingY * 2);

    $image = imagecreatetruecolor($width, $height);
    $white = imagecolorallocate($image, 255, 255, 255);
    $black = imagecolorallocate($image, 20, 20, 20);
    $border = imagecolorallocate($image, 220, 220, 220);

    imagefilledrectangle($image, 0, 0, $width, $height, $white);
    imagerectangle($image, 0, 0, $width - 1, $height - 1, $border);

    $x = $quietModulesLeft * $moduleWidth;
    $y = $paddingY;
    $length = strlen($pattern);

    for ($i = 0; $i < $length; $i++) {
        if ($pattern[$i] === '1') {
            $isGuard = ($i < 3) || ($i >= 45 && $i < 50) || ($i >= 92);
            $heightBar = $barHeight + ($isGuard ? $guardExtra : 0);
            imagefilledrectangle($image, $x, $y, $x + $moduleWidth - 1, $y + $heightBar, $black);
        }
        $x += $moduleWidth;
    }

    $font = 5;
    $textY = $paddingY + $barHeight + 8;

    imagestring($image, $font, 6, $textY, $text[0], $black);

    $leftText = substr($text, 1, 6);
    $leftTextWidth = imagefontwidth($font) * strlen($leftText);
    $leftTextX = ($quietModulesLeft * $moduleWidth) + (3 * $moduleWidth) + (int) (((42 * $moduleWidth) - $leftTextWidth) / 2);
    imagestring($image, $font, $leftTextX, $textY, $leftText, $black);

    $rightText = substr($text, 7, 6);
    $rightTextWidth = imagefontwidth($font) * strlen($rightText);
    $rightTextX = ($quietModulesLeft * $moduleWidth) + (50 * $moduleWidth) + (int) (((42 * $moduleWidth) - $rightTextWidth) / 2);
    imagestring($image, $font, $rightTextX, $textY, $rightText, $black);

    header('Content-Type: image/png');
    header('Cache-Control: private, max-age=86400');

    if ($download) {
        $safeFilename = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename) ?: 'barcode.png';
        header('Content-Disposition: attachment; filename="' . $safeFilename . '"');
    }

    imagepng($image);
    imagedestroy($image);
    exit;
}

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_produk = (int) ($_GET['id'] ?? 0);

$row = ProdukORM::query()
    ->where('id_entitas', $id_entitas)
    ->find($id_produk);

if (!$row) {
    http_response_code(404);
    exit;
}

$barcode = trim((string) ($row->barcode_produk ?? ''));
if ($barcode === '') {
    $barcode = trim((string) ($row->kode_produk ?? ''));
}

$download = (string) ($_GET['download'] ?? '') === '1';
$filename = 'barcode_' . ($row->kode_produk ?? $id_produk) . '.png';

if (ean13_checksum_valid($barcode)) {
    render_ean13_png($barcode, $download, $filename);
}

render_code128_png($barcode, $download, $filename);
