# Copilot Instructions — Smart RW (Laravel)

Panduan utama untuk Copilot dan AI lain saat mengerjakan repo ini. Jangan duplikasi isi blueprint;
baca file sumber saat relevan.

## Peta Dokumen

Semua blueprint berada di [`docs/`](docs/):

| Urutan baca | File | Isi |
| --- | --- | --- |
| 1 | `docs/ROADMAP.md` | Prioritas fase dan prinsip kerja AI |
| 2 | `docs/ROLE_BLUEPRINT.md` | Role, permission, pola nama, data scope |
| 3 | `docs/FEATURE_SPEC.md` | Spesifikasi fitur per modul |
| 4 | `docs/DATABASE_BLUEPRINT.md` | Rancangan DB, konvensi kolom, uang integer |
| 5 | `docs/UI_FLOW.md` | Alur layar per role |
| Kontrak | `docs/AI_CODING_RULES.md` | Aturan teknis wajib (wajib dibaca penuh) |

Panduan operasional dan audit ada di [`docs/`](docs/), antara lain
`docs/AUDIT_RILIS_2026-10-30.md` dan panduan per fitur.

## Workflow

1. **Planning** — Baca `docs/ROADMAP.md` → pilih tugas dari `docs/task-board.md` → tentukan role,
   permission, dan data scope lewat `docs/ROLE_BLUEPRINT.md`.
2. **Development** — Ikuti `docs/AI_CODING_RULES.md` + `docs/DATABASE_BLUEPRINT.md`; kerjakan
   bertahap per modul, ikuti pola Laravel yang sudah ada.
3. **Review** — Cek scope RT/RW, permission route, validasi, upload privat, transaksi DB terhadap
   `docs/FEATURE_SPEC.md` + `docs/UI_FLOW.md`.
4. **Testing** — `composer test` (menjalankan `php artisan test`) + test frontend jika relevan +
   `npm run build`. Jangan klaim selesai sebelum lulus.

## Aturan Wajib

- Jangan edit kode sebelum tugas jelas.
- Jangan hapus/reset perubahan yang sudah ada; jangan jalankan perintah DB destruktif.
- Jangan ubah autentikasi/permission tanpa analisis `docs/ROLE_BLUEPRINT.md` dan seeder terkait.
- Implementasi harus sesuai blueprint dan role pengguna.
- Hindari audit ulang tanpa alasan; pakai hasil yang sudah ada di `docs/`.
- Laporkan konflik dokumentasi dan minta keputusan sebelum perubahan besar.
- Source of truth role = `database/seeders/RoleAndPermissionSeeder.php`; bila berbeda dengan
  `docs/ROLE_BLUEPRINT.md`, utamakan kode berjalan lalu usulkan sinkronisasi dokumen.

## Perintah Penting

```bash
composer test          # backend Pest
npm run build          # build frontend produksi
./vendor/bin/pint      # format/lint PHP (jika diperlukan)
```

Jangan commit/push kecuali diminta user.