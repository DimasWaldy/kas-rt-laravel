# Feature Specification RT/RW

Dokumen ini adalah spesifikasi fitur untuk aplikasi RT/RW. Semua AI dan developer wajib membaca `ROADMAP.md`, `ROLE_BLUEPRINT.md`, lalu dokumen ini sebelum membuat modul baru.

## 1. Aturan Umum Spesifikasi Fitur

Setiap fitur wajib punya:

- Tujuan fitur.
- Aktor utama.
- Permission.
- Data utama dan relasi.
- Status data.
- Alur warga.
- Alur pengurus.
- Halaman yang dibutuhkan.
- Validasi penting.
- Data sensitif.
- Test minimal.

Role dasar:

- `admin`: akses penuh lintas modul.
- `bendahara`: modul keuangan.
- `sekretaris`: modul administrasi.
- `warga`: akses data pribadi/rumah/publik.

## 2. Modul Warga dan Rumah

Tujuan:

- Mengelola data warga, kepala keluarga, rumah/unit, RT/RW, dan penanggung jawab iuran.

Aktor:

- `admin`
- `sekretaris`
- `warga`

Permission:

- `manage-warga`
- `view-warga`
- `export-warga`

Data utama:

- `users`
- `roles`
- `rumahs`

Field penting:

- Nama, email, phone, no KK, rumah, RT, RW, status kepala keluarga, status penanggung jawab rumah, jumlah anggota keluarga.

Status:

- `Lengkap`
- `Belum Lengkap`

Alur:

1. Warga registrasi/login.
2. Warga melengkapi profil.
3. Sekretaris/admin memvalidasi data jika diperlukan.
4. Data rumah menjadi dasar tagihan.

Halaman:

- Profil warga.
- Admin daftar warga.
- Admin tambah/edit warga.
- Admin daftar rumah.
- Admin detail rumah.
- Admin edit rumah.

Data sensitif:

- Phone, no KK, alamat/rumah, data keluarga.

Test minimal:

- Warga tidak bisa akses `/admin/warga`.
- Sekretaris/admin bisa mengelola warga.
- Warga hanya bisa edit profil sendiri.

## 3. Modul Kas RT, Iuran, Tagihan, dan Laporan Kas

Tujuan:

- Mengelola kas masuk, kas keluar, tagihan bulanan, pembayaran warga, dan laporan saldo.

Aktor:

- `admin`
- `bendahara`
- `warga`
- `sekretaris` untuk lihat terbatas jika dibutuhkan.

Permission:

- `view-finance`
- `manage-finance`
- `submit-payment`
- `verify-payment`
- `export-finance`

Data utama:

- `kas_masuks`
- `kas_keluars`
- `tagihans`
- `iuran_bulanans`
- `users`
- `rumahs`

Field penting:

- Nominal, tanggal, bulan, tahun, rumah, user, status pembayaran, metode pembayaran, bukti pembayaran, catatan verifikasi.

Status tagihan:

- `unpaid`
- `pending_transfer`
- `pending_offline`
- `lunas`
- `failed`

Alur warga:

1. Warga membuka daftar tagihan.
2. Warga memilih metode pembayaran.
3. Warga mengirim bukti atau konfirmasi bayar tunai.
4. Warga melihat status pembayaran.

Alur bendahara:

1. Bendahara melihat tagihan pending.
2. Bendahara memverifikasi/menolak pembayaran.
3. Jika valid, sistem mencatat kas masuk.
4. Bendahara melihat laporan kas.

Halaman:

- Daftar tagihan warga.
- Form bayar tagihan.
- Admin tagihan.
- Form generate iuran/tagihan.
- Kas masuk.
- Kas keluar.
- Laporan kas.

Data sensitif:

- Bukti pembayaran, nominal per warga, catatan transaksi.

Test minimal:

- Warga hanya melihat tagihan rumahnya.
- Bendahara bisa verifikasi.
- Warga tidak bisa akses admin tagihan.
- Pembayaran lunas masuk ke laporan kas.

## 4. Modul Aspirasi dan Pengaduan

Tujuan:

- Menampung laporan, aspirasi, keluhan, dan tindak lanjut dari warga.

Aktor:

- `warga`
- `sekretaris`
- `admin`
- `bendahara` jika pengaduan terkait keuangan.

Permission:

- `submit-pengaduan`
- `manage-pengaduan`
- `view-pengaduan`

Data utama:

- `pengaduans`
- `users`

Field penting:

- Kategori, judul, isi laporan, foto/bukti, status, tanggapan, petugas penanggap.

Status:

- `submitted`
- `verified`
- `in_progress`
- `done`
- `rejected`

