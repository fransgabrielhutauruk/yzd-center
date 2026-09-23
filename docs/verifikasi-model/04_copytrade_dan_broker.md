# Panduan Verifikasi Implementasi: Modul 04 - CopyTrade, Akun Broker & Manajemen Multi-Akun 3-Tier

Dokumen ini memuat panduan pengujian dan verifikasi fungsionalitas Modul 04 sesuai spesifikasi arsitektur terbaru pada [`docs/information-design/04_copytrade_dan_broker.md`](../information-design/04_copytrade_dan_broker.md).

---

## 1. Pemetaan Rute Web (`routes/web.php`)

| No | HTTP Method | URI / Endpoint | Nama Rute (`route()`) | Middleware | Controller & Method | Komponen Tampilan (Vue) / Keterangan |
| :---: | :--- | :--- | :--- | :--- | :--- | :--- |
| 1 | `GET` | `/investor/copytrade` | `investor.copytrade.index` | `auth`, `role:investor,affiliator` | `Investor\CopytradeController@index` | `resources/js/Pages/Investor/Copytrade/Index.vue` |
| 2 | `POST` | `/investor/copytrade` | `investor.copytrade.store` | `auth`, `role:investor,affiliator` | `Investor\CopytradeController@store` | Modal Formulir di `AddCopytradeModal.vue` |
| 3 | `GET/POST` | `/investor/copytrade/template-perjanjian/{tier}` | `investor.copytrade.template` | `auth`, `role:investor,affiliator` | `Investor\CopytradeController@downloadTemplate` | Unduh template DOCX perjanjian (`tier_1`, `tier_2`, `tier_3`) |
| 4 | `GET` | `/admin/copytrade` | `admin.copytrade.index` | `auth`, `role:administrator` | `Admin\CopytradeController@index` | `resources/js/Pages/Admin/Copytrade/Index.vue` |
| 5 | `POST` | `/admin/copytrade/{id}/verify` | `admin.copytrade.verify` | `auth`, `role:administrator` | `Admin\CopytradeController@verify` | Modal Keputusan di `VerifyModal.vue` |
| 6 | `GET` | `/admin/settings/master` | `admin.settings.master` | `auth`, `role:administrator` | `Admin\PengaturanMasterController@index` | `resources/js/Pages/Admin/Settings/Master.vue` |
| 7 | `POST` | `/admin/settings/master` | `admin.settings.master.update` | `auth`, `role:administrator` | `Admin\PengaturanMasterController@update` | Simpan Konfigurasi Master & Tier di `Master.vue` |
| 8 | `GET` | `/admin/settings/master/download-pdf/{tier}` | `admin.settings.master.download_pdf` | `auth`, `role:administrator` | `Admin\PengaturanMasterController@downloadPdf` | Pratinjau/Unduh berkas PDF panduan tier dari CMS |
| 9 | `GET` | `/investor/copytrade/panduan-pdf/{tier}` | `investor.copytrade.guide_pdf` | `auth`, `role:investor,affiliator` | `Investor\CopytradeController@downloadGuidePdf` | Unduh berkas PDF panduan tier oleh investor |
| 10 | `GET` | `/documents/copytrade/{id}` | `documents.copytrade` | `auth` | `DocumentStreamController@showCopytradeAgreement` | Streaming Privat PDF Surat Perjanjian (Admin & Pemilik) |

---

## 2. Titik Uji Kritis (*Critical Verification Points*)

### A. Pemisahan Pendaftaran Akun Utama & Manajemen Copytrade
1. **Formulir Registrasi Bersih (`/register`):**
   - Step 1: Data Identitas Pribadi + Unggah Berkas KTP.
   - Step 2: Kontak & Identitas Ahli Waris.
   - Step 3: Konfirmasi Kode Referral (opsional) serta Persetujuan Syarat & Ketentuan.
   - **Aturan:** Tidak ada lagi pengisian modal estimasi, pilihan tier, broker, nomor MT4, atau surat perjanjian pada formulir pendaftaran akun utama.
2. **Akses Menu Investor Baru:**
   - Investor dengan status `pending` (belum diverifikasi KTP-nya) tetap diizinkan mengakses menu `/investor/kyc` dan `/investor/copytrade` via bypass pada middleware `EnsureAccountIsActive`.

### B. Pengaturan Master & 3 Tier Investasi Dinamis (CMS)
1. **Konfigurasi Administrator (`/admin/settings/master`):**
   - Admin dapat mengatur:
     - Tautan Referral Pendaftaran Broker (misal HFM/XM).
     - Tautan Akun Master Trading / MQL5.
     - Batas Minimal Modal Riil Tier 1 (default: \$100).
     - Batas Minimal Modal Riil Tier 2 (default: \$5,000 / \$500).
     - Batas Minimal Modal Riil Tier 3 (default: \$300,000 / \$1,000).
