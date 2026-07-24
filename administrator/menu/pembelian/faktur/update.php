<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';

require_once __DIR__ . '/../../../../orm/FakturPembelianORM.php';
require_once __DIR__ . '/../../../../orm/FakturPembelianDetailORM.php';
require_once __DIR__ . '/../../../../orm/PenerimaanPembelianORM.php';
require_once __DIR__ . '/../../../../orm/PemasokORM.php';
require_once __DIR__ . '/../../../../orm/BahanBakuORM.php';
require_once __DIR__ . '/../../../../helpers/auth.php';
require_once __DIR__ . '/../../../../helpers/pajak.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_admin('pembelian/faktur');
}

function angka_decimal_faktur_beli_update($value): float
{
    return (float) preg_replace('/[^0-9.]/', '', (string) $value);
}

function qty_int_faktur_beli_update($value): int
{
    return max(0, (int) preg_replace('/[^0-9]/', '', (string) $value));
}

function persen_int_faktur_beli_update($value): float
{
    $nilai = (float) preg_replace('/[^0-9.]/', '', (string) $value);

    if ($nilai < 0) {
        $nilai = 0;
    }

    if ($nilai > 100) {
        $nilai = 100;
    }

    return round($nilai, 2);
}
function redirect_edit_faktur_beli(int $id): void
{
    header('Location: ' . admin_url('index.php?menu=pembelian/faktur/edit&id=' . $id));
    exit;
}

function validasi_coa_kas_bank_faktur_beli_update(int $id_entitas, string $jenis_pembayaran, int $id_coa): bool
{
    if (!in_array($jenis_pembayaran, ['tunai', 'transfer'], true)) {
        return true;
    }

    if ($id_coa <= 0) {
        return false;
    }

    $kode_template = $jenis_pembayaran === 'tunai'
        ? 'FAKTUR_PEMBELIAN_TUNAI'
        : 'FAKTUR_PEMBELIAN_TRANSFER';

    return Capsule::table('tb_template_jurnal as tj')
        ->join('tb_template_jurnal_detail as tjd', 'tjd.id_template_jurnal', '=', 'tj.id_template_jurnal')
        ->join('tb_coa as c', 'c.id_coa', '=', 'tjd.id_coa_default')
        ->where('tj.id_entitas', $id_entitas)
        ->where('tj.kode_jenis_transaksi', $kode_template)
        ->where('tj.status_aktif', 1)
        ->where('tjd.posisi_dc', 'kredit')
        ->where('tjd.sumber_akun', 'coa_default')
        ->where('tjd.sumber_nominal', 'rumus_nominal')
        ->where('tjd.rumus_nominal', 'total')
        ->where('c.id_coa', $id_coa)
        ->where('c.boleh_transaksi', 1)
        ->where('c.status_aktif', 1)
        ->exists();
}

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_pengguna = (int) (user_login()['id_pengguna'] ?? 0);

$id_faktur_pembelian = (int) ($_POST['id_faktur_pembelian'] ?? 0);
$tanggal_faktur = trim((string) ($_POST['tanggal_faktur'] ?? ''));
$id_penerimaan_pembelian = (int) ($_POST['id_penerimaan_pembelian'] ?? 0);
$id_pemasok = (int) ($_POST['id_pemasok'] ?? 0);
$jenis_pembayaran = strtolower(trim((string) ($_POST['jenis_pembayaran'] ?? 'kredit')));
$id_coa_kas_bank = (int) ($_POST['id_coa_kas_bank'] ?? 0);
$jatuh_tempo = trim((string) ($_POST['jatuh_tempo'] ?? ''));
$catatan = trim((string) ($_POST['catatan'] ?? ''));
$detail = $_POST['detail'] ?? [];

$diskon_persen = persen_int_faktur_beli_update($_POST['diskon_persen'] ?? 0);
$ppn_persen = persen_int_faktur_beli_update($_POST['ppn_persen'] ?? pajak_default_persen($id_entitas, 'PPN', 11.0));
$ada_biaya_kirim = !empty($_POST['ada_biaya_kirim']) ? 1 : 0;
$biaya_kirim = $ada_biaya_kirim ? angka_decimal_faktur_beli_update($_POST['biaya_kirim'] ?? 0) : 0;
$id_coa_biaya_kirim = (int) ($_POST['id_coa_biaya_kirim'] ?? 0);

