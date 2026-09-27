<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AppConfirmDialog from '@/Components/Common/AppConfirmDialog.vue';
import { Button } from '@/Components/ui/button';
import {
    ArrowLeft,
    User as UserIcon,
    Users,
    UserCheck,
    UserX,
    FileCheck,
    FileText,
    ShieldCheck,
    Clock,
    TrendingUp,
    ExternalLink,
    Copy,
    Check,
    Phone,
    Mail,
    MapPin,
    Briefcase,
    Calendar,
    Globe,
    CreditCard,
    AlertCircle,
    Building2,
    HeartHandshake,
} from 'lucide-vue-next';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
});

// State Dialog Konfirmasi Nonaktifkan / Aktifkan
const isConfirmDialogOpen = ref(false);
const isToggling = ref(false);
const copiedReferral = ref(false);

const handleConfirmToggle = () => {
    isToggling.value = true;
    router.post(
        route('admin.users.toggle_status', props.user.id),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isToggling.value = false;
                isConfirmDialogOpen.value = false;
            },
        }
    );
};

const copyReferralCode = () => {
    if (!props.user.referral_code) return;
    navigator.clipboard.writeText(props.user.referral_code);
    copiedReferral.value = true;
    setTimeout(() => {
        copiedReferral.value = false;
    }, 2000);
};
</script>

