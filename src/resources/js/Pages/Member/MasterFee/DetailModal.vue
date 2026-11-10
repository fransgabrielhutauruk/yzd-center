<script setup>
import { ref, watch, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppModal from '@/Components/Common/AppModal.vue';
import AppButton from '@/Components/Common/AppButton.vue';
import { compressImageFile } from '@/lib/imageCompression';
import { toast } from 'vue-sonner';
import {
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
    Edit3,
    ExternalLink,
    AlertTriangle,
    CreditCard,
} from 'lucide-vue-next';

const getStatusBadgeClass = (status) => {
    switch (status) {
        case 'Lunas':
            return 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20';
        case 'Tanpa Fee':
            return 'bg-slate-500/10 text-slate-700 dark:text-slate-300 border border-slate-500/20';
        case 'Menunggu Verifikasi':
            return 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/20';
        case 'Menunggu Pembayaran':
            return 'bg-blue-500/10 text-blue-700 dark:text-blue-400 border border-blue-500/20';
        case 'Perlu Koreksi':
            return 'bg-purple-500/10 text-purple-700 dark:text-purple-400 border border-purple-500/20';
        case 'Bukti Ditolak':
            return 'bg-rose-500/10 text-rose-700 dark:text-rose-400 border border-rose-500/20';
        default:
            return 'bg-muted text-muted-foreground border border-border';
    }
};

const props = defineProps({
    show: { type: Boolean, default: false },
    laporanId: { type: Number, default: null },
    tagihanId: { type: Number, default: null },
});

const emit = defineEmits(['close', 'uploaded', 'edit']);

const activeId = computed(() => props.laporanId || props.tagihanId || null);

const loading = ref(false);
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
            toast.error('Gagal memuat rincian laporan profit.');
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
        if (isOpen && activeId.value) {
            uploadForm.reset();
            uploadForm.clearErrors();
            fetchDetail(activeId.value);
        } else if (!isOpen) {
            detail.value = null;
        }
    }
);

const laporan = computed(() => detail.value?.laporan || null);
const settings = computed(() => detail.value?.settings || null);
const urls = computed(() => detail.value?.urls || {});

const parseDate = (val) => {
    if (!val) return null;
    if (typeof val === 'string' && val.length === 10) {
        return new Date(val + 'T00:00:00');
    }
    return new Date(val);
};

const formatTanggal = (val) => {
    const d = parseDate(val);
    if (!d || isNaN(d.getTime())) return '-';
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};

const formatRupiah = (val) => {
    if (val === null || val === undefined) return 'Rp 0';
    return 'Rp ' + Number(val).toLocaleString('id-ID');
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
    if (uploadForm.processing) return;
    if (!activeId.value || !uploadForm.bukti_transfer) {
        toast.error('Pilih berkas bukti transfer terlebih dahulu.');
        return;
    }

    uploadForm.post(route('member.master_fee.proof', activeId.value), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            emit('close');
            emit('uploaded');
        },
        onError: (errors) => {
            if (errors?.bukti_transfer) {
                toast.error(errors.bukti_transfer);
            }
        },
    });
};

const triggerEdit = () => {
    if (!laporan.value) return;
    emit('edit', laporan.value);
    emit('close');
};
</script>

