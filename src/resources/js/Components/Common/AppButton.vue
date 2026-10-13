<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import {
    Plus,
    Check,
    X,
    ArrowLeft,
    Download,
    Loader2,
} from 'lucide-vue-next';
import { cn } from '@/lib/utils';

const props = defineProps({
    /**
     * Tipe preset aksi tombol:
     * 'create' | 'add' | 'save' | 'submit' | 'cancel' | 'back' | 'export' | 'custom'
     */
    type: {
        type: String,
        default: 'custom',
    },
    /**
     * Label teks tombol (mengoverride label preset atau slot)
     */
    label: {
        type: String,
        default: '',
    },
    /**
     * Komponen Icon Lucide (mengoverride icon bawaan preset)
     */
    icon: {
        type: [Object, Function],
        default: null,
    },
    /**
     * Posisi icon: 'left' | 'right'
     */
    iconPosition: {
        type: String,
        default: 'left',
    },
    /**
     * Varian tombol UI:
     * 'default' (primary solid) | 'outline' | 'secondary' | 'ghost' | 'destructive' | 'link'
     */
    variant: {
        type: String,
        default: '',
    },
    /**
     * Ukuran tombol:
     * 'xs' | 'sm' (h-8 min-h-[36px]) | 'md' (h-9/10 default min-h-[38px]) | 'lg' | 'icon'
     */
    size: {
        type: String,
        default: 'md',
    },
    /**
     * URL tautan Inertia. Jika diisi, merender Inertia <Link>. Jika kosong, merender <button>.
     */
    href: {
        type: String,
        default: '',
    },
    /**
     * Atribut type button: jika true, type="submit". Default false (type="button").
     */
    submit: {
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
     * Teks alternatif saat status loading aktif
     */
    loadingText: {
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
     * Kelas kustom tambahan
     */
    class: {
        type: [String, Array, Object],
        default: '',
    },
});

defineEmits(['click']);

// Definisi preset tombol aksi utama
const PRESETS = {
    create: {
        label: 'Tambah Baru',
        icon: Plus,
        variant: 'default',
    },
    add: {
        label: 'Tambah Baru',
        icon: Plus,
        variant: 'default',
    },
    save: {
        label: 'Simpan',
        icon: Check,
        variant: 'default',
    },
    submit: {
        label: 'Simpan',
        icon: Check,
        variant: 'default',
    },
    cancel: {
        label: 'Batal',
        icon: X,
        variant: 'outline',
    },
    back: {
        label: 'Kembali',
        icon: ArrowLeft,
        variant: 'ghost',
    },
    export: {
        label: 'Ekspor Data',
        icon: Download,
        variant: 'outline',
    },
    custom: {
        label: '',
        icon: null,
        variant: 'default',
    },
};

const currentPreset = computed(() => PRESETS[props.type] || PRESETS.custom);

const resolvedLabel = computed(() => props.label || currentPreset.value.label || '');

const resolvedIcon = computed(() => props.icon || currentPreset.value.icon || null);

const resolvedVariant = computed(
    () => props.variant || currentPreset.value.variant || 'default'
);

const displayLabel = computed(() => {
    if (props.loading && props.loadingText) {
        return props.loadingText;
    }
    return resolvedLabel.value;
});

const baseClasses = computed(() => {
    const variantMap = {
        default: 'bg-primary text-primary-foreground hover:bg-primary/90 shadow-2xs',
        outline: 'border border-border bg-card shadow-2xs hover:bg-muted text-foreground',
        secondary: 'bg-secondary text-secondary-foreground hover:bg-secondary/80',
        ghost: 'hover:bg-muted text-foreground',
        destructive: 'bg-destructive text-white hover:bg-destructive/90 shadow-2xs',
        link: 'text-primary underline-offset-4 hover:underline',
    };

    const sizeMap = {
        xs: 'h-7 min-h-[32px] px-2.5 text-xs gap-1 rounded-lg',
        sm: 'h-8 min-h-[36px] px-3 text-xs gap-1.5 rounded-xl',
        md: 'h-9 sm:h-10 min-h-[38px] px-4 text-xs sm:text-sm gap-2 rounded-xl',
        lg: 'h-11 min-h-[44px] px-5 text-base gap-2.5 rounded-xl',
        icon: 'size-9 sm:size-10 min-h-[36px] min-w-[36px] p-0 rounded-xl',
    };

    return cn(
        'inline-flex items-center justify-center font-medium transition-all select-none outline-none focus-visible:ring-2 focus-visible:ring-primary-500/50 disabled:pointer-events-none disabled:opacity-50 cursor-pointer shrink-0 active:scale-[0.98]',
        variantMap[resolvedVariant.value] || variantMap.default,
        sizeMap[props.size] || sizeMap.md,
        props.class
    );
});

const iconSizeClass = computed(() => {
    if (props.size === 'xs' || props.size === 'sm') {
        return 'h-3.5 w-3.5 shrink-0';
    }
    if (props.size === 'lg') {
        return 'h-5 w-5 shrink-0';
    }
    return 'h-4 w-4 shrink-0';
});
</script>

<template>
    <!-- Navigasi Inertia Link jika memiliki prop href -->
    <Link
        v-if="href"
        :href="href"
        :class="baseClasses"
        :aria-disabled="disabled || loading ? 'true' : undefined"
    >
        <!-- Loading Spinner (kiri) -->
        <Loader2 v-if="loading && iconPosition === 'left'" :class="[iconSizeClass, 'animate-spin']" />
        <!-- Icon Kiri -->
        <component
            :is="resolvedIcon"
            v-else-if="resolvedIcon && iconPosition === 'left'"
            :stroke-width="2.2"
            :class="iconSizeClass"
        />

        <slot>{{ displayLabel }}</slot>

        <!-- Loading Spinner (kanan) -->
        <Loader2 v-if="loading && iconPosition === 'right'" :class="[iconSizeClass, 'animate-spin']" />
        <!-- Icon Kanan -->
        <component
            :is="resolvedIcon"
            v-else-if="resolvedIcon && iconPosition === 'right'"
            :stroke-width="2.2"
            :class="iconSizeClass"
        />
    </Link>

    <!-- Tombol Standar Button jika aksi event/modal/form -->
    <button
        v-else
        :type="submit ? 'submit' : 'button'"
        :disabled="disabled || loading"
        :class="baseClasses"
        @click="$emit('click', $event)"
    >
        <!-- Loading Spinner (kiri) -->
        <Loader2 v-if="loading && iconPosition === 'left'" :class="[iconSizeClass, 'animate-spin']" />
        <!-- Icon Kiri -->
        <component
            :is="resolvedIcon"
            v-else-if="resolvedIcon && iconPosition === 'left'"
            :stroke-width="2.2"
            :class="iconSizeClass"
        />

        <slot>{{ displayLabel }}</slot>

        <!-- Loading Spinner (kanan) -->
        <Loader2 v-if="loading && iconPosition === 'right'" :class="[iconSizeClass, 'animate-spin']" />
        <!-- Icon Kanan -->
        <component
            :is="resolvedIcon"
            v-else-if="resolvedIcon && iconPosition === 'right'"
            :stroke-width="2.2"
            :class="iconSizeClass"
        />
    </button>
</template>
