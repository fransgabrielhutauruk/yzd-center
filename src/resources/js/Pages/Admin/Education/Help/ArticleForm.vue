<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import {
    FileText,
    FolderKanban,
    Eye,
    FileEdit,
    CheckCircle2,
    Calendar,
    Maximize2,
    Minimize2,
} from 'lucide-vue-next';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AppFullTextEditor from '@/Components/Common/AppFullTextEditor.vue';
import AppButton from '@/Components/Common/AppButton.vue';
import AppCheckbox from '@/Components/Common/AppCheckbox.vue';
import { Button } from '@/Components/ui/button';

const props = defineProps({
    article: {
        type: Object,
        default: null,
    },
    categories: {
        type: Array,
        default: () => [],
    },
});

const isEditing = computed(() => Boolean(props.article?.id));

const pageTitle = computed(() =>
    isEditing.value ? 'Edit Artikel' : 'Tulis Artikel Baru'
);

const form = useForm({
    kategori_id: props.article?.kategori_id || (props.categories[0]?.id ?? ''),
    judul: props.article?.judul || '',
    slug: props.article?.slug || '',
    ringkasan: props.article?.ringkasan || '',
    konten: props.article?.konten || '',
    is_published: props.article ? Boolean(props.article.is_published) : true,
});

// Mode Penuh (Zen / Fokus) untuk mengisi seluruh main content
const isFullContent = ref(false);

const toggleFullContent = () => {
    isFullContent.value = !isFullContent.value;
};

const handleKeydown = (e) => {
    if (e.key === 'Escape' && isFullContent.value) {
        isFullContent.value = false;
    }
};

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
});

// Fitur auto slug otomatis di belakang layar
const generateSlug = (text) => {
    return text
        .toString()
        .toLowerCase()
        .trim()
        .replace(/&/g, '-dan-')
        .replace(/[\s\W-]+/g, '-')
        .replace(/^-+|-+$/g, '');
};

watch(
    () => form.judul,
    (val) => {
        if (!isEditing.value) {
            form.slug = generateSlug(val);
        }
    }
);

