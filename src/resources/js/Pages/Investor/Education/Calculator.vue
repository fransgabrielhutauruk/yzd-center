<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import {
    Calculator,
    TrendingUp,
    Save,
    RotateCcw,
    MessageCircle,
    Info,
    Calendar,
    ArrowUpRight,
    DollarSign,
    Coins,
    Sparkles,
    CheckCircle2,
    Sliders,
    Wallet,
    Layers,
    Scale,
    Percent,
    ArrowRight,
} from 'lucide-vue-next';
import InvestorLayout from '@/Layouts/InvestorLayout.vue';
import InvestorBepQuotaCard from '@/Components/Investor/InvestorBepQuotaCard.vue';
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
const initialCapital = ref(props.presets.default_initial_capital ?? 100);
const monthlyProfitRate = ref(props.presets.default_monthly_profit_rate ?? 5.0);
const durationYears = ref(props.presets.default_duration_years ?? 5);
const exchangeRate = ref(props.presets.default_exchange_rate ?? 16796);

// Opsi Penarikan: 'with_wd' (Dengan WD Rutin) vs 'without_wd' (Tanpa WD)
const withdrawalMode = ref('with_wd');

// Tampilan Tab: 'yearly' (Tinjauan Tahunan) vs 'monthly' (Rincian Bulanan)
const activeViewTab = ref('yearly');

// Filter Tahun pada Rincian Bulanan (misal: 'all', '1', '2', ...)
const selectedYearFilter = ref('all');

// Helper format angka
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
        }).format(d) + ' WIB';
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

// -----------------------------------------------------------------------------
// Persistensi Preferensi Variabel di LocalStorage
// -----------------------------------------------------------------------------
const STORAGE_KEY = 'yzd_projection_calculator_vars_v2';

onMounted(() => {
    try {
        const saved = localStorage.getItem(STORAGE_KEY);
        if (saved) {
            const parsed = JSON.parse(saved);
            if (parsed.initialCapital !== undefined) initialCapital.value = Number(parsed.initialCapital);
            if (parsed.monthlyProfitRate !== undefined) monthlyProfitRate.value = Number(parsed.monthlyProfitRate);
            if (parsed.durationYears !== undefined) durationYears.value = Number(parsed.durationYears);
            if (parsed.withdrawalMode !== undefined) withdrawalMode.value = parsed.withdrawalMode;
            if (parsed.hasCustomExchangeRate && parsed.exchangeRate !== undefined) {
                exchangeRate.value = Number(parsed.exchangeRate);
            }
        }
    } catch {
        // Fallback default
    }
});

const saveVariables = () => {
    try {
        const isCustomRate = exchangeRate.value !== props.presets.default_exchange_rate;
        localStorage.setItem(
            STORAGE_KEY,
            JSON.stringify({
                initialCapital: initialCapital.value,
                monthlyProfitRate: monthlyProfitRate.value,
                durationYears: durationYears.value,
                withdrawalMode: withdrawalMode.value,
                exchangeRate: exchangeRate.value,
                hasCustomExchangeRate: isCustomRate,
            })
        );
        toast.success('Variabel Proyeksi Berhasil Disimpan', {
            description: `Modal: ${formatUSD(initialCapital.value)} | Profit: ${monthlyProfitRate.value}%/bln | ${durationYears.value} Tahun | Opsi: ${withdrawalMode.value === 'with_wd' ? 'Dengan WD' : 'Tanpa WD'}`,
        });
    } catch {
        toast.error('Gagal menyimpan variabel pada peramban.');
    }
};

const resetToDefaults = () => {
    initialCapital.value = props.presets.default_initial_capital ?? 100;
    monthlyProfitRate.value = props.presets.default_monthly_profit_rate ?? 5.0;
    durationYears.value = props.presets.default_duration_years ?? 5;
    withdrawalMode.value = 'with_wd';
    exchangeRate.value = Number(props.presets.default_exchange_rate ?? 16796);
    try {
        localStorage.removeItem(STORAGE_KEY);
    } catch {
        // Ignore
    }
    toast.info('Variabel dikembalikan ke standar awal ($100, 5%, 5 Tahun, Dengan WD).');
};

