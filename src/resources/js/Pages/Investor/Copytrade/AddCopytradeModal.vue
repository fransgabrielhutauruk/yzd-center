<script setup>
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
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
    pengaturan: {
        type: Object,
        default: () => ({}),
    },
    hasExisting: {
        type: Boolean,
        default: false,
    },
    defaultTier: {
        type: String,
        default: 'tier_1',
    },
});

const emit = defineEmits(['close']);

const getTodayIso = () => new Date().toISOString().split('T')[0];

const form = useForm({
    tier_investasi: props.defaultTier || 'tier_1',
    nomor_akun_trading: '',
    modal_riil: '',
    tanggal_mulai_trading: getTodayIso(),
    nama_bank: '',
    nomor_rekening: '',
    atas_nama_rekening: '',
    server_broker: '',
    master_password: '',
    investor_password: '',
    url_mql5: '',
    dokumen_perjanjian: null,
});

watch(
    () => props.show,
    (isOpen) => {
        if (isOpen && props.defaultTier) {
            form.tier_investasi = props.defaultTier;
        }
    }
);

const selectedFile = ref(null);
const filePreview = ref(null);
const isPdf = ref(false);
const isCompressing = ref(false);
const qualityWarning = ref(false);

const minModal = computed(() => {
    if (form.tier_investasi === 'tier_3') return Number(props.pengaturan?.tier_3_min) || 300000;
    if (form.tier_investasi === 'tier_2') return Number(props.pengaturan?.tier_2_min) || 5000;
    return Number(props.pengaturan?.tier_1_min) || 100;
});

const tierDescription = computed(() => {
    switch (form.tier_investasi) {
        case 'tier_3':
            return props.pengaturan?.tier_3_desc || 'Akun Dedicated VPS dengan kontrol master terisolasi. Rekomendasi modal riil besar.';
        case 'tier_2':
            return props.pengaturan?.tier_2_desc || 'Akun Standard Broker XM dengan eksekusi market ultra cepat.';
        case 'tier_1':
        default:
            return props.pengaturan?.tier_1_desc || 'Akun Cent Broker HFM untuk copytrade standar dengan toleransi risiko terjaga.';
    }
});

const tierPdfUrl = computed(() => {
    switch (form.tier_investasi) {
        case 'tier_3':
            return props.pengaturan?.tier_3_pdf_url;
        case 'tier_2':
            return props.pengaturan?.tier_2_pdf_url;
        case 'tier_1':
        default:
            return props.pengaturan?.tier_1_pdf_url;
    }
});

const handleFileChange = async (event) => {
    const file = event.target.files?.[0];
    if (!file) return;

    if (file.size > 10 * 1024 * 1024) {
        toast.error('Ukuran berkas melebihi batas maksimal 10 MB.');
        return;
    }

    if (file.type === 'application/pdf') {
        form.dokumen_perjanjian = file;
        selectedFile.value = file;
        filePreview.value = null;
        isPdf.value = true;
        qualityWarning.value = false;
        form.clearErrors('dokumen_perjanjian');
        return;
    }

    // Silent image compression background execution
    isCompressing.value = true;
    try {
        const result = await compressImageFile(file, { maxWidth: 2400 });
        if (result) {
            form.dokumen_perjanjian = result.file;
            selectedFile.value = result.file;
            filePreview.value = result.previewUrl;
            isPdf.value = false;
            qualityWarning.value = result.isQualityWarning || false;
            form.clearErrors('dokumen_perjanjian');
        }
    } catch {
        toast.error('Gagal memproses berkas gambar.');
    } finally {
        isCompressing.value = false;
    }
};

const clearSelectedFile = () => {
    selectedFile.value = null;
    filePreview.value = null;
    isPdf.value = false;
    qualityWarning.value = false;
    form.dokumen_perjanjian = null;
};

const resetAndClose = () => {
    form.reset();
    form.clearErrors();
    clearSelectedFile();
    emit('close');
};

const downloadPrefilledTemplate = () => {
    const params = new URLSearchParams({
        nomor_akun_trading: form.nomor_akun_trading || '',
        modal_riil: form.modal_riil || '',
        tanggal_mulai_trading: form.tanggal_mulai_trading || '',
        nama_bank: form.nama_bank || '',
        nomor_rekening: form.nomor_rekening || '',
        atas_nama_rekening: form.atas_nama_rekening || '',
        server_broker: form.server_broker || '',
        master_password: form.master_password || '',
        investor_password: form.investor_password || '',
    });

    window.location.href = `/investor/copytrade/template-perjanjian/${form.tier_investasi}?${params.toString()}`;
    toast.info('Mengunduh dokumen surat perjanjian yang terisi otomatis...');
};

