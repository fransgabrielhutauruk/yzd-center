# Software Requirements Specification (SRS)

**Nama Proyek:** YZD Center  
**Klien:** Pak Rizki Abiyasid  
**Versi Dokumen:** 1.3.0  
**Tanggal Update Terakhir:** 21 Oktober 2026  

---

## Daftar Riwayat Perubahan (Changelog)

*   **v1.0.0 (25-08-2026):** Draft inisiasi SRS dan penetapan arsitektur fase 1 (MVP).
*   **v1.1.0 (05-09-2026):** Penyelarasan aturan klaim komisi afiliasi individual pro-rata 3 bulan, formulir data klausul ahli waris, dan alur verifikasi penyambungan CopyTrade 2-tahap (stepper).
*   **v1.2.0 (08-09-2026):** Penambahan kapabilitas Progressive Web App (PWA) dengan tombol install dinamis, modul panduan pengguna (tur aplikasi & jelajah halaman), serta modul kalkulator simulasi proyeksi keuntungan bulanan investor.
*   **v1.3.0 (21-10-2026):** Pembaruan menyeluruh sistem sesuai kondisi operasional terkini:
    *   **Penyelarasan Profit Sharing:** Pembaruan formula bagi hasil menjadi **30% ditarik investor (WD bulanan), 30% performance fee Master Trader, dan 40% compounding ke modal** pada mode Tarik Rutin (serta opsi Reinvest Penuh: 0% WD, 30% fee master, dan 70% compounding).
    *   **Standarisasi 4-Tier Komisi Afiliasi:** Penegasan skema berjenjang 4-tier berdasarkan modal deposit per akun dalam IDR (>10M: 50%, 1M–10M: 40%, 100Jt–1M: 30%, <100Jt: 20%) yang dipotong dari jatah 30% Master Trader dengan proteksi *Drawdown Guard* dan masa aktif minimal 90 hari.
    *   **Spesifikasi 3 Tier Akun Copytrade:** Tier 1 (Cent < $5,000 via HFM), Tier 2 (Standard $5,000–$300,000 via XM), dan Tier 3 (Direct VPS ≥ $300,000 via XM dengan enkripsi kredensial MT4/VPS dan masa kontrak kemitraan 5 tahun).
    *   **Modul Portofolio & Pencatatan Saldo Bulanan:** Pencatatan saldo setiap tanggal 1, pengingat in-app otomatis, metrik pertumbuhan modal, dan analisis titik impas (*Break Even Point* / BEP).
    *   **Otomatisasi Dokumen Legalitas:** Pembuatan dokumen draft Surat Perjanjian kemitraan otomatis terisi berbasis template `.docx` Word untuk dicetak, ditandatangani, dan diunggah kembali.
    *   **Manajemen Pengguna & Alih Peran:** Fitur alih peran pengguna (*Role Switch* Investor $\leftrightarrow$ Affiliator) dengan auto-generate kode referral unik.
    *   **Log Aktivitas Imutabel & Audit Diff:** Audit trail permanen dengan penyamaran data sensitif otomatis, pencatatan snapshot sebelum/sesudah, dan ekspor spreadsheet XLSX.
    *   **Pemantauan Galat Real-Time:** Integrasi Sentry Cloud untuk pelacakan bug teknis mendalam dan Bot Telegram Alerting untuk notifikasi insiden fatal 5xx seketika dengan tautan langsung (*deep-link*).

---

## 1. Pendahuluan

### 1.1 Tujuan Sistem
YZD Center dirancang sebagai platform pendukung operasional terpusat (*back-office*, etalase, dan pembukuan) bagi ekosistem bisnis *copy trading* yang dikelola oleh Master Trader (Pak Rizki Abiyasid). Sistem ini bertujuan untuk:
1. Mengotomatisasi manajemen portofolio dan pemantauan kinerja akun investor.
2. Mengelola verifikasi pendaftaran tertutup (*closed-loop*), kepatuhan identitas (KYC KTP), dan klausul ahli waris.
3. Mengatur pembagian profit sharing berbasis aturan *compounding* modal dan *performance fee*.
4. Mengotomatisasi perhitungan komisi jaringan afiliasi berjenjang (*4-Tier*) secara pro-rata dan adil.
5. Memfasilitasi penerbitan dan pengunggahan surat perjanjian kemitraan bermaterai.
6. Menyediakan pusat edukasi, kalkulator simulasi finansial, jejak audit aktivitas yang imutabel, dan pemantauan stabilitas sistem real-time.

