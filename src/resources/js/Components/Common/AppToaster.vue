<script setup>
import { watch, nextTick } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Toaster, toast } from 'vue-sonner';
import 'vue-sonner/style.css';
import { useDarkMode } from '@/composables/useDarkMode';

const { isDark } = useDarkMode();
const page = usePage();

// Otomatis pantau flash messages dari controller Laravel
watch(
    () => [
        page.props.flash?.success,
        page.props.flash?.error,
        page.props.flash?.warning,
        page.props.flash?.info,
    ],
    ([success, error, warning, info]) => {
        nextTick(() => {
            if (success) {
                toast.success(success);
            }
            if (error) {
                toast.error(error);
            }
            if (warning) {
                toast.warning(warning);
            }
            if (info) {
                toast.info(info);
            }
        });
    },
    { immediate: true }
);
</script>

<template>
    <Toaster
        :theme="isDark ? 'dark' : 'light'"
        position="top-center"
        rich-colors
        close-button
        :duration="3500"
    />
</template>

