# AI Coding Rules RT/RW

Dokumen ini adalah aturan teknis wajib untuk semua AI saat ngoding di repo aplikasi RT/RW ini. Baca bersama `ROADMAP.md`, `ROLE_BLUEPRINT.md`, `FEATURE_SPEC.md`, `DATABASE_BLUEPRINT.md`, dan `UI_FLOW.md`.

## 1. Urutan Baca Sebelum Ngoding

AI wajib membaca dokumen ini sesuai urutan:

1. `ROADMAP.md`
2. `ROLE_BLUEPRINT.md`
3. `FEATURE_SPEC.md`
4. `DATABASE_BLUEPRINT.md`
5. `UI_FLOW.md`
6. File kode yang relevan.

Jangan langsung membuat kode tanpa memahami modul, role, permission, dan data scope.

## 2. Prinsip Umum

- Ikuti pola Laravel yang sudah ada di project.
- Jangan membuat arsitektur baru jika pola lama cukup.
- Jangan menghapus perubahan user yang tidak terkait.
- Jangan mengganti nama route, permission, tabel, atau status lama tanpa alasan kuat.
- Perubahan harus kecil, jelas, dan bisa dites.
- Dokumentasi harus diperbarui jika ada standar baru.

## 3. Standar Penambahan Modul

Urutan implementasi modul:

1. Cek apakah modul/tabel/model sudah ada.
2. Tentukan role dan permission.
3. Tentukan data scope.
4. Buat migration.
5. Buat model dan relasi.
6. Update seeder permission.
7. Buat policy atau pengecekan ownership jika perlu.
8. Buat route dengan middleware.
9. Buat controller.
10. Buat validasi request.
11. Buat view Blade.
12. Update navigasi sesuai role.
13. Tambahkan feature test.
14. Jalankan test relevan.

Jangan mulai dari view jika struktur data dan otorisasi belum jelas.

## 4. Standar Route

Route wajib:

- Berada di group `auth` untuk fitur login.
- Memakai middleware `permission:*` untuk halaman pengurus.
- Memakai route name yang konsisten.
- Tidak membuka endpoint mutasi data untuk guest.

Contoh pola:

```php
Route::middleware(['auth', 'permission:manage-surat'])->group(function () {
    Route::get('/admin/surat', [SuratController::class, 'adminIndex'])->name('surat.admin');
});
```

Untuk warga:

```php
Route::middleware(['auth', 'permission:submit-surat'])->group(function () {
    Route::get('/surat/create', [SuratController::class, 'create'])->name('surat.create');
});
```

## 5. Standar Controller

Controller wajib:

- Validasi input.
- Cek ownership untuk data warga.
- Jangan terlalu gemuk jika logic mulai kompleks.
- Gunakan transaction database untuk proses uang atau multi tabel.
- Redirect dengan flash message setelah aksi berhasil/gagal.
- Return `403` jika user tidak berwenang.

Larangan:

- Jangan percaya data `user_id` dari request warga.
- Jangan update transaksi final tanpa audit/catatan.
- Jangan melakukan query `Model::all()` untuk data sensitif tanpa scope.

## 6. Standar Model dan Relasi

Model wajib:

- Mendefinisikan `fillable` atau guarded sesuai pola project.
- Mendefinisikan casts untuk boolean, date, datetime, integer.
- Mendefinisikan relasi `belongsTo`, `hasMany`, atau `belongsToMany`.
- Menggunakan accessor/helper hanya jika benar-benar membantu.

Nama relasi harus jelas:

- `user()`
- `rumah()`
- `creator()`
- `verifier()`
- `approver()`
- `items()`
- `attachments()`

## 7. Standar Migration

Migration wajib:

- Pakai foreign key jika relasi jelas.
- Pakai index untuk kolom filter umum: `user_id`, `status`, `rumah_id`, `bulan`, `tahun`, `created_at`.
- Uang pakai integer.
- File upload pakai string path.
- Status pakai string dengan default yang jelas.
- Tabel pengajuan punya kolom status dan timestamp proses.

Larangan:

- Jangan pakai float untuk uang.
- Jangan simpan file binary langsung di database.
- Jangan membuat nama tabel/kolom campur bahasa tanpa alasan. Gunakan pola yang sudah ada.

## 8. Standar Permission

Permission baru wajib:

- Ditambahkan di `RoleAndPermissionSeeder`.
- Dipetakan ke role yang tepat.
- Dipakai di route/middleware.
- Dipakai di view untuk menampilkan tombol/menu.
- Dites dengan minimal satu skenario boleh dan satu skenario tidak boleh.

Pola nama:

- `view-{modul}`
- `submit-{modul}`
- `manage-{modul}`
- `verify-{modul}`
- `approve-{modul}`
- `export-{modul}`

Jangan membuat permission yang artinya dobel dengan permission lama kecuali ada kebutuhan jelas.

## 9. Standar Data Scope

AI wajib menentukan scope:

- `own`: data user login.
- `household`: data rumah/KK.
- `rt`: data RT yang sama.
- `rw`: data RW yang sama.
- `public`: data aman untuk semua warga.
- `all`: admin/pengurus tertentu.

Contoh aturan:

- Warga melihat pengaduan sendiri.
- Warga melihat tagihan rumah sendiri.
- Warga melihat surat sendiri.
- Warga melihat UMKM sendiri untuk edit, tetapi katalog approved bisa publik.
- Pengurus melihat data sesuai permission.

## 10. Standar View Blade

View wajib:

- Mengikuti layout yang sudah ada.
- Menampilkan error validasi.
- Menampilkan flash message.
- Menyembunyikan tombol aksi yang tidak sesuai permission.
- Memakai route name, bukan hardcoded URL jika route tersedia.
- Menjaga tampilan tetap bisa dipakai di mobile.

Larangan:

- Jangan menampilkan data sensitif tanpa cek role.
- Jangan membuat menu ke fitur yang belum siap.
- Jangan membuat form upload tanpa `enctype="multipart/form-data"`.
- Jangan membuat tombol delete tanpa CSRF dan method spoofing.

## 11. Standar Validasi

Validasi wajib untuk:

- Input teks.
- Nominal uang.
- Tanggal.
- Status.
- Foreign key.
- File upload.

Aturan dasar:

- Nominal minimal 0 atau 1 sesuai konteks.
- Tanggal harus valid.
- Status harus masuk daftar yang diizinkan.
- File upload batasi tipe dan ukuran.
- Field user/rumah tidak boleh asal diambil dari request warga.

## 12. Standar File Upload

File upload wajib:

- Validasi tipe.
- Validasi ukuran.
- Simpan di storage yang sesuai.
- Simpan path di database.
- Batasi akses file sensitif.

File sensitif:

- Bukti pembayaran.
- Lampiran surat.
- Bukti pengaduan.
- Dokumen kesehatan.

## 13. Standar Transaksi Keuangan

Berlaku untuk kas, tagihan, bank sampah, rukem, koperasi.

Aturan:

- Gunakan integer untuk nominal.
- Gunakan database transaction untuk update multi tabel.
- Status harus jelas.
- Verifikasi harus menyimpan `verified_by` dan `verified_at` jika tabel mendukung.
- Laporan harus mengambil data dari transaksi valid saja.
- Jangan mengubah transaksi final tanpa catatan koreksi.

## 14. Standar Test

Minimal test:

- Guest tidak bisa akses fitur auth.
- Warga tidak bisa akses halaman pengurus.
- Role pengelola bisa akses.
- Warga hanya melihat data sendiri.
- Validasi gagal untuk input buruk.
- Aksi verify/approve hanya untuk role berwenang.

Test keuangan:

- Nominal valid.
- Pembayaran verified mengubah status.
- Kas/laporan berubah sesuai transaksi.
- Bukti pembayaran tidak bocor ke warga lain.

## 15. Standar Git dan File

AI wajib:

- Mengecek status kerja jika akan menyentuh file banyak.
- Tidak revert file yang tidak dibuat/diubah sendiri.
- Membatasi perubahan sesuai request.
- Menghindari refactor besar tanpa diminta.

Jangan:

- Menjalankan perintah destruktif.
- Menghapus file lama tanpa konfirmasi.
- Mengubah `.env` kecuali diminta.
- Commit/push kecuali diminta user.

## 16. Definisi Selesai Untuk AI

Sebelum final answer:

- Jelaskan file yang dibuat/diubah.
- Jelaskan fitur/aturan yang ditambahkan.
- Sebutkan test yang dijalankan.
- Jika test tidak dijalankan, jelaskan alasannya.
- Jika ada risiko atau todo, sebutkan singkat.

AI berikutnya wajib memperlakukan dokumen ini sebagai kontrak kerja saat ngoding aplikasi RT/RW.
