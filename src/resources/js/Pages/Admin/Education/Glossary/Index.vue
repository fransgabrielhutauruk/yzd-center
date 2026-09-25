<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import {
    BookOpen,
    Plus,
    Edit2,
    Trash2,
    Search,
    Filter,
    Loader2,
} from 'lucide-vue-next';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AppDataTable from '@/Components/Common/AppDataTable.vue';
import AppModal from '@/Components/Common/AppModal.vue';
import AppConfirmDialog from '@/Components/Common/AppConfirmDialog.vue';
import { Button } from '@/Components/ui/button';

const props = defineProps({
    glossary: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const isFormModalOpen = ref(false);
const isEditing = ref(false);
const editingId = ref(null);

const isDeleteModalOpen = ref(false);
const deletingItem = ref(null);
const isDeleting = ref(false);

const form = useForm({
    istilah: '',
    definisi: '',
});

const columns = [
    { key: 'istilah', label: 'Nama Istilah', sortable: false },
    { key: 'definisi', label: 'Definisi & Penjelasan', sortable: false },
    { key: 'actions', label: 'Aksi', sortable: false, align: 'right' },
];

const openCreateModal = () => {
    isEditing.value = false;
    editingId.value = null;
    form.reset();
    form.clearErrors();
    isFormModalOpen.value = true;
};

const openEditModal = (item) => {
    isEditing.value = true;
    editingId.value = item.id;
    form.clearErrors();
    form.istilah = item.istilah;
    form.definisi = item.definisi;
    isFormModalOpen.value = true;
};

const submitForm = () => {
    if (isEditing.value) {
        form.put(`/admin/education/glossary/${editingId.value}`, {
            preserveScroll: true,
            onSuccess: () => {
                isFormModalOpen.value = false;
            },
        });
    } else {
        form.post('/admin/education/glossary', {
            preserveScroll: true,
            onSuccess: () => {
                isFormModalOpen.value = false;
            },
        });
    }
};

const confirmDelete = (item) => {
    deletingItem.value = item;
    isDeleteModalOpen.value = true;
};

const handleDelete = () => {
    if (!deletingItem.value) return;
    isDeleting.value = true;
    router.delete(`/admin/education/glossary/${deletingItem.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            isDeleting.value = false;
            isDeleteModalOpen.value = false;
            deletingItem.value = null;
        },
    });
};
</script>

<template>
    <AdminLayout title="Glosarium Forex">
        <div class="space-y-6">
            <!-- Header Halaman -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                        <BookOpen class="h-6 w-6 text-primary" />
                        Glosarium Istilah Forex
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Kelola kamus istilah teknis, dasar, dan manajemen risiko forex yang ditampilkan kepada investor.
                    </p>
                </div>
                <div>
                    <Button @click="openCreateModal" class="gap-2">
                        <Plus class="h-4 w-4" />
                        Tambah Istilah Baru
                    </Button>
                </div>
            </div>

            <!-- Tabel Glosarium (AppDataTable) -->
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <AppDataTable
                    :data="glossary"
                    :columns="columns"
                    :filters="filters"
                    fetch-url="/admin/education/glossary"
                    search-placeholder="Cari nama istilah forex..."
                    empty-message="Belum ada istilah glosarium yang terdaftar."
                >
                    <!-- Slot Kolom Istilah -->
                    <template #column-istilah="{ item }">
                        <span class="font-semibold text-slate-900 dark:text-white">
                            {{ item.istilah }}
                        </span>
                    </template>

                    <!-- Slot Kolom Definisi -->
                    <template #column-definisi="{ item }">
                        <p class="max-w-lg text-xs text-slate-600 dark:text-slate-300 line-clamp-2">
                            {{ item.definisi }}
                        </p>
                    </template>

                    <!-- Slot Kolom Aksi -->
                    <template #column-actions="{ item }">
                        <div class="flex items-center justify-end gap-1.5">
                            <Button
                                variant="outline"
                                size="sm"
                                class="h-8 px-2 text-slate-600 hover:text-slate-900 dark:text-slate-300"
                                @click="openEditModal(item)"
                            >
                                <Edit2 class="h-3.5 w-3.5 mr-1" />
                                Edit
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                class="h-8 px-2 text-red-600 hover:bg-red-50 hover:text-red-700 dark:text-red-400 dark:hover:bg-red-950/40"
                                @click="confirmDelete(item)"
                            >
                                <Trash2 class="h-3.5 w-3.5" />
                            </Button>
                        </div>
                    </template>
                </AppDataTable>
            </div>
        </div>

        <!-- Modal Form Tambah / Edit Istilah (AppModal) -->
        <AppModal
            :show="isFormModalOpen"
            :title="isEditing ? 'Edit Istilah Glosarium' : 'Tambah Istilah Glosarium Baru'"
            max-width="lg"
            @close="isFormModalOpen = false"
        >
            <form @submit.prevent="submitForm" class="space-y-4">
                <!-- Nama Istilah -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Nama Istilah <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.istilah"
                        type="text"
                        required
                        placeholder="Contoh: Drawdown"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                    />
                    <p v-if="form.errors.istilah" class="mt-1 text-xs text-red-600">
                        {{ form.errors.istilah }}
                    </p>
                </div>

                <!-- Definisi & Penjelasan -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Definisi & Penjelasan <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        v-model="form.definisi"
                        rows="4"
                        required
                        placeholder="Uraikan pengertian istilah secara jelas dan ramah pemula..."
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                    ></textarea>
                    <p v-if="form.errors.definisi" class="mt-1 text-xs text-red-600">
                        {{ form.errors.definisi }}
                    </p>
                </div>

                <!-- Aksi Form Modal -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                    <Button
                        type="button"
                        variant="outline"
                        @click="isFormModalOpen = false"
                        :disabled="form.processing"
                    >
                        Batal
                    </Button>
                    <Button
                        type="submit"
                        :disabled="form.processing"
                        class="gap-1.5"
                    >
                        <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                        {{ isEditing ? 'Simpan Perubahan' : 'Tambah Istilah' }}
                    </Button>
                </div>
            </form>
        </AppModal>

        <!-- Dialog Konfirmasi Hapus (AppConfirmDialog) -->
        <AppConfirmDialog
            :show="isDeleteModalOpen"
            title="Hapus Istilah Glosarium"
            :message="`Apakah Anda yakin ingin menghapus istilah '${deletingItem?.istilah}'? Tindakan ini tidak dapat dibatalkan.`"
            confirm-text="Ya, Hapus"
            cancel-text="Batal"
            type="danger"
            :loading="isDeleting"
            @confirm="handleDelete"
            @cancel="isDeleteModalOpen = false"
        />
    </AdminLayout>
</template>
