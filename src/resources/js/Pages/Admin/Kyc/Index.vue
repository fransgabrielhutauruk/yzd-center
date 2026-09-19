<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AppDataTable from '@/Components/Common/AppDataTable.vue';
import AppModal from '@/Components/Common/AppModal.vue';
import { Button } from '@/Components/ui/button';
import { formatBytes } from '@/lib/imageCompression';
import { toast } from 'vue-sonner';
import {
    CheckCircle2,
    Clock,
    AlertTriangle,
    XCircle,
    Eye,
    CreditCard,
    FileText,
    Loader2,
    RotateCw,
    ZoomIn,
    ZoomOut,
    ExternalLink,
    TrendingUp,
    Copy,
    EyeOff,
    ShieldCheck,
} from 'lucide-vue-next';

const props = defineProps({
    investors: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

// Kolom Tabel AppDataTable
const columns = [
    { key: 'created_at', label: 'Terdaftar', sortable: true },
    { key: 'name', label: 'Investor', sortable: true },
    { key: 'akun_copytrade', label: 'Akun Copy Trade' },
    { key: 'dokumen_ktp', label: 'Foto KTP' },
    { key: 'dokumen_perjanjian', label: 'Surat Perjanjian' },
    { key: 'actions', label: 'Aksi' },
];

// State Modal Detail & Verifikasi
const isVerifyModalOpen = ref(false);
const selectedInvestor = ref(null);
const activeDocumentTab = ref('ktp'); // 'ktp' | 'perjanjian' | 'trading'

// Kontrol Transformasi Preview Gambar (Zoom & Rotasi)
const imageZoom = ref(1);
const imageRotation = ref(0);

const resetImageTransform = () => {
    imageZoom.value = 1;
    imageRotation.value = 0;
};

const zoomIn = () => {
    if (imageZoom.value < 3) imageZoom.value += 0.25;
};

const zoomOut = () => {
    if (imageZoom.value > 0.5) imageZoom.value -= 0.25;
};

const rotateImage = () => {
    imageRotation.value = (imageRotation.value + 90) % 360;
};

// Form Verifikasi Dokumen
const verifyForm = useForm({
    status: '',
    catatan_admin: '',
});

// Form Verifikasi Akun Copy Trade
const accountVerifyForm = useForm({
    status: '',
    catatan_admin: '',
});

const showMasterPassword = ref(false);
const showInvestorPassword = ref(false);

const copyToClipboard = (text, label) => {
    if (!text) return;
    navigator.clipboard
        .writeText(String(text))
        .then(() => {
            toast.success(`${label} berhasil disalin ke clipboard!`);
        })
        .catch(() => {
            toast.error('Gagal menyalin ke clipboard.');
        });
};

const openVerifyModal = (investor) => {
    selectedInvestor.value = investor;
    activeDocumentTab.value = 'trading';
    resetImageTransform();

    accountVerifyForm.status = '';
    accountVerifyForm.catatan_admin = '';

    verifyForm.status = '';
    verifyForm.catatan_admin = '';

    showMasterPassword.value = false;
    showInvestorPassword.value = false;
    isVerifyModalOpen.value = true;
};

const closeVerifyModal = () => {
    isVerifyModalOpen.value = false;
    selectedInvestor.value = null;
    verifyForm.reset();
    accountVerifyForm.reset();
    verifyForm.status = '';
    accountVerifyForm.status = '';
};

const initDocForm = (docType) => {
    if (!selectedInvestor.value) return;
    verifyForm.status = '';
    verifyForm.catatan_admin = '';
};

const selectTab = (tab) => {
    activeDocumentTab.value = tab;
    resetImageTransform();
    if (tab === 'trading') {
        accountVerifyForm.status = '';
        accountVerifyForm.catatan_admin = '';
    } else {
        initDocForm(tab);
    }
};

const checkAndUpdateInvestorStatus = () => {
    if (!selectedInvestor.value) return;
    const docs = selectedInvestor.value.dokumen;
    const ktpOk = docs?.ktp?.status === 'approved';
    const perjOk = docs?.perjanjian?.status === 'approved';
    const ct = selectedInvestor.value.akun_copytrade;
    const ctOk = !ct || ct.status === 'active';

    selectedInvestor.value.status = (ktpOk && perjOk && ctOk) ? 'active' : 'pending';
};

const isInvestorActive = computed(() => {
    if (!selectedInvestor.value) return false;
    const docs = selectedInvestor.value.dokumen;
    if (!docs) return selectedInvestor.value.status === 'active';

    const ktpOk = docs.ktp?.status === 'approved';
    const perjOk = docs.perjanjian?.status === 'approved';
    const ct = selectedInvestor.value.akun_copytrade;
    const ctOk = !ct || ct.status === 'active';

    return ktpOk && perjOk && ctOk;
});

const submitAccountVerification = () => {
    const akun = selectedInvestor.value?.akun_copytrade;
    if (!akun) {
        toast.error('Data akun copy trade tidak ditemukan.');
        return;
    }

    if (!accountVerifyForm.status) {
        toast.error('Silakan pilih salah satu opsi keputusan validasi akun trading.');
        return;
    }

    if (accountVerifyForm.status === 'rejected' && !accountVerifyForm.catatan_admin.trim()) {
        toast.error('Harap isi catatan admin saat menolak akun copy trade.');
        return;
    }

    accountVerifyForm.post(`/admin/kyc/accounts/${akun.id}/verify`, {
        preserveScroll: true,
        onSuccess: () => {
            if (selectedInvestor.value?.akun_copytrade) {
                selectedInvestor.value.akun_copytrade.status = accountVerifyForm.status;
                selectedInvestor.value.akun_copytrade.catatan_admin =
                    accountVerifyForm.catatan_admin;
            }
            checkAndUpdateInvestorStatus();
        },
    });
};

const submitVerification = (docType) => {
    const doc = selectedInvestor.value?.dokumen?.[docType];
    if (!doc) {
        toast.error('Dokumen tidak ditemukan.');
        return;
    }

    if (!verifyForm.status) {
        toast.error('Silakan pilih salah satu opsi keputusan verifikasi berkas.');
        return;
    }

    if (verifyForm.status === 'revision_needed' && !verifyForm.catatan_admin.trim()) {
        toast.error(
            'Harap isi catatan revisi agar investor mengetahui bagian yang perlu diperbaiki.'
        );
        return;
    }

    verifyForm.post(`/admin/kyc/documents/${doc.id}/verify`, {
        preserveScroll: true,
        onSuccess: () => {
            // Update data lokal pada modal
            if (selectedInvestor.value?.dokumen?.[docType]) {
                selectedInvestor.value.dokumen[docType].status = verifyForm.status;
                selectedInvestor.value.dokumen[docType].catatan_admin = verifyForm.catatan_admin;
            }
            checkAndUpdateInvestorStatus();
        },
    });
};

const getStatusBadge = (status, type = null) => {
    switch (status) {
        case 'active':
        case 'approved':
            return {
                label:
                    status === 'active'
                        ? type === 'copytrade'
                            ? 'Valid & Sudah Follow'
                            : 'Aktif'
                        : 'Disetujui',
                bg: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-500/30',
                icon: CheckCircle2,
            };
        case 'revision_needed':
            return {
                label: 'Perlu Revisi',
                bg: 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-500/30',
                icon: AlertTriangle,
            };
        case 'rejected':
            return {
                label: type === 'copytrade' ? 'Ditolak / Belum Follow' : 'Ditolak',
                bg: 'bg-rose-500/10 text-rose-700 dark:text-rose-400 border-rose-500/30',
                icon: XCircle,
            };
        case 'pending':
        default:
            return {
                label: 'Menunggu Validasi',
                bg: 'bg-sky-500/10 text-sky-700 dark:text-sky-400 border-sky-500/30',
                icon: Clock,
            };
    }
};

const getInitials = (name) => {
    if (!name) return 'U';
    const parts = name.trim().split(/\s+/);
    if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
};

const getTierBadge = (tier) => {
    const t = String(tier || '').toLowerCase();
    if (t === '1' || t === 'tier_1') {
        return {
            label: 'Tier 1 – Cent Account HFM',
            badge: 'bg-muted text-foreground border-border',
        };
    }
    if (t === '2' || t === 'tier_2') {
        return {
            label: 'Tier 2 – Standard Account XM',
            badge: 'bg-muted text-foreground border-border',
        };
    }
    if (t === '3' || t === 'tier_3') {
        return {
            label: 'Tier 3 – Private VPS XM',
            badge: 'bg-muted text-foreground border-border',
        };
    }
    return {
        label: `Tier ${tier}`,
        badge: 'bg-muted text-foreground border-border',
    };
};
</script>

<template>
    <AdminLayout title="Verifikasi Dokumen Legalitas (KYC)">
        <div class="space-y-6">
            <!-- Header Section -->
            <div
                class="bg-card border-border flex flex-col justify-between gap-4 rounded-2xl border p-6 shadow-xs sm:flex-row sm:items-center"
            >
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <h1 class="text-foreground text-xl font-bold tracking-tight">
                            Verifikasi Dokumen Legalitas (KYC)
                        </h1>
                    </div>
                    <p class="text-muted-foreground text-xs">
                        Pemeriksaan keaslian identitas KTP, dokumen perjanjian bermaterai, dan data
                        ahli waris investor.
                    </p>
                </div>
            </div>

            <!-- Tabel Daftar KYC via AppDataTable -->
            <AppDataTable
                :data="investors"
                :columns="columns"
                :filters="filters"
                fetch-url="/admin/kyc"
                search-placeholder="Cari investor, username..."
                empty-title="Tidak Ada Berkas KYC Ditemukan"
                empty-message="Belum ada pendaftaran akun atau tidak ada hasil yang cocok dengan filter pencarian."
            >
                <!-- Filter Slot: Status Filter Dropdown -->
                <template #filters="{ applyFilters }">
                    <select
                        :value="filters.status || ''"
                        @change="(e) => applyFilters({ status: e.target.value || undefined, page: 1 })"
                        class="bg-background border-input focus:ring-primary-500 min-h-[38px] w-full rounded-lg border px-3 py-1.5 text-xs font-medium focus:ring-2 focus:outline-none sm:w-auto"
                    >
                        <option value="">Semua Status</option>
                        <option value="pending">Menunggu Verifikasi (Pending)</option>
                        <option value="active">Aktif & Disetujui (Active)</option>
                        <option value="revision_needed">Perlu Revisi Dokumen</option>
                        <option value="rejected">Akun Trading Ditolak</option>
                    </select>
                </template>

                <!-- Custom Column: Terdaftar -->
                <template #column-created_at="{ item }">
                    <span class="text-muted-foreground text-[11px] font-medium whitespace-nowrap">
                        {{ item.created_at }}
                    </span>
                </template>

                <!-- Custom Column: Investor Name (tanpa email) -->
                <template #column-name="{ item }">
                    <div class="space-y-0.5">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span class="text-foreground font-bold">{{ item.name }}</span>
                            <span
                                v-if="item.akun_copytrade"
                                class="rounded border px-1.5 py-0.5 text-[10px] font-semibold"
                                :class="getTierBadge(item.akun_copytrade.tier).badge"
                            >
                                Tier {{ String(item.akun_copytrade.tier).replace('tier_', '') }}
                            </span>
                        </div>
                        <div class="text-muted-foreground text-[11px]">
                            <span>@{{ item.username }}</span>
                        </div>
                    </div>
                </template>

                <!-- Custom Column: Akun Copy Trade (hanya status badge) -->
                <template #column-akun_copytrade="{ item }">
                    <div v-if="item.akun_copytrade">
                        <span
                            class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-semibold"
                            :class="getStatusBadge(item.akun_copytrade.status, 'copytrade').bg"
                        >
                            <component
                                :is="getStatusBadge(item.akun_copytrade.status, 'copytrade').icon"
                                class="h-3 w-3"
                            />
                            <span>{{
                                getStatusBadge(item.akun_copytrade.status, 'copytrade').label
                            }}</span>
                        </span>
                    </div>
                    <span v-else class="text-muted-foreground text-[11px] italic">Belum Ada</span>
                </template>

                <!-- Custom Column: Dokumen KTP -->
                <template #column-dokumen_ktp="{ item }">
                    <div v-if="item.dokumen?.ktp" class="space-y-1">
                        <div
                            :class="[
                                'inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-semibold',
                                getStatusBadge(item.dokumen.ktp.status).bg,
                            ]"
                        >
                            <component
                                :is="getStatusBadge(item.dokumen.ktp.status).icon"
                                class="h-3 w-3"
                            />
                            <span>{{ getStatusBadge(item.dokumen.ktp.status).label }}</span>
                        </div>
                    </div>
                    <span v-else class="text-muted-foreground text-[11px] italic">Belum Ada</span>
                </template>

                <!-- Custom Column: Dokumen Perjanjian -->
                <template #column-dokumen_perjanjian="{ item }">
                    <div v-if="item.dokumen?.perjanjian" class="space-y-1">
                        <div
                            :class="[
                                'inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-semibold',
                                getStatusBadge(item.dokumen.perjanjian.status).bg,
                            ]"
                        >
                            <component
                                :is="getStatusBadge(item.dokumen.perjanjian.status).icon"
                                class="h-3 w-3"
                            />
                            <span>{{ getStatusBadge(item.dokumen.perjanjian.status).label }}</span>
                        </div>
                    </div>
                    <span v-else class="text-muted-foreground text-[11px] italic">Belum Ada</span>
                </template>

                <!-- Custom Column: Actions -->
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
        <!-- MODAL VERIFIKASI BERKAS KYC                -->
        <!-- ========================================== -->
        <AppModal
            :show="isVerifyModalOpen"
            title="Peninjauan & Verifikasi"
            max-width="4xl"
            @close="closeVerifyModal"
        >
            <div v-if="selectedInvestor" class="max-h-[75vh] space-y-5 overflow-y-auto ps-0.5 pe-1">
                <!-- Header & Profil Investor -->
                <div class="rounded-xl border border-border bg-card p-4 sm:p-5">
                    <!-- Baris Atas: Avatar Inisial, Identitas Ringkas, dan Status Akun Utama -->
                    <div
                        class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-border/60"
                    >
                        <div class="flex items-center gap-3.5 min-w-0">
                            <div
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-muted font-bold text-sm text-foreground ring-1 ring-border"
                            >
                                {{ getInitials(selectedInvestor.name) }}
                            </div>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-foreground text-sm font-bold">
                                        {{ selectedInvestor.name }}
                                    </h3>
                                    <span class="text-muted-foreground text-xs font-normal">
                                        @{{ selectedInvestor.username }}
                                    </span>
                                </div>
                                <p class="text-muted-foreground text-xs">
                                    {{ selectedInvestor.email }} &bull; {{ selectedInvestor.phone }}
                                </p>
                            </div>
                        </div>

                        <!-- Status Verifikasi Ringkas Tunggal (Reaktif Dinamis) -->
                        <div class="flex items-center shrink-0">
                            <span
                                :class="[
                                    'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold transition-colors',
                                    isInvestorActive
                                        ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400'
                                        : 'border-amber-500/30 bg-amber-500/10 text-amber-700 dark:text-amber-400',
                                ]"
                            >
                                <component
                                    :is="isInvestorActive ? CheckCircle2 : Clock"
                                    class="h-3.5 w-3.5 shrink-0"
                                />
                                <span>{{
                                    isInvestorActive
                                        ? 'Akun Aktif & Disetujui'
                                        : 'Menunggu Verifikasi'
                                }}</span>
                            </span>
                        </div>
                    </div>

                    <!-- Baris Bawah: Grid Atribut 4 Kolom -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 pt-4 text-xs">
                        <!-- Kolom 1: NIK & TTL -->
                        <div class="space-y-1">
                            <span class="text-muted-foreground text-[11px] font-medium block">
                                NIK & Kelahiran
                            </span>
                            <p class="text-foreground font-mono font-bold">
                                {{ selectedInvestor.nik || '-' }}
                            </p>
                            <p class="text-muted-foreground text-[11px]">
                                {{
                                    [selectedInvestor.tempat_lahir, selectedInvestor.tanggal_lahir]
                                        .filter(Boolean)
                                        .join(', ') || '-'
                                }}
                            </p>
                        </div>

                        <!-- Kolom 2: Pekerjaan & Domisili -->
                        <div class="space-y-1">
                            <span class="text-muted-foreground text-[11px] font-medium block">
                                Pekerjaan & Domisili
                            </span>
                            <p class="text-foreground font-medium">
                                {{ selectedInvestor.pekerjaan || '-' }}
                            </p>
                            <p
                                class="text-muted-foreground text-[11px] line-clamp-2"
                                :title="selectedInvestor.alamat"
                            >
                                {{ selectedInvestor.alamat || '-' }}
                            </p>
                        </div>

                        <!-- Kolom 3: Ahli Waris -->
                        <div class="space-y-1">
                            <span class="text-muted-foreground text-[11px] font-medium block">
                                Perlindungan Ahli Waris
                            </span>
                            <template v-if="selectedInvestor.ahli_waris">
                                <p class="text-foreground font-semibold">
                                    {{ selectedInvestor.ahli_waris.nama }}
                                </p>
                                <p class="text-muted-foreground text-[11px]">
                                    {{ selectedInvestor.ahli_waris.hubungan_keluarga }} &bull;
                                    {{ selectedInvestor.ahli_waris.no_hp }}
                                </p>
                                <p
                                    v-if="selectedInvestor.ahli_waris.alamat"
                                    class="text-muted-foreground text-[11px]"
                                    :title="selectedInvestor.ahli_waris.alamat"
                                >
                                    {{ selectedInvestor.ahli_waris.alamat }}
                                </p>
                            </template>
                            <p v-else class="text-muted-foreground text-[11px] italic">
                                Belum ada data ahli waris
                            </p>
                        </div>

                        <!-- Kolom 4: Waktu Registrasi -->
                        <div>
                            <span class="block text-[11px] font-medium text-muted-foreground">
                                Waktu Registrasi
                            </span>
                            <span class="mt-0.5 block font-medium text-foreground">
                                {{ selectedInvestor.created_at_date || selectedInvestor.created_at }}
                            </span>
                            <span
                                v-if="selectedInvestor.created_at_time"
                                class="text-muted-foreground block text-[11px] font-mono"
                            >
                                {{ selectedInvestor.created_at_time }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Tab Navigasi Minimalis (Dengan Status Badge di Masing-Masing Tab) -->
                <div class="border-b border-border overflow-x-auto scroll-smooth">
                    <div class="flex items-center gap-4 sm:gap-6 min-w-max px-0.5 pb-px text-xs">
                        <button
                            type="button"
                            @click="selectTab('trading')"
                            :class="[
                                'shrink-0 flex items-center gap-2 border-b-2 pt-1 pb-2.5 sm:pt-0 sm:pb-3 min-h-[40px] font-medium transition-colors whitespace-nowrap focus:outline-none',
                                activeDocumentTab === 'trading'
                                    ? 'border-primary-600 text-primary-600 dark:text-primary-400 font-semibold'
                                    : 'border-transparent text-muted-foreground hover:text-foreground',
                            ]"
                        >
                            <TrendingUp class="h-4 w-4 shrink-0" />
                            <span>Akun Copy Trade</span>
                            <span
                                v-if="selectedInvestor.akun_copytrade"
                                :class="[
                                    'shrink-0 rounded-full border px-2 py-0.5 text-[10px] font-semibold whitespace-nowrap',
                                    getStatusBadge(
                                        selectedInvestor.akun_copytrade.status,
                                        'copytrade'
                                    ).bg,
                                    activeDocumentTab === 'trading'
                                        ? 'border-primary-600/30'
                                        : '',
                                ]"
                            >
                                {{
                                    getStatusBadge(
                                        selectedInvestor.akun_copytrade.status,
                                        'copytrade'
                                    ).label
                                }}
                            </span>
                        </button>

                        <button
                            type="button"
                            @click="selectTab('ktp')"
                            :class="[
                                'shrink-0 flex items-center gap-2 border-b-2 pt-1 pb-2.5 sm:pt-0 sm:pb-3 min-h-[40px] font-medium transition-colors whitespace-nowrap focus:outline-none',
                                activeDocumentTab === 'ktp'
                                    ? 'border-primary-600 text-primary-600 dark:text-primary-400 font-semibold'
                                    : 'border-transparent text-muted-foreground hover:text-foreground',
                            ]"
                        >
                            <CreditCard class="h-4 w-4 shrink-0" />
                            <span>Foto KTP</span>
                            <span
                                v-if="selectedInvestor.dokumen?.ktp"
                                :class="[
                                    'shrink-0 rounded-full border px-2 py-0.5 text-[10px] font-semibold whitespace-nowrap',
                                    getStatusBadge(selectedInvestor.dokumen.ktp.status).bg,
                                    activeDocumentTab === 'ktp'
                                        ? 'border-primary-600/30'
                                        : '',
                                ]"
                            >
                                {{ getStatusBadge(selectedInvestor.dokumen.ktp.status).label }}
                            </span>
                        </button>

                        <button
                            type="button"
                            @click="selectTab('perjanjian')"
                            :class="[
                                'shrink-0 flex items-center gap-2 border-b-2 pt-1 pb-2.5 sm:pt-0 sm:pb-3 min-h-[40px] font-medium transition-colors whitespace-nowrap focus:outline-none',
                                activeDocumentTab === 'perjanjian'
                                    ? 'border-primary-600 text-primary-600 dark:text-primary-400 font-semibold'
                                    : 'border-transparent text-muted-foreground hover:text-foreground',
                            ]"
                        >
                            <FileText class="h-4 w-4 shrink-0" />
                            <span>Surat Perjanjian</span>
                            <span
                                v-if="selectedInvestor.dokumen?.perjanjian"
                                :class="[
                                    'shrink-0 rounded-full border px-2 py-0.5 text-[10px] font-semibold whitespace-nowrap',
                                    getStatusBadge(selectedInvestor.dokumen.perjanjian.status).bg,
                                    activeDocumentTab === 'perjanjian'
                                        ? 'border-primary-600/30'
                                        : '',
                                ]"
                            >
                                {{ getStatusBadge(selectedInvestor.dokumen.perjanjian.status).label }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Konten Tab 1: Akun Copy Trade & Perbankan -->
                <div v-if="activeDocumentTab === 'trading'" class="space-y-4">
                    <div v-if="selectedInvestor.akun_copytrade" class="space-y-4">
                        <!-- Header Bar: Tier & Catatan Terkini -->
                        <div class="flex flex-wrap items-center justify-between gap-2.5">
                            <span
                                class="rounded-md border border-border bg-muted/60 px-2.5 py-1 text-xs font-semibold text-foreground"
                            >
                                {{ getTierBadge(selectedInvestor.akun_copytrade.tier).label }}
                            </span>
                            <span
                                v-if="selectedInvestor.akun_copytrade.catatan_admin"
                                class="text-muted-foreground text-xs"
                            >
                                Catatan saat ini:
                                <strong class="text-foreground font-medium"
                                    >"{{ selectedInvestor.akun_copytrade.catatan_admin }}"</strong
                                >
                            </span>
                        </div>

                        <!-- Grid Kartu Metrik 3 Kolom Seragam (MT4, Modal, Rekening) -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
                            <!-- Kartu 1: Akun MT4 -->
                            <div class="rounded-xl border border-border bg-card p-3.5 space-y-2">
                                <span class="text-muted-foreground text-[11px] font-medium block">
                                    Nomor Akun Trading MT4
                                </span>
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-mono text-base font-bold text-foreground">
                                        {{ selectedInvestor.akun_copytrade.nomor_akun_trading }}
                                    </span>
                                    <button
                                        type="button"
                                        @click="
                                            copyToClipboard(
                                                selectedInvestor.akun_copytrade.nomor_akun_trading,
                                                'Nomor Akun MT4'
                                            )
                                        "
                                        class="hover:bg-muted text-muted-foreground hover:text-foreground flex items-center gap-1 rounded-md px-2 py-1 text-[11px] font-medium transition-colors"
                                        title="Salin Nomor Akun"
                                    >
                                        <Copy class="h-3.5 w-3.5" />
                                        <span>Salin</span>
                                    </button>
                                </div>
                                <p class="text-muted-foreground text-[11px] pt-0.5">
                                    Broker:
                                    <strong class="text-foreground font-semibold">{{
                                        selectedInvestor.akun_copytrade.broker
                                    }}</strong>
                                    &bull; Tipe: {{ selectedInvestor.akun_copytrade.tipe_akun }}
                                </p>
                            </div>

                            <!-- Kartu 2: Estimasi Modal -->
                            <div class="rounded-xl border border-border bg-card p-3.5 space-y-2">
                                <span class="text-muted-foreground text-[11px] font-medium block">
                                    Estimasi Modal Awal
                                </span>
                                <div class="text-base font-bold text-foreground">
                                    ${{
                                        Number(
                                            selectedInvestor.akun_copytrade.estimasi_modal
                                        ).toLocaleString('en-US')
                                    }}
                                </div>
                                <p class="text-muted-foreground text-[11px] pt-0.5">
                                    Mulai: {{ selectedInvestor.akun_copytrade.tanggal_mulai }}
                                    <template
                                        v-if="selectedInvestor.akun_copytrade.tanggal_selesai"
                                    >
                                        &bull; Selesai:
                                        {{ selectedInvestor.akun_copytrade.tanggal_selesai }}
                                    </template>
                                </p>
                            </div>

                            <!-- Kartu 3: Rekening Pencairan -->
                            <div class="rounded-xl border border-border bg-card p-3.5 space-y-2">
                                <span class="text-muted-foreground text-[11px] font-medium block">
                                    Rekening Bank Pencairan (WD)
                                </span>
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-semibold text-foreground">
                                        {{ selectedInvestor.akun_copytrade.nama_bank }} -
                                        <span class="font-mono">{{
                                            selectedInvestor.akun_copytrade.nomor_rekening
                                        }}</span>
                                    </span>
                                    <button
                                        type="button"
                                        @click="
                                            copyToClipboard(
                                                selectedInvestor.akun_copytrade.nomor_rekening,
                                                'Nomor Rekening Bank'
                                            )
                                        "
                                        class="hover:bg-muted text-muted-foreground hover:text-foreground flex items-center gap-1 rounded-md px-2 py-1 text-[11px] font-medium transition-colors shrink-0"
                                        title="Salin Nomor Rekening"
                                    >
                                        <Copy class="h-3.5 w-3.5" />
                                        <span>Salin</span>
                                    </button>
                                </div>
                                <p class="text-muted-foreground text-[11px] pt-0.5">
                                    a.n.
                                    <strong class="text-foreground font-semibold">{{
                                        selectedInvestor.akun_copytrade.atas_nama_rekening
                                    }}</strong>
                                </p>
                            </div>
                        </div>

                        <!-- Kredensial Private VPS MT4 (Tier 3) - Monokromatik & Bersih -->
                        <div
                            v-if="
                                selectedInvestor.akun_copytrade.server_broker ||
                                selectedInvestor.akun_copytrade.master_password
                            "
                            class="rounded-xl border border-border bg-muted/30 p-4 space-y-3"
                        >
                            <div class="flex items-center gap-2">
                                <ShieldCheck class="h-4 w-4 text-foreground" />
                                <h4 class="text-xs font-bold text-foreground">
                                    Kredensial Private VPS MT4 (Tier 3)
                                </h4>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                                <!-- Server Broker -->
                                <div
                                    v-if="selectedInvestor.akun_copytrade.server_broker"
                                    class="space-y-1"
                                >
                                    <span class="text-muted-foreground text-[11px] block"
                                        >Server Broker MT4</span
                                    >
                                    <div class="flex items-center gap-2">
                                        <span class="text-foreground font-mono font-bold">
                                            {{ selectedInvestor.akun_copytrade.server_broker }}
                                        </span>
                                        <button
                                            type="button"
                                            @click="
                                                copyToClipboard(
                                                    selectedInvestor.akun_copytrade.server_broker,
                                                    'Server Broker'
                                                )
                                            "
                                            class="text-muted-foreground hover:text-foreground"
                                            title="Salin Server"
                                        >
                                            <Copy class="h-3.5 w-3.5" />
                                        </button>
                                    </div>
                                </div>

                                <!-- Master Password -->
                                <div
                                    v-if="selectedInvestor.akun_copytrade.master_password"
                                    class="space-y-1"
                                >
                                    <span class="text-muted-foreground text-[11px] block"
                                        >Master Password</span
                                    >
                                    <div class="flex items-center gap-2">
                                        <span class="text-foreground font-mono font-bold">
                                            {{
                                                showMasterPassword
                                                    ? selectedInvestor.akun_copytrade.master_password
                                                    : '••••••••••••'
                                            }}
                                        </span>
                                        <button
                                            type="button"
                                            @click="showMasterPassword = !showMasterPassword"
                                            class="text-muted-foreground hover:text-foreground"
                                            title="Lihat / Sembunyikan"
                                        >
                                            <EyeOff v-if="showMasterPassword" class="h-3.5 w-3.5" />
                                            <Eye v-else class="h-3.5 w-3.5" />
                                        </button>
                                        <button
                                            type="button"
                                            @click="
                                                copyToClipboard(
                                                    selectedInvestor.akun_copytrade.master_password,
                                                    'Master Password'
                                                )
                                            "
                                            class="text-muted-foreground hover:text-foreground"
                                            title="Salin Password"
                                        >
                                            <Copy class="h-3.5 w-3.5" />
                                        </button>
                                    </div>
                                </div>

                                <!-- Investor Read-only Password -->
                                <div
                                    v-if="selectedInvestor.akun_copytrade.investor_password"
                                    class="space-y-1"
                                >
                                    <span class="text-muted-foreground text-[11px] block"
                                        >Investor (Read-only) Password</span
                                    >
                                    <div class="flex items-center gap-2">
                                        <span class="text-foreground font-mono font-bold">
                                            {{
                                                showInvestorPassword
                                                    ? selectedInvestor.akun_copytrade
                                                          .investor_password
                                                    : '••••••••••••'
                                            }}
                                        </span>
                                        <button
                                            type="button"
                                            @click="showInvestorPassword = !showInvestorPassword"
                                            class="text-muted-foreground hover:text-foreground"
                                            title="Lihat / Sembunyikan"
                                        >
                                            <EyeOff
                                                v-if="showInvestorPassword"
                                                class="h-3.5 w-3.5"
                                            />
                                            <Eye v-else class="h-3.5 w-3.5" />
                                        </button>
                                        <button
                                            type="button"
                                            @click="
                                                copyToClipboard(
                                                    selectedInvestor.akun_copytrade
                                                        .investor_password,
                                                    'Investor Password'
                                                )
                                            "
                                            class="text-muted-foreground hover:text-foreground"
                                            title="Salin Password"
                                        >
                                            <Copy class="h-3.5 w-3.5" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Validasi Status Akun Trading -->
                        <form
                            @submit.prevent="submitAccountVerification"
                            class="border-t border-border pt-4 space-y-4"
                        >
                            <div class="space-y-1.5">
                                <label class="text-foreground block text-xs font-bold">
                                    Keputusan Validasi Akun Trading
                                    <span class="text-destructive">*</span>
                                </label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" role="radiogroup" aria-label="Keputusan Validasi Akun Trading">
                                    <button
                                        type="button"
                                        role="radio"
                                        :aria-checked="accountVerifyForm.status === 'active'"
                                        @click="accountVerifyForm.status = 'active'"
                                        :class="[
                                            'flex items-center gap-2.5 rounded-xl border p-3 text-xs font-semibold transition-colors text-left focus:outline-none focus:ring-2 focus:ring-primary-500/30',
                                            accountVerifyForm.status === 'active'
                                                ? 'border-emerald-500 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 ring-1 ring-emerald-500/20'
                                                : 'border-border bg-card hover:bg-muted/40 text-foreground',
                                        ]"
                                    >
                                        <CheckCircle2
                                            class="h-4 w-4 shrink-0 text-emerald-600 dark:text-emerald-400"
                                        />
                                        <span>Valid & Sudah Follow</span>
                                    </button>

                                    <button
                                        type="button"
                                        role="radio"
                                        :aria-checked="accountVerifyForm.status === 'rejected'"
                                        @click="accountVerifyForm.status = 'rejected'"
                                        :class="[
                                            'flex items-center gap-2.5 rounded-xl border p-3 text-xs font-semibold transition-colors text-left focus:outline-none focus:ring-2 focus:ring-primary-500/30',
                                            accountVerifyForm.status === 'rejected'
                                                ? 'border-rose-500 bg-rose-500/10 text-rose-700 dark:text-rose-400 ring-1 ring-rose-500/20'
                                                : 'border-border bg-card hover:bg-muted/40 text-foreground',
                                        ]"
                                    >
                                        <XCircle
                                            class="h-4 w-4 shrink-0 text-rose-600 dark:text-rose-400"
                                        />
                                        <span>Ditolak / Belum Follow</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Catatan Verifikator Akun Trading (Default hidden, muncul saat ditolak) -->
                            <div
                                v-if="accountVerifyForm.status === 'rejected'"
                                class="space-y-1.5"
                            >
                                <label
                                    for="account_catatan_admin"
                                    class="text-foreground block text-xs font-semibold"
                                >
                                    Catatan / Instruksi untuk Investor
                                    <span class="text-destructive">*</span>
                                </label>
                                <textarea
                                    id="account_catatan_admin"
                                    v-model="accountVerifyForm.catatan_admin"
                                    rows="2"
                                    placeholder="Contoh: Akun MT4 belum terdeteksi mengikuti link master copy trade. Harap ikuti instruksi follow master atau hubungi admin."
                                    class="bg-background border-input focus:ring-primary-500 w-full resize-none rounded-lg border px-3 py-2 text-xs transition-all focus:ring-2 focus:outline-none"
                                ></textarea>
                            </div>

                            <!-- Submit Account Verification Button -->
                            <div class="flex items-center justify-end gap-3 pt-1">
                                <Button
                                    type="submit"
                                    :disabled="accountVerifyForm.processing || !accountVerifyForm.status"
                                    class="bg-primary-600 hover:bg-primary-700 px-5 py-2 text-xs text-white"
                                >
                                    <Loader2
                                        v-if="accountVerifyForm.processing"
                                        class="mr-1.5 h-3.5 w-3.5 animate-spin"
                                    />
                                    <span>{{
                                        accountVerifyForm.processing
                                            ? 'Menyimpan...'
                                            : 'Simpan Status Akun Trading'
                                    }}</span>
                                </Button>
                            </div>
                        </form>
                    </div>

                    <div v-else class="text-muted-foreground py-8 text-center text-xs italic">
                        Investor ini belum mendaftarkan akun copy trade.
                    </div>
                </div>

                <!-- Konten Tab 2 & 3: Dokumen Legalitas (KTP / Perjanjian) -->
                <div v-else class="space-y-5">
                    <div v-if="selectedInvestor.dokumen?.[activeDocumentTab]" class="space-y-5">
                        <!-- Heuristic Quality Warning Alert -->
                        <div
                            v-if="selectedInvestor.dokumen[activeDocumentTab].is_quality_warning"
                            class="flex items-center gap-2.5 rounded-xl border border-amber-500/30 bg-amber-500/10 p-3 text-xs text-amber-800 dark:text-amber-300"
                        >
                            <AlertTriangle class="h-4 w-4 shrink-0" />
                            <span>
                                <strong>Peringatan Kualitas Berkas:</strong> Dokumen ini memiliki
                                ketajaman rendah atau resolusi di bawah standar rekomendasi. Mohon
                                periksa keterbacaan secara seksama.
                            </span>
                        </div>

                        <!-- Area Viewer Dokumen + Toolbar Preview -->
                        <div class="space-y-2">
                            <!-- Toolbar Kontrol Berkas -->
                            <div
                                class="bg-muted/40 border-border text-muted-foreground flex items-center justify-between rounded-lg border p-2 text-xs"
                            >
                                <span class="text-foreground font-semibold truncate">
                                    {{ selectedInvestor.dokumen[activeDocumentTab].file_name }}
                                    <span class="text-muted-foreground font-normal">
                                        ({{
                                            formatBytes(
                                                selectedInvestor.dokumen[activeDocumentTab].file_size
                                            )
                                        }})
                                    </span>
                                </span>

                                <!-- Kontrol Toolbar Gambar -->
                                <div class="flex items-center gap-1 shrink-0">
                                    <template
                                        v-if="
                                            selectedInvestor.dokumen[activeDocumentTab]
                                                .mime_type !== 'application/pdf'
                                        "
                                    >
                                        <button
                                            type="button"
                                            @click="zoomIn"
                                            class="hover:bg-background text-foreground rounded p-1"
                                            title="Perbesar"
                                        >
                                            <ZoomIn class="h-3.5 w-3.5" />
                                        </button>
                                        <button
                                            type="button"
                                            @click="zoomOut"
                                            class="hover:bg-background text-foreground rounded p-1"
                                            title="Perkecil"
                                        >
                                            <ZoomOut class="h-3.5 w-3.5" />
                                        </button>
                                        <button
                                            type="button"
                                            @click="rotateImage"
                                            class="hover:bg-background text-foreground rounded p-1"
                                            title="Putar 90 Derajat"
                                        >
                                            <RotateCw class="h-3.5 w-3.5" />
                                        </button>
                                        <a
                                            :href="
                                                selectedInvestor.dokumen[activeDocumentTab]
                                                    .preview_url
                                            "
                                            target="_blank"
                                            class="hover:bg-background text-foreground flex items-center gap-1 rounded p-1 text-[11px]"
                                            title="Buka Tab Baru"
                                        >
                                            <ExternalLink class="h-3.5 w-3.5" />
                                        </a>
                                    </template>

                                    <!-- Link Pratinjau Khusus Dokumen (PDF) -->
                                    <template v-else>
                                        <a
                                            :href="
                                                selectedInvestor.dokumen[activeDocumentTab]
                                                    .preview_url
                                            "
                                            target="_blank"
                                            class="hover:bg-background text-foreground border-border flex items-center gap-1.5 rounded border px-2.5 py-1 text-[11px] font-medium transition-colors"
                                            title="Buka Pratinjau Dokumen di Tab Baru"
                                        >
                                            <ExternalLink class="h-3.5 w-3.5" />
                                            <span>Buka Dokumen PDF</span>
                                        </a>
                                    </template>
                                </div>
                            </div>

                            <!-- Viewer Box -->
                            <div
                                class="bg-muted/20 border-border relative flex h-80 w-full items-center justify-center overflow-hidden rounded-xl border"
                            >
                                <!-- PDF Viewer -->
                                <iframe
                                    v-if="
                                        selectedInvestor.dokumen[activeDocumentTab].mime_type ===
                                        'application/pdf'
                                    "
                                    :src="selectedInvestor.dokumen[activeDocumentTab].preview_url"
                                    class="h-full w-full rounded-xl"
                                ></iframe>

                                <!-- Image Viewer with Zoom & Rotate -->
                                <div
                                    v-else
                                    class="flex h-full w-full items-center justify-center overflow-auto p-4"
                                >
                                    <img
                                        :src="
                                            selectedInvestor.dokumen[activeDocumentTab].preview_url
                                        "
                                        :alt="activeDocumentTab"
                                        :style="{
                                            transform: `scale(${imageZoom}) rotate(${imageRotation}deg)`,
                                            transition: 'transform 0.2s ease-in-out',
                                        }"
                                        class="max-h-full max-w-full rounded-lg object-contain shadow-sm"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Form Keputusan Verifikasi Dokumen -->
                        <form
                            @submit.prevent="submitVerification(activeDocumentTab)"
                            class="border-t border-border pt-4 space-y-4"
                        >
                            <div class="space-y-1.5">
                                <label class="text-foreground block text-xs font-bold">
                                    Keputusan Verifikasi Dokumen
                                    <span class="text-destructive">*</span>
                                </label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" role="radiogroup" aria-label="Keputusan Verifikasi Dokumen">
                                    <button
                                        type="button"
                                        role="radio"
                                        :aria-checked="verifyForm.status === 'approved'"
                                        @click="verifyForm.status = 'approved'"
                                        :class="[
                                            'flex items-center gap-2.5 rounded-xl border p-3 text-xs font-semibold transition-colors text-left focus:outline-none focus:ring-2 focus:ring-primary-500/30',
                                            verifyForm.status === 'approved'
                                                ? 'border-emerald-500 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 ring-1 ring-emerald-500/20'
                                                : 'border-border bg-card hover:bg-muted/40 text-foreground',
                                        ]"
                                    >
                                        <CheckCircle2
                                            class="h-4 w-4 shrink-0 text-emerald-600 dark:text-emerald-400"
                                        />
                                        <span>Setujui (Valid)</span>
                                    </button>

                                    <button
                                        type="button"
                                        role="radio"
                                        :aria-checked="verifyForm.status === 'revision_needed'"
                                        @click="verifyForm.status = 'revision_needed'"
                                        :class="[
                                            'flex items-center gap-2.5 rounded-xl border p-3 text-xs font-semibold transition-colors text-left focus:outline-none focus:ring-2 focus:ring-primary-500/30',
                                            verifyForm.status === 'revision_needed'
                                                ? 'border-amber-500 bg-amber-500/10 text-amber-700 dark:text-amber-400 ring-1 ring-amber-500/20'
                                                : 'border-border bg-card hover:bg-muted/40 text-foreground',
                                        ]"
                                    >
                                        <AlertTriangle
                                            class="h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400"
                                        />
                                        <span>Minta Revisi</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Catatan Verifikator Dokumen (Default hidden, muncul saat minta revisi) -->
                            <div
                                v-if="verifyForm.status === 'revision_needed'"
                                class="space-y-1.5"
                            >
                                <label
                                    for="catatan_admin"
                                    class="text-foreground block text-xs font-semibold"
                                >
                                    Catatan / Instruksi Revisi
                                    <span class="text-destructive">*</span>
                                </label>
                                <textarea
                                    id="catatan_admin"
                                    v-model="verifyForm.catatan_admin"
                                    rows="2"
                                    placeholder="Contoh: Foto sedikit terpotong, mohon unggah ulang."
                                    class="bg-background border-input focus:ring-primary-500 w-full resize-none rounded-lg border px-3 py-2 text-xs transition-all focus:ring-2 focus:outline-none"
                                ></textarea>
                            </div>

                            <!-- Submit Verification Button -->
                            <div class="flex items-center justify-end gap-3 pt-1">
                                <Button
                                    type="submit"
                                    :disabled="verifyForm.processing || !verifyForm.status"
                                    class="bg-primary-600 hover:bg-primary-700 px-5 py-2 text-xs text-white"
                                >
                                    <Loader2
                                        v-if="verifyForm.processing"
                                        class="mr-1.5 h-3.5 w-3.5 animate-spin"
                                    />
                                    <span>{{
                                        verifyForm.processing
                                            ? 'Menyimpan...'
                                            : 'Simpan Verifikasi ' +
                                              (activeDocumentTab === 'ktp' ? 'KTP' : 'Perjanjian')
                                    }}</span>
                                </Button>
                            </div>
                        </form>
                    </div>

                    <div v-else class="text-muted-foreground py-8 text-center text-xs italic">
                        Dokumen {{ activeDocumentTab.toUpperCase() }} belum diunggah oleh investor.
                    </div>
                </div>
            </div>

        </AppModal>
    </AdminLayout>
</template>
