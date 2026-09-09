# Rules 01 — Perilaku Agent & Guardrails

## Trust Model

Developer: **Frans (solo)**. Agent punya kepercayaan tinggi untuk membaca, menganalisis, dan membuat file baru. Namun untuk aksi yang berpotensi merusak atau tidak dapat di-undo, **wajib konfirmasi eksplisit** terlebih dahulu.

---

## Definisi: Aksi Destruktif (Wajib Konfirmasi)

Sebelum melakukan hal-hal berikut, agent **harus** menyebutkan rencana aksi dan menunggu approval user:

| Aksi | Contoh |
|------|--------|
| Menghapus file | hapus Controller.php, hapus migration |
| Menimpa file yang sudah ada | Overwrite GEMINI.md, SRS.md, file catatan |
| Drop/truncate tabel | migrate:fresh, TRUNCATE TABLE |
| Reset database | php artisan migrate:fresh --seed |
| Membuat domain/area fitur baru | Folder domain baru di app/ |
| Install/remove package | composer require, composer remove, npm install X |
| Mengubah file konfigurasi kritikal | config/auth.php, bootstrap/app.php, .env |
| Mengubah route utama | routes/web.php, routes/api.php |

---

## Definisi: File Kritikal (Ubah dengan Konfirmasi)

File-file berikut harus dikonfirmasi **sebelum** diubah, bukan setelah:

```
app/Models/User.php
app/Http/Middleware/
bootstrap/app.php
config/auth.php
config/app.php
routes/web.php
routes/api.php
database/migrations/ (yang sudah ada — migration baru tetap perlu plan)
.env (jangan pernah tampilkan isi .env di chat)
```

---

## Definisi: File Non-Kritikal (Boleh Langsung)

File-file berikut boleh dibuat/diubah langsung tanpa konfirmasi terlebih dahulu:

```
resources/js/Components/    (file baru)
resources/js/Pages/         (file baru)
resources/js/Layouts/       (file baru)
resources/js/composables/   (file baru)
app/Http/Controllers/       (file baru — bukan mengubah yang sudah ada)
app/Services/               (file baru)
catatan/                    (file catatan baru, setelah user setuju perlu dicatat)
```

---

## Alur Kerja Standar

### Untuk Fitur Baru (perubahan medium-besar):
```
1. Baca SRS.md — pastikan fitur ada di scope
2. Baca rules relevan di .agent/rules/
3. Tulis rencana (plan) — file apa yang dibuat/diubah
4. Tunggu approve user
5. Eksekusi — urutan: migration → model → service → controller → route → Vue page → form request
6. Format kode: vendor/bin/pint --dirty
7. Jalankan test: php artisan test --compact
8. Laporkan hasil
```

### Untuk Bug Fix / Perubahan Kecil:
```
1. Identifikasi root cause
2. Langsung perbaiki (tidak perlu plan formal untuk perubahan < 3 file)
3. Format & test
4. Laporkan
```

### Untuk Pertanyaan / Riset:
```
1. Jawab langsung di chat
2. Tawarkan pencatatan ke catatan/ jika ada keputusan substansial
```

---

## Kapan Agent Harus Berhenti & Tanya

Agent **wajib berhenti dan tanya** sebelum melanjutkan jika:

1. Requirement ambigu atau saling bertentangan dengan SRS
2. Ada dua pendekatan valid dengan trade-off berbeda
3. Akan mengubah file kritikal yang sudah ada
4. Akan install library baru
5. Akan membuat area/domain fitur baru
6. Estimasi perubahan melibatkan > 5 file sekaligus
7. Ada risiko data loss (ubah kolom migration, rename, delete)

---

## Kapan Agent Boleh Langsung Kerja

Agent boleh langsung eksekusi tanpa menunggu konfirmasi tambahan jika:

1. User sudah menyetujui plan secara eksplisit
2. Perubahan hanya di file non-kritikal
3. Membuat file baru (bukan menimpa yang ada)
4. Menjawab pertanyaan / menjelaskan kode
5. Membuat file catatan baru (setelah user setuju perlu dicatat)

---

## Larangan Komunikasi

- Jangan tampilkan isi `.env` di chat dalam bentuk apapun
- Jangan generate nilai secret/key nyata di chat — gunakan placeholder `your-secret-here`
- Jangan asumsikan credential database atau API key
- Jangan copy-paste isi file panjang ke chat jika sudah disimpan — cukup sebutkan path-nya
