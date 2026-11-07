<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import MemberLayout from '@/Layouts/MemberLayout.vue';
import AppDataTable from '@/Components/Common/AppDataTable.vue';
import AppButton from '@/Components/Common/AppButton.vue';
import DetailModal from './DetailModal.vue';
import {
    Receipt,
    CheckCircle2,
    Clock,
    XCircle,
    AlertCircle,
    Eye,
} from 'lucide-vue-next';

const props = defineProps({
    tagihans: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    settings: { type: Object, default: () => ({}) },
    total_belum_dibayar: { type: Number, default: 0 },
    total_lunas: { type: Number, default: 0 },
});

const selectedTagihanId = ref(null);
const isDetailOpen = ref(false);

const openDetail = (id) => {
    selectedTagihanId.value = id;
    isDetailOpen.value = true;
};

const closeDetail = () => {
    isDetailOpen.value = false;
    selectedTagihanId.value = null;
};

const columns = [
    { key: 'nomor', label: 'No. Tagihan' },
    { key: 'periode', label: 'Periode Profit' },
    { key: 'akun', label: 'Akun Trading' },
    { key: 'profit_bersih', label: 'Profit Bersih (USD)', align: 'right' },
    { key: 'nominal_fee', label: 'Master Fee (30%)', align: 'right' },
    { key: 'jatuh_tempo', label: 'Jatuh Tempo' },
    { key: 'status', label: 'Status Pembayaran', align: 'center' },
    { key: 'actions', label: 'Aksi', align: 'center' },
];

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
</script>

<template>
    <MemberLayout
        title="Tagihan Master Fee"
        subtitle="Kelola dan lakukan pembayaran tagihan bagi hasil (Master Fee 30%) kinerja copytrade Anda."
        :breadcrumbs="[{ label: 'Menu Utama' }, { label: 'Master Fee' }]"
    >
        <Head title="Tagihan Master Fee" />

        <div class="space-y-6">
            <!-- Tabel Data Tagihan Master Fee -->
            <div class="rounded-2xl border border-border bg-card p-4 sm:p-5 shadow-xs">
                <AppDataTable
                    :data="tagihans"
                    :columns="columns"
                    :filters="filters"
                    fetch-url="/member/master-fee"
                    search-placeholder="Cari nomor tagihan atau nomor akun..."
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
                                    @change="(e) => applyFilters({ periode_dari: e.target.value > maxMonth ? maxMonth : (e.target.value || undefined), page: 1 })"
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
                                    @change="(e) => applyFilters({ periode_sampai: e.target.value > maxMonth ? maxMonth : (e.target.value || undefined), page: 1 })"
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
                                    @change="(e) => applyFilters({ status: e.target.value || undefined, page: 1 })"
                                    class="h-9 rounded-lg border border-input bg-background px-3 py-1 text-xs text-foreground focus:ring-2 focus:ring-primary focus:outline-none"
                                >
                                    <option value="">Semua Status</option>
                                    <option value="belum_dibayar">Belum Dibayar</option>
                                    <option value="menunggu_verifikasi">Menunggu Verifikasi</option>
                                    <option value="lunas">Lunas</option>
                                    <option value="ditolak">Ditolak</option>
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
                        <span class="text-xs font-medium text-foreground">
                            {{ formatPeriode(item.periode) }}
                        </span>
                    </template>

                    <!-- Slot Kolom Akun -->
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

                    <!-- Slot Kolom Profit Bersih -->
                    <template #column-profit_bersih="{ item }">
                        <span class="font-mono font-bold text-xs text-emerald-600 dark:text-emerald-400">
                            +{{ formatUSD(item.profit_bersih) }}
                        </span>
                    </template>

                    <!-- Slot Kolom Nominal Fee -->
                    <template #column-nominal_fee="{ item }">
                        <span class="font-mono font-bold text-xs text-foreground">
                            {{ formatUSD(item.nominal_fee) }}
                        </span>
                    </template>

                    <!-- Slot Kolom Jatuh Tempo -->
                    <template #column-jatuh_tempo="{ item }">
                        <span class="text-xs text-muted-foreground">
                            {{ formatTanggal(item.jatuh_tempo) }}
                        </span>
                    </template>

                    <!-- Slot Kolom Status -->
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

                    <!-- Slot Kolom Aksi -->
                    <template #column-actions="{ item }">
                        <AppButton
                            type="custom"
                            size="xs"
                            :variant="['belum_dibayar', 'ditolak'].includes(item.status) ? 'default' : 'outline'"
                            :label="['belum_dibayar', 'ditolak'].includes(item.status) ? 'Bayar Sekarang' : 'Rincian'"
                            :icon="['belum_dibayar', 'ditolak'].includes(item.status) ? false : Eye"
                            @click="openDetail(item.id)"
                        />
                    </template>
                </AppDataTable>
            </div>
        </div>

        <!-- Modal Detail Tagihan & Pembayaran -->
        <DetailModal
            :show="isDetailOpen"
            :tagihan-id="selectedTagihanId"
            @close="closeDetail"
        />
    </MemberLayout>
</template>
