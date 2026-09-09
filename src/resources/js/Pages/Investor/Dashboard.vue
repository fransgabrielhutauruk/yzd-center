<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import InvestorLayout from '@/Layouts/InvestorLayout.vue';
import { Button } from '@/Components/ui/button';
import {
    TrendingUp,
    ShieldCheck,
    Wallet,
    Link2,
    Copy,
    Check,
    ArrowRight,
    BookOpen,
    HelpCircle,
    UserCheck,
    Clock
} from 'lucide-vue-next';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
});

const copied = ref(false);

const copyReferralCode = async () => {
    if (!props.user.referral_code) return;
    try {
        await navigator.clipboard.writeText(props.user.referral_code);
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2000);
    } catch (err) {
        console.error('Gagal menyalin kode referral:', err);
    }
};
</script>

<template>
    <InvestorLayout title="Dasbor Portofolio">
        <div class="space-y-6">
            <!-- Welcome Header Card -->
            <div class="rounded-2xl bg-gradient-to-r from-primary-700 via-primary-800 to-slate-900 text-white p-6 sm:p-8 shadow-md border border-primary-600/30">
                <div class="max-w-2xl space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/40">
                            <ShieldCheck class="w-3.5 h-3.5" />
                            Akun Terverifikasi
                        </span>
                        <span
                            v-if="user.role === 'affiliator'"
                            class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-accent-500 text-white shadow-xs"
                        >
                            <UserCheck class="w-3.5 h-3.5" />
                            Affiliator
                        </span>
                    </div>

                    <h2 class="text-xl sm:text-2xl font-bold tracking-tight">
                        Selamat Datang, {{ user.name }}!
                    </h2>
                    <p class="text-xs sm:text-sm text-white/80 leading-relaxed">
                        Akun Anda aktif dalam sistem YZD Trading Management. Pantau performa portofolio dan kelola strategi copytrade Anda secara transparan.
                    </p>
                </div>
            </div>

            <!-- Affiliator Referral Card (Shown only if role is affiliator) -->
            <div
                v-if="user.role === 'affiliator' && user.referral_code"
                class="rounded-2xl bg-card border-2 border-accent-500/40 p-5 sm:p-6 shadow-xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4"
            >
                <div class="space-y-1">
                    <span class="text-xs font-bold uppercase tracking-wider text-accent-600 dark:text-accent-400">
                        Kode Referral Resmi Anda
                    </span>
                    <p class="text-xs text-muted-foreground">
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
                        class="bg-accent-500 hover:bg-accent-600 text-white text-xs font-semibold py-2 px-3.5 rounded-xl shadow-sm flex items-center gap-1.5 transition-colors"
                    >
                        <Check v-if="copied" class="w-4 h-4" />
                        <Copy v-else class="w-4 h-4" />
                        <span>{{ copied ? 'Tersalin!' : 'Salin Kode' }}</span>
                    </Button>
                </div>
            </div>

            <!-- Metric Summary Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <!-- Saldo Portofolio -->
                <div class="bg-card rounded-2xl border border-border p-5 shadow-xs flex items-center justify-between">
                    <div class="space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                            Estimasi Ekuitas
                        </p>
                        <p class="text-2xl font-extrabold text-foreground">
                            Rp 0
                        </p>
                        <p class="text-[11px] text-muted-foreground">
                            Akun riil terhubung
                        </p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-primary-500/10 text-primary-600 dark:text-primary-400 flex items-center justify-center">
                        <Wallet class="w-6 h-6" />
                    </div>
                </div>

                <!-- Status Copytrade -->
                <div class="bg-card rounded-2xl border border-border p-5 shadow-xs flex items-center justify-between">
                    <div class="space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                            Status Copytrade
                        </p>
                        <p class="text-base font-bold text-muted-foreground">
                            Belum Terhubung
                        </p>
                        <Link
                            href="/investor/copytrade"
                            class="inline-flex items-center gap-1 text-[11px] font-semibold text-primary-600 dark:text-primary-400 hover:underline"
                        >
                            Hubungkan Sekarang <ArrowRight class="w-3 h-3" />
                        </Link>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                        <Link2 class="w-6 h-6" />
                    </div>
                </div>

                <!-- Total PnL -->
                <div class="bg-card rounded-2xl border border-border p-5 shadow-xs flex items-center justify-between">
                    <div class="space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                            Total Keuntungan (PnL)
                        </p>
                        <p class="text-2xl font-extrabold text-emerald-600 dark:text-emerald-400">
                            +0.00%
                        </p>
                        <p class="text-[11px] text-muted-foreground">
                            Berdasarkan histori transaksi
                        </p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                        <TrendingUp class="w-6 h-6" />
                    </div>
                </div>
            </div>

            <!-- Quick Action Resources -->
            <div class="space-y-3">
                <h3 class="text-sm font-bold uppercase tracking-wider text-foreground">
                    Pusat Informasi & Layanan
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <Link
                        href="/edukasi/kamus"
                        class="p-4 rounded-xl bg-card border border-border hover:border-primary-500 hover:shadow-md transition-all flex items-start gap-3 group"
                    >
                        <div class="p-2.5 rounded-lg bg-primary-500/10 text-primary-600 group-hover:bg-primary-600 group-hover:text-white transition-colors">
                            <BookOpen class="w-5 h-5" />
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-foreground group-hover:text-primary-600 transition-colors">
                                Kamus Istilah Forex
                            </h4>
                            <p class="text-xs text-muted-foreground mt-0.5">
                                Pelajari istilah-istilah trading seperti Drawdown, High Watermark, Pip, dan Spread.
                            </p>
                        </div>
                    </Link>

                    <Link
                        href="/edukasi/faq"
                        class="p-4 rounded-xl bg-card border border-border hover:border-primary-500 hover:shadow-md transition-all flex items-start gap-3 group"
                    >
                        <div class="p-2.5 rounded-lg bg-accent-500/10 text-accent-600 group-hover:bg-accent-600 group-hover:text-white transition-colors">
                            <HelpCircle class="w-5 h-5" />
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-foreground group-hover:text-accent-600 transition-colors">
                                Pusat Bantuan (FAQ)
                            </h4>
                            <p class="text-xs text-muted-foreground mt-0.5">
                                Temukan jawaban atas pertanyaan seputar skema bagi hasil, penarikan dana, dan sistem trading.
                            </p>
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </InvestorLayout>
</template>