2. **Sinkronisasi di Portal Investor:**
   - Halaman `/investor/copytrade` menampilkan kartu *"Informasi Master Akun"* dengan tombol tautan pendaftaran broker dan tautan akun master.
   - Modal tambah akun secara otomatis menampilkan batas minimal modal yang terdaftar pada konfigurasi CMS untuk tiap opsi tier.

### C. Dukungan Multi-Akun Copytrade (*Multiple Accounts*)
1. Satu investor dapat mendaftarkan **lebih dari 1 akun trading/copytrade**.
2. Setiap pengajuan akun trading memiliki kartu baris tersendiri pada tabel `AppDataTable` dengan nomor akun, modal riil, tier, tanggal mulai, status, dan tanggal aktif (`copy_started_at`).

### D. Penanganan Surat Perjanjian Kemitraan (Hanya Pendaftaran Pertama)
1. **Pendaftaran Pertama Kali (`hasExisting == false`):**
   - Investor **wajib** mengunggah berkas Surat Perjanjian (format PDF, JPG, atau PNG maksimal 10 MB).
   - Investor dapat mengunduh dokumen template resmi DOCX per tier melalui tombol *"Unduh Template Perjanjian"*.
   - Dokumen dikompresi otomatis di latar belakang jika berupa gambar.
2. **Pendaftaran Akun Tambahan (`hasExisting == true`):**
   - Untuk pendaftaran akun copytrade ke-2, ke-3, dst., upload surat perjanjian **tidak diwajibkan** (opsional) karena perjanjian kemitraan investor telah diikat pada pendaftaran awal.

### E. Kredensial Khusus Tier 3 (Private VPS / Master Credentials)
1. Jika memilih **Tier 3**, modal formulir menampilkan seksi khusus bertanda aksen kuning/oranye:
   - Server Broker (wajib)
   - Master Password MT4 (wajib, otomatis dienkripsi dengan `Crypt::encryptString()`)
   - Investor Password MT4 (wajib, otomatis dienkripsi dengan `Crypt::encryptString()`)
   - URL MQL5 pribadi (opsional)

### F. Sistem Panduan 3-Tier Hybrid (Web CMS & Berkas PDF Opsional)
1. **CMS Pengaturan Langkah & PDF Panduan (`/admin/settings/master`):**
   - Admin dapat mengisi petunjuk langkah pendaftaran akun per tier (`tier_1_desc`, `tier_2_desc`, `tier_3_desc`).
   - Admin dapat mengunggah berkas PDF panduan grafis/brosur per tier (maks. 10 MB).
   - Admin dapat menghapus atau mengganti berkas PDF yang telah tersimpan sebelumnya.
2. **Tampilan Kartu Panduan di Portal Investor (`/investor/copytrade`):**
   - Menampilkan 3 kartu panduan interaktif: Tier 1 Cent, Tier 2 Standard, dan Tier 3 Private VPS.
   - Menampilkan batas minimal modal dinamis sesuai pengaturan CMS.
   - Menampilkan teks petunjuk langkah pembukaan akun broker yang ramah mobile.
   - Tombol *"Unduh Brosur PDF"* aktif secara kondisional jika Admin telah mengunggah file PDF.
   - Tombol *"Daftar Akun Tier X"* secara instan membuka modal pendaftaran dengan tier yang sudah dipilih otomatis.

### G. Alur Verifikasi 1-Pintu oleh Administrator
1. Admin meninjau permohonan copytrade pada menu `/admin/copytrade`.
2. Admin membuka modal peninjauan (`VerifyModal.vue`):
   - Menampilkan data lengkap investor (Nama, Email, No. HP).
   - Menampilkan data akun trading (Tier, Modal Riil USD, Nomor Akun, Tanggal Mulai).
   - Menampilkan informasi penarikan bank (Nama Bank, Nomor Rekening, Atas Nama).
   - Menampilkan tombol *"Lihat Berkas"* untuk pratinjau surat perjanjian privat (jika ada).
3. **Keputusan Verifikasi:**
   - **Terima (Aktif):** Status akun berubah menjadi `active`, `copy_started_at` diisi `now()`, dan notifikasi database dikirim ke investor.
   - **Tolak (Rejected):** Admin **wajib** mengisi catatan instruksi alasan penolakan (`catatan_admin`), dan notifikasi dikirim ke investor.

