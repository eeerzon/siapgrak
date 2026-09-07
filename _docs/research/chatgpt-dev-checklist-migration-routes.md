# Siapgrak v1 — Development Checklist, Migration Order & Route Map (ChatGPT, Part 3)

Status: **Part 3** — implementation plan turunan dari [`chatgpt-blueprint-v1.md`](chatgpt-blueprint-v1.md) (Part 2). Ini yang dipakai untuk brief coding task per-sprint ke Claude/dev, bukan "build Siapgrak" sekaligus.

Stack: Laravel 12, PHP 8.3, MySQL 8+, Blade, Alpine.js, Tailwind CSS. Arsitektur: single Laravel application, shared database, multi-organization.

---

## 0. Tujuan Dokumen

Supaya development: tidak lompat antar fitur, tidak bikin seluruh DB sekaligus, tidak bikin halaman sebelum domain model siap, mudah diuji per phase, mudah rollback, tiap phase menghasilkan sistem usable, dan tidak menduplikasi ticketing/voting milik Kreenconnect.

---

## 1. Development Rules (wajib sebelum mulai)

1. **Migration dibuat per phase** — jangan bikin seluruh tabel Phase 1–8 dalam satu batch.
2. **Jangan buat tabel ticketing/voting** (`tickets, ticket_types, orders, payments, payment_transactions, votes, vote_orders, vote_transactions`) — milik Kreenconnect.
3. **Semua tabel & kolom bahasa Inggris** (`events, registrations, competition_entries, judging_criteria` — bukan `acara, pendaftaran, peserta_lomba, kriteria_penilaian`).
4. **FK internal pakai `BIGINT UNSIGNED`** (`organization_id, event_id, team_id`); public URL pakai `slug`/`public_id`.
5. **Business logic jangan di Blade** — Blade cuma presentation; logic masuk Controller/Service/Policy/Action/Model.
6. **Jangan over-engineer** — tidak perlu microservices, event sourcing, CQRS, repository untuk semua model, Elasticsearch, Kafka. Laravel standard cukup.

---

## 2. Urutan Phase (jangan lompat)

```text
PHASE 0  Project Foundation
PHASE 1  Organization + Team + Event
PHASE 2  Competition Registration
PHASE 3  Competition Operations
PHASE 4  Judging
PHASE 5  Results + Public Transparency
PHASE 6  Kreenconnect Integration
PHASE 7  Team History + Ecosystem
PHASE 8  Event Command Center
```

Jangan lompat ke Phase 6 hanya karena integrasi ticketing/voting terlihat menarik — core Siapgrak harus selesai dulu.

---

## PHASE 0 — Project Foundation

**Goal:** Laravel project bersih, siap dikembangkan, belum ada fitur bisnis besar.

**Setup checklist:**
- Laravel 12, PHP 8.3, MySQL configured, `.env.example` lengkap
- Timezone `Asia/Jakarta`, locale default `id` + fallback
- Storage symlink, scheduler ready, queue config ready (meski belum dipakai)

**Authentication:** Login, Logout, Forgot/Reset Password, Email verification (opsional), rate limit login, session security. *(Catatan: kalau nanti pakai SSO Kreen existing, implementasi auth bisa diganti tanpa mengubah domain table lain.)*

**Base UI:** Public Layout / Dashboard Layout / Auth Layout. Components minimum: Button, Input, Select, Textarea, Badge, Card, Table, Empty State, Alert, Drawer/Modal, Pagination.

**Docs folder:** `_docs/{README.md, architecture/, database/, api/, security/, testing/, decisions/}`

**Routes:**
```text
GET / · GET/POST /login · POST /logout
GET/POST /forgot-password
GET/POST /reset-password/{token}
```

**Definition of Done:** fresh install berhasil, login/logout jalan, DB connection valid, Blade/Tailwind jalan, dashboard protected, guest tidak bisa buka dashboard, basic test suite hijau.

---

## PHASE 1 — Organization + Team + Event

**Goal:** Organizer bisa Create Organization → Create Team → Create Event → Create Category → Publish. Public bisa buka event. **Ini skeleton pertama yang usable.**

### Migration order
```text
01 users
02 organizations
03 organization_users
04 teams
05 team_members
06 event_series
07 events
08 event_categories
09 event_staff
```

**01 users:** id, public_id, name, email, email_verified_at, password, phone?, avatar_path?, status, remember_token, timestamps. Unique public_id+email, index phone+status.

