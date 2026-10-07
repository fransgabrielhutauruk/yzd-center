<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import { Eye, Clock, CheckCircle2, XCircle, ClipboardCheck, Activity } from 'lucide-vue-next';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AppDataTable from '@/Components/Common/AppDataTable.vue';
import { Button } from '@/Components/ui/button';
import VerifyModal from './VerifyModal.vue';
import DetailModal from './DetailModal.vue';

const props = defineProps({
    activeTab: { type: String, default: 'verifikasi' },
    counts: { type: Object, default: () => ({ pending: 0, rejected: 0, active: 0 }) },
    verifikasi: { type: Object, required: true },
    aktif: { type: Object, required: true },
    verFilters: { type: Object, default: () => ({}) },
    aktFilters: { type: Object, default: () => ({}) },
});

const activeTab = ref(props.activeTab === 'aktif' ? 'aktif' : 'verifikasi');

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

const isVerifyModalOpen = ref(false);
const isDetailModalOpen = ref(false);
const selectedCopytrade = ref(null);

const openVerifyModal = (item) => {
    selectedCopytrade.value = item;
    isVerifyModalOpen.value = true;
};
const openDetailModal = (item) => {
    selectedCopytrade.value = item;
    isDetailModalOpen.value = true;
};

const verColumns = [
    { key: 'created_at', label: 'Diajukan', sortable: true },
    { key: 'user', label: 'Investor', sortable: false },
    { key: 'pengajuan_ke', label: 'Pengajuan Ke', sortable: false },
    { key: 'nomor_akun_trading', label: 'Akun', sortable: true },
    { key: 'tier', label: 'Tier', sortable: true },
    { key: 'modal_riil', label: 'Modal', sortable: true },
    { key: 'status', label: 'Status', sortable: true },
    { key: 'actions', label: 'Aksi', sortable: false, align: 'center' },
];

const aktColumns = [
    { key: 'tanggal_mulai', label: 'Mulai Investasi', sortable: true },
    { key: 'user', label: 'Investor', sortable: false },
    { key: 'nomor_akun_trading', label: 'Tier & Akun', sortable: true },
    { key: 'modal_riil', label: 'Modal Awal', sortable: true },
    { key: 'current_balance', label: 'Saldo Terakhir', sortable: false },
    { key: 'last_recorded_at', label: 'Catatan Terakhir', sortable: false },
    { key: 'actions', label: 'Aksi', sortable: false, align: 'center' },
];

const statusBadge = (status) => {
    if (status === 'rejected') {
        return {
            label: 'Ditolak',
            bg: 'bg-destructive/10 text-destructive border-destructive/30',
            icon: XCircle,
        };
    }
    if (status === 'active') {
        return {
            label: 'Aktif',
            bg: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-500/30',
            icon: CheckCircle2,
        };
    }
    return {
        label: 'Menunggu Verifikasi',
        bg: 'bg-sky-500/10 text-sky-700 dark:text-sky-400 border-sky-500/30',
        icon: Clock,
    };
};

const tierBadgeClass = (tier) => {
    if (tier === 'tier_3')
        return 'bg-amber-500/10 text-amber-800 dark:text-amber-300 border-amber-500/30';
    if (tier === 'tier_2')
        return 'bg-indigo-500/10 text-indigo-800 dark:text-indigo-300 border-indigo-500/30';
    return 'bg-sky-500/10 text-sky-800 dark:text-sky-300 border-sky-500/30';
};

const profitClass = (v) =>
    v === null || v === undefined
        ? 'text-muted-foreground'
        : v >= 0
          ? 'text-emerald-700 dark:text-emerald-400'
          : 'text-destructive';

const selectClass =
    'bg-background border-input focus:ring-primary-500 min-h-[38px] w-full rounded-lg border px-3 py-1.5 text-xs font-medium focus:ring-2 focus:outline-none sm:w-auto';
</script>

