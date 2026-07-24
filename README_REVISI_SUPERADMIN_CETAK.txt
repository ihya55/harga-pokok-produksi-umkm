REVISI FULL - SUPERADMIN DAN CETAK LAPORAN

1. Ditambahkan folder baru:
   /superadmin

   Akses:
   http://localhost/HARGA_POKOK_PRODUKSI/superadmin/

   Panel superadmin dibuat terpisah dari folder administrator, sehingga tampilan UMKM yang sudah ada tidak diubah.

2. Fungsi superadmin:
   - Dashboard khusus pembuat aplikasi.
   - Akses cepat ke pengaturan UMKM/entitas.
   - Akses cepat ke pengguna.
   - Akses cepat ke COA, mapping akun, jurnal, dan reset data.
   - Menampilkan jumlah entitas, pengguna, menu, dan jurnal.

3. Akun superadmin opsional:
   Jalankan file SQL berikut jika ingin membuat akun khusus pembuat aplikasi:
   database/update_superadmin_pembuat_aplikasi.sql

   Username: superadmin
   Password: superadmin123

   Setelah login pertama, segera ganti password.

4. Cetak laporan yang diperbaiki:
   - Kas Masuk
   - Kas Keluar
   - Jurnal
   - Log Jurnal Sumber
   - Buku Besar
   - Saldo Akun
   - Laba Rugi
   - Neraca
   - Arus Kas
   - Pembatalan Transaksi

5. Format kop cetak:
   - Logo UMKM di tengah.
   - Nama UMKM di tengah.
   - Alamat dan telepon di bawah nama UMKM.
   - Nama laporan berada di tengah.
   - Periode/tanggal laporan tampil sesuai filter tanggal.
   - Dicetak oleh dan tanggal cetak tampil pada kop laporan.

6. Kas Masuk dan Kas Keluar:
   Cetak kas masuk/keluar tidak hanya mengambil data input manual.
   Sekarang laporan kas mengambil gabungan:
   - Data input manual kas masuk/kas keluar.
   - Seluruh jurnal posted yang memengaruhi akun kas/bank/QRIS.

7. Buku Besar:
   Cetak buku besar sekarang dikelompokkan per kode akun.
   Setiap akun menampilkan baris transaksi beserta nominal debit dan kredit.
