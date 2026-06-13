# UI Flow RT/RW

Dokumen ini mengatur alur layar aplikasi RT/RW berdasarkan role. Semua AI wajib mengikuti dokumen ini agar navigasi tidak berantakan dan fitur bisa didemokan dengan jelas.

## 1. Prinsip UI

- Halaman pertama setelah login harus sesuai role.
- Menu hanya tampil jika user punya permission.
- Tombol aksi hanya tampil jika user boleh menjalankan aksi tersebut.
- Jangan menambahkan menu untuk fitur yang route/controller-nya belum siap.
- Warga harus diarahkan ke aksi praktis: bayar tagihan, lihat status, ajukan pengaduan/surat.
- Pengurus harus diarahkan ke pekerjaan operasional: verifikasi, rekap, proses pengajuan.
<!-- BARU -->
- Pengurus RT hanya melihat data warga dan transaksi RT-nya sendiri. Jangan tampilkan data RT lain walaupun secara teknis bisa diakses.
<!-- BARU -->
- Pengurus RW melihat rekap lintas RT, bukan detail per warga.

## 2. Flow Login

Guest:

1. Buka halaman publik.
2. Login/register.
3. Setelah login masuk dashboard sesuai role.

Warga:

1. Login.
2. Masuk dashboard warga.
3. Melihat ringkasan tagihan, pengaduan, surat, kegiatan.

Bendahara:

1. Login.
2. Masuk dashboard keuangan atau dashboard umum.
3. Melihat tagihan pending, saldo kas, kas masuk/keluar, laporan.

Sekretaris:

1. Login.
2. Masuk dashboard administrasi atau dashboard umum.
3. Melihat data warga, rumah, pengaduan, surat, kegiatan.

Admin:

1. Login.
2. Masuk dashboard admin.
3. Bisa akses semua modul.

<!-- BARU -->
## 2A. Flow Login per Role Smart RW

Super Admin:

1. Login.
2. Dashboard super admin menampilkan ringkasan seluruh sistem.
3. Bisa mengakses semua RT dan semua modul tanpa filter wilayah.

Ketua RW / Sekretaris RW / Bendahara RW:

1. Login.
2. Dashboard RW menampilkan ringkasan lintas RT: total warga, total kas, dan kegiatan aktif.
3. Tidak bisa masuk ke pengelolaan detail per RT.

Ketua RT:

1. Login.
2. Dashboard RT hanya menampilkan data RT-nya.
3. Bisa approve surat dan pengaduan RT-nya.
4. Tidak bisa melihat data RT lain.

Sekretaris RT (`sekretaris` lama):

1. Login.
2. Dashboard administrasi hanya menampilkan data RT-nya.
3. Menu: warga RT-nya, rumah RT-nya, pengaduan, surat, dan kegiatan.

Bendahara RT (`bendahara` lama):

1. Login.
2. Dashboard keuangan hanya menampilkan data RT-nya.
3. Menu: tagihan RT-nya, kas RT-nya, dan laporan kas RT-nya.

## 3. Navigasi Warga

Menu utama:

- Dashboard.
- Tagihan.
- Pengaduan.
- Surat.
- Kegiatan.
- Profil.

Menu tambahan jika modul aktif:

- Bank Sampah.
- UMKM Saya.
- Posyandu Keluarga.
- Jadwal Ronda.
- Rukem.
- Koperasi.
- Peminjaman Aset.

Flow utama warga:

1. Dashboard.
2. Lihat tagihan bulan ini.
3. Klik bayar.
4. Isi metode pembayaran dan bukti.
5. Lihat status pembayaran.
6. Jika ada masalah, buat pengaduan.

Aturan UI warga:

- Jangan tampilkan data warga lain.
- Jangan tampilkan menu admin.
- Jangan tampilkan tombol verifikasi/approve.
- Ringkasan kas boleh tampil publik, tetapi tidak detail sensitif.

## 4. Navigasi Bendahara

Menu utama:

