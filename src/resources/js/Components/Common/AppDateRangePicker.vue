<script>
const MONTHS_FULL = [
    'Januari',
    'Februari',
    'Maret',
    'April',
    'Mei',
    'Juni',
    'Juli',
    'Agustus',
    'September',
    'Oktober',
    'November',
    'Desember',
];

const MONTHS_SHORT = [
    'Jan',
    'Feb',
    'Mar',
    'Apr',
    'Mei',
    'Jun',
    'Jul',
    'Agu',
    'Sep',
    'Okt',
    'Nov',
    'Des',
];

const WEEKDAYS_SHORT = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];

function getDaysInMonth(year, month) {
    if (typeof globalThis.Temporal !== 'undefined') {
        return globalThis.Temporal.PlainYearMonth.from({ year, month, calendar: 'iso8601' }).daysInMonth;
    }
    return new Date(Date.UTC(year, month, 0)).getUTCDate();
}

function getFirstWeekdayMon0(year, month) {
    if (typeof globalThis.Temporal !== 'undefined') {
        return globalThis.Temporal.PlainDate.from({ year, month, day: 1 }).dayOfWeek - 1;
    }
    return (new Date(Date.UTC(year, month - 1, 1)).getUTCDay() + 6) % 7;
}

export function parseIsoDate(iso) {
    if (typeof iso !== 'string') return null;
    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(iso.trim());
    if (!match) return null;

    const year = Number(match[1]);
    const month = Number(match[2]);
    const day = Number(match[3]);

    if (month < 1 || month > 12) return null;
    const maxDay = getDaysInMonth(year, month);
    if (day < 1 || day > maxDay) return null;

    return { year, month, day };
}

