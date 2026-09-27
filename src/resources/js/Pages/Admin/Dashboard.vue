<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    Users,
    UserCheck,
    Link2,
    ArrowRight,
    ShieldCheck,
    FileCheck,
    Inbox,
    PlusCircle
} from 'lucide-vue-next';

defineProps({
    stats: {
        type: Object,
        default: () => ({
            investor_count: 0,
            affiliator_count: 0,
            active_invites: 0,
        }),
    },
});
</script>

<template>
    <AdminLayout
        title="Dasbor Administrator"
        subtitle="Sistem manajemen trading terintegrasi YZD Center. Pantau keanggotaan investor, mitra affiliator, dan kelola tautan pendaftaran eksklusif."
        :breadcrumbs="[
            { label: 'Menu Utama' },
            { label: 'Dasbor Admin' }
        ]"
    >
        <div class="space-y-6">

            <!-- Stats Metric Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <!-- Stat 1: Total Investor -->
                <div class="bg-card rounded-2xl border border-border p-5 shadow-xs flex items-center justify-between">
                    <div class="space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                            Total Investor
                        </p>
                        <p class="text-2xl sm:text-3xl font-extrabold text-foreground">
                            {{ stats.investor_count }}
                        </p>
                        <p class="text-[11px] text-muted-foreground">
                            Anggota pemodal terdaftar
                        </p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                        <Users class="w-6 h-6" />
                    </div>
                </div>

                <!-- Stat 2: Total Affiliator -->
                <div class="bg-card rounded-2xl border border-border p-5 shadow-xs flex items-center justify-between">
                    <div class="space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                            Total Affiliator
                        </p>
                        <p class="text-2xl sm:text-3xl font-extrabold text-foreground">
                            {{ stats.affiliator_count }}
                        </p>
                        <p class="text-[11px] text-muted-foreground">
                            Mitra referral aktif
                        </p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-purple-500/10 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                        <UserCheck class="w-6 h-6" />
                    </div>
                </div>

                <!-- Stat 3: Undangan Aktif -->
                <div class="bg-card rounded-2xl border border-border p-5 shadow-xs flex items-center justify-between">
                    <div class="space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                            Tautan Undangan Aktif
                        </p>
                        <p class="text-2xl sm:text-3xl font-extrabold text-foreground">
                            {{ stats.active_invites }}
                        </p>
                        <Link
                            href="/admin/invitations"
                            class="inline-flex items-center gap-1 text-[11px] font-semibold text-primary-600 dark:text-primary-400 hover:underline"
                        >
                            Kelola Undangan <ArrowRight class="w-3 h-3" />
                        </Link>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                        <Link2 class="w-6 h-6" />
                    </div>
                </div>
            </div>

            <!-- Quick Access / Shortcuts Grid -->
            <div class="space-y-3">
                <h3 class="text-sm font-bold uppercase tracking-wider text-foreground">
                    Aksi Cepat Operasional
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <Link
                        href="/admin/invitations"
                        class="p-4 rounded-xl bg-card border border-border hover:border-primary-500 hover:shadow-md transition-all flex items-start gap-3 group"
                    >
                        <div class="p-2.5 rounded-lg bg-primary-500/10 text-primary-600 group-hover:bg-primary-600 group-hover:text-white transition-colors">
                            <PlusCircle class="w-5 h-5" />
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-foreground group-hover:text-primary-600 transition-colors">
                                Buat Tautan Undangan
                            </h4>
                            <p class="text-xs text-muted-foreground mt-0.5">
                                Terbitkan tautan pendaftaran eksklusif untuk investor atau affiliator.
                            </p>
                        </div>
                    </Link>

                    <Link
                        href="/admin/verifikasi-legalitas"
                        class="p-4 rounded-xl bg-card border border-border hover:border-primary-500 hover:shadow-md transition-all flex items-start gap-3 group"
                    >
                        <div class="p-2.5 rounded-lg bg-blue-500/10 text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                            <FileCheck class="w-5 h-5" />
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-foreground group-hover:text-blue-600 transition-colors">
                                Verifikasi Legalitas
                            </h4>
                            <p class="text-xs text-muted-foreground mt-0.5">
                                Tinjau audit persetujuan syarat, identitas NIK, dan dokumen investor.
                            </p>
                        </div>
                    </Link>

                    <Link
                        href="/admin/antrean-validasi"
                        class="p-4 rounded-xl bg-card border border-border hover:border-primary-500 hover:shadow-md transition-all flex items-start gap-3 group"
                    >
                        <div class="p-2.5 rounded-lg bg-accent-500/10 text-accent-600 group-hover:bg-accent-600 group-hover:text-white transition-colors">
                            <Inbox class="w-5 h-5" />
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-foreground group-hover:text-accent-600 transition-colors">
                                Antrean Validasi
                            </h4>
                            <p class="text-xs text-muted-foreground mt-0.5">
                                Pantau permohonan deposit baru dan pencairan profit member.
                            </p>
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