Alur warga:

1. Warga membuat pengaduan.
2. Warga melihat status dan tanggapan.

Alur pengurus:

1. Sekretaris/admin melihat daftar pengaduan.
2. Pengurus mengubah status.
3. Pengurus memberi tanggapan.

Halaman:

- Daftar pengaduan.
- Buat pengaduan.
- Detail pengaduan.
- Update status pengaduan.

Data sensitif:

- Identitas pelapor, isi laporan, foto bukti.

Test minimal:

- Warga hanya melihat pengaduan sendiri.
- Sekretaris/admin bisa update status.
- Guest tidak bisa membuat pengaduan.

## 5. Modul Surat Menyurat

Tujuan:

- Mengelola pengajuan surat warga sampai selesai.

Aktor:

- `warga`
- `sekretaris`
- `admin`
- `ketua_rt` atau `ketua_rw` jika role diaktifkan.

Permission:

- `submit-surat`
- `view-surat`
- `manage-surat`
- `approve-surat`
- `export-surat`

Data utama:

- `surats`
- `surat_attachments`
- `users`

Field penting:

- Nomor surat, jenis surat, keperluan, isi tambahan, status, pemohon, verifikator, approver, file hasil.

Status:

- `submitted`
- `verified`
- `approved`
- `rejected`
- `done`

Halaman:

- Warga ajukan surat.
- Riwayat surat warga.
- Detail surat.
- Pengurus daftar surat.
- Pengurus proses surat.
- Cetak/unduh surat.

Validasi penting:

- Jenis surat wajib.
- Keperluan wajib.
- Lampiran hanya PDF/JPG/PNG dengan ukuran dibatasi.

Test minimal:

- Warga tidak melihat surat warga lain.
- Sekretaris bisa verifikasi.
- Admin/ketua bisa approve.

## 6. Modul Kegiatan

Tujuan:

- Mengelola agenda RT/RW dan partisipasi warga.

Aktor:

- `admin`
- `sekretaris`
- `bendahara` jika kegiatan punya anggaran.
- `warga`

Permission:

- `view-kegiatan`
- `submit-kegiatan`
- `manage-kegiatan`
- `approve-kegiatan`

Data utama:

- `kegiatans`
- `kegiatan_pesertas`
- `kegiatan_dokumentasis`

Field penting:

- Judul, deskripsi, tanggal, lokasi, penanggung jawab, anggaran, status, kuota.

Status:

- `draft`
- `published`
- `done`
- `cancelled`

Halaman:

- Daftar kegiatan publik.
- Detail kegiatan.
- Daftar peserta.
- Admin kelola kegiatan.

Test minimal:

- Warga bisa melihat kegiatan published.
- Sekretaris/admin bisa CRUD.
- Bendahara hanya melihat anggaran jika diberi akses.

## 7. Modul Aset

Tujuan:

- Mencatat inventaris, kondisi, peminjaman, pengembalian, dan perawatan aset RT/RW.

Aktor:

- `admin`
- `sekretaris`
- `pengelola_aset`
- `warga`

Permission:

- `view-aset`
- `manage-aset`
- `approve-aset`
- `export-aset`

Data utama:

- `asets`
- `aset_peminjamans`
- `aset_maintenances`

Field penting:

- Kode aset, nama, kategori, jumlah, kondisi, lokasi, nilai aset, status.

Status aset:

- `available`
- `borrowed`
- `maintenance`
- `broken`
- `lost`

Status peminjaman:

- `submitted`
- `approved`
- `rejected`
- `borrowed`
- `returned`

Test minimal:

- Warga bisa ajukan peminjaman.
- Pengurus bisa approve.
- Warga tidak bisa edit data aset.

## 8. Modul Bank Sampah

Tujuan:

- Mengelola jenis sampah, setoran warga, saldo, dan laporan bank sampah.

Aktor:

- `warga`
- `pengelola_bank_sampah`
- `bendahara`
- `admin`

Permission:

- `view-bank-sampah`
- `submit-bank-sampah`
- `manage-bank-sampah`
- `verify-bank-sampah`
- `export-bank-sampah`

Data utama:

- `bank_sampah_items`
- `bank_sampah_setorans`
- `bank_sampah_saldos`

Field penting:

- Jenis sampah, berat, harga per kg, total nilai, status verifikasi, user.

Status:

- `submitted`
- `verified`
- `rejected`

Test minimal:

- Warga melihat setoran sendiri.
- Pengurus verifikasi setoran.
- Saldo berubah setelah setoran verified.

## 9. Modul UMKM

Tujuan:

- Mendata UMKM warga dan menampilkan katalog UMKM terverifikasi.

Aktor:

- `warga`
- `sekretaris`
- `pengelola_umkm`
- `admin`

Permission:

- `view-umkm`
- `submit-umkm`
- `manage-umkm`
- `approve-umkm`

Data utama:

- `umkms`
- `umkm_products`

Field penting:

- Nama usaha, pemilik, kategori, deskripsi, kontak, alamat, status verifikasi, foto/logo.

Status:

- `submitted`
- `approved`
- `rejected`
- `inactive`

Test minimal:

- Warga mengelola UMKM sendiri.
- Admin/sekretaris approve UMKM.
- Katalog hanya menampilkan UMKM approved.

## 10. Modul Posyandu

Tujuan:

- Mengelola jadwal posyandu, peserta, dan catatan pemeriksaan.

Aktor:

- `kader_posyandu`
- `sekretaris`
- `admin`
- `warga`

Permission:

- `view-posyandu`
- `manage-posyandu`
- `export-posyandu`

Data utama:

- `posyandu_jadwals`
- `posyandu_pesertas`
- `posyandu_pemeriksaans`

Field penting:

- Jadwal, nama peserta, hubungan keluarga, usia, berat, tinggi, catatan kesehatan.

Data sensitif:

- Catatan kesehatan dan data anak/keluarga.

Test minimal:

- Warga hanya melihat data keluarga sendiri.
- Kader/sekretaris bisa kelola pemeriksaan.
- Data kesehatan tidak tampil publik.

## 11. Modul Keamanan/Ronda

Tujuan:

- Mengelola jadwal ronda, petugas, presensi, dan laporan kejadian.

Aktor:

- `sekretaris`
- `petugas_ronda`
- `admin`
- `warga`

Permission:

- `view-ronda`
- `manage-ronda`
- `submit-ronda`
- `verify-ronda`

Data utama:

- `ronda_jadwals`
- `ronda_petugas`
- `ronda_presensis`
- `ronda_laporans`

Field penting:

- Tanggal, shift, lokasi pos, petugas, status hadir, laporan kejadian.

Status:

- `scheduled`
- `attended`
- `missed`
- `reported`
- `verified`

Test minimal:

- Warga melihat jadwal.
- Petugas submit presensi/laporan.
- Pengurus verifikasi laporan.

## 12. Modul Rukem

Rukem diasumsikan sebagai Rukun Kematian.

Tujuan:

- Mengelola data anggota, iuran, laporan duka, dan bantuan rukem.

Aktor:

- `warga`
- `sekretaris`
- `bendahara`
- `admin`

Permission:

- `view-rukem`
- `manage-rukem`
- `submit-rukem`
- `approve-rukem`
- `export-rukem`

Data utama:

- `rukem_members`
- `rukem_iurans`
- `rukem_claims`
- `rukem_bantuans`

Field penting:

- Anggota, periode iuran, nominal, laporan duka, penerima bantuan, status.

Status:

- `submitted`
- `verified`
- `approved`
- `rejected`
- `done`

Test minimal:

- Warga melihat data sendiri.
- Pengurus proses klaim.
- Dana rukem punya laporan terpisah.

## 13. Modul Koperasi

Tujuan:

- Mengelola simpanan, pinjaman, angsuran, dan laporan koperasi warga.

Aktor:

- `warga`
- `bendahara`
- `pengurus_koperasi`
- `admin`

Permission:

- `view-koperasi`
- `submit-koperasi`
- `manage-koperasi`
- `approve-koperasi`
- `export-koperasi`

Data utama:

- `koperasi_members`
- `koperasi_simpanans`
- `koperasi_pinjams`
- `koperasi_angsurans`

Field penting:

- Anggota, nominal simpanan, nominal pinjaman, tenor, bunga/jasa jika ada, status persetujuan, sisa pinjaman.

Status:

- `submitted`
- `approved`
- `rejected`
- `active`
- `paid`
- `defaulted`

Test minimal:

- Warga melihat transaksi sendiri.
- Bendahara/pengurus approve pinjaman.
- Angsuran mengurangi sisa pinjaman.

## 14. Prioritas Implementasi

Urutan implementasi yang disarankan:

1. Stabilkan modul inti: warga, rumah, tagihan, kas, laporan, pengaduan.
2. Surat menyurat.
3. Kegiatan.
4. Aset.
5. Bank sampah.
6. UMKM.
7. Posyandu.
8. Ronda.
9. Rukem.
10. Koperasi.

Jika waktu UAS mepet, prioritaskan fitur yang bisa didemokan end-to-end daripada membuat semua modul setengah matang.
