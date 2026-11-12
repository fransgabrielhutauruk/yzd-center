<script setup>
import { ref, computed, watch, watchEffect } from 'vue';
import { router } from '@inertiajs/vue3';
import AppCheckbox from '@/Components/Common/AppCheckbox.vue';
import AppButton from '@/Components/Common/AppButton.vue';
import {
    Search,
    ArrowUpDown,
    ArrowUp,
    ArrowDown,
    ChevronLeft,
    ChevronRight,
    ChevronDown,
    Inbox,
    Loader2,
    ListFilter,
    FileSpreadsheet,
    X
} from 'lucide-vue-next';

const props = defineProps({
    data: {
        type: Object,
        required: true,
        // Struktur Laravel LengthAwarePaginator:
        // { data: [], current_page, last_page, per_page, total, from, to, links: [] }
    },
    columns: {
        type: Array,
        required: true,
        // Array of { key, label, sortable?: boolean, align?: 'left'|'center'|'right', class?: string }
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    fetchUrl: {
        type: String,
        default: '',
    },
    paramPrefix: {
        type: String,
        default: '',
    },
    extraParams: {
        type: Object,
        default: () => ({}),
    },
    searchPlaceholder: {
        type: String,
        default: 'Cari data...',
    },
    emptyTitle: {
        type: String,
        default: 'Tidak Ada Data Ditemukan',
    },
    emptyMessage: {
        type: String,
        default: 'Belum ada data tersedia atau coba sesuaikan kata kunci pencarian Anda.',
    },
    showSearch: {
        type: Boolean,
        default: true,
    },
    showPerPage: {
        type: Boolean,
        default: true,
    },
    perPageOptions: {
        type: Array,
        default: () => [10, 25, 50, 100],
    },
    enableBatch: {
        type: Boolean,
        default: false,
    },
    idKey: {
        type: String,
        default: 'id',
    },
    defaultFiltersOpen: {
        type: Boolean,
        default: false,
    },
    only: {
        type: Array,
        default: () => [],
    },
    exportable: {
        type: Boolean,
        default: false,
    },
    exportUrl: {
        type: String,
        default: '',
    },
    exportLabel: {
        type: String,
        default: 'Ekspor XLSX',
    },
});

const selected = defineModel('selected', {
    type: Array,
    default: () => [],
});

const emit = defineEmits(['batch-action']);

const searchKey = computed(() => (props.paramPrefix ? `${props.paramPrefix}search` : 'search'));
const sortByKey = computed(() => (props.paramPrefix ? `${props.paramPrefix}sort_by` : 'sort_by'));
const sortDirectionKey = computed(() => (props.paramPrefix ? `${props.paramPrefix}sort_direction` : 'sort_direction'));
const perPageKey = computed(() => (props.paramPrefix ? `${props.paramPrefix}per_page` : 'per_page'));
const pageKey = computed(() => (props.paramPrefix ? `${props.paramPrefix}page` : 'page'));

// Filter panel state & active filter detection
const activeFilterCount = computed(() => {
    if (!props.filters || typeof props.filters !== 'object') return 0;

    const standardKeys = new Set([
        'search',
        'sort',
        'sort_by',
        'sortBy',
        'direction',
        'sort_direction',
        'sortDirection',
        'per_page',
        'perPage',
        'page',
        'tab',
        searchKey.value,
        sortByKey.value,
        sortDirectionKey.value,
        perPageKey.value,
        pageKey.value,
    ]);

    let count = 0;
    for (const [key, val] of Object.entries(props.filters)) {
        if (!standardKeys.has(key) && val !== undefined && val !== null && val !== '') {
            count++;
        }
    }
    return count;
});

const isFilterOpen = ref(props.defaultFiltersOpen || activeFilterCount.value > 0);

const search = ref(props.filters[searchKey.value] || props.filters.search || '');
const sortBy = ref(props.filters[sortByKey.value] || props.filters.sort_by || '');
const sortDirection = ref(props.filters[sortDirectionKey.value] || props.filters.sort_direction || 'desc');
const perPage = ref(props.filters[perPageKey.value] || props.filters.per_page || props.data?.per_page || 10);
const isLoading = ref(false);

const isSearchFocused = ref(false);
const searchInputRef = ref(null);
const lastSentSearch = ref(search.value);

let debounceTimeout = null;

const clearSearch = () => {
    search.value = '';
    lastSentSearch.value = '';
    if (debounceTimeout) {
        clearTimeout(debounceTimeout);
    }
    applyFilters({ [pageKey.value]: 1 });
    searchInputRef.value?.focus();
};

const handleSearchEnter = () => {
    if (debounceTimeout) {
        clearTimeout(debounceTimeout);
    }
    applyFilters({ [pageKey.value]: 1 });
};

const resetCustomFilters = () => {
    if (!props.filters || typeof props.filters !== 'object') return;

    const standardKeys = new Set([
        'search',
        'sort',
        'sort_by',
        'sortBy',
        'direction',
        'sort_direction',
        'sortDirection',
        'per_page',
        'perPage',
        'page',
        'tab',
        searchKey.value,
        sortByKey.value,
        sortDirectionKey.value,
        perPageKey.value,
        pageKey.value,
    ]);

    const resetParams = { [pageKey.value]: 1 };
    for (const key of Object.keys(props.filters)) {
        if (!standardKeys.has(key)) {
            resetParams[key] = undefined;
        }
    }
    applyFilters(resetParams);
};

const applyFilters = (customParams = {}) => {
    isLoading.value = true;
    lastSentSearch.value = search.value;

    // Normalisasi parameter jika customParams menyertakan 'page' generik saat paramPrefix aktif
    const normalizedCustom = { ...customParams };
    if (props.paramPrefix && normalizedCustom.page !== undefined && normalizedCustom[pageKey.value] === undefined) {
        normalizedCustom[pageKey.value] = normalizedCustom.page;
        delete normalizedCustom.page;
    }

    // Ambil semua query params saat ini dari URL agar tidak menghapus parameter tabel/tab lain
    let currentParams = {};
    try {
        currentParams = Object.fromEntries(new URLSearchParams(window.location.search).entries());
    } catch {
        currentParams = {};
    }

    const queryParams = {
        ...currentParams,
        ...props.extraParams,
        ...props.filters,
        [searchKey.value]: search.value || undefined,
        [sortByKey.value]: sortBy.value || undefined,
        [sortDirectionKey.value]: sortDirection.value || undefined,
        [perPageKey.value]: perPage.value || undefined,
        ...normalizedCustom,
    };

    // Bersihkan parameter bernilai undefined, null, atau kosong
    Object.keys(queryParams).forEach((key) => {
        if (queryParams[key] === undefined || queryParams[key] === null || queryParams[key] === '') {
            delete queryParams[key];
        }
    });

    const visitOptions = {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onFinish: () => {
            isLoading.value = false;
        },
    };

    if (Array.isArray(props.only) && props.only.length > 0) {
        visitOptions.only = props.only;
    }

    const targetUrl = props.fetchUrl || (typeof window !== 'undefined' ? window.location.pathname : '');
    router.get(targetUrl, queryParams, visitOptions);
};

// Debounce search input
watch(search, (newVal) => {
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => {
        if (newVal !== lastSentSearch.value) {
            applyFilters({ [pageKey.value]: 1 });
        }
    }, 350);
});

