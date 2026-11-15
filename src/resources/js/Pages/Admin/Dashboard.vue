<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    Users,
    UserCheck,
    Link2,
    ArrowRight,
    BadgeCheck,
    ArrowLeftRight,
    Network,
    Banknote,
    PlusCircle,
    CheckCircle2,
    Clock,
    LifeBuoy,
    Receipt,
    AlertTriangle,
    FileWarning,
} from 'lucide-vue-next';

const props = defineProps({
    pending_actions: {
        type: Object,
        default: () => ({
            kyc: 0,
            copytrade: 0,
            referral_claims: 0,
            commission_claims: 0,
            tickets: 0,
            master_fee: 0,
            total: 0,
        }),
    },
    master_fee_monitoring: {
        type: Object,
        default: () => ({
            unpaid_reports_count: 0,
            unpaid_only_count: 0,
            rejected_reports_count: 0,
            unpaid_investors_count: 0,
            unpaid_total_usd: 0,
            unpaid_total_idr: 0,
            missing_accounts_count: 0,
            missing_investors_count: 0,
        }),
    },
    stats: {
        type: Object,
        default: () => ({
            member_count: 0,
            active_member_count: 0,
            investor_count: 0,
            active_investor_count: 0,
            affiliator_count: 0,
            active_affiliator_count: 0,
            active_copytrade_count: 0,
            active_invites: 0,
        }),
    },
});

const pendingList = computed(() => {
    const list = [];
    const pa = props.pending_actions || {};

    if (pa.kyc > 0) {
        list.push({
            id: 'kyc',
            title: 'Verifikasi Pendaftaran',
            desc: 'Berkas identitas & data calon member baru menunggu peninjauan legalitas.',
            count: pa.kyc,
            href: '/admin/registrations',
            icon: BadgeCheck,
            unit: 'Pendaftar',
        });
    }

    if (pa.copytrade > 0) {
        list.push({
            id: 'copytrade',
            title: 'Verifikasi Akun Copytrade',
            desc: 'Pengajuan akun copytrade (investasi).',
            count: pa.copytrade,
            href: '/admin/copytrade',
            icon: ArrowLeftRight,
            unit: 'Akun',
        });
    }

    if (pa.referral_claims > 0) {
        list.push({
            id: 'referral_claims',
            title: 'Klaim Susulan Referral',
            desc: 'Pengajuan klaim downline manual dari mitra affiliator menunggu validasi.',
            count: pa.referral_claims,
            href: '/admin/affiliates?tab=claims',
            icon: Network,
            unit: 'Permohonan',
        });
    }

    if (pa.commission_claims > 0) {
        list.push({
            id: 'commission_claims',
            title: 'Pencairan Komisi Afiliasi',
            desc: 'Tiket pencairan komisi 3 bulan affiliator menunggu konfirmasi transfer dana.',
            count: pa.commission_claims,
            href: '/admin/commissions',
            icon: Banknote,
            unit: 'Tiket Klaim',
        });
    }

    if (pa.tickets > 0) {
        list.push({
            id: 'tickets',
            title: 'Tiket Bantuan & Kendala',
            desc: 'Laporan kendala member menunggu tanggapan atau penanganan.',
            count: pa.tickets,
            href: '/admin/tickets?status=open',
            icon: LifeBuoy,
            unit: 'Tiket',
        });
    }

    if (pa.master_fee > 0) {
        list.push({
            id: 'master_fee',
            title: 'Bukti Master Fee Menunggu Verifikasi',
            desc: 'Bukti pembayaran Master Fee bulanan oleh member menunggu verifikasi admin.',
            count: pa.master_fee,
            href: '/admin/master-fee',
            icon: Receipt,
            unit: 'Laporan',
        });
    }

    return list;
});

