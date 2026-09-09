<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Button } from '@/Components/ui/button';
import {
    Link2,
    Plus,
    Search,
    Copy,
    Check,
    Trash2,
    Clock,
    Users,
    UserCheck,
    AlertCircle,
    X,
    ExternalLink
} from 'lucide-vue-next';

const props = defineProps({
    invitations: {
        type: Array,
        default: () => [],
    },
});

const searchQuery = ref('');
const copiedToken = ref(null);
const isCreateModalOpen = ref(false);
const deletingId = ref(null);

const form = useForm({
    label: '',
    target_role: 'investor',
    kuota_maksimal: 1,
    expired_hours: 24,
});

const filteredInvitations = computed(() => {
    if (!searchQuery.value.trim()) {
        return props.invitations;
    }
    const q = searchQuery.value.toLowerCase();
    return props.invitations.filter((inv) =>
        inv.label.toLowerCase().includes(q) ||
        inv.token.toLowerCase().includes(q) ||
        inv.target_role.toLowerCase().includes(q)
    );
});

const openCreateModal = () => {
    form.reset();
    form.clearErrors();
    form.target_role = 'investor';
    form.kuota_maksimal = 1;
    form.expired_hours = 24;
    isCreateModalOpen.value = true;
};

const closeCreateModal = () => {
    isCreateModalOpen.value = false;
};

const submitCreate = () => {
    form.post('/admin/invitations', {
        preserveScroll: true,
        onSuccess: () => {
            closeCreateModal();
        },
    });
};

const copyLink = async (invitation) => {
    try {
        await navigator.clipboard.writeText(invitation.full_url);
        copiedToken.value = invitation.token;
        setTimeout(() => {
            if (copiedToken.value === invitation.token) {
                copiedToken.value = null;
            }
        }, 2500);
    } catch (err) {
        console.error('Gagal menyalin tautan:', err);
    }
};

const confirmDelete = (invitation) => {
    if (confirm(`Apakah Anda yakin ingin menghapus tautan undangan "${invitation.label}"? Calon pengguna yang belum mendaftar tidak akan bisa menggunakan tautan ini.`)) {
        deletingId.value = invitation.invitation_id;
        router.delete(`/admin/invitations/${invitation.invitation_id}`, {
            preserveScroll: true,
            onFinish: () => {
                deletingId.value = null;
            },
        });
    }
};

const getStatusBadgeClass = (statusKey) => {
    switch (statusKey) {
        case 'active':
            return 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20';
        case 'quota_full':
            return 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20';
        case 'expired':
            return 'bg-destructive/10 text-destructive border-destructive/20';
        default:
            return 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/20';
    }
};
</script>

