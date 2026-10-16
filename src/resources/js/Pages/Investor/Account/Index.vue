<script setup>
import { ref, computed } from 'vue';
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
    FileText,
    File as FileIcon,
    AlertCircle,
    Info,
    Check,
    Edit3,
} from 'lucide-vue-next';
import AppButton from '@/Components/Common/AppButton.vue';

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
    const freshUrl = url ? `${url}${url.includes('?') ? '&' : '?'}_t=${Date.now()}` : '';

    previewModal.value = {
        isOpen: true,
        title,
        url: freshUrl,
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
const ktpProcessing = ref(false);
const ktpInfo = ref(null);
const showKtpReupload = ref(false);

const canReuploadKtp = computed(() => {
    if (!props.dokumen?.ktp) return false;
    if (props.dokumen.ktp.status === 'approved') return false;
    return (
        props.dokumen.ktp.status === 'revision_needed' ||
        props.dokumen.ktp.status === 'rejected' ||
        (props.dokumen.ktp.status === 'pending' && props.dokumen.ktp.is_quality_warning) ||
        showKtpReupload.value
    );
});

const handleKtpChange = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    ktpProcessing.value = true;
    try {
        const result = await compressImageFile(file);
        if (result) {
            ktpForm.file = result.file;
            ktpInfo.value = {
                name: file.name,
                isQualityWarning: result.isQualityWarning || false,
                warningMessage: result.warningMessage || null,
            };
            if (result.isQualityWarning) {
                toast.warning(
                    'Peringatan: Berkas foto KTP terindikasi resolusi rendah atau sedikit buram.'
                );
            }
        }
    } catch (e) {
        toast.error('Gagal memproses foto KTP.');
    } finally {
        ktpProcessing.value = false;
    }
};

