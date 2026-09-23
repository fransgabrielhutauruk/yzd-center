<script setup>
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import {
    Loader2,
    CheckCircle2,
    XCircle,
    FileText,
    ExternalLink,
    Server,
    Key,
    Shield,
    AlertTriangle,
    Building2,
    CreditCard,
} from 'lucide-vue-next';
import AppModal from '@/Components/Common/AppModal.vue';
import { Button } from '@/Components/ui/button';
import { toast } from 'vue-sonner';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    copytrade: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close']);

const form = useForm({
    status: '',
    catatan_admin: '',
});

watch(
    () => props.copytrade,
    (newVal) => {
        if (newVal) {
            form.status = newVal.status === 'active' ? 'active' : (newVal.status === 'rejected' ? 'rejected' : '');
            form.catatan_admin = newVal.catatan_admin || '';
            form.clearErrors();
        }
    },
    { immediate: true }
);

const close = () => {
    form.reset();
    form.clearErrors();
    emit('close');
};

const submit = () => {
    if (!props.copytrade) return;

    if (!form.status) {
        toast.error('Silakan tentukan keputusan verifikasi (Terima atau Tolak).');
        return;
    }

    if (form.status === 'rejected' && !form.catatan_admin.trim()) {
        toast.error('Harap isi catatan instruksi penolakan agar investor dapat memperbaikinya.');
        return;
    }

    form.post(`/admin/copytrade/${props.copytrade.id}/verify`, {
        preserveScroll: true,
        onSuccess: () => {
            close();
        },
    });
};
</script>

