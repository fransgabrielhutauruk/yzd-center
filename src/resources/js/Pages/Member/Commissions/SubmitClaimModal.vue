<script setup>
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppModal from '@/Components/Common/AppModal.vue';
import AppButton from '@/Components/Common/AppButton.vue';
import { AlertCircle, CreditCard, Users } from 'lucide-vue-next';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    isOpen: {
        type: Boolean,
        default: false,
    },
    selectedDownlines: {
        type: Array,
        default: () => [],
    },
    defaultBank: {
        type: Object,
        default: () => ({
            nama_bank: '',
            nomor_rekening: '',
            atas_nama_rekening: '',
        }),
    },
});

const isVisible = computed(() => props.show || props.isOpen);

const emit = defineEmits(['close', 'submitted']);

const form = useForm({
    downline_ids: [],
    akun_copytrade_ids: [],
    nama_bank: props.defaultBank?.nama_bank || '',
    nomor_rekening: props.defaultBank?.nomor_rekening || '',
    atas_nama_rekening: props.defaultBank?.atas_nama_rekening || '',
});

const syncDefaultBank = () => {
    form.nama_bank = props.defaultBank?.nama_bank || '';
    form.nomor_rekening = props.defaultBank?.nomor_rekening || '';
    form.atas_nama_rekening = props.defaultBank?.atas_nama_rekening || '';
};

watch(
    () => props.defaultBank,
    (bank) => {
        if (bank && !isVisible.value) {
            syncDefaultBank();
        }
    },
    { immediate: true, deep: true }
);

watch(
    () => isVisible.value,
    (open) => {
        if (open) {
            syncDefaultBank();
            form.clearErrors();
        } else {
            form.clearErrors();
        }
    }
);

