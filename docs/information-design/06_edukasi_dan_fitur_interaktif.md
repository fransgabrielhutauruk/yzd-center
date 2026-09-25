# Modul 06: Edukasi, Glosarium & Fitur Interaktif (Kalkulator Proyeksi Finansial)

Dokumen ini memuat spesifikasi teknis dan aturan bisnis untuk modul konten edukasi investor, kamus forex, Pusat Bantuan bertopik, dan simulasi kalkulator profit interaktif (CRM Investor).

--- 

## 1. Ringkasan Fitur & Peran Terlibat

- **Glosarium Istilah Forex:** Kamus istilah pasar forex (misal: *Lot, Pip, Spread, Margin Call, Drawdown, Leverage, Equity, Copytrade*) dengan bahasa ramah pemula, pencarian cepat kata kunci, dan indeks alfabet A-Z agar investor memahami cara kerja trading sebelum menitipkan modal.
- **Pusat Bantuan & Tutorial Video (Bertopik):** Panduan bertahap langkah demi langkah yang terorganisir rapi dalam kategori topik (*Panduan Broker & Deposit*, *Pendaftaran & KYC*, *Copytrade & MT4*, *Kemitraan Afiliasi*) dilengkapi pemutar video tutorial YouTube responsif (16:9) dan counter jumlah tayangan pembaca.
- **Tanya Jawab (FAQ) Terkategorisasi (Tanpa Slug):** Fitur tanya jawab interaktif yang **dipisahkan secara mandiri dari Pusat Bantuan**. FAQ **tidak memiliki slug** (tidak ada halaman detail individual per item), melainkan disajikan dalam bentuk daftar *accordion* buka-tutup interaktif per Topik (*Umum & Legalitas*, *Sistem Copytrade & MT4*, *Deposit, Pembagian Hasil & Penarikan*, *Kemitraan & Afiliasi*) dengan tombol pencarian instan dan tombol buka/tutup semua.
- **Kalkulator Proyeksi Keuntungan Bulanan (Formula CSV Tab "5% (Conservativ)"):** Alat simulasi interaktif bagi investor dan affiliator untuk memproyeksikan target pertumbuhan finansial hingga 5 tahun ke depan (dapat dikustomisasi) berdasarkan formula baku:
  - **Variabel Input Pengguna (Dapat Disimpan):**
    - `Modal Awal` ($ USD, default: `$100 .00`)
    - `Rata-Rata Profit Bulanan` (%, default: `5.0%`)
    - `Durasi Proyeksi` (Tahun, default: `5 Tahun` = 60 Bulan, kustom hingga 10 tahun)
    - `Kurs Acuan Rupiah` (Rp / USD, sinkronisasi otomatis harian via Frankfurter API pukul 06:00 WIB, default fallback: `Rp 16.796`)
  - **Sinkronisasi Kurs Acuan Harian (Frankfurter API):**
    - Mengambil nilai tukar pasar tengah resmi USD ke IDR dari API publik (`https://api.frankfurter.dev/v1/latest?base=USD&symbols=IDR`) 1 kali sehari setiap pukul 06:00 WIB melalui command `php artisan app:update-usd-exchange-rate`.
    - Disimpan di cache sistem (durasi 26 jam) dan tabel konfigurasi `pengaturan_master` (`kurs_usd_idr`, `kurs_usd_idr_updated_at`).
    - **Ketahanan Sistem (Fallback):** Jika koneksi API gagal atau offline, sistem otomatis beralih menggunakan nilai kurs terakhir di database atau nilai cadangan bawaan (Rp 16.796).
    - **Interaktivitas UI:** Terdapat status badge live market rate serta tombol *"Pakai Kurs Hari Ini"* jika pengguna sempat menguji kalkulasi dengan angka kurs kustom.
  - **Formula Perhitungan Tiap Bulan $t$:**
    - $\text{Profit}_t = \text{Modal}_{t} \times \left( \frac{r}{100} \right)$
    - $\text{Hak Investor (Max 30\% WD)}_t = \text{Profit}_t \times 0.30$
    - $\text{Dalam Rupiah}_t = \text{Hak Investor}_t \times \text{Kurs}$
    - $\text{Master Fee (30\%)}_t = \text{Profit}_t \times 0.30$
    - $\text{Compounding Tambahan Modal (40\%)}_t = \text{Profit}_t \times 0.40$
    - $\text{Modal Akhir Bulan}_t = \text{Modal}_t + \text{Compounding}_t$
    - Modal bulan berikutnya berjalan otomatis dari $\text{Modal Akhir Bulan}_t$.
  - **Fee Affiliate:** Dihiraukan / tidak memotong porsi hak investor.
  - **Alokasi Pembagian:** 40% Compounding ke modal akun broker, 30% Hak penarikan rutin investor (WD), dan 30% Master Trader Fee.
  - **Persistensi Preferensi:** Pengguna dapat menyimpan variabel proyeksi langsung ke peramban (`localStorage`) dengan tombol *"Simpan Variabel"*, serta opsi *"Reset Default"*.
  - **Dukungan Tinjauan:** Tab Tinjauan Tahunan (Tahun 1 s/d 5) dan Rincian Bulanan (Bulan 1 s/d 60) lengkap dengan kolom Investor WD, Master Fee, dan Compounding Modal.
  - **Konsultasi Langsung:** Tombol WhatsApp langsung ke kontak resmi Pak Yazid (`082388986189`).