if (!in_array($jenis_pembayaran, ['kredit', 'tunai', 'transfer'], true)) {
    $jenis_pembayaran = 'kredit';
}

$row = FakturPembelianORM::query()
    ->where('id_entitas', $id_entitas)
    ->find($id_faktur_pembelian);

if (!$row) {
    set_flash('error', 'Data faktur pembelian tidak ditemukan.');
    redirect_admin('pembelian/faktur');
}

if ((string) $row->status_faktur !== 'draft') {
    set_flash('error', 'Faktur yang sudah diposting tidak bisa diubah.');
    header('Location: ' . admin_url('index.php?menu=pembelian/faktur/detail&id=' . $id_faktur_pembelian));
    exit;
}

if ($tanggal_faktur === '') {
    set_flash('error', 'Tanggal faktur wajib diisi.');
    redirect_edit_faktur_beli($id_faktur_pembelian);
}

$penerimaan = PenerimaanPembelianORM::query()
    ->where('id_entitas', $id_entitas)
    ->where('status_penerimaan', 'posted')
    ->where('id_penerimaan_pembelian', $id_penerimaan_pembelian)
    ->first();

if (!$penerimaan) {
    set_flash('error', 'Penerimaan pembelian tidak valid atau belum diposting.');
    redirect_edit_faktur_beli($id_faktur_pembelian);
}

$sudah_ada_faktur_lain = FakturPembelianORM::query()
    ->where('id_entitas', $id_entitas)
    ->where('id_penerimaan_pembelian', $id_penerimaan_pembelian)
    ->where('id_faktur_pembelian', '!=', $id_faktur_pembelian)
    ->whereIn('status_faktur', ['draft', 'posted'])
    ->exists();

if ($sudah_ada_faktur_lain) {
    set_flash('error', 'Penerimaan pembelian ini sudah digunakan pada faktur lain.');
    redirect_edit_faktur_beli($id_faktur_pembelian);
}

$pemasok_valid = PemasokORM::query()
    ->where('id_entitas', $id_entitas)
    ->where('id_pemasok', $id_pemasok)
    ->exists();

if (!$pemasok_valid) {
    set_flash('error', 'Pemasok tidak valid.');
    redirect_edit_faktur_beli($id_faktur_pembelian);
}

if ((int) $penerimaan->id_pemasok !== $id_pemasok) {
    set_flash('error', 'Pemasok faktur tidak sesuai dengan pemasok penerimaan pembelian.');
    redirect_edit_faktur_beli($id_faktur_pembelian);
}

if (($jenis_pembayaran === 'tunai' || $jenis_pembayaran === 'transfer') && !validasi_coa_kas_bank_faktur_beli_update($id_entitas, $jenis_pembayaran, $id_coa_kas_bank)) {
    set_flash('error', 'Akun kas/bank tidak valid untuk jenis pembayaran ' . $jenis_pembayaran . '. Pastikan template jurnal sudah benar.');
    redirect_edit_faktur_beli($id_faktur_pembelian);
}

if (!is_array($detail) || count($detail) === 0) {
    set_flash('error', 'Minimal harus ada 1 baris detail.');
    redirect_edit_faktur_beli($id_faktur_pembelian);
}

