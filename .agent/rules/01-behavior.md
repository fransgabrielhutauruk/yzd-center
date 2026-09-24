# Rules 01 — Perilaku Agent & Guardrails

## Trust Model & Profil Pengembang

- **Pengembang:** Frans (Solo Developer).
- **Tingkat Kepercayaan:** Agent memiliki kepercayaan tinggi untuk membaca basis kode, menganalisis, serta membuat file dan fitur baru.
- **Batasan Mutlak:** Untuk aksi yang berpotensi merusak, mengubah konfigurasi dasar, atau tidak dapat dibatalkan (*irreversible*), serta untuk tindakan **Git Commit dan Push**, agent **WAJIB meminta dan menunggu persetujuan eksplisit (approval)** dari pengguna.

---

## Definisi Aksi Destruktif (Wajib Konfirmasi)

Sebelum menjalankan aksi-aksi di bawah ini, agent **harus** menguraikan rencana secara transparan dan menunggu izin dari pengguna:

| Aksi | Contoh Skenario |
|---|---|
| **Git Commit & Git Push** | `git commit`, `git push origin main` |
| **Menghapus file / direktori** | Menghapus controller, model, migration, atau file dokumentasi |
| **Menimpa file kritikal** | Mengganti isi `GEMINI.md`, `SRS.md`, konfigurasi environment |
| **Operasi Database Destruktif** | `migrate:fresh`, `TRUNCATE TABLE`, `DROP TABLE` |
| **Perubahan Konfigurasi Sistem** | `bootstrap/app.php`, `config/auth.php`, `config/database.php` |
| **Install / Remove Package** | `composer require`, `composer remove`, `npm install`, `npm uninstall` |
| **Modifikasi Rute Utama** | Perubahan besar pada struktur grup `routes/web.php` |

---

## Klasifikasi File & Tingkat Otoritas

### 1. File Kritikal (Wajib Persetujuan Sebelum Mengubah)
```text
GEMINI.md
.agent/rules/*
app/Models/User.php
app/Http/Middleware/
bootstrap/app.php
config/
routes/web.php
database/migrations/ (migrasi yang sudah dijalankan)
.env (dilarang keras menampilkan nilai sensitif di percakapan)
```

### 2. File Non-Kritikal (Boleh Langsung Dibuat / Disesuaikan Sesuai Scope Tugas)
```text
resources/js/Pages/         (halaman Vue fitur yang sedang dikerjakan)
resources/js/Components/    (komponen UI fitur)
resources/js/Layouts/       (penambahan menu navigasi yang disepakati)
app/Http/Controllers/       (controller fitur terkait)
app/Http/Requests/          (Form Request validasi baru)
app/Services/               (business logic service baru)
app/Notifications/          (notifikasi in-app baru)
database/migrations/        (file migrasi baru yang dirancang)
tests/Feature/              (file pengujian otomatis baru)
docs/information-design/    (sinkronisasi spesifikasi teknis)
docs/verifikasi-model/      (sinkronisasi skenario uji manual)
```

---

## Alur Kerja Standar (Execution Pipeline)

### A. Fitur Baru / Perubahan Signifikan (Medium–Besar)
1. **Riset & Validasi Scope:** Baca spesifikasi di [`docs/information-design/`](file:///e:/5_projects/yzd-trading-management/docs/information-design) dan aturan terkait di [`.agent/rules/`](file:///e:/5_projects/yzd-trading-management/.agent/rules).
2. **Implementation Plan:** Susun dokumen rencana implementasi terperinci (file baru/ubah, migrasi, validasi, UI, dan skenario pengujian).
3. **Approval Pengguna:** Tunggu konfirmasi eksplisit sebelum menulis kode sumber.
4. **Eksekusi Bertahap:**
   - Database: Migration & Model
   - Backend Logic: Service & Form Request
   - HTTP Layer: Controller & Routes (`routes/web.php`)
   - Notification: In-App Database Notifications
   - Frontend UI: Vue Page, Layout Navigasi, Modal, AppDataTable
   - Testing: Automated Feature Tests (`tests/Feature/`)
5. **Standarisasi & Pemformatan:**
   - Format kode: `& "C:\laragon\bin\php\php-8.3.12-nts-Win32-vs16-x64\php.exe" vendor/bin/pint --test app database routes tests`
   - Uji otomatis: `& "C:\laragon\bin\php\php-8.3.12-nts-Win32-vs16-x64\php.exe" artisan test --compact`
   - Build aset: `npm run build`
6. **Audit Zero Duplicate (PENTING):** Periksa `git diff` menyeluruh untuk memastikan tidak ada duplikasi baris, atribut template ganda, atau fragment kode basi.
7. **Laporan & Tunggu Approval Git:** Laporkan hasil implementasi beserta panduan verifikasi. **JANGAN** lakukan commit/push otomatis.

### B. Perbaikan Bug / Penyesuaian Ringan (< 3 File)
1. Identifikasi akar masalah (*root cause*), bukan sekadar gejalanya.
2. Lakukan perbaikan langsung.
3. Jalankan Pint, Feature Tests terkait, dan build.
4. Periksa `git diff` dan laporkan hasil ke pengguna.

---

## Kapan Agent Wajib Berhenti & Bertanya (Socratic Gate)

Agent **wajib berhenti dan meminta arahan** jika:
1. Menemukan persyaratan bisnis yang bertentangan atau belum diatur dalam `docs/information-design/`.
2. Terdapat lebih dari satu pendekatan arsitektur yang valid dengan trade-off performa atau keamanan yang signifikan.
3. Akan menambahkan dependensi/package luar baru.
4. Menemukan potensi hilangnya data pengguna atau rusaknya integritas relasi tabel.
5. Perubahan di luar rencana awal yang berpotensi memengaruhi modul lain yang sudah selesai.

---

## Larangan Komunikasi & Keamanan Data

- **DILARANG** menampilkan isi `.env` atau nilai rahasia nyata (API secret, database password, encryption key) di chat. Gunakan penanda placeholder (misal: `your-secret-here`).
- **DILARANG** mencetak ulang seluruh isi file panjang ke dalam respons obrolan jika file tersebut sudah tersimpan di direktori proyek — cukup rujuk path dan baris relevannya.
- **DILARANG** menggunakan bahasa klise chatbot AI (*"Tentu, saya adalah asisten AI..."*, hiperbola kosong). Komunikasi harus lugas, profesional, dan fokus pada konteks finansial YZD Center.
