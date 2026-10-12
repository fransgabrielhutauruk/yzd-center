<script setup>
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppModal from '@/Components/Common/AppModal.vue';
import { Button } from '@/Components/ui/button';
import { compressImageFile } from '@/lib/imageCompression';
import {
    Banknote,
    ExternalLink,
    CheckCircle2,
    XCircle,
    CreditCard,
    UploadCloud,
    Loader2,
    ShieldAlert,
    TrendingUp,
    ArrowRightLeft,
} from 'lucide-vue-next';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    isOpen: {
        type: Boolean,
        default: false,
    },
    claim: {
        type: Object,
        default: null,
    },
});

const isVisible = computed(() => props.show || props.isOpen);

const emit = defineEmits(['close']);

const decision = ref('approved_transferred'); // 'approved_transferred' | 'rejected'

const form = useForm({
    status: 'approved_transferred',
    kurs_konversi_idr: '',
    items: [],
    bukti_transfer: null,
    catatan_admin: '',
});

const isCompressing = ref(false);
const fileInputRef = ref(null);
const filePreviewName = ref('');

const formatUSD = (val) => {
    if (val === null || val === undefined || isNaN(val)) return '$0.00';
    return '$' + Number(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const formatRupiah = (val) => {
    if (val === null || val === undefined || isNaN(val)) return 'Rp 0';
    return 'Rp ' + Math.round(Number(val)).toLocaleString('id-ID');
};

watch(
    () => props.claim,
    (newClaim) => {
        if (newClaim) {
            form.reset();
            form.status = decision.value;
            form.kurs_konversi_idr = newClaim.kurs_konversi_idr
                ? String(Math.round(Number(newClaim.kurs_konversi_idr)))
                : '';
            filePreviewName.value = '';

            if (newClaim.items && newClaim.items.length > 0) {
                form.items = newClaim.items.map((it) => ({
                    id: it.id,
                    roi_percentage: it.roi_percentage !== null && it.roi_percentage !== undefined ? it.roi_percentage : '',
                    master_share_pct: it.master_share_pct !== null && it.master_share_pct !== undefined ? it.master_share_pct : 30.0,
                    affiliate_share_pct: it.affiliate_share_pct !== null && it.affiliate_share_pct !== undefined ? it.affiliate_share_pct : (it.persentase_tier || 20.0),
                }));
            } else {
                form.items = [];
            }
        }
    },
    { immediate: true }
);

watch(decision, (val) => {
    form.status = val;
});

// Perhitungan profit, drawdown guard, bagi hasil master, dan komisi afiliator per akun
const calculatedItems = computed(() => {
    if (!props.claim?.items) return [];

    return props.claim.items.map((it, idx) => {
        const itemForm = form.items[idx] || {};
        const deposit = Number(it.deposit_amount || it.modal_riil) || 0;
        const roiRaw = itemForm.roi_percentage;
        const roi = (roiRaw !== '' && roiRaw !== null && !isNaN(roiRaw)) ? Number(roiRaw) : null;
        const masterSharePct = Number(itemForm.master_share_pct ?? 30.0);
        const affiliateSharePct = Number(itemForm.affiliate_share_pct ?? it.persentase_tier ?? 20.0);

        if (roi === null) {
            return {
                ...it,
                deposit,
                roi: 0,
                hasInput: false,
                gross_profit: 0,
                master_gross_share: 0,
                affiliate_commission: 0,
                master_net_share: 0,
                investor_net_profit: 0,
                is_drawdown: false,
                transfer: 0,
            };
        }

        const grossProfit = deposit * (roi / 100);

        if (grossProfit <= 0) {
            return {
                ...it,
                deposit,
                roi,
                hasInput: true,
                gross_profit: grossProfit,
                master_gross_share: 0,
                affiliate_commission: 0,
                master_net_share: 0,
                investor_net_profit: grossProfit,
                is_drawdown: true,
                transfer: 0,
            };
        }

        const masterGrossShare = grossProfit * (masterSharePct / 100);
        const affiliateCommission = masterGrossShare * (affiliateSharePct / 100);
        const masterNetShare = masterGrossShare - affiliateCommission;
        const investorNetProfit = grossProfit - masterGrossShare;

        return {
            ...it,
            deposit,
            roi,
            hasInput: true,
            gross_profit: grossProfit,
            master_gross_share: masterGrossShare,
            affiliate_commission: affiliateCommission,
            master_net_share: masterNetShare,
            investor_net_profit: investorNetProfit,
            is_drawdown: false,
            transfer: affiliateCommission,
        };
    });
});

const totalCalculatedTransfer = computed(() => {
    return calculatedItems.value.reduce((acc, it) => acc + (it.transfer || 0), 0);
});

const parsedKursMt4 = computed(() => {
    const raw = form.kurs_konversi_idr;
    if (raw === '' || raw === null || isNaN(raw)) return 0;
    return Math.max(0, Number(raw));
});

const totalCalculatedTransferIdr = computed(() => {
    if (parsedKursMt4.value <= 0) return 0;
    return Math.round(totalCalculatedTransfer.value * parsedKursMt4.value);
});

const totalCalculatedGrossProfit = computed(() => {
    return calculatedItems.value.reduce((acc, it) => acc + (it.gross_profit || 0), 0);
});

const totalCalculatedMasterGrossShare = computed(() => {
    return calculatedItems.value.reduce((acc, it) => acc + (it.master_gross_share || 0), 0);
});

const totalCalculatedMasterNetShare = computed(() => {
    return calculatedItems.value.reduce((acc, it) => acc + (it.master_net_share || 0), 0);
});

const totalCalculatedInvestorNetProfit = computed(() => {
    return calculatedItems.value.reduce((acc, it) => acc + (it.investor_net_profit || 0), 0);
});

const allItemsHaveRoi = computed(() => {
    if (calculatedItems.value.length === 0) return false;
    return calculatedItems.value.every((it) => it.hasInput);
});

const hasValidKursIdr = computed(() => {
    return parsedKursMt4.value > 0;
});

const handleFileChange = async (event) => {
    const file = event.target.files?.[0];
    if (!file) return;

    if (file.type.startsWith('image/')) {
        try {
            isCompressing.value = true;
            const compressed = await compressImageFile(file);
            form.bukti_transfer = compressed?.file || compressed;
            filePreviewName.value = file.name;
        } catch {
            form.bukti_transfer = file;
            filePreviewName.value = file.name;
        } finally {
            isCompressing.value = false;
        }
    } else {
        form.bukti_transfer = file;
        filePreviewName.value = file.name;
    }
};

const submit = () => {
    if (!props.claim) return;

    form.post(route('admin.commissions.process', props.claim.id), {
        preserveScroll: true,
        onSuccess: () => {
            emit('close');
            form.reset();
            filePreviewName.value = '';
        },
    });
};
</script>

<template>
    <AppModal
        :show="isVisible"
        title="Proses Verifikasi & Pencairan Komisi Afiliasi"
        max-width="3xl"
        @close="$emit('close')"
    >
        <div v-if="claim" class="space-y-4 text-xs">
            <!-- Ringkasan Tiket Klaim -->
            <div class="flex items-center justify-between rounded-xl border border-border bg-muted/30 p-3 flex-wrap gap-2">
                <div class="flex items-center gap-2 flex-wrap">
                    <Banknote class="h-4 w-4 text-primary-600 dark:text-primary-400" />
                    <span class="text-foreground font-mono font-bold text-xs">{{ claim.kode_klaim }}</span>
                    <span class="rounded bg-sky-500/10 border border-sky-500/30 px-2 py-0.5 text-[10px] font-bold text-sky-800 dark:text-sky-300">
                        {{ claim.total_downline }} Investor ({{ claim.total_akun }} Akun Copytrade)
                    </span>
                </div>
                <span class="text-muted-foreground text-[11px]">{{ claim.created_at }}</span>
            </div>

            <!-- Info Rekening Pencairan Affiliator -->
            <div class="rounded-xl border border-border bg-card p-3.5 space-y-2">
                <div class="flex items-center gap-1.5 border-b border-border/60 pb-1.5">
                    <CreditCard class="h-4 w-4 text-primary-600 dark:text-primary-400" />
                    <h4 class="text-foreground text-xs font-bold uppercase tracking-wider">
                        Rekening Tujuan Transfer (Mitra Affiliator)
                    </h4>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-[11px]">
                    <div>
                        <span class="text-muted-foreground block text-[10px]">Nama Affiliator:</span>
                        <strong class="text-foreground text-xs">{{ claim.affiliator?.name }}</strong>
                        <p class="text-muted-foreground font-mono text-[10px]">@{{ claim.affiliator?.username }}</p>
                    </div>
                    <div>
                        <span class="text-muted-foreground block text-[10px]">Bank / Rekening:</span>
                        <strong class="text-foreground uppercase font-mono text-xs">{{ claim.nama_bank }}</strong>
                        <p class="text-foreground font-mono font-bold">{{ claim.nomor_rekening }}</p>
                    </div>
                    <div>
                        <span class="text-muted-foreground block text-[10px]">Atas Nama Rekening:</span>
                        <strong class="text-foreground font-semibold text-xs">{{ claim.atas_nama_rekening }}</strong>
                        <p class="text-muted-foreground text-[10px]">WA: {{ claim.affiliator?.phone || '-' }}</p>
                    </div>
                </div>
            </div>

            <!-- TAMPILAN JIKA KLAIM SUDAH SELESAI DIPROSES (READONLY) -->
            <div v-if="claim.status !== 'pending'" class="space-y-4">
                <div
                    :class="[
                        'rounded-xl border p-4 space-y-3',
                        claim.status === 'approved_transferred'
                            ? 'border-emerald-500/30 bg-emerald-500/5'
                            : 'border-rose-500/30 bg-rose-500/5',
                    ]"
                >
                    <div class="flex items-center gap-2 font-bold text-xs">
                        <component
                            :is="claim.status === 'approved_transferred' ? CheckCircle2 : XCircle"
                            class="h-4 w-4"
                        />
                        <span>
                            {{ claim.status === 'approved_transferred' ? 'Klaim Berhasil Dicairkan' : 'Klaim Ditolak' }}
                        </span>
                    </div>

                    <!-- Rincian Akun yang Dicairkan -->
                    <div class="space-y-2.5">
                        <div
                            v-for="(it, idx) in claim.items"
                            :key="idx"
                            class="rounded-xl bg-card border border-border/70 p-3 space-y-2 text-[11px]"
                        >
                            <div class="flex items-center justify-between flex-wrap gap-1">
                                <div>
                                    <span class="font-bold text-foreground">{{ it.downline_name }}</span>
                                    <span class="text-muted-foreground ml-1 font-mono text-[10px]">({{ it.broker }} - {{ it.nomor_akun_trading }})</span>
                                    <span class="text-muted-foreground ml-2 text-[10px]">
                                        Modal: <strong class="font-mono text-foreground">{{ formatUSD(it.deposit_amount || it.modal_riil) }}</strong>
                                        <span v-if="it.modal_idr" class="font-mono text-muted-foreground"> (&approx; {{ formatRupiah(it.modal_idr) }})</span>
                                    </span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="rounded bg-sky-500/10 border border-sky-500/30 px-1.5 py-0.2 text-[9px] font-bold text-sky-800 dark:text-sky-300">
                                        ROI: {{ it.roi_percentage ?? 0 }}%
                                    </span>
                                    <span class="rounded bg-primary-500/10 text-primary-800 dark:text-primary-300 border border-primary-500/30 px-1.5 py-0.2 font-semibold text-[9px]">
                                        Tier {{ it.tier_level }} ({{ it.affiliate_share_pct ?? it.persentase_tier }}%)
                                    </span>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 bg-muted/30 rounded-lg p-2 border border-border/40 text-[10px] font-mono">
                                <div>
                                    <span class="text-muted-foreground block font-sans">Gross Profit:</span>
                                    <strong class="text-foreground">{{ formatUSD(it.gross_profit) }}</strong>
                                </div>
                                <div>
                                    <span class="text-muted-foreground block font-sans">Hak Master ({{ it.master_share_pct ?? 30 }}%):</span>
                                    <strong class="text-foreground">{{ formatUSD(it.master_gross_share) }}</strong>
                                </div>
                                <div>
                                    <span class="text-muted-foreground block font-sans">Komisi Afiliator:</span>
                                    <strong class="text-emerald-700 dark:text-emerald-400 text-xs">{{ formatUSD(it.affiliate_commission ?? it.nominal_transfer) }}</strong>
                                </div>
                                <div>
                                    <span class="text-muted-foreground block font-sans">Net Master:</span>
                                    <strong class="text-foreground">{{ formatUSD(it.master_net_share) }}</strong>
                                </div>
                                <div>
                                    <span class="text-muted-foreground block font-sans">Net Investor:</span>
                                    <strong class="text-foreground">{{ formatUSD(it.investor_net_profit) }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="claim.status === 'approved_transferred'" class="space-y-2.5 pt-2 border-t border-border/40">
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 font-mono text-xs">
                            <div>
                                <span class="text-muted-foreground block text-[10px] font-sans">Total Gross Profit:</span>
                                <strong>{{ formatUSD(claim.total_gross_profit) }}</strong>
                            </div>
                            <div>
                                <span class="text-muted-foreground block text-[10px] font-sans">Total Hak Master:</span>
                                <strong>{{ formatUSD(claim.total_master_gross_share ?? claim.total_profit_master) }}</strong>
                            </div>
                            <div>
                                <span class="text-muted-foreground block text-[10px] font-sans">Total Komisi (USD):</span>
                                <strong class="text-emerald-700 dark:text-emerald-400 text-sm">{{ formatUSD(claim.total_affiliate_commission ?? claim.total_nominal_transfer) }}</strong>
                            </div>
                            <div>
                                <span class="text-muted-foreground block text-[10px] font-sans">Total Net Investor:</span>
                                <strong>{{ formatUSD(claim.total_investor_net_profit) }}</strong>
                            </div>
                        </div>

                        <!-- Info Kurs Konversi Riil MT4 & Nominal Transfer Rupiah -->
                        <div
                            v-if="claim.kurs_konversi_idr || claim.total_nominal_transfer_idr"
                            class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-3 flex items-center justify-between flex-wrap gap-2"
                        >
                            <div class="space-y-0.5">
                                <span class="text-[10px] font-semibold text-emerald-900 dark:text-emerald-200 block">
                                    Kurs Konversi Riil MT4 (Diinput Admin):
                                </span>
                                <strong class="font-mono text-xs text-foreground">
                                    {{ formatRupiah(claim.kurs_konversi_idr) }} / $1 USD
                                </strong>
                            </div>
                            <div class="text-right space-y-0.5">
                                <span class="text-[10px] font-semibold text-emerald-900 dark:text-emerald-200 block">
                                    Total Nominal Ditransfer (Rupiah):
                                </span>
                                <strong class="font-mono text-sm sm:text-base font-extrabold text-emerald-800 dark:text-emerald-300">
                                    {{ formatRupiah(claim.total_nominal_transfer_idr) }}
                                </strong>
                            </div>
                        </div>
                    </div>

                    <div v-if="claim.catatan_admin" class="text-[11px] pt-1 border-t border-border/40">
                        <span class="text-muted-foreground font-semibold">Catatan Admin: </span>
                        <span class="text-foreground italic">{{ claim.catatan_admin }}</span>
                    </div>

                    <div v-if="claim.bukti_transfer_url" class="pt-2">
                        <a
                            :href="claim.bukti_transfer_url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white shadow-xs transition-colors"
                        >
                            <ExternalLink class="h-3.5 w-3.5" />
                            <span>Lihat Resi Transfer Bank</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- FORM EKSEKUSI JIKA STATUS PENDING -->
            <form v-else @submit.prevent="submit" class="space-y-4">
                <!-- Pilihan Keputusan -->
                <div class="rounded-xl border border-border bg-card p-3.5 space-y-2">
                    <label class="text-foreground text-xs font-bold block">
                        Keputusan Administrator:
                    </label>
                    <div class="grid grid-cols-2 gap-2.5">
                        <button
                            type="button"
                            @click="decision = 'approved_transferred'"
                            :class="[
                                'flex items-center justify-center gap-2 rounded-xl border p-2.5 text-xs font-semibold transition-all',
                                decision === 'approved_transferred'
                                    ? 'border-emerald-500 bg-emerald-500/10 text-emerald-800 dark:text-emerald-300 ring-2 ring-emerald-500/20'
                                    : 'border-border text-muted-foreground hover:bg-muted/50',
                            ]"
                        >
                            <CheckCircle2 class="h-4 w-4" />
                            <span>Setujui & Selesaikan Transfer</span>
                        </button>

                        <button
                            type="button"
                            @click="decision = 'rejected'"
                            :class="[
                                'flex items-center justify-center gap-2 rounded-xl border p-2.5 text-xs font-semibold transition-all',
                                decision === 'rejected'
                                    ? 'border-rose-500 bg-rose-500/10 text-rose-800 dark:text-rose-300 ring-2 ring-rose-500/20'
                                    : 'border-border text-muted-foreground hover:bg-muted/50',
                            ]"
                        >
                            <XCircle class="h-4 w-4" />
                            <span>Tolak Klaim</span>
                        </button>
                    </div>
                </div>

                <!-- Bagian Form Jika Disetujui -->
                <div v-if="decision === 'approved_transferred'" class="space-y-4">
                    <!-- Rincian Input ROI & Perhitungan Komisi Per Akun Copytrade -->
                    <div class="rounded-xl border border-border bg-card p-3.5 space-y-3">
                        <div class="flex items-center justify-between border-b border-border/60 pb-2">
                            <div class="flex items-center gap-2">
                                <TrendingUp class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                                <h4 class="text-foreground text-xs font-bold uppercase tracking-wider">
                                    Kalkulasi Komisi Per Akun Copytrade (Tier Terpisah)
                                </h4>
                            </div>
                            <span class="text-[11px] font-mono text-muted-foreground">
                                {{ claim.items?.length || 0 }} Akun Copytrade
                            </span>
                        </div>

                        <!-- Card Akun Copytrade -->
                        <div class="space-y-3 max-h-80 overflow-y-auto pr-1">
                            <div
                                v-for="(it, idx) in calculatedItems"
                                :key="it.id || idx"
                                class="rounded-xl border border-border bg-muted/20 p-3 space-y-2.5"
                            >
                                <!-- Header Akun -->
                                <div class="flex items-center justify-between flex-wrap gap-1">
                                    <div class="flex items-center gap-1.5">
                                        <span class="flex h-4 w-4 items-center justify-center rounded-full bg-primary-500/10 text-[9px] font-bold text-primary-800 dark:text-primary-300">
                                            {{ idx + 1 }}
                                        </span>
                                        <span class="font-bold text-foreground text-xs">{{ it.downline_name }}</span>
                                        <span class="text-muted-foreground font-mono text-[10px]">({{ it.broker }} - {{ it.nomor_akun_trading }})</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span
                                            v-if="it.is_akun_pertama"
                                            class="rounded bg-sky-500/10 text-sky-800 dark:text-sky-300 border border-sky-500/30 px-1.5 py-0.2 text-[9px] font-bold"
                                        >
                                            Akun Pertama (Acuan)
                                        </span>
                                        <span
                                            v-else
                                            class="rounded bg-purple-500/10 text-purple-800 dark:text-purple-300 border border-purple-500/30 px-1.5 py-0.2 text-[9px] font-semibold"
                                        >
                                            Akun Tambahan
                                        </span>
                                        <span class="rounded bg-primary-500/10 text-primary-800 dark:text-primary-300 border border-primary-500/30 px-1.5 py-0.2 font-semibold text-[10px]">
                                            Tier {{ it.tier_level }} ({{ it.persentase_tier }}%)
                                        </span>
                                    </div>
                                </div>

                                <!-- Baris Info Periode & Deposit -->
                                <div class="text-[11px] text-muted-foreground bg-muted/50 rounded-lg p-2 flex items-center justify-between flex-wrap gap-1 border border-border/40">
                                    <span>
                                        Periode: <strong class="text-foreground">{{ it.tanggal_mulai }} s/d {{ it.tanggal_selesai }}</strong>
                                    </span>
                                    <span>
                                        Dihitung: <strong class="text-emerald-700 dark:text-emerald-400 font-bold">{{ it.hari_kehitung }} Hari</strong>
                                    </span>
                                    <span>
                                        Modal Akun: <strong class="text-foreground font-mono text-xs">{{ formatUSD(it.deposit) }}</strong>
                                        <span v-if="it.modal_idr" class="font-mono text-[10px] text-muted-foreground"> (&approx; {{ formatRupiah(it.modal_idr) }})</span>
                                    </span>
                                </div>

                                <!-- Input Parameter: ROI %, Hak Master %, Jatah Afiliator % -->
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 pt-1">
                                    <div class="space-y-1">
                                        <label class="text-muted-foreground text-[10px] block font-semibold">
                                            ROI Master Periode Ini (%) <span class="text-rose-500">*</span>:
                                        </label>
                                        <div class="relative">
                                            <input
                                                type="number"
                                                v-model="form.items[idx].roi_percentage"
                                                placeholder="cth: 20"
                                                step="0.01"
                                                required
                                                class="bg-background border-input focus:ring-primary-500 h-8 w-full rounded-lg border px-2.5 pr-6 text-xs font-mono focus:ring-2 focus:outline-none"
                                            />
                                            <span class="absolute right-2 top-2 text-[10px] text-muted-foreground font-bold">%</span>
                                        </div>
                                    </div>

                                    <div class="space-y-1">
                                        <label class="text-muted-foreground text-[10px] block font-semibold">
                                            Bagi Hasil Hak Master (%):
                                        </label>
                                        <div class="relative">
                                            <input
                                                type="number"
                                                v-model="form.items[idx].master_share_pct"
                                                placeholder="30"
                                                step="0.1"
                                                min="0"
                                                max="100"
                                                class="bg-background border-input focus:ring-primary-500 h-8 w-full rounded-lg border px-2.5 pr-6 text-xs font-mono focus:ring-2 focus:outline-none"
                                            />
                                            <span class="absolute right-2 top-2 text-[10px] text-muted-foreground font-bold">%</span>
                                        </div>
                                    </div>

                                    <div class="space-y-1">
                                        <label class="text-muted-foreground text-[10px] block font-semibold">
                                            Jatah Afiliator Tier {{ it.tier_level }} (%):
                                        </label>
                                        <div class="relative">
                                            <input
                                                type="number"
                                                v-model="form.items[idx].affiliate_share_pct"
                                                placeholder="20"
                                                step="0.1"
                                                min="0"
                                                max="100"
                                                class="bg-background border-input focus:ring-primary-500 h-8 w-full rounded-lg border px-2.5 pr-6 text-xs font-mono focus:ring-2 focus:outline-none"
                                            />
                                            <span class="absolute right-2 top-2 text-[10px] text-muted-foreground font-bold">%</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Box Hasil Live Perhitungan Akun -->
                                <div v-if="it.hasInput" class="pt-1">
                                    <!-- Drawdown Guard Alert jika Rugi / Impas -->
                                    <div
                                        v-if="it.is_drawdown"
                                        class="rounded-lg border border-amber-500/30 bg-amber-500/10 p-2.5 text-amber-900 dark:text-amber-200 text-[11px] flex items-start gap-2"
                                    >
                                        <ShieldAlert class="h-4 w-4 shrink-0 text-amber-600 mt-0.5" />
                                        <div>
                                            <p class="font-bold">Drawdown Guard Aktif (Posisi Rugi / Impas)</p>
                                            <p class="text-[10px] text-amber-800 dark:text-amber-300">
                                                Gross profit: <strong>{{ formatUSD(it.gross_profit) }}</strong>. Komisi afiliator & jatah master = <strong>$0.00</strong> (tidak ada pemotongan komisi saat rugi).
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Hasil Untung -->
                                    <div
                                        v-else
                                        class="grid grid-cols-2 sm:grid-cols-5 gap-2 rounded-lg bg-card border border-border/60 p-2.5 text-[10px] font-mono"
                                    >
                                        <div>
                                            <span class="text-muted-foreground block font-sans">Gross Profit:</span>
                                            <strong class="text-foreground text-xs">{{ formatUSD(it.gross_profit) }}</strong>
                                        </div>
                                        <div>
                                            <span class="text-muted-foreground block font-sans">Hak Kotor Master:</span>
                                            <strong class="text-foreground text-xs">{{ formatUSD(it.master_gross_share) }}</strong>
                                        </div>
                                        <div class="rounded-md bg-emerald-500/10 border border-emerald-500/30 px-2 py-0.5">
                                            <span class="text-emerald-800 dark:text-emerald-300 block font-sans font-bold">Komisi Afiliator:</span>
                                            <strong class="text-emerald-800 dark:text-emerald-300 text-xs">{{ formatUSD(it.affiliate_commission) }}</strong>
                                        </div>
                                        <div>
                                            <span class="text-muted-foreground block font-sans">Net Bersih Master:</span>
                                            <strong class="text-foreground text-xs">{{ formatUSD(it.master_net_share) }}</strong>
                                        </div>
                                        <div>
                                            <span class="text-muted-foreground block font-sans">Net Investor:</span>
                                            <strong class="text-foreground text-xs">{{ formatUSD(it.investor_net_profit) }}</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Ringkasan Total Akumulasi USD & Input Manual Kurs Konversi MT4 ke Rupiah -->
                        <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-3.5 space-y-3 text-xs">
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 font-mono text-[11px] pb-2 border-b border-emerald-500/20">
                                <div>
                                    <span class="text-muted-foreground block font-sans text-[10px]">Total Gross Profit:</span>
                                    <span class="font-bold text-foreground">{{ formatUSD(totalCalculatedGrossProfit) }}</span>
                                </div>
                                <div>
                                    <span class="text-muted-foreground block font-sans text-[10px]">Total Hak Master:</span>
                                    <span class="font-bold text-foreground">{{ formatUSD(totalCalculatedMasterGrossShare) }}</span>
                                </div>
                                <div>
                                    <span class="text-muted-foreground block font-sans text-[10px]">Total Net Master:</span>
                                    <span class="font-bold text-foreground">{{ formatUSD(totalCalculatedMasterNetShare) }}</span>
                                </div>
                                <div>
                                    <span class="text-muted-foreground block font-sans text-[10px]">Total Net Investor:</span>
                                    <span class="font-bold text-foreground">{{ formatUSD(totalCalculatedInvestorNetProfit) }}</span>
                                </div>
                            </div>

                            <!-- Input Manual Kurs Konversi Riil MT4 (Rp per $1 USD) -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-center pt-0.5">
                                <div class="space-y-1">
                                    <label class="text-foreground text-xs font-bold flex items-center gap-1.5">
                                        <ArrowRightLeft class="h-3.5 w-3.5 text-emerald-700 dark:text-emerald-400" />
                                        <span>Kurs Konversi Riil MT4 (Rp / $1 USD) <span class="text-rose-500">*</span>:</span>
                                    </label>
                                    <p class="text-[10px] text-muted-foreground">
                                        Input nilai kurs penarikan riil sesuai kondisi MT4 saat pencairan.
                                    </p>
                                    <div class="relative">
                                        <span class="absolute left-2.5 top-2 text-xs font-mono font-bold text-muted-foreground">Rp</span>
                                        <input
                                            type="number"
                                            v-model="form.kurs_konversi_idr"
                                            placeholder="cth: 16250"
                                            step="1"
                                            min="1"
                                            required
                                            class="bg-background border-input focus:ring-emerald-500 h-8 w-full rounded-lg border pl-8 pr-12 text-xs font-mono font-bold focus:ring-2 focus:outline-none"
                                        />
                                        <span class="absolute right-2.5 top-2 text-[10px] text-muted-foreground font-mono">/ $1</span>
                                    </div>
                                    <p v-if="form.errors.kurs_konversi_idr" class="text-rose-500 text-[10px]">
                                        {{ form.errors.kurs_konversi_idr }}
                                    </p>
                                </div>

                                <div class="rounded-xl bg-card/90 border border-emerald-500/30 p-3 space-y-1 text-right">
                                    <div class="flex items-center justify-between text-[11px]">
                                        <span class="text-muted-foreground">Total Komisi (USD):</span>
                                        <strong class="font-mono text-foreground">{{ formatUSD(totalCalculatedTransfer) }}</strong>
                                    </div>
                                    <div class="flex items-center justify-between border-t border-border/60 pt-1">
                                        <span class="font-bold text-emerald-900 dark:text-emerald-300 text-xs">
                                            Wajib Ditransfer (IDR):
                                        </span>
                                        <span class="font-mono text-emerald-800 dark:text-emerald-300 text-base font-extrabold">
                                            {{ hasValidKursIdr ? formatRupiah(totalCalculatedTransferIdr) : 'Input Kurs MT4' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Unggah Resi Transfer Bank -->
                    <div class="rounded-xl border border-border bg-card p-3.5 space-y-2">
                        <label class="text-foreground text-xs font-bold block">
                            Unggah Resi / Bukti Transfer Bank <span class="text-rose-500">*</span>
                        </label>
                        <p class="text-muted-foreground text-[11px]">
                            Lakukan transfer dana komisi senilai
                            <strong class="text-foreground font-mono">
                                {{ hasValidKursIdr ? `${formatRupiah(totalCalculatedTransferIdr)} (${formatUSD(totalCalculatedTransfer)})` : formatUSD(totalCalculatedTransfer) }}
                            </strong>
                            ke rekening tujuan di atas, lalu unggah struk pembayarannya.
                        </p>

                        <div class="flex items-center gap-2">
                            <input
                                ref="fileInputRef"
                                type="file"
                                accept="image/jpeg,image/png,image/jpg,application/pdf"
                                @change="handleFileChange"
                                class="hidden"
                            />
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                @click="fileInputRef?.click()"
                                :disabled="isCompressing"
                                class="h-8 text-xs"
                            >
                                <Loader2 v-if="isCompressing" class="h-3.5 w-3.5 animate-spin mr-1.5" />
                                <UploadCloud v-else class="h-3.5 w-3.5 mr-1.5 text-primary-600" />
                                <span>{{ filePreviewName ? 'Ganti Berkas' : 'Pilih Berkas Resi' }}</span>
                            </Button>
                            <span v-if="filePreviewName" class="text-muted-foreground font-mono text-[11px] truncate max-w-xs">
                                {{ filePreviewName }}
                            </span>
                        </div>
                        <p v-if="form.errors.bukti_transfer" class="text-rose-500 text-[11px]">
                            {{ form.errors.bukti_transfer }}
                        </p>
                    </div>

                    <!-- Catatan Tambahan (Opsional) -->
                    <div class="rounded-xl border border-border bg-card p-3.5 space-y-1.5">
                        <label class="text-foreground text-xs font-bold block">
                            Catatan Tambahan untuk Affiliator (Opsional)
                        </label>
                        <textarea
                            v-model="form.catatan_admin"
                            rows="2"
                            placeholder="cth: Transfer berhasil via m-Banking BCA sesuai kurs MT4 hari ini."
                            class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border p-2.5 text-xs focus:ring-2 focus:outline-none"
                        ></textarea>
                    </div>
                </div>

                <!-- Bagian Form Jika Ditolak -->
                <div v-else class="rounded-xl border border-rose-500/30 bg-rose-500/5 p-3.5 space-y-2">
                    <label class="text-rose-800 dark:text-rose-300 text-xs font-bold block">
                        Alasan Penolakan Klaim Komisi <span class="text-rose-500">*</span>
                    </label>
                    <textarea
                        v-model="form.catatan_admin"
                        rows="3"
                        placeholder="Jelaskan alasan penolakan secara jelas kepada Affiliator..."
                        required
                        class="bg-background border-rose-500/40 focus:ring-rose-500 w-full rounded-lg border p-2.5 text-xs focus:ring-2 focus:outline-none"
                    ></textarea>
                    <p v-if="form.errors.catatan_admin" class="text-rose-500 text-[11px]">
                        {{ form.errors.catatan_admin }}
                    </p>
                </div>

                <!-- Footer Aksi -->
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-border">
                    <Button
                        type="button"
                        variant="outline"
                        @click="$emit('close')"
                        :disabled="form.processing"
                    >
                        Batal
                    </Button>

                    <Button
                        type="submit"
                        :disabled="form.processing || isCompressing || (decision === 'approved_transferred' && (!form.bukti_transfer || !allItemsHaveRoi || !hasValidKursIdr))"
                        :class="[
                            decision === 'approved_transferred'
                                ? 'bg-emerald-600 hover:bg-emerald-700 text-white'
                                : 'bg-rose-600 hover:bg-rose-700 text-white',
                        ]"
                    >
                        <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin mr-1.5" />
                        <span v-if="decision === 'approved_transferred'">
                            Selesaikan Pencairan ({{ hasValidKursIdr ? formatRupiah(totalCalculatedTransferIdr) : formatUSD(totalCalculatedTransfer) }})
                        </span>
                        <span v-else>
                            Tolak Permohonan Klaim
                        </span>
                    </Button>
                </div>
            </form>
        </div>
    </AppModal>
</template>
