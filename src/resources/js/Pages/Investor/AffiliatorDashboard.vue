<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import InvestorLayout from '@/Layouts/InvestorLayout.vue';
import { Button } from '@/Components/ui/button';
import {
    Wallet,
    TrendingUp,
    Target,
    CalendarCheck,
    CheckCircle2,
    Clock,
    Link2,
    ArrowRight,
    Calculator,
    Coins,
    LifeBuoy,
    HelpCircle,
    Copy,
    Check,
    Users,
    Banknote,
    Network,
    Layers,
    Share2,
} from 'lucide-vue-next';
import { toast } from 'vue-sonner';

const props = defineProps({
    user: { type: Object, required: true },
    dashboard: { type: Object, required: true },
    affiliate: { type: Object, required: true },
});

const copied = ref(false);

const INVESTMENTS_URL = '/investor/investments';
const RECORD_URL = '/investor/investments#kinerja-dan-riwayat-akun';
const AFFILIATES_URL = '/investor/affiliates';
const COMMISSIONS_URL = '/investor/commissions';

const formatUSD = (val) =>
    new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(val || 0);

const formatIDR = (val) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(val || 0);

const formatSigned = (val) => `${val >= 0 ? '+' : '-'}${formatUSD(Math.abs(val))}`;
const formatPct = (val) =>
    `${Number(val || 0).toLocaleString('id-ID', { maximumFractionDigits: 1 })}%`;

const TIER_LABEL = {
    tier_1: 'Tier 1 (Cent)',
    tier_2: 'Tier 2 (Standard)',
    tier_3: 'Tier 3 (Direct VPS)',
};

const isRunning = computed(() => props.dashboard.stage === 'running');
const p = computed(() => props.dashboard.portfolio);
const recorded = computed(() => props.dashboard.balance_recorded_this_month);

const copyReferralCode = async () => {
    if (!props.user.referral_code) return;
    try {
        await navigator.clipboard.writeText(props.user.referral_code);
        copied.value = true;
        toast.success('Kode referral resmi berhasil disalin ke clipboard!', {
            description: props.user.referral_code,
        });
        setTimeout(() => {
            copied.value = false;
        }, 2000);
    } catch {
        toast.error('Gagal menyalin kode referral.');
    }
};

const nextStep = computed(() => {
    const d = props.dashboard;
    if (d.stage === 'no_account') {
        return {
            icon: Link2,
            tone: 'primary',
            title: 'Daftarkan akun trading pribadi Anda',
            desc: 'Sebagai mitra affiliator, Anda juga dapat menghubungkan akun trading pribadi untuk memantau copy trade dan portofolio.',
            cta: 'Daftarkan Akun Investasi',
            href: INVESTMENTS_URL,
        };
    }
    if (d.stage === 'pending_account') {
        return {
            icon: Clock,
            tone: 'amber',
            title: 'Akun trading menunggu verifikasi',
            desc: `Administrator sedang meninjau ${d.portfolio.pending_accounts_count} akun trading Anda. Saldo dan statistik tampil setelah akun aktif.`,
            cta: 'Lihat Status Akun',
            href: INVESTMENTS_URL,
        };
    }
    if (!d.balance_recorded_this_month) {
        return {
            icon: CalendarCheck,
            tone: 'primary',
            title: 'Catat saldo bulan ini',
            desc: `${d.unrecorded_accounts_count} dari ${d.portfolio.active_accounts_count} akun aktif belum dicatat bulan ini. Pencatatan rutin membuat profit bulanan dan progress BEP akurat.`,
            cta: 'Catat Saldo',
            href: RECORD_URL,
        };
    }
    return {
        icon: CheckCircle2,
        tone: 'emerald',
        title: 'Saldo bulan ini sudah tercatat',
        desc: 'Tidak ada tindakan mendesak untuk akun investasi. Anda dapat fokus mengembangkan jaringan kemitraan.',
        cta: 'Lihat Kinerja',
        href: RECORD_URL,
    };
});