// Watch props.filters untuk sinkronisasi jika filter berubah dari luar
watch(
    () => props.filters,
    (newFilters) => {
        if (newFilters) {
            const serverSearch = newFilters[searchKey.value] ?? newFilters.search ?? '';

            // HANYA sinkronkan search lokal jika input tidak sedang difokuskan user
            // DAN nilai dari server berbeda dari apa yang terakhir dikirim
            // (mencegah respons lama dari server menimpa apa yang sedang diketik user)
            if (!isSearchFocused.value && serverSearch !== lastSentSearch.value) {
                search.value = serverSearch;
                lastSentSearch.value = serverSearch;
            }

            sortBy.value = newFilters[sortByKey.value] ?? newFilters.sort_by ?? '';
            sortDirection.value = newFilters[sortDirectionKey.value] ?? newFilters.sort_direction ?? 'desc';
            perPage.value = newFilters[perPageKey.value] ?? newFilters.per_page ?? props.data?.per_page ?? 10;
        }
    },
    { deep: true }
);

// Per page change
const onPerPageChange = (event) => {
    perPage.value = Number(event.target.value);
    applyFilters({ [pageKey.value]: 1, [perPageKey.value]: perPage.value });
};

// Sort column
const handleSort = (column) => {
    if (!column.sortable) return;

    if (sortBy.value === column.key) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = column.key;
        sortDirection.value = 'asc';
    }

    applyFilters({
        [sortByKey.value]: sortBy.value,
        [sortDirectionKey.value]: sortDirection.value,
        [pageKey.value]: 1,
    });
};

