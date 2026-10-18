<script setup>
import { ref, computed } from 'vue';
import { useForm, router, Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AppDataTable from '@/Components/Common/AppDataTable.vue';
import AppModal from '@/Components/Common/AppModal.vue';
import AppTableAction from '@/Components/Common/AppTableAction.vue';
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
    { key: 'name', label: 'Investor / Afiliator', sortable: true },
    { key: 'nik', label: 'NIK & Kontak' },
    { key: 'upline', label: 'Upline / Referral' },
    { key: 'dokumen_ktp', label: 'Status Verifikasi' },
    { key: 'actions', label: 'Aksi', align: 'center' },
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

    verifyForm.status =
        investor.dokumen?.ktp?.status === 'approved'
            ? 'approved'
            : investor.dokumen?.ktp?.status === 'revision_needed'
              ? 'revision_needed'
              : '';
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

    verifyForm.post(`/admin/registrations/documents/${doc.id}/verify`, {
        preserveScroll: true,
        onSuccess: () => {
            closeVerifyModal();
        },
    });
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
</script>

<template>
    <Head title="Verifikasi Pendaftaran" />
    <AdminLayout
        title="Verifikasi Pendaftaran"
        subtitle="Validasi  identitas KTP pendaftar baru yang menunggu persetujuan atau perbaikan dokumen."
        :breadcrumbs="[{ label: 'Menu Utama' }, { label: 'Verifikasi Pendaftaran' }]"
    >
        <div class="space-y-6">
            <!-- Tabel Daftar Verifikasi Pendaftaran via AppDataTable -->
            <AppDataTable
                :data="investors"
                :columns="columns"
                :filters="filters"
                fetch-url="/admin/registrations"
                search-placeholder="Cari nama, username, upline..."
                empty-title="Tidak Ada Antrean Verifikasi"
                empty-message="Semua pendaftaran telah diproses atau tidak ada antrean verifikasi yang sesuai dengan kriteria filter Anda."
            >
                <!-- Filter Slot: Status Filter Dropdown -->
                <template #filters="{ applyFilters }">
                    <select
                        :value="filters.status || ''"
                        @change="
                            (e) => applyFilters({ status: e.target.value || undefined, page: 1 })
                        "
                        class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border px-3 py-1.5 text-xs font-medium focus:ring-2 focus:outline-none sm:w-auto"
                    >
                        <option value="">Semua Status Antrean</option>
                        <option value="pending">Menunggu Verifikasi</option>
                        <option value="revision_needed">Perlu Revisi</option>
                    </select>
                </template>

                <!-- Custom Column: Terdaftar -->
                <template #column-created_at="{ item }">
                    <div class="space-y-0.5 whitespace-nowrap">
                        <span class="text-foreground block text-xs font-medium">
                            {{ item.created_at_date }}
                        </span>
                        <span class="text-muted-foreground block font-mono text-[11px]">
                            {{ item.created_at_time }}
                        </span>
                    </div>
                </template>

                <!-- Custom Column: Investor Name & Username -->
                <template #column-name="{ item }">
                    <div class="min-w-[140px] space-y-0.5">
                        <div class="flex items-center gap-1.5">
                            <span class="text-foreground text-xs font-bold">{{ item.name }}</span>
                            <span
                                v-if="item.role === 'affiliator'"
                                class="py-0.2 inline-flex items-center rounded-full border border-indigo-200 bg-indigo-50 px-1.5 text-[9px] font-semibold text-indigo-700 dark:border-indigo-800 dark:bg-indigo-950/50 dark:text-indigo-300"
                            >
                                Affiliator
                            </span>
                            <span
                                v-else
                                class="py-0.2 inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-1.5 text-[9px] font-semibold text-sky-700 dark:border-sky-800 dark:bg-sky-950/50 dark:text-sky-300"
                            >
                                Investor
                            </span>
                        </div>
                        <div
                            class="text-muted-foreground flex flex-wrap items-center gap-1 text-[11px]"
                        >
                            <span>@{{ item.username }}</span>
                            <span>&bull;</span>
                            <span>{{ item.email }}</span>
                        </div>
                    </div>
                </template>

                <!-- Custom Column: NIK & Kontak -->
                <template #column-nik="{ item }">
                    <div class="space-y-0.5 whitespace-nowrap">
                        <span class="text-foreground block font-mono text-xs">
                            {{ item.nik || '-' }}
                        </span>
                        <span class="text-muted-foreground block font-mono text-[11px]">
                            {{ item.phone || '-' }}
                        </span>
                    </div>
                </template>

                <!-- Custom Column: Upline / Referral -->
                <template #column-upline="{ item }">
                    <div v-if="item.upline" class="space-y-0.5">
                        <div
                            class="text-foreground inline-flex items-center gap-1 text-xs font-semibold"
                        >
                            <span>{{ item.upline.name }}</span>
                        </div>
                        <span class="text-muted-foreground block font-mono text-[10px]">
                            Ref: {{ item.upline.referral_code }}
                        </span>
                    </div>
                    <span v-else class="text-muted-foreground text-xs italic">
                        Tanpa Referral
                    </span>
                </template>

                <!-- Custom Column: Dokumen KTP -->
                <template #column-dokumen_ktp="{ item }">
                    <div v-if="item.dokumen?.ktp" class="space-y-1">
                        <div
                            :class="[
                                'inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-semibold text-nowrap',
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
                    <span v-else class="text-muted-foreground text-xs italic">Belum Ada</span>
                </template>

                <!-- Custom Column: Actions -->
                <template #column-actions="{ item }">
                    <AppTableAction
                        type="verify"
                        title="Tinjau Pendaftaran"
                        @click="openVerifyModal(item)"
                    />
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
                <div class="border-border bg-card rounded-xl border p-4 sm:p-5">
                    <!-- Baris Atas: Identitas Investor -->
                    <div class="border-border/60 border-b pb-4">
                        <div class="flex min-w-0 items-center gap-3.5">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-foreground truncate text-sm font-bold">
                                        {{ selectedInvestor.name }}
                                    </h3>
                                    <span class="text-muted-foreground text-xs font-normal">
                                        @{{ selectedInvestor.username }}
                                    </span>
                                    <span
                                        v-if="selectedInvestor.role === 'affiliator'"
                                        class="border-primary-500/30 bg-primary-500/10 text-primary-700 dark:text-primary-300 inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold"
                                    >
                                        Affiliator
                                    </span>
                                </div>
                                <p class="text-muted-foreground text-xs">
                                    {{ selectedInvestor.email }} &bull; {{ selectedInvestor.phone }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Baris Bawah: Grid Atribut Detail 3 Kolom Responsif -->
                    <div class="grid grid-cols-1 gap-4 pt-4 text-xs md:grid-cols-3">
                        <!-- Kolom 1: Data Identitas Pribadi -->
                        <div class="border-border/60 bg-muted/20 space-y-2 rounded-lg border p-3">
                            <div class="text-foreground flex items-center gap-1.5 font-bold">
                                <CreditCard
                                    class="text-primary-600 dark:text-primary-400 h-3.5 w-3.5"
                                />
                                <span>Identitas Pribadi</span>
                            </div>
                            <div class="space-y-1">
                                <div>
                                    <span
                                        class="text-muted-foreground block text-[10px] font-semibold uppercase"
                                        >NIK</span
                                    >
                                    <p class="text-foreground font-mono text-xs">
                                        {{ selectedInvestor.nik || '-' }}
                                    </p>
                                </div>
                                <div>
                                    <span
                                        class="text-muted-foreground block text-[10px] font-semibold uppercase"
                                        >Kelahiran</span
                                    >
                                    <p class="text-foreground text-xs">
                                        {{
                                            [
                                                selectedInvestor.tempat_lahir,
                                                selectedInvestor.tanggal_lahir,
                                            ]
                                                .filter(Boolean)
                                                .join(', ') || '-'
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <span
                                        class="text-muted-foreground block text-[10px] font-semibold uppercase"
                                        >Pekerjaan</span
                                    >
                                    <p class="text-foreground text-xs">
                                        {{ selectedInvestor.pekerjaan || '-' }}
                                    </p>
                                </div>
                                <div>
                                    <span
                                        class="text-muted-foreground block text-[10px] font-semibold uppercase"
                                        >Alamat</span
                                    >
                                    <p class="text-foreground text-xs">
                                        {{ selectedInvestor.alamat || '-' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Kolom 2: Kontak Darurat & Ahli Waris -->
                        <div class="border-border/60 bg-muted/20 space-y-2 rounded-lg border p-3">
                            <div class="text-foreground flex items-center gap-1.5 font-bold">
                                <Users class="text-primary-600 dark:text-primary-400 h-3.5 w-3.5" />
                                <span>Kontak Darurat / Ahli Waris</span>
                            </div>
                            <div v-if="selectedInvestor.ahli_waris" class="space-y-1">
                                <div>
                                    <span
                                        class="text-muted-foreground block text-[10px] font-semibold uppercase"
                                        >Nama Lengkap</span
                                    >
                                    <p class="text-foreground text-xs">
                                        {{ selectedInvestor.ahli_waris.nama }}
                                    </p>
                                </div>
                                <div>
                                    <span
                                        class="text-muted-foreground block text-[10px] font-semibold uppercase"
                                        >Hubungan Keluarga</span
                                    >
                                    <p class="text-foreground text-xs">
                                        {{ selectedInvestor.ahli_waris.hubungan_keluarga }}
                                    </p>
                                </div>
                                <div>
                                    <span
                                        class="text-muted-foreground block text-[10px] font-semibold uppercase"
                                        >No. Telepon / WA</span
                                    >
                                    <p class="text-foreground font-mono text-xs">
                                        {{ selectedInvestor.ahli_waris.no_hp }}
                                    </p>
                                </div>
                                <div>
                                    <span
                                        class="text-muted-foreground block text-[10px] font-semibold uppercase"
                                        >Alamat</span
                                    >
                                    <p class="text-foreground text-xs">
                                        {{ selectedInvestor.ahli_waris.alamat || '-' }}
                                    </p>
                                </div>
                            </div>
                            <p v-else class="text-muted-foreground py-2 text-xs italic">
                                Data ahli waris belum dilengkapi
                            </p>
                        </div>

                        <!-- Kolom 3: Registrasi, Legalitas & Referral -->
                        <div class="border-border/60 bg-muted/20 space-y-2 rounded-lg border p-3">
                            <div class="text-foreground flex items-center gap-1.5 font-bold">
                                <FileText
                                    class="text-primary-600 dark:text-primary-400 h-3.5 w-3.5"
                                />
                                <span>Pendaftaran</span>
                            </div>
                            <div class="space-y-1">
                                <div>
                                    <span
                                        class="text-muted-foreground block text-[10px] font-semibold uppercase"
                                        >Waktu Registrasi</span
                                    >
                                    <p class="text-foreground text-xs">
                                        {{ selectedInvestor.created_at_date }}
                                        {{ selectedInvestor.created_at_time }}
                                    </p>
                                </div>
                                <div>
                                    <span
                                        class="text-muted-foreground block text-[10px] font-semibold uppercase"
                                        >Referral Affiliator</span
                                    >
                                    <p
                                        v-if="selectedInvestor.upline"
                                        class="text-foreground flex items-center gap-1 text-xs"
                                    >
                                        <span
                                            >{{ selectedInvestor.upline.name }} ({{
                                                selectedInvestor.upline.referral_code
                                            }})</span
                                        >
                                    </p>
                                    <p v-else class="text -foreground text-xs italic">
                                        Tanpa Upline
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dokumen Identitas (Foto KTP) -->
                <div class="border-border bg-card space-y-4 rounded-xl border p-4 sm:p-5">
                    <div class="border-border/60 flex items-center justify-between border-b pb-3">
                        <div class="flex items-center gap-2">
                            <CreditCard class="text-primary-600 dark:text-primary-400 h-4 w-4" />
                            <h4 class="text-foreground text-xs font-bold tracking-wider uppercase">
                                Foto KTP
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
                            <span>{{
                                getStatusBadge(selectedInvestor.dokumen.ktp.status).label
                            }}</span>
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
                            <spanm>
                                <strong>Peringatan Kualitas Dokumen:</strong> <br />
                                Resolusi atau ketajaman berkas KTP terdeteksi rendah.
                            </spanm>
                        </div>

                        <!-- Previous Admin Note if revision needed -->
                        <div
                            v-if="
                                selectedInvestor.dokumen.ktp.status === 'revision_needed' &&
                                selectedInvestor.dokumen.ktp.catatan_admin
                            "
                            class="flex items-start gap-2.5 rounded-xl border border-amber-500/30 bg-amber-500/10 p-3 text-xs text-amber-900 dark:text-amber-200"
                        >
                            <AlertTriangle class="mt-0.5 h-4 w-4 shrink-0" />
                            <div>
                                <strong class="block">Catatan Revisi Saat Ini:</strong>
                                <p class="mt-0.5">
                                    {{ selectedInvestor.dokumen.ktp.catatan_admin }}
                                </p>
                            </div>
                        </div>

                        <!-- Previous Approval Info if already approved -->
                        <div
                            v-if="
                                selectedInvestor.dokumen.ktp.status === 'approved' &&
                                selectedInvestor.dokumen.ktp.verified_at
                            "
                            class="flex items-center gap-2.5 rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-3 text-xs text-emerald-800 dark:text-emerald-300"
                        >
                            <CheckCircle2 class="h-4 w-4 shrink-0" />
                            <span>
                                Berkas KTP ini telah disetujui pada
                                <strong>{{ selectedInvestor.dokumen.ktp.verified_at }}</strong
                                >.
                            </span>
                        </div>

                        <!-- Toolbar Kontrol Preview -->
                        <div
                            class="bg-muted/40 border-border text-muted-foreground flex items-center justify-between rounded-lg border p-2 text-xs"
                        >
                            <span class="text-foreground truncate font-semibold">
                                {{ selectedInvestor.dokumen.ktp.file_name }}
                                <span class="text-muted-foreground font-normal">
                                    ({{ formatBytes(selectedInvestor.dokumen.ktp.file_size) }})
                                </span>
                            </span>

                            <!-- Toolbar Zoom / Rotate -->
                            <div class="flex shrink-0 items-center gap-1">
                                <template
                                    v-if="
                                        selectedInvestor.dokumen.ktp.mime_type !== 'application/pdf'
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
                                v-if="selectedInvestor.dokumen.ktp.mime_type === 'application/pdf'"
                                :key="selectedInvestor.dokumen.ktp.preview_url"
                                :src="selectedInvestor.dokumen.ktp.preview_url"
                                class="h-full w-full rounded-xl"
                            ></iframe>

                            <!-- Image Viewer with Zoom & Rotate -->
                            <div
                                v-else
                                class="flex h-full w-full items-center justify-center overflow-auto p-4"
                            >
                                <img
                                    :key="selectedInvestor.dokumen.ktp.preview_url"
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
                            class="border-border/60 space-y-4 border-t pt-4"
                        >
                            <div class="space-y-1.5">
                                <label class="text-foreground block text-xs font-bold">
                                    Keputusan Verifikasi KTP
                                    <span class="text-destructive">*</span>
                                </label>
                                <div
                                    class="grid grid-cols-1 gap-3 sm:grid-cols-2"
                                    role="radiogroup"
                                    aria-label="Keputusan Verifikasi Dokumen"
                                >
                                    <button
                                        type="button"
                                        role="radio"
                                        :aria-checked="verifyForm.status === 'approved'"
                                        @click="verifyForm.status = 'approved'"
                                        :class="[
                                            'focus:ring-primary-500/30 flex items-center gap-2.5 rounded-xl border p-3 text-left text-xs font-semibold transition-colors focus:ring-2 focus:outline-none',
                                            verifyForm.status === 'approved'
                                                ? 'border-emerald-500 bg-emerald-500/10 text-emerald-800 ring-1 ring-emerald-500/20 dark:text-emerald-300'
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
                                            'focus:ring-primary-500/30 flex items-center gap-2.5 rounded-xl border p-3 text-left text-xs font-semibold transition-colors focus:ring-2 focus:outline-none',
                                            verifyForm.status === 'revision_needed'
                                                ? 'border-amber-500 bg-amber-500/10 text-amber-800 ring-1 ring-amber-500/20 dark:text-amber-300'
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

                            <!-- Catatan Instruksi Revisi (Wajib jika minta revisi) -->
                            <div v-if="verifyForm.status === 'revision_needed'" class="space-y-1.5">
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
                                <p
                                    v-if="verifyForm.errors.catatan_admin"
                                    class="text-destructive text-xs font-medium"
                                >
                                    {{ verifyForm.errors.catatan_admin }}
                                </p>
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
