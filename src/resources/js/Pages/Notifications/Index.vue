<script setup>
import { computed, ref } from 'vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import InvestorLayout from '@/Layouts/InvestorLayout.vue';
import { Button } from '@/Components/ui/button';
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
    Check,
    ChevronLeft,
    ChevronRight,
    RefreshCw,
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
        case 'refresh-cw':
            return 'text-indigo-800 bg-indigo-100 dark:text-indigo-200 dark:bg-indigo-950/80 border-indigo-300 dark:border-indigo-800';
        default:
            return 'text-slate-800 bg-slate-100 dark:text-slate-200 dark:bg-slate-800 border-slate-300 dark:border-slate-700';
    }
};

const handleOpenNotification = (item) => {
    if (!item.is_read) {
        router.post(`/notifications/${item.id}/read`, {}, {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                if (item.url) {
                    router.visit(item.url);
                }
            },
        });
    } else if (item.url) {
        router.visit(item.url);
    }
};

const markAsRead = (item) => {
    if (item.is_read) return;
    router.post(`/notifications/${item.id}/read`, {}, {
        preserveScroll: true,
        preserveState: true,
    });
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

const goToPage = (url) => {
    if (!url) return;
    router.visit(url, {
        preserveScroll: true,
        preserveState: true,
    });
};
</script>

<template>
    <component :is="layoutComponent" title="Semua Notifikasi">
        <div class="space-y-6">
            <!-- Header Card -->
            <div class="rounded-2xl bg-card border border-border p-5 sm:p-6 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-black tracking-tight text-foreground flex items-center gap-2.5">
                        <Bell class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                        <span>Semua Notifikasi</span>
                    </h2>
                    <p class="text-xs sm:text-sm text-muted-foreground mt-1 leading-relaxed">
                        Riwayat seluruh pemberitahuan sistem.
                    </p>
                </div>

                <div class="flex items-center gap-2 self-start sm:self-auto">
                    <Button
                        type="button"
                        variant="outline"
                        @click="markAllAsRead"
                        :disabled="unreadCount === 0 || isProcessing"
                        class="text-xs font-semibold flex items-center gap-1.5 cursor-pointer min-h-[38px] disabled:opacity-50"
                    >
                        <CheckCheck class="w-4 h-4 text-primary-600 dark:text-primary-400" />
                        <span>Tandai Semua Dibaca</span>
                    </Button>
                </div>
            </div>

            <!-- Filter Tabs -->
            <div class="flex items-center gap-2 border-b border-border pb-3">
                <Link
                    href="/notifications"
                    :class="[
                        'px-3.5 py-1.5 rounded-lg text-xs font-bold transition-colors cursor-pointer min-h-[36px] flex items-center justify-center',
                        currentFilter === 'all'
                            ? 'bg-primary-600 text-white shadow-xs'
                            : 'bg-muted/60 text-muted-foreground hover:text-foreground hover:bg-muted'
                    ]"
                >
                    Semua
                </Link>

                <Link
                    href="/notifications?filter=unread"
                    :class="[
                        'px-3.5 py-1.5 rounded-lg text-xs font-bold transition-colors cursor-pointer min-h-[36px] flex items-center justify-center gap-1.5',
                        currentFilter === 'unread'
                            ? 'bg-primary-600 text-white shadow-xs'
                            : 'bg-muted/60 text-muted-foreground hover:text-foreground hover:bg-muted'
                    ]"
                >
                    <span>Belum Dibaca</span>
                    <span
                        v-if="unreadCount > 0"
                        :class="[
                            'px-1.5 py-0.2 rounded-full text-[10px] font-extrabold',
                            currentFilter === 'unread'
                                ? 'bg-white text-primary-700'
                                : 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-200'
                        ]"
                    >
                        {{ unreadCount }}
                    </span>
                </Link>
            </div>

            <!-- List Container -->
            <div class="bg-card rounded-2xl border border-border shadow-xs overflow-hidden">
                <!-- Empty State -->
                <div v-if="notificationsData.data.length === 0" class="py-14 px-4 text-center">
                    <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-muted flex items-center justify-center text-muted-foreground">
                        <Bell class="w-6 h-6 opacity-60" />
                    </div>
                    <h3 class="text-sm font-bold text-foreground">Tidak Ada Notifikasi</h3>
                    <p class="text-xs text-muted-foreground mt-1 max-w-sm mx-auto">
                        {{
                            currentFilter === 'unread'
                                ? 'Semua notifikasi sudah Anda baca.'
                                : 'Belum ada pemberitahuan sistem yang tercatat pada akun Anda.'
                        }}
                    </p>
                </div>

                <!-- Notification Cards List -->
                <div v-else class="divide-y divide-border">
                    <div
                        v-for="item in notificationsData.data"
                        :key="item.id"
                        :class="[
                            'p-4 sm:p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 transition-colors',
                            item.is_read
                                ? 'bg-card hover:bg-muted/30'
                                : 'bg-primary-50/30 dark:bg-primary-950/15 hover:bg-primary-50/50 dark:hover:bg-primary-950/25'
                        ]"
                    >
                        <div class="flex items-start gap-3.5 min-w-0 flex-1">
                            <!-- Icon -->
                            <div
                                :class="[
                                    'w-10 h-10 rounded-xl flex items-center justify-center shrink-0 border mt-0.5 shadow-xs',
                                    getIconColor(item.icon)
                                ]"
                            >
                                <component :is="getIcon(item.icon)" class="w-5 h-5" />
                            </div>

                            <!-- Detail Content -->
                            <div class="min-w-0 flex-1 space-y-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <button
                                        v-if="item.url"
                                        type="button"
                                        @click="handleOpenNotification(item)"
                                        class="text-sm font-bold text-foreground hover:text-primary-600 dark:hover:text-primary-400 hover:underline transition-colors text-left inline-flex items-center gap-1.5 cursor-pointer group"
                                        title="Buka halaman terkait"
                                    >
                                        <span>{{ item.title }}</span>
                                        <ExternalLink class="w-3.5 h-3.5 text-muted-foreground group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors shrink-0" />
                                    </button>
                                    <h4 v-else class="text-sm font-bold text-foreground">
                                        {{ item.title }}
                                    </h4>

                                    <span
                                        v-if="!item.is_read"
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-100 text-rose-800 dark:bg-rose-950/80 dark:text-rose-200 border border-rose-300 dark:border-rose-800"
                                    >
                                        Baru
                                    </span>
                                </div>

                                <p class="text-xs text-muted-foreground leading-relaxed break-words">
                                    {{ item.message }}
                                </p>

                                <div class="flex items-center gap-2 text-[11px] text-muted-foreground pt-1">
                                    <span class="font-medium text-foreground/80">{{ item.full_date }}</span>
                                    <span>•</span>
                                    <span>{{ item.created_at }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div
                            v-if="!item.is_read"
                            class="flex items-center gap-2 shrink-0 self-end sm:self-center w-full sm:w-auto justify-end pt-2 sm:pt-0 border-t sm:border-t-0 border-border/60"
                        >
                            <!-- Tombol Tandai Dibaca (jika unread) -->
                            <button
                                type="button"
                                @click="markAsRead(item)"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-muted-foreground hover:text-foreground bg-muted hover:bg-muted/80 rounded-lg transition-colors cursor-pointer min-h-[34px]"
                                title="Tandai sudah dibaca"
                            >
                                <Check class="w-3.5 h-3.5" />
                                <span>Tandai Dibaca</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Pagination Footer -->
                <div
                    v-if="notificationsData.total > 0"
                    class="px-4 py-3 bg-muted/30 border-t border-border flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-muted-foreground"
                >
                    <div>
                        Menampilkan <strong class="font-semibold text-foreground">{{ notificationsData.from || 0 }}</strong> sampai
                        <strong class="font-semibold text-foreground">{{ notificationsData.to || 0 }}</strong> dari
                        <strong class="font-semibold text-foreground">{{ notificationsData.total || 0 }}</strong> notifikasi
                    </div>

                    <!-- Page Links -->
                    <div v-if="notificationsData.links && notificationsData.links.length > 3" class="flex items-center gap-1">
                        <template v-for="(link, lIdx) in notificationsData.links" :key="lIdx">
                            <!-- Tombol Sebelumnya -->
                            <button
                                v-if="lIdx === 0"
                                type="button"
                                :disabled="!link.url"
                                @click="goToPage(link.url)"
                                class="p-1.5 rounded-lg border border-input bg-background hover:bg-muted disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                                aria-label="Halaman Sebelumnya"
                            >
                                <ChevronLeft class="w-4 h-4" />
                            </button>

                            <!-- Tombol Selanjutnya -->
                            <button
                                v-else-if="lIdx === notificationsData.links.length - 1"
                                type="button"
                                :disabled="!link.url"
                                @click="goToPage(link.url)"
                                class="p-1.5 rounded-lg border border-input bg-background hover:bg-muted disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                                aria-label="Halaman Selanjutnya"
                            >
                                <ChevronRight class="w-4 h-4" />
                            </button>

                            <!-- Nomor Halaman -->
                            <button
                                v-else
                                type="button"
                                :disabled="!link.url || link.active"
                                @click="goToPage(link.url)"
                                :class="[
                                    'min-w-[32px] h-8 px-2 rounded-lg font-medium text-xs transition-colors flex items-center justify-center',
                                    link.active
                                        ? 'bg-primary-600 text-white font-bold shadow-xs'
                                        : 'border border-input bg-background hover:bg-muted text-foreground'
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
