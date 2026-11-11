<script setup>
import { ref, computed, watch, nextTick, onMounted } from 'vue';

const props = defineProps({
    modelValue: {
        type: [Number, String, null],
        default: null,
    },
    currency: {
        type: String,
        default: 'IDR',
        validator: (val) => ['IDR', 'USD'].includes(val.toUpperCase()),
    },
    prefix: {
        type: [String, Boolean],
        default: 'auto',
    },
    placeholder: {
        type: String,
        default: '',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    required: {
        type: Boolean,
        default: false,
    },
    min: {
        type: Number,
        default: undefined,
    },
    max: {
        type: Number,
        default: undefined,
    },
    allowNegative: {
        type: Boolean,
        default: false,
    },
    allowDecimal: {
        type: Boolean,
        default: undefined,
    },
    size: {
        type: String,
        default: 'md',
        validator: (val) => ['sm', 'md', 'lg'].includes(val),
    },
    hasError: {
        type: Boolean,
        default: false,
    },
    inputClass: {
        type: [String, Array, Object],
        default: '',
    },
    id: {
        type: String,
        default: undefined,
    },
    name: {
        type: String,
        default: undefined,
    },
});

const emit = defineEmits(['update:modelValue', 'input', 'change', 'blur', 'focus']);

const inputRef = ref(null);
const isFocused = ref(false);

const normalizedCurrency = computed(() => props.currency.toUpperCase());

const shouldAllowDecimal = computed(() => {
    if (props.allowDecimal !== undefined) {
        return props.allowDecimal;
    }
    return normalizedCurrency.value === 'USD';
});

const thousandSep = computed(() => (normalizedCurrency.value === 'IDR' ? '.' : ','));
const decimalSep = computed(() => (normalizedCurrency.value === 'IDR' ? ',' : '.'));

const activePrefix = computed(() => {
    if (props.prefix === false || props.prefix === '') {
        return '';
    }
    if (props.prefix === 'auto' || props.prefix === true || props.prefix === undefined) {
        return normalizedCurrency.value === 'IDR' ? 'Rp' : '$';
    }
    return props.prefix;
});

// Format internal untuk tampilan
const parseAndFormat = (inputVal) => {
    if (inputVal === '' || inputVal === null || inputVal === undefined) {
        return { display: '', value: null };
    }

    let str = inputVal.toString().trim();
    const isNegative = props.allowNegative && str.startsWith('-');
    if (isNegative) {
        str = str.substring(1);
    }

    const tSep = thousandSep.value;
    const dSep = decimalSep.value;

    // Bersihkan thousand separator lama
    const regexThousand = new RegExp('\\' + tSep, 'g');
    str = str.replace(regexThousand, '');

    // Konversi separator alternatif jika pengguna mengetik '.' untuk IDR atau ',' untuk USD
    const altDecimal = dSep === '.' ? ',' : '.';
    str = str.replace(altDecimal, dSep);

    // Filter hanya digit dan satu desimal separator
    let cleanStr = '';
    let hasDecimal = false;
    for (let i = 0; i < str.length; i++) {
        const char = str[i];
        if (char >= '0' && char <= '9') {
            cleanStr += char;
        } else if (char === dSep && shouldAllowDecimal.value && !hasDecimal) {
            cleanStr += char;
            hasDecimal = true;
        }
    }

    if (cleanStr === '') {
        return { display: isNegative ? '-' : '', value: null };
    }

    // Pisah bagian integer dan desimal
    const parts = cleanStr.split(dSep);
    let intPart = parts[0] || '0';
    const decPart = parts.length > 1 ? parts[1] : null;

    // Hilangkan leading zero kecuali jika murni "0"
    if (intPart.length > 1 && intPart.startsWith('0')) {
        intPart = intPart.replace(/^0+/, '') || '0';
    }

    // Format ribuan
    const formattedInt = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, tSep);

    let display = formattedInt;
    if (hasDecimal) {
        display += dSep + (decPart !== null ? decPart : '');
    }
    if (isNegative) {
        display = '-' + display;
    }

    // Kalkulasi nilai angka murni (Number)
    const numStr = (isNegative ? '-' : '') + (intPart || '0') + (hasDecimal && decPart !== null && decPart !== '' ? '.' + decPart : (hasDecimal ? '.0' : ''));
    const parsedNum = parseFloat(numStr);
    const validNum = isNaN(parsedNum) ? null : parsedNum;

    return { display, value: validNum };
};

const displayValue = ref('');

const syncFromModel = (val) => {
    if (val === null || val === undefined || val === '') {
        displayValue.value = '';
        return;
    }
    const { display } = parseAndFormat(val);
    displayValue.value = display;
};

