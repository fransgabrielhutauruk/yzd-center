# Panduan Verifikasi Implementasi: Modul 02 - Legalitas, KYC & Perlindungan Ahli Waris

Dokumen ini digunakan sebagai panduan pengujian dan verifikasi fungsionalitas Modul 02 sesuai spesifikasi pada [`docs/information-design/02_legalitas_kyc_dan_ahli_waris.md`](../information-design/02_legalitas_kyc_dan_ahli_waris.md).

---

## 1. Pemetaan Rute Web (`routes/web.php`)

|  No   | HTTP Method | URI / Endpoint                     | Nama Rute (`route()`)         | Middleware                         | Controller & Method                   | Komponen Tampilan (Vue)                        |
| :---: | :---------- | :--------------------------------- | :---------------------------- | :--------------------------------- | :------------------------------------ | :--------------------------------------------- |
|   1   ok ok| `GET`       | `/investor/kyc`                    | `investor.kyc.index`          | `auth`, `role:investor,affiliator` | `Investor\KycController@index`        | `resources/js/Pages/Investor/Kyc/Index.vue`    |
|   2   ok ok| `POST`      | `/investor/kyc/reupload/{type}`    | `investor.kyc.reupload`       | `auth`, `role:investor,affiliator` | `Investor\KycController@reupload`     | Form unggah ulang di `Investor/Kyc/Index.vue`  |
|   3   ok ok| `GET`       | `/admin/kyc`                       | `admin.kyc.index`             | `auth`, `role:administrator`       | `Admin\AdminKycController@index`      | `resources/js/Pages/Admin/Kyc/Index.vue`       |****
|   4   ok ok| `POST`      | `/admin/kyc/documents/{id}/verify` | `admin.kyc.verify`            | `auth`, `role:administrator`       | `Admin\AdminKycController@verify`     | Modal verifikasi di `Admin/Kyc/Index.vue`      |
|   5   ok ok| `GET`       | `/admin/settings/legal`            | `admin.settings.legal`        | `auth`, `role:administrator`       | `Admin\LegalContentController@index`  | `resources/js/Pages/Admin/Settings/Legal.vue`  |
|   6   ok ok| `POST`      | `/admin/settings/legal`            | `admin.settings.legal.update` | `auth`, `role:administrator`       | `Admin\LegalContentController@update` | Rich text editor di `Admin/Settings/Legal.vue` |
|   7   ok ok| `GET`       | `/documents/{id}`                  | `documents.show`              | `auth`                             | `DocumentStreamController@show`       | *Streaming* berkas privat (Image / PDF)        |

---

## 2. Titik Uji Kritis (*Critical Verification Points*)

### A. Alur Siklus Status Berkas (*State Machine*)
1. Dokumen awal diunggah oleh pendaftar $\to$ Status **`pending`**.
2. Administrator memeriksa berkas:
   - **`approved`**: Berkas sah. Jika KTP dan Perjanjian keduanya disetujui, akun pengguna otomatis berubah menjadi **`active`**.
   - **`revision_needed`**: Berkas kurang jelas/salah. Admin **wajib** menyertakan `catatan_admin`.
   - **`rejected`**: Berkas palsu/manipulasi.
3. Investor mengunggah ulang berkas revisi $\to$ Status dokumen kembali menjadi **`pending`**, catatan admin di-reset (`NULL`), dan siap diverifikasi ulang oleh Admin.

### B. Fitur Deteksi Kualitas Berkas (Client-side & Server-side)
1. **Resolusi Minimal:** Minimal $600 \times 400\text{ px}$.
2. **Uji Ketajaman Laplacian Variance:** Menghitung varians kontras tepi; jika skor $< 45$, gambar diklasifikasikan sebagai buram (*blurry*).
3. **Peringatan Kualitas:**
   - Client-side: Menampilkan amber alert banner dan opsi ganti berkas di browser.
   - Server-side: Menandai `is_quality_warning = true` pada tabel `user_dokumen`.
4. **Hak Unggah Ulang Mandiri:** Investor dapat langsung mengunggah ulang dokumen berstatus `pending` jika terdeteksi `is_quality_warning = true` atau saat ingin mengganti berkas yang lebih jelas sebelum admin melakukan verifikasi.

### C. Keamanan Berkas Privat (*Private Storage Protection*)
1. Berkas disimpan di direktori privat: `storage/app/private/kyc_documents/{user_id}/`.
2. **Dilarang keras:** Berkas tidak boleh disimpan di `storage/app/public/` dan tidak boleh diakses via `storage:link`.
3. Akses berkas melalui endpoint `/documents/{id}` dilindungi kebijakan otorisasi (*Policy*):
   - Tamu $\to$ `302 Redirect ke /login`.
   - Pengguna lain $\to$ `403 Forbidden`.
   - Admin & Pemilik dokumen $\to$ `200 OK (Stream File)`.

---

## 3. Skenario Pengujian & Checklist Verifikasi Manual

### Skenario A: Peringatan Kualitas Saat Registrasi
- [ ] **Uji Coba Upload Foto Buram / Ikon Kecil:**
  - Pada Step 3 registrasi (`/register`), unggah gambar berukuran kecil ($< 600\times 400$) atau foto yang sangat buram.
  - **Ekspektasi:** Muncul notifikasi toast peringatan kualitas dan alert banner amber yang menyarankan mengganti foto. Tombol *"Ganti Foto KTP"* / *"Ganti Berkas"* aktif dan berfungsi.
