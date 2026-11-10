<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import {
    DollarSign,
    CheckCircle2,
    Circle,
    ExternalLink,
    BookOpen,
    Key,
    Server,
    Shield,
    UploadCloud,
    Download,
    Eye,
    EyeOff,
    RotateCcw,
    AlertCircle,
    Wallet,
    Layers,
    UserCheck,
    Lock,
    Link as LinkIcon,
} from 'lucide-vue-next';
import AppButton from '@/Components/Common/AppButton.vue';
import AppConfirmDialog from '@/Components/Common/AppConfirmDialog.vue';
import { compressImageFile } from '@/lib/imageCompression';

const props = defineProps({
    onboarding: {
        type: Object,
        default: () => ({
            modal_usd: null,
            tier: null,
            completed_steps: [],
            draft_data: {},
            status: 'in_progress',
        }),
    },
    exchangeRate: {
        type: Number,
        default: 16796,
    },
    hasExisting: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['accountSubmitted']);

// Konfigurasi Batas Modal
const TIER_2_MIN = 5000;
const TIER_3_MIN = 300000;

// State Modal & Tier
const modalUsd = ref(props.onboarding?.modal_usd ? Number(props.onboarding.modal_usd) : null);
const selectedTier = computed(() => {
    if (!modalUsd.value || modalUsd.value <= 0) return null;
    if (modalUsd.value < TIER_2_MIN) return 'tier_1';
    if (modalUsd.value < TIER_3_MIN) return 'tier_2';
    return 'tier_3';
});

// Format Rupiah & USD
const formatUSD = (val) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(val || 0);
};

const formatIDR = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(val || 0);
};

const modalIdrEstimate = computed(() => {
    if (!modalUsd.value || modalUsd.value <= 0) return 0;
    return Math.round(modalUsd.value * props.exchangeRate);
});

// Preset Cepat Modal
const quickPresets = [
    { label: '$1.000', value: 1000, tier: 'Tier 1' },
    { label: '$2.500', value: 2500, tier: 'Tier 1' },
    { label: '$5.000', value: 5000, tier: 'Tier 2' },
    { label: '$10.000', value: 10000, tier: 'Tier 2' },
    { label: '$50.000', value: 50000, tier: 'Tier 2' },
    { label: '$300.000', value: 300000, tier: 'Tier 3' },
];

const setQuickModal = (val) => {
    modalUsd.value = val;
    triggerAutoSave();
};

// Informasi Spesifikasi Tier
const tierMeta = computed(() => {
    switch (selectedTier.value) {
        case 'tier_3':
            return {
                code: 'tier_3',
                name: 'Tier 3 — Direct Managed Trading (Private VPS)',
                broker: 'XM',
                minModal: TIER_3_MIN,
                rangeLabel: 'Minimal $300.000',
                badgeClass:
                    'bg-purple-500/10 text-purple-700 dark:text-purple-300 border-purple-500/30',
            };
        case 'tier_2':
            return {
                code: 'tier_2',
                name: 'Tier 2 — Copytrade Standard (HFM)',
                broker: 'HFM',
                minModal: TIER_2_MIN,
                rangeLabel: '$5.000 – $299.999',
                badgeClass:
                    'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border-emerald-500/30',
            };
        case 'tier_1':
            return {
                code: 'tier_1',
                name: 'Tier 1 — Copytrade Cent (HFM)',
                broker: 'HFM',
                minModal: 1,
                rangeLabel: 'Di bawah $5.000',
                badgeClass: 'bg-sky-500/10 text-sky-700 dark:text-sky-300 border-sky-500/30',
            };
        default:
            return null;
    }
});

// Checklist Steps & Completion State
const completedSteps = ref(
    Array.isArray(props.onboarding?.completed_steps) ? [...props.onboarding.completed_steps] : []
);
const isStepCompleted = (key) => completedSteps.value.includes(key);

const toggleStep = (key) => {
    if (isStepCompleted(key)) {
        completedSteps.value = completedSteps.value.filter((k) => k !== key);
    } else {
        completedSteps.value.push(key);
    }
    triggerAutoSave();
};

// Form Pendaftaran Terintegrasi
const draft = props.onboarding?.draft_data || {};
const form = useForm({
    tier_investasi: selectedTier.value || 'tier_1',
    nomor_akun_trading: draft.nomor_akun_trading || '',
    modal_riil: modalUsd.value || '',
    tanggal_mulai_trading: draft.tanggal_mulai_trading || new Date().toISOString().split('T')[0],
    server_broker: draft.server_broker || '',
    master_password: draft.master_password || '',
    investor_password: draft.investor_password || '',
    myfxbook_url: draft.myfxbook_url || '',
    nama_bank: draft.nama_bank || '',
    nomor_rekening: draft.nomor_rekening || '',
    atas_nama_rekening: draft.atas_nama_rekening || '',
    dokumen_perjanjian: null,
});

watch(selectedTier, (newTier) => {
    if (newTier) {
        form.tier_investasi = newTier;
    }
});

watch(modalUsd, (newModal) => {
    if (newModal) {
        form.modal_riil = newModal;
    }
});

// Password visibility togglers
const showMasterPassword = ref(false);
const showInvestorPassword = ref(false);

// Dokumen Perjanjian Upload & Compression
const agreementFileName = ref('');
const isCompressing = ref(false);

const handleFileUpload = async (event) => {
    const file = event.target.files?.[0];
    if (!file) return;

    if (file.type.startsWith('image/')) {
        try {
            isCompressing.value = true;
            const compressed = await compressImageFile(file);
            form.dokumen_perjanjian = compressed?.file || compressed;
            agreementFileName.value = file.name;
        } catch {
            form.dokumen_perjanjian = file;
            agreementFileName.value = file.name;
        } finally {
            isCompressing.value = false;
        }
    } else {
        form.dokumen_perjanjian = file;
        agreementFileName.value = file.name;
    }
};

// Auto-Save Debounce Mechanism ke Backend
let autoSaveTimeout = null;
const triggerAutoSave = () => {
    if (autoSaveTimeout) {
        clearTimeout(autoSaveTimeout);
        autoSaveTimeout = null;
    }
    // Jika flow sedang dalam kondisi kosong/reset total, jangan lakukan auto-save data kosong
    if (
        !modalUsd.value &&
        completedSteps.value.length === 0 &&
        !form.nomor_akun_trading &&
        !form.myfxbook_url
    ) {
        return;
    }
    autoSaveTimeout = setTimeout(() => {
        router.post(
            route('member.investments.onboarding.update'),
            {
                modal_usd: modalUsd.value,
                tier: selectedTier.value,
                completed_steps: completedSteps.value,
                draft_data: {
                    nomor_akun_trading: form.nomor_akun_trading,
                    server_broker: form.server_broker,
                    master_password: form.master_password,
                    investor_password: form.investor_password,
                    myfxbook_url: form.myfxbook_url,
                    tanggal_mulai_trading: form.tanggal_mulai_trading,
                    nama_bank: form.nama_bank,
                    nomor_rekening: form.nomor_rekening,
                    atas_nama_rekening: form.atas_nama_rekening,
                },
            },
            {
                preserveScroll: true,
                preserveState: true,
            }
        );
    }, 800);
};

