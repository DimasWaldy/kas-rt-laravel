# AGENTS.md — Pintu Masuk Agen AI

Repo: Smart RW (Laravel 13, PHP 8.3, Pest, MySQL/SQLite).

## Mulai dari sini

1. Baca [`.github/copilot-instructions.md`](.github/copilot-instructions.md) sebagai panduan utama.
2. Ikuti urutan baca blueprint di [`docs/`](docs/): `docs/ROADMAP.md` → `docs/ROLE_BLUEPRINT.md` →
   `docs/FEATURE_SPEC.md` → `docs/DATABASE_BLUEPRINT.md` → `docs/UI_FLOW.md`.
3. Wajib baca `docs/AI_CODING_RULES.md` sebelum menulis kode.
4. Cek `docs/task-board.md` untuk tugas dan progres.

## Peta Repository

- `app/Models/` — model + relasi + scope RT/RW.
- `app/Http/Controllers/` — controller fitur dan admin.
- `database/migrations/` — struktur DB.
- `database/seeders/RoleAndPermissionSeeder.php` — sumber role/permission authoritative.
- `routes/web.php`, `routes/api.php` — pola route + middleware.
- `tests/` — Pest feature/unit test.

## Perintah

```bash
composer test          # backend Pest
npm run build          # build frontend produksi
php artisan migrate    # migrasi (non-destruktif rutin)
```

## Aturan Non-Negosiasi

- Jangan edit kode sebelum tugas jelas.
- Jangan hapus/reset perubahan, jangan perintah DB destruktif.
- Jangan ubah autentikasi/permission tanpa analisis.
- Sesuaikan implementasi dengan blueprint dan role.
- Jangan klaim selesai sebelum test lulus.
- Commit/push hanya bila diminta user.