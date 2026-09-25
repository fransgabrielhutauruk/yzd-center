# Panduan Verifikasi Fitur — Modul 06: Edukasi, Glosarium & Kalkulator Proyeksi Finansial

Dokumen ini memuat panduan verifikasi fungsional, daftar rute, dan skenario pengujian untuk **Modul 06: Edukasi, Glosarium Forex, Pusat Bantuan Bertopik & Kalkulator Proyeksi Finansial (CRM Investor)**.

---

## 1. Daftar Rute (Endpoints)

### A. Portal Investor & Afiliator (Bebas Akses / `requiresActive: false`)
| Metode | URI | Nama Rute | Fungsi |
| :--- | :--- | :--- | :--- |
| `GET` ok | `/investor/education/calculator` | `investor.education.calculator` | Kalkulator Proyeksi Finansial 5 Tahun (Formula CSV Tab 5% Conservativ) |
| `GET` ok | `/investor/education/help` | `investor.education.help` | Pusat Bantuan dengan Tab Pemisahan Topik & Video Tutorial |
| `GET` ok | `/investor/education/help/{slug}` | `investor.education.article` | Pembaca Panduan Langkah Lengkap & Video YouTube 16:9 |
| `GET` ok | `/investor/education/faq` | `investor.education.faq` | Tanya Jawab (FAQ) Accordion Tanpa Slug per Topik |
| `GET` ok | `/investor/education/glossary` | `investor.education.glossary` | Kamus Glosarium Forex Interaktif (Filter Kategori & Indeks A-Z) |

### B. Portal Administrator (`role:administrator`)
| Metode | URI | Nama Rute | Fungsi |
| :--- | :--- | :--- | :--- |
| `GET` ok | `/admin/education/glossary` | `admin.education.glossary.index` | Daftar & Manajemen Glosarium Forex (`AppDataTable`) |
| `POST` ok | `/admin/education/glossary` | `admin.education.glossary.store` | Tambah Istilah Glosarium Baru |
| `PUT` ok | `/admin/education/glossary/{id}` | `admin.education.glossary.update` | Perbarui Istilah Glosarium |
| `DELETE` ok| `/admin/education/glossary/{id}` | `admin.education.glossary.destroy` | Hapus Istilah Glosarium |
| `GET` ok | `/admin/education/help` | `admin.education.help.index` | Manajemen Pusat Bantuan (Tab Kategori & Tab Artikel) |
| `POST` ok| `/admin/education/help/categories` | `admin.education.help.categories.store` | Tambah Kategori Topik Bantuan Baru |
| `PUT` ok| `/admin/education/help/categories/{id}` | `admin.education.help.categories.update`| Perbarui Kategori Topik Bantuan |
| `DELETE`ok| `/admin/education/help/categories/{id}` | `admin.education.help.categories.destroy`| Hapus Kategori Topik (Hanya jika tanpa artikel) |
| `POST` ok| `/admin/education/help/articles` | `admin.education.help.articles.store` | Tambah Artikel Panduan & Video YouTube |
| `PUT` ok| `/admin/education/help/articles/{id}` | `admin.education.help.articles.update` | Perbarui Artikel Panduan |
| `DELETE`ok| `/admin/education/help/articles/{id}` | `admin.education.help.articles.destroy` | Hapus Artikel Panduan |
| `GET` ok | `/admin/education/faq` | `admin.education.faq.index` | Manajemen FAQ & Topik (Tab FAQ & Tab Topik) |
| `POST` ok| `/admin/education/faq` | `admin.education.faq.store` | Tambah Pertanyaan FAQ Baru |
| `PUT` ok| `/admin/education/faq/{id}` | `admin.education.faq.update` | Perbarui Pertanyaan FAQ |
| `DELETE`ok| `/admin/education/faq/{id}` | `admin.education.faq.destroy` | Hapus Pertanyaan FAQ |
| `POST` ok| `/admin/education/faq/topics` | `admin.education.faq.topics.store` | Tambah Topik FAQ Baru |
| `PUT` ok| `/admin/education/faq/topics/{id}` | `admin.education.faq.topics.update` | Perbarui Topik FAQ |
| `DELETE`ok| `/admin/education/faq/topics/{id}` | `admin.education.faq.topics.destroy` | Hapus Topik FAQ (Hanya jika tanpa pertanyaan) |

