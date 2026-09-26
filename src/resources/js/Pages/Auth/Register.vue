<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
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
    ArrowLeft,
    ArrowRight,
    CheckCircle2,
    Users,
    Briefcase,
    UploadCloud,
    CheckCircle,
    Loader2,
    Edit3,
    AlertCircle,
    UserCheck,
    Calendar,
    AtSign,
} from 'lucide-vue-next';

// === [DEV ONLY: AUTO-FILL] Hapus blok import di bawah ini kelak jika tidak diperlukan lagi ===
import DevRegisterAutoFill from '@/Components/Dev/DevRegisterAutoFill.vue';
// === [END DEV ONLY: AUTO-FILL] ===

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
    privacy: {
        type: Object,
        default: null,
    },
});

const currentStep = ref(1);
const showPassword = ref(false);
const isTermsModalOpen = ref(false);
const isPrivacyModalOpen = ref(false);

const ktpCompressing = ref(false);
const ktpInfo = ref(null);

const isSubmitted = ref(false);
const showLeaveConfirmation = ref(false);
const pendingNavigation = ref(null);

const steps = [
    { id: 1, title: 'Identitas', icon: User, desc: 'Data Diri' },
    { id: 2, title: 'Ahli Waris', icon: Users, desc: 'Kontak Darurat' },
    { id: 3, title: 'Konfirmasi', icon: CheckCircle2, desc: 'Persetujuan' },
];

const form = useForm({
    invite_token: props.token || '',
    name: '',
    username: '',
    email: '',
    phone: '',
    nik: '',
    alamat: '',
    tempat_lahir: '',
    tanggal_lahir: '',
    pekerjaan: '',
    password: '',
    password_confirmation: '',

    // Data Ahli Waris
    ahli_waris_nama: '',
    ahli_waris_hubungan: '',
    ahli_waris_phone: '',
    ahli_waris_alamat: '',

    // Berkas Legalitas (KTP di Step 1)
    dokumen_ktp: null,

    // Persetujuan T&C
    terms: false,

    // Kode Referral Affiliator (Opsional di Step 3)
    referral_code: '',
});

const isCheckingReferral = ref(false);
const referralVerified = ref(false);
const verifiedAffiliatorName = ref('');
const referralCheckError = ref('');

const checkReferralCode = async () => {
    const rawCode = form.referral_code ? form.referral_code.trim() : '';
    if (!rawCode) {
        referralVerified.value = false;
        verifiedAffiliatorName.value = '';
        referralCheckError.value = '';
        return;
    }

    isCheckingReferral.value = true;
    referralCheckError.value = '';
    referralVerified.value = false;

    try {
        const response = await fetch(
            `/register/check-referral?code=${encodeURIComponent(rawCode)}`
        );
        const data = await response.json();

        if (response.ok && data.valid) {
            referralVerified.value = true;
            verifiedAffiliatorName.value = data.affiliator.name;
            referralCheckError.value = '';
        } else {
            referralVerified.value = false;
            verifiedAffiliatorName.value = '';
            referralCheckError.value = data.message || 'Kode referral tidak ditemukan.';
        }
    } catch {
        referralVerified.value = false;
        verifiedAffiliatorName.value = '';
        referralCheckError.value = 'Gagal memeriksa kode referral. Silakan coba lagi.';
    } finally {
        isCheckingReferral.value = false;
    }
};

watch(
    () => form.referral_code,
    (newVal) => {
        if (!newVal) {
            referralVerified.value = false;
            verifiedAffiliatorName.value = '';
            referralCheckError.value = '';
        } else {
            form.referral_code = newVal.toUpperCase().trim();
            referralVerified.value = false;
            referralCheckError.value = '';
        }
    }
);

const hubunganOptions = ['Anak', 'Istri', 'Suami', 'Orang Tua', 'Saudara Kandung', 'Lainnya'];

