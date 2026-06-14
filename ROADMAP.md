# Roadmap Pengembangan Aplikasi RT/RW

Dokumen ini menjadi acuan utama semua AI dan developer saat ngoding aplikasi RT/RW ini. Setiap pekerjaan fitur harus mengikuti urutan roadmap, standar role di `ROLE_BLUEPRINT.md`, dan pola implementasi Laravel yang sudah ada di repo.

## 1. Prinsip Kerja AI Saat Ngoding

AI wajib mengikuti aturan ini sebelum membuat atau mengubah kode:

1. Baca konteks fitur yang sudah ada.
2. Baca `ROLE_BLUEPRINT.md` untuk menentukan role, permission, dan data scope.
3. Cek route, controller, model, migration, seeder, view, dan test yang relevan.
4. Jangan mengubah pola besar aplikasi tanpa alasan kuat.
5. Jangan menghapus perubahan user yang tidak terkait.
6. Buat fitur secara bertahap: database, model, policy/middleware, controller, route, view, test.
7. Pastikan setiap fitur punya validasi request dan otorisasi.
8. Pastikan warga hanya melihat data milik sendiri, rumah sendiri, atau data publik.
9. Pastikan pengurus hanya mendapat akses sesuai bidangnya.
10. Setelah implementasi, jalankan test yang relevan atau jelaskan jika test tidak dijalankan.

Jika ada konflik antara roadmap dan kode yang sudah berjalan, AI harus mengutamakan kode yang sudah berjalan lalu menyesuaikan roadmap secara hati-hati.

## 2. Target Produk

Aplikasi ini ditujukan sebagai sistem informasi RT/RW untuk UAS dengan modul utama:

- Warga dan rumah/KK.
- Kas RT, iuran, tagihan, dan laporan kas.
- Aspirasi/pengaduan rakyat.
- Surat menyurat.
- Aset.
- Bank sampah.
- UMKM.
- Posyandu.
- Keamanan/ronda.
- Rukem.
- Koperasi.
- Kegiatan.

Prioritas utama bukan sekadar banyak fitur, tetapi fitur yang rapi, bisa didemokan, punya alur jelas, dan aman dari sisi role.

## 3. Urutan Prioritas Roadmap

### Fase 0 - Fondasi Role dan Standar

Status: wajib menjadi acuan sebelum semua fitur.

Tujuan:

- Role dan permission konsisten.
- Semua route penting dilindungi middleware.
- Data warga dan transaksi tidak bocor.
- AI punya standar ngoding yang sama.

Pekerjaan:

- Gunakan `ROLE_BLUEPRINT.md` sebagai aturan role.
- Pertahankan role aktif: `admin`, `bendahara`, `sekretaris`, `warga`.
- Tambahkan permission baru hanya lewat seeder role/permission.
- Buat test akses setiap kali modul baru ditambahkan.

Kriteria selesai:

- Warga tidak bisa masuk halaman pengurus.
- Bendahara hanya mengelola keuangan.
- Sekretaris hanya mengelola administrasi.
- Admin punya akses penuh melalui permission, bukan bypass asal-asalan.

<!-- BARU: Fase wajib migrasi scope aplikasi dari Kas RT menjadi Smart RW -->
### Fase 0.5 - Migrasi ke Smart RW

Status: dikerjakan **SEBELUM fitur UAS apa pun** dan sebelum Fase 1.

Tujuan:

- Tambah entitas RW dan RT ke database.
- Tambah `rt_id` ke tabel inti (`users`, `rumahs`, `tagihans`, `kas_masuks`, `kas_keluars`).
- Aktifkan role `ketua_rw` dan `ketua_rt` di seeder.
- Tambah role `super_admin`.
- Update middleware scope agar pengurus RT hanya mengakses data RT-nya.
- Rename label UI dari "Kas RT" menjadi "Smart RW" di `app.blade.php`.

Pekerjaan:

- Migration: `create_rws_table`, `create_rts_table`.
- Migration: `add_rt_id_to_users_and_rumahs_table`.
- Migration: `add_rt_id_to_tagihans_kas_masuks_kas_keluars_table`.
- Seeder: tambah RW dan RT dummy untuk development.
- Seeder: tambah role `super_admin`, `ketua_rw`, dan `ketua_rt` ke `RoleAndPermissionSeeder`.
- Middleware atau helper: tambah method `rtScope()` di model `User` untuk filter data per RT.
- Test: pastikan pengurus RT tidak bisa melihat data RT lain.

Kriteria selesai:

