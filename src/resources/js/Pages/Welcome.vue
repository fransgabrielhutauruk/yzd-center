<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Button } from '@/Components/ui/button';
import { CheckCircle2, Shield, Users, ArrowRight } from 'lucide-vue-next';
</script>

<template>
    <AuthLayout title="Selamat Datang - YZD Trading">
        <div class="text-center space-y-6">
            <!-- Icon Badge -->
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-primary-50 dark:bg-primary-950/60 border border-primary-200 dark:border-primary-800 text-primary-600 dark:text-primary-400 mx-auto shadow-xs">
                <Shield class="w-7 h-7" />
            </div>

            <div class="space-y-2">
                <h1 class="text-2xl font-bold tracking-tight text-foreground">
                    YZD Trading Management
                </h1>
                <p class="text-sm text-muted-foreground leading-relaxed">
                    Sistem Manajemen Portofolio, Verifikasi CopyTrade, dan Afiliasi Terintegrasi.
                </p>
            </div>

            <!-- Status Check Card -->
            <div class="bg-slate-50 dark:bg-slate-900/50 rounded-xl p-4 border border-border/60 text-left space-y-2.5 text-xs">
                <div class="flex items-center gap-2 text-emerald-600 dark:text-emerald-400 font-semibold">
                    <CheckCircle2 class="w-4 h-4" />
                    <span>Fondasi Sistem Siap Digunakan</span>
                </div>
                <div class="text-muted-foreground space-y-1">
                    <p>• Laravel 13 + Inertia.js v2 + Vue 3</p>
                    <p>• Tailwind CSS v4 + Tema Ungu & Oranye</p>
                    <p>• Multi-role: Administrator, Investor, Afiliator</p>
                    <p>• Dark Mode & Dukungan Multi-Bahasa</p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="space-y-3 pt-2">
                <Button class="w-full bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2.5 rounded-lg shadow-sm">
                    Masuk ke Sistem
                </Button>

                <p class="text-xs text-muted-foreground">
                    Pendaftaran investor hanya dapat dilakukan melalui <span class="text-foreground font-semibold">Tautan Undangan</span> resmi dari Administrator.
                </p>
            </div>
        </div>
    </AuthLayout>
</template>
