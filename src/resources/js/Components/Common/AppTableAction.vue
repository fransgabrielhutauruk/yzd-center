<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import {
    Eye,
    Edit2,
    Trash2,
    UserCheck,
    CheckCircle2,
    ArrowRight,
    UserX,
    Loader2,
} from 'lucide-vue-next';
import { cn } from '@/lib/utils';

const props = defineProps({
    /**
     * Tipe preset aksi tabel:
     * 'view' | 'detail' | 'edit' | 'delete' | 'verify' | 'approve' | 'process' | 'disable' | 'activate' | 'custom'
     */
    type: {
        type: String,
        default: 'custom',
    },
    /**
     * Label teks tombol (mengoverride label bawaan preset).
     */
    label: {
        type: String,
        default: '',
    },
    /**
     * Komponen Icon Lucide (mengoverride icon bawaan preset).
     */
    icon: {
        type: [Object, Function],
        default: null,
    },
    /**
     * Menampilkan hanya ikon (default: true).
     * Ketika true, label tetap digunakan untuk title browser & aria-label aksesibilitas.
     */
    iconOnly: {
        type: Boolean,
        default: true,
    },
    /**
     * Tooltip / Title browser kustom (fallback otomatis ke label).
     */
    title: {
        type: String,
        default: '',
    },
    /**
     * Varian tombol UI:
     * 'outline' | 'ghost' | 'destructive' | 'default' | 'secondary'
     */
    variant: {
        type: String,
        default: '',
    },
    /**
     * Ukuran tombol:
     * 'sm' (default, min-h-[36px] touch target aman mobile-first) | 'xs' (min-h-[32px])
     */
    size: {
        type: String,
        default: 'sm',
    },
    /**
     * URL tautan Inertia. Jika diisi, merender Inertia <Link>. Jika kosong, merender <button>.
     */
    href: {
        type: String,
        default: '',
    },
    /**
     * Status nonaktif
     */
    disabled: {
        type: Boolean,
        default: false,
    },
    /**
     * Status memuat / loading spinner
     */
    loading: {
        type: Boolean,
        default: false,
    },
    /**
     * Kelas kustom tambahan
     */
    class: {
        type: [String, Array, Object],
        default: '',
    },
});

defineEmits(['click']);

// Palet warna soft pastel tinted (sesuai referensi UI)
const COLOR_MAP = {
    amber: 'bg-amber-100/80 text-amber-600 hover:bg-amber-200/80 hover:text-amber-700 dark:bg-amber-950/40 dark:text-amber-400 dark:hover:bg-amber-900/60 dark:hover:text-amber-300',
    blue: 'bg-blue-100/80 text-blue-600 hover:bg-blue-200/80 hover:text-blue-700 dark:bg-blue-950/40 dark:text-blue-400 dark:hover:bg-blue-900/60 dark:hover:text-blue-300',
    sky: 'bg-sky-100/80 text-sky-600 hover:bg-sky-200/80 hover:text-sky-700 dark:bg-sky-950/40 dark:text-sky-400 dark:hover:bg-sky-900/60 dark:hover:text-sky-300',
    rose: 'bg-rose-100/80 text-rose-600 hover:bg-rose-200/80 hover:text-rose-700 dark:bg-rose-950/40 dark:text-rose-400 dark:hover:bg-rose-900/60 dark:hover:text-rose-300',
    emerald: 'bg-emerald-100/80 text-emerald-600 hover:bg-emerald-200/80 hover:text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 dark:hover:bg-emerald-900/60 dark:hover:text-emerald-300',
    primary: 'bg-primary-100/80 text-primary-700 hover:bg-primary-200/80 hover:text-primary-800 dark:bg-primary-950/40 dark:text-primary-300 dark:hover:bg-primary-900/60 dark:hover:text-primary-200',
    slate: 'bg-slate-100/80 text-slate-600 hover:bg-slate-200/80 hover:text-slate-800 dark:bg-slate-800/60 dark:text-slate-300 dark:hover:bg-slate-700/60 dark:hover:text-slate-100',
};

