# Deployment ke Shared Hosting

Panduan ini khusus deployment production melalui shared hosting/cPanel. Dokumentasi proyek dan setup lokal berada di [README.md](README.md).

## Sebelum go-live

Jangan langsung mempublikasikan kondisi repository saat ini. Audit lokal menemukan hal berikut:

- `composer audit --locked --no-dev` melaporkan 15 advisory pada 2 package runtime.
- Kredensial database dan layanan eksternal pernah disimpan dalam file yang terlacak Git.
- `.env`, `error_log`, isi `writable`, dan `vendor` juga terlacak karena `.gitignore` kosong.
- Enam endpoint cron melakukan perubahan data melalui URL publik dan belum memiliki autentikasi khusus.
- CSRF global belum aktif dan beberapa aksi mutasi memakai route GET.

Sebelum production, rotasi seluruh kredensial, pindahkan secret ke `.env`, upgrade dependency ke versi aman yang kompatibel (untuk CodeIgniter, audit saat ini meminta minimal 4.7.4), lalu regression test seluruh proses login/OAuth/sinkronisasi. Jalankan ulang:

```bash
composer audit --locked --no-dev
composer test
```

Deployment production sebaiknya ditunda sampai audit runtime tidak lagi memiliki advisory yang belum diterima secara sadar.

## Persyaratan hosting

- PHP 8.1 atau lebih baru; pilih versi stabil hosting yang sudah diuji dengan aplikasi.
- MySQL 8 atau MariaDB yang kompatibel dengan `ROW_NUMBER()`.
- Ekstensi `curl`, `fileinfo`, `gd`, `intl`, `json`, `mbstring`, `mysqli`, `mysqlnd`, `openssl`, `xml`, dan `zip`.
- Apache `mod_rewrite` dan dukungan `.htaccess`.
- Akses outbound HTTP/HTTPS dan DNS ke API sumber serta Accurate.
- File Manager/SFTP, phpMyAdmin, cron, SSL, dan idealnya SSH/Terminal.
- Memory limit 256 MB atau lebih disarankan untuk spreadsheet dan sinkronisasi.

Tidak ada queue worker. Session, cache, dan log menggunakan file di `writable`.

## Pilihan struktur hosting

Struktur paling aman adalah menyimpan `app`, `vendor`, `writable`, dan `.env` di luar `public_html`, lalu hanya meletakkan front controller dan aset publik di `public_html`. Namun kode sekarang menyimpan avatar ke `ROOTPATH/upload`, sedangkan browser membacanya dari `/upload`; pemisahan ini memerlukan penyesuaian path upload atau symlink yang diizinkan hosting.

Panduan utama di bawah menggunakan struktur existing yang kompatibel tanpa perubahan kode:

```text
/home/CPANEL_USER/public_html/
├── app/
├── assets/
├── upload/
├── vendor/
├── writable/
├── .env
├── .htaccess
└── index.php
```

Karena source berada di document root, proteksi `.htaccess` pada langkah 5 wajib. Untuk hardening terbaik, rencanakan refactor ke struktur private/public setelah deployment awal stabil.

## 1. Backup dan siapkan artifact

Backup file dan database production sebelum mengganti release.

Jika hosting memiliki Composer/SSH, upload source tanpa `vendor`, lalu jalankan:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
```

Jika Composer tidak tersedia di hosting:

1. Jalankan perintah tersebut di komputer dengan versi PHP yang kompatibel dengan hosting.
2. Arsipkan aplikasi beserta `vendor` yang sudah lengkap.
3. Keluarkan `.git`, `.env` lokal, `tests`, `error_log`, serta isi cache/log/session dari artifact.

Jangan menjalankan `composer update` langsung di production.

## 2. Buat atau siapkan database

1. Di **MySQL Databases**, buat database dan user.
2. Hubungkan user ke database dengan privilege yang diperlukan.
3. Untuk instalasi baru, biarkan database kosong; migration akan membuat schema.
4. Untuk pemindahan sistem existing, import dump melalui **phpMyAdmin** agar data lama tetap tersedia.

Catat hostname, nama database, username, password, dan port. Nama database/user cPanel umumnya memakai prefix akun.

## 3. Upload aplikasi

1. Upload artifact melalui File Manager atau SFTP ke document root domain, misalnya `public_html`.
2. Extract artifact.
3. Pastikan `index.php`, `.htaccess`, `app`, `assets`, `vendor`, `upload`, dan `writable` tepat di document root, bukan dalam subfolder tambahan.
4. Hapus arsip deployment setelah extraction.

`router.php` hanya untuk server lokal dan tidak perlu di-upload.

## 4. Konfigurasi `.env` production

Buat/edit `.env` di document root:

```dotenv
CI_ENVIRONMENT = production
app.debug = false
app.baseURL = 'https://domain-anda.example/'
app.indexPage = ''
app.appTimezone = 'Asia/Jakarta'