const handleKtpChange = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    ktpCompressing.value = true;
    try {
        const result = await compressImageFile(file);
        if (result) {
            form.dokumen_ktp = result.file;
            ktpInfo.value = {
                name: file.name,
                previewUrl: result.previewUrl,
                isQualityWarning: result.isQualityWarning || false,
                warningMessage: result.warningMessage || null,
            };
            form.clearErrors('dokumen_ktp');
            if (result.isQualityWarning) {
                toast.warning(
                    'Peringatan Kualitas: Foto KTP terindikasi resolusi rendah atau sedikit buram.'
                );
            }
        }
    } catch (e) {
        toast.error('Gagal memproses foto KTP.');
    } finally {
        ktpCompressing.value = false;
    }
};

const openTermsModal = () => {
    isTermsModalOpen.value = true;
};

const closeTermsModal = () => {
    isTermsModalOpen.value = false;
};

const openPrivacyModal = () => {
    isPrivacyModalOpen.value = true;
};

const closePrivacyModal = () => {
    isPrivacyModalOpen.value = false;
};

// Konten legal tersimpan sebagai HTML Tiptap; dukung fallback plain-text lama
const escapeHtml = (text) =>
    String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

const toLegalHtml = (konten) => {
    if (!konten) return '';
    if (!/<\/?[a-z][\s\S]*>/i.test(konten)) {
        return escapeHtml(konten).replace(/\n/g, '<br />');
    }
    return konten;
};

const termsHtml = computed(
    () =>
        toLegalHtml(props.terms?.konten) ||
        'Tidak ada konten syarat & ketentuan yang tersedia saat ini.'
);

