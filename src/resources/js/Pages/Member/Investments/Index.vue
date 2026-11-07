<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    Activity,
    AlertCircle,
    ArrowDownRight,
    ArrowUpRight,
    Calendar,
    CheckCircle2,
    ChevronRight,
    Clock,
    Coins,
    Copy,
    DollarSign,
    ExternalLink,
    HelpCircle,
    Info,
    Layers,
    LineChart,
    Loader2,
    Minus,
    PlusCircle,
    ShieldCheck,
    Sparkles,
    Trash2,
    TrendingDown,
    TrendingUp,
    Wallet,
    XCircle,
    Lock,
    Eye,
    Receipt,
} from 'lucide-vue-next';
import MemberLayout from '@/Layouts/MemberLayout.vue';
import AppModal from '@/Components/Common/AppModal.vue';
import AppConfirmDialog from '@/Components/Common/AppConfirmDialog.vue';
import AppTableAction from '@/Components/Common/AppTableAction.vue';
import AppButton from '@/Components/Common/AppButton.vue';
import { Button } from '@/Components/ui/button';
import { toast } from 'vue-sonner';
import { compressImageFile } from '@/lib/imageCompression';
import AddInvestmentAccountModal from './AddInvestmentAccountModal.vue';

const props = defineProps({
    consolidated: {
        type: Object,
        required: true,
    },
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
    has_existing: {
        type: Boolean,
        default: false,
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

const getProfitStatus = (val) => {
    if (val === null || val === undefined) return 'zero';
    const num = Number(val);
    if (Math.abs(num) < 0.005) return 'zero';
    return num > 0 ? 'positive' : 'negative';
};

const profitRowClass = (val) => {
    const status = getProfitStatus(val);
    if (status === 'positive') return 'text-emerald-700 dark:text-emerald-300';
    if (status === 'negative') return 'text-destructive';
    return 'text-muted-foreground';
};

const profitTooltipClass = (val) => {
    const status = getProfitStatus(val);
    if (status === 'positive') return 'text-emerald-600 dark:text-emerald-400';
    if (status === 'negative') return 'text-rose-600 dark:text-rose-400';
    return 'text-muted-foreground';
};

const profitIcon = (val) => {
    const status = getProfitStatus(val);
    if (status === 'positive') return ArrowUpRight;
    if (status === 'negative') return ArrowDownRight;
    return Minus;
};

const formatShortDate = (dateStr, fallbackFormatted = '') => {
    if (!dateStr) return fallbackFormatted;
    const parts = dateStr.split('-');
    if (parts.length >= 2) {
        const year = parts[0].slice(-2);
        const monthNum = parseInt(parts[1], 10);
        const months = [
            'Jan',
            'Feb',
            'Mar',
            'Apr',
            'Mei',
            'Jun',
            'Jul',
            'Agu',
            'Sep',
            'Okt',
            'Nov',
            'Des',
        ];
        const month = months[monthNum - 1] || parts[1];
        return `${month} '${year}`;
    }
    return fallbackFormatted;
};

// Clipboard copy helper
const copyToClipboard = (text, label) => {
    if (!text) return;
    navigator.clipboard.writeText(text);
    toast.success(`${label} berhasil disalin ke clipboard.`);
};

// -----------------------------------------------------------------------------
// Akun Terpilih, Akun Aktif & Pending
// -----------------------------------------------------------------------------
const activeAccounts = computed(() => props.accounts.filter((a) => a.status === 'active'));
const pendingAccounts = computed(() => props.accounts.filter((a) => a.status === 'pending'));
const hasActiveAccounts = computed(() => activeAccounts.value.length > 0);

const selectedAccountId = ref(props.selected_account_id);
const selectedPeriod = ref(props.filters.period || 'all');

const monthsList = [
    { value: '01', label: 'Januari' },
    { value: '02', label: 'Februari' },
    { value: '03', label: 'Maret' },
    { value: '04', label: 'April' },
    { value: '05', label: 'Mei' },
    { value: '06', label: 'Juni' },
    { value: '07', label: 'Juli' },
    { value: '08', label: 'Agustus' },
    { value: '09', label: 'September' },
    { value: '10', label: 'Oktober' },
    { value: '11', label: 'November' },
    { value: '12', label: 'Desember' },
];

const currentYear = new Date().getFullYear();
const currentMonthNumber = String(new Date().getMonth() + 1).padStart(2, '0');

const initialStart = props.filters.start_date
    ? props.filters.start_date.substring(0, 7).split('-')
    : [];
const initialEnd = props.filters.end_date ? props.filters.end_date.substring(0, 7).split('-') : [];

const customStartMonth = ref(initialStart[1] || currentMonthNumber);
const customStartYear = ref(Number(initialStart[0]) || currentYear);

const customEndMonth = ref(initialEnd[1] || currentMonthNumber);
const customEndYear = ref(Number(initialEnd[0]) || currentYear);

const isCustomDateOpen = ref(props.filters.period === 'custom');

const selectedAccountData = computed(() => {
    return (
        props.accounts.find((a) => a.id === selectedAccountId.value) || props.accounts[0] || null
    );
});

// Hitung durasi aktif akun dalam bulan kalender
const activeMonths = computed(() => {
    return (
        selectedAccountData.value?.months_active ??
        props.summary?.months_active ??
        props.summary?.account?.months_active ??
        0
    );
});

// Opsi periode analisis dinamis berdasarkan durasi aktif akun
const availablePeriods = computed(() => {
    const list = [];
    const m = activeMonths.value;

    // 1 Bulan: selalu tersedia sebagai rentang snapshot terpendek
    list.push({ key: '1m', label: '1 Bulan' });

    // 3 Bulan: tampil hanya jika akun telah aktif minimal 3 bulan
    if (m >= 3) {
        list.push({ key: '3m', label: '3 Bulan' });
    }

    // 6 Bulan: tampil hanya jika akun telah aktif minimal 6 bulan
    if (m >= 6) {
        list.push({ key: '6m', label: '6 Bulan' });
    }

    // 1 Tahun: tampil hanya jika akun telah aktif minimal 12 bulan (1 tahun)
    if (m >= 12) {
        list.push({ key: '1y', label: '1 Tahun' });
    }

    // 2 Tahun: tampil hanya jika akun telah aktif minimal 24 bulan (2 tahun)
    if (m >= 24) {
        list.push({ key: '2y', label: '2 Tahun' });
    }

    // Semua Periode & Kustom selalu tersedia
    list.push({ key: 'all', label: 'Semua Periode' });
    list.push({ key: 'custom', label: 'Kustom' });

    return list;
});

// Otomatis fallback ke 'all' jika periode yang sebelumnya dipilih tidak ada dalam daftar yang tersedia untuk akun ini
watch(availablePeriods, (newPeriods) => {
    if (
        selectedPeriod.value &&
        selectedPeriod.value !== 'all' &&
        selectedPeriod.value !== 'custom'
    ) {
        const isStillAvailable = newPeriods.some((p) => p.key === selectedPeriod.value);
        if (!isStillAvailable) {
            selectedPeriod.value = 'all';
            applyFilter();
        }
    }
});

watch(
    () => props.selected_account_id,
    (newId) => {
        if (newId && newId !== selectedAccountId.value) {
            selectedAccountId.value = newId;
            balanceForm.akun_copytrade_id = newId;
        }
    }
);

watch(
    () => props.filters.period,
    (newPeriod) => {
        if (newPeriod && newPeriod !== selectedPeriod.value) {
            selectedPeriod.value = newPeriod;
        }
    }
);

const selectAccount = (accId) => {
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
    const startYear = customStartYear.value || currentYear;
    const endYear = customEndYear.value || currentYear;
    const startVal =
        isCustomDateOpen.value && startYear && customStartMonth.value
            ? `${startYear}-${customStartMonth.value}`
            : null;
    const endVal =
        isCustomDateOpen.value && endYear && customEndMonth.value
            ? `${endYear}-${customEndMonth.value}`
            : null;

    router.get(
        route('member.investments.index'),
        {
            account_id: selectedAccountId.value,
            period: selectedPeriod.value,
            start_date: selectedPeriod.value === 'custom' ? startVal : null,
            end_date: selectedPeriod.value === 'custom' ? endVal : null,
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
};

// -----------------------------------------------------------------------------
// Modal Tambah Akun Investasi
// -----------------------------------------------------------------------------
const isAddModalOpen = ref(false);
const selectedTierForAdd = ref('');

const openAddModal = (tier = '') => {
    selectedTierForAdd.value = tier;
    isAddModalOpen.value = true;
};

// -----------------------------------------------------------------------------
// Modal Input Saldo Tanggal 1 (Khusus Akun Aktif)
// -----------------------------------------------------------------------------
const isBalanceModalOpen = ref(false);
const isEditingBalance = ref(false);
const editingRecordId = ref(null);
const editingRecord = ref(null);

const getDefaultDate = () => {
    const d = new Date();
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    return `${year}-${month}-01`;
};

const balanceForm = useForm({
    akun_copytrade_id: props.selected_account_id,
    tanggal_pencatatan: getDefaultDate(),
    balance: '',
    penarikan: 0,
    setoran_tambahan: 0,
    bukti_saldo: null,
    catatan: '',
});

const isRecordLocked = computed(() => {
    return isEditingBalance.value && (editingRecord.value?.tagihan_master_fee?.is_locked || false);
});

const modalAccount = computed(() => {
    return (
        props.accounts.find((a) => a.id === balanceForm.akun_copytrade_id) ||
        selectedAccountData.value ||
        null
    );
});

watch(selectedAccountId, (newId) => {
    if (newId) {
        balanceForm.akun_copytrade_id = newId;
    }
});

const handleBuktiSaldoChange = async (event) => {
    const file = event.target.files?.[0];
    if (!file) {
        balanceForm.bukti_saldo = null;
        return;
    }
    try {
        const res = await compressImageFile(file);
        balanceForm.bukti_saldo = res.file;
    } catch {
        balanceForm.bukti_saldo = file;
    }
};

const openBalanceModalForAccount = (accId) => {
    const targetAcc = props.accounts.find((a) => a.id === accId) || activeAccounts.value[0];
    if (targetAcc && targetAcc.status !== 'active') {
        toast.error('Catatan saldo hanya dapat diisi setelah akun disetujui oleh Administrator.');
        return;
    }

    if (!targetAcc) {
        toast.error('Belum ada akun investasi aktif untuk mencatat saldo.');
        return;
    }

    isEditingBalance.value = false;
    editingRecordId.value = null;
    editingRecord.value = null;
    balanceForm.reset();
    balanceForm.clearErrors();
    balanceForm.akun_copytrade_id = targetAcc.id;
    balanceForm.tanggal_pencatatan = getDefaultDate();
    balanceForm.balance = '';
    balanceForm.penarikan = 0;
    balanceForm.setoran_tambahan = 0;
    balanceForm.bukti_saldo = null;
    balanceForm.catatan = '';
    isBalanceModalOpen.value = true;
};

const openEditBalanceModal = (record) => {
    isEditingBalance.value = true;
    editingRecordId.value = record.id;
    editingRecord.value = record;
    balanceForm.clearErrors();
    balanceForm.akun_copytrade_id = selectedAccountId.value;
    balanceForm.tanggal_pencatatan = record.tanggal_pencatatan;
    balanceForm.balance = record.balance;
    balanceForm.penarikan = record.penarikan || 0;
    balanceForm.setoran_tambahan = record.setoran_tambahan || 0;
    balanceForm.catatan = record.catatan || '';
    balanceForm.bukti_saldo = null;
    isBalanceModalOpen.value = true;
};

const closeBalanceModal = () => {
    isBalanceModalOpen.value = false;
    isEditingBalance.value = false;
    editingRecordId.value = null;
    editingRecord.value = null;
    balanceForm.reset();
    balanceForm.clearErrors();
};

// Live Preview Perhitungan Keuntungan saat input saldo
const liveBaseBalance = computed(() => {
    if (!props.summary) return 0;
    const formDate = balanceForm.tanggal_pencatatan;
    const history = props.summary.history || [];
    const prev = history
        .filter(
            (h) =>
                (!editingRecordId.value || h.id !== editingRecordId.value) &&
                h.tanggal_pencatatan < formDate
        )
        .slice(-1)[0];

    return prev ? prev.balance : props.summary.account?.modal_riil || 0;
});

const liveProfitNominal = computed(() => {
    const bal = Number(balanceForm.balance) || 0;
    const pen = Number(balanceForm.penarikan) || 0;
    const set = Number(balanceForm.setoran_tambahan) || 0;
    return bal - liveBaseBalance.value + pen - set;
});

const liveFeeNominal = computed(() => {
    return liveProfitNominal.value > 0 ? liveProfitNominal.value * 0.30 : 0;
});

const liveProfitPct = computed(() => {
    if (liveBaseBalance.value <= 0) return 0;
    return (liveProfitNominal.value / liveBaseBalance.value) * 100;
});

const submitBalance = () => {
    if (isRecordLocked.value) {
        toast.error('Rekap terkunci karena tagihan master fee sedang diverifikasi atau sudah lunas.');
        return;
    }

    if (isEditingBalance.value && editingRecordId.value) {
        balanceForm.transform((data) => ({
            ...data,
            _method: 'PUT',
        })).post(route('member.investments.balance.update', editingRecordId.value), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                closeBalanceModal();
            },
        });
    } else {
        balanceForm.post(route('member.investments.balance.store'), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                closeBalanceModal();
            },
        });
    }
};

// -----------------------------------------------------------------------------
// Hapus Catatan Saldo
// -----------------------------------------------------------------------------
const isConfirmDeleteOpen = ref(false);
const recordToDelete = ref(null);
const isDeletingBalance = ref(false);

const confirmDeleteRecord = (record) => {
    if (record.tagihan_master_fee?.is_locked) {
        toast.error('Rekap terkunci karena tagihan master fee sedang diverifikasi atau sudah lunas.');
        return;
    }
    recordToDelete.value = record;
    isConfirmDeleteOpen.value = true;
};

const executeDeleteRecord = () => {
    if (!recordToDelete.value) return;
    isDeletingBalance.value = true;
    router.delete(route('member.investments.balance.destroy', recordToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            isConfirmDeleteOpen.value = false;
            recordToDelete.value = null;
        },
        onFinish: () => {
            isDeletingBalance.value = false;
        },
    });
};

