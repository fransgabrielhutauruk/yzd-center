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
    FileCheck,
    CheckCircle2,
    Clock,
    AlertTriangle,
    XCircle,
    User,
    Users,
    Eye,
    Shield,
    CreditCard,
    Phone,
    MapPin,
    Mail,
    FileText,
    AlertCircle,
    Loader2,
    RotateCw,
    ZoomIn,
    ZoomOut,
    ExternalLink,
    Check,
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
    { key: 'name', label: 'Investor', sortable: true },
    { key: 'nik', label: 'NIK & Kontak' },
    { key: 'ahli_waris', label: 'Ahli Waris' },
    { key: 'dokumen_ktp', label: 'Foto KTP' },
    { key: 'dokumen_perjanjian', label: 'Surat Perjanjian' },
    { key: 'status', label: 'Status Akun', sortable: true },
    { key: 'created_at', label: 'Terdaftar', sortable: true },
    { key: 'actions', label: 'Aksi', align: 'right' },
];

// State Modal Detail & Verifikasi
const isVerifyModalOpen = ref(false);
const selectedInvestor = ref(null);
const activeDocumentTab = ref('ktp'); // 'ktp' | 'perjanjian'

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
    status: 'approved',
    catatan_admin: '',
});

const openVerifyModal = (investor) => {
    selectedInvestor.value = investor;
    activeDocumentTab.value = 'ktp';
    resetImageTransform();
    initDocForm('ktp');
    isVerifyModalOpen.value = true;
};

const closeVerifyModal = () => {
    isVerifyModalOpen.value = false;
    selectedInvestor.value = null;
};

const initDocForm = (docType) => {
    if (!selectedInvestor.value) return;
    const doc = selectedInvestor.value.dokumen?.[docType];
    if (doc) {
        verifyForm.status = doc.status === 'pending' ? 'approved' : doc.status;
        verifyForm.catatan_admin = doc.catatan_admin || '';
    } else {
        verifyForm.status = 'approved';
        verifyForm.catatan_admin = '';
    }
};

const selectTab = (tab) => {
    activeDocumentTab.value = tab;
    resetImageTransform();
    initDocForm(tab);
};

const submitVerification = (docType) => {
    const doc = selectedInvestor.value?.dokumen?.[docType];
    if (!doc) {
        toast.error('Dokumen tidak ditemukan.');
        return;
    }

    if (verifyForm.status === 'revision_needed' && !verifyForm.catatan_admin.trim()) {
        toast.error(
            'Harap isi catatan revisi agar investor mengetahui bagian yang perlu diperbaiki.'
        );
        return;
    }

    if (verifyForm.status === 'rejected' && !verifyForm.catatan_admin.trim()) {
        toast.error('Harap isi alasan penolakan dokumen.');
        return;
    }

    verifyForm.post(`/admin/kyc/documents/${doc.id}/verify`, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(`Status ${docType.toUpperCase()} berhasil diperbarui!`);
            // Update data lokal pada modal
            if (selectedInvestor.value?.dokumen?.[docType]) {
                selectedInvestor.value.dokumen[docType].status = verifyForm.status;
                selectedInvestor.value.dokumen[docType].catatan_admin = verifyForm.catatan_admin;
            }
        },
    });
};

