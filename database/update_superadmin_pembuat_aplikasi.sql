-- Update akun superadmin pembuat aplikasi
-- Username : superadmin
-- Password : superadmin123
-- Catatan  : Setelah login pertama, segera ganti password dari menu pengguna/ganti password.

INSERT INTO tb_pengguna
(id_entitas, id_role, nama_pengguna, username, password_hash, nama_lengkap, email, no_hp, status_aktif, tanggal_dibuat)
SELECT 1, 1, 'Superadmin Pembuat Aplikasi', 'superadmin', '$2y$12$oZeccNIAHJp2Xgty8Nd4r.6a3B5Qd0GAVuGad.ET56XEI/ttms8li', 'Superadmin Pembuat Aplikasi', 'developer@hpp.local', '-', 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM tb_pengguna WHERE username = 'superadmin');

INSERT INTO tb_pengguna_entitas
(id_pengguna, id_entitas, id_role, entitas_default, status_aktif, tanggal_dibuat)
SELECT p.id_pengguna, 1, 1, 1, 1, NOW()
FROM tb_pengguna p
WHERE p.username = 'superadmin'
  AND NOT EXISTS (
      SELECT 1 FROM tb_pengguna_entitas pe
      WHERE pe.id_pengguna = p.id_pengguna AND pe.id_entitas = 1
  );
