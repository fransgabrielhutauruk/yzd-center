# ATURAN PENGEMBANGAN (RULES & GUIDELINES)
**YZD Trading Management**

---

## 1. Aturan Git & Push Policy (PENTING)
- **DILARANG OTOMATIS PUSH KE REMOTE REPO (`git push`):**
  Untuk setiap perubahan kode atau fitur yang selesai dikerjakan, perubahan boleh di-commit secara lokal, tetapi **TIDAK BOLEH** langsung menjalankan `git push origin main`.
- **Wajib Menunggu Approval:**
  Selalu tunggu persetujuan eksplisit (*approval*) dari USER sebelum melakukan `git push`, karena mungkin ada revisi atau penyesuaian tambahan dari user terlebih dahulu.
- **Model Branch:**
  Trunk-based development langsung pada branch `main` (commit format: *Conventional Commits* seperti `feat(...)`, `fix(...)`, `refactor(...)`).

---

## 2. Acuan Desain & Prototype
- **Folder `prototype/` Hanyalah Gambaran Kasar:**
  Jangan mengacu 100% secara kaku ke prototype. Utamakan best practice UX modern, desain proporsional, keamanan, responsivitas, dan konsistensi sistem.

---

## 3. Standar Antarmuka Pengguna (UI Components)
- **Toast Notification:**
  Gunakan `vue-sonner` (`toast.success()`, `toast.error()`, `toast.info()`) via `AppToaster.vue` untuk feedback non-blocking (misal: salin link, flash messages).
- **Modal Dialog & Konfirmasi:**
  - Formulir input/fokus: Gunakan `AppModal.vue`.
  - Konfirmasi aksi kritis/destruktif: Gunakan `AppConfirmDialog.vue` (tanpa tombol 'X' di header).
- **Tampilan Tabel Data:**
  Gunakan `AppDataTable.vue` berbasis Native Server-Side Pagination, Debounced Search, Dynamic Sorting, dan Inertia Partial Reloads (jangan gunakan jQuery / Yajra DataTables).
- **Bahasa Antarmuka:**
  Gunakan Bahasa Indonesia untuk semua teks UI, pesan validasi, dan feedback sistem.

---

## 4. Standar Kode & Eksekusi CLI
- **PHP CLI:**
  Selalu gunakan PHP 8.3 dari Laragon:
  `& "C:\laragon\bin\php\php-8.3.12-nts-Win32-vs16-x64\php.exe"`
- **Format Kode:**
  Wajib lolos Laravel Pint sesuai aturan `src/pint.json`:
  `vendor/bin/pint --test app database routes tests`
- **Pengujian:**
  Pastikan seluruh Feature Tests lulus sebelum melaporkan ke user:
  `artisan test --compact`
- **Frontend Build:**
  Pastikan `npm run build` sukses tanpa error sebelum menyerahkan hasil kerja ke user.
