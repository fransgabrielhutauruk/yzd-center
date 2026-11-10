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

            <!-- 2. SEKSI RINGKASAN EKOSISTEM (4 KARTU METRIK) -->
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
