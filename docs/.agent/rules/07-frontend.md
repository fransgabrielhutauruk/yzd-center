# Rules 07 — Frontend (Vue 3 + Inertia.js + Tailwind CSS)

## Prinsip Utama

- **Vue hanya untuk presentasi** — tidak ada business logic di Vue
- **Data siap tampil** — semua formatting, kalkulasi, dan transformasi dilakukan di server (service)
- **Navigasi via Inertia** — jangan gunakan `window.location` untuk navigasi internal

---

## Vue 3 — Composition API

### Template Komponen Vue

```vue
<script setup>
import { ref, computed } from 'vue'
import { router, useForm } from '@inertiajs/vue3'

// Props — selalu define dengan types
const props = defineProps({
    investor: {
        type: Object,
        required: true,
    },
    canEdit: {
        type: Boolean,
        default: false,
    },
})

// Emits
const emit = defineEmits(['update', 'close'])

// State
const isLoading = ref(false)
const showModal = ref(false)

// Form (via Inertia useForm)
const form = useForm({
    nama: props.investor.nama,
    email: props.investor.email,
})

// Computed
const fullName = computed(() => `${props.investor.nama} (${props.investor.role_label})`)

// Methods
function submit() {
    form.put(route('admin.investor.update', props.investor.investor_id), {
        onSuccess: () => {
            showModal.value = false
        },
    })
}
</script>

<template>
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900">{{ fullName }}</h2>
        <!-- ... -->
    </div>
</template>
```

### Larangan Vue

```vue
<!-- DILARANG: Options API -->
<script>
export default {
    data() { return {} },
    methods: {},
    computed: {},
}
</script>

<!-- DILARANG: kalkulasi bisnis di template -->
<template>
    <span>{{ (profit * 0.20).toFixed(2) }}</span>  <!-- SALAH -->
    <span>{{ investor.profit_investor_label }}</span> <!-- BENAR — sudah disiapkan server -->
</template>

<!-- DILARANG: navigasi dengan window.location -->
<script setup>
function goToDashboard() {
    window.location.href = '/investor/dashboard' // SALAH
    router.visit(route('investor.dashboard'))     // BENAR
}
</script>
```

---

## Inertia.js — Konvensi

### Struktur Halaman (Pages/)

Setiap file di `resources/js/Pages/` mewakili satu route Inertia:

```vue
<!-- resources/js/Pages/Investor/Dashboard.vue -->
<script setup>
import InvestorLayout from '@/Layouts/InvestorLayout.vue'
import PortfolioCard from '@/Components/Investor/PortfolioCard.vue'

// Props dari controller (data sudah siap tampil)
const props = defineProps({
    dashboard: Object,   // dari InvestorService::getDashboardData()
    copytrade: Object,   // status stepper copytrade
})

// Layout — gunakan defineOptions
defineOptions({ layout: InvestorLayout })
</script>

<template>
    <div class="container mx-auto py-6 px-4">
        <PortfolioCard :data="dashboard" />
    </div>
</template>
```

### Form Submission

```vue
<script setup>
import { useForm } from '@inertiajs/vue3'

const form = useForm({
    field1: '',
    field2: '',
})

function submit() {
    form.post(route('investor.copytrade.step1.store'), {
        onSuccess: () => { /* reset atau redirect sudah handle Inertia */ },
        onError: () => { /* form.errors sudah terisi otomatis */ },
        onFinish: () => { /* setelah success atau error */ },
    })
}
</script>

<template>
    <form @submit.prevent="submit">
        <div v-if="form.errors.field1" class="text-red-500 text-sm">
            {{ form.errors.field1 }}
        </div>
        <input v-model="form.field1" type="text" />
        <button type="submit" :disabled="form.processing">
            {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
        </button>
    </form>
</template>
```

### Navigasi