const submit = () => {
    if (!props.hasExisting && !form.dokumen_perjanjian) {
        form.setError('dokumen_perjanjian', 'Surat Perjanjian Kemitraan bermaterai wajib diunggah pada pendaftaran pertama.');
        return;
    }

    form.post('/investor/copytrade', {
        preserveScroll: true,
        onSuccess: () => {
            resetAndClose();
        },
    });
};

watch(() => props.show, (newVal) => {
    if (!newVal) {
        clearSelectedFile();
        form.reset();
        form.clearErrors();
    } else {
        form.tanggal_mulai_trading = getTodayIso();
    }
});
</script>

<template>
    <AppModal
        :show="show"
        title="Tambah Akun Copytrade"
        max-width="3xl"
        @close="resetAndClose"
    >
        <form @submit.prevent="submit" class="space-y-6 p-1 max-h-[75vh] overflow-y-auto">
            <!-- Informasi Umum / Callout -->
            <div class="flex items-start gap-2.5 rounded-xl border border-sky-500/30 bg-sky-500/10 p-3.5 text-xs text-sky-800 dark:text-sky-300">
                <Info class="h-4 w-4 shrink-0 mt-0.5" />
                <div class="space-y-1">
                    <p class="font-bold">Ketentuan Pendaftaran Akun Copytrade:</p>
                    <p class="text-[11px] leading-relaxed">
                        Pastikan Anda telah membuat akun trading pada broker yang ditentukan dan mengisi modal riil sesuai batas minimal tier yang dipilih.
                    </p>
                </div>
            </div>

            <!-- Bagian 1: Pemilihan Tier & Detail Akun Trading MT4 -->
            <div class="space-y-4">
                <h3 class="text-foreground text-xs font-bold uppercase tracking-wider border-b border-border/60 pb-2">
                    1. Data Akun Trading MT4
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Pilihan Tier -->
                    <div class="space-y-1.5 sm:col-span-2">
                        <label class="text-foreground block text-xs font-semibold">
                            Pilihan Tier Investasi <span class="text-destructive">*</span>
                        </label>
                        <select
                            v-model="form.tier_investasi"
                            class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border px-3 py-2 text-xs font-medium focus:ring-2 focus:outline-none"
                        >
                            <option value="tier_1">Tier 1 - Cent (Minimal ${{ props.pengaturan?.tier_1_min || 100 }})</option>
                            <option value="tier_2">Tier 2 - Standard (Minimal ${{ props.pengaturan?.tier_2_min || 5000 }})</option>
                            <option value="tier_3">Tier 3 - Private VPS (Minimal ${{ props.pengaturan?.tier_3_min || 300000 }})</option>
                        </select>
                        <p class="text-muted-foreground text-[11px] whitespace-pre-line leading-relaxed">
                            {{ tierDescription }}
                        </p>
                        <div v-if="tierPdfUrl" class="pt-1">
                            <a
                                :href="tierPdfUrl"
                                target="_blank"
                                class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-primary-600 dark:text-primary-400 hover:underline"
                            >
                                <Download class="h-3.5 w-3.5 shrink-0" />
                                <span>Unduh Brosur/Panduan PDF Tier Ini</span>
                            </a>
                        </div>
                        <p v-if="form.errors.tier_investasi" class="text-destructive text-xs">
                            {{ form.errors.tier_investasi }}
                        </p>
                    </div>

                    <!-- Nomor Akun Trading -->
                    <div class="space-y-1.5">
                        <label class="text-foreground block text-xs font-semibold">
                            Nomor Akun Trading MT4 <span class="text-destructive">*</span>
                        </label>
                        <input
                            v-model="form.nomor_akun_trading"
                            type="text"
                            placeholder="Contoh: 12345678"
                            class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border px-3 py-2 font-mono text-xs focus:ring-2 focus:outline-none"
                        />
                        <p v-if="form.errors.nomor_akun_trading" class="text-destructive text-xs">
                            {{ form.errors.nomor_akun_trading }}
                        </p>
                    </div>

                    <!-- Modal Riil USD -->
                    <div class="space-y-1.5">
                        <label class="text-foreground block text-xs font-semibold">
                            Modal Riil Deposit (USD) <span class="text-destructive">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-muted-foreground font-mono text-xs font-bold">$</span>
                            <input
                                v-model="form.modal_riil"
                                type="number"
                                step="0.01"
                                :min="minModal"
                                :placeholder="String(minModal)"
                                class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border pl-7 pr-3 py-2 font-mono text-xs focus:ring-2 focus:outline-none"
                            />
                        </div>
                        <p class="text-muted-foreground text-[11px]">
                            Minimal modal untuk tier ini adalah ${{ minModal.toLocaleString() }}
                        </p>
                        <p v-if="form.errors.modal_riil" class="text-destructive text-xs">
                            {{ form.errors.modal_riil }}
                        </p>
                    </div>

                    <!-- Tanggal Mulai Trading -->
                    <div class="space-y-1.5 sm:col-span-2">
                        <label class="text-foreground block text-xs font-semibold">
                            Tanggal Rencana Mulai Trading <span class="text-destructive">*</span>
                        </label>
                        <input
                            v-model="form.tanggal_mulai_trading"
                            type="date"
                            class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border px-3 py-2 text-xs focus:ring-2 focus:outline-none"
                        />
                        <p v-if="form.errors.tanggal_mulai_trading" class="text-destructive text-xs">
                            {{ form.errors.tanggal_mulai_trading }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Bagian 2: Data Rekening Penarikan Dana (Withdrawal) -->
            <div class="space-y-4">
                <h3 class="text-foreground text-xs font-bold uppercase tracking-wider border-b border-border/60 pb-2">
                    2. Rekening Bank Penarikan Dana (Withdrawal)
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
                            class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border px-3 py-2 text-xs focus:ring-2 focus:outline-none"
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
                            class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border px-3 py-2 font-mono text-xs focus:ring-2 focus:outline-none"
                        />
                        <p v-if="form.errors.nomor_rekening" class="text-destructive text-xs">
                            {{ form.errors.nomor_rekening }}
                        </p>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-foreground block text-xs font-semibold">
                            Atas Nama Rekening <span class="text-destructive">*</span>
                        </label>
                        <input
                            v-model="form.atas_nama_rekening"
                            type="text"
                            placeholder="Sesuai nama di buku tabungan"
                            class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border px-3 py-2 text-xs focus:ring-2 focus:outline-none"
                        />
                        <p v-if="form.errors.atas_nama_rekening" class="text-destructive text-xs">
                            {{ form.errors.atas_nama_rekening }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Bagian 3: Khusus Tier 3 (Private VPS Credentials) -->
            <div v-if="form.tier_investasi === 'tier_3'" class="space-y-4 rounded-xl border border-amber-500/30 bg-amber-500/5 p-4 transition-all">
                <div class="flex items-center gap-2 border-b border-amber-500/20 pb-2">
                    <Server class="h-4 w-4 text-amber-600 dark:text-amber-400" />
                    <h3 class="text-amber-800 dark:text-amber-300 text-xs font-bold uppercase tracking-wider">
                        3. Kredensial Khusus Tier 3 (Private VPS Auto-Trading)
                    </h3>
                </div>
                <p class="text-muted-foreground text-[11px]">
                    Kredensial password akan dienkripsi secara aman menggunakan standar kriptografi AES-256 pada database server.
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="text-foreground block text-xs font-semibold">
                            Server Broker <span class="text-destructive">*</span>
                        </label>
                        <input
                            v-model="form.server_broker"
                            type="text"
                            placeholder="Contoh: XMGlobal-Real 55"
                            class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border px-3 py-2 text-xs focus:ring-2 focus:outline-none"
                        />
                        <p v-if="form.errors.server_broker" class="text-destructive text-xs">
                            {{ form.errors.server_broker }}
                        </p>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-foreground block text-xs font-semibold">
                            Tautan Sinyal MQL5 (Opsional)
                        </label>
                        <input
                            v-model="form.url_mql5"
                            type="url"
                            placeholder="https://www.mql5.com/..."
                            class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border px-3 py-2 text-xs focus:ring-2 focus:outline-none"
                        />
                        <p v-if="form.errors.url_mql5" class="text-destructive text-xs">
                            {{ form.errors.url_mql5 }}
                        </p>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-foreground block text-xs font-semibold">
                            Master Password MT4 <span class="text-destructive">*</span>
                        </label>
                        <input
                            v-model="form.master_password"
                            type="password"
                            autocomplete="new-password"
                            placeholder="Password utama MT4"
                            class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border px-3 py-2 text-xs focus:ring-2 focus:outline-none"
                        />
                        <p v-if="form.errors.master_password" class="text-destructive text-xs">
                            {{ form.errors.master_password }}
                        </p>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-foreground block text-xs font-semibold">
                            Investor Password MT4 <span class="text-destructive">*</span>
                        </label>
                        <input
                            v-model="form.investor_password"
                            type="password"
                            autocomplete="new-password"
                            placeholder="Password read-only MT4"
                            class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border px-3 py-2 text-xs focus:ring-2 focus:outline-none"
                        />
                        <p v-if="form.errors.investor_password" class="text-destructive text-xs">
                            {{ form.errors.investor_password }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Bagian 4: Surat Perjanjian Kemitraan (Wajib pada pendaftaran pertama) -->
            <div class="space-y-4">
                <div class="flex items-center justify-between border-b border-border/60 pb-2">
                    <h3 class="text-foreground text-xs font-bold uppercase tracking-wider">
                        {{ form.tier_investasi === 'tier_3' ? '4.' : '3.' }} Berkas Surat Perjanjian Kemitraan
                        <span v-if="!hasExisting" class="text-destructive">*</span>
                        <span v-else class="text-muted-foreground font-normal lowercase">(opsional)</span>
                    </h3>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="downloadPrefilledTemplate"
                        class="text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 inline-flex items-center gap-1.5 text-xs font-semibold h-8"
                    >
                        <Download class="h-3.5 w-3.5" />
                        <span>Unduh Template Terisi Otomatis (DOCX)</span>
                    </Button>
                </div>

                <div class="rounded-lg border border-border/80 bg-muted/20 p-2.5 flex items-start gap-2 text-[11px] text-muted-foreground">
                    <CheckCircle2 class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5" />
                    <span>
                        <strong>Pengisian Otomatis:</strong> Dokumen DOCX yang diunduh akan otomatis terisi dengan data profil Anda (Nama, NIK, TTL, Alamat, HP, Ahli Waris) serta data formulir trading yang Anda ketik di atas.
                    </span>
                </div>

                <p v-if="!hasExisting" class="text-muted-foreground text-xs leading-relaxed">
                    Karena ini pendaftaran akun copytrade pertama Anda, silakan unduh template dokumen di atas, tandatangani di atas materai, lalu unggah hasil foto / scan dokumen (PDF, JPG, atau PNG).
                </p>
                <p v-else class="text-muted-foreground text-xs leading-relaxed">
                    Anda sudah memiliki perjanjian terdaftar pada akun pertama. Unggah berkas baru hanya jika Anda ingin memperbarui klausul perjanjian khusus.
                </p>

                <!-- Box Unggah Berkas -->
                <div class="space-y-3">
                    <div
                        v-if="!selectedFile"
                        class="border-input hover:border-primary-500 bg-background/50 group relative cursor-pointer rounded-xl border-2 border-dashed p-6 text-center transition-all"
                    >
                        <input
                            type="file"
                            accept="image/jpeg,image/png,image/jpg,application/pdf"
                            @change="handleFileChange"
                            class="absolute inset-0 z-10 h-full w-full cursor-pointer opacity-0"
                        />
                        <div v-if="isCompressing" class="flex flex-col items-center justify-center space-y-2 py-2">
                            <Loader2 class="text-primary-600 h-6 w-6 animate-spin" />
                            <p class="text-foreground text-xs font-semibold">Mengompresi dan memproses berkas...</p>
                        </div>
                        <div v-else class="flex flex-col items-center justify-center space-y-2 py-2">
                            <div class="flex h-11 w-11 items-center justify-center rounded-full bg-primary-500/10 text-primary-600 dark:text-primary-400 transition-transform group-hover:scale-110">
                                <UploadCloud class="h-5 w-5" />
                            </div>
                            <p class="text-foreground text-xs font-semibold">
                                Klik atau seret berkas Surat Perjanjian ke sini
                            </p>
                            <p class="text-muted-foreground text-[11px]">
                                Format berkas: PDF, JPG, JPEG, PNG (Maks. 10 MB)
                            </p>
                        </div>
                    </div>

                    <!-- Selected File State Display -->
                    <div
                        v-else
                        class="bg-muted/30 border-border flex items-center justify-between rounded-xl border p-3 text-xs"
                    >
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20">
                                <FileText class="h-5 w-5" />
                            </div>
                            <div class="min-w-0 space-y-0.5">
                                <p class="text-foreground font-semibold truncate">{{ selectedFile.name }}</p>
                                <p class="text-muted-foreground text-[11px]">
                                    {{ isPdf ? 'Dokumen PDF' : 'Gambar Berkas' }} &bull; Siap diunggah
                                </p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="clearSelectedFile"
                            class="text-muted-foreground hover:text-destructive rounded-lg p-1.5 hover:bg-background transition-colors"
                            title="Hapus / Pilih Ulang"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <p v-if="form.errors.dokumen_perjanjian" class="text-destructive text-xs">
                        {{ form.errors.dokumen_perjanjian }}
                    </p>
                </div>
            </div>

            <!-- Modal Action Buttons -->
            <div class="flex items-center justify-end gap-3 border-t border-border pt-4">
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    @click="resetAndClose"
                >
                    Batal
                </Button>
                <Button
                    type="submit"
                    size="sm"
                    :disabled="form.processing || isCompressing"
                    class="bg-primary-600 hover:bg-primary-700 text-white gap-2"
                >
                    <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                    <span>{{ form.processing ? 'Menyimpan Akun...' : 'Simpan Akun Copytrade' }}</span>
                </Button>
            </div>
        </form>
    </AppModal>
</template>
