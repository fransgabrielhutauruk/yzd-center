<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import AppModal from '@/Components/Common/AppModal.vue';
import AppConfirmDialog from '@/Components/Common/AppConfirmDialog.vue';
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
    ArrowRight,
    CheckCircle2,
    Users,
    FileText,
    UploadCloud,
    CheckCircle,
    ExternalLink,
    File as FileIcon,
    Loader2,
    Info,
    Edit3,
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

const currentStep = ref(1);
const showPassword = ref(false);
const isTermsModalOpen = ref(false);

const ktpCompressing = ref(false);
const ktpInfo = ref(null);

const perjanjianCompressing = ref(false);
const perjanjianInfo = ref(null);

const isSubmitted = ref(false);
const showLeaveConfirmation = ref(false);
const pendingNavigation = ref(null);

const steps = [
    { id: 1, title: 'Akun', icon: User, desc: 'Identitas Diri' },
    { id: 2, title: 'Ahli Waris', icon: Users, desc: 'Kontak Darurat' },
    { id: 3, title: 'Berkas', icon: FileText, desc: 'KTP & Perjanjian' },
    { id: 4, title: 'Persetujuan', icon: CheckCircle2, desc: 'T&C & Submit' },
];

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

const hubunganOptions = ['Anak', 'Istri', 'Suami', 'Orang Tua', 'Saudara Kandung', 'Lainnya'];

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
            form.clearErrors('dokumen_ktp');
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
            form.clearErrors('dokumen_perjanjian');
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

// ==========================================
// VALIDASI KLIEN PER LANGKAH (STEP-BY-STEP)
// ==========================================
const validateStep1 = () => {
    form.clearErrors(
        'name',
        'username',
        'email',
        'phone',
        'nik',
        'alamat',
        'password',
        'password_confirmation'
    );
    let valid = true;

    if (!form.name || !form.name.trim()) {
        form.setError('name', 'Nama lengkap wajib diisi.');
        valid = false;
    }
    if (!form.username || !form.username.trim()) {
        form.setError('username', 'Username wajib diisi.');
        valid = false;
    } else if (form.username.length < 3) {
        form.setError('username', 'Username minimal 3 karakter.');
        valid = false;
    } else if (!/^[a-zA-Z0-9_]+$/.test(form.username)) {
        form.setError('username', 'Username hanya boleh huruf, angka, dan garis bawah (_).');
        valid = false;
    }
    if (!form.email || !form.email.trim()) {
        form.setError('email', 'Alamat email wajib diisi.');
        valid = false;
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
        form.setError('email', 'Format alamat email tidak valid.');
        valid = false;
    }
    if (!form.phone || !form.phone.trim()) {
        form.setError('phone', 'Nomor WhatsApp/HP wajib diisi.');
        valid = false;
    } else if (form.phone.length < 9) {
        form.setError('phone', 'Nomor telepon minimal 9 digit.');
        valid = false;
    }
    if (!form.nik || !form.nik.trim()) {
        form.setError('nik', 'Nomor NIK KTP wajib diisi.');
        valid = false;
    } else if (!/^\d{16}$/.test(form.nik)) {
        form.setError('nik', 'Nomor NIK harus tepat 16 digit angka.');
        valid = false;
    }
    if (!form.alamat || !form.alamat.trim()) {
        form.setError('alamat', 'Alamat domisili lengkap wajib diisi.');
        valid = false;
    }
    if (!form.password) {
        form.setError('password', 'Kata sandi wajib diisi.');
        valid = false;
    } else if (form.password.length < 8) {
        form.setError('password', 'Kata sandi minimal 8 karakter.');
        valid = false;
    }
    if (form.password !== form.password_confirmation) {
        form.setError('password_confirmation', 'Konfirmasi kata sandi tidak cocok.');
        valid = false;
    }

    if (!valid) {
        toast.error('Mohon lengkapi seluruh isian data diri dengan benar.');
    }
    return valid;
};

const validateStep2 = () => {
    form.clearErrors(
        'ahli_waris_nama',
        'ahli_waris_hubungan',
        'ahli_waris_phone',
        'ahli_waris_alamat'
    );
    let valid = true;

    if (!form.ahli_waris_nama || !form.ahli_waris_nama.trim()) {
        form.setError('ahli_waris_nama', 'Nama ahli waris wajib diisi.');
        valid = false;
    }
    if (!form.ahli_waris_hubungan) {
        form.setError('ahli_waris_hubungan', 'Pilih hubungan ahli waris.');
        valid = false;
    }
    if (!form.ahli_waris_phone || !form.ahli_waris_phone.trim()) {
        form.setError('ahli_waris_phone', 'Nomor kontak ahli waris wajib diisi.');
        valid = false;
    } else if (form.ahli_waris_phone.trim() === form.phone.trim()) {
        form.setError('ahli_waris_phone', 'Nomor kontak ahli waris wajib berbeda dari nomor Anda.');
        valid = false;
    }
    if (!form.ahli_waris_alamat || !form.ahli_waris_alamat.trim()) {
        form.setError('ahli_waris_alamat', 'Alamat domisili ahli waris wajib diisi.');
        valid = false;
    }

    if (!valid) {
        toast.error('Mohon lengkapi data perlindungan ahli waris.');
    }
    return valid;
};

const validateStep3 = () => {
    form.clearErrors('dokumen_ktp', 'dokumen_perjanjian');
    let valid = true;

    if (ktpCompressing.value || perjanjianCompressing.value) {
        toast.info('Mohon tunggu hingga proses kompresi berkas selesai.');
        return false;
    }
    if (!form.dokumen_ktp) {
        form.setError('dokumen_ktp', 'Foto KTP wajib diunggah.');
        valid = false;
    }
    if (!form.dokumen_perjanjian) {
        form.setError('dokumen_perjanjian', 'Surat Perjanjian Kerjasama wajib diunggah.');
        valid = false;
    }

    if (!valid) {
        toast.error('Mohon unggah kedua berkas legalitas yang diwajibkan.');
    }
    return valid;
};

