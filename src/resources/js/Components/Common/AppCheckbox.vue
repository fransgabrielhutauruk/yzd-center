<script setup>
import { computed, ref, watch, useId } from 'vue';
import { Check, Minus } from 'lucide-vue-next';

defineOptions({
    inheritAttrs: false,
});

const props = defineProps({
    modelValue: {
        type: [Boolean, Array, String, Number],
        default: undefined,
    },
    checked: {
        type: Boolean,
        default: undefined,
    },
    value: {
        type: [String, Number, Boolean, Object],
        default: null,
    },
    trueValue: {
        type: [Boolean, String, Number],
        default: true,
    },
    falseValue: {
        type: [Boolean, String, Number],
        default: false,
    },
    indeterminate: {
        type: Boolean,
        default: false,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    required: {
        type: Boolean,
        default: false,
    },
    id: {
        type: String,
        default: null,
    },
    name: {
        type: String,
        default: null,
    },
    label: {
        type: String,
        default: '',
    },
    description: {
        type: String,
        default: '',
    },
    size: {
        type: String,
        default: 'sm',
        validator: (v) => ['sm', 'md'].includes(v),
    },
    align: {
        type: String,
        default: 'center',
        validator: (v) => ['center', 'start'].includes(v),
    },
    ariaLabel: {
        type: String,
        default: null,
    },
});

const emit = defineEmits(['update:modelValue', 'update:checked', 'change']);

const generatedId = typeof useId === 'function' ? useId() : `checkbox-${Math.random().toString(36).substring(2, 9)}`;
const inputId = computed(() => props.id || generatedId);

const inputRef = ref(null);

const isChecked = computed(() => {
    if (props.checked !== undefined) {
        return Boolean(props.checked);
    }
    if (Array.isArray(props.modelValue)) {
        return props.modelValue.includes(props.value);
    }
    return props.modelValue === props.trueValue;
});

watch(
    () => props.indeterminate,
    (val) => {
        if (inputRef.value) {
            inputRef.value.indeterminate = Boolean(val);
        }
    },
    { immediate: true, flush: 'post' }
);

const handleChange = (event) => {
    if (props.disabled) return;

    const targetChecked = event.target.checked;

    if (props.checked !== undefined && props.modelValue === undefined) {
        emit('update:checked', targetChecked);
        emit('change', event);
        return;
    }

    if (Array.isArray(props.modelValue)) {
        const current = [...props.modelValue];
        const val = props.value;
        if (targetChecked) {
            if (!current.includes(val)) {
                current.push(val);
            }
        } else {
            const idx = current.indexOf(val);
            if (idx > -1) {
                current.splice(idx, 1);
            }
        }
        emit('update:modelValue', current);
        emit('update:checked', targetChecked);
        emit('change', event);
        return;
    }

    const nextValue = targetChecked ? props.trueValue : props.falseValue;
    emit('update:modelValue', nextValue);
    emit('update:checked', targetChecked);
    emit('change', event);
};
</script>

<template>
    <label
        :for="inputId"
        :class="[
            'group relative inline-flex select-none align-middle transition-colors',
            align === 'start' ? 'items-start' : 'items-center',
            disabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer',
            (label || $slots.default || description || $slots.description) ? 'gap-2.5' : 'justify-center',
            $attrs.class,
        ]"
    >
        <!-- Native Hidden Checkbox for full keyboard, form submission, and a11y support -->
        <input
            :id="inputId"
            ref="inputRef"
            type="checkbox"
            :name="name"
            :checked="isChecked"
            :disabled="disabled"
            :required="required"
            :aria-checked="indeterminate ? 'mixed' : isChecked"
            :aria-label="ariaLabel || label || undefined"
            v-bind="{ ...$attrs, class: undefined }"
            class="peer sr-only"
            @change="handleChange"
        />

        <!-- Custom Styled Box -->
        <span
            :class="[
                'shrink-0 flex items-center justify-center border transition-all duration-150',
                'peer-focus-visible:ring-2 peer-focus-visible:ring-primary-500/30 peer-focus-visible:ring-offset-2 peer-focus-visible:ring-offset-background',
                size === 'md' ? 'h-5 w-5 rounded-[5px]' : 'h-4 w-4 rounded-[4px]',
                align === 'start' ? 'mt-0.5' : '',
                (isChecked || indeterminate)
                    ? 'bg-primary-600 border-primary-600 text-white shadow-2xs dark:bg-primary-500 dark:border-primary-500'
                    : 'bg-background border-input hover:border-muted-foreground/40 dark:bg-card dark:border-border',
            ]"
        >
            <Minus
                v-if="indeterminate"
                :class="size === 'md' ? 'h-3.5 w-3.5 stroke-[3]' : 'h-2.5 w-2.5 stroke-[3]'"
            />
            <Check
                v-else-if="isChecked"
                :class="size === 'md' ? 'h-3.5 w-3.5 stroke-[3]' : 'h-2.5 w-2.5 stroke-[3]'"
            />
        </span>

        <!-- Label / Content -->
        <div
            v-if="label || $slots.default || description || $slots.description"
            class="flex flex-col text-left"
        >
            <span
                v-if="label || $slots.default"
                :class="[
                    'font-medium text-foreground transition-colors',
                    size === 'md' ? 'text-sm' : 'text-xs',
                    !disabled && 'group-hover:text-foreground',
                ]"
            >
                <slot>{{ label }}</slot>
            </span>
            <span
                v-if="description || $slots.description"
                :class="[
                    'text-muted-foreground mt-0.5 leading-normal',
                    size === 'md' ? 'text-xs' : 'text-[11px]',
                ]"
            >
                <slot name="description">{{ description }}</slot>
            </span>
        </div>
    </label>
</template>
