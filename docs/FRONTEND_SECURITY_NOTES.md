# Frontend security follow-up — 20 Oktober 2026

## Perubahan yang diterapkan

- Axios 1.20.0, Vite 8.3.4, PostCSS 8.5.31, concurrently 9.2.5, serta dependency transitif diperbarui lewat lockfile.
- Minimum versi dependency langsung tersebut dinaikkan di `package.json`.
- `postcss-selector-parser` di-override ke `^7.1.6` untuk menutup advisory parser tanpa migrasi framework CSS. Changelog upstream mencatat perubahan mayor 7 pada iterasi/insertion node; kompatibilitas konfigurasi Tailwind proyek diuji lewat kompilasi dan build, bukan diasumsikan.
- Tailwind tetap 3.4.19. Tidak menjalankan `npm audit fix --force`, tidak menonaktifkan audit, dan tidak mengecualikan devDependencies dari laporan.
- Tes Node memeriksa kompilasi forms/responsive/dark mode dengan konfigurasi Tailwind proyek serta Axios bootstrap menggunakan adapter tiruan (tanpa HTTP eksternal). Pest menjaga minimum versi lockfile. Ini bukan uji visual browser.

## Temuan yang BELUM selesai

Sesudah pembaruan, `npm audit` masih melaporkan **5 high**, **0 critical**, **0 moderate**. Kelima entri (`braces`, `chokidar`, `micromatch`, `fast-glob`, `tailwindcss`) berasal dari satu rantai dependency `braces <=3.0.3` dan advisory `GHSA-vfj7-8cjw-p6xm`.

Registry yang diperiksa masih menyediakan `braces` 3.0.3 sebagai versi terbaru. Saran npm untuk menghilangkan rantai ini adalah migrasi Tailwind 4, sebuah perubahan mayor. Belum diterapkan karena perubahan CSS, konfigurasi, dan dukungan browser perlu diuji visual lintas halaman. Tahap security dependency **belum boleh dinyatakan selesai 100%**.

Rantai ini digunakan saat build/dev untuk pola file pada konfigurasi Tailwind. Tidak ditemukan pemanggilan compiler tersebut dari input warga pada alur aplikasi yang diperiksa; ini membatasi permukaan serangan, bukan bukti bahwa advisory boleh diabaikan. Gunakan sumber/pola build tepercaya dan jangan membuka dev server Vite ke internet. Jangan menampilkan nilai audit produksi saja (`--omit=dev`) sebagai hasil audit keseluruhan: Axios juga dibundel dari devDependencies.

## Verifikasi ulang

```bash
npm ci --ignore-scripts
npm run test:frontend
npm run build
npm audit
php artisan optimize:clear
php artisan test
```

Audit masih diharapkan exit nonzero selama rantai `braces` belum diselesaikan. Jangan menambahkan pengecualian agar angka menjadi nol. Setelah migrasi Tailwind atau patch upstream tersedia, jalankan lagi seluruh pemeriksaan dan uji visual login/register, dashboard, formulir, modal, tabel, serta mode gelap/terang.

Referensi:

- [npm audit](https://docs.npmjs.com/cli/v10/commands/npm-audit/)
- [Advisory braces](https://github.com/advisories/GHSA-vfj7-8cjw-p6xm)
- [Changelog parser CSS](https://github.com/postcss/postcss-selector-parser/blob/main/CHANGELOG.md)
- [Panduan migrasi Tailwind 4](https://tailwindcss.com/docs/upgrade-guide)
