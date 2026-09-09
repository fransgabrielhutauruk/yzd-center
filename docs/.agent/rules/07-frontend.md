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