```vue
<script setup>
import { Link, router } from '@inertiajs/vue3'

// Navigasi programmatic
function goTo(routeName, params = {}) {
    router.visit(route(routeName, params))
}
</script>

<template>
    <!-- Gunakan <Link> untuk navigasi deklaratif -->
    <Link :href="route('investor.dashboard')" class="...">Dashboard</Link>

    <!-- Button navigasi programmatic -->
    <button @click="goTo('investor.copytrade.index')">Mulai CopyTrade</button>
</template>
```

---

## Struktur Komponen

```
resources/js/
├── Components/
│   ├── Common/
│   │   ├── AppButton.vue       ← button dengan variants
│   │   ├── AppInput.vue        ← input field dengan error state
│   │   ├── AppModal.vue        ← modal dialog
│   │   ├── AppAlert.vue        ← alert/flash message
│   │   ├── AppBadge.vue        ← status badge
│   │   └── LoadingSpinner.vue
│   ├── Admin/
│   │   ├── InvestorTable.vue
│   │   └── VerificationModal.vue
│   └── Investor/
│       ├── PortfolioCard.vue
│       ├── CopytradeStepCard.vue
│       └── ProfitCalculator.vue
├── Layouts/
│   ├── AdminLayout.vue         ← layout untuk halaman admin
│   ├── InvestorLayout.vue      ← layout untuk investor & affiliator
│   └── AuthLayout.vue          ← layout untuk login/register
└── Pages/
    ├── Auth/
    │   ├── Login.vue
    │   └── Register.vue
    ├── Admin/
    │   ├── Dashboard.vue
    │   ├── Investor/
    │   │   ├── Index.vue
    │   │   └── Show.vue
    │   └── Invitation/
    │       └── Index.vue
    ├── Investor/
    │   ├── Dashboard.vue
    │   ├── Copytrade/
    │   │   └── Index.vue       ← stepper 3 langkah
    │   └── Kalkulator.vue
    └── Affiliator/
        ├── Dashboard.vue
        └── Komisi.vue
```

---

## Tailwind CSS — Aturan

### Gunakan Utility Classes

```vue
<!-- BENAR -->
<div class="flex items-center justify-between gap-4 p-6 bg-white rounded-lg shadow">

<!-- SALAH: CSS custom untuk hal yang bisa dihandle Tailwind -->
<div style="display: flex; padding: 24px; background: white;">
```

### DILARANG Blok `<style>` / CSS Kustom per Komponen

- Seluruh styling WAJIB memakai utility class Tailwind langsung di atribut `class`.
- **DILARANG:** blok `<style>` (scoped maupun global) di file `.vue`, atribut inline `style="..."`, dan file CSS kustom per komponen.
- Pengecualian satu-satunya adalah file sentral `resources/css/app.css` (token `@theme`, `@plugin`, `@layer base`).
- Konten HTML dinamis (mis. output rich-text editor / `v-html`) distyling dengan plugin typography via class `prose`:

```vue
<!-- BENAR: render HTML dinamis -->
<div
    class="prose prose-sm max-w-none [--tw-prose-body:var(--color-foreground)] [--tw-prose-headings:var(--color-foreground)]"
    v-html="previewHtml"
></div>

<!-- SALAH: class CSS kustom + <style> -->
<div class="legal-preview" v-html="previewHtml"></div>
<style>
.legal-preview p { margin: 0 0 0.75rem; }
</style>
```

### Spacing: Gunakan gap, bukan margin untuk list

```vue
<!-- BENAR -->
<div class="flex flex-col gap-4">
    <div>Item 1</div>
    <div>Item 2</div>
</div>

<!-- KURANG IDEAL: margin per item -->
<div>
    <div class="mb-4">Item 1</div>
    <div class="mb-4">Item 2</div>
</div>
```

### Komponen Umum — Gunakan `AppButton`, Jangan Tulis Ulang

```vue
<!-- BENAR: gunakan komponen yang sudah ada -->
<AppButton variant="primary" @click="submit" :loading="form.processing">
    Simpan
</AppButton>

<!-- SALAH: tulis ulang class Tailwind yang sama di setiap halaman -->
<button class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 disabled:opacity-50">
    Simpan
</button>
```

