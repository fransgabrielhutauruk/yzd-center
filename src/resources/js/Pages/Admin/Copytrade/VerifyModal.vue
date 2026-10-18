<script setup>
import { computed, watch } from 'vue';
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
import { useCopytradeDetail } from '@/lib/useCopytradeDetail';

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

const shouldShowPerjanjian = computed(() => {
    if (!props.copytrade) return false;
    if (typeof props.copytrade.show_perjanjian === 'boolean') {
        return props.copytrade.show_perjanjian;
    }
    return !(props.copytrade.pengajuan_ke >= 2 && props.copytrade.has_approved_account);
});

const {
    detail,
    loading: detailLoading,
    error: detailError,
    load: loadDetail,
    reset: resetDetail,
} = useCopytradeDetail();

// Kredensial MT4 tier 3 dimuat on-demand (tidak lagi ikut di data daftar).
watch(
    () => [props.show, props.copytrade?.id, props.copytrade?.tier],
    ([show, id, tier]) => {
        if (show && id && tier === 'tier_3') {
            loadDetail(id);
        } else {
            resetDetail();
        }
    },
    { immediate: true }
);

const form = useForm({
    status: '',
    catatan_admin: '',
});

watch(
    () => props.copytrade,
    (newVal) => {
        if (newVal) {
            form.status =
                newVal.status === 'active'
                    ? 'active'
                    : newVal.status === 'rejected'
                      ? 'rejected'
                      : '';
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

    if (form.status === 'active') {
        form.catatan_admin = '';
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
        <div v-if="copytrade" class="max-h-[75vh] space-y-5 overflow-y-auto p-1">
            <!-- Ringkasan Identitas Investor -->
            <div class="bg-card border-border space-y-3 rounded-xl border p-4">
                <div
                    class="border-border/60 flex flex-col justify-between gap-3 border-b pb-3 sm:flex-row sm:items-center"
                >
                    <div class="space-y-0.5">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-foreground text-sm font-bold">{{
                                copytrade.user.name
                            }}</span>
                            <span class="text-muted-foreground text-xs font-normal"
                                >@{{ copytrade.user.username }}</span
                            >
                        </div>
                    </div>
                    <div class="flex gap-2 text-xs">
                        <span class="text-muted-foreground">Diajukan: </span>
                        <span class="text-foreground">{{ copytrade.created_at }}</span>
                        <span
                            v-if="copytrade.pengajuan_ke"
                            class="text-muted-foreground inline-flex items-center rounded-md border border-slate-200 bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold dark:border-slate-700 dark:bg-slate-800"
                        >
                            Pengajuan ke-{{ copytrade.pengajuan_ke }}
                        </span>
                    </div>
                </div>

                <!-- Grid Data Akun & Withdrawal -->
                <div class="grid grid-cols-1 gap-4 text-xs sm:grid-cols-2">
                    <!-- Akun MT4 -->
                    <div class="space-y-1.5">
                        <span
                            class="text-muted-foreground block text-[11px] font-semibold tracking-wider uppercase"
                        >
                            Akun Trading MT4
                        </span>
                        <div class="space-y-1">
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Nomor Akun:</span>
                                <span class="text-foreground font-semibold">{{
                                    copytrade.nomor_akun_trading
                                }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Tier:</span>
                                <span class="text-foreground font-semibold">{{
                                    copytrade.tier_label
                                }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Broker & Tipe:</span>
                                <span class="text-foreground font-medium"
                                    >{{ copytrade.broker }} ({{ copytrade.tipe_akun }})</span
                                >
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Modal:</span>
                                <span
                                    class="text-foreground font-semibold"
                                >
                                    {{ copytrade.modal_riil_formatted }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Mulai Trading:</span>
                                <span class="text-foreground font-semibold">{{ copytrade.tanggal_mulai }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Rekening Bank -->
                    <div class="space-y-1.5">
                        <span
                            class="text-muted-foreground block text-[11px] font-semibold tracking-wider uppercase"
                        >
                            Rekening Penarikan Dana (Withdrawal)
                        </span>
                        <div class="space-y-1">
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Bank:</span>
                                <span class="text-foreground font-semibold">{{
                                    copytrade.nama_bank
                                }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">No. Rekening:</span>
                                <span class="text-foreground font-semibold">{{
                                    copytrade.nomor_rekening
                                }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Atas Nama:</span>
                                <span class="text-foreground font-medium">{{
                                    copytrade.atas_nama_rekening
                                }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kredensial Khusus Tier 3 jika ada -->
            <div
                v-if="copytrade.tier === 'tier_3'"
                class="space-y-3 rounded-xl border border-amber-500/30 bg-amber-500/10 p-4 text-xs"
            >
                <div class="flex items-center gap-2 border-b border-amber-500/20 pb-2">
                    <Server class="h-4 w-4 text-amber-700 dark:text-amber-400" />
                    <h4
                        class="text-xs font-bold tracking-wider text-amber-800 uppercase dark:text-amber-300"
                    >
                        Kredensial Private VPS & Password MT4 (Tier 3)
                    </h4>
                </div>
                <div class="grid grid-cols-1 gap-3 text-xs sm:grid-cols-3">
                    <div>
                        <span class="text-muted-foreground block text-[11px]">Server Broker:</span>
                        <span class="text-foreground font-mono font-bold">{{
                            copytrade.server_broker || '-'
                        }}</span>
                    </div>
                    <div>
                        <span class="text-muted-foreground block text-[11px]"
                            >Master Password MT4:</span
                        >
                        <span v-if="detailLoading" class="text-muted-foreground">Memuat...</span>
                        <span v-else-if="detailError" class="text-destructive">{{
                            detailError
                        }}</span>
                        <span v-else class="text-foreground font-mono font-bold">{{
                            detail?.credentials?.master_password || '-'
                        }}</span>
                    </div>
                    <div>
                        <span class="text-muted-foreground block text-[11px]"
                            >Investor Password MT4:</span
                        >
                        <span v-if="detailLoading" class="text-muted-foreground">Memuat...</span>
                        <span v-else-if="detailError" class="text-destructive">{{
                            detailError
                        }}</span>
                        <span v-else class="text-foreground font-mono font-bold">{{
                            detail?.credentials?.investor_password || '-'
                        }}</span>
                    </div>
                </div>
            </div>

            <!-- Surat Perjanjian Kemitraan Section (Hanya untuk pengajuan pertama atau jika belum ada pengajuan yang disetujui) -->
            <div
                v-if="shouldShowPerjanjian"
                class="border-border bg-card space-y-3 rounded-xl border p-4"
            >
                <h4
                    class="text-foreground flex items-center gap-2 text-xs font-bold tracking-wider uppercase"
                >
                    <FileText class="text-primary-600 dark:text-primary-400 h-4 w-4" />
                    <span>Berkas Surat Perjanjian</span>
                </h4>

                <a
                    v-if="copytrade.has_surat_perjanjian && copytrade.surat_perjanjian_url"
                    :href="copytrade.surat_perjanjian_url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="bg-background border-border hover:bg-muted text-foreground inline-flex min-h-[36px] items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-semibold shadow-xs transition-colors"
                >
                    <span>Buka Berkas</span>
                    <ExternalLink class="h-3.5 w-3.5" />
                </a>

                <div
                    v-else
                    class="flex items-center gap-2.5 rounded-lg border border-sky-500/30 bg-sky-500/10 p-3 text-xs text-sky-800 dark:text-sky-300"
                >
                    <CheckCircle2 class="h-4 w-4 shrink-0" />
                    <span
                        >Bukan pendaftaran pertama. Perjanjian kemitraan investor ini telah terikat
                        pada akun sebelumnya.</span
                    >
                </div>
            </div>

            <!-- Form Keputusan Verifikasi -->
            <form @submit.prevent="submit" class="border-border space-y-4 border-t pt-4">
                <div class="space-y-1.5">
                    <label class="text-foreground block text-xs font-bold">
                        Keputusan Verifikasi Akun Copytrade <span class="text-destructive">*</span>
                    </label>

                    <div
                        class="grid grid-cols-1 gap-3 sm:grid-cols-2"
                        role="radiogroup"
                        aria-label="Keputusan Verifikasi Akun"
                    >
                        <button
                            type="button"
                            role="radio"
                            :aria-checked="form.status === 'active'"
                            @click="form.status = 'active'"
                            :class="[
                                'focus:ring-primary-500/30 flex items-center gap-2.5 rounded-xl border p-3 text-left text-xs font-semibold transition-colors focus:ring-2 focus:outline-none',
                                form.status === 'active'
                                    ? 'border-emerald-500 bg-emerald-500/10 text-emerald-700 ring-1 ring-emerald-500/20 dark:text-emerald-400'
                                    : 'border-border bg-card hover:bg-muted/40 text-foreground',
                            ]"
                        >
                            <CheckCircle2
                                class="h-4 w-4 shrink-0 text-emerald-600 dark:text-emerald-400"
                            />
                            <div>
                                <span class="block">Setujui (Aktifkan)</span>
                            </div>
                        </button>

                        <button
                            type="button"
                            role="radio"
                            :aria-checked="form.status === 'rejected'"
                            @click="form.status = 'rejected'"
                            :class="[
                                'focus:ring-primary-500/30 flex items-center gap-2.5 rounded-xl border p-3 text-left text-xs font-semibold transition-colors focus:ring-2 focus:outline-none',
                                form.status === 'rejected'
                                    ? 'border-destructive bg-destructive/10 text-destructive ring-destructive/20 ring-1'
                                    : 'border-border bg-card hover:bg-muted/40 text-foreground',
                            ]"
                        >
                            <XCircle class="text-destructive h-4 w-4 shrink-0" />
                            <div>
                                <span class="block">Tolak</span>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Catatan Admin Textarea (Hanya jika Ditolak) -->
                <div v-if="form.status === 'rejected'" class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label
                            for="catatan_admin"
                            class="text-foreground block text-xs font-semibold"
                        >
                            Catatan / Instruksi Verifikator
                            <span class="text-destructive">*</span>
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
                        class="bg-primary-600 hover:bg-primary-700 gap-2 text-white"
                    >
                        <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                        <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Keputusan' }}</span>
                    </Button>
                </div>
            </form>
        </div>
    </AppModal>
</template>
