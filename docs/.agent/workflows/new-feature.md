# Workflow: Membuat Fitur Baru

Checklist ini diikuti setiap kali membuat fitur baru dari nol.

## Langkah 1: Validasi & Riset
- [ ] Baca SRS.md - pastikan fitur ada di scope dan pahami requirement-nya
- [ ] Cek apakah ada fitur serupa yang sudah dibuat (jangan duplikasi)
- [ ] Baca rules relevan di .agent/rules/ sesuai area fitur
- [ ] Identifikasi file yang perlu dibuat dan diubah

## Langkah 2: Plan
- [ ] Buat rencana: daftar file yang akan dibuat/diubah
- [ ] Identifikasi tabel baru yang dibutuhkan (jika ada)
- [ ] Identifikasi relasi antar model
- [ ] Identifikasi apakah perlu library baru - diskusikan dulu!
- [ ] Presentasikan plan ke user, tunggu approval

## Langkah 3: Database
- [ ] Buat migration: php artisan make:migration create_{table}_table
- [ ] Ikuti template migration di rules/05-database.md
- [ ] Pastikan kolom audit ada: created_by, updated_by, deleted_by, timestamps, softDeletes
- [ ] Buat factory jika diperlukan untuk testing
- [ ] Jalankan: php artisan migrate

## Langkah 4: Backend
- [ ] Buat Model: php artisan make:model {Name}
  - Set primaryKey, fillable, casts, relasi
  - Tambahkan trait SoftDeletes dan Blameable
- [ ] Buat Service di app/Services/{Name}Service.php
  - Semua business logic di sini
  - Method getDashboardData() atau sejenisnya return array siap-tampil
- [ ] Buat Form Request: php artisan make:request Store{Name}Request
  - Semua validasi di sini, bukan di controller
- [ ] Buat Controller: php artisan make:controller {Area}/{Name}Controller
  - Thin controller: hanya panggil service, return Inertia response
- [ ] Daftarkan route di routes/web.php
  - Pastikan middleware role sudah benar
  - Beri nama route sesuai konvensi dot.case

## Langkah 5: Frontend
- [ ] Buat Vue page di resources/js/Pages/{Area}/{Name}.vue
  - Gunakan script setup (Composition API)
  - Define props yang diterima dari controller
  - Assign layout yang sesuai
- [ ] Buat komponen Vue reusable jika perlu di resources/js/Components/
- [ ] Sambungkan navigasi (menu/link) ke halaman baru

## Langkah 6: Testing
- [ ] Buat feature test: php artisan make:test Feature/{Name}Test
- [ ] Minimal 1 happy path test per endpoint
- [ ] Test role-based access (user yang tidak berhak harus dapat 403)
- [ ] Jalankan test: php artisan test --compact tests/Feature/{Name}Test.php

## Langkah 7: Sebelum Commit
- [ ] Format PHP: vendor/bin/pint --dirty
- [ ] Cek tidak ada dd() atau dump(): grep -rn "dd(" app/
- [ ] Semua test masih hijau: php artisan test --compact
- [ ] Commit dengan pesan conventional: feat({scope}): {deskripsi}
