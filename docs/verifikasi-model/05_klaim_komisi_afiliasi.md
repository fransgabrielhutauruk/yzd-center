# Panduan Verifikasi Implementasi: Modul 05 - Klaim Komisi Afiliasi, Tiering 4-Level & Perhitungan ROI Master (PB 3)

Dokumen ini memuat panduan pengujian dan verifikasi fungsionalitas Modul 05 sesuai spesifikasi arsitektur pada [`docs/information-design/05_klaim_komisi_afiliasi.md`](../information-design/05_klaim_komisi_afiliasi.md).

---

## 1. Pemetaan Rute Web (`routes/web.php`)

| No | HTTP Method | URI / Endpoint | Nama Rute (`route()`) | Middleware | Controller & Method | Komponen Tampilan (Vue) / Keterangan |
| :---: | :--- | :--- | :--- | :--- | :--- | :--- |
| 1 | `GET` | `/investor/commissions` | `investor.commissions.index` | `auth`, `role:investor,affiliator`, `ensure.active` | `Investor\CommissionClaimController@index` | `resources/js/Pages/Investor/Commissions/Index.vue` |
| 2 | `POST` | `/investor/commissions` | `investor.commissions.store` | `auth`, `role:investor,affiliator`, `ensure.active` | `Investor\CommissionClaimController@store` | Modal Pengajuan Batch di `SubmitClaimModal.vue` |
| 3 | `GET` | `/investor/commissions/eligible-downlines` | `investor.commissions.eligible_downlines` | `auth`, `role:investor,affiliator`, `ensure.active` | `Investor\CommissionClaimController@eligibleDownlines` | API JSON daftar downline eligible 90 hari |
| 4 | `GET` | `/admin/commissions` | `admin.commissions.index` | `auth`, `role:administrator` | `Admin\CommissionClaimController@index` | `resources/js/Pages/Admin/Commissions/Index.vue` |
| 5 | `POST` | `/admin/commissions/{id}/process` | `admin.commissions.process` | `auth`, `role:administrator` | `Admin\CommissionClaimController@process` | Modal Proses Pencairan di `ProcessModal.vue` |
| 6 | `GET` | `/documents/commissions/{id}/receipt` | `documents.commission_receipt` | `auth` | `DocumentStreamController@showCommissionReceipt` | Streaming Privat Resi Transfer (Admin & Affiliator Pemohon) |

---

## 2. Titik Uji Kritis (*Critical Verification Points*)

### A. Konsep 1 Row Per Pengajuan Tiket Klaim Komisi
1. **Satu Row Utama (`klaim_komisi`):**
   - Pengajuan batch—meskipun mencakup banyak investor downline dan banyak akun copytrade—disimpan sebagai **1 (satu) baris (row)** pada tabel `klaim_komisi`.
   - Memiliki 1 `kode_klaim` unik (format: `CLM-YYYYMM-XXXX`).
   - Menyimpan `total_downline`, `total_akun`, `total_deposit_amount`, `total_gross_profit`, `total_master_gross_share`, `total_master_net_share`, `total_investor_net_profit`, `total_affiliate_commission`, serta rekening bank tujuan.
2. **Relasi Master-Detail (`klaim_komisi_items`):**
   - Setiap akun copytrade disimpan sebagai 1 item baris pada tabel `klaim_komisi_items` dengan mencatat `deposit_amount`, `hari_kehitung`, `tier_level`, `persentase_tier`, `roi_percentage`, `gross_profit`, `master_gross_share`, `affiliate_commission`, `master_net_share`, dan `investor_net_profit`.
3. **Pencairan Tunggal Terpadu:**
   - Admin melakukan satu kali transfer bank ke rekening affiliator sebesar `total_affiliate_commission` (`total_nominal_transfer`), dan mengunggah satu resi transfer bank (`bukti_transfer_path`).

### B. Validasi Masa Aktif 90 Hari & Multi-Akun Copytrade
1. **Kelayakan Pengajuan (Eligibility Check):**
   - Downline berhak diajukan klaim jika akun copytrade pertamanya telah aktif minimal **90 hari**.
   - Setiap downline hanya dapat diklaim **1 kali** untuk periode tersebut.
2. **Proporsionalitas Multi-Akun:**
   - Akun pertama dihitung penuh **90 hari**.
   - Akun kedua dan seterusnya dihitung berdasarkan selisih hari mulai terhadap akun pertama: $\text{hari\_kehitung} = \max(0, 90 - \Delta\text{hari\_start})$.

### C. Formula Perhitungan Komisi & Drawdown Guard
1. **Gross Profit Investor:**
   $$\text{gross\_profit} = \text{deposit\_amount} \times \left( \frac{\text{roi\_percentage}}{100} \right)$$
2. **Drawdown Guard (Posisi Rugi / Impas):**
   - Jika $\text{gross\_profit} \le 0$ (atau $\text{roi\_percentage} \le 0$):
     - `affiliate_commission = 0.00`
     - `master_gross_share = 0.00`
     - `master_net_share = 0.00`
     - `investor_net_profit = gross_profit`
