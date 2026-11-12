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
    Calendar,
    AlertTriangle,
    Layers,
    User,
    ExternalLink,
    Check,
    CreditCard,
} from 'lucide-vue-next';

const getStatusBadgeClass = (status) => {
    switch (status) {
        case 'Lunas':
            return 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20';
        case 'Tanpa Fee':
            return 'bg-slate-500/10 text-slate-700 dark:text-slate-300 border border-slate-500/20';
        case 'Menunggu Verifikasi':
            return 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/20';
        case 'Menunggu Pembayaran':
            return 'bg-blue-500/10 text-blue-700 dark:text-blue-400 border border-blue-500/20';
        case 'Perlu Koreksi':
            return 'bg-purple-500/10 text-purple-700 dark:text-purple-400 border border-purple-500/20';
        case 'Bukti Ditolak':
            return 'bg-rose-500/10 text-rose-700 dark:text-rose-400 border border-rose-500/20';
        default:
            return 'bg-muted text-muted-foreground border border-border';
    }
};

const props = defineProps({
    laporans: { type: Object, required: true },
    missing: { type: Object, required: true },
    summary: { type: Object, required: true },
    settings: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    current_tab: { type: String, default: 'laporan' },
});

const activeTab = ref(props.current_tab || 'laporan');
const selectedReport = ref(null);
const isVerifyModalOpen = ref(false);

const openVerifyModal = (item) => {
    selectedReport.value = item;
    isVerifyModalOpen.value = true;
};

const closeVerifyModal = () => {
    isVerifyModalOpen.value = false;
    selectedReport.value = null;
};

const tabs = computed(() => [
    {
        key: 'laporan',
        label: 'Daftar Laporan Profit & Fee',
        icon: Receipt,
        count: props.summary?.menunggu_verifikasi_count > 0 ? props.summary.menunggu_verifikasi_count : undefined,
        badgeVariant: 'warning',
    },
    {
        key: 'belum_lapor',
        label: 'Akun Belum Lapor Profit',
        icon: Layers,
        count: props.summary?.belum_rekap_count > 0 ? props.summary.belum_rekap_count : undefined,
    },
]);

