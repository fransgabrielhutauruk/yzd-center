<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import {
    BookOpen,
    Trash2,
} from 'lucide-vue-next';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AppDataTable from '@/Components/Common/AppDataTable.vue';
import AppModal from '@/Components/Common/AppModal.vue';
import AppConfirmDialog from '@/Components/Common/AppConfirmDialog.vue';
import AppTableAction from '@/Components/Common/AppTableAction.vue';
import AppButton from '@/Components/Common/AppButton.vue';

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

// -----------------------------------------------------------------------------
// State & Form Modal
// -----------------------------------------------------------------------------
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

// -----------------------------------------------------------------------------
// Batch Delete
// -----------------------------------------------------------------------------
const selectedItems = ref([]);
const isBatchDeleteOpen = ref(false);
const isBatchDeleting = ref(false);

const handleBatchDelete = () => {
    if (selectedItems.value.length === 0) return;
    isBatchDeleting.value = true;
    router.delete('/admin/education/glossary', {
        data: { ids: selectedItems.value },
        preserveScroll: true,
        onSuccess: () => {
            selectedItems.value = [];
            isBatchDeleteOpen.value = false;
        },
        onFinish: () => {
            isBatchDeleting.value = false;
        },
    });
};
</script>

<template>
    <AdminLayout
        title="Glosarium Istilah Forex"
        subtitle="Kelola kamus istilah teknis, dasar, dan manajemen risiko forex yang ditampilkan kepada investor."
        :breadcrumbs="[
            { label: 'Bantuan & Informasi' },
            { label: 'Glosarium Forex' }
        ]"
    >
        <template #header-actions>
            <AppButton
                type="create"
                label="Tambah Istilah Baru"
                @click="openCreateModal"
            />
        </template>

        <Head title="Glosarium Istilah Forex - Admin" />

        <div class="space-y-6">
            <!-- Tabel Glosarium -->
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <AppDataTable
                    :data="glossary"
                    :columns="columns"
                    :filters="filters"
                    fetch-url="/admin/education/glossary"
                    search-placeholder="Cari nama istilah forex..."
                    empty-message="Belum ada istilah glosarium yang terdaftar."
                    :enable-batch="true"
                    v-model:selected="selectedItems"
                >
                    <!-- Batch Actions -->
                    <template #batch-actions="{ selected }">
                        <AppButton
                            type="custom"
                            variant="destructive"
                            size="sm"
                            :icon="Trash2"
                            :label="`Hapus ${selected.length} Istilah`"
                            @click="isBatchDeleteOpen = true"
                        />
                    </template>

                    <!-- Kolom Istilah -->
                    <template #column-istilah="{ item }">
                        <span class="font-semibold text-slate-900 dark:text-white">
                            {{ item.istilah }}
                        </span>
                    </template>

                    <!-- Kolom Definisi -->
                    <template #column-definisi="{ item }">
                        <p class="max-w-lg text-xs text-slate-600 dark:text-slate-300 line-clamp-2">
                            {{ item.definisi }}
                        </p>
                    </template>

                    <!-- Kolom Aksi — icon-only tanpa label -->
                    <template #column-actions="{ item }">
                        <div class="flex items-center justify-end gap-1.5">
                            <AppTableAction
                                type="edit"
                                title="Edit Istilah"
                                @click="openEditModal(item)"
                            />
                            <AppTableAction
                                type="delete"
                                title="Hapus Istilah"
                                @click="confirmDelete(item)"
                            />
                        </div>
                    </template>
                </AppDataTable>
            </div>
        </div>

        <!-- Modal Form Tambah / Edit Istilah -->
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

                <!-- Aksi Form -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                    <AppButton
                        type="cancel"
                        @click="isFormModalOpen = false"
                        :disabled="form.processing"
                    />
                    <AppButton
                        type="save"
                        submit
                        :loading="form.processing"
                        :label="isEditing ? 'Simpan Perubahan' : 'Tambah Istilah'"
                    />
                </div>
            </form>
        </AppModal>

        <!-- Dialog Konfirmasi Hapus -->
        <AppConfirmDialog
            :show="isDeleteModalOpen"
            title="Hapus Istilah Glosarium"
            :message="`Apakah Anda yakin ingin menghapus istilah '${deletingItem?.istilah}' dari daftar?`"
            confirm-text="Ya, Hapus"
            cancel-text="Batal"
            type="danger"
            :loading="isDeleting"
            @confirm="handleDelete"
            @cancel="isDeleteModalOpen = false"
        />

        <!-- Dialog Batch Hapus -->
        <AppConfirmDialog
            :show="isBatchDeleteOpen"
            title="Hapus Istilah Terpilih"
            :message="`Apakah Anda yakin ingin menghapus ${selectedItems.length} istilah glosarium yang dipilih dari daftar?`"
            confirm-text="Ya, Hapus Semua"
            cancel-text="Batal"
            type="danger"
            :loading="isBatchDeleting"
            @confirm="handleBatchDelete"
            @cancel="isBatchDeleteOpen = false"
        />
    </AdminLayout>
</template>
