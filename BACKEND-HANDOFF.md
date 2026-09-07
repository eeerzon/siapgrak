# Siapgrak — Backend Ownership (Handoff ke Tim Dev)

**Dibuat:** 2026-09-07. Pembagian kerja mulai sekarang: **frontend/UI dikerjakan terpisah** (lihat `_docs/frontend/frontend-plan.md`), **tim dev fokus penuh ke backend** dari Sprint 3 dan seterusnya.

---

## Baca Dulu Sebelum Mulai

1. `CLAUDE.md` — instruksi kerja, Constitution, aturan kritis project
2. `_docs/brd/brd-core.md` — kenapa Siapgrak dibangun
3. `_docs/decisions/adr-001-siapgrak-kreenconnect-boundary.md` — kenapa TIDAK ada ticketing/voting native
4. `_docs/decisions/adr-002-admin-first-mvp.md` — kenapa MVP admin-only, publik DEFERRED
5. `_docs/tasks/tasks.md` — roadmap sprint, sprint mana yang aktif
6. `_docs/srs/fr-organization-tenancy.md` dan `_docs/srs/fr-team-directory.md` — contoh pola SRS yang sudah dipakai Sprint 1-2, ikuti pola yang sama untuk Sprint 3+
7. Kode Sprint 1-2 yang sudah ada (`app/Models/Organization.php`, `app/Policies/OrganizationPolicy.php`, `app/Services/OrganizationService.php`, dst) — **pola tenant isolation di situ WAJIB diikuti persis** untuk Event/Team/Registration/dst, jangan reinvent.

---

## Yang Jadi Tanggung Jawab Tim Dev

```text
- Database schema & migrations
- Model & relationship
- Business rules / domain logic
- Services (business logic, bukan di Controller)
- Policies (tenant isolation — WAJIB di setiap resource baru)
- Validation (Form Request)
- Status transitions / lifecycle
- Tests (setiap sprint minimal ada tenant isolation test + business rule test)
- Backend data contract (shape data ke view — koordinasi dengan frontend lewat `_docs/frontend/frontend-plan.md` §Backend Data Contract)
- Kreenconnect integration (nanti, Phase DEFERRED)
```

## Yang BUKAN Tanggung Jawab Tim Dev (Sprint 3-13)

```text
❌ JANGAN redesign frontend/UI yang sudah ada
❌ JANGAN membuat halaman publik apapun (public event page, public team page, dst)
❌ JANGAN implementasi voting/ticketing/payment — itu domain Kreenconnect (ADR-001), selamanya tidak dibangun native di sini
❌ JANGAN participant self-registration — mode Sprint 4+ adalah admin-entry (panitia input), bukan peserta daftar sendiri
❌ JANGAN generic-kan skema judging dengan project `juri` (pageant) — pola teknis boleh mirip, skema tetap terpisah (ADR-001)
```

---

## Roadmap Backend — Mulai dari Sprint 3

Status: Sprint 1 (Organization/Tenancy) dan Sprint 2 (Team/School Directory) **SELESAI** — 74 test hijau, tenant isolation teruji (termasuk manipulasi URL/payload/route-model-binding). Lanjut dari sini.

### Sprint 3 — Event + Competition Categories
Domain: `Organization → Event → Competition Categories`
```text
events, event_categories, event_staff
```
Contoh: `LKBB Garuda 2026` punya categories `SMP / SMA / Madya / Utama`.

Tanggung jawab: create/update event, status lifecycle event, date validation, organization ownership (pola sama Sprint 1-2), category CRUD, quota, min/max members, registration dates, category ordering, Policy, tests.

**Belum ada SRS tertulis** — buat `_docs/srs/fr-event-category.md` dulu sebelum coding (pola sama `fr-team-directory.md`), termasuk keputusan desain kalau ada gap serupa Sprint 2 (jangan diam-diam nambah kolom otorisasi baru — stop dan diskusikan dulu kalau nemu).

### Sprint 4 — Participant / Registration (Admin-Entry Mode)
```text
registrations, registration_members, registration_documents
```
**Flow admin-first** (bukan self-registration):
```text
Admin → Add Participant → Select/Create Team → Select Category → Add Roster → Upload Documents → Save → Approve/Mark Complete
```
Field `submitted_by` harus didesain kompatibel dengan self-registration nanti tanpa migrasi skema besar (lihat catatan ADR-002).

### Sprint 5 — Competition Entry + Number
```text
competition_entries
```
Approved registration → competition entry otomatis, competition number, performance order, status lifecycle.

### Sprint 6 — Schedule
```text
event_schedule_slots
```
Slot kompetisi, break, ceremony, technical meeting, venue area, ordering.

### Sprint 7-10 — Judging
```text
judges, judging_rounds, judging_criteria, judge_assignments, score_sheets, scores, score_sheet_revisions
```
**Business rule paling sensitif di seluruh project** — judge isolation (tidak boleh lihat skor juri lain), score sheet lifecycle (draft/submitted/locked dengan audit trail reopen), lihat Constitution di `CLAUDE.md`. Frontend judge scoring UI didesain terpisah (lihat frontend-plan.md F10) — backend cukup sediakan data contract-nya.

### Sprint 11-12 — Results
```text
competition_results, awards
```
Services: `ResultCalculator`, `ResultService`. **Frontend tidak pernah menghitung skor resmi sendiri** — semua kalkulasi ranking harus lewat backend service ini.

### Sprint 13 — Operational Polish
Audit log, query optimization, status validation, integrity checks, final permissions, edge case operasional.

---

## Aturan Kerja (sama seperti Sprint 1-2, jangan dilonggarkan)

- Satu sprint dikerjakan sendiri-sendiri, tidak digabung — lihat Phase Gate Rule di `_docs/tasks/tasks.md`
- SRS wajib ditulis sebelum coding fitur baru (`_docs/srs/fr-*.md`)
- Tenant isolation test wajib untuk setiap resource baru (pola: direct URL, edit form, manipulated payload, route model binding — lihat `tests/Feature/Team/TenantIsolationTest.php` sebagai contoh)
- `_docs/tasks/tasks.md` diupdate begitu sprint selesai
- Migration per-sprint, jangan bikin tabel dari sprint yang belum waktunya
- Kalau nemu design gap (ownership/authorization yang tidak jelas) — STOP, laporkan pilihan desainnya, jangan diam-diam nambah kolom/tabel otorisasi baru (lihat contoh kasus di `_docs/srs/fr-team-directory.md` §"Keputusan Desain Kritis" — itu polanya)