**02 organizations:** id, public_id, name, slug, type (`school/association/community/organizer/agency/other`), description?, logo_path?, email?, phone?, website?, province_code?, city_code?, status, timestamps. Unique slug+public_id, index status/type/city/province.

**03 organization_users:** id, organization_id, user_id, role (`owner/admin/member`), status, timestamps. FK organization+user, unique (organization_id, user_id), policy tenant isolation.

**04 teams:** id, public_id, organization_id?, name, slug, short_name?, logo_path?, province_code?, city_code?, description?, status, timestamps. Unique slug+public_id, index organization+status.

**05 team_members:** id, team_id, user_id?, name, gender?, birth_date?, member_role?, status, timestamps. Sederhana di Phase 1, belum perlu data personal ekstensif.

**06 event_series:** id, public_id, organization_id, name, slug, description?, year?, status, timestamps. Optional — UI boleh ditunda walau tabel sudah ada.

**07 events:** id, public_id, organization_id, event_series_id?, name, slug, short_description?, description?, poster_path?, start_date, end_date, registration_start_at?, registration_end_at?, venue_name?, venue_address?, province_code?, city_code?, latitude?, longitude?, status, published_at?, created_by, timestamps. Status: `draft→published→registration_open→registration_closed→ongoing→finished` (+ `cancelled`). Index penting: (status,start_date), (organization_id,status), (city_code,start_date).

**08 event_categories:** id, event_id, name, code?, description?, quota?, min_members?, max_members?, registration_start_at?, registration_end_at?, status, display_order, timestamps. Unique (event_id,code), index (event_id,display_order).

**09 event_staff:** id, event_id, user_id, role (`event_admin/registration_staff/competition_staff/scoring_admin/viewer`), status, timestamps.

### Models & relasi minimum
```text
Organization hasMany Users (via organization_users), hasMany Teams, hasMany Events
Team belongsTo Organization, hasMany TeamMembers
Event belongsTo Organization, belongsTo EventSeries, hasMany EventCategories, hasMany EventStaff
```

### Routes — Public
```text
GET / · GET /events · GET /events/{event:slug}
GET /teams · GET /teams/{team:slug}
GET /organizations/{organization:slug}
```
`/teams` boleh belum masuk navigation kalau data masih sedikit.

### Routes — Dashboard
```text
GET /dashboard
GET/POST /dashboard/organizations, /dashboard/organization
GET/POST /dashboard/teams, GET /dashboard/teams/create, GET/PUT /dashboard/teams/{team}(/edit)
GET/POST /dashboard/events, GET /dashboard/events/create
GET/PUT /dashboard/events/{event}(/edit)
GET/POST/PUT/DELETE /dashboard/events/{event}/categories(/{category})
```

### Pages
Public: Homepage, Event Listing, Event Detail, Team Detail basic, Organization Detail basic.
Dashboard: Dashboard Home, Organization Settings, Team List/Create/Edit, Event List, Create Event, Event Workspace Overview, Event Categories, Event Settings.

### Event Workspace navigation (Phase 1)
Tampilkan semua menu (`Overview/Competition/Participants/Schedule/Judging/Results/Documents/Staff/Ticketing/Voting/Settings`) tapi yang belum dikembangkan diberi label **Coming Soon** atau di-hide — **jangan bikin dummy feature yang terlihat seolah sudah bekerja.**

### Policies
`OrganizationPolicy, TeamPolicy, EventPolicy, EventCategoryPolicy`. Rules minimum: user hanya akses organization miliknya, organizer A tidak bisa edit event organizer B, public hanya lihat event published, draft tidak public, hanya authorized member yang bisa edit team.

### Tests minimum
Organization (create, unauthorized cannot update, slug unique), Team (organizer create, foreign organizer cannot edit), Event (create, draft not public, published public, slug unique), Category (belongs to event, duplicate code rejected).

### Definition of Done
Organizer: login → create organization → create event → configure categories → publish. Public: buka event listing → event page → lihat categories.

---

## PHASE 2 — Competition Registration

**Goal:** Siapgrak gantikan Google Form pendaftaran lomba. Team representative daftar ke category, organizer review.

### Migration order
```text
10 registrations
11 registration_members
12 registration_documents
```