### 1.2 Batasan Sistem (Out of Scope)
*   **Zero Direct Payment Processing:** Sistem **tidak bertindak sebagai Payment Gateway** dan tidak memproses transfer deposit maupun penarikan modal trading secara langsung. Semua aktivitas setor dana (*deposit*) dan penarikan modal (*withdrawal*) trading dilakukan secara mandiri oleh investor langsung di broker resmi berlisensi eksternal (HFM dan XM).
*   **Pendaftaran Tertutup (No Public Registration):** Tidak ada registrasi terbuka untuk umum. Pendaftaran hanya dapat dilakukan melalui tautan undangan eksklusif (*Invitation Link*) yang diterbitkan oleh Administrator.
*   **Bukan Platform Eksekusi Order (No Direct Execution):** Sistem tidak mengeksekusi order buy/sell pasar valas, melainkan mencatat dan memantau akun copy trade yang telah tersambung di sisi broker.

---

## 2. Deskripsi Pengguna (Actors)

Sistem memiliki 3 level hak akses utama dengan otorisasi berbasis peran:

1.  **Administrator (Super User):**
    *   Membuat dan mengelola tautan undangan registrasi (*Invitation Link*).
    *   Memverifikasi kelengkapan dokumen pendaftaran dan KYC KTP investor/afiliator.
    *   Meninjau dan menyetujui/menolak pengajuan akun copytrade (Tier 1, Tier 2, Tier 3) serta memeriksa kredensial MT4/VPS.
    *   Memverifikasi permohonan klaim relasi referral susulan yang diajukan affiliator.
    *   Memvalidasi pengajuan klaim komisi afiliasi, menginput nilai ROI Master Trader, melakukan rekonsiliasi transfer bank, dan mengunggah resi bukti transfer.
    *   Mengelola pengguna (aktifkan, suspend, alih peran Investor $\leftrightarrow$ Affiliator).
    *   Mengelola artikel edukasi, tanya-jawab (FAQ), kamus istilah forex (*Glossary*), dan dokumen legal platform.
    *   Menginspeksi log aktivitas sistem dan mengekspor rekap audit ke format Excel (.xlsx).
    *   Menerima notifikasi darurat galat sistem real-time melalui integrasi Bot Telegram & Sentry.

2.  **Investor:**
    *   Mendaftar melalui tautan undangan resmi dan melengkapi data profil, NIK, alamat, data bank, serta klausul ahli waris.
    *   Mengunggah dokumen identitas resmi (KTP) dan menyetujui Syarat & Ketentuan serta Kebijakan Privasi.
    *   Mendaftarkan satu atau lebih akun trading copytrade (Tier 1, Tier 2, atau Tier 3) dengan mengunduh draf surat perjanjian otomatis (`.docx`) dan mengunggah dokumen yang telah ditandatangani basah.
    *   Mencatat saldo terkini (*balance snapshot*) setiap tanggal 1 setiap bulan untuk memantau pertumbuhan modal dan metrik Break Even Point (BEP).
    *   Memantau statistik konsolidasi portofolio investasi dan grafik riwayat profit/loss.
    *   Menggunakan Kalkulator Simulasi Proyeksi Keuntungan interaktif dengan live exchange rate USD/IDR.
    *   Mengakses pusat edukasi, kamus forex, serta panduan tur aplikasi interaktif.

