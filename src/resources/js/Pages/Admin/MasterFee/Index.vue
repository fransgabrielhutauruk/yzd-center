<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AppDataTable from '@/Components/Common/AppDataTable.vue';
import AppButton from '@/Components/Common/AppButton.vue';
import AppTabs from '@/Components/Common/AppTabs.vue';
import VerifyModal from './VerifyModal.vue';
import {
    Receipt,
    Wallet,
    CheckCircle2,
    Clock,
    XCircle,
    AlertCircle,
    Settings,
    Eye,
    Filter,
    Calendar,
    AlertTriangle,
    Layers,
    User,
} from 'lucide-vue-next';

const props = defineProps({
    tagihans: { type: Object, required: true },
    missing: { type: Object, required: true },
    summary: { type: Object, required: true },
    settings: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    current_tab: { type: String, default: 'tagihan' },
});

const activeTab = ref(props.current_tab || 'tagihan');
const selectedTagihan = ref(null);
const isVerifyModalOpen = ref(false);

const openVerifyModal = (item) => {
    selectedTagihan.value = item;
    isVerifyModalOpen.value = true;
};

const closeVerifyModal = () => {
    isVerifyModalOpen.value = false;
    selectedTagihan.value = null;
};

const tabs = computed(() => [
    {
        key: 'tagihan',
        label: 'Daftar Tagihan Master Fee',
        icon: Receipt,
        count: props.summary?.menunggu_verifikasi_count > 0 ? props.summary.menunggu_verifikasi_count : undefined,
        badgeVariant: 'warning',
    },
    {
        key: 'belum_rekap',
        label: 'Akun Belum Rekap Saldo',
        icon: Layers,
        count: props.summary?.belum_rekap_count > 0 ? props.summary.belum_rekap_count : undefined,
    },
]);

const formatUSD = (val) => {
    return '$' + Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const parseDate = (val) => {
    if (!val) return null;
    if (typeof val === 'string' && val.length === 10) {
        return new Date(val + 'T00:00:00');
    }
    return new Date(val);
};

const formatPeriode = (val) => {
    const d = parseDate(val);
    if (!d || isNaN(d.getTime())) return '-';
    return d.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });
};

const formatTanggal = (val) => {
    const d = parseDate(val);
    if (!d || isNaN(d.getTime())) return '-';
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};

const maxMonth = computed(() => {
    const d = new Date();
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    return `${year}-${month}`;
});

const tagihanColumns = [
    { key: 'nomor', label: 'No. Tagihan' },
    { key: 'user', label: 'Investor' },
    { key: 'akun', label: 'Akun Trading' },
    { key: 'periode', label: 'Periode Profit' },
    { key: 'profit_bersih', label: 'Profit Bersih', align: 'right' },
    { key: 'nominal_fee', label: 'Master Fee', align: 'right' },
    { key: 'jatuh_tempo', label: 'Jatuh Tempo' },
    { key: 'status', label: 'Status', align: 'center' },
    { key: 'actions', label: 'Aksi', align: 'center' },
];

const missingColumns = [
    { key: 'nomor_akun_trading', label: 'Nomor Akun' },
    { key: 'investor', label: 'Investor' },
    { key: 'broker', label: 'Broker' },
    { key: 'modal_riil', label: 'Modal Awal', align: 'right' },
    { key: 'status', label: 'Status Akun', align: 'center' },
];
</script>

