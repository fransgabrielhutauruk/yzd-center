<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import InvestorLayout from '@/Layouts/InvestorLayout.vue';
import AppDataTable from '@/Components/Common/AppDataTable.vue';
import AppModal from '@/Components/Common/AppModal.vue';
import AppTableAction from '@/Components/Common/AppTableAction.vue';
import AppButton from '@/Components/Common/AppButton.vue';
import AppTabs from '@/Components/Common/AppTabs.vue';
import AppCheckbox from '@/Components/Common/AppCheckbox.vue';
import { Button } from '@/Components/ui/button';
import SubmitClaimModal from './SubmitClaimModal.vue';
import DetailModal from './DetailModal.vue';
import {
    Clock,
    CheckCircle2,
    XCircle,
    PlusCircle,
    FileText,
    TrendingUp,
    Users,
    Search,
    CheckSquare,
    Square,
    Lock,
} from 'lucide-vue-next';

const props = defineProps({
    stats: {
        type: Object,
        required: true,
    },
    claims: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    eligibleDownlines: {
        type: Array,
        default: () => [],
    },
    defaultBank: {
        type: Object,
        default: () => ({}),
    },
    exchangeRate: {
        type: Object,
        default: () => ({ rate: 16796 }),
    },
});

const activeTab = ref('downlines'); // 'downlines' | 'claims'

const tabs = computed(() => [
    {
        key: 'downlines',
        label: 'Status Komisi Downline (3 Bulan)',
        icon: Users,
    },
    {
        key: 'claims',
        label: 'Riwayat Pencairan Klaim',
        icon: FileText,
        count: props.stats.menunggu_transfer > 0 ? props.stats.menunggu_transfer : undefined,
        badgeVariant: 'sky',
    },
]);

const switchTab = (tab) => {
    activeTab.value = tab;
    try {
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url.toString());
    } catch {
        // Fallback jika window tidak tersedia
    }
};

const handlePopState = () => {
    try {
        const params = new URLSearchParams(window.location.search);
        const tab = params.get('tab');
        if (tab === 'claims' || tab === 'downlines') {
            activeTab.value = tab;
        }
    } catch {
        // Fallback
    }
};

onMounted(() => {
    window.addEventListener('popstate', handlePopState);
    try {
        const params = new URLSearchParams(window.location.search);
        const tab = params.get('tab');
        if (tab === 'claims' || tab === 'downlines') {
            activeTab.value = tab;
        } else if (params.get('search') || params.get('status') || params.get('page')) {
            activeTab.value = 'claims';
        }
    } catch {
        // Fallback
    }
});

onUnmounted(() => {
    window.removeEventListener('popstate', handlePopState);
});

// State Filter & Checklist di Tab 1 (Status Komisi Downline)
const downlineSearch = ref('');
const downlineStatusFilter = ref('');
const selectedDownlineIds = ref([]);

const filteredDownlines = computed(() => {
    const list = Array.isArray(props.eligibleDownlines) ? props.eligibleDownlines : [];
    const q = downlineSearch.value.trim().toLowerCase();
    const status = downlineStatusFilter.value;

    return list.filter((d) => {
        if (status === 'eligible' && !d.is_eligible) return false;
        if (
            status === 'progress' &&
            (d.is_eligible || d.is_pending || d.is_claimed || d.jumlah_akun === 0)
        )
            return false;
        if (status === 'pending' && !d.is_pending) return false;
        if (status === 'claimed' && !d.is_claimed) return false;
        if (status === 'no_account' && d.jumlah_akun > 0) return false;

        if (!q) return true;
        return (
            d.name?.toLowerCase().includes(q) ||
            d.username?.toLowerCase().includes(q) ||
            d.phone?.toLowerCase().includes(q) ||
            d.accounts?.some(
                (acc) =>
                    acc.broker?.toLowerCase().includes(q) ||
                    String(acc.nomor_akun || '')
                        .toLowerCase()
                        .includes(q)
            )
        );
    });
});

const eligibleDownlinesList = computed(() => {
    return (props.eligibleDownlines || []).filter((d) => d.is_eligible);
});

const isAllEligibleSelected = computed(() => {
    if (eligibleDownlinesList.value.length === 0) return false;
    return eligibleDownlinesList.value.every((d) => selectedDownlineIds.value.includes(d.id));
});

const isIndeterminateEligible = computed(() => {
    const totalEligible = eligibleDownlinesList.value.length;
    if (totalEligible === 0) return false;
    const selectedCount = eligibleDownlinesList.value.filter((d) => selectedDownlineIds.value.includes(d.id)).length;
    return selectedCount > 0 && selectedCount < totalEligible;
});

