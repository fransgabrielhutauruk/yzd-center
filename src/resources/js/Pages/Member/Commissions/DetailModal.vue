<script setup>
import { computed } from 'vue';
import AppModal from '@/Components/Common/AppModal.vue';
import { Button } from '@/Components/ui/button';
import {
    Banknote,
    User,
    CheckCircle2,
    Clock,
    XCircle,
    ExternalLink,
    CreditCard,
    DollarSign,
    Users,
    TrendingUp,
    ShieldAlert,
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

defineEmits(['close']);

const formatUSD = (val) => {
    if (val === null || val === undefined || isNaN(val)) return '$0.00';
    return '$' + Number(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const formatRupiah = (val) => {
    if (val === null || val === undefined || isNaN(val)) return 'Rp 0';
    return 'Rp ' + Math.round(Number(val)).toLocaleString('id-ID');
};

const statusBadge = computed(() => {
    if (!props.claim) return { label: '-', class: '' };

    switch (props.claim.status) {
        case 'approved_transferred':
            return {
                label: 'Ditransfer',
                class: 'bg-emerald-500/10 text-emerald-800 dark:text-emerald-300 border-emerald-500/30',
                icon: CheckCircle2,
            };
        case 'rejected':
            return {
                label: 'Ditolak',
                class: 'bg-rose-500/10 text-rose-800 dark:text-rose-300 border-rose-500/30',
                icon: XCircle,
            };
        case 'pending':
        default:
            return {
                label: 'Menunggu Verifikasi',
                class: 'bg-sky-500/10 text-sky-800 dark:text-sky-300 border-sky-500/30',
                icon: Clock,
            };
    }
});
</script>

<template>
    <AppModal
        :show="isVisible"
        title="Rincian Permohonan Klaim Komisi"
        max-width="3xl"
        @close="$emit('close')"
    >
        <div v-if="claim" class="space-y-4 text-xs">
            <!-- Header Rincian: Kode Klaim, Akun, & Status -->
            <div class="flex flex-col gap-3 rounded-xl border border-border bg-muted/40 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="space-y-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <Banknote class="h-4 w-4 text-primary-600 dark:text-primary-400" />
                        <span class="text-foreground font-mono text-sm font-bold">{{ claim.kode_klaim }}</span>
                        <span class="rounded bg-sky-500/10 border border-sky-500/30 px-2 py-0.5 text-[10px] font-bold text-sky-800 dark:text-sky-300">
                            {{ claim.total_downline }} Investor ({{ claim.total_akun }} Akun Copytrade)
                        </span>
                    </div>
                    <p class="text-muted-foreground text-[11px]">
                        Diajukan pada {{ claim.created_at }}
                    </p>
                </div>
                <div class="flex items-center">
                    <span
                        :class="[
                            'inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-semibold',
                            statusBadge.class,
                        ]"
                    >
                        <component :is="statusBadge.icon" class="h-3 w-3" />
                        <span>{{ statusBadge.label }}</span>
                    </span>
                </div>
            </div>

            <!-- Rekening Pencairan Affiliator -->
            <div class="rounded-xl border border-border bg-card p-3.5 space-y-2">
                <div class="flex items-center gap-1.5 border-b border-border/60 pb-1.5">
                    <CreditCard class="h-4 w-4 text-primary-600 dark:text-primary-400" />
                    <h4 class="text-foreground text-xs font-bold uppercase tracking-wider">
                        Rekening Tujuan Pencairan
                    </h4>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-[11px] font-mono">
                    <div>
                        <span class="text-muted-foreground block text-[10px] font-sans">Bank:</span>
                        <strong class="text-foreground uppercase text-xs">{{ claim.nama_bank }}</strong>
                    </div>
                    <div>
                        <span class="text-muted-foreground block text-[10px] font-sans">No. Rekening:</span>
                        <strong class="text-foreground text-xs">{{ claim.nomor_rekening }}</strong>
                    </div>
                    <div>
                        <span class="text-muted-foreground block text-[10px] font-sans">Atas Nama:</span>
                        <strong class="text-foreground text-xs">{{ claim.atas_nama_rekening }}</strong>
                    </div>
                </div>
            </div>

            <!-- Daftar Rincian Akun Copytrade & Hari Kehitung -->
            <div class="rounded-xl border border-border bg-card p-4 space-y-3">
                <div class="flex items-center justify-between border-b border-border/60 pb-2">
                    <div class="flex items-center gap-2">
                        <Users class="h-4 w-4 text-sky-600 dark:text-sky-400" />
                        <h4 class="text-foreground text-xs font-bold uppercase tracking-wider">
                            Rincian Akun Copytrade yang Diklaim ({{ claim.items?.length || 0 }})
                        </h4>
                    </div>
                    <span class="text-[11px] font-mono text-muted-foreground">
                        Total Deposit: {{ formatUSD(claim.total_deposit_amount || claim.total_modal_riil) }}
                    </span>
                </div>

                <div class="space-y-3 max-h-80 overflow-y-auto pr-1">
                    <div
                        v-for="(item, idx) in claim.items"
                        :key="item.id || idx"
                        class="rounded-xl border border-border bg-muted/20 p-3 space-y-2.5"
                    >
                        <div class="flex items-center justify-between flex-wrap gap-1">
                            <div class="flex items-center gap-2">
                                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-primary-500/10 text-[10px] font-bold text-primary-800 dark:text-primary-300">
                                    {{ idx + 1 }}
                                </span>
                                <span class="font-bold text-foreground text-xs">{{ item.downline_name }}</span>
                                <span class="text-muted-foreground font-mono text-[11px]">@{{ item.downline_username }}</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span
                                    v-if="item.is_akun_pertama"
                                    class="rounded bg-sky-500/10 border border-sky-500/30 px-2 py-0.5 text-[10px] font-bold text-sky-800 dark:text-sky-300"
                                >
                                    Akun Utama (Acuan)
                                </span>
                                <span
                                    v-else
                                    class="rounded bg-purple-500/10 border border-purple-500/30 px-2 py-0.5 text-[10px] font-bold text-purple-800 dark:text-purple-300"
                                >
                                    Akun Tambahan
                                </span>
                                <span class="rounded bg-primary-500/10 text-primary-800 dark:text-primary-300 px-2 py-0.5 font-semibold text-[10px]">
                                    Tier {{ item.tier_level }} ({{ item.affiliate_share_pct || item.persentase_tier }}%)
                                </span>
                            </div>
                        </div>

                        <!-- Baris Rincian Akun & Modal -->
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-[11px] bg-muted/40 p-2 rounded-lg border border-border/40">
                            <div>
                                <span class="text-muted-foreground block text-[10px]">Akun Broker:</span>
                                <strong class="text-foreground">{{ item.broker }}</strong>
                                <span class="text-muted-foreground font-mono ml-1 text-[10px]">({{ item.nomor_akun_trading }})</span>
                            </div>
                            <div>
                                <span class="text-muted-foreground block text-[10px]">Periode Aktif:</span>
                                <span class="text-foreground font-medium block">{{ item.tanggal_mulai }} s/d {{ item.tanggal_selesai }}</span>
                                <span class="text-emerald-700 dark:text-emerald-400 font-bold text-[10px]">3 Periode Laporan Selesai</span>
                            </div>
                            <div>
                                <span class="text-muted-foreground block text-[10px]">Modal Awal Investor:</span>
                                <span class="text-foreground font-mono font-bold block">{{ formatUSD(item.deposit_amount || item.modal_riil) }}</span>
                                <span v-if="item.modal_idr" class="text-muted-foreground font-mono text-[10px] block">&approx; {{ formatRupiah(item.modal_idr) }}</span>
                            </div>
                        </div>

                        <!-- Rincian Hasil Kalkulasi Jika Sudah Disetujui -->
                        <div v-if="claim.status === 'approved_transferred'" class="pt-1">
                            <!-- Drawdown Guard jika rugi -->
                            <div
                                v-if="(item.gross_profit ?? 0) <= 0 && item.roi_percentage !== null"
                                class="rounded-lg bg-amber-500/10 border border-amber-500/20 p-2 text-[10px] text-amber-800 dark:text-amber-300"
                            >
                                Posisi rugi / drawdown (ROI {{ item.roi_percentage }}%). Tidak ada komisi yang dipotong (Komisi: $0.00).
                            </div>

                            <!-- Hasil Untung -->
                            <div
                                v-else
                                class="grid grid-cols-2 sm:grid-cols-5 gap-2 bg-card p-2 rounded-lg border border-border/60 text-[10px] font-mono"
                            >
                                <div>
                                    <span class="text-muted-foreground block font-sans">ROI Master:</span>
                                    <strong class="text-foreground">{{ item.roi_percentage ?? 0 }}%</strong>
                                </div>
                                <div>
                                    <span class="text-muted-foreground block font-sans">Gross Profit:</span>
                                    <strong class="text-foreground">{{ formatUSD(item.gross_profit) }}</strong>
                                </div>
                                <div>
                                    <span class="text-muted-foreground block font-sans">Hak Master ({{ item.master_share_pct ?? 30 }}%):</span>
                                    <strong class="text-foreground block">{{ formatUSD(item.master_gross_share ?? item.profit_master) }}</strong>
                                    <span v-if="item.master_gross_share_idr" class="text-muted-foreground text-[9px] block">(&approx; {{ formatRupiah(item.master_gross_share_idr) }})</span>
                                </div>
                                <div class="rounded bg-emerald-500/10 border border-emerald-500/20 px-1.5 py-0.5">
                                    <span class="text-emerald-800 dark:text-emerald-300 block font-sans font-bold">Komisi Afiliator:</span>
                                    <strong class="text-emerald-800 dark:text-emerald-300 text-xs block">{{ formatUSD(item.affiliate_commission ?? item.nominal_transfer) }}</strong>
                                    <span v-if="item.affiliate_commission_idr" class="text-emerald-700/80 dark:text-emerald-400/80 text-[9px] block">(&approx; {{ formatRupiah(item.affiliate_commission_idr) }})</span>
                                </div>
                                <div>
                                    <span class="text-muted-foreground block font-sans">Net Investor:</span>
                                    <strong class="text-foreground">{{ formatUSD(item.investor_net_profit) }}</strong>
                                </div>
                            </div>
                        </div>

                        <div v-else class="text-muted-foreground text-[10px] italic">
                            Menunggu verifikasi pencairan oleh Administrator.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bagian Ringkasan Pencairan & Verifikasi Admin -->
            <div v-if="claim.status === 'approved_transferred'" class="rounded-xl border border-emerald-500/30 bg-emerald-500/5 p-4 space-y-3">
                <div class="flex items-center gap-2 border-b border-emerald-500/20 pb-2">
                    <CheckCircle2 class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                    <h4 class="text-emerald-900 dark:text-emerald-300 text-xs font-bold uppercase tracking-wider">
                        Hasil Pencairan Dana Komisi
                    </h4>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 font-mono text-xs">
                    <div>
                        <span class="text-muted-foreground text-[10px] font-sans block">Total Gross Profit:</span>
                        <strong class="text-foreground">{{ formatUSD(claim.total_gross_profit) }}</strong>
                    </div>
                    <div>
                        <span class="text-muted-foreground text-[10px] font-sans block">Total Hak Master:</span>
                        <strong class="text-foreground">{{ formatUSD(claim.total_master_gross_share ?? claim.total_profit_master) }}</strong>
                    </div>
                    <div>
                        <span class="text-muted-foreground text-[10px] font-sans block">Total Net Master:</span>
                        <strong class="text-foreground">{{ formatUSD(claim.total_master_net_share) }}</strong>
                    </div>
                    <div class="rounded-lg bg-emerald-500/10 border border-emerald-500/30 p-2">
                        <span class="text-emerald-800 dark:text-emerald-300 text-[10px] font-sans font-bold block">Komisi (USD):</span>
                        <strong class="text-emerald-800 dark:text-emerald-300 text-sm font-extrabold">{{ formatUSD(claim.total_affiliate_commission ?? claim.total_nominal_transfer) }}</strong>
                    </div>
                </div>

                <!-- Konversi Rupiah Sesuai Setoran Riil Investor / Kurs Efektif -->
                <div
                    v-if="claim.kurs_konversi_idr || claim.total_nominal_transfer_idr"
                    class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-3 flex items-center justify-between flex-wrap gap-2"
                >
                    <div class="space-y-0.5">
                        <span class="text-[10px] font-semibold text-emerald-900 dark:text-emerald-200 block">
                            Kurs Konversi Efektif:
                        </span>
                        <strong class="font-mono text-xs text-foreground">
                            {{ formatRupiah(claim.kurs_konversi_idr) }} / $1 USD
                        </strong>
                    </div>
                    <div class="text-right space-y-0.5">
                        <span class="text-[10px] font-semibold text-emerald-900 dark:text-emerald-200 block">
                            Nominal Ditransfer ke Rekening (IDR):
                        </span>
                        <strong class="font-mono text-sm sm:text-base font-extrabold text-emerald-800 dark:text-emerald-300">
                            {{ formatRupiah(claim.total_nominal_transfer_idr) }}
                        </strong>
                    </div>
                </div>

                <div v-if="claim.catatan_admin" class="rounded-lg bg-card/60 p-2.5 border border-border/40 text-[11px] space-y-0.5">
                    <span class="text-muted-foreground font-semibold">Catatan Administrator:</span>
                    <p class="text-foreground italic">{{ claim.catatan_admin }}</p>
                </div>

                <div v-if="claim.bukti_transfer_url" class="pt-1">
                    <a
                        :href="claim.bukti_transfer_url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white shadow-xs transition-colors"
                    >
                        <ExternalLink class="h-3.5 w-3.5" />
                        <span>Lihat Bukti Resi Transfer Bank</span>
                    </a>
                </div>
            </div>

            <!-- Bagian Ditolak -->
            <div v-else-if="claim.status === 'rejected'" class="rounded-xl border border-rose-500/30 bg-rose-500/5 p-4 space-y-2">
                <div class="flex items-center gap-2 text-rose-800 dark:text-rose-300 font-bold">
                    <XCircle class="h-4 w-4" />
                    <span>Permohonan Klaim Ditolak</span>
                </div>
                <p class="text-muted-foreground text-[11px]">
                    Alasan Penolakan: <strong class="text-foreground">{{ claim.catatan_admin || 'Tidak ada catatan khusus.' }}</strong>
                </p>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-end">
                <Button variant="outline" @click="$emit('close')">
                    Tutup
                </Button>
            </div>
        </template>
    </AppModal>
</template>
