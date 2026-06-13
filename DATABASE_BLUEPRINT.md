# Database Blueprint RT/RW

Dokumen ini adalah rancangan database untuk pengembangan modul aplikasi RT/RW. Semua migration baru wajib mengikuti `ROADMAP.md`, `ROLE_BLUEPRINT.md`, dan dokumen ini.

## 1. Prinsip Database

- Gunakan nama tabel plural dan snake_case.
- Gunakan foreign key jika relasi jelas.
- Gunakan `nullable` hanya jika memang data boleh belum ada.
- Gunakan enum/string status yang konsisten dengan roadmap.
- Setiap tabel transaksi atau pengajuan harus punya status.
- Setiap tabel penting pakai `timestamps`.
- Soft delete dipakai untuk data master penting jika penghapusan permanen berisiko.
- File upload simpan path, bukan binary file di database.
- Data uang gunakan integer dalam rupiah, bukan float.

## 2. Tabel yang Sudah Ada atau Dianggap Inti

Tabel inti yang sudah menjadi dasar sistem:

- `users`
- `roles`
- `permissions`
- `role_permission`
- `rumahs`
- `kas_masuks`
- `kas_keluars`
- `tagihans`
- `iuran_bulanans`
- `pengaduans`

AI wajib cek migration/model yang sudah ada sebelum membuat tabel baru.

## 3. Standar Kolom Umum

Kolom umum untuk tabel pengajuan:

- `id`
- `user_id`
- `status`
- `submitted_at`
- `verified_by`
- `verified_at`
- `approved_by`
- `approved_at`
- `rejected_reason`
- `created_at`
- `updated_at`

Kolom umum untuk tabel master:

- `id`
- `name`
- `description`
- `is_active`
- `created_at`
- `updated_at`

Kolom umum untuk tabel uang:

- `id`
- `user_id`
- `amount`
- `transaction_date`
- `status`
- `note`
- `created_by`
- `verified_by`
- `verified_at`
- `created_at`
- `updated_at`

<!-- BARU: Struktur wilayah RW/RT dan kolom scope untuk migrasi Smart RW -->
## 3A. Modul Wilayah (RW dan RT)

### `rws`

- `id`
- `name` (contoh: `RW 05`)
- `description`
- `address`
- `kelurahan`
- `kecamatan`
- `kota`
- `is_active`
- `created_at`
- `updated_at`

### `rts`

- `id`
- `rw_id` (foreign key ke `rws.id`)
- `name` (contoh: `RT 01`)
- `description`
- `is_active`
- `created_at`
- `updated_at`

Relasi:

- `rts.rw_id` -> `rws.id`

### Kolom Baru pada Tabel yang Sudah Ada

Kolom berikut ditambahkan melalui migration baru, bukan dengan mengubah migration lama:

Tabel `users`:

- `rt_id` (nullable, foreign key ke `rts.id`) untuk menentukan warga atau pengurus ini terhubung ke RT mana.

Tabel `rumahs`:

- `rt_id` (nullable, foreign key ke `rts.id`) untuk menentukan rumah ini berada di RT mana.

Tabel `tagihans`:

- `rt_id` (nullable, foreign key ke `rts.id`) untuk menentukan tagihan ini milik RT mana.

Tabel `kas_masuks`:

- `rt_id` (nullable, foreign key ke `rts.id`) untuk menentukan kas masuk ini dicatat untuk RT mana.

Tabel `kas_keluars`:

- `rt_id` (nullable, foreign key ke `rts.id`) untuk menentukan kas keluar ini dicatat untuk RT mana.

### Catatan Desain

- Semua kolom `rt_id` dibuat nullable agar data lama sebelum migrasi RT tidak rusak.
- Data lama secara default dianggap berada di RT 1 atau di-assign secara manual melalui proses migrasi data.
- Query pengurus RT wajib memakai filter `WHERE rt_id = auth()->user()->rt_id`.
- Query rekap RW tidak perlu membatasi satu `rt_id`; query mengambil semua RT yang berelasi dengan `rw_id` deployment aktif.
- Kas dan tagihan tetap merupakan data operasional per RT. Level RW hanya melakukan monitoring dan rekap lintas RT.

## 4. Modul Surat Menyurat

### `surats`

- `id`
- `user_id`
- `surat_number`
- `type`
- `subject`
- `purpose`
- `content`
- `status`
- `submitted_at`
- `verified_by`
- `verified_at`
- `approved_by`
- `approved_at`
- `rejected_reason`
- `result_file`
- `created_at`
- `updated_at`

Relasi:

- `surats.user_id` -> `users.id`
- `surats.verified_by` -> `users.id`
- `surats.approved_by` -> `users.id`

### `surat_attachments`

- `id`
- `surat_id`
- `file_path`
- `file_name`
- `file_type`
- `file_size`
- `created_at`
- `updated_at`

Relasi:

- `surat_attachments.surat_id` -> `surats.id`

## 5. Modul Kegiatan

### `kegiatans`

- `id`
- `title`
- `description`
- `start_at`
- `end_at`
- `location`
- `person_in_charge_id`
- `budget_amount`
- `status`
- `created_by`
- `created_at`
- `updated_at`

Relasi:

- `person_in_charge_id` -> `users.id`
- `created_by` -> `users.id`

### `kegiatan_pesertas`

- `id`
- `kegiatan_id`
- `user_id`
- `status`
- `registered_at`
- `created_at`
- `updated_at`

Relasi:

- `kegiatan_id` -> `kegiatans.id`
- `user_id` -> `users.id`

### `kegiatan_dokumentasis`

- `id`
- `kegiatan_id`
- `file_path`
- `caption`
- `uploaded_by`
- `created_at`
- `updated_at`

## 6. Modul Aset