**10 registrations:** id, public_id, event_id, event_category_id, team_id, submitted_by, status, submitted_at?, reviewed_at?, reviewed_by?, review_notes?, timestamps. Status: `draft/submitted/revision_requested/approved/rejected/withdrawn`. **Constraint penting: UNIQUE (event_category_id, team_id)** — satu team tidak boleh daftar 2x ke category sama.

**11 registration_members:** id, registration_id, team_member_id?, name, member_role?, gender?, timestamps. **Snapshot roster — jangan cuma refer ke `team_members`**, nama harus di-snapshot supaya history tidak berubah kalau master team berubah.

**12 registration_documents:** id, registration_id, document_type, title?, file_path, status (`uploaded/approved/rejected`), review_notes?, timestamps.

### Routes — Public/Participant
```text
GET /events/{event:slug}/register
GET /events/{event:slug}/register/team
GET /events/{event:slug}/register/{category}
POST /events/{event:slug}/registrations
GET/PUT /registrations/{registration:public_id}(/edit)
POST /registrations/{registration:public_id}/members
POST /registrations/{registration:public_id}/documents
POST /registrations/{registration:public_id}/submit
POST /registrations/{registration:public_id}/withdraw
```

### Routes — Organizer
```text
GET /dashboard/events/{event}/participants
GET /dashboard/events/{event}/registrations(/{registration})
POST .../approve, .../request-revision, .../reject
```

### Pages
Participant: Select Team, Select Category, Registration Form, Member Roster, Document Upload, Registration Review, Submission Success, Registration Status.
Organizer: Registration List (filter category/status), Registration Detail, Member Roster, Document Review, Approve/Request Revision/Reject.

### Service
`RegistrationService`: `createRegistration(), submitRegistration(), approveRegistration(), requestRevision(), rejectRegistration(), withdrawRegistration()`. Business rule jangan tersebar ke banyak controller.

### Policies
`RegistrationPolicy, RegistrationDocumentPolicy`. Rules: owner bisa edit draft, submitted tidak bisa edit bebas, revision_requested bisa edit, approved tidak bisa edit, organizer bisa review event-nya sendiri saja.

### Tests
Team can register, duplicate category rejected, draft stays draft, submitted tidak bisa edit field normal, organizer request revision, revision bisa disubmit lagi, approve works, unauthorized organizer blocked, private documents tidak publicly accessible.

### Definition of Done
Team: pilih event → category → isi roster → upload dokumen wajib → submit. Organizer: review → approve.

---

## PHASE 3 — Competition Operations

**Goal:** Approved registration jadi competition entry; panitia kelola nomor peserta, jadwal, status hari-H.

### Migration order
```text
13 competition_entries
14 event_schedule_slots
15 event_documents
```

**13 competition_entries:** id, public_id, event_id, event_category_id, registration_id, team_id, competition_number?, performance_order?, status, checked_in_at?, timestamps. Status: `registered/checked_in/ready/on_deck/performing/finished/disqualified/withdrawn`. **UNIQUE registration_id; UNIQUE (event_category_id, competition_number).**

**Entry creation otomatis:** saat registration di-approve, `RegistrationService` langsung create `competition_entry` — jangan minta operator bikin ulang manual.

**14 event_schedule_slots:** id, event_id, event_category_id?, competition_entry_id?, title?, start_at, end_at?, venue_area?, slot_type (`competition/technical_meeting/break/ceremony/announcement/other`), display_order?, timestamps.

**15 event_documents:** id, event_id, title, document_type, file_path, visibility (`public/participants/organizer`), published_at?, created_by, timestamps.

### Routes — Organizer
```text
GET /dashboard/events/{event}/competition
GET/PUT /dashboard/events/{event}/competition/entries(/{entry})
POST .../competition/numbers/generate
PUT .../competition/entries/{entry}/number

GET/POST/PUT/DELETE /dashboard/events/{event}/schedule(/{slot})

POST /dashboard/events/{event}/entries/{entry}/check-in
POST /dashboard/events/{event}/entries/{entry}/status

GET/POST/PUT/DELETE /dashboard/events/{event}/documents(/{document})
```

### Routes — Public
```text
GET /events/{event:slug}/participants
GET /events/{event:slug}/schedule
GET /events/{event:slug}/documents
```

### Pages
Organizer: Competition Entries, Competition Number Management, Generate Number, Schedule, Event Documents, Event-day Participant Status.
Public: Participants tab, Schedule tab, Public Documents.

