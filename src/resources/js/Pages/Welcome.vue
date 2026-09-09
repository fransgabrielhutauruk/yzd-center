<script setup>
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Welcome" />
    <div class="min-h-screen flex items-center justify-center bg-gray-100 dark:bg-gray-900 transition-colors duration-300">
        <div class="bg-white dark:bg-gray-800 p-8 rounded-lg shadow-md max-w-md w-full text-center">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                {{ __('Welcome') }} to {{ __('Dashboard') }}
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                Setup Vue 3, Inertia, Tailwind CSS, Ziggy, and i18n is complete.
            </p>
        </div>
    </div>
</template>

