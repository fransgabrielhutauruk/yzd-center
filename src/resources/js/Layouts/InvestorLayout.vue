<script setup>
import { ref, computed } from 'vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import {
    LayoutDashboard,
    Link2,
    BarChart2,
    QrCode,
    UserPlus,
    Users,
    Send,
    BookOpen,
    HelpCircle,
    Bell,
    Sun,
    Moon,
    LogOut,
    Menu,
    X,
    ChevronDown,
    User as UserIcon,
    Globe,
    FileCheck,
} from 'lucide-vue-next';
import { useDarkMode } from '@/composables/useDarkMode';
import AppToaster from '@/Components/Common/AppToaster.vue';

defineProps({
    title: {
        type: String,
        default: 'Dasbor Investor',
    },
});

const page = usePage();
const { isDark, toggleDarkMode } = useDarkMode();
const isMobileSidebarOpen = ref(false);
const isUserDropdownOpen = ref(false);
const isLangDropdownOpen = ref(false);

const user = computed(() => page.props.auth?.user || {});
const isAffiliator = computed(() => user.value?.role === 'affiliator');

const currentLocale = computed(() => page.props.locale?.current || 'id');

const userInitials = computed(() => {
    const name = user.value?.name || 'User';
    const parts = name.trim().split(' ');
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return name.slice(0, 2).toUpperCase();
});

const isActive = (href) => {
    return page.url.startsWith(href);
};

const logout = () => {
    router.post('/logout');
};

const switchLocale = (lang) => {
    isLangDropdownOpen.value = false;
    router.post('/locale', { locale: lang }, { preserveState: true });
};
</script>

