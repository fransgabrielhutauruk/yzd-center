<script setup>
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Eye,
    Calendar,
    Video,
    BookOpen,
    HelpCircle,
    ChevronRight,
    Share2,
} from 'lucide-vue-next';
import InvestorLayout from '@/Layouts/InvestorLayout.vue';
import { Button } from '@/Components/ui/button';

const props = defineProps({
    article: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <InvestorLayout :title="article.judul">
        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Navigasi Kembali / Breadcrumb -->
            <div>
                <Link
                    href="/investor/education/help"
                    class="inline-flex items-center gap-1.5 text-xs sm:text-sm font-medium text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white transition-colors"
                >
                    <ArrowLeft class="h-4 w-4" />
                    Kembali ke Pusat Bantuan
                </Link>
            </div>

            <!-- Konten Utama Artikel -->
            <article class="rounded-2xl border border-slate-200 bg-white p-6 sm:p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <!-- Metadata Topik -->
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary dark:bg-primary/20">
                        {{ article.kategori?.nama }}
                    </span>
                    <span
                        v-if="article.video_url"
                        class="inline-flex items-center gap-1 rounded-full bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-600 dark:bg-red-950/50 dark:text-red-400"
                    >
                        <Video class="h-3.5 w-3.5" />
                        Video Tutorial
                    </span>
                </div>

                <!-- Judul Artikel -->
                <h1 class="mt-3 text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white leading-tight">
                    {{ article.judul }}
                </h1>

                <!-- Info Tanggal & Views -->
                <div class="mt-3 flex items-center gap-4 text-xs text-slate-400 border-b border-slate-100 pb-5 dark:border-slate-800">
                    <span class="flex items-center gap-1">
                        <Calendar class="h-3.5 w-3.5" />
                        Diperbarui: {{ article.updated_at }}
                    </span>
                    <span class="flex items-center gap-1">
                        <Eye class="h-3.5 w-3.5" />
                        Dibaca: {{ article.views_count }} kali
                    </span>
                </div>

                <!-- Pemutar Video YouTube 16:9 (Jika Ada) -->
                <div v-if="article.youtube_id" class="mt-6 overflow-hidden rounded-xl bg-slate-950 shadow-md">
                    <div class="relative aspect-video w-full">
                        <iframe
                            :src="`https://www.youtube.com/embed/${article.youtube_id}`"
                            title="YouTube video player"
                            frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            allowfullscreen
                            loading="lazy"
                            class="absolute inset-0 h-full w-full"
                        ></iframe>
                    </div>
                </div>

                <!-- Ringkasan Tambahan -->
                <div
                    v-if="article.ringkasan"
                    class="mt-6 rounded-xl bg-slate-50 p-4 text-sm font-medium text-slate-700 dark:bg-slate-800/60 dark:text-slate-300 border-l-4 border-primary"
                >
                    {{ article.ringkasan }}
                </div>

                <!-- Isi Konten Langkah Panduan -->
                <div class="mt-6 prose prose-slate max-w-none text-slate-700 dark:text-slate-300 dark:prose-invert">
                    <div class="whitespace-pre-line text-sm sm:text-base leading-relaxed space-y-4">
                        {{ article.konten }}
                    </div>
                </div>
            </article>

            <!-- Artikel Terkait dalam Kategori yang Sama -->
            <div v-if="article.related_articles?.length > 0" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4">
                    Panduan Lain dalam Topik Ini:
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <Link
                        v-for="rel in article.related_articles"
                        :key="rel.id"
                        :href="`/investor/education/help/${rel.slug}`"
                        class="group flex items-start justify-between rounded-lg border border-slate-100 p-3 hover:border-primary/40 hover:bg-slate-50 transition-all dark:border-slate-800 dark:hover:bg-slate-800"
                    >
                        <div>
                            <h4 class="text-xs sm:text-sm font-semibold text-slate-900 group-hover:text-primary transition-colors dark:text-white">
                                {{ rel.judul }}
                            </h4>
                            <p v-if="rel.ringkasan" class="text-xs text-slate-500 dark:text-slate-400 line-clamp-1 mt-0.5">
                                {{ rel.ringkasan }}
                            </p>
                        </div>
                        <ChevronRight class="h-4 w-4 shrink-0 text-slate-400 group-hover:text-primary group-hover:translate-x-0.5 transition-all mt-0.5 ml-2" />
                    </Link>
                </div>
            </div>
        </div>
    </InvestorLayout>
</template>
