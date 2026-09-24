# Workflow: Code Review & Quality Checklist Sebelum Commit

Checklist ini wajib dievaluasi secara menyeluruh sebelum meminta persetujuan (*approval*) commit dari pengguna.

---

## 1. Format & Kualitas Sintaks Kode
- [ ] **Lolos Laravel Pint 100%:**
  ```powershell
  & "C:\laragon\bin\php\php-8.3.12-nts-Win32-vs16-x64\php.exe" vendor/bin/pint --test app database routes tests
  ```
- [ ] **Bebas Statement Debugging:** Pastikan tidak ada sisa `dd()`, `dump()`, `var_dump()`, atau `console.log()` di basis kode:
  ```powershell
  git grep -n "dd(" app/
  git grep -n "dump(" app/
  git grep -n "console.log" resources/js/
  ```
- [ ] **Audit Zero Duplicate (PENTING):** Periksa `git diff` menyeluruh untuk memastikan:
  - Tidak ada duplikasi baris kode atau blok logika berulang.
  - Tidak ada deklarasi array ganda atau tumpang-tindih tag SFC Vue.
  - Tidak ada residu teks atau komentar basi buatan AI.

---

## 2. Arsitektur & Backend Layer
- [ ] **Thin Controller:** Controller hanya menerima FormRequest, memanggil Service, dan mereturn response/redirect.
- [ ] **Fat Service:** Logika bisnis, transaksi atomik `DB::transaction()`, dan kalkulasi finansial berada di Service Layer.
- [ ] **Form Request Valid:** Seluruh input divalidasi via Form Request dengan pesan Bahasa Indonesia yang baku.
- [ ] **Kapasitas Validasi Kolom TEXT:** Input deskripsi, kronologi, catatan admin, atau alasan penolakan divalidasi hingga `max:20000` (bukan dibatasi sempit ke `1000`).
- [ ] **Penyimpanan Berkas Privat:** Pemanggilan `storeAs()` pada disk `'local'` **TIDAK** menyertakan prefiks `"private/"`.
- [ ] **Streaming Berkas Privat:** Berkas sensitif dialirkan melalui `DocumentStreamController` dengan validasi kepemilikan/role admin.
- [ ] **Bebas `env()`:** Tidak ada pemanggilan fungsi `env()` di luar direktori `config/`.
- [ ] **Bebas Hardcode URL:** Seluruh link memanfaatkan route helper (`route('nama.rute')`).

---

## 3. Frontend & Non-Fungsional UX
- [ ] **Komponen Ekosistem:** Menggunakan `AppDataTable.vue`, `AppModal.vue`, `AppConfirmDialog.vue`, `AppToaster.vue`, `@/Components/ui/button`, dan ikon `lucide-vue-next`.
- [ ] **Isolasi Multi-Tab (`paramPrefix`):** Jika ada lebih dari 1 tabel/tab, parameter URL sudah diisolasi dengan prefiks unik (misal `paramPrefix="claim_"`).
- [ ] **Format Tanggal Baku:** Seluruh tanggal tabel menggunakan format ringkas konsisten (`tanggal_singkat_waktu` $\to$ `22 Sep 2026 15:11`), tidak ada ISO string mentah.
- [ ] **Toast Single Source of Truth:** Notifikasi berhasil/gagal ditangkap otomatis dari flash message controller tanpa duplikasi `toast.success()` di callback `onSuccess` Inertia.
- [ ] **Mobile-First & Feature Parity:** Dapat digunakan nyaman di layar HP (360px – 430px) tanpa ada filter atau tombol aksi penting yang disembunyikan (`hidden md:...`).
- [ ] **Anti-Hover Dependency:** Teks yang terpotong (`truncate`) dapat dibaca via sentuhan (modal detail atau tombol ekspansi), tidak bergantung pada hover tooltip browser.
- [ ] **Kontras Warna (WCAG AA):** Teks tajam dan kontras di latar terang/gelap serta tetap kontras saat kursor melakukan *hover*.
- [ ] **Anti-AI Slop:** Bebas dari gradien neon ungu/cyan, div soup bertumpuk, atau copywriting hiperbolis ala AI chatbot.
- [ ] **Kompresi Gambar Senyap:** Formulir unggah gambar menggunakan `imageCompression.js` yang berjalan di latar belakang tanpa modal peringatan.

---

## 4. Pengujian Otomatis & Kompilasi Aset
- [ ] **Feature Tests Lolos 100%:**
  ```powershell
  & "C:\laragon\bin\php\php-8.3.12-nts-Win32-vs16-x64\php.exe" artisan test --compact
  ```
- [ ] **Kompilasi Frontend Sukses:**
  ```powershell
  npm run build
  ```

---

## 5. Kepatuhan Git
- [ ] **DILARANG Commit/Push Otomatis:** Perubahan tetap di working directory lokal sampai pengguna memberikan instruksi atau approval eksplisit.
- [ ] **Pesan Commit Terstandar:** Menyiapkan draft pesan sesuai format *Conventional Commits* (`feat(...)`, `fix(...)`, `refactor(...)`, dsb.).
