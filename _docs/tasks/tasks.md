# Tasks — Siapgrak

Intake SEMUA request (bug, fitur, apapun). Diedit manual oleh Ersa saat brief baru masuk, dan oleh Claude saat status kerja berubah.

**Status project:** Riset & desain selesai (3 dokumen ChatGPT + `_docs` baseline v5 ini). **Belum ada satu baris kode pun.** Belum `git init`.

## Blocking Dependency (harus diselesaikan sebelum Phase 6)

- [ ] **Konfirmasi kontrak API Kreenconnect ke tim terkait** — kontrak endpoint di `research/chatgpt-blueprint-v1.md` §69-76 masih proposal ChatGPT, belum diverifikasi terhadap implementasi nyata Kreenconnect. Lihat ADR-001 dan `architecture/integration.md`.
- [ ] **Putuskan strategi auth**: pakai SSO Kreen Connect existing, atau Laravel auth mandiri? (Open Question di `srs/fr-organization-tenancy.md`)
- [ ] **Putuskan platform hosting** — mempengaruhi `operations/deployment.md` yang masih placeholder

## Roadmap Sprint (dari `research/chatgpt-dev-checklist-migration-routes.md`)

**Aturan wajib:** setiap sprint dikerjakan sendiri-sendiri, tidak digabung. Instruksi coding harus eksplisit menyebut apa yang TIDAK diimplementasikan di sprint itu. Lihat `quality/definition-of-ready.md` sebelum memulai sprint manapun, dan Phase Gate Rule di bawah.

### Phase 0 — Project Foundation
- [ ] Sprint 0: Laravel 12 init, PHP 8.3, MySQL config, Auth (login/logout/forgot-password), base layout (Public/Dashboard/Auth), `_docs/` structure (✅ sudah ada per dokumen ini)

### Phase 1 — Organization + Team + Event
- [ ] **Sprint 1: Organization + tenancy + policy ONLY** — SRS: `srs/fr-organization-tenancy.md` (sudah ditulis, siap dieksekusi). **Jangan implement team/event/registration/judging/ticketing/voting.**
- [ ] Sprint 2: Team, Team Members, Team Public Page — SRS belum ditulis, buat dulu sebelum mulai (lihat CLAUDE.md §Wajib Sebelum Coding Fitur)
- [ ] Sprint 3: Event, Event Categories, Public Event Page — SRS belum ditulis
- [ ] **Milestone: Phase 1 complete** — organizer bisa create org → create event → publish; public bisa lihat event listing

### Phase 2 — Competition Registration
- [ ] Sprint 4: Registration Draft, Registration Members
- [ ] Sprint 5: Documents, Submit, Organizer Review
- [ ] **Milestone: Phase 2 complete** — Siapgrak gantikan Google Form pendaftaran

### Phase 3 — Competition Operations
- [ ] Sprint 6: Competition Entry, Competition Number
- [ ] Sprint 7: Schedule, Check-in, Event Status
- [ ] **Milestone: Phase 3 complete** — panitia kelola operasional lomba tanpa spreadsheet

### Phase 4 — Judging
- [ ] Sprint 8: Judge, Round, Criteria
- [ ] Sprint 9: Assignment, Score Sheet, Save Draft
- [ ] Sprint 10: Submit, Lock, Revision, Audit
- [ ] **Milestone: Phase 4 complete** — full digital scoring, judge isolation tegak

### Phase 5 — Results + Public Transparency
- [ ] Sprint 11: Result Calculator, Ranking
- [ ] Sprint 12: Awards, Public Result, Publish
- [ ] **Milestone: Phase 5 complete — MVP cut line.** Siapgrak core sudah viable tanpa Kreenconnect (lihat `research/chatgpt-dev-checklist-migration-routes.md` §121)

### Phase 6 — Kreenconnect Integration
- [ ] ⚠️ **Blocked sampai kontrak API dikonfirmasi** (lihat Blocking Dependency di atas)
- [ ] Sprint 13: Kreenconnect Client, Ticketing Integration
- [ ] Sprint 14: Voting Integration, Candidate Mapping, Checkout
- [ ] **Milestone: Phase 6 complete**

### Phase 7 — Team History + Ecosystem
- [ ] Belum dipecah jadi sprint detail — lihat `research/chatgpt-dev-checklist-migration-routes.md` §88-92 untuk scope (team/organization profile enhancement, discovery filter, search)

### Phase 8 — Event Command Center
- [ ] Belum dipecah jadi sprint detail — **jangan dibangun sebelum Phase 1-6 stabil** (lihat `research/chatgpt-dev-checklist-migration-routes.md` §93-95)

## Phase Gate Rule

Tidak boleh lanjut ke sprint/phase berikutnya kalau:
- Test failing
- Migration rollback (`down()`) gagal
- Tenant isolation belum ditest
- Status transition (registration/entry/score sheet/result) belum jelas
- Existing flow yang sudah jalan jadi rusak

Setiap phase harus independently deployable — lihat `research/chatgpt-dev-checklist-migration-routes.md` §119.
