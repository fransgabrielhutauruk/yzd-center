<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import {
    Download,
    Loader2,
    UploadCloud,
    FileText,
    CheckCircle2,
    AlertTriangle,
    X,
    Server,
    Shield,
    Key,
    Info,
    ExternalLink,
    Eye,
    EyeOff,
} from 'lucide-vue-next';
import AppModal from '@/Components/Common/AppModal.vue';
import { Button } from '@/Components/ui/button';
import { compressImageFile } from '@/lib/imageCompression';
import { toast } from 'vue-sonner';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    hasExisting: {
        type: Boolean,
        default: false,
    },
    defaultTier: {
        type: String,
        default: '',
    },
});

const TIER_2_MIN = 5000;
const TIER_3_MIN = 300000;

const emit = defineEmits(['close']);

const getTodayIso = () => new Date().toISOString().split('T')[0];

const form = useForm({
    tier_investasi: props.defaultTier || '',
    nomor_akun_trading: '',
    modal_riil: '',
    tanggal_mulai_trading: getTodayIso(),
    nama_bank: '',
    nomor_rekening: '',
    atas_nama_rekening: '',
    server_broker: '',
    master_password: '',
    investor_password: '',
    dokumen_perjanjian: null,
});

watch(
    () => props.show,
    (isOpen) => {
        if (isOpen) {
            form.tier_investasi = props.defaultTier || '';
        }
    }
);

const selectedFile = ref(null);
const filePreview = ref(null);
const isPdf = ref(false);
const isCompressing = ref(false);
const qualityWarning = ref(false);
const showMasterPassword = ref(false);
const showInvestorPassword = ref(false);

const tierRangeInfo = computed(() => {
    const tier2Min = TIER_2_MIN;
    const tier3Min = TIER_3_MIN;

    switch (form.tier_investasi) {
        case 'tier_3':
            return {
                min: tier3Min,
                max: null,
                rangeText: `Minimal $${tier3Min.toLocaleString()}`,
                placeholder: String(tier3Min),
            };
        case 'tier_2':
            return {
                min: tier2Min,
                max: tier3Min,
                rangeText: `$${tier2Min.toLocaleString()} s/d $${tier3Min.toLocaleString()}`,
                placeholder: String(tier2Min),
            };
        case 'tier_1':
            return {
                min: 0.01,
                max: tier2Min - 0.01,
                rangeText: `Di bawah $${tier2Min.toLocaleString()}`,
                placeholder: 'Contoh: 1000',
            };
        default:
            return {
                min: null,
                max: null,
                rangeText: 'Pilih tier investasi terlebih dahulu',
                placeholder: 'Pilih tier dahulu...',
            };
    }
});

const minModal = computed(() => tierRangeInfo.value.min);

const isTier3 = computed(() => form.tier_investasi === 'tier_3');

const handleFileSelect = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    if (file.type === 'application/pdf') {
        isPdf.value = true;
        selectedFile.value = file;
        filePreview.value = null;
        form.dokumen_perjanjian = file;
        return;
    }

    if (!['image/jpeg', 'image/png', 'image/jpg'].includes(file.type)) {
        toast.error('Format berkas tidak didukung. Harap unggah berkas JPG, PNG, atau PDF.');
        return;
    }

    isPdf.value = false;
    isCompressing.value = true;

    try {
        const compressed = await compressImageFile(file, {
            maxSizeMB: 1.0,
            maxWidthOrHeight: 1920,
        });

        selectedFile.value = compressed;
        form.dokumen_perjanjian = compressed;
        filePreview.value = URL.createObjectURL(compressed);

        if (compressed.size > 800 * 1024) {
            qualityWarning.value = true;
        } else {
            qualityWarning.value = false;
        }
    } catch {
        toast.error('Gagal memproses gambar. Menggunakan berkas asli.');
        selectedFile.value = file;
        form.dokumen_perjanjian = file;
        filePreview.value = URL.createObjectURL(file);
    } finally {
        isCompressing.value = false;
    }
};