database.default.hostname = localhost
database.default.database = CPANEL_USER_nama_database
database.default.username = CPANEL_USER_nama_user
database.default.password = 'password-database-kuat'
database.default.DBDriver = MySQLi
database.default.DBPrefix =
database.default.port = 3306
database.default.DBDebug = false

ACCURATE_REDIRECT_URI = 'https://domain-anda.example/auth/callback'

SEED_ADMIN_USERNAME = 'admin'
SEED_ADMIN_PASSWORD = 'password-production-minimal-12-karakter'
SEED_ACCURATE_CLIENT_ID = ''
SEED_ACCURATE_CLIENT_SECRET = ''
```

Ketentuan penting:

- Gunakan HTTPS dan trailing slash pada `app.baseURL`.
- Callback `.env` dan portal developer Accurate harus identik.
- Client ID/Secret alur aktif tersimpan per pengguna di `Ms_UserDetail` dan dikelola melalui menu **Settings**; batasi akses menu tersebut dan lindungi datanya.
- Jangan menggunakan kredensial lokal atau development.
- Pastikan seluruh hardcoded credential di source sudah dipindah ke environment dan dirotasi.
- Lindungi `.env`; jangan memasukkannya ke artifact publik atau Git.

## 5. Jalankan migration dan seeder

Untuk instalasi baru yang database-nya kosong:

```bash
php spark migrate --all
php spark db:seed DatabaseSeeder
```

Setelah seed berhasil:

1. Uji login memakai `SEED_ADMIN_USERNAME` dan `SEED_ADMIN_PASSWORD`.
2. Hapus `SEED_ADMIN_PASSWORD` dan optional secret seed dari `.env`; akun sudah tersimpan dengan password hash.
3. Isi/ubah Client ID dan Secret Accurate melalui menu **Settings** jika tidak diberikan saat seed.

Seeder dapat dijalankan ulang dan akan memperbarui akun dengan username yang sama. Jangan menjalankan seeder tanpa sengaja pada username admin existing karena password-nya akan diganti.

Untuk database existing yang sudah memiliki tabel/data, backup terlebih dahulu lalu jalankan:

```bash
php spark migrate --all
```

Migration memakai pembuatan tabel `IF NOT EXISTS`, sehingga tidak menghapus tabel existing, tetapi juga tidak memvalidasi bahwa struktur lamanya identik. Jangan jalankan `migrate:rollback --all` di production karena perintah tersebut menghapus tabel aplikasi beserta data.

## 6. Hardening `.htaccess`

Gunakan konfigurasi berikut sebagai dasar:

```apacheconf
DirectoryIndex index.php
Options -Indexes

<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /

    RewriteRule ^(?:app|vendor|writable|tests)(?:/|$) - [F,L,NC]

    # Layani file dan direktori publik yang benar-benar tersedia.
    RewriteCond %{REQUEST_FILENAME} -f [OR]
    RewriteCond %{REQUEST_FILENAME} -d
    RewriteRule ^ - [L]

    # Arahkan request lainnya ke front controller CodeIgniter.
    RewriteRule ^ index.php [L]
</IfModule>

<FilesMatch "^(?:\.env|composer\.(?:json|lock)|phpunit\.xml\.dist|spark|preload\.php|router\.php|error_log)$">
    <IfModule authz_core_module>
        Require all denied
    </IfModule>
    <IfModule !authz_core_module>
        Order allow,deny
        Deny from all
    </IfModule>
</FilesMatch>
```

Catatan:

- `DirectoryIndex index.php` diperlukan agar request `/` membuka front controller. Tanpa directive ini, sebagian hosting membalas 403 karena `/` dianggap direktori sementara directory listing dimatikan oleh `Options -Indexes`.
- Jangan menyalin blok `AddHandler` PHP dari hosting lama. Pilih versi PHP melalui **MultiPHP Manager** agar cPanel menghasilkan handler yang tersedia di server tersebut. Handler `ea-php85` existing belum menjadi versi yang diverifikasi proyek.
- Directive `php_value` dapat menghasilkan HTTP 500 pada PHP-FPM. Atur `max_execution_time` dan `max_input_vars` melalui **MultiPHP INI Editor** atau `.user.ini` jika itu terjadi.
- Untuk instalasi subfolder `/sync/`, gunakan `RewriteBase /sync/` dan `app.baseURL = 'https://domain.example/sync/'`.
- Jangan tampilkan error PHP di production; gunakan `writable/logs`.

### Jika muncul HTTP 403

Lakukan pengujian berikut:

1. Buka `https://domain.example/index.php`. Jika URL ini bekerja tetapi `/` tetap 403, periksa `DirectoryIndex index.php`.
2. Buka satu aset, misalnya `/assets/css/bootstrap.min.css`. Jika aset bekerja tetapi `index.php` 403, periksa handler PHP dan permission file.
3. Jika semua URL 403, pastikan file berada di document root domain dan permission direktori/file benar.
4. Periksa menu cPanel **Errors**, Apache error log, atau LiteSpeed error log. Pesan `Directory index forbidden` menunjukkan masalah `DirectoryIndex`.

