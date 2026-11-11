<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import MemberLayout from '@/Layouts/MemberLayout.vue';
import AppDataTable from '@/Components/Common/AppDataTable.vue';
import AppModal from '@/Components/Common/AppModal.vue';
import AppButton from '@/Components/Common/AppButton.vue';
import AppTabs from '@/Components/Common/AppTabs.vue';
import { Button } from '@/Components/ui/button';
import { toast } from 'vue-sonner';
import { compressImageFile, formatBytes } from '@/lib/imageCompression';
import {
    Users,
    Clock,
    FileText,
    PlusCircle,
    CheckCircle2,
    XCircle,
    AlertCircle,
    UploadCloud,
    Loader2,
    Eye,
    ExternalLink,
    MessageSquare,
    Check,
    AlertTriangle,
    CreditCard,
    Calculator,
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
    stats: {
        type: Object,
        required: true,
    },
    downlines: {
        type: Object,
        required: true,
    },
    downlineFilters: {
        type: Object,
        default: () => ({}),
    },
    claims: {
        type: Object,
        required: true,
    },
    claimFilters: {
        type: Object,
        default: () => ({}),
    },
    eligibleDownlines: {
        type: Array,
        default: () => [],
    },
    activeTab: {
        type: String,
        default: 'downlines',
    },
});

const activeTab = ref(props.activeTab || 'downlines'); // 'downlines' | 'claims'

const tabs = computed(() => [
    {
        key: 'downlines',
        label: 'Daftar Downline',
        icon: Users,
        count: props.stats.total_downlines,
    },
    {
        key: 'claims',
        label: 'Riwayat Klaim Susulan',
        icon: FileText,
        count: props.stats.pending_claims > 0 ? props.stats.pending_claims : undefined,
        badgeVariant: 'warning',
    },
]);

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

const handlePopState = () => {
    try {
        const params = new URLSearchParams(window.location.search);
        const tab = params.get('tab');
        if (tab === 'claims' || tab === 'downlines') {
            activeTab.value = tab;
        }
    } catch {
        // Fallback
    }
};

onMounted(() => {
    window.addEventListener('popstate', handlePopState);
    try {
        const params = new URLSearchParams(window.location.search);
        const tab = params.get('tab');
        if (tab === 'claims' || tab === 'downlines') {
            activeTab.value = tab;
        }
    } catch {
        // Fallback
    }
});

onUnmounted(() => {
    window.removeEventListener('popstate', handlePopState);
});

// Modal Ajukan Klaim Susulan
const isClaimModalOpen = ref(false);
const previewFile = ref(null);
const isCheckingDownline = ref(false);
const isCompressingProof = ref(false);
const verifiedDownline = ref(null);
const downlineCheckError = ref('');

const claimForm = useForm({
    downline_username: '',
    downline_id: '',
    bukti_chat: null,
    alasan: '',
});

const openClaimModal = () => {
    claimForm.reset();
    claimForm.clearErrors();
    previewFile.value = null;
    verifiedDownline.value = null;
    downlineCheckError.value = '';
    isCompressingProof.value = false;
    isClaimModalOpen.value = true;
};

const closeClaimModal = () => {
    isClaimModalOpen.value = false;
    claimForm.reset();
    previewFile.value = null;
    verifiedDownline.value = null;
    downlineCheckError.value = '';
    isCompressingProof.value = false;
};

const checkDownlineUsername = async () => {
    const raw = claimForm.downline_username?.trim().replace(/^@/, '');
    if (!raw) {
        downlineCheckError.value = 'Silakan ketikkan username calon downline.';
        verifiedDownline.value = null;
        claimForm.downline_id = '';
        return;
    }

    isCheckingDownline.value = true;
    downlineCheckError.value = '';
    verifiedDownline.value = null;
    claimForm.downline_id = '';

    try {
        const response = await fetch(
            route('member.affiliates.check_downline', { username: raw }),
            {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            }
        );
        const data = await response.json();

        if (response.ok && data.valid) {
            verifiedDownline.value = data.downline;
            claimForm.downline_id = data.downline.id;
            claimForm.downline_username = data.downline.username;
            claimForm.clearErrors('downline_id');
        } else {
            downlineCheckError.value =
                data.message ||
                'Username investor tidak ditemukan atau tidak memenuhi syarat klaim.';
        }
    } catch {
        downlineCheckError.value = 'Terjadi kesalahan saat memeriksa database. Silakan coba lagi.';
    } finally {
        isCheckingDownline.value = false;
    }
};

const handleUsernameInput = () => {
    if (verifiedDownline.value) {
        verifiedDownline.value = null;
        claimForm.downline_id = '';
    }
    downlineCheckError.value = '';
};

