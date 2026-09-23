<script setup>
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import { Sparkles, Dices, RefreshCw } from 'lucide-vue-next';

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    ktpInfo: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['update:ktpInfo']);

const isGenerating = ref(false);

const indonesianNames = [
    'Budi Pratama',
    'Ahmad Fauzi',
    'Siti Rahmawati',
    'Rizky Kurniawan',
    'Dewi Anggraini',
    'Hendra Wijaya',
    'Fajar Ramadhan',
    'Nurul Hidayah',
    'Bambang Pamungkas',
    'Putri Wulandari',
    'Aris Munandar',
    'Eka Saputra',
    'Indah Permata',
    'Tri Wahyudi',
    'Dian Sastro',
];

const warisNames = [
    'Sri Wahyuni',
    'Dimas Prasetyo',
    'Lestari Indah',
    'Bayu Nugroho',
    'Ratna Sari',
    'Surya Dharma',
    'Mega Utami',
    'Aditya Pratama',
];

const hubunganList = ['Anak', 'Istri', 'Suami', 'Orang Tua', 'Saudara Kandung'];

const streetNames = [
    'Jl. Jenderal Sudirman No. ',
    'Jl. M.H. Thamrin No. ',
    'Jl. Gatot Subroto No. ',
    'Jl. H.R. Rasuna Said No. ',
    'Jl. Asia Afrika No. ',
    'Jl. Diponegoro No. ',
];

const cityList = [
    'Jakarta Pusat',
    'Jakarta Selatan',
    'Bandung',
    'Surabaya',
    'Semarang',
    'Yogyakarta',
];

function getRandomItem(array) {
    return array[Math.floor(Math.random() * array.length)];
}

function getRandomDigits(length) {
    let result = '';
    for (let i = 0; i < length; i++) {
        result += Math.floor(Math.random() * 10).toString();
    }
    return result;
}

function formatBytes(bytes, decimals = 1) {
    if (!bytes || bytes === 0) return '0 B';
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}

// Buat dummy foto KTP dengan canvas (tajam, dimensi 800x500, varians laplacian tinggi tanpa blur)
function generateDummyKtpFile(nama, nik) {
    return new Promise((resolve) => {
        const canvas = document.createElement('canvas');
        canvas.width = 800;
        canvas.height = 500;
        const ctx = canvas.getContext('2d');

        // Background kartu gradasi biru
        const grad = ctx.createLinearGradient(0, 0, 800, 500);
        grad.addColorStop(0, '#2563eb');
        grad.addColorStop(1, '#1e3a8a');
        ctx.fillStyle = grad;
        ctx.fillRect(0, 0, 800, 500);

        // Border kartu
        ctx.strokeStyle = '#93c5fd';
        ctx.lineWidth = 4;
        ctx.strokeRect(20, 20, 760, 460);

        // Header KTP
        ctx.fillStyle = '#ffffff';
        ctx.font = 'bold 24px sans-serif';
        ctx.fillText('PROVINSI DKI JAKARTA', 260, 55);
        ctx.font = 'bold 20px sans-serif';
        ctx.fillText('KOTA JAKARTA PUSAT', 280, 85);

        // Teks NIK & Data Diri
        ctx.font = 'bold 22px monospace';
        ctx.fillText(`NIK : ${nik}`, 40, 135);

        ctx.font = '16px sans-serif';
        ctx.fillText(`Nama              : ${nama.toUpperCase()}`, 40, 180);
        ctx.fillText('Tempat/Tgl Lahir  : JAKARTA, 12-08-1990', 40, 215);
        ctx.fillText('Jenis Kelamin     : LAKI-LAKI', 40, 250);
        ctx.fillText('Alamat            : JL. DUMMY TESTING NO. 123', 40, 285);
        ctx.fillText('   RT/RW          : 005/002', 40, 315);
        ctx.fillText('Agama             : ISLAM', 40, 345);
        ctx.fillText('Status Perkawinan : KAWIN', 40, 375);
        ctx.fillText('Pekerjaan         : KARYAWAN SWASTA', 40, 405);
        ctx.fillText('Kewarganegaraan   : WNI', 40, 435);

        // Pas Foto dummy merah
        ctx.fillStyle = '#dc2626';
        ctx.fillRect(600, 140, 150, 200);
        ctx.fillStyle = '#ffffff';
        ctx.font = 'bold 18px sans-serif';
        ctx.fillText('PAS FOTO', 635, 245);

        // Tanda tangan
        ctx.strokeStyle = '#ffffff';
        ctx.lineWidth = 3;
        ctx.beginPath();
        ctx.moveTo(610, 390);
        ctx.bezierCurveTo(630, 360, 670, 420, 730, 380);
        ctx.stroke();

        canvas.toBlob(
            (blob) => {
                const file = new File([blob], 'ktp_dummy.jpg', { type: 'image/jpeg' });
                resolve({
                    file,
                    info: {
                        originalSize: formatBytes(file.size),
                        compressedSize: formatBytes(file.size),
                        isCompressed: false,
                        previewUrl: URL.createObjectURL(blob),
                        name: 'ktp_dummy.jpg',
                        isQualityWarning: false,
                        warningMessage: null,
                    },
                });
            },
            'image/jpeg',
            0.92
        );
    });
}