try {
    Capsule::connection()->transaction(function () use (
        $row,
        $id_entitas,
        $id_pengguna,
        $id_faktur_pembelian,
        $tanggal_faktur,
        $id_penerimaan_pembelian,
        $id_pemasok,
        $jenis_pembayaran,
        $id_coa_kas_bank,
        $jatuh_tempo,
        $catatan,
        $detail,
        $diskon_persen,
        $ppn_persen,
        $ada_biaya_kirim,
        $biaya_kirim,
        $id_coa_biaya_kirim
    ) {
        $detail_valid = [];
        $subtotal_header = 0.0;
        $cek_duplikat = [];

        foreach ($detail as $baris) {
            $id_bahan_baku = (int) ($baris['id_bahan_baku'] ?? 0);
            $qty = qty_int_faktur_beli_update($baris['qty'] ?? 0);
            $harga = angka_decimal_faktur_beli_update($baris['harga'] ?? 0);
            $diskon_detail = angka_decimal_faktur_beli_update($baris['diskon'] ?? 0);

            if ($id_bahan_baku <= 0) {
                throw new RuntimeException('Bahan baku wajib dipilih.');
            }

            if (in_array($id_bahan_baku, $cek_duplikat, true)) {
                throw new RuntimeException('Bahan baku tidak boleh duplikat dalam satu faktur.');
            }

            $bahan_valid = BahanBakuORM::query()
                ->where('id_entitas', $id_entitas)
                ->where('id_bahan_baku', $id_bahan_baku)
                ->exists();

            if (!$bahan_valid) {
                throw new RuntimeException('Bahan baku tidak valid.');
            }

            if ($qty <= 0) {
                throw new RuntimeException('Qty wajib lebih besar dari 0 dan harus bilangan bulat.');
            }

            if ($harga < 0) {
                throw new RuntimeException('Harga tidak valid.');
            }

            $bruto = $qty * $harga;

            if ($diskon_detail > $bruto) {
                $diskon_detail = $bruto;
            }

            $subtotal = round($bruto - $diskon_detail, 2);
            $subtotal_header += $subtotal;
            $cek_duplikat[] = $id_bahan_baku;

            $detail_valid[] = [
                'id_bahan_baku' => $id_bahan_baku,
                'qty'           => $qty,
                'harga'         => round($harga, 2),
                'diskon'        => round($diskon_detail, 2),
                'subtotal'      => $subtotal,
            ];
        }

        if (count($detail_valid) === 0) {
            throw new RuntimeException('Minimal harus ada 1 detail yang valid.');
        }

        if ($subtotal_header <= 0) {
            throw new RuntimeException('Subtotal faktur harus lebih besar dari 0.');
        }

        $diskon_header = round($subtotal_header * $diskon_persen / 100, 2);
        $dasar_ppn = max(0, $subtotal_header - $diskon_header);
        $ppn = round($dasar_ppn * $ppn_persen / 100, 2);
        $biaya_kirim_final = $ada_biaya_kirim ? round(max(0, (float) $biaya_kirim), 2) : 0.00;
        $total = round($subtotal_header - $diskon_header + $ppn + $biaya_kirim_final, 2);
        $sisa_utang = $jenis_pembayaran === 'kredit' ? $total : 0;

        if ($total <= 0) {
            throw new RuntimeException('Total faktur harus lebih besar dari 0.');
        }

        $row->update([
            'tanggal_faktur'          => $tanggal_faktur,
            'id_pemasok'              => $id_pemasok,
            'id_penerimaan_pembelian' => $id_penerimaan_pembelian,
            'jenis_pembayaran'        => $jenis_pembayaran,
            'id_coa_kas_bank'         => $jenis_pembayaran === 'kredit' ? null : $id_coa_kas_bank,
            'jatuh_tempo'             => $jenis_pembayaran === 'kredit' && $jatuh_tempo !== '' ? $jatuh_tempo : null,
            'subtotal'                => round($subtotal_header, 2),
            'diskon'                  => round($diskon_header, 2),
            'diskon_persen'           => $diskon_persen,
            'ppn'                     => round($ppn, 2),
            'ppn_persen'              => $ppn_persen,
            'ada_biaya_kirim'        => $biaya_kirim_final > 0 ? 1 : 0,
            'biaya_kirim'            => $biaya_kirim_final,
            'id_coa_biaya_kirim'     => null,
            'total'                   => $total,
            'sisa_utang'              => $sisa_utang,
            'catatan'                 => $catatan !== '' ? $catatan : null,
            'tanggal_diubah'          => date('Y-m-d H:i:s'),
            'diubah_oleh'             => $id_pengguna > 0 ? $id_pengguna : null,
        ]);

        FakturPembelianDetailORM::query()
            ->where('id_faktur_pembelian', $id_faktur_pembelian)
            ->delete();

        foreach ($detail_valid as $item) {
            FakturPembelianDetailORM::create([
                'id_faktur_pembelian' => $id_faktur_pembelian,
                'id_bahan_baku'       => $item['id_bahan_baku'],
                'qty'                 => $item['qty'],
                'harga'               => $item['harga'],
                'diskon'              => $item['diskon'],
                'subtotal'            => $item['subtotal'],
            ]);
        }
    });

    set_flash('success', 'Faktur pembelian berhasil diperbarui.');
    header('Location: ' . admin_url('index.php?menu=pembelian/faktur/detail&id=' . $id_faktur_pembelian));
    exit;
} catch (Throwable $e) {
    set_flash('error', $e->getMessage());
    redirect_edit_faktur_beli($id_faktur_pembelian);
}