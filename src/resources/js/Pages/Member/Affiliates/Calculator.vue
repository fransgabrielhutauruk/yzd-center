<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import MemberLayout from '@/Layouts/MemberLayout.vue';
import AppPageHeader from '@/Components/Common/AppPageHeader.vue';
import AppButton from '@/Components/Common/AppButton.vue';
import AppCurrencyInput from '@/Components/Common/AppCurrencyInput.vue';
import {
    Calculator,
    PlusCircle,
    Trash2,
    RotateCcw,
    Sliders,
    ChevronDown,
    ChevronUp,
    TrendingUp,
    DollarSign,
    Users,
    Receipt,
    Percent,
    AlertCircle,
    Info,
    CheckCircle2,
    ArrowUpRight,
} from 'lucide-vue-next';
import { toast } from 'vue-sonner';
import { determineTier, simulateAll } from '@/lib/affiliateCommission';

const props = defineProps({
    tiers: {
        type: Array,
        required: true,
    },
    presets: {
        type: Object,
        required: true,
    },
});

// Breadcrumbs navigasi
const breadcrumbs = [
    { label: 'Program Kemitraan' },
    { label: 'Kalkulator Komisi' },
];

// Parameter Utama Kalkulator
const monthlyProfitRate = ref(Number(props.presets?.default_profit_rate ?? 5.0));
const exchangeRate = ref(Number(props.presets?.default_exchange_rate ?? 16796));
const showAdvancedSettings = ref(false);

// Daftar Investor Simulasi
let nextInvestorId = 2;
const investors = ref([
    {
        id: 1,
        name: 'Investor 1',
        modalIdr: Number(props.presets?.default_modal_idr ?? 100_000_000),
    },
]);

const maxInvestors = computed(() => props.presets?.max_investors ?? null);

const addInvestor = () => {
    if (maxInvestors.value && investors.value.length >= maxInvestors.value) {
        toast.error(`Maksimal ${maxInvestors.value} investor dalam satu simulasi.`);
        return;
    }
    const currentNum = investors.value.length + 1;
    investors.value.push({
        id: nextInvestorId++,
        name: `Investor ${currentNum}`,
        modalIdr: Number(props.presets?.default_modal_idr ?? 100_000_000),
    });
};

const removeInvestor = (index) => {
    if (investors.value.length <= 1) {
        toast.info('Minimal harus ada 1 investor dalam simulasi.');
        return;
    }
    investors.value.splice(index, 1);
};

const applyLiveRate = () => {
    exchangeRate.value = Number(props.presets?.default_exchange_rate ?? 16796);
    toast.info('Kurs acuan dikembalikan ke nilai pasar terkini.');
};

const resetCalculator = () => {
    monthlyProfitRate.value = Number(props.presets?.default_profit_rate ?? 5.0);
    exchangeRate.value = Number(props.presets?.default_exchange_rate ?? 16796);
    investors.value = [
        {
            id: 1,
            name: 'Investor 1',
            modalIdr: Number(props.presets?.default_modal_idr ?? 100_000_000),
        },
    ];
    toast.info('Kalkulator dikembalikan ke nilai acuan default (1 Investor Rp100 Juta, Profit 5%).');
};

// Formatting Currency
const formatUSD = (val) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(val || 0);
};

const formatIDR = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(val || 0);
};

// Simulasi Komisi Dinamis
const simulation = computed(() => {
    return simulateAll(investors.value, monthlyProfitRate.value, props.tiers, {
        wdPct: Number(props.presets?.wd_pct ?? 60),
        feePct: Number(props.presets?.master_fee_pct ?? 30),
        months: Number(props.presets?.months ?? 3),
    });
});

const activeTierSet = computed(() => new Set(simulation.value.activeTierLevels));

// Total Modal Terkumpul
const totalInitialModalIdr = computed(() => {
    return investors.value.reduce((sum, inv) => sum + (Number(inv.modalIdr) || 0), 0);
});

// Helper badge tier
const getTierBadgeClass = (tierLevel) => {
    switch (Number(tierLevel)) {
        case 1:
            return 'bg-sky-500/10 text-sky-700 dark:text-sky-300 border-sky-500/30';
        case 2:
            return 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border-emerald-500/30';
        case 3:
            return 'bg-purple-500/10 text-purple-700 dark:text-purple-300 border-purple-500/30';
        case 4:
        default:
            return 'bg-amber-500/10 text-amber-700 dark:text-amber-300 border-amber-500/30';
    }
};