> [!NOTE]
> **Pengecualian Scope (Ditunda ke Finishing MVP):**
> Panduan Interaktif Pengguna (Tur Aplikasi) dan Kapabilitas PWA (Progressive Web App) dikeluarkan dari modul ini dan dijadwalkan pada modul penyempurnaan akhir MVP.

---

## 2. Entitas Database Terkait

- **`pengaturan_master`**: Menyimpan konfigurasi master sistem dan kurs pasar tengah cadangan (`kurs_usd_idr`, `kurs_usd_idr_updated_at`).
- **`edukasi_glosarium`**: Dikelola penuh oleh Administrator (`istilah`, `singkatan`, `definisi`, `contoh_kasus`, `urutan`, `is_active`, `created_by`).
- **`panduan_kategori`**: Kategori topik panduan langkah bantuan (`nama`, `slug`, `deskripsi`, `icon`, `urutan`, `is_active`).
- **`panduan_bantuan`**: Artikel panduan bertahap (`kategori_id`, `judul`, `slug`, `ringkasan`, `konten`, `video_url`, `urutan`, `is_published`, `views_count`, `created_by`).
- **`edukasi_faq_topik`**: Topik kategori untuk Tanya Jawab / FAQ (**tanpa slug**, `nama`, `deskripsi`, `urutan`, `is_active`).
- **`edukasi_faq`**: Butir pertanyaan & jawaban FAQ (**tanpa slug**, `topik_id`, `pertanyaan`, `jawaban`, `urutan`, `is_active`, `created_by`).
- *(Catatan: Perhitungan kalkulator dijalankan secara reaktif di sisi client-side Vue 3 dan disimpan di `localStorage` peramban untuk menjaga performa server tetap ringan).*

---

## 3. Ruang Catatan Tambahan (Area Kolaboratif Pengembang & AI)

- **[2026-09-24]**: Pusat Bantuan dilengkapi entitas `panduan_kategori` untuk pemisahan topik yang rapi. Tur Aplikasi dan PWA ditunda ke modul finishing akhir MVP.
- **[2026-09-25]**: Kalkulator proyeksi diperbarui mengadopsi model riil tab CSV *"5% (Conservativ)"* dengan variabel input (Modal Awal & Rata-rata Profit Bulanan) untuk proyeksi 5 tahun (default, kustomisasi hingga 10 tahun). Formula pembagian profit diselaraskan menjadi: **30% Master Fee**, **30% Hak Investor (WD)**, dan **40% Modal Compounding**. Fee affiliate dihiraukan.
- **[2026-09-25]**: Kurs acuan USD ke IDR diintegrasikan langsung dengan Frankfurter API publik dengan jadwal pembaruan harian otomatis pukul 06:00 WIB, penyimpanan cache 26 jam, dan perlindungan fallback ke database `pengaturan_master`.
- **[2026-09-25]**: Fitur FAQ dipisahkan secara mandiri dari Pusat Bantuan sesuai instruksi pengguna. FAQ tidak menggunakan slug (`slug` dihilangkan total), memiliki pengelompokan per Topik (`edukasi_faq_topik` & `edukasi_faq`), dan disajikan interaktif dengan komponen accordion di `/investor/education/faq`. Migrasi disatukan langsung pada file migrasi create sumbernya (`2026_09_24_180000_create_edukasi_dan_bantuan_tables.php`).
- **[2026-09-25]**: Atribut kategori pada entitas `edukasi_glosarium` dihapus sesuai permintaan pengguna, menjadikan kamus glosarium fokus pada indeks alfabet A-Z dan pencarian kata kunci langsung. Single source migration diselaraskan.