<template>
    <AdminLayout
        title="Kelola Master Fee"
        :subtitle="`Monitoring tagihan bagi hasil (Performance Fee ${settings.fee_persen}%), verifikasi bukti pembayaran, dan akun belum rekap.`"
        :breadcrumbs="[{ label: 'Menu Utama' }, { label: 'Master Fee' }]"
    >
        <template #header-actions>
            <Link :href="route('admin.settings.master_fee')">
                <AppButton
                    type="custom"
                    variant="outline"
                    size="sm"
                    label="Pengaturan Rekening & Fee"
                    :icon="Settings"
                />
            </Link>
        </template>

        <Head title="Kelola Master Fee" />

        <div class="space-y-6">
            <!-- 1. Kartu Ringkasan Metrik Master Fee -->
            <div class="grid grid-cols-2 lg:grid-cols-6 gap-3">
                <!-- Total Tagihan -->
                <div class="rounded-xl border border-border bg-card p-3.5 space-y-1">
                    <span class="text-[11px] font-semibold text-muted-foreground">Total Tagihan</span>
                    <div class="font-mono text-base font-black text-foreground">
                        {{ formatUSD(summary.total_tagihan_usd) }}
                    </div>
                    <span class="text-[10px] text-muted-foreground block">
                        {{ summary.total_tagihan_count }} invoice
                    </span>
                </div>

                <!-- Lunas -->
                <div class="rounded-xl border border-emerald-500/20 bg-emerald-500/5 dark:bg-emerald-500/10 p-3.5 space-y-1">
                    <span class="text-[11px] font-semibold text-emerald-700 dark:text-emerald-400">Lunas</span>
                    <div class="font-mono text-base font-black text-emerald-700 dark:text-emerald-400">
                        {{ formatUSD(summary.lunas_usd) }}
                    </div>
                    <span class="text-[10px] text-muted-foreground block">
                        {{ summary.lunas_count }} tagihan
                    </span>
                </div>

                <!-- Menunggu Verifikasi -->
                <div
                    class="rounded-xl border p-3.5 space-y-1"
                    :class="
                        summary.menunggu_verifikasi_count > 0
                            ? 'border-amber-500/30 bg-amber-500/10 text-amber-900 dark:text-amber-200'
                            : 'border-border bg-card text-muted-foreground'
                    "
                >
                    <span class="text-[11px] font-semibold">Menunggu Verifikasi</span>
                    <div class="font-mono text-base font-black">
                        {{ formatUSD(summary.menunggu_verifikasi_usd) }}
                    </div>
                    <span class="text-[10px] block">
                        {{ summary.menunggu_verifikasi_count }} perlu tindakan
                    </span>
                </div>

                <!-- Belum Dibayar -->
                <div class="rounded-xl border border-border bg-card p-3.5 space-y-1">
                    <span class="text-[11px] font-semibold text-muted-foreground">Belum Dibayar</span>
                    <div class="font-mono text-base font-black text-foreground">
                        {{ formatUSD(summary.belum_dibayar_usd) }}
                    </div>
                    <span class="text-[10px] text-muted-foreground block">
                        {{ summary.belum_dibayar_count }} tagihan
                    </span>
                </div>

                <!-- Terlambat -->
                <div
                    class="rounded-xl border p-3.5 space-y-1"
                    :class="
                        summary.terlambat_count > 0
                            ? 'border-rose-500/30 bg-rose-500/10 text-rose-900 dark:text-rose-200'
                            : 'border-border bg-card text-muted-foreground'
                    "
                >
                    <span class="text-[11px] font-semibold">Terlambat</span>
                    <div class="font-mono text-base font-black">
                        {{ formatUSD(summary.terlambat_usd) }}
                    </div>
                    <span class="text-[10px] block">
                        {{ summary.terlambat_count }} melewati jatuh tempo
                    </span>
                </div>

                <!-- Belum Rekap -->
                <div class="rounded-xl border border-border bg-card p-3.5 space-y-1">
                    <span class="text-[11px] font-semibold text-muted-foreground">Belum Rekap</span>
                    <div class="font-mono text-base font-black text-foreground">
                        {{ summary.belum_rekap_count }} Akun
                    </div>
                    <span class="text-[10px] text-muted-foreground block">
                        Akun aktif tanpa saldo
                    </span>
                </div>
            </div>

            <!-- 4. Tab Navigasi -->
            <AppTabs v-model="activeTab" :items="tabs" />

            <!-- 5. Konten Tab 1: Tabel Tagihan -->
            <div v-show="activeTab === 'tagihan'" class="rounded-2xl border border-border bg-card p-4 sm:p-5 shadow-xs">
                <AppDataTable
                    :data="tagihans"
                    :columns="tagihanColumns"
                    :filters="filters"
                    fetch-url="/admin/master-fee"
                    search-placeholder="Cari nomor tagihan, nama member, atau nomor akun..."
                >
                    <!-- Filter Tambahan -->
                    <template #filters="{ applyFilters }">
                        <div class="flex items-center gap-2.5 flex-wrap">
                            <!-- Periode Bulan Dari -->
                            <div class="flex items-center gap-1.5">
                                <label class="text-xs font-semibold text-muted-foreground shrink-0">
                                    Periode:
                                </label>
                                <input
                                    type="month"
                                    :value="filters.periode_dari || ''"
                                    :max="filters.periode_sampai && filters.periode_sampai < maxMonth ? filters.periode_sampai : maxMonth"
                                    @change="(e) => applyFilters({ periode_dari: e.target.value > maxMonth ? maxMonth : (e.target.value || undefined), tagihan_page: 1 })"
                                    class="h-9 w-36 rounded-lg border border-input bg-background px-2.5 py-1 text-xs text-foreground focus:ring-2 focus:ring-primary focus:outline-none"
                                    title="Bulan Tahun Mulai"
                                />
                            </div>

                            <span class="text-xs font-bold text-muted-foreground">s/d</span>

                            <!-- Periode Bulan Sampai -->
                            <div class="flex items-center gap-1.5">
                                <input
                                    type="month"
                                    :value="filters.periode_sampai || ''"
                                    :min="filters.periode_dari || undefined"
                                    :max="maxMonth"
                                    @change="(e) => applyFilters({ periode_sampai: e.target.value > maxMonth ? maxMonth : (e.target.value || undefined), tagihan_page: 1 })"
                                    class="h-9 w-36 rounded-lg border border-input bg-background px-2.5 py-1 text-xs text-foreground focus:ring-2 focus:ring-primary focus:outline-none"
                                    title="Bulan Tahun Selesai"
                                />
                            </div>

                            <!-- Status Tagihan -->
                            <div class="flex items-center gap-1.5 ml-0 sm:ml-2">
                                <label class="text-xs font-semibold text-muted-foreground shrink-0">
                                    Status:
                                </label>
                                <select
                                    :value="filters.status || ''"
                                    @change="(e) => applyFilters({ status: e.target.value || undefined, tagihan_page: 1 })"
                                    class="h-9 rounded-lg border border-input bg-background px-3 py-1 text-xs text-foreground focus:ring-2 focus:ring-primary focus:outline-none"
                                >
                                    <option value="">Semua Status</option>
                                    <option value="menunggu_verifikasi">Menunggu Verifikasi</option>
                                    <option value="belum_dibayar">Belum Dibayar</option>
                                    <option value="lunas">Lunas</option>
                                    <option value="ditolak">Ditolak</option>
                                </select>
                            </div>
                        </div>
                    </template>

                    <!-- Slot Nomor -->
                    <template #column-nomor="{ item }">
                        <span class="font-mono font-bold text-xs text-foreground">
                            {{ item.nomor }}
                        </span>
                    </template>

                    <!-- Slot Investor -->
                    <template #column-user="{ item }">
                        <div class="space-y-0.5">
                            <div class="font-semibold text-xs text-foreground">
                                {{ item.user?.name || '-' }}
                            </div>
                            <div class="text-[11px] text-muted-foreground font-mono">
                                @{{ item.user?.username || '-' }}
                            </div>
                        </div>
                    </template>

                    <!-- Slot Akun Trading -->
                    <template #column-akun="{ item }">
                        <div class="space-y-0.5">
                            <div class="font-mono font-semibold text-xs text-foreground">
                                No. {{ item.akun?.nomor_akun_trading || '-' }}
                            </div>
                            <div class="text-[11px] text-muted-foreground">
                                {{ item.akun?.broker || '-' }} <span v-if="item.akun?.tipe_akun">&bull; {{ item.akun.tipe_akun.toUpperCase() }}</span>
                            </div>
                        </div>
                    </template>

                    <!-- Slot Periode -->
                    <template #column-periode="{ item }">
                        <span class="text-xs font-medium text-foreground">
                            {{ formatPeriode(item.periode) }}
                        </span>
                    </template>

                    <!-- Slot Profit Bersih -->
                    <template #column-profit_bersih="{ item }">
                        <span class="font-mono font-bold text-xs text-emerald-600 dark:text-emerald-400">
                            +{{ formatUSD(item.profit_bersih) }}
                        </span>
                    </template>

                    <!-- Slot Nominal Fee -->
                    <template #column-nominal_fee="{ item }">
                        <span class="font-mono font-black text-xs text-foreground">
                            {{ formatUSD(item.nominal_fee) }}
                        </span>
                    </template>

                    <!-- Slot Jatuh Tempo -->
                    <template #column-jatuh_tempo="{ item }">
                        <span class="text-xs text-muted-foreground">
                            {{ formatTanggal(item.jatuh_tempo) }}
                        </span>
                    </template>

                    <!-- Slot Status -->
                    <template #column-status="{ item }">
                        <span
                            :class="[
                                'inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold text-nowrap',
                                item.status === 'lunas'
                                    ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20'
                                    : item.status === 'menunggu_verifikasi'
                                      ? 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/20'
                                      : item.is_terlambat
                                        ? 'bg-rose-500/10 text-rose-700 dark:text-rose-400 border border-rose-500/20 font-bold'
                                        : item.status === 'ditolak'
                                          ? 'bg-rose-500/10 text-rose-700 dark:text-rose-400 border border-rose-500/20'
                                          : 'bg-slate-500/10 text-slate-700 dark:text-slate-300 border border-slate-500/20'
                            ]"
                        >
                            <CheckCircle2 v-if="item.status === 'lunas'" class="h-3 w-3" />
                            <Clock v-else-if="item.status === 'menunggu_verifikasi'" class="h-3 w-3" />
                            <XCircle v-else-if="item.status === 'ditolak'" class="h-3 w-3" />
                            <AlertCircle v-else-if="item.is_terlambat" class="h-3 w-3" />
                            <span>{{ item.is_terlambat ? 'Terlambat' : item.status_label }}</span>
                        </span>
                    </template>

                    <!-- Slot Aksi -->
                    <template #column-actions="{ item }">
                        <AppButton
                            type="custom"
                            size="xs"
                            :variant="item.status === 'menunggu_verifikasi' ? 'default' : 'outline'"
                            :label="item.status === 'menunggu_verifikasi' ? 'Periksa Bukti' : 'Rincian'"
                            :icon="item.status === 'menunggu_verifikasi' ? CheckCircle2 : Eye"
                            @click="openVerifyModal(item)"
                        />
                    </template>
                </AppDataTable>
            </div>

            <!-- 6. Konten Tab 2: Akun Belum Rekap Saldo -->
            <div v-show="activeTab === 'belum_rekap'" class="rounded-2xl border border-border bg-card p-4 sm:p-5 shadow-xs">
                <AppDataTable
                    :data="missing"
                    :columns="missingColumns"
                    :filters="filters"
                    fetch-url="/admin/master-fee"
                    param-prefix="missing_"
                    search-placeholder="Cari nomor akun atau nama investor..."
                >
                    <template #filters="{ applyFilters }">
                        <div class="flex items-center gap-1.5">
                            <label class="text-xs font-semibold text-muted-foreground shrink-0">
                                Target Rekap Bulan:
                            </label>
                            <input
                                type="month"
                                :value="filters.missing_periode || ''"
                                :max="maxMonth"
                                @change="(e) => applyFilters({ missing_periode: e.target.value > maxMonth ? maxMonth : (e.target.value || undefined), missing_page: 1 })"
                                class="h-9 w-36 rounded-lg border border-input bg-background px-2.5 py-1 text-xs text-foreground focus:ring-2 focus:ring-primary focus:outline-none"
                            />
                        </div>
                    </template>

                    <template #column-nomor_akun_trading="{ item }">
                        <span class="font-mono font-bold text-xs text-foreground">
                            {{ item.nomor_akun_trading }}
                        </span>
                    </template>

                    <template #column-investor="{ item }">
                        <div class="space-y-0.5">
                            <div class="font-semibold text-xs text-foreground">
                                {{ item.user?.name || '-' }}
                            </div>
                            <div class="text-[11px] text-muted-foreground font-mono">
                                @{{ item.user?.username || '-' }}
                            </div>
                        </div>
                    </template>

                    <template #column-broker="{ item }">
                        <span class="text-xs text-foreground">
                            {{ item.broker }}
                        </span>
                    </template>

                    <template #column-modal_riil="{ item }">
                        <span class="font-mono font-bold text-xs text-foreground">
                            {{ formatUSD(item.modal_riil) }}
                        </span>
                    </template>

                    <template #column-status="{ item }">
                        <span class="inline-flex items-center rounded-full bg-emerald-500/10 px-2 py-0.5 text-xs font-semibold text-emerald-700 dark:text-emerald-400 border border-emerald-500/20">
                            Aktif
                        </span>
                    </template>
                </AppDataTable>
            </div>
        </div>

        <!-- Modal Verifikasi & Penolakan Tagihan -->
        <VerifyModal
            :show="isVerifyModalOpen"
            :tagihan="selectedTagihan"
            @close="closeVerifyModal"
        />
    </AdminLayout>
</template>
