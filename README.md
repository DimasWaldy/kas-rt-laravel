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