<template>
    <Head title="Kelola Copytrade" />
    <AdminLayout
        title="Kelola Copytrade"
        subtitle="Verifikasi pendaftaran akun trading baru dan pantau akun copytrade yang sedang aktif."
        :breadcrumbs="[{ label: 'Menu Utama' }, { label: 'Kelola Copytrade' }]"
    >
        <div class="space-y-6">
            <!-- Tab Navigasi -->
            <div class="border-border border-b">
                <nav class="-mb-px flex gap-6 text-sm font-medium">
                    <button
                        type="button"
                        @click="switchTab('verifikasi')"
                        class="flex min-h-[40px] cursor-pointer items-center gap-2 border-b-2 pb-3 text-xs transition-colors sm:text-sm"
                        :class="
                            activeTab === 'verifikasi'
                                ? 'border-primary-600 text-primary-600 dark:text-primary-400 font-bold'
                                : 'text-muted-foreground hover:text-foreground border-transparent'
                        "
                    >
                        <ClipboardCheck class="h-4 w-4" />
                        <span>Verifikasi</span>
                        <span
                            class="text-foreground rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold dark:bg-slate-800"
                            >{{ counts.pending }}</span
                        >
                    </button>
                    <button
                        type="button"
                        @click="switchTab('aktif')"
                        class="flex min-h-[40px] cursor-pointer items-center gap-2 border-b-2 pb-3 text-xs transition-colors sm:text-sm"
                        :class="
                            activeTab === 'aktif'
                                ? 'border-primary-600 text-primary-600 dark:text-primary-400 font-bold'
                                : 'text-muted-foreground hover:text-foreground border-transparent'
                        "
                    >
                        <Activity class="h-4 w-4" />
                        <span>Akun Aktif</span>
                        <span
                            class="text-foreground rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold dark:bg-slate-800"
                            >{{ counts.active }}</span
                        >
                    </button>
                </nav>
            </div>

            <!-- TAB 1: VERIFIKASI -->
            <div v-show="activeTab === 'verifikasi'">
                <AppDataTable
                    :data="verifikasi"
                    :columns="verColumns"
                    :filters="verFilters"
                    param-prefix="ver_"
                    fetch-url="/admin/copytrade"
                    search-placeholder="Cari investor, username, nomor akun..."
                    empty-title="Tidak Ada Pendaftaran Ditemukan"
                    empty-message="Tidak ada pendaftaran akun copytrade yang cocok dengan filter saat ini."
                >
                    <template #filters="{ applyFilters }">
                        <div
                            class="flex w-full flex-col items-stretch gap-2 sm:w-auto sm:flex-row sm:items-center"
                        >
                            <select
                                :value="verFilters.ver_status || 'pending'"
                                @change="
                                    (e) =>
                                        applyFilters({
                                            ver_status: e.target.value || undefined,
                                            ver_page: 1,
                                        })
                                "
                                :class="selectClass"
                            >
                                <option value="pending">Menunggu Verifikasi</option>
                                <option value="rejected">Ditolak</option>
                                <option value="all">Menunggu &amp; Ditolak</option>
                            </select>
                            <select
                                :value="verFilters.ver_tier || ''"
                                @change="
                                    (e) =>
                                        applyFilters({
                                            ver_tier: e.target.value || undefined,
                                            ver_page: 1,
                                        })
                                "
                                :class="selectClass"
                            >
                                <option value="">Semua Tier</option>
                                <option value="tier_1">Tier 1 (Cent)</option>
                                <option value="tier_2">Tier 2 (Standard)</option>
                                <option value="tier_3">Tier 3 (VPS)</option>
                            </select>
                        </div>
                    </template>

                    <template #column-created_at="{ item }">
                        <span
                            class="text-muted-foreground text-[11px] font-medium whitespace-nowrap"
                            >{{ item.created_at }}</span
                        >
                    </template>
                    <template #column-user="{ item }">
                        <div class="space-y-0.5">
                            <span class="text-foreground text-xs font-bold">{{
                                item.user.name
                            }}</span>
                            <div class="text-muted-foreground text-[11px]">
                                <span>@{{ item.user.username }}</span>
                            </div>
                        </div>
                    </template>
                    <template #column-pengajuan_ke="{ item }">
                        <span
                            v-if="item.pengajuan_ke"
                            class="text-foreground inline-flex items-center rounded-md border border-slate-200 bg-slate-100 px-2 py-0.5 text-xs font-semibold whitespace-nowrap dark:border-slate-700 dark:bg-slate-800"
                        >
                            Pengajuan ke-{{ item.pengajuan_ke }}
                        </span>
                        <span v-else class="text-muted-foreground text-xs">-</span>
                    </template>
                    <template #column-nomor_akun_trading="{ item }">
                        <span class="text-foreground font-mono font-bold">{{
                            item.nomor_akun_trading
                        }}</span>
                    </template>
                    <template #column-tier="{ item }">
                        <div class="space-y-0.5">
                            <span
                                :class="[
                                    'inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold whitespace-nowrap',
                                    tierBadgeClass(item.tier),
                                ]"
                                >{{ item.tier_label }}</span
                            >
                            <div class="text-muted-foreground text-[11px] text-nowrap">
                                {{ item.broker }} ({{ item.tipe_akun }})
                            </div>
                        </div>
                    </template>
                    <template #column-modal_riil="{ item }">
                        <span class="text-foreground font-mono text-xs font-bold">{{
                            item.modal_riil_formatted
                        }}</span>
                    </template>
                    <template #column-status="{ item }">
                        <div
                            :class="[
                                'inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-[10px] font-semibold whitespace-nowrap',
                                statusBadge(item.status).bg,
                            ]"
                        >
                            <component
                                :is="statusBadge(item.status).icon"
                                class="h-3 w-3 shrink-0"
                            />
                            <span>{{ statusBadge(item.status).label }}</span>
                        </div>
                    </template>
                    <template #column-actions="{ item }">
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            class="mx-auto flex min-h-[36px] items-center gap-1.5 text-xs"
                            @click="openVerifyModal(item)"
                        >
                            <Eye class="h-3.5 w-3.5" />
                            <span>Tinjau</span>
                        </Button>
                    </template>
                </AppDataTable>
            </div>

            <!-- TAB 2: AKUN AKTIF -->
            <div v-show="activeTab === 'aktif'">
                <AppDataTable
                    :data="aktif"
                    :columns="aktColumns"
                    :filters="aktFilters"
                    param-prefix="akt_"
                    fetch-url="/admin/copytrade"
                    search-placeholder="Cari investor, username, nomor akun..."
                    empty-title="Belum Ada Akun Aktif"
                    empty-message="Tidak ada akun copytrade aktif yang cocok dengan filter saat ini."
                >
                    <template #filters="{ applyFilters }">
                        <div
                            class="flex w-full flex-col items-stretch gap-2 sm:w-auto sm:flex-row sm:items-center"
                        >
                            <select
                                :value="aktFilters.akt_tier || ''"
                                @change="
                                    (e) =>
                                        applyFilters({
                                            akt_tier: e.target.value || undefined,
                                            akt_page: 1,
                                        })
                                "
                                :class="selectClass"
                            >
                                <option value="">Semua Tier</option>
                                <option value="tier_1">Tier 1 (Cent)</option>
                                <option value="tier_2">Tier 2 (Standard)</option>
                                <option value="tier_3">Tier 3 (VPS)</option>
                            </select>
                            <select
                                :value="aktFilters.akt_catatan || ''"
                                @change="
                                    (e) =>
                                        applyFilters({
                                            akt_catatan: e.target.value || undefined,
                                            akt_page: 1,
                                        })
                                "
                                :class="selectClass"
                            >
                                <option value="">Semua Status Catatan</option>
                                <option value="belum">Belum dicatat bulan ini</option>
                            </select>
                        </div>
                    </template>

                    <template #column-tanggal_mulai="{ item }">
                        <div class="space-y-0.5">
                            <span
                                class="text-muted-foreground text-[11px] font-medium whitespace-nowrap"
                                >{{ item.tanggal_mulai || '-' }}</span
                            >
                            <div class="text-foreground text-[11px] font-semibold">
                                {{ item.active_duration }}
                            </div>
                        </div>
                    </template>
                    <template #column-user="{ item }">
                        <div class="space-y-0.5">
                            <span class="text-foreground text-xs font-bold">{{
                                item.user.name
                            }}</span>
                            <div class="text-muted-foreground text-[11px]">
                                @{{ item.user.username }}
                            </div>
                        </div>
                    </template>
                    <template #column-nomor_akun_trading="{ item }">
                        <div>
                            <div>
                                <span
                                    :class="[
                                        'me-1 inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold whitespace-nowrap',
                                        tierBadgeClass(item.tier),
                                    ]"
                                    >{{ item.tier_label }}</span
                                >
                                <span class="text-muted-foreground text-[11px] text-nowrap">
                                    {{ item.broker }} ({{ item.tipe_akun }})
                                </span>
                            </div>
                            <span class="text-foreground font-mono font-bold">{{
                                item.nomor_akun_trading
                            }}</span>
                        </div>
                    </template>
                    <template #column-modal_riil="{ item }">
                        <span class="text-foreground font-mono text-xs font-bold">{{
                            item.modal_riil_formatted
                        }}</span>
                    </template>
                    <template #column-current_balance="{ item }">
                        <div v-if="item.current_balance_formatted" class="space-y-0.5">
                            <span class="text-foreground font-mono text-xs font-bold block">
                                {{ item.current_balance_formatted }}
                            </span>
                            <span
                                v-if="item.profit !== null"
                                :class="['font-mono text-[11px] font-semibold block', profitClass(item.profit)]"
                            >
                                {{ item.profit_formatted }}
                            </span>
                        </div>
                        <span v-else class="text-muted-foreground text-xs">Belum ada catatan</span>
                    </template>
                    <template #column-last_recorded_at="{ item }">
                        <div class="space-y-1">
                            <span
                                class="text-foreground text-[11px] font-medium whitespace-nowrap"
                                >{{ item.last_recorded_at || '-' }}</span
                            >
                        </div>
                    </template>
                    <template #column-actions="{ item }">
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            class="flex min-h-[36px] items-center mx-auto gap-1.5 text-xs"
                            @click="openDetailModal(item)"
                        >
                            <Eye class="h-3.5 w-3.5" />
                            <span>Detail</span>
                        </Button>
                    </template>
                </AppDataTable>
            </div>
        </div>

        <VerifyModal
            :show="isVerifyModalOpen"
            :copytrade="selectedCopytrade"
            @close="isVerifyModalOpen = false"
        />
        <DetailModal
            :show="isDetailModalOpen"
            :copytrade="selectedCopytrade"
            @close="isDetailModalOpen = false"
        />
    </AdminLayout>
</template>
