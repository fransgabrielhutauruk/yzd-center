<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import {
    Calculator,
    TrendingUp,
    RotateCcw,
    MessageCircle,
    Info,
    Wallet,
    Scale,
    ShieldAlert,
    ChevronDown,
    ChevronUp,
    Sliders,
    Coins,
    ArrowUpRight,
} from 'lucide-vue-next';
import InvestorLayout from '@/Layouts/InvestorLayout.vue';
import { Button } from '@/Components/ui/button';
import { toast } from 'vue-sonner';

const props = defineProps({
    presets: {
        type: Object,
        required: true,
    },
});

// -----------------------------------------------------------------------------
// Variabel Input Utama & Opsi Penarikan
// -----------------------------------------------------------------------------
const initialCapital = ref(Number(props.presets.default_initial_capital ?? 500));
const monthlyProfitRate = ref(Number(props.presets.default_monthly_profit_rate ?? 5.0));
const durationYears = ref(Number(props.presets.default_duration_years ?? 5));
const exchangeRate = ref(Number(props.presets.default_exchange_rate ?? 16796));

// Opsi Penarikan: 'with_wd' (Tarik Rutin) vs 'without_wd' (Reinvest Penuh)
const withdrawalMode = ref('with_wd');

// State UI
const showAdvancedSettings = ref(false);
const showMonthlyTable = ref(false);
const selectedYearFilter = ref('all');

// -----------------------------------------------------------------------------
// Helper Format Angka & Waktu
// -----------------------------------------------------------------------------
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

const formatCompactUSD = (val) => {
    if (val >= 1000000) {
        return `$${(val / 1000000).toFixed(1)}M`;
    }
    if (val >= 1000) {
        return `$${(val / 1000).toFixed(val >= 10000 ? 0 : 1)}K`;
    }
    return `$${Math.round(val)}`;
};

const formattedRateUpdate = computed(() => {
    const meta = props.presets.exchange_rate_meta;
    if (!meta || !meta.updated_at) {
        return 'Pukul 06:00 WIB';
    }
    try {
        const d = new Date(meta.updated_at);
        return new Intl.DateTimeFormat('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            timeZone: 'Asia/Jakarta',
        }).format(d);
    } catch {
        return meta.date || '06:00 WIB';
    }
});

const applyLiveRate = () => {
    exchangeRate.value = Number(props.presets.default_exchange_rate ?? 16796);
    toast.info('Kurs dikembalikan ke nilai pasar tengah hari ini.', {
        description: `Rp ${Number(exchangeRate.value).toLocaleString('id-ID')} per 1 USD`,
    });
};

const resetToDefaults = () => {
    initialCapital.value = Number(props.presets.default_initial_capital ?? 100);
    monthlyProfitRate.value = Number(props.presets.default_monthly_profit_rate ?? 5.0);
    durationYears.value = Number(props.presets.default_duration_years ?? 5);
    withdrawalMode.value = 'with_wd';
    exchangeRate.value = Number(props.presets.default_exchange_rate ?? 16796);
    selectedYearFilter.value = 'all';
    toast.info('Kalkulator dikembalikan ke nilai acuan standar ($500, 5%, 5 Tahun, Tarik Rutin).');
};

// -----------------------------------------------------------------------------
// Engine Simulasi Matematis Finansial
// -----------------------------------------------------------------------------
const runSimulation = (mode, initModal, rate, totalMonths, kurs) => {
    let currentModal = initModal;
    let totalGrossProfit = 0;
    let totalTakenUSD = 0;
    let totalCompoundedUSD = 0;
    let totalMasterFeeUSD = 0;

    const monthlyList = [];
    const yearlyMap = {};

    let firstMonthWD = 0;
    let lastMonthWD = 0;

    for (let m = 1; m <= totalMonths; m++) {
        const yearNumber = Math.floor((m - 1) / 12) + 1;
        const yearDecimal = (m / 12).toFixed(1);

        const startModal = currentModal;
        const profit = startModal * rate;
        const masterFeeUSD = profit * 0.30; // 30% Master Trader Fee

        // Alokasi hak investor
        let takenUSD = 0;
        let compoundingUSD = 0;

        if (mode === 'with_wd') {
            takenUSD = profit * 0.30;       // 30% Hak Investor ditarik (WD rutin bulanan)
            compoundingUSD = profit * 0.40; // 40% Compounding ke modal
        } else {
            takenUSD = 0;                   // Tanpa WD (0% ditarik)
            compoundingUSD = profit * 0.70; // 70% Reinvest penuh ke modal
        }

        const takenIDR = takenUSD * kurs;
        const endModal = startModal + compoundingUSD;

        if (m === 1) firstMonthWD = takenUSD;
        if (m === totalMonths) lastMonthWD = takenUSD;

        totalGrossProfit += profit;
        totalTakenUSD += takenUSD;
        totalMasterFeeUSD += masterFeeUSD;
        totalCompoundedUSD += compoundingUSD;

        monthlyList.push({
            month: m,
            yearNumber,
            yearDecimal,
            startModal,
            profit,
            takenUSD,
            takenIDR,
            endModal,
        });

        // Agregasi Tahunan
        if (!yearlyMap[yearNumber]) {
            yearlyMap[yearNumber] = {
                year: yearNumber,
                startModal,
                endModal: 0,
                accumulatedWD: 0,
            };
        }
        yearlyMap[yearNumber].endModal = endModal;
        yearlyMap[yearNumber].accumulatedWD = totalTakenUSD;

        currentModal = endModal;
    }

    const totalNetFinancialValue = currentModal + totalTakenUSD;
    const multiplier = totalNetFinancialValue / initModal;

    return {
        mode,
        initialCapital: initModal,
        endModal: currentModal,
        totalGrossProfit,
        totalTakenUSD,
        totalTakenIDR: totalTakenUSD * kurs,
        totalMasterFeeUSD,
        totalCompoundedUSD,
        totalNetFinancialValue,
        multiplier,
        overallGrowthPct: ((currentModal - initModal) / initModal) * 100,
        monthlyList,
        yearlyList: Object.values(yearlyMap),
        totalMonths,
        firstMonthWD,
        lastMonthWD,
    };
};