- Tabel `rws` dan `rts` sudah ada.
- `rt_id` sudah ada di tabel inti, semuanya nullable, dan data lama tetap aman.
- Role `super_admin`, `ketua_rw`, dan `ketua_rt` sudah ada di seeder.
- Kode lama pada Fase 1 masih berjalan tanpa error.
- Test yang sudah ada (64 test) tetap pass.

### Fase 1 - Stabilkan Modul Inti yang Sudah Ada

Status: **selesai pada 13 Juni 2026**.

Modul:

- Dashboard.
- Warga.
- Rumah/KK.
- Kas masuk.
- Kas keluar.
- Tagihan.
- Iuran bulanan.
- Laporan kas.
- Pengaduan.

Pekerjaan:

- Rapikan alur tagihan dari generate, bayar, verifikasi, sampai masuk kas.
- Pastikan laporan kas mengambil data yang benar dan bisa difilter.
- Pastikan dashboard admin menampilkan ringkasan yang akurat.
- Pastikan data rumah/KK menjadi dasar tagihan.
- Pastikan pengaduan punya status dan tanggapan pengurus.
- Tambahkan test untuk alur pembayaran, role access, laporan kas, dan manajemen rumah.

Kriteria selesai:

- Demo UAS bisa menunjukkan alur warga bayar tagihan.
- Bendahara bisa verifikasi pembayaran.
- Kas otomatis terupdate setelah pembayaran valid.
- Sekretaris/admin bisa mengelola warga dan rumah.
- Warga hanya melihat tagihan dan pengaduan miliknya.

Hasil finalisasi:

- Alur generate tagihan, pembayaran warga, verifikasi bendahara, dan pencatatan kas masuk sudah teruji end-to-end.
- Dashboard, laporan kas, tagihan, kas, warga, rumah, dan pengaduan sudah memakai scope RT untuk pengurus RT.
- Pengurus RW mendapat rekap lintas RT tanpa akses mutasi kas atau verifikasi tagihan RT.
- Role legacy dan role Smart RW aktif serta tetap kompatibel.
- `php artisan migrate:fresh --seed` berhasil.
- Seluruh 76 test lulus dengan 372 assertions.

### Fase 2 - Surat Menyurat

Tujuan:

- Warga bisa mengajukan surat.
- Sekretaris memproses surat.
- Ketua RT/RW atau admin bisa menyetujui jika role itu diaktifkan.

Fitur minimum:

- Form pengajuan surat.
- Jenis surat: domisili, pengantar, keterangan usaha, keterangan tidak mampu, dan surat umum.
- Status: `submitted`, `verified`, `approved`, `rejected`, `done`.
- Upload dokumen pendukung jika perlu.
- Riwayat surat milik warga.
- Halaman pengurus untuk memproses surat.

Permission:

- `submit-surat`
- `view-surat`
- `manage-surat`
- `approve-surat`
- `export-surat`

Kriteria selesai:

- Warga bisa membuat pengajuan dan melihat status.
- Sekretaris bisa memverifikasi.
- Admin bisa menyetujui atau menolak.
- Data surat warga lain tidak terlihat oleh warga biasa.

Status: selesai dan difinalisasi.

Hasil finalisasi:

- Pengajuan surat tersedia melalui modal Alpine.js dengan validasi dan old input.
- Workflow persetujuan mendukung jalur RT-only dan jalur RT dilanjutkan ke RW.
- Pengurus RT hanya dapat melihat dan memproses surat dari RT-nya sendiri.
- Warga hanya dapat melihat pengajuan surat miliknya sendiri.
- Katalog tersedia untuk 15 jenis surat kependudukan, sosial-ekonomi, pengantar, dan umum.
- Lampiran disimpan secara privat dan hanya dapat diakses oleh pihak berwenang.
- Surat selesai mendapat nomor serta kode verifikasi dan dapat dicetak dalam format resmi.
- Route detail surat dilindungi permission `view-surat`.
- Seluruh 86 test lulus dengan 425 assertions.

### Fase 3 - Kegiatan RT/RW

Tujuan:

- Pengurus bisa membuat kegiatan.
- Warga bisa melihat jadwal dan mendaftar/ikut jika diperlukan.

Fitur minimum:

- Daftar kegiatan.
- Detail kegiatan.
- Jadwal, lokasi, penanggung jawab, anggaran opsional.
- Pendaftaran peserta.
- Dokumentasi kegiatan.

Permission:

- `view-kegiatan`
- `submit-kegiatan`
- `manage-kegiatan`
- `approve-kegiatan`

Kriteria selesai:

- Warga bisa melihat kegiatan aktif.
- Sekretaris/admin bisa mengelola kegiatan.
- Bendahara bisa melihat anggaran jika kegiatan memakai dana kas.

