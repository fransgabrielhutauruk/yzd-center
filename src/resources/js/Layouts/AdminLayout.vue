<script setup>
import { ref, onMounted, computed } from 'vue';
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
    Settings,
    Banknote,
    BookOpen,
    HelpCircle,
    MessageCircleQuestion,
    TrendingUp,
    ArrowLeftRight,
    Network,
    SquarePlay,
} from 'lucide-vue-next';
import { useDarkMode } from '@/composables/useDarkMode';
import AppToaster from '@/Components/Common/AppToaster.vue';
import AppNotificationBell from '@/Components/Common/AppNotificationBell.vue';
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

const isMobileSidebarOpen = ref(false);
const isUserDropdownOpen = ref(false);
const isCollapsed = ref(false);

onMounted(() => {
    try {
        isCollapsed.value = localStorage.getItem('yzd_admin_sidebar_collapsed') === 'true';
    } catch {
        isCollapsed.value = false;
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
                label: 'Dasbor Admin',
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
                href: '/admin/kyc',
                icon: BadgeCheck,
                routeName: 'admin.kyc.index',
            },
            {
                label: 'Verifikasi Copytrade',
                href: '/admin/copytrade',
                icon: ArrowLeftRight,
                routeName: 'admin.copytrade.index',
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
                label: 'Laporan Keuntungan',
                href: '/admin/profit-reports',
                icon: TrendingUp,
                routeName: 'admin.profit_reports.index',
            },
            {
                label: 'Klaim Susulan Referral',
                href: '/admin/referral-claims',
                icon: UserCheck,
                routeName: 'admin.referral_claims.index',
            },
        ],
    },
    {
        title: 'Edukasi & Informasi',
        items: [
            {
                label: 'Glosarium Forex',
                href: '/admin/education/glossary',
                icon: BookOpen,
                routeName: 'admin.education.glossary.index',
            },
            {
                label: 'Pusat Bantuan & Video',
                href: '/admin/education/help',
                icon: SquarePlay,
                routeName: 'admin.education.help.index',
            },
            {
                label: 'Tanya Jawab (FAQ)',
                href: '/admin/education/faq',
                icon: HelpCircle,
                routeName: 'admin.education.faq.index',
            },
        ],
    },
    {
        title: 'Pengaturan Sistem',
        items: [
            {
                label: 'Pengaturan Master',
                href: '/admin/settings/master',
                icon: Settings,
                routeName: 'admin.settings.master',
            },
            {
                label: 'Konten Legalitas',
                href: '/admin/settings/legal',
                icon: FileText,
                routeName: 'admin.settings.legal',
            },
        ],
    },
];

const isActive = (item) => {
    return page.url.startsWith(item.href);
};

const logout = () => {
    router.post('/logout');
};
</script>

