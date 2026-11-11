<script setup>
import { ref, onMounted, onUnmounted, nextTick, computed } from 'vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import {
    LayoutDashboard,
    BadgeCheck,
    Link2,
    FileText,
    Sun,
    Moon,
    LogOut,
    Menu,
    X,
    ChevronDown,
    UserCheck,
    Users,
    Banknote,
    BookOpen,
    HelpCircle,
    MessageCircleQuestion,
    ArrowLeftRight,
    Network,
    SquarePlay,
    KeyRound,
    History,
    TrendingUp,
    LifeBuoy,
    Receipt,
    Settings,
} from 'lucide-vue-next';
import { useDarkMode } from '@/composables/useDarkMode';
import { usePwaInstall } from '@/composables/usePwaInstall';
import AppToaster from '@/Components/Common/AppToaster.vue';
import AppNotificationBell from '@/Components/Common/AppNotificationBell.vue';
import AppPwaInstallButton from '@/Components/Common/AppPwaInstallButton.vue';
import AppPageHeader from '@/Components/Common/AppPageHeader.vue';

defineProps({
    title: {
        type: String,
        default: 'Admin Panel',
    },
    subtitle: {
        type: String,
        default: '',
    },
    breadcrumbs: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const { isDark, toggleDarkMode } = useDarkMode();
const { canShowInstallButton } = usePwaInstall();

const isMobileSidebarOpen = ref(false);
const isUserDropdownOpen = ref(false);
const isCollapsed = ref(false);

const SIDEBAR_SCROLL_KEY = 'yzd_admin_sidebar_scroll';
const sidebarNavRef = ref(null);
const isRestoringScroll = ref(false);

const saveSidebarScroll = () => {
    if (!sidebarNavRef.value || isRestoringScroll.value) return;
    try {
        sessionStorage.setItem(SIDEBAR_SCROLL_KEY, String(sidebarNavRef.value.scrollTop));
    } catch {
        // Ignore storage error
    }
};

const onSidebarScroll = () => {
    saveSidebarScroll();
};

const handleLinkClick = () => {
    isMobileSidebarOpen.value = false;
    saveSidebarScroll();
};

const handleLogoClick = () => {
    isMobileSidebarOpen.value = false;
    try {
        sessionStorage.setItem(SIDEBAR_SCROLL_KEY, '0');
    } catch {
        // Ignore storage error
    }
    if (sidebarNavRef.value) {
        sidebarNavRef.value.scrollTop = 0;
    }
};

const restoreSidebarScroll = () => {
    if (!sidebarNavRef.value) return;
    isRestoringScroll.value = true;
    try {
        const saved = sessionStorage.getItem(SIDEBAR_SCROLL_KEY);
        if (saved !== null) {
            const top = parseInt(saved, 10);
            if (!isNaN(top)) {
                sidebarNavRef.value.scrollTop = top;
            }
        } else {
            const activeEl = sidebarNavRef.value.querySelector('.bg-primary-600');
            if (activeEl) {
                activeEl.scrollIntoView({ block: 'nearest' });
            }
        }
    } catch {
        // Ignore storage error
    } finally {
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                isRestoringScroll.value = false;
            });
        });
    }
};

let removeNavigateListener = null;

onMounted(() => {
    try {
        isCollapsed.value = localStorage.getItem('yzd_admin_sidebar_collapsed') === 'true';
    } catch {
        isCollapsed.value = false;
    }

    nextTick(() => {
        restoreSidebarScroll();
    });

    removeNavigateListener = router.on('finish', () => {
        nextTick(() => {
            restoreSidebarScroll();
        });
    });
});

onUnmounted(() => {
    if (removeNavigateListener) {
        removeNavigateListener();
    }
});

const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
    try {
        localStorage.setItem('yzd_admin_sidebar_collapsed', String(isCollapsed.value));
    } catch {
        // Ignore storage error
    }
};

const user = computed(() => page.props.auth?.user || {});
const userInitials = computed(() => {
    const name = user.value?.name || 'Administrator';
    const parts = name.trim().split(' ');
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return name.slice(0, 2).toUpperCase();
});

