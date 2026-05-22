# Blueprint Sistem Role

Dokumen ini menjadi aturan standar AI dalam sistem role yang sedang dibangun.

## 1. Tujuan

- Menetapkan struktur role dan permission.
- Menjamin kontrol akses konsisten.
- Menjadi acuan AI untuk memproses validasi, otorisasi, dan logging.

## 2. Komponen Utama

- `User`: entitas pengguna.
- `Role`: kelompok hak akses.
- `Permission`: tindakan spesifik yang boleh dilakukan.
- `Resource`: modul atau data yang dikontrol.

## 3. Role Standar

Minimal role yang direkomendasikan:

- `admin`
    - akses penuh.
    - manajemen user, role, data kas, laporan.
- `bendahara`
    - input dan edit kas masuk/keluar.
    - lihat laporan ringkas.
- `kasir`
    - input transaksi harian.
    - lihat data kas.
- `warga`
    - lihat status kontribusi/riwayat.
    - submit permintaan atau laporan.
- `guest`
    - akses publik terbatas.

## 4. Permission Umum

Permission sebaiknya dibuat granular:

- `manage_users`
- `manage_roles`
- `view_kas`
- `create_kas`
- `update_kas`
- `delete_kas`
- `view_laporan`
- `export_laporan`

## 5. Aturan AI Standar

AI dalam sistem harus:

- Selalu membaca `role` dan `permission` sebelum mengeksekusi.
- Menggunakan prinsip `least privilege`: hanya izinkan akses yang diperlukan.
- Menolak akses jika role tidak mencakup permission yang diminta.
- Tidak mengembalikan data sensitif jika user tidak berwenang.
- Menggunakan policy/gate untuk setiap tindakan CRUD dan filter.
- Mengutamakan otentikasi terlebih dahulu, baru otorisasi.

## 6. Mekanisme Kontrol Akses

Saran implementasi:

- Middleware otentikasi + middleware role.
- Policy atau Gate Laravel untuk masing-masing model.
- Relasi:
    - `users` -> `roles`
    - `roles` -> `permissions`

Contoh alur:

1. request masuk.
2. cek auth.
3. ambil role user.
4. tentukan permission yang dibutuhkan.
5. jika sesuai, lanjut; jika tidak, tolak.

## 7. Integrasi dengan Modul Kas

Untuk modul kas masuk/keluar, minimal:

- `admin`: bisa lihat, buat, edit, hapus, filter.
- `bendahara`: bisa lihat, buat, edit.
- `kasir`: bisa lihat, buat.
- `warga`: hanya lihat riwayat sendiri.

## 8. Standard AI bagi Validasi dan Pelaporan

- AI menyimpulkan request berdasarkan role.
- Jika role `admin`, AI memberikan akses penuh.
- Jika role `bendahara`/`kasir`, AI membatasi aksi sesuai permission.
- Jika role `warga`, AI hanya menampilkan data relevan.
- Semua eksekusi akses harus tercatat untuk audit.

## 9. Referensi Implementasi

- `Role` sebagai entitas independen, bukan hanya string.
- Gunakan tabel:
    - `roles`
    - `permissions`
    - `role_permission`
    - `user_role`
- Pastikan:
    - role dapat diubah tanpa mengubah logika bisnis.
    - permission dapat ditambah untuk fitur baru.

Dengan aturan ini, AI dapat bertindak sebagai pengawal standar akses dalam sistem role yang dibangun.
