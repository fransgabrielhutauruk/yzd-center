<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AppDataTable from '@/Components/Common/AppDataTable.vue';
import AppModal from '@/Components/Common/AppModal.vue';
import AppTableAction from '@/Components/Common/AppTableAction.vue';
import AppTabs from '@/Components/Common/AppTabs.vue';
import { Button } from '@/Components/ui/button';
import {
    Users,
    UserCheck,
    Clock,
    CheckCircle2,
    XCircle,
    MessageSquare,
    FileText,
    Loader2,
    ExternalLink,
    Eye,
    Network,
} from 'lucide-vue-next';

const props = defineProps({
    affiliates: {
        type: Object,
        required: true,
    },
    claims: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    claimFilters: {
        type: Object,
        default: () => ({}),
    },
    pendingClaimsCount: {
        type: Number,
        default: 0,
    },
    activeTab: {
        type: String,
        default: 'affiliates',
    },
    stats: {
        type: Object,
        default: () => ({
            total_affiliators: 0,
            total_downlines: 0,
            active_downlines: 0,
            pending_claims: 0,
        }),
    },
});

const currentTab = ref(
    props.activeTab === 'claims' || props.claimFilters?.claim_search || props.claimFilters?.claim_status
        ? 'claims'
        : 'affiliates'
);

watch(
    () => props.activeTab,
    (newTab) => {
        if (newTab === 'claims' || newTab === 'affiliates') {
            currentTab.value = newTab;
        }
    }
);

const tabs = computed(() => [
    {
        key: 'affiliates',
        label: 'Daftar Mitra & Jaringan Downline',
        icon: Network,
        count: props.stats.total_affiliators || props.affiliates.total || 0,
    },
    {
        key: 'claims',
        label: 'Verifikasi Klaim Susulan Referral',
        icon: UserCheck,
        badge: props.pendingClaimsCount > 0 ? `${props.pendingClaimsCount} Menunggu` : undefined,
        badgeVariant: 'warning',
    },
]);