<template>
    <AppModal
        :show="show"
        title="Peninjauan & Verifikasi Akun Copytrade"
        max-width="3xl"
        @close="close"
    >
        <div v-if="copytrade" class="space-y-5 p-1 max-h-[75vh] overflow-y-auto">
            <!-- Ringkasan Identitas Investor -->
            <div class="bg-card border-border rounded-xl border p-4 space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-border/60 pb-3">
                    <div class="space-y-0.5">
                        <div class="flex items-center gap-2">
                            <span class="text-foreground text-sm font-bold">{{ copytrade.user.name }}</span>
                            <span class="text-muted-foreground text-xs font-normal">@{{ copytrade.user.username }}</span>
                        </div>
                        <p class="text-muted-foreground text-xs">
                            {{ copytrade.user.email }} &bull; {{ copytrade.user.phone }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2 text-xs">
                        <span class="text-muted-foreground text-[11px]">Terdaftar Diajukan:</span>
                        <span class="text-foreground font-semibold">{{ copytrade.created_at }}</span>
                    </div>
                </div>

                <!-- Grid Data Akun & Withdrawal -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <!-- Akun MT4 -->
                    <div class="space-y-1.5">
                        <span class="text-muted-foreground block text-[11px] font-semibold uppercase tracking-wider">
                            Akun Trading MT4
                        </span>
                        <div class="space-y-1">
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Nomor Akun:</span>
                                <span class="text-foreground font-mono font-bold">{{ copytrade.nomor_akun_trading }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Tier:</span>
                                <span class="text-foreground font-semibold">{{ copytrade.tier_label }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Broker & Tipe:</span>
                                <span class="text-foreground font-medium">{{ copytrade.broker }} ({{ copytrade.tipe_akun }})</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Modal Riil:</span>
                                <span class="text-foreground font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                    {{ copytrade.modal_riil_formatted }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Mulai Trading:</span>
                                <span class="text-foreground">{{ copytrade.tanggal_mulai }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Rekening Bank -->
                    <div class="space-y-1.5">
                        <span class="text-muted-foreground block text-[11px] font-semibold uppercase tracking-wider">
                            Rekening Penarikan Dana (Withdrawal)
                        </span>
                        <div class="space-y-1">
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Bank:</span>
                                <span class="text-foreground font-bold">{{ copytrade.nama_bank }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">No. Rekening:</span>
                                <span class="text-foreground font-mono font-semibold">{{ copytrade.nomor_rekening }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Atas Nama:</span>
                                <span class="text-foreground font-medium">{{ copytrade.atas_nama_rekening }}</span>
                            </div>
                            <div v-if="copytrade.copy_started_at" class="flex justify-between">
                                <span class="text-muted-foreground">Mulai Aktif Copy:</span>
                                <span class="text-emerald-600 dark:text-emerald-400 font-medium">{{ copytrade.copy_started_at }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kredensial Khusus Tier 3 jika ada -->
            <div v-if="copytrade.tier === 'tier_3'" class="rounded-xl border border-amber-500/30 bg-amber-500/10 p-4 space-y-3 text-xs">
                <div class="flex items-center gap-2 border-b border-amber-500/20 pb-2">
                    <Server class="h-4 w-4 text-amber-700 dark:text-amber-400" />
                    <h4 class="text-amber-800 dark:text-amber-300 font-bold uppercase tracking-wider text-xs">
                        Kredensial Private VPS & Password MT4 (Tier 3)
                    </h4>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                    <div>
                        <span class="text-muted-foreground text-[11px] block">Server Broker:</span>
                        <span class="text-foreground font-mono font-bold">{{ copytrade.server_broker || '-' }}</span>
                    </div>
                    <div>
                        <span class="text-muted-foreground text-[11px] block">Tautan Sinyal MQL5:</span>
                        <a
                            v-if="copytrade.url_mql5"
                            :href="copytrade.url_mql5"
                            target="_blank"
                            class="text-primary-600 dark:text-primary-400 hover:underline font-mono text-xs inline-flex items-center gap-1"
                        >
                            <span class="truncate">{{ copytrade.url_mql5 }}</span>
                            <ExternalLink class="h-3 w-3 shrink-0" />
                        </a>
                        <span v-else class="text-muted-foreground">-</span>
                    </div>
                    <div>
                        <span class="text-muted-foreground text-[11px] block">Master Password MT4:</span>
                        <span class="text-foreground font-mono font-bold">{{ copytrade.master_password || '-' }}</span>
                    </div>
                    <div>
                        <span class="text-muted-foreground text-[11px] block">Investor Password MT4:</span>
                        <span class="text-foreground font-mono font-bold">{{ copytrade.investor_password || '-' }}</span>
                    </div>
                </div>
            </div>

            <!-- Surat Perjanjian Kemitraan Section -->
            <div class="rounded-xl border border-border bg-card p-4 space-y-3">
                <h4 class="text-foreground font-bold text-xs uppercase tracking-wider flex items-center gap-2">
                    <FileText class="h-4 w-4 text-primary-600 dark:text-primary-400" />
                    <span>Berkas Surat Perjanjian Kemitraan</span>
                </h4>

                <div v-if="copytrade.has_surat_perjanjian && copytrade.surat_perjanjian_url" class="flex items-center justify-between rounded-lg border border-border bg-muted/30 p-3">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary-500/10 text-primary-600 dark:text-primary-400">
                            <FileText class="h-5 w-5" />
                        </div>
                        <div class="space-y-0.5">
                            <p class="text-foreground text-xs font-semibold">Surat_Perjanjian_Kemitraan.pdf</p>
                            <p class="text-muted-foreground text-[11px]">Berkas legalitas bermaterai yang diunggah investor</p>
                        </div>
                    </div>
                    <a
                        :href="copytrade.surat_perjanjian_url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="bg-background border-border hover:bg-muted text-foreground inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-semibold shadow-xs transition-colors"
                    >
                        <span>Buka Berkas</span>
                        <ExternalLink class="h-3.5 w-3.5" />
                    </a>
                </div>

                <div v-else class="flex items-center gap-2.5 rounded-lg border border-sky-500/30 bg-sky-500/10 p-3 text-xs text-sky-800 dark:text-sky-300">
                    <CheckCircle2 class="h-4 w-4 shrink-0" />
                    <span>Bukan pendaftaran pertama. Perjanjian kemitraan investor ini telah terikat pada akun sebelumnya.</span>
                </div>
            </div>

            <!-- Form Keputusan Verifikasi -->
            <form @submit.prevent="submit" class="border-t border-border pt-4 space-y-4">
                <div class="space-y-1.5">
                    <label class="text-foreground block text-xs font-bold">
                        Keputusan Verifikasi Akun Copytrade <span class="text-destructive">*</span>
                    </label>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" role="radiogroup" aria-label="Keputusan Verifikasi Akun">
                        <button
                            type="button"
                            role="radio"
                            :aria-checked="form.status === 'active'"
                            @click="form.status = 'active'"
                            :class="[
                                'flex items-center gap-2.5 rounded-xl border p-3 text-xs font-semibold transition-colors text-left focus:outline-none focus:ring-2 focus:ring-primary-500/30',
                                form.status === 'active'
                                    ? 'border-emerald-500 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 ring-1 ring-emerald-500/20'
                                    : 'border-border bg-card hover:bg-muted/40 text-foreground',
                            ]"
                        >
                            <CheckCircle2 class="h-4 w-4 shrink-0 text-emerald-600 dark:text-emerald-400" />
                            <div>
                                <span class="block">Setujui (Aktifkan Akun)</span>
                                <span class="text-[10px] text-muted-foreground font-normal">Mencatat tanggal mulai aktif copytrade</span>
                            </div>
                        </button>

                        <button
                            type="button"
                            role="radio"
                            :aria-checked="form.status === 'rejected'"
                            @click="form.status = 'rejected'"
                            :class="[
                                'flex items-center gap-2.5 rounded-xl border p-3 text-xs font-semibold transition-colors text-left focus:outline-none focus:ring-2 focus:ring-primary-500/30',
                                form.status === 'rejected'
                                    ? 'border-destructive bg-destructive/10 text-destructive ring-1 ring-destructive/20'
                                    : 'border-border bg-card hover:bg-muted/40 text-foreground',
                            ]"
                        >
                            <XCircle class="h-4 w-4 shrink-0 text-destructive" />
                            <div>
                                <span class="block">Tolak Permohonan</span>
                                <span class="text-[10px] text-muted-foreground font-normal">Wajib menyertakan instruksi penolakan</span>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Info Banner jika Disetujui -->
                <div v-if="form.status === 'active'" class="flex items-center gap-2 rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-3 text-xs text-emerald-800 dark:text-emerald-300">
                    <CheckCircle2 class="h-4 w-4 shrink-0" />
                    <span>
                        Menyetujui akun ini akan langsung mengaktifkan status copytrade dan mencatat waktu sekarang sebagai titik tolak <strong>copy_started_at</strong> untuk validasi klaim afiliasi.
                    </span>
                </div>

                <!-- Catatan Admin Textarea -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label for="catatan_admin" class="text-foreground block text-xs font-semibold">
                            Catatan / Instruksi Verifikator
                            <span v-if="form.status === 'rejected'" class="text-destructive">*</span>
                            <span v-else class="text-muted-foreground font-normal">(opsional)</span>
                        </label>
                        <span class="text-muted-foreground text-[10px]">
                            Maks. 20.000 karakter
                        </span>
                    </div>
                    <textarea
                        id="catatan_admin"
                        v-model="form.catatan_admin"
                        rows="3"
                        placeholder="Contoh: Akun broker belum terhubung dengan master, silakan ikuti panduan follow..."
                        class="bg-background border-input focus:ring-primary-500 w-full resize-none rounded-lg border px-3 py-2 text-xs transition-all focus:ring-2 focus:outline-none"
                    ></textarea>
                    <p v-if="form.errors.catatan_admin" class="text-destructive text-xs">
                        {{ form.errors.catatan_admin }}
                    </p>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-end gap-3 pt-2">
                    <Button type="button" variant="outline" size="sm" @click="close">
                        Batal
                    </Button>
                    <Button
                        type="submit"
                        size="sm"
                        :disabled="form.processing || !form.status"
                        class="bg-primary-600 hover:bg-primary-700 text-white gap-2"
                    >
                        <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                        <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Keputusan' }}</span>
                    </Button>
                </div>
            </form>
        </div>
    </AppModal>
</template>
