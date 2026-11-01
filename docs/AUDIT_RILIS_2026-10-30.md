# Audit kesiapan rilis Smart RW — 30 Oktober 2026

## Keputusan

**Belum siap dibuka untuk warga umum dengan data nyata.** Dua celah utama pada hubungan rumah/KK sudah dibenahi di lokal sebagaimana pembaruan berikut. Temuan operasional lainnya dan pemeriksaan produksi masih menjadi pekerjaan berikutnya.

Audit membaca working tree lokal pada branch `hardening/lanjutan`, termasuk perubahan registrasi, domisili, dan RFID yang belum dikomit. Commit dasar saat pemeriksaan: `7dff5b5`. Hasil ini tidak membuktikan bahwa perubahan sudah terpasang di server produksi.

## Pembaruan perbaikan pertama — pemilihan rumah

**Status: implementasi dan migrasi lokal selesai; belum dideploy ke produksi.**

- Pilihan rumah, termasuk alamat yang sama dengan rumah terdaftar, hanya disimpan sebagai pengajuan. `users.rumah_id` tetap kosong dan layanan/data rumah masih ditahan.
- Pengurus memeriksa melalui **Verifikasi Warga → Pengajuan Domisili**. Hanya persetujuan pengurus yang menghubungkan akun ke rumah. Pemeriksaan dibatasi ke wilayah akun warga; pengajuan dapat dikembalikan dengan catatan untuk diperbaiki.
- Rumah baru dibuat setelah persetujuan. Perubahan RT/status atau penghapusan rumah ketika menunggu harus diperiksa kembali; rumah yang muncul setelah pengajuan tidak diam-diam menjadi tujuan persetujuan.
- Regresi keamanan memastikan bukti pembayaran ditolak sebelum persetujuan, baik melalui pilihan rumah maupun alamat yang cocok. Otorisasi data juga diuji tanpa mengandalkan redirect middleware.
- Hasil verifikasi setelah perbaikan: **368 test backend lulus, 3.066 assertions; 5 test frontend lulus; build produksi berhasil**.
- Migrasi `2026_10_30_000003_add_domicile_review_to_wargas` berhasil pada MySQL lokal. Pemeriksaan jumlah data sebelum/sesudah tetap: 2 akun, 1 warga, 1 rumah, 0 tagihan. Migrasi lain yang tertunda tidak dijalankan.
- Smoke test melalui HTTP kernel dengan MySQL lokal mengembalikan **HTTP 200**, antrean **Pengajuan Domisili** berhasil dirender, dan pencatatan migrasi terkonfirmasi. Session/cache smoke test hanya memakai memori.
- Penempatan rumah dari versi sebelumnya tidak dicabut otomatis; aplikasi tidak dapat membedakan penempatan sah dari pengurus dan pengisian mandiri versi lama tanpa pemeriksaan tambahan. Bila versi lama sudah dipakai warga, pengurus perlu memeriksa penempatan akun lama.

Panduan alur dan penerapan: `docs/REGISTRASI_DOMISILI.md`. Bagian temuan di bawah mempertahankan bukti kondisi saat audit awal, sebelum perbaikan ini.

## Pembaruan perbaikan kedua — KK dan kepala keluarga

**Status: implementasi lokal selesai; tidak memerlukan migration baru; belum dideploy ke produksi.**

- Profil menampilkan nomor KK, nama kepala, dan status keluarga sebagai data baca saja. Server menolak perubahan keanggotaan/status KK dan field pengambilalihan dari profil. Penyimpanan profil hanya mengubah data pribadi, termasuk jika formulir lama mengirim ulang nilai KK yang sama.
- Mengganti nama akun kepala keluarga tidak lagi menimpa nama kepala KK resmi. Penetapan/koreksi KK dilakukan pengurus aktif melalui **Data Warga → Edit**.
- Tambah/edit warga menolak kepala kedua kecuali pengurus secara eksplisit mengonfirmasi penggantian. Penggantian yang disetujui menjadikan kepala sebelumnya sebagai anggota, menetapkan satu kepala, dan dicatat di audit log bersama pengurus dan waktu.
- Penguncian baris KK dan transaksi menyatukan perubahan kepala, keanggotaan, dan audit. Kegagalan validasi juga membatalkan akun atau rumah baru dari request yang sama. Penanggung jawab iuran tidak ikut berpindah karena pergantian kepala KK.
- KK baru untuk seorang anggota wajib memakai nama kepala yang sebenarnya; nama anggota tidak digunakan sebagai pengganti otomatis. KK di rumah lain dan perubahan oleh pengurus dari RT/RW lain ditolak.
- Hasil verifikasi: **388 test backend lulus, 3.175 assertions; 5 test frontend lulus; build produksi berhasil**. Regresi mencakup serangan lewat profil, perubahan nama akun kepala, konfirmasi penggantian, koreksi kepala ganda lama, rollback, dan batas wilayah.
- Smoke test MySQL lokal: profil dan form pengurus **HTTP 200**, field keluarga di profil tidak dapat diedit, dan form pengurus memuat konfirmasi penggantian. Pemeriksaan baca saja menemukan **0 KK berkepala ganda** pada database lokal; data lama tidak diubah secara massal.