---

## Ziggy — Route Helper di Vue

Gunakan Ziggy untuk generate named routes di Vue:

```vue
<script setup>
// route() tersedia global setelah setup Ziggy
const url = route('investor.dashboard')
const urlWithParam = route('admin.investor.show', { investor: 1 })
</script>
```

Setup di `app.js`:
```js
import { createInertiaApp } from '@inertiajs/vue3'
import { ZiggyVue } from '../../vendor/tightenco/ziggy'

createInertiaApp({
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el)
    },
})
```

---

## Standar Desain Responsif & Mobile UX (Mobile-First)

- **Kesetaraan Fitur (Feature Parity):** Semua alur kerja dan fungsi (termasuk filter tabel, aksi modal, dan navigasi) harus dapat diakses dan digunakan secara nyaman pada layar HP ($360\text{px} - 430\text{px}$).
- **Dilarang Menyembunyikan Kontrol Penting:** Jangan gunakan `hidden md:...` atau `hidden sm:...` pada filter data, tombol aksi, atau input penting. Gunakan stacking vertikal atau grid responsif (`grid grid-cols-2 gap-2 w-full sm:flex sm:w-auto`).
- **Touch Target:** Seluruh tombol dan kontrol form di mobile harus memiliki tinggi/area sentuh minimal 36px–44px untuk kenyamanan sentuhan jari.
- **Tabel Responsif:** Seluruh tabel dibungkus kontainer scroll horizontal (`overflow-x-auto`) dengan visual padding yang pas.

---

## Standar Kontras Warna & Aksesibilitas Visual (Anti-Low Contrast)

- **Ketajaman Kontras Warna (WCAG AA Compliance):** Seluruh teks judul, teks isi, label form, placeholder, ikon, status badge, border, dan tombol wajib memiliki rasio kontras yang cukup tinggi terhadap latar belakangnya (minimal 4.5:1 untuk teks normal, 3:1 untuk teks tebal/besar/badge).
- **Anti Low Contrast pada Desain Statis:**
  - **Teks pada Latar Terang:** Dilarang menggunakan teks abu-abu pudar atau tipis di atas latar belakang putih/terang (hindari `text-slate-400`, `text-gray-400`, atau `text-zinc-300` untuk teks informasi penting). Gunakan `text-foreground`, `text-slate-700`, atau `text-muted-foreground` berbobot medium/semibold.
  - **Banner & Status Badge:** Dilarang memasangkan teks warna terang/pastel di atas latar pastel yang senada (misal: `text-amber-400` di atas `bg-amber-50`). Selalu gunakan pasangan warna kontras tinggi: misal `text-amber-800 dark:text-amber-200` pada `bg-amber-500/10 border-amber-500/30`, atau `text-rose-800 dark:text-rose-200` pada `bg-rose-500/10 border-rose-500/30`.
- **Kontras Tinggi pada State Interaktif (Hover, Focus, Active):**
  - Saat kursor melakukan *hover* (`hover:bg-...`, `hover:text-...`), teks dan ikon TIDAK BOLEH memudar (*washed out*) atau menyatu dengan warna latar.
  - Jika latar belakang berubah warna saat *hover*, pastikan warna teks tetap memiliki kontras yang tajam (contoh tombol outline: `border-border text-foreground hover:bg-muted/60 hover:text-foreground`).
  - Hindari situasi di mana background berubah warna pada hover tetapi warna teks tidak disesuaikan sehingga teks menjadi tidak terbaca.
- **Dukungan Light & Dark Mode yang Seimbang:**
  - Selalu sertakan varian `dark:` bila menggunakan warna spesifik (misal: `text-rose-700 dark:text-rose-300`, `text-foreground`, `bg-card`).

---

## Standar Anti-AI Slop & Clean Frontend Craftsmanship

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