const privacyHtml = computed(
    () =>
        toLegalHtml(props.privacy?.konten) ||
        'Tidak ada konten kebijakan privasi yang tersedia saat ini.'
);

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
        'tempat_lahir',
        'tanggal_lahir',
        'pekerjaan',
        'password',
        'password_confirmation',
        'dokumen_ktp'
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
    if (!form.tempat_lahir || !form.tempat_lahir.trim()) {
        form.setError('tempat_lahir', 'Tempat lahir wajib diisi.');
        valid = false;
    }
    if (!form.tanggal_lahir) {
        form.setError('tanggal_lahir', 'Tanggal lahir wajib diisi.');
        valid = false;
    }
    if (!form.pekerjaan || !form.pekerjaan.trim()) {
        form.setError('pekerjaan', 'Pekerjaan / sumber dana wajib diisi.');
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
    if (!form.dokumen_ktp) {
        form.setError('dokumen_ktp', 'Foto KTP wajib diunggah.');
        valid = false;
    }

    if (!valid) {
        toast.error('Mohon lengkapi seluruh isian data diri & unggah foto KTP dengan benar.');
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

const nextStep = () => {
    if (currentStep.value === 1 && !validateStep1()) return;
    if (currentStep.value === 2 && !validateStep2()) return;

    if (currentStep.value < 3) {
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
    if (targetStep === 3 && !validateStep2()) {
        currentStep.value = 2;
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
        form.dokumen_ktp !== null
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
                'dokumen_ktp',
                'username',
                'email',
                'phone',
                'nik',
                'alamat',
                'tempat_lahir',
                'tanggal_lahir',
                'pekerjaan',
                'password',
                'password_confirmation',
            ];
            const step2Fields = [
                'ahli_waris_nama',
                'ahli_waris_hubungan',
                'ahli_waris_phone',
                'ahli_waris_alamat',
            ];
            const step3Fields = ['terms'];

            const errorKeys = Object.keys(form.errors);

            if (errorKeys.some((k) => step1Fields.includes(k))) {
                currentStep.value = 1;
                toast.error('Terdapat kesalahan pada data akun & domisili.');
            } else if (errorKeys.some((k) => step2Fields.includes(k))) {
                currentStep.value = 2;
                toast.error('Terdapat kesalahan pada data ahli waris.');
            } else if (errorKeys.some((k) => step3Fields.includes(k))) {
                currentStep.value = 3;
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
                    YZD Center menerapkan sistem eksklusif berbasis undangan. Setiap pendaftaran
                    akun wajib menggunakan tautan resmi yang masih aktif dari Administrator.
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
                <h1 class="text-foreground text-xl font-bold tracking-tight">Pendaftaran Akun</h1>
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

            <!-- === [DEV ONLY: AUTO-FILL] Hapus blok komponen di bawah ini kelak jika tidak diperlukan lagi === -->
            <DevRegisterAutoFill
                :form="form"
                :ktp-info="ktpInfo"
                @update:ktp-info="(val) => (ktpInfo = val)"
            />
            <!-- === [END DEV ONLY: AUTO-FILL] === -->

            <!-- ========================================== -->
            <!-- STEPPER INDICATOR (DESKTOP)               -->
            <!-- ========================================== -->
            <div class="border-border/70 hidden grid-cols-3 gap-2 border-b pt-1 pb-2 sm:grid">
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
                        {{ steps[currentStep - 1]?.title }}
                    </span>
                    <span class="text-muted-foreground font-medium"
                        >Langkah {{ currentStep }} dari 3</span
                    >
                </div>
                <div class="grid grid-cols-3 gap-1.5">
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
                            <User class="h-6 w-6" />
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
                                Nama Lengkap (Sesuai KTP) <span class="text-destructive">*</span>
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
                                    <AtSign class="h-4 w-4" />
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
                            Alamat Lengkap <span class="text-destructive">*</span>
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

                    <!-- Grid: Tempat Lahir & Tanggal Lahir -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <!-- Tempat Lahir -->
                        <div class="space-y-1.5">
                            <label
                                for="tempat_lahir"
                                class="text-foreground block text-xs font-semibold"
                            >
                                Tempat Lahir <span class="text-destructive">*</span>
                            </label>
                            <div class="relative">
                                <div
                                    class="text-muted-foreground pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"
                                >
                                    <MapPin class="h-4 w-4" />
                                </div>
                                <input
                                    id="tempat_lahir"
                                    v-model="form.tempat_lahir"
                                    type="text"
                                    required
                                    placeholder="Contoh: Jakarta"
                                    class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border py-2 pr-3 pl-9 text-sm transition-all focus:border-transparent focus:ring-2 focus:outline-none"
                                    :class="{
                                        'border-destructive focus:ring-destructive':
                                            form.errors.tempat_lahir,
                                    }"
                                />
                            </div>
                            <p
                                v-if="form.errors.tempat_lahir"
                                class="text-destructive text-xs font-medium"
                            >
                                {{ form.errors.tempat_lahir }}
                            </p>
                        </div>

                        <!-- Tanggal Lahir -->
                        <div class="space-y-1.5">
                            <label
                                for="tanggal_lahir"
                                class="text-foreground block text-xs font-semibold"
                            >
                                Tanggal Lahir <span class="text-destructive">*</span>
                            </label>
                            <div class="relative">
                                <div
                                    class="text-muted-foreground pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"
                                >
                                    <Calendar class="h-4 w-4" />
                                </div>
                                <input
                                    id="tanggal_lahir"
                                    v-model="form.tanggal_lahir"
                                    type="date"
                                    required
                                    class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border py-2 pr-3 pl-9 text-sm transition-all focus:border-transparent focus:ring-2 focus:outline-none"
                                    :class="{
                                        'border-destructive focus:ring-destructive':
                                            form.errors.tanggal_lahir,
                                    }"
                                />
                            </div>
                            <p
                                v-if="form.errors.tanggal_lahir"
                                class="text-destructive text-xs font-medium"
                            >
                                {{ form.errors.tanggal_lahir }}
                            </p>
                        </div>
                    </div>

                    <!-- Pekerjaan / Sumber Dana -->
                    <div class="space-y-1.5">
                        <label for="pekerjaan" class="text-foreground block text-xs font-semibold">
                            Pekerjaan / Sumber Dana <span class="text-destructive">*</span>
                        </label>
                        <div class="relative">
                            <div
                                class="text-muted-foreground pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"
                            >
                                <Briefcase class="h-4 w-4" />
                            </div>
                            <input
                                id="pekerjaan"
                                v-model="form.pekerjaan"
                                type="text"
                                required
                                placeholder="Contoh: Karyawan Swasta / Wiraswasta"
                                class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border py-2 pr-3 pl-9 text-sm transition-all focus:border-transparent focus:ring-2 focus:outline-none"
                                :class="{
                                    'border-destructive focus:ring-destructive':
                                        form.errors.pekerjaan,
                                }"
                            />
                        </div>
                        <p
                            v-if="form.errors.pekerjaan"
                            class="text-destructive text-xs font-medium"
                        >
                            {{ form.errors.pekerjaan }}
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

                    <!-- Upload KTP -->
                    <div class="border-border bg-card/60 space-y-3 rounded-xl border p-4">
                        <div class="flex items-start justify-between">
                            <div>
                                <label class="text-foreground block text-xs font-bold">
                                    Foto KTP <span class="text-destructive">*</span>
                                </label>
                                <p class="text-muted-foreground mt-0.5 text-[11px]">
                                    JPG, JPEG, PNG (Maks 10 MB)
                                </p>
                            </div>
                            <span
                                class="bg-primary-500/10 text-primary-600 rounded px-2 py-0.5 text-[10px] font-bold"
                                >Wajib</span
                            >
                        </div>

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
                                <p
                                    class="text-foreground flex items-center justify-center gap-1.5 px-2 text-xs font-semibold break-all"
                                >
                                    <CheckCircle class="h-4 w-4 shrink-0 text-emerald-600" />
                                    <span>{{ ktpInfo.name || 'Foto KTP Terpilih' }}</span>
                                </p>
                                <div
                                    v-if="ktpInfo.isQualityWarning"
                                    class="mt-2 flex items-start gap-2.5 rounded-lg border border-amber-500/30 bg-amber-500/10 p-3 text-left text-xs text-amber-800 dark:text-amber-200"
                                >
                                    <AlertCircle
                                        class="mt-0.5 h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400"
                                    />
                                    <p class="text-[11px] leading-relaxed">
                                        {{
                                            ktpInfo.warningMessage ||
                                            'Foto terindikasi buram. Pastikan teks NIK dan nama terbaca jelas.'
                                        }}
                                    </p>
                                </div>
                                <span
                                    class="border-input bg-background/80 text-primary-600 dark:text-primary-100 hover:bg-muted inline-flex items-center gap-1.5 rounded-md border px-2.5 py-1 text-[11px] font-semibold shadow-2xs"
                                >
                                    <Edit3 class="h-3 w-3" /> Ganti Foto KTP
                                </span>
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
                                <Users class="h-6 w-6" />
                            </div>
                            <div>
                                <h2 class="text-sm font-bold tracking-wider uppercase">
                                    2. Perlindungan Ahli Waris
                                </h2>
                                <p class="text-muted-foreground text-xs">
                                    Kontak darurat untuk pengamanan hak & aset.
                                </p>
                            </div>
                        </div>
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

                    <!-- Alamat Domisili Ahli Waris -->
                    <div class="space-y-1.5">
                        <label
                            for="ahli_waris_alamat"
                            class="text-foreground block text-xs font-semibold"
                        >
                            Alamat Ahli Waris <span class="text-destructive">*</span>
                        </label>
                        <div class="relative">
                            <div
                                class="text-muted-foreground pointer-events-none absolute top-2.5 left-0 flex items-center pl-3"
                            >
                                <MapPin class="h-4 w-4" />
                            </div>
                            <textarea
                                id="ahli_waris_alamat"
                                v-model="form.ahli_waris_alamat"
                                rows="2"
                                required
                                placeholder="Jl. Mawar No. 12, Kelurahan, Kecamatan, Kota"
                                class="bg-background border-input focus:ring-primary-500 w-full resize-none rounded-lg border py-2 pr-3 pl-9 text-sm transition-all focus:border-transparent focus:ring-2 focus:outline-none"
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
                            <span>Lanjutkan ke Konfirmasi</span>
                            <ArrowRight class="h-4 w-4" />
                        </Button>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- LANGKAH 3: KONFIRMASI & PERSETUJUAN        -->
                <!-- ========================================== -->
                <div v-show="currentStep === 3" class="space-y-4">
                    <div
                        class="border-border text-foreground flex items-center gap-2.5 border-b pb-2.5"
                    >
                        <div
                            class="bg-primary-500/10 text-primary-600 dark:text-primary-400 shrink-0 rounded-lg p-1.5"
                        >
                            <CheckCircle2 class="h-4 w-4" />
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-foreground text-sm font-bold tracking-wider uppercase">
                                3. Konfirmasi & Persetujuan
                            </h2>
                            <p class="text-muted-foreground text-xs">
                                Periksa kembali ringkasan pendaftaran Anda sebelum mengirimkannya.
                            </p>
                        </div>
                    </div>

                    <!-- Ringkasan Data Pendaftaran -->
                    <div
                        class="border-border bg-card/60 divide-border/60 divide-y overflow-hidden rounded-2xl border text-xs shadow-2xs"
                    >
                        <!-- 1. Akun Ringkasan -->
                        <div class="space-y-3.5 p-4 sm:p-5">
                            <!-- Header Section 1 -->
                            <div
                                class="border-border/50 flex items-center justify-between gap-3 border-b pb-2.5"
                            >
                                <div class="flex min-w-0 items-center gap-2">
                                    <div
                                        class="bg-primary-500/10 text-primary-600 dark:text-primary-400 flex h-6 w-6 shrink-0 items-center justify-center rounded-md"
                                    >
                                        <User class="h-3.5 w-3.5" />
                                    </div>
                                    <span
                                        class="text-foreground truncate text-xs font-bold tracking-wider uppercase"
                                    >
                                        Data Identitas Diri
                                    </span>
                                </div>
                                <button
                                    type="button"
                                    @click="jumpToStep(1)"
                                    class="border-border bg-background text-primary-600 dark:text-primary-400 hover:bg-muted hover:text-primary-700 inline-flex min-h-[30px] shrink-0 cursor-pointer items-center gap-1 rounded-md border px-2.5 py-1 text-[11px] font-semibold shadow-2xs transition-colors"
                                >
                                    <Edit3 class="h-3 w-3" />
                                    <span>Ubah</span>
                                </button>
                            </div>

                            <!-- Content Grid -->
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <!-- Nama -->
                                <div class="min-w-0 space-y-1">
                                    <span
                                        class="text-muted-foreground block text-[11px] font-medium"
                                        >Nama Lengkap (Sesuai KTP)</span
                                    >
                                    <span
                                        class="text-foreground block text-xs font-semibold break-all"
                                        >{{ form.name || '-' }}</span
                                    >
                                </div>

                                <!-- Username -->
                                <div class="min-w-0 space-y-1">
                                    <span
                                        class="text-muted-foreground block text-[11px] font-medium"
                                        >Username</span
                                    >
                                    <span
                                        class="text-foreground block text-xs font-semibold break-all"
                                        >{{ form.username || '-' }}</span
                                    >
                                </div>

                                <!-- Email -->
                                <div class="min-w-0 space-y-1">
                                    <span
                                        class="text-muted-foreground block text-[11px] font-medium"
                                        >Alamat Email</span
                                    >
                                    <span
                                        class="text-foreground block text-xs font-semibold break-all"
                                        >{{ form.email || '-' }}</span
                                    >
                                </div>

                                <!-- Nomor HP -->
                                <div class="min-w-0 space-y-1">
                                    <span
                                        class="text-muted-foreground block text-[11px] font-medium"
                                        >Nomor WhatsApp / HP</span
                                    >
                                    <span
                                        class="text-foreground block font-mono text-xs font-bold tracking-wide"
                                        >{{ form.phone || '-' }}</span
                                    >
                                </div>

                                <!-- NIK -->
                                <div class="min-w-0 space-y-1">
                                    <span
                                        class="text-muted-foreground block text-[11px] font-medium"
                                        >NIK</span
                                    >
                                    <span
                                        class="text-foreground block font-mono text-xs font-bold tracking-wide"
                                        >{{ form.nik || '-' }}</span
                                    >
                                </div>

                                <!-- Tempat, Tanggal Lahir -->
                                <div class="min-w-0 space-y-1">
                                    <span
                                        class="text-muted-foreground block text-[11px] font-medium"
                                        >Tempat, Tanggal Lahir</span
                                    >
                                    <span class="text-foreground block text-xs font-semibold">
                                        {{ form.tempat_lahir || '-'
                                        }}{{ form.tanggal_lahir ? ', ' + form.tanggal_lahir : '' }}
                                    </span>
                                </div>

                                <!-- Pekerjaan -->
                                <div class="min-w-0 space-y-1">
                                    <span
                                        class="text-muted-foreground block text-[11px] font-medium"
                                        >Pekerjaan</span
                                    >
                                    <span class="text-foreground block text-xs font-semibold">{{
                                        form.pekerjaan || '-'
                                    }}</span>
                                </div>

                                <!-- Berkas KTP -->
                                <div class="min-w-0 space-y-1">
                                    <span
                                        class="text-muted-foreground block text-[11px] font-medium"
                                        >Dokumen KTP</span
                                    >
                                    <div>
                                        <span
                                            class="inline-flex items-center gap-1.5 rounded-md border px-2.5 py-1 text-[11px] font-semibold"
                                            :class="
                                                form.dokumen_ktp
                                                    ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-800 dark:text-emerald-300'
                                                    : 'bg-destructive/10 text-destructive border-destructive/30'
                                            "
                                        >
                                            <CheckCircle2
                                                v-if="form.dokumen_ktp"
                                                class="h-3.5 w-3.5 shrink-0"
                                            />
                                            <AlertCircle v-else class="h-3.5 w-3.5 shrink-0" />
                                            <span>{{
                                                form.dokumen_ktp ? 'Siap Diunggah' : 'Belum Dipilih'
                                            }}</span>
                                        </span>
                                    </div>
                                </div>

                                <!-- Alamat Domisili (Full Width) -->
                                <div class="min-w-0 space-y-1">
                                    <span
                                        class="text-muted-foreground block text-[11px] font-medium"
                                        >Alamat</span
                                    >
                                    <p
                                        class="text-foreground text-xs leading-relaxed font-semibold break-words"
                                    >
                                        {{ form.alamat || '-' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Ahli Waris Ringkasan -->
                        <div class="space-y-3.5 p-4 sm:p-5">
                            <!-- Header Section 2 -->
                            <div
                                class="border-border/50 flex items-center justify-between gap-3 border-b pb-2.5"
                            >
                                <div class="flex min-w-0 items-center gap-2">
                                    <div
                                        class="bg-accent-500/10 text-accent-600 dark:text-accent-400 flex h-6 w-6 shrink-0 items-center justify-center rounded-md"
                                    >
                                        <Users class="h-3.5 w-3.5" />
                                    </div>
                                    <span
                                        class="text-foreground truncate text-xs font-bold tracking-wider uppercase"
                                    >
                                        Data Ahli Waris
                                    </span>
                                </div>
                                <button
                                    type="button"
                                    @click="jumpToStep(2)"
                                    class="border-border bg-background text-primary-600 dark:text-primary-400 hover:bg-muted hover:text-primary-700 inline-flex min-h-[30px] shrink-0 cursor-pointer items-center gap-1 rounded-md border px-2.5 py-1 text-[11px] font-semibold shadow-2xs transition-colors"
                                >
                                    <Edit3 class="h-3 w-3" />
                                    <span>Ubah</span>
                                </button>
                            </div>

                            <!-- Content Grid -->
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <!-- Nama Ahli Waris -->
                                <div class="min-w-0 space-y-1">
                                    <span
                                        class="text-muted-foreground block text-[11px] font-medium"
                                        >Nama Lengkap Ahli Waris</span
                                    >
                                    <span
                                        class="text-foreground block text-xs font-semibold break-words"
                                    >
                                        {{ form.ahli_waris_nama || '-' }}
                                    </span>
                                </div>

                                <!-- Hubungan Keluarga -->
                                <div class="min-w-0 space-y-1">
                                    <span
                                        class="text-muted-foreground block text-[11px] font-medium"
                                        >Hubungan / Relasi</span
                                    >
                                    <span
                                        class="text-foreground block text-xs font-semibold capitalize"
                                    >
                                        {{ form.ahli_waris_hubungan || '-' }}
                                    </span>
                                </div>

                                <!-- Kontak Darurat -->
                                <div class="min-w-0 space-y-1">
                                    <span
                                        class="text-muted-foreground block text-[11px] font-medium"
                                        >Nomor WhatsApp / HP Ahli Waris</span
                                    >
                                    <span
                                        class="text-foreground block font-mono text-xs font-bold"
                                    >
                                        {{ form.ahli_waris_phone || '-' }}
                                    </span>
                                </div>

                                <!-- Alamat Domisili Ahli Waris (Full Width) -->
                                <div
                                    class="min-w-0 space-y-1 "
                                >
                                    <span
                                        class="text-muted-foreground block text-[11px] font-medium"
                                        >Alamat Ahli Waris</span
                                    >
                                    <p
                                        class="text-foreground text-xs leading-relaxed font-semibold break-words"
                                    >
                                        {{ form.ahli_waris_alamat || '-' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Kode Referral Affiliator (Opsional) -->
                    <div
                        class="border-border/80 bg-card/60 space-y-2 rounded-2xl border p-4 shadow-2xs sm:p-5"
                    >
                        <div class="flex items-center justify-between">
                            <label
                                for="referral_code"
                                class="text-foreground flex items-center gap-1.5 text-xs font-bold"
                            >
                                <Users class="text-primary-600 dark:text-primary-400 h-3.5 w-3.5" />
                                <span>Kode Referral Affiliator</span>
                                <span class="text-muted-foreground text-[11px] font-normal"
                                    >(Opsional)</span
                                >
                            </label>
                        </div>
                        <p class="text-muted-foreground text-[11px] leading-relaxed">
                            Jika Anda diajak oleh mitra affiliator, masukkan 6 karakter kode
                            referral mereka di bawah ini.
                        </p>
                        <div class="flex items-center gap-2">
                            <div class="relative flex-1">
                                <input
                                    id="referral_code"
                                    v-model="form.referral_code"
                                    type="text"
                                    maxlength="10"
                                    placeholder="Contoh: ABI8K2"
                                    @keydown.enter.prevent="checkReferralCode"
                                    class="border-input bg-background focus:border-primary-500 focus:ring-primary-500/20 h-9 w-full rounded-lg border px-3 py-2 font-mono text-sm tracking-wider uppercase transition-all focus:ring-2 focus:outline-none"
                                    :class="{
                                        'border-destructive':
                                            form.errors.referral_code || referralCheckError,
                                    }"
                                />
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                :disabled="!form.referral_code || isCheckingReferral"
                                @click="checkReferralCode"
                                class="flex h-9 min-h-[36px] shrink-0 cursor-pointer items-center gap-1.5 text-xs"
                            >
                                <Loader2
                                    v-if="isCheckingReferral"
                                    class="h-3.5 w-3.5 animate-spin"
                                />
                                <span>{{ isCheckingReferral ? 'Memeriksa...' : 'Periksa' }}</span>
                            </Button>
                        </div>

                        <!-- Status Verifikasi Sukses -->
                        <div
                            v-if="referralVerified"
                            class="flex items-center gap-2 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-3 py-2 text-xs font-medium text-emerald-800 dark:text-emerald-300"
                        >
                            <span
                                >Terhubung dengan Affiliator:
                                <strong class="text-foreground font-bold">{{
                                    verifiedAffiliatorName
                                }}</strong></span
                            >
                        </div>

                        <!-- Pesan Kesalahan / Validasi -->
                        <p v-if="referralCheckError" class="text-destructive text-xs font-medium">
                            {{ referralCheckError }}
                        </p>
                        <p
                            v-if="form.errors.referral_code"
                            class="text-destructive text-xs font-medium"
                        >
                            {{ form.errors.referral_code }}
                        </p>
                    </div>

                    <!-- Persetujuan Syarat & Ketentuan -->
                    <div
                        class="border-border/80 bg-card/60 space-y-2 rounded-2xl border p-4 shadow-2xs sm:p-5"
                    >
                        <!-- Checkbox Persetujuan -->
                        <label class="flex cursor-pointer items-start gap-3">
                            <input
                                v-model="form.terms"
                                type="checkbox"
                                required
                                class="border-input text-primary-600 focus:ring-primary-500 mt-1 h-4 w-4 shrink-0 cursor-pointer rounded"
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
                                serta
                                <button
                                    type="button"
                                    @click.prevent="openPrivacyModal"
                                    class="text-primary-600 dark:text-primary-400 inline font-semibold hover:underline"
                                >
                                    Kebijakan Privasi
                                </button>
                                platform YZD Center.
                            </span>
                        </label>

                        <p v-if="form.errors.terms" class="text-destructive text-xs font-medium">
                            {{ form.errors.terms }}
                        </p>
                    </div>

                    <p v-if="form.errors.invite_token" class="text-destructive text-xs font-medium">
                        {{ form.errors.invite_token }}
                    </p>

                    <!-- Footer Navigasi Step 3 & Tombol Submit Final -->
                    <div class="flex items-center justify-between gap-3 pt-2">
                        <Button
                            type="button"
                            variant="outline"
                            @click="prevStep"
                            class="flex min-h-[38px] cursor-pointer items-center gap-1.5 px-4 py-2 text-xs"
                        >
                            <ArrowLeft class="h-4 w-4" />
                            <span>Kembali</span>
                        </Button>

                        <Button
                            type="submit"
                            :disabled="form.processing || ktpCompressing"
                            class="bg-primary-600 hover:bg-primary-700 flex min-h-[38px] cursor-pointer items-center justify-center gap-2 rounded-lg px-6 py-2.5 text-xs font-semibold text-white shadow-sm transition-colors disabled:opacity-50 sm:text-sm"
                        >
                            <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                            <UserCheck v-else class="h-4 w-4" />
                            <span>{{
                                form.processing ? 'Mengirim Pendaftaran...' : 'Kirim Pendaftaran'
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
            :title="terms?.judul || 'Syarat & Ketentuan'"
            max-width="2xl"
            @close="closeTermsModal"
        >
            <div
                class="text-foreground max-h-[60vh] overflow-y-auto pr-2 text-xs leading-relaxed sm:text-sm"
            >
                <div
                    class="prose prose-sm max-w-none text-xs leading-relaxed [--tw-prose-body:var(--color-foreground)] [--tw-prose-bold:var(--color-foreground)] [--tw-prose-bullets:var(--color-muted-foreground)] [--tw-prose-counters:var(--color-muted-foreground)] [--tw-prose-headings:var(--color-foreground)] [--tw-prose-quote-borders:var(--color-primary-500)] [--tw-prose-quotes:var(--color-muted-foreground)] sm:text-sm [&_blockquote]:my-2 [&_h2]:mt-5 [&_h2]:mb-1.5 [&_h3]:mt-4 [&_h3]:mb-1 [&_li]:my-1 [&_ol]:my-1.5 [&_p]:my-1.5 [&_ul]:my-1.5 [&>*:first-child]:mt-0"
                    v-html="termsHtml"
                ></div>
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

        <!-- ========================================== -->
        <!-- MODAL PEMBACA KEBIJAKAN PRIVASI LENGKAP     -->
        <!-- ========================================== -->
        <AppModal
            :show="isPrivacyModalOpen"
            :title="privacy?.judul || 'Kebijakan Privasi YZD Center'"
            max-width="2xl"
            @close="closePrivacyModal"
        >
            <div
                class="text-foreground max-h-[60vh] overflow-y-auto pr-2 text-xs leading-relaxed sm:text-sm"
            >
                <div
                    class="prose prose-sm max-w-none text-xs leading-relaxed [--tw-prose-body:var(--color-foreground)] [--tw-prose-bold:var(--color-foreground)] [--tw-prose-bullets:var(--color-muted-foreground)] [--tw-prose-counters:var(--color-muted-foreground)] [--tw-prose-headings:var(--color-foreground)] [--tw-prose-quote-borders:var(--color-primary-500)] [--tw-prose-quotes:var(--color-muted-foreground)] sm:text-sm [&_blockquote]:my-2 [&_h2]:mt-5 [&_h2]:mb-1.5 [&_h3]:mt-4 [&_h3]:mb-1 [&_li]:my-1 [&_ol]:my-1.5 [&_p]:my-1.5 [&_ul]:my-1.5 [&>*:first-child]:mt-0"
                    v-html="privacyHtml"
                ></div>
            </div>

            <template #footer>
                <Button
                    type="button"
                    class="bg-primary-600 hover:bg-primary-700 px-4 text-xs text-white"
                    @click="closePrivacyModal"
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
            confirm-text="Tinggalkan Halaman"
            cancel-text="Lanjutkan Mendaftar"
            @confirm="confirmLeave"
            @cancel="cancelLeave"
        />
    </AuthLayout>
</template>
