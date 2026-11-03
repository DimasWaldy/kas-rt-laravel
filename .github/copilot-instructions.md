# Copilot Instructions — Smart RW (Laravel)

Panduan utama untuk Copilot dan AI lain saat mengerjakan repo ini. Jangan duplikasi isi blueprint;
baca file sumber saat relevan.

## Peta Dokumen

Semua blueprint berada di [`docs/`](docs/):

| Urutan baca | File                         | Isi                                        |
| ----------- | ---------------------------- | ------------------------------------------ |
| 1           | `docs/ROADMAP.md`            | Prioritas fase dan prinsip kerja AI        |
| 2           | `docs/ROLE_BLUEPRINT.md`     | Role, permission, pola nama, data scope    |
| 3           | `docs/FEATURE_SPEC.md`       | Spesifikasi fitur per modul                |
| 4           | `docs/DATABASE_BLUEPRINT.md` | Rancangan DB, konvensi kolom, uang integer |
| 5           | `docs/UI_FLOW.md`            | Alur layar per role                        |
| Kontrak     | `docs/AI_CODING_RULES.md`    | Aturan teknis wajib (wajib dibaca penuh)   |

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

# Laravel Copilot — Token Efficient Coding Rules

## Primary Goal

Complete Laravel coding tasks accurately with minimal token usage, minimal tool calls, and minimal unnecessary exploration.

## 1. Context Efficiency

- Inspect only files directly related to the requested feature.
- Never scan the entire repository unless absolutely necessary.
- Do not read vendor/, node_modules/, storage/logs/, public/build/, or generated files unless required.
- Prefer targeted symbol search over reading entire files.
- Reuse already discovered information instead of rereading unchanged files.
- Keep explanations and intermediate updates concise.

## 2. Editing Rules

- Make small, targeted changes.
- Never rewrite entire files when a localized edit is sufficient.
- Preserve existing Laravel architecture, naming, and coding conventions.
- Do not refactor unrelated code.
- Avoid unnecessary dependencies, migrations, and new abstractions.

## 3. Terminal Efficiency

- Run only commands necessary for the current task.
- Never start an unnecessary development server.
- Avoid verbose terminal output.
- Limit automatic debugging attempts to 2 per distinct failure.
- If the same error persists after 2 attempts, stop and summarize the blocker.

## 4. Laravel Workflow

- Identify relevant routes, controllers, models, services, and Blade files.
- Follow existing implementation patterns.
- Implement the smallest complete solution.
- Prefer targeted Laravel tests over the entire test suite.
- Do not run npm install, composer install, npm build, or full project tests unless required.
- Do not automatically run destructive commands.

## 5. Token Budget

- Prefer few, focused tool calls over repeated exploration.
- Do not repeat terminal commands without a reason.
- Keep tool output concise while preserving important error details.
- Avoid generating lengthy plans or explanations for straightforward changes.
- If the task expands beyond the requested scope, stop and report.

## 6. Completion

- Verify only the affected functionality.
- Report changed files, test results, and any remaining issues.
- Use no more than 5 concise lines for the final summary.
- Never claim tests passed unless they actually ran successfully.

## Search and Context Limits

- Track files and symbols already inspected during this task.
- Never repeat an identical search unless files changed or the previous result was incomplete.
- Before searching, check whether the answer is already present in context.
- Search exact file paths or narrow directories first.
- Use at most 3 exploratory searches per task initially.
- If more searches are needed, explain why.
- Do not repeatedly open unchanged controllers or models.
- Prefer targeted edits over broad refactoring.
- Preserve verification, authorization, and data integrity.
- Run targeted tests after editing.
- Stop if investigation is cycling without progress.
