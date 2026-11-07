<script setup>
import { ref, watch, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppModal from '@/Components/Common/AppModal.vue';
import AppButton from '@/Components/Common/AppButton.vue';
import { compressImageFile } from '@/lib/imageCompression';
import { toast } from 'vue-sonner';
import {
    Receipt,
    Copy,
    Check,
    AlertCircle,
    CheckCircle2,
    Clock,
    XCircle,
    Eye,
    UploadCloud,
    Building2,
    Calendar,
    ArrowUpRight,
} from 'lucide-vue-next';

const props = defineProps({
    show: { type: Boolean, default: false },
    tagihanId: { type: Number, default: null },
});

const emit = defineEmits(['close', 'uploaded']);

const loading = ref(false);
const isSubmitting = ref(false);
const detail = ref(null);
const copied = ref(false);

const uploadForm = useForm({
    bukti_transfer: null,
});

const fetchDetail = async (id) => {
    if (!id) return;
    loading.value = true;
    try {
        const response = await fetch(`/member/master-fee/${id}`, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        if (response.ok) {
            detail.value = await response.json();
        } else {
            toast.error('Gagal memuat rincian tagihan.');
        }
    } catch {
        toast.error('Terjadi kesalahan jaringan.');
    } finally {
        loading.value = false;
    }
};

watch(
    () => props.show,
    (isOpen) => {
        if (isOpen && props.tagihanId) {
            uploadForm.reset();
            uploadForm.clearErrors();
            fetchDetail(props.tagihanId);
        } else if (!isOpen) {
            detail.value = null;
        }
    }
);

const tagihan = computed(() => detail.value?.tagihan || null);
const settings = computed(() => detail.value?.settings || null);
const urls = computed(() => detail.value?.urls || {});

const parseDate = (val) => {
    if (!val) return null;
    if (typeof val === 'string' && val.length === 10) {
        return new Date(val + 'T00:00:00');
    }
    return new Date(val);
};

const formatPeriode = (val) => {
    const d = parseDate(val);
    if (!d || isNaN(d.getTime())) return '-';
    return d.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });
};

const formatTanggal = (val) => {
    const d = parseDate(val);
    if (!d || isNaN(d.getTime())) return '-';
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};

const copyRekening = async () => {
    if (!settings.value?.nomor_rekening) return;
    try {
        await navigator.clipboard.writeText(settings.value.nomor_rekening);
        copied.value = true;
        toast.success('Nomor rekening Master berhasil disalin.');
        setTimeout(() => {
            copied.value = false;
        }, 2000);
    } catch {
        toast.error('Gagal menyalin nomor rekening.');
    }
};

const handleProofChange = async (event) => {
    const file = event.target.files?.[0];
    if (!file) {
        uploadForm.bukti_transfer = null;
        return;
    }
    try {
        const res = await compressImageFile(file);
        uploadForm.bukti_transfer = res.file;
    } catch {
        uploadForm.bukti_transfer = file;
    }
};

const submitUpload = () => {
    if (isSubmitting.value || uploadForm.processing) return;
    if (!props.tagihanId || !uploadForm.bukti_transfer) {
        toast.error('Pilih berkas bukti transfer terlebih dahulu.');
        return;
    }

    isSubmitting.value = true;
    uploadForm.post(route('member.master_fee.proof', props.tagihanId), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            emit('close');
        },
        onError: (errors) => {
            if (errors?.bukti) {
                toast.error(errors.bukti);
            }
        },
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
};
</script>

