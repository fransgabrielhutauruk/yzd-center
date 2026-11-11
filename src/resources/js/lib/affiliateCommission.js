/**
 * Library Fungsi Murni Perhitungan Simulasi Komisi Afiliator YZD Center.
 * Mengikuti skema 4-Tier komisi berbasis modal riil (single source of truth: KlaimKomisiItem).
 */

/**
 * Tentukan tier komisi berdasarkan nominal modal dalam Rupiah.
 *
 * @param {number} modalIdr
 * @param {Array<Object>} tiers - Definisi tiers dari backend (urut Tier 1 ke Tier 4)
 * @returns {Object} Tier terpilih
 */
export function determineTier(modalIdr, tiers = []) {
    const validModal = Number(modalIdr) || 0;

    if (!Array.isArray(tiers) || tiers.length === 0) {
        // Fallback default jika props belum siap
        if (validModal > 10_000_000_000) return { tier_level: 1, persentase_tier: 50, label: 'Tier 1 (> Rp 10 Milyar) - Komisi 50%' };
        if (validModal >= 1_000_000_000) return { tier_level: 2, persentase_tier: 40, label: 'Tier 2 (Rp 1 Milyar - Rp 10 Milyar) - Komisi 40%' };
        if (validModal >= 100_000_000) return { tier_level: 3, persentase_tier: 30, label: 'Tier 3 (Rp 100 Juta - Rp 1 Milyar) - Komisi 30%' };
        return { tier_level: 4, persentase_tier: 20, label: 'Tier 4 (< Rp 100 Juta) - Komisi 20%' };
    }

    for (const tier of tiers) {
        if (tier.is_strict_greater) {
            if (validModal > Number(tier.min_idr)) {
                return tier;
            }
        } else if (tier.min_idr !== null && tier.min_idr !== undefined && validModal >= Number(tier.min_idr)) {
            return tier;
        }
    }

    // Default ke tier terakhir (Tier 4)
    return tiers[tiers.length - 1];
}

/**
 * Simulasi komisi untuk 1 investor selama N bulan (default 3 bulan).
 *
 * @param {Object} params
 * @param {number} params.modalIdr - Modal awal investor dalam IDR
 * @param {number} params.profitPct - Persentase profit bulanan (contoh: 5 untuk 5%)
 * @param {Array<Object>} params.tiers - Definisi tiers dari backend
 * @param {number} [params.wdPct=60] - Persentase penarikan profit (WD) tiap bulan
 * @param {number} [params.feePct=30] - Persentase fee Master Trader dari profit
 * @param {number} [params.months=3] - Durasi simulasi (jendela komisi afiliator)
 * @returns {Object}
 */
export function simulateInvestor({
    modalIdr,
    profitPct,
    tiers = [],
    wdPct = 60,
    feePct = 30,
    months = 3,
}) {
    const rawModal = Number(modalIdr);
    const validModal = !isNaN(rawModal) && rawModal > 0 ? rawModal : 0;
    const tier = determineTier(validModal, tiers);

    let currentModal = validModal;
    const rows = [];
    let totalKomisi = 0;

    for (let m = 1; m <= months; m++) {
        if (validModal <= 0) {
            rows.push({
                bulan: m,
                modal: 0,
                profit: 0,
                feeMaster: 0,
                komisi: 0,
            });
            continue;
        }

        const profit = currentModal * (Number(profitPct) / 100);
        const feeMaster = profit * (Number(feePct) / 100);
        const komisi = feeMaster * (Number(tier.persentase_tier) / 100);

        rows.push({
            bulan: m,
            modal: currentModal,
            profit,
            feeMaster,
            komisi,
        });

        totalKomisi += komisi;

        // WD 60% ditarik, sisa 40% (1 - wdPct/100) reinvestasi menambah modal bulan berikutnya
        const reinvestment = profit * (1 - Number(wdPct) / 100);
        currentModal += reinvestment;
    }

    return {
        initialModal: validModal,
        tier,
        rows,
        total: totalKomisi,
    };
}

/**
 * Simulasi untuk kumpulan investor sekaligus, dengan akumulasi per bulan dan total gabungan.
 *
 * @param {Array<Object|number>} investors - Daftar investor (objek { id, modalIdr } atau nominal number)
 * @param {number} profitPct - Persentase profit bulanan
 * @param {Array<Object>} tiers - Definisi tiers dari backend
 * @param {Object} [opts={}]
 * @param {number} [opts.wdPct=60]
 * @param {number} [opts.feePct=30]
 * @param {number} [opts.months=3]
 * @returns {Object}
 */
export function simulateAll(investors = [], profitPct = 5, tiers = [], opts = {}) {
    const { wdPct = 60, feePct = 30, months = 3 } = opts;

    const investorResults = investors.map((inv, index) => {
        const modalIdr = typeof inv === 'object' && inv !== null ? inv.modalIdr : inv;
        const id = typeof inv === 'object' && inv !== null ? inv.id : index + 1;
        const name = typeof inv === 'object' && inv !== null && inv.name ? inv.name : `Investor ${index + 1}`;
        const sim = simulateInvestor({
            modalIdr,
            profitPct,
            tiers,
            wdPct,
            feePct,
            months,
        });

        return {
            id,
            name,
            ...sim,
        };
    });

    // Akumulasi bulanan
    const monthlyTotals = [];
    let grandTotalKomisi = 0;
    let grandTotalProfit = 0;
    let grandTotalFeeMaster = 0;

    for (let m = 1; m <= months; m++) {
        let monthModal = 0;
        let monthProfit = 0;
        let monthFeeMaster = 0;
        let monthKomisi = 0;

        for (const res of investorResults) {
            const row = res.rows.find((r) => r.bulan === m);
            if (row) {
                monthModal += row.modal;
                monthProfit += row.profit;
                monthFeeMaster += row.feeMaster;
                monthKomisi += row.komisi;
            }
        }

        monthlyTotals.push({
            bulan: m,
            totalModal: monthModal,
            totalProfit: monthProfit,
            totalFeeMaster: monthFeeMaster,
            totalKomisi: monthKomisi,
        });

        grandTotalKomisi += monthKomisi;
        grandTotalProfit += monthProfit;
        grandTotalFeeMaster += monthFeeMaster;
    }

    // Set tier yang aktif terpakai oleh investor yang memiliki modal > 0
    const activeTierLevels = new Set(
        investorResults
            .filter((inv) => inv.initialModal > 0)
            .map((inv) => inv.tier.tier_level)
    );

    return {
        investorResults,
        monthlyTotals,
        grandTotalKomisi,
        grandTotalProfit,
        grandTotalFeeMaster,
        activeTierLevels: Array.from(activeTierLevels),
    };
}
