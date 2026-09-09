<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import InvestorLayout from '@/Layouts/InvestorLayout.vue';
import AppModal from '@/Components/Common/AppModal.vue';
import { Button } from '@/Components/ui/button';
import { compressImageFile, formatBytes } from '@/lib/imageCompression';
import { toast } from 'vue-sonner';
import {
    FileCheck,
    Clock,
    AlertTriangle,
    XCircle,
    CheckCircle2,
    Shield,
    User,
    Users,
    CreditCard,
    Phone,
    MapPin,
    Mail,
    Eye,
    UploadCloud,
    Loader2,
    Sparkles,
    FileText,
    File as FileIcon,
    AlertCircle,
    Info,
    Check,
} from 'lucide-vue-next';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    ahli_waris: {
        type: Object,
        default: null,
    },
    dokumen: {
        type: Object,
        required: true,
    },
    is_kyc_approved: {
        type: Boolean,
        default: false,
    },
});

// Modal Preview Berkas
const previewModal = ref({
    isOpen: false,
    title: '',
    url: '',
    isPdf: false,
});

const openPreview = (title, url, mimeType) => {
    previewModal.value = {
        isOpen: true,
        title,
        url,
        isPdf: mimeType === 'application/pdf',
    };
};

const closePreview = () => {
    previewModal.value.isOpen = false;
};

// State Unggah Ulang KTP
const ktpForm = useForm({
    file: null,
});
const ktpCompressing = ref(false);
const ktpInfo = ref(null);

const handleKtpChange = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    ktpCompressing.value = true;
    try {
        const result = await compressImageFile(file);
        if (result) {
            ktpForm.file = result.file;
            ktpInfo.value = {
                originalSize: formatBytes(result.originalSize),
                compressedSize: formatBytes(result.compressedSize),
                isCompressed: result.isCompressed,
                previewUrl: result.previewUrl,
            };
        }
    } catch (e) {
        toast.error('Gagal memproses foto KTP.');
    } finally {
        ktpCompressing.value = false;
    }
};

const submitKtpReupload = () => {
    if (!ktpForm.file) {
        toast.error('Pilih berkas foto KTP baru terlebih dahulu.');
        return;
    }

    ktpForm.post('/investor/kyc/reupload/ktp', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            ktpForm.reset();
            ktpInfo.value = null;
        },
    });
};

// State Unggah Ulang Perjanjian
const perjanjianForm = useForm({
    file: null,
});
const perjanjianCompressing = ref(false);
const perjanjianInfo = ref(null);

const handlePerjanjianChange = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    perjanjianCompressing.value = true;
    try {
        const result = await compressImageFile(file);
        if (result) {
            perjanjianForm.file = result.file;
            perjanjianInfo.value = {
                originalSize: formatBytes(result.originalSize),
                compressedSize: formatBytes(result.compressedSize),
                isCompressed: result.isCompressed,
                previewUrl: result.previewUrl,
                isPdf: file.type === 'application/pdf',
                name: file.name,
            };
        }
    } catch (e) {
        toast.error('Gagal memproses berkas perjanjian.');
    } finally {
        perjanjianCompressing.value = false;
    }
};

const submitPerjanjianReupload = () => {
    if (!perjanjianForm.file) {
        toast.error('Pilih berkas perjanjian baru terlebih dahulu.');
        return;
    }

    perjanjianForm.post('/investor/kyc/reupload/perjanjian', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            perjanjianForm.reset();
            perjanjianInfo.value = null;
        },
    });
};

const getStatusBadge = (status) => {
    switch (status) {
        case 'approved':
            return {
                label: 'Disetujui',
                bg: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/30',
                icon: CheckCircle2,
            };
        case 'revision_needed':
            return {
                label: 'Perlu Revisi',
                bg: 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/30',
                icon: AlertTriangle,
            };
        case 'rejected':
            return {
                label: 'Ditolak',
                bg: 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/30',
                icon: XCircle,
            };
        case 'pending':
        default:
            return {
                label: 'Menunggu Peninjauan',
                bg: 'bg-sky-500/10 text-sky-600 dark:text-sky-400 border-sky-500/30',
                icon: Clock,
            };
    }
};
</script>