3.  **Affiliator (Mitra Kemitraan):**
    *   Memiliki seluruh kapabilitas fungsional Investor.
    *   Memiliki kode referral dan tautan afiliasi unik untuk merekrut anggota baru (*downline*).
    *   Memantau daftar pengikut (*downline*) pada jaringan kemitraannya beserta status durasi aktif akun copytrade masing-masing.
    *   Mengajukan permohonan klaim referral susulan beserta tangkapan layar bukti *chat* apabila calon downline lupa mencantumkan kode saat mendaftar.
    *   Mengajukan permohonan pencairan komisi afiliasi berjenjang (*4-Tier*) bagi downline yang telah aktif minimal 90 hari (3 bulan).
    *   Memantau status pencairan komisi dan mengunduh bukti resi transfer resmi dari Administrator.

---

## 3. Aturan Bisnis (Business Rules)

### 3.1 Aturan Bagi Hasil Profit (Profit Sharing Scheme)
Keuntungan kotor (*Gross Profit*) yang dihasilkan dari aktivitas trading copy trade tidak dialokasikan untuk penarikan penuh 100%, melainkan diatur dengan formula proporsional untuk menjamin kesinambungan modal:

#### A. Mode Tarik Rutin (*With Withdrawal* — Standar Operasional)
Diterapkan bagi investor yang memilih menikmati hasil keuntungan setiap bulan:
*   **30%** dapat ditarik (*Withdrawal* / WD rutin) oleh Investor ke rekening bank pribadi.
*   **30%** dialokasikan kepada Master Trader (Pak Rizki Abiyasid) sebagai *Performance Fee*.
*   **40%** dibiarkan tetap berada di dalam akun broker sebagai *Compounding* (penambahan modal trading secara bertahap).

#### B. Mode Reinvest Penuh (*Without Withdrawal* — Akselerasi Pertumbuhan Modal)
Diterapkan bagi investor yang memprioritaskan pertumbuhan modal maksimal secara eksponensial:
*   **0%** ditarik oleh Investor (seluruh hak keuntungan digulung kembali ke modal).
*   **30%** dialokasikan kepada Master Trader sebagai *Performance Fee*.
*   **70%** dialokasikan sebagai *Compounding* penuh ke dalam modal trading investor.

---

### 3.2 Klasifikasi 3-Tier Akun Copytrade
Untuk menyesuaikan profil risiko, kapasitas dana, dan teknologi penyambungan broker, akun trading copytrade diklasifikasikan ke dalam 3 tier:

| Parameter | Tier 1 (Cent) | Tier 2 (Standard) | Tier 3 (Direct VPS) |
| :--- | :--- | :--- | :--- |
| **Rentang Modal Riil** | $< \$5,000\text{ USD}$ | $\$5,000\text{ USD} \text{ s.d. } \$300,000\text{ USD}$ | $\ge \$300,000\text{ USD}$ |
| **Broker Rekanan** | **HFM** (HF Markets) | **XM** (XM Global) | **XM** (XM Global) |
| **Tipe Akun Broker** | `Cent Account` | `Standard Account` | `Private VPS Account` |
| **Metode Copy** | Copy Trading Pool Broker | Copy Trading Pool Broker | Direct Copy via Private VPS |
| **Kredensial Tambahan** | Nomor Akun Trading | Nomor Akun Trading | Server Broker, Master Password, Investor Password (Enkripsi Kuat) |
| **Masa Komitmen** | Fleksibel | Fleksibel | **5 Tahun** (Kemitraan Jangka Panjang) |
| **Template Perjanjian** | `tier_1_hfm.docx` | `tier_2_xm.docx` | `tier_3_xm.docx` |

---

### 3.3 Skema Komisi Afiliasi Berjenjang (4-Tier Affiliate Commission)
Afiliator berhak memperoleh komisi atas keuntungan yang dibukukan oleh *downline* yang diajaknya. Nilai persentase komisi ditentukan berdasarkan nominal modal deposit akun *downline* masing-masing (dikonversi dari USD ke IDR dengan kurs pasar riil):

