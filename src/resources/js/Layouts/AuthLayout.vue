<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { Sun, Moon } from 'lucide-vue-next';
import { useDarkMode } from '@/composables/useDarkMode';
import AppAlert from '@/Components/Common/AppAlert.vue';

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

    <div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-foreground flex flex-col transition-colors duration-200">
        <!-- Top Navigation -->
        <header class="w-full px-6 py-4 flex items-center justify-between border-b border-border/50 bg-white/70 dark:bg-slate-900/70 backdrop-blur-sm sticky top-0 z-10">
            <Link href="/" class="flex items-center gap-1 text-xl font-bold tracking-tight">
                <span class="text-accent-500 font-extrabold text-2xl">YZD</span>
                <span class="text-primary-600 dark:text-primary-400">Trading</span>
            </Link>

            <div class="flex items-center gap-3">
                <button
                    type="button"
                    @click="toggleDarkMode"
                    class="p-2 rounded-lg text-muted-foreground hover:text-foreground hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                    :title="isDark ? 'Mode Terang' : 'Mode Gelap'"
                    aria-label="Toggle Tema"
                >
                    <Sun v-if="isDark" class="w-5 h-5 text-amber-400" />
                    <Moon v-else class="w-5 h-5 text-slate-600" />
                </button>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 flex flex-col items-center justify-center p-4 sm:p-6 md:p-8">
            <div :class="['w-full', maxWidth]">
                <AppAlert />

                <div class="bg-card text-card-foreground rounded-2xl border border-border/80 shadow-xl p-6 sm:p-8 backdrop-blur-sm">
                    <slot />
                </div>

                <footer class="mt-8 text-center text-xs text-muted-foreground">
                    &copy; 2026 <span class="font-semibold text-foreground">YZD Trading Management</span>. Hak cipta dilindungi.
                </footer>
            </div>
        </main>
    </div>
</template>
