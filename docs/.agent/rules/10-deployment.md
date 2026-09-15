# Rules 10 - Deployment and Environment

## Environment Files

- File .env WAJIB ada di semua environment, jangan di-commit ke Git
- Gunakan .env.example sebagai template yang di-commit
- Semua nilai sensitif (API key, DB password, secret) hanya di .env
- Jangan hardcode nilai dari .env di kode -- selalu lewat config()

---

## Environment Variables Penting

APP_NAME=YZD Center
APP_ENV=production (production/staging/local)
APP_KEY= (generate: php artisan key:generate)
APP_URL=https://domain.com
APP_DEBUG=false (wajib false di production)

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=yzd_trading
DB_USERNAME=
DB_PASSWORD=

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

INVITATION_EXPIRE_HOURS=24

---

## Konfigurasi Bisnis (config/business.php)

Nilai persentase bisnis TIDAK boleh hardcode di kode -- simpan di config:

profit_share:
  investor_pct: 20         (20% dapat ditarik investor)
  master_trader_pct: 25    (25% performance fee Pak Abi)
  operasional_pct: 5       (5% biaya operasional)
  compounding_pct: 50      (50% reinvestasi)

commission_tiers:
  premium:  min_modal 1.000.000.000, komisi_pct 50
  standard: min_modal 100.000.000,   komisi_pct 40
  basic:    modal < 100jt,           komisi_pct 30

affiliation_duration_months: 3
invitation_expire_hours: 24

---

## Command Setelah Deploy

Urutan yang benar setelah deploy ke server:
1. composer install --no-dev --optimize-autoloader
2. php artisan config:cache
3. php artisan route:cache
4. php artisan view:cache
5. php artisan migrate --force
6. php artisan storage:link
7. php artisan queue:restart (jika pakai queue)
8. npm run build

---

## Queue

Gunakan queue untuk proses berat:
- Kirim email notifikasi (verifikasi dokumen, approval komisi)
- Generate PDF dokumen legal
- Kalkulasi batch komisi afiliasi

Jangan jalankan proses berat di request-response cycle.

---

## Storage

- Dokumen sensitif (KTP, surat bermaterai): disk private
- Selalu jalankan php artisan storage:link setelah fresh install
- Jangan expose path storage langsung -- gunakan signed URL atau stream

---

## DILARANG di Production

- APP_DEBUG=true di production
- DemoSeeder dijalankan di production (sudah ada guard di kode)
- php artisan migrate:fresh di production (hapus semua data!)
- Expose .env via URL apapun
- composer install tanpa --no-dev (jangan install dev dependency di production)
