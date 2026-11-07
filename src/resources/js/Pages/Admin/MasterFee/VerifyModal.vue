<script setup>
import { ref, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppModal from '@/Components/Common/AppModal.vue';
import AppButton from '@/Components/Common/AppButton.vue';
import { toast } from 'vue-sonner';
import {
    CheckCircle2,
    XCircle,
    Eye,
    Receipt,
    User,
    Building2,
    Calendar,
    AlertTriangle,
} from 'lucide-vue-next';

const props = defineProps({
    show: { type: Boolean, default: false },
    tagihan: { type: Object, default: null },
});

const emit = defineEmits(['close']);

const isRejectMode = ref(false);

const rejectForm = useForm({
    alasan: '',
});

const verifyForm = useForm({});

watch(
    () => props.show,
    (isOpen) => {
        if (isOpen) {
            isRejectMode.value = false;
            rejectForm.reset();
            rejectForm.clearErrors();
        }
    }
);

const confirmVerify = () => {
    if (!props.tagihan) return;
    verifyForm.post(route('admin.master_fee.verify', props.tagihan.id), {
        preserveScroll: true,
        onSuccess: () => {
            emit('close');
        },
    });
};

const submitReject = () => {
    if (!props.tagihan) return;
    if (!rejectForm.alasan.trim()) {
        toast.error('Alasan penolakan wajib diisi.');
        return;
    }

    rejectForm.post(route('admin.master_fee.reject', props.tagihan.id), {
        preserveScroll: true,
        onSuccess: () => {
            emit('close');
        },
    });
};
</script>

<template>
    <AppModal
        :show="show"
        :title="tagihan ? `Verifikasi Pembayaran ${tagihan.nomor}` : 'Verifikasi Tagihan'"
        max-width="lg"
        @close="$emit('close')"
    >
        <div v-if="tagihan" class="space-y-4">
            <!-- 1. Identitas Member & Akun Trading -->
            <div class="rounded-xl border border-border bg-card p-4 space-y-3">
                <div class="flex items-start justify-between gap-3">
                    <div class="space-y-0.5">
                        <span class="text-xs text-muted-foreground flex items-center gap-1">
                            <User class="h-3 w-3" />
                            <span>{{ tagihan.user?.name }} ({{ tagihan.user?.email }})</span>
                        </span>
                        <h4 class="text-sm font-bold text-foreground">
                            Akun Trading: #{{ tagihan.akun?.nomor_akun_trading }} — {{ tagihan.akun?.broker }}
                        </h4>
                    </div>
                    <span
                        :class="[
                            'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold',
                            tagihan.status === 'lunas'
                                ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20'
                                : tagihan.status === 'menunggu_verifikasi'
                                  ? 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/20'
                                  : 'bg-slate-500/10 text-slate-700 dark:text-slate-300'
                        ]"
                    >
                        {{ tagihan.is_terlambat ? 'Terlambat' : tagihan.status_label }}
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-2 text-xs pt-2 border-t border-border">
                    <div>
                        <span class="text-muted-foreground block text-[11px]">Profit Bersih Bulan Lalu:</span>
                        <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400">
                            +${{ Number(tagihan.profit_bersih).toFixed(2) }}
                        </span>
                    </div>
                    <div>
                        <span class="text-muted-foreground block text-[11px]">Nominal Master Fee ({{ Number(tagihan.fee_persen) }}%):</span>
                        <span class="font-mono font-black text-foreground text-sm">
                            ${{ Number(tagihan.nominal_fee).toFixed(2) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- 2. Pratinjau Berkas Bukti Transfer & Bukti Saldo -->
            <div class="grid grid-cols-1 gap-3">
                <!-- Bukti Transfer Pembayaran -->
                <div class="rounded-xl border border-border p-3 space-y-2 bg-muted/20">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-foreground">Bukti Transfer Bank</span>
                        <a
                            v-if="tagihan.bukti_transfer_path"
                            :href="route('documents.master_fee_proof', tagihan.id)"
                            target="_blank"
                            class="text-primary hover:underline inline-flex items-center gap-1 text-[11px] font-semibold"
                        >
                            <Eye class="h-3 w-3" />
                            <span>Buka Penuh</span>
                        </a>
                    </div>

                    <div
                        v-if="tagihan.bukti_transfer_path"
                        class="relative rounded-lg border border-border overflow-hidden bg-background flex items-center justify-center min-h-[140px]"
                    >
                        <img
                            :src="route('documents.master_fee_proof', tagihan.id)"
                            alt="Bukti Transfer"
                            class="max-h-48 w-auto object-contain"
                        />
                    </div>
                    <div v-else class="text-xs text-muted-foreground italic py-6 text-center">
                        Belum ada bukti transfer diunggah.
                    </div>
                </div>

                <!-- Bukti Saldo MT4 -->
                <div class="rounded-xl border border-border p-3 space-y-2 bg-muted/20">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-foreground">Bukti Saldo MT4</span>
                        <a
                            v-if="tagihan.riwayat_balance?.bukti_saldo_path"
                            :href="route('documents.balance_proof', tagihan.riwayat_balance_id)"
                            target="_blank"
                            class="text-primary hover:underline inline-flex items-center gap-1 text-[11px] font-semibold"
                        >
                            <Eye class="h-3 w-3" />
                            <span>Buka Penuh</span>
                        </a>
                    </div>

                    <div
                        v-if="tagihan.riwayat_balance?.bukti_saldo_path"
                        class="relative rounded-lg border border-border overflow-hidden bg-background flex items-center justify-center min-h-[140px]"
                    >
                        <img
                            :src="route('documents.balance_proof', tagihan.riwayat_balance_id)"
                            alt="Bukti Saldo"
                            class="max-h-48 w-auto object-contain"
                        />
                    </div>
                    <div v-else class="text-xs text-muted-foreground italic py-6 text-center">
                        Tidak ada screenshot saldo (rekap historis).
                    </div>
                </div>
            </div>

            <!-- 3. Form Input Alasan Penolakan (Mode Tolak) -->
            <div
                v-if="isRejectMode"
                class="rounded-xl border border-rose-500/30 bg-rose-500/10 p-4 space-y-2"
            >
                <div class="flex items-center gap-2 text-rose-800 dark:text-rose-300 font-bold text-xs">
                    <AlertTriangle class="h-4 w-4" />
                    <span>Masukkan Alasan Penolakan Bukti Pembayaran</span>
                </div>
                <textarea
                    v-model="rejectForm.alasan"
                    rows="3"
                    placeholder="Contoh: Nominal bukti transfer tidak sesuai, gambar terpotong, atau bukti buram..."
                    class="w-full rounded-lg border border-input bg-background p-2.5 text-xs focus:ring-2 focus:ring-rose-500 focus:outline-none"
                ></textarea>
                <p v-if="rejectForm.errors.alasan" class="text-xs text-destructive">
                    {{ rejectForm.errors.alasan }}
                </p>

                <div class="flex items-center justify-end gap-2 pt-1">
                    <AppButton
                        type="cancel"
                        size="sm"
                        @click="isRejectMode = false"
                        :disabled="rejectForm.processing"
                    />
                    <AppButton
                        type="custom"
                        variant="destructive"
                        size="sm"
                        label="Konfirmasi Tolak"
                        :icon="XCircle"
                        :loading="rejectForm.processing"
                        @click="submitReject"
                    />
                </div>
            </div>

            <!-- 4. Tombol Aksi Verifikasi / Tolak -->
            <div
                v-else
                class="border-t border-border pt-4 flex flex-col sm:flex-row items-center justify-between gap-3"
            >
                <AppButton
                    type="cancel"
                    size="sm"
                    label="Tutup"
                    @click="$emit('close')"
                />

                <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                    <AppButton
                        type="custom"
                        variant="destructive"
                        size="sm"
                        label="Tolak Bukti"
                        :icon="XCircle"
                        @click="isRejectMode = true"
                    />
                    <AppButton
                        type="custom"
                        size="sm"
                        label="Verifikasi Lunas"
                        :icon="CheckCircle2"
                        :loading="verifyForm.processing"
                        @click="confirmVerify"
                    />
                </div>
            </div>
        </div>
    </AppModal>
</template>