const formatUSD = (val) => {
    if (val === null || val === undefined || isNaN(val)) return '$0.00';
    return '$' + Number(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

// ---------------------------------------------------------------------------
// TAB 1: Daftar Mitra & Modal Rincian Downline
// ---------------------------------------------------------------------------
const affiliateColumns = [
    { key: 'joined_at', label: 'Bergabung Sejak', sortable: false },
    { key: 'name', label: 'Mitra Affiliator', sortable: false },
    { key: 'phone', label: 'Nomor HP', sortable: false },
    { key: 'referral_code', label: 'Kode Referral', sortable: false },
    { key: 'total_downlines', label: 'Total Downline', sortable: false, align: 'center' },
    { key: 'actions', label: 'Aksi', sortable: false },
];

const selectedAffiliator = ref(null);
const isDownlinesModalOpen = ref(false);

const openDownlinesModal = (affiliator) => {
    selectedAffiliator.value = affiliator;
    isDownlinesModalOpen.value = true;
};

const closeDownlinesModal = () => {
    isDownlinesModalOpen.value = false;
    selectedAffiliator.value = null;
};

// ---------------------------------------------------------------------------
// TAB 2: Verifikasi Klaim Susulan Referral
// ---------------------------------------------------------------------------
const claimColumns = [
    { key: 'created_at', label: 'Diajukan Pada', sortable: true },
    { key: 'affiliator', label: 'Mitra Affiliator', sortable: false },
    { key: 'downline', label: 'Calon Downline', sortable: false },
    { key: 'status', label: 'Status', sortable: true },
    { key: 'actions', label: 'Aksi', sortable: false },
];

const selectedClaim = ref(null);
const isVerifyModalOpen = ref(false);
const isImageZoomOpen = ref(false);

const verifyForm = useForm({
    status: '',
    catatan_admin: '',
    from_affiliates: true,
});

const openVerifyModal = (claim) => {
    selectedClaim.value = claim;
    verifyForm.status = claim.status === 'pending' ? '' : claim.status;
    verifyForm.catatan_admin = claim.catatan_admin || '';
    verifyForm.from_affiliates = true;
    verifyForm.clearErrors();
    isVerifyModalOpen.value = true;
};

const closeVerifyModal = () => {
    isVerifyModalOpen.value = false;
    selectedClaim.value = null;
    verifyForm.reset();
    verifyForm.status = '';
};

const submitVerify = () => {
    if (!selectedClaim.value) return;

    verifyForm.post(route('admin.referral_claims.verify', { id: selectedClaim.value.id }), {
        preserveScroll: true,
        onSuccess: () => {
            closeVerifyModal();
        },
    });
};
</script>

<template>
    <AdminLayout
        title="Jaringan Kemitraan & Klaim Referral"
        subtitle="Kelola daftar mitra affiliator, pantau anggota jaringan downline, serta verifikasi permohonan klaim susulan referral."
        :breadcrumbs="[
            { label: 'Manajemen Afiliasi' },
            { label: 'Jaringan Kemitraan' }
        ]"
    >
        <Head title="Jaringan Kemitraan" />

        <div class="space-y-5">
            <!-- Navigasi 2 Tab -->
            <AppTabs v-model="currentTab" :items="tabs" />

            <!-- ========================================================= -->
            <!-- TAB 1: DAFTAR MITRA & JARINGAN DOWNLINE                   -->
            <!-- ========================================================= -->
            <div v-show="currentTab === 'affiliates'">
                <AppDataTable
                    :data="affiliates"
                    :columns="affiliateColumns"
                    :filters="filters"
                    :fetch-url="route('admin.affiliates.index')"
                    search-placeholder="Cari nama, username, nomor HP, atau kode referral..."
                    empty-title="Belum Ada Mitra Affiliator"
                    empty-message="Belum ada mitra affiliator aktif yang terdaftar dalam sistem."
                >
                    <template #column-joined_at="{ item }">
                        <span class="text-xs text-muted-foreground whitespace-nowrap">
                            {{ item.joined_at_label || item.joined_at }}
                        </span>
                    </template>

                    <template #column-name="{ item }">
                        <div class="space-y-0.5 min-w-0">
                            <Link
                                :href="route('admin.users.show', item.id)"
                                class="font-bold text-foreground hover:text-primary-600 dark:hover:text-primary-400 text-xs sm:text-sm truncate inline-flex items-center gap-1 transition-colors"
                            >
                                <span>{{ item.name }}</span>
                            </Link>
                            <p class="text-xs text-muted-foreground font-mono">
                                @{{ item.username }}
                            </p>
                        </div>
                    </template>

                    <template #column-phone="{ item }">
                        <span
                            v-if="item.phone && item.phone !== '-'"
                            class="text-foreground font-mono text-xs whitespace-nowrap"
                        >
                            {{ item.phone }}
                        </span>
                        <span v-else class="text-muted-foreground text-xs">-</span>
                    </template>

                    <template #column-referral_code="{ item }">
                        <span class="font-mono text-xs font-bold tracking-wider text-primary-800 dark:text-primary-300 rounded bg-primary-500/10 px-2 py-0.5 border border-primary-500/30">
                            {{ item.referral_code }}
                        </span>
                    </template>

                    <template #column-total_downlines="{ item }">
                        <span class="font-bold text-foreground text-sm font-mono">
                            {{ item.total_downlines }}
                        </span>
                    </template>

                    <template #column-actions="{ item }">
                        <AppTableAction
                            type="detail"
                            :title="`Lihat Downline (${item.total_downlines || 0})`"
                            @click="openDownlinesModal(item)"
                        />
                    </template>
                </AppDataTable>
            </div>

            <!-- ========================================================= -->
            <!-- TAB 2: VERIFIKASI KLAIM SUSULAN REFERRAL                  -->
            <!-- ========================================================= -->
            <div v-show="currentTab === 'claims'">
                <AppDataTable
                    :data="claims"
                    :columns="claimColumns"
                    :filters="claimFilters"
                    param-prefix="claim"
                    :fetch-url="route('admin.affiliates.index', { tab: 'claims' })"
                    search-placeholder="Cari nama affiliator atau calon downline..."
                    empty-title="Tidak Ada Permohonan Klaim Susulan"
                    empty-message="Belum ada permohonan klaim referral susulan yang diajukan oleh mitra affiliator."
                >
                    <template #filters="{ applyFilters }">
                        <div class="flex items-center gap-2">
                            <select
                                :value="claimFilters.claim_status || ''"
                                @change="(e) => applyFilters({ status: e.target.value || undefined, page: 1 })"
                                class="h-9 rounded-lg border border-input bg-background px-3 text-xs text-foreground transition-all focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none"
                            >
                                <option value="">Semua Status Klaim</option>
                                <option value="pending">Menunggu Verifikasi</option>
                                <option value="approved">Disetujui</option>
                                <option value="rejected">Ditolak</option>
                            </select>
                        </div>
                    </template>

                    <template #column-created_at="{ item }">
                        <span class="text-xs font-medium text-foreground whitespace-nowrap">
                            {{ item.created_at_label || item.created_at }}
                        </span>
                    </template>

                    <template #column-affiliator="{ item }">
                        <div class="space-y-0.5">
                            <p class="font-bold text-foreground text-xs">{{ item.affiliator?.name }}</p>
                            <p class="text-[11px] text-muted-foreground font-mono">
                                @{{ item.affiliator?.username }} &bull; Kode: {{ item.affiliator?.referral_code }}
                            </p>
                        </div>
                    </template>

                    <template #column-downline="{ item }">
                        <div class="space-y-0.5">
                            <p class="font-bold text-foreground text-xs">{{ item.downline?.name }}</p>
                            <p class="text-[11px] text-muted-foreground">
                                <span class="font-mono">@{{ item.downline?.username }}</span> &bull; Terdaftar: {{ item.downline?.created_at }}
                            </p>
                        </div>
                    </template>

                    <template #column-status="{ item }">
                        <div
                            v-if="item.status === 'approved'"
                            class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-semibold bg-emerald-500/10 text-emerald-800 dark:text-emerald-300 border-emerald-500/30 whitespace-nowrap"
                        >
                            <CheckCircle2 class="h-3 w-3" />
                            <span>Disetujui</span>
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

                    <template #column-actions="{ item }">
                        <AppTableAction
                            type="verify"
                            :title="item.status === 'pending' ? 'Tinjau & Putuskan' : 'Lihat Keputusan'"
                            @click="openVerifyModal(item)"
                        />
                    </template>
                </AppDataTable>
            </div>
        </div>

        <!-- ========================================================= -->
        <!-- MODAL 1: DAFTAR ANGGOTA DOWNLINE MILIK AFFILIATOR         -->
        <!-- ========================================================= -->
        <AppModal
            :show="isDownlinesModalOpen"
            :title="`Jaringan Downline — ${selectedAffiliator?.name || 'Affiliator'}`"
            max-width="3xl"
            @close="closeDownlinesModal"
        >
            <div v-if="selectedAffiliator" class="space-y-4 text-xs">
                <!-- Info Ringkas Affiliator -->
                <div class="flex items-center justify-between flex-wrap gap-2 rounded-xl border border-border bg-muted/30 p-3">
                    <div class="space-y-0.5">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-bold text-foreground text-sm">{{ selectedAffiliator.name }}</span>
                            <span class="text-muted-foreground font-mono text-xs">@{{ selectedAffiliator.username }}</span>
                            <span class="font-mono text-[11px] font-bold tracking-wider text-primary-800 dark:text-primary-300 rounded bg-primary-500/10 px-2 py-0.5 border border-primary-500/30">
                                Kode: {{ selectedAffiliator.referral_code }}
                            </span>
                        </div>
                        <p class="text-muted-foreground text-[11px]">
                            Total Jaringan: <strong>{{ selectedAffiliator.total_downlines }} Investor</strong>
                        </p>
                    </div>
                    <Link
                        :href="route('admin.users.show', selectedAffiliator.id)"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-border bg-card hover:bg-muted/50 px-3 py-1.5 text-xs font-semibold text-foreground transition-colors"
                    >
                        <span>Profil Mitra</span>
                        <ExternalLink class="h-3.5 w-3.5 text-muted-foreground" />
                    </Link>
                </div>

                <!-- Daftar Anggota Downline -->
                <div
                    v-if="selectedAffiliator.downlines_list && selectedAffiliator.downlines_list.length > 0"
                    class="space-y-2.5 max-h-96 overflow-y-auto pr-1"
                >
                    <div
                        v-for="(dl, idx) in selectedAffiliator.downlines_list"
                        :key="dl.id || idx"
                        class="rounded-xl border border-border bg-card p-3 flex items-center justify-between flex-wrap gap-3"
                    >
                        <div class="space-y-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-primary-500/10 text-[10px] font-bold text-primary-800 dark:text-primary-300">
                                    {{ idx + 1 }}
                                </span>
                                <span class="font-bold text-foreground text-xs sm:text-sm">{{ dl.name }}</span>
                                <span class="text-muted-foreground font-mono text-[11px]">@{{ dl.username }}</span>
                                <span
                                    :class="[
                                        'rounded-full border px-2 py-0.5 text-[10px] font-semibold',
                                        dl.sumber === 'link_otomatis'
                                            ? 'bg-emerald-500/10 text-emerald-800 dark:text-emerald-300 border-emerald-500/30'
                                            : 'bg-sky-500/10 text-sky-800 dark:text-sky-300 border-sky-500/30',
                                    ]"
                                >
                                    {{ dl.sumber_label }}
                                </span>
                            </div>
                            <p class="text-[11px] text-muted-foreground flex items-center gap-2 flex-wrap">
                                <span>Terhubung: <strong class="text-foreground">{{ dl.joined_at_label }}</strong></span>
                                <span>&bull;</span>
                                <span>
                                    Copytrade Aktif: <strong class="text-emerald-700 dark:text-emerald-400 font-mono">{{ dl.active_accounts_count }} Akun</strong>
                                </span>
                                <span v-if="dl.total_modal_usd > 0">
                                    &bull; Total Modal: <strong class="text-foreground font-mono">{{ formatUSD(dl.total_modal_usd) }}</strong>
                                </span>
                            </p>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <Link
                                v-if="dl.user_id"
                                :href="route('admin.users.show', dl.user_id)"
                                class="inline-flex items-center gap-1 rounded-lg border border-border bg-muted/30 hover:bg-muted px-2.5 py-1.5 text-[11px] font-semibold text-foreground transition-colors"
                            >
                                <span>Detail User</span>
                                <ExternalLink class="h-3 w-3 text-muted-foreground" />
                            </Link>
                        </div>
                    </div>
                </div>

                <div
                    v-else
                    class="rounded-xl border border-dashed border-border p-8 text-center space-y-1.5"
                >
                    <Users class="h-7 w-7 mx-auto text-muted-foreground/60" />
                    <p class="text-xs font-bold text-foreground">Belum Memiliki Anggota Downline</p>
                    <p class="text-[11px] text-muted-foreground">
                        Mitra affiliator ini belum memiliki investor yang terhubung di bawah kode referralnya.
                    </p>
                </div>

                <div class="flex justify-end pt-2 border-t border-border">
                    <Button type="button" variant="outline" size="sm" @click="closeDownlinesModal" class="text-xs">
                        Tutup
                    </Button>
                </div>
            </div>
        </AppModal>

        <!-- ========================================================= -->
        <!-- MODAL 2: TINJAU & KEPUTUSAN VERIFIKASI KLAIM SUSULAN      -->
        <!-- ========================================================= -->
        <AppModal
            :show="isVerifyModalOpen"
            title="Verifikasi Klaim Referral Susulan"
            max-width="2xl"
            @close="closeVerifyModal"
        >
            <div v-if="selectedClaim" class="space-y-4">
                <!-- Info Mitra & Downline -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="rounded-xl border border-border bg-muted/20 p-3 space-y-1 text-xs">
                        <span class="text-muted-foreground font-medium">Mitra Pengaju:</span>
                        <p class="font-bold text-foreground text-sm">{{ selectedClaim.affiliator?.name }}</p>
                        <p class="text-muted-foreground font-mono">@{{ selectedClaim.affiliator?.username }}</p>
                    </div>

                    <div class="rounded-xl border border-border bg-muted/20 p-3 space-y-1 text-xs">
                        <span class="text-muted-foreground font-medium">Calon Downline:</span>
                        <p class="font-bold text-foreground text-sm">{{ selectedClaim.downline?.name }}</p>
                        <p class="text-muted-foreground font-mono">@{{ selectedClaim.downline?.username }}</p>
                        <p class="text-muted-foreground">Terdaftar: {{ selectedClaim.downline?.created_at }}</p>
                    </div>
                </div>

                <!-- Alasan Kronologi -->
                <div class="rounded-xl border border-border p-3 space-y-1 text-xs">
                    <p class="font-semibold text-foreground flex items-center gap-1.5">
                        <MessageSquare class="h-3.5 w-3.5 text-primary-600" />
                        <span>Kronologi dari Affiliator:</span>
                    </p>
                    <p class="text-muted-foreground leading-relaxed italic whitespace-pre-wrap">
                        "{{ selectedClaim.alasan }}"
                    </p>
                </div>

                <!-- Pratinjau Bukti Pendukung -->
                <div v-if="selectedClaim.bukti_chat_url" class="space-y-1.5">
                    <p class="text-xs font-semibold text-foreground flex items-center justify-between">
                        <span>Bukti Pendukung:</span>
                        <a
                            :href="selectedClaim.bukti_chat_url"
                            target="_blank"
                            class="text-[11px] text-primary-600 dark:text-primary-400 hover:underline flex items-center gap-1"
                        >
                            <span>Buka di Tab Baru</span>
                            <ExternalLink class="h-3 w-3" />
                        </a>
                    </p>
                    <div v-if="selectedClaim.bukti_is_pdf" class="rounded-xl border border-border bg-muted/10 p-4 text-center">
                        <FileText class="h-8 w-8 mx-auto text-primary-600 dark:text-primary-400" />
                        <p class="text-xs font-medium text-foreground mt-1">Dokumen Bukti Pendukung (PDF)</p>
                        <a :href="selectedClaim.bukti_chat_url" target="_blank" class="inline-flex items-center gap-1 text-xs text-primary-600 dark:text-primary-400 hover:underline mt-2">
                            <span>Buka Dokumen PDF</span>
                            <ExternalLink class="h-3 w-3" />
                        </a>
                    </div>
                    <div v-else class="flex justify-center rounded-xl border border-border bg-muted/10 p-2 overflow-hidden">
                        <img
                            :src="selectedClaim.bukti_chat_url"
                            alt="Bukti Pendukung"
                            class="max-h-56 rounded-lg object-contain cursor-pointer hover:opacity-95 transition-opacity"
                            @click="isImageZoomOpen = true"
                        />
                    </div>
                </div>
                <div v-else class="rounded-xl border border-dashed border-border p-3 text-center text-xs text-muted-foreground">
                    Affiliator tidak melampirkan berkas bukti fisik (opsional).
                </div>

                <!-- Formulir Keputusan -->
                <form @submit.prevent="submitVerify" class="space-y-4 pt-2 border-t border-border">
                    <div v-if="selectedClaim.status === 'pending'" class="space-y-3">
                        <p class="text-xs font-bold text-foreground">
                            Tentukan Keputusan Verifikasi:
                        </p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <label
                                class="flex items-start gap-3 rounded-xl border p-3 cursor-pointer transition-all"
                                :class="[
                                    verifyForm.status === 'approved'
                                        ? 'border-emerald-500 bg-emerald-500/10 dark:bg-emerald-950/20'
                                        : 'border-border hover:bg-muted/30',
                                ]"
                            >
                                <input
                                    type="radio"
                                    name="verify_status"
                                    value="approved"
                                    v-model="verifyForm.status"
                                    class="mt-0.5 text-emerald-600 focus:ring-emerald-500"
                                />
                                <div>
                                    <p class="text-xs font-bold text-foreground">Setujui Klaim (Sah)</p>
                                    <p class="text-[11px] text-muted-foreground mt-0.5">
                                        Hubungkan investor resmi sebagai downline affiliator ini.
                                    </p>
                                </div>
                            </label>

                            <label
                                class="flex items-start gap-3 rounded-xl border p-3 cursor-pointer transition-all"
                                :class="[
                                    verifyForm.status === 'rejected'
                                        ? 'border-rose-500 bg-rose-500/10 dark:bg-rose-950/20'
                                        : 'border-border hover:bg-muted/30',
                                ]"
                            >
                                <input
                                    type="radio"
                                    name="verify_status"
                                    value="rejected"
                                    v-model="verifyForm.status"
                                    class="mt-0.5 text-rose-600 focus:ring-rose-500"
                                />
                                <div>
                                    <p class="text-xs font-bold text-foreground">Tolak Permohonan</p>
                                    <p class="text-[11px] text-muted-foreground mt-0.5">
                                        Bukti tidak cocok, meragukan, atau tanggal tidak sesuai.
                                    </p>
                                </div>
                            </label>
                        </div>

                        <div class="space-y-1.5">
                            <label for="catatan_admin" class="block text-xs font-semibold text-foreground">
                                Catatan Instruksi / Alasan Keputusan
                                <span v-if="verifyForm.status === 'rejected'" class="text-destructive">* (Wajib diisi)</span>
                                <span v-else class="text-muted-foreground font-normal">(Opsional)</span>
                            </label>
                            <textarea
                                id="catatan_admin"
                                v-model="verifyForm.catatan_admin"
                                rows="2"
                                placeholder="Tuliskan catatan alasan jika menolak klaim..."
                                class="w-full rounded-lg border border-input bg-background py-2 px-3 text-xs sm:text-sm transition-all focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none"
                                :class="{ 'border-destructive': verifyForm.errors.catatan_admin }"
                            ></textarea>
                            <p v-if="verifyForm.errors.catatan_admin" class="text-xs font-medium text-destructive">
                                {{ verifyForm.errors.catatan_admin }}
                            </p>
                        </div>
                    </div>

                    <div v-else class="rounded-xl border border-border bg-muted/20 p-3 space-y-1.5 text-xs">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-foreground">Status Keputusan:</span>
                            <span
                                class="font-bold uppercase tracking-wider"
                                :class="selectedClaim.status === 'approved' ? 'text-emerald-600' : 'text-rose-600'"
                            >
                                {{ selectedClaim.status_label }}
                            </span>
                        </div>
                        <p class="text-muted-foreground">
                            Ditinjau pada {{ selectedClaim.reviewed_at }}
                        </p>
                        <p v-if="selectedClaim.catatan_admin" class="text-muted-foreground">
                            Catatan: {{ selectedClaim.catatan_admin }}
                        </p>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-border">
                        <Button
                            type="button"
                            variant="outline"
                            @click="closeVerifyModal"
                            class="text-xs"
                        >
                            Tutup
                        </Button>

                        <Button
                            v-if="selectedClaim.status === 'pending'"
                            type="submit"
                            :disabled="verifyForm.processing || !verifyForm.status || (verifyForm.status === 'rejected' && !verifyForm.catatan_admin.trim())"
                            class="bg-primary-600 hover:bg-primary-700 text-white text-xs flex items-center gap-1.5"
                        >
                            <Loader2 v-if="verifyForm.processing" class="h-3.5 w-3.5 animate-spin" />
                            <span>Simpan Keputusan</span>
                        </Button>
                    </div>
                </form>
            </div>
        </AppModal>

        <!-- MODAL ZOOM GAMBAR BUKTI CHAT -->
        <AppModal
            :show="isImageZoomOpen"
            title="Pratinjau Bukti Tangkapan Layar Chat"
            max-width="3xl"
            @close="isImageZoomOpen = false"
        >
            <div v-if="selectedClaim" class="flex flex-col items-center justify-center p-2">
                <img
                    :src="selectedClaim.bukti_chat_url"
                    alt="Bukti Chat Zoom"
                    class="max-h-[75vh] rounded-lg object-contain border border-border"
                />
            </div>
            <div class="flex justify-end pt-3 border-t border-border">
                <Button
                    type="button"
                    variant="outline"
                    @click="isImageZoomOpen = false"
                    class="text-xs"
                >
                    Tutup
                </Button>
            </div>
        </AppModal>
    </AdminLayout>
</template>