Panduan: `docs/KEAMANAN_KARTU_KELUARGA.md`. Bukti temuan nomor 2 berikut merekam kondisi sebelum perbaikan.

## Pembaruan perbaikan ketiga — rumah belum siap ditagih

**Status: implementasi lokal selesai; tidak memerlukan migration baru; belum dideploy ke produksi.**

- Generator sekarang memeriksa semua rumah aktif dalam wilayah proses, lalu melaporkan ID/kode rumah beserta alasan PJ yang membuatnya belum dapat ditagih. Rumah yang sudah siap tetap diproses; rumah yang tertahan tidak dicampur dengan penghitung nota yang sudah ada.
- Halaman **Iuran Bulanan** menampilkan hasil generate dan daftar rumah yang perlu ditangani. **Data Rumah → Perlu PJ Iuran** menyediakan antrean aktif dengan tombol **Tetapkan PJ**. Pengurus mendapat langkah penetapan PJ setelah menyetujui domisili yang belum siap; warga melihat penjelasan di profil dan tagihan.
- PJ harus merupakan akun aktif yang tinggal di rumah dan RT yang sama. Penetapan tetap dilakukan pengurus, dicatat di audit, dan tidak otomatis membuat tagihan. PJ yang pindah/tidak aktif/penandanya tidak sesuai juga dilaporkan untuk perbaikan.
- Bendahara dapat memilih bulan/tahun yang terlewat melalui UI. CLI mendukung `--bulan` dan `--tahun`, menampilkan tabel rumah/alasan, dan mengembalikan exit 1 saat ada rumah tertahan. Generate ulang periode yang sama melengkapi tagihan tanpa menyisipkan nota yang sudah ada.
- Laporan dan antrean dibatasi ke RT/RW pengurus; operator global/scheduler dapat memeriksa seluruh wilayah. Pengurus yang hanya mempunyai akses keuangan mendapat petunjuk untuk meminta pengelola rumah menetapkan PJ, tanpa tautan mutasi yang tidak dapat diaksesnya.
- Verifikasi akhir: **408 test backend lulus, 3.306 assertions; 5 test frontend lulus; build produksi berhasil**. Regresi mencakup laporan parsial, PJ tidak sah, batas wilayah, penetapan PJ, pengulangan periode berjalan/lama, CLI, serta tagihan lunas/pending.
- Smoke test baca saja pada MySQL lokal: Iuran Bulanan dan filter tugas rumah **HTTP 200**, pemilih periode lama berhasil dirender, dan jumlah tagihan tidak berubah. Ada **1 rumah lokal yang perlu PJ**; data penanggung jawab tidak diisi otomatis dan generate nyata tidak dijalankan.

Panduan: `docs/TAGIHAN_RUMAH_DAN_PJ.md`. Bukti nomor 3 berikut merekam keadaan sebelum perbaikan.

## Pembaruan perbaikan keempat — pendaftaran ditolak dan pengajuan ulang

**Status: implementasi lokal selesai; tidak memerlukan migration baru; belum dideploy ke produksi.**