| Level Tier | Rentang Modal Deposit Downline (IDR) | Persentase Komisi Afiliasi |
| :---: | :--- | :---: |
| **Tier 1** | Modal $> \text{Rp } 10.000.000.000$ ($> \text{Rp } 10\text{ Milyar}$) | **50%** dari jatah Master Trader |
| **Tier 2** | $\text{Rp } 1.000.000.000 \text{ s.d. } \text{Rp } 10.000.000.000$ ($\text{Rp } 1\text{ M} - \text{Rp } 10\text{ M}$) | **40%** dari jatah Master Trader |
| **Tier 3** | $\text{Rp } 100.000.000 \text{ s.d. } \text{Rp } 1.000.000.000$ ($\text{Rp } 100\text{ Jt} - \text{Rp } 1\text{ M}$) | **30%** dari jatah Master Trader |
| **Tier 4** | $< \text{Rp } 100.000.000$ ($< \text{Rp } 100\text{ Juta}$) | **20%** dari jatah Master Trader |

#### Formula Perhitungan & Proteksi Finansial:
1.  **Sumber Pembayaran Komisi:** Komisi afiliator **dipotong dari jatah 30% Master Trader** (`master_gross_share`), **bukan** memotong jatah hak keuntungan investor. Hak bersih investor tetap terjaga 70% dari gross profit.
    $$\text{gross\_profit} = \text{deposit\_amount} \times \left(\frac{\text{ROI}}{100}\right)$$
    $$\text{master\_gross\_share} = \text{gross\_profit} \times 30\%$$
    $$\text{affiliate\_commission} = \text{master\_gross\_share} \times \left(\frac{\text{Persentase Tier}}{100}\right)$$
    $$\text{master\_net\_share} = \text{master\_gross\_share} - \text{affiliate\_commission}$$
    $$\text{investor\_net\_profit} = \text{gross\_profit} - \text{master\_gross\_share}$$
2.  **Mekanisme Proteksi Kerugian (*Drawdown Guard*):**
    Apabila performa trading pada periode evaluasi mengalami kerugian atau impas ($\text{gross\_profit} \le 0$):
    *   $\text{affiliate\_commission} = \$0.00$ (tidak ada komisi yang dibayarkan).
    *   $\text{master\_gross\_share} = \$0.00$.
    *   Kerugian tidak dibebankan kepada pihak affiliator.
3.  **Masa Tunggu Kelayakan (*Eligibility Period*):** Akun trading downline wajib telah berstatus aktif minimal **90 hari (3 bulan)** terhitung sejak tanggal mulai copy trade sebelum komisi pertamanya dapat diajukan pencairan oleh affiliator.
4.  **Perhitungan Mandiri Multi-Akun (*Per-Account Pro-Rata*):** Jika seorang investor memiliki lebih dari satu akun copytrade, penentuan Tier komisi dihitung secara independen per akun, tidak digabungkan.

---

### 3.4 Klausul Ahli Waris & Kepatuhan Hukum
*   Setiap investor wajib menunjuk 1 (satu) orang pihak ahli waris sah (Nama Lengkap, Hubungan Kekeluargaan, Kontak HP/WhatsApp, dan Alamat Domisili).
*   Perekaman data ahli waris berfungsi sebagai mitigasi risiko kepemilikan dan penerusan hak atas akun investasi apabila terjadi keadaan darurat, kecelakaan fatal, atau investor meninggal dunia.
*   Pendaftaran akun copytrade mewajibkan penandatanganan Surat Perjanjian Kemitraan bermaterai Rp 10.000.

---

## 4. Kebutuhan Fungsional (Functional Requirements)

### 4.1 Modul Autentikasi, Registrasi Jalur Tertutup & KYC
*   **FR-AUTH-01:** Sistem membatasi pendaftaran hanya melalui tautan undangan unik (`/register?token=...`) yang diterbitkan Administrator.
*   **FR-AUTH-02:** Halaman utama (`/`) hanya menyediakan form login pengguna terdaftar dan tautan bantuan WhatsApp Admin.
*   **FR-AUTH-03:** Formulir pendaftaran merekam secara terintegrasi:
    *   *Akun:* Nama Pengguna (Username), Surel (Email), dan Kata Sandi.
    *   *Profil & Legalitas:* Nama Lengkap, NIK (16 digit), Tempat & Tanggal Lahir, Pekerjaan, Alamat Domisili, No. WhatsApp.
    *   *Klausul Ahli Waris:* Nama, Hubungan, Kontak, dan Alamat Ahli Waris.
    *   *Rekening Bank:* Nama Bank, Nomor Rekening, dan Atas Nama Rekening.
    *   *Dokumen KYC:* Berkas foto identitas resmi KTP.
