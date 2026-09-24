# Rules 02 — Tech Stack & Library Resmi

## Stack Resmi Proyek

| Layer | Teknologi | Versi / Lingkungan | Catatan Penting |
|---|---|---|---|
| **Runtime CLI** | PHP | **8.3.12-nts** (Laragon) | Wajib: `& "C:\laragon\bin\php\php-8.3.12-nts-Win32-vs16-x64\php.exe"` |
| **Backend Framework** | Laravel | 11.x / 12.x | Struktur standar (tanpa modularisasi Nwidart) |
| **SPA Bridge** | Inertia.js | v2.x | Partial reload, SSR disabled, server-driven flash props |
| **Frontend Framework** | Vue.js | 3.x (Composition API) | Wajib `<script setup>` syntax murni |
| **CSS Framework** | Tailwind CSS | v3.x | Utility-first, semantic variables tokens |
| **UI Primitive & Icons**| Shadcn UI / Lucide | `@/Components/ui/button`, `lucide-vue-next` | Standar konsistensi tombol dan ikon |
| **Build Tool** | Vite | latest | HMR di local (`npm run dev`), build teroptimasi (`npm run build`) |
| **Database** | MySQL | 8.x | Transaksi ACID, InnoDB engine, utf8mb4 |
| **Testing** | PHPUnit | 11.x | Feature tests berbasis HTTP & database refresh |
| **Code Formatter** | Laravel Pint | v1.x | Aturan konfigurasi kustom di [`src/pint.json`](file:///e:/5_projects/yzd-trading-management/src/pint.json) |

---

## Package PHP Utama yang Digunakan

| Package | Fungsi | Catatan Penggunaan |
|---|---|---|
| `laravel/framework` | Core Framework | Middleware di `bootstrap/app.php` |
| `inertiajs/inertia-laravel` | Inertia Server Adapter | Shared data via `HandleInertiaRequests` |
| `tightenco/ziggy` | Route Helper Vue | Generate URL rute bernama di client (`route(...)`) |
| `laravel/pint` | Standard Formatter | Evaluasi via `vendor/bin/pint --test app database routes tests` |
| `phpunit/phpunit` | Test Suite | Eksekusi via `artisan test --compact` |

---

## Package NPM Utama yang Digunakan

| Package | Fungsi |
|---|---|
| `@inertiajs/vue3` | Inertia Client Adapter (Router, useForm, Link, Head) |
| `vue` | Vue 3 Reactive Engine |
| `tailwindcss` | Utility-first CSS Engine |
| `lucide-vue-next` | Standar Iconography tunggal aplikasi |
| `radix-vue`, `clsx`, `tailwind-merge`, `class-variance-authority` | Primitif UI untuk komponen `@/Components/ui/*` (Button) |
| `ziggy-js` | Integrasi route helper Laravel ke JavaScript |
| `axios` | HTTP client pendukung |

---

## Prosedur Penambahan Library / Package Baru

Dilarang menginstal package pihak ketiga baru secara sembarangan:
1. Agent wajib mengevaluasi apakah kebutuhan dapat diselesaikan dengan kode native Laravel/Vue.
2. Jika butuh package, usulkan nama package, ukuran bundle, dan alasannya.
3. Tunggu persetujuan pengguna sebelum menjalankan `composer require` atau `npm install`.
4. Dilarang menginstal 2 library berbeda untuk fungsi yang identik.

---

## Standar Kode per Bahasa & Framework

### 1. PHP 8.3 & Laravel 11/12
- Gunakan *Constructor Property Promotion* pada kelas Service dan Form Request.
- Wajib menyertakan *explicit return type hints* dan *parameter type hints* pada setiap method (`public function submit(...): KlaimKomisi`).
- Gunakan ekspresi `match` modern untuk pemetaan kondisi yang ringkas.
- Pendaftaran middleware dan route console terpusat di `bootstrap/app.php` (tidak ada `app/Http/Kernel.php` warisan lama).
- Gunakan fungsi `config('...')` untuk membaca konfigurasi aplikasi, **DILARANG** memanggil `env('...')` langsung di luar file folder `config/`.
- Kredensial sensitif pada model menggunakan Eloquent attribute casting `'encrypted'`.

### 2. Inertia.js v2
- Semua form submit menggunakan composable `useForm()` dari `@inertiajs/vue3`.
- Seluruh navigasi internal menggunakan komponen `<Link>` atau fungsi `router.visit()`, dilarang menggunakan `window.location.href`.
- Error validasi server otomatis ditangkap oleh form helper (`form.errors.field_name`).
- Gunakan *Inertia Partial Reloads* (`only: [...]`, `preserveScroll: true`, `preserveState: true`) untuk pembaruan data tabel dan pagination yang responsif.

### 3. Vue 3 (Composition API)
- Wajib menggunakan `<script setup>` syntax.
- **DILARANG** menggunakan Options API (`data()`, `methods:`, `computed:`, dsb.).
- Deklarasi props wajib menggunakan `defineProps({ ... })` yang terstruktur tipe dan default valuenya.
- State dikelola menggunakan `ref()` dan `computed()`.

### 4. Tailwind CSS v3 & Tema (Light & Dark Mode)
- Seluruh styling komponen menggunakan utility classes Tailwind v3.
- **Wajib mendukung Light Mode dan Dark Mode secara seimbang:** Default aplikasi adalah Light Mode dengan dukungan varian `dark:` atau semantic CSS variable tokens (`text-foreground`, `text-muted-foreground`, `bg-card`, `bg-background`, `border-border`).
- **DILARANG** menggunakan tag `<style>` (baik scoped maupun unscoped) di dalam file `.vue`, kecuali berkas styling global `resources/css/app.css`.

---

## Larangan Keras Ekosistem (Hard Tech Stack Bans)

- **DILARANG** menggunakan Nwidart Modules (arsitektur menggunakan standar modular MVC Laravel).
- **DILARANG** menggunakan jQuery atau Yajra DataTables (gunakan `AppDataTable.vue`).
- **DILARANG** menggunakan Bootstrap CSS.
- **DILARANG** menginstal Laravel Livewire (aplikasi adalah SPA berbasis Inertia.js).
- **DILARANG** menginstal Spatie Permission (manajemen peran menggunakan kolom `role` terstruktur di tabel `users`).
- **DILARANG** menulis atribut inline `style="..."` pada elemen HTML jika dapat diakomodasi oleh class Tailwind.
- **DILARANG** menggunakan icon pack selain `lucide-vue-next`.
