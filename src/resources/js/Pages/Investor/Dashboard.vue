<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import InvestorLayout from '@/Layouts/InvestorLayout.vue';
import InvestorBepQuotaCard from '@/Components/Investor/InvestorBepQuotaCard.vue';
import { Button } from '@/Components/ui/button';
import {
    TrendingUp,
    ShieldCheck,
    AlertCircle,
    Wallet,
    Link2,
    Copy,
    Check,
    ArrowRight,
    UserCheck,
    FileCheck,
    LineChart,
} from 'lucide-vue-next';
import { toast } from 'vue-sonner';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    bep_summary: {
        type: Object,
        default: () => ({}),
    },
});

const copied = ref(false);

const formatUSD = (val) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(val || 0);
};

const formatIDR = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(val || 0);
};

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
    } catch (err) {
        toast.error('Gagal menyalin kode referral.');
    }
};
</script>

<template>
    <InvestorLayout title="Dasbor Portofolio">
        <div class="space-y-6">
            <!-- Welcome Header Card -->
            <div class="rounded-2xl bg-gradient-to-r from-primary-700 via-primary-800 to-slate-900 text-white p-6 sm:p-8 shadow-md border border-primary-600/40">
                <div class="max-w-2xl space-y-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            v-if="user.is_active"
                            class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/40"
                        >
                            <ShieldCheck class="w-3.5 h-3.5" />
                            Akun Terverifikasi
                        </span>
                        <span
                            v-else
                            class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-500/20 text-amber-300 border border-amber-500/40"
                        >
                            <AlertCircle class="w-3.5 h-3.5" />
                            Akun Belum Terverifikasi
                        </span>

                        <span
                            v-if="user.role === 'affiliator'"
                            class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-accent-500 text-white shadow-xs"
                        >
                            <UserCheck class="w-3.5 h-3.5" />
                            Affiliator
                        </span>
                    </div>

                    <h2 class="text-xl sm:text-2xl font-black tracking-tight text-white">
                        Selamat Datang, {{ user.name }}!
                    </h2>
                    <p class="text-xs sm:text-sm text-white/90 leading-relaxed font-normal">
                        Kelola portofolio copy trade, tinjau legalitas KYC, dan pantau performa investasi Anda.
                    </p>
                </div>
            </div>

            <!-- KYC Status Reminder Banner if account is not active -->
            <div
                v-if="!user.is_active"
                class="rounded-xl border border-amber-500/30 bg-amber-500/10 p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-amber-900 dark:text-amber-200"
            >
                <div class="flex items-start gap-3">
                    <AlertCircle class="w-5 h-5 text-amber-700 dark:text-amber-400 shrink-0 mt-0.5" />
                    <div>
                        <h4 class="text-sm font-bold text-amber-950 dark:text-amber-100">
                            Akun Belum Aktif
                        </h4>
                        <p class="text-xs text-amber-800 dark:text-amber-300 mt-0.5 leading-relaxed">
                            Verifikasi Dokumen KYC Anda sedang ditinjau oleh Administrator.
                        </p>
                    </div>
                </div>

                <Link
                    href="/investor/kyc"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2 text-xs font-bold rounded-lg bg-amber-600 hover:bg-amber-700 text-white transition-colors shrink-0 shadow-xs cursor-pointer min-h-[36px]"
                >
                    <FileCheck class="w-4 h-4" />
                    <span>Buka Status KYC</span>
                </Link>
            </div>

            <!-- Affiliator Referral Card (Shown only if role is affiliator) -->
            <div
                v-if="user.role === 'affiliator' && user.referral_code"
                class="rounded-2xl bg-card border-2 border-accent-500/40 p-5 sm:p-6 shadow-xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4"
            >
                <div class="space-y-1">
                    <span class="text-xs font-black uppercase tracking-wider text-accent-700 dark:text-accent-400">
                        Kode Referral Resmi Anda
                    </span>
                    <p class="text-xs text-muted-foreground leading-relaxed">
                        Bagikan kode referral ini kepada calon downline Anda untuk mendapatkan komisi afiliasi berjenjang.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <div class="px-4 py-2 bg-muted rounded-xl font-mono text-base font-bold text-foreground tracking-wider border border-border">
                        {{ user.referral_code }}
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
            </div>

            <!-- WIDGET PROGRESS BEP (TAMPILAN KUOTA INTERNET) -->
            <InvestorBepQuotaCard
                v-if="bep_summary && Object.keys(bep_summary).length > 0"
                :bep-data="bep_summary"
                :title="bep_summary.has_active_copytrade ? 'Progress Balik Modal (BEP) Portofolio Anda' : 'Simulasi Kuota Balik Modal (BEP)'"
                :subtitle="bep_summary.has_active_copytrade
                    ? `Dihitung dari rata-rata profit ${bep_summary.monthly_profit_rate}%/bln sejak Anda mulai aktif pada ${bep_summary.join_date_formatted}, termasuk akumulasi yang sudah di-WD.`
                    : 'Dihitung dari rata-rata profit bulanan konservatif 5% sejak awal join hingga 100% modal kembali (mencakup yang sudah di-WD).'"
            />

            <!-- Metric Summary Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <!-- Saldo Portofolio -->
                <div class="bg-card rounded-2xl border border-border p-5 shadow-xs flex items-center justify-between">
                    <div class="space-y-1">
                        <p class="text-xs font-bold uppercase tracking-wider text-muted-foreground">
                            Estimasi Ekuitas
                        </p>
                        <p class="text-2xl font-black text-foreground font-mono">
                            {{ formatIDR(bep_summary?.current_equity_idr || 0) }}
                        </p>
                        <p class="text-[11px] text-muted-foreground font-medium">
                            {{ bep_summary?.has_active_copytrade ? `${formatUSD(bep_summary.current_equity)} (${bep_summary.active_accounts_count} akun riil)` : 'Belum ada akun trading riil' }}
                        </p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-primary-500/10 text-primary-600 dark:text-primary-400 flex items-center justify-center">
                        <Wallet class="w-6 h-6" />
                    </div>
                </div>

                <!-- Status Copytrade -->
                <div class="bg-card rounded-2xl border border-border p-5 shadow-xs flex items-center justify-between">
                    <div class="space-y-1">
                        <p class="text-xs font-bold uppercase tracking-wider text-muted-foreground">
                            Status Copy Trade
                        </p>
                        <p class="text-base font-bold text-foreground">
                            {{ bep_summary?.has_active_copytrade ? `Aktif (${bep_summary.active_accounts_count} Akun)` : (user.is_active ? 'Siap Hubungkan Akun' : 'Menunggu Validasi KYC') }}
                        </p>
                        <Link
                            :href="bep_summary?.has_active_copytrade ? '/investor/copytrade' : (user.is_active ? '/investor/copytrade' : '/investor/kyc')"
                            class="inline-flex items-center gap-1 text-[11px] font-bold text-primary hover:underline cursor-pointer"
                        >
                            {{ bep_summary?.has_active_copytrade ? 'Kelola Akun Copytrade' : (user.is_active ? 'Daftarkan Akun Trading' : 'Buka Status KYC') }} <ArrowRight class="w-3 h-3" />
                        </Link>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-sky-500/10 text-sky-600 dark:text-sky-400 flex items-center justify-center">
                        <Link2 class="w-6 h-6" />
                    </div>
                </div>

                <!-- Total PnL / BEP Progress -->
                <div class="bg-card rounded-2xl border border-border p-5 shadow-xs flex items-center justify-between">
                    <div class="space-y-1">
                        <p class="text-xs font-bold uppercase tracking-wider text-muted-foreground">
                            Progress Balik Modal (BEP)
                        </p>
                        <p class="text-2xl font-black text-emerald-700 dark:text-emerald-400 font-mono">
                            {{ (bep_summary?.bep_progress_pct || 0).toFixed(1) }}%
                        </p>
                        <p class="text-[11px] text-muted-foreground font-medium">
                            {{ bep_summary?.is_bep_achieved ? '100% BEP Tercapai (Murni Profit)' : `Est. ${bep_summary?.remaining_months_to_bep || 0} bln lagi menuju 100% BEP` }}
                        </p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 flex items-center justify-center">
                        <TrendingUp class="w-6 h-6" />
                    </div>
                </div>
            </div>

            <!-- Action Card: Input Saldo Tanggal 1 & Riwayat Kinerja -->
            <div class="rounded-2xl border border-border bg-card p-5 sm:p-6 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <LineChart class="w-4 h-4 text-primary" />
                        <h4 class="text-sm font-bold text-foreground">
                            Pencatatan Saldo MT4 Setiap Tanggal 1
                        </h4>
                    </div>
                    <p class="text-xs text-muted-foreground leading-relaxed">
                        Catat saldo akun trading Anda secara berkala untuk menghitung keuntungan bulanan otomatis (MoM) serta analisis kinerja 3–6 bulan dan 1–4 tahun terakhir.
                    </p>
                </div>

                <Link
                    href="/investor/copytrade/balance"
                    class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl bg-primary hover:bg-primary/90 text-primary-foreground text-xs font-bold transition-colors shadow-xs cursor-pointer shrink-0 min-h-[38px]"
                >
                    <span>Input & Rekap Saldo</span>
                    <ArrowRight class="w-3.5 h-3.5" />
                </Link>
            </div>
        </div>
    </InvestorLayout>
</template>
