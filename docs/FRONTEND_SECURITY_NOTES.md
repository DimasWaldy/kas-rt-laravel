# Frontend security follow-up — 20 Oktober 2026

## Perubahan

- Setelah pembaruan kompatibel sebelumnya, migrasi mayor disetujui: Tailwind CSS dan `@tailwindcss/vite` sekarang 4.3.3.
- Vite memakai plugin Tailwind langsung. Konfigurasi tema, sumber Blade/JS/pagination, forms, serta dark mode berbasis class dipindahkan ke `resources/css/app.css`.
- Hapus `tailwind.config.js`, `postcss.config.js`, dependency autoprefixer, dan override parser yang tidak diperlukan lagi.
- Lockfile tidak lagi membawa rantai `braces`, `micromatch`, `fast-glob`, `postcss-nested`, dan `postcss-selector-parser`. Tidak ada pengecualian audit atau `npm audit fix --force`.
- 96 file Blade hanya mengalami penggantian utility CSS kompatibilitas (shadow, radius, outline, ring, opacity, shrink/grow, blur, gradient). Pemeriksaan seluruh diff memastikan tidak ada perubahan logic Blade di luar pemetaan token tersebut.
- Default border, placeholder, cursor tombol, ring, dan font dipertahankan secara eksplisit. Gradient memakai interpolasi sRGB. CSS Font Awesome berada di layer components agar utility tetap dapat menimpanya.
- Pengecekan browser menemukan kartu `bg-lime-50` berteks terang pada latar terang di mode gelap; aturan dark background dilengkapi dan dicakup smoke test.

## Verifikasi

- `npm ci --ignore-scripts`: berhasil, 96 packages terpasang dan 97 diaudit.
- `npm audit`: **0 vulnerabilities**, termasuk devDependencies. Audit ini snapshot registry, bukan jaminan bebas semua celah.
- `npm run test:frontend`: **2 passed, 0 failed**. Kompilasi melalui Vite asli memeriksa forms, responsive, dark, Font Awesome, shadow, opacity, dan perbaikan lime. Axios diperiksa memakai adapter tiruan, bukan HTTP eksternal.
- `npm run build`: berhasil, 60 modules.
- `php artisan view:cache`: semua Blade berhasil dikompilasi.
- `php artisan test --compact`: **320 passed, 0 failed (2332 assertions)**, 72.50 detik, setelah pembersihan cache terisolasi.
- Pest menambah 9 render HTTP halaman representatif dengan data fiktif dan SQLite terisolasi, serta pemeriksaan dependency/class lama. Ekspor HTML bersifat opt-in lewat `SMART_RW_EXPORT_VISUAL_PREVIEWS=1` ke `storage/app/private/tailwind-review`, tidak menambah route publik atau mengubah data utama.

### Batas lingkungan

`php artisan optimize:clear` biasa sempat gagal karena cache database memerlukan MySQL lokal yang tidak aktif (`127.0.0.1:3306`, connection refused). Pengulangan dengan `CACHE_STORE=array` khusus proses berhasil. `.env` tidak diubah; cache database utama belum dibersihkan. Full suite dijalankan dengan SQLite sesuai konfigurasi PHPUnit, bukan MySQL produksi.

### Pengecekan visual

Preview HTML hasil render tes disajikan sementara di loopback, menggunakan asset build aktual:

- Login dan animasi pindah ke daftar.
- Dashboard admin pada mode terang/gelap; kartu tunggakan kini terbaca.
- Dashboard warga, tabel warga, tagihan, dan tab koperasi (Anggota ke Pinjaman).
- Modal Buat Tagihan dibuka/ditutup, tanpa mengirim form.
- Profil dan buka/tutup sidebar pada viewport 390 x 844; tidak ditemukan horizontal overflow dokumen profil. Dashboard desktop juga tidak overflow.

Ini smoke check tampilan, **bukan** uji end-to-end login, pengiriman form, transaksi, email, atau database utama. Seluruh halaman tidak diuji visual satu per satu. Belum ada pengujian lintas browser atau perangkat fisik. Beberapa warna dark mode lama di luar kartu yang diperbaiki masih layak diaudit aksesibilitas secara terpisah.

## Cara mengulang

```bash
npm ci --ignore-scripts
npm run test:frontend
npm run build
npm audit
php artisan view:cache
php artisan optimize:clear
php artisan test
```

Pada mesin tanpa MySQL, gunakan override cache sementara hanya untuk pemeriksaan terisolasi; jangan menganggap cache database produksi sudah dibersihkan.

Tailwind 4 menargetkan browser modern: Safari 16.4+, Chrome 111+, Firefox 128+. Browser lama perlu keputusan dukungan tersendiri. Jangan membuka Vite dev server ke internet; deploy hasil build.

Referensi:

- [npm audit](https://docs.npmjs.com/cli/v10/commands/npm-audit/)
- [Advisory braces yang dihapus bersama rantai dependency lama](https://github.com/advisories/GHSA-vfj7-8cjw-p6xm)
- [Panduan migrasi Tailwind 4](https://tailwindcss.com/docs/upgrade-guide)
- [Plugin Vite Tailwind](https://tailwindcss.com/docs/installation/using-vite)