const submit = () => {
    if (isEditing.value) {
        form.put(`/admin/education/help/articles/${props.article.id}`, {
            preserveScroll: true,
        });
    } else {
        form.post('/admin/education/help/articles', {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head :title="pageTitle" />

    <AdminLayout
        :title="isFullContent ? '' : pageTitle"
        :subtitle="
            isFullContent
                ? ''
                : isEditing
                  ? 'Perbarui konten, kategori, dan pengaturan artikel.'
                  : 'Buat artikel bantuan atau panduan baru.'
        "
        :breadcrumbs="
            isFullContent
                ? []
                : [
                      { label: 'Bantuan & Informasi' },
                      { label: 'Pusat Bantuan', href: '/admin/education/help?tab=articles' },
                      { label: isEditing ? 'Edit Artikel' : 'Tulis Baru' },
                  ]
        "
    >
        <template v-if="!isFullContent" #header-actions>
            <div class="flex items-center gap-2">
                <AppButton
                    type="back"
                    href="/admin/education/help?tab=articles"
                    label="Kembali ke Daftar"
                    size="sm"
                />
            </div>
        </template>

        <form @submit.prevent="submit" :class="isFullContent ? 'space-y-0' : 'space-y-6'">
            <!-- Grid Layout: Konten Utama & Sidebar Pengaturan -->
            <div
                :class="
                    isFullContent ? 'block' : 'grid grid-cols-1 items-start gap-6 lg:grid-cols-12'
                "
            >
                <!-- KOLOM KONTEN UTAMA -->
                <div :class="isFullContent ? 'w-full' : 'space-y-6 lg:col-span-8'">
                    <!-- Kartu Judul Artikel (Sembunyi di Mode Penuh) -->
                    <div
                        v-if="!isFullContent"
                        class="bg-card border-border rounded-xl border p-5 shadow-xs sm:p-6"
                    >
                        <label
                            class="text-foreground mb-1.5 block text-xs font-semibold tracking-wider uppercase"
                        >
                            Judul Artikel Panduan <span class="text-destructive">*</span>
                        </label>
                        <input
                            v-model="form.judul"
                            type="text"
                            placeholder="Contoh: Cara Menghubungkan Akun MT4 ke Broker Terpercaya"
                            class="bg-background border-input focus:ring-primary-500 placeholder:text-muted-foreground w-full rounded-xl border px-3.5 py-2.5 text-base font-bold transition-all placeholder:font-normal focus:ring-2 focus:outline-none"
                        />
                        <p
                            v-if="form.errors.judul"
                            class="text-destructive mt-1 text-xs font-medium"
                        >
                            {{ form.errors.judul }}
                        </p>
                    </div>

                    <!-- Kartu Full Text Editor Konten Panduan -->
                    <div
                        :class="[
                            'bg-card border-border rounded-xl border shadow-xs transition-all duration-200',
                            isFullContent
                                ? 'flex min-h-[calc(100vh-140px)] flex-col space-y-4 p-4 sm:p-6'
                                : 'space-y-3 p-5 sm:p-6',
                        ]"
                    >
                        <div
                            class="border-border/60 flex items-center justify-between gap-3 border-b pb-3"
                        >
                            <!-- Judul & Info Section -->
                            <div class="flex items-center gap-2">
                                <FileText class="text-primary-600 h-4 w-4 shrink-0" />
                                <div>
                                    <h3 class="text-foreground text-sm font-bold whitespace-nowrap">
                                        Isi Konten Artikel
                                        <span class="text-destructive">*</span>
                                    </h3>
                                    <p
                                        v-if="!isFullContent"
                                        class="text-muted-foreground mt-0.5 text-[11px]"
                                    >
                                        Format teks terstruktur, langkah bernomor, kutipan catatan,
                                        gambar panduan, dan video embed.
                                    </p>
                                </div>
                            </div>

                            <!-- Tombol Toggle Mode Penuh -->
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                @click="toggleFullContent"
                                class="text-foreground h-8 cursor-pointer gap-1.5 text-xs"
                                :title="
                                    isFullContent
                                        ? 'Keluar Mode Penuh (Esc)'
                                        : 'Penuhi seluruh area konten (sembunyikan breadcrumb & panel samping)'
                                "
                            >
                                <component
                                    :is="isFullContent ? Minimize2 : Maximize2"
                                    class="h-3.5 w-3.5"
                                />
                                <span >{{
                                    isFullContent ? 'Keluar Mode Penuh' : 'Mode Penuh'
                                }}</span>
                                <kbd
                                    v-if="isFullContent"
                                    class="border-border hidden rounded border px-1.5 py-0.5 font-mono text-[10px] md:inline-block"
                                    >Esc</kbd
                                >
                            </Button>
                        </div>

                        <!-- Full Rich Text Editor Tiptap -->
                        <div :class="isFullContent ? 'flex flex-1 flex-col' : ''">
                            <AppFullTextEditor
                                v-model="form.konten"
                                placeholder="Tuliskan petunjuk langkah-langkah, solusi kendala, gambar ilustrasi, atau tabel panduan di sini..."
                                :min-height="
                                    isFullContent ? 'min-h-[calc(100vh-270px)]' : 'min-h-[460px]'
                                "
                            />
                        </div>

                        <p v-if="form.errors.konten" class="text-destructive text-xs font-medium">
                            {{ form.errors.konten }}
                        </p>
                    </div>
                </div>

                <!-- KOLOM KANAN (SIDEBAR PENGATURAN) (Sembunyi di Mode Penuh) -->
                <div v-if="!isFullContent" class="space-y-6 lg:col-span-4">
                    <!-- Ringkasan / Excerpt (Sembunyi di Mode Penuh) -->
                    <div
                        v-if="!isFullContent"
                        class="bg-card border-border space-y-4 rounded-xl border p-5 shadow-xs"
                    >
                        <div>
                            <label
                                class="text-foreground mb-1 block text-xs font-semibold tracking-wider uppercase"
                            >
                                Ringkasan Panduan (Opsional)
                            </label>
                            <p class="text-muted-foreground mb-2 text-xs">
                                Penjelasan singkat yang muncul pada kartu pencarian dan pratinjau
                                daftar bantuan.
                            </p>
                            <textarea
                                v-model="form.ringkasan"
                                rows="3"
                                placeholder="Tuliskan 1-2 kalimat ringkasan tentang apa yang dipelajari investor dalam artikel ini..."
                                class="bg-background border-input focus:ring-primary-500 placeholder:text-muted-foreground w-full rounded-xl border px-3.5 py-2 text-xs transition-all focus:ring-2 focus:outline-none"
                            ></textarea>
                            <p
                                v-if="form.errors.ringkasan"
                                class="text-destructive mt-1 text-xs font-medium"
                            >
                                {{ form.errors.ringkasan }}
                            </p>
                        </div>
                    </div>

                    <!-- Panel Topik Kategori -->
                    <div class="bg-card border-border space-y-4 rounded-xl border p-5 shadow-xs">
                        <h4
                            class="text-muted-foreground border-border flex items-center gap-2 border-b pb-2 text-xs font-bold tracking-wider uppercase"
                        >
                            <span>Kategori Topik</span>
                        </h4>

                        <!-- Pilihan Kategori -->
                        <div>
                            <label class="text-foreground mb-1.5 block text-xs font-semibold">
                                Kategori Topik <span class="text-destructive">*</span>
                            </label>
                            <select
                                v-model="form.kategori_id"
                                class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border px-3 py-2 text-xs font-medium focus:ring-2 focus:outline-none"
                            >
                                <option value="" disabled>Pilih Kategori Topik</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                    {{ cat.nama }}
                                </option>
                            </select>
                            <p
                                v-if="form.errors.kategori_id"
                                class="text-destructive mt-1 text-xs font-medium"
                            >
                                {{ form.errors.kategori_id }}
                            </p>
                        </div>
                    </div>

                    <!-- Panel Status Publikasi & Simpan -->
                    <div class="bg-card border-border space-y-4 rounded-xl border p-5 shadow-xs">
                        <h4
                            class="text-muted-foreground border-border flex items-center justify-between border-b pb-2 text-xs font-bold tracking-wider uppercase"
                        >
                            <span>Status Publikasi</span>
                            <span
                                :class="[
                                    'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold',
                                    form.is_published
                                        ? 'border border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800/60 dark:bg-emerald-950/50 dark:text-emerald-400'
                                        : 'border border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800/60 dark:bg-amber-950/50 dark:text-amber-400',
                                ]"
                            >
                                <CheckCircle2 v-if="form.is_published" class="h-3 w-3" />
                                <FileEdit v-else class="h-3 w-3" />
                                {{ form.is_published ? 'Diterbitkan' : 'Draf (Disembunyikan)' }}
                            </span>
                        </h4>

                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-foreground block text-xs font-semibold">
                                    Tampilkan ke Investor
                                </span>
                                <span class="text-muted-foreground block text-[11px] leading-tight">
                                    Jika aktif, artikel dapat dicari dan dibaca investor.
                                </span>
                            </div>
                            <AppCheckbox
                                v-model="form.is_published"
                                aria-label="Tampilkan ke Investor"
                            />
                        </div>

                        <!-- Info Views & Update jika mode edit -->
                        <div
                            v-if="isEditing"
                            class="border-border text-muted-foreground space-y-2 border-t pt-3 text-xs"
                        >
                            <div class="flex items-center justify-between">
                                <span class="flex items-center gap-1.5">
                                    <Eye class="h-3.5 w-3.5" />
                                    Total Dibaca
                                </span>
                                <span class="text-foreground font-mono font-bold"
                                    >{{ props.article.views_count ?? 0 }} kali</span
                                >
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="flex items-center gap-1.5">
                                    <Calendar class="h-3.5 w-3.5" />
                                    Terakhir Update
                                </span>
                                <span class="text-foreground">{{
                                    props.article.updated_at || '-'
                                }}</span>
                            </div>
                        </div>

                        <!-- Tombol Submit Action -->
                        <div class="pt-2">
                            <AppButton
                                type="save"
                                :label="isEditing ? 'Simpan Perubahan' : 'Terbitkan Panduan'"
                                :loading="form.processing"
                                loading-text="Menyimpan..."
                                class="w-full text-xs font-semibold shadow-xs"
                                @click="submit"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </AdminLayout>
</template>
