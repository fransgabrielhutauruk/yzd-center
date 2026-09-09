<script setup>
import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { CheckCircle2, AlertCircle, X } from 'lucide-vue-next';

const page = usePage();
const visible = ref(false);
const message = ref('');
const type = ref('success');

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) {
            message.value = flash.success;
            type.value = 'success';
            visible.value = true;
        } else if (flash?.error) {
            message.value = flash.error;
            type.value = 'error';
            visible.value = true;
        } else {
            visible.value = false;
        }
    },
    { deep: true, immediate: true }
);

const close = () => {
    visible.value = false;
};
</script>

<template>
    <transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="opacity-0 -translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 -translate-y-2"
    >
        <div
            v-if="visible && message"
            :class="[
                'flex items-center justify-between p-4 mb-6 rounded-lg border text-sm font-medium shadow-sm',
                type === 'success'
                    ? 'bg-emerald-50 border-emerald-200 text-emerald-800 dark:bg-emerald-950/40 dark:border-emerald-800 dark:text-emerald-300'
                    : 'bg-red-50 border-red-200 text-red-800 dark:bg-red-950/40 dark:border-red-800 dark:text-red-300'
            ]"
            role="alert"
        >
            <div class="flex items-center gap-3">
                <CheckCircle2 v-if="type === 'success'" class="w-5 h-5 flex-shrink-0 text-emerald-600 dark:text-emerald-400" />
                <AlertCircle v-else class="w-5 h-5 flex-shrink-0 text-red-600 dark:text-red-400" />
                <span>{{ message }}</span>
            </div>

            <button
                type="button"
                @click="close"
                class="ml-auto -mx-1.5 -my-1.5 p-1.5 inline-flex items-center justify-center h-8 w-8 rounded-md text-gray-500 hover:text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-800 transition-colors"
                aria-label="Tutup"
            >
                <X class="w-4 h-4" />
            </button>
        </div>
    </transition>
</template>
