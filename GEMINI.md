# MASTER RULES (LEAN & FAST EXECUTION) — YZD Center

---

## 0. Master Index (Peta Aturan & Alur Kerja ke `.agent/`)
Detail teknis dan checklist mendalam didelegasikan ke folder [`.agent/`](file:///e:/5_projects/yzd-trading-management/.agent):

- **Rules Teknis ([`.agent/rules/`](file:///e:/5_projects/yzd-trading-management/.agent/rules)):**
  [`01-behavior.md`](file:///e:/5_projects/yzd-trading-management/.agent/rules/01-behavior.md) (perilaku/trust), [`02-tech-stack.md`](file:///e:/5_projects/yzd-trading-management/.agent/rules/02-tech-stack.md) (stack/CLI), [`03-architecture.md`](file:///e:/5_projects/yzd-trading-management/.agent/rules/03-architecture.md) (thin controller/service/stream), [`04-coding-conventions.md`](file:///e:/5_projects/yzd-trading-management/.agent/rules/04-coding-conventions.md) (naming/helpers/pint), [`05-database.md`](file:///e:/5_projects/yzd-trading-management/.agent/rules/05-database.md) (decimal/encrypted), [`06-security.md`](file:///e:/5_projects/yzd-trading-management/.agent/rules/06-security.md) (role/disk local privat/TEXT capacity), [`07-frontend.md`](file:///e:/5_projects/yzd-trading-management/.agent/rules/07-frontend.md) (UI ecosystem/mobile-first/anti-slop), [`08-testing.md`](file:///e:/5_projects/yzd-trading-management/.agent/rules/08-testing.md) (targeted testing/skema komisi 4-tier), [`09-git.md`](file:///e:/5_projects/yzd-trading-management/.agent/rules/09-git.md) (git policy/diff check), [`10-deployment.md`](file:///e:/5_projects/yzd-trading-management/.agent/rules/10-deployment.md) (env/production).
- **Workflows ([`.agent/workflows/`](file:///e:/5_projects/yzd-trading-management/.agent/workflows)):**
  [`new-feature.md`](file:///e:/5_projects/yzd-trading-management/.agent/workflows/new-feature.md), [`code-review.md`](file:///e:/5_projects/yzd-trading-management/.agent/workflows/code-review.md), [`debug-session.md`](file:///e:/5_projects/yzd-trading-management/.agent/workflows/debug-session.md), [`new-migration.md`](file:///e:/5_projects/yzd-trading-management/.agent/workflows/new-migration.md), [`requirement-session.md`](file:///e:/5_projects/yzd-trading-management/.agent/workflows/requirement-session.md).

---

## 1. Alur Eksekusi Cepat (Fast Inner-Loop vs Final Outer-Loop)
Untuk menjaga kecepatan respon agentic tanpa mengorbankan mutu:

- **Inner-Loop (Selama Ngoding / Iterasi Berlangsung):**
  - **Dilarang Full Test Suite (116+ test):** Jalankan **hanya** targeted test pada file yang sedang diubah (misal: `artisan test tests/Feature/CommissionClaimTest.php`) agar selesai dalam 2–4 detik.
  - **Dilarang Full Build Vite (`npm run build`) berulang kali:** Vite Dev Server menangani HMR secara instan. Lewati build produksi selama masih dalam proses iterasi.
  - **Pint Terarah:** Gunakan `vendor/bin/pint --dirty` (hanya format file yang dimodifikasi).
  - **Bias for Action:** Kerjakan langsung instruksi yang jelas tanpa bolak-balik bertanya untuk keputusan minor.

- **Outer-Loop (Hanya Sekali Sebelum Meminta Approval Commit/Push):**
  - Jalankan `vendor/bin/pint --dirty` atau `vendor/bin/pint app database routes tests`.
  - Jalankan full test: `artisan test --compact`.
  - Jalankan build produksi: `npm run build`.
  - Periksa `git diff` untuk zero duplicate lines.

---

## 2. Kebijakan Git & Branching (develop & main)
- **Branch Aktif Utama (`develop`):** Pengerjaan sehari-hari (fitur, bugfix, refactor) langsung di-commit & push ke branch `develop` (*Trunk-based on develop*) setelah disetujui.
- **Branch Produksi Terkunci (`main`):** Branch `main` khusus lingkungan produksi. **DILARANG** direct commit atau push langsung ke `main`.
- **Merge ke `main` (Rilis Produksi):** Merge dari `develop` ke `main` **HANYA** dilakukan saat ada perintah eksplisit dari USER. Menggunakan `git merge --no-ff develop` dan disematkan Git Tag versi SemVer (misal: `v1.0.1`).
- **Semua Perbaikan via `develop`:** Termasuk perbaikan darurat / hotfix produksi, selalu kerjakan dan uji di `develop` terlebih dahulu sebelum di-merge ke `main`.
- **DILARANG OTOMATIS COMMIT DAN PUSH:** Jangan pernah jalankan `git commit` maupun `git push` tanpa persetujuan eksplisit dari USER.
- **Pengecekan Kode Duplikat:** Sebelum meminta izin commit, pastikan `git diff` bersih dari tag duplikat, pesan validasi berulang, atau residu teks AI.
- **Conventional Commits:** Gunakan format standar (`feat(...)`, `fix(...)`, dll.).

---

## 3. Eksekusi CLI & Lingkungan Laragon
- **PHP CLI Baku:** Selalu gunakan PHP 8.3 Laragon:
  `& "C:\laragon\bin\php\php-8.3.12-nts-Win32-vs16-x64\php.exe"`
- **Format Pint:** `vendor/bin/pint --dirty` (konfigurasi: [`src/pint.json`](file:///e:/5_projects/yzd-trading-management/src/pint.json)).

---

## 4. Standar UI Ekosistem & Frontend
- **Toast Tunggal:** Flash message controller (`with('success', ...)`) ditangkap otomatis oleh `AppToaster.vue`. Dilarang menduplikasi `toast.success()` di callback Inertia.
- **Komponen Ekosistem:** Wajib memprioritaskan komponen terpadu yang sudah ada di `@/Components/Common/` (*No Reinventing the Wheel*):
  - `AppButton.vue`: Wajib digunakan untuk seluruh tombol aksi, tombol header/toolbar, tombol navigasi/link Inertia, tombol form submit/batal, dan tombol ber-state loading/icon (dengan preset `type="create"|"add"|"save"|"submit"|"cancel"|"back"|"export"|"custom"`). Dilarang membuat tag `<button>` mentah jika `AppButton` dapat digunakan. **Anti-Ikon Duplikat:** Preset `type` sudah otomatis merender ikon bawaan dan label baku; gunakan prop `label="..."` untuk teks kustom dan tag self-closing `<AppButton ... />`. Dilarang memasukkan elemen ikon manual di dalam slot jika preset `type` sudah berikon. Gunakan prop `:icon="CustomIcon"` untuk mengganti ikon, atau `:icon="false"` untuk menghilangkan ikon preset.
  - `AppDataTable.vue`: Wajib untuk tabel data (wajib `paramPrefix` jika multi-tab, format tanggal ringkas `tanggal_singkat_waktu`).
  - `AppModal.vue`: Wajib untuk formulir modal & pop-up rincian (zero layout shift).
  - `AppConfirmDialog.vue`: Wajib untuk dialog konfirmasi aksi berisiko/destruktif (hapus, batalkan, nonaktifkan).
  - `AppTableAction.vue`: Wajib untuk tombol aksi baris di dalam tabel data.
  - `AppPageHeader.vue`: Wajib untuk breadcrumb dan judul halaman.
  - Ikon: Wajib dari `lucide-vue-next`.
- **Mobile-First & Anti-Hover:** Fitur harus utuh di HP (360px–430px, touch target >= 36-44px). Dilarang menyembunyikan filter (`hidden md:...`). Dilarang bergantung pada hover untuk teks terpotong (sediakan tombol baca/modal).
- **Anti-Low Contrast:** Standar WCAG AA (teks solid, status badge kontras tinggi, kontras terjaga saat hover, seimbang Light & Dark mode).
- **Anti-AI Slop:** Bebas gradien neon/purple glow, bebas animasi liar, bebas copywriting klise AI, bebas div soup.
- **Kompresi Gambar Senyap:** [`imageCompression.js`](file:///e:/5_projects/yzd-trading-management/src/resources/js/lib/imageCompression.js) berjalan otomatis di latar belakang tanpa modal peringatan.

---

## 5. Standar Backend, Storage & Keamanan
- **Penyimpanan Berkas Privat:** Root disk `'local'` sudah berada di `storage/app/private`. Dilarang menyertakan prefiks `"private/"` pada pemanggilan `storeAs()` atau `Storage::disk('local')`.
- **Validasi Kolom TEXT:** Batas validasi form untuk kolom `TEXT` database wajib memadai (hingga `max:20000`).
- **Streaming Aman:** Berkas sensitif (KTP, perjanjian, bukti transfer) dialirkan via `DocumentStreamController` dengan cek otorisasi kepemilikan/admin.
- **Enkripsi Kredensial:** Password MT4 dan VPS menggunakan Eloquent cast `'encrypted'`.
- **Migrasi Skema Produksi (File Migrasi Baru / Zero Data Loss):** Karena aplikasi telah berada di fase produksi, **DILARANG** mengubah file migrasi `create` yang sudah pernah berjalan atau mengeksekusi `migrate:fresh`. Setiap penambahan/modifikasi kolom, tabel baru, atau penyesuaian index wajib dibuatkan **file migrasi baru** (`artisan make:migration add_..._to_..._table` atau `alter_...`) dengan fungsi `up()` dan `down()` yang simetris, lalu dijalankan menggunakan `php artisan migrate`.
- **Sinkronisasi 4-Titik:** Setiap penambahan field formulir disinkronkan di FormRequest, Model/Migration, Frontend (`useForm`), DevTool (`DevRegisterAutoFill.vue`), dan Feature Tests.