const formatUSD = (val) => {
    if (val === null || val === undefined || isNaN(val)) return '$0.00';
    return (
        '$' +
        Number(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
    );
};

const formatIDR = (val) => {
    if (val === null || val === undefined || isNaN(val)) return 'Rp 0';
    return 'Rp ' + Math.round(Number(val)).toLocaleString('id-ID');
};

const selectedSummary = computed(() => {
    const selected = props.selectedDownlines || [];
    const uniqueInvestors = new Set(selected.map((d) => d.downline_id || d.id)).size;
    const totalAkun = selected.length;
    const totalModal = selected.reduce((acc, d) => acc + (d.modal_riil ?? d.total_modal_riil ?? 0), 0);

    return {
        totalDownline: uniqueInvestors,
        totalAkun,
        totalModal,
    };
});

const submit = () => {
    if (!props.selectedDownlines?.length) return;

    form.akun_copytrade_ids = props.selectedDownlines.map((d) => d.id);
    form.downline_ids = props.selectedDownlines.map((d) => d.downline_id || d.id);

    form.post(route('member.commissions.store'), {
        preserveScroll: true,
        onSuccess: () => {
            emit('submitted');
            emit('close');
        },
    });
};
</script>

<template>
    <AppModal
        :show="isVisible"
        title="Konfirmasi Pengajuan Klaim Komisi"
        max-width="2xl"
        @close="$emit('close')"
    >
        <form @submit.prevent="submit" class="space-y-4 text-xs">
            <div
                v-if="form.errors.akun_copytrade_ids || form.errors.downline_ids"
                class="border-destructive/30 bg-destructive/10 text-destructive flex items-center gap-2 rounded-lg border p-2.5 text-xs"
            >
                <AlertCircle class="h-4 w-4 shrink-0" />
                <span>{{ form.errors.akun_copytrade_ids || form.errors.downline_ids }}</span>
            </div>

            <!-- Ringkasan Akun Copytrade yang Dipilih -->
            <div class="border-border bg-card space-y-3 rounded-xl border p-3.5">
                <div class="border-border/60 flex items-center justify-between border-b pb-2">
                    <div class="flex items-center gap-1.5">
                        <Users class="text-primary-600 dark:text-primary-400 h-4 w-4" />
                        <h4 class="text-foreground text-xs font-bold tracking-wider uppercase">
                            Ringkasan Akun Terpilih ({{ selectedSummary.totalAkun }} Akun &bull; {{ selectedSummary.totalDownline }} Investor)
                        </h4>
                    </div>
                </div>

                <div class="max-h-64 space-y-2.5 overflow-y-auto pr-1">
                    <div
                        v-for="item in selectedDownlines"
                        :key="item.id"
                        class="border-border/60 bg-muted/20 space-y-2 rounded-lg border p-3"
                    >
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <div class="flex items-center gap-1.5">
                                    <span class="text-foreground text-xs font-bold">{{ item.name }}</span>
                                    <span class="text-muted-foreground font-mono text-[11px]">@{{ item.username }}</span>
                                </div>
                                <p class="text-muted-foreground text-[10px]">
                                    Akun: <strong class="text-foreground font-mono">#{{ item.nomor_akun }}</strong> ({{ item.broker }}) &bull; Periode: {{ item.first_started_at }} s/d {{ item.period_end_date }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span
                                    class="border-primary-500/30 bg-primary-500/10 text-primary-800 dark:text-primary-300 rounded-full border px-2 py-0.5 text-[10px] font-bold"
                                >
                                    Tier {{ item.tier_level }} ({{ item.persentase_tier }}%)
                                </span>
                                <div class="text-right">
                                    <span class="text-foreground block font-mono text-xs font-bold">
                                        {{ formatUSD(item.modal_riil ?? item.total_modal_riil) }}
                                    </span>
                                    <span
                                        v-if="item.modal_idr ?? item.total_modal_idr"
                                        class="text-muted-foreground block font-mono text-[10px]"
                                    >
                                        &asymp; {{ formatIDR(item.modal_idr ?? item.total_modal_idr) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rekening Bank Tujuan Transfer Pencairan -->
            <div class="border-border bg-card space-y-3 rounded-xl border p-3.5">
                <div class="border-border/60 flex items-center justify-between border-b pb-2">
                    <div class="flex items-center gap-1.5">
                        <CreditCard class="text-primary-600 dark:text-primary-400 h-4 w-4" />
                        <h4 class="text-foreground text-xs font-bold tracking-wider uppercase">
                            Rekening Bank Tujuan Transfer
                        </h4>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div class="space-y-1">
                        <label class="text-muted-foreground block text-[11px] font-semibold">
                            Nama Bank <span class="text-destructive">*</span>
                        </label>
                        <input
                            v-model="form.nama_bank"
                            type="text"
                            required
                            placeholder="cth: BCA, Mandiri, BRI"
                            class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border px-3 py-1.5 text-xs focus:ring-2 focus:outline-none"
                        />
                        <p v-if="form.errors.nama_bank" class="text-destructive text-[11px]">
                            {{ form.errors.nama_bank }}
                        </p>
                    </div>

                    <div class="space-y-1">
                        <label class="text-muted-foreground block text-[11px] font-semibold">
                            Nomor Rekening <span class="text-destructive">*</span>
                        </label>
                        <input
                            v-model="form.nomor_rekening"
                            type="text"
                            required
                            placeholder="Nomor rekening"
                            class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border px-3 py-1.5 font-mono text-xs focus:ring-2 focus:outline-none"
                        />
                        <p v-if="form.errors.nomor_rekening" class="text-destructive text-[11px]">
                            {{ form.errors.nomor_rekening }}
                        </p>
                    </div>

                    <div class="space-y-1">
                        <label class="text-muted-foreground block text-[11px] font-semibold">
                            Atas Nama <span class="text-destructive">*</span>
                        </label>
                        <input
                            v-model="form.atas_nama_rekening"
                            type="text"
                            required
                            placeholder="Nama pemilik rekening"
                            class="bg-background border-input focus:ring-primary-500 w-full rounded-lg border px-3 py-1.5 text-xs focus:ring-2 focus:outline-none"
                        />
                        <p v-if="form.errors.atas_nama_rekening" class="text-destructive text-[11px]">
                            {{ form.errors.atas_nama_rekening }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Tombol Aksi Form -->
            <div class="border-border/60 flex items-center justify-end gap-2 border-t pt-3">
                <AppButton
                    type="cancel"
                    @click="$emit('close')"
                />
                <AppButton
                    type="submit"
                    :loading="form.processing"
                    :disabled="selectedSummary.totalAkun === 0 || form.processing"
                    :label="`Kirim Pengajuan (${selectedSummary.totalAkun} Akun)`"
                />
            </div>
        </form>
    </AppModal>
</template>