<template>
    <Head :title="`Detail Pengguna - ${user.name}`" />
    <AdminLayout
        :title="`Detail Pengguna: ${user.name}`"
        subtitle="Informasi profil lengkap, kontak darurat, data bank, dan histori keanggotaan pengguna."
        :breadcrumbs="[
            { label: 'Menu Utama' },
            { label: 'Pengguna', href: route('admin.users.index') },
            { label: 'Detail Pengguna' }
        ]"
    >
        <template #header-actions>
            <Button
                v-if="user.is_active"
                variant="destructive"
                size="sm"
                class="h-9 px-3 text-xs font-medium cursor-pointer"
                @click="isConfirmDialogOpen = true"
            >
                <UserX class="h-4 w-4 mr-1.5" />
                Nonaktifkan Akun
            </Button>
            <Button
                v-else
                variant="default"
                size="sm"
                class="h-9 px-3 text-xs font-medium bg-emerald-600 hover:bg-emerald-700 text-white cursor-pointer"
                @click="isConfirmDialogOpen = true"
            >
                <UserCheck class="h-4 w-4 mr-1.5" />
                Aktifkan Kembali Akun
            </Button>
        </template>

        <div class="space-y-6">

            <!-- Profile Summary Banner Card -->
            <div class="rounded-2xl border border-border bg-card p-6 shadow-xs">
                <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                    <div class="flex items-start gap-4 sm:items-center">
                        <!-- Avatar / Initial -->
                        <div
                            class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-primary-100 text-2xl font-black text-primary-700 dark:bg-primary-950 dark:text-primary-300 shadow-xs border border-primary-200 dark:border-primary-800"
                        >
                            {{ user.name ? user.name.charAt(0).toUpperCase() : 'U' }}
                        </div>

                        <!-- User Info -->
                        <div class="space-y-1.5">
                            <div class="flex flex-wrap items-center gap-2.5">
                                <h1 class="text-xl font-bold tracking-tight text-foreground sm:text-2xl">
                                    {{ user.name }}
                                </h1>

                                <!-- Role Badge -->
                                <span
                                    v-if="user.role === 'affiliator'"
                                    class="inline-flex items-center rounded-md bg-indigo-50 px-2.5 py-0.5 text-xs font-semibold text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800"
                                >
                                    Affiliator
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center rounded-md bg-sky-50 px-2.5 py-0.5 text-xs font-semibold text-sky-700 dark:bg-sky-950/50 dark:text-sky-300 border border-sky-200 dark:border-sky-800"
                                >
                                    Investor
                                </span>

                                <!-- Status Akses Badge -->
                                <span
                                    v-if="user.is_active"
                                    class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    Akun Aktif
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-2.5 py-0.5 text-xs font-semibold text-rose-700 dark:bg-rose-950/50 dark:text-rose-300 border border-rose-200 dark:border-rose-800"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                    Dinonaktifkan
                                </span>
                            </div>

                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted-foreground">
                                <span class="font-mono">@{{ user.username || '-' }}</span>
                                <span>&bull;</span>
                                <span class="flex items-center gap-1">
                                    <Mail class="h-3 w-3" />
                                    {{ user.email }}
                                </span>
                                <span>&bull;</span>
                                <span class="flex items-center gap-1">
                                    <Phone class="h-3 w-3" />
                                    {{ user.phone }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Meta Tags -->
                    <div class="flex flex-wrap gap-2 text-xs border-t border-border pt-4 md:border-t-0 md:pt-0">
                        <div class="rounded-lg bg-muted/50 px-3 py-2 border border-border/50">
                            <p class="text-[10px] text-muted-foreground">Status KYC</p>
                            <p class="font-semibold text-foreground flex items-center gap-1 mt-0.5">
                                <ShieldCheck v-if="user.status === 'active'" class="h-3.5 w-3.5 text-emerald-600" />
                                <Clock v-else class="h-3.5 w-3.5 text-amber-600" />
                                {{ user.status === 'active' ? 'Terverifikasi' : 'Pending Verifikasi' }}
                            </p>
                        </div>
                        <div class="rounded-lg bg-muted/50 px-3 py-2 border border-border/50">
                            <p class="text-[10px] text-muted-foreground">Bergabung</p>
                            <p class="font-semibold text-foreground mt-0.5">{{ user.created_at_date }}</p>
                        </div>
                        <div class="rounded-lg bg-muted/50 px-3 py-2 border border-border/50">
                            <p class="text-[10px] text-muted-foreground">IP Terakhir</p>
                            <p class="font-semibold font-mono text-foreground mt-0.5">{{ user.ip_address }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Kolom Kiri & Tengah: Data Diri, Ahli Waris, KTP (2 Kolom) -->
                <div class="space-y-6 lg:col-span-2">
                    <!-- Card 1: Data Diri Lengkap -->
                    <div class="rounded-2xl border border-border bg-card p-5 sm:p-6 shadow-xs">
                        <div class="flex items-center justify-between border-b border-border/60 pb-3 mb-4">
                            <div class="flex items-center gap-2">
                                <UserIcon class="h-4 w-4 text-primary-600 dark:text-primary-400" />
                                <h2 class="text-sm font-bold text-foreground">Data Diri Pribadi</h2>
                            </div>
                            <span class="text-xs text-muted-foreground font-mono">NIK: {{ user.nik }}</span>
                        </div>

                        <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2 text-xs">
                            <div>
                                <dt class="text-muted-foreground">Nama Lengkap</dt>
                                <dd class="mt-1 font-semibold text-foreground">{{ user.name }}</dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">Nomor Induk Kependudukan (NIK)</dt>
                                <dd class="mt-1 font-mono font-semibold text-foreground">{{ user.nik }}</dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">Tempat, Tanggal Lahir</dt>
                                <dd class="mt-1 font-semibold text-foreground">
                                    {{ user.tempat_lahir }}, {{ user.tanggal_lahir }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">Pekerjaan</dt>
                                <dd class="mt-1 font-semibold text-foreground">{{ user.pekerjaan }}</dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">Nomor Telepon / WhatsApp</dt>
                                <dd class="mt-1 font-semibold text-foreground">{{ user.phone }}</dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">Alamat Email</dt>
                                <dd class="mt-1 font-semibold text-foreground">{{ user.email }}</dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="text-muted-foreground">Alamat Domisili KTP</dt>
                                <dd class="mt-1 font-medium text-foreground leading-relaxed">{{ user.alamat }}</dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">Persetujuan Syarat & Ketentuan</dt>
                                <dd class="mt-1 font-medium text-foreground">{{ user.terms_agreed_at }}</dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">Waktu Terdaftar</dt>
                                <dd class="mt-1 font-medium text-foreground">{{ user.created_at }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Card 2: Dokumen KTP & Verifikasi -->
                    <div class="rounded-2xl border border-border bg-card p-5 sm:p-6 shadow-xs">
                        <div class="flex items-center justify-between border-b border-border/60 pb-3 mb-4">
                            <div class="flex items-center gap-2">
                                <FileCheck class="h-4 w-4 text-primary-600 dark:text-primary-400" />
                                <h2 class="text-sm font-bold text-foreground">Dokumen Identitas (KTP)</h2>
                            </div>
                            <span
                                v-if="user.ktp?.status === 'approved'"
                                class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800"
                            >
                                <Check class="h-3 w-3" />
                                Disetujui
                            </span>
                            <span
                                v-else-if="user.ktp?.status === 'revision_needed'"
                                class="inline-flex items-center gap-1 rounded-md bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700 dark:bg-amber-950/50 dark:text-amber-300 border border-amber-200 dark:border-amber-800"
                            >
                                <AlertCircle class="h-3 w-3" />
                                Perlu Revisi
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-1 rounded-md bg-sky-50 px-2 py-0.5 text-xs font-semibold text-sky-700 dark:bg-sky-950/50 dark:text-sky-300 border border-sky-200 dark:border-sky-800"
                            >
                                <Clock class="h-3 w-3" />
                                Menunggu Verifikasi
                            </span>
                        </div>

                        <div v-if="user.ktp" class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 rounded-xl border border-border/70 bg-muted/30 p-4">
                            <div class="space-y-1">
                                <p class="text-xs font-semibold text-foreground flex items-center gap-1.5">
                                    <FileText class="h-4 w-4 text-primary-600" />
                                    {{ user.ktp.file_name }}
                                </p>
                                <p class="text-[11px] text-muted-foreground">
                                    Ukuran: {{ Math.round((user.ktp.file_size || 0) / 1024) }} KB &bull; Diverifikasi: {{ user.ktp.verified_at || 'Belum diverifikasi' }}
                                </p>
                                <p v-if="user.ktp.catatan_admin" class="text-xs text-amber-700 dark:text-amber-300 mt-2 bg-amber-50 dark:bg-amber-950/50 p-2 rounded-lg border border-amber-200 dark:border-amber-800">
                                    <strong>Catatan Admin:</strong> {{ user.ktp.catatan_admin }}
                                </p>
                            </div>

                            <a
                                :href="user.ktp.preview_url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-primary-600/30 bg-primary-50 px-3 py-1.5 text-xs font-semibold text-primary-700 hover:bg-primary-100 dark:bg-primary-950 dark:text-primary-300 dark:hover:bg-primary-900 transition-colors shadow-2xs shrink-0 cursor-pointer"
                            >
                                <ExternalLink class="h-3.5 w-3.5" />
                                Buka Berkas KTP
                            </a>
                        </div>
                        <div v-else class="rounded-xl border border-dashed border-border p-6 text-center text-xs text-muted-foreground">
                            Belum ada dokumen KTP yang diunggah oleh pengguna ini.
                        </div>
                    </div>

                    <!-- Card 3: Data Ahli Waris -->
                    <div class="rounded-2xl border border-border bg-card p-5 sm:p-6 shadow-xs">
                        <div class="flex items-center gap-2 border-b border-border/60 pb-3 mb-4">
                            <HeartHandshake class="h-4 w-4 text-primary-600 dark:text-primary-400" />
                            <h2 class="text-sm font-bold text-foreground">Data Ahli Waris (Kontak Darurat)</h2>
                        </div>

                        <div v-if="user.ahli_waris" class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2 text-xs">
                            <div>
                                <dt class="text-muted-foreground">Nama Ahli Waris</dt>
                                <dd class="mt-1 font-semibold text-foreground">{{ user.ahli_waris.nama }}</dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">Hubungan Keluarga</dt>
                                <dd class="mt-1 font-semibold text-foreground">{{ user.ahli_waris.hubungan_keluarga }}</dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">Nomor Telepon / Kontak</dt>
                                <dd class="mt-1 font-semibold text-foreground">{{ user.ahli_waris.no_hp }}</dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">Alamat Ahli Waris</dt>
                                <dd class="mt-1 font-medium text-foreground">{{ user.ahli_waris.alamat }}</dd>
                            </div>
                        </div>
                        <div v-else class="rounded-xl border border-dashed border-border p-6 text-center text-xs text-muted-foreground">
                            Data ahli waris belum dilengkapi oleh pengguna ini.
                        </div>
                    </div>

                    <!-- Card 4: Daftar Akun Copytrade -->
                    <div class="rounded-2xl border border-border bg-card p-5 sm:p-6 shadow-xs">
                        <div class="flex items-center justify-between border-b border-border/60 pb-3 mb-4">
                            <div class="flex items-center gap-2">
                                <TrendingUp class="h-4 w-4 text-primary-600 dark:text-primary-400" />
                                <h2 class="text-sm font-bold text-foreground">Akun Copytrade & Broker</h2>
                            </div>
                            <span class="font-mono text-xs text-muted-foreground">
                                Total: {{ user.copytrades.length }} Akun
                            </span>
                        </div>

                        <div v-if="user.copytrades.length > 0" class="space-y-3">
                            <div
                                v-for="akun in user.copytrades"
                                :key="akun.id"
                                class="rounded-xl border border-border/80 bg-muted/20 p-4 transition-all hover:bg-muted/40"
                            >
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-foreground text-sm font-mono">
                                                {{ akun.broker }} - #{{ akun.nomor_akun_trading }}
                                            </span>
                                            <span
                                                v-if="akun.status === 'active'"
                                                class="rounded-md bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300 border border-emerald-200"
                                            >
                                                Aktif
                                            </span>
                                            <span
                                                v-else
                                                class="rounded-md bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700 dark:bg-amber-950/50 dark:text-amber-300 border border-amber-200"
                                            >
                                                {{ akun.status }}
                                            </span>
                                        </div>
                                        <div class="flex flex-wrap gap-x-3 gap-y-1 text-xs text-muted-foreground">
                                            <span>Server: <strong class="text-foreground">{{ akun.server_broker || '-' }}</strong></span>
                                            <span>&bull;</span>
                                            <span>Tipe: <strong class="text-foreground">{{ akun.tipe_akun }}</strong></span>
                                            <span>&bull;</span>
                                            <span>Modal: <strong class="text-foreground font-mono">{{ akun.modal_riil_formatted }}</strong></span>
                                        </div>
                                        <div class="text-[11px] text-muted-foreground pt-1">
                                            Rekening: {{ akun.nama_bank }} - {{ akun.nomor_rekening }} (a.n {{ akun.atas_nama_rekening }})
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2 shrink-0">
                                        <a
                                            v-if="akun.url_mql5"
                                            :href="akun.url_mql5"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="inline-flex items-center gap-1 rounded-lg border border-border bg-card px-2.5 py-1 text-xs font-medium text-foreground hover:bg-muted"
                                        >
                                            <Globe class="h-3.5 w-3.5 text-primary-600" />
                                            MQL5
                                        </a>
                                        <a
                                            v-if="akun.agreement_url"
                                            :href="akun.agreement_url"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="inline-flex items-center gap-1 rounded-lg border border-border bg-card px-2.5 py-1 text-xs font-medium text-foreground hover:bg-muted"
                                        >
                                            <FileText class="h-3.5 w-3.5 text-primary-600" />
                                            Surat Perjanjian
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="rounded-xl border border-dashed border-border p-6 text-center text-xs text-muted-foreground">
                            Pengguna ini belum mendaftarkan akun copytrade.
                        </div>
                    </div>
                </div>

                <!-- Kolom Kanan: Jaringan Afiliasi & Referral (1 Kolom) -->
                <div class="space-y-6">
                    <!-- Jika User adalah Investor: Tampilkan Info Upline -->
                    <div v-if="user.role === 'investor'" class="rounded-2xl border border-border bg-card p-5 sm:p-6 shadow-xs">
                        <div class="flex items-center gap-2 border-b border-border/60 pb-3 mb-4">
                            <UserCheck class="h-4 w-4 text-primary-600 dark:text-primary-400" />
                            <h2 class="text-sm font-bold text-foreground">Mitra Affiliator (Upline)</h2>
                        </div>

                        <div v-if="user.upline" class="space-y-3">
                            <div class="rounded-xl border border-border/70 bg-muted/20 p-4">
                                <p class="font-bold text-foreground text-sm">{{ user.upline.name }}</p>
                                <p class="text-xs text-muted-foreground font-mono">@{{ user.upline.username }}</p>

                                <div class="mt-3 space-y-1.5 border-t border-border/50 pt-2.5 text-xs text-muted-foreground">
                                    <div class="flex items-center justify-between">
                                        <span>Kode Referral:</span>
                                        <span class="font-mono font-semibold text-foreground">{{ user.upline.referral_code }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span>Email:</span>
                                        <span class="text-foreground">{{ user.upline.email }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span>Telepon:</span>
                                        <span class="text-foreground">{{ user.upline.phone }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span>Terhubung Sejak:</span>
                                        <span class="text-foreground">{{ user.upline.joined_at }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="rounded-xl border border-dashed border-border p-6 text-center text-xs text-muted-foreground">
                            Investor ini mendaftar langsung (organik tanpa upline affiliator).
                        </div>
                    </div>

                    <!-- Jika User adalah Affiliator: Tampilkan Info Jaringan Downline -->
                    <div v-if="user.role === 'affiliator'" class="rounded-2xl border border-border bg-card p-5 sm:p-6 shadow-xs">
                        <div class="flex items-center justify-between border-b border-border/60 pb-3 mb-4">
                            <div class="flex items-center gap-2">
                                <Users class="h-4 w-4 text-primary-600 dark:text-primary-400" />
                                <h2 class="text-sm font-bold text-foreground">Jaringan Kemitraan</h2>
                            </div>
                            <span class="font-mono text-xs text-muted-foreground">
                                {{ user.downlines.length }} Downline
                            </span>
                        </div>

                        <!-- Kode Referral Pribadi -->
                        <div class="rounded-xl border border-border/70 bg-muted/20 p-3.5 mb-4">
                            <p class="text-[11px] text-muted-foreground mb-1">Kode Referral Affiliator:</p>
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-mono text-sm font-bold text-primary-600 dark:text-primary-400">
                                    {{ user.referral_code }}
                                </span>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    class="h-7 px-2 text-xs cursor-pointer"
                                    @click="copyReferralCode"
                                >
                                    <Check v-if="copiedReferral" class="h-3.5 w-3.5 text-emerald-600 mr-1" />
                                    <Copy v-else class="h-3.5 w-3.5 mr-1" />
                                    {{ copiedReferral ? 'Disalin' : 'Salin' }}
                                </Button>
                            </div>
                        </div>

                        <!-- Daftar Anggota Downline -->
                        <div v-if="user.downlines.length > 0" class="space-y-2.5 max-h-[480px] overflow-y-auto pr-1">
                            <div
                                v-for="downline in user.downlines"
                                :key="downline.id"
                                class="rounded-xl border border-border/70 bg-card p-3 shadow-2xs text-xs space-y-1"
                            >
                                <div class="flex items-center justify-between">
                                    <p class="font-semibold text-foreground truncate">{{ downline.name }}</p>
                                    <span
                                        v-if="downline.status === 'active'"
                                        class="rounded px-1.5 py-0.2 text-[10px] font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300"
                                    >
                                        KYC Aktif
                                    </span>
                                    <span
                                        v-else
                                        class="rounded px-1.5 py-0.2 text-[10px] font-semibold bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300"
                                    >
                                        Pending
                                    </span>
                                </div>
                                <p class="text-[11px] text-muted-foreground truncate">{{ downline.email }} &bull; {{ downline.phone }}</p>
                                <div class="flex items-center justify-between text-[10px] text-muted-foreground pt-1 border-t border-border/40">
                                    <span>Bergabung: {{ downline.joined_at_short }}</span>
                                    <Link
                                        :href="route('admin.users.show', downline.id)"
                                        class="text-primary-600 hover:underline font-semibold"
                                    >
                                        Lihat Profil &rarr;
                                    </Link>
                                </div>
                            </div>
                        </div>
                        <div v-else class="rounded-xl border border-dashed border-border p-6 text-center text-xs text-muted-foreground">
                            Belum ada anggota downline yang mendaftar melalui kode referral ini.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dialog Konfirmasi Status Akses Akun -->
        <AppConfirmDialog
            :show="isConfirmDialogOpen"
            :title="user.is_active ? 'Nonaktifkan Akun Pengguna?' : 'Aktifkan Kembali Akun Pengguna?'"
            :message="
                user.is_active
                    ? `Apakah Anda yakin ingin menonaktifkan akun '${user.name}' (@${user.username})? Pengguna ini tidak akan dapat login atau mengakses sistem lagi, dan seluruh sesi aktifnya akan segera diputus.`
                    : `Apakah Anda yakin ingin mengaktifkan kembali akun '${user.name}' (@${user.username})? Pengguna ini akan dapat login dan mengakses sistem seperti semula.`
            "
            :confirm-text="user.is_active ? 'Ya, Nonaktifkan Akun' : 'Ya, Aktifkan Akun'"
            cancel-text="Batal"
            :type="user.is_active ? 'danger' : 'warning'"
            :loading="isToggling"
            @confirm="handleConfirmToggle"
            @cancel="isConfirmDialogOpen = false"
        />
    </AdminLayout>
</template>
