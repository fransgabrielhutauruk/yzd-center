# Panduan Verifikasi Implementasi: Modul 03 - Jaringan Afiliasi & Perekaman Relasi Referral

Dokumen ini digunakan sebagai panduan pengujian dan verifikasi fungsionalitas Modul 03 sesuai spesifikasi pada [`docs/information-design/03_jaringan_dan_afiliasi.md`](../information-design/03_jaringan_dan_afiliasi.md).

---

## 1. Pemetaan Rute Web (`routes/web.php`)

| No | HTTP Method | URI / Endpoint | Nama Rute (`route()`) | Middleware | Controller & Method | Komponen Tampilan (Vue) |
| :---: | :--- | :--- | :--- | :--- | :--- | :--- |
| 1 ok ok | `GET` | `/register/check-referral` | `register.check_referral` | `guest` | `Auth\RegisteredUserController@checkReferral` | API Validasi Realtime di `Register.vue` |
| 2 ok ok| `GET` | `/investor/affiliates` | `investor.affiliates.index` | `auth`, `role:investor,affiliator`, `ensure.active` | `Investor\AffiliateNetworkController@index` | `resources/js/Pages/Investor/Affiliates/Index.vue` |
| 3 ok ok| `POST` | `/investor/affiliates/claims` | `investor.affiliates.claims.store` | `auth`, `role:investor,affiliator`, `ensure.active` | `Investor\AffiliateNetworkController@storeClaim` | Form Modal Klaim di `Investor/Affiliates/Index.vue` |
| 4 ok ok| `GET` | `/investor/affiliates/check-downline` | `investor.affiliates.check_downline` | `auth`, `role:investor,affiliator`, `ensure.active` | `Investor\AffiliateNetworkController@checkDownline` | API Verifikasi Realtime Username Downline |
| 5 ok ok| `GET` | `/admin/referral-claims` | `admin.referral_claims.index` | `auth`, `role:administrator` | `Admin\AdminReferralClaimController@index` | `resources/js/Pages/Admin/ReferralClaims/Index.vue` |
| 6 ok ok| `POST` | `/admin/referral-claims/{id}/verify` | `admin.referral_claims.verify` | `auth`, `role:administrator` | `Admin\AdminReferralClaimController@verify` | Modal Verifikasi di `Admin/ReferralClaims/Index.vue` |
| 7 ok | `GET` | `/admin/affiliates` | `admin.affiliates.index` | `auth`, `role:administrator` | `Admin\AdminAffiliateController@index` | `resources/js/Pages/Admin/Affiliates/Index.vue` |
| 8 ok ok| `GET` | `/documents/claims/{id}` | `documents.claim_proof` | `auth` | `DocumentStreamController@showClaimProof` | Streaming Privat Bukti Chat/Dokumen (Admin & Affiliator) |

---

## 2. Titik Uji Kritis (*Critical Verification Points*)

### A. Perekaman Otomatis Kode Referral Manual (Step 4 Registrasi)
1. **Input Kode Referral:**
   - Ditempatkan di Step 4 sebelum checkbox Syarat & Ketentuan Layanan.
   - Bersifat opsional (pendaftar tanpa kode dapat menyelesaikan registrasi sebagai investor mandiri).
2. **Validasi Interaktif:**
   - Input dikonversi otomatis ke huruf kapital alfanumerik.
   - Menekan tombol "Periksa" memicu pengecekan ke endpoint `/register/check-referral`.
   - Jika valid: muncul badge hijau *"✓ Terhubung dengan Affiliator: [Nama Affiliator]"*.
   - Jika tidak valid / salah: muncul teks error merah.
3. **Perekaman Database:**
   - Menyimpan relasi di tabel `relasi_referral` dengan status `sumber = 'link_otomatis'` dan `status = 'active'`.

### B. Standardisasi Format Kode Referral Affiliator (3+3 Karakter)
1. Saat affiliator baru terdaftar, sistem secara otomatis menghasilkan kode referral 6 karakter alfanumerik kapital:
   - 3 huruf pertama nama lengkap (uppercase).
   - 3 karakter acak `[A-Z0-9]` unik.
   - Contoh: `ABI8K2`, `ABIX79`.

### C. Alur Klaim Referral Susulan (PB 4)
1. **Pengecekan Username Calon Downline:**
   - Affiliator mengetik username calon downline (misal `@dian` atau `dian`).
   - Menekan tombol **Periksa** (atau Enter) memvalidasi ke database via `/investor/affiliates/check-downline`.
   - Sistem memvalidasi bahwa user ada, berperan sebagai investor, belum memiliki upline, bukan akun milik affiliator sendiri, dan tidak memiliki permohonan klaim lain yang berstatus `pending`.
2. **Pengajuan oleh Affiliator:**
   - Bukti pendukung bersifat **opsional** (mendukung berkas gambar JPG, PNG, WEBP atau dokumen PDF hingga 5 MB).
   - Label & helper keterangan: *"bukti pendukung (seperti chat whatsapp atau yang lainnya)"*.
   - Wajib mengisi kronologi / alasan klaim (minimal 10 karakter).
   - Status awal tersimpan: **`pending`**.
