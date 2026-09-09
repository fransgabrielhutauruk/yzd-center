<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Button } from '@/Components/ui/button';
import { User, Lock, Eye, EyeOff, LogIn, Info } from 'lucide-vue-next';

const showPassword = ref(false);

const form = useForm({
    login: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <AuthLayout title="Masuk ke Akun - YZD Trading">
        <div class="space-y-6">
            <div class="text-center space-y-1">
                <h1 class="text-xl font-bold tracking-tight text-foreground">
                    Masuk ke Sistem
                </h1>
                <p class="text-xs text-muted-foreground">
                    Masukkan username atau email Anda untuk melanjutkan
                </p>
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <!-- Username / Email Field -->
                <div class="space-y-1.5">
                    <label for="login" class="block text-xs font-semibold text-foreground">
                        Username atau Email
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-muted-foreground">
                            <User class="w-4 h-4" />
                        </div>
                        <input
                            id="login"
                            v-model="form.login"
                            type="text"
                            autocomplete="username"
                            required
                            placeholder="admin atau nama@email.com"
                            class="w-full pl-9 pr-3 py-2 text-sm bg-background border border-input rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                            :class="{ 'border-destructive focus:ring-destructive': form.errors.login }"
                        />
                    </div>
                    <p v-if="form.errors.login" class="text-xs text-destructive font-medium">
                        {{ form.errors.login }}
                    </p>
                </div>

                <!-- Password Field -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label for="password" class="block text-xs font-semibold text-foreground">
                            Kata Sandi
                        </label>
                    </div>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-muted-foreground">
                            <Lock class="w-4 h-4" />
                        </div>
                        <input
                            id="password"
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            autocomplete="current-password"
                            required
                            placeholder="••••••••"
                            class="w-full pl-9 pr-10 py-2 text-sm bg-background border border-input rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
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

                <!-- Remember Me Checkbox -->
                <div class="flex items-center justify-between pt-1">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input
                            v-model="form.remember"
                            type="checkbox"
                            class="rounded border-input text-primary-600 focus:ring-primary-500 w-4 h-4"
                        />
                        <span class="text-xs text-muted-foreground select-none">Ingat saya di perangkat ini</span>
                    </label>
                </div>

                <!-- Submit Button -->
                <Button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2.5 rounded-lg shadow-sm flex items-center justify-center gap-2 mt-2 transition-colors disabled:opacity-50"
                >
                    <LogIn class="w-4 h-4" />
                    <span>{{ form.processing ? 'Memproses...' : 'Masuk' }}</span>
                </Button>
            </form>

            <!-- Closed Registration Notice -->
            <div class="rounded-xl p-3 bg-amber-500/10 border border-amber-500/20 text-amber-800 dark:text-amber-300 text-xs flex items-start gap-2.5">
                <Info class="w-4 h-4 flex-shrink-0 mt-0.5 text-amber-600 dark:text-amber-400" />
                <p class="leading-relaxed">
                    Pendaftaran akun baru bersifat tertutup. Anda memerlukan <strong>Tautan Undangan</strong> resmi dari Administrator untuk mendaftar.
                </p>
            </div>
        </div>
    </AuthLayout>
</template>
