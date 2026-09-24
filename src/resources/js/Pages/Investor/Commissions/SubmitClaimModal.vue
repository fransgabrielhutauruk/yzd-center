<script setup>
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppModal from '@/Components/Common/AppModal.vue';
import { Button } from '@/Components/ui/button';
import {
    Banknote,
    User,
    CheckCircle2,
    AlertCircle,
    Info,
    CreditCard,
    DollarSign,
    Loader2,
    Search,
    Calendar,
    Layers,
    Clock,
    Lock,
    CheckSquare,
    Square,
} from 'lucide-vue-next';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    isOpen: {
        type: Boolean,
        default: false,
    },
    eligibleDownlines: {
        type: Array,
        default: () => [],
    },
    defaultBank: {
        type: Object,
        default: () => ({
            nama_bank: '',
            nomor_rekening: '',
            atas_nama_rekening: '',
        }),
    },
});

const isVisible = computed(() => props.show || props.isOpen);

const emit = defineEmits(['close']);

const searchQuery = ref('');

// Array of selected downline IDs: [1, 2, ...]
const selectedDownlineIds = ref([]);

const form = useForm({
    downline_ids: [],
    nama_bank: '',
    nomor_rekening: '',
    atas_nama_rekening: '',
});

// Watch defaultBank prop to populate bank details
watch(
    () => props.defaultBank,
    (bank) => {
        if (bank) {
            form.nama_bank = form.nama_bank || bank.nama_bank || '';
            form.nomor_rekening = form.nomor_rekening || bank.nomor_rekening || '';
            form.atas_nama_rekening = form.atas_nama_rekening || bank.atas_nama_rekening || '';
        }
    },
    { immediate: true, deep: true }
);

// Reset state when modal closes
watch(
    () => isVisible.value,
    (open) => {
        if (!open) {
            selectedDownlineIds.value = [];
            searchQuery.value = '';
            form.clearErrors();
        }
    }
);

