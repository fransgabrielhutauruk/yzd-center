<script setup>
import { watch } from 'vue';
import { Server, Loader2 } from 'lucide-vue-next';
import AppModal from '@/Components/Common/AppModal.vue';
import { useCopytradeDetail } from '@/lib/useCopytradeDetail';

const props = defineProps({
    show: { type: Boolean, default: false },
    copytrade: { type: Object, default: null },
});
const emit = defineEmits(['close']);

const { detail, loading, error, load, reset } = useCopytradeDetail();

watch(
    () => [props.show, props.copytrade?.id],
    ([show, id]) => {
        if (show && id) load(id);
        else reset();
    },
    { immediate: true }
);

const profitClass = (v) => {
    if (v === null || v === undefined) return 'text-muted-foreground';
    const num = Number(v);
    if (Math.abs(num) < 0.005) return 'text-muted-foreground';
    return num > 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-destructive';
};
</script>

<template>
    <AppModal :show="show" title="Detail Akun Copytrade" max-width="3xl" @close="emit('close')">
        <div v-if="copytrade" class="max-h-[75vh] space-y-5 overflow-y-auto p-1">
            <!-- Ringkasan -->
            <div class="bg-card border-border space-y-3 rounded-xl border p-4">
                <div
                    class="border-border/60 flex flex-col justify-between gap-1 border-b pb-3 sm:flex-row sm:items-center"
                >
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-foreground text-sm font-bold">{{
                                copytrade.user.name
                            }}</span>
                            <span class="text-muted-foreground text-xs"
                                >@{{ copytrade.user.username }}</span
                            >
                            <span
                                v-if="copytrade.pengajuan_ke"
                                class="text-muted-foreground inline-flex items-center rounded-md border border-slate-200 bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold dark:border-slate-700 dark:bg-slate-800"
                            >
                                Pengajuan ke-{{ copytrade.pengajuan_ke }}
                            </span>
                        </div>
                    </div>
                    <span class="text-muted-foreground text-[11px]"
                        >Aktif sejak {{ copytrade.tanggal_mulai || '-' }} ({{
                            copytrade.active_duration
                        }})</span
                    >
                </div>
                <div class="grid grid-cols-1 gap-4 text-xs sm:grid-cols-2">
                    <dl class="space-y-1">
                        <div class="flex justify-between">
                            <dt class="text-muted-foreground">Nomor Akun:</dt>
                            <dd class="text-foreground font-semibold">
                                {{ copytrade.nomor_akun_trading }}
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted-foreground">Tier:</dt>
                            <dd class="text-foreground font-semibold">
                                {{ copytrade.tier_label }}
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted-foreground">Broker &amp; Tipe:</dt>
                            <dd class="text-foreground font-semibold">
                                {{ copytrade.broker }} ({{ copytrade.tipe_akun }})
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted-foreground">Modal Awal:</dt>
                            <dd class="text-foreground font-semibold">
                                {{ copytrade.modal_riil_formatted }}
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted-foreground">Saldo Terakhir:</dt>
                            <dd class="text-foreground font-semibold">
                                {{ copytrade.current_balance_formatted || '-' }}
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted-foreground">Profit:</dt>
                            <dd :class="['font-semibold', profitClass(copytrade.profit)]">
                                <template v-if="copytrade.profit !== null"
                                    >{{ copytrade.profit_formatted }} ({{
                                        copytrade.profit_pct
                                    }}%)</template
                                >
                                <template v-else>-</template>
                            </dd>
                        </div>
                    </dl>
                    <dl class="space-y-1">
                        <div class="flex justify-between">
                            <dt class="text-muted-foreground">Bank:</dt>
                            <dd class="text-foreground font-semibold">{{ copytrade.nama_bank }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted-foreground">No. Rekening:</dt>
                            <dd class="text-foreground font-semibold">
                                {{ copytrade.nomor_rekening }}
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted-foreground">Atas Nama:</dt>
                            <dd class="text-foreground font-semibold">{{ copytrade.atas_nama_rekening }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted-foreground">Diverifikasi:</dt>
                            <dd class="text-foreground font-semibold">{{ copytrade.verified_at || '-' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Kredensial tier 3 -->
            <div
                v-if="copytrade.tier === 'tier_3'"
                class="space-y-3 rounded-xl border border-amber-500/30 bg-amber-500/10 p-4 text-xs"
            >
                <div class="flex items-center gap-2 border-b border-amber-500/20 pb-2">
                    <Server class="h-4 w-4 text-amber-700 dark:text-amber-400" />
                    <h4
                        class="text-xs font-bold tracking-wider text-amber-800 uppercase dark:text-amber-300"
                    >
                        Kredensial Private VPS &amp; MT4 (Tier 3)
                    </h4>
                </div>
                <p v-if="loading" class="text-muted-foreground">Memuat...</p>
                <p v-else-if="error" class="text-destructive">{{ error }}</p>
                <div v-else class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div>
                        <span class="text-muted-foreground block text-[11px]">Server Broker:</span
                        ><span class="text-foreground font-mono font-bold">{{
                            detail?.credentials?.server_broker || '-'
                        }}</span>
                    </div>
                    <div>
                        <span class="text-muted-foreground block text-[11px]">Master Password:</span
                        ><span class="text-foreground font-mono font-bold">{{
                            detail?.credentials?.master_password || '-'
                        }}</span>
                    </div>
                    <div>
                        <span class="text-muted-foreground block text-[11px]"
                            >Investor Password:</span
                        ><span class="text-foreground font-mono font-bold">{{
                            detail?.credentials?.investor_password || '-'
                        }}</span>
                    </div>
                </div>
            </div>

            <!-- Riwayat saldo (read-only) -->
            <div class="bg-card border-border space-y-3 rounded-xl border p-4">
                <h4 class="text-foreground text-xs font-bold tracking-wider uppercase">
                    Riwayat Saldo (24 catatan terakhir)
                </h4>
                <p v-if="loading" class="text-muted-foreground flex items-center gap-2 text-xs">
                    <Loader2 class="h-4 w-4 animate-spin" />Memuat...
                </p>
                <p v-else-if="error" class="text-destructive text-xs">{{ error }}</p>
                <p v-else-if="!detail?.history?.length" class="text-muted-foreground text-xs">
                    Investor belum mencatat saldo.
                </p>
                <div v-else class="max-h-[260px] overflow-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-muted/60 sticky top-0">
                            <tr>
                                <th class="text-foreground px-3 py-2 font-semibold">Tanggal</th>
                                <th class="text-foreground px-3 py-2 font-semibold">Saldo</th>
                                <th class="text-foreground px-3 py-2 font-semibold">Profit</th>
                                <th class="text-foreground px-3 py-2 font-semibold">%</th>
                            </tr>
                        </thead>
                        <tbody class="divide-border divide-y">
                            <tr v-for="h in detail.history" :key="h.id">
                                <td class="text-foreground px-3 py-2 whitespace-nowrap">
                                    {{ h.tanggal_formatted }}
                                </td>
                                <td class="text-foreground px-3 py-2 font-mono font-bold">
                                    {{ h.balance_formatted }}
                                </td>
                                <td
                                    :class="[
                                        'px-3 py-2 font-mono font-semibold',
                                        profitClass(h.keuntungan_nominal),
                                    ]"
                                >
                                    {{ h.keuntungan_nominal_formatted }}
                                </td>
                                <td
                                    :class="[
                                        'px-3 py-2 font-mono',
                                        profitClass(h.keuntungan_persen),
                                    ]"
                                >
                                    {{ Number(h.keuntungan_persen) > 0 ? '+' : '' }}{{ h.keuntungan_persen }}%
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppModal>
</template>
