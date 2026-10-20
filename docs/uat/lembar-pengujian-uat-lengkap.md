# Lembar Pengujian UAT Praktis (*User Acceptance Testing Checklist*) — Platform YZD Center

**Versi Dokumen:** 1.0 (Final)  
**Target Pengguna:** Tim Penguji Klien (*Client Test Team*), Administrator & Tim QA/Pengembang  
**Tanggal Rilis Dokumen:** 18 Oktober 2026  
**Lingkungan Pengujian:** Lingkungan Lokal / Staging (`http://localhost:8000`)

---

## Daftar Isi

1. [Bab 1: Panduan Pengujian & Kredensial Akun Standar](#bab-1-panduan-pengujian--kredensial-akun-standar)
   - [1.1 Petunjuk Penggunaan Lembar UAT](#11-petunjuk-penggunaan-lembar-uat)
   - [1.2 Data Kredensial Akun Pengujian Bawaan (*Seeder*)](#12-data-kredensial-akun-pengujian-bawaan-seeder)
   - [1.3 Fitur Bantuan Otomatis Pengembang (*Dev Tool Autofill*)](#13-fitur-bantuan-otomatis-pengembang-dev-tool-autofill)
2. [Bab 2: Lembar Pengujian Portal Investor (`UAT-INV`)](#bab-2-lembar-pengujian-portal-investor-uat-inv)
   - [2.1 Registrasi Tertutup, Verifikasi KTP & Manajemen Akun](#21-registrasi-tertutup-verifikasi-ktp--manajemen-akun)
   - [2.2 Portofolio Copytrade, Kredensial & Saldo Bulanan](#22-portofolio-copytrade-kredensial--saldo-bulanan)
   - [2.3 Fitur Interaktif, Pusat Bantuan & Keamanan Akun](#23-fitur-interaktif-pusat-bantuan--keamanan-akun)
3. [Bab 3: Lembar Pengujian Portal Mitra Affiliator (`UAT-AFF`)](#bab-3-lembar-pengujian-portal-mitra-affiliator-uat-aff)
   - [3.1 Hak Akses Khusus & Tautan Referral Otomatis](#31-hak-akses-khusus--tautan-referral-otomatis)
   - [3.2 Pemantauan Jaringan Downline & Pengajuan Klaim Susulan](#32-pemantauan-jaringan-downline--pengajuan-klaim-susulan)
   - [3.3 Skema Komisi 4-Tier & Pengajuan Pencairan Bank](#33-skema-komisi-4-tier--pengajuan-pencairan-bank)
4. [Bab 4: Lembar Pengujian Portal Administrator (`UAT-ADM`)](#bab-4-lembar-pengujian-portal-administrator-uat-adm)
   - [4.1 Dashboard Analitik & Manajemen Tautan Undangan](#41-dashboard-analitik--manajemen-tautan-undangan)
   - [4.2 Verifikasi Registrasi KTP & Akun Copytrade (MT4/VPS)](#42-verifikasi-registrasi-ktp--akun-copytrade-mt4vps)
   - [4.3 Audit Jaringan Kemitraan & Putusan Klaim Susulan](#43-audit-jaringan-kemitraan--putusan-klaim-susulan)
   - [4.4 Pemrosesan Transfer Pencairan Komisi Afiliasi](#44-pemrosesan-transfer-pencairan-komisi-afiliasi)
   - [4.5 Manajemen Pengguna, Materi Edukasi & Pengaturan Legalitas](#45-manajemen-pengguna-materi-edukasi--pengaturan-legalitas)
5. [Bab 5: Skenario Pengujian Terintegrasi Lintas-Peran (`E2E`)](#bab-5-skenario-pengujian-terintegrasi-lintas-peran-e2e)
   - [5.1 E2E-01: Siklus Registrasi Undangan, Audit KYC & Aktivasi](#51-e2e-01-siklus-registrasi-undangan-audit-kyc--aktivasi)
   - [5.2 E2E-02: Siklus Pendaftaran Copytrade Tier 3 & Setup Kredensial VPS](#52-e2e-02-siklus-pendaftaran-copytrade-tier-3--setup-kredensial-vps)
   - [5.3 E2E-03: Siklus Klaim Susulan & Pembentukan Relasi Downline](#53-e2e-03-siklus-klaim-susulan--pembentukan-relasi-downline)
   - [5.4 E2E-04: Siklus Pencairan Komisi & Pengunggahan Bukti Transfer](#54-e2e-04-siklus-pencairan-komisi--pengunggahan-bukti-transfer)
6. [Bab 6: Rekapitulasi Hasil Pengujian & Lembar Catatan Temuan Klien](#bab-6-rekapitulasi-hasil-pengujian--lembar-catatan-temuan-klien)

---

## Bab 1: Panduan Pengujian & Kredensial Akun Standar

### 1.1 Petunjuk Penggunaan Lembar UAT
1. Lembar pengujian ini dirancang secara terstruktur agar perwakilan penguji dari pihak klien dapat memverifikasi kesesuaian sistem secara mandiri atau didampingi tim pengembang.
2. Setiap kasus uji (*Test Case*) memiliki kode unik, langkah tindakan yang terperinci, nilai data uji konkret, hasil yang diharapkan (*Expected Result*), serta kotak centang status:
   - `[ ] Lolos`: Fitur berjalan persis sesuai spesifikasi dan bebas galat (*error*).
   - `[ ] Gagal`: Terjadi kegagalan fungsionalitas, pesan galat tak tertangani, atau ketidaksesuaian antarmuka.
3. Jika ditemukan status `Gagal`, catat detail kendala pada **Bab 6: Lembar Catatan Temuan Klien**.

---

### 1.2 Data Kredensial Akun Pengujian Bawaan (*Seeder*)
Gunakan akun uji berikut yang telah tersedia di basis data sistem:

| Peran Akun (*Role*) | Username / Identitas | Kata Sandi (*Password*) | Status Akun & Profil | Tujuan Pengujian Utama |
| :--- | :--- | :--- | :--- | :--- |
| **Administrator** | `admin` | `admin` | Aktif (*Super User*) | Verifikasi gerbang, audit KTP, approval copytrade/VPS, transfer komisi. |
| **Investor** | `investor` | `investor` | Aktif (KTP Disetujui) | Pemantauan portofolio, input saldo tgl 1, kalkulator proyeksi, edukasi. |
| **Mitra Affiliator** | `affiliator` | `affiliator` | Aktif (KTP Disetujui) | Penyalinan link referral, pantau downline, klaim susulan, tarik komisi. |

---

### 1.3 Fitur Bantuan Otomatis Pengembang (*Dev Tool Autofill*)
Untuk mempercepat pengujian formulir panjang (khususnya saat menguji pendaftaran akun baru):
- Pada halaman `/register?invitation=...`, tersedia banner pengembang atau tombol melayang **"Isi Form Acak (Dev)"**.
- Mengeklik tombol ini akan mengisi secara instan seluruh data diri, NIK 16-digit unik, data ahli waris, kata sandi bawaan `asdf1234`, serta menghasilkan berkas simulasi KTP beresolusi tajam tanpa perlu menyiapkan dokumen fisik.

---

## Bab 2: Lembar Pengujian Portal Investor (`UAT-INV`)

### 2.1 Registrasi Tertutup, Verifikasi KTP & Manajemen Akun

| ID Pengujian | Fitur / Sub-Modul | Langkah-Langkah Pengujian | Data Uji / Nilai Input | Hasil yang Diharapkan (Ekspektasi) | Status Uji | Catatan Penguji |
| :---: | :--- | :--- | :--- | :--- | :---: | :--- |
| **UAT-INV-01** | Gerbang Tertutup (*Closed Registration*) | 1. Buka browser pada mode *Incognito*.<br>2. Akses URL: `http://localhost:8000/register` tanpa token undangan. | URL: `/register` (tanpa query string) | Sistem menolak akses form dan menampilkan halaman peringatan *"Tautan Undangan Tidak Valid atau Diperlukan"*. | [ ] Lolos<br>[ ] Gagal | |
| **UAT-INV-02** | Registrasi via Undangan Valid | 1. Buka tautan undangan valid (misal: `/register?invitation=TOKEN_UJI`).<br>2. Langkah 1: Lengkapi Data Diri & unggah foto KTP.<br>3. Langkah 2: Lengkapi Data Ahli Waris.<br>4. Langkah 3: Centang persetujuan syarat & klik **"Kirim Pendaftaran"**. | - Nama: `Budi Santoso`<br>- NIK: `3578012345678901`<br>- HP: `081234567890`<br>- User: `budi_investor`<br>- Pass: `Rahasia123!` | Pendaftaran berhasil dikirim, muncul pesan sukses, dan halaman dialihkan ke status *"Menunggu Verifikasi KTP"*. | [ ] Lolos<br>[ ] Gagal | |
| **UAT-INV-03** | Autentikasi Masuk (*Login*) | 1. Buka `http://localhost:8000/login`.<br>2. Masukkan username dan password salah $\rightarrow$ klik **"Masuk"**.<br>3. Masukkan kredensial benar (`investor` / `investor`) $\rightarrow$ klik **"Masuk"**. | User: `investor`<br>Pass salah: `salah`<br>Pass benar: `investor` | - Percobaan salah: Muncul validasi *"Kredensial tidak cocok"*.<br>- Percobaan benar: Berhasil masuk ke Dashboard Investor. | [ ] Lolos<br>[ ] Gagal | |
| **UAT-INV-04** | Restriksi Menu Akun Pending | 1. Login menggunakan akun yang baru mendaftar (belum diverifikasi Admin).<br>2. Perhatikan bilah samping (*sidebar*).<br>3. Coba akses paksa URL `/investor/portfolio`. | Akun status: `pending` | Menu Portofolio terkunci/tersembunyi. Akses paksa dialihkan kembali dengan pesan *"Akun Anda sedang dalam antrean verifikasi KTP"*. | [ ] Lolos<br>[ ] Gagal | |
| **UAT-INV-05** | Perbaikan Dokumen KYC (*Revisi KTP*) | 1. Login akun berstatus *Perlu Revisi*.<br>2. Buka menu **Informasi Akun**.<br>3. Baca catatan Admin, unggah foto KTP baru yang tajam, klik **"Unggah Ulang KTP"**. | Berkas KTP revisi format JPG/PNG | Berkas berhasil diperbarui, status berubah kembali menjadi *Menunggu Verifikasi*, catatan revisi diperbarui. | [ ] Lolos<br>[ ] Gagal | |

---

### 2.2 Portofolio Copytrade, Kredensial & Saldo Bulanan

| ID Pengujian | Fitur / Sub-Modul | Langkah-Langkah Pengujian | Data Uji / Nilai Input | Hasil yang Diharapkan (Ekspektasi) | Status Uji | Catatan Penguji |
| :---: | :--- | :--- | :--- | :--- | :---: | :--- |
| **UAT-INV-06** | Dashboard Utama Investor | 1. Login sebagai `investor` / `investor`.<br>2. Buka halaman `/investor/dashboard`.<br>3. Amati kartu ringkasan keuangan. | Halaman: Dashboard | Menampilkan kartu Total Modal Aktif (\$ USD), Total Akun MT4, Indikator BEP (Balik Modal), dan grafik pertumbuhan. | [ ] Lolos<br>[ ] Gagal | |
| **UAT-INV-07** | Pendaftaran Akun Copytrade Baru | 1. Buka menu **Portofolio Investasi**.<br>2. Klik tombol **"Tambah Akun Investasi"**.<br>3. Pilih Tier, isi No Akun MT4, Modal, Tanggal, Rekening Bank, dan unggah Surat Perjanjian bermaterai.<br>4. Klik **"Kirim Pendaftaran Akun"**. | - Tier: `Tier 1 ($100 - $499)`<br>- MT4: `88991122`<br>- Modal: `$100.00`<br>- Bank: BCA `1234567890` an Budi Santoso | Pengajuan akun trading tersimpan, muncul notifikasi sukses, dan akun berstatus *Menunggu Verifikasi Admin*. | [ ] Lolos<br>[ ] Gagal | |
| **UAT-INV-08** | Pratinjau Kredensial Akun Aktif | 1. Buka menu **Portofolio Investasi**.<br>2. Klik salah satu kartu akun trading berstatus **Aktif**.<br>3. Periksa informasi broker, nomor akun, dan data rekening penarikan. | Akun MT4 Aktif | Seluruh informasi akun tertampil rapi. Kredensial terlindungi dan Surat Perjanjian dapat dibuka via tombol link privat. | [ ] Lolos<br>[ ] Gagal | |
| **UAT-INV-09** | Input Saldo Rutin Tanggal 1 | 1. Pada kartu akun aktif, klik tombol **"Input Saldo Tanggal 1"**.<br>2. Masukkan saldo baru yang lebih besar dari modal awal.<br>3. Perhatikan pratinjau profit otomatis.<br>4. Klik **"Simpan Saldo"**. | - Modal awal: `$100.00`<br>- Saldo baru: `$110.00` | Pratinjau menghitung profit `+$10.00` (+10%). Data tersimpan dan grafik saldo ter-update seketika. | [ ] Lolos<br>[ ] Gagal | |
| **UAT-INV-10** | Verifikasi Nilai Profit Netral `$0.00` | 1. Buka detail akun aktif.<br>2. Periksa riwayat saldo pada dua bulan berturut-turut yang bernilai identik (misal Jan: \$110, Feb: \$110).<br>3. Cek kolom *Profit* dan *%*. | Bulan 1: `$110.00`<br>Bulan 2: `$110.00` | Kolom Profit menampilkan persis **+$0.00** dengan persentase **0%**, berwarna abu-abu netral dengan ikon *Minus* (tidak terhitung profit ganda). | [ ] Lolos<br>[ ] Gagal | |

---

### 2.3 Fitur Interaktif, Pusat Bantuan & Keamanan Akun

| ID Pengujian | Fitur / Sub-Modul | Langkah-Langkah Pengujian | Data Uji / Nilai Input | Hasil yang Diharapkan (Ekspektasi) | Status Uji | Catatan Penguji |
| :---: | :--- | :--- | :--- | :--- | :---: | :--- |
| **UAT-INV-11** | Kalkulator Proyeksi Finansial | 1. Buka menu **Kalkulator Proyeksi** (`/investor/calculator`).<br>2. Masukkan Modal Awal \$1,000.<br>3. Geser slider estimasi profit ke 5% per bulan.<br>4. Amati tabel proyeksi 1–5 tahun. | - Modal: `$1,000`<br>- Profit: `5% / bulan`<br>- Tenor: `12 - 60 bulan` | Tabel dan grafik akumulasi modal majemuk (*compound*) terhitung dinamis secara *real-time*. | [ ] Lolos<br>[ ] Gagal | |
| **UAT-INV-12** | Pusat Bantuan & Panduan | 1. Buka menu **Pusat Bantuan** (`/investor/education/help`).<br>2. Ketik kata kunci pada kotak pencarian.<br>3. Klik salah satu judul artikel untuk membaca detail. | Kata kunci: `copytrade` | Menampilkan artikel terkait. Tampilan artikel bersih, tanpa badge kategori atau jumlah pembaca yang tidak perlu. | [ ] Lolos<br>[ ] Gagal | |
| **UAT-INV-13** | Tanya Jawab FAQ Interaktif | 1. Buka menu **Tanya Jawab (FAQ)** (`/investor/education/faq`).<br>2. Perhatikan kondisi accordion pertanyaan.<br>3. Klik salah satu pertanyaan untuk membuka jawaban. | Halaman: FAQ | Seluruh item FAQ dalam kondisi tertutup secara default (*collapsed*). Saat diklik, jawaban terbuka dengan animasi mulus. | [ ] Lolos<br>[ ] Gagal | |
| **UAT-INV-14** | Glosarium Istilah Forex | 1. Buka menu **Glosarium** (`/investor/education/glossary`).<br>2. Gunakan filter abjad (misal huruf "D" untuk Drawdown). | Abjad: `D` | Istilah tersaring tepat, memuat definisi istilah trading yang mudah dimengerti investor awam. | [ ] Lolos<br>[ ] Gagal | |
| **UAT-INV-15** | Notifikasi In-App | 1. Klik ikon lonceng (`🔔`) di pojok kanan atas.<br>2. Buka daftar notifikasi masuk.<br>3. Klik salah satu item notifikasi. | Notifikasi sistem | Dropdown menampilkan pesan riil. Mengeklik notifikasi mengarahkan pengguna langsung ke modul yang relevan. | [ ] Lolos<br>[ ] Gagal | |
| **UAT-INV-16** | Penggantian Kata Sandi Mandiri | 1. Klik profil pengguna $\rightarrow$ pilih **Ganti Password**.<br>2. Masukkan password lama dan password baru $\rightarrow$ klik **"Perbarui Kata Sandi"**. | - Lama: `investor`<br>- Baru: `InvestorBaru2026!` | Password berhasil diperbarui, muncul toast konfirmasi hijau, dan login berikutnya menggunakan password baru. | [ ] Lolos<br>[ ] Gagal | |

---

## Bab 3: Lembar Pengujian Portal Mitra Affiliator (`UAT-AFF`)

### 3.1 Hak Akses Khusus & Tautan Referral Otomatis

| ID Pengujian | Fitur / Sub-Modul | Langkah-Langkah Pengujian | Data Uji / Nilai Input | Hasil yang Diharapkan (Ekspektasi) | Status Uji | Catatan Penguji |
| :---: | :--- | :--- | :--- | :--- | :---: | :--- |
| **UAT-AFF-01** | Visibilitas Menu Program Afiliasi | 1. Login sebagai akun Affiliator (`affiliator` / `affiliator`).<br>2. Periksa menu bilah samping (*sidebar*). | User: `affiliator` | Muncul kelompok menu **"Program Afiliasi"** berisi sub-menu **Jaringan Kemitraan** dan **Klaim Komisi**. | [ ] Lolos<br>[ ] Gagal | |
| **UAT-AFF-02** | Penyalinan Tautan Referral | 1. Buka menu **Jaringan Kemitraan** (`/investor/affiliates`).<br>2. Klik tombol **"Salin Tautan"** pada panel referral.<br>3. Tempel (*paste*) URL di tab browser baru / incognito. | Panel Tautan Referral | Tautan tersalin ke clipboard dengan feedback toast sukses. Saat dibuka di incognito, URL memuat kode referral affiliator. | [ ] Lolos<br>[ ] Gagal | |

---

### 3.2 Pemantauan Jaringan Downline & Pengajuan Klaim Susulan

| ID Pengujian | Fitur / Sub-Modul | Langkah-Langkah Pengujian | Data Uji / Nilai Input | Hasil yang Diharapkan (Ekspektasi) | Status Uji | Catatan Penguji |
| :---: | :--- | :--- | :--- | :--- | :---: | :--- |
| **UAT-AFF-03** | Tabel Daftar Downline | 1. Buka tab **Daftar Downline** di menu Jaringan Kemitraan.<br>2. Periksa data investor binaan, status copytrade, dan modal. | Tab: Downlines | Menampilkan daftar lengkap: Nama, Nomor HP/WhatsApp, Tanggal Bergabung, Akun MT4 Aktif, dan Akumulasi Modal binaan. | [ ] Lolos<br>[ ] Gagal | |
| **UAT-AFF-04** | Pengajuan Klaim Referral Susulan | 1. Klik tombol **"Ajukan Klaim Susulan"** di kanan atas.<br>2. Masukkan username investor yang belum ber-upline $\rightarrow$ klik **"Cek Database"**.<br>3. Unggah bukti chat (screenshot WA).<br>4. Isi kronologi alasan $\rightarrow$ klik **"Kirim Permohonan Klaim"**. | - Downline: `investor`<br>- Bukti: File JPG/PNG chat<br>- Alasan: *"Investor mendaftar arahan saya via WhatsApp"* | Validasi database menemukan akun investor, bukti terunggah aman, klaim masuk ke antrean Admin dengan status *Pending*. | [ ] Lolos<br>[ ] Gagal | |
| **UAT-AFF-05** | Validasi Anti-Klaim Diri Sendiri & Ganda | 1. Buka modal klaim susulan.<br>2. Coba masukkan username akun sendiri (`affiliator`) atau investor yang sudah memiliki upline permanen. | Username: `affiliator` | Sistem memvalidasi dan menolak pencarian dengan pesan peringatan *"Anda tidak dapat mengklaim akun Anda sendiri / sudah memiliki upline"*. | [ ] Lolos<br>[ ] Gagal | |
| **UAT-AFF-06** | Riwayat Klaim & Catatan Penolakan | 1. Buka tab **Riwayat Klaim Susulan**.<br>2. Periksa item klaim yang berstatus *Ditolak*.<br>3. Klik tombol **"Baca Selengkapnya"** pada kolom catatan. | Baris status: `rejected` | Modal membaca detail alasan penolakan dari Administrator terbuka utuh tanpa merusak tata letak tabel (*anti-hover dependency*). | [ ] Lolos<br>[ ] Gagal | |

---

### 3.3 Skema Komisi 4-Tier & Pengajuan Pencairan Bank

| ID Pengujian | Fitur / Sub-Modul | Langkah-Langkah Pengujian | Data Uji / Nilai Input | Hasil yang Diharapkan (Ekspektasi) | Status Uji | Catatan Penguji |
| :---: | :--- | :--- | :--- | :--- | :---: | :--- |
| **UAT-AFF-07** | Kartu Ringkasan Metrik Komisi | 1. Buka menu **Klaim Komisi** (`/investor/commissions`).<br>2. Amati kartu saldo: Komisi Siap Dicairkan, Total Komisi Dicairkan, Akun Binaan Layak Komisi. | Halaman: Klaim Komisi | Data numerik terformat rapi dalam mata uang Rupiah (IDR) berdasarkan kurs resmi \$1 = Rp 15.000,-. | [ ] Lolos<br>[ ] Gagal | |
| **UAT-AFF-08** | Pengajuan Pencairan Komisi Bank | 1. Klik tombol **"Ajukan Klaim Komisi"**.<br>2. Centang akun-akun downline aktif yang ingin dicairkan.<br>3. Periksa kalkulasi otomatis Total Rupiah.<br>4. Konfirmasi rincian nomor rekening bank tujuan $\rightarrow$ klik **"Kirim Pengajuan Pencairan"**. | - Pilihan: 1 Akun Aktif Tier 1 ($100)<br>- Rekening: BCA `987654321` an Mitra Affiliator | Pengajuan komisi tersimpan, saldo siap cair berkurang sesuai klaim, dan muncul di tabel riwayat penarikan status *Pending*. | [ ] Lolos<br>[ ] Gagal | |
| **UAT-AFF-09** | Pembukaan Bukti Transfer Bank | 1. Pada tabel riwayat pencairan, cari transaksi berstatus **"Berhasil Ditransfer"**.<br>2. Klik tombol **"Lihat Bukti Transfer"**. | Transaksi status: `transferred` | Berkas bukti transfer resmi yang diunggah Admin dapat dibuka/diunduh secara aman via tab baru browser. | [ ] Lolos<br>[ ] Gagal | |
| **UAT-AFF-10** | Aturan Komisi 1x Seumur Hidup | 1. Buka kembali modal ajukan pencairan komisi.<br>2. Periksa apakah akun trading downline yang sudah pernah dicairkan masih muncul dalam pilihan. | Akun yang sudah `transferred` | Akun tersebut otomatis terkunci/tidak lagi muncul di daftar pilihan klaim (*one-time commission policy per unique account*). | [ ] Lolos<br>[ ] Gagal | |

---

## Bab 4: Lembar Pengujian Portal Administrator (`UAT-ADM`)

### 4.1 Dashboard Analitik & Manajemen Tautan Undangan

| ID Pengujian | Fitur / Sub-Modul | Langkah-Langkah Pengujian | Data Uji / Nilai Input | Hasil yang Diharapkan (Ekspektasi) | Status Uji | Catatan Penguji |
| :---: | :--- | :--- | :--- | :--- | :---: | :--- |
| **UAT-ADM-01** | Dashboard Administrator | 1. Login sebagai `admin` / `admin`.<br>2. Akses halaman `/admin/dashboard`.<br>3. Periksa kartu metrik dan antrean *Action Needed*. | User: `admin` | Menampilkan Total Modal Ekosistem (\$ USD), Total Investor, Total Affiliator, dan badge antrean tugas verifikasi pending. | [ ] Lolos<br>[ ] Gagal | |
| **UAT-ADM-02** | Pembuatan Tautan Undangan Baru | 1. Buka menu **Tautan Undangan** (`/admin/invitations`).<br>2. Klik **"Buat Undangan Baru"**.<br>3. Isi Label, pilih Role, isi Kuota, dan tentukan Masa Berlaku $\rightarrow$ klik **"Simpan & Buat Tautan"**. | - Label: `UAT Demo Batch 1`<br>- Role: `investor`<br>- Kuota: `5`<br>- Masa: `24 Jam` | Modal terbuka mulus, tautan terbit dengan token unik, muncul di tabel utama berstatus aktif (*Active badge* hijau). | [ ] Lolos<br>[ ] Gagal | |
| **UAT-ADM-03** | Penghapusan Massal (*Batch Delete*) | 1. Pada tabel undangan, centang 2 atau lebih baris token yang tidak digunakan.<br>2. Klik tombol merah **"Hapus Terpilih (N)"** pada bilah aksi massal.<br>3. Konfirmasi dialog modal. | Checklist 2 baris token | Seluruh token terpilih terhapus permanen dari sistem, dan seleksi otomatis ter-reset bersih. | [ ] Lolos<br>[ ] Gagal | |

---

### 4.2 Verifikasi Registrasi KTP & Akun Copytrade (MT4/VPS)

| ID Pengujian | Fitur / Sub-Modul | Langkah-Langkah Pengujian | Data Uji / Nilai Input | Hasil yang Diharapkan (Ekspektasi) | Status Uji | Catatan Penguji |
| :---: | :--- | :--- | :--- | :--- | :---: | :--- |
| **UAT-ADM-04** | Persetujuan Verifikasi KYC KTP | 1. Buka menu **Verifikasi Pendaftaran** (`/admin/registrations`).<br>2. Klik tombol **"Tinjau Pendaftaran"** pada baris pendaftar baru.<br>3. Periksa kesesuaian Nama, NIK, dan pratinjau foto KTP.<br>4. Pilih opsi **"Setujui Pendaftaran"** $\rightarrow$ klik **"Simpan Keputusan"**. | Pendaftar baru status: `pending` | Status pendaftar seketika berubah menjadi *Aktif*, akun pengguna dapat langsung login dan menggunakan modul investor penuh. | [ ] Lolos<br>[ ] Gagal | |
| **UAT-ADM-05** | Penolakan Verifikasi KYC KTP | 1. Buka modal tinjau pendaftaran pada akun dengan data tidak sesuai.<br>2. Pilih opsi **"Tolak Pendaftaran"**.<br>3. Ketikkan alasan pada kolom catatan $\rightarrow$ klik **"Simpan Keputusan"**. | Alasan: *"Foto KTP buram pada angka NIK"* | Status pendaftar berubah menjadi *Perlu Revisi*, catatan penolakan terkirim ke notifikasi akun calon investor. | [ ] Lolos<br>[ ] Gagal | |
| **UAT-ADM-06** | Verifikasi Copytrade & Surat Perjanjian | 1. Buka menu **Kelola Copytrade** (`/admin/copytrade`) $\rightarrow$ tab **Menunggu Verifikasi**.<br>2. Klik tombol **"Tinjau Pengajuan"**.<br>3. Klik tombol **"Buka Berkas"** Surat Perjanjian Kemitraan. | Pengajuan MT4 pending | Berkas PDF Surat Perjanjian bermaterai terbuka pada tab baru via rute streaming aman `/documents/copytrade/{id}`. | [ ] Lolos<br>[ ] Gagal | |
| **UAT-ADM-07** | Setup Kredensial MT4/VPS Khusus Tier 3 | 1. Buka modal persetujuan pengajuan akun **Tier 3** (modal $\ge$ \$1.000).<br>2. Lengkapi form kredensial: No MT4, Server, Master Password, Investor Password, IP VPS, Port, User & Pass VPS.<br>3. Klik **"Simpan & Aktifkan Copytrade"**. | - MT4: `1002003`<br>- Server: `HFMarkets-Live`<br>- IP VPS: `103.150.10.25`<br>- Pass VPS: `VpsSecret2026!` | Akun berpindah ke tab **Akun Aktif**. Seluruh password MT4 & VPS tersimpan terenkripsi (*AES-256 encrypted cast*) di database. | [ ] Lolos<br>[ ] Gagal | |
| **UAT-ADM-08** | Input Saldo & Rumus Profit Bulanan | 1. Buka tab **Akun Aktif** $\rightarrow$ klik **"Tinjau / Detail Akun"**.<br>2. Buka tabel Riwayat Saldo.<br>3. Catat saldo baru per tanggal 1 bulan berikutnya. | - Modal awal: `$100.00`<br>- Saldo baru: `$120.00` | Riwayat mencatat saldo secara presisi. Kolom Profit menampilkan `+$20.00` dan persentase terhitung otomatis. | [ ] Lolos<br>[ ] Gagal | |

---

### 4.3 Audit Jaringan Kemitraan & Putusan Klaim Susulan

| ID Pengujian | Fitur / Sub-Modul | Langkah-Langkah Pengujian | Data Uji / Nilai Input | Hasil yang Diharapkan (Ekspektasi) | Status Uji | Catatan Penguji |
| :---: | :--- | :--- | :--- | :--- | :---: | :--- |
| **UAT-ADM-09** | Pemantauan Jaringan Downline | 1. Buka menu **Jaringan Kemitraan** (`/admin/affiliates`) $\rightarrow$ tab **Daftar Mitra**.<br>2. Klik tombol mata (**"Lihat Downline"**) pada salah satu baris affiliator. | Affiliator terpilih | Modal terbuka menampilkan daftar downline lengkap: nama, username, sumber bergabung (link/klaim), dan total modal investasi. | [ ] Lolos<br>[ ] Gagal | |
| **UAT-ADM-10** | Form Dinamis Putusan Klaim Susulan | 1. Buka tab **Verifikasi Klaim Susulan Referral** (`/admin/affiliates?tab=claims`).<br>2. Klik **"Tinjau & Putuskan"**.<br>3. Perhatikan perilaku field *"Catatan Instruksi / Alasan"*: Pilih **Setujui**, lalu ubah ke **Tolak**. | - Opsi 1: `Setujui`<br>- Opsi 2: `Tolak` | - Saat pilih Setujui: Field catatan **otomatis sembunyi**.<br>- Saat pilih Tolak: Field catatan **otomatis muncul** & wajib diisi. | [ ] Lolos<br>[ ] Gagal | |
| **UAT-ADM-11** | Eksekusi Persetujuan Klaim Susulan | 1. Pada modal tinjau klaim, evaluasi bukti chat WhatsApp pendukung.<br>2. Pilih **"Setujui Permohonan"** $\rightarrow$ klik **"Simpan Keputusan"**. | Bukti chat valid | Klaim berstatus *Approved*. Relasi referral terbuat seketika: investor menjadi downline affiliator dan akunnya berstatus eligible komisi. | [ ] Lolos<br>[ ] Gagal | |
| **UAT-ADM-12** | Penolakan Klaim & Truncate Notifikasi | 1. Buka modal klaim yang bukti chat-nya tidak valid.<br>2. Pilih **"Tolak Permohonan"**.<br>3. Tulis catatan panjang (> 100 karakter) $\rightarrow$ klik **"Simpan Keputusan"**. | Catatan: Alasan penolakan detail | Klaim berstatus *Rejected*. Notifikasi in-app affiliator menampilkan teks catatan yang diringkas rapi (*truncated*) dengan tombol detail. | [ ] Lolos<br>[ ] Gagal | |

---

### 4.4 Pemrosesan Transfer Pencairan Komisi Afiliasi

| ID Pengujian | Fitur / Sub-Modul | Langkah-Langkah Pengujian | Data Uji / Nilai Input | Hasil yang Diharapkan (Ekspektasi) | Status Uji | Catatan Penguji |
| :---: | :--- | :--- | :--- | :--- | :---: | :--- |
| **UAT-ADM-13** | Antrean Pencairan Komisi Afiliasi | 1. Buka menu **Pencairan Komisi** (`/admin/commissions`).<br>2. Periksa baris pengajuan berstatus *Pending*.<br>3. Klik tombol **"Proses Pencairan"**. | Pengajuan komisi pending | Modal proses pencairan terbuka, menyajikan rincian Akun Downline, Nominal IDR, dan Rekening Bank Tujuan lengkap. | [ ] Lolos<br>[ ] Gagal | |
| **UAT-ADM-14** | Unggah Bukti Transfer & Konfirmasi Payout | 1. Lakukan transfer bank simulasi/riil sesuai nominal IDR.<br>2. Unggah file struk/tangkapan layar bukti transfer (JPG/PNG/PDF).<br>3. Masukkan catatan nomor referensi bank.<br>4. Klik tombol **"Konfirmasi Pembayaran Selesai"**. | - File: Struk transfer bank<br>- Catatan: `Ref BCA-2026101801` | Bukti transfer terunggah aman, status transaksi berubah menjadi **"Berhasil Ditransfer"** (*transferred*), dan Affiliator menerima notifikasi. | [ ] Lolos<br>[ ] Gagal | |

---

### 4.5 Manajemen Pengguna, Materi Edukasi & Pengaturan Legalitas

| ID Pengujian | Fitur / Sub-Modul | Langkah-Langkah Pengujian | Data Uji / Nilai Input | Hasil yang Diharapkan (Ekspektasi) | Status Uji | Catatan Penguji |
| :---: | :--- | :--- | :--- | :--- | :---: | :--- |
| **UAT-ADM-15** | Pencarian & Filter Peran Pengguna | 1. Buka menu **Manajemen Pengguna** (`/admin/users`).<br>2. Filter dropdown peran: *Investor*, *Affiliator*, *Administrator*.<br>3. Gunakan kolom pencarian berdasarkan nama/username. | Filter: `Affiliator`<br>Keyword: `affiliator` | Tabel data menyaring pengguna secara instan sesuai kriteria pencarian dan filter peran yang dipilih. | [ ] Lolos<br>[ ] Gagal | |
| **UAT-ADM-16** | Promosi / Demosi Peran (*Role Switching*) | 1. Buka detail salah satu akun investor.<br>2. Ubah peran pengguna dari *Investor* menjadi *Affiliator* $\rightarrow$ Simpan. | Perubahan peran: `investor` $\rightarrow$ `affiliator` | Hak akses pengguna berubah seketika; saat pengguna login kembali, menu Program Afiliasi otomatis aktif. | [ ] Lolos<br>[ ] Gagal | |
| **UAT-ADM-17** | Penonaktifan (*Suspend*) Akun | 1. Pada baris pengguna, klik toggle/tombol ubah status akun menjadi **Nonaktif (Suspended)**.<br>2. Coba login menggunakan akun tersebut di tab browser lain. | Akun: `status = suspended` | Login ditolak dengan pesan peringatan *"Akun Anda telah dinonaktifkan oleh Administrator. Silakan hubungi pusat bantuan"*. | [ ] Lolos<br>[ ] Gagal | |
| **UAT-ADM-18** | Manajemen Konten Edukasi (Help & FAQ) | 1. Buka `/admin/education/help` $\rightarrow$ tambah/edit artikel panduan.<br>2. Buka `/admin/education/faq` $\rightarrow$ tambah topik & pertanyaan FAQ. | Konten bantuan baru | Konten tersimpan di database dan langsung terbit di portal investor secara *real-time*. | [ ] Lolos<br>[ ] Gagal | |
| **UAT-ADM-19** | Sunting Dokumen Legalitas Platform | 1. Buka `/admin/settings/legal`.<br>2. Sunting teks pada tab *Syarat & Ketentuan* atau *Kebijakan Privasi* $\rightarrow$ klik **"Simpan Perubahan"**. | Sunting klausul legal | Teks dokumen legalitas langsung terbarukan pada halaman pendaftaran publik dan tautan footer platform. | [ ] Lolos<br>[ ] Gagal | |

---

## Bab 5: Skenario Pengujian Terintegrasi Lintas-Peran (`E2E`)

Skenario terintegrasi ini menguji kesinambungan alur kerja platform (*end-to-end*) yang melibatkan kolaborasi lintas-aktor (Admin $\leftrightarrow$ Investor $\leftrightarrow$ Affiliator) dari hulu ke hilir.

---

### 5.1 E2E-01: Siklus Registrasi Undangan, Audit KYC & Aktivasi

```mermaid
sequenceDiagram
    autonumber
    actor Admin
    actor Calon as Calon Investor
    Admin->>Admin: Terbitkan Tautan Undangan Tertutup (/admin/invitations)
    Admin-->>Calon: Bagikan Link (/register?invitation=TOKEN)
    Calon->>Calon: Isi Form 3-Step & Unggah Foto KTP
    Calon->>Admin: Submit Pendaftaran (Status Pending)
    Admin->>Admin: Tinjau NIK & Foto KTP (/admin/registrations)
    Admin->>Admin: Setujui Pendaftaran
    Admin-->>Calon: Notifikasi & Akun Investor Aktif Penuh
```

| Kode Uji | Aktor Terlibat | Langkah Pengujian Integrasi | Hasil yang Diharapkan | Status Uji | Catatan |
| :---: | :--- | :--- | :--- | :---: | :--- |
| **E2E-01** | Administrator $\rightarrow$ Calon Investor $\rightarrow$ Administrator | 1. Admin membuat token undangan baru di `/admin/invitations`.<br>2. Calon investor membuka link registrasi & melengkapi form registrasi KYC.<br>3. Admin membuka `/admin/registrations` dan mengecek kesesuaian KTP.<br>4. Admin menyetujui pendaftaran.<br>5. Calon investor login kembali ke portal. | Alur pendaftaran closed-loop berhasil 100%. Akun investor aktif penuh tanpa ada hambatan (*seamless transition*). | [ ] Lolos<br>[ ] Gagal | |

---

### 5.2 E2E-02: Siklus Pendaftaran Copytrade Tier 3 & Setup Kredensial VPS

```mermaid
sequenceDiagram
    autonumber
    actor Investor
    actor Admin
    Investor->>Investor: Tambah Akun MT4 Tier 3 (Modal >= $1,000)
    Investor->>Admin: Unggah Dokumen Perjanjian Bermaterai
    Admin->>Admin: Buka Berkas Perjanjian via Link Privat
    Admin->>Admin: Konfirmasi Deposit Broker & Input IP/Pass VPS
    Admin->>Admin: Simpan (Password MT4/VPS Terenkripsi)
    Admin-->>Investor: Akun Berstatus Aktif di Portofolio
```

| Kode Uji | Aktor Terlibat | Langkah Pengujian Integrasi | Hasil yang Diharapkan | Status Uji | Catatan |
| :---: | :--- | :--- | :--- | :---: | :--- |
| **E2E-02** | Investor $\rightarrow$ Administrator | 1. Investor menambahkan akun MT4 Tier 3 modal \$1.000 dan mengunggah PDF perjanjian.<br>2. Admin membuka tab *Menunggu Verifikasi* di `/admin/copytrade`.<br>3. Admin meninjau dokumen legalitas via tombol *Buka Berkas*.<br>4. Admin menyetujui sambil mengisi kredensial MT4 dan IP/Port/Password VPS.<br>5. Investor memantau status di menu Portofolio Investasi. | Akun aktif di portofolio investor. Kredensial VPS terenkripsi di database dan hanya dapat dikelola oleh pemilik hak otorisasi. | [ ] Lolos<br>[ ] Gagal | |

---

### 5.3 E2E-03: Siklus Klaim Susulan & Pembentukan Relasi Downline

```mermaid
sequenceDiagram
    autonumber
    actor Affiliator
    actor Admin
    actor Downline as Investor Downline
    Downline->>Downline: Terdaftar tanpa Tautan Referral
    Affiliator->>Affiliator: Ajukan Klaim Susulan + Bukti Chat WA
    Affiliator->>Admin: Kirim Klaim ke Antrean Admin
    Admin->>Admin: Verifikasi Bukti Chat (/admin/affiliates?tab=claims)
    Admin->>Admin: Setujui Klaim Susulan
    Admin-->>Affiliator: Relasi Terbentuk & Downline Muncul di Jaringan
```

| Kode Uji | Aktor Terlibat | Langkah Pengujian Integrasi | Hasil yang Diharapkan | Status Uji | Catatan |
| :---: | :--- | :--- | :--- | :---: | :--- |
| **E2E-03** | Mitra Affiliator $\rightarrow$ Administrator $\rightarrow$ Investor | 1. Investor mendaftar mandiri tanpa link referral.<br>2. Affiliator mengajukan klaim susulan di `/investor/affiliates` dengan melampirkan screenshot chat WhatsApp.<br>3. Admin memeriksa bukti di `/admin/affiliates?tab=claims`.<br>4. Admin memilih *Setujui Permohonan* (field catatan hilang) & simpan.<br>5. Affiliator memeriksa tab *Daftar Downline*. | Investor berhasil terhubung menjadi downline resmi affiliator. Seluruh akun copytrade aktif miliknya tercatat layak komisi. | [ ] Lolos<br>[ ] Gagal | |

---

### 5.4 E2E-04: Siklus Pencairan Komisi & Pengunggahan Bukti Transfer

```mermaid
sequenceDiagram
    autonumber
    actor Affiliator
    actor Admin
    actor Bank as Sistem Perbankan
    Affiliator->>Affiliator: Pilih Akun Downline & Ajukan Pencairan
    Affiliator->>Admin: Submit Klaim Payout (Status Pending)
    Admin->>Bank: Transfer Dana Rupiah via Rekening Resmi
    Admin->>Admin: Unggah Bukti Struk Transfer (/admin/commissions)
    Admin->>Affiliator: Status Payout Menjadi "Dicairkan"
    Affiliator->>Affiliator: Periksa Notifikasi & Unduh Bukti Transfer
```

| Kode Uji | Aktor Terlibat | Langkah Pengujian Integrasi | Hasil yang Diharapkan | Status Uji | Catatan |
| :---: | :--- | :--- | :--- | :---: | :--- |
| **E2E-04** | Mitra Affiliator $\rightarrow$ Administrator | 1. Affiliator memilih akun downline aktif dan mengajukan penarikan komisi ke rekening bank.<br>2. Admin membuka `/admin/commissions` dan mengeklik *Proses Pencairan*.<br>3. Admin melakukan transfer dan mengunggah berkas bukti transfer resmi.<br>4. Admin konfirmasi pembayaran selesai.<br>5. Affiliator memantau status transaksi dan membuka bukti transfer. | Payout komisi sukses dari hulu ke hilir. Bukti transfer bank dapat diakses dan akun MT4 terkait terkunci dari klaim ganda seumur hidup. | [ ] Lolos<br>[ ] Gagal | |

---

## Bab 6: Rekapitulasi Hasil Pengujian & Lembar Catatan Temuan Klien

### 6.1 Tabel Ringkasan Metrik Kelulusan UAT

| Kategori Pengujian | Kode Uji | Total Kasus Uji | Jumlah Lolos | Jumlah Gagal | Persentase Kelulusan (%) |
| :--- | :---: | :---: | :---: | :---: | :---: |
| **Portal Investor** | `UAT-INV-01 s/d 16` | 16 | _______ | _______ | _______ % |
| **Portal Mitra Affiliator** | `UAT-AFF-01 s/d 10` | 10 | _______ | _______ | _______ % |
| **Portal Administrator** | `UAT-ADM-01 s/d 19` | 19 | _______ | _______ | _______ % |
| **Skenario Terintegrasi (E2E)** | `E2E-01 s/d 04` | 4 | _______ | _______ | _______ % |
| **TOTAL KESELURUHAN** | — | **49** | _______ | _______ | _______ % |

---

### 6.2 Lembar Catatan Temuan / Umpan Balik Klien (*Defect & Feedback Log*)

Gunakan tabel di bawah ini untuk mencatat setiap temuan, ketidaksesuaian, atau catatan khusus selama sesi pengujian bersama klien:

| No | Kode Kasus Uji | Deskripsi Temuan / Kendala yang Terjadi | Tingkat Urgensi (*Critical / Major / Minor*) | Rekomendasi / Tindak Lanjut Tim Teknis | Status Solusi (*Open / Resolved*) |
| :---: | :---: | :--- | :---: | :--- | :---: |
| 1 | | | | | |
| 2 | | | | | |
| 3 | | | | | |
| 4 | | | | | |
| 5 | | | | | |
| 6 | | | | | |
| 7 | | | | | |
| 8 | | | | | |

---

### 6.3 Pengesahan Hasil Pengujian Bersama (*Test Acknowledgment*)

Lembar catatan ini menandai bahwa rangkaian pengujian penerimaan pengguna (*User Acceptance Testing*) telah dilaksanakan bersama pada lingkungan yang disepakati:

- **Tanggal Pelaksanaan UAT:** __________________________________
- **Lokasi / Media Pengujian:** __________________________________

**Perwakilan Tim Penguji Klien:**
- Nama Penguji: _____________________________________________
- Jabatan: __________________________________________________
- Tanda Tangan: _____________________________________________

**Perwakilan Tim Teknis / Pengembang:**
- Nama Pengembang: __________________________________________
- Jabatan: __________________________________________________
- Tanda Tangan: _____________________________________________

---
*Lembar Pengujian UAT Resmi — Sistem Manajemen Kemitraan & Perdagangan Otomatis YZD Center.*