// Kalkulasi Simulasi Aktif & Komparasi Skenario
const simulationData = computed(() => {
    const initModal = Math.max(1, Number(initialCapital.value) || 100);
    const rate = Math.max(0.1, Number(monthlyProfitRate.value) || 5.0) / 100;
    const years = Math.min(30, Math.max(1, Number(durationYears.value) || 5));
    const totalMonths = years * 12;
    const kurs = Number(exchangeRate.value) || 16796;

    // Skenario Aktif
    const active = runSimulation(withdrawalMode.value, initModal, rate, totalMonths, kurs);

    // Komparasi Alternatif
    const withWd = runSimulation('with_wd', initModal, rate, totalMonths, kurs);
    const withoutWd = runSimulation('without_wd', initModal, rate, totalMonths, kurs);

    return {
        ...active,
        comparison: {
            withWd,
            withoutWd,
            capitalDifference: withoutWd.endModal - withWd.endModal,
            wdDifference: withWd.totalTakenUSD - withoutWd.totalTakenUSD,
        },
    };
});

// Filter Bulanan Sesuai Pilihan Dropdown Tahun
const filteredMonthlyList = computed(() => {
    if (selectedYearFilter.value === 'all') {
        return simulationData.value.monthlyList;
    }
    const yNum = Number(selectedYearFilter.value);
    return simulationData.value.monthlyList.filter((m) => m.yearNumber === yNum);
});

// Agregasi Ringkasan Baris Terakhir (Summary) untuk Tabel Bulanan
const filteredMonthlySummary = computed(() => {
    const list = filteredMonthlyList.value;
    if (!list || list.length === 0) {
        return {
            endModal: 0,
            totalProfit: 0,
            totalTakenUSD: 0,
            totalTakenIDR: 0,
        };
    }

    const totalProfit = list.reduce((sum, item) => sum + (Number(item.profit) || 0), 0);
    const totalTakenUSD = list.reduce((sum, item) => sum + (Number(item.takenUSD) || 0), 0);
    const totalTakenIDR = list.reduce((sum, item) => sum + (Number(item.takenIDR) || 0), 0);
    const endModal = list[list.length - 1].endModal || 0;

    return {
        endModal,
        totalProfit,
        totalTakenUSD,
        totalTakenIDR,
    };
});