- Dashboard.
- Tagihan Admin.
- Iuran Bulanan.
- Kas Masuk.
- Kas Keluar.
- Laporan Kas.

Menu tambahan jika modul aktif:

- Bank Sampah.
- Koperasi.
- Rukem.

Flow utama bendahara:

1. Buka dashboard.
2. Lihat pembayaran pending.
3. Buka Tagihan Admin.
4. Verifikasi/konfirmasi pembayaran.
5. Cek kas masuk otomatis.
6. Buat kas keluar jika ada pengeluaran.
7. Buka laporan kas.

Aturan UI bendahara:

- Fokus pada nominal, status, tanggal, bukti, dan laporan.
- Tidak perlu menu edit identitas warga penuh.
- Boleh melihat data warga yang diperlukan untuk transaksi.

## 5. Navigasi Sekretaris

Menu utama:

- Dashboard.
- Warga.
- Rumah.
- Pengaduan.
- Surat.
- Kegiatan.

Menu tambahan jika modul aktif:

- Posyandu.
- Ronda.
- UMKM.
- Aset.
- Rukem administrasi.

Flow utama sekretaris:

1. Buka dashboard.
2. Cek data warga/rumah.
3. Proses pengaduan.
4. Verifikasi surat.
5. Kelola kegiatan.
6. Kelola modul administrasi tambahan.

Aturan UI sekretaris:

- Fokus pada data administrasi dan status pengajuan.
- Tidak menampilkan aksi transaksi final kecuali diberi permission.
- Bisa melihat ringkasan kas jika dibutuhkan, tetapi bukan pengelola utama.

## 6. Navigasi Admin

Menu utama:

- Dashboard Admin.
- Warga.
- Rumah.
- Tagihan.
- Iuran Bulanan.
- Kas Masuk.
- Kas Keluar.
- Laporan Kas.
- Pengaduan.
- Semua modul tambahan aktif.

Flow utama admin:

1. Buka dashboard admin.
2. Lihat ringkasan sistem.
3. Masuk modul yang perlu dicek.
4. Kelola data, approval, dan konfigurasi.

Aturan UI admin:

- Admin boleh melihat semua menu aktif.
- Tetap gunakan validasi dan permission di route/controller.
- Jangan membuat admin bypass alur bisnis penting seperti verifikasi transaksi tanpa audit.

## 7. Flow Modul Inti

### Tagihan dan Pembayaran

Warga:

1. Dashboard.
2. Tagihan.
3. Detail tagihan.
4. Bayar.
5. Upload bukti atau pilih bayar tunai.
6. Status pending.
7. Status lunas setelah diverifikasi.

Bendahara/admin:

1. Tagihan Admin.
2. Filter pending.
3. Detail bukti.
4. Konfirmasi atau tolak.
5. Sistem update tagihan dan kas.

### Pengaduan

Warga:

1. Pengaduan.
2. Buat pengaduan.
3. Lihat detail.
4. Pantau status/tanggapan.

Sekretaris/admin:

1. Daftar pengaduan.
2. Detail pengaduan.
3. Ubah status.
4. Isi tanggapan.

### Warga dan Rumah

Sekretaris/admin:

1. Daftar warga.
2. Tambah/edit warga.
3. Daftar rumah.
4. Detail rumah.
5. Atur penanggung jawab/pindah warga.

Warga:

1. Profil.
2. Lengkapi data pribadi.
3. Simpan.

## 8. Flow Modul Tambahan

### Surat

Warga:

1. Surat.
2. Ajukan surat.
3. Upload dokumen jika perlu.
4. Pantau status.
5. Unduh surat selesai.

Sekretaris/admin:

1. Surat masuk.
2. Detail pengajuan.
3. Verifikasi.
4. Approve/reject.
5. Upload atau generate hasil surat.

### Kegiatan

Warga:

1. Kegiatan.
2. Detail kegiatan.
3. Daftar/ikut jika tersedia.

Sekretaris/admin:

1. Kelola kegiatan.
2. Buat/edit kegiatan.
3. Lihat peserta.
4. Upload dokumentasi.