const formatUSD = (val) => {
    const num = Number(val ?? 0);
    if (isNaN(num)) return '$0.00';
    return '$' + num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const formatRupiah = (val) => {
    const num = Math.round(Number(val ?? 0));
    if (isNaN(num)) return 'Rp 0';
    return 'Rp ' + num.toLocaleString('id-ID');
};

const monitoringTotal = computed(() => {
    const mfm = props.master_fee_monitoring || {};
    return Number(mfm.unpaid_reports_count || 0) + Number(mfm.missing_accounts_count || 0);
});

const monitoringCards = computed(() => {
    const mfm = props.master_fee_monitoring || {};
    const unpaidCount = Number(mfm.unpaid_reports_count || 0);
    const unpaidOnlyCount = Number(mfm.unpaid_only_count || 0);
    const rejectedCount = Number(mfm.rejected_reports_count || 0);
    const unpaidInvestors = Number(mfm.unpaid_investors_count || 0);
    const missingCount = Number(mfm.missing_accounts_count || 0);
    const missingInvestors = Number(mfm.missing_investors_count || 0);

    const unpaidStatusQuery = unpaidOnlyCount === 0 && rejectedCount > 0 ? 'ditolak' : 'belum_dibayar';
    const rejectedNote = rejectedCount > 0 ? ` (${rejectedCount} bukti ditolak)` : '';

    return [
        {
            id: 'unpaid_master_fee',
            title: 'Belum Kirim Bukti Fee',
            desc: 'Laporan profit > $0 yang belum dibayar atau bukti transfernya ditolak oleh admin.',
            count: unpaidCount,
            unit: 'Laporan',
            subInfo: unpaidCount > 0
                ? `dari ${unpaidInvestors} Investor • Tagihan: ${formatRupiah(mfm.unpaid_total_idr)} (${formatUSD(mfm.unpaid_total_usd)})${rejectedNote}`
                : 'Tidak ada tagihan Master Fee tertunggak.',
            href: `/admin/master-fee?tab=laporan&status=${unpaidStatusQuery}`,
            ctaLabel: 'Lihat Tagihan',
            icon: AlertTriangle,
        },
        {
            id: 'missing_master_fee_report',
            title: 'Belum Lapor Bulanan',
            desc: 'Akun copytrade aktif yang sudah memasuki jadwal periode laporan tetapi belum input laporan.',
            count: missingCount,
            unit: 'Akun',
            subInfo: missingCount > 0
                ? `dari ${missingInvestors} Investor • Periode laporan bulanan telah terbuka`
                : 'Seluruh akun aktif telah melapor sesuai jadwal.',
            href: '/admin/master-fee?tab=belum_lapor',
            ctaLabel: 'Lihat Akun',
            icon: FileWarning,
        },
    ];
});

const shortcuts = [
    {
        label: 'Buat Tautan Undangan',
        href: '/admin/invitations',
        icon: PlusCircle,
    },
    {
        label: 'Verifikasi Pendaftaran',
        href: '/admin/registrations',
        icon: BadgeCheck,
    },
    {
        label: 'Kelola Akun Copytrade',
        href: '/admin/copytrade',
        icon: ArrowLeftRight,
    },
    {
        label: 'Pencairan Komisi',
        href: '/admin/commissions',
        icon: Banknote,
    },
];
</script>

<template>
    <AdminLayout
        title="Dashboard Administrator"
        subtitle="Pusat kendali operasional trading dan manajemen ekosistem YZD Center."
        :breadcrumbs="[
            { label: 'Menu Utama' },
            { label: 'Dashboard' }
        ]"
    >
        <div class="space-y-6">

            <!-- 1. SEKSI ANTREAN BUTUH TINDAKAN (KONDISIONAL) -->
            <section class="space-y-3">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <h2 class="text-xs font-bold uppercase tracking-wider text-muted-foreground">
                            Antrean Butuh Tindakan
                        </h2>
                        <span
                            v-if="pending_actions.total > 0"
                            class="inline-flex items-center gap-1 rounded-full border border-sky-500/30 bg-sky-500/10 px-2 py-0.5 text-[10px] font-semibold text-sky-800 dark:text-sky-300"
                        >
                            <Clock class="h-3 w-3" />
                            <span>{{ pending_actions.total }} Menunggu Verifikasi</span>
                        </span>
                    </div>
                </div>

                <!-- A. Jika ada antrean pending: Tampilkan kartu antrean aktif -->
                <div
                    v-if="pendingList.length > 0"
                    class="grid grid-cols-1 md:grid-cols-2 gap-4"
                >
                    <div
                        v-for="item in pendingList"
                        :key="item.id"
                        class="bg-card border-border hover:border-sky-500/40 rounded-2xl border p-5 shadow-xs transition-colors flex flex-col justify-between gap-4"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <h3 class="text-sm font-bold text-foreground">
                                        {{ item.title }}
                                    </h3>
                                </div>
                                <p class="text-xs text-muted-foreground leading-relaxed">
                                    {{ item.desc }}
                                </p>
                            </div>
                            <div class="shrink-0 w-10 h-10 rounded-xl bg-sky-500/10 text-sky-700 dark:text-sky-400 flex items-center justify-center">
                                <component :is="item.icon" class="w-5 h-5" />
                            </div>
                        </div>

                        <div class="pt-2 border-t border-border/60 flex items-center justify-between gap-2">
                            <div class="flex items-baseline gap-1.5">
                                <span class="font-mono text-xl font-black text-sky-800 dark:text-sky-300">
                                    {{ item.count }}
                                </span>
                                <span class="text-xs font-medium text-muted-foreground">
                                    {{ item.unit }}
                                </span>
                            </div>
                            <Link
                                :href="item.href"
                                class="inline-flex items-center gap-1 rounded-lg bg-sky-500/10 hover:bg-sky-500/20 px-3 py-1.5 text-xs font-bold text-sky-800 dark:text-sky-300 transition-colors"
                            >
                                Tinjau Sekarang
                                <ArrowRight class="w-3.5 h-3.5" />
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- B. Jika tidak ada antrean: Banner emerald bersih -->
                <div
                    v-else
                    class="rounded-2xl border border-emerald-500/30 bg-emerald-500/10 p-4 sm:p-5 flex items-center gap-3.5"
                >
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-800 dark:text-emerald-300 flex items-center justify-center shrink-0">
                        <CheckCircle2 class="w-5 h-5" />
                    </div>
                    <div class="space-y-0.5">
                        <h3 class="text-sm font-bold text-emerald-950 dark:text-emerald-200">
                            Semua antrean operasional terkendali
                        </h3>
                        <p class="text-xs text-emerald-800/80 dark:text-emerald-300/80 leading-relaxed">
                            Tidak ada permohonan pendaftaran, akun copytrade, maupun pencairan komisi yang menunggu verifikasi saat ini.
                        </p>
                    </div>
                </div>
            </section>

            <!-- 2. SEKSI MONITORING TAGIHAN & LAPORAN MASTER FEE INVESTOR -->
            <section class="space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-xs font-bold uppercase tracking-wider text-muted-foreground">
                            Monitoring Tagihan &amp; Laporan Master Fee
                        </h2>
                        <span
                            v-if="monitoringTotal > 0"
                            class="inline-flex items-center gap-1 rounded-full border border-amber-500/30 bg-amber-500/10 px-2 py-0.5 text-[10px] font-semibold text-amber-800 dark:text-amber-300"
                        >
                            <AlertTriangle class="h-3 w-3" />
                            <span>{{ monitoringTotal }} Menunggu Tindak Lanjut Investor</span>
                        </span>
                        <span
                            v-else
                            class="inline-flex items-center gap-1 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-2 py-0.5 text-[10px] font-semibold text-emerald-800 dark:text-emerald-300"
                        >
                            <CheckCircle2 class="h-3 w-3" />
                            <span>Semua Terkendali</span>
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div
                        v-for="card in monitoringCards"
                        :key="card.id"
                        :class="[
                            'bg-card rounded-2xl border p-5 shadow-xs transition-colors flex flex-col justify-between gap-4',
                            card.count > 0
                                ? 'border-amber-500/40 hover:border-amber-500/60'
                                : 'border-border hover:border-emerald-500/40',
                        ]"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="space-y-1">
                                <h3 class="text-sm font-bold text-foreground">
                                    {{ card.title }}
                                </h3>
                                <p class="text-xs text-muted-foreground leading-relaxed">
                                    {{ card.desc }}
                                </p>
                            </div>
                            <div
                                :class="[
                                    'shrink-0 w-10 h-10 rounded-xl flex items-center justify-center',
                                    card.count > 0
                                        ? 'bg-amber-500/10 text-amber-700 dark:text-amber-300'
                                        : 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
                                ]"
                            >
                                <component
                                    :is="card.count > 0 ? card.icon : CheckCircle2"
                                    class="w-5 h-5"
                                />
                            </div>
                        </div>

                        <div class="pt-2.5 border-t border-border/60 flex flex-wrap items-center justify-between gap-2">
                            <div class="space-y-0.5">
                                <div class="flex items-baseline gap-1.5">
                                    <span
                                        :class="[
                                            'font-mono text-xl font-black',
                                            card.count > 0
                                                ? 'text-amber-800 dark:text-amber-300'
                                                : 'text-emerald-700 dark:text-emerald-300',
                                        ]"
                                    >
                                        {{ card.count }}
                                    </span>
                                    <span class="text-xs font-medium text-muted-foreground">
                                        {{ card.unit }}
                                    </span>
                                </div>
                                <p class="text-[11px] font-medium text-muted-foreground">
                                    {{ card.subInfo }}
                                </p>
                            </div>

                            <Link
                                :href="card.href"
                                :class="[
                                    'inline-flex min-h-[36px] items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-bold transition-colors shrink-0',
                                    card.count > 0
                                        ? 'bg-amber-500/15 hover:bg-amber-500/25 text-amber-900 dark:text-amber-200'
                                        : 'bg-muted hover:bg-muted/80 text-foreground',
                                ]"
                            >
                                <span>{{ card.ctaLabel }}</span>
                                <ArrowRight class="w-3.5 h-3.5" />
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 3. SEKSI RINGKASAN EKOSISTEM (4 KARTU METRIK) -->
            <section class="space-y-3">
                <h2 class="text-xs font-bold uppercase tracking-wider text-muted-foreground">
                    Ringkasan Ekosistem Trading
                </h2>

                <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    <!-- Kartu 1: Total Member -->
                    <div class="bg-card border-border rounded-2xl border p-4 shadow-xs sm:p-5">
                        <div class="text-muted-foreground flex items-center justify-between gap-1.5 text-xs font-bold">
                            <span class="flex items-center gap-1.5">
                                <Users class="h-4 w-4" /> Total Member
                            </span>
                        </div>
                        <p class="text-foreground mt-1 font-mono text-xl font-black sm:text-2xl">
                            {{ stats.member_count }}
                        </p>
                        <p class="text-muted-foreground mt-0.5 text-[11px] font-medium">
                            <Link href="/admin/users" class="hover:text-primary transition-colors">
                                {{ stats.active_member_count }} member terverifikasi &rarr;
                            </Link>
                        </p>
                    </div>

                    <!-- Kartu 2: Investor Aktif -->
                    <div class="bg-card border-border rounded-2xl border p-4 shadow-xs sm:p-5">
                        <div class="text-muted-foreground flex items-center justify-between gap-1.5 text-xs font-bold">
                            <span class="flex items-center gap-1.5">
                                <ArrowLeftRight class="h-4 w-4" /> Investor Aktif
                            </span>
                        </div>
                        <p class="text-foreground mt-1 font-mono text-xl font-black sm:text-2xl">
                            {{ stats.active_investor_count }}
                        </p>
                        <p class="text-muted-foreground mt-0.5 text-[11px] font-medium">
                            <Link href="/admin/copytrade" class="hover:text-primary transition-colors">
                                {{ stats.active_copytrade_count }} akun copytrade &rarr;
                            </Link>
                        </p>
                    </div>

                    <!-- Kartu 3: Affiliator Aktif -->
                    <div class="bg-card border-border rounded-2xl border p-4 shadow-xs sm:p-5">
                        <div class="text-muted-foreground flex items-center justify-between gap-1.5 text-xs font-bold">
                            <span class="flex items-center gap-1.5">
                                <UserCheck class="h-4 w-4" /> Affiliator Aktif
                            </span>
                        </div>
                        <p class="text-foreground mt-1 font-mono text-xl font-black sm:text-2xl">
                            {{ stats.active_affiliator_count }}
                        </p>
                        <p class="text-muted-foreground mt-0.5 text-[11px] font-medium">
                            <Link href="/admin/affiliates" class="hover:text-primary transition-colors">
                                Mitra jaringan aktif &rarr;
                            </Link>
                        </p>
                    </div>

                    <!-- Kartu 4: Tautan Undangan Aktif -->
                    <div class="bg-card border-border rounded-2xl border p-4 shadow-xs sm:p-5">
                        <div class="text-muted-foreground flex items-center justify-between gap-1.5 text-xs font-bold">
                            <span class="flex items-center gap-1.5">
                                <Link2 class="h-4 w-4" /> Undangan Aktif
                            </span>
                        </div>
                        <p class="text-foreground mt-1 font-mono text-xl font-black sm:text-2xl">
                            {{ stats.active_invites }}
                        </p>
                        <p class="text-muted-foreground mt-0.5 text-[11px] font-medium">
                            <Link href="/admin/invitations" class="hover:text-primary transition-colors">
                                Kelola tautan undangan &rarr;
                            </Link>
                        </p>
                    </div>
                </div>
            </section>

            <!-- 3. SEKSI AKSI CEPAT OPERASIONAL (4 TOMBOL RINGKAS) -->
            <section class="space-y-3">
                <h2 class="text-xs font-bold uppercase tracking-wider text-muted-foreground">
                    Aksi Cepat Operasional
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <Link
                        v-for="s in shortcuts"
                        :key="s.href"
                        :href="s.href"
                        class="bg-card border-border hover:bg-muted text-foreground flex min-h-[48px] cursor-pointer items-center justify-between gap-2.5 rounded-xl border px-3.5 py-3 text-xs font-bold shadow-xs transition-colors"
                    >
                        <div class="flex items-center gap-2.5 min-w-0">
                            <component :is="s.icon" class="text-primary h-4 w-4 shrink-0" />
                            <span class="truncate">{{ s.label }}</span>
                        </div>
                        <ArrowRight class="text-muted-foreground h-3.5 w-3.5 shrink-0" />
                    </Link>
                </div>
            </section>

        </div>
    </AdminLayout>
</template>
