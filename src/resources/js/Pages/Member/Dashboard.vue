<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import MemberLayout from '@/Layouts/MemberLayout.vue';
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
    Users,
    Banknote,
    Network,
    Layers,
    PlusCircle,
} from 'lucide-vue-next';

const props = defineProps({
    user: { type: Object, required: true },
    dashboard: { type: Object, required: true },
    affiliate: { type: Object, required: true },
});

const INVESTMENTS_URL = '/member/investments';
const RECORD_URL = '/member/investments#kinerja-dan-riwayat-akun';
const AFFILIATES_URL = '/member/affiliates';
const COMMISSIONS_URL = '/member/commissions';

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

const nextStep = computed(() => {
    const d = props.dashboard;
    if (d.stage === 'no_account') {
        return {
            icon: Link2,
            tone: 'primary',
            title: 'Mulai perjalanan investasi copytrade Anda',
            desc: 'Hubungkan akun trading MT4 untuk mereplikasi transaksi Master Trader secara otomatis',
            cta: 'Daftarkan Akun Investasi',
            href: INVESTMENTS_URL,
        };
    }
    if (d.stage === 'pending_account') {
        return {
            icon: Clock,
            tone: 'amber',
            title: 'Akun trading menunggu verifikasi Administrator',
            desc: `Administrator sedang meninjau ${d.portfolio.pending_accounts_count} akun trading Anda. Saldo dan statistik portofolio akan tampil penuh setelah akun aktif.`,
            cta: 'Lihat Status Akun',
            href: INVESTMENTS_URL,
        };
    }
    if (!d.balance_recorded_this_month) {
        return {
            icon: CalendarCheck,
            tone: 'primary',
            title: 'Catat saldo akun bulan ini',
            desc: `${d.unrecorded_accounts_count} dari ${d.portfolio.active_accounts_count} akun aktif belum dicatat bulan ini. Pencatatan rutin membuat evaluasi profit dan progres BEP akurat.`,
            cta: 'Catat Saldo',
            href: RECORD_URL,
        };
    }
    return {
        icon: CheckCircle2,
        tone: 'emerald',
        title: 'Saldo bulan ini sudah tercatat lengkap',
        desc: 'Portofolio investasi Anda telah terbarui. Anda juga dapat terus memantau dan memperluas jaringan kemitraan.',
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
    (p.value?.total_all_time_profit ?? 0) >= 0
        ? 'text-emerald-700 dark:text-emerald-400'
        : 'text-red-700 dark:text-red-400'
);

const shortcuts = [
    { label: 'Jaringan Kemitraan', href: AFFILIATES_URL, icon: Network },
    { label: 'Klaim Komisi', href: COMMISSIONS_URL, icon: Banknote },
    { label: 'Portofolio Investasi', href: INVESTMENTS_URL, icon: Layers },
    { label: 'Kalkulator Proyeksi', href: '/member/education/calculator', icon: Calculator },
    { label: 'Pusat Bantuan', href: '/member/education/help', icon: LifeBuoy },
    { label: 'Tanya Jawab (FAQ)', href: '/member/education/faq', icon: HelpCircle },
];
</script>

<template>
    <MemberLayout
        :title="`Selamat Datang, ${user.name}!`"
        subtitle="Pantau kinerja portofolio investasi dan jaringan kemitraan Anda."
        :breadcrumbs="[{ label: 'Menu Utama' }, { label: 'Dashboard' }]"
    >
        <div class="space-y-6">
            <!-- 1. KODE REFERRAL MEMBER -->
            <section
                class="relative overflow-hidden rounded-2xl border border-primary-200 dark:border-primary-800/50 bg-gradient-to-br from-primary-50 via-white to-primary-50/30 dark:from-slate-900 dark:via-slate-900 dark:to-primary-950/20 p-5 sm:p-6 shadow-xs"
            >
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="space-y-1">

                        <h2 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white">
                            Kode Referral Anda
                        </h2>
                        <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 max-w-xl">
                            Bagikan kode referral Anda kepada rekan atau calon investor untuk memperluas jaringan dan memperoleh bagi hasil komisi.
                        </p>
                    </div>

                    <div class="flex items-center gap-2 self-start sm:self-center shrink-0">
                        <div
                            class="px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800/80 font-mono text-base sm:text-lg font-black tracking-wider text-primary-600 dark:text-primary-400 select-all shadow-xs"
                        >
                            {{ user.referral_code || '-' }}
                        </div>
                    </div>
                </div>
            </section>

            <!-- 2. LANGKAH BERIKUTNYA -->
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

            <!-- 3. METRIK PROGRAM KEMITRAAN (AFFILIASI) -->
            <section class="space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">
                            Ringkasan Jaringan Kemitraan
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Performa downline binaan dan status klaim komisi Anda.
                        </p>
                    </div>
                    <Link
                        :href="AFFILIATES_URL"
                        class="text-xs font-semibold text-primary-600 dark:text-primary-400 hover:underline inline-flex items-center gap-1"
                    >
                        <span>Lihat Semua Downline</span>
                        <ArrowRight class="w-3.5 h-3.5" />
                    </Link>
                </div>

                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Total Downline -->
                    <div class="rounded-2xl border border-border bg-card p-4 space-y-2">
                        <div class="flex items-center justify-between text-muted-foreground text-xs font-medium">
                            <span>Total Downline</span>
                            <Users class="w-4 h-4 text-primary" />
                        </div>
                        <div class="text-2xl font-black text-foreground">
                            {{ affiliate.network.total_downlines ?? 0 }}
                        </div>
                        <div class="text-[11px] text-muted-foreground">
                            Investor terhubung di jaringan
                        </div>
                    </div>

                    <!-- Downline Aktif -->
                    <div class="rounded-2xl border border-border bg-card p-4 space-y-2">
                        <div class="flex items-center justify-between text-muted-foreground text-xs font-medium">
                            <span>Downline Aktif</span>
                            <CheckCircle2 class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                        </div>
                        <div class="text-2xl font-black text-foreground">
                            {{ affiliate.network.active_downlines ?? 0 }}
                        </div>
                        <div class="text-[11px] text-muted-foreground">
                            Memiliki akun copytrade aktif
                        </div>
                    </div>

                    <!-- Siap Diklaim -->
                    <div class="rounded-2xl border border-border bg-card p-4 space-y-2">
                        <div class="flex items-center justify-between text-muted-foreground text-xs font-medium">
                            <span>Siap Diklaim</span>
                            <Coins class="w-4 h-4 text-amber-600 dark:text-amber-400" />
                        </div>
                        <div class="text-2xl font-black text-foreground">
                            {{ affiliate.commission.downline_siap_diklaim ?? 0 }}
                        </div>
                        <div class="text-[11px]">
                            <Link
                                :href="COMMISSIONS_URL"
                                class="font-semibold text-primary-600 dark:text-primary-400 hover:underline inline-flex items-center gap-1"
                            >
                                <span>Klaim Sekarang</span>
                                <ArrowRight class="w-3.5 h-3.5" />
                            </Link>
                        </div>
                    </div>

                    <!-- Total Komisi Dicairkan -->
                    <div class="rounded-2xl border border-border bg-card p-4 space-y-2">
                        <div class="flex items-center justify-between text-muted-foreground text-xs font-medium">
                            <span>Total Komisi Dicairkan</span>
                            <Banknote class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                        </div>
                        <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400">
                            {{ formatIDR(affiliate.commission.total_dicairkan_idr) }}
                        </div>
                        <div class="text-[11px] text-muted-foreground">
                            {{ affiliate.commission.klaim_disetujui ?? 0 }} permohonan disetujui
                        </div>
                    </div>
                </div>
            </section>

            <!-- 4. KARTU ANGKA PORTOFOLIO INVESTASI -->
            <section class="space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">
                            Kinerja Portofolio Trading
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Pemantauan akun trading copytrade pribadi Anda.
                        </p>
                    </div>
                    <Link
                        v-if="isRunning"
                        :href="INVESTMENTS_URL"
                        class="text-xs font-semibold text-primary-600 dark:text-primary-400 hover:underline inline-flex items-center gap-1"
                    >
                        <span>Kelola Portofolio</span>
                        <ArrowRight class="w-3.5 h-3.5" />
                    </Link>
                </div>

                <!-- Tampilan jika memiliki akun trading aktif (running) -->
                <div v-if="isRunning" class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    <div class="bg-card border-border rounded-2xl border p-4">
                        <div class="text-muted-foreground flex items-center justify-between text-xs font-medium">
                            <span>Modal Awal Riil</span>
                            <Wallet class="h-4 w-4 text-slate-400" />
                        </div>
                        <div class="text-foreground mt-2 text-xl font-black sm:text-2xl">
                            {{ formatUSD(p.total_modal_riil) }}
                        </div>
                        <div class="text-muted-foreground mt-1 text-[11px]">
                            Dari {{ p.active_accounts_count }} akun aktif
                        </div>
                    </div>

                    <div class="bg-card border-border rounded-2xl border p-4">
                        <div class="text-muted-foreground flex items-center justify-between text-xs font-medium">
                            <span>Saldo Terkini</span>
                            <TrendingUp class="h-4 w-4 text-slate-400" />
                        </div>
                        <div class="text-foreground mt-2 text-xl font-black sm:text-2xl">
                            {{ formatUSD(p.total_current_balance) }}
                        </div>
                        <div class="text-muted-foreground mt-1 text-[11px]">
                            {{ recorded ? 'Tercatat bulan ini' : 'Belum update bulan ini' }}
                        </div>
                    </div>

                    <div class="bg-card border-border rounded-2xl border p-4">
                        <div class="text-muted-foreground flex items-center justify-between text-xs font-medium">
                            <span>Total Profit Riil</span>
                            <Coins class="h-4 w-4 text-slate-400" />
                        </div>
                        <div :class="['mt-2 text-xl font-black sm:text-2xl', profitClass]">
                            {{ formatSigned(p.total_all_time_profit) }}
                        </div>
                        <div class="text-muted-foreground mt-1 text-[11px]">
                            {{ formatPct(p.total_all_time_profit_pct) }} dari modal awal
                        </div>
                    </div>

                    <div class="bg-card border-border rounded-2xl border p-4">
                        <div class="text-muted-foreground flex items-center justify-between text-xs font-medium">
                            <span>Progres BEP</span>
                            <Target class="h-4 w-4 text-slate-400" />
                        </div>
                        <div class="text-foreground mt-2 text-xl font-black sm:text-2xl">
                            {{ formatPct(p.bep_percentage) }}
                        </div>
                        <div class="text-muted-foreground mt-1 text-[11px]">
                            Target 100% modal kembali
                        </div>
                    </div>
                </div>

                <!-- Tampilan jika belum memiliki akun trading (no_account atau pending) -->
                <div
                    v-else
                    class="rounded-2xl border border-dashed border-slate-300 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-900/40 p-6 text-center space-y-3"
                >
                    <div class="w-12 h-12 rounded-full bg-primary/10 text-primary flex items-center justify-center mx-auto">
                        <Layers class="w-6 h-6" />
                    </div>
                    <div class="space-y-1 max-w-md mx-auto">
                        <h4 class="text-sm font-bold text-foreground">
                            {{ dashboard.stage === 'pending_account' ? 'Akun Trading Sedang Diverifikasi' : 'Belum Ada Akun Copytrade Aktif' }}
                        </h4>
                        <p class="text-xs text-muted-foreground">
                            {{ dashboard.stage === 'pending_account'
                                ? 'Pendaftaran akun trading Anda sedang dalam antrean verifikasi administrator.'
                                : 'Mulai alokasikan modal Anda ke dalam skema copytrade Master Trader untuk menikmati pertumbuhan portofolio secara autopilot.'
                            }}
                        </p>
                    </div>
                    <div class="pt-1">
                        <Link
                            :href="INVESTMENTS_URL"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-primary text-primary-foreground text-xs font-bold shadow-xs hover:bg-primary/90 transition-colors"
                        >
                            <PlusCircle class="w-4 h-4" />
                            <span>{{ dashboard.stage === 'pending_account' ? 'Periksa Status Verifikasi' : 'Daftarkan Akun Trading' }}</span>
                        </Link>
                    </div>
                </div>
            </section>

            <!-- 5. JALUR PINTAS CEPAT -->
            <section class="space-y-3">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">
                    Akses Cepat
                </h3>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                    <Link
                        v-for="s in shortcuts"
                        :key="s.href"
                        :href="s.href"
                        class="bg-card border-border hover:border-primary/50 group flex flex-col items-center gap-2 rounded-2xl border p-4 text-center transition-all shadow-xs"
                    >
                        <div
                            class="bg-primary/10 text-primary group-hover:bg-primary group-hover:text-primary-foreground rounded-xl p-2.5 transition-colors"
                        >
                            <component :is="s.icon" class="h-4 w-4" />
                        </div>
                        <span class="text-foreground text-xs font-semibold">
                            {{ s.label }}
                        </span>
                    </Link>
                </div>
            </section>
        </div>
    </MemberLayout>
</template>