### Service
`CompetitionService, CompetitionNumberService, ScheduleService`.

### Competition Number rules
V1: `manual` + `automatic sequential` (config Prefix/Start number/Digits, mis. `A01, A02, A03`). Random draw belum perlu.

### Tests
Approved registration create entry, duplicate competition number dicegah, sequential generator works, category isolation works, check-in timestamp tersimpan, invalid status transition ditolak, private documents terproteksi.

### Definition of Done
Approved Participants → Competition Number → Schedule → Check-In → Ready → Performing → Finished, tanpa spreadsheet eksternal.

---

## PHASE 4 — Judging System

**Goal:** Penilaian LKBB digital dengan submit/lock/audit yang benar. **Phase paling sensitif secara data.**

### Migration order
```text
16 judges
17 judging_rounds
18 judging_criteria
19 judge_assignments
20 score_sheets
21 scores
22 score_sheet_revisions
```
Jangan buat `competition_results` sebelum scoring flow benar.

**16 judges:** id, public_id, user_id?, name, photo_path?, bio?, status, timestamps. Judge boleh dibuat tanpa account.

**17 judging_rounds:** id, event_category_id, name, code?, weight?, status, display_order, timestamps.

**18 judging_criteria:** id, judging_round_id, parent_id?, name, description?, weight?, minimum_score, maximum_score, display_order, status, timestamps. **Business rule: max nested depth = 2.**

**19 judge_assignments:** id, event_category_id, judging_round_id, judge_id, judging_criteria_id?, status, timestamps. `criteria_id NULL` = judge menilai seluruh round.

**20 score_sheets:** id, public_id, judging_round_id, competition_entry_id, judge_id, status (`draft/submitted/locked`), submitted_at?, locked_at?, revision_count (default 0), timestamps. **UNIQUE (judging_round_id, competition_entry_id, judge_id).**

**21 scores:** id, score_sheet_id, judging_criteria_id, score `DECIMAL(10,3)` (never FLOAT), notes?, timestamps. UNIQUE (score_sheet_id, judging_criteria_id).

**22 score_sheet_revisions:** id, score_sheet_id, requested_by, reason, previous_status, created_at (tidak perlu updated_at).

### Routes — Organizer
```text
GET /dashboard/events/{event}/judging
GET/POST/PUT /dashboard/events/{event}/judges(/{judge})
POST /dashboard/events/{event}/categories/{category}/rounds
PUT/DELETE /dashboard/events/{event}/rounds/{round}
POST /dashboard/events/{event}/rounds/{round}/criteria
PUT/DELETE /dashboard/events/{event}/criteria/{criterion}
GET/POST/DELETE /dashboard/events/{event}/judge-assignments(/{assignment})
POST /dashboard/events/{event}/score-sheets/{scoreSheet}/reopen  (reason wajib)
POST /dashboard/events/{event}/score-sheets/{scoreSheet}/lock
```

### Routes — Judge (area terpisah, TIDAK boleh akses dashboard organizer)
```text
GET /judge
GET /judge/events/{event}
GET/PUT /judge/score-sheets/{scoreSheet}
POST /judge/score-sheets/{scoreSheet}/submit
```

### Pages
Organizer: Judges, Rounds, Criteria Builder, Judge Assignment, Scoring Monitor, Score Sheet Detail, Reopen Score Sheet.
Judge: Judge Dashboard, Assigned Participants, Score Form, Save Draft, Submit Confirmation, Submitted State, Locked State.

### Critical business rules
- Judge hanya lihat event & criteria yang di-assign ke dia
- Score harus dalam range min/max
- Semua score wajib ada sebelum submit
- Status submitted harus eksplisit (bukan ditebak dari semua field terisi)
- Score `0` valid kalau min mengizinkan 0
- Submitted sheet tidak bisa diedit
- Reopen wajib reason
- Locked sheet tidak bisa reopen tanpa privileged action
- **Audit setiap reopen**

### Service
`JudgingService, ScoreSheetService` — `buildScoreSheet(), saveDraft(), submit(), reopen(), lock(), validateScores()`.

### Tests
Assignment (unauthorized judge tidak bisa buka sheet, tidak bisa nilai criteria di luar assignment), Score (di bawah/atas min-max ditolak, zero score valid, decimal tersimpan benar), Submission (incomplete tidak bisa submit, submit ubah state, submitted tidak bisa edit, reopen wajib reason, revision_count bertambah), Tenant (organizer tidak bisa manipulasi score event lain).