### `asets`

- `id`
- `asset_code`
- `name`
- `category`
- `description`
- `quantity`
- `unit`
- `condition`
- `location`
- `estimated_value`
- `status`
- `created_by`
- `created_at`
- `updated_at`
- `deleted_at`

### `aset_peminjamans`

- `id`
- `aset_id`
- `user_id`
- `quantity`
- `borrow_date`
- `return_date`
- `actual_return_date`
- `purpose`
- `status`
- `approved_by`
- `approved_at`
- `rejected_reason`
- `created_at`
- `updated_at`

### `aset_maintenances`

- `id`
- `aset_id`
- `maintenance_date`
- `description`
- `cost`
- `handled_by`
- `created_at`
- `updated_at`

## 7. Modul Bank Sampah

### `bank_sampah_items`

- `id`
- `name`
- `category`
- `unit`
- `price_per_unit`
- `is_active`
- `created_at`
- `updated_at`

### `bank_sampah_setorans`

- `id`
- `user_id`
- `item_id`
- `weight`
- `price_per_unit`
- `total_amount`
- `status`
- `submitted_at`
- `verified_by`
- `verified_at`
- `rejected_reason`
- `created_at`
- `updated_at`

### `bank_sampah_saldos`

- `id`
- `user_id`
- `balance`
- `created_at`
- `updated_at`

Catatan:

- `total_amount` dihitung dari `weight * price_per_unit`.
- Saldo bertambah hanya setelah setoran `verified`.

## 8. Modul UMKM

### `umkms`

- `id`
- `user_id`
- `business_name`
- `category`
- `description`
- `phone`
- `address`
- `logo_path`
- `status`
- `approved_by`
- `approved_at`
- `rejected_reason`
- `created_at`
- `updated_at`

### `umkm_products`

- `id`
- `umkm_id`
- `name`
- `description`
- `price`
- `image_path`
- `is_active`
- `created_at`
- `updated_at`

## 9. Modul Posyandu

### `posyandu_jadwals`

- `id`
- `title`
- `date`
- `start_time`
- `end_time`
- `location`
- `description`
- `created_by`
- `created_at`
- `updated_at`

### `posyandu_pesertas`

- `id`
- `user_id`
- `name`
- `family_relation`
- `birth_date`
- `gender`
- `created_at`
- `updated_at`

### `posyandu_pemeriksaans`

- `id`
- `jadwal_id`
- `peserta_id`
- `weight`
- `height`
- `blood_pressure`
- `notes`
- `checked_by`
- `created_at`
- `updated_at`

Data kesehatan wajib dibatasi aksesnya.

## 10. Modul Keamanan/Ronda

### `ronda_jadwals`

- `id`
- `date`
- `shift`
- `location`
- `notes`
- `created_by`
- `created_at`
- `updated_at`

### `ronda_petugas`

- `id`
- `jadwal_id`
- `user_id`
- `role_note`
- `created_at`
- `updated_at`

### `ronda_presensis`

- `id`
- `jadwal_id`
- `user_id`
- `status`
- `checked_in_at`
- `notes`
- `created_at`
- `updated_at`

### `ronda_laporans`

- `id`
- `jadwal_id`
- `reported_by`
- `title`
- `description`
- `status`
- `verified_by`
- `verified_at`
- `created_at`
- `updated_at`

## 11. Modul Rukem

### `rukem_members`

- `id`
- `user_id`
- `joined_at`
- `status`
- `created_at`
- `updated_at`

### `rukem_iurans`

- `id`
- `user_id`
- `period_month`
- `period_year`
- `amount`
- `status`
- `paid_at`
- `verified_by`
- `verified_at`
- `created_at`
- `updated_at`

### `rukem_claims`

- `id`
- `user_id`
- `deceased_name`
- `relationship`
- `death_date`
- `description`
- `status`
- `approved_by`
- `approved_at`
- `rejected_reason`
- `created_at`
- `updated_at`

### `rukem_bantuans`

- `id`
- `claim_id`
- `amount`
- `given_at`
- `given_by`
- `note`
- `created_at`
- `updated_at`

## 12. Modul Koperasi

### `koperasi_members`

- `id`
- `user_id`
- `member_number`
- `joined_at`
- `status`
- `created_at`
- `updated_at`

### `koperasi_simpanans`

- `id`
- `user_id`
- `type`
- `amount`
- `transaction_date`
- `status`
- `verified_by`
- `verified_at`
- `created_at`
- `updated_at`

### `koperasi_pinjams`

- `id`
- `user_id`
- `amount`
- `tenor_months`
- `service_fee`
- `remaining_amount`
- `status`
- `approved_by`
- `approved_at`
- `rejected_reason`
- `created_at`
- `updated_at`

### `koperasi_angsurans`

- `id`
- `pinjam_id`
- `amount`
- `paid_at`
- `status`
- `verified_by`
- `verified_at`
- `created_at`
- `updated_at`

## 13. Audit Trail

Jika audit dibuat, gunakan tabel:

### `audit_logs`

- `id`
- `user_id`
- `role_name`
- `module`
- `action`
- `target_type`
- `target_id`
- `old_values`
- `new_values`
- `ip_address`
- `user_agent`
- `created_at`

Tabel ini dipakai untuk aksi penting: transaksi, role, warga, surat, approval, export, dan penghapusan data.

## 14. Prioritas Migration

Urutan aman:

1. Tambah permission di seeder.
2. Buat tabel master sederhana.
3. Buat tabel pengajuan/transaksi.
4. Tambah relasi dan index.
5. Tambah model dan factory jika test membutuhkan.
6. Tambah test akses dan alur utama.

AI tidak boleh membuat migration baru tanpa mengecek apakah tabel/kolom serupa sudah ada.
