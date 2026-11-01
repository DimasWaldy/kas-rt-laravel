# Rumah belum siap ditagih dan penetapan PJ iuran

## Perilaku sekarang

Rumah yang disetujui melalui alur domisili tidak otomatis menunjuk warga pertama
sebagai penanggung jawab iuran. Pengurus tetap menetapkannya secara eksplisit.
Jika rumah belum mempunyai PJ yang sah, generator melaporkan rumah tersebut
beserta alasannya, sementara rumah lain yang sudah siap tetap diproses.

Rumah siap menerima tagihan baru jika berstatus aktif dan penanggung jawabnya:

- mempunyai akun aktif;
- terdaftar sebagai penghuni rumah dan RT yang sama;
- mempunyai penanda penanggung jawab iuran rumah yang sesuai.

Rumah kosong/nonaktif tidak masuk antrean penetapan PJ maupun target tagihan baru.
PJ yang sudah pindah, tidak aktif, atau tidak lagi ditetapkan sebagai PJ juga
masuk daftar perbaikan. Sistem tidak membuat tagihan baru untuk akun yang tidak
sesuai tersebut.

## Alur pengurus

1. Setelah menyetujui domisili rumah yang belum siap, menu **Verifikasi Warga**
   menampilkan **Langkah berikutnya: tetapkan PJ iuran**.
2. Antrean juga selalu dapat dibuka lewat **Data Rumah → Perlu PJ Iuran**.
   Filter ini menampilkan rumah aktif yang belum siap, bukan rumah kosong/nonaktif.
3. Buka **Tetapkan PJ**, pilih penghuni aktif dari rumah/RT itu, lalu simpan.
   Akun dari rumah lain, RT lain, dan akun tidak aktif ditolak oleh server.
4. Penyimpanan menetapkan satu PJ dan mencatat audit `pj_iuran_ditetapkan`.
   Penyimpanan PJ tidak langsung membuat tagihan atau memilih periode penagihan.
5. Bendahara membuka **Iuran Bulanan**, memilih **bulan dan tahun yang terlewat**,
   lalu menekan **Generate Tagihan**. Ini juga dapat digunakan untuk bulan sebelumnya.
6. Periksa hasil generate dan daftar rumah yang masih perlu ditangani. Generate
   ulang periode yang sama melengkapi tagihan yang belum dibuat tanpa menyisipkan
   ulang tagihan rumah/periode/kelompok iuran yang sudah ada.

Pengurus yang dapat mengelola Data Rumah mendapatkan tautan **Tetapkan PJ**.
Bendahara yang hanya memiliki akses keuangan melihat rumah dan alasannya, beserta
petunjuk untuk meminta pengurus menetapkan PJ. Halaman profil/tagihan warga juga
menjelaskan bahwa rumah belum siap menerima tagihan, sehingga ketiadaan tagihan
tidak disalahartikan sebagai proses penagihan yang sudah selesai.

## Hasil generator

Hasil `Tagihan::generate()` mempertahankan penghitung `created`, `skipped`, dan
`updated`, lalu menambahkan:

| Field | Makna |
| --- | --- |
| `active_houses` | Jumlah rumah aktif yang diperiksa dalam wilayah proses |
| `has_iuran` | Ada komponen iuran bernilai positif untuk periode yang dipilih |
| `unbilled_houses` | Daftar ID/kode rumah dan alasan PJ yang membuat rumah belum dapat ditagih |

Rumah yang belum siap dihitung terpisah dari `skipped`. `skipped` menghitung nota
yang sudah ada; `updated` adalah bagian dari nota yang sudah ada tersebut, bukan
kategori yang harus dijumlahkan dengannya. Satu rumah yang belum siap dilaporkan
satu kali walaupun ada beberapa kelompok iuran.

Halaman Iuran Bulanan menampilkan ringkasan hasil generate dan antrean PJ terkini.
Hasil generate massal juga dicatat di audit `mass_tagihan_generated`. Laporan dan
antrean dibatasi ke RT pengurus, RW pengurus RW, atau seluruh wilayah untuk
operator global. Scheduler tanpa aktor memeriksa seluruh wilayah.

## Perintah terminal

Default tetap menggunakan bulan berjalan:

```sh
php artisan bills:generate
```

Untuk melengkapi periode tertentu setelah PJ ditetapkan:

```sh
php artisan bills:generate --bulan=9 --tahun=2026
```

Perintah menampilkan jumlah tagihan baru/sudah ada/diperbarui, jumlah rumah aktif,
dan tabel kode rumah beserta alasan belum dapat ditagih.

- Exit `0`: tidak ada rumah aktif yang tertahan karena PJ.
- Exit `1`: ada rumah yang perlu penetapan/perbaikan PJ. Tagihan rumah yang sudah
  siap tetap dapat dibuat; periksa hasil sebelum mengulang.
- Exit `2`: periode tidak valid. Tidak ada komponen/tagihan yang diubah.

UI dan CLI menerima bulan 1–12 dan tahun 2024 sampai tahun berjalan + 5. Saat
menyalin komponen dari bulan sebelumnya, tanggal periode menggunakan tanggal 1
agar perhitungan tidak melompat bulan pada tanggal 29–31.

## Penerapan dan uji

Perbaikan ini tidak memerlukan migration baru dan tidak mengisi PJ secara massal.
Terapkan kode dan jalankan build aset di lingkungan tujuan. Migrasi domisili dari
tahap sebelumnya tetap diperlukan bila belum diterapkan.

```sh
php artisan test tests/Feature/BillingReadinessTest.php tests/Feature/RumahBillingTest.php tests/Feature/RumahManagementTest.php tests/Feature/DomisiliOnboardingTest.php
php artisan test
npm run test:frontend
npm run build
```

Verifikasi utama: rumah tanpa PJ muncul beserta alasan; rumah siap tetap ditagih;
pengurus memilih PJ yang sah; antrean berkurang setelah penyimpanan; generate ulang
periode yang dipilih membuat tagihan tertunda satu kali. Regresi juga memeriksa
bahwa status/nominal tagihan lunas dan pending tidak direset oleh generate ulang.

Smoke test pada database aplikasi hanya membaca halaman/antrean; tidak menjalankan
generate nyata dan tidak mengirim notifikasi. Penetapan PJ nyata tetap memerlukan
pemeriksaan pengurus atas siapa yang bertanggung jawab.

Hasil lokal saat penyelesaian: **408 test backend lulus (3.306 assertions)**,
**5 test frontend lulus**, dan **build produksi berhasil**. Halaman Iuran Bulanan
serta filter rumah perlu PJ mengembalikan HTTP 200 pada smoke test MySQL lokal.
