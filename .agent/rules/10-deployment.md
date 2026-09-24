# Rules 10 — Konfigurasi Environment & Deployment

## Manajemen File Environment (`.env`)

- File `.env` bersifat rahasia dan **DILARANG KERAS** di-commit ke Git.
- Gunakan file `.env.example` sebagai referensi struktur variabel yang diperbarui saat ada penambahan konfigurasi baru.
- Seluruh nilai rahasia (kunci enkripsi aplikasi, kredensial basis data, token integrasi pembayaran Mayar) wajib disimpan di `.env`.
- **DILARANG** memanggil fungsi `env()` langsung di dalam kode aplikasi — selalu bungkus dan panggil melalui file konfigurasi di folder `config/` (contoh: `config('app.name')`).

---

## Konfigurasi Bisnis Terkini (YZD Center)

Nilai-nilai acuan bisnis dikelola secara terpusat:

### 1. CMS Pengaturan Master (`pengaturan_master` table)
- **Minimum Modal Akun Copytrade (USD):**
  - Tier 1 (Cent - Broker HFM): **$100**
  - Tier 2 (Standard - Broker XM): **$5,000**
  - Tier 3 (Private VPS - Bebas Broker): **$300,000**
- **Tautan Referral Broker & Portofolio Myfxbook:** Dikelola dinamis oleh Administrator via `/admin/settings/master`.

### 2. Skema Komisi Afiliasi (Modul 05 / PB 3)
- **4-Tier Hak Komisi dari Profit Master Trader (Pak Abi):**
  - **Tier 1 (Modal $> \text{Rp } 10\text{ M}$):** **50%** dari bagian profit Master.
  - **Tier 2 (Modal $\text{Rp } 1\text{ M} - 10\text{ M}$):** **40%** dari bagian profit Master.
  - **Tier 3 (Modal $\text{Rp } 100\text{ Jt} - 1\text{ M}$):** **30%** dari bagian profit Master.
  - **Tier 4 (Modal $< \text{Rp } 100\text{ Jt}$):** **20%** dari bagian profit Master.
- **Syarat Masa Keaktifan:** Investor downline wajib aktif menyalin minimal **90 hari berturut-turut** sejak `copy_started_at`.

---

## Alur Eksekusi Deployment ke Server Produksi

Saat mendeploy pembaruan ke server produksi, jalankan tahapan berurutan:

```bash
# 1. Unduh dependensi backend produksi teroptimasi
composer install --no-dev --optimize-autoloader

# 2. Optimalisasi cache konfigurasi dan rute Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 3. Jalankan migrasi skema tabel
php artisan migrate --force

# 4. Pastikan symlink storage terhubung (untuk berkas publik)
php artisan storage:link

# 5. Restart worker queue (jika menggunakan antrean asinkron)
php artisan queue:restart

# 6. Kompilasi bundle frontend produksi
npm ci
npm run build
```

---

## Keamanan Storage & Berkas Privat di Server

- **Penyimpanan Privat:** Direktori `storage/app/private/` (tempat penyimpanan KTP, selfie, surat perjanjian investasi, bukti modal MT4, dan struk transfer komisi) **TIDAK BOLEH** dibuatkan symlink publik ke folder `public/`.
- Seluruh berkas sensitif dialirkan (*streamed*) secara privat via `DocumentStreamController` yang mewajibkan sesi autentikasi dan otorisasi hak akses.
- Pastikan direktori `storage/` dan `bootstrap/cache/` memiliki izin tulis yang tepat oleh user web server (misal `chown -R www-data:www-data storage bootstrap/cache`).

---

## Larangan Mutlak di Lingkungan Produksi (Production Safety)

- **DILARANG** menyetel `APP_DEBUG=true` di server produksi.
- **DILARANG** menjalankan `php artisan migrate:fresh` di server produksi karena akan menghapus seluruh data pengguna.
- **DILARANG** mengeksekusi `DemoSeeder` di produksi (seeder wajib dilengkapi pengaman `if (app()->isProduction())`).
- **DILARANG** mengekspos file `.env` atau direktori `.git` melalui web server virtual host.
