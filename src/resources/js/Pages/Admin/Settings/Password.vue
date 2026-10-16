<script setup>
import { useForm } from '@inertiajs/vue3';
import { Eye, EyeOff } from 'lucide-vue-next';
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AppButton from '@/Components/Common/AppButton.vue';

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const showCurrent = ref(false);
const showNew = ref(false);
const showConfirm = ref(false);

const submit = () => {
    form.post(route('password.change'), {
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <AdminLayout
        title="Ganti Password"
        subtitle="Perbarui password akun administrator Anda dengan aman. Gunakan password yang kuat dan unik."
        :breadcrumbs="[{ label: 'Pengaturan Sistem' }, { label: 'Ganti Password' }]"
    >
        <div class="max-w-lg">
            <form @submit.prevent="submit" novalidate class="space-y-5">
                <!-- Password Saat Ini -->
                <div class="space-y-1.5">
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">
                        Password Saat Ini
                    </label>
                    <div class="relative">
                        <input
                            v-model="form.current_password"
                            :type="showCurrent ? 'text' : 'password'"
                            autocomplete="current-password"
                            placeholder="Masukkan password saat ini"
                            class="focus:ring-primary-500 dark:focus:ring-primary-600 w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 pr-10 text-sm text-slate-800 transition placeholder:text-slate-400 focus:ring-2 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                            :class="{
                                'border-rose-400 focus:ring-rose-400 dark:border-rose-500':
                                    form.errors.current_password,
                            }"
                        />
                        <button
                            type="button"
                            @click="showCurrent = !showCurrent"
                            class="absolute top-1/2 right-3 -translate-y-1/2 text-slate-400 transition hover:text-slate-600 dark:hover:text-slate-300"
                            tabindex="-1"
                        >
                            <EyeOff v-if="showCurrent" class="h-4 w-4" />
                            <Eye v-else class="h-4 w-4" />
                        </button>
                    </div>
                    <p
                        v-if="form.errors.current_password"
                        class="text-xs font-medium text-rose-500"
                    >
                        {{ form.errors.current_password }}
                    </p>
                </div>

                <!-- Password Baru -->
                <div class="space-y-1.5">
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">
                        Password Baru
                    </label>
                    <div class="relative">
                        <input
                            v-model="form.password"
                            :type="showNew ? 'text' : 'password'"
                            autocomplete="new-password"
                            placeholder="Minimal 8 karakter"
                            class="focus:ring-primary-500 dark:focus:ring-primary-600 w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 pr-10 text-sm text-slate-800 transition placeholder:text-slate-400 focus:ring-2 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                            :class="{
                                'border-rose-400 focus:ring-rose-400 dark:border-rose-500':
                                    form.errors.password,
                            }"
                        />
                        <button
                            type="button"
                            @click="showNew = !showNew"
                            class="absolute top-1/2 right-3 -translate-y-1/2 text-slate-400 transition hover:text-slate-600 dark:hover:text-slate-300"
                            tabindex="-1"
                        >
                            <EyeOff v-if="showNew" class="h-4 w-4" />
                            <Eye v-else class="h-4 w-4" />
                        </button>
                    </div>
                    <p v-if="form.errors.password" class="text-xs font-medium text-rose-500">
                        {{ form.errors.password }}
                    </p>
                </div>

                <!-- Ulangi Password Baru -->
                <div class="space-y-1.5">
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">
                        Ulangi Password Baru
                    </label>
                    <div class="relative">
                        <input
                            v-model="form.password_confirmation"
                            :type="showConfirm ? 'text' : 'password'"
                            autocomplete="new-password"
                            placeholder="Ulangi password baru"
                            class="focus:ring-primary-500 dark:focus:ring-primary-600 w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 pr-10 text-sm text-slate-800 transition placeholder:text-slate-400 focus:ring-2 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                            :class="{
                                'border-rose-400 focus:ring-rose-400 dark:border-rose-500':
                                    form.errors.password_confirmation,
                            }"
                        />
                        <button
                            type="button"
                            @click="showConfirm = !showConfirm"
                            class="absolute top-1/2 right-3 -translate-y-1/2 text-slate-400 transition hover:text-slate-600 dark:hover:text-slate-300"
                            tabindex="-1"
                        >
                            <EyeOff v-if="showConfirm" class="h-4 w-4" />
                            <Eye v-else class="h-4 w-4" />
                        </button>
                    </div>
                    <p
                        v-if="form.errors.password_confirmation"
                        class="text-xs font-medium text-rose-500"
                    >
                        {{ form.errors.password_confirmation }}
                    </p>
                </div>

                <!-- Submit -->
                <div class="flex justify-start pt-2">
                    <AppButton
                        type="submit"
                        :submit="true"
                        label="Simpan Password"
                        :loading="form.processing"
                    />
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
