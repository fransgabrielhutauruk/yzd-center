<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import InvestorLayout from '@/Layouts/InvestorLayout.vue';
import AppModal from '@/Components/Common/AppModal.vue';
import { Button } from '@/Components/ui/button';
import { compressImageFile, formatBytes } from '@/lib/imageCompression';
import { toast } from 'vue-sonner';
import {
    FileCheck,
    Clock,
    AlertTriangle,
    XCircle,
    CheckCircle2,
    Shield,
    User,
    Users,
    CreditCard,
    Phone,
    MapPin,
    Mail,
    Eye,
    EyeOff,
    UploadCloud,
    Loader2,
    Sparkles,
    FileText,
    File as FileIcon,
    AlertCircle,
    Info,
    Check,
    Edit3,
    TrendingUp,
    Building2,
    Copy,
    CheckSquare,
    ShieldCheck,
    Pencil,
} from 'lucide-vue-next';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    ahli_waris: {
        type: Object,
        default: null,
    },
    akun_copytrade: {
        type: Object,
        default: null,
    },
    dokumen: {
        type: Object,
        required: true,
    },
    is_kyc_approved: {
        type: Boolean,
        default: false,
    },
});

// Modal Preview Berkas
const previewModal = ref({
    isOpen: false,
    title: '',
    url: '',
    isPdf: false,
});

const openPreview = (title, url, mimeType) => {
    previewModal.value = {
        isOpen: true,
        title,
        url,
        isPdf: mimeType === 'application/pdf',
    };
};

const closePreview = () => {
    previewModal.value.isOpen = false;
};

// State Unggah Ulang KTP
const ktpForm = useForm({
    file: null,
});
const ktpProcessing = ref(false);
const ktpInfo = ref(null);
const showKtpReupload = ref(false);

const canReuploadKtp = computed(() => {
    if (!props.dokumen?.ktp) return false;
    if (props.dokumen.ktp.status === 'approved') return false;
    return (
        props.dokumen.ktp.status === 'revision_needed' ||
        (props.dokumen.ktp.status === 'pending' && props.dokumen.ktp.is_quality_warning) ||
        showKtpReupload.value
    );
});

const handleKtpChange = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    ktpProcessing.value = true;
    try {
        const result = await compressImageFile(file);
        if (result) {
            ktpForm.file = result.file;
            ktpInfo.value = {
                name: file.name,
                isQualityWarning: result.isQualityWarning || false,
                warningMessage: result.warningMessage || null,
            };
            if (result.isQualityWarning) {
                toast.warning(
                    'Peringatan: Berkas foto KTP terindikasi resolusi rendah atau sedikit buram.'
                );
            }
        }
    } catch (e) {
        toast.error('Gagal memproses foto KTP.');
    } finally {
        ktpProcessing.value = false;
    }
};

const submitKtpReupload = () => {
    if (!ktpForm.file) {
        toast.error('Pilih berkas foto KTP baru terlebih dahulu.');
        return;
    }

    ktpForm.post('/investor/kyc/reupload/ktp', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            ktpForm.reset();
            ktpInfo.value = null;
            showKtpReupload.value = false;
        },
    });
};

// State Unggah Ulang Perjanjian
const perjanjianForm = useForm({
    file: null,
});
const perjanjianProcessing = ref(false);
const perjanjianInfo = ref(null);
const showPerjanjianReupload = ref(false);

const canReuploadPerjanjian = computed(() => {
    if (!props.dokumen?.perjanjian) return false;
    if (props.dokumen.perjanjian.status === 'approved') return false;
    return (
        props.dokumen.perjanjian.status === 'revision_needed' ||
        (props.dokumen.perjanjian.status === 'pending' &&
            props.dokumen.perjanjian.is_quality_warning) ||
        showPerjanjianReupload.value
    );
});

const handlePerjanjianChange = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    perjanjianProcessing.value = true;
    try {
        const result = await compressImageFile(file);
        if (result) {
            perjanjianForm.file = result.file;
            perjanjianInfo.value = {
                name: file.name,
                isQualityWarning: result.isQualityWarning || false,
                warningMessage: result.warningMessage || null,
            };
            if (result.isQualityWarning) {
                toast.warning(
                    'Peringatan: Surat perjanjian terindikasi resolusi rendah atau sedikit buram.'
                );
            }
        }
    } catch (e) {
        toast.error('Gagal memproses berkas perjanjian.');
    } finally {
        perjanjianProcessing.value = false;
    }
};

const submitPerjanjianReupload = () => {
    if (!perjanjianForm.file) {
        toast.error('Pilih berkas perjanjian baru terlebih dahulu.');
        return;
    }

    perjanjianForm.post('/investor/kyc/reupload/perjanjian', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            perjanjianForm.reset();
            perjanjianInfo.value = null;
            showPerjanjianReupload.value = false;
        },
    });
};

const getStatusBadge = (status) => {
    switch (status) {
        case 'approved':
            return {
                label: 'Disetujui',
                bg: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/30',
                icon: CheckCircle2,
            };
        case 'revision_needed':
            return {
                label: 'Perlu Revisi',
                bg: 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/30',
                icon: AlertTriangle,
            };
        case 'rejected':
            return {
                label: 'Ditolak',
                bg: 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/30',
                icon: XCircle,
            };
        case 'pending':
        default:
            return {
                label: 'Menunggu Peninjauan',
                bg: 'bg-sky-500/10 text-sky-600 dark:text-sky-400 border-sky-500/30',
                icon: Clock,
            };
    }
};

const getTierBadge = (tier) => {
    const t = String(tier || '').toLowerCase();
    if (t === '1' || t === 'tier_1') {
        return {
            label: 'Tier 1 – Cent Account HFM',
            badge: 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/30',
        };
    }
    if (t === '2' || t === 'tier_2') {
        return {
            label: 'Tier 2 – Standard Account XM',
            badge: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/30',
        };
    }
    if (t === '3' || t === 'tier_3') {
        return {
            label: 'Tier 3 – Private VPS XM',
            badge: 'bg-violet-500/10 text-violet-600 dark:text-violet-400 border-violet-500/30',
        };
    }
    return {
        label: `Tier ${tier}`,
        badge: 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/30',
    };
};

const getCopytradeStatusBadge = (status) => {
    switch (status) {
        case 'active':
            return {
                label: 'Valid',
                badge: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-500/30',
                icon: CheckCircle2,
            };
        case 'suspended':
            return {
                label: 'Ditangguhkan',
                badge: 'bg-rose-500/10 text-rose-700 dark:text-rose-400 border-rose-500/30',
                icon: XCircle,
            };
        case 'rejected':
            return {
                label: 'Ditolak / Belum Follow',
                badge: 'bg-rose-500/10 text-rose-700 dark:text-rose-400 border-rose-500/30',
                icon: XCircle,
            };
        case 'pending':
        default:
            return {
                label: 'Menunggu Validasi',
                badge: 'bg-sky-500/10 text-sky-600 dark:text-sky-400 border-sky-500/30',
                icon: Clock,
            };
    }
};

// Clipboard helper
const copyToClipboard = async (text, label) => {
    if (!text) return;
    try {
        await navigator.clipboard.writeText(String(text));
        toast.success(`${label} berhasil disalin ke clipboard!`);
    } catch (e) {
        toast.error(`Gagal menyalin ${label}.`);
    }
};

