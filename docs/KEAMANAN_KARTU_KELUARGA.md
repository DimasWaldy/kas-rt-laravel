# Penetapan KK dan kepala keluarga melalui pengurus

## Perubahan

Profil pribadi hanya memperbarui nama akun, email, nomor HP, dan NIK. Nomor KK,
keanggotaan KK, nama kepala keluarga, dan status kepala/anggota ditampilkan sebagai
data baca saja. Pengiriman field KK yang berbeda melalui request profil tetap
ditolak oleh server; field ID KK dan konfirmasi penggantian juga tidak dapat
dipakai sebagai jalan pintas. Input lama yang mengirim nilai KK/status yang sama
masih diterima untuk kompatibilitas, tetapi tidak menulis ulang data keluarga.

Perubahan nama akun seorang kepala keluarga tidak otomatis mengganti nama kepala
KK resmi. Koreksi nama resmi tetap melalui pengurus agar data bersama tidak
ditimpa oleh perubahan profil pribadi.

## Cara melengkapi atau mengoreksi KK

1. Warga menyerahkan informasi KK yang benar kepada pengurus.
2. Pengurus aktif dengan permission `manage-warga` membuka **Data Warga → Edit**
   untuk warga dalam wilayahnya. Operator global dapat mengelola seluruh wilayah.
3. Pengurus memeriksa nomor KK dan tempat tinggal. KK yang sudah ditempatkan di
   rumah lain tidak dapat dihubungkan, termasuk jika warga belum mempunyai rumah.
   KK lama tanpa rumah juga tidak boleh diambil alih dari anggota di wilayah lain.
4. Untuk anggota keluarga pada KK yang sudah ada, pengurus menyimpan nomor KK
   tanpa mencentang **Kepala Keluarga**. Nama kepala KK yang ada tetap dipertahankan.
5. Untuk KK baru dengan warga sebagai anggota, pengurus wajib mengisi nama kepala
   keluarga yang sebenarnya. Sistem tidak menebak nama kepala dari nama anggota.
   Jika warga yang didaftarkan adalah kepala keluarga, nama akun warga digunakan.

Jika data keluarga belum ditetapkan, indikator profil tetap menunjukkan kekurangan
KK/status keluarga sampai pengurus menyelesaikannya. Warga tetap dapat melengkapi
data pribadinya melalui profil; halaman profil menjelaskan bagian yang harus
ditangani pengurus.

## Penggantian kepala keluarga

- Satu KK tidak boleh mendapat kepala keluarga kedua melalui tambah/edit warga.
- Jika sudah ada kepala lain, pengurus harus mencentang **Kepala Keluarga** dan
  konfirmasi **Saya sudah memeriksa dan menyetujui penggantian kepala KK yang sudah
  terdaftar**. Tanpa konfirmasi, penyimpanan ditolak dan seluruh perubahan dibatalkan.
- Penggantian yang disetujui menjadikan kepala sebelumnya sebagai anggota dan
  menyimpan kepala baru. Jika terdapat beberapa kepala ganda dari versi lama,
  tindakan yang sama dapat dipakai pengurus untuk menetapkan satu kepala yang benar.
- Penggantian, nama kepala resmi, dan keanggotaan disimpan dalam satu transaksi
  dengan penguncian baris KK. Audit log `keluarga_diperbarui` merekam pengurus,
  waktu, data sebelum/sesudah, dan kepala yang digantikan.
- Penggantian kepala KK tidak otomatis memindahkan penanggung jawab iuran rumah.
  Penetapan iuran tetap menggunakan alur pengelolaan rumah yang sudah ada.

## Data lama dan penerapan

Tidak ada migration baru untuk perbaikan KK ini. Tidak ada penghapusan atau
pemilihan kepala keluarga secara massal. Data kepala ganda lama perlu diperiksa
pengurus sebelum dikoreksi melalui konfirmasi penggantian.

Untuk lingkungan lain, terapkan perubahan kode dan jalankan `npm run build`.
Migrasi domisili dari perbaikan sebelumnya tetap diperlukan bila lingkungan itu
belum menerapkannya; lihat `docs/REGISTRASI_DOMISILI.md`. Tidak perlu menjalankan
seeder demo atau melakukan reset database.

## Verifikasi

```sh
php artisan test tests/Feature/FamilyAssignmentSecurityTest.php tests/Feature/ProfileTest.php tests/Feature/ProfileRumahTest.php tests/Feature/ProfileCompletionNotificationTest.php tests/Feature/CoreResidentRtScopeTest.php
php artisan test
npm run test:frontend
npm run build
```

Checklist manual:

- Profil warga menampilkan KK dan status keluarga tanpa pilihan untuk mengubahnya.
- Perubahan nama akun kepala tidak menimpa nama resmi kepala KK.
- Memilih kepala kedua lewat Data Warga ditolak jika konfirmasi belum dicentang.
- Penggantian yang dikonfirmasi menyisakan tepat satu kepala dan menghasilkan audit.
- Anggota tambahan tidak mengganti nama kepala yang sudah terdaftar.
- Pengurus RT/RW lain dan warga biasa tidak dapat melakukan penggantian tersebut.
- Penanggung jawab iuran rumah tetap sama setelah penggantian kepala KK.
