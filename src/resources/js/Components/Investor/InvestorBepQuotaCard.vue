<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import {
    Activity,
    CheckCircle2,
    Clock,
    DollarSign,
    Sparkles,
    TrendingUp,
    Wallet,
    ArrowUpRight,
    HelpCircle,
    Info,
} from 'lucide-vue-next';

const props = defineProps({
    bepData: {
        type: Object,
        required: true,
    },
    showCalculatorLink: {
        type: Boolean,
        default: true,
    },
    title: {
        type: String,
        default: 'Progress Balik Modal (BEP)',
    },
    subtitle: {
        type: String,
        default: 'Dihitung dari rata² profit bulanan sejak awal join hingga 100% modal kembali (termasuk yang sudah di-WD)',
    },
});

// Helper formatting
const formatUSD = (val) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(val || 0);
};

const formatIDR = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(val || 0);
};

// Normalisasi progress (0 - 100%)
const progress = computed(() => {
    return Math.min(100, Math.max(0, Number(props.bepData.bep_progress_pct) || 0));
});

// Proporsi segmen bar kuota
const wdRatio = computed(() => {
    const init = Number(props.bepData.initial_capital) || 1;
    const wd = Number(props.bepData.total_withdrawn) || 0;
    return Math.min(100, (wd / init) * 100);
});

const compoundRatio = computed(() => {
    const init = Number(props.bepData.initial_capital) || 1;
    const comp = Number(props.bepData.total_compounded) || 0;
    return Math.min(100, (comp / init) * 100);
});

const remainingRatio = computed(() => {
    return Math.max(0, 100 - (wdRatio.value + compoundRatio.value));
});

// Parameter lingkaran SVG kuota
const radius = 54;
const circumference = 2 * Math.PI * radius;
const strokeDashoffset = computed(() => {
    return circumference - (progress.value / 100) * circumference;
});
</script>

