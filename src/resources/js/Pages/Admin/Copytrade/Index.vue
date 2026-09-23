<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    Eye,
    TrendingUp,
    Clock,
    CheckCircle2,
    XCircle,
    Layers,
    Filter,
} from 'lucide-vue-next';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AppDataTable from '@/Components/Common/AppDataTable.vue';
import { Button } from '@/Components/ui/button';
import VerifyModal from './VerifyModal.vue';

const props = defineProps({
    copytrades: {
        type: Object,
        required: true,
    },
    counts: {
        type: Object,
        default: () => ({
            pending: 0,
            active: 0,
            rejected: 0,
            all: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const isVerifyModalOpen = ref(false);
const selectedCopytrade = ref(null);

const columns = [
    { key: 'created_at', label: 'Diajukan', sortable: true },
    { key: 'user', label: 'Investor', sortable: false },
    { key: 'nomor_akun_trading', label: 'Akun & Broker', sortable: true },
    { key: 'tier', label: 'Tier', sortable: true },
    { key: 'modal_riil', label: 'Modal Riil', sortable: true },
    { key: 'status', label: 'Status', sortable: true },
    { key: 'actions', label: 'Aksi', sortable: false, align: 'right' },
];

const openVerifyModal = (copytrade) => {
    selectedCopytrade.value = copytrade;
    isVerifyModalOpen.value = true;
};

const getStatusBadge = (status) => {
    switch (status) {
        case 'active':
            return {
                label: 'Aktif & Disetujui',
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
                bg: 'bg-sky-500/10 text-sky-700 dark:text-sky-400 border-sky-500/30',
                icon: Clock,
            };
    }
};

const getTierBadge = (tier) => {
    switch (tier) {
        case 'tier_3':
            return {
                label: 'Tier 3 (VPS)',
                bg: 'bg-amber-500/10 text-amber-800 dark:text-amber-300 border-amber-500/30',
            };
        case 'tier_2':
            return {
                label: 'Tier 2 (Standard)',
                bg: 'bg-indigo-500/10 text-indigo-800 dark:text-indigo-300 border-indigo-500/30',
            };
        case 'tier_1':
        default:
            return {
                label: 'Tier 1 (Cent)',
                bg: 'bg-sky-500/10 text-sky-800 dark:text-sky-300 border-sky-500/30',
            };
    }
};

const filterByQuickStatus = (statusValue) => {
    router.get(
        '/admin/copytrade',
        {
            ...props.filters,
            status: statusValue === props.filters.status ? undefined : statusValue,
            page: 1,
        },
        { preserveState: true, preserveScroll: true }
    );
};
</script>

<template>
    <Head title="Verifikasi Akun Copytrade" />
    <AdminLayout title="Verifikasi Akun Copytrade">
        <div class="space-y-6">
            <!-- Header Section -->
            <div
                class="bg-card border-border flex flex-col justify-between gap-4 rounded-2xl border p-6 shadow-xs sm:flex-row sm:items-center"
            >
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <TrendingUp class="h-5 w-5 text-primary-600 dark:text-primary-400" />
                        <h1 class="text-foreground text-xl font-bold tracking-tight">
                            Verifikasi Permohonan Copytrade
                        </h1>
                    </div>
                    <p class="text-muted-foreground text-xs leading-relaxed">
                        Tinjau dan verifikasi pendaftaran akun trading MT4 investor, kesesuaian broker, dan kelengkapan surat perjanjian kemitraan.
                    </p>
                </div>
            </div>

            <!-- Quick Stats Cards (Clickable Filter Shortcuts) -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                <button
                    type="button"
                    @click="filterByQuickStatus('pending')"
                    :class="[
                        'p-4 rounded-xl border text-left transition-all',
                        filters.status === 'pending'
                            ? 'border-sky-500 bg-sky-500/10 ring-1 ring-sky-500/30'
                            : 'border-border bg-card hover:bg-muted/40',
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-sky-700 dark:text-sky-400">Menunggu</span>
                        <Clock class="h-4 w-4 text-sky-600 dark:text-sky-400" />
                    </div>
                    <p class="mt-2 text-2xl font-bold text-foreground font-mono">{{ counts.pending }}</p>
                    <span class="text-[11px] text-muted-foreground">Perlu ditinjau</span>
                </button>

                <button
                    type="button"
                    @click="filterByQuickStatus('active')"
                    :class="[
                        'p-4 rounded-xl border text-left transition-all',
                        filters.status === 'active'
                            ? 'border-emerald-500 bg-emerald-500/10 ring-1 ring-emerald-500/30'
                            : 'border-border bg-card hover:bg-muted/40',
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-emerald-700 dark:text-emerald-400">Aktif & Berjalan</span>
                        <CheckCircle2 class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                    </div>
                    <p class="mt-2 text-2xl font-bold text-foreground font-mono">{{ counts.active }}</p>
                    <span class="text-[11px] text-muted-foreground">Telah disetujui</span>
                </button>

                <button
                    type="button"
                    @click="filterByQuickStatus('rejected')"
                    :class="[
                        'p-4 rounded-xl border text-left transition-all',
                        filters.status === 'rejected'
                            ? 'border-destructive bg-destructive/10 ring-1 ring-destructive/30'
                            : 'border-border bg-card hover:bg-muted/40',
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-destructive">Ditolak</span>
                        <XCircle class="h-4 w-4 text-destructive" />
                    </div>
                    <p class="mt-2 text-2xl font-bold text-foreground font-mono">{{ counts.rejected }}</p>
                    <span class="text-[11px] text-muted-foreground">Perlu revisi</span>
                </button>

                <button
                    type="button"
                    @click="filterByQuickStatus('all')"
                    :class="[
                        'p-4 rounded-xl border text-left transition-all',
                        filters.status === 'all'
                            ? 'border-primary-500 bg-primary-500/10 ring-1 ring-primary-500/30'
                            : 'border-border bg-card hover:bg-muted/40',
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-muted-foreground">Semua Akun</span>
                        <Layers class="h-4 w-4 text-muted-foreground" />
                    </div>
                    <p class="mt-2 text-2xl font-bold text-foreground font-mono">{{ counts.all }}</p>
                    <span class="text-[11px] text-muted-foreground">Total pendaftaran</span>
                </button>
            </div>

            <!-- Tabel AppDataTable Server-Side -->
            <AppDataTable
                :data="copytrades"
                :columns="columns"
                :filters="filters"
                fetch-url="/admin/copytrade"
                search-placeholder="Cari investor, username, nomor akun, atau bank..."
                empty-title="Tidak Ada Pendaftaran Copytrade Ditemukan"
                empty-message="Belum ada pendaftaran akun copytrade atau tidak ada data yang cocok dengan kriteria pencarian Anda."
            >
                <!-- Filter Slot -->
                <template #filters="{ applyFilters }">
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 w-full sm:w-auto">
                        <!-- Status Dropdown -->
                        <select
                            :value="filters.status || 'pending'"
                            @change="(e) => applyFilters({ status: e.target.value || undefined, page: 1 })"
                            class="bg-background border-input focus:ring-primary-500 min-h-[38px] w-full rounded-lg border px-3 py-1.5 text-xs font-medium focus:ring-2 focus:outline-none sm:w-auto"
                        >
                            <option value="pending">Menunggu Verifikasi</option>
                            <option value="active">Aktif & Disetujui</option>
                            <option value="rejected">Ditolak</option>
                            <option value="all">Semua Status</option>
                        </select>

                        <!-- Tier Dropdown -->
                        <select
                            :value="filters.tier || ''"
                            @change="(e) => applyFilters({ tier: e.target.value || undefined, page: 1 })"
                            class="bg-background border-input focus:ring-primary-500 min-h-[38px] w-full rounded-lg border px-3 py-1.5 text-xs font-medium focus:ring-2 focus:outline-none sm:w-auto"
                        >
                            <option value="">Semua Tier</option>
                            <option value="tier_1">Tier 1 (Cent)</option>
                            <option value="tier_2">Tier 2 (Standard)</option>
                            <option value="tier_3">Tier 3 (VPS)</option>
                        </select>
                    </div>
                </template>

                <!-- Custom Column: Diajukan -->
                <template #column-created_at="{ item }">
                    <span class="text-muted-foreground text-[11px] font-medium whitespace-nowrap">
                        {{ item.created_at }}
                    </span>
                </template>

                <!-- Custom Column: Investor -->
                <template #column-user="{ item }">
                    <div class="space-y-0.5">
                        <span class="text-foreground font-bold text-xs">{{ item.user.name }}</span>
                        <div class="text-muted-foreground text-[11px]">
                            <span>@{{ item.user.username }}</span>
                            <span v-if="item.user.phone"> &bull; {{ item.user.phone }}</span>
                        </div>
                    </div>
                </template>

                <!-- Custom Column: Akun & Broker -->
                <template #column-nomor_akun_trading="{ item }">
                    <div class="space-y-0.5">
                        <span class="text-foreground font-mono font-bold">{{ item.nomor_akun_trading }}</span>
                        <div class="text-muted-foreground text-[11px]">
                            <span>{{ item.broker }} ({{ item.tipe_akun }})</span>
                        </div>
                    </div>
                </template>

                <!-- Custom Column: Tier -->
                <template #column-tier="{ item }">
                    <span
                        :class="[
                            'inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold whitespace-nowrap',
                            getTierBadge(item.tier).bg,
                        ]"
                    >
                        {{ item.tier_label }}
                    </span>
                </template>

                <!-- Custom Column: Modal Riil -->
                <template #column-modal_riil="{ item }">
                    <span class="text-foreground font-mono font-bold text-xs">{{ item.modal_riil_formatted }}</span>
                </template>

                <!-- Custom Column: Status -->
                <template #column-status="{ item }">
                    <div
                        :class="[
                            'inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-[10px] font-semibold whitespace-nowrap',
                            getStatusBadge(item.status).bg,
                        ]"
                    >
                        <component :is="getStatusBadge(item.status).icon" class="h-3 w-3 shrink-0" />
                        <span>{{ getStatusBadge(item.status).label }}</span>
                    </div>
                </template>

                <!-- Custom Column: Aksi -->
                <template #column-actions="{ item }">
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        class="flex items-center gap-1.5 text-xs"
                        @click="openVerifyModal(item)"
                    >
                        <Eye class="h-3.5 w-3.5" />
                        <span>Tinjau</span>
                    </Button>
                </template>
            </AppDataTable>
        </div>

        <!-- ========================================== -->
        <!-- MODAL VERIFIKASI AKUN COPYTRADE (ADMIN)    -->
        <!-- ========================================== -->
        <VerifyModal
            :show="isVerifyModalOpen"
            :copytrade="selectedCopytrade"
            @close="isVerifyModalOpen = false"
        />
    </AdminLayout>
</template>