const toggleSelectAll = () => {
    if (isAllEligibleSelected.value) {
        selectedDownlineIds.value = [];
    } else {
        selectedDownlineIds.value = eligibleDownlinesList.value.map((d) => d.id);
    }
};

const isDownlineSelected = (id) => selectedDownlineIds.value.includes(id);

const toggleDownline = (id) => {
    const idx = selectedDownlineIds.value.indexOf(id);
    if (idx >= 0) {
        selectedDownlineIds.value.splice(idx, 1);
    } else {
        selectedDownlineIds.value.push(id);
    }
};

const selectedDownlinesObjects = computed(() => {
    return (props.eligibleDownlines || []).filter((d) => selectedDownlineIds.value.includes(d.id));
});

// Modal Pengajuan Klaim & Modal Rincian
const isSubmitModalOpen = ref(false);
const isDetailModalOpen = ref(false);
const selectedClaim = ref(null);

const isAccountBreakdownModalOpen = ref(false);
const selectedDownlineForBreakdown = ref(null);

const openBatchClaimModal = () => {
    if (selectedDownlineIds.value.length === 0) return;
    isSubmitModalOpen.value = true;
};

const claimSingleDownline = (downline) => {
    if (!downline.is_eligible) return;
    if (!selectedDownlineIds.value.includes(downline.id)) {
        selectedDownlineIds.value = [downline.id];
    }
    isSubmitModalOpen.value = true;
};

const handleClaimSubmitted = () => {
    selectedDownlineIds.value = [];
    switchTab('claims');
};

const openDownlineBreakdown = (downline) => {
    selectedDownlineForBreakdown.value = downline;
    isAccountBreakdownModalOpen.value = true;
};

const openDetailModal = (claim) => {
    selectedClaim.value = claim;
    isDetailModalOpen.value = true;
};