// -----------------------------------------------------------------------------
// SVG Trend Chart Data Calculation
// -----------------------------------------------------------------------------
const hoveredIndex = ref(null);
const activeClickedIndex = ref(null);
const chartScrollContainer = ref(null);

const activeHoveredIndex = computed(() => {
    if (hoveredIndex.value !== null) return hoveredIndex.value;
    if (activeClickedIndex.value !== null) return activeClickedIndex.value;
    return null;
});

const hoveredPoint = computed(() => {
    if (activeHoveredIndex.value !== null && chartPoints.value[activeHoveredIndex.value]) {
        return chartPoints.value[activeHoveredIndex.value];
    }
    return null;
});

const chartPaddingLeft = 55;
const chartPaddingRight = 30;

const chartWidth = computed(() => {
    const count = props.summary?.history?.length || 0;
    // Berikan ruang lebar minimal 55px per titik data agar tidak berdempetan, minimal 750px
    return Math.max(750, count * 55);
});

const stepX = computed(() => {
    if (chartPoints.value.length < 2) return 50;
    return (
        (chartWidth.value - chartPaddingLeft - chartPaddingRight) / (chartPoints.value.length - 1)
    );
});

const yAxisLevels = computed(() => {
    if (!props.summary || !props.summary.history || props.summary.history.length < 2) {
        return [];
    }
    const balances = props.summary.history.map((h) => h.balance);
    const minVal = Math.min(...balances);
    const maxVal = Math.max(...balances);
    const range = maxVal - minVal;

    const height = 240;
    const paddingTop = 32;
    const paddingBottom = 42;
    const usableHeight = height - paddingTop - paddingBottom;

    if (range === 0) {
        return [{ val: maxVal, y: paddingTop + usableHeight / 2 }];
    }

    const midVal = Math.round((maxVal + minVal) / 2);
    return [
        { val: maxVal, y: paddingTop },
        { val: midVal, y: paddingTop + usableHeight / 2 },
        { val: minVal, y: height - paddingBottom },
    ];
});

const chartPoints = computed(() => {
    if (!props.summary || !props.summary.history || props.summary.history.length < 2) {
        return [];
    }

    const history = props.summary.history;
    const balances = history.map((h) => h.balance);
    const minVal = Math.min(...balances);
    const maxVal = Math.max(...balances);
    const range = maxVal - minVal || 1;

    const width = chartWidth.value;
    const height = 240;
    const paddingTop = 32;
    const paddingBottom = 42;

    const usableWidth = width - chartPaddingLeft - chartPaddingRight;
    const usableHeight = height - paddingTop - paddingBottom;
    const currentStepX = usableWidth / (history.length - 1);

    // Cari index max & min untuk penandaan titik penting
    let maxIdx = 0;
    let minIdx = 0;
    balances.forEach((b, i) => {
        if (b > balances[maxIdx]) maxIdx = i;
        if (b < balances[minIdx]) minIdx = i;
    });
    const latestIdx = history.length - 1;

    return history.map((item, idx) => {
        const x = chartPaddingLeft + idx * currentStepX;
        const normalizedY = (item.balance - minVal) / range;
        const y = height - paddingBottom - normalizedY * usableHeight;

        const isMax = idx === maxIdx && maxVal !== minVal;
        const isMin = idx === minIdx && maxVal !== minVal;
        const isLatest = idx === latestIdx;

        return {
            idx,
            x,
            y,
            balance: item.balance,
            fullDate: item.tanggal_formatted,
            shortDate: formatShortDate(item.tanggal_pencatatan, item.tanggal_formatted),
            profitMoM: item.keuntungan_nominal,
            profitPct: item.keuntungan_persen,
            isMax,
            isMin,
            isLatest,
        };
    });
});

const chartPathD = computed(() => {
    if (chartPoints.value.length < 2) return '';
    return chartPoints.value.reduce((acc, pt, idx) => {
        return idx === 0 ? `M ${pt.x} ${pt.y}` : `${acc} L ${pt.x} ${pt.y}`;
    }, '');
});

const chartAreaD = computed(() => {
    if (chartPoints.value.length < 2) return '';
    const first = chartPoints.value[0];
    const last = chartPoints.value[chartPoints.value.length - 1];
    const baseline = 240 - 42;
    return `${chartPathD.value} L ${last.x} ${baseline} L ${first.x} ${baseline} Z`;
});

const shouldShowPointLabel = (pt) => {
    if (chartPoints.value.length <= 7) return true;
    return pt.isLatest || pt.isMax || pt.isMin;
};

const shouldShowDateLabel = (idx, total) => {
    if (total <= 12) return true;
    if (total <= 20) return idx % 2 === 0 || idx === total - 1;
    if (total <= 30) return idx % 3 === 0 || idx === total - 1;
    return idx % 4 === 0 || idx === total - 1;
};

const scrollToLatestRecord = () => {
    if (chartScrollContainer.value) {
        const maxScroll =
            chartScrollContainer.value.scrollWidth - chartScrollContainer.value.clientWidth;
        if (maxScroll > 20) {
            chartScrollContainer.value.scrollLeft = maxScroll;
        } else {
            chartScrollContainer.value.scrollLeft = 0;
        }
    }
};

onMounted(() => {
    scrollToLatestRecord();
});

watch(
    () => props.summary?.history,
    () => {
        setTimeout(scrollToLatestRecord, 100);
    },
    { deep: true }
);

