<script setup>
import { computed } from 'vue';
import { Calculator, CheckCircle2, Clock, ExternalLink, Minus } from 'lucide-vue-next';

const props = defineProps({
    periods: {
        type: Array,
        default: () => [],
    },
    accumulation: {
        type: Object,
        default: null,
    },
    persentaseTier: {
        type: [Number, String],
        default: 20,
    },
    tierLevel: {
        type: [Number, String],
        default: null,
    },
    modalRiil: {
        type: [Number, String],
        default: null,
    },
    modalIdr: {
        type: [Number, String],
        default: null,
    },
});

const formatUSD = (val) => {
    const num = Number(val ?? 0);
    if (isNaN(num)) return '$0.00';
    const prefix = num < 0 ? '-$' : '$';
    return prefix + Math.abs(num).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const formatIDR = (val) => {
    const num = Math.round(Number(val ?? 0));
    if (isNaN(num)) return 'Rp 0';
    const prefix = num < 0 ? '-Rp ' : 'Rp ';
    return prefix + Math.abs(num).toLocaleString('id-ID');
};

const formatPercent = (val) => {
    const num = Number(val ?? 0);
    if (isNaN(num)) return '0%';
    return Number.isInteger(num) ? `${num}%` : `${num.toFixed(1)}%`;
};

const computedAccumulation = computed(() => {
    if (props.accumulation) {
        return props.accumulation;
    }

    const list = props.periods || [];
    const profitUsd = list.reduce((sum, p) => sum + Number(p.profit_usd ?? 0), 0);
    const wdUsd = list.reduce((sum, p) => sum + Number(p.wd_usd ?? 0), 0);
    const feeUsd = list.reduce((sum, p) => sum + Number(p.fee_usd ?? 0), 0);
    const feeIdr = list.reduce((sum, p) => sum + Number(p.fee_idr ?? 0), 0);
    const commUsd = list.reduce((sum, p) => sum + Number(p.affiliate_commission_usd ?? 0), 0);
    const commIdr = list.reduce((sum, p) => sum + Number(p.affiliate_commission_idr ?? 0), 0);
    const completedCount = list.filter((p) => p.state === 'settled').length;

    return {
        profit_usd: Number(profitUsd.toFixed(2)),
        wd_usd: Number(wdUsd.toFixed(2)),
        wd_persen: profitUsd > 0 && wdUsd > 0 ? Number(((wdUsd / profitUsd) * 100).toFixed(1)) : 0,
        fee_usd: Number(feeUsd.toFixed(2)),
        fee_idr: Math.round(feeIdr),
        affiliate_commission_usd: Number(commUsd.toFixed(2)),
        affiliate_commission_idr: Math.round(commIdr),
        completed_periods_count: completedCount,
    };
});

const getPeriodBadgeClass = (period) => {
    if (period.state === 'settled') {
        return period.is_tanpa_fee
            ? 'border-sky-500/30 bg-sky-500/10 text-sky-800 dark:text-sky-300'
            : 'border-emerald-500/30 bg-emerald-500/10 text-emerald-800 dark:text-emerald-300';
    }
    if (period.state === 'unsettled') {
        return 'border-amber-500/30 bg-amber-500/10 text-amber-800 dark:text-amber-300';
    }
    return 'border-border bg-muted/50 text-muted-foreground';
};
</script>

<template>
    <div class="space-y-2.5 text-xs">
        <!-- 3 Kartu Vertikal Periode (P1, P2, P3) -->
        <div
            v-for="p in periods"
            :key="p.periode_ke"
            class="border-border bg-card space-y-2.5 rounded-xl border p-3"
        >
            <div class="border-border/60 flex flex-wrap items-center justify-between gap-2 border-b pb-2">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="bg-primary-500/10 text-primary-800 dark:text-primary-300 border-primary-500/30 rounded-md border px-2 py-0.5 text-[11px] font-bold">
                        Periode {{ p.periode_ke }}
                    </span>
                    <span class="text-muted-foreground font-mono text-[11px]">
                        {{ p.periode_label || 'Belum Diterbitkan' }}
                    </span>
                </div>
                <span
                    :class="[
                        'inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-semibold',
                        getPeriodBadgeClass(p),
                    ]"
                >
                    <CheckCircle2 v-if="p.state === 'settled'" class="h-3 w-3" />
                    <Clock v-else-if="p.state === 'unsettled'" class="h-3 w-3" />
                    <Minus v-else class="h-3 w-3" />
                    <span>{{ p.status_tampil }}</span>
                </span>
            </div>

            <div v-if="p.has_report" class="grid grid-cols-2 gap-2.5 sm:grid-cols-5">
                <!-- Profit Periode -->
                <div class="border-border/50 bg-muted/20 rounded-lg border p-2">
                    <span class="text-muted-foreground block text-[10px]">Profit Periode</span>
                    <strong
                        class="font-mono text-xs"
                        :class="Number(p.profit_usd) > 0 ? 'text-foreground' : 'text-muted-foreground'"
                    >
                        {{ formatUSD(p.profit_usd) }}
                    </strong>
                </div>

                <!-- Investor WD + Persen -->
                <div class="border-border/50 bg-muted/20 rounded-lg border p-2">
                    <span class="text-muted-foreground block text-[10px]">Investor WD</span>
                    <strong class="text-foreground font-mono text-xs">
                        {{ formatUSD(p.wd_usd) }}
                    </strong>
                    <span class="text-primary-700 dark:text-primary-300 ml-1 font-mono text-[10px] font-semibold">
                        ({{ formatPercent(p.wd_persen) }})
                    </span>
                </div>

                <!-- Kirim ke Performance Fee (IDR & USD) -->
                <div class="border-border/50 bg-muted/20 rounded-lg border p-2">
                    <span class="text-muted-foreground block text-[10px]">
                        Performance Fee ({{ p.fee_persen ?? 30 }}%)
                    </span>
                    <template v-if="p.is_tanpa_fee && Number(p.fee_idr) <= 0">
                        <strong class="text-muted-foreground font-mono text-xs">Rp 0</strong>
                        <span class="text-muted-foreground block text-[10px]">Tanpa Fee</span>
                    </template>
                    <template v-else>
                        <strong class="text-foreground block font-mono text-xs">
                            {{ formatIDR(p.fee_idr) }}
                        </strong>
                        <span class="text-muted-foreground font-mono text-[10px]">
                            ({{ formatUSD(p.fee_usd) }})
                        </span>
                    </template>
                </div>

                <!-- Komisi Tier Afiliator -->
                <div class="rounded-lg border border-emerald-500/25 bg-emerald-500/5 p-2">
                    <span class="block text-[10px] font-semibold text-emerald-800 dark:text-emerald-300">
                        Komisi ({{ persentaseTier }}%)
                    </span>
                    <strong class="block font-mono text-xs font-bold text-emerald-800 dark:text-emerald-300">
                        {{ formatIDR(p.affiliate_commission_idr) }}
                    </strong>
                    <span class="font-mono text-[10px] text-emerald-700/90 dark:text-emerald-400/90">
                        ({{ formatUSD(p.affiliate_commission_usd) }})
                    </span>
                </div>

                <!-- Link Bukti Transfer Investor ke Master -->
                <div class="border-border/50 bg-muted/20 col-span-2 flex items-center justify-between rounded-lg border p-2 sm:col-span-1 sm:flex-col sm:items-start sm:justify-center">
                    <span class="text-muted-foreground block text-[10px]">Bukti Transfer Fee</span>
                    <a
                        v-if="p.bukti_transfer_url"
                        :href="p.bukti_transfer_url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="text-primary-700 hover:text-primary-800 dark:text-primary-300 dark:hover:text-primary-200 inline-flex min-h-[36px] items-center gap-1 rounded-md px-2 py-1 text-[11px] font-semibold underline-offset-2 hover:underline sm:min-h-0 sm:px-0 sm:py-0.5"
                    >
                        <span>Lihat Bukti</span>
                        <ExternalLink class="h-3 w-3 shrink-0" />
                    </a>
                    <span v-else class="text-muted-foreground font-mono text-xs">-</span>
                </div>
            </div>

            <p v-else class="text-muted-foreground text-[11px] italic">
                Laporan profit bulanan periode {{ p.periode_ke }} belum diterbitkan oleh investor.
            </p>
        </div>

        <!-- Kartu ke-4: Akumulasi 3 Periode -->
        <div class="space-y-2.5 rounded-xl border border-emerald-500/35 bg-emerald-500/10 p-3.5">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-emerald-500/20 pb-2">
                <div class="flex items-center gap-1.5">
                    <Calculator class="h-4 w-4 text-emerald-700 dark:text-emerald-300" />
                    <h5 class="text-xs font-bold tracking-wider text-emerald-950 uppercase dark:text-emerald-200">
                        Akumulasi 3 Periode
                    </h5>
                </div>
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="rounded border border-emerald-500/30 bg-card/80 px-2 py-0.5 font-mono text-[10px] font-semibold text-emerald-800 dark:text-emerald-300">
                        {{ computedAccumulation.completed_periods_count || 0 }}/3 Periode Selesai
                    </span>
                    <span
                        v-if="tierLevel !== null"
                        class="border-primary-500/30 bg-card/80 text-primary-800 dark:text-primary-300 rounded border px-2 py-0.5 text-[10px] font-bold"
                    >
                        Tier {{ tierLevel }} ({{ persentaseTier }}%<template v-if="modalRiil !== null"> &bull; Modal {{ formatUSD(modalRiil) }}</template>)
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-4">
                <div class="bg-card/85 border-border/60 rounded-lg border p-2.5">
                    <span class="text-muted-foreground block text-[10px]">Total Profit (3 Periode)</span>
                    <strong class="text-foreground font-mono text-xs font-bold">
                        {{ formatUSD(computedAccumulation.profit_usd) }}
                    </strong>
                </div>

                <div class="bg-card/85 border-border/60 rounded-lg border p-2.5">
                    <span class="text-muted-foreground block text-[10px]">Total Investor WD</span>
                    <strong class="text-foreground font-mono text-xs font-bold">
                        {{ formatUSD(computedAccumulation.wd_usd) }}
                    </strong>
                    <span class="text-primary-700 dark:text-primary-300 ml-1 font-mono text-[10px] font-semibold">
                        ({{ formatPercent(computedAccumulation.wd_persen) }})
                    </span>
                </div>

                <div class="bg-card/85 border-border/60 rounded-lg border p-2.5">
                    <span class="text-muted-foreground block text-[10px]">Total Kirim ke Master</span>
                    <strong class="text-foreground block font-mono text-xs font-bold">
                        {{ formatIDR(computedAccumulation.fee_idr) }}
                    </strong>
                    <span class="text-muted-foreground font-mono text-[10px]">
                        ({{ formatUSD(computedAccumulation.fee_usd) }})
                    </span>
                </div>

                <div class="bg-card rounded-lg border border-emerald-500/40 p-2.5">
                    <span class="block text-[10px] font-bold text-emerald-900 dark:text-emerald-200">
                        Total Komisi Afiliator ({{ persentaseTier }}%)
                    </span>
                    <strong class="block font-mono text-sm font-extrabold text-emerald-700 dark:text-emerald-300">
                        {{ formatIDR(computedAccumulation.affiliate_commission_idr) }}
                    </strong>
                    <span class="font-mono text-[10px] text-emerald-700/90 dark:text-emerald-400">
                        ({{ formatUSD(computedAccumulation.affiliate_commission_usd) }})
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
