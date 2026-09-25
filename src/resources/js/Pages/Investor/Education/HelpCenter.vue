<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    HelpCircle,
    Search,
    Video,
    BookOpen,
    ArrowRight,
    Wallet,
    UserCheck,
    TrendingUp,
    Users,
    ChevronRight,
    Eye,
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

const handleSearch = () => {
    router.get(
        '/investor/education/help',
        {
            search: searchQuery.value || undefined,
            kategori: props.help.active_slug,
        },
        { preserveState: true, preserveScroll: true }
    );
};

const selectCategory = (slug) => {
    router.get(
        '/investor/education/help',
        {
            kategori: slug,
            search: searchQuery.value || undefined,
        },
        { preserveState: true, preserveScroll: true }
    );
};

const getCategoryIcon = (iconName) => {
    switch (iconName) {
        case 'Wallet':
            return Wallet;
        case 'UserCheck':
            return UserCheck;
        case 'TrendingUp':
            return TrendingUp;
        case 'Users':
            return Users;
        default:
            return HelpCircle;
    }
};
</script>

<template>
    <InvestorLayout title="Pusat Bantuan & Tutorial">
        <div class="space-y-6">
            <!-- Banner Utama -->
            <div class="rounded-2xl bg-gradient-to-r from-slate-900 via-indigo-950 to-blue-900 p-6 sm:p-8 text-white shadow-md">
                <div class="max-w-2xl">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 text-xs font-medium backdrop-blur-sm">
                        <HelpCircle class="h-3.5 w-3.5 text-blue-300" />
                        Pusat Bantuan & Panduan
                    </span>
                    <h1 class="mt-3 text-2xl sm:text-3xl font-bold tracking-tight">
                        Ada yang Bisa Kami Bantu?
                    </h1>
                    <p class="mt-2 text-sm sm:text-base text-blue-100/90 leading-relaxed">
                        Temukan petunjuk bertahap, tutorial video, dan solusi kendala teknis seputar deposit, akun MT4, dan sistem copytrade.
                    </p>
                </div>

                <!-- Input Pencarian Bantuan -->
                <div class="mt-6 max-w-xl">
                    <div class="relative">
                        <Search class="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari topik panduan (misal: deposit HFM, KTP, perjanjian)..."
                            @keyup.enter="handleSearch"
                            class="w-full rounded-xl border-0 bg-white/95 py-3 pl-10 pr-24 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-400"
                        />
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

            <!-- Grid Pemisahan Kategori Topik -->
            <div>
                <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-3">
                    Pilih Topik Panduan
                </h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                    <button
                        v-for="cat in help.categories"
                        :key="cat.id"
                        type="button"
                        @click="selectCategory(cat.slug)"
                        :class="[
                            'flex flex-col items-start p-4 rounded-xl border text-left transition-all',
                            help.active_slug === cat.slug
                                ? 'border-primary bg-primary/5 ring-1 ring-primary dark:bg-primary/10'
                                : 'border-slate-200 bg-white hover:border-slate-300 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700',
                        ]"
                    >
                        <div
                            :class="[
                                'flex h-9 w-9 items-center justify-center rounded-lg mb-2.5',
                                help.active_slug === cat.slug
                                    ? 'bg-primary text-primary-foreground'
                                    : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
                            ]"
                        >
                            <component :is="getCategoryIcon(cat.icon)" class="h-4 w-4" />
                        </div>
                        <h3 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white line-clamp-1">
                            {{ cat.nama }}
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            {{ cat.articles_count }} artikel
                        </p>
                    </button>
                </div>
            </div>

            <!-- Daftar Artikel Panduan dalam Topik Aktif -->
            <div class="space-y-4">
                <div class="flex items-center justify-between border-b border-slate-200 pb-3 dark:border-slate-800">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white">
                            Daftar Panduan Bertahap
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Pilih artikel untuk membaca panduan lengkap dan menonton tutorial video.
                        </p>
                    </div>
                </div>

                <div v-if="help.articles.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <Link
                        v-for="article in help.articles"
                        :key="article.id"
                        :href="`/investor/education/help/${article.slug}`"
                        class="group flex flex-col justify-between rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition-all hover:border-primary/50 hover:shadow-md dark:border-slate-800 dark:bg-slate-900"
                    >
                        <div>
                            <div class="flex items-start justify-between gap-3">
                                <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                    {{ article.kategori_nama }}
                                </span>
                                <span
                                    v-if="article.video_url"
                                    class="inline-flex items-center gap-1 rounded bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-600 dark:bg-red-950/50 dark:text-red-400"
                                >
                                    <Video class="h-3 w-3" />
                                    Video Tutorial
                                </span>
                            </div>

                            <h3 class="mt-3 text-base font-bold text-slate-900 group-hover:text-primary transition-colors dark:text-white dark:group-hover:text-primary">
                                {{ article.judul }}
                            </h3>

                            <p class="mt-2 text-xs sm:text-sm text-slate-600 dark:text-slate-300 line-clamp-2 leading-relaxed">
                                {{ article.ringkasan || 'Klik untuk membaca instruksi lengkap...' }}
                            </p>
                        </div>

                        <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3 text-xs text-slate-500 dark:border-slate-800">
                            <span class="flex items-center gap-1">
                                <Eye class="h-3.5 w-3.5" />
                                Dibaca {{ article.views_count }} kali
                            </span>
                            <span class="font-semibold text-primary inline-flex items-center gap-1 group-hover:translate-x-0.5 transition-transform">
                                Baca Panduan
                                <ChevronRight class="h-3.5 w-3.5" />
                            </span>
                        </div>
                    </Link>
                </div>

                <!-- Empty State -->
                <div
                    v-else
                    class="rounded-xl border border-dashed border-slate-200 bg-white p-12 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900"
                >
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800">
                        <HelpCircle class="h-6 w-6 text-slate-400" />
                    </div>
                    <h3 class="mt-3 text-base font-semibold text-slate-900 dark:text-white">
                        Belum Ada Artikel pada Topik Ini
                    </h3>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400 max-w-sm mx-auto">
                        Artikel panduan untuk topik ini sedang disiapkan. Silakan pilih topik kategori lainnya.
                    </p>
                </div>
            </div>
        </div>
    </InvestorLayout>
</template>
