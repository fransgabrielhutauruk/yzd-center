<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AppPageHeader from '@/Components/Common/AppPageHeader.vue';
import AppButton from '@/Components/Common/AppButton.vue';
import AppModal from '@/Components/Common/AppModal.vue';
import TicketThread from '@/Components/Common/TicketThread.vue';
import {
    SlidersHorizontal,
    CheckCircle2,
    Lock,
    AlertCircle,
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

const isStatusModalOpen = ref(false);

const statusForm = useForm({
    status: props.ticket.status,
});

const openStatusModal = () => {
    statusForm.status = props.ticket.status;
    isStatusModalOpen.value = true;
};

const closeStatusModal = () => {
    isStatusModalOpen.value = false;
};

const submitStatusChange = () => {
    statusForm.patch(route('admin.tickets.status', props.ticket.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeStatusModal();
        },
    });
};

const getStatusBadgeClass = (status) => {
    switch (status) {
        case 'open':
            return 'bg-blue-500/15 text-blue-700 dark:text-blue-300 border-blue-500/30';
        case 'in_progress':
            return 'bg-amber-500/15 text-amber-700 dark:text-amber-300 border-amber-500/30';
        case 'answered':
            return 'bg-purple-500/15 text-purple-700 dark:text-purple-300 border-purple-500/30';
        case 'resolved':
            return 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border-emerald-500/30';
        case 'closed':
            return 'bg-muted text-muted-foreground border-border';
        default:
            return 'bg-muted text-muted-foreground border-border';
    }
};
</script>

<template>
    <AdminLayout :title="`${ticket.nomor}`">
        <Head :title="`${ticket.nomor}`" />

        <div class="space-y-6 max-w-5xl mx-auto">
            <!-- Header Halaman -->
            <AppPageHeader
                :title="`${ticket.nomor}`"
                subtitle="Detail laporan kendala, penugasan tindak lanjut, dan utas komunikasi dua arah dengan member."
            >
                <template #actions>
                    <div class="flex items-center gap-2 flex-wrap">
                        <AppButton
                            type="back"
                            label="Kembali"
                            :href="route('admin.tickets.index')"
                            class="rounded-xl text-xs"
                        />

                        <!-- Tombol Ubah Status Manual Admin -->
                        <AppButton
                            variant="outline"
                            size="sm"
                            :icon="SlidersHorizontal"
                            label="Ubah Status Tiket"
                            class="rounded-xl text-xs"
                            @click="openStatusModal"
                        />
                    </div>
                </template>
            </AppPageHeader>

            <!-- Utas Percakapan (Thread) -->
            <TicketThread
                :ticket="ticket"
                role="admin"
                :status-labels="status_labels"
            />
        </div>

        <!-- Modal Ubah Status Manual Admin -->
        <AppModal
            :show="isStatusModalOpen"
            title="Pembaruan Status Tiket Bantuan"
            max-width="md"
            @close="closeStatusModal"
        >
            <form id="statusForm" @submit.prevent="submitStatusChange" class="space-y-4">
                <!-- Info Ringkas Tiket -->
                <div class="rounded-xl border border-border/70 bg-muted/30 p-3.5 space-y-2.5 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-muted-foreground font-medium">Nomor Tiket:</span>
                        <span class="font-mono font-bold text-primary bg-primary/10 px-2 py-0.5 rounded-md border border-primary/20">
                            {{ ticket.nomor }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-muted-foreground font-medium">Pelapor:</span>
                        <span class="font-semibold text-foreground flex items-center gap-1.5">
                            {{ ticket.user?.name || '-' }}
                            <span v-if="ticket.user?.username" class="font-normal text-muted-foreground text-[11px]">
                                (@{{ ticket.user.username }})
                            </span>
                        </span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-muted-foreground font-medium">Status Saat Ini:</span>
                        <span
                            :class="[
                                'text-[11px] font-semibold px-2.5 py-0.5 rounded-full border',
                                getStatusBadgeClass(ticket.status)
                            ]"
                        >
                            {{ ticket.status_label || status_labels[ticket.status] || ticket.status }}
                        </span>
                    </div>
                </div>

                <!-- Pilihan Status Baru -->
                <div class="space-y-1.5">
                    <label for="new-status-select" class="block text-xs sm:text-sm font-semibold text-foreground">
                        Pilih Status Baru <span class="text-destructive">*</span>
                    </label>
                    <select
                        id="new-status-select"
                        v-model="statusForm.status"
                        class="w-full rounded-xl border border-border bg-background px-3 py-2.5 text-xs sm:text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/40 transition-all cursor-pointer"
                        required
                    >
                        <option
                            v-for="(label, key) in status_labels"
                            :key="key"
                            :value="key"
                        >
                            {{ label }} {{ key === ticket.status ? '(Status Saat Ini)' : '' }}
                        </option>
                    </select>

                    <div v-if="statusForm.errors.status" class="text-destructive text-xs font-medium flex items-center gap-1 pt-1">
                        <AlertCircle class="size-3.5 shrink-0" />
                        <span>{{ statusForm.errors.status }}</span>
                    </div>

                    <p v-if="statusForm.status === ticket.status" class="text-[11px] text-muted-foreground pt-0.5">
                        Pilih status yang berbeda untuk menyimpan pembaruan.
                    </p>
                </div>
            </form>

            <!-- Footer Aksi Modal -->
            <template #footer>
                <AppButton
                    type="cancel"
                    @click="closeStatusModal"
                />
                <AppButton
                    type="save"
                    form="statusForm"
                    submit
                    label="Simpan Perubahan"
                    :loading="statusForm.processing"
                    :disabled="statusForm.processing || statusForm.status === ticket.status"
                />
            </template>
        </AppModal>
    </AdminLayout>
</template>
