<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AppButton from '@/Components/Common/AppButton.vue';
import AppModal from '@/Components/Common/AppModal.vue';
import AppConfirmDialog from '@/Components/Common/AppConfirmDialog.vue';
import { compressImageFile, formatBytes } from '@/lib/imageCompression';
import {
    Search,
    Share2,
    Copy,
    Check,
    Download,
    Plus,
    MoreVertical,
    Pencil,
    Trash2,
    Image as ImageIcon,
    X,
    ChevronLeft,
    ChevronRight,
    Loader2,
    TrendingUp,
    Sparkles,
} from 'lucide-vue-next';

const props = defineProps({
    canManage: {
        type: Boolean,
        default: false,
    },
    feedUrl: {
        type: String,
        required: true,
    },
    actionUrl: {
        type: String,
        default: '/admin/profit-master',
    },
    initialSearch: {
        type: String,
        default: '',
    },
});

// State Feed & Pagination
const posts = ref([]);
const nextCursor = ref(null);
const hasMore = ref(false);
const isLoading = ref(false);
const isLoadingMore = ref(false);
const searchQuery = ref(props.initialSearch || '');
const activeSearch = ref(props.initialSearch || '');
const sentinelRef = ref(null);
let observer = null;

// State Aksi Interaktif Feed
const copiedPostId = ref(null);
const expandedPostIds = ref(new Set());

// State Lightbox Modal
const lightbox = ref({
    isOpen: false,
    images: [],
    currentIndex: 0,
});

// State Create / Edit Modal (Admin)
const isModalOpen = ref(false);
const isEditing = ref(false);
const editPostId = ref(null);
const isSubmitting = ref(false);
const isCompressing = ref(false);

const form = ref({
    caption: '',
    existingImages: [],
    deletedImageIds: [],
    newImages: [], // File objects
    newPreviews: [], // { name, size, url }
});

const formErrors = ref({});

// State Delete Confirm Dialog
const deleteDialog = ref({
    show: false,
    post: null,
    loading: false,
});

// State Dropdown Menu Aksi Per Post
const activeDropdownId = ref(null);

const toggleDropdown = (id) => {
    activeDropdownId.value = activeDropdownId.value === id ? null : id;
};

const closeDropdowns = () => {
    activeDropdownId.value = null;
};

// Klik luar untuk menutup dropdown
const handleDocumentClick = (e) => {
    if (!e.target.closest('.post-dropdown-container')) {
        closeDropdowns();
    }
};

// Fetch Feed API
const fetchFeed = async (cursor = null, isAppend = false) => {
    if (isAppend) {
        if (isLoadingMore.value || !hasMore.value) return;
        isLoadingMore.value = true;
    } else {
        isLoading.value = true;
    }

    try {
        const url = new URL(props.feedUrl, window.location.origin);
        if (activeSearch.value) {
            url.searchParams.set('search', activeSearch.value);
        }
        if (cursor) {
            url.searchParams.set('cursor', cursor);
        }

        const res = await fetch(url.toString(), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!res.ok) throw new Error('Gagal memuat postingan');
        const data = await res.json();

        if (isAppend) {
            posts.value = [...posts.value, ...data.data];
        } else {
            posts.value = data.data;
        }

        nextCursor.value = data.next_cursor;
        hasMore.value = Boolean(data.has_more);
    } catch (err) {
        console.error('Error fetching profit master feed:', err);
    } finally {
        isLoading.value = false;
        isLoadingMore.value = false;
    }
};

const handleSearch = () => {
    activeSearch.value = searchQuery.value.trim();
    fetchFeed(null, false);
};

const handleResetSearch = () => {
    searchQuery.value = '';
    activeSearch.value = '';
    fetchFeed(null, false);
};

// Infinite Scroll Observer
const setupObserver = () => {
    if (observer) observer.disconnect();

    observer = new IntersectionObserver(
        (entries) => {
            const entry = entries[0];
            if (
                entry &&
                entry.isIntersecting &&
                hasMore.value &&
                !isLoading.value &&
                !isLoadingMore.value
            ) {
                fetchFeed(nextCursor.value, true);
            }
        },
        { rootMargin: '200px' }
    );

    if (sentinelRef.value) {
        observer.observe(sentinelRef.value);
    }
};

// Expand / Collapse Caption
const toggleExpandCaption = (postId) => {
    const updated = new Set(expandedPostIds.value);
    if (updated.has(postId)) {
        updated.delete(postId);
    } else {
        updated.add(postId);
    }
    expandedPostIds.value = updated;
};

const isCaptionLong = (caption) => {
    if (!caption) return false;
    return caption.length > 250 || (caption.match(/\n/g) || []).length > 4;
};