// -----------------------------------------------------------------------------
// ENGINE SIMULASI MATEMATIS FINANSIAL
// -----------------------------------------------------------------------------
const runSimulation = (mode, initModal, rate, totalMonths, kurs) => {
    let currentModal = initModal;
    let totalGrossProfit = 0;
    let totalTakenUSD = 0;
    let totalCompoundedUSD = 0;
    let totalMasterFeeUSD = 0;

    const monthlyList = [];
    const yearlyMap = {};
    let bepMonth = 0;

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
            takenUSD = profit * 0.30;       // 30% Hak Investor ditarik (WD)
            compoundingUSD = profit * 0.40; // 40% Compounding ke Modal
        } else {
            takenUSD = 0;                   // Tanpa WD (0% ditarik)
            compoundingUSD = profit * 0.70; // 70% Reinvest / Compounding Penuh Hak Investor
        }

        const takenIDR = takenUSD * kurs;
        const endModal = startModal + compoundingUSD;

        totalGrossProfit += profit;
        totalTakenUSD += takenUSD;
        totalMasterFeeUSD += masterFeeUSD;
        totalCompoundedUSD += compoundingUSD;

        // Akumulasi keuntungan bersih investor (WD yang sudah diterima + peningkatan modal)
        const netProfitAccumulated = totalTakenUSD + (endModal - initModal);
        if (netProfitAccumulated >= initModal && bepMonth === 0) {
            bepMonth = m;
        }

        const bepProgressPct = Math.min(100, (netProfitAccumulated / initModal) * 100);

        monthlyList.push({
            month: m,
            yearNumber,
            yearDecimal,
            startModal,
            profit,
            takenUSD,
            takenIDR,
            masterFeeUSD,
            compoundingUSD,
            endModal,
            netProfitAccumulated,
            bepProgressPct,
        });

        // Agregasi Tahunan
        if (!yearlyMap[yearNumber]) {
            yearlyMap[yearNumber] = {
                year: yearNumber,
                startModal,
                totalProfit: 0,
                totalTakenUSD: 0,
                totalTakenIDR: 0,
                totalMasterFeeUSD: 0,
                totalCompoundingUSD: 0,
                endModal: 0,
                netProfitAccumulated: 0,
            };
        }
        yearlyMap[yearNumber].totalProfit += profit;
        yearlyMap[yearNumber].totalTakenUSD += takenUSD;
        yearlyMap[yearNumber].totalTakenIDR += takenIDR;
        yearlyMap[yearNumber].totalMasterFeeUSD += masterFeeUSD;
        yearlyMap[yearNumber].totalCompoundingUSD += compoundingUSD;
        yearlyMap[yearNumber].endModal = endModal;
        yearlyMap[yearNumber].netProfitAccumulated = netProfitAccumulated;

        currentModal = endModal;
    }

    const yearlyList = Object.values(yearlyMap);
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
        totalNetGrowthPct: ((totalNetFinancialValue - initModal) / initModal) * 100,
        bepMonth: bepMonth || Math.ceil(totalMonths),
        isBepAchieved: (totalTakenUSD + (currentModal - initModal)) >= initModal,
        monthlyList,
        yearlyList,
        totalMonths,
    };
};

// Kalkulasi Proyeksi Utama & Komparasi Skenario
const simulationData = computed(() => {
    const initModal = Math.max(1, Number(initialCapital.value) || 100);
    const rate = Math.max(0.1, Number(monthlyProfitRate.value) || 5.0) / 100;
    const years = Math.min(30, Math.max(1, Number(durationYears.value) || 5));
    const totalMonths = years * 12;
    const kurs = Number(exchangeRate.value) || 16796;

    // Skenario Aktif
    const active = runSimulation(withdrawalMode.value, initModal, rate, totalMonths, kurs);

    // Skenario Kedua untuk Komparasi Akhir Siklus
    const withWd = runSimulation('with_wd', initModal, rate, totalMonths, kurs);
    const withoutWd = runSimulation('without_wd', initModal, rate, totalMonths, kurs);

    return {
        ...active,
        comparison: {
            withWd,
            withoutWd,
            capitalDifference: withoutWd.endModal - withWd.endModal,
            wdDifference: withWd.totalTakenUSD - withoutWd.totalTakenUSD,
            netDifference: withoutWd.totalNetFinancialValue - withWd.totalNetFinancialValue,
        },
    };
});

// Data Kuota Internet BEP untuk Komponen Visual
const bepCardData = computed(() => {
    const sim = simulationData.value;
    const kurs = Number(exchangeRate.value) || 16796;
    const init = sim.initialCapital;
    const totalProfit = sim.totalTakenUSD + (sim.endModal - init);
    const bepPct = Math.min(100, Math.max(0, (totalProfit / init) * 100));

    return {
        has_active_copytrade: true,
        initial_capital: init,
        initial_capital_idr: init * kurs,
        total_withdrawn: sim.totalTakenUSD,
        total_compounded: sim.totalCompoundedUSD,
        total_profit_accumulated: totalProfit,
        total_profit_idr: totalProfit * kurs,
        bep_progress_pct: bepPct,
        remaining_capital_to_bep: Math.max(0, init - totalProfit),
        months_to_bep: sim.bepMonth,
        remaining_months_to_bep: Math.max(0, sim.bepMonth - 1),
        months_active: 1,
        join_date_formatted: 'Simulasi Awal Join',
        is_bep_achieved: sim.isBepAchieved,
    };
});

// Filter Bulanan Sesuai Pilihan Tab Tahun
const filteredMonthlyList = computed(() => {
    if (selectedYearFilter.value === 'all') {
        return simulationData.value.monthlyList;
    }
    const yNum = Number(selectedYearFilter.value);
    return simulationData.value.monthlyList.filter((m) => m.yearNumber === yNum);
});

// Tautan Konsultasi WhatsApp Resmi
const whatsappConsultUrl = computed(() => {
    const init = formatUSD(initialCapital.value);
    const end = formatUSD(simulationData.value.endModal);
    const rate = monthlyProfitRate.value;
    const dur = durationYears.value;
    const modeText = withdrawalMode.value === 'with_wd' ? 'Dengan WD Rutin' : 'Tanpa WD (Reinvest)';
    const msg = `Halo Pak Yazid, saya telah mencoba simulasi kalkulator proyeksi YZD Center dengan modal awal ${init} (${modeText}) dan target profit ${rate}%/bulan selama ${dur} tahun (estimasi akhir ${end}). Saya ingin konsultasi lebih lanjut.`;
    return `https://wa.me/6282388986189?text=${encodeURIComponent(msg)}`;
});
</script>

