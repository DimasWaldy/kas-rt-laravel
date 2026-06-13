# Blueprint Sistem Role RT/RW

Dokumen ini adalah aturan standar AI dan developer untuk membangun, menambah, atau mengubah fitur pada aplikasi RT/RW ini. Setiap modul baru wajib mengikuti blueprint ini agar otorisasi, validasi, data scope, dan audit tetap konsisten.

## 1. Tujuan

- Menetapkan standar role dan permission untuk sistem RT/RW.
- Menjadi acuan AI saat membuat controller, route, view, policy, seeder, test, dan dokumentasi fitur.
- Mencegah akses berlebih dengan prinsip least privilege.
- Menjaga data warga, kas, aset, surat, pengaduan, dan informasi sosial tetap aman.
- Memudahkan ekspansi fitur UAS: aset, kas RT, bank sampah, UMKM, posyandu, surat menyurat, keamanan/ronda, warga, rukem, koperasi, aspirasi/pengaduan, dan kegiatan.

## 2. Kondisi Sistem Saat Ini

<!-- BARU: Status transisi aplikasi dari Kas RT menuju Smart RW -->
Aplikasi sedang bertransisi dari "Kas RT" menjadi "Smart RW", yaitu sistem informasi untuk satu RW yang menaungi banyak RT. Satu deployment tetap hanya melayani satu RW, sedangkan pemisahan dan pembatasan data operasional dilakukan per RT.

Role aktif lama berikut tetap dipertahankan untuk backward compatibility dengan kode, middleware, seeder, dan test yang sudah ada:

Role aktif yang sudah dipakai aplikasi:

- `admin`: pengurus utama dengan akses penuh.
- `bendahara`: pengurus keuangan.
- `sekretaris`: pengurus administrasi, warga, surat, dan pengaduan.
- `warga`: pengguna warga biasa.

<!-- BARU: Role hierarkis Smart RW yang akan diaktifkan -->
Role baru yang akan diaktifkan dalam transisi Smart RW:

- `super_admin`: developer/god mode dengan akses penuh ke semua data RT dan RW tanpa filter wilayah.
- `ketua_rw`: monitoring lintas RT, approval surat level RW, dan rekap RW.
- `sekretaris_rw`: administrasi level RW dan pengelolaan kegiatan RW.
- `bendahara_rw`: rekap keuangan lintas RT tanpa mengelola kas RT secara langsung.
- `ketua_rt`: approval surat level RT dan validasi pengaduan pada RT-nya.
- `sekretaris_rt`: fungsi `sekretaris` yang sudah ada, tetapi di-scope ke RT tertentu.
- `bendahara_rt`: fungsi `bendahara` yang sudah ada, tetapi di-scope ke RT tertentu.

Selama masa transisi, role lama dan role baru boleh hidup berdampingan. Implementasi baru harus mengarah ke role hierarkis dan scope wilayah, tanpa memutus akses fitur lama sebelum migrasi role selesai.

Permission aktif yang sudah ada:

- `admin-only`: akses khusus admin utama.
- `manage-finance`: mengelola kas, iuran, dan tagihan.
- `manage-warga`: mengelola data warga dan rumah.
- `manage-pengaduan`: mengelola tanggapan pengaduan warga.
- `view-dashboard`: melihat dashboard.
- `view-finance`: melihat laporan kas dan tagihan.
- `submit-payment`: mengajukan pembayaran tagihan.
- `submit-pengaduan`: membuat pengaduan warga.

AI wajib mempertahankan kompatibilitas permission di atas. Jika menambah fitur, tambahkan permission baru secara granular, jangan mengganti nama permission lama tanpa migrasi yang jelas.

## 3. Prinsip Role

- Role menjawab "siapa pengguna ini".
- Permission menjawab "aksi apa yang boleh dilakukan".
- Route dan controller harus mengecek permission, bukan hanya nama role, kecuali akses benar-benar khusus seperti `admin-only`.
- `admin` boleh memiliki semua permission, tetapi fitur tetap harus memakai middleware/policy agar pola akses konsisten.
- `warga` hanya boleh melihat dan mengubah data miliknya sendiri, rumahnya sendiri, atau data publik yang memang dirancang untuk warga.
- Pengurus boleh melihat data sesuai bidangnya, tetapi tidak otomatis boleh menghapus data sensitif.

## 4. Role Standar

