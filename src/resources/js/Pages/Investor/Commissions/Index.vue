<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import InvestorLayout from '@/Layouts/InvestorLayout.vue';
import AppDataTable from '@/Components/Common/AppDataTable.vue';
import { Button } from '@/Components/ui/button';
import SubmitClaimModal from './SubmitClaimModal.vue';
import DetailModal from './DetailModal.vue';
import {
    Banknote,
    Clock,
    CheckCircle2,
    XCircle,
    UserCheck,
    PlusCircle,
    FileText,
    Eye,
    TrendingUp,
    Users,
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
});

const isSubmitModalOpen = ref(false);
const isDetailModalOpen = ref(false);
const selectedClaim = ref(null);

const openDetailModal = (claim) => {
    selectedClaim.value = claim;
    isDetailModalOpen.value = true;
};

const formatUSD = (val) => {
    if (val === null || val === undefined || isNaN(val)) return '$0.00';
    return '$' + Number(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const formatRupiah = (val) => {
    if (val === null || val === undefined || isNaN(val)) return 'Rp 0';
    return 'Rp ' + Number(val).toLocaleString('id-ID');
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
                label: 'Menunggu Transfer',
                class: 'bg-amber-500/10 text-amber-800 dark:text-amber-300 border-amber-500/30',
                icon: Clock,
            };
    }
};
</script>