---

## 3. Skenario Pengujian & Checklist Verifikasi Manual

### Skenario A: Konfigurasi Master & Tier oleh Admin
- [ ] Login sebagai Administrator di `/login` (`admin@yzdcenter.com` / `password`).
- [ ] Buka menu navigasi sidebar **Pengaturan Sistem** $\rightarrow$ **Pengaturan Master** (`/admin/settings/master`).
- [ ] Pastikan input form memuat:
  - Tautan Referral Pendaftaran Broker
  - Tautan Akun Master Trading
  - Minimal Modal Tier 1, Tier 2, dan Tier 3
- [ ] Ubah salah satu nilai (misal Tautan Referral Broker menjadi `https://hfm.com/id/?refid=999999`) lalu klik **Simpan Pengaturan**.
- [ ] **Ekspektasi:** Muncul notifikasi toast hijau *"Pengaturan master berhasil disimpan"* dan data tersimpan ke database tabel `pengaturan_master`.

### Skenario B: Pendaftaran Investor Baru (Alur Bersih Tanpa Form Broker)
- [ ] Buka halaman `/admin/invitations`, salin token undangan investor (atau gunakan `INV-TEST-INVESTOR`).
- [ ] Buka tab *incognito/private*, akses tautan registrasi: `/register?invite=INV-TEST-INVESTOR`.
- [ ] Gunakan fitur dev tool *"Isi Form Acak (Dev)"* di pojok kanan bawah, atau isi manual:
  - **Step 1:** Data Diri lengkap + Unggah berkas KTP (gambar JPG/PNG). Klik Selanjutnya.
  - **Step 2:** Data Ahli Waris lengkap. Klik Selanjutnya.
  - **Step 3:** Review ringkasan dan centang persetujuan Syarat & Ketentuan.
- [ ] Periksa bahwa **tidak ada field broker, modal, maupun MT4** di seluruh tahapan registrasi.
- [ ] Klik **Daftar Sekarang**.
- [ ] **Ekspektasi:** Pendaftaran berhasil dan diarahkan ke halaman login dengan pesan sukses.

### Skenario C: Penambahan Akun Copytrade Pertama oleh Investor (Tier 1)
- [ ] Login menggunakan akun investor yang baru didaftarkan di Skenario B.
- [ ] Masuk ke menu sidebar **Manajemen Copytrade** (`/investor/copytrade`).
- [ ] Perhatikan blok *"Informasi Master Akun"*: periksa bahwa Tautan Referral Broker dan Tautan Master sesuai dengan konfigurasi Admin.
- [ ] Klik tombol **Tambah Akun**.
- [ ] Modal terbuka:
  - Periksa pilihan dropdown **Tier Investasi**: Tier 1, Tier 2, Tier 3.
  - Pilih **Tier 1**.
  - Isi **Nomor Akun Trading**: `11223344`.
  - Isi **Modal Riil (USD)**: `500`.
  - Isi **Informasi Bank**: BCA, No. Rek: `0123456789`, An: Nama Investor.
  - Periksa bagian bawah: Muncul instruksi *"Unggah Surat Perjanjian"* karena ini pendaftaran pertama.
- [ ] Klik tombol unduh template perjanjian Tier 1: pastikan file template DOCX berhasil terunduh.
- [ ] Unggah berkas surat perjanjian (gambar atau PDF).
- [ ] Klik **Simpan Pendaftaran**.
- [ ] **Ekspektasi:** Modal tertutup, muncul notifikasi toast sukses, dan pada tabel muncul baris akun dengan status badge kuning/amber: **Menunggu Verifikasi (Pending)**.

### Skenario D: Peninjauan & Persetujuan Akun Copytrade oleh Admin
- [ ] Login kembali ke akun Administrator.
- [ ] Buka menu **Manajemen Copytrade** $\rightarrow$ **Verifikasi Copytrade** (`/admin/copytrade`).
- [ ] Pada tab filter status **Menunggu Verifikasi**, cari nomor akun investor tadi (`11223344`).
- [ ] Klik tombol aksi **Tinjau** (ikon mata).
- [ ] Modal *Tinjau Pendaftaran Copytrade* terbuka:
  - Periksa data investor dan data copytrade (Tier 1, Modal \$500).
  - Klik tautan **Lihat Berkas** pada bagian Surat Perjanjian: pastikan berkas PDF/gambar terbuka di tab baru secara aman melalui streaming privat `/documents/copytrade/{id}`.
  - Pilih radio opsi **Terima (Aktif)**.
  - Klik **Simpan Keputusan**.