// Salin Caption
const copyCaption = async (caption, postId) => {
    try {
        await navigator.clipboard.writeText(caption);
        copiedPostId.value = postId;
        setTimeout(() => {
            if (copiedPostId.value === postId) {
                copiedPostId.value = null;
            }
        }, 2000);
    } catch (err) {
        console.error('Gagal menyalin caption:', err);
    }
};

// Unduh Foto Tunggal
const downloadSingleImage = async (img) => {
    try {
        const res = await fetch(img.url);
        const blob = await res.blob();
        const blobUrl = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = blobUrl;
        link.download = img.file_name || 'profit_master.png';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(blobUrl);
    } catch (err) {
        window.open(img.url, '_blank');
    }
};

// Unduh Seluruh Foto dalam Post
const downloadAllPostImages = (post) => {
    if (!post.images || post.images.length === 0) return;
    post.images.forEach((img, index) => {
        setTimeout(() => {
            downloadSingleImage(img);
        }, index * 200);
    });
};

// Bagikan Post (Web Share API dengan Fallback)
const isSharing = ref(false);
const sharePost = async (post) => {
    if (isSharing.value) return;
    isSharing.value = true;

    try {
        // Cek dukungan Web Share API
        if (navigator.share) {
            // Coba sertakan berkas fisik jika didukung
            if (navigator.canShare && post.images && post.images.length > 0) {
                try {
                    const filesToShare = [];
                    for (const img of post.images.slice(0, 4)) {
                        const res = await fetch(img.url);
                        const blob = await res.blob();
                        const file = new File([blob], img.file_name || 'profit.png', {
                            type: img.mime_type || blob.type || 'image/png',
                        });
                        filesToShare.push(file);
                    }

                    if (filesToShare.length > 0 && navigator.canShare({ files: filesToShare })) {
                        await navigator.share({
                            title: 'Materi Promosi Master Trader - YZD Center',
                            text: post.caption,
                            files: filesToShare,
                        });
                        return;
                    }
                } catch (shareFilesErr) {
                    // Fallback lanjut ke share text
                }
            }

            // Share text jika share files gagal / tidak didukung
            await navigator.share({
                title: 'Materi Promosi Master Trader - YZD Center',
                text: post.caption,
            });
            return;
        }

        // Fallback untuk browser desktop: salin caption & buka unduhan
        await copyCaption(post.caption, post.id);
        downloadAllPostImages(post);
    } catch (err) {
        if (err.name !== 'AbortError') {
            await copyCaption(post.caption, post.id);
        }
    } finally {
        isSharing.value = false;
    }
};

// Buka Lightbox
const openLightbox = (images, index = 0) => {
    lightbox.value = {
        isOpen: true,
        images: images || [],
        currentIndex: index,
    };
};

const closeLightbox = () => {
    lightbox.value.isOpen = false;
};

const nextLightboxImage = () => {
    if (lightbox.value.currentIndex < lightbox.value.images.length - 1) {
        lightbox.value.currentIndex++;
    } else {
        lightbox.value.currentIndex = 0;
    }
};

const prevLightboxImage = () => {
    if (lightbox.value.currentIndex > 0) {
        lightbox.value.currentIndex--;
    } else {
        lightbox.value.currentIndex = lightbox.value.images.length - 1;
    }
};

const handleLightboxKeydown = (e) => {
    if (!lightbox.value.isOpen) return;
    if (e.key === 'Escape') closeLightbox();
    if (e.key === 'ArrowRight') nextLightboxImage();
    if (e.key === 'ArrowLeft') prevLightboxImage();
};

// Modal Create / Edit Admin
const openCreateModal = () => {
    isEditing.value = false;
    editPostId.value = null;
    formErrors.value = {};
    isDragging.value = false;
    dragCounter = 0;
    form.value = {
        caption: '',
        existingImages: [],
        deletedImageIds: [],
        newImages: [],
        newPreviews: [],
    };
    isModalOpen.value = true;
};

const openEditModal = (post) => {
    isEditing.value = true;
    editPostId.value = post.id;
    formErrors.value = {};
    isDragging.value = false;
    dragCounter = 0;
    form.value = {
        caption: post.caption,
        existingImages: [...(post.images || [])],
        deletedImageIds: [],
        newImages: [],
        newPreviews: [],
    };
    closeDropdowns();
    isModalOpen.value = true;
};

const closeModal = () => {
    if (isSubmitting.value || isCompressing.value) return;
    isModalOpen.value = false;
    isDragging.value = false;
    dragCounter = 0;
};

// State Drag & Drop & Referensi Input File
const fileInputRef = ref(null);
const isDragging = ref(false);
let dragCounter = 0;

const triggerFileInput = () => {
    if (!isCompressing.value && !isSubmitting.value) {
        fileInputRef.value?.click();
    }
};

