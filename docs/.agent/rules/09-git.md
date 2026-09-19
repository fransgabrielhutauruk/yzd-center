# Rules 09 — Git Workflow

## Alur Kerja Git (Solo Developer)

Karena proyek dikembangkan oleh **solo developer (Frans)**, alur Git disederhanakan menggunakan pendekatan **Trunk-Based Development**:

- **Default:** JANGAN commit maupun push otomatis ke branch `main`. Selalu tunggu persetujuan eksplisit (*approval*) dari USER sebelum menjalankan `git commit` maupun `git push origin main`.
- **Branching bersifat OPSIONAL:** Hanya dibuat saat mengerjakan eksperimen besar, riset, atau fitur berisiko tinggi yang belum tentu digabungkan ke `main`.

---

## Branch Naming Convention (Jika Membuat Branch Eksperimen)

Format: `{type}/{deskripsi-singkat-kebab-case}`

| Type | Kapan Dipakai | Contoh |
|------|---------------|--------|
| `feature` | Fitur baru / eksperimen | `feature/invitation-system` |
| `fix` | Perbaikan bug kompleks | `fix/commission-tiering` |
| `chore` | Konfigurasi, dependency | `chore/setup-inertia` |
| `docs` | Dokumentasi besar | `docs/srs-update-v1-3` |
| `refactor` | Refaktor arsitektur | `refactor/profit-service` |
| `test` | Penambahan test suite | `test/commission-calc` |

---

## Conventional Commits (Wajib)

Setiap commit ke `main` **wajib** menggunakan format Conventional Commits:

Format: `{type}({scope}): {deskripsi singkat}` atau `{type}: {deskripsi singkat}`

**Scope yang umum:** `auth`, `admin`, `investor`, `affiliator`, `commission`, `profit`, `invitation`, `copytrade`, `deps`

**Contoh:**
- `feat(invitation): tambah generate invitation link`
- `feat(copytrade): implementasi stepper 3 langkah`
- `fix(commission): perbaiki kalkulasi tiering modal`
- `chore(deps): install inertia-laravel dan ziggy`
- `docs(srs): tambah modul kalkulator proyeksi`
- `refactor(profit): extract ProfitService dari controller`
- `test(commission): tambah test tiering premium`

**DILARANG:** `fix bug`, `update`, `changes`, `wip`, `asdf`

---

## Sebelum Commit & Push ke `main` — Checklist Wajib

Hanya jalankan setelah ada approval eksplisit dari USER. Sebelum melakukan `git commit` dan `git push origin main`:

1. **Format PHP:** `vendor/bin/pint --dirty` (atau `vendor/bin/pint app database`)
2. **Cek debug statement:** Pastikan tidak ada `dd(`, `dump(`, atau `console.log(` tersisa
3. **Jalankan test:** `php artisan test --compact`
4. **Verifikasi build frontend:** `npm run build`
5. **Cek status:** `git status` (pastikan tidak ada file rahasia/untracked yang bocor)

---

## .gitignore — File yang Dilarang Commit

- `.env` dan `.env.*`
- `storage/logs/`
- `vendor/`
- `node_modules/`
- `public/build/`
- `public/hot`
- file `*.log`

