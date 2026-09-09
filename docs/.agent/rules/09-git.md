# Rules 09 - Git Workflow

## Branch Naming Convention

Format: {type}/{deskripsi-singkat-kebab-case}

| Type | Kapan Dipakai | Contoh |
|------|---------------|--------|
| feature | Fitur baru | feature/invitation-system |
| fix | Bug fix | fix/commission-tiering |
| chore | Konfigurasi, dependency | chore/setup-inertia |
| docs | Dokumentasi | docs/srs-update-v1-3 |
| refactor | Refactor tanpa fitur baru | refactor/profit-service |
| test | Menambah test | test/commission-calc |

DILARANG: my-changes, fix-bug, new-feature, update

---

## Conventional Commits

Format: {type}({scope}): {deskripsi singkat}

Scope: investor, admin, affiliator, auth, commission, profit, invitation, copytrade

Contoh:
- feat(invitation): tambah generate invitation link
- feat(copytrade): implementasi stepper 3 langkah
- fix(commission): perbaiki kalkulasi tiering modal
- chore(deps): install inertia-laravel dan ziggy
- docs(srs): tambah modul kalkulator proyeksi
- refactor(profit): extract ProfitService dari controller
- test(commission): tambah test tiering premium

DILARANG: fix bug, update, changes, wip

---

## Sebelum Commit - Checklist Wajib

1. Format PHP: vendor/bin/pint --dirty
2. Cek debug statement: grep -rn "dd(" app/
3. Jalankan test relevan: php artisan test --compact --filter=NamaTest
4. Update status: git fetch origin && git status

---

## Aturan Branch dan Merge

- Jangan push langsung ke main
- Satu branch = satu fitur atau satu bug fix
- Hapus branch setelah di-merge

---

## .gitignore - File yang Dilarang Commit

- .env dan .env.*
- storage/logs/
- vendor/
- node_modules/
- public/hot
- file *.log

