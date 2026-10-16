<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    HelpCircle,
    Search,
    X,
} from 'lucide-vue-next';
import InvestorLayout from '@/Layouts/InvestorLayout.vue';
import { Button } from '@/Components/ui/button';

const props = defineProps({
    help: {
        type: Object,
        required: true,
    },
});

const searchQuery = ref(props.help.search || '');

// Status kategori aktif
const isAllActive = computed(() => !props.help.active_slug || props.help.active_slug === 'all');

// Total semua artikel panduan
const totalArticlesCount = computed(() => {
    if (props.help.total_articles_count !== undefined) {
        return props.help.total_articles_count;
    }
    return props.help.categories?.reduce((acc, c) => acc + (c.articles_count || 0), 0) ?? 0;
});

// Helper eksekusi filter & search
const applyFilters = () => {
    router.get(
        '/investor/education/help',
        {
            search: searchQuery.value ? searchQuery.value.trim() : undefined,
            kategori: props.help.active_slug && props.help.active_slug !== 'all' ? props.help.active_slug : undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true }
    );
};

// --- Live Search dengan Debounce 350ms ---
let debounceTimeout = null;
watch(searchQuery, () => {
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => {
        applyFilters();
    }, 350);
});

const clearSearch = () => {
    searchQuery.value = '';
    applyFilters();
};

const selectCategory = (slug) => {
    router.get(
        '/investor/education/help',
        {
            kategori: slug && slug !== 'all' ? slug : undefined,
            search: searchQuery.value ? searchQuery.value.trim() : undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true }
    );
};
</script>

<template>
    <Head title="Pusat Bantuan" />
    <InvestorLayout
        title="Pusat Bantuan"
        subtitle="Temukan bantuan atau panduan pada artikel-artikel berikut."
        :breadcrumbs="[
            { label: 'Bantuan & Edukasi' },
            { label: 'Pusat Bantuan' }
        ]"
    >
        <div class="space-y-6">
            <!-- Input Pencarian Bantuan (Live Search) -->
            <div class="max-w-xl">
                <div class="relative">
                    <Search class="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground pointer-events-none" />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari panduan..."
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

            <!-- Deretan Pills Kategori Topik (Scrollable Horizontal jika melebihi lebar konten) -->
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-muted-foreground">
                        Pilih Topik
                    </h2>
                    <span class="text-xs text-muted-foreground font-medium">
                        {{ (help.categories.length || 0) + 1 }} Topik
                    </span>
                </div>

                <div class="relative w-full">
                    <div class="topic-scroll-container flex items-center gap-2 overflow-x-auto pb-2.5 pt-0.5">
                        <!-- Pill: Semua (Default) -->
                        <button
                            type="button"
                            @click="selectCategory('all')"
                            :class="[
                                'group inline-flex items-center gap-2 whitespace-nowrap rounded-lg px-3.5 py-2 text-xs font-semibold transition-all cursor-pointer shrink-0 border shadow-2xs',
                                isAllActive
                                    ? 'bg-primary text-primary-foreground border-primary shadow-xs'
                                    : 'bg-card text-foreground border-border hover:bg-muted/70 hover:border-border/80',
                            ]"
                        >
                            <span>Semua</span>
                            <span
                                :class="[
                                    'rounded-full px-1.5 py-0.2 text-[10px] font-bold font-mono transition-colors',
                                    isAllActive
                                        ? 'bg-primary-foreground/20 text-primary-foreground'
                                        : 'bg-muted text-muted-foreground group-hover:text-foreground',
                                ]"
                            >
                                {{ totalArticlesCount }}
                            </span>
                        </button>

                        <!-- Pill: Kategori Individual -->
                        <button
                            v-for="cat in help.categories"
                            :key="cat.id"
                            type="button"
                            @click="selectCategory(cat.slug)"
                            :class="[
                                'group inline-flex items-center gap-2 whitespace-nowrap rounded-lg px-3.5 py-2 text-xs font-semibold transition-all cursor-pointer shrink-0 border shadow-2xs',
                                help.active_slug === cat.slug
                                    ? 'bg-primary text-primary-foreground border-primary shadow-xs'
                                    : 'bg-card text-foreground border-border hover:bg-muted/70 hover:border-border/80',
                            ]"
                        >
                            <span>{{ cat.nama }}</span>
                            <span
                                :class="[
                                    'rounded-full px-1.5 py-0.2 text-[10px] font-bold font-mono transition-colors',
                                    help.active_slug === cat.slug
                                        ? 'bg-primary-foreground/20 text-primary-foreground'
                                        : 'bg-muted text-muted-foreground group-hover:text-foreground',
                                ]"
                            >
                                {{ cat.articles_count }}
                            </span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Daftar Artikel Panduan dalam Topik Aktif -->
            <div class="space-y-4">
                <div class="flex items-center justify-between border-b border-border pb-3">
                    <div>
                        <h2 class="text-lg font-bold text-foreground">
                            Daftar Panduan
                        </h2>
                        <p class="text-xs text-muted-foreground">
                            {{ searchQuery ? `Menampilkan hasil pencarian untuk "${searchQuery}"` : 'Pilih artikel untuk membaca secara lengkap.' }}
                        </p>
                    </div>
                    <span v-if="help.articles.length > 0" class="text-xs font-medium text-muted-foreground">
                        {{ help.articles.length }} artikel
                    </span>
                </div>

                <div v-if="help.articles.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <Link
                        v-for="article in help.articles"
                        :key="article.id"
                        :href="`/investor/education/help/${article.slug}`"
                        class="group rounded-xl border border-border bg-card p-5 shadow-2xs transition-all hover:border-primary/50 hover:shadow-xs block"
                    >
                        <h3 class="text-base font-bold text-foreground group-hover:text-primary transition-colors">
                            {{ article.judul }}
                        </h3>

                        <p class="mt-2 text-xs sm:text-sm text-muted-foreground line-clamp-2 leading-relaxed">
                            {{ article.ringkasan || 'Klik untuk membaca instruksi lengkap...' }}
                        </p>
                    </Link>
                </div>

                <!-- Empty State -->
                <div
                    v-else
                    class="rounded-xl border border-dashed border-border bg-card p-12 text-center shadow-2xs space-y-3"
                >
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-muted">
                        <HelpCircle class="h-6 w-6 text-muted-foreground" />
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-foreground">
                            {{ searchQuery ? 'Tidak Ditemukan Panduan yang Sesuai' : 'Belum Ada Panduan pada Topik Ini' }}
                        </h3>
                        <p class="mt-1 text-sm text-muted-foreground max-w-sm mx-auto">
                            {{ searchQuery
                                ? `Tidak ada artikel panduan yang cocok dengan kata kunci "${searchQuery}". Silakan coba kata kunci lain.`
                                : 'Artikel panduan untuk topik ini sedang disiapkan. Silakan pilih topik kategori lainnya.'
                            }}
                        </p>
                    </div>
                    <div class="pt-1">
                        <Button
                            v-if="searchQuery"
                            variant="outline"
                            size="sm"
                            @click="clearSearch"
                            class="cursor-pointer"
                        >
                            Bersihkan Pencarian
                        </Button>
                        <Button
                            v-else-if="!isAllActive"
                            variant="outline"
                            size="sm"
                            @click="selectCategory('all')"
                            class="cursor-pointer"
                        >
                            Tampilkan Semua Panduan
                        </Button>
                    </div>
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
