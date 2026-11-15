<script setup>
import { computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AppPageHeader from '@/Components/Common/AppPageHeader.vue';
import AppDataTable from '@/Components/Common/AppDataTable.vue';
import AppTableAction from '@/Components/Common/AppTableAction.vue';
import {
    LifeBuoy,
    MessageSquare,
    Clock,
    User,
    CheckCircle2,
    AlertCircle,
    Eye,
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
            butuh_respons: 0,
            open: 0,
            in_progress: 0,
            answered: 0,
            resolved: 0,
            closed: 0,
        }),
    },
    status_labels: {
        type: Object,
        default: () => ({}),
    },
});

const columns = [
    { key: 'nomor', label: 'Nomor & Subjek Kendala' },
    { key: 'user', label: 'Pelapor / Member' },
    { key: 'status', label: 'Status' },
    { key: 'balasan_count', label: 'Percakapan' },
    { key: 'last_reply_at', label: 'Terakhir Diperbarui' },
    { key: 'actions', label: 'Aksi', align: 'right' },
];

const activeStatus = computed(() => props.filters.status || 'all');

const applyStatusTab = (status) => {
    router.get(
        route('admin.tickets.index'),
        {
            ...props.filters,
            status: status !== 'all' ? status : undefined,
            page: 1,
        },
        { preserveState: true, replace: true }
    );
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
    <AdminLayout title="Tiket Bantuan">
        <Head title="Tiket Bantuan & Pelaporan Kendala" />

        <div class="space-y-6">
            <!-- Header Halaman -->
            <AppPageHeader
                title="Tiket Bantuan & Pelaporan Kendala"
                subtitle="Kelola laporan kendala member dan berikan tanggapan solusi."
            />

            <!-- Tab Ringkasan Status -->
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-none bg-card border border-border rounded-2xl p-2.5 shadow-2xs">
                <button
                    type="button"
                    :class="[
                        'px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all shrink-0 select-none cursor-pointer',
                        activeStatus === 'all'
                            ? 'bg-primary text-primary-foreground shadow-xs'
                            : 'bg-muted text-muted-foreground hover:bg-muted/80 hover:text-foreground'
                    ]"
                    @click="applyStatusTab('all')"
                >
                    Semua ({{ counts.total }})
                </button>

                <button
                    v-if="counts.butuh_respons > 0"
                    type="button"
                    :class="[
                        'px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all shrink-0 select-none cursor-pointer flex items-center gap-1.5',
                        activeStatus === 'open'
                            ? 'bg-blue-600 text-white shadow-xs'
                            : 'bg-blue-500/15 text-blue-700 dark:text-blue-300 hover:bg-blue-500/25 border border-blue-500/30'
                    ]"
                    @click="applyStatusTab('open')"
                >
                    <span>Butuh Respons</span>
                    <span class="font-mono">({{ counts.butuh_respons }})</span>
                </button>

                <button
                    type="button"
                    :class="[
                        'px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all shrink-0 select-none cursor-pointer',
                        activeStatus === 'in_progress'
                            ? 'bg-primary text-primary-foreground shadow-xs'
                            : 'bg-muted text-muted-foreground hover:bg-muted/80 hover:text-foreground'
                    ]"
                    @click="applyStatusTab('in_progress')"
                >
                    Sedang Diproses ({{ counts.in_progress }})
                </button>

                <button
                    type="button"
                    :class="[
                        'px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all shrink-0 select-none cursor-pointer',
                        activeStatus === 'answered'
                            ? 'bg-primary text-primary-foreground shadow-xs'
                            : 'bg-muted text-muted-foreground hover:bg-muted/80 hover:text-foreground'
                    ]"
                    @click="applyStatusTab('answered')"
                >
                    Dijawab ({{ counts.answered }})
                </button>

                <button
                    type="button"
                    :class="[
                        'px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all shrink-0 select-none cursor-pointer',
                        activeStatus === 'resolved'
                            ? 'bg-primary text-primary-foreground shadow-xs'
                            : 'bg-muted text-muted-foreground hover:bg-muted/80 hover:text-foreground'
                    ]"
                    @click="applyStatusTab('resolved')"
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
                    @click="applyStatusTab('closed')"
                >
                    Ditutup ({{ counts.closed }})
                </button>
            </div>

            <!-- Tabel Data Tiket via AppDataTable -->
            <AppDataTable
                :data="tickets"
                :columns="columns"
                :filters="filters"
                :fetch-url="route('admin.tickets.index')"
                exportable
                search-placeholder="Cari nomor tiket, subjek, nama atau username member..."
                empty-title="Tidak Ada Tiket Bantuan"
                empty-message="Belum ada tiket bantuan yang diajukan oleh member atau sesuaikan kriteria filter pencarian Anda."
            >
                <!-- Filter Kustom: Status -->
                <template #filters="{ applyFilters }">
                    <div class="flex items-center gap-2 flex-wrap">
                        <select
                            :value="filters.status || ''"
                            class="h-9 rounded-lg border border-input bg-background px-3 text-xs text-foreground transition-all focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none"
                            @change="(e) => applyFilters({ status: e.target.value || undefined, page: 1 })"
                        >
                            <option value="">Semua Status</option>
                            <option
                                v-for="(sLabel, sKey) in status_labels"
                                :key="sKey"
                                :value="sKey"
                            >
                                {{ sLabel }}
                            </option>
                        </select>
                    </div>
                </template>

                <!-- Kolom Nomor & Subjek -->
                <template #column-nomor="{ item }">
                    <div class="space-y-1 py-1 max-w-sm">
                        <div class="flex items-center gap-1.5">
                            <span class="font-mono text-xs font-bold text-primary bg-primary/10 px-2 py-0.5 rounded-md border border-primary/20">
                                {{ item.nomor }}
                            </span>
                            <span
                                v-if="['open', 'in_progress'].includes(item.status) && item.last_reply_by === 'member'"
                                class="text-[10px] bg-rose-500/15 text-rose-700 dark:text-rose-300 font-semibold px-1.5 py-0.5 rounded-md border border-rose-500/30 whitespace-nowrap"
                            >
                                Perlu Balasan
                            </span>
                        </div>
                        <p class="font-bold text-foreground text-sm truncate">
                            {{ item.subjek }}
                        </p>
                    </div>
                </template>

                <!-- Kolom Pelapor / Member -->
                <template #column-user="{ item }">
                    <div class="space-y-0.5 py-1">
                        <div class="font-semibold text-foreground text-xs flex items-center gap-1">
                            <span>{{ item.user?.name || '-' }}</span>
                            <span v-if="item.user?.username" class="text-muted-foreground font-mono">
                                (@{{ item.user.username }})
                            </span>
                        </div>
                        <div class="text-[11px] text-muted-foreground truncate max-w-[180px]">
                            {{ item.user?.email || '-' }}
                        </div>
                    </div>
                </template>

                <!-- Kolom Status -->
                <template #column-status="{ item }">
                    <span
                        :class="[
                            'inline-block text-[11px] font-semibold px-2.5 py-0.5 rounded-full border whitespace-nowrap',
                            getStatusBadgeClass(item.status)
                        ]"
                    >
                        {{ item.status_label || status_labels[item.status] || item.status }}
                    </span>
                </template>

                <!-- Kolom Percakapan -->
                <template #column-balasan_count="{ item }">
                    <div class="flex items-center gap-1 text-xs text-muted-foreground">
                        <MessageSquare class="size-3.5" />
                        <span class="font-semibold text-foreground">{{ item.balasan_count || 1 }}</span>
                    </div>
                </template>

                <!-- Kolom Terakhir Diperbarui -->
                <template #column-last_reply_at="{ item }">
                    <div class="text-xs text-foreground whitespace-nowrap space-y-0.5">
                        <div>{{ formatDate(item.last_reply_at || item.created_at) }}</div>
                        <div v-if="item.last_reply_by" class="text-[10px] text-muted-foreground">
                            Oleh: <span class="capitalize font-medium">{{ item.last_reply_by }}</span>
                        </div>
                    </div>
                </template>

                <!-- Kolom Aksi -->
                <template #column-actions="{ item }">
                    <div class="flex items-center justify-end">
                        <AppTableAction
                            type="detail"
                            label="Lihat Utas Tiket"
                            title="Buka detail percakapan tiket"
                            :href="route('admin.tickets.show', item.id)"
                        />
                    </div>
                </template>
            </AppDataTable>
        </div>
    </AdminLayout>
</template>