const nextStep = () => {
    if (currentStep.value === 1 && !validateStep1()) return;
    if (currentStep.value === 2 && !validateStep2()) return;
    if (currentStep.value === 3 && !validateStep3()) return;

    if (currentStep.value < 4) {
        currentStep.value++;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

const jumpToStep = (targetStep) => {
    if (targetStep < currentStep.value) {
        currentStep.value = targetStep;
        window.scrollTo({ top: 0, behavior: 'smooth' });
        return;
    }
    if (targetStep === currentStep.value) return;

    if (targetStep >= 2 && !validateStep1()) {
        currentStep.value = 1;
        return;
    }
    if (targetStep >= 3 && !validateStep2()) {
        currentStep.value = 2;
        return;
    }
    if (targetStep === 4 && !validateStep3()) {
        currentStep.value = 3;
        return;
    }

    currentStep.value = targetStep;
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const hasUnsavedChanges = computed(() => {
    if (isSubmitted.value || form.processing) return false;

    return (
        (Boolean(form.name) && form.name.trim() !== '') ||
        (Boolean(form.username) && form.username.trim() !== '') ||
        (Boolean(form.email) && form.email.trim() !== '') ||
        (Boolean(form.phone) && form.phone.trim() !== '') ||
        (Boolean(form.nik) && form.nik.trim() !== '') ||
        (Boolean(form.alamat) && form.alamat.trim() !== '') ||
        (Boolean(form.password) && form.password !== '') ||
        (Boolean(form.password_confirmation) && form.password_confirmation !== '') ||
        (Boolean(form.ahli_waris_nama) && form.ahli_waris_nama.trim() !== '') ||
        (Boolean(form.ahli_waris_hubungan) && form.ahli_waris_hubungan !== '') ||
        (Boolean(form.ahli_waris_phone) && form.ahli_waris_phone.trim() !== '') ||
        (Boolean(form.ahli_waris_alamat) && form.ahli_waris_alamat.trim() !== '') ||
        form.dokumen_ktp !== null ||
        form.dokumen_perjanjian !== null
    );
});

const handleBeforeUnload = (e) => {
    if (hasUnsavedChanges.value && !form.processing && !isSubmitted.value) {
        e.preventDefault();
        e.returnValue = '';
        return '';
    }
};

const confirmLeave = () => {
    showLeaveConfirmation.value = false;
    if (pendingNavigation.value) {
        const target = pendingNavigation.value;
        pendingNavigation.value = null;
        isSubmitted.value = true;
        router.visit(target.url, target.options);
    }
};

const cancelLeave = () => {
    showLeaveConfirmation.value = false;
    pendingNavigation.value = null;
};

let removeRouterListener = null;

onMounted(() => {
    window.addEventListener('beforeunload', handleBeforeUnload);

    removeRouterListener = router.on('before', (event) => {
        if (event.detail?.visit?.method?.toLowerCase() === 'post') {
            return;
        }

        if (hasUnsavedChanges.value && !isSubmitted.value && !form.processing) {
            event.preventDefault();
            pendingNavigation.value = {
                url: event.detail.visit.url,
                options: event.detail.visit,
            };
            showLeaveConfirmation.value = true;
        }
    });
});

onUnmounted(() => {
    window.removeEventListener('beforeunload', handleBeforeUnload);
    if (removeRouterListener) {
        removeRouterListener();
    }
});

const submit = () => {
    if (!validateStep1()) {
        currentStep.value = 1;
        return;
    }
    if (!validateStep2()) {
        currentStep.value = 2;
        return;
    }
    if (!validateStep3()) {
        currentStep.value = 3;
        return;
    }
    if (!form.terms) {
        toast.error('Anda wajib menyetujui Syarat & Ketentuan Layanan.');
        return;
    }

    isSubmitted.value = true;

    form.post('/register', {
        forceFormData: true,
        onError: (errors) => {
            isSubmitted.value = false;
            const step1Fields = [
                'name',
                'username',
                'email',
                'phone',
                'nik',
                'alamat',
                'password',
                'password_confirmation',
            ];
            const step2Fields = [
                'ahli_waris_nama',
                'ahli_waris_hubungan',
                'ahli_waris_phone',
                'ahli_waris_alamat',
            ];
            const step3Fields = ['dokumen_ktp', 'dokumen_perjanjian'];

            const errorKeys = Object.keys(errors);
            if (errorKeys.some((k) => step1Fields.includes(k))) {
                currentStep.value = 1;
                toast.error('Terdapat kesalahan pada data akun & domisili.');
            } else if (errorKeys.some((k) => step2Fields.includes(k))) {
                currentStep.value = 2;
                toast.error('Terdapat kesalahan pada data ahli waris.');
            } else if (errorKeys.some((k) => step3Fields.includes(k))) {
                currentStep.value = 3;
                toast.error('Terdapat kesalahan pada berkas legalitas.');
            } else {
                currentStep.value = 4;
            }
        },
    });
};
</script>

<template>
    <AuthLayout
        :title="
            invitation.valid
                ? 'Pendaftaran Akun & Verifikasi Legalitas - YZD Center'
                : 'Undangan Tidak Valid - YZD Center'
        "
        :max-width="invitation.valid ? 'max-w-2xl' : 'max-w-md'"
    >
        <!-- Case 1: Undangan Tidak Valid / Kedaluwarsa / Kuota Habis -->
        <div v-if="!invitation.valid" class="space-y-6 py-4 text-center">
            <div
                class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-amber-500/10 text-amber-600 dark:text-amber-400"
            >
                <ShieldAlert class="h-8 w-8" />
            </div>

            <div class="space-y-2">
                <h1 class="text-foreground text-xl font-bold">Tautan Undangan Tidak Valid</h1>
                <p class="text-muted-foreground text-sm leading-relaxed">
                    {{
                        invitation.message ||
                        'Tautan pendaftaran ini tidak dapat digunakan atau telah kedaluwarsa.'
                    }}
                </p>
            </div>

            <div
                class="bg-muted/60 border-border text-muted-foreground space-y-2 rounded-xl border p-4 text-left text-xs"
            >
                <p class="text-foreground flex items-center gap-1.5 font-semibold">
                    <Ticket class="text-primary-500 h-4 w-4" />
                    Kebijakan Pendaftaran Tertutup:
                </p>
                <p class="leading-relaxed">
                    YZD Center menerapkan sistem eksklusif berbasis undangan (*Closed
                    Registration*). Setiap pendaftaran akun wajib menggunakan token tautan resmi
                    yang masih aktif dari Administrator.
                </p>
            </div>

            <div class="pt-2">
                <Link
                    href="/login"
                    class="bg-primary-600 hover:bg-primary-700 inline-flex w-full items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors"
                >
                    <ArrowLeft class="h-4 w-4" />
                    <span>Kembali ke Halaman Masuk</span>
                </Link>
            </div>
        </div>

        <!-- Case 2: Undangan Valid -> Multi-Step Wizard -->
        <div v-else class="space-y-6">
            <!-- Header Judul -->
            <div class="space-y-1 text-center">
                <h1 class="text-foreground text-xl font-bold tracking-tight">
                    Pendaftaran Akun & Legalitas
                </h1>
                <p class="text-muted-foreground text-xs">
                    Selesaikan tahapan pendaftaran secara bertahap untuk aktivasi akun Anda.
                </p>
            </div>

            <!-- Invitation Token Badge Info -->
            <div
                class="bg-primary-500/10 border-primary-500/20 flex items-center justify-between rounded-xl border p-3 text-xs"
            >
                <div class="flex items-center gap-2.5">
                    <div
                        class="bg-primary-500/20 text-primary-600 dark:text-primary-400 rounded-lg p-1.5"
                    >
                        <Ticket class="h-4 w-4" />
                    </div>
                    <div>
                        <span class="text-foreground block font-semibold">{{
                            invitation.invitation?.label
                        }}</span>
                        <span class="text-muted-foreground text-[11px]">Tautan Aktif</span>
                    </div>
                </div>
                <div
                    class="bg-primary-600 flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold text-white shadow-xs"
                >
                    <CheckCircle2 class="h-3.5 w-3.5" />
                    <span>{{ invitation.invitation?.target_role_label }}</span>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- STEPPER INDICATOR (DESKTOP)               -->
            <!-- ========================================== -->
            <div class="border-border/70 hidden grid-cols-4 gap-2 border-b pt-1 pb-2 sm:grid">
                <button
                    v-for="step in steps"
                    :key="step.id"
                    type="button"
                    @click="jumpToStep(step.id)"
                    class="group relative flex flex-col items-center rounded-xl p-2 text-center transition-all"
                    :class="[
                        currentStep === step.id
                            ? 'bg-primary-500/10 text-primary-600 dark:text-primary-400 font-semibold shadow-2xs'
                            : currentStep > step.id
                              ? 'text-foreground hover:bg-muted/60 cursor-pointer'
                              : 'text-muted-foreground cursor-default opacity-60',
                    ]"
                >
                    <div
                        class="mb-1.5 flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold transition-all"
                        :class="[
                            currentStep === step.id
                                ? 'bg-primary-600 ring-primary-500/20 text-white shadow-sm ring-4'
                                : currentStep > step.id
                                  ? 'bg-emerald-600 text-white'
                                  : 'bg-muted text-muted-foreground border-border border',
                        ]"
                    >
                        <CheckCircle2 v-if="currentStep > step.id" class="h-4 w-4" />
                        <span v-else>{{ step.id }}</span>
                    </div>
                    <span class="w-full truncate text-xs leading-tight font-bold">{{
                        step.title
                    }}</span>
                    <span class="text-muted-foreground w-full truncate text-[10px]">{{
                        step.desc
                    }}</span>
                </button>
            </div>

            <!-- ========================================== -->
            <!-- STEPPER INDICATOR (MOBILE)                -->
            <!-- ========================================== -->
            <div class="border-border/70 space-y-2 border-b pb-2 sm:hidden">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-foreground flex items-center gap-1.5 font-bold">
                        <span
                            class="bg-primary-600 flex h-5 w-5 items-center justify-center rounded-full text-[11px] font-bold text-white"
                        >
                            {{ currentStep }}
                        </span>
                        {{ steps[currentStep - 1].title }}
                    </span>
                    <span class="text-muted-foreground font-medium"
                        >Langkah {{ currentStep }} dari 4</span
                    >
                </div>
                <div class="grid grid-cols-4 gap-1.5">
                    <div
                        v-for="step in steps"
                        :key="step.id"
                        class="h-1.5 rounded-full transition-all"
                        :class="[
                            currentStep > step.id
                                ? 'bg-emerald-600'
                                : currentStep === step.id
                                  ? 'bg-primary-600'
                                  : 'bg-muted',
                        ]"
                    ></div>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- ========================================== -->
                <!-- LANGKAH 1: IDENTITAS AKUN                  -->
                <!-- ========================================== -->
                <div v-show="currentStep === 1" class="space-y-4">
                    <div
                        class="border-border text-foreground flex items-center gap-2 border-b pb-2"
                    >
                        <div class="bg-primary-500/10 text-primary-600 rounded-md p-1">
                            <User class="h-4 w-4" />
                        </div>
                        <div>
                            <h2 class="text-sm font-bold tracking-wider uppercase">
                                1. Identitas Akun
                            </h2>
                            <p class="text-muted-foreground text-xs">
                                Lengkapi data identitas pendaftar.
                            </p>
                        </div>
                    </div>

                    <!-- Grid: Nama & Username -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <!-- Nama Lengkap -->
                        <div class="space-y-1.5">
                            <label for="name" class="text-foreground block text-xs font-semibold">
                                Nama Lengkap <span class="text-destructive">*</span>
                            </label>
                            <div class="relative">
                                <div
                                    class="text-muted-foreground pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"
                                >
                                    <User class="h-4 w-4" />
                                </div>
                                <input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    required
                                    placeholder="Contoh: Budi Santoso"
                                    class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border py-2 pr-3 pl-9 text-sm transition-all focus:border-transparent focus:ring-2 focus:outline-none"
                                    :class="{
                                        'border-destructive focus:ring-destructive':
                                            form.errors.name,
                                    }"
                                />
                            </div>
                            <p v-if="form.errors.name" class="text-destructive text-xs font-medium">
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <!-- Username -->
                        <div class="space-y-1.5">
                            <label
                                for="username"
                                class="text-foreground block text-xs font-semibold"
                            >
                                Username <span class="text-destructive">*</span>
                            </label>
                            <div class="relative">
                                <div
                                    class="text-muted-foreground pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"
                                >
                                    <span class="text-xs font-bold">@</span>
                                </div>
                                <input
                                    id="username"
                                    v-model="form.username"
                                    type="text"
                                    required
                                    placeholder="budisantoso"
                                    class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border py-2 pr-3 pl-9 text-sm transition-all focus:border-transparent focus:ring-2 focus:outline-none"
                                    :class="{
                                        'border-destructive focus:ring-destructive':
                                            form.errors.username,
                                    }"
                                />
                            </div>
                            <p
                                v-if="form.errors.username"
                                class="text-destructive text-xs font-medium"
                            >
                                {{ form.errors.username }}
                            </p>
                        </div>
                    </div>

                    <!-- Grid: Email & Phone -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <!-- Email -->
                        <div class="space-y-1.5">
                            <label for="email" class="text-foreground block text-xs font-semibold">
                                Alamat Email <span class="text-destructive">*</span>
                            </label>
                            <div class="relative">
                                <div
                                    class="text-muted-foreground pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"
                                >
                                    <Mail class="h-4 w-4" />
                                </div>
                                <input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    required
                                    placeholder="budi@example.com"
                                    class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border py-2 pr-3 pl-9 text-sm transition-all focus:border-transparent focus:ring-2 focus:outline-none"
                                    :class="{
                                        'border-destructive focus:ring-destructive':
                                            form.errors.email,
                                    }"
                                />
                            </div>
                            <p
                                v-if="form.errors.email"
                                class="text-destructive text-xs font-medium"
                            >
                                {{ form.errors.email }}
                            </p>
                        </div>

                        <!-- Nomor Telepon -->
                        <div class="space-y-1.5">
                            <label for="phone" class="text-foreground block text-xs font-semibold">
                                Nomor WhatsApp / HP <span class="text-destructive">*</span>
                            </label>
                            <div class="relative">
                                <div
                                    class="text-muted-foreground pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"
                                >
                                    <Phone class="h-4 w-4" />
                                </div>
                                <input
                                    id="phone"
                                    v-model="form.phone"
                                    type="tel"
                                    required
                                    placeholder="+628..."
                                    class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border py-2 pr-3 pl-9 text-sm transition-all focus:border-transparent focus:ring-2 focus:outline-none"
                                    :class="{
                                        'border-destructive focus:ring-destructive':
                                            form.errors.phone,
                                    }"
                                />
                            </div>
                            <p
                                v-if="form.errors.phone"
                                class="text-destructive text-xs font-medium"
                            >
                                {{ form.errors.phone }}
                            </p>
                        </div>
                    </div>

                    <!-- NIK KTP -->
                    <div class="space-y-1.5">
                        <label for="nik" class="text-foreground block text-xs font-semibold">
                            Nomor Induk Kependudukan (NIK 16 Digit)
                            <span class="text-destructive">*</span>
                        </label>
                        <div class="relative">
                            <div
                                class="text-muted-foreground pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"
                            >
                                <CreditCard class="h-4 w-4" />
                            </div>
                            <input
                                id="nik"
                                v-model="form.nik"
                                type="text"
                                maxlength="16"
                                required
                                placeholder="3201xxxxxxxxxxxx"
                                class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border py-2 pr-3 pl-9 font-mono text-sm transition-all focus:border-transparent focus:ring-2 focus:outline-none"
                                :class="{
                                    'border-destructive focus:ring-destructive': form.errors.nik,
                                }"
                            />
                        </div>
                        <p v-if="form.errors.nik" class="text-destructive text-xs font-medium">
                            {{ form.errors.nik }}
                        </p>
                    </div>

                    <!-- Alamat Domisili -->
                    <div class="space-y-1.5">
                        <label for="alamat" class="text-foreground block text-xs font-semibold">
                            Alamat Domisili Lengkap <span class="text-destructive">*</span>
                        </label>
                        <div class="relative">
                            <div
                                class="text-muted-foreground pointer-events-none absolute top-2.5 left-0 flex items-start pl-3"
                            >
                                <MapPin class="h-4 w-4" />
                            </div>
                            <textarea
                                id="alamat"
                                v-model="form.alamat"
                                rows="2"
                                required
                                placeholder="Jl. Mawar No. 12, Kelurahan, Kecamatan, Kota"
                                class="bg-background border-input focus:ring-primary-500 w-full resize-none rounded-lg border py-2 pr-3 pl-9 text-sm transition-all focus:border-transparent focus:ring-2 focus:outline-none"
                                :class="{
                                    'border-destructive focus:ring-destructive': form.errors.alamat,
                                }"
                            ></textarea>
                        </div>
                        <p v-if="form.errors.alamat" class="text-destructive text-xs font-medium">
                            {{ form.errors.alamat }}
                        </p>
                    </div>

                    <!-- Grid: Password & Konfirmasi -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <!-- Kata Sandi -->
                        <div class="space-y-1.5">
                            <label
                                for="password"
                                class="text-foreground block text-xs font-semibold"
                            >
                                Kata Sandi <span class="text-destructive">*</span>
                            </label>
                            <div class="relative">
                                <div
                                    class="text-muted-foreground pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"
                                >
                                    <Lock class="h-4 w-4" />
                                </div>
                                <input
                                    id="password"
                                    v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    autocomplete="new-password"
                                    required
                                    placeholder="Min. 8 karakter"
                                    class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border py-2 pr-9 pl-9 text-sm transition-all focus:border-transparent focus:ring-2 focus:outline-none"
                                    :class="{
                                        'border-destructive focus:ring-destructive':
                                            form.errors.password,
                                    }"
                                />
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="text-muted-foreground hover:text-foreground absolute inset-y-0 right-0 flex items-center pr-3"
                                    tabindex="-1"
                                >
                                    <EyeOff v-if="showPassword" class="h-4 w-4" />
                                    <Eye v-else class="h-4 w-4" />
                                </button>
                            </div>
                            <p
                                v-if="form.errors.password"
                                class="text-destructive text-xs font-medium"
                            >
                                {{ form.errors.password }}
                            </p>
                        </div>

                        <!-- Konfirmasi Kata Sandi -->
                        <div class="space-y-1.5">
                            <label
                                for="password_confirmation"
                                class="text-foreground block text-xs font-semibold"
                            >
                                Konfirmasi Kata Sandi <span class="text-destructive">*</span>
                            </label>
                            <div class="relative">
                                <div
                                    class="text-muted-foreground pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"
                                >
                                    <Lock class="h-4 w-4" />
                                </div>
                                <input
                                    id="password_confirmation"
                                    v-model="form.password_confirmation"
                                    :type="showPassword ? 'text' : 'password'"
                                    autocomplete="new-password"
                                    required
                                    placeholder="Ulangi kata sandi"
                                    class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border py-2 pr-3 pl-9 text-sm transition-all focus:border-transparent focus:ring-2 focus:outline-none"
                                />
                            </div>
                            <p
                                v-if="form.errors.password_confirmation"
                                class="text-destructive text-xs font-medium"
                            >
                                {{ form.errors.password_confirmation }}
                            </p>
                        </div>
                    </div>

                    <!-- Footer Navigasi Step 1 -->
                    <div class="flex items-center justify-end pt-4">
                        <Button
                            type="button"
                            @click="nextStep"
                            class="bg-primary-600 hover:bg-primary-700 flex items-center gap-2 rounded-lg px-6 py-2 font-semibold text-white shadow-xs transition-colors"
                        >
                            <span>Lanjutkan ke Data Ahli Waris</span>
                            <ArrowRight class="h-4 w-4" />
                        </Button>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- LANGKAH 2: PERLINDUNGAN AHLI WARIS         -->
                <!-- ========================================== -->
                <div v-show="currentStep === 2" class="space-y-4">
                    <div
                        class="border-border text-foreground flex items-center justify-between border-b pb-2"
                    >
                        <div class="flex items-center gap-2">
                            <div class="bg-accent-500/10 text-accent-600 rounded-md p-1">
                                <Users class="h-4 w-4" />
                            </div>
                            <div>
                                <h2 class="text-sm font-bold tracking-wider uppercase">
                                    2. Perlindungan Ahli Waris
                                </h2>
                                <p class="text-muted-foreground text-xs">
                                    Kontak darurat resmi untuk pengamanan hak dan aset.
                                </p>
                            </div>
                        </div>
                        <span
                            class="text-accent-600 bg-accent-500/10 rounded-full px-2 py-0.5 text-[11px] font-semibold"
                        >
                            Kontak Darurat
                        </span>
                    </div>

                    <div
                        class="bg-muted/60 border-border text-muted-foreground space-y-1 rounded-xl border p-3 text-xs"
                    >
                        <p class="text-foreground flex items-center gap-1.5 font-semibold">
                            <Info class="text-primary-500 h-3.5 w-3.5" />
                            Mengapa Data Ahli Waris Wajib?
                        </p>
                        <p class="leading-relaxed">
                            Data ahli waris diperlukan sebagai perlindungan kepemilikan aset modal
                            dan hak bagi hasil jika terjadi musibah atau keadaan darurat di kemudian
                            hari.
                        </p>
                    </div>

                    <!-- Grid: Nama & Hubungan Ahli Waris -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <!-- Nama Ahli Waris -->
                        <div class="space-y-1.5">
                            <label
                                for="ahli_waris_nama"
                                class="text-foreground block text-xs font-semibold"
                            >
                                Nama Lengkap Ahli Waris <span class="text-destructive">*</span>
                            </label>
                            <div class="relative">
                                <div
                                    class="text-muted-foreground pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"
                                >
                                    <User class="h-4 w-4" />
                                </div>
                                <input
                                    id="ahli_waris_nama"
                                    v-model="form.ahli_waris_nama"
                                    type="text"
                                    required
                                    placeholder="Contoh: Siti Rahmawati"
                                    class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border py-2 pr-3 pl-9 text-sm transition-all focus:ring-2 focus:outline-none"
                                    :class="{
                                        'border-destructive focus:ring-destructive':
                                            form.errors.ahli_waris_nama,
                                    }"
                                />
                            </div>
                            <p
                                v-if="form.errors.ahli_waris_nama"
                                class="text-destructive text-xs font-medium"
                            >
                                {{ form.errors.ahli_waris_nama }}
                            </p>
                        </div>

                        <!-- Hubungan / Relasi Ahli Waris -->
                        <div class="space-y-1.5">
                            <label
                                for="ahli_waris_hubungan"
                                class="text-foreground block text-xs font-semibold"
                            >
                                Hubungan / Relasi <span class="text-destructive">*</span>
                            </label>
                            <select
                                id="ahli_waris_hubungan"
                                v-model="form.ahli_waris_hubungan"
                                required
                                class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border px-3 py-2 text-sm transition-all focus:ring-2 focus:outline-none"
                                :class="{
                                    'border-destructive focus:ring-destructive':
                                        form.errors.ahli_waris_hubungan,
                                }"
                            >
                                <option value="" disabled>-- Pilih Hubungan --</option>
                                <option v-for="opt in hubunganOptions" :key="opt" :value="opt">
                                    {{ opt }}
                                </option>
                            </select>
                            <p
                                v-if="form.errors.ahli_waris_hubungan"
                                class="text-destructive text-xs font-medium"
                            >
                                {{ form.errors.ahli_waris_hubungan }}
                            </p>
                        </div>
                    </div>

                    <!-- Nomor HP Ahli Waris -->
                    <div class="space-y-1.5">
                        <label
                            for="ahli_waris_phone"
                            class="text-foreground block text-xs font-semibold"
                        >
                            Nomor WhatsApp / HP Ahli Waris <span class="text-destructive">*</span>
                        </label>
                        <div class="relative">
                            <div
                                class="text-muted-foreground pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"
                            >
                                <Phone class="h-4 w-4" />
                            </div>
                            <input
                                id="ahli_waris_phone"
                                v-model="form.ahli_waris_phone"
                                type="tel"
                                required
                                placeholder="+628... (Wajib berbeda dari nomor Anda)"
                                class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border py-2 pr-3 pl-9 text-sm transition-all focus:ring-2 focus:outline-none"
                                :class="{
                                    'border-destructive focus:ring-destructive':
                                        form.errors.ahli_waris_phone,
                                }"
                            />
                        </div>
                        <p
                            v-if="form.errors.ahli_waris_phone"
                            class="text-destructive text-xs font-medium"
                        >
                            {{ form.errors.ahli_waris_phone }}
                        </p>
                    </div>

                    <!-- Alamat Ahli Waris -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label
                                for="ahli_waris_alamat"
                                class="text-foreground block text-xs font-semibold"
                            >
                                Alamat Domisili Ahli Waris <span class="text-destructive">*</span>
                            </label>
                            <button
                                type="button"
                                @click="copyAlamatToAhliWaris"
                                class="text-primary-600 text-[11px] font-medium hover:underline"
                            >
                                Samakan dengan alamat saya
                            </button>
                        </div>
                        <div class="relative">
                            <div
                                class="text-muted-foreground pointer-events-none absolute top-2.5 left-0 flex items-start pl-3"
                            >
                                <MapPin class="h-4 w-4" />
                            </div>
                            <textarea
                                id="ahli_waris_alamat"
                                v-model="form.ahli_waris_alamat"
                                rows="2"
                                required
                                placeholder="Alamat domisili lengkap ahli waris"
                                class="bg-background border-input focus:ring-primary-500 w-full resize-none rounded-lg border py-2 pr-3 pl-9 text-sm transition-all focus:ring-2 focus:outline-none"
                                :class="{
                                    'border-destructive focus:ring-destructive':
                                        form.errors.ahli_waris_alamat,
                                }"
                            ></textarea>
                        </div>
                        <p
                            v-if="form.errors.ahli_waris_alamat"
                            class="text-destructive text-xs font-medium"
                        >
                            {{ form.errors.ahli_waris_alamat }}
                        </p>
                    </div>

                    <!-- Footer Navigasi Step 2 -->
                    <div class="flex items-center justify-between pt-4">
                        <Button
                            type="button"
                            variant="outline"
                            @click="prevStep"
                            class="flex items-center gap-1.5 px-4 py-2 text-xs"
                        >
                            <ArrowLeft class="h-4 w-4" />
                            <span>Kembali</span>
                        </Button>

                        <Button
                            type="button"
                            @click="nextStep"
                            class="bg-primary-600 hover:bg-primary-700 flex items-center gap-2 rounded-lg px-6 py-2 font-semibold text-white shadow-xs transition-colors"
                        >
                            <span>Lanjutkan ke Unggah Berkas</span>
                            <ArrowRight class="h-4 w-4" />
                        </Button>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- LANGKAH 3: UNGGAH BERKAS               -->
                <!-- ========================================== -->
                <div v-show="currentStep === 3" class="space-y-4">
                    <div
                        class="border-border text-foreground flex items-center justify-between border-b pb-2"
                    >
                        <div class="flex items-center gap-2">
                            <div class="rounded-md bg-emerald-500/10 p-1 text-emerald-600">
                                <FileText class="h-4 w-4" />
                            </div>
                            <div>
                                <h2 class="text-sm font-bold tracking-wider uppercase">
                                    3. Unggah Berkas
                                </h2>
                                <p class="text-muted-foreground text-xs">
                                    Unggah dokumen KTP dan Surat Perjanjian Kerjasama Bermaterai.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        class="bg-muted/60 border-border text-muted-foreground space-y-1 rounded-xl border p-3 text-xs"
                    >
                        <p class="text-foreground flex items-center gap-1.5 font-semibold">
                            <Info class="text-primary-500 h-3.5 w-3.5" />
                            Petunjuk Berkas:
                        </p>
                        <p class="leading-relaxed">
                            Pastikan foto KTP dan surat perjanjian terlihat jelas, tidak buram, dan
                            seluruh informasi identitas dapat terbaca dengan baik.
                        </p>
                    </div>

                    <!-- Grid: 2 Kolom Unggah Berkas -->
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <!-- Upload KTP -->
                        <div class="border-border bg-card/60 space-y-3 rounded-xl border p-4">
                            <div class="flex items-start justify-between">
                                <div>
                                    <label class="text-foreground block text-xs font-bold">
                                        Foto Kartu Tanda Penduduk (KTP)
                                        <span class="text-destructive">*</span>
                                    </label>
                                    <p class="text-muted-foreground mt-0.5 text-[11px]">
                                        Format JPG, JPEG, PNG (Maks 10 MB)
                                    </p>
                                </div>
                                <span
                                    class="bg-primary-500/10 text-primary-600 rounded px-2 py-0.5 text-[10px] font-bold"
                                >
                                    Wajib
                                </span>
                            </div>

                            <!-- Input Box -->
                            <div
                                class="border-input hover:border-primary-500 bg-background/50 group relative cursor-pointer rounded-xl border-2 border-dashed p-4 text-center transition-all"
                            >
                                <input
                                    type="file"
                                    accept="image/jpeg,image/png,image/jpg"
                                    @change="handleKtpChange"
                                    class="absolute inset-0 z-10 h-full w-full cursor-pointer opacity-0"
                                />

                                <div
                                    v-if="ktpCompressing"
                                    class="flex flex-col items-center justify-center space-y-2 py-4"
                                >
                                    <Loader2 class="text-primary-600 h-6 w-6 animate-spin" />
                                    <p class="text-foreground text-xs font-semibold">
                                        Memproses foto KTP...
                                    </p>
                                </div>

                                <div v-else-if="ktpInfo" class="space-y-2">
                                    <img
                                        v-if="ktpInfo.previewUrl"
                                        :src="ktpInfo.previewUrl"
                                        alt="Preview KTP"
                                        class="border-border mx-auto h-28 rounded-lg border object-contain shadow-xs"
                                    />
                                    <div class="space-y-0.5 text-xs">
                                        <p
                                            class="text-foreground flex items-center justify-center gap-1 font-semibold"
                                        >
                                            <CheckCircle class="h-3.5 w-3.5 text-emerald-600" />
                                            Foto KTP Terpilih
                                        </p>
                                        <p class="text-muted-foreground text-[11px]">
                                            Ukuran berkas: {{ ktpInfo.compressedSize }}
                                        </p>
                                    </div>
                                    <p
                                        class="text-primary-600 text-[10px] font-medium hover:underline"
                                    >
                                        Klik untuk mengganti foto
                                    </p>
                                </div>

                                <div
                                    v-else
                                    class="flex flex-col items-center justify-center space-y-1.5 py-4"
                                >
                                    <div
                                        class="bg-primary-500/10 text-primary-600 flex h-10 w-10 items-center justify-center rounded-full transition-transform group-hover:scale-110"
                                    >
                                        <UploadCloud class="h-5 w-5" />
                                    </div>
                                    <p class="text-foreground text-xs font-semibold">
                                        Pilih atau seret foto KTP
                                    </p>
                                    <p class="text-muted-foreground text-[10px]">
                                        Pastikan seluruh teks identitas terbaca jelas
                                    </p>
                                </div>
                            </div>

                            <p
                                v-if="form.errors.dokumen_ktp"
                                class="text-destructive text-xs font-medium"
                            >
                                {{ form.errors.dokumen_ktp }}
                            </p>
                        </div>

                        <!-- Upload Perjanjian Kerjasama Bermaterai -->
                        <div class="border-border bg-card/60 space-y-3 rounded-xl border p-4">
                            <div class="flex items-start justify-between">
                                <div>
                                    <label class="text-foreground block text-xs font-bold">
                                        Surat Perjanjian Bermaterai
                                        <span class="text-destructive">*</span>
                                    </label>
                                    <p class="text-muted-foreground mt-0.5 text-[11px]">
                                        Format JPG, PNG, atau PDF (Maks 10 MB)
                                    </p>
                                </div>
                                <span
                                    class="bg-primary-500/10 text-primary-600 rounded px-2 py-0.5 text-[10px] font-bold"
                                >
                                    Wajib
                                </span>
                            </div>

                            <!-- Input Box -->
                            <div
                                class="border-input hover:border-primary-500 bg-background/50 group relative cursor-pointer rounded-xl border-2 border-dashed p-4 text-center transition-all"
                            >
                                <input
                                    type="file"
                                    accept="image/jpeg,image/png,image/jpg,application/pdf"
                                    @change="handlePerjanjianChange"
                                    class="absolute inset-0 z-10 h-full w-full cursor-pointer opacity-0"
                                />

                                <div
                                    v-if="perjanjianCompressing"
                                    class="flex flex-col items-center justify-center space-y-2 py-4"
                                >
                                    <Loader2 class="text-primary-600 h-6 w-6 animate-spin" />
                                    <p class="text-foreground text-xs font-semibold">
                                        Memproses berkas perjanjian...
                                    </p>
                                </div>

                                <div v-else-if="perjanjianInfo" class="space-y-2">
                                    <template v-if="perjanjianInfo.isPdf">
                                        <div
                                            class="mx-auto flex h-28 flex-col items-center justify-center rounded-lg border border-red-500/20 bg-red-500/10 text-red-600"
                                        >
                                            <FileIcon class="mb-1 h-8 w-8" />
                                            <span
                                                class="max-w-[200px] truncate text-xs font-bold"
                                                >{{ perjanjianInfo.name }}</span
                                            >
                                            <span class="text-muted-foreground text-[10px]"
                                                >Dokumen PDF ({{
                                                    perjanjianInfo.originalSize
                                                }})</span
                                            >
                                        </div>
                                    </template>
                                    <template v-else>
                                        <img
                                            v-if="perjanjianInfo.previewUrl"
                                            :src="perjanjianInfo.previewUrl"
                                            alt="Preview Perjanjian"
                                            class="border-border mx-auto h-28 rounded-lg border object-contain shadow-xs"
                                        />
                                    </template>

                                    <div class="space-y-0.5 text-xs">
                                        <p
                                            class="text-foreground flex items-center justify-center gap-1 font-semibold"
                                        >
                                            <CheckCircle class="h-3.5 w-3.5 text-emerald-600" />
                                            Berkas Perjanjian Terpilih
                                        </p>
                                        <p
                                            v-if="!perjanjianInfo.isPdf"
                                            class="text-muted-foreground text-[11px]"
                                        >
                                            Ukuran berkas: {{ perjanjianInfo.compressedSize }}
                                        </p>
                                    </div>
                                    <p
                                        class="text-primary-600 text-[10px] font-medium hover:underline"
                                    >
                                        Klik untuk mengganti berkas
                                    </p>
                                </div>

                                <div
                                    v-else
                                    class="flex flex-col items-center justify-center space-y-1.5 py-4"
                                >
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-500/10 text-emerald-600 transition-transform group-hover:scale-110"
                                    >
                                        <UploadCloud class="h-5 w-5" />
                                    </div>
                                    <p class="text-foreground text-xs font-semibold">
                                        Pilih berkas Surat Perjanjian
                                    </p>
                                    <p class="text-muted-foreground text-[10px]">
                                        Hasil berkas atau Scan PDF
                                    </p>
                                </div>
                            </div>

                            <p
                                v-if="form.errors.dokumen_perjanjian"
                                class="text-destructive text-xs font-medium"
                            >
                                {{ form.errors.dokumen_perjanjian }}
                            </p>
                        </div>
                    </div>

                    <!-- Footer Navigasi Step 3 -->
                    <div class="flex items-center justify-between pt-4">
                        <Button
                            type="button"
                            variant="outline"
                            @click="prevStep"
                            class="flex items-center gap-1.5 px-4 py-2 text-xs"
                        >
                            <ArrowLeft class="h-4 w-4" />
                            <span>Kembali</span>
                        </Button>

                        <Button
                            type="button"
                            @click="nextStep"
                            class="bg-primary-600 hover:bg-primary-700 flex items-center gap-2 rounded-lg px-6 py-2 font-semibold text-white shadow-xs transition-colors"
                        >
                            <span>Lanjutkan ke Persetujuan</span>
                            <ArrowRight class="h-4 w-4" />
                        </Button>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- LANGKAH 4: SYARAT, KETENTUAN & SUBMIT      -->
                <!-- ========================================== -->
                <div v-show="currentStep === 4" class="space-y-4">
                    <div
                        class="border-border text-foreground flex items-center gap-2 border-b pb-2"
                    >
                        <div class="bg-primary-500/10 text-primary-600 rounded-md p-1">
                            <CheckCircle2 class="h-4 w-4" />
                        </div>
                        <div>
                            <h2 class="text-sm font-bold tracking-wider uppercase">
                                4. Konfirmasi & Persetujuan Layanan
                            </h2>
                            <p class="text-muted-foreground text-xs">
                                Periksa ringkasan pendaftaran Anda sebelum mengirimkannya.
                            </p>
                        </div>
                    </div>

                    <!-- Ringkasan Data Pendaftaran -->
                    <div
                        class="border-border bg-card/60 divide-border/60 divide-y rounded-xl border text-xs"
                    >
                        <!-- Akun Ringkasan -->
                        <div class="flex items-start justify-between p-3.5">
                            <div class="space-y-1">
                                <p class="text-foreground flex items-center gap-1.5 font-bold">
                                    <User class="text-primary-500 h-3.5 w-3.5" />
                                    <span>{{ form.name || '-' }}</span>
                                    <span class="text-muted-foreground font-normal"
                                        >(@{{ form.username }})</span
                                    >
                                </p>
                                <p class="text-muted-foreground">
                                    Email:
                                    <strong class="text-foreground font-medium">{{
                                        form.email
                                    }}</strong>
                                    &bull; HP:
                                    <strong class="text-foreground font-medium">{{
                                        form.phone
                                    }}</strong>
                                </p>
                                <p class="text-muted-foreground">
                                    NIK:
                                    <span class="text-foreground font-mono font-medium">{{
                                        form.nik
                                    }}</span>
                                </p>
                                <p class="text-muted-foreground max-w-sm truncate sm:max-w-md">
                                    Domisili: {{ form.alamat }}
                                </p>
                            </div>
                            <button
                                type="button"
                                @click="jumpToStep(1)"
                                class="text-primary-600 dark:text-primary-400 ml-2 flex shrink-0 items-center gap-1 text-[11px] font-semibold hover:underline"
                            >
                                <Edit3 class="h-3 w-3" /> Ubah
                            </button>
                        </div>

                        <!-- Ahli Waris Ringkasan -->
                        <div class="flex items-start justify-between p-3.5">
                            <div class="space-y-1">
                                <p class="text-foreground flex items-center gap-1.5 font-bold">
                                    <Users class="text-accent-500 h-3.5 w-3.5" />
                                    <span>Ahli Waris: {{ form.ahli_waris_nama || '-' }}</span>
                                    <span class="text-muted-foreground font-normal"
                                        >({{ form.ahli_waris_hubungan }})</span
                                    >
                                </p>
                                <p class="text-muted-foreground">
                                    Kontak Darurat:
                                    <strong class="text-foreground font-medium">{{
                                        form.ahli_waris_phone
                                    }}</strong>
                                </p>
                                <p class="text-muted-foreground max-w-sm truncate sm:max-w-md">
                                    Alamat: {{ form.ahli_waris_alamat }}
                                </p>
                            </div>
                            <button
                                type="button"
                                @click="jumpToStep(2)"
                                class="text-primary-600 dark:text-primary-400 ml-2 flex shrink-0 items-center gap-1 text-[11px] font-semibold hover:underline"
                            >
                                <Edit3 class="h-3 w-3" /> Ubah
                            </button>
                        </div>

                        <!-- Dokumen Ringkasan -->
                        <div class="flex items-start justify-between p-3.5">
                            <div class="space-y-1">
                                <p class="text-foreground flex items-center gap-1.5 font-bold">
                                    <FileText class="h-3.5 w-3.5 text-emerald-500" />
                                    <span>Berkas Legalitas:</span>
                                </p>
                                <div class="flex flex-wrap items-center gap-2 pt-0.5">
                                    <span
                                        class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[11px] font-semibold"
                                        :class="
                                            form.dokumen_ktp
                                                ? 'bg-emerald-500/10 text-emerald-600'
                                                : 'bg-destructive/10 text-destructive'
                                        "
                                    >
                                        <CheckCircle v-if="form.dokumen_ktp" class="h-3 w-3" />
                                        <span
                                            >Foto KTP:
                                            {{ form.dokumen_ktp ? 'Siap' : 'Belum Dipilih' }}</span
                                        >
                                    </span>
                                    <span
                                        class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[11px] font-semibold"
                                        :class="
                                            form.dokumen_perjanjian
                                                ? 'bg-emerald-500/10 text-emerald-600'
                                                : 'bg-destructive/10 text-destructive'
                                        "
                                    >
                                        <CheckCircle
                                            v-if="form.dokumen_perjanjian"
                                            class="h-3 w-3"
                                        />
                                        <span
                                            >Surat Perjanjian:
                                            {{
                                                form.dokumen_perjanjian ? 'Siap' : 'Belum Dipilih'
                                            }}</span
                                        >
                                    </span>
                                </div>
                            </div>
                            <button
                                type="button"
                                @click="jumpToStep(3)"
                                class="text-primary-600 dark:text-primary-400 ml-2 flex shrink-0 items-center gap-1 text-[11px] font-semibold hover:underline"
                            >
                                <Edit3 class="h-3 w-3" /> Ubah
                            </button>
                        </div>
                    </div>

                    <!-- Syarat & Ketentuan Dinamis -->
                    <div class="border-border bg-card/60 space-y-3 rounded-xl border p-4">
                        <div class="flex items-center justify-between">
                            <span
                                class="text-foreground flex items-center gap-1.5 text-xs font-bold"
                            >
                                <FileText class="text-primary-500 h-3.5 w-3.5" />
                                Syarat & Ketentuan Layanan
                            </span>
                            <button
                                type="button"
                                @click="openTermsModal"
                                class="text-primary-600 dark:text-primary-400 flex items-center gap-1 text-xs font-semibold hover:underline"
                            >
                                <span>Baca Lengkap</span>
                                <ExternalLink class="h-3 w-3" />
                            </button>
                        </div>

                        <!-- Preview Snippet Box -->
                        <div
                            class="bg-muted/50 border-border text-muted-foreground max-h-24 overflow-y-auto rounded-lg border p-3 font-mono text-xs leading-relaxed whitespace-pre-line select-none"
                        >
                            {{
                                terms?.konten
                                    ? terms.konten.slice(0, 320) + '...'
                                    : 'Memuat syarat & ketentuan platform...'
                            }}
                        </div>

                        <!-- Checkbox Persetujuan -->
                        <label class="flex cursor-pointer items-start gap-2.5 pt-1">
                            <input
                                v-model="form.terms"
                                type="checkbox"
                                required
                                class="border-input text-primary-600 focus:ring-primary-500 mt-0.5 h-4 w-4 cursor-pointer rounded"
                            />
                            <span class="text-muted-foreground text-xs leading-relaxed select-none">
                                Saya telah membaca, memahami, dan menyetujui secara sadar seluruh
                                klausul pada
                                <button
                                    type="button"
                                    @click.prevent="openTermsModal"
                                    class="text-primary-600 dark:text-primary-400 inline font-semibold hover:underline"
                                >
                                    Syarat & Ketentuan Layanan
                                </button>
                                serta Kebijakan Privasi platform YZD Center.
                            </span>
                        </label>

                        <p v-if="form.errors.terms" class="text-destructive text-xs font-medium">
                            {{ form.errors.terms }}
                        </p>
                    </div>

                    <p v-if="form.errors.invite_token" class="text-destructive text-xs font-medium">
                        {{ form.errors.invite_token }}
                    </p>

                    <!-- Footer Navigasi Step 4 & Tombol Submit Final -->
                    <div class="flex items-center justify-between pt-4">
                        <Button
                            type="button"
                            variant="outline"
                            @click="prevStep"
                            class="flex items-center gap-1.5 px-4 py-2 text-xs"
                        >
                            <ArrowLeft class="h-4 w-4" />
                            <span>Kembali</span>
                        </Button>

                        <Button
                            type="submit"
                            :disabled="form.processing || ktpCompressing || perjanjianCompressing"
                            class="bg-primary-600 hover:bg-primary-700 flex items-center justify-center gap-2 rounded-lg px-6 py-2.5 text-xs font-semibold text-white shadow-sm transition-colors disabled:opacity-50 sm:text-sm"
                        >
                            <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                            <UserCheck v-else class="h-4 w-4" />
                            <span>{{
                                form.processing
                                    ? 'Mengirim Pendaftaran...'
                                    : 'Kirim'
                            }}</span>
                        </Button>
                    </div>
                </div>
            </form>

            <div class="pt-2 text-center">
                <p class="text-muted-foreground text-xs">
                    Sudah memiliki akun terdaftar?
                    <Link
                        href="/login"
                        class="text-primary-600 dark:text-primary-400 ml-1 font-semibold hover:underline"
                    >
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
            :title="terms?.judul || 'Syarat & Ketentuan Layanan YZD Center'"
            max-width="2xl"
            @close="closeTermsModal"
        >
            <div
                class="text-foreground max-h-[60vh] space-y-4 overflow-y-auto pr-2 text-xs leading-relaxed sm:text-sm"
            >
                <div
                    class="border-border text-muted-foreground flex items-center justify-between border-b pb-2 text-xs"
                >
                    <span class="text-foreground font-medium">Dokumen Resmi Layanan</span>
                    <span>Platform: YZD Center</span>
                </div>
                <div class="text-muted-foreground leading-relaxed font-normal whitespace-pre-line">
                    {{
                        terms?.konten ||
                        'Tidak ada konten syarat & ketentuan yang tersedia saat ini.'
                    }}
                </div>
            </div>

            <template #footer>
                <Button
                    type="button"
                    class="bg-primary-600 hover:bg-primary-700 px-4 text-xs text-white"
                    @click="closeTermsModal"
                >
                    Saya Mengerti
                </Button>
            </template>
        </AppModal>

        <!-- Modal Konfirmasi Meninggalkan Halaman / Navigasi Internal -->
        <AppConfirmDialog
            :show="showLeaveConfirmation"
            title="Formulir Belum Selesai"
            message="Seluruh data pendaftaran dan berkas yang telah Anda isi akan hilang jika Anda meninggalkan halaman ini. Apakah Anda yakin ingin keluar?"
            type="warning"
            confirm-text="Ya, Tinggalkan Halaman"
            cancel-text="Lanjutkan Mendaftar"
            @confirm="confirmLeave"
            @cancel="cancelLeave"
        />
    </AuthLayout>
</template>
