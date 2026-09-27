<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import {
    Loader2,
    Save,
    Settings,
    Link2,
    TrendingUp,
    FileText,
    UploadCloud,
    Trash2,
    ExternalLink,
    Layers,
    CheckCircle2,
} from 'lucide-vue-next';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Button } from '@/Components/ui/button';
import { toast } from 'vue-sonner';

const props = defineProps({
    pengaturan: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    link_referral_broker: props.pengaturan?.link_referral_broker || '',
    link_master_akun: props.pengaturan?.link_master_akun || '',
    tier_1_min: props.pengaturan?.tier_1_min ?? 100,
    tier_2_min: props.pengaturan?.tier_2_min ?? 5000,
    tier_3_min: props.pengaturan?.tier_3_min ?? 300000,
    tier_1_desc: props.pengaturan?.tier_1_desc || '',
    tier_2_desc: props.pengaturan?.tier_2_desc || '',
    tier_3_desc: props.pengaturan?.tier_3_desc || '',
    tier_1_pdf: null,
    tier_2_pdf: null,
    tier_3_pdf: null,
    remove_tier_1_pdf: false,
    remove_tier_2_pdf: false,
    remove_tier_3_pdf: false,
});

const fileNames = ref({
    tier_1: '',
    tier_2: '',
    tier_3: '',
});

const handleFileChange = (e, tierKey) => {
    const file = e.target.files?.[0];
    if (!file) return;

    if (file.type !== 'application/pdf') {
        toast.error('Berkas harus berformat PDF (.pdf)');
        e.target.value = '';
        return;
    }

    if (file.size > 10 * 1024 * 1024) {
        toast.error('Ukuran berkas melebihi batas maksimal 10 MB.');
        e.target.value = '';
        return;
    }

    form[`${tierKey}_pdf`] = file;
    form[`remove_${tierKey}_pdf`] = false;
    fileNames.value[tierKey] = file.name;
};

const removeSelectedFile = (tierKey) => {
    form[`${tierKey}_pdf`] = null;
    fileNames.value[tierKey] = '';
    const inputEl = document.getElementById(`file_${tierKey}`);
    if (inputEl) inputEl.value = '';
};

const markRemoveExistingPdf = (tierKey) => {
    form[`remove_${tierKey}_pdf`] = true;
    form[`${tierKey}_pdf`] = null;
    fileNames.value[tierKey] = '';
};

const cancelRemoveExistingPdf = (tierKey) => {
    form[`remove_${tierKey}_pdf`] = false;
};

const submit = () => {
    form.post('/admin/settings/master', {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            fileNames.value.tier_1 = '';
            fileNames.value.tier_2 = '';
            fileNames.value.tier_3 = '';
        },
    });
};
</script>

