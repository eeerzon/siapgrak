# Design Doc — Judge Assignment (Phase D, Sprint 8)

**Status:** Implemented (2026-09-14) — 14/14 test hijau (11 ManageAssignmentTest + 3 TenantIsolationTest), verifikasi manual via curl selesai
**Author:** Claude
**Tanggal:** 2026-09-14
**Kontrak:** `BACKEND-HANDOFF.md` §Sprint 7-10 (Judging) · `tasks.md` breakdown: Sprint 8 = Judge Assignment
**Definition of Ready:** ✅ — Sprint 7 selesai (233 test). Masih Phase D — hati-hati, tapi Sprint 8 sendiri belum menyentuh scoring (itu Sprint 9-10).

## TL;DR

`judge_assignments` — menugaskan judge (dari `judges` Sprint 7) ke sebuah `judging_round`, entah untuk **seluruh round** (`judging_criteria_id = NULL`) atau **kriteria spesifik** (mis. Judge A cuma menilai "PBB"). Ini murni "peta siapa-menilai-apa" — belum ada scoring, belum ada score sheet.

**Requirement ID:** `FR-JUDGE-ASSIGN-01`

## Keputusan Desain

**A. `event_category_id` di-OMIT dari tabel** — blueprint asli mencantumkannya, tapi itu 100% derivable dari `judging_round.event_category_id` (satu round selalu satu kategori). Menyimpan dua sumber kebenaran yang sama berisiko desync. Diturunkan via relasi, bukan kolom.

**B. Bug NULL-UNIQUE dari blueprint — ditangani eksplisit.** MySQL/MariaDB `UNIQUE` menganggap `NULL != NULL`, jadi `UNIQUE(judging_round_id, judge_id, judging_criteria_id)` **tidak mencegah** judge yang sama dapat 2 assignment "seluruh round" (`criteria_id=NULL`) di round yang sama — persis peringatan yang sudah ditulis di blueprint. **Ditegakkan di app-level** (Form Request, bukan DB): untuk pasangan (round, judge) — kalau assignment baru "seluruh round", tolak kalau judge itu SUDAH punya assignment apapun di round itu; kalau assignment baru "kriteria spesifik", tolak kalau judge itu sudah punya assignment "seluruh round" di round itu (kontradiktif/redundan).

**C. Judge harus dari organisasi event ini** — konsisten Sprint 7 (judge = master data per-organisasi). Judge lintas-organisasi (organizer B pinjam juri organizer A) **belum didukung** — perlu mekanisme "invite judge" tersendiri kalau nanti dibutuhkan, sengaja tidak dibangun sekarang.

**D. `judging_criteria_id` → CASCADE on delete (bukan SET NULL)** — kalau kriteria yang di-assign dihapus, assignment untuk kriteria itu ikut hilang. **SET NULL secara sengaja dihindari** — itu akan diam-diam mengubah makna assignment dari "menilai kriteria X" jadi "menilai seluruh round", sebuah perubahan semantik yang jelas salah kalau terjadi tanpa disadari.

**E. Hard delete diizinkan** — tidak ada FK dari `scores`/`score_sheets` ke `judge_assignments` (blueprint: score_sheets referensi `judging_round_id`+`competition_entry_id`+`judge_id` langsung, bukan `judge_assignment_id`). Assign/lepas judge adalah penyesuaian operasional wajar sebelum/selama setup.

**F. Tidak ada endpoint update** — field yang "bisa diubah" cuma `status`, belum ada kebutuhan nyata untuk itu sekarang (beda dari Round/Criteria yang punya banyak field editable bermakna). Ganti scope assignment = hapus + assign ulang. Lean, sesuai Karpathy #2.

**G. Otorisasi**: reuse `EventPolicy::manageJudging` (owner/admin) — assignment adalah bagian dari domain "Penjurian" yang sama, tidak perlu policy/permission baru.

## Perubahan Schema

### `judge_assignments` (migration 19)
```text
id                   BIGINT UNSIGNED, PK
judging_round_id     FK → judging_rounds.id, CASCADE
judge_id             FK → judges.id, RESTRICT
judging_criteria_id  FK → judging_criteria.id, nullable, CASCADE   (Keputusan D)
status               string (enum app: Status), default 'active'
timestamps
```
Index: `unique(judging_round_id, judge_id, judging_criteria_id)` (defense-in-depth untuk kasus non-NULL — lihat Keputusan B untuk kenapa ini tidak cukup sendirian), `index(judge_id)`.

