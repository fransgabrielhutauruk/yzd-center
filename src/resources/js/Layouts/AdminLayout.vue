<script setup>
import { ref } from 'vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import {
    LayoutDashboard,
    FileCheck,
    Inbox,
    UserPlus,
    Wallet,
    Link2,
    Users,
    UserCheck,
    BookOpen,
    HelpCircle,
    FileText,
    Shield,
    Settings,
    Bell,
    Sun,
    Moon,
    LogOut,
    Menu,
    X,
    ChevronDown,
    User as UserIcon,
} from 'lucide-vue-next';
import { useDarkMode } from '@/composables/useDarkMode';
import AppToaster from '@/Components/Common/AppToaster.vue';

defineProps({
    title: {
        type: String,
        default: 'Admin Panel',
    },
});

const page = usePage();
const { isDark, toggleDarkMode } = useDarkMode();
const isMobileSidebarOpen = ref(false);
const isUserDropdownOpen = ref(false);

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
        ],
    },
    {
        title: 'Operasional & Validasi',
        items: [
            {
                label: 'Verifikasi Legalitas',
                href: '/admin/kyc',
                icon: FileCheck,
                routeName: 'admin.kyc.index',
            },
            {
                label: 'Antrean Validasi',
                href: '/admin/antrean-validasi',
                icon: Inbox,
                badge: '3',
                routeName: 'admin.antrean-validasi',
            },
        ],
    },
    {
        title: 'Manajemen Afiliasi',
        items: [
            {
                label: 'Klaim Referral Susulan',
                href: '/admin/klaim-susulan',
                icon: UserPlus,
                routeName: 'admin.klaim-susulan',
            },
            {
                label: 'Pencairan Komisi',
                href: '/admin/pencairan-komisi',
                icon: Wallet,
                routeName: 'admin.pencairan-komisi',
            },
        ],
    },
    {
        title: 'Manajemen Data',
        items: [
            {
                label: 'Tautan Undangan',
                href: '/admin/invitations',
                icon: Link2,
                routeName: 'admin.invitations.index',
            },
            {
                label: 'Data Investor',
                href: '/admin/investors',
                icon: Users,
                routeName: 'admin.investors.index',
            },
            {
                label: 'Data Afiliator',
                href: '/admin/affiliators',
                icon: UserCheck,
                routeName: 'admin.affiliators.index',
            },
        ],
    },
    {
        title: 'Kelola Konten (CMS)',
        items: [
            {
                label: 'Kamus Forex',
                href: '/admin/kamus-forex',
                icon: BookOpen,
                routeName: 'admin.kamus-forex',
            },
            {
                label: 'Pusat Bantuan (FAQ)',
                href: '/admin/faq',
                icon: HelpCircle,
                routeName: 'admin.faq',
            },
            {
                label: 'Konten Legalitas',
                href: '/admin/settings/legal',
                icon: FileText,
                routeName: 'admin.settings.legal',
            },
        ],
    },
    {
        title: 'Pengaturan',
        items: [
            {
                label: 'Pengaturan Master',
                href: '/admin/settings',
                icon: Settings,
                routeName: 'admin.settings',
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
        class="text-foreground flex min-h-screen bg-slate-50 transition-colors duration-200 dark:bg-slate-950"
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
                'bg-primary-600 border-primary-700/50 fixed inset-y-0 left-0 z-50 flex w-64 flex-col border-r text-white shadow-xl transition-transform duration-300 ease-in-out md:static md:translate-x-0 md:shadow-none dark:border-slate-800 dark:bg-slate-900',
                isMobileSidebarOpen ? 'translate-x-0' : '-translate-x-full',
            ]"
        >
            <!-- Logo Brand -->
            <div
                class="flex h-16 items-center justify-between border-b border-white/15 px-6 dark:border-slate-800"
            >
                <Link href="/admin/dashboard" class="flex items-center gap-1">
                    <span class="text-accent-500 text-2xl font-extrabold tracking-tight">YZD</span>
                    <span class="text-xl font-bold tracking-tight text-white">Center</span>
                    <span
                        class="bg-accent-500/20 text-accent-300 border-accent-500/40 ml-2 rounded border px-1.5 py-0.5 text-[10px] font-semibold"
                        >ADMIN</span
                    >
                </Link>

                <button
                    type="button"
                    @click="isMobileSidebarOpen = false"
                    class="rounded-lg p-1.5 text-white/70 hover:bg-white/10 hover:text-white md:hidden"
                >
                    <X class="h-5 w-5" />
                </button>
            </div>

            <!-- Sidebar Navigation Links -->
            <nav class="flex-1 space-y-6 overflow-y-auto px-4 py-5 text-sm font-medium">
                <div v-for="(section, idx) in navSections" :key="idx" class="space-y-1.5">
                    <p
                        class="px-3 text-[11px] font-semibold tracking-wider text-white/50 uppercase"
                    >
                        {{ section.title }}
                    </p>

                    <div class="space-y-1">
                        <Link
                            v-for="item in section.items"
                            :key="item.href"
                            :href="item.href"
                            @click="isMobileSidebarOpen = false"
                            :class="[
                                'group flex items-center justify-between rounded-lg px-3 py-2 transition-colors',
                                isActive(item)
                                    ? 'bg-primary-700 dark:bg-primary-900/60 font-semibold text-white shadow-inner'
                                    : 'text-white/80 hover:bg-white/10 hover:text-white dark:hover:bg-slate-800/80',
                            ]"
                        >
                            <div class="flex items-center gap-3">
                                <component
                                    :is="item.icon"
                                    class="h-4 w-4 text-white/75 transition-colors group-hover:text-white"
                                />
                                <span>{{ item.label }}</span>
                            </div>

                            <span
                                v-if="item.badge"
                                class="bg-accent-500 rounded-full px-2 py-0.5 text-xs font-bold text-white shadow-xs"
                            >
                                {{ item.badge }}
                            </span>
                        </Link>
                    </div>
                </div>
            </nav>

            <!-- Bottom User Profile Area -->
            <div
                class="bg-primary-700/50 flex items-center justify-between border-t border-white/15 p-4 dark:border-slate-800 dark:bg-slate-900/80"
            >
                <div class="flex items-center gap-3 truncate">
                    <div
                        class="bg-accent-500 flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full text-sm font-bold text-white shadow-sm"
                    >
                        AD
                    </div>
                    <div class="truncate text-xs">
                        <p class="truncate font-semibold text-white">
                            {{ page.props.auth?.user?.name || 'Administrator' }}
                        </p>
                        <p class="truncate text-white/60">Admin Portal</p>
                    </div>
                </div>

                <button
                    type="button"
                    @click="logout"
                    class="rounded-lg p-2 text-white/70 transition-colors hover:bg-white/10 hover:text-white"
                    title="Keluar"
                >
                    <LogOut class="h-4 w-4" />
                </button>
            </div>
        </aside>

        <!-- Main Wrapper -->
        <div class="flex min-w-0 flex-1 flex-col">
            <!-- Top Navbar -->
            <header
                class="border-accent-500 sticky top-0 z-30 flex h-16 items-center justify-between border-b-2 bg-white px-4 shadow-xs transition-colors sm:px-6 dark:bg-slate-900"
            >
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        @click="isMobileSidebarOpen = true"
                        class="text-muted-foreground hover:text-foreground rounded-lg p-2 hover:bg-slate-100 md:hidden dark:hover:bg-slate-800"
                        aria-label="Buka Menu"
                    >
                        <Menu class="h-5 w-5" />
                    </button>

                    <h1 class="text-foreground truncate text-base font-semibold sm:text-lg">
                        {{ title }}
                    </h1>
                </div>

                <!-- Right Header Actions -->
                <div class="flex items-center gap-2 sm:gap-4">
                    <!-- Dark Mode Toggle -->
                    <button
                        type="button"
                        @click="toggleDarkMode"
                        class="text-muted-foreground hover:text-foreground rounded-lg p-2 transition-colors hover:bg-slate-100 dark:hover:bg-slate-800"
                        :title="isDark ? 'Mode Terang' : 'Mode Gelap'"
                        aria-label="Toggle Tema"
                    >
                        <Sun v-if="isDark" class="h-5 w-5 text-amber-400" />
                        <Moon v-else class="h-5 w-5 text-slate-600" />
                    </button>

                    <!-- Notifications Bell (stub) -->
                    <div class="relative">
                        <button
                            type="button"
                            class="text-muted-foreground hover:text-foreground rounded-lg p-2 transition-colors hover:bg-slate-100 dark:hover:bg-slate-800"
                            aria-label="Notifikasi"
                        >
                            <Bell class="h-5 w-5" />
                            <span
                                class="bg-accent-500 absolute top-1.5 right-1.5 h-2 w-2 rounded-full ring-2 ring-white dark:ring-slate-900"
                            ></span>
                        </button>
                    </div>

                    <!-- User Dropdown Menu -->
                    <div class="relative">
                        <button
                            type="button"
                            @click="isUserDropdownOpen = !isUserDropdownOpen"
                            class="flex items-center gap-2 rounded-lg p-1.5 text-sm transition-colors hover:bg-slate-100 dark:hover:bg-slate-800"
                        >
                            <div
                                class="bg-accent-500 flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold text-white shadow-xs"
                            >
                                AD
                            </div>
                            <span class="text-foreground hidden font-medium lg:inline-block">
                                {{ page.props.auth?.user?.name || 'Admin' }}
                            </span>
                            <ChevronDown class="text-muted-foreground h-4 w-4" />
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
                                    <p class="text-foreground truncate font-semibold">
                                        {{ page.props.auth?.user?.name || 'Administrator' }}
                                    </p>
                                    <p class="text-muted-foreground truncate text-xs">
                                        {{ page.props.auth?.user?.email }}
                                    </p>
                                </div>

                                <Link
                                    href="/admin/settings"
                                    class="text-foreground flex items-center gap-2 px-4 py-2 transition-colors hover:bg-slate-100 dark:hover:bg-slate-800"
                                >
                                    <UserIcon class="text-muted-foreground h-4 w-4" />
                                    <span>Profil Saya</span>
                                </Link>

                                <div class="border-border/60 my-1 border-t"></div>

                                <button
                                    type="button"
                                    @click="logout"
                                    class="text-destructive hover:bg-destructive/10 flex w-full items-center gap-2 px-4 py-2 text-left transition-colors"
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
            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
                <slot />
            </main>
        </div>
    </div>
</template>