- Akun pending atau ditolak dapat login ke halaman **Status pendaftaran**. Password tetap harus benar; alasan penolakan hanya terlihat oleh pemilik akun. Middleware membatasi halaman layanan, request mutasi, dan JSON sebelum persetujuan, termasuk akun lama tanpa onboarding.
- Warga ditolak dapat memperbaiki nama/HP dan mengirim klarifikasi memakai akun/email yang sama. Tidak ada akun duplikat, perubahan role/RT/rumah/KK, atau aktivasi otomatis. Email yang sudah terdaftar mendapat petunjuk login/reset password saat mencoba daftar lagi.
- Pengajuan ulang mengembalikan akun ke pending dan antrean pengurus sesuai batas wilayah yang ada. Detail pemeriksaan menampilkan alasan sebelumnya serta klarifikasi terakhir. Penolakan/pengajuan ulang dicatat di audit, dengan transaksi dan kunci baris untuk menolak kirim ganda atau form kedaluwarsa.
- Setelah disetujui, pendaftar baru tetap harus melalui pemeriksaan domisili. Reset password tidak mengubah status persetujuan. Tidak ada perubahan massal pada data lokal.
- Regresi baru mencakup login/status privat, pembatasan layanan dan bukti pembayaran, pemalsuan field, akun/email tetap, siklus penolakan berulang, batas RT, reset password, serta persetujuan dan domisili berikutnya.
- Verifikasi akhir: **422 test backend lulus, 3.564 assertions; 5 test frontend lulus; build produksi berhasil**. Tes menggunakan SQLite in-memory dan notifikasi palsu; bukan bukti pengiriman email atau deployment produksi.

Panduan: `docs/PERBAIKAN_PENDAFTARAN.md`. Bukti nomor 4 berikut merekam keadaan sebelum perbaikan.

## Bukti pemeriksaan awal

| Pemeriksaan | Hasil |
| --- | --- |
| Seluruh test backend yang sudah ada | **357 lulus, 2.846 assertions**, 38,67 detik |
| Test frontend | **5 lulus**, 0 gagal |
| Build aset produksi | **Berhasil** dengan Vite |
| Probe tambahan untuk enam dugaan masalah | **Keenam perilaku berhasil direproduksi**, 66 assertions |

Probe tambahan memakai SQLite `:memory:` dan data sintetis. Kelulusan probe berarti masalahnya terbukti terjadi, bukan berarti perilakunya benar. File probe sementara dihapus setelah pemeriksaan agar perilaku yang keliru tidak menjadi standar test aplikasi. Implementasi aplikasi belum diubah pada tahap audit awal ini.

Pengujian PHP perlu dijalankan di luar sandbox karena pemeriksaan `is_readable()` pada autoload menghasilkan false di dalam sandbox, walaupun filenya ada. Setelah dijalankan dengan akses yang sesuai, suite selesai tanpa kegagalan.

## 1. P1 — Pilihan rumah pertama langsung memberi akses ke data rumah orang lain (sudah dibenahi di lokal)

**Terbukti melalui request aplikasi.**

Urutan reproduksi:

1. Siapkan rumah milik warga A beserta tagihan dan bukti pembayarannya.
2. Warga B mendaftar dan disetujui pengurus ke RT yang sama. Pengurus belum menetapkan rumah B.
3. B memilih rumah A di halaman domisili awal.
4. Penyimpanan berhasil dan `users.rumah_id` B langsung menjadi rumah A.
5. B meminta endpoint bukti pembayaran A; server membalas **HTTP 200**.

Sumber: `app/Http/Controllers/DomisiliController.php:48` dan `:61`; `app/Http/Controllers/TagihanController.php:450` dan `:466`. Daftar tagihan juga mengambil data berdasarkan rumah pada `TagihanController.php:43`.

Pembatasan RT sudah bekerja, tetapi kesamaan RT belum membuktikan seseorang tinggal di rumah tertentu. Pengurus menyetujui akun sebelum pilihan rumah tersedia, dan tidak ada pemeriksaan ulang atas pilihan tersebut.

**Perbaikan minimum:** perlakukan pilihan rumah sebagai pengajuan sampai pengurus mengonfirmasi hubungan warga–rumah. Jangan gunakan hubungan yang belum disetujui untuk membuka tagihan dan berkas rumah. Rumah yang sudah berpenghuni juga dapat memakai undangan yang disetujui pengurus/penghuni yang berwenang. Larangan pindah rumah lewat profil tetap dipertahankan.

**Kriteria selesai:** warga yang disetujui hanya untuk RT belum dapat membaca bukti pembayaran rumah lain hanya dengan memilih rumah tersebut. Penghuni yang sah tetap dapat mengakses data rumah sesuai kebijakan aplikasi.

