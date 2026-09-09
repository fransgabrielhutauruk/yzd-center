# GEMINI.md — Master Rules: YZD Trading Management

> **Untuk agent:** Baca file ini dulu di setiap sesi. Lalu baca rules spesifik di `.agent/rules/` sesuai konteks permintaan. Jangan mulai eksekusi sebelum memahami rules yang relevan.

---

## 0. Peta Rules (Index)

| File | Topik |
|------|-------|
| `.agent/rules/01-behavior.md` | Perilaku agent, guardrails, kapan tanya vs langsung kerja |
| `.agent/rules/02-tech-stack.md` | Stack resmi, versi, library yang diizinkan |
| `.agent/rules/03-architecture.md` | Thin controller + fat service, struktur folder |
| `.agent/rules/04-coding-conventions.md` | Naming, PHP conventions, view payload pattern |
| `.agent/rules/05-database.md` | Migration template, naming tabel, soft delete |
| `.agent/rules/06-security.md` | Role-based auth, middleware, file upload |
| `.agent/rules/07-frontend.md` | Vue 3 + Inertia.js + Tailwind CSS conventions |
| `.agent/rules/08-testing.md` | Feature test strategy, PHPUnit |
| `.agent/rules/09-git.md` | Branch naming, conventional commits |
| `.agent/rules/10-deployment.md` | Environment, queue, cache, storage |

| Workflow | Kapan Dipakai |
|----------|---------------|
| `.agent/workflows/new-feature.md` | Saat membuat fitur baru dari nol |
| `.agent/workflows/new-migration.md` | Saat membuat migration database |
| `.agent/workflows/code-review.md` | Sebelum commit/PR |
| `.agent/workflows/requirement-session.md` | Saat diskusi requirement baru |
| `.agent/workflows/debug-session.md` | Saat debugging error |

---

## 1. Klasifikasi Permintaan

### Langsung jawab di chat (tidak perlu buat dokumen):
- Pertanyaan umum / diskusi konsep
- Klarifikasi requirement yang jawabannya singkat
- Penjelasan kode yang sudah ada

### Tulis langsung ke file (jangan hanya di chat):
- Permintaan menyusun/mengubah SRS, use case, proses bisnis → tulis ke `catatan/` atau file terkait
- Permintaan coding (feature, migration, bug fix) → tulis langsung ke codebase

### Tanya dulu ke user sebelum eksekusi (Socratic Gate):
- Requirement ambigu — jangan tebak lalu dokumentasikan sebagai fakta
- Permintaan yang akan mengubah file kritikal (lihat definisi di `01-behavior.md`)
- Permintaan yang membutuhkan pilihan arsitektur atau library baru

---

## 2. Aturan Pencatatan Saran & Keputusan

**Aturan penting:** Setiap kali agent memberikan saran atau rekomendasi substansial (misal: pilihan library, keputusan arsitektur, flow bisnis), agent **HARUS BERTANYA DULU:**

> *"Apakah saran ini perlu saya catat ke `catatan/`?"*

Jika user menjawab ya, simpan ke:
```
catatan/YYYY-MM-DD_topik-singkat.md
```

Format isi catatan:
```markdown
# <Judul Topik>

**Tanggal:** YYYY-MM-DD
**Konteks/Pertanyaan:** <situasi yang memicu diskusi>

## Saran/Rekomendasi
<rekomendasi utama>

## Alasan
- <alasan 1>
- <alasan 2>

## Alternatif yang Dipertimbangkan
- <opsi lain + trade-off singkat, jika ada>

## Status
- [ ] Belum diputuskan / [ ] Disetujui / [ ] Ditolak
```

**Kapan TIDAK perlu dicatat:**
- Obrolan basa-basi / pertanyaan trivial
- Klarifikasi kecil tanpa keputusan baru

**Update, bukan duplikat.** Topik yang sama dibahas ulang → update file yang sudah ada, jangan buat baru.

---

## 3. Alur Kerja Wajib (Guardrails)

### Untuk dokumen besar (SRS, use case, arsitektur):
1. Tulis outline singkat dulu → minta konfirmasi
2. Baru tulis dokumen lengkap
3. Verifikasi konsistensi dengan dokumen yang sudah ada

### Untuk coding (feature baru, migration, perubahan besar):
1. **Plan** — jelaskan rencana perubahan
2. **Konfirmasi** — tunggu approve user
3. **Eksekusi** — tulis kode
4. **Verifikasi** — jalankan test, format kode

### Untuk bug fix / perubahan kecil:
- Boleh langsung eksekusi tanpa plan formal
- Tetap wajib format kode sebelum selesai

---

## 4. Larangan Absolut (Hard Constraints)

- **DILARANG** memulai implementasi saat masih tahap requirement, kecuali diminta eksplisit
- **DILARANG** menghapus/menimpa file dokumentasi yang sudah ada tanpa konfirmasi user
- **DILARANG** membuat asumsi requirement teknis sebagai "final" tanpa status "Belum diputuskan"
- **DILARANG** melakukan aksi destruktif (drop table, hapus file, reset DB) tanpa konfirmasi eksplisit
- **DILARANG** membuat domain/area fitur baru tanpa konfirmasi
- **DILARANG** install library baru tanpa diskusi dan pencatatan ke `catatan/`
- **DILARANG** menggunakan `env()` langsung di luar file `config/`
- **DILARANG** hardcode credential, URL produksi, atau secret di kode

---

## 5. Gaya Komunikasi Agent

- Jawab ringkas, langsung ke inti
- Bahasa Indonesia untuk komunikasi dan semua dokumentasi
- Istilah teknis (controller, service, migration, dll.) boleh tetap Bahasa Inggris
- Untuk keputusan besar (library, arsitektur): sebutkan trade-off singkat, tawarkan pencatatan ke `catatan/`
- Jangan menampilkan ulang isi file yang sudah disimpan — cukup beri tahu path-nya

---

## 6. Konteks Proyek

- **Nama Proyek:** YZD Trading Management
- **Klien:** Pak Rizki Abiyasid (alias: Pak Abi)
- **SRS:** `SRS.md` (v1.2.0 — baca sebelum mulai implementasi fitur baru)
- **Stack:** Laravel 12 + Inertia.js v2 + Vue 3 + Tailwind CSS v3 + PHP 8.4
- **Auth:** Role-based sederhana (3 role: Administrator, Investor, Affiliator) — field `role` di tabel users
- **Arsitektur:** Thin Controller + Fat Service, standard Laravel structure (tanpa Nwidart)
- **Developer:** Frans (solo) — agent punya kepercayaan tinggi, tapi tetap konfirmasi untuk aksi destruktif

---

## 7. Catatan Penggunaan Rules

- File `.agent/rules/` adalah rules operasional detail — baca sesuai konteks
- File `.agent/workflows/` adalah checklist langkah demi langkah — ikuti saat eksekusi
- Jika rules ini mendekati ~12.000 karakter, pecah ke rules baru di `.agent/rules/`
- Rules ini berlaku untuk **semua tahap**: requirement → design → implementation → testing → deployment
