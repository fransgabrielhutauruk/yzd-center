<script setup>
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppModal from '@/Components/Common/AppModal.vue';
import AppButton from '@/Components/Common/AppButton.vue';
import AppCurrencyInput from '@/Components/Common/AppCurrencyInput.vue';
import { compressImageFile } from '@/lib/imageCompression';
import { toast } from 'vue-sonner';
import {
    Calendar,
    DollarSign,
    UploadCloud,
    HelpCircle,
    Info,
    ExternalLink,
    Percent,
} from 'lucide-vue-next';

const props = defineProps({
    show: { type: Boolean, default: false },
    availableAccounts: { type: Array, default: () => [] },
    editingReport: { type: Object, default: null },
    settings: { type: Object, default: () => ({ fee_persen: 30, wd_maks_persen: 60 }) },
});

const emit = defineEmits(['close']);

const isEditing = computed(() => !!props.editingReport);

const selectedAccount = ref(null);

const form = useForm({
    akun_copytrade_id: '',
    periode_ke: null,
    profit_usd: '',
    wd_usd: '',
    wd_idr: '',
    bukti_myfxbook: null,
    bukti_wd: null,
});

watch(
    () => props.show,
    (isOpen) => {
        if (isOpen) {
            form.clearErrors();
            if (props.editingReport) {
                const rep = props.editingReport;
                form.akun_copytrade_id = rep.akun_copytrade_id;
                form.periode_ke = rep.periode_ke;
                form.profit_usd = rep.profit_usd;
                form.wd_usd = rep.wd_usd !== null ? rep.wd_usd : '';
                form.wd_idr = rep.wd_idr !== null ? rep.wd_idr : '';
                form.bukti_myfxbook = null;
                form.bukti_wd = null;
                selectedAccount.value = null;
            } else {
                form.reset();
                if (props.availableAccounts.length > 0) {
                    selectAccountItem(props.availableAccounts[0]);
                } else {
                    selectedAccount.value = null;
                }
            }
        }
    }
);

const selectAccountItem = (item) => {
    selectedAccount.value = item;
    form.akun_copytrade_id = item.akun_id;
    form.periode_ke = item.periode_ke;
};

const onAccountChange = (e) => {
    const accId = Number(e.target.value);
    const found = props.availableAccounts.find((a) => a.akun_id === accId);
    if (found) {
        selectAccountItem(found);
    }
};

const feePersen = computed(() => Number(props.settings?.fee_persen || 30));
const wdMaksPersen = computed(() => Number(props.settings?.wd_maks_persen || 60));

const profitNum = computed(() => {
    const val = parseFloat(form.profit_usd);
    return isNaN(val) ? 0 : val;
});

const isProfitPositive = computed(() => profitNum.value > 0);

const wdMinUsd = computed(() => {
    if (!isProfitPositive.value) return 0;
    return Math.round(profitNum.value * (feePersen.value / 100) * 100) / 100;
});

const wdMaxUsd = computed(() => {
    if (!isProfitPositive.value) return 0;
    return Math.round(profitNum.value * (wdMaksPersen.value / 100) * 100) / 100;
});

const applyWdPreset = (ratio) => {
    const target = ratio === 'min' ? wdMinUsd.value : wdMaxUsd.value;
    form.wd_usd = target.toFixed(2);
};

const wdNum = computed(() => {
    const val = parseFloat(form.wd_usd);
    return isNaN(val) ? 0 : val;
});

const wdIdrNum = computed(() => {
    const val = parseInt(form.wd_idr, 10);
    return isNaN(val) ? 0 : val;
});

const liveKurs = computed(() => {
    if (wdNum.value <= 0 || wdIdrNum.value <= 0) return 0;
    return Math.round((wdIdrNum.value / wdNum.value) * 100) / 100;
});

const liveFeeUsd = computed(() => {
    if (!isProfitPositive.value) return 0;
    return Math.round(profitNum.value * (feePersen.value / 100) * 100) / 100;
});

const liveFeeIdr = computed(() => {
    if (liveFeeUsd.value <= 0 || liveKurs.value <= 0) return 0;
    return Math.round(liveFeeUsd.value * liveKurs.value);
});

const handleMyfxbookFile = async (e) => {
    const file = e.target.files?.[0];
    if (!file) {
        form.bukti_myfxbook = null;
        return;
    }
    try {
        const res = await compressImageFile(file);
        form.bukti_myfxbook = res.file;
    } catch {
        form.bukti_myfxbook = file;
    }
};

const handleWdFile = async (e) => {
    const file = e.target.files?.[0];
    if (!file) {
        form.bukti_wd = null;
        return;
    }
    try {
        const res = await compressImageFile(file);
        form.bukti_wd = res.file;
    } catch {
        form.bukti_wd = file;
    }
};

const submit = () => {
    if (isEditing.value) {
        form.transform((data) => ({
            ...data,
            _method: 'PUT',
        })).post(route('member.master_fee.update', props.editingReport.id), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => emit('close'),
        });
    } else {
        form.post(route('member.master_fee.store'), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => emit('close'),
        });
    }
};
</script>