const removeFile = () => {
    selectedFile.value = null;
    filePreview.value = null;
    form.dokumen_perjanjian = null;
    isPdf.value = false;
    qualityWarning.value = false;
};

const isDownloadingTemplate = ref(false);

const downloadAgreementTemplate = () => {
    if (!form.tier_investasi) {
        toast.error('Harap pilih tier investasi terlebih dahulu.');
        return;
    }

    isDownloadingTemplate.value = true;
    try {
        const url = route('investor.investments.template', form.tier_investasi);
        const params = new URLSearchParams({
            nomor_akun_trading: form.nomor_akun_trading || '',
            modal_riil: form.modal_riil || '',
            nama_bank: form.nama_bank || '',
            nomor_rekening: form.nomor_rekening || '',
            atas_nama_rekening: form.atas_nama_rekening || '',
            tanggal_mulai_trading: form.tanggal_mulai_trading || '',
            server_broker: form.server_broker || '',
            master_password: form.master_password || '',
            investor_password: form.investor_password || '',
        });

        window.open(`${url}?${params.toString()}`, '_blank');
        toast.success('Mengunduh draf template surat perjanjian Word (.docx)...');
    } catch {
        toast.error('Gagal mengunduh template surat perjanjian.');
    } finally {
        setTimeout(() => {
            isDownloadingTemplate.value = false;
        }, 1500);
    }
};

const submit = () => {
    form.post(route('investor.investments.store'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            resetAndClose();
        },
    });
};

const resetAndClose = () => {
    form.reset();
    form.tier_investasi = props.defaultTier || '';
    form.clearErrors();
    showMasterPassword.value = false;
    showInvestorPassword.value = false;
    removeFile();
    emit('close');
};
</script>

