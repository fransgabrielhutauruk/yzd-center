<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import {
    MessageCircleQuestion,
    Trash2,
    FolderKanban,
    Layers,
} from 'lucide-vue-next';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AppDataTable from '@/Components/Common/AppDataTable.vue';
import AppModal from '@/Components/Common/AppModal.vue';
import AppConfirmDialog from '@/Components/Common/AppConfirmDialog.vue';
import AppTableAction from '@/Components/Common/AppTableAction.vue';
import AppButton from '@/Components/Common/AppButton.vue';
import AppTabs from '@/Components/Common/AppTabs.vue';
import AppCheckbox from '@/Components/Common/AppCheckbox.vue';

const props = defineProps({
    faqs: {
        type: Object,
        required: true,
    },
    topics: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const activeTab = ref(props.filters.tab || 'faqs');

const tabs = computed(() => [
    {
        key: 'faqs',
        label: 'Daftar Pertanyaan FAQ',
        icon: MessageCircleQuestion,
    },
    {
        key: 'topics',
        label: 'Topik FAQ',
        icon: FolderKanban,
        count: props.topics.length,
    },
]);

// -----------------------------------------------------------------------------
// State & Form Modal Topik FAQ
// -----------------------------------------------------------------------------
const isTopicModalOpen = ref(false);
const isEditingTopic = ref(false);
const editingTopicId = ref(null);

const topicForm = useForm({
    nama: '',
    deskripsi: '',
    urutan: 0,
    is_active: true,
});

const openCreateTopicModal = () => {
    isEditingTopic.value = false;
    editingTopicId.value = null;
    topicForm.reset();
    topicForm.clearErrors();
    topicForm.urutan = props.topics.length + 1;
    topicForm.is_active = true;
    isTopicModalOpen.value = true;
};

const openEditTopicModal = (topic) => {
    isEditingTopic.value = true;
    editingTopicId.value = topic.id;
    topicForm.clearErrors();
    topicForm.nama = topic.nama;
    topicForm.deskripsi = topic.deskripsi || '';
    topicForm.urutan = topic.urutan;
    topicForm.is_active = Boolean(topic.is_active);
    isTopicModalOpen.value = true;
};

const submitTopicForm = () => {
    if (isEditingTopic.value) {
        topicForm.put(`/admin/education/faq/topics/${editingTopicId.value}`, {
            preserveScroll: true,
            onSuccess: () => {
                isTopicModalOpen.value = false;
            },
        });
    } else {
        topicForm.post('/admin/education/faq/topics', {
            preserveScroll: true,
            onSuccess: () => {
                isTopicModalOpen.value = false;
            },
        });
    }
};

const isDeleteTopicModalOpen = ref(false);
const deletingTopic = ref(null);
const isDeletingTopic = ref(false);

const confirmDeleteTopic = (topic) => {
    deletingTopic.value = topic;
    isDeleteTopicModalOpen.value = true;
};

const handleDeleteTopic = () => {
    if (!deletingTopic.value) return;
    isDeletingTopic.value = true;
    router.delete(`/admin/education/faq/topics/${deletingTopic.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            isDeletingTopic.value = false;
            isDeleteTopicModalOpen.value = false;
            deletingTopic.value = null;
        },
    });
};

// -----------------------------------------------------------------------------
// State & Form Modal Pertanyaan FAQ
// -----------------------------------------------------------------------------
const isFaqModalOpen = ref(false);
const isEditingFaq = ref(false);
const editingFaqId = ref(null);

const faqForm = useForm({
    topik_id: '',
    pertanyaan: '',
    jawaban: '',
    urutan: 0,
    is_active: true,
});

const openCreateFaqModal = () => {
    isEditingFaq.value = false;
    editingFaqId.value = null;
    faqForm.reset();
    faqForm.clearErrors();
    faqForm.topik_id = props.topics.length > 0 ? props.topics[0].id : '';
    faqForm.urutan = 0;
    faqForm.is_active = true;
    isFaqModalOpen.value = true;
};

const openEditFaqModal = (faq) => {
    isEditingFaq.value = true;
    editingFaqId.value = faq.id;
    faqForm.clearErrors();
    faqForm.topik_id = faq.topik_id;
    faqForm.pertanyaan = faq.pertanyaan;
    faqForm.jawaban = faq.jawaban;
    faqForm.urutan = faq.urutan;
    faqForm.is_active = Boolean(faq.is_active);
    isFaqModalOpen.value = true;
};

const submitFaqForm = () => {
    if (isEditingFaq.value) {
        faqForm.put(`/admin/education/faq/${editingFaqId.value}`, {
            preserveScroll: true,
            onSuccess: () => {
                isFaqModalOpen.value = false;
            },
        });
    } else {
        faqForm.post('/admin/education/faq', {
            preserveScroll: true,
            onSuccess: () => {
                isFaqModalOpen.value = false;
            },
        });
    }
};

const isDeleteFaqModalOpen = ref(false);
const deletingFaq = ref(null);
const isDeletingFaq = ref(false);

const confirmDeleteFaq = (faq) => {
    deletingFaq.value = faq;
    isDeleteFaqModalOpen.value = true;
};

const handleDeleteFaq = () => {
    if (!deletingFaq.value) return;
    isDeletingFaq.value = true;
    router.delete(`/admin/education/faq/${deletingFaq.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            isDeletingFaq.value = false;
            isDeleteFaqModalOpen.value = false;
            deletingFaq.value = null;
        },
    });
};

// -----------------------------------------------------------------------------
// Batch Delete FAQ
// -----------------------------------------------------------------------------
const selectedFaqs = ref([]);
const isBatchDeleteOpen = ref(false);
const isBatchDeleting = ref(false);

const handleBatchDelete = () => {
    if (selectedFaqs.value.length === 0) return;
    isBatchDeleting.value = true;
    router.delete('/admin/education/faq', {
        data: { ids: selectedFaqs.value },
        preserveScroll: true,
        onSuccess: () => {
            selectedFaqs.value = [];
            isBatchDeleteOpen.value = false;
        },
        onFinish: () => {
            isBatchDeleting.value = false;
        },
    });
};

// -----------------------------------------------------------------------------
// Kolom Tabel FAQ (hapus kolom Urutan)
// -----------------------------------------------------------------------------
const faqColumns = [
    { key: 'pertanyaan', label: 'Pertanyaan & Jawaban', sortable: false },
    { key: 'topik', label: 'Kategori Topik', sortable: false },
    { key: 'is_active', label: 'Status', sortable: false, align: 'center' },
    { key: 'actions', label: 'Aksi', sortable: false, align: 'center' },
];
</script>


<template>
    <AdminLayout
        title="Tanya Jawab (FAQ)"
        subtitle="Kelola daftar tanya jawab yang dikelompokkan berdasarkan topik."
        :breadcrumbs="[
            { label: 'Bantuan & Informasi' },
            { label: 'Tanya Jawab (FAQ)' }
        ]"
    >
        <template #header-actions>
            <AppButton
                v-if="activeTab === 'topics'"
                type="create"
                label="Tambah Topik FAQ"
                @click="openCreateTopicModal"
            />
            <AppButton
                v-else
                type="create"
                label="Tambah Pertanyaan FAQ"
                @click="openCreateFaqModal"
            />
        </template>

        <Head title="Manajemen Tanya Jawab (FAQ) - Admin" />

        <div class="space-y-6">

            <!-- Tab Navigasi (Pertanyaan FAQ vs Topik FAQ) -->
            <AppTabs v-model="activeTab" :items="tabs" />

            <!-- TAB 1: PERTANYAAN FAQ -->
            <div v-if="activeTab === 'faqs'" class="space-y-4">
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <AppDataTable
                        :data="faqs"
                        :columns="faqColumns"
                        :filters="filters"
                        fetch-url="/admin/education/faq"
                        search-placeholder="Cari pertanyaan atau teks jawaban FAQ..."
                        empty-message="Belum ada pertanyaan FAQ yang terdaftar."
                        :enable-batch="true"
                        v-model:selected="selectedFaqs"
                    >
                        <!-- Filter Topik Dropdown -->
                        <template #filters="{ applyFilters }">
                            <div class="w-full sm:w-60">
                                <select
                                    :value="filters.topik_id || ''"
                                    @change="(e) => applyFilters({ topik_id: e.target.value || undefined, page: 1 })"
                                    class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                >
                                    <option value="">Semua Topik FAQ</option>
                                    <option v-for="t in topics" :key="t.id" :value="t.id">
                                        {{ t.nama }}
                                    </option>
                                </select>
                            </div>
                        </template>

                        <!-- Batch Actions -->
                        <template #batch-actions="{ selected }">
                            <AppButton
                                type="custom"
                                variant="destructive"
                                size="sm"
                                :icon="Trash2"
                                :label="`Hapus ${selected.length} FAQ`"
                                @click="isBatchDeleteOpen = true"
                            />
                        </template>

                        <!-- Kolom Pertanyaan & Cuplikan Jawaban -->
                        <template #column-pertanyaan="{ item }">
                            <div class="max-w-md py-1">
                                <p class="text-sm font-semibold text-slate-900 dark:text-white">
                                    {{ item.pertanyaan }}
                                </p>
                                <p class="mt-1 line-clamp-2 text-xs text-slate-500 dark:text-slate-400">
                                    {{ item.jawaban }}
                                </p>
                            </div>
                        </template>

                        <!-- Kolom Topik -->
                        <template #column-topik="{ item }">
                            <span class="inline-flex items-center gap-1 rounded-md bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300">
                                {{ item.topik?.nama || '-' }}
                            </span>
                        </template>

                        <!-- Kolom Status -->
                        <template #column-is_active="{ item }">
                            <span
                                v-if="item.is_active"
                                class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300"
                            >
                                Aktif
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-400"
                            >
                                Draf
                            </span>
                        </template>

                        <!-- Kolom Aksi -->
                        <template #column-actions="{ item }">
                            <div class="flex items-center justify-end gap-1.5">
                                <AppTableAction
                                    type="edit"
                                    title="Edit FAQ"
                                    @click="openEditFaqModal(item)"
                                />
                                <AppTableAction
                                    type="delete"
                                    title="Hapus FAQ"
                                    @click="confirmDeleteFaq(item)"
                                />
                            </div>
                        </template>
                    </AppDataTable>
                </div>
            </div>

            <!-- TAB 2: TOPIK FAQ -->
            <div v-else-if="activeTab === 'topics'" class="space-y-4">
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                            <thead class="border-b border-slate-200 bg-slate-50 text-xs font-semibold text-slate-700 dark:border-slate-800 dark:bg-slate-800/60 dark:text-slate-200">
                                <tr>
                                    <th scope="col" class="px-4 py-3">Nama Topik</th>
                                    <th scope="col" class="px-4 py-3">Deskripsi</th>
                                    <th scope="col" class="px-4 py-3 text-center">Urutan</th>
                                    <th scope="col" class="px-4 py-3 text-center">Jumlah FAQ</th>
                                    <th scope="col" class="px-4 py-3 text-center">Status</th>
                                    <th scope="col" class="px-4 py-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                <tr
                                    v-for="topic in topics"
                                    :key="topic.id"
                                    class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition-colors"
                                >
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">

                                            <div class="font-medium text-slate-900 dark:text-white">
                                                {{ topic.nama }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 max-w-sm">
                                        <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2">
                                            {{ topic.deskripsi || '-' }}
                                        </p>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="rounded bg-slate-100 px-2 py-0.5 text-xs font-mono font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                            #{{ topic.urutan }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                            {{ topic.faqs_count ?? 0 }} Pertanyaan
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span
                                            v-if="topic.is_active"
                                            class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300"
                                        >
                                            Aktif
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-400"
                                        >
                                            Nonaktif
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <AppTableAction
                                                type="edit"
                                                title="Edit Topik"
                                                @click="openEditTopicModal(topic)"
                                            />
                                            <AppTableAction
                                                type="delete"
                                                :disabled="topic.faqs_count > 0"
                                                :title="topic.faqs_count > 0 ? 'Pindahkan atau hapus pertanyaan pada topik ini terlebih dahulu' : 'Hapus Topik'"
                                                @click="confirmDeleteTopic(topic)"
                                            />
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="topics.length === 0">
                                    <td colspan="6" class="px-4 py-8 text-center text-slate-500">
                                        Belum ada topik FAQ yang dibuat.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Tambah / Edit Topik FAQ -->
        <AppModal
            :show="isTopicModalOpen"
            :title="isEditingTopic ? 'Edit Topik FAQ' : 'Tambah Topik FAQ Baru'"
            max-width="md"
            @close="isTopicModalOpen = false"
        >
            <form @submit.prevent="submitTopicForm" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Nama Topik <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="topicForm.nama"
                        type="text"
                        required
                        maxlength="100"
                        placeholder="Contoh: Umum & Legalitas"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                    />
                    <p v-if="topicForm.errors.nama" class="mt-1 text-xs text-red-600">
                        {{ topicForm.errors.nama }}
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Deskripsi Topik (Opsional)
                    </label>
                    <textarea
                        v-model="topicForm.deskripsi"
                        rows="3"
                        placeholder="Jelaskan cakupan topik tanya jawab ini..."
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                    ></textarea>
                    <p v-if="topicForm.errors.deskripsi" class="mt-1 text-xs text-red-600">
                        {{ topicForm.errors.deskripsi }}
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Urutan Tampil
                    </label>
                    <input
                        v-model.number="topicForm.urutan"
                        type="number"
                        min="0"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                    />
                </div>

                <div class="pt-2">
                    <AppCheckbox
                        id="topic_active"
                        v-model="topicForm.is_active"
                        label="Topik Aktif (Dapat dilihat oleh investor)"
                    />
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                    <AppButton
                        type="cancel"
                        @click="isTopicModalOpen = false"
                        :disabled="topicForm.processing"
                    />
                    <AppButton
                        type="save"
                        submit
                        :loading="topicForm.processing"
                        :label="isEditingTopic ? 'Simpan Perubahan' : 'Tambah Topik'"
                    />
                </div>
            </form>
        </AppModal>

        <!-- Modal Tambah / Edit Pertanyaan FAQ -->
        <AppModal
            :show="isFaqModalOpen"
            :title="isEditingFaq ? 'Edit Pertanyaan FAQ' : 'Tambah Pertanyaan FAQ Baru'"
            max-width="2xl"
            @close="isFaqModalOpen = false"
        >
            <form @submit.prevent="submitFaqForm" class="space-y-4">
                <!-- Topik -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Topik FAQ <span class="text-red-500">*</span>
                    </label>
                    <select
                        v-model="faqForm.topik_id"
                        required
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                    >
                        <option value="" disabled>Pilih Topik FAQ</option>
                        <option v-for="t in topics" :key="t.id" :value="t.id">
                            {{ t.nama }}
                        </option>
                    </select>
                    <p v-if="faqForm.errors.topik_id" class="mt-1 text-xs text-red-600">
                        {{ faqForm.errors.topik_id }}
                    </p>
                </div>

                <!-- Pertanyaan -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Kalimat Pertanyaan <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="faqForm.pertanyaan"
                        type="text"
                        required
                        maxlength="255"
                        placeholder="Contoh: Apakah dana modal saya aman di broker pribadi?"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                    />
                    <p v-if="faqForm.errors.pertanyaan" class="mt-1 text-xs text-red-600">
                        {{ faqForm.errors.pertanyaan }}
                    </p>
                </div>

                <!-- Jawaban -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Uraian Jawaban Lengkap <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        v-model="faqForm.jawaban"
                        rows="6"
                        required
                        placeholder="Tuliskan uraian jawaban yang jelas, transparan, dan mudah dipahami..."
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-white font-sans text-sm"
                    ></textarea>
                    <p v-if="faqForm.errors.jawaban" class="mt-1 text-xs text-red-600">
                        {{ faqForm.errors.jawaban }}
                    </p>
                </div>

                <div class="pt-2">
                    <AppCheckbox
                        id="faq_active"
                        v-model="faqForm.is_active"
                        label="Pertanyaan Aktif (Dapat dilihat oleh investor)"
                    />
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                    <AppButton
                        type="cancel"
                        @click="isFaqModalOpen = false"
                        :disabled="faqForm.processing"
                    />
                    <AppButton
                        type="save"
                        submit
                        :loading="faqForm.processing"
                        :label="isEditingFaq ? 'Simpan Perubahan' : 'Tambah Pertanyaan'"
                    />
                </div>
            </form>
        </AppModal>

        <!-- Dialog Hapus Topik -->
        <AppConfirmDialog
            :show="isDeleteTopicModalOpen"
            title="Hapus Topik FAQ"
            :message="`Apakah Anda yakin ingin menghapus topik '${deletingTopic?.nama}'? Pastikan tidak ada pertanyaan FAQ yang bernaung pada topik ini.`"
            confirm-text="Ya, Hapus"
            cancel-text="Batal"
            type="danger"
            :loading="isDeletingTopic"
            @confirm="handleDeleteTopic"
            @cancel="isDeleteTopicModalOpen = false"
        />

        <!-- Dialog Hapus FAQ -->
        <AppConfirmDialog
            :show="isDeleteFaqModalOpen"
            title="Hapus Pertanyaan FAQ"
            :message="`Apakah Anda yakin ingin menghapus pertanyaan '${deletingFaq?.pertanyaan}'? Tindakan ini tidak dapat dibatalkan.`"
            confirm-text="Ya, Hapus"
            cancel-text="Batal"
            type="danger"
            :loading="isDeletingFaq"
            @confirm="handleDeleteFaq"
            @cancel="isDeleteFaqModalOpen = false"
        />

        <!-- Dialog Batch Hapus FAQ -->
        <AppConfirmDialog
            :show="isBatchDeleteOpen"
            title="Hapus Pertanyaan FAQ Terpilih"
            :message="`Apakah Anda yakin ingin menghapus ${selectedFaqs.length} pertanyaan FAQ yang dipilih? Tindakan ini tidak dapat dibatalkan.`"
            confirm-text="Ya, Hapus Semua"
            cancel-text="Batal"
            type="danger"
            :loading="isBatchDeleting"
            @confirm="handleBatchDelete"
            @cancel="isBatchDeleteOpen = false"
        />
    </AdminLayout>
</template>
