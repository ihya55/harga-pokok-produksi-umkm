Update PPN Default
==================

Perubahan yang ditambahkan:
1. Tabel tb_pajak untuk menyimpan persentase PPN/default pajak per entitas.
2. Menu Master Setup > Pajak / PPN.
3. Form Faktur Pembelian dan Faktur Penjualan mengambil default PPN dari tb_pajak.
4. File SQL update ada di database/update_ppn_default.sql.

Cara pasang:
1. Copy folder aplikasi ini ke htdocs seperti biasa.
2. Import/jalankan database/update_ppn_default.sql pada database aplikasi.
3. Login administrator, buka Master Setup > Pajak / PPN.
4. Edit persentase PPN default sesuai kebutuhan, misalnya 11 atau 12.

Catatan:
- Default awal dibuat 11%.
- Jika ada beberapa data PPN, yang bertanda Default dan Aktif akan dipakai otomatis di faktur.
