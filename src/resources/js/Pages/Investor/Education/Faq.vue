<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    MessageCircleQuestion,
    Search,
    ChevronDown,
    X,
    Layers,
    MessageCircle,
    HelpCircle,
    ChevronsUpDown,
} from 'lucide-vue-next';
import InvestorLayout from '@/Layouts/InvestorLayout.vue';
import { Button } from '@/Components/ui/button';

const props = defineProps({
    faq: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const searchQuery = ref(props.filters.search || '');
const activeTopikId = ref(props.filters.topik_id ? Number(props.filters.topik_id) : null);

// Menyimpan ID FAQ yang sedang terbuka di accordion
const openFaqIds = ref(new Set());

// Buka pertanyaan pertama secara default jika ada
if (props.faq.topics?.length > 0 && props.faq.topics[0]?.faqs?.length > 0) {
    openFaqIds.value.add(props.faq.topics[0].faqs[0].id);
}

const toggleFaq = (id) => {
    if (openFaqIds.value.has(id)) {
        openFaqIds.value.delete(id);
    } else {
        openFaqIds.value.add(id);
    }
};

const isFaqOpen = (id) => openFaqIds.value.has(id);

const totalFaqsCount = computed(() => {
    if (!props.faq.topics) return 0;
    return props.faq.topics.reduce((acc, topic) => acc + (topic.faqs?.length || 0), 0);
});

const toggleAll = () => {
    if (openFaqIds.value.size === totalFaqsCount.value) {
        openFaqIds.value.clear();
    } else {
        const allIds = new Set();
        props.faq.topics.forEach((topic) => {
            topic.faqs?.forEach((item) => allIds.add(item.id));
        });
        openFaqIds.value = allIds;
    }
};

const handleSearch = () => {
    router.get(
        '/investor/education/faq',
        {
            search: searchQuery.value || undefined,
            topik_id: activeTopikId.value || undefined,
        },
        { preserveState: true, preserveScroll: true }
    );
};

const clearSearch = () => {
    searchQuery.value = '';
    handleSearch();
};

const selectTopic = (topicId) => {
    activeTopikId.value = topicId;
    router.get(
        '/investor/education/faq',
        {
            topik_id: topicId || undefined,
            search: searchQuery.value || undefined,
        },
        { preserveState: true, preserveScroll: true }
    );
};

const resetAllFilters = () => {
    searchQuery.value = '';
    activeTopikId.value = null;
    router.get('/investor/education/faq', {}, { preserveState: true, preserveScroll: true });
};
</script>

<template>
    <InvestorLayout title="Tanya Jawab (FAQ)">
        <Head title="Tanya Jawab (FAQ) - YZD Center" />

        <div class="space-y-8 max-w-5xl mx-auto">
            <!-- Banner Utama & Kotak Pencarian -->
            <div class="rounded-2xl bg-gradient-to-r from-slate-900 via-indigo-950 to-blue-900 p-6 sm:p-8 text-white shadow-md">
                <div class="max-w-2xl">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 text-xs font-medium backdrop-blur-sm">
                        <MessageCircleQuestion class="h-3.5 w-3.5 text-blue-300" />
                        Pusat Tanya Jawab
                    </span>
                    <h1 class="mt-3 text-2xl sm:text-3xl font-bold tracking-tight">
                        Pertanyaan yang Sering Diajukan (FAQ)
                    </h1>
                    <p class="mt-2 text-sm sm:text-base text-blue-100/90 leading-relaxed">
                        Temukan jawaban instan seputar legalitas, skema copytrade non-custodial, tata cara deposit, dan transparansi bagi hasil.
                    </p>
                </div>

                <!-- Input Pencarian FAQ -->
                <div class="mt-6 max-w-xl">
                    <div class="relative">
                        <Search class="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Ketik kata kunci (misal: penarikan modal, margin call, komisi)..."
                            @keyup.enter="handleSearch"
                            class="w-full rounded-xl border-0 bg-white/95 py-3 pl-10 pr-24 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-400"
                        />
                        <button
                            v-if="searchQuery"
                            type="button"
                            @click="clearSearch"
                            class="absolute right-20 top-1/2 -translate-y-1/2 p-1 text-slate-400 hover:text-slate-600 transition-colors"
                            title="Bersihkan pencarian"
                        >
                            <X class="h-4 w-4" />
                        </button>
                        <button
                            type="button"
                            @click="handleSearch"
                            class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg bg-blue-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-blue-500 transition-colors"
                        >
                            Cari
                        </button>
                    </div>
                </div>
            </div>

            <!-- Filter Topik Pills -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        @click="selectTopic(null)"
                        :class="[
                            'rounded-full px-4 py-2 text-xs font-medium transition-all shadow-sm',
                            activeTopikId === null
                                ? 'bg-primary text-white shadow-primary/20'
                                : 'bg-white text-slate-600 hover:bg-slate-100 hover:text-slate-900 border border-slate-200 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800',
                        ]"
                    >
                        Semua Topik
                    </button>
                    <button
                        v-for="topic in faq.available_topics"
                        :key="topic.id"
                        type="button"
                        @click="selectTopic(topic.id)"
                        :class="[
                            'rounded-full px-4 py-2 text-xs font-medium transition-all shadow-sm',
                            activeTopikId === topic.id
                                ? 'bg-primary text-white shadow-primary/20'
                                : 'bg-white text-slate-600 hover:bg-slate-100 hover:text-slate-900 border border-slate-200 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800',
                        ]"
                    >
                        {{ topic.nama }}
                    </button>
                </div>

                <!-- Tombol Buka/Tutup Semua -->
                <button
                    v-if="totalFaqsCount > 0"
                    type="button"
                    @click="toggleAll"
                    class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-600 dark:text-slate-400 hover:text-primary dark:hover:text-primary transition-colors self-end sm:self-auto"
                >
                    <ChevronsUpDown class="h-3.5 w-3.5" />
                    {{ openFaqIds.size === totalFaqsCount ? 'Tutup Semua' : 'Buka Semua' }}
                </button>
            </div>

            <!-- Konten Tanya Jawab (Accordion per Topik) -->
            <div v-if="faq.topics && faq.topics.length > 0" class="space-y-8">
                <div
                    v-for="topic in faq.topics"
                    :key="topic.id"
                    class="space-y-3"
                >
                    <!-- Header Topik -->
                    <div class="flex items-center gap-2.5 pb-2 border-b border-slate-200 dark:border-slate-800">
                        <div class="rounded-lg bg-primary/10 p-1.5 text-primary">
                            <Layers class="h-4 w-4" />
                        </div>
                        <div>
                            <h2 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white">
                                {{ topic.nama }}
                            </h2>
                            <p v-if="topic.deskripsi" class="text-xs text-slate-500 dark:text-slate-400">
                                {{ topic.deskripsi }}
                            </p>
                        </div>
                    </div>

                    <!-- Accordion Item Pertanyaan -->
                    <div class="space-y-2.5 pt-1">
                        <div
                            v-for="item in topic.faqs"
                            :key="item.id"
                            class="rounded-xl border border-slate-200 bg-white transition-all dark:border-slate-800 dark:bg-slate-900 overflow-hidden shadow-sm"
                        >
                            <button
                                type="button"
                                @click="toggleFaq(item.id)"
                                :aria-expanded="isFaqOpen(item.id)"
                                class="flex w-full items-center justify-between gap-4 p-4 sm:p-5 text-left transition-colors hover:bg-slate-50/70 dark:hover:bg-slate-800/50"
                            >
                                <span class="text-sm sm:text-base font-semibold text-slate-900 dark:text-white">
                                    {{ item.pertanyaan }}
                                </span>
                                <div
                                    class="shrink-0 rounded-full p-1 text-slate-400 transition-transform duration-200"
                                    :class="{ 'rotate-180 text-primary': isFaqOpen(item.id) }"
                                >
                                    <ChevronDown class="h-4 w-4" />
                                </div>
                            </button>

                            <div
                                v-show="isFaqOpen(item.id)"
                                class="border-t border-slate-100 bg-slate-50/40 p-4 sm:p-5 dark:border-slate-800/80 dark:bg-slate-900/60"
                            >
                                <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed whitespace-pre-line">
                                    {{ item.jawaban }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- State Kosong jika tidak ada hasil -->
            <div
                v-else
                class="rounded-2xl border border-slate-200 bg-white p-8 sm:p-12 text-center dark:border-slate-800 dark:bg-slate-900 shadow-sm"
            >
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 mb-4">
                    <HelpCircle class="h-7 w-7" />
                </div>
                <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white">
                    Tidak Ditemukan Pertanyaan yang Sesuai
                </h3>
                <p class="mt-1 text-xs sm:text-sm text-slate-500 dark:text-slate-400 max-w-md mx-auto">
                    {{ searchQuery ? `Kata kunci "${searchQuery}" tidak cocok dengan pertanyaan yang tersedia saat ini.` : 'Belum ada pertanyaan pada topik ini.' }}
                </p>
                <div class="mt-5">
                    <Button
                        type="button"
                        variant="outline"
                        @click="resetAllFilters"
                    >
                        Tampilkan Semua Pertanyaan
                    </Button>
                </div>
            </div>

            <!-- Kotak Bantuan WhatsApp Master Trader -->
            <div class="rounded-2xl border border-blue-100 bg-blue-50/60 p-6 sm:p-8 dark:border-blue-900/40 dark:bg-blue-950/20">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-start gap-3.5">
                        <div class="rounded-xl bg-blue-600 p-2.5 text-white shadow-md shadow-blue-500/20">
                            <MessageCircle class="h-6 w-6" />
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">
                                Masih Memiliki Pertanyaan Lain?
                            </h3>
                            <p class="mt-0.5 text-xs sm:text-sm text-slate-600 dark:text-slate-300">
                                Konsultasikan kendala teknis atau pertanyaan khusus Anda langsung dengan Pak Yazid melalui WhatsApp.
                            </p>
                        </div>
                    </div>
                    <a
                        href="https://wa.me/6282388986189?text=Halo%20Pak%20Yazid%2C%20saya%20ingin%20bertanya%20seputar%20FAQ%20dan%20sistem%20copytrade%20YZD%20Center."
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-xs sm:text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 transition-colors shrink-0"
                    >
                        <MessageCircle class="h-4 w-4" />
                        Hubungi via WhatsApp
                    </a>
                </div>
            </div>
        </div>
    </InvestorLayout>
</template>