const formatUSD = (val) => {
    return '$' + Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const formatRupiah = (val) => {
    if (val === null || val === undefined) return 'Rp 0';
    return 'Rp ' + Number(val).toLocaleString('id-ID');
};

const maxMonth = computed(() => {
    const d = new Date();
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    return `${year}-${month}`;
});

const laporanColumns = [
    { key: 'nomor', label: 'No. Laporan' },
    { key: 'user', label: 'Investor' },
    { key: 'akun', label: 'Akun Trading' },
    { key: 'periode', label: 'Periode Profit' },
    { key: 'profit_usd', label: 'Profit USD', align: 'right' },
    { key: 'wd', label: 'Penarikan (WD)', align: 'right' },
    { key: 'fee', label: 'Master Fee', align: 'right' },
    { key: 'status', label: 'Status', align: 'center' },
    { key: 'actions', label: 'Aksi', align: 'center' },
];

const missingColumns = [
    { key: 'nomor_akun_trading', label: 'Nomor Akun' },
    { key: 'investor', label: 'Investor' },
    { key: 'broker', label: 'Broker' },
    { key: 'modal_riil', label: 'Modal Awal', align: 'right' },
    { key: 'periode', label: 'Periode Terbuka' },
    { key: 'myfxbook', label: 'Myfxbook', align: 'center' },
];
</script>

<template>
    <AdminLayout
        title="Kelola Master Fee & Laporan Profit"
        :subtitle="`Monitoring laporan bulanan Myfxbook, verifikasi transfer Master Fee (${settings.fee_persen}%), dan akun belum lapor.`"
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

        <Head title="Kelola Master Fee & Laporan Profit" />

        <div class="space-y-6">
            <!-- 1. Kartu Ringkasan Metrik Master Fee -->
            <div class="grid grid-cols-2 lg:grid-cols-6 gap-3">
                <!-- Total Laporan -->
                <div class="rounded-xl border border-border bg-card p-3.5 space-y-1">
                    <span class="text-[11px] font-semibold text-muted-foreground">Total Fee Masuk</span>
                    <div class="font-mono text-base font-black text-foreground">
                        {{ formatUSD(summary.total_tagihan_usd) }}
                    </div>
                    <span class="text-[10px] text-muted-foreground block">
                        {{ summary.total_tagihan_count }} laporan
                    </span>
                </div>

                <!-- Lunas -->
                <div class="rounded-xl border border-emerald-500/20 bg-emerald-500/5 dark:bg-emerald-500/10 p-3.5 space-y-1">
                    <span class="text-[11px] font-semibold text-emerald-700 dark:text-emerald-400">Fee Lunas</span>
                    <div class="font-mono text-base font-black text-emerald-700 dark:text-emerald-400">
                        {{ formatUSD(summary.lunas_usd) }}
                    </div>
                    <span class="text-[10px] text-muted-foreground block">
                        {{ summary.lunas_count }} laporan
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
                        {{ summary.menunggu_verifikasi_count }} perlu verifikasi
                    </span>
                </div>

                <!-- Menunggu Pembayaran -->
                <div class="rounded-xl border border-border bg-card p-3.5 space-y-1">
                    <span class="text-[11px] font-semibold text-muted-foreground">Menunggu Pembayaran</span>
                    <div class="font-mono text-base font-black text-foreground">
                        {{ formatUSD(summary.belum_dibayar_usd) }}
                    </div>
                    <span class="text-[10px] text-muted-foreground block">
                        {{ summary.belum_dibayar_count }} laporan
                    </span>
                </div>

                <!-- Perlu Koreksi -->
                <div
                    class="rounded-xl border p-3.5 space-y-1"
                    :class="
                        summary.perlu_koreksi_count > 0
                            ? 'border-amber-500/30 bg-amber-500/10 text-amber-900 dark:text-amber-200'
                            : 'border-border bg-card text-muted-foreground'
                    "
                >
                    <span class="text-[11px] font-semibold">Perlu Koreksi</span>
                    <div class="font-mono text-base font-black">
                        {{ summary.perlu_koreksi_count }} Laporan
                    </div>
                    <span class="text-[10px] block">
                        Menunggu revisi member
                    </span>
                </div>

                <!-- Belum Lapor -->
                <div class="rounded-xl border border-border bg-card p-3.5 space-y-1">
                    <span class="text-[11px] font-semibold text-muted-foreground">Belum Lapor</span>
                    <div class="font-mono text-base font-black text-foreground">
                        {{ summary.belum_rekap_count }} Akun
                    </div>
                    <span class="text-[10px] text-muted-foreground block">
                        Periode terbuka siap lapor
                    </span>
                </div>
            </div>

            <!-- 2. Tab Navigasi -->
            <AppTabs v-model="activeTab" :items="tabs" />

            <!-- 3. Konten Tab 1: Tabel Laporan Profit -->
            <div v-show="activeTab === 'laporan'" class="rounded-2xl border border-border bg-card p-4 sm:p-5 shadow-xs">
                <AppDataTable
                    :data="laporans"
                    :columns="laporanColumns"
                    :filters="filters"
                    fetch-url="/admin/master-fee"
                    exportable
                    :extra-params="{ tab: 'laporan' }"
                    param-prefix="laporan_"
                    search-placeholder="Cari nomor laporan, nama member, atau nomor akun..."
                >
                    <!-- Filter Tambahan -->
                    <template #filters="{ applyFilters }">
                        <div class="flex items-center gap-2.5 flex-wrap">
                            <!-- Periode Bulan -->
                            <div class="flex items-center gap-1.5">
                                <label class="text-xs font-semibold text-muted-foreground shrink-0">
                                    Periode:
                                </label>
                                <input
                                    type="month"
                                    :value="filters.periode || ''"
                                    :max="maxMonth"
                                    @change="(e) => applyFilters({ periode: e.target.value > maxMonth ? maxMonth : (e.target.value || undefined), laporan_page: 1 })"
                                    class="h-9 w-36 rounded-lg border border-input bg-background px-2.5 py-1 text-xs text-foreground focus:ring-2 focus:ring-primary focus:outline-none"
                                    title="Filter Bulan Periode"
                                />
                            </div>

                            <!-- Status Laporan -->
                            <div class="flex items-center gap-1.5 ml-0 sm:ml-2">
                                <label class="text-xs font-semibold text-muted-foreground shrink-0">
                                    Status:
                                </label>
                                <select
                                    :value="filters.status || ''"
                                    @change="(e) => applyFilters({ status: e.target.value || undefined, laporan_page: 1 })"
                                    class="h-9 rounded-lg border border-input bg-background px-3 py-1 text-xs text-foreground focus:ring-2 focus:ring-primary focus:outline-none"
                                >
                                    <option value="">Semua Status</option>
                                    <option value="menunggu_verifikasi">Menunggu Verifikasi</option>
                                    <option value="belum_dibayar">Menunggu Pembayaran</option>
                                    <option value="lunas">Lunas</option>
                                    <option value="tanpa_fee">Tanpa Fee</option>
                                    <option value="perlu_koreksi">Perlu Koreksi</option>
                                    <option value="ditolak">Bukti Ditolak</option>
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
                                {{ item.user_name }}
                            </div>
                            <div class="text-[11px] text-muted-foreground font-mono">
                                @{{ item.user_username }}
                            </div>
                        </div>
                    </template>

                    <!-- Slot Akun Trading -->
                    <template #column-akun="{ item }">
                        <div class="space-y-0.5">
                            <div class="font-mono font-semibold text-xs text-foreground">
                                No. {{ item.nomor_akun_trading }}
                            </div>
                            <div class="text-[11px] text-muted-foreground">
                                {{ item.broker }}
                            </div>
                        </div>
                    </template>

                    <!-- Slot Periode -->
                    <template #column-periode="{ item }">
                        <div class="space-y-0.5">
                            <span class="text-xs font-semibold text-foreground block">
                                Periode ke-{{ item.periode_ke }}
                            </span>
                            <span class="text-[11px] text-muted-foreground block">
                                {{ item.periode_label }}
                            </span>
                        </div>
                    </template>

                    <!-- Slot Profit USD -->
                    <template #column-profit_usd="{ item }">
                        <span
                            class="font-mono font-bold text-xs"
                            :class="item.profit_usd >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'"
                        >
                            {{ item.profit_usd >= 0 ? '+' : '' }}{{ item.profit_usd_formatted }}
                        </span>
                    </template>

                    <!-- Slot Penarikan (WD) -->
                    <template #column-wd="{ item }">
                        <div v-if="item.wd_usd !== null" class="space-y-0.5 text-right font-mono text-xs text-foreground">
                            <div>${{ Number(item.wd_usd).toFixed(2) }}</div>
                            <div v-if="item.wd_idr" class="text-[10px] text-muted-foreground">
                                {{ formatRupiah(item.wd_idr) }}
                            </div>
                        </div>
                        <span v-else class="text-xs text-muted-foreground">-</span>
                    </template>

                    <!-- Slot Master Fee -->
                    <template #column-fee="{ item }">
                        <div class="space-y-0.5 text-right font-mono text-xs">
                            <div class="font-bold text-foreground">{{ item.fee_usd_formatted }}</div>
                            <div v-if="item.fee_idr > 0" class="text-[10px] text-muted-foreground">
                                {{ item.fee_idr_formatted }}
                            </div>
                        </div>
                    </template>

                    <!-- Slot Status -->
                    <template #column-status="{ item }">
                        <div class="flex flex-col items-center gap-1">
                            <span
                                :class="[
                                    'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold text-nowrap',
                                    getStatusBadgeClass(item.status_tampil)
                                ]"
                            >
                                <CheckCircle2 v-if="item.status_tampil === 'Lunas'" class="h-3 w-3" />
                                <Check v-else-if="item.status_tampil === 'Tanpa Fee'" class="h-3 w-3" />
                                <Clock v-else-if="item.status_tampil === 'Menunggu Verifikasi'" class="h-3 w-3" />
                                <CreditCard v-else-if="item.status_tampil === 'Menunggu Pembayaran'" class="h-3 w-3" />
                                <AlertTriangle v-else-if="item.status_tampil === 'Perlu Koreksi'" class="h-3 w-3" />
                                <XCircle v-else-if="item.status_tampil === 'Bukti Ditolak'" class="h-3 w-3" />
                                <span>{{ item.status_tampil }}</span>
                            </span>

                            <span v-if="item.status_catatan" class="text-[10px] text-muted-foreground text-center">
                                {{ item.status_catatan }}
                            </span>
                        </div>
                    </template>

                    <!-- Slot Aksi -->
                    <template #column-actions="{ item }">
                        <AppButton
                            type="custom"
                            size="xs"
                            :variant="item.status === 'menunggu_verifikasi' ? 'default' : 'outline'"
                            :label="item.status === 'menunggu_verifikasi' ? 'Verifikasi' : 'Rincian'"
                            :icon="item.status === 'menunggu_verifikasi' ? CheckCircle2 : Eye"
                            @click="openVerifyModal(item)"
                        />
                    </template>
                </AppDataTable>
            </div>

            <!-- 4. Konten Tab 2: Akun Belum Lapor Profit -->
            <div v-show="activeTab === 'belum_lapor'" class="rounded-2xl border border-border bg-card p-4 sm:p-5 shadow-xs">
                <AppDataTable
                    :data="missing"
                    :columns="missingColumns"
                    :filters="filters"
                    fetch-url="/admin/master-fee"
                    exportable
                    :extra-params="{ tab: 'belum_lapor' }"
                    param-prefix="missing_"
                    search-placeholder="Cari nomor akun atau nama investor..."
                >
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

                    <template #column-periode="{ item }">
                        <div class="space-y-0.5">
                            <span class="text-xs font-semibold text-foreground block">
                                Periode ke-{{ item.next_periode_ke }}
                            </span>
                            <span class="text-[11px] text-muted-foreground block">
                                {{ item.next_periode_label }}
                            </span>
                        </div>
                    </template>

                    <template #column-myfxbook="{ item }">
                        <a
                            v-if="item.myfxbook_url"
                            :href="item.myfxbook_url"
                            target="_blank"
                            class="inline-flex items-center gap-1 text-xs text-primary hover:underline font-semibold"
                        >
                            <span>Myfxbook</span>
                            <ExternalLink class="h-3 w-3" />
                        </a>
                        <span v-else class="text-xs text-muted-foreground italic">Belum diisi</span>
                    </template>
                </AppDataTable>
            </div>
        </div>

        <!-- Modal Verifikasi & Penolakan Laporan -->
        <VerifyModal
            :show="isVerifyModalOpen"
            :laporan="selectedReport"
            @close="closeVerifyModal"
        />
    </AdminLayout>
</template>