Jika `Options -Indexes` menghasilkan HTTP 500 karena directive `Options` tidak diizinkan provider, hapus hanya baris tersebut. Proteksi direktori sensitif tetap ditangani oleh aturan rewrite dan `.htaccess` bawaan dalam `app` serta `writable`.

## 7. Permission

Jika SSH tersedia:

```bash
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;
chmod -R 775 writable upload
chmod 600 .env
```

Pastikan proses PHP dapat menulis ke:

- `writable/cache`
- `writable/logs`
- `writable/session`
- `writable/uploads`
- `upload`

Hindari `chmod 777`. Pada hosting tertentu, permission 755 sudah cukup karena PHP berjalan sebagai user akun.

## 8. Optimasi

Jika terminal tersedia:

```bash
php spark cache:clear
php spark optimize
php spark env
php spark config:check App
```

Pastikan output environment adalah `production`. Jika PHP CLI berbeda dari PHP web, gunakan binary yang disediakan hosting atau jalankan hanya perintah yang kompatibel.

## 9. Cron sinkronisasi

Endpoint yang tersedia:

```text
/cron/auto-sync
/cron/auto-sync-pembelian
/cron/auto-sync-invoice-pembelian
/cron/auto-sync-retur-pembelian
/cron/auto-sync-invoice-penjualan
/cron/auto-sync-retur-penjualan
```

Endpoint tersebut memproses data H-1 zona Asia/Jakarta dan dapat melakukan delete/insert. Sebelum mengaktifkan cron, implementasikan secret/token atau pembatasan IP dan pencegahan overlapping. Jangan mempublikasikan URL ini tanpa proteksi.

Setelah diproteksi, contoh command cPanel setiap 15 menit:

```bash
/usr/bin/curl --fail --silent --show-error --max-time 300 'https://domain-anda.example/cron/auto-sync?token=SECRET_ACAK' >> /home/CPANEL_USER/cron-bangunan-abadi.log 2>&1
```

Parameter token di atas hanya contoh dan belum didukung kode existing. Implementasikan validasinya terlebih dahulu. Beri jadwal berbeda untuk setiap endpoint agar tidak berebut CPU, memory, atau koneksi database.

## 10. Verifikasi deployment

```bash
curl -I https://domain-anda.example/
curl -I https://domain-anda.example/assets/css/bootstrap.min.css
curl -I https://domain-anda.example/upload/logo.png
```

Checklist verifikasi:

- Login, CSS, dan logo merespons HTTP 200.
- Form login mengarah ke domain production.
- HTTPS valid tanpa redirect loop.
- Login akun uji berhasil.
- OAuth kembali ke `/auth/callback` domain production.
- Database Accurate dapat dipilih.
- Satu sinkronisasi data uji berhasil dan log tercatat.
- `/.env`, `/app/Config/Database.php`, `/vendor/`, dan `/writable/logs/` merespons 403/404.
- `composer audit --locked --no-dev` sudah ditinjau dan tidak menyisakan advisory yang belum ditangani.

Untuk error, periksa `writable/logs`, menu cPanel **Errors**, `error_log`, dan log cron.

## Checklist go-live

- [ ] Backup file dan database tersedia.
- [ ] Semua secret lama sudah dirotasi dan dipindah ke environment.
- [ ] Advisory dependency sudah ditangani dan regression test lulus.
- [ ] PHP dan ekstensi memenuhi persyaratan.
- [ ] Database baru sudah disiapkan atau data existing sudah di-import.
- [ ] Migration berhasil dan akun awal sudah dibuat untuk instalasi baru.
- [ ] Environment production, debug off, dan base URL benar.
- [ ] Callback Accurate memakai HTTPS/domain production.
- [ ] Permission `writable` dan `upload` benar.
- [ ] Source, `.env`, dependency, dan log tidak dapat diakses publik.
- [ ] Login, OAuth, aset, database, dan sinkronisasi uji berhasil.
- [ ] Endpoint cron sudah diautentikasi, diberi jeda, timeout, dan log.
- [ ] Artifact sementara sudah dihapus dari document root.

## Rollback

1. Nonaktifkan cron agar tidak ada sinkronisasi saat rollback.
2. Kembalikan file release dan `.env` sebelumnya.
3. Restore database hanya jika deployment mengubah data/schema dan rollback database diperlukan.
4. Jalankan `php spark cache:clear` jika terminal tersedia.
5. Uji halaman login, aset, database, serta Accurate sebelum mengaktifkan cron kembali.