### Fase 4 - Aset

Tujuan:

- Inventaris aset RT/RW tercatat.
- Peminjaman dan pengembalian aset bisa dilacak.

Fitur minimum:

- Data aset: nama, kode, kategori, kondisi, jumlah, lokasi.
- Riwayat perawatan.
- Pengajuan peminjaman aset oleh warga.
- Verifikasi pengurus.
- Status aset: tersedia, dipinjam, rusak, hilang, perawatan.

Permission:

- `view-aset`
- `manage-aset`
- `approve-aset`
- `export-aset`

Kriteria selesai:

- Aset bisa dicatat dan dicari.
- Peminjaman punya status dan riwayat.
- Warga tidak bisa mengubah data aset.

### Fase 5 - Bank Sampah

Tujuan:

- Warga bisa mencatat/menyetorkan sampah.
- Pengurus bisa mengelola harga, setoran, dan saldo.

Fitur minimum:

- Jenis sampah dan harga per kg.
- Setoran sampah warga.
- Verifikasi setoran.
- Saldo warga dari bank sampah.
- Laporan setoran.

Permission:

- `view-bank-sampah`
- `submit-bank-sampah`
- `manage-bank-sampah`
- `verify-bank-sampah`
- `export-bank-sampah`

Kriteria selesai:

- Warga bisa melihat riwayat setoran sendiri.
- Pengurus bisa memverifikasi setoran.
- Bendahara bisa melihat nilai saldo jika terhubung ke kas.

### Fase 6 - UMKM

Tujuan:

- Warga bisa mendaftarkan UMKM.
- RT/RW punya katalog UMKM warga.

Fitur minimum:

- Profil UMKM.
- Produk/jasa.
- Kontak dan alamat.
- Status verifikasi.
- Katalog UMKM publik untuk warga.

Permission:

- `view-umkm`
- `submit-umkm`
- `manage-umkm`
- `approve-umkm`

Kriteria selesai:

- Warga bisa mengelola UMKM sendiri.
- Sekretaris/admin bisa verifikasi.
- Data UMKM terverifikasi bisa tampil di katalog.

### Fase 7 - Posyandu

Tujuan:

- Jadwal dan data posyandu tercatat.
- Warga bisa melihat jadwal dan data keluarganya.

Fitur minimum:

- Jadwal posyandu.
- Data peserta.
- Catatan pemeriksaan.
- Rekap bulanan.

Permission:

- `view-posyandu`
- `manage-posyandu`
- `export-posyandu`

Kriteria selesai:

- Kader/sekretaris bisa mengelola data.
- Warga hanya melihat data keluarga sendiri.
- Data kesehatan tidak tampil publik.

### Fase 8 - Keamanan/Ronda

Tujuan:

- Jadwal ronda dan laporan keamanan tertata.

Fitur minimum:

- Jadwal ronda.
- Petugas ronda.
- Presensi ronda.
- Laporan kejadian.
- Rekap keamanan.

Permission:

- `view-ronda`
- `manage-ronda`
- `submit-ronda`
- `verify-ronda`

Kriteria selesai:

- Warga bisa melihat jadwal ronda.
- Petugas bisa submit laporan/presensi.
- Pengurus bisa verifikasi laporan.

### Fase 9 - Rukem

Rukem diasumsikan sebagai Rukun Kematian.

Tujuan:

- Bantuan dan data rukem tercatat.
- Pengurus bisa mengelola dana dan status bantuan.

Fitur minimum:

- Data anggota rukem.
- Iuran rukem jika ada.
- Pengajuan/laporan duka.
- Bantuan yang diberikan.
- Laporan dana rukem.

Permission:

- `view-rukem`
- `manage-rukem`
- `submit-rukem`
- `approve-rukem`
- `export-rukem`

Kriteria selesai:

- Warga bisa mengajukan/lapor status.
- Pengurus bisa memproses bantuan.
- Dana rukem tidak bercampur tanpa jejak dengan kas RT.

### Fase 10 - Koperasi

Tujuan:

- Simpan pinjam atau transaksi koperasi warga bisa dikelola.

Fitur minimum:

- Data anggota koperasi.
- Simpanan.
- Pinjaman.
- Angsuran.
- Persetujuan pengajuan.
- Laporan koperasi.

Permission:

- `view-koperasi`
- `submit-koperasi`
- `manage-koperasi`
- `approve-koperasi`
- `export-koperasi`

Kriteria selesai:

- Warga melihat transaksi koperasi sendiri.
- Bendahara/pengurus koperasi mengelola transaksi.
- Persetujuan pinjaman punya status dan audit.

## 4. Standar Struktur Implementasi Modul

