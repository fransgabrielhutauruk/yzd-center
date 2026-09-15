<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Button } from '@/Components/ui/button';
import { toast } from 'vue-sonner';
import {
    FileText,
    Shield,
    Save,
    Clock,
    Sparkles,
    Eye,
    Edit3,
    History,
    CheckCircle2,
    Loader2,
    Info,
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
    versi: props.terms?.versi || '1.0',
    konten: props.terms?.konten || '',
});

const privacyForm = useForm({
    key_name: 'privacy_policy',
    judul: props.privacy?.judul || 'Kebijakan Privasi YZD Center',
    versi: props.privacy?.versi || '1.0',
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
    currentForm.value.post('/admin/settings/legal', {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(
                `Dokumen ${activeDoc.value === 'terms_conditions' ? 'Syarat & Ketentuan' : 'Kebijakan Privasi'} berhasil diperbarui ke versi ${currentForm.value.versi}!`
            );
        },
    });
};
</script>

<template>
    <AdminLayout title="Pengelolaan Dokumen Legalitas">
        <div class="mx-auto max-w-5xl space-y-6">
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
                        Sesuaikan pasal Syarat & Ketentuan serta Kebijakan Privasi yang berlaku
                        untuk seluruh investor.
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
                    <span>Syarat & Ketentuan (T&C)</span>
                    <span
                        class="bg-muted text-muted-foreground rounded px-1.5 py-0.5 font-mono text-[10px] font-normal"
                    >
                        v{{ terms?.versi || '1.0' }}
                    </span>
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
                    <span
                        class="bg-muted text-muted-foreground rounded px-1.5 py-0.5 font-mono text-[10px] font-normal"
                    >
                        v{{ privacy?.versi || '1.0' }}
                    </span>
                </button>
            </div>

            <!-- Card Utama -->
            <div class="bg-card border-border space-y-6 rounded-2xl border p-6 shadow-xs">
                <!-- Info Versi & Riwayat -->
                <div
                    class="bg-muted/40 border-border text-muted-foreground flex flex-col justify-between gap-3 rounded-xl border p-3.5 text-xs sm:flex-row sm:items-center"
                >
                    <div class="flex items-center gap-2">
                        <Info class="text-primary-500 h-4 w-4 shrink-0" />
                        <span>
                            Setiap perubahan dokumen akan mengikat investor baru yang mendaftar
                            menggunakan versi ini.
                        </span>
                    </div>
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
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <!-- Judul Dokumen (2 Col) -->
                        <div class="space-y-1.5 sm:col-span-2">
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

                        <!-- Versi Dokumen (1 Col) -->
                        <div class="space-y-1.5">
                            <label for="versi" class="text-foreground block text-xs font-bold">
                                Nomor Versi Dokumen <span class="text-destructive">*</span>
                            </label>
                            <input
                                id="versi"
                                v-model="currentForm.versi"
                                type="text"
                                required
                                placeholder="Contoh: 1.1 atau 2.0"
                                class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border px-3 py-2 font-mono text-sm transition-all focus:ring-2 focus:outline-none"
                            />
                            <p
                                v-if="currentForm.errors.versi"
                                class="text-destructive text-xs font-medium"
                            >
                                {{ currentForm.errors.versi }}
                            </p>
                        </div>
                    </div>

                    <!-- Isi Pasal / Konten Dokumen -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label for="konten" class="text-foreground block text-xs font-bold">
                                Isi Pasal & Klausul Hukum <span class="text-destructive">*</span>
                            </label>
                            <span class="text-muted-foreground text-[11px]">
                                Mendukung pemisahan baris dan format teks pasal bernomor
                            </span>
                        </div>
                        <textarea
                            id="konten"
                            v-model="currentForm.konten"
                            rows="16"
                            required
                            placeholder="Tuliskan pasal-pasal perjanjian atau kebijakan privasi di sini..."
                            class="bg-background border-input focus:ring-primary-500 w-full resize-y rounded-xl border p-4 font-mono text-xs leading-relaxed transition-all focus:ring-2 focus:outline-none"
                        ></textarea>
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
                        <div class="border-border border-b pb-4">
                            <h2 class="text-foreground text-base font-bold">
                                {{ currentForm.judul }}
                            </h2>
                            <div class="text-muted-foreground mt-1 flex items-center gap-3 text-xs">
                                <span
                                    >Versi:
                                    <strong class="text-foreground"
                                        >v{{ currentForm.versi }}</strong
                                    ></span
                                >
                                <span>&bull;</span>
                                <span>Platform: YZD Center</span>
                            </div>
                        </div>

                        <div
                            class="text-foreground text-xs leading-relaxed font-normal whitespace-pre-line"
                        >
                            {{ currentForm.konten || 'Belum ada konten pasal yang dimasukkan.' }}
                        </div>
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
