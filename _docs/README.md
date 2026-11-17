# _docs — Master Index

Siapgrak — platform operasional Paskibra/LKBB. Laravel 12 / PHP 8.3, repo tunggal. Standar v5, Lapis 1 baseline. **Sprint 1-13 + 4b backend SELESAI — MVP "Siapgrak Admin v1" tuntas** — **369 test hijau di MariaDB (XAMPP)** (Phase A-E semua tuntas, termasuk CSV import). Dev lokal: `operations/deployment.md` §Local Development.

**MVP saat ini: "Siapgrak Admin v1"** (ADR-002) — sistem internal panitia, tanpa halaman publik. Public site, self-registration, dan Kreenconnect integration sengaja DEFERRED. Lihat [decisions/adr-002-admin-first-mvp.md](decisions/adr-002-admin-first-mvp.md) dan [tasks/tasks.md](tasks/tasks.md).

**Pembagian kerja (2026-09-07):** frontend/UI dan backend Sprint 3+ berjalan **paralel, terpisah** — lihat [frontend/frontend-plan.md](frontend/frontend-plan.md) (build order F0-F12, boleh mock data) dan [`BACKEND-HANDOFF.md`](../BACKEND-HANDOFF.md) (kontrak tim dev, root project).

**Terakhir diupdate:** 2026-09-07

## Navigasi Cepat

| Butuh apa? | Buka ini |
|---|---|
| "Kenapa Siapgrak dibangun, siapa penggunanya?" | [brd/brd-core.md](brd/brd-core.md) |
| "Bagaimana fitur pertama (Organization/Tenancy) harus bekerja?" | [srs/fr-organization-tenancy.md](srs/fr-organization-tenancy.md) |
| "SRS Team / Event+Category / Registration / Import CSV / Competition Entry / Schedule / Judging Setup / Judge Assignment / Scoring / Score Sheet Lifecycle / Result Calculator / Awards+Finalization / Operational Polish?" | [srs/fr-team-directory.md](srs/fr-team-directory.md) · [srs/fr-event-category.md](srs/fr-event-category.md) · [srs/fr-registration.md](srs/fr-registration.md) · [srs/fr-registration-import.md](srs/fr-registration-import.md) · [srs/fr-competition-entry.md](srs/fr-competition-entry.md) · [srs/fr-schedule.md](srs/fr-schedule.md) · [srs/fr-judging-setup.md](srs/fr-judging-setup.md) · [srs/fr-judge-assignment.md](srs/fr-judge-assignment.md) · [srs/fr-scoring.md](srs/fr-scoring.md) · [srs/fr-score-sheet-lifecycle.md](srs/fr-score-sheet-lifecycle.md) · [srs/fr-result-calculator.md](srs/fr-result-calculator.md) · [srs/fr-awards-finalization.md](srs/fr-awards-finalization.md) · [srs/fr-operational-polish.md](srs/fr-operational-polish.md) |
| "Halaman utama publik & konten admin platform?" | [srs/fr-public-content.md](srs/fr-public-content.md) · [decisions/adr-003-public-homepage-platform-content.md](decisions/adr-003-public-homepage-platform-content.md) |
| "NFR yang berlaku global?" | [srs/nfr.md](srs/nfr.md) |
| "Kenapa keputusan arsitektur ini diambil?" | [decisions/README.md](decisions/) |
| "Arsitektur sistem tingkat tinggi?" | [architecture/system-overview.md](architecture/system-overview.md) |
| "Bagaimana Siapgrak bicara ke Kreenconnect?" | [architecture/integration.md](architecture/integration.md) |
| "Code style & aturan project?" | [referensi/coding-standards.md](referensi/coding-standards.md) |
| "Kapan boleh mulai coding?" | [quality/definition-of-ready.md](quality/definition-of-ready.md) |
| "Kapan fitur dianggap selesai?" | [quality/definition-of-done.md](quality/definition-of-done.md) |
| "Test strategy?" | [quality/test-strategy.md](quality/test-strategy.md) |
| "Checklist sebelum/sesudah deploy?" | [quality/release-checklist.md](quality/release-checklist.md) |
| "Aturan keamanan?" | [security/security-baseline.md](security/security-baseline.md) |
| "Alur Info Lomba sampai hasil penjurian (panduan admin)?" | [operations/alur-info-lomba-penjurian.md](operations/alur-info-lomba-penjurian.md) |
| "Cara deploy?" | [operations/deployment.md](operations/deployment.md) |
| "Cara rollback kalau ada yang rusak?" | [operations/rollback.md](operations/rollback.md) |
| "Diagnosis+recovery incident yang pernah terjadi?" | [operations/runbook.md](operations/runbook.md) |
| "Checklist AI sebelum lapor selesai?" | [ai/review-checklist.md](ai/review-checklist.md) |
| "Apa yang perlu dikerjakan, sprint mana sekarang?" | [tasks/tasks.md](tasks/tasks.md) |
| "Jalur lengkap dari sekarang sampai MVP selesai (milestone M0-M7)?" | [status/roadmap-to-mvp.md](status/roadmap-to-mvp.md) |
| "Progress sesi terakhir, blocker, next steps?" | [status/log.md](status/log.md) |
| "Rencana & build order frontend/UI?" | [frontend/frontend-plan.md](frontend/frontend-plan.md) |
| "Kontrak kerja tim dev backend?" | [`BACKEND-HANDOFF.md`](../BACKEND-HANDOFF.md) (root project) |
| "Detail skema ~25 tabel, kontrak API Kreenconnect lengkap, route map penuh" | [research/](research/) — rujukan teknis, bukan SoT normatif (lihat catatan di bawah) |