### Definition of Done
Organizer buat criteria → assign judges. Judge buka team yang di-assign → input score → save → submit. Organizer lihat completion state. Semua tanpa spreadsheet.

---

## PHASE 5 — Results + Public Transparency

**Goal:** Nilai dihitung, difinalisasi, dipublikasikan.

### Migration order
```text
23 competition_results
24 awards
25 activity_logs
```

**23 competition_results:** id, event_category_id, judging_round_id?, competition_entry_id, raw_score, weighted_score, penalty_score, final_score, rank?, status (`draft/finalized/published`), finalized_at?, timestamps.

**24 awards:** id, public_id, event_id, event_category_id?, name, award_type, competition_entry_id?, registration_member_id?, source (`judging/manual/kreenconnect_voting`), external_reference?, display_order, timestamps.

**25 activity_logs:** id, user_id?, organization_id?, event_id?, action, subject_type?, subject_id?, metadata (json)?, ip_address?, created_at. Audit high-value actions: `registration_approved, registration_rejected, score_sheet_submitted, score_sheet_reopened, result_recalculated, result_finalized, result_published, award_changed`.

### Result Calculator
`ResultCalculator` service — **jangan hitung ranking di Blade/controller**: `calculateRawScore(), calculateWeightedScore(), calculatePenalty(), calculateFinalScore(), calculateRanking()`. **Tie handling harus eksplisit**, jangan diam-diam pakai arbitrary order.

### Routes
Organizer: `GET .../results`, `POST .../results/calculate`, `.../finalize`, `.../publish`, CRUD `.../awards`.
Public: `GET /events/{slug}/judges`, `GET /events/{slug}/results`.

### Pages
Organizer: Result Preview, Score Breakdown, Ranking, Finalize Result, Awards Management, Publish Result.
Public: Judges, Results Leaderboard, Awards, Participant Detail (opsional), Share Result.

### Publishing rule
```text
scores → calculate → draft result → review → finalize → publish
```
**Jangan** biarkan judge submit langsung jadi public — harus ada review/finalization checkpoint dari panitia.

### Tests
Calculation deterministic, weight benar, decimal precision benar, rank benar, tie behavior benar, draft tidak public, finalized tidak otomatis public, published baru public, perubahan score butuh recalculation, publikasi hasil ter-audit.

### Definition of Done
Registration → Competition → Judging → Result → Public Leaderboard jalan penuh. **Di titik ini Siapgrak core sudah viable meskipun belum terhubung Kreenconnect.**

---

## PHASE 6 — Kreenconnect Integration

**Goal:** Pakai ticketing & voting mature dari Kreenconnect tanpa menyimpan transaksi.

### Migration order
```text
26 event_integrations
27 integration_snapshots
28 integration_sync_logs
29 processed_webhooks (opsional, hanya kalau webhook dipakai)
30 voting_candidate_mappings
```

**26 event_integrations:** id, event_id, provider, service, external_event_id, external_url?, status, settings (json)?, last_synced_at?, timestamps.

**27 integration_snapshots:** id, event_integration_id, metric, value_decimal?/value_integer?/value_text?, captured_at, created_at. Boleh pakai model latest-value yang lebih ringan kalau history tidak dibutuhkan.

**28 integration_sync_logs:** id, event_integration_id, action, status, http_status?, error_code?, error_message?, duration_ms?, created_at. Retention 30-90 hari.

**29 processed_webhooks** (jika dipakai): id, provider, external_event_id, event_type, processed_at, created_at. UNIQUE (provider, external_event_id) untuk replay protection.

**30 voting_candidate_mappings:** id, event_integration_id, competition_entry_id, external_candidate_id, timestamps. UNIQUE (event_integration_id, competition_entry_id) dan (event_integration_id, external_candidate_id).

### Kreenconnect Client
`KreenconnectClient` (authenticate, send request, timeout, retry, request logging, response normalization) → `KreenconnectTicketingService`, `KreenconnectVotingService`.

### Routes — Organizer
```text
GET/POST/DELETE /dashboard/events/{event}/ticketing(/connect|/disconnect)
GET/POST/DELETE /dashboard/events/{event}/voting(/connect|/disconnect)
GET /dashboard/events/{event}/voting/candidates
POST /dashboard/events/{event}/voting/candidate-mappings
```