// Navigasi aktif hanya untuk modul yang berjalan di Fase 1 & 2
const navSections = [
    {
        title: 'Menu Utama',
        items: [
            {
                label: 'Dashboard',
                href: '/admin/dashboard',
                icon: LayoutDashboard,
                routeName: 'admin.dashboard',
            },
            {
                label: 'Tautan Undangan',
                href: '/admin/invitations',
                icon: Link2,
                routeName: 'admin.invitations.index',
            },
            {
                label: 'Verifikasi Pendaftaran',
                href: '/admin/registrations',
                icon: BadgeCheck,
                routeName: 'admin.registrations.index',
            },
            {
                label: 'Kelola Copytrade',
                href: '/admin/copytrade',
                icon: ArrowLeftRight,
                routeName: 'admin.copytrade.index',
            },
            {
                label: 'Master Fee',
                href: '/admin/master-fee',
                icon: Receipt,
                routeName: 'admin.master_fee.index',
            },
            {
                label: 'Pengguna',
                href: '/admin/users',
                icon: Users,
                routeName: 'admin.users.index',
            },
        ],
    },
    {
        title: 'Manajemen Afiliasi',
        items: [
            {
                label: 'Jaringan Kemitraan',
                href: '/admin/affiliates',
                icon: Network,
                routeName: 'admin.affiliates.index',
            },
            {
                label: 'Pencairan Komisi',
                href: '/admin/commissions',
                icon: Banknote,
                routeName: 'admin.commissions.index',
            },
            {
                label: 'Materi Promosi',
                href: '/admin/profit-master',
                icon: TrendingUp,
                routeName: 'admin.profit_master.index',
            },
        ],
    },
    {
        title: 'Bantuan & Informasi',
        items: [
            {
                label: 'Pusat Bantuan',
                href: '/admin/education/help',
                icon: SquarePlay,
                routeName: 'admin.education.help.index',
            },
            {
                label: 'Tiket Bantuan',
                href: '/admin/tickets',
                icon: LifeBuoy,
                routeName: 'admin.tickets.index',
            },
            {
                label: 'Tanya Jawab (FAQ)',
                href: '/admin/education/faq',
                icon: HelpCircle,
                routeName: 'admin.education.faq.index',
            },
            {
                label: 'Glosarium Forex',
                href: '/admin/education/glossary',
                icon: BookOpen,
                routeName: 'admin.education.glossary.index',
            },
        ],
    },
    {
        title: 'Pengaturan Sistem',
        items: [
            {
                label: 'Log Aktivitas',
                href: '/admin/activity-logs',
                icon: History,
                routeName: 'admin.activity_logs.index',
            },
            {
                label: 'Konten Legalitas',
                href: '/admin/settings/legal',
                icon: FileText,
                routeName: 'admin.settings.legal',
            },
            {
                label: 'Pengaturan Master Fee',
                href: '/admin/settings/master-fee',
                icon: Settings,
                routeName: 'admin.settings.master_fee',
            },
        ],
    },
];

const isActive = (itemOrHref) => {
    const href = typeof itemOrHref === 'string' ? itemOrHref : itemOrHref?.href;
    const currentPath = (page.url || '').split('?')[0].replace(/\/+$/, '') || '/';
    const targetPath = (href || '').split('?')[0].replace(/\/+$/, '') || '/';

    if (currentPath === targetPath) {
        return true;
    }

    if (targetPath !== '/admin' && targetPath !== '' && currentPath.startsWith(targetPath + '/')) {
        return true;
    }

    return false;
};

const logout = () => {
    router.post('/logout');
};
</script>

