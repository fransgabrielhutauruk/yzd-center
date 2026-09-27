<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AppDataTable from '@/Components/Common/AppDataTable.vue';
import { Button } from '@/Components/ui/button';
import ProcessModal from './ProcessModal.vue';
import {
    Banknote,
    Clock,
    CheckCircle2,
    XCircle,
    User,
    FileText,
    ExternalLink,
    CreditCard,
    TrendingUp,
    ShieldAlert,
    Users,
} from 'lucide-vue-next';

const props = defineProps({
    claims: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    counts: {
        type: Object,
        required: true,
    },
});

const isProcessModalOpen = ref(false);
const selectedClaim = ref(null);

const openProcessModal = (claim) => {
    selectedClaim.value = claim;
    isProcessModalOpen.value = true;
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
    { key: 'kode_klaim', label: 'Kode / Waktu', sortable: true },
    { key: 'affiliator', label: 'Mitra Affiliator' },
    { key: 'rincian_downline', label: 'Investor Downline & Akun' },
    { key: 'total_modal', label: 'Total Modal Riil', sortable: true },
    { key: 'nominal_transfer', label: 'Profit & Transfer' },
    { key: 'status', label: 'Status Pencairan', sortable: true },
    { key: 'actions', label: 'Aksi' },
];

const applyQuickFilter = (status) => {
    router.get(
        route('admin.commissions.index'),
        { ...props.filters, status: status || undefined, page: 1 },
        { preserveState: true, preserveScroll: true }
    );
};

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
    <Head title="Pencairan Komisi Afiliasi" />

    <AdminLayout
        title="Pencairan Komisi Afiliasi (PB 3)"
        subtitle="Verifikasi permohonan klaim komisi periode 90 hari dari affiliator dan selesaikan pencairan transfer profit Master Trader per akun copytrade."
        :breadcrumbs="[
            { label: 'Manajemen Afiliasi' },
            { label: 'Pencairan Komisi' }
        ]"
    >
        <div class="space-y-6">

            <!-- Kartu Statistik & Quick Filter -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-3 sm:gap-4">
                <!-- Total Ditransfer -->
                <div class="col-span-2 md:col-span-1 bg-card border-border rounded-2xl border p-4 shadow-xs space-y-1">
                    <div class="flex items-center justify-between text-muted-foreground">
                        <span class="text-xs font-medium">Total Dicairkan</span>
                        <Banknote class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                    </div>
                    <p class="text-emerald-800 dark:text-emerald-300 font-mono text-base font-extrabold truncate">
                        {{ formatRupiah(counts.total_transferred) }}
                    </p>
                    <p class="text-muted-foreground text-[10px]">
                        Seluruh pencairan sukses
                    </p>
                </div>

                <!-- Semua Klaim -->
                <button
                    type="button"
                    @click="applyQuickFilter('')"
                    :class="[
                        'bg-card rounded-2xl border p-4 shadow-xs space-y-1 text-left transition-all',
                        !filters.status
                            ? 'border-primary-500 ring-2 ring-primary-500/20'
                            : 'border-border hover:border-primary-500/50',
                    ]"
                >
                    <div class="flex items-center justify-between text-muted-foreground">
                        <span class="text-xs font-medium">Semua Klaim</span>
                        <FileText class="h-4 w-4 text-primary-600" />
                    </div>
                    <p class="text-foreground font-mono text-base font-extrabold">
                        {{ counts.all }}
                    </p>
                    <p class="text-muted-foreground text-[10px]">
                        Total tiket klaim
                    </p>
                </button>

                <!-- Menunggu Transfer (Pending) -->
                <button
                    type="button"
                    @click="applyQuickFilter('pending')"
                    :class="[
                        'bg-card rounded-2xl border p-4 shadow-xs space-y-1 text-left transition-all',
                        filters.status === 'pending'
                            ? 'border-amber-500 ring-2 ring-amber-500/20'
                            : 'border-border hover:border-amber-500/50',
                    ]"
                >
                    <div class="flex items-center justify-between text-amber-600">
                        <span class="text-xs font-medium">Menunggu Transfer</span>
                        <Clock class="h-4 w-4" />
                    </div>
                    <p class="text-amber-800 dark:text-amber-300 font-mono text-base font-extrabold">
                        {{ counts.pending }}
                    </p>
                    <p class="text-muted-foreground text-[10px]">
                        Perlu diproses
                    </p>
                </button>

                <!-- Berhasil Ditransfer -->
                <button
                    type="button"
                    @click="applyQuickFilter('approved_transferred')"
                    :class="[
                        'bg-card rounded-2xl border p-4 shadow-xs space-y-1 text-left transition-all',
                        filters.status === 'approved_transferred'
                            ? 'border-emerald-500 ring-2 ring-emerald-500/20'
                            : 'border-border hover:border-emerald-500/50',
                    ]"
                >
                    <div class="flex items-center justify-between text-emerald-600">
                        <span class="text-xs font-medium">Selesai Ditransfer</span>
                        <CheckCircle2 class="h-4 w-4" />
                    </div>
                    <p class="text-emerald-800 dark:text-emerald-300 font-mono text-base font-extrabold">
                        {{ counts.approved }}
                    </p>
                    <p class="text-muted-foreground text-[10px]">
                        Dana telah dicairkan
                    </p>
                </button>

                <!-- Ditolak -->
                <button
                    type="button"
                    @click="applyQuickFilter('rejected')"
                    :class="[
                        'bg-card rounded-2xl border p-4 shadow-xs space-y-1 text-left transition-all',
                        filters.status === 'rejected'
                            ? 'border-rose-500 ring-2 ring-rose-500/20'
                            : 'border-border hover:border-rose-500/50',
                    ]"
                >
                    <div class="flex items-center justify-between text-rose-600">
                        <span class="text-xs font-medium">Ditolak</span>
                        <XCircle class="h-4 w-4" />
                    </div>
                    <p class="text-rose-800 dark:text-rose-300 font-mono text-base font-extrabold">
                        {{ counts.rejected }}
                    </p>
                    <p class="text-muted-foreground text-[10px]">
                        Tidak memenuhi syarat
                    </p>
                </button>
            </div>

            <!-- Tabel Data Antrean Klaim Komisi (1 Row per Tiket Klaim) -->
            <div class="bg-card border-border rounded-2xl border p-4 sm:p-5 shadow-xs space-y-4">
                <AppDataTable
                    :data="claims"
                    :columns="tableColumns"
                    :filters="filters"
                    fetch-url="/admin/commissions"
                    search-placeholder="Cari kode klaim, affiliator, downline, bank..."
                    empty-title="Tidak Ada Antrean Klaim Komisi"
                    empty-message="Saat ini belum ada permohonan klaim komisi affiliator yang perlu diproses."
                >
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

                    <!-- Kolom Mitra Affiliator -->
                    <template #column-affiliator="{ item }">
                        <div class="space-y-0.5">
                            <p class="text-foreground font-semibold text-xs">{{ item.affiliator?.name || '-' }}</p>
                            <p class="text-muted-foreground font-mono text-[11px]">@{{ item.affiliator?.username || '-' }}</p>
                            <p class="text-muted-foreground text-[10px] font-mono uppercase">
                                {{ item.nama_bank }} - {{ item.nomor_rekening }}
                            </p>
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

                    <!-- Kolom Profit & Nominal Transfer -->
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
                            Menunggu Diproses
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
                            variant="outline"
                            size="sm"
                            @click="openProcessModal(item)"
                            class="h-8 px-2.5 text-xs font-semibold"
                        >
                            <span>{{ item.status === 'pending' ? 'Proses Pencairan' : 'Lihat Rincian' }}</span>
                        </Button>
                    </template>
                </AppDataTable>
            </div>
        </div>

        <!-- Modal Eksekusi Verifikasi & Pencairan Komisi -->
        <ProcessModal
            :show="isProcessModalOpen"
            :is-open="isProcessModalOpen"
            :claim="selectedClaim"
            @close="isProcessModalOpen = false"
        />
    </AdminLayout>
</template>
