-- Tambahan chat general pelanggan dan menu Log Chat Online
-- Jalankan satu kali dari phpMyAdmin.

CREATE TABLE IF NOT EXISTS `tb_pesanan_online_chat_general` (
  `id_chat_general` INT NOT NULL AUTO_INCREMENT,
  `id_entitas` INT NOT NULL,
  `session_key` VARCHAR(120) NOT NULL,
  `nama_pelanggan` VARCHAR(150) NULL,
  `no_hp` VARCHAR(40) NULL,
  `pengirim_tipe` ENUM('customer','admin') NOT NULL DEFAULT 'customer',
  `nama_pengirim` VARCHAR(150) NULL,
  `pesan` TEXT NOT NULL,
  `status_dibaca_admin` TINYINT(1) NOT NULL DEFAULT 0,
  `status_dibaca_customer` TINYINT(1) NOT NULL DEFAULT 0,
  `tanggal_dibuat` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_chat_general`),
  KEY `idx_chat_general_entitas_session` (`id_entitas`, `session_key`),
  KEY `idx_chat_general_tanggal` (`tanggal_dibuat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET @id_penjualan := (SELECT id_menu FROM tb_menu WHERE kode_menu = 'PENJUALAN' LIMIT 1);

INSERT INTO `tb_menu` (`id_menu_induk`, `kode_menu`, `nama_menu`, `jenis_menu`, `url`, `ikon`, `urutan`, `tingkat_menu`, `status_aktif`, `perlu_login`, `keterangan`, `tanggal_dibuat`, `dibuat_oleh`)
SELECT @id_penjualan, 'LOG_CHAT_ONLINE', 'Log Chat Online', 'menu', '/penjualan/chat-online', 'fa fa-comments', 89, 2, 1, 1, 'Log chat general pelanggan dari halaman pesanan online', NOW(), 1
WHERE @id_penjualan IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM tb_menu WHERE kode_menu = 'LOG_CHAT_ONLINE');

INSERT INTO `tb_role_menu` (`id_role`, `id_menu`, `boleh_lihat`, `boleh_tambah`, `boleh_ubah`, `boleh_hapus`, `boleh_posting`, `boleh_approve`, `boleh_cetak`, `boleh_export`, `status_aktif`, `tanggal_dibuat`, `dibuat_oleh`)
SELECT r.`id_role`, m.`id_menu`, 1, 1, 1, 0, 0, 0, 1, 1, 1, NOW(), 1
FROM `tb_role` r
JOIN `tb_menu` m ON m.`kode_menu` = 'LOG_CHAT_ONLINE'
WHERE LOWER(REPLACE(r.`nama_role`, ' ', '_')) IN ('super_admin','admin_entitas','admin_penjualan')
  AND NOT EXISTS (
    SELECT 1 FROM `tb_role_menu` rm
    WHERE rm.`id_role` = r.`id_role` AND rm.`id_menu` = m.`id_menu`
  );
