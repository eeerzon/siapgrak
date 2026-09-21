# Design Doc — Judges + Judging Rounds + Criteria (Phase D, Sprint 7)

**Status:** Implemented (2026-09-14) — 34 test hijau di MariaDB (total 233), Pint clean, migrasi up/down bersih.
**Author:** Claude
**Tanggal:** 2026-09-14
**Kontrak:** `BACKEND-HANDOFF.md` §Sprint 7-10 (Judging) — sprint ini scope `judges`+`judging_rounds`+`judging_criteria` saja (`tasks.md` breakdown)
**Definition of Ready:** ✅ — Sprint 1-6 selesai (199 test). **Keputusan Desain Kritis di bawah dikonfirmasi Ersa 2026-09-14** sebelum coding.

---

> ⚠️ **Phase D = domain paling sensitif di seluruh project** (Constitution `CLAUDE.md`): "Judge tidak boleh melihat skor/ranking juri lain selama judging berjalan — kebocoran adalah insiden bisnis, bukan sekadar bug." Sprint 7 belum menyentuh scoring (itu Sprint 9-10), tapi fondasi tenant isolation untuk `judges` diputuskan di sini — kalau salah sekarang, semua sprint Judging berikutnya mewarisi kesalahannya.

## TL;DR

`judges` (master data reusable per organisasi, mirip `teams`), `judging_rounds` (per kategori, biasanya 1 round v1), `judging_criteria` (per round, max 2 level nested — root + subcriteria). **Belum ada** judge assignment (Sprint 8) atau scoring (Sprint 9-10) — sprint ini cuma bangun "rubrik" penilaian + direktori juri organisasi.

**Requirement ID:** `FR-JUDGING-01`

---

## Keputusan Desain Kritis

### A. `judges.organization_id` — gap tenant isolation di blueprint, DITAMBAHKAN

**Masalah:** `research/chatgpt-blueprint-v1.md` §9 mendefinisikan `judges` **tanpa** `organization_id`/`event_id` sama sekali (`id, public_id, user_id?, name, photo_path?, bio?, status`). Tapi Constitution eksplisit: tenant isolation wajib di **setiap** resource yang (seharusnya) py `organization_id`. Tanpa kolom itu, organizer A bisa lihat/edit data juri organizer B — persis kelas masalah yang sama dengan `teams.organization_id` di Sprint 2.

**Opsi yang diajukan ke Ersa (2026-09-14):**
- **A (dipilih):** Tambah `organization_id` — nullable di schema (skema tetap kompatibel arah blueprint jangka panjang kalau nanti judge memang dibikin bisa lintas-organisasi), **wajib diisi di validasi** saat create (pola identik `teams` Sprint 2 — lihat `fr-team-directory.md` §Keputusan Desain Kritis, ini kasus yang sama persis). Judge jadi master data reusable milik satu organisasi, dikelola di halaman `/dashboard/judges` sendiri.
- B: Biarkan global tanpa `organization_id` — ikuti blueprint literal, isolasi cuma di level `judge_assignments` (Sprint 8).

**Keputusan: A.** Alasan: mencegah kebocoran data personal juri (nama/foto/bio) antar-tenant sebelum assignment pun dibuat; konsisten dengan pola Team; tidak menutup kemungkinan "juri lintas organisasi" nanti (tinggal ubah validasi, skema sudah nullable).

### B. Halaman `/dashboard/judges` TIDAK masuk sidebar global

CLAUDE.md §Aturan Kritis mengunci sidebar ke `Dashboard/Events/Teams/Organization/Settings` — tidak boleh nambah menu tanpa diminta. Judges dikelola lewat halaman resource penuh (index/create/show/edit, pola sama Teams) tapi **hanya reachable lewat link "Kelola Juri Organisasi →" di tab Penjurian pada Event Workspace** — bukan item sidebar baru. Route tetap ada dan bisa diakses langsung by URL.

### C. Round & Criteria dikelola inline di tab baru "Penjurian" pada `events/show`

Konsisten pola Kategori/Kompetisi/Jadwal — bukan halaman terpisah. Per kategori: daftar round (biasanya 1) + form tambah; per round: tree criteria (root + subcriteria, indent visual) + form tambah (dengan pilihan "sub dari" dibatasi ke root criteria round yang sama).

