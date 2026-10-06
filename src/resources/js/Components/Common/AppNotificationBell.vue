<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { usePage, router, Link } from '@inertiajs/vue3';
import {
    Bell,
    CheckCheck,
    FileCheck,
    AlertCircle,
    UserPlus,
    FileUp,
    CheckCircle,
    XCircle,
    Info,
    ArrowRight,
    RefreshCw,
    Calendar,
} from 'lucide-vue-next';

const page = usePage();

const isOpen = ref(false);
const dropdownRef = ref(null);
const isProcessing = ref(false);

const notifications = computed(() => page.props.notifications || { unread_count: 0, items: [] });
const unreadCount = computed(() => notifications.value.unread_count || 0);
const items = computed(() => notifications.value.items || []);

const toggleDropdown = () => {
    isOpen.value = !isOpen.value;
};

const closeDropdown = () => {
    isOpen.value = false;
};

// Handle click outside to close dropdown
const handleClickOutside = (event) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        closeDropdown();
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

const getIcon = (iconName) => {
    switch (iconName) {
        case 'file-check':
            return FileCheck;
        case 'alert-circle':
            return AlertCircle;
        case 'user-plus':
            return UserPlus;
        case 'file-up':
            return FileUp;
        case 'check-circle':
            return CheckCircle;
        case 'x-circle':
            return XCircle;
        case 'refresh-cw':
            return RefreshCw;
        case 'calendar':
            return Calendar;
        default:
            return Info;
    }
};

const getIconColor = (iconName) => {
    switch (iconName) {
        case 'file-check':
        case 'check-circle':
            return 'text-emerald-700 bg-emerald-100 dark:text-emerald-300 dark:bg-emerald-950/80 border-emerald-300 dark:border-emerald-800';
        case 'alert-circle':
            return 'text-amber-800 bg-amber-100 dark:text-amber-200 dark:bg-amber-950/80 border-amber-300 dark:border-amber-800';
        case 'x-circle':
            return 'text-rose-800 bg-rose-100 dark:text-rose-200 dark:bg-rose-950/80 border-rose-300 dark:border-rose-800';
        case 'user-plus':
            return 'text-sky-800 bg-sky-100 dark:text-sky-200 dark:bg-sky-950/80 border-sky-300 dark:border-sky-800';
        case 'calendar':
            return 'text-sky-700 bg-sky-100 dark:text-sky-300 dark:bg-sky-950/80 border-sky-300 dark:border-sky-800';
        case 'refresh-cw':
            return 'text-indigo-800 bg-indigo-100 dark:text-indigo-200 dark:bg-indigo-950/80 border-indigo-300 dark:border-indigo-800';
        default:
            return 'text-slate-800 bg-slate-100 dark:text-slate-200 dark:bg-slate-800 border-slate-300 dark:border-slate-700';
    }
};

const navigateToUrl = (targetUrl) => {
    if (!targetUrl) return;

    if (targetUrl.includes('#')) {
        const [path, hash] = targetUrl.split('#');
        if (window.location.pathname === path) {
            const el = document.getElementById(hash);
            if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                window.history.pushState(null, '', targetUrl);
                return;
            }
        }
    }

    router.visit(targetUrl);
};

const handleItemClick = (item) => {
    closeDropdown();

    if (!item.is_read) {
        router.post(`/notifications/${item.id}/read`, {}, {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                navigateToUrl(item.url);
            },
        });
    } else {
        navigateToUrl(item.url);
    }
};

const markAllAsRead = () => {
    if (unreadCount.value === 0 || isProcessing.value) return;

    isProcessing.value = true;
    router.post('/notifications/mark-all-read', {}, {
        preserveScroll: true,
        preserveState: true,
        onFinish: () => {
            isProcessing.value = false;
        },
    });
};
</script>

