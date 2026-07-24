-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 09, 2026 at 04:42 AM
-- Server version: 8.0.30
-- PHP Version: 8.3.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `harga_pokok_produksi`
--

-- --------------------------------------------------------

--
-- Table structure for table `tb_bahan_baku`
--

CREATE TABLE `tb_bahan_baku` (
  `id_bahan_baku` bigint UNSIGNED NOT NULL,
  `id_entitas` bigint UNSIGNED NOT NULL,
  `kode_bahan_baku` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_bahan_baku` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_satuan` bigint UNSIGNED NOT NULL,
  `harga_standar` decimal(18,2) NOT NULL DEFAULT '0.00',
  `stok_minimum` int NOT NULL DEFAULT '0',
  `status_aktif` tinyint(1) NOT NULL DEFAULT '1',
  `id_coa_persediaan` bigint UNSIGNED DEFAULT NULL,
  `tanggal_dibuat` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dibuat_oleh` bigint UNSIGNED DEFAULT NULL,
  `tanggal_diubah` datetime DEFAULT NULL,
  `diubah_oleh` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_bahan_baku`
--

INSERT INTO `tb_bahan_baku` (`id_bahan_baku`, `id_entitas`, `kode_bahan_baku`, `nama_bahan_baku`, `id_satuan`, `harga_standar`, `stok_minimum`, `status_aktif`, `id_coa_persediaan`, `tanggal_dibuat`, `dibuat_oleh`, `tanggal_diubah`, `diubah_oleh`) VALUES
(1, 1, 'BHN-0001', 'Tepung Ketan', 1, '0.00', 0, 1, 6, '2026-06-06 21:48:35', 1, NULL, NULL),
(2, 1, 'BHN-0002', 'Kelapa', 3, '0.00', 0, 1, 6, '2026-06-06 21:48:50', 1, '2026-06-06 22:21:05', 1),
(3, 1, 'BHN-0003', 'Gula Merah', 1, '0.00', 0, 1, 6, '2026-06-06 21:49:29', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tb_biaya_produksi`
--

CREATE TABLE `tb_biaya_produksi` (
  `id_biaya_produksi` bigint UNSIGNED NOT NULL,
  `id_entitas` bigint UNSIGNED NOT NULL,
  `no_biaya_produksi` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal_biaya` date NOT NULL,
  `id_perintah_produksi` bigint UNSIGNED DEFAULT NULL,
  `jenis_biaya_produksi` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `keterangan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `no_nota` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_nota` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jumlah_biaya` decimal(18,2) NOT NULL DEFAULT '0.00',
  `id_coa_lawan` bigint UNSIGNED NOT NULL,
  `status_posting` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `tanggal_dibuat` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dibuat_oleh` bigint UNSIGNED DEFAULT NULL,
  `tanggal_diubah` datetime DEFAULT NULL,
  `diubah_oleh` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_biaya_produksi`
--

INSERT INTO `tb_biaya_produksi` (`id_biaya_produksi`, `id_entitas`, `no_biaya_produksi`, `tanggal_biaya`, `id_perintah_produksi`, `jenis_biaya_produksi`, `keterangan`, `no_nota`, `file_nota`, `jumlah_biaya`, `id_coa_lawan`, `status_posting`, `tanggal_dibuat`, `dibuat_oleh`, `tanggal_diubah`, `diubah_oleh`) VALUES
(1, 1, 'BPR-0001', '2026-06-09', 1, 'tenaga_kerja', NULL, NULL, 'uploads/nota_produksi/nota_biaya_20260609044551_6d0e433c.jpeg', '55000.00', 3, 'posted', '2026-06-09 04:45:51', 1, '2026-06-09 04:45:55', 1);

-- --------------------------------------------------------

--
-- Table structure for table `tb_biaya_produksi_detail`
--

CREATE TABLE `tb_biaya_produksi_detail` (
  `id_biaya_produksi_detail` bigint UNSIGNED NOT NULL,
  `id_biaya_produksi` bigint UNSIGNED NOT NULL,
  `jenis_biaya_produksi` varchar(50) NOT NULL,
  `id_coa_lawan` bigint UNSIGNED NOT NULL,
  `kode_jenis_transaksi_template` varchar(50) DEFAULT NULL,
  `jumlah_biaya` decimal(18,2) NOT NULL DEFAULT '0.00',
  `keterangan` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tb_biaya_produksi_detail`
--

INSERT INTO `tb_biaya_produksi_detail` (`id_biaya_produksi_detail`, `id_biaya_produksi`, `jenis_biaya_produksi`, `id_coa_lawan`, `kode_jenis_transaksi_template`, `jumlah_biaya`, `keterangan`) VALUES
(1, 1, 'tenaga_kerja', 3, 'BIAYA_TENAGA_KERJA_LANGSUNG', '50000.00', NULL),
(2, 1, 'overhead', 3, 'BIAYA_LAIN_LAIN_PRODUKSI', '5000.00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tb_coa`
--

CREATE TABLE `tb_coa` (
  `id_coa` bigint UNSIGNED NOT NULL,
  `id_entitas` bigint UNSIGNED NOT NULL,
  `kode_coa` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_coa` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_coa_induk` bigint UNSIGNED DEFAULT NULL,
  `level_coa` int NOT NULL DEFAULT '1',
  `kategori_coa` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `posisi_saldo_normal` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `boleh_transaksi` tinyint(1) NOT NULL DEFAULT '1',
  `status_aktif` tinyint(1) NOT NULL DEFAULT '1',
  `nama_bank` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nomor_rekening` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `atas_nama_rekening` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qris_nama_merchant` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qris_static_payload` text COLLATE utf8mb4_unicode_ci,
  `qris_image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_akun_qris` tinyint(1) NOT NULL DEFAULT '0',
  `tanggal_dibuat` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dibuat_oleh` bigint UNSIGNED DEFAULT NULL,
  `tanggal_diubah` datetime DEFAULT NULL,
  `diubah_oleh` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_coa`
--

INSERT INTO `tb_coa` (`id_coa`, `id_entitas`, `kode_coa`, `nama_coa`, `id_coa_induk`, `level_coa`, `kategori_coa`, `posisi_saldo_normal`, `boleh_transaksi`, `status_aktif`, `nama_bank`, `nomor_rekening`, `atas_nama_rekening`, `qris_nama_merchant`, `qris_static_payload`, `qris_image_path`, `is_akun_qris`, `tanggal_dibuat`, `dibuat_oleh`, `tanggal_diubah`, `diubah_oleh`) VALUES
(1, 1, '1000', 'ASET', NULL, 1, 'aset', 'debit', 0, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-18 09:28:37', 1, NULL, NULL),
(2, 1, '1100', 'ASET LANCAR', 1, 2, 'aset', 'debit', 0, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-18 09:29:18', 1, '2026-04-18 09:31:14', 1),
(3, 1, '1110', 'Kas', 2, 3, 'aset', 'debit', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-18 09:29:43', 1, '2026-06-07 14:39:24', 1),
(4, 1, '1120', 'Bank', 2, 3, 'aset', 'debit', 1, 1, 'Blu My BCA', '001934340882', 'DODOL KURNIA - CATERING', 'DODOL', NULL, 'uploads/qris/qris_1_20260607090458_31688b2a.jpeg', 0, '2026-04-18 09:31:55', 1, '2026-06-07 14:39:24', 1),
(5, 1, '1130', 'Piutang Usaha', 2, 3, 'aset', 'debit', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-18 09:57:31', 1, NULL, NULL),
(6, 1, '1140', 'Persediaan Bahan Baku', 2, 3, 'aset', 'debit', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-18 09:57:58', 1, NULL, NULL),
(7, 1, '1150', 'Persediaan Bahan Penolong', 2, 3, 'aset', 'debit', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-18 09:58:23', 1, NULL, NULL),
(8, 1, '1160', 'Persediaan Kemasan', 2, 3, 'aset', 'debit', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-18 09:58:48', 1, NULL, NULL),
(9, 1, '1170', 'Persediaan Barang Jadi', 2, 3, 'aset', 'debit', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-18 09:59:05', 1, NULL, NULL),
(10, 1, '1180', 'Uang Muka Pembelian', 2, 3, 'aset', 'debit', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-18 09:59:22', 1, NULL, NULL),
(11, 1, '1200', 'ASET TETAP', NULL, 2, 'aset', 'debit', 0, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-18 09:59:46', 1, '2026-04-18 10:00:58', 1),
(12, 1, '1210', 'Peralatan Produksi', 11, 3, 'aset', 'debit', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-18 10:00:15', 1, NULL, NULL),
(13, 1, '1220', 'Akumulasi Penyusutan Peralatan', 11, 3, 'aset', 'kredit', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-18 10:01:32', 1, '2026-04-18 10:04:04', 1),
(14, 1, '1230', 'Kendaraan', 11, 3, 'aset', 'debit', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-18 10:01:49', 1, NULL, NULL),
(15, 1, '1240', 'Akumulasi Penyusutan Kendaraan', 11, 3, 'aset', 'kredit', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-18 10:02:20', 1, '2026-04-18 10:04:17', 1),
(16, 1, '1250', 'Peralatan Kantor', 11, 3, 'aset', 'debit', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-18 10:02:45', 1, NULL, NULL),
(17, 1, '1260', 'Akumulasi Penyusutan Peralatan Kantor', 11, 3, 'aset', 'kredit', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-18 10:03:07', 1, '2026-04-18 10:04:31', 1),
(18, 1, '2000', 'LIABILITAS', NULL, 1, 'liabilitas', 'kredit', 0, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-18 10:03:37', 1, NULL, NULL),
(19, 1, '2100', 'LIABILITAS JANGKA PENDEK', 18, 2, 'liabilitas', 'kredit', 0, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-18 10:05:23', 1, NULL, NULL),
(20, 1, '2110', 'Utang Usaha', 19, 3, 'liabilitas', 'kredit', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-18 10:05:56', 1, NULL, NULL),
(21, 1, '2120', 'Utang Gaji', 19, 3, 'liabilitas', 'kredit', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-18 10:06:35', 1, NULL, NULL),
(22, 1, '2130', 'Utang Biaya', 19, 3, 'liabilitas', 'kredit', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-18 10:06:55', 1, NULL, NULL),
(23, 1, '2140', 'Utang Pajak', 19, 3, 'liabilitas', 'kredit', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-18 10:07:12', 1, NULL, NULL),
(24, 1, '2150', 'Pendapatan Diterima Dimuka', 19, 3, 'liabilitas', 'kredit', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-18 10:07:41', 1, NULL, NULL),
(25, 1, '3000', 'EKUITAS', NULL, 1, 'ekuitas', 'kredit', 0, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-18 10:08:23', 1, NULL, NULL),
(26, 1, '3100', 'Modal Pemilik', 25, 2, 'ekuitas', 'kredit', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-18 10:08:54', 1, NULL, NULL),
(27, 1, '3200', 'Prive Pemilik', 25, 2, 'ekuitas', 'debit', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-18 10:09:21', 1, NULL, NULL),
(28, 1, '3300', 'Laba Ditahan', 25, 2, 'ekuitas', 'kredit', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-18 10:09:56', 1, NULL, NULL),
(29, 1, '3400', 'Laba Tahun Berjalan', 25, 2, 'ekuitas', 'kredit', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-18 10:10:17', 1, NULL, NULL),
(30, 1, '4000', 'PENDAPATAN', NULL, 1, 'pendapatan', 'kredit', 0, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-18 10:10:52', 1, NULL, NULL),
(31, 1, '4100', 'Penjualan Produk', 30, 2, 'pendapatan', 'kredit', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-18 10:11:14', 1, NULL, NULL),
(32, 1, '4110', 'Penjualan Tunai', 31, 3, 'pendapatan', 'kredit', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-18 10:11:40', 1, NULL, NULL),
(33, 1, '4120', 'Penjualan Kredit', 31, 3, 'pendapatan', 'kredit', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-18 10:12:01', 1, NULL, NULL),
(34, 1, '4200', 'Pendapatan Lain-lain', 30, 2, 'pendapatan', 'kredit', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-18 10:13:00', 1, NULL, NULL),
(35, 1, '5000', 'HARGA POKOK PRODUKSI', NULL, 1, 'hpp', 'debit', 0, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-18 10:13:25', 1, NULL, NULL),
(36, 1, '5100', 'Bahan Baku Terpakai', 35, 2, 'hpp', 'debit', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-18 10:13:46', 1, NULL, NULL),
(37, 1, '5110', 'Pembelian Bahan Baku', 36, 3, 'hpp', 'debit', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-18 10:14:10', 1, NULL, NULL),
(38, 1, '5120', 'Biaya Angkut Pembelian', 36, 3, 'hpp', 'debit', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-18 10:14:41', 1, NULL, NULL),
(39, 1, '5200', 'Tenaga Kerja Langsung', 35, 2, 'hpp', 'debit', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-18 10:15:44', 1, NULL, NULL),
(40, 1, '5300', 'Overhead Pabrik', 35, 2, 'hpp', 'debit', 0, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-18 10:16:58', 1, NULL, NULL),
(41, 1, '5310', 'Listrik dan Air Produksi', 40, 3, 'hpp', 'debit', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-18 10:17:22', 1, NULL, NULL),
(42, 1, '5320', 'Penyusutan Peralatan Produksi', 40, 3, 'hpp', 'debit', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-18 10:17:50', 1, NULL, NULL),
(43, 1, '5330', 'Bahan Penolong Terpakai', 40, 3, 'hpp', 'debit', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-18 10:18:14', 1, NULL, NULL),
(44, 1, '5400', 'Harga Pokok Penjualan', 35, 2, 'hpp', 'debit', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-18 10:18:39', 1, NULL, NULL),
(45, 1, '6000', 'BEBAN OPERASIONAL', NULL, 1, 'beban', 'debit', 0, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-18 10:18:59', 1, NULL, NULL),
(46, 1, '6100', 'Beban Gaji Administrasi', 45, 2, 'beban', 'debit', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-18 10:19:30', 1, NULL, NULL),
(47, 1, '6200', 'Beban Pemasaran dan Promosi', 45, 2, 'beban', 'debit', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-18 10:19:51', 1, NULL, NULL),
(48, 1, '6300', 'Beban Transportasi', 45, 2, 'beban', 'debit', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-18 10:20:10', 1, NULL, NULL),
(49, 1, '6400', 'Beban ATK dan Kebersihan', 45, 2, 'beban', 'debit', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-18 10:20:29', 1, NULL, NULL),
(50, 1, '6500', 'Beban Listrik dan Air Kantor', 45, 2, 'beban', 'debit', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-18 10:20:51', 1, NULL, NULL),
(51, 1, '6600', 'Beban Penyusutan Non Produksi', 45, 2, 'beban', 'debit', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-18 10:21:17', 1, NULL, NULL),
(52, 1, '6700', 'Beban Lain-lain', 45, 2, 'beban', 'debit', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-18 10:21:36', 1, NULL, NULL),
(53, 1, '5340', 'Biaya Kemasan Produksi', 40, 3, 'hpp', 'debit', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-25 10:53:40', 1, NULL, NULL),
(54, 1, '5350', 'Biaya Lain-lain Produksi', 40, 3, 'hpp', 'debit', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-25 10:53:40', 1, NULL, NULL),
(55, 1, '1190', 'Pajak Masukan', 2, 3, 'aset', 'debit', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-25 16:25:45', 1, '2026-04-25 16:25:45', 1),
(56, 1, '2141', 'Pajak Keluaran', 23, 4, 'liabilitas', 'kredit', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-25 16:25:45', 1, '2026-04-25 16:25:45', 1),
(57, 1, '4210', 'Pendapatan Selisih Stok', 34, 3, 'pendapatan', 'kredit', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-27 07:02:33', 1, NULL, NULL),
(58, 1, '6800', 'Beban Selisih Stok', 45, 2, 'beban', 'debit', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-27 07:02:33', 1, NULL, NULL),
(59, 1, '6310', 'Beban Pengiriman Penjualan', 45, 2, 'beban', 'debit', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-06-09 06:03:52', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tb_entitas`
--

CREATE TABLE `tb_entitas` (
  `id_entitas` bigint UNSIGNED NOT NULL,
  `kode_entitas` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_entitas` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_pemilik` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alamat` text COLLATE utf8mb4_unicode_ci,
  `no_hp` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jenis_usaha` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo_umkm` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'uploads/logo_umkm/logo_umkm_default.svg',
  `status_aktif` tinyint(1) NOT NULL DEFAULT '1',
  `tanggal_dibuat` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dibuat_oleh` bigint UNSIGNED DEFAULT NULL,
  `tanggal_diubah` datetime DEFAULT NULL,
  `diubah_oleh` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_entitas`
--

INSERT INTO `tb_entitas` (`id_entitas`, `kode_entitas`, `nama_entitas`, `nama_pemilik`, `alamat`, `no_hp`, `email`, `jenis_usaha`, `logo_umkm`, `status_aktif`, `tanggal_dibuat`, `dibuat_oleh`, `tanggal_diubah`, `diubah_oleh`) VALUES
(1, 'ENT-001', 'UMKM Guru Kapuh', 'Siti Aisyah', 'Kandangan, Kalimantan Selatan', '081254619752', 'winter.blue215@gmail.com', 'Dodol Kandangan', 'uploads/logo_umkm/logo_umkm_default.svg', 1, '2026-04-17 15:10:18', NULL, '2026-06-08 11:33:04', 1);

-- --------------------------------------------------------

--
-- Table structure for table `tb_faktur_pembelian`
--

CREATE TABLE `tb_faktur_pembelian` (
  `id_faktur_pembelian` bigint UNSIGNED NOT NULL,
  `id_entitas` bigint UNSIGNED NOT NULL,
  `no_faktur_pembelian` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal_faktur` date NOT NULL,
  `id_pemasok` bigint UNSIGNED NOT NULL,
  `id_penerimaan_pembelian` bigint UNSIGNED DEFAULT NULL,
  `jenis_pembayaran` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_coa_kas_bank` bigint UNSIGNED DEFAULT NULL,
  `status_faktur` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `jatuh_tempo` date DEFAULT NULL,
  `subtotal` decimal(18,2) NOT NULL DEFAULT '0.00',
  `diskon` decimal(18,2) NOT NULL DEFAULT '0.00',
  `diskon_persen` int NOT NULL DEFAULT '0',
  `ppn` decimal(18,2) NOT NULL DEFAULT '0.00',
  `ppn_persen` int NOT NULL DEFAULT '0',
  `ada_biaya_kirim` tinyint(1) NOT NULL DEFAULT '0',
  `biaya_kirim` decimal(18,2) NOT NULL DEFAULT '0.00',
  `id_coa_biaya_kirim` bigint UNSIGNED DEFAULT NULL,
  `total` decimal(18,2) NOT NULL DEFAULT '0.00',
  `sisa_utang` decimal(18,2) NOT NULL DEFAULT '0.00',
  `catatan` text COLLATE utf8mb4_unicode_ci,
  `tanggal_dibuat` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dibuat_oleh` bigint UNSIGNED DEFAULT NULL,
  `tanggal_posting` datetime DEFAULT NULL,
  `diposting_oleh` bigint UNSIGNED DEFAULT NULL,
  `tanggal_diubah` datetime DEFAULT NULL,
  `diubah_oleh` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_faktur_pembelian`
--

INSERT INTO `tb_faktur_pembelian` (`id_faktur_pembelian`, `id_entitas`, `no_faktur_pembelian`, `tanggal_faktur`, `id_pemasok`, `id_penerimaan_pembelian`, `jenis_pembayaran`, `id_coa_kas_bank`, `status_faktur`, `jatuh_tempo`, `subtotal`, `diskon`, `diskon_persen`, `ppn`, `ppn_persen`, `ada_biaya_kirim`, `biaya_kirim`, `id_coa_biaya_kirim`, `total`, `sisa_utang`, `catatan`, `tanggal_dibuat`, `dibuat_oleh`, `tanggal_posting`, `diposting_oleh`, `tanggal_diubah`, `diubah_oleh`) VALUES
(1, 1, 'FPB-0001', '2026-06-09', 1, 1, 'tunai', 3, 'posted', NULL, '650000.00', '0.00', 0, '0.00', 0, 0, '0.00', NULL, '650000.00', '0.00', NULL, '2026-06-09 04:43:07', 1, '2026-06-09 04:43:11', 1, '2026-06-09 04:43:11', 1);

-- --------------------------------------------------------

--
-- Table structure for table `tb_faktur_pembelian_detail`
--

CREATE TABLE `tb_faktur_pembelian_detail` (
  `id_faktur_pembelian_detail` bigint UNSIGNED NOT NULL,
  `id_faktur_pembelian` bigint UNSIGNED NOT NULL,
  `id_bahan_baku` bigint UNSIGNED NOT NULL,
  `qty` decimal(18,2) NOT NULL DEFAULT '0.00',
  `harga` decimal(18,2) NOT NULL DEFAULT '0.00',
  `diskon` decimal(18,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(18,2) NOT NULL DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_faktur_pembelian_detail`
--

INSERT INTO `tb_faktur_pembelian_detail` (`id_faktur_pembelian_detail`, `id_faktur_pembelian`, `id_bahan_baku`, `qty`, `harga`, `diskon`, `subtotal`) VALUES
(1, 1, 2, '50.00', '2000.00', '0.00', '100000.00'),
(2, 1, 1, '10.00', '15000.00', '0.00', '150000.00'),
(3, 1, 3, '20.00', '20000.00', '0.00', '400000.00');

-- --------------------------------------------------------

--
-- Table structure for table `tb_faktur_penjualan`
--

CREATE TABLE `tb_faktur_penjualan` (
  `id_faktur_penjualan` bigint UNSIGNED NOT NULL,
  `id_entitas` bigint UNSIGNED NOT NULL,
  `no_faktur_penjualan` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal_faktur` date NOT NULL,
  `id_pelanggan` bigint UNSIGNED NOT NULL,
  `id_penyerahan_penjualan` bigint UNSIGNED DEFAULT NULL,
  `sumber_faktur` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_pos` tinyint(1) NOT NULL DEFAULT '0',
  `jenis_pembayaran` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status_faktur` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `jatuh_tempo` date DEFAULT NULL,
  `subtotal` decimal(18,2) NOT NULL DEFAULT '0.00',
  `diskon` decimal(18,2) NOT NULL DEFAULT '0.00',
  `ppn` decimal(18,2) NOT NULL DEFAULT '0.00',
  `ppn_persen` decimal(5,2) NOT NULL DEFAULT '0.00',
  `total` decimal(18,2) NOT NULL DEFAULT '0.00',
  `sisa_piutang` decimal(18,2) NOT NULL DEFAULT '0.00',
  `catatan` text COLLATE utf8mb4_unicode_ci,
  `tanggal_dibuat` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dibuat_oleh` bigint UNSIGNED DEFAULT NULL,
  `tanggal_posting` datetime DEFAULT NULL,
  `diposting_oleh` bigint UNSIGNED DEFAULT NULL,
  `tanggal_diubah` datetime DEFAULT NULL,
  `diubah_oleh` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_faktur_penjualan`
--

INSERT INTO `tb_faktur_penjualan` (`id_faktur_penjualan`, `id_entitas`, `no_faktur_penjualan`, `tanggal_faktur`, `id_pelanggan`, `id_penyerahan_penjualan`, `sumber_faktur`, `is_pos`, `jenis_pembayaran`, `status_faktur`, `jatuh_tempo`, `subtotal`, `diskon`, `ppn`, `ppn_persen`, `total`, `sisa_piutang`, `catatan`, `tanggal_dibuat`, `dibuat_oleh`, `tanggal_posting`, `diposting_oleh`, `tanggal_diubah`, `diubah_oleh`) VALUES
(1, 1, 'FPJ-202606-0001', '2026-06-09', 1, 1, NULL, 0, 'kredit', 'posted', '2026-06-09', '50000.00', '0.00', '0.00', '0.00', '50000.00', '50000.00', 'POS: POS', '2026-06-09 05:30:26', 1, '2026-06-09 05:30:26', 1, NULL, NULL),
(2, 1, 'FPJ-202606-0002', '2026-06-09', 1, 3, 'pos', 1, 'tunai', 'posted', NULL, '50000.00', '0.00', '0.00', '0.00', '50000.00', '0.00', 'POS: POS', '2026-06-09 07:20:01', 1, '2026-06-09 07:20:01', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tb_faktur_penjualan_detail`
--

CREATE TABLE `tb_faktur_penjualan_detail` (
  `id_faktur_penjualan_detail` bigint UNSIGNED NOT NULL,
  `id_faktur_penjualan` bigint UNSIGNED NOT NULL,
  `id_produk` bigint UNSIGNED NOT NULL,
  `qty` decimal(18,2) NOT NULL DEFAULT '0.00',
  `harga` decimal(18,2) NOT NULL DEFAULT '0.00',
  `diskon` decimal(18,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(18,2) NOT NULL DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_faktur_penjualan_detail`
--

INSERT INTO `tb_faktur_penjualan_detail` (`id_faktur_penjualan_detail`, `id_faktur_penjualan`, `id_produk`, `qty`, `harga`, `diskon`, `subtotal`) VALUES
(1, 1, 3, '1.00', '50000.00', '0.00', '50000.00'),
(2, 2, 3, '1.00', '50000.00', '0.00', '50000.00');

-- --------------------------------------------------------

--
-- Table structure for table `tb_gudang`
--

CREATE TABLE `tb_gudang` (
  `id_gudang` bigint UNSIGNED NOT NULL,
  `id_entitas` bigint UNSIGNED NOT NULL,
  `kode_gudang` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_gudang` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jenis_gudang` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `alamat` text COLLATE utf8mb4_unicode_ci,
  `status_aktif` tinyint(1) NOT NULL DEFAULT '1',
  `tanggal_dibuat` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dibuat_oleh` bigint UNSIGNED DEFAULT NULL,
  `tanggal_diubah` datetime DEFAULT NULL,
  `diubah_oleh` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_gudang`
--

INSERT INTO `tb_gudang` (`id_gudang`, `id_entitas`, `kode_gudang`, `nama_gudang`, `jenis_gudang`, `alamat`, `status_aktif`, `tanggal_dibuat`, `dibuat_oleh`, `tanggal_diubah`, `diubah_oleh`) VALUES
(1, 1, 'GDG-0001', 'Gudang Utama', 'bahan_baku', NULL, 1, '2026-06-06 22:24:30', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tb_hasil_produksi`
--

CREATE TABLE `tb_hasil_produksi` (
  `id_hasil_produksi` bigint UNSIGNED NOT NULL,
  `id_entitas` bigint UNSIGNED NOT NULL,
  `no_hasil_produksi` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal_hasil` date NOT NULL,
  `id_perintah_produksi` bigint UNSIGNED NOT NULL,
  `id_produk` bigint UNSIGNED NOT NULL,
  `id_gudang` bigint UNSIGNED NOT NULL,
  `qty_hasil` decimal(18,2) NOT NULL DEFAULT '0.00',
  `total_biaya_bahan` decimal(18,2) NOT NULL DEFAULT '0.00',
  `total_biaya_tenaga_kerja` decimal(18,2) NOT NULL DEFAULT '0.00',
  `total_biaya_bop` decimal(18,2) NOT NULL DEFAULT '0.00',
  `total_hpp` decimal(18,2) NOT NULL DEFAULT '0.00',
  `hpp_per_unit` decimal(18,2) NOT NULL DEFAULT '0.00',
  `status_posting` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `catatan` text COLLATE utf8mb4_unicode_ci,
  `tanggal_dibuat` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dibuat_oleh` bigint UNSIGNED DEFAULT NULL,
  `tanggal_posting` datetime DEFAULT NULL,
  `diposting_oleh` bigint UNSIGNED DEFAULT NULL,
  `tanggal_diubah` datetime DEFAULT NULL,
  `diubah_oleh` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_hasil_produksi`
--

INSERT INTO `tb_hasil_produksi` (`id_hasil_produksi`, `id_entitas`, `no_hasil_produksi`, `tanggal_hasil`, `id_perintah_produksi`, `id_produk`, `id_gudang`, `qty_hasil`, `total_biaya_bahan`, `total_biaya_tenaga_kerja`, `total_biaya_bop`, `total_hpp`, `hpp_per_unit`, `status_posting`, `catatan`, `tanggal_dibuat`, `dibuat_oleh`, `tanggal_posting`, `diposting_oleh`, `tanggal_diubah`, `diubah_oleh`) VALUES
(1, 1, 'HPR-0001', '2026-06-09', 1, 3, 1, '45.00', '650000.00', '50000.00', '5000.00', '705000.00', '15666.67', 'posted', NULL, '2026-06-09 04:46:30', 1, '2026-06-09 04:46:34', 1, '2026-06-09 04:46:34', 1);

-- --------------------------------------------------------

--
-- Table structure for table `tb_jurnal`
--

CREATE TABLE `tb_jurnal` (
  `id_jurnal` bigint UNSIGNED NOT NULL,
  `id_entitas` bigint UNSIGNED NOT NULL,
  `no_jurnal` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal_jurnal` date NOT NULL,
  `id_periode` bigint UNSIGNED NOT NULL,
  `kode_jenis_transaksi` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `keterangan` text COLLATE utf8mb4_unicode_ci,
  `tabel_sumber` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_sumber` bigint UNSIGNED NOT NULL,
  `no_sumber` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_jurnal` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `total_debit` decimal(18,2) NOT NULL DEFAULT '0.00',
  `total_kredit` decimal(18,2) NOT NULL DEFAULT '0.00',
  `tanggal_dibuat` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dibuat_oleh` bigint UNSIGNED DEFAULT NULL,
  `tanggal_posting` datetime DEFAULT NULL,
  `diposting_oleh` bigint UNSIGNED DEFAULT NULL,
  `tanggal_diubah` datetime DEFAULT NULL,
  `diubah_oleh` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_jurnal`
--

INSERT INTO `tb_jurnal` (`id_jurnal`, `id_entitas`, `no_jurnal`, `tanggal_jurnal`, `id_periode`, `kode_jenis_transaksi`, `keterangan`, `tabel_sumber`, `id_sumber`, `no_sumber`, `status_jurnal`, `total_debit`, `total_kredit`, `tanggal_dibuat`, `dibuat_oleh`, `tanggal_posting`, `diposting_oleh`, `tanggal_diubah`, `diubah_oleh`) VALUES
(1, 1, 'JRN-0001', '2026-06-09', 2, 'SALDO_AWAL_COA', 'SALDO AWAL Juni', 'tb_jurnal', 1, 'JRN-0001', 'posted', '2000000.00', '2000000.00', '2026-06-09 04:40:54', 1, '2026-06-09 04:41:03', 1, '2026-06-09 04:41:03', 1),
(2, 1, 'JRN-0002', '2026-06-09', 2, 'FAKTUR_PEMBELIAN_TUNAI', 'Posting faktur pembelian FPB-0001 (Tunai)', 'tb_faktur_pembelian', 1, 'FPB-0001', 'posted', '650000.00', '650000.00', '2026-06-09 04:43:11', 1, '2026-06-09 04:43:11', 1, NULL, NULL),
(3, 1, 'JRN-0003', '2026-06-09', 2, 'PENGAMBILAN_BAHAN_PRODUKSI', 'Posting pengambilan bahan PGB-0001', 'tb_pengambilan_bahan', 1, 'PGB-0001', 'posted', '650000.00', '650000.00', '2026-06-09 04:44:24', 1, '2026-06-09 04:44:24', 1, NULL, NULL),
(4, 1, 'JRN-0004', '2026-06-09', 2, 'BIAYA_PRODUKSI', 'Posting biaya produksi BPR-0001', 'tb_biaya_produksi', 1, 'BPR-0001', 'posted', '55000.00', '55000.00', '2026-06-09 04:45:55', 1, '2026-06-09 04:45:55', 1, NULL, NULL),
(5, 1, 'JRN-0005', '2026-06-09', 2, 'HASIL_PRODUKSI', 'Posting hasil produksi HPR-0001', 'tb_hasil_produksi', 1, 'HPR-0001', 'posted', '705000.00', '705000.00', '2026-06-09 04:46:34', 1, '2026-06-09 04:46:34', 1, NULL, NULL),
(6, 1, 'JRN-0006', '2026-06-09', 2, 'PENYERAHAN_PENJUALAN_HPP', 'Posting HPP penyerahan penjualan PNJ-202606-0001', 'tb_penyerahan_penjualan', 1, 'PNJ-202606-0001', 'posted', '15666.67', '15666.67', '2026-06-09 05:30:26', 1, '2026-06-09 05:30:26', 1, NULL, NULL),
(7, 1, 'JRN-0007', '2026-06-09', 2, 'FAKTUR_PENJUALAN_KREDIT', 'Posting faktur penjualan FPJ-202606-0001', 'tb_faktur_penjualan', 1, 'FPJ-202606-0001', 'posted', '50000.00', '50000.00', '2026-06-09 05:30:26', 1, '2026-06-09 05:30:26', 1, NULL, NULL),
(8, 1, 'JRN-0008', '2026-06-09', 2, 'PENYERAHAN_PENJUALAN_HPP', 'Posting HPP penyerahan penjualan PNJ-202606-0002', 'tb_penyerahan_penjualan', 3, 'PNJ-202606-0002', 'posted', '15666.67', '15666.67', '2026-06-09 07:20:01', 1, '2026-06-09 07:20:01', 1, NULL, NULL),
(9, 1, 'JRN-0009', '2026-06-09', 2, 'FAKTUR_PENJUALAN_TUNAI', 'Posting faktur penjualan FPJ-202606-0002', 'tb_faktur_penjualan', 2, 'FPJ-202606-0002', 'posted', '50000.00', '50000.00', '2026-06-09 07:20:01', 1, '2026-06-09 07:20:01', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tb_jurnal_detail`
--

CREATE TABLE `tb_jurnal_detail` (
  `id_jurnal_detail` bigint UNSIGNED NOT NULL,
  `id_jurnal` bigint UNSIGNED NOT NULL,
  `urutan` int NOT NULL,
  `id_coa` bigint UNSIGNED NOT NULL,
  `debit` decimal(18,2) NOT NULL DEFAULT '0.00',
  `kredit` decimal(18,2) NOT NULL DEFAULT '0.00',
  `keterangan_baris` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_pelanggan` bigint UNSIGNED DEFAULT NULL,
  `id_pemasok` bigint UNSIGNED DEFAULT NULL,
  `id_produk` bigint UNSIGNED DEFAULT NULL,
  `id_bahan_baku` bigint UNSIGNED DEFAULT NULL,
  `id_gudang` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_jurnal_detail`
--

INSERT INTO `tb_jurnal_detail` (`id_jurnal_detail`, `id_jurnal`, `urutan`, `id_coa`, `debit`, `kredit`, `keterangan_baris`, `id_pelanggan`, `id_pemasok`, `id_produk`, `id_bahan_baku`, `id_gudang`) VALUES
(1, 1, 1, 3, '2000000.00', '0.00', NULL, NULL, NULL, NULL, NULL, NULL),
(2, 1, 2, 26, '0.00', '2000000.00', NULL, NULL, NULL, NULL, NULL, NULL),
(3, 2, 1, 6, '650000.00', '0.00', 'Persediaan bahan baku', NULL, 1, NULL, NULL, NULL),
(4, 2, 2, 3, '0.00', '650000.00', 'Kas keluar', NULL, 1, NULL, NULL, NULL),
(5, 3, 1, 36, '650000.00', '0.00', 'Bahan baku terpakai - PGB-0001', NULL, NULL, NULL, NULL, 1),
(6, 3, 2, 6, '0.00', '650000.00', 'Persediaan bahan baku keluar - PGB-0001', NULL, NULL, NULL, NULL, 1),
(7, 4, 1, 39, '50000.00', '0.00', 'Biaya tenaga kerja langsung - BPR-0001', NULL, NULL, NULL, NULL, NULL),
(8, 4, 2, 3, '0.00', '50000.00', 'Pengeluaran kas tenaga kerja langsung - BPR-0001', NULL, NULL, NULL, NULL, NULL),
(9, 4, 3, 54, '5000.00', '0.00', 'Biaya lain-lain produksi - BPR-0001', NULL, NULL, NULL, NULL, NULL),
(10, 4, 4, 3, '0.00', '5000.00', 'Pengeluaran kas biaya lain-lain produksi - BPR-0001', NULL, NULL, NULL, NULL, NULL),
(11, 5, 1, 9, '705000.00', '0.00', 'Persediaan produk jadi - HPR-0001', NULL, NULL, 3, NULL, 1),
(12, 5, 2, 36, '0.00', '705000.00', 'Pemindahan biaya produksi ke produk jadi - HPR-0001', NULL, NULL, 3, NULL, 1),
(13, 6, 1, 44, '15666.67', '0.00', 'Harga pokok penjualan', 1, NULL, NULL, NULL, 1),
(14, 6, 2, 9, '0.00', '15666.67', 'Persediaan barang jadi keluar', 1, NULL, NULL, NULL, 1),
(15, 7, 1, 5, '50000.00', '0.00', 'Piutang usaha faktur penjualan kredit', 1, NULL, NULL, NULL, NULL),
(16, 7, 2, 33, '0.00', '50000.00', 'Penjualan kredit', 1, NULL, NULL, NULL, NULL),
(17, 8, 1, 44, '15666.67', '0.00', 'Harga pokok penjualan', 1, NULL, NULL, NULL, 1),
(18, 8, 2, 9, '0.00', '15666.67', 'Persediaan barang jadi keluar', 1, NULL, NULL, NULL, 1),
(19, 9, 1, 3, '50000.00', '0.00', 'Kas masuk penjualan tunai', 1, NULL, NULL, NULL, NULL),
(20, 9, 2, 32, '0.00', '50000.00', 'Penjualan tunai', 1, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tb_kas_keluar`
--

CREATE TABLE `tb_kas_keluar` (
  `id_kas_keluar` bigint UNSIGNED NOT NULL,
  `id_entitas` bigint UNSIGNED NOT NULL,
  `no_kas_keluar` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal_kas_keluar` date NOT NULL,
  `jenis_kas_keluar` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_coa_beban` bigint UNSIGNED NOT NULL,
  `id_coa_kas_bank` bigint UNSIGNED NOT NULL,
  `jumlah` decimal(18,2) NOT NULL DEFAULT '0.00',
  `keterangan` text COLLATE utf8mb4_unicode_ci,
  `status_posting` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `tanggal_dibuat` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dibuat_oleh` bigint UNSIGNED DEFAULT NULL,
  `tanggal_diubah` datetime DEFAULT NULL,
  `diubah_oleh` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tb_kas_masuk`
--

CREATE TABLE `tb_kas_masuk` (
  `id_kas_masuk` bigint UNSIGNED NOT NULL,
  `id_entitas` bigint UNSIGNED NOT NULL,
  `no_kas_masuk` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal_kas_masuk` date NOT NULL,
  `sumber_kas_masuk` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_coa_kas_bank` bigint UNSIGNED NOT NULL,
  `jumlah` decimal(18,2) NOT NULL DEFAULT '0.00',
  `keterangan` text COLLATE utf8mb4_unicode_ci,
  `status_posting` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `tanggal_dibuat` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dibuat_oleh` bigint UNSIGNED DEFAULT NULL,
  `tanggal_diubah` datetime DEFAULT NULL,
  `diubah_oleh` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tb_kategori_produk`
--

CREATE TABLE `tb_kategori_produk` (
  `id_kategori_produk` bigint UNSIGNED NOT NULL,
  `id_entitas` bigint UNSIGNED NOT NULL,
  `kode_kategori_produk` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_kategori_produk` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `keterangan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_aktif` tinyint(1) NOT NULL DEFAULT '1',
  `tanggal_dibuat` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dibuat_oleh` bigint UNSIGNED DEFAULT NULL,
  `tanggal_diubah` datetime DEFAULT NULL,
  `diubah_oleh` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_kategori_produk`
--

INSERT INTO `tb_kategori_produk` (`id_kategori_produk`, `id_entitas`, `kode_kategori_produk`, `nama_kategori_produk`, `keterangan`, `status_aktif`, `tanggal_dibuat`, `dibuat_oleh`, `tanggal_diubah`, `diubah_oleh`) VALUES
(1, 1, 'KAT-0001', 'Makanan', 'Produk makanan olahan', 1, '2026-06-06 21:47:58', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tb_konfigurasi_akun`
--

CREATE TABLE `tb_konfigurasi_akun` (
  `id_konfigurasi_akun` bigint UNSIGNED NOT NULL,
  `id_entitas` bigint UNSIGNED NOT NULL,
  `kode_konfigurasi` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_konfigurasi` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `digunakan_di_menu` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_coa` bigint UNSIGNED NOT NULL,
  `keterangan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_aktif` tinyint(1) NOT NULL DEFAULT '1',
  `tanggal_dibuat` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dibuat_oleh` bigint UNSIGNED DEFAULT NULL,
  `tanggal_diubah` datetime DEFAULT NULL,
  `diubah_oleh` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_konfigurasi_akun`
--

INSERT INTO `tb_konfigurasi_akun` (`id_konfigurasi_akun`, `id_entitas`, `kode_konfigurasi`, `nama_konfigurasi`, `digunakan_di_menu`, `id_coa`, `keterangan`, `status_aktif`, `tanggal_dibuat`, `dibuat_oleh`, `tanggal_diubah`, `diubah_oleh`) VALUES
(1, 1, 'persediaan_bahan_baku', 'Default Persediaan Bahan Baku', 'Modul Bahan Baku', 6, 'Dipakai default COA persediaan pada form bahan baku', 1, '2026-04-18 18:07:17', 1, NULL, NULL),
(2, 1, 'persediaan_barang_jadi', 'Default Persediaan Barang Jadi', 'Modul Produk', 9, 'Dipakai default COA persediaan untuk produk barang jadi', 1, '2026-04-18 18:07:17', 1, NULL, NULL),
(3, 1, 'penjualan_produk', 'Default Penjualan Produk', 'Modul Produk', 31, 'Dipakai default COA penjualan produk', 1, '2026-04-18 18:07:18', 1, NULL, NULL),
(4, 1, 'hpp', 'Default HPP', 'Modul Produk', 44, 'Dipakai default COA HPP produk', 1, '2026-04-18 18:07:18', 1, NULL, NULL),
(5, 1, 'pendapatan_lain', 'Default Pendapatan Lain-lain', 'Modul Produk', 34, 'Dipakai default COA pendapatan lain untuk jasa atau non persediaan', 1, '2026-04-18 18:07:18', 1, NULL, NULL),
(6, 1, 'kas_pembelian_tunai', 'Default Kas Pembelian Tunai', 'Faktur Pembelian', 3, 'Dipakai sebagai akun kredit saat faktur pembelian jenis pembayaran tunai', 1, '2026-04-25 08:21:16', 1, NULL, NULL),
(7, 1, 'bank_pembelian_transfer', 'Default Bank Pembelian Transfer', 'Faktur Pembelian', 4, 'Dipakai sebagai akun kredit saat faktur pembelian jenis pembayaran transfer', 1, '2026-04-25 08:21:16', 1, NULL, NULL),
(8, 1, 'utang_usaha', 'Default Utang Usaha', 'Faktur Pembelian', 20, 'Dipakai sebagai akun kredit saat faktur pembelian jenis pembayaran kredit', 1, '2026-04-25 08:21:16', 1, NULL, NULL),
(9, 1, 'BIAYA_KIRIM_PEMBELIAN', 'Akun Biaya Kirim Pembelian', 'Pembelian > Faktur Pembelian', 48, 'Dipakai saat faktur pembelian memiliki ongkir/biaya kirim.', 1, '2026-06-08 10:52:42', NULL, '2026-06-08 10:55:13', NULL),
(10, 1, 'BIAYA_PENGIRIMAN_PENJUALAN', 'Biaya Pengiriman Penjualan', NULL, 59, 'Dipakai saat penyerahan/surat jalan memiliki ongkir yang dibayar UMKM.', 1, '2026-06-09 06:03:52', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tb_konversi_produk`
--

CREATE TABLE `tb_konversi_produk` (
  `id_konversi_produk` bigint UNSIGNED NOT NULL,
  `id_entitas` bigint UNSIGNED NOT NULL,
  `no_konversi_produk` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal_konversi` date NOT NULL,
  `id_gudang` bigint UNSIGNED NOT NULL,
  `id_produk_sumber` bigint UNSIGNED NOT NULL,
  `qty_sumber` decimal(18,3) NOT NULL DEFAULT '0.000',
  `hpp_sumber` decimal(18,2) NOT NULL DEFAULT '0.00',
  `nilai_sumber` decimal(18,2) NOT NULL DEFAULT '0.00',
  `id_produk_tujuan` bigint UNSIGNED NOT NULL,
  `qty_tujuan` decimal(18,3) NOT NULL DEFAULT '0.000',
  `hpp_tujuan` decimal(18,2) NOT NULL DEFAULT '0.00',
  `nilai_tujuan` decimal(18,2) NOT NULL DEFAULT '0.00',
  `status_posting` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `catatan` text COLLATE utf8mb4_unicode_ci,
  `tanggal_dibuat` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dibuat_oleh` bigint UNSIGNED DEFAULT NULL,
  `tanggal_posting` datetime DEFAULT NULL,
  `diposting_oleh` bigint UNSIGNED DEFAULT NULL,
  `tanggal_diubah` datetime DEFAULT NULL,
  `diubah_oleh` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_konversi_produk`
--

INSERT INTO `tb_konversi_produk` (`id_konversi_produk`, `id_entitas`, `no_konversi_produk`, `tanggal_konversi`, `id_gudang`, `id_produk_sumber`, `qty_sumber`, `hpp_sumber`, `nilai_sumber`, `id_produk_tujuan`, `qty_tujuan`, `hpp_tujuan`, `nilai_tujuan`, `status_posting`, `catatan`, `tanggal_dibuat`, `dibuat_oleh`, `tanggal_posting`, `diposting_oleh`, `tanggal_diubah`, `diubah_oleh`) VALUES
(1, 1, 'KVP-2606-0001', '2026-06-09', 1, 3, '5.000', '15666.67', '78333.35', 4, '500.000', '156.67', '78333.35', 'posted', '5 kg Dodol 1 Kg dikonversi menjadi 500 biji Dodol Per Biji', '2026-06-09 04:47:18', 1, '2026-06-09 04:47:21', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tb_log_jurnal_sumber`
--

CREATE TABLE `tb_log_jurnal_sumber` (
  `id_log_jurnal_sumber` bigint UNSIGNED NOT NULL,
  `id_entitas` bigint UNSIGNED NOT NULL,
  `id_jurnal` bigint UNSIGNED NOT NULL,
  `tabel_sumber` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_sumber` bigint UNSIGNED NOT NULL,
  `no_sumber` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kode_jenis_transaksi` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal_dibuat` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_log_jurnal_sumber`
--

INSERT INTO `tb_log_jurnal_sumber` (`id_log_jurnal_sumber`, `id_entitas`, `id_jurnal`, `tabel_sumber`, `id_sumber`, `no_sumber`, `kode_jenis_transaksi`, `tanggal_dibuat`) VALUES
(1, 1, 2, 'tb_faktur_pembelian', 1, 'FPB-0001', 'FAKTUR_PEMBELIAN_TUNAI', '2026-06-09 04:43:11'),
(2, 1, 3, 'tb_pengambilan_bahan', 1, 'PGB-0001', 'PENGAMBILAN_BAHAN_PRODUKSI', '2026-06-09 04:44:24'),
(3, 1, 4, 'tb_biaya_produksi', 1, 'BPR-0001', 'BIAYA_PRODUKSI', '2026-06-09 04:45:55'),
(4, 1, 5, 'tb_hasil_produksi', 1, 'HPR-0001', 'HASIL_PRODUKSI', '2026-06-09 04:46:34'),
(5, 1, 6, 'tb_penyerahan_penjualan', 1, 'PNJ-202606-0001', 'PENYERAHAN_PENJUALAN_HPP', '2026-06-09 05:30:26'),
(6, 1, 7, 'tb_faktur_penjualan', 1, 'FPJ-202606-0001', 'FAKTUR_PENJUALAN_KREDIT', '2026-06-09 05:30:26'),
(7, 1, 8, 'tb_penyerahan_penjualan', 3, 'PNJ-202606-0002', 'PENYERAHAN_PENJUALAN_HPP', '2026-06-09 07:20:01'),
(8, 1, 9, 'tb_faktur_penjualan', 2, 'FPJ-202606-0002', 'FAKTUR_PENJUALAN_TUNAI', '2026-06-09 07:20:01');

-- --------------------------------------------------------

--
-- Table structure for table `tb_log_login`
--

CREATE TABLE `tb_log_login` (
  `id_log_login` bigint UNSIGNED NOT NULL,
  `id_pengguna` bigint UNSIGNED DEFAULT NULL,
  `id_entitas` bigint UNSIGNED DEFAULT NULL,
  `username_input` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `waktu_login` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `waktu_logout` datetime DEFAULT NULL,
  `alamat_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_login` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'berhasil',
  `keterangan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_log_login`
--

INSERT INTO `tb_log_login` (`id_log_login`, `id_pengguna`, `id_entitas`, `username_input`, `waktu_login`, `waktu_logout`, `alamat_ip`, `user_agent`, `status_login`, `keterangan`) VALUES
(1, 1, 1, 'Admin', '2026-04-29 13:12:54', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'berhasil', 'Login berhasil'),
(2, 1, 1, 'Admin', '2026-04-29 13:12:56', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'berhasil', 'Login berhasil'),
(3, 1, 1, 'Admin', '2026-05-04 11:51:44', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'berhasil', 'Login berhasil'),
(4, 1, 1, 'Admin', '2026-05-04 13:06:17', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'berhasil', 'Login berhasil'),
(5, 1, 1, 'Admin', '2026-05-04 13:06:18', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'berhasil', 'Login berhasil'),
(6, 1, 1, 'admin', '2026-05-09 14:23:45', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'berhasil', 'Login berhasil'),
(7, 1, 1, 'admin', '2026-05-11 12:04:18', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'berhasil', 'Login berhasil'),
(8, 1, NULL, 'admin', '2026-05-25 06:59:05', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'gagal', 'Password salah'),
(9, 1, NULL, 'admin', '2026-05-25 06:59:12', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'gagal', 'Password salah'),
(10, 1, 1, 'admin', '2026-05-25 06:59:28', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'berhasil', 'Login berhasil'),
(11, 1, 1, 'admin', '2026-05-25 12:14:53', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'berhasil', 'Login berhasil'),
(12, 1, 1, 'admin', '2026-05-25 13:48:00', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'berhasil', 'Login berhasil'),
(13, 1, 1, 'admin', '2026-06-02 11:00:13', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'berhasil', 'Login berhasil'),
(14, 1, NULL, 'admin', '2026-06-02 14:01:40', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'gagal', 'Password salah'),
(15, 1, 1, 'admin', '2026-06-02 14:01:48', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'berhasil', 'Login berhasil'),
(16, 1, NULL, 'admin', '2026-06-05 14:40:39', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'gagal', 'Password salah'),
(17, 1, NULL, 'admin', '2026-06-05 14:40:45', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'gagal', 'Password salah'),
(18, 1, 1, 'admin', '2026-06-05 14:40:56', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'berhasil', 'Login berhasil'),
(19, 1, NULL, 'admin', '2026-06-06 09:51:10', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'gagal', 'Password salah'),
(20, 1, NULL, 'admin', '2026-06-06 09:51:18', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'gagal', 'Password salah'),
(21, 1, 1, 'admin', '2026-06-06 09:51:28', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'berhasil', 'Login berhasil'),
(22, 1, 1, 'admin', '2026-06-06 09:51:30', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'berhasil', 'Login berhasil'),
(23, 1, 1, 'admin', '2026-06-06 09:51:31', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'berhasil', 'Login berhasil'),
(24, 1, 1, 'admin', '2026-06-06 09:51:31', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'berhasil', 'Login berhasil'),
(25, 1, 1, 'admin', '2026-06-06 10:33:28', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'berhasil', 'Login berhasil'),
(26, 1, NULL, 'admin', '2026-06-06 11:29:24', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'gagal', 'Password salah'),
(27, 1, 1, 'admin', '2026-06-06 11:29:33', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'berhasil', 'Login berhasil'),
(28, NULL, NULL, 'super_admin', '2026-06-06 20:08:48', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'gagal', 'User tidak ditemukan'),
(29, 1, 1, 'admin', '2026-06-06 20:09:03', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'berhasil', 'Login berhasil'),
(30, 1, 1, 'admin', '2026-06-06 20:26:54', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'berhasil', 'Login berhasil'),
(31, 6, 1, 'admin_gudang', '2026-06-06 20:27:11', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'berhasil', 'Login berhasil'),
(32, 5, 1, 'admin_keuangan', '2026-06-06 20:28:11', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'berhasil', 'Login berhasil'),
(33, 3, 1, 'admin_pembelian', '2026-06-06 20:28:41', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'berhasil', 'Login berhasil'),
(34, 2, 1, 'admin_penjualan', '2026-06-06 20:29:01', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'berhasil', 'Login berhasil'),
(35, NULL, NULL, 'super_admin', '2026-06-06 20:36:32', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'gagal', 'User tidak ditemukan'),
(36, NULL, NULL, 'Super_Admin', '2026-06-06 20:37:01', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'gagal', 'User tidak ditemukan'),
(37, NULL, NULL, 'Super_admin', '2026-06-06 20:37:15', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'gagal', 'User tidak ditemukan'),
(38, 1, NULL, 'admin', '2026-06-06 20:37:36', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'gagal', 'Password salah'),
(39, 1, NULL, 'admin', '2026-06-06 20:37:56', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'gagal', 'Password salah'),
(40, 1, 1, 'admin', '2026-06-06 20:38:02', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'berhasil', 'Login berhasil'),
(41, 1, 1, 'admin', '2026-06-06 21:20:38', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'berhasil', 'Login berhasil'),
(42, 1, 1, 'admin', '2026-06-06 21:42:49', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'berhasil', 'Login berhasil'),
(43, 1, NULL, 'admin', '2026-06-06 21:46:35', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'gagal', 'Password salah'),
(44, NULL, NULL, 'aadmin', '2026-06-06 21:46:43', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'gagal', 'User tidak ditemukan'),
(45, 1, NULL, 'admin', '2026-06-06 21:46:50', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'gagal', 'Password salah'),
(46, 1, 1, 'admin', '2026-06-06 21:47:02', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'berhasil', 'Login berhasil'),
(47, 1, 1, 'admin', '2026-06-07 08:59:48', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'berhasil', 'Login berhasil'),
(48, 1, 1, 'admin', '2026-06-07 12:32:32', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'berhasil', 'Login berhasil'),
(49, 1, 1, 'admin', '2026-06-07 13:11:58', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'berhasil', 'Login berhasil'),
(50, 1, 1, 'admin', '2026-06-07 14:15:34', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'berhasil', 'Login berhasil'),
(51, 1, 1, 'admin', '2026-06-07 14:32:06', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'berhasil', 'Login berhasil'),
(52, 1, 1, 'admin', '2026-06-07 14:38:34', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'berhasil', 'Login berhasil'),
(53, 1, 1, 'admin', '2026-06-07 14:41:25', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'berhasil', 'Login berhasil'),
(54, 1, NULL, 'admin', '2026-06-07 14:43:30', NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/149.0.0.0', 'gagal', 'Password salah'),
(55, 1, 1, 'admin', '2026-06-07 14:43:36', NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/149.0.0.0', 'berhasil', 'Login berhasil'),
(56, 1, 1, 'admin', '2026-06-07 14:51:34', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'berhasil', 'Login berhasil'),
(57, 1, 1, 'admin', '2026-06-07 15:03:28', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'berhasil', 'Login berhasil'),
(58, 1, 1, 'admin', '2026-06-07 16:10:57', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'berhasil', 'Login berhasil'),
(59, 1, 1, 'admin', '2026-06-07 16:22:32', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'berhasil', 'Login berhasil'),
(60, 1, 1, 'admin', '2026-06-07 16:31:49', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'berhasil', 'Login berhasil'),
(61, 1, 1, 'admin', '2026-06-07 16:40:01', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'berhasil', 'Login berhasil'),
(62, 1, 1, 'admin', '2026-06-07 16:56:26', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'berhasil', 'Login berhasil'),
(63, 1, 1, 'admin', '2026-06-07 16:59:14', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'berhasil', 'Login berhasil'),
(64, 1, 1, 'admin', '2026-06-07 17:12:50', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'berhasil', 'Login berhasil'),
(65, 1, 1, 'admin', '2026-06-08 09:11:44', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'berhasil', 'Login berhasil'),
(66, 1, 1, 'admin', '2026-06-08 09:47:35', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'berhasil', 'Login berhasil'),
(67, 1, 1, 'admin', '2026-06-08 09:53:11', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'berhasil', 'Login berhasil'),
(68, 1, 1, 'admin', '2026-06-08 09:55:49', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'berhasil', 'Login berhasil'),
(69, NULL, NULL, '@admin_keuangan', '2026-06-08 09:56:55', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'gagal', 'User tidak ditemukan'),
(70, 5, 1, 'admin_keuangan', '2026-06-08 09:57:09', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'berhasil', 'Login berhasil'),
(71, 1, NULL, 'admin', '2026-06-08 09:59:56', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'gagal', 'Password salah'),
(72, 1, 1, 'admin', '2026-06-08 10:00:11', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'berhasil', 'Login berhasil'),
(73, 1, 1, 'admin', '2026-06-08 11:22:57', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'berhasil', 'Login berhasil'),
(74, 1, 1, 'admin', '2026-06-08 12:00:55', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'berhasil', 'Login berhasil'),
(75, 1, NULL, 'admin', '2026-06-08 14:28:47', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'gagal', 'Password salah'),
(76, 1, 1, 'admin', '2026-06-08 14:28:54', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'berhasil', 'Login berhasil'),
(77, 1, 1, 'admin', '2026-06-08 16:35:42', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'berhasil', 'Login berhasil'),
(78, 1, 1, 'admin', '2026-06-09 04:07:37', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'berhasil', 'Login berhasil'),
(79, 1, 1, 'admin', '2026-06-09 04:07:40', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'berhasil', 'Login berhasil'),
(80, 1, NULL, 'admin', '2026-06-09 04:37:59', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'gagal', 'Password salah'),
(81, 1, 1, 'admin', '2026-06-09 04:38:05', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'berhasil', 'Login berhasil'),
(82, 1, 1, 'admin', '2026-06-09 04:39:58', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'berhasil', 'Login berhasil'),
(83, 1, 1, 'admin', '2026-06-09 04:49:35', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'berhasil', 'Login berhasil'),
(84, 1, 1, 'admin', '2026-06-09 05:24:25', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'berhasil', 'Login berhasil'),
(85, 1, 1, 'admin', '2026-06-09 06:10:48', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'berhasil', 'Login berhasil'),
(86, 1, 1, 'admin', '2026-06-09 06:17:05', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'berhasil', 'Login berhasil'),
(87, 1, 1, 'admin', '2026-06-09 07:19:17', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'berhasil', 'Login berhasil'),
(88, 1, 1, 'admin', '2026-06-09 11:55:47', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'berhasil', 'Login berhasil'),
(89, 1, 1, 'admin', '2026-06-09 11:55:50', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'berhasil', 'Login berhasil');

-- --------------------------------------------------------

--
-- Table structure for table `tb_mapping_akun`
--

CREATE TABLE `tb_mapping_akun` (
  `id_mapping_akun` bigint UNSIGNED NOT NULL,
  `id_entitas` bigint UNSIGNED NOT NULL,
  `kode_jenis_transaksi` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jenis_objek` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_objek` bigint UNSIGNED NOT NULL,
  `peran_akun` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_coa` bigint UNSIGNED NOT NULL,
  `status_aktif` tinyint(1) NOT NULL DEFAULT '1',
  `tanggal_dibuat` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dibuat_oleh` bigint UNSIGNED DEFAULT NULL,
  `tanggal_diubah` datetime DEFAULT NULL,
  `diubah_oleh` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_mapping_akun`
--

INSERT INTO `tb_mapping_akun` (`id_mapping_akun`, `id_entitas`, `kode_jenis_transaksi`, `jenis_objek`, `id_objek`, `peran_akun`, `id_coa`, `status_aktif`, `tanggal_dibuat`, `dibuat_oleh`, `tanggal_diubah`, `diubah_oleh`) VALUES
(1, 1, 'ARUS_KAS', 'global', 0, 'akun_kas_bank', 3, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(2, 1, 'KAS_MASUK', 'global', 0, 'akun_kas_default', 3, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(3, 1, 'KAS_MASUK_LAIN', 'global', 0, 'akun_kas_default', 3, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(4, 1, 'KAS_KELUAR', 'global', 0, 'akun_kas_default', 3, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(5, 1, 'KAS_KELUAR_LAIN', 'global', 0, 'akun_kas_default', 3, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(6, 1, 'FAKTUR_PENJUALAN_TUNAI', 'global', 0, 'akun_kas_bank', 3, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(7, 1, 'PEMBAYARAN_PENJUALAN', 'global', 0, 'akun_kas_default', 3, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(8, 1, 'PEMBAYARAN_PENJUALAN_TUNAI', 'global', 0, 'akun_kas_default', 3, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(9, 1, 'FAKTUR_PEMBELIAN_TUNAI', 'global', 0, 'akun_kas_bank', 3, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(10, 1, 'PEMBAYARAN_PEMBELIAN', 'global', 0, 'akun_kas_default', 3, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(11, 1, 'BIAYA_PRODUKSI', 'global', 0, 'akun_kas_default', 3, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(12, 1, 'BIAYA_LISTRIK_PRODUKSI', 'global', 0, 'akun_kas_default', 3, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(13, 1, 'BIAYA_KEMASAN_PRODUKSI', 'global', 0, 'akun_kas_default', 3, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(14, 1, 'BIAYA_LAIN_LAIN_PRODUKSI', 'global', 0, 'akun_kas_default', 3, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(15, 1, 'ARUS_KAS', 'global', 0, 'akun_kas_bank', 4, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(16, 1, 'KAS_MASUK', 'global', 0, 'akun_bank_default', 4, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(17, 1, 'KAS_MASUK_LAIN', 'global', 0, 'akun_bank_default', 4, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(18, 1, 'KAS_KELUAR', 'global', 0, 'akun_bank_default', 4, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(19, 1, 'KAS_KELUAR_LAIN', 'global', 0, 'akun_bank_default', 4, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(20, 1, 'FAKTUR_PENJUALAN_TRANSFER', 'global', 0, 'akun_kas_bank', 4, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(21, 1, 'PEMBAYARAN_PENJUALAN', 'global', 0, 'akun_bank_default', 4, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(22, 1, 'PEMBAYARAN_PENJUALAN_TRANSFER', 'global', 0, 'akun_bank_default', 4, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(23, 1, 'FAKTUR_PEMBELIAN_TRANSFER', 'global', 0, 'akun_kas_bank', 4, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(24, 1, 'PEMBAYARAN_PEMBELIAN', 'global', 0, 'akun_bank_default', 4, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(25, 1, 'BIAYA_PRODUKSI', 'global', 0, 'akun_bank_default', 4, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(26, 1, 'BIAYA_LISTRIK_PRODUKSI', 'global', 0, 'akun_bank_default', 4, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(27, 1, 'BIAYA_KEMASAN_PRODUKSI', 'global', 0, 'akun_bank_default', 4, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(28, 1, 'BIAYA_LAIN_LAIN_PRODUKSI', 'global', 0, 'akun_bank_default', 4, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(29, 1, 'FAKTUR_PENJUALAN_KREDIT', 'global', 0, 'akun_piutang', 5, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(30, 1, 'PEMBAYARAN_PENJUALAN', 'global', 0, 'akun_piutang', 5, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(31, 1, 'PEMBAYARAN_PENJUALAN_TUNAI', 'global', 0, 'akun_piutang', 5, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(32, 1, 'PEMBAYARAN_PENJUALAN_TRANSFER', 'global', 0, 'akun_piutang', 5, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(33, 1, 'FAKTUR_PEMBELIAN', 'global', 0, 'akun_persediaan_bahan', 6, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(34, 1, 'FAKTUR_PEMBELIAN_KREDIT', 'global', 0, 'akun_persediaan_bahan', 6, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(35, 1, 'FAKTUR_PEMBELIAN_TUNAI', 'global', 0, 'akun_persediaan_bahan', 6, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(36, 1, 'FAKTUR_PEMBELIAN_TRANSFER', 'global', 0, 'akun_persediaan_bahan', 6, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(37, 1, 'PENGAMBILAN_BAHAN_PRODUKSI', 'global', 0, 'akun_persediaan_bahan', 6, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(38, 1, 'SALDO_AWAL_STOK', 'global', 0, 'akun_persediaan_bahan', 6, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(39, 1, 'STOK_OPNAME_PLUS', 'global', 0, 'akun_persediaan_bahan', 6, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(40, 1, 'STOK_OPNAME_MINUS', 'global', 0, 'akun_persediaan_bahan', 6, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(41, 1, 'PENYERAHAN_PENJUALAN_HPP', 'global', 0, 'akun_persediaan_produk', 9, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(42, 1, 'HASIL_PRODUKSI', 'global', 0, 'akun_persediaan_produk', 9, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(43, 1, 'SALDO_AWAL_STOK', 'global', 0, 'akun_persediaan_produk', 9, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(44, 1, 'STOK_OPNAME_PLUS', 'global', 0, 'akun_persediaan_produk', 9, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(45, 1, 'STOK_OPNAME_MINUS', 'global', 0, 'akun_persediaan_produk', 9, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(46, 1, 'FAKTUR_PEMBELIAN', 'global', 0, 'akun_utang', 20, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(47, 1, 'FAKTUR_PEMBELIAN_KREDIT', 'global', 0, 'akun_utang', 20, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(48, 1, 'PEMBAYARAN_PEMBELIAN', 'global', 0, 'akun_utang', 20, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(49, 1, 'SALDO_AWAL_STOK', 'global', 0, 'akun_modal', 26, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(50, 1, 'FAKTUR_PENJUALAN_TUNAI', 'global', 0, 'akun_penjualan', 32, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(51, 1, 'FAKTUR_PENJUALAN_TRANSFER', 'global', 0, 'akun_penjualan', 32, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(52, 1, 'FAKTUR_PENJUALAN_KREDIT', 'global', 0, 'akun_penjualan', 33, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(53, 1, 'KAS_MASUK', 'global', 0, 'akun_pendapatan_lain', 34, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(54, 1, 'KAS_MASUK_LAIN', 'global', 0, 'akun_pendapatan_lain', 34, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(55, 1, 'PENGAMBILAN_BAHAN_PRODUKSI', 'global', 0, 'akun_bahan_baku_terpakai', 36, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(56, 1, 'HASIL_PRODUKSI', 'global', 0, 'akun_bahan_baku_terpakai', 36, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(57, 1, 'BIAYA_PRODUKSI', 'global', 0, 'akun_tenaga_kerja_langsung', 39, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(58, 1, 'HASIL_PRODUKSI', 'global', 0, 'akun_tenaga_kerja_langsung', 39, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(59, 1, 'BIAYA_PRODUKSI', 'global', 0, 'akun_listrik_produksi', 41, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(60, 1, 'BIAYA_LISTRIK_PRODUKSI', 'global', 0, 'akun_biaya', 41, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(61, 1, 'PENYERAHAN_PENJUALAN_HPP', 'global', 0, 'akun_hpp', 44, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(62, 1, 'KAS_KELUAR', 'global', 0, 'akun_beban_lain', 52, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(63, 1, 'KAS_KELUAR_LAIN', 'global', 0, 'akun_beban_lain', 52, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(64, 1, 'BIAYA_PRODUKSI', 'global', 0, 'akun_kemasan_produksi', 53, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(65, 1, 'BIAYA_KEMASAN_PRODUKSI', 'global', 0, 'akun_biaya', 53, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(66, 1, 'BIAYA_PRODUKSI', 'global', 0, 'akun_bop_lain', 54, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(67, 1, 'BIAYA_LAIN_LAIN_PRODUKSI', 'global', 0, 'akun_biaya', 54, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(68, 1, 'HASIL_PRODUKSI', 'global', 0, 'akun_bop_lain', 54, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(69, 1, 'FAKTUR_PEMBELIAN', 'global', 0, 'akun_pajak_masukan', 55, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(70, 1, 'FAKTUR_PEMBELIAN_KREDIT', 'global', 0, 'akun_pajak_masukan', 55, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(71, 1, 'FAKTUR_PEMBELIAN_TUNAI', 'global', 0, 'akun_pajak_masukan', 55, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(72, 1, 'FAKTUR_PEMBELIAN_TRANSFER', 'global', 0, 'akun_pajak_masukan', 55, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(73, 1, 'FAKTUR_PENJUALAN_TUNAI', 'global', 0, 'akun_pajak_keluaran', 56, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(74, 1, 'FAKTUR_PENJUALAN_TRANSFER', 'global', 0, 'akun_pajak_keluaran', 56, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(75, 1, 'FAKTUR_PENJUALAN_KREDIT', 'global', 0, 'akun_pajak_keluaran', 56, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(76, 1, 'STOK_OPNAME_PLUS', 'global', 0, 'akun_pendapatan_selisih_stok', 57, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(77, 1, 'STOK_OPNAME_MINUS', 'global', 0, 'akun_beban_selisih_stok', 58, 1, '2026-04-28 15:47:55', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tb_mapping_laporan`
--

CREATE TABLE `tb_mapping_laporan` (
  `id_mapping_laporan` bigint UNSIGNED NOT NULL,
  `id_entitas` bigint UNSIGNED NOT NULL,
  `kode_laporan` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_laporan` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kategori_kode` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kategori_label` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sub_kode` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sub_label` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `parent_id` bigint UNSIGNED DEFAULT NULL,
  `tipe_baris` enum('HEADER','DETAIL','SUBTOTAL','TOTAL') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DETAIL',
  `kelompok_laporan` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kelompok_arus_kas` enum('OPERASIONAL','INVESTASI','PENDANAAN','TIDAK_TERAPKAN') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'TIDAK_TERAPKAN',
  `arah_saldo` enum('NORMAL','DEBIT','KREDIT','NETTO') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'NORMAL',
  `operator_nilai` enum('TAMBAH','KURANG') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'TAMBAH',
  `rumus_total` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `urutan` int NOT NULL DEFAULT '0',
  `sub_urutan` int NOT NULL DEFAULT '0',
  `status_aktif` tinyint(1) NOT NULL DEFAULT '1',
  `tanggal_dibuat` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dibuat_oleh` bigint UNSIGNED DEFAULT NULL,
  `tanggal_diubah` datetime DEFAULT NULL,
  `diubah_oleh` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_mapping_laporan`
--

INSERT INTO `tb_mapping_laporan` (`id_mapping_laporan`, `id_entitas`, `kode_laporan`, `nama_laporan`, `kategori_kode`, `kategori_label`, `sub_kode`, `sub_label`, `parent_id`, `tipe_baris`, `kelompok_laporan`, `kelompok_arus_kas`, `arah_saldo`, `operator_nilai`, `rumus_total`, `urutan`, `sub_urutan`, `status_aktif`, `tanggal_dibuat`, `dibuat_oleh`, `tanggal_diubah`, `diubah_oleh`) VALUES
(1, 1, 'SALDO_AKUN', 'Saldo Akun', 'ASET', 'Aset', 'saldo-akun-aset', 'Aset', NULL, 'DETAIL', 'ASET', 'TIDAK_TERAPKAN', 'DEBIT', 'TAMBAH', NULL, 10, 10, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(2, 1, 'SALDO_AKUN', 'Saldo Akun', 'LIABILITAS', 'Liabilitas', 'saldo-akun-liabilitas', 'Liabilitas', NULL, 'DETAIL', 'LIABILITAS', 'TIDAK_TERAPKAN', 'KREDIT', 'TAMBAH', NULL, 20, 10, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(3, 1, 'SALDO_AKUN', 'Saldo Akun', 'EKUITAS', 'Ekuitas', 'saldo-akun-ekuitas', 'Ekuitas', NULL, 'DETAIL', 'EKUITAS', 'TIDAK_TERAPKAN', 'KREDIT', 'TAMBAH', NULL, 30, 10, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(4, 1, 'SALDO_AKUN', 'Saldo Akun', 'PENDAPATAN', 'Pendapatan', 'saldo-akun-pendapatan', 'Pendapatan', NULL, 'DETAIL', 'PENDAPATAN', 'TIDAK_TERAPKAN', 'KREDIT', 'TAMBAH', NULL, 40, 10, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(5, 1, 'SALDO_AKUN', 'Saldo Akun', 'HPP', 'Harga Pokok / Produksi', 'saldo-akun-hpp', 'Harga Pokok / Produksi', NULL, 'DETAIL', 'HPP', 'TIDAK_TERAPKAN', 'DEBIT', 'TAMBAH', NULL, 50, 10, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(6, 1, 'SALDO_AKUN', 'Saldo Akun', 'BEBAN', 'Beban', 'saldo-akun-beban', 'Beban', NULL, 'DETAIL', 'BEBAN', 'TIDAK_TERAPKAN', 'DEBIT', 'TAMBAH', NULL, 60, 10, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(7, 1, 'LABA_RUGI', 'Laba Rugi', 'PENDAPATAN', 'Pendapatan', 'penjualan-produk', 'Penjualan Produk', NULL, 'DETAIL', 'PENDAPATAN', 'TIDAK_TERAPKAN', 'KREDIT', 'TAMBAH', NULL, 10, 10, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(8, 1, 'LABA_RUGI', 'Laba Rugi', 'PENDAPATAN', 'Pendapatan', 'pendapatan-lain', 'Pendapatan Lain-lain', NULL, 'DETAIL', 'PENDAPATAN', 'TIDAK_TERAPKAN', 'KREDIT', 'TAMBAH', NULL, 10, 20, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(9, 1, 'LABA_RUGI', 'Laba Rugi', 'TOTAL_PENDAPATAN', 'Total Pendapatan', 'total-pendapatan', 'Total Pendapatan', NULL, 'SUBTOTAL', 'PENDAPATAN', 'TIDAK_TERAPKAN', 'KREDIT', 'TAMBAH', 'SUM:PENDAPATAN', 15, 10, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(10, 1, 'LABA_RUGI', 'Laba Rugi', 'HPP', 'Harga Pokok Penjualan', 'hpp-penjualan', 'Harga Pokok Penjualan', NULL, 'DETAIL', 'HPP', 'TIDAK_TERAPKAN', 'DEBIT', 'KURANG', NULL, 20, 10, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(11, 1, 'LABA_RUGI', 'Laba Rugi', 'LABA_KOTOR', 'Laba Kotor', 'laba-kotor', 'Laba Kotor', NULL, 'SUBTOTAL', 'LABA', 'TIDAK_TERAPKAN', 'NETTO', 'TAMBAH', 'TOTAL_PENDAPATAN+HPP', 25, 10, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(12, 1, 'LABA_RUGI', 'Laba Rugi', 'BEBAN_OPERASIONAL', 'Beban Operasional', 'beban-gaji-administrasi', 'Beban Gaji Administrasi', NULL, 'DETAIL', 'BEBAN', 'TIDAK_TERAPKAN', 'DEBIT', 'KURANG', NULL, 30, 10, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(13, 1, 'LABA_RUGI', 'Laba Rugi', 'BEBAN_OPERASIONAL', 'Beban Operasional', 'beban-pemasaran-promosi', 'Beban Pemasaran dan Promosi', NULL, 'DETAIL', 'BEBAN', 'TIDAK_TERAPKAN', 'DEBIT', 'KURANG', NULL, 30, 20, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(14, 1, 'LABA_RUGI', 'Laba Rugi', 'BEBAN_OPERASIONAL', 'Beban Operasional', 'beban-transportasi', 'Beban Transportasi', NULL, 'DETAIL', 'BEBAN', 'TIDAK_TERAPKAN', 'DEBIT', 'KURANG', NULL, 30, 30, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(15, 1, 'LABA_RUGI', 'Laba Rugi', 'BEBAN_OPERASIONAL', 'Beban Operasional', 'beban-atk-kebersihan', 'Beban ATK dan Kebersihan', NULL, 'DETAIL', 'BEBAN', 'TIDAK_TERAPKAN', 'DEBIT', 'KURANG', NULL, 30, 40, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(16, 1, 'LABA_RUGI', 'Laba Rugi', 'BEBAN_OPERASIONAL', 'Beban Operasional', 'beban-listrik-air-kantor', 'Beban Listrik dan Air Kantor', NULL, 'DETAIL', 'BEBAN', 'TIDAK_TERAPKAN', 'DEBIT', 'KURANG', NULL, 30, 50, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(17, 1, 'LABA_RUGI', 'Laba Rugi', 'BEBAN_OPERASIONAL', 'Beban Operasional', 'beban-penyusutan-non-produksi', 'Beban Penyusutan Non Produksi', NULL, 'DETAIL', 'BEBAN', 'TIDAK_TERAPKAN', 'DEBIT', 'KURANG', NULL, 30, 60, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(18, 1, 'LABA_RUGI', 'Laba Rugi', 'BEBAN_OPERASIONAL', 'Beban Operasional', 'beban-lain-lain', 'Beban Lain-lain', NULL, 'DETAIL', 'BEBAN', 'TIDAK_TERAPKAN', 'DEBIT', 'KURANG', NULL, 30, 70, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(19, 1, 'LABA_RUGI', 'Laba Rugi', 'BEBAN_OPERASIONAL', 'Beban Operasional', 'beban-selisih-stok', 'Beban Selisih Stok', NULL, 'DETAIL', 'BEBAN', 'TIDAK_TERAPKAN', 'DEBIT', 'KURANG', NULL, 30, 80, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(20, 1, 'LABA_RUGI', 'Laba Rugi', 'TOTAL_BEBAN', 'Total Beban Operasional', 'total-beban-operasional', 'Total Beban Operasional', NULL, 'SUBTOTAL', 'BEBAN', 'TIDAK_TERAPKAN', 'DEBIT', 'KURANG', 'SUM:BEBAN_OPERASIONAL', 35, 10, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(21, 1, 'LABA_RUGI', 'Laba Rugi', 'LABA_RUGI_BERSIH', 'Laba / Rugi Bersih', 'laba-rugi-bersih', 'Laba / Rugi Bersih', NULL, 'TOTAL', 'LABA', 'TIDAK_TERAPKAN', 'NETTO', 'TAMBAH', 'TOTAL_PENDAPATAN+HPP+TOTAL_BEBAN', 99, 10, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(22, 1, 'NERACA', 'Neraca', 'ASET_LANCAR', 'Aset Lancar', 'kas-dan-bank', 'Kas dan Bank', NULL, 'DETAIL', 'ASET', 'TIDAK_TERAPKAN', 'DEBIT', 'TAMBAH', NULL, 10, 10, 1, '2026-04-28 15:47:55', 1, '2026-05-25 14:02:45', 1),
(23, 1, 'NERACA', 'Neraca', 'ASET_LANCAR', 'Aset Lancar', 'piutang-usaha', 'Piutang Usaha', NULL, 'DETAIL', 'ASET', 'TIDAK_TERAPKAN', 'DEBIT', 'TAMBAH', NULL, 10, 20, 1, '2026-04-28 15:47:55', 1, '2026-05-25 14:02:45', 1),
(24, 1, 'NERACA', 'Neraca', 'ASET_LANCAR', 'Aset Lancar', 'persediaan', 'Persediaan', NULL, 'DETAIL', 'ASET', 'TIDAK_TERAPKAN', 'DEBIT', 'TAMBAH', NULL, 10, 30, 1, '2026-04-28 15:47:55', 1, '2026-05-25 14:02:45', 1),
(25, 1, 'NERACA', 'Neraca', 'ASET_LANCAR', 'Aset Lancar', 'aset-lancar-lain', 'Aset Lancar Lainnya', NULL, 'DETAIL', 'ASET', 'TIDAK_TERAPKAN', 'DEBIT', 'TAMBAH', NULL, 10, 40, 1, '2026-04-28 15:47:55', 1, '2026-05-25 14:02:45', 1),
(26, 1, 'NERACA', 'Neraca', 'TOTAL_ASET_LANCAR', 'Total Aset Lancar', 'total-aset-lancar', 'Total Aset Lancar', NULL, 'SUBTOTAL', 'ASET', 'TIDAK_TERAPKAN', 'DEBIT', 'TAMBAH', 'SUM:ASET_LANCAR', 15, 10, 1, '2026-04-28 15:47:55', 1, '2026-05-25 14:02:45', 1),
(27, 1, 'NERACA', 'Neraca', 'ASET_TETAP', 'Aset Tetap', 'aset-tetap', 'Aset Tetap', NULL, 'DETAIL', 'ASET', 'TIDAK_TERAPKAN', 'DEBIT', 'TAMBAH', NULL, 20, 10, 1, '2026-04-28 15:47:55', 1, '2026-05-25 14:02:45', 1),
(28, 1, 'NERACA', 'Neraca', 'ASET_TETAP', 'Aset Tetap', 'akumulasi-penyusutan', 'Akumulasi Penyusutan', NULL, 'DETAIL', 'ASET', 'TIDAK_TERAPKAN', 'KREDIT', 'KURANG', NULL, 20, 20, 1, '2026-04-28 15:47:55', 1, '2026-05-25 14:02:45', 1),
(29, 1, 'NERACA', 'Neraca', 'TOTAL_ASET_TETAP', 'Total Aset Tetap', 'total-aset-tetap', 'Total Aset Tetap', NULL, 'SUBTOTAL', 'ASET', 'TIDAK_TERAPKAN', 'DEBIT', 'TAMBAH', 'SUM:ASET_TETAP', 25, 10, 1, '2026-04-28 15:47:55', 1, '2026-05-25 14:02:45', 1),
(30, 1, 'NERACA', 'Neraca', 'TOTAL_ASET', 'Total Aset', 'total-aset', 'Total Aset', NULL, 'TOTAL', 'ASET', 'TIDAK_TERAPKAN', 'DEBIT', 'TAMBAH', 'TOTAL_ASET_LANCAR+TOTAL_ASET_TETAP', 29, 10, 1, '2026-04-28 15:47:55', 1, '2026-05-25 14:02:45', 1),
(31, 1, 'NERACA', 'Neraca', 'LIABILITAS_JANGKA_PENDEK', 'Liabilitas Jangka Pendek', 'utang-usaha', 'Utang Usaha', NULL, 'DETAIL', 'LIABILITAS', 'TIDAK_TERAPKAN', 'KREDIT', 'TAMBAH', NULL, 40, 10, 1, '2026-04-28 15:47:55', 1, '2026-05-25 14:02:45', 1),
(32, 1, 'NERACA', 'Neraca', 'LIABILITAS_JANGKA_PENDEK', 'Liabilitas Jangka Pendek', 'utang-lain', 'Utang Lainnya', NULL, 'DETAIL', 'LIABILITAS', 'TIDAK_TERAPKAN', 'KREDIT', 'TAMBAH', NULL, 40, 20, 1, '2026-04-28 15:47:55', 1, '2026-05-25 14:02:45', 1),
(33, 1, 'NERACA', 'Neraca', 'TOTAL_LIABILITAS', 'Total Liabilitas', 'total-liabilitas', 'Total Liabilitas', NULL, 'SUBTOTAL', 'LIABILITAS', 'TIDAK_TERAPKAN', 'KREDIT', 'TAMBAH', 'SUM:LIABILITAS_JANGKA_PENDEK', 49, 10, 1, '2026-04-28 15:47:55', 1, '2026-05-25 14:02:45', 1),
(34, 1, 'NERACA', 'Neraca', 'EKUITAS', 'Ekuitas', 'modal-pemilik', 'Modal Pemilik', NULL, 'DETAIL', 'EKUITAS', 'TIDAK_TERAPKAN', 'KREDIT', 'TAMBAH', NULL, 60, 10, 1, '2026-04-28 15:47:55', 1, '2026-05-25 14:02:45', 1),
(35, 1, 'NERACA', 'Neraca', 'EKUITAS', 'Ekuitas', 'prive-pemilik', 'Prive Pemilik', NULL, 'DETAIL', 'EKUITAS', 'TIDAK_TERAPKAN', 'DEBIT', 'KURANG', NULL, 60, 20, 1, '2026-04-28 15:47:55', 1, '2026-05-25 14:02:45', 1),
(36, 1, 'NERACA', 'Neraca', 'EKUITAS', 'Ekuitas', 'laba-ditahan', 'Laba Ditahan', NULL, 'DETAIL', 'EKUITAS', 'TIDAK_TERAPKAN', 'KREDIT', 'TAMBAH', NULL, 60, 30, 1, '2026-04-28 15:47:55', 1, '2026-05-25 14:02:45', 1),
(37, 1, 'NERACA', 'Neraca', 'EKUITAS', 'Ekuitas', 'laba-tahun-berjalan', 'Laba Tahun Berjalan', NULL, 'DETAIL', 'EKUITAS', 'TIDAK_TERAPKAN', 'KREDIT', 'TAMBAH', NULL, 60, 40, 1, '2026-04-28 15:47:55', 1, '2026-05-25 14:02:45', 1),
(38, 1, 'NERACA', 'Neraca', 'TOTAL_EKUITAS', 'Total Ekuitas', 'total-ekuitas', 'Total Ekuitas', NULL, 'SUBTOTAL', 'EKUITAS', 'TIDAK_TERAPKAN', 'KREDIT', 'TAMBAH', 'SUM:EKUITAS', 69, 10, 1, '2026-04-28 15:47:55', 1, '2026-05-25 14:02:45', 1),
(39, 1, 'NERACA', 'Neraca', 'TOTAL_LIABILITAS_EKUITAS', 'Total Liabilitas dan Ekuitas', 'total-liabilitas-ekuitas', 'Total Liabilitas dan Ekuitas', NULL, 'TOTAL', 'LIABILITAS_EKUITAS', 'TIDAK_TERAPKAN', 'KREDIT', 'TAMBAH', 'TOTAL_LIABILITAS+TOTAL_EKUITAS', 99, 10, 1, '2026-04-28 15:47:55', 1, '2026-05-25 14:02:45', 1),
(40, 1, 'ARUS_KAS', 'Arus Kas', 'OPERASIONAL', 'Aktivitas Operasional', 'penerimaan-penjualan', 'Penerimaan kas dari penjualan produk', NULL, 'DETAIL', 'ARUS_KAS', 'OPERASIONAL', 'NETTO', 'TAMBAH', NULL, 10, 10, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(41, 1, 'ARUS_KAS', 'Arus Kas', 'OPERASIONAL', 'Aktivitas Operasional', 'pembelian-bahan-baku', 'Pengeluaran kas untuk pembelian bahan baku', NULL, 'DETAIL', 'ARUS_KAS', 'OPERASIONAL', 'NETTO', 'KURANG', NULL, 10, 20, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(42, 1, 'ARUS_KAS', 'Arus Kas', 'OPERASIONAL', 'Aktivitas Operasional', 'biaya-produksi', 'Pengeluaran kas untuk biaya produksi', NULL, 'DETAIL', 'ARUS_KAS', 'OPERASIONAL', 'NETTO', 'KURANG', NULL, 10, 30, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(43, 1, 'ARUS_KAS', 'Arus Kas', 'OPERASIONAL', 'Aktivitas Operasional', 'kas-masuk-lain', 'Penerimaan kas lainnya', NULL, 'DETAIL', 'ARUS_KAS', 'OPERASIONAL', 'NETTO', 'TAMBAH', NULL, 10, 40, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(44, 1, 'ARUS_KAS', 'Arus Kas', 'OPERASIONAL', 'Aktivitas Operasional', 'kas-keluar-lain', 'Pengeluaran kas lainnya', NULL, 'DETAIL', 'ARUS_KAS', 'OPERASIONAL', 'NETTO', 'KURANG', NULL, 10, 50, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(45, 1, 'ARUS_KAS', 'Arus Kas', 'TOTAL_OPERASIONAL', 'Total Aktivitas Operasional', 'total-operasional', 'Arus Kas Bersih dari Aktivitas Operasional', NULL, 'SUBTOTAL', 'ARUS_KAS', 'OPERASIONAL', 'NETTO', 'TAMBAH', 'SUM:OPERASIONAL', 19, 10, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(46, 1, 'ARUS_KAS', 'Arus Kas', 'INVESTASI', 'Aktivitas Investasi', 'pembelian-aset-tetap', 'Pengeluaran kas untuk pembelian aset tetap', NULL, 'DETAIL', 'ARUS_KAS', 'INVESTASI', 'NETTO', 'KURANG', NULL, 30, 10, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(47, 1, 'ARUS_KAS', 'Arus Kas', 'TOTAL_INVESTASI', 'Total Aktivitas Investasi', 'total-investasi', 'Arus Kas Bersih dari Aktivitas Investasi', NULL, 'SUBTOTAL', 'ARUS_KAS', 'INVESTASI', 'NETTO', 'TAMBAH', 'SUM:INVESTASI', 39, 10, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(48, 1, 'ARUS_KAS', 'Arus Kas', 'PENDANAAN', 'Aktivitas Pendanaan', 'setoran-modal', 'Penerimaan kas dari setoran modal', NULL, 'DETAIL', 'ARUS_KAS', 'PENDANAAN', 'NETTO', 'TAMBAH', NULL, 50, 10, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(49, 1, 'ARUS_KAS', 'Arus Kas', 'PENDANAAN', 'Aktivitas Pendanaan', 'prive-pemilik', 'Pengeluaran kas untuk prive pemilik', NULL, 'DETAIL', 'ARUS_KAS', 'PENDANAAN', 'NETTO', 'KURANG', NULL, 50, 20, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(50, 1, 'ARUS_KAS', 'Arus Kas', 'TOTAL_PENDANAAN', 'Total Aktivitas Pendanaan', 'total-pendanaan', 'Arus Kas Bersih dari Aktivitas Pendanaan', NULL, 'SUBTOTAL', 'ARUS_KAS', 'PENDANAAN', 'NETTO', 'TAMBAH', 'SUM:PENDANAAN', 59, 10, 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(51, 1, 'ARUS_KAS', 'Arus Kas', 'KENAIKAN_KAS', 'Kenaikan / Penurunan Kas', 'kenaikan-penurunan-kas', 'Kenaikan / Penurunan Kas Bersih', NULL, 'TOTAL', 'ARUS_KAS', 'TIDAK_TERAPKAN', 'NETTO', 'TAMBAH', 'TOTAL_OPERASIONAL+TOTAL_INVESTASI+TOTAL_PENDANAAN', 99, 10, 1, '2026-04-28 15:47:55', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tb_mapping_laporan_detail`
--

CREATE TABLE `tb_mapping_laporan_detail` (
  `id_mapping_laporan_detail` bigint UNSIGNED NOT NULL,
  `id_mapping_laporan` bigint UNSIGNED NOT NULL,
  `id_entitas` bigint UNSIGNED NOT NULL,
  `tipe_sumber` enum('COA','KATEGORI_COA','KODE_COA_PREFIX','KODE_JENIS_TRANSAKSI','TABEL_SUMBER','MAPPING_AKUN') COLLATE utf8mb4_unicode_ci NOT NULL,
  `nilai_sumber` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_coa` bigint UNSIGNED DEFAULT NULL,
  `arah_saldo` enum('NORMAL','DEBIT_MINUS_KREDIT','KREDIT_MINUS_DEBIT','KAS_MASUK','KAS_KELUAR','NETTO') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'NORMAL',
  `operator_nilai` enum('TAMBAH','KURANG') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'TAMBAH',
  `keterangan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_aktif` tinyint(1) NOT NULL DEFAULT '1',
  `tanggal_dibuat` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dibuat_oleh` bigint UNSIGNED DEFAULT NULL,
  `tanggal_diubah` datetime DEFAULT NULL,
  `diubah_oleh` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_mapping_laporan_detail`
--

INSERT INTO `tb_mapping_laporan_detail` (`id_mapping_laporan_detail`, `id_mapping_laporan`, `id_entitas`, `tipe_sumber`, `nilai_sumber`, `id_coa`, `arah_saldo`, `operator_nilai`, `keterangan`, `status_aktif`, `tanggal_dibuat`, `dibuat_oleh`, `tanggal_diubah`, `diubah_oleh`) VALUES
(1, 1, 1, 'KATEGORI_COA', 'aset', NULL, 'DEBIT_MINUS_KREDIT', 'TAMBAH', 'Saldo semua akun aset', 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(2, 6, 1, 'KATEGORI_COA', 'beban', NULL, 'DEBIT_MINUS_KREDIT', 'TAMBAH', 'Saldo semua akun beban', 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(3, 3, 1, 'KATEGORI_COA', 'ekuitas', NULL, 'KREDIT_MINUS_DEBIT', 'TAMBAH', 'Saldo semua akun ekuitas', 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(4, 5, 1, 'KATEGORI_COA', 'hpp', NULL, 'DEBIT_MINUS_KREDIT', 'TAMBAH', 'Saldo semua akun HPP/produksi', 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(5, 2, 1, 'KATEGORI_COA', 'liabilitas', NULL, 'KREDIT_MINUS_DEBIT', 'TAMBAH', 'Saldo semua akun liabilitas', 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(6, 4, 1, 'KATEGORI_COA', 'pendapatan', NULL, 'KREDIT_MINUS_DEBIT', 'TAMBAH', 'Saldo semua akun pendapatan', 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(8, 15, 1, 'KODE_COA_PREFIX', '6400', NULL, 'DEBIT_MINUS_KREDIT', 'KURANG', 'Beban ATK dan kebersihan', 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(9, 12, 1, 'KODE_COA_PREFIX', '6100', NULL, 'DEBIT_MINUS_KREDIT', 'KURANG', 'Beban gaji administrasi', 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(10, 18, 1, 'KODE_COA_PREFIX', '6700', NULL, 'DEBIT_MINUS_KREDIT', 'KURANG', 'Beban lain-lain', 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(11, 16, 1, 'KODE_COA_PREFIX', '6500', NULL, 'DEBIT_MINUS_KREDIT', 'KURANG', 'Beban listrik dan air kantor', 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(12, 13, 1, 'KODE_COA_PREFIX', '6200', NULL, 'DEBIT_MINUS_KREDIT', 'KURANG', 'Beban pemasaran dan promosi', 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(13, 17, 1, 'KODE_COA_PREFIX', '6600', NULL, 'DEBIT_MINUS_KREDIT', 'KURANG', 'Beban penyusutan non produksi', 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(14, 19, 1, 'KODE_COA_PREFIX', '6800', NULL, 'DEBIT_MINUS_KREDIT', 'KURANG', 'Beban selisih stok', 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(15, 14, 1, 'KODE_COA_PREFIX', '6300', NULL, 'DEBIT_MINUS_KREDIT', 'KURANG', 'Beban transportasi', 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(16, 10, 1, 'KODE_COA_PREFIX', '54', NULL, 'DEBIT_MINUS_KREDIT', 'KURANG', 'Harga pokok penjualan: 5400', 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(17, 8, 1, 'KODE_COA_PREFIX', '42', NULL, 'KREDIT_MINUS_DEBIT', 'TAMBAH', 'Pendapatan lain dan pendapatan selisih stok: 4200, 4210', 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(18, 7, 1, 'KODE_COA_PREFIX', '41', NULL, 'KREDIT_MINUS_DEBIT', 'TAMBAH', 'Penjualan produk: 4100, 4110, 4120', 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(23, 25, 1, 'KODE_COA_PREFIX', '1180', NULL, 'DEBIT_MINUS_KREDIT', 'TAMBAH', 'Uang Muka Pembelian', 1, '2026-04-28 15:47:55', 1, '2026-05-25 14:02:45', 1),
(24, 25, 1, 'KODE_COA_PREFIX', '1190', NULL, 'DEBIT_MINUS_KREDIT', 'TAMBAH', 'Pajak Masukan', 1, '2026-04-28 15:47:55', 1, '2026-05-25 14:02:45', 1),
(25, 22, 1, 'KODE_COA_PREFIX', '111', NULL, 'DEBIT_MINUS_KREDIT', 'TAMBAH', 'Kas', 1, '2026-04-28 15:47:55', 1, '2026-05-25 14:02:45', 1),
(26, 22, 1, 'KODE_COA_PREFIX', '112', NULL, 'DEBIT_MINUS_KREDIT', 'TAMBAH', 'Bank', 1, '2026-04-28 15:47:55', 1, '2026-05-25 14:02:45', 1),
(27, 24, 1, 'KODE_COA_PREFIX', '1140', NULL, 'DEBIT_MINUS_KREDIT', 'TAMBAH', 'Persediaan Bahan Baku', 1, '2026-04-28 15:47:55', 1, '2026-05-25 14:02:45', 1),
(28, 24, 1, 'KODE_COA_PREFIX', '1150', NULL, 'DEBIT_MINUS_KREDIT', 'TAMBAH', 'Persediaan Bahan Penolong', 1, '2026-04-28 15:47:55', 1, '2026-05-25 14:02:45', 1),
(29, 24, 1, 'KODE_COA_PREFIX', '1160', NULL, 'DEBIT_MINUS_KREDIT', 'TAMBAH', 'Persediaan Kemasan', 1, '2026-04-28 15:47:55', 1, '2026-05-25 14:02:45', 1),
(30, 24, 1, 'KODE_COA_PREFIX', '1170', NULL, 'DEBIT_MINUS_KREDIT', 'TAMBAH', 'Persediaan Barang Jadi', 1, '2026-04-28 15:47:55', 1, '2026-05-25 14:02:45', 1),
(31, 23, 1, 'KODE_COA_PREFIX', '1130', NULL, 'DEBIT_MINUS_KREDIT', 'TAMBAH', 'Piutang Usaha', 1, '2026-04-28 15:47:55', 1, '2026-05-25 14:02:45', 1),
(32, 28, 1, 'KODE_COA_PREFIX', '1220', NULL, 'KREDIT_MINUS_DEBIT', 'KURANG', 'Akumulasi Penyusutan Peralatan', 1, '2026-04-28 15:47:55', 1, '2026-05-25 14:02:45', 1),
(33, 28, 1, 'KODE_COA_PREFIX', '1240', NULL, 'KREDIT_MINUS_DEBIT', 'KURANG', 'Akumulasi Penyusutan Kendaraan', 1, '2026-04-28 15:47:55', 1, '2026-05-25 14:02:45', 1),
(34, 28, 1, 'KODE_COA_PREFIX', '1260', NULL, 'KREDIT_MINUS_DEBIT', 'KURANG', 'Akumulasi Penyusutan Peralatan Kantor', 1, '2026-04-28 15:47:55', 1, '2026-05-25 14:02:45', 1),
(35, 27, 1, 'KODE_COA_PREFIX', '1210', NULL, 'DEBIT_MINUS_KREDIT', 'TAMBAH', 'Peralatan Produksi', 1, '2026-04-28 15:47:55', 1, '2026-05-25 14:02:45', 1),
(36, 27, 1, 'KODE_COA_PREFIX', '1230', NULL, 'DEBIT_MINUS_KREDIT', 'TAMBAH', 'Kendaraan', 1, '2026-04-28 15:47:55', 1, '2026-05-25 14:02:45', 1),
(37, 27, 1, 'KODE_COA_PREFIX', '1250', NULL, 'DEBIT_MINUS_KREDIT', 'TAMBAH', 'Peralatan Kantor', 1, '2026-04-28 15:47:55', 1, '2026-05-25 14:02:45', 1),
(38, 36, 1, 'KODE_COA_PREFIX', '3300', NULL, 'KREDIT_MINUS_DEBIT', 'TAMBAH', 'Laba Ditahan', 1, '2026-04-28 15:47:55', 1, '2026-05-25 14:02:45', 1),
(39, 37, 1, 'KODE_COA_PREFIX', '3400', NULL, 'KREDIT_MINUS_DEBIT', 'TAMBAH', 'Laba Tahun Berjalan', 1, '2026-04-28 15:47:55', 1, '2026-05-25 14:02:45', 1),
(40, 34, 1, 'KODE_COA_PREFIX', '3100', NULL, 'KREDIT_MINUS_DEBIT', 'TAMBAH', 'Modal Pemilik', 1, '2026-04-28 15:47:55', 1, '2026-05-25 14:02:45', 1),
(41, 35, 1, 'KODE_COA_PREFIX', '3200', NULL, 'DEBIT_MINUS_KREDIT', 'KURANG', 'Prive Pemilik', 1, '2026-04-28 15:47:55', 1, '2026-05-25 14:02:45', 1),
(42, 32, 1, 'KODE_COA_PREFIX', '2120', NULL, 'KREDIT_MINUS_DEBIT', 'TAMBAH', 'Utang Gaji', 1, '2026-04-28 15:47:55', 1, '2026-05-25 14:02:45', 1),
(43, 32, 1, 'KODE_COA_PREFIX', '2130', NULL, 'KREDIT_MINUS_DEBIT', 'TAMBAH', 'Utang Biaya', 1, '2026-04-28 15:47:55', 1, '2026-05-25 14:02:45', 1),
(44, 32, 1, 'KODE_COA_PREFIX', '2140', NULL, 'KREDIT_MINUS_DEBIT', 'TAMBAH', 'Utang Pajak', 1, '2026-04-28 15:47:55', 1, '2026-05-25 14:02:45', 1),
(45, 32, 1, 'KODE_COA_PREFIX', '2141', NULL, 'KREDIT_MINUS_DEBIT', 'TAMBAH', 'Pajak Keluaran', 1, '2026-04-28 15:47:55', 1, '2026-05-25 14:02:45', 1),
(46, 32, 1, 'KODE_COA_PREFIX', '2150', NULL, 'KREDIT_MINUS_DEBIT', 'TAMBAH', 'Pendapatan Diterima Dimuka', 1, '2026-04-28 15:47:55', 1, '2026-05-25 14:02:45', 1),
(47, 31, 1, 'KODE_COA_PREFIX', '2110', NULL, 'KREDIT_MINUS_DEBIT', 'TAMBAH', 'Utang Usaha', 1, '2026-04-28 15:47:55', 1, '2026-05-25 14:02:45', 1),
(54, 46, 1, 'KODE_JENIS_TRANSAKSI', 'PEMBELIAN_ASET_TETAP', NULL, 'KAS_KELUAR', 'KURANG', 'Pembelian aset tetap', 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(55, 42, 1, 'KODE_JENIS_TRANSAKSI', 'BIAYA_PRODUKSI', NULL, 'KAS_KELUAR', 'KURANG', 'Pembayaran biaya produksi', 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(56, 42, 1, 'KODE_JENIS_TRANSAKSI', 'BIAYA_LISTRIK_PRODUKSI', NULL, 'KAS_KELUAR', 'KURANG', 'Pembayaran biaya listrik produksi', 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(57, 42, 1, 'KODE_JENIS_TRANSAKSI', 'BIAYA_KEMASAN_PRODUKSI', NULL, 'KAS_KELUAR', 'KURANG', 'Pembayaran biaya kemasan produksi', 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(58, 42, 1, 'KODE_JENIS_TRANSAKSI', 'BIAYA_LAIN_LAIN_PRODUKSI', NULL, 'KAS_KELUAR', 'KURANG', 'Pembayaran biaya lain-lain produksi', 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(59, 44, 1, 'KODE_JENIS_TRANSAKSI', 'KAS_KELUAR', NULL, 'KAS_KELUAR', 'KURANG', 'Pengeluaran kas lainnya', 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(60, 44, 1, 'KODE_JENIS_TRANSAKSI', 'KAS_KELUAR_LAIN', NULL, 'KAS_KELUAR', 'KURANG', 'Pengeluaran kas lainnya', 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(61, 43, 1, 'KODE_JENIS_TRANSAKSI', 'KAS_MASUK', NULL, 'KAS_MASUK', 'TAMBAH', 'Penerimaan kas lainnya', 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(62, 43, 1, 'KODE_JENIS_TRANSAKSI', 'KAS_MASUK_LAIN', NULL, 'KAS_MASUK', 'TAMBAH', 'Penerimaan kas lainnya', 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(63, 41, 1, 'KODE_JENIS_TRANSAKSI', 'FAKTUR_PEMBELIAN_TUNAI', NULL, 'KAS_KELUAR', 'KURANG', 'Pembelian bahan baku tunai', 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(64, 41, 1, 'KODE_JENIS_TRANSAKSI', 'FAKTUR_PEMBELIAN_TRANSFER', NULL, 'KAS_KELUAR', 'KURANG', 'Pembelian bahan baku transfer', 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(65, 41, 1, 'KODE_JENIS_TRANSAKSI', 'PEMBAYARAN_PEMBELIAN', NULL, 'KAS_KELUAR', 'KURANG', 'Pembayaran utang pembelian', 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(66, 40, 1, 'KODE_JENIS_TRANSAKSI', 'FAKTUR_PENJUALAN_TUNAI', NULL, 'KAS_MASUK', 'TAMBAH', 'Penerimaan faktur penjualan tunai', 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(67, 40, 1, 'KODE_JENIS_TRANSAKSI', 'FAKTUR_PENJUALAN_TRANSFER', NULL, 'KAS_MASUK', 'TAMBAH', 'Penerimaan faktur penjualan transfer', 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(68, 40, 1, 'KODE_JENIS_TRANSAKSI', 'PEMBAYARAN_PENJUALAN', NULL, 'KAS_MASUK', 'TAMBAH', 'Penerimaan pembayaran penjualan', 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(69, 40, 1, 'KODE_JENIS_TRANSAKSI', 'PEMBAYARAN_PENJUALAN_TUNAI', NULL, 'KAS_MASUK', 'TAMBAH', 'Penerimaan pembayaran penjualan tunai', 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(70, 40, 1, 'KODE_JENIS_TRANSAKSI', 'PEMBAYARAN_PENJUALAN_TRANSFER', NULL, 'KAS_MASUK', 'TAMBAH', 'Penerimaan pembayaran penjualan transfer', 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(71, 49, 1, 'KODE_JENIS_TRANSAKSI', 'PRIVE_PEMILIK', NULL, 'KAS_KELUAR', 'KURANG', 'Prive pemilik', 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(72, 48, 1, 'KODE_JENIS_TRANSAKSI', 'SETORAN_MODAL', NULL, 'KAS_MASUK', 'TAMBAH', 'Setoran modal pemilik', 1, '2026-04-28 15:47:55', 1, NULL, NULL),
(74, 40, 1, 'KODE_JENIS_TRANSAKSI', 'PEMBAYARAN_ONLINE_DITERIMA', NULL, 'KAS_MASUK', 'TAMBAH', 'Penerimaan uang pesanan online dari customer', 1, '2026-06-08 12:00:58', 1, NULL, NULL),
(75, 44, 1, 'KODE_JENIS_TRANSAKSI', 'REFUND_PEMBAYARAN_ONLINE', NULL, 'KAS_KELUAR', 'KURANG', 'Pengembalian uang/refund pesanan online', 1, '2026-06-08 12:00:58', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tb_menu`
--

CREATE TABLE `tb_menu` (
  `id_menu` bigint UNSIGNED NOT NULL,
  `id_menu_induk` bigint UNSIGNED DEFAULT NULL,
  `kode_menu` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_menu` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jenis_menu` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'menu',
  `url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ikon` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `urutan` int NOT NULL DEFAULT '0',
  `tingkat_menu` int NOT NULL DEFAULT '1',
  `status_aktif` tinyint(1) NOT NULL DEFAULT '1',
  `perlu_login` tinyint(1) NOT NULL DEFAULT '1',
  `keterangan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tanggal_dibuat` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dibuat_oleh` bigint UNSIGNED DEFAULT NULL,
  `tanggal_diubah` datetime DEFAULT NULL,
  `diubah_oleh` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_menu`
--

INSERT INTO `tb_menu` (`id_menu`, `id_menu_induk`, `kode_menu`, `nama_menu`, `jenis_menu`, `url`, `ikon`, `urutan`, `tingkat_menu`, `status_aktif`, `perlu_login`, `keterangan`, `tanggal_dibuat`, `dibuat_oleh`, `tanggal_diubah`, `diubah_oleh`) VALUES
(1, NULL, 'DASHBOARD', 'Dashboard', 'menu', '/dashboard', 'fa fa-home', 1, 1, 1, 1, 'Dashboard utama', '2026-04-17 12:34:28', NULL, NULL, NULL),
(2, NULL, 'PROFIL_SAYA', 'Profil Saya', 'menu', '/profil', 'fa fa-user', 2, 1, 1, 1, 'Profil pengguna', '2026-04-17 12:34:28', NULL, NULL, NULL),
(3, NULL, 'GANTI_PASSWORD', 'Ganti Password', 'menu', '/ganti-password', 'fa fa-key', 3, 1, 1, 1, 'Ganti password', '2026-04-17 12:34:28', NULL, NULL, NULL),
(4, NULL, 'MASTER_SETUP', 'Master Setup', 'grup', '#', 'fa fa-database', 10, 1, 1, 1, 'Grup master', '2026-04-17 12:34:28', NULL, NULL, NULL),
(5, 4, 'ENTITAS', 'Entitas', 'menu', '/master/entitas', 'fa fa-building', 11, 2, 1, 1, 'Master entitas', '2026-04-17 12:34:28', NULL, NULL, NULL),
(6, 4, 'ROLE', 'Role', 'menu', '/master/role', 'fa fa-user-shield', 12, 2, 1, 1, 'Master role', '2026-04-17 12:34:28', NULL, NULL, NULL),
(7, 4, 'PENGGUNA', 'Pengguna', 'menu', '/master/pengguna', 'fa fa-users', 13, 2, 1, 1, 'Master pengguna', '2026-04-17 12:34:28', NULL, NULL, NULL),
(8, 4, 'COA', 'COA', 'menu', '/master_setup/coa', 'fa fa-book', 14, 2, 1, 1, 'Chart of account', '2026-04-17 12:34:28', NULL, NULL, NULL),
(9, 4, 'PERIODE_AKUNTANSI', 'Periode Akuntansi', 'menu', '/master_setup/periode_akuntansi', 'fa fa-calendar', 15, 2, 1, 1, 'Periode akuntansi', '2026-04-17 12:34:28', NULL, NULL, NULL),
(10, 4, 'TEMPLATE_JURNAL', 'Template Jurnal', 'menu', '/master_setup/template_jurnal', 'fa fa-file-invoice', 16, 2, 1, 1, 'Template jurnal', '2026-04-17 12:34:28', NULL, NULL, NULL),
(11, 4, 'MAPPING_AKUN', 'Mapping Akun', 'menu', '/master_setup/mapping_akun', 'fa fa-link', 17, 2, 1, 1, 'Mapping akun', '2026-04-17 12:34:28', NULL, '2026-06-06 19:31:23', 1),
(12, 4, 'SATUAN', 'Satuan', 'menu', '/master_setup/satuan', 'fa fa-balance-scale', 18, 2, 1, 1, 'Master satuan', '2026-04-17 12:34:28', NULL, NULL, NULL),
(13, 4, 'GUDANG', 'Gudang', 'menu', '/master_setup/gudang', 'fa fa-warehouse', 19, 2, 1, 1, 'Master gudang', '2026-04-17 12:34:28', NULL, NULL, NULL),
(14, 4, 'PELANGGAN', 'Pelanggan', 'menu', '/master_setup/pelanggan', 'fa fa-address-book', 20, 2, 1, 1, 'Master pelanggan', '2026-04-17 12:34:28', NULL, NULL, NULL),
(15, 4, 'PEMASOK', 'Pemasok', 'menu', '/master_setup/pemasok', 'fa fa-truck', 21, 2, 1, 1, 'Master pemasok', '2026-04-17 12:34:28', NULL, NULL, NULL),
(16, 4, 'KATEGORI_PRODUK', 'Kategori Produk', 'menu', '/master_setup/kategori_produk', 'fa fa-tags', 22, 2, 1, 1, 'Master kategori produk', '2026-04-17 12:34:28', NULL, NULL, NULL),
(17, 4, 'PRODUK', 'Produk', 'menu', '/master_setup/produk', 'fa fa-box', 23, 2, 1, 1, 'Master produk', '2026-04-17 12:34:28', NULL, NULL, NULL),
(18, 4, 'BAHAN_BAKU', 'Bahan Baku', 'menu', '/master_setup/bahan_baku', 'fa fa-cubes', 24, 2, 1, 1, 'Master bahan baku', '2026-04-17 12:34:28', NULL, NULL, NULL),
(19, 4, 'RESEP', 'Resep / BOM', 'menu', '/master_setup/resep', 'fa fa-list', 25, 2, 1, 1, 'Master resep', '2026-04-17 12:34:28', NULL, NULL, NULL),
(20, NULL, 'PENJUALAN', 'Penjualan', 'grup', '#', 'fa fa-shopping-cart', 30, 1, 1, 1, 'Grup penjualan', '2026-04-17 12:34:28', NULL, NULL, NULL),
(21, 20, 'DASHBOARD_PENJUALAN', 'Dashboard Penjualan', 'menu', '/penjualan/dashboard', 'fa fa-chart-line', 31, 2, 1, 1, 'Dashboard penjualan', '2026-04-17 12:34:28', NULL, NULL, NULL),
(22, 20, 'PESANAN_PENJUALAN', 'Pesanan Penjualan', 'menu', '/penjualan/pesanan', 'fa fa-file-alt', 33, 2, 1, 1, 'Pesanan penjualan', '2026-04-17 12:34:28', NULL, NULL, NULL),
(23, 20, 'PENYERAHAN_PENJUALAN', 'Penyerahan Penjualan', 'menu', '/penjualan/penyerahan', 'fa fa-dolly', 34, 2, 1, 1, 'Penyerahan penjualan', '2026-04-17 12:34:28', NULL, NULL, NULL),
(24, 20, 'FAKTUR_PENJUALAN', 'Daftar Piutang Pelanggan', 'menu', '/penjualan/faktur', 'fa fa-money-bill-wave', 35, 2, 1, 1, 'Daftar pelanggan yang membeli kredit, sisa piutang, dan tombol bayar', '2026-04-17 12:34:28', NULL, NULL, NULL),
(25, 20, 'PEMBAYARAN_PENJUALAN', 'Pembayaran Penjualan', 'menu', '/penjualan/pembayaran', 'fa fa-money-bill', 36, 2, 1, 1, 'Pembayaran penjualan', '2026-04-17 12:34:28', NULL, NULL, NULL),
(26, 20, 'PESANAN_ONLINE', 'Pesanan Online', 'menu', '/penjualan/pesanan-online', 'fa fa-globe', 37, 2, 1, 1, 'Pesanan online', '2026-04-17 12:34:28', NULL, NULL, NULL),
(30, NULL, 'PEMBELIAN', 'Pembelian', 'grup', '#', 'fa fa-shopping-basket', 40, 1, 1, 1, 'Grup pembelian', '2026-04-17 12:34:28', NULL, NULL, NULL),
(31, 30, 'DASHBOARD_PEMBELIAN', 'Dashboard Pembelian', 'menu', '/pembelian/dashboard', 'fa fa-chart-bar', 41, 2, 1, 1, 'Dashboard pembelian', '2026-04-17 12:34:28', NULL, NULL, NULL),
(32, 30, 'PESANAN_PEMBELIAN', 'Pesanan Pembelian', 'menu', '/pembelian/pesanan', 'fa fa-file-signature', 42, 2, 1, 1, 'Pesanan pembelian', '2026-04-17 12:34:28', NULL, NULL, NULL),
(33, 30, 'PENERIMAAN_PEMBELIAN', 'Penerimaan Pembelian', 'menu', '/pembelian/penerimaan', 'fa fa-box-open', 43, 2, 1, 1, 'Penerimaan pembelian', '2026-04-17 12:34:28', NULL, NULL, NULL),
(34, 30, 'FAKTUR_PEMBELIAN', 'Faktur Pembelian', 'menu', '/pembelian/faktur', 'fa fa-file-invoice-dollar', 44, 2, 1, 1, 'Faktur pembelian', '2026-04-17 12:34:28', NULL, NULL, NULL),
(35, 30, 'PEMBAYARAN_PEMBELIAN', 'Pembayaran Pembelian', 'menu', '/pembelian/pembayaran', 'fa fa-credit-card', 45, 2, 1, 1, 'Pembayaran pembelian', '2026-04-17 12:34:28', NULL, NULL, NULL),
(40, NULL, 'PRODUKSI', 'Produksi', 'grup', '#', 'fa fa-industry', 50, 1, 1, 1, 'Grup produksi', '2026-04-17 12:34:28', NULL, NULL, NULL),
(41, 40, 'DASHBOARD_PRODUKSI', 'Dashboard Produksi', 'menu', '/produksi/dashboard', 'fa fa-chart-pie', 51, 2, 1, 1, 'Dashboard produksi', '2026-04-17 12:34:28', NULL, NULL, NULL),
(42, 40, 'PERINTAH_PRODUKSI', 'Perintah Produksi', 'menu', '/produksi/perintah', 'fa fa-tasks', 52, 2, 1, 1, 'Perintah produksi', '2026-04-17 12:34:28', NULL, NULL, NULL),
(43, 40, 'PENGAMBILAN_BAHAN', 'Pengambilan Bahan', 'menu', '/produksi/pengambilan-bahan', 'fa fa-hand-paper', 53, 2, 1, 1, 'Pengambilan bahan', '2026-04-17 12:34:28', NULL, NULL, NULL),
(44, 40, 'BIAYA_PRODUKSI', 'Biaya Produksi', 'menu', '/produksi/biaya', 'fa fa-coins', 54, 2, 1, 1, 'Biaya produksi', '2026-04-17 12:34:28', NULL, NULL, NULL),
(45, 40, 'HASIL_PRODUKSI', 'Hasil Produksi', 'menu', '/produksi/hasil', 'fa fa-check-circle', 55, 2, 1, 1, 'Hasil produksi', '2026-04-17 12:34:28', NULL, NULL, NULL),
(50, NULL, 'PERSEDIAAN', 'Persediaan / Gudang', 'grup', '#', 'fa fa-archive', 60, 1, 1, 1, 'Grup persediaan', '2026-04-17 12:34:28', NULL, NULL, NULL),
(51, 50, 'DASHBOARD_GUDANG', 'Dashboard Gudang', 'menu', '/persediaan/dashboard', 'fa fa-th-large', 61, 2, 1, 1, 'Dashboard gudang', '2026-04-17 12:34:28', NULL, NULL, NULL),
(52, 50, 'MUTASI_STOK', 'Mutasi Stok', 'menu', '/persediaan/mutasi-stok', 'fa fa-random', 62, 2, 1, 1, 'Mutasi stok', '2026-04-17 12:34:28', NULL, NULL, NULL),
(53, 50, 'SALDO_STOK', 'Saldo Stok', 'menu', '/persediaan/saldo-stok', 'fa fa-boxes', 63, 2, 1, 1, 'Saldo stok', '2026-04-17 12:34:28', NULL, NULL, NULL),
(54, 50, 'STOK_MINIMUM', 'Stok Minimum', 'menu', '/persediaan/stok-minimum', 'fa fa-exclamation-triangle', 64, 2, 1, 1, 'Stok minimum', '2026-04-17 12:34:28', NULL, NULL, NULL),
(55, 50, 'STOK_OPNAME', 'Stok Opname', 'menu', '/persediaan/stok-opname', 'fa fa-clipboard-check', 65, 2, 1, 1, 'Stok opname', '2026-04-17 12:34:28', NULL, NULL, NULL),
(60, NULL, 'KEUANGAN', 'Keuangan / Akuntansi', 'grup', '#', 'fa fa-calculator', 70, 1, 1, 1, 'Grup keuangan', '2026-04-17 12:34:28', NULL, NULL, NULL),
(61, 60, 'DASHBOARD_KEUANGAN', 'Dashboard Keuangan', 'menu', '/keuangan/dashboard', 'fa fa-wallet', 71, 2, 1, 1, 'Dashboard keuangan', '2026-04-17 12:34:28', NULL, NULL, NULL),
(62, 60, 'KAS_MASUK', 'Kas Masuk', 'menu', '/keuangan/kas-masuk', 'fa fa-arrow-circle-down', 72, 2, 1, 1, 'Kas masuk', '2026-04-17 12:34:28', NULL, NULL, NULL),
(63, 60, 'KAS_KELUAR', 'Kas Keluar', 'menu', '/keuangan/kas-keluar', 'fa fa-arrow-circle-up', 73, 2, 1, 1, 'Kas keluar', '2026-04-17 12:34:28', NULL, NULL, NULL),
(64, 60, 'JURNAL', 'Jurnal', 'menu', '/keuangan/jurnal', 'fa fa-book-open', 74, 2, 1, 1, 'Jurnal umum', '2026-04-17 12:34:28', NULL, NULL, NULL),
(65, 60, 'LOG_JURNAL_SUMBER', 'Log Jurnal Sumber', 'menu', '/keuangan/log-jurnal-sumber', 'fa fa-history', 75, 2, 1, 1, 'Audit trail jurnal', '2026-04-17 12:34:28', NULL, NULL, NULL),
(66, 60, 'BUKU_BESAR', 'Buku Besar', 'menu', '/keuangan/buku-besar', 'fa fa-book-reader', 76, 2, 1, 1, 'Buku besar', '2026-04-17 12:34:28', NULL, NULL, NULL),
(67, 60, 'SALDO_AKUN', 'Saldo Akun', 'menu', '/keuangan/saldo-akun', 'fa fa-list-alt', 77, 2, 1, 1, 'Saldo akun', '2026-04-17 12:34:28', NULL, NULL, NULL),
(68, 60, 'LABA_RUGI', 'Laba Rugi', 'menu', '/keuangan/laba-rugi', 'fa fa-chart-line', 78, 2, 1, 1, 'Laporan laba rugi', '2026-04-17 12:34:28', NULL, NULL, NULL),
(69, 60, 'NERACA', 'Neraca', 'menu', '/keuangan/neraca', 'fa fa-balance-scale-right', 79, 2, 1, 1, 'Laporan neraca', '2026-04-17 12:34:28', NULL, NULL, NULL),
(70, 60, 'ARUS_KAS', 'Arus Kas', 'menu', '/keuangan/arus-kas', 'fa fa-stream', 80, 2, 1, 1, 'Laporan arus kas', '2026-04-17 12:34:28', NULL, NULL, NULL),
(80, NULL, 'LOGOUT', 'Logout', 'menu', '/logout', 'fa fa-sign-out-alt', 99, 1, 1, 1, 'Keluar aplikasi', '2026-04-17 12:34:28', NULL, NULL, NULL),
(84, 4, 'KONFIGURASI_AKUN', 'Konfigurasi Akun', 'menu', '/master_setup/konfigurasi_akun', 'bi bi-sliders', 35, 2, 1, 1, 'Konfigurasi akun default sistem per entitas', '2026-04-18 18:34:20', 1, NULL, NULL),
(85, 50, 'SALDO_AWAL_STOK', 'Saldo Awal Stok', 'menu', '/persediaan/saldo-stok/saldo-awal', 'fa fa-plus-circle', 64, 3, 1, 1, 'Input saldo awal stok dalam halaman saldo stok', '2026-04-26 13:55:59', NULL, NULL, NULL),
(86, 20, 'KASIR_POS', 'Kasir / POS', 'menu', '/penjualan/kasir', 'fa fa-cash-register', 32, 2, 1, 1, 'Kasir / Point of Sale', '2026-04-27 07:59:31', 1, NULL, NULL),
(87, 60, 'PEMBATALAN_TRANSAKSI', 'Pembatalan Transaksi', 'menu', '/keuangan/pembatalan-transaksi', 'fa fa-undo', 81, 2, 1, 1, 'Pembatalan transaksi posted dengan jurnal reversal', '2026-04-28 17:30:10', 1, NULL, NULL),
(88, 4, 'MAPPING_LAPORAN', 'Mapping Laporan', 'menu', '/master_setup/mapping_laporan', 'fa fa-sitemap', 36, 2, 1, 1, 'Setup mapping akun untuk laporan keuangan', '2026-05-25 11:19:00', 1, NULL, NULL),
(89, 4, 'PAJAK_PPN', 'Pajak / PPN', 'menu', '/master_setup/pajak', 'bi bi-percent', 34, 2, 0, 1, 'Dinonaktifkan sebagai menu terpisah. PPN digabung ke Mapping Akun > Pajak / PPN.', '2026-06-02 13:01:25', 1, '2026-06-06 19:31:23', 1),
(90, 4, 'RESET_DATA_PERCOBAAN', 'Reset Data Percobaan', 'menu', '/master_setup/reset_data', 'fa fa-trash', 38, 2, 1, 1, 'Reset data dummy transaksi, stok, mutasi, dan jurnal', '2026-06-06 09:27:52', NULL, NULL, NULL),
(91, 50, 'KONVERSI_PRODUK', 'Konversi Produk', 'menu', '/persediaan/konversi-produk', 'fa fa-exchange-alt', 66, 2, 1, 1, 'Pecah stok produk kg menjadi pcs/biji dengan HPP tetap tersambung', '2026-06-06 21:03:00', 1, NULL, NULL),
(92, 20, 'LOG_CHAT_ONLINE', 'Log Chat Online', 'menu', '/penjualan/chat-online', 'fa fa-comments', 89, 2, 1, 1, 'Log chat general pelanggan dari halaman pesanan online', '2026-06-07 15:06:23', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tb_mutasi_stok`
--

CREATE TABLE `tb_mutasi_stok` (
  `id_mutasi_stok` bigint UNSIGNED NOT NULL,
  `id_entitas` bigint UNSIGNED NOT NULL,
  `tanggal_mutasi` datetime NOT NULL,
  `jenis_barang` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_referensi_barang` bigint UNSIGNED NOT NULL,
  `id_gudang` bigint UNSIGNED NOT NULL,
  `jenis_mutasi` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `qty_masuk` decimal(18,3) NOT NULL DEFAULT '0.000',
  `qty_keluar` decimal(18,3) NOT NULL DEFAULT '0.000',
  `harga_satuan` decimal(18,2) NOT NULL DEFAULT '0.00',
  `nilai_total` decimal(18,2) NOT NULL DEFAULT '0.00',
  `tabel_sumber` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_sumber` bigint UNSIGNED NOT NULL,
  `no_sumber` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `keterangan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tanggal_dibuat` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dibuat_oleh` bigint UNSIGNED DEFAULT NULL,
  `tanggal_diubah` datetime DEFAULT NULL,
  `diubah_oleh` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_mutasi_stok`
--

INSERT INTO `tb_mutasi_stok` (`id_mutasi_stok`, `id_entitas`, `tanggal_mutasi`, `jenis_barang`, `id_referensi_barang`, `id_gudang`, `jenis_mutasi`, `qty_masuk`, `qty_keluar`, `harga_satuan`, `nilai_total`, `tabel_sumber`, `id_sumber`, `no_sumber`, `keterangan`, `tanggal_dibuat`, `dibuat_oleh`, `tanggal_diubah`, `diubah_oleh`) VALUES
(1, 1, '2026-06-09 04:42:41', 'bahan_baku', 2, 1, 'penerimaan_pembelian', '50.000', '0.000', '2000.00', '100000.00', 'tb_penerimaan_pembelian', 1, 'PBM-0001', 'Posting penerimaan pembelian', '2026-06-09 04:42:41', 1, NULL, NULL),
(2, 1, '2026-06-09 04:42:41', 'bahan_baku', 1, 1, 'penerimaan_pembelian', '10.000', '0.000', '15000.00', '150000.00', 'tb_penerimaan_pembelian', 1, 'PBM-0001', 'Posting penerimaan pembelian', '2026-06-09 04:42:41', 1, NULL, NULL),
(3, 1, '2026-06-09 04:42:41', 'bahan_baku', 3, 1, 'penerimaan_pembelian', '20.000', '0.000', '20000.00', '400000.00', 'tb_penerimaan_pembelian', 1, 'PBM-0001', 'Posting penerimaan pembelian', '2026-06-09 04:42:41', 1, NULL, NULL),
(4, 1, '2026-06-09 00:00:00', 'bahan_baku', 2, 1, 'keluar', '0.000', '50.000', '2000.00', '100000.00', 'tb_pengambilan_bahan', 1, 'PGB-0001', 'Pengambilan bahan produksi PGB-0001', '2026-06-09 04:44:24', 1, NULL, NULL),
(5, 1, '2026-06-09 00:00:00', 'bahan_baku', 1, 1, 'keluar', '0.000', '10.000', '15000.00', '150000.00', 'tb_pengambilan_bahan', 1, 'PGB-0001', 'Pengambilan bahan produksi PGB-0001', '2026-06-09 04:44:24', 1, NULL, NULL),
(6, 1, '2026-06-09 00:00:00', 'bahan_baku', 3, 1, 'keluar', '0.000', '20.000', '20000.00', '400000.00', 'tb_pengambilan_bahan', 1, 'PGB-0001', 'Pengambilan bahan produksi PGB-0001', '2026-06-09 04:44:24', 1, NULL, NULL),
(7, 1, '2026-06-09 00:00:00', 'produk', 3, 1, 'masuk', '45.000', '0.000', '15666.67', '705000.00', 'tb_hasil_produksi', 1, 'HPR-0001', 'Hasil produksi HPR-0001', '2026-06-09 04:46:34', 1, NULL, NULL),
(8, 1, '2026-06-09 00:00:00', 'produk', 3, 1, 'konversi_produk_keluar', '0.000', '5.000', '15666.67', '78333.35', 'tb_konversi_produk', 1, 'KVP-2606-0001', 'Keluar karena konversi produk ke Dodol Per Biji', '2026-06-09 04:47:21', 1, NULL, NULL),
(9, 1, '2026-06-09 00:00:00', 'produk', 4, 1, 'konversi_produk_masuk', '500.000', '0.000', '156.67', '78333.35', 'tb_konversi_produk', 1, 'KVP-2606-0001', 'Masuk dari konversi produk Dodol 1 Kg', '2026-06-09 04:47:21', 1, NULL, NULL),
(10, 1, '2026-06-09 00:00:00', 'produk', 3, 1, 'penyerahan_penjualan', '0.000', '1.000', '15666.67', '15666.67', 'tb_penyerahan_penjualan', 1, 'PNJ-202606-0001', 'POS - penyerahan penjualan', '2026-06-09 05:30:26', 1, NULL, NULL),
(11, 1, '2026-06-09 00:00:00', 'produk', 3, 1, 'penyerahan_penjualan', '0.000', '1.000', '15666.67', '15666.67', 'tb_penyerahan_penjualan', 3, 'PNJ-202606-0002', 'POS - penyerahan penjualan', '2026-06-09 07:20:01', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tb_pajak`
--

CREATE TABLE `tb_pajak` (
  `id_pajak` bigint UNSIGNED NOT NULL,
  `id_entitas` bigint UNSIGNED NOT NULL,
  `kode_pajak` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_pajak` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `persentase` decimal(5,2) NOT NULL DEFAULT '0.00',
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `status_aktif` tinyint(1) NOT NULL DEFAULT '1',
  `keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `tanggal_dibuat` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dibuat_oleh` bigint UNSIGNED DEFAULT NULL,
  `tanggal_diubah` datetime DEFAULT NULL,
  `diubah_oleh` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_pajak`
--

INSERT INTO `tb_pajak` (`id_pajak`, `id_entitas`, `kode_pajak`, `nama_pajak`, `persentase`, `is_default`, `status_aktif`, `keterangan`, `tanggal_dibuat`, `dibuat_oleh`, `tanggal_diubah`, `diubah_oleh`) VALUES
(1, 1, 'PPN', 'Pajak Pertambahan Nilai', '0.00', 1, 1, 'PPN default untuk faktur pembelian dan penjualan', '2026-06-02 13:01:25', 1, '2026-06-08 09:20:26', 1);

-- --------------------------------------------------------

--
-- Table structure for table `tb_payment_gateway_setting`
--

CREATE TABLE `tb_payment_gateway_setting` (
  `id_payment_gateway_setting` bigint UNSIGNED NOT NULL,
  `id_entitas` bigint UNSIGNED NOT NULL,
  `provider` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'midtrans',
  `mode` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sandbox',
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `server_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `client_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `merchant_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `enabled_payments` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'qris',
  `tanggal_dibuat` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dibuat_oleh` bigint UNSIGNED DEFAULT NULL,
  `tanggal_diubah` datetime DEFAULT NULL,
  `diubah_oleh` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_payment_gateway_setting`
--

INSERT INTO `tb_payment_gateway_setting` (`id_payment_gateway_setting`, `id_entitas`, `provider`, `mode`, `is_active`, `server_key`, `client_key`, `merchant_id`, `enabled_payments`, `tanggal_dibuat`, `dibuat_oleh`, `tanggal_diubah`, `diubah_oleh`) VALUES
(1, 1, 'midtrans', 'sandbox', 0, NULL, NULL, NULL, 'qris', '2026-06-06 22:56:34', NULL, '2026-06-09 05:49:50', NULL),
(2, 1, 'duitku', 'sandbox', 0, NULL, NULL, NULL, 'SP', '2026-06-07 11:28:30', NULL, '2026-06-09 05:49:50', NULL),
(3, 1, 'ipaymu', 'sandbox', 0, '897CB14A-2DD7-4A15-AA87-52B09CD26BD1', NULL, '1179005325944002', 'qris', '2026-06-07 12:09:38', NULL, '2026-06-09 05:49:50', 1);

-- --------------------------------------------------------

--
-- Table structure for table `tb_pelanggan`
--

CREATE TABLE `tb_pelanggan` (
  `id_pelanggan` bigint UNSIGNED NOT NULL,
  `id_entitas` bigint UNSIGNED NOT NULL,
  `kode_pelanggan` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_pelanggan` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `alamat` text COLLATE utf8mb4_unicode_ci,
  `no_hp` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jenis_pelanggan` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `batas_piutang` decimal(18,2) NOT NULL DEFAULT '0.00',
  `tempo_hari` int NOT NULL DEFAULT '0',
  `status_aktif` tinyint(1) NOT NULL DEFAULT '1',
  `tanggal_dibuat` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dibuat_oleh` bigint UNSIGNED DEFAULT NULL,
  `tanggal_diubah` datetime DEFAULT NULL,
  `diubah_oleh` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_pelanggan`
--

INSERT INTO `tb_pelanggan` (`id_pelanggan`, `id_entitas`, `kode_pelanggan`, `nama_pelanggan`, `alamat`, `no_hp`, `email`, `jenis_pelanggan`, `batas_piutang`, `tempo_hari`, `status_aktif`, `tanggal_dibuat`, `dibuat_oleh`, `tanggal_diubah`, `diubah_oleh`) VALUES
(1, 1, 'PLG-0001', 'UMUM', NULL, NULL, NULL, 'umum', '0.00', 0, 1, '2026-06-06 23:33:43', 1, NULL, NULL),
(2, 1, 'PLG-WEB-202606-0001', 'Jaya', 'Jl. A. Yani Km 15,200', '081254619752', 'ihya.wb5@gmail.com', 'online', '0.00', 0, 1, '2026-06-07 14:18:13', NULL, '2026-06-09 05:11:54', NULL),
(3, 1, 'PLG-WEB-202606-0002', 'Jaya', 'Jalan Korporat', '0890909000', 'arasaka@nightcity.com', 'online', '0.00', 0, 1, '2026-06-07 15:44:16', NULL, NULL, NULL),
(4, 1, 'PLG-WEB-202606-0003', 'Yaya', 'Jl. A. Yani Km 15,200', '0895325944002', 'ihya.wb5@gmail.com', 'online', '0.00', 0, 1, '2026-06-08 16:35:09', NULL, '2026-06-09 05:10:48', NULL),
(5, 1, 'PLG-WEB-202606-0004', 'Jaya', 'ambil sendiri', '1111', 'ihya.wb5@gmail.com', 'online', '0.00', 0, 1, '2026-06-09 05:17:17', NULL, '2026-06-09 05:17:21', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tb_pemasok`
--

CREATE TABLE `tb_pemasok` (
  `id_pemasok` bigint UNSIGNED NOT NULL,
  `id_entitas` bigint UNSIGNED NOT NULL,
  `kode_pemasok` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_pemasok` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `alamat` text COLLATE utf8mb4_unicode_ci,
  `no_hp` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tempo_hari` int NOT NULL DEFAULT '0',
  `status_aktif` tinyint(1) NOT NULL DEFAULT '1',
  `tanggal_dibuat` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dibuat_oleh` bigint UNSIGNED DEFAULT NULL,
  `tanggal_diubah` datetime DEFAULT NULL,
  `diubah_oleh` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_pemasok`
--

INSERT INTO `tb_pemasok` (`id_pemasok`, `id_entitas`, `kode_pemasok`, `nama_pemasok`, `alamat`, `no_hp`, `email`, `tempo_hari`, `status_aktif`, `tanggal_dibuat`, `dibuat_oleh`, `tanggal_diubah`, `diubah_oleh`) VALUES
(1, 1, 'PMS-0001', 'Pemasok Dummy Bahan Dodol', 'Banjarmasin\r\nPemasok percobaan bahan baku dodol', '081234567890', NULL, 0, 1, '2026-06-06 22:32:29', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tb_pembatalan_transaksi`
--

CREATE TABLE `tb_pembatalan_transaksi` (
  `id_pembatalan_transaksi` bigint UNSIGNED NOT NULL,
  `id_entitas` bigint UNSIGNED NOT NULL,
  `no_pembatalan` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal_pembatalan` date NOT NULL,
  `id_jurnal_asal` bigint UNSIGNED NOT NULL,
  `no_jurnal_asal` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_jurnal_reversal` bigint UNSIGNED DEFAULT NULL,
  `no_jurnal_reversal` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kode_jenis_transaksi_asal` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tabel_sumber` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_sumber` bigint UNSIGNED DEFAULT NULL,
  `no_sumber` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alasan_pembatalan` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status_pembatalan` enum('draft','posted','batal') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'posted',
  `tanggal_dibuat` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dibuat_oleh` bigint UNSIGNED DEFAULT NULL,
  `tanggal_diubah` datetime DEFAULT NULL,
  `diubah_oleh` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tb_pembayaran_online_bukti`
--

CREATE TABLE `tb_pembayaran_online_bukti` (
  `id_bukti_pembayaran_online` bigint UNSIGNED NOT NULL,
  `id_entitas` bigint UNSIGNED NOT NULL,
  `id_pesanan_penjualan` bigint UNSIGNED NOT NULL,
  `metode_pembayaran` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'qris',
  `nominal_bayar` decimal(18,2) NOT NULL DEFAULT '0.00',
  `nama_pengirim` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tanggal_bayar` date DEFAULT NULL,
  `file_bukti` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `catatan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status_verifikasi` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'menunggu_verifikasi',
  `catatan_admin` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `diverifikasi_oleh` bigint UNSIGNED DEFAULT NULL,
  `tanggal_verifikasi` datetime DEFAULT NULL,
  `tanggal_dibuat` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dibuat_oleh` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_pembayaran_online_bukti`
--

INSERT INTO `tb_pembayaran_online_bukti` (`id_bukti_pembayaran_online`, `id_entitas`, `id_pesanan_penjualan`, `metode_pembayaran`, `nominal_bayar`, `nama_pengirim`, `tanggal_bayar`, `file_bukti`, `catatan`, `status_verifikasi`, `catatan_admin`, `diverifikasi_oleh`, `tanggal_verifikasi`, `tanggal_dibuat`, `dibuat_oleh`) VALUES
(1, 1, 2, 'qris', '550.00', 'Yaya', '2026-06-07', 'uploads/bukti_pembayaran/bukti_2_20260607145011_b8f1f698.jpeg', NULL, 'lunas', 'Pembayaran diverifikasi oleh admin.', 1, '2026-06-07 14:52:33', '2026-06-07 14:50:11', NULL),
(2, 1, 3, 'qris', '550.00', 'Jaya', '2026-06-07', 'uploads/bukti_pembayaran/bukti_3_20260607154416_ca55a867.jpeg', 'Upload dari checkout pesanan online.', 'menunggu_verifikasi', NULL, NULL, NULL, '2026-06-07 15:44:16', NULL),
(3, 1, 1, 'transfer', '100000.00', 'Yaya', '2026-06-08', 'uploads/bukti_pembayaran/bukti_1_20260608094613_a1b0f93a.jpeg', 'Upload dari checkout pesanan online.', 'lunas', 'Pembayaran diverifikasi oleh admin. Uang sudah masuk.', 1, '2026-06-08 09:48:54', '2026-06-08 09:46:13', NULL),
(4, 1, 2, 'transfer', '51000.00', 'Jaya', '2026-06-08', 'uploads/bukti_pembayaran/bukti_2_20260608163509_66a14585.jpeg', 'Upload dari checkout pesanan online.', 'menunggu_verifikasi', NULL, NULL, NULL, '2026-06-08 16:35:09', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tb_pembayaran_pembelian`
--

CREATE TABLE `tb_pembayaran_pembelian` (
  `id_pembayaran_pembelian` bigint UNSIGNED NOT NULL,
  `id_entitas` bigint UNSIGNED NOT NULL,
  `no_pembayaran_pembelian` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal_pembayaran` date NOT NULL,
  `id_faktur_pembelian` bigint UNSIGNED NOT NULL,
  `id_pemasok` bigint UNSIGNED NOT NULL,
  `metode_pembayaran` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_coa_kas_bank` bigint UNSIGNED NOT NULL,
  `jumlah_bayar` decimal(18,2) NOT NULL DEFAULT '0.00',
  `no_referensi_transfer` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kode_cek_transfer` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nama_pengirim_transfer` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bukti_bayar_pos` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `catatan` text COLLATE utf8mb4_unicode_ci,
  `status_posting` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `tanggal_dibuat` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dibuat_oleh` bigint UNSIGNED DEFAULT NULL,
  `tanggal_diubah` datetime DEFAULT NULL,
  `diubah_oleh` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_pembayaran_pembelian`
--

INSERT INTO `tb_pembayaran_pembelian` (`id_pembayaran_pembelian`, `id_entitas`, `no_pembayaran_pembelian`, `tanggal_pembayaran`, `id_faktur_pembelian`, `id_pemasok`, `metode_pembayaran`, `id_coa_kas_bank`, `jumlah_bayar`, `catatan`, `status_posting`, `tanggal_dibuat`, `dibuat_oleh`, `tanggal_diubah`, `diubah_oleh`) VALUES
(1, 1, 'PBP-0001', '2026-06-09', 1, 1, 'tunai', 3, '650000.00', 'Otomatis lunas dari posting faktur pembelian FPB-0001', 'posted', '2026-06-09 04:43:11', 1, '2026-06-09 04:43:11', 1);

-- --------------------------------------------------------

--
-- Table structure for table `tb_pembayaran_penjualan`
--

CREATE TABLE `tb_pembayaran_penjualan` (
  `id_pembayaran_penjualan` bigint UNSIGNED NOT NULL,
  `id_entitas` bigint UNSIGNED NOT NULL,
  `no_pembayaran_penjualan` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal_pembayaran` date NOT NULL,
  `id_faktur_penjualan` bigint UNSIGNED NOT NULL,
  `id_pelanggan` bigint UNSIGNED NOT NULL,
  `metode_pembayaran` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_coa_kas_bank` bigint UNSIGNED NOT NULL,
  `jumlah_bayar` decimal(18,2) NOT NULL DEFAULT '0.00',
  `no_referensi_transfer` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kode_cek_transfer` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nama_pengirim_transfer` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bukti_bayar_pos` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `catatan` text COLLATE utf8mb4_unicode_ci,
  `status_posting` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `tanggal_dibuat` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dibuat_oleh` bigint UNSIGNED DEFAULT NULL,
  `tanggal_diubah` datetime DEFAULT NULL,
  `diubah_oleh` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_pembayaran_penjualan`
--

INSERT INTO `tb_pembayaran_penjualan` (`id_pembayaran_penjualan`, `id_entitas`, `no_pembayaran_penjualan`, `tanggal_pembayaran`, `id_faktur_penjualan`, `id_pelanggan`, `metode_pembayaran`, `id_coa_kas_bank`, `jumlah_bayar`, `catatan`, `status_posting`, `tanggal_dibuat`, `dibuat_oleh`, `tanggal_diubah`, `diubah_oleh`) VALUES
(1, 1, 'BPJ-202606-0001', '2026-06-09', 2, 1, 'tunai', 3, '50000.00', 'Pembayaran otomatis dari POS. Jurnal kas/bank berasal dari faktur POS.', 'posted', '2026-06-09 07:20:01', 1, '2026-06-09 07:20:01', 1);

-- --------------------------------------------------------

--
-- Table structure for table `tb_penerimaan_pembelian`
--

CREATE TABLE `tb_penerimaan_pembelian` (
  `id_penerimaan_pembelian` bigint UNSIGNED NOT NULL,
  `id_entitas` bigint UNSIGNED NOT NULL,
  `no_penerimaan_pembelian` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal_penerimaan` date NOT NULL,
  `id_pesanan_pembelian` bigint UNSIGNED DEFAULT NULL,
  `id_pemasok` bigint UNSIGNED NOT NULL,
  `id_gudang` bigint UNSIGNED NOT NULL,
  `status_penerimaan` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `catatan` text COLLATE utf8mb4_unicode_ci,
  `tanggal_dibuat` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dibuat_oleh` bigint UNSIGNED DEFAULT NULL,
  `tanggal_posting` datetime DEFAULT NULL,
  `diposting_oleh` bigint UNSIGNED DEFAULT NULL,
  `tanggal_diubah` datetime DEFAULT NULL,
  `diubah_oleh` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_penerimaan_pembelian`
--

INSERT INTO `tb_penerimaan_pembelian` (`id_penerimaan_pembelian`, `id_entitas`, `no_penerimaan_pembelian`, `tanggal_penerimaan`, `id_pesanan_pembelian`, `id_pemasok`, `id_gudang`, `status_penerimaan`, `catatan`, `tanggal_dibuat`, `dibuat_oleh`, `tanggal_posting`, `diposting_oleh`, `tanggal_diubah`, `diubah_oleh`) VALUES
(1, 1, 'PBM-0001', '2026-06-09', NULL, 1, 1, 'posted', NULL, '2026-06-09 04:42:37', 1, '2026-06-09 04:42:41', 1, '2026-06-09 04:42:41', 1);

-- --------------------------------------------------------

--
-- Table structure for table `tb_penerimaan_pembelian_detail`
--

CREATE TABLE `tb_penerimaan_pembelian_detail` (
  `id_penerimaan_pembelian_detail` bigint UNSIGNED NOT NULL,
  `id_penerimaan_pembelian` bigint UNSIGNED NOT NULL,
  `id_bahan_baku` bigint UNSIGNED NOT NULL,
  `qty` decimal(18,2) NOT NULL DEFAULT '0.00',
  `harga` decimal(18,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(18,2) NOT NULL DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_penerimaan_pembelian_detail`
--

INSERT INTO `tb_penerimaan_pembelian_detail` (`id_penerimaan_pembelian_detail`, `id_penerimaan_pembelian`, `id_bahan_baku`, `qty`, `harga`, `subtotal`) VALUES
(1, 1, 2, '50.00', '2000.00', '100000.00'),
(2, 1, 1, '10.00', '15000.00', '150000.00'),
(3, 1, 3, '20.00', '20000.00', '400000.00');

-- --------------------------------------------------------

--
-- Table structure for table `tb_pengambilan_bahan`
--

CREATE TABLE `tb_pengambilan_bahan` (
  `id_pengambilan_bahan` bigint UNSIGNED NOT NULL,
  `id_entitas` bigint UNSIGNED NOT NULL,
  `no_pengambilan_bahan` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal_pengambilan` date NOT NULL,
  `id_perintah_produksi` bigint UNSIGNED NOT NULL,
  `id_gudang` bigint UNSIGNED NOT NULL,
  `status_posting` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `catatan` text COLLATE utf8mb4_unicode_ci,
  `tanggal_dibuat` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dibuat_oleh` bigint UNSIGNED DEFAULT NULL,
  `tanggal_posting` datetime DEFAULT NULL,
  `diposting_oleh` bigint UNSIGNED DEFAULT NULL,
  `tanggal_diubah` datetime DEFAULT NULL,
  `diubah_oleh` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_pengambilan_bahan`
--

INSERT INTO `tb_pengambilan_bahan` (`id_pengambilan_bahan`, `id_entitas`, `no_pengambilan_bahan`, `tanggal_pengambilan`, `id_perintah_produksi`, `id_gudang`, `status_posting`, `catatan`, `tanggal_dibuat`, `dibuat_oleh`, `tanggal_posting`, `diposting_oleh`, `tanggal_diubah`, `diubah_oleh`) VALUES
(1, 1, 'PGB-0001', '2026-06-09', 1, 1, 'posted', NULL, '2026-06-09 04:44:16', 1, NULL, NULL, '2026-06-09 04:44:24', 1);

-- --------------------------------------------------------

--
-- Table structure for table `tb_pengambilan_bahan_detail`
--

CREATE TABLE `tb_pengambilan_bahan_detail` (
  `id_pengambilan_bahan_detail` bigint UNSIGNED NOT NULL,
  `id_pengambilan_bahan` bigint UNSIGNED NOT NULL,
  `id_bahan_baku` bigint UNSIGNED NOT NULL,
  `qty` int NOT NULL DEFAULT '0',
  `harga_satuan` decimal(18,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(18,2) NOT NULL DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_pengambilan_bahan_detail`
--

INSERT INTO `tb_pengambilan_bahan_detail` (`id_pengambilan_bahan_detail`, `id_pengambilan_bahan`, `id_bahan_baku`, `qty`, `harga_satuan`, `subtotal`) VALUES
(1, 1, 2, 50, '2000.00', '100000.00'),
(2, 1, 1, 10, '15000.00', '150000.00'),
(3, 1, 3, 20, '20000.00', '400000.00');

-- --------------------------------------------------------

--
-- Table structure for table `tb_pengguna`
--

CREATE TABLE `tb_pengguna` (
  `id_pengguna` bigint UNSIGNED NOT NULL,
  `id_entitas` bigint UNSIGNED NOT NULL,
  `id_role` bigint UNSIGNED NOT NULL,
  `nama_pengguna` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_lengkap` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `no_hp` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_aktif` tinyint(1) NOT NULL DEFAULT '1',
  `terakhir_login` datetime DEFAULT NULL,
  `tanggal_dibuat` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dibuat_oleh` bigint UNSIGNED DEFAULT NULL,
  `tanggal_diubah` datetime DEFAULT NULL,
  `diubah_oleh` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_pengguna`
--

INSERT INTO `tb_pengguna` (`id_pengguna`, `id_entitas`, `id_role`, `nama_pengguna`, `username`, `password_hash`, `nama_lengkap`, `email`, `no_hp`, `status_aktif`, `terakhir_login`, `tanggal_dibuat`, `dibuat_oleh`, `tanggal_diubah`, `diubah_oleh`) VALUES
(1, 1, 2, 'admin', 'admin', '$2y$10$6gkjiMxKK2MICBgcyRL1OefywNZkPi9h.D3b7kIbq02D20iEMNCYG', 'Administrator', 'admin@demo.local', '08123456789', 1, '2026-06-09 11:55:50', '2026-04-17 15:10:19', NULL, '2026-06-06 20:25:03', 1),
(2, 1, 3, 'Admin Penjualan', 'admin_penjualan', '$2y$10$ksw29.l/rCWlfKNy.RKZMu8dcnBS9GzseOgEF3aRwJ61FSN9Rg/Yu', 'Administrator Penjualan', 'admin.penjualan@demo.local', '080000000003', 1, '2026-06-06 20:29:01', '2026-04-27 17:56:08', 1, '2026-06-06 20:25:58', 1),
(3, 1, 4, 'Admin Pembelian', 'admin_pembelian', '$2y$10$JmAf01W0QxiX3vT9U8Xn8u9HtKi7i4wC1OnNnnmi3R5wUzZKXI1O6', 'Administrator Pembelian', 'admin.pembelian@demo.local', '080000000004', 1, '2026-06-06 20:28:41', '2026-04-27 17:56:08', 1, '2026-06-06 20:25:45', 1),
(4, 1, 5, 'Admin Produksi', 'admin_produksi', '$2y$10$nyeChQfqgco2KE6iNZo0aeGKvoRfVhlsqSuBH88B7gl4tzeSo3Laq', 'Administrator Produksi', 'admin.produksi@demo.local', '080000000005', 1, '2026-04-27 18:57:50', '2026-04-27 17:56:08', 1, '2026-06-06 20:26:10', 1),
(5, 1, 6, 'Admin Keuangan', 'admin_keuangan', '$2y$10$SDhZsREogQTjpN3/lCBXJO.i3VFJE5t71WGUTkZW/.vL3SOiPz5QG', 'Administrator Keuangan', 'admin.keuangan@demo.local', '080000000006', 1, '2026-06-08 09:57:09', '2026-04-27 17:56:08', 1, '2026-06-06 20:25:32', 1),
(6, 1, 7, 'Admin Gudang', 'admin_gudang', '$2y$10$R7sUN22cRFYZg848V2BtUu/c4XZhkKarPMSrIaOjDEvuoXuEAmM3K', 'Administrator Gudang', 'admin.gudang@demo.local', '080000000007', 1, '2026-06-06 20:27:11', '2026-04-27 17:56:08', 1, '2026-06-06 20:25:15', 1);

-- --------------------------------------------------------

--
-- Table structure for table `tb_pengguna_entitas`
--

CREATE TABLE `tb_pengguna_entitas` (
  `id_pengguna_entitas` bigint UNSIGNED NOT NULL,
  `id_pengguna` bigint UNSIGNED NOT NULL,
  `id_entitas` bigint UNSIGNED NOT NULL,
  `id_role` bigint UNSIGNED NOT NULL,
  `entitas_default` tinyint(1) NOT NULL DEFAULT '0',
  `status_aktif` tinyint(1) NOT NULL DEFAULT '1',
  `tanggal_dibuat` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dibuat_oleh` bigint UNSIGNED DEFAULT NULL,
  `tanggal_diubah` datetime DEFAULT NULL,
  `diubah_oleh` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_pengguna_entitas`
--

INSERT INTO `tb_pengguna_entitas` (`id_pengguna_entitas`, `id_pengguna`, `id_entitas`, `id_role`, `entitas_default`, `status_aktif`, `tanggal_dibuat`, `dibuat_oleh`, `tanggal_diubah`, `diubah_oleh`) VALUES
(1, 1, 1, 2, 1, 1, '2026-04-17 15:10:19', NULL, NULL, NULL),
(2, 2, 1, 3, 1, 1, '2026-04-27 17:56:19', 1, NULL, NULL),
(3, 3, 1, 4, 1, 1, '2026-04-27 17:56:19', 1, NULL, NULL),
(4, 4, 1, 5, 1, 1, '2026-04-27 17:56:19', 1, NULL, NULL),
(5, 5, 1, 6, 1, 1, '2026-04-27 17:56:19', 1, NULL, NULL),
(6, 6, 1, 7, 1, 1, '2026-04-27 17:56:19', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tb_penyerahan_penjualan`
--

CREATE TABLE `tb_penyerahan_penjualan` (
  `id_penyerahan_penjualan` bigint UNSIGNED NOT NULL,
  `id_entitas` bigint UNSIGNED NOT NULL,
  `no_penyerahan_penjualan` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal_penyerahan` date NOT NULL,
  `id_pesanan_penjualan` bigint UNSIGNED DEFAULT NULL,
  `id_pelanggan` bigint UNSIGNED NOT NULL,
  `id_gudang` bigint UNSIGNED NOT NULL,
  `jenis_alamat_tujuan` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pelanggan',
  `alamat_tujuan` text COLLATE utf8mb4_unicode_ci,
  `kurir_pengiriman` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `no_resi_pengiriman` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `biaya_pengiriman` decimal(18,2) NOT NULL DEFAULT '0.00',
  `id_coa_biaya_pengiriman` bigint UNSIGNED DEFAULT NULL,
  `status_penyerahan` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `catatan` text COLLATE utf8mb4_unicode_ci,
  `tanggal_dibuat` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dibuat_oleh` bigint UNSIGNED DEFAULT NULL,
  `tanggal_posting` datetime DEFAULT NULL,
  `diposting_oleh` bigint UNSIGNED DEFAULT NULL,
  `tanggal_diubah` datetime DEFAULT NULL,
  `diubah_oleh` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_penyerahan_penjualan`
--

INSERT INTO `tb_penyerahan_penjualan` (`id_penyerahan_penjualan`, `id_entitas`, `no_penyerahan_penjualan`, `tanggal_penyerahan`, `id_pesanan_penjualan`, `id_pelanggan`, `id_gudang`, `jenis_alamat_tujuan`, `alamat_tujuan`, `kurir_pengiriman`, `no_resi_pengiriman`, `biaya_pengiriman`, `id_coa_biaya_pengiriman`, `status_penyerahan`, `catatan`, `tanggal_dibuat`, `dibuat_oleh`, `tanggal_posting`, `diposting_oleh`, `tanggal_diubah`, `diubah_oleh`) VALUES
(1, 1, 'PNJ-202606-0001', '2026-06-09', NULL, 1, 1, 'pelanggan', NULL, NULL, NULL, '0.00', NULL, 'posted', 'POS: POS', '2026-06-09 05:30:26', 1, '2026-06-09 05:30:26', 1, NULL, NULL),
(2, 1, 'PNJ-0001', '2026-06-09', 5, 5, 1, 'lain', 'BanjarRaya', NULL, NULL, '0.00', NULL, 'draft', 'ongkirnya dimana ya?', '2026-06-09 06:15:26', 1, NULL, NULL, NULL, NULL),
(3, 1, 'PNJ-202606-0002', '2026-06-09', NULL, 1, 1, 'pelanggan', NULL, NULL, NULL, '0.00', NULL, 'posted', 'POS: POS', '2026-06-09 07:20:01', 1, '2026-06-09 07:20:01', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tb_penyerahan_penjualan_detail`
--

CREATE TABLE `tb_penyerahan_penjualan_detail` (
  `id_penyerahan_penjualan_detail` bigint UNSIGNED NOT NULL,
  `id_penyerahan_penjualan` bigint UNSIGNED NOT NULL,
  `id_produk` bigint UNSIGNED NOT NULL,
  `qty` decimal(18,2) NOT NULL DEFAULT '0.00',
  `hpp_satuan` decimal(18,2) NOT NULL DEFAULT '0.00',
  `hpp_total` decimal(18,2) NOT NULL DEFAULT '0.00',
  `catatan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_penyerahan_penjualan_detail`
--

INSERT INTO `tb_penyerahan_penjualan_detail` (`id_penyerahan_penjualan_detail`, `id_penyerahan_penjualan`, `id_produk`, `qty`, `hpp_satuan`, `hpp_total`, `catatan`) VALUES
(1, 1, 3, '1.00', '15666.67', '15666.67', NULL),
(2, 2, 3, '1.00', '15666.67', '15666.67', NULL),
(3, 3, 3, '1.00', '15666.67', '15666.67', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tb_perintah_produksi`
--

CREATE TABLE `tb_perintah_produksi` (
  `id_perintah_produksi` bigint UNSIGNED NOT NULL,
  `id_entitas` bigint UNSIGNED NOT NULL,
  `no_perintah_produksi` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal_perintah` date NOT NULL,
  `id_produk` bigint UNSIGNED NOT NULL,
  `id_resep` bigint UNSIGNED DEFAULT NULL,
  `qty_rencana` decimal(18,2) NOT NULL DEFAULT '0.00',
  `qty_hasil` decimal(18,2) NOT NULL DEFAULT '0.00',
  `status_produksi` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `tanggal_mulai` date DEFAULT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `id_pesanan_penjualan` bigint UNSIGNED DEFAULT NULL,
  `catatan` text COLLATE utf8mb4_unicode_ci,
  `tanggal_dibuat` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dibuat_oleh` bigint UNSIGNED DEFAULT NULL,
  `tanggal_diubah` datetime DEFAULT NULL,
  `diubah_oleh` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_perintah_produksi`
--

INSERT INTO `tb_perintah_produksi` (`id_perintah_produksi`, `id_entitas`, `no_perintah_produksi`, `tanggal_perintah`, `id_produk`, `id_resep`, `qty_rencana`, `qty_hasil`, `status_produksi`, `tanggal_mulai`, `tanggal_selesai`, `id_pesanan_penjualan`, `catatan`, `tanggal_dibuat`, `dibuat_oleh`, `tanggal_diubah`, `diubah_oleh`) VALUES
(1, 1, 'PPD-0001', '2026-06-09', 3, 1, '45.00', '45.00', 'posted', '2026-06-09', '2026-06-09', NULL, NULL, '2026-06-09 04:43:54', 1, '2026-06-09 04:46:34', 1);

-- --------------------------------------------------------

--
-- Table structure for table `tb_periode_akuntansi`
--

CREATE TABLE `tb_periode_akuntansi` (
  `id_periode` bigint UNSIGNED NOT NULL,
  `id_entitas` bigint UNSIGNED NOT NULL,
  `tahun` smallint NOT NULL,
  `bulan` tinyint NOT NULL,
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date NOT NULL,
  `status_periode` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'terbuka',
  `tanggal_dibuat` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dibuat_oleh` bigint UNSIGNED DEFAULT NULL,
  `tanggal_diubah` datetime DEFAULT NULL,
  `diubah_oleh` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_periode_akuntansi`
--

INSERT INTO `tb_periode_akuntansi` (`id_periode`, `id_entitas`, `tahun`, `bulan`, `tanggal_mulai`, `tanggal_selesai`, `status_periode`, `tanggal_dibuat`, `dibuat_oleh`, `tanggal_diubah`, `diubah_oleh`) VALUES
(1, 1, 2026, 4, '2026-04-01', '2026-04-30', 'terbuka', '2026-04-18 10:37:07', 1, NULL, NULL),
(2, 1, 2026, 6, '2026-06-01', '2026-06-30', 'terbuka', '2026-06-06 22:56:42', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tb_pesanan_online_chat`
--

CREATE TABLE `tb_pesanan_online_chat` (
  `id_chat` bigint UNSIGNED NOT NULL,
  `id_entitas` bigint UNSIGNED NOT NULL,
  `id_pesanan_penjualan` bigint UNSIGNED NOT NULL,
  `pengirim_tipe` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'customer',
  `nama_pengirim` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pesan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal_dibuat` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dibuat_oleh` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_pesanan_online_chat`
--

INSERT INTO `tb_pesanan_online_chat` (`id_chat`, `id_entitas`, `id_pesanan_penjualan`, `pengirim_tipe`, `nama_pengirim`, `pesan`, `tanggal_dibuat`, `dibuat_oleh`) VALUES
(1, 1, 1, 'customer', 'Yaya', 'tahap mana ya?', '2026-06-07 14:18:49', NULL),
(2, 1, 1, 'customer', 'Yaya', 'tahap mana ya?', '2026-06-07 14:18:50', NULL),
(3, 1, 2, 'customer', 'Yaya', 'Pesanan saya sudah sampai tahap mana?', '2026-06-07 14:41:00', NULL),
(4, 1, 2, 'admin', 'Administrator', 'belum ya mba, tolong bersabarrrrrr', '2026-06-07 14:41:58', 1),
(5, 1, 2, 'customer', 'Yaya', 'Bukti pembayaran saya sudah diterima?', '2026-06-07 14:50:34', NULL),
(6, 1, 2, 'admin', 'Administrator', 'ya sudah diterima', '2026-06-07 14:52:47', 1),
(7, 1, 2, 'admin', 'Administrator', 'ngga tau ahh belum kek nya :<', '2026-06-07 15:04:40', 1),
(8, 1, 2, 'customer', 'Yaya', 'Kapan pesanan saya bisa diproses?', '2026-06-07 15:07:17', NULL),
(9, 1, 2, 'customer', 'Yaya', 'ehhh tolong dong cepetttttt', '2026-06-07 15:07:31', NULL),
(10, 1, 2, 'customer', 'Yaya', 'P', '2026-06-07 15:18:19', NULL),
(11, 1, 1, 'customer', 'Yaya', 'dimana sudah barang?', '2026-06-08 09:52:55', NULL),
(12, 1, 1, 'admin', 'Administrator', 'kena lah lgi haur', '2026-06-08 09:53:53', 1),
(13, 1, 1, 'admin', 'Administrator', 'kena lah lgi haur', '2026-06-08 09:54:25', 1),
(14, 1, 1, 'admin', 'Administrator', 'kena lah lgi haur', '2026-06-08 09:54:31', 1),
(15, 1, 1, 'admin', 'Administrator', 'kena', '2026-06-08 09:54:46', 1),
(16, 1, 1, 'admin', 'Administrator', 'p', '2026-06-08 12:09:13', 1),
(17, 1, 1, 'admin', 'Administrator', 'y', '2026-06-08 12:10:19', 1),
(18, 1, 1, 'admin', 'Administrator', 'coba deh', '2026-06-08 12:30:20', 1);

-- --------------------------------------------------------

--
-- Table structure for table `tb_pesanan_online_chat_general`
--

CREATE TABLE `tb_pesanan_online_chat_general` (
  `id_chat_general` int NOT NULL,
  `id_entitas` int NOT NULL,
  `session_key` varchar(120) NOT NULL,
  `nama_pelanggan` varchar(150) DEFAULT NULL,
  `no_hp` varchar(40) DEFAULT NULL,
  `pengirim_tipe` enum('customer','admin') NOT NULL DEFAULT 'customer',
  `nama_pengirim` varchar(150) DEFAULT NULL,
  `pesan` text NOT NULL,
  `status_dibaca_admin` tinyint(1) NOT NULL DEFAULT '0',
  `status_dibaca_customer` tinyint(1) NOT NULL DEFAULT '0',
  `tanggal_dibuat` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tb_pesanan_online_chat_general`
--

INSERT INTO `tb_pesanan_online_chat_general` (`id_chat_general`, `id_entitas`, `session_key`, `nama_pelanggan`, `no_hp`, `pengirim_tipe`, `nama_pengirim`, `pesan`, `status_dibaca_admin`, `status_dibaca_customer`, `tanggal_dibuat`) VALUES
(1, 1, 'GEN1780819815556b89a680256f13', 'Ihya', '081254619752', 'customer', 'Ihya', 'Saya mau tanya produk Dodol Per Biji', 1, 0, '2026-06-07 16:10:33'),
(2, 1, 'GEN1780819815556b89a680256f13', 'Ihya', '081254619752', 'customer', 'Ihya', 'Berapa lama proses pesanannya?', 1, 0, '2026-06-07 16:10:41'),
(3, 1, 'GEN1780819815556b89a680256f13', 'Ihya', '081254619752', 'admin', 'Administrator', 'ngga lama sih', 1, 0, '2026-06-07 16:35:46'),
(4, 1, 'GEN1780819815556b89a680256f13', 'Ihya', '081254619752', 'customer', 'Ihya', 'Berapa lama proses pesanannya?', 1, 0, '2026-06-07 16:36:56'),
(5, 1, 'GEN1780819815556b89a680256f13', 'Ihya', '081254619752', 'customer', 'Ihya', 'Berapa lama proses pesanannya?', 1, 0, '2026-06-07 17:12:23');

-- --------------------------------------------------------

--
-- Table structure for table `tb_pesanan_pembelian`
--

CREATE TABLE `tb_pesanan_pembelian` (
  `id_pesanan_pembelian` bigint UNSIGNED NOT NULL,
  `id_entitas` bigint UNSIGNED NOT NULL,
  `no_pesanan_pembelian` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal_pesanan` date NOT NULL,
  `id_pemasok` bigint UNSIGNED NOT NULL,
  `status_pesanan` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `tanggal_datang_rencana` date DEFAULT NULL,
  `catatan` text COLLATE utf8mb4_unicode_ci,
  `subtotal` decimal(18,2) NOT NULL DEFAULT '0.00',
  `diskon` decimal(18,2) NOT NULL DEFAULT '0.00',
  `total` decimal(18,2) NOT NULL DEFAULT '0.00',
  `tanggal_dibuat` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dibuat_oleh` bigint UNSIGNED DEFAULT NULL,
  `tanggal_diubah` datetime DEFAULT NULL,
  `diubah_oleh` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tb_pesanan_pembelian_detail`
--

CREATE TABLE `tb_pesanan_pembelian_detail` (
  `id_pesanan_pembelian_detail` bigint UNSIGNED NOT NULL,
  `id_pesanan_pembelian` bigint UNSIGNED NOT NULL,
  `id_bahan_baku` bigint UNSIGNED NOT NULL,
  `qty` decimal(18,2) NOT NULL DEFAULT '0.00',
  `harga` decimal(18,2) NOT NULL DEFAULT '0.00',
  `diskon` decimal(18,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(18,2) NOT NULL DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tb_pesanan_penjualan`
--

CREATE TABLE `tb_pesanan_penjualan` (
  `id_pesanan_penjualan` bigint UNSIGNED NOT NULL,
  `id_entitas` bigint UNSIGNED NOT NULL,
  `no_pesanan_penjualan` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal_pesanan` date NOT NULL,
  `id_pelanggan` bigint UNSIGNED NOT NULL,
  `sumber_pesanan` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status_pesanan` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `tanggal_kirim_rencana` date DEFAULT NULL,
  `catatan` text COLLATE utf8mb4_unicode_ci,
  `subtotal` decimal(18,2) NOT NULL DEFAULT '0.00',
  `diskon` decimal(18,2) NOT NULL DEFAULT '0.00',
  `ppn_persen` decimal(5,2) NOT NULL DEFAULT '0.00',
  `ppn` decimal(18,2) NOT NULL DEFAULT '0.00',
  `total` decimal(18,2) NOT NULL DEFAULT '0.00',
  `metode_pembayaran_online` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nominal_pembayaran_online` decimal(18,2) NOT NULL DEFAULT '0.00',
  `status_pembayaran_online` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'belum_bayar',
  `status_refund_online` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nominal_refund_online` decimal(18,2) NOT NULL DEFAULT '0.00',
  `bukti_refund_online` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tanggal_refund_online` datetime DEFAULT NULL,
  `catatan_refund_online` text COLLATE utf8mb4_unicode_ci,
  `midtrans_order_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `midtrans_snap_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `midtrans_redirect_url` text COLLATE utf8mb4_unicode_ci,
  `midtrans_transaction_status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `midtrans_fraud_status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `midtrans_paid_at` datetime DEFAULT NULL,
  `midtrans_error_message` text COLLATE utf8mb4_unicode_ci,
  `midtrans_response_json` longtext COLLATE utf8mb4_unicode_ci,
  `duitku_order_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `duitku_reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `duitku_payment_url` text COLLATE utf8mb4_unicode_ci,
  `duitku_qr_string` text COLLATE utf8mb4_unicode_ci,
  `duitku_status_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `duitku_status_message` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `duitku_paid_at` datetime DEFAULT NULL,
  `duitku_response_json` longtext COLLATE utf8mb4_unicode_ci,
  `duitku_error_message` text COLLATE utf8mb4_unicode_ci,
  `tanggal_dibuat` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dibuat_oleh` bigint UNSIGNED DEFAULT NULL,
  `tanggal_diubah` datetime DEFAULT NULL,
  `diubah_oleh` bigint UNSIGNED DEFAULT NULL,
  `ipaymu_order_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ipaymu_session_id` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ipaymu_payment_url` text COLLATE utf8mb4_unicode_ci,
  `ipaymu_transaction_id` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ipaymu_status` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ipaymu_response_json` longtext COLLATE utf8mb4_unicode_ci,
  `ipaymu_error_message` text COLLATE utf8mb4_unicode_ci,
  `ipaymu_paid_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_pesanan_penjualan`
--

INSERT INTO `tb_pesanan_penjualan` (`id_pesanan_penjualan`, `id_entitas`, `no_pesanan_penjualan`, `tanggal_pesanan`, `id_pelanggan`, `sumber_pesanan`, `status_pesanan`, `tanggal_kirim_rencana`, `catatan`, `subtotal`, `diskon`, `ppn_persen`, `ppn`, `total`, `metode_pembayaran_online`, `nominal_pembayaran_online`, `status_pembayaran_online`, `status_refund_online`, `nominal_refund_online`, `bukti_refund_online`, `tanggal_refund_online`, `catatan_refund_online`, `midtrans_order_id`, `midtrans_snap_token`, `midtrans_redirect_url`, `midtrans_transaction_status`, `midtrans_fraud_status`, `midtrans_paid_at`, `midtrans_error_message`, `midtrans_response_json`, `duitku_order_id`, `duitku_reference`, `duitku_payment_url`, `duitku_qr_string`, `duitku_status_code`, `duitku_status_message`, `duitku_paid_at`, `duitku_response_json`, `duitku_error_message`, `tanggal_dibuat`, `dibuat_oleh`, `tanggal_diubah`, `diubah_oleh`, `ipaymu_order_id`, `ipaymu_session_id`, `ipaymu_payment_url`, `ipaymu_transaction_id`, `ipaymu_status`, `ipaymu_response_json`, `ipaymu_error_message`, `ipaymu_paid_at`) VALUES
(1, 1, 'PJO-202606-0001', '2026-06-09', 4, 'website', 'draft', NULL, 'Pesanan online dari website.\nNama: Jaya\nNo HP: 0895325944002\nEmail: ihya.wb5@gmail.com\nAlamat: Jl. A. Yani Km 15,200\nMetode pembayaran: QRIS', '500.00', '0.00', '0.00', '0.00', '500.00', 'qris', '500.00', 'menunggu_bayar', NULL, '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-09 05:06:50', NULL, '2026-06-09 05:06:54', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'iPaymu menolak transaksi: The POST method is not supported for route api/v2/payment/redirect. Supported methods: GET, HEAD.', NULL),
(2, 1, 'PJO-202606-0002', '2026-06-09', 4, 'website', 'draft', '2026-06-11', 'Pesanan online dari website.\nNama: Yaya\nNo HP: 0895325944002\nEmail: ihya.wb5@gmail.com\nAlamat: Jl. A. Yani Km 15,200\nMetode pembayaran: QRIS', '100000.00', '0.00', '0.00', '0.00', '100000.00', 'qris', '100000.00', 'menunggu_bayar', NULL, '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-09 05:10:48', NULL, '2026-06-09 05:10:51', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'iPaymu menolak transaksi: The POST method is not supported for route api/v2/payment/redirect. Supported methods: GET, HEAD.', NULL),
(3, 1, 'PJO-202606-0003', '2026-06-09', 2, 'website', 'draft', '2026-06-12', 'Pesanan online dari website.\nNama: Jaya\nNo HP: 081254619752\nEmail: ihya.wb5@gmail.com\nAlamat: Jl. A. Yani Km 15,200\nMetode pembayaran: QRIS', '50000.00', '0.00', '0.00', '0.00', '50000.00', 'qris', '50000.00', 'menunggu_bayar', NULL, '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-09 05:11:54', NULL, '2026-06-09 05:11:59', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'iPaymu menolak transaksi: The POST method is not supported for route api/v2/payment/redirect. Supported methods: GET, HEAD.', NULL),
(4, 1, 'PJO-202606-0004', '2026-06-09', 5, 'website', 'draft', '2026-06-12', 'Pesanan online dari website.\nNama: Jaya\nNo HP: 1111\nEmail: ihya.wb5@gmail.com\nAlamat: ambil sendiri\nMetode pembayaran: QRIS', '50000.00', '0.00', '0.00', '0.00', '50000.00', 'qris', '50000.00', 'menunggu_bayar', NULL, '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-09 05:17:17', NULL, '2026-06-09 05:17:21', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'iPaymu menolak transaksi: The POST method is not supported for route api/v2/payment/redirect. Supported methods: GET, HEAD.', NULL),
(5, 1, 'PJO-202606-0005', '2026-06-09', 5, 'website', 'diproses', '2026-06-12', 'Pesanan online dari website.\nNama: Jaya\nNo HP: 1111\nEmail: ihya.wb5@gmail.com\nAlamat: ambil sendiri\nMetode pembayaran: QRIS', '50000.00', '0.00', '0.00', '0.00', '50000.00', 'qris', '50000.00', 'menunggu_bayar', NULL, '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-09 05:17:21', NULL, '2026-06-09 06:15:26', 1, NULL, NULL, NULL, NULL, NULL, NULL, 'iPaymu menolak transaksi: The POST method is not supported for route api/v2/payment/redirect. Supported methods: GET, HEAD.', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tb_pesanan_penjualan_detail`
--

CREATE TABLE `tb_pesanan_penjualan_detail` (
  `id_pesanan_penjualan_detail` bigint UNSIGNED NOT NULL,
  `id_pesanan_penjualan` bigint UNSIGNED NOT NULL,
  `id_produk` bigint UNSIGNED NOT NULL,
  `qty` decimal(18,2) NOT NULL DEFAULT '0.00',
  `harga` decimal(18,2) NOT NULL DEFAULT '0.00',
  `diskon` decimal(18,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(18,2) NOT NULL DEFAULT '0.00',
  `hpp_standar` decimal(18,2) NOT NULL DEFAULT '0.00',
  `catatan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_pesanan_penjualan_detail`
--

INSERT INTO `tb_pesanan_penjualan_detail` (`id_pesanan_penjualan_detail`, `id_pesanan_penjualan`, `id_produk`, `qty`, `harga`, `diskon`, `subtotal`, `hpp_standar`, `catatan`) VALUES
(1, 1, 4, '1.00', '500.00', '0.00', '500.00', '0.00', NULL),
(2, 2, 3, '2.00', '50000.00', '0.00', '100000.00', '0.00', NULL),
(3, 3, 3, '1.00', '50000.00', '0.00', '50000.00', '0.00', NULL),
(4, 4, 3, '1.00', '50000.00', '0.00', '50000.00', '0.00', NULL),
(5, 5, 3, '1.00', '50000.00', '0.00', '50000.00', '0.00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tb_produk`
--

CREATE TABLE `tb_produk` (
  `id_produk` bigint UNSIGNED NOT NULL,
  `id_entitas` bigint UNSIGNED NOT NULL,
  `kode_produk` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `barcode_produk` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nama_produk` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_kategori_produk` bigint UNSIGNED DEFAULT NULL,
  `id_satuan` bigint UNSIGNED NOT NULL,
  `jenis_produk` enum('barang_jadi','setengah_jadi','jasa','non_persediaan') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'barang_jadi',
  `harga_jual` decimal(18,2) NOT NULL DEFAULT '0.00',
  `hpp_standar` decimal(18,2) NOT NULL DEFAULT '0.00',
  `stok_minimum` int NOT NULL DEFAULT '0',
  `berat_bersih` int DEFAULT NULL,
  `satuan_berat_bersih` enum('gram','kg') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'gram',
  `gambar_produk` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_produk` tinyint(1) NOT NULL DEFAULT '1',
  `id_coa_penjualan` bigint UNSIGNED DEFAULT NULL,
  `id_coa_hpp` bigint UNSIGNED DEFAULT NULL,
  `id_coa_persediaan` bigint UNSIGNED DEFAULT NULL,
  `tanggal_dibuat` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dibuat_oleh` bigint UNSIGNED DEFAULT NULL,
  `tanggal_diubah` datetime DEFAULT NULL,
  `diubah_oleh` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_produk`
--

INSERT INTO `tb_produk` (`id_produk`, `id_entitas`, `kode_produk`, `barcode_produk`, `nama_produk`, `id_kategori_produk`, `id_satuan`, `jenis_produk`, `harga_jual`, `hpp_standar`, `stok_minimum`, `berat_bersih`, `satuan_berat_bersih`, `gambar_produk`, `status_produk`, `id_coa_penjualan`, `id_coa_hpp`, `id_coa_persediaan`, `tanggal_dibuat`, `dibuat_oleh`, `tanggal_diubah`, `diubah_oleh`) VALUES
(3, 1, 'PRD-0001', '2000100000014', 'Dodol 1 Kg', 1, 1, 'barang_jadi', '50000.00', '0.00', 0, 1, 'kg', 'produk_6a2618fb105020.77154961.jpeg', 1, 31, 44, 9, '2026-06-06 22:18:56', 1, '2026-06-08 09:20:59', 1),
(4, 1, 'PRD-0002', '2000100000021', 'Dodol Per Biji', 1, 3, 'barang_jadi', '500.00', '0.00', 0, 10, 'gram', 'produk_6a2619057a6e52.29208370.jpeg', 1, 31, 44, 9, '2026-06-06 22:19:35', 1, '2026-06-08 09:21:09', 1);

-- --------------------------------------------------------

--
-- Table structure for table `tb_resep`
--

CREATE TABLE `tb_resep` (
  `id_resep` bigint UNSIGNED NOT NULL,
  `id_entitas` bigint UNSIGNED NOT NULL,
  `id_produk` bigint UNSIGNED NOT NULL,
  `kode_resep` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_resep` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jumlah_hasil` int NOT NULL,
  `versi_resep` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_aktif` tinyint(1) NOT NULL DEFAULT '1',
  `tanggal_dibuat` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dibuat_oleh` bigint UNSIGNED DEFAULT NULL,
  `tanggal_diubah` datetime DEFAULT NULL,
  `diubah_oleh` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_resep`
--

INSERT INTO `tb_resep` (`id_resep`, `id_entitas`, `id_produk`, `kode_resep`, `nama_resep`, `jumlah_hasil`, `versi_resep`, `status_aktif`, `tanggal_dibuat`, `dibuat_oleh`, `tanggal_diubah`, `diubah_oleh`) VALUES
(1, 1, 3, 'RSP-0001', 'Resep Dodol 1 Kawah', 45, 'V1', 1, '2026-06-06 22:22:28', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tb_resep_detail`
--

CREATE TABLE `tb_resep_detail` (
  `id_resep_detail` bigint UNSIGNED NOT NULL,
  `id_resep` bigint UNSIGNED NOT NULL,
  `id_bahan_baku` bigint UNSIGNED NOT NULL,
  `jumlah_pakai` int NOT NULL DEFAULT '0',
  `id_satuan` bigint UNSIGNED NOT NULL,
  `keterangan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_resep_detail`
--

INSERT INTO `tb_resep_detail` (`id_resep_detail`, `id_resep`, `id_bahan_baku`, `jumlah_pakai`, `id_satuan`, `keterangan`) VALUES
(1, 1, 2, 50, 3, 'Untuk 1 kawah'),
(2, 1, 1, 10, 1, 'Untuk 1 kawah'),
(3, 1, 3, 20, 1, 'Untuk 1 kawah');

-- --------------------------------------------------------

--
-- Table structure for table `tb_reset_data_log`
--

CREATE TABLE `tb_reset_data_log` (
  `id_reset_data_log` bigint UNSIGNED NOT NULL,
  `id_pengguna` bigint UNSIGNED DEFAULT NULL,
  `username` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tanggal_reset` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `total_baris_dihapus` int NOT NULL DEFAULT '0',
  `detail_tabel` longtext COLLATE utf8mb4_unicode_ci,
  `alasan` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_reset_data_log`
--

INSERT INTO `tb_reset_data_log` (`id_reset_data_log`, `id_pengguna`, `username`, `tanggal_reset`, `total_baris_dihapus`, `detail_tabel`, `alasan`) VALUES
(1, 1, 'admin', '2026-06-06 10:45:29', 340, '{\"tb_pembatalan_transaksi\":0,\"tb_log_jurnal_sumber\":27,\"tb_jurnal_detail\":70,\"tb_jurnal\":30,\"tb_kas_masuk\":0,\"tb_kas_keluar\":0,\"tb_saldo_stok\":12,\"tb_mutasi_stok\":62,\"tb_saldo_awal_stok_detail\":0,\"tb_saldo_awal_stok\":0,\"tb_stok_opname_detail\":0,\"tb_stok_opname\":0,\"tb_pembayaran_pembelian\":2,\"tb_faktur_pembelian_detail\":13,\"tb_faktur_pembelian\":3,\"tb_penerimaan_pembelian_detail\":13,\"tb_penerimaan_pembelian\":3,\"tb_pesanan_pembelian_detail\":13,\"tb_pesanan_pembelian\":3,\"tb_pembayaran_penjualan\":3,\"tb_faktur_penjualan_detail\":6,\"tb_faktur_penjualan\":3,\"tb_penyerahan_penjualan_detail\":6,\"tb_penyerahan_penjualan\":3,\"tb_pesanan_penjualan_detail\":0,\"tb_pesanan_penjualan\":0,\"tb_biaya_produksi_detail\":10,\"tb_biaya_produksi\":5,\"tb_hasil_produksi\":5,\"tb_pengambilan_bahan_detail\":38,\"tb_pengambilan_bahan\":5,\"tb_perintah_produksi\":5}', 'mengahapus data dummy'),
(2, 1, 'admin', '2026-06-06 10:54:05', 0, '{\"tb_pembatalan_transaksi\":0,\"tb_log_jurnal_sumber\":0,\"tb_jurnal_detail\":0,\"tb_jurnal\":0,\"tb_kas_masuk\":0,\"tb_kas_keluar\":0,\"tb_saldo_stok\":0,\"tb_mutasi_stok\":0,\"tb_saldo_awal_stok_detail\":0,\"tb_saldo_awal_stok\":0,\"tb_stok_opname_detail\":0,\"tb_stok_opname\":0,\"tb_pembayaran_pembelian\":0,\"tb_faktur_pembelian_detail\":0,\"tb_faktur_pembelian\":0,\"tb_penerimaan_pembelian_detail\":0,\"tb_penerimaan_pembelian\":0,\"tb_pesanan_pembelian_detail\":0,\"tb_pesanan_pembelian\":0,\"tb_pembayaran_penjualan\":0,\"tb_faktur_penjualan_detail\":0,\"tb_faktur_penjualan\":0,\"tb_penyerahan_penjualan_detail\":0,\"tb_penyerahan_penjualan\":0,\"tb_pesanan_penjualan_detail\":0,\"tb_pesanan_penjualan\":0,\"tb_biaya_produksi_detail\":0,\"tb_biaya_produksi\":0,\"tb_hasil_produksi\":0,\"tb_pengambilan_bahan_detail\":0,\"tb_pengambilan_bahan\":0,\"tb_perintah_produksi\":0}', 'DATA DUMMY'),
(3, 1, 'admin', '2026-06-06 11:07:19', 54, '{\"kelompok_dihapus\":[\"Transaksi, stok, mutasi, dan jurnal\",\"Produk, bahan baku, kategori, dan resep\",\"Pelanggan\",\"Pemasok\",\"Gudang\",\"Satuan\"],\"detail_tabel\":{\"tb_pembatalan_transaksi\":0,\"tb_log_jurnal_sumber\":0,\"tb_jurnal_detail\":0,\"tb_jurnal\":0,\"tb_kas_masuk\":0,\"tb_kas_keluar\":0,\"tb_saldo_stok\":0,\"tb_mutasi_stok\":0,\"tb_saldo_awal_stok_detail\":0,\"tb_saldo_awal_stok\":0,\"tb_stok_opname_detail\":0,\"tb_stok_opname\":0,\"tb_pembayaran_pembelian\":0,\"tb_faktur_pembelian_detail\":0,\"tb_faktur_pembelian\":0,\"tb_penerimaan_pembelian_detail\":0,\"tb_penerimaan_pembelian\":0,\"tb_pesanan_pembelian_detail\":0,\"tb_pesanan_pembelian\":0,\"tb_pembayaran_penjualan\":0,\"tb_faktur_penjualan_detail\":0,\"tb_faktur_penjualan\":0,\"tb_penyerahan_penjualan_detail\":0,\"tb_penyerahan_penjualan\":0,\"tb_pesanan_penjualan_detail\":0,\"tb_pesanan_penjualan\":0,\"tb_biaya_produksi_detail\":0,\"tb_biaya_produksi\":0,\"tb_hasil_produksi\":0,\"tb_pengambilan_bahan_detail\":0,\"tb_pengambilan_bahan\":0,\"tb_perintah_produksi\":0,\"tb_resep_detail\":23,\"tb_resep\":3,\"tb_produk\":3,\"tb_bahan_baku\":9,\"tb_kategori_produk\":1,\"tb_pelanggan\":3,\"tb_pemasok\":3,\"tb_gudang\":2,\"tb_satuan\":7}}', 'MENGHAPUS DATA DUMMY'),
(4, 1, 'admin', '2026-06-07 16:55:50', 52, '{\"kelompok_dihapus\":[\"Transaksi, stok, mutasi, dan jurnal\"],\"detail_tabel\":{\"tb_pembatalan_transaksi\":0,\"tb_log_jurnal_sumber\":4,\"tb_jurnal_detail\":10,\"tb_jurnal\":4,\"tb_kas_masuk\":0,\"tb_kas_keluar\":0,\"tb_saldo_stok\":5,\"tb_mutasi_stok\":9,\"tb_konversi_produk\":1,\"tb_saldo_awal_stok_detail\":0,\"tb_saldo_awal_stok\":0,\"tb_stok_opname_detail\":0,\"tb_stok_opname\":0,\"tb_pembayaran_pembelian\":0,\"tb_faktur_pembelian_detail\":0,\"tb_faktur_pembelian\":0,\"tb_penerimaan_pembelian_detail\":3,\"tb_penerimaan_pembelian\":1,\"tb_pesanan_pembelian_detail\":0,\"tb_pesanan_pembelian\":0,\"tb_pembayaran_penjualan\":0,\"tb_faktur_penjualan_detail\":0,\"tb_faktur_penjualan\":0,\"tb_penyerahan_penjualan_detail\":0,\"tb_penyerahan_penjualan\":0,\"tb_pesanan_penjualan_detail\":3,\"tb_pesanan_penjualan\":3,\"tb_biaya_produksi_detail\":2,\"tb_biaya_produksi\":1,\"tb_hasil_produksi\":1,\"tb_pengambilan_bahan_detail\":3,\"tb_pengambilan_bahan\":1,\"tb_perintah_produksi\":1}}', 'hapus data coba'),
(5, 1, 'admin', '2026-06-09 04:09:01', 86, '{\"kelompok_dihapus\":[\"Transaksi, stok, mutasi, dan jurnal\"],\"detail_tabel\":{\"tb_pembatalan_transaksi\":0,\"tb_log_jurnal_sumber\":9,\"tb_jurnal_detail\":21,\"tb_jurnal\":9,\"tb_kas_masuk\":0,\"tb_kas_keluar\":0,\"tb_saldo_stok\":5,\"tb_mutasi_stok\":11,\"tb_konversi_produk\":1,\"tb_saldo_awal_stok_detail\":0,\"tb_saldo_awal_stok\":0,\"tb_stok_opname_detail\":0,\"tb_stok_opname\":0,\"tb_pembayaran_pembelian\":1,\"tb_faktur_pembelian_detail\":3,\"tb_faktur_pembelian\":1,\"tb_penerimaan_pembelian_detail\":3,\"tb_penerimaan_pembelian\":1,\"tb_pesanan_pembelian_detail\":0,\"tb_pesanan_pembelian\":0,\"tb_pembayaran_penjualan\":1,\"tb_faktur_penjualan_detail\":1,\"tb_faktur_penjualan\":1,\"tb_penyerahan_penjualan_detail\":2,\"tb_penyerahan_penjualan\":2,\"tb_pesanan_penjualan_detail\":3,\"tb_pesanan_penjualan\":2,\"tb_biaya_produksi_detail\":2,\"tb_biaya_produksi\":1,\"tb_hasil_produksi\":1,\"tb_pengambilan_bahan_detail\":3,\"tb_pengambilan_bahan\":1,\"tb_perintah_produksi\":1}}', 'data dummy');

-- --------------------------------------------------------

--
-- Table structure for table `tb_role`
--

CREATE TABLE `tb_role` (
  `id_role` bigint UNSIGNED NOT NULL,
  `nama_role` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `keterangan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tanggal_dibuat` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dibuat_oleh` bigint UNSIGNED DEFAULT NULL,
  `tanggal_diubah` datetime DEFAULT NULL,
  `diubah_oleh` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_role`
--

INSERT INTO `tb_role` (`id_role`, `nama_role`, `keterangan`, `tanggal_dibuat`, `dibuat_oleh`, `tanggal_diubah`, `diubah_oleh`) VALUES
(1, 'super_admin', 'Akses penuh sistem', '2026-04-17 19:45:57', NULL, NULL, NULL),
(2, 'admin_entitas', 'Admin per entitas', '2026-04-17 19:45:57', NULL, NULL, NULL),
(3, 'admin_penjualan', 'Mengelola penjualan', '2026-04-17 19:45:57', NULL, NULL, NULL),
(4, 'admin_pembelian', 'Mengelola pembelian', '2026-04-17 19:45:57', NULL, NULL, NULL),
(5, 'admin_produksi', 'Mengelola produksi', '2026-04-17 19:45:57', NULL, NULL, NULL),
(6, 'admin_keuangan', 'Mengelola akuntansi dan kas', '2026-04-17 19:45:57', NULL, NULL, NULL),
(7, 'admin_gudang', 'Mengelola stok dan gudang', '2026-04-17 19:45:57', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tb_role_menu`
--

CREATE TABLE `tb_role_menu` (
  `id_role_menu` bigint UNSIGNED NOT NULL,
  `id_role` bigint UNSIGNED NOT NULL,
  `id_menu` bigint UNSIGNED NOT NULL,
  `boleh_lihat` tinyint(1) NOT NULL DEFAULT '1',
  `boleh_tambah` tinyint(1) NOT NULL DEFAULT '0',
  `boleh_ubah` tinyint(1) NOT NULL DEFAULT '0',
  `boleh_hapus` tinyint(1) NOT NULL DEFAULT '0',
  `boleh_posting` tinyint(1) NOT NULL DEFAULT '0',
  `boleh_approve` tinyint(1) NOT NULL DEFAULT '0',
  `boleh_cetak` tinyint(1) NOT NULL DEFAULT '0',
  `boleh_export` tinyint(1) NOT NULL DEFAULT '0',
  `status_aktif` tinyint(1) NOT NULL DEFAULT '1',
  `tanggal_dibuat` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dibuat_oleh` bigint UNSIGNED DEFAULT NULL,
  `tanggal_diubah` datetime DEFAULT NULL,
  `diubah_oleh` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_role_menu`
--

INSERT INTO `tb_role_menu` (`id_role_menu`, `id_role`, `id_menu`, `boleh_lihat`, `boleh_tambah`, `boleh_ubah`, `boleh_hapus`, `boleh_posting`, `boleh_approve`, `boleh_cetak`, `boleh_export`, `status_aktif`, `tanggal_dibuat`, `dibuat_oleh`, `tanggal_diubah`, `diubah_oleh`) VALUES
(1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(2, 1, 2, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(3, 1, 3, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(4, 1, 4, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(5, 1, 5, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(6, 1, 6, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(7, 1, 7, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(8, 1, 8, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(9, 1, 9, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(10, 1, 10, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(11, 1, 11, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, '2026-06-06 19:31:23', 1),
(12, 1, 12, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(13, 1, 13, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(14, 1, 14, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(15, 1, 15, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(16, 1, 16, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(17, 1, 17, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(18, 1, 18, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(19, 1, 19, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(20, 1, 20, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(21, 1, 21, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(22, 1, 22, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(23, 1, 23, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(24, 1, 24, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(25, 1, 25, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(26, 1, 26, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(27, 1, 30, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(28, 1, 31, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(29, 1, 32, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(30, 1, 33, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(31, 1, 34, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(32, 1, 35, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(33, 1, 40, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(34, 1, 41, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(35, 1, 42, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(36, 1, 43, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(37, 1, 44, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(38, 1, 45, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(39, 1, 50, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(40, 1, 51, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(41, 1, 52, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(42, 1, 53, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(43, 1, 54, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(44, 1, 55, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(45, 1, 60, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(46, 1, 61, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(47, 1, 62, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(48, 1, 63, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(49, 1, 64, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(50, 1, 65, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(51, 1, 66, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(52, 1, 67, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(53, 1, 68, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(54, 1, 69, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(55, 1, 70, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(56, 1, 80, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(64, 2, 70, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(65, 2, 18, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(66, 2, 44, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(67, 2, 66, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(68, 2, 8, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(69, 2, 1, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(70, 2, 51, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(71, 2, 61, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(72, 2, 31, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(73, 2, 21, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(74, 2, 41, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(75, 2, 34, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(76, 2, 24, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(77, 2, 3, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(78, 2, 13, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(79, 2, 45, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(80, 2, 64, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(81, 2, 63, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(82, 2, 62, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(83, 2, 16, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(84, 2, 60, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(85, 2, 68, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(86, 2, 65, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(87, 2, 80, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(88, 2, 11, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, '2026-06-06 19:31:23', 1),
(89, 2, 4, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(90, 2, 52, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(91, 2, 69, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(92, 2, 14, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(93, 2, 15, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(94, 2, 35, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(95, 2, 25, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(96, 2, 30, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(97, 2, 33, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(98, 2, 43, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(99, 2, 7, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(100, 2, 20, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(101, 2, 23, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(102, 2, 42, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(103, 2, 9, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(104, 2, 50, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(105, 2, 26, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(106, 2, 32, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(107, 2, 22, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(108, 2, 17, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(109, 2, 40, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(110, 2, 2, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(111, 2, 19, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(112, 2, 67, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(113, 2, 53, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(114, 2, 12, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(115, 2, 54, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(116, 2, 55, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(117, 2, 10, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(127, 3, 1, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(128, 3, 21, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(129, 3, 24, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(130, 3, 3, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(131, 3, 16, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(132, 3, 80, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(133, 3, 4, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(134, 3, 14, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(135, 3, 25, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(136, 3, 20, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(137, 3, 23, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(138, 3, 50, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(139, 3, 26, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(140, 3, 22, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(141, 3, 17, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(142, 3, 2, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(143, 3, 53, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(144, 3, 54, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(158, 4, 18, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(159, 4, 1, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(160, 4, 31, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(161, 4, 34, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(162, 4, 3, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(163, 4, 13, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(164, 4, 80, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(165, 4, 4, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(166, 4, 15, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(167, 4, 35, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(168, 4, 30, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(169, 4, 33, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(170, 4, 50, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(171, 4, 32, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(172, 4, 2, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(173, 4, 53, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(174, 4, 12, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(175, 4, 54, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(189, 5, 18, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(190, 5, 44, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(191, 5, 1, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(192, 5, 41, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(193, 5, 3, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(194, 5, 13, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(195, 5, 45, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(196, 5, 80, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(197, 5, 4, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(198, 5, 52, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(199, 5, 43, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(200, 5, 42, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(201, 5, 50, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(202, 5, 17, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(203, 5, 40, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(204, 5, 2, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(205, 5, 19, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(206, 5, 53, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(207, 5, 54, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(220, 7, 18, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(221, 7, 1, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(222, 7, 51, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(223, 7, 3, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(224, 7, 13, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(225, 7, 45, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(226, 7, 80, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(227, 7, 4, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(228, 7, 52, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(229, 7, 30, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(230, 7, 33, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(231, 7, 43, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(232, 7, 20, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(233, 7, 23, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(234, 7, 50, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(235, 7, 17, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(236, 7, 40, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(237, 7, 2, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(238, 7, 53, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(239, 7, 54, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(240, 7, 55, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(251, 6, 70, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(252, 6, 18, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(253, 6, 66, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(254, 6, 8, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(255, 6, 1, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(256, 6, 61, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(257, 6, 34, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(258, 6, 24, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(259, 6, 3, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(260, 6, 64, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(261, 6, 63, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(262, 6, 62, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(263, 6, 60, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(264, 6, 68, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(265, 6, 65, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(266, 6, 80, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(267, 6, 11, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, '2026-06-06 19:31:23', 1),
(268, 6, 4, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(269, 6, 52, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(270, 6, 69, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(271, 6, 14, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(272, 6, 15, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(273, 6, 35, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(274, 6, 25, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(275, 6, 30, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(276, 6, 20, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(277, 6, 9, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(278, 6, 50, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(279, 6, 17, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(280, 6, 2, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(281, 6, 67, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(282, 6, 53, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(283, 6, 55, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(284, 6, 10, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 12:34:28', NULL, NULL, NULL),
(314, 2, 5, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 15:55:10', NULL, NULL, NULL),
(315, 2, 6, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-04-17 15:55:10', NULL, NULL, NULL),
(316, 2, 84, 1, 1, 1, 1, 0, 0, 0, 0, 1, '2026-04-18 18:34:38', 1, NULL, NULL),
(317, 1, 84, 1, 1, 1, 1, 0, 0, 0, 0, 1, '2026-04-18 18:34:51', 1, NULL, NULL),
(318, 2, 86, 1, 1, 1, 1, 1, 0, 1, 1, 1, '2026-04-27 07:59:31', 1, NULL, NULL),
(319, 3, 86, 1, 1, 1, 1, 1, 0, 1, 1, 1, '2026-04-27 07:59:31', 1, NULL, NULL),
(320, 1, 86, 1, 1, 1, 1, 1, 0, 1, 1, 1, '2026-04-27 07:59:31', 1, NULL, NULL),
(321, 2, 87, 1, 1, 1, 0, 1, 0, 1, 0, 1, '2026-04-28 17:30:23', 1, NULL, NULL),
(322, 1, 87, 1, 1, 1, 0, 1, 0, 1, 0, 1, '2026-04-28 17:30:23', 1, NULL, NULL),
(323, 2, 88, 1, 1, 1, 1, 0, 0, 1, 1, 1, '2026-05-25 11:19:00', 1, NULL, NULL),
(324, 6, 88, 1, 1, 1, 1, 0, 0, 1, 1, 1, '2026-05-25 11:19:00', 1, NULL, NULL),
(325, 1, 88, 1, 1, 1, 1, 0, 0, 1, 1, 1, '2026-05-25 11:19:00', 1, NULL, NULL),
(326, 1, 89, 1, 1, 1, 1, 0, 0, 1, 1, 0, '2026-06-02 13:01:26', 1, '2026-06-06 19:31:23', 1),
(327, 2, 89, 1, 1, 1, 0, 0, 0, 1, 1, 0, '2026-06-02 13:01:26', 1, '2026-06-06 19:31:23', 1),
(328, 3, 89, 1, 1, 1, 0, 0, 0, 1, 1, 0, '2026-06-02 13:01:26', 1, '2026-06-06 19:31:23', 1),
(329, 4, 89, 1, 1, 1, 0, 0, 0, 1, 1, 0, '2026-06-02 13:01:26', 1, '2026-06-06 19:31:23', 1),
(330, 5, 89, 1, 1, 1, 0, 0, 0, 1, 1, 0, '2026-06-02 13:01:26', 1, '2026-06-06 19:31:23', 1),
(331, 7, 89, 1, 1, 1, 0, 0, 0, 1, 1, 0, '2026-06-02 13:01:26', 1, '2026-06-06 19:31:23', 1),
(332, 6, 89, 1, 1, 1, 0, 0, 0, 1, 1, 0, '2026-06-02 13:01:26', 1, '2026-06-06 19:31:23', 1),
(333, 1, 90, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-06-06 09:27:52', NULL, NULL, NULL),
(334, 2, 90, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-06-06 09:44:23', NULL, NULL, NULL),
(336, 2, 91, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-06-06 21:03:00', 1, NULL, NULL),
(337, 7, 91, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-06-06 21:03:00', 1, NULL, NULL),
(338, 3, 91, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-06-06 21:03:00', 1, NULL, NULL),
(339, 5, 91, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-06-06 21:03:00', 1, NULL, NULL),
(340, 1, 91, 1, 1, 1, 0, 1, 0, 1, 1, 1, '2026-06-06 21:03:00', 1, NULL, NULL),
(341, 2, 92, 1, 1, 1, 0, 0, 0, 1, 1, 1, '2026-06-07 15:06:23', 1, NULL, NULL),
(342, 3, 92, 1, 1, 1, 0, 0, 0, 1, 1, 1, '2026-06-07 15:06:23', 1, NULL, NULL),
(343, 1, 92, 1, 1, 1, 0, 0, 0, 1, 1, 1, '2026-06-07 15:06:23', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tb_saldo_awal_stok`
--

CREATE TABLE `tb_saldo_awal_stok` (
  `id_saldo_awal_stok` bigint UNSIGNED NOT NULL,
  `id_entitas` bigint UNSIGNED NOT NULL,
  `no_saldo_awal_stok` varchar(50) NOT NULL,
  `tanggal_saldo_awal` date NOT NULL,
  `id_gudang` bigint UNSIGNED NOT NULL,
  `id_coa_lawan` bigint UNSIGNED NOT NULL,
  `total_nilai` decimal(18,2) NOT NULL DEFAULT '0.00',
  `status_posting` varchar(30) NOT NULL DEFAULT 'draft',
  `catatan` text,
  `tanggal_dibuat` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dibuat_oleh` bigint UNSIGNED DEFAULT NULL,
  `tanggal_posting` datetime DEFAULT NULL,
  `diposting_oleh` bigint UNSIGNED DEFAULT NULL,
  `tanggal_diubah` datetime DEFAULT NULL,
  `diubah_oleh` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tb_saldo_awal_stok_detail`
--

CREATE TABLE `tb_saldo_awal_stok_detail` (
  `id_saldo_awal_stok_detail` bigint UNSIGNED NOT NULL,
  `id_saldo_awal_stok` bigint UNSIGNED NOT NULL,
  `jenis_barang` varchar(30) NOT NULL,
  `id_referensi_barang` bigint UNSIGNED NOT NULL,
  `qty_awal` decimal(18,3) NOT NULL DEFAULT '0.000',
  `harga_satuan` decimal(18,2) NOT NULL DEFAULT '0.00',
  `nilai_total` decimal(18,2) NOT NULL DEFAULT '0.00',
  `keterangan` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tb_saldo_stok`
--

CREATE TABLE `tb_saldo_stok` (
  `id_saldo_stok` bigint UNSIGNED NOT NULL,
  `id_entitas` bigint UNSIGNED NOT NULL,
  `jenis_barang` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_referensi_barang` bigint UNSIGNED NOT NULL,
  `id_gudang` bigint UNSIGNED NOT NULL,
  `qty_saldo` decimal(18,3) NOT NULL DEFAULT '0.000',
  `nilai_saldo` decimal(18,2) NOT NULL DEFAULT '0.00',
  `hpp_rata_rata` decimal(18,2) NOT NULL DEFAULT '0.00',
  `tanggal_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `tanggal_dibuat` datetime DEFAULT NULL,
  `dibuat_oleh` bigint UNSIGNED DEFAULT NULL,
  `tanggal_diubah` datetime DEFAULT NULL,
  `diubah_oleh` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_saldo_stok`
--

INSERT INTO `tb_saldo_stok` (`id_saldo_stok`, `id_entitas`, `jenis_barang`, `id_referensi_barang`, `id_gudang`, `qty_saldo`, `nilai_saldo`, `hpp_rata_rata`, `tanggal_update`, `tanggal_dibuat`, `dibuat_oleh`, `tanggal_diubah`, `diubah_oleh`) VALUES
(1, 1, 'bahan_baku', 2, 1, '0.000', '0.00', '2000.00', '2026-06-09 04:44:24', NULL, NULL, '2026-06-09 04:44:24', 1),
(2, 1, 'bahan_baku', 1, 1, '0.000', '0.00', '15000.00', '2026-06-09 04:44:24', NULL, NULL, '2026-06-09 04:44:24', 1),
(3, 1, 'bahan_baku', 3, 1, '0.000', '0.00', '20000.00', '2026-06-09 04:44:24', NULL, NULL, '2026-06-09 04:44:24', 1),
(4, 1, 'produk', 3, 1, '38.000', '595333.31', '15666.67', '2026-06-09 07:20:01', '2026-06-09 04:46:34', 1, '2026-06-09 07:20:01', 1),
(5, 1, 'produk', 4, 1, '500.000', '78333.35', '156.67', '2026-06-09 04:47:21', '2026-06-09 04:47:21', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tb_satuan`
--

CREATE TABLE `tb_satuan` (
  `id_satuan` bigint UNSIGNED NOT NULL,
  `nama_satuan` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `keterangan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tanggal_dibuat` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dibuat_oleh` bigint UNSIGNED DEFAULT NULL,
  `tanggal_diubah` datetime DEFAULT NULL,
  `diubah_oleh` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_satuan`
--

INSERT INTO `tb_satuan` (`id_satuan`, `nama_satuan`, `keterangan`, `tanggal_dibuat`, `dibuat_oleh`, `tanggal_diubah`, `diubah_oleh`) VALUES
(1, 'kg', 'kilogram', '2026-06-06 21:43:47', 1, NULL, NULL),
(2, 'pcs', 'per biji / satuan kecil', '2026-06-06 21:44:03', 1, NULL, NULL),
(3, 'biji', 'untuk kelapa', '2026-06-06 21:44:20', 1, NULL, NULL),
(4, 'kawah', 'untuk satu kali produksi', '2026-06-06 21:44:38', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tb_stok_opname`
--

CREATE TABLE `tb_stok_opname` (
  `id_stok_opname` bigint UNSIGNED NOT NULL,
  `id_entitas` bigint UNSIGNED NOT NULL,
  `no_stok_opname` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal_stok_opname` date NOT NULL,
  `id_gudang` bigint UNSIGNED NOT NULL,
  `status_posting` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `catatan` text COLLATE utf8mb4_unicode_ci,
  `tanggal_dibuat` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dibuat_oleh` bigint UNSIGNED DEFAULT NULL,
  `tanggal_diubah` datetime DEFAULT NULL,
  `diubah_oleh` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tb_stok_opname_detail`
--

CREATE TABLE `tb_stok_opname_detail` (
  `id_stok_opname_detail` bigint UNSIGNED NOT NULL,
  `id_stok_opname` bigint UNSIGNED NOT NULL,
  `jenis_barang` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_referensi_barang` bigint UNSIGNED NOT NULL,
  `qty_sistem` decimal(18,3) NOT NULL DEFAULT '0.000',
  `qty_fisik` decimal(18,3) NOT NULL DEFAULT '0.000',
  `selisih_qty` decimal(18,3) NOT NULL DEFAULT '0.000',
  `harga_satuan` decimal(18,2) NOT NULL DEFAULT '0.00',
  `nilai_selisih` decimal(18,2) NOT NULL DEFAULT '0.00',
  `keterangan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tb_template_jurnal`
--

CREATE TABLE `tb_template_jurnal` (
  `id_template_jurnal` bigint UNSIGNED NOT NULL,
  `id_entitas` bigint UNSIGNED NOT NULL,
  `kode_template_jurnal` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_template_jurnal` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kode_jenis_transaksi` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `keterangan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_aktif` tinyint(1) NOT NULL DEFAULT '1',
  `tanggal_dibuat` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dibuat_oleh` bigint UNSIGNED DEFAULT NULL,
  `tanggal_diubah` datetime DEFAULT NULL,
  `diubah_oleh` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_template_jurnal`
--

INSERT INTO `tb_template_jurnal` (`id_template_jurnal`, `id_entitas`, `kode_template_jurnal`, `nama_template_jurnal`, `kode_jenis_transaksi`, `keterangan`, `status_aktif`, `tanggal_dibuat`, `dibuat_oleh`, `tanggal_diubah`, `diubah_oleh`) VALUES
(3, 1, 'PJT-0001', 'Template Penjualan Tunai', 'PENJUALAN_TUNAI', 'Default template jurnal penjualan tunai', 1, '2026-04-18 11:23:41', 1, NULL, NULL),
(4, 1, 'PJK-0001', 'Template Penjualan Kredit', 'PENJUALAN_KREDIT', 'Default template jurnal penjualan kredit', 1, '2026-04-18 11:23:41', 1, NULL, NULL),
(5, 1, 'PPI-0001', 'Template Pelunasan Piutang', 'PELUNASAN_PIUTANG', 'Default template jurnal pelunasan piutang', 1, '2026-04-18 11:23:41', 1, NULL, NULL),
(6, 1, 'PBT-0001', 'Template Pembelian Bahan Baku Tunai', 'PEMBELIAN_BAHAN_BAKU_TUNAI', 'Default template pembelian bahan baku tunai', 1, '2026-04-18 11:23:41', 1, NULL, NULL),
(7, 1, 'PBK-0001', 'Template Pembelian Bahan Baku Kredit', 'PEMBELIAN_BAHAN_BAKU_KREDIT', 'Default template pembelian bahan baku kredit', 1, '2026-04-18 11:23:41', 1, NULL, NULL),
(8, 1, 'PPT-0001', 'Template Pembelian Bahan Penolong Tunai', 'PEMBELIAN_BAHAN_PENOLONG_TUNAI', 'Default template pembelian bahan penolong tunai', 1, '2026-04-18 11:23:41', 1, NULL, NULL),
(9, 1, 'PPK-0001', 'Template Pembelian Bahan Penolong Kredit', 'PEMBELIAN_BAHAN_PENOLONG_KREDIT', 'Default template pembelian bahan penolong kredit', 1, '2026-04-18 11:23:41', 1, NULL, NULL),
(10, 1, 'PKT-0001', 'Template Pembelian Kemasan Tunai', 'PEMBELIAN_KEMASAN_TUNAI', 'Default template pembelian kemasan tunai', 1, '2026-04-18 11:23:41', 1, NULL, NULL),
(11, 1, 'PKK-0001', 'Template Pembelian Kemasan Kredit', 'PEMBELIAN_KEMASAN_KREDIT', 'Default template pembelian kemasan kredit', 1, '2026-04-18 11:23:41', 1, NULL, NULL),
(12, 1, 'PUU-0001', 'Template Pembayaran Utang Usaha', 'PEMBAYARAN_UTANG_USAHA', 'Default template pembayaran utang usaha', 1, '2026-04-18 11:23:41', 1, NULL, NULL),
(13, 1, 'MBB-0001', 'Template Pemakaian Bahan Baku', 'PEMAKAIAN_BAHAN_BAKU', 'Default template pemakaian bahan baku', 1, '2026-04-18 11:23:41', 1, NULL, NULL),
(14, 1, 'MBP-0001', 'Template Pemakaian Bahan Penolong', 'PEMAKAIAN_BAHAN_PENOLONG', 'Default template pemakaian bahan penolong', 1, '2026-04-18 11:23:41', 1, NULL, NULL),
(15, 1, 'MKM-0001', 'Template Pemakaian Kemasan', 'PEMAKAIAN_KEMASAN', 'Default template pemakaian kemasan', 1, '2026-04-18 11:23:41', 1, NULL, NULL),
(16, 1, 'BTK-0001', 'Template Biaya Tenaga Kerja Langsung', 'BIAYA_TENAGA_KERJA_LANGSUNG', '1 dokumen BPR per jenis biaya per PPD agar total_biaya tepat sama dengan template.', 1, '2026-04-18 11:23:41', 1, '2026-05-25 09:06:49', 1),
(17, 1, 'BOP-0001', 'Template Biaya Overhead Pabrik', 'BIAYA_OVERHEAD_PABRIK', 'Default template biaya overhead pabrik', 1, '2026-04-18 11:23:41', 1, '2026-05-25 09:06:49', 1),
(18, 1, 'HPR-0001', 'Template Hasil Produksi', 'HASIL_PRODUKSI', 'Default template hasil produksi', 1, '2026-04-18 11:23:41', 1, NULL, NULL),
(19, 1, 'BGA-0001', 'Template Beban Gaji Administrasi', 'BEBAN_GAJI_ADMIN', 'Diinput sebagai kas keluar; ref PPD ditulis di keterangan karena tb_kas_keluar tidak punya id_perintah_produksi.', 1, '2026-04-18 11:23:41', 1, '2026-04-29 16:03:07', 1),
(20, 1, 'BPM-0001', 'Template Beban Pemasaran', 'BEBAN_PEMASARAN', 'Promosi/distribusi masuk laba rugi, tidak menambah HPP produksi.', 1, '2026-04-18 11:23:41', 1, '2026-04-29 16:03:07', 1),
(21, 1, 'BTR-0001', 'Template Beban Transportasi', 'BEBAN_TRANSPORTASI', 'Gunakan bila ada ongkir/transport operasional terpisah.', 1, '2026-04-18 11:23:41', 1, '2026-04-29 16:03:07', 1),
(22, 1, 'BAK-0001', 'Template Beban ATK dan Kebersihan', 'BEBAN_ATK_KEBERSIHAN', 'Gunakan untuk ATK dan kebersihan kantor/operasional.', 1, '2026-04-18 11:23:41', 1, '2026-04-29 16:03:07', 1),
(23, 1, 'BLA-0001', 'Template Beban Listrik dan Air Kantor', 'BEBAN_LISTRIK_AIR_KANTOR', 'Berbeda dari listrik produksi; ini beban kantor.', 1, '2026-04-18 11:23:41', 1, '2026-04-29 16:03:07', 1),
(24, 1, 'BLL-0001', 'Template Beban Lain-lain', 'BEBAN_LAIN_LAIN', 'Beban operasional lain-lain.', 1, '2026-04-18 11:23:41', 1, '2026-04-29 16:03:07', 1),
(25, 1, 'STB-0001', 'Template Setor Bank', 'SETOR_BANK', 'Default template setor bank', 1, '2026-04-18 11:23:41', 1, NULL, NULL),
(26, 1, 'TRB-0001', 'Template Tarik Bank', 'TARIK_BANK', 'Default template tarik bank', 1, '2026-04-18 11:23:41', 1, NULL, NULL),
(27, 1, 'KML-0001', 'Template Kas Masuk Lain', 'KAS_MASUK_LAIN', 'Default template kas masuk lain', 1, '2026-04-18 11:23:41', 1, NULL, NULL),
(28, 1, 'KKL-0001', 'Template Kas Keluar Lain', 'KAS_KELUAR_LAIN', 'Default template kas keluar lain', 1, '2026-04-18 11:23:41', 1, NULL, NULL),
(29, 1, 'PPP-0001', 'Template Penyusutan Peralatan Produksi', 'PENYUSUTAN_PERALATAN_PRODUKSI', 'Default template penyusutan peralatan produksi', 1, '2026-04-18 11:23:41', 1, NULL, NULL),
(30, 1, 'PPKT-0001', 'Template Penyusutan Peralatan Kantor', 'PENYUSUTAN_PERALATAN_KANTOR', 'Default template penyusutan peralatan kantor', 1, '2026-04-18 11:23:41', 1, NULL, NULL),
(31, 1, 'PYK-0001', 'Template Penyusutan Kendaraan', 'PENYUSUTAN_KENDARAAN', 'Default template penyusutan kendaraan', 1, '2026-04-18 11:23:41', 1, NULL, NULL),
(32, 1, 'FPBT-0001', 'Template Faktur Pembelian Tunai', 'FAKTUR_PEMBELIAN_TUNAI', 'Debit persediaan dan pajak masukan, kredit kas', 1, '2026-04-25 08:23:32', 1, '2026-04-25 16:26:07', 1),
(33, 1, 'FPBK-0001', 'Template Faktur Pembelian Kredit', 'FAKTUR_PEMBELIAN_KREDIT', 'Debit persediaan dan pajak masukan, kredit utang usaha', 1, '2026-04-25 08:23:43', 1, '2026-04-25 16:26:07', 1),
(34, 1, 'FPBTR-0001', 'Template Faktur Pembelian Transfer', 'FAKTUR_PEMBELIAN_TRANSFER', 'Debit persediaan dan pajak masukan, kredit bank', 1, '2026-04-25 08:23:53', 1, '2026-04-25 16:26:07', 1),
(36, 1, 'BLP-0001', 'Template Biaya Listrik Produksi', 'BIAYA_LISTRIK_PRODUKSI', 'Dipakai untuk listrik/gas produksi; masuk BOP produksi.', 1, '2026-04-25 10:53:53', 1, '2026-04-29 16:03:07', 1),
(37, 1, 'BKP-0001', 'Template Biaya Kemasan Produksi', 'BIAYA_KEMASAN_PRODUKSI', 'Dipakai jika kemasan dicatat sebagai biaya produksi, bukan sebagai bahan/kemasan stok.', 1, '2026-04-25 10:53:53', 1, '2026-04-29 16:03:07', 1),
(38, 1, 'BLPR-0001', 'Template Biaya Lain-lain Produksi', 'BIAYA_LAIN_LAIN_PRODUKSI', 'Dipakai untuk biaya produksi lain yang tidak masuk TKL/listrik/kemasan.', 1, '2026-04-25 10:53:53', 1, '2026-04-29 16:03:07', 1),
(42, 1, 'FPJK-0001', 'Template Faktur Penjualan Kredit', 'FAKTUR_PENJUALAN_KREDIT', 'Debit piutang, kredit penjualan kredit dan pajak keluaran', 1, '2026-04-25 16:26:21', 1, '2026-04-27 08:37:53', 1),
(43, 1, 'FPJT-0001', 'Template Faktur Penjualan Tunai', 'FAKTUR_PENJUALAN_TUNAI', 'Debit kas, kredit penjualan tunai dan pajak keluaran', 1, '2026-04-25 16:26:21', 1, '2026-04-27 08:37:53', 1),
(44, 1, 'FPJTR-0001', 'Template Faktur Penjualan Transfer', 'FAKTUR_PENJUALAN_TRANSFER', 'Debit bank, kredit penjualan tunai dan pajak keluaran', 1, '2026-04-25 16:26:21', 1, '2026-04-27 08:37:53', 1),
(45, 1, 'SALDO_AWAL_STOK', 'Saldo Awal Stok', 'SALDO_AWAL_STOK', 'Template jurnal saldo awal stok', 1, '2026-04-26 13:56:15', 1, NULL, NULL),
(46, 1, 'SOPP-0001', 'Template Stok Opname Selisih Lebih', 'STOK_OPNAME_PLUS', 'Debit persediaan, kredit pendapatan selisih stok', 1, '2026-04-27 07:02:33', 1, NULL, NULL),
(47, 1, 'SOPM-0001', 'Template Stok Opname Selisih Kurang', 'STOK_OPNAME_MINUS', 'Debit beban selisih stok, kredit persediaan', 1, '2026-04-27 07:02:33', 1, NULL, NULL),
(48, 1, 'PPHPP-0001', 'Template Penyerahan Penjualan HPP', 'PENYERAHAN_PENJUALAN_HPP', 'Debit HPP, kredit persediaan barang jadi', 1, '2026-04-27 08:36:07', 1, '2026-04-27 08:37:53', 1),
(49, 1, 'PPJT-0001', 'Template Pembayaran Penjualan Tunai', 'PEMBAYARAN_PENJUALAN_TUNAI', 'Debit kas, kredit piutang usaha', 1, '2026-04-27 08:36:07', 1, '2026-04-27 08:37:53', 1),
(50, 1, 'PPJTR-0001', 'Template Pembayaran Penjualan Transfer', 'PEMBAYARAN_PENJUALAN_TRANSFER', 'Debit bank, kredit piutang usaha', 1, '2026-04-27 08:36:07', 1, '2026-04-27 08:37:53', 1);

-- --------------------------------------------------------

--
-- Table structure for table `tb_template_jurnal_detail`
--

CREATE TABLE `tb_template_jurnal_detail` (
  `id_template_jurnal_detail` bigint UNSIGNED NOT NULL,
  `id_template_jurnal` bigint UNSIGNED NOT NULL,
  `urutan` int NOT NULL,
  `posisi_dc` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sumber_akun` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_coa_default` bigint UNSIGNED DEFAULT NULL,
  `sumber_nominal` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rumus_nominal` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `keterangan_baris` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_template_jurnal_detail`
--

INSERT INTO `tb_template_jurnal_detail` (`id_template_jurnal_detail`, `id_template_jurnal`, `urutan`, `posisi_dc`, `sumber_akun`, `id_coa_default`, `sumber_nominal`, `rumus_nominal`, `keterangan_baris`) VALUES
(12, 3, 1, 'debit', 'coa_default', 3, 'total_transaksi', NULL, 'Penerimaan kas penjualan tunai'),
(13, 3, 2, 'kredit', 'coa_default', 32, 'total_transaksi', NULL, 'Pengakuan penjualan tunai'),
(14, 3, 3, 'debit', 'coa_default', 44, 'total_hpp', NULL, 'HPP penjualan tunai'),
(15, 3, 4, 'kredit', 'coa_default', 9, 'total_hpp', NULL, 'Pengurangan persediaan barang jadi'),
(16, 4, 1, 'debit', 'coa_default', 5, 'total_transaksi', NULL, 'Piutang penjualan kredit'),
(17, 4, 2, 'kredit', 'coa_default', 33, 'total_transaksi', NULL, 'Pengakuan penjualan kredit'),
(18, 4, 3, 'debit', 'coa_default', 44, 'total_hpp', NULL, 'HPP penjualan kredit'),
(19, 4, 4, 'kredit', 'coa_default', 9, 'total_hpp', NULL, 'Pengurangan persediaan barang jadi'),
(20, 5, 1, 'debit', 'coa_default', 3, 'jumlah_bayar', NULL, 'Kas masuk pelunasan piutang'),
(21, 5, 2, 'kredit', 'coa_default', 5, 'jumlah_bayar', NULL, 'Pelunasan piutang usaha'),
(22, 6, 1, 'debit', 'coa_default', 6, 'total_transaksi', NULL, 'Pembelian bahan baku tunai'),
(23, 6, 2, 'kredit', 'coa_default', 3, 'total_transaksi', NULL, 'Pengeluaran kas pembelian bahan baku'),
(24, 7, 1, 'debit', 'coa_default', 6, 'total_transaksi', NULL, 'Pembelian bahan baku kredit'),
(25, 7, 2, 'kredit', 'coa_default', 20, 'total_transaksi', NULL, 'Utang usaha pembelian bahan baku'),
(26, 8, 1, 'debit', 'coa_default', 7, 'total_transaksi', NULL, 'Pembelian bahan penolong tunai'),
(27, 8, 2, 'kredit', 'coa_default', 3, 'total_transaksi', NULL, 'Pengeluaran kas pembelian bahan penolong'),
(28, 9, 1, 'debit', 'coa_default', 7, 'total_transaksi', NULL, 'Pembelian bahan penolong kredit'),
(29, 9, 2, 'kredit', 'coa_default', 20, 'total_transaksi', NULL, 'Utang usaha pembelian bahan penolong'),
(30, 10, 1, 'debit', 'coa_default', 8, 'total_transaksi', NULL, 'Pembelian kemasan tunai'),
(31, 10, 2, 'kredit', 'coa_default', 3, 'total_transaksi', NULL, 'Pengeluaran kas pembelian kemasan'),
(32, 11, 1, 'debit', 'coa_default', 8, 'total_transaksi', NULL, 'Pembelian kemasan kredit'),
(33, 11, 2, 'kredit', 'coa_default', 20, 'total_transaksi', NULL, 'Utang usaha pembelian kemasan'),
(34, 12, 1, 'debit', 'coa_default', 20, 'jumlah_bayar', NULL, 'Pembayaran utang usaha'),
(35, 12, 2, 'kredit', 'coa_default', 3, 'jumlah_bayar', NULL, 'Pengeluaran kas pembayaran utang'),
(36, 13, 1, 'debit', 'coa_default', 36, 'total_bahan', NULL, 'Pemakaian bahan baku ke produksi'),
(37, 13, 2, 'kredit', 'coa_default', 6, 'total_bahan', NULL, 'Pengurangan persediaan bahan baku'),
(38, 14, 1, 'debit', 'coa_default', 43, 'total_bahan', NULL, 'Pemakaian bahan penolong ke produksi'),
(39, 14, 2, 'kredit', 'coa_default', 7, 'total_bahan', NULL, 'Pengurangan persediaan bahan penolong'),
(40, 15, 1, 'debit', 'coa_default', 40, 'total_kemasan', NULL, 'Pemakaian kemasan produksi'),
(41, 15, 2, 'kredit', 'coa_default', 8, 'total_kemasan', NULL, 'Pengurangan persediaan kemasan'),
(44, 17, 1, 'debit', 'coa_default', 54, 'total_biaya', NULL, 'Biaya overhead pabrik'),
(45, 17, 2, 'kredit', 'coa_default', 3, 'total_biaya', NULL, 'Pengeluaran kas overhead pabrik'),
(46, 18, 1, 'debit', 'coa_default', 9, 'total_hpp', NULL, 'Hasil produksi masuk gudang'),
(47, 18, 2, 'kredit', 'coa_default', 36, 'nilai_bahan_baku', NULL, 'Penutupan bahan baku terpakai'),
(48, 18, 3, 'kredit', 'coa_default', 43, 'nilai_bahan_penolong', NULL, 'Penutupan bahan penolong terpakai'),
(49, 18, 4, 'kredit', 'coa_default', 39, 'nilai_tkl', NULL, 'Penutupan tenaga kerja langsung'),
(50, 18, 5, 'kredit', 'coa_default', 41, 'nilai_listrik_air', NULL, 'Penutupan listrik dan air produksi'),
(51, 18, 6, 'kredit', 'coa_default', 42, 'nilai_penyusutan', NULL, 'Penutupan penyusutan produksi'),
(64, 25, 1, 'debit', 'coa_default', 4, 'jumlah_setor', NULL, 'Setoran ke bank'),
(65, 25, 2, 'kredit', 'coa_default', 3, 'jumlah_setor', NULL, 'Pengurangan kas karena setor bank'),
(66, 26, 1, 'debit', 'coa_default', 3, 'jumlah_tarik', NULL, 'Tarik tunai dari bank'),
(67, 26, 2, 'kredit', 'coa_default', 4, 'jumlah_tarik', NULL, 'Pengurangan saldo bank'),
(68, 27, 1, 'debit', 'coa_default', 3, 'jumlah_masuk', NULL, 'Kas masuk lain-lain'),
(69, 27, 2, 'kredit', 'coa_default', 34, 'jumlah_masuk', NULL, 'Pendapatan lain-lain'),
(70, 28, 1, 'debit', 'coa_default', 52, 'jumlah_keluar', NULL, 'Kas keluar lain-lain'),
(71, 28, 2, 'kredit', 'coa_default', 3, 'jumlah_keluar', NULL, 'Pengeluaran kas lain-lain'),
(72, 29, 1, 'debit', 'coa_default', 42, 'total_penyusutan', NULL, 'Penyusutan peralatan produksi'),
(73, 29, 2, 'kredit', 'coa_default', 13, 'total_penyusutan', NULL, 'Akumulasi penyusutan peralatan produksi'),
(74, 30, 1, 'debit', 'coa_default', 51, 'total_penyusutan', NULL, 'Penyusutan peralatan kantor'),
(75, 30, 2, 'kredit', 'coa_default', 17, 'total_penyusutan', NULL, 'Akumulasi penyusutan peralatan kantor'),
(76, 31, 1, 'debit', 'coa_default', 51, 'total_penyusutan', NULL, 'Penyusutan kendaraan'),
(77, 31, 2, 'kredit', 'coa_default', 15, 'total_penyusutan', NULL, 'Akumulasi penyusutan kendaraan'),
(90, 33, 1, 'debit', 'coa_default', 6, 'rumus_nominal', 'subtotal_setelah_diskon', 'Persediaan bahan baku'),
(91, 33, 2, 'debit', 'coa_default', 55, 'rumus_nominal', 'ppn', 'Pajak masukan'),
(92, 33, 3, 'kredit', 'coa_default', 20, 'rumus_nominal', 'total', 'Utang usaha'),
(93, 32, 1, 'debit', 'coa_default', 6, 'rumus_nominal', 'subtotal_setelah_diskon', 'Persediaan bahan baku'),
(94, 32, 2, 'debit', 'coa_default', 55, 'rumus_nominal', 'ppn', 'Pajak masukan'),
(95, 32, 3, 'kredit', 'coa_default', 3, 'rumus_nominal', 'total', 'Kas keluar'),
(96, 34, 1, 'debit', 'coa_default', 6, 'rumus_nominal', 'subtotal_setelah_diskon', 'Persediaan bahan baku'),
(97, 34, 2, 'debit', 'coa_default', 55, 'rumus_nominal', 'ppn', 'Pajak masukan'),
(98, 34, 3, 'kredit', 'coa_default', 4, 'rumus_nominal', 'total', 'Bank keluar'),
(108, 46, 1, 'debit', 'akun_persediaan_barang', NULL, 'rumus_nominal', 'nilai_selisih_abs', 'Persediaan bertambah karena stok opname'),
(109, 46, 2, 'kredit', 'coa_default', 57, 'rumus_nominal', 'nilai_selisih_abs', 'Pendapatan selisih stok'),
(110, 47, 1, 'debit', 'coa_default', 58, 'rumus_nominal', 'nilai_selisih_abs', 'Beban selisih stok opname'),
(111, 47, 2, 'kredit', 'akun_persediaan_barang', NULL, 'rumus_nominal', 'nilai_selisih_abs', 'Persediaan berkurang karena stok opname'),
(127, 42, 1, 'debit', 'coa_default', 5, 'rumus_nominal', 'total', 'Piutang usaha faktur penjualan kredit'),
(128, 42, 2, 'kredit', 'coa_default', 33, 'rumus_nominal', 'subtotal_setelah_diskon', 'Penjualan kredit'),
(129, 42, 3, 'kredit', 'coa_default', 56, 'rumus_nominal', 'ppn', 'Pajak keluaran'),
(130, 43, 1, 'debit', 'coa_default', 3, 'rumus_nominal', 'total', 'Kas masuk penjualan tunai'),
(131, 43, 2, 'kredit', 'coa_default', 32, 'rumus_nominal', 'subtotal_setelah_diskon', 'Penjualan tunai'),
(132, 43, 3, 'kredit', 'coa_default', 56, 'rumus_nominal', 'ppn', 'Pajak keluaran'),
(133, 44, 1, 'debit', 'coa_default', 4, 'rumus_nominal', 'total', 'Bank masuk penjualan transfer'),
(134, 44, 2, 'kredit', 'coa_default', 32, 'rumus_nominal', 'subtotal_setelah_diskon', 'Penjualan transfer'),
(135, 44, 3, 'kredit', 'coa_default', 56, 'rumus_nominal', 'ppn', 'Pajak keluaran'),
(136, 48, 1, 'debit', 'coa_default', 44, 'rumus_nominal', 'total_hpp', 'Harga pokok penjualan'),
(137, 48, 2, 'kredit', 'coa_default', 9, 'rumus_nominal', 'total_hpp', 'Persediaan barang jadi keluar'),
(138, 49, 1, 'debit', 'coa_default', 3, 'rumus_nominal', 'jumlah_bayar', 'Kas masuk pembayaran piutang'),
(139, 49, 2, 'kredit', 'coa_default', 5, 'rumus_nominal', 'jumlah_bayar', 'Pelunasan piutang usaha'),
(140, 50, 1, 'debit', 'coa_default', 4, 'rumus_nominal', 'jumlah_bayar', 'Bank masuk pembayaran piutang'),
(141, 50, 2, 'kredit', 'coa_default', 5, 'rumus_nominal', 'jumlah_bayar', 'Pelunasan piutang usaha'),
(142, 16, 1, 'debit', 'coa_default', 39, 'total_biaya', NULL, 'Biaya tenaga kerja langsung'),
(143, 16, 2, 'kredit', 'coa_default', 3, 'total_biaya', NULL, 'Pengeluaran kas tenaga kerja langsung'),
(144, 36, 1, 'debit', 'coa_default', 41, 'total_biaya', NULL, 'Biaya listrik dan air produksi'),
(145, 36, 2, 'kredit', 'coa_default', 3, 'total_biaya', NULL, 'Pengeluaran kas biaya listrik produksi'),
(146, 37, 1, 'debit', 'coa_default', 53, 'total_biaya', NULL, 'Biaya kemasan produksi'),
(147, 37, 2, 'kredit', 'coa_default', 3, 'total_biaya', NULL, 'Pengeluaran kas biaya kemasan produksi'),
(148, 38, 1, 'debit', 'coa_default', 54, 'total_biaya', NULL, 'Biaya lain-lain produksi'),
(149, 38, 2, 'kredit', 'coa_default', 3, 'total_biaya', NULL, 'Pengeluaran kas biaya lain-lain produksi'),
(150, 19, 1, 'debit', 'coa_default', 46, 'total_biaya', NULL, 'Beban gaji administrasi'),
(151, 19, 2, 'kredit', 'coa_default', 3, 'total_biaya', NULL, 'Pengeluaran kas beban gaji administrasi'),
(152, 20, 1, 'debit', 'coa_default', 47, 'total_biaya', NULL, 'Beban pemasaran dan promosi'),
(153, 20, 2, 'kredit', 'coa_default', 3, 'total_biaya', NULL, 'Pengeluaran kas beban pemasaran dan promosi'),
(154, 21, 1, 'debit', 'coa_default', 48, 'total_biaya', NULL, 'Beban transportasi'),
(155, 21, 2, 'kredit', 'coa_default', 3, 'total_biaya', NULL, 'Pengeluaran kas beban transportasi'),
(156, 22, 1, 'debit', 'coa_default', 49, 'total_biaya', NULL, 'Beban ATK dan kebersihan'),
(157, 22, 2, 'kredit', 'coa_default', 3, 'total_biaya', NULL, 'Pengeluaran kas beban ATK dan kebersihan'),
(158, 23, 1, 'debit', 'coa_default', 50, 'total_biaya', NULL, 'Beban listrik dan air kantor'),
(159, 23, 2, 'kredit', 'coa_default', 3, 'total_biaya', NULL, 'Pengeluaran kas beban listrik dan air kantor'),
(160, 24, 1, 'debit', 'coa_default', 52, 'total_biaya', NULL, 'Beban lain-lain'),
(161, 24, 2, 'kredit', 'coa_default', 3, 'total_biaya', NULL, 'Pengeluaran kas beban lain-lain');

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_buku_besar`
-- (See below for the actual view)
--
CREATE TABLE `vw_buku_besar` (
`debit` decimal(18,2)
,`id_coa` bigint unsigned
,`id_entitas` bigint unsigned
,`id_jurnal` bigint unsigned
,`id_jurnal_detail` bigint unsigned
,`id_sumber` bigint unsigned
,`keterangan_baris` varchar(255)
,`kode_coa` varchar(30)
,`kode_jenis_transaksi` varchar(50)
,`kredit` decimal(18,2)
,`nama_coa` varchar(150)
,`no_jurnal` varchar(50)
,`no_sumber` varchar(50)
,`tabel_sumber` varchar(100)
,`tanggal_jurnal` date
,`urutan` int
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_role_menu_aktif`
-- (See below for the actual view)
--
CREATE TABLE `vw_role_menu_aktif` (
`boleh_approve` tinyint(1)
,`boleh_cetak` tinyint(1)
,`boleh_export` tinyint(1)
,`boleh_hapus` tinyint(1)
,`boleh_lihat` tinyint(1)
,`boleh_posting` tinyint(1)
,`boleh_tambah` tinyint(1)
,`boleh_ubah` tinyint(1)
,`id_menu` bigint unsigned
,`id_menu_induk` bigint unsigned
,`id_role` bigint unsigned
,`id_role_menu` bigint unsigned
,`ikon` varchar(100)
,`jenis_menu` varchar(20)
,`kode_menu` varchar(50)
,`nama_menu` varchar(100)
,`nama_role` varchar(100)
,`tingkat_menu` int
,`url` varchar(255)
,`urutan` int
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_saldo_coa`
-- (See below for the actual view)
--
CREATE TABLE `vw_saldo_coa` (
`id_coa` bigint unsigned
,`id_entitas` bigint unsigned
,`kategori_coa` varchar(50)
,`kode_coa` varchar(30)
,`nama_coa` varchar(150)
,`posisi_saldo_normal` varchar(10)
,`saldo_akhir` decimal(41,2)
,`total_debit` decimal(40,2)
,`total_kredit` decimal(40,2)
);

-- --------------------------------------------------------

--
-- Structure for view `vw_buku_besar`
--
DROP TABLE IF EXISTS `vw_buku_besar`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_buku_besar`  AS SELECT `j`.`id_entitas` AS `id_entitas`, `j`.`id_jurnal` AS `id_jurnal`, `j`.`no_jurnal` AS `no_jurnal`, `j`.`tanggal_jurnal` AS `tanggal_jurnal`, `j`.`kode_jenis_transaksi` AS `kode_jenis_transaksi`, `j`.`tabel_sumber` AS `tabel_sumber`, `j`.`id_sumber` AS `id_sumber`, `j`.`no_sumber` AS `no_sumber`, `jd`.`id_jurnal_detail` AS `id_jurnal_detail`, `jd`.`urutan` AS `urutan`, `jd`.`id_coa` AS `id_coa`, `c`.`kode_coa` AS `kode_coa`, `c`.`nama_coa` AS `nama_coa`, `jd`.`debit` AS `debit`, `jd`.`kredit` AS `kredit`, `jd`.`keterangan_baris` AS `keterangan_baris` FROM ((`tb_jurnal` `j` join `tb_jurnal_detail` `jd` on((`jd`.`id_jurnal` = `j`.`id_jurnal`))) join `tb_coa` `c` on((`c`.`id_coa` = `jd`.`id_coa`))) WHERE (`j`.`status_jurnal` <> 'batal')  ;

-- --------------------------------------------------------

--
-- Structure for view `vw_role_menu_aktif`
--
DROP TABLE IF EXISTS `vw_role_menu_aktif`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_role_menu_aktif`  AS SELECT `rm`.`id_role_menu` AS `id_role_menu`, `rm`.`id_role` AS `id_role`, `r`.`nama_role` AS `nama_role`, `rm`.`id_menu` AS `id_menu`, `m`.`kode_menu` AS `kode_menu`, `m`.`nama_menu` AS `nama_menu`, `m`.`id_menu_induk` AS `id_menu_induk`, `m`.`jenis_menu` AS `jenis_menu`, `m`.`url` AS `url`, `m`.`ikon` AS `ikon`, `m`.`urutan` AS `urutan`, `m`.`tingkat_menu` AS `tingkat_menu`, `rm`.`boleh_lihat` AS `boleh_lihat`, `rm`.`boleh_tambah` AS `boleh_tambah`, `rm`.`boleh_ubah` AS `boleh_ubah`, `rm`.`boleh_hapus` AS `boleh_hapus`, `rm`.`boleh_posting` AS `boleh_posting`, `rm`.`boleh_approve` AS `boleh_approve`, `rm`.`boleh_cetak` AS `boleh_cetak`, `rm`.`boleh_export` AS `boleh_export` FROM ((`tb_role_menu` `rm` join `tb_role` `r` on((`r`.`id_role` = `rm`.`id_role`))) join `tb_menu` `m` on((`m`.`id_menu` = `rm`.`id_menu`))) WHERE ((`rm`.`status_aktif` = 1) AND (`m`.`status_aktif` = 1) AND (`rm`.`boleh_lihat` = 1))  ;

-- --------------------------------------------------------

--
-- Structure for view `vw_saldo_coa`
--
DROP TABLE IF EXISTS `vw_saldo_coa`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_saldo_coa`  AS SELECT `j`.`id_entitas` AS `id_entitas`, `jd`.`id_coa` AS `id_coa`, `c`.`kode_coa` AS `kode_coa`, `c`.`nama_coa` AS `nama_coa`, `c`.`kategori_coa` AS `kategori_coa`, `c`.`posisi_saldo_normal` AS `posisi_saldo_normal`, sum(`jd`.`debit`) AS `total_debit`, sum(`jd`.`kredit`) AS `total_kredit`, (case when (`c`.`posisi_saldo_normal` = 'debit') then (sum(`jd`.`debit`) - sum(`jd`.`kredit`)) else (sum(`jd`.`kredit`) - sum(`jd`.`debit`)) end) AS `saldo_akhir` FROM ((`tb_jurnal` `j` join `tb_jurnal_detail` `jd` on((`jd`.`id_jurnal` = `j`.`id_jurnal`))) join `tb_coa` `c` on((`c`.`id_coa` = `jd`.`id_coa`))) WHERE (`j`.`status_jurnal` = 'posted') GROUP BY `j`.`id_entitas`, `jd`.`id_coa`, `c`.`kode_coa`, `c`.`nama_coa`, `c`.`kategori_coa`, `c`.`posisi_saldo_normal``posisi_saldo_normal`  ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tb_bahan_baku`
--
ALTER TABLE `tb_bahan_baku`
  ADD PRIMARY KEY (`id_bahan_baku`),
  ADD UNIQUE KEY `uk_tb_bahan_baku_entitas_kode` (`id_entitas`,`kode_bahan_baku`),
  ADD KEY `idx_tb_bahan_baku_entitas` (`id_entitas`),
  ADD KEY `idx_tb_bahan_baku_satuan` (`id_satuan`),
  ADD KEY `idx_tb_bahan_baku_coa_persediaan` (`id_coa_persediaan`);

--
-- Indexes for table `tb_biaya_produksi`
--
ALTER TABLE `tb_biaya_produksi`
  ADD PRIMARY KEY (`id_biaya_produksi`),
  ADD UNIQUE KEY `uk_tb_biaya_produksi_entitas_no` (`id_entitas`,`no_biaya_produksi`),
  ADD KEY `idx_tb_biaya_produksi_entitas` (`id_entitas`),
  ADD KEY `idx_tb_biaya_produksi_perintah` (`id_perintah_produksi`),
  ADD KEY `idx_tb_biaya_produksi_coa` (`id_coa_lawan`);

--
-- Indexes for table `tb_biaya_produksi_detail`
--
ALTER TABLE `tb_biaya_produksi_detail`
  ADD PRIMARY KEY (`id_biaya_produksi_detail`),
  ADD KEY `idx_biaya_produksi` (`id_biaya_produksi`),
  ADD KEY `idx_jenis_biaya_produksi` (`jenis_biaya_produksi`),
  ADD KEY `idx_coa_lawan` (`id_coa_lawan`);

--
-- Indexes for table `tb_coa`
--
ALTER TABLE `tb_coa`
  ADD PRIMARY KEY (`id_coa`),
  ADD UNIQUE KEY `uk_tb_coa_entitas_kode` (`id_entitas`,`kode_coa`),
  ADD KEY `idx_tb_coa_entitas` (`id_entitas`),
  ADD KEY `idx_tb_coa_induk` (`id_coa_induk`);

--
-- Indexes for table `tb_entitas`
--
ALTER TABLE `tb_entitas`
  ADD PRIMARY KEY (`id_entitas`),
  ADD UNIQUE KEY `uk_tb_entitas_kode` (`kode_entitas`),
  ADD KEY `idx_tb_entitas_nama` (`nama_entitas`);

--
-- Indexes for table `tb_faktur_pembelian`
--
ALTER TABLE `tb_faktur_pembelian`
  ADD PRIMARY KEY (`id_faktur_pembelian`),
  ADD UNIQUE KEY `uk_tb_faktur_pembelian_entitas_no` (`id_entitas`,`no_faktur_pembelian`),
  ADD KEY `idx_tb_faktur_pembelian_entitas` (`id_entitas`),
  ADD KEY `idx_tb_faktur_pembelian_pemasok` (`id_pemasok`),
  ADD KEY `idx_tb_faktur_pembelian_penerimaan` (`id_penerimaan_pembelian`);

--
-- Indexes for table `tb_faktur_pembelian_detail`
--
ALTER TABLE `tb_faktur_pembelian_detail`
  ADD PRIMARY KEY (`id_faktur_pembelian_detail`),
  ADD KEY `idx_tb_faktur_pembelian_detail_header` (`id_faktur_pembelian`),
  ADD KEY `idx_tb_faktur_pembelian_detail_bahan` (`id_bahan_baku`);

--
-- Indexes for table `tb_faktur_penjualan`
--
ALTER TABLE `tb_faktur_penjualan`
  ADD PRIMARY KEY (`id_faktur_penjualan`),
  ADD UNIQUE KEY `uk_tb_faktur_penjualan_entitas_no` (`id_entitas`,`no_faktur_penjualan`),
  ADD KEY `idx_tb_faktur_penjualan_entitas` (`id_entitas`),
  ADD KEY `idx_tb_faktur_penjualan_pelanggan` (`id_pelanggan`),
  ADD KEY `idx_tb_faktur_penjualan_penyerahan` (`id_penyerahan_penjualan`);

--
-- Indexes for table `tb_faktur_penjualan_detail`
--
ALTER TABLE `tb_faktur_penjualan_detail`
  ADD PRIMARY KEY (`id_faktur_penjualan_detail`),
  ADD KEY `idx_tb_faktur_penjualan_detail_header` (`id_faktur_penjualan`),
  ADD KEY `idx_tb_faktur_penjualan_detail_produk` (`id_produk`);

--
-- Indexes for table `tb_gudang`
--
ALTER TABLE `tb_gudang`
  ADD PRIMARY KEY (`id_gudang`),
  ADD UNIQUE KEY `uk_tb_gudang_entitas_kode` (`id_entitas`,`kode_gudang`),
  ADD KEY `idx_tb_gudang_entitas` (`id_entitas`),
  ADD KEY `fk_tb_gudang_diubah_oleh` (`diubah_oleh`);

--
-- Indexes for table `tb_hasil_produksi`
--
ALTER TABLE `tb_hasil_produksi`
  ADD PRIMARY KEY (`id_hasil_produksi`),
  ADD UNIQUE KEY `uk_tb_hasil_produksi_entitas_no` (`id_entitas`,`no_hasil_produksi`),
  ADD KEY `idx_tb_hasil_produksi_entitas` (`id_entitas`),
  ADD KEY `idx_tb_hasil_produksi_perintah` (`id_perintah_produksi`),
  ADD KEY `idx_tb_hasil_produksi_produk` (`id_produk`),
  ADD KEY `idx_tb_hasil_produksi_gudang` (`id_gudang`);

--
-- Indexes for table `tb_jurnal`
--
ALTER TABLE `tb_jurnal`
  ADD PRIMARY KEY (`id_jurnal`),
  ADD UNIQUE KEY `uk_tb_jurnal_entitas_no` (`id_entitas`,`no_jurnal`),
  ADD KEY `idx_tb_jurnal_entitas` (`id_entitas`),
  ADD KEY `idx_tb_jurnal_periode` (`id_periode`),
  ADD KEY `idx_tb_jurnal_sumber` (`tabel_sumber`,`id_sumber`);

--
-- Indexes for table `tb_jurnal_detail`
--
ALTER TABLE `tb_jurnal_detail`
  ADD PRIMARY KEY (`id_jurnal_detail`),
  ADD KEY `idx_tb_jurnal_detail_jurnal` (`id_jurnal`),
  ADD KEY `idx_tb_jurnal_detail_coa` (`id_coa`),
  ADD KEY `idx_tb_jurnal_detail_pelanggan` (`id_pelanggan`),
  ADD KEY `idx_tb_jurnal_detail_pemasok` (`id_pemasok`),
  ADD KEY `idx_tb_jurnal_detail_produk` (`id_produk`),
  ADD KEY `idx_tb_jurnal_detail_bahan` (`id_bahan_baku`),
  ADD KEY `idx_tb_jurnal_detail_gudang` (`id_gudang`);

--
-- Indexes for table `tb_kas_keluar`
--
ALTER TABLE `tb_kas_keluar`
  ADD PRIMARY KEY (`id_kas_keluar`),
  ADD UNIQUE KEY `uk_tb_kas_keluar_entitas_no` (`id_entitas`,`no_kas_keluar`),
  ADD KEY `idx_tb_kas_keluar_entitas` (`id_entitas`),
  ADD KEY `idx_tb_kas_keluar_coa_beban` (`id_coa_beban`),
  ADD KEY `idx_tb_kas_keluar_coa_kas` (`id_coa_kas_bank`);

--
-- Indexes for table `tb_kas_masuk`
--
ALTER TABLE `tb_kas_masuk`
  ADD PRIMARY KEY (`id_kas_masuk`),
  ADD UNIQUE KEY `uk_tb_kas_masuk_entitas_no` (`id_entitas`,`no_kas_masuk`),
  ADD KEY `idx_tb_kas_masuk_entitas` (`id_entitas`),
  ADD KEY `idx_tb_kas_masuk_coa` (`id_coa_kas_bank`);

--
-- Indexes for table `tb_kategori_produk`
--
ALTER TABLE `tb_kategori_produk`
  ADD PRIMARY KEY (`id_kategori_produk`),
  ADD UNIQUE KEY `uk_tb_kategori_produk_entitas_kode` (`id_entitas`,`kode_kategori_produk`),
  ADD KEY `idx_tb_kategori_produk_entitas` (`id_entitas`),
  ADD KEY `fk_tb_kategori_produk_dibuat_oleh` (`dibuat_oleh`),
  ADD KEY `fk_tb_kategori_produk_diubah_oleh` (`diubah_oleh`);

--
-- Indexes for table `tb_konfigurasi_akun`
--
ALTER TABLE `tb_konfigurasi_akun`
  ADD PRIMARY KEY (`id_konfigurasi_akun`),
  ADD UNIQUE KEY `uniq_konfigurasi_akun` (`id_entitas`,`kode_konfigurasi`),
  ADD KEY `idx_konfigurasi_akun_entitas` (`id_entitas`),
  ADD KEY `idx_konfigurasi_akun_coa` (`id_coa`);

--
-- Indexes for table `tb_konversi_produk`
--
ALTER TABLE `tb_konversi_produk`
  ADD PRIMARY KEY (`id_konversi_produk`),
  ADD KEY `idx_konversi_produk_entitas` (`id_entitas`),
  ADD KEY `idx_konversi_produk_sumber` (`id_produk_sumber`),
  ADD KEY `idx_konversi_produk_tujuan` (`id_produk_tujuan`),
  ADD KEY `idx_konversi_produk_gudang` (`id_gudang`),
  ADD KEY `idx_konversi_produk_status` (`status_posting`);

--
-- Indexes for table `tb_log_jurnal_sumber`
--
ALTER TABLE `tb_log_jurnal_sumber`
  ADD PRIMARY KEY (`id_log_jurnal_sumber`),
  ADD KEY `idx_tb_log_jurnal_sumber_entitas` (`id_entitas`),
  ADD KEY `idx_tb_log_jurnal_sumber_jurnal` (`id_jurnal`),
  ADD KEY `idx_tb_log_jurnal_sumber_sumber` (`tabel_sumber`,`id_sumber`);

--
-- Indexes for table `tb_log_login`
--
ALTER TABLE `tb_log_login`
  ADD PRIMARY KEY (`id_log_login`),
  ADD KEY `idx_tb_log_login_pengguna` (`id_pengguna`),
  ADD KEY `idx_tb_log_login_entitas` (`id_entitas`),
  ADD KEY `idx_tb_log_login_waktu` (`waktu_login`);

--
-- Indexes for table `tb_mapping_akun`
--
ALTER TABLE `tb_mapping_akun`
  ADD PRIMARY KEY (`id_mapping_akun`),
  ADD UNIQUE KEY `uk_tb_mapping_akun_unik_v2` (`id_entitas`,`kode_jenis_transaksi`,`jenis_objek`,`id_objek`,`peran_akun`,`id_coa`),
  ADD KEY `idx_tb_mapping_akun_entitas` (`id_entitas`),
  ADD KEY `idx_tb_mapping_akun_coa` (`id_coa`);

--
-- Indexes for table `tb_mapping_laporan`
--
ALTER TABLE `tb_mapping_laporan`
  ADD PRIMARY KEY (`id_mapping_laporan`),
  ADD UNIQUE KEY `uk_mapping_laporan_unik` (`id_entitas`,`kode_laporan`,`kategori_kode`,`sub_kode`),
  ADD KEY `idx_mapping_laporan_entitas` (`id_entitas`),
  ADD KEY `idx_mapping_laporan_kode` (`kode_laporan`),
  ADD KEY `idx_mapping_laporan_parent` (`parent_id`),
  ADD KEY `idx_mapping_laporan_urutan` (`kode_laporan`,`urutan`,`sub_urutan`);

--
-- Indexes for table `tb_mapping_laporan_detail`
--
ALTER TABLE `tb_mapping_laporan_detail`
  ADD PRIMARY KEY (`id_mapping_laporan_detail`),
  ADD KEY `idx_mld_mapping` (`id_mapping_laporan`),
  ADD KEY `idx_mld_entitas` (`id_entitas`),
  ADD KEY `idx_mld_tipe_sumber` (`tipe_sumber`,`nilai_sumber`),
  ADD KEY `idx_mld_coa` (`id_coa`);

--
-- Indexes for table `tb_menu`
--
ALTER TABLE `tb_menu`
  ADD PRIMARY KEY (`id_menu`),
  ADD UNIQUE KEY `uk_tb_menu_kode` (`kode_menu`),
  ADD KEY `idx_tb_menu_induk` (`id_menu_induk`),
  ADD KEY `idx_tb_menu_urutan` (`urutan`);

--
-- Indexes for table `tb_mutasi_stok`
--
ALTER TABLE `tb_mutasi_stok`
  ADD PRIMARY KEY (`id_mutasi_stok`),
  ADD KEY `idx_tb_mutasi_stok_entitas` (`id_entitas`),
  ADD KEY `idx_tb_mutasi_stok_barang` (`jenis_barang`,`id_referensi_barang`),
  ADD KEY `idx_tb_mutasi_stok_gudang` (`id_gudang`),
  ADD KEY `idx_tb_mutasi_stok_sumber` (`tabel_sumber`,`id_sumber`);

--
-- Indexes for table `tb_pajak`
--
ALTER TABLE `tb_pajak`
  ADD PRIMARY KEY (`id_pajak`),
  ADD KEY `idx_tb_pajak_entitas` (`id_entitas`),
  ADD KEY `idx_tb_pajak_kode` (`kode_pajak`),
  ADD KEY `idx_tb_pajak_default` (`id_entitas`,`kode_pajak`,`is_default`,`status_aktif`);

--
-- Indexes for table `tb_payment_gateway_setting`
--
ALTER TABLE `tb_payment_gateway_setting`
  ADD PRIMARY KEY (`id_payment_gateway_setting`),
  ADD UNIQUE KEY `uq_gateway_entitas_provider` (`id_entitas`,`provider`);

--
-- Indexes for table `tb_pelanggan`
--
ALTER TABLE `tb_pelanggan`
  ADD PRIMARY KEY (`id_pelanggan`),
  ADD UNIQUE KEY `uk_tb_pelanggan_entitas_kode` (`id_entitas`,`kode_pelanggan`),
  ADD KEY `idx_tb_pelanggan_entitas` (`id_entitas`),
  ADD KEY `idx_tb_pelanggan_nama` (`nama_pelanggan`),
  ADD KEY `fk_tb_pelanggan_diubah_oleh` (`diubah_oleh`);

--
-- Indexes for table `tb_pemasok`
--
ALTER TABLE `tb_pemasok`
  ADD PRIMARY KEY (`id_pemasok`),
  ADD UNIQUE KEY `uk_tb_pemasok_entitas_kode` (`id_entitas`,`kode_pemasok`),
  ADD KEY `idx_tb_pemasok_entitas` (`id_entitas`),
  ADD KEY `idx_tb_pemasok_nama` (`nama_pemasok`),
  ADD KEY `fk_tb_pemasok_diubah_oleh` (`diubah_oleh`);

--
-- Indexes for table `tb_pembatalan_transaksi`
--
ALTER TABLE `tb_pembatalan_transaksi`
  ADD PRIMARY KEY (`id_pembatalan_transaksi`),
  ADD UNIQUE KEY `uk_pembatalan_no` (`id_entitas`,`no_pembatalan`),
  ADD UNIQUE KEY `uk_pembatalan_jurnal_asal` (`id_entitas`,`id_jurnal_asal`),
  ADD KEY `idx_pembatalan_entitas` (`id_entitas`),
  ADD KEY `idx_pembatalan_tanggal` (`tanggal_pembatalan`),
  ADD KEY `idx_pembatalan_jurnal_reversal` (`id_jurnal_reversal`),
  ADD KEY `idx_pembatalan_sumber` (`tabel_sumber`,`id_sumber`),
  ADD KEY `fk_pembatalan_jurnal_asal` (`id_jurnal_asal`);

--
-- Indexes for table `tb_pembayaran_online_bukti`
--
ALTER TABLE `tb_pembayaran_online_bukti`
  ADD PRIMARY KEY (`id_bukti_pembayaran_online`),
  ADD KEY `idx_bukti_pesanan` (`id_pesanan_penjualan`),
  ADD KEY `idx_bukti_entitas_status` (`id_entitas`,`status_verifikasi`);

--
-- Indexes for table `tb_pembayaran_pembelian`
--
ALTER TABLE `tb_pembayaran_pembelian`
  ADD PRIMARY KEY (`id_pembayaran_pembelian`),
  ADD UNIQUE KEY `uk_tb_pembayaran_pembelian_entitas_no` (`id_entitas`,`no_pembayaran_pembelian`),
  ADD KEY `idx_tb_pembayaran_pembelian_entitas` (`id_entitas`),
  ADD KEY `idx_tb_pembayaran_pembelian_faktur` (`id_faktur_pembelian`),
  ADD KEY `idx_tb_pembayaran_pembelian_pemasok` (`id_pemasok`),
  ADD KEY `idx_tb_pembayaran_pembelian_coa` (`id_coa_kas_bank`);

--
-- Indexes for table `tb_pembayaran_penjualan`
--
ALTER TABLE `tb_pembayaran_penjualan`
  ADD PRIMARY KEY (`id_pembayaran_penjualan`),
  ADD UNIQUE KEY `uk_tb_pembayaran_penjualan_entitas_no` (`id_entitas`,`no_pembayaran_penjualan`),
  ADD KEY `idx_tb_pembayaran_penjualan_entitas` (`id_entitas`),
  ADD KEY `idx_tb_pembayaran_penjualan_faktur` (`id_faktur_penjualan`),
  ADD KEY `idx_tb_pembayaran_penjualan_pelanggan` (`id_pelanggan`),
  ADD KEY `idx_tb_pembayaran_penjualan_coa` (`id_coa_kas_bank`);

--
-- Indexes for table `tb_penerimaan_pembelian`
--
ALTER TABLE `tb_penerimaan_pembelian`
  ADD PRIMARY KEY (`id_penerimaan_pembelian`),
  ADD UNIQUE KEY `uk_tb_penerimaan_pembelian_entitas_no` (`id_entitas`,`no_penerimaan_pembelian`),
  ADD KEY `idx_tb_penerimaan_pembelian_entitas` (`id_entitas`),
  ADD KEY `idx_tb_penerimaan_pembelian_pesanan` (`id_pesanan_pembelian`),
  ADD KEY `idx_tb_penerimaan_pembelian_pemasok` (`id_pemasok`),
  ADD KEY `idx_tb_penerimaan_pembelian_gudang` (`id_gudang`);

--
-- Indexes for table `tb_penerimaan_pembelian_detail`
--
ALTER TABLE `tb_penerimaan_pembelian_detail`
  ADD PRIMARY KEY (`id_penerimaan_pembelian_detail`),
  ADD KEY `idx_tb_penerimaan_pembelian_detail_header` (`id_penerimaan_pembelian`),
  ADD KEY `idx_tb_penerimaan_pembelian_detail_bahan` (`id_bahan_baku`);

--
-- Indexes for table `tb_pengambilan_bahan`
--
ALTER TABLE `tb_pengambilan_bahan`
  ADD PRIMARY KEY (`id_pengambilan_bahan`),
  ADD UNIQUE KEY `uk_tb_pengambilan_bahan_entitas_no` (`id_entitas`,`no_pengambilan_bahan`),
  ADD KEY `idx_tb_pengambilan_bahan_entitas` (`id_entitas`),
  ADD KEY `idx_tb_pengambilan_bahan_perintah` (`id_perintah_produksi`),
  ADD KEY `idx_tb_pengambilan_bahan_gudang` (`id_gudang`);

--
-- Indexes for table `tb_pengambilan_bahan_detail`
--
ALTER TABLE `tb_pengambilan_bahan_detail`
  ADD PRIMARY KEY (`id_pengambilan_bahan_detail`),
  ADD KEY `idx_tb_pengambilan_bahan_detail_header` (`id_pengambilan_bahan`),
  ADD KEY `idx_tb_pengambilan_bahan_detail_bahan` (`id_bahan_baku`);

--
-- Indexes for table `tb_pengguna`
--
ALTER TABLE `tb_pengguna`
  ADD PRIMARY KEY (`id_pengguna`),
  ADD UNIQUE KEY `uk_tb_pengguna_username` (`username`),
  ADD KEY `idx_tb_pengguna_entitas` (`id_entitas`),
  ADD KEY `idx_tb_pengguna_role` (`id_role`);

--
-- Indexes for table `tb_pengguna_entitas`
--
ALTER TABLE `tb_pengguna_entitas`
  ADD PRIMARY KEY (`id_pengguna_entitas`),
  ADD UNIQUE KEY `uk_tb_pengguna_entitas` (`id_pengguna`,`id_entitas`),
  ADD KEY `idx_tb_pengguna_entitas_pengguna` (`id_pengguna`),
  ADD KEY `idx_tb_pengguna_entitas_entitas` (`id_entitas`),
  ADD KEY `idx_tb_pengguna_entitas_role` (`id_role`);

--
-- Indexes for table `tb_penyerahan_penjualan`
--
ALTER TABLE `tb_penyerahan_penjualan`
  ADD PRIMARY KEY (`id_penyerahan_penjualan`),
  ADD UNIQUE KEY `uk_tb_penyerahan_penjualan_entitas_no` (`id_entitas`,`no_penyerahan_penjualan`),
  ADD KEY `idx_tb_penyerahan_penjualan_entitas` (`id_entitas`),
  ADD KEY `idx_tb_penyerahan_penjualan_pesanan` (`id_pesanan_penjualan`),
  ADD KEY `idx_tb_penyerahan_penjualan_pelanggan` (`id_pelanggan`),
  ADD KEY `idx_tb_penyerahan_penjualan_gudang` (`id_gudang`);

--
-- Indexes for table `tb_penyerahan_penjualan_detail`
--
ALTER TABLE `tb_penyerahan_penjualan_detail`
  ADD PRIMARY KEY (`id_penyerahan_penjualan_detail`),
  ADD KEY `idx_tb_penyerahan_penjualan_detail_header` (`id_penyerahan_penjualan`),
  ADD KEY `idx_tb_penyerahan_penjualan_detail_produk` (`id_produk`);

--
-- Indexes for table `tb_perintah_produksi`
--
ALTER TABLE `tb_perintah_produksi`
  ADD PRIMARY KEY (`id_perintah_produksi`),
  ADD UNIQUE KEY `uk_tb_perintah_produksi_entitas_no` (`id_entitas`,`no_perintah_produksi`),
  ADD KEY `idx_tb_perintah_produksi_entitas` (`id_entitas`),
  ADD KEY `idx_tb_perintah_produksi_produk` (`id_produk`),
  ADD KEY `idx_tb_perintah_produksi_resep` (`id_resep`),
  ADD KEY `idx_tb_perintah_produksi_pesanan` (`id_pesanan_penjualan`);

--
-- Indexes for table `tb_periode_akuntansi`
--
ALTER TABLE `tb_periode_akuntansi`
  ADD PRIMARY KEY (`id_periode`),
  ADD UNIQUE KEY `uk_tb_periode_entitas_bulan` (`id_entitas`,`tahun`,`bulan`),
  ADD KEY `idx_tb_periode_entitas` (`id_entitas`);

--
-- Indexes for table `tb_pesanan_online_chat`
--
ALTER TABLE `tb_pesanan_online_chat`
  ADD PRIMARY KEY (`id_chat`),
  ADD KEY `idx_chat_pesanan` (`id_pesanan_penjualan`),
  ADD KEY `idx_chat_entitas` (`id_entitas`);

--
-- Indexes for table `tb_pesanan_online_chat_general`
--
ALTER TABLE `tb_pesanan_online_chat_general`
  ADD PRIMARY KEY (`id_chat_general`),
  ADD KEY `idx_chat_general_entitas_session` (`id_entitas`,`session_key`),
  ADD KEY `idx_chat_general_tanggal` (`tanggal_dibuat`);

--
-- Indexes for table `tb_pesanan_pembelian`
--
ALTER TABLE `tb_pesanan_pembelian`
  ADD PRIMARY KEY (`id_pesanan_pembelian`),
  ADD UNIQUE KEY `uk_tb_pesanan_pembelian_entitas_no` (`id_entitas`,`no_pesanan_pembelian`),
  ADD KEY `idx_tb_pesanan_pembelian_entitas` (`id_entitas`),
  ADD KEY `idx_tb_pesanan_pembelian_pemasok` (`id_pemasok`);

--
-- Indexes for table `tb_pesanan_pembelian_detail`
--
ALTER TABLE `tb_pesanan_pembelian_detail`
  ADD PRIMARY KEY (`id_pesanan_pembelian_detail`),
  ADD KEY `idx_tb_pesanan_pembelian_detail_header` (`id_pesanan_pembelian`),
  ADD KEY `idx_tb_pesanan_pembelian_detail_bahan` (`id_bahan_baku`);

--
-- Indexes for table `tb_pesanan_penjualan`
--
ALTER TABLE `tb_pesanan_penjualan`
  ADD PRIMARY KEY (`id_pesanan_penjualan`),
  ADD UNIQUE KEY `uk_tb_pesanan_penjualan_entitas_no` (`id_entitas`,`no_pesanan_penjualan`),
  ADD KEY `idx_tb_pesanan_penjualan_entitas` (`id_entitas`),
  ADD KEY `idx_tb_pesanan_penjualan_pelanggan` (`id_pelanggan`);

--
-- Indexes for table `tb_pesanan_penjualan_detail`
--
ALTER TABLE `tb_pesanan_penjualan_detail`
  ADD PRIMARY KEY (`id_pesanan_penjualan_detail`),
  ADD KEY `idx_tb_pesanan_penjualan_detail_header` (`id_pesanan_penjualan`),
  ADD KEY `idx_tb_pesanan_penjualan_detail_produk` (`id_produk`);

--
-- Indexes for table `tb_produk`
--
ALTER TABLE `tb_produk`
  ADD PRIMARY KEY (`id_produk`),
  ADD UNIQUE KEY `uk_tb_produk_entitas_kode` (`id_entitas`,`kode_produk`),
  ADD KEY `idx_tb_produk_entitas` (`id_entitas`),
  ADD KEY `idx_tb_produk_kategori` (`id_kategori_produk`),
  ADD KEY `idx_tb_produk_satuan` (`id_satuan`),
  ADD KEY `idx_tb_produk_coa_penjualan` (`id_coa_penjualan`),
  ADD KEY `idx_tb_produk_coa_hpp` (`id_coa_hpp`),
  ADD KEY `idx_tb_produk_coa_persediaan` (`id_coa_persediaan`),
  ADD KEY `idx_tb_produk_barcode` (`id_entitas`,`barcode_produk`);

--
-- Indexes for table `tb_resep`
--
ALTER TABLE `tb_resep`
  ADD PRIMARY KEY (`id_resep`),
  ADD UNIQUE KEY `uk_tb_resep_entitas_kode` (`id_entitas`,`kode_resep`),
  ADD KEY `idx_tb_resep_entitas` (`id_entitas`),
  ADD KEY `idx_tb_resep_produk` (`id_produk`);

--
-- Indexes for table `tb_resep_detail`
--
ALTER TABLE `tb_resep_detail`
  ADD PRIMARY KEY (`id_resep_detail`),
  ADD KEY `idx_tb_resep_detail_resep` (`id_resep`),
  ADD KEY `idx_tb_resep_detail_bahan` (`id_bahan_baku`),
  ADD KEY `idx_tb_resep_detail_satuan` (`id_satuan`);

--
-- Indexes for table `tb_reset_data_log`
--
ALTER TABLE `tb_reset_data_log`
  ADD PRIMARY KEY (`id_reset_data_log`),
  ADD KEY `idx_reset_data_log_pengguna` (`id_pengguna`),
  ADD KEY `idx_reset_data_log_tanggal` (`tanggal_reset`);

--
-- Indexes for table `tb_role`
--
ALTER TABLE `tb_role`
  ADD PRIMARY KEY (`id_role`),
  ADD UNIQUE KEY `uk_tb_role_nama` (`nama_role`);

--
-- Indexes for table `tb_role_menu`
--
ALTER TABLE `tb_role_menu`
  ADD PRIMARY KEY (`id_role_menu`),
  ADD UNIQUE KEY `uk_tb_role_menu` (`id_role`,`id_menu`),
  ADD KEY `idx_tb_role_menu_role` (`id_role`),
  ADD KEY `idx_tb_role_menu_menu` (`id_menu`);

--
-- Indexes for table `tb_saldo_awal_stok`
--
ALTER TABLE `tb_saldo_awal_stok`
  ADD PRIMARY KEY (`id_saldo_awal_stok`),
  ADD UNIQUE KEY `uk_saldo_awal_no_entitas` (`id_entitas`,`no_saldo_awal_stok`),
  ADD KEY `idx_saldo_awal_entitas` (`id_entitas`),
  ADD KEY `idx_saldo_awal_gudang` (`id_gudang`),
  ADD KEY `idx_saldo_awal_status` (`status_posting`),
  ADD KEY `idx_saldo_awal_coa_lawan` (`id_coa_lawan`);

--
-- Indexes for table `tb_saldo_awal_stok_detail`
--
ALTER TABLE `tb_saldo_awal_stok_detail`
  ADD PRIMARY KEY (`id_saldo_awal_stok_detail`),
  ADD KEY `idx_saldo_awal_detail_header` (`id_saldo_awal_stok`),
  ADD KEY `idx_saldo_awal_detail_barang` (`jenis_barang`,`id_referensi_barang`);

--
-- Indexes for table `tb_saldo_stok`
--
ALTER TABLE `tb_saldo_stok`
  ADD PRIMARY KEY (`id_saldo_stok`),
  ADD UNIQUE KEY `uk_tb_saldo_stok_unik` (`id_entitas`,`jenis_barang`,`id_referensi_barang`,`id_gudang`),
  ADD KEY `idx_tb_saldo_stok_entitas` (`id_entitas`),
  ADD KEY `idx_tb_saldo_stok_gudang` (`id_gudang`),
  ADD KEY `idx_saldo_stok_dibuat_oleh` (`dibuat_oleh`),
  ADD KEY `idx_saldo_stok_diubah_oleh` (`diubah_oleh`);

--
-- Indexes for table `tb_satuan`
--
ALTER TABLE `tb_satuan`
  ADD PRIMARY KEY (`id_satuan`),
  ADD UNIQUE KEY `uk_tb_satuan_nama` (`nama_satuan`),
  ADD KEY `fk_tb_satuan_dibuat_oleh` (`dibuat_oleh`),
  ADD KEY `fk_tb_satuan_diubah_oleh` (`diubah_oleh`);

--
-- Indexes for table `tb_stok_opname`
--
ALTER TABLE `tb_stok_opname`
  ADD PRIMARY KEY (`id_stok_opname`),
  ADD UNIQUE KEY `uk_tb_stok_opname_entitas_no` (`id_entitas`,`no_stok_opname`),
  ADD KEY `idx_tb_stok_opname_entitas` (`id_entitas`),
  ADD KEY `idx_tb_stok_opname_gudang` (`id_gudang`);

--
-- Indexes for table `tb_stok_opname_detail`
--
ALTER TABLE `tb_stok_opname_detail`
  ADD PRIMARY KEY (`id_stok_opname_detail`),
  ADD KEY `idx_tb_stok_opname_detail_header` (`id_stok_opname`);

--
-- Indexes for table `tb_template_jurnal`
--
ALTER TABLE `tb_template_jurnal`
  ADD PRIMARY KEY (`id_template_jurnal`),
  ADD UNIQUE KEY `uk_tb_template_jurnal_entitas_kode` (`id_entitas`,`kode_template_jurnal`),
  ADD KEY `idx_tb_template_jurnal_entitas` (`id_entitas`),
  ADD KEY `idx_tb_template_jurnal_jenis` (`kode_jenis_transaksi`);

--
-- Indexes for table `tb_template_jurnal_detail`
--
ALTER TABLE `tb_template_jurnal_detail`
  ADD PRIMARY KEY (`id_template_jurnal_detail`),
  ADD KEY `idx_tb_template_jurnal_detail_template` (`id_template_jurnal`),
  ADD KEY `idx_tb_template_jurnal_detail_coa` (`id_coa_default`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tb_bahan_baku`
--
ALTER TABLE `tb_bahan_baku`
  MODIFY `id_bahan_baku` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tb_biaya_produksi`
--
ALTER TABLE `tb_biaya_produksi`
  MODIFY `id_biaya_produksi` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tb_biaya_produksi_detail`
--
ALTER TABLE `tb_biaya_produksi_detail`
  MODIFY `id_biaya_produksi_detail` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tb_coa`
--
ALTER TABLE `tb_coa`
  MODIFY `id_coa` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `tb_entitas`
--
ALTER TABLE `tb_entitas`
  MODIFY `id_entitas` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tb_faktur_pembelian`
--
ALTER TABLE `tb_faktur_pembelian`
  MODIFY `id_faktur_pembelian` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tb_faktur_pembelian_detail`
--
ALTER TABLE `tb_faktur_pembelian_detail`
  MODIFY `id_faktur_pembelian_detail` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tb_faktur_penjualan`
--
ALTER TABLE `tb_faktur_penjualan`
  MODIFY `id_faktur_penjualan` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tb_faktur_penjualan_detail`
--
ALTER TABLE `tb_faktur_penjualan_detail`
  MODIFY `id_faktur_penjualan_detail` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tb_gudang`
--
ALTER TABLE `tb_gudang`
  MODIFY `id_gudang` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tb_hasil_produksi`
--
ALTER TABLE `tb_hasil_produksi`
  MODIFY `id_hasil_produksi` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tb_jurnal`
--
ALTER TABLE `tb_jurnal`
  MODIFY `id_jurnal` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `tb_jurnal_detail`
--
ALTER TABLE `tb_jurnal_detail`
  MODIFY `id_jurnal_detail` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `tb_kas_keluar`
--
ALTER TABLE `tb_kas_keluar`
  MODIFY `id_kas_keluar` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tb_kas_masuk`
--
ALTER TABLE `tb_kas_masuk`
  MODIFY `id_kas_masuk` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tb_kategori_produk`
--
ALTER TABLE `tb_kategori_produk`
  MODIFY `id_kategori_produk` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tb_konfigurasi_akun`
--
ALTER TABLE `tb_konfigurasi_akun`
  MODIFY `id_konfigurasi_akun` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `tb_konversi_produk`
--
ALTER TABLE `tb_konversi_produk`
  MODIFY `id_konversi_produk` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tb_log_jurnal_sumber`
--
ALTER TABLE `tb_log_jurnal_sumber`
  MODIFY `id_log_jurnal_sumber` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `tb_log_login`
--
ALTER TABLE `tb_log_login`
  MODIFY `id_log_login` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=90;

--
-- AUTO_INCREMENT for table `tb_mapping_akun`
--
ALTER TABLE `tb_mapping_akun`
  MODIFY `id_mapping_akun` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=128;

--
-- AUTO_INCREMENT for table `tb_mapping_laporan`
--
ALTER TABLE `tb_mapping_laporan`
  MODIFY `id_mapping_laporan` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `tb_mapping_laporan_detail`
--
ALTER TABLE `tb_mapping_laporan_detail`
  MODIFY `id_mapping_laporan_detail` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT for table `tb_menu`
--
ALTER TABLE `tb_menu`
  MODIFY `id_menu` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=93;

--
-- AUTO_INCREMENT for table `tb_mutasi_stok`
--
ALTER TABLE `tb_mutasi_stok`
  MODIFY `id_mutasi_stok` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `tb_pajak`
--
ALTER TABLE `tb_pajak`
  MODIFY `id_pajak` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tb_payment_gateway_setting`
--
ALTER TABLE `tb_payment_gateway_setting`
  MODIFY `id_payment_gateway_setting` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tb_pelanggan`
--
ALTER TABLE `tb_pelanggan`
  MODIFY `id_pelanggan` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tb_pemasok`
--
ALTER TABLE `tb_pemasok`
  MODIFY `id_pemasok` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tb_pembatalan_transaksi`
--
ALTER TABLE `tb_pembatalan_transaksi`
  MODIFY `id_pembatalan_transaksi` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tb_pembayaran_online_bukti`
--
ALTER TABLE `tb_pembayaran_online_bukti`
  MODIFY `id_bukti_pembayaran_online` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tb_pembayaran_pembelian`
--
ALTER TABLE `tb_pembayaran_pembelian`
  MODIFY `id_pembayaran_pembelian` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tb_pembayaran_penjualan`
--
ALTER TABLE `tb_pembayaran_penjualan`
  MODIFY `id_pembayaran_penjualan` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tb_penerimaan_pembelian`
--
ALTER TABLE `tb_penerimaan_pembelian`
  MODIFY `id_penerimaan_pembelian` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tb_penerimaan_pembelian_detail`
--
ALTER TABLE `tb_penerimaan_pembelian_detail`
  MODIFY `id_penerimaan_pembelian_detail` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tb_pengambilan_bahan`
--
ALTER TABLE `tb_pengambilan_bahan`
  MODIFY `id_pengambilan_bahan` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tb_pengambilan_bahan_detail`
--
ALTER TABLE `tb_pengambilan_bahan_detail`
  MODIFY `id_pengambilan_bahan_detail` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tb_pengguna`
--
ALTER TABLE `tb_pengguna`
  MODIFY `id_pengguna` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tb_pengguna_entitas`
--
ALTER TABLE `tb_pengguna_entitas`
  MODIFY `id_pengguna_entitas` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tb_penyerahan_penjualan`
--
ALTER TABLE `tb_penyerahan_penjualan`
  MODIFY `id_penyerahan_penjualan` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tb_penyerahan_penjualan_detail`
--
ALTER TABLE `tb_penyerahan_penjualan_detail`
  MODIFY `id_penyerahan_penjualan_detail` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tb_perintah_produksi`
--
ALTER TABLE `tb_perintah_produksi`
  MODIFY `id_perintah_produksi` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tb_periode_akuntansi`
--
ALTER TABLE `tb_periode_akuntansi`
  MODIFY `id_periode` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tb_pesanan_online_chat`
--
ALTER TABLE `tb_pesanan_online_chat`
  MODIFY `id_chat` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `tb_pesanan_online_chat_general`
--
ALTER TABLE `tb_pesanan_online_chat_general`
  MODIFY `id_chat_general` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tb_pesanan_pembelian`
--
ALTER TABLE `tb_pesanan_pembelian`
  MODIFY `id_pesanan_pembelian` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tb_pesanan_pembelian_detail`
--
ALTER TABLE `tb_pesanan_pembelian_detail`
  MODIFY `id_pesanan_pembelian_detail` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tb_pesanan_penjualan`
--
ALTER TABLE `tb_pesanan_penjualan`
  MODIFY `id_pesanan_penjualan` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tb_pesanan_penjualan_detail`
--
ALTER TABLE `tb_pesanan_penjualan_detail`
  MODIFY `id_pesanan_penjualan_detail` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tb_produk`
--
ALTER TABLE `tb_produk`
  MODIFY `id_produk` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tb_resep`
--
ALTER TABLE `tb_resep`
  MODIFY `id_resep` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tb_resep_detail`
--
ALTER TABLE `tb_resep_detail`
  MODIFY `id_resep_detail` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tb_reset_data_log`
--
ALTER TABLE `tb_reset_data_log`
  MODIFY `id_reset_data_log` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tb_role`
--
ALTER TABLE `tb_role`
  MODIFY `id_role` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `tb_role_menu`
--
ALTER TABLE `tb_role_menu`
  MODIFY `id_role_menu` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=344;

--
-- AUTO_INCREMENT for table `tb_saldo_awal_stok`
--
ALTER TABLE `tb_saldo_awal_stok`
  MODIFY `id_saldo_awal_stok` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tb_saldo_awal_stok_detail`
--
ALTER TABLE `tb_saldo_awal_stok_detail`
  MODIFY `id_saldo_awal_stok_detail` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tb_saldo_stok`
--
ALTER TABLE `tb_saldo_stok`
  MODIFY `id_saldo_stok` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tb_satuan`
--
ALTER TABLE `tb_satuan`
  MODIFY `id_satuan` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tb_stok_opname`
--
ALTER TABLE `tb_stok_opname`
  MODIFY `id_stok_opname` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tb_stok_opname_detail`
--
ALTER TABLE `tb_stok_opname_detail`
  MODIFY `id_stok_opname_detail` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tb_template_jurnal`
--
ALTER TABLE `tb_template_jurnal`
  MODIFY `id_template_jurnal` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `tb_template_jurnal_detail`
--
ALTER TABLE `tb_template_jurnal_detail`
  MODIFY `id_template_jurnal_detail` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=162;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tb_bahan_baku`
--
ALTER TABLE `tb_bahan_baku`
  ADD CONSTRAINT `fk_tb_bahan_baku_coa_persediaan` FOREIGN KEY (`id_coa_persediaan`) REFERENCES `tb_coa` (`id_coa`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_bahan_baku_entitas` FOREIGN KEY (`id_entitas`) REFERENCES `tb_entitas` (`id_entitas`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_bahan_baku_satuan` FOREIGN KEY (`id_satuan`) REFERENCES `tb_satuan` (`id_satuan`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `tb_biaya_produksi`
--
ALTER TABLE `tb_biaya_produksi`
  ADD CONSTRAINT `fk_tb_biaya_produksi_coa` FOREIGN KEY (`id_coa_lawan`) REFERENCES `tb_coa` (`id_coa`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_biaya_produksi_entitas` FOREIGN KEY (`id_entitas`) REFERENCES `tb_entitas` (`id_entitas`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_biaya_produksi_perintah` FOREIGN KEY (`id_perintah_produksi`) REFERENCES `tb_perintah_produksi` (`id_perintah_produksi`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `tb_biaya_produksi_detail`
--
ALTER TABLE `tb_biaya_produksi_detail`
  ADD CONSTRAINT `fk_biaya_produksi_detail_coa_lawan` FOREIGN KEY (`id_coa_lawan`) REFERENCES `tb_coa` (`id_coa`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_biaya_produksi_detail_header` FOREIGN KEY (`id_biaya_produksi`) REFERENCES `tb_biaya_produksi` (`id_biaya_produksi`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tb_coa`
--
ALTER TABLE `tb_coa`
  ADD CONSTRAINT `fk_tb_coa_entitas` FOREIGN KEY (`id_entitas`) REFERENCES `tb_entitas` (`id_entitas`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_coa_induk` FOREIGN KEY (`id_coa_induk`) REFERENCES `tb_coa` (`id_coa`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `tb_faktur_pembelian`
--
ALTER TABLE `tb_faktur_pembelian`
  ADD CONSTRAINT `fk_tb_faktur_pembelian_entitas` FOREIGN KEY (`id_entitas`) REFERENCES `tb_entitas` (`id_entitas`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_faktur_pembelian_pemasok` FOREIGN KEY (`id_pemasok`) REFERENCES `tb_pemasok` (`id_pemasok`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_faktur_pembelian_penerimaan` FOREIGN KEY (`id_penerimaan_pembelian`) REFERENCES `tb_penerimaan_pembelian` (`id_penerimaan_pembelian`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `tb_faktur_pembelian_detail`
--
ALTER TABLE `tb_faktur_pembelian_detail`
  ADD CONSTRAINT `fk_tb_faktur_pembelian_detail_bahan` FOREIGN KEY (`id_bahan_baku`) REFERENCES `tb_bahan_baku` (`id_bahan_baku`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_faktur_pembelian_detail_header` FOREIGN KEY (`id_faktur_pembelian`) REFERENCES `tb_faktur_pembelian` (`id_faktur_pembelian`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tb_faktur_penjualan`
--
ALTER TABLE `tb_faktur_penjualan`
  ADD CONSTRAINT `fk_tb_faktur_penjualan_entitas` FOREIGN KEY (`id_entitas`) REFERENCES `tb_entitas` (`id_entitas`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_faktur_penjualan_pelanggan` FOREIGN KEY (`id_pelanggan`) REFERENCES `tb_pelanggan` (`id_pelanggan`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_faktur_penjualan_penyerahan` FOREIGN KEY (`id_penyerahan_penjualan`) REFERENCES `tb_penyerahan_penjualan` (`id_penyerahan_penjualan`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `tb_faktur_penjualan_detail`
--
ALTER TABLE `tb_faktur_penjualan_detail`
  ADD CONSTRAINT `fk_tb_faktur_penjualan_detail_header` FOREIGN KEY (`id_faktur_penjualan`) REFERENCES `tb_faktur_penjualan` (`id_faktur_penjualan`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_faktur_penjualan_detail_produk` FOREIGN KEY (`id_produk`) REFERENCES `tb_produk` (`id_produk`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `tb_gudang`
--
ALTER TABLE `tb_gudang`
  ADD CONSTRAINT `fk_tb_gudang_diubah_oleh` FOREIGN KEY (`diubah_oleh`) REFERENCES `tb_pengguna` (`id_pengguna`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_gudang_entitas` FOREIGN KEY (`id_entitas`) REFERENCES `tb_entitas` (`id_entitas`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `tb_hasil_produksi`
--
ALTER TABLE `tb_hasil_produksi`
  ADD CONSTRAINT `fk_tb_hasil_produksi_entitas` FOREIGN KEY (`id_entitas`) REFERENCES `tb_entitas` (`id_entitas`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_hasil_produksi_gudang` FOREIGN KEY (`id_gudang`) REFERENCES `tb_gudang` (`id_gudang`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_hasil_produksi_perintah` FOREIGN KEY (`id_perintah_produksi`) REFERENCES `tb_perintah_produksi` (`id_perintah_produksi`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_hasil_produksi_produk` FOREIGN KEY (`id_produk`) REFERENCES `tb_produk` (`id_produk`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `tb_jurnal`
--
ALTER TABLE `tb_jurnal`
  ADD CONSTRAINT `fk_tb_jurnal_entitas` FOREIGN KEY (`id_entitas`) REFERENCES `tb_entitas` (`id_entitas`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_jurnal_periode` FOREIGN KEY (`id_periode`) REFERENCES `tb_periode_akuntansi` (`id_periode`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `tb_jurnal_detail`
--
ALTER TABLE `tb_jurnal_detail`
  ADD CONSTRAINT `fk_tb_jurnal_detail_bahan` FOREIGN KEY (`id_bahan_baku`) REFERENCES `tb_bahan_baku` (`id_bahan_baku`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_jurnal_detail_coa` FOREIGN KEY (`id_coa`) REFERENCES `tb_coa` (`id_coa`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_jurnal_detail_gudang` FOREIGN KEY (`id_gudang`) REFERENCES `tb_gudang` (`id_gudang`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_jurnal_detail_jurnal` FOREIGN KEY (`id_jurnal`) REFERENCES `tb_jurnal` (`id_jurnal`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_jurnal_detail_pelanggan` FOREIGN KEY (`id_pelanggan`) REFERENCES `tb_pelanggan` (`id_pelanggan`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_jurnal_detail_pemasok` FOREIGN KEY (`id_pemasok`) REFERENCES `tb_pemasok` (`id_pemasok`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_jurnal_detail_produk` FOREIGN KEY (`id_produk`) REFERENCES `tb_produk` (`id_produk`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `tb_kas_keluar`
--
ALTER TABLE `tb_kas_keluar`
  ADD CONSTRAINT `fk_tb_kas_keluar_coa_beban` FOREIGN KEY (`id_coa_beban`) REFERENCES `tb_coa` (`id_coa`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_kas_keluar_coa_kas` FOREIGN KEY (`id_coa_kas_bank`) REFERENCES `tb_coa` (`id_coa`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_kas_keluar_entitas` FOREIGN KEY (`id_entitas`) REFERENCES `tb_entitas` (`id_entitas`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `tb_kas_masuk`
--
ALTER TABLE `tb_kas_masuk`
  ADD CONSTRAINT `fk_tb_kas_masuk_coa` FOREIGN KEY (`id_coa_kas_bank`) REFERENCES `tb_coa` (`id_coa`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_kas_masuk_entitas` FOREIGN KEY (`id_entitas`) REFERENCES `tb_entitas` (`id_entitas`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `tb_kategori_produk`
--
ALTER TABLE `tb_kategori_produk`
  ADD CONSTRAINT `fk_tb_kategori_produk_dibuat_oleh` FOREIGN KEY (`dibuat_oleh`) REFERENCES `tb_pengguna` (`id_pengguna`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_kategori_produk_diubah_oleh` FOREIGN KEY (`diubah_oleh`) REFERENCES `tb_pengguna` (`id_pengguna`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_kategori_produk_entitas` FOREIGN KEY (`id_entitas`) REFERENCES `tb_entitas` (`id_entitas`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `tb_konfigurasi_akun`
--
ALTER TABLE `tb_konfigurasi_akun`
  ADD CONSTRAINT `fk_konfigurasi_akun_coa` FOREIGN KEY (`id_coa`) REFERENCES `tb_coa` (`id_coa`),
  ADD CONSTRAINT `fk_konfigurasi_akun_entitas` FOREIGN KEY (`id_entitas`) REFERENCES `tb_entitas` (`id_entitas`);

--
-- Constraints for table `tb_log_jurnal_sumber`
--
ALTER TABLE `tb_log_jurnal_sumber`
  ADD CONSTRAINT `fk_tb_log_jurnal_sumber_entitas` FOREIGN KEY (`id_entitas`) REFERENCES `tb_entitas` (`id_entitas`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_log_jurnal_sumber_jurnal` FOREIGN KEY (`id_jurnal`) REFERENCES `tb_jurnal` (`id_jurnal`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tb_log_login`
--
ALTER TABLE `tb_log_login`
  ADD CONSTRAINT `fk_tb_log_login_entitas` FOREIGN KEY (`id_entitas`) REFERENCES `tb_entitas` (`id_entitas`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_log_login_pengguna` FOREIGN KEY (`id_pengguna`) REFERENCES `tb_pengguna` (`id_pengguna`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `tb_mapping_akun`
--
ALTER TABLE `tb_mapping_akun`
  ADD CONSTRAINT `fk_tb_mapping_akun_coa` FOREIGN KEY (`id_coa`) REFERENCES `tb_coa` (`id_coa`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_mapping_akun_entitas` FOREIGN KEY (`id_entitas`) REFERENCES `tb_entitas` (`id_entitas`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `tb_mapping_laporan`
--
ALTER TABLE `tb_mapping_laporan`
  ADD CONSTRAINT `fk_mapping_laporan_entitas` FOREIGN KEY (`id_entitas`) REFERENCES `tb_entitas` (`id_entitas`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mapping_laporan_parent` FOREIGN KEY (`parent_id`) REFERENCES `tb_mapping_laporan` (`id_mapping_laporan`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `tb_mapping_laporan_detail`
--
ALTER TABLE `tb_mapping_laporan_detail`
  ADD CONSTRAINT `fk_mld_coa` FOREIGN KEY (`id_coa`) REFERENCES `tb_coa` (`id_coa`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mld_entitas` FOREIGN KEY (`id_entitas`) REFERENCES `tb_entitas` (`id_entitas`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mld_mapping_laporan` FOREIGN KEY (`id_mapping_laporan`) REFERENCES `tb_mapping_laporan` (`id_mapping_laporan`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tb_menu`
--
ALTER TABLE `tb_menu`
  ADD CONSTRAINT `fk_tb_menu_induk` FOREIGN KEY (`id_menu_induk`) REFERENCES `tb_menu` (`id_menu`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `tb_mutasi_stok`
--
ALTER TABLE `tb_mutasi_stok`
  ADD CONSTRAINT `fk_tb_mutasi_stok_entitas` FOREIGN KEY (`id_entitas`) REFERENCES `tb_entitas` (`id_entitas`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_mutasi_stok_gudang` FOREIGN KEY (`id_gudang`) REFERENCES `tb_gudang` (`id_gudang`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `tb_pelanggan`
--
ALTER TABLE `tb_pelanggan`
  ADD CONSTRAINT `fk_tb_pelanggan_diubah_oleh` FOREIGN KEY (`diubah_oleh`) REFERENCES `tb_pengguna` (`id_pengguna`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_pelanggan_entitas` FOREIGN KEY (`id_entitas`) REFERENCES `tb_entitas` (`id_entitas`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `tb_pemasok`
--
ALTER TABLE `tb_pemasok`
  ADD CONSTRAINT `fk_tb_pemasok_diubah_oleh` FOREIGN KEY (`diubah_oleh`) REFERENCES `tb_pengguna` (`id_pengguna`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_pemasok_entitas` FOREIGN KEY (`id_entitas`) REFERENCES `tb_entitas` (`id_entitas`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `tb_pembatalan_transaksi`
--
ALTER TABLE `tb_pembatalan_transaksi`
  ADD CONSTRAINT `fk_pembatalan_entitas` FOREIGN KEY (`id_entitas`) REFERENCES `tb_entitas` (`id_entitas`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pembatalan_jurnal_asal` FOREIGN KEY (`id_jurnal_asal`) REFERENCES `tb_jurnal` (`id_jurnal`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pembatalan_jurnal_reversal` FOREIGN KEY (`id_jurnal_reversal`) REFERENCES `tb_jurnal` (`id_jurnal`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `tb_pembayaran_pembelian`
--
ALTER TABLE `tb_pembayaran_pembelian`
  ADD CONSTRAINT `fk_tb_pembayaran_pembelian_coa` FOREIGN KEY (`id_coa_kas_bank`) REFERENCES `tb_coa` (`id_coa`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_pembayaran_pembelian_entitas` FOREIGN KEY (`id_entitas`) REFERENCES `tb_entitas` (`id_entitas`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_pembayaran_pembelian_faktur` FOREIGN KEY (`id_faktur_pembelian`) REFERENCES `tb_faktur_pembelian` (`id_faktur_pembelian`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_pembayaran_pembelian_pemasok` FOREIGN KEY (`id_pemasok`) REFERENCES `tb_pemasok` (`id_pemasok`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `tb_pembayaran_penjualan`
--
ALTER TABLE `tb_pembayaran_penjualan`
  ADD CONSTRAINT `fk_tb_pembayaran_penjualan_coa` FOREIGN KEY (`id_coa_kas_bank`) REFERENCES `tb_coa` (`id_coa`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_pembayaran_penjualan_entitas` FOREIGN KEY (`id_entitas`) REFERENCES `tb_entitas` (`id_entitas`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_pembayaran_penjualan_faktur` FOREIGN KEY (`id_faktur_penjualan`) REFERENCES `tb_faktur_penjualan` (`id_faktur_penjualan`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_pembayaran_penjualan_pelanggan` FOREIGN KEY (`id_pelanggan`) REFERENCES `tb_pelanggan` (`id_pelanggan`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `tb_penerimaan_pembelian`
--
ALTER TABLE `tb_penerimaan_pembelian`
  ADD CONSTRAINT `fk_tb_penerimaan_pembelian_entitas` FOREIGN KEY (`id_entitas`) REFERENCES `tb_entitas` (`id_entitas`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_penerimaan_pembelian_gudang` FOREIGN KEY (`id_gudang`) REFERENCES `tb_gudang` (`id_gudang`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_penerimaan_pembelian_pemasok` FOREIGN KEY (`id_pemasok`) REFERENCES `tb_pemasok` (`id_pemasok`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_penerimaan_pembelian_pesanan` FOREIGN KEY (`id_pesanan_pembelian`) REFERENCES `tb_pesanan_pembelian` (`id_pesanan_pembelian`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `tb_penerimaan_pembelian_detail`
--
ALTER TABLE `tb_penerimaan_pembelian_detail`
  ADD CONSTRAINT `fk_tb_penerimaan_pembelian_detail_bahan` FOREIGN KEY (`id_bahan_baku`) REFERENCES `tb_bahan_baku` (`id_bahan_baku`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_penerimaan_pembelian_detail_header` FOREIGN KEY (`id_penerimaan_pembelian`) REFERENCES `tb_penerimaan_pembelian` (`id_penerimaan_pembelian`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tb_pengambilan_bahan`
--
ALTER TABLE `tb_pengambilan_bahan`
  ADD CONSTRAINT `fk_tb_pengambilan_bahan_entitas` FOREIGN KEY (`id_entitas`) REFERENCES `tb_entitas` (`id_entitas`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_pengambilan_bahan_gudang` FOREIGN KEY (`id_gudang`) REFERENCES `tb_gudang` (`id_gudang`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_pengambilan_bahan_perintah` FOREIGN KEY (`id_perintah_produksi`) REFERENCES `tb_perintah_produksi` (`id_perintah_produksi`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `tb_pengambilan_bahan_detail`
--
ALTER TABLE `tb_pengambilan_bahan_detail`
  ADD CONSTRAINT `fk_tb_pengambilan_bahan_detail_bahan` FOREIGN KEY (`id_bahan_baku`) REFERENCES `tb_bahan_baku` (`id_bahan_baku`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_pengambilan_bahan_detail_header` FOREIGN KEY (`id_pengambilan_bahan`) REFERENCES `tb_pengambilan_bahan` (`id_pengambilan_bahan`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tb_pengguna`
--
ALTER TABLE `tb_pengguna`
  ADD CONSTRAINT `fk_tb_pengguna_entitas` FOREIGN KEY (`id_entitas`) REFERENCES `tb_entitas` (`id_entitas`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_pengguna_role` FOREIGN KEY (`id_role`) REFERENCES `tb_role` (`id_role`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `tb_pengguna_entitas`
--
ALTER TABLE `tb_pengguna_entitas`
  ADD CONSTRAINT `fk_tb_pengguna_entitas_entitas` FOREIGN KEY (`id_entitas`) REFERENCES `tb_entitas` (`id_entitas`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_pengguna_entitas_pengguna` FOREIGN KEY (`id_pengguna`) REFERENCES `tb_pengguna` (`id_pengguna`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_pengguna_entitas_role` FOREIGN KEY (`id_role`) REFERENCES `tb_role` (`id_role`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `tb_penyerahan_penjualan`
--
ALTER TABLE `tb_penyerahan_penjualan`
  ADD CONSTRAINT `fk_tb_penyerahan_penjualan_entitas` FOREIGN KEY (`id_entitas`) REFERENCES `tb_entitas` (`id_entitas`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_penyerahan_penjualan_gudang` FOREIGN KEY (`id_gudang`) REFERENCES `tb_gudang` (`id_gudang`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_penyerahan_penjualan_pelanggan` FOREIGN KEY (`id_pelanggan`) REFERENCES `tb_pelanggan` (`id_pelanggan`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_penyerahan_penjualan_pesanan` FOREIGN KEY (`id_pesanan_penjualan`) REFERENCES `tb_pesanan_penjualan` (`id_pesanan_penjualan`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `tb_penyerahan_penjualan_detail`
--
ALTER TABLE `tb_penyerahan_penjualan_detail`
  ADD CONSTRAINT `fk_tb_penyerahan_penjualan_detail_header` FOREIGN KEY (`id_penyerahan_penjualan`) REFERENCES `tb_penyerahan_penjualan` (`id_penyerahan_penjualan`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_penyerahan_penjualan_detail_produk` FOREIGN KEY (`id_produk`) REFERENCES `tb_produk` (`id_produk`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `tb_perintah_produksi`
--
ALTER TABLE `tb_perintah_produksi`
  ADD CONSTRAINT `fk_tb_perintah_produksi_entitas` FOREIGN KEY (`id_entitas`) REFERENCES `tb_entitas` (`id_entitas`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_perintah_produksi_pesanan` FOREIGN KEY (`id_pesanan_penjualan`) REFERENCES `tb_pesanan_penjualan` (`id_pesanan_penjualan`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_perintah_produksi_produk` FOREIGN KEY (`id_produk`) REFERENCES `tb_produk` (`id_produk`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_perintah_produksi_resep` FOREIGN KEY (`id_resep`) REFERENCES `tb_resep` (`id_resep`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `tb_periode_akuntansi`
--
ALTER TABLE `tb_periode_akuntansi`
  ADD CONSTRAINT `fk_tb_periode_entitas` FOREIGN KEY (`id_entitas`) REFERENCES `tb_entitas` (`id_entitas`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `tb_pesanan_pembelian`
--
ALTER TABLE `tb_pesanan_pembelian`
  ADD CONSTRAINT `fk_tb_pesanan_pembelian_entitas` FOREIGN KEY (`id_entitas`) REFERENCES `tb_entitas` (`id_entitas`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_pesanan_pembelian_pemasok` FOREIGN KEY (`id_pemasok`) REFERENCES `tb_pemasok` (`id_pemasok`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `tb_pesanan_pembelian_detail`
--
ALTER TABLE `tb_pesanan_pembelian_detail`
  ADD CONSTRAINT `fk_tb_pesanan_pembelian_detail_bahan` FOREIGN KEY (`id_bahan_baku`) REFERENCES `tb_bahan_baku` (`id_bahan_baku`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_pesanan_pembelian_detail_header` FOREIGN KEY (`id_pesanan_pembelian`) REFERENCES `tb_pesanan_pembelian` (`id_pesanan_pembelian`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tb_pesanan_penjualan`
--
ALTER TABLE `tb_pesanan_penjualan`
  ADD CONSTRAINT `fk_tb_pesanan_penjualan_entitas` FOREIGN KEY (`id_entitas`) REFERENCES `tb_entitas` (`id_entitas`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_pesanan_penjualan_pelanggan` FOREIGN KEY (`id_pelanggan`) REFERENCES `tb_pelanggan` (`id_pelanggan`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `tb_pesanan_penjualan_detail`
--
ALTER TABLE `tb_pesanan_penjualan_detail`
  ADD CONSTRAINT `fk_tb_pesanan_penjualan_detail_header` FOREIGN KEY (`id_pesanan_penjualan`) REFERENCES `tb_pesanan_penjualan` (`id_pesanan_penjualan`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_pesanan_penjualan_detail_produk` FOREIGN KEY (`id_produk`) REFERENCES `tb_produk` (`id_produk`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `tb_produk`
--
ALTER TABLE `tb_produk`
  ADD CONSTRAINT `fk_tb_produk_coa_hpp` FOREIGN KEY (`id_coa_hpp`) REFERENCES `tb_coa` (`id_coa`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_produk_coa_penjualan` FOREIGN KEY (`id_coa_penjualan`) REFERENCES `tb_coa` (`id_coa`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_produk_coa_persediaan` FOREIGN KEY (`id_coa_persediaan`) REFERENCES `tb_coa` (`id_coa`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_produk_entitas` FOREIGN KEY (`id_entitas`) REFERENCES `tb_entitas` (`id_entitas`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_produk_kategori` FOREIGN KEY (`id_kategori_produk`) REFERENCES `tb_kategori_produk` (`id_kategori_produk`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_produk_satuan` FOREIGN KEY (`id_satuan`) REFERENCES `tb_satuan` (`id_satuan`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `tb_resep`
--
ALTER TABLE `tb_resep`
  ADD CONSTRAINT `fk_tb_resep_entitas` FOREIGN KEY (`id_entitas`) REFERENCES `tb_entitas` (`id_entitas`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_resep_produk` FOREIGN KEY (`id_produk`) REFERENCES `tb_produk` (`id_produk`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `tb_resep_detail`
--
ALTER TABLE `tb_resep_detail`
  ADD CONSTRAINT `fk_tb_resep_detail_bahan` FOREIGN KEY (`id_bahan_baku`) REFERENCES `tb_bahan_baku` (`id_bahan_baku`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_resep_detail_resep` FOREIGN KEY (`id_resep`) REFERENCES `tb_resep` (`id_resep`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_resep_detail_satuan` FOREIGN KEY (`id_satuan`) REFERENCES `tb_satuan` (`id_satuan`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `tb_role_menu`
--
ALTER TABLE `tb_role_menu`
  ADD CONSTRAINT `fk_tb_role_menu_menu` FOREIGN KEY (`id_menu`) REFERENCES `tb_menu` (`id_menu`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_role_menu_role` FOREIGN KEY (`id_role`) REFERENCES `tb_role` (`id_role`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tb_saldo_awal_stok`
--
ALTER TABLE `tb_saldo_awal_stok`
  ADD CONSTRAINT `fk_saldo_awal_coa_lawan` FOREIGN KEY (`id_coa_lawan`) REFERENCES `tb_coa` (`id_coa`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_saldo_awal_entitas` FOREIGN KEY (`id_entitas`) REFERENCES `tb_entitas` (`id_entitas`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_saldo_awal_gudang` FOREIGN KEY (`id_gudang`) REFERENCES `tb_gudang` (`id_gudang`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `tb_saldo_awal_stok_detail`
--
ALTER TABLE `tb_saldo_awal_stok_detail`
  ADD CONSTRAINT `fk_saldo_awal_detail_header` FOREIGN KEY (`id_saldo_awal_stok`) REFERENCES `tb_saldo_awal_stok` (`id_saldo_awal_stok`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tb_saldo_stok`
--
ALTER TABLE `tb_saldo_stok`
  ADD CONSTRAINT `fk_tb_saldo_stok_entitas` FOREIGN KEY (`id_entitas`) REFERENCES `tb_entitas` (`id_entitas`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_saldo_stok_gudang` FOREIGN KEY (`id_gudang`) REFERENCES `tb_gudang` (`id_gudang`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `tb_satuan`
--
ALTER TABLE `tb_satuan`
  ADD CONSTRAINT `fk_tb_satuan_dibuat_oleh` FOREIGN KEY (`dibuat_oleh`) REFERENCES `tb_pengguna` (`id_pengguna`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_satuan_diubah_oleh` FOREIGN KEY (`diubah_oleh`) REFERENCES `tb_pengguna` (`id_pengguna`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `tb_stok_opname`
--
ALTER TABLE `tb_stok_opname`
  ADD CONSTRAINT `fk_tb_stok_opname_entitas` FOREIGN KEY (`id_entitas`) REFERENCES `tb_entitas` (`id_entitas`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_stok_opname_gudang` FOREIGN KEY (`id_gudang`) REFERENCES `tb_gudang` (`id_gudang`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `tb_stok_opname_detail`
--
ALTER TABLE `tb_stok_opname_detail`
  ADD CONSTRAINT `fk_tb_stok_opname_detail_header` FOREIGN KEY (`id_stok_opname`) REFERENCES `tb_stok_opname` (`id_stok_opname`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tb_template_jurnal`
--
ALTER TABLE `tb_template_jurnal`
  ADD CONSTRAINT `fk_tb_template_jurnal_entitas` FOREIGN KEY (`id_entitas`) REFERENCES `tb_entitas` (`id_entitas`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `tb_template_jurnal_detail`
--
ALTER TABLE `tb_template_jurnal_detail`
  ADD CONSTRAINT `fk_tb_template_jurnal_detail_coa` FOREIGN KEY (`id_coa_default`) REFERENCES `tb_coa` (`id_coa`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tb_template_jurnal_detail_header` FOREIGN KEY (`id_template_jurnal`) REFERENCES `tb_template_jurnal` (`id_template_jurnal`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
