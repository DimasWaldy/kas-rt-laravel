# 💸 Sistem Kas RT Laravel
<img width="1918" height="917" alt="image" src="https://github.com/user-attachments/assets/2f6a6829-b6a2-4607-8e02-7ad3be01b990" />

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

## ⚙️ Cara Menjalankan

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