<template>
    <Head :title="title" />
    <AppToaster />

    <div
        class="text-foreground flex h-dvh h-screen overflow-hidden bg-white transition-colors duration-200 dark:bg-slate-950"
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
                class="fixed inset-0 z-40 bg-black/60 backdrop-blur-xs md:hidden"
            ></div>
        </transition>

        <!-- Sidebar Component -->
        <aside
            :class="[
                'fixed inset-y-0 left-0 z-50 flex flex-col border-r border-slate-200 bg-[#f8f9ff] shadow-xs transition-all duration-300 ease-in-out md:static md:translate-x-0 md:shadow-none dark:border-slate-800 dark:bg-slate-900',
                isCollapsed ? 'md:w-20' : 'md:w-64',
                'w-64',
                isMobileSidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0',
            ]"
        >
            <!-- Logo Brand & Mobile Close Button -->
            <div class="flex h-16 items-center justify-between px-4">
                <Link
                    href="/admin/dashboard"
                    @click="handleLogoClick"
                    class="mx-auto flex items-center gap-1.5 overflow-hidden"
                >
                    <span class="text-accent-500 shrink-0 text-2xl font-black tracking-tight"
                        >YZD</span
                    >
                    <span
                        v-if="!isCollapsed"
                        class="text-primary-600 dark:text-primary-400 shrink-0 text-xl font-bold tracking-tight"
                        >Center</span
                    >

                    <span
                        v-if="!isCollapsed"
                        class="bg-primary-600 ml-1.5 shrink-0 rounded-md px-2 py-0.5 text-[10px] font-bold tracking-wider text-white uppercase"
                    >
                        ADMIN
                    </span>
                </Link>

                <!-- Close Button (Mobile only) -->
                <button
                    type="button"
                    @click="isMobileSidebarOpen = false"
                    class="cursor-pointer rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-700 md:hidden dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200"
                    aria-label="Tutup Menu"
                >
                    <X class="h-5 w-5" />
                </button>
            </div>

            <!-- Sidebar Navigation Links -->
            <nav
                ref="sidebarNavRef"
                @scroll.passive="onSidebarScroll"
                class="flex-1 space-y-4 overflow-y-auto px-3 py-1 text-sm font-medium"
            >
                <div v-for="(section, idx) in navSections" :key="idx" class="space-y-1">
                    <!-- Section Title (Hidden when collapsed) -->
                    <p
                        v-if="!isCollapsed"
                        class="px-3 pt-3 pb-1 text-[11px] font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                    >
                        {{ section.title }}
                    </p>

                    <div class="space-y-1">
                        <Link
                            v-for="item in section.items"
                            :key="item.href"
                            :href="item.href"
                            @click="handleLinkClick"
                            :title="isCollapsed ? item.label : undefined"
                            :class="[
                                'group flex items-center rounded-xl py-2.5 transition-all duration-150',
                                isCollapsed ? 'justify-center px-2' : 'justify-between px-3.5',
                                isActive(item)
                                    ? 'bg-primary-600 font-semibold text-white shadow-xs'
                                    : 'hover:bg-primary-50 dark:hover:bg-primary-950/40 hover:text-primary-600 dark:hover:text-primary-400 font-semibold text-slate-700 dark:text-slate-300',
                            ]"
                        >
                            <div class="flex min-w-0 items-center gap-3">
                                <component
                                    :is="item.icon"
                                    :class="[
                                        'h-4 w-4 shrink-0 transition-colors',
                                        isActive(item)
                                            ? 'text-white'
                                            : 'group-hover:text-primary-600 dark:group-hover:text-primary-400 text-slate-500 dark:text-slate-400',
                                    ]"
                                />
                                <span
                                    v-if="!isCollapsed"
                                    class="truncate text-[13px] font-semibold"
                                >
                                    {{ item.label }}
                                </span>
                            </div>
                        </Link>
                    </div>
                </div>
            </nav>
        </aside>

        <!-- Main Wrapper -->
        <div class="flex min-w-0 flex-1 flex-col">
            <!-- Top Navbar -->
            <header
                class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-slate-200 bg-[#f8f9ff] px-4 transition-colors sm:px-6 dark:border-slate-800 dark:bg-slate-900"
            >
                <div class="flex items-center gap-2.5 sm:gap-3">
                    <!-- Mobile Menu Button (Hamburger) -->
                    <button
                        type="button"
                        @click="isMobileSidebarOpen = true"
                        class="flex min-h-[38px] min-w-[38px] cursor-pointer items-center justify-center rounded-lg p-2 text-slate-700 transition-colors hover:bg-slate-200/60 hover:text-slate-900 md:hidden dark:text-slate-200 dark:hover:bg-slate-800"
                        aria-label="Buka Menu"
                    >
                        <Menu class="h-5 w-5" />
                    </button>

                    <!-- Desktop Sidebar Minimize/Maximize Button (Hamburger) -->
                    <button
                        type="button"
                        @click="toggleCollapse"
                        class="hidden min-h-[38px] min-w-[38px] cursor-pointer items-center justify-center rounded-lg p-2 text-slate-700 transition-colors hover:bg-slate-200/60 hover:text-slate-900 md:flex dark:text-slate-200 dark:hover:bg-slate-800"
                        :title="isCollapsed ? 'Tampilkan Sidebar' : 'Sembunyikan Sidebar'"
                        :aria-label="isCollapsed ? 'Perluas Sidebar' : 'Kecilkan Sidebar'"
                    >
                        <Menu class="h-5 w-5" />
                    </button>
                </div>

                <!-- Right Header Actions -->
                <div class="flex items-center gap-2 sm:gap-2.5">
                    <!-- PWA Install Button -->
                    <AppPwaInstallButton />

                    <!-- Vertical Divider -->
                    <div
                        v-if="canShowInstallButton"
                        class="mx-0.5 h-6 w-px bg-slate-200 sm:mx-1 dark:bg-slate-700"
                    ></div>

                    <!-- Notifications Bell Component -->
                    <AppNotificationBell />

                    <!-- Dark Mode Toggle -->
                    <button
                        type="button"
                        @click="toggleDarkMode"
                        class="flex min-h-[38px] min-w-[38px] cursor-pointer items-center justify-center rounded-lg p-2 text-slate-700 transition-colors hover:bg-slate-100 hover:text-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                        :title="isDark ? 'Mode Terang' : 'Mode Gelap'"
                        aria-label="Toggle Tema"
                    >
                        <Sun v-if="isDark" class="h-5 w-5 text-amber-500" />
                        <Moon v-else class="h-5 w-5 text-slate-700 dark:text-slate-300" />
                    </button>

                    <!-- Vertical Divider -->
                    <div class="mx-1 h-6 w-px bg-slate-200 sm:mx-1.5 dark:bg-slate-700"></div>

                    <!-- User Dropdown Menu -->
                    <div class="relative">
                        <button
                            type="button"
                            @click="isUserDropdownOpen = !isUserDropdownOpen"
                            class="flex min-h-[38px] cursor-pointer items-center gap-2.5 rounded-lg p-1.5 text-sm transition-colors hover:bg-slate-100 dark:hover:bg-slate-800"
                        >
                            <div
                                class="bg-primary-600 flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-bold text-white shadow-xs"
                            >
                                {{ userInitials }}
                            </div>
                            <div class="hidden text-left sm:block">
                                <p
                                    class="truncate text-sm leading-tight font-bold text-slate-800 dark:text-slate-100"
                                >
                                    {{ page.props.auth?.user?.name || 'Administrator' }}
                                </p>
                                <p class="text-xs leading-tight text-slate-500 dark:text-slate-400">
                                    Super User
                                </p>
                            </div>
                            <ChevronDown
                                class="h-4 w-4 shrink-0 text-slate-500 dark:text-slate-400"
                            />
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
                                class="bg-card border-border absolute right-0 z-50 mt-2 w-48 rounded-xl border py-1 text-sm font-medium shadow-lg"
                            >
                                <div class="border-border/60 border-b px-4 py-2">
                                    <p class="text-foreground truncate font-bold">
                                        {{ page.props.auth?.user?.name || 'Administrator' }}
                                    </p>
                                    <p class="text-muted-foreground truncate text-xs">
                                        @{{ page.props.auth?.user?.username }}
                                    </p>
                                </div>

                                <Link
                                    href="/admin/settings/password"
                                    @click="isUserDropdownOpen = false"
                                    class="flex w-full items-center gap-2 px-4 py-2 text-left font-medium text-slate-700 transition-colors hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"
                                >
                                    <KeyRound class="h-4 w-4 text-slate-400" />
                                    <span>Ganti Password</span>
                                </Link>

                                <div class="border-border/60 my-1 border-t"></div>

                                <button
                                    type="button"
                                    @click="logout"
                                    class="flex w-full cursor-pointer items-center gap-2 px-4 py-2 text-left font-bold text-rose-600 transition-colors hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/30"
                                >
                                    <LogOut class="h-4 w-4" />
                                    <span>Keluar</span>
                                </button>
                            </div>
                        </transition>
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto bg-white p-4 sm:p-6 lg:p-8 dark:bg-slate-950">
                <AppPageHeader
                    v-if="breadcrumbs && breadcrumbs.length > 0"
                    :breadcrumbs="breadcrumbs"
                    :title="title"
                    :subtitle="subtitle"
                >
                    <template v-if="$slots['header-actions']" #actions>
                        <slot name="header-actions" />
                    </template>
                </AppPageHeader>
                <slot />
            </main>
        </div>
    </div>
</template>
