<script setup>
import { ref } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import MemberLayout from '@/Layouts/MemberLayout.vue';
import AppPageHeader from '@/Components/Common/AppPageHeader.vue';
import AppButton from '@/Components/Common/AppButton.vue';
import { compressImageFile, formatBytes } from '@/lib/imageCompression';
import {
    LifeBuoy,
    Send,
    Image as ImageIcon,
    X,
    AlertCircle,
    Info,
} from 'lucide-vue-next';

const fileInputRef = ref(null);
const isCompressing = ref(false);
const imagePreviews = ref([]);
const isDragging = ref(false);
const dragCounter = ref(0);

const form = useForm({
    subjek: '',
    pesan: '',
    images: [],
});

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

const onDragEnter = (e) => {
    e.preventDefault();
    dragCounter.value++;
    if (e.dataTransfer?.items && e.dataTransfer.items.length > 0) {
        isDragging.value = true;
    }
};

const onDragOver = (e) => {
    e.preventDefault();
};

const onDragLeave = (e) => {
    e.preventDefault();
    dragCounter.value--;
    if (dragCounter.value <= 0) {
        isDragging.value = false;
        dragCounter.value = 0;
    }
};

const onDrop = (e) => {
    e.preventDefault();
    isDragging.value = false;
    dragCounter.value = 0;
    if (e.dataTransfer?.files && e.dataTransfer.files.length > 0) {
        handleFiles(e.dataTransfer.files);
    }
};

const submitTicket = () => {
    form.post(route('member.tickets.store'), {
        forceFormData: true,
    });
};
</script>