// Toggle password visibility (Tier 3)
const showMasterPassword = ref(false);
const showInvestorPassword = ref(false);

// Modal State & Form Edit Akun Copy Trade & Rekening
const showAccountEditModal = ref(false);

const bankOptions = [
    'BCA',
    'Mandiri',
    'BNI',
    'BRI',
    'CIMB Niaga',
    'Permata',
    'Danamon',
    'BSI',
    'Panin',
    'Mega',
    'BTN',
    'BPD',
    'OCBC NISP',
    'Maybank',
    'Lainnya',
];
const selectedBankOption = ref('');
const customBankName = ref('');

const accountEditForm = useForm({
    nomor_akun_trading: '',
    server_broker: '',
    estimasi_modal: '',
    master_password: '',
    investor_password: '',
    nama_bank: '',
    nomor_rekening: '',
    atas_nama_rekening: '',
    confirm_follow_ack: true,
});

const handleCustomBankInput = (event) => {
    const val = event.target.value;
    customBankName.value = val;
    if (selectedBankOption.value === 'Lainnya') {
        accountEditForm.nama_bank = val;
    }
};

const handleBankSelectChange = (val) => {
    selectedBankOption.value = val;
    if (val === 'Lainnya') {
        accountEditForm.nama_bank = customBankName.value;
    } else {
        accountEditForm.nama_bank = val;
        customBankName.value = '';
    }
};

const openAccountEditModal = () => {
    if (!props.akun_copytrade) return;
    const akun = props.akun_copytrade;
    accountEditForm.nomor_akun_trading = akun.nomor_akun_trading || '';
    accountEditForm.server_broker = akun.server_broker || '';
    accountEditForm.estimasi_modal = akun.estimasi_modal || '';
    accountEditForm.master_password = akun.master_password || '';
    accountEditForm.investor_password = akun.investor_password || '';
    accountEditForm.nomor_rekening = akun.nomor_rekening || '';
    accountEditForm.atas_nama_rekening = akun.atas_nama_rekening || '';
    accountEditForm.confirm_follow_ack = true;

    // Sinkronisasi bank pilihan
    const currentBank = akun.nama_bank || '';
    if (bankOptions.filter((b) => b !== 'Lainnya').includes(currentBank)) {
        selectedBankOption.value = currentBank;
        customBankName.value = '';
        accountEditForm.nama_bank = currentBank;
    } else if (currentBank) {
        selectedBankOption.value = 'Lainnya';
        customBankName.value = currentBank;
        accountEditForm.nama_bank = currentBank;
    } else {
        selectedBankOption.value = '';
        customBankName.value = '';
        accountEditForm.nama_bank = '';
    }

    accountEditForm.clearErrors();
    showAccountEditModal.value = true;
};

const submitAccountEdit = () => {
    if (!accountEditForm.confirm_follow_ack) {
        toast.error('Harap centang konfirmasi follow master sebelum menyimpan.');
        return;
    }

    accountEditForm.post('/investor/kyc/account/update', {
        preserveScroll: true,
        onSuccess: () => {
            showAccountEditModal.value = false;
        },
        onError: () => {
            toast.error('Gagal memperbarui data akun trading. Periksa kembali isian formulir.');
        },
    });
};

// Form Cepat: Konfirmasi Sudah Follow Master
const confirmFollowForm = useForm({});
const submitConfirmFollow = () => {
    confirmFollowForm.post('/investor/kyc/account/confirm-follow', {
        preserveScroll: true,
    });
};

// Status Global Halaman
const globalStatus = computed(() => {
    const hasDocRevision =
        props.dokumen.ktp?.status === 'revision_needed' ||
        props.dokumen.perjanjian?.status === 'revision_needed';
    const isAccountRejected = props.akun_copytrade?.status === 'rejected';
    const isAccountSuspended = props.akun_copytrade?.status === 'suspended';

    if (hasDocRevision || isAccountRejected) {
        return {
            type: 'warning',
            label:
                isAccountRejected && !hasDocRevision
                    ? 'Akun Trading Ditolak / Belum Follow'
                    : 'Memerlukan Perbaikan Data',
            badge: 'border-amber-500/30 bg-amber-500/10 text-amber-700 dark:text-amber-300',
            icon: AlertTriangle,
        };
    }

    if (isAccountSuspended) {
        return {
            type: 'suspended',
            label: 'Akun Ditangguhkan',
            badge: 'border-rose-500/30 bg-rose-500/10 text-rose-600',
            icon: XCircle,
        };
    }

    const isKycDocsApproved = props.is_kyc_approved;
    const isAccountActive = props.akun_copytrade?.status === 'active';

    if (isKycDocsApproved && isAccountActive) {
        return {
            type: 'approved',
            label: 'Akun Terverifikasi Penuh',
            badge: 'border-emerald-500/30 bg-emerald-500/10 text-emerald-600',
            icon: CheckCircle2,
        };
    }

    if (isKycDocsApproved && !isAccountActive) {
        return {
            type: 'partial',
            label: 'Menunggu Validasi Copy Trade',
            badge: 'border-sky-500/30 bg-sky-500/10 text-sky-600',
            icon: Clock,
        };
    }

    return {
        type: 'pending',
        label: 'Dalam Proses Peninjauan',
        badge: 'border-sky-500/30 bg-sky-500/10 text-sky-600',
        icon: Clock,
    };
});
</script>

