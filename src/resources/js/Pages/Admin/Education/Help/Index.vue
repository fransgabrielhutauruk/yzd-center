<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import {
    HelpCircle,
    Plus,
    Edit2,
    Trash2,
    Video,
    FileText,
    FolderKanban,
    Eye,
    CheckCircle2,
    XCircle,
    Loader2,
    ExternalLink,
} from 'lucide-vue-next';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AppDataTable from '@/Components/Common/AppDataTable.vue';
import AppModal from '@/Components/Common/AppModal.vue';
import AppConfirmDialog from '@/Components/Common/AppConfirmDialog.vue';
import { Button } from '@/Components/ui/button';

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

const activeTab = ref(props.filters.tab || 'articles');

const switchTab = (tab) => {
    activeTab.value = tab;
    router.get(
        '/admin/education/help',
        { ...props.filters, tab },
        { preserveState: true, preserveScroll: true, replace: true }
    );
};

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
    icon: 'HelpCircle',
    urutan: 0,
    is_active: true,
});

const openCreateCategoryModal = () => {
    isEditingCategory.value = false;
    editingCategoryId.value = null;
    categoryForm.reset();
    categoryForm.clearErrors();
    categoryForm.icon = 'HelpCircle';
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
    categoryForm.icon = cat.icon || 'HelpCircle';
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

// -----------------------------------------------------------------------------
// State & Form Modal Artikel Panduan
// -----------------------------------------------------------------------------
const isArticleModalOpen = ref(false);
const isEditingArticle = ref(false);
const editingArticleId = ref(null);

const articleForm = useForm({
    kategori_id: '',
    judul: '',
    slug: '',
    ringkasan: '',
    konten: '',
    video_url: '',
    urutan: 0,
    is_published: true,
});

const openCreateArticleModal = () => {
    isEditingArticle.value = false;
    editingArticleId.value = null;
    articleForm.reset();
    articleForm.clearErrors();
    if (props.categories.length > 0) {
        articleForm.kategori_id = props.categories[0].id;
    }
    articleForm.is_published = true;
    articleForm.urutan = 0;
    isArticleModalOpen.value = true;
};

const openEditArticleModal = (art) => {
    isEditingArticle.value = true;
    editingArticleId.value = art.id;
    articleForm.clearErrors();
    articleForm.kategori_id = art.kategori_id;
    articleForm.judul = art.judul;
    articleForm.slug = art.slug;
    articleForm.ringkasan = art.ringkasan || '';
    articleForm.konten = art.konten;
    articleForm.video_url = art.video_url || '';
    articleForm.urutan = art.urutan;
    articleForm.is_published = Boolean(art.is_published);
    isArticleModalOpen.value = true;
};

const submitArticleForm = () => {
    if (isEditingArticle.value) {
        articleForm.put(`/admin/education/help/articles/${editingArticleId.value}`, {
            preserveScroll: true,
            onSuccess: () => {
                isArticleModalOpen.value = false;
            },
        });
    } else {
        articleForm.post('/admin/education/help/articles', {
            preserveScroll: true,
            onSuccess: () => {
                isArticleModalOpen.value = false;
            },
        });
    }
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
    { key: 'kategori', label: 'Topik Kategori', sortable: false },
    { key: 'media', label: 'Media', sortable: false, align: 'center' },
    { key: 'views_count', label: 'Dilihat', sortable: false, align: 'center' },
    { key: 'is_published', label: 'Status', sortable: false, align: 'center' },
    { key: 'actions', label: 'Aksi', sortable: false, align: 'right' },
];
</script>

<template>
    <AdminLayout title="Pusat Bantuan & Tutorial">
        <div class="space-y-6">
            <!-- Header Halaman -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                        <HelpCircle class="h-6 w-6 text-primary" />
                        Pusat Bantuan & Tutorial Video
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Kelola topik kategori, artikel panduan bertahap, dan tautan video edukasi untuk investor.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Button
                        v-if="activeTab === 'categories'"
                        @click="openCreateCategoryModal"
                        class="gap-1.5"
                    >
                        <Plus class="h-4 w-4" />
                        Tambah Kategori Topik
                    </Button>
                    <Button
                        v-else
                        @click="openCreateArticleModal"
                        class="gap-1.5"
                    >
                        <Plus class="h-4 w-4" />
                        Tambah Artikel Panduan
                    </Button>
                </div>
            </div>

            <!-- Tab Navigasi (Artikel vs Kategori Topik) -->
            <div class="flex border-b border-slate-200 dark:border-slate-800">
                <button
                    type="button"
                    @click="switchTab('articles')"
                    :class="[
                        'flex items-center gap-2 border-b-2 px-4 py-2.5 text-sm font-medium transition-colors',
                        activeTab === 'articles'
                            ? 'border-primary text-primary dark:text-primary'
                            : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200',
                    ]"
                >
                    <FileText class="h-4 w-4" />
                    Artikel & Video Panduan
                </button>
                <button
                    type="button"
                    @click="switchTab('categories')"
                    :class="[
                        'flex items-center gap-2 border-b-2 px-4 py-2.5 text-sm font-medium transition-colors',
                        activeTab === 'categories'
                            ? 'border-primary text-primary dark:text-primary'
                            : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200',
                    ]"
                >
                    <FolderKanban class="h-4 w-4" />
                    Kategori Topik
                    <span
                        class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300"
                    >
                        {{ categories.length }}
                    </span>
                </button>
            </div>

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
                                    class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
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

                        <!-- Slot Media Video -->
                        <template #column-media="{ item }">
                            <a
                                v-if="item.video_url"
                                :href="item.video_url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-1 rounded bg-red-50 px-2 py-0.5 text-xs font-medium text-red-600 hover:bg-red-100 dark:bg-red-950/40 dark:text-red-400"
                            >
                                <Video class="h-3.5 w-3.5" />
                                Video
                            </a>
                            <span v-else class="text-xs text-slate-400 dark:text-slate-500">
                                Teks Saja
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
                                <Button
                                    variant="outline"
                                    size="sm"
                                    class="h-8 px-2 text-slate-600 hover:text-slate-900 dark:text-slate-300"
                                    @click="openEditArticleModal(item)"
                                >
                                    <Edit2 class="h-3.5 w-3.5 mr-1" />
                                    Edit
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    class="h-8 px-2 text-red-600 hover:bg-red-50 hover:text-red-700 dark:text-red-400 dark:hover:bg-red-950/40"
                                    @click="confirmDeleteArticle(item)"
                                >
                                    <Trash2 class="h-3.5 w-3.5" />
                                </Button>
                            </div>
                        </template>
                    </AppDataTable>
                </div>
            </div>

            <!-- TAB 2: KATEGORI TOPIK -->
            <div v-else-if="activeTab === 'categories'" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="cat in categories"
                        :key="cat.id"
                        class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition-all hover:border-primary/40 dark:border-slate-800 dark:bg-slate-900"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary dark:bg-primary/20">
                                    <FolderKanban class="h-5 w-5" />
                                </div>
                                <div>
                                    <h3 class="font-semibold text-slate-900 dark:text-white">
                                        {{ cat.nama }}
                                    </h3>
                                    <p class="text-xs font-mono text-slate-400">
                                        slug: {{ cat.slug }}
                                    </p>
                                </div>
                            </div>
                            <span
                                v-if="cat.is_active"
                                class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400"
                            >
                                Aktif
                            </span>
                        </div>

                        <p class="mt-3 text-xs text-slate-500 dark:text-slate-400 line-clamp-2">
                            {{ cat.deskripsi || 'Tidak ada deskripsi kategori.' }}
                        </p>

                        <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3 dark:border-slate-800">
                            <span class="text-xs text-slate-500 font-medium">
                                {{ cat.articles_count }} artikel dimuat
                            </span>
                            <div class="flex items-center gap-1.5">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    class="h-7 px-2 text-xs"
                                    @click="openEditCategoryModal(cat)"
                                >
                                    <Edit2 class="h-3 w-3 mr-1" />
                                    Edit
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    class="h-7 px-2 text-xs text-red-600 hover:bg-red-50 hover:text-red-700 dark:text-red-400"
                                    @click="confirmDeleteCategory(cat)"
                                >
                                    <Trash2 class="h-3 w-3" />
                                </Button>
                            </div>
                        </div>
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
                        Slug URL (Opsional)
                    </label>
                    <input
                        v-model="categoryForm.slug"
                        type="text"
                        placeholder="Otomatis dari nama (misal: panduan-deposit)"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-white font-mono text-xs"
                    />
                    <p v-if="categoryForm.errors.slug" class="mt-1 text-xs text-red-600">
                        {{ categoryForm.errors.slug }}
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

                <div class="grid grid-cols-2 gap-4">
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
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Ikon Lucide
                        </label>
                        <select
                            v-model="categoryForm.icon"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                        >
                            <option value="Wallet">Wallet (Keuangan)</option>
                            <option value="UserCheck">UserCheck (KYC)</option>
                            <option value="TrendingUp">TrendingUp (Trading)</option>
                            <option value="Users">Users (Afiliasi)</option>
                            <option value="HelpCircle">HelpCircle (Umum)</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <input
                        id="cat_active"
                        v-model="categoryForm.is_active"
                        type="checkbox"
                        class="h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary"
                    />
                    <label for="cat_active" class="text-sm font-medium text-slate-700 dark:text-slate-300">
                        Kategori Aktif
                    </label>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                    <Button
                        type="button"
                        variant="outline"
                        @click="isCategoryModalOpen = false"
                        :disabled="categoryForm.processing"
                    >
                        Batal
                    </Button>
                    <Button type="submit" :disabled="categoryForm.processing" class="gap-1.5">
                        <Loader2 v-if="categoryForm.processing" class="h-4 w-4 animate-spin" />
                        {{ isEditingCategory ? 'Simpan Perubahan' : 'Tambah Kategori' }}
                    </Button>
                </div>
            </form>
        </AppModal>

        <!-- Modal Tambah / Edit Artikel Panduan -->
        <AppModal
            :show="isArticleModalOpen"
            :title="isEditingArticle ? 'Edit Artikel Panduan' : 'Tambah Artikel Panduan Baru'"
            max-width="2xl"
            @close="isArticleModalOpen = false"
        >
            <form @submit.prevent="submitArticleForm" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Kategori -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Topik Kategori <span class="text-red-500">*</span>
                        </label>
                        <select
                            v-model="articleForm.kategori_id"
                            required
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                        >
                            <option value="" disabled>Pilih Topik Kategori</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                {{ cat.nama }}
                            </option>
                        </select>
                        <p v-if="articleForm.errors.kategori_id" class="mt-1 text-xs text-red-600">
                            {{ articleForm.errors.kategori_id }}
                        </p>
                    </div>

                    <!-- Urutan -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Urutan Tampil
                        </label>
                        <input
                            v-model.number="articleForm.urutan"
                            type="number"
                            min="0"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                        />
                    </div>
                </div>

                <!-- Judul -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Judul Artikel Panduan <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="articleForm.judul"
                        type="text"
                        required
                        placeholder="Contoh: Cara Membuka Akun MT4 & Melakukan Deposit di Broker HFM"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                    />
                    <p v-if="articleForm.errors.judul" class="mt-1 text-xs text-red-600">
                        {{ articleForm.errors.judul }}
                    </p>
                </div>

                <!-- Video URL YouTube -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Link URL Video YouTube (Opsional)
                    </label>
                    <input
                        v-model="articleForm.video_url"
                        type="url"
                        placeholder="https://www.youtube.com/watch?v=..."
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                    />
                    <p class="text-xs text-slate-400 mt-1">
                        Jika diisi, video tutorial akan otomatis diputar di halaman artikel dalam format 16:9.
                    </p>
                    <p v-if="articleForm.errors.video_url" class="mt-1 text-xs text-red-600">
                        {{ articleForm.errors.video_url }}
                    </p>
                </div>

                <!-- Ringkasan Singkat -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Ringkasan Singkat (Muncul di kartu daftar)
                    </label>
                    <textarea
                        v-model="articleForm.ringkasan"
                        rows="2"
                        placeholder="Ringkasan 1-2 kalimat mengenai topik panduan ini..."
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                    ></textarea>
                </div>

                <!-- Konten Panduan Lengkap -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Konten Langkah Panduan Lengkap <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        v-model="articleForm.konten"
                        rows="6"
                        required
                        placeholder="Tuliskan petunjuk bertahap (mendukung format paragraf atau poin 1, 2, 3)..."
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                    ></textarea>
                    <p v-if="articleForm.errors.konten" class="mt-1 text-xs text-red-600">
                        {{ articleForm.errors.konten }}
                    </p>
                </div>

                <!-- Status Publikasi -->
                <div class="flex items-center gap-2 pt-2">
                    <input
                        id="art_published"
                        v-model="articleForm.is_published"
                        type="checkbox"
                        class="h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary"
                    />
                    <label for="art_published" class="text-sm font-medium text-slate-700 dark:text-slate-300">
                        Terbitkan artikel (Dapat dibaca oleh investor)
                    </label>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                    <Button
                        type="button"
                        variant="outline"
                        @click="isArticleModalOpen = false"
                        :disabled="articleForm.processing"
                    >
                        Batal
                    </Button>
                    <Button type="submit" :disabled="articleForm.processing" class="gap-1.5">
                        <Loader2 v-if="articleForm.processing" class="h-4 w-4 animate-spin" />
                        {{ isEditingArticle ? 'Simpan Perubahan' : 'Terbitkan Panduan' }}
                    </Button>
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
            :message="`Apakah Anda yakin ingin menghapus artikel '${deletingArticle?.judul}'? Tindakan ini tidak dapat dibatalkan.`"
            confirm-text="Ya, Hapus"
            cancel-text="Batal"
            type="danger"
            :loading="isDeletingArticle"
            @confirm="handleDeleteArticle"
            @cancel="isDeleteArticleModalOpen = false"
        />
    </AdminLayout>
</template>
