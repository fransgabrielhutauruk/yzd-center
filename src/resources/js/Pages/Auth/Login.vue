<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { useForm, Link, usePage } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import AppModal from '@/Components/Common/AppModal.vue';
import { Button } from '@/Components/ui/button';
import {
    User,
    Lock,
    Eye,
    EyeOff,
    LogIn,
    Loader2,
    Info,
    CheckCircle2,
    Clock,
    ShieldCheck,
    AlertTriangle,
} from 'lucide-vue-next';
import loginIllustration from '@images/auth/login-illustration.webp';

const props = defineProps({
    lockoutSeconds: {
        type: Number,
        default: 0,
    },
});

const page = usePage();
const showPassword = ref(false);
const showSuccessModal = ref(false);
const registrationData = ref(null);
const remainingSeconds = ref(props.lockoutSeconds || 0);
let timerInterval = null;

const form = useForm({
    login: '',
    password: '',
    remember: false,
});

const startCountdown = (seconds) => {
    if (seconds <= 0) return;
    if (timerInterval) {
        clearInterval(timerInterval);
        timerInterval = null;
    }

    const targetTime = Date.now() + seconds * 1000;
    remainingSeconds.value = seconds;

    timerInterval = setInterval(() => {
        const diff = Math.ceil((targetTime - Date.now()) / 1000);
        if (diff <= 0) {
            clearInterval(timerInterval);
            timerInterval = null;
            remainingSeconds.value = 0;
            if (form.errors.login && form.errors.login.includes('Terlalu banyak percobaan')) {
                form.clearErrors('login');
            }
        } else {
            remainingSeconds.value = diff;
        }
    }, 1000);
};

onMounted(() => {
    if (props.lockoutSeconds > 0) {
        startCountdown(props.lockoutSeconds);
    }
});

onUnmounted(() => {
    if (timerInterval) {
        clearInterval(timerInterval);
        timerInterval = null;
    }
});

watch(
    () => props.lockoutSeconds,
    (newVal) => {
        if (newVal > 0) {
            startCountdown(newVal);
        }
    }
);

watch(
    () => form.errors.login,
    (errorMessage) => {
        if (errorMessage && errorMessage.includes('Terlalu banyak percobaan')) {
            const match = errorMessage.match(/(\d+)\s+detik/i);
            if (match && match[1]) {
                startCountdown(parseInt(match[1], 10));
            }
        }
    }
);

watch(
    () => page.props.flash?.registration_success,
    (data) => {
        if (data) {
            registrationData.value = data;
            showSuccessModal.value = true;
            if (data.username) {
                form.login = data.username;
            } else if (data.email) {
                form.login = data.email;
            }
        }
    },
    { immediate: true }
);