<template>
    <MemberLayout title="Buat Tiket Bantuan">
        <Head title="Buat Tiket Bantuan Baru" />

        <div class="space-y-6 max-w-4xl mx-auto">
            <!-- Header Halaman -->
            <AppPageHeader
                title="Buat Tiket Bantuan Baru"
                subtitle="Jelaskan kendala Anda secara rinci agar tim teknis kami dapat menindaklanjuti secara akurat."
            >
                <template #actions>
                    <AppButton
                        type="back"
                        label="Kembali"
                        :href="route('member.tickets.index')"
                        class="rounded-xl text-xs"
                    />
                </template>
            </AppPageHeader>

            <!-- Informasi Panduan -->
            <div class="bg-primary/5 dark:bg-primary/10 border border-primary/20 rounded-2xl p-4 flex items-start gap-3">
                <Info class="size-5 shrink-0 text-primary mt-0.5" />
                <div class="text-xs sm:text-sm text-foreground space-y-1">
                    <span class="font-bold">Tips Pelaporan:</span>
                    <p class="text-muted-foreground leading-relaxed">
                        Sertakan tangkapan layar (screenshot) bukti atau dokumen terkait, serta detail langkah yang Anda lakukan sebelum kendala muncul.
                    </p>
                </div>
            </div>

            <!-- Formulir Tiket -->
            <form @submit.prevent="submitTicket" class="bg-card border border-border rounded-2xl p-5 sm:p-7 shadow-2xs space-y-6">
                <!-- Subjek Kendala -->
                <div class="space-y-1.5">
                    <label class="block text-xs sm:text-sm font-semibold text-foreground">
                        Subjek Kendala <span class="text-destructive">*</span>
                    </label>
                    <input
                        v-model="form.subjek"
                        type="text"
                        maxlength="255"
                        placeholder="Contoh: Gagal Melakukan Pengajuan Klaim Komisi"
                        class="w-full rounded-xl border border-border bg-background px-3.5 py-2.5 text-sm text-foreground placeholder:text-muted-foreground/60 focus:outline-none focus:ring-2 focus:ring-primary/40 transition-all"
                        required
                    />
                    <div v-if="form.errors.subjek" class="text-destructive text-xs font-medium flex items-center gap-1">
                        <AlertCircle class="size-3.5" />
                        <span>{{ form.errors.subjek }}</span>
                    </div>
                </div>

                <!-- Deskripsi / Pesan -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs sm:text-sm font-semibold text-foreground">
                            Deskripsi Lengkap Kendala <span class="text-destructive">*</span>
                        </label>
                        <span class="text-xs text-muted-foreground font-mono">
                            {{ form.pesan.length }} / 20.000
                        </span>
                    </div>
                    <textarea
                        v-model="form.pesan"
                        rows="6"
                        maxlength="20000"
                        placeholder="Tuliskan kronologi kendala secara rinci..."
                        class="w-full rounded-xl border border-border bg-background px-3.5 py-3 text-sm text-foreground placeholder:text-muted-foreground/60 focus:outline-none focus:ring-2 focus:ring-primary/40 transition-all resize-y"
                        required
                    ></textarea>
                    <div v-if="form.errors.pesan" class="text-destructive text-xs font-medium flex items-center gap-1">
                        <AlertCircle class="size-3.5" />
                        <span>{{ form.errors.pesan }}</span>
                    </div>
                </div>

                <!-- Unggah Foto Bukti Kendala -->
                <div class="space-y-2">
                    <label class="block text-xs sm:text-sm font-semibold text-foreground">
                        Lampiran Foto Bukti Kendala (Opsional)
                    </label>

                    <input
                        ref="fileInputRef"
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        multiple
                        class="hidden"
                        @change="onFileInputChange"
                    />

                    <!-- Dropzone -->
                    <div
                        role="button"
                        tabindex="0"
                        :class="[
                            'border-2 border-dashed rounded-xl p-6 text-center transition-all cursor-pointer select-none',
                            isDragging
                                ? 'border-primary bg-primary/10'
                                : 'border-border hover:border-primary/50 hover:bg-muted/40'
                        ]"
                        @click="fileInputRef?.click()"
                        @keydown.enter.prevent="fileInputRef?.click()"
                        @keydown.space.prevent="fileInputRef?.click()"
                        @dragenter="onDragEnter"
                        @dragover="onDragOver"
                        @dragleave="onDragLeave"
                        @drop="onDrop"
                    >
                        <div class="flex flex-col items-center justify-center gap-2 text-muted-foreground">
                            <ImageIcon class="size-8 text-muted-foreground" />
                            <div class="text-sm font-medium text-foreground">
                                <span class="text-primary font-semibold">Klik untuk memilih</span> atau seret foto bukti ke area ini
                            </div>
                            <p class="text-xs text-muted-foreground">
                                Format JPG, PNG, WEBP (maksimal 10 MB per foto)
                            </p>
                        </div>
                    </div>

                    <div v-if="form.errors.images" class="text-destructive text-xs font-medium flex items-center gap-1">
                        <AlertCircle class="size-3.5" />
                        <span>{{ form.errors.images }}</span>
                    </div>

                    <!-- Pratinjau Foto yang Dipilih -->
                    <div v-if="imagePreviews.length > 0" class="mt-3 grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-3">
                        <div
                            v-for="(prev, pIdx) in imagePreviews"
                            :key="pIdx"
                            class="relative group aspect-square rounded-xl overflow-hidden border border-border bg-muted shadow-2xs"
                        >
                            <img :src="prev.url" :alt="prev.name" class="w-full h-full object-cover" />
                            <button
                                type="button"
                                class="absolute top-1 right-1 size-6 rounded-full bg-destructive text-destructive-foreground flex items-center justify-center opacity-90 hover:opacity-100 shadow-sm"
                                title="Hapus foto ini"
                                @click.stop="removeImage(pIdx)"
                            >
                                <X class="size-3.5" />
                            </button>
                            <div class="absolute bottom-0 inset-x-0 bg-black/70 text-[9px] text-white px-1.5 py-0.5 truncate pointer-events-none text-center" :title="prev.name">
                                {{ prev.name }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tombol Aksi Formulir -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-border">
                    <AppButton
                        type="cancel"
                        :href="route('member.tickets.index')"
                        class="rounded-xl px-5"
                    />
                    <AppButton
                        type="submit"
                        submit
                        :icon="Send"
                        :label="isCompressing ? 'Mengompresi Foto...' : 'Kirim Tiket Bantuan'"
                        :loading="form.processing || isCompressing"
                        :disabled="form.processing || isCompressing"
                        class="rounded-xl px-6"
                    />
                </div>
            </form>
        </div>
    </MemberLayout>
</template>
