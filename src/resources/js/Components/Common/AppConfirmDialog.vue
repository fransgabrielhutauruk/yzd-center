<script setup>
import { computed } from 'vue';
import AppModal from './AppModal.vue';
import { Button } from '@/Components/ui/button';
import { AlertTriangle, AlertCircle, Info, Trash2 } from 'lucide-vue-next';

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
    cancelText: {
        type: String,
        default: 'Batal',
    },
    type: {
        type: String,
        default: 'danger',
        validator: (v) => ['danger', 'warning', 'info'].includes(v),
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['confirm', 'cancel']);

const iconComponent = computed(() => {
    switch (props.type) {
        case 'warning':
            return AlertTriangle;
        case 'info':
            return Info;
        case 'danger':
        default:
            return Trash2;
    }
});

const iconStyle = computed(() => {
    switch (props.type) {
        case 'warning':
            return 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20';
        case 'info':
            return 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20';
        case 'danger':
        default:
            return 'bg-destructive/10 text-destructive border-destructive/20';
    }
});

const confirmButtonClass = computed(() => {
    switch (props.type) {
        case 'warning':
            return 'bg-amber-600 hover:bg-amber-700 text-white';
        case 'info':
            return 'bg-primary-600 hover:bg-primary-700 text-white';
        case 'danger':
        default:
            return 'bg-destructive hover:bg-destructive/90 text-destructive-foreground';
    }
});
</script>

<template>
    <AppModal
        :show="show"
        max-width="md"
        :closeable="!loading"
        @close="emit('cancel')"
    >
        <div class="flex items-start gap-4">
            <!-- Alert Icon Badge -->
            <div
                :class="[
                    'w-12 h-12 rounded-2xl border flex items-center justify-center flex-shrink-0',
                    iconStyle
                ]"
            >
                <component :is="iconComponent" class="w-6 h-6" />
            </div>

            <!-- Content Area -->
            <div class="space-y-1.5 flex-1 min-w-0">
                <h4 class="text-base font-bold text-foreground">
                    {{ title }}
                </h4>
                <p class="text-xs text-muted-foreground leading-relaxed">
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
                class="px-4 py-2 text-xs font-medium"
            >
                {{ cancelText }}
            </Button>

            <Button
                type="button"
                :disabled="loading"
                @click="emit('confirm')"
                :class="[
                    'px-5 py-2 text-xs font-semibold rounded-lg shadow-sm transition-colors disabled:opacity-50',
                    confirmButtonClass
                ]"
            >
                {{ loading ? 'Memproses...' : confirmText }}
            </Button>
        </div>
    </AppModal>
</template>