// -----------------------------------------------------------------------------
// Generator Data Grafik Garis SVG
// -----------------------------------------------------------------------------
const chartData = computed(() => {
    const sim = simulationData.value;
    const totalMonths = sim.totalMonths;

    // Tentukan nilai maksimum untuk skala vertikal
    const maxVal = Math.max(
        sim.endModal,
        sim.totalNetFinancialValue,
        sim.initialCapital,
        1
    ) * 1.08;

    const width = 800;
    const height = 260;
    const padding = { top: 20, right: 35, bottom: 35, left: 65 };
    const chartW = width - padding.left - padding.right;
    const chartH = height - padding.top - padding.bottom;

    // Sampling titik grafik agar mulus dan ringan
    // Jika <= 36 bulan: ambil per bulan. Jika > 36 bulan: ambil per 3-6 bulan + titik akhir tiap tahun
    const points = [];
    points.push({
        month: 0,
        label: 'Awal',
        modal: sim.initialCapital,
        wdAccum: 0,
        x: padding.left,
        yModal: padding.top + chartH - (sim.initialCapital / maxVal) * chartH,
        yWd: padding.top + chartH,
    });

    let runningAccumWd = 0;
    const step = totalMonths <= 36 ? 1 : Math.ceil(totalMonths / 40);

    for (let i = 0; i < sim.monthlyList.length; i++) {
        const item = sim.monthlyList[i];
        runningAccumWd += item.takenUSD;

        const isLastMonth = i === sim.monthlyList.length - 1;
        const isYearEnd = item.month % 12 === 0;
        const isSampled = item.month % step === 0;

        if (isSampled || isYearEnd || isLastMonth) {
            const x = padding.left + (item.month / totalMonths) * chartW;
            const yModal = padding.top + chartH - (item.endModal / maxVal) * chartH;
            const yWd = padding.top + chartH - (runningAccumWd / maxVal) * chartH;

            points.push({
                month: item.month,
                label: `Bln ${item.month}`,
                modal: item.endModal,
                wdAccum: runningAccumWd,
                x,
                yModal,
                yWd,
            });
        }
    }

    // Path SVG untuk Modal di Broker
    const modalPath = points.map((p, idx) => `${idx === 0 ? 'M' : 'L'} ${p.x.toFixed(1)} ${p.yModal.toFixed(1)}`).join(' ');
    // Area fill di bawah garis Modal
    const lastP = points[points.length - 1];
    const modalAreaPath = `${modalPath} L ${lastP.x.toFixed(1)} ${(padding.top + chartH).toFixed(1)} L ${padding.left.toFixed(1)} ${(padding.top + chartH).toFixed(1)} Z`;

    // Path SVG untuk Akumulasi WD (jika with_wd)
    const wdPath = points.map((p, idx) => `${idx === 0 ? 'M' : 'L'} ${p.x.toFixed(1)} ${p.yWd.toFixed(1)}`).join(' ');
    const wdAreaPath = `${wdPath} L ${lastP.x.toFixed(1)} ${(padding.top + chartH).toFixed(1)} L ${padding.left.toFixed(1)} ${(padding.top + chartH).toFixed(1)} Z`;

    // Sumbu horizontal grid
    const yGridTicks = [0, 0.25, 0.5, 0.75, 1].map((pct) => {
        const val = maxVal * pct;
        const y = padding.top + chartH - pct * chartH;
        return {
            y,
            val,
            label: formatCompactUSD(val),
        };
    });

    // Milestone waktu sumbu X
    const years = durationYears.value;
    const xTimeTicks = [];
    xTimeTicks.push({
        x: padding.left,
        label: 'Awal',
    });

    const yearInterval = years <= 5 ? 1 : years <= 15 ? 2 : 5;
    for (let y = yearInterval; y <= years; y += yearInterval) {
        const monthIndex = y * 12;
        const x = padding.left + (monthIndex / totalMonths) * chartW;
        xTimeTicks.push({
            x,
            label: `Thn ${y}`,
        });
    }

    // Milestone cards ringkas untuk mobile
    const midYear = Math.max(1, Math.floor(years / 2));
    const year1Data = sim.yearlyList[0];
    const midYearData = sim.yearlyList[midYear - 1] || sim.yearlyList[0];
    const endYearData = sim.yearlyList[sim.yearlyList.length - 1];

    return {
        width,
        height,
        padding,
        chartW,
        chartH,
        maxVal,
        points,
        modalPath,
        modalAreaPath,
        wdPath,
        wdAreaPath,
        yGridTicks,
        xTimeTicks,
        lastPoint: lastP,
        milestones: [
            {
                title: 'Tahun ke-1',
                modal: year1Data?.endModal ?? 0,
                wdAccum: year1Data?.accumulatedWD ?? 0,
            },
            ...(years > 2 ? [{
                title: `Tahun ke-${midYear}`,
                modal: midYearData?.endModal ?? 0,
                wdAccum: midYearData?.accumulatedWD ?? 0,
            }] : []),
            {
                title: `Tahun ke-${years} (Akhir)`,
                modal: endYearData?.endModal ?? 0,
                wdAccum: endYearData?.accumulatedWD ?? 0,
            },
        ],
    };
});

// Tautan Konsultasi WhatsApp Resmi
const whatsappConsultUrl = computed(() => {
    const init = formatUSD(initialCapital.value);
    const end = formatUSD(simulationData.value.endModal);
    const rate = monthlyProfitRate.value;
    const dur = durationYears.value;
    const modeText = withdrawalMode.value === 'with_wd' ? 'Tarik Rutin Tiap Bulan' : 'Reinvest Penuh (Tanpa WD)';
    const msg = `Halo Pak Yazid, saya telah mencoba simulasi kalkulator proyeksi YZD Center dengan modal awal ${init} (${modeText}) dan target profit ${rate}%/bulan selama ${dur} tahun (estimasi modal akhir ${end}). Saya ingin konsultasi lebih lanjut mengenai registrasi akun dan alokasi copytrade.`;
    return `https://wa.me/6282388986189?text=${encodeURIComponent(msg)}`;
});
</script>

