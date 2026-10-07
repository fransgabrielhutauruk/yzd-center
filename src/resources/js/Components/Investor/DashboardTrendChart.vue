<script setup>
import { computed } from 'vue';

const props = defineProps({
    // [{ month: '2026-09', value: 1100 }, ...] minimal 2 titik
    points: { type: Array, required: true },
});

const W = 360;
const H = 160;
const PAD_X = 28;
const PAD_TOP = 26;
const PAD_BOTTOM = 28;

const formatUSD0 = (v) =>
    new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        maximumFractionDigits: 0,
    }).format(v || 0);

const monthLabel = (ym) =>
    new Date(`${ym}-01T00:00:00`).toLocaleDateString('id-ID', { month: 'short' });

const coords = computed(() => {
    const values = props.points.map((p) => p.value);
    const min = Math.min(...values);
    const max = Math.max(...values);
    const range = max - min || 1;
    const stepX = (W - PAD_X * 2) / (props.points.length - 1);
    return props.points.map((p, i) => ({
        x: PAD_X + i * stepX,
        y: PAD_TOP + (1 - (p.value - min) / range) * (H - PAD_TOP - PAD_BOTTOM),
        value: p.value,
        label: monthLabel(p.month),
    }));
});

const pathD = computed(() =>
    coords.value.map((c, i) => `${i === 0 ? 'M' : 'L'} ${c.x} ${c.y}`).join(' ')
);

const ariaLabel = computed(
    () =>
        `Tren saldo: ${coords.value.map((c) => `${c.label} ${formatUSD0(c.value)}`).join(', ')}`
);
</script>

<template>
    <svg :viewBox="`0 0 ${W} ${H}`" class="h-auto w-full" role="img" :aria-label="ariaLabel">
        <path
            :d="pathD"
            fill="none"
            stroke="currentColor"
            stroke-width="2.5"
            stroke-linejoin="round"
            stroke-linecap="round"
            class="text-primary"
        />
        <g v-for="(c, i) in coords" :key="i">
            <circle :cx="c.x" :cy="c.y" r="3.5" fill="currentColor" class="text-primary" />
            <text
                v-if="i === 0 || i === coords.length - 1"
                :x="c.x"
                :y="c.y - 9"
                text-anchor="middle"
                font-size="11"
                font-weight="700"
                fill="currentColor"
                class="text-foreground"
            >
                {{ formatUSD0(c.value) }}
            </text>
            <text
                :x="c.x"
                :y="H - 8"
                text-anchor="middle"
                font-size="11"
                fill="currentColor"
                class="text-muted-foreground"
            >
                {{ c.label }}
            </text>
        </g>
    </svg>
</template>
