<script setup>
import { computed } from 'vue';
import AppModal from './AppModal.vue';
import { Button } from '@/Components/ui/button';
import { AlertTriangle, AlertCircle, Info, Trash2, CheckCircle2, Loader2 } from 'lucide-vue-next';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Konfirmasi Tindakan',
    },
    message: {
        type: String,
        default: 'Apakah Anda yakin ingin melanjutkan tindakan ini?',
    },
    confirmText: {
        type: String,
        default: 'Konfirmasi',
    },
    confirmLabel: {
        type: String,
        default: '',
    },
    cancelText: {
        type: String,
        default: 'Batal',
    },
    cancelLabel: {
        type: String,
        default: '',
    },
    type: {
        type: String,
        default: 'danger',
        validator: (v) => ['danger', 'warning', 'info', 'success'].includes(v),
    },
    icon: {
        type: [Object, Function],
        default: null,
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['confirm', 'cancel']);

const resolvedConfirmText = computed(() => props.confirmLabel || props.confirmText);
const resolvedCancelText = computed(() => props.cancelLabel || props.cancelText);

const iconComponent = computed(() => {
    if (props.icon) {
        return props.icon;
    }
    switch (props.type) {
        case 'danger':
            return Trash2;
        case 'warning':
            return AlertTriangle;
        case 'info':
            return Info;
        case 'success':
            return CheckCircle2;
        default:
            return AlertCircle;
    }
});

const iconStyle = computed(() => {
    switch (props.type) {
        case 'danger':
            return 'bg-destructive/10 text-destructive border-destructive/20';
        case 'warning':
            return 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20';
        case 'info':
            return 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20';
        case 'success':
            return 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20';
        default:
            return 'bg-destructive/10 text-destructive border-destructive/20';
    }
});

const confirmButtonClass = computed(() => {
    switch (props.type) {
        case 'danger':
            return 'bg-destructive hover:bg-destructive/90 text-white';
        case 'warning':
            return 'bg-amber-600 hover:bg-amber-700 text-white';
        case 'info':
            return 'bg-primary-600 hover:bg-primary-700 text-white';
        case 'success':
            return 'bg-emerald-600 hover:bg-emerald-700 text-white';
        default:
            return 'bg-destructive hover:bg-destructive/90 text-destructive-foreground';
    }
});
</script>

<template>
    <AppModal
        :show="show"
        max-width="md"
        :show-header="false"
        :show-close="false"
        :closeable="!loading"
        @close="emit('cancel')"
    >
        <template #header></template>
        <div class="flex items-center gap-4">
            <!-- Alert Icon Badge -->
            <div
                :class="[
                    'flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl border',
                    iconStyle,
                ]"
            >
                <component :is="iconComponent" class="h-6 w-6" />
            </div>

            <!-- Content Area -->
            <div class="min-w-0 flex-1 space-y-1.5">
                <h4 class="text-foreground text-base font-bold">
                    {{ title }}
                </h4>
                <p class="text-muted-foreground text-xs leading-relaxed">
                    {{ message }}
                </p>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="mt-6 flex items-center justify-end gap-3">
            <Button
                type="button"
                variant="outline"
                :disabled="loading"
                @click="emit('cancel')"
                class="px-4 py-2 text-xs font-medium cursor-pointer"
            >
                {{ resolvedCancelText }}
            </Button>

            <Button
                type="button"
                :disabled="loading"
                @click="emit('confirm')"
                :class="[
                    'rounded-lg px-5 py-2 text-xs font-semibold shadow-sm transition-colors disabled:opacity-50 cursor-pointer',
                    confirmButtonClass,
                ]"
            >
                <Loader2 v-if="loading" class="h-3.5 w-3.5 animate-spin mr-1.5" />
                <span>{{ loading ? 'Memproses...' : resolvedConfirmText }}</span>
            </Button>
        </div>
    </AppModal>
</template>
