# CLAUDE.md

## Konteks project

- Project Laravel (PHP). Kode utama di `app/`, route di `routes/`, view/asset sumber di `resources/`, test di `tests/`, dokumentasi di `docs/`.
  <!-- ISI: 1-2 kalimat tentang project ini, versi Laravel/PHP, dan stack frontend (Blade/Vue/React/Livewire/dll) -->

## Environment

- OS: Windows. Shell: PowerShell.
- Pakai sintaks PowerShell, bukan bash. Jangan pakai `export`, `rm -rf`, `cat`, `grep` ala Linux.
    - Hapus: `Remove-Item`, set env: `$env:NAMA = "nilai"`.
    - Chain command pakai `;` (jangan `&&`, belum tentu didukung).
- Path pakai backslash atau quote kalau ada spasi.

## Command project

- Install PHP deps: `composer install`
- Install JS deps: `npm install`
- Jalanin server: `php artisan serve`
- Frontend dev: `npm run dev`
- Test: `php artisan test`
- Daftar route: `php artisan route:list`
  <!-- Tambah/ubah kalau project pakai Pint, PHPStan, dll -->

## Cara kerja

- Baca file yang relevan dulu sebelum edit. Jangan menebak isi file atau struktur project.
- Untuk tugas yang menyentuh lebih dari 3 file, tulis rencana singkat dulu dan tunggu persetujuan.
- Perubahan kecil dan fokus. Kerjakan yang diminta saja, jangan refactor atau rapihin kode di luar permintaan.
- Ikuti konvensi Laravel dan gaya kode yang sudah ada di project (Eloquent, Form Request, Resource, dll). Cek dulu pola yang dipakai sebelum bikin yang baru.
- Pakai `php artisan make:*` untuk bikin controller, model, migration, dsb, biar struktur sesuai konvensi.
- Jangan tambah package (composer/npm) baru tanpa tanya dulu.
- Jangan mengarang method, helper, atau package Laravel. Kalau tidak yakin, cek kode yang ada atau bilang tidak yakin.

## Database dan migration

- Migration yang sudah pernah dijalankan jangan diedit. Bikin migration baru.
- DILARANG tanpa izin: `migrate:fresh`, `migrate:rollback`, `migrate:reset`, `db:wipe`, `db:seed` di database yang berisi data.
- Sebelum menjalankan migration, sebutkan apa yang akan berubah.

## File yang tidak boleh disentuh

- `.env`: jangan baca isinya, jangan edit, jangan tampilkan di output. Kalau butuh key baru, tambahkan ke `.env.example` dan kasih tahu saya.
- `vendor/`, `node_modules/`, `storage/`, `bootstrap/cache/`: jangan diedit manual.
- Jangan menulis secret, token, atau API key ke kode.

## Verifikasi

- Setelah mengubah kode, jalankan `php artisan test` (atau test yang relevan) dan lint kalau ada.
- Jangan bilang "selesai" kalau belum diverifikasi. Kalau tidak bisa dijalankan, bilang apa adanya.
- Kalau test gagal, perbaiki penyebabnya. Jangan hapus atau melemahkan test supaya lolos.

## Wajib tanya dulu sebelum

- Menghapus file atau folder.
- Menjalankan `git push`, `git reset --hard`, `git clean`, force push, atau rewrite history.
- Install package secara global atau mengubah konfigurasi sistem.
- Mengubah file di luar folder project ini.

## Git

- Jangan commit sendiri kecuali diminta.
- Kalau diminta commit: pesan singkat dan jelas, format `tipe: deskripsi` (contoh: `fix: perbaiki validasi email`).

## Komunikasi

- Balas dalam bahasa Indonesia, ringkas dan langsung. Kode, nama variabel, dan komentar kode tetap bahasa Inggris.
- Kalau permintaan ambigu, tanya satu pertanyaan klarifikasi, jangan langsung menebak.
- Setelah selesai, rangkum singkat: apa yang diubah, file mana, dan apa yang sudah dites.

# Claude Agent — Smart RW

Pintu masuk agent utama ada di [`AGENTS.md`](../../AGENTS.md).

Ikuti urutan baca dokumen pada [`docs/`](../../docs/):
`docs/ROADMAP.md` → `docs/ROLE_BLUEPRINT.md` → `docs/FEATURE_SPEC.md` → `docs/DATABASE_BLUEPRINT.md` → `docs/UI_FLOW.md`,
lalu baca penuh `docs/AI_CODING_RULES.md` sebelum menulis kode.

Patuhi aturan wajib di `.github/copilot-instructions.md`. Jangan ubah kode tanpa tugas jelas,
jangan hapus/reset perubahan, jangan jalankan perintah DB destruktif, dan jangan klaim selesai
sebelum test lulus.
