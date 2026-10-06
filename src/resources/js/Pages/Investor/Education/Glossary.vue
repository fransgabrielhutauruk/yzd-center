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
    <InvestorLayout
        title="Kamus & Glosarium Forex"
        subtitle="Pahami istilah-istilah penting dalam dunia trading dan copytrade dengan bahasa yang ramah bagi pemula."
        :breadcrumbs="[
            { label: 'Bantuan & Edukasi' },
            { label: 'Glosarium Forex' }
        ]"
    >
        <div class="space-y-6">
            <!-- Input Pencarian (Live Search) -->
            <div class="max-w-xl">
                <div class="relative">
                    <Search class="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari nama istilah..."
                        class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 py-2.5 pl-10 pr-10 text-sm text-foreground shadow-xs placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary-500"
                    />
                    <button
                        v-if="searchQuery"
                        type="button"
                        @click="clearSearch"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors cursor-pointer"
                        title="Hapus pencarian"
                    >
                        <X class="h-4 w-4" />
                    </button>
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
