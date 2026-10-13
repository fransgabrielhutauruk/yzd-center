<script setup>
import { computed } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps({
    /**
     * Nilai tab aktif (v-model)
     */
    modelValue: {
        type: [String, Number],
        required: true,
    },
    /**
     * Daftar tab: Array<{ key: string, label: string, icon?: Component, count?: number|string, badge?: string, badgeVariant?: string, disabled?: boolean }>
     */
    items: {
        type: Array,
        required: true,
        default: () => [],
    },
    /**
     * Sinkronkan tab aktif ke query parameter URL browser
     */
    syncUrl: {
        type: Boolean,
        default: true,
    },
    /**
     * Nama parameter URL query
     */
    urlParam: {
        type: String,
        default: 'tab',
    },
    /**
     * Kelas wrapper tambahan
     */
    class: {
        type: [String, Array, Object],
        default: '',
    },
});

const emit = defineEmits(['update:modelValue', 'change']);

const selectTab = (tab) => {
    if (tab.disabled) return;
    const tabKey = tab.key ?? tab.value ?? tab.id;
    if (tabKey === props.modelValue) return;

    emit('update:modelValue', tabKey);
    emit('change', tabKey);

    if (props.syncUrl) {
        try {
            const url = new URL(window.location.href);
            url.searchParams.set(props.urlParam, String(tabKey));
            window.history.replaceState({}, '', url.toString());
        } catch {
            // Fallback jika window tidak tersedia (SSR/test)
        }
    }
};

const getBadgeClasses = (item, isActive) => {
    if (item.badgeVariant === 'warning') {
        return 'bg-amber-500/10 text-amber-700 dark:text-amber-300 border border-amber-500/30';
    }
    if (item.badgeVariant === 'info' || item.badgeVariant === 'sky') {
        return 'bg-sky-500/10 text-sky-800 dark:text-sky-300 border border-sky-500/30';
    }
    if (item.badgeVariant === 'success' || item.badgeVariant === 'emerald') {
        return 'bg-emerald-500/10 text-emerald-800 dark:text-emerald-300 border border-emerald-500/30';
    }
    if (item.badgeVariant === 'destructive') {
        return 'bg-destructive/10 text-destructive border border-destructive/30';
    }

    // Default badge / count
    if (isActive) {
        return 'bg-primary-100 text-primary-800 dark:bg-primary-950/60 dark:text-primary-300';
    }
    return 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300';
};
</script>

<template>
    <div :class="cn('border-border border-b', props.class)">
        <nav
            class="scrollbar-hide -mb-px flex items-center gap-4 sm:gap-6 overflow-x-auto"
            role="tablist"
            aria-orientation="horizontal"
        >
            <button
                v-for="item in items"
                :key="item.key ?? item.value ?? item.id"
                type="button"
                role="tab"
                :id="`tab-${item.key ?? item.value ?? item.id}`"
                :aria-selected="(item.key ?? item.value ?? item.id) === modelValue"
                :aria-controls="`tabpanel-${item.key ?? item.value ?? item.id}`"
                :disabled="item.disabled"
                @click="selectTab(item)"
                :class="[
                    'inline-flex min-h-[40px] items-center gap-2 border-b-2 pb-3 pt-2 text-xs transition-colors cursor-pointer whitespace-nowrap sm:text-sm select-none shrink-0',
                    (item.key ?? item.value ?? item.id) === modelValue
                        ? 'border-primary-600 text-primary-600 dark:text-primary-400 font-bold'
                        : 'border-transparent text-muted-foreground hover:border-border hover:text-foreground font-medium',
                    item.disabled ? 'opacity-50 cursor-not-allowed pointer-events-none' : '',
                ]"
            >
                <slot
                    :name="`tab-${item.key ?? item.value ?? item.id}`"
                    :item="item"
                    :is-active="(item.key ?? item.value ?? item.id) === modelValue"
                >
                    <!-- Icon Opsional -->
                    <component
                        :is="item.icon"
                        v-if="item.icon"
                        class="h-4 w-4 shrink-0"
                        :stroke-width="(item.key ?? item.value ?? item.id) === modelValue ? 2.2 : 2"
                    />

                    <!-- Label Teks -->
                    <span>{{ item.label }}</span>

                    <!-- Slot Badge Kustom -->
                    <slot
                        :name="`badge-${item.key ?? item.value ?? item.id}`"
                        :item="item"
                        :is-active="(item.key ?? item.value ?? item.id) === modelValue"
                    >
                        <!-- Badge Otomatis jika ada prop count atau badge -->
                        <span
                            v-if="item.badge || (item.count !== undefined && item.count !== null)"
                            :class="[
                                'rounded-full px-2 py-0.5 text-[11px] font-bold transition-colors shrink-0',
                                getBadgeClasses(item, (item.key ?? item.value ?? item.id) === modelValue),
                            ]"
                        >
                            {{ item.badge ?? item.count }}
                        </span>
                    </slot>
                </slot>
            </button>
        </nav>
    </div>
</template>
