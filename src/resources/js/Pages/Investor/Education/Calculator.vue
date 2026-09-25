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

// Variabel Input Utama (Sesuai Referensi CSV Tab "5% (Conservativ)")
const initialCapital = ref(props.presets.default_initial_capital ?? 100);
const monthlyProfitRate = ref(props.presets.default_monthly_profit_rate ?? 5.0);
const durationYears = ref(props.presets.default_duration_years ?? 5);
const exchangeRate = ref(props.presets.default_exchange_rate ?? 16796);

// Tampilan Tab: 'yearly' (Tinjauan Tahunan) vs 'monthly' (Rincian Bulanan)
const activeViewTab = ref('yearly');

// Filter Tahun pada Rincian Bulanan (misal: 'all', '1', '2', '3', '4', '5')
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
const STORAGE_KEY = 'yzd_projection_calculator_vars';

onMounted(() => {
    try {
        const saved = localStorage.getItem(STORAGE_KEY);
        if (saved) {
            const parsed = JSON.parse(saved);
            if (parsed.initialCapital !== undefined) initialCapital.value = Number(parsed.initialCapital);
            if (parsed.monthlyProfitRate !== undefined) monthlyProfitRate.value = Number(parsed.monthlyProfitRate);
            if (parsed.durationYears !== undefined) durationYears.value = Number(parsed.durationYears);
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
                exchangeRate: exchangeRate.value,
                hasCustomExchangeRate: isCustomRate,
            })
        );
        toast.success('Variabel Proyeksi Berhasil Disimpan', {
            description: `Modal Awal: ${formatUSD(initialCapital.value)} | Profit: ${monthlyProfitRate.value}%/bulan | Durasi: ${durationYears.value} Tahun`,
        });
    } catch {
        toast.error('Gagal menyimpan variabel pada peramban.');
    }
};

const resetToDefaults = () => {
    initialCapital.value = props.presets.default_initial_capital ?? 100;
    monthlyProfitRate.value = props.presets.default_monthly_profit_rate ?? 5.0;
    durationYears.value = props.presets.default_duration_years ?? 5;
    exchangeRate.value = Number(props.presets.default_exchange_rate ?? 16796);
    try {
        localStorage.removeItem(STORAGE_KEY);
    } catch {
        // Ignore
    }
    toast.info('Variabel dikembalikan ke standar awal ($100, 5%, 5 Tahun, Kurs Hari Ini).');
};

