# Workflow: Debug Session

Alur sistematis saat menghadapi error atau bug.

## Langkah 1: Identifikasi Error
- [ ] Baca pesan error secara lengkap (jangan hanya baris pertama)
- [ ] Catat: jenis error, file, baris, stack trace
- [ ] Reproduksi error secara konsisten (pastikan bisa diulang)

## Langkah 2: Kumpulkan Informasi
- [ ] Cek log: storage/logs/laravel.log
- [ ] Jika error di browser: cek console (F12)
- [ ] Jika error 500: pastikan APP_DEBUG=true di local untuk lihat detail
- [ ] Identifikasi input apa yang memicu error

## Langkah 3: Analisis Root Cause
- [ ] Lacak alur: route -> middleware -> controller -> service -> model
- [ ] Cek apakah ada data null/undefined yang tidak dihandle
- [ ] Cek apakah ada type mismatch (string vs int, dll.)
- [ ] Cek apakah ada permission issue (403 yang tidak diharapkan)
- [ ] Cek apakah ada N+1 query yang menyebabkan timeout

## Langkah 4: Buat Hipotesis
- Identifikasi kemungkinan penyebab (maksimal 3 hipotesis)
- Mulai dari yang paling mungkin
- Jangan ubah kode sebelum yakin dengan root cause

## Langkah 5: Fix
- [ ] Perbaiki root cause, bukan symptom
- [ ] Pastikan fix tidak merusak fungsionalitas lain
- [ ] Jika melibatkan kalkulasi bisnis: verifikasi dengan rules 05-database.md
- [ ] Format kode setelah fix: vendor/bin/pint --dirty

## Langkah 6: Verifikasi
- [ ] Error sudah tidak muncul saat direproduksi
- [ ] Test terkait masih hijau: php artisan test --compact
- [ ] Tidak ada regresi di area lain
- [ ] Commit fix dengan pesan: fix({scope}): {deskripsi singkat}

---

## Perintah Debug Berguna

Cek route terdaftar:
  php artisan route:list --name=investor

REPL untuk test kode:
  php artisan tinker

Cek konfigurasi environment:
  php artisan about

Clear semua cache:
  php artisan optimize:clear

Cek status migration:
  php artisan migrate:status

Cek struktur tabel:
  php artisan db:table investors

Cek permission cache (jika ada issue role):
  php artisan permission:cache:reset

---

## Tips Spesifik Proyek YZD Center

Jika error di kalkulasi komisi:
- Verifikasi nilai modal disimpan sebagai integer (bukan float)
- Cek tier yang dipakai: premium >= 1M, standard >= 100Jt, basic < 100Jt
- Cek apakah masa berlaku 3 bulan sudah expired

Jika error invitation link:
- Cek kolom used_at: sudah diisi berarti token sudah dipakai
- Cek kolom expired_at: bandingkan dengan now()
- Verifikasi token tidak terpotong saat dikirim via URL

Jika error stepper CopyTrade:
- Cek urutan step: step 2 tidak bisa diakses sebelum step 1 approved
- Cek status verifikasi admin di tabel yang sesuai