// Helper teks rentang USD untuk panel samping
const getTierUsdRange = (tier) => {
    const kurs = Number(exchangeRate.value) || 16796;
    if (tier.min_idr === null || tier.min_idr === 0) {
        const maxUsd = Math.round(Number(tier.max_idr) / kurs);
        return `< $${maxUsd.toLocaleString('en-US')}`;
    }
    if (tier.max_idr === null) {
        const minUsd = Math.round(Number(tier.min_idr) / kurs);
        return `> $${minUsd.toLocaleString('en-US')}`;
    }
    const minUsd = Math.round(Number(tier.min_idr) / kurs);
    const maxUsd = Math.round(Number(tier.max_idr) / kurs);
    return `$${minUsd.toLocaleString('en-US')} – $${maxUsd.toLocaleString('en-US')}`;
};
</script>

<template>
    <Head title="Kalkulator Komisi Afiliator" />

    <MemberLayout>
        <div class="space-y-6">
            <!-- Header Halaman -->
            <AppPageHeader
                title="Kalkulator Komisi Afiliator"
                subtitle="Simulasikan estimasi perolehan komisi kemitraan selama 3 bulan/periode pertama berdasarkan modal riil investor Anda."
                :breadcrumbs="breadcrumbs"
            >
                <template #actions>
                    <div class="flex items-center gap-2">
                        <AppButton
                            type="custom"
                            variant="outline"
                            size="sm"
                            :icon="RotateCcw"
                            label="Mulai Ulang"
                            @click="resetCalculator"
                        />
                    </div>
                </template>
            </AppPageHeader>

            <!-- Layout Grid Utama (Desktop: 2 Kolom Kalkulator + 1 Kolom Panel Tier) -->
            <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-3">
                <!-- Kolom Kiri: Input Form & Hasil Simulasi (2 Kolom) -->
                <div class="space-y-6 lg:col-span-2">
                    <!-- KARTU 1: Parameter Simulasi Global -->
                    <div class="rounded-2xl border border-border bg-card p-5 shadow-xs sm:p-6 space-y-5">
                        <div class="flex items-center justify-between border-b border-border/70 pb-3">
                            <div class="flex items-center gap-2">
                                <Sliders class="h-4 w-4 text-primary" />
                                <h3 class="text-sm font-bold text-foreground sm:text-base">
                                    Parameter Pasar & Asumsi Profit
                                </h3>
                            </div>
                            <span class="rounded-full border border-primary/20 bg-primary/10 px-2.5 py-0.5 text-xs font-semibold text-primary">
                                Jendela 3 Bulan
                            </span>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <!-- Input Estimasi Profit Bulanan -->
                            <div class="space-y-1.5">
                                <label class="flex items-center justify-between text-xs font-semibold text-foreground">
                                    <span>Estimasi Profit Akun Trading (% / bln)</span>
                                    <span class="font-mono text-primary font-bold">{{ monthlyProfitRate }}%</span>
                                </label>
                                <div class="relative">
                                    <input
                                        v-model.number="monthlyProfitRate"
                                        type="number"
                                        min="0.1"
                                        max="100"
                                        step="0.1"
                                        class="h-10 w-full rounded-xl border border-input bg-background pl-3 pr-8 font-mono text-sm font-bold text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                                    />
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold text-muted-foreground">%</span>
                                </div>
                                <p class="text-[11px] text-muted-foreground">
                                    Acuan rata-rata kinerja adalah 5% per bulan.
                                </p>
                            </div>

                            <!-- Ketentuan Tetap Sistem (Read-only Info) -->
                            <div class="space-y-1.5">
                                <label class="block text-xs font-semibold text-foreground">
                                    Ketentuan Sistem Master & WD
                                </label>
                                <div class="grid grid-cols-2 gap-2 text-xs font-mono">
                                    <div class="rounded-xl border border-border bg-muted/30 p-2.5">
                                        <span class="block text-[10px] font-sans text-muted-foreground">Fee Master:</span>
                                        <strong class="text-foreground text-sm">{{ presets.master_fee_pct }}%</strong>
                                    </div>
                                    <div class="rounded-xl border border-border bg-muted/30 p-2.5">
                                        <span class="block text-[10px] font-sans text-muted-foreground">Penarikan (WD):</span>
                                        <strong class="text-foreground text-sm">{{ presets.wd_pct }}% / bln</strong>
                                    </div>
                                </div>
                                <p class="text-[11px] text-muted-foreground">
                                    Sisa 40% profit otomatis dikompon ke modal bulan berikutnya.
                                </p>
                            </div>
                        </div>

                        <!-- Pengaturan Lanjutan (Kurs USD/IDR) -->
                        <div class="border-t border-border/70 pt-3">
                            <button
                                type="button"
                                @click="showAdvancedSettings = !showAdvancedSettings"
                                class="flex min-h-[36px] cursor-pointer items-center gap-1.5 text-xs font-semibold text-muted-foreground transition-colors hover:text-foreground"
                            >
                                <Sliders class="h-3.5 w-3.5" />
                                <span>Pengaturan Lanjutan: Kurs Acuan USD/IDR</span>
                                <ChevronDown v-if="!showAdvancedSettings" class="h-3.5 w-3.5" />
                                <ChevronUp v-else class="h-3.5 w-3.5" />
                            </button>

                            <div v-show="showAdvancedSettings" class="mt-3 rounded-xl border border-border bg-muted/20 p-3.5 space-y-2">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="max-w-xs w-full">
                                        <label class="mb-1 block text-xs font-medium text-foreground">
                                            Kurs USD/IDR (Rp per 1 USD)
                                        </label>
                                        <AppCurrencyInput
                                            v-model="exchangeRate"
                                            currency="IDR"
                                            :min="1000"
                                            placeholder="16.500"
                                        />
                                    </div>
                                    <div v-if="Number(exchangeRate) !== Number(presets.default_exchange_rate)" class="self-end sm:self-center">
                                        <AppButton
                                            type="custom"
                                            variant="outline"
                                            size="xs"
                                            label="Gunakan Kurs Terkini"
                                            @click="applyLiveRate"
                                        />
                                    </div>
                                </div>
                                <p class="text-[10px] text-muted-foreground">
                                    Kurs pasar: Rp {{ Number(presets.default_exchange_rate).toLocaleString('id-ID') }}
                                    <span v-if="presets.exchange_rate_meta?.date"> • Terakhir diperbarui: {{ presets.exchange_rate_meta.date }}</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- KARTU 2: Daftar Investor Simulasi -->
                    <div class="rounded-2xl border border-border bg-card p-5 shadow-xs sm:p-6 space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-border/70 pb-3">
                            <div class="flex items-center gap-2">
                                <Users class="h-4 w-4 text-primary" />
                                <h3 class="text-sm font-bold text-foreground sm:text-base">
                                    Daftar Investor Ilustrasi
                                </h3>
                                <span class="rounded-full border border-border bg-muted px-2 py-0.5 font-mono text-xs text-muted-foreground">
                                    {{ maxInvestors ? `${investors.length} / ${maxInvestors}` : `${investors.length} Investor` }}
                                </span>
                            </div>

                            <AppButton
                                type="custom"
                                variant="outline"
                                size="xs"
                                :icon="PlusCircle"
                                label="Tambah Investor"
                                :disabled="maxInvestors ? investors.length >= maxInvestors : false"
                                @click="addInvestor"
                            />
                        </div>

                        <!-- Baris Input Setiap Investor -->
                        <div class="space-y-3">
                            <div
                                v-for="(inv, idx) in investors"
                                :key="inv.id"
                                class="flex flex-col gap-3 rounded-xl border border-border bg-muted/10 p-3 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div class="flex min-w-0 flex-1 flex-col gap-2 sm:flex-row sm:items-center">
                                    <!-- Label Nama Investor -->
                                    <div class="w-full sm:w-32 shrink-0">
                                        <span class="block text-xs font-bold text-foreground">
                                            {{ inv.name || `Investor ${idx + 1}` }}
                                        </span>
                                        <span class="text-[10px] text-muted-foreground">
                                            Downline Langsung
                                        </span>
                                    </div>

                                    <!-- Input Modal IDR -->
                                    <div class="flex-1 min-w-[180px]">
                                        <AppCurrencyInput
                                            v-model="inv.modalIdr"
                                            currency="IDR"
                                            :min="0"
                                            placeholder="Nominal modal dalam Rupiah"
                                        />
                                    </div>

                                    <!-- Setara USD & Badge Tier -->
                                    <div class="flex shrink-0 items-center gap-2 self-start sm:self-center">
                                        <span class="font-mono text-xs text-muted-foreground hidden sm:inline">
                                            &approx; {{ formatUSD((Number(inv.modalIdr) || 0) / exchangeRate) }}
                                        </span>
                                        <span
                                            :class="[
                                                'rounded-full border px-2.5 py-0.5 text-xs font-bold whitespace-nowrap',
                                                getTierBadgeClass(determineTier(inv.modalIdr, props.tiers).tier_level),
                                            ]"
                                        >
                                            Tier {{ determineTier(inv.modalIdr, props.tiers).tier_level }} ({{ determineTier(inv.modalIdr, props.tiers).persentase_tier }}%)
                                        </span>
                                    </div>
                                </div>

                                <!-- Tombol Hapus Baris -->
                                <button
                                    v-if="investors.length > 1"
                                    type="button"
                                    @click="removeInvestor(idx)"
                                    class="flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center rounded-lg text-muted-foreground transition-colors hover:bg-rose-500/10 hover:text-rose-600 self-end sm:self-center"
                                    title="Hapus baris investor"
                                    aria-label="Hapus investor"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </div>
                        </div>

                        <!-- Ringkasan Total Modal Terkumpul -->
                        <div class="flex flex-wrap items-center justify-between border-t border-border/70 pt-3 text-xs">
                            <span class="text-muted-foreground">Total Modal Investor yang Disimulasikan:</span>
                            <div class="text-right">
                                <span class="font-mono font-bold text-foreground sm:text-sm">
                                    {{ formatIDR(totalInitialModalIdr) }}
                                </span>
                                <span class="text-muted-foreground font-mono text-[11px] block sm:inline sm:ml-1.5">
                                    ({{ formatUSD(totalInitialModalIdr / exchangeRate) }})
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- KARTU 3: HASIL ESTIMASI KOMISI -->
                    <div class="space-y-6">
                        <!-- Hero Card: Total Komisi 3 Bulan -->
                        <div class="relative overflow-hidden rounded-2xl border border-primary/30 bg-gradient-to-br from-primary/10 via-primary/5 to-transparent p-5 sm:p-6 shadow-xs">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <DollarSign class="h-5 w-5 text-primary" />
                                        <span class="text-xs font-bold uppercase tracking-wider text-primary">
                                            Estimasi Total Komisi Afiliator (3 Bulan)
                                        </span>
                                    </div>
                                    <p class="text-2xl font-black font-mono text-foreground sm:text-3xl">
                                        {{ formatIDR(simulation.grandTotalKomisi) }}
                                    </p>
                                    <p class="text-xs font-mono font-semibold text-muted-foreground">
                                        Setara {{ formatUSD(simulation.grandTotalKomisi / exchangeRate) }} (Kurs Rp {{ Number(exchangeRate).toLocaleString('id-ID') }})
                                    </p>
                                </div>

                                <div class="grid grid-cols-2 gap-2 text-xs font-mono sm:text-right">
                                    <div class="rounded-xl border border-border bg-card/60 p-2.5">
                                        <span class="block text-[10px] font-sans text-muted-foreground">Total Profit:</span>
                                        <strong class="text-foreground">{{ formatIDR(simulation.grandTotalProfit) }}</strong>
                                    </div>
                                    <div class="rounded-xl border border-border bg-card/60 p-2.5">
                                        <span class="block text-[10px] font-sans text-muted-foreground">Total Fee Master:</span>
                                        <strong class="text-foreground">{{ formatIDR(simulation.grandTotalFeeMaster) }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tabel Akumulasi per Bulan -->
                        <div class="rounded-2xl border border-border bg-card p-5 shadow-xs space-y-3">
                            <div class="flex items-center gap-2 border-b border-border/70 pb-3">
                                <Receipt class="h-4 w-4 text-primary" />
                                <h4 class="text-xs font-bold text-foreground sm:text-sm">
                                    Proyeksi Akumulasi Arus Kas Bulanan (Seluruh Investor)
                                </h4>
                            </div>

                            <div class="overflow-x-auto rounded-xl border border-border/70">
                                <table class="w-full text-left text-xs">
                                    <thead>
                                        <tr class="border-b border-border bg-muted/40 text-[11px] text-muted-foreground">
                                            <th class="px-3 py-2.5 font-semibold">Periode Bulan</th>
                                            <th class="px-3 py-2.5 font-semibold text-right font-mono">Total Modal</th>
                                            <th class="px-3 py-2.5 font-semibold text-right font-mono">Profit ({{ monthlyProfitRate }}%)</th>
                                            <th class="px-3 py-2.5 font-semibold text-right font-mono">Fee Master ({{ presets.master_fee_pct }}%)</th>
                                            <th class="px-3 py-2.5 font-semibold text-right font-mono text-primary">Komisi Afiliator</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-border/60 bg-card">
                                        <tr v-for="m in simulation.monthlyTotals" :key="m.bulan" class="hover:bg-muted/30 transition-colors">
                                            <td class="px-3 py-3 font-bold text-foreground whitespace-nowrap">
                                                Bulan {{ m.bulan }}
                                            </td>
                                            <td class="px-3 py-3 text-right font-mono whitespace-nowrap">
                                                {{ formatIDR(m.totalModal) }}
                                            </td>
                                            <td class="px-3 py-3 text-right font-mono text-emerald-600 dark:text-emerald-400 font-semibold whitespace-nowrap">
                                                +{{ formatIDR(m.totalProfit) }}
                                            </td>
                                            <td class="px-3 py-3 text-right font-mono whitespace-nowrap">
                                                {{ formatIDR(m.totalFeeMaster) }}
                                            </td>
                                            <td class="px-3 py-3 text-right font-mono font-bold text-primary whitespace-nowrap">
                                                {{ formatIDR(m.totalKomisi) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr class="border-t-2 border-border bg-muted/20 font-bold text-foreground">
                                            <td class="px-3 py-3 font-semibold" colspan="4">
                                                Total Komisi 3 Bulan
                                            </td>
                                            <td class="px-3 py-3 text-right font-mono text-primary text-sm whitespace-nowrap">
                                                {{ formatIDR(simulation.grandTotalKomisi) }}
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- Rincian per Investor -->
                        <div class="rounded-2xl border border-border bg-card p-5 shadow-xs space-y-3">
                            <div class="flex items-center gap-2 border-b border-border/70 pb-3">
                                <Users class="h-4 w-4 text-primary" />
                                <h4 class="text-xs font-bold text-foreground sm:text-sm">
                                    Rincian Estimasi Komisi per Investor
                                </h4>
                            </div>

                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div
                                    v-for="inv in simulation.investorResults"
                                    :key="inv.id"
                                    class="rounded-xl border border-border bg-muted/10 p-3.5 space-y-2.5"
                                >
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-xs text-foreground">{{ inv.name }}</span>
                                        <span
                                            :class="[
                                                'rounded-full border px-2 py-0.5 text-[10px] font-bold',
                                                getTierBadgeClass(inv.tier.tier_level),
                                            ]"
                                        >
                                            Tier {{ inv.tier.tier_level }} ({{ inv.tier.persentase_tier }}%)
                                        </span>
                                    </div>

                                    <div class="space-y-1 text-xs font-mono">
                                        <div class="flex justify-between text-muted-foreground text-[11px]">
                                            <span>Modal Awal:</span>
                                            <strong class="text-foreground">{{ formatIDR(inv.initialModal) }}</strong>
                                        </div>
                                        <div class="flex justify-between text-[11px] text-muted-foreground border-t border-border/50 pt-1">
                                            <span>Komisi Bulan 1:</span>
                                            <span>{{ formatIDR(inv.rows[0]?.komisi || 0) }}</span>
                                        </div>
                                        <div class="flex justify-between text-[11px] text-muted-foreground">
                                            <span>Komisi Bulan 2:</span>
                                            <span>{{ formatIDR(inv.rows[1]?.komisi || 0) }}</span>
                                        </div>
                                        <div class="flex justify-between text-[11px] text-muted-foreground">
                                            <span>Komisi Bulan 3:</span>
                                            <span>{{ formatIDR(inv.rows[2]?.komisi || 0) }}</span>
                                        </div>
                                        <div class="flex justify-between border-t border-border/70 pt-1.5 font-bold text-primary">
                                            <span>Subtotal Komisi:</span>
                                            <span>{{ formatIDR(inv.total) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Catatan Penting & Disclaimer Bisnis -->
                        <div class="rounded-xl border border-amber-500/30 bg-amber-500/5 p-4 text-xs text-amber-900 dark:text-amber-200 space-y-2">
                            <div class="flex items-center gap-2 font-bold">
                                <Info class="h-4 w-4 text-amber-600 dark:text-amber-400 shrink-0" />
                                <span>Catatan Penting Perhitungan & Disclaimer:</span>
                            </div>
                            <ul class="list-disc pl-5 space-y-1 text-[11px] leading-relaxed text-muted-foreground">
                                <li>
                                    <strong>Ketentuan Jendela Komisi:</strong> Hak komisi afiliator berlaku selama 3 periode laporan bulanan pertama terhitung sejak investor mulai investasi.
                                </li>
                                <li>
                                    <strong>Siklus Periode Awal:</strong> Apabila investor mulai copy trade pada tanggal 16–31, periode pertama mencakup &plusmn; 2 bulan kalender. Kalkulator di atas menyederhanakan tiap periode menjadi satu bulan penuh.
                                </li>
                                <li>
                                    <strong>Ilustrasi Bukan Jaminan:</strong> Hasil perhitungan ini merupakan simulasi matematis berbasis estimasi profit  dan bukan merupakan jaminan keuntungan pasti di masa mendatang.
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Kolom Kanan: Panel Referensi "Tier Komisi" (Sticky Ringan) -->
                <div class="space-y-4 lg:col-span-1 lg:sticky lg:top-6">
                    <div class="rounded-2xl border border-border bg-card p-5 shadow-xs space-y-4">
                        <div class="flex items-center gap-2 border-b border-border/70 pb-3">
                            <Percent class="h-4 w-4 text-primary" />
                            <div>
                                <h3 class="text-sm font-bold text-foreground">
                                    Skema 4-Tier Komisi
                                </h3>
                                <p class="text-[11px] text-muted-foreground">
                                    Berdasarkan modal awal riil investor
                                </p>
                            </div>
                        </div>

                        <!-- List 4 Baris Tier -->
                        <div class="space-y-3">
                            <div
                                v-for="t in tiers"
                                :key="t.tier_level"
                                :class="[
                                    'rounded-xl border p-3.5 transition-all space-y-2',
                                    activeTierSet.has(t.tier_level)
                                        ? 'border-primary ring-1 ring-primary/40 bg-primary/5 shadow-xs'
                                        : 'border-border bg-muted/10',
                                ]"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span
                                            :class="[
                                                'rounded-full border px-2 py-0.5 text-xs font-bold',
                                                getTierBadgeClass(t.tier_level),
                                            ]"
                                        >
                                            {{ t.name }}
                                        </span>
                                        <span class="font-mono text-xs font-bold text-foreground">
                                            Komisi {{ t.persentase_tier }}%
                                        </span>
                                    </div>

                                    <span
                                        v-if="activeTierSet.has(t.tier_level)"
                                        class="inline-flex items-center gap-1 rounded-full border border-primary/30 bg-primary/10 px-2 py-0.5 text-[10px] font-bold text-primary"
                                    >
                                        <CheckCircle2 class="h-3 w-3" />
                                        <span>Dipakai</span>
                                    </span>
                                </div>

                                <div class="space-y-0.5 text-xs font-mono">
                                    <div class="text-foreground font-semibold">
                                        {{ t.range_label }}
                                    </div>
                                    <div class="text-[10px] text-muted-foreground">
                                        Setara: {{ getTierUsdRange(t) }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Info Formula Dasar -->
                        <div class="rounded-xl border border-border bg-muted/20 p-3 text-[11px] text-muted-foreground space-y-1">
                            <span class="font-bold text-foreground block">Rumus Bagi Hasil:</span>
                            <p class="leading-relaxed">
                                Komisi = Persentase Tier &times; (Fee Master 30% &times; Profit Bulanan Investor).
                            </p>
                        </div>

                        <!-- Navigasi ke Halaman Jaringan Kemitraan -->
                        <div class="border-t border-border/70 pt-2">
                            <Link :href="route('member.affiliates.index')" class="w-full block">
                                <AppButton
                                    type="custom"
                                    variant="outline"
                                    size="sm"
                                    :icon="ArrowUpRight"
                                    label="Lihat Jaringan Saya"
                                    class="w-full justify-center"
                                />
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MemberLayout>
</template>
