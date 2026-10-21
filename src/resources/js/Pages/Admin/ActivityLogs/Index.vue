<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AppDataTable from '@/Components/Common/AppDataTable.vue';
import AppModal from '@/Components/Common/AppModal.vue';
import { Button } from '@/Components/ui/button';
import AppTableAction from '@/Components/Common/AppTableAction.vue';
import AppButton from '@/Components/Common/AppButton.vue';
import {
    Download,
    Eye,
    Laptop,
    Smartphone,
    Globe,
    User,
    Shield,
    CheckCircle2,
    Clock,
    FileSpreadsheet,
    Code,
    SlidersHorizontal,
} from 'lucide-vue-next';

const props = defineProps({
    logs: {
        type: Object,
        required: true,
    },
    stats: {
        type: Object,
        default: () => ({
            total_logs: 0,
            today_logs: 0,
            critical_logs: 0,
            security_alerts: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    kategoriOptions: {
        type: Object,
        default: () => ({}),
    },
    aksiOptions: {
        type: Object,
        default: () => ({}),
    },
    roleOptions: {
        type: Object,
        default: () => ({}),
    },
    tingkatOptions: {
        type: Object,
        default: () => ({}),
    },
});

// Kolom Tabel AppDataTable
const columns = [
    { key: 'waktu', label: 'Waktu & Perangkat', sortable: false },
    { key: 'user', label: 'Pengguna', sortable: false },
    { key: 'kategori', label: 'Kategori & Aksi', sortable: false },
    { key: 'deskripsi', label: 'Aktivitas', sortable: false },
    { key: 'tingkat', label: 'Tingkat', sortable: false, align: 'center' },
    { key: 'actions', label: 'Rincian', align: 'center' },
];

// State Modal Detail Perubahan
const isDetailModalOpen = ref(false);
const selectedLog = ref(null);
const showRawJson = ref(false);

const openDetailModal = (log) => {
    selectedLog.value = log;
    showRawJson.value = false;
    isDetailModalOpen.value = true;
};

const closeDetailModal = () => {
    isDetailModalOpen.value = false;
    selectedLog.value = null;
};

// Ekspor XLSX (Excel)
const exportXlsx = () => {
    const params = new URLSearchParams();
    if (props.filters.search) params.append('search', props.filters.search);
    if (props.filters.kategori) params.append('kategori', props.filters.kategori);
    if (props.filters.user_role) params.append('user_role', props.filters.user_role);
    if (props.filters.tingkat) params.append('tingkat', props.filters.tingkat);
    if (props.filters.start_date) params.append('start_date', props.filters.start_date);
    if (props.filters.end_date) params.append('end_date', props.filters.end_date);

    window.location.href = `/admin/activity-logs/export?${params.toString()}`;
};

// Helper Gaya & Label Peran Pengguna (Investor: Biru, Affiliator: Ungu)
const getRoleBadgeClass = (role) => {
    const normalized = (role || '').toLowerCase();
    if (normalized === 'investor') {
        return 'border border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-800 dark:bg-blue-950/50 dark:text-blue-300';
    }
    if (normalized === 'affiliator') {
        return 'border border-purple-200 bg-purple-50 text-purple-700 dark:border-purple-800 dark:bg-purple-950/50 dark:text-purple-300';
    }
    if (normalized === 'administrator' || normalized === 'admin') {
        return 'border border-slate-200 bg-slate-100 text-slate-800 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200';
    }
    return 'border border-border bg-muted text-muted-foreground';
};

const getRoleLabel = (role) => {
    const normalized = (role || '').toLowerCase();
    if (normalized === 'investor') return 'INVESTOR';
    if (normalized === 'affiliator') return 'AFFILIATOR';
    if (normalized === 'administrator' || normalized === 'admin') return 'ADMINISTRATOR';
    return role ? role.toUpperCase() : 'GUEST';
};

// Helper Format Nilai untuk Modal Perubahan
const formatValue = (val) => {
    if (val === null || val === undefined) return '-';
    if (typeof val === 'boolean') return val ? 'true' : 'false';
    if (typeof val === 'object') return JSON.stringify(val);
    return String(val);
};

// Komparasi Field Sebelum vs Sesudah
const comparedFields = computed(() => {
    if (!selectedLog.value?.perubahan || typeof selectedLog.value.perubahan !== 'object') {
        return [];
    }

    const p = selectedLog.value.perubahan;

    // Normalisasi kunci sebelum & sesudah dari berbagai konvensi (sebelum, data_sebelum, old, before)
    const rawSebelum =
        p.sebelum !== undefined
            ? p.sebelum
            : p.data_sebelum !== undefined
              ? p.data_sebelum
              : p.old !== undefined
                ? p.old
                : p.before !== undefined
                  ? p.before
                  : undefined;

    const rawSesudah =
        p.sesudah !== undefined
            ? p.sesudah
            : p.data_sesudah !== undefined
              ? p.data_sesudah
              : p.new !== undefined
                ? p.new
                : p.after !== undefined
                  ? p.after
                  : undefined;

    const hasSebelum = rawSebelum !== undefined && rawSebelum !== null;
    const hasSesudah = rawSesudah !== undefined && rawSesudah !== null;

    // Kasus 1: Terdapat blok 'sebelum' dan/atau 'sesudah'
    if (hasSebelum || hasSesudah) {
        const sebelumObj =
            hasSebelum && typeof rawSebelum === 'object' && !Array.isArray(rawSebelum)
                ? rawSebelum
                : null;
        const sesudahObj =
            hasSesudah && typeof rawSesudah === 'object' && !Array.isArray(rawSesudah)
                ? rawSesudah
                : null;

        if (sebelumObj || sesudahObj) {
            const allKeys = Array.from(
                new Set([
                    ...(sebelumObj ? Object.keys(sebelumObj) : []),
                    ...(sesudahObj ? Object.keys(sesudahObj) : []),
                ])
            );

            return allKeys.map((key) => {
                const oldVal = sebelumObj && key in sebelumObj ? sebelumObj[key] : undefined;
                const newVal = sesudahObj && key in sesudahObj ? sesudahObj[key] : undefined;
                const isModified = formatValue(oldVal) !== formatValue(newVal);

                return {
                    key,
                    oldVal,
                    newVal,
                    isModified,
                };
            });
        }

        // Jika salah satunya data primitif atau array
        return [
            {
                key: 'data',
                oldVal: rawSebelum,
                newVal: rawSesudah,
                isModified: formatValue(rawSebelum) !== formatValue(rawSesudah),
            },
        ];
    }

    // Kasus 2: Flat payload tanpa pembungkus sebelum/sesudah (misal catatan meta informasi)
    return Object.keys(p).map((k) => ({
        key: k,
        oldVal: undefined,
        newVal: p[k],
        isModified: true,
    }));
});
</script>

<template>
    <AdminLayout
        title="Log Aktivitas Sistem"
        subtitle="Rekam jejak audit keamanan dan seluruh perubahan data platform YZD Center."
        :breadcrumbs="[{ label: 'Pengaturan Sistem', href: '#' }, { label: 'Log Aktivitas' }]"
    >
        <Head title="Log Aktivitas Sistem - Admin" />

        <template #header-actions>
            <AppButton
                type="export"
                label="Ekspor XLSX"
                :icon="FileSpreadsheet"
                @click="exportXlsx"
                title="Unduh seluruh data sesuai filter dalam format Excel (.xlsx)"
            />
        </template>

        <div class="space-y-6">
            <!-- Tabel Log Aktivitas via AppDataTable -->
            <AppDataTable
                :data="logs"
                :columns="columns"
                :filters="filters"
                fetch-url="/admin/activity-logs"
                search-placeholder="Cari deskripsi, pengguna, IP..."
                empty-title="Tidak Ada Jejak Log Ditemukan"
                empty-message="Belum ada aktivitas tercatat atau sesuaikan kriteria filter pencarian Anda."
            >
                <!-- Slot Filter Kustom -->
                <template #filters="{ applyFilters, resetFilters }">
                    <!-- Dropdown Kategori Modul -->
                    <select
                        :value="filters.kategori || ''"
                        @change="
                            (e) => applyFilters({ kategori: e.target.value || undefined, page: 1 })
                        "
                        class="bg-background border-input focus:ring-primary-500 min-h-[38px] w-full rounded-lg border px-3 py-1.5 text-xs font-medium focus:ring-2 focus:outline-none sm:w-auto"
                    >
                        <option value="">Semua Kategori</option>
                        <option v-for="(label, key) in kategoriOptions" :key="key" :value="key">
                            {{ label }}
                        </option>
                    </select>

                    <!-- Dropdown Peran Pengguna -->
                    <select
                        :value="filters.user_role || ''"
                        @change="
                            (e) => applyFilters({ user_role: e.target.value || undefined, page: 1 })
                        "
                        class="bg-background border-input focus:ring-primary-500 min-h-[38px] w-full rounded-lg border px-3 py-1.5 text-xs font-medium focus:ring-2 focus:outline-none sm:w-auto"
                    >
                        <option value="">Semua Peran</option>
                        <option v-for="(label, key) in roleOptions" :key="key" :value="key">
                            {{ label }}
                        </option>
                    </select>

                    <!-- Dropdown Tingkat Risiko -->
                    <select
                        :value="filters.tingkat || ''"
                        @change="
                            (e) => applyFilters({ tingkat: e.target.value || undefined, page: 1 })
                        "
                        class="bg-background border-input focus:ring-primary-500 min-h-[38px] w-full rounded-lg border px-3 py-1.5 text-xs font-medium focus:ring-2 focus:outline-none sm:w-auto"
                    >
                        <option value="">Semua Tingkat</option>
                        <option value="info">Info</option>
                        <option value="warning">Warning</option>
                        <option value="critical">Critical</option>
                    </select>

                    <!-- Filter Tanggal Mulai -->
                    <div class="flex w-full items-center gap-1.5 sm:w-auto">
                        <span class="text-muted-foreground text-[11px] whitespace-nowrap"
                            >Dari:</span
                        >
                        <input
                            type="date"
                            :value="filters.start_date || ''"
                            @change="
                                (e) =>
                                    applyFilters({
                                        start_date: e.target.value || undefined,
                                        page: 1,
                                    })
                            "
                            class="bg-background border-input focus:ring-primary-500 min-h-[38px] w-full rounded-lg border px-2.5 py-1.5 text-xs font-medium focus:ring-2 focus:outline-none sm:w-auto"
                        />
                    </div>

                    <!-- Filter Tanggal Akhir -->
                    <div class="flex w-full items-center gap-1.5 sm:w-auto">
                        <span class="text-muted-foreground text-[11px] whitespace-nowrap"
                            >Sampai:</span
                        >
                        <input
                            type="date"
                            :value="filters.end_date || ''"
                            @change="
                                (e) =>
                                    applyFilters({ end_date: e.target.value || undefined, page: 1 })
                            "
                            class="bg-background border-input focus:ring-primary-500 min-h-[38px] w-full rounded-lg border px-2.5 py-1.5 text-xs font-medium focus:ring-2 focus:outline-none sm:w-auto"
                        />
                    </div>
                </template>

                <!-- Kolom 1: Waktu & Perangkat -->
                <template #column-waktu="{ item }">
                    <div class="space-y-1">
                        <div class="flex items-center gap-1.5">
                            <Clock class="text-muted-foreground h-3.5 w-3.5 shrink-0" />
                            <span class="text-foreground text-xs font-semibold">
                                {{ item.waktu_relatif }}
                            </span>
                        </div>
                        <div class="text-muted-foreground font-mono text-[11px]">
                            {{ item.waktu_lengkap }}
                        </div>
                        <div
                            class="text-muted-foreground flex items-center gap-1.5 text-[11px] text-nowrap"
                        >
                            <Globe class="text-muted-foreground h-3 w-3 shrink-0" />
                            <span class="font-mono">{{ item.ip_address || '-' }}</span>
                            <span class="text-muted-foreground/60">•</span>
                            <span>{{ item.perangkat_ringkas }}</span>
                        </div>
                    </div>
                </template>

                <!-- Kolom 2: Pengguna / Pelaku -->
                <template #column-user="{ item }">
                    <div class="flex items-start gap-2.5">

                        <div class="min-w-0">
                            <div class="text-foreground truncate text-xs font-bold">
                                {{ item.user_name || 'Tamu / Sistem' }}
                            </div>
                            <div class="text-muted-foreground truncate font-mono text-[11px]">
                                {{ item.user_username ? `@${item.user_username}` : 'guest' }}
                            </div>
                            <span
                                :class="[
                                    'py-0.2 mt-0.5 inline-block rounded-full px-1.5 text-[10px] font-semibold tracking-wider uppercase',
                                    getRoleBadgeClass(item.user_role),
                                ]"
                            >
                                {{ getRoleLabel(item.user_role) }}
                            </span>
                        </div>
                    </div>
                </template>

                <!-- Kolom 3: Kategori & Aksi -->
                <template #column-kategori="{ item }">
                    <div class="space-y-1">
                        <span
                            class="bg-muted text-foreground border-border inline-flex items-center rounded-md border px-2 py-0.5 text-[11px] font-semibold"
                        >
                            {{ item.kategori_label }}
                        </span>
                        <div class="text-foreground text-xs font-medium">
                            {{ item.aksi_label }}
                        </div>
                    </div>
                </template>

                <!-- Kolom 4: Deskripsi & Subjek -->
                <template #column-deskripsi="{ item }">
                    <div class="max-w-md space-y-1">
                        <p class="text-foreground text-xs leading-relaxed">
                            {{ item.deskripsi }}
                        </p>
                    </div>
                </template>

                <!-- Kolom 5: Tingkat Risiko -->
                <template #column-tingkat="{ item }">
                    <span
                        :class="[
                            'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-bold tracking-wider uppercase',
                            item.tingkat === 'critical'
                                ? 'border border-rose-200 bg-rose-100 text-rose-800 dark:border-rose-800 dark:bg-rose-950/60 dark:text-rose-200'
                                : item.tingkat === 'warning'
                                  ? 'border border-amber-200 bg-amber-100 text-amber-800 dark:border-amber-800 dark:bg-amber-950/60 dark:text-amber-200'
                                  : 'border border-slate-200 bg-slate-100 text-slate-800 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-200',
                        ]"
                    >
                        <span
                            :class="[
                                'h-1.5 w-1.5 rounded-full',
                                item.tingkat === 'critical'
                                    ? 'bg-rose-500'
                                    : item.tingkat === 'warning'
                                      ? 'bg-amber-500'
                                      : 'bg-slate-500',
                            ]"
                        ></span>
                        {{ item.tingkat }}
                    </span>
                </template>

                <!-- Kolom 6: Aksi / Rincian -->
                <template #column-actions="{ item }">
                    <AppTableAction
                        type="detail"
                        title="Lihat Detail Log"
                        @click="openDetailModal(item)"
                    />
                </template>
            </AppDataTable>
        </div>

        <!-- Modal Detail Audit Log & Inspeksi Perubahan -->
        <AppModal
            :show="isDetailModalOpen"
            :title="`Rincian Audit Log #${selectedLog?.id || ''}`"
            max-width="3xl"
            @close="closeDetailModal"
        >
            <div v-if="selectedLog" class="space-y-5">
                <!-- Info Grid Pelaku & Waktu -->
                <div
                    class="bg-muted/40 border-border grid grid-cols-1 gap-3.5 rounded-xl border p-3.5 text-xs sm:grid-cols-2"
                >
                    <div>
                        <span class="text-muted-foreground mb-0.5 block font-semibold"
                            >Pelaku / Pengguna</span
                        >
                        <div class="flex items-center gap-2">
                            <span class="text-foreground font-bold">
                                {{ selectedLog.user_name || 'Tamu / Sistem' }}
                            </span>
                            <span
                                v-if="selectedLog.user_username"
                                class="text-muted-foreground font-mono"
                            >
                                (@{{ selectedLog.user_username }})
                            </span>
                            <span
                                :class="[
                                    'py-0.2 rounded-full border px-1.5 text-[10px] font-semibold tracking-wider uppercase',
                                    getRoleBadgeClass(selectedLog.user_role),
                                ]"
                            >
                                {{ getRoleLabel(selectedLog.user_role) }}
                            </span>
                        </div>
                    </div>

                    <div>
                        <span class="text-muted-foreground mb-0.5 block font-semibold"
                            >Waktu Kejadian</span
                        >
                        <div class="text-foreground font-mono">
                            {{ selectedLog.waktu_lengkap }} ({{ selectedLog.waktu_relatif }})
                        </div>
                    </div>

                    <div>
                        <span class="text-muted-foreground mb-0.5 block font-semibold"
                            >Kategori & Aksi</span
                        >
                        <div class="flex items-center gap-1.5">
                            <span class="text-foreground font-bold">{{
                                selectedLog.kategori_label
                            }}</span>
                            <span class="text-muted-foreground">•</span>
                            <span class="text-foreground">{{ selectedLog.aksi_label }}</span>
                        </div>
                    </div>

                    <div>
                        <span class="text-muted-foreground mb-0.5 block font-semibold"
                            >Jaringan & Perangkat</span
                        >
                        <div class="text-foreground font-mono">
                            IP: {{ selectedLog.ip_address || '-' }}
                            <span class="text-muted-foreground"
                                >({{ selectedLog.perangkat_ringkas }})</span
                            >
                        </div>
                    </div>
                </div>

                <!-- Deskripsi Aktivitas -->
                <div class="space-y-1">
                    <span class="text-muted-foreground text-xs font-semibold"
                        >Deskripsi Aktivitas</span
                    >
                    <div
                        class="bg-card border-border text-foreground rounded-lg border p-3 text-xs leading-relaxed font-medium"
                    >
                        {{ selectedLog.deskripsi }}
                    </div>
                </div>

                <!-- Objek Terkait -->
                <div v-if="selectedLog.subject_type" class="flex items-center gap-2 text-xs">
                    <span class="text-muted-foreground font-semibold">Objek Target:</span>
                    <span
                        class="bg-muted border-border text-foreground rounded border px-2 py-0.5 font-mono font-medium"
                    >
                        {{ selectedLog.subject_type }}
                        <span v-if="selectedLog.subject_id">#{{ selectedLog.subject_id }}</span>
                    </span>
                </div>

                <!-- Komparasi Perubahan Data (Sebelum vs Sesudah) -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-foreground text-xs font-bold">
                            Rekam Data Perubahan (Audit Diff)
                        </span>

                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            @click="showRawJson = !showRawJson"
                            class="text-muted-foreground hover:text-foreground inline-flex h-7 items-center gap-1 px-2 text-[11px]"
                        >
                            <Code class="h-3 w-3" />
                            <span>{{ showRawJson ? 'Tutup JSON' : 'Lihat JSON Mentah' }}</span>
                        </Button>
                    </div>

                    <!-- JSON Mentah -->
                    <div v-if="showRawJson" class="bg-muted border-border rounded-lg border p-3">
                        <pre
                            class="text-foreground max-h-60 overflow-x-auto font-mono text-[11px] leading-relaxed whitespace-pre-wrap"
                            >{{ JSON.stringify(selectedLog.perubahan, null, 2) }}</pre>
                    </div>

                    <!-- Tabel Perubahan Terstruktur -->
                    <div
                        v-if="comparedFields.length > 0"
                        class="border-border overflow-hidden rounded-xl border shadow-2xs"
                    >
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead
                                    class="bg-muted/60 border-border text-muted-foreground border-b text-[11px] font-semibold tracking-wider uppercase"
                                >
                                    <tr>
                                        <th class="px-3 py-2.5">Field / Kolom</th>
                                        <th class="px-3 py-2.5">Data Sebelum</th>
                                        <th class="px-3 py-2.5">Data Sesudah</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-border divide-y font-mono text-[11px]">
                                    <tr
                                        v-for="field in comparedFields"
                                        :key="field.key"
                                        :class="[
                                            field.isModified ? 'bg-amber-500/5' : '',
                                            'hover:bg-muted/20 transition-colors',
                                        ]"
                                    >
                                        <td
                                            class="text-foreground px-3 py-2.5 align-top font-semibold"
                                        >
                                            {{ field.key }}
                                        </td>
                                        <td class="px-3 py-2.5 align-top">
                                            <span
                                                v-if="
                                                    field.oldVal !== undefined &&
                                                    field.oldVal !== null
                                                "
                                                :class="[
                                                    'inline-block rounded px-2 py-0.5 break-all',
                                                    field.isModified
                                                        ? 'border border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900 dark:bg-rose-950/50 dark:text-rose-300'
                                                        : 'text-muted-foreground',
                                                ]"
                                            >
                                                {{ formatValue(field.oldVal) }}
                                            </span>
                                            <span v-else class="text-muted-foreground/60">-</span>
                                        </td>
                                        <td class="px-3 py-2.5 align-top">
                                            <span
                                                v-if="
                                                    field.newVal !== undefined &&
                                                    field.newVal !== null
                                                "
                                                :class="[
                                                    'inline-block rounded px-2 py-0.5 break-all',
                                                    field.isModified
                                                        ? 'border border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-300'
                                                        : 'text-foreground',
                                                ]"
                                            >
                                                {{ formatValue(field.newVal) }}
                                            </span>
                                            <span v-else class="text-muted-foreground/60">-</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Keadaan Tanpa Data Perubahan Spesifik -->
                    <div
                        v-else
                        class="bg-muted/20 border-border text-muted-foreground rounded-xl border border-dashed p-4 text-center text-xs"
                    >
                        Tidak ada rincian perubahan atribut spesifik yang tercatat untuk aktivitas
                        ini.
                    </div>
                </div>

                <!-- Footer Informasi Integritas -->
                <div class="border-border flex justify-end border-t pt-2">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="closeDetailModal"
                        class="text-xs"
                    >
                        Tutup
                    </Button>
                </div>
            </div>
        </AppModal>
    </AdminLayout>
</template>
