# Panduan Developer: Manajemen Keranjang Sampah (Trash CLI)

Dokumen ini memandu pengembang dalam memantau, memulihkan, dan membersihkan data yang terhapus secara *soft delete* di lingkungan produksi maupun lokal.

---

## 1. Konsep Utama

1. **Tanpa UI:** Pengguna dan admin tidak memiliki antarmuka pemulihan di dasbor untuk menjaga kesederhanaan operasional dan integritas alur kerja.
2. **Perlindungan Berkas Fisik:** Berkas foto profit master, screenshot penarikan broker, bukti transfer fee, dan bukti Myfxbook **tidak pernah dihapus** saat soft delete biasa terjadi.
3. **Penyelarasan Slug & Unik:** Slug artikel dan kategori yang dihapus otomatis dialihkan menjadi `{slug}--trashed-{id}` sehingga pengguna dapat segera membuat konten baru dengan judul/slug yang sama tanpa konflik.
4. **Cascade Otomatis:** Menghapus kategori/topik/post secara otomatis men-cascade soft delete ke entitas anak dengan timestamp `deleted_at` identik.

---

## 2. Modul yang Didukung

Daftar modul terdaftar di `config/trash.php`:
- `glosarium`: Glosarium Istilah Forex (`EdukasiGlosarium`)
- `faq`: FAQ Tanya Jawab (`EdukasiFaq`)
- `faq-topik`: Topik FAQ (`EdukasiFaqTopik`) — Cascade ke `faqs`
- `panduan-kategori`: Kategori Pusat Bantuan (`PanduanKategori`) — Cascade ke `panduan`
- `panduan-artikel`: Artikel Panduan Bantuan (`PanduanBantuan`)
- `profit-master`: Materi Promosi Master (`ProfitMasterPost`) — Cascade ke `images`
- `profit-master-foto`: Foto Materi Promosi (`ProfitMasterPostImage`)
- `laporan-profit`: Laporan Profit Bulanan & Master Fee (`LaporanProfitBulanan`)
- `undangan`: Tautan Undangan Pendaftaran (`Invitation`)

---

## 3. Perintah CLI Artisan

Selalu jalankan perintah menggunakan PHP CLI Laragon:
`& "C:\laragon\bin\php\php-8.3.12-nts-Win32-vs16-x64\php.exe" artisan ...`

### 3.1 Menampilkan Daftar Data Terhapus (`app:trash:list`)

```bash
# Menampilkan seluruh data terhapus dari seluruh modul
php artisan app:trash:list

# Menampilkan data terhapus untuk modul tertentu
php artisan app:trash:list panduan-artikel
php artisan app:trash:list laporan-profit
```

Tabel output memuat:
- `Modul`: Kunci modul data.
- `ID`: Primary key data.
- `Judul/Display`: Representasi teks judul/istilah/nomor data.
- `Deleted At`: Waktu data dihapus.
- `Deleted By`: Username pengguna/admin yang mengeksekusi penghapusan.

---

### 3.2 Memulihkan Data Terhapus (`app:trash:restore`)

```bash
php artisan app:trash:restore {model} {id}

# Contoh:
php artisan app:trash:restore panduan-kategori 3
php artisan app:trash:restore laporan-profit 12
```

**Perilaku Pemulihan:**
1. **Pengecekan Relasi Induk:** Data anak (misal FAQ atau Artikel) tidak dapat dipulihkan jika kategori/topik induknya masih terhapus. Pulihkan induknya terlebih dahulu.
2. **Pemulihan Bersama (Cascade):** Memulihkan data induk otomatis memulihkan anak-anak yang terhapus bersamaan dengannya (memiliki timestamp `deleted_at` identik). Anak yang sebelumnya dihapus terpisah tetap berada di tempat sampah.
3. **Pemeriksaan Bentrok Slug:** Slug dipulihkan ke bentuk aslinya. Jika sudah ada data aktif baru yang memakai slug tersebut, perintah dibatalkan dan menampilkan pesan galat.
4. **Pemeriksaan Laporan Profit:** Laporan profit periode ke-N ditolak untuk dipulihkan jika akun terkait sudah memiliki laporan aktif baru untuk periode ke-N.
5. **Pencatatan Jejak Audit:** Seluruh pemulihan dicatat otomatis ke Log Aktivitas sistem (`log_aktivitas`).

---

### 3.3 Pembersihan Permanen / Purge (`app:trash:purge`)

```bash
# Bantuan penggunaan
php artisan app:trash:purge --help

# Purge interaktif (meminta konfirmasi) untuk data terhapus > 30 hari
php artisan app:trash:purge --days=30

# Purge seluruh modul tanpa konfirmasi (misal saat testing / maintenance darurat)
php artisan app:trash:purge --days=0 --force

# Purge khusus modul profit-master
php artisan app:trash:purge --days=60 --model=profit-master --force
```

**Perilaku Purge:**
1. **Parameter `--days` Wajib:** Mencegah eksekusi destruktif tanpa batas usia data yang disengaja.
2. **Pembersihan Berkas Fisik:** Menghapus data dari DB (`forceDelete`) sekaligus menghapus seluruh berkas gambar dan dokumen terkait dari penyimpanan privat lokal (`storage/app/private`).
3. **Pembersihan Berurutan:** Entitas anak diproses terlebih dahulu sebelum entitas induk.
4. **Tanpa Jadwal Otomatis:** Perintah ini sengaja tidak dijadwalkan di cron/scheduler untuk menjamin kontrol manual penuh oleh tim insinyur.
