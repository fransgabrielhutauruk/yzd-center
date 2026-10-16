<script setup>
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Calendar,
    ChevronRight,
} from 'lucide-vue-next';
import InvestorLayout from '@/Layouts/InvestorLayout.vue';
import { Button } from '@/Components/ui/button';

const props = defineProps({
    article: {
        type: Object,
        required: true,
    },
});

const isHtmlContent = (content) => {
    if (!content) return false;
    return /<[a-z][\s\S]*>/i.test(content);
};
</script>

<template>
    <Head :title="`${article.judul} - Pusat Bantuan`" />
    <InvestorLayout>
        <div class="space-y-6">
            <!-- Tombol Kembali Sederhana & Minimalis -->
            <div class="flex items-center justify-between pt-1">
                <Button
                    variant="ghost"
                    size="sm"
                    as-child
                    class="h-8 gap-1.5 px-2.5 text-xs font-semibold text-muted-foreground hover:text-foreground hover:bg-muted/60 transition-colors cursor-pointer -ml-2 rounded-lg"
                >
                    <Link href="/investor/education/help">
                        <ArrowLeft class="h-4 w-4" />
                        Kembali ke Pusat Bantuan
                    </Link>
                </Button>
            </div>

            <!-- Konten Utama Artikel -->
            <article class="rounded-2xl border border-border bg-card p-6 sm:p-8 shadow-2xs">
                <!-- Metadata Topik -->
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary dark:bg-primary/20">
                        {{ article.kategori?.nama }}
                    </span>
                </div>

                <!-- Judul Artikel -->
                <h1 class="mt-3 text-2xl sm:text-3xl font-extrabold tracking-tight text-foreground leading-tight">
                    {{ article.judul }}
                </h1>

                <!-- Info Tanggal -->
                <div class="mt-3 flex items-center gap-4 text-xs text-muted-foreground border-b border-border/60 pb-5">
                    <span class="flex items-center gap-1.5">
                        <Calendar class="h-3.5 w-3.5" />
                        Diperbarui: {{ article.updated_at }}
                    </span>
                </div>

                <!-- Ringkasan Tambahan -->
                <div
                    v-if="article.ringkasan"
                    class="mt-6 rounded-xl bg-muted/40 p-4 text-sm font-medium text-foreground border-l-4 border-primary"
                >
                    {{ article.ringkasan }}
                </div>

                <!-- Isi Konten Langkah Panduan (TipTap Typography Styling) -->
                <div
                    class="mt-6 prose prose-slate max-w-none dark:prose-invert text-foreground leading-relaxed text-sm sm:text-base
                           [--tw-prose-body:var(--color-foreground)]
                           [--tw-prose-headings:var(--color-foreground)]
                           [--tw-prose-bold:var(--color-foreground)]
                           [--tw-prose-counters:var(--color-muted-foreground)]
                           [--tw-prose-bullets:var(--color-muted-foreground)]
                           [--tw-prose-quotes:var(--color-muted-foreground)]
                           [--tw-prose-quote-borders:var(--color-primary-500)]
                           [--tw-prose-code:var(--color-foreground)]
                           [--tw-prose-pre-bg:var(--color-muted)]
                           [--tw-prose-pre-code:var(--color-foreground)]
                           [&_p]:my-3 [&_ul]:my-3 [&_ol]:my-3 [&_li]:my-1.5
                           [&_h1]:text-2xl [&_h1]:font-extrabold [&_h1]:mt-8 [&_h1]:mb-4
                           [&_h2]:text-xl [&_h2]:font-bold [&_h2]:mt-6 [&_h2]:mb-3
                           [&_h3]:text-base [&_h3]:font-semibold [&_h3]:mt-5 [&_h3]:mb-2
                           [&_blockquote]:border-l-4 [&_blockquote]:border-primary [&_blockquote]:bg-muted/40 [&_blockquote]:py-2 [&_blockquote]:px-4 [&_blockquote]:rounded-r-xl [&_blockquote]:my-4 [&_blockquote]:italic
                           [&_code]:bg-muted [&_code]:px-1.5 [&_code]:py-0.5 [&_code]:rounded [&_code]:font-mono [&_code]:text-xs
                           [&_pre]:bg-muted/80 [&_pre]:p-4 [&_pre]:rounded-xl [&_pre]:border [&_pre]:border-border [&_pre]:overflow-x-auto
                           [&_hr]:my-6 [&_hr]:border-border
                           [&_img]:rounded-xl [&_img]:border [&_img]:border-border [&_img]:shadow-xs [&_img]:my-4
                           [&_iframe]:rounded-xl [&_iframe]:w-full [&_iframe]:aspect-video [&_iframe]:my-4 [&_iframe]:border [&_iframe]:border-border
                           [&_a]:text-primary-600 dark:[&_a]:text-primary-400 [&_a]:underline [&_a]:font-medium hover:[&_a]:text-primary-700
                           [&_table]:w-full [&_table]:my-4 [&_table]:border-collapse [&_table]:border [&_table]:border-border [&_th]:border [&_th]:border-border [&_th]:bg-muted/50 [&_th]:p-2.5 [&_th]:text-left [&_th]:text-xs [&_th]:font-bold [&_td]:border [&_td]:border-border [&_td]:p-2.5 [&_td]:text-xs"
                >
                    <div
                        v-if="isHtmlContent(article.konten)"
                        v-html="article.konten"
                    />
                    <div
                        v-else
                        class="whitespace-pre-line space-y-4"
                    >
                        {{ article.konten }}
                    </div>
                </div>
            </article>

            <!-- Panduan Terkait dalam Kategori yang Sama -->
            <div
                v-if="article.related_articles?.length > 0"
                class="rounded-xl border border-border bg-card p-6 shadow-2xs"
            >
                <h3 class="text-base font-bold text-foreground mb-4">
                    Panduan Lain dalam Topik Ini:
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <Link
                        v-for="rel in article.related_articles"
                        :key="rel.id"
                        :href="`/investor/education/help/${rel.slug}`"
                        class="group flex items-start justify-between rounded-lg border border-border/70 p-3 hover:border-primary/50 hover:bg-muted/40 transition-all"
                    >
                        <div>
                            <h4 class="text-xs sm:text-sm font-semibold text-foreground group-hover:text-primary transition-colors">
                                {{ rel.judul }}
                            </h4>
                            <p v-if="rel.ringkasan" class="text-xs text-muted-foreground line-clamp-1 mt-0.5">
                                {{ rel.ringkasan }}
                            </p>
                        </div>
                        <ChevronRight class="h-4 w-4 shrink-0 text-muted-foreground group-hover:text-primary group-hover:translate-x-0.5 transition-all mt-0.5 ml-2" />
                    </Link>
                </div>
            </div>
        </div>
    </InvestorLayout>
</template>