// Reset Total Seluruh Field Formulir & State Terkait
const resetAllFormFields = () => {
    form.tier_investasi = 'tier_1';
    form.nomor_akun_trading = '';
    form.modal_riil = '';
    form.tanggal_mulai_trading = new Date().toISOString().split('T')[0];
    form.server_broker = '';
    form.master_password = '';
    form.investor_password = '';
    form.myfxbook_url = '';
    form.nama_bank = '';
    form.nomor_rekening = '';
    form.atas_nama_rekening = '';
    form.dokumen_perjanjian = null;
    form.clearErrors();

    // Perbarui nilai default useForm agar isDirty menjadi false & reset selanjutnya tetap kosong
    form.defaults({
        tier_investasi: 'tier_1',
        nomor_akun_trading: '',
        modal_riil: '',
        tanggal_mulai_trading: new Date().toISOString().split('T')[0],
        server_broker: '',
        master_password: '',
        investor_password: '',
        myfxbook_url: '',
        nama_bank: '',
        nomor_rekening: '',
        atas_nama_rekening: '',
        dokumen_perjanjian: null,
    });

    agreementFileName.value = '';
    showMasterPassword.value = false;
    showInvestorPassword.value = false;

    // Bersihkan file input pada DOM jika sebelumnya sempat memilih berkas
    if (typeof document !== 'undefined') {
        const fileInputs = document.querySelectorAll('input[type="file"]');
        fileInputs.forEach((input) => {
            input.value = '';
        });
    }
};

// Watcher Sinkronisasi Saat Props Onboarding Dikosongkan dari Server
watch(
    () => props.onboarding,
    (newVal) => {
        if (
            !newVal ||
            (!newVal.modal_usd &&
                (!newVal.completed_steps || newVal.completed_steps.length === 0) &&
                (!newVal.draft_data || Object.keys(newVal.draft_data).length === 0))
        ) {
            modalUsd.value = null;
            completedSteps.value = [];
            resetAllFormFields();
        }
    },
    { deep: true }
);

// Reset Onboarding Dialog (Mulai Ulang)
const showResetConfirm = ref(false);
const isResetting = ref(false);

const executeReset = () => {
    if (autoSaveTimeout) {
        clearTimeout(autoSaveTimeout);
        autoSaveTimeout = null;
    }
    isResetting.value = true;
    router.post(
        route('member.investments.onboarding.reset'),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                modalUsd.value = null;
                completedSteps.value = [];
                resetAllFormFields();
                showResetConfirm.value = false;
            },
            onFinish: () => {
                isResetting.value = false;
            },
        }
    );
};

// Kirim Pendaftaran Akun Copytrade
const submitAccount = () => {
    form.tier_investasi = selectedTier.value || 'tier_1';
    form.modal_riil = modalUsd.value;

    if (form.tier_investasi !== 'tier_3') {
        form.server_broker = '';
        form.master_password = '';
        form.investor_password = '';
    }

    form.post(route('member.investments.store'), {
        preserveScroll: true,
        onSuccess: () => {
            emit('accountSubmitted');
        },
    });
};

// Download Template Perjanjian
const downloadAgreementTemplate = () => {
    if (!selectedTier.value) return;
    const isTier3 = selectedTier.value === 'tier_3';
    const url = route('member.investments.template', {
        tier: selectedTier.value,
        modal_riil: modalUsd.value,
        nama_bank: form.nama_bank,
        nomor_rekening: form.nomor_rekening,
        atas_nama_rekening: form.atas_nama_rekening,
        nomor_akun_trading: form.nomor_akun_trading,
        server_broker: isTier3 ? form.server_broker : '',
        master_password: isTier3 ? form.master_password : '',
        investor_password: isTier3 ? form.investor_password : '',
        tanggal_mulai_trading: form.tanggal_mulai_trading || '',
    });
    window.open(url, '_blank');
};

// Progress Selesai
const totalChecklistCount = 6;
const completedCount = computed(() => {
    const validKeys = ['step_1', 'step_2', 'step_3', 'step_4', 'step_5', 'step_6'];
    return completedSteps.value.filter((k) => validKeys.includes(k)).length;
});
const progressPct = computed(() => Math.round((completedCount.value / totalChecklistCount) * 100));
</script>

