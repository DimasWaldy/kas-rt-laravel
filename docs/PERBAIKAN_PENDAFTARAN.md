# Status dan perbaikan pendaftaran

## Alur warga

1. Daftar sekali memakai nama, email, nomor HP, dan password.
2. Masuk memakai akun yang sama. Sebelum persetujuan, akun hanya dapat membuka
   **Status pendaftaran** (`/pendaftaran`) dan keluar. Status menunggu bisa
   diperiksa kembali tanpa mendaftar ulang.
3. Jika ditolak, halaman menampilkan alasan dari pengurus. Perbaiki nama/nomor HP,
   isi klarifikasi, lalu pilih **Kirim perbaikan & ajukan ulang**. Untuk identitas
   atau dokumen yang perlu diperiksa, hubungi pengurus dan bawa dokumen saat tatap muka.
4. Akun yang sama kembali berstatus `pending_verifikasi`; data Warga kembali
   `pending`. Email, password, role, RT, rumah, dan keanggotaan KK tidak ditetapkan
   ulang oleh form ini. Tidak ada akun duplikat atau aktivasi otomatis.
5. Pengurus memeriksa ulang. Setelah disetujui, pendaftar baru melanjutkan
   [pengajuan domisili](REGISTRASI_DOMISILI.md), yang tetap membutuhkan persetujuan.

Jika lupa password, keluar dan gunakan **Lupa Password** di halaman masuk. Reset
password tidak mengaktifkan akun. Pengiriman email reset di produksi tetap perlu
konfigurasi mail yang benar; uji otomatis memakai notification fake.

Daftar memakai email yang sudah ada tetap ditolak oleh validasi unik, dengan
petunjuk masuk ke akun lama atau reset password. Form registrasi publik tidak
boleh menimpa akun hanya karena seseorang mengetahui emailnya.

## Alur pengurus

- Penolakan wajib memuat alasan 10–1.000 karakter yang bisa ditindaklanjuti warga.
  Warga membaca alasan setelah membuktikan email/password melalui login.
- Pengajuan ulang muncul dalam antrean **Verifikasi Warga** dengan batas wilayah
  yang sama. Pendaftaran baru tanpa RT masih mengikuti kebijakan pemeriksaan
  awal: operator global atau pengurus RT aktif memeriksa identitasnya. Akun lama
  tetap mengikuti wilayah yang tersimpan; akun lama tanpa wilayah perlu operator global.
- Detail pemeriksaan menampilkan alasan penolakan sebelumnya dan klarifikasi
  terakhir. Pengurus dapat memverifikasi atau menolak kembali dengan alasan baru.
- Penolakan dan pengajuan ulang dicatat sebagai `pendaftaran_ditolak` dan
  `pendaftaran_diajukan_ulang` di `audit_logs`, termasuk pelaku/waktu. Pengajuan
  ulang menyimpan alasan dan petugas pemeriksaan sebelumnya sebelum mengosongkan
  hasil pemeriksaan aktif. Penolakan lama sebelum perubahan ini tetap tersimpan
  dalam audit saat diajukan ulang.

## Pembatasan dan penerapan

- Middleware web membatasi seluruh akun `pending_verifikasi`/`ditolak`, termasuk
  akun lama tanpa penanda onboarding. Halaman layanan diarahkan ke status;
  request mutasi dan JSON ke layanan menghasilkan 403.
- Halaman status hanya membaca akun yang login, tidak menerima ID warga tujuan,
  dan memakai `Cache-Control: no-store`. Alasan/klarifikasi di-escape saat dirender.
- Pengajuan ulang memakai transaksi dan kunci baris Warga lalu User. Status
  diperiksa ulang dari database; kirim ganda atau form lama tidak mereset hasil
  persetujuan. Login dan pengajuan ulang dibatasi jumlah percobaannya.
- Form hanya mengubah nama, HP, status pemeriksaan, dan mencatat klarifikasi.
  Catatan warga tidak pernah menetapkan rumah, RT, kepala KK, atau PJ iuran.
- Tidak memerlukan migration baru. Menggunakan kolom status/catatan yang sudah
  ada dan tabel audit. Tidak ada pembaruan massal data akun lokal.

## Verifikasi lokal

Hasil akhir: **422 test backend lulus (3.564 assertions), 5 test frontend lulus,
dan build produksi berhasil**. Database tes memakai SQLite in-memory. Perubahan
belum dideploy; pengiriman email nyata dan tampilan browser produksi belum diuji.

```powershell
& 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' artisan test --compact tests/Feature/Auth/RegistrationRecoveryTest.php tests/Feature/Auth/RegistrationTest.php tests/Feature/VerifikasiWargaTest.php tests/Feature/DomisiliOnboardingTest.php
npm.cmd run test:frontend
npm.cmd run build
```

Regresi mencakup password salah, pembatasan percobaan, email sama, status privat,
blokir layanan dan bukti pembayaran, koreksi data, pemalsuan field, pengajuan
ulang ganda, riwayat penolakan berulang, batas RT, reset password, dan persetujuan
yang tetap harus dilanjutkan dengan pemeriksaan domisili.
