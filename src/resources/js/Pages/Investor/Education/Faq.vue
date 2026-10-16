<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    Search,
    ChevronDown,
    X,
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

// Menyimpan ID FAQ yang sedang terbuka di accordion (default tertutup semua)
const openFaqIds = ref(new Set());

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

// Total count statis untuk "Semua Topik" (dari available_topics tanpa filter)
const totalStaticCount = computed(() =>
    props.faq.available_topics?.reduce((acc, t) => acc + (t.faq_count ?? 0), 0) ?? 0
);

// Map topik id → count statis untuk badge per pill
const faqCountByTopicId = computed(() => {
    const map = {};
    props.faq.available_topics?.forEach((t) => {
        map[t.id] = t.faq_count ?? 0;
    });
    return map;
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

// --- Live Search: debounce 400ms ---
let searchTimer = null;
watch(searchQuery, (val) => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        router.get(
            '/investor/education/faq',
            {
                search: val || undefined,
                topik_id: activeTopikId.value || undefined,
            },
            { preserveState: true, preserveScroll: true }
        );
    }, 400);
});

const clearSearch = () => {
    searchQuery.value = '';
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
    <InvestorLayout
        title="Pertanyaan yang Sering Diajukan (FAQ)"
        subtitle="Temukan jawaban seputar copytrade (investasi) dan lainnya."
        :breadcrumbs="[
            { label: 'Bantuan & Edukasi' },
            { label: 'Tanya Jawab (FAQ)' }
        ]"
    >
        <Head title="Tanya Jawab (FAQ) - YZD Center" />

        <div class="space-y-6">
            <!-- Kotak Pencarian (Live Search) -->
            <div class="max-w-xl">
                <div class="relative">
                    <Search class="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground pointer-events-none" />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Ketik kata kunci..."
                        class="w-full rounded-xl border border-input bg-card py-2.5 pl-10 pr-10 text-sm text-foreground shadow-2xs placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all"
                    />
                    <button
                        v-if="searchQuery"
                        type="button"
                        @click="clearSearch"
                        class="absolute right-3 top-1/2 -translate-y-1/2 p-1 text-muted-foreground hover:text-foreground transition-colors cursor-pointer rounded-md hover:bg-muted"
                        title="Bersihkan pencarian"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>
            </div>

            <!-- Deretan Pills Topik FAQ (Scrollable Horizontal jika melebihi lebar konten) -->
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-muted-foreground">
                        Pilih Topik
                    </h2>
                    <span class="text-xs text-muted-foreground font-medium">
                        {{ (faq.available_topics?.length || 0) + 1 }} Topik
                    </span>
                </div>

                <div class="relative w-full">
                    <div class="topic-scroll-container flex items-center gap-2 overflow-x-auto pb-2.5 pt-0.5">
                        <!-- Pill: Semua -->
                        <button
                            type="button"
                            @click="selectTopic(null)"
                            :class="[
                                'group inline-flex items-center gap-2 whitespace-nowrap rounded-lg px-3.5 py-2 text-xs font-semibold transition-all cursor-pointer shrink-0 border shadow-2xs',
                                activeTopikId === null
                                    ? 'bg-primary text-primary-foreground border-primary shadow-xs'
                                    : 'bg-card text-foreground border-border hover:bg-muted/70 hover:border-border/80',
                            ]"
                        >
                            <span>Semua</span>
                            <span
                                :class="[
                                    'rounded-full px-1.5 py-0.2 text-[10px] font-bold font-mono transition-colors',
                                    activeTopikId === null
                                        ? 'bg-primary-foreground/20 text-primary-foreground'
                                        : 'bg-muted text-muted-foreground group-hover:text-foreground',
                                ]"
                            >
                                {{ totalStaticCount }}
                            </span>
                        </button>

                        <!-- Pills per Topik -->
                        <button
                            v-for="topic in faq.available_topics"
                            :key="topic.id"
                            type="button"
                            @click="selectTopic(topic.id)"
                            :class="[
                                'group inline-flex items-center gap-2 whitespace-nowrap rounded-lg px-3.5 py-2 text-xs font-semibold transition-all cursor-pointer shrink-0 border shadow-2xs',
                                activeTopikId === topic.id
                                    ? 'bg-primary text-primary-foreground border-primary shadow-xs'
                                    : 'bg-card text-foreground border-border hover:bg-muted/70 hover:border-border/80',
                            ]"
                        >
                            <span>{{ topic.nama }}</span>
                            <span
                                :class="[
                                    'rounded-full px-1.5 py-0.2 text-[10px] font-bold font-mono transition-colors',
                                    activeTopikId === topic.id
                                        ? 'bg-primary-foreground/20 text-primary-foreground'
                                        : 'bg-muted text-muted-foreground group-hover:text-foreground',
                                ]"
                            >
                                {{ faqCountByTopicId[topic.id] ?? 0 }}
                            </span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Konten Tanya Jawab (Accordion per Topik) -->
            <div class="space-y-4">
                <div class="flex items-center justify-between border-b border-border pb-3">
                    <div>
                        <h2 class="text-lg font-bold text-foreground">
                            Daftar FAQ
                        </h2>
                    </div>
                    <div class="flex items-center gap-3 shrink-0">
                        <span v-if="totalFaqsCount > 0" class="hidden sm:inline text-xs font-medium text-muted-foreground">
                            {{ totalFaqsCount }} pertanyaan
                        </span>
                        <button
                            v-if="totalFaqsCount > 0"
                            type="button"
                            @click="toggleAll"
                            class="inline-flex items-center gap-1.5 text-xs font-medium text-muted-foreground hover:text-foreground transition-colors cursor-pointer rounded-md hover:bg-muted px-2.5 py-1.5 border border-border/60 shadow-2xs"
                        >
                            <ChevronsUpDown class="h-3.5 w-3.5" />
                            <span>{{ openFaqIds.size === totalFaqsCount ? 'Tutup Semua' : 'Buka Semua' }}</span>
                        </button>
                    </div>
                </div>

                <div v-if="faq.topics && faq.topics.length > 0" class="space-y-8 pt-1">
                    <div
                        v-for="topic in faq.topics"
                        :key="topic.id"
                        class="space-y-3"
                    >
                        <!-- Header Topik -->
                        <div class="flex items-center gap-2.5 pb-2 border-b border-border">
                            <div>
                                <h3 class="text-base sm:text-lg font-bold text-foreground">
                                    {{ topic.nama }}
                                </h3>
                                <p v-if="topic.deskripsi" class="text-xs text-muted-foreground">
                                    {{ topic.deskripsi }}
                                </p>
                            </div>
                        </div>

                        <!-- Accordion Item Pertanyaan -->
                        <div class="space-y-2.5 pt-1">
                            <div
                                v-for="item in topic.faqs"
                                :key="item.id"
                                class="rounded-xl border border-border bg-card transition-all overflow-hidden shadow-2xs"
                            >
                                <button
                                    type="button"
                                    @click="toggleFaq(item.id)"
                                    :aria-expanded="isFaqOpen(item.id)"
                                    class="flex w-full items-center justify-between gap-4 p-4 sm:p-5 text-left transition-colors hover:bg-muted/50 cursor-pointer"
                                >
                                    <span class="text-sm sm:text-base font-semibold text-foreground">
                                        {{ item.pertanyaan }}
                                    </span>
                                    <div
                                        class="shrink-0 rounded-full p-1 text-muted-foreground transition-transform duration-200"
                                        :class="{ 'rotate-180 text-primary': isFaqOpen(item.id) }"
                                    >
                                        <ChevronDown class="h-4 w-4" />
                                    </div>
                                </button>

                                <div
                                    v-show="isFaqOpen(item.id)"
                                    class="border-t border-border/60 bg-muted/30 p-4 sm:p-5"
                                >
                                    <p class="text-xs sm:text-sm text-foreground/80 leading-relaxed whitespace-pre-line">
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
                    class="rounded-xl border border-dashed border-border bg-card p-12 text-center shadow-2xs space-y-3"
                >
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-muted">
                        <HelpCircle class="h-6 w-6 text-muted-foreground" />
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-foreground">
                            {{ searchQuery ? 'Tidak Ditemukan Pertanyaan yang Sesuai' : 'Belum Ada Pertanyaan pada Topik Ini' }}
                        </h3>
                        <p class="mt-1 text-sm text-muted-foreground max-w-sm mx-auto">
                            {{ searchQuery ? `Kata kunci "${searchQuery}" tidak cocok dengan pertanyaan yang tersedia saat ini.` : 'Pertanyaan untuk topik ini sedang disiapkan. Silakan pilih topik lainnya.' }}
                        </p>
                    </div>
                    <div class="pt-1">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="resetAllFilters"
                            class="cursor-pointer"
                        >
                            {{ searchQuery ? 'Bersihkan Pencarian' : 'Tampilkan Semua Pertanyaan' }}
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Kotak Bantuan WhatsApp Master Trader -->
            <div class="rounded-2xl border border-border bg-card p-6 sm:p-8 shadow-2xs">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-start gap-3.5">
                        <div class="rounded-xl bg-emerald-600 p-2.5 text-white shadow-md shadow-emerald-500/20 shrink-0">
                            <MessageCircle class="h-6 w-6" />
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-foreground">
                                Masih Memiliki Pertanyaan Lain?
                            </h3>
                            <p class="mt-0.5 text-xs sm:text-sm text-muted-foreground">
                                Konsultasikan kendala teknis atau pertanyaan khusus Anda langsung via WhatsApp resmi YZD Center.
                            </p>
                        </div>
                    </div>
                    <a
                        href="https://wa.me/#?text=Halo%20Pak%20Yazid%2C%20saya%20ingin%20bertanya%20seputar%20FAQ%20dan%20sistem%20copytrade%20YZD%20Center."
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

<style scoped>
.topic-scroll-container {
    scrollbar-width: thin;
    scrollbar-color: var(--border) transparent;
}
.topic-scroll-container::-webkit-scrollbar {
    height: 6px;
}
.topic-scroll-container::-webkit-scrollbar-track {
    background: transparent;
    border-radius: 9999px;
}
.topic-scroll-container::-webkit-scrollbar-thumb {
    background: var(--border);
    border-radius: 9999px;
}
.topic-scroll-container::-webkit-scrollbar-thumb:hover {
    background: var(--muted-foreground);
}
</style>