### Routes — Public
```text
GET /events/{event:slug}/tickets
POST /events/{event:slug}/checkout/ticket
GET /events/{event:slug}/vote
POST /events/{event:slug}/vote/{entry}
GET /events/{event:slug}/checkout/return
```
Server flow: Siapgrak → Kreenconnect API → create checkout session → redirect `checkout_url`. **Jangan percaya `?payment=success` sebagai payment truth** — itu cuma UX state, payment truth tetap di Kreenconnect.

### Webhook
```text
POST /webhooks/kreenconnect
```
Middleware: signature validation, timestamp validation, replay protection. Route tidak pakai CSRF tapi wajib punya signature security sendiri.

### Pages
Organizer: Ticketing Integration, Connect Ticketing, Ticketing Summary, Voting Integration, Connect Voting, Voting Candidate Mapping, Voting Summary.
Public: Ticket Selection, Voting Candidate, Voting Package Selection, Redirect to Kreenconnect Checkout, Return State.

### Tests
API credentials tidak pernah exposed ke browser, timeout ditangani, API error ditangani graceful, duplicate checkout diproteksi via idempotency, webhook invalid signature ditolak, webhook replay ditolak, organizer lain tidak bisa connect integration event orang lain, transaksi eksternal tidak disimpan lokal, checkout URL hanya dari response API terpercaya.

### Definition of Done
```text
Siapgrak Event → Buy Ticket → Kreen Checkout → Payment → Return Siapgrak
Siapgrak Event → Vote → Kreen Checkout → Payment → Return Siapgrak
```
Organizer lihat summary tanpa punya transaction DB sendiri.

---

## PHASE 7 — Team History + Ecosystem

**Goal:** Siapgrak jadi database historis Paskibra/LKBB, bukan cuma software event.

**Tidak perlu migration besar** — data sudah tersedia dari `teams, competition_entries, competition_results, awards, events`. **Jangan buat tabel `team_statistics`** — hitung dulu dari canonical data, cache hanya kalau query mulai mahal.

### Pages
Team (`/teams/{slug}`): events joined, competition history, champion count, podium count, awards, recent results.
Organization (`/organizations/{slug}`): upcoming events, completed events, teams, organizer history.

### Event Discovery improvements
Filter: province, city, date, category, status. Homepage: upcoming events, latest results, popular/recent teams, organizer CTA.

### Search (opsional, hanya kalau perlu)
FULLTEXT atau optimized LIKE — **jangan Elasticsearch**.

### Tests
Team history cuma pakai data published/finalized yang sesuai, cancelled event tidak salah dihitung, withdrawn entries dikecualikan, deleted/deactivated team history tetap terjaga.

---

## PHASE 8 — Event Command Center

**Goal:** Dashboard live hari-H untuk panitia. **Jangan dibangun sebelum Phase 1–6 stabil.**

```text
GET /dashboard/events/{event}/live
```

Contoh isi: Participants/Checked In/Finished counts, Current & Next performer, Judges completion (`4/5 submitted`), Voting count, Tickets Checked In.

**Data source:** Siapgrak (`competition_entries, schedule, score_sheets, results`) + Kreenconnect (ticket summary, voting summary — **jangan fetch berulang per component, pakai cached aggregate**).

**Real-time:** mulai dari polling 10-30 detik. **Jangan langsung implement WebSocket** — upgrade hanya kalau usage event benar-benar butuh.

---

## Full Migration Order (referensi cepat)

```text
01 users

PHASE 1: 02 organizations · 03 organization_users · 04 teams · 05 team_members
         06 event_series · 07 events · 08 event_categories · 09 event_staff

PHASE 2: 10 registrations · 11 registration_members · 12 registration_documents

PHASE 3: 13 competition_entries · 14 event_schedule_slots · 15 event_documents

PHASE 4: 16 judges · 17 judging_rounds · 18 judging_criteria · 19 judge_assignments
         20 score_sheets · 21 scores · 22 score_sheet_revisions

PHASE 5: 23 competition_results · 24 awards · 25 activity_logs

PHASE 6: 26 event_integrations · 27 integration_snapshots · 28 integration_sync_logs
         29 processed_webhooks (opsional) · 30 voting_candidate_mappings
```

## Full Route Map (referensi cepat)

