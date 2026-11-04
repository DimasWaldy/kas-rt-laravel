# Pendaftaran tanpa rumah dan pengisian domisili setelah verifikasi

## Alur

1. Warga mendaftar dengan nama, email, HP, dan password. Tidak perlu rumah atau
   RT tersedia untuk mengirim pendaftaran. Akun berstatus `pending_verifikasi`,
   role tetap `warga`, RT/rumah belum diisi. Login hanya membuka halaman status
   pendaftaran sampai disetujui. Jika ditolak, warga dapat membaca alasan dan
   [mengirim perbaikan memakai akun yang sama](PERBAIKAN_PENDAFTARAN.md).
2. Superadmin/operator global atau pengurus RT yang memiliki `manage-warga`
   membuka **Verifikasi Warga**. Khusus pendaftaran baru tanpa wilayah, pengurus
   RT aktif mana pun dapat memprosesnya setelah memeriksa identitas calon warga.
   Ini tidak membuka akses ke akun lama tanpa RT, transaksi, atau berkas wilayah
   lain. Pengurus RW tetap menggunakan pembatasan wilayah yang sudah ada.
3. Superadmin memilih RT aktif sesuai hasil pemeriksaan. Pengurus RT otomatis
   menetapkan RT-nya sendiri, tidak boleh mengirim RT lain. Akun menjadi aktif;
   rumah tetap kosong. Tidak ada kewajiban verifikasi email.
4. Setelah login, warga langsung diarahkan ke `/domisili`. Halaman layanan lain
   ditahan sampai domisili disetujui pengurus. Logout tetap dapat diakses. Email
   tetap wajib untuk login/reset password; pastikan alamatnya benar.
5. Warga memilih rumah aktif yang ada di RT terverifikasi, atau memasukkan alamat
   rumah baru jika belum tersedia. Jika daftar rumah kosong, mode alamat baru
   tersedia langsung. Penyimpanan hanya membuat **pengajuan domisili**. Akun
   belum dihubungkan ke rumah, rumah baru belum dibuat, dan bukti pembayaran
   rumah yang diajukan belum dapat dibaca. Alamat yang cocok dengan rumah yang
   sudah ada juga harus menunggu persetujuan; mengetik alamat bukan jalan pintas.
6. Di **Verifikasi Warga → Pengajuan Domisili**, pengurus dengan `manage-warga`
   memeriksa tempat tinggal warga. Pengurus RT dibatasi ke RT akun warga,
   pengurus RW ke RW-nya, dan operator global dapat memeriksa seluruh wilayah.
   Tombol **Setujui domisili** baru menghubungkan akun ke rumah yang diperiksa.
   Rumah baru dibuat pada saat persetujuan dengan kode otomatis. Rumah yang
   sudah berubah RT, nonaktif, atau dihapus tidak dapat disetujui.
7. Jika alamat belum sesuai, pengurus memilih **Kembalikan untuk diperbaiki** dan
   mengisi catatan. Warga membaca catatan di `/domisili`, memperbaiki alamat atau
   pilihan rumah, lalu mengirim ulang. Akun tidak ditolak karena koreksi domisili.
   Pengajuan yang masih menunggu tidak dapat ditimpa saat sedang diperiksa.
8. Setelah disetujui, tombol **Periksa status** di halaman domisili membawa warga
   ke profil untuk melengkapi NIK dan kontak. Penetapan KK/kepala keluarga melalui
   pengurus pada menu Data Warga, bukan melalui profil pribadi; lihat
   `docs/KEAMANAN_KARTU_KELUARGA.md`. Pengisian domisili awal ditutup. Pergantian
   rumah/RT berikutnya tetap melalui pengurus; endpoint edit profil tidak dibuka
   untuk perpindahan wilayah. Persetujuan/perbaikan dicatat beserta petugas,
   waktu, dan audit log.

