# Rules 09 — Git Workflow & Kebijakan Commit/Push

## Model Pengembangan Trunk-Based (Solo Developer)

Proyek ini dikembangkan oleh pengembang solo (Frans) menggunakan pendekatan **Trunk-Based Development** langsung pada branch `main`.

---

## Kebijakan Mutlak Git (PENTING)

### 1. DILARANG Otomatis Commit dan Push
- **DILARANG KERAS** menjalankan `git commit` maupun `git push origin main` secara otomatis setelah menyelesaikan penulisan kode atau perbaikan fitur.
- Setiap perubahan harus tetap berada di *working tree* lokal sampai pengguna memberikan persetujuan eksplisit.

### 2. Wajib Menunggu Persetujuan Eksplisit (*Approval*)
- Agent harus mempresentasikan ringkasan hasil pekerjaan, hasil pengujian, dan status build kepada pengguna.
- Eksekusi `git commit` dan `git push` **hanya boleh dilakukan setelah pengguna secara eksplisit menginstruksikan atau menyetujuinya** di obrolan.

### 3. Pengecekan Kode/Baris Duplikat (Zero Duplicate Policy)
- Sebelum mengajukan commit ke pengguna, agent **WAJIB** mengaudit seluruh keluaran `git diff` untuk memastikan:
  - Tidak ada duplikasi baris atau logika yang berulang akibat proses pengeditan.
  - Tidak ada deklarasi array key ganda atau re-assignment variabel yang redundan.
  - Tidak ada tag template/SFC ganda atau penutupan tag yang tumpang-tindih.
  - Tidak ada pesan validasi atau blok teks yang berulang.

---

## Standar Format Pesan Commit (Conventional Commits)

Setiap commit ke `main` **wajib** mengikuti standar *Conventional Commits*:

Format: `{type}({scope}): {deskripsi ringkas dan jelas}`

### 1. Daftar Type Resmi:
- `feat`: Penambahan fitur baru (misal: `feat(commissions): implementasi modul 05 klaim komisi tiering 4-level`)
- `fix`: Perbaikan bug atau galat (misal: `fix(copytrade): perbaiki validasi dynamic tier minimum modal`)
- `refactor`: Restrukturisasi kode tanpa mengubah fungsionalitas (misal: `refactor(kyc): ekstrak logika verifikasi ke service`)
- `docs`: Pembaruan dokumentasi (misal: `docs(specs): perbarui alur verifikasi modul 04`)
- `test`: Penambahan atau penyesuaian unit/feature test (misal: `test(commissions): tambah uji 4-tier persentase komisi`)
- `chore`: Konfigurasi build, dependensi, atau pemeliharaan (misal: `chore(pint): sinkronisasi aturan formatting kode`)

### 2. Larangan Pesan Commit:
- **DILARANG** menggunakan pesan ambigu: `fix bug`, `update code`, `wip`, `changes`, `commit`, `asdf`.

---

## Checklist Wajib Sebelum Meminta Approval Commit

Sebelum melaporkan pekerjaan selesai dan meminta izin commit dari pengguna:
1. **Format Kode Pint Lolos 100%:**
   `& "C:\laragon\bin\php\php-8.3.12-nts-Win32-vs16-x64\php.exe" vendor/bin/pint --test app database routes tests`
2. **Feature Tests Lolos 100% (Zero Failures):**
   `& "C:\laragon\bin\php\php-8.3.12-nts-Win32-vs16-x64\php.exe" artisan test --compact`
3. **Frontend Production Build Sukses:**
   `npm run build`
4. **Audit Integritas Diff Bersih:**
   Jalankan `git diff` atau `git status` untuk memastikan tidak ada file rahasia (`.env`), file sampah, atau baris duplikat yang tertinggal.