### D. Max nested depth = 2 — ditegakkan di service, bukan DB constraint

`parent_id` (kalau diisi) harus menunjuk criteria yang **tidak punya parent sendiri** (root). Mencegah subcriteria-dari-subcriteria. Dicek di `JudgingService`, bukan CHECK constraint SQL (MySQL/MariaDB versi yang dipakai tidak konsisten dukung CHECK kompleks).

### E. Hard delete Round/Criteria diizinkan SEKARANG — akan digembok Sprint 9

Belum ada `scores` yang mereferensikan `judging_criteria_id` (itu Sprint 9). Untuk sprint ini, destroy dibiarkan bebas (pola sama Schedule, bukan pola Category-dengan-guard). **Catatan wajib untuk Sprint 9:** begitu tabel `scores` ada, tambahkan guard yang sama seperti `EventCategoryController::destroy` (Sprint 4) — tolak hapus criteria/round yang sudah punya score, jangan biarkan `QueryException` mentah atau (lebih parah) hapus skor secara diam-diam.

### F. Weight tidak divalidasi harus total 100%

`judging_rounds.weight` dan `judging_criteria.weight` disimpan (`DECIMAL(5,2)`, bukan FLOAT), dipakai `ResultCalculator` nanti (Sprint 11). Validasi "total weight round harus 100%" **ditunda** — perhitungan akhir ada di Sprint 11, terlalu dini mengunci aturan itu sekarang.

### G. Otorisasi

- **Judge**: `JudgePolicy` pola identik `TeamPolicy` — `viewAny`/`create` true (org divalidasi di request), `view` = member organisasi judge, `update` = owner/admin. Tidak ada `destroy` (tidak ada route).
- **Round/Criteria**: `EventPolicy::manageJudging($user, $event)` (owner/admin) — event diturunkan dari `round->category->event` / `criteria->round->category->event`. Tidak ada policy terpisah, konsisten Sprint 3-6.

---

## Perubahan Schema

### `judges` (migration 16)
```text
id                BIGINT UNSIGNED, PK
public_id         string, unique                      (HasPublicId, prefix "judge_")
organization_id   FK → organizations.id, nullable, RESTRICT     (Keputusan A)
user_id           FK → users.id, nullable, SET NULL           (judge boleh belum py akun)
name              string
photo_path        string, nullable                     (kolom ada, upload ditunda — pola poster/logo)
bio               text, nullable
status            string (enum app: Status), default 'active'
timestamps
```
Index: `unique(public_id)`, `index(organization_id, status)`.

### `judging_rounds` (migration 17)
```text
id                  BIGINT UNSIGNED, PK
event_category_id   FK → event_categories.id, CASCADE    (child config kategori, pola event_categories/staff)
name                string
code                string, nullable
weight              DECIMAL(5,2), nullable
status              string (enum app: Status), default 'active'
display_order       unsigned int, default 0
timestamps
```
Index: `index(event_category_id, display_order)`.

### `judging_criteria` (migration 18)
```text
id                  BIGINT UNSIGNED, PK
judging_round_id    FK → judging_rounds.id, CASCADE
parent_id           FK → judging_criteria.id, nullable, RESTRICT   (self-referential, max depth 2 — Keputusan D)
name                string
description         text, nullable
weight              DECIMAL(5,2), nullable
minimum_score       DECIMAL(10,3)                        (never FLOAT — Constitution)
maximum_score       DECIMAL(10,3)
display_order       unsigned int, default 0
status              string (enum app: Status), default 'active'
timestamps
```
Index: `index(judging_round_id, display_order)`, `index(parent_id)`.

---

## Business Logic
```text
RULE-1  organization_id wajib diisi saat create judge, harus organisasi tempat user member aktif.
RULE-2  judging_round selalu di bawah event_category dari route (CASCADE).
RULE-3  judging_criteria.parent_id (kalau diisi): harus judging_round yang SAMA, dan parent itu sendiri
        harus root (parent_id-nya NULL) — depth cap 2 (Keputusan D).
RULE-4  maximum_score >= minimum_score.
RULE-5  Judge tidak bisa hard delete (tidak ada route). Round/Criteria BOLEH hard delete sekarang
        (Keputusan E) — WAJIB digembok begitu Sprint 9 (scores) ada.
```