## Business Logic
```text
RULE-1  judge_id harus judge milik organisasi event ini (Keputusan C).
RULE-2  judging_criteria_id (kalau diisi) harus milik judging_round yang sama.
RULE-3  Satu judge per round: HANYA salah satu — "seluruh round" (1x, criteria NULL)
        ATAU N assignment kriteria spesifik (tidak campur). Ditegakkan di Form Request
        (Keputusan B), bukan cuma DB unique.
RULE-4  Hard delete diizinkan (Keputusan E).
```

## Permission
| Aksi | owner/admin | member | non-member |
|---|---|---|---|
| Lihat assignment | ✅ | ✅ | ❌ |
| Assign / hapus | ✅ | ❌ | ❌ |

## Testing Plan
```text
JudgeAssignment/ManageAssignmentTest
  □ assign judge ke seluruh round (criteria_id null)
  □ assign judge ke kriteria spesifik
  □ judge yang sama assign "seluruh round" 2x di round sama → ditolak (NULL-unique gap, Keputusan B)
  □ judge sudah "seluruh round" lalu di-assign ke kriteria spesifik → ditolak
  □ judge sudah assign ke kriteria spesifik lalu coba "seluruh round" → ditolak
  □ judge assign ke 2 kriteria berbeda di round sama → boleh
  □ judge dari organisasi lain → ditolak
  □ kriteria dari round lain → ditolak
  □ hapus assignment → berhasil (hard delete)
  □ hapus kriteria yang di-assign → assignment ikut CASCADE hilang
  □ member role forbidden

JudgeAssignment/TenantIsolationTest — pola sama sprint sebelumnya
```

## Catatan Implementasi

**Bug migrasi: nama index unique auto-generate melebihi batas 64 karakter MySQL.** Nama default Laravel untuk unique constraint 3-kolom ini (`judge_assignments_judging_round_id_judge_id_judging_criteria_id_unique`) adalah 73 karakter — melebihi limit identifier MySQL/MariaDB (64 char), menyebabkan `SQLSTATE[42000]: ... Identifier name ... is too long`. Efek sampingnya membingungkan: `CREATE TABLE` sempat berhasil (tabel fisik ada) sebelum `ALTER TABLE` unique index-nya gagal, tapi migrasi tidak tercatat "Ran" di tabel `migrations` — jadi retry berikutnya selalu gagal dengan pesan "table already exists" yang menyesatkan (seolah tabel bentrok, padahal migrasi sebelumnya gagal separuh jalan). Solusi: beri nama eksplisit pendek pada constraint (`'judge_assignments_unique'`). Diverifikasi ulang via migrate → rollback → migrate, semua bersih. **Pelajaran:** kalau error migrasi terkesan tidak konsisten dengan state DB yang teramati, selalu ambil output error lengkap (jangan di-`tail`/potong) — di sini output terpotong sempat menyembunyikan pesan error sebenarnya selama beberapa percobaan debug.

Verifikasi manual (curl, live server) mengonfirmasi seluruh alur: assign seluruh-round, assign kriteria spesifik, penolakan duplikat (Keputusan B), penolakan judge lintas-organisasi, render "Juri Ditugaskan" di tab Penjurian, dan hapus ("Lepas") — semua sesuai automated test.

## File Dibuat/Diubah
```text
database/migrations/*_create_judge_assignments_table.php (19)
app/Models/JudgeAssignment.php (+relasi di JudgingRound/JudgingCriteria/Judge)
app/Services/JudgingService.php     (+createAssignment dengan RULE-2/3, +deleteAssignment)
app/Http/Requests/Judging/StoreJudgeAssignmentRequest.php
app/Http/Controllers/Dashboard/JudgeAssignmentController.php  (store, destroy)
routes/web.php  (+judging-rounds/{round}/assignments, judge-assignments/{assignment})
database/factories/JudgeAssignmentFactory.php
resources/views/dashboard/events/partials/judging.blade.php  (+seksi "Juri Ditugaskan" per round)
tests/Feature/JudgeAssignment/*
```
