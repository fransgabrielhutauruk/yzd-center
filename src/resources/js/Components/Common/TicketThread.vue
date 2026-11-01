<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppButton from '@/Components/Common/AppButton.vue';
import AppModal from '@/Components/Common/AppModal.vue';
import { compressImageFile, formatBytes } from '@/lib/imageCompression';
import {
    Send,
    X,
    Download,
    Eye,
    LifeBuoy,
    CheckCircle2,
    Lock,
    User,
    ShieldCheck,
    Paperclip,
    AlertCircle,
    ChevronLeft,
    ChevronRight,
} from 'lucide-vue-next';

const props = defineProps({
    ticket: {
        type: Object,
        required: true,
    },
    role: {
        type: String,
        default: 'member', // 'member' | 'admin'
    },
    statusLabels: {
        type: Object,
        default: () => ({}),
    },
});

// State Balas Percakapan
const fileInputRef = ref(null);
const isCompressing = ref(false);
const imagePreviews = ref([]);

const form = useForm({
    pesan: '',
    images: [],
});

const canReply = computed(() => {
    if (props.role === 'admin') {
        return props.ticket.status !== 'closed';
    }
    return ['open', 'in_progress', 'answered'].includes(props.ticket.status);
});

// Pesan kendala awal & balasan lanjutan
const firstBalasan = computed(() => {
    return props.ticket.balasan && props.ticket.balasan.length > 0
        ? props.ticket.balasan[0]
        : null;
});

const threadReplies = computed(() => {
    return props.ticket.balasan && props.ticket.balasan.length > 1
        ? props.ticket.balasan.slice(1)
        : [];
});

// Status Badge Styling Helper
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

// State Lightbox Modal
const lightbox = ref({
    isOpen: false,
    images: [],
    currentIndex: 0,
});

const openLightbox = (images, index) => {
    lightbox.value = {
        isOpen: true,
        images,
        currentIndex: index,
    };
};

const closeLightbox = () => {
    lightbox.value.isOpen = false;
};

const currentLightboxImage = computed(() => {
    if (!lightbox.value.images || lightbox.value.images.length === 0) return null;
    return lightbox.value.images[lightbox.value.currentIndex] || null;
});

const nextLightbox = () => {
    if (lightbox.value.currentIndex < lightbox.value.images.length - 1) {
        lightbox.value.currentIndex++;
    }
};

const prevLightbox = () => {
    if (lightbox.value.currentIndex > 0) {
        lightbox.value.currentIndex--;
    }
};

// Penanganan Unggah Berkas & Kompresi Senyap
const handleFiles = async (files) => {
    if (!files || files.length === 0) return;

    isCompressing.value = true;
    try {
        for (const file of Array.from(files)) {
            if (!file.type.startsWith('image/')) {
                continue;
            }

            const result = await compressImageFile(file, 2048, 2048, 0.85);
            if (result && result.file) {
                form.images.push(result.file);
                imagePreviews.value.push({
                    name: file.name,
                    size: result.compressedSize || result.file.size,
                    url: result.previewUrl || URL.createObjectURL(result.file),
                });
            }
        }
    } catch (e) {
        console.error('Gagal mengompresi gambar:', e);
    } finally {
        isCompressing.value = false;
    }
};

const onFileInputChange = (e) => {
    handleFiles(e.target.files);
    e.target.value = '';
};

const removeImage = (index) => {
    form.images.splice(index, 1);
    imagePreviews.value.splice(index, 1);
};

// Kirim balasan
const submitReply = () => {
    if (!form.pesan.trim() && form.images.length === 0) return;

    const endpoint = props.role === 'admin'
        ? route('admin.tickets.reply', props.ticket.id)
        : route('member.tickets.reply', props.ticket.id);

    form.post(endpoint, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            form.reset('pesan', 'images');
            imagePreviews.value = [];
        },
    });
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
};
</script>

