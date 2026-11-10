<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import MemberLayout from '@/Layouts/MemberLayout.vue';
import AppDataTable from '@/Components/Common/AppDataTable.vue';
import AppButton from '@/Components/Common/AppButton.vue';
import DetailModal from './DetailModal.vue';
import ReportFormModal from './ReportFormModal.vue';
import {
    Receipt,
    CheckCircle2,
    Check,
    Clock,
    XCircle,
    AlertCircle,
    AlertTriangle,
    Eye,
    Plus,
    DollarSign,
    Edit3,
    CreditCard,
} from 'lucide-vue-next';

const props = defineProps({
    laporans: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    settings: { type: Object, default: () => ({}) },
    available_accounts: { type: Array, default: () => [] },
    total_menunggu_pembayaran: { type: Number, default: 0 },
    total_menunggu_verifikasi: { type: Number, default: 0 },
    total_lunas: { type: Number, default: 0 },
    total_belum_dibayar: { type: Number, default: 0 },
});

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

const selectedLaporanId = ref(null);
const isDetailOpen = ref(false);
const isFormOpen = ref(false);
const editingReport = ref(null);

const openDetail = (id) => {
    selectedLaporanId.value = id;
    isDetailOpen.value = true;
};

const closeDetail = () => {
    isDetailOpen.value = false;
    selectedLaporanId.value = null;
};

const openCreateForm = () => {
    editingReport.value = null;
    isFormOpen.value = true;
};

const openEditForm = (report) => {
    editingReport.value = report;
    isFormOpen.value = true;
};

const handleEditFromDetail = (report) => {
    closeDetail();
    openEditForm(report);
};

const closeForm = () => {
    isFormOpen.value = false;
    editingReport.value = null;
};

const columns = [
    { key: 'nomor', label: 'No. Laporan' },
    { key: 'periode', label: 'Periode Profit' },
    { key: 'akun', label: 'Akun Trading' },
    { key: 'profit_usd', label: 'Profit (USD)', align: 'right' },
    { key: 'wd_usd', label: 'Penarikan (WD)', align: 'right' },
    { key: 'kurs_riil', label: 'Kurs Riil', align: 'right' },
    { key: 'fee', label: 'Master Fee', align: 'right' },
    { key: 'status', label: 'Status', align: 'center' },
    { key: 'actions', label: 'Aksi', align: 'center' },
];