// Pagination click
const goToPage = (url) => {
    if (!url || isLoading.value) return;
    isLoading.value = true;
    const visitOptions = {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
            isLoading.value = false;
        },
    };

    if (Array.isArray(props.only) && props.only.length > 0) {
        visitOptions.only = props.only;
    }

    router.visit(url, visitOptions);
};

const getAlignmentClass = (align) => {
    switch (align) {
        case 'center':
            return 'text-center justify-center';
        case 'right':
            return 'text-right justify-end';
        case 'left':
        default:
            return 'text-left justify-start';
    }
};

// ==========================================
// Logika Ekspor Data ke Berkas XLSX
// ==========================================
const handleExport = () => {
    const params = new URLSearchParams();

    // 1. Sertakan extraParams jika ada
    if (props.extraParams && typeof props.extraParams === 'object') {
        for (const [key, val] of Object.entries(props.extraParams)) {
            if (val !== undefined && val !== null && val !== '') {
                params.set(key, String(val));
            }
        }
    }

    // 2. Sertakan filter aktif dari props.filters
    if (props.filters && typeof props.filters === 'object') {
        for (const [key, val] of Object.entries(props.filters)) {
            if (val !== undefined && val !== null && val !== '') {
                // Abaikan parameter paginasi
                if (key === 'page' || key === 'per_page' || key === perPageKey.value || key === pageKey.value) {
                    continue;
                }
                params.set(key, String(val));
            }
        }
    }

    // 3. Sertakan search aktif
    if (search.value) {
        params.set(searchKey.value, search.value);
    }

    // 4. Sertakan sorting aktif
    if (sortBy.value) {
        params.set(sortByKey.value, sortBy.value);
        params.set(sortDirectionKey.value, sortDirection.value);
    }

    const baseUrl = props.exportUrl || (props.fetchUrl ? `${props.fetchUrl}/export` : '/export');
    const queryString = params.toString();
    const finalUrl = queryString ? `${baseUrl}?${queryString}` : baseUrl;

    window.location.href = finalUrl;
};

// ==========================================
// Logika Pilihan Batch (Checklist Masal)
// ==========================================
const getItemId = (item, index) => {
    return item[props.idKey] ?? item.id ?? item.invitation_id ?? index;
};

const isAllSelected = computed(() => {
    const pageItems = props.data?.data || [];
    if (pageItems.length === 0) return false;
    const current = Array.isArray(selected.value) ? selected.value : [];
    return pageItems.every((item, idx) => current.includes(getItemId(item, idx)));
});

const isIndeterminate = computed(() => {
    const pageItems = props.data?.data || [];
    if (pageItems.length === 0) return false;
    const current = Array.isArray(selected.value) ? selected.value : [];
    const selectedCountOnPage = pageItems.filter((item, idx) =>
        current.includes(getItemId(item, idx))
    ).length;
    return selectedCountOnPage > 0 && selectedCountOnPage < pageItems.length;
});

const toggleSelectAll = () => {
    const pageItems = props.data?.data || [];
    if (pageItems.length === 0) return;

    const current = Array.isArray(selected.value) ? selected.value : [];
    if (isAllSelected.value) {
        const pageIds = pageItems.map((item, idx) => getItemId(item, idx));
        selected.value = current.filter((id) => !pageIds.includes(id));
    } else {
        const newIds = new Set(current);
        pageItems.forEach((item, idx) => {
            newIds.add(getItemId(item, idx));
        });
        selected.value = Array.from(newIds);
    }
};

const toggleSelectItem = (item, index) => {
    const id = getItemId(item, index);
    const current = Array.isArray(selected.value) ? selected.value : [];
    const pos = current.indexOf(id);
    if (pos > -1) {
        selected.value = current.filter((i) => i !== id);
    } else {
        selected.value = [...current, id];
    }
};

const isItemSelected = (item, index) => {
    const current = Array.isArray(selected.value) ? selected.value : [];
    return current.includes(getItemId(item, index));
};

const clearSelection = () => {
    selected.value = [];
};

defineExpose({
    clearSelection,
    clearSearch,
    searchInputRef,
    selected,
    selectedIds: selected,
    isFilterOpen,
    resetCustomFilters,
});
</script>

