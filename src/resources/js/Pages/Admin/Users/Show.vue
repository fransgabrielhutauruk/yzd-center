<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AppConfirmDialog from '@/Components/Common/AppConfirmDialog.vue';
import AppModal from '@/Components/Common/AppModal.vue';
import AppButton from '@/Components/Common/AppButton.vue';
import {
    User as UserIcon,
    UserCheck,
    UserX,
    FileText,
    TrendingUp,
    ExternalLink,
    Eye,
    Phone,
    Mail,
    CreditCard,
    HeartHandshake,
    ArrowRight,
    ArrowLeftRight,
} from 'lucide-vue-next';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
});

// State Dialog Konfirmasi Nonaktifkan / Aktifkan & Modal Preview Dokumen
const isConfirmDialogOpen = ref(false);
const isToggling = ref(false);
const isKtpModalOpen = ref(false);
const isAgreementModalOpen = ref(false);

const isAgreementPdf = computed(() => {
    if (!props.user?.surat_perjanjian) return false;
    const sp = props.user.surat_perjanjian;
    if (sp.mime_type === 'application/pdf') return true;
    if (sp.is_pdf) return true;
    const name = sp.file_name || sp.preview_url || '';
    return name.toLowerCase().endsWith('.pdf');
});

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
</script>

<template>
    <Head title="Detail Pengguna" />
    <AdminLayout
        title="Detail Pengguna"
        subtitle="Informasi profil lengkap, kontak darurat, dan data lainnya."
        :breadcrumbs="[
            { label: 'Menu Utama' },
            { label: 'Manajemen Pengguna', href: route('admin.users.index') },
            { label: 'Detail Pengguna' },
        ]"
    >
        <template #header-actions>
            <div class="flex items-center gap-2">
                <AppButton
                    type="back"
                    :href="route('admin.users.index')"
                    label="Kembali"
                    size="sm"
                />
                <AppButton
                    v-if="user.is_active"
                    type="custom"
                    variant="destructive"
                    size="sm"
                    :icon="UserX"
                    label="Nonaktifkan Akun"
                    @click="isConfirmDialogOpen = true"
                />
                <AppButton
                    v-else
                    type="custom"
                    variant="default"
                    size="sm"
                    :icon="UserCheck"
                    label="Aktifkan Kembali Akun"
                    class="bg-emerald-600 hover:bg-emerald-700 text-white"
                    @click="isConfirmDialogOpen = true"
                />
            </div>
        </template>

        <div class="space-y-6">
            <!-- Profile Summary Banner Card -->
            <div class="border-border bg-card rounded-2xl border p-6 shadow-xs">
                <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                    <div class="flex items-start gap-4 sm:items-center">
                        <!-- Avatar / Initial -->
                        <div
                            class="bg-primary-100 text-primary-700 dark:bg-primary-950 dark:text-primary-300 border-primary-200 dark:border-primary-800 flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl border text-2xl font-black shadow-xs"
                        >
                            {{ user.name ? user.name.charAt(0).toUpperCase() : 'U' }}
                        </div>

                        <!-- User Info -->
                        <div class="space-y-1.5">
                            <div class="flex flex-wrap items-center gap-2.5">
                                <h1
                                    class="text-foreground text-xl font-bold tracking-tight sm:text-2xl"
                                >
                                    {{ user.name }}
                                </h1>

                                <!-- Derived Labels Badges -->
                                <div class="flex flex-wrap gap-1.5">
                                    <span
                                        v-for="lbl in (user.derived_labels || [user.role === 'administrator' ? 'Administrator' : 'Member'])"
                                        :key="lbl"
                                        :class="[
                                            'inline-flex items-center rounded-md px-2.5 py-0.5 text-xs font-semibold',
                                            lbl === 'Affiliator' ? 'border border-indigo-200 bg-indigo-50 text-indigo-700 dark:border-indigo-800 dark:bg-indigo-950/50 dark:text-indigo-300' :
                                            lbl === 'Investor' ? 'border border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-800 dark:bg-sky-950/50 dark:text-sky-300' :
                                            'border border-slate-200 bg-slate-50 text-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300'
                                        ]"
                                    >
                                        {{ lbl }}
                                    </span>
                                </div>

                                <!-- Status Akses Badge -->
                                <span
                                    v-if="user.is_active"
                                    class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300"
                                >
                                    <span
                                        class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500"
                                    ></span>
                                    Akun Aktif
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1.5 rounded-full border border-rose-200 bg-rose-50 px-2.5 py-0.5 text-xs font-semibold text-rose-700 dark:border-rose-800 dark:bg-rose-950/50 dark:text-rose-300"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                    Dinonaktifkan
                                </span>
                            </div>

                            <div
                                class="text-muted-foreground flex flex-wrap items-center gap-x-4 gap-y-1 text-xs"
                            >
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

                    <!-- Meta Tags (Hanya Waktu Bergabung) -->
                    <div
                        class="border-border flex flex-wrap gap-2 border-t pt-4 text-xs md:border-t-0 md:pt-0"
                    >
                        <div class="bg-muted/50 border-border/50 rounded-lg border px-3.5 py-2">
                            <p class="text-muted-foreground text-[10px]">Bergabung Sejak</p>
                            <p class="text-foreground mt-0.5 font-semibold">
                                {{ user.created_at_date }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2 Kolom Layout Desktop: Kiri (Data Pribadi & Ahli Waris), Kanan (Investasi / Copytrade) -->
            <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-2">
                <!-- Kolom Kiri: Data Pribadi & Ahli Waris -->
                <div class="space-y-6">
                    <!-- Card 1: Data Diri Pribadi -->
                    <div class="border-border bg-card rounded-2xl border p-5 shadow-xs sm:p-6">
                        <div class="border-border/60 mb-4 flex items-center gap-2 border-b pb-3">
                            <UserIcon class="text-primary-600 dark:text-primary-400 h-4 w-4" />
                            <h2 class="text-foreground text-sm font-bold">Data Diri Pribadi</h2>
                        </div>

                        <dl class="grid grid-cols-1 gap-x-4 gap-y-4 text-xs sm:grid-cols-2">
                            <div>
                                <dt class="text-muted-foreground">Nama Lengkap</dt>
                                <dd class="text-foreground mt-1 font-semibold">{{ user.name }}</dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">NIK</dt>
                                <dd class="text-foreground mt-1 font-semibold">{{ user.nik }}</dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">Tempat, Tanggal Lahir</dt>
                                <dd class="text-foreground mt-1 font-semibold">
                                    {{ user.tempat_lahir }}, {{ user.tanggal_lahir }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">Pekerjaan / Sumber Dana</dt>
                                <dd class="text-foreground mt-1 font-semibold">
                                    {{ user.pekerjaan || '-' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">Nomor Telepon / WhatsApp</dt>
                                <dd class="text-foreground mt-1 font-semibold">{{ user.phone }}</dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">Alamat Email</dt>
                                <dd class="text-foreground mt-1 font-semibold">{{ user.email }}</dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="text-muted-foreground">Alamat</dt>
                                <dd class="text-foreground mt-1 leading-relaxed font-medium">
                                    {{ user.alamat }}
                                </dd>
                            </div>
                            <div class="border-border/50 border-t pt-2 sm:col-span-2">
                                <dt class="text-muted-foreground mb-1.5">
                                    <span>Dokumen KTP</span>
                                </dt>
                                <dd>
                                    <div v-if="user.ktp?.preview_url">
                                        <!-- Pratinjau Gambar KTP Interaktif -->
                                        <div
                                            v-if="user.ktp.mime_type !== 'application/pdf'"
                                            class="group border-border bg-muted/20 hover:border-primary-500/50 relative inline-block cursor-pointer overflow-hidden rounded-xl border p-1 shadow-2xs transition-all hover:shadow-md"
                                            role="button"
                                            tabindex="0"
                                            title="Klik untuk melihat KTP secara penuh"
                                            @click="isKtpModalOpen = true"
                                            @keydown.enter="isKtpModalOpen = true"
                                        >
                                            <img
                                                :src="user.ktp.preview_url"
                                                :alt="`KTP ${user.name}`"
                                                class="h-28 w-44 rounded-lg object-cover transition-transform duration-200 group-hover:scale-[1.02]"
                                                loading="lazy"
                                            />
                                            <!-- Overlay Hover dengan Icon Eye -->
                                            <div
                                                class="absolute inset-1 flex items-center justify-center rounded-lg bg-black/45 opacity-0 backdrop-blur-[1px] transition-opacity group-hover:opacity-100"
                                            >
                                                <span
                                                    class="inline-flex items-center gap-1.5 rounded-md bg-white/95 px-2.5 py-1 text-[11px] font-semibold text-slate-900 shadow-sm dark:bg-slate-900/95 dark:text-slate-100"
                                                >
                                                    <Eye class="h-3.5 w-3.5" />
                                                    Lihat
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Fallback jika berkas PDF -->
                                        <div v-else class="flex items-center gap-3">
                                            <button
                                                type="button"
                                                class="border-border bg-card text-foreground hover:bg-muted inline-flex cursor-pointer items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-semibold"
                                                @click="isKtpModalOpen = true"
                                            >
                                                <FileText class="text-primary-600 h-3.5 w-3.5" />
                                                <span>Lihat Berkas PDF</span>
                                            </button>
                                            <a
                                                :href="user.ktp.preview_url"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="text-primary-600 inline-flex items-center gap-1 text-xs font-semibold hover:underline"
                                            >
                                                <span>Buka Tab Baru</span>
                                                <ExternalLink class="h-3 w-3" />
                                            </a>
                                        </div>
                                    </div>
                                    <span v-else class="text-muted-foreground text-xs italic"
                                        >Belum diunggah</span
                                    >
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Card 2: Data Ahli Waris -->
                    <div class="border-border bg-card rounded-2xl border p-5 shadow-xs sm:p-6">
                        <div class="border-border/60 mb-4 flex items-center gap-2 border-b pb-3">
                            <HeartHandshake
                                class="text-primary-600 dark:text-primary-400 h-4 w-4"
                            />
                            <h2 class="text-foreground text-sm font-bold">
                                Data Ahli Waris (Kontak Darurat)
                            </h2>
                        </div>

                        <div
                            v-if="user.ahli_waris"
                            class="grid grid-cols-1 gap-x-4 gap-y-4 text-xs sm:grid-cols-2"
                        >
                            <div>
                                <dt class="text-muted-foreground">Nama Ahli Waris</dt>
                                <dd class="text-foreground mt-1 font-semibold">
                                    {{ user.ahli_waris.nama }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">Hubungan</dt>
                                <dd class="text-foreground mt-1 font-semibold">
                                    {{ user.ahli_waris.hubungan_keluarga }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">Telepon / WhatsApp</dt>
                                <dd class="text-foreground mt-1 font-semibold">
                                    {{ user.ahli_waris.no_hp }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">Alamat Ahli Waris</dt>
                                <dd class="text-foreground mt-1 font-medium">
                                    {{ user.ahli_waris.alamat }}
                                </dd>
                            </div>
                        </div>
                        <div
                            v-else
                            class="border-border text-muted-foreground rounded-xl border border-dashed p-6 text-center text-xs"
                        >
                            Data ahli waris belum dilengkapi oleh pengguna ini.
                        </div>
                    </div>
                </div>

                <!-- Kolom Kanan: Investasi / Copytrade -->
                <div class="space-y-6">
                    <!-- Card 3: Investasi / Copytrade -->
                    <div class="border-border bg-card rounded-2xl border p-5 shadow-xs sm:p-6">
                        <div
                            class="border-border/60 mb-5 flex flex-col gap-2 border-b pb-3 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div class="flex items-center gap-2">
                                <TrendingUp
                                    class="text-primary-600 dark:text-primary-400 h-4 w-4"
                                />
                                <h2 class="text-foreground text-sm font-bold">
                                    Investasi / Copytrade
                                </h2>
                                <span class="text-muted-foreground font-mono text-xs">
                                    ({{ user.copytrades.length }} Akun Aktif)
                                </span>
                            </div>
                            <Link
                                :href="
                                    route('admin.copytrade.index', {
                                        tab: 'aktif',
                                        akt_username: user.username,
                                    })
                                "
                                class="text-primary-600 hover:text-primary-700 dark:text-primary-400 inline-flex items-center gap-1 text-xs font-semibold hover:underline"
                            >
                                <span>Lihat Pemantauan Aktif</span>
                                <ArrowRight class="h-3.5 w-3.5" />
                            </Link>
                        </div>

                        <div class="space-y-5">
                            <!-- Daftar Compact Cards Horizontal per Akun -->
                            <div v-if="user.copytrades.length > 0" class="space-y-2.5">
                                <div
                                    v-for="akun in user.copytrades"
                                    :key="akun.id"
                                    class="border-border/70 bg-card hover:bg-muted/30 rounded-xl border p-3.5 transition-colors"
                                >
                                    <div
                                        class="grid grid-cols-2 items-center gap-3 text-xs sm:grid-cols-4 lg:grid-cols-2 xl:grid-cols-4"
                                    >
                                        <!-- 1. Mulai Investasi -->
                                        <div>
                                            <span class="text-muted-foreground block text-[11px]"
                                                >Mulai Investasi</span
                                            >
                                            <span class="text-foreground font-medium">{{
                                                akun.tanggal_mulai
                                            }}</span>
                                        </div>
                                        <!-- 2. Akun -->
                                        <div>
                                            <span class="text-muted-foreground block text-[11px]"
                                                >Akun</span
                                            >
                                            <span
                                                class="text-foreground block truncate font-mono font-bold"
                                            >
                                                {{ akun.broker }} - #{{ akun.nomor_akun_trading }}
                                            </span>
                                        </div>
                                        <!-- 3. Tier -->
                                        <div>
                                            <span class="text-muted-foreground block text-[11px]"
                                                >Tier</span
                                            >
                                            <span
                                                class="bg-muted text-foreground border-border inline-flex items-center rounded-md border px-2 py-0.5 text-xs font-semibold"
                                            >
                                                {{
                                                    akun.tier
                                                        ? akun.tier.replace('_', ' ').toUpperCase()
                                                        : '-'
                                                }}
                                            </span>
                                        </div>
                                        <!-- 4. Modal Awal -->
                                        <div>
                                            <span class="text-muted-foreground block text-[11px]"
                                                >Modal Awal</span
                                            >
                                            <span
                                                class="font-mono font-bold text-emerald-600 dark:text-emerald-400"
                                            >
                                                {{ akun.modal_riil_formatted }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div
                                v-else
                                class="border-border text-muted-foreground rounded-xl border border-dashed p-5 text-center text-xs"
                            >
                                Pengguna ini belum memiliki akun investasi / copytrade aktif.
                            </div>

                            <!-- Bagian Surat Perjanjian & Rekening Pencairan -->
                            <div class="border-border/60 space-y-3.5 border-t pt-5">
                                <!-- Card Berkas Surat Perjanjian -->
                                <div class="border-border/70 bg-card rounded-xl border p-4">
                                    <div class="mb-3 flex items-center gap-2">
                                        <FileText
                                            class="text-primary-600 dark:text-primary-400 h-3.5 w-3.5"
                                        />
                                        <h4 class="text-foreground text-xs font-bold">
                                            Surat Perjanjian
                                        </h4>
                                    </div>
                                    <div v-if="user.surat_perjanjian" class="space-y-2">
                                        <div>
                                            <button
                                                type="button"
                                                class="border-primary-600/30 bg-primary-50 text-primary-700 hover:bg-primary-100 dark:bg-primary-950 dark:text-primary-300 dark:hover:bg-primary-900 inline-flex cursor-pointer items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-semibold shadow-2xs transition-colors"
                                                @click="isAgreementModalOpen = true"
                                            >
                                                <span>Lihat Surat Perjanjian</span>
                                            </button>
                                        </div>
                                        <p
                                            v-if="user.surat_perjanjian.verified_at"
                                            class="text-muted-foreground text-[11px]"
                                        >
                                            Diverifikasi: {{ user.surat_perjanjian.verified_at }}
                                        </p>
                                    </div>
                                    <div v-else class="text-muted-foreground text-xs">
                                        Belum ada berkas surat perjanjian yang diunggah.
                                    </div>
                                </div>

                                <!-- Card Rekening Bank Pencairan -->
                                <div class="border-border/70 bg-card rounded-xl border p-4">
                                    <div class="mb-3 flex items-center gap-2">
                                        <CreditCard
                                            class="text-primary-600 dark:text-primary-400 h-3.5 w-3.5"
                                        />
                                        <h4 class="text-foreground text-xs font-bold">
                                            Rekening Bank Pencairan
                                        </h4>
                                    </div>
                                    <div
                                        v-if="user.rekening_bank"
                                        class="grid grid-cols-1 gap-3 text-xs sm:grid-cols-3"
                                    >
                                        <div>
                                            <span class="text-muted-foreground block text-[11px]"
                                                >Nama Bank</span
                                            >
                                            <strong class="text-foreground font-semibold">{{
                                                user.rekening_bank.nama_bank || '-'
                                            }}</strong>
                                        </div>
                                        <div>
                                            <span class="text-muted-foreground block text-[11px]"
                                                >Nomor Rekening</span
                                            >
                                            <strong
                                                class="text-foreground font-mono font-semibold"
                                                >{{
                                                    user.rekening_bank.nomor_rekening || '-'
                                                }}</strong
                                            >
                                        </div>
                                        <div>
                                            <span class="text-muted-foreground block text-[11px]"
                                                >Atas Nama Rekening</span
                                            >
                                            <strong class="text-foreground font-semibold">{{
                                                user.rekening_bank.atas_nama_rekening || '-'
                                            }}</strong>
                                        </div>
                                    </div>
                                    <div v-else class="text-muted-foreground text-xs">
                                        Data rekening bank belum dilengkapi.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dialog Konfirmasi Status Akses Akun -->
        <AppConfirmDialog
            :show="isConfirmDialogOpen"
            :title="
                user.is_active ? 'Nonaktifkan Akun Pengguna?' : 'Aktifkan Kembali Akun Pengguna?'
            "
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

        <!-- Modal Pratinjau KTP Penuh -->
        <AppModal
            :show="isKtpModalOpen"
            :title="`Dokumen KTP - ${user.name}`"
            max-width="3xl"
            close-on-backdrop
            @close="isKtpModalOpen = false"
        >
            <div class="space-y-4">
                <div
                    class="text-muted-foreground border-border/60 flex items-center justify-between border-b pb-3 text-xs"
                >
                    <span v-if="user.ktp?.file_size">
                        Ukuran Berkas: {{ Math.round(user.ktp.file_size / 1024) }} KB
                    </span>
                    <span v-else></span>
                    <a
                        v-if="user.ktp?.preview_url"
                        :href="user.ktp.preview_url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="text-primary-600 dark:text-primary-400 inline-flex items-center gap-1 font-semibold hover:underline"
                    >
                        <span>Buka Tab Baru</span>
                        <ExternalLink class="h-3.5 w-3.5" />
                    </a>
                </div>

                <div
                    class="border-border bg-muted/20 relative flex max-h-[75vh] min-h-[300px] items-center justify-center overflow-hidden rounded-xl border"
                >
                    <iframe
                        v-if="user.ktp?.mime_type === 'application/pdf'"
                        :src="user.ktp.preview_url"
                        class="h-[65vh] w-full rounded-xl"
                    ></iframe>
                    <div
                        v-else
                        class="flex h-full w-full items-center justify-center overflow-auto p-2"
                    >
                        <img
                            :src="user.ktp.preview_url"
                            :alt="`KTP ${user.name}`"
                            class="max-h-[70vh] w-auto max-w-full rounded-lg object-contain shadow-md"
                        />
                    </div>
                </div>
            </div>
        </AppModal>

        <!-- Modal Pratinjau Surat Perjanjian -->
        <AppModal
            :show="isAgreementModalOpen"
            :title="`Surat Perjanjian - ${user.name}`"
            max-width="3xl"
            close-on-backdrop
            @close="isAgreementModalOpen = false"
        >
            <div class="space-y-4">
                <div
                    class="text-muted-foreground border-border/60 flex items-center justify-between border-b pb-3 text-xs"
                >
                    <span v-if="user.surat_perjanjian?.verified_at">
                        Diverifikasi: {{ user.surat_perjanjian.verified_at }}
                    </span>
                    <span v-else>Berkas Surat Perjanjian</span>
                    <a
                        v-if="user.surat_perjanjian?.preview_url"
                        :href="user.surat_perjanjian.preview_url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="text-primary-600 dark:text-primary-400 inline-flex items-center gap-1 font-semibold hover:underline"
                    >
                        <span>Buka Tab Baru</span>
                        <ExternalLink class="h-3.5 w-3.5" />
                    </a>
                </div>

                <div
                    class="border-border bg-muted/20 relative flex max-h-[75vh] min-h-[300px] items-center justify-center overflow-hidden rounded-xl border"
                >
                    <iframe
                        v-if="isAgreementPdf"
                        :src="user.surat_perjanjian?.preview_url"
                        class="h-[65vh] w-full rounded-xl"
                    ></iframe>
                    <div
                        v-else
                        class="flex h-full w-full items-center justify-center overflow-auto p-2"
                    >
                        <img
                            :src="user.surat_perjanjian?.preview_url"
                            :alt="`Surat Perjanjian ${user.name}`"
                            class="max-h-[70vh] w-auto max-w-full rounded-lg object-contain shadow-md"
                        />
                    </div>
                </div>
            </div>
        </AppModal>
    </AdminLayout>
</template>
