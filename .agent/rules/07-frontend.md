# Rules 07 — Frontend (Vue 3 + Inertia.js + Tailwind CSS)

## Prinsip Utama Frontend

- **Vue Hanya untuk Presentasi:** Logika bisnis, kalkulasi finansial, dan pemformatan tanggal/angka utama diselesaikan di server (Service Layer).
- **Single Source of Truth:** Manfaatkan props Inertia dan flash message server.
- **Navigasi Internal via Inertia:** Gunakan `<Link>` atau `router.visit()`, dilarang `window.location.href`.

---

## Standar Komponen Ekosistem UI (Wajib Digunakan)

Dilarang membuat ulang komponen dasar secara mentah (*No Reinventing the Wheel*). Selalu gunakan komponen teruji yang sudah tersedia di `@/Components/Common/`:

### 1. `AppButton.vue` (Komponen Tombol Standar — Wajib Utama)
- **Wajib Digunakan:** Seluruh tombol aksi primer/sekunder, tombol navigasi Inertia (`href`), tombol submit/batal formulir modal, tombol aksi toolbar/filter, serta tombol dengan status loading/ikon **WAJIB** menggunakan `AppButton.vue` (`@/Components/Common/AppButton.vue`).
- **Dilarang Tag `<button>` Mentah:** Jangan membuat tag `<button>` mentah atau mengimpor langsung `@/Components/ui/button` kecuali untuk kebutuhan atomik internal tingkat rendah.
- **Preset Aksi Terstandarisasi:**
  - `type="create"` atau `type="add"`: Untuk tombol Tambah/Buat Baru (ikon Plus otomatis, varian default).
  - `type="save"` atau `type="submit"`: Untuk tombol Simpan/Kirim form (ikon Check otomatis, otomatis menangani `:loading="form.processing"` dan `:disabled="..."`).
  - `type="cancel"`: Untuk tombol Batal modal/dialog (varian outline, ikon X).
  - `type="back"`: Untuk tombol Kembali ke halaman sebelumnya (varian ghost, ikon ArrowLeft).
  - `type="export"`: Untuk tombol Ekspor data Excel/CSV (varian outline, ikon Download).
  - `type="custom"`: Untuk tombol aksi khusus dengan fleksibilitas `variant`, `size`, `icon`, dan `label`.
- **Inertia Link Otomatis:** Berikan prop `href="..."` agar `AppButton` otomatis merender `<Link>` Inertia tanpa perlu membungkusnya secara manual.
- **Area Sentuh Aman (Mobile):** Ukuran `sm` (h-8 min-h-[36px]), `md` (h-9/10 min-h-[38px]), dan `lg` (h-11 min-h-[44px]) telah memenuhi standar ergonomis mobile touch target.

### 2. `AppDataTable.vue` (Tabel Data Terpadu)
- Berbasis Native Server-Side Pagination, Debounced Search, Dynamic Sorting, dan Inertia Partial Reloads.
- **Wajib Isolasi Parameter Multi-Tab (`paramPrefix`):** Jika satu halaman memuat lebih dari satu tabel atau tab, wajib memberikan prefiks unik (misal: `paramPrefix="downline_"` dan `paramPrefix="claim_"`) agar pencarian, filter, dan paginasi antar-tab tidak saling merusak state di URL.
- **Format Tanggal Baku:** Seluruh tanggal pada baris tabel wajib diformat ringkas dan konsisten (`tanggal_singkat_waktu` $\to$ `22 Sep 2026 15:11` atau `tanggal_singkat` $\to$ `22 Sep 2026`). Dilarang menampilkan string ISO mentah (`2026-09-22T12:16:00+07:00`).
- **Slot Kustomisasi:** Gunakan slot `#filters="{ applyFilters }"` untuk filter toolbar dan `#cell-{columnKey}="{ item }"` untuk render isi baris.

### 3. `AppModal.vue` (Modal Dialog & Formulir)
- Digunakan untuk formulir input, filter lanjutan, dan dialog rincian data.
- **Bebas Pergeseran Layout (*Zero Layout Shift*):** Pastikan kontainer modal stabil dan tidak melompat ketika elemen kondisional atau pesan error muncul.
- **Pilihan Kritis Tanpa Default:** Hindari menentukan opsi default pada aksi kritis (biarkan *unselected* agar pengguna memilih secara sadar).

### 4. `AppConfirmDialog.vue` (Dialog Konfirmasi Aksi Destruktif)
- Digunakan khusus untuk konfirmasi aksi berisiko atau permanen (hapus data, batalkan pengajuan, suspend akun).
- **Tanpa Tombol 'X' di Header:** Pengguna harus membuat keputusan sadar dengan menekan tombol **Batal** atau **Konfirmasi**.

### 5. `AppTableAction.vue` (Tombol Aksi Baris Data)
- Digunakan untuk merender aksi pada baris tabel data (lihat detail, ubah, hapus, unduh berkas).
- Otomatis menyediakan dropdown aksi responsif pada layar ponsel/tablet.

### 6. `AppPageHeader.vue` (Bilah Judul & Breadcrumb)
- Digunakan di bagian atas setiap halaman konten untuk menampilkan judul halaman, subjudul deskriptif, dan jalur navigasi breadcrumb.

### 7. `AppToaster.vue` (Toast Notification — Single Source of Truth)
- Notifikasi status aksi di-trigger oleh flash message controller (`->with('success', '...')` atau `->with('error', '...')`) yang ditangkap otomatis oleh `AppToaster.vue`.
- **DILARANG MENDUPLIKASI PANGGILAN TOAST:** Jangan memanggil manual `toast.success()` di dalam callback `onSuccess` Inertia `form.post()` jika controller sudah mengirimkan flash message.
- Panggilan manual `toast.xxx()` hanya diizinkan untuk aksi interaksi *client-side murni* (misal: salin link referral ke clipboard, peringatan ukuran file sebelum upload).