Saat AI menambah modul baru, gunakan urutan ini:

1. Migration dan struktur tabel.
2. Model dan relasi.
3. Seeder permission.
4. Policy atau helper otorisasi jika ada data milik warga.
5. Route dengan middleware `auth` dan `permission:*`.
6. Controller.
7. Form request atau validasi controller.
8. View Blade sesuai layout aplikasi.
9. Navigasi jika fitur sudah siap.
10. Feature test role access dan alur utama.

Jangan mulai dari UI jika database dan otorisasi belum jelas.

## 5. Standar Status Data

Gunakan status konsisten:

- `draft`: masih disimpan, belum diajukan.
- `submitted`: sudah diajukan warga.
- `pending`: menunggu proses.
- `verified`: sudah dicek petugas.
- `approved`: disetujui.
- `rejected`: ditolak.
- `done`: selesai.
- `cancelled`: dibatalkan.

Untuk transaksi keuangan:

- `unpaid`: belum dibayar.
- `pending_transfer`: menunggu verifikasi transfer.
- `pending_offline`: menunggu pembayaran tunai.
- `paid` atau `lunas`: sudah lunas.
- `failed`: pembayaran gagal/ditolak.

Jika modul lama sudah memakai nama status tertentu, ikuti status lama dulu agar tidak merusak data.

## 6. Standar Navigasi

Navigasi harus mengikuti role:

- Warga: dashboard, tagihan, pengaduan, surat, kegiatan, profil, dan modul warga lain yang aktif.
- Bendahara: dashboard keuangan, kas, tagihan, iuran, laporan, bank sampah/koperasi/rukem jika ada dana.
- Sekretaris: dashboard administrasi, warga, rumah, surat, pengaduan, kegiatan, posyandu, ronda, UMKM.
- Admin: semua modul.

AI tidak boleh menambahkan menu yang route-nya belum siap atau belum diberi otorisasi.

## 7. Standar Test

Minimal test setiap modul:

- Guest diarahkan login.
- Warga tidak bisa akses halaman pengurus.
- Role pengelola bisa membuka halaman index/create.
- Warga hanya melihat data sendiri.
- Create/update gagal jika input tidak valid.
- Aksi approve/verify hanya bisa dilakukan role berwenang.

Untuk modul keuangan, tambah test:

- Nominal wajib valid.
- Transaksi terverifikasi mempengaruhi saldo/laporan sesuai aturan.
- Bukti pembayaran hanya bisa dilihat pemilik dan pengurus berwenang.

## 8. Urutan Demo UAS yang Disarankan

Alur demo paling aman:

1. Login admin, tampilkan dashboard.
2. Tampilkan data warga dan rumah.
3. Generate iuran/tagihan.
4. Login warga, lihat tagihan.
5. Warga submit pembayaran.
6. Login bendahara, verifikasi pembayaran.
7. Tampilkan kas dan laporan kas.
8. Warga submit pengaduan.
9. Sekretaris/admin tanggapi pengaduan.
10. Tampilkan satu modul tambahan yang paling matang, misalnya surat atau kegiatan.

Modul tambahan lain boleh ada sebagai CRUD pendukung, tetapi jangan mengorbankan kestabilan alur inti.

## 9. Larangan untuk AI

- Jangan membuat fitur tanpa permission.
- Jangan membuat route admin tanpa middleware.
- Jangan menampilkan data semua warga kepada role `warga`.
- Jangan mengubah nama permission lama tanpa update seeder, middleware, test, dan dokumentasi.
- Jangan membuat tabel transaksi uang tanpa audit/status.
- Jangan mencampur kas RT, koperasi, bank sampah, dan rukem tanpa field kategori atau sumber dana yang jelas.
- Jangan menyimpan file upload tanpa validasi tipe dan ukuran.
- Jangan membuat UI yang menampilkan tombol aksi jika user tidak berwenang.
- Jangan menghapus kode lama yang tidak dipahami hanya agar fitur baru cepat jalan.

## 10. Definisi Selesai

Sebuah fitur dianggap selesai jika:

- Route sudah dilindungi.
- Permission sudah ada.
- Role sudah dimapping.
- Validasi input sudah ada.
- Data scope sudah aman.
- View mengikuti layout aplikasi.
- Test akses minimal sudah ada.
- Fitur bisa didemokan dari awal sampai akhir.
- Dokumen roadmap/blueprint diperbarui jika ada standar baru.

Roadmap ini harus dibaca bersama `ROLE_BLUEPRINT.md`. Jika AI berikutnya diminta ngoding fitur apapun, mulai dari roadmap ini, lalu cek blueprint role, baru implementasi.