*   **FR-AUTH-04:** Penegasan persetujuan Syarat & Ketentuan Layanan serta Kebijakan Privasi saat registrasi dengan rekam jejak waktu (*timestamped consent*).
*   **FR-AUTH-05:** Pengguna dengan status dokumen `revision_needed` dapat mengunggah ulang dokumen KTP perbaikan melalui antarmuka khusus tanpa perlu mendaftar ulang.

### 4.2 Modul Administrator
*   **FR-ADM-01 (Tautan Undangan):** Administrator dapat membuat tautan undangan dengan label, target peran (`investor` atau `affiliator`), batas kuota pemakaian, masa berlaku (kadaluarsa), serta melakukan penghapusan massal (*batch delete*).
*   **FR-ADM-02 (Audit KYC):** Administrator meninjau dokumen KTP pengguna melalui *streaming* privat aman, memberikan status persetujuan (`approved`) atau permintaan revisi (`revision_needed`) dengan catatan admin yang komprehensif.
*   **FR-ADM-03 (Kelola Copytrade):**
    *   Meninjau antrean pendaftaran akun trading copytrade baru (memeriksa nomor akun, broker, dan modal riil).
    *   Menyetujui (`active`) atau menolak (`rejected`) akun beserta catatan resmi.
    *   Mengakses dan mengunduh berkas surat perjanjian kemitraan yang diunggah investor.
    *   Menginspeksi kredensial MT4/VPS khusus akun Tier 3 dengan pencatatan audit log berlevel peringatan (*warning*).
*   **FR-ADM-04 (Klaim Referral Susulan):** Meninjau permohonan affiliator yang mengklaim downline lupa kode referral, memeriksa bukti tangkapan layar percakapan (*chat*), dan menyetujui pengikatan (*binding*) relasi referral secara sah.
*   **FR-ADM-05 (Pencairan Komisi Afiliasi):**
    *   Menampilkan antrean klaim komisi berstatus pending lengkap dengan rincian rekening bank tujuan.
    *   Menginput nilai performa ROI (%) Master Trader untuk kalkulasi otomatis nominal bagi hasil dan nilai transfer IDR sesuai 4-tier modal.
    *   Memproses status transfer dan mengunggah bukti resi transfer bank resmi (`.jpg`, `.png`, `.pdf`).
*   **FR-ADM-06 (Manajemen Pengguna):**
    *   Menampilkan direktori seluruh pengguna platform dengan filter peran, status, dan pencarian cepat.
    *   Mengubah status aktifasi akun pengguna (`active` $\leftrightarrow$ `suspended`).
    *   Mengalihkan peran pengguna (*Role Switch*: Investor $\rightarrow$ Affiliator atau sebaliknya). Saat pengguna dialihkan menjadi Affiliator, sistem secara otomatis menerbitkan kode referral unik baru.
*   **FR-ADM-07 (Pusat Edukasi & Pengaturan Legal):**
    *   Mengelola artikel bantuan FAQ (tambah, edit dengan editor teks kaya, susun urutan, hapus).
    *   Mengelola Glosarium / Kamus Istilah Forex (*Glossary*).
    *   Memperbarui dokumen hukum platform (Syarat & Ketentuan serta Kebijakan Privasi) dengan pencatatan versi dokumen legal.
*   **FR-ADM-08 (Audit Trail & Log Aktivitas):**
    *   Menampilkan tabel aktivitas sistem terpaginasi (*server-side pagination*) dengan filter kategori, aksi, peran, tingkat risiko, dan tanggal.
    *   Menampilkan modal inspeksi rincian perubahan data (*audit diff*: nilai sebelum vs nilai sesudah).
    *   Mengekspor laporan jejak audit log aktivitas ke format spreadsheet Excel (.xlsx).
