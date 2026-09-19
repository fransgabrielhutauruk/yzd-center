<script setup>
import { ref, watch, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AppRichTextEditor from '@/Components/Common/AppRichTextEditor.vue';
import { Button } from '@/Components/ui/button';
import { toast } from 'vue-sonner';
import {
    FileText,
    Shield,
    Save,
    Clock,
    Eye,
    Edit3,
    Loader2,
} from 'lucide-vue-next';

const props = defineProps({
    terms: {
        type: Object,
        default: null,
    },
    privacy: {
        type: Object,
        default: null,
    },
});

const activeDoc = ref('terms_conditions'); // 'terms_conditions' | 'privacy_policy'
const isPreviewMode = ref(false);

const termsForm = useForm({
    key_name: 'terms_conditions',
    judul: props.terms?.judul || 'Syarat & Ketentuan Layanan YZD Center',
    konten: props.terms?.konten || '',
});

const privacyForm = useForm({
    key_name: 'privacy_policy',
    judul: props.privacy?.judul || 'Kebijakan Privasi YZD Center',
    konten: props.privacy?.konten || '',
});

const currentForm = ref(termsForm);

watch(
    activeDoc,
    (newDoc) => {
        currentForm.value = newDoc === 'terms_conditions' ? termsForm : privacyForm;
    },
    { immediate: true }
);

const submitUpdate = () => {
    const plainText = stripHtml(currentForm.value.konten).trim();
    if (!plainText) {
        toast.error('Isi pasal dokumen wajib diisi.');
        return;
    }
    currentForm.value.post('/admin/settings/legal', {
        preserveScroll: true,
    });
};

const stripHtml = (html) => {
    if (!html) return '';
    const withoutTags = String(html).replace(/<[^>]*>/g, ' ');
    const textarea = typeof document !== 'undefined' ? document.createElement('textarea') : null;
    const decoded = textarea
        ? (() => {
              textarea.innerHTML = withoutTags;
              return textarea.value;
          })()
        : withoutTags;
    return decoded.replace(/\s+/g, ' ');
};

const escapeHtml = (text) =>
    String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

const toPreviewHtml = (konten) => {
    if (!konten) return '';
    if (!/<\/?[a-z][\s\S]*>/i.test(konten)) {
        return escapeHtml(konten).replace(/\n/g, '<br />');
    }
    return konten;
};

const previewHtml = computed(
    () => toPreviewHtml(currentForm.value.konten) || 'Belum ada konten pasal yang dimasukkan.'
);
</script>

<template>
    <AdminLayout title="Pengelolaan Dokumen Legalitas">
        <div class="space-y-6">
            <!-- Header -->
            <div
                class="bg-card border-border flex flex-col justify-between gap-4 rounded-2xl border p-6 shadow-xs sm:flex-row sm:items-center"
            >
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <span class="bg-primary-500/10 text-primary-600 rounded-xl p-2">
                            <FileText class="h-5 w-5" />
                        </span>
                        <h1 class="text-foreground text-xl font-bold tracking-tight">
                            Pengelolaan Konten Legalitas Platform
                        </h1>
                    </div>
                    <p class="text-muted-foreground pl-11 text-xs">
                        Sesuaikan pasal Syarat & Ketentuan serta Kebijakan Privasi yang berlaku.
                    </p>
                </div>

                <!-- Mode Pratinjau Toggle -->
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="isPreviewMode = false"
                        :class="[
                            'flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors',
                            !isPreviewMode
                                ? 'bg-primary-600 text-white shadow-xs'
                                : 'bg-muted text-muted-foreground hover:text-foreground',
                        ]"
                    >
                        <Edit3 class="h-3.5 w-3.5" />
                        <span>Mode Editor</span>
                    </button>

                    <button
                        type="button"
                        @click="isPreviewMode = true"
                        :class="[
                            'flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors',
                            isPreviewMode
                                ? 'bg-primary-600 text-white shadow-xs'
                                : 'bg-muted text-muted-foreground hover:text-foreground',
                        ]"
                    >
                        <Eye class="h-3.5 w-3.5" />
                        <span>Pratinjau Pengguna</span>
                    </button>
                </div>
            </div>

            <!-- Tab Pemilihan Dokumen -->
            <div class="border-border flex items-center gap-3 border-b">
                <button
                    type="button"
                    @click="activeDoc = 'terms_conditions'"
                    :class="[
                        'flex items-center gap-2 border-b-2 pb-3 text-xs font-bold transition-colors',
                        activeDoc === 'terms_conditions'
                            ? 'border-primary-600 text-primary-600 dark:text-primary-400'
                            : 'text-muted-foreground hover:text-foreground border-transparent',
                    ]"
                >
                    <FileText class="h-4 w-4" />
                    <span>Syarat & Ketentuan</span>
                </button>

                <button
                    type="button"
                    @click="activeDoc = 'privacy_policy'"
                    :class="[
                        'flex items-center gap-2 border-b-2 pb-3 text-xs font-bold transition-colors',
                        activeDoc === 'privacy_policy'
                            ? 'border-primary-600 text-primary-600 dark:text-primary-400'
                            : 'text-muted-foreground hover:text-foreground border-transparent',
                    ]"
                >
                    <Shield class="h-4 w-4" />
                    <span>Kebijakan Privasi</span>
                </button>
            </div>

            <!-- Card Utama -->
            <div class="bg-card border-border space-y-6 rounded-2xl border p-6 shadow-xs">
                <!-- Info Riwayat Pembaruan -->
                <div
                    class="bg-muted/40 border-border text-muted-foreground flex flex-col justify-between gap-3 rounded-xl border p-3.5 text-xs sm:flex-row sm:items-center"
                >
                    <div class="flex items-center gap-1 text-[11px] whitespace-nowrap">
                        <Clock class="h-3.5 w-3.5" />
                        <span
                            >Terakhir diperbarui:
                            {{
                                (
                                    activeDoc === 'terms_conditions'
                                        ? terms?.updated_at
                                        : privacy?.updated_at
                                )
                                    ? new Date(
                                          activeDoc === 'terms_conditions'
                                              ? terms.updated_at
                                              : privacy.updated_at
                                      ).toLocaleDateString('id-ID', {
                                          year: 'numeric',
                                          month: 'short',
                                          day: 'numeric',
                                          hour: '2-digit',
                                          minute: '2-digit',
                                      })
                                    : 'Belum pernah'
                            }}</span
                        >
                    </div>
                </div>

                <!-- Mode 1: Editor Form -->
                <form v-if="!isPreviewMode" @submit.prevent="submitUpdate" class="space-y-4">
                    <!-- Judul Dokumen -->
                    <div class="space-y-1.5">
                        <label for="judul" class="text-foreground block text-xs font-bold">
                            Judul Dokumen Legalitas <span class="text-destructive">*</span>
                        </label>
                        <input
                            id="judul"
                            v-model="currentForm.judul"
                            type="text"
                            required
                            class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border px-3 py-2 text-sm font-semibold transition-all focus:ring-2 focus:outline-none"
                        />
                        <p
                            v-if="currentForm.errors.judul"
                            class="text-destructive text-xs font-medium"
                        >
                            {{ currentForm.errors.judul }}
                        </p>
                    </div>

                    <!-- Isi Pasal / Konten Dokumen -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label for="konten" class="text-foreground block text-xs font-bold">
                                Isi Pasal & Klausul Hukum <span class="text-destructive">*</span>
                            </label>
                        </div>
                        <AppRichTextEditor
                            v-model="currentForm.konten"
                            placeholder="Tuliskan pasal-pasal perjanjian atau kebijakan privasi di sini..."
                        />
                        <p
                            v-if="currentForm.errors.konten"
                            class="text-destructive text-xs font-medium"
                        >
                            {{ currentForm.errors.konten }}
                        </p>
                    </div>

                    <!-- Submit Button -->
                    <div class="border-border flex items-center justify-end gap-3 border-t pt-4">
                        <Button
                            type="submit"
                            :disabled="currentForm.processing"
                            class="bg-primary-600 hover:bg-primary-700 flex items-center gap-2 px-6 py-2.5 text-xs text-white shadow-xs"
                        >
                            <Loader2 v-if="currentForm.processing" class="h-4 w-4 animate-spin" />
                            <Save v-else class="h-4 w-4" />
                            <span>{{
                                currentForm.processing
                                    ? 'Menyimpan Perubahan...'
                                    : 'Simpan & Publikasikan Dokumen'
                            }}</span>
                        </Button>
                    </div>
                </form>

                <!-- Mode 2: Pratinjau Tampilan Pengguna -->
                <div v-else class="space-y-4">
                    <div
                        class="bg-muted/20 border-border max-h-[600px] space-y-4 overflow-y-auto rounded-xl border p-6"
                    >
                        <div class="border-border border-b pb-3">
                            <h2 class="text-foreground text-base font-bold">
                                {{ currentForm.judul }}
                            </h2>
                        </div>
                        <div
                            class="prose prose-sm max-w-none text-xs leading-relaxed [--tw-prose-body:var(--color-foreground)] [--tw-prose-bold:var(--color-foreground)] [--tw-prose-bullets:var(--color-muted-foreground)] [--tw-prose-counters:var(--color-muted-foreground)] [--tw-prose-headings:var(--color-foreground)] [--tw-prose-quote-borders:var(--color-primary-500)] [--tw-prose-quotes:var(--color-muted-foreground)] [&_blockquote]:my-2 [&_h2]:mt-5 [&_h2]:mb-1.5 [&_h3]:mt-4 [&_h3]:mb-1 [&_li]:my-1 [&_ol]:my-1.5 [&_p]:my-1.5 [&_ul]:my-1.5"
                            v-html="previewHtml"
                        ></div>
                    </div>

                    <div class="flex justify-end">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            class="text-xs"
                            @click="isPreviewMode = false"
                        >
                            Kembali ke Mode Editor
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
