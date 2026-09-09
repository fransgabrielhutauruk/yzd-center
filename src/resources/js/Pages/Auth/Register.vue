<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import AppModal from '@/Components/Common/AppModal.vue';
import { Button } from '@/Components/ui/button';
import { compressImageFile, formatBytes } from '@/lib/imageCompression';
import { toast } from 'vue-sonner';
import {
    User,
    Lock,
    Eye,
    EyeOff,
    Mail,
    Phone,
    CreditCard,
    MapPin,
    ShieldAlert,
    Ticket,
    UserCheck,
    ArrowLeft,
    CheckCircle2,
    Users,
    FileText,
    UploadCloud,
    CheckCircle,
    ExternalLink,
    File as FileIcon,
    Sparkles,
    Loader2,
    Info,
} from 'lucide-vue-next';

const props = defineProps({
    invitation: {
        type: Object,
        required: true,
    },
    token: {
        type: String,
        default: '',
    },
    terms: {
        type: Object,
        default: null,
    },
});

const showPassword = ref(false);
const isTermsModalOpen = ref(false);

const ktpCompressing = ref(false);
const ktpInfo = ref(null);

const perjanjianCompressing = ref(false);
const perjanjianInfo = ref(null);

const form = useForm({
    invite_token: props.token || '',
    name: '',
    username: '',
    email: '',
    phone: '',
    nik: '',
    alamat: '',
    password: '',
    password_confirmation: '',

    // Data Ahli Waris
    ahli_waris_nama: '',
    ahli_waris_hubungan: '',
    ahli_waris_phone: '',
    ahli_waris_alamat: '',

    // Berkas Legalitas
    dokumen_ktp: null,
    dokumen_perjanjian: null,

    // Persetujuan T&C
    terms: false,
});

const hubunganOptions = [
    'Anak',
    'Istri',
    'Suami',
    'Orang Tua',
    'Saudara Kandung',
];

const copyAlamatToAhliWaris = () => {
    if (form.alamat) {
        form.ahli_waris_alamat = form.alamat;
        toast.info('Alamat domisili disalin ke alamat ahli waris.');
    }
};