const handleAutoFill = async () => {
    isGenerating.value = true;
    try {
        const randName = getRandomItem(indonesianNames);
        const randWarisName = getRandomItem(warisNames.filter((n) => n !== randName));
        const randHubungan = getRandomItem(hubunganList);
        const randCity = getRandomItem(cityList);
        const randStreet = getRandomItem(streetNames);
        const uniqueId = Date.now().toString().slice(-4);
        const cleanName = randName.toLowerCase().replace(/[^a-z0-9]/g, '');

        const nik = `3201${getRandomDigits(12)}`;
        const phone = `0812${getRandomDigits(8)}`;
        const warisPhone = `0856${getRandomDigits(8)}`;
        const alamat = `${randStreet}${Math.floor(1 + Math.random() * 150)}, RT 0${Math.floor(1 + Math.random() * 9)}/RW 0${Math.floor(1 + Math.random() * 9)}, ${randCity}`;

        // Random tanggal lahir antara 1970-2000
        const lahirYear = 1970 + Math.floor(Math.random() * 30);
        const lahirMonth = String(1 + Math.floor(Math.random() * 12)).padStart(2, '0');
        const lahirDay = String(1 + Math.floor(Math.random() * 28)).padStart(2, '0');
        const tanggalLahir = `${lahirYear}-${lahirMonth}-${lahirDay}`;

        const pekerjaanList = [
            'Karyawan Swasta', 'Wiraswasta', 'Pengusaha', 'Pedagang',
            'Guru / Dosen', 'Dokter', 'Teknisi IT', 'Petani',
        ];

        // 1. Data Akun (Password selalu asdf1234)
        props.form.name = randName;
        props.form.username = `${cleanName}_${uniqueId}`;
        props.form.email = `${cleanName}.${uniqueId}@example.test`;
        props.form.phone = phone;
        props.form.nik = nik;
        props.form.alamat = alamat;
        props.form.tempat_lahir = randCity;
        props.form.tanggal_lahir = tanggalLahir;
        props.form.pekerjaan = getRandomItem(pekerjaanList);
        props.form.password = 'asdf1234';
        props.form.password_confirmation = 'asdf1234';

        // 2. Data Ahli Waris
        props.form.ahli_waris_nama = randWarisName;
        props.form.ahli_waris_hubungan = randHubungan;
        props.form.ahli_waris_phone = warisPhone;
        props.form.ahli_waris_alamat = alamat;

        // 4. Dokumen Legalitas (Canvas Dummy Berkualitas Tinggi)
        const ktpResult = await generateDummyKtpFile(randName, nik);
        props.form.dokumen_ktp = ktpResult.file;
        emit('update:ktpInfo', ktpResult.info);

        // 5. Persetujuan Syarat & Ketentuan
        props.form.terms = true;
        props.form.referral_code = '';

        // Bersihkan error validasi sebelumnya
        props.form.clearErrors();

        toast.success(`Form diisi acak! Password: asdf1234`);
    } catch (e) {
        toast.error('Gagal mengisi form otomatis.');
    } finally {
        isGenerating.value = false;
    }
};