<template>
    <InvestorLayout title="Status & Verifikasi Legalitas">
        <div class="space-y-6">
            <!-- Header Page -->
            <div
                class="bg-card border-border flex flex-col justify-between gap-4 rounded-2xl border p-6 shadow-xs sm:flex-row sm:items-center"
            >
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <h1 class="text-foreground text-xl font-bold tracking-tight">
                            Status Verifikasi & Legalitas
                        </h1>
                    </div>
                    <p class="text-muted-foreground text-xs">
                        Informasi validasi identitas diri, dokumen, akun copytrade, dan data
                        perlindungan hak ahli waris.
                    </p>
                </div>

                <!-- Global Status Indicator -->
                <div>
                    <div
                        class="inline-flex items-center gap-2 rounded-full border px-3.5 py-1.5 text-xs font-semibold shadow-xs"
                        :class="globalStatus.badge"
                    >
                        <component :is="globalStatus.icon" class="h-4 w-4" />
                        <span>{{ globalStatus.label }}</span>
                    </div>
                </div>
            </div>

            <!-- Konten Utama: 2 Kolom -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Kolom Kiri: Ringkasan Data Pribadi & Ahli Waris (1 Col) -->
                <div class="space-y-6">
                    <!-- Kartu Data Identitas -->
                    <div class="bg-card border-border space-y-4 rounded-2xl border p-5 shadow-xs">
                        <div class="border-border flex items-center gap-2 border-b pb-3">
                            <User class="text-primary-600 h-4 w-4" />
                            <h2 class="text-foreground text-sm font-bold">Data Identitas Akun</h2>
                        </div>

                        <div class="space-y-3 text-xs">
                            <div>
                                <span class="text-muted-foreground block text-[11px]"
                                    >Nama Lengkap</span
                                >
                                <span class="text-foreground font-semibold">{{ user.name }}</span>
                            </div>

                            <div>
                                <span class="text-muted-foreground block text-[11px]"
                                    >Nomor NIK KTP</span
                                >
                                <span class="text-foreground font-mono font-semibold">{{
                                    user.nik
                                }}</span>
                            </div>

                            <div v-if="user.tempat_lahir || user.tanggal_lahir">
                                <span class="text-muted-foreground block text-[11px]"
                                    >Tempat, Tanggal Lahir</span
                                >
                                <span class="text-foreground font-medium">
                                    {{
                                        [user.tempat_lahir, user.tanggal_lahir]
                                            .filter(Boolean)
                                            .join(', ')
                                    }}
                                </span>
                            </div>

                            <div v-if="user.pekerjaan">
                                <span class="text-muted-foreground block text-[11px]"
                                    >Pekerjaan / Profesi</span
                                >
                                <span class="text-foreground font-medium">{{
                                    user.pekerjaan
                                }}</span>
                            </div>

                            <div>
                                <span class="text-muted-foreground block text-[11px]"
                                    >Alamat Email</span
                                >
                                <span class="text-foreground">{{ user.email }}</span>
                            </div>

                            <div>
                                <span class="text-muted-foreground block text-[11px]"
                                    >Nomor WhatsApp/HP</span
                                >
                                <span class="text-foreground">{{ user.phone }}</span>
                            </div>

                            <div>
                                <span class="text-muted-foreground block text-[11px]"
                                    >Alamat Domisili Sesuai KTP</span
                                >
                                <span class="text-foreground leading-relaxed">{{
                                    user.alamat
                                }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Kartu Ahli Waris -->
                    <div class="bg-card border-border space-y-4 rounded-2xl border p-5 shadow-xs">
                        <div class="border-border flex items-center gap-2 border-b pb-3">
                            <Users class="text-accent-500 h-4 w-4" />
                            <h2 class="text-foreground text-sm font-bold">
                                Perlindungan Ahli Waris
                            </h2>
                        </div>

                        <div v-if="ahli_waris" class="space-y-3 text-xs">
                            <div>
                                <span class="text-muted-foreground block text-[11px]"
                                    >Nama Ahli Waris</span
                                >
                                <span class="text-foreground font-semibold">{{
                                    ahli_waris.nama
                                }}</span>
                            </div>

                            <div>
                                <span class="text-muted-foreground block text-[11px]"
                                    >Hubungan Keluarga</span
                                >
                                <span
                                    class="bg-primary-500/10 text-primary-600 inline-block rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                >
                                    {{ ahli_waris.hubungan_keluarga }}
                                </span>
                            </div>

                            <div>
                                <span class="text-muted-foreground block text-[11px]"
                                    >Kontak Darurat</span
                                >
                                <span class="text-foreground">{{ ahli_waris.no_hp }}</span>
                            </div>

                            <div v-if="ahli_waris.alamat">
                                <span class="text-muted-foreground block text-[11px]"
                                    >Alamat Domisili Ahli Waris</span
                                >
                                <span class="text-foreground leading-relaxed">{{
                                    ahli_waris.alamat
                                }}</span>
                            </div>
                        </div>

                        <div v-else class="text-muted-foreground text-xs italic">
                            Data ahli waris belum terdata.
                        </div>
                    </div>
                </div>

                <!-- Kolom Kanan: Akun CopyTrade, Status & Re-upload Berkas Dokumen (2 Col) -->
                <div class="space-y-6">
                    <!-- Kartu Akun Copy Trade & Perbankan -->
                    <div class="bg-card border-border space-y-4 rounded-2xl border p-5 shadow-xs">
                        <div class="border-border flex items-center justify-between border-b pb-3">
                            <div class="flex items-center gap-2">
                                <TrendingUp class="h-4 w-4 text-emerald-600" />
                                <h2 class="text-foreground text-sm font-bold">Akun Copy Trade</h2>
                            </div>
                            <span
                                v-if="akun_copytrade"
                                class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-semibold"
                                :class="getCopytradeStatusBadge(akun_copytrade.status).badge"
                            >
                                <component
                                    :is="getCopytradeStatusBadge(akun_copytrade.status).icon"
                                    class="h-3.5 w-3.5"
                                />
                                {{ getCopytradeStatusBadge(akun_copytrade.status).label }}
                            </span>
                        </div>

                        <div v-if="akun_copytrade" class="space-y-3.5 text-xs">
                            <div>
                                <span class="text-muted-foreground block text-[11px]"
                                    >Paket Layanan Trading</span
                                >
                                <span
                                    class="mt-1 inline-block rounded-md border px-2 py-0.5 text-xs font-semibold"
                                    :class="getTierBadge(akun_copytrade.tier).badge"
                                >
                                    {{ getTierBadge(akun_copytrade.tier).label }}
                                </span>
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <span class="text-muted-foreground block text-[11px]"
                                        >Broker & Tipe</span
                                    >
                                    <span class="text-foreground font-semibold">
                                        {{ akun_copytrade.broker }} ({{ akun_copytrade.tipe_akun }})
                                    </span>
                                </div>
                                <div>
                                    <span class="text-muted-foreground block text-[11px]"
                                        >No. Akun MT4</span
                                    >
                                    <div class="mt-0.5 flex items-center gap-1.5">
                                        <span class="text-foreground font-mono font-semibold">
                                            {{ akun_copytrade.nomor_akun_trading }}
                                        </span>
                                        <button
                                            type="button"
                                            class="text-muted-foreground hover:text-foreground rounded p-0.5 transition-colors"
                                            title="Salin Nomor Akun MT4"
                                            @click="
                                                copyToClipboard(
                                                    akun_copytrade.nomor_akun_trading,
                                                    'Nomor Akun MT4'
                                                )
                                            "
                                        >
                                            <Copy class="h-3.5 w-3.5" />
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div v-if="akun_copytrade.server_broker">
                                <span class="text-muted-foreground block text-[11px]"
                                    >Server Broker</span
                                >
                                <div class="mt-0.5 flex items-center gap-1.5">
                                    <span class="text-foreground font-mono font-medium">
                                        {{ akun_copytrade.server_broker }}
                                    </span>
                                    <button
                                        type="button"
                                        class="text-muted-foreground hover:text-foreground rounded p-0.5 transition-colors"
                                        title="Salin Server Broker"
                                        @click="
                                            copyToClipboard(
                                                akun_copytrade.server_broker,
                                                'Server Broker'
                                            )
                                        "
                                    >
                                        <Copy class="h-3.5 w-3.5" />
                                    </button>
                                </div>
                            </div>

                            <!-- Kredensial Keamanan MT4 (Khusus Tier 3 VPS) -->
                            <div
                                v-if="
                                    (akun_copytrade.tier === 'tier_3' ||
                                        akun_copytrade.tier === '3') &&
                                    (akun_copytrade.master_password ||
                                        akun_copytrade.investor_password)
                                "
                                class="space-y-2 rounded-xl border border-violet-500/20 bg-violet-500/5 p-3"
                            >
                                <span class="text-foreground block text-[11px] font-bold"
                                    >Kredensial MT4 Private VPS</span
                                >
                                <div class="space-y-2 text-xs">
                                    <div v-if="akun_copytrade.master_password">
                                        <span class="text-muted-foreground block text-[10px]"
                                            >Master Password (VPS Setup)</span
                                        >
                                        <div class="mt-0.5 flex items-center gap-1.5 font-mono">
                                            <span class="text-foreground font-semibold">
                                                {{
                                                    showMasterPassword
                                                        ? akun_copytrade.master_password
                                                        : '••••••••••••'
                                                }}
                                            </span>
                                            <button
                                                type="button"
                                                class="text-muted-foreground hover:text-foreground rounded p-0.5 transition-colors"
                                                :title="
                                                    showMasterPassword ? 'Sembunyikan' : 'Tampilkan'
                                                "
                                                @click="showMasterPassword = !showMasterPassword"
                                            >
                                                <component
                                                    :is="showMasterPassword ? EyeOff : Eye"
                                                    class="h-3.5 w-3.5"
                                                />
                                            </button>
                                            <button
                                                type="button"
                                                class="text-muted-foreground hover:text-foreground rounded p-0.5 transition-colors"
                                                title="Salin Master Password"
                                                @click="
                                                    copyToClipboard(
                                                        akun_copytrade.master_password,
                                                        'Master Password'
                                                    )
                                                "
                                            >
                                                <Copy class="h-3.5 w-3.5" />
                                            </button>
                                        </div>
                                    </div>
                                    <div v-if="akun_copytrade.investor_password">
                                        <span class="text-muted-foreground block text-[10px]"
                                            >Investor Password (Read-Only)</span
                                        >
                                        <div class="mt-0.5 flex items-center gap-1.5 font-mono">
                                            <span class="text-foreground font-semibold">
                                                {{
                                                    showInvestorPassword
                                                        ? akun_copytrade.investor_password
                                                        : '••••••••••••'
                                                }}
                                            </span>
                                            <button
                                                type="button"
                                                class="text-muted-foreground hover:text-foreground rounded p-0.5 transition-colors"
                                                :title="
                                                    showInvestorPassword
                                                        ? 'Sembunyikan'
                                                        : 'Tampilkan'
                                                "
                                                @click="
                                                    showInvestorPassword = !showInvestorPassword
                                                "
                                            >
                                                <component
                                                    :is="showInvestorPassword ? EyeOff : Eye"
                                                    class="h-3.5 w-3.5"
                                                />
                                            </button>
                                            <button
                                                type="button"
                                                class="text-muted-foreground hover:text-foreground rounded p-0.5 transition-colors"
                                                title="Salin Investor Password"
                                                @click="
                                                    copyToClipboard(
                                                        akun_copytrade.investor_password,
                                                        'Investor Password'
                                                    )
                                                "
                                            >
                                                <Copy class="h-3.5 w-3.5" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <span class="text-muted-foreground block text-[11px]"
                                        >Estimasi Modal Awal</span
                                    >
                                    <span class="text-foreground font-semibold">
                                        ${{
                                            Number(akun_copytrade.estimasi_modal).toLocaleString(
                                                'en-US'
                                            )
                                        }}
                                    </span>
                                </div>
                                <div>
                                    <span class="text-muted-foreground block text-[11px]"
                                        >Mulai Kelola</span
                                    >
                                    <span class="text-foreground">
                                        {{ akun_copytrade.tanggal_mulai }}
                                    </span>
                                </div>
                            </div>

                            <div v-if="akun_copytrade.tanggal_selesai">
                                <span class="text-muted-foreground block text-[11px]"
                                    >Selesai Kelola</span
                                >
                                <span class="text-foreground">
                                    {{ akun_copytrade.tanggal_selesai }}
                                </span>
                            </div>

                            <div class="border-border/60 border-t pt-2.5">
                                <span class="text-muted-foreground block text-[11px] font-medium"
                                    >Rekening Penarikan Dana (Withdrawal)</span
                                >
                                <div class="mt-1 flex items-center gap-2">
                                    <Building2 class="text-muted-foreground h-3.5 w-3.5 shrink-0" />
                                    <span class="text-foreground font-semibold">
                                        {{ akun_copytrade.nama_bank }}
                                    </span>
                                    <span class="text-foreground font-mono">
                                        {{ akun_copytrade.nomor_rekening }}
                                    </span>
                                    <button
                                        type="button"
                                        class="text-muted-foreground hover:text-foreground rounded p-0.5 transition-colors"
                                        title="Salin Nomor Rekening"
                                        @click="
                                            copyToClipboard(
                                                akun_copytrade.nomor_rekening,
                                                'Nomor Rekening'
                                            )
                                        "
                                    >
                                        <Copy class="h-3.5 w-3.5" />
                                    </button>
                                </div>
                                <span class="text-muted-foreground block pl-5 text-[11px]">
                                    a.n. {{ akun_copytrade.atas_nama_rekening }}
                                </span>
                            </div>

                            <!-- Catatan Verifikator / Admin -->
                            <div
                                v-if="
                                    akun_copytrade.catatan_admin &&
                                    akun_copytrade.status !== 'active'
                                "
                                :class="[
                                    'space-y-1 rounded-xl border p-3 text-xs',
                                    akun_copytrade.status === 'rejected'
                                        ? 'border-rose-500/30 bg-rose-500/10 text-rose-800 dark:text-rose-200'
                                        : 'border-amber-500/30 bg-amber-500/10 text-amber-800 dark:text-amber-200',
                                ]"
                            >
                                <div class="flex items-center gap-1.5 font-bold">
                                    <component
                                        :is="
                                            akun_copytrade.status === 'rejected'
                                                ? XCircle
                                                : AlertTriangle
                                        "
                                        class="h-3.5 w-3.5 shrink-0"
                                    />
                                    <span>{{
                                        akun_copytrade.status === 'rejected'
                                            ? 'Catatan Status Akun Trading (Ditolak / Belum Follow):'
                                            : 'Catatan Administrator:'
                                    }}</span>
                                </div>
                                <p class="pl-5 text-[11px] leading-relaxed">
                                    {{ akun_copytrade.catatan_admin }}
                                </p>
                            </div>

                            <!-- Tombol Aksi Investor: Perbaiki / Konfirmasi -->
                            <div
                                v-if="
                                    akun_copytrade.status === 'pending' ||
                                    akun_copytrade.status === 'rejected'
                                "
                                class="border-border/60 space-y-2 border-t pt-3"
                            >
                                <Button
                                    type="button"
                                    class="bg-primary-600 hover:bg-primary-700 w-full py-2 text-xs font-semibold text-white"
                                    @click="openAccountEditModal"
                                >
                                    <Pencil class="h-3.5 w-3.5" />
                                    <span>{{
                                        akun_copytrade.status === 'rejected'
                                            ? 'Perbaiki Data'
                                            : 'Ubah Data'
                                    }}</span>
                                </Button>

                                <Button
                                    v-if="
                                        akun_copytrade.status !== 'active' &&
                                        (akun_copytrade.status === 'rejected' ||
                                            akun_copytrade.catatan_admin)
                                    "
                                    type="button"
                                    variant="outline"
                                    :disabled="confirmFollowForm.processing"
                                    class="w-full text-xs font-medium"
                                    @click="submitConfirmFollow"
                                >
                                    <Loader2
                                        v-if="confirmFollowForm.processing"
                                        class="h-3.5 w-3.5 animate-spin"
                                    />
                                    <CheckCircle2 v-else class="h-3.5 w-3.5" />
                                    <span>Konfirmasi Sudah Follow Master (Cek Ulang)</span>
                                </Button>
                            </div>
                        </div>

                        <div v-else class="text-muted-foreground text-xs italic">
                            Data akun copy trade belum terdata.
                        </div>
                    </div>

                    <!-- ========================================== -->
                    <!-- DOKUMEN 1: FOTO KTP                        -->
                    <!-- ========================================== -->
                    <div class="bg-card border-border space-y-4 rounded-2xl border p-6 shadow-xs">
                        <div
                            class="border-border flex flex-row justify-between gap-2 border-b pb-3 sm:items-center"
                        >
                            <div class="flex items-center gap-2">
                                <CreditCard class="text-primary-600 h-4 w-4" />
                                <h3 class="text-foreground text-sm font-bold">Foto KTP</h3>
                            </div>

                            <!-- Status Badge -->
                            <div
                                v-if="dokumen.ktp"
                                :class="[
                                    'inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-semibold',
                                    getStatusBadge(dokumen.ktp.status).bg,
                                ]"
                            >
                                <component
                                    :is="getStatusBadge(dokumen.ktp.status).icon"
                                    class="h-3.5 w-3.5"
                                />
                                <span>{{ getStatusBadge(dokumen.ktp.status).label }}</span>
                            </div>
                        </div>

                        <!-- Info Berkas Terunggah -->
                        <div v-if="dokumen.ktp" class="space-y-4">
                            <div
                                class="bg-muted/40 border-border flex flex-col items-start justify-between gap-4 rounded-xl border p-3 sm:flex-row sm:items-center"
                            >
                                <div class="min-w-0 flex-1 space-y-1 text-xs">
                                    <p
                                        class="text-foreground font-semibold [overflow-wrap:anywhere] break-words"
                                    >
                                        {{ dokumen.ktp.file_name }}
                                    </p>
                                    <p class="text-muted-foreground text-[11px]">
                                        <span v-if="dokumen.ktp.verified_at">
                                            &bull; Diverifikasi:
                                            {{
                                                new Date(
                                                    dokumen.ktp.verified_at
                                                ).toLocaleDateString('id-ID')
                                            }}</span
                                        >
                                    </p>
                                </div>

                                <div class="flex shrink-0 items-center gap-2">
                                    <Button
                                        v-if="
                                            dokumen.ktp.status === 'pending' &&
                                            !dokumen.ktp.is_quality_warning &&
                                            !showKtpReupload
                                        "
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        class="text-muted-foreground flex items-center gap-1.5 text-xs hover:text-white"
                                        @click="showKtpReupload = true"
                                    >
                                        <Edit3 class="h-3.5 w-3.5" />
                                        <span>Ganti Berkas</span>
                                    </Button>

                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        class="flex items-center gap-1.5 text-xs"
                                        @click="
                                            openPreview(
                                                'Foto KTP - ' + user.name,
                                                dokumen.ktp.preview_url,
                                                dokumen.ktp.mime_type
                                            )
                                        "
                                    >
                                        <Eye class="h-3.5 w-3.5" />
                                        <span>Lihat Berkas</span>
                                    </Button>
                                </div>
                            </div>

                            <!-- Quality Warning Notification -->
                            <div
                                v-if="
                                    dokumen.ktp.is_quality_warning &&
                                    dokumen.ktp.status !== 'approved'
                                "
                                class="flex items-start gap-3 rounded-xl border border-amber-500/30 bg-amber-500/10 p-4 text-xs text-amber-800 dark:text-amber-200"
                            >
                                <AlertCircle
                                    class="mt-0.5 h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400"
                                />
                                <div class="space-y-1">
                                    <p class="font-bold">Peringatan Kualitas Dokumen</p>
                                    <p class="leading-relaxed">
                                        Foto KTP yang Anda unggah terindikasi memiliki resolusi
                                        rendah atau sedikit buram. Agar proses verifikasi akun tidak
                                        terhambat, Anda disarankan mengunggah ulang foto baru yang
                                        lebih tajam.
                                    </p>
                                </div>
                            </div>

                            <!-- Catatan Verifikator -->
                            <div
                                v-if="
                                    dokumen.ktp.catatan_admin && dokumen.ktp.status !== 'approved'
                                "
                                class="bg-muted/70 border-border space-y-1 rounded-xl border p-3.5 text-xs"
                            >
                                <span class="text-foreground block font-bold">
                                    Catatan Administrator:
                                </span>
                                <p class="text-muted-foreground leading-relaxed italic">
                                    "{{ dokumen.ktp.catatan_admin }}"
                                </p>
                            </div>

                            <!-- FORM RE-UPLOAD KTP -->
                            <div
                                v-if="canReuploadKtp"
                                class="border-border mt-4 space-y-3 border-t pt-4"
                            >
                                <div class="flex items-center justify-between">
                                    <h4
                                        class="text-foreground text-xs font-bold tracking-wider uppercase"
                                    >
                                        {{
                                            dokumen.ktp.status === 'revision_needed'
                                                ? 'Unggah Ulang Foto KTP Revisi'
                                                : dokumen.ktp.is_quality_warning
                                                  ? 'Perbaiki & Unggah Ulang Foto KTP'
                                                  : 'Ganti Foto KTP'
                                        }}
                                    </h4>
                                    <button
                                        v-if="
                                            showKtpReupload &&
                                            dokumen.ktp.status === 'pending' &&
                                            !dokumen.ktp.is_quality_warning
                                        "
                                        type="button"
                                        class="text-muted-foreground hover:text-foreground text-xs"
                                        @click="showKtpReupload = false"
                                    >
                                        Batal
                                    </button>
                                </div>

                                <form @submit.prevent="submitKtpReupload" class="space-y-3">
                                    <div
                                        class="border-input hover:border-primary-500 bg-background/50 relative cursor-pointer rounded-xl border-2 border-dashed p-4 text-center transition-all"
                                    >
                                        <input
                                            type="file"
                                            accept="image/jpeg,image/png,image/jpg"
                                            @change="handleKtpChange"
                                            class="absolute inset-0 z-10 h-full w-full cursor-pointer opacity-0"
                                        />

                                        <div
                                            v-if="ktpProcessing"
                                            class="flex items-center justify-center gap-2 py-2 text-xs font-semibold"
                                        >
                                            <Loader2
                                                class="text-primary-600 h-4 w-4 animate-spin"
                                            />
                                            <span>Memeriksa berkas...</span>
                                        </div>

                                        <div v-else-if="ktpInfo" class="space-y-1.5 text-xs">
                                            <p
                                                class="text-foreground flex items-center justify-center gap-1.5 px-2 font-semibold break-all"
                                            >
                                                <Check class="h-4 w-4 shrink-0 text-emerald-600" />
                                                <span>{{ ktpInfo.name }}</span>
                                            </p>

                                            <!-- Peringatan Kualitas File Baru -->
                                            <div
                                                v-if="ktpInfo.isQualityWarning"
                                                class="mt-2 flex items-start gap-2 rounded-lg border border-amber-500/30 bg-amber-500/10 p-2.5 text-left text-xs text-amber-800 dark:text-amber-200"
                                            >
                                                <AlertCircle
                                                    class="mt-0.5 h-3.5 w-3.5 shrink-0 text-amber-600 dark:text-amber-400"
                                                />
                                                <p class="text-[11px] leading-relaxed">
                                                    {{
                                                        ktpInfo.warningMessage ||
                                                        'Foto baru ini terindikasi kurang tajam atau buram. Disarankan memilih foto lain yang lebih jelas.'
                                                    }}
                                                </p>
                                            </div>
                                        </div>

                                        <div
                                            v-else
                                            class="flex flex-col items-center justify-center space-y-1 py-2"
                                        >
                                            <UploadCloud class="text-primary-600 h-6 w-6" />
                                            <p class="text-foreground text-xs font-semibold">
                                                Klik untuk memilih foto KTP baru
                                            </p>
                                            <p class="text-muted-foreground text-[10px]">
                                                Maksimal 10 MB (JPG, JPEG, PNG)
                                            </p>
                                        </div>
                                    </div>

                                    <Button
                                        type="submit"
                                        :disabled="
                                            ktpForm.processing || !ktpForm.file || ktpProcessing
                                        "
                                        class="bg-primary-600 hover:bg-primary-700 w-full rounded-lg py-2 text-xs text-white"
                                    >
                                        <Loader2
                                            v-if="ktpForm.processing"
                                            class="mr-1 h-3.5 w-3.5 animate-spin"
                                        />
                                        <span>{{
                                            ktpForm.processing
                                                ? 'Mengunggah Berkas...'
                                                : dokumen.ktp.status === 'revision_needed'
                                                  ? 'Kirim Berkas KTP Revisi'
                                                  : 'Kirim Pembaruan Foto KTP'
                                        }}</span>
                                    </Button>
                                </form>
                            </div>
                        </div>

                        <div v-else class="text-muted-foreground text-xs italic">
                            Berkas KTP belum pernah diunggah.
                        </div>
                    </div>

                    <!-- ========================================== -->
                    <!-- DOKUMEN 2: SURAT PERJANJIAN BERMATERAI    -->
                    <!-- ========================================== -->
                    <div class="bg-card border-border space-y-4 rounded-2xl border p-6 shadow-xs">
                        <div
                            class="border-border flex flex-row justify-between gap-2 border-b pb-3 sm:items-center"
                        >
                            <div class="flex items-center gap-2">
                                <FileText class="h-4 w-4 text-emerald-600" />
                                <h3 class="text-foreground text-sm font-bold">Surat Perjanjian</h3>
                            </div>

                            <!-- Status Badge -->
                            <div
                                v-if="dokumen.perjanjian"
                                :class="[
                                    'inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-semibold',
                                    getStatusBadge(dokumen.perjanjian.status).bg,
                                ]"
                            >
                                <component
                                    :is="getStatusBadge(dokumen.perjanjian.status).icon"
                                    class="h-3.5 w-3.5"
                                />
                                <span>{{ getStatusBadge(dokumen.perjanjian.status).label }}</span>
                            </div>
                        </div>

                        <!-- Info Berkas Terunggah -->
                        <div v-if="dokumen.perjanjian" class="space-y-4">
                            <div
                                class="bg-muted/40 border-border flex flex-col items-start justify-between gap-4 rounded-xl border p-3 sm:flex-row sm:items-center"
                            >
                                <div class="min-w-0 flex-1 space-y-1 text-xs">
                                    <p
                                        class="text-foreground font-semibold [overflow-wrap:anywhere] break-words"
                                    >
                                        {{ dokumen.perjanjian.file_name }}
                                    </p>
                                    <p class="text-muted-foreground text-[11px]">
                                        <span v-if="dokumen.perjanjian.verified_at">
                                            &bull; Diverifikasi:
                                            {{
                                                new Date(
                                                    dokumen.perjanjian.verified_at
                                                ).toLocaleDateString('id-ID')
                                            }}</span
                                        >
                                    </p>
                                </div>

                                <div class="flex shrink-0 items-center gap-2">
                                    <Button
                                        v-if="
                                            dokumen.perjanjian.status === 'pending' &&
                                            !dokumen.perjanjian.is_quality_warning &&
                                            !showPerjanjianReupload
                                        "
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        class="text-muted-foreground flex items-center gap-1.5 text-xs hover:text-white"
                                        @click="showPerjanjianReupload = true"
                                    >
                                        <Edit3 class="h-3.5 w-3.5" />
                                        <span>Ganti Berkas</span>
                                    </Button>

                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        class="flex items-center gap-1.5 text-xs"
                                        @click="
                                            openPreview(
                                                'Surat Perjanjian - ' + user.name,
                                                dokumen.perjanjian.preview_url,
                                                dokumen.perjanjian.mime_type
                                            )
                                        "
                                    >
                                        <Eye class="h-3.5 w-3.5" />
                                        <span>Lihat Berkas</span>
                                    </Button>
                                </div>
                            </div>

                            <!-- Quality Warning Notification -->
                            <div
                                v-if="
                                    dokumen.perjanjian.is_quality_warning &&
                                    dokumen.perjanjian.status !== 'approved'
                                "
                                class="flex items-start gap-3 rounded-xl border border-amber-500/30 bg-amber-500/10 p-4 text-xs text-amber-800 dark:text-amber-200"
                            >
                                <AlertCircle
                                    class="mt-0.5 h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400"
                                />
                                <div class="space-y-1">
                                    <p class="font-bold">Peringatan Kualitas Dokumen</p>
                                    <p class="leading-relaxed">
                                        Berkas Surat Perjanjian yang Anda unggah terindikasi
                                        memiliki ketajaman rendah atau resolusi kecil. Agar proses
                                        verifikasi tidak terhambat, Anda disarankan mengunggah ulang
                                        berkas baru atau hasil scan PDF yang lebih jelas.
                                    </p>
                                </div>
                            </div>

                            <!-- Catatan Verifikator -->
                            <div
                                v-if="
                                    dokumen.perjanjian.catatan_admin &&
                                    dokumen.perjanjian.status !== 'approved'
                                "
                                class="bg-muted/70 border-border space-y-1 rounded-xl border p-3.5 text-xs"
                            >
                                <span class="text-foreground block font-bold">
                                    Catatan Administrator:
                                </span>
                                <p class="text-muted-foreground leading-relaxed italic">
                                    "{{ dokumen.perjanjian.catatan_admin }}"
                                </p>
                            </div>

                            <!-- FORM RE-UPLOAD PERJANJIAN -->
                            <div
                                v-if="canReuploadPerjanjian"
                                class="border-border mt-4 space-y-3 border-t pt-4"
                            >
                                <div class="flex items-center justify-between">
                                    <h4
                                        class="text-foreground text-xs font-bold tracking-wider uppercase"
                                    >
                                        {{
                                            dokumen.perjanjian.status === 'revision_needed'
                                                ? 'Unggah Ulang Surat Perjanjian Revisi'
                                                : dokumen.perjanjian.is_quality_warning
                                                  ? 'Perbaiki & Unggah Ulang Surat Perjanjian'
                                                  : 'Ganti Surat Perjanjian'
                                        }}
                                    </h4>
                                    <button
                                        v-if="
                                            showPerjanjianReupload &&
                                            dokumen.perjanjian.status === 'pending' &&
                                            !dokumen.perjanjian.is_quality_warning
                                        "
                                        type="button"
                                        class="text-muted-foreground hover:text-foreground text-xs"
                                        @click="showPerjanjianReupload = false"
                                    >
                                        Batal
                                    </button>
                                </div>

                                <form @submit.prevent="submitPerjanjianReupload" class="space-y-3">
                                    <div
                                        class="border-input hover:border-primary-500 bg-background/50 relative cursor-pointer rounded-xl border-2 border-dashed p-4 text-center transition-all"
                                    >
                                        <input
                                            type="file"
                                            accept="image/jpeg,image/png,image/jpg,application/pdf"
                                            @change="handlePerjanjianChange"
                                            class="absolute inset-0 z-10 h-full w-full cursor-pointer opacity-0"
                                        />

                                        <div
                                            v-if="perjanjianProcessing"
                                            class="flex items-center justify-center gap-2 py-2 text-xs font-semibold"
                                        >
                                            <Loader2
                                                class="text-primary-600 h-4 w-4 animate-spin"
                                            />
                                            <span>Memeriksa berkas...</span>
                                        </div>

                                        <div v-else-if="perjanjianInfo" class="space-y-1.5 text-xs">
                                            <p
                                                class="text-foreground flex items-center justify-center gap-1.5 px-2 font-semibold break-all"
                                            >
                                                <Check class="h-4 w-4 shrink-0 text-emerald-600" />
                                                <span>{{ perjanjianInfo.name }}</span>
                                            </p>

                                            <!-- Peringatan Kualitas File Baru -->
                                            <div
                                                v-if="perjanjianInfo.isQualityWarning"
                                                class="mt-2 flex items-start gap-2 rounded-lg border border-amber-500/30 bg-amber-500/10 p-2.5 text-left text-xs text-amber-800 dark:text-amber-200"
                                            >
                                                <AlertCircle
                                                    class="mt-0.5 h-3.5 w-3.5 shrink-0 text-amber-600 dark:text-amber-400"
                                                />
                                                <p class="text-[11px] leading-relaxed">
                                                    {{
                                                        perjanjianInfo.warningMessage ||
                                                        'Berkas baru ini terindikasi kurang tajam atau buram. Disarankan memilih foto atau scan PDF yang lebih jelas.'
                                                    }}
                                                </p>
                                            </div>
                                        </div>

                                        <div
                                            v-else
                                            class="flex flex-col items-center justify-center space-y-1 py-2"
                                        >
                                            <UploadCloud class="h-6 w-6 text-emerald-600" />
                                            <p class="text-foreground text-xs font-semibold">
                                                Klik untuk memilih berkas perjanjian baru
                                            </p>
                                            <p class="text-muted-foreground text-[10px]">
                                                Maksimal 10 MB (JPG, PNG, atau PDF)
                                            </p>
                                        </div>
                                    </div>

                                    <Button
                                        type="submit"
                                        :disabled="
                                            perjanjianForm.processing ||
                                            !perjanjianForm.file ||
                                            perjanjianProcessing
                                        "
                                        class="bg-primary-600 hover:bg-primary-700 w-full rounded-lg py-2 text-xs text-white"
                                    >
                                        <Loader2
                                            v-if="perjanjianForm.processing"
                                            class="mr-1 h-3.5 w-3.5 animate-spin"
                                        />
                                        <span>{{
                                            perjanjianForm.processing
                                                ? 'Mengunggah Berkas...'
                                                : dokumen.perjanjian.status === 'revision_needed'
                                                  ? 'Kirim Surat Perjanjian Revisi'
                                                  : 'Kirim Pembaruan Surat Perjanjian'
                                        }}</span>
                                    </Button>
                                </form>
                            </div>
                        </div>

                        <div v-else class="text-muted-foreground text-xs italic">
                            Berkas perjanjian belum pernah diunggah.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- MODAL PREVIEW DOKUMEN                      -->
        <!-- ========================================== -->
        <AppModal
            :show="previewModal.isOpen"
            :title="previewModal.title"
            max-width="3xl"
            @close="closePreview"
        >
            <div
                class="bg-muted/20 flex max-h-[70vh] min-h-[300px] items-center justify-center overflow-auto rounded-xl p-2"
            >
                <!-- PDF Viewer -->
                <iframe
                    v-if="previewModal.isPdf"
                    :src="previewModal.url"
                    class="border-border h-[65vh] w-full rounded-lg border"
                ></iframe>

                <!-- Image Viewer -->
                <img
                    v-else
                    :src="previewModal.url"
                    :alt="previewModal.title"
                    class="max-h-[65vh] max-w-full rounded-lg object-contain shadow-sm"
                />
            </div>

            <template #footer>
                <div class="flex w-full items-center justify-between">
                    <a
                        :href="previewModal.url"
                        target="_blank"
                        download
                        class="text-primary-600 text-xs font-semibold hover:underline"
                    >
                        Unduh Berkas
                    </a>
                    <Button
                        type="button"
                        variant="secondary"
                        size="sm"
                        class="text-xs"
                        @click="closePreview"
                    >
                        Tutup
                    </Button>
                </div>
            </template>
        </AppModal>

        <!-- ========================================== -->
        <!-- MODAL EDIT AKUN TRADING & REKENING         -->
        <!-- ========================================== -->
        <AppModal
            :show="showAccountEditModal"
            title="Perbarui Data Akun Copy Trade"
            max-width="lg"
            @close="showAccountEditModal = false"
        >
            <form
                @submit.prevent="submitAccountEdit"
                class="max-h-[70vh] space-y-4 overflow-y-auto ps-1 pr-2 text-xs"
            >
                <div
                    class="text-muted-foreground rounded-xl border border-sky-500/20 bg-sky-500/5 p-3 leading-relaxed"
                >
                    Perbarui data akun MT4 Anda di bawah ini. Setelah disimpan, status akun akan
                    dikembalikan ke antrean peninjauan oleh Administrator.
                </div>

                <!-- Nomor Akun Trading MT4 -->
                <div class="space-y-1.5">
                    <label
                        for="edit_nomor_akun_trading"
                        class="text-foreground block font-semibold"
                    >
                        Nomor Akun Trading MT4 <span class="text-destructive">*</span>
                    </label>
                    <input
                        id="edit_nomor_akun_trading"
                        v-model="accountEditForm.nomor_akun_trading"
                        type="text"
                        required
                        placeholder="Contoh: 12345678"
                        class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border px-3 py-2 font-mono text-xs focus:ring-2 focus:outline-none"
                        :class="{
                            'border-destructive focus:ring-destructive':
                                accountEditForm.errors.nomor_akun_trading,
                        }"
                    />
                    <p
                        v-if="accountEditForm.errors.nomor_akun_trading"
                        class="text-destructive text-xs"
                    >
                        {{ accountEditForm.errors.nomor_akun_trading }}
                    </p>
                </div>

                <!-- Server Broker -->
                <div class="space-y-1.5">
                    <label for="edit_server_broker" class="text-foreground block font-semibold">
                        Server Broker
                        <span
                            v-if="akun_copytrade?.tier === 'tier_3' || akun_copytrade?.tier === '3'"
                            class="text-destructive"
                            >*</span
                        >
                        <span v-else class="text-muted-foreground font-normal">(Opsional)</span>
                    </label>
                    <input
                        id="edit_server_broker"
                        v-model="accountEditForm.server_broker"
                        type="text"
                        placeholder="Contoh: XMGlobal-Real 50 atau HFM-Live Server 2"
                        class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border px-3 py-2 font-mono text-xs focus:ring-2 focus:outline-none"
                        :class="{
                            'border-destructive focus:ring-destructive':
                                accountEditForm.errors.server_broker,
                        }"
                    />
                    <p v-if="accountEditForm.errors.server_broker" class="text-destructive text-xs">
                        {{ accountEditForm.errors.server_broker }}
                    </p>
                </div>

                <!-- Estimasi Modal (USD) -->
                <div class="space-y-1.5">
                    <label for="edit_estimasi_modal" class="text-foreground block font-semibold">
                        Estimasi Modal Awal ($ USD) <span class="text-destructive">*</span>
                    </label>
                    <input
                        id="edit_estimasi_modal"
                        v-model="accountEditForm.estimasi_modal"
                        type="number"
                        step="0.01"
                        min="100"
                        required
                        placeholder="Contoh: 1000"
                        class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border px-3 py-2 text-xs focus:ring-2 focus:outline-none"
                        :class="{
                            'border-destructive focus:ring-destructive':
                                accountEditForm.errors.estimasi_modal,
                        }"
                    />
                    <p
                        v-if="accountEditForm.errors.estimasi_modal"
                        class="text-destructive text-xs"
                    >
                        {{ accountEditForm.errors.estimasi_modal }}
                    </p>
                </div>

                <!-- Khusus Tier 3: Password MT4 -->
                <div
                    v-if="akun_copytrade?.tier === 'tier_3' || akun_copytrade?.tier === '3'"
                    class="space-y-3 rounded-xl border border-violet-500/20 bg-violet-500/5 p-3"
                >
                    <div class="space-y-1.5">
                        <label
                            for="edit_master_password"
                            class="text-foreground block font-semibold"
                        >
                            Master Password MT4 (VPS Setup)
                        </label>
                        <input
                            id="edit_master_password"
                            v-model="accountEditForm.master_password"
                            type="password"
                            placeholder="Ketik password master baru (atau biarkan jika tidak diubah)"
                            class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border px-3 py-2 font-mono text-xs focus:ring-2 focus:outline-none"
                        />
                    </div>
                    <div class="space-y-1.5">
                        <label
                            for="edit_investor_password"
                            class="text-foreground block font-semibold"
                        >
                            Investor Password MT4 (Read-Only)
                        </label>
                        <input
                            id="edit_investor_password"
                            v-model="accountEditForm.investor_password"
                            type="password"
                            placeholder="Ketik password investor baru (atau biarkan jika tidak diubah)"
                            class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border px-3 py-2 font-mono text-xs focus:ring-2 focus:outline-none"
                        />
                    </div>
                </div>

                <!-- Rekening Pencairan -->
                <div class="border-border/60 space-y-3 border-t pt-3">
                    <span class="text-foreground block text-xs font-bold"
                        >Rekening Penarikan Dana (Withdrawal)</span
                    >

                    <!-- Nama Bank -->
                    <div class="space-y-1.5">
                        <label for="edit_nama_bank" class="text-foreground block font-semibold">
                            Bank <span class="text-destructive">*</span>
                        </label>
                        <select
                            id="edit_nama_bank"
                            v-model="selectedBankOption"
                            class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border px-3 py-2 text-xs focus:ring-2 focus:outline-none"
                            :class="{
                                'border-destructive focus:ring-destructive':
                                    accountEditForm.errors.nama_bank,
                            }"
                            @change="handleBankSelectChange(selectedBankOption)"
                        >
                            <option value="" disabled>-- Pilih Bank --</option>
                            <option v-for="bank in bankOptions" :key="bank" :value="bank">
                                {{ bank }}
                            </option>
                        </select>

                        <div v-if="selectedBankOption === 'Lainnya'" class="mt-1.5">
                            <input
                                :value="customBankName"
                                type="text"
                                placeholder="Ketik nama bank Anda (contoh: Bank Jago, SeaBank)..."
                                class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border px-3 py-2 text-xs focus:ring-2 focus:outline-none"
                                :class="{
                                    'border-destructive focus:ring-destructive':
                                        accountEditForm.errors.nama_bank,
                                }"
                                @input="handleCustomBankInput"
                            />
                        </div>
                        <p v-if="accountEditForm.errors.nama_bank" class="text-destructive text-xs">
                            {{ accountEditForm.errors.nama_bank }}
                        </p>
                    </div>

                    <!-- Nomor Rekening -->
                    <div class="space-y-1.5">
                        <label
                            for="edit_nomor_rekening"
                            class="text-foreground block font-semibold"
                        >
                            Nomor Rekening <span class="text-destructive">*</span>
                        </label>
                        <input
                            id="edit_nomor_rekening"
                            v-model="accountEditForm.nomor_rekening"
                            type="text"
                            required
                            placeholder="Contoh: 1234567890"
                            class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border px-3 py-2 font-mono text-xs focus:ring-2 focus:outline-none"
                            :class="{
                                'border-destructive focus:ring-destructive':
                                    accountEditForm.errors.nomor_rekening,
                            }"
                        />
                        <p
                            v-if="accountEditForm.errors.nomor_rekening"
                            class="text-destructive text-xs"
                        >
                            {{ accountEditForm.errors.nomor_rekening }}
                        </p>
                    </div>

                    <!-- Atas Nama Rekening -->
                    <div class="space-y-1.5">
                        <label
                            for="edit_atas_nama_rekening"
                            class="text-foreground block font-semibold"
                        >
                            Atas Nama Rekening <span class="text-destructive">*</span>
                        </label>
                        <input
                            id="edit_atas_nama_rekening"
                            v-model="accountEditForm.atas_nama_rekening"
                            type="text"
                            required
                            placeholder="Nama pemilik rekening bank"
                            class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border px-3 py-2 text-xs focus:ring-2 focus:outline-none"
                            :class="{
                                'border-destructive focus:ring-destructive':
                                    accountEditForm.errors.atas_nama_rekening,
                            }"
                        />
                        <p
                            v-if="accountEditForm.errors.atas_nama_rekening"
                            class="text-destructive text-xs"
                        >
                            {{ accountEditForm.errors.atas_nama_rekening }}
                        </p>
                    </div>
                </div>

                <!-- Checkbox Konfirmasi Follow Master -->
                <div class="border-border/60 border-t pt-3">
                    <label class="flex cursor-pointer items-start gap-2.5">
                        <input
                            v-model="accountEditForm.confirm_follow_ack"
                            type="checkbox"
                            class="border-input text-primary-600 focus:ring-primary-500 mt-0.5 h-4 w-4 rounded"
                        />
                        <span class="text-foreground text-[11px] leading-relaxed">
                            Saya menyatakan akun MT4 ini adalah milik saya yang sah dan saya telah
                            melakukan follow akun master copy trade YZD di broker.
                        </span>
                    </label>
                </div>

                <!-- Tombol Aksi Modal -->
                <div class="border-border flex items-center justify-end gap-3 border-t pt-3">
                    <Button
                        type="button"
                        variant="outline"
                        class="text-xs"
                        @click="showAccountEditModal = false"
                    >
                        Batal
                    </Button>
                    <Button
                        type="submit"
                        :disabled="accountEditForm.processing"
                        class="bg-primary-600 hover:bg-primary-700 px-4 py-2 text-xs text-white"
                    >
                        <Loader2
                            v-if="accountEditForm.processing"
                            class="mr-1.5 h-3.5 w-3.5 animate-spin"
                        />
                        <span>{{
                            accountEditForm.processing
                                ? 'Menyimpan...'
                                : 'Simpan Pembaruan & Kirim ke Admin'
                        }}</span>
                    </Button>
                </div>
            </form>
        </AppModal>
    </InvestorLayout>
</template>
