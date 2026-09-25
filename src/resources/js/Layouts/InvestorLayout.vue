<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import {
    LayoutDashboard,
    FileCheck,
    Sun,
    Moon,
    LogOut,
    Menu,
    X,
    ChevronDown,
    PanelLeftClose,
    PanelLeft,
    Lock,
    Users,
    TrendingUp,
    LineChart,
    Banknote,
    Calculator,
    BookOpen,
    HelpCircle,
    MessageCircleQuestion,
    Megaphone,
} from 'lucide-vue-next';
import { useDarkMode } from '@/composables/useDarkMode';
import AppToaster from '@/Components/Common/AppToaster.vue';
import AppNotificationBell from '@/Components/Common/AppNotificationBell.vue';
import { toast } from 'vue-sonner';

defineProps({
    title: {
        type: String,
        default: 'Dasbor Portofolio',
    },
});

const page = usePage();
const { isDark, toggleDarkMode } = useDarkMode();

const isMobileSidebarOpen = ref(false);
const isUserDropdownOpen = ref(false);
const isCollapsed = ref(false);

onMounted(() => {
    try {
        isCollapsed.value = localStorage.getItem('yzd_investor_sidebar_collapsed') === 'true';
    } catch {
        isCollapsed.value = false;
    }
});

const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
    try {
        localStorage.setItem('yzd_investor_sidebar_collapsed', String(isCollapsed.value));
    } catch {
        // Ignore storage error
    }
};

const user = computed(() => page.props.auth?.user || {});
const isAffiliator = computed(() => user.value?.role === 'affiliator');

const userInitials = computed(() => {
    const name = user.value?.name || 'Investor';
    const parts = name.trim().split(' ');
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return name.slice(0, 2).toUpperCase();
});

const isUserActive = computed(() => {
    return user.value?.status === 'active' || Boolean(user.value?.is_active);
});

const navItems = computed(() => {
    const items = [
        {
            label: 'Dasbor Portofolio',
            href: '/investor/dashboard',
            icon: LayoutDashboard,
            requiresActive: true,
        },
        {
            label: 'Status KYC & Legalitas',
            href: '/investor/kyc',
            icon: FileCheck,
            requiresActive: false,
            routeName: 'investor.kyc',
        },
        {
            label: 'Manajemen Copytrade',
            href: '/investor/copytrade',
            icon: TrendingUp,
            requiresActive: false,
            routeName: 'investor.copytrade.index',
        },
        {
            label: 'Kinerja & Saldo MT4',
            href: '/investor/copytrade/balance',
            icon: LineChart,
            requiresActive: false,
            routeName: 'investor.copytrade.balance.index',
        },
        {
            label: 'Kalkulator Proyeksi',
            href: '/investor/education/calculator',
            icon: Calculator,
            requiresActive: false,
            routeName: 'investor.education.calculator',
        },
        {
            label: 'Pusat Bantuan & Video',
            href: '/investor/education/help',
            icon: HelpCircle,
            requiresActive: false,
            routeName: 'investor.education.help',
        },
        {
            label: 'Tanya Jawab (FAQ)',
            href: '/investor/education/faq',
            icon: MessageCircleQuestion,
            requiresActive: false,
            routeName: 'investor.education.faq',
        },
        {
            label: 'Glosarium Forex',
            href: '/investor/education/glossary',
            icon: BookOpen,
            requiresActive: false,
            routeName: 'investor.education.glossary',
        },
    ];

    if (isAffiliator.value) {
        items.push({
            label: 'Materi Promosi & Profit',
            href: '/investor/promotions',
            icon: Megaphone,
            requiresActive: false,
            routeName: 'investor.promotions.index',
        });
        items.push({
            label: 'Jaringan Kemitraan',
            href: '/investor/affiliates',
            icon: Users,
            requiresActive: true,
        });
        items.push({
            label: 'Klaim Komisi (PB 3)',
            href: '/investor/commissions',
            icon: Banknote,
            requiresActive: true,
            routeName: 'investor.commissions.index',
        });
    } else {
        items.push({
            label: 'Laporan Profit Trading',
            href: '/investor/promotions',
            icon: Megaphone,
            requiresActive: false,
            routeName: 'investor.promotions.index',
        });
    }

    return items;
});

const isActive = (href) => {
    return page.url.startsWith(href);
};

const isLocked = (item) => {
    return Boolean(item.requiresActive && !isUserActive.value);
};

const handleLockedClick = (item) => {
    toast.info('Fitur Terkunci', {
        description: `Menu "${item.label}" belum dapat diakses karena akun Anda masih dalam proses verifikasi oleh Administrator.`,
    });
};