// Badges Helper
const getTierBadge = (tier) => {
    switch (tier) {
        case 'tier_3':
            return {
                label: 'Tier 3 (Direct VPS ≥ $300.000)',
                shortLabel: 'Tier 3',
                bg: 'bg-amber-500/10 text-amber-800 dark:text-amber-300 border-amber-500/30',
            };
        case 'tier_2':
            return {
                label: 'Tier 2 (Standard $5.000 - $300.000)',
                shortLabel: 'Tier 2',
                bg: 'bg-indigo-500/10 text-indigo-800 dark:text-indigo-300 border-indigo-500/30',
            };
        case 'tier_1':
        default:
            return {
                label: 'Tier 1 (Cent < $5.000)',
                shortLabel: 'Tier 1',
                bg: 'bg-sky-500/10 text-sky-800 dark:text-sky-300 border-sky-500/30',
            };
    }
};

const getStatusBadge = (status) => {
    switch (status) {
        case 'active':
            return {
                label: 'Aktif',
                bg: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-500/30',
                icon: CheckCircle2,
            };
        case 'rejected':
            return {
                label: 'Ditolak',
                bg: 'bg-destructive/10 text-destructive border-destructive/30',
                icon: XCircle,
            };
        case 'pending':
        default:
            return {
                label: 'Menunggu Verifikasi',
                bg: 'bg-amber-500/10 text-amber-800 dark:text-amber-300 border-amber-500/30',
                icon: Clock,
            };
    }
};

