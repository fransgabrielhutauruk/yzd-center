<script setup>
import { watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Toaster, toast } from 'vue-sonner';
import 'vue-sonner/style.css';
import { useDarkMode } from '@/composables/useDarkMode';

const { isDark } = useDarkMode();
const page = usePage();

// Otomatis pantau flash messages dari controller Laravel
watch(
    () => page.props.flash,
    (flash) => {
        if (!flash) return;

        if (flash.success) {
            toast.success(flash.success);
        }
        if (flash.error) {
            toast.error(flash.error);
        }
        if (flash.warning) {
            toast.warning(flash.warning);
        }
        if (flash.info) {
            toast.info(flash.info);
        }
    },
    { deep: true }
);
</script>

<template>
    <Toaster
        :theme="isDark ? 'dark' : 'light'"
        position="top-right"
        rich-colors
        close-button
        :duration="3500"
    />
</template>