const submit = () => {
    if (remainingSeconds.value > 0) {
        return;
    }

    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <AuthLayout title="Masuk ke Akun" max-width="max-w-md lg:max-w-4xl" :no-card-padding="true">
        <div class="grid grid-cols-1 lg:grid-cols-2 items-stretch">
            <!-- Kolom Kiri: Form Login -->
            <div class="p-6 sm:p-8 lg:p-10 flex flex-col justify-center space-y-6">
                <div class="space-y-1 text-center lg:text-left">
                    <h1 class="text-foreground text-xl font-bold tracking-tight">Masuk ke Sistem</h1>
                    <p class="text-muted-foreground text-xs">
                        Masukkan username/email dan password Anda
                    </p>
                </div>

                <!-- Rate Limit Alert Banner -->
                <div
                    v-if="remainingSeconds > 0"
                    class="flex items-start gap-2.5 rounded-xl border border-destructive/30 bg-destructive/10 p-3.5 text-xs text-destructive dark:border-destructive/40 dark:bg-destructive/15"
                >
                    <AlertTriangle class="mt-0.5 h-4 w-4 flex-shrink-0 text-destructive animate-pulse" />
                    <div class="space-y-0.5">
                        <p class="font-bold">Percobaan Masuk Dibatasi</p>
                        <p class="leading-relaxed">
                            Terlalu banyak percobaan masuk yang gagal. Silakan coba lagi dalam
                            <strong class="font-mono text-sm">{{ remainingSeconds }} detik</strong>.
                        </p>
                    </div>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <!-- Username / Email Field -->
                    <div class="space-y-1.5">
                        <label for="login" class="text-foreground block text-xs font-semibold">
                            Username atau Email
                        </label>
                        <div class="relative">
                            <div
                                class="text-muted-foreground pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"
                            >
                                <User class="h-4 w-4" />
                            </div>
                            <input
                                id="login"
                                v-model="form.login"
                                type="text"
                                autocomplete="username"
                                required
                                :disabled="remainingSeconds > 0 || form.processing"
                                placeholder="Isi username atau email anda"
                                class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border py-2 pr-3 pl-9 text-sm transition-all focus:border-transparent focus:ring-2 focus:outline-none disabled:opacity-60 disabled:cursor-not-allowed"
                            />
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label for="password" class="text-foreground block text-xs font-semibold">
                                Kata Sandi
                            </label>
                        </div>
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
                                autocomplete="current-password"
                                required
                                :disabled="remainingSeconds > 0 || form.processing"
                                placeholder="••••••••"
                                class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border py-2 pr-10 pl-9 text-sm transition-all focus:border-transparent focus:ring-2 focus:outline-none disabled:opacity-60 disabled:cursor-not-allowed"
                                :class="{
                                    'border-destructive focus:ring-destructive': form.errors.login && !form.errors.login.includes('Terlalu banyak percobaan'),
                                }"
                            />
                            <button
                                type="button"
                                :disabled="remainingSeconds > 0 || form.processing"
                                @click="showPassword = !showPassword"
                                class="text-muted-foreground hover:text-foreground absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                                tabindex="-1"
                            >
                                <EyeOff v-if="showPassword" class="h-4 w-4" />
                                <Eye v-else class="h-4 w-4" />
                            </button>
                        </div>
                        <p
                            v-if="form.errors.login && !form.errors.login.includes('Terlalu banyak percobaan')"
                            class="text-destructive text-xs font-medium"
                        >
                            {{ form.errors.login }}
                        </p>
                    </div>

                    <!-- Remember Me Checkbox -->
                    <div class="flex items-center justify-between pt-1">
                        <label class="flex cursor-pointer items-center gap-2" :class="{ 'cursor-not-allowed opacity-60': remainingSeconds > 0 }">
                            <input
                                v-model="form.remember"
                                type="checkbox"
                                :disabled="remainingSeconds > 0 || form.processing"
                                class="border-input text-primary-600 focus:ring-primary-500 h-4 w-4 rounded cursor-pointer disabled:cursor-not-allowed"
                            />
                            <span class="text-muted-foreground text-xs select-none"
                                >Ingat saya di perangkat ini</span
                            >
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <Button
                        type="submit"
                        :disabled="remainingSeconds > 0 || form.processing"
                        class="bg-primary-600 hover:bg-primary-700 mt-2 flex w-full items-center justify-center gap-2 rounded-lg py-2.5 font-bold text-white shadow-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer min-h-[42px]"
                    >
                        <template v-if="remainingSeconds > 0">
                            <Clock class="h-4 w-4" />
                            <span>Tunggu {{ remainingSeconds }} detik</span>
                        </template>
                        <template v-else-if="form.processing">
                            <Loader2 class="h-4 w-4 animate-spin" />
                            <span>Memproses...</span>
                        </template>
                        <template v-else>
                            <LogIn class="h-4 w-4" />
                            <span>Masuk</span>
                        </template>
                    </Button>
                </form>

                <!-- Closed Registration Notice -->
                <div
                    class="flex items-start gap-1.5 rounded-xl border border-amber-500/30 bg-amber-500/10 p-3 text-xs text-amber-900 dark:text-amber-200"
                >
                    <Info class="mt-0.5 h-4 w-4 flex-shrink-0 text-amber-700 dark:text-amber-400" />
                    <p class="leading-relaxed">
                        Pendaftaran saat ini hanya dapat dilakukan melalui tautan undangan resmi dari tim YZD Center.
                    </p>
                </div>
            </div>

            <!-- Kolom Kanan: Gambar Ilustrasi (Hanya Tampil di Desktop, Hidden di HP) -->
            <div
                class="hidden lg:flex flex-col items-center justify-center bg-slate-50/70 dark:bg-slate-900/40"
            >
                <div class="w-full h-full min-h-[440px] max-h-[560px]  overflow-hidden shadow-xs">
                    <img
                        :src="loginIllustration"
                        alt="YZD Center Trading Management Illustration"
                        class="w-full h-full object-cover "
                        loading="eager"
                    />
                </div>
            </div>
        </div>

        <!-- Modal Sukses Pendaftaran Akun -->
        <AppModal
            :show="showSuccessModal"
            max-width="lg"
            :closeable="true"
            :show-header="false"
            @close="showSuccessModal = false"
        >
            <div class="space-y-5 py-2 text-center">
                <!-- Icon Sukses -->
                <div
                    class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-500/10 text-emerald-600 ring-8 ring-emerald-500/5 dark:text-emerald-400"
                >
                    <CheckCircle2 class="h-10 w-10" />
                </div>

                <!-- Judul & Pengantar -->
                <div class="space-y-1.5">
                    <h3 class="text-foreground text-xl font-bold">Pendaftaran Berhasil Dikirim!</h3>
                    <p
                        class="text-muted-foreground mx-auto max-w-md text-xs leading-relaxed sm:text-sm"
                    >
                        Terima kasih,
                        <strong class="text-foreground">{{
                            registrationData?.name || 'Investor'
                        }}</strong
                        >. Data akun Anda telah berhasil terkirim ke sistem
                        <strong class="text-foreground">YZD Center</strong>.
                    </p>
                </div>

                <!-- Detail Rincian Akun & Status -->
                <div class="border-border/80 bg-muted/60 space-y-3 rounded-xl border p-4 text-left">
                    <div class="border-border/60 flex items-center justify-between border-b pb-2.5">
                        <span class="text-muted-foreground text-xs">Status Pendaftaran:</span>
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full border border-amber-500/30 bg-amber-500/15 px-2.5 py-0.5 text-xs font-semibold text-amber-700 dark:text-amber-400"
                        >
                            <Clock class="h-3.5 w-3.5 animate-pulse" />
                            Menunggu Verifikasi
                        </span>
                    </div>

                    <div
                        v-if="registrationData?.username"
                        class="flex items-center justify-between text-xs"
                    >
                        <span class="text-muted-foreground">Username:</span>
                        <span class="text-foreground font-mono font-bold">{{
                            registrationData.username
                        }}</span>
                    </div>

                    <div
                        v-if="registrationData?.email"
                        class="flex items-center justify-between text-xs"
                    >
                        <span class="text-muted-foreground">Email:</span>
                        <span class="text-foreground font-mono font-medium">{{
                            registrationData.email
                        }}</span>
                    </div>
                </div>

                <!-- Tombol Tutup / Mulai Masuk -->
                <div class="pt-2">
                    <Button
                        type="button"
                        class="bg-primary-600 hover:bg-primary-700 w-full rounded-lg py-2.5 font-semibold text-white shadow-sm"
                        @click="showSuccessModal = false"
                    >
                        Saya Mengerti, Kembali ke Halaman Masuk
                    </Button>
                </div>
            </div>
        </AppModal>
    </AuthLayout>
</template>