*   **FR-ADM-09 (Materi Promosi / Profit Master):**
    *   Menerbitkan materi promosi resmi berupa caption teks (hingga 20.000 karakter) beserta lampiran foto bukti transaksi/profit.
    *   Foto dikompresi otomatis di sisi klien sebelum diunggah dan disimpan pada direktori privat terisolasi.
    *   Mengedit caption, menambah/menghapus gambar pendukung, atau menghapus materi promosi secara permanen beserta berkas fisiknya.
*   **FR-ADM-10 (Tiket Bantuan & Pelaporan Kendala):**
    *   Memantau antrean tiket kendala member via dasbor ("Tiket Butuh Respons") dan tabel manajemen terpadu `AppDataTable`.
    *   Mengirimkan tanggapan/solusi dua arah dengan lampiran gambar panduan, mengubah status kendala secara manual, dan memicu notifikasi in-app ke pengguna.
    *   Sistem scheduler otomatis menutup (*Closed*) tiket berstatus *Resolved* setelah 7 hari tanpa aktivitas.

### 4.3 Modul Investor
*   **FR-INV-01 (Portofolio Investasi Multi-Akun):**
    *   Investor dapat mendaftarkan satu atau lebih akun trading copy trade (Tier 1, Tier 2, atau Tier 3).
    *   Formulir pendaftaran akun menyediakan fitur **Unduh Draf Surat Perjanjian Otomatis** (template Word `.docx` yang telah terisi data nama investor, nomor akun, modal, dan rekening bank). Investor mencetak dokumen, menempelkan materai, menandatangani, lalu mengunggahnya kembali.
    *   Khusus Tier 3, formulir mewajibkan pengisian kredensial MT4/VPS (Server Broker, Master Password, Investor Password) yang disimpan dengan enkripsi reversibel aman.
*   **FR-INV-02 (Pencatatan Saldo Bulanan & BEP):**
    *   Investor dapat mencatat saldo terkini (*balance MT4*) secara mandiri setiap tanggal 1 (periode hari ke-1 s.d. 5 setiap bulan).
    *   Sistem otomatis menghitung keuntungan nominal ($USD & Rp IDR), persentase pertumbuhan akun (*growth rate*), serta metrik Break Even Point (BEP).
    *   Sistem menampilkan spanduk pengingat (*In-App Monthly Reminder*) di bagian atas dasbor pada tanggal 1 s.d. 5 setiap bulannya.
    *   Investor dapat melihat grafik tren pertumbuhan saldo dan mengedit entri saldo jika terjadi kekeliruan input.
*   **FR-INV-03 (Dasbor Portofolio Konsolidasi):**
    *   Menampilkan total modal awal terkonsolidasi, total saldo berjalan, total profit nominal, dan persentase pertumbuhan total dari seluruh akun aktif milik investor.
    *   Menyediakan filter analitik kinerja akun: Semua Waktu (*All*), 1 Bulan, 3 Bulan, 6 Bulan, 1 Tahun, atau Rentang Tanggal Kustom.
*   **FR-INV-04 (Kalkulator Simulasi Finansial Interaktif):**
    *   Menyediakan simulasi matematis proyeksi pertumbuhan modal untuk jangka waktu 1 hingga 10 tahun (standar 5 tahun).
    *   Mendukung komparasi 2 skenario: **Tarik Rutin** (30% WD, 30% Master Fee, 40% Compounding) dan **Reinvest Penuh** (0% WD, 30% Master Fee, 70% Compounding).
    *   Dilengkapi nilai acuan standar ($500 modal awal, 5.0% profit bulanan) serta pembaruan kurs tengah USD/IDR harian secara otomatis.
    *   Menyajikan hasil dalam bentuk ringkasan komparasi metrik, tabel proyeksi tahunan, dan tabulasi bulanan.
*   **FR-INV-05 (Panduan Interaktif):**
    *   *Tur Aplikasi (Onboarding Tour):* Panduan visual pengenalan sistem langkah-demi-langkah bagi investor baru.
    *   *Jelajah Halaman (Feature Walkthrough):* Panduan interaktif terpandu dengan penyorotan fokus (*spotlight*) pada elemen antarmuka utama di dasbor.