const handleFileChange = async (e) => {
    const file = e.target.files?.[0];
    if (!file) return;

    const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp', 'application/pdf'];
    if (!allowedTypes.includes(file.type)) {
        toast.error('Format berkas harus berupa gambar (JPG, PNG, WEBP) atau dokumen PDF.');
        return;
    }

    const isPdf = file.type === 'application/pdf';

    if (isPdf) {
        if (file.size > 5 * 1024 * 1024) {
            toast.error('Ukuran berkas PDF maksimal 5 MB.');
            return;
        }

        claimForm.bukti_chat = file;
        previewFile.value = {
            type: 'pdf',
            url: URL.createObjectURL(file),
            name: file.name,
            size: formatBytes(file.size),
        };
        claimForm.clearErrors('bukti_chat');
        return;
    }

    // Untuk berkas gambar: kompresi di belakang layar tanpa mengurangi ketajaman teks/kualitas visual
    isCompressingProof.value = true;
    try {
        const result = await compressImageFile(file);
        const processedFile = result?.file || file;

        if (processedFile.size > 5 * 1024 * 1024) {
            toast.error('Ukuran berkas maksimal 5 MB.');
            return;
        }

        claimForm.bukti_chat = processedFile;
        previewFile.value = {
            type: 'image',
            url: result?.previewUrl || URL.createObjectURL(processedFile),
            name: file.name,
            size: formatBytes(processedFile.size),
        };
        claimForm.clearErrors('bukti_chat');
    } catch {
        // Fallback jika kompresi canvas gagal
        if (file.size > 5 * 1024 * 1024) {
            toast.error('Ukuran berkas maksimal 5 MB.');
            return;
        }

        claimForm.bukti_chat = file;
        previewFile.value = {
            type: 'image',
            url: URL.createObjectURL(file),
            name: file.name,
            size: formatBytes(file.size),
        };
        claimForm.clearErrors('bukti_chat');
    } finally {
        isCompressingProof.value = false;
    }
};

const removeFile = () => {
    claimForm.bukti_chat = null;
    previewFile.value = null;
    isCompressingProof.value = false;
};

const submitClaim = () => {
    claimForm.post(route('member.affiliates.claims.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closeClaimModal();
            switchTab('claims');
        },
    });
};

// Modal Pratinjau Bukti Pendukung
const isProofModalOpen = ref(false);
const currentProofUrl = ref('');
const currentProofDownline = ref('');
const currentProofIsPdf = ref(false);

const openProofModal = (claim) => {
    if (!claim.bukti_chat_url) return;
    currentProofUrl.value = claim.bukti_chat_url;
    currentProofDownline.value = claim.downline?.name || 'Investor';
    currentProofIsPdf.value = !!claim.bukti_is_pdf;
    isProofModalOpen.value = true;
};

// Modal Detail Alasan / Kronologi
const isReasonModalOpen = ref(false);
const selectedReasonText = ref('');

const openReasonModal = (text) => {
    selectedReasonText.value = text;
    isReasonModalOpen.value = true;
};

// Modal Detail Catatan Admin
const isAdminNoteModalOpen = ref(false);
const selectedAdminNoteText = ref('');

const openAdminNoteModal = (note) => {
    selectedAdminNoteText.value = note;
    isAdminNoteModalOpen.value = true;
};

// Modal Detail Kinerja Akun Downline
const selectedDownlineForDetail = ref(null);
const isDownlineDetailOpen = ref(false);

const openDownlineDetail = (item) => {
    selectedDownlineForDetail.value = item;
    isDownlineDetailOpen.value = true;
};

// Kolom Tabel Downline
const downlineColumns = [
    { key: 'joined_at', label: 'Bergabung', sortable: true },
    { key: 'name', label: 'Investor', sortable: false },
    { key: 'phone', label: 'Nomor HP', sortable: false },
    { key: 'copytrade_status', label: 'Status Akun Trading', sortable: false },
    { key: 'sumber', label: 'Sumber Relasi', sortable: false },
];

// Kolom Tabel Klaim
const claimColumns = [
    { key: 'created_at', label: 'Diajukan', sortable: true },
    { key: 'downline', label: 'Calon Downline', sortable: false },
    { key: 'bukti_chat', label: 'Bukti Pendukung', sortable: false },
    { key: 'alasan', label: 'Alasan / Kronologi', sortable: false },
    { key: 'status', label: 'Status Klaim', sortable: true },
    { key: 'catatan_admin', label: 'Catatan Admin', sortable: false },
];
</script>