3. **Posisi Untung ($\text{gross\_profit} > 0$):**
   - $\text{master\_gross\_share} = \text{gross\_profit} \times (\text{master\_share\_pct} / 100)$
   - $\text{affiliate\_commission} = \text{master\_gross\_share} \times (\text{affiliate\_share\_pct} / 100)$
   - $\text{master\_net\_share} = \text{master\_gross\_share} - \text{affiliate\_commission}$
   - $\text{investor\_net_profit} = \text{gross\_profit} - \text{master\_gross\_share}$
4. **Keseimbangan Finansial:**
   $$\text{investor\_net\_profit} + \text{master\_net\_share} + \text{affiliate\_commission} = \text{gross\_profit}$$
5. **Mata Uang:**
   - Seluruh nominal modal, profit, dan komisi menggunakan mata uang USD ($).

---

## 3. Langkah Pengujian Manual (*Step-by-Step Test Guide*)

### Skenario 1: Pengajuan Batch Klaim oleh Affiliator
1. Buka browser dan login menggunakan akun Affiliator (`affiliator@yzd.test` / password).
2. Klik menu **Klaim Komisi (PB 3)** pada navigasi samping.
3. Klik tombol **Ajukan Klaim Komisi Baru**:
   - Periksa daftar investor downline: tanggal mulai copytrade pertama, durasi hari aktif ($\ge 90$ hari), tier modal, dan akun copytrade yang dimiliki.
   - Pilih satu atau beberapa investor downline yang memenuhi syarat.
   - Periksa rekening bank tujuan transfer.
   - Klik tombol **Kirim Pengajuan Klaim**.
4. Verifikasi bertambah tepat **1 baris (1 row)** pada tabel riwayat dengan status *Menunggu Persetujuan*.

### Skenario 2: Admin Memverifikasi Komisi Berbasis Master Fee Lunas & Menyelesaikan Transfer
1. Buka browser dan login sebagai Administrator (`admin@yzd.test` / password).
2. Buka menu **Manajemen Afiliasi -> Pencairan Komisi** (`/admin/commissions`).
3. Temukan baris permohonan klaim, klik **Proses**.
4. Di modal proses pencairan:
   - Periksa kalkulasi otomatis nilai Gross Profit, Hak Kotor Master (30%), Komisi Afiliator, Hak Bersih Master, dan Profit Bersih Investor yang diambil dari tagihan Master Fee lunas downline (tanpa input ROI manual).
   - Masukkan nilai Kurs Konversi Riil MT4 ke Rupiah (misal: `Rp 16.250`).
   - Unggah resi/bukti transfer bank (JPG/PNG/PDF).
   - Klik **Selesaikan Pencairan**.
5. Verifikasi status klaim berubah menjadi **Ditransfer** dengan badge hijau solid.

### Skenario 3: Transparansi Rincian Komisi di Sisi Affiliator
1. Kembali ke akun Affiliator di `/investor/commissions`.
2. Klik tombol **Rincian** pada tiket klaim yang telah dicairkan.
3. Modal rincian menampilkan:
   - Rekening pencairan afiliator.
   - Kartu ringkasan total: Total Modal ($), Total Gross Profit ($), Total Komisi Diterima ($).
   - Rincian breakdown per akun copytrade: Deposit, ROI Master (%), Gross Profit, Hak Master (30%), Komisi Afiliator, Net Master, dan Net Investor.
   - Tombol **Lihat Bukti Transfer Bank** untuk melihat berkas resi transfer via streaming aman.

---

## 4. Ringkasan Pengujian Otomatis (*Test Suite*)

Seluruh fitur dan kalkulasi diuji secara otomatis melalui [`src/tests/Feature/CommissionClaimTest.php`](file:///e:/5_projects/yzd-trading-management/src/tests/Feature/CommissionClaimTest.php):

```powershell
& "C:\laragon\bin\php\php-8.3.12-nts-Win32-vs16-x64\php.exe" artisan test tests/Feature/CommissionClaimTest.php --compact
```

Daftar 13 skenario uji otomatis (100% Passed):
1. `test_affiliator_can_view_commission_dashboard_and_claims_list`
2. `test_downline_with_less_than_90_days_copytrade_is_not_eligible`
3. `test_downline_with_at_least_90_days_copytrade_is_eligible`
4. `test_multi_copytrade_calculates_earliest_start_combined_modal_and_account_days`
5. `test_all_4_tier_percentages_calculation`
6. `test_affiliator_can_submit_batch_claims_as_single_row_in_klaim_komisi`
7. `test_affiliator_cannot_submit_duplicate_claim_for_same_downline`
8. `test_admin_can_view_commissions_list`
9. `test_admin_can_approve_and_transfer_commission`
10. `test_admin_approval_with_drawdown_guard_yields_zero_commission`
11. `test_admin_can_reject_commission_claim_with_reason`
12. `test_only_authorized_user_can_stream_commission_documents`
13. `test_commission_calculation_formula_and_mathematical_balance`
