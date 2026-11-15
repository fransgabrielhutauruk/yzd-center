<script setup>
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppModal from '@/Components/Common/AppModal.vue';
import AppButton from '@/Components/Common/AppButton.vue';
import { toast } from 'vue-sonner';
import {
    CheckCircle2,
    XCircle,
    Eye,
    User,
    Calendar,
    AlertTriangle,
    ExternalLink,
    Clock,
    FileText,
    Check,
    CreditCard,
} from 'lucide-vue-next';

const getStatusBadgeClass = (status) => {
    switch (status) {
        case 'Lunas':
            return 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20';
        case 'Tanpa Fee':
            return 'bg-slate-500/10 text-slate-700 dark:text-slate-300 border border-slate-500/20';
        case 'Menunggu Verifikasi':
            return 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/20';
        case 'Menunggu Pembayaran':
            return 'bg-blue-500/10 text-blue-700 dark:text-blue-400 border border-blue-500/20';
        case 'Perlu Koreksi':
            return 'bg-purple-500/10 text-purple-700 dark:text-purple-400 border border-purple-500/20';
        case 'Bukti Ditolak':
            return 'bg-rose-500/10 text-rose-700 dark:text-rose-400 border border-rose-500/20';
        default:
            return 'bg-muted text-muted-foreground border border-border';
    }
};

const props = defineProps({
    show: { type: Boolean, default: false },
    laporan: { type: Object, default: null },
    tagihan: { type: Object, default: null }, // backward compatibility
});

const emit = defineEmits(['close']);

const currentReport = computed(() => props.laporan || props.tagihan || null);

const activeMode = ref('normal'); // 'normal' | 'reject' | 'correction'

const rejectForm = useForm({
    alasan: '',
});

const correctionForm = useForm({
    alasan: '',
});

const verifyForm = useForm({});

watch(
    () => props.show,
    (isOpen) => {
        if (isOpen) {
            activeMode.value = 'normal';
            rejectForm.reset();
            rejectForm.clearErrors();
            correctionForm.reset();
            correctionForm.clearErrors();
        }
    }
);

const confirmVerify = () => {
    if (!currentReport.value) return;
    verifyForm.post(route('admin.master_fee.verify', currentReport.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            emit('close');
        },
    });
};

const submitReject = () => {
    if (!currentReport.value) return;
    if (!rejectForm.alasan.trim()) {
        toast.error('Alasan penolakan wajib diisi.');
        return;
    }

    rejectForm.post(route('admin.master_fee.reject', currentReport.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            emit('close');
        },
    });
};

const submitCorrection = () => {
    if (!currentReport.value) return;
    if (!correctionForm.alasan.trim()) {
        toast.error('Catatan koreksi wajib diisi.');
        return;
    }

    correctionForm.post(route('admin.master_fee.flag_correction', currentReport.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            emit('close');
        },
    });
};

const formatRupiah = (val) => {
    if (val === null || val === undefined) return 'Rp 0';
    return 'Rp ' + Number(val).toLocaleString('id-ID');
};
</script>

