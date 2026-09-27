<script setup>
import { ref, computed } from 'vue';
import { useForm, router, Head } from '@inertiajs/vue3';
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
    Eye,
    CreditCard,
    Loader2,
    RotateCw,
    ZoomIn,
    ZoomOut,
    ExternalLink,
    Users,
    ShieldCheck,
    UserCheck,
    Link2,
    Calendar,
    Phone,
    MapPin,
    Briefcase,
    FileText,
} from 'lucide-vue-next';

const props = defineProps({
    investors: {
        type: Object,
        required: true,
    },
    counts: {
        type: Object,
        default: () => ({
            all: 0,
            pending: 0,
            active: 0,
            revision_needed: 0,
        }),
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
    { key: 'nik', label: 'NIK & Kontak' },
    { key: 'upline', label: 'Upline / Referral' },
    { key: 'dokumen_ktp', label: 'Status KTP' },
    { key: 'status', label: 'Status Akun', sortable: true },
    { key: 'actions', label: 'Aksi', align: 'right' },
];

// State Modal Detail & Verifikasi
const isVerifyModalOpen = ref(false);
const selectedInvestor = ref(null);

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

// Form Verifikasi Dokumen KTP
const verifyForm = useForm({
    status: '',
    catatan_admin: '',
});

const openVerifyModal = (investor) => {
    selectedInvestor.value = investor;
    resetImageTransform();

    verifyForm.status = investor.dokumen?.ktp?.status === 'approved'
        ? 'approved'
        : (investor.dokumen?.ktp?.status === 'revision_needed' ? 'revision_needed' : '');
    verifyForm.catatan_admin = investor.dokumen?.ktp?.catatan_admin || '';

    isVerifyModalOpen.value = true;
};

const closeVerifyModal = () => {
    isVerifyModalOpen.value = false;
    selectedInvestor.value = null;
    verifyForm.reset();
    verifyForm.status = '';
    verifyForm.catatan_admin = '';
};

const checkAndUpdateInvestorStatus = () => {
    if (!selectedInvestor.value) return;
    const ktpOk = selectedInvestor.value.dokumen?.ktp?.status === 'approved';
    selectedInvestor.value.status = ktpOk ? 'active' : 'pending';
};

const isInvestorActive = computed(() => {
    if (!selectedInvestor.value) return false;
    return selectedInvestor.value.dokumen?.ktp?.status === 'approved';
});

const submitVerification = () => {
    const doc = selectedInvestor.value?.dokumen?.ktp;
    if (!doc) {
        toast.error('Dokumen KTP tidak ditemukan.');
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
            if (selectedInvestor.value?.dokumen?.ktp) {
                selectedInvestor.value.dokumen.ktp.status = verifyForm.status;
                selectedInvestor.value.dokumen.ktp.catatan_admin = verifyForm.catatan_admin;
            }
            checkAndUpdateInvestorStatus();
        },
    });
};

const filterByQuickStatus = (statusValue) => {
    router.get(
        '/admin/kyc',
        {
            ...props.filters,
            status: statusValue === props.filters.status ? undefined : statusValue,
            page: 1,
        },
        { preserveState: true, preserveScroll: true }
    );
};

