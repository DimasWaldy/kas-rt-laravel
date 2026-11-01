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
| backlog | REL-7 | Commit + uji penuh perubahan hardening | `docs/AUDIT_RILIS_2026-10-30.md` | tunggu perintah commit |
| backlog | REL-8 | Uji pakai ponsel dan pengguna awam | `docs/AUDIT_RILIS_2026-10-30.md` | belum dicakup audit |
| backlog | REL-9 | Audit kesiapan server produksi | `docs/AUDIT_RILIS_2026-10-30.md` | belum dicakup audit |

## Aturan Update

- Pindahkan status sesuai progres nyata; jangan klaim `done` sebelum test/build lulus.
- Satu baris per tugas agar mudah dibaca agen dan manusia.
- Tambah referensi dokumen blueprint yang relevan di kolom Referensi.