*   **FR-INV-06 (Pusat Bantuan & Glosarium):** Menelusuri artikel tanya-jawab, panduan setor/tarik broker, dan mencari definisi istilah trading di Kamus Forex.

### 4.4 Modul Affiliator
*   **FR-AFF-01:** Menampilkan kartu kode referral, tautan pendaftaran referral personal, dan tombol salin cepat (*one-click copy*).
*   **FR-AFF-02:** Menampilkan pohon direktori downline aktif: nama, username, tanggal pendaftaran, status akun copytrade, modal riil, dan kalkulasi durasi hari aktif.
*   **FR-AFF-03:** Mengajukan klaim relasi referral susulan dengan menyertakan username downline, alasan keterlambatan, dan berkas foto bukti tangkapan layar percakapan WhatsApp.
*   **FR-AFF-04:** Mengajukan permohonan pencairan komisi afiliasi berjenjang untuk downline yang telah melewati masa aktif minimal 90 hari.
*   **FR-AFF-05:** Memantau riwayat klaim komisi: melihat kode klaim unik, rincian pembagian persentase tiering modal, status verifikasi admin, dan mengunduh bukti transfer bank resmi.
*   **FR-AFF-06 (Materi Promosi & Berbagi Bahan Pemasaran):**
    *   Mengakses umpan (*feed*) materi promosi resmi Master Trader yang kronologis dan dapat dicari.
    *   Menyediakan tombol cepat Salin Caption, Unduh Foto, dan Bagikan langsung (*Web Share API*) ke aplikasi WhatsApp / media sosial.
*   **FR-MBR-01 (Tiket Bantuan & Pelaporan Kendala Member):**
    *   Seluruh member terautentikasi (termasuk member berstatus pending KYC) dapat membuat tiket bantuan dengan penomoran unik bulanan `TKT-YYYYMM-XXXX`.
    *   Menyematkan berkas tangkapan layar bukti kendala (hingga 10 MB per gambar dengan kompresi senyap sisi klien).
    *   Melakukan interaksi utas balasan dua arah dengan admin, menandai tiket selesai (*Resolved*), atau membuka kembali (*Reopen*) jika kendala berulang.

### 4.5 Modul Notifikasi, Pemantauan Galat & Keamanan
*   **FR-SEC-01 (Notifikasi In-App):** Notifikasi lonceng *real-time* untuk pembaruan status penting (verifikasi KYC disetujui/ditolak, akun copytrade aktif, klaim referral diterima, komisi ditransfer, dan pengingat saldo tanggal 1).
*   **FR-SEC-02 (Penyimpanan Berkas Privat):** Berkas sensitif (KTP, surat perjanjian, bukti transfer, bukti chat) disimpan pada penyimpanan privat terisolasi (`storage/app/private`) dan hanya dapat dialirkan via otorisasi ketat controller.
*   **FR-SEC-03 (Enkripsi Data Kredensial):** Password master, investor password MT4, dan data rahasia VPS dienkripsi menggunakan algoritma simetris AES-256-CBC bawaan Laravel (`casts => 'encrypted'`).
*   **FR-SEC-04 (Audit Trail Imutabel):** Rekam jejak aktivitas tersimpan di tabel `log_aktivitas` yang diproteksi secara permanen (operasi `update` dan `delete` dinonaktifkan di level model Eloquent). Kredensial sensitif otomatis disensor (*masked*) sebelum dicatat.
*   **FR-SEC-05 (Pemantauan Error Sentry & Telegram):**
    *   Integrasi **Sentry Cloud** melacak seluruh *unhandled exception*, *stack trace*, *breadcrumbs*, dan nomor baris galat di backend maupun frontend.
    *   Integrasi **Bot Telegram Alerting** mengirimkan ringkasan insiden darurat 5xx secara seketika (*real-time*) ke perangkat seluler pengembang, lengkap dengan URL rute, metode HTTP, status otentikasi pengguna, dan tautan langsung (*deep-link*) menuju dasbor investigasi Sentry terkait.
    *   Mekanisme pembatasan lonjakan (*throttling*) 10 menit mencegah banjir notifikasi Telegram jika terjadi loop galat berulang.