- [ ] **Uji Coba Upload Berkas Tajam / Jernih:**
  - Unggah foto KTP beresolusi tajam ($> 800\times 600$).
  - **Ekspektasi:** Kompresi otomatis 2K berjalan lancar, tidak muncul peringatan kualitas, dan badge preview berstatus valid.

### Skenario B: Verifikasi Berkas oleh Administrator
- [ ] **Akses Panel KYC Admin:**
  - Login sebagai `admin@yzdcenter.com`, buka menu `http://localhost:8000/admin/kyc`.
  - **Ekspektasi:** Server-side data table memuat daftar pendaftar lengkap dengan kolom status, badge dokumen, dan indikator peringatan kualitas (*badge kuning jika is_quality_warning*).
- [ ] **Tinjau Berkas Dokumen:**
  - Klik tombol *"Tinjau Berkas"* pada salah satu pengguna.
  - **Ekspektasi:** Modal tinjauan terbuka, menampilkan foto KTP / dokumen perjanjian secara aman via `/documents/{id}`.
- [ ] **Minta Revisi (*Revision Needed*):**
  - Pilih status *"Perlu Revisi"*, kosongkan catatan, klik simpan $\to$ **Ekspektasi:** Validasi error muncul karena catatan admin wajib diisi.
  - Isi catatan (misal: *"Foto KTP silau pada bagian NIK, mohon foto ulang"*), klik simpan $\to$ **Ekspektasi:** Status dokumen berubah menjadi `revision_needed`.
- [ ] **Setujui Berkas (*Approve*):**
  - Setujui dokumen KTP $\to$ Akun pengguna masih berstatus `pending` (karena perjanjian belum disetujui).
  - Setujui dokumen Surat Perjanjian $\to$ Status akun pengguna otomatis berganti menjadi **`active`**.

### Skenario C: Portal KYC Investor & Unggah Ulang Berkas
- [ ] **Lihat Status & Catatan Revisi:**
  - Login sebagai investor yang dokumennya berstatus `revision_needed`.
  - Buka `http://localhost:8000/investor/kyc`.
  - **Ekspektasi:** Banner merah/oranye muncul menampilkan catatan dari admin dan formulir unggah ulang terbuka aktif.
- [ ] **Unggah Ulang Berkas Revisi:**
  - Unggah foto pengganti baru dan submit.
  - **Ekspektasi:** Status dokumen kembali menjadi `pending`, catatan revisi lama hilang, dan berkas fisik lama terhapus dari storage.
- [ ] **Unggah Ulang Mandiri Dokumen Berperingatan Kualitas:**
  - Pada dokumen berstatus `pending` dengan badge `is_quality_warning`, form unggah ulang tetap aktif terbuka.
  - Unggah foto baru yang lebih tajam.
  - **Ekspektasi:** Berkas berhasil diunggah ulang tanpa perlu menunggu admin menolak dokumen.
- [ ] **Proteksi Dokumen yang Telah Disetujui (*Approved*):**
  - Coba unggah ulang dokumen yang berstatus `approved`.
  - **Ekspektasi:** Tombol dan form unggah ulang terkunci, backend mengembalikan pesan bahwa berkas yang disetujui tidak dapat diubah.

### Skenario D: Pengaturan Konten Legal Dinamis (Admin)
- [ ] **Editor Konten Legal:**
  - Buka `http://localhost:8000/admin/settings/legal`.
  - **Ekspektasi:** Editor teks kaya (Rich Text Editor) memuat naskah aktif Syarat & Ketentuan dan Kebijakan Privasi tanpa ada referensi nomor versi lawas.
- [ ] **Simpan Perubahan:**
  - Edit salah satu pasal dan klik Simpan.
  - **Ekspektasi:** Konten terbarui di database dan langsung tercermin saat membuka modal Syarat & Ketentuan pada formulir registrasi.

### Skenario E: Keamanan Akses Berkas Privat
- [ ] **Akses Tanpa Otentikasi:**
  - Salin URL streaming dokumen (misal: `http://localhost:8000/documents/1`). Buka di browser baru tanpa login.
  - **Ekspektasi:** Otomatis redirect ke `/login`.
- [ ] **Akses Lintas Pengguna (*IDOR Protection*):**
  - Login sebagai Investor A, coba akses URL dokumen milik Investor B (`/documents/{id_milik_investor_b}`).
  - **Ekspektasi:** Sistem mengembalikan respon **`403 Forbidden`**.

---

## 4. Pengujian Otomatis (*Automated Feature Tests*)

Jalankan seluruh suite pengujian otomatis Modul 02 via PHP CLI Laragon:

```powershell
# 1. Uji Alur Registrasi KYC & Ahli Waris
& "C:\laragon\bin\php\php-8.3.12-nts-Win32-vs16-x64\php.exe" artisan test tests/Feature/KycRegistrationTest.php --compact

# 2. Uji Verifikasi Dokumen & Unggah Ulang Revisi
& "C:\laragon\bin\php\php-8.3.12-nts-Win32-vs16-x64\php.exe" artisan test tests/Feature/KycVerificationTest.php --compact

# 3. Uji Keamanan Berkas Privat & Otorisasi Policy
& "C:\laragon\bin\php\php-8.3.12-nts-Win32-vs16-x64\php.exe" artisan test tests/Feature/DocumentSecurityTest.php --compact

# 4. Uji Pengaturan Konten Legal Dinamis
& "C:\laragon\bin\php\php-8.3.12-nts-Win32-vs16-x64\php.exe" artisan test tests/Feature/LegalContentTest.php --compact
```