## Permission

| Aksi | owner/admin | member | non-member |
|---|---|---|---|
| Lihat judge / round / criteria | ✅ | ✅ | ❌ 403 |
| CRUD judge (di org sendiri) | ✅ | ❌ (create/update) — viewAny/create true tapi org divalidasi | — | — |
| CRUD round / criteria | ✅ | ❌ | ❌ |

## Testing Plan
```text
Judge/ManageJudgeTest        — create (org wajib & tervalidasi), update, member tak bisa mutasi, tenant isolation
JudgingRound/ManageRoundTest — create di bawah category, update, delete, tenant isolation
JudgingCriteria/ManageCriteriaTest
  □ create root criteria; create subcriteria (parent = root) OK
  □ subcriteria-dari-subcriteria (parent punya parent) DITOLAK — depth cap
  □ parent dari round lain DITOLAK
  □ maximum_score < minimum_score DITOLAK
  □ delete criteria (masih bebas, Keputusan E)
  □ tenant isolation + member role forbidden
```

## File Dibuat/Diubah
```text
database/migrations/*_create_judges_table.php (16)
database/migrations/*_create_judging_rounds_table.php (17)
database/migrations/*_create_judging_criteria_table.php (18)
app/Models/Judge.php, JudgingRound.php, JudgingCriteria.php (+relasi Organization/EventCategory)
app/Policies/JudgePolicy.php
app/Policies/EventPolicy.php               (+manageJudging)
app/Services/JudgeService.php              (create/update, assignableOrganizationIds)
app/Services/JudgingService.php            (createRound/updateRound/deleteRound, createCriteria/updateCriteria/deleteCriteria + depth-cap check)
app/Http/Requests/Judge/{Store,Update}JudgeRequest.php
app/Http/Requests/Judging/{Store,Update}JudgingRoundRequest.php
app/Http/Requests/Judging/{Store,Update}JudgingCriteriaRequest.php
app/Http/Controllers/Dashboard/JudgeController.php
app/Http/Controllers/Dashboard/JudgingRoundController.php
app/Http/Controllers/Dashboard/JudgingCriteriaController.php
routes/web.php  (+resource judges except destroy; +rounds/criteria nested routes)
database/factories/JudgeFactory.php, JudgingRoundFactory.php, JudgingCriteriaFactory.php
resources/views/dashboard/judges/{index,create,show,edit}.blade.php   (pola Teams)
resources/views/dashboard/events/show.blade.php    (+tab "Penjurian")
resources/views/dashboard/events/partials/judging.blade.php
tests/Feature/Judge/*, JudgingRound/*, JudgingCriteria/*
```

## Catatan Implementasi

- **Depth-cap (Keputusan D) sepenuhnya via `Rule::exists()->whereNull('parent_id')`** — satu baris validasi menegakkan DUA syarat sekaligus (parent harus di round yang sama DAN parent harus root), jadi `JudgingService` tidak perlu logic depth-check terpisah. Diverifikasi via test + manual: submit subcriteria-dari-subcriteria (`parent_id` = criteria yang sudah punya parent) ditolak dengan pesan jelas.
- **`gte:minimum_score` dipakai langsung** (bukan `withValidator` custom kayak Sprint 3 category) — karena `minimum_score`/`maximum_score` sama-sama **required** di sini (beda dari category yang `min_members`/`max_members` opsional), jadi tidak ada masalah "field pembanding belum tentu ada".
- Sidebar **dikonfirmasi tidak menampilkan** "Juri" (Keputusan B) — dicek manual di rendered HTML, bukan cuma asumsi dari kode.
- Verifikasi manual HTTP langsung (bukan cuma test suite): approve tidak relevan di sini, tapi **percobaan bikin subcriteria-dari-subcriteria via curl nyata** dicoba dan ditolak dengan pesan yang benar — konsisten dengan hasil test otomatis.