---

## 5. Kebutuhan Non-Fungsional & Arsitektur Sistem

### 5.1 Spesifikasi Teknologi (Tech Stack)
*   **Platform Runtime:** PHP 8.3 (Windows Laragon Environment)
*   **Framework Backend:** Laravel 11
*   **Arsitektur Komunikasi:** Inertia.js (Monolith Modern tanpa API publik terpisah)
*   **Framework Frontend:** Vue 3 (Composition API dengan `<script setup>`)
*   **Styling & UI Components:** Tailwind CSS, Lucide Vue Next Icons, Shadcn-Vue Primitives
*   **Basis Data:** MySQL 8.0 (InnoDB Engine, utf8mb4 collation)
*   **Penyusunan Berkas:** PhpOffice/PhpWord (Generasi otomatis Surat Perjanjian `.docx`), Fast-Excel / Spatie Simple Excel (Ekspor `.xlsx`)
*   **Optimasi Gambar:** Native Canvas API (`imageCompression.js`) untuk kompresi otomatis client-side sebelum berkas dikirim ke server.
*   **Pemantauan & Error Alerting:** Sentry Laravel SDK (`sentry/sentry-laravel`), Sentry Vue SDK, dan Telegram Bot API (eksekusi antrean asinkron via `SendTelegramAlertJob`).

### 5.2 Standar Antarmuka & Pengalaman Pengguna (UI/UX)
*   **Mobile-First Responsive Design:** Seluruh fitur dapat diakses penuh secara ergonomis pada layar *smartphone* (360px – 430px) dengan target sentuh (*touch target*) minimal 44px. Filter tabel dan tombol aksi tidak disembunyikan di layar kecil.
*   **Konsistensi Kontras & Anti-AI Slop (WCAG AA):** Tipografi solid, hierarki kontras seimbang di Mode Terang (*Light Mode*) dan Mode Gelap (*Dark Mode*), bebas dari animasi gradien ungu neon liar (*neon glow*) atau teks *copywriting* klise buatan mesin.
*   **Sistem Notifikasi Toast Tunggal:** Umpan balik aksi menggunakan `AppToaster.vue` berbasis pesan kilat sesi controller (`with('success', ...)`), mencegah kemunculan toast ganda.
*   **Zero Layout Shift:** Modul modal interaktif (`AppModal.vue` dan `AppConfirmDialog.vue`) dirancang tanpa pergeseran tata letak saat dibuka/ditutup.
*   **Progressive Web App (PWA):** Mendukung web app manifest dan service worker untuk instalasi mandiri di beranda ponsel/desktop dengan tombol instalasi dinamis yang otomatis tersembunyi dalam mode *standalone*.

---

## 6. Lampiran (Proses Bisnis & Purwarupa)

Berikut adalah lampiran diagram alur kerja (BPMN) beserta tautan (*link*) menuju purwarupa (*prototype*) antarmuka sistem:

> **Catatan Prototype:** Seluruh purwarupa antarmuka interaktif dapat diakses melalui portal terpusat:  
> **`https://yzd-trading-prototype.netlify.app/`**

1.  **Use Case Diagram Sistem:**  
    `assets/UseCaseDiagram_v1.1.0.jpg`
2.  **Alur Pendaftaran & Verifikasi Akun Pengguna (BPMN):**  
    `assets/alur pendaftaran dan verifikasi akun pengguna.png`
3.  **Manajemen dan Perekaman Relasi Referral (BPMN):**  
    `assets/Alur Manajemen dan Perekaman Relasi Referral.png`
4.  **Pengajuan & Verifikasi Klaim Profit Afiliasi (BPMN):**  
    `assets/Alur Pengajuan Klaim Profit Affiliasi.png`
5.  **Pengajuan Klaim Relasi Referral Susulan (BPMN):**  
    `assets/Alur Pengajuan Klaim Relasi Referral Susulan.png`
6.  **Konfirmasi Penyambungan Akun CopyTrade via Eksternal Broker (BPMN):**  
    `assets/Alur Konfirmasi Penyambungan CopyTrade (Via Eksternal Broker).png`