const formatUSD = (val) => {
    return '$' + Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const maxMonth = computed(() => {
    const d = new Date();
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    return `${year}-${month}`;
});
</script>

<template>
    <MemberLayout
        title="Laporan Profit & Master Fee"
        subtitle="Laporkan profit bulanan dari Myfxbook & penarikan broker, serta kelola pembayaran Master Fee."
        :breadcrumbs="[{ label: 'Menu Utama' }, { label: 'Master Fee' }]"
    >
        <Head title="Laporan Profit & Master Fee" />

        <div class="space-y-6">
            <!-- Ringkasan Finansial & Aksi Cepat -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Card Menunggu Pembayaran -->
                <div class="rounded-2xl border border-blue-500/20 bg-blue-500/5 p-4 sm:p-5 flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-semibold text-blue-800 dark:text-blue-300 uppercase tracking-wider">
                            Menunggu Pembayaran
                        </span>
                        <div class="font-mono text-2xl font-black text-blue-700 dark:text-blue-400">
                            {{ formatUSD(total_menunggu_pembayaran) }}
                        </div>
                        <p class="text-[11px] text-muted-foreground">Siap ditransfer oleh Anda</p>
                    </div>
                    <div class="h-11 w-11 rounded-xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-600 dark:text-blue-400 shrink-0">
                        <CreditCard class="h-5 w-5" />
                    </div>
                </div>

                <!-- Card Menunggu Verifikasi -->
                <div class="rounded-2xl border border-amber-500/20 bg-amber-500/5 p-4 sm:p-5 flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-semibold text-amber-800 dark:text-amber-300 uppercase tracking-wider">
                            Menunggu Verifikasi
                        </span>
                        <div class="font-mono text-2xl font-black text-amber-700 dark:text-amber-400">
                            {{ formatUSD(total_menunggu_verifikasi) }}
                        </div>
                        <p class="text-[11px] text-muted-foreground">Bukti transfer ditinjau admin</p>
                    </div>
                    <div class="h-11 w-11 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-600 dark:text-amber-400 shrink-0">
                        <Clock class="h-5 w-5" />
                    </div>
                </div>

                <!-- Card Total Lunas -->
                <div class="rounded-2xl border border-emerald-500/20 bg-emerald-500/5 p-4 sm:p-5 flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-semibold text-emerald-800 dark:text-emerald-300 uppercase tracking-wider">
                            Total Fee Lunas
                        </span>
                        <div class="font-mono text-2xl font-black text-emerald-700 dark:text-emerald-400">
                            {{ formatUSD(total_lunas) }}
                        </div>
                        <p class="text-[11px] text-muted-foreground">Master fee berhasil diverifikasi</p>
                    </div>
                    <div class="h-11 w-11 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-600 dark:text-emerald-400 shrink-0">
                        <CheckCircle2 class="h-5 w-5" />
                    </div>
                </div>

                <!-- Card Tindakan Cepat (Input Laporan) -->
                <div class="rounded-2xl border border-border bg-card p-4 sm:p-5 flex flex-col justify-between">
                    <div>
                        <span class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">
                            Periode Siap Lapor
                        </span>
                        <div class="font-mono text-xl font-black text-foreground mt-1">
                            {{ available_accounts.length }} Akun
                        </div>
                        <p class="text-[11px] text-muted-foreground mt-0.5">
                            {{ available_accounts.length > 0 ? 'Tersedia periode profit siap dilaporkan' : 'Semua periode telah dilaporkan' }}
                        </p>
                    </div>

                    <div class="pt-3">
                        <AppButton
                            type="add"
                            size="sm"
                            label="Input Laporan Profit"
                            :disabled="available_accounts.length === 0"
                            @click="openCreateForm"
                            class="w-full justify-center"
                        />
                    </div>
                </div>
            </div>

            <!-- Tabel Data Laporan Profit Master Fee -->
            <div class="rounded-2xl border border-border bg-card p-4 sm:p-5 shadow-xs">
                <AppDataTable
                    :data="laporans"
                    :columns="columns"
                    :filters="filters"
                    fetch-url="/member/master-fee"
                    search-placeholder="Cari nomor laporan atau nomor akun..."
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
                                    @change="(e) => applyFilters({ periode: e.target.value > maxMonth ? maxMonth : (e.target.value || undefined), page: 1 })"
                                    class="h-9 w-36 rounded-lg border border-input bg-background px-2.5 py-1 text-xs text-foreground focus:ring-2 focus:ring-primary focus:outline-none"
                                    title="Filter Bulan Periode"
                                />
                            </div>

                            <!-- Status Tagihan -->
                            <div class="flex items-center gap-1.5 ml-0 sm:ml-2">
                                <label class="text-xs font-semibold text-muted-foreground shrink-0">
                                    Status:
                                </label>
                                <select
                                    :value="filters.status || ''"
                                    @change="(e) => applyFilters({ status: e.target.value || undefined, page: 1 })"
                                    class="h-9 rounded-lg border border-input bg-background px-3 py-1 text-xs text-foreground focus:ring-2 focus:ring-primary focus:outline-none"
                                >
                                    <option value="">Semua Status</option>
                                    <option value="belum_dibayar">Menunggu Pembayaran</option>
                                    <option value="menunggu_verifikasi">Menunggu Verifikasi</option>
                                    <option value="lunas">Lunas</option>
                                    <option value="tanpa_fee">Tanpa Fee</option>
                                    <option value="perlu_koreksi">Perlu Koreksi</option>
                                    <option value="ditolak">Bukti Ditolak</option>
                                </select>
                            </div>
                        </div>
                    </template>

                    <!-- Slot Kolom Nomor -->
                    <template #column-nomor="{ item }">
                        <span class="font-mono font-bold text-xs text-foreground">
                            {{ item.nomor }}
                        </span>
                    </template>

                    <!-- Slot Kolom Periode -->
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

                    <!-- Slot Kolom Akun -->
                    <template #column-akun="{ item }">
                        <div class="space-y-0.5">
                            <div class="font-mono font-semibold text-xs text-foreground">
                                No. {{ item.nomor_akun_trading || '-' }}
                            </div>
                            <div class="text-[11px] text-muted-foreground">
                                {{ item.broker || '-' }}
                            </div>
                        </div>
                    </template>

                    <!-- Slot Kolom Profit USD -->
                    <template #column-profit_usd="{ item }">
                        <span
                            class="font-mono font-bold text-xs"
                            :class="item.profit_usd >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'"
                        >
                            {{ item.profit_usd >= 0 ? '+' : '' }}{{ item.profit_usd_formatted }}
                        </span>
                    </template>

                    <!-- Slot Kolom Penarikan WD -->
                    <template #column-wd_usd="{ item }">
                        <div v-if="item.wd_usd !== null" class="space-y-0.5 text-right font-mono text-xs text-foreground">
                            <div>${{ Number(item.wd_usd).toFixed(2) }}</div>
                            <div v-if="item.wd_idr" class="text-[10px] text-muted-foreground">
                                Rp {{ Number(item.wd_idr).toLocaleString('id-ID') }}
                            </div>
                        </div>
                        <span v-else class="text-xs text-muted-foreground">-</span>
                    </template>

                    <!-- Slot Kolom Kurs Riil -->
                    <template #column-kurs_riil="{ item }">
                        <span v-if="item.kurs_riil" class="font-mono text-xs text-muted-foreground">
                            Rp {{ Number(item.kurs_riil).toLocaleString('id-ID') }}
                        </span>
                        <span v-else class="text-xs text-muted-foreground">-</span>
                    </template>

                    <!-- Slot Kolom Fee -->
                    <template #column-fee="{ item }">
                        <div class="space-y-0.5 text-right font-mono text-xs">
                            <div class="font-bold text-foreground">{{ item.fee_usd_formatted }}</div>
                            <div v-if="item.fee_idr > 0" class="text-[10px] text-muted-foreground">
                                {{ item.fee_idr_formatted }}
                            </div>
                        </div>
                    </template>

                    <!-- Slot Kolom Status -->
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

                    <!-- Slot Kolom Aksi -->
                    <template #column-actions="{ item }">
                        <div class="flex items-center justify-center gap-1.5">
                            <AppButton
                                v-if="item.perlu_koreksi"
                                type="custom"
                                size="xs"
                                variant="warning"
                                label="Koreksi"
                                :icon="Edit3"
                                @click="openEditForm(item)"
                            />
                            <AppButton
                                v-else-if="['belum_dibayar', 'ditolak'].includes(item.status) && item.fee_usd > 0"
                                type="custom"
                                size="xs"
                                variant="default"
                                label="Bayar Sekarang"
                                @click="openDetail(item.id)"
                            />
                            <AppButton
                                v-else
                                type="custom"
                                size="xs"
                                variant="outline"
                                label="Rincian"
                                :icon="Eye"
                                @click="openDetail(item.id)"
                            />
                        </div>
                    </template>
                </AppDataTable>
            </div>
        </div>

        <!-- Modal Detail Laporan & Form Pembayaran -->
        <DetailModal
            :show="isDetailOpen"
            :laporan-id="selectedLaporanId"
            @close="closeDetail"
            @edit="handleEditFromDetail"
        />

        <!-- Modal Input / Edit Laporan Profit -->
        <ReportFormModal
            :show="isFormOpen"
            :available-accounts="available_accounts"
            :editing-report="editingReport"
            :settings="settings"
            @close="closeForm"
        />
    </MemberLayout>
</template>
