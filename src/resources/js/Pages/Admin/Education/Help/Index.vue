<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import {
    HelpCircle,
    Trash2,
    FileText,
    FolderKanban,
    Eye,
    CheckCircle2,
    XCircle,
    ExternalLink,
    Search,
    X,
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
    articles: {
        type: Object,
        required: true,
    },
    categories: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const categorySearch = ref('');
const filteredCategories = computed(() => {
    if (!categorySearch.value) return props.categories;
    const q = categorySearch.value.toLowerCase().trim();
    return props.categories.filter((cat) =>
        cat.nama?.toLowerCase().includes(q) ||
        (cat.slug && cat.slug.toLowerCase().includes(q)) ||
        (cat.deskripsi && cat.deskripsi.toLowerCase().includes(q))
    );
});

const activeTab = ref(props.filters.tab || 'articles');

const tabs = computed(() => [
    {
        key: 'articles',
        label: 'Artikel & Video Panduan',
        icon: FileText,
    },
    {
        key: 'categories',
        label: 'Kategori Topik',
        icon: FolderKanban,
        count: props.categories.length,
    },
]);

// -----------------------------------------------------------------------------
// State & Form Modal Kategori
// -----------------------------------------------------------------------------
const isCategoryModalOpen = ref(false);
const isEditingCategory = ref(false);
const editingCategoryId = ref(null);

const categoryForm = useForm({
    nama: '',
    slug: '',
    deskripsi: '',
    urutan: 0,
    is_active: true,
});

// Auto-slug otomatis di belakang layar
const generateSlug = (text) => {
    return text
        .toString()
        .toLowerCase()
        .trim()
        .replace(/&/g, '-dan-')
        .replace(/[\s\W-]+/g, '-')
        .replace(/^-+|-+$/g, '');
};

watch(
    () => categoryForm.nama,
    (val) => {
        if (!isEditingCategory.value && val) {
            categoryForm.slug = generateSlug(val);
        }
    }
);

const openCreateCategoryModal = () => {
    isEditingCategory.value = false;
    editingCategoryId.value = null;
    categoryForm.reset();
    categoryForm.clearErrors();
    categoryForm.is_active = true;
    categoryForm.urutan = props.categories.length + 1;
    isCategoryModalOpen.value = true;
};

const openEditCategoryModal = (cat) => {
    isEditingCategory.value = true;
    editingCategoryId.value = cat.id;
    categoryForm.clearErrors();
    categoryForm.nama = cat.nama;
    categoryForm.slug = cat.slug;
    categoryForm.deskripsi = cat.deskripsi || '';
    categoryForm.urutan = cat.urutan;
    categoryForm.is_active = Boolean(cat.is_active);
    isCategoryModalOpen.value = true;
};

const submitCategoryForm = () => {
    if (isEditingCategory.value) {
        categoryForm.put(`/admin/education/help/categories/${editingCategoryId.value}`, {
            preserveScroll: true,
            onSuccess: () => {
                isCategoryModalOpen.value = false;
            },
        });
    } else {
        categoryForm.post('/admin/education/help/categories', {
            preserveScroll: true,
            onSuccess: () => {
                isCategoryModalOpen.value = false;
            },
        });
    }
};

const isDeleteCategoryModalOpen = ref(false);
const deletingCategory = ref(null);
const isDeletingCategory = ref(false);

const confirmDeleteCategory = (cat) => {
    deletingCategory.value = cat;
    isDeleteCategoryModalOpen.value = true;
};

const handleDeleteCategory = () => {
    if (!deletingCategory.value) return;
    isDeletingCategory.value = true;
    router.delete(`/admin/education/help/categories/${deletingCategory.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            isDeletingCategory.value = false;
            isDeleteCategoryModalOpen.value = false;
            deletingCategory.value = null;
        },
    });
};


const isDeleteArticleModalOpen = ref(false);
const deletingArticle = ref(null);
const isDeletingArticle = ref(false);

const confirmDeleteArticle = (art) => {
    deletingArticle.value = art;
    isDeleteArticleModalOpen.value = true;
};

const handleDeleteArticle = () => {
    if (!deletingArticle.value) return;
    isDeletingArticle.value = true;
    router.delete(`/admin/education/help/articles/${deletingArticle.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            isDeletingArticle.value = false;
            isDeleteArticleModalOpen.value = false;
            deletingArticle.value = null;
        },
    });
};

