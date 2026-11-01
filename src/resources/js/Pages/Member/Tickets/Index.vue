<script setup>
import { ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import MemberLayout from '@/Layouts/MemberLayout.vue';
import AppPageHeader from '@/Components/Common/AppPageHeader.vue';
import AppButton from '@/Components/Common/AppButton.vue';
import {
    LifeBuoy,
    Search,
    MessageSquare,
    Clock,
    CheckCircle2,
    Lock,
    ArrowRight,
    HelpCircle,
} from 'lucide-vue-next';

const props = defineProps({
    tickets: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    counts: {
        type: Object,
        default: () => ({
            total: 0,
            active: 0,
            resolved: 0,
            closed: 0,
        }),
    },
    status_labels: {
        type: Object,
        default: () => ({}),
    },
});

const searchQuery = ref(props.filters.search || '');
const activeStatus = ref(props.filters.status || 'all');
const lastSentSearch = ref(searchQuery.value);
let debounceTimeout = null;

const applyFilter = (status) => {
    activeStatus.value = status;
    router.get(
        route('member.tickets.index'),
        {
            search: searchQuery.value || undefined,
            status: status !== 'all' ? status : undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true }
    );
};

const handleSearch = () => {
    lastSentSearch.value = searchQuery.value;
    router.get(
        route('member.tickets.index'),
        {
            search: searchQuery.value || undefined,
            status: activeStatus.value !== 'all' ? activeStatus.value : undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true }
    );
};

// Live search otomatis saat mengetik (debounce 350ms)
watch(searchQuery, (newVal) => {
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => {
        if (newVal !== lastSentSearch.value) {
            handleSearch();
        }
    }, 350);
});

// Sinkronisasi jika filters.search berubah dari luar / navigasi browser
watch(
    () => props.filters.search,
    (newSearch) => {
        const val = newSearch || '';
        if (val !== searchQuery.value) {
            searchQuery.value = val;
            lastSentSearch.value = val;
        }
    }
);

const handleSearchEnter = () => {
    clearTimeout(debounceTimeout);
    if (searchQuery.value !== lastSentSearch.value) {
        handleSearch();
    }
};

const clearSearch = () => {
    clearTimeout(debounceTimeout);
    searchQuery.value = '';
    handleSearch();
};

const getStatusBadgeClass = (status) => {
    switch (status) {
        case 'open':
            return 'bg-blue-500/15 text-blue-700 dark:text-blue-300 border-blue-500/30';
        case 'in_progress':
            return 'bg-amber-500/15 text-amber-700 dark:text-amber-300 border-amber-500/30';
        case 'answered':
            return 'bg-purple-500/15 text-purple-700 dark:text-purple-300 border-purple-500/30';
        case 'resolved':
            return 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border-emerald-500/30';
        case 'closed':
            return 'bg-muted text-muted-foreground border-border';
        default:
            return 'bg-muted text-muted-foreground border-border';
    }
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
};
</script>

