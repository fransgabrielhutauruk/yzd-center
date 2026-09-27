<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import InvestorLayout from '@/Layouts/InvestorLayout.vue';
import AppModal from '@/Components/Common/AppModal.vue';
import { Button } from '@/Components/ui/button';
import {
    TrendingUp,
    Calendar,
    CalendarDays,
    Image as ImageIcon,
    Download,
    Copy,
    Check,
    Share2,
    Eye,
    ChevronLeft,
    ChevronRight,
    Search,
    Clock,
    Sparkles,
    Link2,
    MessageCircle,
    ExternalLink,
} from 'lucide-vue-next';
import { toast } from 'vue-sonner';

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
    referralCode: {
        type: String,
        default: '',
    },
    referralUrl: {
        type: String,
        default: '',
    },
    brokerUrl: {
        type: String,
        default: '',
    },
    isAffiliator: {
        type: Boolean,
        default: false,
    },
});

// State Filter
const activeTab = ref(
    props.filters.is_kalender === 'true' || props.filters.is_kalender === true
        ? 'kalender'
        : props.filters.tipe || 'all'
);
const searchQuery = ref(props.filters.search || '');
const yearFilter = ref(props.filters.tahun || '');
const monthFilter = ref(props.filters.bulan || '');

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
    if (yearFilter.value) params.tahun = yearFilter.value;
    if (monthFilter.value) params.bulan = monthFilter.value;

    router.get(route('investor.promotions.index'), params, {
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

// Salin Link Referral
const copiedReferral = ref(false);
const copyReferralLink = async () => {
    if (!props.referralUrl) return;
    try {
        await navigator.clipboard.writeText(props.referralUrl);
        copiedReferral.value = true;
        toast.success('Tautan Berhasil Disalin', {
            description: 'Tautan referral Anda siap dibagikan ke calon investor.',
        });
        setTimeout(() => {
            copiedReferral.value = false;
        }, 3000);
    } catch {
        toast.error('Gagal menyalin tautan referral.');
    }
};

// Salin Caption Promosi Lengkap per Laporan
const copiedCaptionReportId = ref(null);
const generatePromoCaption = (report) => {
    let caption = `🔥 *${report.judul}* 🔥\n\n`;

    if (report.profit_persen !== null) {
        const sign = parseFloat(report.profit_persen) >= 0 ? '+' : '';
        caption += `📈 Performa Keuntungan: *${sign}${report.profit_persen}%*\n`;
    }
    if (report.profit_nominal_usd !== null) {
        caption += `💵 Estimasi Akumulasi: *$${Number(report.profit_nominal_usd).toLocaleString('en-US', { minimumFractionDigits: 2 })}*\n`;
    }

    caption += `📅 Periode: ${report.periode_formatted}\n\n`;

    if (report.deskripsi) {
        caption += `${report.deskripsi}\n\n`;
    }

    caption += `Nikmati hasil trading otomatis bersama Master Trader profesional YZD Center tanpa perlu pusing analisa chart.\n\n`;

    if (props.referralUrl) {
        caption += `👇 *Gabung & Mulai Copytrade Sekarang:* \n${props.referralUrl}\n`;
    }

    return caption;
};

const copyCaption = async (report) => {
    const caption = generatePromoCaption(report);
    try {
        await navigator.clipboard.writeText(caption);
        copiedCaptionReportId.value = report.id;
        toast.success('Caption Promosi Disalin!', {
            description: 'Teks promosi beserta link referral Anda siap diposting di media sosial.',
        });
        setTimeout(() => {
            copiedCaptionReportId.value = null;
        }, 3000);
    } catch {
        toast.error('Gagal menyalin teks caption.');
    }
};

const shareToWhatsApp = (report) => {
    const caption = generatePromoCaption(report);
    const url = `https://api.whatsapp.com/send?text=${encodeURIComponent(caption)}`;
    window.open(url, '_blank');
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
    <InvestorLayout
        :title="isAffiliator ? 'Materi Promosi & Kalender Keuntungan' : 'Laporan Keuntungan & Kalender Profit'"
        :subtitle="isAffiliator
            ? 'Unduh bukti screenshot kalender trading dan profit nyata dari Master Trader YZD Center. Gunakan gambar dan caption otomatis dengan link referral Anda untuk menjaring investor baru!'
            : 'Pantau rekap performa profit trading harian, bulanan, dan kalender akun Master YZD Center secara terbuka dan terverifikasi.'"
        :breadcrumbs="[
            { label: 'Program Afiliasi' },
            { label: 'Materi Promosi & Profit' }
        ]"
    >
        <template #header-actions>
            <div v-if="isAffiliator && referralUrl" class="flex items-center gap-2">
                <button
                    type="button"
                    @click="copyReferralLink"
                    class="flex items-center justify-center gap-2 px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs transition-colors shadow-xs cursor-pointer min-h-[38px]"
                >
                    <Check v-if="copiedReferral" class="w-4 h-4" />
                    <Copy v-else class="w-4 h-4" />
                    <span>{{ copiedReferral ? 'Tautan Tersalin!' : 'Salin Tautan Referral' }}</span>
                </button>
            </div>
        </template>

        <Head :title="isAffiliator ? 'Materi Promosi & Laporan Keuntungan' : 'Laporan Keuntungan Trading'" />

        <div class="space-y-6 max-w-7xl mx-auto">

            <!-- Stats Bar Ringkas -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
                <div class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700 shadow-xs">
                    <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">Materi Tersedia</span>
                    <div class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-slate-100 mt-1">
                        {{ statistics.total_published || 0 }}
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700 shadow-xs">
                    <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">Kalender Profit</span>
                    <div class="text-xl sm:text-2xl font-bold text-cyan-600 dark:text-cyan-400 mt-1">
                        {{ statistics.total_kalender || 0 }}
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700 shadow-xs">
                    <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">Rekap Bulanan</span>
                    <div class="text-xl sm:text-2xl font-bold text-indigo-600 dark:text-indigo-400 mt-1">
                        {{ statistics.total_bulanan || 0 }}
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700 shadow-xs">
                    <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">Total Foto Promosi</span>
                    <div class="text-xl sm:text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1">
                        {{ statistics.total_foto || 0 }}
                    </div>
                </div>
            </div>

            <!-- Filter Controls -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 space-y-3">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
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
                            Semua Materi
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
                            Kalender Keuntungan
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

                    <!-- Search Input -->
                    <div class="relative flex-1 md:max-w-xs">
                        <Search class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari materi promosi..."
                            class="w-full pl-9 pr-3 py-1.5 text-xs bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:outline-hidden focus:ring-1 focus:ring-emerald-500"
                        />
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="!reports.data || reports.data.length === 0" class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 text-center py-16 px-4">
                <div class="w-16 h-16 mx-auto rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-400 mb-3">
                    <ImageIcon class="w-8 h-8" />
                </div>
                <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">Belum Ada Materi Promosi</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-sm mx-auto">
                    Materi keuntungan trading dan kalender profit sedang dipersiapkan oleh tim analis YZD Center. Silakan periksa kembali berkala.
                </p>
            </div>

            <!-- Grid Cards Materi Promosi -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="report in reports.data"
                    :key="report.id"
                    class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-xs hover:shadow-md transition-shadow flex flex-col"
                >
                    <!-- Media Header (Thumbnail / Slider Mini) -->
                    <div class="relative bg-slate-950 aspect-video overflow-hidden group">
                        <div v-if="report.gambar && report.gambar.length" class="w-full h-full">
                            <img
                                :src="report.gambar[0].url"
                                :alt="report.judul"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                loading="lazy"
                            />

                            <!-- Overlay Hover Action -->
                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                                <button
                                    type="button"
                                    @click="openLightbox(report, 0)"
                                    class="p-2.5 rounded-full bg-white/90 text-slate-900 hover:bg-white transition-colors shadow-md"
                                    title="Lihat Gambar Penuh"
                                >
                                    <Eye class="w-4 h-4" />
                                </button>
                                <a
                                    :href="report.gambar[0].download_url"
                                    target="_blank"
                                    class="p-2.5 rounded-full bg-emerald-600 text-white hover:bg-emerald-500 transition-colors shadow-md"
                                    title="Unduh Gambar Ini"
                                >
                                    <Download class="w-4 h-4" />
                                </a>
                            </div>

                            <!-- Badge Multi Foto -->
                            <button
                                v-if="report.gambar.length > 1"
                                type="button"
                                @click="openLightbox(report, 0)"
                                class="absolute top-3 right-3 px-2.5 py-1 rounded-full bg-black/70 hover:bg-black/90 backdrop-blur-xs text-white text-[11px] font-semibold flex items-center gap-1.5 transition-colors"
                            >
                                <ImageIcon class="w-3.5 h-3.5" />
                                <span>{{ report.gambar.length }} Foto</span>
                            </button>
                        </div>

                        <!-- Fallback jika tanpa foto -->
                        <div v-else class="w-full h-full flex flex-col items-center justify-center text-slate-500">
                            <CalendarDays v-if="report.is_kalender" class="w-12 h-12 mb-1 text-slate-600" />
                            <TrendingUp v-else class="w-12 h-12 mb-1 text-slate-600" />
                            <span class="text-xs">Laporan Tanpa Berkas Foto</span>
                        </div>

                        <!-- Badge Tipe & Kalender di Sudut Kiri Atas -->
                        <div class="absolute top-3 left-3 flex items-center gap-1.5">
                            <span
                                v-if="report.is_kalender"
                                class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-cyan-600 text-white uppercase tracking-wider shadow-sm"
                            >
                                Kalender Profit
                            </span>
                            <span
                                :class="[
                                    'inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold capitalize shadow-sm',
                                    report.tipe === 'bulanan'
                                        ? 'bg-indigo-600 text-white'
                                        : 'bg-emerald-600 text-white'
                                ]"
                            >
                                {{ report.tipe }}
                            </span>
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
                        <div class="space-y-2">
                            <!-- Judul & Tanggal -->
                            <div class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
                                <Clock class="w-3.5 h-3.5" />
                                <span>{{ report.periode_formatted }}</span>
                            </div>

                            <h3 class="font-bold text-slate-900 dark:text-slate-100 text-base leading-snug line-clamp-2">
                                {{ report.judul }}
                            </h3>

                            <!-- Performa Profit Highlight -->
                            <div
                                v-if="report.profit_persen !== null || report.profit_nominal_usd !== null"
                                class="p-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-900/50 flex items-center justify-between"
                            >
                                <div>
                                    <span class="text-[10px] uppercase font-bold text-emerald-800 dark:text-emerald-400 tracking-wider">
                                        Performa Profit
                                    </span>
                                    <div
                                        v-if="report.profit_persen !== null"
                                        class="text-xl font-black text-emerald-600 dark:text-emerald-400"
                                    >
                                        {{ parseFloat(report.profit_persen) >= 0 ? '+' : '' }}{{ report.profit_persen }}%
                                    </div>
                                </div>
                                <div v-if="report.profit_nominal_usd !== null" class="text-right">
                                    <span class="text-[10px] uppercase font-medium text-slate-500 dark:text-slate-400">
                                        Akumulasi
                                    </span>
                                    <div class="text-sm font-bold text-slate-900 dark:text-slate-100">
                                        ${{ Number(report.profit_nominal_usd).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                                    </div>
                                </div>
                            </div>

                            <!-- Deskripsi / Teks Promosi Admin -->
                            <p v-if="report.deskripsi" class="text-xs text-slate-600 dark:text-slate-300 line-clamp-3 leading-relaxed">
                                {{ report.deskripsi }}
                            </p>
                        </div>

                        <!-- Card Footer: Tombol Aksi Promosi -->
                        <div class="pt-3 border-t border-slate-100 dark:border-slate-700/60 space-y-2">
                            <div class="grid grid-cols-2 gap-2">
                                <!-- Tombol Salin Caption Promosi -->
                                <button
                                    type="button"
                                    @click="copyCaption(report)"
                                    class="inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-800 dark:text-slate-100 transition-colors"
                                    :title="'Salin teks promosi yang sudah dilengkapi link referral Anda'"
                                >
                                    <Check v-if="copiedCaptionReportId === report.id" class="w-3.5 h-3.5 text-emerald-600" />
                                    <Copy v-else class="w-3.5 h-3.5 text-slate-500 dark:text-slate-400" />
                                    <span>{{ copiedCaptionReportId === report.id ? 'Tersalin!' : 'Salin Caption' }}</span>
                                </button>

                                <!-- Tombol Bagikan ke WhatsApp -->
                                <button
                                    type="button"
                                    @click="shareToWhatsApp(report)"
                                    class="inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white transition-colors shadow-xs"
                                    title="Bagikan teks promosi langsung ke WhatsApp"
                                >
                                    <MessageCircle class="w-3.5 h-3.5" />
                                    <span>Kirim WA</span>
                                </button>
                            </div>

                            <!-- Tombol Download / Galeri Foto -->
                            <div v-if="report.gambar && report.gambar.length" class="flex items-center justify-between pt-1">
                                <button
                                    type="button"
                                    @click="openLightbox(report, 0)"
                                    class="text-[11px] font-medium text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 flex items-center gap-1"
                                >
                                    <Eye class="w-3.5 h-3.5" />
                                    <span>Lihat {{ report.gambar.length }} Foto</span>
                                </button>

                                <a
                                    :href="report.gambar[0].download_url"
                                    target="_blank"
                                    class="text-[11px] font-semibold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 flex items-center gap-1"
                                >
                                    <Download class="w-3.5 h-3.5" />
                                    <span>Unduh Foto</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div
                v-if="reports.links && reports.links.length > 3"
                class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 flex flex-col sm:flex-row items-center justify-between gap-3 shadow-xs"
            >
                <div class="text-xs text-slate-500 dark:text-slate-400">
                    Menampilkan {{ reports.from || 0 }} - {{ reports.to || 0 }} dari {{ reports.total || 0 }} materi promosi
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

        <!-- Lightbox Modal untuk Preview Foto & Download -->
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
    </InvestorLayout>
</template>
