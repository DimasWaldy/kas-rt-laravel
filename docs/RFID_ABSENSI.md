# Absensi kegiatan RFID v1

## Batas versi ini

Integrasi web/API untuk absensi musyawarah atau kegiatan RT/RW. Bukan verifikasi
identitas, login, pembayaran, persetujuan akun, atau antrean Posyandu. UID kartu
bisa disalin atau kartu dititipkan: petugas tetap mengawasi. Tidak membaca NIK,
nama, atau alamat dari chip KTP. Model reader dan kestabilan UID KTP harus diuji
sebelum digunakan; kartu/tag kompatibel boleh dipakai untuk prototipe.

Firmware/wiring belum ditentukan karena model board/reader belum diberikan.
Kontrak HTTP di bawah dapat dipakai ESP32/NodeMCU setelah perangkat dipilih.
V1 memerlukan koneksi internet: jangan menyalakan indikator sukses sebelum
server mengembalikan HTTP 200. Tidak ada antrean offline atau jaminan hadir saat
server/jaringan mati. Jam kehadiran memakai waktu server (WIB), bukan jam alat.

## Persiapan aplikasi

Jalankan dari root proyek dengan konfigurasi database yang benar:

```sh
php artisan migrate --path=database/migrations/2026_10_30_000001_create_kegiatan_rfid_tables.php
npm run build
```

Migration hanya menambahkan dua tabel dan satu kolom nullable; tidak menjalankan
seeder atau menghapus data lama. Jangan memakai `migrate:fresh`. Di production,
gunakan HTTPS yang valid, `APP_DEBUG=false`, cookie session aman, dan waktu server
yang sinkron. Pastikan cache tersedia agar throttle bekerja. Jangan menonaktifkan
validasi sertifikat TLS pada firmware. Token bukan parameter URL, jangan dicatat
di log alat atau di Git. Tidak perlu membuka port database/VPS untuk ESP32.

## Cara pengurus menggunakan

1. Masuk dengan akun aktif dan izin `manage-kegiatan`. Verifikasi email tidak diperlukan.
   Buka **Kegiatan → Detail → Absensi RFID**. Pengurus RT hanya mengelola kegiatan
   RT sendiri; pengurus RW hanya kegiatan dalam RW-nya. Pengurus tanpa assignment
   RT/RW ditolak (tidak menggunakan fallback RW pertama). Admin/superadmin boleh
   mengelola semua kegiatan. Aturan ini khusus fitur RFID; tidak mengubah fitur lama.
2. Klik **Buka / perbarui sesi**. Salin token satu kali ke alat. Berlaku 6 jam,
   hanya untuk kegiatan ini. Pembukaan ulang mengganti token sebelumnya. Satu
   sesi per kegiatan; gunakan satu reader per sesi pada prototipe pertama.
3. Cari nama warga (nomor akun ditampilkan untuk membedakan nama sama), cocokkan
   identitas secara langsung, lalu **Daftarkan kartu**. Dalam dua menit, tap kartu
   warga tersebut. Satu tap ini hanya memasangkan kartu, belum mencatat hadir.
   Jangan biarkan orang lain tap saat mode pendaftaran aktif.
4. Setelah respons `registered`, tap lagi saat kegiatan telah dimulai. Warga
   aktif di wilayah kegiatan akan tercatat hadir. Tidak perlu memilih nama lagi
   pada kegiatan berikutnya. UID tidak dikirim ke browser atau disimpan mentah.
5. Panel pengurus memperbarui kehadiran setiap 5 detik, maksimal 100 kehadiran
   terbaru (jumlah total tetap ditampilkan). Daftar status kartu di bawah panel
   perlu dimuat ulang setelah pairing. Token tidak ditampilkan lagi setelah refresh.
6. Tap berulang memberi `already_present`; waktu tap pertama tidak ditimpa.
   **Konfirmasi Hadir** lama adalah RSVP/rencana hadir, tetap terpisah dari
   `rfid_hadir_at` dan tidak dihapus oleh tap. Tap pertama tidak mengubah catatan.
7. Pendaftaran kedaluwarsa harus dibatalkan/diulang oleh petugas. Untuk mengganti
   kartu, **Cabut kartu** dulu. Ini mencabut kartu di semua kegiatan, bukan
   menghapus kehadiran atau mengubah status akun. Kartu dicabut tidak bisa tap.
