# Rules 02 — Tech Stack & Library

## Stack Resmi

| Layer | Teknologi | Versi |
|-------|-----------|-------|
| Runtime | PHP | 8.4 |
| Framework | Laravel | 12.x |
| SPA Bridge | Inertia.js | v2.x |
| Frontend Framework | Vue.js | 3.x (Composition API) |
| CSS Framework | Tailwind CSS | v3.x |
| Build Tool | Vite | latest |
| Database | MySQL | 8.x |
| Cache/Queue | Redis | 6.x (opsional, recommended) |
| Testing | PHPUnit | 11.x |
| Code Formatter | Laravel Pint | v1.x (kustom: `src/pint.json`) |

---

## Package PHP yang Sudah Diputuskan

| Package | Versi | Fungsi |
|---------|-------|--------|
| `laravel/framework` | v12 | Core |
| `laravel/sanctum` | v4 | API Token (jika dibutuhkan nanti) |
| `inertiajs/inertia-laravel` | ^2.0 | Inertia adapter |
| `tightenco/ziggy` | latest | Route helper di Vue |
| `laravel/pint` | v1 | Code formatter (kustom: `src/pint.json`) |
| `phpunit/phpunit` | v11 | Testing |

> **Catatan:** Package lain (file upload, PDF, PWA, onboarding tour, dll.) diputuskan saat implementasi fitur terkait — diskusikan dulu, catat ke `catatan/`, baru install.

---

## Package NPM yang Sudah Diputuskan

| Package | Fungsi |
|---------|--------|
| `@inertiajs/vue3` | Inertia Vue adapter |
| `vue` | Vue 3 |
| `tailwindcss` | CSS framework |
| `@vitejs/plugin-vue` | Vite Vue plugin |
| `autoprefixer` | PostCSS |
| `ziggy-js` | Route helper di Vue |
| `axios` | HTTP client (sudah include di Laravel scaffold) |

---

## Cara Menambah Library / Package Baru

**Wajib ikuti alur ini:**

1. Agent usulkan library + alasan + alternatif yang dipertimbangkan
2. Tanya user: *"Apakah keputusan ini perlu dicatat ke `catatan/`?"*
3. Jika user setuju → catat ke `catatan/YYYY-MM-DD_library-X.md`
4. Baru install setelah user menyetujui

**Jangan install dua library untuk fungsi yang sama.** Cek dulu apakah sudah ada package yang bisa melakukan hal yang sama.

---

## Versi — Hal yang Harus Diperhatikan

### PHP 8.4
- Gunakan constructor property promotion di `__construct()`
- Gunakan explicit return type hints di semua method dan function
- Gunakan union types dan nullable types (`?string`, `string|null`)
- Gunakan `match` expression daripada `switch` jika memungkinkan
- Selalu gunakan curly braces untuk control structures

### Laravel 12
- Middleware didaftarkan di `bootstrap/app.php` via `->withMiddleware()` — bukan `Kernel.php`
- Gunakan `php artisan make:` untuk semua file baru — jangan buat manual
- Tidak ada `app/Console/Kernel.php` — console config di `bootstrap/app.php` atau `routes/console.php`
- Gunakan `config('app.name')`, **bukan** `env('APP_NAME')` di luar file config/

### Inertia.js v2
- Semua navigasi via `router.visit()` atau komponen `<Link>` dari `@inertiajs/vue3`
- Jangan gunakan `window.location` untuk navigasi internal
- Shared data dikirim via `HandleInertiaRequests` middleware
- Form submission via `useForm()` composable dari `@inertiajs/vue3`
- Error handling sudah built-in di `useForm` — gunakan `form.errors.fieldname`

### Vue 3
- **Wajib Composition API** dengan `<script setup>` syntax
- **Dilarang** Options API (`data()`, `methods:`, `computed:`, `mounted:`, dll.)
- Gunakan `defineProps()`, `defineEmits()`, `defineExpose()` dengan TypeScript-style type annotations
- Gunakan `ref()` dan `reactive()` untuk state
- Gunakan `computed()` untuk derived state
- Gunakan `onMounted()`, `onUnmounted()` untuk lifecycle hooks

### Tailwind CSS v3
- Verifikasi class yang digunakan ada di v3 (bukan v4 atau v2)
- Gunakan utility classes — hindari CSS custom kecuali benar-benar tidak bisa dengan Tailwind
- Tidak wajib dark mode untuk proyek ini
- Gunakan `@apply` di CSS hanya untuk pattern yang sangat berulang

---

## Larangan Tech Stack

- **DILARANG** menggunakan Nwidart Modules
- **DILARANG** menggunakan Options API Vue (gunakan Composition API)
- **DILARANG** menggunakan jQuery (kecuali library pihak ketiga memaksa dan sudah disetujui)
- **DILARANG** menggunakan Bootstrap CSS (stack ini memakai Tailwind)
- **DILARANG** menginstall Livewire (stack yang dipilih adalah Inertia.js)
- **DILARANG** menginstall Spatie Laravel Permission (cukup role-based sederhana via field `role`)
- **DILARANG** menulis inline `style=""` di Vue component untuk styling yang bisa di-handle Tailwind
- **DILARANG** menggunakan `env()` langsung di luar file `config/`
