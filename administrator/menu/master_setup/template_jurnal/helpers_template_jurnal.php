<?php
declare(strict_types=1);

function opsi_jenis_transaksi_template(): array
{
    return [
        'PENJUALAN_TUNAI'               => 'Penjualan Tunai',
        'PENJUALAN_KREDIT'              => 'Penjualan Kredit',
        'PELUNASAN_PIUTANG'             => 'Pelunasan Piutang',
        'PEMBELIAN_BAHAN_BAKU_TUNAI'    => 'Pembelian Bahan Baku Tunai',
        'PEMBELIAN_BAHAN_BAKU_KREDIT'   => 'Pembelian Bahan Baku Kredit',
        'PEMBELIAN_BAHAN_PENOLONG_TUNAI'=> 'Pembelian Bahan Penolong Tunai',
        'PEMBELIAN_KEMASAN_TUNAI'       => 'Pembelian Kemasan Tunai',
        'PEMBAYARAN_UTANG_USAHA'        => 'Pembayaran Utang Usaha',
        'PEMAKAIAN_BAHAN_BAKU'          => 'Pemakaian Bahan Baku',
        'PEMAKAIAN_BAHAN_PENOLONG'      => 'Pemakaian Bahan Penolong',
        'BIAYA_TENAGA_KERJA_LANGSUNG'   => 'Biaya Tenaga Kerja Langsung',
        'BIAYA_OVERHEAD_PABRIK'         => 'Biaya Overhead Pabrik',
        'HASIL_PRODUKSI'                => 'Hasil Produksi',
        'BEBAN_GAJI_ADMIN'              => 'Beban Gaji Administrasi',
        'BEBAN_PEMASARAN'               => 'Beban Pemasaran',
        'BEBAN_TRANSPORTASI'            => 'Beban Transportasi',
        'BEBAN_ATK_KEBERSIHAN'          => 'Beban ATK dan Kebersihan',
        'BEBAN_LISTRIK_AIR_KANTOR'      => 'Beban Listrik dan Air Kantor',
        'BEBAN_LAIN_LAIN'               => 'Beban Lain-lain',
        'SETOR_BANK'                    => 'Setor Bank',
        'TARIK_BANK'                    => 'Tarik Bank',
        'KAS_MASUK_LAIN'                => 'Kas Masuk Lain',
        'KAS_KELUAR_LAIN'               => 'Kas Keluar Lain',
        'PENYUSUTAN_PERALATAN_PRODUKSI' => 'Penyusutan Peralatan Produksi',
        'PENYUSUTAN_PERALATAN_KANTOR'   => 'Penyusutan Peralatan Kantor',
        'PENYUSUTAN_KENDARAAN'          => 'Penyusutan Kendaraan',
    ];
}

function singkatan_jenis_transaksi_template(string $kodeJenis): string
{
    $map = [
        'PENJUALAN_TUNAI'                => 'PJT',
        'PENJUALAN_KREDIT'               => 'PJK',
        'PELUNASAN_PIUTANG'              => 'PPI',
        'PEMBELIAN_BAHAN_BAKU_TUNAI'     => 'PBT',
        'PEMBELIAN_BAHAN_BAKU_KREDIT'    => 'PBK',
        'PEMBELIAN_BAHAN_PENOLONG_TUNAI' => 'PPT',
        'PEMBELIAN_KEMASAN_TUNAI'        => 'PKT',
        'PEMBAYARAN_UTANG_USAHA'         => 'PUU',
        'PEMAKAIAN_BAHAN_BAKU'           => 'MBB',
        'PEMAKAIAN_BAHAN_PENOLONG'       => 'MBP',
        'BIAYA_TENAGA_KERJA_LANGSUNG'    => 'BTK',
        'BIAYA_OVERHEAD_PABRIK'          => 'BOP',
        'HASIL_PRODUKSI'                 => 'HPR',
        'BEBAN_GAJI_ADMIN'               => 'BGA',
        'BEBAN_PEMASARAN'                => 'BPM',
        'BEBAN_TRANSPORTASI'             => 'BTR',
        'BEBAN_ATK_KEBERSIHAN'           => 'BAK',
        'BEBAN_LISTRIK_AIR_KANTOR'       => 'BLA',
        'BEBAN_LAIN_LAIN'                => 'BLL',
        'SETOR_BANK'                     => 'STB',
        'TARIK_BANK'                     => 'TRB',
        'KAS_MASUK_LAIN'                 => 'KML',
        'KAS_KELUAR_LAIN'                => 'KKL',
        'PENYUSUTAN_PERALATAN_PRODUKSI'  => 'PPP',
        'PENYUSUTAN_PERALATAN_KANTOR'    => 'PPK',
        'PENYUSUTAN_KENDARAAN'           => 'PYK',
    ];

    return $map[$kodeJenis] ?? 'TJL';
}