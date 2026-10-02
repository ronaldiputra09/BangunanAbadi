# Bangunan Abadi — Sync System AOL

Aplikasi CodeIgniter 4 untuk mengelola pengguna serta menyinkronkan master data dan transaksi antara database Bangunan Abadi dengan Accurate Online (AOL).

Panduan pengguna lengkap tersedia dalam [PDF bergambar](output/pdf/Panduan_Pengguna_Bangunan_Abadi.pdf) dan [versi Markdown](output/pdf/Panduan_Pengguna_Bangunan_Abadi.md). PDF mencakup 31 halaman dan 16 screenshot; tampilan setelah login memakai data demonstrasi dari template aplikasi asli.

Dokumen ini membahas proyek dan setup lokal. Panduan production dipisahkan ke [DEPLOYMENT_SHARED_HOSTING.md](DEPLOYMENT_SHARED_HOSTING.md).

## Fitur

- Login, profil, password, avatar, dan pengelolaan pengguna.
- OAuth Accurate Online dan pemilihan database Accurate.
- Sinkronisasi pelanggan, pemasok, karyawan, dan barang.
- Sinkronisasi purchase order, penerimaan barang, invoice/retur pembelian, invoice/penerimaan/retur penjualan.
- Sinkronisasi berdasarkan periode atau nomor transaksi.
- Log sinkronisasi dan endpoint cron otomatis.
- Ekspor spreadsheet dengan PhpSpreadsheet.

## Teknologi

- PHP `^8.1` (terverifikasi lokal dengan PHP 8.4.19)
- CodeIgniter 4.6.0
- MySQL/MariaDB melalui MySQLi
- PhpSpreadsheet 4.1
- Bootstrap, jQuery, dan aset frontend statis
- PHPUnit 10

Tidak ada proses build Node.js/npm; aset frontend sudah tersedia di folder `assets`.

## Struktur penting

```text
app/
├── Config/          Konfigurasi dan route
├── Controllers/     Login, dashboard, dan integrasi Accurate
├── Libraries/       Service API internal
├── Models/          Query database
└── Views/           Tampilan
assets/              CSS, JavaScript, dan gambar
upload/              Logo dan avatar publik
vendor/              Dependency Composer
writable/            Cache, log, session, dan file sementara
index.php            Front controller proyek
router.php           Router PHP development server
```

Proyek telah dimodifikasi untuk hosting: `index.php` berada di root dan `public/index.php` tidak tersedia. Karena itu, gunakan `router.php` untuk lokal dan jangan memakai `php spark serve`.

## Persyaratan lokal

- PHP 8.1 atau lebih baru.
- Composer 2.
- MySQL 8 atau MariaDB yang kompatibel dengan window function `ROW_NUMBER()`.
- Ekstensi PHP: `curl`, `fileinfo`, `gd`, `intl`, `json`, `mbstring`, `mysqli`, `mysqlnd`, `openssl`, `xml`, dan `zip`.
- Kredensial OAuth Accurate Online.
- Database MySQL/MariaDB kosong atau database existing yang kompatibel.
- Akses outbound HTTP/HTTPS ke API sumber dan Accurate.

Schema instalasi baru dibuat oleh migration di `app/Database/Migrations`. Seeder membuat akun administrator awal; data transaksi akan terisi melalui proses sinkronisasi.

## Setup lokal

### 1. Pasang dependency

```bash
composer install
```

Gunakan `composer install`, bukan `composer update`, agar versi mengikuti `composer.lock`.

Jika `vendor` sudah ada tetapi muncul error `Boot.php` tidak ditemukan:

```bash
composer reinstall "*" --no-interaction --prefer-dist
```

### 2. Siapkan database

1. Buat database MySQL/MariaDB lokal yang kosong.
2. Aktifkan konfigurasi berikut di `.env`:

```dotenv
database.default.hostname = localhost
database.default.database = nama_database
database.default.username = nama_user
database.default.password = 'password_database'
database.default.DBDriver = MySQLi
database.default.DBPrefix =
database.default.port = 3306
```

Nilai `.env` menggantikan default `app/Config/Database.php`. Jangan menyimpan password database di Git.

### 3. Atur environment dan Accurate OAuth

