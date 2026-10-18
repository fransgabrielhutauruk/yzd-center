<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AppDataTable from '@/Components/Common/AppDataTable.vue';
import AppTableAction from '@/Components/Common/AppTableAction.vue';
import ProcessModal from './ProcessModal.vue';
import {
    Clock,
    CheckCircle2,
    XCircle,
    Users,
} from 'lucide-vue-next';

defineProps({
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
        default: () => ({}),
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
    return 'Rp ' + Math.round(Number(val)).toLocaleString('id-ID');
};

const tableColumns = [
    { key: 'kode_klaim', label: 'Kode / Waktu', sortable: true },
    { key: 'affiliator', label: 'Mitra Affiliator' },
    { key: 'rincian_downline', label: 'Investor Downline' },
    { key: 'total_modal', label: 'Total Modal Riil', sortable: true },
    { key: 'nominal_transfer', label: 'Komisi & Konversi Rupiah' },
    { key: 'status', label: 'Status Pencairan', sortable: true },
    { key: 'actions', label: 'Aksi' },
];
</script>

<template>
    <Head title="Pencairan Komisi Afiliasi" />

    <AdminLayout
        title="Pencairan Komisi Afiliasi"
        subtitle="Verifikasi permohonan klaim komisi periode 3 bulan dari affiliator dan selesaikan pencairan transfer profit Master Trader per akun copytrade."
        :breadcrumbs="[
            { label: 'Manajemen Afiliasi' },
            { label: 'Pencairan Komisi' }
        ]"
    >
        <div class="space-y-6">
            <!-- Tabel Data Antrean Klaim Komisi (1 Row per Tiket Klaim) -->
            <AppDataTable
                :data="claims"
                :columns="tableColumns"
                :filters="filters"
                fetch-url="/admin/commissions"
                search-placeholder="Cari kode klaim, affiliator, downline, bank..."
                empty-title="Tidak Ada Antrean Klaim Komisi"
                empty-message="Saat ini belum ada permohonan klaim komisi affiliator yang perlu diproses."
            >
                <!-- Filter Dropdown Status -->
                <template #filters="{ applyFilters }">
                    <div class="flex items-center gap-2">
                        <select
                            :value="filters.status || ''"
                            @change="(e) => applyFilters({ status: e.target.value || undefined, page: 1 })"
                            class="h-9 rounded-lg border border-input bg-background px-3 text-xs text-foreground transition-all focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none"
                        >
                            <option value="">Semua Status</option>
                            <option value="pending">Menunggu Verifikasi</option>
                            <option value="approved_transferred">Ditransfer</option>
                            <option value="rejected">Ditolak</option>
                        </select>
                    </div>
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

                <!-- Kolom Investor Downline & Tier Akun -->
                <template #column-rincian_downline="{ item }">
                    <div class="space-y-1">
                        <div class="flex items-center gap-1.5">
                            <Users class="h-3.5 w-3.5 text-primary-600 dark:text-primary-400 shrink-0" />
                            <span class="text-foreground font-semibold text-xs">
                                {{ item.summary_downlines || `${item.total_downline} Investor` }}
                            </span>
                        </div>

                    </div>
                </template>

                <!-- Kolom Total Modal Riil -->
                <template #column-total_modal="{ item }">
                    <div class="space-y-0.5">
                        <span class="text-foreground font-mono font-bold text-xs">
                            {{ formatUSD(item.total_deposit_amount || item.total_modal_riil) }}
                        </span>
                        <p v-if="item.total_modal_idr" class="text-muted-foreground font-mono text-[10px]">
                            &approx; {{ formatRupiah(item.total_modal_idr) }}
                        </p>
                        <p class="text-muted-foreground text-[10px]">{{ item.total_akun }} akun copytrade</p>
                    </div>
                </template>

                <!-- Kolom Profit & Nominal Transfer -->
                <template #column-nominal_transfer="{ item }">
                    <div v-if="item.status === 'approved_transferred'" class="space-y-0.5">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="text-emerald-700 dark:text-emerald-400 font-mono font-extrabold text-xs">
                                {{ formatUSD(item.total_affiliate_commission ?? item.total_nominal_transfer) }}
                            </span>
                            <span
                                v-if="item.total_nominal_transfer_idr !== null && item.total_nominal_transfer_idr !== undefined"
                                class="rounded bg-emerald-500/10 border border-emerald-500/30 px-1.5 py-0.5 font-mono text-[10px] font-bold text-emerald-800 dark:text-emerald-300"
                            >
                                {{ formatRupiah(item.total_nominal_transfer_idr) }}
                            </span>
                        </div>
                        <p v-if="item.kurs_konversi_idr" class="text-muted-foreground font-mono text-[10px]">
                            Kurs MT4: {{ formatRupiah(item.kurs_konversi_idr) }}/$1
                        </p>
                        <p class="text-muted-foreground text-[10px]">
                            Hak Master: {{ formatUSD(item.total_master_gross_share ?? item.total_profit_master) }}
                        </p>
                    </div>
                    <div v-else-if="item.status === 'rejected'" class="text-rose-700 dark:text-rose-400 text-xs font-medium">
                        Klaim Ditolak
                    </div>
                    <div v-else class="text-muted-foreground text-xs italic">
                        Menunggu Verifikasi
                    </div>
                </template>

                <!-- Kolom Status -->
                <template #column-status="{ item }">
                    <div
                        v-if="item.status === 'approved_transferred'"
                        class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-semibold bg-emerald-500/10 text-emerald-800 dark:text-emerald-300 border-emerald-500/30 whitespace-nowrap"
                    >
                        <CheckCircle2 class="h-3 w-3" />
                        <span>Ditransfer</span>
                    </div>
                    <div
                        v-else-if="item.status === 'rejected'"
                        class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-semibold bg-rose-500/10 text-rose-800 dark:text-rose-300 border-rose-500/30 whitespace-nowrap"
                    >
                        <XCircle class="h-3 w-3" />
                        <span>Ditolak</span>
                    </div>
                    <div
                        v-else
                        class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-semibold bg-sky-500/10 text-sky-800 dark:text-sky-300 border-sky-500/30 whitespace-nowrap"
                    >
                        <Clock class="h-3 w-3" />
                        <span>Menunggu Verifikasi</span>
                    </div>
                </template>

                <!-- Kolom Aksi -->
                <template #column-actions="{ item }">
                    <AppTableAction
                        :type="item.status === 'pending' ? 'process' : 'detail'"
                        :title="item.status === 'pending' ? 'Proses Pencairan' : 'Lihat Rincian'"
                        @click="openProcessModal(item)"
                    />
                </template>
            </AppDataTable>
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