<template>
    <Head :title="title" />
    <AppToaster />

    <div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-foreground flex transition-colors duration-200">
        <!-- Backdrop Mobile Sidebar -->
        <transition
            enter-active-class="transition-opacity ease-linear duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity ease-linear duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isMobileSidebarOpen"
                @click="isMobileSidebarOpen = false"
                class="fixed inset-0 z-40 bg-black/60 md:hidden backdrop-blur-xs"
            ></div>
        </transition>

        <!-- Sidebar Component -->
        <aside
            :class="[
                'fixed md:static inset-y-0 left-0 z-50 w-64 bg-primary-600 dark:bg-slate-900 text-white flex flex-col transition-transform duration-300 ease-in-out border-r border-primary-700/50 dark:border-slate-800 shadow-xl md:shadow-none md:translate-x-0',
                isMobileSidebarOpen ? 'translate-x-0' : '-translate-x-full'
            ]"
        >
            <!-- Logo Brand -->
            <div class="h-16 px-6 flex items-center justify-between border-b border-white/15 dark:border-slate-800">
                <Link href="/investor/dashboard" class="flex items-center gap-1">
                    <span class="text-accent-500 font-extrabold text-2xl tracking-tight">YZD</span>
                    <span class="text-white font-bold text-xl tracking-tight">Trading</span>
                    <span
                        v-if="isAffiliator"
                        class="ml-2 text-[10px] font-semibold bg-accent-500/20 text-accent-300 border border-accent-500/40 px-1.5 py-0.5 rounded"
                    >
                        AFFILIATOR
                    </span>
                </Link>

                <button
                    type="button"
                    @click="isMobileSidebarOpen = false"
                    class="md:hidden p-1.5 text-white/70 hover:text-white rounded-lg hover:bg-white/10"
                >
                    <X class="w-5 h-5" />
                </button>
            </div>

            <!-- Sidebar Navigation Links -->
            <nav class="flex-1 overflow-y-auto px-4 py-5 space-y-6 text-sm font-medium">
                <!-- Menu Utama -->
                <div class="space-y-1.5">
                    <p class="px-3 text-[11px] font-semibold uppercase tracking-wider text-white/50">
                        Menu Utama
                    </p>
                    <div class="space-y-1">
                        <Link
                            href="/investor/dashboard"
                            @click="isMobileSidebarOpen = false"
                            :class="[
                                'flex items-center gap-3 px-3 py-2 rounded-lg transition-colors group',
                                isActive('/investor/dashboard')
                                    ? 'bg-primary-700 dark:bg-primary-900/60 text-white font-semibold shadow-inner'
                                    : 'text-white/80 hover:text-white hover:bg-white/10 dark:hover:bg-slate-800/80'
                            ]"
                        >
                            <LayoutDashboard class="w-4 h-4 text-white/75 group-hover:text-white" />
                            <span>Dasbor Portofolio</span>
                        </Link>

                        <Link
                            href="/investor/copytrade"
                            @click="isMobileSidebarOpen = false"
                            :class="[
                                'flex items-center gap-3 px-3 py-2 rounded-lg transition-colors group',
                                isActive('/investor/copytrade')
                                    ? 'bg-primary-700 dark:bg-primary-900/60 text-white font-semibold shadow-inner'
                                    : 'text-white/80 hover:text-white hover:bg-white/10 dark:hover:bg-slate-800/80'
                            ]"
                        >
                            <Link2 class="w-4 h-4 text-white/75 group-hover:text-white" />
                            <span>Mulai Copytrade</span>
                        </Link>

                        <Link
                            href="/investor/statistik"
                            @click="isMobileSidebarOpen = false"
                            :class="[
                                'flex items-center gap-3 px-3 py-2 rounded-lg transition-colors group',
                                isActive('/investor/statistik')
                                    ? 'bg-primary-700 dark:bg-primary-900/60 text-white font-semibold shadow-inner'
                                    : 'text-white/80 hover:text-white hover:bg-white/10 dark:hover:bg-slate-800/80'
                            ]"
                        >
                            <BarChart2 class="w-4 h-4 text-white/75 group-hover:text-white" />
                            <span>Statistik Portofolio</span>
                        </Link>

                        <Link
                            href="/investor/kyc"
                            @click="isMobileSidebarOpen = false"
                            :class="[
                                'flex items-center gap-3 px-3 py-2 rounded-lg transition-colors group',
                                isActive('/investor/kyc')
                                    ? 'bg-primary-700 dark:bg-primary-900/60 text-white font-semibold shadow-inner'
                                    : 'text-white/80 hover:text-white hover:bg-white/10 dark:hover:bg-slate-800/80'
                            ]"
                        >
                            <FileCheck class="w-4 h-4 text-white/75 group-hover:text-white" />
                            <span>Status KYC & Legalitas</span>
                        </Link>
                    </div>
                </div>

                <!-- Bagian Khusus Afiliator -->
                <template v-if="isAffiliator">
                    <div class="space-y-1.5">
                        <p class="px-3 text-[11px] font-semibold uppercase tracking-wider text-white/50">
                            Promosi & Referral
                        </p>
                        <div class="space-y-1">
                            <Link
                                href="/affiliator/referral-code"
                                @click="isMobileSidebarOpen = false"
                                :class="[
                                    'flex items-center gap-3 px-3 py-2 rounded-lg transition-colors group',
                                    isActive('/affiliator/referral-code')
                                        ? 'bg-primary-700 dark:bg-primary-900/60 text-white font-semibold shadow-inner'
                                        : 'text-white/80 hover:text-white hover:bg-white/10 dark:hover:bg-slate-800/80'
                                ]"
                            >
                                <QrCode class="w-4 h-4 text-white/75 group-hover:text-white" />
                                <span>Kode Referral</span>
                            </Link>

                            <Link
                                href="/affiliator/klaim-susulan"
                                @click="isMobileSidebarOpen = false"
                                :class="[
                                    'flex items-center gap-3 px-3 py-2 rounded-lg transition-colors group',
                                    isActive('/affiliator/klaim-susulan')
                                        ? 'bg-primary-700 dark:bg-primary-900/60 text-white font-semibold shadow-inner'
                                        : 'text-white/80 hover:text-white hover:bg-white/10 dark:hover:bg-slate-800/80'
                                ]"
                            >
                                <UserPlus class="w-4 h-4 text-white/75 group-hover:text-white" />
                                <span>Klaim Referral Susulan</span>
                            </Link>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <p class="px-3 text-[11px] font-semibold uppercase tracking-wider text-white/50">
                            Jaringan & Komisi
                        </p>
                        <div class="space-y-1">
                            <Link
                                href="/affiliator/downline"
                                @click="isMobileSidebarOpen = false"
                                :class="[
                                    'flex items-center gap-3 px-3 py-2 rounded-lg transition-colors group',
                                    isActive('/affiliator/downline')
                                        ? 'bg-primary-700 dark:bg-primary-900/60 text-white font-semibold shadow-inner'
                                        : 'text-white/80 hover:text-white hover:bg-white/10 dark:hover:bg-slate-800/80'
                                ]"
                            >
                                <Users class="w-4 h-4 text-white/75 group-hover:text-white" />
                                <span>Daftar Downline</span>
                            </Link>

                            <Link
                                href="/affiliator/klaim-komisi"
                                @click="isMobileSidebarOpen = false"
                                :class="[
                                    'flex items-center gap-3 px-3 py-2 rounded-lg transition-colors group',
                                    isActive('/affiliator/klaim-komisi')
                                        ? 'bg-primary-700 dark:bg-primary-900/60 text-white font-semibold shadow-inner'
                                        : 'text-white/80 hover:text-white hover:bg-white/10 dark:hover:bg-slate-800/80'
                                ]"
                            >
                                <Send class="w-4 h-4 text-white/75 group-hover:text-white" />
                                <span>Ajukan Klaim Komisi</span>
                            </Link>
                        </div>
                    </div>
                </template>

                <!-- Edukasi & Bantuan -->
                <div class="space-y-1.5">
                    <p class="px-3 text-[11px] font-semibold uppercase tracking-wider text-white/50">
                        Edukasi & Bantuan
                    </p>
                    <div class="space-y-1">
                        <Link
                            href="/edukasi/kamus"
                            @click="isMobileSidebarOpen = false"
                            :class="[
                                'flex items-center gap-3 px-3 py-2 rounded-lg transition-colors group',
                                isActive('/edukasi/kamus')
                                    ? 'bg-primary-700 dark:bg-primary-900/60 text-white font-semibold shadow-inner'
                                    : 'text-white/80 hover:text-white hover:bg-white/10 dark:hover:bg-slate-800/80'
                            ]"
                        >
                            <BookOpen class="w-4 h-4 text-white/75 group-hover:text-white" />
                            <span>Kamus Forex</span>
                        </Link>

                        <Link
                            href="/edukasi/faq"
                            @click="isMobileSidebarOpen = false"
                            :class="[
                                'flex items-center gap-3 px-3 py-2 rounded-lg transition-colors group',
                                isActive('/edukasi/faq')
                                    ? 'bg-primary-700 dark:bg-primary-900/60 text-white font-semibold shadow-inner'
                                    : 'text-white/80 hover:text-white hover:bg-white/10 dark:hover:bg-slate-800/80'
                            ]"
                        >
                            <HelpCircle class="w-4 h-4 text-white/75 group-hover:text-white" />
                            <span>Pusat Bantuan (FAQ)</span>
                        </Link>
                    </div>
                </div>
            </nav>

            <!-- Bottom User Profile Area -->
            <div class="p-4 border-t border-white/15 dark:border-slate-800 bg-primary-700/50 dark:bg-slate-900/80 flex items-center justify-between">
                <div class="flex items-center gap-3 truncate">
                    <div class="w-9 h-9 rounded-full bg-accent-500 text-white font-bold flex items-center justify-center text-sm shadow-sm flex-shrink-0">
                        {{ userInitials }}
                    </div>
                    <div class="truncate text-xs">
                        <p class="font-semibold text-white truncate">{{ user?.name || 'Investor' }}</p>
                        <p class="text-white/60 truncate capitalize">{{ user?.role || 'Investor' }}</p>
                    </div>
                </div>

                <button
                    type="button"
                    @click="logout"
                    class="p-2 text-white/70 hover:text-white hover:bg-white/10 rounded-lg transition-colors"
                    title="Keluar"
                >
                    <LogOut class="w-4 h-4" />
                </button>
            </div>
        </aside>

        <!-- Main Wrapper -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Top Navbar -->
            <header class="h-16 px-4 sm:px-6 bg-white dark:bg-slate-900 border-b-2 border-accent-500 flex items-center justify-between shadow-xs sticky top-0 z-30 transition-colors">
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        @click="isMobileSidebarOpen = true"
                        class="md:hidden p-2 rounded-lg text-muted-foreground hover:text-foreground hover:bg-slate-100 dark:hover:bg-slate-800"
                        aria-label="Buka Menu"
                    >
                        <Menu class="w-5 h-5" />
                    </button>

                    <h1 class="text-base sm:text-lg font-semibold text-foreground truncate">
                        {{ title }}
                    </h1>
                </div>

                <!-- Right Header Actions -->
                <div class="flex items-center gap-2 sm:gap-4">
                    <!-- Language Selector Dropdown -->
                    <div class="relative">
                        <button
                            type="button"
                            @click="isLangDropdownOpen = !isLangDropdownOpen"
                            class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-muted-foreground hover:text-foreground hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors uppercase"
                            aria-label="Pilih Bahasa"
                        >
                            <Globe class="w-4 h-4" />
                            <span>{{ currentLocale }}</span>
                            <ChevronDown class="w-3.5 h-3.5" />
                        </button>

                        <div
                            v-if="isLangDropdownOpen"
                            @click="isLangDropdownOpen = false"
                            class="fixed inset-0 z-40"
                        ></div>

                        <div
                            v-if="isLangDropdownOpen"
                            class="absolute right-0 mt-2 w-32 bg-card rounded-xl border border-border shadow-lg py-1 z-50 text-xs font-medium"
                        >
                            <button
                                type="button"
                                @click="switchLocale('id')"
                                :class="[
                                    'w-full text-left px-3 py-1.5 flex items-center justify-between hover:bg-slate-100 dark:hover:bg-slate-800',
                                    currentLocale === 'id' ? 'font-bold text-primary-600 dark:text-primary-400' : 'text-foreground'
                                ]"
                            >
                                <span>Indonesia</span>
                                <span v-if="currentLocale === 'id'">✓</span>
                            </button>
                            <button
                                type="button"
                                @click="switchLocale('en')"
                                :class="[
                                    'w-full text-left px-3 py-1.5 flex items-center justify-between hover:bg-slate-100 dark:hover:bg-slate-800',
                                    currentLocale === 'en' ? 'font-bold text-primary-600 dark:text-primary-400' : 'text-foreground'
                                ]"
                            >
                                <span>English</span>
                                <span v-if="currentLocale === 'en'">✓</span>
                            </button>
                        </div>
                    </div>

                    <!-- Dark Mode Toggle -->
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

                    <!-- Notifications Bell -->
                    <div class="relative">
                        <button
                            type="button"
                            class="p-2 rounded-lg text-muted-foreground hover:text-foreground hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                            aria-label="Notifikasi"
                        >
                            <Bell class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- User Dropdown Menu -->
                    <div class="relative">
                        <button
                            type="button"
                            @click="isUserDropdownOpen = !isUserDropdownOpen"
                            class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors text-sm"
                        >
                            <div class="w-8 h-8 rounded-full bg-accent-500 text-white font-bold flex items-center justify-center text-xs shadow-xs">
                                {{ userInitials }}
                            </div>
                            <span class="hidden lg:inline-block font-medium text-foreground">
                                {{ user?.name || 'Investor' }}
                            </span>
                            <ChevronDown class="w-4 h-4 text-muted-foreground" />
                        </button>

                        <div
                            v-if="isUserDropdownOpen"
                            @click="isUserDropdownOpen = false"
                            class="fixed inset-0 z-40"
                        ></div>

                        <transition
                            enter-active-class="transition ease-out duration-100"
                            enter-from-class="transform opacity-0 scale-95"
                            enter-to-class="transform opacity-100 scale-100"
                            leave-active-class="transition ease-in duration-70"
                            leave-from-class="transform opacity-100 scale-100"
                            leave-to-class="transform opacity-0 scale-95"
                        >
                            <div
                                v-if="isUserDropdownOpen"
                                class="absolute right-0 mt-2 w-48 bg-card rounded-xl border border-border shadow-lg py-1 z-50 text-sm font-medium"
                            >
                                <div class="px-4 py-2 border-b border-border/60">
                                    <p class="font-semibold text-foreground truncate">{{ user?.name || 'Investor' }}</p>
                                    <p class="text-xs text-muted-foreground truncate">{{ user?.email }}</p>
                                </div>

                                <Link
                                    href="/investor/profil"
                                    class="flex items-center gap-2 px-4 py-2 text-foreground hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                                >
                                    <UserIcon class="w-4 h-4 text-muted-foreground" />
                                    <span>Profil Saya</span>
                                </Link>

                                <div class="my-1 border-t border-border/60"></div>

                                <button
                                    type="button"
                                    @click="logout"
                                    class="w-full text-left flex items-center gap-2 px-4 py-2 text-destructive hover:bg-destructive/10 transition-colors"
                                >
                                    <LogOut class="w-4 h-4" />
                                    <span>Keluar</span>
                                </button>
                            </div>
                        </transition>
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8 overflow-y-auto">
                <slot />
            </main>
        </div>
    </div>
</template>
