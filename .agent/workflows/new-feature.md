# Workflow: Membangun Fitur Baru (End-to-End Implementation)

Checklist ini wajib diikuti setiap kali membangun fitur atau modul baru dari tahap perancangan hingga pengujian.

---

## Tahap 1: Validasi & Riset Scope
- [ ] **Pelajari Dokumen Spesifikasi:** Baca detail aturan bisnis di [`docs/information-design/`](file:///e:/5_projects/yzd-trading-management/docs/information-design) dan panduan uji di [`docs/verifikasi-model/`](file:///e:/5_projects/yzd-trading-management/docs/verifikasi-model).
- [ ] **Pencegahan Redundansi:** Periksa apakah model, controller, atau komponen serupa sudah ada di basis kode sebelum membuat baru.
- [ ] **Konsultasi Rules:** Baca file terkait di [`.agent/rules/`](file:///e:/5_projects/yzd-trading-management/.agent/rules) (terutama Arsitektur, Frontend, Database, dan Security).

---

## Tahap 2: Penyusunan Rencana (Implementation Plan)
- [ ] Buat dokumen rencana kerja terperinci (`implementation_plan.md`).
- [ ] Uraikan daftar file yang akan dibuat/diubah, rancangan skema tabel, validasi input, alur UI, dan rencana pengujian otomatis.
- [ ] **Tunggu Approval Pengguna:** Dilarang mulai menulis kode sumber sebelum pengguna menyetujui rencana tersebut.

---

## Tahap 3: Database & Skema Eloquent
- [ ] **Buat File Migrasi:**
  ```powershell
  & "C:\laragon\bin\php\php-8.3.12-nts-Win32-vs16-x64\php.exe" artisan make:migration create_{table}_table
  ```
- [ ] Gunakan primary key `$table->id()`.
- [ ] Gunakan `decimal(15, 2)` untuk seluruh nilai uang/keuangan.
- [ ] Pasang index untuk kolom yang sering difilter (`status`, `user_id`, `created_at`).
- [ ] Jalankan migrasi:
  ```powershell
  & "C:\laragon\bin\php\php-8.3.12-nts-Win32-vs16-x64\php.exe" artisan migrate
  ```
- [ ] **Buat Model Eloquent:**
  - Definisikan `$fillable` eksplisit.
  - Pasang method `casts(): array` (misal `'encrypted'` untuk kredensial, `datetime`, `decimal:2`).
  - Definisikan relasi dengan return type hint lengkap (`BelongsTo`, `HasMany`).

---

## Tahap 4: Backend Logic & HTTP Layer
- [ ] **Buat Form Request:** Validasi input ketat, pesan Bahasa Indonesia, kapasitas validasi kolom TEXT hingga `max:20000`.
- [ ] **Buat Service Layer:**
  - Bungkus penulisan multi-tabel dalam `DB::transaction()`.
  - Logika kalkulasi finansial (profit, 4-tier komisi, eligibilitas keaktifan).
  - Simpan berkas privat ke disk `'local'` (tanpa prefiks `"private/"`).
  - Siapkan payload data siap-tampil (*view-ready payload*) dengan tanggal ringkas (`tanggal_singkat_waktu`) dan format `rupiah()`.
- [ ] **Buat Notifikasi In-App Database:** Kirim pemberitahuan saat data diajukan atau diproses.
- [ ] **Buat Controller:** Thin Controller, delegasikan logika ke Service, kirim redirect dengan `with('success', ...)`.
- [ ] **Daftarkan Rute di `routes/web.php`:** Pasang middleware peran (`role`) dan status aktif (`ensure.active`).
- [ ] **Streaming Berkas Privat:** Daftarkan rute streaming aman di `DocumentStreamController` jika fitur melibatkan unggah berkas sensitif.

---

## Tahap 5: Frontend UI & Standar Ekosistem
- [ ] **Halaman Vue (`Pages/`):** Gunakan Vue 3 Composition API dengan `<script setup>`.
- [ ] **Komponen Ekosistem:**
  - Manfaatkan `AppDataTable.vue` dengan `paramPrefix` jika ada tabel multi-tab.
  - Gunakan `AppModal.vue` untuk formulir (bebas pergeseran layout / *zero layout shift*).
  - Gunakan `AppConfirmDialog.vue` untuk aksi destruktif.
  - Gunakan Button dari `@/Components/ui/button`.
  - Gunakan ikon dari `lucide-vue-next`.
- [ ] **Mobile-First & Feature Parity:** Pastikan seluruh filter dan aksi dapat digunakan nyaman pada layar ponsel (360px – 430px) tanpa disembunyikan.
- [ ] **Anti-Hover Dependency:** Jangan sembunyikan teks penting di balik tooltip/hover; sediakan tombol buka atau modal rincian.
- [ ] **Anti-Low Contrast:** Pastikan kontras teks tajam (WCAG AA) dan seimbang pada Light Mode maupun Dark Mode, termasuk saat state hover.
- [ ] **Anti-AI Slop:** Hindari efek visual norak, animasi liar, atau copywriting hiperbolis.
- [ ] **Kompresi Gambar Senyap:** Form unggah gambar wajib mengintegrasikan `imageCompression.js` di latar belakang.

---

## Tahap 6: Sinkronisasi 4-Titik (Khusus Form & Helper)
Jika fitur melibatkan penambahan field form pendaftaran/data profil/KYC, sinkronkan di 4 titik:
1. Backend (FormRequest, Model, Migration).
2. Frontend (`useForm`, template formulir & kartu ringkasan).
3. Dev Tool ([`DevRegisterAutoFill.vue`](file:///e:/5_projects/yzd-trading-management/src/resources/js/Components/Common/DevRegisterAutoFill.vue)).
4. Automated Feature Tests.

---

## Tahap 7: Automated Testing & Verifikasi Mutu
- [ ] Buat Feature Test baru di `tests/Feature/{FeatureName}Test.php`.
- [ ] Uji skenario sukses (*happy path*), skenario batas (*edge cases*), dan pembatasan hak akses (*authorization barriers*).
- [ ] Jalankan verifikasi menyeluruh:
  ```powershell
  # 1. Format Kode Pint
  & "C:\laragon\bin\php\php-8.3.12-nts-Win32-vs16-x64\php.exe" vendor/bin/pint --test app database routes tests

  # 2. Automated Tests
  & "C:\laragon\bin\php\php-8.3.12-nts-Win32-vs16-x64\php.exe" artisan test --compact

  # 3. Frontend Build
  npm run build
  ```
- [ ] **Audit Git Diff:** Pastikan bebas baris duplikat (*Zero Duplicate Policy*).
- [ ] Buat atau perbarui dokumen panduan verifikasi di [`docs/verifikasi-model/`](file:///e:/5_projects/yzd-trading-management/docs/verifikasi-model).
- [ ] Laporkan hasil ke pengguna dan **tunggu persetujuan eksplisit sebelum melakukan commit**.