<template>
    <AppModal
        :show="show"
        title="Tambah Akun Investasi"
        max-width="3xl"
        @close="resetAndClose"
    >
        <form @submit.prevent="submit" class="space-y-6 p-1 max-h-[75vh] overflow-y-auto">
            <!-- Informasi Panduan Terpadu -->
            <div class="flex items-start justify-between gap-3 rounded-xl border border-sky-500/30 bg-sky-500/10 p-4 text-xs text-sky-900 dark:text-sky-200">
                <div class="flex items-start gap-2.5">
                    <Info class="h-4 w-4 shrink-0 mt-0.5 text-sky-600 dark:text-sky-400" />
                    <div class="space-y-1">
                        <p class="font-bold text-foreground">Ketentuan Alokasi Modal Tier Investasi:</p>
                        <p class="text-[11px] leading-relaxed text-muted-foreground">
                            Pilih tier sesuai alokasi modal: 
                            <span class="font-semibold text-foreground">Tier 1</span> (&lt; $5.000), 
                            <span class="font-semibold text-foreground">Tier 2</span> ($5.000 - $300.000), atau 
                            <span class="font-semibold text-foreground">Tier 3</span> (&ge; $300.000).
                        </p>
                    </div>
                </div>

                <Link
                    href="/investor/education/help?kategori=copytrade-mt4"
                    target="_blank"
                    class="shrink-0 inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-sky-500/30 bg-card hover:bg-sky-500/10 text-xs font-bold text-sky-700 dark:text-sky-300 transition-colors shadow-xs"
                >
                    <span>Panduan Tier ↗</span>
                </Link>
            </div>

            <!-- Bagian 1: Pemilihan Tier & Detail Akun Trading MT4 -->
            <div class="space-y-4">
                <h3 class="text-foreground text-xs font-bold uppercase tracking-wider border-b border-border/60 pb-2">
                    Data Investasi
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Pilihan Tier -->
                    <div class="space-y-1.5 sm:col-span-2">
                        <label class="text-foreground block text-xs font-semibold">
                            Pilihan Tier Investasi <span class="text-destructive">*</span>
                        </label>
                        <select
                            v-model="form.tier_investasi"
                            class="bg-background border-input focus:ring-primary w-full rounded-lg border px-3 py-2 text-xs font-medium focus:ring-2 focus:outline-none"
                        >
                            <option value="" disabled>Pilih Tier Investasi</option>
                            <option value="tier_1">Tier 1 - Copytrade Cent Broker HFM (Di bawah ${{ TIER_2_MIN.toLocaleString() }})</option>
                            <option value="tier_2">Tier 2 - Copytrade Standard Broker XM (${{ TIER_2_MIN.toLocaleString() }} - ${{ TIER_3_MIN.toLocaleString() }})</option>
                            <option value="tier_3">Tier 3 - Direct Managed Trading Broker XM (Minimal ${{ TIER_3_MIN.toLocaleString() }})</option>
                        </select>
                        <p v-if="form.errors.tier_investasi" class="text-destructive text-xs">
                            {{ form.errors.tier_investasi }}
                        </p>
                    </div>

                    <!-- Nomor Akun Trading / Investor Copier -->
                    <div class="space-y-1.5">
                        <label class="text-foreground block text-xs font-semibold">
                            {{ isTier3 ? 'Nomor Akun Trading' : 'Nomor Akun Investor (Copier)' }} <span class="text-destructive">*</span>
                        </label>
                        <input
                            v-model="form.nomor_akun_trading"
                            type="text"
                            placeholder="Contoh: 12345678"
                            class="bg-background border-input focus:ring-primary w-full rounded-lg border px-3 py-2 font-mono text-xs focus:ring-2 focus:outline-none"
                        />
                        <p v-if="form.errors.nomor_akun_trading" class="text-destructive text-xs">
                            {{ form.errors.nomor_akun_trading }}
                        </p>
                    </div>

                    <!-- Modal Riil USD -->
                    <div class="space-y-1.5">
                        <label class="text-foreground block text-xs font-semibold">
                            Modal Deposit (USD) <span class="text-destructive">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-muted-foreground font-mono text-xs font-bold">$</span>
                            <input
                                v-model="form.modal_riil"
                                type="number"
                                step="0.01"
                                :min="tierRangeInfo.min"
                                :max="tierRangeInfo.max"
                                :placeholder="tierRangeInfo.placeholder"
                                class="bg-background border-input focus:ring-primary w-full rounded-lg border pl-7 pr-3 py-2 font-mono text-xs focus:ring-2 focus:outline-none"
                            />
                        </div>
                        <p class="text-muted-foreground text-[11px]">
                            Range alokasi modal untuk tier ini: <span class="font-semibold text-foreground">{{ tierRangeInfo.rangeText }}</span>
                        </p>
                        <p v-if="form.errors.modal_riil" class="text-destructive text-xs">
                            {{ form.errors.modal_riil }}
                        </p>
                    </div>

                    <!-- Tanggal Mulai Investasi/Copytrade -->
                    <div class="space-y-1.5 sm:col-span-2">
                        <label class="text-foreground block text-xs font-semibold">
                            Tanggal Mulai Investasi/Copytrade <span class="text-destructive">*</span>
                        </label>
                        <input
                            v-model="form.tanggal_mulai_trading"
                            type="date"
                            class="bg-background border-input focus:ring-primary w-full rounded-lg border px-3 py-2 text-xs focus:ring-2 focus:outline-none"
                        />
                        <p v-if="form.errors.tanggal_mulai_trading" class="text-destructive text-xs">
                            {{ form.errors.tanggal_mulai_trading }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Bagian 2: Data Rekening Penarikan Dana - Hanya Saat Pertama Kali Tambah Akun -->
            <div v-if="!hasExisting" class="space-y-4">
                <h3 class="text-foreground text-xs font-bold uppercase tracking-wider border-b border-border/60 pb-2">
                    Rekening Bank Penarikan Dana
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="space-y-1.5">
                        <label class="text-foreground block text-xs font-semibold">
                            Nama Bank <span class="text-destructive">*</span>
                        </label>
                        <input
                            v-model="form.nama_bank"
                            type="text"
                            placeholder="Contoh: BCA, Mandiri"
                            class="bg-background border-input focus:ring-primary w-full rounded-lg border px-3 py-2 text-xs focus:ring-2 focus:outline-none"
                        />
                        <p v-if="form.errors.nama_bank" class="text-destructive text-xs">
                            {{ form.errors.nama_bank }}
                        </p>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-foreground block text-xs font-semibold">
                            Nomor Rekening <span class="text-destructive">*</span>
                        </label>
                        <input
                            v-model="form.nomor_rekening"
                            type="text"
                            placeholder="Nomor rekening bank"
                            class="bg-background border-input focus:ring-primary w-full rounded-lg border px-3 py-2 font-mono text-xs focus:ring-2 focus:outline-none"
                        />
                        <p v-if="form.errors.nomor_rekening" class="text-destructive text-xs">
                            {{ form.errors.nomor_rekening }}
                        </p>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-foreground block text-xs font-semibold">
                            Nama Pemilik Rekening <span class="text-destructive">*</span>
                        </label>
                        <input
                            v-model="form.atas_nama_rekening"
                            type="text"
                            placeholder="Nama pemilik rekening"
                            class="bg-background border-input focus:ring-primary w-full rounded-lg border px-3 py-2 text-xs focus:ring-2 focus:outline-none"
                        />
                        <p v-if="form.errors.atas_nama_rekening" class="text-destructive text-xs">
                            {{ form.errors.atas_nama_rekening }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Bagian: Kredensial Khusus Tier 3 (Managed Trading / Dedicated VPS) -->
            <div v-if="isTier3" class="space-y-4 rounded-xl border border-amber-500/30 bg-amber-500/5 p-4">
                <div class="flex items-center gap-2 border-b border-amber-500/20 pb-2">
                    <h3 class="text-foreground text-xs font-bold uppercase tracking-wider">
                        Kredensial Akun Trading Khusus Tier 3 (Direct Trading / VPS)
                    </h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="space-y-1.5">
                        <label class="text-foreground block text-xs font-semibold">
                            Server Broker <span class="text-destructive">*</span>
                        </label>
                        <input
                            v-model="form.server_broker"
                            type="text"
                            placeholder="Contoh: XMGlobal-Real 50"
                            class="bg-background border-input focus:ring-primary w-full rounded-lg border px-3 py-2 text-xs focus:ring-2 focus:outline-none"
                        />
                        <p v-if="form.errors.server_broker" class="text-destructive text-xs">
                            {{ form.errors.server_broker }}
                        </p>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-foreground block text-xs font-semibold">
                            Master Password MT4 <span class="text-destructive">*</span>
                        </label>
                        <div class="relative">
                            <input
                                v-model="form.master_password"
                                :type="showMasterPassword ? 'text' : 'password'"
                                autocomplete="new-password"
                                placeholder="Kata sandi eksekusi trading"
                                class="bg-background border-input focus:ring-primary w-full rounded-lg border py-2 pl-3 pr-10 font-mono text-xs focus:ring-2 focus:outline-none"
                            />
                            <button
                                type="button"
                                @click="showMasterPassword = !showMasterPassword"
                                class="text-muted-foreground hover:text-foreground absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer"
                                tabindex="-1"
                                :title="showMasterPassword ? 'Sembunyikan password' : 'Lihat password'"
                            >
                                <EyeOff v-if="showMasterPassword" class="h-4 w-4" />
                                <Eye v-else class="h-4 w-4" />
                            </button>
                        </div>
                        <p v-if="form.errors.master_password" class="text-destructive text-xs">
                            {{ form.errors.master_password }}
                        </p>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-foreground block text-xs font-semibold">
                            Investor Password MT4 <span class="text-destructive">*</span>
                        </label>
                        <div class="relative">
                            <input
                                v-model="form.investor_password"
                                :type="showInvestorPassword ? 'text' : 'password'"
                                autocomplete="new-password"
                                placeholder="Kata sandi read-only"
                                class="bg-background border-input focus:ring-primary w-full rounded-lg border py-2 pl-3 pr-10 font-mono text-xs focus:ring-2 focus:outline-none"
                            />
                            <button
                                type="button"
                                @click="showInvestorPassword = !showInvestorPassword"
                                class="text-muted-foreground hover:text-foreground absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer"
                                tabindex="-1"
                                :title="showInvestorPassword ? 'Sembunyikan password' : 'Lihat password'"
                            >
                                <EyeOff v-if="showInvestorPassword" class="h-4 w-4" />
                                <Eye v-else class="h-4 w-4" />
                            </button>
                        </div>
                        <p v-if="form.errors.investor_password" class="text-destructive text-xs">
                            {{ form.errors.investor_password }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Bagian: Surat Perjanjian Kemitraan (Bermaterai) - Hanya Saat Pertama Kali Tambah Akun -->
            <div v-if="!hasExisting" class="space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-border/60 pb-2">
                    <h3 class="text-foreground text-xs font-bold uppercase tracking-wider">
                        Berkas Surat Perjanjian Investasi
                        <span class="text-destructive">*</span>
                    </h3>

                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        :disabled="isDownloadingTemplate"
                        @click="downloadAgreementTemplate"
                        class="gap-1.5 text-xs text-primary cursor-pointer self-start sm:self-auto"
                    >
                        <Loader2 v-if="isDownloadingTemplate" class="h-3.5 w-3.5 animate-spin" />
                        <Download v-else class="h-3.5 w-3.5" />
                        <span>Unduh Template Surat (.docx)</span>
                    </Button>
                </div>

                <div class="space-y-3">
                    <div
                        v-if="!selectedFile"
                        class="border-border hover:bg-muted/30 relative flex flex-col items-center justify-center rounded-xl border-2 border-dashed p-6 text-center transition-colors cursor-pointer"
                        @click="$refs.fileInput.click()"
                    >
                        <input
                            ref="fileInput"
                            type="file"
                            accept="image/jpeg,image/png,image/jpg,application/pdf"
                            class="hidden"
                            @change="handleFileSelect"
                        />
                        <div class="p-3 bg-primary/10 rounded-full text-primary mb-2">
                            <UploadCloud class="h-6 w-6" />
                        </div>
                        <p class="text-foreground text-xs font-semibold">
                            Klik untuk unggah Surat Perjanjian (Foto/Scan PDF)
                        </p>
                        <p class="text-muted-foreground text-[11px] mt-1">
                            Format JPG, PNG, atau PDF. Wajib dibubuhi tanda tangan & meterai Rp 10.000.
                        </p>
                    </div>

                    <!-- Pratinjau Berkas Terpilih -->
                    <div v-else class="bg-muted/30 border-border flex items-center justify-between rounded-xl border p-3">
                        <div class="flex items-center gap-3 overflow-hidden">
                            <div v-if="filePreview" class="h-12 w-12 rounded-lg border overflow-hidden shrink-0">
                                <img :src="filePreview" class="h-full w-full object-cover" alt="Preview" />
                            </div>
                            <div v-else class="p-2.5 bg-primary/10 rounded-lg text-primary shrink-0">
                                <FileText class="h-6 w-6" />
                            </div>
                            <div class="truncate">
                                <p class="text-foreground text-xs font-semibold truncate">{{ selectedFile.name }}</p>
                                <p class="text-muted-foreground text-[11px]">
                                    {{ (selectedFile.size / 1024).toFixed(1) }} KB
                                    <span v-if="isCompressing" class="text-primary italic ml-1">Mengompres...</span>
                                </p>
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="removeFile"
                            class="text-muted-foreground hover:text-destructive p-1 rounded-md"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <p v-if="form.errors.dokumen_perjanjian" class="text-destructive text-xs">
                        {{ form.errors.dokumen_perjanjian }}
                    </p>
                </div>
            </div>

            <!-- Footer Modal Actions -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-border">
                <Button
                    type="button"
                    variant="outline"
                    @click="resetAndClose"
                    :disabled="form.processing"
                    class="cursor-pointer"
                >
                    Batal
                </Button>
                <Button
                    type="submit"
                    class="bg-primary hover:bg-primary/90 text-primary-foreground gap-2 cursor-pointer"
                    :disabled="form.processing || isCompressing"
                >
                    <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                    <CheckCircle2 v-else class="h-4 w-4" />
                    <span>Daftarkan Akun Investasi</span>
                </Button>
            </div>
        </form>
    </AppModal>
</template>
