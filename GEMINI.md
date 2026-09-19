# ATURAN PENGEMBANGAN (RULES & GUIDELINES)
**YZD Center**

---

## 1. Aturan Git & Commit/Push Policy (PENTING)
- **DILARANG OTOMATIS COMMIT DAN PUSH (`git commit` / `git push`):**
  Untuk setiap perubahan kode atau fitur yang selesai dikerjakan, **TIDAK BOLEH** langsung menjalankan `git commit` maupun `git push origin main` secara otomatis.
- **Wajib Menunggu Approval:**
  Selalu tunggu persetujuan eksplisit (*approval*) dari USER sebelum melakukan `git commit` maupun `git push`, karena mungkin ada revisi atau penyesuaian tambahan dari user terlebih dahulu.
- **Pengecekan Kode/Baris Duplikat Sebelum Commit (Zero Duplicate Policy):**
  Sebelum membuat commit lokal maupun push, WAJIB memeriksa seluruh `git diff` secara teliti untuk mendeteksi dan membersihkan kode atau baris duplikat (seperti pesan validasi berulang, deklarasi array key ganda, re-assignment variabel yang tidak perlu, tag template/SFC ganda, atau tumpang-tindih teks akibat proses pengeditan). Pastikan setiap file benar-benar bersih dari redundansi kode.
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

## 4. Standar Non-Fungsional: Desain Responsif & Mobile UX (Mobile-First)
- **Mobile-First & Kesetaraan Fitur (Feature Parity):**
  Seluruh halaman dan alur kerja (baik portal Investor maupun panel Admin) wajib dapat digunakan dengan nyaman pada layar ponsel (viewport 360px – 430px) tanpa ada fitur yang dipangkas/hilang.
- **DILARANG Menghilangkan Filter atau Kontrol Aksi di Mobile:**
  Dilarang keras menyembunyikan filter data, tombol aksi, atau kontrol formulir esensial hanya demi kerapian visual dengan menggunakan `hidden md:...` atau `hidden sm:...`. Jika ruang layar sempit, tata elemen secara bertumpuk (*stacking*), gunakan grid responsif (`grid grid-cols-2 gap-2 w-full sm:flex sm:w-auto`), atau sheet/modal filter.
- **Tabel Data & Area Sentuh (Touch Target):**
  - Tabel data wajib memiliki pembungkus scroll horizontal (`overflow-x-auto`) yang mulus.
  - Toolbar tabel (`AppDataTable.vue`) harus responsif: input pencarian mengambil lebar penuh di HP, filter tersusun rapi di bawahnya, dan tombol baris/paginasi tidak bertumpuk/terpotong.
  - Seluruh tombol dan kontrol form di mobile harus memiliki tinggi sentuh yang ergonomis (minimal 36px – 44px).

---

## 5. Standar Non-Fungsional: Kontras Warna & Aksesibilitas Visual (Anti-Low Contrast)
- **Keterbacaan dan Ketajaman Kontras (High Contrast Standard):**
  Seluruh elemen visual (teks judul, teks isi, label formulir, placeholder, ikon, status badge, border, dan tombol) wajib memiliki tingkat kontras warna yang tajam, tegas, dan mudah dibaca (memenuhi standar aksesibilitas WCAG AA: rasio kontras minimal 4.5:1 untuk teks normal, dan 3:1 untuk teks besar, badge, atau komponen grafis penting).
- **DILARANG Desain Low Contrast (Warna Redup / Menyamar dengan Latar Belakang):**
  - **Teks pada Latar Terang:** Dilarang menggunakan teks abu-abu pudar atau tipis di atas latar belakang putih/terang (hindari `text-slate-400`, `text-gray-400`, atau `text-zinc-300` untuk teks informasi penting). Gunakan warna yang solid dan terbaca jelas seperti `text-foreground`, `text-slate-700`, atau `text-muted-foreground` berbobot tebal/medium.
  - **Banner & Status Badge:** Dilarang memasangkan teks warna terang/pastel di atas latar pastel yang senada (misal: `text-amber-400` di atas `bg-amber-50`). Selalu gunakan pasangan warna kontras tinggi: misal teks gelap pada mode terang dan teks cerah pada mode gelap (`text-amber-800 dark:text-amber-200` pada `bg-amber-500/10 border-amber-500/30`, `text-rose-800 dark:text-rose-200` pada `bg-rose-500/10 border-rose-500/30`).