<template>
    <div class="space-y-6">
        <!-- Banner Keterangan Status Non-Aktif (Tepat di bawah Header Title / Subtitle) -->
        <div
            v-if="ticket.status === 'resolved'"
            class="bg-emerald-500/10 border border-emerald-500/30 rounded-2xl p-4 flex items-start gap-3 text-emerald-800 dark:text-emerald-300 shadow-2xs"
        >
            <CheckCircle2 class="size-5 shrink-0 mt-0.5 text-emerald-600 dark:text-emerald-400" />
            <div class="text-xs sm:text-sm space-y-1">
                <div class="font-bold">Tiket Telah Selesai (Resolved)</div>
                <p class="leading-relaxed">
                    Tiket ini telah ditandai selesai. Jika kendala masih berlanjut atau memerlukan bantuan tambahan,
                    silakan gunakan tombol <strong>"Buka Kembali"</strong> di bagian atas halaman untuk melanjutkan percakapan.
                </p>
            </div>
        </div>

        <div
            v-else-if="ticket.status === 'closed'"
            class="bg-muted border border-border rounded-2xl p-4 flex items-start gap-3 text-muted-foreground shadow-2xs"
        >
            <Lock class="size-5 shrink-0 mt-0.5 text-muted-foreground" />
            <div class="text-xs sm:text-sm space-y-1">
                <div class="font-bold text-foreground">Tiket Telah Ditutup (Closed)</div>
                <p class="leading-relaxed">
                    Tiket ini telah resmi ditutup dan diarsipkan. Anda tidak dapat mengirim balasan baru pada tiket ini.
                    Jika Anda memiliki kendala baru, silakan buat tiket bantuan baru.
                </p>
            </div>
        </div>

        <!-- Header Info Tiket -->
        <div class="bg-card border border-border rounded-2xl p-5 shadow-2xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-border pb-3 mb-2">
                <div class="space-y-1">
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <span
                            :class="[
                                'text-xs font-semibold px-2.5 py-0.5 rounded-full border',
                                getStatusBadgeClass(ticket.status)
                            ]"
                        >
                            {{ ticket.status_label || statusLabels[ticket.status] || ticket.status }}
                        </span>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-bold text-foreground pt-1">
                        {{ ticket.subjek }}
                    </h2>
                </div>

                <div class="text-xs text-muted-foreground space-y-0.5 shrink-0 sm:text-right">
                    <div>Dibuat: <span class="font-medium text-foreground">{{ formatDate(ticket.created_at) }}</span></div>
                    <div v-if="ticket.last_reply_at">
                        Pembaruan: <span class="font-medium text-foreground">{{ formatDate(ticket.last_reply_at) }}</span>
                    </div>
                </div>
            </div>

            <!-- Identitas Pelapor (Khusus Tampilan Admin) -->
            <div v-if="role === 'admin' && ticket.user" class="flex items-center justify-between gap-3 bg-muted/40 rounded-xl p-3 text-xs mb-4">
                <div class="flex items-center gap-3">
                    <div class="size-8 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-sm">
                        {{ ticket.user.name?.charAt(0).toUpperCase() }}
                    </div>
                    <div>
                        <div class="font-semibold text-foreground text-sm flex items-center gap-1.5">
                            {{ ticket.user.name }}
                        </div>
                        <div v-if="ticket.user.username" class="text-muted-foreground">@{{ ticket.user.username }}</div>
                    </div>
                </div>
                <AppButton
                    :href="`/admin/users?search=${encodeURIComponent(ticket.user.email)}`"
                    variant="outline"
                    size="xs"
                    :icon="User"
                    label="Profil Pengguna"
                    class="rounded-lg text-xs"
                />
            </div>

            <!-- Deskripsi Kendala Awal -->
            <div v-if="firstBalasan" class="space-y-2 pt-2">
                <div class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                    Deskripsi Kendala
                </div>
                <div class="text-sm leading-relaxed text-foreground whitespace-pre-wrap break-words">
                    {{ firstBalasan.pesan }}
                </div>
            </div>

            <!-- Lampiran Foto Bukti Awal (Jika Ada) -->
            <div v-if="firstBalasan && firstBalasan.lampiran && firstBalasan.lampiran.length > 0" class="space-y-2.5 pt-3 ">
                <div class="text-xs font-semibold text-muted-foreground flex items-center gap-1.5">
                    <Paperclip class="size-3.5" />
                    <span>Lampiran Gambar ({{ firstBalasan.lampiran.length }})</span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2.5">
                    <div
                        v-for="(att, attIdx) in firstBalasan.lampiran"
                        :key="att.id || attIdx"
                        class="group relative bg-muted/40 border border-border rounded-xl overflow-hidden aspect-video flex items-center justify-center cursor-pointer hover:border-primary/50 transition-all shadow-2xs"
                        @click="openLightbox(firstBalasan.lampiran, attIdx)"
                    >
                        <img
                            :src="`/documents/tickets/${att.id}`"
                            :alt="att.file_name"
                            class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                            loading="lazy"
                        />
                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2 text-white">
                            <Eye class="size-4" />
                            <span class="text-xs font-medium">Buka</span>
                        </div>
                        <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-black/70 to-transparent p-1.5 text-[10px] text-white truncate pointer-events-none">
                            {{ att.file_name }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Header Riwayat Tanggapan & Balasan (Jika Ada) -->
        <div v-if="threadReplies.length > 0" class="flex items-center gap-2 pt-1">
            <h3 class="text-xs font-bold uppercase tracking-wider text-muted-foreground">
                Riwayat Tanggapan & Balasan ({{ threadReplies.length }})
            </h3>
            <div class="h-px bg-border flex-1"></div>
        </div>

        <!-- Daftar Utas Percakapan (Thread Messages) -->
        <div v-if="threadReplies.length > 0" class="space-y-4">
            <div
                v-for="(msg, idx) in threadReplies"
                :key="msg.id || idx"
                :class="[
                    'rounded-2xl p-4 sm:p-5 transition-all',
                    msg.pengirim === 'admin'
                        ? 'bg-primary/5 dark:bg-primary/10 border border-primary/20 mr-auto max-w-4xl'
                        : 'bg-card border border-border ml-auto max-w-4xl shadow-2xs'
                ]"
            >
                <!-- Pengirim Header -->
                <div class="flex items-center justify-between gap-2 border-b border-border/50 pb-2.5 mb-3">
                    <div class="flex items-center gap-2.5">
                        <div
                            :class="[
                                'size-7 rounded-full flex items-center justify-center font-bold text-xs',
                                msg.pengirim === 'admin'
                                    ? 'bg-primary text-primary-foreground'
                                    : 'bg-muted text-foreground'
                            ]"
                        >
                            <ShieldCheck v-if="msg.pengirim === 'admin'" class="size-4" />
                            <span v-else>{{ msg.user?.name?.charAt(0).toUpperCase() || 'M' }}</span>
                        </div>
                        <div>
                            <div class="text-xs font-bold flex items-center gap-1.5">
                                <span :class="msg.pengirim === 'admin' ? 'text-primary' : 'text-foreground'">
                                    {{ msg.pengirim === 'admin' ? 'Administrator' : (msg.user?.name || 'Member') }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <span class="text-[11px] text-muted-foreground">
                        {{ formatDate(msg.created_at) }}
                    </span>
                </div>

                <!-- Isi Pesan -->
                <div class="text-sm leading-relaxed text-foreground whitespace-pre-wrap break-words">
                    {{ msg.pesan }}
                </div>

                <!-- Lampiran Gambar (Jika Ada) -->
                <div v-if="msg.lampiran && msg.lampiran.length > 0" class="mt-4 pt-3">
                    <div class="text-xs font-semibold text-muted-foreground flex items-center gap-1.5 mb-2.5">
                        <Paperclip class="size-3.5" />
                        <span>Lampiran Gambar ({{ msg.lampiran.length }})</span>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2.5">
                        <div
                            v-for="(att, attIdx) in msg.lampiran"
                            :key="att.id || attIdx"
                            class="group relative bg-muted/40 border border-border rounded-xl overflow-hidden aspect-video flex items-center justify-center cursor-pointer hover:border-primary/50 transition-all shadow-2xs"
                            @click="openLightbox(msg.lampiran, attIdx)"
                        >
                            <img
                                :src="`/documents/tickets/${att.id}`"
                                :alt="att.file_name"
                                class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                                loading="lazy"
                            />
                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2 text-white">
                                <Eye class="size-4" />
                                <span class="text-xs font-medium">Buka</span>
                            </div>
                            <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-black/70 to-transparent p-1.5 text-[10px] text-white truncate pointer-events-none">
                                {{ att.file_name }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Info Jika Belum Ada Balasan Lanjutan -->
        <div
            v-else
            class="bg-muted/20 border border-dashed border-border rounded-2xl p-5 text-center text-xs text-muted-foreground space-y-1"
        >
            <p class="font-medium text-foreground">
                {{ role === 'admin' ? 'Belum ada tanggapan lanjutan pada tiket ini.' : 'Belum ada tanggapan balasan dari tim admin.' }}
            </p>
            <p>
                {{ role === 'admin' ? 'Kirimkan tanggapan pertama untuk merespons kendala member melalui formulir di bawah.' : 'Tim teknis kami sedang meninjau kendala Anda dan akan segera merespons di sini.' }}
            </p>
        </div>

        <!-- Form Balas Pesan (Jika Masih Aktif) -->
        <div v-if="canReply" class="bg-card border border-border rounded-2xl p-4 sm:p-5 shadow-2xs space-y-4">
            <div class="flex items-center justify-between gap-2 border-b border-border pb-3">
                <h3 class="text-sm sm:text-base font-bold text-foreground flex items-center gap-2">
                    <LifeBuoy class="size-4 text-primary" />
                    <span>{{ role === 'admin' ? 'Kirim Tanggapan Admin' : 'Kirim Balasan' }}</span>
                </h3>
                <span class="text-xs text-muted-foreground font-mono">
                    {{ form.pesan.length }} / 20.000
                </span>
            </div>

            <form @submit.prevent="submitReply" class="space-y-4">
                <!-- Textarea Pesan -->
                <div>
                    <textarea
                        v-model="form.pesan"
                        rows="4"
                        maxlength="20000"
                        :placeholder="role === 'admin' ? 'Tulis tanggapan atau instruksi solusi penyelesaian kendala member...' : 'Tuliskan balasan, informasi tambahan, atau pertanyaan Anda di sini...'"
                        class="w-full rounded-xl border border-border bg-background px-3.5 py-3 text-sm text-foreground placeholder:text-muted-foreground/60 focus:outline-none focus:ring-2 focus:ring-primary/40 transition-all resize-y"
                    ></textarea>
                    <div v-if="form.errors.pesan" class="text-destructive text-xs mt-1.5 font-medium flex items-center gap-1">
                        <AlertCircle class="size-3.5" />
                        <span>{{ form.errors.pesan }}</span>
                    </div>
                </div>

                <!-- Unggah Lampiran Gambar (Hanya Tombol & Pratinjau Ringkas) -->
                <div>
                    <input
                        ref="fileInputRef"
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        multiple
                        class="hidden"
                        @change="onFileInputChange"
                    />

                    <!-- Pratinjau Gambar yang Akan Diunggah -->
                    <div v-if="imagePreviews.length > 0" class="mb-3 flex flex-wrap gap-2.5">
                        <div
                            v-for="(prev, pIdx) in imagePreviews"
                            :key="pIdx"
                            class="relative group size-16 sm:size-20 rounded-xl overflow-hidden border border-border bg-muted shadow-2xs shrink-0"
                        >
                            <img :src="prev.url" :alt="prev.name" class="w-full h-full object-cover" />
                            <button
                                type="button"
                                class="absolute top-1 right-1 size-5 rounded-full bg-destructive text-destructive-foreground flex items-center justify-center opacity-90 hover:opacity-100 shadow-sm transition-opacity cursor-pointer"
                                title="Hapus foto ini"
                                @click.stop="removeImage(pIdx)"
                            >
                                <X class="size-3" />
                            </button>
                            <div class="absolute bottom-0 inset-x-0 bg-black/70 text-[9px] text-white px-1 py-0.5 truncate pointer-events-none text-center" :title="prev.name">
                                {{ prev.name }}
                            </div>
                        </div>
                    </div>

                    <div v-if="form.errors.images" class="text-destructive text-xs mb-2 font-medium flex items-center gap-1">
                        <AlertCircle class="size-3.5" />
                        <span>{{ form.errors.images }}</span>
                    </div>

                    <!-- Toolbar Aksi Bawah: Tombol Lampirkan & Tombol Kirim -->
                    <div class="flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-border/60">
                        <div class="flex items-center gap-2">
                            <AppButton
                                type="custom"
                                variant="outline"
                                size="sm"
                                :icon="Paperclip"
                                label="Unggah Gambar"
                                class="rounded-xl text-xs"
                                :disabled="form.processing || isCompressing"
                                @click="fileInputRef?.click()"
                            />
                            <span v-if="imagePreviews.length > 0" class="text-xs text-muted-foreground font-medium">
                                {{ imagePreviews.length }} gambar dipilih
                            </span>
                            <span v-else class="text-[11px] text-muted-foreground hidden sm:inline">
                                JPG, PNG, WEBP maks. 10 MB (opsional)
                            </span>
                        </div>

                        <AppButton
                            type="submit"
                            submit
                            :icon="Send"
                            :label="isCompressing ? 'Mengompresi Gambar...' : (role === 'admin' ? 'Kirim Tanggapan' : 'Kirim Balasan')"
                            :loading="form.processing || isCompressing"
                            :disabled="form.processing || isCompressing || (!form.pesan.trim() && form.images.length === 0)"
                            class="rounded-xl px-5 text-xs sm:text-sm"
                        />
                    </div>
                </div>
            </form>
        </div>

        <!-- Lightbox Modal untuk Pratinjau Penuh Gambar -->
        <AppModal
            :show="lightbox.isOpen"
            max-width="4xl"
            close-on-backdrop
            @close="closeLightbox"
        >
            <template #header>
                <div class="shrink-0 px-4 py-3 sm:px-6 sm:py-4 border-b border-border flex items-center justify-between gap-3">
                    <div class="text-sm sm:text-base font-bold text-foreground truncate max-w-md sm:max-w-xl">
                        {{ currentLightboxImage?.file_name || 'Lampiran Berkas' }}
                    </div>
                    <div class="flex items-center gap-1.5 shrink-0">
                        <a
                            v-if="currentLightboxImage?.id"
                            :href="`/documents/tickets/${currentLightboxImage.id}`"
                            download
                            class="p-1.5 text-muted-foreground hover:text-foreground hover:bg-muted rounded-lg transition-colors cursor-pointer"
                            title="Unduh berkas gambar"
                        >
                            <Download class="size-4" />
                        </a>
                        <button
                            type="button"
                            class="p-1.5 text-muted-foreground hover:text-foreground hover:bg-muted rounded-lg transition-colors cursor-pointer"
                            title="Tutup"
                            aria-label="Tutup"
                            @click="closeLightbox"
                        >
                            <X class="size-4" />
                        </button>
                    </div>
                </div>
            </template>

            <div class="space-y-4">
                <div class="relative flex items-center justify-center min-h-[300px] max-h-[75vh] bg-black/5 dark:bg-black/30 rounded-xl overflow-hidden p-2">
                    <img
                        v-if="currentLightboxImage?.id"
                        :src="`/documents/tickets/${currentLightboxImage.id}`"
                        :alt="currentLightboxImage.file_name"
                        class="max-w-full max-h-[70vh] object-contain rounded-lg shadow-lg"
                    />

                    <!-- Tombol Navigasi Lightbox -->
                    <button
                        v-if="lightbox.images.length > 1 && lightbox.currentIndex > 0"
                        type="button"
                        class="absolute left-3 top-1/2 -translate-y-1/2 size-10 rounded-full bg-black/60 text-white hover:bg-black/80 flex items-center justify-center transition-all shadow-md cursor-pointer"
                        @click="prevLightbox"
                    >
                        <ChevronLeft class="size-5" />
                    </button>

                    <button
                        v-if="lightbox.images.length > 1 && lightbox.currentIndex < lightbox.images.length - 1"
                        type="button"
                        class="absolute right-3 top-1/2 -translate-y-1/2 size-10 rounded-full bg-black/60 text-white hover:bg-black/80 flex items-center justify-center transition-all shadow-md cursor-pointer"
                        @click="nextLightbox"
                    >
                        <ChevronRight class="size-5" />
                    </button>
                </div>

                <div v-if="lightbox.images.length > 1" class="text-center text-xs text-muted-foreground font-medium">
                    Gambar {{ lightbox.currentIndex + 1 }} dari {{ lightbox.images.length }}
                </div>
            </div>
        </AppModal>
    </div>
</template>