onMounted(() => {
    if (window.location.hash) {
        const hash = window.location.hash.replace('#', '');
        const el = document.getElementById(hash);
        if (el) {
            setTimeout(() => {
                el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 150);
        }
    }
});
</script>

<template>
    <Head title="Portofolio Investasi" />
    <MemberLayout
        title="Portofolio Investasi"
        subtitle="Pantau seluruh investasi Anda, BEP, dan catat saldo bulanan secara disiplin."
        :breadcrumbs="[{ label: 'Menu Utama' }, { label: 'Portofolio Investasi' }]"
    >
        <div class="space-y-8">
            <!-- ================================================================= -->
            <!-- 1. RINGKASAN KONSOLIDASI TOTAL PORTOFOLIO (Semua Akun / Tier)    -->
            <!-- ================================================================= -->
            <div v-if="hasActiveAccounts || pendingAccounts.length > 0" class="space-y-4">
                <!-- KONDISI A: Ada Akun Aktif (3 Kartu Ringkasan Portofolio) -->
                <div
                    v-if="hasActiveAccounts"
                    class="grid grid-cols-1 items-stretch gap-4 sm:grid-cols-3"
                >
                    <!-- 1. TOTAL MODAL AWAL -->
                    <div
                        class="border-border bg-card flex h-full flex-col rounded-2xl border p-4 shadow-xs sm:p-5"
                    >
                        <div
                            class="border-border/60 flex items-center justify-between border-b pb-2.5"
                        >
                            <span
                                class="text-muted-foreground text-xs font-semibold tracking-wider uppercase"
                            >
                                TOTAL MODAL AWAL
                            </span>
                            <Wallet class="text-muted-foreground h-4 w-4" />
                        </div>

                        <div class="my-auto py-3">
                            <p class="text-foreground font-mono text-2xl leading-tight font-black">
                                {{ formatUSD(consolidated.total_initial_capital) }}
                            </p>
                            <span class="text-muted-foreground mt-1 block text-[11px]">
                                Total deposit bersih yang disetor
                            </span>
                        </div>
                    </div>

                    <!-- 2. PROGRES BALIK MODAL -->
                    <div
                        class="border-border bg-card flex h-full flex-col rounded-2xl border p-4 shadow-xs sm:p-5"
                    >
                        <div
                            class="border-border/60 flex items-center justify-between border-b pb-2.5"
                        >
                            <span
                                class="text-muted-foreground text-xs font-semibold tracking-wider uppercase"
                            >
                                PROGRES BALIK MODAL
                            </span>
                            <span class="text-foreground font-mono text-xs font-bold">
                                {{
                                    Math.min(
                                        100,
                                        Math.max(
                                            0,
                                            Number(consolidated.bep_summary.bep_progress_pct) || 0
                                        )
                                    ).toFixed(1)
                                }}%
                            </span>
                        </div>

                        <div class="my-auto flex items-center gap-3 py-3">
                            <!-- Mini Circular Gauge -->
                            <div class="relative flex shrink-0 items-center justify-center">
                                <svg class="h-16 w-16 -rotate-90 transform" viewBox="0 0 64 64">
                                    <circle
                                        cx="32"
                                        cy="32"
                                        r="25"
                                        stroke="currentColor"
                                        stroke-width="6"
                                        fill="transparent"
                                        class="text-muted/50"
                                    />
                                    <circle
                                        cx="32"
                                        cy="32"
                                        r="25"
                                        stroke="currentColor"
                                        stroke-width="6"
                                        fill="transparent"
                                        stroke-linecap="round"
                                        :stroke-dasharray="2 * Math.PI * 25"
                                        :stroke-dashoffset="
                                            2 * Math.PI * 25 -
                                            (Math.min(
                                                100,
                                                Math.max(
                                                    0,
                                                    Number(
                                                        consolidated.bep_summary.bep_progress_pct
                                                    ) || 0
                                                )
                                            ) /
                                                100) *
                                                (2 * Math.PI * 25)
                                        "
                                        :class="[
                                            'transition-all duration-700',
                                            consolidated.bep_summary.is_bep_achieved
                                                ? 'stroke-emerald-500 text-emerald-500'
                                                : 'stroke-sky-500 text-sky-500',
                                        ]"
                                    />
                                </svg>
                                <span
                                    class="text-foreground absolute inset-0 flex items-center justify-center font-mono text-[11px] font-black"
                                >
                                    {{
                                        Math.min(
                                            100,
                                            Math.max(
                                                0,
                                                Number(consolidated.bep_summary.bep_progress_pct) ||
                                                    0
                                            )
                                        ).toFixed(1)
                                    }}%
                                </span>
                            </div>

                            <div class="min-w-0 space-y-0.5">
                                <span class="text-muted-foreground block text-[11px] font-medium"
                                    >Tercapai / Target BEP</span
                                >
                                <p class="text-foreground truncate font-mono text-xs font-bold">
                                    {{
                                        formatUSD(consolidated.bep_summary.total_profit_accumulated)
                                    }}
                                    <span class="text-muted-foreground font-normal">
                                        / {{ formatUSD(consolidated.total_initial_capital) }}</span
                                    >
                                </p>
                                <span class="text-muted-foreground block truncate text-[10px]">
                                    {{
                                        consolidated.bep_summary.is_bep_achieved
                                            ? 'Modal 100% kembali'
                                            : `Sisa modal: ${formatUSD(consolidated.bep_summary.remaining_capital_to_bep)} lagi`
                                    }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- 3. TOTAL SALDO TERKINI -->
                    <div
                        class="border-border bg-card flex h-full flex-col rounded-2xl border p-4 shadow-xs sm:p-5"
                    >
                        <div
                            class="border-border/60 flex items-center justify-between border-b pb-2.5"
                        >
                            <span
                                class="text-muted-foreground text-xs font-semibold tracking-wider uppercase"
                            >
                                TOTAL SALDO TERKINI
                            </span>
                            <Activity class="text-muted-foreground h-4 w-4" />
                        </div>

                        <div class="my-auto py-3">
                            <p class="text-foreground font-mono text-2xl leading-tight font-black">
                                {{ formatUSD(consolidated.total_current_balance) }}
                            </p>
                            <div class="mt-1 flex flex-wrap items-center gap-1.5 text-xs">
                                <template v-if="consolidated.has_balance_history">
                                    <span
                                        :class="[
                                            'inline-flex items-center gap-1 font-mono font-bold',
                                            consolidated.total_all_time_profit >= 0
                                                ? 'text-emerald-700 dark:text-emerald-400'
                                                : 'text-destructive',
                                        ]"
                                    >
                                        <TrendingUp
                                            v-if="consolidated.total_all_time_profit >= 0"
                                            class="h-3.5 w-3.5"
                                        />
                                        <TrendingDown v-else class="h-3.5 w-3.5" />
                                        <span>
                                            {{ consolidated.total_all_time_profit >= 0 ? '+' : ''
                                            }}{{ formatUSD(consolidated.total_all_time_profit) }}
                                        </span>
                                        <span class="text-[11px]">
                                            ({{
                                                consolidated.total_all_time_growth_pct >= 0
                                                    ? '+'
                                                    : ''
                                            }}{{ consolidated.total_all_time_growth_pct }}%)
                                        </span>
                                    </span>
                                </template>
                                <span v-else class="text-muted-foreground text-[11px]">
                                    Equity gabungan saat ini
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- KONDISI B: Belum Ada Akun Aktif, Hanya Akun Pending (Kotak Informasi Menunggu Verifikasi) -->
                <div
                    v-else-if="pendingAccounts.length > 0"
                    class="space-y-4 rounded-2xl border border-amber-500/30 bg-amber-500/5 p-6 shadow-xs"
                >
                    <div class="flex items-start gap-4">
                        <div
                            class="mt-0.5 shrink-0 rounded-2xl bg-amber-500/10 p-3 text-amber-600 dark:text-amber-400"
                        >
                            <Clock class="h-6 w-6 animate-pulse" />
                        </div>
                        <div class="flex-1 space-y-1.5">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-foreground text-base font-bold">
                                    Akun Investasi Sedang Diverifikasi Administrator
                                </h3>
                            </div>
                            <p class="text-muted-foreground max-w-3xl text-xs leading-relaxed">
                                Anda telah mendaftarkan akun copytrade (investasi). Tim administrator saat ini
                                sedang memverifikasi dan dokumen kemitraan Anda.
                                Ringkasan statistik portofolio, analisis keuntungan, dan
                                kalkulasi balik modal (BEP) akan aktif secara otomatis setelah
                                permohonan disetujui.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ================================================================= -->
            <!-- 2. GRID KARTU AKUN INVESTASI (Card per Akun MT4)                  -->
            <!-- ================================================================= -->
            <div class="space-y-4">
                <div class="border-border/60 flex items-center justify-between gap-2 border-b pb-3">
                    <div class="flex items-center gap-2">
                        <Layers class="text-primary h-4 w-4" />
                        <h2 class="text-foreground text-sm font-bold tracking-tight">
                            Daftar Akun Investasi Anda
                        </h2>
                    </div>

                    <AppButton
                        type="create"
                        :icon="PlusCircle"
                        label="Tambah Akun Baru"
                        size="sm"
                        @click="openAddModal()"
                    />
                </div>

                <!-- Grid Kartu Akun -->
                <div
                    v-if="accounts.length > 0"
                    class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 xl:grid-cols-3"
                >
                    <!-- Kartu per Akun (Compact) -->
                    <div
                        v-for="acc in accounts"
                        :key="acc.id"
                        class="border-border bg-card flex flex-col justify-between space-y-3 rounded-xl border p-3.5 shadow-xs"
                    >
                        <!-- Header Kartu: Tier, Broker & Status Badge -->
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span
                                    :class="[
                                        'inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-bold tracking-wider uppercase',
                                        getTierBadge(acc.tier).bg,
                                    ]"
                                >
                                    {{ getTierBadge(acc.tier).shortLabel }}
                                </span>
                                <span
                                    class="border-border text-muted-foreground bg-muted/40 inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold"
                                >
                                    {{ acc.broker }}
                                </span>
                            </div>

                            <span
                                :class="[
                                    'inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-semibold',
                                    getStatusBadge(acc.status).bg,
                                ]"
                            >
                                <component :is="getStatusBadge(acc.status).icon" class="h-3 w-3" />
                                <span>{{ getStatusBadge(acc.status).label }}</span>
                            </span>
                        </div>

                        <!-- Nomor Akun Trading MT4 & Tanggal Mulai Investasi -->
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <span
                                    class="text-muted-foreground block text-[10px] font-medium tracking-wider uppercase"
                                >
                                    No. Akun MT4
                                </span>
                                <div class="mt-0.5 flex items-center gap-1.5">
                                    <p
                                        class="text-foreground font-mono text-base font-bold tracking-tight"
                                    >
                                        {{ acc.nomor_akun_trading }}
                                    </p>
                                    <button
                                        type="button"
                                        @click="
                                            copyToClipboard(acc.nomor_akun_trading, 'No. Akun MT4')
                                        "
                                        class="text-muted-foreground hover:text-foreground cursor-pointer rounded-md p-1 transition-colors"
                                        title="Salin No. Akun"
                                    >
                                        <Copy class="h-3.5 w-3.5" />
                                    </button>
                                </div>
                            </div>

                            <div class="text-right">
                                <span
                                    class="text-muted-foreground block text-[10px] font-medium tracking-wider uppercase"
                                >
                                    Mulai Investasi
                                </span>
                                <div
                                    class="text-foreground mt-0.5 flex items-center justify-end gap-1.5"
                                >
                                    <Calendar class="text-muted-foreground h-3 w-3 shrink-0" />
                                    <span class="text-xs font-semibold whitespace-nowrap">
                                        {{ acc.tanggal_mulai }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Data Finansial Akun (Compact Grid 3 Kolom) -->
                        <div
                            class="border-border/70 bg-muted/20 grid grid-cols-3 gap-2 rounded-lg border p-2.5"
                        >
                            <div>
                                <span class="text-muted-foreground block text-[10px] font-medium"
                                    >Modal Awal</span
                                >
                                <p class="text-foreground mt-0.5 font-mono text-xs font-bold">
                                    {{ acc.modal_riil_formatted }}
                                </p>
                            </div>

                            <div>
                                <span class="text-muted-foreground block text-[10px] font-medium"
                                    >Saldo Terkini</span
                                >
                                <p
                                    v-if="acc.status === 'active'"
                                    class="text-foreground mt-0.5 font-mono text-xs font-bold"
                                >
                                    {{ formatUSD(acc.current_balance) }}
                                </p>
                                <p
                                    v-else
                                    class="mt-0.5 text-[10px] font-medium text-amber-700 dark:text-amber-400"
                                >
                                    Pending
                                </p>
                            </div>

                            <div>
                                <span class="text-muted-foreground block text-[10px] font-medium"
                                    >Laba / Rugi</span
                                >
                                <p
                                    v-if="acc.status === 'active'"
                                    :class="[
                                        'mt-0.5 truncate font-mono text-xs font-bold',
                                        acc.profit_nominal >= 0
                                            ? 'text-emerald-700 dark:text-emerald-300'
                                            : 'text-destructive',
                                    ]"
                                    :title="`${acc.profit_nominal >= 0 ? '+' : ''}${formatUSD(acc.profit_nominal)} (${acc.growth_pct >= 0 ? '+' : ''}${acc.growth_pct}%)`"
                                >
                                    {{ acc.profit_nominal >= 0 ? '+' : ''
                                    }}{{ formatUSD(acc.profit_nominal) }}
                                </p>
                                <p v-else class="text-muted-foreground mt-0.5 text-[10px]">-</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- KONDISI EMPTY STATE: Belum Ada Akun Investasi Sama Sekali -->
                <div v-else class="border-border bg-card rounded-2xl border p-6 shadow-xs sm:p-8">
                    <div class="mx-auto max-w-2xl text-center">
                        <div
                            class="bg-primary/10 text-primary mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl"
                        >
                            <Layers class="h-7 w-7" />
                        </div>
                        <h3 class="text-foreground text-base font-bold tracking-tight sm:text-lg">
                            Belum Ada Pengajuan Akun Investasi
                        </h3>
                        <p class="text-muted-foreground mt-2 text-xs leading-relaxed sm:text-sm">
                            Anda belum mendaftarkan akun copytrade (investasi) untuk dikelola dalam
                            sistem YZD Center. Daftarkan akun investasi Anda sekarang untuk mulai
                            pemantauan portofolio, dan kalkulasi balik modal (BEP).
                        </p>

                        <div class="mt-5 flex flex-wrap items-center justify-center gap-3">
                            <AppButton
                                type="create"
                                :icon="PlusCircle"
                                label="Ajukan Akun Investasi Baru"
                                @click="openAddModal()"
                            />
                            <Link
                                href="/member/education/help?kategori=investasi-tiering"
                                target="_blank"
                                class="border-border bg-background hover:bg-muted text-foreground inline-flex min-h-[40px] cursor-pointer items-center gap-2 rounded-xl border px-4 text-xs font-semibold shadow-xs transition-colors"
                            >
                                <HelpCircle class="h-4 w-4 text-sky-600 dark:text-sky-400" />
                                <span>Panduan Pendaftaran &amp; Tiering</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ================================================================= -->
            <!-- 3. KINERJA & RIWAYAT SALDO AKUN                                   -->
            <!-- ================================================================= -->
            <div
                v-if="selectedAccountData"
                id="kinerja-dan-riwayat-akun"
                class="scroll-mt-6 space-y-4"
            >
                <div
                    class="border-border/60 flex flex-col justify-between gap-3 border-b pb-3 sm:flex-row sm:items-center"
                >
                    <div class="flex items-center gap-2">
                        <LineChart class="text-primary h-4 w-4" />
                        <h2 class="text-foreground text-sm font-bold tracking-tight">
                            Kinerja & Riwayat Akun
                        </h2>
                    </div>

                    <!-- Dropdown Pemilihan Akun (Mandiri & Terpisah dari Daftar Akun) -->
                    <div v-if="accounts.length > 1" class="flex items-center gap-2">
                        <label
                            for="select-account-kinerja"
                            class="text-muted-foreground text-xs font-semibold whitespace-nowrap"
                        >
                            Pilih Akun:
                        </label>
                        <select
                            id="select-account-kinerja"
                            :value="selectedAccountId"
                            @change="selectAccount(Number($event.target.value))"
                            class="bg-background border-input focus:ring-primary text-foreground cursor-pointer rounded-lg border px-3 py-1.5 text-xs font-semibold focus:ring-2 focus:outline-none"
                        >
                            <option v-for="acc in accounts" :key="acc.id" :value="acc.id">
                                No. {{ acc.nomor_akun_trading }} — {{ acc.broker }} ({{
                                    getTierBadge(acc.tier).shortLabel
                                }}){{ acc.status === 'pending' ? ' • Pending' : '' }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- JIKA AKUN TERPILIH BERSTATUS PENDING -->
                <div
                    v-if="selectedAccountData.status === 'pending'"
                    class="bg-card space-y-4 rounded-2xl border border-amber-500/30 p-6 shadow-xs"
                >
                    <div class="flex items-start gap-4">
                        <div
                            class="shrink-0 rounded-2xl bg-amber-500/10 p-3 text-amber-600 dark:text-amber-400"
                        >
                            <Clock class="h-6 w-6" />
                        </div>
                        <div class="flex-1 space-y-1.5">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-foreground text-base font-bold">
                                    Akun #{{ selectedAccountData.nomor_akun_trading }} — Sedang
                                    Dalam Peninjauan
                                </h3>
                                <span
                                    class="border-border text-muted-foreground bg-muted/40 inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold"
                                >
                                    {{ selectedAccountData.broker }}
                                </span>
                                <span
                                    :class="[
                                        'rounded-full border px-2 py-0.5 text-[10px] font-bold',
                                        getTierBadge(selectedAccountData.tier).bg,
                                    ]"
                                >
                                    {{ getTierBadge(selectedAccountData.tier).shortLabel }}
                                </span>
                            </div>
                            <p class="text-muted-foreground max-w-3xl text-xs leading-relaxed">
                                Akun ini telah didaftarkan dan sedang menunggu verifikasi
                                oleh Administrator. Evaluasi kinerja bulanan, grafik tren saldo,
                                dan formulir pencatatan saldo tanggal 1 akan otomatis aktif segera
                                setelah permohonan disetujui.
                            </p>
                        </div>
                    </div>

                    <div
                        class="border-border bg-muted/20 grid grid-cols-2 gap-3 rounded-xl border p-4 text-xs sm:grid-cols-3"
                    >
                        <div>
                            <span class="text-muted-foreground block text-[10px] font-medium"
                                >Modal Awal Terdaftar</span
                            >
                            <span class="text-foreground font-mono font-bold">{{
                                selectedAccountData.modal_riil_formatted
                            }}</span>
                        </div>
                        <div>
                            <span class="text-muted-foreground block text-[10px] font-medium"
                                >Broker & Tipe</span
                            >
                            <span class="text-foreground font-semibold"
                                >{{ selectedAccountData.broker }} ({{
                                    selectedAccountData.tipe_akun
                                }})</span
                            >
                        </div>
                        <div>
                            <span class="text-muted-foreground block text-[10px] font-medium"
                                >Tanggal Pengajuan</span
                            >
                            <span class="text-foreground font-semibold">{{
                                selectedAccountData.tanggal_mulai
                            }}</span>
                        </div>
                    </div>
                </div>

                <!-- JIKA AKUN TERPILIH BERSTATUS AKTIF -->
                <div v-else-if="selectedAccountData.status === 'active'" class="space-y-6">
                    <div
                        class="border-border bg-card space-y-6 rounded-2xl border p-5 shadow-xs sm:p-6"
                    >
                        <!-- Header Seksi Kinerja Akun Terpilih -->
                        <div
                            class="border-border/60 flex flex-col justify-between gap-4 border-b pb-5 sm:flex-row sm:items-center"
                        >
                            <div class="flex items-start gap-3.5 sm:items-center">
                                <div
                                    class="bg-primary/10 text-primary mt-0.5 shrink-0 rounded-xl p-2.5 sm:mt-0"
                                >
                                    <LineChart class="h-5 w-5" />
                                </div>
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="text-foreground text-base font-bold">
                                            Akun #{{ selectedAccountData.nomor_akun_trading }}
                                        </h3>
                                        <span
                                            :class="[
                                                'rounded-full border px-2 py-0.5 text-[10px] font-bold',
                                                getTierBadge(selectedAccountData.tier).bg,
                                            ]"
                                        >
                                            {{ getTierBadge(selectedAccountData.tier).shortLabel }}
                                        </span>
                                        <span
                                            class="border-border text-muted-foreground bg-muted/40 inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold"
                                        >
                                            {{ selectedAccountData.broker }}
                                        </span>
                                    </div>
                                    <p class="text-muted-foreground mt-1 text-xs">
                                        Mulai Investasi:
                                        <span class="text-foreground font-medium">{{
                                            selectedAccountData.tanggal_mulai
                                        }}</span>
                                        &bull; Modal Awal:
                                        <span class="text-foreground font-medium">{{
                                            selectedAccountData.modal_riil_formatted
                                        }}</span>
                                        &bull; Saldo Terkini:
                                        <span class="text-foreground font-mono font-medium">{{
                                            formatUSD(selectedAccountData.current_balance)
                                        }}</span>
                                    </p>
                                </div>
                            </div>

                            <div class="flex shrink-0 items-center gap-2 self-start sm:self-center">
                                <AppButton
                                    type="create"
                                    label="Input Saldo Tanggal 1"
                                    size="sm"
                                    @click="openBalanceModalForAccount(selectedAccountId)"
                                />
                            </div>
                        </div>

                        <!-- Filter Rentang Periode Analisis -->
                        <div class="space-y-3">
                            <div
                                class="flex flex-col justify-between gap-2 sm:flex-row sm:items-center"
                            >
                                <span class="text-muted-foreground text-xs font-semibold"
                                    >Periode Analisis:</span
                                >
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <button
                                        v-for="p in availablePeriods"
                                        :key="p.key"
                                        type="button"
                                        @click="setPeriod(p.key)"
                                        :class="[
                                            'cursor-pointer rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors',
                                            selectedPeriod === p.key
                                                ? 'bg-primary text-primary-foreground shadow-xs'
                                                : 'bg-muted/40 hover:bg-muted text-muted-foreground hover:text-foreground border-border/60 border',
                                        ]"
                                    >
                                        {{ p.label }}
                                    </button>
                                </div>
                            </div>

                            <!-- Pilihan Bulan & Tahun Kustom (<select> Dropdown) -->
                            <div
                                v-if="isCustomDateOpen"
                                class="bg-muted/30 border-border flex flex-wrap items-center gap-3.5 rounded-xl border p-3.5"
                            >
                                <!-- Dari Bulan & Tahun -->
                                <div class="flex flex-wrap items-center gap-2">
                                    <label
                                        for="custom-start-month"
                                        class="text-muted-foreground text-xs font-semibold whitespace-nowrap"
                                    >
                                        Dari:
                                    </label>
                                    <select
                                        id="custom-start-month"
                                        v-model="customStartMonth"
                                        class="bg-card border-border text-foreground focus:ring-primary cursor-pointer rounded-lg border px-2.5 py-1.5 text-xs font-medium focus:ring-1 focus:outline-none"
                                    >
                                        <option
                                            v-for="m in monthsList"
                                            :key="m.value"
                                            :value="m.value"
                                        >
                                            {{ m.label }}
                                        </option>
                                    </select>
                                    <input
                                        id="custom-start-year"
                                        v-model.number="customStartYear"
                                        type="number"
                                        min="2020"
                                        max="2099"
                                        placeholder="YYYY"
                                        class="bg-card border-border text-foreground focus:ring-primary w-20 rounded-lg border px-2.5 py-1.5 font-mono text-xs font-medium focus:ring-1 focus:outline-none"
                                    />
                                </div>

                                <span class="text-muted-foreground/50 hidden sm:inline"
                                    >&mdash;</span
                                >

                                <!-- Sampai Bulan & Tahun -->
                                <div class="flex flex-wrap items-center gap-2">
                                    <label
                                        for="custom-end-month"
                                        class="text-muted-foreground text-xs font-semibold whitespace-nowrap"
                                    >
                                        Sampai:
                                    </label>
                                    <select
                                        id="custom-end-month"
                                        v-model="customEndMonth"
                                        class="bg-card border-border text-foreground focus:ring-primary cursor-pointer rounded-lg border px-2.5 py-1.5 text-xs font-medium focus:ring-1 focus:outline-none"
                                    >
                                        <option
                                            v-for="m in monthsList"
                                            :key="m.value"
                                            :value="m.value"
                                        >
                                            {{ m.label }}
                                        </option>
                                    </select>
                                    <input
                                        id="custom-end-year"
                                        v-model.number="customEndYear"
                                        type="number"
                                        min="2020"
                                        max="2099"
                                        placeholder="YYYY"
                                        class="bg-card border-border text-foreground focus:ring-primary w-20 rounded-lg border px-2.5 py-1.5 font-mono text-xs font-medium focus:ring-1 focus:outline-none"
                                    />
                                </div>

                                <Button
                                    type="button"
                                    size="sm"
                                    @click="applyFilter"
                                    class="bg-primary hover:bg-primary/90 text-primary-foreground h-8 cursor-pointer rounded-lg px-4 py-1 text-xs font-bold shadow-xs"
                                >
                                    Terapkan
                                </Button>
                            </div>
                        </div>

                        <!-- 4 Kartu Metrik Periode Terpilih -->
                        <div v-if="summary" class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                            <div class="border-border bg-background/60 rounded-xl border p-3.5">
                                <span class="text-muted-foreground block text-[11px] font-semibold"
                                    >Saldo Awal Periode</span
                                >
                                <p
                                    class="text-foreground mt-0.5 font-mono text-base font-bold sm:text-lg"
                                >
                                    {{ formatUSD(summary.period_start_balance) }}
                                </p>
                                <span class="text-muted-foreground block truncate text-[10px]">
                                    Saldo Awal Rentang
                                </span>
                            </div>

                            <div class="border-border bg-background/60 rounded-xl border p-3.5">
                                <span class="text-muted-foreground block text-[11px] font-semibold"
                                    >Saldo Akhir Periode</span
                                >
                                <p
                                    class="text-foreground mt-0.5 font-mono text-base font-bold sm:text-lg"
                                >
                                    {{ formatUSD(summary.period_end_balance) }}
                                </p>
                                <span class="text-muted-foreground block truncate text-[10px]">
                                    Saldo Akhir Rentang
                                </span>
                            </div>

                            <div
                                :class="[
                                    'rounded-xl border p-3.5',
                                    summary.period_profit_nominal >= 0
                                        ? 'border-emerald-500/30 bg-emerald-500/10'
                                        : 'border-destructive/30 bg-destructive/10',
                                ]"
                            >
                                <span class="text-muted-foreground block text-[11px] font-semibold"
                                    >Laba Periode Ini</span
                                >
                                <p
                                    :class="[
                                        'mt-0.5 font-mono text-base font-bold sm:text-lg',
                                        summary.period_profit_nominal >= 0
                                            ? 'text-emerald-700 dark:text-emerald-300'
                                            : 'text-destructive',
                                    ]"
                                >
                                    {{ summary.period_profit_nominal >= 0 ? '+' : ''
                                    }}{{ formatUSD(summary.period_profit_nominal) }}
                                </p>
                                <span class="text-muted-foreground block truncate text-[10px]">
                                    Nominal Profit Riil
                                </span>
                            </div>

                            <div class="border-border bg-background/60 rounded-xl border p-3.5">
                                <span class="text-muted-foreground block text-[11px] font-semibold"
                                    >Pertumbuhan & Rata-rata</span
                                >
                                <p
                                    :class="[
                                        'mt-0.5 font-mono text-base font-bold sm:text-lg',
                                        summary.period_growth_pct >= 0
                                            ? 'text-emerald-700 dark:text-emerald-300'
                                            : 'text-destructive',
                                    ]"
                                >
                                    {{ summary.period_growth_pct >= 0 ? '+' : ''
                                    }}{{ summary.period_growth_pct }}%
                                </p>
                                <span class="text-muted-foreground block truncate text-[10px]">
                                    Rata-rata {{ summary.avg_monthly_profit_pct }}% / bln
                                </span>
                            </div>
                        </div>

                        <!-- Grafik Tren Saldo Bulanan (SVG Chart) -->
                        <div
                            v-if="chartPoints.length >= 2"
                            class="border-border bg-background/40 space-y-3 rounded-xl border p-4 sm:p-5"
                        >
                            <div
                                class="border-border/50 flex flex-wrap items-center justify-between gap-2 border-b pb-2.5"
                            >
                                <div class="flex items-center gap-2">
                                    <Activity class="text-primary h-4 w-4" />
                                    <span class="text-foreground text-xs font-bold sm:text-sm">
                                        Grafik Tren Saldo Bulanan
                                    </span>
                                    <span
                                        class="bg-primary/10 text-primary border-primary/20 rounded-md border px-2 py-0.5 font-mono text-[10px] font-semibold"
                                    >
                                        {{ summary?.history?.length || 0 }} Titik Rekaman
                                    </span>
                                </div>

                                <span
                                    class="text-muted-foreground hidden items-center gap-1.5 text-[11px] font-medium sm:inline-flex"
                                >
                                    <Info class="text-muted-foreground h-3.5 w-3.5" />
                                    <span>Sorot titik grafik untuk rincian saldo & laba MoM</span>
                                </span>
                            </div>

                            <!-- Area Grafik Scrollable & Responsif -->
                            <div
                                ref="chartScrollContainer"
                                class="w-full overflow-x-auto scroll-smooth pb-1"
                            >
                                <div
                                    class="relative h-[240px]"
                                    :style="{ width: `${chartWidth}px`, minWidth: '100%' }"
                                >
                                    <!-- Tooltip Popover Interaktif -->
                                    <div
                                        v-if="hoveredPoint"
                                        class="border-border bg-popover/95 pointer-events-none absolute z-30 rounded-xl border p-2.5 text-xs shadow-lg backdrop-blur-md transition-all duration-100"
                                        :style="{
                                            left: `${hoveredPoint.x}px`,
                                            top: `${Math.max(8, hoveredPoint.y - 82)}px`,
                                            transform:
                                                hoveredPoint.x < 110
                                                    ? 'translateX(-10%)'
                                                    : hoveredPoint.x > chartWidth - 110
                                                      ? 'translateX(-90%)'
                                                      : 'translateX(-50%)',
                                        }"
                                    >
                                        <div
                                            class="text-muted-foreground mb-1 flex items-center gap-1.5 text-[10px]"
                                        >
                                            <Calendar class="h-3 w-3" />
                                            <span>{{ hoveredPoint.fullDate }}</span>
                                        </div>
                                        <div class="font-mono">
                                            <span class="text-foreground text-sm font-bold">
                                                {{ formatUSD(hoveredPoint.balance) }}
                                            </span>
                                        </div>
                                        <div
                                            v-if="
                                                hoveredPoint.profitMoM !== undefined &&
                                                hoveredPoint.idx > 0
                                            "
                                            class="mt-1 flex items-center gap-1 text-[10px] font-semibold"
                                            :class="profitTooltipClass(hoveredPoint.profitMoM)"
                                        >
                                            <component
                                                :is="profitIcon(hoveredPoint.profitMoM)"
                                                class="h-3 w-3"
                                            />
                                            <span>
                                                {{ Number(hoveredPoint.profitMoM) > 0 ? '+' : ''
                                                }}{{ formatUSD(hoveredPoint.profitMoM) }} ({{
                                                    Number(hoveredPoint.profitPct) > 0 ? '+' : ''
                                                }}{{ hoveredPoint.profitPct }}%) MoM
                                            </span>
                                        </div>
                                    </div>

                                    <!-- SVG Grafik Utama -->
                                    <svg
                                        :viewBox="`0 0 ${chartWidth} 240`"
                                        class="h-full w-full overflow-visible"
                                    >
                                        <defs>
                                            <linearGradient
                                                id="balanceGradient"
                                                x1="0"
                                                y1="0"
                                                x2="0"
                                                y2="1"
                                            >
                                                <stop
                                                    offset="0%"
                                                    stop-color="var(--color-primary, #3b82f6)"
                                                    stop-opacity="0.28"
                                                />
                                                <stop
                                                    offset="100%"
                                                    stop-color="var(--color-primary, #3b82f6)"
                                                    stop-opacity="0.0"
                                                />
                                            </linearGradient>
                                        </defs>

                                        <!-- Horizontal Reference Gridlines & Y-Axis Labels -->
                                        <g v-for="(lvl, lIdx) in yAxisLevels" :key="`lvl-${lIdx}`">
                                            <line
                                                :x1="chartPaddingLeft"
                                                :y1="lvl.y"
                                                :x2="chartWidth - chartPaddingRight"
                                                :y2="lvl.y"
                                                stroke="currentColor"
                                                class="text-border/40"
                                                stroke-width="1"
                                                stroke-dasharray="4 4"
                                            />
                                            <text
                                                :x="chartPaddingLeft - 8"
                                                :y="lvl.y + 3"
                                                text-anchor="end"
                                                class="fill-muted-foreground font-mono text-[9px] font-semibold"
                                            >
                                                ${{ Number(lvl.val).toLocaleString('id-ID') }}
                                            </text>
                                        </g>

                                        <!-- Garis Basis X-Axis -->
                                        <line
                                            :x1="chartPaddingLeft"
                                            y1="198"
                                            :x2="chartWidth - chartPaddingRight"
                                            y2="198"
                                            stroke="currentColor"
                                            class="text-border/70"
                                            stroke-width="1"
                                        />

                                        <!-- Area Gradient Saldo -->
                                        <path :d="chartAreaD" fill="url(#balanceGradient)" />

                                        <!-- Garis Kurva Saldo -->
                                        <path
                                            :d="chartPathD"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2.5"
                                            class="text-primary"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        />

                                        <!-- Garis Vertikal Indikator Saat Titik Di-Hover -->
                                        <line
                                            v-if="hoveredPoint"
                                            :x1="hoveredPoint.x"
                                            :y1="32"
                                            :x2="hoveredPoint.x"
                                            :y2="198"
                                            stroke="currentColor"
                                            class="text-primary/50"
                                            stroke-width="1.5"
                                            stroke-dasharray="3 3"
                                        />

                                        <!-- Kolom Area Hitbox Interaktif (Mudah disentuh & di-hover) -->
                                        <rect
                                            v-for="(pt, idx) in chartPoints"
                                            :key="`hitbox-${idx}`"
                                            :x="pt.x - stepX / 2"
                                            y="0"
                                            :width="stepX"
                                            height="240"
                                            fill="transparent"
                                            style="pointer-events: all"
                                            class="cursor-pointer"
                                            @mouseenter="hoveredIndex = idx"
                                            @mouseleave="hoveredIndex = null"
                                            @click="
                                                activeClickedIndex =
                                                    activeClickedIndex === idx ? null : idx
                                            "
                                        />

                                        <!-- Titik Lingkaran Data -->
                                        <g
                                            v-for="(pt, idx) in chartPoints"
                                            :key="`pt-${idx}`"
                                            class="pointer-events-none"
                                        >
                                            <!-- Pulse Ring untuk Titik Terkini -->
                                            <circle
                                                v-if="pt.isLatest && activeHoveredIndex !== idx"
                                                :cx="pt.x"
                                                :cy="pt.y"
                                                r="9"
                                                class="fill-primary/20 animate-pulse"
                                            />
                                            <!-- Titik Utama -->
                                            <circle
                                                :cx="pt.x"
                                                :cy="pt.y"
                                                :r="
                                                    activeHoveredIndex === idx
                                                        ? 6.5
                                                        : pt.isLatest
                                                          ? 5.5
                                                          : 4.5
                                                "
                                                :class="[
                                                    activeHoveredIndex === idx
                                                        ? 'fill-primary stroke-background stroke-2'
                                                        : pt.isLatest
                                                          ? 'fill-primary stroke-background stroke-2'
                                                          : 'fill-background stroke-primary stroke-2',
                                                ]"
                                                class="transition-all duration-150"
                                            />
                                        </g>

                                        <!-- Label Angka Saldo di Atas Titik -->
                                        <template
                                            v-for="(pt, idx) in chartPoints"
                                            :key="`label-${idx}`"
                                        >
                                            <g
                                                v-if="
                                                    shouldShowPointLabel(pt) ||
                                                    activeHoveredIndex === idx
                                                "
                                                class="pointer-events-none"
                                            >
                                                <!-- Background Pill agar teks tidak terpotong garis -->
                                                <rect
                                                    :x="pt.x - 28"
                                                    :y="pt.y - 25"
                                                    width="56"
                                                    height="16"
                                                    rx="4"
                                                    class="fill-background/95 stroke-border/60 stroke-[0.5px]"
                                                />
                                                <text
                                                    :x="pt.x"
                                                    :y="pt.y - 14"
                                                    text-anchor="middle"
                                                    class="font-mono text-[9px] font-bold"
                                                    :class="[
                                                        pt.isLatest
                                                            ? 'fill-primary'
                                                            : pt.isMax
                                                              ? 'fill-emerald-600 dark:fill-emerald-400'
                                                              : pt.isMin
                                                                ? 'fill-rose-600 dark:fill-rose-400'
                                                                : 'fill-foreground',
                                                    ]"
                                                >
                                                    ${{
                                                        Number(pt.balance).toLocaleString('id-ID')
                                                    }}
                                                </text>
                                            </g>
                                        </template>

                                        <!-- Label Tanggal Sumbu X di Bawah Garis Basis -->
                                        <template
                                            v-for="(pt, idx) in chartPoints"
                                            :key="`date-${idx}`"
                                        >
                                            <!-- Garis Centang Sumbu (Tick Mark) -->
                                            <line
                                                :x1="pt.x"
                                                y1="198"
                                                :x2="pt.x"
                                                y2="203"
                                                stroke="currentColor"
                                                class="text-border"
                                                stroke-width="1"
                                            />
                                            <!-- Label Tanggal Ringkas (Sep '25) -->
                                            <text
                                                v-if="shouldShowDateLabel(idx, chartPoints.length)"
                                                :x="pt.x"
                                                y="218"
                                                text-anchor="middle"
                                                class="fill-muted-foreground font-mono text-[9px] font-medium"
                                            >
                                                {{ pt.shortDate }}
                                            </text>
                                        </template>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Notifikasi Jika Baru 1 Data Point (Ringkas & Bermanfaat) -->
                        <div
                            v-else-if="summary?.history?.length === 1"
                            class="flex items-center gap-3 rounded-xl border border-sky-500/30 bg-sky-500/10 p-3.5 text-xs text-sky-950 dark:text-sky-200"
                        >
                            <Sparkles class="h-4 w-4 shrink-0 text-sky-600 dark:text-sky-400" />
                            <p class="leading-relaxed">
                                Tercatat 1 titik saldo (<strong>{{
                                    formatUSD(summary.history[0].balance)
                                }}</strong>
                                pada {{ summary.history[0].tanggal_formatted }}). Grafik garis tren
                                otomatis aktif setelah Anda mencatat saldo pada bulan kedua.
                            </p>
                        </div>

                        <!-- ============================================================= -->
                        <!-- TABEL RIWAYAT SALDO TANGGAL 1 (Akun Terpilih)                 -->
                        <!-- ============================================================= -->
                        <div class="border-border/60 space-y-3 border-t pt-2">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <Calendar class="text-primary h-4 w-4" />
                                    <h4 class="text-foreground text-xs font-bold sm:text-sm">
                                        Riwayat Catatan Saldo & Keuntungan Month-on-Month (MoM)
                                    </h4>
                                </div>
                                <span class="text-muted-foreground font-mono text-[11px]">
                                    {{ summary?.history?.length || 0 }} Catatan
                                </span>
                            </div>

                            <!-- State Jika Belum Ada Catatan Riwayat Saldo -->
                            <div
                                v-if="!summary || !summary.history || summary.history.length === 0"
                                class="border-border space-y-3 rounded-xl border border-dashed p-8 text-center"
                            >
                                <Clock class="text-muted-foreground mx-auto h-8 w-8 opacity-50" />
                                <div class="space-y-1">
                                    <p class="text-foreground text-xs font-bold">
                                        Belum Ada Catatan Saldo Bulanan
                                    </p>
                                    <p class="text-muted-foreground mx-auto max-w-md text-[11px]">
                                        Mulai catat saldo Anda setiap tanggal 1 untuk memantau
                                        performa keuntungan bulanan (MoM) dan kalkulasi balik modal
                                        (BEP).
                                    </p>
                                </div>
                                <AppButton
                                    type="create"
                                    label="Input Saldo Pertama Sekarang"
                                    size="sm"
                                    @click="openBalanceModalForAccount(selectedAccountId)"
                                />
                            </div>

                            <!-- Tabel Riwayat (Jika Ada Catatan) -->
                            <div v-else class="border-border/60 overflow-x-auto rounded-xl border">
                                <table class="w-full text-left text-xs">
                                    <thead>
                                        <tr
                                            class="border-border bg-muted/40 text-muted-foreground border-b text-[11px]"
                                        >
                                            <th class="px-3 py-2.5 font-semibold">
                                                Tanggal Pencatatan
                                            </th>
                                            <th class="px-3 py-2.5 font-mono font-semibold">
                                                Saldo (USD)
                                            </th>
                                            <th class="px-3 py-2.5 font-semibold">
                                                Mutasi Dana
                                            </th>
                                            <th class="px-3 py-2.5 font-semibold">
                                                Profit Bersih MoM
                                            </th>
                                            <th class="px-3 py-2.5 font-semibold">
                                                Tagihan Master Fee
                                            </th>
                                            <th class="px-3 py-2.5 text-center font-semibold">
                                                Bukti Saldo
                                            </th>
                                            <th class="px-3 py-2.5 text-center font-semibold">
                                                Aksi
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-border/60 bg-card divide-y">
                                        <tr
                                            v-for="rec in summary.history"
                                            :key="rec.id"
                                            class="hover:bg-muted/30 transition-colors"
                                        >
                                            <td
                                                class="text-foreground px-3 py-3 font-medium whitespace-nowrap"
                                            >
                                                {{ rec.tanggal_formatted }}
                                            </td>
                                            <td
                                                class="text-foreground px-3 py-3 font-mono font-bold whitespace-nowrap"
                                            >
                                                {{ formatUSD(rec.balance) }}
                                            </td>
                                            <td class="px-3 py-3 whitespace-nowrap font-mono text-[11px]">
                                                <div v-if="Number(rec.penarikan) > 0" class="text-rose-600 dark:text-rose-400">
                                                    Tarik: -{{ formatUSD(rec.penarikan) }}
                                                </div>
                                                <div v-if="Number(rec.setoran_tambahan) > 0" class="text-emerald-600 dark:text-emerald-400">
                                                    Setor: +{{ formatUSD(rec.setoran_tambahan) }}
                                                </div>
                                                <span v-if="!Number(rec.penarikan) && !Number(rec.setoran_tambahan)" class="text-muted-foreground">-</span>
                                            </td>
                                            <td class="px-3 py-3 whitespace-nowrap">
                                                <span
                                                    :class="[
                                                        'inline-flex items-center gap-1 font-mono font-bold',
                                                        profitRowClass(rec.keuntungan_nominal),
                                                    ]"
                                                >
                                                    <component
                                                        :is="profitIcon(rec.keuntungan_nominal)"
                                                        class="h-3 w-3"
                                                    />
                                                    <span
                                                        >{{ Number(rec.keuntungan_nominal) > 0 ? '+' : ''
                                                        }}{{
                                                            formatUSD(rec.keuntungan_nominal)
                                                        }}</span
                                                    >
                                                    <span class="text-[10px]"
                                                        >({{ Number(rec.keuntungan_persen) > 0 ? '+' : ''
                                                        }}{{ rec.keuntungan_persen }}%)</span
                                                    >
                                                </span>
                                            </td>
                                            <td class="px-3 py-3 whitespace-nowrap">
                                                <div v-if="rec.tagihan_master_fee" class="flex flex-col gap-0.5">
                                                    <Link
                                                        href="/member/master-fee"
                                                        class="font-mono font-bold text-xs hover:underline text-foreground inline-flex items-center gap-1"
                                                    >
                                                        <span>{{ formatUSD(rec.tagihan_master_fee.nominal_fee) }}</span>
                                                    </Link>
                                                    <span
                                                        :class="[
                                                            'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold w-fit',
                                                            rec.tagihan_master_fee.status === 'lunas'
                                                                ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400'
                                                                : rec.tagihan_master_fee.status === 'menunggu_verifikasi'
                                                                  ? 'bg-amber-500/10 text-amber-700 dark:text-amber-400'
                                                                  : rec.tagihan_master_fee.is_terlambat
                                                                    ? 'bg-rose-500/10 text-rose-700 dark:text-rose-400 font-bold'
                                                                    : 'bg-slate-500/10 text-slate-700 dark:text-slate-300'
                                                        ]"
                                                    >
                                                        {{ rec.tagihan_master_fee.is_terlambat ? 'Terlambat' : rec.tagihan_master_fee.status_label }}
                                                    </span>
                                                </div>
                                                <span v-else class="text-muted-foreground text-[11px] italic">
                                                    Tanpa Fee (Rugi/Impas)
                                                </span>
                                            </td>
                                            <td class="px-3 py-3 text-center whitespace-nowrap">
                                                <a
                                                    v-if="rec.bukti_saldo_path"
                                                    :href="route('documents.balance_proof', rec.id)"
                                                    target="_blank"
                                                    class="inline-flex items-center gap-1 text-[11px] font-medium text-primary hover:underline"
                                                    title="Lihat Bukti Saldo"
                                                >
                                                    <Eye class="h-3.5 w-3.5" />
                                                    <span>Lihat</span>
                                                </a>
                                                <span v-else class="text-muted-foreground text-[11px]">-</span>
                                            </td>
                                            <td class="px-3 py-3 text-center whitespace-nowrap">
                                                <div
                                                    v-if="rec.tagihan_master_fee?.is_locked"
                                                    class="inline-flex items-center gap-1 rounded-md bg-muted/60 px-2 py-1 text-[11px] font-medium text-muted-foreground"
                                                    title="Rekap terkunci karena tagihan Master Fee sedang diverifikasi atau sudah lunas"
                                                >
                                                    <Lock class="h-3 w-3 text-amber-500" />
                                                    <span>Terkunci</span>
                                                </div>
                                                <div
                                                    v-else
                                                    class="flex items-center justify-center gap-1.5"
                                                >
                                                    <AppTableAction
                                                        type="edit"
                                                        title="Edit Saldo"
                                                        @click="openEditBalanceModal(rec)"
                                                    />
                                                    <AppTableAction
                                                        type="delete"
                                                        title="Hapus Saldo"
                                                        @click="confirmDeleteRecord(rec)"
                                                    />
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===================================================================== -->
        <!-- MODAL: Tambah Akun Investasi                                          -->
        <!-- ===================================================================== -->
        <AddInvestmentAccountModal
            :show="isAddModalOpen"
            :has-existing="has_existing || accounts.length > 0"
            :default-tier="selectedTierForAdd"
            @close="isAddModalOpen = false"
        />

        <!-- ===================================================================== -->
        <!-- MODAL: Input Saldo Tanggal 1 (Hanya Akun Aktif)                       -->
        <!-- ===================================================================== -->
        <AppModal
            :show="isBalanceModalOpen"
            :title="isEditingBalance ? 'Edit Catatan Saldo MT4' : 'Input Saldo MT4 Tanggal 1'"
            max-width="md"
            @close="closeBalanceModal"
        >
            <form @submit.prevent="submitBalance" class="space-y-4 p-1">
                <!-- Banner Terkunci (Jika Rekap Terkunci) -->
                <div
                    v-if="isRecordLocked"
                    class="flex items-start gap-2.5 rounded-xl border border-amber-500/30 bg-amber-500/10 p-3 text-xs text-amber-900 dark:text-amber-200"
                >
                    <Lock class="h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400 mt-0.5" />
                    <div>
                        <p class="font-bold">Rekap Saldo Terkunci</p>
                        <p class="text-[11px] leading-relaxed text-amber-800/90 dark:text-amber-300/90 mt-0.5">
                            Rekap saldo ini tidak dapat diubah atau dihapus karena tagihan Master Fee sedang diverifikasi atau sudah lunas.
                        </p>
                    </div>
                </div>

                <!-- Informasi Akun Trading (Read-only / Bersifat Informasi Saja) -->
                <div class="space-y-1.5">
                    <label class="text-muted-foreground block text-xs font-semibold">
                        Akun Trading MT4
                    </label>
                    <div
                        v-if="modalAccount"
                        class="bg-muted/40 border-border flex items-center justify-between rounded-xl border p-3"
                    >
                        <div class="flex min-w-0 items-center gap-2.5">
                            <div
                                class="bg-primary/10 text-primary flex h-8 w-8 shrink-0 items-center justify-center rounded-lg font-mono text-xs font-bold"
                            >
                                MT4
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5">
                                    <span
                                        class="text-foreground truncate font-mono text-xs font-bold"
                                    >
                                        No. {{ modalAccount.nomor_akun_trading }}
                                    </span>
                                    <span class="text-muted-foreground text-xs">&bull;</span>
                                    <span
                                        class="text-muted-foreground truncate text-xs font-medium"
                                    >
                                        {{ modalAccount.broker }}
                                    </span>
                                </div>
                                <span class="text-muted-foreground block truncate text-[11px]">
                                    {{
                                        modalAccount.tier_label ||
                                        getTierBadge(modalAccount.tier).label
                                    }}
                                </span>
                            </div>
                        </div>

                        <span
                            class="inline-flex shrink-0 items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-semibold"
                            :class="
                                modalAccount.status === 'active'
                                    ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400'
                                    : 'border-amber-500/20 bg-amber-500/10 text-amber-700 dark:text-amber-400'
                            "
                        >
                            {{ modalAccount.status === 'active' ? 'Aktif' : 'Pending' }}
                        </span>
                    </div>

                    <p v-if="balanceForm.errors.akun_copytrade_id" class="text-destructive text-xs">
                        {{ balanceForm.errors.akun_copytrade_id }}
                    </p>
                </div>

                <!-- Tanggal Pencatatan -->
                <div class="space-y-1.5">
                    <label class="text-foreground block text-xs font-semibold">
                        Tanggal Pencatatan Saldo <span class="text-destructive">*</span>
                    </label>
                    <input
                        v-model="balanceForm.tanggal_pencatatan"
                        type="date"
                        :disabled="isRecordLocked"
                        class="bg-background border-input focus:ring-primary w-full rounded-lg border px-3 py-2 text-xs focus:ring-2 focus:outline-none disabled:opacity-50"
                    />
                    <p class="text-muted-foreground text-[11px]">
                        Disarankan mencatat setiap tanggal 1 untuk sinkronisasi evaluasi bulanan.
                    </p>
                    <p
                        v-if="balanceForm.errors.tanggal_pencatatan"
                        class="text-destructive text-xs"
                    >
                        {{ balanceForm.errors.tanggal_pencatatan }}
                    </p>
                </div>

                <!-- Nilai Saldo (Balance) USD -->
                <div class="space-y-1.5">
                    <label class="text-foreground block text-xs font-semibold">
                        Nilai Saldo Terkini (USD) <span class="text-destructive">*</span>
                    </label>
                    <div class="relative">
                        <span
                            class="text-muted-foreground absolute inset-y-0 left-0 flex items-center pl-3 font-mono text-xs font-bold"
                            >$</span
                        >
                        <input
                            v-model="balanceForm.balance"
                            type="number"
                            step="0.01"
                            min="0"
                            :disabled="isRecordLocked"
                            placeholder="0.00"
                            class="bg-background border-input focus:ring-primary w-full rounded-lg border py-2 pr-3 pl-7 font-mono text-xs focus:ring-2 focus:outline-none disabled:opacity-50"
                        />
                    </div>
                    <p v-if="balanceForm.errors.balance" class="text-destructive text-xs">
                        {{ balanceForm.errors.balance }}
                    </p>
                </div>

                <!-- Mutasi Dana: Penarikan & Setoran Tambahan -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="space-y-1.5">
                        <label class="text-foreground block text-xs font-semibold">
                            Penarikan Dana (USD)
                        </label>
                        <div class="relative">
                            <span class="text-muted-foreground absolute inset-y-0 left-0 flex items-center pl-3 font-mono text-xs font-bold">$</span>
                            <input
                                v-model="balanceForm.penarikan"
                                type="number"
                                step="0.01"
                                min="0"
                                :disabled="isRecordLocked"
                                placeholder="0.00"
                                class="bg-background border-input focus:ring-primary w-full rounded-lg border py-2 pr-3 pl-7 font-mono text-xs focus:ring-2 focus:outline-none disabled:opacity-50"
                            />
                        </div>
                        <p v-if="balanceForm.errors.penarikan" class="text-destructive text-xs">
                            {{ balanceForm.errors.penarikan }}
                        </p>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-foreground block text-xs font-semibold">
                            Setoran Tambahan (USD)
                        </label>
                        <div class="relative">
                            <span class="text-muted-foreground absolute inset-y-0 left-0 flex items-center pl-3 font-mono text-xs font-bold">$</span>
                            <input
                                v-model="balanceForm.setoran_tambahan"
                                type="number"
                                step="0.01"
                                min="0"
                                :disabled="isRecordLocked"
                                placeholder="0.00"
                                class="bg-background border-input focus:ring-primary w-full rounded-lg border py-2 pr-3 pl-7 font-mono text-xs focus:ring-2 focus:outline-none disabled:opacity-50"
                            />
                        </div>
                        <p v-if="balanceForm.errors.setoran_tambahan" class="text-destructive text-xs">
                            {{ balanceForm.errors.setoran_tambahan }}
                        </p>
                    </div>
                </div>

                <!-- Unggah Bukti Saldo MT4 (Screenshot) -->
                <div class="space-y-1.5">
                    <label class="text-foreground block text-xs font-semibold">
                        Bukti Saldo MT4 (Screenshot)
                        <span v-if="!isEditingBalance" class="text-destructive">*</span>
                        <span v-else class="text-muted-foreground font-normal"> (Opsional jika tidak diganti)</span>
                    </label>
                    <input
                        type="file"
                        accept="image/jpeg,image/png,image/jpg,image/webp"
                        :disabled="isRecordLocked"
                        @change="handleBuktiSaldoChange"
                        class="bg-background border-input file:bg-primary file:text-primary-foreground focus:ring-primary w-full rounded-lg border file:mr-3 file:rounded-md file:border-0 file:px-3 file:py-1.5 file:text-xs file:font-semibold text-xs focus:ring-2 focus:outline-none disabled:opacity-50 cursor-pointer"
                    />
                    <p class="text-muted-foreground text-[11px]">
                        Unggah gambar bukti saldo akun MT4.
                    </p>
                    <p v-if="balanceForm.errors.bukti_saldo" class="text-destructive text-xs">
                        {{ balanceForm.errors.bukti_saldo }}
                    </p>
                </div>

                <!-- Live Preview Perhitungan Profit Bersih & Master Fee -->
                <div
                    v-if="balanceForm.balance !== ''"
                    :class="[
                        'space-y-1.5 rounded-xl border p-3 text-xs',
                        liveProfitNominal >= 0
                            ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-900 dark:text-emerald-200'
                            : 'border-destructive/30 bg-destructive/10 text-destructive',
                    ]"
                >
                    <div class="flex items-center justify-between font-semibold">
                        <span>Profit Bersih:</span>
                        <span class="font-mono font-bold">
                            {{ liveProfitNominal >= 0 ? '+' : ''
                            }}{{ formatUSD(liveProfitNominal) }} ({{ liveProfitPct >= 0 ? '+' : ''
                            }}{{ liveProfitPct.toFixed(2) }}%)
                        </span>
                    </div>
                    <div class="flex items-center justify-between font-semibold border-t border-current/10 pt-1.5">
                        <span>Estimasi Master Fee (30%):</span>
                        <span class="font-mono font-bold">
                            {{ liveProfitNominal > 0 ? formatUSD(liveFeeNominal) : '$0.00 (Tidak ada tagihan)' }}
                        </span>
                    </div>
                    <span class="text-muted-foreground block text-[10px]">
                        Basis saldo sebelumnya: ${{ Number(liveBaseBalance).toFixed(2) }}
                    </span>
                </div>

                <div class="border-border flex items-center justify-end gap-2 border-t pt-4">
                    <Button
                        type="button"
                        variant="outline"
                        @click="closeBalanceModal"
                        :disabled="balanceForm.processing"
                        class="cursor-pointer"
                    >
                        Batal
                    </Button>
                    <Button
                        type="submit"
                        class="bg-primary hover:bg-primary/90 text-primary-foreground cursor-pointer gap-2"
                        :disabled="balanceForm.processing || isRecordLocked"
                    >
                        <Loader2 v-if="balanceForm.processing" class="h-4 w-4 animate-spin" />
                        <CheckCircle2 v-else class="h-4 w-4" />
                        <span>{{
                            balanceForm.processing
                                ? 'Menyimpan...'
                                : isEditingBalance
                                  ? 'Simpan Perubahan'
                                  : 'Simpan Saldo'
                        }}</span>
                    </Button>
                </div>
            </form>
        </AppModal>

        <!-- ===================================================================== -->
        <!-- DIALOG KONFIRMASI HAPUS SALDO                                         -->
        <!-- ===================================================================== -->
        <AppConfirmDialog
            :show="isConfirmDeleteOpen"
            :loading="isDeletingBalance"
            title="Hapus Catatan Saldo"
            message="Apakah Anda yakin ingin menghapus catatan saldo ini? Seluruh persentase keuntungan MoM setelah tanggal ini akan otomatis direkalkulasi."
            confirm-text="Hapus Catatan"
            cancel-text="Batal"
            type="danger"
            @confirm="executeDeleteRecord"
            @cancel="isConfirmDeleteOpen = false"
        />
    </MemberLayout>
</template>