// Pantau perubahan modelValue dari luar
watch(
    () => props.modelValue,
    (newVal) => {
        // Jangan timpa format jika sedang fokus dan nilai numeriknya identik
        // agar tidak mengganggu pengetikan trailing decimal (".") atau trailing zero
        if (isFocused.value && inputRef.value) {
            const currentNumeric = parseAndFormat(inputRef.value.value).value;
            if (currentNumeric === newVal) {
                return;
            }
        }
        syncFromModel(newVal);
    },
    { immediate: true }
);

watch(
    () => props.currency,
    () => {
        syncFromModel(props.modelValue);
    }
);

onMounted(() => {
    syncFromModel(props.modelValue);
});

// Hitung posisi kursor saat mengetik agar tidak melompat ke ujung
const calculateNewCursor = (rawVal, formattedVal, oldCursor) => {
    const dSep = decimalSep.value;
    let sigCount = 0;
    for (let i = 0; i < oldCursor && i < rawVal.length; i++) {
        const c = rawVal[i];
        if ((c >= '0' && c <= '9') || c === '-' || c === dSep) {
            sigCount++;
        }
    }

    let passedSig = 0;
    let newCursor = formattedVal.length;
    for (let i = 0; i < formattedVal.length; i++) {
        if (passedSig === sigCount) {
            newCursor = i;
            break;
        }
        const c = formattedVal[i];
        if ((c >= '0' && c <= '9') || c === '-' || c === dSep) {
            passedSig++;
        }
        if (passedSig === sigCount) {
            newCursor = i + 1;
            break;
        }
    }
    return newCursor;
};

const handleInput = (event) => {
    const rawVal = event.target.value;
    const oldCursor = event.target.selectionStart;

    const { display, value } = parseAndFormat(rawVal);
    displayValue.value = display;

    // Emit nilai numerik murni ke parent
    emit('update:modelValue', value);
    emit('input', value, event);

    nextTick(() => {
        if (inputRef.value && oldCursor !== null) {
            const newCursor = calculateNewCursor(rawVal, display, oldCursor);
            inputRef.value.setSelectionRange(newCursor, newCursor);
        }
    });
};

const handleFocus = (event) => {
    isFocused.value = true;
    emit('focus', event);
};

const handleBlur = (event) => {
    isFocused.value = false;

    // Validasi min/max saat blur jika didefinisikan dan input terisi
    if (props.modelValue !== null && props.modelValue !== undefined && props.modelValue !== '') {
        let finalVal = Number(props.modelValue);
        if (props.min !== undefined && finalVal < props.min) {
            finalVal = props.min;
            emit('update:modelValue', finalVal);
        }
        if (props.max !== undefined && finalVal > props.max) {
            finalVal = props.max;
            emit('update:modelValue', finalVal);
        }
        syncFromModel(finalVal);
    }

    emit('blur', event);
    emit('change', event);
};

defineExpose({
    focus: () => inputRef.value?.focus(),
    blur: () => inputRef.value?.blur(),
    inputRef,
});
</script>

<template>
    <div class="relative w-full">
        <span
            v-if="activePrefix"
            class="pointer-events-none absolute inset-y-0 left-0 flex items-center select-none font-mono font-bold text-muted-foreground"
            :class="[
                size === 'lg' ? 'pl-3.5 text-sm' : 'pl-3 text-xs',
            ]"
        >
            {{ activePrefix }}
        </span>

        <input
            :id="id"
            ref="inputRef"
            type="text"
            inputmode="decimal"
            :name="name"
            :value="displayValue"
            :placeholder="placeholder"
            :disabled="disabled"
            :required="required"
            @input="handleInput"
            @focus="handleFocus"
            @blur="handleBlur"
            class="w-full border bg-background font-mono font-bold text-foreground transition-colors focus:outline-none focus:ring-2 disabled:cursor-not-allowed disabled:opacity-50"
            :class="[
                size === 'lg' ? 'h-10 text-sm rounded-xl' : size === 'sm' ? 'h-8 text-xs rounded-lg' : 'h-9 text-xs rounded-lg',
                hasError
                    ? 'border-destructive focus:ring-destructive text-destructive'
                    : 'border-input focus:ring-primary',
                activePrefix
                    ? activePrefix.length >= 2
                        ? size === 'lg' ? 'pl-10 pr-3.5' : 'pl-9 pr-3'
                        : size === 'lg' ? 'pl-8 pr-3.5' : 'pl-7 pr-3'
                    : size === 'lg' ? 'px-3.5' : 'px-3',
                inputClass,
            ]"
        />
    </div>
</template>
