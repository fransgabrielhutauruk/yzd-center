import { ref } from 'vue';

/**
 * Memuat detail akun copytrade (kredensial tier 3 + riwayat saldo) dari endpoint admin.
 * Hasil lama diabaikan jika permintaan baru dimulai (token).
 */
export function useCopytradeDetail() {
    const detail = ref(null);
    const loading = ref(false);
    const error = ref('');
    let token = 0;

    const load = async (id) => {
        const current = ++token;
        loading.value = true;
        error.value = '';
        detail.value = null;
        try {
            const res = await fetch(route('admin.copytrade.detail', { id }), {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (!res.ok) throw new Error('bad status');
            const data = await res.json();
            if (current === token) detail.value = data;
        } catch {
            if (current === token) error.value = 'Gagal memuat detail akun. Coba lagi.';
        } finally {
            if (current === token) loading.value = false;
        }
    };

    const reset = () => {
        token++;
        detail.value = null;
        loading.value = false;
        error.value = '';
    };

    return { detail, loading, error, load, reset };
}
