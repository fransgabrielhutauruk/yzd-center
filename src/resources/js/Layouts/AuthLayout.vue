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
});

const { isDark, toggleDarkMode } = useDarkMode();
</script>

<template>
    <Head :title="title" />
    <AppToaster />

    <div
        class="min-h-screen min-h-dvh flex-1 bg-slate-50 dark:bg-slate-950 text-foreground flex flex-col transition-colors duration-200"
    >
        <!-- Top Navigation -->
        <header class="w-full px-4 sm:px-6 py-3.5 flex items-center justify-between border-b border-border/60 bg-white/80 dark:bg-slate-900/80 backdrop-blur-sm sticky top-0 z-10">
            <Link href="/" class="flex items-center gap-1 text-xl font-bold tracking-tight">
                <span class="text-accent-500 font-black text-2xl">YZD</span>
                <span class="text-primary-600 dark:text-primary-400 font-extrabold">Center</span>
            </Link>

            <div class="flex items-center gap-2 sm:gap-3">
                <!-- Dark Mode Toggle -->
                <button
                    type="button"
                    @click="toggleDarkMode"
                    class="p-2 rounded-lg text-slate-700 dark:text-slate-200 hover:text-foreground hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer min-h-[36px] min-w-[36px] flex items-center justify-center"
                    :title="isDark ? 'Mode Terang' : 'Mode Gelap'"
                    aria-label="Toggle Tema"
                >
                    <Sun v-if="isDark" class="w-5 h-5 text-amber-500" />
                    <Moon v-else class="w-5 h-5 text-slate-700" />
                </button>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 flex flex-col items-center justify-center p-4 sm:p-6 md:p-8">
            <div :class="['w-full', maxWidth]">
                <div class="bg-card text-card-foreground rounded-2xl border border-border/80 shadow-xl p-6 sm:p-8 backdrop-blur-sm">
                    <slot />
                </div>

                <footer class="mt-8 text-center text-xs text-muted-foreground font-medium">
                    &copy; 2026 <span class="font-bold text-foreground">YZD Center</span>. All rights reserved.
                </footer>
            </div>
        </main>
    </div>
</template>
