# Panduan Migrasi & Kesiapan Produksi VPS — Smart RW

Dokumen ini menjadi rujukan lengkap untuk memindahkan aplikasi Smart RW (Laravel 13, PHP 8.3, MySQL, Vite) dari lokal ke Virtual Private Server (VPS) produksi (Ubuntu 22.04 / 24.04 LTS).

Menjawab task board:
- **REL-9**: Audit kesiapan server produksi.
- **REL-10**: Seed wilayah nyata + admin produksi.

---

## 1. Spesifikasi Minimum VPS

- **CPU**: 1 vCPU (rekomendasi 2 vCPU).
- **RAM**: 2 GB (rekomendasi 4 GB; jika 1-2 GB wajib aktifkan swap 2 GB).
- **Storage**: SSD 25 GB+.
- **OS**: Ubuntu 22.04 LTS atau 24.04 LTS.
- **Domain**: Domain aktif yang sudah diarahkan DNS A record ke IP VPS.

---

## 2. Checklist Persiapan Sebelum Sentuh Server

Siapkan kredensial berikut di tempat aman (password manager), **jangan pernah commit ke git**:

- [ ] IP VPS, user non-root (misal: `deploy`), dan SSH key.
- [ ] Nama domain (misal: `smartrw.rw05-kelurahan.id`).
- [ ] Kredensial MySQL produksi (database, user, password kuat).
- [ ] Kredensial SMTP email untuk reset password / notifikasi (host, port, user, pass).
- [ ] Kredensial gateway WhatsApp (opsional, jika modul WA aktif).
- [ ] Kredensial akun Super Admin pertama:
  - `SEED_ADMIN_NAME`
  - `SEED_ADMIN_EMAIL`
  - `SEED_ADMIN_PASSWORD` (minimal 12 karakter acak)
- [ ] Data wilayah nyata RT/RW:
  - Nama RW (misal: "RW 05").
  - Daftar RT yang ada di bawahnya (misal: "RT 01", "RT 02", dst.).

---

## 3. Langkah Instalasi Server (Sekali Saja di VPS)

Jalankan perintah berikut di VPS sebagai `root` atau user dengan `sudo`:

### 3.1. Update OS & Swap (jika RAM <= 2 GB)

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y curl git unzip ufw zip

# Buat swap 2GB bila RAM terbatas
if [ $(free -m | awk '/^Mem:/{print $2}') -le 2048 ]; then
    sudo fallocate -l 2G /swapfile
    sudo chmod 600 /swapfile
    sudo mkswap /swapfile
    sudo swapon /swapfile
    echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
fi
```

### 3.2. Install PHP 8.3 & Ekstensi yang Dibutuhkan

```bash
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

sudo apt install -y \
    php8.3-fpm \
    php8.3-cli \
    php8.3-mysql \
    php8.3-mbstring \
    php8.3-xml \
    php8.3-curl \
    php8.3-zip \
    php8.3-bcmath \
    php8.3-intl \
    php8.3-gd
```

### 3.3. Install Nginx, MySQL, Composer, & Node.js

```bash
# Nginx & Certbot
sudo apt install -y nginx certbot python3-certbot-nginx

# MySQL Server
sudo apt install -y mysql-server
sudo mysql_secure_installation

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Node.js 20 LTS (untuk build frontend)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

### 3.4. Siapkan Database MySQL Produksi

Masuk ke MySQL (`sudo mysql`):

```sql
CREATE DATABASE smart_rw CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'smart_rw_user'@'localhost' IDENTIFIED BY 'GANTI_DENGAN_PASSWORD_DATABASE_KUAT';
GRANT ALL PRIVILEGES ON smart_rw.* TO 'smart_rw_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

## 4. Setup Aplikasi di VPS

Asumsi path deployment: `/var/www/smart-rw`.

### 4.1. Clone Repository & Atur Izin

```bash
sudo mkdir -p /var/www/smart-rw
sudo chown -R $USER:www-data /var/www/smart-rw

