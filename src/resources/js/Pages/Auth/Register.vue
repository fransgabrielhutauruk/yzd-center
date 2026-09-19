<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import AppModal from '@/Components/Common/AppModal.vue';
import AppConfirmDialog from '@/Components/Common/AppConfirmDialog.vue';
import { Button } from '@/Components/ui/button';
import { compressImageFile, formatBytes } from '@/lib/imageCompression';
import { toast } from 'vue-sonner';
import {
    User,
    Lock,
    Eye,
    EyeOff,
    Mail,
    Phone,
    CreditCard,
    MapPin,
    ShieldAlert,
    Ticket,
    UserCheck,
    ArrowLeft,
    ArrowRight,
    CheckCircle2,
    Users,
    FileText,
    UploadCloud,
    CheckCircle,
    File as FileIcon,
    Loader2,
    Info,
    Edit3,
    AlertCircle,
    Download,
    Building2,
    Banknote,
    Calendar,
    TrendingUp,
    Server,
    KeyRound,
    Briefcase,
} from 'lucide-vue-next';

// === [DEV ONLY: AUTO-FILL] Hapus blok import di bawah ini kelak jika tidak diperlukan lagi ===
import DevRegisterAutoFill from '@/Components/Dev/DevRegisterAutoFill.vue';
// === [END DEV ONLY: AUTO-FILL] ===

const props = defineProps({
    invitation: {
        type: Object,
        required: true,
    },
    token: {
        type: String,
        default: '',
    },
    terms: {
        type: Object,
        default: null,
    },
    privacy: {
        type: Object,
        default: null,
    },
});

const currentStep = ref(1);
const showPassword = ref(false);
const isTermsModalOpen = ref(false);
const isPrivacyModalOpen = ref(false);

const ktpCompressing = ref(false);
const ktpInfo = ref(null);

const perjanjianCompressing = ref(false);
const perjanjianInfo = ref(null);

const isSubmitted = ref(false);
const showLeaveConfirmation = ref(false);
const pendingNavigation = ref(null);

const steps = [
    { id: 1, title: 'Akun', icon: User, desc: 'Identitas Diri' },
    { id: 2, title: 'Ahli Waris', icon: Users, desc: 'Kontak Darurat' },
    { id: 3, title: 'Trading & Berkas', icon: FileText, desc: 'Akun & Legalitas' },
    { id: 4, title: 'Persetujuan', icon: CheckCircle2, desc: 'Konfirmasi & Submit' },
];

const form = useForm({
    invite_token: props.token || '',
    name: '',
    username: '',
    email: '',
    phone: '',
    nik: '',
    alamat: '',
    tempat_lahir: '',
    tanggal_lahir: '',
    pekerjaan: '',
    password: '',
    password_confirmation: '',

    // Data Ahli Waris
    ahli_waris_nama: '',
    ahli_waris_hubungan: '',
    ahli_waris_phone: '',
    ahli_waris_alamat: '',

    // Data Akun Copy Trade
    tier_investasi: 'tier_1',
    nomor_akun_trading: '',
    estimasi_modal: '',
    tanggal_mulai_trading: new Date().toISOString().split('T')[0],
    nama_bank: '',
    nomor_rekening: '',
    atas_nama_rekening: '',
    // Khusus Tier 3
    server_broker: '',
    master_password: '',
    investor_password: '',

    // Berkas Legalitas
    dokumen_ktp: null,
    dokumen_perjanjian: null,

    // Persetujuan T&C
    terms: false,
});

const hubunganOptions = ['Anak', 'Istri', 'Suami', 'Orang Tua', 'Saudara Kandung', 'Lainnya'];

const showMasterPassword = ref(false);
const showInvestorPassword = ref(false);

const bankOptions = [
    'BCA', 'Mandiri', 'BRI', 'BNI', 'BSI', 'CIMB Niaga', 'Danamon', 'Permata',
    'Panin', 'Mega', 'BTN', 'BPD', 'OCBC NISP', 'Maybank', 'Lainnya',
];

const selectedBankOption = ref('');
const customBankName = ref('');

// Sinkronisasi bank pilihan dengan form.nama_bank
watch(selectedBankOption, (newVal) => {
    if (newVal === 'Lainnya') {
        form.nama_bank = customBankName.value;
    } else {
        form.nama_bank = newVal;
        customBankName.value = '';
    }
});

// Update form.nama_bank saat user mengetik di input manual "Lainnya"
const handleCustomBankInput = (event) => {
    const val = event.target.value;
    customBankName.value = val;
    if (selectedBankOption.value === 'Lainnya') {
        form.nama_bank = val;
    }
};

// Sinkronisasi dari form.nama_bank (misal saat autofill atau reset)
watch(() => form.nama_bank, (newVal) => {
    if (!newVal) {
        selectedBankOption.value = '';
        customBankName.value = '';
    } else if (bankOptions.filter(b => b !== 'Lainnya').includes(newVal)) {
        selectedBankOption.value = newVal;
        customBankName.value = '';
    } else {
        selectedBankOption.value = 'Lainnya';
        customBankName.value = newVal;
    }
}, { immediate: true });

const tierConfig = {
    tier_1: {
        label: 'Tier 1 – Cent Account HFM',
        broker: 'HFM',
        desc: 'Copy trade otomatis. Modal < $5,000.',
        color: 'blue',
        modalInfo: 'Modal: $100 – $4,999',
    },
    tier_2: {
        label: 'Tier 2 – Standard Account XM',
        broker: 'XM',
        desc: 'Copy trade otomatis. Modal $5,000 – $300,000.',
        color: 'emerald',
        modalInfo: 'Modal: $5,000 – $300,000',
    },
    tier_3: {
        label: 'Tier 3 – Private VPS XM',
        broker: 'XM',
        desc: 'Dikelola langsung via Private VPS. Modal ≥ $300,000.',
        color: 'violet',
        modalInfo: 'Modal: ≥ $300,000',
    },
};

const isDownloadingTemplate = ref(false);

