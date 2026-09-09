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
import AppAlert from '@/Components/Common/AppAlert.vue';
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
            { label: 'Dasbor Admin', href: '/admin/dashboard', icon: LayoutDashboard, routeName: 'admin.dashboard' },
        ],
    },
    {
        title: 'Operasional & Validasi',
        items: [
            { label: 'Verifikasi Legalitas', href: '/admin/verifikasi-legalitas', icon: FileCheck, routeName: 'admin.verifikasi-legalitas' },
            { label: 'Antrean Validasi', href: '/admin/antrean-validasi', icon: Inbox, badge: '3', routeName: 'admin.antrean-validasi' },
        ],
    },
    {
        title: 'Manajemen Afiliasi',
        items: [
            { label: 'Klaim Referral Susulan', href: '/admin/klaim-susulan', icon: UserPlus, routeName: 'admin.klaim-susulan' },
            { label: 'Pencairan Komisi', href: '/admin/pencairan-komisi', icon: Wallet, routeName: 'admin.pencairan-komisi' },
        ],
    },
    {
        title: 'Manajemen Data',
        items: [
            { label: 'Tautan Undangan', href: '/admin/invitations', icon: Link2, routeName: 'admin.invitations.index' },
            { label: 'Data Investor', href: '/admin/investors', icon: Users, routeName: 'admin.investors.index' },
            { label: 'Data Afiliator', href: '/admin/affiliators', icon: UserCheck, routeName: 'admin.affiliators.index' },
        ],
    },
    {
        title: 'Kelola Konten (CMS)',
        items: [
            { label: 'Kamus Forex', href: '/admin/kamus-forex', icon: BookOpen, routeName: 'admin.kamus-forex' },
            { label: 'Pusat Bantuan (FAQ)', href: '/admin/faq', icon: HelpCircle, routeName: 'admin.faq' },
            { label: 'Syarat & Ketentuan', href: '/admin/syarat-ketentuan', icon: FileText, routeName: 'admin.syarat-ketentuan' },
            { label: 'Kebijakan Privasi', href: '/admin/kebijakan-privasi', icon: Shield, routeName: 'admin.kebijakan-privasi' },
        ],
    },
    {
        title: 'Pengaturan',
        items: [
            { label: 'Pengaturan Master', href: '/admin/settings', icon: Settings, routeName: 'admin.settings' },
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
                <Link href="/admin/dashboard" class="flex items-center gap-1">
                    <span class="text-accent-500 font-extrabold text-2xl tracking-tight">YZD</span>
                    <span class="text-white font-bold text-xl tracking-tight">Trading</span>
                    <span class="ml-2 text-[10px] font-semibold bg-accent-500/20 text-accent-300 border border-accent-500/40 px-1.5 py-0.5 rounded">ADMIN</span>
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
                <div v-for="(section, idx) in navSections" :key="idx" class="space-y-1.5">
                    <p class="px-3 text-[11px] font-semibold uppercase tracking-wider text-white/50">
                        {{ section.title }}
                    </p>

                    <div class="space-y-1">
                        <Link
                            v-for="item in section.items"
                            :key="item.href"
                            :href="item.href"
                            @click="isMobileSidebarOpen = false"
                            :class="[
                                'flex items-center justify-between px-3 py-2 rounded-lg transition-colors group',
                                isActive(item)
                                    ? 'bg-primary-700 dark:bg-primary-900/60 text-white font-semibold shadow-inner'
                                    : 'text-white/80 hover:text-white hover:bg-white/10 dark:hover:bg-slate-800/80'
                            ]"
                        >
                            <div class="flex items-center gap-3">
                                <component :is="item.icon" class="w-4 h-4 text-white/75 group-hover:text-white transition-colors" />
                                <span>{{ item.label }}</span>
                            </div>

                            <span
                                v-if="item.badge"
                                class="text-xs font-bold px-2 py-0.5 rounded-full bg-accent-500 text-white shadow-xs"
                            >
                                {{ item.badge }}
                            </span>
                        </Link>
                    </div>
                </div>
            </nav>

            <!-- Bottom User Profile Area -->
            <div class="p-4 border-t border-white/15 dark:border-slate-800 bg-primary-700/50 dark:bg-slate-900/80 flex items-center justify-between">
                <div class="flex items-center gap-3 truncate">
                    <div class="w-9 h-9 rounded-full bg-accent-500 text-white font-bold flex items-center justify-center text-sm shadow-sm flex-shrink-0">
                        AD
                    </div>
                    <div class="truncate text-xs">
                        <p class="font-semibold text-white truncate">{{ page.props.auth?.user?.name || 'Administrator' }}</p>
                        <p class="text-white/60 truncate">Admin Portal</p>
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

                    <!-- Notifications Bell (stub) -->
                    <div class="relative">
                        <button
                            type="button"
                            class="p-2 rounded-lg text-muted-foreground hover:text-foreground hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                            aria-label="Notifikasi"
                        >
                            <Bell class="w-5 h-5" />
                            <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-accent-500 ring-2 ring-white dark:ring-slate-900"></span>
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
                                AD
                            </div>
                            <span class="hidden lg:inline-block font-medium text-foreground">
                                {{ page.props.auth?.user?.name || 'Admin' }}
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
                                    <p class="font-semibold text-foreground truncate">{{ page.props.auth?.user?.name || 'Administrator' }}</p>
                                    <p class="text-xs text-muted-foreground truncate">{{ page.props.auth?.user?.email }}</p>
                                </div>

                                <Link
                                    href="/admin/settings"
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
                <AppAlert />
                <slot />
            </main>
        </div>
    </div>
</template>
