<script setup>
import { useForm, Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AppButton from '@/Components/Common/AppButton.vue';
import {
    Building2,
    Percent,
    CreditCard,
    User,
    Info,
} from 'lucide-vue-next';

const props = defineProps({
    settings: { type: Object, required: true },
});

const form = useForm({
    fee_persen: props.settings?.fee_persen ? parseInt(props.settings.fee_persen, 10) : 30,
    nama_bank: props.settings?.nama_bank || '',
    nomor_rekening: props.settings?.nomor_rekening || '',
    atas_nama: props.settings?.atas_nama || '',
});

const submit = () => {
    form.put(route('admin.settings.master_fee.update'), {
        preserveScroll: true,
        onSuccess: () => {
            // Flash message ditangkap otomatis oleh AppToaster
        },
    });
};
</script>

<template>
    <AdminLayout
        title="Pengaturan Master Fee"
        subtitle="Atur persentase bagi hasil (Performance Fee) dan rekening bank penampung pembayaran dari member."
        :breadcrumbs="[
            { label: 'Pengaturan Sistem' },
            { label: 'Pengaturan Master Fee' },
        ]"
    >
        <Head title="Pengaturan Master Fee" />

        <div class="max-w-4xl space-y-6">
            <!-- Kotak Informasi Aturan Penguncian Persentase -->
            <div class="rounded-2xl border border-primary/20 bg-primary/5 p-4 sm:p-5 flex items-start gap-3.5 text-xs">
                <div class="rounded-xl bg-primary/10 p-2 text-primary shrink-0 mt-0.5">
                    <Info class="h-4 w-4" />
                </div>
                <div class="space-y-1">
                    <h4 class="font-bold text-foreground">Kebijakan Penguncian Persentase Fee</h4>
                    <p class="text-muted-foreground leading-relaxed">
                        Perubahan persentase fee di halaman ini hanya akan berlaku untuk tagihan yang diterbitkan <strong>setelah</strong> perubahan disimpan. Seluruh tagihan historis yang sudah pernah terbit akan tetap mengunci persentase fee masing-masing guna menjaga akurasi pembukuan dan komisi kemitraan.
                    </p>
                </div>
            </div>

            <!-- Formulir Pengaturan -->
            <form @submit.prevent="submit" class="rounded-2xl border border-border bg-card p-5 sm:p-6 shadow-xs space-y-6">
                <!-- 1. Persentase Bagi Hasil -->
                <div class="space-y-2">
                    <label class="text-xs font-bold text-foreground flex items-center gap-1.5">
                        <Percent class="h-3.5 w-3.5 text-primary" />
                        <span>Persentase Master Fee (%) <span class="text-destructive">*</span></span>
                    </label>
                    <div class="relative max-w-xs">
                        <input
                            v-model.number="form.fee_persen"
                            type="number"
                            step="1"
                            min="1"
                            max="100"
                            placeholder="30"
                            class="w-full rounded-lg border border-input bg-background py-2 pr-8 pl-3 font-mono text-sm focus:ring-2 focus:ring-primary focus:outline-none"
                        />
                        <span class="absolute inset-y-0 right-0 flex items-center pr-3 font-bold text-xs text-muted-foreground">%</span>
                    </div>
                    <p class="text-[11px] text-muted-foreground">
                        Default: 30%. Nilai persentase yang dikenakan dari profit bersih bulanan investor copytrade.
                    </p>
                    <p v-if="form.errors.fee_persen" class="text-xs text-destructive">
                        {{ form.errors.fee_persen }}
                    </p>
                </div>

                <div class="border-t border-border pt-4 space-y-4">
                    <h3 class="text-sm font-bold text-foreground flex items-center gap-1.5">
                        <Building2 class="h-4 w-4 text-primary" />
                        <span>Rekening Bank Penampung (Master)</span>
                    </h3>
                    <p class="text-xs text-muted-foreground">
                        Informasi rekening ini akan ditampilkan kepada seluruh investor saat membuka rincian pembayaran tagihan Master Fee.
                    </p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Nama Bank -->
                        <div class="space-y-1.5">
                            <label class="text-xs font-semibold text-foreground">
                                Nama Bank <span class="text-destructive">*</span>
                            </label>
                            <input
                                v-model="form.nama_bank"
                                type="text"
                                placeholder="Contoh: BCA, Mandiri, BNI"
                                class="w-full rounded-lg border border-input bg-background px-3 py-2 text-xs focus:ring-2 focus:ring-primary focus:outline-none"
                            />
                            <p v-if="form.errors.nama_bank" class="text-xs text-destructive">
                                {{ form.errors.nama_bank }}
                            </p>
                        </div>

                        <!-- Nomor Rekening -->
                        <div class="space-y-1.5">
                            <label class="text-xs font-semibold text-foreground">
                                Nomor Rekening <span class="text-destructive">*</span>
                            </label>
                            <input
                                v-model="form.nomor_rekening"
                                type="text"
                                placeholder="Contoh: 1234567890"
                                class="w-full rounded-lg border border-input bg-background px-3 py-2 text-xs font-mono focus:ring-2 focus:ring-primary focus:outline-none"
                            />
                            <p v-if="form.errors.nomor_rekening" class="text-xs text-destructive">
                                {{ form.errors.nomor_rekening }}
                            </p>
                        </div>

                        <!-- Atas Nama Rekening -->
                        <div class="space-y-1.5 sm:col-span-2">
                            <label class="text-xs font-semibold text-foreground">
                                Atas Nama Pemilik Rekening <span class="text-destructive">*</span>
                            </label>
                            <input
                                v-model="form.atas_nama"
                                type="text"
                                placeholder="Contoh: Master YZD Trading / PT YZD..."
                                class="w-full rounded-lg border border-input bg-background px-3 py-2 text-xs focus:ring-2 focus:ring-primary focus:outline-none"
                            />
                            <p v-if="form.errors.atas_nama" class="text-xs text-destructive">
                                {{ form.errors.atas_nama }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="border-t border-border pt-4 flex items-center justify-end">
                    <AppButton
                        type="save"
                        submit
                        :loading="form.processing"
                        loading-text="Menyimpan..."
                        label="Simpan Pengaturan"
                    />
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
