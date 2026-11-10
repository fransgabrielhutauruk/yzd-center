<script setup>
import { ref, computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { Info, X } from 'lucide-vue-next';

const page = usePage();
const isDismissed = ref(false);

const notice = computed(() => page.props.first_period_notice);
const show = computed(() => !isDismissed.value && !!notice.value);

const dismiss = () => {
    isDismissed.value = true;
    router.post(
        route('member.first_period_banner.dismiss'),
        {},
        {
            preserveScroll: true,
            preserveState: true,
            onError: () => {},
        }
    );
};
</script>

<template>
    <div
        v-if="show"
        role="status"
        aria-live="polite"
        class="mb-4 flex items-center justify-between gap-3 rounded-xl border border-blue-200 bg-blue-50/90 px-3.5 py-2.5 text-xs text-blue-900 shadow-xs sm:px-4 dark:border-blue-800/60 dark:bg-blue-950/50 dark:text-blue-100"
    >
        <div class="flex min-w-0 items-center gap-2.5">
            <Info class="h-4 w-4 shrink-0 text-blue-600 dark:text-blue-400" />
            <p class="leading-relaxed">
                <span class="font-bold">Periode pertama akun #{{ notice.nomor_akun }}:</span>
                {{ notice.tanggal_mulai }} – {{ notice.periode_akhir }}.
                <span class="font-semibold text-blue-700 dark:text-blue-300"
                    >Input profit dibuka {{ notice.input_dibuka }}.</span
                >
                <span v-if="notice.other_count > 0" class="font-medium text-blue-700/80 dark:text-blue-300/80">
                    (+{{ notice.other_count }} akun lain)
                </span>
                <span class="hidden md:inline text-blue-800/80 dark:text-blue-200/80">
                    ({{ notice.aturan }})
                </span>
            </p>
        </div>

        <button
            type="button"
            @click="dismiss"
            class="flex h-7 w-7 shrink-0 cursor-pointer items-center justify-center rounded-lg text-blue-700 transition-colors hover:bg-blue-100/80 hover:text-blue-900 focus:outline-hidden focus:ring-2 focus:ring-blue-500 dark:text-blue-300 dark:hover:bg-blue-900/60 dark:hover:text-blue-100"
            aria-label="Tutup pemberitahuan periode pertama"
            title="Tutup pemberitahuan"
        >
            <X class="h-4 w-4" />
        </button>
    </div>
</template>