## Tentang Folder `research/`

Berisi 4 dokumen hasil riset & diskusi dengan ChatGPT sebelum baseline `_docs` ini ditulis:

1. `research-and-design-proposal.md` — riset kompetitor Simpaskor.id + insight awal. **Bagian ticketing/voting native sudah superseded** oleh keputusan di ADR-001.
2. `chatgpt-architecture-proposal.md` — keputusan pivot: Siapgrak vs Kreenconnect boundary, insight domain (organizations/teams, event series, dst).
3. `chatgpt-blueprint-v1.md` — **skema database lengkap** (~25 tabel dengan kolom/index/FK), ERD, kontrak API Kreenconnect (endpoint+payload).
4. `chatgpt-dev-checklist-migration-routes.md` — urutan migration 01-30, route map lengkap, status matrix, sprint sequence.

**Cara pakai:** dokumen `_docs/` baseline ini (BRD, SRS, ADR, dst) adalah **source of truth normatif** — kalau ada konflik antara isi `research/` dan `_docs/brd|srs|decisions`, yang di baseline yang menang (biasanya karena sudah direview/disederhanakan). Folder `research/` tetap dipertahankan sebagai referensi detail teknis (skema kolom, payload API) yang terlalu panjang untuk diulang di tiap SRS — SRS akan me-link balik ke bagian relevan di `research/` alih-alih menyalin ulang.

## Struktur Lengkap

```
_docs/
├── README.md                          (file ini)
├── research/                           4 dokumen riset ChatGPT (lihat di atas)
├── brd/
│   └── brd-core.md
├── srs/
│   ├── fr-organization-tenancy.md      (Phase A Sprint 1)
│   ├── fr-team-directory.md            (Phase A Sprint 2)
│   ├── fr-event-category.md            (Phase A Sprint 3)
│   ├── fr-registration.md             (Phase B Sprint 4 — admin-entry)
│   ├── fr-registration-import.md      (Phase B Sprint 4b — import CSV)
│   ├── fr-competition-entry.md        (Phase C Sprint 5 — entry + nomor)
│   ├── fr-schedule.md                 (Phase C Sprint 6 — jadwal)
│   ├── fr-judging-setup.md            (Phase D Sprint 7 — judges + rounds + criteria)
│   ├── fr-judge-assignment.md          (Phase D Sprint 8 — judge assignment)
│   ├── fr-scoring.md                   (Phase D Sprint 9 — score sheet, save draft)
│   ├── fr-score-sheet-lifecycle.md     (Phase D Sprint 10 — submit/lock/reopen + audit trail)
│   ├── fr-result-calculator.md         (Phase E Sprint 11 — result calculator + ranking)
│   ├── fr-awards-finalization.md       (Phase E Sprint 12 — awards + finalize/publish/reopen)
│   ├── fr-operational-polish.md        (Sprint 13 — audit permission/pagination/N+1 + activity log retrofit)
│   └── nfr.md
├── decisions/
│   ├── README.md
│   ├── adr-001-siapgrak-kreenconnect-boundary.md
│   └── adr-002-admin-first-mvp.md      (MVP = Admin v1, public/self-registration/Kreenconnect DEFERRED)
├── architecture/
│   ├── system-overview.md
│   └── integration.md                  (Lapis 2 — dipicu integrasi Kreenconnect)
├── referensi/
│   └── coding-standards.md
├── quality/
│   ├── definition-of-ready.md
│   ├── definition-of-done.md
│   ├── test-strategy.md
│   └── release-checklist.md
├── security/
│   └── security-baseline.md
├── operations/
│   ├── deployment.md
│   ├── rollback.md
│   └── runbook.md
├── ai/
│   └── review-checklist.md
├── tasks/
│   └── tasks.md
├── status/
│   ├── log.md                           (progress per sesi, WAJIB diupdate — lihat CLAUDE.md §"Status Update")
│   └── roadmap-to-mvp.md                (sintesis: milestone M0-M7 dari kondisi sekarang sampai MVP selesai)
└── frontend/
    └── frontend-plan.md                 (build order F0-F12, terpisah dari sprint backend)

../BACKEND-HANDOFF.md                    (root project — kontrak kerja tim dev)
```

**Belum dibuat (Lapis 2, tunda sampai dibutuhkan):** `database/erd.md` (untuk sekarang cukup rujuk `research/chatgpt-blueprint-v1.md` §"Simplified ERD"), `security/permission-matrix.md` (7 role sudah cukup terdokumentasi di BRD §User Persona + SRS §Permission, naikkan jadi file terpisah kalau role bertambah kompleks), `security/abuse-cases.md` (belum ada fitur uang/kuota native di Siapgrak — approval registration bukan target abuse tinggi; re-evaluasi begitu Phase 6 Kreenconnect integration mulai dikerjakan).