// -----------------------------------------------------------------------------
// KALKULASI PROYEKSI MATEMATIS (Formula CSV Tab 5% Conservativ)
// -----------------------------------------------------------------------------
const simulationData = computed(() => {
    const initModal = Math.max(1, Number(initialCapital.value) || 100);
    const rate = Math.max(0.1, Number(monthlyProfitRate.value) || 5.0) / 100;
    const totalMonths = Math.min(120, Math.max(12, (Number(durationYears.value) || 5) * 12));
    const kurs = Number(exchangeRate.value) || 16796;

    let currentModal = initModal;
    let totalGrossProfit = 0;
    let totalTakenUSD = 0;
    let totalCompoundedUSD = 0;
    let totalMasterFeeUSD = 0;

    const monthlyList = [];
    const yearlyMap = {};

    for (let m = 1; m <= totalMonths; m++) {
        const yearNumber = Math.floor((m - 1) / 12) + 1;
        const yearDecimal = (m / 12).toFixed(1);

        const startModal = currentModal;
        const profit = startModal * rate;
        const takenUSD = profit * 0.30; // 30% Hak Investor (Withdrawal)
        const takenIDR = takenUSD * kurs;
        const masterFeeUSD = profit * 0.30; // 30% Master Trader Fee
        const compoundingUSD = profit * 0.40; // 40% Compounding ke Modal
        const endModal = startModal + compoundingUSD;

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
            masterFeeUSD,
            compoundingUSD,
            endModal,
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
            };
        }
        yearlyMap[yearNumber].totalProfit += profit;
        yearlyMap[yearNumber].totalTakenUSD += takenUSD;
        yearlyMap[yearNumber].totalTakenIDR += takenIDR;
        yearlyMap[yearNumber].totalMasterFeeUSD += masterFeeUSD;
        yearlyMap[yearNumber].totalCompoundingUSD += compoundingUSD;
        yearlyMap[yearNumber].endModal = endModal;

        // Modal berlanjut ke bulan berikutnya
        currentModal = endModal;
    }

    const yearlyList = Object.values(yearlyMap);

    return {
        initialCapital: initModal,
        endModal: currentModal,
        totalGrossProfit,
        totalTakenUSD,
        totalTakenIDR: totalTakenUSD * kurs,
        totalMasterFeeUSD,
        totalCompoundedUSD,
        overallGrowthPct: ((currentModal - initModal) / initModal) * 100,
        monthlyList,
        yearlyList,
        totalMonths,
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

// Tautan Konsultasi WhatsApp
const whatsappConsultUrl = computed(() => {
    const init = formatUSD(initialCapital.value);
    const end = formatUSD(simulationData.value.endModal);
    const rate = monthlyProfitRate.value;
    const dur = durationYears.value;
    const msg = `Halo Pak Yazid, saya telah mencoba simulasi kalkulator proyeksi YZD Center dengan modal awal ${init} dan target profit ${rate}%/bulan selama ${dur} tahun (estimasi akhir ${end}). Saya ingin konsultasi lebih lanjut.`;
    return `https://wa.me/6282388986189?text=${encodeURIComponent(msg)}`;
});
</script>

<template>
    <InvestorLayout title="Kalkulator Proyeksi Finansial">
        <div class="space-y-6">
            <!-- Header Banner -->
            <div class="rounded-2xl bg-gradient-to-r from-slate-900 via-indigo-950 to-blue-900 p-6 sm:p-8 text-white shadow-md">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="max-w-2xl">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 text-xs font-medium backdrop-blur-sm">
                            <Calculator class="h-3.5 w-3.5 text-blue-300" />
                            CRM Proyeksi Finansial — Konservatif 5% & Compounding
                        </span>
                        <h1 class="mt-3 text-2xl sm:text-3xl font-bold tracking-tight">
                            Kalkulator Proyeksi Keuntungan Bulanan
                        </h1>
                        <p class="mt-2 text-sm text-blue-100/90 leading-relaxed">
                            Hitung estimasi pertumbuhan portofolio modal hingga {{ durationYears }} tahun ke depan dengan formula baku:
                            <strong>40% Compounding</strong> bergulir menambah ketahanan modal, <strong>30% Hak Investor (Withdrawal)</strong>, dan <strong>30% Master Fee</strong>.
                        </p>
                    </div>

                    <!-- Tombol Aksi Simpan & Reset -->
                    <div class="flex items-center gap-2 self-start sm:self-center">
                        <Button
                            variant="secondary"
                            size="sm"
                            class="gap-1.5 bg-white text-slate-900 hover:bg-slate-100"
                            @click="saveVariables"
                        >
                            <Save class="h-4 w-4" />
                            Simpan Variabel
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            class="gap-1.5 border-white/20 text-white hover:bg-white/10"
                            @click="resetToDefaults"
                        >
                            <RotateCcw class="h-4 w-4" />
                            Reset
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Panel Pengaturan Variabel Proyeksi -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
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
                            step="10"
                            class="w-full rounded-lg border border-slate-300 py-2 pl-7 pr-3 text-sm font-bold text-slate-900 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-white font-mono"
                        />
                    </div>
                    <!-- Preset Cepat -->
                    <div class="mt-2 flex flex-wrap gap-1">
                        <button
                            v-for="preset in [100, 500, 1000, 5000, 10000]"
                            :key="preset"
                            type="button"
                            @click="initialCapital = preset"
                            :class="[
                                'rounded px-1.5 py-0.5 text-[10px] font-medium transition-colors',
                                initialCapital === preset
                                    ? 'bg-primary text-primary-foreground'
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
                            class="w-full rounded-lg border border-slate-300 py-2 pl-3 pr-8 text-sm font-bold text-slate-900 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-white font-mono"
                        />
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">%</span>
                    </div>
                    <!-- Preset Cepat -->
                    <div class="mt-2 flex flex-wrap gap-1">
                        <button
                            v-for="r in [3, 5, 8, 10]"
                            :key="r"
                            type="button"
                            @click="monthlyProfitRate = r"
                            :class="[
                                'rounded px-2 py-0.5 text-[10px] font-medium transition-colors',
                                monthlyProfitRate === r
                                    ? 'bg-primary text-primary-foreground'
                                    : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300',
                            ]"
                        >
                            {{ r }}% {{ r === 5 ? '(Baku)' : '' }}
                        </button>
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
                            max="10"
                            step="1"
                            class="w-full rounded-lg border border-slate-300 py-2 pl-3 pr-14 text-sm font-bold text-slate-900 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-white font-mono"
                        />
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">Tahun</span>
                    </div>
                    <!-- Preset Cepat -->
                    <div class="mt-2 flex flex-wrap gap-1">
                        <button
                            v-for="y in [1, 2, 3, 5]"
                            :key="y"
                            type="button"
                            @click="durationYears = y"
                            :class="[
                                'rounded px-2 py-0.5 text-[10px] font-medium transition-colors',
                                durationYears === y
                                    ? 'bg-primary text-primary-foreground'
                                    : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300',
                            ]"
                        >
                            {{ y }} Thn {{ y === 5 ? '(Default)' : '' }}
                        </button>
                    </div>
                </div>

                <!-- 4. Kurs Acuan Rupiah (Ambil dari API Frankfurter) -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                            Kurs Acuan (Rp / USD)
                        </label>
                        <button
                            v-if="Number(exchangeRate) !== Number(presets.default_exchange_rate)"
                            type="button"
                            @click="applyLiveRate"
                            class="text-[10px] text-primary hover:underline font-semibold"
                        >
                            Pakai Kurs Hari Ini
                        </button>
                    </div>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">Rp</span>
                        <input
                            v-model.number="exchangeRate"
                            type="number"
                            min="10000"
                            step="50"
                            class="w-full rounded-lg border border-slate-300 py-2 pl-8 pr-3 text-sm font-bold text-slate-900 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-white font-mono"
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
                            {{ presets.exchange_rate_meta?.is_live ? 'Live Pasar Tengah (Frankfurter API)' : 'Kurs Cadangan Sistem' }}
                            • {{ formattedRateUpdate }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Kartu Ringkasan Proyeksi Eksekutif (Summary Cards) -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- 1. Modal Awal -->
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400 block">
                        Modal Awal
                    </span>
                    <p class="mt-1 text-lg sm:text-xl font-extrabold text-slate-900 dark:text-white font-mono">
                        {{ formatUSD(simulationData.initialCapital) }}
                    </p>
                    <span class="text-[11px] text-slate-400 block mt-0.5">
                        {{ formatIDR(simulationData.initialCapital * exchangeRate) }}
                    </span>
                </div>

                <!-- 2. Estimasi Modal Akhir -->
                <div class="rounded-xl border border-blue-200 bg-blue-50/50 p-4 shadow-sm dark:border-blue-900/50 dark:bg-blue-950/30">
                    <span class="text-xs font-medium text-blue-800 dark:text-blue-300 block">
                        Estimasi Modal Akhir (Compounded)
                    </span>
                    <p class="mt-1 text-lg sm:text-xl font-extrabold text-blue-700 dark:text-blue-400 font-mono">
                        {{ formatUSD(simulationData.endModal) }}
                    </p>
                    <span class="text-[11px] text-blue-600/80 dark:text-blue-400/80 block mt-0.5">
                        Tumbuh +{{ simulationData.overallGrowthPct.toFixed(1) }}%
                    </span>
                </div>

                <!-- 3. Total Profit Yang Boleh Diambil (Hak Investor 30% WD) -->
                <div class="rounded-xl border border-emerald-200 bg-emerald-50/50 p-4 shadow-sm dark:border-emerald-900/50 dark:bg-emerald-950/30">
                    <span class="text-xs font-medium text-emerald-800 dark:text-emerald-300 block">
                        Hak Investor (30% WD)
                    </span>
                    <p class="mt-1 text-lg sm:text-xl font-extrabold text-emerald-700 dark:text-emerald-400 font-mono">
                        {{ formatUSD(simulationData.totalTakenUSD) }}
                    </p>
                    <span class="text-[11px] text-emerald-600/90 dark:text-emerald-400/90 block mt-0.5 font-medium">
                        {{ formatIDR(simulationData.totalTakenIDR) }}
                    </span>
                </div>

                <!-- 4. Total Compounding Tambahan Modal (40%) -->
                <div class="rounded-xl border border-purple-200 bg-purple-50/50 p-4 shadow-sm dark:border-purple-900/50 dark:bg-purple-950/30">
                    <span class="text-xs font-medium text-purple-800 dark:text-purple-300 block">
                        Compounding Modal (40%)
                    </span>
                    <p class="mt-1 text-lg sm:text-xl font-extrabold text-purple-700 dark:text-purple-400 font-mono">
                        {{ formatUSD(simulationData.totalCompoundedUSD) }}
                    </p>
                    <span class="text-[11px] text-purple-600/80 dark:text-purple-400/80 block mt-0.5">
                        Master Fee (30%): {{ formatUSD(simulationData.totalMasterFeeUSD) }}
                    </span>
                </div>
            </div>

            <!-- Banner Penjelasan Alokasi Profit Baku -->
            <div class="rounded-xl bg-slate-50 p-4 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-800 flex items-start gap-3">
                <Info class="h-5 w-5 shrink-0 text-primary mt-0.5" />
                <div class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                    <strong class="text-slate-900 dark:text-white">Alokasi Pembagian Profit Bulanan YZD Center:</strong>
                    Setiap keuntungan bulanan dialokasikan secara transparan & disiplin:
                    <span class="font-semibold text-purple-700 dark:text-purple-300">40% Compounding</span> dibiarkan bergulir otomatis memperkuat modal di broker,
                    <span class="font-semibold text-emerald-700 dark:text-emerald-300">30% Hak Investor (Withdrawal)</span> profit berkala yang boleh ditarik, dan
                    <span class="font-semibold text-amber-700 dark:text-amber-300">30% Master Fee</span> bagi hasil Master Trader.
                    *(Catatan: Fee affiliator diabaikan pada proyeksi ini).*
                </div>
            </div>

            <!-- Navigasi Tampilan Tabel: Tahunan vs Bulanan -->
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-100 pb-4 dark:border-slate-800">
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="activeViewTab = 'yearly'"
                            :class="[
                                'rounded-lg px-3.5 py-1.5 text-xs font-bold transition-all',
                                activeViewTab === 'yearly'
                                    ? 'bg-primary text-primary-foreground shadow-sm'
                                    : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300',
                            ]"
                        >
                            Tinjauan Per Tahun
                        </button>
                        <button
                            type="button"
                            @click="activeViewTab = 'monthly'"
                            :class="[
                                'rounded-lg px-3.5 py-1.5 text-xs font-bold transition-all',
                                activeViewTab === 'monthly'
                                    ? 'bg-primary text-primary-foreground shadow-sm'
                                    : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300',
                            ]"
                        >
                            Rincian Per Bulan (Bulan 1 s/d {{ simulationData.totalMonths }})
                        </button>
                    </div>

                    <!-- Filter Tahun (Jika di Tab Bulanan) -->
                    <div v-if="activeViewTab === 'monthly'" class="flex items-center gap-1.5 text-xs">
                        <span class="text-slate-500 font-medium">Filter Tahun:</span>
                        <select
                            v-model="selectedYearFilter"
                            class="rounded-md border border-slate-300 bg-white px-2 py-1 text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
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
                                <th class="py-3 px-3 font-semibold text-slate-700 dark:text-slate-200">Investor WD (30%)</th>
                                <th class="py-3 px-3 font-semibold text-slate-700 dark:text-slate-200">Investor (IDR)</th>
                                <th class="py-3 px-3 font-semibold text-slate-700 dark:text-slate-200">Master Fee (30%)</th>
                                <th class="py-3 px-3 font-semibold text-slate-700 dark:text-slate-200">Compounding (40%)</th>
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

                <!-- TAB 2: RINCIAN PER BULAN (Persis Tabel CSV) -->
                <div v-else class="overflow-x-auto max-h-[500px] overflow-y-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="sticky top-0 z-10">
                            <tr class="border-b border-slate-200 bg-slate-100 dark:border-slate-800 dark:bg-slate-800">
                                <th class="py-2.5 px-3 font-semibold text-slate-700 dark:text-slate-200">Bulan</th>
                                <th class="py-2.5 px-3 font-semibold text-slate-700 dark:text-slate-200">Tahun</th>
                                <th class="py-2.5 px-3 font-semibold text-slate-700 dark:text-slate-200">Modal</th>
                                <th class="py-2.5 px-3 font-semibold text-slate-700 dark:text-slate-200">Profit {{ monthlyProfitRate }}%</th>
                                <th class="py-2.5 px-3 font-semibold text-slate-700 dark:text-slate-200">Investor WD (30%)</th>
                                <th class="py-2.5 px-3 font-semibold text-slate-700 dark:text-slate-200">Dalam Rupiah</th>
                                <th class="py-2.5 px-3 font-semibold text-slate-700 dark:text-slate-200">Master Fee (30%)</th>
                                <th class="py-2.5 px-3 font-semibold text-slate-700 dark:text-slate-200">Compounding 40%</th>
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
                        class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-emerald-500 transition-colors"
                    >
                        <MessageCircle class="h-4 w-4" />
                        Konsultasi via WhatsApp ({{ presets.contact.phone }})
                    </a>
                </div>
            </div>
        </div>
    </InvestorLayout>
</template>