const handleReset = () => {
    props.form.reset();
    props.form.password = '';
    props.form.password_confirmation = '';
    props.form.terms = false;
    props.form.referral_code = '';
    props.form.dokumen_ktp = null;
    emit('update:ktpInfo', null);
    props.form.clearErrors();
    toast.info('Form pendaftaran telah dikosongkan.');
};
</script>

<template>
    <!-- Dev Tool Banner (Tampil di atas form wizard) -->
    <div
        class="border-amber-500/40 bg-amber-500/10 dark:bg-amber-950/20 text-foreground relative overflow-hidden rounded-xl border border-dashed p-3 text-xs shadow-xs"
    >
        <div class="flex flex-wrap items-center justify-between gap-2.5">
            <div class="flex items-center gap-2">
                <div class="bg-amber-500/20 text-amber-600 dark:text-amber-400 rounded-lg p-1.5">
                    <Sparkles class="h-4 w-4" />
                </div>
                <div>
                    <div class="flex items-center gap-1.5">
                        <span class="font-bold text-amber-700 dark:text-amber-300">
                            Dev Tool: Auto-Fill Form
                        </span>
                        <span
                            class="bg-amber-500/20 text-amber-700 dark:text-amber-300 rounded px-1.5 py-0.5 text-[10px] font-semibold"
                        >
                            Testing Only
                        </span>
                    </div>
                    <p class="text-muted-foreground text-[11px]">
                        Isi otomatis Akun, Ahli Waris, Berkas KTP dummy & T&C (Password:
                        <code
                            class="bg-background/80 text-amber-600 dark:text-amber-400 rounded px-1 font-mono font-bold"
                        >
                            asdf1234
                        </code>).
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-1.5">
                <button
                    type="button"
                    :disabled="isGenerating"
                    @click="handleAutoFill"
                    class="bg-amber-600 hover:bg-amber-700 active:scale-95 inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold text-white shadow-xs transition-all disabled:opacity-50"
                >
                    <RefreshCw v-if="isGenerating" class="h-3.5 w-3.5 animate-spin" />
                    <Dices v-else class="h-3.5 w-3.5" />
                    <span>{{ isGenerating ? 'Mengisi...' : 'Isi Form Acak' }}</span>
                </button>

                <button
                    type="button"
                    @click="handleReset"
                    title="Kosongkan Form"
                    class="border-border hover:bg-muted text-muted-foreground hover:text-foreground inline-flex items-center rounded-lg border px-2 py-1.5 text-xs transition-colors"
                >
                    Reset
                </button>
            </div>
        </div>
    </div>

    <!-- Tombol Floating Cepat di Pojok Kanan Bawah (Tampil di semua step tanpa scroll) -->
    <button
        type="button"
        :disabled="isGenerating"
        @click="handleAutoFill"
        title="Isi Form Pendaftaran Otomatis (Password: asdf1234)"
        class="fixed right-4 bottom-5 z-50 inline-flex items-center gap-2 rounded-full border border-amber-400 bg-amber-500 px-3.5 py-2 text-xs font-bold text-slate-950 shadow-xl transition-all hover:scale-105 hover:bg-amber-400 active:scale-95 disabled:opacity-50 dark:border-amber-500 dark:bg-amber-500 dark:text-slate-950"
    >
        <RefreshCw v-if="isGenerating" class="h-4 w-4 animate-spin" />
        <Dices v-else class="h-4 w-4" />
        <span class="hidden sm:inline">Auto-Fill Dev</span>
        <span class="sm:hidden">Dev</span>
    </button>
</template>