const downloadTemplate = async () => {
    if (isDownloadingTemplate.value) return;

    const tier = form.tier_investasi;
    isDownloadingTemplate.value = true;
    toast.info('Menyiapkan template surat perjanjian...');

    try {
        const payload = {
            name: form.name,
            nik: form.nik,
            tempat_lahir: form.tempat_lahir,
            tanggal_lahir: form.tanggal_lahir,
            pekerjaan: form.pekerjaan,
            alamat: form.alamat,
            phone: form.phone,
            email: form.email,
            tier_investasi: form.tier_investasi,
            nomor_akun_trading: form.nomor_akun_trading,
            estimasi_modal: form.estimasi_modal,
            tanggal_mulai_trading: form.tanggal_mulai_trading,
            nama_bank: form.nama_bank,
            nomor_rekening: form.nomor_rekening,
            atas_nama_rekening: form.atas_nama_rekening,
            ahli_waris_nama: form.ahli_waris_nama,
            ahli_waris_hubungan: form.ahli_waris_hubungan,
            ahli_waris_phone: form.ahli_waris_phone,
            server_broker: form.server_broker,
            master_password: form.master_password,
            investor_password: form.investor_password,
        };

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        const response = await fetch(`/register/template-perjanjian/${tier}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken || '',
                Accept: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document, application/json',
            },
            body: JSON.stringify(payload),
        });

        if (!response.ok) {
            throw new Error('Gagal mengunduh template perjanjian.');
        }

        const blob = await response.blob();
        let filename = `Surat_Perjanjian_${tier.toUpperCase()}.docx`;
        const disposition = response.headers.get('content-disposition');
        if (disposition && disposition.indexOf('filename=') !== -1) {
            const matches = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/.exec(disposition);
            if (matches != null && matches[1]) {
                filename = matches[1].replace(/['"]/g, '');
            }
        }

        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        a.remove();
        window.URL.revokeObjectURL(url);

        toast.success(`Surat perjanjian ${form.name ? 'untuk ' + form.name : ''} berhasil diunduh!`);
    } catch (e) {
        toast.error('Gagal menyiapkan dokumen perjanjian. Silakan coba lagi.');
    } finally {
        isDownloadingTemplate.value = false;
    }
};

const handleKtpChange = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    ktpCompressing.value = true;
    try {
        const result = await compressImageFile(file);
        if (result) {
            form.dokumen_ktp = result.file;
            ktpInfo.value = {
                name: file.name,
                previewUrl: result.previewUrl,
                isQualityWarning: result.isQualityWarning || false,
                warningMessage: result.warningMessage || null,
            };
            form.clearErrors('dokumen_ktp');
            if (result.isQualityWarning) {
                toast.warning(
                    'Peringatan Kualitas: Foto KTP terindikasi resolusi rendah atau sedikit buram.'
                );
            }
        }
    } catch (e) {
        toast.error('Gagal memproses foto KTP.');
    } finally {
        ktpCompressing.value = false;
    }
};

const handlePerjanjianChange = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    perjanjianCompressing.value = true;
    try {
        const result = await compressImageFile(file);
        if (result) {
            form.dokumen_perjanjian = result.file;
            perjanjianInfo.value = {
                name: file.name,
                previewUrl: result.previewUrl,
                isPdf: file.type === 'application/pdf',
                isQualityWarning: result.isQualityWarning || false,
                warningMessage: result.warningMessage || null,
            };
            form.clearErrors('dokumen_perjanjian');
            if (result.isQualityWarning) {
                toast.warning(
                    'Peringatan Kualitas: Berkas perjanjian terindikasi resolusi rendah atau sedikit buram.'
                );
            }
        }
    } catch (e) {
        toast.error('Gagal memproses berkas perjanjian.');
    } finally {
        perjanjianCompressing.value = false;
    }
};

const openTermsModal = () => {
    isTermsModalOpen.value = true;
};

const closeTermsModal = () => {
    isTermsModalOpen.value = false;
};

const openPrivacyModal = () => {
    isPrivacyModalOpen.value = true;
};

const closePrivacyModal = () => {
    isPrivacyModalOpen.value = false;
};

// Konten legal tersimpan sebagai HTML Tiptap; dukung fallback plain-text lama
const escapeHtml = (text) =>
    String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

const toLegalHtml = (konten) => {
    if (!konten) return '';
    if (!/<\/?[a-z][\s\S]*>/i.test(konten)) {
        return escapeHtml(konten).replace(/\n/g, '<br />');
    }
    return konten;
};

const termsHtml = computed(
    () =>
        toLegalHtml(props.terms?.konten) ||
        'Tidak ada konten syarat & ketentuan yang tersedia saat ini.'
);

const privacyHtml = computed(
    () =>
        toLegalHtml(props.privacy?.konten) ||
        'Tidak ada konten kebijakan privasi yang tersedia saat ini.'
);

// ==========================================
// VALIDASI KLIEN PER LANGKAH (STEP-BY-STEP)
// ==========================================
const validateStep1 = () => {
    form.clearErrors(
        'name',
        'username',
        'email',
        'phone',
        'nik',
        'alamat',
        'tempat_lahir',
        'tanggal_lahir',
        'pekerjaan',
        'password',
        'password_confirmation'
    );
    let valid = true;

    if (!form.name || !form.name.trim()) {
        form.setError('name', 'Nama lengkap wajib diisi.');
        valid = false;
    }
    if (!form.username || !form.username.trim()) {
        form.setError('username', 'Username wajib diisi.');
        valid = false;
    } else if (form.username.length < 3) {
        form.setError('username', 'Username minimal 3 karakter.');
        valid = false;
    } else if (!/^[a-zA-Z0-9_]+$/.test(form.username)) {
        form.setError('username', 'Username hanya boleh huruf, angka, dan garis bawah (_).');
        valid = false;
    }
    if (!form.email || !form.email.trim()) {
        form.setError('email', 'Alamat email wajib diisi.');
        valid = false;
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
        form.setError('email', 'Format alamat email tidak valid.');
        valid = false;
    }
    if (!form.phone || !form.phone.trim()) {
        form.setError('phone', 'Nomor WhatsApp/HP wajib diisi.');
        valid = false;
    } else if (form.phone.length < 9) {
        form.setError('phone', 'Nomor telepon minimal 9 digit.');
        valid = false;
    }
    if (!form.nik || !form.nik.trim()) {
        form.setError('nik', 'Nomor NIK KTP wajib diisi.');
        valid = false;
    } else if (!/^\d{16}$/.test(form.nik)) {
        form.setError('nik', 'Nomor NIK harus tepat 16 digit angka.');
        valid = false;
    }
    if (!form.alamat || !form.alamat.trim()) {
        form.setError('alamat', 'Alamat domisili lengkap wajib diisi.');
        valid = false;
    }
    if (!form.tempat_lahir || !form.tempat_lahir.trim()) {
        form.setError('tempat_lahir', 'Tempat lahir wajib diisi.');
        valid = false;
    }
    if (!form.tanggal_lahir) {
        form.setError('tanggal_lahir', 'Tanggal lahir wajib diisi.');
        valid = false;
    }
    if (!form.pekerjaan || !form.pekerjaan.trim()) {
        form.setError('pekerjaan', 'Pekerjaan / sumber dana wajib diisi.');
        valid = false;
    }
    if (!form.password) {
        form.setError('password', 'Kata sandi wajib diisi.');
        valid = false;
    } else if (form.password.length < 8) {
        form.setError('password', 'Kata sandi minimal 8 karakter.');
        valid = false;
    }
    if (form.password !== form.password_confirmation) {
        form.setError('password_confirmation', 'Konfirmasi kata sandi tidak cocok.');
        valid = false;
    }

    if (!valid) {
        toast.error('Mohon lengkapi seluruh isian data diri dengan benar.');
    }
    return valid;
};

const validateStep2 = () => {
    form.clearErrors(
        'ahli_waris_nama',
        'ahli_waris_hubungan',
        'ahli_waris_phone',
        'ahli_waris_alamat'
    );
    let valid = true;

    if (!form.ahli_waris_nama || !form.ahli_waris_nama.trim()) {
        form.setError('ahli_waris_nama', 'Nama ahli waris wajib diisi.');
        valid = false;
    }
    if (!form.ahli_waris_hubungan) {
        form.setError('ahli_waris_hubungan', 'Pilih hubungan ahli waris.');
        valid = false;
    }
    if (!form.ahli_waris_phone || !form.ahli_waris_phone.trim()) {
        form.setError('ahli_waris_phone', 'Nomor kontak ahli waris wajib diisi.');
        valid = false;
    } else if (form.ahli_waris_phone.trim() === form.phone.trim()) {
        form.setError('ahli_waris_phone', 'Nomor kontak ahli waris wajib berbeda dari nomor Anda.');
        valid = false;
    }
    if (!form.ahli_waris_alamat || !form.ahli_waris_alamat.trim()) {
        form.setError('ahli_waris_alamat', 'Alamat domisili ahli waris wajib diisi.');
        valid = false;
    }

    if (!valid) {
        toast.error('Mohon lengkapi data perlindungan ahli waris.');
    }
    return valid;
};

const validateStep3 = () => {
    form.clearErrors(
        'tier_investasi',
        'nomor_akun_trading',
        'estimasi_modal',
        'tanggal_mulai_trading',
        'nama_bank',
        'nomor_rekening',
        'atas_nama_rekening',
        'server_broker',
        'master_password',
        'investor_password',
        'dokumen_ktp',
        'dokumen_perjanjian'
    );
    let valid = true;

    if (!form.tier_investasi) {
        form.setError('tier_investasi', 'Pilih tier investasi.');
        valid = false;
    }
    if (!form.nomor_akun_trading || !form.nomor_akun_trading.trim()) {
        form.setError('nomor_akun_trading', 'Nomor akun trading wajib diisi.');
        valid = false;
    }
    const modal = parseFloat(form.estimasi_modal);
    if (!form.estimasi_modal || isNaN(modal) || modal < 100) {
        form.setError('estimasi_modal', 'Estimasi modal wajib diisi (minimal $100).');
        valid = false;
    } else {
        if (form.tier_investasi === 'tier_1' && modal >= 5000) {
            form.setError('estimasi_modal', 'Tier 1 hanya untuk modal di bawah $5,000.');
            valid = false;
        } else if (form.tier_investasi === 'tier_2' && (modal < 5000 || modal > 300000)) {
            form.setError('estimasi_modal', 'Tier 2 hanya untuk modal $5,000 – $300,000.');
            valid = false;
        } else if (form.tier_investasi === 'tier_3' && modal < 300000) {
            form.setError('estimasi_modal', 'Tier 3 membutuhkan modal minimal $300,000.');
            valid = false;
        }
    }
    if (!form.tanggal_mulai_trading) {
        form.setError('tanggal_mulai_trading', 'Tanggal mulai copy trade wajib diisi.');
        valid = false;
    }
    if (!form.nama_bank || !form.nama_bank.trim()) {
        form.setError('nama_bank', 'Nama bank pencairan wajib diisi.');
        valid = false;
    }
    if (!form.nomor_rekening || !form.nomor_rekening.trim()) {
        form.setError('nomor_rekening', 'Nomor rekening wajib diisi.');
        valid = false;
    }
    if (!form.atas_nama_rekening || !form.atas_nama_rekening.trim()) {
        form.setError('atas_nama_rekening', 'Nama pemilik rekening wajib diisi.');
        valid = false;
    }
    if (form.tier_investasi === 'tier_3') {
        if (!form.server_broker || !form.server_broker.trim()) {
            form.setError('server_broker', 'Server broker XM wajib diisi untuk Tier 3.');
            valid = false;
        }
        if (!form.master_password) {
            form.setError('master_password', 'Master password MT4 wajib diisi untuk Tier 3.');
            valid = false;
        }
        if (!form.investor_password) {
            form.setError('investor_password', 'Investor password MT4 wajib diisi untuk Tier 3.');
            valid = false;
        }
    }
    if (ktpCompressing.value || perjanjianCompressing.value) {
        toast.info('Mohon tunggu hingga proses kompresi berkas selesai.');
        return false;
    }
    if (!form.dokumen_ktp) {
        form.setError('dokumen_ktp', 'Foto KTP wajib diunggah.');
        valid = false;
    }
    if (!form.dokumen_perjanjian) {
        form.setError('dokumen_perjanjian', 'Surat Perjanjian Bermaterai wajib diunggah.');
        valid = false;
    }

    if (!valid) {
        toast.error('Mohon lengkapi data akun trading dan berkas legalitas.');
    }
    return valid;
};

const nextStep = () => {
    if (currentStep.value === 1 && !validateStep1()) return;
    if (currentStep.value === 2 && !validateStep2()) return;
    if (currentStep.value === 3 && !validateStep3()) return;

    if (currentStep.value < 4) {
        currentStep.value++;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

const jumpToStep = (targetStep) => {
    if (targetStep < currentStep.value) {
        currentStep.value = targetStep;
        window.scrollTo({ top: 0, behavior: 'smooth' });
        return;
    }
    if (targetStep === currentStep.value) return;

    if (targetStep >= 2 && !validateStep1()) {
        currentStep.value = 1;
        return;
    }
    if (targetStep >= 3 && !validateStep2()) {
        currentStep.value = 2;
        return;
    }
    if (targetStep === 4 && !validateStep3()) {
        currentStep.value = 3;
        return;
    }

    currentStep.value = targetStep;
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const hasUnsavedChanges = computed(() => {
    if (isSubmitted.value || form.processing) return false;

    return (
        (Boolean(form.name) && form.name.trim() !== '') ||
        (Boolean(form.username) && form.username.trim() !== '') ||
        (Boolean(form.email) && form.email.trim() !== '') ||
        (Boolean(form.phone) && form.phone.trim() !== '') ||
        (Boolean(form.nik) && form.nik.trim() !== '') ||
        (Boolean(form.alamat) && form.alamat.trim() !== '') ||
        (Boolean(form.password) && form.password !== '') ||
        (Boolean(form.password_confirmation) && form.password_confirmation !== '') ||
        (Boolean(form.ahli_waris_nama) && form.ahli_waris_nama.trim() !== '') ||
        (Boolean(form.ahli_waris_hubungan) && form.ahli_waris_hubungan !== '') ||
        (Boolean(form.ahli_waris_phone) && form.ahli_waris_phone.trim() !== '') ||
        (Boolean(form.nomor_akun_trading) && form.nomor_akun_trading.trim() !== '') ||
        form.dokumen_ktp !== null ||
        form.dokumen_perjanjian !== null
    );
});

const handleBeforeUnload = (e) => {
    if (hasUnsavedChanges.value && !form.processing && !isSubmitted.value) {
        e.preventDefault();
        e.returnValue = '';
        return '';
    }
};

const confirmLeave = () => {
    showLeaveConfirmation.value = false;
    if (pendingNavigation.value) {
        const target = pendingNavigation.value;
        pendingNavigation.value = null;
        isSubmitted.value = true;
        router.visit(target.url, target.options);
    }
};

const cancelLeave = () => {
    showLeaveConfirmation.value = false;
    pendingNavigation.value = null;
};

let removeRouterListener = null;

onMounted(() => {
    window.addEventListener('beforeunload', handleBeforeUnload);

    removeRouterListener = router.on('before', (event) => {
        if (event.detail?.visit?.method?.toLowerCase() === 'post') {
            return;
        }

        if (hasUnsavedChanges.value && !isSubmitted.value && !form.processing) {
            event.preventDefault();
            pendingNavigation.value = {
                url: event.detail.visit.url,
                options: event.detail.visit,
            };
            showLeaveConfirmation.value = true;
        }
    });
});

onUnmounted(() => {
    window.removeEventListener('beforeunload', handleBeforeUnload);
    if (removeRouterListener) {
        removeRouterListener();
    }
});

const submit = () => {
    if (!validateStep1()) {
        currentStep.value = 1;
        return;
    }
    if (!validateStep2()) {
        currentStep.value = 2;
        return;
    }
    if (!validateStep3()) {
        currentStep.value = 3;
        return;
    }
    if (!form.terms) {
        toast.error('Anda wajib menyetujui Syarat & Ketentuan Layanan.');
        return;
    }

    isSubmitted.value = true;

    form.post('/register', {
        forceFormData: true,
        onError: (errors) => {
            isSubmitted.value = false;
            const step1Fields = [
                'name',
                'username',
                'email',
                'phone',
                'nik',
                'alamat',
                'tempat_lahir',
                'tanggal_lahir',
                'pekerjaan',
                'password',
                'password_confirmation',
            ];
            const step2Fields = [
                'ahli_waris_nama',
                'ahli_waris_hubungan',
                'ahli_waris_phone',
                'ahli_waris_alamat',
            ];
            const step3Fields = [
                'tier_investasi',
                'nomor_akun_trading',
                'estimasi_modal',
                'tanggal_mulai_trading',
                'nama_bank',
                'nomor_rekening',
                'atas_nama_rekening',
                'server_broker',
                'master_password',
                'investor_password',
                'dokumen_ktp',
                'dokumen_perjanjian',
            ];

            const errorKeys = Object.keys(errors);
            if (errorKeys.some((k) => step1Fields.includes(k))) {
                currentStep.value = 1;
                toast.error('Terdapat kesalahan pada data akun & domisili.');
            } else if (errorKeys.some((k) => step2Fields.includes(k))) {
                currentStep.value = 2;
                toast.error('Terdapat kesalahan pada data ahli waris.');
            } else if (errorKeys.some((k) => step3Fields.includes(k))) {
                currentStep.value = 3;
                toast.error('Terdapat kesalahan pada berkas legalitas.');
            } else {
                currentStep.value = 4;
            }
        },
    });
};
</script>

<template>
    <AuthLayout
        :title="
            invitation.valid
                ? 'Pendaftaran Akun & Verifikasi Legalitas - YZD Center'
                : 'Undangan Tidak Valid - YZD Center'
        "
        :max-width="invitation.valid ? 'max-w-2xl' : 'max-w-md'"
    >
        <!-- Case 1: Undangan Tidak Valid / Kedaluwarsa / Kuota Habis -->
        <div v-if="!invitation.valid" class="space-y-6 py-4 text-center">
            <div
                class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-amber-500/10 text-amber-600 dark:text-amber-400"
            >
                <ShieldAlert class="h-8 w-8" />
            </div>

            <div class="space-y-2">
                <h1 class="text-foreground text-xl font-bold">Tautan Undangan Tidak Valid</h1>
                <p class="text-muted-foreground text-sm leading-relaxed">
                    {{
                        invitation.message ||
                        'Tautan pendaftaran ini tidak dapat digunakan atau telah kedaluwarsa.'
                    }}
                </p>
            </div>

            <div
                class="bg-muted/60 border-border text-muted-foreground space-y-2 rounded-xl border p-4 text-left text-xs"
            >
                <p class="text-foreground flex items-center gap-1.5 font-semibold">
                    <Ticket class="text-primary-500 h-4 w-4" />
                    Kebijakan Pendaftaran Tertutup:
                </p>
                <p class="leading-relaxed">
                    YZD Center menerapkan sistem eksklusif berbasis undangan. Setiap pendaftaran akun wajib menggunakan  tautan resmi
                    yang masih aktif dari Administrator.
                </p>
            </div>

            <div class="pt-2">
                <Link
                    href="/login"
                    class="bg-primary-600 hover:bg-primary-700 inline-flex w-full items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors"
                >
                    <ArrowLeft class="h-4 w-4" />
                    <span>Kembali ke Halaman Masuk</span>
                </Link>
            </div>
        </div>

        <!-- Case 2: Undangan Valid -> Multi-Step Wizard -->
        <div v-else class="space-y-6">
            <!-- Header Judul -->
            <div class="space-y-1 text-center">
                <h1 class="text-foreground text-xl font-bold tracking-tight">
                    Pendaftaran Akun & Legalitas
                </h1>
                <p class="text-muted-foreground text-xs">
                    Selesaikan tahapan pendaftaran secara bertahap untuk aktivasi akun Anda.
                </p>
            </div>

            <!-- Invitation Token Badge Info -->
            <div
                class="bg-primary-500/10 border-primary-500/20 flex items-center justify-between rounded-xl border p-3 text-xs"
            >
                <div class="flex items-center gap-2.5">
                    <div
                        class="bg-primary-500/20 text-primary-600 dark:text-primary-400 rounded-lg p-1.5"
                    >
                        <Ticket class="h-4 w-4" />
                    </div>
                    <div>
                        <span class="text-foreground block font-semibold">{{
                            invitation.invitation?.label
                        }}</span>
                        <span class="text-muted-foreground text-[11px]">Tautan Aktif</span>
                    </div>
                </div>
                <div
                    class="bg-primary-600 flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold text-white shadow-xs"
                >
                    <CheckCircle2 class="h-3.5 w-3.5" />
                    <span>{{ invitation.invitation?.target_role_label }}</span>
                </div>
            </div>

            <!-- === [DEV ONLY: AUTO-FILL] Hapus blok komponen di bawah ini kelak jika tidak diperlukan lagi === -->
            <DevRegisterAutoFill
                :form="form"
                :ktp-info="ktpInfo"
                :perjanjian-info="perjanjianInfo"
                @update:ktp-info="(val) => (ktpInfo = val)"
                @update:perjanjian-info="(val) => (perjanjianInfo = val)"
            />
            <!-- === [END DEV ONLY: AUTO-FILL] === -->

            <!-- ========================================== -->
            <!-- STEPPER INDICATOR (DESKTOP)               -->
            <!-- ========================================== -->
            <div class="border-border/70 hidden grid-cols-4 gap-2 border-b pt-1 pb-2 sm:grid">
                <button
                    v-for="step in steps"
                    :key="step.id"
                    type="button"
                    @click="jumpToStep(step.id)"
                    class="group relative flex flex-col items-center rounded-xl p-2 text-center transition-all"
                    :class="[
                        currentStep === step.id
                            ? 'bg-primary-500/10 text-primary-600 dark:text-primary-400 font-semibold shadow-2xs'
                            : currentStep > step.id
                              ? 'text-foreground hover:bg-muted/60 cursor-pointer'
                              : 'text-muted-foreground cursor-default opacity-60',
                    ]"
                >
                    <div
                        class="mb-1.5 flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold transition-all"
                        :class="[
                            currentStep === step.id
                                ? 'bg-primary-600 ring-primary-500/20 text-white shadow-sm ring-4'
                                : currentStep > step.id
                                  ? 'bg-emerald-600 text-white'
                                  : 'bg-muted text-muted-foreground border-border border',
                        ]"
                    >
                        <CheckCircle2 v-if="currentStep > step.id" class="h-4 w-4" />
                        <span v-else>{{ step.id }}</span>
                    </div>
                    <span class="w-full truncate text-xs leading-tight font-bold">{{
                        step.title
                    }}</span>
                    <span class="text-muted-foreground w-full truncate text-[10px]">{{
                        step.desc
                    }}</span>
                </button>
            </div>

            <!-- ========================================== -->
            <!-- STEPPER INDICATOR (MOBILE)                -->
            <!-- ========================================== -->
            <div class="border-border/70 space-y-2 border-b pb-2 sm:hidden">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-foreground flex items-center gap-1.5 font-bold">
                        <span
                            class="bg-primary-600 flex h-5 w-5 items-center justify-center rounded-full text-[11px] font-bold text-white"
                        >
                            {{ currentStep }}
                        </span>
                        {{ steps[currentStep - 1].title }}
                    </span>
                    <span class="text-muted-foreground font-medium"
                        >Langkah {{ currentStep }} dari 4</span
                    >
                </div>
                <div class="grid grid-cols-4 gap-1.5">
                    <div
                        v-for="step in steps"
                        :key="step.id"
                        class="h-1.5 rounded-full transition-all"
                        :class="[
                            currentStep > step.id
                                ? 'bg-emerald-600'
                                : currentStep === step.id
                                  ? 'bg-primary-600'
                                  : 'bg-muted',
                        ]"
                    ></div>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- ========================================== -->
                <!-- LANGKAH 1: IDENTITAS AKUN                  -->
                <!-- ========================================== -->
                <div v-show="currentStep === 1" class="space-y-4">
                    <div
                        class="border-border text-foreground flex items-center gap-2 border-b pb-2"
                    >
                        <div class="bg-primary-500/10 text-primary-600 rounded-md p-1">
                            <User class="h-4 w-4" />
                        </div>
                        <div>
                            <h2 class="text-sm font-bold tracking-wider uppercase">
                                1. Identitas Akun
                            </h2>
                            <p class="text-muted-foreground text-xs">
                                Lengkapi data identitas pendaftar.
                            </p>
                        </div>
                    </div>

                    <!-- Grid: Nama & Username -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <!-- Nama Lengkap -->
                        <div class="space-y-1.5">
                            <label for="name" class="text-foreground block text-xs font-semibold">
                                Nama Lengkap (Sesuai KTP) <span class="text-destructive">*</span>
                            </label>
                            <div class="relative">
                                <div
                                    class="text-muted-foreground pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"
                                >
                                    <User class="h-4 w-4" />
                                </div>
                                <input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    required
                                    placeholder="Contoh: Budi Santoso"
                                    class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border py-2 pr-3 pl-9 text-sm transition-all focus:border-transparent focus:ring-2 focus:outline-none"
                                    :class="{
                                        'border-destructive focus:ring-destructive':
                                            form.errors.name,
                                    }"
                                />
                            </div>
                            <p v-if="form.errors.name" class="text-destructive text-xs font-medium">
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <!-- Username -->
                        <div class="space-y-1.5">
                            <label
                                for="username"
                                class="text-foreground block text-xs font-semibold"
                            >
                                Username <span class="text-destructive">*</span>
                            </label>
                            <div class="relative">
                                <div
                                    class="text-muted-foreground pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"
                                >
                                    <span class="text-xs font-bold">@</span>
                                </div>
                                <input
                                    id="username"
                                    v-model="form.username"
                                    type="text"
                                    required
                                    placeholder="budisantoso"
                                    class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border py-2 pr-3 pl-9 text-sm transition-all focus:border-transparent focus:ring-2 focus:outline-none"
                                    :class="{
                                        'border-destructive focus:ring-destructive':
                                            form.errors.username,
                                    }"
                                />
                            </div>
                            <p
                                v-if="form.errors.username"
                                class="text-destructive text-xs font-medium"
                            >
                                {{ form.errors.username }}
                            </p>
                        </div>
                    </div>

                    <!-- Grid: Email & Phone -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <!-- Email -->
                        <div class="space-y-1.5">
                            <label for="email" class="text-foreground block text-xs font-semibold">
                                Alamat Email <span class="text-destructive">*</span>
                            </label>
                            <div class="relative">
                                <div
                                    class="text-muted-foreground pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"
                                >
                                    <Mail class="h-4 w-4" />
                                </div>
                                <input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    required
                                    placeholder="budi@example.com"
                                    class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border py-2 pr-3 pl-9 text-sm transition-all focus:border-transparent focus:ring-2 focus:outline-none"
                                    :class="{
                                        'border-destructive focus:ring-destructive':
                                            form.errors.email,
                                    }"
                                />
                            </div>
                            <p
                                v-if="form.errors.email"
                                class="text-destructive text-xs font-medium"
                            >
                                {{ form.errors.email }}
                            </p>
                        </div>

                        <!-- Nomor Telepon -->
                        <div class="space-y-1.5">
                            <label for="phone" class="text-foreground block text-xs font-semibold">
                                Nomor WhatsApp / HP <span class="text-destructive">*</span>
                            </label>
                            <div class="relative">
                                <div
                                    class="text-muted-foreground pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"
                                >
                                    <Phone class="h-4 w-4" />
                                </div>
                                <input
                                    id="phone"
                                    v-model="form.phone"
                                    type="tel"
                                    required
                                    placeholder="+628..."
                                    class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border py-2 pr-3 pl-9 text-sm transition-all focus:border-transparent focus:ring-2 focus:outline-none"
                                    :class="{
                                        'border-destructive focus:ring-destructive':
                                            form.errors.phone,
                                    }"
                                />
                            </div>
                            <p
                                v-if="form.errors.phone"
                                class="text-destructive text-xs font-medium"
                            >
                                {{ form.errors.phone }}
                            </p>
                        </div>
                    </div>

                    <!-- NIK KTP -->
                    <div class="space-y-1.5">
                        <label for="nik" class="text-foreground block text-xs font-semibold">
                            Nomor Induk Kependudukan (NIK 16 Digit)
                            <span class="text-destructive">*</span>
                        </label>
                        <div class="relative">
                            <div
                                class="text-muted-foreground pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"
                            >
                                <CreditCard class="h-4 w-4" />
                            </div>
                            <input
                                id="nik"
                                v-model="form.nik"
                                type="text"
                                maxlength="16"
                                required
                                placeholder="3201xxxxxxxxxxxx"
                                class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border py-2 pr-3 pl-9 font-mono text-sm transition-all focus:border-transparent focus:ring-2 focus:outline-none"
                                :class="{
                                    'border-destructive focus:ring-destructive': form.errors.nik,
                                }"
                            />
                        </div>
                        <p v-if="form.errors.nik" class="text-destructive text-xs font-medium">
                            {{ form.errors.nik }}
                        </p>
                    </div>

                    <!-- Alamat Domisili -->
                    <div class="space-y-1.5">
                        <label for="alamat" class="text-foreground block text-xs font-semibold">
                            Alamat Lengkap <span class="text-destructive">*</span>
                        </label>
                        <div class="relative">
                            <div
                                class="text-muted-foreground pointer-events-none absolute top-2.5 left-0 flex items-start pl-3"
                            >
                                <MapPin class="h-4 w-4" />
                            </div>
                            <textarea
                                id="alamat"
                                v-model="form.alamat"
                                rows="2"
                                required
                                placeholder="Jl. Mawar No. 12, Kelurahan, Kecamatan, Kota"
                                class="bg-background border-input focus:ring-primary-500 w-full resize-none rounded-lg border py-2 pr-3 pl-9 text-sm transition-all focus:border-transparent focus:ring-2 focus:outline-none"
                                :class="{
                                    'border-destructive focus:ring-destructive': form.errors.alamat,
                                }"
                            ></textarea>
                        </div>
                        <p v-if="form.errors.alamat" class="text-destructive text-xs font-medium">
                            {{ form.errors.alamat }}
                        </p>
                    </div>

                    <!-- Grid: Tempat Lahir & Tanggal Lahir -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <!-- Tempat Lahir -->
                        <div class="space-y-1.5">
                            <label for="tempat_lahir" class="text-foreground block text-xs font-semibold">
                                Tempat Lahir <span class="text-destructive">*</span>
                            </label>
                            <div class="relative">
                                <div
                                    class="text-muted-foreground pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"
                                >
                                    <MapPin class="h-4 w-4" />
                                </div>
                                <input
                                    id="tempat_lahir"
                                    v-model="form.tempat_lahir"
                                    type="text"
                                    required
                                    placeholder="Contoh: Jakarta"
                                    class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border py-2 pr-3 pl-9 text-sm transition-all focus:border-transparent focus:ring-2 focus:outline-none"
                                    :class="{
                                        'border-destructive focus:ring-destructive':
                                            form.errors.tempat_lahir,
                                    }"
                                />
                            </div>
                            <p v-if="form.errors.tempat_lahir" class="text-destructive text-xs font-medium">
                                {{ form.errors.tempat_lahir }}
                            </p>
                        </div>

                        <!-- Tanggal Lahir -->
                        <div class="space-y-1.5">
                            <label for="tanggal_lahir" class="text-foreground block text-xs font-semibold">
                                Tanggal Lahir <span class="text-destructive">*</span>
                            </label>
                            <div class="relative">
                                <div
                                    class="text-muted-foreground pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"
                                >
                                    <Calendar class="h-4 w-4" />
                                </div>
                                <input
                                    id="tanggal_lahir"
                                    v-model="form.tanggal_lahir"
                                    type="date"
                                    required
                                    class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border py-2 pr-3 pl-9 text-sm transition-all focus:border-transparent focus:ring-2 focus:outline-none"
                                    :class="{
                                        'border-destructive focus:ring-destructive':
                                            form.errors.tanggal_lahir,
                                    }"
                                />
                            </div>
                            <p v-if="form.errors.tanggal_lahir" class="text-destructive text-xs font-medium">
                                {{ form.errors.tanggal_lahir }}
                            </p>
                        </div>
                    </div>

                    <!-- Pekerjaan / Sumber Dana -->
                    <div class="space-y-1.5">
                        <label for="pekerjaan" class="text-foreground block text-xs font-semibold">
                            Pekerjaan / Sumber Dana <span class="text-destructive">*</span>
                        </label>
                        <div class="relative">
                            <div
                                class="text-muted-foreground pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"
                            >
                                <Briefcase class="h-4 w-4" />
                            </div>
                            <input
                                id="pekerjaan"
                                v-model="form.pekerjaan"
                                type="text"
                                required
                                placeholder="Contoh: Karyawan Swasta / Wiraswasta"
                                class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border py-2 pr-3 pl-9 text-sm transition-all focus:border-transparent focus:ring-2 focus:outline-none"
                                :class="{
                                    'border-destructive focus:ring-destructive': form.errors.pekerjaan,
                                }"
                            />
                        </div>
                        <p v-if="form.errors.pekerjaan" class="text-destructive text-xs font-medium">
                            {{ form.errors.pekerjaan }}
                        </p>
                    </div>

                    <!-- Grid: Password & Konfirmasi -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <!-- Kata Sandi -->
                        <div class="space-y-1.5">
                            <label
                                for="password"
                                class="text-foreground block text-xs font-semibold"
                            >
                                Kata Sandi <span class="text-destructive">*</span>
                            </label>
                            <div class="relative">
                                <div
                                    class="text-muted-foreground pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"
                                >
                                    <Lock class="h-4 w-4" />
                                </div>
                                <input
                                    id="password"
                                    v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    autocomplete="new-password"
                                    required
                                    placeholder="Min. 8 karakter"
                                    class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border py-2 pr-9 pl-9 text-sm transition-all focus:border-transparent focus:ring-2 focus:outline-none"
                                    :class="{
                                        'border-destructive focus:ring-destructive':
                                            form.errors.password,
                                    }"
                                />
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="text-muted-foreground hover:text-foreground absolute inset-y-0 right-0 flex items-center pr-3"
                                    tabindex="-1"
                                >
                                    <EyeOff v-if="showPassword" class="h-4 w-4" />
                                    <Eye v-else class="h-4 w-4" />
                                </button>
                            </div>
                            <p
                                v-if="form.errors.password"
                                class="text-destructive text-xs font-medium"
                            >
                                {{ form.errors.password }}
                            </p>
                        </div>

                        <!-- Konfirmasi Kata Sandi -->
                        <div class="space-y-1.5">
                            <label
                                for="password_confirmation"
                                class="text-foreground block text-xs font-semibold"
                            >
                                Konfirmasi Kata Sandi <span class="text-destructive">*</span>
                            </label>
                            <div class="relative">
                                <div
                                    class="text-muted-foreground pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"
                                >
                                    <Lock class="h-4 w-4" />
                                </div>
                                <input
                                    id="password_confirmation"
                                    v-model="form.password_confirmation"
                                    :type="showPassword ? 'text' : 'password'"
                                    autocomplete="new-password"
                                    required
                                    placeholder="Ulangi kata sandi"
                                    class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border py-2 pr-3 pl-9 text-sm transition-all focus:border-transparent focus:ring-2 focus:outline-none"
                                />
                            </div>
                            <p
                                v-if="form.errors.password_confirmation"
                                class="text-destructive text-xs font-medium"
                            >
                                {{ form.errors.password_confirmation }}
                            </p>
                        </div>
                    </div>

                    <!-- Footer Navigasi Step 1 -->
                    <div class="flex items-center justify-end pt-4">
                        <Button
                            type="button"
                            @click="nextStep"
                            class="bg-primary-600 hover:bg-primary-700 flex items-center gap-2 rounded-lg px-6 py-2 font-semibold text-white shadow-xs transition-colors"
                        >
                            <span>Lanjutkan ke Data Ahli Waris</span>
                            <ArrowRight class="h-4 w-4" />
                        </Button>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- LANGKAH 2: PERLINDUNGAN AHLI WARIS         -->
                <!-- ========================================== -->
                <div v-show="currentStep === 2" class="space-y-4">
                    <div
                        class="border-border text-foreground flex items-center justify-between border-b pb-2"
                    >
                        <div class="flex items-center gap-2">
                            <div class="bg-accent-500/10 text-accent-600 rounded-md p-1">
                                <Users class="h-4 w-4" />
                            </div>
                            <div>
                                <h2 class="text-sm font-bold tracking-wider uppercase">
                                    2. Perlindungan Ahli Waris
                                </h2>
                                <p class="text-muted-foreground text-xs">
                                    Kontak darurat resmi untuk pengamanan hak dan aset.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Grid: Nama & Hubungan Ahli Waris -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <!-- Nama Ahli Waris -->
                        <div class="space-y-1.5">
                            <label
                                for="ahli_waris_nama"
                                class="text-foreground block text-xs font-semibold"
                            >
                                Nama Lengkap Ahli Waris <span class="text-destructive">*</span>
                            </label>
                            <div class="relative">
                                <div
                                    class="text-muted-foreground pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"
                                >
                                    <User class="h-4 w-4" />
                                </div>
                                <input
                                    id="ahli_waris_nama"
                                    v-model="form.ahli_waris_nama"
                                    type="text"
                                    required
                                    placeholder="Contoh: Siti Rahmawati"
                                    class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border py-2 pr-3 pl-9 text-sm transition-all focus:ring-2 focus:outline-none"
                                    :class="{
                                        'border-destructive focus:ring-destructive':
                                            form.errors.ahli_waris_nama,
                                    }"
                                />
                            </div>
                            <p
                                v-if="form.errors.ahli_waris_nama"
                                class="text-destructive text-xs font-medium"
                            >
                                {{ form.errors.ahli_waris_nama }}
                            </p>
                        </div>

                        <!-- Hubungan / Relasi Ahli Waris -->
                        <div class="space-y-1.5">
                            <label
                                for="ahli_waris_hubungan"
                                class="text-foreground block text-xs font-semibold"
                            >
                                Hubungan / Relasi <span class="text-destructive">*</span>
                            </label>
                            <select
                                id="ahli_waris_hubungan"
                                v-model="form.ahli_waris_hubungan"
                                required
                                class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border px-3 py-2 text-sm transition-all focus:ring-2 focus:outline-none"
                                :class="{
                                    'border-destructive focus:ring-destructive':
                                        form.errors.ahli_waris_hubungan,
                                }"
                            >
                                <option value="" disabled>-- Pilih Hubungan --</option>
                                <option v-for="opt in hubunganOptions" :key="opt" :value="opt">
                                    {{ opt }}
                                </option>
                            </select>
                            <p
                                v-if="form.errors.ahli_waris_hubungan"
                                class="text-destructive text-xs font-medium"
                            >
                                {{ form.errors.ahli_waris_hubungan }}
                            </p>
                        </div>
                    </div>

                    <!-- Nomor HP Ahli Waris -->
                    <div class="space-y-1.5">
                        <label
                            for="ahli_waris_phone"
                            class="text-foreground block text-xs font-semibold"
                        >
                            Nomor WhatsApp / HP Ahli Waris <span class="text-destructive">*</span>
                        </label>
                        <div class="relative">
                            <div
                                class="text-muted-foreground pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"
                            >
                                <Phone class="h-4 w-4" />
                            </div>
                            <input
                                id="ahli_waris_phone"
                                v-model="form.ahli_waris_phone"
                                type="tel"
                                required
                                placeholder="+628... (Wajib berbeda dari nomor Anda)"
                                class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border py-2 pr-3 pl-9 text-sm transition-all focus:ring-2 focus:outline-none"
                                :class="{
                                    'border-destructive focus:ring-destructive':
                                        form.errors.ahli_waris_phone,
                                }"
                            />
                        </div>
                        <p
                            v-if="form.errors.ahli_waris_phone"
                            class="text-destructive text-xs font-medium"
                        >
                            {{ form.errors.ahli_waris_phone }}
                        </p>
                    </div>

                    <!-- Alamat Domisili Ahli Waris -->
                    <div class="space-y-1.5">
                        <label
                            for="ahli_waris_alamat"
                            class="text-foreground block text-xs font-semibold"
                        >
                            Alamat Domisili Ahli Waris <span class="text-destructive">*</span>
                        </label>
                        <div class="relative">
                            <div
                                class="text-muted-foreground pointer-events-none absolute top-2.5 left-0 flex items-center pl-3"
                            >
                                <MapPin class="h-4 w-4" />
                            </div>
                            <textarea
                                id="ahli_waris_alamat"
                                v-model="form.ahli_waris_alamat"
                                rows="2"
                                required
                                placeholder="Jl. Mawar No. 12, Kelurahan, Kecamatan, Kota"
                                class="bg-background border-input focus:ring-primary-500 w-full resize-none rounded-lg border py-2 pr-3 pl-9 text-sm transition-all focus:border-transparent focus:ring-2 focus:outline-none"
                                :class="{
                                    'border-destructive focus:ring-destructive':
                                        form.errors.ahli_waris_alamat,
                                }"
                            ></textarea>
                        </div>
                        <p
                            v-if="form.errors.ahli_waris_alamat"
                            class="text-destructive text-xs font-medium"
                        >
                            {{ form.errors.ahli_waris_alamat }}
                        </p>
                    </div>

                    <!-- Footer Navigasi Step 2 -->
                    <div class="flex items-center justify-between pt-4">
                        <Button
                            type="button"
                            variant="outline"
                            @click="prevStep"
                            class="flex items-center gap-1.5 px-4 py-2 text-xs"
                        >
                            <ArrowLeft class="h-4 w-4" />
                            <span>Kembali</span>
                        </Button>

                        <Button
                            type="button"
                            @click="nextStep"
                            class="bg-primary-600 hover:bg-primary-700 flex items-center gap-2 rounded-lg px-6 py-2 font-semibold text-white shadow-xs transition-colors"
                        >
                            <span>Selanjutnya</span>
                            <ArrowRight class="h-4 w-4" />
                        </Button>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- LANGKAH 3: AKUN TRADING & BERKAS LEGALITAS -->
                <!-- ========================================== -->
                <div v-show="currentStep === 3" class="space-y-5">
                    <div
                        class="border-border text-foreground flex items-center justify-between border-b pb-2"
                    >
                        <div class="flex items-center gap-2">
                            <div class="rounded-md bg-emerald-500/10 p-1 text-emerald-600">
                                <TrendingUp class="h-4 w-4" />
                            </div>
                            <div>
                                <h2 class="text-sm font-bold tracking-wider uppercase">
                                    3. Akun Trading & Berkas Legalitas
                                </h2>
                                <p class="text-muted-foreground text-xs">
                                    Data akun trading aktif, rekening pencairan, dan unggah berkas resmi.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- === PEMILIHAN TIER INVESTASI === -->
                    <div class="space-y-2">
                        <p class="text-foreground text-xs font-bold">
                            Pilih Tier Investasi <span class="text-destructive">*</span>
                        </p>
                        <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-3">
                            <button
                                v-for="(cfg, key) in tierConfig"
                                :key="key"
                                type="button"
                                @click="form.tier_investasi = key"
                                class="relative rounded-xl border-2 p-3 text-left transition-all"
                                :class="
                                    form.tier_investasi === key
                                        ? 'border-primary-600 bg-primary-500/10'
                                        : 'border-border bg-card/50 hover:border-primary-400'
                                "
                            >
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="text-foreground text-[11px] font-bold leading-tight">
                                            {{ cfg.label }}
                                        </p>
                                        <p class="text-muted-foreground mt-1 text-[10px] leading-snug">
                                            {{ cfg.desc }}
                                        </p>
                                        <span class="text-primary-600 mt-1.5 block text-[10px] font-semibold">
                                            {{ cfg.modalInfo }}
                                        </span>
                                    </div>
                                    <div
                                        class="mt-0.5 shrink-0 h-4 w-4 rounded-full border-2 flex items-center justify-center"
                                        :class="
                                            form.tier_investasi === key
                                                ? 'border-primary-600 bg-primary-600'
                                                : 'border-muted-foreground'
                                        "
                                    >
                                        <CheckCircle2
                                            v-if="form.tier_investasi === key"
                                            class="h-3 w-3 text-white"
                                        />
                                    </div>
                                </div>
                            </button>
                        </div>
                        <p v-if="form.errors.tier_investasi" class="text-destructive text-xs font-medium">
                            {{ form.errors.tier_investasi }}
                        </p>
                    </div>

                    <!-- === DATA AKUN TRADING === -->
                    <div class="space-y-3">
                        <p class="text-foreground border-border border-b pb-1.5 text-xs font-bold">
                            Data Akun Trading Aktif
                        </p>

                        <!-- Nomor Akun Trading -->
                        <div class="space-y-1.5">
                            <label for="nomor_akun_trading" class="text-foreground block text-xs font-semibold">
                                Nomor Akun Trading / Copier (MT4)
                                <span class="text-destructive">*</span>
                            </label>
                            <div class="relative">
                                <div class="text-muted-foreground pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <CreditCard class="h-4 w-4" />
                                </div>
                                <input
                                    id="nomor_akun_trading"
                                    v-model="form.nomor_akun_trading"
                                    type="text"
                                    placeholder="Contoh: 66252077"
                                    class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border py-2 pr-3 pl-9 font-mono text-sm transition-all focus:border-transparent focus:ring-2 focus:outline-none"
                                    :class="{ 'border-destructive focus:ring-destructive': form.errors.nomor_akun_trading }"
                                />
                            </div>
                            <p v-if="form.errors.nomor_akun_trading" class="text-destructive text-xs font-medium">
                                {{ form.errors.nomor_akun_trading }}
                            </p>
                        </div>

                        <!-- Grid: Estimasi Modal & Tanggal Mulai -->
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <!-- Estimasi Modal -->
                            <div class="space-y-1.5">
                                <label for="estimasi_modal" class="text-foreground block text-xs font-semibold">
                                    Estimasi Modal (USD) <span class="text-destructive">*</span>
                                </label>
                                <div class="relative">
                                    <div class="text-muted-foreground pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <Banknote class="h-4 w-4" />
                                    </div>
                                    <input
                                        id="estimasi_modal"
                                        v-model="form.estimasi_modal"
                                        type="number"
                                        min="100"
                                        step="0.01"
                                        placeholder="Contoh: 1000"
                                        class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border py-2 pr-3 pl-9 text-sm transition-all focus:border-transparent focus:ring-2 focus:outline-none"
                                        :class="{ 'border-destructive focus:ring-destructive': form.errors.estimasi_modal }"
                                    />
                                </div>
                                <p v-if="form.errors.estimasi_modal" class="text-destructive text-xs font-medium">
                                    {{ form.errors.estimasi_modal }}
                                </p>
                            </div>

                            <!-- Tanggal Mulai Trading -->
                            <div class="space-y-1.5">
                                <label for="tanggal_mulai_trading" class="text-foreground block text-xs font-semibold">
                                    Tanggal Mulai Copy Trade <span class="text-destructive">*</span>
                                </label>
                                <div class="relative">
                                    <div class="text-muted-foreground pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <Calendar class="h-4 w-4" />
                                    </div>
                                    <input
                                        id="tanggal_mulai_trading"
                                        v-model="form.tanggal_mulai_trading"
                                        type="date"
                                        class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border py-2 pr-3 pl-9 text-sm transition-all focus:border-transparent focus:ring-2 focus:outline-none"
                                        :class="{ 'border-destructive focus:ring-destructive': form.errors.tanggal_mulai_trading }"
                                    />
                                </div>
                                <p v-if="form.errors.tanggal_mulai_trading" class="text-destructive text-xs font-medium">
                                    {{ form.errors.tanggal_mulai_trading }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- === REKENING BANK PENCAIRAN === -->
                    <div class="space-y-3">
                        <p class="text-foreground border-border border-b pb-1.5 text-xs font-bold">
                            Rekening Bank Pencairan (WD)
                        </p>

                        <!-- Nama Bank -->
                        <div class="space-y-1.5">
                            <label for="nama_bank" class="text-foreground block text-xs font-semibold">
                                Bank <span class="text-destructive">*</span>
                            </label>
                            <div class="relative">
                                <div class="text-muted-foreground pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <Building2 class="h-4 w-4" />
                                </div>
                                <select
                                    id="nama_bank"
                                    v-model="selectedBankOption"
                                    class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border py-2 pr-3 pl-9 text-sm transition-all focus:ring-2 focus:outline-none"
                                    :class="{ 'border-destructive focus:ring-destructive': form.errors.nama_bank }"
                                >
                                    <option value="" disabled>-- Pilih Bank --</option>
                                    <option v-for="bank in bankOptions" :key="bank" :value="bank">
                                        {{ bank }}
                                    </option>
                                </select>
                            </div>
                            <!-- Input manual jika pilih "Lainnya" -->
                            <div v-if="selectedBankOption === 'Lainnya'" class="mt-1.5">
                                <input
                                    :value="customBankName"
                                    @input="handleCustomBankInput"
                                    type="text"
                                    placeholder="Ketik nama bank Anda (contoh: Bank Jago, SeaBank)..."
                                    class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border py-2 px-3 text-sm transition-all focus:ring-2 focus:outline-none"
                                    :class="{ 'border-destructive focus:ring-destructive': form.errors.nama_bank }"
                                />
                            </div>
                            <p v-if="form.errors.nama_bank" class="text-destructive text-xs font-medium">
                                {{ form.errors.nama_bank }}
                            </p>
                        </div>

                        <!-- Grid: Nomor Rekening & Atas Nama -->
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div class="space-y-1.5">
                                <label for="nomor_rekening" class="text-foreground block text-xs font-semibold">
                                    Nomor Rekening <span class="text-destructive">*</span>
                                </label>
                                <input
                                    id="nomor_rekening"
                                    v-model="form.nomor_rekening"
                                    type="text"
                                    placeholder="Contoh: 1234567890"
                                    class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border py-2 px-3 font-mono text-sm transition-all focus:ring-2 focus:outline-none"
                                    :class="{ 'border-destructive focus:ring-destructive': form.errors.nomor_rekening }"
                                />
                                <p v-if="form.errors.nomor_rekening" class="text-destructive text-xs font-medium">
                                    {{ form.errors.nomor_rekening }}
                                </p>
                            </div>
                            <div class="space-y-1.5">
                                <label for="atas_nama_rekening" class="text-foreground block text-xs font-semibold">
                                    Atas Nama <span class="text-destructive">*</span>
                                </label>
                                <input
                                    id="atas_nama_rekening"
                                    v-model="form.atas_nama_rekening"
                                    type="text"
                                    :placeholder="form.name || 'Nama pemilik rekening'"
                                    class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border py-2 px-3 text-sm transition-all focus:ring-2 focus:outline-none"
                                    :class="{ 'border-destructive focus:ring-destructive': form.errors.atas_nama_rekening }"
                                />
                                <p v-if="form.errors.atas_nama_rekening" class="text-destructive text-xs font-medium">
                                    {{ form.errors.atas_nama_rekening }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- === KHUSUS TIER 3: KREDENSIAL MT4 === -->
                    <div v-if="form.tier_investasi === 'tier_3'" class="space-y-3">
                        <p class="border-border text-foreground flex items-center gap-1.5 border-b pb-1.5 text-xs font-bold">
                            <Server class="h-3.5 w-3.5 text-violet-600" />
                            Kredensial MT4 Eksklusif (Tier 3)
                        </p>
                        <div
                            class="border-border bg-violet-500/10 rounded-xl border border-dashed p-3 text-xs text-violet-800 dark:text-violet-200"
                        >
                            Data ini digunakan untuk mengelola akun Anda melalui Private VPS. Disimpan
                            terenkripsi dan hanya dapat diakses oleh tim pengelola berwenang.
                        </div>

                        <!-- Server Broker -->
                        <div class="space-y-1.5">
                            <label for="server_broker" class="text-foreground block text-xs font-semibold">
                                Server Broker XM <span class="text-destructive">*</span>
                            </label>
                            <div class="relative">
                                <div class="text-muted-foreground pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <Server class="h-4 w-4" />
                                </div>
                                <input
                                    id="server_broker"
                                    v-model="form.server_broker"
                                    type="text"
                                    placeholder="Contoh: XMGlobal-Real 50"
                                    class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border py-2 pr-3 pl-9 text-sm transition-all focus:ring-2 focus:outline-none"
                                    :class="{ 'border-destructive focus:ring-destructive': form.errors.server_broker }"
                                />
                            </div>
                            <p v-if="form.errors.server_broker" class="text-destructive text-xs font-medium">
                                {{ form.errors.server_broker }}
                            </p>
                        </div>

                        <!-- Grid: Master & Investor Password -->
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <!-- Master Password -->
                            <div class="space-y-1.5">
                                <label for="master_password" class="text-foreground block text-xs font-semibold">
                                    Master Password MT4 <span class="text-destructive">*</span>
                                </label>
                                <div class="relative">
                                    <div class="text-muted-foreground pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <KeyRound class="h-4 w-4" />
                                    </div>
                                    <input
                                        id="master_password"
                                        v-model="form.master_password"
                                        :type="showMasterPassword ? 'text' : 'password'"
                                        placeholder="Master password MT4"
                                        class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border py-2 pr-9 pl-9 text-sm transition-all focus:ring-2 focus:outline-none"
                                        :class="{ 'border-destructive focus:ring-destructive': form.errors.master_password }"
                                    />
                                    <button
                                        type="button"
                                        @click="showMasterPassword = !showMasterPassword"
                                        class="text-muted-foreground hover:text-foreground absolute inset-y-0 right-0 flex items-center pr-3"
                                        tabindex="-1"
                                    >
                                        <EyeOff v-if="showMasterPassword" class="h-4 w-4" />
                                        <Eye v-else class="h-4 w-4" />
                                    </button>
                                </div>
                                <p v-if="form.errors.master_password" class="text-destructive text-xs font-medium">
                                    {{ form.errors.master_password }}
                                </p>
                            </div>

                            <!-- Investor Password -->
                            <div class="space-y-1.5">
                                <label for="investor_password" class="text-foreground block text-xs font-semibold">
                                    Investor Password MT4 <span class="text-destructive">*</span>
                                </label>
                                <div class="relative">
                                    <div class="text-muted-foreground pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <KeyRound class="h-4 w-4" />
                                    </div>
                                    <input
                                        id="investor_password"
                                        v-model="form.investor_password"
                                        :type="showInvestorPassword ? 'text' : 'password'"
                                        placeholder="Investor password MT4"
                                        class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border py-2 pr-9 pl-9 text-sm transition-all focus:ring-2 focus:outline-none"
                                        :class="{ 'border-destructive focus:ring-destructive': form.errors.investor_password }"
                                    />
                                    <button
                                        type="button"
                                        @click="showInvestorPassword = !showInvestorPassword"
                                        class="text-muted-foreground hover:text-foreground absolute inset-y-0 right-0 flex items-center pr-3"
                                        tabindex="-1"
                                    >
                                        <EyeOff v-if="showInvestorPassword" class="h-4 w-4" />
                                        <Eye v-else class="h-4 w-4" />
                                    </button>
                                </div>
                                <p v-if="form.errors.investor_password" class="text-destructive text-xs font-medium">
                                    {{ form.errors.investor_password }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- === UNDUH TEMPLATE SURAT PERJANJIAN === -->
                    <div class="border-border rounded-xl border bg-amber-500/5 p-4 space-y-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-foreground text-xs font-bold">
                                    Template Surat Perjanjian Resmi
                                </p>
                                <p class="text-muted-foreground mt-0.5 text-[11px]">
                                    {{ tierConfig[form.tier_investasi]?.label }}
                                </p>
                            </div>
                            <button
                                type="button"
                                @click="downloadTemplate"
                                :disabled="isDownloadingTemplate"
                                class="flex shrink-0 items-center gap-1.5 rounded-lg bg-amber-600 px-3.5 py-2 text-xs font-semibold text-white shadow-xs transition-all hover:bg-amber-700 active:scale-95 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                <Loader2 v-if="isDownloadingTemplate" class="h-3.5 w-3.5 animate-spin" />
                                <Download v-else class="h-3.5 w-3.5" />
                                <span>{{ isDownloadingTemplate ? 'Menyiapkan .docx...' : 'Unduh .docx' }}</span>
                            </button>
                        </div>

                        <!-- Notifikasi Pengisian Otomatis Sesuai Form -->
                        <div class="flex items-start gap-2.5 rounded-lg border border-amber-500/30 bg-amber-500/10 p-3 text-xs text-amber-900 dark:text-amber-200">
                            <Info class="mt-0.5 h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400" />
                            <div class="space-y-0.5 text-[11px] leading-relaxed">
                                <p class="font-bold text-amber-950 dark:text-amber-100">
                                    Pengisian Otomatis Dokumen:
                                </p>
                                <p>
                                    Harap isi dan lengkapi semua field inputan formulir sebelum mengunduh template agar berkas surat perjanjian terisi otomatis dengan data Anda.
                                </p>
                            </div>
                        </div>

                        <ol class="text-muted-foreground list-decimal space-y-1 pl-4 text-[11px]">
                            <li>Unduh berkas template surat perjanjian resmi di atas.</li>
                            <li>Cetak dokumen, tempel meterai Rp10.000, dan tanda tangani di atas meterai.</li>
                            <li>Foto atau scan dokumen bermaterai tersebut, lalu unggah di bawah.</li>
                        </ol>
                    </div>

                    <!-- === UNGGAH BERKAS LEGALITAS === -->
                    <div class="space-y-3">
                        <p class="text-foreground border-border border-b pb-1.5 text-xs font-bold">
                            Unggah Berkas Legalitas
                        </p>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <!-- Upload KTP -->
                            <div class="border-border bg-card/60 space-y-3 rounded-xl border p-4">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <label class="text-foreground block text-xs font-bold">
                                            Foto KTP <span class="text-destructive">*</span>
                                        </label>
                                        <p class="text-muted-foreground mt-0.5 text-[11px]">
                                            JPG, JPEG, PNG (Maks 10 MB)
                                        </p>
                                    </div>
                                    <span class="bg-primary-500/10 text-primary-600 rounded px-2 py-0.5 text-[10px] font-bold">Wajib</span>
                                </div>

                                <div
                                    class="border-input hover:border-primary-500 bg-background/50 group relative cursor-pointer rounded-xl border-2 border-dashed p-4 text-center transition-all"
                                >
                                    <input
                                        type="file"
                                        accept="image/jpeg,image/png,image/jpg"
                                        @change="handleKtpChange"
                                        class="absolute inset-0 z-10 h-full w-full cursor-pointer opacity-0"
                                    />
                                    <div v-if="ktpCompressing" class="flex flex-col items-center justify-center space-y-2 py-4">
                                        <Loader2 class="text-primary-600 h-6 w-6 animate-spin" />
                                        <p class="text-foreground text-xs font-semibold">Memproses foto KTP...</p>
                                    </div>
                                    <div v-else-if="ktpInfo" class="space-y-2">
                                        <img
                                            v-if="ktpInfo.previewUrl"
                                            :src="ktpInfo.previewUrl"
                                            alt="Preview KTP"
                                            class="border-border mx-auto h-28 rounded-lg border object-contain shadow-xs"
                                        />
                                        <p class="text-foreground flex items-center justify-center gap-1.5 text-xs font-semibold break-all px-2">
                                            <CheckCircle class="h-4 w-4 text-emerald-600 shrink-0" />
                                            <span>{{ ktpInfo.name || 'Foto KTP Terpilih' }}</span>
                                        </p>
                                        <div v-if="ktpInfo.isQualityWarning" class="mt-2 flex items-start gap-2.5 rounded-lg border border-amber-500/30 bg-amber-500/10 p-3 text-left text-xs text-amber-800 dark:text-amber-200">
                                            <AlertCircle class="mt-0.5 h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400" />
                                            <p class="text-[11px] leading-relaxed">{{ ktpInfo.warningMessage || 'Foto terindikasi buram. Pastikan teks NIK dan nama terbaca jelas.' }}</p>
                                        </div>
                                        <span class="border-input bg-background/80 text-primary-600 hover:bg-muted inline-flex items-center gap-1.5 rounded-md border px-2.5 py-1 text-[11px] font-semibold shadow-2xs">
                                            <Edit3 class="h-3 w-3" /> Ganti Foto KTP
                                        </span>
                                    </div>
                                    <div v-else class="flex flex-col items-center justify-center space-y-1.5 py-4">
                                        <div class="bg-primary-500/10 text-primary-600 flex h-10 w-10 items-center justify-center rounded-full transition-transform group-hover:scale-110">
                                            <UploadCloud class="h-5 w-5" />
                                        </div>
                                        <p class="text-foreground text-xs font-semibold">Pilih atau seret foto KTP</p>
                                        <p class="text-muted-foreground text-[10px]">Pastikan seluruh teks identitas terbaca jelas</p>
                                    </div>
                                </div>
                                <p v-if="form.errors.dokumen_ktp" class="text-destructive text-xs font-medium">
                                    {{ form.errors.dokumen_ktp }}
                                </p>
                            </div>

                            <!-- Upload Perjanjian -->
                            <div class="border-border bg-card/60 space-y-3 rounded-xl border p-4">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <label class="text-foreground block text-xs font-bold">
                                            Surat Perjanjian Bermaterai <span class="text-destructive">*</span>
                                        </label>
                                        <p class="text-muted-foreground mt-0.5 text-[11px]">
                                            JPG, PNG, atau PDF (Maks 10 MB)
                                        </p>
                                    </div>
                                    <span class="bg-primary-500/10 text-primary-600 rounded px-2 py-0.5 text-[10px] font-bold">Wajib</span>
                                </div>

                                <div
                                    class="border-input hover:border-primary-500 bg-background/50 group relative cursor-pointer rounded-xl border-2 border-dashed p-4 text-center transition-all"
                                >
                                    <input
                                        type="file"
                                        accept="image/jpeg,image/png,image/jpg,application/pdf"
                                        @change="handlePerjanjianChange"
                                        class="absolute inset-0 z-10 h-full w-full cursor-pointer opacity-0"
                                    />
                                    <div v-if="perjanjianCompressing" class="flex flex-col items-center justify-center space-y-2 py-4">
                                        <Loader2 class="text-primary-600 h-6 w-6 animate-spin" />
                                        <p class="text-foreground text-xs font-semibold">Memproses berkas perjanjian...</p>
                                    </div>
                                    <div v-else-if="perjanjianInfo" class="space-y-2">
                                        <template v-if="perjanjianInfo.isPdf">
                                            <div class="mx-auto flex h-24 flex-col items-center justify-center rounded-lg border border-emerald-500/20 bg-emerald-500/10 px-4 py-2 text-emerald-600">
                                                <FileIcon class="mb-1 h-7 w-7" />
                                                <span class="text-[11px] font-semibold">Dokumen PDF</span>
                                            </div>
                                        </template>
                                        <template v-else>
                                            <img
                                                v-if="perjanjianInfo.previewUrl"
                                                :src="perjanjianInfo.previewUrl"
                                                alt="Preview Perjanjian"
                                                class="border-border mx-auto h-28 rounded-lg border object-contain shadow-xs"
                                            />
                                        </template>
                                        <p class="text-foreground flex items-center justify-center gap-1.5 text-xs font-semibold break-all px-2">
                                            <CheckCircle class="h-4 w-4 text-emerald-600 shrink-0" />
                                            <span>{{ perjanjianInfo.name || 'Berkas Perjanjian Terpilih' }}</span>
                                        </p>
                                        <div v-if="perjanjianInfo.isQualityWarning" class="mt-2 flex items-start gap-2.5 rounded-lg border border-amber-500/30 bg-amber-500/10 p-3 text-left text-xs text-amber-800 dark:text-amber-200">
                                            <AlertCircle class="mt-0.5 h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400" />
                                            <p class="text-[11px] leading-relaxed">{{ perjanjianInfo.warningMessage || 'Berkas terindikasi buram. Pastikan materai dan tanda tangan terbaca.' }}</p>
                                        </div>
                                        <span class="border-input bg-background/80 text-primary-600 hover:bg-muted inline-flex items-center gap-1.5 rounded-md border px-2.5 py-1 text-[11px] font-semibold shadow-2xs">
                                            <Edit3 class="h-3 w-3" /> Ganti Berkas Perjanjian
                                        </span>
                                    </div>
                                    <div v-else class="flex flex-col items-center justify-center space-y-1.5 py-4">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-500/10 text-emerald-600 transition-transform group-hover:scale-110">
                                            <UploadCloud class="h-5 w-5" />
                                        </div>
                                        <p class="text-foreground text-xs font-semibold">Pilih berkas Surat Perjanjian</p>
                                        <p class="text-muted-foreground text-[10px]">Hasil foto atau scan PDF</p>
                                    </div>
                                </div>
                                <p v-if="form.errors.dokumen_perjanjian" class="text-destructive text-xs font-medium">
                                    {{ form.errors.dokumen_perjanjian }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Navigasi Step 3 -->
                    <div class="flex items-center justify-between pt-4">
                        <Button
                            type="button"
                            variant="outline"
                            @click="prevStep"
                            class="flex items-center gap-1.5 px-4 py-2 text-xs"
                        >
                            <ArrowLeft class="h-4 w-4" />
                            <span>Kembali</span>
                        </Button>

                        <Button
                            type="button"
                            @click="nextStep"
                            class="bg-primary-600 hover:bg-primary-700 flex items-center gap-2 rounded-lg px-6 py-2 font-semibold text-white shadow-xs transition-colors"
                        >
                            <span>Lanjutkan ke Persetujuan</span>
                            <ArrowRight class="h-4 w-4" />
                        </Button>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- LANGKAH 4: SYARAT, KETENTUAN & SUBMIT      -->
                <!-- ========================================== -->
                <div v-show="currentStep === 4" class="space-y-4">
                    <div
                        class="border-border text-foreground flex items-center gap-2 border-b pb-2"
                    >
                        <div class="bg-primary-500/10 text-primary-600 rounded-md p-1">
                            <CheckCircle2 class="h-4 w-4" />
                        </div>
                        <div>
                            <h2 class="text-sm font-bold tracking-wider uppercase">
                                4. Konfirmasi & Persetujuan Layanan
                            </h2>
                            <p class="text-muted-foreground text-xs">
                                Periksa ringkasan pendaftaran Anda sebelum mengirimkannya.
                            </p>
                        </div>
                    </div>

                    <!-- Ringkasan Data Pendaftaran -->
                    <div
                        class="border-border bg-card/60 divide-border/60 divide-y rounded-xl border text-xs"
                    >
                        <!-- Akun Ringkasan -->
                        <div class="flex items-start justify-between p-3.5">
                            <div class="space-y-1 min-w-0 flex-1">
                                <p class="text-foreground flex items-center gap-1.5 font-bold">
                                    <User class="text-primary-500 h-3.5 w-3.5" />
                                    <span>{{ form.name || '-' }}</span>
                                    <span class="text-muted-foreground font-normal"
                                        >(@{{ form.username }})</span
                                    >
                                </p>
                                <p class="text-muted-foreground">
                                    Email:
                                    <strong class="text-foreground font-medium">{{
                                        form.email
                                    }}</strong>
                                    &bull; HP:
                                    <strong class="text-foreground font-medium">{{
                                        form.phone
                                    }}</strong>
                                </p>
                                <p class="text-muted-foreground">
                                    NIK:
                                    <span class="text-foreground font-mono font-medium">{{
                                        form.nik
                                    }}</span>
                                </p>
                                <p class="text-muted-foreground">
                                    TTL: <span class="text-foreground font-medium">{{ form.tempat_lahir }}, {{ form.tanggal_lahir }}</span>
                                    &bull; Pekerjaan: <span class="text-foreground font-medium">{{ form.pekerjaan }}</span>
                                </p>
                                <p class="text-muted-foreground max-w-sm truncate sm:max-w-md">
                                    Domisili: {{ form.alamat }}
                                </p>
                            </div>
                            <button
                                type="button"
                                @click="jumpToStep(1)"
                                class="text-primary-600 dark:text-primary-400 ml-2 flex shrink-0 items-center gap-1 text-[11px] font-semibold hover:underline"
                            >
                                <Edit3 class="h-3 w-3" /> Ubah
                            </button>
                        </div>

                        <!-- Ahli Waris Ringkasan -->
                        <div class="flex items-start justify-between p-3.5">
                            <div class="space-y-1">
                                <p class="text-foreground flex items-center gap-1.5 font-bold">
                                    <Users class="text-accent-500 h-3.5 w-3.5" />
                                    <span>Ahli Waris: {{ form.ahli_waris_nama || '-' }}</span>
                                    <span class="text-muted-foreground font-normal"
                                        >({{ form.ahli_waris_hubungan }})</span
                                    >
                                </p>
                                <p class="text-muted-foreground">
                                    Kontak Darurat:
                                    <strong class="text-foreground font-medium">{{
                                        form.ahli_waris_phone
                                    }}</strong>
                                </p>
                                <p class="text-muted-foreground max-w-sm truncate sm:max-w-md">
                                    Alamat: {{ form.ahli_waris_alamat || '-' }}
                                </p>
                            </div>
                            <button
                                type="button"
                                @click="jumpToStep(2)"
                                class="text-primary-600 dark:text-primary-400 ml-2 flex shrink-0 items-center gap-1 text-[11px] font-semibold hover:underline"
                            >
                                <Edit3 class="h-3 w-3" /> Ubah
                            </button>
                        </div>

                        <!-- Trading & Rekening Ringkasan -->
                        <div class="flex items-start justify-between p-3.5">
                            <div class="space-y-1 min-w-0 flex-1">
                                <p class="text-foreground flex items-center gap-1.5 font-bold">
                                    <TrendingUp class="h-3.5 w-3.5 text-emerald-500" />
                                    <span>Akun Trading: {{ tierConfig[form.tier_investasi]?.label }}</span>
                                </p>
                                <p class="text-muted-foreground">
                                    No. Akun: <span class="text-foreground font-mono font-medium">{{ form.nomor_akun_trading }}</span>
                                    &bull; Modal: <span class="text-foreground font-medium">${{ form.estimasi_modal }}</span>
                                </p>
                                <p class="text-muted-foreground">
                                    Mulai: <span class="text-foreground font-medium">{{ form.tanggal_mulai_trading }}</span>
                                </p>
                                <p class="text-muted-foreground">
                                    Rekening:
                                    <span class="text-foreground font-medium">{{ form.nama_bank }} – {{ form.nomor_rekening }}</span>
                                    a.n. <span class="text-foreground font-medium">{{ form.atas_nama_rekening }}</span>
                                </p>
                            </div>
                            <button
                                type="button"
                                @click="jumpToStep(3)"
                                class="text-primary-600 dark:text-primary-400 ml-2 flex shrink-0 items-center gap-1 text-[11px] font-semibold hover:underline"
                            >
                                <Edit3 class="h-3 w-3" /> Ubah
                            </button>
                        </div>

                        <!-- Dokumen Ringkasan -->
                        <div class="flex items-start justify-between p-3.5">
                            <div class="space-y-1">
                                <p class="text-foreground flex items-center gap-1.5 font-bold">
                                    <FileText class="h-3.5 w-3.5 text-emerald-500" />
                                    <span>Berkas Legalitas:</span>
                                </p>
                                <div class="flex flex-wrap items-center gap-2 pt-0.5">
                                    <span
                                        class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[11px] font-semibold"
                                        :class="
                                            form.dokumen_ktp
                                                ? 'bg-emerald-500/10 text-emerald-600'
                                                : 'bg-destructive/10 text-destructive'
                                        "
                                    >
                                        <CheckCircle v-if="form.dokumen_ktp" class="h-3 w-3" />
                                        <span
                                            >Foto KTP:
                                            {{ form.dokumen_ktp ? 'Siap' : 'Belum Dipilih' }}</span
                                        >
                                    </span>
                                    <span
                                        class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[11px] font-semibold"
                                        :class="
                                            form.dokumen_perjanjian
                                                ? 'bg-emerald-500/10 text-emerald-600'
                                                : 'bg-destructive/10 text-destructive'
                                        "
                                    >
                                        <CheckCircle
                                            v-if="form.dokumen_perjanjian"
                                            class="h-3 w-3"
                                        />
                                        <span
                                            >Surat Perjanjian:
                                            {{
                                                form.dokumen_perjanjian ? 'Siap' : 'Belum Dipilih'
                                            }}</span
                                        >
                                    </span>
                                </div>
                            </div>
                            <button
                                type="button"
                                @click="jumpToStep(3)"
                                class="text-primary-600 dark:text-primary-400 ml-2 flex shrink-0 items-center gap-1 text-[11px] font-semibold hover:underline"
                            >
                                <Edit3 class="h-3 w-3" /> Ubah
                            </button>
                        </div>
                    </div>

                    <!-- Persetujuan Syarat & Ketentuan -->
                    <div class="space-y-1.5">
                        <!-- Checkbox Persetujuan -->
                        <label class="flex cursor-pointer items-start gap-2.5 pt-1">
                            <input
                                v-model="form.terms"
                                type="checkbox"
                                required
                                class="border-input text-primary-600 focus:ring-primary-500 mt-0.5 h-4 w-4 cursor-pointer rounded"
                            />
                            <span class="text-muted-foreground text-xs leading-relaxed select-none">
                                Saya telah membaca, memahami, dan menyetujui secara sadar seluruh
                                klausul pada
                                <button
                                    type="button"
                                    @click.prevent="openTermsModal"
                                    class="text-primary-600 dark:text-primary-400 inline font-semibold hover:underline"
                                >
                                    Syarat & Ketentuan Layanan
                                </button>
                                serta
                                <button
                                    type="button"
                                    @click.prevent="openPrivacyModal"
                                    class="text-primary-600 dark:text-primary-400 inline font-semibold hover:underline"
                                >
                                    Kebijakan Privasi
                                </button>
                                platform YZD Center.
                            </span>
                        </label>

                        <p v-if="form.errors.terms" class="text-destructive text-xs font-medium">
                            {{ form.errors.terms }}
                        </p>
                    </div>

                    <p v-if="form.errors.invite_token" class="text-destructive text-xs font-medium">
                        {{ form.errors.invite_token }}
                    </p>

                    <!-- Footer Navigasi Step 4 & Tombol Submit Final -->
                    <div class="flex items-center justify-between pt-4">
                        <Button
                            type="button"
                            variant="outline"
                            @click="prevStep"
                            class="flex items-center gap-1.5 px-4 py-2 text-xs"
                        >
                            <ArrowLeft class="h-4 w-4" />
                            <span>Kembali</span>
                        </Button>

                        <Button
                            type="submit"
                            :disabled="form.processing || ktpCompressing || perjanjianCompressing"
                            class="bg-primary-600 hover:bg-primary-700 flex items-center justify-center gap-2 rounded-lg px-6 py-2.5 text-xs font-semibold text-white shadow-sm transition-colors disabled:opacity-50 sm:text-sm"
                        >
                            <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                            <UserCheck v-else class="h-4 w-4" />
                            <span>{{ form.processing ? 'Mengirim Pendaftaran...' : 'Kirim' }}</span>
                        </Button>
                    </div>
                </div>
            </form>

            <div class="pt-2 text-center">
                <p class="text-muted-foreground text-xs">
                    Sudah memiliki akun terdaftar?
                    <Link
                        href="/login"
                        class="text-primary-600 dark:text-primary-400 ml-1 font-semibold hover:underline"
                    >
                        Masuk di sini
                    </Link>
                </p>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- MODAL PEMBACA SYARAT & KETENTUAN LENGKAP    -->
        <!-- ========================================== -->
        <AppModal
            :show="isTermsModalOpen"
            :title="terms?.judul || 'Syarat & Ketentuan'"
            max-width="2xl"
            @close="closeTermsModal"
        >
            <div
                class="text-foreground max-h-[60vh] overflow-y-auto pr-2 text-xs leading-relaxed sm:text-sm"
            >
                <div
                    class="prose prose-sm max-w-none text-xs leading-relaxed [--tw-prose-body:var(--color-foreground)] [--tw-prose-bold:var(--color-foreground)] [--tw-prose-bullets:var(--color-muted-foreground)] [--tw-prose-counters:var(--color-muted-foreground)] [--tw-prose-headings:var(--color-foreground)] [--tw-prose-quote-borders:var(--color-primary-500)] [--tw-prose-quotes:var(--color-muted-foreground)] sm:text-sm [&_blockquote]:my-2 [&_h2]:mt-5 [&_h2]:mb-1.5 [&_h3]:mt-4 [&_h3]:mb-1 [&_li]:my-1 [&_ol]:my-1.5 [&_p]:my-1.5 [&_ul]:my-1.5 [&>*:first-child]:mt-0"
                    v-html="termsHtml"
                ></div>
            </div>

            <template #footer>
                <Button
                    type="button"
                    class="bg-primary-600 hover:bg-primary-700 px-4 text-xs text-white"
                    @click="closeTermsModal"
                >
                    Saya Mengerti
                </Button>
            </template>
        </AppModal>

        <!-- ========================================== -->
        <!-- MODAL PEMBACA KEBIJAKAN PRIVASI LENGKAP     -->
        <!-- ========================================== -->
        <AppModal
            :show="isPrivacyModalOpen"
            :title="privacy?.judul || 'Kebijakan Privasi YZD Center'"
            max-width="2xl"
            @close="closePrivacyModal"
        >
            <div
                class="text-foreground max-h-[60vh] overflow-y-auto pr-2 text-xs leading-relaxed sm:text-sm"
            >
                <div
                    class="prose prose-sm max-w-none text-xs leading-relaxed [--tw-prose-body:var(--color-foreground)] [--tw-prose-bold:var(--color-foreground)] [--tw-prose-bullets:var(--color-muted-foreground)] [--tw-prose-counters:var(--color-muted-foreground)] [--tw-prose-headings:var(--color-foreground)] [--tw-prose-quote-borders:var(--color-primary-500)] [--tw-prose-quotes:var(--color-muted-foreground)] sm:text-sm [&_blockquote]:my-2 [&_h2]:mt-5 [&_h2]:mb-1.5 [&_h3]:mt-4 [&_h3]:mb-1 [&_li]:my-1 [&_ol]:my-1.5 [&_p]:my-1.5 [&_ul]:my-1.5 [&>*:first-child]:mt-0"
                    v-html="privacyHtml"
                ></div>
            </div>

            <template #footer>
                <Button
                    type="button"
                    class="bg-primary-600 hover:bg-primary-700 px-4 text-xs text-white"
                    @click="closePrivacyModal"
                >
                    Saya Mengerti
                </Button>
            </template>
        </AppModal>

        <!-- Modal Konfirmasi Meninggalkan Halaman / Navigasi Internal -->
        <AppConfirmDialog
            :show="showLeaveConfirmation"
            title="Formulir Belum Selesai"
            message="Seluruh data pendaftaran dan berkas yang telah Anda isi akan hilang jika Anda meninggalkan halaman ini. Apakah Anda yakin ingin keluar?"
            type="warning"
            confirm-text="Ya, Tinggalkan Halaman"
            cancel-text="Lanjutkan Mendaftar"
            @confirm="confirmLeave"
            @cancel="cancelLeave"
        />
    </AuthLayout>
</template>
