<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import {
    Search,
    ArrowUpDown,
    ArrowUp,
    ArrowDown,
    ChevronLeft,
    ChevronRight,
    Inbox,
    Loader2
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
        required: true,
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
});

const searchKey = computed(() => (props.paramPrefix ? `${props.paramPrefix}search` : 'search'));
const sortByKey = computed(() => (props.paramPrefix ? `${props.paramPrefix}sort_by` : 'sort_by'));
const sortDirectionKey = computed(() => (props.paramPrefix ? `${props.paramPrefix}sort_direction` : 'sort_direction'));
const perPageKey = computed(() => (props.paramPrefix ? `${props.paramPrefix}per_page` : 'per_page'));
const pageKey = computed(() => (props.paramPrefix ? `${props.paramPrefix}page` : 'page'));

const search = ref(props.filters[searchKey.value] || props.filters.search || '');
const sortBy = ref(props.filters[sortByKey.value] || props.filters.sort_by || '');
const sortDirection = ref(props.filters[sortDirectionKey.value] || props.filters.sort_direction || 'desc');
const perPage = ref(props.filters[perPageKey.value] || props.filters.per_page || props.data?.per_page || 10);
const isLoading = ref(false);

let debounceTimeout = null;

const applyFilters = (customParams = {}) => {
    isLoading.value = true;

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

    router.get(props.fetchUrl, queryParams, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
            isLoading.value = false;
        },
    });
};

// Debounce search input
watch(search, () => {
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => {
        applyFilters({ [pageKey.value]: 1 });
    }, 350);
});

// Watch props.filters untuk sinkronisasi jika filter berubah dari luar
watch(
    () => props.filters,
    (newFilters) => {
        if (newFilters) {
            search.value = newFilters[searchKey.value] ?? newFilters.search ?? '';
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
    router.visit(url, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
            isLoading.value = false;
        },
    });
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
</script>

<template>
    <div class="space-y-4">
        <!-- Toolbar: Search Bar, Per-Page Selector, Custom Filters & Action Slot -->
        <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3 bg-card p-3 sm:p-4 rounded-xl border border-border shadow-xs">
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 sm:gap-3 flex-1">
                <!-- Search Input -->
                <div v-if="showSearch" class="relative w-full sm:max-w-xs md:max-w-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-muted-foreground">
                        <Loader2 v-if="isLoading" class="w-4 h-4 animate-spin text-primary-600" />
                        <Search v-else class="w-4 h-4" />
                    </div>
                    <input
                        v-model="search"
                        type="text"
                        :placeholder="searchPlaceholder"
                        class="w-full pl-9 pr-3 py-1.5 text-xs bg-background border border-input rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all placeholder:text-muted-foreground"
                    />
                </div>

                <!-- Custom Filter Slot -->
                <slot name="filters" :apply-filters="applyFilters" />
            </div>

            <!-- Right Toolbar: Custom Actions & Per-Page Selector -->
            <div class="flex items-center gap-3 justify-between sm:justify-end">
                <slot name="actions" />

                <!-- Per-Page Selector -->
                <div v-if="showPerPage" class="flex items-center gap-1.5 text-xs text-muted-foreground whitespace-nowrap ml-auto sm:ml-0">
                    <span>Baris:</span>
                    <select
                        :value="perPage"
                        @change="onPerPageChange"
                        class="px-2 py-1 bg-background border border-input rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-primary-500 font-medium"
                    >
                        <option v-for="opt in perPageOptions" :key="opt" :value="opt">
                            {{ opt }}
                        </option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Table Container -->
        <div class="bg-card rounded-xl border border-border shadow-xs overflow-hidden relative">
            <!-- Loading Overlay Indicator -->
            <div
                v-if="isLoading"
                class="absolute inset-0 bg-background/50 backdrop-blur-2xs flex items-center justify-center z-10"
            >
                <div class="flex items-center gap-2 px-4 py-2 bg-card rounded-xl border border-border shadow-md text-xs font-semibold text-foreground">
                    <Loader2 class="w-4 h-4 animate-spin text-primary-600" />
                    <span>Memperbarui data...</span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <!-- Table Head -->
                    <thead class="bg-muted/50 border-b border-border text-muted-foreground font-semibold uppercase tracking-wider">
                        <tr>
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
                                :key="item.id || item.invitation_id || index"
                                class="hover:bg-muted/30 transition-colors"
                            >
                                <slot name="row" :item="item" :index="index">
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
                            <td :colspan="columns.length" class="py-12 text-center text-muted-foreground">
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