<template>
    <Head title="Klaim Komisi Afiliasi (PB 3)" />

    <InvestorLayout title="Klaim Komisi Afiliasi">
        <div class="space-y-6">
            <!-- Header Halaman -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-foreground text-lg sm:text-xl font-bold tracking-tight">
                        Klaim Komisi Afiliasi (PB 3)
                    </h1>
                    <p class="text-muted-foreground text-xs sm:text-sm">
                        Kelola hak komisi dari profit Master Trader per investor downline aktif minimal 90 hari (3 bulan masa salin copytrade).
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Button @click="isSubmitModalOpen = true" class="w-full sm:w-auto">
                        <PlusCircle class="h-4 w-4 mr-1.5" />
                        <span>Ajukan Klaim Komisi Baru</span>
                    </Button>
                </div>
            </div>

            <!-- 4 Kartu Statistik Ringkas -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                <!-- Total Dicairkan -->
                <div class="bg-card border-border rounded-2xl border p-4 shadow-xs space-y-1">
                    <div class="flex items-center justify-between text-muted-foreground">
                        <span class="text-xs font-medium">Total Dicairkan</span>
                        <Banknote class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                    </div>
                    <p class="text-foreground font-mono text-base sm:text-lg font-extrabold truncate">
                        {{ formatUSD(stats.total_dicairkan) }}
                    </p>
                    <p class="text-muted-foreground text-[10px] sm:text-[11px]">
                        Ditransfer ke rekening Anda
                    </p>
                </div>

                <!-- Menunggu Transfer -->
                <div class="bg-card border-border rounded-2xl border p-4 shadow-xs space-y-1">
                    <div class="flex items-center justify-between text-muted-foreground">
                        <span class="text-xs font-medium">Menunggu Transfer</span>
                        <Clock class="h-4 w-4 text-amber-600 dark:text-amber-400" />
                    </div>
                    <p class="text-amber-800 dark:text-amber-300 font-mono text-base sm:text-lg font-extrabold">
                        {{ stats.menunggu_transfer }} Pengajuan
                    </p>
                    <p class="text-muted-foreground text-[10px] sm:text-[11px]">
                        Dalam antrean verifikasi
                    </p>
                </div>

                <!-- Klaim Disetujui -->
                <div class="bg-card border-border rounded-2xl border p-4 shadow-xs space-y-1">
                    <div class="flex items-center justify-between text-muted-foreground">
                        <span class="text-xs font-medium">Klaim Berhasil</span>
                        <CheckCircle2 class="h-4 w-4 text-indigo-600 dark:text-indigo-400" />
                    </div>
                    <p class="text-foreground font-mono text-base sm:text-lg font-extrabold">
                        {{ stats.klaim_disetujui }} Kali
                    </p>
                    <p class="text-muted-foreground text-[10px] sm:text-[11px]">
                        Pencairan telah tuntas
                    </p>
                </div>

                <!-- Downline Siap Diklaim -->
                <div class="bg-card border-border rounded-2xl border p-4 shadow-xs space-y-1">
                    <div class="flex items-center justify-between text-muted-foreground">
                        <span class="text-xs font-medium">Downline Siap Diklaim</span>
                        <UserCheck class="h-4 w-4 text-sky-600 dark:text-sky-400" />
                    </div>
                    <p class="text-sky-800 dark:text-sky-300 font-mono text-base sm:text-lg font-extrabold">
                        {{ stats.downline_siap_diklaim }} Investor
                    </p>
                    <p class="text-muted-foreground text-[10px] sm:text-[11px]">
                        Masa aktif &ge; 90 hari copytrade
                    </p>
                </div>
            </div>

            <!-- Banner Skema 4-Tiering Komisi -->
            <div class="bg-card border-border rounded-2xl border p-4 sm:p-5 shadow-xs space-y-3">
                <div class="flex items-center gap-2 border-b border-border/60 pb-2.5">
                    <TrendingUp class="h-4 w-4 text-primary-600 dark:text-primary-400" />
                    <h2 class="text-foreground text-xs sm:text-sm font-bold">
                        Skema 4-Tiering Pembagian Komisi Afiliasi (dari Bagian Profit Master Trader)
                    </h2>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5 text-xs">
                    <!-- Tier 1 -->
                    <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/5 p-3 space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="text-emerald-900 dark:text-emerald-300 font-bold uppercase text-[11px]">Tier 1</span>
                            <span class="font-mono font-extrabold text-emerald-800 dark:text-emerald-300">50%</span>
                        </div>
                        <p class="text-muted-foreground text-[11px]">Modal &gt; Rp 10 Milyar</p>
                    </div>

                    <!-- Tier 2 -->
                    <div class="rounded-xl border border-indigo-500/30 bg-indigo-500/5 p-3 space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="text-indigo-900 dark:text-indigo-300 font-bold uppercase text-[11px]">Tier 2</span>
                            <span class="font-mono font-extrabold text-indigo-800 dark:text-indigo-300">40%</span>
                        </div>
                        <p class="text-muted-foreground text-[11px]">Modal Rp 1 Milyar – Rp 10 Milyar</p>
                    </div>

                    <!-- Tier 3 -->
                    <div class="rounded-xl border border-sky-500/30 bg-sky-500/5 p-3 space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="text-sky-900 dark:text-sky-300 font-bold uppercase text-[11px]">Tier 3</span>
                            <span class="font-mono font-extrabold text-sky-800 dark:text-sky-300">30%</span>
                        </div>
                        <p class="text-muted-foreground text-[11px]">Modal Rp 100 Juta – Rp 1 Milyar</p>
                    </div>

                    <!-- Tier 4 -->
                    <div class="rounded-xl border border-amber-500/30 bg-amber-500/5 p-3 space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="text-amber-900 dark:text-amber-300 font-bold uppercase text-[11px]">Tier 4</span>
                            <span class="font-mono font-extrabold text-amber-800 dark:text-amber-300">20%</span>
                        </div>
                        <p class="text-muted-foreground text-[11px]">Modal &lt; Rp 100 Juta</p>
                    </div>
                </div>
            </div>

            <!-- Tabel Riwayat Klaim Komisi (1 Row per Tiket Klaim) -->
            <div class="bg-card border-border rounded-2xl border p-4 sm:p-5 shadow-xs space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-border/60 pb-3">
                    <div class="flex items-center gap-2">
                        <FileText class="h-4 w-4 text-primary-600 dark:text-primary-400" />
                        <h2 class="text-foreground text-sm font-bold">
                            Riwayat Permohonan Klaim Komisi
                        </h2>
                    </div>
                </div>

                <AppDataTable
                    :data="claims"
                    :columns="tableColumns"
                    :filters="filters"
                    fetch-url="/investor/commissions"
                    search-placeholder="Cari kode klaim, nama downline, bank..."
                    empty-title="Belum Ada Riwayat Klaim Komisi"
                    empty-message="Anda belum pernah mengajukan klaim komisi atau data pencarian tidak ditemukan."
                >
                    <!-- Filter Slot -->
                    <template #filters="{ applyFilters }">
                        <select
                            :value="filters.status || ''"
                            @change="(e) => applyFilters({ status: e.target.value || undefined, page: 1 })"
                            class="bg-background border-input focus:ring-primary-500 min-h-[38px] w-full rounded-lg border px-3 py-1.5 text-xs font-medium focus:ring-2 focus:outline-none sm:w-auto"
                        >
                            <option value="">Semua Status</option>
                            <option value="pending">Menunggu Transfer</option>
                            <option value="approved_transferred">Berhasil Ditransfer</option>
                            <option value="rejected">Ditolak</option>
                        </select>
                    </template>

                    <!-- Kolom Kode Klaim & Waktu -->
                    <template #column-kode_klaim="{ item }">
                        <div class="space-y-0.5">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="text-foreground font-mono font-bold text-xs">{{ item.kode_klaim }}</span>
                                <span class="rounded bg-sky-500/10 border border-sky-500/30 px-1.5 py-0.5 text-[10px] font-bold text-sky-800 dark:text-sky-300">
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
                                <Users class="h-3.5 w-3.5 text-primary-600 dark:text-primary-400 shrink-0" />
                                <span class="text-foreground font-semibold text-xs">
                                    {{ item.summary_downlines || `${item.total_downline} Investor` }}
                                </span>
                            </div>
                            <div class="flex items-center gap-1 flex-wrap">
                                <span
                                    v-for="(accItem, idx) in item.items.slice(0, 3)"
                                    :key="idx"
                                    class="rounded bg-muted px-1.5 py-0.5 text-[10px] text-muted-foreground font-mono"
                                >
                                    {{ accItem.broker }} ({{ accItem.hari_kehitung }} hari)
                                </span>
                                <span
                                    v-if="item.items.length > 3"
                                    class="rounded bg-muted px-1.5 py-0.5 text-[10px] text-muted-foreground font-medium"
                                >
                                    +{{ item.items.length - 3 }} akun lainnya
                                </span>
                            </div>
                        </div>
                    </template>

                    <!-- Kolom Total Modal Riil -->
                    <template #column-total_modal="{ item }">
                        <div class="space-y-0.5">
                            <span class="text-foreground font-mono font-bold text-xs">{{ formatUSD(item.total_deposit_amount || item.total_modal_riil) }}</span>
                            <p class="text-muted-foreground text-[10px]">{{ item.total_akun }} akun copytrade</p>
                        </div>
                    </template>

                    <!-- Kolom Profit & Transfer -->
                    <template #column-nominal_transfer="{ item }">
                        <div v-if="item.status === 'approved_transferred'" class="space-y-0.5">
                            <span class="text-emerald-700 dark:text-emerald-400 font-mono font-extrabold text-xs">
                                {{ formatUSD(item.total_affiliate_commission || item.total_nominal_transfer) }}
                            </span>
                            <p class="text-muted-foreground text-[10px]">
                                Dari Profit: {{ formatUSD(item.total_master_gross_share || item.total_profit_master) }}
                            </p>
                        </div>
                        <div v-else class="text-muted-foreground text-xs italic">
                            Menunggu Peninjauan
                        </div>
                    </template>

                    <!-- Kolom Status -->
                    <template #column-status="{ item }">
                        <span
                            :class="[
                                'inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-[11px] font-semibold',
                                getStatusBadge(item.status).class,
                            ]"
                        >
                            <component :is="getStatusBadge(item.status).icon" class="h-3 w-3" />
                            <span>{{ getStatusBadge(item.status).label }}</span>
                        </span>
                    </template>

                    <!-- Kolom Aksi -->
                    <template #column-actions="{ item }">
                        <Button
                            variant="ghost"
                            size="sm"
                            @click="openDetailModal(item)"
                            class="h-8 px-2 text-xs"
                        >
                            <Eye class="h-3.5 w-3.5 mr-1" />
                            <span>Rincian</span>
                        </Button>
                    </template>
                </AppDataTable>
            </div>
        </div>

        <!-- Modal Pengajuan Klaim Komisi Baru (Batch) -->
        <SubmitClaimModal
            :show="isSubmitModalOpen"
            :is-open="isSubmitModalOpen"
            :eligible-downlines="eligibleDownlines"
            :default-bank="defaultBank"
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