<template>
    <div class="space-y-4">
        <!-- Toolbar: Search Bar, Custom Actions, Filters Button, Per-Page Selector & Collapsible Filters -->
        <div class="bg-card p-3 sm:p-4 rounded-xl border border-border shadow-xs">
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                <!-- Search Input -->
                <div v-if="showSearch" class="relative w-full sm:max-w-xs md:max-w-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-muted-foreground">
                        <Search class="w-4 h-4" />
                    </div>
                    <input
                        ref="searchInputRef"
                        v-model="search"
                        type="text"
                        :placeholder="searchPlaceholder"
                        @focus="isSearchFocused = true"
                        @blur="isSearchFocused = false"
                        @keydown.enter.prevent="handleSearchEnter"
                        class="w-full h-8 min-h-[36px] pl-9 pr-9 py-1.5 text-xs bg-background border border-input rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500/50 focus:border-primary-500 transition-all placeholder:text-muted-foreground shadow-2xs"
                    />
                    <div class="absolute inset-y-0 right-0 pr-2.5 flex items-center gap-1.5">
                        <Loader2 v-if="isLoading" class="w-3.5 h-3.5 animate-spin text-primary-600 pointer-events-none" />
                        <button
                            v-if="search"
                            type="button"
                            @click="clearSearch"
                            class="p-0.5 text-muted-foreground hover:text-foreground rounded transition-colors cursor-pointer"
                            title="Hapus pencarian"
                            aria-label="Hapus pencarian"
                        >
                            <X class="w-3.5 h-3.5" />
                        </button>
                    </div>
                </div>
                <div v-else class="hidden sm:block flex-1" />

                <!-- Right Toolbar: Custom Actions, Filters Button & Per-Page Selector -->
                <div class="flex items-center gap-2 sm:gap-2.5 justify-between sm:justify-end flex-wrap sm:flex-nowrap">
                    <!-- Export Button -->
                    <AppButton
                        v-if="exportable"
                        type="export"
                        size="sm"
                        :label="exportLabel"
                        :icon="FileSpreadsheet"
                        @click="handleExport"
                    />

                    <slot name="actions" />

                    <!-- Filters Toggle Button (di samping kiri elemen 'Baris: x') -->
                    <AppButton
                        v-if="$slots.filters"
                        type="custom"
                        variant="outline"
                        size="sm"
                        :icon="ListFilter"
                        icon-position="right"
                        :class="[
                            isFilterOpen
                                ? 'bg-muted text-foreground ring-1 ring-border shadow-2xs'
                                : 'bg-card text-foreground shadow-2xs'
                        ]"
                        :aria-expanded="isFilterOpen"
                        aria-label="Toggle filter"
                        @click="isFilterOpen = !isFilterOpen"
                    >
                        <span>Filters</span>
                        <span
                            v-if="activeFilterCount > 0"
                            class="inline-flex items-center justify-center min-w-[16px] h-4 px-1 text-[10px] font-bold rounded-full bg-primary-600 text-white leading-none"
                        >
                            {{ activeFilterCount }}
                        </span>
                    </AppButton>

                    <!-- Per-Page Selector Card (menggunakan AppButton agar ukuran & radius selaras) -->
                    <div
                        v-if="showPerPage"
                        class="relative inline-flex items-center ml-auto sm:ml-0 group"
                    >
                        <AppButton
                            type="custom"
                            variant="outline"
                            size="sm"
                            :label="`Baris: ${perPage}`"
                            :icon="ChevronDown"
                            icon-position="right"
                            tabindex="-1"
                            class="pointer-events-none group-hover:bg-muted group-active:scale-[0.98] group-focus-within:ring-2 group-focus-within:ring-primary-500/50"
                        />
                        <select
                            :value="perPage"
                            @change="onPerPageChange"
                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                            aria-label="Pilih jumlah baris per halaman"
                        >
                            <option v-for="opt in perPageOptions" :key="opt" :value="opt">
                                Baris: {{ opt }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Collapsible Filters Panel -->
            <transition
                enter-active-class="transition-all duration-200 ease-out"
                enter-from-class="opacity-0 -translate-y-1"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition-all duration-150 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-1"
            >
                <div
                    v-if="$slots.filters && isFilterOpen"
                    class="mt-3 pt-3 border-t border-border flex flex-wrap items-center gap-2.5 sm:gap-3"
                >
                    <div class="flex items-center gap-1.5 text-xs font-semibold text-muted-foreground mr-1">
                        <ListFilter class="w-3.5 h-3.5" />
                        <span>Filter:</span>
                    </div>

                    <slot
                        name="filters"
                        :apply-filters="applyFilters"
                        :reset-filters="resetCustomFilters"
                        :is-open="isFilterOpen"
                    />

                    <!-- Reset Filter Button -->
                    <button
                        v-if="activeFilterCount > 0"
                        type="button"
                        @click="resetCustomFilters"
                        class="text-xs text-muted-foreground hover:text-destructive transition-colors ml-auto sm:ml-2 inline-flex items-center gap-1 cursor-pointer font-medium"
                        title="Reset semua filter kustom"
                    >
                        <X class="w-3 h-3" />
                        <span>Reset Filter</span>
                    </button>
                </div>
            </transition>
        </div>

        <!-- Batch Action Banner -->
        <transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0 -translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-1"
        >
            <div
                v-if="enableBatch && (selected?.length > 0)"
                class="flex flex-wrap items-center justify-between gap-3 p-3 bg-primary-50 dark:bg-primary-950/40 border border-primary-200 dark:border-primary-800/60 rounded-xl text-xs shadow-xs"
            >
                <div class="flex items-center gap-2.5">
                    <span class="inline-flex items-center justify-center bg-primary-600 text-white font-bold text-[11px] h-5 min-w-[20px] px-1.5 rounded-full shadow-2xs">
                        {{ selected.length }}
                    </span>
                    <span class="font-medium text-slate-800 dark:text-slate-200">
                        data terpilih
                    </span>
                    <button
                        type="button"
                        @click="clearSelection"
                        class="text-xs text-primary-600 dark:text-primary-400 hover:underline font-semibold ml-1 cursor-pointer"
                    >
                        Batal pilih
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <slot
                        name="batch-actions"
                        :selected="selected"
                        :selected-count="selected.length"
                        :clear-selection="clearSelection"
                    />
                </div>
            </div>
        </transition>

        <!-- Table Container -->
        <div class="bg-card rounded-xl border border-border shadow-xs overflow-hidden relative">
            <!-- Subtle Top Loading Bar -->
            <div
                v-if="isLoading"
                class="absolute top-0 left-0 right-0 h-0.5 bg-primary-100 dark:bg-primary-950 overflow-hidden z-20 pointer-events-none"
            >
                <div class="h-full bg-primary-600 animate-pulse w-full"></div>
            </div>

            <!-- Loading Overlay Indicator -->
            <transition
                enter-active-class="transition-opacity duration-150 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity duration-100 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="isLoading"
                    class="absolute inset-0 bg-background/40 flex items-center justify-center z-10 pointer-events-none"
                >
                    <div class="flex items-center gap-2 px-3.5 py-1.5 bg-card/95 rounded-xl border border-border shadow-xs text-xs font-semibold text-foreground">
                        <Loader2 class="w-3.5 h-3.5 animate-spin text-primary-600" />
                        <span>Memperbarui data...</span>
                    </div>
                </div>
            </transition>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <!-- Table Head -->
                    <thead class="bg-muted/50 border-b border-border text-muted-foreground font-semibold uppercase tracking-wider">
                        <tr>
                            <!-- Batch Select All Checkbox -->
                            <th v-if="enableBatch" class="w-10 px-3 py-3 text-center">
                                <AppCheckbox
                                    :checked="isAllSelected"
                                    :indeterminate="isIndeterminate"
                                    @change="toggleSelectAll"
                                    aria-label="Pilih semua baris pada halaman ini"
                                />
                            </th>
                            <th
                                v-for="col in columns"
                                :key="col.key"
                                class="py-3 px-4"
                                :class="[col.class, col.sortable ? 'cursor-pointer select-none hover:text-foreground' : '']"
                                @click="col.sortable ? handleSort(col) : null"
                            >
                                <div class="flex items-center gap-1.5" :class="getAlignmentClass(col.align)">
                                    <span>{{ col.label }}</span>
                                    <template v-if="col.sortable">
                                        <ArrowUp v-if="sortBy === col.key && sortDirection === 'asc'" class="w-3.5 h-3.5 text-primary-600 font-bold" />
                                        <ArrowDown v-else-if="sortBy === col.key && sortDirection === 'desc'" class="w-3.5 h-3.5 text-primary-600 font-bold" />
                                        <ArrowUpDown v-else class="w-3 h-3 text-muted-foreground/60" />
                                    </template>
                                </div>
                            </th>
                        </tr>
                    </thead>

                    <!-- Table Body -->
                    <tbody class="divide-y divide-border">
                        <template v-if="data?.data?.length > 0">
                            <tr
                                v-for="(item, index) in data.data"
                                :key="getItemId(item, index)"
                                :class="[
                                    enableBatch && isItemSelected(item, index) ? 'bg-primary-50/40 dark:bg-primary-950/20' : 'hover:bg-muted/30',
                                    'transition-colors'
                                ]"
                            >
                                <!-- Batch Row Checkbox -->
                                <td v-if="enableBatch" class="w-10 px-3 py-3 text-center">
                                    <AppCheckbox
                                        :checked="isItemSelected(item, index)"
                                        @change="toggleSelectItem(item, index)"
                                        :aria-label="`Pilih baris ${index + 1}`"
                                    />
                                </td>

                                <slot
                                    name="row"
                                    :item="item"
                                    :index="index"
                                    :is-selected="isItemSelected(item, index)"
                                    :toggle-select="() => toggleSelectItem(item, index)"
                                >
                                    <td
                                        v-for="col in columns"
                                        :key="col.key"
                                        class="py-3 px-4 text-foreground"
                                        :class="[col.class, getAlignmentClass(col.align)]"
                                    >
                                        <slot
                                            :name="`column-${col.key}`"
                                            :item="item"
                                            :value="item[col.key]"
                                            :index="index"
                                        >
                                            {{ item[col.key] }}
                                        </slot>
                                    </td>
                                </slot>
                            </tr>
                        </template>

                        <!-- Empty State -->
                        <tr v-else>
                            <td :colspan="columns.length + (enableBatch ? 1 : 0)" class="py-12 text-center text-muted-foreground">
                                <div class="max-w-xs mx-auto space-y-3">
                                    <div class="w-12 h-12 rounded-2xl bg-muted/80 flex items-center justify-center mx-auto text-muted-foreground border border-border">
                                        <Inbox class="w-6 h-6" />
                                    </div>
                                    <p class="font-bold text-sm text-foreground">
                                        {{ emptyTitle }}
                                    </p>
                                    <p class="text-xs leading-relaxed">
                                        {{ emptyMessage }}
                                    </p>
                                    <slot name="empty-action" />
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Table Pagination Bar -->
            <div
                v-if="data?.total > 0"
                class="px-4 py-3 bg-muted/30 border-t border-border flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-muted-foreground"
            >
                <div>
                    Menampilkan <strong class="font-semibold text-foreground">{{ data.from || 0 }}</strong> sampai
                    <strong class="font-semibold text-foreground">{{ data.to || 0 }}</strong> dari
                    <strong class="font-semibold text-foreground">{{ data.total || 0 }}</strong> data
                </div>

                <!-- Page Links -->
                <div v-if="data?.links?.length > 3" class="flex items-center gap-1">
                    <template v-for="(link, lIdx) in data.links" :key="lIdx">
                        <!-- Tombol Sebelumnya (First/Prev) -->
                        <button
                            v-if="lIdx === 0"
                            type="button"
                            :disabled="!link.url || isLoading"
                            @click="goToPage(link.url)"
                            class="p-1.5 rounded-lg border border-input bg-background hover:bg-muted disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                            aria-label="Halaman Sebelumnya"
                        >
                            <ChevronLeft class="w-4 h-4" />
                        </button>

                        <!-- Tombol Selanjutnya (Last/Next) -->
                        <button
                            v-else-if="lIdx === data.links.length - 1"
                            type="button"
                            :disabled="!link.url || isLoading"
                            @click="goToPage(link.url)"
                            class="p-1.5 rounded-lg border border-input bg-background hover:bg-muted disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                            aria-label="Halaman Selanjutnya"
                        >
                            <ChevronRight class="w-4 h-4" />
                        </button>

                        <!-- Nomor Halaman -->
                        <button
                            v-else
                            type="button"
                            :disabled="!link.url || link.active || isLoading"
                            @click="goToPage(link.url)"
                            :class="[
                                'min-w-[32px] h-8 px-2 rounded-lg font-medium text-xs transition-colors flex items-center justify-center',
                                link.active
                                    ? 'bg-primary-600 text-white font-bold shadow-xs'
                                    : 'border border-input bg-background hover:bg-muted text-foreground'
                            ]"
                            v-html="link.label"
                        ></button>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>

