<script setup>
import { computed, ref } from 'vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import InvestorLayout from '@/Layouts/InvestorLayout.vue';
import AppButton from '@/Components/Common/AppButton.vue';
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
    ExternalLink,
    ChevronLeft,
    ChevronRight,
    RefreshCw,
    Calendar,
} from 'lucide-vue-next';

const props = defineProps({
    notificationsData: {
        type: Object,
        required: true,
    },
    currentFilter: {
        type: String,
        default: 'all',
    },
});

const page = usePage();
const isProcessing = ref(false);

// Pilih layout secara dinamis berdasarkan peran pengguna
const userRole = computed(() => page.props.auth?.user?.role);
const layoutComponent = computed(() => {
    return userRole.value === 'administrator' ? AdminLayout : InvestorLayout;
});

const unreadCount = computed(() => page.props.notifications?.unread_count || 0);

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

const handleOpenNotification = (item) => {
    if (!item.is_read) {
        router.post(
            `/notifications/${item.id}/read`,
            {},
            {
                preserveScroll: true,
                preserveState: true,
                onFinish: () => {
                    navigateToUrl(item.url);
                },
            }
        );
    } else {
        navigateToUrl(item.url);
    }
};

const markAllAsRead = () => {
    if (unreadCount.value === 0 || isProcessing.value) return;

    isProcessing.value = true;
    router.post(
        '/notifications/mark-all-read',
        {},
        {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                isProcessing.value = false;
            },
        }
    );
};

const goToPage = (url) => {
    if (!url) return;
    router.visit(url, {
        preserveScroll: true,
        preserveState: true,
    });
};
</script>