// Definisi preset bawaan
const PRESETS = {
    view: {
        label: 'Detail',
        icon: Eye,
        colorKey: 'blue',
    },
    detail: {
        label: 'Detail',
        icon: Eye,
        colorKey: 'blue',
    },
    edit: {
        label: 'Edit',
        icon: Edit2,
        colorKey: 'amber',
    },
    delete: {
        label: 'Hapus',
        icon: Trash2,
        colorKey: 'rose',
    },
    verify: {
        label: 'Tinjau',
        icon: UserCheck,
        colorKey: 'blue',
    },
    approve: {
        label: 'Setujui',
        icon: CheckCircle2,
        colorKey: 'emerald',
    },
    process: {
        label: 'Proses',
        icon: ArrowRight,
        colorKey: 'primary',
    },
    disable: {
        label: 'Nonaktifkan',
        icon: UserX,
        colorKey: 'rose',
    },
    activate: {
        label: 'Aktifkan',
        icon: UserCheck,
        colorKey: 'emerald',
    },
    custom: {
        label: '',
        icon: null,
        colorKey: 'slate',
    },
};

const currentPreset = computed(() => PRESETS[props.type] || PRESETS.custom);

const resolvedLabel = computed(() => props.label || currentPreset.value.label || '');

const resolvedTitle = computed(
    () => props.title || resolvedLabel.value || (props.type !== 'custom' ? currentPreset.value.label : '')
);

const resolvedIcon = computed(() => props.icon || currentPreset.value.icon || null);

const resolvedVariant = computed(() => props.variant || 'soft');

const resolvedColorClass = computed(() => {
    if (props.color && COLOR_MAP[props.color]) {
        return COLOR_MAP[props.color];
    }
    const colorKey = currentPreset.value.colorKey || 'slate';
    return COLOR_MAP[colorKey] || COLOR_MAP.slate;
});

const baseClasses = computed(() => {
    // Varian dasar
    const variantMap = {
        soft: resolvedColorClass.value,
        outline: 'border border-border bg-card shadow-2xs hover:bg-muted text-foreground',
        ghost: 'hover:bg-muted text-foreground',
        destructive:
            'border border-destructive/20 bg-card hover:bg-destructive/10 text-destructive dark:border-border dark:hover:bg-destructive/20',
        default: 'bg-primary text-primary-foreground hover:bg-primary/90 shadow-2xs',
        secondary: 'bg-secondary text-secondary-foreground hover:bg-secondary/80',
    };

    const sizeClasses = props.iconOnly
        ? props.size === 'xs'
            ? 'h-7 w-7 min-h-[32px] min-w-[32px] p-0'
            : 'h-8 w-8 min-h-[36px] min-w-[36px] p-0'
        : props.size === 'xs'
          ? 'h-7 min-h-[32px] px-2 py-1 text-xs gap-1'
          : 'h-8 min-h-[36px] px-2.5 py-1.5 text-xs gap-1.5';

    return cn(
        'inline-flex items-center justify-center font-medium transition-all rounded-xl select-none outline-none focus-visible:ring-2 focus-visible:ring-primary-500/50 disabled:pointer-events-none disabled:opacity-50 cursor-pointer shrink-0 border border-transparent',
        variantMap[resolvedVariant.value] || variantMap.soft,
        sizeClasses,
        props.class
    );
});
</script>

<template>
    <!-- Navigasi Inertia Link jika memiliki prop href -->
    <Link
        v-if="href"
        :href="href"
        :class="baseClasses"
        :title="resolvedTitle"
        :aria-label="resolvedTitle"
    >
        <Loader2 v-if="loading" class="h-3.5 w-3.5 animate-spin" />
        <component
            :is="resolvedIcon"
            v-else-if="resolvedIcon"
            :stroke-width="2.2"
            :class="iconOnly ? 'h-4 w-4' : 'h-3.5 w-3.5'"
        />
        <span v-if="!iconOnly && resolvedLabel">{{ resolvedLabel }}</span>
        <slot />
    </Link>

    <!-- Tombol Standar Button jika aksi event/modal -->
    <button
        v-else
        type="button"
        :disabled="disabled || loading"
        :class="baseClasses"
        :title="resolvedTitle"
        :aria-label="resolvedTitle"
        @click="$emit('click', $event)"
    >
        <Loader2 v-if="loading" class="h-3.5 w-3.5 animate-spin" />
        <component
            :is="resolvedIcon"
            v-else-if="resolvedIcon"
            :stroke-width="2.2"
            :class="iconOnly ? 'h-4 w-4' : 'h-3.5 w-3.5'"
        />
        <span v-if="!iconOnly && resolvedLabel">{{ resolvedLabel }}</span>
        <slot />
    </button>
</template>
