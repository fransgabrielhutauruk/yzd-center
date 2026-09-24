# Rules 08 — Strategi Pengujian (Testing Strategy)

## Strategi Pengujian: Fast Inner-Loop vs Final Verification

Untuk menjaga alur kerja agentic tetap gesit dan cepat:

1. **Selama Iterasi / Pengerjaan Fitur (Fast Inner-Loop):**
   - **GUNAKAN TARGETED TESTING:** Jalankan pengujian **hanya** pada file test yang relevan dengan fitur/perbaikan yang sedang dikerjakan.
   - Jangan jalankan full suite test (116+ test) di setiap putaran perubahan kode kecil karena memakan waktu > 60 detik di environment lokal.
   - Contoh eksekusi targeted (hanya 2-4 detik):
     ```powershell
     & "C:\laragon\bin\php\php-8.3.12-nts-Win32-vs16-x64\php.exe" artisan test tests/Feature/CommissionClaimTest.php
     ```

2. **Verifikasi Akhir Sebelum Commit (Final Outer-Loop):**
   - Jalankan seluruh suite test satu kali sebelum meminta persetujuan commit ke pengguna:
     ```powershell
     & "C:\laragon\bin\php\php-8.3.12-nts-Win32-vs16-x64\php.exe" artisan test --compact
     ```

---

## Cakupan Skenario Bisnis Kritikal (Wajib Ada Test)

### 1. Modul 05: Klaim Komisi Afiliasi (PB 3)
- **Uji Skema 4-Tier Komisi dari Profit Master Trader:**
  - Deposit $> \text{Rp } 10\text{ M}$ $\rightarrow$ Tier 1 (**50%** dari profit master).
  - Deposit $\text{Rp } 1\text{ M} - 10\text{ M}$ $\rightarrow$ Tier 2 (**40%** dari profit master).
  - Deposit $\text{Rp } 100\text{ Jt} - 1\text{ M}$ $\rightarrow$ Tier 3 (**30%** dari profit master).
  - Deposit $< \text{Rp } 100\text{ Jt}$ $\rightarrow$ Tier 4 (**20%** dari profit master).
- **Uji Syarat Keaktifan 90 Hari Berturut-turut:**
  - Downline aktif $< 90$ hari sejak `copy_started_at` $\rightarrow$ Ditolak / Tidak eligible.
  - Downline aktif $\ge 90$ hari sejak `copy_started_at` $\rightarrow$ Eligible diajukan klaim.
- **Uji Anti-Duplikasi:** Affiliator dilarang mengajukan klaim baru untuk downline yang sama jika masih ada klaim berstatus `pending`.
- **Uji Verifikasi & Pencairan Admin:**
  - Admin menyetujui transfer: wajib input `profit_master` dan upload `bukti_transfer`, nominal transfer terkalkulasi otomatis:
    $$\text{nominal\_transfer} = \text{profit\_master} \times \left(\frac{\text{persentase\_tier}}{100}\right)$$
  - Admin menolak klaim: wajib input `catatan_admin` alasan penolakan.
- **Uji Notifikasi In-App Database:** Admin menerima notifikasi klaim baru, affiliator menerima notifikasi hasil transfer/penolakan.

### 2. Modul 04: CopyTrade & Broker
- Stepper 3-tahap (Tahap 1 broker deposit $\to$ Tahap 2 buka link master & follow $\to$ Tahap 3 MQL5).
- Enkripsi password akun trading MT4 dan kredensial VPS (`'encrypted'` cast).
- Penguncian link rahasia master trader sebelum deposit disetujui.
- Pencatatan atomik timestamp `copy_started_at` saat akun copytrade resmi disetujui aktif.

### 3. Modul 03: Jaringan Afiliasi & Referral
- Format kode referral 6-karakter (3 huruf inisial + 3 alfanumerik acak, contoh: `ABI8K2`).
- Registrasi manual Step 4 tanpa parameter URL `?ref=`.
- Pengajuan klaim susulan referral (PB 4) dengan upload berkas bukti chat.

### 4. Modul 01 & 02: Autentikasi, KYC & Ahli Waris
- Token undangan pendaftaran tertutup 1x pakai.
- Otorisasi streaming privat dokumen (`DocumentStreamController`): Pengguna tanpa hak akses wajib menerima HTTP 403 Forbidden.

---

## Konvensi Penamaan Method Pengujian

Gunakan format baku: `test_{aktor}_{aksi}_{ekspektasi_hasil}`

Contoh yang benar:
- `test_affiliator_can_view_commission_dashboard_and_claims_list`
- `test_downline_with_less_than_90_days_copytrade_is_not_eligible`
- `test_downline_with_active_copytrade_at_least_90_days_is_eligible`
- `test_tier_percentage_calculation_for_all_4_tiers`
- `test_affiliator_cannot_submit_duplicate_pending_claim_for_same_downline`
- `test_admin_can_approve_and_transfer_commission_claim_with_receipt`
- `test_unauthorized_user_cannot_stream_private_commission_documents`

---

## Larangan dalam Pengujian

- **DILARANG** menghapus atau menonaktifkan test yang gagal tanpa persetujuan eksplisit pengguna.
- **DILARANG** menggunakan `$this->markTestSkipped()` untuk menyembunyikan kegagalan pengujian.
- **DILARANG** melakukan hardcode ID database dalam pengujian — gunakan Factory atau Eloquent create untuk menyiapkan data dinamis.
- Seluruh Feature Test wajib memanfaatkan trait `RefreshDatabase` untuk mengisolasi kondisi basis data pengujian.