**Public:**
```text
/ · /events · /events/{slug}
/events/{slug}/participants · /schedule · /judges · /results · /documents
/events/{slug}/register · /register/{category}
/events/{slug}/tickets · /events/{slug}/vote
/teams · /teams/{slug}
/organizations/{slug}
```

**User (peserta):**
```text
/registrations/{public_id}(/edit)
/registrations/{public_id}/members · /documents
/registrations/{public_id}/submit · /withdraw
```

**Dashboard organizer:**
```text
/dashboard · /dashboard/organization · /dashboard/teams(/{team})
/dashboard/events · /dashboard/events/create · /dashboard/events/{event}/
    overview · competition/entries · participants · registrations · schedule
    · judging · judges · judge-assignments · results · documents · staff
    · ticketing · voting · live · settings
```
UI sidebar tidak perlu tampilkan `/overview` — klik event langsung buka overview.

**Judge:**
```text
/judge · /judge/events/{event}
/judge/score-sheets/{scoreSheet}(/submit)
```
Area judge dibuat sesederhana mungkin — tidak butuh seluruh dashboard organizer.

### Navigation final

Global dashboard: `Dashboard / Events / Teams / Organization / Settings` (+ opsional Integrations, tapi lebih baik dikelola per-Event Workspace karena integration bersifat event-specific).

Event Workspace: `Overview` + grup **COMPETITION** (Participants/Schedule/Judging/Results) + **OPERATIONS** (Documents/Staff) + **ENGAGEMENT** (Ticketing/Voting) + `Settings`. Mobile: `Overview/Participants/Judging/Results/More`.

Public navigation: `Home / Events / Teams / Results / About` (Organizations tidak perlu top-nav, ditemukan lewat event/team profile).

---

## Status Matrix (semua entity)

**Event:** `draft → published → registration_open → registration_closed → ongoing → finished` (+ exit `cancelled`). **Jangan otomatis tentukan status dari tanggal saja** — sistem boleh kasih suggestion, tapi stored status tetap eksplisit.

**Registration:** `draft → submitted → {revision_requested → submitted} | approved | rejected` (+ `withdrawn`).

**Competition Entry:** `registered → checked_in → ready → on_deck → performing → finished` (+ `withdrawn`, `disqualified`).

**Score Sheet:** `draft → submitted → locked`. Revisi: `submitted → reopen → draft → submitted`, harus ada audit trail.

**Result:** `draft → finalized → published`. **Tidak boleh** balik dari `published` ke `draft` tanpa privileged administrative workflow + audit.

---

## Minimum Factories & Seeders

```text
UserFactory, OrganizationFactory, TeamFactory, EventFactory, EventCategoryFactory,
RegistrationFactory, CompetitionEntryFactory, JudgeFactory
```
Dev seeder: Demo Organizer, Demo Team, Demo Event, 3 Categories, 10 Participants, 3 Judges — supaya UI tidak dikembangkan dengan database kosong.

## Required Policies
```text
OrganizationPolicy, TeamPolicy, EventPolicy, EventCategoryPolicy, RegistrationPolicy,
CompetitionEntryPolicy, EventDocumentPolicy, JudgePolicy, ScoreSheetPolicy,
ResultPolicy, EventIntegrationPolicy
```

## Required Services (minimum, jangan bikin semua service yang terbayang)
```text
RegistrationService
CompetitionService, CompetitionNumberService
JudgingService, ScoreSheetService
ResultCalculator, ResultService
KreenconnectClient, KreenconnectTicketingService, KreenconnectVotingService
```

## Suggested Controllers
Public: `HomeController, EventController, TeamController, OrganizationController, PublicResultController`.
Registration: `RegistrationController, RegistrationMemberController, RegistrationDocumentController`.
Dashboard: `DashboardController, OrganizationController, TeamController, EventController, EventCategoryController, EventRegistrationController, CompetitionEntryController, EventScheduleController, EventDocumentController, JudgeController, JudgingRoundController, JudgingCriteriaController, JudgeAssignmentController, ResultController, AwardController, TicketingIntegrationController, VotingIntegrationController`.
Judge: `JudgeDashboardController, JudgeScoreSheetController`.

---

## Security Checklist per Phase
Route auth benar, policy diimplementasi, tenant isolation di-test, validasi diimplementasi, mass assignment direview, foreign keys benar, unique constraints benar, file upload aman, public/private visibility direview, rate limit di tempat yang perlu, audit sensitive actions.

