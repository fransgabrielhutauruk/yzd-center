<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import MemberLayout from '@/Layouts/MemberLayout.vue';
import AppPageHeader from '@/Components/Common/AppPageHeader.vue';
import AppButton from '@/Components/Common/AppButton.vue';
import AppConfirmDialog from '@/Components/Common/AppConfirmDialog.vue';
import TicketThread from '@/Components/Common/TicketThread.vue';
import {
    CheckCircle2,
    RotateCcw,
} from 'lucide-vue-next';

const props = defineProps({
    ticket: {
        type: Object,
        required: true,
    },
    status_labels: {
        type: Object,
        default: () => ({}),
    },
});

const isResolveDialogOpen = ref(false);
const isReopenDialogOpen = ref(false);
const isProcessing = ref(false);

const confirmResolve = () => {
    isProcessing.value = true;
    router.post(
        route('member.tickets.resolve', props.ticket.id),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isProcessing.value = false;
                isResolveDialogOpen.value = false;
            },
        }
    );
};

const confirmReopen = () => {
    isProcessing.value = true;
    router.post(
        route('member.tickets.reopen', props.ticket.id),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isProcessing.value = false;
                isReopenDialogOpen.value = false;
            },
        }
    );
};
</script>

<template>
    <MemberLayout :title="`${ticket.nomor}`">
        <Head :title="`${ticket.nomor}`" />

        <div class="space-y-6 max-w-5xl mx-auto">
            <!-- Header Halaman -->
            <AppPageHeader
                :title="`${ticket.nomor}`"
                subtitle="Utas percakapan langsung dengan admin terkait laporan kendala Anda."
            >
                <template #actions>
                    <div class="flex items-center gap-2 flex-wrap">
                        <AppButton
                            type="back"
                            :href="route('member.tickets.index')"
                            class="rounded-xl text-xs"
                        />

                        <!-- Tombol Tandai Selesai (Jika Tiket Aktif) -->
                        <AppButton
                            v-if="['open', 'in_progress', 'answered'].includes(ticket.status)"
                            variant="outline"
                            size="sm"
                            :icon="CheckCircle2"
                            label="Tandai Selesai"
                            class="rounded-xl border-emerald-500/40 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-500/10 text-xs"
                            @click="isResolveDialogOpen = true"
                        />

                        <!-- Tombol Buka Kembali (Jika Tiket Resolved) -->
                        <AppButton
                            v-else-if="ticket.status === 'resolved'"
                            variant="outline"
                            size="sm"
                            :icon="RotateCcw"
                            label="Buka Kembali Tiket"
                            class="rounded-xl border-blue-500/40 text-blue-700 dark:text-blue-300 hover:bg-blue-500/10 text-xs"
                            @click="isReopenDialogOpen = true"
                        />
                    </div>
                </template>
            </AppPageHeader>

            <!-- Utas Percakapan (Thread) -->
            <TicketThread
                :ticket="ticket"
                role="member"
                :status-labels="status_labels"
            />
        </div>

        <!-- Dialog Konfirmasi Tandai Selesai -->
        <AppConfirmDialog
            :show="isResolveDialogOpen"
            title="Tandai Tiket Selesai?"
            message="Apakah kendala Anda telah tertangani dengan baik oleh admin?"
            confirm-text="Ya, Tandai Selesai"
            cancel-text="Kembali"
            type="success"
            :icon="CheckCircle2"
            :loading="isProcessing"
            @confirm="confirmResolve"
            @cancel="isResolveDialogOpen = false"
        />

        <!-- Dialog Konfirmasi Buka Kembali -->
        <AppConfirmDialog
            :show="isReopenDialogOpen"
            title="Buka Kembali Tiket Bantuan?"
            message="Status tiket akan diubah kembali menjadi 'Open' dan tim admin akan menerima notifikasi bahwa kendala masih berlangsung."
            confirm-text="Ya, Buka Kembali"
            cancel-text="Batal"
            type="info"
            :icon="RotateCcw"
            :loading="isProcessing"
            @confirm="confirmReopen"
            @cancel="isReopenDialogOpen = false"
        />
    </MemberLayout>
</template>
