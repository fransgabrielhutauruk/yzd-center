# Dokumentasi & Panduan Verifikasi Implementasi Sistem

Direktori ini memuat seluruh panduan verifikasi fungsionalitas, daftar rute web (`routes/web.php`), skenario pengujian manual, serta suite pengujian otomatis untuk setiap modul dalam sistem **YZD Center**.

---

## 1. Daftar Modul & Status Verifikasi

| Kode Modul | Nama Modul | Dokumen Desain Informasi | Panduan Verifikasi Implementasi | Status Implementasi | Status Uji Otomatis |
| :---: | :--- | :--- | :--- | :---: | :---: |
| **01** | Autentikasi, Manajemen Akun & Multi-Role | [01_autentikasi_dan_manajemen_akun.md](../information-design/01_autentikasi_dan_manajemen_akun.md) | [01_autentikasi_dan_manajemen_akun.md](01_autentikasi_dan_manajemen_akun.md) | Selesai | 13/13 Lulus |
| **02** | Legalitas, KYC & Perlindungan Ahli Waris | [02_legalitas_kyc_dan_ahli_waris.md](../information-design/02_legalitas_kyc_dan_ahli_waris.md) | [02_legalitas_kyc_dan_ahli_waris.md](02_legalitas_kyc_dan_ahli_waris.md) | Selesai | 39/39 Lulus |
| **03** | Jaringan & Afiliasi | [03_jaringan_dan_afiliasi.md](../information-design/03_jaringan_dan_afiliasi.md) | [03_jaringan_dan_afiliasi.md](03_jaringan_dan_afiliasi.md) | Selesai | 15/15 Lulus |
| **04** | Copytrade & Broker | [04_copytrade_dan_broker.md](../information-design/04_copytrade_dan_broker.md) | *(Tahap Berikutnya)* | Pending | - |
| **05** | Klaim Komisi Afiliasi | [05_klaim_komisi_afiliasi.md](../information-design/05_klaim_komisi_afiliasi.md) | *(Tahap Berikutnya)* | Pending | - |
| **06** | Edukasi & Fitur Interaktif | [06_edukasi_dan_fitur_interaktif.md](../information-design/06_edukasi_dan_fitur_interaktif.md) | *(Tahap Berikutnya)* | Pending | - |
| **07** | Pengaturan Sistem & Konten Legal | [07_pengaturan_sistem_dan_konten_legal.md](../information-design/07_pengaturan_sistem_dan_konten_legal.md) | *(Tergabung di Modul 02)* | Selesai | 10/10 Lulus |

---

## 2. Ringkasan Eksekusi Pengujian Otomatis (*Test Suite*)

Semua pengujian dijalankan menggunakan PHP CLI 8.3 Laragon:

```powershell
# Jalankan seluruh pengujian fitur aplikasi
& "C:\laragon\bin\php\php-8.3.12-nts-Win32-vs16-x64\php.exe" artisan test --compact

# Verifikasi standar kode (Laravel Pint)
& "C:\laragon\bin\php\php-8.3.12-nts-Win32-vs16-x64\php.exe" vendor/bin/pint --test app database routes tests

# Verifikasi kompilasi frontend
npm run build
```

---

## 3. Akun Pengujian Standar (Database Seeder)

Untuk keperluan pengujian lokal, sistem telah dilengkapi dengan seeder database:

- **Administrator:** `admin@yzdcenter.com` / `password`
- **Tautan Registrasi Investor:** Generate melalui menu Admin `/admin/invitations` atau gunakan token seeder `INV-TEST-INVESTOR`.
- **Fitur Dev Tool:** Tombol *"Isi Form Acak (Dev)"* pada halaman `/register` untuk mengisi data dummy lengkap dan password default `asdf1234`.