<template>
    <AppModal
        :show="show"
        :title="laporan ? `Rincian Laporan ${laporan.nomor}` : 'Memuat Laporan...'"
        max-width="lg"
        @close="$emit('close')"
    >
        <div v-if="loading" class="flex flex-col items-center justify-center py-12 text-muted-foreground">
            <div class="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent"></div>
            <p class="mt-3 text-xs font-medium">Memuat data laporan...</p>
        </div>

        <div v-else-if="laporan" class="space-y-4">
            <!-- 1. Header Ringkasan & Status -->
            <div class="rounded-xl border border-border bg-card p-4 space-y-3">
                <div class="flex items-start justify-between gap-3">
                    <div class="space-y-0.5">
                        <span class="text-xs text-muted-foreground font-medium">
                            Periode ke-{{ laporan.periode_ke }} ({{ formatTanggal(laporan.periode_awal) }} – {{ formatTanggal(laporan.periode_akhir) }})
                        </span>
                        <h4 class="text-sm font-bold text-foreground">
                            Akun #{{ laporan.akun?.nomor_akun_trading }} ({{ laporan.akun?.broker }})
                        </h4>
                        <p class="text-xs text-muted-foreground flex items-center gap-1">
                            <Calendar class="h-3 w-3" />
                            <span>Jatuh Tempo: {{ formatTanggal(laporan.jatuh_tempo) }}</span>
                        </p>
                    </div>

                    <div class="text-right space-y-1">
                        <span
                            :class="[
                                'inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold',
                                getStatusBadgeClass(laporan.status_tampil)
                            ]"
                        >
                            <CheckCircle2 v-if="laporan.status_tampil === 'Lunas'" class="h-3 w-3" />
                            <Check v-else-if="laporan.status_tampil === 'Tanpa Fee'" class="h-3 w-3" />
                            <Clock v-else-if="laporan.status_tampil === 'Menunggu Verifikasi'" class="h-3 w-3" />
                            <CreditCard v-else-if="laporan.status_tampil === 'Menunggu Pembayaran'" class="h-3 w-3" />
                            <AlertTriangle v-else-if="laporan.status_tampil === 'Perlu Koreksi'" class="h-3 w-3" />
                            <XCircle v-else-if="laporan.status_tampil === 'Bukti Ditolak'" class="h-3 w-3" />
                            <span>{{ laporan.status_tampil }}</span>
                        </span>
                        <div v-if="laporan.status_catatan" class="text-[11px] text-muted-foreground">
                            {{ laporan.status_catatan }}
                        </div>
                    </div>
                </div>

                <!-- Nominal Fee Highlight -->
                <div class="rounded-lg bg-primary/5 border border-primary/15 p-3 flex items-center justify-between">
                    <div class="space-y-0.5">
                        <span class="text-[11px] font-semibold text-muted-foreground uppercase tracking-wider">
                            Master Fee ({{ Number(laporan.fee_persen) }}%)
                        </span>
                        <div class="font-mono text-xl font-black text-primary">
                            ${{ Number(laporan.fee_usd).toFixed(2) }}
                        </div>
                        <div v-if="laporan.fee_idr > 0" class="text-xs font-semibold text-muted-foreground">
                            ≈ {{ formatRupiah(laporan.fee_idr) }}
                        </div>
                    </div>
                    <div class="text-right text-xs space-y-0.5">
                        <div class="text-muted-foreground">Profit Laporan:</div>
                        <div
                            class="font-mono font-bold text-sm"
                            :class="Number(laporan.profit_usd) >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'"
                        >
                            ${{ Number(laporan.profit_usd).toFixed(2) }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Banner Perlu Koreksi (Jika Ada) -->
            <div
                v-if="laporan.perlu_koreksi"
                class="rounded-xl border border-amber-500/30 bg-amber-500/10 p-3.5 text-xs text-amber-900 dark:text-amber-200 space-y-2"
            >
                <div class="flex items-center gap-1.5 font-bold">
                    <AlertCircle class="h-4 w-4 text-amber-600 dark:text-amber-400" />
                    <span>Laporan Ini Memerlukan Koreksi</span>
                </div>
                <p class="text-amber-800/90 dark:text-amber-300/90 leading-relaxed pl-5.5">
                    <strong>Catatan Admin:</strong> {{ laporan.catatan_koreksi || 'Mohon periksa dan perbaiki data laporan Anda.' }}
                </p>
                <div class="pl-5.5 pt-1">
                    <AppButton
                        type="custom"
                        size="xs"
                        variant="warning"
                        :icon="Edit3"
                        label="Koreksi Laporan Sekarang"
                        @click="triggerEdit"
                    />
                </div>
            </div>

            <!-- 3. Alasan Penolakan Pembayaran (Jika Ada) -->
            <div
                v-if="laporan.status === 'ditolak' && laporan.catatan_admin"
                class="rounded-xl border border-rose-500/30 bg-rose-500/10 p-3.5 text-xs text-rose-900 dark:text-rose-200 space-y-1"
            >
                <div class="flex items-center gap-1.5 font-bold">
                    <XCircle class="h-4 w-4 text-rose-600 dark:text-rose-400" />
                    <span>Pembayaran Sebelumnya Ditolak</span>
                </div>
                <p class="text-rose-800/90 dark:text-rose-300/90 leading-relaxed pl-5.5">
                    {{ laporan.catatan_admin }}
                </p>
                <p class="text-[11px] text-muted-foreground pl-5.5 pt-1">
                    Silakan unggah ulang bukti transfer yang valid di formulir bawah ini.
                </p>
            </div>

            <!-- 4. Rincian Perhitungan Profit & Penarikan -->
            <div class="rounded-xl border border-border p-3.5 text-xs space-y-2.5">
                <span class="font-bold text-foreground block">Rincian Laporan & Penarikan Broker</span>
                <div class="grid grid-cols-2 gap-2 text-muted-foreground text-[11px]">
                    <div>Profit Bersih: <strong class="text-foreground">${{ Number(laporan.profit_usd).toFixed(2) }}</strong></div>
                    <div>Penarikan (WD) USD: <strong class="text-foreground">{{ laporan.wd_usd !== null ? '$' + Number(laporan.wd_usd).toFixed(2) : '-' }}</strong></div>
                    <div>Rupiah Diterima: <strong class="text-foreground">{{ laporan.wd_idr !== null ? formatRupiah(laporan.wd_idr) : '-' }}</strong></div>
                    <div>Kurs Riil: <strong class="text-foreground">{{ laporan.kurs_riil !== null ? formatRupiah(laporan.kurs_riil) + ' / USD' : '-' }}</strong></div>
                </div>

                <!-- Tautan Bukti Myfxbook & Bukti WD -->
                <div class="pt-2 border-t border-border space-y-1.5 text-[11px]">
                    <div v-if="urls.bukti_myfxbook" class="flex items-center justify-between">
                        <span class="text-muted-foreground">Screenshot Myfxbook:</span>
                        <a
                            :href="urls.bukti_myfxbook"
                            target="_blank"
                            class="text-primary hover:underline font-semibold inline-flex items-center gap-1"
                        >
                            <Eye class="h-3 w-3" />
                            <span>Lihat Screenshot Myfxbook</span>
                        </a>
                    </div>
                    <div v-if="urls.bukti_wd" class="flex items-center justify-between">
                        <span class="text-muted-foreground">Screenshot Bukti WD Broker:</span>
                        <a
                            :href="urls.bukti_wd"
                            target="_blank"
                            class="text-primary hover:underline font-semibold inline-flex items-center gap-1"
                        >
                            <Eye class="h-3 w-3" />
                            <span>Lihat Bukti WD Broker</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- 5. Rekening Tujuan Transfer Master (Manual) -->
            <div
                v-if="Number(laporan.fee_usd) > 0 && ['belum_dibayar', 'ditolak', 'menunggu_verifikasi'].includes(laporan.status)"
                class="rounded-xl border border-border bg-muted/30 p-3.5 space-y-2.5"
            >
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-foreground flex items-center gap-1.5">
                        <Building2 class="h-3.5 w-3.5 text-primary" />
                        Rekening Tujuan Transfer (Master)
                    </span>
                    <span class="text-[11px] text-muted-foreground">Mata Uang: IDR</span>
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

            <!-- 6. Form Unggah Bukti Transfer / Pratinjau Bukti -->
            <div v-if="Number(laporan.fee_usd) > 0" class="rounded-xl border border-border p-3.5 space-y-3">
                <span class="text-xs font-bold text-foreground block">
                    Bukti Pembayaran Master Fee
                </span>

                <!-- Jika sudah ada bukti transfer dan status menunggu_verifikasi / lunas -->
                <div v-if="urls.bukti_transfer && laporan.status !== 'ditolak'" class="space-y-2">
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
                    <div v-if="laporan.status === 'menunggu_verifikasi'" class="p-2.5 rounded-lg bg-amber-500/10 border border-amber-500/20 text-xs text-amber-800 dark:text-amber-300 flex items-start gap-2">
                        <Clock class="h-4 w-4 shrink-0 mt-0.5" />
                        <span>Bukti transfer telah diterima dan sedang menunggu verifikasi oleh Administrator.</span>
                    </div>
                    <div v-else-if="laporan.status === 'lunas'" class="p-2.5 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-xs text-emerald-800 dark:text-emerald-300 flex items-start gap-2">
                        <CheckCircle2 class="h-4 w-4 shrink-0 mt-0.5" />
                        <span>Pembayaran telah diverifikasi Lunas oleh Administrator. Terima kasih.</span>
                    </div>
                </div>

                <!-- Form Unggah jika status belum_dibayar atau ditolak -->
                <form
                    v-if="['belum_dibayar', 'ditolak'].includes(laporan.status)"
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
                            :disabled="uploadForm.processing"
                        />
                        <AppButton
                            type="submit"
                            size="sm"
                            :label="laporan.status === 'ditolak' ? 'Unggah Ulang Bukti' : 'Kirim Bukti Pembayaran'"
                            :loading="uploadForm.processing"
                            :disabled="uploadForm.processing"
                            :icon="UploadCloud"
                        />
                    </div>
                </form>
            </div>

            <div v-else class="rounded-xl border border-slate-500/20 bg-slate-500/5 p-3.5 text-xs text-slate-800 dark:text-slate-300 flex items-center gap-2">
                <Check class="h-4 w-4 text-slate-600 dark:text-slate-400 shrink-0" />
                <span>Laporan ini tidak dikenakan Master Fee karena profit tidak bernilai positif. Status: Tanpa Fee.</span>
            </div>
        </div>
    </AppModal>
</template>