<template>
    <component
        :is="layoutComponent"
        title="Semua Notifikasi"
        subtitle="Riwayat seluruh pemberitahuan dan aktivitas sistem."
        :breadcrumbs="[{ label: 'Sistem' }, { label: 'Semua Notifikasi' }]"
    >
        <template #header-actions>
            <AppButton
                type="custom"
                variant="outline"
                size="sm"
                :icon="CheckCheck"
                label="Tandai Semua Dibaca"
                :disabled="unreadCount === 0 || isProcessing"
                :loading="isProcessing"
                loading-text="Memproses..."
                @click="markAllAsRead"
            />
        </template>

        <div class="space-y-6">
            <!-- Filter Tabs -->
            <div class="border-border flex items-center gap-2 border-b pb-3">
                <Link
                    href="/notifications"
                    :class="[
                        'flex min-h-[36px] cursor-pointer items-center justify-center rounded-lg px-3.5 py-1.5 text-xs font-bold transition-colors',
                        currentFilter === 'all'
                            ? 'bg-primary-600 text-white shadow-xs'
                            : 'bg-muted/60 text-muted-foreground hover:text-foreground hover:bg-muted',
                    ]"
                >
                    Semua
                </Link>

                <Link
                    href="/notifications?filter=unread"
                    :class="[
                        'flex min-h-[36px] cursor-pointer items-center justify-center gap-1.5 rounded-lg px-3.5 py-1.5 text-xs font-bold transition-colors',
                        currentFilter === 'unread'
                            ? 'bg-primary-600 text-white shadow-xs'
                            : 'bg-muted/60 text-muted-foreground hover:text-foreground hover:bg-muted',
                    ]"
                >
                    <span>Belum Dibaca</span>
                    <span
                        v-if="unreadCount > 0"
                        :class="[
                            'py-0.2 rounded-full px-1.5 text-[10px] font-extrabold',
                            currentFilter === 'unread'
                                ? 'text-primary-700 bg-white'
                                : 'bg-accent-500 text-white',
                        ]"
                    >
                        {{ unreadCount }}
                    </span>
                </Link>
            </div>

            <!-- List Container -->
            <div class="bg-card border-border overflow-hidden rounded-2xl border shadow-xs">
                <!-- Empty State -->
                <div v-if="notificationsData.data.length === 0" class="px-4 py-14 text-center">
                    <div
                        class="bg-muted text-muted-foreground mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full"
                    >
                        <Bell class="h-6 w-6 opacity-60" />
                    </div>
                    <h3 class="text-foreground text-sm font-bold">Tidak Ada Notifikasi</h3>
                    <p class="text-muted-foreground mx-auto mt-1 max-w-sm text-xs">
                        {{
                            currentFilter === 'unread'
                                ? 'Semua notifikasi sudah Anda baca.'
                                : 'Belum ada pemberitahuan sistem yang tercatat pada akun Anda.'
                        }}
                    </p>
                </div>

                <!-- Notification Cards List -->
                <div v-else class="divide-border divide-y">
                    <div
                        v-for="item in notificationsData.data"
                        :key="item.id"
                        :class="[
                            'flex items-start justify-between gap-4 p-4 transition-colors sm:p-5',
                            item.is_read
                                ? 'bg-card hover:bg-muted/30'
                                : 'bg-primary-50/30 dark:bg-primary-950/15 hover:bg-primary-50/50 dark:hover:bg-primary-950/25',
                        ]"
                    >
                        <div class="flex min-w-0 flex-1 items-start gap-3.5">
                            <!-- Icon -->
                            <div
                                :class="[
                                    'mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border shadow-xs',
                                    getIconColor(item.icon),
                                ]"
                            >
                                <component :is="getIcon(item.icon)" class="h-5 w-5" />
                            </div>

                            <!-- Detail Content -->
                            <div class="min-w-0 flex-1 space-y-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <button
                                        v-if="item.url"
                                        type="button"
                                        @click="handleOpenNotification(item)"
                                        class="text-foreground hover:text-primary-600 dark:hover:text-primary-400 group inline-flex cursor-pointer items-center gap-1.5 text-left text-sm font-bold transition-colors hover:underline"
                                        title="Buka halaman terkait"
                                    >
                                        <span>{{ item.title }}</span>
                                        <ExternalLink
                                            class="text-muted-foreground group-hover:text-primary-600 dark:group-hover:text-primary-400 h-3.5 w-3.5 shrink-0 transition-colors"
                                        />
                                    </button>
                                    <h4 v-else class="text-foreground text-sm font-bold">
                                        {{ item.title }}
                                    </h4>

                                    <span
                                        v-if="!item.is_read"
                                        class="inline-flex items-center rounded-full border border-accent-500/30 bg-accent-500/15  px-2 py-0.5 text-[10px] font-bold text-accent-700 "
                                    >
                                        Baru
                                    </span>
                                </div>

                                <p
                                    class="text-muted-foreground text-xs leading-relaxed break-words"
                                >
                                    {{ item.message }}
                                </p>

                                <div
                                    class="text-muted-foreground flex items-center gap-2 pt-1 text-[11px]"
                                >
                                    <span class="text-foreground/80 font-medium">{{
                                        item.full_date
                                    }}</span>
                                    <span>•</span>
                                    <span>{{ item.created_at }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination Footer -->
                <div
                    v-if="notificationsData.total > 0"
                    class="bg-muted/30 border-border text-muted-foreground flex flex-col items-center justify-between gap-3 border-t px-4 py-3 text-xs sm:flex-row"
                >
                    <div>
                        Menampilkan
                        <strong class="text-foreground font-semibold">{{
                            notificationsData.from || 0
                        }}</strong>
                        sampai
                        <strong class="text-foreground font-semibold">{{
                            notificationsData.to || 0
                        }}</strong>
                        dari
                        <strong class="text-foreground font-semibold">{{
                            notificationsData.total || 0
                        }}</strong>
                        notifikasi
                    </div>

                    <!-- Page Links -->
                    <div
                        v-if="notificationsData.links && notificationsData.links.length > 3"
                        class="flex items-center gap-1"
                    >
                        <template v-for="(link, lIdx) in notificationsData.links" :key="lIdx">
                            <!-- Tombol Sebelumnya -->
                            <button
                                v-if="lIdx === 0"
                                type="button"
                                :disabled="!link.url"
                                @click="goToPage(link.url)"
                                class="border-input bg-background hover:bg-muted rounded-lg border p-1.5 transition-colors disabled:cursor-not-allowed disabled:opacity-40"
                                aria-label="Halaman Sebelumnya"
                            >
                                <ChevronLeft class="h-4 w-4" />
                            </button>

                            <!-- Tombol Selanjutnya -->
                            <button
                                v-else-if="lIdx === notificationsData.links.length - 1"
                                type="button"
                                :disabled="!link.url"
                                @click="goToPage(link.url)"
                                class="border-input bg-background hover:bg-muted rounded-lg border p-1.5 transition-colors disabled:cursor-not-allowed disabled:opacity-40"
                                aria-label="Halaman Selanjutnya"
                            >
                                <ChevronRight class="h-4 w-4" />
                            </button>

                            <!-- Nomor Halaman -->
                            <button
                                v-else
                                type="button"
                                :disabled="!link.url || link.active"
                                @click="goToPage(link.url)"
                                :class="[
                                    'flex h-8 min-w-[32px] items-center justify-center rounded-lg px-2 text-xs font-medium transition-colors',
                                    link.active
                                        ? 'bg-primary-600 font-bold text-white shadow-xs'
                                        : 'border-input bg-background hover:bg-muted text-foreground border',
                                ]"
                                v-html="link.label"
                            ></button>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </component>
</template>