<!-- BARU: Role aktif Smart RW dan pemetaan role legacy -->
| Role         | Tanggung Jawab                                                                                 | Batasan Utama                                                            |
| ------------ | ---------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------ |
| `super_admin` | Akses penuh semua data semua RT dan RW, dipakai developer/operator sistem                     | Tetap mengikuti validasi bisnis dan audit untuk aksi sensitif            |
| `admin`      | Konfigurasi sistem, dashboard utama, manajemen role, user, modul lintas bidang                 | Tidak boleh dipakai sebagai alasan melewati validasi data                |
| `bendahara`  | Kas RT, tagihan, iuran, koperasi, bank sampah bernilai uang, laporan keuangan                  | Tidak mengelola identitas warga kecuali data pendukung transaksi         |
| `sekretaris` | Warga, rumah, surat menyurat, posyandu, kegiatan, pengaduan, administrasi RT/RW                | Tidak mengubah transaksi keuangan final kecuali diberi permission khusus |
| `ketua_rw` | Monitoring lintas RT, approval surat level RW, rekap warga, kegiatan, dan laporan strategis RW | Tidak mengelola transaksi kas RT secara langsung tanpa permission khusus |
| `sekretaris_rw` | Administrasi level RW, surat level RW, dan kegiatan RW                                    | Akses operasional RT hanya untuk kebutuhan rekap atau permission khusus  |
| `bendahara_rw` | Rekap dan monitoring keuangan lintas RT                                                    | Tidak membuat atau mengubah kas RT secara langsung                        |
| `ketua_rt` | Approval surat level RT, validasi pengaduan, kegiatan, aset, dan laporan strategis RT-nya       | Hanya mengakses data RT sendiri                                           |
| `sekretaris_rt` | Administrasi warga, rumah, surat, kegiatan, dan pengaduan pada RT tertentu                 | Hanya mengakses data RT sendiri; ekuivalen scoped dari `sekretaris`       |
| `bendahara_rt` | Kas, tagihan, iuran, dan laporan keuangan pada RT tertentu                                  | Hanya mengakses data RT sendiri; ekuivalen scoped dari `bendahara`        |
| `warga`      | Melihat profil, tagihan, kas publik, kegiatan, mengirim pembayaran, surat, pengaduan, aspirasi | Hanya data sendiri/keluarga/rumah, bukan data warga lain                 |

Role opsional untuk pengembangan berikutnya:

- `petugas_ronda`: jadwal ronda, presensi ronda, laporan keamanan.
- `kader_posyandu`: data posyandu, jadwal, hasil pemeriksaan, rekap kesehatan.
- `pengelola_umkm`: data UMKM, produk, katalog, status verifikasi.
- `pengelola_bank_sampah`: setoran sampah, harga jenis sampah, saldo warga.
- `pengelola_aset`: inventaris, peminjaman, pengembalian, kondisi aset.
- `pengurus_koperasi`: simpan pinjam, transaksi koperasi, laporan anggota.

Catatan: role opsional jangan dibuat jika modulnya belum membutuhkan pemisahan tanggung jawab. Untuk UAS, role legacy `admin`, `bendahara`, `sekretaris`, dan `warga` tetap dipertahankan, sedangkan `super_admin`, role pengurus RW, dan role pengurus RT menjadi role aktif dalam migrasi Smart RW.

## 5. Pola Permission

Gunakan pola nama permission:

```text
view-{modul}
create-{modul}
update-{modul}
delete-{modul}
manage-{modul}
approve-{modul}
export-{modul}
submit-{modul}
verify-{modul}
```

Aturan pemakaian:

- `view-*`: melihat daftar/detail.
- `create-*`: membuat data baru oleh pengurus.
- `submit-*`: pengajuan dari warga.
- `update-*`: mengubah data.
- `delete-*`: menghapus data, harus dibatasi.
- `manage-*`: paket pengelolaan penuh untuk modul tertentu.
- `approve-*`: menyetujui atau menolak pengajuan.
- `verify-*`: memverifikasi bukti/hasil tanpa selalu menyetujui final.
- `export-*`: mengunduh laporan atau rekap.

Jika modul sederhana, gunakan `manage-{modul}`. Jika modul sensitif atau banyak alur persetujuan, pisahkan menjadi permission granular.

## 6. Permission Modul UAS