---

## 2. Skenario Verifikasi Manual

### Skenario 1: Kalkulator Proyeksi Finansial (Formula Tab "5% Conservativ" 30/30/40 Split)
1. Akses langsung `http://localhost:8000/investor/education/calculator`.
2. **Uji Nilai Bawaan (*Default Values* & Live Kurs API):**
   - Modal Awal: `$100.00`
   - Rata-rata Profit Bulanan: `5.0%`
   - Durasi: `5 Tahun` (60 Bulan, kustomisasi hingga 10 tahun / 120 bulan)
   - Kurs Acuan: Otomatis terisi nilai kurs pasar tengah terkini dari Frankfurter API (misal: `Rp 17.933`).
   - Indikator Status: Terdapat badge hijau `Live Pasar Tengah (Frankfurter API) • Pembaruan: [Waktu WIB]`.
3. **Uji Fitur "Pakai Kurs Hari Ini":**
   - Ubah angka kurs di form menjadi `16000`.
   - Muncul tautan *"Pakai Kurs Hari Ini"* di atas input.
   - Klik tautan tersebut $\rightarrow$ Nilai kurs kembali sinkron dengan nilai pasar tengah hari ini.
4. **Uji Perhitungan Akumulasi 5 Tahun (Rasio 30% Investor / 30% Master Fee / 40% Compounding):**
   - **Estimasi Modal Akhir:** `$328.10` (bertumbuh +228.1% dari akumulasi 40% compounding).
   - **Hak Investor (30% WD):** `$171.08`.
   - **Hak Master Trader (30% Fee):** `$171.08`.
   - **Total Compounding Modal (40%):** `$228.10`.
   - **Total Gross Profit Dihasilkan:** `$570.26`.
5. **Uji Rincian Bulanan (Persis CSV):**
   - Beralih ke tab *"Rincian Per Bulan"*:
     - Bulan 1: Modal `$100.00`, Profit 5% `$5.00`, Investor WD `$1.50`, Master Fee `$1.50`, Compounding `$2.00`, Modal Akhir `$102.00`.
     - Bulan 12: Modal `$124.34`, Profit 5% `$6.22`, Investor WD `$1.87`, Master Fee `$1.87`, Compounding `$2.49`, Modal Akhir `$126.82`.
     - Bulan 24: Modal `$157.69`, Profit 5% `$7.88`, Investor WD `$2.37`, Master Fee `$2.37`, Compounding `$3.15`, Modal Akhir `$160.84`.
6. **Uji Kustomisasi Variabel & Fitur Simpan:**
   - Ubah Modal Awal menjadi `$1,000` dan Rata-rata Profit menjadi `8%`.
   - Klik tombol **"Simpan Variabel"** $\rightarrow$ Muncul notifikasi toast bahwa preferensi tersimpan di peramban.
   - Muat ulang (*refresh*) halaman $\rightarrow$ Nilai `$1,000` dan `8%` tetap bertahan.
   - Klik tombol **"Reset"** $\rightarrow$ Kembali ke nilai standar `$100.00` dan `5.0%`.
7. **Uji Perintah Sinkronisasi Harian (CLI):**
   - Jalankan perintah: `php artisan app:update-usd-exchange-rate`
   - Pastikan output menampilkan kurs berhasil diperbarui dari Frankfurter API.
   - Periksa jadwal otomatis: `php artisan schedule:list` $\rightarrow$ Terdaftar berjalan setiap hari pukul 06:00 WIB (`0 6 * * *`).