<template>
    <MemberLayout title="Tiket Bantuan">
        <Head title="Tiket Bantuan & Kendala" />

        <div class="space-y-6">
            <!-- Header Halaman -->
            <AppPageHeader
                title="Tiket Bantuan & Pelaporan Kendala"
                subtitle="Laporkan kendala atau bug teknis langsung ke tim admin."
            >
                <template #actions>
                    <AppButton
                        type="create"
                        label="Buat Tiket Baru"
                        :href="route('member.tickets.create')"
                        class="rounded-xl shadow-xs"
                    />
                </template>
            </AppPageHeader>

            <!-- Toolbar: Filter Status & Pencarian -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4 bg-card border border-border rounded-2xl p-4 shadow-2xs">
                <!-- Status Tab Buttons -->
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0 scrollbar-none">
                    <button
                        type="button"
                        :class="[
                            'px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all shrink-0 select-none cursor-pointer',
                            activeStatus === 'all'
                                ? 'bg-primary text-primary-foreground shadow-xs'
                                : 'bg-muted text-muted-foreground hover:bg-muted/80 hover:text-foreground'
                        ]"
                        @click="applyFilter('all')"
                    >
                        Semua ({{ counts.total }})
                    </button>
                    <button
                        type="button"
                        :class="[
                            'px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all shrink-0 select-none cursor-pointer',
                            activeStatus === 'active'
                                ? 'bg-primary text-primary-foreground shadow-xs'
                                : 'bg-muted text-muted-foreground hover:bg-muted/80 hover:text-foreground'
                        ]"
                        @click="applyFilter('active')"
                    >
                        Aktif ({{ counts.active }})
                    </button>
                    <button
                        type="button"
                        :class="[
                            'px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all shrink-0 select-none cursor-pointer',
                            activeStatus === 'resolved'
                                ? 'bg-primary text-primary-foreground shadow-xs'
                                : 'bg-muted text-muted-foreground hover:bg-muted/80 hover:text-foreground'
                        ]"
                        @click="applyFilter('resolved')"
                    >
                        Selesai ({{ counts.resolved }})
                    </button>
                    <button
                        type="button"
                        :class="[
                            'px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all shrink-0 select-none cursor-pointer',
                            activeStatus === 'closed'
                                ? 'bg-primary text-primary-foreground shadow-xs'
                                : 'bg-muted text-muted-foreground hover:bg-muted/80 hover:text-foreground'
                        ]"
                        @click="applyFilter('closed')"
                    >
                        Ditutup ({{ counts.closed }})
                    </button>
                </div>

                <!-- Input Pencarian -->
                <div class="relative w-full sm:w-72">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari nomor atau subjek tiket..."
                        class="w-full rounded-xl border border-border bg-background pl-9 pr-8 py-2 text-xs text-foreground placeholder:text-muted-foreground/60 focus:outline-none focus:ring-2 focus:ring-primary/40 transition-all"
                        @keydown.enter.prevent="handleSearchEnter"
                    />
                    <button
                        v-if="searchQuery"
                        type="button"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-xs text-muted-foreground hover:text-foreground cursor-pointer p-0.5 rounded"
                        title="Hapus pencarian"
                        @click="clearSearch"
                    >
                        ×
                    </button>
                </div>
            </div>

            <!-- Daftar Tiket -->
            <div v-if="tickets.data && tickets.data.length > 0" class="space-y-3">
                <div
                    v-for="t in tickets.data"
                    :key="t.id"
                    class="bg-card border border-border hover:border-primary/40 rounded-2xl p-4 sm:p-5 transition-all shadow-2xs hover:shadow-xs group flex flex-col sm:flex-row sm:items-center justify-between gap-4"
                >
                    <div class="space-y-2 flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-mono text-xs font-bold text-primary bg-primary/10 px-2 py-0.5 rounded-md border border-primary/20">
                                {{ t.nomor }}
                            </span>
                            <span
                                :class="[
                                    'text-[11px] font-semibold px-2 py-0.5 rounded-full border',
                                    getStatusBadgeClass(t.status)
                                ]"
                            >
                                {{ t.status_label || status_labels[t.status] || t.status }}
                            </span>
                        </div>

                        <h3 class="text-base font-bold text-foreground group-hover:text-primary transition-colors truncate">
                            {{ t.subjek }}
                        </h3>

                        <div class="flex items-center gap-4 text-xs text-muted-foreground flex-wrap">
                            <span class="flex items-center gap-1.5">
                                <Clock class="size-3.5" />
                                Update: {{ formatDate(t.last_reply_at || t.created_at) }}
                            </span>
                            <span class="flex items-center gap-1.5">
                                <MessageSquare class="size-3.5" />
                                {{ t.balasan_count || 1 }} Percakapan
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 shrink-0 pt-2 sm:pt-0 border-t sm:border-t-0 border-border/50">
                        <AppButton
                            :href="route('member.tickets.show', t.id)"
                            variant="outline"
                            size="sm"
                            :icon="ArrowRight"
                            icon-position="right"
                            label="Lihat Percakapan"
                            class="rounded-xl w-full sm:w-auto"
                        />
                    </div>
                </div>

                <!-- Paginasi -->
                <div v-if="tickets.links && tickets.links.length > 3" class="flex justify-center pt-4">
                    <nav class="flex items-center gap-1">
                        <template v-for="(link, lIdx) in tickets.links" :key="lIdx">
                            <AppButton
                                v-if="link.url"
                                :href="link.url"
                                :variant="link.active ? 'primary' : 'outline'"
                                size="xs"
                                class="rounded-lg min-w-[32px] px-2"
                                v-html="link.label"
                            />
                            <span
                                v-else
                                class="text-xs text-muted-foreground px-2"
                                v-html="link.label"
                            />
                        </template>
                    </nav>
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-else
                class="bg-card border border-border rounded-2xl p-8 sm:p-12 text-center space-y-4 shadow-2xs"
            >
                <div class="size-14 rounded-2xl bg-primary/10 text-primary mx-auto flex items-center justify-center">
                    <LifeBuoy class="size-7" />
                </div>
                <div class="space-y-1.5 max-w-md mx-auto">
                    <h3 class="text-lg font-bold text-foreground">
                        {{ searchQuery ? 'Tidak ada tiket yang cocok' : 'Belum Ada Tiket Bantuan' }}
                    </h3>
                    <p class="text-xs sm:text-sm text-muted-foreground leading-relaxed">
                        {{
                            searchQuery
                                ? 'Coba ubah kata kunci pencarian atau status filter untuk menemukan tiket Anda.'
                                : 'Apakah Anda mengalami kendala pada akun, transaksi, atau fitur sistem? Tim kami siap membantu Anda.'
                        }}
                    </p>
                </div>

                <div class="pt-2">
                    <AppButton
                        v-if="searchQuery"
                        variant="outline"
                        size="sm"
                        label="Hapus Filter Pencarian"
                        class="rounded-xl"
                        @click="clearSearch"
                    />
                    <AppButton
                        v-else
                        type="create"
                        label="Buat Tiket Bantuan Baru"
                        :href="route('member.tickets.create')"
                        class="rounded-xl"
                    />
                </div>
            </div>
        </div>
    </MemberLayout>
</template>