// Proses File Terpilih (dari File Picker maupun Drag & Drop)
const processSelectedFiles = async (files) => {
    if (!files || !files.length) return;

    isCompressing.value = true;
    formErrors.value.images = null;

    try {
        for (const file of files) {
            // Validasi format file gambar
            if (!file.type || !file.type.startsWith('image/')) {
                continue;
            }

            // Kompresi senyap di sisi klien
            const compressedResult = await compressImageFile(file);
            const finalFile = compressedResult?.file || file;

            form.value.newImages.push(finalFile);
            form.value.newPreviews.push({
                name: file.name,
                size: formatBytes(finalFile.size),
                url: URL.createObjectURL(finalFile),
            });
        }
    } catch (err) {
        console.error('Error compressing images:', err);
    } finally {
        isCompressing.value = false;
    }
};

// Handle Pemilihan File via Input Dialog
const handleFileChange = async (e) => {
    const files = Array.from(e.target.files || []);
    if (!files.length) return;

    await processSelectedFiles(files);
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
};

// Handle Drag & Drop Events
const handleDragEnter = (e) => {
    e.preventDefault();
    e.stopPropagation();
    dragCounter++;
    if (
        e.dataTransfer &&
        e.dataTransfer.types &&
        Array.from(e.dataTransfer.types).includes('Files')
    ) {
        isDragging.value = true;
    }
};

const handleDragOver = (e) => {
    e.preventDefault();
    e.stopPropagation();
    if (e.dataTransfer) {
        e.dataTransfer.dropEffect = 'copy';
    }
    isDragging.value = true;
};

const handleDragLeave = (e) => {
    e.preventDefault();
    e.stopPropagation();
    dragCounter--;
    if (dragCounter <= 0) {
        dragCounter = 0;
        isDragging.value = false;
    }
};

const handleDrop = async (e) => {
    e.preventDefault();
    e.stopPropagation();
    dragCounter = 0;
    isDragging.value = false;

    if (isCompressing.value || isSubmitting.value) return;

    const files = Array.from(e.dataTransfer?.files || []);
    if (!files.length) return;

    const imageFiles = files.filter((f) => f.type && f.type.startsWith('image/'));
    if (!imageFiles.length) {
        formErrors.value.images = 'Format foto harus berupa JPG, JPEG, PNG, atau WEBP.';
        return;
    }

    await processSelectedFiles(imageFiles);
};

const removeNewImage = (index) => {
    const preview = form.value.newPreviews[index];
    if (preview?.url) {
        URL.revokeObjectURL(preview.url);
    }
    form.value.newImages.splice(index, 1);
    form.value.newPreviews.splice(index, 1);
};

const markExistingImageDeleted = (imageId) => {
    form.value.deletedImageIds.push(imageId);
    form.value.existingImages = form.value.existingImages.filter((img) => img.id !== imageId);
};

// Submit Create / Edit
const submitPost = () => {
    formErrors.value = {};

    if (!form.value.caption.trim()) {
        formErrors.value.caption = 'Caption materi promosi wajib diisi.';
        return;
    }

    const totalRemaining = form.value.existingImages.length + form.value.newImages.length;
    if (totalRemaining < 1) {
        formErrors.value.images = 'Minimal 1 foto bukti profit wajib diunggah.';
        return;
    }

    isSubmitting.value = true;

    const formData = new FormData();
    formData.append('caption', form.value.caption.trim());

    if (isEditing.value) {
        formData.append('_method', 'PUT');
        form.value.deletedImageIds.forEach((id) => {
            formData.append('deleted_image_ids[]', id);
        });
    }

    form.value.newImages.forEach((file) => {
        formData.append('images[]', file);
    });

    const submitUrl = isEditing.value ? `${props.actionUrl}/${editPostId.value}` : props.actionUrl;

    router.post(submitUrl, formData, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            isSubmitting.value = false;
            closeModal();
            fetchFeed(null, false);
        },
        onError: (errs) => {
            isSubmitting.value = false;
            formErrors.value = errs || {};
        },
    });
};

// Hapus Post (Admin)
const openDeleteDialog = (post) => {
    closeDropdowns();
    deleteDialog.value = {
        show: true,
        post,
        loading: false,
    };
};