<template>
    <div class="space-y-6">
        <!-- LANGKAH 1: PENENTUAN MODAL INVESTASI -->
        <div class="border-border bg-card space-y-4 rounded-2xl border p-5 shadow-xs sm:p-6">
            <div
                class="border-border/70 flex flex-col gap-3 border-b pb-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <span
                            class="bg-primary-500/10 text-primary-700 dark:text-primary-300 flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold"
                        >
                            1
                        </span>
                        <h3 class="text-foreground text-sm font-bold sm:text-base">
                            Tentukan Modal Investasi Anda
                        </h3>
                    </div>
                    <p class="text-muted-foreground text-xs">
                        Masukkan estimasi nominal modal yang ingin Anda kelola. Sistem otomatis
                        mendeteksi Tier dan broker yang tepat.
                    </p>
                </div>

                <div v-if="modalUsd || completedSteps.length > 0" class="flex items-center gap-2">
                    <AppButton
                        type="custom"
                        variant="outline"
                        size="sm"
                        :icon="RotateCcw"
                        label="Mulai Ulang"
                        @click="showResetConfirm = true"
                    />
                </div>
            </div>

            <!-- Input Form Modal -->
            <div class="grid grid-cols-1 items-start gap-4 sm:grid-cols-2">
                <div class="space-y-1.5">
                    <label
                        class="text-foreground flex items-center justify-between text-xs font-semibold"
                    >
                        <span>Nominal Modal (USD) <span class="text-rose-500">*</span></span>
                        <span
                            v-if="exchangeRate"
                            class="text-muted-foreground text-[11px] font-normal"
                        >
                            Kurs: ${{ 1 }} = {{ formatIDR(exchangeRate) }}
                        </span>
                    </label>
                    <div class="relative">
                        <span
                            class="text-muted-foreground absolute top-2.5 left-3 font-mono text-xs font-bold"
                            >$</span
                        >
                        <input
                            v-model.number="modalUsd"
                            type="number"
                            min="1"
                            step="any"
                            placeholder="Contoh: 5000"
                            @input="triggerAutoSave"
                            class="bg-background border-input focus:ring-primary-500 h-10 w-full rounded-xl border pr-4 pl-8 font-mono text-sm font-bold focus:ring-2 focus:outline-none"
                        />
                    </div>
                    <p v-if="modalIdrEstimate > 0" class="text-muted-foreground font-mono text-xs">
                        Estimasi Rupiah:
                        <strong class="text-foreground">{{ formatIDR(modalIdrEstimate) }}</strong>
                    </p>
                </div>

                <!-- Tombol Cepat (Presets) -->
                <div class="space-y-1.5">
                    <span class="text-muted-foreground block text-xs font-medium"
                        >Pilihan Nominal Cepat:</span
                    >
                    <div class="flex flex-wrap gap-1.5">
                        <button
                            v-for="p in quickPresets"
                            :key="p.value"
                            type="button"
                            @click="setQuickModal(p.value)"
                            :class="[
                                'rounded-lg border px-2.5 py-1 font-mono text-xs font-semibold transition-all',
                                modalUsd === p.value
                                    ? 'bg-primary-600 border-primary-600 text-white shadow-xs'
                                    : 'border-border bg-muted/40 hover:bg-muted text-foreground',
                            ]"
                        >
                            {{ p.label }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Banner Hasil Deteksi Tier -->
            <div v-if="tierMeta" class="border-border/70 border-t pt-3">
                <div
                    class="flex flex-col justify-between gap-2.5 rounded-xl border px-4 py-2.5 transition-all sm:flex-row sm:items-center"
                    :class="[
                        tierMeta.code === 'tier_3'
                            ? 'border-purple-500/30 bg-purple-500/5'
                            : tierMeta.code === 'tier_2'
                              ? 'border-emerald-500/30 bg-emerald-500/5'
                              : 'border-sky-500/30 bg-sky-500/5',
                    ]"
                >
                    <div class="flex min-w-0 items-center gap-2.5">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-foreground text-xs font-bold sm:text-sm">
                                {{ tierMeta.name }}
                            </span>
                            <span class="text-muted-foreground font-mono text-xs">
                                &bull; Rentang Modal:
                                <strong class="text-foreground">{{ tierMeta.rangeLabel }}</strong>
                            </span>
                        </div>
                    </div>

                    <span
                        class="shrink-0 self-start rounded-full border px-2.5 py-0.5 text-xs font-bold sm:self-auto"
                        :class="tierMeta.badgeClass"
                    >
                        Broker: {{ tierMeta.broker }}
                    </span>
                </div>
            </div>
        </div>

        <!-- LANGKAH 2: CHECKLIST INTERAKTIF & TUTORIAL -->
        <div
            v-if="selectedTier"
            class="border-border bg-card space-y-5 rounded-2xl border p-5 shadow-xs sm:p-6"
        >
            <!-- Header Checklist & Progress Bar -->
            <div class="border-border/70 space-y-3 border-b pb-4">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <span
                                class="bg-primary-500/10 text-primary-700 dark:text-primary-300 flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold"
                            >
                                2
                            </span>
                            <h3 class="text-foreground text-sm font-bold sm:text-base">
                                Checklist Panduan Langkah Investor ({{ tierMeta.broker }})
                            </h3>
                        </div>
                        <p class="text-muted-foreground text-xs">
                            Ikuti langkah-langkah di bawah ini secara berurutan. Gunakan tautan
                            tutorial yang tersedia di setiap langkah.
                        </p>
                    </div>

                    <div class="shrink-0 text-right">
                        <span class="text-foreground text-xs font-bold">
                            {{ completedCount }} / {{ totalChecklistCount }} Selesai ({{
                                progressPct
                            }}%)
                        </span>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="bg-muted h-2 w-full overflow-hidden rounded-full">
                    <div
                        class="h-full bg-emerald-600 transition-all duration-300"
                        :style="{ width: `${progressPct}%` }"
                    ></div>
                </div>
            </div>

            <!-- DAFTAR ITEM CHECKLIST TIER 1 & TIER 2 (HFM) -->
            <div v-if="selectedTier === 'tier_1' || selectedTier === 'tier_2'" class="space-y-4">
                <!-- STEP 1: Daftar Broker HFM -->
                <div
                    class="space-y-3 rounded-xl border p-4 transition-colors"
                    :class="
                        isStepCompleted('step_1')
                            ? 'border-emerald-500/40 bg-emerald-500/5'
                            : 'border-border bg-muted/20'
                    "
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-start gap-3">
                            <button
                                type="button"
                                @click="toggleStep('step_1')"
                                class="mt-0.5 text-emerald-600 transition-colors hover:text-emerald-700 focus:outline-hidden"
                            >
                                <CheckCircle2
                                    v-if="isStepCompleted('step_1')"
                                    class="h-5 w-5 fill-emerald-500/20"
                                />
                                <Circle v-else class="text-muted-foreground h-5 w-5" />
                            </button>
                            <div class="space-y-1">
                                <h4
                                    class="text-foreground flex items-center gap-2 text-xs font-bold sm:text-sm"
                                >
                                    <span>Langkah 1: Daftar Akun di Broker HFM</span>
                                    <span
                                        v-if="isStepCompleted('step_1')"
                                        class="py-0.2 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2 text-[10px] text-emerald-700 dark:text-emerald-300"
                                    >
                                        Selesai
                                    </span>
                                </h4>
                                <p class="text-muted-foreground text-xs">
                                    Buka akun myHF baru di broker HFM melalui tautan resmi kemitraan
                                    YZD Center. Lengkapi verifikasi profil (KYC) Anda di portal HFM.
                                </p>
                            </div>
                        </div>

                        <!-- Tutorial & Action Link -->
                        <div class="flex shrink-0 flex-wrap items-center justify-end gap-2">
                            <a
                                href="https://register.hfmtrade-ind.com/sv/id/new-live-account/?refid=30570652"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-primary-600 hover:text-primary-700 bg-primary-500/10 border-primary-500/20 inline-flex items-center gap-1 rounded-lg border px-2.5 py-1 text-[11px] font-semibold"
                            >
                                <ExternalLink class="h-3 w-3" />
                                <span>Link Pendaftaran HFM</span>
                            </a>
                            <a
                                :href="route('member.education.article', 'panduan-registrasi-broker-hfm')"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-muted-foreground hover:text-foreground bg-muted border-border inline-flex items-center gap-1 rounded-lg border px-2.5 py-1 text-[11px] font-semibold"
                            >
                                <BookOpen class="h-3 w-3" />
                                <span>Panduan Registrasi</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- STEP 2: Deposit ke Wallet HFM -->
                <div
                    class="space-y-3 rounded-xl border p-4 transition-colors"
                    :class="
                        isStepCompleted('step_2')
                            ? 'border-emerald-500/40 bg-emerald-500/5'
                            : 'border-border bg-muted/20'
                    "
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-start gap-3">
                            <button
                                type="button"
                                @click="toggleStep('step_2')"
                                class="mt-0.5 text-emerald-600 transition-colors hover:text-emerald-700 focus:outline-hidden"
                            >
                                <CheckCircle2
                                    v-if="isStepCompleted('step_2')"
                                    class="h-5 w-5 fill-emerald-500/20"
                                />
                                <Circle v-else class="text-muted-foreground h-5 w-5" />
                            </button>
                            <div class="space-y-1">
                                <h4
                                    class="text-foreground flex items-center gap-2 text-xs font-bold sm:text-sm"
                                >
                                    <span>Langkah 2: Deposit Dana ke Wallet HFM</span>
                                    <span
                                        v-if="isStepCompleted('step_2')"
                                        class="py-0.2 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2 text-[10px] text-emerald-700 dark:text-emerald-300"
                                    >
                                        Selesai
                                    </span>
                                </h4>
                                <p class="text-muted-foreground text-xs">
                                    Lakukan deposit saldo ke MyWallet akun HFM Anda sesuai modal yang telah ditentukan,
                                    <strong>{{ formatUSD(modalUsd) }}</strong> (&approx;
                                    {{ formatIDR(modalIdrEstimate) }}) menggunakan metode transfer
                                    bank lokal atau payment gateway resmi HFM.
                                </p>
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            <a
                                :href="route('member.education.article', 'panduan-deposit-wallet-hfm')"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-muted-foreground hover:text-foreground bg-muted border-border inline-flex items-center gap-1 rounded-lg border px-2.5 py-1 text-[11px] font-semibold"
                            >
                                <BookOpen class="h-3 w-3" />
                                <span>Panduan Deposit</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- STEP 3: Follow Akun Master YZD -->
                <div
                    class="space-y-3 rounded-xl border p-4 transition-colors"
                    :class="
                        isStepCompleted('step_3')
                            ? 'border-emerald-500/40 bg-emerald-500/5'
                            : 'border-border bg-muted/20'
                    "
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-start gap-3">
                            <button
                                type="button"
                                @click="toggleStep('step_3')"
                                class="mt-0.5 text-emerald-600 transition-colors hover:text-emerald-700 focus:outline-hidden"
                            >
                                <CheckCircle2
                                    v-if="isStepCompleted('step_3')"
                                    class="h-5 w-5 fill-emerald-500/20"
                                />
                                <Circle v-else class="text-muted-foreground h-5 w-5" />
                            </button>
                            <div class="space-y-1">
                                <h4
                                    class="text-foreground flex items-center gap-2 text-xs font-bold sm:text-sm"
                                >
                                    <span>Langkah 3: Follow Akun Master Strategi YZD</span>
                                    <span
                                        v-if="isStepCompleted('step_3')"
                                        class="py-0.2 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2 text-[10px] text-emerald-700 dark:text-emerald-300"
                                    >
                                        Selesai
                                    </span>
                                </h4>
                                <p class="text-muted-foreground text-xs">
                                    Masuk ke menu <strong>HFM Copy</strong>, cari akun Master Trader
                                    YZD, lalu pilih
                                    <strong>Follow / Copy Strategy</strong> dan alokasikan saldo
                                    wallet Anda.
                                </p>
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            <a
                                :href="route('member.education.article', 'panduan-follow-master-hfm')"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-muted-foreground hover:text-foreground bg-muted border-border inline-flex items-center gap-1 rounded-lg border px-2.5 py-1 text-[11px] font-semibold"
                            >
                                <BookOpen class="h-3 w-3" />
                                <span>Panduan Follow Master</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- STEP 4: Catat / Input Kredensial Akun MT4 Follower -->
                <div
                    class="space-y-3 rounded-xl border p-4 transition-colors"
                    :class="
                        isStepCompleted('step_4')
                            ? 'border-emerald-500/40 bg-emerald-500/5'
                            : 'border-border bg-muted/20'
                    "
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-start gap-3">
                            <button
                                type="button"
                                @click="toggleStep('step_4')"
                                class="mt-0.5 text-emerald-600 transition-colors hover:text-emerald-700 focus:outline-hidden"
                            >
                                <CheckCircle2
                                    v-if="isStepCompleted('step_4')"
                                    class="h-5 w-5 fill-emerald-500/20"
                                />
                                <Circle v-else class="text-muted-foreground h-5 w-5" />
                            </button>
                            <div class="space-y-1">
                                <h4
                                    class="text-foreground flex items-center gap-2 text-xs font-bold sm:text-sm"
                                >
                                    <span>Langkah 4: Catat Nomor Akun MT4 Follower</span>
                                    <span
                                        v-if="isStepCompleted('step_4')"
                                        class="py-0.2 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2 text-[10px] text-emerald-700 dark:text-emerald-300"
                                    >
                                        Selesai
                                    </span>
                                </h4>
                                <p class="text-muted-foreground text-xs">
                                    Setelah berhasil mengikuti (follow) akun Master Copytrade di portal HFM, broker akan menerbitkan nomor akun MT4 Follower Anda. Masukkan nomor akun tersebut ke formulir di bawah ini untuk identifikasi sistem YZD Center.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Input Form Terintegrasi Langkah 4 -->
                    <div class="bg-card border-border/80 mt-2 space-y-3 rounded-xl border p-3.5">
                        <div class="max-w-md space-y-1.5 text-xs">
                            <label class="text-foreground block text-xs font-semibold">
                                Nomor Akun Trading MT4 Follower <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-3 top-2.5 text-xs font-mono font-bold text-muted-foreground">#</span>
                                <input
                                    v-model="form.nomor_akun_trading"
                                    type="text"
                                    placeholder="Contoh: 3829104"
                                    @input="triggerAutoSave"
                                    class="bg-background border-input focus:ring-primary-500 h-9 w-full rounded-lg border pl-8 pr-3 font-mono text-xs font-bold focus:ring-2 focus:outline-none"
                                />
                            </div>
                            <p
                                v-if="form.errors.nomor_akun_trading"
                                class="text-[10px] text-rose-500"
                            >
                                {{ form.errors.nomor_akun_trading }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- STEP 5: Hubungkan ke MT4 -->
                <div
                    class="space-y-3 rounded-xl border p-4 transition-colors"
                    :class="
                        isStepCompleted('step_5')
                            ? 'border-emerald-500/40 bg-emerald-500/5'
                            : 'border-border bg-muted/20'
                    "
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-start gap-3">
                            <button
                                type="button"
                                @click="toggleStep('step_5')"
                                class="mt-0.5 text-emerald-600 transition-colors hover:text-emerald-700 focus:outline-hidden"
                            >
                                <CheckCircle2
                                    v-if="isStepCompleted('step_5')"
                                    class="h-5 w-5 fill-emerald-500/20"
                                />
                                <Circle v-else class="text-muted-foreground h-5 w-5" />
                            </button>
                            <div class="space-y-1">
                                <h4
                                    class="text-foreground flex items-center gap-2 text-xs font-bold sm:text-sm"
                                >
                                    <span
                                        >Langkah 5: Hubungkan Akun ke Aplikasi MetaTrader 4
                                        (MT4)</span
                                    >
                                    <span
                                        v-if="isStepCompleted('step_5')"
                                        class="py-0.2 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2 text-[10px] text-emerald-700 dark:text-emerald-300"
                                    >
                                        Selesai
                                    </span>
                                </h4>
                                <p class="text-muted-foreground text-xs">
                                    Login ke aplikasi MT4 (HP atau Laptop) menggunakan Nomor Akun serta server dan password yang dikirimkan oleh broker HFM ke email Anda untuk memantau transaksi copytrade secara langsung dan transparan.
                                </p>
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            <a
                                :href="route('member.education.article', 'panduan-login-aplikasi-mt4')"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-muted-foreground hover:text-foreground bg-muted border-border inline-flex items-center gap-1 rounded-lg border px-2.5 py-1 text-[11px] font-semibold"
                            >
                                <BookOpen class="h-3 w-3" />
                                <span>Panduan Login MT4</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- STEP 6: Hubungkan ke Myfxbook & Pendaftaran Final -->
                <div
                    class="space-y-4 rounded-xl border p-4 transition-colors"
                    :class="
                        isStepCompleted('step_6')
                            ? 'border-emerald-500/40 bg-emerald-500/5'
                            : 'border-border bg-muted/20'
                    "
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-start gap-3">
                            <button
                                type="button"
                                @click="toggleStep('step_6')"
                                class="mt-0.5 text-emerald-600 transition-colors hover:text-emerald-700 focus:outline-hidden"
                            >
                                <CheckCircle2
                                    v-if="isStepCompleted('step_6')"
                                    class="h-5 w-5 fill-emerald-500/20"
                                />
                                <Circle v-else class="text-muted-foreground h-5 w-5" />
                            </button>
                            <div class="space-y-1">
                                <h4
                                    class="text-foreground flex items-center gap-2 text-xs font-bold sm:text-sm"
                                >
                                    <span
                                        >Langkah 6: Hubungkan ke Myfxbook & Daftarkan Akun ke YZD
                                        Center</span
                                    >
                                    <span
                                        v-if="isStepCompleted('step_6')"
                                        class="py-0.2 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2 text-[10px] text-emerald-700 dark:text-emerald-300"
                                    >
                                        Siap Dikirim
                                    </span>
                                </h4>
                                <p class="text-muted-foreground text-xs">
                                    Hubungkan akun MT4 Anda ke Myfxbook agar kinerja bulanan
                                    tercatat otomatis. Isi data final di
                                    bawah dan kirim pendaftaran.
                                </p>
                            </div>
                        </div>

                        <div class="flex shrink-0 flex-wrap items-center justify-end gap-2">
                            <a
                                href="https://www.myfxbook.com"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-primary-600 hover:text-primary-700 bg-primary-500/10 border-primary-500/20 inline-flex items-center gap-1 rounded-lg border px-2.5 py-1 text-[11px] font-semibold"
                            >
                                <ExternalLink class="h-3 w-3" />
                                <span>Buka Myfxbook</span>
                            </a>
                            <a
                                :href="route('member.education.article', 'panduan-menghubungkan-myfxbook')"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-muted-foreground hover:text-foreground bg-muted border-border inline-flex items-center gap-1 rounded-lg border px-2.5 py-1 text-[11px] font-semibold"
                            >
                                <BookOpen class="h-3 w-3" />
                                <span>Panduan Myfxbook</span>
                            </a>
                        </div>
                    </div>

                    <!-- FORM FINAL TERINTEGRASI -->
                    <div class="bg-card border-border space-y-4 rounded-xl border p-4">
                        <div class="grid grid-cols-1 gap-3 text-xs sm:grid-cols-2">
                            <div class="space-y-1 sm:col-span-2">
                                <label class="text-foreground block text-xs font-semibold">
                                    Tautan Publik Portofolio Myfxbook
                                    <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="form.myfxbook_url"
                                    type="url"
                                    placeholder="Contoh: https://www.myfxbook.com/members/nama/akun/123456"
                                    @input="triggerAutoSave"
                                    class="bg-background border-input focus:ring-primary-500 h-9 w-full rounded-lg border px-3 font-mono text-xs focus:ring-2 focus:outline-none"
                                />
                                <p
                                    v-if="form.errors.myfxbook_url"
                                    class="text-[10px] text-rose-500"
                                >
                                    {{ form.errors.myfxbook_url }}
                                </p>
                            </div>

                            <div class="space-y-1">
                                <label class="text-foreground block text-xs font-semibold">
                                    Tanggal Mulai Investasi <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="form.tanggal_mulai_trading"
                                    type="date"
                                    @input="triggerAutoSave"
                                    class="bg-background border-input focus:ring-primary-500 h-9 w-full rounded-lg border px-3 text-xs focus:ring-2 focus:outline-none"
                                />
                                <p
                                    v-if="form.errors.tanggal_mulai_trading"
                                    class="text-[10px] text-rose-500"
                                >
                                    {{ form.errors.tanggal_mulai_trading }}
                                </p>
                            </div>
                        </div>

                        <!-- KHUSUS AKUN PERTAMA: REKENING BANK & SURAT PERJANJIAN -->
                        <div v-if="!hasExisting" class="border-border/70 space-y-3 border-t pt-3">
                            <div class="flex items-center gap-1.5">
                                <Shield class="text-primary-600 h-4 w-4" />
                                <h5
                                    class="text-foreground text-xs font-bold tracking-wider uppercase"
                                >
                                    Kelengkapan Akun Pertama (Rekening & Perjanjian Kemitraan)
                                </h5>
                            </div>

                            <div class="grid grid-cols-1 gap-3 text-xs sm:grid-cols-3">
                                <div class="space-y-1">
                                    <label class="text-foreground block text-xs font-semibold">
                                        Nama Bank <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.nama_bank"
                                        type="text"
                                        placeholder="Contoh: BCA / Mandiri"
                                        @input="triggerAutoSave"
                                        class="bg-background border-input focus:ring-primary-500 h-9 w-full rounded-lg border px-3 text-xs focus:ring-2 focus:outline-none"
                                    />
                                    <p
                                        v-if="form.errors.nama_bank"
                                        class="text-[10px] text-rose-500"
                                    >
                                        {{ form.errors.nama_bank }}
                                    </p>
                                </div>

                                <div class="space-y-1">
                                    <label class="text-foreground block text-xs font-semibold">
                                        Nomor Rekening Bank <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.nomor_rekening"
                                        type="text"
                                        placeholder="Nomor rekening Anda"
                                        @input="triggerAutoSave"
                                        class="bg-background border-input focus:ring-primary-500 h-9 w-full rounded-lg border px-3 font-mono text-xs font-bold focus:ring-2 focus:outline-none"
                                    />
                                    <p
                                        v-if="form.errors.nomor_rekening"
                                        class="text-[10px] text-rose-500"
                                    >
                                        {{ form.errors.nomor_rekening }}
                                    </p>
                                </div>

                                <div class="space-y-1">
                                    <label class="text-foreground block text-xs font-semibold">
                                        Atas Nama Rekening <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.atas_nama_rekening"
                                        type="text"
                                        placeholder="Nama pemilik rekening"
                                        @input="triggerAutoSave"
                                        class="bg-background border-input focus:ring-primary-500 h-9 w-full rounded-lg border px-3 text-xs focus:ring-2 focus:outline-none"
                                    />
                                    <p
                                        v-if="form.errors.atas_nama_rekening"
                                        class="text-[10px] text-rose-500"
                                    >
                                        {{ form.errors.atas_nama_rekening }}
                                    </p>
                                </div>
                            </div>

                            <!-- Unduh & Upload Perjanjian -->
                            <div
                                class="border-border/80 bg-muted/30 space-y-3 rounded-xl border p-3.5"
                            >
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="space-y-0.5">
                                        <span class="text-foreground block text-xs font-bold">
                                            Surat Perjanjian Kemitraan (Bermaterai 10.000)
                                            <span class="text-rose-500">*</span>
                                        </span>
                                        <p class="text-muted-foreground text-[11px]">
                                            Unduh draf perjanjian otomatis, tanda tangani di atas
                                            materai, lalu unggah foto/scan berkasnya.
                                        </p>
                                    </div>
                                    <AppButton
                                        type="custom"
                                        variant="outline"
                                        size="sm"
                                        :icon="Download"
                                        label="Unduh Draf Dokumen"
                                        @click="downloadAgreementTemplate"
                                    />
                                </div>

                                <div class="flex items-center gap-2">
                                    <input
                                        type="file"
                                        accept="image/jpeg,image/png,image/jpg,application/pdf"
                                        @change="handleFileUpload"
                                        class="text-muted-foreground file:bg-primary-600 hover:file:bg-primary-700 block w-full cursor-pointer text-xs file:mr-3 file:rounded-lg file:border-0 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white"
                                    />
                                </div>
                                <p
                                    v-if="form.errors.dokumen_perjanjian"
                                    class="text-[10px] text-rose-500"
                                >
                                    {{ form.errors.dokumen_perjanjian }}
                                </p>
                            </div>
                        </div>

                        <!-- Tombol Submit Akhir -->
                        <div
                            class="border-border flex items-center justify-end gap-2 border-t pt-2"
                        >
                            <AppButton
                                type="submit"
                                label="Kirim Pendaftaran Akun Copytrade"
                                :loading="form.processing || isCompressing"
                                :disabled="
                                    form.processing ||
                                    isCompressing ||
                                    !form.nomor_akun_trading ||
                                    !form.myfxbook_url
                                "
                                @click="submitAccount"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- DAFTAR ITEM CHECKLIST TIER 3 (XM) -->
            <div v-else-if="selectedTier === 'tier_3'" class="space-y-4">
                <!-- STEP 1: Daftar Broker XM -->
                <div
                    class="space-y-3 rounded-xl border p-4 transition-colors"
                    :class="
                        isStepCompleted('step_1')
                            ? 'border-purple-500/40 bg-purple-500/5'
                            : 'border-border bg-muted/20'
                    "
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-start gap-3">
                            <button
                                type="button"
                                @click="toggleStep('step_1')"
                                class="mt-0.5 text-purple-600 transition-colors hover:text-purple-700 focus:outline-hidden"
                            >
                                <CheckCircle2
                                    v-if="isStepCompleted('step_1')"
                                    class="h-5 w-5 fill-purple-500/20"
                                />
                                <Circle v-else class="text-muted-foreground h-5 w-5" />
                            </button>
                            <div class="space-y-1">
                                <h4
                                    class="text-foreground flex items-center gap-2 text-xs font-bold sm:text-sm"
                                >
                                    <span>Langkah 1: Daftar Akun di Broker XM</span>
                                    <span
                                        v-if="isStepCompleted('step_1')"
                                        class="py-0.2 rounded-full border border-purple-500/20 bg-purple-500/10 px-2 text-[10px] text-purple-700 dark:text-purple-300"
                                    >
                                        Selesai
                                    </span>
                                </h4>
                                <p class="text-muted-foreground text-xs">
                                    Buka akun profil investor di broker XM melalui tautan resmi
                                    kemitraan YZD Center. Selesaikan proses verifikasi.
                                </p>
                            </div>
                        </div>

                        <div class="flex shrink-0 flex-wrap items-center justify-end gap-2">
                            <a
                                href="https://affs.click/jeMVj"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-primary-600 hover:text-primary-700 bg-primary-500/10 border-primary-500/20 inline-flex items-center gap-1 rounded-lg border px-2.5 py-1 text-[11px] font-semibold"
                            >
                                <ExternalLink class="h-3 w-3" />
                                <span>Link Pendaftaran XM</span>
                            </a>
                            <a
                                :href="route('member.education.article', 'panduan-registrasi-broker-xm')"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-muted-foreground hover:text-foreground bg-muted border-border inline-flex items-center gap-1 rounded-lg border px-2.5 py-1 text-[11px] font-semibold"
                            >
                                <BookOpen class="h-3 w-3" />
                                <span>Panduan Registrasi</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- STEP 2: Buka Akun MT4 di XM -->
                <div
                    class="space-y-3 rounded-xl border p-4 transition-colors"
                    :class="
                        isStepCompleted('step_2')
                            ? 'border-purple-500/40 bg-purple-500/5'
                            : 'border-border bg-muted/20'
                    "
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-start gap-3">
                            <button
                                type="button"
                                @click="toggleStep('step_2')"
                                class="mt-0.5 text-purple-600 transition-colors hover:text-purple-700 focus:outline-hidden"
                            >
                                <CheckCircle2
                                    v-if="isStepCompleted('step_2')"
                                    class="h-5 w-5 fill-purple-500/20"
                                />
                                <Circle v-else class="text-muted-foreground h-5 w-5" />
                            </button>
                            <div class="space-y-1">
                                <h4
                                    class="text-foreground flex items-center gap-2 text-xs font-bold sm:text-sm"
                                >
                                    <span>Langkah 2: Buka Akun Trading MT4 di XM</span>
                                    <span
                                        v-if="isStepCompleted('step_2')"
                                        class="py-0.2 rounded-full border border-purple-500/20 bg-purple-500/10 px-2 text-[10px] text-purple-700 dark:text-purple-300"
                                    >
                                        Selesai
                                    </span>
                                </h4>
                                <p class="text-muted-foreground text-xs">
                                    Buka akun trading MT4 Standard dengan leverage yang
                                    direkomendasikan di Member Area XM untuk koneksi private VPS.
                                </p>
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            <a
                                :href="route('member.education.article', 'panduan-buka-akun-mt4-xm')"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-muted-foreground hover:text-foreground bg-muted border-border inline-flex items-center gap-1 rounded-lg border px-2.5 py-1 text-[11px] font-semibold"
                            >
                                <BookOpen class="h-3 w-3" />
                                <span>Panduan Buka Akun MT4</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- STEP 3: Catat / Input Kredensial MT4 XM -->
                <div
                    class="space-y-3 rounded-xl border p-4 transition-colors"
                    :class="
                        isStepCompleted('step_3')
                            ? 'border-purple-500/40 bg-purple-500/5'
                            : 'border-border bg-muted/20'
                    "
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-start gap-3">
                            <button
                                type="button"
                                @click="toggleStep('step_3')"
                                class="mt-0.5 text-purple-600 transition-colors hover:text-purple-700 focus:outline-hidden"
                            >
                                <CheckCircle2
                                    v-if="isStepCompleted('step_3')"
                                    class="h-5 w-5 fill-purple-500/20"
                                />
                                <Circle v-else class="text-muted-foreground h-5 w-5" />
                            </button>
                            <div class="space-y-1">
                                <h4
                                    class="text-foreground flex items-center gap-2 text-xs font-bold sm:text-sm"
                                >
                                    <span>Langkah 3: Catat Kredensial Akun MT4 XM</span>
                                    <span
                                        v-if="isStepCompleted('step_3')"
                                        class="py-0.2 rounded-full border border-purple-500/20 bg-purple-500/10 px-2 text-[10px] text-purple-700 dark:text-purple-300"
                                    >
                                        Selesai
                                    </span>
                                </h4>
                                <p class="text-muted-foreground text-xs">
                                    Kredensial akun MT4 XM (Server, Master Password, dan Investor Password) diperlukan untuk instalasi robot trading di Dedicated Private VPS. Masukkan data ke formulir di bawah ini.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Input Form Terintegrasi Langkah 3 (Tier 3) -->
                    <div class="bg-card border-border/80 mt-2 space-y-3 rounded-xl border p-3.5">
                        <div class="grid grid-cols-1 gap-3 text-xs sm:grid-cols-2 lg:grid-cols-4">
                            <div class="space-y-1">
                                <label class="text-foreground block text-xs font-semibold">
                                    Nomor Akun Trading (MT4) <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="form.nomor_akun_trading"
                                    type="text"
                                    placeholder="Contoh: 8192301"
                                    @input="triggerAutoSave"
                                    class="bg-background border-input focus:ring-primary-500 h-9 w-full rounded-lg border px-3 font-mono text-xs font-bold focus:ring-2 focus:outline-none"
                                />
                                <p
                                    v-if="form.errors.nomor_akun_trading"
                                    class="text-[10px] text-rose-500"
                                >
                                    {{ form.errors.nomor_akun_trading }}
                                </p>
                            </div>

                            <div class="space-y-1">
                                <label class="text-foreground block text-xs font-semibold">
                                    Server Broker XM <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="form.server_broker"
                                    type="text"
                                    placeholder="Contoh: XMGlobal-Real 50"
                                    @input="triggerAutoSave"
                                    class="bg-background border-input focus:ring-primary-500 h-9 w-full rounded-lg border px-3 text-xs focus:ring-2 focus:outline-none"
                                />
                                <p
                                    v-if="form.errors.server_broker"
                                    class="text-[10px] text-rose-500"
                                >
                                    {{ form.errors.server_broker }}
                                </p>
                            </div>

                            <div class="space-y-1">
                                <label class="text-foreground block text-xs font-semibold">
                                    Master Password MT4 <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <input
                                        v-model="form.master_password"
                                        :type="showMasterPassword ? 'text' : 'password'"
                                        placeholder="Kata sandi eksekusi"
                                        @input="triggerAutoSave"
                                        class="bg-background border-input focus:ring-primary-500 h-9 w-full rounded-lg border pr-8 pl-3 font-mono text-xs focus:ring-2 focus:outline-none"
                                    />
                                    <button
                                        type="button"
                                        @click="showMasterPassword = !showMasterPassword"
                                        class="text-muted-foreground hover:text-foreground absolute top-2.5 right-2.5 cursor-pointer"
                                        tabindex="-1"
                                        :title="showMasterPassword ? 'Sembunyikan password' : 'Lihat password'"
                                    >
                                        <EyeOff v-if="showMasterPassword" class="h-4 w-4" />
                                        <Eye v-else class="h-4 w-4" />
                                    </button>
                                </div>
                                <p
                                    v-if="form.errors.master_password"
                                    class="text-[10px] text-rose-500"
                                >
                                    {{ form.errors.master_password }}
                                </p>
                            </div>

                            <div class="space-y-1">
                                <label class="text-foreground block text-xs font-semibold">
                                    Investor Password MT4 <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <input
                                        v-model="form.investor_password"
                                        :type="showInvestorPassword ? 'text' : 'password'"
                                        placeholder="Kata sandi read-only"
                                        @input="triggerAutoSave"
                                        class="bg-background border-input focus:ring-primary-500 h-9 w-full rounded-lg border pr-8 pl-3 font-mono text-xs focus:ring-2 focus:outline-none"
                                    />
                                    <button
                                        type="button"
                                        @click="showInvestorPassword = !showInvestorPassword"
                                        class="text-muted-foreground hover:text-foreground absolute top-2.5 right-2.5 cursor-pointer"
                                        tabindex="-1"
                                        :title="showInvestorPassword ? 'Sembunyikan password' : 'Lihat password'"
                                    >
                                        <EyeOff v-if="showInvestorPassword" class="h-4 w-4" />
                                        <Eye v-else class="h-4 w-4" />
                                    </button>
                                </div>
                                <p
                                    v-if="form.errors.investor_password"
                                    class="text-[10px] text-rose-500"
                                >
                                    {{ form.errors.investor_password }}
                                </p>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- STEP 4: Deposit ke Akun MT4 XM -->
                <div
                    class="space-y-3 rounded-xl border p-4 transition-colors"
                    :class="
                        isStepCompleted('step_4')
                            ? 'border-purple-500/40 bg-purple-500/5'
                            : 'border-border bg-muted/20'
                    "
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-start gap-3">
                            <button
                                type="button"
                                @click="toggleStep('step_4')"
                                class="mt-0.5 text-purple-600 transition-colors hover:text-purple-700 focus:outline-hidden"
                            >
                                <CheckCircle2
                                    v-if="isStepCompleted('step_4')"
                                    class="h-5 w-5 fill-purple-500/20"
                                />
                                <Circle v-else class="text-muted-foreground h-5 w-5" />
                            </button>
                            <div class="space-y-1">
                                <h4
                                    class="text-foreground flex items-center gap-2 text-xs font-bold sm:text-sm"
                                >
                                    <span>Langkah 4: Deposit Dana ke Akun MT4 XM</span>
                                    <span
                                        v-if="isStepCompleted('step_4')"
                                        class="py-0.2 rounded-full border border-purple-500/20 bg-purple-500/10 px-2 text-[10px] text-purple-700 dark:text-purple-300"
                                    >
                                        Selesai
                                    </span>
                                </h4>
                                <p class="text-muted-foreground text-xs">
                                    Lakukan deposit saldo langsung ke akun MT4 XM sesuai modal yang telah ditentukan, senilai 
                                    <strong>{{ formatUSD(modalUsd) }}</strong> (&approx;
                                    {{ formatIDR(modalIdrEstimate) }}).
                                </p>
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            <a
                                :href="route('member.education.article', 'panduan-deposit-akun-mt4-xm')"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-muted-foreground hover:text-foreground bg-muted border-border inline-flex items-center gap-1 rounded-lg border px-2.5 py-1 text-[11px] font-semibold"
                            >
                                <BookOpen class="h-3 w-3" />
                                <span>Panduan Deposit XM</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- STEP 5: Hubungkan ke MT4 -->
                <div
                    class="space-y-3 rounded-xl border p-4 transition-colors"
                    :class="
                        isStepCompleted('step_5')
                            ? 'border-purple-500/40 bg-purple-500/5'
                            : 'border-border bg-muted/20'
                    "
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-start gap-3">
                            <button
                                type="button"
                                @click="toggleStep('step_5')"
                                class="mt-0.5 text-purple-600 transition-colors hover:text-purple-700 focus:outline-hidden"
                            >
                                <CheckCircle2
                                    v-if="isStepCompleted('step_5')"
                                    class="h-5 w-5 fill-purple-500/20"
                                />
                                <Circle v-else class="text-muted-foreground h-5 w-5" />
                            </button>
                            <div class="space-y-1">
                                <h4
                                    class="text-foreground flex items-center gap-2 text-xs font-bold sm:text-sm"
                                >
                                    <span
                                        >Langkah 5: Hubungkan Akun ke Aplikasi MetaTrader 4
                                        (MT4)</span
                                    >
                                    <span
                                        v-if="isStepCompleted('step_5')"
                                        class="py-0.2 rounded-full border border-purple-500/20 bg-purple-500/10 px-2 text-[10px] text-purple-700 dark:text-purple-300"
                                    >
                                        Selesai
                                    </span>
                                </h4>
                                <p class="text-muted-foreground text-xs">
                                    Login ke MT4 untuk memverifikasi dana modal telah masuk dan siap
                                    dipasangi algoritma trading oleh tim YZD Center.
                                </p>
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            <a
                                :href="route('member.education.article', 'panduan-login-aplikasi-mt4')"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-muted-foreground hover:text-foreground bg-muted border-border inline-flex items-center gap-1 rounded-lg border px-2.5 py-1 text-[11px] font-semibold"
                            >
                                <BookOpen class="h-3 w-3" />
                                <span>Panduan Login MT4</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- STEP 6: Hubungkan ke Myfxbook & Pendaftaran Final Tier 3 -->
                <div
                    class="space-y-4 rounded-xl border p-4 transition-colors"
                    :class="
                        isStepCompleted('step_6')
                            ? 'border-purple-500/40 bg-purple-500/5'
                            : 'border-border bg-muted/20'
                    "
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-start gap-3">
                            <button
                                type="button"
                                @click="toggleStep('step_6')"
                                class="mt-0.5 text-purple-600 transition-colors hover:text-purple-700 focus:outline-hidden"
                            >
                                <CheckCircle2
                                    v-if="isStepCompleted('step_6')"
                                    class="h-5 w-5 fill-purple-500/20"
                                />
                                <Circle v-else class="text-muted-foreground h-5 w-5" />
                            </button>
                            <div class="space-y-1">
                                <h4
                                    class="text-foreground flex items-center gap-2 text-xs font-bold sm:text-sm"
                                >
                                    <span
                                        >Langkah 6: Hubungkan ke Myfxbook & Daftarkan Akun ke YZD
                                        Center</span
                                    >
                                    <span
                                        v-if="isStepCompleted('step_6')"
                                        class="py-0.2 rounded-full border border-purple-500/20 bg-purple-500/10 px-2 text-[10px] text-purple-700 dark:text-purple-300"
                                    >
                                        Siap Dikirim
                                    </span>
                                </h4>
                                <p class="text-muted-foreground text-xs">
                                    Hubungkan akun MT4 XM Anda ke Myfxbook untuk transparansi
                                    kinerja bulanan, lalu selesaikan pendaftaran akun ke sistem YZD
                                    Center.
                                </p>
                            </div>
                        </div>

                        <div class="flex shrink-0 flex-wrap items-center justify-end gap-2">
                            <a
                                href="https://www.myfxbook.com"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-primary-600 hover:text-primary-700 bg-primary-500/10 border-primary-500/20 inline-flex items-center gap-1 rounded-lg border px-2.5 py-1 text-[11px] font-semibold"
                            >
                                <ExternalLink class="h-3 w-3" />
                                <span>Buka Myfxbook</span>
                            </a>
                            <a
                                :href="route('member.education.article', 'panduan-menghubungkan-myfxbook')"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-muted-foreground hover:text-foreground bg-muted border-border inline-flex items-center gap-1 rounded-lg border px-2.5 py-1 text-[11px] font-semibold"
                            >
                                <BookOpen class="h-3 w-3" />
                                <span>Panduan Myfxbook</span>
                            </a>
                        </div>
                    </div>

                    <!-- FORM FINAL TERINTEGRASI TIER 3 -->
                    <div class="bg-card border-border space-y-4 rounded-xl border p-4">
                        <div class="grid grid-cols-1 gap-3 text-xs sm:grid-cols-2">
                            <div class="space-y-1 sm:col-span-2">
                                <label class="text-foreground block text-xs font-semibold">
                                    Tautan Publik Portofolio Myfxbook
                                    <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="form.myfxbook_url"
                                    type="url"
                                    placeholder="Contoh: https://www.myfxbook.com/members/nama/akun/123456"
                                    @input="triggerAutoSave"
                                    class="bg-background border-input focus:ring-primary-500 h-9 w-full rounded-lg border px-3 font-mono text-xs focus:ring-2 focus:outline-none"
                                />
                                <p
                                    v-if="form.errors.myfxbook_url"
                                    class="text-[10px] text-rose-500"
                                >
                                    {{ form.errors.myfxbook_url }}
                                </p>
                            </div>

                            <div class="space-y-1">
                                <label class="text-foreground block text-xs font-semibold">
                                    Tanggal Mulai Trading <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="form.tanggal_mulai_trading"
                                    type="date"
                                    @input="triggerAutoSave"
                                    class="bg-background border-input focus:ring-primary-500 h-9 w-full rounded-lg border px-3 text-xs focus:ring-2 focus:outline-none"
                                />
                                <p
                                    v-if="form.errors.tanggal_mulai_trading"
                                    class="text-[10px] text-rose-500"
                                >
                                    {{ form.errors.tanggal_mulai_trading }}
                                </p>
                            </div>
                        </div>

                        <!-- KHUSUS AKUN PERTAMA: REKENING BANK & SURAT PERJANJIAN -->
                        <div v-if="!hasExisting" class="border-border/70 space-y-3 border-t pt-3">
                            <div class="flex items-center gap-1.5">
                                <Shield class="h-4 w-4 text-purple-600" />
                                <h5
                                    class="text-foreground text-xs font-bold tracking-wider uppercase"
                                >
                                    Kelengkapan Akun Pertama (Rekening & Perjanjian Kemitraan)
                                </h5>
                            </div>

                            <div class="grid grid-cols-1 gap-3 text-xs sm:grid-cols-3">
                                <div class="space-y-1">
                                    <label class="text-foreground block text-xs font-semibold">
                                        Nama Bank <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.nama_bank"
                                        type="text"
                                        placeholder="Contoh: BCA / Mandiri"
                                        @input="triggerAutoSave"
                                        class="bg-background border-input focus:ring-primary-500 h-9 w-full rounded-lg border px-3 text-xs focus:ring-2 focus:outline-none"
                                    />
                                    <p
                                        v-if="form.errors.nama_bank"
                                        class="text-[10px] text-rose-500"
                                    >
                                        {{ form.errors.nama_bank }}
                                    </p>
                                </div>

                                <div class="space-y-1">
                                    <label class="text-foreground block text-xs font-semibold">
                                        Nomor Rekening Bank <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.nomor_rekening"
                                        type="text"
                                        placeholder="Nomor rekening Anda"
                                        @input="triggerAutoSave"
                                        class="bg-background border-input focus:ring-primary-500 h-9 w-full rounded-lg border px-3 font-mono text-xs font-bold focus:ring-2 focus:outline-none"
                                    />
                                    <p
                                        v-if="form.errors.nomor_rekening"
                                        class="text-[10px] text-rose-500"
                                    >
                                        {{ form.errors.nomor_rekening }}
                                    </p>
                                </div>

                                <div class="space-y-1">
                                    <label class="text-foreground block text-xs font-semibold">
                                        Atas Nama Rekening <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.atas_nama_rekening"
                                        type="text"
                                        placeholder="Nama pemilik rekening"
                                        @input="triggerAutoSave"
                                        class="bg-background border-input focus:ring-primary-500 h-9 w-full rounded-lg border px-3 text-xs focus:ring-2 focus:outline-none"
                                    />
                                    <p
                                        v-if="form.errors.atas_nama_rekening"
                                        class="text-[10px] text-rose-500"
                                    >
                                        {{ form.errors.atas_nama_rekening }}
                                    </p>
                                </div>
                            </div>

                            <!-- Unduh & Upload Perjanjian -->
                            <div
                                class="border-border/80 bg-muted/30 space-y-3 rounded-xl border p-3.5"
                            >
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="space-y-0.5">
                                        <span class="text-foreground block text-xs font-bold">
                                            Surat Perjanjian Kemitraan Tier 3 (Bermaterai 10.000)
                                            <span class="text-rose-500">*</span>
                                        </span>
                                        <p class="text-muted-foreground text-[11px]">
                                            Unduh draf perjanjian otomatis, tanda tangani di atas
                                            materai, lalu unggah foto/scan berkasnya.
                                        </p>
                                    </div>
                                    <AppButton
                                        type="custom"
                                        variant="outline"
                                        size="sm"
                                        :icon="Download"
                                        label="Unduh Draf Dokumen"
                                        @click="downloadAgreementTemplate"
                                    />
                                </div>

                                <div class="flex items-center gap-2">
                                    <input
                                        type="file"
                                        accept="image/jpeg,image/png,image/jpg,application/pdf"
                                        @change="handleFileUpload"
                                        class="text-muted-foreground file:bg-primary-600 hover:file:bg-primary-700 block w-full cursor-pointer text-xs file:mr-3 file:rounded-lg file:border-0 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white"
                                    />
                                </div>
                                <p
                                    v-if="form.errors.dokumen_perjanjian"
                                    class="text-[10px] text-rose-500"
                                >
                                    {{ form.errors.dokumen_perjanjian }}
                                </p>
                            </div>
                        </div>

                        <!-- Tombol Submit Akhir Tier 3 -->
                        <div
                            class="border-border flex items-center justify-end gap-2 border-t pt-2"
                        >
                            <AppButton
                                type="submit"
                                label="Kirim Pendaftaran Akun Tier 3"
                                :loading="form.processing || isCompressing"
                                :disabled="
                                    form.processing ||
                                    isCompressing ||
                                    !form.nomor_akun_trading ||
                                    !form.myfxbook_url
                                "
                                @click="submitAccount"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dialog Konfirmasi Mulai Ulang / Reset Flow -->
        <AppConfirmDialog
            :show="showResetConfirm"
            :loading="isResetting"
            title="Reset Alur Panduan Investasi?"
            message="Seluruh progres centang checklist dan draft yang belum tersimpan akan dihapus, dan Anda dapat memulai kembali dari Langkah 1."
            confirm-label="Ya, Mulai Ulang"
            cancel-label="Batal"
            type="danger"
            @confirm="executeReset"
            @cancel="showResetConfirm = false"
        />
    </div>
</template>