<template>
    <Head title="Pengaturan Master Copytrade" />
    <AdminLayout
        title="Pengaturan Master & Tier Copytrade"
        subtitle="Konfigurasi tautan broker, tautan profil master trading MQL5, batas modal riil, serta instruksi panduan langkah pendaftaran & berkas brosur PDF per tier."
        :breadcrumbs="[
            { label: 'Pengaturan Sistem' },
            { label: 'Pengaturan Master' }
        ]"
    >
        <div class="max-w-4xl space-y-6">

            <!-- Form Pengaturan -->
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Seksi 1: Tautan Referral Broker & Master MQL5 -->
                <div class="bg-card border-border rounded-2xl border p-5 shadow-xs space-y-4">
                    <div class="flex items-center gap-2 border-b border-border/60 pb-3">
                        <Link2 class="h-4 w-4 text-primary-600 dark:text-primary-400" />
                        <h2 class="text-foreground text-sm font-bold">
                            Tautan Resmi Pendaftaran Broker & Master Akun
                        </h2>
                    </div>

                    <div class="space-y-4">
                        <div class="space-y-1.5">
                            <label class="text-foreground block text-xs font-semibold">
                                Tautan Referral Pendaftaran Akun Broker
                            </label>
                            <input
                                v-model="form.link_referral_broker"
                                type="url"
                                placeholder="Contoh: https://my.hfm.com/?refid=..."
                                class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border px-3 py-2 text-xs font-mono focus:ring-2 focus:outline-none"
                            />
                            <p class="text-muted-foreground text-[11px]">
                                Tautan ini akan ditampilkan pada kartu panduan investor untuk mendaftar akun di bawah broker mitra resmi.
                            </p>
                            <p v-if="form.errors.link_referral_broker" class="text-destructive text-xs">
                                {{ form.errors.link_referral_broker }}
                            </p>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-foreground block text-xs font-semibold">
                                Tautan Akun Master Trading (MQL5 / Sinyal Eksternal)
                            </label>
                            <input
                                v-model="form.link_master_akun"
                                type="url"
                                placeholder="Contoh: https://www.mql5.com/en/signals/..."
                                class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border px-3 py-2 text-xs font-mono focus:ring-2 focus:outline-none"
                            />
                            <p class="text-muted-foreground text-[11px]">
                                Tautan ini memudahkan investor untuk memverifikasi track record dan performa real-time sinyal master.
                            </p>
                            <p v-if="form.errors.link_master_akun" class="text-destructive text-xs">
                                {{ form.errors.link_master_akun }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Seksi 2: Batas Minimal Modal Riil Tier Investasi -->
                <div class="bg-card border-border rounded-2xl border p-5 shadow-xs space-y-4">
                    <div class="flex items-center gap-2 border-b border-border/60 pb-3">
                        <TrendingUp class="h-4 w-4 text-primary-600 dark:text-primary-400" />
                        <h2 class="text-foreground text-sm font-bold">
                            Batas Minimal Modal Riil per Tier (USD)
                        </h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Tier 1 -->
                        <div class="rounded-xl border border-sky-500/30 bg-sky-500/5 p-4 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-sky-800 dark:text-sky-300 text-xs font-bold uppercase">Tier 1 - Cent</span>
                                <span class="text-[10px] text-muted-foreground">Broker HFM</span>
                            </div>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-muted-foreground font-mono text-xs font-bold">$</span>
                                <input
                                    v-model="form.tier_1_min"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    required
                                    class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border pl-7 pr-3 py-2 font-mono text-xs font-bold focus:ring-2 focus:outline-none"
                                />
                            </div>
                            <p class="text-muted-foreground text-[11px]">
                                Batas minimal modal riil awal investor untuk Tier 1.
                            </p>
                            <p v-if="form.errors.tier_1_min" class="text-destructive text-xs">
                                {{ form.errors.tier_1_min }}
                            </p>
                        </div>

                        <!-- Tier 2 -->
                        <div class="rounded-xl border border-indigo-500/30 bg-indigo-500/5 p-4 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-indigo-800 dark:text-indigo-300 text-xs font-bold uppercase">Tier 2 - Standard</span>
                                <span class="text-[10px] text-muted-foreground">Broker XM / HFM</span>
                            </div>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-muted-foreground font-mono text-xs font-bold">$</span>
                                <input
                                    v-model="form.tier_2_min"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    required
                                    class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border pl-7 pr-3 py-2 font-mono text-xs font-bold focus:ring-2 focus:outline-none"
                                />
                            </div>
                            <p class="text-muted-foreground text-[11px]">
                                Batas minimal modal riil awal investor untuk Tier 2.
                            </p>
                            <p v-if="form.errors.tier_2_min" class="text-destructive text-xs">
                                {{ form.errors.tier_2_min }}
                            </p>
                        </div>

                        <!-- Tier 3 -->
                        <div class="rounded-xl border border-amber-500/30 bg-amber-500/5 p-4 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-amber-800 dark:text-amber-300 text-xs font-bold uppercase">Tier 3 - VPS</span>
                                <span class="text-[10px] text-muted-foreground">Dedicated VPS</span>
                            </div>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-muted-foreground font-mono text-xs font-bold">$</span>
                                <input
                                    v-model="form.tier_3_min"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    required
                                    class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border pl-7 pr-3 py-2 font-mono text-xs font-bold focus:ring-2 focus:outline-none"
                                />
                            </div>
                            <p class="text-muted-foreground text-[11px]">
                                Batas minimal modal riil awal investor untuk Tier 3.
                            </p>
                            <p v-if="form.errors.tier_3_min" class="text-destructive text-xs">
                                {{ form.errors.tier_3_min }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Seksi 3: Panduan Langkah Web (CMS) & Berkas Brosur PDF Tiap Tier -->
                <div class="bg-card border-border rounded-2xl border p-5 shadow-xs space-y-5">
                    <div class="flex items-center gap-2 border-b border-border/60 pb-3">
                        <Layers class="h-4 w-4 text-primary-600 dark:text-primary-400" />
                        <div>
                            <h2 class="text-foreground text-sm font-bold">
                                Panduan Interaktif Web & Berkas Brosur PDF Tiap Tier
                            </h2>
                            <p class="text-muted-foreground text-[11px]">
                                Teks instruksi akan langsung dibaca investor di layar ponsel/laptop, sedangkan berkas PDF opsional sebagai brosur yang dapat diunduh.
                            </p>
                        </div>
                    </div>

                    <!-- Panduan Tier 1 -->
                    <div class="rounded-xl border border-sky-500/30 bg-sky-500/5 p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <FileText class="h-4 w-4 text-sky-600 dark:text-sky-400" />
                                <h3 class="text-foreground text-xs font-bold uppercase tracking-wider">
                                    Panduan Tier 1 (Cent - Pemula)
                                </h3>
                            </div>
                            <span class="text-[10px] font-semibold text-sky-700 dark:text-sky-400">Min. ${{ form.tier_1_min }}</span>
                        </div>

                        <!-- Teks Panduan Web -->
                        <div class="space-y-1">
                            <label class="text-foreground block text-xs font-semibold">
                                Instruksi Langkah Pendaftaran Web (Tampil di Portal Investor)
                            </label>
                            <textarea
                                v-model="form.tier_1_desc"
                                rows="3"
                                placeholder="Tuliskan petunjuk langkah 1, 2, 3 untuk membuka akun Cent..."
                                class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border px-3 py-2 text-xs transition-all focus:ring-2 focus:outline-none"
                            ></textarea>
                            <p v-if="form.errors.tier_1_desc" class="text-destructive text-xs">
                                {{ form.errors.tier_1_desc }}
                            </p>
                        </div>

                        <!-- Berkas PDF Panduan/Brosur -->
                        <div class="space-y-1.5 pt-1">
                            <label class="text-foreground block text-xs font-semibold">
                                Berkas Brosur / Panduan PDF Tier 1 (Opsional)
                            </label>

                            <!-- Jika Berkas Sudah Tersimpan Sebelumnya -->
                            <div
                                v-if="pengaturan?.tier_1_pdf_url && !form.remove_tier_1_pdf"
                                class="flex items-center justify-between rounded-lg border border-border bg-card p-2.5 text-xs"
                            >
                                <div class="flex items-center gap-2 min-w-0">
                                    <FileText class="h-4 w-4 text-primary-600 shrink-0" />
                                    <span class="text-foreground font-mono text-xs truncate">
                                        {{ pengaturan.tier_1_pdf_name || 'Panduan-Tier-1-Cent.pdf' }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <a
                                        :href="pengaturan.tier_1_pdf_url"
                                        target="_blank"
                                        class="hover:bg-muted text-foreground flex items-center gap-1 rounded border border-border px-2 py-1 text-[11px] font-medium transition-colors"
                                    >
                                        <ExternalLink class="h-3 w-3" />
                                        <span>Pratinjau</span>
                                    </a>
                                    <button
                                        type="button"
                                        @click="markRemoveExistingPdf('tier_1')"
                                        class="text-destructive hover:bg-destructive/10 rounded border border-destructive/30 px-2 py-1 text-[11px] font-medium transition-colors flex items-center gap-1"
                                    >
                                        <Trash2 class="h-3 w-3" />
                                        <span>Hapus</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Peringatan Jika Ditandai Hapus -->
                            <div
                                v-else-if="form.remove_tier_1_pdf"
                                class="flex items-center justify-between rounded-lg border border-destructive/30 bg-destructive/10 p-2.5 text-xs text-destructive"
                            >
                                <span>Berkas PDF Tier 1 akan dihapus saat disimpan.</span>
                                <button
                                    type="button"
                                    @click="cancelRemoveExistingPdf('tier_1')"
                                    class="underline hover:text-foreground font-semibold"
                                >
                                    Batalkan
                                </button>
                            </div>

                            <!-- Input Berkas Baru -->
                            <div v-if="!form.remove_tier_1_pdf" class="flex items-center gap-3">
                                <label
                                    class="cursor-pointer border-border hover:bg-muted/40 text-foreground inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-medium transition-colors"
                                >
                                    <UploadCloud class="h-3.5 w-3.5" />
                                    <span>{{ pengaturan?.tier_1_pdf_url ? 'Ganti Berkas PDF' : 'Unggah PDF Panduan' }}</span>
                                    <input
                                        id="file_tier_1"
                                        type="file"
                                        accept="application/pdf"
                                        class="hidden"
                                        @change="(e) => handleFileChange(e, 'tier_1')"
                                    />
                                </label>
                                <span v-if="fileNames.tier_1" class="text-xs text-muted-foreground flex items-center gap-1 font-mono">
                                    <CheckCircle2 class="h-3.5 w-3.5 text-emerald-600 shrink-0" />
                                    {{ fileNames.tier_1 }}
                                    <button
                                        type="button"
                                        @click="removeSelectedFile('tier_1')"
                                        class="text-destructive ml-1 hover:underline text-[11px]"
                                    >
                                        Batal
                                    </button>
                                </span>
                            </div>
                            <p v-if="form.errors.tier_1_pdf" class="text-destructive text-xs">
                                {{ form.errors.tier_1_pdf }}
                            </p>
                        </div>
                    </div>

                    <!-- Panduan Tier 2 -->
                    <div class="rounded-xl border border-indigo-500/30 bg-indigo-500/5 p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <FileText class="h-4 w-4 text-indigo-600 dark:text-indigo-400" />
                                <h3 class="text-foreground text-xs font-bold uppercase tracking-wider">
                                    Panduan Tier 2 (Standard - Menengah)
                                </h3>
                            </div>
                            <span class="text-[10px] font-semibold text-indigo-700 dark:text-indigo-400">Min. ${{ form.tier_2_min }}</span>
                        </div>

                        <!-- Teks Panduan Web -->
                        <div class="space-y-1">
                            <label class="text-foreground block text-xs font-semibold">
                                Instruksi Langkah Pendaftaran Web (Tampil di Portal Investor)
                            </label>
                            <textarea
                                v-model="form.tier_2_desc"
                                rows="3"
                                placeholder="Tuliskan petunjuk langkah untuk akun Standard..."
                                class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border px-3 py-2 text-xs transition-all focus:ring-2 focus:outline-none"
                            ></textarea>
                            <p v-if="form.errors.tier_2_desc" class="text-destructive text-xs">
                                {{ form.errors.tier_2_desc }}
                            </p>
                        </div>

                        <!-- Berkas PDF Panduan/Brosur -->
                        <div class="space-y-1.5 pt-1">
                            <label class="text-foreground block text-xs font-semibold">
                                Berkas Brosur / Panduan PDF Tier 2 (Opsional)
                            </label>

                            <!-- Jika Berkas Sudah Tersimpan -->
                            <div
                                v-if="pengaturan?.tier_2_pdf_url && !form.remove_tier_2_pdf"
                                class="flex items-center justify-between rounded-lg border border-border bg-card p-2.5 text-xs"
                            >
                                <div class="flex items-center gap-2 min-w-0">
                                    <FileText class="h-4 w-4 text-primary-600 shrink-0" />
                                    <span class="text-foreground font-mono text-xs truncate">
                                        {{ pengaturan.tier_2_pdf_name || 'Panduan-Tier-2-Standard.pdf' }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <a
                                        :href="pengaturan.tier_2_pdf_url"
                                        target="_blank"
                                        class="hover:bg-muted text-foreground flex items-center gap-1 rounded border border-border px-2 py-1 text-[11px] font-medium transition-colors"
                                    >
                                        <ExternalLink class="h-3 w-3" />
                                        <span>Pratinjau</span>
                                    </a>
                                    <button
                                        type="button"
                                        @click="markRemoveExistingPdf('tier_2')"
                                        class="text-destructive hover:bg-destructive/10 rounded border border-destructive/30 px-2 py-1 text-[11px] font-medium transition-colors flex items-center gap-1"
                                    >
                                        <Trash2 class="h-3 w-3" />
                                        <span>Hapus</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Peringatan Hapus -->
                            <div
                                v-else-if="form.remove_tier_2_pdf"
                                class="flex items-center justify-between rounded-lg border border-destructive/30 bg-destructive/10 p-2.5 text-xs text-destructive"
                            >
                                <span>Berkas PDF Tier 2 akan dihapus saat disimpan.</span>
                                <button
                                    type="button"
                                    @click="cancelRemoveExistingPdf('tier_2')"
                                    class="underline hover:text-foreground font-semibold"
                                >
                                    Batalkan
                                </button>
                            </div>

                            <!-- Input Berkas Baru -->
                            <div v-if="!form.remove_tier_2_pdf" class="flex items-center gap-3">
                                <label
                                    class="cursor-pointer border-border hover:bg-muted/40 text-foreground inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-medium transition-colors"
                                >
                                    <UploadCloud class="h-3.5 w-3.5" />
                                    <span>{{ pengaturan?.tier_2_pdf_url ? 'Ganti Berkas PDF' : 'Unggah PDF Panduan' }}</span>
                                    <input
                                        id="file_tier_2"
                                        type="file"
                                        accept="application/pdf"
                                        class="hidden"
                                        @change="(e) => handleFileChange(e, 'tier_2')"
                                    />
                                </label>
                                <span v-if="fileNames.tier_2" class="text-xs text-muted-foreground flex items-center gap-1 font-mono">
                                    <CheckCircle2 class="h-3.5 w-3.5 text-emerald-600 shrink-0" />
                                    {{ fileNames.tier_2 }}
                                    <button
                                        type="button"
                                        @click="removeSelectedFile('tier_2')"
                                        class="text-destructive ml-1 hover:underline text-[11px]"
                                    >
                                        Batal
                                    </button>
                                </span>
                            </div>
                            <p v-if="form.errors.tier_2_pdf" class="text-destructive text-xs">
                                {{ form.errors.tier_2_pdf }}
                            </p>
                        </div>
                    </div>

                    <!-- Panduan Tier 3 -->
                    <div class="rounded-xl border border-amber-500/30 bg-amber-500/5 p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <FileText class="h-4 w-4 text-amber-600 dark:text-amber-400" />
                                <h3 class="text-foreground text-xs font-bold uppercase tracking-wider">
                                    Panduan Tier 3 (Private VPS - Modal Besar)
                                </h3>
                            </div>
                            <span class="text-[10px] font-semibold text-amber-800 dark:text-amber-300">Min. ${{ form.tier_3_min }}</span>
                        </div>

                        <!-- Teks Panduan Web -->
                        <div class="space-y-1">
                            <label class="text-foreground block text-xs font-semibold">
                                Instruksi Langkah Pendaftaran Web (Tampil di Portal Investor)
                            </label>
                            <textarea
                                v-model="form.tier_3_desc"
                                rows="3"
                                placeholder="Tuliskan petunjuk teknis penyerahan kredensial MT4 & koneksi VPS..."
                                class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border px-3 py-2 text-xs transition-all focus:ring-2 focus:outline-none"
                            ></textarea>
                            <p v-if="form.errors.tier_3_desc" class="text-destructive text-xs">
                                {{ form.errors.tier_3_desc }}
                            </p>
                        </div>

                        <!-- Berkas PDF Panduan/Brosur -->
                        <div class="space-y-1.5 pt-1">
                            <label class="text-foreground block text-xs font-semibold">
                                Berkas Brosur / Panduan PDF Tier 3 (Opsional)
                            </label>

                            <!-- Jika Berkas Sudah Tersimpan -->
                            <div
                                v-if="pengaturan?.tier_3_pdf_url && !form.remove_tier_3_pdf"
                                class="flex items-center justify-between rounded-lg border border-border bg-card p-2.5 text-xs"
                            >
                                <div class="flex items-center gap-2 min-w-0">
                                    <FileText class="h-4 w-4 text-primary-600 shrink-0" />
                                    <span class="text-foreground font-mono text-xs truncate">
                                        {{ pengaturan.tier_3_pdf_name || 'Panduan-Tier-3-Private-VPS.pdf' }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <a
                                        :href="pengaturan.tier_3_pdf_url"
                                        target="_blank"
                                        class="hover:bg-muted text-foreground flex items-center gap-1 rounded border border-border px-2 py-1 text-[11px] font-medium transition-colors"
                                    >
                                        <ExternalLink class="h-3 w-3" />
                                        <span>Pratinjau</span>
                                    </a>
                                    <button
                                        type="button"
                                        @click="markRemoveExistingPdf('tier_3')"
                                        class="text-destructive hover:bg-destructive/10 rounded border border-destructive/30 px-2 py-1 text-[11px] font-medium transition-colors flex items-center gap-1"
                                    >
                                        <Trash2 class="h-3 w-3" />
                                        <span>Hapus</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Peringatan Hapus -->
                            <div
                                v-else-if="form.remove_tier_3_pdf"
                                class="flex items-center justify-between rounded-lg border border-destructive/30 bg-destructive/10 p-2.5 text-xs text-destructive"
                            >
                                <span>Berkas PDF Tier 3 akan dihapus saat disimpan.</span>
                                <button
                                    type="button"
                                    @click="cancelRemoveExistingPdf('tier_3')"
                                    class="underline hover:text-foreground font-semibold"
                                >
                                    Batalkan
                                </button>
                            </div>

                            <!-- Input Berkas Baru -->
                            <div v-if="!form.remove_tier_3_pdf" class="flex items-center gap-3">
                                <label
                                    class="cursor-pointer border-border hover:bg-muted/40 text-foreground inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-medium transition-colors"
                                >
                                    <UploadCloud class="h-3.5 w-3.5" />
                                    <span>{{ pengaturan?.tier_3_pdf_url ? 'Ganti Berkas PDF' : 'Unggah PDF Panduan' }}</span>
                                    <input
                                        id="file_tier_3"
                                        type="file"
                                        accept="application/pdf"
                                        class="hidden"
                                        @change="(e) => handleFileChange(e, 'tier_3')"
                                    />
                                </label>
                                <span v-if="fileNames.tier_3" class="text-xs text-muted-foreground flex items-center gap-1 font-mono">
                                    <CheckCircle2 class="h-3.5 w-3.5 text-emerald-600 shrink-0" />
                                    {{ fileNames.tier_3 }}
                                    <button
                                        type="button"
                                        @click="removeSelectedFile('tier_3')"
                                        class="text-destructive ml-1 hover:underline text-[11px]"
                                    >
                                        Batal
                                    </button>
                                </span>
                            </div>
                            <p v-if="form.errors.tier_3_pdf" class="text-destructive text-xs">
                                {{ form.errors.tier_3_pdf }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex items-center justify-end pt-2">
                    <Button
                        type="submit"
                        :disabled="form.processing"
                        class="bg-primary-600 hover:bg-primary-700 text-white gap-2 px-6"
                    >
                        <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                        <Save v-else class="h-4 w-4" />
                        <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Pengaturan' }}</span>
                    </Button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