const confirmDelete = () => {
    if (!deleteDialog.value.post) return;
    deleteDialog.value.loading = true;

    router.delete(`${props.actionUrl}/${deleteDialog.value.post.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            deleteDialog.value.show = false;
            deleteDialog.value.post = null;
            deleteDialog.value.loading = false;
            fetchFeed(null, false);
        },
        onError: () => {
            deleteDialog.value.loading = false;
        },
    });
};

onMounted(() => {
    document.addEventListener('click', handleDocumentClick);
    window.addEventListener('keydown', handleLightboxKeydown);
    fetchFeed();
});

onUnmounted(() => {
    document.removeEventListener('click', handleDocumentClick);
    window.removeEventListener('keydown', handleLightboxKeydown);
    if (observer) observer.disconnect();
    // Bersihkan URL preview lokal
    form.value.newPreviews.forEach((p) => {
        if (p?.url) URL.revokeObjectURL(p.url);
    });
});

watch(sentinelRef, () => {
    setupObserver();
});
</script>

<template>
    <div class="mx-auto w-full max-w-4xl pb-16">
        <!-- Pencarian & Filter Header -->
        <div
            class="mb-5 rounded-2xl border border-slate-200/80 bg-white p-3 shadow-sm dark:border-slate-800 dark:bg-slate-900"
        >
            <div class="flex items-center gap-2">
                <div class="relative flex-1">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400"
                    />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari materi promosi berdasarkan caption..."
                        class="focus:border-primary-500 focus:ring-primary-500/20 dark:focus:border-primary-400 h-10 w-full rounded-xl border border-slate-200 bg-slate-50/60 pr-3 pl-9 text-sm text-slate-800 placeholder-slate-400 transition-colors focus:bg-white focus:ring-2 focus:outline-none dark:border-slate-700 dark:bg-slate-800/80 dark:text-slate-100 dark:placeholder-slate-500 dark:focus:bg-slate-900"
                        @keydown.enter.prevent="handleSearch"
                    />
                </div>
                <AppButton
                    type="custom"
                    label="Cari"
                    :icon="Search"
                    size="md"
                    class="min-w-[76px]"
                    @click="handleSearch"
                />
            </div>

            <div
                v-if="activeSearch"
                class="mt-2.5 flex items-center justify-between px-1 text-xs text-slate-500 dark:text-slate-400"
            >
                <span>
                    Hasil pencarian untuk:
                    <strong class="text-slate-800 dark:text-slate-200">"{{ activeSearch }}"</strong>
                </span>
                <button
                    type="button"
                    class="text-primary-600 dark:text-primary-400 cursor-pointer font-medium hover:underline"
                    @click="handleResetSearch"
                >
                    Hapus Filter
                </button>
            </div>
        </div>

        <!-- Tombol / Kotak Buat Postingan Baru (Khusus Admin) -->
        <div
            v-if="canManage"
            class="mb-6 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm transition-all hover:border-slate-300 dark:border-slate-800 dark:bg-slate-900"
        >
            <div class="flex items-center gap-3">
                <div
                    class="bg-primary-100 text-primary-700 dark:bg-primary-950 dark:text-primary-300 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
                >
                    <TrendingUp class="h-5 w-5" />
                </div>
                <button
                    type="button"
                    class="hover:border-primary-400 hover:bg-primary-50/30 dark:hover:border-primary-500 flex h-11 flex-1 min-w-0 cursor-pointer items-center rounded-xl border border-dashed border-slate-300 bg-slate-50/70 px-4 text-left text-sm text-slate-500 transition-colors hover:text-slate-700 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-400 dark:hover:text-slate-200"
                    @click="openCreateModal"
                    placeholder="Bagikan materi promosi profit baru.."
                >
                    <span class="truncate block w-full">Bagikan materi promosi profit baru...</span>
                </button>
                <AppButton type="create" label="Buat Post" size="md" @click="openCreateModal" />
            </div>
        </div>

        <!-- Skeleton Loading Awal -->
        <div v-if="isLoading && posts.length === 0" class="space-y-4">
            <div
                v-for="n in 3"
                :key="n"
                class="animate-pulse rounded-2xl border border-slate-200/70 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900"
            >
                <div class="mb-3 flex items-center gap-3">
                    <div class="h-10 w-10 rounded-full bg-slate-200 dark:bg-slate-800"></div>
                    <div class="flex-1 space-y-1.5">
                        <div class="h-3.5 w-32 rounded bg-slate-200 dark:bg-slate-800"></div>
                        <div class="h-2.5 w-20 rounded bg-slate-100 dark:bg-slate-800"></div>
                    </div>
                </div>
                <div class="mb-4 space-y-2">
                    <div class="h-3 w-full rounded bg-slate-200 dark:bg-slate-800"></div>
                    <div class="h-3 w-4/5 rounded bg-slate-200 dark:bg-slate-800"></div>
                </div>
                <div class="h-56 w-full rounded-xl bg-slate-200 dark:bg-slate-800"></div>
            </div>
        </div>

        <!-- Kondisi Kosong (Tidak Ada Post) -->
        <div
            v-else-if="!isLoading && posts.length === 0"
            class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center shadow-xs dark:border-slate-800 dark:bg-slate-900"
        >
            <div
                class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500"
            >
                <ImageIcon class="h-7 w-7" />
            </div>
            <h3 class="text-base font-semibold text-slate-800 dark:text-slate-200">
                {{ activeSearch ? 'Materi Tidak Ditemukan' : 'Belum Ada Materi Promosi' }}
            </h3>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                {{
                    activeSearch
                        ? 'Coba gunakan kata kunci pencarian yang lain atau reset filter.'
                        : 'Admin belum menerbitkan materi promosi profit master saat ini.'
                }}
            </p>
            <AppButton
                v-if="canManage && !activeSearch"
                type="create"
                label="Terbitkan Post Pertama"
                size="sm"
                class="mt-4"
                @click="openCreateModal"
            />
        </div>

        <!-- Daftar Feed Postingan -->
        <div v-else class="space-y-5">
            <article
                v-for="post in posts"
                :key="post.id"
                class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs transition-shadow hover:shadow-sm sm:p-5 dark:border-slate-800 dark:bg-slate-900"
            >
                <!-- Header Post: Profil & Menu Aksi Admin -->
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 text-xs font-bold text-white shadow-xs"
                        >
                            YZD
                        </div>
                        <div>
                            <div class="flex items-center gap-1.5">
                                <h4 class="text-sm font-bold text-slate-900 dark:text-slate-100">
                                    {{ post.creator?.name || 'Master Trader' }}
                                </h4>
                            </div>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                {{ post.relative_time }} • {{ post.formatted_date }}
                            </p>
                        </div>
                    </div>

                    <!-- Dropdown Opsi Admin -->
                    <div v-if="canManage" class="post-dropdown-container relative">
                        <button
                            type="button"
                            class="flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800 dark:hover:text-slate-200"
                            aria-label="Menu Opsi Postingan"
                            @click.stop="toggleDropdown(post.id)"
                        >
                            <MoreVertical class="h-4 w-4" />
                        </button>

                        <div
                            v-if="activeDropdownId === post.id"
                            class="absolute top-9 right-0 z-20 w-36 rounded-xl border border-slate-200 bg-white py-1 shadow-lg dark:border-slate-700 dark:bg-slate-800"
                        >
                            <button
                                type="button"
                                class="flex w-full cursor-pointer items-center gap-2 px-3 py-2 text-left text-xs font-medium text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-700/60"
                                @click="openEditModal(post)"
                            >
                                <Pencil class="h-3.5 w-3.5" />
                                Edit Post
                            </button>
                            <button
                                type="button"
                                class="flex w-full cursor-pointer items-center gap-2 px-3 py-2 text-left text-xs font-medium text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/40"
                                @click="openDeleteDialog(post)"
                            >
                                <Trash2 class="h-3.5 w-3.5" />
                                Hapus Post
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Konten Caption -->
                <div class="mt-3.5">
                    <p
                        class="text-sm leading-relaxed whitespace-pre-line text-slate-800 dark:text-slate-200"
                        :class="{
                            'line-clamp-6':
                                !expandedPostIds.has(post.id) && isCaptionLong(post.caption),
                        }"
                    >
                        {{ post.caption }}
                    </p>

                    <button
                        v-if="isCaptionLong(post.caption)"
                        type="button"
                        class="text-primary-600 dark:text-primary-400 mt-1 cursor-pointer text-xs font-semibold hover:underline"
                        @click="toggleExpandCaption(post.id)"
                    >
                        {{ expandedPostIds.has(post.id) ? 'Lebih sedikit' : 'Selengkapnya' }}
                    </button>
                </div>

                <!-- Grid Gambar Bukti Profit (Ala X / Thread) -->
                <div
                    v-if="post.images && post.images.length > 0"
                    class="mt-3.5 overflow-hidden rounded-xl border border-slate-100 dark:border-slate-800/80"
                >
                    <!-- 1 Gambar -->
                    <div
                        v-if="post.images.length === 1"
                        class="relative cursor-pointer overflow-hidden bg-slate-950"
                        @click="openLightbox(post.images, 0)"
                    >
                        <img
                            :src="post.images[0].url"
                            :alt="post.images[0].file_name"
                            loading="lazy"
                            decoding="async"
                            class="mx-auto max-h-[460px] w-full object-contain transition-transform duration-300 hover:scale-[1.01]"
                        />
                    </div>

                    <!-- 2 Gambar -->
                    <div
                        v-else-if="post.images.length === 2"
                        class="grid grid-cols-2 gap-1 bg-slate-100 dark:bg-slate-800"
                    >
                        <div
                            v-for="(img, idx) in post.images"
                            :key="img.id"
                            class="relative aspect-4/3 cursor-pointer overflow-hidden bg-slate-900"
                            @click="openLightbox(post.images, idx)"
                        >
                            <img
                                :src="img.url"
                                :alt="img.file_name"
                                loading="lazy"
                                decoding="async"
                                class="h-full w-full object-cover transition-transform duration-300 hover:scale-105"
                            />
                        </div>
                    </div>

                    <!-- 3 Gambar -->
                    <div
                        v-else-if="post.images.length === 3"
                        class="grid grid-cols-2 gap-1 bg-slate-100 dark:bg-slate-800"
                    >
                        <div
                            class="relative row-span-2 aspect-auto cursor-pointer overflow-hidden bg-slate-900"
                            @click="openLightbox(post.images, 0)"
                        >
                            <img
                                :src="post.images[0].url"
                                :alt="post.images[0].file_name"
                                loading="lazy"
                                decoding="async"
                                class="h-full w-full object-cover transition-transform duration-300 hover:scale-105"
                            />
                        </div>
                        <div
                            v-for="(img, idx) in post.images.slice(1, 3)"
                            :key="img.id"
                            class="relative aspect-4/3 cursor-pointer overflow-hidden bg-slate-900"
                            @click="openLightbox(post.images, idx + 1)"
                        >
                            <img
                                :src="img.url"
                                :alt="img.file_name"
                                loading="lazy"
                                decoding="async"
                                class="h-full w-full object-cover transition-transform duration-300 hover:scale-105"
                            />
                        </div>
                    </div>

                    <!-- 4 atau Lebih Gambar -->
                    <div v-else class="grid grid-cols-2 gap-1 bg-slate-100 dark:bg-slate-800">
                        <div
                            v-for="(img, idx) in post.images.slice(0, 4)"
                            :key="img.id"
                            class="relative aspect-4/3 cursor-pointer overflow-hidden bg-slate-900"
                            @click="openLightbox(post.images, idx)"
                        >
                            <img
                                :src="img.url"
                                :alt="img.file_name"
                                loading="lazy"
                                decoding="async"
                                class="h-full w-full object-cover transition-transform duration-300 hover:scale-105"
                            />

                            <!-- Overlay Sisa Foto di Slot ke-4 -->
                            <div
                                v-if="idx === 3 && post.images.length > 4"
                                class="absolute inset-0 flex items-center justify-center bg-black/60 text-lg font-bold text-white transition-colors hover:bg-black/70"
                            >
                                +{{ post.images.length - 3 }} foto
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bilah Aksi Interaktif (Target Sentuh >= 40px) -->
                <div
                    class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3 dark:border-slate-800"
                >
                    <div class="flex items-center gap-1.5">
                        <!-- Tombol Salin Caption -->
                        <AppButton
                            type="custom"
                            variant="ghost"
                            size="sm"
                            :icon="copiedPostId === post.id ? Check : Copy"
                            :label="copiedPostId === post.id ? 'Tersalin!' : 'Salin Caption'"
                            :class="
                                copiedPostId === post.id
                                    ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300'
                                    : 'text-slate-600 dark:text-slate-300'
                            "
                            @click="copyCaption(post.caption, post.id)"
                        />

                        <!-- Tombol Unduh Semua Foto -->
                        <AppButton
                            v-if="post.images && post.images.length > 0"
                            type="custom"
                            variant="ghost"
                            size="sm"
                            :icon="Download"
                            :label="`Unduh (${post.images.length})`"
                            title="Unduh seluruh foto bukti"
                            @click="downloadAllPostImages(post)"
                        />
                    </div>

                    <!-- Tombol Bagikan (Web Share API) -->
                    <AppButton
                        type="custom"
                        variant="secondary"
                        size="sm"
                        :icon="Share2"
                        label="Bagikan"
                        :loading="isSharing"
                        class="bg-primary-50 text-primary-700 hover:bg-primary-100 dark:bg-primary-950 dark:text-primary-300 dark:hover:bg-primary-900"
                        @click="sharePost(post)"
                    />
                </div>
            </article>
        </div>

        <!-- Sentinel Elemen untuk Infinite Scroll -->
        <div ref="sentinelRef" class="py-4 text-center">
            <div
                v-if="isLoadingMore"
                class="flex items-center justify-center gap-2 text-xs text-slate-500 dark:text-slate-400"
            >
                <Loader2 class="text-primary-600 h-4 w-4 animate-spin" />
                <span>Memuat materi promosi berikutnya...</span>
            </div>
            <p
                v-else-if="!hasMore && posts.length > 0"
                class="text-xs text-slate-400 dark:text-slate-500"
            >
                Semua materi promosi sudah ditampilkan.
            </p>
        </div>

        <!-- MODAL LIGHTBOX FOTO PENUH -->
        <div
            v-if="lightbox.isOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/95 backdrop-blur-xs select-none"
            @click.self="closeLightbox"
        >
            <!-- Kontrol Atas Lightbox -->
            <div
                class="pointer-events-none absolute inset-x-4 top-4 z-10 flex items-center justify-between text-white"
            >
                <span
                    class="pointer-events-auto rounded-lg bg-black/60 px-3 py-1.5 text-xs font-semibold backdrop-blur-xs"
                >
                    {{ lightbox.currentIndex + 1 }} / {{ lightbox.images.length }}
                </span>
                <div class="pointer-events-auto flex items-center gap-2">
                    <button
                        type="button"
                        class="flex h-10 w-10 cursor-pointer items-center justify-center rounded-xl bg-black/60 text-white transition-colors hover:bg-white/20"
                        title="Unduh foto ini"
                        @click="downloadSingleImage(lightbox.images[lightbox.currentIndex])"
                    >
                        <Download class="h-5 w-5" />
                    </button>
                    <button
                        type="button"
                        class="flex h-10 w-10 cursor-pointer items-center justify-center rounded-xl bg-black/60 text-white transition-colors hover:bg-white/20"
                        title="Tutup (Esc)"
                        @click="closeLightbox"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>
            </div>

            <!-- Tombol Navigasi Gambar Sebelumnya -->
            <button
                v-if="lightbox.images.length > 1"
                type="button"
                class="absolute top-1/2 left-4 flex h-12 w-12 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full bg-black/60 text-white transition-colors hover:bg-white/20"
                aria-label="Foto Sebelumnya"
                @click="prevLightboxImage"
            >
                <ChevronLeft class="h-7 w-7" />
            </button>

            <!-- Tampilan Foto Utama -->
            <div class="max-h-[85vh] max-w-[92vw] overflow-hidden p-2">
                <img
                    :src="lightbox.images[lightbox.currentIndex]?.url"
                    :alt="lightbox.images[lightbox.currentIndex]?.file_name"
                    class="mx-auto max-h-[82vh] max-w-[90vw] rounded-lg object-contain"
                />
            </div>

            <!-- Tombol Navigasi Gambar Berikutnya -->
            <button
                v-if="lightbox.images.length > 1"
                type="button"
                class="absolute top-1/2 right-4 flex h-12 w-12 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full bg-black/60 text-white transition-colors hover:bg-white/20"
                aria-label="Foto Berikutnya"
                @click="nextLightboxImage"
            >
                <ChevronRight class="h-7 w-7" />
            </button>
        </div>

        <!-- MODAL FORM CREATE / EDIT (ADMIN) -->
        <AppModal
            :show="isModalOpen"
            :title="isEditing ? 'Ubah Materi Promosi' : 'Buat Materi Promosi Baru'"
            max-width="lg"
            :closeable="!isSubmitting && !isCompressing"
            @close="closeModal"
        >
            <form @submit.prevent="submitPost" class="space-y-4">
                <!-- Input Caption -->
                <div>
                    <label
                        class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-slate-300"
                    >
                        Teks / Caption Promosi <span class="text-rose-500">*</span>
                    </label>
                    <textarea
                        v-model="form.caption"
                        rows="5"
                        placeholder="Tuliskan detail pencapaian profit, edukasi, atau arahan promosi bagi para member..."
                        class="focus:border-primary-500 focus:ring-primary-500/20 w-full rounded-xl border border-slate-300 bg-white p-3 text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:outline-none dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                        maxlength="20000"
                    ></textarea>
                    <div class="mt-1 flex items-center justify-between text-[11px] text-slate-400">
                        <span>Dapat menggunakan emoji dan baris baru.</span>
                        <span>{{ form.caption.length }} / 20.000</span>
                    </div>
                    <p v-if="formErrors.caption" class="mt-1 text-xs font-medium text-rose-500">
                        {{ formErrors.caption }}
                    </p>
                </div>

                <!-- Bagian Gambar Yang Sudah Ada (Saat Edit) -->
                <div v-if="isEditing && form.existingImages.length > 0">
                    <label
                        class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-slate-300"
                    >
                        Foto Saat Ini ({{ form.existingImages.length }})
                    </label>
                    <div class="grid grid-cols-3 gap-2">
                        <div
                            v-for="img in form.existingImages"
                            :key="img.id"
                            class="group relative aspect-square overflow-hidden rounded-xl border border-slate-200 bg-slate-100 dark:border-slate-800 dark:bg-slate-800"
                        >
                            <img
                                :src="img.url"
                                :alt="img.file_name"
                                class="h-full w-full object-cover"
                            />
                            <button
                                type="button"
                                class="absolute top-1.5 right-1.5 flex h-6 w-6 cursor-pointer items-center justify-center rounded-lg bg-black/70 text-white transition-opacity hover:bg-rose-600"
                                title="Hapus foto ini"
                                @click="markExistingImageDeleted(img.id)"
                            >
                                <X class="h-3.5 w-3.5" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Unggah Foto Baru -->
                <div>
                    <label
                        class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-slate-300"
                    >
                        Unggah Foto Bukti Profit
                        <span
                            v-if="!isEditing || form.existingImages.length === 0"
                            class="text-rose-500"
                            >*</span
                        >
                    </label>

                    <div
                        role="button"
                        tabindex="0"
                        class="focus:ring-primary-500/20 relative flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed p-5 text-center transition-all duration-200 select-none focus:ring-2 focus:outline-none"
                        :class="[
                            isDragging
                                ? 'border-primary-500 bg-primary-50/70 ring-primary-500/15 dark:border-primary-400 dark:bg-primary-950/40 scale-[1.01] ring-4'
                                : 'hover:border-primary-500 hover:bg-primary-50/20 dark:hover:border-primary-400 border-slate-300 dark:border-slate-700',
                        ]"
                        @dragenter="handleDragEnter"
                        @dragover="handleDragOver"
                        @dragleave="handleDragLeave"
                        @drop="handleDrop"
                        @click="triggerFileInput"
                        @keydown.enter.prevent="triggerFileInput"
                        @keydown.space.prevent="triggerFileInput"
                    >
                        <div
                            class="mb-2 flex h-11 w-11 items-center justify-center rounded-xl transition-colors"
                            :class="
                                isDragging
                                    ? 'bg-primary-600 text-white shadow-xs'
                                    : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400'
                            "
                        >
                            <ImageIcon class="h-5 w-5" />
                        </div>
                        <span
                            class="text-xs font-semibold"
                            :class="
                                isDragging
                                    ? 'text-primary-700 dark:text-primary-300 font-bold'
                                    : 'text-slate-700 dark:text-slate-200'
                            "
                        >
                            {{
                                isDragging
                                    ? 'Lepaskan foto bukti di sini...'
                                    : 'Pilih foto atau seret ke sini'
                            }}
                        </span>
                        <span class="mt-0.5 text-[11px] text-slate-400">
                            Format JPG, PNG, WEBP (maks. 10 MB/foto)
                        </span>
                        <input
                            ref="fileInputRef"
                            type="file"
                            multiple
                            accept="image/jpeg,image/png,image/webp"
                            class="hidden"
                            :disabled="isCompressing"
                            @change="handleFileChange"
                        />
                    </div>

                    <!-- Status Kompresi Senyap -->
                    <div
                        v-if="isCompressing"
                        class="text-primary-600 mt-2 flex items-center gap-2 text-xs font-medium"
                    >
                        <Loader2 class="h-3.5 w-3.5 animate-spin" />
                        <span>Mengompresi gambar di latar belakang...</span>
                    </div>

                    <!-- Pratinjau Foto Baru -->
                    <div v-if="form.newPreviews.length > 0" class="mt-3 grid grid-cols-3 gap-2">
                        <div
                            v-for="(prev, idx) in form.newPreviews"
                            :key="idx"
                            class="relative aspect-square overflow-hidden rounded-xl border border-slate-200 bg-slate-100 dark:border-slate-800 dark:bg-slate-800"
                        >
                            <img
                                :src="prev.url"
                                :alt="prev.name"
                                class="h-full w-full object-cover"
                            />
                            <button
                                type="button"
                                class="absolute top-1.5 right-1.5 flex h-6 w-6 cursor-pointer items-center justify-center rounded-lg bg-black/70 text-white transition-opacity hover:bg-rose-600"
                                title="Batal unggah"
                                @click="removeNewImage(idx)"
                            >
                                <X class="h-3.5 w-3.5" />
                            </button>
                            <span
                                class="absolute inset-x-1 bottom-1 truncate rounded bg-black/60 px-1 py-0.5 text-[9px] text-white"
                            >
                                {{ prev.size }}
                            </span>
                        </div>
                    </div>

                    <p v-if="formErrors.images" class="mt-1.5 text-xs font-medium text-rose-500">
                        {{ formErrors.images }}
                    </p>
                </div>
            </form>

            <template #footer>
                <div class="flex items-center justify-end gap-2">
                    <AppButton
                        type="cancel"
                        label="Batal"
                        size="sm"
                        :disabled="isSubmitting || isCompressing"
                        @click="closeModal"
                    />
                    <AppButton
                        :type="isEditing ? 'save' : 'submit'"
                        :label="isEditing ? 'Simpan Perubahan' : 'Terbitkan Sekarang'"
                        size="sm"
                        :loading="isSubmitting"
                        :loading-text="isEditing ? 'Menyimpan...' : 'Menerbitkan...'"
                        :disabled="isSubmitting || isCompressing"
                        @click="submitPost"
                    />
                </div>
            </template>
        </AppModal>

        <!-- DIALOG KONFIRMASI HAPUS POST (ADMIN) -->
        <AppConfirmDialog
            :show="deleteDialog.show"
            title="Hapus Materi Promosi"
            message="Apakah Anda yakin ingin menghapus materi promosi ini? Seluruh gambar dan konten di dalamnya akan dihapus secara permanen."
            confirm-text="Hapus Post"
            type="danger"
            :loading="deleteDialog.loading"
            @confirm="confirmDelete"
            @cancel="deleteDialog.show = false"
        />
    </div>
</template>