```dotenv
CI_ENVIRONMENT = development
app.debug = true
app.baseURL = 'http://127.0.0.1:8080/'
app.appTimezone = 'Asia/Jakarta'

ACCURATE_REDIRECT_URI = 'http://127.0.0.1:8080/auth/callback'

SEED_ADMIN_USERNAME = 'admin'
SEED_ADMIN_PASSWORD = 'ganti-dengan-password-minimal-12-karakter'
SEED_ACCURATE_CLIENT_ID = ''
SEED_ACCURATE_CLIENT_SECRET = ''
```

Daftarkan callback lokal yang sama pada aplikasi developer Accurate. Nilainya harus identik, termasuk skema, host, port, dan path. Client ID/Secret dapat diberikan melalui variabel seed atau menu **Settings** setelah login.

> `.env` saat ini terlacak karena `.gitignore` kosong. Jangan commit perubahan yang berisi kredensial. Perbaiki ignore dan rotasi seluruh kredensial yang pernah tersimpan di repository sebelum digunakan pada production.

### 4. Buat schema dan akun awal

```bash
php spark migrate --all
php spark db:seed DatabaseSeeder
```

`SEED_ADMIN_PASSWORD` wajib diisi dan minimal 12 karakter. Seeder bersifat idempotent: menjalankannya kembali akan memperbarui akun dengan username yang sama, bukan membuat duplikat.

Untuk membatalkan seluruh migration pada database development:

```bash
php spark migrate:rollback --all
```

Perintah rollback menghapus tabel beserta datanya. Jangan jalankan pada database yang datanya masih diperlukan.

### 5. Atur folder runtime

```bash
chmod -R u+rwX writable upload
```

`writable` dipakai untuk session, cache, debugbar, dan log. `upload` dipakai untuk avatar pengguna.

### 6. Jalankan aplikasi

```bash
php -S 127.0.0.1:8080 router.php
```

Buka <http://127.0.0.1:8080>. Hentikan server dengan `Ctrl+C`.

## Verifikasi

```bash
php spark routes
php spark config:check App
composer test
```

Smoke test halaman dan aset:

```bash
curl -I http://127.0.0.1:8080/
curl -I http://127.0.0.1:8080/assets/css/bootstrap.min.css
curl -I http://127.0.0.1:8080/upload/logo.png
```

Semua URL tersebut seharusnya merespons HTTP 200. Test saat dokumentasi dibuat: 5 test dan 7 assertions lulus; PHPUnit tetap keluar dengan warning karena driver code coverage tidak terpasang dan konfigurasi mengaktifkan `failOnWarning`.

## Alur penggunaan

1. Login memakai akun dari database aplikasi.
2. Otorisasi Accurate dan pilih database Accurate.
3. Sinkronkan master data sebelum transaksi.
4. Jalankan sinkronisasi periode/nomor transaksi.
5. Periksa halaman log jika ada data gagal.

Token, session, host, dan database Accurate terpilih disimpan dalam session pengguna. Otorisasi ulang diperlukan setelah session berakhir.

## Endpoint utama

| Area | Endpoint | Keterangan |
| --- | --- | --- |
| Login | `/`, `/login` | Halaman login |
| Accurate | `/auth`, `/auth/callback` | OAuth Accurate |
| Database Accurate | `/auth/db-list` | Memilih database |
| Dashboard | `/home` | Halaman utama |
| Master | `/MasterData`, `/SyncMasterItem` | Sinkronisasi master |
| Transaksi | `/SyncTransaction`, `/Transaction-no` | Sinkronisasi transaksi |
| Log | `/home/log` | Riwayat proses |
| Pengguna | `/users` | Pengelolaan pengguna |

Daftar lengkap tersedia melalui `php spark routes` atau `app/Config/Routes.php`.

## Troubleshooting

### `Boot.php` tidak ditemukan

```bash
composer reinstall "*" --no-interaction --prefer-dist
```

### CSS/gambar 404 atau route tidak bekerja

Pastikan perintah lokal memakai router yang disediakan:

```bash
php -S 127.0.0.1:8080 router.php
```

### Redirect menuju domain lama

Set `app.baseURL` di `.env` ke URL lokal dan gunakan trailing slash.

### Login/sinkronisasi gagal karena database

Pastikan schema sudah di-import, `database.default.*` benar, server dapat diakses, serta `mysqli` aktif.

### OAuth Accurate gagal

Periksa Client ID pengguna pada menu **Settings** dan `ACCURATE_REDIRECT_URI` di `.env`. Callback `.env` dan portal Accurate harus sama persis.

### Session, log, atau avatar gagal ditulis

Pastikan proses PHP mempunyai izin tulis ke `writable` dan `upload`.