## 2. P1 — Profil pribadi dapat mengganti kepala KK dan menghasilkan dua kepala keluarga (sudah dibenahi di lokal)

**Terbukti melalui request aplikasi.**

Setelah berada di rumah yang sama, pengguna mengisi nomor KK yang sudah ada dan memilih `kepala_keluarga` pada profil. Server menghubungkan pengguna ke KK tersebut dan mengganti `nama_kepala_keluarga` dengan nama pengguna. Warga kepala keluarga sebelumnya tetap berstatus kepala keluarga. Dalam probe, satu KK berakhir dengan **dua warga berstatus kepala keluarga**, sementara status verifikasi pengguna tetap `terverifikasi`.

Sumber: `app/Http/Controllers/ProfileController.php:84`, `:97`, dan `:117`; `app/Http/Requests/ProfileUpdateRequest.php` mengizinkan nomor KK dan status keluarga diisi lewat profil.

Masalah ini juga dapat terjadi tanpa niat buruk: dua anggota keluarga salah memilih status, lalu nama kepala KK berubah tanpa disadari.

**Perbaikan minimum:** perubahan anggota/nomor KK dan kepala keluarga masuk sebagai pengajuan yang diperiksa pengurus; perubahan profil biasa tidak langsung mengubah data bersama. Penetapan kepala keluarga harus memastikan hanya satu kepala aktif per KK melalui transaksi dan validasi yang sesuai. Saat membuat KK oleh anggota pertama, jangan otomatis memakai nama anggota sebagai nama kepala keluarga.

**Kriteria selesai:** pengguna tidak dapat menimpa kepala KK yang sudah ada; satu KK tidak menghasilkan dua kepala keluarga; koreksi KK memiliki alur yang jelas.

## 3. P2 — Rumah baru selesai dibuat tetapi belum siap ditagih (sudah dibenahi di lokal)

**Terbukti melalui pembuatan tagihan bulanan.**

Domisili awal membuat rumah aktif dan menyelesaikan onboarding, tetapi memang tidak menetapkan penanggung jawab iuran. Generator tagihan hanya mengambil rumah dengan `penanggung_jawab_id` terisi. Dalam probe terdapat iuran wajib, tetapi hasil generate untuk rumah baru adalah `created=0, skipped=0, updated=0`; tidak ada tagihan dibuat.

Sumber: `app/Http/Controllers/DomisiliController.php:54`; `app/Services/TagihanService.php:31` sampai `:34`. Halaman Data Rumah sudah memiliki penghitung rumah tanpa penanggung jawab, sehingga jalur penanganannya sebagian tersedia.

Tidak otomatis mengambil alih penanggung jawab merupakan keputusan yang tepat. Celahnya adalah langkah operasional belum menjadi bagian dari penyelesaian pendaftaran: warga sudah bisa melanjutkan, sedangkan rumahnya belum siap ditagih dan hasil generator tidak menjelaskan rumah yang terlewat.

**Perbaikan minimum:** tampilkan tugas yang jelas untuk pengurus, misalnya “rumah baru perlu penanggung jawab”; tampilkan status tersebut kepada warga; laporkan jumlah rumah yang belum dapat ditagih dan alasannya saat generate. Pengurus menetapkan penanggung jawab secara eksplisit.

**Kriteria selesai:** setiap rumah baru masuk antrean penyelesaian administrasi, dan bendahara dapat mengetahui rumah yang belum tertagih beserta penyebabnya.

## 4. P2 — Pendaftaran yang ditolak buntu dan status sulit dipantau (sudah dibenahi di lokal)

**Terbukti melalui penolakan, login, dan pendaftaran ulang.**

Setelah ditolak, warga tidak bisa login. Pendaftaran ulang dengan email yang sama ditolak karena email sudah dipakai. Warga hilang dari antrean verifikasi karena daftar hanya mengambil status `pending`, sedangkan halaman detail verifikasi mengembalikan 404 untuk status lain. Pesan login tidak menampilkan alasan spesifik penolakan.

Sumber: `app/Http/Controllers/VerifikasiWargaController.php:30`, `:43`, dan `:130`; `app/Http/Requests/Auth/LoginRequest.php:50`; aturan email unik di `RegisteredUserController.php:37`.