const logout = () => {
    router.post('/logout');
};
</script>

<template>
    <Head :title="title" />
    <AppToaster />

    <div
        class="min-h-screen min-h-dvh flex-1 bg-white dark:bg-slate-900 text-foreground flex transition-colors duration-200"
    >
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
                'bg-slate-50 dark:bg-slate-950 border-r border-slate-200 dark:border-slate-800 fixed inset-y-0 left-0 z-50 flex flex-col shadow-sm transition-all duration-300 ease-in-out md:static md:translate-x-0 md:shadow-none',
                isCollapsed ? 'md:w-20' : 'md:w-64',
                'w-64',
                isMobileSidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0',
            ]"
        >
            <!-- Logo Brand & Mobile Close Button -->
            <div
                class="h-16 px-4 flex items-center justify-between border-b border-slate-200 dark:border-slate-800"
            >
                <Link :href="isUserActive ? '/investor/dashboard' : '/investor/kyc'" class="flex items-center gap-1.5 overflow-hidden">
                    <span class="text-accent-500 font-black text-2xl tracking-tight shrink-0">YZD</span>
                    <span v-if="!isCollapsed" class="text-primary-600 dark:text-primary-400 font-bold text-xl tracking-tight shrink-0">Center</span>
                    <span
                        v-if="!isCollapsed && isAffiliator"
                        class="ml-1 text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 px-1.5 py-0.5 rounded shrink-0 tracking-wide"
                    >
                        AFFILIATOR
                    </span>
                </Link>

                <!-- Close Button (Mobile only) -->
                <button
                    type="button"
                    @click="isMobileSidebarOpen = false"
                    class="md:hidden p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200 rounded-lg cursor-pointer"
                    aria-label="Tutup Menu"
                >
                    <X class="w-5 h-5" />
                </button>
            </div>

            <!-- Sidebar Navigation Links -->
            <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-5 text-sm font-medium">
                <div class="space-y-1">
                    <!-- Section Title (Hidden when collapsed) -->
                    <p
                        v-if="!isCollapsed"
                        class="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500"
                    >
                        Menu Utama
                    </p>
                    <div v-else class="h-px bg-slate-200 dark:bg-slate-700 mx-2 my-2"></div>

                    <div class="space-y-1">
                        <template v-for="item in navItems" :key="item.href">
                            <!-- Menu Terkunci (Klik memicu Toast) -->
                            <button
                                v-if="isLocked(item)"
                                type="button"
                                @click="handleLockedClick(item)"
                                :title="isCollapsed ? `${item.label} (Terkunci)` : `${item.label} (Terkunci - Menunggu verifikasi)`"
                                :class="[
                                    'w-full group flex items-center rounded-lg py-2.5 transition-all duration-150 cursor-pointer text-slate-400 dark:text-slate-600 hover:bg-slate-50 dark:hover:bg-slate-800/60',
                                    isCollapsed ? 'justify-center px-2' : 'justify-between px-3',
                                ]"
                            >
                                <div class="flex items-center gap-3 min-w-0">
                                    <component
                                        :is="item.icon"
                                        class="w-4 h-4 shrink-0 text-slate-300 dark:text-slate-600 transition-colors"
                                    />
                                    <span v-if="!isCollapsed" class="truncate font-semibold text-[13px]">
                                        {{ item.label }}
                                    </span>
                                </div>

                                <!-- Gembok Indikator jika menu terkunci -->
                                <div
                                    v-if="!isCollapsed"
                                    class="flex items-center gap-1 text-[11px] text-amber-500 dark:text-amber-400 font-medium shrink-0 ml-2"
                                >
                                    <Lock class="w-3.5 h-3.5" />
                                </div>
                            </button>

                            <!-- Menu Aktif Normal -->
                            <Link
                                v-else
                                :href="item.href"
                                @click="isMobileSidebarOpen = false"
                                :title="isCollapsed ? item.label : undefined"
                                :class="[
                                    'group flex items-center rounded-lg py-2.5 transition-all duration-150',
                                    isCollapsed ? 'justify-center px-2' : 'justify-between px-3',
                                    isActive(item.href)
                                        ? 'bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white font-bold'
                                        : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-slate-100',
                                ]"
                            >
                                <div class="flex items-center gap-3 min-w-0">
                                    <component
                                        :is="item.icon"
                                        :class="[
                                            'w-4 h-4 shrink-0 transition-colors',
                                            isActive(item.href)
                                                ? 'text-accent-600 dark:text-accent-400'
                                                : 'text-slate-400 dark:text-slate-500 group-hover:text-slate-600 dark:group-hover:text-slate-300',
                                        ]"
                                    />
                                    <span v-if="!isCollapsed" class="truncate font-semibold text-[13px]">
                                        {{ item.label }}
                                    </span>
                                </div>
                            </Link>
                        </template>
                    </div>
                </div>
            </nav>
        </aside>

        <!-- Main Wrapper -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Top Navbar -->
            <header
                class="h-16 bg-slate-50 dark:bg-slate-950 border-b-2 border-accent-500 shadow-xs sticky top-0 z-30 flex items-center justify-between px-4 sm:px-6 transition-colors"
            >
                <div class="flex items-center gap-2.5 sm:gap-3">
                    <!-- Mobile Menu Button -->
                    <button
                        type="button"
                        @click="isMobileSidebarOpen = true"
                        class="md:hidden p-2 rounded-lg text-slate-700 dark:text-slate-200 hover:text-foreground hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer min-h-[38px] min-w-[38px] flex items-center justify-center"
                        aria-label="Buka Menu"
                    >
                        <Menu class="w-5 h-5" />
                    </button>

                    <!-- Desktop Sidebar Minimize/Maximize Button -->
                    <button
                        type="button"
                        @click="toggleCollapse"
                        class="hidden md:flex rounded-lg p-2 text-slate-700 dark:text-slate-200 hover:text-foreground hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer min-h-[38px] min-w-[38px] items-center justify-center"
                        :title="isCollapsed ? 'Tampilkan Sidebar' : 'Sembunyikan Sidebar'"
                        :aria-label="isCollapsed ? 'Perluas Sidebar' : 'Kecilkan Sidebar'"
                    >
                        <PanelLeftClose v-if="!isCollapsed" class="w-5 h-5" />
                        <PanelLeft v-else class="w-5 h-5" />
                    </button>
                </div>

                <!-- Right Header Actions -->
                <div class="flex items-center gap-2 sm:gap-3">
                    <!-- In-App Notification Bell Component -->
                    <AppNotificationBell />

                    <!-- Dark Mode Toggle -->
                    <button
                        type="button"
                        @click="toggleDarkMode"
                        class="p-2 rounded-lg text-slate-700 dark:text-slate-200 hover:text-foreground hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer min-h-[38px] min-w-[38px] flex items-center justify-center"
                        :title="isDark ? 'Mode Terang' : 'Mode Gelap'"
                        aria-label="Toggle Tema"
                    >
                        <Sun v-if="isDark" class="w-5 h-5 text-amber-500" />
                        <Moon v-else class="w-5 h-5 text-slate-700" />
                    </button>

                    <!-- User Dropdown Menu -->
                    <div class="relative">
                        <button
                            type="button"
                            @click="isUserDropdownOpen = !isUserDropdownOpen"
                            class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-sm transition-colors cursor-pointer min-h-[38px]"
                        >
                            <div
                                class="w-8 h-8 rounded-full bg-accent-500 text-white font-bold text-xs flex items-center justify-center shadow-xs"
                            >
                                {{ userInitials }}
                            </div>
                            <span class="hidden lg:inline-block font-bold text-foreground">
                                {{ user.name || 'Investor' }}
                            </span>
                            <ChevronDown class="w-4 h-4 text-slate-600 dark:text-slate-300" />
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
                                class="absolute right-0 mt-2 w-48 rounded-xl bg-card text-card-foreground border border-border shadow-lg py-1 z-50 text-sm font-medium"
                            >
                                <div class="px-4 py-2 border-b border-border/60">
                                    <p class="font-bold text-foreground truncate">{{ user.name }}</p>
                                    <p class="text-xs text-muted-foreground truncate">{{ user.email }}</p>
                                </div>

                                <Link
                                    href="/investor/kyc"
                                    class="flex items-center gap-2 px-4 py-2 hover:bg-slate-100 dark:hover:bg-slate-800 text-foreground transition-colors font-medium"
                                >
                                    <FileCheck class="w-4 h-4 text-muted-foreground" />
                                    <span>Status KYC & Legalitas</span>
                                </Link>

                                <div class="my-1 border-t border-border/60"></div>

                                <button
                                    type="button"
                                    @click="logout"
                                    class="w-full flex items-center gap-2 px-4 py-2 text-left text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition-colors font-bold cursor-pointer"
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
            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
                <slot />
            </main>
        </div>
    </div>
</template>