### 8. Iconography Tunggal
- Wajib menggunakan ikon dari `lucide-vue-next`. Dilarang mencampur icon pack lain atau menyematkan SVG mentah yang panjang ke template.

---

## Standar Non-Fungsional: Desain Responsif & Mobile UX (Mobile-First)

- **Feature Parity di Mobile (360px – 430px):** Seluruh alur kerja, baik di portal Investor maupun panel Admin, wajib dapat dioperasikan secara utuh di layar ponsel tanpa ada fitur atau filter yang dipangkas.
- **DILARANG Menghilangkan Filter / Aksi di Mobile:** Dilarang keras menyembunyikan filter data, tombol aksi, atau kontrol penting menggunakan `hidden md:...` atau `hidden sm:...`. Tata kontrol secara bertumpuk (*stacking*) atau gunakan grid responsif (`grid grid-cols-2 gap-2 w-full sm:flex sm:w-auto`).
- **Dilarang Bergantung pada Hover untuk Membaca Teks (*Anti-Hover Dependency*):**
  - Pada perangkat layar sentuh, interaksi hover tidak tersedia.
  - Dilarang hanya mengandalkan `:title` browser atau tooltip hover untuk menampilkan teks yang terpotong (`line-clamp`, `truncate`).
  - Wajib menyediakan aksi sentuh eksplisit yang ergonomis, seperti tombol/tautan *"Baca Selengkapnya"* atau modal dialog detail.
- **Area Sentuh Ergonomis (*Touch Target*):** Seluruh tombol dan kontrol form di mobile harus memiliki tinggi sentuh minimal 36px – 44px.
- **Scroll Horizontal Tabel:** Tabel data wajib dibungkus kontainer `overflow-x-auto` yang mulus.

---

## Standar Non-Fungsional: Kontras Warna & Aksesibilitas (Anti-Low Contrast)

- **Kepatuhan Standar Aksesibilitas WCAG AA:**
  - Rasio kontras minimal **4.5:1** untuk teks normal dan **3:1** untuk teks tebal/besar/badge.
  - Teks pada latar terang wajib solid dan terbaca jelas (gunakan `text-foreground`, `text-slate-700`, atau `text-muted-foreground` berbobot medium/semibold). Hindari teks pudar seperti `text-slate-400` atau `text-zinc-300` untuk informasi penting.
- **Banner & Status Badge Kontras Tinggi:**
  - Dilarang memasangkan teks warna terang di atas latar senada (misal `text-amber-400` di atas `bg-amber-50`).
  - Gunakan pasangan warna kontras tegas: `text-amber-800 dark:text-amber-200` pada `bg-amber-500/10 border-amber-500/30`, `text-rose-800 dark:text-rose-200` pada `bg-rose-500/10 border-rose-500/30`.
- **Kontras Terjaga Saat Interaksi (Hover, Focus, Active):**
  - Saat kursor melakukan *hover* (`hover:bg-...`), warna teks dan ikon **TIDAK BOLEH** memudar atau tenggelam ke dalam warna latar baru.
- **Dukungan Seimbang Light & Dark Mode:**
  - Default aplikasi adalah Light Mode dengan dukungan penuh Dark Mode melalui varian `dark:...` atau token semantik CSS variable (`text-foreground`, `bg-card`, `border-border`).

---

## Standar Anti-AI Slop & Craftsmanship Bersih

- **Dilarang Estetika AI Klise:**
  - Dilarang menambahkan gradien ungu/violet/cyan mencolok ala template AI SaaS (*purple AI glow*, *rainbow borders*, *neon badges*), border berkilau buatan, atau efek glassmorphism murahan yang tidak relevan dengan identitas korporat trading profesional YZD Center.
  - Dilarang menambahkan animasi liar atau jitter tanpa tujuan fungsional nyata (`animate-bounce`, `animate-pulse` non-skeleton).
  - Dilarang menggunakan copywriting klise chatbot AI. Gunakan bahasa finansial yang baku, ringkas, dan lugas.
- **Dilarang "Div Soup":**
  - Hindari pembungkus elemen dengan `<div>` bersarang tanpa fungsi layout yang jelas. Jaga struktur DOM tetap ringkas.
- **Pembersihan Residu AI (Zero AI Residue):**
  - Bersihkan unused imports, variabel yang tidak terpakai, dan komentar-komentar basi buatan AI.
- **Ketahanan UX Dunia Nyata (Real-World Resilience):**
  - Seluruh layout wajib menangani teks panjang (`truncate`, `break-words`, `min-w-0`), data kosong (*empty states* informatif), dan error handling responsif.

---

## Kompresi Gambar di Belakang Layar (*Silent Background Execution*)

- Untuk seluruh formulir yang memiliki fitur unggah berkas gambar (KTP, selfie, surat perjanjian, bukti modal deposit MT4, struk transfer komisi), proses kompresi client-side ([`imageCompression.js`](file:///e:/5_projects/yzd-trading-management/src/resources/js/lib/imageCompression.js)) **wajib berjalan otomatis di latar belakang**.
- Dilarang memunculkan dialog konfirmasi atau banner peringatan kompresi yang mengganggu kenyamanan pengguna.
- Kompresi wajib menjaga ketajaman teks dokumen dan angka transaksi.
