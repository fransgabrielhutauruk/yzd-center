<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AppModal from '@/Components/Common/AppModal.vue';
import AppConfirmDialog from '@/Components/Common/AppConfirmDialog.vue';
import { Button } from '@/Components/ui/button';
import {
    TrendingUp,
    Calendar,
    CalendarDays,
    Image as ImageIcon,
    UploadCloud,
    Plus,
    Search,
    Trash2,
    Edit3,
    Eye,
    ChevronLeft,
    ChevronRight,
    Download,
    X,
    CheckCircle2,
    Clock,
    FileText,
    Sparkles,
    AlertCircle,
} from 'lucide-vue-next';

const props = defineProps({
    reports: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    statistics: {
        type: Object,
        default: () => ({}),
    },
});

// State Filter
const activeTab = ref(
    props.filters.is_kalender === 'true' || props.filters.is_kalender === true
        ? 'kalender'
        : props.filters.tipe || 'all'
);
const searchQuery = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || 'all');
const yearFilter = ref(props.filters.tahun || '');

const applyFilters = () => {
    const params = {};
    if (activeTab.value === 'kalender') {
        params.is_kalender = 'true';
    } else if (activeTab.value === 'harian') {
        params.tipe = 'harian';
    } else if (activeTab.value === 'bulanan') {
        params.tipe = 'bulanan';
    }

    if (searchQuery.value) params.search = searchQuery.value;
    if (statusFilter.value && statusFilter.value !== 'all') params.status = statusFilter.value;
    if (yearFilter.value) params.tahun = yearFilter.value;

    router.get(route('admin.profit_reports.index'), params, {
        preserveState: true,
        replace: true,
    });
};

const setTab = (tab) => {
    activeTab.value = tab;
    applyFilters();
};

let searchTimeout = null;
watch(searchQuery, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 400);
});

// Modal Tambah / Edit Laporan
const isFormModalOpen = ref(false);
const editingReport = ref(null);
const fileInputRef = ref(null);
const selectedFiles = ref([]);
const previewUrls = ref([]);
const existingImages = ref([]);
const deletedImageIds = ref([]);

const form = useForm({
    tipe: 'bulanan',
    judul: '',
    tanggal: '',
    bulan: new Date().getMonth() + 1,
    tahun: new Date().getFullYear(),
    profit_persen: '',
    profit_nominal_usd: '',
    is_kalender: false,
    deskripsi: '',
    status: 'published',
    images: [],
    deleted_image_ids: [],
});

const openCreateModal = () => {
    editingReport.value = null;
    form.reset();
    form.clearErrors();
    form.tipe = 'bulanan';
    form.bulan = new Date().getMonth() + 1;
    form.tahun = new Date().getFullYear();
    form.status = 'published';
    form.is_kalender = false;
    selectedFiles.value = [];
    previewUrls.value = [];
    existingImages.value = [];
    deletedImageIds.value = [];
    isFormModalOpen.value = true;
};

const openEditModal = (report) => {
    editingReport.value = report;
    form.reset();
    form.clearErrors();
    form.tipe = report.tipe;
    form.judul = report.judul;
    form.tanggal = report.tanggal || '';
    form.bulan = report.bulan || new Date().getMonth() + 1;
    form.tahun = report.tahun || new Date().getFullYear();
    form.profit_persen = report.profit_persen || '';
    form.profit_nominal_usd = report.profit_nominal_usd || '';
    form.is_kalender = Boolean(report.is_kalender);
    form.deskripsi = report.deskripsi || '';
    form.status = report.status;
    form.images = [];
    form.deleted_image_ids = [];

    selectedFiles.value = [];
    previewUrls.value = [];
    existingImages.value = [...(report.gambar || [])];
    deletedImageIds.value = [];
    isFormModalOpen.value = true;
};

const closeFormModal = () => {
    isFormModalOpen.value = false;
    previewUrls.value.forEach(url => URL.revokeObjectURL(url));
    previewUrls.value = [];
    selectedFiles.value = [];
};