export function formatPartsToIso(year, month, day) {
    return `${String(year).padStart(4, '0')}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
}

export function formatIsoDateShort(iso, includeYear = true) {
    const parts = parseIsoDate(iso);
    if (!parts) return '';

    const dayStr = String(parts.day).padStart(2, '0');
    const monthStr = MONTHS_SHORT[parts.month - 1] || '';

    return includeYear ? `${dayStr} ${monthStr} ${parts.year}` : `${dayStr} ${monthStr}`;
}

export function formatDateRangeLabel(startDate, endDate, fallback = '') {
    const start = parseIsoDate(startDate);
    const end = parseIsoDate(endDate);

    if (start && end) {
        if (startDate === endDate) {
            return formatIsoDateShort(startDate, true);
        }
        if (start.year === end.year) {
            return `${formatIsoDateShort(startDate, false)} – ${formatIsoDateShort(endDate, true)}`;
        }
        return `${formatIsoDateShort(startDate, true)} – ${formatIsoDateShort(endDate, true)}`;
    }

    if (start) {
        return `Mulai ${formatIsoDateShort(startDate, true)}`;
    }

    if (end) {
        return `s/d ${formatIsoDateShort(endDate, true)}`;
    }

    return fallback;
}
</script>

<script setup>
import { ref, computed, watch } from 'vue';
import { PopoverRoot, PopoverTrigger, PopoverPortal, PopoverContent } from 'reka-ui';
import { Calendar, ChevronLeft, ChevronRight, Check, X } from 'lucide-vue-next';
import AppButton from '@/Components/Common/AppButton.vue';
import { cn } from '@/lib/utils';

const props = defineProps({
    startDate: {
        type: String,
        default: '',
    },
    endDate: {
        type: String,
        default: '',
    },
    maxDate: {
        type: String,
        default: '',
    },
    minDate: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: 'Pilih tanggal',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    align: {
        type: String,
        default: 'end',
    },
    clearable: {
        type: Boolean,
        default: true,
    },
    class: {
        type: [String, Array, Object],
        default: '',
    },
});

const emit = defineEmits(['update:startDate', 'update:endDate', 'apply']);

const isOpen = ref(false);
const draftStart = ref('');
const draftEnd = ref('');
const hoveredDate = ref('');

const localTodayIso = computed(() => {
    if (typeof globalThis.Temporal !== 'undefined') {
        return globalThis.Temporal.Now.plainDateISO().toString();
    }
    const now = new Date();
    return formatPartsToIso(now.getFullYear(), now.getMonth() + 1, now.getDate());
});

const todayIso = computed(() => {
    return parseIsoDate(props.maxDate) ? props.maxDate : localTodayIso.value;
});

const effectiveMaxIso = computed(() => todayIso.value);
const effectiveMinIso = computed(() => (parseIsoDate(props.minDate) ? props.minDate : ''));

const maxParts = computed(() => parseIsoDate(effectiveMaxIso.value));
const minParts = computed(() => parseIsoDate(effectiveMinIso.value));

const initialViewParts = computed(() => {
    return (
        parseIsoDate(props.startDate) ||
        parseIsoDate(props.endDate) ||
        maxParts.value || { year: 2026, month: 1, day: 1 }
    );
});

const viewYear = ref(initialViewParts.value.year);
const viewMonth = ref(initialViewParts.value.month);

const syncViewToSelectionOrMax = () => {
    const target =
        parseIsoDate(draftStart.value) ||
        parseIsoDate(draftEnd.value) ||
        maxParts.value;

    if (!target) return;

    viewYear.value = target.year;
    viewMonth.value = target.month;

    if (maxParts.value) {
        if (viewYear.value > maxParts.value.year) {
            viewYear.value = maxParts.value.year;
            viewMonth.value = maxParts.value.month;
        } else if (viewYear.value === maxParts.value.year && viewMonth.value > maxParts.value.month) {
            viewMonth.value = maxParts.value.month;
        }
    }
};

watch(isOpen, (opened) => {
    if (opened) {
        draftStart.value = parseIsoDate(props.startDate) ? props.startDate : '';
        draftEnd.value = parseIsoDate(props.endDate) ? props.endDate : '';
        hoveredDate.value = '';
        syncViewToSelectionOrMax();
    }
});

const hasCommittedValue = computed(() => Boolean(parseIsoDate(props.startDate) || parseIsoDate(props.endDate)));

const triggerLabel = computed(() =>
    formatDateRangeLabel(props.startDate, props.endDate, props.placeholder)
);

const yearOptions = computed(() => {
    const maxY = maxParts.value?.year ?? initialViewParts.value.year;
    const minCandidate = Math.min(
        2020,
        maxY - 5,
        parseIsoDate(props.startDate)?.year ?? maxY,
        parseIsoDate(props.endDate)?.year ?? maxY
    );
    const minY = minParts.value ? Math.max(minParts.value.year, minCandidate) : minCandidate;

    const years = [];
    for (let y = minY; y <= maxY; y++) {
        years.push(y);
    }
    return years;
});

const monthOptions = computed(() => {
    return MONTHS_FULL.map((label, idx) => {
        const month = idx + 1;
        const afterMax =
            maxParts.value && viewYear.value === maxParts.value.year && month > maxParts.value.month;
        const beforeMin =
            minParts.value && viewYear.value === minParts.value.year && month < minParts.value.month;

        return {
            value: month,
            label,
            disabled: Boolean(afterMax || beforeMin),
        };
    });
});

const canGoPrevMonth = computed(() => {
    const firstYear = yearOptions.value[0] ?? viewYear.value;
    if (minParts.value) {
        return (
            viewYear.value > minParts.value.year ||
            (viewYear.value === minParts.value.year && viewMonth.value > minParts.value.month)
        );
    }
    return viewYear.value > firstYear || (viewYear.value === firstYear && viewMonth.value > 1);
});

const canGoNextMonth = computed(() => {
    if (!maxParts.value) return true;
    return (
        viewYear.value < maxParts.value.year ||
        (viewYear.value === maxParts.value.year && viewMonth.value < maxParts.value.month)
    );
});

const goToPrevMonth = () => {
    if (!canGoPrevMonth.value) return;
    if (viewMonth.value === 1) {
        viewYear.value -= 1;
        viewMonth.value = 12;
    } else {
        viewMonth.value -= 1;
    }
};

const goToNextMonth = () => {
    if (!canGoNextMonth.value) return;
    if (viewMonth.value === 12) {
        viewYear.value += 1;
        viewMonth.value = 1;
    } else {
        viewMonth.value += 1;
    }
};

const handleYearChange = (e) => {
    const nextYear = Number(e.target.value);
    viewYear.value = nextYear;

    if (maxParts.value && nextYear === maxParts.value.year && viewMonth.value > maxParts.value.month) {
        viewMonth.value = maxParts.value.month;
    }
    if (minParts.value && nextYear === minParts.value.year && viewMonth.value < minParts.value.month) {
        viewMonth.value = minParts.value.month;
    }
};

const handleMonthChange = (e) => {
    viewMonth.value = Number(e.target.value);
};

const leadingSpacerCount = computed(() => getFirstWeekdayMon0(viewYear.value, viewMonth.value));

const previewRange = computed(() => {
    if (draftStart.value && draftEnd.value) {
        return { start: draftStart.value, end: draftEnd.value, isPreview: false };
    }
    if (draftStart.value && hoveredDate.value) {
        if (hoveredDate.value < draftStart.value) {
            return { start: hoveredDate.value, end: draftStart.value, isPreview: true };
        }
        return { start: draftStart.value, end: hoveredDate.value, isPreview: true };
    }
    if (draftStart.value) {
        return { start: draftStart.value, end: draftStart.value, isPreview: false };
    }
    if (draftEnd.value) {
        return { start: draftEnd.value, end: draftEnd.value, isPreview: false };
    }
    return { start: '', end: '', isPreview: false };
});

const calendarDays = computed(() => {
    const totalDays = getDaysInMonth(viewYear.value, viewMonth.value);
    const { start, end } = previewRange.value;
    const days = [];

    for (let day = 1; day <= totalDays; day++) {
        const iso = formatPartsToIso(viewYear.value, viewMonth.value, day);
        const isAfterMax = Boolean(effectiveMaxIso.value && iso > effectiveMaxIso.value);
        const isBeforeMin = Boolean(effectiveMinIso.value && iso < effectiveMinIso.value);
        const isDisabled = isAfterMax || isBeforeMin;

        const isDraftStart = Boolean(draftStart.value && iso === draftStart.value);
        const isDraftEnd = Boolean(draftEnd.value && iso === draftEnd.value);
        const isEndpoint = Boolean(start && end && (iso === start || iso === end));
        const isRangeStart = Boolean(start && end && start !== end && iso === start);
        const isRangeEnd = Boolean(start && end && start !== end && iso === end);
        const isInRange = Boolean(start && end && start < iso && iso < end);
        const isToday = iso === todayIso.value;
        const fullLabel = `${day} ${MONTHS_FULL[viewMonth.value - 1]} ${viewYear.value}`;

        days.push({
            day,
            iso,
            fullLabel,
            isDisabled,
            isDraftStart,
            isDraftEnd,
            isEndpoint,
            isRangeStart,
            isRangeEnd,
            isInRange,
            isToday,
        });
    }

    return days;
});

const selectDay = (day) => {
    if (day.isDisabled) return;

    const iso = day.iso;
    hoveredDate.value = '';

    // Klik 1 (saat kosong, kedua endpoint terisi, atau hanya end yang terisi dari URL manual)
    if (!draftStart.value || (draftStart.value && draftEnd.value)) {
        draftStart.value = iso;
        draftEnd.value = '';
        return;
    }

    // Klik 2 (saat baru draftStart terisi)
    if (iso === draftStart.value) {
        draftEnd.value = iso;
    } else if (iso < draftStart.value) {
        draftEnd.value = draftStart.value;
        draftStart.value = iso;
    } else {
        draftEnd.value = iso;
    }
};

const selectionSummaryText = computed(() => {
    if (draftStart.value && draftEnd.value) {
        if (draftStart.value === draftEnd.value) {
            return formatIsoDateShort(draftStart.value, true);
        }
        return `${formatIsoDateShort(draftStart.value, true)} – ${formatIsoDateShort(draftEnd.value, true)}`;
    }
    if (draftStart.value) {
        return `${formatIsoDateShort(draftStart.value, true)} (klik tanggal lain untuk rentang)`;
    }
    if (draftEnd.value) {
        return `s/d ${formatIsoDateShort(draftEnd.value, true)}`;
    }
    return 'Pilih 1 tanggal atau rentang tanggal';
});

const canApply = computed(() => Boolean(draftStart.value || draftEnd.value));

const commitSelection = (start, end) => {
    emit('update:startDate', start);
    emit('update:endDate', end);
    emit('apply', { startDate: start, endDate: end });
};

const handleApply = () => {
    if (!canApply.value) return;

    const start = draftStart.value ? draftStart.value : '';
    const end = draftStart.value ? draftEnd.value || draftStart.value : draftEnd.value;

    commitSelection(start, end);
    isOpen.value = false;
};

const handleReset = () => {
    draftStart.value = '';
    draftEnd.value = '';
    hoveredDate.value = '';

    if (props.clearable && hasCommittedValue.value) {
        commitSelection('', '');
        isOpen.value = false;
    }
};

const handleInlineClear = () => {
    if (props.disabled) return;
    draftStart.value = '';
    draftEnd.value = '';
    hoveredDate.value = '';
    commitSelection('', '');
    isOpen.value = false;
};

const dayButtonClass = (day) => {
    if (day.isDisabled) {
        return 'cursor-not-allowed rounded-lg text-slate-300 dark:text-slate-600';
    }

    if (day.isEndpoint) {
        const radius = day.isRangeStart
            ? 'rounded-l-lg rounded-r-none'
            : day.isRangeEnd
              ? 'rounded-r-lg rounded-l-none'
              : 'rounded-lg';

        return cn(
            radius,
            'bg-primary-600 font-semibold text-white hover:bg-primary-700 dark:bg-primary-500 dark:text-white dark:hover:bg-primary-400'
        );
    }

    if (day.isInRange) {
        return 'rounded-none bg-primary-100 text-primary-900 hover:bg-primary-200/80 dark:bg-primary-900/50 dark:text-primary-100 dark:hover:bg-primary-800/60';
    }

    if (day.isToday) {
        return 'rounded-lg font-semibold text-primary-700 ring-1 ring-inset ring-primary-500/70 hover:bg-slate-100 dark:text-primary-300 dark:hover:bg-slate-800';
    }

    return 'rounded-lg text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800';
};
</script>

<template>
    <div :class="cn('relative flex items-center', props.class)">
        <PopoverRoot v-model:open="isOpen">
            <PopoverTrigger as-child>
                <button
                    type="button"
                    :disabled="disabled"
                    :aria-label="hasCommittedValue ? `Filter tanggal: ${triggerLabel}` : placeholder"
                    class="focus:border-primary-500 focus:ring-primary-500/20 dark:focus:border-primary-400 flex h-10 min-h-[40px] w-full cursor-pointer items-center gap-2 rounded-xl border pl-3 text-left text-sm transition-colors focus:ring-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                    :class="[
                        hasCommittedValue
                            ? [
                                  'border-primary-300 bg-primary-50/40 text-slate-800 font-medium dark:border-primary-700 dark:bg-primary-950/30 dark:text-slate-100',
                                  clearable ? 'pr-9' : 'pr-3',
                              ]
                            : 'border-slate-200 bg-slate-50/60 pr-3 text-slate-400 hover:bg-slate-100/70 dark:border-slate-700 dark:bg-slate-800/80 dark:text-slate-400 dark:hover:bg-slate-800',
                    ]"
                >
                    <Calendar
                        class="h-4 w-4 shrink-0"
                        :class="
                            hasCommittedValue
                                ? 'text-primary-600 dark:text-primary-400'
                                : 'text-slate-400 dark:text-slate-500'
                        "
                        aria-hidden="true"
                    />
                    <span class="min-w-0 flex-1 truncate">{{ triggerLabel }}</span>
                </button>
            </PopoverTrigger>

            <PopoverPortal>
                <PopoverContent
                    :align="align"
                    side="bottom"
                    :side-offset="8"
                    :collision-padding="12"
                    :avoid-collisions="true"
                    class="z-[60] w-[312px] max-w-[calc(100vw-1.5rem)] rounded-2xl border border-slate-200 bg-white p-3.5 shadow-xl outline-none dark:border-slate-700 dark:bg-slate-900"
                >
                    <!-- Header Navigasi Bulan & Tahun -->
                    <div class="mb-3 flex items-center justify-between gap-1.5">
                        <button
                            type="button"
                            :disabled="!canGoPrevMonth"
                            aria-label="Bulan sebelumnya"
                            class="flex h-9 min-h-[36px] w-9 min-w-[36px] shrink-0 cursor-pointer items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-700 transition-colors hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                            @click="goToPrevMonth"
                        >
                            <ChevronLeft class="h-4 w-4" aria-hidden="true" />
                        </button>

                        <div class="flex min-w-0 flex-1 items-center gap-1.5">
                            <select
                                :value="viewMonth"
                                aria-label="Pilih bulan"
                                class="focus:border-primary-500 focus:ring-primary-500/20 h-9 min-h-[36px] min-w-0 flex-1 cursor-pointer rounded-xl border border-slate-200 bg-white px-2 text-xs font-semibold text-slate-800 focus:ring-2 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                @change="handleMonthChange"
                            >
                                <option
                                    v-for="m in monthOptions"
                                    :key="m.value"
                                    :value="m.value"
                                    :disabled="m.disabled"
                                >
                                    {{ m.label }}
                                </option>
                            </select>

                            <select
                                :value="viewYear"
                                aria-label="Pilih tahun"
                                class="focus:border-primary-500 focus:ring-primary-500/20 h-9 min-h-[36px] w-[74px] shrink-0 cursor-pointer rounded-xl border border-slate-200 bg-white px-2 text-xs font-semibold text-slate-800 focus:ring-2 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                @change="handleYearChange"
                            >
                                <option v-for="y in yearOptions" :key="y" :value="y">
                                    {{ y }}
                                </option>
                            </select>
                        </div>

                        <button
                            type="button"
                            :disabled="!canGoNextMonth"
                            aria-label="Bulan berikutnya"
                            class="flex h-9 min-h-[36px] w-9 min-w-[36px] shrink-0 cursor-pointer items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-700 transition-colors hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                            @click="goToNextMonth"
                        >
                            <ChevronRight class="h-4 w-4" aria-hidden="true" />
                        </button>
                    </div>

                    <!-- Header Nama Hari -->
                    <div class="mb-1 grid grid-cols-7 text-center">
                        <span
                            v-for="weekday in WEEKDAYS_SHORT"
                            :key="weekday"
                            class="py-1 text-[11px] font-semibold text-slate-500 dark:text-slate-400"
                        >
                            {{ weekday }}
                        </span>
                    </div>

                    <!-- Grid Tanggal -->
                    <div
                        class="grid grid-cols-7 gap-y-1"
                        @mouseleave="hoveredDate = ''"
                    >
                        <span
                            v-for="spacer in leadingSpacerCount"
                            :key="`empty-${spacer}`"
                            aria-hidden="true"
                            class="h-9"
                        />

                        <button
                            v-for="day in calendarDays"
                            :key="day.iso"
                            type="button"
                            :disabled="day.isDisabled"
                            :aria-label="day.fullLabel"
                            :aria-pressed="day.isEndpoint ? 'true' : 'false'"
                            class="flex h-9 min-h-[36px] w-full cursor-pointer items-center justify-center text-xs font-medium transition-colors focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:outline-none"
                            :class="dayButtonClass(day)"
                            @click="selectDay(day)"
                            @mouseenter="!day.isDisabled && (hoveredDate = day.iso)"
                        >
                            {{ day.day }}
                        </button>
                    </div>

                    <!-- Ringkasan Pilihan Aktif (Zero Layout Shift) -->
                    <div
                        class="mt-3 flex min-h-[32px] items-center rounded-xl bg-slate-50 px-2.5 py-1.5 text-[11px] font-medium text-slate-600 dark:bg-slate-800/80 dark:text-slate-300"
                    >
                        <span class="truncate">{{ selectionSummaryText }}</span>
                    </div>

                    <!-- Footer Aksi -->
                    <div
                        class="mt-3 flex items-center justify-between gap-2 border-t border-slate-100 pt-3 dark:border-slate-800"
                    >
                        <AppButton
                            type="custom"
                            variant="ghost"
                            size="sm"
                            label="Reset"
                            :disabled="!draftStart && !draftEnd && !(clearable && hasCommittedValue)"
                            @click="handleReset"
                        />
                        <AppButton
                            type="custom"
                            variant="default"
                            size="sm"
                            :icon="Check"
                            label="Terapkan"
                            :disabled="!canApply"
                            @click="handleApply"
                        />
                    </div>
                </PopoverContent>
            </PopoverPortal>
        </PopoverRoot>

        <!-- Tombol Hapus Filter Tanggal Inline (Sibling, bukan nested button) -->
        <button
            v-if="clearable && hasCommittedValue && !disabled"
            type="button"
            aria-label="Hapus filter tanggal"
            class="absolute right-1.5 top-1/2 flex h-7 w-7 -translate-y-1/2 cursor-pointer items-center justify-center rounded-lg text-slate-500 transition-colors hover:bg-slate-200/80 hover:text-slate-800 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-slate-100"
            @click="handleInlineClear"
        >
            <X class="h-3.5 w-3.5" aria-hidden="true" />
        </button>
    </div>
</template>