git clone https://github.com/DimasWaldy/kas-rt-laravel.git /var/www/smart-rw
cd /var/www/smart-rw

# Pindah ke branch main yang sudah stabil
git checkout main
```

### 4.2. File `.env` Produksi

Salin template produksi dan edit nilainya:

```bash
cp .env.production.example .env
nano .env
```

Nilai wajib diatur:
```dotenv
APP_NAME="Smart RW"
APP_ENV=production
APP_KEY=                          # Di-generate di langkah 4.3
APP_DEBUG=false                   # Wajib FALSE di produksi!
APP_URL=https://smartrw.domain-anda.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=smart_rw
DB_USERNAME=smart_rw_user
DB_PASSWORD=GANTI_DENGAN_PASSWORD_DATABASE_KUAT

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true        # Aktifkan karena wajib HTTPS

QUEUE_CONNECTION=sync             # atau 'database' bila pakai supervisor

# Kredensial SMTP
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=postmaster@domain-anda.com
MAIL_PASSWORD=rahasia-smtp
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@domain-anda.com"
MAIL_FROM_NAME="Smart RW"

# Kredensial untuk RestoreAdminSeeder (REL-10)
SEED_ADMIN_NAME="Super Administrator RW"
SEED_ADMIN_EMAIL="admin@domain-anda.com"
SEED_ADMIN_PASSWORD="PasswordKuatAdmin2026!"
```

### 4.3. Install Dependencies & Build Frontend

```bash
# Backend dependencies (tanpa package dev)
composer install --no-dev --optimize-autoloader --no-interaction

# Generate APP_KEY
php artisan key:generate --force

# Storage link
php artisan storage:link

# Frontend dependencies & build aset
npm ci
npm run build
```

### 4.4. Izin Direktori (Penting untuk Keamanan)

```bash
sudo chown -R www-data:www-data /var/www/smart-rw/storage /var/www/smart-rw/bootstrap/cache
sudo chmod -R 775 /var/www/smart-rw/storage /var/www/smart-rw/bootstrap/cache
```

---

## 5. Eksekusi Migrasi & Data Nyata (REL-10)

Jalankan berurutan:

```bash
# 1. Jalankan seluruh migration struktur tabel
php artisan migrate --force

# 2. Seed Role & Permission resmi (Wajib pertama)
php artisan db:seed --class=RoleAndPermissionSeeder --force

# 3. Buat RW dan RT Nyata
# Masuk tinker atau buat migration/seeder khusus wilayah nyata:
php artisan tinker --execute='
$rw = App\Models\Rw::firstOrCreate(["name" => "RW 05"], ["is_active" => true]);
foreach (["RT 01", "RT 02", "RT 03", "RT 04", "RT 05"] as $rtName) {
    App\Models\Rt::firstOrCreate(["rw_id" => $rw->id, "name" => $rtName], ["is_active" => true]);
}
echo "Wilayah nyata berhasil di-seed.\n";
'

# 4. Buat Akun Super Admin Produksi (membaca SEED_ADMIN_* dari .env)
php artisan db:seed --class=RestoreAdminSeeder --force

# 5. HAPUS kredensial SEED_ADMIN_* dari .env setelah selesai demi keamanan!
sed -i '/^SEED_ADMIN_/d' .env
```

---

## 6. Konfigurasi Nginx & SSL (REL-9)

### 6.1. Virtual Host Nginx

Buat file `/etc/nginx/sites-available/smart-rw`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name smartrw.domain-anda.com;
    root /var/www/smart-rw/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header Referrer-Policy "strict-origin-when-cross-origin";

    index index.php;

    charset utf-8;

    # Tolak akses file hidden/git/.env
    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Upload berkas privat via controller, bukan direct access
    location /storage/private/ {
        deny all;
        return 404;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }
}
```

Aktifkan konfigurasi:

```bash
sudo ln -s /etc/nginx/sites-available/smart-rw /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 6.2. Pasang SSL Gratis (Let's Encrypt)

```bash
sudo certbot --nginx -d smartrw.domain-anda.com
```

Certbot akan otomatis memperbarui konfigurasi Nginx ke HTTPS dan mengatur perpanjangan sertifikat otomatis.

---

## 7. Pasang Scheduler Cron & Firewall (REL-9)

### 7.1. Laravel Scheduler (Wajib untuk Tagihan & Notifikasi Rutin)

Edit crontab untuk user `www-data`:

```bash
sudo crontab -u www-data -e
```

Tambahkan baris berikut di paling bawah:

```cron
* * * * * cd /var/www/smart-rw && php artisan schedule:run >> /dev/null 2>&1
```

> **Catatan jadwal yang otomatis berjalan via scheduler:**
> - `bills:generate` — tanggal 1 setiap bulan pukul 07:00 WIB.
> - `tagihan:daily-notification` — setiap hari pukul 08:00 WIB.

### 7.2. Optimasi Cache Produksi

Jalankan setiap selesai deploy/update kode:

```bash
cd /var/www/smart-rw
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 7.3. Firewall UFW

```bash
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow ssh
sudo ufw allow 'Nginx Full'
sudo ufw enable
```

---

## 8. Checklist Verifikasi Akhir (Go-Live Audit)

Sebelum aplikasi disebarkan ke warga, lakukan verifikasi ini secara manual melalui browser:

- [ ] **HTTPS Valid**: Buka `https://smartrw.domain-anda.com`, pastikan gembok hijau aktif, redirect HTTP ke HTTPS berjalan otomatis.
- [ ] **Debug Mode Mati**: Buka halaman yang tidak ada (misal `/halaman-pasti-404`), pastikan menampilkan halaman 404 aplikasi, **bukan** halaman debug Flare/Ignition Laravel.
- [ ] **Akses `.env` Tertutup**: Akses `https://smartrw.domain-anda.com/.env` dari browser, harus menghasilkan **403 Forbidden** atau **404 Not Found**.
- [ ] **Login Super Admin**: Login memakai email dan password dari `SEED_ADMIN_*`. Masuk ke dashboard utama.
- [ ] **Data Wilayah**: Cek menu admin, pastikan RW dan daftar RT sesuai dengan wilayah nyata.
- [ ] **Pengujian Alur Warga (Smoke Test)**:
  1. Daftar warga baru dari halaman registrasi.
  2. Buka akun pengurus RT, setujui pendaftaran.
  3. Akun warga mengisi domisili rumah, pengurus menyetujui.
  4. Pengurus menetapkan Penanggung Jawab (PJ) rumah.
  5. Generate iuran bulanan berjalan dan tagihan muncul di akun warga.
  6. Warga upload bukti bayar, bendahara verifikasi lunas.
- [ ] **Kirim Email / Reset Password**: Gunakan fitur "Lupa Password", pastikan email reset masuk ke inbox (bukan spam).
- [ ] **Cadangan Database Pertama**:
  ```bash
  mysqldump -u smart_rw_user -p smart_rw | gzip > /var/backups/smart_rw_init_$(date +%F).sql.gz
  ```

---

## 9. Skrip Pembaruan Kode di Kemudian Hari (Deploy Rutin)

Simpan sebagai `/var/www/smart-rw/deploy.sh`:

```bash
#!/usr/bin/env bash
set -e

cd /var/www/smart-rw

echo "1. Aktifkan Maintenance Mode..."
php artisan down --render="errors::503"

echo "2. Ambil kode terbaru..."
git fetch origin main
git reset --hard origin/main

echo "3. Update PHP dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "4. Migrasi database aman..."
php artisan migrate --force

echo "5. Build frontend (jika ada perubahan UI)..."
npm ci
npm run build

echo "6. Cache konfigurasi..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "7. Matikan Maintenance Mode..."
php artisan up

echo "Deploy selesai dengan sukses!"
```

Beri izin eksekusi:
```bash
chmod +x /var/www/smart-rw/deploy.sh
```