<template>
    <InvestorLayout title="Status KYC & Verifikasi Legalitas">
        <div class="mx-auto max-w-6xl space-y-6">
            <!-- Header Page -->
            <div
                class="bg-card border-border flex flex-col justify-between gap-4 rounded-2xl border p-6 shadow-xs sm:flex-row sm:items-center"
            >
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <span class="bg-primary-500/10 text-primary-600 rounded-xl p-2">
                            <Shield class="h-5 w-5" />
                        </span>
                        <h1 class="text-foreground text-xl font-bold tracking-tight">
                            Status Verifikasi KYC & Legalitas
                        </h1>
                    </div>
                    <p class="text-muted-foreground pl-11 text-xs">
                        Informasi validasi identitas diri, dokumen fisik, dan data perlindungan hak
                        ahli waris.
                    </p>
                </div>

                <!-- Global Status Indicator -->
                <div>
                    <div
                        v-if="is_kyc_approved"
                        class="inline-flex items-center gap-2 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3.5 py-1.5 text-xs font-semibold text-emerald-600 shadow-xs"
                    >
                        <CheckCircle2 class="h-4 w-4" />
                        <span>Akun Terverifikasi Penuh (KYC Approved)</span>
                    </div>

                    <div
                        v-else-if="
                            dokumen.ktp?.status === 'revision_needed' ||
                            dokumen.perjanjian?.status === 'revision_needed'
                        "
                        class="inline-flex items-center gap-2 rounded-full border border-amber-500/30 bg-amber-500/10 px-3.5 py-1.5 text-xs font-semibold text-amber-600 shadow-xs"
                    >
                        <AlertTriangle class="h-4 w-4" />
                        <span>Memerlukan Revisi Berkas</span>
                    </div>

                    <div
                        v-else
                        class="inline-flex items-center gap-2 rounded-full border border-sky-500/30 bg-sky-500/10 px-3.5 py-1.5 text-xs font-semibold text-sky-600 shadow-xs"
                    >
                        <Clock class="h-4 w-4" />
                        <span>Dalam Proses Peninjauan</span>
                    </div>
                </div>
            </div>

            <!-- Banner Notifikasi Status -->
            <div
                v-if="
                    dokumen.ktp?.status === 'revision_needed' ||
                    dokumen.perjanjian?.status === 'revision_needed'
                "
                class="flex items-start gap-3 rounded-xl border border-amber-500/30 bg-amber-500/10 p-4 text-amber-700 dark:text-amber-300"
            >
                <AlertTriangle class="mt-0.5 h-5 w-5 shrink-0" />
                <div class="space-y-1 text-xs">
                    <p class="font-bold">Terdapat Dokumen yang Membutuhkan Perbaikan</p>
                    <p class="leading-relaxed">
                        Administrator telah meninjau pengajuan Anda dan meminta perbaikan pada
                        berkas tertentu. Silakan baca catatan admin pada kartu dokumen di bawah dan
                        unggah ulang berkas baru yang sesuai.
                    </p>
                </div>
            </div>

            <div
                v-else-if="is_kyc_approved"
                class="flex items-start gap-3 rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-emerald-700 dark:text-emerald-300"
            >
                <CheckCircle2 class="mt-0.5 h-5 w-5 shrink-0" />
                <div class="space-y-1 text-xs">
                    <p class="font-bold">Verifikasi Legalitas Selesai</p>
                    <p class="leading-relaxed">
                        Akun Anda telah berstatus aktif. Dokumen KTP dan Surat Perjanjian Kerjasama
                        Bermaterai Anda telah sah terverifikasi oleh tim manajemen YZD Trading.
                    </p>
                </div>
            </div>

            <div
                v-else
                class="bg-primary-500/10 border-primary-500/30 text-primary-700 dark:text-primary-300 flex items-start gap-3 rounded-xl border p-4"
            >
                <Info class="mt-0.5 h-5 w-5 shrink-0" />
                <div class="space-y-1 text-xs">
                    <p class="font-bold">Dokumen Sedang Ditinjau Tim Kepatuhan</p>
                    <p class="leading-relaxed">
                        Berkas identitas dan perjanjian Anda sedang dalam antrean verifikasi manual.
                        Proses ini umumnya memerlukan waktu 1x24 jam kerja.
                    </p>
                </div>
            </div>

            <!-- Konten Utama: 2 Kolom -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Kolom Kiri: Ringkasan Data Pribadi & Ahli Waris (1 Col) -->
                <div class="space-y-6">
                    <!-- Kartu Data Identitas -->
                    <div class="bg-card border-border space-y-4 rounded-2xl border p-5 shadow-xs">
                        <div class="border-border flex items-center gap-2 border-b pb-3">
                            <User class="text-primary-600 h-4 w-4" />
                            <h2 class="text-foreground text-sm font-bold">Data Identitas Akun</h2>
                        </div>

                        <div class="space-y-3 text-xs">
                            <div>
                                <span class="text-muted-foreground block text-[11px]"
                                    >Nama Lengkap</span
                                >
                                <span class="text-foreground font-semibold">{{ user.name }}</span>
                            </div>

                            <div>
                                <span class="text-muted-foreground block text-[11px]"
                                    >Nomor NIK KTP</span
                                >
                                <span class="text-foreground font-mono font-semibold">{{
                                    user.nik
                                }}</span>
                            </div>

                            <div>
                                <span class="text-muted-foreground block text-[11px]"
                                    >Alamat Email</span
                                >
                                <span class="text-foreground">{{ user.email }}</span>
                            </div>

                            <div>
                                <span class="text-muted-foreground block text-[11px]"
                                    >Nomor WhatsApp/HP</span
                                >
                                <span class="text-foreground">{{ user.phone }}</span>
                            </div>

                            <div>
                                <span class="text-muted-foreground block text-[11px]"
                                    >Alamat Domisili</span
                                >
                                <span class="text-foreground leading-relaxed">{{
                                    user.alamat
                                }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Kartu Ahli Waris -->
                    <div class="bg-card border-border space-y-4 rounded-2xl border p-5 shadow-xs">
                        <div class="border-border flex items-center gap-2 border-b pb-3">
                            <Users class="text-accent-500 h-4 w-4" />
                            <h2 class="text-foreground text-sm font-bold">
                                Perlindungan Ahli Waris
                            </h2>
                        </div>

                        <div v-if="ahli_waris" class="space-y-3 text-xs">
                            <div>
                                <span class="text-muted-foreground block text-[11px]"
                                    >Nama Ahli Waris</span
                                >
                                <span class="text-foreground font-semibold">{{
                                    ahli_waris.nama
                                }}</span>
                            </div>

                            <div>
                                <span class="text-muted-foreground block text-[11px]"
                                    >Hubungan Keluarga</span
                                >
                                <span
                                    class="bg-primary-500/10 text-primary-600 inline-block rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                >
                                    {{ ahli_waris.hubungan_keluarga }}
                                </span>
                            </div>

                            <div>
                                <span class="text-muted-foreground block text-[11px]"
                                    >Kontak Darurat</span
                                >
                                <span class="text-foreground">{{ ahli_waris.no_hp }}</span>
                            </div>

                            <div>
                                <span class="text-muted-foreground block text-[11px]"
                                    >Alamat Domisili</span
                                >
                                <span class="text-foreground leading-relaxed">{{
                                    ahli_waris.alamat
                                }}</span>
                            </div>
                        </div>

                        <div v-else class="text-muted-foreground text-xs italic">
                            Data ahli waris belum terdata.
                        </div>
                    </div>
                </div>

                <!-- Kolom Kanan: Status & Re-upload Berkas Dokumen (2 Col) -->
                <div class="space-y-6 lg:col-span-2">
                    <!-- ========================================== -->
                    <!-- DOKUMEN 1: FOTO KTP                        -->
                    <!-- ========================================== -->
                    <div class="bg-card border-border space-y-4 rounded-2xl border p-6 shadow-xs">
                        <div
                            class="border-border flex flex-col justify-between gap-2 border-b pb-3 sm:flex-row sm:items-center"
                        >
                            <div class="flex items-center gap-2">
                                <CreditCard class="text-primary-600 h-4 w-4" />
                                <h3 class="text-foreground text-sm font-bold">
                                    Foto Kartu Tanda Penduduk (KTP)
                                </h3>
                            </div>

                            <!-- Status Badge -->
                            <div
                                v-if="dokumen.ktp"
                                :class="[
                                    'inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-semibold',
                                    getStatusBadge(dokumen.ktp.status).bg,
                                ]"
                            >
                                <component
                                    :is="getStatusBadge(dokumen.ktp.status).icon"
                                    class="h-3.5 w-3.5"
                                />
                                <span>{{ getStatusBadge(dokumen.ktp.status).label }}</span>
                            </div>
                        </div>

                        <!-- Info Berkas Terunggah -->
                        <div v-if="dokumen.ktp" class="space-y-4">
                            <div
                                class="bg-muted/40 border-border flex flex-col items-start justify-between gap-4 rounded-xl border p-3 sm:flex-row sm:items-center"
                            >
                                <div class="space-y-1 text-xs">
                                    <p
                                        class="text-foreground max-w-xs truncate font-semibold sm:max-w-md"
                                    >
                                        {{ dokumen.ktp.file_name }}
                                    </p>
                                    <p class="text-muted-foreground text-[11px]">
                                        Ukuran: {{ formatBytes(dokumen.ktp.file_size) }}
                                        <span v-if="dokumen.ktp.verified_at">
                                            &bull; Diverifikasi:
                                            {{
                                                new Date(
                                                    dokumen.ktp.verified_at
                                                ).toLocaleDateString('id-ID')
                                            }}</span
                                        >
                                    </p>
                                </div>

                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    class="flex items-center gap-1.5 text-xs"
                                    @click="
                                        openPreview(
                                            'Foto KTP - ' + user.name,
                                            dokumen.ktp.preview_url,
                                            dokumen.ktp.mime_type
                                        )
                                    "
                                >
                                    <Eye class="h-3.5 w-3.5" />
                                    <span>Lihat Berkas</span>
                                </Button>
                            </div>

                            <!-- Quality Warning Notification -->
                            <div
                                v-if="dokumen.ktp.is_quality_warning"
                                class="flex items-center gap-2 rounded-lg border border-amber-500/20 bg-amber-500/10 p-3 text-xs text-amber-700 dark:text-amber-300"
                            >
                                <AlertCircle class="h-4 w-4 shrink-0" />
                                <span
                                    >Peringatan Sistem: Gambar terindikasi memiliki resolusi rendah
                                    atau sedikit buram. Pastikan data identitas terbaca jelas.</span
                                >
                            </div>

                            <!-- Catatan Verifikator -->
                            <div
                                v-if="dokumen.ktp.catatan_admin"
                                class="bg-muted/70 border-border space-y-1 rounded-xl border p-3.5 text-xs"
                            >
                                <span class="text-foreground block font-bold">
                                    Catatan Administrator:
                                </span>
                                <p class="text-muted-foreground leading-relaxed italic">
                                    "{{ dokumen.ktp.catatan_admin }}"
                                </p>
                            </div>

                            <!-- FORM RE-UPLOAD KTP (Hanya jika status revision_needed) -->
                            <div
                                v-if="dokumen.ktp.status === 'revision_needed'"
                                class="border-border mt-4 space-y-3 border-t pt-4"
                            >
                                <div class="flex items-center justify-between">
                                    <h4
                                        class="text-foreground text-xs font-bold tracking-wider uppercase"
                                    >
                                        Unggah Ulang Foto KTP Revisi
                                    </h4>
                                    <span
                                        class="flex items-center gap-1 rounded-full bg-emerald-500/10 px-2 py-0.5 text-[10px] font-semibold text-emerald-600"
                                    >
                                        <Sparkles class="h-3 w-3" /> Auto-Compress 2K
                                    </span>
                                </div>

                                <form @submit.prevent="submitKtpReupload" class="space-y-3">
                                    <div
                                        class="border-input hover:border-primary-500 bg-background/50 relative cursor-pointer rounded-xl border-2 border-dashed p-4 text-center transition-all"
                                    >
                                        <input
                                            type="file"
                                            accept="image/jpeg,image/png,image/jpg"
                                            @change="handleKtpChange"
                                            class="absolute inset-0 z-10 h-full w-full cursor-pointer opacity-0"
                                        />

                                        <div
                                            v-if="ktpCompressing"
                                            class="flex items-center justify-center gap-2 py-2 text-xs font-semibold"
                                        >
                                            <Loader2
                                                class="text-primary-600 h-4 w-4 animate-spin"
                                            />
                                            <span>Mengompresi foto KTP...</span>
                                        </div>

                                        <div v-else-if="ktpInfo" class="space-y-1 text-xs">
                                            <p
                                                class="text-foreground flex items-center justify-center gap-1 font-semibold"
                                            >
                                                <Check class="h-3.5 w-3.5 text-emerald-600" />
                                                Berkas Baru Dipilih
                                            </p>
                                            <p class="text-muted-foreground text-[11px]">
                                                {{ ktpInfo.originalSize }} &rarr;
                                                <span class="font-bold text-emerald-600">{{
                                                    ktpInfo.compressedSize
                                                }}</span>
                                            </p>
                                        </div>

                                        <div
                                            v-else
                                            class="flex flex-col items-center justify-center space-y-1 py-2"
                                        >
                                            <UploadCloud class="text-primary-600 h-6 w-6" />
                                            <p class="text-foreground text-xs font-semibold">
                                                Klik untuk memilih foto KTP baru
                                            </p>
                                            <p class="text-muted-foreground text-[10px]">
                                                Maksimal 10 MB (JPG, JPEG, PNG)
                                            </p>
                                        </div>
                                    </div>

                                    <Button
                                        type="submit"
                                        :disabled="
                                            ktpForm.processing || !ktpForm.file || ktpCompressing
                                        "
                                        class="bg-primary-600 hover:bg-primary-700 w-full rounded-lg py-2 text-xs text-white"
                                    >
                                        <Loader2
                                            v-if="ktpForm.processing"
                                            class="mr-1 h-3.5 w-3.5 animate-spin"
                                        />
                                        <span>{{
                                            ktpForm.processing
                                                ? 'Mengunggah Berkas...'
                                                : 'Kirim Berkas KTP Revisi'
                                        }}</span>
                                    </Button>
                                </form>
                            </div>
                        </div>

                        <div v-else class="text-muted-foreground text-xs italic">
                            Berkas KTP belum pernah diunggah.
                        </div>
                    </div>

                    <!-- ========================================== -->
                    <!-- DOKUMEN 2: SURAT PERJANJIAN BERMATERAI    -->
                    <!-- ========================================== -->
                    <div class="bg-card border-border space-y-4 rounded-2xl border p-6 shadow-xs">
                        <div
                            class="border-border flex flex-col justify-between gap-2 border-b pb-3 sm:flex-row sm:items-center"
                        >
                            <div class="flex items-center gap-2">
                                <FileText class="h-4 w-4 text-emerald-600" />
                                <h3 class="text-foreground text-sm font-bold">
                                    Surat Perjanjian Kerjasama Bermaterai
                                </h3>
                            </div>

                            <!-- Status Badge -->
                            <div
                                v-if="dokumen.perjanjian"
                                :class="[
                                    'inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-semibold',
                                    getStatusBadge(dokumen.perjanjian.status).bg,
                                ]"
                            >
                                <component
                                    :is="getStatusBadge(dokumen.perjanjian.status).icon"
                                    class="h-3.5 w-3.5"
                                />
                                <span>{{ getStatusBadge(dokumen.perjanjian.status).label }}</span>
                            </div>
                        </div>

                        <!-- Info Berkas Terunggah -->
                        <div v-if="dokumen.perjanjian" class="space-y-4">
                            <div
                                class="bg-muted/40 border-border flex flex-col items-start justify-between gap-4 rounded-xl border p-3 sm:flex-row sm:items-center"
                            >
                                <div class="space-y-1 text-xs">
                                    <p
                                        class="text-foreground max-w-xs truncate font-semibold sm:max-w-md"
                                    >
                                        {{ dokumen.perjanjian.file_name }}
                                    </p>
                                    <p class="text-muted-foreground text-[11px]">
                                        Ukuran: {{ formatBytes(dokumen.perjanjian.file_size) }}
                                        <span v-if="dokumen.perjanjian.verified_at">
                                            &bull; Diverifikasi:
                                            {{
                                                new Date(
                                                    dokumen.perjanjian.verified_at
                                                ).toLocaleDateString('id-ID')
                                            }}</span
                                        >
                                    </p>
                                </div>

                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    class="flex items-center gap-1.5 text-xs"
                                    @click="
                                        openPreview(
                                            'Surat Perjanjian - ' + user.name,
                                            dokumen.perjanjian.preview_url,
                                            dokumen.perjanjian.mime_type
                                        )
                                    "
                                >
                                    <Eye class="h-3.5 w-3.5" />
                                    <span>Lihat Berkas</span>
                                </Button>
                            </div>

                            <!-- Quality Warning Notification -->
                            <div
                                v-if="dokumen.perjanjian.is_quality_warning"
                                class="flex items-center gap-2 rounded-lg border border-amber-500/20 bg-amber-500/10 p-3 text-xs text-amber-700 dark:text-amber-300"
                            >
                                <AlertCircle class="h-4 w-4 shrink-0" />
                                <span
                                    >Peringatan Sistem: Berkas terdeteksi memiliki ketajaman rendah.
                                    Harap pastikan klausul dan materai terbaca.</span
                                >
                            </div>

                            <!-- Catatan Verifikator -->
                            <div
                                v-if="dokumen.perjanjian.catatan_admin"
                                class="bg-muted/70 border-border space-y-1 rounded-xl border p-3.5 text-xs"
                            >
                                <span class="text-foreground block font-bold">
                                    Catatan Administrator:
                                </span>
                                <p class="text-muted-foreground leading-relaxed italic">
                                    "{{ dokumen.perjanjian.catatan_admin }}"
                                </p>
                            </div>

                            <!-- FORM RE-UPLOAD PERJANJIAN (Hanya jika status revision_needed) -->
                            <div
                                v-if="dokumen.perjanjian.status === 'revision_needed'"
                                class="border-border mt-4 space-y-3 border-t pt-4"
                            >
                                <div class="flex items-center justify-between">
                                    <h4
                                        class="text-foreground text-xs font-bold tracking-wider uppercase"
                                    >
                                        Unggah Ulang Surat Perjanjian Revisi
                                    </h4>
                                    <span
                                        class="flex items-center gap-1 rounded-full bg-emerald-500/10 px-2 py-0.5 text-[10px] font-semibold text-emerald-600"
                                    >
                                        <Sparkles class="h-3 w-3" /> Auto-Compress 2K
                                    </span>
                                </div>

                                <form @submit.prevent="submitPerjanjianReupload" class="space-y-3">
                                    <div
                                        class="border-input hover:border-primary-500 bg-background/50 relative cursor-pointer rounded-xl border-2 border-dashed p-4 text-center transition-all"
                                    >
                                        <input
                                            type="file"
                                            accept="image/jpeg,image/png,image/jpg,application/pdf"
                                            @change="handlePerjanjianChange"
                                            class="absolute inset-0 z-10 h-full w-full cursor-pointer opacity-0"
                                        />

                                        <div
                                            v-if="perjanjianCompressing"
                                            class="flex items-center justify-center gap-2 py-2 text-xs font-semibold"
                                        >
                                            <Loader2
                                                class="text-primary-600 h-4 w-4 animate-spin"
                                            />
                                            <span>Memproses berkas perjanjian...</span>
                                        </div>

                                        <div v-else-if="perjanjianInfo" class="space-y-1 text-xs">
                                            <p
                                                class="text-foreground flex items-center justify-center gap-1 font-semibold"
                                            >
                                                <Check class="h-3.5 w-3.5 text-emerald-600" />
                                                Berkas Baru Dipilih ({{
                                                    perjanjianInfo.name || 'Dokumen'
                                                }})
                                            </p>
                                            <p
                                                v-if="!perjanjianInfo.isPdf"
                                                class="text-muted-foreground text-[11px]"
                                            >
                                                {{ perjanjianInfo.originalSize }} &rarr;
                                                <span class="font-bold text-emerald-600">{{
                                                    perjanjianInfo.compressedSize
                                                }}</span>
                                            </p>
                                        </div>

                                        <div
                                            v-else
                                            class="flex flex-col items-center justify-center space-y-1 py-2"
                                        >
                                            <UploadCloud class="h-6 w-6 text-emerald-600" />
                                            <p class="text-foreground text-xs font-semibold">
                                                Klik untuk memilih berkas perjanjian baru
                                            </p>
                                            <p class="text-muted-foreground text-[10px]">
                                                Maksimal 10 MB (JPG, PNG, atau PDF)
                                            </p>
                                        </div>
                                    </div>

                                    <Button
                                        type="submit"
                                        :disabled="
                                            perjanjianForm.processing ||
                                            !perjanjianForm.file ||
                                            perjanjianCompressing
                                        "
                                        class="bg-primary-600 hover:bg-primary-700 w-full rounded-lg py-2 text-xs text-white"
                                    >
                                        <Loader2
                                            v-if="perjanjianForm.processing"
                                            class="mr-1 h-3.5 w-3.5 animate-spin"
                                        />
                                        <span>{{
                                            perjanjianForm.processing
                                                ? 'Mengunggah Berkas...'
                                                : 'Kirim Berkas Perjanjian Revisi'
                                        }}</span>
                                    </Button>
                                </form>
                            </div>
                        </div>

                        <div v-else class="text-muted-foreground text-xs italic">
                            Berkas perjanjian belum pernah diunggah.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- MODAL PREVIEW DOKUMEN                      -->
        <!-- ========================================== -->
        <AppModal
            :show="previewModal.isOpen"
            :title="previewModal.title"
            max-width="3xl"
            @close="closePreview"
        >
            <div
                class="bg-muted/20 flex max-h-[70vh] min-h-[300px] items-center justify-center overflow-auto rounded-xl p-2"
            >
                <!-- PDF Viewer -->
                <iframe
                    v-if="previewModal.isPdf"
                    :src="previewModal.url"
                    class="border-border h-[65vh] w-full rounded-lg border"
                ></iframe>

                <!-- Image Viewer -->
                <img
                    v-else
                    :src="previewModal.url"
                    :alt="previewModal.title"
                    class="max-h-[65vh] max-w-full rounded-lg object-contain shadow-sm"
                />
            </div>

            <template #footer>
                <div class="flex w-full items-center justify-between">
                    <a
                        :href="previewModal.url"
                        target="_blank"
                        download
                        class="text-primary-600 text-xs font-semibold hover:underline"
                    >
                        Unduh Berkas Asli
                    </a>
                    <Button
                        type="button"
                        variant="secondary"
                        size="sm"
                        class="text-xs"
                        @click="closePreview"
                    >
                        Tutup
                    </Button>
                </div>
            </template>
        </AppModal>
    </InvestorLayout>
</template>