- **Kontras Wajib Terjaga saat Interaksi (State Hover, Focus, dan Active):**
  - Pada saat kursor melakukan *hover* (`hover:bg-...`, `hover:text-...`), kontras antara teks/ikon dengan latar belakang **TIDAK BOLEH** memudar, menyatu, atau menjadi sulit dibaca.
  - Pastikan setiap perubahan latar saat *hover* diimbangi dengan warna teks yang tetap kontras tajam (contoh: jika latar menjadi lebih gelap/terang saat di-hover, pastikan teks tidak tenggelam ke dalam warna latar tersebut).
  - Elemen tombol interaktif harus memberikan umpan balik visual yang jelas tanpa mengorbankan keterbacaan teks/ikon di dalamnya.
- **Dukungan Light & Dark Mode yang Seimbang:**
  Pastikan setiap pemakaian utility warna eksplisit menyertakan varian mode gelap yang sepadan (`dark:...`), atau prioritaskan pemakaian semantic design tokens Tailwind/CSS variables (`text-foreground`, `text-muted-foreground`, `bg-card`, `bg-background`, `border-border`).

---

## 6. Standar Frontend: Anti-AI Slop & Craftsmanship Bersih
- **Dilarang Estetika AI Klise / Generic AI Slop:**
  - Dilarang menambahkan gradien ungu/violet/cyan mencolok ala template AI SaaS (*purple AI glow*, *rainbow borders*, *neon badges*), border berkilau buatan, atau efek glassmorphism murahan yang tidak relevan dengan identitas korporat trading profesional YZD Center.
  - Dilarang menambahkan animasi liar atau berlebihan tanpa tujuan fungsional nyata (`animate-bounce`, `animate-pulse` non-skeleton, hover jitter). Animasi harus halus, proporsional, dan mempercepat pemahaman pengguna.
  - Dilarang menggunakan teks copywriting klise khas chatbot AI (*"Elevate your trading journey"*, kata-kata hiperbolis hampa). Gunakan bahasa sistem finansial yang ringkas, baku, lugas, dan kontekstual.
- **Dilarang "Div Soup" & Over-Engineering Komponen:**
  - Dilarang membungkus elemen dengan tumpukan `<div>` bersarang tanpa fungsi layout yang jelas. Jaga struktur DOM tetap ringkas dan semantik.
  - Hindari membuat composable kustom prematur atau abstraksi rumit jika solusi dasar Vue 3 Composition API (`ref`, `computed`, `useForm`) sudah cukup jelas, efisien, dan mudah dipelihara.
- **Wajib Memanfaatkan Komponen Ekosistem yang Ada (No Reinventing the Wheel):**
  - Selalu prioritaskan komponen UI yang sudah teruji di proyek: `AppModal.vue`, `AppConfirmDialog.vue`, `AppDataTable.vue`, `AppToaster.vue`, `@/Components/ui/button`, dsb.
  - Dilarang keras mengarang ulang komponen tabel, modal, form input, atau dropdown secara terpisah dengan kode mentah jika komponen dasar sudah tersedia di codebase.
- **Konsistensi Iconography:**
  - Wajib konsisten menggunakan ikon dari `lucide-vue-next`. Dilarang mencampuradukkan icon pack lain atau menyisipkan kode SVG mentah yang panjang ke dalam template kecuali benar-benar tidak ada alternatif di Lucide.
- **Pembersihan Residu AI (Zero AI Residue & Dead Code):**
  - Bersihkan unused imports, variabel atau state yang tidak digunakan (*orphaned variables*), serta komentar-komentar basi/obvious hasil buatan AI (seperti `// Handle button click`, `// Return template`).
  - Periksa teliti agar tidak ada artefak kode duplikat, atribut template ganda, atau fragment sintaks yang rusak akibat proses copy-paste / regenerasi kode AI.
- **Ketahanan UX di Dunia Nyata (Real-World Resilience):**
  - Seluruh layout dan kartu data wajib menangani skenario batas: teks panjang (`truncate`, `break-words`, `min-w-0`), data kosong (*empty state* yang informatif, bukan placeholder kosong atau rusak), dan penanganan error responsif.

---

## 7. Standar Kode & Eksekusi CLI
- **PHP CLI:**
  Selalu gunakan PHP 8.3 dari Laragon:
  `& "C:\laragon\bin\php\php-8.3.12-nts-Win32-vs16-x64\php.exe"`
- **Format Kode:**
  Wajib lolos Laravel Pint sesuai aturan `src/pint.json`:
  `vendor/bin/pint --test app database routes tests`
- **Pengecekan Integritas Kode & Anti-Duplikasi:**
  Lakukan audit `git diff` untuk memastikan tidak ada artefak atau potongan kode duplikat yang tertinggal sebelum menjalankan pengujian dan build final.
- **Pengujian:**
  Pastikan seluruh Feature Tests lulus sebelum melaporkan ke user:
  `artisan test --compact`
- **Frontend Build:**
  Pastikan `npm run build` sukses tanpa error sebelum menyerahkan hasil kerja ke user.