<template>
    <div ref="dropdownRef" class="relative inline-block text-left">
        <!-- Notification Bell Trigger Button -->
        <button
            type="button"
            @click="toggleDropdown"
            class="relative p-2 rounded-lg text-slate-600 hover:text-slate-950 hover:bg-slate-100 dark:text-slate-300 dark:hover:text-white dark:hover:bg-slate-800 transition-colors cursor-pointer min-h-[38px] min-w-[38px] flex items-center justify-center focus:outline-none focus:ring-2 focus:ring-primary-500"
            :class="{ 'bg-slate-100 dark:bg-slate-800 text-slate-950 dark:text-white': isOpen }"
            :aria-expanded="isOpen"
            aria-label="Buka Notifikasi"
            title="Notifikasi"
        >
            <Bell class="w-5 h-5 text-current" />

            <!-- Unread Badge Indicator -->
            <span
                v-if="unreadCount > 0"
                class="absolute -top-1 -right-1 flex items-center justify-center min-w-[18px] h-[18px] px-1 text-[10px] font-bold text-white bg-accent-500 rounded-full border-2 border-[#f8f9ff] dark:border-slate-900 shadow-xs animate-in fade-in duration-200"
            >
                {{ unreadCount > 99 ? '99+' : unreadCount }}
            </span>
        </button>

        <!-- Notification Popover Dropdown -->
        <transition
            enter-active-class="transition ease-out duration-150"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="isOpen"
                class="fixed inset-x-3 top-16 mt-2 max-w-md mx-auto sm:absolute sm:top-full sm:right-0 sm:left-auto sm:mt-2 sm:w-96 sm:max-w-none sm:mx-0 rounded-xl bg-card text-card-foreground border border-border shadow-xl z-50 overflow-hidden origin-top sm:origin-top-right"
            >
                <!-- Popover Header -->
                <div class="px-4 py-3 border-b border-border flex items-center justify-between bg-muted/40">
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-sm text-foreground">Notifikasi</span>
                        <span
                            v-if="unreadCount > 0"
                            class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-accent-500/15 text-accent-700 dark:text-accent-300 border border-accent-500/30"
                        >
                            {{ unreadCount }} baru
                        </span>
                    </div>

                    <button
                        v-if="unreadCount > 0"
                        type="button"
                        @click="markAllAsRead"
                        :disabled="isProcessing"
                        class="text-xs font-semibold text-primary hover:text-primary-700 dark:hover:text-primary-400 hover:underline flex items-center gap-1 cursor-pointer disabled:opacity-50 py-1 px-1.5 min-h-[36px]"
                    >
                        <CheckCheck class="w-3.5 h-3.5" />
                        <span>Tandai semua dibaca</span>
                    </button>
                </div>

                <!-- Notification List Container -->
                <div class="max-h-[min(380px,calc(100dvh-180px))] overflow-y-auto divide-y divide-border/50">
                    <!-- Empty State -->
                    <div v-if="items.length === 0" class="py-10 px-4 text-center">
                        <div class="w-10 h-10 mx-auto mb-2 rounded-full bg-muted flex items-center justify-center text-muted-foreground">
                            <Bell class="w-5 h-5 opacity-60" />
                        </div>
                        <p class="text-sm font-medium text-foreground">Belum ada notifikasi</p>
                        <p class="text-xs text-muted-foreground mt-0.5">Semua pemberitahuan sistem akan muncul di sini.</p>
                    </div>

                    <!-- Items -->
                    <button
                        v-for="item in items"
                        :key="item.id"
                        type="button"
                        @click="handleItemClick(item)"
                        :class="[
                            'w-full text-left p-3.5 flex items-start gap-3 transition-colors cursor-pointer group',
                            item.is_read
                                ? 'bg-card hover:bg-muted/60 opacity-80 hover:opacity-100'
                                : 'bg-primary-50/40 dark:bg-primary-950/20 hover:bg-primary-50 dark:hover:bg-primary-950/40 font-medium'
                        ]"
                    >
                        <!-- Icon -->
                        <div
                            :class="[
                                'w-8 h-8 rounded-lg flex items-center justify-center shrink-0 border mt-0.5',
                                getIconColor(item.icon)
                            ]"
                        >
                            <component :is="getIcon(item.icon)" class="w-4 h-4" />
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-1 mb-1">
                                <h4 class="text-xs font-bold truncate text-foreground">
                                    {{ item.title }}
                                </h4>
                                <span class="text-[10px] text-muted-foreground shrink-0 font-medium">
                                    {{ item.created_at }}
                                </span>
                            </div>

                            <p class="text-xs text-muted-foreground line-clamp-2 leading-relaxed">
                                {{ item.message }}
                            </p>
                        </div>

                        <!-- Unread Dot Indicator -->
                        <span
                            v-if="!item.is_read"
                            class="w-2 h-2 rounded-full bg-primary-600 dark:bg-primary-400 shrink-0 mt-2"
                            title="Belum dibaca"
                        ></span>
                    </button>
                </div>

                <!-- Popover Footer: Lihat Semua Notifikasi -->
                <div class="p-2 border-t border-border bg-muted/40 text-center">
                    <Link
                        href="/notifications"
                        @click="closeDropdown"
                        class="inline-flex items-center justify-center gap-1.5 w-full py-2 px-3 text-xs font-bold text-primary hover:text-primary-700 dark:hover:text-primary-400 hover:bg-background/80 rounded-lg transition-colors border border-transparent hover:border-border cursor-pointer min-h-[36px]"
                    >
                        <span>Lihat semua notifikasi</span>
                        <ArrowRight class="w-3.5 h-3.5" />
                    </Link>
                </div>
            </div>
        </transition>
    </div>
</template>
