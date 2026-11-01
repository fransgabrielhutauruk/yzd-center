# Rules 09 — Git Workflow & Kebijakan Commit/Push

## Model Branching: Dual-Branch (`develop` & `main`)

Aplikasi ini telah berada di **fase produksi**. Model Git menggunakan strategi **Dual-Branch** yang memisahkan lingkungan pengembangan aktif dari lingkungan produksi yang stabil:

```text
  (Fitur / Bugfix / Refactor Harian)
                   │
                   ▼
[ develop ] ───► Commit & Push (Trunk-based harian setelah approval)
                   │
                   │ (Hanya saat ada perintah eksplisit dari Pengguna)
                   ▼
  [ main ]  ───► git merge --no-ff develop + Git Tag (SemVer)
```

---

## Aturan Cabang (Branching Policy)

### 1. Branch `develop` (Cabang Utama Pengembangan)
- Merupakan cabang aktif *default* untuk seluruh pekerjaan sehari-hari solo developer (Frans).
- Semua penambahan fitur baru, perbaikan bug, refactoring, maupun pembaruan pengujian langsung dikerjakan, di-commit, dan di-push ke `develop` (*Trunk-based on develop*).
- **Semua perbaikan (termasuk hotfix bug produksi)** wajib dikerjakan dan diverifikasi terlebih dahulu di `develop`. Tidak ada cabang perbaikan darurat yang langsung di-commit ke `main`.

### 2. Branch `main` (Cabang Produksi Terkunci — Production Locked)
- Merepresentasikan kode sumber stabil yang sedang berjalan di server produksi.
- **DILARANG KERAS** melakukan direct commit atau direct push ke branch `main`.
- Merge dari `develop` ke `main` **HANYA BOLEH DILAKUKAN ATAS PERINTAH EKSPLISIT DARI PENGGUNA**.

---

## Prosedur Rilis Produksi (Merge `develop` ke `main`)

Ketika pengguna memberikan perintah eksplisit (misal: *"merge ke main"*, *"rilis ke production"*, atau *"siapkan rilis v1.x.x"*), ikuti langkah baku berikut:

1. **Pastikan `develop` Bersih & Ter-push:**
   - Semua test lolos 100%, frontend build sukses, pint clean, dan branch `develop` sudah up-to-date dengan remote `origin/develop`.
2. **Pindah ke `main` dan Tarik Versi Terbaru:**
   ```powershell
   git checkout main
   git pull origin main
   ```
3. **Lakukan Merge Eksplisit (--no-ff):**
   ```powershell
   git merge --no-ff develop -m "release: vX.Y.Z rilis produksi [deskripsi ringkas]"
   ```
4. **Sematkan Git Tag Rilis (Semantic Versioning):**
   ```powershell
   git tag -a vX.Y.Z -m "Release vX.Y.Z: [catatan perubahan ringkas]"
   ```
5. **Push ke Remote dengan Tag:**
   ```powershell
   git push origin main --tags
   ```
6. **Kembalikan Branch Aktif ke `develop`:**
   ```powershell
   git checkout develop
   ```

---

## Kebijakan Mutlak Git (PENTING)

### 1. DILARANG Otomatis Commit dan Push
- **DILARANG KERAS** menjalankan `git commit` maupun `git push origin develop` / `git push origin main` secara otomatis tanpa instruksi pengguna.
- Setiap perubahan harus tetap berada di *working tree* lokal sampai pengguna memberikan persetujuan eksplisit di chat.

### 2. Wajib Menunggu Persetujuan Eksplisit (*Approval*)
- Agent harus mempresentasikan ringkasan hasil pekerjaan, hasil pengujian, status build, dan draft pesan commit kepada pengguna.
- Eksekusi `git commit` dan `git push` **hanya boleh dilakukan setelah pengguna secara eksplisit menginstruksikan atau menyetujuinya**.

### 3. Pengecekan Kode/Baris Duplikat (Zero Duplicate Policy)
- Sebelum mengajukan commit ke pengguna, agent **WAJIB** mengaudit seluruh keluaran `git diff` untuk memastikan:
  - Tidak ada duplikasi baris atau logika yang berulang akibat proses pengeditan.
  - Tidak ada deklarasi array key ganda atau re-assignment variabel yang redundan.
  - Tidak ada tag template/SFC ganda atau penutupan tag yang tumpang-tindih.
  - Tidak ada pesan validasi atau blok teks yang berulang.

---

## Standar Format Pesan Commit (Conventional Commits)

Setiap commit ke `develop` maupun `main` **wajib** mengikuti standar *Conventional Commits*:

Format: `{type}({scope}): {deskripsi ringkas dan jelas}`

### 1. Daftar Type Resmi:
- `feat`: Penambahan fitur baru (misal: `feat(commissions): implementasi modul 05 klaim komisi tiering 4-level`)
- `fix`: Perbaikan bug atau galat (misal: `fix(copytrade): perbaiki validasi dynamic tier minimum modal`)
- `refactor`: Restrukturisasi kode tanpa mengubah fungsionalitas (misal: `refactor(kyc): ekstrak logika verifikasi ke service`)
- `docs`: Pembaruan dokumentasi (misal: `docs(specs): perbarui alur verifikasi modul 04`)
- `test`: Penambahan atau penyesuaian unit/feature test (misal: `test(commissions): tambah uji 4-tier persentase komisi`)
- `chore`: Konfigurasi build, dependensi, atau pemeliharaan (misal: `chore(pint): sinkronisasi aturan formatting kode`)
- `release`: Commit merge penanda rilis produksi ke branch `main` (misal: `release: v1.1.0 modul 05 komisi klaim`)

### 2. Larangan Pesan Commit:
- **DILARANG** menggunakan pesan ambigu: `fix bug`, `update code`, `wip`, `changes`, `commit`, `asdf`.

---

## Checklist Wajib Sebelum Meminta Approval Commit

Sebelum melaporkan pekerjaan selesai dan meminta izin commit dari pengguna:
1. **Pastikan Berada di Branch `develop`:**
   `git branch --show-current` (harus `develop`)
2. **Format Kode Pint Lolos 100%:**
   `& "C:\laragon\bin\php\php-8.3.12-nts-Win32-vs16-x64\php.exe" vendor/bin/pint --dirty`
3. **Targeted / Full Tests Lolos (Zero Failures):**
   `& "C:\laragon\bin\php\php-8.3.12-nts-Win32-vs16-x64\php.exe" artisan test --compact`
4. **Frontend Production Build Sukses:**
   `npm run build`
5. **Audit Integritas Diff Bersih:**
   Jalankan `git diff` atau `git status` untuk memastikan tidak ada file rahasia (`.env`), file sampah, atau baris duplikat yang tertinggal.