3. **Keputusan oleh Administrator:**
   - **Disetujui (`approved`)**: Record di `klaim_referral` berubah menjadi `approved`, dan sistem secara otomatis meng-insert record ke `relasi_referral` dengan `sumber = 'klaim_manual'`.
   - **Ditolak (`rejected`)**: Record di `klaim_referral` berubah menjadi `rejected`, Admin **wajib** mengisi catatan alasan penolakan, dan tidak ada relasi yang dibuat.

### D. Keamanan Berkas Bukti Privat (*Private Storage Protection*)
1. Jika berkas bukti dilampirkan, disimpan di direktori privat: `storage/app/private/referral_claims/{affiliator_id}/`.
2. Akses berkas via `/documents/claims/{id}` dibatasi hanya untuk **Administrator** dan **Affiliator pemohon**. Pengguna lain mendapat respons `403 Forbidden`.

---

## 3. Skenario Pengujian & Checklist Verifikasi Manual

### Skenario A: Pembuatan Affiliator & Kode Referral Baru
- [ ] Login sebagai Admin di `/login`.
- [ ] Buka menu **Tautan Undangan** (`/admin/invitations`), buat undangan baru dengan peran target **Affiliator**.
- [ ] Buka link undangan di tab incognito, daftarkan akun baru dengan nama `Rudi Pratama`.
- [ ] Periksa bahwa setelah pendaftaran, user memiliki peran `affiliator` dan kode referral 6 karakter berawalan `RUD` (contoh: `RUD9K1`).

### Skenario B: Pendaftaran Investor dengan Kode Referral Manual
- [ ] Buka link pendaftaran investor (`/register?invite={token}`).
- [ ] Lengkapi Step 1, Step 2, dan Step 3.
- [ ] Pada **Step 4**, sebelum mencentang Syarat & Ketentuan, ketikkan kode referral affiliator (misal `RUD9K1`).
- [ ] Klik tombol **Periksa**.
- [ ] **Ekspektasi:** Muncul badge hijau *"✓ Terhubung dengan Affiliator: Rudi Pratama"*.
- [ ] Centang persetujuan dan klik **Kirim Pendaftaran**.
- [ ] Periksa database tabel `relasi_referral`: record terbentuk dengan `sumber = 'link_otomatis'`.

### Skenario C: Pengajuan Klaim Susulan oleh Affiliator (PB 4)
- [ ] Daftarkan investor baru tanpa memasukkan kode referral (misal username `investorbru`).
- [ ] Login sebagai Affiliator di `/login`.
- [ ] Buka menu **Jaringan Kemitraan** (`/investor/affiliates`).
- [ ] Periksa bahwa kolom **Bergabung** berada di paling kiri tabel downline resmi.
- [ ] Klik tombol **Ajukan Klaim Susulan**.
- [ ] Ketikkan username calon downline (`investorbru`), lalu klik tombol **Periksa**.
- [ ] **Ekspektasi:** Kartu detail investor muncul dengan tanda centang hijau *"Investor Ditemukan di Database"*.
- [ ] Unggah bukti pendukung gambar atau dokumen PDF (atau lewati karena opsional).
- [ ] Ketikkan kronologi alasan klaim.
- [ ] Klik **Kirim Permohonan Klaim**.
- [ ] **Ekspektasi:** Modal tertutup, tab otomatis berpindah ke **Riwayat Klaim Susulan**, kolom **Diajukan** berada di paling kiri tabel, dan status klaim berstatus **Menunggu Peninjauan**.

### Skenario D: Peninjauan & Verifikasi Klaim oleh Admin
- [ ] Login kembali sebagai Admin.
- [ ] Buka menu **Klaim Susulan Referral** (`/admin/referral-claims`).
- [ ] Periksa permohonan klaim affiliator muncul dalam tabel.
- [ ] Klik tombol **Tinjau & Putuskan**.
- [ ] Periksa pratinjau bukti chat tampil jelas (bisa dibuka fullscreen/tab baru).
- [ ] Pilih keputusan **Setujui Klaim (Sah)** dan simpan.
- [ ] **Ekspektasi:** Status klaim menjadi `approved`.
- [ ] Buka menu **Jaringan Kemitraan** (`/admin/affiliates`): investor tersebut kini resmi tercatat sebagai downline affiliator terkait dengan sumber *"Klaim Susulan"*.

### Skenario E: Verifikasi Lonceng Notifikasi In-App
- [ ] **Notifikasi Pendaftaran Downline ke Affiliator:**
  - Daftarkan investor baru dengan kode referral affiliator.
  - Login sebagai Affiliator pemilik kode.
  - Periksa lonceng notifikasi di header: muncul badge unread dan item *"Downline Baru Terhubung"*. Klik item notifikasi, halaman diarahkan ke `/investor/affiliates`.
- [ ] **Notifikasi Pengajuan Klaim ke Administrator:**
  - Login sebagai Affiliator, ajukan klaim susulan.
  - Login sebagai Admin.
  - Periksa lonceng notifikasi: muncul item *"Permohonan Klaim Referral Susulan"*. Klik item, diarahkan ke `/admin/referral-claims`.
- [ ] **Notifikasi Keputusan Klaim ke Affiliator:**
  - Admin menyetujui atau menolak klaim.
  - Login sebagai Affiliator.
  - Periksa lonceng notifikasi: muncul item *"Klaim Referral Susulan Disetujui"* (atau *"Ditolak"*). Klik item, diarahkan ke `/investor/affiliates`.