// Handle File Selection
const handleFileChange = (e) => {
    const files = Array.from(e.target.files || []);
    if (!files.length) return;

    files.forEach(file => {
        if (!file.type.startsWith('image/')) return;
        selectedFiles.value.push(file);
        previewUrls.value.push(URL.createObjectURL(file));
    });

    form.images = selectedFiles.value;
};

const removeSelectedFile = (index) => {
    URL.revokeObjectURL(previewUrls.value[index]);
    previewUrls.value.splice(index, 1);
    selectedFiles.value.splice(index, 1);
    form.images = selectedFiles.value;
};

const markImageForDeletion = (imageId) => {
    deletedImageIds.value.push(imageId);
    form.deleted_image_ids = deletedImageIds.value;
    existingImages.value = existingImages.value.filter(img => img.id !== imageId);
};

const submitForm = () => {
    form.deleted_image_ids = deletedImageIds.value;

    if (editingReport.value) {
        form.post(route('admin.profit_reports.update', editingReport.value.id), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                closeFormModal();
            },
        });
    } else {
        form.post(route('admin.profit_reports.store'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                closeFormModal();
            },
        });
    }
};

// Delete Confirmation Dialog
const isDeleteDialogOpen = ref(false);
const deletingReportId = ref(null);
const isDeleting = ref(false);

const confirmDelete = (reportId) => {
    deletingReportId.value = reportId;
    isDeleteDialogOpen.value = true;
};

const executeDelete = () => {
    if (!deletingReportId.value) return;
    isDeleting.value = true;
    router.delete(route('admin.profit_reports.destroy', deletingReportId.value), {
        preserveScroll: true,
        onFinish: () => {
            isDeleting.value = false;
            isDeleteDialogOpen.value = false;
            deletingReportId.value = null;
        },
    });
};

// Lightbox Viewer
const isLightboxOpen = ref(false);
const activeGalleryImages = ref([]);
const activeImageIndex = ref(0);
const activeReportTitle = ref('');

const openLightbox = (report, startIndex = 0) => {
    if (!report.gambar || !report.gambar.length) return;
    activeGalleryImages.value = report.gambar;
    activeImageIndex.value = startIndex;
    activeReportTitle.value = report.judul;
    isLightboxOpen.value = true;
};

const closeLightbox = () => {
    isLightboxOpen.value = false;
};

const nextImage = () => {
    if (activeImageIndex.value < activeGalleryImages.value.length - 1) {
        activeImageIndex.value++;
    } else {
        activeImageIndex.value = 0;
    }
};

const prevImage = () => {
    if (activeImageIndex.value > 0) {
        activeImageIndex.value--;
    } else {
        activeGalleryImages.value.length ? (activeImageIndex.value = activeGalleryImages.value.length - 1) : 0;
    }
};

const currentImage = computed(() => {
    return activeGalleryImages.value[activeImageIndex.value] || null;
});

const monthNames = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
];
</script>