<template>
    <AppModal
        :show="show"
        :title="isEditing ? `Edit Laporan Profit ${editingReport?.nomor}` : 'Input Laporan Profit Bulanan'"
        max-width="lg"
        @close="$emit('close')"
    >
        <form @submit.prevent="submit" class="space-y-4 p-1">
            <!-- Pilihan Akun (Jika Create) -->
            <div v-if="!isEditing" class="space-y-1.5">
                <label class="block text-xs font-semibold text-foreground">
                    Pilih Akun Copytrade <span class="text-destructive">*</span>
                </label>
                <select
                    :value="form.akun_copytrade_id"
                    @change="onAccountChange"
                    class="w-full rounded-lg border border-input bg-background px-3 py-2 text-xs font-medium focus:outline-none focus:ring-2 focus:ring-primary"
                    required
                >
                    <option v-for="item in availableAccounts" :key="item.akun_id" :value="item.akun_id">
                        #{{ item.nomor_akun_trading }} — {{ item.broker }} (Periode {{ item.periode_ke }}: {{ item.periode_label }})
                    </option>
                </select>
                <p v-if="form.errors.akun_copytrade_id" class="text-xs text-destructive">
                    {{ form.errors.akun_copytrade_id }}
                </p>
            </div>

            <!-- Banner Info Akun & Periode Terpilih -->
            <div
                v-if="selectedAccount || isEditing"
                class="rounded-xl border border-blue-500/30 bg-blue-500/10 p-3 text-xs text-blue-900 dark:text-blue-200"
            >
                <div class="flex items-center justify-between">
                    <span class="font-bold">
                        Periode Ke-{{ isEditing ? editingReport.periode_ke : selectedAccount.periode_ke }}
                    </span>
                    <span class="font-mono text-[11px]">
                        {{ isEditing ? editingReport.periode_label : selectedAccount.periode_label }}
                    </span>
                </div>
            </div>

            <!-- Input Profit USD -->
            <div class="space-y-1.5">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-semibold text-foreground">
                        Profit Riil Periode (USD) <span class="text-destructive">*</span>
                    </label>
                    <span class="text-[11px] text-muted-foreground">
                        Boleh bernilai 0 atau negatif (rugi)
                    </span>
                </div>
                <AppCurrencyInput
                    v-model="form.profit_usd"
                    currency="USD"
                    allow-negative
                    placeholder="Contoh: 150.00 atau -25.00"
                    :has-error="!!form.errors.profit_usd"
                    required
                />
                <p v-if="form.errors.profit_usd" class="text-xs text-destructive">
                    {{ form.errors.profit_usd }}
                </p>
            </div>

            <!-- Screenshot Myfxbook (Wajib) -->
            <div class="space-y-1.5">
                <label class="block text-xs font-semibold text-foreground">
                    Screenshot Bukti Myfxbook
                    <span v-if="!isEditing" class="text-destructive">*</span>
                    <span v-else class="text-muted-foreground font-normal"> (Opsional jika tidak diganti)</span>
                </label>
                <input
                    type="file"
                    accept="image/jpeg,image/png,image/jpg,image/webp"
                    @change="handleMyfxbookFile"
                    class="w-full cursor-pointer rounded-lg border border-input bg-background text-xs file:mr-3 file:cursor-pointer file:rounded-md file:border-0 file:bg-primary file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-primary-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                    :required="!isEditing"
                />
                <p class="text-[11px] text-muted-foreground">
                    Screenshot layar sistem Myfxbook yang menunjukkan perolehan laba pada periode terkait.
                </p>
                <p v-if="form.errors.bukti_myfxbook" class="text-xs text-destructive">
                    {{ form.errors.bukti_myfxbook }}
                </p>
            </div>

            <!-- JIKA PROFIT > 0: INPUT PENARIKAN (WD) BROKER & BUKTI -->
            <div v-if="isProfitPositive" class="space-y-4 rounded-xl border border-border bg-muted/20 p-3.5">
                <div class="flex items-center gap-1.5 text-xs font-bold text-foreground">
                    <DollarSign class="h-4 w-4 text-emerald-600" />
                    <span>Rincian Penarikan Broker (Wajib jika profit > $0)</span>
                </div>

                <!-- WD USD & Batas Min-Maks -->
                <div class="space-y-1.5">
                    <div class="flex flex-wrap items-center justify-between gap-1 text-[11px]">
                        <label class="font-semibold text-foreground">
                            Nominal Penarikan (WD Broker) USD <span class="text-destructive">*</span>
                        </label>
                        <span class="text-muted-foreground">
                            Batas: ${{ wdMinUsd }} ({{ feePersen }}%) s/d ${{ wdMaxUsd }} ({{ wdMaksPersen }}%)
                        </span>
                    </div>

                    <AppCurrencyInput
                        v-model="form.wd_usd"
                        currency="USD"
                        :min="wdMinUsd"
                        :max="wdMaxUsd"
                        placeholder="0.00"
                        :has-error="!!form.errors.wd_usd"
                        required
                    />

                    <!-- Tombol Preset WD -->
                    <div class="flex items-center gap-2 pt-1">
                        <button
                            type="button"
                            @click="applyWdPreset('min')"
                            class="cursor-pointer rounded-md border border-border bg-background px-2.5 py-1 text-[11px] font-semibold text-foreground hover:bg-muted"
                        >
                            WD {{ feePersen }}% (${{ wdMinUsd }} - Hanya Fee)
                        </button>
                        <button
                            type="button"
                            @click="applyWdPreset('max')"
                            class="cursor-pointer rounded-md border border-border bg-background px-2.5 py-1 text-[11px] font-semibold text-foreground hover:bg-muted"
                        >
                            WD {{ wdMaksPersen }}% (${{ wdMaxUsd }} - Maksimal)
                        </button>
                    </div>

                    <p v-if="form.errors.wd_usd" class="text-xs text-destructive">
                        {{ form.errors.wd_usd }}
                    </p>
                </div>

                <!-- WD IDR (Rupiah Diterima di Rekening) -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold text-foreground">
                        Nominal Rupiah Bersih Diterima di Bank (IDR) <span class="text-destructive">*</span>
                    </label>
                    <AppCurrencyInput
                        v-model="form.wd_idr"
                        currency="IDR"
                        :min="1"
                        placeholder="Contoh: 1.000.000"
                        :has-error="!!form.errors.wd_idr"
                        required
                    />
                    <p class="text-[11px] text-muted-foreground">
                        Jumlah rupiah persis yang diterima pada mutasi rekening bank Anda dari hasil penarikan broker.
                    </p>
                    <p v-if="form.errors.wd_idr" class="text-xs text-destructive">
                        {{ form.errors.wd_idr }}
                    </p>
                </div>

                <!-- Screenshot Bukti WD Broker -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold text-foreground">
                        Screenshot Bukti Penarikan (WD Broker)
                        <span v-if="!isEditing" class="text-destructive">*</span>
                        <span v-else class="text-muted-foreground font-normal"> (Opsional jika tidak diganti)</span>
                    </label>
                    <input
                        type="file"
                        accept="image/jpeg,image/png,image/jpg,image/webp"
                        @change="handleWdFile"
                        class="w-full cursor-pointer rounded-lg border border-input bg-background text-xs file:mr-3 file:cursor-pointer file:rounded-md file:border-0 file:bg-primary file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-primary-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                        :required="!isEditing"
                    />
                    <p class="text-[11px] text-muted-foreground">
                        Screenshot riwayat penarikan dana atau mutasi bank masuk dari broker.
                    </p>
                    <p v-if="form.errors.bukti_wd" class="text-xs text-destructive">
                        {{ form.errors.bukti_wd }}
                    </p>
                </div>

                <!-- Live Perhitungan Kurs & Fee Box -->
                <div
                    v-if="wdNum > 0 && wdIdrNum > 0"
                    class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-3 text-xs text-emerald-950 dark:text-emerald-200 space-y-1.5 font-medium"
                >
                    <div class="flex items-center justify-between">
                        <span>Kurs Riil WD (IDR / USD):</span>
                        <span class="font-mono font-bold">Rp {{ liveKurs.toLocaleString('id-ID') }} / $1</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Kewajiban Master Fee ({{ feePersen }}%):</span>
                        <span class="font-mono font-bold">${{ liveFeeUsd.toFixed(2) }}</span>
                    </div>
                    <div class="flex items-center justify-between border-t border-emerald-500/20 pt-1.5 font-bold">
                        <span>Total Master Fee Yang Harus Ditransfer:</span>
                        <span class="font-mono text-sm text-emerald-700 dark:text-emerald-300">
                            Rp {{ liveFeeIdr.toLocaleString('id-ID') }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- JIKA PROFIT <= 0: INFO TANPA FEE -->
            <div
                v-else-if="form.profit_usd !== ''"
                class="flex items-start gap-2.5 rounded-xl border border-slate-300 bg-slate-100/80 p-3 text-xs text-slate-800 dark:border-slate-800 dark:bg-slate-900/60 dark:text-slate-200"
            >
                <Info class="h-4 w-4 shrink-0 text-slate-600 dark:text-slate-400 mt-0.5" />
                <p class="leading-relaxed">
                    Profit bernilai $0 atau minus. Anda tidak perlu melakukan penarikan broker maupun transfer Master Fee. Status laporan otomatis berstatus <strong>Lunas (Tanpa Fee)</strong> setelah disimpan.
                </p>
            </div>

            <!-- Tombol Aksi Modal -->
            <div class="flex items-center justify-end gap-2 border-t border-border pt-4">
                <AppButton
                    type="cancel"
                    :disabled="form.processing"
                    @click="$emit('close')"
                />
                <AppButton
                    type="save"
                    :loading="form.processing"
                    :label="isEditing ? 'Simpan Perubahan Laporan' : 'Kirim Laporan Profit'"
                />
            </div>
        </form>
    </AppModal>
</template>