## Database Checklist per Migration
Table/column name English, tipe data tepat, nullable intentional, index berdasarkan query nyata, foreign key ada, delete behavior intentional, `down()` jalan, tidak ada index redundan, DECIMAL untuk score/money, JSON hanya kalau strukturnya genuinely fleksibel.

## Performance Rules
Jangan optimasi prematur, tapi hindari kesalahan jelas: hindari N+1 (eager loading), paginate `events/registrations/participants/activity logs` (jangan load 1000 participant sekaligus), index berdasarkan `event_id/organization_id/status/category/date` (jangan index semua kolom).

## Delete Rules
Prefer status/archive daripada hard delete. Published Event, Finished Event, Approved Registration, Submitted Score → **tidak boleh hard delete**. Hard delete hanya untuk draft tanpa downstream records.

---

## Recommended Sprint Sequence

```text
Sprint 1  Auth, Organization, Organization Policy
Sprint 2  Team, Team Members, Team Public Page
Sprint 3  Event, Event Categories, Public Event Page          → Milestone: Phase 1 complete

Sprint 4  Registration Draft, Registration Members
Sprint 5  Documents, Submit, Organizer Review                 → Milestone: Phase 2 complete

Sprint 6  Competition Entry, Competition Number
Sprint 7  Schedule, Check-in, Event Status                    → Milestone: Phase 3 complete

Sprint 8  Judge, Round, Criteria
Sprint 9  Assignment, Score Sheet, Save Draft
Sprint 10 Submit, Lock, Revision, Audit                       → Milestone: Phase 4 complete

Sprint 11 Result Calculator, Ranking
Sprint 12 Awards, Public Result, Publish                      → Milestone: Phase 5 complete

Sprint 13 Kreenconnect Client, Ticketing Integration
Sprint 14 Voting Integration, Candidate Mapping, Checkout     → Milestone: Phase 6 complete
```

---

## Instruksi Penting untuk Claude/Dev

**Jangan implementasi semua migration & fitur dalam satu pass.** Untuk tiap sprint:

```text
1. Create migration
2. Create model relationship
3. Create policy
4. Create service/business logic
5. Create controller
6. Create route
7. Create UI
8. Create automated tests
9. Run complete test suite
10. Review database query/index impact
11. Commit
```
Baru lanjut ke sprint berikutnya.

### Phase Gate Rule
Claude/dev **tidak boleh lanjut ke phase berikutnya** kalau: tests failing, migration rollback failing, tenant isolation belum di-test, status transition belum jelas, existing production flow rusak. Tiap phase harus independently deployable.

### Contoh instruksi coding pertama (jangan bilang "Build Siapgrak")

```text
Implement Siapgrak Phase 1, Sprint 1 only.

Scope:
- Organization model/database
- organization_users pivot
- create organization
- organization membership
- organization authorization/policies
- organization settings page
- tests

Do not implement:
- team
- event
- registration
- judging
- ticketing
- voting

Use Laravel 12 / PHP 8.3.
All database names must use English.
Add migrations incrementally.
Run migrations up/down and full test suite before finishing.
Document implementation decisions in _docs.
```

Setelah Sprint 1 stabil → Sprint 2 (Team) → Sprint 3 (Event). **Jangan dibalik urutannya.**

---

## MVP Cut Line

**MVP public beta yang sehat ada setelah Phase 5**: Organization, Team, Event, Registration, Competition Operations, Judging, Results. **Ticketing/voting belum wajib** — Kreenconnect masih bisa dipakai via external link sementara. Setelah core Siapgrak terbukti dipakai event nyata, baru Phase 6 bikin integrasinya seamless.

## Final Build Order

```text
Foundation → Organization → Team → Event → Category → Registration → Approval
→ Competition Entry → Competition Number → Schedule → Judge → Criteria → Score
→ Result → Public Result → Kreenconnect Ticketing → Kreenconnect Voting
→ History/Ecosystem → Command Center
```

**Jangan balik urutannya. Fondasi Siapgrak adalah competition operations, bukan payment atau voting.**

Instruksi coding pertama ke Claude: **Phase 1 Sprint 1 — Organization + tenancy + policy saja.** Kalau ada keputusan desain/database yang ternyata salah, ketahuan saat baru 2-3 tabel, bukan setelah 30 migration saling bergantung.
