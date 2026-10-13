<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AppDataTable from '@/Components/Common/AppDataTable.vue';
import AppModal from '@/Components/Common/AppModal.vue';
import AppTableAction from '@/Components/Common/AppTableAction.vue';
import { Button } from '@/Components/ui/button';
import { toast } from 'vue-sonner';
import {
    UserCheck,
    Clock,
    CheckCircle2,
    XCircle,
    MessageSquare,
    FileText,
    Loader2,
    ExternalLink,
} from 'lucide-vue-next';

const props = defineProps({
    claims: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    pendingCount: {
        type: Number,
        default: 0,
    },
});

const selectedClaim = ref(null);
const isVerifyModalOpen = ref(false);
const isImageZoomOpen = ref(false);

const verifyForm = useForm({
    status: '',
    catatan_admin: '',
});

const openVerifyModal = (claim) => {
    selectedClaim.value = claim;
    verifyForm.status = claim.status === 'pending' ? '' : claim.status;
    verifyForm.catatan_admin = claim.catatan_admin || '';
    verifyForm.clearErrors();
    isVerifyModalOpen.value = true;
};

const closeVerifyModal = () => {
    isVerifyModalOpen.value = false;
    selectedClaim.value = null;
    verifyForm.reset();
    verifyForm.status = '';
};

const submitVerify = () => {
    if (!selectedClaim.value) return;

    verifyForm.post(route('admin.referral_claims.verify', { id: selectedClaim.value.id }), {
        preserveScroll: true,
        onSuccess: () => {
            closeVerifyModal();
        },
    });
};

const columns = [
    { key: 'created_at', label: 'Diajukan Pada', sortable: true },
    { key: 'affiliator', label: 'Mitra Affiliator', sortable: false },
    { key: 'downline', label: 'Calon Downline', sortable: false },
    { key: 'status', label: 'Status', sortable: true },
    { key: 'actions', label: 'Aksi', sortable: false },
];
</script>

