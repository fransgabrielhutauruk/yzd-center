# Modul 06: Edukasi, Glosarium & Fitur Interaktif (PWA & Kalkulator)

Dokumen ini memuat spesifikasi teknis dan aturan bisnis untuk modul konten edukasi investor, kamus forex, simulasi kalkulator profit, dan panduan interaktif aplikasi.

---

## 1. Ringkasan Fitur & Peran Terlibat

- **Glosarium Istilah Forex:** Kamus istilah pasar forex (misal: *Lot, Pip, Spread, Margin Call, Drawdown, Leverage*) dengan bahasa ramah pemula agar investor memahami cara kerja trading sebelum menitipkan modal.
- **Pusat Bantuan & Tutorial Video:** Panduan bertahap cara deposit di broker HFM/XM, cara install MT4, dan pemecahan masalah umum (FAQ) dilengkapi video tutorial YouTube.
- **Kalkulator Proyeksi Keuntungan Bulanan:** Alat simulasi interaktif bagi investor untuk menghitung estimasi pertumbuhan portofolio secara dinamis berdasarkan formula alokasi baku Pak Abi:
  - **50%:** *Compounding* (dibiarkan bergulir menambah ketahanan modal di broker).
  - **20%:** Penarikan rutin (*Withdrawal*) hak investor.
  - **25%:** Biaya kinerja (*Performance Fee*) Master Trader Pak Abi.
  - **5%:** Biaya operasional & pemeliharaan sistem.
- **Panduan Interaktif Pengguna (Tur Aplikasi):** Tur selamat datang (Carousel onboarding 3 langkah) dan jelajah spotlight interaktif di dashboard untuk mengedukasi investor yang gagap teknologi (*gaptek*).
- **Kapabilitas PWA (Progressive Web App):** Kemudahan bagi investor untuk meng-install portal web langsung ke layar utama (*homescreen*) smartphone tanpa melalui Google Play / App Store.

---

## 2. Entitas Database Terkait

- **`edukasi_glosarium`**: Dikelola penuh oleh Administrator (`istilah`, `kategori`, `definisi`, `created_by`).
- **`panduan_bantuan`**: Artikel panduan bertahap (`kategori`, `judul`, `konten`, `video_url`, `is_published`).
- *(Catatan: Perhitungan kalkulator dan progres status tur aplikasi dijalankan sepenuhnya di sisi client-side Vue 3 dan disimpan di `localStorage` peramban untuk menjaga performa server tetap ringan).*

---

## 3. Aturan Bisnis & Logika Komponen

### 3.1 Manajemen Glosarium & Bantuan
1. Hanya peran **Administrator** yang berhak menambah, mengedit, atau menghapus istilah glosarium dan artikel bantuan.
2. Seluruh pengguna terdaftar (Investor & Affiliator) dapat mencari istilah secara instan (*live search input*) dan menyaring berdasarkan kategori (*Dasar, Teknikal, Manajemen Risiko*).

### 3.2 Logika Matematika Kalkulator Proyeksi
Komponen Vue menerima input reaktif:
- Modal Awal ($M_0$)
- Estimasi Rata-rata Profit Bulanan ($r\%$, default: $10\%$)
- Durasi Bulan ($n$ bulan, default: $12$)

Untuk setiap bulan $t = 1 \dots n$:
1. Total Profit Bulan Berjalan: $P_t = M_{t-1} \times \frac{r}{100}$
2. Penarikan Investor (20%): $WD_t = P_t \times 0.20$
3. Master Trader Fee (25%): $FeeMaster_t = P_t \times 0.25$
4. Biaya Sistem (5%): $Ops_t = P_t \times 0.05$
5. Compounding Tambahan Modal (50%): $Comp_t = P_t \times 0.50$
6. Modal Akhir Bulan: $M_t = M_{t-1} + Comp_t$

Hasil ditampilkan dalam bentuk tabel proyeksi akumulasi dan grafik visual responsif.

### 3.3 Alur Panduan Interaktif (App Tour)
1. Saat investor baru pertama kali masuk ke dashboard setelah akun disetujui, modal **Tur Selamat Datang** otomatis muncul.
2. Pengguna dapat memilih:
   - *"Mulai Tur Interaktif"* $\rightarrow$ Menjalankan tur spotlight penunjuk elemen (sidebar, status copytrade, tombol klaim).
   - *"Lewati Panduan"* $\rightarrow$ Menutup modal dan menyimpan preferensi `yzd_tour_completed = true` di browser `localStorage`.
3. Pengguna dapat membuka kembali tur kapan saja melalui tombol bantuan di navbar atas.

---

## 4. Catatan Teknis & PWA

- **PWA Manifest & Service Worker:** Menyiapkan `manifest.webmanifest` dengan ikon adaptif (192x192, 512x512) dan tema warna `theme_color: #1e3a8a`.
- **Event `beforeinstallprompt`:** Menangkap event peramban di Vue 3 untuk menampilkan tombol *"Pasang Aplikasi"* secara dinamis di antarmuka jika peramban mendukung instalasi PWA.
- **YouTube Embed Responsive:** Menggunakan wrapper responsif `16:9` (`ratio ratio-16x9`) dengan atribut `loading="lazy"` agar tidak memberatkan loading awal halaman.

---

## 5. Ruang Catatan Tambahan (Area Kolaboratif Pengembang & AI)

*Tambahkan catatan atau perubahan kebutuhan terkait edukasi dan PWA di bawah ini:*

- **[2026-09-08]**: Fitur kalkulator proyeksi dan PWA telah diuji coba pada prototipe `investor-proyeksi.html` dan disetujui untuk diintegrasikan ke kode produksi.
- *(Tulis catatan baru di sini jika ada perubahan flow)*