const submitKtpReupload = () => {
    if (!ktpForm.file) {
        toast.error('Pilih berkas foto KTP baru terlebih dahulu.');
        return;
    }

    ktpForm.post('/investor/account/reupload/ktp', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            ktpForm.reset();
            ktpInfo.value = null;
            showKtpReupload.value = false;
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
    <InvestorLayout
        title="Informasi Akun"
        subtitle="Informasi identitas diri, data ahli waris, status verifikasi dokumen pendaftaran, dan surat perjanjian investasi."
        :breadcrumbs="[{ label: 'Profil' }, { label: 'Informasi Akun' }]"
    >
        <div class="space-y-6">
            <!-- Konten Utama: 2 Kolom -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Kolom Kiri: Ringkasan Data Pribadi & Ahli Waris (1 Col) -->
                <div class="space-y-6">
                    <!-- Kartu Data Identitas -->
                    <div class="bg-card border-border space-y-4 rounded-2xl border p-5 shadow-xs">
                        <div class="border-border flex items-center gap-2 border-b pb-3">
                            <User class="text-primary-600 h-4 w-4" />
                            <h2 class="text-foreground text-sm font-bold">Data Identitas</h2>
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
                                    >Username</span
                                >
                                <span class="text-foreground font-semibold">{{
                                    user.username
                                }}</span>
                            </div>

                            <div>
                                <span class="text-muted-foreground block text-[11px]">Email</span>
                                <span class="text-foreground font-semibold">{{ user.email }}</span>
                            </div>

                            <div>
                                <span class="text-muted-foreground block text-[11px]"
                                    >Nomor WhatsApp/HP</span
                                >
                                <span class="text-foreground font-semibold">{{ user.phone }}</span>
                            </div>

                            <div>
                                <span class="text-muted-foreground block text-[11px]">NIK</span>
                                <span class="text-foreground font-semibold">{{ user.nik }}</span>
                            </div>

                            <div v-if="user.tempat_lahir || user.tanggal_lahir">
                                <span class="text-muted-foreground block text-[11px]"
                                    >Tempat, Tanggal Lahir</span
                                >
                                <span class="text-foreground font-semibold">
                                    {{
                                        [user.tempat_lahir, user.tanggal_lahir]
                                            .filter(Boolean)
                                            .join(', ')
                                    }}
                                </span>
                            </div>

                            <div v-if="user.pekerjaan">
                                <span class="text-muted-foreground block text-[11px]"
                                    >Pekerjaan / Sumber Dana</span
                                >
                                <span class="text-foreground font-semibold">{{
                                    user.pekerjaan
                                }}</span>
                            </div>

                            <div>
                                <span class="text-muted-foreground block text-[11px]">Alamat</span>
                                <span class="text-foreground leading-relaxed font-semibold">{{
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
                                Ahli Waris / Kontak Darurat
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
                                    >Hubungan</span
                                >
                                <span class="text-foreground font-semibold">{{
                                    ahli_waris.hubungan_keluarga
                                }}</span>
                            </div>

                            <div>
                                <span class="text-muted-foreground block text-[11px]"
                                    >Kontak Darurat</span
                                >
                                <span class="text-foreground font-semibold">{{
                                    ahli_waris.no_hp
                                }}</span>
                            </div>

                            <div v-if="ahli_waris.alamat">
                                <span class="text-muted-foreground block text-[11px]"
                                    >Alamat Domisili Ahli Waris</span
                                >
                                <span class="text-foreground leading-relaxed font-semibold">{{
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
                <div class="space-y-6">
                    <!-- ========================================== -->
                    <!-- DOKUMEN 1: FOTO KTP                        -->
                    <!-- ========================================== -->
                    <div class="bg-card border-border space-y-4 rounded-2xl border p-6 shadow-xs">
                        <div
                            class="border-border flex flex-row justify-between gap-2 border-b pb-3 sm:items-center"
                        >
                            <div class="flex items-center gap-2">
                                <CreditCard class="text-primary-600 h-4 w-4" />
                                <h3 class="text-foreground text-sm font-bold">Foto KTP</h3>
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
                                <div class="min-w-0 flex-1 space-y-1 text-xs">
                                    <p
                                        class="text-foreground font-semibold [overflow-wrap:anywhere] break-words"
                                    >
                                        {{ dokumen.ktp.file_name }}
                                    </p>
                                    <p class="text-muted-foreground text-[11px]">
                                        <span v-if="dokumen.ktp.verified_at">
                                            Diverifikasi: {{ dokumen.ktp.verified_at }}
                                        </span>
                                    </p>
                                </div>

                                <div class="flex shrink-0 items-center gap-2">
                                    <AppButton
                                        v-if="
                                            dokumen.ktp.status === 'pending' &&
                                            !dokumen.ktp.is_quality_warning &&
                                            !showKtpReupload
                                        "
                                        type="custom"
                                        variant="ghost"
                                        size="sm"
                                        label="Ganti Berkas"
                                        :icon="Edit3"
                                        @click="showKtpReupload = true"
                                    />

                                    <AppButton
                                        type="custom"
                                        label="Lihat"
                                        :icon="Eye"
                                        variant="outline"
                                        size="sm"
                                        @click="
                                            openPreview(
                                                'Foto KTP - ' + user.name,
                                                dokumen.ktp.preview_url,
                                                dokumen.ktp.mime_type
                                            )
                                        "
                                    />
                                </div>
                            </div>

                            <!-- Quality Warning Notification -->
                            <div
                                v-if="
                                    dokumen.ktp.is_quality_warning &&
                                    dokumen.ktp.status !== 'approved'
                                "
                                class="flex items-start gap-3 rounded-xl border border-amber-500/30 bg-amber-500/10 p-4 text-xs text-amber-800 dark:text-amber-200"
                            >
                                <AlertCircle
                                    class="mt-0.5 h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400"
                                />
                                <div class="space-y-1">
                                    <p class="font-bold">Peringatan Kualitas Dokumen</p>
                                    <p class="leading-relaxed">
                                        Foto KTP yang Anda unggah terindikasi memiliki resolusi
                                        rendah atau sedikit buram. Agar proses verifikasi akun tidak
                                        terhambat, Anda disarankan mengunggah ulang foto baru yang
                                        lebih tajam.
                                    </p>
                                </div>
                            </div>

                            <!-- Catatan Verifikator -->
                            <div
                                v-if="
                                    dokumen.ktp.catatan_admin && dokumen.ktp.status !== 'approved'
                                "
                                class="bg-muted/70 border-border space-y-1 rounded-xl border p-3.5 text-xs"
                            >
                                <span class="text-foreground block font-bold">
                                    Catatan Administrator:
                                </span>
                                <p class="text-muted-foreground leading-relaxed italic">
                                    "{{ dokumen.ktp.catatan_admin }}"
                                </p>
                            </div>

                            <!-- FORM RE-UPLOAD KTP -->
                            <div
                                v-if="canReuploadKtp"
                                class="border-border mt-4 space-y-3 border-t pt-4"
                            >
                                <div class="flex items-center justify-between">
                                    <h4
                                        class="text-foreground text-xs font-bold tracking-wider uppercase"
                                    >
                                        {{
                                            dokumen.ktp.status === 'revision_needed' ||
                                            dokumen.ktp.status === 'rejected'
                                                ? 'Unggah Ulang Foto KTP'
                                                : dokumen.ktp.is_quality_warning
                                                  ? 'Perbaiki & Unggah Ulang Foto KTP'
                                                  : 'Ganti Foto KTP'
                                        }}
                                    </h4>
                                    <button
                                        v-if="
                                            showKtpReupload &&
                                            dokumen.ktp.status === 'pending' &&
                                            !dokumen.ktp.is_quality_warning
                                        "
                                        type="button"
                                        class="text-muted-foreground hover:text-foreground text-xs"
                                        @click="showKtpReupload = false"
                                    >
                                        Batal
                                    </button>
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
                                            v-if="ktpProcessing"
                                            class="flex items-center justify-center gap-2 py-2 text-xs font-semibold"
                                        >
                                            <Loader2
                                                class="text-primary-600 h-4 w-4 animate-spin"
                                            />
                                            <span>Memeriksa berkas...</span>
                                        </div>

                                        <div v-else-if="ktpInfo" class="space-y-1.5 text-xs">
                                            <p
                                                class="text-foreground flex items-center justify-center gap-1.5 px-2 font-semibold break-all"
                                            >
                                                <Check class="h-4 w-4 shrink-0 text-emerald-600" />
                                                <span>{{ ktpInfo.name }}</span>
                                            </p>

                                            <!-- Peringatan Kualitas File Baru -->
                                            <div
                                                v-if="ktpInfo.isQualityWarning"
                                                class="mt-2 flex items-start gap-2 rounded-lg border border-amber-500/30 bg-amber-500/10 p-2.5 text-left text-xs text-amber-800 dark:text-amber-200"
                                            >
                                                <AlertCircle
                                                    class="mt-0.5 h-3.5 w-3.5 shrink-0 text-amber-600 dark:text-amber-400"
                                                />
                                                <p class="text-[11px] leading-relaxed">
                                                    {{
                                                        ktpInfo.warningMessage ||
                                                        'Foto baru ini terindikasi kurang tajam atau buram. Disarankan memilih foto lain yang lebih jelas.'
                                                    }}
                                                </p>
                                            </div>
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
                                            ktpForm.processing || !ktpForm.file || ktpProcessing
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
                                                : dokumen.ktp.status === 'revision_needed' ||
                                                    dokumen.ktp.status === 'rejected'
                                                  ? 'Kirim Berkas KTP'
                                                  : 'Kirim Pembaruan Foto KTP'
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
                            class="border-border flex flex-row justify-between gap-2 border-b pb-3 sm:items-center"
                        >
                            <div class="flex items-center gap-2">
                                <FileText class="h-4 w-4 text-emerald-600" />
                                <h3 class="text-foreground text-sm font-bold">Surat Perjanjian</h3>
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

                        <!-- Info Berkas Terunggah (Hanya Disetujui) -->
                        <div v-if="dokumen.perjanjian" class="space-y-4">
                            <div
                                class="bg-muted/40 border-border flex flex-col items-start justify-between gap-4 rounded-xl border p-3 sm:flex-row sm:items-center"
                            >
                                <div class="min-w-0 flex-1 space-y-1 text-xs">
                                    <p
                                        class="text-foreground font-semibold [overflow-wrap:anywhere] break-words"
                                    >
                                        {{ dokumen.perjanjian.file_name }}
                                    </p>
                                    <p class="text-muted-foreground text-[11px]">
                                        <span v-if="dokumen.perjanjian.verified_at">
                                            Diverifikasi: {{ dokumen.perjanjian.verified_at }}
                                        </span>
                                    </p>
                                </div>

                                <div class="flex shrink-0 items-center gap-2">
                                    <AppButton
                                        type="custom"
                                        label="Lihat"
                                        :icon="Eye"
                                        variant="outline"
                                        size="sm"
                                        @click="
                                            openPreview(
                                                'Surat Perjanjian - ' + user.name,
                                                dokumen.perjanjian.preview_url,
                                                dokumen.perjanjian.mime_type
                                            )
                                        "
                                    />
                                </div>
                            </div>
                        </div>

                        <div v-else class="text-muted-foreground text-xs italic">
                            Belum ada berkas surat perjanjian yang disetujui. Berkas perjanjian
                            disertakan dan diverifikasi saat pendaftaran akun copytrade (investasi).
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
                    :key="previewModal.url"
                    :src="previewModal.url"
                    class="border-border h-[65vh] w-full rounded-lg border"
                ></iframe>

                <!-- Image Viewer -->
                <img
                    v-else
                    :key="previewModal.url"
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
                        Unduh Berkas
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