Selain itu, notifikasi persetujuan hanya memakai kanal database (`app/Notifications/WargaTerverifikasi.php:12`). Calon warga belum bisa masuk saat menunggu, sehingga belum tersedia cara dalam aplikasi untuk memantau status dan membaca notifikasi persetujuan sebelum mencoba login lagi. Tidak ada jalur pengajuan ulang atau pemulihan status yang ditemukan dalam alur verifikasi saat ini; pemulihan manual memerlukan tindakan operator di luar alur itu.

**Perbaikan minimum:** sediakan akses akun terbatas untuk melihat status dan memperbaiki pengajuan. Bedakan `perlu_perbaikan` dari penolakan final. Tampilkan alasan, aksi berikutnya, dan kontak pengurus yang nyata. Persetujuan dapat diberitahukan melalui kanal yang memang digunakan warga; hilangnya kewajiban verifikasi email tidak harus diubah untuk menyelesaikan masalah ini.

**Kriteria selesai:** salah input tidak mengharuskan email baru atau edit database; warga tahu kapan harus menunggu, memperbaiki, atau menghubungi pengurus.

## 5. P2 — Operator global dapat membuat warga aktif tanpa wilayah

**Terbukti melalui form tambah warga oleh superadmin.**

Superadmin menambah warga beserta rumah baru menggunakan kode dan alamat. Penyimpanan berhasil, tetapi rumah dan pengguna sama-sama memiliki `rt_id=null`; akun berstatus aktif dan tidak masuk onboarding domisili. Pengurus RT tidak melihat warga tersebut pada daftar wilayahnya.

Sumber: `app/Http/Controllers/Admin/UserController.php:119`, `:174`, dan `:184`. Pembuatan rumah oleh aktor yang memiliki akses semua RT mengisi RT dengan null, dan aturan form tidak menyediakan penetapan RT yang wajib untuk skenario ini.

**Perbaikan minimum:** operator global wajib memilih RT aktif saat membuat rumah/warga. Gunakan aturan wilayah yang sama pada registrasi mandiri, tambah warga, dan perpindahan domisili. Jangan mengaktifkan akun warga tanpa penempatan wilayah yang jelas.

**Kriteria selesai:** tambah warga oleh operator menghasilkan akun yang terlihat oleh pengurus wilayah yang dipilih dan dapat memakai layanan sesuai RT-nya.

## 6. P2 — Verifikasi dokumen dapat disahkan meskipun dokumen kosong

**Terbukti melalui request verifikasi dengan metode `dokumen`.**

Pendaftaran baru tidak mengunggah dokumen. Halaman pengurus memberi pesan agar melakukan tatap muka jika berkas tidak ada, tetapi opsi “Review dokumen” tetap tersedia. Backend menerima metode itu, mengaktifkan akun, dan mencatat verifikasi dokumen meskipun KTP dan KK sama-sama null.

Sumber: `app/Http/Controllers/VerifikasiWargaController.php:78`; `resources/views/verifikasi_warga/show.blade.php:59` dan `:91`.

**Perbaikan minimum:** hanya izinkan metode dokumen ketika bukti yang disyaratkan tersedia. Jika petugas memeriksa dokumen fisik di luar aplikasi, beri pilihan dan catatan yang menjelaskan pemeriksaan tersebut. Rekaman verifikasi harus cocok dengan kejadian sebenarnya.

**Kriteria selesai:** server menolak verifikasi unggahan jika berkasnya tidak ada, atau menyimpan metode pemeriksaan fisik beserta catatan yang jelas.

## Penilaian logika dan kemudahan penggunaan

### Yang sudah baik

- Pendaftaran tidak lagi bergantung pada tersedianya rumah dalam database.
- Akun baru dipaksa menjadi warga dengan status menunggu; input role/status dari browser tidak menentukan hak akses.
- Domisili awal dibatasi ke RT hasil persetujuan, hanya dapat diisi sekali, dan tidak otomatis mengambil alih penanggung jawab iuran.
- Profil menolak perpindahan rumah/RT dan pengambilalihan penanggung jawab secara langsung.
- Alamat yang sama setelah trim dan penyamaan huruf besar/kecil dapat menggunakan rumah yang sudah ada.
- Suite yang sudah ada menguji akses wilayah, pembayaran, penghapusan akun bertransaksi, surat, fitur warga, serta onboarding. Kelulusan suite merupakan dasar yang baik, dengan celah cakupan yang dijelaskan di atas.

### Alur saat ini