<template>
    <div class="relative overflow-hidden rounded-2xl border border-sky-200/80 bg-gradient-to-br from-white via-sky-50/40 to-indigo-50/40 p-5 sm:p-6 shadow-sm dark:border-sky-900/50 dark:from-slate-900 dark:via-slate-900 dark:to-sky-950/20">
        <!-- Watermark Efek Kuota Internet -->
        <div class="pointer-events-none absolute -right-12 -top-12 h-44 w-44 rounded-full bg-sky-500/10 blur-2xl"></div>

        <div class="relative space-y-5">
            <!-- Header Kartu ala Paket Data Telko -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-sky-500/10 px-2.5 py-0.5 text-xs font-bold text-sky-700 dark:text-sky-300 border border-sky-500/30">
                            <Activity class="h-3.5 w-3.5 text-sky-600 animate-pulse" />
                            Paket Kuota Balik Modal
                        </span>
                        <span
                            v-if="bepData.is_bep_achieved"
                            class="inline-flex items-center gap-1 rounded-full bg-emerald-500/15 px-2.5 py-0.5 text-xs font-bold text-emerald-700 dark:text-emerald-300 border border-emerald-500/30"
                        >
                            <CheckCircle2 class="h-3 w-3" />
                            100% BEP Tercapai (Murni Profit)
                        </span>
                        <span
                            v-else
                            class="inline-flex items-center gap-1 rounded-full bg-blue-500/10 px-2.5 py-0.5 text-xs font-semibold text-blue-700 dark:text-blue-300"
                        >
                            <Clock class="h-3 w-3" />
                            Est. BEP: Bulan ke-{{ bepData.months_to_bep || 23 }}
                        </span>
                    </div>

                    <h3 class="text-base sm:text-lg font-black tracking-tight text-slate-900 dark:text-white">
                        {{ title }}
                    </h3>
                    <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed max-w-2xl font-normal">
                        {{ subtitle }}
                    </p>
                </div>

                <div v-if="showCalculatorLink" class="shrink-0">
                    <Link
                        href="/investor/education/calculator"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold px-3.5 py-2 transition-colors shadow-xs cursor-pointer min-h-[38px]"
                    >
                        <span>Simulasi 20 Tahun</span>
                        <ArrowUpRight class="h-3.5 w-3.5" />
                    </Link>
                </div>
            </div>

            <!-- Tampilan Visual Utama Kuota Internet (Circular Gauge + Progress Bar) -->
            <div class="grid grid-cols-1 md:grid-cols-12 gap-5 items-center bg-white/80 dark:bg-slate-800/80 rounded-xl p-4 sm:p-5 border border-sky-100 dark:border-slate-800 shadow-xs backdrop-blur-xs">
                <!-- Circular Ring Gauge ala Kuota Data Telkomsel / MyTelkomsel -->
                <div class="md:col-span-4 flex flex-col items-center justify-center text-center">
                    <div class="relative flex items-center justify-center">
                        <svg class="h-36 w-36 -rotate-90 transform" viewBox="0 0 128 128">
                            <!-- Background Track Circle -->
                            <circle
                                cx="64"
                                cy="64"
                                :r="radius"
                                stroke="currentColor"
                                stroke-width="10"
                                fill="transparent"
                                class="text-slate-200 dark:text-slate-700"
                            />
                            <!-- Progress Stroke Circle -->
                            <circle
                                cx="64"
                                cy="64"
                                :r="radius"
                                stroke="currentColor"
                                stroke-width="10"
                                fill="transparent"
                                stroke-linecap="round"
                                :stroke-dasharray="circumference"
                                :stroke-dashoffset="strokeDashoffset"
                                class="text-sky-500 transition-all duration-1000 ease-out"
                            />
                        </svg>

                        <!-- Angka Kuota Tengah -->
                        <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                            <span class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white font-mono tracking-tight">
                                {{ progress.toFixed(1) }}%
                            </span>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-sky-700 dark:text-sky-300">
                                Balik Modal
                            </span>
                        </div>
                    </div>

                    <div class="mt-2 text-center">
                        <p class="text-xs font-bold text-slate-800 dark:text-slate-200">
                            {{ formatUSD(bepData.total_profit_accumulated) }}
                            <span class="text-slate-400 font-normal">/ {{ formatUSD(bepData.initial_capital) }}</span>
                        </p>
                        <p class="text-[11px] text-muted-foreground">
                            {{ bepData.is_bep_achieved ? 'Modal telah 100% kembali' : `Sisa ${formatUSD(bepData.remaining_capital_to_bep)} lagi` }}
                        </p>
                    </div>
                </div>

                <!-- Segmen Kuota & Detail Akumulasi (Termasuk yang sudah di-WD) -->
                <div class="md:col-span-8 space-y-4">
                    <!-- Segmented Kuota Bar -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between text-xs font-bold">
                            <span class="text-slate-700 dark:text-slate-300">Alokasi Pengembalian Modal</span>
                            <span class="text-sky-700 dark:text-sky-400 font-mono">
                                {{ progress >= 100 ? '100% BEP Terpenuhi' : `${(100 - progress).toFixed(1)}% Kuota Menuju BEP` }}
                            </span>
                        </div>

                        <!-- Bar Kapsul Segmented ala Kuota Internet -->
                        <div class="h-4 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700 flex p-0.5 gap-0.5">
                            <!-- Segmen 1: Sudah Ditarik (WD) -->
                            <div
                                v-if="wdRatio > 0"
                                :style="{ width: `${wdRatio}%` }"
                                class="h-full rounded-l-full bg-cyan-500 transition-all duration-700"
                                :title="`Cash WD: ${formatUSD(bepData.total_withdrawn)}`"
                            ></div>
                            <!-- Segmen 2: Compounding Modal -->
                            <div
                                v-if="compoundRatio > 0"
                                :style="{ width: `${compoundRatio}%` }"
                                :class="[
                                    'h-full bg-emerald-500 transition-all duration-700',
                                    wdRatio === 0 ? 'rounded-l-full' : '',
                                    remainingRatio === 0 ? 'rounded-r-full' : '',
                                ]"
                                :title="`Compounding: ${formatUSD(bepData.total_compounded)}`"
                            ></div>
                            <!-- Segmen 3: Sisa Belum Kembali -->
                            <div
                                v-if="remainingRatio > 0"
                                :style="{ width: `${remainingRatio}%` }"
                                class="h-full rounded-r-full bg-transparent"
                            ></div>
                        </div>

                        <!-- Keterangan Legenda Warna Kuota -->
                        <div class="flex flex-wrap items-center gap-3 pt-1 text-[11px]">
                            <div class="flex items-center gap-1.5">
                                <span class="h-2.5 w-2.5 rounded-full bg-cyan-500 shrink-0"></span>
                                <span class="text-slate-600 dark:text-slate-300 font-medium">
                                    Sudah di-WD: <strong class="text-slate-900 dark:text-white">{{ formatUSD(bepData.total_withdrawn) }}</strong>
                                </span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="h-2.5 w-2.5 rounded-full bg-emerald-500 shrink-0"></span>
                                <span class="text-slate-600 dark:text-slate-300 font-medium">
                                    Compounding: <strong class="text-slate-900 dark:text-white">{{ formatUSD(bepData.total_compounded) }}</strong>
                                </span>
                            </div>
                            <div class="flex items-center gap-1.5" v-if="remainingRatio > 0">
                                <span class="h-2.5 w-2.5 rounded-full bg-slate-300 dark:bg-slate-600 shrink-0"></span>
                                <span class="text-slate-500 dark:text-slate-400 font-medium">
                                    Sisa Modal: {{ formatUSD(bepData.remaining_capital_to_bep) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Mini Grid Statistik Kuota -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 pt-2 border-t border-slate-100 dark:border-slate-700/60 text-xs">
                        <div class="bg-slate-50 dark:bg-slate-900/60 rounded-lg p-2.5">
                            <span class="text-[10px] text-muted-foreground block font-medium">Modal Awal</span>
                            <span class="font-bold text-foreground font-mono">{{ formatUSD(bepData.initial_capital) }}</span>
                            <span class="text-[9px] text-muted-foreground block truncate">{{ formatIDR(bepData.initial_capital_idr) }}</span>
                        </div>

                        <div class="bg-slate-50 dark:bg-slate-900/60 rounded-lg p-2.5">
                            <span class="text-[10px] text-muted-foreground block font-medium">Keuntungan Bersih</span>
                            <span class="font-bold text-emerald-600 dark:text-emerald-400 font-mono">{{ formatUSD(bepData.total_profit_accumulated) }}</span>
                            <span class="text-[9px] text-emerald-600/80 block truncate">{{ formatIDR(bepData.total_profit_idr) }}</span>
                        </div>

                        <div class="bg-slate-50 dark:bg-slate-900/60 rounded-lg p-2.5">
                            <span class="text-[10px] text-muted-foreground block font-medium">Bulan Sejak Join</span>
                            <span class="font-bold text-foreground font-mono">Bulan ke-{{ bepData.months_active || 1 }}</span>
                            <span class="text-[9px] text-muted-foreground block">Mulai: {{ bepData.join_date_formatted || 'Bulan ini' }}</span>
                        </div>

                        <div class="bg-slate-50 dark:bg-slate-900/60 rounded-lg p-2.5">
                            <span class="text-[10px] text-muted-foreground block font-medium">Sisa Waktu BEP</span>
                            <span class="font-bold font-mono" :class="bepData.is_bep_achieved ? 'text-emerald-600' : 'text-sky-600 dark:text-sky-400'">
                                {{ bepData.is_bep_achieved ? 'Lunas (BEP)' : `${bepData.remaining_months_to_bep || 0} Bln Lagi` }}
                            </span>
                            <span class="text-[9px] text-muted-foreground block">
                                {{ bepData.is_bep_achieved ? '100% Bebas Risiko' : `Target: Bln ke-${bepData.months_to_bep || 23}` }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Catatan Edukatif Singkat -->
            <div class="flex items-start gap-2 text-[11px] text-slate-500 dark:text-slate-400 bg-sky-50/50 dark:bg-sky-950/30 p-2.5 rounded-lg border border-sky-200/50 dark:border-sky-900/40">
                <Info class="w-3.5 h-3.5 text-sky-600 dark:text-sky-400 shrink-0 mt-0.5" />
                <p class="leading-relaxed">
                    <strong>Pemberitahuan Transparansi:</strong> Perhitungan BEP mencakup seluruh uang yang ditarik tunai (WD) ditambah pertumbuhan modal riil dari compounding. Setelah BEP 100% tercapai, seluruh modal awal Anda telah kembali sepenuhnya ke kantong Anda dan portofolio trading berjalan 100% dari keuntungan murni.
                </p>
            </div>
        </div>
    </div>
</template>