<template>
    <AdminLayout
        title="Laporan Keuntungan Trading"
        subtitle="Unggah bukti profit harian, bulanan, dan kalender trading dengan multi-foto untuk amunisi promosi affiliator."
        :breadcrumbs="[
            { label: 'Manajemen Afiliasi' },
            { label: 'Laporan Keuntungan' }
        ]"
    >
        <template #header-actions>
            <Button
                type="button"
                class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium flex items-center gap-2 shadow-xs cursor-pointer"
                @click="openCreateModal"
            >
                <Plus class="w-4 h-4" />
                Unggah Keuntungan Baru
            </Button>
        </template>

        <Head title="Laporan Keuntungan & Materi Promosi" />

        <div class="space-y-6">

            <!-- Stats Bar -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700 shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Total Diterbitkan</span>
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                            <CheckCircle2 class="w-4 h-4" />
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-slate-900 dark:text-slate-100 mt-2">
                        {{ statistics.total_published || 0 }}
                    </div>
                    <span class="text-xs text-slate-400">Materi siap promosi</span>
                </div>

                <div class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700 shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Kalender Trading</span>
                        <div class="w-8 h-8 rounded-lg bg-cyan-50 dark:bg-cyan-950/40 text-cyan-600 dark:text-cyan-400 flex items-center justify-center">
                            <CalendarDays class="w-4 h-4" />
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-slate-900 dark:text-slate-100 mt-2">
                        {{ statistics.total_kalender || 0 }}
                    </div>
                    <span class="text-xs text-slate-400">Rekap kalender visual</span>
                </div>

                <div class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700 shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Laporan Bulanan</span>
                        <div class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                            <Calendar class="w-4 h-4" />
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-slate-900 dark:text-slate-100 mt-2">
                        {{ statistics.total_bulanan || 0 }}
                    </div>
                    <span class="text-xs text-slate-400">Rekap performa bulanan</span>
                </div>

                <div class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700 shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Foto Materi</span>
                        <div class="w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                            <ImageIcon class="w-4 h-4" />
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-slate-900 dark:text-slate-100 mt-2">
                        {{ statistics.total_foto || 0 }}
                    </div>
                    <span class="text-xs text-slate-400">Tersedia untuk didownload</span>
                </div>
            </div>

            <!-- Filter Controls & Tabs -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 space-y-4">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <!-- Tab Pills -->
                    <div class="flex flex-wrap items-center gap-1.5 p-1 bg-slate-100 dark:bg-slate-900/60 rounded-xl">
                        <button
                            type="button"
                            @click="setTab('all')"
                            :class="[
                                'px-3.5 py-1.5 rounded-lg text-xs font-medium transition-colors',
                                activeTab === 'all'
                                    ? 'bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 shadow-xs'
                                    : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200'
                            ]"
                        >
                            Semua
                        </button>
                        <button
                            type="button"
                            @click="setTab('kalender')"
                            :class="[
                                'px-3.5 py-1.5 rounded-lg text-xs font-medium transition-colors flex items-center gap-1.5',
                                activeTab === 'kalender'
                                    ? 'bg-cyan-600 text-white shadow-xs'
                                    : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200'
                            ]"
                        >
                            <CalendarDays class="w-3.5 h-3.5" />
                            Kalender Profit
                        </button>
                        <button
                            type="button"
                            @click="setTab('bulanan')"
                            :class="[
                                'px-3.5 py-1.5 rounded-lg text-xs font-medium transition-colors',
                                activeTab === 'bulanan'
                                    ? 'bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 shadow-xs'
                                    : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200'
                            ]"
                        >
                            Keuntungan Bulanan
                        </button>
                        <button
                            type="button"
                            @click="setTab('harian')"
                            :class="[
                                'px-3.5 py-1.5 rounded-lg text-xs font-medium transition-colors',
                                activeTab === 'harian'
                                    ? 'bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 shadow-xs'
                                    : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200'
                            ]"
                        >
                            Keuntungan Harian
                        </button>
                    </div>

                    <!-- Search & Status Select -->
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="relative flex-1 sm:w-64">
                            <Search class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Cari judul / deskripsi..."
                                class="w-full pl-9 pr-3 py-1.5 text-xs bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:outline-hidden focus:ring-1 focus:ring-emerald-500"
                            />
                        </div>

                        <select
                            v-model="statusFilter"
                            @change="applyFilters"
                            class="px-3 py-1.5 text-xs bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-700 dark:text-slate-300 focus:outline-hidden focus:ring-1 focus:ring-emerald-500"
                        >
                            <option value="all">Semua Status</option>
                            <option value="published">Diterbitkan (Published)</option>
                            <option value="draft">Draf (Draft)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- List / Table Section -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-xs">
                <div v-if="!reports.data || reports.data.length === 0" class="text-center py-16 px-4">
                    <div class="w-16 h-16 mx-auto rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-400 mb-3">
                        <ImageIcon class="w-8 h-8" />
                    </div>
                    <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">Belum Ada Laporan Keuntungan</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-sm mx-auto">
                        Klik tombol "Unggah Keuntungan Baru" di atas untuk menambahkan rekap hasil profit trading dan foto materi promosi affiliator.
                    </p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 uppercase tracking-wider font-semibold">
                            <tr>
                                <th class="px-4 py-3">Laporan & Periode</th>
                                <th class="px-4 py-3">Performa Profit</th>
                                <th class="px-4 py-3">Foto Bukti / Kalender</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Dibuat Oleh</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                            <tr
                                v-for="report in reports.data"
                                :key="report.id"
                                class="hover:bg-slate-50/80 dark:hover:bg-slate-700/30 transition-colors"
                            >
                                <td class="px-4 py-3">
                                    <div class="flex items-start gap-2.5">
                                        <div
                                            :class="[
                                                'w-8 h-8 rounded-lg flex items-center justify-center shrink-0 mt-0.5',
                                                report.is_kalender
                                                    ? 'bg-cyan-100 dark:bg-cyan-950/60 text-cyan-600 dark:text-cyan-400'
                                                    : report.tipe === 'bulanan'
                                                    ? 'bg-indigo-100 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400'
                                                    : 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400'
                                            ]"
                                        >
                                            <CalendarDays v-if="report.is_kalender" class="w-4 h-4" />
                                            <Calendar v-else-if="report.tipe === 'bulanan'" class="w-4 h-4" />
                                            <Clock v-else class="w-4 h-4" />
                                        </div>
                                        <div>
                                            <div class="font-medium text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                                                <span>{{ report.judul }}</span>
                                                <span
                                                    v-if="report.is_kalender"
                                                    class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-cyan-100 text-cyan-800 dark:bg-cyan-900/40 dark:text-cyan-300"
                                                >
                                                    Kalender
                                                </span>
                                            </div>
                                            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 flex items-center gap-2">
                                                <span class="capitalize">{{ report.tipe }}</span>
                                                <span>•</span>
                                                <span>{{ report.periode_formatted }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-4 py-3">
                                    <div v-if="report.profit_persen !== null || report.profit_nominal_usd !== null" class="space-y-0.5">
                                        <div
                                            v-if="report.profit_persen !== null"
                                            :class="[
                                                'font-bold text-sm',
                                                parseFloat(report.profit_persen) >= 0
                                                    ? 'text-emerald-600 dark:text-emerald-400'
                                                    : 'text-rose-600 dark:text-rose-400'
                                            ]"
                                        >
                                            {{ parseFloat(report.profit_persen) >= 0 ? '+' : '' }}{{ report.profit_persen }}%
                                        </div>
                                        <div v-if="report.profit_nominal_usd !== null" class="text-[11px] text-slate-500 dark:text-slate-400">
                                            ${{ Number(report.profit_nominal_usd).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                                        </div>
                                    </div>
                                    <span v-else class="text-slate-400 text-xs">-</span>
                                </td>

                                <td class="px-4 py-3">
                                    <div v-if="report.gambar && report.gambar.length" class="flex items-center gap-2">
                                        <!-- Thumbnail First Image -->
                                        <button
                                            type="button"
                                            @click="openLightbox(report, 0)"
                                            class="relative group rounded-lg overflow-hidden border border-slate-200 dark:border-slate-700 w-12 h-12 bg-slate-100 dark:bg-slate-900 shrink-0 shadow-xs"
                                        >
                                            <img
                                                :src="report.gambar[0].url"
                                                :alt="report.judul"
                                                class="w-full h-full object-cover group-hover:scale-105 transition-transform"
                                                loading="lazy"
                                            />
                                            <div class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white">
                                                <Eye class="w-3.5 h-3.5" />
                                            </div>
                                        </button>

                                        <!-- Badge Jumlah Foto -->
                                        <button
                                            type="button"
                                            @click="openLightbox(report, 0)"
                                            class="text-[11px] font-medium text-emerald-700 dark:text-emerald-400 hover:underline flex items-center gap-1"
                                        >
                                            <ImageIcon class="w-3 h-3" />
                                            <span>{{ report.gambar.length }} Foto</span>
                                        </button>
                                    </div>
                                    <span v-else class="text-slate-400 text-xs italic">Tanpa foto</span>
                                </td>

                                <td class="px-4 py-3">
                                    <span
                                        :class="[
                                            'inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium',
                                            report.status === 'published'
                                                ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300'
                                                : 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300'
                                        ]"
                                    >
                                        {{ report.status === 'published' ? 'Diterbitkan' : 'Draf' }}
                                    </span>
                                </td>

                                <td class="px-4 py-3 text-slate-500 dark:text-slate-400 text-[11px]">
                                    <div>{{ report.creator?.name || 'Administrator' }}</div>
                                    <div class="text-[10px] text-slate-400">{{ new Date(report.created_at).toLocaleDateString('id-ID') }}</div>
                                </td>

                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <Button
                                            v-if="report.gambar && report.gambar.length"
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            class="h-7 w-7 p-0 text-slate-600 dark:text-slate-400 hover:text-emerald-600"
                                            title="Lihat Galeri Foto"
                                            @click="openLightbox(report, 0)"
                                        >
                                            <Eye class="w-3.5 h-3.5" />
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            class="h-7 w-7 p-0 text-slate-600 dark:text-slate-400 hover:text-indigo-600"
                                            title="Ubah Laporan"
                                            @click="openEditModal(report)"
                                        >
                                            <Edit3 class="w-3.5 h-3.5" />
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            class="h-7 w-7 p-0 text-rose-600 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/40"
                                            title="Hapus Laporan"
                                            @click="confirmDelete(report.id)"
                                        >
                                            <Trash2 class="w-3.5 h-3.5" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div
                    v-if="reports.links && reports.links.length > 3"
                    class="px-4 py-3 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between"
                >
                    <div class="text-xs text-slate-500 dark:text-slate-400">
                        Menampilkan {{ reports.from || 0 }} - {{ reports.to || 0 }} dari {{ reports.total || 0 }} laporan
                    </div>
                    <div class="flex items-center gap-1">
                        <button
                            v-for="(link, i) in reports.links"
                            :key="i"
                            type="button"
                            :disabled="!link.url || link.active"
                            @click="router.get(link.url, {}, { preserveState: true })"
                            v-html="link.label"
                            :class="[
                                'px-2.5 py-1 text-xs rounded transition-colors',
                                link.active
                                    ? 'bg-emerald-600 text-white font-medium'
                                    : link.url
                                    ? 'bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-200'
                                    : 'text-slate-300 dark:text-slate-600 cursor-not-allowed'
                            ]"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Form Unggah / Edit Laporan Keuntungan -->
        <AppModal
            :show="isFormModalOpen"
            :title="editingReport ? 'Perbarui Laporan Keuntungan' : 'Unggah Keuntungan Trading Baru'"
            max-width="2xl"
            @close="closeFormModal"
        >
            <form @submit.prevent="submitForm" class="space-y-4">
                <!-- Tipe Switcher & Kalender Checkbox -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-3 bg-slate-50 dark:bg-slate-900/50 rounded-xl border border-slate-200 dark:border-slate-700">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                            Tipe Laporan
                        </label>
                        <div class="flex items-center gap-2">
                            <label class="flex items-center gap-2 text-xs font-medium text-slate-700 dark:text-slate-300 cursor-pointer">
                                <input
                                    type="radio"
                                    value="bulanan"
                                    v-model="form.tipe"
                                    class="text-emerald-600 focus:ring-emerald-500"
                                />
                                <span>Bulanan</span>
                            </label>
                            <label class="flex items-center gap-2 text-xs font-medium text-slate-700 dark:text-slate-300 cursor-pointer ml-3">
                                <input
                                    type="radio"
                                    value="harian"
                                    v-model="form.tipe"
                                    class="text-emerald-600 focus:ring-emerald-500"
                                />
                                <span>Harian</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                            Format Materi
                        </label>
                        <label class="flex items-center gap-2 text-xs text-slate-700 dark:text-slate-300 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="form.is_kalender"
                                class="rounded text-cyan-600 focus:ring-cyan-500 border-slate-300"
                            />
                            <span class="font-medium flex items-center gap-1">
                                <CalendarDays class="w-3.5 h-3.5 text-cyan-600" />
                                Format Kalender Profit (Myfxbook/MT4)
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Judul Laporan -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Judul Laporan / Banner <span class="text-rose-500">*</span>
                    </label>
                    <input
                        v-model="form.judul"
                        type="text"
                        placeholder="Contoh: Kalender Profit Januari 2026 (+18.4%) atau Profit Harian 25 Sept 2026"
                        class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:outline-hidden focus:ring-1 focus:ring-emerald-500"
                    />
                    <div v-if="form.errors.judul" class="text-rose-500 text-[11px] mt-1">{{ form.errors.judul }}</div>
                </div>

                <!-- Input Tanggal (Harian) vs Bulan/Tahun (Bulanan) -->
                <div v-if="form.tipe === 'harian'" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Tanggal Transaksi <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model="form.tanggal"
                            type="date"
                            class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:outline-hidden focus:ring-1 focus:ring-emerald-500"
                        />
                        <div v-if="form.errors.tanggal" class="text-rose-500 text-[11px] mt-1">{{ form.errors.tanggal }}</div>
                    </div>
                </div>

                <div v-else class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Bulan <span class="text-rose-500">*</span>
                        </label>
                        <select
                            v-model="form.bulan"
                            class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:outline-hidden focus:ring-1 focus:ring-emerald-500"
                        >
                            <option v-for="(name, index) in monthNames" :key="index + 1" :value="index + 1">
                                {{ name }}
                            </option>
                        </select>
                        <div v-if="form.errors.bulan" class="text-rose-500 text-[11px] mt-1">{{ form.errors.bulan }}</div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Tahun <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model.number="form.tahun"
                            type="number"
                            min="2020"
                            max="2050"
                            class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:outline-hidden focus:ring-1 focus:ring-emerald-500"
                        />
                        <div v-if="form.errors.tahun" class="text-rose-500 text-[11px] mt-1">{{ form.errors.tahun }}</div>
                    </div>
                </div>

                <!-- Profit Persen & Nominal USD -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Pertumbuhan Profit (%) <span class="text-slate-400 font-normal">(opsional)</span>
                        </label>
                        <div class="relative">
                            <input
                                v-model="form.profit_persen"
                                type="number"
                                step="0.01"
                                placeholder="Contoh: 18.40"
                                class="w-full pl-3 pr-7 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:outline-hidden focus:ring-1 focus:ring-emerald-500"
                            />
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">%</span>
                        </div>
                        <div v-if="form.errors.profit_persen" class="text-rose-500 text-[11px] mt-1">{{ form.errors.profit_persen }}</div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Nominal Profit ($ USD) <span class="text-slate-400 font-normal">(opsional)</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">$</span>
                            <input
                                v-model="form.profit_nominal_usd"
                                type="number"
                                step="0.01"
                                placeholder="Contoh: 1840.50"
                                class="w-full pl-7 pr-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:outline-hidden focus:ring-1 focus:ring-emerald-500"
                            />
                        </div>
                        <div v-if="form.errors.profit_nominal_usd" class="text-rose-500 text-[11px] mt-1">{{ form.errors.profit_nominal_usd }}</div>
                    </div>
                </div>

                <!-- Multi-Image Upload Area (Bisa upload lebih dari 1 foto) -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Unggah Bukti / Screenshot Kalender & Profit <span class="text-emerald-600 font-medium">(Bisa lebih dari 1 foto)</span>
                    </label>

                    <div
                        @click="$refs.fileInputRef.click()"
                        class="border-2 border-dashed border-slate-300 dark:border-slate-700 hover:border-emerald-500 rounded-xl p-4 text-center cursor-pointer transition-colors bg-slate-50/50 dark:bg-slate-900/30 group"
                    >
                        <UploadCloud class="w-8 h-8 mx-auto text-slate-400 group-hover:text-emerald-500 transition-colors mb-2" />
                        <p class="text-xs font-medium text-slate-700 dark:text-slate-300">
                            Klik di sini untuk memilih 1 atau beberapa foto sekaligus
                        </p>
                        <p class="text-[11px] text-slate-400 mt-0.5">
                            Mendukung JPG, PNG, WEBP (maks. 10 MB per berkas).
                        </p>
                    </div>

                    <input
                        ref="fileInputRef"
                        type="file"
                        multiple
                        accept="image/png,image/jpeg,image/webp"
                        class="hidden"
                        @change="handleFileChange"
                    />

                    <!-- Existing Images in Edit Mode -->
                    <div v-if="existingImages.length" class="mt-3 space-y-1.5">
                        <span class="text-[11px] font-semibold text-slate-600 dark:text-slate-400">Foto Tersimpan:</span>
                        <div class="grid grid-cols-3 sm:grid-cols-4 gap-2.5">
                            <div
                                v-for="img in existingImages"
                                :key="img.id"
                                class="relative group rounded-lg overflow-hidden border border-slate-200 dark:border-slate-700 bg-slate-100 aspect-square"
                            >
                                <img :src="img.url" class="w-full h-full object-cover" />
                                <button
                                    type="button"
                                    @click="markImageForDeletion(img.id)"
                                    class="absolute top-1 right-1 p-1 rounded-full bg-rose-600 text-white shadow-xs opacity-90 hover:opacity-100 transition-opacity"
                                    title="Hapus foto ini"
                                >
                                    <X class="w-3 h-3" />
                                </button>
                                <span class="absolute bottom-0 inset-x-0 bg-black/60 text-[9px] text-white px-1 py-0.5 truncate text-center">
                                    {{ img.file_name }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- New Selected Previews -->
                    <div v-if="previewUrls.length" class="mt-3 space-y-1.5">
                        <span class="text-[11px] font-semibold text-emerald-600 dark:text-emerald-400">
                            Foto Baru yang Akan Diunggah ({{ previewUrls.length }} Foto):
                        </span>
                        <div class="grid grid-cols-3 sm:grid-cols-4 gap-2.5">
                            <div
                                v-for="(url, index) in previewUrls"
                                :key="index"
                                class="relative group rounded-lg overflow-hidden border border-emerald-300 dark:border-emerald-700 bg-slate-100 aspect-square"
                            >
                                <img :src="url" class="w-full h-full object-cover" />
                                <button
                                    type="button"
                                    @click="removeSelectedFile(index)"
                                    class="absolute top-1 right-1 p-1 rounded-full bg-slate-800/80 text-white hover:bg-rose-600 transition-colors"
                                    title="Batal pilih"
                                >
                                    <X class="w-3 h-3" />
                                </button>
                                <span class="absolute bottom-0 inset-x-0 bg-black/60 text-[9px] text-white px-1 py-0.5 truncate text-center">
                                    {{ selectedFiles[index]?.name }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Teks Deskripsi & Rekomendasi Caption Promosi -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Deskripsi / Template Caption Promosi untuk Affiliator
                    </label>
                    <textarea
                        v-model="form.deskripsi"
                        rows="3"
                        placeholder="Tuliskan catatan pasar, ringkasan pair trading, atau caption promosi menarik yang siap disalin oleh affiliator..."
                        class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:outline-hidden focus:ring-1 focus:ring-emerald-500"
                    ></textarea>
                </div>

                <!-- Status Publikasi -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Status Publikasi
                    </label>
                    <select
                        v-model="form.status"
                        class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:outline-hidden focus:ring-1 focus:ring-emerald-500"
                    >
                        <option value="published">Diterbitkan (Langsung tampil di menu Affiliator)</option>
                        <option value="draft">Draf (Hanya Admin yang dapat melihat)</option>
                    </select>
                </div>

                <!-- Modal Actions -->
                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200 dark:border-slate-700">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="closeFormModal"
                        :disabled="form.processing"
                    >
                        Batal
                    </Button>
                    <Button
                        type="submit"
                        size="sm"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium shadow-xs"
                        :disabled="form.processing"
                    >
                        <span v-if="form.processing">Menyimpan...</span>
                        <span v-else>{{ editingReport ? 'Simpan Perubahan' : 'Terbitkan Sekarang' }}</span>
                    </Button>
                </div>
            </form>
        </AppModal>

        <!-- Lightbox Modal untuk Preview Foto Resolusi Penuh -->
        <AppModal
            :show="isLightboxOpen"
            :title="activeReportTitle"
            max-width="3xl"
            @close="closeLightbox"
        >
            <div v-if="currentImage" class="space-y-4">
                <div class="relative bg-slate-950 rounded-xl overflow-hidden flex items-center justify-center min-h-[300px] max-h-[65vh]">
                    <img
                        :src="currentImage.url"
                        :alt="activeReportTitle"
                        class="max-h-[65vh] w-auto max-w-full object-contain mx-auto"
                    />

                    <!-- Prev/Next Controls if more than 1 image -->
                    <button
                        v-if="activeGalleryImages.length > 1"
                        type="button"
                        @click="prevImage"
                        class="absolute left-2 top-1/2 -translate-y-1/2 p-2 rounded-full bg-black/60 text-white hover:bg-black/90 transition-colors"
                        title="Foto Sebelumnya"
                    >
                        <ChevronLeft class="w-5 h-5" />
                    </button>
                    <button
                        v-if="activeGalleryImages.length > 1"
                        type="button"
                        @click="nextImage"
                        class="absolute right-2 top-1/2 -translate-y-1/2 p-2 rounded-full bg-black/60 text-white hover:bg-black/90 transition-colors"
                        title="Foto Berikutnya"
                    >
                        <ChevronRight class="w-5 h-5" />
                    </button>

                    <!-- Image Counter Badge -->
                    <div class="absolute bottom-2 left-2 px-2.5 py-1 rounded-md bg-black/70 text-white text-[11px] font-medium">
                        Foto {{ activeImageIndex + 1 }} dari {{ activeGalleryImages.length }}
                    </div>
                </div>

                <div class="flex items-center justify-between pt-2 border-t border-slate-200 dark:border-slate-700">
                    <div class="text-xs text-slate-500 dark:text-slate-400">
                        <span class="font-medium text-slate-800 dark:text-slate-200">{{ currentImage.file_name }}</span>
                        <span class="ml-2">({{ currentImage.formatted_file_size }})</span>
                    </div>

                    <a
                        :href="currentImage.download_url"
                        target="_blank"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs transition-colors"
                    >
                        <Download class="w-3.5 h-3.5" />
                        Unduh Foto Ini
                    </a>
                </div>
            </div>
        </AppModal>

        <!-- Dialog Konfirmasi Hapus Laporan -->
        <AppConfirmDialog
            :show="isDeleteDialogOpen"
            title="Hapus Laporan Keuntungan?"
            message="Apakah Anda yakin ingin menghapus laporan keuntungan ini beserta seluruh foto berkasnya? Tindakan ini tidak dapat dibatalkan."
            confirm-text="Ya, Hapus Laporan"
            cancel-text="Batal"
            type="danger"
            :loading="isDeleting"
            @confirm="executeDelete"
            @cancel="isDeleteDialogOpen = false"
        />
    </AdminLayout>
</template>
