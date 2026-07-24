<?php
declare(strict_types=1);

function opsi_jenis_produk(): array
{
    return [
        'barang_jadi'    => 'Barang Jadi',
        'setengah_jadi'  => 'Setengah Jadi',
        'jasa'           => 'Jasa',
        'non_persediaan' => 'Non Persediaan',
    ];
}

/**
 * Ambil id_coa default dari tabel konfigurasi akun sistem
 * berdasarkan kode_konfigurasi per entitas.
 */
function get_default_coa_id(int $idEntitas, string $kodeKonfigurasi): ?int
{
    $row = KonfigurasiAkunORM::query()
        ->where('id_entitas', $idEntitas)
        ->where('kode_konfigurasi', $kodeKonfigurasi)
        ->where('status_aktif', 1)
        ->first();

    return $row ? (int) $row->id_coa : null;
}

/**
 * Ambil id_coa dengan fallback beberapa kode konfigurasi.
 */
function get_default_coa_id_fallback(int $idEntitas, array $kodeKonfigurasiList): ?int
{
    foreach ($kodeKonfigurasiList as $kodeKonfigurasi) {
        $idCoa = get_default_coa_id($idEntitas, (string) $kodeKonfigurasi);
        if ($idCoa !== null) {
            return $idCoa;
        }
    }

    return null;
}

/**
 * Mapping default COA per jenis produk
 * berbasis tb_konfigurasi_akun, bukan hardcode kode_coa.
 */
function mapping_default_coa_produk(int $idEntitas): array
{
    $coaPenjualanProduk = get_default_coa_id($idEntitas, 'penjualan_produk');
    $coaHpp             = get_default_coa_id($idEntitas, 'hpp');
    $coaPersBarangJadi  = get_default_coa_id($idEntitas, 'persediaan_barang_jadi');
    $coaPersSetengahJadi = get_default_coa_id_fallback($idEntitas, [
        'persediaan_barang_setengah_jadi',
        'persediaan_barang_jadi',
    ]);
    $coaPendapatanLain  = get_default_coa_id($idEntitas, 'pendapatan_lain');

    return [
        'barang_jadi' => [
            'id_coa_penjualan'  => $coaPenjualanProduk,
            'id_coa_hpp'        => $coaHpp,
            'id_coa_persediaan' => $coaPersBarangJadi,
        ],
        'setengah_jadi' => [
            'id_coa_penjualan'  => null,
            'id_coa_hpp'        => null,
            'id_coa_persediaan' => $coaPersSetengahJadi,
        ],
        'jasa' => [
            'id_coa_penjualan'  => $coaPendapatanLain,
            'id_coa_hpp'        => null,
            'id_coa_persediaan' => null,
        ],
        'non_persediaan' => [
            'id_coa_penjualan'  => $coaPendapatanLain,
            'id_coa_hpp'        => null,
            'id_coa_persediaan' => null,
        ],
    ];
}

/**
 * Normalisasi kode barcode dari input form.
 * Spasi dihapus agar kode mudah dipindai dan konsisten saat dicek duplikasi.
 */
function normalisasi_barcode_produk(string $barcode): string
{
    $barcode = trim($barcode);
    $barcode = preg_replace('/\s+/', '', $barcode) ?? '';

    return strtoupper($barcode);
}

/**
 * Validasi format kode barcode produk.
 * Format dibuat konservatif agar aman untuk tampilan barcode Code 128 dan nama file unduhan.
 */
function format_barcode_produk_valid(string $barcode): bool
{
    if ($barcode === '') {
        return false;
    }

    if (strlen($barcode) > 100) {
        return false;
    }

    return (bool) preg_match('/^[A-Z0-9._-]+$/', $barcode);
}

/**
 * Cek apakah barcode sudah dipakai pada entitas yang sama.
 */
function barcode_produk_sudah_dipakai(string $barcode, int $idEntitas, ?int $kecualiIdProduk = null): bool
{
    $query = ProdukORM::query()
        ->where('id_entitas', $idEntitas)
        ->where('barcode_produk', $barcode);

    if ($kecualiIdProduk !== null && $kecualiIdProduk > 0) {
        $query->where('id_produk', '!=', $kecualiIdProduk);
    }

    return $query->exists();
}

/**
 * Hitung digit kontrol EAN-13 untuk 12 digit pertama.
 * Kode otomatis memakai pola numerik 13 digit agar rapi sebagai kode barcode internal.
 */
function hitung_digit_kontrol_ean13(string $base12): string
{
    if (!preg_match('/^\d{12}$/', $base12)) {
        throw new InvalidArgumentException('Basis EAN-13 harus 12 digit angka.');
    }

    $total = 0;
    for ($i = 0; $i < 12; $i++) {
        $digit = (int) $base12[$i];
        $total += ($i % 2 === 0) ? $digit : ($digit * 3);
    }

    return (string) ((10 - ($total % 10)) % 10);
}

/**
 * Generate kode barcode otomatis per entitas.
 * Pola: 20 + 3 digit ID entitas + 7 digit nomor urut + 1 digit kontrol = 13 digit.
 */
function generate_barcode_produk_otomatis(int $idEntitas, ?int $kecualiIdProduk = null): string
{
    $entitasPart = str_pad((string) ($idEntitas % 1000), 3, '0', STR_PAD_LEFT);
    $prefix = '20' . $entitasPart;

    $last = ProdukORM::query()
        ->where('id_entitas', $idEntitas)
        ->where('barcode_produk', 'like', $prefix . '%')
        ->orderBy('barcode_produk', 'desc')
        ->first(['barcode_produk']);

    $nextNumber = 1;

    if ($last && !empty($last->barcode_produk)) {
        $lastBarcode = (string) $last->barcode_produk;
        if (preg_match('/^' . preg_quote($prefix, '/') . '(\d{7})\d$/', $lastBarcode, $matches)) {
            $nextNumber = ((int) $matches[1]) + 1;
        }
    }

    for ($i = 0; $i < 10000; $i++) {
        if ($nextNumber > 9999999) {
            break;
        }

        $base12 = $prefix . str_pad((string) $nextNumber, 7, '0', STR_PAD_LEFT);
        $barcode = $base12 . hitung_digit_kontrol_ean13($base12);

        if (!barcode_produk_sudah_dipakai($barcode, $idEntitas, $kecualiIdProduk)) {
            return $barcode;
        }

        $nextNumber++;
    }

    throw new RuntimeException('Gagal membuat kode barcode otomatis yang unik.');
}