<template>
    <InvestorLayout
        title="Kalkulator Proyeksi Pertumbuhan Modal"
        subtitle="Simulasikan estimasi pertumbuhan modal dan arus kas penarikan bulanan."
        :breadcrumbs="[
            { label: 'Menu Utama' },
            { label: 'Kalkulator Proyeksi' }
        ]"
    >
        <template #header-actions>
            <Button
                variant="outline"
                size="sm"
                class="gap-1.5 border-slate-200 dark:border-slate-700 dark:hover:bg-white min-h-[36px] cursor-pointer"
                @click="resetToDefaults"
            >
                <RotateCcw class="h-4 w-4" />
                Reset Nilai Acuan
            </Button>
        </template>

        <div class="space-y-6">

            <!-- 1. PANEL INPUT RINGKAS (MODAL, PROFIT, DURASI & TOGGLE STRATEGI) -->
            <div class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900 space-y-5">
                
                <!-- Toggle Mode: Tarik Rutin vs Reinvest -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-xs font-bold text-slate-700 dark:text-slate-300">
                            Pilihan Strategi Arus Kas:
                        </label>
                        <span class="text-[11px] text-muted-foreground font-medium">
                            {{ withdrawalMode === 'with_wd' ? '30% WD Rutin • 40% Compounding' : '0% WD • 70% Reinvest Penuh' }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <button
                            type="button"
                            @click="withdrawalMode = 'with_wd'"
                            :class="[
                                'flex items-center gap-3 rounded-xl border-2 p-3 text-left transition-all cursor-pointer min-h-[44px]',
                                withdrawalMode === 'with_wd'
                                    ? 'border-sky-500 bg-sky-50/60 dark:border-sky-500 dark:bg-sky-950/30'
                                    : 'border-slate-200 bg-white hover:border-slate-300 dark:border-slate-800 dark:bg-slate-800/40',
                            ]"
                        >
                            <div
                                :class="[
                                    'rounded-lg p-2 shrink-0',
                                    withdrawalMode === 'with_wd'
                                        ? 'bg-sky-500 text-white'
                                        : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'
                                ]"
                            >
                                <Wallet class="w-4 h-4" />
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white">
                                        Tarik Rutin Tiap Bulan
                                    </span>
                                    <span class="text-[10px] font-bold px-1.5 py-0.2 rounded bg-sky-500/15 text-sky-700 dark:text-sky-300">
                                        Cash Flow
                                    </span>
                                </div>
                                <p class="text-[11px] text-muted-foreground truncate">
                                    30% profit ditarik ke rekening, 40% compounding ke modal
                                </p>
                            </div>
                        </button>

                        <button
                            type="button"
                            @click="withdrawalMode = 'without_wd'"
                            :class="[
                                'flex items-center gap-3 rounded-xl border-2 p-3 text-left transition-all cursor-pointer min-h-[44px]',
                                withdrawalMode === 'without_wd'
                                    ? 'border-purple-500 bg-purple-50/60 dark:border-purple-500 dark:bg-purple-950/30'
                                    : 'border-slate-200 bg-white hover:border-slate-300 dark:border-slate-800 dark:bg-slate-800/40',
                            ]"
                        >
                            <div
                                :class="[
                                    'rounded-lg p-2 shrink-0',
                                    withdrawalMode === 'without_wd'
                                        ? 'bg-purple-500 text-white'
                                        : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'
                                ]"
                            >
                                <TrendingUp class="w-4 h-4" />
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white">
                                        Reinvest Penuh (Tanpa WD)
                                    </span>
                                    <span class="text-[10px] font-bold px-1.5 py-0.2 rounded bg-purple-500/15 text-purple-700 dark:text-purple-300">
                                        Maksimal
                                    </span>
                                </div>
                                <p class="text-[11px] text-muted-foreground truncate">
                                    100% hak investor digulung kembali ke modal
                                </p>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Grid Input: Modal Awal, Profit Bulanan, Durasi -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-1">
                    <!-- 1. Modal Awal -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Modal Awal ($ USD)
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-bold text-slate-400">$</span>
                            <input
                                v-model.number="initialCapital"
                                type="number"
                                min="10"
                                step="50"
                                class="w-full rounded-xl border border-slate-300 py-2.5 pl-7 pr-3 text-sm font-bold text-slate-900 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-white font-mono"
                            />
                        </div>
                    </div>

                    <!-- 2. Target Profit Bulanan (%) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Asumsi Profit Bulanan (%)
                        </label>
                        <div class="relative">
                            <input
                                v-model.number="monthlyProfitRate"
                                type="number"
                                min="0.1"
                                max="50"
                                step="0.1"
                                class="w-full rounded-xl border border-slate-300 py-2.5 pl-3 pr-8 text-sm font-bold text-slate-900 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-white font-mono"
                            />
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">%</span>
                        </div>
                    </div>

                    <!-- 3. Durasi Proyeksi (Tahun) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Durasi Proyeksi (Tahun)
                        </label>
                        <div class="relative">
                            <input
                                v-model.number="durationYears"
                                type="number"
                                min="1"
                                max="30"
                                step="1"
                                class="w-full rounded-xl border border-slate-300 py-2.5 pl-3 pr-14 text-sm font-bold text-slate-900 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-white font-mono"
                            />
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">Tahun</span>
                        </div>
                    </div>
                </div>

                <!-- Pengaturan Lanjutan (Kurs Rp / USD - Collapsible) -->
                <div class="pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button
                        type="button"
                        @click="showAdvancedSettings = !showAdvancedSettings"
                        class="flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200 cursor-pointer min-h-[36px]"
                    >
                        <Sliders class="w-3.5 h-3.5" />
                        <span>Pengaturan Lanjutan: Kurs Acuan Rupiah (Rp / USD)</span>
                        <ChevronDown v-if="!showAdvancedSettings" class="w-3.5 h-3.5 ml-0.5" />
                        <ChevronUp v-else class="w-3.5 h-3.5 ml-0.5" />
                    </button>

                    <div v-show="showAdvancedSettings" class="mt-3 p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/80 dark:border-slate-800 space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="max-w-xs">
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    Kurs Acuan (Rp per 1 USD)
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">Rp</span>
                                    <input
                                        v-model.number="exchangeRate"
                                        type="number"
                                        min="10000"
                                        step="50"
                                        class="w-full rounded-lg border border-slate-300 py-1.5 pl-8 pr-3 text-xs font-bold text-slate-900 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-white font-mono"
                                    />
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <Button
                                    v-if="Number(exchangeRate) !== Number(presets.default_exchange_rate)"
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    @click="applyLiveRate"
                                    class="text-xs min-h-[32px] cursor-pointer"
                                >
                                    Gunakan Kurs Pasar Terkini
                                </Button>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 text-[11px] text-muted-foreground">

                            <span>
                                {{ presets.exchange_rate_meta?.is_live ? 'Default dari Frankfurter API' : 'Kurs Cadangan' }}
                                • Pembaruan: {{ formattedRateUpdate }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. KARTU HASIL (HERO METRICS: 4 METRIK UTAMA) -->
            <div class="space-y-2">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
                    
                    <!-- 1. Modal Akhir di Broker -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300 block">
                            Modal Akhir di Broker (Thn ke-{{ durationYears }})
                        </span>
                        <p class="mt-1 text-xl sm:text-2xl font-black text-slate-900 dark:text-white font-mono">
                            {{ formatUSD(simulationData.endModal) }}
                        </p>
                        <span class="text-xs text-muted-foreground block mt-0.5 font-medium">
                            {{ formatIDR(simulationData.endModal * exchangeRate) }}
                        </span>
                        <div class="mt-2 pt-2 border-t border-slate-100 dark:border-slate-800 text-[11px] font-semibold text-slate-600 dark:text-slate-400">
                            Peningkatan Modal: +{{ simulationData.overallGrowthPct.toLocaleString('id-ID', { maximumFractionDigits: 1 }) }}%
                        </div>
                    </div>

                    <!-- 2. Cash WD per Bulan (Arus Kas Masuk Rekening) -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300 block">
                            {{ withdrawalMode === 'with_wd' ? 'Estimasi Cash WD per Bulan' : 'Penarikan Tunai Rutin' }}
                        </span>
                        <template v-if="withdrawalMode === 'with_wd'">
                            <p class="mt-1 text-xl sm:text-2xl font-black text-slate-900 dark:text-white font-mono">
                                {{ formatUSD(simulationData.firstMonthWD) }}
                                <span class="text-xs font-medium text-muted-foreground">/ bln</span>
                            </p>
                            <span class="text-xs text-muted-foreground block mt-0.5 font-medium">
                                {{ formatIDR(simulationData.firstMonthWD * exchangeRate) }} / bln
                            </span>
                            <div class="mt-2 pt-2 border-t border-slate-100 dark:border-slate-800 text-[11px] font-semibold text-slate-600 dark:text-slate-400">
                                Total Masuk Rekening: {{ formatUSD(simulationData.totalTakenUSD) }}
                            </div>
                        </template>
                        <template v-else>
                            <p class="mt-1 text-xl sm:text-2xl font-black text-slate-900 dark:text-white font-mono">
                                $0.00
                            </p>
                            <span class="text-xs text-muted-foreground block mt-0.5 font-medium">
                                Tidak ada penarikan rutin
                            </span>
                            <div class="mt-2 pt-2 border-t border-slate-100 dark:border-slate-800 text-[11px] font-semibold text-slate-600 dark:text-slate-400">
                                100% Hak profit investor digulung ke modal
                            </div>
                        </template>
                    </div>

                    <!-- 3. Total Nilai Finansial (Modal + Seluruh WD) -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300 block">
                            Total Nilai Finansial (Capital + WD)
                        </span>
                        <p class="mt-1 text-xl sm:text-2xl font-black text-slate-900 dark:text-white font-mono">
                            {{ formatUSD(simulationData.totalNetFinancialValue) }}
                        </p>
                        <span class="text-xs text-muted-foreground block mt-0.5 font-medium">
                            {{ formatIDR(simulationData.totalNetFinancialValue * exchangeRate) }}
                        </span>
                        <div class="mt-2 pt-2 border-t border-slate-100 dark:border-slate-800 text-[11px] font-semibold text-slate-600 dark:text-slate-400">
                            Modal akhir ditambah seluruh akumulasi WD
                        </div>
                    </div>

                    <!-- 4. Kelipatan Modal Awal -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300 block">
                            Kelipatan Portofolio
                        </span>
                        <p class="mt-1 text-xl sm:text-2xl font-black text-slate-900 dark:text-white font-mono">
                            {{ simulationData.multiplier.toFixed(1) }}x
                            <span class="text-xs font-medium text-muted-foreground">lipat modal</span>
                        </p>
                        <span class="text-xs text-muted-foreground block mt-0.5 font-medium">
                            Dari modal awal {{ formatUSD(simulationData.initialCapital) }}
                        </span>
                        <div class="mt-2 pt-2 border-t border-slate-100 dark:border-slate-800 text-[11px] font-semibold text-slate-600 dark:text-slate-400">
                            Periode simulasi {{ durationYears }} tahun ({{ durationYears * 12 }} bulan)
                        </div>
                    </div>

                </div>

                <!-- Disclaimer 1 Baris Dekat Hasil -->
                <p class="text-xs text-muted-foreground flex items-center gap-1.5 px-1 pt-1">
                    <Info class="h-3.5 w-3.5 shrink-0 text-slate-400 dark:text-slate-500" />
                    <span>Simulasi matematis dengan asumsi profit {{ monthlyProfitRate }}%/bulan. Hasil transaksi riil berfluktuasi.</span>
                </p>
            </div>

            <!-- 3. BARIS SELISIH RINGKAS: TARIK RUTIN VS REINVEST -->
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-800/40 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                <div class="flex items-start sm:items-center gap-2.5">
                    <div class="rounded-lg p-2 bg-slate-200/80 text-slate-700 dark:bg-slate-700 dark:text-slate-200 shrink-0">
                        <Scale class="w-4 h-4" />
                    </div>
                    <div>
                        <template v-if="withdrawalMode === 'with_wd'">
                            <span class="font-bold text-slate-900 dark:text-white">
                                Perbandingan dengan Mode Reinvest Penuh:
                            </span>
                            <p class="text-slate-600 dark:text-slate-400 mt-0.5">
                                Jika memilih <strong>Reinvest (Tanpa WD)</strong>, modal akhir Anda menjadi <strong class="text-purple-600 dark:text-purple-400 font-mono">{{ formatUSD(simulationData.comparison.withoutWd.endModal) }}</strong> (+{{ formatUSD(simulationData.comparison.capitalDifference) }} lebih tinggi), namun tanpa penerimaan cash flow bulanan.
                            </p>
                        </template>
                        <template v-else>
                            <span class="font-bold text-slate-900 dark:text-white">
                                Perbandingan dengan Mode Tarik Rutin:
                            </span>
                            <p class="text-slate-600 dark:text-slate-400 mt-0.5">
                                Jika memilih <strong>Tarik Rutin</strong>, Anda menikmati cash flow dengan total <strong class="text-emerald-600 dark:text-emerald-400 font-mono">{{ formatUSD(simulationData.comparison.withWd.totalTakenUSD) }}</strong> masuk rekening, dengan modal akhir tetap bertumbuh menjadi <strong class="text-blue-600 dark:text-blue-400 font-mono">{{ formatUSD(simulationData.comparison.withWd.endModal) }}</strong>.
                            </p>
                        </template>
                    </div>
                </div>

                <Button
                    variant="outline"
                    size="sm"
                    @click="withdrawalMode = withdrawalMode === 'with_wd' ? 'without_wd' : 'with_wd'"
                    class="shrink-0 text-xs font-semibold min-h-[36px] cursor-pointer"
                >
                    Lihat Mode {{ withdrawalMode === 'with_wd' ? 'Reinvest Penuh' : 'Tarik Rutin' }}
                </Button>
            </div>

            <!-- 4. GRAFIK PERTUMBUHAN MODAL (SVG RINGAN & RESPONSIF) -->
            <div class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <TrendingUp class="w-4 h-4 text-primary" />
                            Kurva Pertumbuhan Portofolio ({{ durationYears }} Tahun)
                        </h3>
                        <p class="text-xs text-muted-foreground mt-0.5">
                            Visualisasi akumulasi modal {{ withdrawalMode === 'with_wd' ? 'dan penerimaan cash flow WD' : 'secara bunga majemuk' }}.
                        </p>
                    </div>

                    <!-- Legend Indikator -->
                    <div class="flex items-center gap-4 text-xs font-medium">
                        <div class="flex items-center gap-1.5">
                            <span class="h-2.5 w-2.5 rounded-full bg-blue-500"></span>
                            <span class="text-slate-700 dark:text-slate-300">Modal</span>
                        </div>
                        <div v-if="withdrawalMode === 'with_wd'" class="flex items-center gap-1.5">
                            <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                            <span class="text-slate-700 dark:text-slate-300">Akumulasi Cash WD</span>
                        </div>
                    </div>
                </div>

                <!-- Area Grafik SVG Responsif -->
                <div class="w-full overflow-hidden">
                    <svg
                        :viewBox="`0 0 ${chartData.width} ${chartData.height}`"
                        class="w-full h-48 sm:h-64 select-none"
                    >
                        <defs>
                            <!-- Linear Gradient Modal -->
                            <linearGradient id="modalGradient" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#3b82f6" stop-opacity="0.22" />
                                <stop offset="100%" stop-color="#3b82f6" stop-opacity="0.01" />
                            </linearGradient>

                            <!-- Linear Gradient WD -->
                            <linearGradient id="wdGradient" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#10b981" stop-opacity="0.18" />
                                <stop offset="100%" stop-color="#10b981" stop-opacity="0.01" />
                            </linearGradient>
                        </defs>

                        <!-- Garis Grid Horizontal & Label Sumbu Y -->
                        <g class="grid-lines">
                            <g v-for="tick in chartData.yGridTicks" :key="tick.y">
                                <line
                                    :x1="chartData.padding.left"
                                    :y1="tick.y"
                                    :x2="chartData.width - chartData.padding.right"
                                    :y2="tick.y"
                                    stroke="currentColor"
                                    class="text-slate-100 dark:text-slate-800"
                                    stroke-dasharray="4 4"
                                    stroke-width="1"
                                />
                                <text
                                    :x="chartData.padding.left - 8"
                                    :y="tick.y + 4"
                                    text-anchor="end"
                                    class="fill-slate-400 dark:fill-slate-500 text-[10px] font-mono"
                                >
                                    {{ tick.label }}
                                </text>
                            </g>
                        </g>

                        <!-- Area Fill Modal -->
                        <path :d="chartData.modalAreaPath" fill="url(#modalGradient)" />

                        <!-- Area Fill WD (Jika With WD) -->
                        <path
                            v-if="withdrawalMode === 'with_wd'"
                            :d="chartData.wdAreaPath"
                            fill="url(#wdGradient)"
                        />

                        <!-- Garis Garis WD (Hijau) -->
                        <path
                            v-if="withdrawalMode === 'with_wd'"
                            :d="chartData.wdPath"
                            fill="none"
                            stroke="#10b981"
                            stroke-width="2.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />

                        <!-- Garis Garis Modal (Biru) -->
                        <path
                            :d="chartData.modalPath"
                            fill="none"
                            stroke="#3b82f6"
                            stroke-width="2.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />

                        <!-- Titik Akhir Modal -->
                        <circle
                            :cx="chartData.lastPoint.x"
                            :cy="chartData.lastPoint.yModal"
                            r="4.5"
                            fill="#3b82f6"
                            stroke="#ffffff"
                            stroke-width="2"
                        />

                        <!-- Titik Akhir WD (Jika With WD) -->
                        <circle
                            v-if="withdrawalMode === 'with_wd'"
                            :cx="chartData.lastPoint.x"
                            :cy="chartData.lastPoint.yWd"
                            r="4"
                            fill="#10b981"
                            stroke="#ffffff"
                            stroke-width="2"
                        />

                        <!-- Label Sumbu X (Milestone Waktu) -->
                        <g class="x-ticks">
                            <text
                                v-for="xt in chartData.xTimeTicks"
                                :key="xt.label"
                                :x="xt.x"
                                :y="chartData.height - 10"
                                text-anchor="middle"
                                class="fill-slate-500 dark:fill-slate-400 text-[10px] font-medium"
                            >
                                {{ xt.label }}
                            </text>
                        </g>
                    </svg>
                </div>

                <!-- Milestone Cards (Bantu Baca di Smartphone) -->
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 pt-1">
                    <div
                        v-for="ms in chartData.milestones"
                        :key="ms.title"
                        class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800"
                    >
                        <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 block">
                            {{ ms.title }}
                        </span>
                        <div class="mt-1 flex items-baseline justify-between gap-1">
                            <span class="text-xs sm:text-sm font-extrabold text-blue-600 dark:text-blue-400 font-mono">
                                {{ formatUSD(ms.modal) }}
                            </span>
                            <span v-if="withdrawalMode === 'with_wd'" class="text-[10px] text-emerald-600 dark:text-emerald-400 font-mono font-medium">
                                +{{ formatUSD(ms.wdAccum) }} WD
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5. TABEL RINCIAN PER BULAN (COLLAPSIBLE + FILTER TAHUN) -->
            <div class="rounded-2xl border border-slate-200 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900 overflow-hidden">
                <!-- Header Collapsible Toggle -->
                <button
                    type="button"
                    @click="showMonthlyTable = !showMonthlyTable"
                    class="w-full flex items-center justify-between p-5 text-left hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors cursor-pointer min-h-[48px]"
                >
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                                Rincian Simulasi Per Bulan (Bulan 1 s/d {{ simulationData.totalMonths }})
                            </h3>
                            <span class="text-[11px] px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-medium">
                                {{ simulationData.totalMonths }} Bulan
                            </span>
                        </div>
                        <p class="text-xs text-muted-foreground mt-0.5">
                            {{ showMonthlyTable ? 'Klik untuk menutup rincian angka tabel bulanan' : 'Klik untuk melihat angka modal, profit, dan penarikan per bulan' }}
                        </p>
                    </div>

                    <div class="flex items-center gap-1.5 text-xs font-semibold text-primary">
                        <span>{{ showMonthlyTable ? 'Tutup Tabel' : 'Buka Tabel' }}</span>
                        <ChevronDown v-if="!showMonthlyTable" class="w-4 h-4" />
                        <ChevronUp v-else class="w-4 h-4" />
                    </div>
                </button>

                <!-- Isi Tabel Bulanan Jika Dibuka -->
                <div v-show="showMonthlyTable" class="p-5 pt-0 space-y-4 border-t border-slate-100 dark:border-slate-800">
                    
                    <!-- Filter Tahun -->
                    <div class="flex items-center justify-between gap-3 pt-4">
                        <span class="text-xs text-muted-foreground font-medium">
                            Menampilkan {{ filteredMonthlyList.length }} data bulan:
                        </span>

                        <div class="flex items-center gap-2 text-xs">
                            <label class="text-muted-foreground font-medium">Filter Tahun:</label>
                            <select
                                v-model="selectedYearFilter"
                                class="rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                            >
                                <option value="all">Semua Bulan (1 - {{ simulationData.totalMonths }})</option>
                                <option
                                    v-for="y in simulationData.yearlyList"
                                    :key="y.year"
                                    :value="String(y.year)"
                                >
                                    Tahun ke-{{ y.year }} (Bln {{ (y.year - 1) * 12 + 1 }} - {{ y.year * 12 }})
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Tabel 5 Kolom (atau 3 kolom jika mode reinvest) -->
                    <div class="overflow-x-auto max-h-[460px] overflow-y-auto rounded-xl border border-slate-200 dark:border-slate-800">
                        <table class="w-full text-left text-xs">
                            <thead class="sticky top-0 z-10">
                                <tr class="border-b border-slate-200 bg-slate-100 dark:border-slate-800 dark:bg-slate-800 font-semibold text-slate-700 dark:text-slate-200">
                                    <th class="py-2.5 px-3">Bulan</th>
                                    <th class="py-2.5 px-3">Modal</th>
                                    <th class="py-2.5 px-3">Profit ({{ monthlyProfitRate }}%)</th>
                                    <th v-if="withdrawalMode === 'with_wd'" class="py-2.5 px-3 text-emerald-700 dark:text-emerald-400">
                                        WD (USD)
                                    </th>
                                    <th v-if="withdrawalMode === 'with_wd'" class="py-2.5 px-3 text-emerald-700 dark:text-emerald-400">
                                        WD (IDR)
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr
                                    v-for="m in filteredMonthlyList"
                                    :key="m.month"
                                    class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors"
                                >
                                    <td class="py-2 px-3 font-semibold text-slate-900 dark:text-white">
                                        Bulan {{ m.month }}
                                    </td>
                                    <td class="py-2 px-3 font-mono">
                                        {{ formatUSD(m.startModal) }}
                                    </td>
                                    <td class="py-2 px-3 font-mono text-slate-600 dark:text-slate-300">
                                        {{ formatUSD(m.profit) }}
                                    </td>
                                    <td v-if="withdrawalMode === 'with_wd'" class="py-2 px-3 font-mono font-semibold text-emerald-600 dark:text-emerald-400">
                                        {{ formatUSD(m.takenUSD) }}
                                    </td>
                                    <td v-if="withdrawalMode === 'with_wd'" class="py-2 px-3 font-mono text-emerald-700 dark:text-emerald-300 font-medium">
                                        {{ formatIDR(m.takenIDR) }}
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="border-t-2 border-slate-300 bg-slate-50 font-bold text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                                    <td class="py-2.5 px-3">
                                        Total Akumulasi
                                    </td>
                                    <td class="py-2.5 px-3 font-mono font-black text-slate-900 dark:text-white">
                                        {{ formatUSD(filteredMonthlySummary.endModal) }}
                                    </td>
                                    <td class="py-2.5 px-3 font-mono font-bold text-slate-900 dark:text-white">
                                        {{ formatUSD(filteredMonthlySummary.totalProfit) }}
                                    </td>
                                    <td v-if="withdrawalMode === 'with_wd'" class="py-2.5 px-3 font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                        {{ formatUSD(filteredMonthlySummary.totalTakenUSD) }}
                                    </td>
                                    <td v-if="withdrawalMode === 'with_wd'" class="py-2.5 px-3 font-mono font-bold text-emerald-700 dark:text-emerald-300">
                                        {{ formatIDR(filteredMonthlySummary.totalTakenIDR) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 6. DISCLAIMER RISIKO LENGKAP -->
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 sm:p-5 dark:border-slate-800 dark:bg-slate-800/40 space-y-2">
                <div class="flex items-center gap-2 text-slate-900 dark:text-white font-bold text-xs sm:text-sm">
                    <ShieldAlert class="w-4 h-4 text-amber-500 shrink-0" />
                    <span>Disclaimer Risiko Investasi & Simulasi Finansial</span>
                </div>
                <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed font-normal">
                    Perdagangan valuta asing (forex) mengandung risiko pasar dan tidak menjamin imbal hasil pasti. Hasil masa lalu bukan jaminan kinerja masa depan. Kalkulator ini disajikan sebagai alat bantu edukasi matematis berbasis target profit rata-rata dan pembagian hasil (30% fee pengelola, 70% hak investor), bukan merupakan jaminan pendapatan tetap (fixed return) atau penawaran investasi tanpa risiko.
                </p>
            </div>

            <!-- 7. CTA WHATSAPP KONSULTASI RESMI (FLAT STYLE) -->
            <div class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="space-y-1">
                    <h4 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <MessageCircle class="h-4 w-4 text-emerald-600 shrink-0" />
                        Tertarik Memulai Portofolio Berdasarkan Simulasi Ini?
                    </h4>
                    <p class="text-xs text-slate-600 dark:text-slate-400 max-w-xl">
                        Konsultasikan alokasi modal dan tahapan registrasi akun copytrade resmi langsung via WhatsApp resmi YZD Center.
                    </p>
                </div>

                <a
                    :href="whatsappConsultUrl"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 px-4 py-2.5 text-xs font-bold text-white shadow-xs transition-colors cursor-pointer min-h-[40px]"
                >
                    <MessageCircle class="h-4 w-4" />
                    Konsultasi WhatsApp ({{ presets.contact.phone }})
                </a>
            </div>

        </div>
    </InvestorLayout>
</template>
