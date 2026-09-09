<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Button } from '@/Components/ui/button';
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
    CheckCircle2
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
});

const showPassword = ref(false);

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
    terms: false,
});

const submit = () => {
    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <AuthLayout
        :title="invitation.valid ? 'Pendaftaran Akun - YZD Trading' : 'Undangan Tidak Valid - YZD Trading'"
        :max-width="invitation.valid ? 'max-w-xl' : 'max-w-md'"
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

        <!-- Case 2: Undangan Valid -> Tampilkan Formulir Pendaftaran -->
        <div v-else class="space-y-6">
            <div class="text-center space-y-1">
                <h1 class="text-xl font-bold tracking-tight text-foreground">
                    Pendaftaran Akun Baru
                </h1>
                <p class="text-xs text-muted-foreground">
                    Lengkapi data identitas Anda di bawah ini untuk aktivasi akun.
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
                        <span class="text-muted-foreground">Undangan Terverifikasi</span>
                    </div>
                </div>
                <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-primary-600 text-white shadow-xs">
                    <CheckCircle2 class="w-3.5 h-3.5" />
                    <span>{{ invitation.invitation?.target_role_label }}</span>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <!-- Grid 1: Nama & Username -->
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

                <!-- Grid 2: Email & Phone -->
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

                <!-- Grid 3: Password & Konfirmasi -->
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

                <!-- Terms & Conditions Checkbox -->
                <div class="space-y-1 pt-2">
                    <label class="flex items-start gap-2.5 cursor-pointer">
                        <input
                            v-model="form.terms"
                            type="checkbox"
                            required
                            class="mt-0.5 rounded border-input text-primary-600 focus:ring-primary-500 w-4 h-4"
                        />
                        <span class="text-xs text-muted-foreground leading-relaxed select-none">
                            Saya telah membaca, memahami, dan menyetujui
                            <span class="font-semibold text-foreground">Syarat & Ketentuan Layanan</span>
                            serta <span class="font-semibold text-foreground">Kebijakan Privasi</span> platform YZD Trading (v1.0).
                        </span>
                    </label>
                    <p v-if="form.errors.terms" class="text-xs text-destructive font-medium">
                        {{ form.errors.terms }}
                    </p>
                    <p v-if="form.errors.invite_token" class="text-xs text-destructive font-medium">
                        {{ form.errors.invite_token }}
                    </p>
                </div>

                <!-- Submit Button -->
                <Button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2.5 rounded-lg shadow-sm flex items-center justify-center gap-2 mt-4 transition-colors disabled:opacity-50"
                >
                    <UserCheck class="w-4 h-4" />
                    <span>{{ form.processing ? 'Mendaftarkan Akun...' : 'Daftar Akun Sekarang' }}</span>
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
    </AuthLayout>
</template>