- [ ] **Ekspektasi:** Status akun berubah menjadi hijau: **Aktif (Active)** dan kolom `copy_started_at` terisi tanggal & waktu sekarang.

### Skenario E: Penambahan Akun Copytrade Kedua (Multi-Akun & Bebas Surat Perjanjian)
- [ ] Login kembali sebagai Investor di `/login`.
- [ ] Buka menu **Manajemen Copytrade** (`/investor/copytrade`).
- [ ] Klik kembali tombol **Tambah Akun**.
- [ ] Pilih **Tier 2** (misal modal \$5,000), isi nomor MT4 baru `22334455`, dan lengkapi data rekening.
- [ ] **Perhatikan bagian bawah modal:** Muncul badge hijau/informasi bahwa pendaftaran ini adalah akun tambahan sehingga **tidak diwajibkan mengunggah surat perjanjian lagi**.
- [ ] Klik **Simpan Pendaftaran** tanpa melampirkan berkas perjanjian.
- [ ] **Ekspektasi:** Berhasil tersimpan tanpa error validasi, dan investor kini memiliki 2 baris akun copytrade pada tabel portofolionya.

### Skenario F: Penambahan Akun Tier 3 & Pengisian Kredensial VPS Terenkripsi
- [ ] Sebagai investor, klik **Tambah Akun** untuk akun ke-3.
- [ ] Pilih **Tier 3**.
- [ ] **Ekspektasi:** Muncul kotak form tambahan khusus Tier 3:
  - Input Server Broker (misal: `HFMarkets-Live-Server2`)
  - Input Master Password MT4
  - Input Investor Password MT4
  - Input URL MQL5 (opsional)
- [ ] Isi semua field dan simpan pendaftaran.
- [ ] Login sebagai Admin dan buka modal peninjauan akun Tier 3 tersebut.
- [ ] **Ekspektasi:** Admin dapat melihat kredensial server dan password yang telah didekripsi secara aman di dalam modal admin.

### Skenario G: Notifikasi In-App Seluruh Siklus Copytrade
- [ ] Saat investor menambahkan akun copytrade (Skenario C/E/F):
  - Periksa lonceng notifikasi Administrator (`AppNotificationBell.vue`).
  - **Ekspektasi:** Muncul badge merah notifikasi baru dengan judul *"Pengajuan Akun Copytrade Baru"*, teks memuat nama investor & nomor akun trading, dan ikon `file-check`.
  - Klik notifikasi: diarahkan langsung ke halaman `/admin/copytrade`.
- [ ] Saat Administrator menyetujui akun copytrade (Skenario D):
  - Login sebagai Investor terkait dan periksa lonceng notifikasi.
  - **Ekspektasi:** Muncul notifikasi baru *"Akun Copy Trade Valid & Aktif"*, ikon `check-circle`, dan status akun aktif.
  - Klik notifikasi: diarahkan ke `/investor/copytrade`.
- [ ] Saat Administrator menolak akun copytrade dengan catatan perbaikan:
  - Periksa lonceng notifikasi Investor.
  - **Ekspektasi:** Muncul notifikasi *"Akun Copy Trade Ditolak"*, ikon `x-circle`, pesan memuat catatan instruksi penolakan dari admin.
  - Klik notifikasi: diarahkan ke `/investor/copytrade`.

---

## 4. Standar Responsivitas & Aksesibilitas Visual
- [ ] Buka browser Developer Tools $\rightarrow$ Device Mode (resolusi ponsel 360px – 430px).
- [ ] Buka `/investor/copytrade` dan periksa:
  - Tombol **Tambah Akun** tetap terlihat dan mudah ditekan (touch target $\ge$ 40px).
  - Kartu informasi master broker tersusun bertumpuk vertikal (*stacked*) tanpa overflow horizontal liar.
  - Tabel daftar copytrade memiliki horizontal scroll (`overflow-x-auto`) yang mulus.
- [ ] Buka modal **Tambah Akun** di ponsel:
  - Dialog modal memenuhi lebar layar dengan padding ergonomis, scroll vertikal lancar tanpa pergeseran layout.
- [ ] Buka `/admin/copytrade` di ponsel:
  - Tombol filter status tab tersusun rapi atau dapat digulir horizontal.
  - Modal verifikasi admin dapat di-scroll vertikal dan tombol simpan keputusan tetap mudah diakses.
- [ ] Periksa kontras warna (Light & Dark Mode):
  - Teks informasi tidak pudar (kontras teks $\ge$ 4.5:1).
  - Badge status (Pending, Aktif, Ditolak) menggunakan warna kontras solid dan jelas terbaca.
