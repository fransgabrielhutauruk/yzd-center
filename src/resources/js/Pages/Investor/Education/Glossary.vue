<script setup>
import { ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    BookOpen,
    Search,
    HelpCircle,
    X,
} from 'lucide-vue-next';
import InvestorLayout from '@/Layouts/InvestorLayout.vue';
import { Button } from '@/Components/ui/button';

const props = defineProps({
    glossary: {
        type: Object,
        required: true,
    },
});

const searchQuery = ref(props.glossary.filters?.search || '');
const selectedLetter = ref(props.glossary.filters?.huruf || '');
let debounceTimeout = null;

const handleFilter = () => {
    router.get(
        '/investor/education/glossary',
        {
            search: searchQuery.value || undefined,
            huruf: selectedLetter.value || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true }
    );
};

// Live search otomatis saat mengetik nama istilah
watch(searchQuery, () => {
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => {
        handleFilter();
    }, 350);
});

const clearSearch = () => {
    searchQuery.value = '';
    handleFilter();
};

const setLetter = (letter) => {
    selectedLetter.value = selectedLetter.value === letter ? '' : letter;
    handleFilter();
};

const resetFilter = () => {
    searchQuery.value = '';
    selectedLetter.value = '';
    handleFilter();
};
</script>

<template>
    <InvestorLayout title="Glosarium Istilah Forex">
        <div class="space-y-6">
            <!-- Banner / Header -->
            <div class="rounded-2xl bg-gradient-to-r from-blue-700 via-indigo-800 to-slate-900 p-6 sm:p-8 text-white shadow-md">
                <div class="max-w-2xl">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 text-xs font-medium backdrop-blur-sm">
                        <BookOpen class="h-3.5 w-3.5 text-blue-300" />
                        Pusat Pengetahuan Investor
                    </span>
                    <h1 class="mt-3 text-2xl sm:text-3xl font-bold tracking-tight">
                        Kamus & Glosarium Forex
                    </h1>
                    <p class="mt-2 text-sm sm:text-base text-blue-100/90 leading-relaxed">
                        Pahami istilah-istilah penting dalam dunia trading dan copytrade dengan bahasa yang lugas, transparan, dan ramah bagi pemula.
                    </p>
                </div>

                <!-- Input Pencarian (Live Search) -->
                <div class="mt-6 max-w-xl">
                    <div class="relative">
                        <Search class="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari nama istilah forex (misal: Margin, Drawdown, Lot)..."
                            class="w-full rounded-xl border-0 bg-white/95 py-3 pl-10 pr-10 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-400"
                        />
                        <button
                            v-if="searchQuery"
                            type="button"
                            @click="clearSearch"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors"
                            title="Hapus pencarian"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Bar Filter Alfabet A-Z -->
            <div class="flex flex-wrap items-center gap-1 rounded-xl border border-slate-200 bg-white p-2 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <span class="px-2 text-xs font-semibold text-slate-400 uppercase tracking-wider">
                    Indeks:
                </span>
                <button
                    v-for="letter in glossary.available_letters"
                    :key="letter"
                    type="button"
                    @click="setLetter(letter)"
                    :class="[
                        'flex h-7 w-7 items-center justify-center rounded-lg text-xs font-bold transition-all',
                        selectedLetter === letter
                            ? 'bg-primary text-primary-foreground shadow-sm'
                            : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800',
                    ]"
                >
                    {{ letter }}
                </button>
                <button
                    v-if="selectedLetter || searchQuery"
                    type="button"
                    @click="resetFilter"
                    class="ml-auto px-2.5 py-1 text-xs font-medium text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white"
                >
                    Reset Filter
                </button>
            </div>

            <!-- Daftar Istilah (Grid Card) -->
            <div v-if="glossary.items.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div
                    v-for="item in glossary.items"
                    :key="item.id"
                    class="group flex flex-col justify-start rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition-all hover:border-primary/40 hover:shadow-md dark:border-slate-800 dark:bg-slate-900"
                >
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">
                        {{ item.istilah }}
                    </h3>
                    <p class="mt-2.5 text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                        {{ item.definisi }}
                    </p>
                </div>
            </div>

            <!-- Kondisi Kosong (Empty State) -->
            <div
                v-else
                class="rounded-xl border border-dashed border-slate-200 bg-white p-12 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900"
            >
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800">
                    <HelpCircle class="h-6 w-6 text-slate-400" />
                </div>
                <h3 class="mt-3 text-base font-semibold text-slate-900 dark:text-white">
                    Istilah Tidak Ditemukan
                </h3>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400 max-w-sm mx-auto">
                    Tidak ada istilah yang cocok dengan kriteria pencarian nama istilah atau filter yang Anda pilih.
                </p>
                <div class="mt-4">
                    <Button variant="outline" size="sm" @click="resetFilter">
                        Tampilkan Semua Istilah
                    </Button>
                </div>
            </div>
        </div>
    </InvestorLayout>
</template>
