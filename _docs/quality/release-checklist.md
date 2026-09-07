# Release Checklist — Siapgrak

**Status:** belum pernah dipakai (belum ada deploy). Checklist ini disiapkan sebelum deploy pertama, akan diperbarui begitu deploy pertama benar-benar terjadi dan ada pelajaran nyata.

## Pre-Release

- [ ] Working tree bersih (`git status` tidak ada perubahan tak sengaja ter-commit)
- [ ] Semua test hijau (lihat `quality/test-strategy.md`)
- [ ] Tidak ada secret ter-commit (`.env` tidak masuk git, credential Kreenconnect tidak hardcoded — lihat `security/security-baseline.md`)
- [ ] Migration baru sudah di-review: backward-compatible (kalau menambah kolom NOT NULL ke tabel yang sudah berisi data, ada default value atau strategi backfill)
- [ ] Migration sesuai urutan nomor di `research/chatgpt-dev-checklist-migration-routes.md` §Full Migration Order — tidak ada migration dari sprint yang belum seharusnya dikerjakan

## Deploy

- [ ] Urutan migration dijalankan (`php artisan migrate`)
- [ ] Cache di-clear (`php artisan config:clear`, `cache:clear`, `view:clear` sesuai kebutuhan)
- [ ] Scheduler/cron terdampak dicek (khusus setelah Phase 6: sync Kreenconnect summary jobs — lihat `research/chatgpt-dev-checklist-migration-routes.md` §Scheduler)
- [ ] Queue worker di-restart kalau ada perubahan Job class

## Post-Deploy

- [ ] Health endpoint / homepage accessible
- [ ] Login berhasil (organizer + judge area terpisah dicek keduanya)
- [ ] Alur kritikal untuk phase yang baru dideploy berhasil (contoh Phase 1: create event; Phase 4: judge submit score sheet)
- [ ] Error rate normal (belum ada dashboard observability — cek log aplikasi manual untuk sementara, lihat `srs/nfr.md` §Observability)

## Rollback Decision

- [ ] Metric/error apa yang trigger rollback: error rate tinggi di alur kritikal (registration submit, score sheet submit, result publish), atau data corruption terdeteksi
- [ ] Apakah code bisa rollback independen dari DB: **ya** untuk perubahan yang tidak mengubah schema; **perlu hati-hati** untuk migration yang mengubah kolom existing — lihat `operations/rollback.md`
- [ ] Apakah migration reversible: dicek per-migration saat `down()` ditulis (lihat `quality/definition-of-done.md`)

Detail generik per-platform (kalau nanti pakai Vercel/Forge/dst) ada di referensi `_meta/ENGINEERING-FLOW.md` Fase 7-8 — file ini adalah versi project-specific yang akan diisi konkret begitu platform hosting Siapgrak diputuskan (belum diputuskan per 2026-09-07).