<template>
    <AppModal
        :show="show"
        :title="tagihan ? `Rincian Tagihan ${tagihan.nomor}` : 'Memuat Tagihan...'"
        max-width="lg"
        @close="$emit('close')"
    >
        <div v-if="loading" class="flex flex-col items-center justify-center py-12 text-muted-foreground">
            <div class="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent"></div>
            <p class="mt-3 text-xs font-medium">Memuat data tagihan...</p>
        </div>

        <div v-else-if="tagihan" class="space-y-4">
            <!-- 1. Header Ringkasan Tagihan & Status -->
            <div class="rounded-xl border border-border bg-card p-4 space-y-3">
                <div class="flex items-start justify-between gap-3">
                    <div class="space-y-0.5">
                        <span class="text-xs text-muted-foreground font-medium">
                            Periode: {{ formatPeriode(tagihan.periode) }}
                        </span>
                        <h4 class="text-sm font-bold text-foreground">
                            Akun #{{ tagihan.akun?.nomor_akun_trading }} ({{ tagihan.akun?.broker }})
                        </h4>
                        <p class="text-xs text-muted-foreground flex items-center gap-1">
                            <Calendar class="h-3 w-3" />
                            <span>Jatuh Tempo: {{ formatTanggal(tagihan.jatuh_tempo) }}</span>
                        </p>
                    </div>

                    <div class="text-right">
                        <span
                            :class="[
                                'inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold',
                                tagihan.status === 'lunas'
                                    ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20'
                                    : tagihan.status === 'menunggu_verifikasi'
                                      ? 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/20'
                                      : tagihan.is_terlambat
                                        ? 'bg-rose-500/10 text-rose-700 dark:text-rose-400 border border-rose-500/20 font-bold'
                                        : 'bg-slate-500/10 text-slate-700 dark:text-slate-300 border border-slate-500/20'
                            ]"
                        >
                            <CheckCircle2 v-if="tagihan.status === 'lunas'" class="h-3 w-3" />
                            <Clock v-else-if="tagihan.status === 'menunggu_verifikasi'" class="h-3 w-3" />
                            <XCircle v-else-if="tagihan.status === 'ditolak'" class="h-3 w-3" />
                            <AlertCircle v-else-if="tagihan.is_terlambat" class="h-3 w-3" />
                            <span>{{ tagihan.is_terlambat ? 'Terlambat' : tagihan.status_label }}</span>
                        </span>
                    </div>
                </div>

                <!-- Nominal Fee Highlight -->
                <div class="rounded-lg bg-primary/5 border border-primary/15 p-3 flex items-center justify-between">
                    <div class="space-y-0.5">
                        <span class="text-[11px] font-semibold text-muted-foreground uppercase tracking-wider">
                            Nominal Master Fee ({{ Number(tagihan.fee_persen) }}%)
                        </span>
                        <div class="font-mono text-xl font-black text-primary">
                            ${{ Number(tagihan.nominal_fee).toFixed(2) }}
                        </div>
                    </div>
                    <div class="text-right text-[11px] text-muted-foreground">
                        Profit Bersih: <strong>${{ Number(tagihan.profit_bersih).toFixed(2) }}</strong>
                    </div>
                </div>
            </div>

            <!-- 2. Alasan Penolakan (Jika Ada) -->
            <div
                v-if="tagihan.status === 'ditolak' && tagihan.catatan_admin"
                class="rounded-xl border border-rose-500/30 bg-rose-500/10 p-3.5 text-xs text-rose-900 dark:text-rose-200 space-y-1"
            >
                <div class="flex items-center gap-1.5 font-bold">
                    <XCircle class="h-4 w-4 text-rose-600 dark:text-rose-400" />
                    <span>Pembayaran Sebelumnya Ditolak</span>
                </div>
                <p class="text-rose-800/90 dark:text-rose-300/90 leading-relaxed pl-5.5">
                    {{ tagihan.catatan_admin }}
                </p>
                <p class="text-[11px] text-muted-foreground pl-5.5 pt-1">
                    Silakan lakukan transfer ulang atau unggah bukti transfer yang jelas dan valid di bawah ini.
                </p>
            </div>

            <!-- 3. Rekening Tujuan Transfer Master (Manual) -->
            <div class="rounded-xl border border-border bg-muted/30 p-3.5 space-y-2.5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-foreground flex items-center gap-1.5">
                        <Building2 class="h-3.5 w-3.5 text-primary" />
                        Rekening Tujuan Transfer (Master)
                    </span>
                    <span class="text-[11px] text-muted-foreground">Mata Uang: USD / Setara</span>
                </div>

                <div class="bg-card border border-border rounded-lg p-2.5 flex items-center justify-between">
                    <div class="space-y-0.5 min-w-0">
                        <div class="text-xs font-bold text-foreground">
                            {{ settings?.nama_bank || 'BCA' }} — a.n {{ settings?.atas_nama || 'Master YZD Trading' }}
                        </div>
                        <div class="font-mono text-sm font-black tracking-wide text-primary">
                            {{ settings?.nomor_rekening || '1234567890' }}
                        </div>
                    </div>
                    <AppButton
                        type="custom"
                        size="xs"
                        variant="outline"
                        :icon="copied ? Check : Copy"
                        :label="copied ? 'Tersalin' : 'Salin'"
                        @click="copyRekening"
                    />
                </div>
            </div>

            <!-- 4. Rincian Perhitungan Profit Rekap Saldo -->
            <div class="rounded-xl border border-border p-3.5 text-xs space-y-2">
                <span class="font-bold text-foreground block">Rincian Perhitungan Profit Rekap</span>
                <div class="grid grid-cols-2 gap-2 text-muted-foreground text-[11px]">
                    <div>Saldo Akhir: <strong class="text-foreground">${{ Number(tagihan.riwayat_balance?.balance || 0).toFixed(2) }}</strong></div>
                    <div>Penarikan: <strong class="text-foreground">${{ Number(tagihan.riwayat_balance?.penarikan || 0).toFixed(2) }}</strong></div>
                    <div>Setoran Tambahan: <strong class="text-foreground">${{ Number(tagihan.riwayat_balance?.setoran_tambahan || 0).toFixed(2) }}</strong></div>
                    <div>Profit Bersih: <strong class="text-foreground font-mono font-bold">${{ Number(tagihan.profit_bersih).toFixed(2) }}</strong></div>
                </div>

                <div v-if="urls.bukti_saldo" class="pt-2 border-t border-border flex items-center justify-between text-[11px]">
                    <span class="text-muted-foreground">Tangkapan Layar Saldo MT4</span>
                    <a
                        :href="urls.bukti_saldo"
                        target="_blank"
                        class="text-primary hover:underline font-semibold inline-flex items-center gap-1"
                    >
                        <Eye class="h-3 w-3" />
                        <span>Lihat Bukti Saldo</span>
                    </a>
                </div>
            </div>

            <!-- 5. Form Unggah Bukti Transfer / Pratinjau Bukti -->
            <div class="rounded-xl border border-border p-3.5 space-y-3">
                <span class="text-xs font-bold text-foreground block">
                    Bukti Pembayaran Transfer
                </span>

                <!-- Jika sudah ada bukti transfer dan status menunggu_verifikasi / lunas -->
                <div v-if="urls.bukti_transfer && tagihan.status !== 'ditolak'" class="space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-muted-foreground">Berkas Bukti Transfer</span>
                        <a
                            :href="urls.bukti_transfer"
                            target="_blank"
                            class="text-primary hover:underline font-semibold inline-flex items-center gap-1"
                        >
                            <Eye class="h-3.5 w-3.5" />
                            <span>Buka Gambar Bukti</span>
                        </a>
                    </div>
                    <div v-if="tagihan.status === 'menunggu_verifikasi'" class="p-2.5 rounded-lg bg-amber-500/10 border border-amber-500/20 text-xs text-amber-800 dark:text-amber-300 flex items-start gap-2">
                        <Clock class="h-4 w-4 shrink-0 mt-0.5" />
                        <span>Bukti transfer telah diterima dan sedang menunggu verifikasi oleh Administrator.</span>
                    </div>
                    <div v-else-if="tagihan.status === 'lunas'" class="p-2.5 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-xs text-emerald-800 dark:text-emerald-300 flex items-start gap-2">
                        <CheckCircle2 class="h-4 w-4 shrink-0 mt-0.5" />
                        <span>Pembayaran telah diverifikasi Lunas oleh Administrator. Terima kasih.</span>
                    </div>
                </div>

                <!-- Form Unggah jika status belum_dibayar atau ditolak -->
                <form
                    v-if="['belum_dibayar', 'ditolak'].includes(tagihan.status)"
                    @submit.prevent="submitUpload"
                    class="space-y-3"
                >
                    <div class="space-y-1">
                        <label class="text-[11px] font-semibold text-muted-foreground block">
                            Pilih Gambar Bukti Transfer (JPG, PNG, WebP)
                        </label>
                        <input
                            type="file"
                            accept="image/jpeg,image/png,image/jpg,image/webp"
                            @change="handleProofChange"
                            class="bg-background border-input file:bg-primary file:text-primary-foreground focus:ring-primary w-full rounded-lg border file:mr-3 file:rounded-md file:border-0 file:px-3 file:py-1.5 file:text-xs file:font-semibold text-xs focus:ring-2 focus:outline-none cursor-pointer"
                        />
                        <p v-if="uploadForm.errors.bukti_transfer" class="text-xs text-destructive">
                            {{ uploadForm.errors.bukti_transfer }}
                        </p>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <AppButton
                            type="cancel"
                            size="sm"
                            @click="$emit('close')"
                            :disabled="uploadForm.processing || isSubmitting"
                        />
                        <AppButton
                            type="submit"
                            size="sm"
                            :label="tagihan.status === 'ditolak' ? 'Unggah Ulang Bukti' : 'Kirim Bukti Pembayaran'"
                            :loading="uploadForm.processing || isSubmitting"
                            :disabled="uploadForm.processing || isSubmitting"
                            :icon="UploadCloud"
                        />
                    </div>
                </form>
            </div>
        </div>
    </AppModal>
</template>
