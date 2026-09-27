<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    Calendar,
    Coins,
    DollarSign,
    TrendingUp,
    TrendingDown,
    Plus,
    Scale,
    LineChart,
    Wallet,
    Info,
    AlertCircle,
    CheckCircle2,
    Clock,
    Trash2,
    Edit3,
    ArrowUpRight,
    ArrowDownRight,
    Filter,
    ChevronRight,
} from 'lucide-vue-next';
import InvestorLayout from '@/Layouts/InvestorLayout.vue';
import { Button } from '@/Components/ui/button';
import AppModal from '@/Components/Common/AppModal.vue';
import AppConfirmDialog from '@/Components/Common/AppConfirmDialog.vue';
import { toast } from 'vue-sonner';

const props = defineProps({
    accounts: {
        type: Array,
        required: true,
    },
    selected_account_id: {
        type: Number,
        default: null,
    },
    summary: {
        type: Object,
        default: null,
    },
    filters: {
        type: Object,
        default: () => ({
            period: 'all',
            start_date: null,
            end_date: null,
        }),
    },
    exchange_rate_meta: {
        type: Object,
        default: () => ({ rate: 16796 }),
    },
    today_date: {
        type: String,
        default: '',
    },
    is_first_day_of_month: {
        type: Boolean,
        default: false,
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

// -----------------------------------------------------------------------------
// Filter & Pergantian Akun
// -----------------------------------------------------------------------------
const selectedAccountId = ref(props.selected_account_id);
const selectedPeriod = ref(props.filters.period || 'all');
const customStartDate = ref(props.filters.start_date || '');
const customEndDate = ref(props.filters.end_date || '');
const isCustomDateOpen = ref(props.filters.period === 'custom');

const changeAccount = (accId) => {
    selectedAccountId.value = accId;
    applyFilter();
};

const setPeriod = (periodKey) => {
    selectedPeriod.value = periodKey;
    if (periodKey === 'custom') {
        isCustomDateOpen.value = true;
        return;
    }
    isCustomDateOpen.value = false;
    applyFilter();
};

const applyFilter = () => {
    router.get(
        route('investor.copytrade.balance.index'),
        {
            account_id: selectedAccountId.value,
            period: selectedPeriod.value,
            start_date: selectedPeriod.value === 'custom' ? customStartDate.value : null,
            end_date: selectedPeriod.value === 'custom' ? customEndDate.value : null,
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
};

// -----------------------------------------------------------------------------
// Form Modal Input Saldo Tanggal 1
// -----------------------------------------------------------------------------
const isModalOpen = ref(false);
const isEditing = ref(false);
const editingRecordId = ref(null);

// Tanggal default: tanggal 1 bulan berjalan
const getDefaultDate = () => {
    const d = new Date();
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    return `${year}-${month}-01`;
};

const form = useForm({
    akun_copytrade_id: props.selected_account_id,
    tanggal_pencatatan: getDefaultDate(),
    balance: '',
    catatan: '',
});

const openCreateModal = () => {
    isEditing.value = false;
    editingRecordId.value = null;
    form.reset();
    form.clearErrors();
    form.akun_copytrade_id = selectedAccountId.value;
    form.tanggal_pencatatan = getDefaultDate();
    form.balance = '';
    form.catatan = '';
    isModalOpen.value = true;
};

const openEditModal = (record) => {
    isEditing.value = true;
    editingRecordId.value = record.id;
    form.clearErrors();
    form.akun_copytrade_id = selectedAccountId.value;
    form.tanggal_pencatatan = record.tanggal_pencatatan;
    form.balance = record.balance;
    form.catatan = record.catatan || '';
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
    form.clearErrors();
};

// Live Preview Perhitungan Keuntungan saat Mengetik Saldo
const liveBaseBalance = computed(() => {
    if (!props.summary) return 0;
    // Cari record sebelum tanggal form
    const formDate = form.tanggal_pencatatan;
    const history = props.summary.history || [];
    const prev = history
        .filter((h) => h.tanggal_pencatatan < formDate)
        .slice(-1)[0];

    return prev ? prev.balance : (props.summary.account.modal_riil || 0);
});

const liveProfitNominal = computed(() => {
    const bal = Number(form.balance) || 0;
    return bal - liveBaseBalance.value;
});

const liveProfitPct = computed(() => {
    if (liveBaseBalance.value <= 0) return 0;
    return (liveProfitNominal.value / liveBaseBalance.value) * 100;
});

const submitForm = () => {
    form.post(route('investor.copytrade.balance.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
        },
    });
};

// -----------------------------------------------------------------------------
// Dialog Hapus Catatan Saldo
// -----------------------------------------------------------------------------
const isConfirmDeleteOpen = ref(false);
const recordToDelete = ref(null);

const confirmDelete = (record) => {
    recordToDelete.value = record;
    isConfirmDeleteOpen.value = true;
};

const executeDelete = () => {
    if (!recordToDelete.value) return;
    router.delete(route('investor.copytrade.balance.destroy', recordToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            isConfirmDeleteOpen.value = false;
            recordToDelete.value = null;
        },
    });
};
</script>

<template>
    <InvestorLayout
        title="Kinerja & Riwayat Saldo MT4"
        subtitle="Input saldo akun trading Anda secara disiplin setiap tanggal 1. Sistem secara otomatis menghitung keuntungan bulan berjalan serta akumulasi profit multi-periode."
        :breadcrumbs="[
            { label: 'Menu Utama' },
            { label: 'Kinerja & Saldo MT4' }
        ]"
    >
        <template #header-actions>
            <div v-if="accounts.length > 0" class="shrink-0 self-start sm:self-center">
                <Button
                    type="button"
                    @click="openCreateModal"
                    class="bg-primary hover:bg-primary/90 text-primary-foreground text-xs font-bold py-2.5 px-4 rounded-xl shadow-xs flex items-center gap-2 cursor-pointer min-h-[40px]"
                >
                    <Plus class="w-4 h-4" />
                    <span>Input Saldo Tanggal 1</span>
                </Button>
            </div>
        </template>

        <Head title="Kinerja & Saldo MT4" />

        <div class="space-y-6">

            <!-- Banner Pengingat Khusus Tanggal 1 / Awal Bulan -->
            <div
                v-if="is_first_day_of_month || new Date().getDate() <= 5"
                class="rounded-xl border border-sky-500/30 bg-sky-500/10 p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-sky-950 dark:text-sky-200"
            >
                <div class="flex items-start gap-3">
                    <Calendar class="w-5 h-5 text-sky-600 dark:text-sky-400 shrink-0 mt-0.5 animate-bounce" />
                    <div>
                        <h4 class="text-xs sm:text-sm font-bold text-sky-950 dark:text-sky-100 flex items-center gap-2">
                            <span>Waktunya Rekapitulasi Saldo Tanggal 1!</span>
                            <span class="text-[10px] bg-sky-500/20 text-sky-700 dark:text-sky-300 px-2 py-0.5 rounded-full font-semibold">
                                Hari ke-{{ new Date().getDate() }} Bulan Ini
                            </span>
                        </h4>
                        <p class="text-xs text-sky-800/90 dark:text-sky-300/90 mt-0.5 leading-relaxed">
                            Buka aplikasi MT4 Anda, periksa saldo terkini, lalu klik tombol <strong>Input Saldo Tanggal 1</strong> agar performa keuntungan bulan lalu tercatat otomatis dan akurat.
                        </p>
                    </div>
                </div>

                <Button
                    v-if="accounts.length > 0"
                    type="button"
                    size="sm"
                    @click="openCreateModal"
                    class="bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold rounded-lg shrink-0 cursor-pointer min-h-[36px]"
                >
                    <Plus class="w-3.5 h-3.5" />
                    <span>Catat Saldo Sekarang</span>
                </Button>
            </div>

            <!-- State Jika Belum Memiliki Akun Copytrade -->
            <div
                v-if="accounts.length === 0"
                class="rounded-2xl border border-dashed border-border bg-card p-10 text-center space-y-4"
            >
                <div class="mx-auto w-14 h-14 rounded-2xl bg-primary-500/10 text-primary-600 flex items-center justify-center">
                    <Wallet class="w-7 h-7" />
                </div>
                <div class="space-y-1 max-w-md mx-auto">
                    <h3 class="text-base font-bold text-foreground">
                        Belum Ada Akun Copytrade Terhubung
                    </h3>
                    <p class="text-xs text-muted-foreground leading-relaxed">
                        Anda belum mendaftarkan akun copytrade. Hubungkan akun broker MT4 Anda terlebih dahulu untuk mulai mencatat saldo bulanan dan memantau keuntungan otomatis.
                    </p>
                </div>
                <div>
                    <Link
                        href="/investor/copytrade"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-primary text-primary-foreground text-xs font-bold hover:bg-primary/90 transition-colors shadow-xs"
                    >
                        <span>Daftarkan Akun Copytrade</span>
                        <ChevronRight class="w-4 h-4" />
                    </Link>
                </div>
            </div>

            <!-- Area Data & Kinerja Akun (Jika Ada Akun) -->
            <div v-else class="space-y-6">
                <!-- 1. Pemilih Akun Copytrade & Filter Periode Bebas -->
                <div class="rounded-2xl border border-border bg-card p-5 shadow-xs space-y-4">
                    <!-- Selector Akun (Multi-Akun Support) -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-border pb-4">
                        <div>
                            <span class="text-[11px] font-bold uppercase tracking-wider text-muted-foreground block">
                                Akun Trading Aktif:
                            </span>
                            <div class="flex flex-wrap gap-2 mt-1.5">
                                <button
                                    v-for="acc in accounts"
                                    :key="acc.id"
                                    type="button"
                                    @click="changeAccount(acc.id)"
                                    :class="[
                                        'rounded-xl px-3.5 py-2 text-xs font-bold transition-all flex items-center gap-2 cursor-pointer border',
                                        selectedAccountId === acc.id
                                            ? 'border-primary bg-primary/10 text-primary shadow-xs'
                                            : 'border-border bg-card hover:bg-muted text-muted-foreground',
                                    ]"
                                >
                                    <span class="font-mono">{{ acc.nomor_akun_trading }}</span>
                                    <span class="text-[10px] px-1.5 py-0.2 rounded bg-muted font-normal uppercase">
                                        {{ acc.broker }} • {{ acc.tier }}
                                    </span>
                                </button>
                            </div>
                        </div>

                        <!-- Informasi Modal Awal Akun -->
                        <div v-if="summary" class="sm:text-right">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-muted-foreground block">
                                Modal Awal Investasi:
                            </span>
                            <p class="text-base font-black text-foreground font-mono mt-0.5">
                                {{ formatUSD(summary.account.modal_riil) }}
                            </p>
                            <span class="text-[11px] text-muted-foreground block">
                                {{ formatIDR(summary.account.modal_riil_idr) }}
                            </span>
                        </div>
                    </div>

                    <!-- Filter Periode Bebas (3 Bln, 6 Bln, 1, 2, 3, 4 Tahun, Custom) -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-bold text-foreground flex items-center gap-1.5">
                                <Filter class="w-3.5 h-3.5 text-primary" />
                                <span>Pilih Periode Analisis Kinerja:</span>
                            </label>
                            <span class="text-xs font-semibold text-primary">
                                {{ summary?.period_label }}
                            </span>
                        </div>

                        <div class="flex flex-wrap items-center gap-1.5">
                            <button
                                v-for="p in [
                                    { key: '1m', label: '1 Bln Terakhir' },
                                    { key: '3m', label: '3 Bln Terakhir' },
                                    { key: '6m', label: '6 Bln Terakhir' },
                                    { key: '1y', label: '1 Thn Terakhir' },
                                    { key: '2y', label: '2 Thn Terakhir' },
                                    { key: '3y', label: '3 Thn Terakhir' },
                                    { key: '4y', label: '4 Thn Terakhir' },
                                    { key: 'all', label: 'Semua Periode' },
                                    { key: 'custom', label: 'Rentang Kustom' },
                                ]"
                                :key="p.key"
                                type="button"
                                @click="setPeriod(p.key)"
                                :class="[
                                    'rounded-lg px-2.5 py-1.5 text-xs font-bold transition-colors cursor-pointer',
                                    selectedPeriod === p.key
                                        ? 'bg-primary text-primary-foreground shadow-xs'
                                        : 'bg-muted hover:bg-muted/80 text-muted-foreground',
                                ]"
                            >
                                {{ p.label }}
                            </button>
                        </div>

                        <!-- Baris Input Tanggal Kustom (Jika memilih 'custom') -->
                        <div
                            v-if="isCustomDateOpen"
                            class="pt-3 border-t border-border mt-3 flex flex-wrap items-center gap-3 bg-muted/30 p-3 rounded-xl"
                        >
                            <div class="flex items-center gap-2">
                                <label class="text-xs font-semibold text-muted-foreground">Dari:</label>
                                <input
                                    v-model="customStartDate"
                                    type="date"
                                    class="rounded-lg border border-border bg-card px-2.5 py-1 text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-primary"
                                />
                            </div>
                            <div class="flex items-center gap-2">
                                <label class="text-xs font-semibold text-muted-foreground">Sampai:</label>
                                <input
                                    v-model="customEndDate"
                                    type="date"
                                    class="rounded-lg border border-border bg-card px-2.5 py-1 text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-primary"
                                />
                            </div>
                            <Button
                                type="button"
                                size="sm"
                                @click="applyFilter"
                                class="bg-primary text-primary-foreground text-xs font-bold px-3 py-1 rounded-lg cursor-pointer"
                            >
                                Terapkan Periode
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- 2. Ringkasan Kinerja Periode Terpilih (Performance Cards) -->
                <div v-if="summary" class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <!-- Saldo Awal Periode -->
                    <div class="rounded-2xl border border-border bg-card p-4 sm:p-5 shadow-xs">
                        <span class="text-xs font-medium text-muted-foreground block">
                            Saldo Awal Periode
                        </span>
                        <p class="mt-1 text-lg sm:text-2xl font-black text-foreground font-mono">
                            {{ formatUSD(summary.period_start_balance) }}
                        </p>
                        <span class="text-[11px] text-muted-foreground block mt-0.5">
                            {{ formatIDR(summary.period_start_balance_idr) }}
                        </span>
                    </div>

                    <!-- Saldo Akhir Periode -->
                    <div class="rounded-2xl border border-border bg-card p-4 sm:p-5 shadow-xs">
                        <span class="text-xs font-medium text-muted-foreground block">
                            Saldo Akhir Periode
                        </span>
                        <p class="mt-1 text-lg sm:text-2xl font-black text-foreground font-mono">
                            {{ formatUSD(summary.period_end_balance) }}
                        </p>
                        <span class="text-[11px] text-muted-foreground block mt-0.5">
                            {{ formatIDR(summary.period_end_balance_idr) }}
                        </span>
                    </div>

                    <!-- Keuntungan Periode Terpilih -->
                    <div
                        :class="[
                            'rounded-2xl border p-4 sm:p-5 shadow-xs',
                            summary.period_profit_nominal >= 0
                                ? 'border-emerald-500/30 bg-emerald-500/10'
                                : 'border-rose-500/30 bg-rose-500/10',
                        ]"
                    >
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-muted-foreground block">
                                Keuntungan Periode Ini
                            </span>
                            <span
                                :class="[
                                    'inline-flex items-center text-[10px] font-bold px-1.5 py-0.5 rounded-full',
                                    summary.period_profit_nominal >= 0
                                        ? 'bg-emerald-500/20 text-emerald-700 dark:text-emerald-300'
                                        : 'bg-rose-500/20 text-rose-700 dark:text-rose-300',
                                ]"
                            >
                                <ArrowUpRight v-if="summary.period_profit_nominal >= 0" class="w-3 h-3" />
                                <ArrowDownRight v-else class="w-3 h-3" />
                                {{ summary.period_growth_pct > 0 ? '+' : '' }}{{ summary.period_growth_pct.toFixed(2) }}%
                            </span>
                        </div>
                        <p
                            :class="[
                                'mt-1 text-lg sm:text-2xl font-black font-mono',
                                summary.period_profit_nominal >= 0
                                    ? 'text-emerald-700 dark:text-emerald-400'
                                    : 'text-rose-700 dark:text-rose-400',
                            ]"
                        >
                            {{ summary.period_profit_nominal >= 0 ? '+' : '' }}{{ formatUSD(summary.period_profit_nominal) }}
                        </p>
                        <span class="text-[11px] text-muted-foreground block mt-0.5">
                            {{ formatIDR(summary.period_profit_nominal_idr) }}
                        </span>
                    </div>

                    <!-- Total Keuntungan All-Time (Sejak Awal Join) -->
                    <div class="rounded-2xl border border-blue-500/30 bg-blue-500/10 p-4 sm:p-5 shadow-xs">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-blue-900 dark:text-blue-200 block">
                                Total All-Time Profit
                            </span>
                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-blue-500/20 text-blue-700 dark:text-blue-300">
                                {{ summary.all_time_growth_pct > 0 ? '+' : '' }}{{ summary.all_time_growth_pct.toFixed(2) }}%
                            </span>
                        </div>
                        <p class="mt-1 text-lg sm:text-2xl font-black text-blue-700 dark:text-blue-400 font-mono">
                            {{ summary.all_time_profit_nominal >= 0 ? '+' : '' }}{{ formatUSD(summary.all_time_profit_nominal) }}
                        </p>
                        <span class="text-[11px] text-muted-foreground block mt-0.5">
                            Rata-rata: +{{ summary.avg_monthly_profit_pct.toFixed(2) }}%/bulan
                        </span>
                    </div>
                </div>

                <!-- 3. Tabel Riwayat Pencatatan Saldo Bulanan -->
                <div class="rounded-2xl border border-border bg-card p-5 sm:p-6 shadow-xs space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-border pb-4">
                        <div>
                            <h3 class="text-base font-bold text-foreground">
                                Riwayat Saldo MT4 & Rekap Keuntungan Bulanan
                            </h3>
                            <p class="text-xs text-muted-foreground">
                                Keuntungan otomatis dihitung: saldo bulan berjalan dikurangi saldo bulan sebelumnya (atau modal awal untuk input pertama).
                            </p>
                        </div>

                        <div class="text-xs text-muted-foreground font-mono">
                            Total Entri: {{ summary?.history?.length || 0 }} data
                        </div>
                    </div>

                    <!-- Tabel Data -->
                    <div v-if="summary && summary.history.length > 0" class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="border-b border-border bg-muted/50">
                                    <th class="py-3 px-3.5 font-bold text-foreground">Tanggal Saldo</th>
                                    <th class="py-3 px-3.5 font-bold text-foreground">Saldo Tercatat (USD)</th>
                                    <th class="py-3 px-3.5 font-bold text-foreground">Saldo (IDR)</th>
                                    <th class="py-3 px-3.5 font-bold text-foreground">Keuntungan Bulan Ini (MoM)</th>
                                    <th class="py-3 px-3.5 font-bold text-foreground">Profit (%)</th>
                                    <th class="py-3 px-3.5 font-bold text-foreground">Total Profit All-Time</th>
                                    <th class="py-3 px-3.5 font-bold text-foreground">Catatan</th>
                                    <th class="py-3 px-3.5 font-bold text-foreground text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                <tr
                                    v-for="row in summary.history"
                                    :key="row.id"
                                    class="hover:bg-muted/30 transition-colors"
                                >
                                    <td class="py-3 px-3.5 font-bold text-foreground whitespace-nowrap">
                                        {{ row.tanggal_formatted }}
                                    </td>
                                    <td class="py-3 px-3.5 font-mono font-bold text-foreground whitespace-nowrap text-sm">
                                        {{ formatUSD(row.balance) }}
                                    </td>
                                    <td class="py-3 px-3.5 font-mono text-muted-foreground whitespace-nowrap">
                                        {{ formatIDR(row.balance_idr) }}
                                    </td>
                                    <td class="py-3 px-3.5 font-mono font-bold whitespace-nowrap">
                                        <span
                                            :class="row.keuntungan_nominal >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'"
                                        >
                                            {{ row.keuntungan_nominal >= 0 ? '+' : '' }}{{ formatUSD(row.keuntungan_nominal) }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-3.5 font-mono whitespace-nowrap">
                                        <span
                                            :class="[
                                                'inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full text-[11px] font-bold',
                                                row.keuntungan_persen >= 0
                                                    ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300'
                                                    : 'bg-rose-500/15 text-rose-700 dark:text-rose-300',
                                            ]"
                                        >
                                            {{ row.keuntungan_persen >= 0 ? '+' : '' }}{{ row.keuntungan_persen.toFixed(2) }}%
                                        </span>
                                    </td>
                                    <td class="py-3 px-3.5 font-mono text-foreground font-semibold whitespace-nowrap">
                                        {{ row.keuntungan_kumulatif >= 0 ? '+' : '' }}{{ formatUSD(row.keuntungan_kumulatif) }}
                                    </td>
                                    <td class="py-3 px-3.5 text-muted-foreground max-w-xs truncate">
                                        {{ row.catatan || '-' }}
                                    </td>
                                    <td class="py-3 px-3.5 text-center whitespace-nowrap">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <button
                                                type="button"
                                                @click="openEditModal(row)"
                                                class="p-1.5 rounded-lg text-muted-foreground hover:text-foreground hover:bg-muted transition-colors cursor-pointer"
                                                title="Edit Saldo"
                                            >
                                                <Edit3 class="w-3.5 h-3.5" />
                                            </button>
                                            <button
                                                type="button"
                                                @click="confirmDelete(row)"
                                                class="p-1.5 rounded-lg text-rose-600 hover:bg-rose-500/10 transition-colors cursor-pointer"
                                                title="Hapus Catatan"
                                            >
                                                <Trash2 class="w-3.5 h-3.5" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- State Jika Belum Ada Data Saldo Tercatat -->
                    <div
                        v-else
                        class="p-8 text-center rounded-xl bg-muted/20 border border-dashed border-border space-y-3"
                    >
                        <Coins class="w-10 h-10 text-muted-foreground mx-auto" />
                        <div class="space-y-1">
                            <h4 class="text-sm font-bold text-foreground">
                                Belum Ada Catatan Saldo MT4
                            </h4>
                            <p class="text-xs text-muted-foreground max-w-md mx-auto">
                                Mulai input saldo akun trading Anda (terutama setiap tanggal 1) untuk memantau performa keuntungan bulanan secara otomatis.
                            </p>
                        </div>
                        <div>
                            <Button
                                type="button"
                                size="sm"
                                @click="openCreateModal"
                                class="bg-primary text-primary-foreground text-xs font-bold rounded-lg cursor-pointer min-h-[36px]"
                            >
                                <Plus class="w-3.5 h-3.5" />
                                <span>Input Saldo Sekarang</span>
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL FORM INPUT / EDIT SALDO TANGGAL 1 -->
        <AppModal
            :show="isModalOpen"
            :title="isEditing ? 'Ubah Catatan Saldo MT4' : 'Input Saldo MT4 (Tanggal 1)'"
            @close="closeModal"
            max-width="md"
        >
            <form @submit.prevent="submitForm" class="space-y-4">
                <!-- Info Akun yang Sedang Diisi -->
                <div class="p-3 rounded-xl bg-muted/40 border border-border text-xs flex items-center justify-between">
                    <div>
                        <span class="text-muted-foreground block text-[10px] uppercase font-bold">Akun Terpilih:</span>
                        <strong class="text-foreground font-mono">{{ summary?.account?.nomor_akun_trading }}</strong>
                    </div>
                    <div class="text-right">
                        <span class="text-muted-foreground block text-[10px] uppercase font-bold">Modal Awal:</span>
                        <strong class="text-foreground font-mono">{{ formatUSD(summary?.account?.modal_riil) }}</strong>
                    </div>
                </div>

                <!-- Input Tanggal Pencatatan -->
                <div>
                    <label class="block text-xs font-bold text-foreground mb-1">
                        Tanggal Pencatatan Saldo <span class="text-rose-500">*</span>
                    </label>
                    <input
                        v-model="form.tanggal_pencatatan"
                        type="date"
                        class="w-full rounded-xl border border-input bg-background px-3 py-2 text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-primary"
                        required
                    />
                    <p class="text-[10px] text-muted-foreground mt-1">
                        Disarankan setiap tanggal 1 untuk rekapitulasi performa bulan sebelumnya.
                    </p>
                    <span v-if="form.errors.tanggal_pencatatan" class="text-[11px] text-rose-500 mt-1 block font-medium">
                        {{ form.errors.tanggal_pencatatan }}
                    </span>
                </div>

                <!-- Input Nilai Saldo / Balance (USD) -->
                <div>
                    <label class="block text-xs font-bold text-foreground mb-1">
                        Saldo MT4 Saat Ini ($ USD) <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-bold text-muted-foreground">$</span>
                        <input
                            v-model.number="form.balance"
                            type="number"
                            step="0.01"
                            min="0"
                            placeholder="Contoh: 105.50"
                            class="w-full rounded-xl border border-input bg-background py-2.5 pl-8 pr-3 text-sm font-bold text-foreground focus:outline-none focus:ring-1 focus:ring-primary font-mono"
                            required
                        />
                    </div>
                    <span v-if="form.errors.balance" class="text-[11px] text-rose-500 mt-1 block font-medium">
                        {{ form.errors.balance }}
                    </span>
                </div>

                <!-- LIVE PREVIEW PERHITUNGAN OTOMATIS KEUNTUNGAN -->
                <div
                    v-if="form.balance"
                    class="rounded-xl border border-sky-500/30 bg-sky-500/10 p-3.5 space-y-2 text-xs"
                >
                    <span class="font-bold text-sky-950 dark:text-sky-200 block text-[11px] uppercase tracking-wider">
                        Perhitungan Otomatis Keuntungan:
                    </span>
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-muted-foreground">Basis Perbandingan:</span>
                        <span class="font-mono font-bold text-foreground">{{ formatUSD(liveBaseBalance) }}</span>
                    </div>
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-muted-foreground">Keuntungan Bulan Ini:</span>
                        <span
                            :class="[
                                'font-mono font-black text-sm',
                                liveProfitNominal >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'
                            ]"
                        >
                            {{ liveProfitNominal >= 0 ? '+' : '' }}{{ formatUSD(liveProfitNominal) }}
                            ({{ liveProfitPct >= 0 ? '+' : '' }}{{ liveProfitPct.toFixed(2) }}%)
                        </span>
                    </div>
                    <div class="flex justify-between items-center text-[11px] text-muted-foreground pt-1 border-t border-sky-500/20">
                        <span>Konversi IDR (Kurs Rp {{ exchange_rate_meta?.rate?.toLocaleString('id-ID') }}):</span>
                        <span class="font-mono font-semibold">{{ formatIDR(liveProfitNominal * (exchange_rate_meta?.rate || 16796)) }}</span>
                    </div>
                </div>

                <!-- Input Catatan Opsional -->
                <div>
                    <label class="block text-xs font-bold text-foreground mb-1">
                        Catatan Tambahan (Opsional)
                    </label>
                    <input
                        v-model="form.catatan"
                        type="text"
                        placeholder="Misal: Profit normal + ada penarikan $20"
                        class="w-full rounded-xl border border-input bg-background px-3 py-2 text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-primary"
                    />
                    <span v-if="form.errors.catatan" class="text-[11px] text-rose-500 mt-1 block font-medium">
                        {{ form.errors.catatan }}
                    </span>
                </div>

                <!-- Tombol Aksi Form -->
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-border">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="closeModal"
                        class="cursor-pointer min-h-[36px]"
                    >
                        Batal
                    </Button>
                    <Button
                        type="submit"
                        size="sm"
                        :disabled="form.processing"
                        class="bg-primary text-primary-foreground font-bold cursor-pointer min-h-[36px]"
                    >
                        {{ form.processing ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Simpan Saldo Tanggal 1') }}
                    </Button>
                </div>
            </form>
        </AppModal>

        <!-- DIALOG KONFIRMASI HAPUS CATATAN SALDO -->
        <AppConfirmDialog
            :show="isConfirmDeleteOpen"
            title="Hapus Catatan Saldo MT4?"
            :message="`Apakah Anda yakin ingin menghapus catatan saldo per tanggal ${recordToDelete?.tanggal_formatted} ($${recordToDelete?.balance})? Selisih keuntungan bulan berikutnya akan disesuaikan otomatis.`"
            confirm-label="Ya, Hapus Catatan"
            cancel-label="Batal"
            variant="danger"
            @confirm="executeDelete"
            @close="isConfirmDeleteOpen = false"
        />
    </InvestorLayout>
</template>