const handleKtpChange = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    ktpCompressing.value = true;
    try {
        const result = await compressImageFile(file);
        if (result) {
            form.dokumen_ktp = result.file;
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

const handlePerjanjianChange = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    perjanjianCompressing.value = true;
    try {
        const result = await compressImageFile(file);
        if (result) {
            form.dokumen_perjanjian = result.file;
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

const openTermsModal = () => {
    isTermsModalOpen.value = true;
};

const closeTermsModal = () => {
    isTermsModalOpen.value = false;
};

const submit = () => {
    if (!form.dokumen_ktp) {
        toast.error('Foto KTP wajib diunggah.');
        return;
    }
    if (!form.dokumen_perjanjian) {
        toast.error('Surat Perjanjian Kerjasama wajib diunggah.');
        return;
    }
    if (!form.terms) {
        toast.error('Anda wajib menyetujui Syarat & Ketentuan Layanan.');
        return;
    }

    form.post('/register', {
        forceFormData: true,
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <AuthLayout
        :title="invitation.valid ? 'Pendaftaran Akun & Verifikasi Legalitas - YZD Trading' : 'Undangan Tidak Valid - YZD Trading'"
        :max-width="invitation.valid ? 'max-w-2xl' : 'max-w-md'"
    >
        <!-- Case 1: Undangan Tidak Valid / Kedaluwarsa / Kuota Habis -->
        <div v-if="!invitation.valid" class="text-center space-y-6 py-4">
            <div class="mx-auto w-16 h-16 rounded-full bg-amber-500/10 flex items-center justify-center text-amber-600 dark:text-amber-400">
                <ShieldAlert class="w-8 h-8" />
            </div>

            <div class="space-y-2">
                <h1 class="text-xl font-bold text-foreground">
                    Tautan Undangan Tidak Valid
                </h1>
                <p class="text-sm text-muted-foreground leading-relaxed">
                    {{ invitation.message || 'Tautan pendaftaran ini tidak dapat digunakan atau telah kedaluwarsa.' }}
                </p>
            </div>

            <div class="rounded-xl p-4 bg-muted/60 border border-border text-left text-xs text-muted-foreground space-y-2">
                <p class="font-semibold text-foreground flex items-center gap-1.5">
                    <Ticket class="w-4 h-4 text-primary-500" />
                    Kebijakan Pendaftaran Tertutup:
                </p>
                <p class="leading-relaxed">
                    YZD Trading menerapkan sistem eksklusif berbasis undangan (*Closed Registration*). Setiap pendaftaran akun wajib menggunakan token tautan resmi yang masih aktif dari Administrator.
                </p>
            </div>

            <div class="pt-2">
                <Link
                    href="/login"
                    class="inline-flex items-center justify-center gap-2 w-full py-2.5 px-4 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-lg shadow-sm transition-colors text-sm"
                >
                    <ArrowLeft class="w-4 h-4" />
                    <span>Kembali ke Halaman Masuk</span>
                </Link>
            </div>
        </div>

        <!-- Case 2: Undangan Valid -> Tampilkan Formulir Pendaftaran Lengkap -->
        <div v-else class="space-y-6">
            <div class="text-center space-y-1">
                <h1 class="text-xl font-bold tracking-tight text-foreground">
                    Pendaftaran Akun & Legalitas
                </h1>
                <p class="text-xs text-muted-foreground">
                    Lengkapi identitas diri, kontak darurat ahli waris, dan berkas legalitas untuk aktivasi akun.
                </p>
            </div>

            <!-- Invitation Token Badge Info -->
            <div class="rounded-xl p-3.5 bg-primary-500/10 border border-primary-500/20 text-xs flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="p-1.5 bg-primary-500/20 rounded-lg text-primary-600 dark:text-primary-400">
                        <Ticket class="w-4 h-4" />
                    </div>
                    <div>
                        <span class="font-semibold text-foreground block">{{ invitation.invitation?.label }}</span>
                        <span class="text-muted-foreground">Tautan Terverifikasi</span>
                    </div>
                </div>
                <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-primary-600 text-white shadow-xs">
                    <CheckCircle2 class="w-3.5 h-3.5" />
                    <span>{{ invitation.invitation?.target_role_label }}</span>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- ========================================== -->
                <!-- BAGIAN 1: IDENTITAS UTAMA PENGGUNA         -->
                <!-- ========================================== -->
                <div class="space-y-4">
                    <div class="flex items-center gap-2 pb-2 border-b border-border text-foreground">
                        <div class="p-1 rounded-md bg-primary-500/10 text-primary-600">
                            <User class="w-4 h-4" />
                        </div>
                        <h2 class="text-sm font-bold uppercase tracking-wider">
                            1. Identitas Akun & Domisili
                        </h2>
                    </div>

                    <!-- Grid: Nama & Username -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Nama Lengkap -->
                        <div class="space-y-1.5">
                            <label for="name" class="block text-xs font-semibold text-foreground">
                                Nama Lengkap (Sesuai KTP) <span class="text-destructive">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-muted-foreground">
                                    <User class="w-4 h-4" />
                                </div>
                                <input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    required
                                    placeholder="Contoh: Budi Santoso"
                                    class="w-full pl-9 pr-3 py-2 text-sm bg-background border border-input rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                                    :class="{ 'border-destructive focus:ring-destructive': form.errors.name }"
                                />
                            </div>
                            <p v-if="form.errors.name" class="text-xs text-destructive font-medium">
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <!-- Username -->
                        <div class="space-y-1.5">
                            <label for="username" class="block text-xs font-semibold text-foreground">
                                Username <span class="text-destructive">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-muted-foreground">
                                    <span class="text-xs font-bold">@</span>
                                </div>
                                <input
                                    id="username"
                                    v-model="form.username"
                                    type="text"
                                    required
                                    placeholder="budisantoso"
                                    class="w-full pl-9 pr-3 py-2 text-sm bg-background border border-input rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                                    :class="{ 'border-destructive focus:ring-destructive': form.errors.username }"
                                />
                            </div>
                            <p v-if="form.errors.username" class="text-xs text-destructive font-medium">
                                {{ form.errors.username }}
                            </p>
                        </div>
                    </div>

                    <!-- Grid: Email & Phone -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Email -->
                        <div class="space-y-1.5">
                            <label for="email" class="block text-xs font-semibold text-foreground">
                                Alamat Email <span class="text-destructive">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-muted-foreground">
                                    <Mail class="w-4 h-4" />
                                </div>
                                <input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    required
                                    placeholder="budi@example.com"
                                    class="w-full pl-9 pr-3 py-2 text-sm bg-background border border-input rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                                    :class="{ 'border-destructive focus:ring-destructive': form.errors.email }"
                                />
                            </div>
                            <p v-if="form.errors.email" class="text-xs text-destructive font-medium">
                                {{ form.errors.email }}
                            </p>
                        </div>

                        <!-- Nomor Telepon -->
                        <div class="space-y-1.5">
                            <label for="phone" class="block text-xs font-semibold text-foreground">
                                Nomor WhatsApp / HP <span class="text-destructive">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-muted-foreground">
                                    <Phone class="w-4 h-4" />
                                </div>
                                <input
                                    id="phone"
                                    v-model="form.phone"
                                    type="tel"
                                    required
                                    placeholder="081234567890"
                                    class="w-full pl-9 pr-3 py-2 text-sm bg-background border border-input rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                                    :class="{ 'border-destructive focus:ring-destructive': form.errors.phone }"
                                />
                            </div>
                            <p v-if="form.errors.phone" class="text-xs text-destructive font-medium">
                                {{ form.errors.phone }}
                            </p>
                        </div>
                    </div>

                    <!-- NIK KTP -->
                    <div class="space-y-1.5">
                        <label for="nik" class="block text-xs font-semibold text-foreground">
                            Nomor Induk Kependudukan (NIK 16 Digit) <span class="text-destructive">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-muted-foreground">
                                <CreditCard class="w-4 h-4" />
                            </div>
                            <input
                                id="nik"
                                v-model="form.nik"
                                type="text"
                                maxlength="16"
                                required
                                placeholder="3201xxxxxxxxxxxx"
                                class="w-full pl-9 pr-3 py-2 text-sm bg-background border border-input rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all font-mono"
                                :class="{ 'border-destructive focus:ring-destructive': form.errors.nik }"
                            />
                        </div>
                        <p v-if="form.errors.nik" class="text-xs text-destructive font-medium">
                            {{ form.errors.nik }}
                        </p>
                    </div>

                    <!-- Alamat Domisili -->
                    <div class="space-y-1.5">
                        <label for="alamat" class="block text-xs font-semibold text-foreground">
                            Alamat Domisili Lengkap <span class="text-destructive">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute top-2.5 left-0 pl-3 flex items-start pointer-events-none text-muted-foreground">
                                <MapPin class="w-4 h-4" />
                            </div>
                            <textarea
                                id="alamat"
                                v-model="form.alamat"
                                rows="2"
                                required
                                placeholder="Jl. Mawar No. 12, Kelurahan, Kecamatan, Kota"
                                class="w-full pl-9 pr-3 py-2 text-sm bg-background border border-input rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all resize-none"
                                :class="{ 'border-destructive focus:ring-destructive': form.errors.alamat }"
                            ></textarea>
                        </div>
                        <p v-if="form.errors.alamat" class="text-xs text-destructive font-medium">
                            {{ form.errors.alamat }}
                        </p>
                    </div>

                    <!-- Grid: Password & Konfirmasi -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Kata Sandi -->
                        <div class="space-y-1.5">
                            <label for="password" class="block text-xs font-semibold text-foreground">
                                Kata Sandi <span class="text-destructive">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-muted-foreground">
                                    <Lock class="w-4 h-4" />
                                </div>
                                <input
                                    id="password"
                                    v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    autocomplete="new-password"
                                    required
                                    placeholder="Min. 8 karakter"
                                    class="w-full pl-9 pr-9 py-2 text-sm bg-background border border-input rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                                    :class="{ 'border-destructive focus:ring-destructive': form.errors.password }"
                                />
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-muted-foreground hover:text-foreground"
                                    tabindex="-1"
                                >
                                    <EyeOff v-if="showPassword" class="w-4 h-4" />
                                    <Eye v-else class="w-4 h-4" />
                                </button>
                            </div>
                            <p v-if="form.errors.password" class="text-xs text-destructive font-medium">
                                {{ form.errors.password }}
                            </p>
                        </div>

                        <!-- Konfirmasi Kata Sandi -->
                        <div class="space-y-1.5">
                            <label for="password_confirmation" class="block text-xs font-semibold text-foreground">
                                Konfirmasi Kata Sandi <span class="text-destructive">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-muted-foreground">
                                    <Lock class="w-4 h-4" />
                                </div>
                                <input
                                    id="password_confirmation"
                                    v-model="form.password_confirmation"
                                    :type="showPassword ? 'text' : 'password'"
                                    autocomplete="new-password"
                                    required
                                    placeholder="Ulangi kata sandi"
                                    class="w-full pl-9 pr-3 py-2 text-sm bg-background border border-input rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- BAGIAN 2: DATA AHLI WARIS (PERLINDUNGAN)   -->
                <!-- ========================================== -->
                <div class="space-y-4 pt-2">
                    <div class="flex items-center justify-between pb-2 border-b border-border text-foreground">
                        <div class="flex items-center gap-2">
                            <div class="p-1 rounded-md bg-accent-500/10 text-accent-600">
                                <Users class="w-4 h-4" />
                            </div>
                            <div>
                                <h2 class="text-sm font-bold uppercase tracking-wider">
                                    2. Perlindungan Ahli Waris
                                </h2>
                            </div>
                        </div>
                        <span class="text-[11px] text-muted-foreground">Kontak Darurat Resmi</span>
                    </div>

                    <p class="text-xs text-muted-foreground leading-relaxed">
                        Data ahli waris diperlukan sebagai perlindungan aset dan hak investor jika terjadi keadaan darurat/force majeure di kemudian hari.
                    </p>

                    <!-- Grid: Nama & Hubungan Ahli Waris -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Nama Ahli Waris -->
                        <div class="space-y-1.5">
                            <label for="ahli_waris_nama" class="block text-xs font-semibold text-foreground">
                                Nama Lengkap Ahli Waris <span class="text-destructive">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-muted-foreground">
                                    <User class="w-4 h-4" />
                                </div>
                                <input
                                    id="ahli_waris_nama"
                                    v-model="form.ahli_waris_nama"
                                    type="text"
                                    required
                                    placeholder="Contoh: Siti Rahmawati"
                                    class="w-full pl-9 pr-3 py-2 text-sm bg-background border border-input rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all"
                                    :class="{ 'border-destructive focus:ring-destructive': form.errors.ahli_waris_nama }"
                                />
                            </div>
                            <p v-if="form.errors.ahli_waris_nama" class="text-xs text-destructive font-medium">
                                {{ form.errors.ahli_waris_nama }}
                            </p>
                        </div>

                        <!-- Hubungan Keluarga -->
                        <div class="space-y-1.5">
                            <label for="ahli_waris_hubungan" class="block text-xs font-semibold text-foreground">
                                Hubungan Keluarga <span class="text-destructive">*</span>
                            </label>
                            <select
                                id="ahli_waris_hubungan"
                                v-model="form.ahli_waris_hubungan"
                                required
                                class="w-full px-3 py-2 text-sm bg-background border border-input rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all font-medium"
                                :class="{ 'border-destructive focus:ring-destructive': form.errors.ahli_waris_hubungan }"
                            >
                                <option value="" disabled>-- Pilih Hubungan Keluarga --</option>
                                <option v-for="opt in hubunganOptions" :key="opt" :value="opt">
                                    {{ opt }}
                                </option>
                            </select>
                            <p v-if="form.errors.ahli_waris_hubungan" class="text-xs text-destructive font-medium">
                                {{ form.errors.ahli_waris_hubungan }}
                            </p>
                        </div>
                    </div>

                    <!-- Nomor HP Ahli Waris -->
                    <div class="space-y-1.5">
                        <label for="ahli_waris_phone" class="block text-xs font-semibold text-foreground">
                            Nomor WhatsApp / HP Ahli Waris <span class="text-destructive">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-muted-foreground">
                                <Phone class="w-4 h-4" />
                            </div>
                            <input
                                id="ahli_waris_phone"
                                v-model="form.ahli_waris_phone"
                                type="tel"
                                required
                                placeholder="089876543210 (Wajib berbeda dari nomor Anda)"
                                class="w-full pl-9 pr-3 py-2 text-sm bg-background border border-input rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all"
                                :class="{ 'border-destructive focus:ring-destructive': form.errors.ahli_waris_phone }"
                            />
                        </div>
                        <p v-if="form.errors.ahli_waris_phone" class="text-xs text-destructive font-medium">
                            {{ form.errors.ahli_waris_phone }}
                        </p>
                    </div>

                    <!-- Alamat Ahli Waris -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label for="ahli_waris_alamat" class="block text-xs font-semibold text-foreground">
                                Alamat Domisili Ahli Waris <span class="text-destructive">*</span>
                            </label>
                            <button
                                type="button"
                                @click="copyAlamatToAhliWaris"
                                class="text-[11px] text-primary-600 hover:underline font-medium"
                            >
                                Samakan dengan alamat saya
                            </button>
                        </div>
                        <div class="relative">
                            <div class="absolute top-2.5 left-0 pl-3 flex items-start pointer-events-none text-muted-foreground">
                                <MapPin class="w-4 h-4" />
                            </div>
                            <textarea
                                id="ahli_waris_alamat"
                                v-model="form.ahli_waris_alamat"
                                rows="2"
                                required
                                placeholder="Alamat domisili lengkap ahli waris"
                                class="w-full pl-9 pr-3 py-2 text-sm bg-background border border-input rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all resize-none"
                                :class="{ 'border-destructive focus:ring-destructive': form.errors.ahli_waris_alamat }"
                            ></textarea>
                        </div>
                        <p v-if="form.errors.ahli_waris_alamat" class="text-xs text-destructive font-medium">
                            {{ form.errors.ahli_waris_alamat }}
                        </p>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- BAGIAN 3: BERKAS LEGALITAS & AUTO COMPRESS -->
                <!-- ========================================== -->
                <div class="space-y-4 pt-2">
                    <div class="flex items-center justify-between pb-2 border-b border-border text-foreground">
                        <div class="flex items-center gap-2">
                            <div class="p-1 rounded-md bg-emerald-500/10 text-emerald-600">
                                <FileText class="w-4 h-4" />
                            </div>
                            <h2 class="text-sm font-bold uppercase tracking-wider">
                                3. Unggah Berkas Legalitas & KYC
                            </h2>
                        </div>
                        <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-600 bg-emerald-500/10 px-2 py-0.5 rounded-full">
                            <Sparkles class="w-3 h-3" /> Auto-Compress 2K
                        </span>
                    </div>

                    <div class="p-3 bg-muted/60 rounded-xl border border-border text-xs text-muted-foreground space-y-1">
                        <p class="font-semibold text-foreground flex items-center gap-1.5">
                            <Info class="w-3.5 h-3.5 text-primary-500" />
                            Panduan Unggah Berkas:
                        </p>
                        <p class="leading-relaxed">
                            Anda dapat langsung mengunggah foto mentah dari smartphone (hingga 10 MB). Sistem secara otomatis mengompresi gambar ke resolusi tajam 2K tanpa mengurangi keterbacaan teks kartu identitas.
                        </p>
                    </div>

                    <!-- Grid: 2 Kolom Unggah Berkas -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Upload KTP -->
                        <div class="rounded-xl border border-border p-4 bg-card/60 space-y-3">
                            <div class="flex items-start justify-between">
                                <div>
                                    <label class="block text-xs font-bold text-foreground">
                                        Foto Kartu Tanda Penduduk (KTP) <span class="text-destructive">*</span>
                                    </label>
                                    <p class="text-[11px] text-muted-foreground mt-0.5">
                                        Format JPG, JPEG, PNG (Maks 10 MB)
                                    </p>
                                </div>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-primary-500/10 text-primary-600">
                                    Wajib
                                </span>
                            </div>

                            <!-- Input Box -->
                            <div class="relative border-2 border-dashed border-input hover:border-primary-500 rounded-xl p-4 text-center transition-all bg-background/50 group cursor-pointer">
                                <input
                                    type="file"
                                    accept="image/jpeg,image/png,image/jpg"
                                    @change="handleKtpChange"
                                    class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                                />

                                <div v-if="ktpCompressing" class="py-4 space-y-2 flex flex-col items-center justify-center">
                                    <Loader2 class="w-6 h-6 animate-spin text-primary-600" />
                                    <p class="text-xs font-semibold text-foreground">Mengompresi foto KTP...</p>
                                </div>

                                <div v-else-if="ktpInfo" class="space-y-2">
                                    <img
                                        v-if="ktpInfo.previewUrl"
                                        :src="ktpInfo.previewUrl"
                                        alt="Preview KTP"
                                        class="h-28 mx-auto rounded-lg object-contain border border-border shadow-xs"
                                    />
                                    <div class="text-xs space-y-0.5">
                                        <p class="font-semibold text-foreground flex items-center justify-center gap-1">
                                            <CheckCircle class="w-3.5 h-3.5 text-emerald-600" />
                                            Foto KTP Terpilih
                                        </p>
                                        <p class="text-[11px] text-muted-foreground">
                                            {{ ktpInfo.originalSize }} &rarr; <span class="text-emerald-600 font-bold">{{ ktpInfo.compressedSize }}</span> (Terkonversi)
                                        </p>
                                    </div>
                                    <p class="text-[10px] text-primary-600 font-medium hover:underline">
                                        Klik untuk mengganti foto
                                    </p>
                                </div>

                                <div v-else class="py-4 space-y-1.5 flex flex-col items-center justify-center">
                                    <div class="w-10 h-10 rounded-full bg-primary-500/10 flex items-center justify-center text-primary-600 group-hover:scale-110 transition-transform">
                                        <UploadCloud class="w-5 h-5" />
                                    </div>
                                    <p class="text-xs font-semibold text-foreground">
                                        Pilih atau seret foto KTP
                                    </p>
                                    <p class="text-[10px] text-muted-foreground">
                                        Pastikan seluruh teks identitas terbaca jelas
                                    </p>
                                </div>
                            </div>

                            <p v-if="form.errors.dokumen_ktp" class="text-xs text-destructive font-medium">
                                {{ form.errors.dokumen_ktp }}
                            </p>
                        </div>

                        <!-- Upload Perjanjian Kerjasama Bermaterai -->
                        <div class="rounded-xl border border-border p-4 bg-card/60 space-y-3">
                            <div class="flex items-start justify-between">
                                <div>
                                    <label class="block text-xs font-bold text-foreground">
                                        Surat Perjanjian Bermaterai <span class="text-destructive">*</span>
                                    </label>
                                    <p class="text-[11px] text-muted-foreground mt-0.5">
                                        Format JPG, PNG, atau PDF (Maks 10 MB)
                                    </p>
                                </div>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-primary-500/10 text-primary-600">
                                    Wajib
                                </span>
                            </div>

                            <!-- Input Box -->
                            <div class="relative border-2 border-dashed border-input hover:border-primary-500 rounded-xl p-4 text-center transition-all bg-background/50 group cursor-pointer">
                                <input
                                    type="file"
                                    accept="image/jpeg,image/png,image/jpg,application/pdf"
                                    @change="handlePerjanjianChange"
                                    class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                                />

                                <div v-if="perjanjianCompressing" class="py-4 space-y-2 flex flex-col items-center justify-center">
                                    <Loader2 class="w-6 h-6 animate-spin text-primary-600" />
                                    <p class="text-xs font-semibold text-foreground">Memproses berkas perjanjian...</p>
                                </div>

                                <div v-else-if="perjanjianInfo" class="space-y-2">
                                    <template v-if="perjanjianInfo.isPdf">
                                        <div class="h-28 mx-auto rounded-lg bg-red-500/10 border border-red-500/20 flex flex-col items-center justify-center text-red-600">
                                            <FileIcon class="w-8 h-8 mb-1" />
                                            <span class="text-xs font-bold truncate max-w-[200px]">{{ perjanjianInfo.name }}</span>
                                            <span class="text-[10px] text-muted-foreground">Dokumen PDF ({{ perjanjianInfo.originalSize }})</span>
                                        </div>
                                    </template>
                                    <template v-else>
                                        <img
                                            v-if="perjanjianInfo.previewUrl"
                                            :src="perjanjianInfo.previewUrl"
                                            alt="Preview Perjanjian"
                                            class="h-28 mx-auto rounded-lg object-contain border border-border shadow-xs"
                                        />
                                    </template>

                                    <div class="text-xs space-y-0.5">
                                        <p class="font-semibold text-foreground flex items-center justify-center gap-1">
                                            <CheckCircle class="w-3.5 h-3.5 text-emerald-600" />
                                            Berkas Perjanjian Terpilih
                                        </p>
                                        <p v-if="!perjanjianInfo.isPdf" class="text-[11px] text-muted-foreground">
                                            {{ perjanjianInfo.originalSize }} &rarr; <span class="text-emerald-600 font-bold">{{ perjanjianInfo.compressedSize }}</span> (Terkonversi)
                                        </p>
                                    </div>
                                    <p class="text-[10px] text-primary-600 font-medium hover:underline">
                                        Klik untuk mengganti berkas
                                    </p>
                                </div>

                                <div v-else class="py-4 space-y-1.5 flex flex-col items-center justify-center">
                                    <div class="w-10 h-10 rounded-full bg-emerald-500/10 flex items-center justify-center text-emerald-600 group-hover:scale-110 transition-transform">
                                        <UploadCloud class="w-5 h-5" />
                                    </div>
                                    <p class="text-xs font-semibold text-foreground">
                                        Pilih berkas Surat Perjanjian
                                    </p>
                                    <p class="text-[10px] text-muted-foreground">
                                        Foto fisik bermaterai Rp 10.000 atau Scan PDF
                                    </p>
                                </div>
                            </div>

                            <p v-if="form.errors.dokumen_perjanjian" class="text-xs text-destructive font-medium">
                                {{ form.errors.dokumen_perjanjian }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- BAGIAN 4: SYARAT & KETENTUAN DINAMIS       -->
                <!-- ========================================== -->
                <div class="space-y-3 pt-2">
                    <div class="rounded-xl border border-border bg-card/60 p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-foreground flex items-center gap-1.5">
                                <FileText class="w-3.5 h-3.5 text-primary-500" />
                                Syarat & Ketentuan Layanan (v{{ terms?.versi || '1.0' }})
                            </span>
                            <button
                                type="button"
                                @click="openTermsModal"
                                class="text-xs text-primary-600 hover:underline font-semibold flex items-center gap-1"
                            >
                                <span>Baca Lengkap</span>
                                <ExternalLink class="w-3 h-3" />
                            </button>
                        </div>

                        <!-- Preview Snippet Box -->
                        <div class="rounded-lg bg-muted/50 border border-border p-3 text-xs text-muted-foreground max-h-24 overflow-y-auto leading-relaxed whitespace-pre-line font-mono select-none">
                            {{ terms?.konten ? terms.konten.slice(0, 320) + '...' : 'Memuat syarat & ketentuan platform...' }}
                        </div>

                        <!-- Checkbox Persetujuan -->
                        <label class="flex items-start gap-2.5 cursor-pointer pt-1">
                            <input
                                v-model="form.terms"
                                type="checkbox"
                                required
                                class="mt-0.5 rounded border-input text-primary-600 focus:ring-primary-500 w-4 h-4 cursor-pointer"
                            />
                            <span class="text-xs text-muted-foreground leading-relaxed select-none">
                                Saya telah membaca, memahami, dan menyetujui secara sadar seluruh klausul pada
                                <button
                                    type="button"
                                    @click.prevent="openTermsModal"
                                    class="font-semibold text-primary-600 hover:underline inline"
                                >
                                    Syarat & Ketentuan Layanan (v{{ terms?.versi || '1.0' }})
                                </button>
                                serta Kebijakan Privasi platform YZD Trading.
                            </span>
                        </label>

                        <p v-if="form.errors.terms" class="text-xs text-destructive font-medium">
                            {{ form.errors.terms }}
                        </p>
                    </div>

                    <p v-if="form.errors.invite_token" class="text-xs text-destructive font-medium">
                        {{ form.errors.invite_token }}
                    </p>
                </div>

                <!-- Submit Button -->
                <Button
                    type="submit"
                    :disabled="form.processing || ktpCompressing || perjanjianCompressing"
                    class="w-full bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2.5 rounded-lg shadow-sm flex items-center justify-center gap-2 mt-4 transition-colors disabled:opacity-50"
                >
                    <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
                    <UserCheck v-else class="w-4 h-4" />
                    <span>{{ form.processing ? 'Mengirim Pendaftaran & Berkas...' : 'Daftar Akun & Kirim Berkas Legalitas' }}</span>
                </Button>
            </form>

            <div class="text-center pt-2">
                <p class="text-xs text-muted-foreground">
                    Sudah memiliki akun terdaftar?
                    <Link href="/login" class="text-primary-600 dark:text-primary-400 font-semibold hover:underline ml-1">
                        Masuk di sini
                    </Link>
                </p>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- MODAL PEMBACA SYARAT & KETENTUAN LENGKAP    -->
        <!-- ========================================== -->
        <AppModal
            :show="isTermsModalOpen"
            :title="terms?.judul || 'Syarat & Ketentuan Layanan YZD Trading'"
            max-width="2xl"
            @close="closeTermsModal"
        >
            <div class="space-y-4 text-xs sm:text-sm text-foreground leading-relaxed max-h-[60vh] overflow-y-auto pr-2">
                <div class="flex items-center justify-between pb-2 border-b border-border text-xs text-muted-foreground">
                    <span>Versi Dokumen: <strong class="text-foreground">v{{ terms?.versi || '1.0' }}</strong></span>
                    <span>Platform: YZD Trading Management</span>
                </div>
                <div class="whitespace-pre-line text-muted-foreground font-normal leading-relaxed">
                    {{ terms?.konten || 'Tidak ada konten syarat & ketentuan yang tersedia saat ini.' }}
                </div>
            </div>

            <template #footer>
                <Button
                    type="button"
                    class="bg-primary-600 hover:bg-primary-700 text-white text-xs px-4"
                    @click="closeTermsModal"
                >
                    Saya Mengerti
                </Button>
            </template>
        </AppModal>
    </AuthLayout>
</template>
