# Workflow: Code Review Sebelum Commit

Checklist ini dijalankan sebelum setiap commit.

## Format & Kualitas Kode
- [ ] Format PHP: vendor/bin/pint --dirty
- [ ] Cek tidak ada debug statement:
  - grep -rn "dd(" app/ resources/
  - grep -rn "dump(" app/ resources/
  - grep -rn "var_dump(" app/ resources/
  - grep -rn "console.log" resources/js/
- [ ] Tidak ada komentar yang di-comment out (kode yang tidak dipakai)
- [ ] Tidak ada TODO atau FIXME yang belum diselesaikan (kecuali sudah dicatat di task)

## Arsitektur
- [ ] Controller tidak mengandung business logic langsung (hanya memanggil service)
- [ ] Service mengandung semua kalkulasi dan transformasi data
- [ ] Vue component tidak melakukan kalkulasi bisnis
- [ ] Tidak ada env() di luar file config/
- [ ] Tidak ada hardcode URL — semua pakai route()
- [ ] Tidak ada hardcode nilai bisnis (persentase, batas modal) — semua pakai config()

## Keamanan
- [ ] Semua form POST dilindungi CSRF
- [ ] Semua endpoint sudah ada middleware role yang benar
- [ ] Tidak ada nilai sensitif di kode (password, key, token)
- [ ] File upload sudah divalidasi tipe dan ukuran

## Database
- [ ] Migration sudah punya kolom audit: created_by, updated_by, deleted_by
- [ ] SoftDeletes sudah dipakai di model bisnis baru
- [ ] Tidak ada N+1 query (eager load relasi yang dibutuhkan)

## Testing
- [ ] Test baru sudah ada untuk fitur/fix yang dibuat
- [ ] Semua test hijau: php artisan test --compact
- [ ] Test yang ada tidak rusak karena perubahan baru

## Git
- [ ] Branch name sesuai konvensi: feature/, fix/, chore/, dst.
- [ ] Commit message sesuai Conventional Commits
- [ ] Tidak ada file yang tidak perlu di-stage (.env, log, dll.)