const articleColumns = [
    { key: 'judul', label: 'Judul Panduan', sortable: false },
    { key: 'kategori', label: 'Kategori Topik', sortable: false },
    { key: 'views_count', label: 'Dilihat', sortable: false, align: 'center' },
    { key: 'is_published', label: 'Status', sortable: false, align: 'center' },
    { key: 'actions', label: 'Aksi', sortable: false, align: 'center' },
];
</script>

<template>
    <AdminLayout
        title="Pusat Bantuan"
        subtitle="Kelola artikel panduan dan kategori topik untuk investor."
        :breadcrumbs="[
            { label: 'Bantuan & Informasi' },
            { label: 'Pusat Bantuan' }
        ]"
    >
        <template #header-actions>
            <AppButton
                v-if="activeTab === 'categories'"
                type="create"
                label="Tambah Kategori Topik"
                @click="openCreateCategoryModal"
            />
            <AppButton
                v-else
                type="create"
                label="Tambah Artikel Panduan"
                href="/admin/education/help/articles/create"
            />
        </template>

        <div class="space-y-6">

            <!-- Tab Navigasi (Artikel vs Kategori Topik) -->
            <AppTabs v-model="activeTab" :items="tabs" />

            <!-- TAB 1: ARTIKEL & VIDEO PANDUAN -->
            <div v-if="activeTab === 'articles'" class="space-y-4">
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <AppDataTable
                        :data="articles"
                        :columns="articleColumns"
                        :filters="filters"
                        fetch-url="/admin/education/help"
                        search-placeholder="Cari judul artikel atau ringkasan panduan..."
                        empty-message="Belum ada artikel panduan yang terdaftar."
                    >
                        <!-- Filter Kategori Dropdown -->
                        <template #filters="{ applyFilters }">
                            <div class="w-full sm:w-56">
                                <select
                                    :value="filters.kategori_id || ''"
                                    @change="(e) => applyFilters({ kategori_id: e.target.value || undefined, page: 1 })"
                                    class="bg-background border border-input focus:border-primary w-full rounded-lg px-2.5 py-1.5 text-xs font-medium focus:outline-none focus:ring-1 focus:ring-primary"
                                >
                                    <option value="">Semua Kategori Topik</option>
                                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                        {{ cat.nama }}
                                    </option>
                                </select>
                            </div>
                        </template>

                        <!-- Slot Judul -->
                        <template #column-judul="{ item }">
                            <div>
                                <p class="font-semibold text-slate-900 dark:text-white">
                                    {{ item.judul }}
                                </p>
                                <p v-if="item.ringkasan" class="text-xs text-slate-500 dark:text-slate-400 line-clamp-1 mt-0.5">
                                    {{ item.ringkasan }}
                                </p>
                            </div>
                        </template>

                        <!-- Slot Kategori -->
                        <template #column-kategori="{ item }">
                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                {{ item.kategori_nama }}
                            </span>
                        </template>

                        <!-- Slot Views -->
                        <template #column-views_count="{ item }">
                            <span class="text-xs font-mono text-slate-600 dark:text-slate-400">
                                {{ item.views_count }}x
                            </span>
                        </template>

                        <!-- Slot Status Publikasi -->
                        <template #column-is_published="{ item }">
                            <span
                                v-if="item.is_published"
                                class="inline-flex items-center gap-1 text-xs font-medium text-emerald-600 dark:text-emerald-400"
                            >
                                <CheckCircle2 class="h-3.5 w-3.5" />
                                Diterbitkan
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-1 text-xs font-medium text-amber-500 dark:text-amber-400"
                            >
                                <XCircle class="h-3.5 w-3.5" />
                                Draf
                            </span>
                        </template>

                        <!-- Slot Aksi -->
                        <template #column-actions="{ item }">
                            <div class="flex items-center justify-end gap-1.5">
                                <AppTableAction
                                    type="edit"
                                    title="Edit Panduan"
                                    :href="`/admin/education/help/articles/${item.id}/edit`"
                                />
                                <AppTableAction
                                    type="delete"
                                    title="Hapus Panduan"
                                    @click="confirmDeleteArticle(item)"
                                />
                            </div>
                        </template>
                    </AppDataTable>
                </div>
            </div>

            <!-- TAB 2: KATEGORI TOPIK -->
            <div v-else-if="activeTab === 'categories'" class="space-y-4">
                <!-- Toolbar Pencarian Kategori & Total Counter -->
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 bg-card p-3 sm:p-4 rounded-xl border border-border shadow-xs">
                    <div class="relative w-full sm:max-w-xs md:max-w-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-muted-foreground">
                            <Search class="w-4 h-4" />
                        </div>
                        <input
                            v-model="categorySearch"
                            type="text"
                            placeholder="Cari nama kategori topik..."
                            class="w-full pl-9 pr-8 py-1.5 text-xs bg-background border border-input rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all placeholder:text-muted-foreground"
                        />
                        <button
                            v-if="categorySearch"
                            type="button"
                            @click="categorySearch = ''"
                            class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-muted-foreground hover:text-foreground cursor-pointer"
                            aria-label="Bersihkan pencarian kategori"
                        >
                            <X class="w-3.5 h-3.5" />
                        </button>
                    </div>

                    <div class="text-xs text-muted-foreground font-medium text-right sm:text-left">
                        Total <strong class="font-semibold text-foreground">{{ filteredCategories.length }}</strong> kategori topik
                    </div>
                </div>

                <!-- Tabel Kategori Topik -->
                <div class="overflow-hidden rounded-xl border border-border bg-card shadow-xs">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="border-b border-border bg-muted/50 text-muted-foreground font-semibold uppercase tracking-wider">
                                <tr>
                                    <th scope="col" class="py-3 px-4">Kategori Topik</th>
                                    <th scope="col" class="py-3 px-4">Deskripsi</th>
                                    <th scope="col" class="py-3 px-4 text-center">Urutan</th>
                                    <th scope="col" class="py-3 px-4 text-center">Jumlah Panduan</th>
                                    <th scope="col" class="py-3 px-4 text-center">Status</th>
                                    <th scope="col" class="py-3 px-4 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                <template v-if="filteredCategories.length > 0">
                                    <tr
                                        v-for="cat in filteredCategories"
                                        :key="cat.id"
                                        class="hover:bg-muted/30 transition-colors"
                                    >
                                        <!-- Nama Kategori -->
                                        <td class="py-3 px-4">
                                            <div class="flex items-center gap-2.5">
                                                <span class="font-semibold text-foreground text-sm">
                                                    {{ cat.nama }}
                                                </span>
                                            </div>
                                        </td>

                                        <!-- Deskripsi -->
                                        <td class="py-3 px-4 max-w-xs md:max-w-sm">
                                            <p class="text-xs text-muted-foreground line-clamp-2">
                                                {{ cat.deskripsi || '-' }}
                                            </p>
                                        </td>

                                        <!-- Urutan -->
                                        <td class="py-3 px-4 text-center">
                                            <span class="rounded bg-muted px-2 py-0.5 text-xs font-mono font-medium text-muted-foreground border border-border">
                                                #{{ cat.urutan }}
                                            </span>
                                        </td>

                                        <!-- Jumlah Panduan -->
                                        <td class="py-3 px-4 text-center">
                                            <span class="inline-flex items-center rounded-full bg-primary-50 dark:bg-primary-950/50 px-2.5 py-0.5 text-xs font-semibold text-primary-700 dark:text-primary-300 border border-primary-200 dark:border-primary-800/60">
                                                {{ cat.articles_count ?? 0 }} Panduan
                                            </span>
                                        </td>

                                        <!-- Status -->
                                        <td class="py-3 px-4 text-center">
                                            <span
                                                v-if="cat.is_active"
                                                class="inline-flex items-center gap-1 rounded-full bg-emerald-50 dark:bg-emerald-950/50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/60"
                                            >
                                                <CheckCircle2 class="h-3 w-3" />
                                                Aktif
                                            </span>
                                            <span
                                                v-else
                                                class="inline-flex items-center gap-1 rounded-full bg-slate-100 dark:bg-slate-800 px-2.5 py-0.5 text-xs font-semibold text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700"
                                            >
                                                <XCircle class="h-3 w-3" />
                                                Nonaktif
                                            </span>
                                        </td>

                                        <!-- Aksi -->
                                        <td class="py-3 px-4 text-right">
                                            <div class="flex items-center justify-end gap-1.5">
                                                <AppTableAction
                                                    type="edit"
                                                    title="Edit Kategori"
                                                    @click="openEditCategoryModal(cat)"
                                                />
                                                <AppTableAction
                                                    type="delete"
                                                    title="Hapus Kategori"
                                                    @click="confirmDeleteCategory(cat)"
                                                />
                                            </div>
                                        </td>
                                    </tr>
                                </template>

                                <!-- Empty State -->
                                <tr v-else>
                                    <td colspan="6" class="py-12 text-center text-muted-foreground">
                                        <div class="max-w-xs mx-auto space-y-3">
                                            <div class="w-12 h-12 rounded-2xl bg-muted/80 flex items-center justify-center mx-auto text-muted-foreground border border-border">
                                                <FolderKanban class="w-6 h-6" />
                                            </div>
                                            <p class="font-bold text-sm text-foreground">
                                                Tidak Ada Kategori Ditemukan
                                            </p>
                                            <p class="text-xs leading-relaxed">
                                                {{ categorySearch ? 'Tidak ada kategori yang cocok dengan kriteria pencarian Anda.' : 'Belum ada kategori topik panduan yang terdaftar.' }}
                                            </p>
                                            <AppButton
                                                v-if="!categorySearch"
                                                type="create"
                                                label="Tambah Kategori Topik"
                                                size="sm"
                                                class="mt-2"
                                                @click="openCreateCategoryModal"
                                            />
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Tambah / Edit Kategori -->
        <AppModal
            :show="isCategoryModalOpen"
            :title="isEditingCategory ? 'Edit Kategori Topik' : 'Tambah Kategori Topik Baru'"
            max-width="md"
            @close="isCategoryModalOpen = false"
        >
            <form @submit.prevent="submitCategoryForm" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Nama Kategori Topik <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="categoryForm.nama"
                        type="text"
                        required
                        placeholder="Contoh: Panduan Deposit & Broker"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                    />
                    <p v-if="categoryForm.errors.nama" class="mt-1 text-xs text-red-600">
                        {{ categoryForm.errors.nama }}
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Deskripsi Singkat Topik
                    </label>
                    <textarea
                        v-model="categoryForm.deskripsi"
                        rows="3"
                        placeholder="Jelaskan cakupan topik bahasan pada kategori ini..."
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                    ></textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Urutan Tampil
                    </label>
                    <input
                        v-model.number="categoryForm.urutan"
                        type="number"
                        min="0"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                    />
                </div>

                <div class="pt-2">
                    <AppCheckbox
                        id="cat_active"
                        v-model="categoryForm.is_active"
                        label="Kategori Aktif"
                    />
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                    <AppButton
                        type="cancel"
                        @click="isCategoryModalOpen = false"
                        :disabled="categoryForm.processing"
                    />
                    <AppButton
                        type="save"
                        submit
                        @click="submitCategoryForm"
                        :loading="categoryForm.processing"
                        :label="isEditingCategory ? 'Simpan Perubahan' : 'Tambah Kategori'"
                    />
                </div>
            </form>
        </AppModal>


        <!-- Dialog Hapus Kategori -->
        <AppConfirmDialog
            :show="isDeleteCategoryModalOpen"
            title="Hapus Kategori Topik"
            :message="`Apakah Anda yakin ingin menghapus kategori '${deletingCategory?.nama}'? Pastikan tidak ada artikel pada kategori ini.`"
            confirm-text="Ya, Hapus"
            cancel-text="Batal"
            type="danger"
            :loading="isDeletingCategory"
            @confirm="handleDeleteCategory"
            @cancel="isDeleteCategoryModalOpen = false"
        />

        <!-- Dialog Hapus Artikel -->
        <AppConfirmDialog
            :show="isDeleteArticleModalOpen"
            title="Hapus Artikel Panduan"
            :message="`Apakah Anda yakin ingin menghapus artikel '${deletingArticle?.judul}' dari daftar?`"
            confirm-text="Ya, Hapus"
            cancel-text="Batal"
            type="danger"
            :loading="isDeletingArticle"
            @confirm="handleDeleteArticle"
            @cancel="isDeleteArticleModalOpen = false"
        />
    </AdminLayout>
</template>