### Aset

Warga:

1. Lihat aset tersedia.
2. Ajukan peminjaman.
3. Pantau status.

Pengurus:

1. Kelola aset.
2. Proses peminjaman.
3. Catat pengembalian/perawatan.

### Bank Sampah

Warga:

1. Lihat jenis sampah.
2. Submit setoran.
3. Lihat saldo/riwayat.

Pengurus:

1. Kelola jenis sampah.
2. Verifikasi setoran.
3. Lihat laporan.

### UMKM

Warga:

1. Daftarkan UMKM.
2. Kelola produk.
3. Pantau status verifikasi.

Pengurus:

1. Lihat pengajuan UMKM.
2. Approve/reject.
3. Kelola katalog.

### Posyandu

Warga:

1. Lihat jadwal.
2. Lihat data keluarga sendiri.

Kader/sekretaris:

1. Kelola jadwal.
2. Tambah peserta.
3. Input pemeriksaan.
4. Lihat rekap.

### Ronda

Warga:

1. Lihat jadwal ronda.
2. Lapor kejadian jika perlu.

Petugas/pengurus:

1. Kelola jadwal.
2. Presensi.
3. Submit laporan.
4. Verifikasi laporan.

### Rukem

Warga:

1. Lihat status keanggotaan/iuran.
2. Ajukan/lapor klaim.
3. Pantau bantuan.

Pengurus:

1. Kelola anggota.
2. Kelola iuran.
3. Proses klaim.
4. Lihat laporan dana.

### Koperasi

Warga:

1. Lihat simpanan/pinjaman sendiri.
2. Ajukan pinjaman.
3. Bayar angsuran.

Pengurus:

1. Kelola anggota.
2. Verifikasi simpanan.
3. Approve pinjaman.
4. Kelola angsuran.
5. Lihat laporan.

<!-- BARU -->
## 8A. Navigasi Pengurus RW

Menu utama `ketua_rw`:

- Dashboard RW.
- Rekap Warga (lintas RT, read-only).
- Rekap Kas (lintas RT, read-only).
- Surat (approve surat level RW).
- Kegiatan RW.

Menu utama `sekretaris_rw`:

- Dashboard RW.
- Rekap Warga.
- Surat.
- Kegiatan RW.
- Pengaduan (tanggap level RW).

Menu utama `bendahara_rw`:

- Dashboard RW.
- Rekap Kas (lintas RT).
- Laporan Kas RW.

Aturan UI pengurus RW:

- Tidak ada tombol edit data per warga.
- Tidak ada tombol verifikasi tagihan karena itu tanggung jawab bendahara RT.
- Hanya tampilkan agregat dan rekap, bukan detail transaksi per orang.

## 9. Flow Demo UAS

Urutan demo:

1. Login admin.
2. Tunjukkan dashboard admin.
3. Tunjukkan warga dan rumah.
4. Generate tagihan/iuran.
5. Login warga.
6. Tunjukkan tagihan warga.
7. Submit pembayaran.
8. Login bendahara.
9. Verifikasi pembayaran.
10. Tunjukkan kas/laporan kas.
11. Login warga.
12. Submit pengaduan atau surat.
13. Login sekretaris/admin.
14. Proses pengaduan atau surat.
15. Tunjukkan satu modul tambahan paling matang.
<!-- BARU -->
16. Login `ketua_rw` atau `super_admin`.
<!-- BARU -->
17. Tunjukkan dashboard rekap lintas RT.
<!-- BARU -->
18. Tunjukkan bahwa pengurus RT tidak bisa melihat data RT lain.

## 10. Checklist UI

Sebelum fitur dianggap selesai:

- Menu sesuai role.
- Tombol aksi sesuai permission.
- Empty state jelas.
- Error validasi tampil.
- Success message tampil.
- Data sensitif tidak bocor.
- Halaman mobile masih bisa dipakai.
- Alur utama bisa didemokan tanpa buka database manual.