const formatUSD = (val) => {
    if (val === null || val === undefined || isNaN(val)) return '$0.00';
    return '$' + Number(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const formatRupiah = (val) => {
    if (val === null || val === undefined || isNaN(val)) return 'Rp 0';
    return 'Rp ' + Number(val).toLocaleString('id-ID');
};

const filteredDownlines = computed(() => {
    if (!props.eligibleDownlines || !Array.isArray(props.eligibleDownlines)) {
        return [];
    }

    if (!searchQuery.value.trim()) {
        return props.eligibleDownlines;
    }

    const q = searchQuery.value.toLowerCase().trim();
    return props.eligibleDownlines.filter((d) => {
        return (
            d.name?.toLowerCase().includes(q) ||
            d.username?.toLowerCase().includes(q) ||
            d.email?.toLowerCase().includes(q) ||
            d.phone?.includes(q)
        );
    });
});

const eligibleDownlinesList = computed(() => {
    return (props.eligibleDownlines || []).filter((d) => d.is_eligible);
});

const isAllEligibleSelected = computed(() => {
    if (eligibleDownlinesList.value.length === 0) return false;
    return eligibleDownlinesList.value.every((d) => selectedDownlineIds.value.includes(d.id));
});

const toggleSelectAll = () => {
    if (isAllEligibleSelected.value) {
        selectedDownlineIds.value = [];
    } else {
        selectedDownlineIds.value = eligibleDownlinesList.value.map((d) => d.id);
    }
};

const toggleDownline = (downlineId) => {
    const idx = selectedDownlineIds.value.indexOf(downlineId);
    if (idx >= 0) {
        selectedDownlineIds.value.splice(idx, 1);
    } else {
        selectedDownlineIds.value.push(downlineId);
    }
};

const isDownlineSelected = (downlineId) => {
    return selectedDownlineIds.value.includes(downlineId);
};

// Hitung total akun copytrade dan modal dari seluruh downline yang dipilih
const selectedSummary = computed(() => {
    const selected = (props.eligibleDownlines || []).filter((d) => selectedDownlineIds.value.includes(d.id));
    const totalAkun = selected.reduce((acc, d) => acc + (d.accounts?.length || 0), 0);
    const totalModal = selected.reduce((acc, d) => acc + (d.total_modal_riil || 0), 0);

    return {
        totalDownline: selected.length,
        totalAkun,
        totalModal,
    };
});

const submit = () => {
    if (selectedDownlineIds.value.length === 0) return;

    form.downline_ids = selectedDownlineIds.value;

    form.post(route('investor.commissions.store'), {
        preserveScroll: true,
        onSuccess: () => {
            emit('close');
            selectedDownlineIds.value = [];
            form.reset();
        },
    });
};
</script>

<template>
    <AppModal
        :show="isVisible"
        title="Ajukan Klaim Komisi Afiliasi (Periode 90 Hari)"
        max-width="3xl"
        @close="$emit('close')"
    >
        <form @submit.prevent="submit" class="space-y-4 text-xs">
            <!-- Banner Penjelasan Aturan 90 Hari & Multi-Akun -->
            <div class="rounded-xl border border-sky-500/30 bg-sky-500/10 p-3 sm:p-3.5 space-y-1.5">
                <div class="flex items-center gap-2 text-sky-900 dark:text-sky-300 font-bold text-xs">
                    <Info class="h-4 w-4 shrink-0 text-sky-600 dark:text-sky-400" />
                    <span>Ketentuan Perhitungan Klaim Komisi Afiliasi (PB 3)</span>
                </div>
                <ul class="text-[11px] text-muted-foreground list-disc list-inside space-y-0.5 leading-relaxed">
                    <li>Klaim komisi per investor downline diajukan <strong>1x saja</strong> untuk masa aktif penuh <strong>90 hari (3 bulan)</strong> sejak tanggal mulai copytrade pertama.</li>
                    <li>Jika investor memiliki akun copytrade kedua atau tambahan di tengah jalan, durasi yang dihitung adalah <strong>hari mulai copytrade tambahan dikurangi hari copytrade pertama</strong> (sisa hari menuju akhir 90 hari acuan).</li>
                    <li>Anda tidak perlu menginput modal deposit atau screenshot. Nominal modal dan tier komisi ditarik otomatis dari sistem.</li>
                    <li>Satu pengajuan klaim mencakup seluruh downline terpilih sebagai <strong>1 tiket klaim komisi</strong>.</li>
                </ul>
            </div>

            <!-- Toolbar Pencarian & Pilih Semua -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2.5">
                <div class="relative flex-1">
                    <Search class="h-3.5 w-3.5 text-muted-foreground absolute left-3 top-1/2 -translate-y-1/2" />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari nama, username, email investor..."
                        class="bg-background border-input focus:ring-primary-500 h-9 w-full rounded-lg border pl-8 pr-3 text-xs focus:ring-2 focus:outline-none"
                    />
                </div>

                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    @click="toggleSelectAll"
                    :disabled="eligibleDownlinesList.length === 0"
                    class="h-9 shrink-0 text-xs"
                >
                    <component :is="isAllEligibleSelected ? CheckSquare : Square" class="h-3.5 w-3.5 mr-1.5 text-primary-600" />
                    <span>{{ isAllEligibleSelected ? 'Batalkan Semua' : 'Pilih Semua yang Siap' }}</span>
                </Button>
            </div>

            <div v-if="form.errors.downline_ids" class="rounded-lg border border-destructive/30 bg-destructive/10 p-2.5 text-destructive text-xs flex items-center gap-2">
                <AlertCircle class="h-4 w-4 shrink-0" />
                <span>{{ form.errors.downline_ids }}</span>
            </div>

            <!-- Daftar Kartu Downline -->
            <div class="space-y-3.5 max-h-[50vh] overflow-y-auto pr-1">
                <div
                    v-if="filteredDownlines.length === 0"
                    class="rounded-xl border border-dashed border-border p-8 text-center text-muted-foreground space-y-1"
                >
                    <User class="h-8 w-8 mx-auto opacity-50 mb-1" />
                    <p class="font-semibold text-xs">Tidak ada data investor downline yang ditemukan.</p>
                    <p class="text-[11px]">Pastikan investor binaan Anda telah memiliki akun copytrade yang aktif.</p>
                </div>

                <div
                    v-for="downline in filteredDownlines"
                    :key="downline.id"
                    :class="[
                        'rounded-xl border p-3.5 space-y-3 transition-all',
                        isDownlineSelected(downline.id)
                            ? 'border-primary-500 bg-primary-500/5 ring-1 ring-primary-500/30'
                            : 'border-border bg-card hover:border-border/80',
                    ]"
                >
                    <!-- Header Kartu: Identitas, Status, & Checkbox -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-border/60 pb-2.5">
                        <div class="flex items-center gap-2.5">
                            <!-- Checkbox Pemilihan -->
                            <button
                                type="button"
                                @click="downline.is_eligible && toggleDownline(downline.id)"
                                :disabled="!downline.is_eligible"
                                class="shrink-0 transition-opacity"
                                :class="downline.is_eligible ? 'cursor-pointer' : 'opacity-40 cursor-not-allowed'"
                            >
                                <component
                                    :is="isDownlineSelected(downline.id) ? CheckSquare : Square"
                                    :class="[
                                        'h-5 w-5',
                                        isDownlineSelected(downline.id) ? 'text-primary-600 dark:text-primary-400' : 'text-muted-foreground',
                                    ]"
                                />
                            </button>

                            <div class="space-y-0.5">
                                <div class="flex items-center gap-2">
                                    <span class="text-foreground text-xs font-bold">{{ downline.name }}</span>
                                    <span class="text-muted-foreground font-mono text-[11px]">@{{ downline.username }}</span>
                                </div>
                                <p class="text-muted-foreground text-[10px]">
                                    WhatsApp: {{ downline.phone || '-' }} | Email: {{ downline.email }}
                                </p>
                            </div>
                        </div>

                        <!-- Status & Tier Badge -->
                        <div class="flex flex-wrap items-center gap-1.5 self-start sm:self-auto">
                            <!-- Status Kelayakan -->
                            <span
                                :class="[
                                    'inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-[10px] font-bold',
                                    downline.is_eligible
                                        ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-800 dark:text-emerald-300'
                                        : downline.is_claimed
                                        ? 'border-slate-500/30 bg-slate-500/10 text-slate-700 dark:text-slate-300'
                                        : downline.is_pending
                                        ? 'border-amber-500/30 bg-amber-500/10 text-amber-800 dark:text-amber-300'
                                        : 'border-muted-foreground/30 bg-muted text-muted-foreground',
                                ]"
                            >
                                <CheckCircle2 v-if="downline.is_eligible" class="h-3 w-3" />
                                <Clock v-else-if="downline.is_pending" class="h-3 w-3" />
                                <Lock v-else class="h-3 w-3" />
                                <span>{{ downline.status_label }}</span>
                            </span>

                            <!-- Tier Komisi Badge -->
                            <span
                                v-if="downline.total_modal_riil > 0"
                                class="rounded-full bg-primary-500/10 border border-primary-500/30 px-2 py-0.5 text-[10px] font-bold text-primary-800 dark:text-primary-300"
                            >
                                Tier {{ downline.tier_info?.tier_level }} ({{ downline.tier_info?.persentase_tier }}%)
                            </span>
                        </div>
                    </div>

                    <!-- Ringkasan Acuan Periode 90 Hari -->
                    <div class="rounded-lg bg-muted/40 p-2.5 border border-border/40 text-[11px] space-y-1">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 text-muted-foreground">
                            <span>
                                Acuan Copytrade Pertama: <strong class="text-foreground font-medium">{{ downline.first_started_at || 'Belum mulai' }}</strong>
                            </span>
                            <span v-if="downline.period_end_date">
                                Periode 90 Hari: <strong class="text-foreground font-medium">{{ downline.first_started_at }} s/d {{ downline.period_end_date }}</strong>
                            </span>
                            <span>
                                Masa Aktif: <strong class="text-emerald-700 dark:text-emerald-400 font-bold">{{ downline.active_days }} Hari</strong>
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-[11px] pt-1 border-t border-border/40">
                            <span class="text-muted-foreground">Total Akumulasi Modal Riil Investor:</span>
                            <span class="font-mono font-bold text-foreground text-xs">{{ formatUSD(downline.total_modal_riil) }}</span>
                        </div>
                    </div>

                    <!-- Rincian Seluruh Akun Copytrade & Perhitungan Hari Kehitung -->
                    <div v-if="downline.accounts?.length" class="space-y-1.5 pt-1">
                        <div class="flex items-center justify-between text-[11px] font-semibold text-muted-foreground">
                            <span>Rincian Akun Copytrade ({{ downline.accounts.length }} Akun):</span>
                        </div>

                        <div class="space-y-1.5">
                            <div
                                v-for="(acc, accIdx) in downline.accounts"
                                :key="accIdx"
                                class="rounded-lg bg-background border border-border/60 p-2.5 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-[11px]"
                            >
                                <div class="space-y-0.5">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-foreground">{{ acc.broker }}</span>
                                        <span class="font-mono text-muted-foreground text-[10px]">No. {{ acc.nomor_akun }}</span>
                                        <span
                                            v-if="acc.is_first"
                                            class="rounded bg-sky-500/10 text-sky-800 dark:text-sky-300 border border-sky-500/30 px-1.5 py-0.2 text-[9px] font-bold"
                                        >
                                            Akun Pertama (Acuan)
                                        </span>
                                        <span
                                            v-else
                                            class="rounded bg-purple-500/10 text-purple-800 dark:text-purple-300 border border-purple-500/30 px-1.5 py-0.2 text-[9px] font-semibold"
                                        >
                                            Akun Tambahan (+{{ acc.selisih_hari }} hari)
                                        </span>
                                    </div>
                                    <p class="text-muted-foreground text-[10px]">
                                        Periode: {{ acc.tanggal_mulai }} s/d {{ acc.tanggal_selesai }}
                                    </p>
                                </div>

                                <div class="flex items-center gap-4 text-right sm:text-right">
                                    <div>
                                        <span class="text-muted-foreground block text-[10px]">Modal Riil:</span>
                                        <span class="font-mono font-bold text-foreground">{{ formatUSD(acc.modal_riil) }}</span>
                                    </div>
                                    <div class="rounded-md bg-emerald-500/10 border border-emerald-500/20 px-2 py-1 text-center min-w-[90px]">
                                        <span class="text-muted-foreground block text-[9px]">Dihitung:</span>
                                        <span class="font-mono font-extrabold text-emerald-800 dark:text-emerald-300 text-xs">
                                            {{ acc.hari_kehitung }} Hari
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rekening Bank Tujuan Transfer Pencairan -->
            <div class="rounded-xl border border-border bg-card p-3.5 space-y-3">
                <div class="flex items-center gap-1.5 border-b border-border/60 pb-2">
                    <CreditCard class="h-4 w-4 text-primary-600 dark:text-primary-400" />
                    <h4 class="text-foreground text-xs font-bold uppercase tracking-wider">
                        Rekening Bank Tujuan Transfer (Mitra Affiliator)
                    </h4>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="text-muted-foreground block text-[11px] font-semibold">
                            Nama Bank <span class="text-destructive">*</span>
                        </label>
                        <input
                            v-model="form.nama_bank"
                            type="text"
                            required
                            placeholder="cth: BCA, Mandiri, BRI"
                            class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border px-3 py-1.5 text-xs focus:ring-2 focus:outline-none"
                        />
                        <p v-if="form.errors.nama_bank" class="text-destructive text-[11px]">
                            {{ form.errors.nama_bank }}
                        </p>
                    </div>

                    <div>
                        <label class="text-muted-foreground block text-[11px] font-semibold">
                            Nomor Rekening <span class="text-destructive">*</span>
                        </label>
                        <input
                            v-model="form.nomor_rekening"
                            type="text"
                            required
                            placeholder="Nomor rekening"
                            class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border px-3 py-1.5 text-xs font-mono focus:ring-2 focus:outline-none"
                        />
                        <p v-if="form.errors.nomor_rekening" class="text-destructive text-[11px]">
                            {{ form.errors.nomor_rekening }}
                        </p>
                    </div>

                    <div>
                        <label class="text-muted-foreground block text-[11px] font-semibold">
                            Atas Nama <span class="text-destructive">*</span>
                        </label>
                        <input
                            v-model="form.atas_nama_rekening"
                            type="text"
                            required
                            placeholder="Nama pemilik rekening"
                            class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border px-3 py-1.5 text-xs focus:ring-2 focus:outline-none"
                        />
                        <p v-if="form.errors.atas_nama_rekening" class="text-destructive text-[11px]">
                            {{ form.errors.atas_nama_rekening }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Ringkasan & Tombol Aksi Form -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2 border-t border-border/60">
                <div class="text-xs">
                    <span v-if="selectedSummary.totalDownline > 0" class="text-foreground font-semibold">
                        Terpilih: <strong class="text-primary-600 dark:text-primary-400 font-bold">{{ selectedSummary.totalDownline }} Investor</strong> ({{ selectedSummary.totalAkun }} Akun Copytrade) — Total Modal: {{ formatUSD(selectedSummary.totalModal) }}
                    </span>
                    <span v-else class="text-muted-foreground">
                        Belum ada investor downline yang dipilih
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <Button type="button" variant="outline" @click="$emit('close')">
                        Batal
                    </Button>
                    <Button
                        type="submit"
                        :disabled="selectedSummary.totalDownline === 0 || form.processing"
                        class="bg-primary-600 hover:bg-primary-700 text-white"
                    >
                        <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin mr-1.5" />
                        <span>Kirim Pengajuan ({{ selectedSummary.totalDownline }} Investor)</span>
                    </Button>
                </div>
            </div>
        </form>
    </AppModal>
</template>