<template>
    <AdminLayout title="Manajemen Tautan Undangan">
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold tracking-tight text-foreground">
                        Tautan Undangan Pendaftaran
                    </h2>
                    <p class="text-xs sm:text-sm text-muted-foreground mt-0.5">
                        Kelola token pendaftaran eksklusif tertutup (*Closed Registration*) untuk Investor dan Affiliator.
                    </p>
                </div>

                <Button
                    type="button"
                    @click="openCreateModal"
                    class="bg-primary-600 hover:bg-primary-700 text-white font-medium py-2 px-4 rounded-lg shadow-sm flex items-center gap-2 self-start sm:self-auto transition-colors"
                >
                    <Plus class="w-4 h-4" />
                    <span>Buat Undangan Baru</span>
                </Button>
            </div>

            <!-- Search and Filter Bar -->
            <div class="bg-card rounded-xl border border-border p-4 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="relative w-full sm:w-80">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-muted-foreground">
                        <Search class="w-4 h-4" />
                    </div>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari label atau token..."
                        class="w-full pl-9 pr-3 py-1.5 text-xs bg-background border border-input rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all"
                    />
                </div>

                <div class="text-xs text-muted-foreground self-end sm:self-center">
                    Total: <strong class="text-foreground font-semibold">{{ filteredInvitations.length }}</strong> tautan
                </div>
            </div>

            <!-- Invitations Table Card -->
            <div class="bg-card rounded-xl border border-border shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-muted/50 border-b border-border text-muted-foreground font-semibold uppercase tracking-wider">
                            <tr>
                                <th class="py-3 px-4">Label Kegiatan</th>
                                <th class="py-3 px-4">Hak Akses</th>
                                <th class="py-3 px-4">Penggunaan Kuota</th>
                                <th class="py-3 px-4">Masa Berlaku</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4">Tautan Registrasi</th>
                                <th class="py-3 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr
                                v-for="item in filteredInvitations"
                                :key="item.invitation_id"
                                class="hover:bg-muted/30 transition-colors"
                            >
                                <!-- Label -->
                                <td class="py-3 px-4 font-medium text-foreground">
                                    <div class="flex items-center gap-2">
                                        <Link2 class="w-4 h-4 text-primary-500 flex-shrink-0" />
                                        <span class="truncate max-w-[200px]" :title="item.label">{{ item.label }}</span>
                                    </div>
                                    <span class="text-[10px] text-muted-foreground font-mono pl-6">
                                        Dibuat: {{ item.created_at_label }}
                                    </span>
                                </td>

                                <!-- Target Role -->
                                <td class="py-3 px-4">
                                    <span
                                        :class="[
                                            'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full font-semibold text-[11px] border shadow-2xs',
                                            item.target_role === 'affiliator'
                                                ? 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/20'
                                                : 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20'
                                        ]"
                                    >
                                        <component
                                            :is="item.target_role === 'affiliator' ? UserCheck : Users"
                                            class="w-3 h-3"
                                        />
                                        {{ item.target_role_label }}
                                    </span>
                                </td>

                                <!-- Usage Quota -->
                                <td class="py-3 px-4">
                                    <div class="space-y-1">
                                        <div class="flex items-center justify-between font-mono font-medium text-foreground">
                                            <span>{{ item.usage_label }}</span>
                                            <span class="text-[10px] text-muted-foreground">
                                                {{ Math.round((item.total_terpakai / item.kuota_maksimal) * 100) }}%
                                            </span>
                                        </div>
                                        <div class="w-24 bg-muted rounded-full h-1.5 overflow-hidden">
                                            <div
                                                class="bg-primary-600 h-1.5 rounded-full transition-all"
                                                :style="{ width: `${Math.min(100, (item.total_terpakai / item.kuota_maksimal) * 100)}%` }"
                                            ></div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Expired Date -->
                                <td class="py-3 px-4 text-muted-foreground">
                                    <div class="flex items-center gap-1.5">
                                        <Clock class="w-3.5 h-3.5 text-muted-foreground" />
                                        <span>{{ item.expired_at_label }}</span>
                                    </div>
                                </td>

                                <!-- Status Badge -->
                                <td class="py-3 px-4">
                                    <span
                                        :class="[
                                            'inline-flex items-center gap-1 px-2 py-0.5 rounded-md font-semibold text-[11px] border',
                                            getStatusBadgeClass(item.status_key)
                                        ]"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                        {{ item.status_label }}
                                    </span>
                                </td>

                                <!-- Link & Copy Button -->
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-2">
                                        <code class="font-mono text-[11px] bg-muted px-2 py-1 rounded text-muted-foreground">
                                            {{ item.masked_token }}
                                        </code>
                                        <button
                                            type="button"
                                            @click="copyLink(item)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[11px] font-medium transition-colors"
                                            :class="[
                                                copiedToken === item.token
                                                    ? 'bg-emerald-600 text-white shadow-xs'
                                                    : 'bg-primary-50 dark:bg-primary-950/60 text-primary-600 dark:text-primary-400 hover:bg-primary-100 dark:hover:bg-primary-900 border border-primary-500/20'
                                            ]"
                                            :title="item.full_url"
                                        >
                                            <Check v-if="copiedToken === item.token" class="w-3 h-3" />
                                            <Copy v-else class="w-3 h-3" />
                                            <span>{{ copiedToken === item.token ? 'Tersalin!' : 'Salin' }}</span>
                                        </button>
                                    </div>
                                </td>

                                <!-- Action Buttons -->
                                <td class="py-3 px-4 text-right">
                                    <button
                                        type="button"
                                        @click="confirmDelete(item)"
                                        :disabled="deletingId === item.invitation_id"
                                        class="p-1.5 text-muted-foreground hover:text-destructive hover:bg-destructive/10 rounded-md transition-colors disabled:opacity-50"
                                        title="Hapus Tautan"
                                    >
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="filteredInvitations.length === 0">
                                <td colspan="7" class="py-12 text-center text-muted-foreground">
                                    <div class="max-w-xs mx-auto space-y-3">
                                        <div class="w-12 h-12 rounded-full bg-muted flex items-center justify-center mx-auto text-muted-foreground">
                                            <Link2 class="w-6 h-6" />
                                        </div>
                                        <p class="font-medium text-foreground">Tidak Ada Tautan Undangan</p>
                                        <p class="text-xs">
                                            Belum ada tautan undangan yang dibuat atau tidak ada data yang cocok dengan pencarian Anda.
                                        </p>
                                        <Button
                                            type="button"
                                            @click="openCreateModal"
                                            size="sm"
                                            class="bg-primary-600 hover:bg-primary-700 text-white font-medium py-1.5 px-3 rounded-lg text-xs"
                                        >
                                            + Buat Undangan Sekarang
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal Buat Undangan Baru -->
        <transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isCreateModalOpen"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
                @click.self="closeCreateModal"
            >
                <div class="bg-card text-card-foreground w-full max-w-lg rounded-2xl border border-border shadow-2xl overflow-hidden animate-in fade-in-0 zoom-in-95 duration-200">
                    <!-- Modal Header -->
                    <div class="px-6 py-4 border-b border-border flex items-center justify-between">
                        <div>
                            <h3 class="font-bold text-base text-foreground">
                                Buat Tautan Undangan Baru
                            </h3>
                            <p class="text-xs text-muted-foreground">
                                Tautan ini akan menghasilkan token pendaftaran khusus.
                            </p>
                        </div>
                        <button
                            type="button"
                            @click="closeCreateModal"
                            class="p-1 rounded-lg text-muted-foreground hover:text-foreground hover:bg-muted"
                        >
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- Modal Body Form -->
                    <form @submit.prevent="submitCreate" class="p-6 space-y-4">
                        <!-- Label / Nama Kegiatan -->
                        <div class="space-y-1.5">
                            <label for="label" class="block text-xs font-semibold text-foreground">
                                Label / Nama Kegiatan <span class="text-destructive">*</span>
                            </label>
                            <input
                                id="label"
                                v-model="form.label"
                                type="text"
                                required
                                placeholder="Contoh: Batch 1 Calon Investor Surabaya"
                                class="w-full px-3 py-2 text-sm bg-background border border-input rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all"
                                :class="{ 'border-destructive focus:ring-destructive': form.errors.label }"
                            />
                            <p v-if="form.errors.label" class="text-xs text-destructive font-medium">
                                {{ form.errors.label }}
                            </p>
                        </div>

                        <!-- Target Peran (Role) -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-semibold text-foreground">
                                Hak Akses / Peran Target <span class="text-destructive">*</span>
                            </label>
                            <div class="grid grid-cols-2 gap-3">
                                <label
                                    :class="[
                                        'flex items-center gap-2.5 p-3 rounded-xl border cursor-pointer transition-all',
                                        form.target_role === 'investor'
                                            ? 'border-primary-600 bg-primary-500/10 text-foreground font-semibold ring-1 ring-primary-600'
                                            : 'border-input hover:bg-muted/50 text-muted-foreground'
                                    ]"
                                >
                                    <input
                                        v-model="form.target_role"
                                        type="radio"
                                        value="investor"
                                        class="sr-only"
                                    />
                                    <Users class="w-4 h-4 text-blue-600 dark:text-blue-400 flex-shrink-0" />
                                    <div class="text-xs">
                                        <p class="text-foreground">Investor</p>
                                        <p class="text-[10px] text-muted-foreground font-normal">Anggota pemodal</p>
                                    </div>
                                </label>

                                <label
                                    :class="[
                                        'flex items-center gap-2.5 p-3 rounded-xl border cursor-pointer transition-all',
                                        form.target_role === 'affiliator'
                                            ? 'border-primary-600 bg-primary-500/10 text-foreground font-semibold ring-1 ring-primary-600'
                                            : 'border-input hover:bg-muted/50 text-muted-foreground'
                                    ]"
                                >
                                    <input
                                        v-model="form.target_role"
                                        type="radio"
                                        value="affiliator"
                                        class="sr-only"
                                    />
                                    <UserCheck class="w-4 h-4 text-purple-600 dark:text-purple-400 flex-shrink-0" />
                                    <div class="text-xs">
                                        <p class="text-foreground">Affiliator</p>
                                        <p class="text-[10px] text-muted-foreground font-normal">Mitra referral</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Grid: Kuota & Masa Berlaku -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Kuota Maksimal -->
                            <div class="space-y-1.5">
                                <label for="kuota_maksimal" class="block text-xs font-semibold text-foreground">
                                    Kuota Pendaftaran <span class="text-destructive">*</span>
                                </label>
                                <input
                                    id="kuota_maksimal"
                                    v-model.number="form.kuota_maksimal"
                                    type="number"
                                    min="1"
                                    max="10000"
                                    required
                                    class="w-full px-3 py-2 text-sm bg-background border border-input rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all"
                                    :class="{ 'border-destructive focus:ring-destructive': form.errors.kuota_maksimal }"
                                />
                                <p class="text-[10px] text-muted-foreground">Jumlah maksimal pendaftar.</p>
                            </div>

                            <!-- Durasi Masa Berlaku -->
                            <div class="space-y-1.5">
                                <label for="expired_hours" class="block text-xs font-semibold text-foreground">
                                    Masa Berlaku
                                </label>
                                <select
                                    id="expired_hours"
                                    v-model.number="form.expired_hours"
                                    class="w-full px-3 py-2 text-sm bg-background border border-input rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all"
                                >
                                    <option :value="24">24 Jam (1 Hari)</option>
                                    <option :value="72">72 Jam (3 Hari)</option>
                                    <option :value="168">7 Hari (1 Minggu)</option>
                                    <option :value="720">30 Hari (1 Bulan)</option>
                                    <option :value="0">Tanpa Batas Waktu</option>
                                </select>
                                <p class="text-[10px] text-muted-foreground">Otomatis kedaluwarsa setelah durasi.</p>
                            </div>
                        </div>

                        <!-- Modal Actions -->
                        <div class="pt-4 border-t border-border flex items-center justify-end gap-3">
                            <Button
                                type="button"
                                variant="outline"
                                @click="closeCreateModal"
                                class="px-4 py-2 text-xs font-medium"
                            >
                                Batal
                            </Button>

                            <Button
                                type="submit"
                                :disabled="form.processing"
                                class="bg-primary-600 hover:bg-primary-700 text-white px-5 py-2 text-xs font-semibold rounded-lg shadow-sm disabled:opacity-50"
                            >
                                {{ form.processing ? 'Menyimpan...' : 'Simpan & Buat Tautan' }}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </transition>
    </AdminLayout>
</template>