const getStatusBadge = (status) => {
    switch (status) {
        case 'active':
        case 'approved':
            return {
                label: status === 'active' ? 'Aktif' : 'Disetujui',
                bg: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/30',
                icon: CheckCircle2,
            };
        case 'revision_needed':
            return {
                label: 'Perlu Revisi',
                bg: 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/30',
                icon: AlertTriangle,
            };
        case 'suspended':
        case 'rejected':
            return {
                label: status === 'suspended' ? 'Ditangguhkan' : 'Ditolak',
                bg: 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/30',
                icon: XCircle,
            };
        case 'pending':
        default:
            return {
                label: 'Menunggu Validasi',
                bg: 'bg-sky-500/10 text-sky-600 dark:text-sky-400 border-sky-500/30',
                icon: Clock,
            };
    }
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
                        <span class="bg-primary-500/10 text-primary-600 rounded-xl p-2">
                            <FileCheck class="h-5 w-5" />
                        </span>
                        <h1 class="text-foreground text-xl font-bold tracking-tight">
                            Verifikasi Dokumen Legalitas (KYC)
                        </h1>
                    </div>
                    <p class="text-muted-foreground pl-11 text-xs">
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
                search-placeholder="Cari investor, email, username, NIK..."
                empty-title="Tidak Ada Berkas KYC Ditemukan"
                empty-message="Belum ada pendaftaran akun atau tidak ada hasil yang cocok dengan filter pencarian."
            >
                <!-- Filter Slot: Status Filter Dropdown -->
                <template #filters="{ applyFilters }">
                    <select
                        :value="filters.status || ''"
                        @change="(e) => applyFilters({ status: e.target.value, page: 1 })"
                        class="bg-background border-input focus:ring-primary-500 rounded-lg border px-3 py-1.5 text-xs font-medium focus:ring-2 focus:outline-none"
                    >
                        <option value="">Semua Status Akun</option>
                        <option value="pending">Menunggu Verifikasi (Pending)</option>
                        <option value="active">Aktif (Approved)</option>
                        <option value="suspended">Ditangguhkan (Suspended)</option>
                        <option value="rejected">Ditolak (Rejected)</option>
                    </select>
                </template>

                <!-- Custom Column: Investor Name & Email -->
                <template #column-name="{ item }">
                    <div class="space-y-0.5">
                        <span class="text-foreground block font-bold">{{ item.name }}</span>
                        <div class="text-muted-foreground flex items-center gap-2 text-[11px]">
                            <span>@{{ item.username }}</span>
                            <span>&bull;</span>
                            <span>{{ item.email }}</span>
                        </div>
                    </div>
                </template>

                <!-- Custom Column: NIK & Phone -->
                <template #column-nik="{ item }">
                    <div class="space-y-0.5">
                        <span class="text-foreground block font-mono text-xs font-semibold">{{
                            item.nik
                        }}</span>
                        <span class="text-muted-foreground text-[11px]">{{ item.phone }}</span>
                    </div>
                </template>

                <!-- Custom Column: Ahli Waris -->
                <template #column-ahli_waris="{ item }">
                    <div v-if="item.ahli_waris" class="space-y-0.5">
                        <span class="text-foreground block font-semibold">{{
                            item.ahli_waris.nama
                        }}</span>
                        <span
                            class="bg-muted text-muted-foreground rounded px-1.5 py-0.5 text-[10px] font-medium"
                        >
                            {{ item.ahli_waris.hubungan_keluarga }}
                        </span>
                    </div>
                    <span v-else class="text-muted-foreground text-[11px] italic">-</span>
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
                            class="flex items-center gap-1 text-[10px] font-medium text-amber-600"
                        >
                            <AlertCircle class="h-3 w-3" />
                            <span>Peringatan Kualitas</span>
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
                        <div
                            v-if="item.dokumen.perjanjian.is_quality_warning"
                            class="flex items-center gap-1 text-[10px] font-medium text-amber-600"
                        >
                            <AlertCircle class="h-3 w-3" />
                            <span>Peringatan Kualitas</span>
                        </div>
                    </div>
                    <span v-else class="text-muted-foreground text-[11px] italic">Belum Ada</span>
                </template>

                <!-- Custom Column: Status Akun -->
                <template #column-status="{ item }">
                    <span
                        :class="[
                            'inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-xs font-semibold',
                            getStatusBadge(item.status).bg,
                        ]"
                    >
                        <component :is="getStatusBadge(item.status).icon" class="h-3.5 w-3.5" />
                        <span>{{ getStatusBadge(item.status).label }}</span>
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
                        <span>Tinjau Berkas</span>
                    </Button>
                </template>
            </AppDataTable>
        </div>

        <!-- ========================================== -->
        <!-- MODAL VERIFIKASI BERKAS KYC                -->
        <!-- ========================================== -->
        <AppModal
            :show="isVerifyModalOpen"
            title="Peninjauan & Verifikasi Berkas Legalitas"
            max-width="4xl"
            @close="closeVerifyModal"
        >
            <div v-if="selectedInvestor" class="max-h-[70vh] space-y-6 overflow-y-auto pr-2">
                <!-- Data Header Investor -->
                <div
                    class="bg-muted/40 border-border grid grid-cols-1 gap-4 rounded-xl border p-4 text-xs md:grid-cols-3"
                >
                    <!-- Kolom 1: Identitas -->
                    <div class="space-y-1">
                        <span
                            class="text-muted-foreground text-[11px] font-bold tracking-wider uppercase"
                            >Identitas Pengguna</span
                        >
                        <p class="text-foreground text-sm font-bold">{{ selectedInvestor.name }}</p>
                        <p class="text-muted-foreground">
                            NIK:
                            <strong class="text-foreground font-mono">{{
                                selectedInvestor.nik
                            }}</strong>
                        </p>
                        <p class="text-muted-foreground">
                            {{ selectedInvestor.email }} &bull; {{ selectedInvestor.phone }}
                        </p>
                        <p class="text-muted-foreground leading-relaxed">
                            {{ selectedInvestor.alamat }}
                        </p>
                    </div>

                    <!-- Kolom 2: Ahli Waris -->
                    <div class="space-y-1">
                        <span
                            class="text-muted-foreground text-[11px] font-bold tracking-wider uppercase"
                            >Perlindungan Ahli Waris</span
                        >
                        <template v-if="selectedInvestor.ahli_waris">
                            <p class="text-foreground text-sm font-bold">
                                {{ selectedInvestor.ahli_waris.nama }}
                            </p>
                            <p class="text-muted-foreground">
                                Hubungan:
                                <span class="text-foreground font-semibold">{{
                                    selectedInvestor.ahli_waris.hubungan_keluarga
                                }}</span>
                            </p>
                            <p class="text-muted-foreground">
                                Kontak: {{ selectedInvestor.ahli_waris.no_hp }}
                            </p>
                            <p class="text-muted-foreground leading-relaxed">
                                {{ selectedInvestor.ahli_waris.alamat }}
                            </p>
                        </template>
                        <p v-else class="text-muted-foreground italic">
                            Tidak ada data ahli waris.
                        </p>
                    </div>

                    <!-- Kolom 3: Status & Legalitas -->
                    <div class="space-y-1">
                        <span
                            class="text-muted-foreground text-[11px] font-bold tracking-wider uppercase"
                            >Audit Syarat & Ketentuan</span
                        >
                        <p class="text-muted-foreground">
                            Status Akun:
                            <span
                                :class="[
                                    'font-semibold capitalize',
                                    selectedInvestor.status === 'active'
                                        ? 'text-emerald-600'
                                        : 'text-amber-600',
                                ]"
                            >
                                {{ selectedInvestor.status }}
                            </span>
                        </p>
                        <p class="text-muted-foreground">
                            Versi T&C:
                            <strong class="text-foreground"
                                >v{{ selectedInvestor.terms_version || '1.0' }}</strong
                            >
                        </p>
                        <p class="text-muted-foreground">
                            Disetujui:
                            <span class="text-foreground">{{
                                selectedInvestor.terms_agreed_at || '-'
                            }}</span>
                        </p>
                        <p class="text-muted-foreground">
                            Terdaftar:
                            <span class="text-foreground">{{ selectedInvestor.created_at }}</span>
                        </p>
                    </div>
                </div>

                <!-- Tabs Pilihan Dokumen -->
                <div class="border-border flex items-center gap-2 border-b">
                    <button
                        type="button"
                        @click="selectTab('ktp')"
                        :class="[
                            'flex items-center gap-2 border-b-2 px-4 py-2.5 text-xs font-bold transition-colors',
                            activeDocumentTab === 'ktp'
                                ? 'border-primary-600 text-primary-600 dark:text-primary-400'
                                : 'text-muted-foreground hover:text-foreground border-transparent',
                        ]"
                    >
                        <CreditCard class="h-4 w-4" />
                        <span>1. Foto KTP</span>
                        <span
                            v-if="selectedInvestor.dokumen?.ktp"
                            :class="[
                                'rounded-full px-1.5 py-0.5 text-[10px] font-semibold',
                                getStatusBadge(selectedInvestor.dokumen.ktp.status).bg,
                            ]"
                        >
                            {{ getStatusBadge(selectedInvestor.dokumen.ktp.status).label }}
                        </span>
                    </button>

                    <button
                        type="button"
                        @click="selectTab('perjanjian')"
                        :class="[
                            'flex items-center gap-2 border-b-2 px-4 py-2.5 text-xs font-bold transition-colors',
                            activeDocumentTab === 'perjanjian'
                                ? 'border-primary-600 text-primary-600 dark:text-primary-400'
                                : 'text-muted-foreground hover:text-foreground border-transparent',
                        ]"
                    >
                        <FileText class="h-4 w-4" />
                        <span>2. Surat Perjanjian Bermaterai</span>
                        <span
                            v-if="selectedInvestor.dokumen?.perjanjian"
                            :class="[
                                'rounded-full px-1.5 py-0.5 text-[10px] font-semibold',
                                getStatusBadge(selectedInvestor.dokumen.perjanjian.status).bg,
                            ]"
                        >
                            {{ getStatusBadge(selectedInvestor.dokumen.perjanjian.status).label }}
                        </span>
                    </button>
                </div>

                <!-- Konten Tab Aktif -->
                <div v-if="selectedInvestor.dokumen?.[activeDocumentTab]" class="space-y-6">
                    <!-- Heuristic Quality Warning Alert -->
                    <div
                        v-if="selectedInvestor.dokumen[activeDocumentTab].is_quality_warning"
                        class="flex items-center gap-2 rounded-xl border border-amber-500/30 bg-amber-500/10 p-3 text-xs text-amber-700 dark:text-amber-300"
                    >
                        <AlertTriangle class="h-4 w-4 shrink-0" />
                        <span>
                            <strong>Peringatan Algoritma Kualitas Berkas:</strong> Dokumen ini
                            memiliki ketajaman rendah atau resolusi di bawah standar rekomendasi.
                            Mohon periksa keterbacaan teks secara manual.
                        </span>
                    </div>

                    <!-- Area Viewer Dokumen + Toolbar Preview -->
                    <div class="space-y-2">
                        <!-- Toolbar Kontrol Berkas (Gambar & Dokumen PDF) -->
                        <div
                            class="bg-muted/40 border-border text-muted-foreground flex items-center justify-between rounded-lg border p-2 text-xs"
                        >
                            <span class="text-foreground font-semibold">
                                Pratinjau Dokumen ({{
                                    formatBytes(
                                        selectedInvestor.dokumen[activeDocumentTab].file_size
                                    )
                                }})
                            </span>
                            <div class="flex items-center gap-2">
                                <!-- Kontrol Zoom & Rotate (Khusus Gambar KTP) -->
                                <template
                                    v-if="
                                        selectedInvestor.dokumen[activeDocumentTab].mime_type !==
                                        'application/pdf'
                                    "
                                >
                                    <button
                                        type="button"
                                        @click="zoomIn"
                                        class="hover:bg-background text-foreground rounded p-1"
                                        title="Perbesar"
                                    >
                                        <ZoomIn class="h-4 w-4" />
                                    </button>
                                    <button
                                        type="button"
                                        @click="zoomOut"
                                        class="hover:bg-background text-foreground rounded p-1"
                                        title="Perkecil"
                                    >
                                        <ZoomOut class="h-4 w-4" />
                                    </button>
                                    <button
                                        type="button"
                                        @click="rotateImage"
                                        class="hover:bg-background text-foreground rounded p-1"
                                        title="Putar 90 Derajat"
                                    >
                                        <RotateCw class="h-4 w-4" />
                                    </button>
                                    <a
                                        :href="
                                            selectedInvestor.dokumen[activeDocumentTab].preview_url
                                        "
                                        target="_blank"
                                        class="hover:bg-background text-foreground flex items-center gap-1 rounded p-1 text-[11px]"
                                        title="Buka Tab Baru"
                                    >
                                        <ExternalLink class="h-3.5 w-3.5" />
                                    </a>
                                </template>

                                <!-- Button Pratinjau Khusus Dokumen (PDF) -->
                                <template v-else>
                                    <a
                                        :href="
                                            selectedInvestor.dokumen[activeDocumentTab].preview_url
                                        "
                                        target="_blank"
                                        class="hover:bg-background text-foreground border-border flex items-center gap-1.5 rounded border px-2.5 py-1 text-[11px] font-medium transition-colors"
                                        title="Buka Pratinjau Dokumen di Tab Baru"
                                    >
                                        <ExternalLink class="h-3.5 w-3.5" />
                                        <span>Pratinjau Dokumen</span>
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
                                    :src="selectedInvestor.dokumen[activeDocumentTab].preview_url"
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
                        class="border-border space-y-4 border-t pt-2"
                    >
                        <div class="space-y-1.5">
                            <label class="text-foreground block text-xs font-bold">
                                Keputusan Verifikasi Dokumen <span class="text-destructive">*</span>
                            </label>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                <label
                                    :class="[
                                        'flex cursor-pointer items-center gap-2 rounded-xl border p-3 text-xs font-semibold transition-all',
                                        verifyForm.status === 'approved'
                                            ? 'border-emerald-500 bg-emerald-500/10 text-emerald-700 ring-2 ring-emerald-500/20 dark:text-emerald-300'
                                            : 'border-border bg-card hover:bg-muted/40 text-foreground',
                                    ]"
                                >
                                    <input
                                        v-model="verifyForm.status"
                                        type="radio"
                                        value="approved"
                                        class="sr-only"
                                    />
                                    <CheckCircle2 class="h-4 w-4 shrink-0 text-emerald-600" />
                                    <span>Setujui (Valid)</span>
                                </label>

                                <label
                                    :class="[
                                        'flex cursor-pointer items-center gap-2 rounded-xl border p-3 text-xs font-semibold transition-all',
                                        verifyForm.status === 'revision_needed'
                                            ? 'border-amber-500 bg-amber-500/10 text-amber-700 ring-2 ring-amber-500/20 dark:text-amber-300'
                                            : 'border-border bg-card hover:bg-muted/40 text-foreground',
                                    ]"
                                >
                                    <input
                                        v-model="verifyForm.status"
                                        type="radio"
                                        value="revision_needed"
                                        class="sr-only"
                                    />
                                    <AlertTriangle class="h-4 w-4 shrink-0 text-amber-600" />
                                    <span>Minta Revisi</span>
                                </label>

                                <label
                                    :class="[
                                        'flex cursor-pointer items-center gap-2 rounded-xl border p-3 text-xs font-semibold transition-all',
                                        verifyForm.status === 'rejected'
                                            ? 'border-rose-500 bg-rose-500/10 text-rose-700 ring-2 ring-rose-500/20 dark:text-rose-300'
                                            : 'border-border bg-card hover:bg-muted/40 text-foreground',
                                    ]"
                                >
                                    <input
                                        v-model="verifyForm.status"
                                        type="radio"
                                        value="rejected"
                                        class="sr-only"
                                    />
                                    <XCircle class="h-4 w-4 shrink-0 text-rose-600" />
                                    <span>Tolak Berkas</span>
                                </label>
                            </div>
                        </div>

                        <!-- Catatan Verifikator -->
                        <div class="space-y-1.5">
                            <label
                                for="catatan_admin"
                                class="text-foreground block text-xs font-semibold"
                            >
                                Catatan / Instruksi Verifikasi
                                <span
                                    v-if="
                                        verifyForm.status === 'revision_needed' ||
                                        verifyForm.status === 'rejected'
                                    "
                                    class="text-destructive"
                                    >*</span
                                >
                                <span v-else class="text-muted-foreground font-normal"
                                    >(Opsional)</span
                                >
                            </label>
                            <textarea
                                id="catatan_admin"
                                v-model="verifyForm.catatan_admin"
                                rows="2"
                                placeholder="Contoh: Foto KTP sedikit terpotong pada bagian NIK, mohon unggah ulang foto yang menampilkan seluruh kartu."
                                class="bg-background border-input focus:ring-primary-500 w-full resize-none rounded-lg border px-3 py-2 text-xs transition-all focus:ring-2 focus:outline-none"
                            ></textarea>
                            <p class="text-muted-foreground text-[11px]">
                                Catatan ini akan ditampilkan langsung kepada investor di halaman
                                status KYC mereka.
                            </p>
                        </div>

                        <!-- Submit Verification Button -->
                        <div class="flex items-center justify-end gap-3 pt-2">
                            <Button
                                type="submit"
                                :disabled="verifyForm.processing"
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

            <template #footer>
                <div class="flex w-full items-center justify-between">
                    <span class="text-muted-foreground text-xs">
                        Akun otomatis aktif setelah kedua berkas KTP dan Perjanjian disetujui.
                    </span>
                    <Button
                        type="button"
                        variant="secondary"
                        size="sm"
                        class="text-xs"
                        @click="closeVerifyModal"
                    >
                        Tutup Modal
                    </Button>
                </div>
            </template>
        </AppModal>
    </AdminLayout>
</template>