<template>
    <Head :title="title" />
    <AppToaster />

    <div
        class="text-foreground flex min-h-screen min-h-dvh flex-1 bg-white transition-colors duration-200 dark:bg-slate-950"
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
                'bg-[#f8f9ff] dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 fixed inset-y-0 left-0 z-50 flex flex-col shadow-xs transition-all duration-300 ease-in-out md:static md:translate-x-0 md:shadow-none',
                isCollapsed ? 'md:w-20' : 'md:w-64',
                'w-64',
                isMobileSidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0',
            ]"
        >
            <!-- Logo Brand & Mobile Close Button -->
            <div
                class="flex h-16 items-center justify-between px-4  "
            >
                <Link href="/admin/dashboard" class="flex items-center gap-1.5 overflow-hidden mx-auto">
                    <span class="text-accent-500 text-2xl font-black tracking-tight shrink-0">YZD</span>
                    <span v-if="!isCollapsed" class="text-xl font-bold tracking-tight text-primary-600 dark:text-primary-400 shrink-0">Center</span>

                    <span
                        v-if="!isCollapsed"
                        class="bg-primary-600 text-white ml-1.5 rounded-md px-2 py-0.5 text-[10px] font-bold tracking-wider uppercase shrink-0"
                    >
                        ADMIN
                    </span>
                </Link>

                <!-- Close Button (Mobile only) -->
                <button
                    type="button"
                    @click="isMobileSidebarOpen = false"
                    class="rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200 md:hidden cursor-pointer"
                    aria-label="Tutup Menu"
                >
                    <X class="h-5 w-5" />
                </button>
            </div>

            <!-- Sidebar Navigation Links -->
            <nav class="flex-1 space-y-4 overflow-y-auto px-3 py-1 text-sm font-medium">
                <div v-for="(section, idx) in navSections" :key="idx" class="space-y-1">
                    <!-- Section Title (Hidden when collapsed) -->
                    <p
                        v-if="!isCollapsed"
                        class="px-3 pt-3 pb-1 text-[11px] font-bold tracking-wider text-slate-500 dark:text-slate-400 uppercase"
                    >
                        {{ section.title }}
                    </p>

                    <div class="space-y-1">
                        <Link
                            v-for="item in section.items"
                            :key="item.href"
                            :href="item.href"
                            @click="isMobileSidebarOpen = false"
                            :title="isCollapsed ? item.label : undefined"
                            :class="[
                                'group flex items-center rounded-xl py-2.5 transition-all duration-150',
                                isCollapsed ? 'justify-center px-2' : 'justify-between px-3.5',
                                isActive(item)
                                    ? 'bg-primary-600 text-white font-semibold shadow-xs'
                                    : 'text-slate-700 dark:text-slate-300 font-semibold hover:bg-primary-50 dark:hover:bg-primary-950/40 hover:text-primary-600 dark:hover:text-primary-400',
                            ]"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <component
                                    :is="item.icon"
                                    :class="[
                                        'h-4 w-4 shrink-0 transition-colors',
                                        isActive(item)
                                            ? 'text-white'
                                            : 'text-slate-500 dark:text-slate-400 group-hover:text-primary-600 dark:group-hover:text-primary-400',
                                    ]"
                                />
                                <span v-if="!isCollapsed" class="truncate font-semibold text-[13px]">
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
                class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-slate-200 dark:border-slate-800 bg-[#f8f9ff] px-4 transition-colors sm:px-6 dark:bg-slate-900"
            >
                <div class="flex items-center gap-2.5 sm:gap-3">
                    <!-- Mobile Menu Button (Hamburger) -->
                    <button
                        type="button"
                        @click="isMobileSidebarOpen = true"
                        class="text-slate-700 dark:text-slate-200 hover:text-slate-900 rounded-lg p-2 hover:bg-slate-200/60 md:hidden dark:hover:bg-slate-800 cursor-pointer min-h-[38px] min-w-[38px] flex items-center justify-center transition-colors"
                        aria-label="Buka Menu"
                    >
                        <Menu class="h-5 w-5" />
                    </button>

                    <!-- Desktop Sidebar Minimize/Maximize Button (Hamburger) -->
                    <button
                        type="button"
                        @click="toggleCollapse"
                        class="hidden md:flex rounded-lg p-2 text-slate-700 dark:text-slate-200 hover:text-slate-900 hover:bg-slate-200/60 dark:hover:bg-slate-800 transition-colors cursor-pointer min-h-[38px] min-w-[38px] items-center justify-center"
                        :title="isCollapsed ? 'Tampilkan Sidebar' : 'Sembunyikan Sidebar'"
                        :aria-label="isCollapsed ? 'Perluas Sidebar' : 'Kecilkan Sidebar'"
                    >
                        <Menu class="h-5 w-5" />
                    </button>
                </div>

                <!-- Right Header Actions -->
                <div class="flex items-center gap-2 sm:gap-2.5">
                    <!-- Notifications Bell Component -->
                    <AppNotificationBell />

                    <!-- Dark Mode Toggle -->
                    <button
                        type="button"
                        @click="toggleDarkMode"
                        class="text-slate-700 dark:text-slate-200 hover:text-slate-900 rounded-lg p-2 transition-colors hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer min-h-[38px] min-w-[38px] flex items-center justify-center"
                        :title="isDark ? 'Mode Terang' : 'Mode Gelap'"
                        aria-label="Toggle Tema"
                    >
                        <Sun v-if="isDark" class="h-5 w-5 text-amber-500" />
                        <Moon v-else class="h-5 w-5 text-slate-700 dark:text-slate-300" />
                    </button>

                    <!-- Vertical Divider -->
                    <div class="h-6 w-px bg-slate-200 dark:bg-slate-700 mx-1 sm:mx-1.5"></div>

                    <!-- User Dropdown Menu -->
                    <div class="relative">
                        <button
                            type="button"
                            @click="isUserDropdownOpen = !isUserDropdownOpen"
                            class="flex items-center gap-2.5 rounded-lg p-1.5 text-sm transition-colors hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer min-h-[38px]"
                        >
                            <div
                                class="bg-primary-600 flex h-9 w-9 items-center justify-center rounded-full text-xs font-bold text-white shadow-xs shrink-0"
                            >
                                {{ userInitials }}
                            </div>
                            <div class="hidden text-left sm:block">
                                <p class="text-sm font-bold text-slate-800 dark:text-slate-100 leading-tight truncate">
                                    {{ page.props.auth?.user?.name || 'Administrator' }}
                                </p>
                                <p class="text-xs text-slate-500 dark:text-slate-400 leading-tight">
                                    Super User
                                </p>
                            </div>
                            <ChevronDown class="text-slate-500 dark:text-slate-400 h-4 w-4 shrink-0" />
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
                                        {{ page.props.auth?.user?.email }}
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    @click="logout"
                                    class="text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/30 flex w-full items-center gap-2 px-4 py-2 text-left transition-colors font-bold cursor-pointer"
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
            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-white dark:bg-slate-950">
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