Pilihan mode rumah dan alamat baru saling eksklusif: input yang tersembunyi
dinonaktifkan di browser, dan server juga menolak kedua jenis input dikirim
bersamaan. Pengisian awal tidak mengambil alih penanggung jawab iuran rumah,
tidak otomatis masuk ke KK lain, serta tidak mengubah RT hasil verifikasi.
Persetujuan domisili juga tidak mengambil alih penanggung jawab iuran atau KK.
Jika rumah belum siap ditagih, pengurus mendapat langkah **Tetapkan PJ iuran**.
Antrean tetap tersedia melalui **Data Rumah → Perlu PJ Iuran**. Setelah PJ
ditetapkan, bendahara memilih periode yang belum dibuat dan generate ulang;
lihat `docs/TAGIHAN_RUMAH_DAN_PJ.md`. Warga dapat melihat penjelasan status PJ
di halaman profil dan tagihannya.
Jika rumah dengan alamat yang sama baru dibuat selama pengajuan menunggu,
pengurus harus mengembalikan pengajuan agar rumah yang sudah terdaftar dipilih
dan diperiksa kembali; persetujuan tidak diam-diam beralih ke rumah tersebut.

## Migrasi

```sh
php artisan migrate --path=database/migrations/2026_10_30_000002_add_domicile_onboarding_to_users.php
php artisan migrate --path=database/migrations/2026_10_30_000003_add_domicile_review_to_wargas.php
php artisan optimize:clear
npm run build
```

Migration menambah `users.onboarding_domisili` dengan default false. Hanya akun
dari alur registrasi baru diberi nilai true oleh server. Akun lama, superadmin,
role/permission, data rumah, dan transaksi tidak dihapus atau diubah. Jangan
menjalankan `migrate:fresh` atau DemoSeeder untuk menerapkan perubahan ini.
Migration `000003` menambah status, mode/alamat pengajuan, catatan, dan metadata
pemeriksaan di `wargas`; data lama tetap null dan tidak diubah secara massal.
Jalankan migrasi ini sebelum mengakses halaman domisili/verifikasi dari versi
baru. Untuk instalasi yang sudah menjalankan `000002`, cukup jalankan `000003`.

Perbaikan ini mencegah hubungan rumah dibuat sendiri oleh pengajuan baru.
Hubungan rumah yang terlanjur tersimpan melalui versi sebelumnya tidak dicabut
otomatis karena tidak dapat dibedakan dengan penetapan sah dari pengurus.
Jika versi lama sudah digunakan warga, pengurus perlu memeriksa penempatan rumah
akun-akun tersebut sebelum menganggap data lama sudah terverifikasi.

Jika pengurus menetapkan rumah secara manual sebelum warga menyelesaikan langkah
ini (melalui penyimpanan model User), hak pengisian awal otomatis ditutup. Jika
RT hasil verifikasi salah, pengurus perlu mengoreksinya; warga tidak dapat memilih
wilayah lain sendiri. RT/RW nonaktif tidak dapat dipakai untuk pengisian awal.

## Uji

```sh
php artisan test tests/Feature/DomisiliOnboardingTest.php tests/Feature/Auth/RegistrationTest.php tests/Feature/Auth/AuthenticationTest.php tests/Feature/VerifikasiWargaTest.php tests/Feature/ProfileRumahTest.php
php artisan test
```

Checklist manual:

- Daftar ketika tabel rumah kosong; setujui akun; login tanpa link email.
- Ajukan alamat baru: tetap tampil status menunggu, belum ada rumah yang dibuat.
- Pengurus membuka Pengajuan Domisili dan menyetujui; rumah baru dibuat satu kali.
- Gunakan akun uji kedua untuk memilih rumah yang sudah memiliki tagihan/bukti.
  Sebelum persetujuan, bukti pembayaran dan layanan tetap tertutup. Ulangi dengan
  mengetik alamat yang sama untuk memastikan tidak ada jalan pintas.
- Kembalikan pengajuan yang salah dengan catatan; warga dapat memperbaiki dan
  mengirim ulang. Pengurus wilayah lain tidak dapat memeriksa atau menyetujui.
- Setelah pengurus menyetujui rumah yang benar, akses rumah sesuai hak warga
  tersedia; penanggung jawab iuran/KK lama tidak diambil alih.
- Perubahan/nonaktif/penghapusan rumah saat pengajuan menunggu harus ditolak
  ketika persetujuan dicoba. Pengajuan yang sudah selesai tidak bisa diproses ulang.
