# System Overview — Siapgrak

**Status:** Draft (pre-implementation). Diagram & keputusan di bawah adalah desain yang disepakati, belum diverifikasi terhadap kode nyata (belum ada kode).

## Ringkasan

Siapgrak adalah aplikasi Laravel 12 monolitik (repo tunggal, shared database, multi-organization) yang menangani domain **kompetisi/operasional LKBB-Paskibra**. Transaksi (ticketing, voting, payment) sengaja TIDAK ditangani di sini — dikonsumsi lewat API dari **Kreenconnect** (lihat [ADR-001](../decisions/adr-001-siapgrak-kreenconnect-boundary.md) dan [integration.md](integration.md)).

## Diagram Arsitektur Tingkat Tinggi

```text
                       PUBLIC USER
                            │
                            ▼
                      siapgrak.id
                            │
          ┌─────────────────┼─────────────────┐
          ▼                 ▼                 ▼
      Discovery        Competition        Results
      Events           Registration       Awards
      Teams            Schedule           History
      Organizer        Judging            Profile
                            │
                            │ integration (API, server-to-server)
                            ▼
                     KREENCONNECT API
                  ┌─────────┴─────────┐
                  ▼                   ▼
              Ticketing             Voting
                  └─────────┬─────────┘
                            ▼
                     Kreen Checkout → Payment
```

Diagram detail per-fungsi (public site, event workspace, judging flow) ada di `research/chatgpt-blueprint-v1.md` §3-9 dan §96 — tidak diulang di sini untuk menghindari dokumen duplikat yang gampang stale (lihat prinsip utama standar dokumentasi).

## Domain Utama (Bounded Context)

```text
1. Identity & Tenancy    users, organizations, organization_users
2. Paskibra Directory    teams, team_members
3. Event                 event_series, events, event_categories
4. Registration          registrations, registration_members, registration_documents
5. Competition           competition_entries, event_schedule_slots
6. Judging               judges, judging_rounds, judging_criteria, judge_assignments,
                          score_sheets, scores, score_sheet_revisions
7. Result                competition_results, awards
8. Operations            event_documents, event_staff
9. Integration           event_integrations, integration_snapshots, integration_sync_logs,
                          voting_candidate_mappings
10. System               activity_logs
```

Skema kolom+index+FK lengkap per tabel: `research/chatgpt-blueprint-v1.md` §39-66. ERD visual: `research/chatgpt-blueprint-v1.md` §67 (disalin ke `database/erd.md` kalau kompleksitas relasi mulai butuh diagram terpisah yang lebih mudah dibaca — belum dibuat, lihat `_docs/README.md`).

## Stack

```text
Backend        Laravel 12, PHP 8.3
Database       MySQL 8+
Frontend       Blade + Alpine.js + Tailwind CSS
Arsitektur     Single Laravel application, shared database, multi-organization
```

**Sengaja TIDAK dipakai** (lihat CLAUDE.md → Prinsip Karpathy #2 Simplicity First): microservices, event sourcing, CQRS, repository pattern untuk semua model, Elasticsearch, Kafka, WebSocket (di awal — polling dulu untuk real-time Event Command Center di Phase 8).

## Struktur Kode (opsional, jangan over-engineer)

```text
app/
├── Domain/            (opsional — kalau tim lebih nyaman DDD ringan)
│   ├── Organization/
│   ├── Team/
│   ├── Event/
│   ├── Registration/
│   ├── Competition/
│   ├── Judging/
│   ├── Result/
│   └── Integration/
├── Http/
├── Models/
├── Services/
└── Jobs/
```

Kalau tim lebih nyaman Laravel biasa (flat `Models/Controllers/Services/Jobs/Policies`), itu juga cukup — **yang penting adalah boundary database & domain-nya, bukan struktur folder.**

## Multi-Tenant Strategy

Shared database, **bukan** database terpisah per organizer. Setiap resource yang dimiliki organizer punya `organization_id`/`event_id` eksplisit. Isolasi tenant ditegakkan lewat Laravel Policy di setiap Controller — bukan lewat pemisahan skema/DB. Lihat `security/security-baseline.md` §Authorization untuk aturan konkret.

## Public Identifier

URL publik menggunakan slug (`/events/lkbb-garuda-2026`), bukan numeric ID auto-increment. Untuk referensi API/internal action, gunakan `public_id` (contoh: `evt_01J...`). Foreign key di database tetap integer `BIGINT UNSIGNED` — `public_id`/slug adalah lapisan presentasi, bukan pengganti PK.

## Batas Sistem (System Boundary)

| Di dalam Siapgrak | Di luar Siapgrak (Kreenconnect) |
|---|---|
| Organization, Team, Event, Category | Ticket, Ticket Type |
| Competition Registration | Order, Payment, Payment Transaction |
| Competition Entry, Schedule | Vote, Vote Transaction |
| Judging (Round/Criteria/Score Sheet) | Invoice, Refund, Settlement |
| Result, Awards | — |
| `event_integrations` (referensi ke resource Kreenconnect) | — |

Lihat [integration.md](integration.md) untuk detail kontrak komunikasi lintas boundary ini.