<template>
    <MemberLayout
        title="Jaringan Kemitraan"
        subtitle="Pantau daftar anggota binaan Anda & ajukan klaim referral susulan jika diperlukan."
        :breadcrumbs="[
            { label: 'Program Kemitraan' },
            { label: 'Jaringan Kemitraan' }
        ]"
    >
        <template #header-actions>
            <div class="flex items-center gap-2 self-start sm:self-center">
                <Link :href="route('member.affiliates.calculator')">
                    <AppButton
                        type="custom"
                        variant="outline"
                        :icon="Calculator"
                        label="Kalkulator Komisi"
                    />
                </Link>
                <AppButton
                    type="create"
                    :icon="PlusCircle"
                    label="Ajukan Klaim Susulan"
                    @click="openClaimModal"
                />
            </div>
        </template>

        <Head title="Jaringan Kemitraan" />

        <div class="space-y-6">
            <!-- Tab Navigasi -->
            <AppTabs v-model="activeTab" :items="tabs" />

            <!-- TAB 1: DAFTAR DOWNLINE -->
            <div v-show="activeTab === 'downlines'" class="space-y-4">
                <AppDataTable
                    :data="downlines"
                    :columns="downlineColumns"
                    :filters="downlineFilters"
                    :extra-params="{ tab: 'downlines' }"
                    :fetch-url="route('member.affiliates.index')"
                    search-placeholder="Cari nama, username, atau no. HP downline..."
                    empty-title="Belum Ada Downline Terdaftar"
                    empty-message="Belum ada investor yang terhubung dengan kode referral Anda."
                >
                    <!-- Filter Kustom: Sumber Relasi -->
                    <template #filters="{ applyFilters }">
                        <select
                            :value="downlineFilters.sumber || ''"
                            @change="
                                (e) =>
                                    applyFilters({ sumber: e.target.value || undefined, page: 1 })
                            "
                            class="border-input bg-background text-foreground focus:border-primary-500 focus:ring-primary-500/20 h-9 rounded-lg border px-3 text-xs transition-all focus:ring-2 focus:outline-none"
                        >
                            <option value="">Semua Sumber</option>
                            <option value="link_otomatis">Kode Registrasi</option>
                            <option value="klaim_manual">Klaim Susulan</option>
                        </select>
                    </template>

                    <!-- Custom Slot: Joined At -->
                    <template #column-joined_at="{ item }">
                        <span class="text-muted-foreground text-xs whitespace-nowrap">
                            {{ item.joined_at_label || item.joined_at }}
                        </span>
                    </template>

                    <!-- Custom Slot: Investor Name -->
                    <template #column-name="{ item }">
                        <div class="min-w-0 space-y-0.5">
                            <p class="text-foreground truncate text-sm font-bold">
                                {{ item.name }}
                            </p>
                            <p class="text-muted-foreground font-mono text-xs">
                                @{{ item.username }}
                            </p>
                        </div>
                    </template>

                    <!-- Custom Slot: Nomor HP -->
                    <template #column-phone="{ item }">
                        <span
                            v-if="item.phone && item.phone !== '-'"
                            class="text-foreground font-mono text-xs whitespace-nowrap"
                        >
                            {{ item.phone }}
                        </span>
                        <span v-else class="text-muted-foreground text-xs">-</span>
                    </template>

                    <!-- Custom Slot: Copytrade Status (Jumlah Akun Aktif & Menunggu Verifikasi) -->
                    <template #column-copytrade_status="{ item }">
                        <div
                            v-if="item.active_accounts_count > 0 || item.pending_accounts_count > 0"
                            class="flex flex-col gap-1.5"
                        >
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span
                                    v-if="item.active_accounts_count > 0"
                                    class="inline-flex items-center gap-1 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-2 py-0.5 text-[10px] font-semibold whitespace-nowrap text-emerald-800 dark:text-emerald-300"
                                >
                                    <CheckCircle2
                                        class="h-3 w-3 text-emerald-600 dark:text-emerald-400"
                                    />
                                    <span>{{ item.active_accounts_count }} Aktif</span>
                                </span>
                                <span
                                    v-if="item.pending_accounts_count > 0"
                                    class="inline-flex items-center gap-1 rounded-full border border-sky-500/30 bg-sky-500/10 px-2 py-0.5 text-[10px] font-semibold whitespace-nowrap text-sky-800 dark:text-sky-300"
                                >
                                    <Clock class="h-3 w-3" />
                                    <span>{{ item.pending_accounts_count }} Menunggu Verifikasi</span>
                                </span>
                            </div>

                            <button
                                v-if="item.accounts && item.accounts.length > 0"
                                type="button"
                                @click="openDownlineDetail(item)"
                                class="text-[11px] font-semibold text-primary hover:underline inline-flex items-center gap-1 self-start"
                            >
                                <Eye class="h-3 w-3" />
                                <span>Kinerja Akun ({{ item.accounts.length }})</span>
                            </button>
                        </div>
                        <span v-else class="text-muted-foreground text-xs">Belum Ada Akun</span>
                    </template>

                    <!-- Custom Slot: Sumber Relasi -->
                    <template #column-sumber="{ item }">
                        <span
                            class="inline-flex items-center rounded-md border px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap"
                            :class="[
                                item.sumber === 'link_otomatis'
                                    ? 'border-sky-500/30 bg-sky-500/10 text-sky-800 dark:text-sky-300'
                                    : 'border-purple-500/30 bg-purple-500/10 text-purple-800 dark:text-purple-300',
                            ]"
                        >
                            {{ item.sumber_label }}
                        </span>
                    </template>
                </AppDataTable>
            </div>

            <!-- TAB 2: RIWAYAT KLAIM SUSULAN -->
            <div v-show="activeTab === 'claims'" class="space-y-4">
                <AppDataTable
                    param-prefix="claim_"
                    :data="claims"
                    :columns="claimColumns"
                    :filters="claimFilters"
                    :extra-params="{ tab: 'claims' }"
                    :fetch-url="route('member.affiliates.index')"
                    search-placeholder="Cari calon downline..."
                    empty-title="Belum Ada Riwayat Klaim"
                    empty-message="Anda belum pernah mengajukan klaim susulan downline."
                >
                    <!-- Filter Kustom: Status Klaim -->
                    <template #filters="{ applyFilters }">
                        <select
                            :value="claimFilters.claim_status || claimFilters.status || ''"
                            @change="
                                (e) =>
                                    applyFilters({
                                        claim_status: e.target.value || undefined,
                                        claim_page: 1,
                                    })
                            "
                            class="border-input bg-background text-foreground focus:border-primary-500 focus:ring-primary-500/20 h-9 rounded-lg border px-3 text-xs transition-all focus:ring-2 focus:outline-none"
                        >
                            <option value="">Semua Status Klaim</option>
                            <option value="pending">Menunggu Verifikasi</option>
                            <option value="approved">Disetujui</option>
                            <option value="rejected">Ditolak</option>
                        </select>
                    </template>

                    <!-- Custom Slot: Created At -->
                    <template #column-created_at="{ item }">
                        <span class="text-muted-foreground text-xs whitespace-nowrap">
                            {{ item.created_at_label || item.created_at }}
                        </span>
                    </template>

                    <!-- Custom Slot: Calon Downline -->
                    <template #column-downline="{ item }">
                        <div class="min-w-0 space-y-0.5">
                            <p class="text-foreground truncate text-sm font-bold">
                                {{ item.downline?.name }}
                            </p>
                            <p class="text-muted-foreground font-mono text-xs">
                                @{{ item.downline?.username }}
                            </p>
                        </div>
                    </template>

                    <!-- Custom Slot: Bukti Pendukung -->
                    <template #column-bukti_chat="{ item }">
                        <button
                            v-if="item.bukti_chat_url"
                            type="button"
                            @click="openProofModal(item)"
                            class="border-border text-foreground hover:bg-muted inline-flex cursor-pointer items-center gap-1.5 rounded-lg border px-2.5 py-1 text-xs font-semibold transition-colors"
                            title="Lihat berkas bukti"
                        >
                            <Eye class="text-primary-600 dark:text-primary-400 h-3.5 w-3.5" />
                            <span>Lihat</span>
                        </button>
                        <span v-else class="text-muted-foreground text-xs italic">-</span>
                    </template>

                    <!-- Custom Slot: Alasan -->
                    <template #column-alasan="{ item }">
                        <div class="max-w-xs">
                            <p
                                class="text-muted-foreground text-xs leading-relaxed"
                                :class="{
                                    'line-clamp-2':
                                        item.alasan &&
                                        (item.alasan.length > 60 || item.alasan.includes('\n')),
                                }"
                                :title="item.alasan"
                            >
                                {{ item.alasan }}
                            </p>
                            <button
                                v-if="
                                    item.alasan &&
                                    (item.alasan.length > 60 || item.alasan.includes('\n'))
                                "
                                type="button"
                                @click="openReasonModal(item.alasan)"
                                class="text-primary-600 dark:text-primary-400 mt-0.5 inline-flex cursor-pointer items-center gap-0.5 text-start text-[11px] font-semibold hover:underline"
                            >
                                <span>Baca Selengkapnya</span>
                            </button>
                        </div>
                    </template>

                    <!-- Custom Slot: Status -->
                    <template #column-status="{ item }">
                        <span
                            class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-semibold whitespace-nowrap"
                            :class="[
                                item.status === 'approved'
                                    ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-800 dark:text-emerald-300'
                                    : item.status === 'rejected'
                                      ? 'border-rose-500/30 bg-rose-500/10 text-rose-800 dark:text-rose-300'
                                      : 'border-sky-500/30 bg-sky-500/10 text-sky-800 dark:text-sky-300',
                            ]"
                        >
                            <CheckCircle2
                                v-if="item.status === 'approved'"
                                class="h-3 w-3 text-emerald-600 dark:text-emerald-400"
                            />
                            <XCircle
                                v-else-if="item.status === 'rejected'"
                                class="h-3 w-3 text-rose-600 dark:text-rose-400"
                            />
                            <Clock v-else class="h-3 w-3" />
                            <span>{{ item.status_label }}</span>
                        </span>
                    </template>

                    <!-- Custom Slot: Catatan Admin -->
                    <template #column-catatan_admin="{ item }">
                        <div v-if="item.catatan_admin" class="max-w-xs">
                            <p
                                class="text-muted-foreground text-xs leading-relaxed font-medium"
                                :class="{
                                    'line-clamp-2':
                                        item.catatan_admin.length > 60 ||
                                        item.catatan_admin.includes('\n'),
                                }"
                                :title="item.catatan_admin"
                            >
                                {{ item.catatan_admin }}
                            </p>
                            <button
                                v-if="
                                    item.catatan_admin.length > 60 ||
                                    item.catatan_admin.includes('\n')
                                "
                                type="button"
                                @click="openAdminNoteModal(item.catatan_admin)"
                                class="text-primary-600 dark:text-primary-400 mt-0.5 inline-flex cursor-pointer items-center gap-0.5 text-start text-[11px] font-semibold hover:underline"
                            >
                                <span>Baca Selengkapnya</span>
                            </button>
                        </div>
                        <span v-else class="text-muted-foreground text-xs">-</span>
                    </template>
                </AppDataTable>
            </div>
        </div>

        <!-- MODAL PENGAJUAN KLAIM SUSULAN -->
        <AppModal
            :show="isClaimModalOpen"
            title="Ajukan Klaim Referral Susulan"
            max-width="lg"
            @close="closeClaimModal"
        >
            <form @submit.prevent="submitClaim" class="space-y-3">
                <!-- Input Username Calon Downline & Pengecekan Database -->
                <div class="space-y-1.5">
                    <label
                        for="claim_downline_username"
                        class="text-foreground block text-xs font-semibold"
                    >
                        Username Calon Investor Downline <span class="text-destructive">*</span>
                    </label>
                    <div class="flex gap-2">
                        <div class="relative flex-1">
                            <span
                                class="text-muted-foreground pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 font-mono text-xs"
                            >
                                @
                            </span>
                            <input
                                id="claim_downline_username"
                                v-model="claimForm.downline_username"
                                type="text"
                                placeholder="Ketik username member..."
                                @keydown.enter.prevent="checkDownlineUsername"
                                @input="handleUsernameInput"
                                class="bg-background border-input focus:ring-primary-500 placeholder:text-muted-foreground w-full rounded-lg border py-2 pr-3 pl-7 font-mono text-xs transition-all focus:ring-2 focus:outline-none sm:text-sm"
                                :class="{
                                    'border-destructive':
                                        claimForm.errors.downline_id || downlineCheckError,
                                }"
                            />
                        </div>
                        <Button
                            type="button"
                            variant="outline"
                            @click="checkDownlineUsername"
                            :disabled="isCheckingDownline || !claimForm.downline_username?.trim()"
                            class="h-auto min-w-[80px] px-3 py-2 text-xs"
                        >
                            <Loader2 v-if="isCheckingDownline" class="h-3.5 w-3.5 animate-spin" />
                            <span v-else>Periksa</span>
                        </Button>
                    </div>

                    <!-- Pesan Error Pengecekan -->
                    <p
                        v-if="downlineCheckError"
                        class="text-destructive mt-1 flex items-center gap-1 text-xs font-medium"
                    >
                        <XCircle class="h-3.5 w-3.5 shrink-0" />
                        <span>{{ downlineCheckError }}</span>
                    </p>
                    <p
                        v-else-if="claimForm.errors.downline_id"
                        class="text-destructive text-xs font-medium"
                    >
                        {{ claimForm.errors.downline_id }}
                    </p>

                    <!-- Informasi Investor Ditemukan -->
                    <div
                        v-if="verifiedDownline"
                        class="mt-1.5 space-y-1 rounded-lg border border-emerald-500/30 bg-emerald-500/10 p-2.5 text-xs text-emerald-800 dark:text-emerald-300"
                    >
                        <div class="flex items-center gap-1.5 font-bold">
                            <CheckCircle2
                                class="h-4 w-4 shrink-0 text-emerald-600 dark:text-emerald-400"
                            />
                            <span>Investor Ditemukan</span>
                        </div>
                        <p class="text-foreground pl-5.5 text-xs font-semibold">
                            {{ verifiedDownline.name }} (@{{ verifiedDownline.username }})
                        </p>
                        <p class="text-muted-foreground pl-5.5 text-[11px]">
                            Terdaftar: {{ verifiedDownline.created_at_label }}
                        </p>
                    </div>
                </div>

                <!-- Alasan Kronologi -->
                <div class="space-y-1.5">
                    <label for="claim_alasan" class="text-foreground block text-xs font-semibold">
                        Kronologi / Alasan Klaim <span class="text-destructive">*</span>
                    </label>
                    <textarea
                        id="claim_alasan"
                        v-model="claimForm.alasan"
                        rows="3"
                        placeholder="Jelaskan kronologi singkat (misal: Calon investor terburu-buru mendaftar dan lupa memasukkan kode referral saya)..."
                        class="border-input bg-background focus:border-primary-500 focus:ring-primary-500/20 w-full rounded-lg border px-3 py-2 text-xs transition-all focus:ring-2 focus:outline-none sm:text-sm"
                        :class="{ 'border-destructive': claimForm.errors.alasan }"
                    ></textarea>
                    <p v-if="claimForm.errors.alasan" class="text-destructive text-xs font-medium">
                        {{ claimForm.errors.alasan }}
                    </p>
                </div>

                <!-- Unggah Bukti Pendukung (Opsional) -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label class="text-foreground block text-xs font-semibold">
                            Bukti Pendukung
                            <span class="text-muted-foreground font-normal">(Opsional)</span>
                        </label>
                        <button
                            v-if="previewFile"
                            type="button"
                            @click="removeFile"
                            class="text-destructive cursor-pointer text-[11px] font-medium hover:underline"
                        >
                            Hapus Berkas
                        </button>
                    </div>

                    <div
                        class="border-border/80 bg-muted/20 hover:border-primary-500 relative flex flex-col items-center justify-center rounded-xl border-2 border-dashed p-4 text-center transition-colors"
                    >
                        <input
                            type="file"
                            accept="image/jpeg,image/png,image/webp,application/pdf"
                            @change="handleFileChange"
                            :disabled="isCompressingProof"
                            class="absolute inset-0 h-full w-full cursor-pointer opacity-0 disabled:cursor-not-allowed"
                        />

                        <!-- State Memproses Berkas -->
                        <div v-if="isCompressingProof" class="space-y-1.5 py-4">
                            <Loader2 class="text-primary-600 mx-auto h-8 w-8 animate-spin" />
                            <p class="text-foreground text-xs font-medium">Memproses berkas...</p>
                        </div>

                        <!-- Pratinjau Gambar -->
                        <div
                            v-else-if="previewFile && previewFile.type === 'image'"
                            class="space-y-2"
                        >
                            <img
                                :src="previewFile.url"
                                alt="Preview Bukti Pendukung"
                                class="border-border mx-auto max-h-48 rounded-lg border object-contain"
                            />
                            <p class="text-muted-foreground text-[11px]">
                                Klik atau seret untuk mengganti berkas
                            </p>
                        </div>

                        <!-- Pratinjau Dokumen PDF -->
                        <div
                            v-else-if="previewFile && previewFile.type === 'pdf'"
                            class="space-y-1.5 py-2"
                        >
                            <FileText
                                class="text-primary-600 dark:text-primary-400 mx-auto h-10 w-10"
                            />
                            <p class="text-foreground text-xs font-semibold">
                                {{ previewFile.name }}
                            </p>
                            <p class="text-muted-foreground text-[11px]">
                                Dokumen PDF ({{ previewFile.size }}) &bull; Klik untuk mengganti
                            </p>
                        </div>

                        <!-- State Kosong / Belum Unggah -->
                        <div v-else class="space-y-1.5">
                            <UploadCloud class="text-muted-foreground mx-auto h-8 w-8" />
                            <p class="text-foreground text-xs font-medium">
                                Klik atau seret berkas bukti ke sini
                            </p>
                            <p class="text-muted-foreground text-[11px]">
                                Format JPG, PNG, WEBP, atau PDF (Maks. 5 MB)
                            </p>
                        </div>
                    </div>

                    <p class="text-muted-foreground text-[11px] leading-relaxed">
                        Bukti pendukung seperti tangkapan layar percakapan chat WhatsApp, Telegram,
                        atau bukti rekomendasi lainnya.
                    </p>

                    <p
                        v-if="claimForm.errors.bukti_chat"
                        class="text-destructive text-xs font-medium"
                    >
                        {{ claimForm.errors.bukti_chat }}
                    </p>
                </div>

                <!-- Tombol Aksi Form -->
                <div class="border-border flex items-center justify-end gap-2 border-t pt-3">
                    <AppButton
                        type="cancel"
                        @click="closeClaimModal"
                        class="text-xs"
                    />
                    <AppButton
                        type="save"
                        submit
                        :loading="claimForm.processing || isCompressingProof"
                        :loading-text="claimForm.processing ? 'Mengirim...' : 'Memproses...'"
                        label="Kirim Permohonan Klaim"
                        :disabled="
                            claimForm.processing ||
                            !claimForm.downline_id ||
                            isCheckingDownline ||
                            isCompressingProof
                        "
                        class="text-xs"
                    />
                </div>
            </form>
        </AppModal>

        <!-- MODAL PRATINJAU BUKTI PENDUKUNG (GAMBAR & PDF) -->
        <AppModal
            :show="isProofModalOpen"
            :title="`Bukti Pendukung: ${currentProofDownline}`"
            :max-width="currentProofIsPdf ? '4xl' : '2xl'"
            @close="isProofModalOpen = false"
        >
            <div class="p-1">
                <!-- Pratinjau Dokumen PDF -->
                <div v-if="currentProofIsPdf" class="w-full">
                    <iframe
                        :src="currentProofUrl"
                        class="border-border bg-card h-[65vh] w-full rounded-lg border"
                        title="Pratinjau Dokumen PDF"
                    >
                        <p class="text-muted-foreground p-4 text-center text-xs">
                            Peramban Anda tidak mendukung pratinjau dokumen langsung di dalam modal.
                            Silakan klik tombol "Lihat di Tab Baru" di bawah.
                        </p>
                    </iframe>
                </div>

                <!-- Pratinjau Gambar -->
                <div v-else class="flex flex-col items-center justify-center p-2">
                    <img
                        :src="currentProofUrl"
                        alt="Tangkapan Layar Bukti Pendukung"
                        class="border-border max-h-[70vh] rounded-lg border object-contain"
                    />
                </div>
            </div>

            <div class="border-border flex items-center justify-between border-t pt-3">
                <a
                    :href="currentProofUrl"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="border-border text-foreground hover:bg-muted inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-semibold transition-colors"
                >
                    <span>Lihat di Tab Baru</span>
                    <ExternalLink class="text-muted-foreground h-3.5 w-3.5" />
                </a>
                <Button
                    type="button"
                    variant="outline"
                    @click="isProofModalOpen = false"
                    class="text-xs"
                >
                    Tutup
                </Button>
            </div>
        </AppModal>

        <!-- MODAL DETAIL ALASAN / KRONOLOGI KLAIM -->
        <AppModal
            :show="isReasonModalOpen"
            title="Detail Alasan & Kronologi Klaim"
            max-width="2xl"
            @close="isReasonModalOpen = false"
        >
            <div class="space-y-3 p-1">
                <!-- Isi Lengkap Alasan / Kronologi -->
                <div class="border-border bg-card space-y-1.5 rounded-xl border p-3.5">
                    <p class="text-foreground flex items-center gap-1.5 text-xs font-semibold">
                        <MessageSquare class="text-primary-600 dark:text-primary-400 h-3.5 w-3.5" />
                        <span>Alasan / Kronologi:</span>
                    </p>
                    <p
                        class="text-foreground text-xs leading-relaxed whitespace-pre-wrap sm:text-sm"
                    >
                        {{ selectedReasonText }}
                    </p>
                </div>
            </div>

            <div class="border-border flex justify-end border-t pt-3">
                <Button
                    type="button"
                    variant="outline"
                    @click="isReasonModalOpen = false"
                    class="text-xs"
                >
                    Tutup
                </Button>
            </div>
        </AppModal>

        <!-- MODAL DETAIL CATATAN ADMIN -->
        <AppModal
            :show="isAdminNoteModalOpen"
            title="Catatan dari Administrator"
            max-width="2xl"
            @close="isAdminNoteModalOpen = false"
        >
            <div class="space-y-3 p-1">
                <div
                    class="border-destructive/30 bg-destructive/5 space-y-1.5 rounded-xl border p-3.5"
                >
                    <p class="text-destructive flex items-center gap-1.5 text-xs font-semibold">
                        <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                        <span>Catatan Keputusan / Evaluasi Admin:</span>
                    </p>
                    <p
                        class="text-destructive text-xs leading-relaxed whitespace-pre-wrap sm:text-sm"
                    >
                        {{ selectedAdminNoteText }}
                    </p>
                </div>
            </div>

            <div class="border-border flex justify-end border-t pt-3">
                <Button
                    type="button"
                    variant="outline"
                    @click="isAdminNoteModalOpen = false"
                    class="text-xs"
                >
                    Tutup
                </Button>
            </div>
        </AppModal>

        <!-- MODAL RINCIAN KINERJA AKUN DOWNLINE -->
        <AppModal
            :show="isDownlineDetailOpen"
            :title="`Kinerja Akun Copytrade — ${selectedDownlineForDetail?.name || ''}`"
            max-width="2xl"
            @close="isDownlineDetailOpen = false"
        >
            <div v-if="selectedDownlineForDetail" class="space-y-4 p-1">
                <div v-if="!selectedDownlineForDetail.accounts || selectedDownlineForDetail.accounts.length === 0" class="text-center py-6 text-xs text-muted-foreground">
                    Investor ini belum memiliki akun copytrade yang aktif.
                </div>

                <div
                    v-for="acc in selectedDownlineForDetail.accounts"
                    :key="acc.id"
                    class="rounded-xl border border-border bg-card p-4 space-y-3"
                >
                    <div class="flex items-center justify-between flex-wrap gap-2">
                        <div class="space-y-0.5">
                            <h4 class="font-bold text-sm text-foreground">
                                Akun #{{ acc.nomor_akun_trading }} ({{ acc.broker }})
                            </h4>
                        </div>
                        <a
                            v-if="acc.myfxbook_url"
                            :href="acc.myfxbook_url"
                            target="_blank"
                            class="inline-flex items-center gap-1 text-xs font-semibold text-primary hover:underline"
                        >
                            <span>Buka Myfxbook</span>
                            <ExternalLink class="h-3 w-3" />
                        </a>
                    </div>

                    <!-- Tabel Periode Laporan Profit Akun -->
                    <div class="rounded-lg border border-border overflow-hidden">
                        <table class="w-full text-xs">
                            <thead class="bg-muted/50 border-b border-border text-muted-foreground font-semibold">
                                <tr>
                                    <th class="py-2 px-3 text-left">Periode</th>
                                    <th class="py-2 px-3 text-right">Profit (USD)</th>
                                    <th class="py-2 px-3 text-center">Status Tagihan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                <tr v-if="!acc.laporan || acc.laporan.length === 0">
                                    <td colspan="3" class="py-3 px-3 text-center text-muted-foreground">
                                        Belum ada laporan profit bulanan untuk akun ini.
                                    </td>
                                </tr>
                                <tr
                                    v-for="lap in acc.laporan"
                                    :key="lap.periode_ke"
                                    class="hover:bg-muted/30"
                                >
                                    <td class="py-2 px-3">
                                        <div class="font-medium text-foreground">Periode ke-{{ lap.periode_ke }}</div>
                                        <div class="text-[10px] text-muted-foreground">{{ lap.periode_label }}</div>
                                    </td>
                                    <td class="py-2 px-3 text-right font-mono font-bold" :class="lap.profit_usd >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'">
                                        {{ lap.profit_usd >= 0 ? '+' : '' }}${{ Number(lap.profit_usd).toFixed(2) }}
                                    </td>
                                    <td class="py-2 px-3 text-center">
                                        <span
                                            :class="[
                                                'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold text-nowrap',
                                                getStatusBadgeClass(lap.status_tampil)
                                            ]"
                                        >
                                            <CheckCircle2 v-if="lap.status_tampil === 'Lunas'" class="h-2.5 w-2.5" />
                                            <Check v-else-if="lap.status_tampil === 'Tanpa Fee'" class="h-2.5 w-2.5" />
                                            <Clock v-else-if="lap.status_tampil === 'Menunggu Verifikasi'" class="h-2.5 w-2.5" />
                                            <CreditCard v-else-if="lap.status_tampil === 'Menunggu Pembayaran'" class="h-2.5 w-2.5" />
                                            <AlertTriangle v-else-if="lap.status_tampil === 'Perlu Koreksi'" class="h-2.5 w-2.5" />
                                            <XCircle v-else-if="lap.status_tampil === 'Bukti Ditolak'" class="h-2.5 w-2.5" />
                                            <span>{{ lap.status_tampil }}</span>
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="border-border flex justify-end border-t pt-3 mt-3">
                <AppButton
                    type="custom"
                    variant="outline"
                    label="Tutup"
                    @click="isDownlineDetailOpen = false"
                />
            </div>
        </AppModal>
    </MemberLayout>
</template>