const formatUSD = (val) => {
    if (val === null || val === undefined || isNaN(val)) return '$0.00';
    return (
        '$' +
        Number(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
    );
};

const formatIDR = (val) => {
    if (val === null || val === undefined || isNaN(val)) return 'Rp 0';
    return 'Rp ' + Math.round(Number(val)).toLocaleString('id-ID');
};

const tableColumns = [
    { key: 'kode_klaim', label: 'Kode Klaim / Waktu', sortable: true },
    { key: 'rincian_downline', label: 'Investor Downline & Akun' },
    { key: 'total_modal', label: 'Total Modal Riil', sortable: true },
    { key: 'nominal_transfer', label: 'Hak Komisi / Transfer' },
    { key: 'status', label: 'Status Pencairan', sortable: true },
    { key: 'actions', label: 'Aksi' },
];

const getStatusBadge = (status) => {
    switch (status) {
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
};
</script>

<template>
    <Head title="Klaim Komisi Afiliasi" />

    <InvestorLayout
        title="Klaim Komisi Afiliasi"
        subtitle="Pantau masa aktif periode 3 bulan copytrade downline Anda dan ajukan pencairan komisi."
        :breadcrumbs="[{ label: 'Program Afiliasi' }, { label: 'Klaim Komisi' }]"
    >
        <template #header-actions>
            <AppButton
                type="create"
                :icon="PlusCircle"
                :label="
                    selectedDownlineIds.length > 0
                        ? `Ajukan Klaim Komisi (${selectedDownlineIds.length} Investor)`
                        : 'Ajukan Klaim Komisi'
                "
                :disabled="selectedDownlineIds.length === 0"
                class="w-full sm:w-auto"
                @click="openBatchClaimModal"
            />
        </template>

        <div class="space-y-6">
            <!-- Banner Skema 4-Tiering Komisi -->
            <div class="bg-card border-border space-y-3 rounded-2xl border p-4 shadow-xs sm:p-5">
                <div
                    class="border-border/60 flex flex-wrap items-center justify-between gap-2 border-b pb-2.5"
                >
                    <h2 class="text-foreground text-xs font-bold sm:text-sm">
                        Skema 4-Tiering Pembagian Komisi Afiliasi (dari Bagian Profit Master)
                    </h2>
                    <span
                        v-if="exchangeRate?.rate"
                        class="border-border bg-muted/50 text-muted-foreground rounded-lg border px-2.5 py-1 font-mono text-[11px]"
                    >
                        Kurs Acuan:
                        <strong class="text-foreground"
                            >$1 = {{ formatIDR(exchangeRate.rate) }}</strong
                        >
                    </span>
                </div>
                <div class="grid grid-cols-1 gap-2.5 text-xs sm:grid-cols-2 lg:grid-cols-4">
                    <!-- Tier 1 -->
                    <div
                        class="space-y-1 rounded-xl border border-emerald-500/30 bg-emerald-500/5 p-3"
                    >
                        <div class="flex items-center justify-between">
                            <span
                                class="text-[11px] font-bold text-emerald-900 uppercase dark:text-emerald-300"
                                >Tier 1</span
                            >
                            <span
                                class="font-mono font-extrabold text-emerald-800 dark:text-emerald-300"
                                >50%</span
                            >
                        </div>
                        <p class="text-muted-foreground text-[11px]">Modal &gt; Rp 10 Milyar</p>
                    </div>

                    <!-- Tier 2 -->
                    <div
                        class="space-y-1 rounded-xl border border-indigo-500/30 bg-indigo-500/5 p-3"
                    >
                        <div class="flex items-center justify-between">
                            <span
                                class="text-[11px] font-bold text-indigo-900 uppercase dark:text-indigo-300"
                                >Tier 2</span
                            >
                            <span
                                class="font-mono font-extrabold text-indigo-800 dark:text-indigo-300"
                                >40%</span
                            >
                        </div>
                        <p class="text-muted-foreground text-[11px]">
                            Modal Rp 1 Milyar – Rp 10 Milyar
                        </p>
                    </div>

                    <!-- Tier 3 -->
                    <div class="space-y-1 rounded-xl border border-sky-500/30 bg-sky-500/5 p-3">
                        <div class="flex items-center justify-between">
                            <span
                                class="text-[11px] font-bold text-sky-900 uppercase dark:text-sky-300"
                                >Tier 3</span
                            >
                            <span class="font-mono font-extrabold text-sky-800 dark:text-sky-300"
                                >30%</span
                            >
                        </div>
                        <p class="text-muted-foreground text-[11px]">
                            Modal Rp 100 Juta – Rp 1 Milyar
                        </p>
                    </div>

                    <!-- Tier 4 -->
                    <div class="space-y-1 rounded-xl border border-amber-500/30 bg-amber-500/5 p-3">
                        <div class="flex items-center justify-between">
                            <span
                                class="text-[11px] font-bold text-amber-900 uppercase dark:text-amber-300"
                                >Tier 4</span
                            >
                            <span
                                class="font-mono font-extrabold text-amber-800 dark:text-amber-300"
                                >20%</span
                            >
                        </div>
                        <p class="text-muted-foreground text-[11px]">Modal &lt; Rp 100 Juta</p>
                    </div>
                </div>
            </div>

            <!-- Tab Navigasi -->
            <AppTabs v-model="activeTab" :items="tabs">
                <template #badge-downlines>
                    <span
                        class="text-foreground rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold dark:bg-slate-800 shrink-0"
                    >
                        {{ eligibleDownlines.length }}
                    </span>
                    <span
                        v-if="eligibleDownlinesList.length > 0"
                        class="rounded-full bg-emerald-500/20 px-2 py-0.5 text-[10px] font-bold text-emerald-800 dark:text-emerald-300 shrink-0"
                    >
                        {{ eligibleDownlinesList.length }} Siap
                    </span>
                </template>
            </AppTabs>

            <!-- TAB 1: STATUS KOMISI DOWNLINE (3 BULAN) -->
            <div v-show="activeTab === 'downlines'" class="space-y-4">
                <div
                    class="bg-card border-border space-y-4 rounded-2xl border p-4 shadow-xs sm:p-5"
                >
                    <!-- Toolbar Filter, Pencarian, & Pilih Semua Batch -->
                    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                        <div class="flex flex-1 flex-col gap-2.5 sm:flex-row sm:items-center">
                            <div class="relative flex-1 sm:max-w-xs">
                                <Search
                                    class="text-muted-foreground absolute top-1/2 left-3 h-3.5 w-3.5 -translate-y-1/2"
                                />
                                <input
                                    v-model="downlineSearch"
                                    type="text"
                                    placeholder="Cari nama, username, no. HP, akun..."
                                    class="bg-background border-input focus:ring-primary-500 h-9 w-full rounded-lg border pr-3 pl-8 text-xs focus:ring-2 focus:outline-none"
                                />
                            </div>

                            <select
                                v-model="downlineStatusFilter"
                                class="border-input bg-background text-foreground focus:border-primary-500 focus:ring-primary-500/20 h-9 rounded-lg border px-3 text-xs transition-all focus:ring-2 focus:outline-none"
                            >
                                <option value="">Semua Status</option>
                                <option value="eligible">Siap Diklaim (3 Bulan)</option>
                                <option value="progress">Masa Tunggu (&lt; 3 Bulan)</option>
                                <option value="pending">Menunggu Verifikasi</option>
                                <option value="claimed">Sudah Dicairkan</option>
                                <option value="no_account">Belum Copytrade</option>
                            </select>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                @click="toggleSelectAll"
                                :disabled="eligibleDownlinesList.length === 0"
                                class="h-9 cursor-pointer text-xs"
                            >
                                <component
                                    :is="isAllEligibleSelected ? CheckSquare : Square"
                                    class="text-primary-600 mr-1.5 h-3.5 w-3.5"
                                />
                                <span>
                                    {{
                                        isAllEligibleSelected
                                            ? 'Batalkan Semua Pilihan'
                                            : `Pilih Semua yang Siap (${eligibleDownlinesList.length})`
                                    }}
                                </span>
                            </Button>

                            <AppButton
                                v-if="selectedDownlineIds.length > 0"
                                type="create"
                                :icon="PlusCircle"
                                size="sm"
                                :label="`Klaim Terpilih (${selectedDownlineIds.length})`"
                                @click="openBatchClaimModal"
                            />
                        </div>
                    </div>

                    <!-- Tabel Daftar Downline & Progres 3 Bulan -->
                    <div class="border-border overflow-x-auto rounded-xl border">
                        <table class="w-full text-left text-xs">
                            <thead
                                class="border-border bg-muted/40 text-muted-foreground border-b text-[11px] font-bold uppercase"
                            >
                                <tr>
                                    <th class="w-10 px-3.5 py-3 text-center">
                                        <AppCheckbox
                                            :checked="isAllEligibleSelected"
                                            :indeterminate="isIndeterminateEligible"
                                            :disabled="eligibleDownlinesList.length === 0"
                                            @change="toggleSelectAll"
                                            title="Pilih semua investor yang siap diklaim"
                                            aria-label="Pilih semua investor yang siap diklaim"
                                        />
                                    </th>
                                    <th class="px-4 py-3">Investor</th>
                                    <th class="px-4 py-3">Periode 3 Bulan &amp; Progres</th>
                                    <th class="px-4 py-3">Akun Tergabung</th>
                                    <th class="px-4 py-3">Total Modal &amp; Tier</th>
                                    <th class="px-4 py-3">Status Kelayakan</th>
                                    <th class="px-4 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-border divide-y">
                                <tr v-if="filteredDownlines.length === 0">
                                    <td
                                        colspan="7"
                                        class="text-muted-foreground px-4 py-10 text-center"
                                    >
                                        <p class="text-foreground text-xs font-semibold">
                                            Tidak Ada Data Downline
                                        </p>
                                        <p class="mt-0.5 text-[11px]">
                                            Belum ada investor downline yang sesuai dengan filter
                                            pencarian Anda.
                                        </p>
                                    </td>
                                </tr>

                                <tr
                                    v-for="downline in filteredDownlines"
                                    :key="downline.id"
                                    class="transition-colors"
                                    :class="
                                        isDownlineSelected(downline.id)
                                            ? 'bg-primary-500/5'
                                            : 'hover:bg-muted/30'
                                    "
                                >
                                    <!-- 1. Kolom Checklist -->
                                    <td class="px-3.5 py-3.5 text-center align-middle">
                                        <AppCheckbox
                                            :checked="isDownlineSelected(downline.id)"
                                            :disabled="!downline.is_eligible"
                                            @change="downline.is_eligible && toggleDownline(downline.id)"
                                            :aria-label="`Pilih downline ${downline.name}`"
                                        />
                                    </td>

                                    <!-- 2. Kolom Investor -->
                                    <td class="px-4 py-3.5 align-middle">
                                        <div class="space-y-0.5">
                                            <p class="text-foreground text-xs font-bold sm:text-sm">
                                                {{ downline.name }}
                                            </p>
                                            <p class="text-muted-foreground font-mono text-[11px]">
                                                @{{ downline.username }}
                                                <span
                                                    v-if="downline.phone && downline.phone !== '-'"
                                                >
                                                    &bull; {{ downline.phone }}
                                                </span>
                                            </p>
                                        </div>
                                    </td>

                                    <!-- 3. Kolom Periode 3 Bulan & Progres -->
                                    <td class="px-4 py-3.5 align-middle">
                                        <div v-if="downline.first_started_at" class="space-y-1">
                                            <p
                                                class="text-foreground text-xs font-medium whitespace-nowrap"
                                            >
                                                {{ downline.first_started_at }} &ndash;
                                                {{ downline.period_end_date }}
                                            </p>
                                            <div class="flex items-center gap-2">
                                                <div
                                                    class="bg-muted h-1.5 w-24 overflow-hidden rounded-full"
                                                >
                                                    <div
                                                        class="h-full rounded-full transition-all"
                                                        :class="
                                                            downline.active_days >=
                                                            (downline.target_days || 90)
                                                                ? 'bg-emerald-500'
                                                                : 'bg-sky-500'
                                                        "
                                                        :style="{
                                                            width: `${Math.min(100, Math.round((downline.active_days / (downline.target_days || 90)) * 100))}%`,
                                                        }"
                                                    ></div>
                                                </div>
                                                <span
                                                    class="text-muted-foreground font-mono text-[11px] font-semibold"
                                                >
                                                    {{ downline.active_days }}/{{
                                                        downline.target_days || 90
                                                    }}
                                                    Hari
                                                </span>
                                            </div>
                                        </div>
                                        <span v-else class="text-muted-foreground text-xs">-</span>
                                    </td>

                                    <!-- 4. Kolom Akun Tergabung -->
                                    <td class="px-4 py-3.5 align-middle">
                                        <div v-if="downline.accounts?.length" class="space-y-1">
                                            <div class="flex flex-wrap items-center gap-1">
                                                <span
                                                    v-for="(acc, idx) in downline.accounts.slice(
                                                        0,
                                                        2
                                                    )"
                                                    :key="idx"
                                                    class="border-border bg-muted/40 text-foreground inline-flex items-center gap-1 rounded-md border px-2 py-0.5 font-mono text-[10px]"
                                                >
                                                    <strong>{{ acc.broker }}</strong>
                                                    <span>({{ acc.hari_kehitung }} hr)</span>
                                                </span>
                                                <span
                                                    v-if="downline.accounts.length > 2"
                                                    class="bg-muted text-muted-foreground rounded-md px-1.5 py-0.5 text-[10px] font-semibold"
                                                >
                                                    +{{ downline.accounts.length - 2 }} lainnya
                                                </span>
                                            </div>
                                            <p
                                                v-if="downline.has_multi_copytrade"
                                                class="text-muted-foreground text-[10px]"
                                            >
                                                {{ downline.jumlah_akun }} akun digabung
                                                (proporsional)
                                            </p>
                                        </div>
                                        <span v-else class="text-muted-foreground text-xs"
                                            >Belum Ada Akun</span
                                        >
                                    </td>

                                    <!-- 5. Kolom Total Modal & Tier -->
                                    <td class="px-4 py-3.5 align-middle">
                                        <div v-if="downline.total_modal_riil > 0" class="space-y-1">
                                            <p class="text-foreground font-mono text-xs font-bold">
                                                {{ formatUSD(downline.total_modal_riil) }}
                                            </p>
                                            <p
                                                v-if="downline.total_modal_idr"
                                                class="text-muted-foreground font-mono text-[10px]"
                                            >
                                                &asymp; {{ formatIDR(downline.total_modal_idr) }}
                                            </p>
                                            <div
                                                v-if="downline.accounts?.length > 1"
                                                class="flex flex-wrap gap-1 pt-0.5"
                                            >
                                                <span
                                                    v-for="(acc, accIdx) in downline.accounts"
                                                    :key="accIdx"
                                                    class="border-primary-500/30 bg-primary-500/10 text-primary-800 dark:text-primary-300 inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold"
                                                >
                                                    Akun {{ accIdx + 1 }}: Tier {{ acc.tier_level }} ({{ acc.persentase_tier }}%)
                                                </span>
                                            </div>
                                            <span
                                                v-else
                                                class="border-primary-500/30 bg-primary-500/10 text-primary-800 dark:text-primary-300 inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold"
                                            >
                                                Tier {{ downline.accounts?.[0]?.tier_level ?? downline.tier_info?.tier_level }} ({{
                                                    downline.accounts?.[0]?.persentase_tier ?? downline.tier_info?.persentase_tier
                                                }}%)
                                            </span>
                                        </div>
                                        <span v-else class="text-muted-foreground text-xs">-</span>
                                    </td>

                                    <!-- 6. Kolom Status Kelayakan -->
                                    <td class="px-4 py-3.5 align-middle">
                                        <!-- Siap Diklaim -->
                                        <span
                                            v-if="downline.is_eligible"
                                            class="inline-flex items-center gap-1 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-2 py-0.5 text-[10px] font-semibold whitespace-nowrap text-emerald-800 dark:text-emerald-300"
                                        >
                                            <CheckCircle2
                                                class="h-3 w-3 text-emerald-600 dark:text-emerald-400"
                                            />
                                            <span>Siap Diklaim</span>
                                        </span>

                                        <!-- Menunggu Verifikasi -->
                                        <span
                                            v-else-if="downline.is_pending"
                                            class="inline-flex items-center gap-1 rounded-full border border-sky-500/30 bg-sky-500/10 px-2 py-0.5 text-[10px] font-semibold whitespace-nowrap text-sky-800 dark:text-sky-300"
                                        >
                                            <Clock class="h-3 w-3" />
                                            <span>Menunggu Verifikasi</span>
                                        </span>

                                        <!-- Sudah Dicairkan -->
                                        <span
                                            v-else-if="downline.is_claimed"
                                            class="inline-flex items-center gap-1 rounded-full border border-indigo-500/30 bg-indigo-500/10 px-2 py-0.5 text-[10px] font-semibold whitespace-nowrap text-indigo-800 dark:text-indigo-300"
                                        >
                                            <CheckCircle2
                                                class="h-3 w-3 text-indigo-600 dark:text-indigo-400"
                                            />
                                            <span>Sudah Dicairkan</span>
                                        </span>

                                        <!-- Masa Tunggu (< 3 Bulan) -->
                                        <span
                                            v-else-if="downline.jumlah_akun > 0"
                                            class="inline-flex items-center gap-1 rounded-full border border-amber-500/30 bg-amber-500/10 px-2 py-0.5 text-[10px] font-semibold whitespace-nowrap text-amber-800 dark:text-amber-300"
                                        >
                                            <Clock
                                                class="h-3 w-3 text-amber-600 dark:text-amber-400"
                                            />
                                            <span>{{ downline.status_label }}</span>
                                        </span>

                                        <!-- Belum Copytrade -->
                                        <span
                                            v-else
                                            class="border-border bg-muted text-muted-foreground inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-semibold whitespace-nowrap"
                                        >
                                            <Lock class="h-3 w-3" />
                                            <span>Belum Copytrade</span>
                                        </span>
                                    </td>

                                    <!-- 7. Kolom Aksi -->
                                    <td class="px-4 py-3.5 text-right align-middle">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <AppTableAction
                                                v-if="downline.is_eligible"
                                                type="process"
                                                label="Klaim"
                                                title="Klaim Komisi"
                                                variant="default"
                                                :icon-only="false"
                                                @click="claimSingleDownline(downline)"
                                            />
                                            <AppTableAction
                                                v-if="downline.accounts?.length > 0"
                                                type="detail"
                                                title="Rincian Akun Downline"
                                                @click="openDownlineBreakdown(downline)"
                                            />
                                            <span
                                                v-if="
                                                    !downline.is_eligible &&
                                                    !downline.accounts?.length
                                                "
                                                class="text-muted-foreground text-xs"
                                            >
                                                -
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 2: RIWAYAT PENCAIRAN KLAIM -->
            <div v-show="activeTab === 'claims'" class="space-y-4">
                <AppDataTable
                    :data="claims"
                    :columns="tableColumns"
                    :filters="filters"
                    :extra-params="{ tab: 'claims' }"
                    fetch-url="/investor/commissions"
                    search-placeholder="Cari kode klaim, nama downline..."
                    empty-title="Belum Ada Riwayat Klaim Komisi"
                    empty-message="Anda belum pernah mengajukan klaim komisi atau data pencarian tidak ditemukan."
                >
                    <!-- Filter Slot -->
                    <template #filters="{ applyFilters }">
                        <select
                            :value="filters.status || ''"
                            @change="
                                (e) =>
                                    applyFilters({ status: e.target.value || undefined, page: 1 })
                            "
                            class="bg-background border-input focus:ring-primary-500 min-h-[38px] w-full rounded-lg border px-3 py-1.5 text-xs font-medium focus:ring-2 focus:outline-none sm:w-auto"
                        >
                            <option value="">Semua Status</option>
                            <option value="pending">Menunggu Verifikasi</option>
                            <option value="approved_transferred">Ditransfer</option>
                            <option value="rejected">Ditolak</option>
                        </select>
                    </template>

                    <!-- Kolom Kode Klaim & Waktu -->
                    <template #column-kode_klaim="{ item }">
                        <div class="space-y-0.5">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="text-foreground font-mono text-xs font-bold">{{
                                    item.kode_klaim
                                }}</span>
                                <span
                                    class="rounded border border-sky-500/30 bg-sky-500/10 px-1.5 py-0.5 text-[10px] font-bold text-sky-800 dark:text-sky-300"
                                >
                                    {{ item.total_downline }} Investor ({{ item.total_akun }} Akun)
                                </span>
                            </div>
                            <p class="text-muted-foreground text-[11px]">{{ item.created_at }}</p>
                        </div>
                    </template>

                    <!-- Kolom Investor Downline & Akun -->
                    <template #column-rincian_downline="{ item }">
                        <div class="space-y-1">
                            <div class="flex items-center gap-1.5">
                                <Users
                                    class="text-primary-600 dark:text-primary-400 h-3.5 w-3.5 shrink-0"
                                />
                                <span class="text-foreground text-xs font-semibold">
                                    {{
                                        item.summary_downlines || `${item.total_downline} Investor`
                                    }}
                                </span>
                            </div>
                            <div class="flex flex-wrap items-center gap-1">
                                <span
                                    v-for="(accItem, idx) in item.items.slice(0, 3)"
                                    :key="idx"
                                    class="bg-muted text-muted-foreground rounded px-1.5 py-0.5 font-mono text-[10px]"
                                >
                                    {{ accItem.broker }} ({{ accItem.hari_kehitung }} hari)
                                </span>
                                <span
                                    v-if="item.items.length > 3"
                                    class="bg-muted text-muted-foreground rounded px-1.5 py-0.5 text-[10px] font-medium"
                                >
                                    +{{ item.items.length - 3 }} akun lainnya
                                </span>
                            </div>
                        </div>
                    </template>

                    <!-- Kolom Total Modal Riil -->
                    <template #column-total_modal="{ item }">
                        <div class="space-y-0.5">
                            <span class="text-foreground font-mono text-xs font-bold">{{
                                formatUSD(item.total_deposit_amount || item.total_modal_riil)
                            }}</span>
                            <p class="text-muted-foreground text-[10px]">
                                {{ item.total_akun }} akun copytrade
                            </p>
                        </div>
                    </template>

                    <!-- Kolom Profit & Transfer -->
                    <template #column-nominal_transfer="{ item }">
                        <div v-if="item.status === 'approved_transferred'" class="space-y-0.5">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span
                                    class="font-mono text-xs font-extrabold text-emerald-700 dark:text-emerald-400"
                                >
                                    {{
                                        formatUSD(
                                            item.total_affiliate_commission ??
                                                item.total_nominal_transfer
                                        )
                                    }}
                                </span>
                                <span
                                    v-if="item.total_nominal_transfer_idr !== null && item.total_nominal_transfer_idr !== undefined"
                                    class="rounded border border-emerald-500/30 bg-emerald-500/10 px-1.5 py-0.5 font-mono text-[10px] font-bold text-emerald-800 dark:text-emerald-300"
                                >
                                    {{ formatIDR(item.total_nominal_transfer_idr) }}
                                </span>
                            </div>
                            <p v-if="item.kurs_konversi_idr" class="text-muted-foreground font-mono text-[10px]">
                                Kurs MT4: {{ formatIDR(item.kurs_konversi_idr) }}/$1
                            </p>
                            <p class="text-muted-foreground text-[10px]">
                                Dari Profit:
                                {{
                                    formatUSD(
                                        item.total_master_gross_share ?? item.total_profit_master
                                    )
                                }}
                            </p>
                        </div>
                        <div v-else class="text-muted-foreground text-xs italic">
                            Menunggu Verifikasi
                        </div>
                    </template>

                    <!-- Kolom Status -->
                    <template #column-status="{ item }">
                        <span
                            :class="[
                                'inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-semibold whitespace-nowrap',
                                getStatusBadge(item.status).class,
                            ]"
                        >
                            <component :is="getStatusBadge(item.status).icon" class="h-3 w-3" />
                            <span>{{ getStatusBadge(item.status).label }}</span>
                        </span>
                    </template>

                    <!-- Kolom Aksi -->
                    <template #column-actions="{ item }">
                        <AppTableAction
                            type="detail"
                            title="Lihat Rincian Klaim"
                            @click="openDetailModal(item)"
                        />
                    </template>
                </AppDataTable>
            </div>
        </div>

        <!-- Modal Rincian Akun Tergabung per Downline (Tab 1) -->
        <AppModal
            :show="isAccountBreakdownModalOpen"
            :title="`Rincian Akun Copytrade: ${selectedDownlineForBreakdown?.name || ''}`"
            max-width="xl"
            @close="isAccountBreakdownModalOpen = false"
        >
            <div v-if="selectedDownlineForBreakdown" class="space-y-3.5 text-xs">
                <div
                    class="border-border bg-muted/30 flex flex-wrap items-center justify-between gap-2 rounded-xl border p-3"
                >
                    <div class="space-y-0.5">
                        <p class="text-foreground font-bold">
                            {{ selectedDownlineForBreakdown.name }}
                            <span class="text-muted-foreground font-mono font-normal">
                                (@{{ selectedDownlineForBreakdown.username }})
                            </span>
                        </p>
                        <p class="text-muted-foreground text-[11px]">
                            Periode 3 Bulan Acuan:
                            {{ selectedDownlineForBreakdown.first_started_at }} s/d
                            {{ selectedDownlineForBreakdown.period_end_date }}
                        </p>
                    </div>
                    <div class="text-right">
                        <span class="text-muted-foreground block text-[10px]"
                            >Total Akumulasi Modal:</span
                        >
                        <span class="text-foreground font-mono text-sm font-extrabold">
                            {{ formatUSD(selectedDownlineForBreakdown.total_modal_riil) }}
                        </span>
                        <span
                            v-if="selectedDownlineForBreakdown.total_modal_idr"
                            class="text-muted-foreground block font-mono text-[10px]"
                        >
                            &asymp; {{ formatIDR(selectedDownlineForBreakdown.total_modal_idr) }}
                        </span>
                    </div>
                </div>

                <div class="space-y-2">
                    <div
                        v-for="(acc, idx) in selectedDownlineForBreakdown.accounts"
                        :key="idx"
                        class="border-border bg-card flex flex-col justify-between gap-2 rounded-xl border p-3 sm:flex-row sm:items-center"
                    >
                        <div class="space-y-1">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="text-foreground font-bold">{{ acc.broker }}</span>
                                <span class="text-muted-foreground font-mono text-[11px]"
                                    >No. {{ acc.nomor_akun }}</span
                                >
                                <span
                                    v-if="acc.is_first"
                                    class="rounded border border-sky-500/30 bg-sky-500/10 px-1.5 py-0.5 text-[10px] font-bold text-sky-800 dark:text-sky-300"
                                >
                                    Akun Pertama (Acuan 3 Bulan)
                                </span>
                                <span
                                    v-else
                                    class="rounded border border-purple-500/30 bg-purple-500/10 px-1.5 py-0.5 text-[10px] font-semibold text-purple-800 dark:text-purple-300"
                                >
                                    Akun Tambahan (+{{ acc.selisih_hari }} hari)
                                </span>
                            </div>
                            <p class="text-muted-foreground text-[11px]">
                                Mulai: {{ acc.tanggal_mulai }} s/d {{ acc.tanggal_selesai }}
                            </p>
                        </div>

                        <div class="flex items-center gap-3 self-end sm:self-center">
                            <div class="text-right">
                                <span class="text-muted-foreground block text-[10px]"
                                    >Modal Riil:</span
                                >
                                <span class="text-foreground font-mono font-bold">{{
                                    formatUSD(acc.modal_riil)
                                }}</span>
                            </div>
                            <div
                                class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-2.5 py-1 text-center"
                            >
                                <span class="text-muted-foreground block text-[9px]"
                                    >Dihitung:</span
                                >
                                <span
                                    class="font-mono text-xs font-extrabold text-emerald-800 dark:text-emerald-300"
                                >
                                    {{ acc.hari_kehitung }} Hari
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-border flex justify-end gap-2 border-t pt-3">
                    <Button
                        type="button"
                        variant="outline"
                        @click="isAccountBreakdownModalOpen = false"
                        class="text-xs"
                    >
                        Tutup
                    </Button>
                    <Button
                        v-if="selectedDownlineForBreakdown.is_eligible"
                        type="button"
                        @click="
                            isAccountBreakdownModalOpen = false;
                            claimSingleDownline(selectedDownlineForBreakdown);
                        "
                        class="bg-primary-600 hover:bg-primary-700 cursor-pointer text-xs text-white"
                    >
                        Klaim Komisi Investor Ini
                    </Button>
                </div>
            </div>
        </AppModal>

        <!-- Modal Konfirmasi Pengajuan Klaim Komisi -->
        <SubmitClaimModal
            :show="isSubmitModalOpen"
            :is-open="isSubmitModalOpen"
            :selected-downlines="selectedDownlinesObjects"
            :default-bank="defaultBank"
            @submitted="handleClaimSubmitted"
            @close="isSubmitModalOpen = false"
        />

        <!-- Modal Rincian Klaim Komisi -->
        <DetailModal
            :show="isDetailModalOpen"
            :is-open="isDetailModalOpen"
            :claim="selectedClaim"
            @close="isDetailModalOpen = false"
        />
    </InvestorLayout>
</template>