`Daftar → dilarang login/menunggu → pengurus menyetujui sebelum data domisili tersedia → login → pilih/buat rumah → isi profil/KK → layanan aktif`

Urutan ini memudahkan pengiriman formulir pertama, tetapi status “terverifikasi” datang sebelum rumah dan data keluarga yang akan dipakai layanan benar-benar diperiksa. Setelah itu warga masih dapat mengubah hubungan KK melalui profil. Beban koreksi berpindah ke pengurus setelah akun aktif.

### Alur yang disarankan

`Daftar singkat → masuk dengan akses terbatas → isi pengajuan domisili/data warga → pengurus memeriksa → aktif dan memakai layanan`

Gunakan status yang mudah dipahami: **Lengkapi data**, **Menunggu pengurus**, **Perlu perbaikan**, dan **Aktif**. Login akun terbatas hanya membuka status, pengisian pengajuan, bantuan, dan logout. Rumah boleh diajukan sebagai alamat baru agar daftar akun tetap tidak tergantung pada data rumah yang sudah tersedia. Pilihan RT/kode undangan dapat mengarahkan pengajuan, tetapi penetapan akses tetap ditentukan pengurus.

Jika urutan “setujui akun dulu, isi domisili kemudian” ingin dipertahankan, status rumah/KK yang diajukan tetap perlu persetujuan terpisah sebelum digunakan sebagai dasar akses data bersama. Akun aktif tidak boleh otomatis berarti semua hubungan rumah dan keluarga sudah sah.

Untuk warga umum, halaman awal sebaiknya memprioritaskan aksi **Tagihan**, **Ajukan surat**, dan **Pengaduan**, lalu layanan lain sebagai pilihan tambahan. Formulir perlu memberi label tetap, contoh nomor HP, aturan password sebelum submit, dan pesan error dekat kolom. Normalisasi email dan nomor HP dapat mengurangi kegagalan karena huruf besar, spasi, tanda hubung, atau awalan `+62`. Ini rekomendasi dari review kode/template; audit ini belum menguji tampilan dengan browser atau pengguna awam di ponsel.

## Hal yang belum dapat dinyatakan siap

- **Produksi:** belum memeriksa server/domain aktif, HTTPS, konfigurasi debug, SMTP/reset password nyata, hak akses berkas, migrasi pada database produksi, atau konsistensi versi aset.
- **Operasional:** jadwal generate tagihan dan notifikasi ada di `routes/console.php`; pemasangan scheduler pada server belum diverifikasi. Backup dan pemulihan database/unggahan belum diuji pada audit ini.
- **Pengurus awal:** bootstrap admin tersedia melalui seeder/terminal. Pengaturan wilayah nyata, akun pengurus, dan hak aksesnya perlu dipastikan sebelum menerima warga.
- **MySQL dan concurrency:** test menggunakan SQLite; audit ini tidak membuktikan perilaku lock saat banyak request pada MySQL produksi.
- **Penggunaan nyata:** belum ada uji dari perangkat ponsel, percobaan pengguna awam, pengiriman notifikasi nyata, maupun pembacaan perangkat RFID fisik.
- **Versi rilis:** perubahan terbaru masih ada di working tree. Belum ada bukti snapshot rilis, deployment, dan pengujian domain untuk perubahan tersebut dalam audit ini.

## Urutan penyelesaian

1. Tutup akses rumah dan perubahan data KK yang belum mendapat persetujuan.
2. Satukan status pendaftaran, perbaikan pengajuan, dan penetapan wilayah agar tidak ada alur buntu.
3. Pastikan rumah baru masuk tugas penetapan penanggung jawab; buat hasil generate tagihan menjelaskan rumah yang belum dapat ditagih.
4. Selaraskan metode verifikasi dengan bukti yang tersedia dan berikan cara warga memantau hasilnya.
5. Uji satu perjalanan lengkap: daftar → pengajuan → koreksi bila perlu → persetujuan → rumah/KK/PJ sah → tagihan → pembayaran → konfirmasi → laporan, disertai uji penolakan akses rumah lain.
6. Lakukan uji perangkat ponsel dan pemeriksaan produksi sebelum memutuskan rilis umum.

**Hubungan warga–rumah dan perlindungan KK/kepala keluarga sudah dibenahi di lokal. Berikutnya selesaikan temuan operasional yang tersisa dan verifikasi produksi sebelum rilis umum.**