<template>
    <AppModal
        :show="show"
        :title="currentReport ? `Verifikasi Laporan ${currentReport.nomor}` : 'Verifikasi Laporan'"
        max-width="3xl"
        @close="$emit('close')"
    >
        <div v-if="currentReport" class="space-y-4">
            <!-- 1. Identitas Member & Akun Trading -->
            <div class="border-border bg-card space-y-3 rounded-xl border p-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="space-y-0.5">
                        <span class="text-muted-foreground flex items-center gap-1 text-xs">
                            <User class="h-3 w-3" />
                            <span
                                >{{ currentReport.user_name }} (@{{
                                    currentReport.user_username
                                }})</span
                            >
                        </span>
                        <h4 class="text-foreground text-sm font-bold">
                            Akun #{{ currentReport.nomor_akun_trading }} —
                            {{ currentReport.broker }}
                        </h4>
                        <p class="text-muted-foreground flex items-center gap-1 text-xs">
                            <Calendar class="h-3 w-3" />
                            <span
                                >Periode ke-{{ currentReport.periode_ke }} ({{
                                    currentReport.periode_label
                                }})</span
                            >
                        </p>
                    </div>

                    <div class="space-y-1 text-right">
                        <span
                            :class="[
                                'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                getStatusBadgeClass(currentReport.status_tampil),
                            ]"
                        >
                            <CheckCircle2
                                v-if="currentReport.status_tampil === 'Lunas'"
                                class="h-3 w-3"
                            />
                            <Check
                                v-else-if="currentReport.status_tampil === 'Tanpa Fee'"
                                class="h-3 w-3"
                            />
                            <Clock
                                v-else-if="currentReport.status_tampil === 'Menunggu Verifikasi'"
                                class="h-3 w-3"
                            />
                            <CreditCard
                                v-else-if="currentReport.status_tampil === 'Menunggu Pembayaran'"
                                class="h-3 w-3"
                            />
                            <AlertTriangle
                                v-else-if="currentReport.status_tampil === 'Perlu Koreksi'"
                                class="h-3 w-3"
                            />
                            <XCircle
                                v-else-if="currentReport.status_tampil === 'Bukti Ditolak'"
                                class="h-3 w-3"
                            />
                            <span>{{ currentReport.status_tampil }}</span>
                        </span>
                        <div
                            v-if="currentReport.status_catatan"
                            class="text-muted-foreground text-[10px]"
                        >
                            {{ currentReport.status_catatan }}
                        </div>
                    </div>
                </div>

                <!-- Rincian Finansial -->
                <div
                    class="border-border grid grid-cols-2 gap-2 border-t pt-3 text-xs sm:grid-cols-4"
                >
                    <div>
                        <span class="text-muted-foreground block text-[11px]">Profit USD:</span>
                        <span
                            class="font-mono font-bold"
                            :class="
                                currentReport.profit_usd >= 0
                                    ? 'text-emerald-600 dark:text-emerald-400'
                                    : 'text-rose-600 dark:text-rose-400'
                            "
                        >
                            {{ currentReport.profit_usd >= 0 ? '+' : '' }}${{
                                Number(currentReport.profit_usd).toFixed(2)
                            }}
                        </span>
                    </div>

                    <div>
                        <span class="text-muted-foreground block text-[11px]">Penarikan (WD):</span>
                        <span class="text-foreground font-mono font-bold">
                            {{ formatRupiah(currentReport.wd_idr) }}
                        </span>
                        <span
                            v-if="currentReport.wd_idr"
                            class="text-muted-foreground block text-[10px]"
                        >
                            {{
                                currentReport.wd_usd !== null
                                    ? '$' + Number(currentReport.wd_usd).toFixed(2)
                                    : '-'
                            }}
                        </span>
                    </div>

                    <div>
                        <span class="text-muted-foreground block text-[11px]">Kurs Riil:</span>
                        <span class="text-muted-foreground font-mono text-[11px]">
                            {{
                                currentReport.kurs_riil
                                    ? formatRupiah(currentReport.kurs_riil) + ' / $'
                                    : '-'
                            }}
                        </span>
                    </div>

                    <div>
                        <span class="text-muted-foreground block text-[11px]"
                            >Master Fee ({{ Number(currentReport.fee_persen) }}%):</span
                        >
                        <span class="text-foreground font-mono font-black">
                            {{ formatRupiah(currentReport.fee_idr) }}
                        </span>
                        <span
                            v-if="currentReport.fee_idr > 0"
                            class="text-muted-foreground block text-[10px]"
                        >
                            ≈ ${{ Number(currentReport.fee_usd).toFixed(2) }}
                        </span>
                    </div>
                </div>

                <!-- Tautan Myfxbook Publik Akun -->
                <div
                    v-if="currentReport.myfxbook_url"
                    class="border-border flex items-center justify-between border-t pt-2 text-xs"
                >
                    <span class="text-muted-foreground">Tautan Myfxbook Publik Akun:</span>
                    <a
                        :href="currentReport.myfxbook_url"
                        target="_blank"
                        class="text-primary inline-flex items-center gap-1 font-semibold hover:underline"
                    >
                        <span>{{ currentReport.myfxbook_url }}</span>
                        <ExternalLink class="h-3 w-3" />
                    </a>
                </div>
            </div>

            <!-- Banner Catatan Koreksi Sebelumnya (Jika Ada) -->
            <div
                v-if="currentReport.perlu_koreksi && currentReport.catatan_koreksi"
                class="space-y-1 rounded-xl border border-amber-500/30 bg-amber-500/10 p-3 text-xs text-amber-900 dark:text-amber-200"
            >
                <div class="flex items-center gap-1.5 font-bold">
                    <AlertTriangle class="h-3.5 w-3.5 text-amber-600 dark:text-amber-400" />
                    <span>Laporan Sedang Dalam Status Perlu Koreksi</span>
                </div>
                <p class="pl-5 leading-relaxed text-amber-800/90 dark:text-amber-300/90">
                    <strong>Catatan Admin:</strong> {{ currentReport.catatan_koreksi }}
                </p>
            </div>

            <!-- 2. Pratinjau Berkas Bukti (Myfxbook, WD Broker, Transfer Bank) -->
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <!-- Bukti Screenshot Myfxbook -->
                <div class="border-border bg-muted/20 space-y-2 rounded-xl border p-3">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-foreground font-bold">1. Screenshot Myfxbook</span>
                        <a
                            v-if="currentReport.urls?.bukti_myfxbook"
                            :href="currentReport.urls.bukti_myfxbook"
                            target="_blank"
                            class="text-primary inline-flex items-center gap-1 text-[11px] font-semibold hover:underline"
                        >
                            <Eye class="h-3 w-3" />
                            <span>Buka</span>
                        </a>
                    </div>

                    <div
                        v-if="currentReport.urls?.bukti_myfxbook"
                        class="border-border bg-background relative flex min-h-[120px] items-center justify-center overflow-hidden rounded-lg border"
                    >
                        <img
                            :src="currentReport.urls.bukti_myfxbook"
                            alt="Bukti Myfxbook"
                            class="max-h-36 w-auto object-contain"
                        />
                    </div>
                    <div v-else class="text-muted-foreground py-6 text-center text-xs italic">
                        Tidak ada berkas.
                    </div>
                </div>

                <!-- Bukti Screenshot WD Broker -->
                <div class="border-border bg-muted/20 space-y-2 rounded-xl border p-3">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-foreground font-bold">2. Bukti WD Broker</span>
                        <a
                            v-if="currentReport.urls?.bukti_wd"
                            :href="currentReport.urls.bukti_wd"
                            target="_blank"
                            class="text-primary inline-flex items-center gap-1 text-[11px] font-semibold hover:underline"
                        >
                            <Eye class="h-3 w-3" />
                            <span>Buka</span>
                        </a>
                    </div>

                    <div
                        v-if="currentReport.urls?.bukti_wd"
                        class="border-border bg-background relative flex min-h-[120px] items-center justify-center overflow-hidden rounded-lg border"
                    >
                        <img
                            :src="currentReport.urls.bukti_wd"
                            alt="Bukti WD Broker"
                            class="max-h-36 w-auto object-contain"
                        />
                    </div>
                    <div v-else class="text-muted-foreground py-6 text-center text-xs italic">
                        {{
                            currentReport.profit_usd <= 0
                                ? 'Tanpa WD (Profit <= 0)'
                                : 'Belum diunggah'
                        }}
                    </div>
                </div>

                <!-- Bukti Transfer Pembayaran Fee -->
                <div class="border-border bg-muted/20 space-y-2 rounded-xl border p-3">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-foreground font-bold">3. Bukti Transfer Bank</span>
                        <a
                            v-if="currentReport.urls?.bukti_transfer"
                            :href="currentReport.urls.bukti_transfer"
                            target="_blank"
                            class="text-primary inline-flex items-center gap-1 text-[11px] font-semibold hover:underline"
                        >
                            <Eye class="h-3 w-3" />
                            <span>Buka</span>
                        </a>
                    </div>

                    <div
                        v-if="currentReport.urls?.bukti_transfer"
                        class="border-border bg-background relative flex min-h-[120px] items-center justify-center overflow-hidden rounded-lg border"
                    >
                        <img
                            :src="currentReport.urls.bukti_transfer"
                            alt="Bukti Transfer"
                            class="max-h-36 w-auto object-contain"
                        />
                    </div>
                    <div v-else class="text-muted-foreground py-6 text-center text-xs italic">
                        {{
                            currentReport.fee_usd <= 0
                                ? 'Tanpa fee (bebas transfer)'
                                : 'Belum ada bukti transfer'
                        }}
                    </div>
                </div>
            </div>

            <!-- 3. Form Input Alasan Penolakan (Mode Tolak) -->
            <div
                v-if="activeMode === 'reject'"
                class="space-y-2 rounded-xl border border-rose-500/30 bg-rose-500/10 p-4"
            >
                <div
                    class="flex items-center gap-2 text-xs font-bold text-rose-800 dark:text-rose-300"
                >
                    <AlertTriangle class="h-4 w-4" />
                    <span>Masukkan Alasan Penolakan Bukti Pembayaran</span>
                </div>
                <textarea
                    v-model="rejectForm.alasan"
                    rows="3"
                    placeholder="Contoh: Nominal bukti transfer tidak sesuai, gambar terpotong, atau bukti buram..."
                    class="border-input bg-background w-full rounded-lg border p-2.5 text-xs focus:ring-2 focus:ring-rose-500 focus:outline-none"
                ></textarea>
                <p v-if="rejectForm.errors.alasan" class="text-destructive text-xs">
                    {{ rejectForm.errors.alasan }}
                </p>

                <div class="flex items-center justify-end gap-2 pt-1">
                    <AppButton
                        type="cancel"
                        size="sm"
                        @click="activeMode = 'normal'"
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

            <!-- 4. Form Input Catatan Perlu Koreksi (Mode Koreksi) -->
            <div
                v-else-if="activeMode === 'correction'"
                class="space-y-2 rounded-xl border border-amber-500/30 bg-amber-500/10 p-4"
            >
                <div
                    class="flex items-center gap-2 text-xs font-bold text-amber-800 dark:text-amber-300"
                >
                    <AlertTriangle class="h-4 w-4" />
                    <span>Tandai Laporan Perlu Koreksi oleh Member</span>
                </div>
                <p class="text-muted-foreground text-[11px]">
                    Member akan menerima notifikasi dan dapat memperbarui data profit, WD, atau
                    screenshot laporan ini.
                </p>
                <textarea
                    v-model="correctionForm.alasan"
                    rows="3"
                    placeholder="Contoh: Screenshot Myfxbook menampilkan profit $80 bukan $100. Mohon sesuaikan nominal profit dan bukti penarikan..."
                    class="border-input bg-background w-full rounded-lg border p-2.5 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none"
                ></textarea>
                <p v-if="correctionForm.errors.alasan" class="text-destructive text-xs">
                    {{ correctionForm.errors.alasan }}
                </p>

                <div class="flex items-center justify-end gap-2 pt-1">
                    <AppButton
                        type="cancel"
                        size="sm"
                        @click="activeMode = 'normal'"
                        :disabled="correctionForm.processing"
                    />
                    <AppButton
                        type="custom"
                        variant="warning"
                        size="sm"
                        label="Kirim Catatan Koreksi"
                        :icon="AlertTriangle"
                        :loading="correctionForm.processing"
                        @click="submitCorrection"
                    />
                </div>
            </div>

            <!-- 5. Tombol Aksi Normal -->
            <div
                v-else
                class="border-border flex flex-col items-center justify-between gap-3 border-t pt-4 sm:flex-row"
            >
                <AppButton type="cancel" size="sm" label="Tutup" @click="$emit('close')" />

                <div class="flex w-full flex-wrap items-center justify-end gap-2 sm:w-auto">
                    <!-- Tombol Perlu Koreksi (bila belum lunas) -->
                    <AppButton
                        v-if="currentReport.status !== 'lunas'"
                        type="custom"
                        variant="outline"
                        size="sm"
                        label="Perlu Koreksi"
                        :icon="AlertTriangle"
                        @click="activeMode = 'correction'"
                    />

                    <!-- Tombol Tolak Bukti (bila ada bukti transfer / menunggu verifikasi) -->
                    <AppButton
                        v-if="
                            currentReport.urls?.bukti_transfer &&
                            currentReport.status === 'menunggu_verifikasi'
                        "
                        type="custom"
                        variant="destructive"
                        size="sm"
                        label="Tolak Bukti"
                        :icon="XCircle"
                        @click="activeMode = 'reject'"
                    />

                    <!-- Tombol Verifikasi Lunas -->
                    <AppButton
                        v-if="currentReport.status !== 'lunas'"
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