8. **Tutup sesi** setelah selesai. Kegiatan selesai/dibatalkan menolak tap walaupun
   token belum kedaluwarsa. Hilangnya izin/assignment operator juga memblokir alat.

## Kontrak alat

```http
POST /api/rfid/tap
Accept: application/json
Content-Type: application/json
Authorization: Bearer <token-sesi-64-karakter>

{"uid":"A1B2C3D4"}
```

UID: hex tanpa spasi/titik dua, 8/14/20 karakter (4/7/10 byte), huruf besar/kecil
diterima. Tolak pembacaan reader yang tidak lengkap. Jangan mengirim user_id,
kegiatan_id, timestamp, status akun atau informasi kependudukan dari alat: server
menentukan semuanya berdasarkan sesi, kartu terdaftar, dan wilayah kegiatan.

Respons sukses (200): `{"status":"present","message":"Kehadiran berhasil dicatat."}`.
Status lain: `registered` atau `already_present`. Respons tidak menyertakan nama,
email, NIK, telepon, fingerprint kartu, maupun token. Batasi pembacaan satu kali
per tap fisik (lepaskan kartu sebelum membaca ulang), khususnya saat pendaftaran.

| HTTP | Makna / tindakan |
| --- | --- |
| 401 | Token salah, sesi ditutup atau kedaluwarsa; konfigurasi ulang oleh petugas. |
| 403 | Pengurus pembuka sesi tidak lagi berwenang/aktif. |
| 404 | Warga tidak aktif/di luar wilayah atau data sesi/kegiatan telah dihapus. |
| 409 | Kegiatan belum dimulai untuk absensi, sudah selesai, atau dibatalkan. |
| 422 | UID tidak valid/tidak dikenal, pairing habis waktu, atau kartu sudah terikat. |
| 429 | Maksimal 120 request/menit/IP; jangan mengirim loop terus-menerus. |
| 5xx / timeout | Jangan tampilkan berhasil. Petugas memeriksa panel sebelum mencoba ulang. |

UID di-hash dengan HMAC memakai APP_KEY; mengganti APP_KEY membuat pairing lama
tidak cocok sehingga kartu harus didaftarkan ulang (cabut pairing lama terlebih
dahulu). Jangan melakukan rotasi APP_KEY sembarangan pada aplikasi berjalan.

## Simulasi tanpa hardware (hanya akun/kegiatan uji)

Sesudah membuka sesi dan memilih warga untuk pairing, jalankan PowerShell berikut.
Token dimasukkan tersembunyi, bukan ditulis sebagai literal ke riwayat terminal:

```powershell
$base = Read-Host 'Alamat aplikasi, contoh https://smart-rw.example'
$secret = Read-Host 'Token sesi RFID' -AsSecureString
$credential = [System.Net.NetworkCredential]::new('', $secret)
$headers = @{ Authorization = 'Bearer ' + $credential.Password; Accept = 'application/json' }
Invoke-RestMethod -Method Post -Uri ($base.TrimEnd('/') + '/api/rfid/tap') -Headers $headers -ContentType 'application/json' -Body '{"uid":"A1B2C3D4"}'
# Ulangi request sekali untuk hadir, lalu sekali lagi untuk memeriksa duplicate.
# Jangan menjalankan pairing pada data warga sungguhan hanya untuk simulasi.
Remove-Variable secret, credential, headers
```

Uji fisik yang masih wajib: pembacaan KTP/tag konsisten, wiring, TLS ESP32,
indikator berhasil/gagal, tap berulang, jaringan putus, sesi tutup dan dua warga
berbeda. Kelulusan test HTTP/Pest tidak membuktikan reader sudah bekerja.

## Pengujian otomatis

```sh
php artisan test tests/Feature/KegiatanRfidTest.php
npm run test:frontend
php artisan test
```

Tests menggunakan SQLite terisolasi. Pengujian contention/concurrency pada MySQL
dan pengujian browser/perangkat fisik perlu dilakukan terpisah; row lock MySQL
tidak disimulasikan oleh SQLite. Unique constraint tetap melindungi pasangan
kegiatan-user, kartu-user, serta fingerprint kartu dari duplikasi.