const TONE_CLASS = {
    primary: 'border-primary/40 bg-primary/5',
    amber: 'border-amber-500/40 bg-amber-500/10',
    emerald: 'border-emerald-500/40 bg-emerald-500/10',
};
const TONE_ICON = {
    primary: 'bg-primary/10 text-primary',
    amber: 'bg-amber-500/15 text-amber-800 dark:text-amber-300',
    emerald: 'bg-emerald-500/15 text-emerald-800 dark:text-emerald-300',
};

const profitClass = computed(() =>
    p.value.total_all_time_profit >= 0
        ? 'text-emerald-700 dark:text-emerald-400'
        : 'text-red-700 dark:text-red-400'
);

const shortcuts = [
    { label: 'Jaringan Kemitraan', href: AFFILIATES_URL, icon: Network },
    { label: 'Klaim Komisi', href: COMMISSIONS_URL, icon: Banknote },
    { label: 'Portofolio Investasi', href: INVESTMENTS_URL, icon: Layers },
    { label: 'Kalkulator Proyeksi', href: '/investor/education/calculator', icon: Calculator },
    { label: 'Pusat Bantuan', href: '/investor/education/help', icon: LifeBuoy },
    { label: 'FAQ', href: '/investor/education/faq', icon: HelpCircle },
];
</script>