<template>
    <AdminLayout
        title="Verifikasi Klaim Susulan Referral"
        subtitle="Tinjau dan verifikasi permohonan klaim downline susulan yang diajukan oleh mitra affiliator."
        :breadcrumbs="[
            { label: 'Operasional & Validasi' },
            { label: 'Klaim Susulan Referral' }
        ]"
    >
        <template #header-actions>
            <div
                v-if="pendingCount > 0"
                class="flex items-center gap-2 rounded-xl border border-amber-500/30 bg-amber-500/10 px-3.5 py-2 text-xs font-semibold text-amber-800 dark:text-amber-300"
            >
                <Clock class="h-4 w-4 text-amber-600 dark:text-amber-400" />
                <span>{{ pendingCount }} Permohonan Menunggu Peninjauan</span>
            </div>
        </template>

        <Head title="Verifikasi Klaim Referral" />

        <div class="space-y-6">

            <!-- Tabel Data Klaim Susulan -->
            <AppDataTable
                :data="claims"
                :columns="columns"
                :filters="filters"
                :fetch-url="route('admin.referral_claims.index')"
                search-placeholder="Cari nama affiliator atau downline..."
                empty-title="Tidak Ada Permohonan Klaim"
                empty-message="Belum ada permohonan klaim referral susulan yang diajukan atau sesuaikan filter pencarian Anda."
            >
                <!-- Filter Kustom: Status -->
                <template #filters="{ applyFilters }">
                    <div class="flex items-center gap-2">
                        <select
                            :value="filters.status || ''"
                            @change="(e) => applyFilters({ status: e.target.value || undefined, page: 1 })"
                            class="h-9 rounded-lg border border-input bg-background px-3 text-xs text-foreground transition-all focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none"
                        >
                            <option value="">Semua Status Klaim</option>
                            <option value="pending">Menunggu Verifikasi</option>
                            <option value="approved">Disetujui</option>
                            <option value="rejected">Ditolak</option>
                        </select>
                    </div>
                </template>

                <!-- Custom Slot: Created At -->
                <template #column-created_at="{ item }">
                    <span class="text-xs font-medium text-foreground whitespace-nowrap">
                        {{ item.created_at_label || item.created_at }}
                    </span>
                </template>

                <!-- Custom Slot: Affiliator -->
                <template #column-affiliator="{ item }">
                    <div class="space-y-0.5">
                        <p class="font-bold text-foreground">{{ item.affiliator?.name }}</p>
                        <p class="text-xs text-muted-foreground font-mono">
                            @{{ item.affiliator?.username }}
                        </p>
                    </div>
                </template>

                <!-- Custom Slot: Downline -->
                <template #column-downline="{ item }">
                    <div class="space-y-0.5">
                        <p class="font-bold text-foreground">{{ item.downline?.name }}</p>
                        <p class="text-xs text-muted-foreground">
                            @{{ item.downline?.username }} &bull; Terdaftar: {{ item.downline?.created_at }}
                        </p>
                    </div>
                </template>

                <!-- Custom Slot: Status -->
                <template #column-status="{ item }">
                    <div
                        v-if="item.status === 'approved'"
                        class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-semibold bg-emerald-500/10 text-emerald-800 dark:text-emerald-300 border-emerald-500/30 whitespace-nowrap"
                    >
                        <CheckCircle2 class="h-3 w-3" />
                        <span>Disetujui</span>
                    </div>
                    <div
                        v-else-if="item.status === 'rejected'"
                        class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-semibold bg-rose-500/10 text-rose-800 dark:text-rose-300 border-rose-500/30 whitespace-nowrap"
                    >
                        <XCircle class="h-3 w-3" />
                        <span>Ditolak</span>
                    </div>
                    <div
                        v-else
                        class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-semibold bg-sky-500/10 text-sky-800 dark:text-sky-300 border-sky-500/30 whitespace-nowrap"
                    >
                        <Clock class="h-3 w-3" />
                        <span>Menunggu Verifikasi</span>
                    </div>
                </template>

                <!-- Custom Slot: Actions -->
                <template #column-actions="{ item }">
                    <AppTableAction
                        type="verify"
                        :title="item.status === 'pending' ? 'Tinjau & Putuskan' : 'Lihat Keputusan'"
                        @click="openVerifyModal(item)"
                    />
                </template>
            </AppDataTable>
        </div>

        <!-- MODAL TINJAU & KEPUTUSAN VERIFIKASI KLAIM -->
        <AppModal
            :show="isVerifyModalOpen"
            title="Verifikasi Klaim Referral Susulan"
            max-width="2xl"
            @close="closeVerifyModal"
        >
            <div v-if="selectedClaim" class="space-y-4">
                <!-- Info Mitra & Downline -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="rounded-xl border border-border bg-muted/20 p-3 space-y-1 text-xs">
                        <span class="text-muted-foreground font-medium">Mitra Pengaju:</span>
                        <p class="font-bold text-foreground text-sm">{{ selectedClaim.affiliator?.name }}</p>
                        <p class="text-muted-foreground">Username: @{{ selectedClaim.affiliator?.username }}</p>
                    </div>

                    <div class="rounded-xl border border-border bg-muted/20 p-3 space-y-1 text-xs">
                        <span class="text-muted-foreground font-medium">Calon Downline:</span>
                        <p class="font-bold text-foreground text-sm">{{ selectedClaim.downline?.name }}</p>
                        <p class="text-muted-foreground">Username: @{{ selectedClaim.downline?.username }}</p>
                        <p class="text-muted-foreground">Terdaftar: {{ selectedClaim.downline?.created_at }}</p>
                    </div>
                </div>

                <!-- Alasan Kronologi -->
                <div class="rounded-xl border border-border p-3 space-y-1 text-xs">
                    <p class="font-semibold text-foreground flex items-center gap-1.5">
                        <MessageSquare class="h-3.5 w-3.5 text-primary-600" />
                        <span>Kronologi dari Affiliator:</span>
                    </p>
                    <p class="text-muted-foreground leading-relaxed italic whitespace-pre-wrap">
                        "{{ selectedClaim.alasan }}"
                    </p>
                </div>

                <!-- Pratinjau Bukti Pendukung -->
                <div v-if="selectedClaim.bukti_chat_url" class="space-y-1.5">
                    <p class="text-xs font-semibold text-foreground flex items-center justify-between">
                        <span>Bukti Pendukung:</span>
                        <a
                            :href="selectedClaim.bukti_chat_url"
                            target="_blank"
                            class="text-[11px] text-primary-600 dark:text-primary-400 hover:underline flex items-center gap-1"
                        >
                            <span>Buka di Tab Baru</span>
                            <ExternalLink class="h-3 w-3" />
                        </a>
                    </p>
                    <div v-if="selectedClaim.bukti_is_pdf" class="rounded-xl border border-border bg-muted/10 p-4 text-center">
                        <FileText class="h-8 w-8 mx-auto text-primary-600 dark:text-primary-400" />
                        <p class="text-xs font-medium text-foreground mt-1">Dokumen Bukti Pendukung (PDF)</p>
                        <a :href="selectedClaim.bukti_chat_url" target="_blank" class="inline-flex items-center gap-1 text-xs text-primary-600 dark:text-primary-400 hover:underline mt-2">
                            <span>Buka Dokumen PDF</span>
                            <ExternalLink class="h-3 w-3" />
                        </a>
                    </div>
                    <div v-else class="flex justify-center rounded-xl border border-border bg-muted/10 p-2 overflow-hidden">
                        <img
                            :src="selectedClaim.bukti_chat_url"
                            alt="Bukti Pendukung"
                            class="max-h-56 rounded-lg object-contain cursor-pointer hover:opacity-95 transition-opacity"
                            @click="isImageZoomOpen = true"
                        />
                    </div>
                </div>
                <div v-else class="rounded-xl border border-dashed border-border p-3 text-center text-xs text-muted-foreground">
                    Affiliator tidak melampirkan berkas bukti fisik (opsional).
                </div>

                <!-- Formulir Keputusan (Hanya aktif jika status masih pending, atau menampilkan info jika sudah diproses) -->
                <form @submit.prevent="submitVerify" class="space-y-4 pt-2 border-t border-border">
                    <div v-if="selectedClaim.status === 'pending'" class="space-y-3">
                        <p class="text-xs font-bold text-foreground">
                            Tentukan Keputusan Verifikasi:
                        </p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <!-- Opsi Setujui -->
                            <label
                                class="flex items-start gap-3 rounded-xl border p-3 cursor-pointer transition-all"
                                :class="[
                                    verifyForm.status === 'approved'
                                        ? 'border-emerald-500 bg-emerald-500/10 dark:bg-emerald-950/20'
                                        : 'border-border hover:bg-muted/30',
                                ]"
                            >
                                <input
                                    type="radio"
                                    name="verify_status"
                                    value="approved"
                                    v-model="verifyForm.status"
                                    class="mt-0.5 text-emerald-600 focus:ring-emerald-500"
                                />
                                <div>
                                    <p class="text-xs font-bold text-foreground">Setujui Klaim (Sah)</p>
                                    <p class="text-[11px] text-muted-foreground mt-0.5">
                                        Hubungkan investor resmi sebagai downline affiliator ini.
                                    </p>
                                </div>
                            </label>

                            <!-- Opsi Tolak -->
                            <label
                                class="flex items-start gap-3 rounded-xl border p-3 cursor-pointer transition-all"
                                :class="[
                                    verifyForm.status === 'rejected'
                                        ? 'border-rose-500 bg-rose-500/10 dark:bg-rose-950/20'
                                        : 'border-border hover:bg-muted/30',
                                ]"
                            >
                                <input
                                    type="radio"
                                    name="verify_status"
                                    value="rejected"
                                    v-model="verifyForm.status"
                                    class="mt-0.5 text-rose-600 focus:ring-rose-500"
                                />
                                <div>
                                    <p class="text-xs font-bold text-foreground">Tolak Permohonan</p>
                                    <p class="text-[11px] text-muted-foreground mt-0.5">
                                        Bukti tidak cocok, meragukan, atau tanggal tidak sesuai.
                                    </p>
                                </div>
                            </label>
                        </div>

                        <!-- Catatan Admin (Wajib jika tolak) -->
                        <div class="space-y-1.5">
                            <label for="catatan_admin" class="block text-xs font-semibold text-foreground">
                                Catatan Instruksi / Alasan Keputusan
                                <span v-if="verifyForm.status === 'rejected'" class="text-destructive">* (Wajib diisi)</span>
                                <span v-else class="text-muted-foreground font-normal">(Opsional)</span>
                            </label>
                            <textarea
                                id="catatan_admin"
                                v-model="verifyForm.catatan_admin"
                                rows="2"
                                placeholder="Tuliskan catatan alasan jika menolak klaim..."
                                class="w-full rounded-lg border border-input bg-background py-2 px-3 text-xs sm:text-sm transition-all focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none"
                                :class="{ 'border-destructive': verifyForm.errors.catatan_admin }"
                            ></textarea>
                            <p v-if="verifyForm.errors.catatan_admin" class="text-xs font-medium text-destructive">
                                {{ verifyForm.errors.catatan_admin }}
                            </p>
                        </div>
                    </div>

                    <!-- Jika sudah diproses sebelumnya -->
                    <div v-else class="rounded-xl border border-border bg-muted/20 p-3 space-y-1.5 text-xs">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-foreground">Status Keputusan:</span>
                            <span
                                class="font-bold uppercase tracking-wider"
                                :class="selectedClaim.status === 'approved' ? 'text-emerald-600' : 'text-rose-600'"
                            >
                                {{ selectedClaim.status_label }}
                            </span>
                        </div>
                        <p class="text-muted-foreground">
                            Ditinjau pada {{ selectedClaim.reviewed_at }}
                        </p>
                        <p v-if="selectedClaim.catatan_admin" class="text-muted-foreground">
                            Catatan: {{ selectedClaim.catatan_admin }}
                        </p>
                    </div>

                    <!-- Tombol Aksi Modal -->
                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-border">
                        <Button
                            type="button"
                            variant="outline"
                            @click="closeVerifyModal"
                            class="text-xs"
                        >
                            Tutup
                        </Button>

                        <Button
                            v-if="selectedClaim.status === 'pending'"
                            type="submit"
                            :disabled="verifyForm.processing || !verifyForm.status || (verifyForm.status === 'rejected' && !verifyForm.catatan_admin.trim())"
                            class="bg-primary-600 hover:bg-primary-700 text-white text-xs flex items-center gap-1.5"
                        >
                            <Loader2 v-if="verifyForm.processing" class="h-3.5 w-3.5 animate-spin" />
                            <span>Simpan Keputusan</span>
                        </Button>
                    </div>
                </form>
            </div>
        </AppModal>

        <!-- MODAL ZOOM GAMBAR BUKTI CHAT -->
        <AppModal
            :show="isImageZoomOpen"
            title="Pratinjau Bukti Tangkapan Layar Chat"
            max-width="3xl"
            @close="isImageZoomOpen = false"
        >
            <div v-if="selectedClaim" class="flex flex-col items-center justify-center p-2">
                <img
                    :src="selectedClaim.bukti_chat_url"
                    alt="Bukti Chat Zoom"
                    class="max-h-[75vh] rounded-lg object-contain border border-border"
                />
            </div>
            <div class="flex justify-end pt-3 border-t border-border">
                <Button
                    type="button"
                    variant="outline"
                    @click="isImageZoomOpen = false"
                    class="text-xs"
                >
                    Tutup
                </Button>
            </div>
        </AppModal>
    </AdminLayout>
</template>
