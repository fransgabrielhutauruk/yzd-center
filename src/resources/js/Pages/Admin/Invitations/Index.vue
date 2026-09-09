<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AppDataTable from '@/Components/Common/AppDataTable.vue';
import AppModal from '@/Components/Common/AppModal.vue';
import AppConfirmDialog from '@/Components/Common/AppConfirmDialog.vue';
import { Button } from '@/Components/ui/button';
import { toast } from 'vue-sonner';
import {
    Link2,
    Plus,
    Copy,
    Trash2,
    Clock,
    Users,
    UserCheck,
    Check,
    Loader2,
} from 'lucide-vue-next';

const props = defineProps({
    invitations: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

// Definisi Kolom Tabel
const columns = [
    { key: 'label', label: 'Label Kegiatan', sortable: true },
    { key: 'target_role', label: 'Hak Akses', sortable: true },
    { key: 'total_terpakai', label: 'Penggunaan Kuota', sortable: true },
    { key: 'expired_at', label: 'Masa Berlaku', sortable: true },
    { key: 'status_key', label: 'Status' },
    { key: 'token', label: 'Tautan Registrasi' },
    { key: 'actions', label: 'Aksi', align: 'right' },
];

// State Modal Pembuatan
const isCreateModalOpen = ref(false);

const form = useForm({
    label: '',
    target_role: 'investor',
    kuota_maksimal: 1,
    expired_hours: 24,
});

const openCreateModal = () => {
    form.reset();
    form.clearErrors();
    form.target_role = 'investor';
    form.kuota_maksimal = 1;
    form.expired_hours = 24;
    isCreateModalOpen.value = true;
};

const closeCreateModal = () => {
    isCreateModalOpen.value = false;
};

const submitCreate = () => {
    form.post('/admin/invitations', {
        preserveScroll: true,
        onSuccess: () => {
            closeCreateModal();
        },
    });
};

// Salin Tautan dengan Feedback Toast
const copiedToken = ref(null);

const copyLink = async (item) => {
    try {
        await navigator.clipboard.writeText(item.full_url);
        copiedToken.value = item.token;
        toast.success('Tautan undangan berhasil disalin ke clipboard!', {
            description: item.label,
        });
        setTimeout(() => {
            if (copiedToken.value === item.token) {
                copiedToken.value = null;
            }
        }, 2000);
    } catch (err) {
        toast.error('Gagal menyalin tautan ke clipboard.');
    }
};

// State Konfirmasi Hapus Modal
const isDeleteDialogOpen = ref(false);
const itemToDelete = ref(null);
const isDeleting = ref(false);

const openDeleteConfirm = (item) => {
    itemToDelete.value = item;
    isDeleteDialogOpen.value = true;
};

const cancelDelete = () => {
    isDeleteDialogOpen.value = false;
    itemToDelete.value = null;
};

const executeDelete = () => {
    if (!itemToDelete.value) return;

    isDeleting.value = true;
    router.delete(`/admin/invitations/${itemToDelete.value.invitation_id}`, {
        preserveScroll: true,
        onSuccess: () => {
            isDeleteDialogOpen.value = false;
            itemToDelete.value = null;
        },
        onFinish: () => {
            isDeleting.value = false;
        },
    });
};

const getStatusBadgeClass = (statusKey) => {
    switch (statusKey) {
        case 'active':
            return 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20';
        case 'quota_full':
            return 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20';
        case 'expired':
            return 'bg-destructive/10 text-destructive border-destructive/20';
        default:
            return 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/20';
    }
};
</script>

<template>
    <AdminLayout title="Manajemen Tautan Undangan">
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <h2 class="text-foreground text-xl font-bold tracking-tight">
                        Tautan Undangan Pendaftaran
                    </h2>
                    <p class="text-muted-foreground mt-0.5 text-xs sm:text-sm">
                        Kelola token pendaftaran tertutup (*Closed Registration*) untuk Calon
                        Investor dan Affiliator.
                    </p>
                </div>

                <Button
                    type="button"
                    @click="openCreateModal"
                    class="bg-primary-600 hover:bg-primary-700 flex items-center gap-2 self-start rounded-lg px-4 py-2 font-medium text-white shadow-sm transition-colors sm:self-auto"
                >
                    <Plus class="h-4 w-4" />
                    <span>Buat Undangan Baru</span>
                </Button>
            </div>

            <!-- Server-Side Reusable Data Table -->
            <AppDataTable
                :data="invitations"
                :columns="columns"
                :filters="filters"
                fetch-url="/admin/invitations"
                search-placeholder="Cari label atau token..."
                empty-title="Belum Ada Tautan Undangan"
                empty-message="Tidak ditemukan data undangan yang sesuai dengan kriteria filter Anda."
            >
                <!-- Custom Filter Slot: Role & Status Filter Dropdowns -->
                <template #filters="{ applyFilters }">
                    <div class="hidden items-center gap-2 md:flex">
                        <!-- Filter Role -->
                        <select
                            :value="filters.role || ''"
                            @change="
                                applyFilters({ role: $event.target.value || undefined, page: 1 })
                            "
                            class="bg-background border-input focus:ring-primary-500 rounded-lg border px-2.5 py-1.5 text-xs font-medium focus:ring-2 focus:outline-none"
                        >
                            <option value="">Semua Peran</option>
                            <option value="investor">Investor</option>
                            <option value="affiliator">Affiliator</option>
                        </select>

                        <!-- Filter Status -->
                        <select
                            :value="filters.status || ''"
                            @change="
                                applyFilters({ status: $event.target.value || undefined, page: 1 })
                            "
                            class="bg-background border-input focus:ring-primary-500 rounded-lg border px-2.5 py-1.5 text-xs font-medium focus:ring-2 focus:outline-none"
                        >
                            <option value="">Semua Status</option>
                            <option value="active">Aktif</option>
                            <option value="quota_full">Kuota Habis</option>
                            <option value="expired">Kedaluwarsa</option>
                            <option value="inactive">Nonaktif</option>
                        </select>
                    </div>
                </template>

                <!-- Custom Column: Label Kegiatan -->
                <template #column-label="{ item }">
                    <div class="flex items-center gap-2">
                        <Link2 class="text-primary-500 h-4 w-4 flex-shrink-0" />
                        <span
                            class="text-foreground max-w-[220px] truncate font-semibold"
                            :title="item.label"
                        >
                            {{ item.label }}
                        </span>
                    </div>
                    <span class="text-muted-foreground mt-0.5 block pl-6 font-mono text-[10px]">
                        Dibuat: {{ item.created_at_label }}
                    </span>
                </template>

                <!-- Custom Column: Hak Akses / Role -->
                <template #column-target_role="{ item }">
                    <span
                        :class="[
                            'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[11px] font-semibold shadow-2xs',
                            item.target_role === 'affiliator'
                                ? 'border-purple-500/20 bg-purple-500/10 text-purple-600 dark:text-purple-400'
                                : 'border-blue-500/20 bg-blue-500/10 text-blue-600 dark:text-blue-400',
                        ]"
                    >
                        <component
                            :is="item.target_role === 'affiliator' ? UserCheck : Users"
                            class="h-3 w-3"
                        />
                        {{ item.target_role_label }}
                    </span>
                </template>

                <!-- Custom Column: Penggunaan Kuota -->
                <template #column-total_terpakai="{ item }">
                    <div class="space-y-1">
                        <div
                            class="text-foreground flex items-center justify-between font-mono font-medium"
                        >
                            <span>{{ item.usage_label }}</span>
                            <span class="text-muted-foreground text-[10px]">
                                {{ Math.round((item.total_terpakai / item.kuota_maksimal) * 100) }}%
                            </span>
                        </div>
                        <div class="bg-muted h-1.5 w-24 overflow-hidden rounded-full">
                            <div
                                class="bg-primary-600 h-1.5 rounded-full transition-all"
                                :style="{
                                    width: `${Math.min(100, (item.total_terpakai / item.kuota_maksimal) * 100)}%`,
                                }"
                            ></div>
                        </div>
                    </div>
                </template>

                <!-- Custom Column: Masa Berlaku -->
                <template #column-expired_at="{ item }">
                    <div class="text-muted-foreground flex items-center gap-1.5">
                        <Clock class="h-3.5 w-3.5 flex-shrink-0" />
                        <span>{{ item.expired_at_label }}</span>
                    </div>
                </template>

                <!-- Custom Column: Status Badge -->
                <template #column-status_key="{ item }">
                    <span
                        :class="[
                            'inline-flex items-center gap-1 rounded-md border px-2 py-0.5 text-[11px] font-semibold',
                            getStatusBadgeClass(item.status_key),
                        ]"
                    >
                        <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                        {{ item.status_label }}
                    </span>
                </template>

                <!-- Custom Column: Tautan Registrasi & Salin -->
                <template #column-token="{ item }">
                    <div class="flex items-center gap-2">
                        <code
                            class="bg-muted text-muted-foreground rounded px-2 py-1 font-mono text-[11px]"
                        >
                            {{ item.masked_token }}
                        </code>
                        <button
                            type="button"
                            @click="copyLink(item)"
                            class="inline-flex items-center gap-1 rounded-md px-2.5 py-1 text-[11px] font-medium transition-colors"
                            :class="[
                                copiedToken === item.token
                                    ? 'bg-emerald-600 text-white shadow-xs'
                                    : 'bg-primary-50 dark:bg-primary-950/60 text-primary-600 dark:text-primary-400 hover:bg-primary-100 dark:hover:bg-primary-900 border-primary-500/20 border',
                            ]"
                            :title="item.full_url"
                        >
                            <Check v-if="copiedToken === item.token" class="h-3 w-3" />
                            <Copy v-else class="h-3 w-3" />
                            <span>{{ copiedToken === item.token ? 'Tersalin!' : 'Salin' }}</span>
                        </button>
                    </div>
                </template>

                <!-- Custom Column: Aksi Hapus -->
                <template #column-actions="{ item }">
                    <button
                        type="button"
                        @click="openDeleteConfirm(item)"
                        class="text-muted-foreground hover:text-destructive hover:bg-destructive/10 rounded-md p-1.5 transition-colors"
                        title="Hapus Tautan"
                    >
                        <Trash2 class="h-4 w-4" />
                    </button>
                </template>

                <!-- Action Button inside Empty State -->
                <template #empty-action>
                    <div class="pt-2">
                        <Button
                            type="button"
                            @click="openCreateModal"
                            size="sm"
                            class="bg-primary-600 hover:bg-primary-700 rounded-lg px-3 py-1.5 text-xs font-medium text-white"
                        >
                            + Buat Undangan Sekarang
                        </Button>
                    </div>
                </template>
            </AppDataTable>
        </div>

        <!-- Reusable Modal: Buat Undangan Baru -->
        <AppModal
            :show="isCreateModalOpen"
            title="Buat Tautan Undangan Baru"
            max-width="lg"
            @close="closeCreateModal"
        >
            <form @submit.prevent="submitCreate" class="space-y-4">
                <!-- Label / Nama Kegiatan -->
                <div class="space-y-1.5">
                    <label for="label" class="text-foreground block text-xs font-semibold">
                        Label / Nama Kegiatan <span class="text-destructive">*</span>
                    </label>
                    <input
                        id="label"
                        v-model="form.label"
                        type="text"
                        required
                        placeholder="Contoh: Batch 1 Calon Investor Surabaya"
                        class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border px-3 py-2 text-sm transition-all focus:ring-2 focus:outline-none"
                        :class="{ 'border-destructive focus:ring-destructive': form.errors.label }"
                    />
                    <p v-if="form.errors.label" class="text-destructive text-xs font-medium">
                        {{ form.errors.label }}
                    </p>
                </div>

                <!-- Target Peran (Role) -->
                <div class="space-y-1.5">
                    <label class="text-foreground block text-xs font-semibold">
                        Hak Akses / Peran Target <span class="text-destructive">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <label
                            :class="[
                                'flex cursor-pointer items-center gap-2.5 rounded-xl border p-3 transition-all',
                                form.target_role === 'investor'
                                    ? 'border-primary-600 bg-primary-500/10 text-foreground ring-primary-600 font-semibold ring-1'
                                    : 'border-input hover:bg-muted/50 text-muted-foreground',
                            ]"
                        >
                            <input
                                v-model="form.target_role"
                                type="radio"
                                value="investor"
                                class="sr-only"
                            />
                            <Users class="h-4 w-4 flex-shrink-0 text-blue-600 dark:text-blue-400" />
                            <div class="text-xs">
                                <p class="text-foreground">Investor</p>
                                <p class="text-muted-foreground text-[10px] font-normal">
                                    Anggota pemodal
                                </p>
                            </div>
                        </label>

                        <label
                            :class="[
                                'flex cursor-pointer items-center gap-2.5 rounded-xl border p-3 transition-all',
                                form.target_role === 'affiliator'
                                    ? 'border-primary-600 bg-primary-500/10 text-foreground ring-primary-600 font-semibold ring-1'
                                    : 'border-input hover:bg-muted/50 text-muted-foreground',
                            ]"
                        >
                            <input
                                v-model="form.target_role"
                                type="radio"
                                value="affiliator"
                                class="sr-only"
                            />
                            <UserCheck
                                class="h-4 w-4 flex-shrink-0 text-purple-600 dark:text-purple-400"
                            />
                            <div class="text-xs">
                                <p class="text-foreground">Affiliator</p>
                                <p class="text-muted-foreground text-[10px] font-normal">
                                    Mitra referral
                                </p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Grid: Kuota & Masa Berlaku -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <!-- Kuota Maksimal -->
                    <div class="space-y-1.5">
                        <label
                            for="kuota_maksimal"
                            class="text-foreground block text-xs font-semibold"
                        >
                            Kuota Pendaftaran <span class="text-destructive">*</span>
                        </label>
                        <input
                            id="kuota_maksimal"
                            v-model.number="form.kuota_maksimal"
                            type="number"
                            min="1"
                            max="10000"
                            required
                            class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border px-3 py-2 text-sm transition-all focus:ring-2 focus:outline-none"
                            :class="{
                                'border-destructive focus:ring-destructive':
                                    form.errors.kuota_maksimal,
                            }"
                        />
                        <p class="text-muted-foreground text-[10px]">Jumlah maksimal pendaftar.</p>
                    </div>

                    <!-- Durasi Masa Berlaku -->
                    <div class="space-y-1.5">
                        <label
                            for="expired_hours"
                            class="text-foreground block text-xs font-semibold"
                        >
                            Masa Berlaku
                        </label>
                        <select
                            id="expired_hours"
                            v-model.number="form.expired_hours"
                            class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border px-3 py-2 text-sm transition-all focus:ring-2 focus:outline-none"
                        >
                            <option :value="24">24 Jam (1 Hari)</option>
                            <option :value="72">72 Jam (3 Hari)</option>
                            <option :value="168">7 Hari (1 Minggu)</option>
                            <option :value="720">30 Hari (1 Bulan)</option>
                            <option :value="0">Tanpa Batas Waktu</option>
                        </select>
                        <p class="text-muted-foreground text-[10px]">
                            Otomatis kedaluwarsa setelah durasi.
                        </p>
                    </div>
                </div>

                <!-- Modal Actions -->
                <div class="border-border flex items-center justify-end gap-3 border-t pt-4">
                    <Button
                        type="button"
                        variant="outline"
                        @click="closeCreateModal"
                        class="px-4 py-2 text-xs font-medium"
                    >
                        Batal
                    </Button>

                    <Button
                        type="submit"
                        :disabled="form.processing"
                        class="bg-primary-600 hover:bg-primary-700 rounded-lg px-5 py-2 text-xs font-semibold text-white shadow-sm disabled:opacity-50"
                    >
                        <Loader2 v-if="form.processing" class="h-3.5 w-3.5 animate-spin" />
                        {{ form.processing ? 'Menyimpan...' : 'Simpan & Buat Tautan' }}
                    </Button>
                </div>
            </form>
        </AppModal>

        <!-- Reusable Confirm Dialog: Hapus Undangan -->
        <AppConfirmDialog
            :show="isDeleteDialogOpen"
            title="Hapus Tautan Undangan"
            :message="`Apakah Anda yakin ingin menghapus tautan undangan '${itemToDelete?.label}'? Calon pengguna yang belum mendaftar tidak akan dapat menggunakan tautan ini.`"
            confirm-text="Ya, Hapus Tautan"
            cancel-text="Batal"
            type="danger"
            :loading="isDeleting"
            @confirm="executeDelete"
            @cancel="cancelDelete"
        />
    </AdminLayout>
</template>
