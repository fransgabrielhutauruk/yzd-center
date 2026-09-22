<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AppDataTable from '@/Components/Common/AppDataTable.vue';
import { Users, TrendingUp } from 'lucide-vue-next';

const props = defineProps({
    affiliates: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    stats: {
        type: Object,
        default: () => ({
            total_affiliators: 0,
            total_downlines: 0,
            active_downlines: 0,
        }),
    },
});

const columns = [
    { key: 'joined_at', label: 'Bergabung Sejak', sortable: false },
    { key: 'name', label: 'Mitra Affiliator', sortable: false },
    { key: 'phone', label: 'Nomor HP', sortable: false },
    { key: 'referral_code', label: 'Kode Referral', sortable: false },
    { key: 'total_downlines', label: 'Total Downline', sortable: false, align: 'center' },
    { key: 'active_downlines', label: 'Downline Aktif', sortable: false, align: 'center' },
    { key: 'status', label: 'Status Akun', sortable: false },
];
</script>

<template>
    <AdminLayout title="Jaringan Kemitraan">
        <Head title="Jaringan Kemitraan" />

        <div class="space-y-6">
            <!-- Header Halaman -->
            <div>
                <h1 class="text-xl font-bold tracking-tight text-foreground sm:text-2xl">
                    Jaringan Kemitraan & Mitra Affiliator
                </h1>
                <p class="text-xs text-muted-foreground sm:text-sm">
                    Rekapitulasi dan pemantauan seluruh mitra affiliator serta jumlah anggota jaringan downline.
                </p>
            </div>

            <!-- Kartu Ringkasan Jaringan Kemitraan (Data Perbaris) -->
            <div class="w-full max-w-md">
                <div class="rounded-xl border border-border/80 bg-card p-4 shadow-xs">
                    <div class="space-y-3">
                        <!-- Baris 1: Total Mitra Affiliator -->
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-primary-500/10 text-primary-600 dark:text-primary-400">
                                    <Users class="h-4 w-4" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-foreground truncate">Total Mitra Affiliator</p>
                                    <p class="text-[11px] text-muted-foreground truncate">Mitra pemegang kode referral</p>
                                </div>
                            </div>
                            <span class="text-lg sm:text-xl font-bold text-foreground shrink-0">
                                {{ stats.total_affiliators }}
                            </span>
                        </div>

                        <!-- Garis Pemisah Antar Baris -->
                        <div class="border-t border-border/60"></div>

                        <!-- Baris 2: Total Downline Terhubung -->
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-sky-500/10 text-sky-600 dark:text-sky-400">
                                    <TrendingUp class="h-4 w-4" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-foreground truncate">Total Downline Terhubung</p>
                                    <p class="text-[11px] text-muted-foreground truncate">Akumulasi seluruh investor dalam jaringan</p>
                                </div>
                            </div>
                            <span class="text-lg sm:text-xl font-bold text-foreground shrink-0">
                                {{ stats.total_downlines }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabel Rekapitulasi Mitra Affiliator -->
            <AppDataTable
                :data="affiliates"
                :columns="columns"
                :filters="filters"
                :fetch-url="route('admin.affiliates.index')"
                search-placeholder="Cari nama, username, email, nomor HP, atau kode referral..."
                empty-title="Belum Ada Mitra Affiliator"
                empty-message="Belum ada mitra affiliator yang terdaftar dalam sistem."
            >
                <!-- Custom Slot: Name -->
                <template #column-name="{ item }">
                    <div class="space-y-0.5 min-w-0">
                        <p class="font-bold text-foreground text-sm truncate">{{ item.name }}</p>
                        <p class="text-xs text-muted-foreground flex items-center gap-1.5 flex-wrap">
                            <span>@{{ item.username }}</span>
                            <span class="text-muted-foreground/60">&bull;</span>
                            <span>{{ item.email }}</span>
                        </p>
                    </div>
                </template>

                <!-- Custom Slot: Phone -->
                <template #column-phone="{ item }">
                    <span
                        v-if="item.phone && item.phone !== '-'"
                        class="text-foreground font-mono text-xs whitespace-nowrap"
                    >
                        {{ item.phone }}
                    </span>
                    <span v-else class="text-muted-foreground text-xs">-</span>
                </template>

                <!-- Custom Slot: Referral Code -->
                <template #column-referral_code="{ item }">
                    <span class="font-mono text-xs font-bold tracking-wider text-primary-800 dark:text-primary-300 rounded bg-primary-500/10 px-2 py-0.5 border border-primary-500/30">
                        {{ item.referral_code }}
                    </span>
                </template>

                <!-- Custom Slot: Total Downlines -->
                <template #column-total_downlines="{ item }">
                    <span class="font-bold text-foreground text-sm">
                        {{ item.total_downlines }}
                    </span>
                </template>

                <!-- Custom Slot: Active Downlines -->
                <template #column-active_downlines="{ item }">
                    <span class="font-bold text-emerald-800 dark:text-emerald-400 text-sm">
                        {{ item.active_downlines }}
                    </span>
                </template>

                <!-- Custom Slot: Status -->
                <template #column-status="{ item }">
                    <span
                        class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-semibold border"
                        :class="[
                            item.status === 'active'
                                ? 'bg-emerald-500/10 text-emerald-800 dark:text-emerald-300 border-emerald-500/30'
                                : 'bg-amber-500/10 text-amber-800 dark:text-amber-300 border-amber-500/30',
                        ]"
                    >
                        {{ item.status === 'active' ? 'Aktif' : 'Pending' }}
                    </span>
                </template>

                <!-- Custom Slot: Joined At -->
                <template #column-joined_at="{ item }">
                    <span class="text-xs text-muted-foreground whitespace-nowrap">
                        {{ item.joined_at_label || item.joined_at }}
                    </span>
                </template>
            </AppDataTable>
        </div>
    </AdminLayout>
</template>
