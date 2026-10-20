<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AppDataTable from '@/Components/Common/AppDataTable.vue';
import AppConfirmDialog from '@/Components/Common/AppConfirmDialog.vue';
import AppTableAction from '@/Components/Common/AppTableAction.vue';
import { Users, TrendingUp, UserCheck, UserX, CheckCircle2, ArrowLeftRight } from 'lucide-vue-next';

const props = defineProps({
    users: {
        type: Object,
        required: true,
    },
    stats: {
        type: Object,
        default: () => ({
            total_users: 0,
            total_investors: 0,
            total_affiliators: 0,
            total_active: 0,
            total_inactive: 0,
            inactive_investors: 0,
            inactive_affiliators: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

// Kolom Tabel AppDataTable
const columns = [
    { key: 'created_at_date', label: 'Terdaftar', sortable: true },
    { key: 'name', label: 'Pengguna', sortable: true },
    { key: 'role', label: 'Peran', sortable: true },
    { key: 'copytrade_count', label: 'Investasi / Copytrade', sortable: true },
    { key: 'is_active', label: 'Status Akses', sortable: true },
    { key: 'actions', label: 'Aksi', align: 'center' },
];

// State Konfirmasi Status Pengguna
const isConfirmDialogOpen = ref(false);
const selectedUser = ref(null);
const isToggling = ref(false);

const openToggleConfirm = (user) => {
    selectedUser.value = user;
    isConfirmDialogOpen.value = true;
};

const handleConfirmToggle = () => {
    if (!selectedUser.value) return;

    isToggling.value = true;
    router.post(
        route('admin.users.toggle_status', selectedUser.value.id),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isToggling.value = false;
                isConfirmDialogOpen.value = false;
                selectedUser.value = null;
            },
        }
    );
};

// State Konfirmasi Alih Peran (Investor <-> Affiliator)
const isSwitchRoleDialogOpen = ref(false);
const userToSwitch = ref(null);
const isSwitchingRole = ref(false);

const openSwitchRoleConfirm = (user) => {
    userToSwitch.value = user;
    isSwitchRoleDialogOpen.value = true;
};

const handleConfirmSwitchRole = () => {
    if (!userToSwitch.value) return;

    isSwitchingRole.value = true;
    router.post(
        route('admin.users.switch_role', userToSwitch.value.id),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isSwitchingRole.value = false;
                isSwitchRoleDialogOpen.value = false;
                userToSwitch.value = null;
            },
        }
    );
};

const filterByQuick = (type, val) => {
    const currentFilters = { ...props.filters };

    if (type === 'role') {
        delete currentFilters.is_active;
        if (val === '') {
            delete currentFilters.role;
        } else {
            currentFilters.role = val;
        }
    } else if (type === 'is_active') {
        delete currentFilters.role;
        if (val === '') {
            delete currentFilters.is_active;
        } else {
            currentFilters.is_active = val;
        }
    }

    currentFilters.page = 1;

    router.get(route('admin.users.index'), currentFilters, {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Manajemen Pengguna" />
    <AdminLayout
        title="Manajemen Pengguna"
        subtitle="Pusat kendali pengguna untuk memantau investor dan affiliator, serta mengelola izin akses sistem."
        :breadcrumbs="[{ label: 'Menu Utama' }, { label: 'Manajemen Pengguna' }]"
    >
        <div class="space-y-6">
            <!-- 4 Quick Stat Filter Cards -->
            <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                <button
                    type="button"
                    @click="filterByQuick('role', '')"
                    :class="[
                        'cursor-pointer rounded-xl border p-4 text-left transition-all',
                        !filters.role && !filters.is_active
                            ? 'border-primary-500 bg-primary-500/10 ring-primary-500/30 ring-1'
                            : 'border-border bg-card hover:bg-muted/40',
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-primary-700 dark:text-primary-300 text-xs font-semibold"
                            >Semua Pengguna</span
                        >
                        <Users class="text-primary-600 dark:text-primary-400 h-4 w-4" />
                    </div>
                    <p class="text-foreground mt-2 font-mono text-2xl font-bold">
                        {{ stats.total_users }}
                    </p>
                </button>

                <button
                    type="button"
                    @click="filterByQuick('role', 'investor')"
                    :class="[
                        'cursor-pointer rounded-xl border p-4 text-left transition-all',
                        filters.role === 'investor' && !filters.is_active
                            ? 'border-sky-500 bg-sky-500/10 ring-1 ring-sky-500/30'
                            : 'border-border bg-card hover:bg-muted/40',
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-sky-700 dark:text-sky-300"
                            >Total Investor Aktif</span
                        >
                        <TrendingUp class="h-4 w-4 text-sky-600 dark:text-sky-400" />
                    </div>
                    <p class="text-foreground mt-2 font-mono text-2xl font-bold">
                        {{ stats.total_investors }}
                    </p>
                </button>

                <button
                    type="button"
                    @click="filterByQuick('role', 'affiliator')"
                    :class="[
                        'cursor-pointer rounded-xl border p-4 text-left transition-all',
                        filters.role === 'affiliator' && !filters.is_active
                            ? 'border-indigo-500 bg-indigo-500/10 ring-1 ring-indigo-500/30'
                            : 'border-border bg-card hover:bg-muted/40',
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-indigo-700 dark:text-indigo-300"
                            >Total Affiliator Aktif</span
                        >
                        <UserCheck class="h-4 w-4 text-indigo-600 dark:text-indigo-400" />
                    </div>
                    <p class="text-foreground mt-2 font-mono text-2xl font-bold">
                        {{ stats.total_affiliators }}
                    </p>
                </button>

                <button
                    type="button"
                    @click="filterByQuick('is_active', 'inactive')"
                    :class="[
                        'cursor-pointer rounded-xl border p-4 text-left transition-all',
                        filters.is_active === 'inactive' || filters.is_active === '0'
                            ? 'border-rose-500 bg-rose-500/10 ring-1 ring-rose-500/30'
                            : 'border-border bg-card hover:bg-muted/40',
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-rose-700 dark:text-rose-300"
                            >Dinonaktifkan</span
                        >
                        <UserX class="h-4 w-4 text-rose-600 dark:text-rose-400" />
                    </div>
                    <div class="mt-2 flex flex-wrap items-baseline gap-1.5">
                        <span class="text-foreground font-mono text-2xl font-bold">
                            {{ stats.total_inactive }}
                        </span>
                        <span class="text-muted-foreground text-xs font-normal">
                            ({{ stats.inactive_affiliators || 0 }} affiliator |
                            {{ stats.inactive_investors || 0 }} investor)
                        </span>
                    </div>
                </button>
            </div>

            <!-- Tabel Daftar Pengguna via AppDataTable -->
            <AppDataTable
                :data="users"
                :columns="columns"
                :filters="filters"
                fetch-url="/admin/users"
                search-placeholder="Cari nama, username, email, telepon, NIK..."
                empty-title="Tidak Ada Pengguna Ditemukan"
                empty-message="Belum ada data pengguna atau tidak ada hasil yang sesuai dengan kriteria filter pencarian."
            >
                <!-- Filter Slot: Dropdown Peran & Akses -->
                <template #filters="{ applyFilters }">
                    <!-- Filter Peran (Role) -->
                    <select
                        :value="filters.role || ''"
                        @change="
                            (e) => applyFilters({ role: e.target.value || undefined, page: 1 })
                        "
                        class="bg-background border-input focus:ring-primary-500 min-h-[38px] w-full rounded-lg border px-3 py-1.5 text-xs font-medium focus:ring-2 focus:outline-none sm:w-auto"
                    >
                        <option value="">Semua Peran</option>
                        <option value="investor">Investor</option>
                        <option value="affiliator">Affiliator</option>
                    </select>

                    <!-- Filter Status Akses Akun -->
                    <select
                        :value="filters.is_active || ''"
                        @change="
                            (e) => applyFilters({ is_active: e.target.value || undefined, page: 1 })
                        "
                        class="bg-background border-input focus:ring-primary-500 min-h-[38px] w-full rounded-lg border px-3 py-1.5 text-xs font-medium focus:ring-2 focus:outline-none sm:w-auto"
                    >
                        <option value="">Semua Akses</option>
                        <option value="active">Aktif</option>
                        <option value="inactive">Dinonaktifkan</option>
                    </select>
                </template>

                <!-- Kolom 1: Terdaftar Pada -->
                <template #column-created_at_date="{ item }">
                    <span class="text-foreground text-xs font-medium">
                        {{ item.created_at_date }}
                    </span>
                </template>

                <!-- Kolom 2: Pengguna (Avatar, Nama yang dapat diklik, Username) -->
                <template #column-name="{ item }">
                    <div class="flex items-center gap-3">
                        <div
                            class="bg-primary-100 text-primary-700 dark:bg-primary-900/50 dark:text-primary-300 flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-bold shadow-2xs"
                        >
                            {{ item.name ? item.name.charAt(0).toUpperCase() : 'U' }}
                        </div>
                        <div class="min-w-0">
                            <Link
                                :href="route('admin.users.show', item.id)"
                                class="text-foreground hover:text-primary-600 block truncate text-sm font-semibold transition-colors"
                                title="Lihat detail pengguna"
                            >
                                {{ item.name }}
                            </Link>
                            <span class="text-muted-foreground block truncate font-mono text-xs">
                                @{{ item.username || '-' }}
                            </span>
                        </div>
                    </div>
                </template>

                <!-- Kolom 3: Peran -->
                <template #column-role="{ item }">
                    <span
                        v-if="item.role === 'affiliator'"
                        class="inline-flex items-center rounded-md border border-indigo-200 bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-700 dark:border-indigo-800 dark:bg-indigo-950/50 dark:text-indigo-300"
                    >
                        Affiliator
                    </span>
                    <span
                        v-else
                        class="inline-flex items-center rounded-md border border-sky-200 bg-sky-50 px-2 py-0.5 text-xs font-semibold text-sky-700 dark:border-sky-800 dark:bg-sky-950/50 dark:text-sky-300"
                    >
                        Investor
                    </span>
                </template>

                <!-- Kolom 4: Investasi / Copytrade (Hanya Akun Aktif) -->
                <template #column-copytrade_count="{ item }">
                    <span
                        class="text-foreground inline-flex items-center rounded-md border border-slate-200 bg-slate-100 px-2 py-0.5 text-xs font-semibold whitespace-nowrap dark:border-slate-700 dark:bg-slate-800"
                    >
                        {{ item.active_copytrade_count }} Investasi Aktif
                    </span>
                </template>

                <!-- Kolom 5: Status Akses Akun (is_active) -->
                <template #column-is_active="{ item }">
                    <span
                        v-if="item.is_active"
                        class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300"
                    >
                        <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500"></span>
                        Aktif
                    </span>
                    <span
                        v-else
                        class="inline-flex items-center gap-1.5 rounded-full border border-rose-200 bg-rose-50 px-2.5 py-0.5 text-xs font-semibold text-rose-700 dark:border-rose-800 dark:bg-rose-950/50 dark:text-rose-300"
                    >
                        <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                        Dinonaktifkan
                    </span>
                </template>

                <!-- Kolom 6: Aksi -->
                <template #column-actions="{ item }">
                    <div class="flex items-center justify-center gap-1.5">
                        <AppTableAction
                            type="detail"
                            title="Lihat Detail Akun"
                            :href="route('admin.users.show', item.id)"
                        />
                        <AppTableAction
                            type="custom"
                            :icon="item.role === 'investor' ? UserCheck : ArrowLeftRight"
                            :color="item.role === 'investor' ? 'primary' : 'amber'"
                            :title="item.role === 'investor' ? 'Angkat Menjadi Affiliator' : 'Ubah Menjadi Investor'"
                            @click="openSwitchRoleConfirm(item)"
                        />
                        <AppTableAction
                            :type="item.is_active ? 'disable' : 'activate'"
                            :title="item.is_active ? 'Nonaktifkan Akses Akun' : 'Aktifkan Kembali Akses Akun'"
                            @click="openToggleConfirm(item)"
                        />
                    </div>
                </template>
            </AppDataTable>
        </div>

        <!-- Dialog Konfirmasi Status Akses Akun -->
        <AppConfirmDialog
            :show="isConfirmDialogOpen"
            :title="
                selectedUser?.is_active
                    ? 'Nonaktifkan Akses Pengguna?'
                    : 'Aktifkan Kembali Akses Pengguna?'
            "
            :message="
                selectedUser?.is_active
                    ? `Apakah Anda yakin ingin menonaktifkan akun '${selectedUser?.name}' (@${selectedUser?.username})? Pengguna ini tidak akan dapat login atau mengakses sistem kembali, dan seluruh sesi aktifnya akan segera diputus.`
                    : `Apakah Anda yakin ingin mengaktifkan kembali akun '${selectedUser?.name}' (@${selectedUser?.username})? Pengguna ini akan dapat login dan mengakses sistem seperti semula.`
            "
            :confirm-text="selectedUser?.is_active ? 'Ya, Nonaktifkan Akun' : 'Ya, Aktifkan Akun'"
            cancel-text="Batal"
            :type="selectedUser?.is_active ? 'danger' : 'warning'"
            :loading="isToggling"
            @confirm="handleConfirmToggle"
            @cancel="isConfirmDialogOpen = false"
        />

        <!-- Dialog Konfirmasi Alih Peran Pengguna (Investor <-> Affiliator) -->
        <AppConfirmDialog
            :show="isSwitchRoleDialogOpen"
            :title="
                userToSwitch?.role === 'investor'
                    ? 'Angkat Menjadi Mitra Affiliator?'
                    : 'Ubah Menjadi Investor Biasa?'
            "
            :message="
                userToSwitch?.role === 'investor'
                    ? `Apakah Anda yakin ingin mengangkat akun '${userToSwitch?.name}' (@${userToSwitch?.username}) menjadi Mitra Affiliator? Pengguna akan mendapatkan akses ke menu Program Afiliasi dan diterbitkan kode referral unik.`
                    : `Apakah Anda yakin ingin mengubah akun '${userToSwitch?.name}' (@${userToSwitch?.username}) kembali menjadi Investor biasa? Akses menu Program Afiliasi akan dinonaktifkan.`
            "
            :confirm-text="
                userToSwitch?.role === 'investor'
                    ? 'Ya, Angkat Jadi Affiliator'
                    : 'Ya, Ubah Jadi Investor'
            "
            cancel-text="Batal"
            type="warning"
            :loading="isSwitchingRole"
            @confirm="handleConfirmSwitchRole"
            @cancel="isSwitchRoleDialogOpen = false"
        />
    </AdminLayout>
</template>
