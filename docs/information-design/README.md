# Information Design System — YZD Center

Folder `/information-design` ini berfungsi sebagai **pusat referensi detail kebutuhan teknis dan logika bisnis per modul** yang digunakan selama fase perancangan (*design*) dan implementasi (*coding*). 

Dokumen ini bersifat kolaboratif: **Anda dan AI dapat saling memperbarui, melengkapi catatan, atau menambahkan aturan khusus sewaktu-waktu.**

---

## Struktur Modul Informasi Desain

| No | Modul / Fitur | Berkas Dokumentasi | Fokus Informasi Utama |
|---|---|---|---|
| 01 | **Autentikasi & Akun Pengguna** | [`01_autentikasi_dan_manajemen_akun.md`](./01_autentikasi_dan_manajemen_akun.md) | Multi-role (Investor & Affiliator), registrasi tertutup via link token Mayar, verifikasi status, audit trail terms. |
| 02 | **Legalitas, KYC & Ahli Waris** | [`02_legalitas_kyc_dan_ahli_waris.md`](./02_legalitas_kyc_dan_ahli_waris.md) | Klausul perlindungan ahli waris, upload berkas privat (KTP/Agreement/Selfie), alur verifikasi admin. |
| 03 | **Jaringan & Referral** | [`03_jaringan_dan_afiliasi.md`](./03_jaringan_dan_afiliasi.md) | Kode referral unik, relasi permanen 1 downline 1 upline, PB-4 klaim referral susulan via bukti chat. |
| 04 | **CopyTrade & Akun Broker** | [`04_copytrade_dan_broker.md`](./04_copytrade_dan_broker.md) | Stepper 3-tahap, verifikasi deposit broker oleh admin, buka gembok link rahasia master, portofolio MQL5. |
| 05 | **Klaim Komisi Afiliasi** | [`05_klaim_komisi_afiliasi.md`](./05_klaim_komisi_afiliasi.md) | Syarat aktif $\ge$ 3 bulan pro-rata, tiering otomatis 30%/40%/50% dari profit master, bukti transfer admin. |
| 06 | **Edukasi & Fitur Interaktif** | [`06_edukasi_dan_fitur_interaktif.md`](./06_edukasi_dan_fitur_interaktif.md) | Glosarium forex, FAQ/Tutorial video, kalkulator proyeksi bulanan, tur aplikasi interaktif, PWA install. |
| 07 | **Pengaturan Sistem & Konten Legal** | [`07_pengaturan_sistem_dan_konten_legal.md`](./07_pengaturan_sistem_dan_konten_legal.md) | URL Myfxbook Master & broker referral, Syarat Ketentuan & Kebijakan Privasi dinamis berversi. |

---

## Format Standar Setiap File Modul

Setiap file di dalam direktori ini disusun dengan struktur seragam:
1. **Ringkasan Fitur & Peran Terlibat**
2. **Entitas Database & Relasi Terkait**
3. **Aturan Bisnis & Logika Validasi (Business Logic & Validation Rules)**
4. **State Machine / Siklus Status (Status Lifecycle)**
5. **Catatan Teknis & Keamanan (Security & Technical Notes)**
6. **Ruang Catatan Tambahan (Area Kolaboratif Anda & AI)**

