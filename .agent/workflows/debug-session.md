# Workflow: Debug Session & Pelacakan Error Sistematis

Alur kerja investigasi dan pemecahan masalah saat menghadapi galat atau kendala fungsional.

---

## 1. Identifikasi & Pengumpulan Informasi
- [ ] **Baca Pesan Error Lengkap:** Periksa stack trace, nama berkas, nomor baris, dan jenis exception.
- [ ] **Periksa Log Server:**
  ```powershell
  Get-Content -Tail 50 storage/logs/laravel.log
  ```
- [ ] **Periksa Konsol & Jaringan Browser (F12):** Catat status HTTP (403, 404, 422, 500) dan muatan payload request/response Inertia.
- [ ] **Reproduksi Masalah:** Pastikan langkah-langkah untuk memicu galat dapat diulang secara konsisten.

---

## 2. Analisis Akar Masalah (Root Cause Analysis)
- [ ] **Telusuri Rantai Eksekusi:** Rute $\to$ Middleware (`role`, `ensure.active`) $\to$ Form Request $\to$ Controller $\to$ Service $\to$ Model Eloquent.
- [ ] **Cek Validasi Form Request:** Jika error 422, periksa aturan validasi dan tipe data payload yang dikirimkan client.
- [ ] **Cek Otorisasi (403 Forbidden):** Pastikan peran pengguna (`role`) dan status akun (`status == 'active'`) sesuai dengan middleware yang melindungi rute.
- [ ] **Cek Kepemilikan Berkas Privat:** Jika streaming dokumen gagal (403/404), periksa verifikasi otorisasi pada `DocumentStreamController` dan integritas path di disk `'local'`.
- [ ] **Cek Mutasi Database:** Pastikan transaksi dibungkus `DB::transaction()` sehingga data tidak setengah tersimpan saat terjadi kegagalan.

---

## 3. Eksekusi Perbaikan & Verifikasi
- [ ] Perbaiki sumber masalah pada lapisan yang tepat (Service untuk logika bisnis, Form Request untuk validasi, Vue untuk UI state).
- [ ] Hindari perbaikan instan (*quick fix*) yang hanya menutupi gejala namun merusak modul lain.
- [ ] Jalankan format kode:
  ```powershell
  & "C:\laragon\bin\php\php-8.3.12-nts-Win32-vs16-x64\php.exe" vendor/bin/pint --test app database routes tests
  ```
- [ ] Jalankan pengujian otomatis untuk memastikan zero regression:
  ```powershell
  & "C:\laragon\bin\php\php-8.3.12-nts-Win32-vs16-x64\php.exe" artisan test --compact
  ```
- [ ] Verifikasi bahwa error sudah tidak terjadi lagi pada skenario reproduksi.

---

## Panduan Debug Spesifik Modul YZD Center

### A. Modul 05: Klaim Komisi Afiliasi (PB 3)
1. **Galat Downline Tidak Eligible:**
   - Periksa kolom `copy_started_at` pada akun copytrade downline di database.
   - Syarat mutlak: durasi aktif $\ge 90$ hari berturut-turut terhitung sejak `copy_started_at`.
   - Cek apakah downline sudah memiliki klaim berstatus `pending` (sistem mencegah duplikasi klaim aktif).
2. **Galat Kalkulasi Nominal Transfer:**
   - Skema tiering 4-level: Tier 1 ($> 10\text{ M} \to 50\%$), Tier 2 ($1\text{ M}-10\text{ M} \to 40\%$), Tier 3 ($100\text{ Jt}-1\text{ M} \to 30\%$), Tier 4 ($< 100\text{ Jt} \to 20\%$).
   - Formula transfer: `nominal_transfer = profit_master * (persentase_tier / 100)`.

### B. Modul 04: CopyTrade & Broker
1. **Galat Link Rahasia Master Tidak Muncul:**
   - Verifikasi status pendaftaran copytrade: link rahasia master trader Pak Abi baru terbuka setelah Fase 1 disetujui (`step1_deposit_verified_at IS NOT NULL`).
2. **Galat Kredensial MT4/VPS:**
   - Model `AkunCopytrade` menggunakan casting `'encrypted'`. Jangan mengenkripsi manual dua kali menggunakan `Crypt::encryptString()` saat menyimpan.
3. **Galat Validasi Minimum Modal:**
   - Pastikan nilai batas modal diambil dinamis dari `pengaturan_master` via `PengaturanMaster::getSettings()`.

### C. Modul 03: Jaringan Afiliasi & Perekaman Referral
1. **Konflik Paginasi / Filter Multi-Tab:**
   - Jika filter tab downline merusak filter tab klaim susulan di halaman yang sama, pastikan prop `paramPrefix` di `AppDataTable.vue` sudah diisi berbeda (misal `downline_` vs `claim_`).
2. **Galat Format Kode Referral:**
   - Format wajib: 6 karakter (3 huruf awal nama kapital + 3 karakter alfanumerik acak, contoh: `ABI8K2`).

---

## Perintah Bantuan Diagnostik (CLI)

```powershell
# Bersihkan seluruh cache aplikasi:
& "C:\laragon\bin\php\php-8.3.12-nts-Win32-vs16-x64\php.exe" artisan optimize:clear

# Daftar rute terdaftar dengan filter nama:
& "C:\laragon\bin\php\php-8.3.12-nts-Win32-vs16-x64\php.exe" artisan route:list --name=commissions

# Status migrasi basis data:
& "C:\laragon\bin\php\php-8.3.12-nts-Win32-vs16-x64\php.exe" artisan migrate:status
```