<template>
    <InvestorLayout title="Kalkulator Proyeksi Finansial">
        <div class="space-y-6">
            <!-- Header Banner -->
            <div class="rounded-2xl bg-gradient-to-r from-slate-900 via-indigo-950 to-blue-900 p-6 sm:p-8 text-white shadow-md border border-indigo-800/40">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="max-w-2xl space-y-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 text-xs font-semibold backdrop-blur-sm border border-white/10 text-sky-200">
                            <Calculator class="h-3.5 w-3.5 text-sky-300" />
                            Kalkulator Finansial Investor — Proyeksi Modal & Kuota BEP
                        </span>
                        <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white">
                            Kalkulator Proyeksi Capital Gain & Balik Modal (BEP)
                        </h1>
                        <p class="text-xs sm:text-sm text-blue-100/90 leading-relaxed font-normal">
                            Hitung pertumbuhan portofolio modal hingga <strong>{{ durationYears }} tahun</strong> (misal 20 tahun), pantau estimasi waktu <strong>Balik Modal (BEP 100%)</strong>, serta bandingkan skenario <strong>Dengan WD Rutin</strong> vs <strong>Tanpa WD</strong> di akhir siklus.
                        </p>
                    </div>

                    <!-- Tombol Aksi Simpan & Reset -->
                    <div class="flex items-center gap-2 self-start sm:self-center">
                        <Button
                            variant="secondary"
                            size="sm"
                            class="gap-1.5 bg-white text-slate-900 hover:bg-slate-100 shadow-xs min-h-[36px] cursor-pointer"
                            @click="saveVariables"
                        >
                            <Save class="h-4 w-4" />
                            Simpan Variabel
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            class="gap-1.5 border-white/20 text-white hover:bg-white/10 min-h-[36px] cursor-pointer"
                            @click="resetToDefaults"
                        >
                            <RotateCcw class="h-4 w-4" />
                            Reset
                        </Button>
                    </div>
                </div>
            </div>

            <!-- 1. WIDGET KUOTA BALIK MODAL (BEP) ALA KUOTA INTERNET -->
            <InvestorBepQuotaCard
                :bep-data="bepCardData"
                :show-calculator-link="false"
                title="Progress Balik Modal (BEP) — Tampilan Kuota Internet"
                :subtitle="`Dihitung dari rata-rata profit ${monthlyProfitRate}%/bulan sejak awal join, hingga 100% modal kembali (mencakup akumulasi profit yang sudah di-WD + penambahan compounding modal).`"
            />

            <!-- 2. PANEL PENGATURAN VARIABEL PROYEKSI & OPSI PENARIKAN (CAPITAL GAIN) -->
            <div class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900 space-y-5">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <Sliders class="h-4 w-4 text-primary" />
                        Pengaturan Variabel Simulasi & Opsi Penarikan
                    </h3>
                    <span class="text-xs text-muted-foreground font-medium">
                        Model: {{ withdrawalMode === 'with_wd' ? 'Dengan WD Rutin (30%)' : 'Tanpa WD (100% Reinvest)' }}
                    </span>
                </div>

                <!-- Opsi Penarikan (Capital Gain): Dengan WD vs Tanpa WD -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">
                        Opsi Penarikan Profit Bulanan (Capital Gain Strategy):
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <!-- Opsi 1: Dengan WD Rutin -->
                        <div
                            @click="withdrawalMode = 'with_wd'"
                            :class="[
                                'relative rounded-xl border-2 p-4 cursor-pointer transition-all flex items-start gap-3.5',
                                withdrawalMode === 'with_wd'
                                    ? 'border-sky-500 bg-sky-50/60 dark:border-sky-500 dark:bg-sky-950/30'
                                    : 'border-slate-200 hover:border-slate-300 bg-white dark:border-slate-800 dark:bg-slate-800/50',
                            ]"
                        >
                            <div class="mt-0.5 rounded-lg p-2 bg-sky-500/10 text-sky-600 dark:text-sky-400">
                                <Wallet class="w-5 h-5" />
                            </div>
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <h4 class="text-sm font-bold text-slate-900 dark:text-white">
                                        Dengan WD Rutin (30%/bln)
                                    </h4>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-sky-500/15 text-sky-700 dark:text-sky-300">
                                        Rekomendasi Cash Flow
                                    </span>
                                </div>
                                <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed font-normal">
                                    Investor menarik profit rutin 30% tiap bulan ke rekening, 40% compounding memperkuat modal, dan 30% master fee.
                                </p>
                            </div>
                        </div>

                        <!-- Opsi 2: Tanpa WD -->
                        <div
                            @click="withdrawalMode = 'without_wd'"
                            :class="[
                                'relative rounded-xl border-2 p-4 cursor-pointer transition-all flex items-start gap-3.5',
                                withdrawalMode === 'without_wd'
                                    ? 'border-purple-500 bg-purple-50/60 dark:border-purple-500 dark:bg-purple-950/30'
                                    : 'border-slate-200 hover:border-slate-300 bg-white dark:border-slate-800 dark:bg-slate-800/50',
                            ]"
                        >
                            <div class="mt-0.5 rounded-lg p-2 bg-purple-500/10 text-purple-600 dark:text-purple-400">
                                <TrendingUp class="w-5 h-5" />
                            </div>
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <h4 class="text-sm font-bold text-slate-900 dark:text-white">
                                        Tanpa WD (100% Compounding)
                                    </h4>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-purple-500/15 text-purple-700 dark:text-purple-300">
                                        Maksimum Capital Gain
                                    </span>
                                </div>
                                <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed font-normal">
                                    0% penarikan. Seluruh 70% hak profit investor di-reinvest bergulir ke modal untuk akselerasi bola salju maksimal.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Baris Input Parameter -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 pt-2">
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
                        <div class="mt-2 flex flex-wrap gap-1">
                            <button
                                v-for="preset in [100, 500, 1000, 5000, 10000]"
                                :key="preset"
                                type="button"
                                @click="initialCapital = preset"
                                :class="[
                                    'rounded-md px-1.5 py-0.5 text-[10px] font-medium transition-colors cursor-pointer',
                                    initialCapital === preset
                                        ? 'bg-primary text-primary-foreground font-bold'
                                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300',
                                ]"
                            >
                                ${{ preset.toLocaleString() }}
                            </button>
                        </div>
                    </div>

                    <!-- 2. Rata-Rata Profit Bulanan (%) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Rata-Rata Profit Bulanan (%)
                        </label>
                        <div class="relative">
                            <input
                                v-model.number="monthlyProfitRate"
                                type="number"
                                min="0.1"
                                max="50"
                                step="0.5"
                                class="w-full rounded-xl border border-slate-300 py-2.5 pl-3 pr-8 text-sm font-bold text-slate-900 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-white font-mono"
                            />
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">%</span>
                        </div>
                        <div class="mt-2 flex flex-wrap gap-1">
                            <button
                                v-for="r in [3, 5, 8, 10]"
                                :key="r"
                                type="button"
                                @click="monthlyProfitRate = r"
                                :class="[
                                    'rounded-md px-2 py-0.5 text-[10px] font-medium transition-colors cursor-pointer',
                                    monthlyProfitRate === r
                                        ? 'bg-primary text-primary-foreground font-bold'
                                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300',
                                ]"
                            >
                                {{ r }}% {{ r === 5 ? '(Baku)' : '' }}
                            </button>
                        </div>
                    </div>

                    <!-- 3. Durasi Proyeksi (Tahun) - Bebas hingga 30 Tahun (contoh 20 tahun) -->
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
                        <div class="mt-2 flex flex-wrap gap-1">
                            <button
                                v-for="y in [3, 5, 10, 20, 30]"
                                :key="y"
                                type="button"
                                @click="durationYears = y"
                                :class="[
                                    'rounded-md px-1.5 py-0.5 text-[10px] font-medium transition-colors cursor-pointer',
                                    durationYears === y
                                        ? 'bg-primary text-primary-foreground font-bold'
                                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300',
                                ]"
                            >
                                {{ y }} Thn {{ y === 20 ? '(Contoh)' : '' }}
                            </button>
                        </div>
                    </div>

                    <!-- 4. Kurs Acuan Rupiah -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                Kurs Acuan (Rp / USD)
                            </label>
                            <button
                                v-if="Number(exchangeRate) !== Number(presets.default_exchange_rate)"
                                type="button"
                                @click="applyLiveRate"
                                class="text-[10px] text-primary hover:underline font-semibold cursor-pointer"
                            >
                                Reset Kurs
                            </button>
                        </div>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">Rp</span>
                            <input
                                v-model.number="exchangeRate"
                                type="number"
                                min="10000"
                                step="50"
                                class="w-full rounded-xl border border-slate-300 py-2.5 pl-8 pr-3 text-sm font-bold text-slate-900 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-white font-mono"
                            />
                        </div>
                        <div class="mt-2 flex items-center gap-1.5 text-[10px]">
                            <span
                                :class="[
                                    'inline-block h-2 w-2 rounded-full shrink-0',
                                    presets.exchange_rate_meta?.is_live ? 'bg-emerald-500 animate-pulse' : 'bg-amber-500'
                                ]"
                            ></span>
                            <span class="text-slate-500 dark:text-slate-400 leading-tight">
                                {{ presets.exchange_rate_meta?.is_live ? 'Live Pasar Tengah (Frankfurter API)' : 'Kurs Cadangan' }}
                                • {{ formattedRateUpdate }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. KARTU RINGKASAN PROYEKSI EKSEKUTIF (SUMMARY CARDS) -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- 1. Modal Awal -->
                <div class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                    <span class="text-xs font-medium text-muted-foreground block">
                        Modal Awal Pokok
                    </span>
                    <p class="mt-1 text-lg sm:text-2xl font-black text-foreground font-mono">
                        {{ formatUSD(simulationData.initialCapital) }}
                    </p>
                    <span class="text-[11px] text-muted-foreground block mt-0.5">
                        {{ formatIDR(simulationData.initialCapital * exchangeRate) }}
                    </span>
                </div>

                <!-- 2. Modal Akhir (Capital di Akhir Siklus) -->
                <div class="rounded-2xl border border-blue-200 bg-blue-50/50 p-4 sm:p-5 shadow-xs dark:border-blue-900/50 dark:bg-blue-950/30">
                    <span class="text-xs font-bold text-blue-800 dark:text-blue-300 block">
                        Capital di Akhir Siklus (Thn ke-{{ durationYears }})
                    </span>
                    <p class="mt-1 text-lg sm:text-2xl font-black text-blue-700 dark:text-blue-400 font-mono">
                        {{ formatUSD(simulationData.endModal) }}
                    </p>
                    <span class="text-[11px] text-blue-600/90 dark:text-blue-400/90 block mt-0.5 font-medium">
                        Tumbuh +{{ simulationData.overallGrowthPct.toLocaleString('id-ID', { maximumFractionDigits: 1 }) }}%
                    </span>
                </div>

                <!-- 3. Total Hak Investor (WD Masuk Rekening) -->
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50/50 p-4 sm:p-5 shadow-xs dark:border-emerald-900/50 dark:bg-emerald-950/30">
                    <span class="text-xs font-bold text-emerald-800 dark:text-emerald-300 block">
                        {{ withdrawalMode === 'with_wd' ? 'Total Cash WD Masuk Rekening' : 'Penarikan Tunai (Tanpa WD)' }}
                    </span>
                    <p class="mt-1 text-lg sm:text-2xl font-black text-emerald-700 dark:text-emerald-400 font-mono">
                        {{ formatUSD(simulationData.totalTakenUSD) }}
                    </p>
                    <span class="text-[11px] text-emerald-600/90 dark:text-emerald-400/90 block mt-0.5 font-medium">
                        {{ withdrawalMode === 'with_wd' ? formatIDR(simulationData.totalTakenIDR) : '100% dialokasikan compounding' }}
                    </span>
                </div>

                <!-- 4. Total Nilai Kekayaan Finansial Akhir -->
                <div class="rounded-2xl border border-purple-200 bg-purple-50/50 p-4 sm:p-5 shadow-xs dark:border-purple-900/50 dark:bg-purple-950/30">
                    <span class="text-xs font-bold text-purple-800 dark:text-purple-300 block">
                        Total Nilai Kekayaan (Capital + WD)
                    </span>
                    <p class="mt-1 text-lg sm:text-2xl font-black text-purple-700 dark:text-purple-400 font-mono">
                        {{ formatUSD(simulationData.totalNetFinancialValue) }}
                    </p>
                    <span class="text-[11px] text-purple-600/90 dark:text-purple-400/90 block mt-0.5 font-medium">
                        Kelipatan: {{ simulationData.multiplier.toFixed(1) }}x lipat modal awal
                    </span>
                </div>
            </div>

            <!-- 4. PROYEKSI KEUNTUNGAN / CAPITAL DI AKHIR SIKLUS (KOMPARASI DENGAN WD VS TANPA WD) -->
            <div class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900 space-y-5">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-4">
                    <div class="space-y-1">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/20">
                            <Scale class="w-3.5 h-3.5" />
                            Komparasi Akhir Siklus (Tahun ke-{{ durationYears }})
                        </span>
                        <h3 class="text-base sm:text-lg font-black text-slate-900 dark:text-white">
                            Proyeksi Keuntungan & Capital di Akhir Siklus
                        </h3>
                        <p class="text-xs text-muted-foreground leading-relaxed">
                            Bandingkan hasil akumulasi modal dan arus kas jika Anda melakukan penarikan rutin tiap bulan vs tanpa penarikan (reinvest penuh).
                        </p>
                    </div>

                    <div class="text-right">
                        <span class="text-xs text-muted-foreground block">Durasi Siklus:</span>
                        <span class="text-base font-black text-foreground font-mono">{{ durationYears }} Tahun ({{ durationYears * 12 }} Bulan)</span>
                    </div>
                </div>

                <!-- Grid Komparasi Side-by-Side -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <!-- Skenario A: Dengan WD Rutin Sebagian Tiap Bulan -->
                    <div
                        :class="[
                            'rounded-xl border-2 p-5 space-y-4 transition-all',
                            withdrawalMode === 'with_wd'
                                ? 'border-sky-500 bg-sky-50/40 dark:border-sky-500 dark:bg-sky-950/20 shadow-xs'
                                : 'border-slate-200 bg-slate-50/50 dark:border-slate-800 dark:bg-slate-800/30',
                        ]"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="rounded-lg p-2 bg-sky-500/10 text-sky-600 dark:text-sky-400">
                                    <Wallet class="w-4 h-4" />
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-slate-900 dark:text-white">
                                        Skenario A: Dengan WD Rutin
                                    </h4>
                                    <span class="text-[10px] text-muted-foreground">30% WD • 40% Compounding • 30% Master Fee</span>
                                </div>
                            </div>
                            <span
                                v-if="withdrawalMode === 'with_wd'"
                                class="inline-flex items-center gap-1 text-[11px] font-bold text-sky-600 dark:text-sky-400 bg-sky-100 dark:bg-sky-900/60 px-2 py-0.5 rounded-full"
                            >
                                <CheckCircle2 class="w-3 h-3" /> Pilihan Aktif
                            </span>
                        </div>

                        <div class="space-y-3 pt-2 border-t border-slate-200/60 dark:border-slate-700/60 text-xs">
                            <div class="flex justify-between items-center">
                                <span class="text-muted-foreground">Modal Akhir di Broker:</span>
                                <span class="font-bold text-foreground font-mono text-sm">
                                    {{ formatUSD(simulationData.comparison.withWd.endModal) }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-muted-foreground">Total Cash Masuk Rekening (WD):</span>
                                <span class="font-bold text-emerald-600 dark:text-emerald-400 font-mono text-sm">
                                    {{ formatUSD(simulationData.comparison.withWd.totalTakenUSD) }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-muted-foreground">Total Nilai Finansial (Modal + WD):</span>
                                <span class="font-black text-purple-700 dark:text-purple-400 font-mono text-sm">
                                    {{ formatUSD(simulationData.comparison.withWd.totalNetFinancialValue) }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-muted-foreground">Waktu Balik Modal (BEP 100%):</span>
                                <span class="font-bold text-sky-700 dark:text-sky-400 font-mono">
                                    Bulan ke-{{ simulationData.comparison.withWd.bepMonth }} (~{{ (simulationData.comparison.withWd.bepMonth / 12).toFixed(1) }} Tahun)
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-muted-foreground">Kelipatan Modal Awal:</span>
                                <span class="font-extrabold text-foreground font-mono">
                                    {{ simulationData.comparison.withWd.multiplier.toFixed(1) }}x lipat
                                </span>
                            </div>
                        </div>

                        <div class="p-3 rounded-lg bg-white dark:bg-slate-900 border border-sky-100 dark:border-slate-800 text-[11px] text-slate-600 dark:text-slate-400">
                            <strong>Kelebihan Skenario A:</strong> Menyediakan pendapatan pasif rutin bulanan ke rekening bank untuk kebutuhan hidup, sementara modal tetap berkembang aman.
                        </div>
                    </div>

                    <!-- Skenario B: Tanpa WD (Full Compounding / Reinvest) -->
                    <div
                        :class="[
                            'rounded-xl border-2 p-5 space-y-4 transition-all',
                            withdrawalMode === 'without_wd'
                                ? 'border-purple-500 bg-purple-50/40 dark:border-purple-500 dark:bg-purple-950/20 shadow-xs'
                                : 'border-slate-200 bg-slate-50/50 dark:border-slate-800 dark:bg-slate-800/30',
                        ]"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="rounded-lg p-2 bg-purple-500/10 text-purple-600 dark:text-purple-400">
                                    <TrendingUp class="w-4 h-4" />
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-slate-900 dark:text-white">
                                        Skenario B: Tanpa WD (Reinvest)
                                    </h4>
                                    <span class="text-[10px] text-muted-foreground">0% WD • 70% Compounding • 30% Master Fee</span>
                                </div>
                            </div>
                            <span
                                v-if="withdrawalMode === 'without_wd'"
                                class="inline-flex items-center gap-1 text-[11px] font-bold text-purple-600 dark:text-purple-400 bg-purple-100 dark:bg-purple-900/60 px-2 py-0.5 rounded-full"
                            >
                                <CheckCircle2 class="w-3 h-3" /> Pilihan Aktif
                            </span>
                        </div>

                        <div class="space-y-3 pt-2 border-t border-slate-200/60 dark:border-slate-700/60 text-xs">
                            <div class="flex justify-between items-center">
                                <span class="text-muted-foreground">Modal Akhir di Broker:</span>
                                <span class="font-bold text-foreground font-mono text-sm">
                                    {{ formatUSD(simulationData.comparison.withoutWd.endModal) }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-muted-foreground">Total Cash Masuk Rekening (WD):</span>
                                <span class="font-bold text-slate-400 font-mono text-sm">
                                    $0.00 (Tidak ditarik)
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-muted-foreground">Total Nilai Finansial (Modal + WD):</span>
                                <span class="font-black text-purple-700 dark:text-purple-400 font-mono text-sm">
                                    {{ formatUSD(simulationData.comparison.withoutWd.totalNetFinancialValue) }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-muted-foreground">Waktu Balik Modal (BEP 100%):</span>
                                <span class="font-bold text-purple-700 dark:text-purple-400 font-mono">
                                    Bulan ke-{{ simulationData.comparison.withoutWd.bepMonth }} (~{{ (simulationData.comparison.withoutWd.bepMonth / 12).toFixed(1) }} Tahun)
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-muted-foreground">Kelipatan Modal Awal:</span>
                                <span class="font-extrabold text-foreground font-mono">
                                    {{ simulationData.comparison.withoutWd.multiplier.toFixed(1) }}x lipat
                                </span>
                            </div>
                        </div>

                        <div class="p-3 rounded-lg bg-white dark:bg-slate-900 border border-purple-100 dark:border-slate-800 text-[11px] text-slate-600 dark:text-slate-400">
                            <strong>Kelebihan Skenario B:</strong> Sangat ideal untuk dana pensiun jangka panjang (10-20 tahun), di mana bunga majemuk melipatgandakan modal hingga berlipat ganda maksimal.
                        </div>
                    </div>
                </div>

                <!-- Selisih Pertumbuhan Finansial -->
                <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 p-4 border border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                    <div class="space-y-0.5">
                        <span class="font-bold text-foreground">Selisih Modal Akhir di Broker (Tanpa WD vs Dengan WD):</span>
                        <p class="text-muted-foreground">
                            Tanpa WD menghasilkan modal akhir lebih besar sebesar <strong class="text-purple-600 dark:text-purple-400 font-mono">{{ formatUSD(simulationData.comparison.capitalDifference) }}</strong>.
                        </p>
                    </div>
                    <div class="space-y-0.5 text-right sm:text-right">
                        <span class="font-bold text-foreground">Total Cash Ditarik pada Opsi WD:</span>
                        <p class="text-emerald-600 dark:text-emerald-400 font-bold font-mono">
                            +{{ formatUSD(simulationData.comparison.wdDifference) }} telah dinikmati
                        </p>
                    </div>
                </div>
            </div>

            <!-- 5. NAVIGASI TAMPILAN TABEL: TAHUNAN VS BULANAN -->
            <div class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-100 pb-4 dark:border-slate-800">
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="activeViewTab = 'yearly'"
                            :class="[
                                'rounded-xl px-4 py-2 text-xs font-bold transition-all cursor-pointer min-h-[36px]',
                                activeViewTab === 'yearly'
                                    ? 'bg-primary text-primary-foreground shadow-xs'
                                    : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300',
                            ]"
                        >
                            Tinjauan Tahunan (1 - {{ durationYears }} Tahun)
                        </button>
                        <button
                            type="button"
                            @click="activeViewTab = 'monthly'"
                            :class="[
                                'rounded-xl px-4 py-2 text-xs font-bold transition-all cursor-pointer min-h-[36px]',
                                activeViewTab === 'monthly'
                                    ? 'bg-primary text-primary-foreground shadow-xs'
                                    : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300',
                            ]"
                        >
                            Rincian Per Bulan (Bulan 1 s/d {{ simulationData.totalMonths }})
                        </button>
                    </div>

                    <!-- Filter Tahun (Jika di Tab Bulanan) -->
                    <div v-if="activeViewTab === 'monthly'" class="flex items-center gap-1.5 text-xs">
                        <span class="text-muted-foreground font-medium">Filter Tahun:</span>
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
                                Tahun ke-{{ y.year }} (Bulan {{ (y.year - 1) * 12 + 1 }} - {{ y.year * 12 }})
                            </option>
                        </select>
                    </div>
                </div>

                <!-- TAB 1: TINJAUAN PER TAHUN -->
                <div v-if="activeViewTab === 'yearly'" class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-800/50">
                                <th class="py-3 px-3 font-semibold text-slate-700 dark:text-slate-200">Periode</th>
                                <th class="py-3 px-3 font-semibold text-slate-700 dark:text-slate-200">Modal Awal</th>
                                <th class="py-3 px-3 font-semibold text-slate-700 dark:text-slate-200">Total Profit ({{ monthlyProfitRate }}%/bln)</th>
                                <th class="py-3 px-3 font-semibold text-slate-700 dark:text-slate-200">
                                    {{ withdrawalMode === 'with_wd' ? 'Investor WD (30%)' : 'Investor WD (0%)' }}
                                </th>
                                <th class="py-3 px-3 font-semibold text-slate-700 dark:text-slate-200">Investor (IDR)</th>
                                <th class="py-3 px-3 font-semibold text-slate-700 dark:text-slate-200">Master Fee (30%)</th>
                                <th class="py-3 px-3 font-semibold text-slate-700 dark:text-slate-200">
                                    {{ withdrawalMode === 'with_wd' ? 'Compounding (40%)' : 'Compounding (70%)' }}
                                </th>
                                <th class="py-3 px-3 font-semibold text-slate-700 dark:text-slate-200">Modal Akhir</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr
                                v-for="row in simulationData.yearlyList"
                                :key="row.year"
                                class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors"
                            >
                                <td class="py-3 px-3 font-bold text-slate-900 dark:text-white">
                                    Tahun ke-{{ row.year }}
                                </td>
                                <td class="py-3 px-3 font-mono font-medium">
                                    {{ formatUSD(row.startModal) }}
                                </td>
                                <td class="py-3 px-3 font-mono text-slate-600 dark:text-slate-300">
                                    {{ formatUSD(row.totalProfit) }}
                                </td>
                                <td class="py-3 px-3 font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                    {{ formatUSD(row.totalTakenUSD) }}
                                </td>
                                <td class="py-3 px-3 font-mono text-emerald-700 dark:text-emerald-300 font-semibold">
                                    {{ formatIDR(row.totalTakenIDR) }}
                                </td>
                                <td class="py-3 px-3 font-mono text-amber-600 dark:text-amber-400">
                                    {{ formatUSD(row.totalMasterFeeUSD) }}
                                </td>
                                <td class="py-3 px-3 font-mono text-purple-600 dark:text-purple-400">
                                    {{ formatUSD(row.totalCompoundingUSD) }}
                                </td>
                                <td class="py-3 px-3 font-mono font-bold text-blue-700 dark:text-blue-400">
                                    {{ formatUSD(row.endModal) }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 border-slate-300 bg-slate-50 font-bold text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                                <td class="py-3 px-3">Total Akumulasi</td>
                                <td class="py-3 px-3 font-mono">{{ formatUSD(simulationData.initialCapital) }}</td>
                                <td class="py-3 px-3 font-mono">{{ formatUSD(simulationData.totalGrossProfit) }}</td>
                                <td class="py-3 px-3 font-mono text-emerald-600 dark:text-emerald-400">
                                    {{ formatUSD(simulationData.totalTakenUSD) }}
                                </td>
                                <td class="py-3 px-3 font-mono text-emerald-700 dark:text-emerald-300">
                                    {{ formatIDR(simulationData.totalTakenIDR) }}
                                </td>
                                <td class="py-3 px-3 font-mono text-amber-600 dark:text-amber-400">
                                    {{ formatUSD(simulationData.totalMasterFeeUSD) }}
                                </td>
                                <td class="py-3 px-3 font-mono text-purple-600 dark:text-purple-400">
                                    {{ formatUSD(simulationData.totalCompoundedUSD) }}
                                </td>
                                <td class="py-3 px-3 font-mono text-blue-700 dark:text-blue-400">
                                    {{ formatUSD(simulationData.endModal) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- TAB 2: RINCIAN PER BULAN -->
                <div v-else class="overflow-x-auto max-h-[500px] overflow-y-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="sticky top-0 z-10">
                            <tr class="border-b border-slate-200 bg-slate-100 dark:border-slate-800 dark:bg-slate-800">
                                <th class="py-2.5 px-3 font-semibold text-slate-700 dark:text-slate-200">Bulan</th>
                                <th class="py-2.5 px-3 font-semibold text-slate-700 dark:text-slate-200">Tahun</th>
                                <th class="py-2.5 px-3 font-semibold text-slate-700 dark:text-slate-200">Modal</th>
                                <th class="py-2.5 px-3 font-semibold text-slate-700 dark:text-slate-200">Profit {{ monthlyProfitRate }}%</th>
                                <th class="py-2.5 px-3 font-semibold text-slate-700 dark:text-slate-200">
                                    {{ withdrawalMode === 'with_wd' ? 'Investor WD (30%)' : 'Investor WD (0%)' }}
                                </th>
                                <th class="py-2.5 px-3 font-semibold text-slate-700 dark:text-slate-200">Dalam Rupiah</th>
                                <th class="py-2.5 px-3 font-semibold text-slate-700 dark:text-slate-200">Master Fee (30%)</th>
                                <th class="py-2.5 px-3 font-semibold text-slate-700 dark:text-slate-200">
                                    {{ withdrawalMode === 'with_wd' ? 'Compounding 40%' : 'Compounding 70%' }}
                                </th>
                                <th class="py-2.5 px-3 font-semibold text-slate-700 dark:text-slate-200">Modal Akhir</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr
                                v-for="m in filteredMonthlyList"
                                :key="m.month"
                                class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors"
                            >
                                <td class="py-2 px-3 font-semibold text-slate-900 dark:text-white">
                                    {{ m.month }}
                                </td>
                                <td class="py-2 px-3 font-mono text-slate-500">
                                    {{ m.yearDecimal }}
                                </td>
                                <td class="py-2 px-3 font-mono">
                                    {{ formatUSD(m.startModal) }}
                                </td>
                                <td class="py-2 px-3 font-mono text-slate-600 dark:text-slate-300">
                                    {{ formatUSD(m.profit) }}
                                </td>
                                <td class="py-2 px-3 font-mono font-semibold text-emerald-600 dark:text-emerald-400">
                                    {{ formatUSD(m.takenUSD) }}
                                </td>
                                <td class="py-2 px-3 font-mono text-emerald-700 dark:text-emerald-300">
                                    {{ formatIDR(m.takenIDR) }}
                                </td>
                                <td class="py-2 px-3 font-mono text-amber-600 dark:text-amber-400">
                                    {{ formatUSD(m.masterFeeUSD) }}
                                </td>
                                <td class="py-2 px-3 font-mono text-purple-600 dark:text-purple-400">
                                    {{ formatUSD(m.compoundingUSD) }}
                                </td>
                                <td class="py-2 px-3 font-mono font-bold text-slate-900 dark:text-white">
                                    {{ formatUSD(m.endModal) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- CTA WhatsApp Konsultasi Resmi -->
                <div class="mt-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-xl bg-gradient-to-r from-emerald-50 to-teal-50 p-4 dark:from-emerald-950/40 dark:to-teal-950/30 border border-emerald-200 dark:border-emerald-800/60">
                    <div>
                        <h4 class="text-xs sm:text-sm font-bold text-emerald-900 dark:text-emerald-200 flex items-center gap-1.5">
                            <MessageCircle class="h-4 w-4 text-emerald-600" />
                            Ingin Memulai Portofolio Copytrade Berdasarkan Simulasi Ini?
                        </h4>
                        <p class="text-xs text-emerald-700/90 dark:text-emerald-300/80 mt-0.5">
                            Konsultasikan alokasi modal dan tahapan registrasi akun MT4 resmi langsung dengan Pak Yazid.
                        </p>
                    </div>
                    <a
                        :href="whatsappConsultUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-emerald-500 transition-colors cursor-pointer min-h-[38px]"
                    >
                        <MessageCircle class="h-4 w-4" />
                        Konsultasi via WhatsApp ({{ presets.contact.phone }})
                    </a>
                </div>
            </div>
        </div>
    </InvestorLayout>
</template>