<template>
    <InvestorLayout
        :title="`Selamat Datang, ${user.name}!`"
        subtitle="Pusat kendali jaringan kemitraan afiliasi dan portofolio investasi Anda."
        :breadcrumbs="[{ label: 'Menu Utama' }, { label: 'Dasbor Mitra Affiliator' }]"
    >
        <div class="space-y-6">
            <!-- 1. KARTU KODE REFERRAL RESMI -->
            <section
                class="rounded-2xl border-2 border-accent-500/40 bg-card p-5 shadow-xs sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4"
            >
                <div class="space-y-1">
                    <span class="text-xs font-black uppercase tracking-wider text-accent-700 dark:text-accent-400 flex items-center gap-1.5">
                        <Share2 class="w-3.5 h-3.5" />
                        Kode Referral Resmi Anda
                    </span>
                    <p class="text-xs text-muted-foreground leading-relaxed max-w-xl">
                        Bagikan kode referral ini kepada calon investor Anda. Investor yang mendaftar akan otomatis terhubung ke jaringan kemitraan Anda.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <div class="px-4 py-2 bg-muted rounded-xl font-mono text-base font-bold text-foreground tracking-wider border border-border">
                        {{ user.referral_code || '-' }}
                    </div>

                    <Button
                        type="button"
                        @click="copyReferralCode"
                        class="bg-accent-600 hover:bg-accent-700 text-white text-xs font-bold py-2 px-3.5 rounded-xl shadow-xs flex items-center gap-1.5 transition-colors cursor-pointer min-h-[38px]"
                    >
                        <Check v-if="copied" class="w-4 h-4" />
                        <Copy v-else class="w-4 h-4" />
                        <span>{{ copied ? 'Tersalin!' : 'Salin Kode' }}</span>
                    </Button>
                </div>
            </section>

            <!-- 2. KARTU STATISTIK AFILIASI & KOMISI -->
            <section class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <!-- Total Downline -->
                <div class="bg-card border-border rounded-2xl border p-4 shadow-xs sm:p-5">
                    <div class="text-muted-foreground flex items-center gap-1.5 text-xs font-bold">
                        <Users class="h-4 w-4" /> Total Downline
                    </div>
                    <p class="text-foreground mt-1 font-mono text-lg font-black sm:text-2xl">
                        {{ affiliate.network.total_downlines }}
                    </p>
                    <p class="text-muted-foreground mt-0.5 text-[11px] font-medium">
                        <Link :href="AFFILIATES_URL" class="text-primary hover:underline font-bold">
                            Lihat Jaringan &rarr;
                        </Link>
                    </p>
                </div>

                <!-- Total Komisi Dicairkan (Hanya Rupiah) -->
                <div class="bg-card border-border rounded-2xl border p-4 shadow-xs sm:p-5">
                    <div class="text-muted-foreground flex items-center gap-1.5 text-xs font-bold">
                        <Banknote class="h-4 w-4" /> Total Komisi Dicairkan
                    </div>
                    <p class="text-emerald-700 dark:text-emerald-400 mt-1 font-mono text-lg font-black sm:text-2xl">
                        {{ formatIDR(affiliate.commission.total_dicairkan_idr ?? affiliate.commission.total_dicairkan) }}
                    </p>
                    <p class="text-muted-foreground mt-0.5 text-[11px] font-medium">
                        {{ affiliate.commission.klaim_disetujui }} permohonan disetujui
                    </p>
                </div>

                <!-- Downline Siap Diklaim -->
                <div class="bg-card border-border rounded-2xl border p-4 shadow-xs sm:p-5">
                    <div class="text-muted-foreground flex items-center gap-1.5 text-xs font-bold">
                        <Coins class="h-4 w-4" /> Siap Diklaim Komisi
                    </div>
                    <p class="text-accent-700 dark:text-accent-400 mt-1 font-mono text-lg font-black sm:text-2xl">
                        {{ affiliate.commission.downline_siap_diklaim }}
                    </p>
                    <p class="text-muted-foreground mt-0.5 text-[11px] font-medium">
                        <Link :href="COMMISSIONS_URL" class="text-primary hover:underline font-bold">
                            Klaim Sekarang &rarr;
                        </Link>
                    </p>
                </div>

                <!-- Menunggu Verifikasi -->
                <div class="bg-card border-border rounded-2xl border p-4 shadow-xs sm:p-5">
                    <div class="text-muted-foreground flex items-center gap-1.5 text-xs font-bold">
                        <Clock class="h-4 w-4" /> Menunggu Verifikasi
                    </div>
                    <p :class="['mt-1 font-mono text-lg font-black sm:text-2xl', affiliate.commission.menunggu_transfer > 0 ? 'text-sky-700 dark:text-sky-400' : 'text-foreground']">
                        {{ affiliate.commission.menunggu_transfer }}
                    </p>
                    <p class="text-muted-foreground mt-0.5 text-[11px] font-medium">
                        {{ affiliate.commission.menunggu_transfer > 0 ? 'Sedang diverifikasi admin' : 'Tidak ada antrean' }}
                    </p>
                </div>
            </section>

            <!-- 3. LANGKAH BERIKUTNYA (PORTOFOLIO PRIBADI) -->
            <section
                :class="['flex flex-col gap-4 rounded-2xl border-2 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6', TONE_CLASS[nextStep.tone]]"
            >
                <div class="flex items-start gap-3.5">
                    <div :class="['mt-0.5 shrink-0 rounded-xl p-2.5', TONE_ICON[nextStep.tone]]">
                        <component :is="nextStep.icon" class="h-5 w-5" />
                    </div>
                    <div class="space-y-1">
                        <h2 class="text-foreground text-base font-bold">{{ nextStep.title }}</h2>
                        <p class="text-muted-foreground text-xs leading-relaxed sm:text-sm">
                            {{ nextStep.desc }}
                        </p>
                    </div>
                </div>
                <Link
                    :href="nextStep.href"
                    class="bg-primary text-primary-foreground hover:bg-primary/90 inline-flex min-h-[40px] shrink-0 cursor-pointer items-center justify-center gap-1.5 rounded-xl px-4 py-2.5 text-xs font-bold shadow-xs transition-colors"
                >
                    {{ nextStep.cta }}
                    <ArrowRight class="h-3.5 w-3.5" />
                </Link>
            </section>

            <!-- 4. KARTU ANGKA INVESTASI PRIBADI (HANYA TAHAP RUNNING) -->
            <section v-if="isRunning" class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <!-- Ekuitas Pribadi -->
                <div class="bg-card border-border rounded-2xl border p-4 shadow-xs sm:p-5">
                    <div class="text-muted-foreground flex items-center gap-1.5 text-xs font-bold">
                        <Wallet class="h-4 w-4" /> Ekuitas Pribadi
                    </div>
                    <p class="text-foreground mt-1 font-mono text-lg font-black sm:text-2xl">
                        {{ formatUSD(p.total_current_balance) }}
                    </p>
                    <p class="text-muted-foreground mt-0.5 text-[11px] font-medium">
                        <template v-if="!p.has_balance_history">Masih modal awal</template>
                        <template v-else-if="dashboard.mom_change !== null">
                            <span :class="dashboard.mom_change >= 0 ? 'text-emerald-600 dark:text-emerald-400 font-semibold' : 'text-red-600 dark:text-red-400 font-semibold'">
                                {{ formatSigned(dashboard.mom_change) }}
                                <span v-if="dashboard.mom_change_pct !== null">({{ formatPct(dashboard.mom_change_pct) }})</span>
                            </span>
                            vs bulan lalu
                        </template>
                        <template v-else>Belum ada pembanding bulan lalu</template>
                    </p>
                </div>

                <!-- Profit Riil Pribadi -->
                <div class="bg-card border-border rounded-2xl border p-4 shadow-xs sm:p-5">
                    <div class="text-muted-foreground flex items-center gap-1.5 text-xs font-bold">
                        <TrendingUp class="h-4 w-4" /> Profit Riil Pribadi
                    </div>
                    <template v-if="p.has_balance_history">
                        <p :class="['mt-1 font-mono text-lg font-black sm:text-2xl', profitClass]">
                            {{ formatSigned(p.total_all_time_profit) }}
                        </p>
                        <p class="text-muted-foreground mt-0.5 text-[11px] font-medium">
                            {{ formatPct(p.total_all_time_growth_pct) }} dari modal awal {{ formatUSD(p.total_initial_capital) }}
                        </p>
                    </template>
                    <template v-else>
                        <p class="text-foreground mt-1 font-mono text-lg font-black sm:text-2xl">—</p>
                        <p class="text-muted-foreground mt-0.5 text-[11px] font-medium">Belum ada saldo tercatat</p>
                    </template>
                </div>

                <!-- Balik Modal (BEP) -->
                <div class="bg-card border-border rounded-2xl border p-4 shadow-xs sm:p-5">
                    <div class="text-muted-foreground flex items-center gap-1.5 text-xs font-bold">
                        <Target class="h-4 w-4" /> Balik Modal (BEP)
                    </div>
                    <p class="text-foreground mt-1 font-mono text-lg font-black sm:text-2xl">
                        {{ formatPct(p.bep_progress_pct) }}
                    </p>
                    <div class="bg-muted mt-2 h-2 w-full overflow-hidden rounded-full" role="progressbar" :aria-valuenow="p.bep_progress_pct" aria-valuemin="0" aria-valuemax="100">
                        <div class="bg-primary h-full rounded-full" :style="{ width: `${Math.min(100, p.bep_progress_pct)}%` }" />
                    </div>
                    <p class="text-muted-foreground mt-1.5 text-[11px] font-medium">
                        {{ p.bep_achieved ? 'BEP tercapai' : `Sisa ${formatUSD(p.bep_remaining)} menuju BEP` }}
                    </p>
                </div>

                <!-- Status Pencatatan Saldo -->
                <div class="bg-card border-border rounded-2xl border p-4 shadow-xs sm:p-5">
                    <div class="text-muted-foreground flex items-center gap-1.5 text-xs font-bold">
                        <CalendarCheck class="h-4 w-4" /> Pencatatan Bulan Ini
                    </div>
                    <p :class="['mt-1 text-lg font-black sm:text-2xl', recorded ? 'text-emerald-700 dark:text-emerald-400' : 'text-amber-700 dark:text-amber-400']">
                        {{ recorded ? 'Sudah' : 'Belum' }}
                    </p>
                    <p class="text-muted-foreground mt-0.5 text-[11px] font-medium">
                        {{ p.active_accounts_count - dashboard.unrecorded_accounts_count }} dari {{ p.active_accounts_count }} akun aktif tercatat
                    </p>
                </div>
            </section>

            <!-- 5. AKUN TRADING & PINTASAN CEPAT (50% - 50% DI DESKTOP) -->
            <div v-if="dashboard.accounts.length" class="grid grid-cols-1 items-start gap-6 lg:grid-cols-2">
                <!-- Kolom Kiri: Akun Trading Anda -->
                <section class="bg-card border-border rounded-2xl border p-5 shadow-xs sm:p-6">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <h3 class="text-foreground text-sm font-bold">Akun Trading Anda</h3>
                        <Link :href="INVESTMENTS_URL" class="text-primary inline-flex min-h-[36px] items-center gap-1 text-xs font-bold hover:underline">
                            Kelola <ArrowRight class="h-3 w-3" />
                        </Link>
                    </div>
                    <ul class="divide-border divide-y">
                        <li v-for="acc in dashboard.accounts" :key="acc.id" class="flex flex-wrap items-center justify-between gap-2 py-3">
                            <div class="min-w-0 space-y-0.5">
                                <p class="text-foreground truncate font-mono text-sm font-bold">{{ acc.nomor_akun_trading }}</p>
                                <p class="text-muted-foreground text-[11px] font-medium">{{ TIER_LABEL[acc.tier] || acc.tier }}</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <span
                                    v-if="acc.status === 'active'"
                                    class="rounded-full border border-emerald-500/30 bg-emerald-500/10 px-2 py-0.5 text-[11px] font-bold text-emerald-800 dark:text-emerald-300"
                                >
                                    Aktif
                                </span>
                                <div
                                    v-else
                                    class="inline-flex items-center gap-1 rounded-full border border-sky-500/30 bg-sky-500/10 px-2 py-0.5 text-[10px] font-semibold text-sky-800 dark:text-sky-300"
                                >
                                    <Clock class="h-3 w-3" />
                                    <span>Menunggu Verifikasi</span>
                                </div>
                                <div class="text-right">
                                    <p class="text-foreground font-mono text-sm font-bold">
                                        {{ formatUSD(acc.status === 'active' ? acc.current_balance : acc.modal_riil) }}
                                    </p>
                                    <p class="text-muted-foreground text-[10px] font-medium">
                                        {{ acc.status === 'active' ? 'Saldo terakhir' : 'Modal awal' }}
                                    </p>
                                </div>
                            </div>
                        </li>
                    </ul>
                </section>

                <!-- Kolom Kanan: Pintasan Cepat -->
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <Link
                        v-for="s in shortcuts"
                        :key="s.href"
                        :href="s.href"
                        class="bg-muted/40 hover:bg-muted/80 border-border text-foreground flex min-h-[48px] cursor-pointer items-center gap-2.5 rounded-xl border px-3.5 py-3 text-xs font-bold shadow-xs transition-colors"
                    >
                        <component :is="s.icon" class="text-primary h-4 w-4 shrink-0" />
                        <span class="truncate">{{ s.label }}</span>
                    </Link>
                </div>
            </div>

            <!-- Jika Belum Ada Akun (Pintasan Full Width) -->
            <section v-else class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                <Link
                    v-for="s in shortcuts"
                    :key="s.href"
                    :href="s.href"
                    class="bg-card border-border hover:bg-muted text-foreground flex min-h-[48px] cursor-pointer items-center gap-2.5 rounded-xl border px-3.5 py-3 text-xs font-bold shadow-xs transition-colors"
                >
                    <component :is="s.icon" class="text-primary h-4 w-4 shrink-0" />
                    {{ s.label }}
                </Link>
            </section>
        </div>
    </InvestorLayout>
</template>
