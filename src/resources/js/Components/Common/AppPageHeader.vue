<script setup>
import { Link } from '@inertiajs/vue3';
import { ChevronRight } from 'lucide-vue-next';

defineProps({
    breadcrumbs: {
        type: Array,
        default: () => [],
    },
    title: {
        type: String,
        required: true,
    },
    subtitle: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <div class="mb-6">
        <!-- Breadcrumb Navigation -->
        <nav
            v-if="breadcrumbs && breadcrumbs.length > 0"
            aria-label="Breadcrumb"
            class="mb-2 flex flex-wrap items-center gap-1.5 text-xs"
        >
            <template v-for="(crumb, idx) in breadcrumbs" :key="idx">
                <Link
                    v-if="crumb.href && idx < breadcrumbs.length - 1"
                    :href="crumb.href"
                    class="font-medium text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200 transition-colors"
                >
                    {{ crumb.label }}
                </Link>
                <span
                    v-else-if="idx < breadcrumbs.length - 1"
                    class="font-medium text-slate-500 dark:text-slate-400"
                >
                    {{ crumb.label }}
                </span>
                <span
                    v-else
                    class="font-semibold text-primary-600 dark:text-primary-400"
                    aria-current="page"
                >
                    {{ crumb.label }}
                </span>

                <ChevronRight
                    v-if="idx < breadcrumbs.length - 1"
                    class="h-3.5 w-3.5 text-slate-400 dark:text-slate-500 shrink-0"
                />
            </template>
        </nav>

        <!-- Title, Subtitle, and Header Actions -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                    {{ title }}
                </h1>
                <p
                    v-if="subtitle"
                    class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed max-w-4xl"
                >
                    {{ subtitle }}
                </p>
            </div>

            <!-- Optional Actions Slot -->
            <div v-if="$slots.actions" class="flex items-center gap-2.5 shrink-0 self-start sm:self-center">
                <slot name="actions" />
            </div>
        </div>
    </div>
</template>