| Modul              | Permission Minimum                                                                                         | Pemilik Utama                               |
| ------------------ | ---------------------------------------------------------------------------------------------------------- | ------------------------------------------- |
| Dashboard          | `view-dashboard`, `admin-only`                                                                             | Semua login, admin untuk dashboard pengurus |
| Warga dan Rumah    | `view-warga`, `manage-warga`, `export-warga`                                                               | Sekretaris, admin                           |
| Kas RT             | `view-finance`, `manage-finance`, `export-finance`                                                         | Bendahara, admin                            |
| Tagihan/Iuran      | `submit-payment`, `verify-payment`, `manage-finance`                                                       | Bendahara, warga                            |
| Laporan Kas        | `view-finance`, `export-finance`                                                                           | Bendahara, admin, warga terbatas            |
| Aset               | `view-aset`, `manage-aset`, `approve-aset`, `export-aset`                                                  | Admin, pengelola aset, sekretaris           |
| Bank Sampah        | `view-bank-sampah`, `submit-bank-sampah`, `manage-bank-sampah`, `verify-bank-sampah`, `export-bank-sampah` | Pengelola bank sampah, bendahara            |
| UMKM               | `view-umkm`, `submit-umkm`, `manage-umkm`, `approve-umkm`                                                  | Warga, sekretaris, pengelola UMKM           |
| Posyandu           | `view-posyandu`, `manage-posyandu`, `export-posyandu`                                                      | Kader posyandu, sekretaris                  |
| Surat Menyurat     | `submit-surat`, `view-surat`, `manage-surat`, `approve-surat`, `export-surat`                              | Warga, sekretaris, ketua RT/RW              |
| Keamanan/Ronda     | `view-ronda`, `manage-ronda`, `submit-ronda`, `verify-ronda`                                               | Petugas ronda, sekretaris                   |
| Rukem              | `view-rukem`, `manage-rukem`, `submit-rukem`, `approve-rukem`, `export-rukem`                              | Sekretaris, bendahara, admin                |
| Koperasi           | `view-koperasi`, `submit-koperasi`, `manage-koperasi`, `approve-koperasi`, `export-koperasi`               | Bendahara, pengurus koperasi                |
| Aspirasi/Pengaduan | `submit-pengaduan`, `manage-pengaduan`, `view-pengaduan`                                                   | Warga, sekretaris, admin                    |
| Kegiatan           | `view-kegiatan`, `manage-kegiatan`, `submit-kegiatan`, `approve-kegiatan`                                  | Sekretaris, admin, warga                    |

Rukem di blueprint ini diasumsikan sebagai Rukun Kematian. Jika istilah lokal berbeda, nama modul boleh tetap `rukem`, tetapi deskripsi bisnisnya harus disesuaikan di dokumen fitur.

## 7. Matriks Akses Ringkas

| Modul              | Admin | Bendahara                     | Sekretaris                  | Warga                                  |
| ------------------ | ----- | ----------------------------- | --------------------------- | -------------------------------------- |
| Dashboard pengurus | Penuh | Bidang keuangan               | Bidang administrasi         | Tidak                                  |
| Profil sendiri     | Penuh | Sendiri                       | Sendiri                     | Sendiri                                |
| Warga dan rumah    | Penuh | Lihat pendukung transaksi     | Penuh                       | Lihat/edit profil sendiri              |
| Kas RT             | Penuh | Penuh                         | Lihat rekap jika dibutuhkan | Lihat ringkasan publik/riwayat sendiri |
| Tagihan/iuran      | Penuh | Penuh                         | Lihat status                | Bayar dan lihat tagihan sendiri/rumah  |
| Aset               | Penuh | Lihat nilai aset              | Kelola administrasi         | Lihat aset publik/pinjam jika tersedia |
| Bank sampah        | Penuh | Kelola nilai/saldo            | Lihat administrasi          | Setor dan lihat saldo sendiri          |
| UMKM               | Penuh | Lihat transaksi jika ada      | Verifikasi data UMKM        | Daftar dan kelola UMKM sendiri         |
| Posyandu           | Penuh | Tidak default                 | Kelola/jadwal/rekap         | Lihat jadwal dan data keluarga sendiri |
| Surat menyurat     | Penuh | Tidak default                 | Penuh administrasi          | Ajukan dan lihat surat sendiri         |
| Keamanan/ronda     | Penuh | Tidak default                 | Jadwal dan rekap            | Lihat jadwal/lapor kejadian            |
| Rukem              | Penuh | Kelola dana rukem             | Kelola data/kejadian        | Ajukan/lapor dan lihat status sendiri  |
| Koperasi           | Penuh | Penuh                         | Lihat anggota jika perlu    | Ajukan dan lihat transaksi sendiri     |
| Pengaduan/aspirasi | Penuh | Tanggapi jika bidang keuangan | Penuh penanganan            | Ajukan dan lihat milik sendiri         |
| Kegiatan           | Penuh | Lihat anggaran                | Kelola kegiatan             | Lihat/daftar/usul kegiatan             |

