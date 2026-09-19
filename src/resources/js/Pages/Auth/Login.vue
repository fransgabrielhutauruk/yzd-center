<script setup>
import { ref, watch } from 'vue';
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
    Info,
    CheckCircle2,
    Clock,
    ShieldCheck,
} from 'lucide-vue-next';

const page = usePage();
const showPassword = ref(false);
const showSuccessModal = ref(false);
const registrationData = ref(null);

const form = useForm({
    login: '',
    password: '',
    remember: false,
});

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
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <AuthLayout title="Masuk ke Akun">
        <div class="space-y-6">
            <div class="space-y-1 text-center">
                <h1 class="text-foreground text-xl font-bold tracking-tight">Masuk ke Sistem</h1>
                <p class="text-muted-foreground text-xs">
                    Masukkan username atau email dan password Anda untuk melanjutkan
                </p>
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
                            placeholder="Isi username atau email anda"
                            class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border py-2 pr-3 pl-9 text-sm transition-all focus:border-transparent focus:ring-2 focus:outline-none"
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
                            placeholder="••••••••"
                            class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border py-2 pr-10 pl-9 text-sm transition-all focus:border-transparent focus:ring-2 focus:outline-none"
                            :class="{
                                'border-destructive focus:ring-destructive': form.errors.login,
                            }"
                        />
                        <button
                            type="button"
                            @click="showPassword = !showPassword"
                            class="text-muted-foreground hover:text-foreground absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer"
                            tabindex="-1"
                        >
                            <EyeOff v-if="showPassword" class="h-4 w-4" />
                            <Eye v-else class="h-4 w-4" />
                        </button>
                    </div>
                    <p v-if="form.errors.login" class="text-destructive text-xs font-medium">
                        {{ form.errors.login }}
                    </p>
                </div>

                <!-- Remember Me Checkbox -->
                <div class="flex items-center justify-between pt-1">
                    <label class="flex cursor-pointer items-center gap-2">
                        <input
                            v-model="form.remember"
                            type="checkbox"
                            class="border-input text-primary-600 focus:ring-primary-500 h-4 w-4 rounded cursor-pointer"
                        />
                        <span class="text-muted-foreground text-xs select-none"
                            >Ingat saya di perangkat ini</span
                        >
                    </label>
                </div>

                <!-- Submit Button -->
                <Button
                    type="submit"
                    :disabled="form.processing"
                    class="bg-primary-600 hover:bg-primary-700 mt-2 flex w-full items-center justify-center gap-2 rounded-lg py-2.5 font-bold text-white shadow-sm transition-colors disabled:opacity-50 cursor-pointer min-h-[42px]"
                >
                    <LogIn class="h-4 w-4" />
                    <span>{{ form.processing ? 'Memproses...' : 'Masuk' }}</span>
                </Button>
            </form>

            <!-- Closed Registration Notice -->
            <div
                class="flex items-start gap-2.5 rounded-xl border border-amber-500/30 bg-amber-500/10 p-3 text-xs text-amber-900 dark:text-amber-200"
            >
                <Info class="mt-0.5 h-4 w-4 flex-shrink-0 text-amber-700 dark:text-amber-400" />
                <p class="leading-relaxed">
                    Pendaftaran saat ini hanya dapat dilakukan melalui tautan undangan resmi dari tim YZD Center.
                </p>
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
                        >. Berkas legalitas dan data akun Anda telah berhasil terkirim ke sistem
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