8. **Uji Tombol Konsultasi WhatsApp:**
   - Klik tombol WhatsApp $\rightarrow$ mengarahkan ke link kontak resmi Pak Yazid (`082388986189`) dengan pesan prefilled membawa ringkasan simulasi.

---

### Skenario 2: Pusat Bantuan Bertopik & Tutorial Video
1. Akses `http://localhost:8000/investor/education/help`.
2. Klik tombol kategori: *Panduan Broker & Deposit*, *Pendaftaran & KYC*, *Copytrade & MT4*, dll.
   - Pastikan artikel yang tampil berganti secara instan sesuai topik.
3. Klik salah satu artikel dengan badge *Video Tutorial*.
   - Halaman pembaca memuat pemutar video YouTube 16:9 responsif.
   - Angka `views_count` bertambah setelah halaman dibuka.
   - Daftar artikel terkait di kategori yang sama muncul di bagian bawah.

---

### Skenario 3: Glosarium Forex & Indeks A-Z
1. Akses `http://localhost:8000/investor/education/glossary`.
2. Coba fitur *live search* untuk kata kunci istilah atau singkatan (misal: `"DD"` atau `"Margin"`).
3. Klik huruf indeks A-Z (misal huruf `"L"` untuk melihat istilah berawalan L seperti *Lot* dan *Leverage*).
4. Klik tombol *"Reset Filter"* untuk kembali menampilkan seluruh istilah.

---

### Skenario 4: Panel Administrator
1. Login sebagai Admin (`admin` / `admin`) dan akses `http://localhost:8000/admin/education/glossary`.
   - Tambah istilah baru (tanpa input kategori), edit istilah yang ada, dan uji hapus via `AppConfirmDialog`.
2. Akses `http://localhost:8000/admin/education/help`.
   - Beralih antara tab *Artikel & Video Panduan* dan tab *Kategori Topik*.
   - Tambah kategori baru dan artikel baru dengan URL video YouTube.
3. Akses `http://localhost:8000/admin/education/faq`.
   - Beralih antara tab *Daftar Pertanyaan FAQ* dan tab *Topik FAQ*.
   - Tambah Topik FAQ baru (tanpa slug) dan buat pertanyaan FAQ di bawah topik tersebut.
   - Edit urutan dan status aktif/nonaktif.
   - Uji hapus topik yang masih memiliki FAQ (harus gagal dengan pesan validasi sistem).
   - Hapus pertanyaan FAQ lalu hapus topik (berhasil).

---

### Skenario 5: Tanya Jawab (FAQ) Portal Investor
1. Akses `http://localhost:8000/investor/education/faq`.
2. **Uji Accordion Buka-Tutup:**
   - Klik salah satu pertanyaan $\rightarrow$ Jawaban terbuka dengan rotasi ikon chevron.
   - Klik kembali $\rightarrow$ Jawaban tertutup rapi.
   - Klik tombol *"Buka Semua"* / *"Tutup Semua"* $\rightarrow$ Seluruh butir FAQ merespons serentak.
3. **Uji Filter Berdasarkan Topik:**
   - Klik pill topik (*Umum & Legalitas*, *Sistem Copytrade & MT4*, dll.) $\rightarrow$ Daftar pertanyaan tersaring instan tanpa reload halaman penuh.
4. **Uji Live Search:**
   - Masukkan kata kunci (misal: `"modal"` atau `"penarikan"`) $\rightarrow$ Hanya pertanyaan yang memuat kata kunci tersebut yang ditampilkan.
   - Klik tombol silang (X) untuk menghapus pencarian.
5. **Uji Tombol Kontak WhatsApp:**
   - Klik tombol *"Hubungi via WhatsApp"* di bagian bawah $\rightarrow$ Mengarah langsung ke kontak Pak Yazid dengan format konsultasi pertanyaan FAQ.