## 8. Data Scope

AI wajib menentukan scope data sebelum membuat query.

- Scope `own`: data milik user login, contoh pengaduan sendiri, surat sendiri, pembayaran sendiri.
- Scope `household`: data satu rumah/KK, contoh tagihan rumah, data keluarga, posyandu keluarga.
- Scope `rt`: data dalam RT yang sama.
- Scope `rw`: data lintas RT dalam RW yang sama.
- Scope `public`: data yang aman dilihat semua warga, contoh kegiatan umum atau ringkasan kas.
- Scope `all`: hanya untuk admin atau permission khusus.

Aturan query:

- Jangan tampilkan seluruh data warga ke role `warga`.
- Jangan tampilkan nomor HP, alamat lengkap, NIK, KK, bukti pembayaran, dan dokumen surat kepada user yang tidak berwenang.
- Untuk warga, filter minimal memakai `user_id`, `rumah_id`, `no_kk`, `rt`, atau `rw` sesuai konteks fitur.
- Untuk pengurus RT/RW, filter wilayah jika sistem sudah mendukung multi RT/RW.

<!-- BARU: Aturan eksplisit scope wilayah Smart RW -->
Aturan scope wilayah Smart RW:

- Pengurus RT hanya bisa mengakses data RT-nya sendiri dan query wajib difilter dengan `rt_id = auth()->user()->rt_id`.
- Pengurus RW bisa mengakses monitoring dan rekap semua RT dalam RW-nya, dengan data dibatasi berdasarkan `rw_id` deployment aktif.
- `super_admin` tidak diberi filter wilayah dan dapat mengakses seluruh data dalam sistem.
- `warga` hanya bisa mengakses data RT-nya sendiri yang terkait dirinya/rumahnya, ditambah data yang berstatus publik.
- Kas, tagihan, kas masuk, dan kas keluar tetap dimiliki serta dikelola per RT. Pengurus RW hanya memonitor dan merekap lintas RT.

<!-- BARU: Hierarki wilayah dan aktor Smart RW -->
## 8A. Hierarki Wilayah

Satu deployment Smart RW melayani tepat satu RW dengan satu atau lebih RT. Hierarki aksesnya adalah:

```text
Super Admin
└── RW (1 RW dalam sistem ini)
    ├── Pengurus RW (ketua_rw, sekretaris_rw, bendahara_rw)
    └── RT 1..N
        ├── Pengurus RT (ketua_rt, sekretaris_rt, bendahara_rt)
        └── Warga RT
```

Hierarki ini adalah batas otorisasi dan pelaporan, bukan model multi-tenant banyak RW. Pengurus RW memiliki fungsi monitoring, administrasi RW, dan rekap lintas RT; pengelolaan kas dan tagihan tetap berada di masing-masing RT.

## 9. Aturan Standar AI Saat Membuat Fitur

Sebelum membuat route/controller/view untuk fitur baru, AI harus menjawab:

1. Modul apa yang sedang dibuat?
2. Siapa aktor utama: warga, sekretaris, bendahara, admin, atau role opsional?
3. Permission apa yang dibutuhkan untuk setiap aksi?
4. Data scope apa yang berlaku untuk daftar dan detail?
5. Field apa yang sensitif?
6. Apakah ada alur pengajuan, verifikasi, persetujuan, penolakan, atau audit?
7. Apakah perlu test akses role?

Standar implementasi:

- Route admin/pengurus wajib memakai middleware `auth` dan `permission:*`.
- Aksi mutasi data wajib validasi request.
- Aksi sensitif wajib memakai policy/gate atau pengecekan permission eksplisit.
- View hanya menampilkan tombol/aksi yang sesuai permission.
- Controller tetap harus mengecek permission walaupun tombol disembunyikan di view.
- Seeder role dan permission wajib diperbarui saat permission baru dibuat.
- Test minimal memastikan warga tidak bisa mengakses halaman pengurus.

