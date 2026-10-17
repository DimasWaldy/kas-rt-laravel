# 💸 Sistem Kas RT Laravel
<img width="1918" height="915" alt="image" src="https://github.com/user-attachments/assets/e168e83f-5966-4f48-a6ad-a53016145b4a" />
<img width="1918" height="908" alt="image" src="https://github.com/user-attachments/assets/05541fba-1136-4c6f-8957-05b3f807ecde" />
<img width="1918" height="912" alt="image" src="https://github.com/user-attachments/assets/e872fecc-4fa8-44db-a44d-1eb36651631d" />


Aplikasi manajemen kas RT berbasis web menggunakan Laravel + MySQL.

---

## 🚀 Fitur Utama

- 🔐 Authentication (Login & Register)
- 💰 Kas Masuk (input oleh warga/admin)
- 💸 Kas Keluar (khusus admin)
- 🧾 Upload bukti transaksi (gambar)
- 📊 Dashboard (total kas + chart)
- 🏆 Leaderboard warga (top iuran)
- 📋 Laporan warga (rekap iuran)

---

## 🛠 Teknologi

- Laravel
- MySQL
- Tailwind CSS
- Chart.js

---

## Admin awal

Siapkan RT aktif, lalu isi `SEED_ADMIN_EMAIL`, `SEED_ADMIN_PASSWORD` (minimal 12 karakter, unik dan kuat), dan `SEED_ADMIN_RT` dengan nama RT persis, misalnya `RT 01`. `SEED_ADMIN_NAME` opsional. Jalankan:

```bash
php artisan config:clear
php artisan db:seed --class=RestoreAdminSeeder --force
```

Seeder ini memperbarui password akun bila email sudah ada. RT hanya dihubungkan bila namanya cocok dengan tepat satu RT aktif. Nama tidak ditemukan/ambigu menghasilkan warning dan tidak mengubah RT akun lama. Tanpa RT, modul Aset dan Peminjaman Aset menolak akses (403). Hapus variabel kredensial seeder setelah selesai, lalu cache konfigurasi kembali bila dipakai pada deployment.

Untuk menghubungkan admin yang sudah ada tanpa mengganti password, jalankan `php artisan tinker`, ganti email/nama RT berikut, dan pastikan hanya ada satu RT aktif yang cocok:

```php
$admin = App\Models\User::where('email', 'admin@example.com')->whereHas('role', fn ($q) => $q->whereIn('name', ['admin', 'super_admin']))->sole();
$rt = App\Models\Rt::where('name', 'RT 01')->where('is_active', true)->sole();
$admin->update(['rt_id' => $rt->id]);
```

## Queue

`.env.production.example` memakai `QUEUE_CONNECTION=sync`: pekerjaan dijalankan dalam request, sehingga tidak memerlukan worker. `PaymentReceived` saat ini mengirim lewat kanal database secara langsung dan tidak mengimplementasikan `ShouldQueue`; trait `Queueable` saja tidak membuat notifikasi antre.

Untuk pekerjaan asynchronous, pilih `QUEUE_CONNECTION=database`, pastikan tabel `jobs` dari migration sudah tersedia, dan jalankan `php artisan queue:work --tries=3` menggunakan systemd atau Supervisor. Tanpa worker, pekerjaan yang masuk antrean tidak diproses. Setelah mengubah konfigurasi jalankan `php artisan config:cache`; restart worker setiap deployment dengan `php artisan queue:restart`.

Contoh unit systemd berikut hanya contoh teks; sesuaikan user, path PHP, dan direktori deployment:

```ini
[Unit]
Description=Smart RW queue worker
After=network.target

[Service]
User=www-data
WorkingDirectory=/var/www/smart-rw
ExecStart=/usr/bin/php artisan queue:work --tries=3
Restart=always
RestartSec=5
TimeoutStopSec=3600

[Install]
WantedBy=multi-user.target
```

WhatsApp dilewati dengan log info bila URL, token, atau nomor pengirim belum diisi. Tidak ada HTTP request dalam kondisi tersebut.

## Email/SMTP production

Smart RW memakai verifikasi email untuk akun yang sudah aktif. Di production, pastikan SMTP benar-benar berjalan agar warga bisa menerima link verifikasi.

Variabel email yang perlu dicek di `.env` production:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=isi_username_smtp
MAIL_PASSWORD=isi_password_smtp
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@domainmu.com"
MAIL_FROM_NAME="${APP_NAME}"
```

Cara tes kirim email lewat tinker:

```bash
php artisan tinker
```

```php
Mail::raw('Tes email Smart RW production', fn ($message) => $message->to('email-tujuan@example.com')->subject('Tes SMTP Smart RW'));
```

Peringatan: kalau SMTP belum aktif atau salah konfigurasi, warga yang sudah disetujui RT tetap tidak bisa masuk ke area aplikasi yang dilindungi verifikasi email karena link verifikasi tidak terkirim.

---

## ⚙️ Cara Menjalankan

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve


---

### 2. 🛠 Tambahin Tech Stack (kalau belum full)

```md
## 🛠 Teknologi
- Laravel
- MySQL
- Tailwind CSS
- Chart.js
