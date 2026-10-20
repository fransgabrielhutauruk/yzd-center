<script setup>
import { ref } from 'vue';
import { Download, Share, PlusSquare, Check } from 'lucide-vue-next';
import { usePwaInstall } from '@/composables/usePwaInstall';
import AppModal from '@/Components/Common/AppModal.vue';
import { Button } from '@/Components/ui/button';

const { canShowInstallButton, triggerInstall, isIos } = usePwaInstall();

const showIosModal = ref(false);
const isInstalling = ref(false);

const handleInstallClick = async () => {
    if (isIos.value) {
        showIosModal.value = true;
        return;
    }

    isInstalling.value = true;
    try {
        const result = await triggerInstall();
        if (result && result.outcome === 'ios_guide') {
            showIosModal.value = true;
        }
    } finally {
        isInstalling.value = false;
    }
};
</script>

<template>
    <div v-if="canShowInstallButton" class="relative inline-flex items-center">
        <!-- Install Trigger Button -->
        <button
            type="button"
            @click="handleInstallClick"
            :disabled="isInstalling"
            class="inline-flex items-center justify-center gap-1.5 p-2 sm:px-3 sm:py-1.5 rounded-lg text-xs font-semibold bg-primary-600 hover:bg-primary-700 text-white dark:bg-primary-600 dark:hover:bg-primary-500 shadow-xs transition-colors cursor-pointer min-h-[33px] min-w-[33px] focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 disabled:opacity-50 disabled:cursor-not-allowed"
            title="Pasang Aplikasi YZD Center di Perangkat"
            aria-label="Install Aplikasi YZD Center"
        >
            <Download class="w-5 h-5 sm:w-4 sm:h-4 shrink-0 text-white" />
            <span class="hidden sm:inline">Install App</span>
        </button>

        <!-- iOS / iPadOS Safari Installation Guide Modal -->
        <AppModal
            :show="showIosModal"
            title="Pasang Aplikasi di iOS"
            max-width="md"
            :closeable="true"
            @close="showIosModal = false"
        >
            <div class="space-y-4">
                <p class="text-sm text-muted-foreground leading-relaxed">
                    Pasang pintasan aplikasi <strong class="text-foreground">YZD Center</strong> ke Layar Utama iPhone atau iPad Anda untuk pengalaman akses cepat seperti aplikasi bawaan.
                </p>

                <div class="space-y-3 pt-1">
                    <!-- Step 1 -->
                    <div class="flex items-start gap-3 p-3 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800">
                        <div class="p-2 rounded-lg bg-primary-100 text-primary-700 dark:bg-primary-950/80 dark:text-primary-300 shrink-0">
                            <Share class="w-5 h-5" />
                        </div>
                        <div class="text-xs sm:text-sm">
                            <span class="font-semibold text-foreground block mb-0.5">1. Ketuk Menu Bagikan (Share)</span>
                            <span class="text-muted-foreground">Ketuk ikon <strong class="text-foreground font-medium">Bagikan</strong> pada bilah navigasi Safari (biasanya di bagian bawah layar iPhone atau atas iPad).</span>
                        </div>
                    </div>

                    <!-- Step 2 -->
                    <div class="flex items-start gap-3 p-3 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800">
                        <div class="p-2 rounded-lg bg-primary-100 text-primary-700 dark:bg-primary-950/80 dark:text-primary-300 shrink-0">
                            <PlusSquare class="w-5 h-5" />
                        </div>
                        <div class="text-xs sm:text-sm">
                            <span class="font-semibold text-foreground block mb-0.5">2. Tambah ke Layar Utama</span>
                            <span class="text-muted-foreground">Gulir ke bawah pada daftar opsi lembar aksi, lalu pilih <strong class="text-foreground font-medium">Tambah ke Layar Utama (Add to Home Screen)</strong>.</span>
                        </div>
                    </div>

                    <!-- Step 3 -->
                    <div class="flex items-start gap-3 p-3 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800">
                        <div class="p-2 rounded-lg bg-primary-100 text-primary-700 dark:bg-primary-950/80 dark:text-primary-300 shrink-0">
                            <Check class="w-5 h-5" />
                        </div>
                        <div class="text-xs sm:text-sm">
                            <span class="font-semibold text-foreground block mb-0.5">3. Simpan Aplikasi</span>
                            <span class="text-muted-foreground">Ketuk <strong class="text-foreground font-medium">Tambah (Add)</strong> di sudut kanan atas. Pintasan YZD Center akan langsung tersedia di layar utama perangkat Anda.</span>
                        </div>
                    </div>
                </div>
            </div>

            <template #footer>
                <Button
                    type="button"
                    variant="default"
                    @click="showIosModal = false"
                    class="w-full sm:w-auto font-medium"
                >
                    Saya Mengerti
                </Button>
            </template>
        </AppModal>
    </div>
</template>