## 10. Alur Otorisasi Standar

1. Request masuk.
2. Cek `auth`.
3. Ambil `user`, `role`, dan permission.
4. Tentukan permission yang dibutuhkan.
5. Tentukan data scope.
6. Jalankan query sesuai scope.
7. Validasi input.
8. Jalankan aksi.
9. Catat audit untuk aksi penting.
10. Kembalikan response sesuai role.

Jika role atau permission tidak sesuai, sistem harus menolak akses dengan `403`, bukan menampilkan data kosong yang membingungkan.

## 11. Standar Modul Keuangan

Berlaku untuk kas RT, kas masuk, kas keluar, tagihan, iuran, koperasi, bank sampah bernilai saldo, dan rukem jika memiliki dana.

- `bendahara` dan `admin` boleh mengelola transaksi.
- `warga` boleh mengirim pembayaran/pengajuan dan melihat riwayat sendiri.
- Transaksi terverifikasi tidak boleh diubah sembarangan; gunakan koreksi, pembatalan, atau catatan audit.
- Bukti pembayaran hanya boleh dilihat pengurus keuangan dan pemilik pembayaran.
- Laporan untuk warga harus berupa ringkasan publik, bukan data sensitif per warga lain.
- Export laporan hanya untuk role pengurus dengan `export-*`.

## 12. Standar Modul Administrasi Warga

Berlaku untuk warga, rumah, surat menyurat, posyandu, ronda, kegiatan, UMKM, dan pengaduan.

- `sekretaris` dan `admin` menjadi pengelola utama.
- `warga` boleh membuat pengajuan dan melihat status miliknya.
- Data keluarga, kesehatan, dokumen surat, dan kontak pribadi termasuk data sensitif.
- Setiap perubahan status pengajuan harus menyimpan siapa pengubahnya dan kapan diubah.
- Status yang disarankan: `draft`, `submitted`, `verified`, `approved`, `rejected`, `done`, `cancelled`.

## 13. Standar Audit

Aksi yang wajib diaudit:

- Login pengurus jika audit tersedia.
- Membuat, mengubah, menghapus data warga.
- Membuat, memverifikasi, mengubah, atau membatalkan transaksi.
- Menyetujui/menolak surat, pengaduan, koperasi, rukem, peminjaman aset, dan kegiatan.
- Export laporan.
- Perubahan role dan permission.

Minimal data audit:

- `user_id`
- `role_name`
- `action`
- `module`
- `target_type`
- `target_id`
- `old_values`
- `new_values`
- `ip_address`
- `user_agent`
- `created_at`

## 14. Standar Penamaan Modul

Gunakan slug permission dan route yang konsisten:

| Nama Modul         | Slug                 |
| ------------------ | -------------------- |
| Aset               | `aset`               |
| Kas RT             | `finance` atau `kas` |
| Bank Sampah        | `bank-sampah`        |
| UMKM               | `umkm`               |
| Posyandu           | `posyandu`           |
| Surat Menyurat     | `surat`              |
| Keamanan/Ronda     | `ronda`              |
| Warga              | `warga`              |
| Rumah/KK           | `rumah`              |
| Rukem              | `rukem`              |
| Koperasi           | `koperasi`           |
| Aspirasi/Pengaduan | `pengaduan`          |
| Kegiatan           | `kegiatan`           |

Untuk sistem saat ini, `finance` tetap dipakai karena sudah ada `manage-finance` dan `view-finance`.

## 15. Checklist Penambahan Modul Baru

- Tambahkan migration/model sesuai kebutuhan.
- Tambahkan permission di `RoleAndPermissionSeeder`.
- Mapping permission ke role yang sesuai.
- Tambahkan middleware permission di route.
- Tambahkan validasi request.
- Tambahkan policy/gate jika ada data ownership atau alur approval.
- Tambahkan filter data scope di query.
- Sembunyikan tombol aksi yang tidak boleh diakses role tersebut.
- Tambahkan test akses minimal untuk `admin`, role pengelola, dan `warga`.
- Perbarui blueprint jika ada role atau permission baru yang menjadi standar.

Dengan blueprint ini, AI harus bertindak sebagai penjaga standar akses aplikasi RT/RW: setiap fitur baru boleh berkembang, tetapi pola role, permission, scope data, validasi, dan audit harus tetap konsisten.
