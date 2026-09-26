<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { Sun, Moon } from 'lucide-vue-next';
import { useDarkMode } from '@/composables/useDarkMode';
import AppToaster from '@/Components/Common/AppToaster.vue';

defineProps({
    title: {
        type: String,
        default: 'Autentikasi',
    },
    maxWidth: {
        type: String,
        default: 'max-w-md',
    },
    noCardPadding: {
        type: Boolean,
        default: false,
    },
});

const { isDark, toggleDarkMode } = useDarkMode();
</script>

<template>
    <Head :title="title" />
    <AppToaster />

    <div
        class="min-h-screen min-h-dvh flex-1 bg-slate-50/80 dark:bg-slate-950 text-foreground flex flex-col justify-center items-center p-4 sm:p-6 md:p-8 transition-colors duration-200"
    >
        <div :class="['w-full', maxWidth]">
            <!-- Header di atas Form: Logo di Kiri & Dark Mode Toggle di Kanan -->
            <div class="flex items-center justify-between mb-4 px-1">
                <Link href="/" class="flex items-center gap-1 text-xl font-bold tracking-tight hover:opacity-90 transition-opacity">
                    <span class="text-accent-500 font-black text-2xl">YZD</span>
                    <span class="text-primary-600 dark:text-primary-400 font-extrabold">Center</span>
                </Link>

                <!-- Dark Mode Toggle -->
                <button
                    type="button"
                    @click="toggleDarkMode"
                    class="p-2 rounded-xl text-slate-700 dark:text-slate-200 hover:text-foreground hover:bg-slate-200/60 dark:hover:bg-slate-800 transition-colors cursor-pointer min-h-[36px] min-w-[36px] flex items-center justify-center border border-border/60 bg-card shadow-xs"
                    :title="isDark ? 'Mode Terang' : 'Mode Gelap'"
                    aria-label="Toggle Tema"
                >
                    <Sun v-if="isDark" class="w-4 h-4 text-amber-500" />
                    <Moon v-else class="w-4 h-4 text-slate-700" />
                </button>
            </div>

            <!-- Card Utama Form -->
            <div
                :class="[
                    'bg-card text-card-foreground rounded-2xl border border-border/80 shadow-xl backdrop-blur-sm overflow-hidden',
                    noCardPadding ? '' : 'p-6 sm:p-8'
                ]"
            >
                <slot />
            </div>

            <footer class="mt-8 text-center text-xs text-muted-foreground font-medium">
                &copy; 2026 <span class="font-bold text-foreground">YZD Center</span>. All rights reserved.
            </footer>
        </div>
    </div>
</template>
