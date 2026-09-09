# Penyederhanaan Alur Git Workflow untuk Solo Developer

**Tanggal:** 2026-09-09  
**Konteks/Pertanyaan:** Pengembang bekerja sebagai solo developer (Frans) dan mempertimbangkan untuk menghapus aturan branching wajib agar bisa langsung melakukan commit dan push ke branch `main`.

## Saran/Rekomendasi
1. Mengubah alur Git dari Feature-Branching wajib menjadi **Trunk-Based Development** dengan push langsung ke branch `main`.
2. Pembuatan branch baru bersifat **opsional**, hanya disarankan ketika melakukan eksperimen berisiko tinggi atau riset teknis yang butuh beberapa hari.
3. Tetap mempertahankan disiplin **Conventional Commits** (`feat(...)`, `fix(...)`, `chore(...)`, dll.) serta menjalankan checklist pre-commit (Laravel Pint, PHPUnit, dan build frontend) sebelum push.

## Alasan
- **Eliminasi Process Overhead:** Pada solo development, alur branch -> PR -> merge -> delete branch menciptakan birokrasi tanpa nilai kolaborasi (karena di-review dan di-merge oleh orang yang sama).
- **Kecepatan Iterasi:** Pengembang dapat bergerak cepat menyelesaikan backlog fitur MVP.
- **Bebas Konflik:** Tidak ada risiko konflik penggabungan kode (*merge conflict*) karena hanya ada satu pengembang aktif.
- **Riwayat Git Linear:** Riwayat commit di repositori GitHub menjadi lurus, rapi, dan mudah ditelusuri.

## Alternatif yang Dipertimbangkan
- **GitFlow / Feature Branching Ketat:** Trade-off aman untuk tim besar, namun sangat lambat dan berlebihan untuk proyek solo MVP.
- **Trunk-Based Murni Tanpa Branching:** Pilihan terbaik saat ini, dengan tetap membuka opsi branch hanya untuk eksperimen besar.

## Status
- [x] Disetujui (Diterapkan ke `.agent/rules/09-git.md` pada 2026-09-09)
