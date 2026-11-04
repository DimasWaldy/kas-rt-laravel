# Task Board — Smart RW

Papan progres pengerjaan. Sumber tugas: `docs/AUDIT_RILIS_2026-10-30.md`, `docs/ROADMAP.md`,
dan kebutuhan dev harian.

## Status Key

- `backlog` — belum diambil
- `todo` — siap dikerjakan
- `in-progress` — sedang dikerjakan
- `review` — menunggu review/test
- `done` — selesai dan terverifikasi

## Board

| Status | ID | Tugas | Referensi | Catatan |
| --- | --- | --- | --- | --- |
| done | REL-1 | Tutup celah pemilihan rumah warga baru | `docs/REGISTRASI_DOMISILI.md` | 368 test lulus |
| done | REL-2 | Cegah penimpaan kepala KK ganda | `docs/KEAMANAN_KARTU_KELUARGA.md` | 388 test lulus |
| done | REL-3 | Tagih rumah tanpa PJ dengan penjelasan | `docs/TAGIHAN_RUMAH_DAN_PJ.md` | 408 test lulus |
| done | REL-4 | Perbaiki alur pendaftaran buntu | `docs/PERBAIKAN_PENDAFTARAN.md` | audit: dibenahi |
| done | REL-5 | Cegah superadmin buat warga aktif tanpa RT | `docs/AUDIT_RILIS_2026-10-30.md` | audit: dibenahi |
| done | REL-6 | Batasi verifikasi dokumen tanpa unggahan | `docs/AUDIT_RILIS_2026-10-30.md` | audit: dibenahi |
| done | WFL-1 | Audit dokumentasi + workflow AI coding | `docs/AI_CODING_RULES.md` | tidak ubah kode |
| done | WFL-2 | Buat `.github/copilot-instructions.md`, `AGENTS.md` | file ini | |
| done | WFL-3 | Pindahkan blueprint root ke `docs/` | `git mv` | referensi path diperbarui |
| done | REL-7 | Commit + uji penuh perubahan hardening | `docs/AUDIT_RILIS_2026-10-30.md` | commit `6e3022a`/`fa24284`/`3e8b5f8`/`7ec9176`; 456 test lulus |
| done | REL-11 | Hardening isolasi wilayah (RT, RW, personal) & fix runtime tagihan | `app/Models/Concerns/HasRtScope.php` | 459 test lulus |
| backlog | REL-8 | Uji pakai ponsel dan pengguna awam | `docs/AUDIT_RILIS_2026-10-30.md` | perlu perangkat + data nyata |
| review | REL-9 | Audit kesiapan server produksi | `docs/AUDIT_RILIS_2026-10-30.md` | baca-saja lokal selesai; sisanya butuh akses server |
| backlog | REL-10 | Seed wilayah nyata + admin produksi | `README.md`, `RestoreAdminSeeder` | butuh data wilayah + kredensial `SEED_ADMIN_*` dari pemilik |

## Aturan Update

- Pindahkan status sesuai progres nyata; jangan klaim `done` sebelum test/build lulus.
- Satu baris per tugas agar mudah dibaca agen dan manusia.
- Tambah referensi dokumen blueprint yang relevan di kolom Referensi.