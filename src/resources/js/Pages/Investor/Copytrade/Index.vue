<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    PlusCircle,
    ExternalLink,
    Copy,
    CheckCircle2,
    Clock,
    XCircle,
    TrendingUp,
    ShieldCheck,
    Building2,
    Eye,
    AlertCircle,
    Server,
    Key,
    Layers,
    Download,
    FileText,
    LineChart,
} from 'lucide-vue-next';
import InvestorLayout from '@/Layouts/InvestorLayout.vue';
import AppDataTable from '@/Components/Common/AppDataTable.vue';
import AppModal from '@/Components/Common/AppModal.vue';
import { Button } from '@/Components/ui/button';
import { toast } from 'vue-sonner';
import AddCopytradeModal from './AddCopytradeModal.vue';

const props = defineProps({
    copytrades: {
        type: Object,
        required: true,
    },
    pengaturan: {
        type: Object,
        default: () => ({}),
    },
    hasExisting: {
        type: Boolean,
        default: false,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const isAddModalOpen = ref(false);
const isDetailModalOpen = ref(false);
const selectedAccount = ref(null);
const selectedTierForAdd = ref('tier_1');

const openAddModalWithTier = (tier = 'tier_1') => {
    selectedTierForAdd.value = tier;
    isAddModalOpen.value = true;
};

const columns = [
    { key: 'nomor_akun_trading', label: 'No. Akun MT4', sortable: true },
    { key: 'tier', label: 'Tier & Broker', sortable: true },
    { key: 'modal_riil', label: 'Modal Riil', sortable: true },
    { key: 'tanggal_mulai', label: 'Mulai Trading', sortable: true },
    { key: 'status', label: 'Status', sortable: true },
    { key: 'copy_started_at', label: 'Mulai Copy', sortable: true },
    { key: 'actions', label: 'Aksi', sortable: false, align: 'right' },
];

const openDetailModal = (item) => {
    selectedAccount.value = item;
    isDetailModalOpen.value = true;
};

const copyToClipboard = (text, label) => {
    if (!text) return;
    navigator.clipboard.writeText(text);
    toast.success(`${label} berhasil disalin ke clipboard.`);
};

const getStatusBadge = (status) => {
    switch (status) {
        case 'active':
            return {
                label: 'Aktif & Berjalan',
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
                label: 'Tier 3 (Private VPS)',
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
</script>

<template>
    <Head title="Manajemen Copytrade" />
    <InvestorLayout title="Manajemen Copytrade">
        <div class="space-y-6">
            <!-- Header & Action Section -->
            <div
                class="bg-card border-border flex flex-col justify-between gap-4 rounded-2xl border p-6 shadow-xs sm:flex-row sm:items-center"
            >
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <TrendingUp class="h-5 w-5 text-primary-600 dark:text-primary-400" />
                        <h1 class="text-foreground text-xl font-bold tracking-tight">
                            Manajemen Akun Copytrade
                        </h1>
                    </div>
                    <p class="text-muted-foreground text-xs leading-relaxed">
                        Kelola akun trading MT4 Anda yang tersambung dengan sistem Copytrade YZD Center. Anda dapat mendaftarkan lebih dari satu akun.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2 shrink-0">
                    <Link
                        href="/investor/copytrade/balance"
                        class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl border border-border bg-card hover:bg-muted text-foreground text-xs font-bold transition-colors shadow-xs cursor-pointer min-h-[40px]"
                    >
                        <LineChart class="h-4 w-4 text-primary" />
                        <span>Kinerja & Saldo MT4</span>
                    </Link>
                    <Button
                        type="button"
                        class="bg-primary-600 hover:bg-primary-700 text-white gap-2 cursor-pointer min-h-[40px]"
                        @click="openAddModalWithTier('tier_1')"
                    >
                        <PlusCircle class="h-4 w-4" />
                        <span>Tambah Akun Copytrade</span>
                    </Button>
                </div>
            </div>

            <!-- Master Account Information Card -->
            <div class="bg-card border-border rounded-2xl border p-5 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-border/60 pb-3">
                    <div class="flex items-center gap-2">
                        <Building2 class="h-4 w-4 text-primary-600 dark:text-primary-400" />
                        <h2 class="text-foreground text-sm font-bold">Informasi Master Broker YZD Center</h2>
                    </div>
                    <span class="text-muted-foreground text-[11px]">
                        Gunakan tautan resmi di bawah untuk registrasi akun broker
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Tautan Referral Broker -->
                    <div class="bg-muted/30 border-border rounded-xl border p-4 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-muted-foreground text-xs font-semibold">Tautan Referral Broker</span>
                            <button
                                v-if="pengaturan?.link_referral_broker"
                                type="button"
                                @click="copyToClipboard(pengaturan.link_referral_broker, 'Tautan Broker')"
                                class="text-muted-foreground hover:text-foreground inline-flex items-center gap-1 text-[11px] font-medium"
                                title="Salin tautan"
                            >
                                <Copy class="h-3 w-3" />
                                <span>Salin</span>
                            </button>
                        </div>
                        <div v-if="pengaturan?.link_referral_broker" class="flex items-center justify-between gap-2">
                            <a
                                :href="pengaturan.link_referral_broker"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-primary-600 dark:text-primary-400 hover:underline font-mono text-xs font-semibold truncate"
                            >
                                {{ pengaturan.link_referral_broker }}
                            </a>
                            <a
                                :href="pengaturan.link_referral_broker"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="border-border hover:bg-background text-foreground inline-flex items-center gap-1 rounded-lg border px-2.5 py-1 text-xs font-medium shrink-0"
                            >
                                <span>Buka</span>
                                <ExternalLink class="h-3 w-3" />
                            </a>
                        </div>
                        <p v-else class="text-muted-foreground text-xs italic">
                            Belum dikonfigurasi oleh Administrator.
                        </p>
                    </div>

                    <!-- Tautan Akun Master MQL5 -->
                    <div class="bg-muted/30 border-border rounded-xl border p-4 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-muted-foreground text-xs font-semibold">Tautan Profil Master (MQL5 / Sinyal)</span>
                            <button
                                v-if="pengaturan?.link_master_akun"
                                type="button"
                                @click="copyToClipboard(pengaturan.link_master_akun, 'Tautan Master MQL5')"
                                class="text-muted-foreground hover:text-foreground inline-flex items-center gap-1 text-[11px] font-medium"
                                title="Salin tautan"
                            >
                                <Copy class="h-3 w-3" />
                                <span>Salin</span>
                            </button>
                        </div>
                        <div v-if="pengaturan?.link_master_akun" class="flex items-center justify-between gap-2">
                            <a
                                :href="pengaturan.link_master_akun"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-primary-600 dark:text-primary-400 hover:underline font-mono text-xs font-semibold truncate"
                            >
                                {{ pengaturan.link_master_akun }}
                            </a>
                            <a
                                :href="pengaturan.link_master_akun"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="border-border hover:bg-background text-foreground inline-flex items-center gap-1 rounded-lg border px-2.5 py-1 text-xs font-medium shrink-0"
                            >
                                <span>Buka</span>
                                <ExternalLink class="h-3 w-3" />
                            </a>
                        </div>
                        <p v-else class="text-muted-foreground text-xs italic">
                            Belum dikonfigurasi oleh Administrator.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Seksi Panduan & Spesifikasi 3-Tier Investasi -->
            <div class="bg-card border-border rounded-2xl border p-5 shadow-xs space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-border/60 pb-3">
                    <div class="flex items-center gap-2">
                        <Layers class="h-4 w-4 text-primary-600 dark:text-primary-400" />
                        <h2 class="text-foreground text-sm font-bold">Panduan & Spesifikasi 3-Tier Investasi</h2>
                    </div>
                    <span class="text-muted-foreground text-[11px]">
                        Pilih tier yang sesuai dengan alokasi modal dan preferensi risiko Anda
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Tier 1 -->
                    <div class="rounded-xl border border-sky-500/30 bg-sky-500/5 p-4 flex flex-col justify-between space-y-4">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="inline-flex items-center rounded-full border border-sky-500/30 bg-sky-500/10 px-2 py-0.5 text-[10px] font-bold text-sky-800 dark:text-sky-300 uppercase">
                                    Tier 1 &bull; Cent
                                </span>
                                <span class="font-mono text-xs font-bold text-foreground">Min. ${{ Number(pengaturan?.tier_1_min || 100).toLocaleString() }}</span>
                            </div>
                            <div>
                                <h3 class="text-foreground text-xs font-bold">Akun Cent (Broker HFM)</h3>
                                <p class="text-muted-foreground text-[11px] mt-1 whitespace-pre-line leading-relaxed">
                                    {{ pengaturan?.tier_1_desc || 'Akun Cent untuk copytrade pemula dengan risiko terukur dan lot cent.' }}
                                </p>
                            </div>
                        </div>

                        <div class="space-y-2 pt-2 border-t border-sky-500/20">
                            <a
                                v-if="pengaturan?.tier_1_pdf_url"
                                :href="pengaturan.tier_1_pdf_url"
                                target="_blank"
                                class="w-full justify-center inline-flex items-center gap-1.5 rounded-lg border border-sky-500/30 bg-card hover:bg-sky-500/10 px-3 py-1.5 text-[11px] font-semibold text-sky-800 dark:text-sky-300 transition-colors"
                            >
                                <Download class="h-3.5 w-3.5" />
                                <span>Unduh Brosur PDF Tier 1</span>
                            </a>
                            <Button
                                type="button"
                                size="sm"
                                class="w-full bg-sky-600 hover:bg-sky-700 text-white text-xs"
                                @click="openAddModalWithTier('tier_1')"
                            >
                                <span>Daftar Akun Tier 1</span>
                            </Button>
                        </div>
                    </div>

                    <!-- Tier 2 -->
                    <div class="rounded-xl border border-indigo-500/30 bg-indigo-500/5 p-4 flex flex-col justify-between space-y-4">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="inline-flex items-center rounded-full border border-indigo-500/30 bg-indigo-500/10 px-2 py-0.5 text-[10px] font-bold text-indigo-800 dark:text-indigo-300 uppercase">
                                    Tier 2 &bull; Standard
                                </span>
                                <span class="font-mono text-xs font-bold text-foreground">Min. ${{ Number(pengaturan?.tier_2_min || 5000).toLocaleString() }}</span>
                            </div>
                            <div>
                                <h3 class="text-foreground text-xs font-bold">Akun Standard (Broker XM / HFM)</h3>
                                <p class="text-muted-foreground text-[11px] mt-1 whitespace-pre-line leading-relaxed">
                                    {{ pengaturan?.tier_2_desc || 'Akun Standard dengan eksekusi pasar langsung dan potensi pertumbuhan modal menengah.' }}
                                </p>
                            </div>
                        </div>

                        <div class="space-y-2 pt-2 border-t border-indigo-500/20">
                            <a
                                v-if="pengaturan?.tier_2_pdf_url"
                                :href="pengaturan.tier_2_pdf_url"
                                target="_blank"
                                class="w-full justify-center inline-flex items-center gap-1.5 rounded-lg border border-indigo-500/30 bg-card hover:bg-indigo-500/10 px-3 py-1.5 text-[11px] font-semibold text-indigo-800 dark:text-indigo-300 transition-colors"
                            >
                                <Download class="h-3.5 w-3.5" />
                                <span>Unduh Brosur PDF Tier 2</span>
                            </a>
                            <Button
                                type="button"
                                size="sm"
                                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white text-xs"
                                @click="openAddModalWithTier('tier_2')"
                            >
                                <span>Daftar Akun Tier 2</span>
                            </Button>
                        </div>
                    </div>

                    <!-- Tier 3 -->
                    <div class="rounded-xl border border-amber-500/30 bg-amber-500/5 p-4 flex flex-col justify-between space-y-4">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="inline-flex items-center rounded-full border border-amber-500/30 bg-amber-500/10 px-2 py-0.5 text-[10px] font-bold text-amber-800 dark:text-amber-300 uppercase">
                                    Tier 3 &bull; Private VPS
                                </span>
                                <span class="font-mono text-xs font-bold text-foreground">Min. ${{ Number(pengaturan?.tier_3_min || 300000).toLocaleString() }}</span>
                            </div>
                            <div>
                                <h3 class="text-foreground text-xs font-bold">Dedicated VPS MT4 (Modal Besar)</h3>
                                <p class="text-muted-foreground text-[11px] mt-1 whitespace-pre-line leading-relaxed">
                                    {{ pengaturan?.tier_3_desc || 'Akun Dedicated VPS eksklusif dengan koneksi terisolasi dan pemantauan privat.' }}
                                </p>
                            </div>
                        </div>

                        <div class="space-y-2 pt-2 border-t border-amber-500/20">
                            <a
                                v-if="pengaturan?.tier_3_pdf_url"
                                :href="pengaturan.tier_3_pdf_url"
                                target="_blank"
                                class="w-full justify-center inline-flex items-center gap-1.5 rounded-lg border border-amber-500/30 bg-card hover:bg-amber-500/10 px-3 py-1.5 text-[11px] font-semibold text-amber-800 dark:text-amber-300 transition-colors"
                            >
                                <Download class="h-3.5 w-3.5" />
                                <span>Unduh Brosur PDF Tier 3</span>
                            </a>
                            <Button
                                type="button"
                                size="sm"
                                class="w-full bg-amber-600 hover:bg-amber-700 text-white text-xs"
                                @click="openAddModalWithTier('tier_3')"
                            >
                                <span>Daftar Akun Tier 3</span>
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabel Akun Copytrade via AppDataTable -->
            <AppDataTable
                :data="copytrades"
                :columns="columns"
                :filters="filters"
                fetch-url="/investor/copytrade"
                search-placeholder="Cari no. akun, bank, atau rekening..."
                empty-title="Belum Ada Akun Copytrade"
                empty-message="Anda belum mendaftarkan akun copytrade. Klik tombol 'Tambah Akun Copytrade' di atas untuk memulai."
            >
                <!-- Filters Slot -->
                <template #filters="{ applyFilters }">
                    <select
                        :value="filters.status || ''"
                        @change="(e) => applyFilters({ status: e.target.value || undefined, page: 1 })"
                        class="bg-background border-input focus:ring-primary-500 min-h-[38px] w-full rounded-lg border px-3 py-1.5 text-xs font-medium focus:ring-2 focus:outline-none sm:w-auto"
                    >
                        <option value="">Semua Status</option>
                        <option value="pending">Menunggu Verifikasi</option>
                        <option value="active">Aktif & Berjalan</option>
                        <option value="rejected">Ditolak</option>
                    </select>
                </template>

                <!-- Custom Column: No. Akun MT4 -->
                <template #column-nomor_akun_trading="{ item }">
                    <div class="space-y-0.5">
                        <span class="text-foreground font-mono font-bold">{{ item.nomor_akun_trading }}</span>
                        <div class="text-muted-foreground text-[11px]">
                            <span>{{ item.nama_bank }} &bull; {{ item.nomor_rekening }}</span>
                        </div>
                    </div>
                </template>

                <!-- Custom Column: Tier & Broker -->
                <template #column-tier="{ item }">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span
                            :class="[
                                'inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold',
                                getTierBadge(item.tier).bg,
                            ]"
                        >
                            {{ item.tier_label }}
                        </span>
                        <span class="text-muted-foreground text-[11px] font-medium">
                            ({{ item.broker }} - {{ item.tipe_akun }})
                        </span>
                    </div>
                </template>

                <!-- Custom Column: Modal Riil -->
                <template #column-modal_riil="{ item }">
                    <span class="text-foreground font-mono font-bold">{{ item.modal_riil_formatted }}</span>
                </template>

                <!-- Custom Column: Mulai Trading -->
                <template #column-tanggal_mulai="{ item }">
                    <span class="text-muted-foreground text-[11px] whitespace-nowrap">
                        {{ item.tanggal_mulai }}
                    </span>
                </template>

                <!-- Custom Column: Status -->
                <template #column-status="{ item }">
                    <div
                        :class="[
                            'inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-[10px] font-semibold',
                            getStatusBadge(item.status).bg,
                        ]"
                    >
                        <component :is="getStatusBadge(item.status).icon" class="h-3 w-3 shrink-0" />
                        <span>{{ getStatusBadge(item.status).label }}</span>
                    </div>
                </template>

                <!-- Custom Column: Mulai Copy -->
                <template #column-copy_started_at="{ item }">
                    <span v-if="item.copy_started_at" class="text-foreground text-[11px] font-medium whitespace-nowrap">
                        {{ item.copy_started_at }}
                    </span>
                    <span v-else class="text-muted-foreground text-[11px] italic">
                        Belum aktif
                    </span>
                </template>

                <!-- Custom Column: Aksi -->
                <template #column-actions="{ item }">
                    <div class="flex items-center justify-end gap-1.5">
                        <Link
                            v-if="item.status === 'active'"
                            :href="`/investor/copytrade/balance?account_id=${item.id}`"
                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-border bg-card hover:bg-muted text-foreground text-xs font-semibold transition-colors cursor-pointer"
                            title="Input Saldo & Kinerja"
                        >
                            <LineChart class="h-3.5 w-3.5 text-primary" />
                            <span class="hidden sm:inline">Saldo & Kinerja</span>
                        </Link>
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            class="flex items-center gap-1.5 text-xs cursor-pointer"
                            @click="openDetailModal(item)"
                        >
                            <Eye class="h-3.5 w-3.5" />
                            <span>Detail</span>
                        </Button>
                    </div>
                </template>
            </AppDataTable>
        </div>

        <!-- ========================================== -->
        <!-- MODAL DETAIL AKUN COPYTRADE (ANTI-HOVER)   -->
        <!-- ========================================== -->
        <AppModal
            :show="isDetailModalOpen"
            title="Rincian Akun Copytrade"
            max-width="2xl"
            @close="isDetailModalOpen = false"
        >
            <div v-if="selectedAccount" class="space-y-5 p-1">
                <!-- Status Banner -->
                <div
                    :class="[
                        'flex items-center gap-2.5 rounded-xl border p-3.5 text-xs font-semibold',
                        getStatusBadge(selectedAccount.status).bg,
                    ]"
                >
                    <component :is="getStatusBadge(selectedAccount.status).icon" class="h-4 w-4 shrink-0" />
                    <div class="flex-1">
                        <p class="font-bold">{{ getStatusBadge(selectedAccount.status).label }}</p>
                        <p v-if="selectedAccount.status === 'active' && selectedAccount.copy_started_at" class="font-normal text-[11px]">
                            Terhubung dan aktif sejak {{ selectedAccount.copy_started_at }}.
                        </p>
                        <p v-else-if="selectedAccount.status === 'pending'" class="font-normal text-[11px]">
                            Akun sedang dalam proses peninjauan verifikasi oleh tim Administrator YZD Center.
                        </p>
                        <p v-else-if="selectedAccount.status === 'rejected'" class="font-normal text-[11px]">
                            Permohonan ditolak oleh Admin. Silakan periksa instruksi di bawah.
                        </p>
                    </div>
                </div>

                <!-- Catatan Penolakan Admin jika ada -->
                <div
                    v-if="selectedAccount.status === 'rejected' && selectedAccount.catatan_admin"
                    class="rounded-xl border border-destructive/30 bg-destructive/10 p-3.5 space-y-1 text-xs text-destructive"
                >
                    <p class="font-bold flex items-center gap-1.5">
                        <AlertCircle class="h-3.5 w-3.5" />
                        Catatan Instruksi Perbaikan:
                    </p>
                    <p class="leading-relaxed whitespace-pre-line text-[11px] text-foreground">
                        {{ selectedAccount.catatan_admin }}
                    </p>
                </div>

                <!-- Grid Data Akun -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div class="bg-muted/30 border-border rounded-xl border p-3.5 space-y-2">
                        <span class="text-muted-foreground font-semibold block text-[11px]">Informasi Trading MT4</span>
                        <div class="space-y-1">
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Nomor Akun:</span>
                                <span class="text-foreground font-mono font-bold">{{ selectedAccount.nomor_akun_trading }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Tier Investasi:</span>
                                <span class="text-foreground font-semibold">{{ selectedAccount.tier_label }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Broker & Tipe:</span>
                                <span class="text-foreground font-medium">{{ selectedAccount.broker }} ({{ selectedAccount.tipe_akun }})</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Modal Riil:</span>
                                <span class="text-foreground font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                    {{ selectedAccount.modal_riil_formatted }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Mulai Trading:</span>
                                <span class="text-foreground">{{ selectedAccount.tanggal_mulai }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-muted/30 border-border rounded-xl border p-3.5 space-y-2">
                        <span class="text-muted-foreground font-semibold block text-[11px]">Rekening Penarikan Dana (Withdrawal)</span>
                        <div class="space-y-1">
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Nama Bank:</span>
                                <span class="text-foreground font-bold">{{ selectedAccount.nama_bank }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">No. Rekening:</span>
                                <span class="text-foreground font-mono font-semibold">{{ selectedAccount.nomor_rekening }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Atas Nama:</span>
                                <span class="text-foreground font-medium">{{ selectedAccount.atas_nama_rekening }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Surat Perjanjian:</span>
                                <span class="text-foreground font-semibold">
                                    {{ selectedAccount.has_surat_perjanjian ? 'Terlampir' : 'Tersambung di akun awal' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Seksi Khusus Tier 3 jika ada -->
                <div v-if="selectedAccount.tier === 'tier_3'" class="bg-amber-500/10 border-amber-500/30 rounded-xl border p-3.5 space-y-2 text-xs">
                    <span class="text-amber-800 dark:text-amber-300 font-bold block text-[11px] flex items-center gap-1.5">
                        <Server class="h-3.5 w-3.5" />
                        Konfigurasi Private VPS (Tier 3)
                    </span>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-[11px]">
                        <div>
                            <span class="text-muted-foreground">Server Broker:</span>
                            <span class="text-foreground font-mono font-bold block">{{ selectedAccount.server_broker || '-' }}</span>
                        </div>
                        <div>
                            <span class="text-muted-foreground">URL MQL5:</span>
                            <span class="text-foreground block truncate">{{ selectedAccount.url_mql5 || '-' }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <Button type="button" variant="outline" size="sm" @click="isDetailModalOpen = false">
                        Tutup
                    </Button>
                </div>
            </div>
        </AppModal>

        <!-- ========================================== -->
        <!-- MODAL TAMBAH AKUN COPYTRADE                -->
        <!-- ========================================== -->
        <AddCopytradeModal
            :show="isAddModalOpen"
            :pengaturan="pengaturan"
            :has-existing="hasExisting"
            :default-tier="selectedTierForAdd"
            @close="isAddModalOpen = false"
        />
    </InvestorLayout>
</template>