const getStatusBadge = (status) => {
    switch (status) {
        case 'active':
        case 'approved':
            return {
                label: 'Disetujui',
                bg: 'bg-emerald-500/10 text-emerald-800 dark:text-emerald-300 border-emerald-500/30',
                icon: CheckCircle2,
            };
        case 'revision_needed':
            return {
                label: 'Perlu Revisi',
                bg: 'bg-amber-500/10 text-amber-800 dark:text-amber-300 border-amber-500/30',
                icon: AlertTriangle,
            };
        case 'pending':
        default:
            return {
                label: 'Menunggu Verifikasi',
                bg: 'bg-sky-500/10 text-sky-800 dark:text-sky-300 border-sky-500/30',
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
</script>

<template>
    <Head title="Verifikasi Dokumen Legalitas (KYC)" />
    <AdminLayout
        title="Verifikasi Dokumen Legalitas (KYC)"
        subtitle="Pemeriksaan keaslian identitas KTP, verifikasi data pribadi, kontak darurat/ahli waris, dan persetujuan legalitas investor."
        :breadcrumbs="[
            { label: 'Operasional & Validasi' },
            { label: 'Verifikasi Legalitas' }
        ]"
    >
        <div class="space-y-6">

            <!-- Quick Filter Stat Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                <button
                    type="button"
                    @click="filterByQuickStatus('')"
                    :class="[
                        'p-4 rounded-xl border text-left transition-all',
                        !filters.status
                            ? 'border-primary-500 bg-primary-500/10 ring-1 ring-primary-500/30'
                            : 'border-border bg-card hover:bg-muted/40',
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-primary-700 dark:text-primary-300">Semua Investor</span>
                        <Users class="h-4 w-4 text-primary-600 dark:text-primary-400" />
                    </div>
                    <p class="mt-2 text-2xl font-bold text-foreground font-mono">{{ counts.all }}</p>
                    <span class="text-[11px] text-muted-foreground">Total pendaftar</span>
                </button>

                <button
                    type="button"
                    @click="filterByQuickStatus('pending')"
                    :class="[
                        'p-4 rounded-xl border text-left transition-all',
                        filters.status === 'pending'
                            ? 'border-sky-500 bg-sky-500/10 ring-1 ring-sky-500/30'
                            : 'border-border bg-card hover:bg-muted/40',
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-sky-700 dark:text-sky-300">Menunggu</span>
                        <Clock class="h-4 w-4 text-sky-600 dark:text-sky-400" />
                    </div>
                    <p class="mt-2 text-2xl font-bold text-foreground font-mono">{{ counts.pending }}</p>
                    <span class="text-[11px] text-muted-foreground">Perlu ditinjau</span>
                </button>

                <button
                    type="button"
                    @click="filterByQuickStatus('revision_needed')"
                    :class="[
                        'p-4 rounded-xl border text-left transition-all',
                        filters.status === 'revision_needed'
                            ? 'border-amber-500 bg-amber-500/10 ring-1 ring-amber-500/30'
                            : 'border-border bg-card hover:bg-muted/40',
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-amber-800 dark:text-amber-300">Perlu Revisi</span>
                        <AlertTriangle class="h-4 w-4 text-amber-600 dark:text-amber-400" />
                    </div>
                    <p class="mt-2 text-2xl font-bold text-foreground font-mono">{{ counts.revision_needed }}</p>
                    <span class="text-[11px] text-muted-foreground">Dokumen tidak valid</span>
                </button>

                <button
                    type="button"
                    @click="filterByQuickStatus('active')"
                    :class="[
                        'p-4 rounded-xl border text-left transition-all',
                        filters.status === 'active'
                            ? 'border-emerald-500 bg-emerald-500/10 ring-1 ring-emerald-500/30'
                            : 'border-border bg-card hover:bg-muted/40',
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-emerald-800 dark:text-emerald-300">Aktif & Disetujui</span>
                        <CheckCircle2 class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                    </div>
                    <p class="mt-2 text-2xl font-bold text-foreground font-mono">{{ counts.active }}</p>
                    <span class="text-[11px] text-muted-foreground">KYC Terverifikasi</span>
                </button>
            </div>

            <!-- Tabel Daftar KYC via AppDataTable -->
            <AppDataTable
                :data="investors"
                :columns="columns"
                :filters="filters"
                fetch-url="/admin/kyc"
                search-placeholder="Cari investor, username, NIK, no HP..."
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
                    </select>
                </template>

                <!-- Custom Column: Terdaftar -->
                <template #column-created_at="{ item }">
                    <div class="space-y-0.5 whitespace-nowrap">
                        <span class="text-foreground text-xs font-medium block">
                            {{ item.created_at_date }}
                        </span>
                        <span class="text-muted-foreground text-[11px] font-mono block">
                            {{ item.created_at_time }}
                        </span>
                    </div>
                </template>

                <!-- Custom Column: Investor Name & Username -->
                <template #column-name="{ item }">
                    <div class="space-y-0.5 min-w-[140px]">
                        <div class="flex items-center gap-1.5">
                            <span class="text-foreground font-bold text-xs">{{ item.name }}</span>
                            <span
                                v-if="item.role === 'affiliator'"
                                class="inline-flex items-center rounded-full border border-primary-500/30 bg-primary-500/10 px-1.5 py-0.2 text-[9px] font-semibold text-primary-700 dark:text-primary-300"
                            >
                                Affiliator
                            </span>
                        </div>
                        <div class="text-muted-foreground text-[11px] flex flex-wrap items-center gap-1">
                            <span>@{{ item.username }}</span>
                            <span>&bull;</span>
                            <span>{{ item.email }}</span>
                        </div>
                    </div>
                </template>

                <!-- Custom Column: NIK & Kontak -->
                <template #column-nik="{ item }">
                    <div class="space-y-0.5 whitespace-nowrap">
                        <span class="text-foreground font-mono font-bold text-xs block">
                            {{ item.nik || '-' }}
                        </span>
                        <span class="text-muted-foreground font-mono text-[11px] block">
                            {{ item.phone || '-' }}
                        </span>
                    </div>
                </template>

                <!-- Custom Column: Upline / Referral -->
                <template #column-upline="{ item }">
                    <div v-if="item.upline" class="space-y-0.5">
                        <div class="inline-flex items-center gap-1 text-xs font-semibold text-foreground">
                            <Link2 class="h-3 w-3 text-primary-500 shrink-0" />
                            <span>{{ item.upline.name }}</span>
                        </div>
                        <span class="text-muted-foreground text-[10px] font-mono block">
                            Ref: {{ item.upline.referral_code }}
                        </span>
                    </div>
                    <span v-else class="text-muted-foreground text-xs italic">
                        Organik
                    </span>
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
                        <div
                            v-if="item.dokumen.ktp.is_quality_warning"
                            class="text-[10px] text-amber-700 dark:text-amber-400 flex items-center gap-0.5"
                        >
                            <AlertTriangle class="h-2.5 w-2.5 shrink-0" />
                            <span>Resolusi rendah</span>
                        </div>
                    </div>
                    <span v-else class="text-muted-foreground text-xs italic">Belum Ada</span>
                </template>

                <!-- Custom Column: Status Akun -->
                <template #column-status="{ item }">
                    <span
                        :class="[
                            'inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-semibold',
                            item.status === 'active'
                                ? 'bg-emerald-500/10 text-emerald-800 dark:text-emerald-300 border-emerald-500/30'
                                : 'bg-sky-500/10 text-sky-800 dark:text-sky-300 border-sky-500/30',
                        ]"
                    >
                        <component
                            :is="item.status === 'active' ? CheckCircle2 : Clock"
                            class="h-3 w-3"
                        />
                        <span>{{ item.status === 'active' ? 'Aktif' : 'Pending' }}</span>
                    </span>
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
            title="Verifikasi Identitas & Dokumen KTP"
            max-width="4xl"
            @close="closeVerifyModal"
        >
            <div v-if="selectedInvestor" class="max-h-[75vh] space-y-5 overflow-y-auto ps-0.5 pe-1">
                <!-- Header Profil Investor -->
                <div class="rounded-xl border border-border bg-card p-4 sm:p-5">
                    <!-- Baris Atas: Avatar, Identitas, Status Akun -->
                    <div
                        class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-border/60"
                    >
                        <div class="flex items-center gap-3.5 min-w-0">
                            <div
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-muted font-bold text-sm text-foreground ring-1 ring-border"
                            >
                                {{ getInitials(selectedInvestor.name) }}
                            </div>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-foreground text-sm font-bold truncate">
                                        {{ selectedInvestor.name }}
                                    </h3>
                                    <span class="text-muted-foreground text-xs font-normal">
                                        @{{ selectedInvestor.username }}
                                    </span>
                                    <span
                                        v-if="selectedInvestor.role === 'affiliator'"
                                        class="inline-flex items-center rounded-full border border-primary-500/30 bg-primary-500/10 px-2 py-0.5 text-[10px] font-semibold text-primary-700 dark:text-primary-300"
                                    >
                                        Affiliator
                                    </span>
                                </div>
                                <p class="text-muted-foreground text-xs">
                                    {{ selectedInvestor.email }} &bull; {{ selectedInvestor.phone }}
                                </p>
                            </div>
                        </div>

                        <!-- Status Akun Investor -->
                        <div class="flex items-center shrink-0">
                            <span
                                :class="[
                                    'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold transition-colors',
                                    isInvestorActive
                                        ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-800 dark:text-emerald-300'
                                        : 'border-amber-500/30 bg-amber-500/10 text-amber-800 dark:text-amber-300',
                                ]
                            "
                            >
                                <component
                                    :is="isInvestorActive ? CheckCircle2 : Clock"
                                    class="h-3.5 w-3.5 shrink-0"
                                />
                                <span>{{
                                    isInvestorActive
                                        ? 'Akun Aktif & Disetujui'
                                        : 'Menunggu Verifikasi KTP'
                                }}</span>
                            </span>
                        </div>
                    </div>

                    <!-- Baris Bawah: Grid Atribut Detail 3 Kolom Responsif -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-4 text-xs">
                        <!-- Kolom 1: Data Identitas Pribadi -->
                        <div class="rounded-lg border border-border/60 bg-muted/20 p-3 space-y-2">
                            <div class="flex items-center gap-1.5 font-bold text-foreground">
                                <CreditCard class="h-3.5 w-3.5 text-primary-600 dark:text-primary-400" />
                                <span>Identitas Pribadi</span>
                            </div>
                            <div class="space-y-1">
                                <div>
                                    <span class="text-muted-foreground text-[10px] uppercase font-semibold block">NIK</span>
                                    <p class="text-foreground font-mono font-bold text-xs">
                                        {{ selectedInvestor.nik || '-' }}
                                    </p>
                                </div>
                                <div>
                                    <span class="text-muted-foreground text-[10px] uppercase font-semibold block">Kelahiran</span>
                                    <p class="text-foreground text-xs">
                                        {{
                                            [selectedInvestor.tempat_lahir, selectedInvestor.tanggal_lahir]
                                                .filter(Boolean)
                                                .join(', ') || '-'
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <span class="text-muted-foreground text-[10px] uppercase font-semibold block">Pekerjaan</span>
                                    <p class="text-foreground text-xs">
                                        {{ selectedInvestor.pekerjaan || '-' }}
                                    </p>
                                </div>
                                <div>
                                    <span class="text-muted-foreground text-[10px] uppercase font-semibold block">Domisili</span>
                                    <p class="text-foreground text-xs">
                                        {{ selectedInvestor.alamat || '-' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Kolom 2: Kontak Darurat & Ahli Waris -->
                        <div class="rounded-lg border border-border/60 bg-muted/20 p-3 space-y-2">
                            <div class="flex items-center gap-1.5 font-bold text-foreground">
                                <Users class="h-3.5 w-3.5 text-primary-600 dark:text-primary-400" />
                                <span>Kontak Darurat / Ahli Waris</span>
                            </div>
                            <div v-if="selectedInvestor.ahli_waris" class="space-y-1">
                                <div>
                                    <span class="text-muted-foreground text-[10px] uppercase font-semibold block">Nama Lengkap</span>
                                    <p class="text-foreground font-bold text-xs">
                                        {{ selectedInvestor.ahli_waris.nama }}
                                    </p>
                                </div>
                                <div>
                                    <span class="text-muted-foreground text-[10px] uppercase font-semibold block">Hubungan Keluarga</span>
                                    <p class="text-foreground text-xs">
                                        {{ selectedInvestor.ahli_waris.hubungan_keluarga }}
                                    </p>
                                </div>
                                <div>
                                    <span class="text-muted-foreground text-[10px] uppercase font-semibold block">No. Telepon / WA</span>
                                    <p class="text-foreground font-mono text-xs">
                                        {{ selectedInvestor.ahli_waris.no_hp }}
                                    </p>
                                </div>
                                <div>
                                    <span class="text-muted-foreground text-[10px] uppercase font-semibold block">Alamat</span>
                                    <p class="text-foreground text-xs">
                                        {{ selectedInvestor.ahli_waris.alamat || '-' }}
                                    </p>
                                </div>
                            </div>
                            <p v-else class="text-muted-foreground text-xs italic py-2">
                                Data ahli waris belum dilengkapi
                            </p>
                        </div>

                        <!-- Kolom 3: Registrasi, Legalitas & Referral -->
                        <div class="rounded-lg border border-border/60 bg-muted/20 p-3 space-y-2">
                            <div class="flex items-center gap-1.5 font-bold text-foreground">
                                <FileText class="h-3.5 w-3.5 text-primary-600 dark:text-primary-400" />
                                <span>Pendaftaran & Legalitas</span>
                            </div>
                            <div class="space-y-1">
                                <div>
                                    <span class="text-muted-foreground text-[10px] uppercase font-semibold block">Waktu Registrasi</span>
                                    <p class="text-foreground text-xs font-medium">
                                        {{ selectedInvestor.created_at_date }} {{ selectedInvestor.created_at_time }}
                                    </p>
                                </div>
                                <div>
                                    <span class="text-muted-foreground text-[10px] uppercase font-semibold block">Persetujuan S&K</span>
                                    <p v-if="selectedInvestor.terms_agreed_at" class="text-emerald-700 dark:text-emerald-400 text-xs font-medium flex items-center gap-1">
                                        <CheckCircle2 class="h-3.5 w-3.5 shrink-0" />
                                        <span>Disetujui: {{ selectedInvestor.terms_agreed_at }}</span>
                                    </p>
                                    <p v-else class="text-muted-foreground text-xs italic">
                                        Belum disetujui
                                    </p>
                                </div>
                                <div>
                                    <span class="text-muted-foreground text-[10px] uppercase font-semibold block">Referral Affiliator</span>
                                    <p v-if="selectedInvestor.upline" class="text-foreground text-xs font-medium flex items-center gap-1">
                                        <Link2 class="h-3.5 w-3.5 text-primary-500 shrink-0" />
                                        <span>{{ selectedInvestor.upline.name }} ({{ selectedInvestor.upline.referral_code }})</span>
                                    </p>
                                    <p v-else class="text-muted-foreground text-xs italic">
                                        Organik (Tanpa Upline)
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dokumen Identitas (Foto KTP) -->
                <div class="rounded-xl border border-border bg-card p-4 sm:p-5 space-y-4">
                    <div class="flex items-center justify-between border-b border-border/60 pb-3">
                        <div class="flex items-center gap-2">
                            <CreditCard class="h-4 w-4 text-primary-600 dark:text-primary-400" />
                            <h4 class="font-bold text-xs uppercase tracking-wider text-foreground">
                                Dokumen Identitas (Foto KTP)
                            </h4>
                        </div>
                        <span
                            v-if="selectedInvestor.dokumen?.ktp"
                            :class="[
                                'inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-[10px] font-semibold whitespace-nowrap',
                                getStatusBadge(selectedInvestor.dokumen.ktp.status).bg,
                            ]"
                        >
                            <component
                                :is="getStatusBadge(selectedInvestor.dokumen.ktp.status).icon"
                                class="h-3 w-3"
                            />
                            <span>{{ getStatusBadge(selectedInvestor.dokumen.ktp.status).label }}</span>
                        </span>
                    </div>

                    <!-- Detail & Viewer KTP -->
                    <div v-if="selectedInvestor.dokumen?.ktp" class="space-y-4">
                        <!-- Quality Warning Alert -->
                        <div
                            v-if="selectedInvestor.dokumen.ktp.is_quality_warning"
                            class="flex items-center gap-2.5 rounded-xl border border-amber-500/30 bg-amber-500/10 p-3 text-xs text-amber-800 dark:text-amber-300"
                        >
                            <AlertTriangle class="h-4 w-4 shrink-0" />
                            <span>
                                <strong>Peringatan Kualitas Dokumen:</strong> Resolusi atau ketajaman berkas KTP terdeteksi rendah. Pastikan teks NIK dan identitas dapat dibaca dengan jelas sebelum menyetujui.
                            </span>
                        </div>

                        <!-- Previous Admin Note if revision needed -->
                        <div
                            v-if="selectedInvestor.dokumen.ktp.status === 'revision_needed' && selectedInvestor.dokumen.ktp.catatan_admin"
                            class="flex items-start gap-2.5 rounded-xl border border-amber-500/30 bg-amber-500/10 p-3 text-xs text-amber-900 dark:text-amber-200"
                        >
                            <AlertTriangle class="h-4 w-4 shrink-0 mt-0.5" />
                            <div>
                                <strong class="block">Catatan Revisi Saat Ini:</strong>
                                <p class="mt-0.5">{{ selectedInvestor.dokumen.ktp.catatan_admin }}</p>
                            </div>
                        </div>

                        <!-- Previous Approval Info if already approved -->
                        <div
                            v-if="selectedInvestor.dokumen.ktp.status === 'approved' && selectedInvestor.dokumen.ktp.verified_at"
                            class="flex items-center gap-2.5 rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-3 text-xs text-emerald-800 dark:text-emerald-300"
                        >
                            <CheckCircle2 class="h-4 w-4 shrink-0" />
                            <span>
                                Berkas KTP ini telah disetujui pada <strong>{{ selectedInvestor.dokumen.ktp.verified_at }}</strong>.
                            </span>
                        </div>

                        <!-- Toolbar Kontrol Preview -->
                        <div
                            class="bg-muted/40 border-border text-muted-foreground flex items-center justify-between rounded-lg border p-2 text-xs"
                        >
                            <span class="text-foreground font-semibold truncate">
                                {{ selectedInvestor.dokumen.ktp.file_name }}
                                <span class="text-muted-foreground font-normal">
                                    ({{
                                        formatBytes(
                                            selectedInvestor.dokumen.ktp.file_size
                                        )
                                    }})
                                </span>
                            </span>

                            <!-- Toolbar Zoom / Rotate -->
                            <div class="flex items-center gap-1 shrink-0">
                                <template
                                    v-if="
                                        selectedInvestor.dokumen.ktp
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
                                        :href="selectedInvestor.dokumen.ktp.preview_url"
                                        target="_blank"
                                        class="hover:bg-background text-foreground flex items-center gap-1 rounded p-1 text-[11px]"
                                        title="Buka Tab Baru"
                                    >
                                        <ExternalLink class="h-3.5 w-3.5" />
                                    </a>
                                </template>

                                <template v-else>
                                    <a
                                        :href="selectedInvestor.dokumen.ktp.preview_url"
                                        target="_blank"
                                        class="hover:bg-background text-foreground border-border flex items-center gap-1.5 rounded border px-2.5 py-1 text-[11px] font-medium transition-colors"
                                        title="Buka Berkas PDF di Tab Baru"
                                    >
                                        <ExternalLink class="h-3.5 w-3.5" />
                                        <span>Buka PDF</span>
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
                                    selectedInvestor.dokumen.ktp.mime_type ===
                                    'application/pdf'
                                "
                                :src="selectedInvestor.dokumen.ktp.preview_url"
                                class="h-full w-full rounded-xl"
                            ></iframe>

                            <!-- Image Viewer with Zoom & Rotate -->
                            <div
                                v-else
                                class="flex h-full w-full items-center justify-center overflow-auto p-4"
                            >
                                <img
                                    :src="selectedInvestor.dokumen.ktp.preview_url"
                                    alt="Foto KTP Investor"
                                    :style="{
                                        transform: `scale(${imageZoom}) rotate(${imageRotation}deg)`,
                                        transition: 'transform 0.2s ease-in-out',
                                    }"
                                    class="max-h-full max-w-full rounded-lg object-contain shadow-sm"
                                />
                            </div>
                        </div>

                        <!-- Form Keputusan Verifikasi Dokumen -->
                        <form
                            @submit.prevent="submitVerification"
                            class="border-t border-border/60 pt-4 space-y-4"
                        >
                            <div class="space-y-1.5">
                                <label class="text-foreground block text-xs font-bold">
                                    Keputusan Verifikasi KTP
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
                                                ? 'border-emerald-500 bg-emerald-500/10 text-emerald-800 dark:text-emerald-300 ring-1 ring-emerald-500/20'
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
                                                ? 'border-amber-500 bg-amber-500/10 text-amber-800 dark:text-amber-300 ring-1 ring-amber-500/20'
                                                : 'border-border bg-card hover:bg-muted/40 text-foreground',
                                        ]"
                                    >
                                        <AlertTriangle
                                            class="h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400"
                                        />
                                        <span>Minta Revisi Dokumen</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Catatan Instruksi Revisi (Wajib jika minta revisi) -->
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
                                    rows="3"
                                    placeholder="Contoh: Foto KTP sedikit terpotong pada bagian NIK. Mohon unggah ulang foto yang jelas dan seluruh sudut terlihat."
                                    class="bg-background border-input focus:ring-primary-500 w-full resize-none rounded-lg border px-3 py-2 text-xs transition-all focus:ring-2 focus:outline-none"
                                ></textarea>
                            </div>

                            <!-- Tombol Aksi Simpan -->
                            <div class="flex items-center justify-end gap-3 pt-2">
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
                                            ? 'Menyimpan Keputusan...'
                                            : 'Simpan Verifikasi KTP'
                                    }}</span>
                                </Button>
                            </div>
                        </form>
                    </div>

                    <!-- State Jika Foto KTP Belum Diunggah -->
                    <div v-else class="text-muted-foreground py-10 text-center text-xs italic">
                        Dokumen Foto KTP belum diunggah oleh investor.
                    </div>
                </div>
            </div>
        </AppModal>
    </AdminLayout>
</template>
