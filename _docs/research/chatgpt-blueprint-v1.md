# Siapgrak v1 — Product, System & Database Blueprint (ChatGPT, Part 2)

Status: **Part 2** — lanjutan dari [`chatgpt-architecture-proposal.md`](chatgpt-architecture-proposal.md) (Part 1). Blueprint ini dimaksudkan untuk langsung dipakai sebagai input ke Claude/dev, fondasi modular dan sengaja tidak over-engineered.

---

## 0. Product Positioning

**Siapgrak** adalah platform khusus ekosistem Paskibra dan LKBB untuk:

- menemukan event
- mempromosikan event
- mengelola peserta lomba
- mengelola teknis perlombaan
- mengelola penjurian
- mempublikasikan hasil
- menyimpan histori prestasi tim/sekolah
- membantu operasional panitia sebelum, saat, dan sesudah event

Siapgrak **bukan** platform payment, ticketing, atau paid voting.

Untuk transaksi:

```text
Siapgrak → Kreenconnect API → Kreenconnect Checkout → Payment → Return to Siapgrak
```

Ownership:

```text
SIAPGRAK      = Competition + Event Operations + Ecosystem
KREENCONNECT  = Ticketing + Voting + Payment + Transaction
```

---

## 1. Prinsip Arsitektur (final sejak awal)

1. **Siapgrak tidak menyimpan transaksi Kreenconnect** — jangan buat `tickets`, `ticket_orders`, `payments`, `payment_transactions`, `votes`, `vote_orders`, `vote_transactions`. Siapgrak hanya simpan hubungan ke resource Kreenconnect.
2. **Event adalah domain utama** — semua operasional lomba berpusat pada satu **Event Workspace**, bukan menu terpisah yang tidak saling terhubung.
3. **Team dan Organization adalah entity permanen** — peserta jangan disimpan sebagai teks bebas ("SMAN 1 Bandung") tiap event; harus ada master entity agar histori kompetisi bisa dibangun.
4. **Category bersifat fleksibel** — jangan hardcode SMP/SMA/Purna; penyelenggara bisa bikin kategori apa saja (SMP Putra, RUKIBRA, LFPB, Variasi Formasi, dst).
5. **Scoring harus configurable per competition category** — tidak ada asumsi semua LKBB punya struktur nilai sama.
6. **Public transparency adalah bagian core product** — public event page, daftar peserta, dewan juri, jadwal, hasil bukan fitur tambahan.

---

## 2. User Type (v1)

```text
Super Admin
Organizer Owner
Organizer Admin
Event Staff
Judge
Team Representative
Public User
```

Tidak perlu role system terlalu granular di awal. Hak akses organizer via `user → organization → event`. Judge sebaiknya punya akses sangat terbatas hanya ke assignment penilaiannya.

---

## 3. Public Website — Struktur Halaman

**Main navigation:** `Home / Events / Teams / Results / Organizations / About`

### Homepage
Bukan dashboard — tujuannya "menemukan aktivitas Paskibra/LKBB yang sedang berlangsung":
```text
Hero "Cari Event LKBB & Paskibra" → Search
Upcoming Events / Featured Events / Latest Results / Popular Teams / Event by Region
CTA "Selenggarakan Event di Siapgrak"
```
Future (bukan MVP): News, Rankings, Competition Calendar.

### Event Discovery — `/events`
Filter: Search, Province, City, Event Date, Competition Level, Status (`Upcoming / Registration Open / Ongoing / Finished`).

### Public Event Page — `/events/{slug}`
Header: poster, nama, tanggal, lokasi, organizer.
Primary actions: `[Daftar Lomba]` `[Beli Tiket]` `[Vote]` — dua terakhir **hanya muncul kalau integrasi Kreenconnect aktif**.
Navigation: `Overview / Categories / Participants / Schedule / Judges / Results`.

### Event Overview
Isi: description, date & location, registration period, organizer, competition categories, technical info, dokumen penting (Juknis, Rundown, TM Document, Venue Map, Competition Rules), contact person.

### Public Participants
Contoh: `SMA 28/30 teams` lalu list `A01 SMAN 1 Bandung`, dst. Filter: Category/School/Team. **Jangan expose data personal anggota** — yang public cukup Team, School/Organization, Category, Competition Number.

### Public Judges
Nama, role/kriteria tanggung jawab, foto opsional.

### Public Results
Leaderboard dengan medali, plus award kategori (Best Danton, Best PBB, dst). Full scoring breakdown bisa dikonfigurasi per event: `Public / Organizer Only / Hidden`.

### Team Public Page — `/teams/{slug}`
Profil sekolah, lokasi, **Competition History** per tahun, statistik (Events Joined, Podiums, Champion, Awards) — **dihitung dari result data, bukan disimpan manual permanen**; cache aggregate baru kalau data sudah besar.

### Organizer Public Page — `/organizations/{slug}`
About, Upcoming Events, Past Events, Contact/Social.

---

## 4. Organizer Dashboard

**Global sidebar (setelah login):** `Dashboard / Events / Teams / Organization / Integrations / Settings`

**Penting:** jangan taruh `Participants / Judging / Schedule / Results` sebagai sidebar global — semua itu ada **di dalam Event Workspace**.

Dashboard global: Active Events, Upcoming Events, Total Participants, Completed Events + Recent Registrations + Recent Activity. Jangan terlalu banyak analytics di v1.

### Event Workspace — `/dashboard/events/{event}` (halaman terpenting aplikasi)

```text
Overview
Competition: Participants / Schedule / Judging / Results
Operations: Documents / Staff
Engagement: Ticketing / Voting
Settings
```

**Event Overview Dashboard** contoh:
```text
Categories 3 · Participants 82 · Judges 5 · Approved Teams 74
Registration: 74 Approved, 8 Pending
Competition Number: 68 Assigned
Schedule: Ready
Judging: 5 Judges Assigned
Ticketing: Connected · Voting: Connected
```

### Event Creation — 3 section sederhana

1. **Basic Information**: Event Name, Slug, Description, Poster, Organizer
2. **Schedule & Location**: Start/End Date, Venue Name, Address, Province, City
3. **Registration**: Registration Start/End, Participant Registration Open/Closed

Konfigurasi lanjutan dilakukan di Event Workspace setelah event dibuat.

---

## 5. Domain Model Detail

### Event Series (opsional)
```text
KEJURCAB FORBASI BANDUNG 2026
├── RUKIBRA
├── LKBB
└── VAFORMUS
```
Jangan buat hierarki recursive — cukup `event_series` + `events.event_series_id`.

### Competition Category
Fields: `Name, Code, Description, Quota, Registration Start/End, Minimum/Maximum Members, Status`. Category punya scoring configuration sendiri.

### Competition Registration Flow
```text
Public Event → Daftar Lomba → Login/Register → Select Team → Select Category
→ Registration Form → Team Members → Upload Documents → Submit → Pending Review
→ Organizer Review → Approved/Revision/Rejected → Competition Number
```
Registration bukan ticketing.

### Team Registration
Representative pilih existing team atau `Create Team` (Team Name, School/Organization, Province, City, Logo, Coach). Team reusable untuk event berikutnya.

### Registration Status — gunakan status sederhana
```text
draft, submitted, revision_requested, approved, rejected, withdrawn
```
Jangan pakai `pending/processing/verified/confirmed/finished` yang tumpang tindih.

### Competition Number
Setelah approved: `A01, A02, A03...`. Support: Manual Assignment, Automatic Sequential (v1), Random Draw (phase berikutnya).

### Competition Schedule
Tiap entry: Start Time, End Time, Order, Arena. V1 boleh manual; generate otomatis dari `start_time + duration_per_team + break` di fase berikutnya.

### Event-Day Entry Status
```text
registered, checked_in, ready, on_deck, performing, finished, disqualified, withdrawn
```
Ini fondasi untuk Event Command Center nanti.

---

## 6. Judging Structure

```text
Event → Competition Category → Judging Round → Judging Criteria
```

Contoh:
```text
LKBB SMA → Final → PBB 40% / Variation 30% / Danton 20% / Costume 10%
```

Round tetap dipakai walau v1 kebanyakan event cuma 1 round — supaya nanti bisa ada Preliminary → Final tanpa migrasi struktural.

### Criteria Structure
Fields: `Name, Description, Weight, Minimum/Maximum Score, Parent Criteria (nullable), Display Order`. Contoh subcriteria: `PBB → Gerakan / Kekompakan / Ketepatan`. Tidak semua event butuh subcriteria.

### Judge Assignment
Judge tidak otomatis menilai semua criteria — assignment harus **explicit** (Judge A → PBB, Judge B → Variation, dst).

### Score Sheet
1 record per `Judge x Competition Entry x Round`. Status: `draft, submitted, locked`.
```text
Judge enters scores → Save Draft → Submit → Locked
```
**"Sudah submit" harus eksplisit** — jangan ditebak dari semua nilai sudah terisi.

### Score Revision
Organizer **tidak boleh** langsung ubah nilai juri diam-diam:
```text
Submitted → Organizer Reopen (Reason Required) → Judge Edit → Submit Again
```
Semua tercatat di audit log.

### Result Calculation
Jangan taruh logic di controller — buat service `ResultCalculator`.
Input: Round, Criteria, Scores, Weight, Penalty. Output: Raw Score, Weighted Score, Penalty, Final Score, Rank.

### Awards
Pisahkan ranking utama dari awards (Champion, Runner-up, Best Danton, Best PBB, Best Costume, Favorite Team). Award berbasis voting (mis. Favorite Team) boleh berasal dari Kreenconnect, tapi final award record tetap disimpan di Siapgrak sebagai histori.

---

## 7. Operations

### Event Documents
Types: Technical Guide, Rundown, Venue Map, TM Material, Invitation, Other. Fields: `title, type, file, visibility (public/participants/organizer), published_at`.

### Event Staff
Role sederhana: `event_admin, registration_staff, competition_staff, scoring_admin, viewer`. Laravel Policy cukup — jangan bikin permission table kompleks di v1.

---

## 8. Integrasi Kreenconnect

### Ticketing
```text
Connect Ticketing → organizer pilih/hubungkan Kreenconnect event
```
Setelah tersambung, dashboard tampilkan: Status Connected, Tickets Sold, Revenue, `[Open Ticketing]` — data summary dari API.

### Voting
Dashboard tampilkan: Status, Votes, Candidates, Revenue, `[Open Voting]`. Voting candidates di-mapping ke `competition_entry` agar Siapgrak bisa tampilkan tombol Vote di profil peserta — tapi **vote transaction tetap hanya di Kreenconnect**.

### Checkout Experience
```text
Siapgrak Select Ticket/Vote → Kreenconnect Checkout Session API
→ checkout.kreenconnect.com/xxxx → Payment → Return URL → Siapgrak
```

---

## 9. Database Architecture

Stack: **MySQL 8+, Laravel 12, PHP 8.3**. Semua tabel & kolom pakai bahasa Inggris. Primary key `BIGINT UNSIGNED`. Public identifier di URL pakai `public_id`/slug; relasi internal tetap numeric `id`. Jangan pakai UUID sebagai FK di seluruh DB kalau tidak perlu.

### Daftar Tabel & Kolom (ringkas — index/FK lengkap ada di percakapan asli, simpan sebagai referensi struktural)

**Auth**
- `users`: id, public_id, name, email, email_verified_at, password, phone, avatar_path, status, remember_token, timestamps. Unique: public_id, email.

**Organization & Team**
- `organizations`: id, public_id, name, slug, type (school/association/community/organizer/agency/other), description, logo_path, email, phone, website, province_code, city_code, status, timestamps.
- `organization_users`: id, organization_id, user_id, role, status, timestamps. Unique (organization_id, user_id).
- `teams`: id, public_id, organization_id (nullable), name, slug, short_name, logo_path, province_code, city_code, description, status, timestamps. FK organization_id → ON DELETE SET NULL.
- `team_members`: id, team_id, user_id (nullable), name, gender, birth_date, member_role, status, timestamps. Jangan simpan data personal berlebihan.

**Event**
- `event_series`: id, public_id, organization_id, name, slug, description, year, status, timestamps.
- `events`: id, public_id, organization_id, event_series_id (nullable), name, slug, short_description, description, poster_path, start_date, end_date, registration_start_at, registration_end_at, venue_name, venue_address, province_code, city_code, latitude, longitude, status, published_at, created_by, timestamps. Status: `draft, published, registration_open, registration_closed, ongoing, finished, cancelled`.
- `event_categories`: id, event_id, name, code, description, quota, min_members, max_members, registration_start_at, registration_end_at, status, display_order, timestamps. Unique (event_id, code).

**Registration**
- `registrations`: id, public_id, event_id, event_category_id, team_id, submitted_by, status, submitted_at, reviewed_at, reviewed_by, review_notes, timestamps. Unique (event_category_id, team_id).
- `registration_members`: id, registration_id, team_member_id (nullable), name, member_role, gender, timestamps. **Snapshot roster** — jangan hanya refer ke `team_members` karena roster historis harus stabil walau member team berubah tahun depan.
- `registration_documents`: id, registration_id, document_type, title, file_path, status, review_notes, timestamps.

**Competition**
- `competition_entries`: id, public_id, event_id, event_category_id, registration_id, team_id, competition_number, performance_order, status, checked_in_at, timestamps. Unique: registration_id; (event_category_id, competition_number).
- `event_schedule_slots`: id, event_id, event_category_id (nullable), competition_entry_id (nullable), title, start_at, end_at, venue_area, slot_type (competition/technical_meeting/break/ceremony/announcement/other), display_order, timestamps.

**Judging**
- `judges`: id, public_id, user_id (nullable — judge bisa belum punya account), name, photo_path, bio, status, timestamps.
- `judging_rounds`: id, event_category_id, name, code, weight, status, display_order, timestamps.
- `judging_criteria`: id, judging_round_id, parent_id (nullable, FK self ON DELETE RESTRICT, **max depth 2** di v1), name, description, weight, minimum_score, maximum_score, display_order, status, timestamps.
- `judge_assignments`: id, event_category_id, judging_round_id, judge_id, judging_criteria_id (nullable — null berarti judge menilai semua criteria di round itu), status, timestamps. Unique (judging_round_id, judge_id, judging_criteria_id) — **catatan: MySQL UNIQUE dengan NULL punya perilaku khusus, butuh validasi tambahan di Laravel**.
- `score_sheets`: id, public_id, judging_round_id, competition_entry_id, judge_id, status (draft/submitted/locked), submitted_at, locked_at, revision_count (default 0), timestamps. Unique (judging_round_id, competition_entry_id, judge_id).
- `scores`: id, score_sheet_id, judging_criteria_id, score (**DECIMAL(10,3), jangan FLOAT**), notes, timestamps. Unique (score_sheet_id, judging_criteria_id).
- `score_sheet_revisions`: id, score_sheet_id, requested_by, reason, previous_status, created_at. (Audit trail perubahan score.)

**Result**
- `competition_results`: id, event_category_id, judging_round_id (nullable), competition_entry_id, raw_score, weighted_score, penalty_score, final_score, rank, status, finalized_at, timestamps. Unique (event_category_id, judging_round_id, competition_entry_id). **Jangan update tiap kali satu angka diketik** — generate/recalculate setelah score sheet berubah atau saat organizer finalize.
- `awards`: id, public_id, event_id, event_category_id (nullable), name, award_type, competition_entry_id (nullable), registration_member_id (nullable), source (judging/manual/kreenconnect_voting), external_reference, display_order, timestamps.

**Operations**
- `event_documents`: id, event_id, title, document_type, file_path, visibility, published_at, created_by, timestamps.
- `event_staff`: id, event_id, user_id, role, status, timestamps. Unique (event_id, user_id).

**Integration**
- `event_integrations`: id, event_id, provider, service, external_event_id, external_url, status, settings (json), last_synced_at, timestamps. Unique (event_id, provider, service). **Ini boundary utama dengan Kreenconnect — jangan simpan token API di sini, credentials masuk environment/config atau credential store.**
- `integration_snapshots` (opsional tapi disarankan — supaya dashboard tidak request Kreenconnect tiap page load): id, event_integration_id, metric, value_decimal/value_integer/value_text, captured_at, created_at. Contoh metric: `ticket_sold, ticket_revenue, vote_count`. Retention dibatasi; kalau cuma butuh latest value boleh 1 row per metric di-update.
- `integration_sync_logs`: id, event_integration_id, action, status, http_status, error_code, error_message, duration_ms, created_at. **Jangan log seluruh response JSON selamanya** — retention 30-90 hari.

**System**
- `activity_logs`: id, user_id (nullable), organization_id (nullable), event_id (nullable), action, subject_type, subject_id, metadata (json), ip_address, created_at (tidak perlu updated_at).

### Simplified ERD

```text
users
  │
  ├──── organization_users ─── organizations
  │                                │
  │                                ├── teams
  │                                │     └── team_members
  │                                │
  │                                ├── event_series
  │                                │
  │                                └── events
  │                                      │
  │                                      ├── event_categories
  │                                      │       │
  │                                      │       ├── registrations
  │                                      │       │      ├── registration_members
  │                                      │       │      └── registration_documents
  │                                      │       │
  │                                      │       ├── competition_entries
  │                                      │       │
  │                                      │       └── judging_rounds
  │                                      │               ├── judging_criteria
  │                                      │               ├── judge_assignments
  │                                      │               └── score_sheets
  │                                      │                        └── scores
  │                                      │
  │                                      ├── event_schedule_slots
  │                                      ├── event_documents
  │                                      ├── event_staff
  │                                      ├── event_integrations
  │                                      └── awards
  │
  └── judges

competition_entries
       │
       └── competition_results
```

### Tabel yang SENGAJA TIDAK dibuat

```text
payments, payment_methods, payment_transactions
tickets, ticket_types, ticket_orders, ticket_attendees
voting_categories, voting_candidates, vote_transactions, vote_payments
invoices, refunds, settlements
```
Semua ini milik Kreenconnect.

---

## 10. Kreenconnect API Contract yang Dibutuhkan

Siapgrak tidak perlu seluruh API Kreenconnect — cukup kontrak kecil ini:

**Ticketing:**
- `GET /api/v1/events/{id}` → `{id, name, status, checkout_url, currency}`
- `GET /api/v1/events/{id}/tickets` → daftar ticket type + harga + availability (tanpa expose inventory internal berlebihan)
- `POST /api/v1/checkout-sessions` → input `{event_id, items[{ticket_id, quantity}], source: "siapgrak", reference, return_url}`, output `{checkout_session_id, checkout_url, expires_at}`
- `GET /api/v1/events/{id}/ticketing-summary` → `{tickets_sold, checked_in, gross_revenue, status, updated_at}`

**Voting:**
- `GET /api/v1/voting/events/{id}` → `{id, name, status, checkout_url}`
- `GET /api/v1/voting/events/{id}/candidates` → `{id, name, image_url, vote_count}[]` — **Siapgrak harus bisa mapping `candidate_id ↔ competition_entry_id`**
- `POST /api/v1/voting/checkout-sessions` → input `{voting_event_id, candidate_id, package_id, source, reference, return_url}`, output `{checkout_session_id, checkout_url}`
- `GET /api/v1/voting/events/{id}/summary` → `{total_votes, candidate_count, gross_revenue, status, updated_at}`

### Authentication Antar Sistem
Jangan pakai login user Siapgrak sebagai auth API langsung — gunakan **machine-to-machine authentication** (API Client ID + Secret, atau signed request). Minimum: HTTPS, server-to-server API, credential di ENV, rate limiting, request ID, **idempotency key** untuk checkout session creation (biar double-click tidak bikin beberapa checkout), audit log.

### Webhooks (opsional untuk MVP)
Potential: `ticketing.summary.updated`, `voting.summary.updated`, `checkout.completed`. **Tidak perlu** dikirim ke Siapgrak: `payment.pending`, `payment.failed`, `refund.processing`, `gateway.callback`.

Security: `X-Kreen-Signature` / `X-Kreen-Timestamp` / `X-Kreen-Event-ID`, HMAC SHA-256, validasi signature + timestamp tolerance + event id uniqueness, simpan processed webhook ID untuk cegah replay. Kalau real-time tidak krusial, polling summary API cukup untuk MVP.

### Caching Integration Data
Jangan request Kreenconnect tiap kali public event page dibuka:
- Ticket summary: cache 1–5 menit
- Voting leaderboard: cache 15–60 detik saat event aktif
- Voting summary: cache 1 menit
- Historic event: cache jauh lebih lama

Event-day dashboard boleh lebih agresif, tapi jangan sinkronisasi seluruh transaction database.

---

## 11. Laravel Implementation Notes

### Services
```text
EventService, RegistrationService, CompetitionService, JudgingService, ResultService
KreenconnectClient, KreenconnectTicketingService, KreenconnectVotingService
```
Controller harus tipis — jangan taruh business logic scoring langsung di controller.

### Jobs (tidak perlu queue untuk semuanya)
```text
CalculateCompetitionResults
SyncKreenconnectTicketingSummary
SyncKreenconnectVotingSummary
GenerateCompetitionNumbers
ProcessUploadedDocument
PublishResults
```
Kalau environment belum stabil untuk queue, sync integration bisa mulai dengan scheduled command.

### Scheduler
```text
Every 5 minutes: sync ticketing summary
Every 1 minute during event: sync voting summary
Daily: cleanup old integration logs
```
Jangan mulai dengan terlalu banyak cron.

---

## 12. Data Integrity & Delete Strategy

Rules yang harus dijamin DB + aplikasi:
- Team tidak bisa register 2x ke category yang sama
- Competition number unique dalam category
- Judge tidak bisa submit score untuk criteria yang bukan assignment-nya
- Locked score sheet tidak bisa diedit
- Finished competition result tidak boleh diam-diam berubah
- Deleted team tidak boleh menghapus historical results
- Deleted user tidak boleh merusak audit trail

**Delete strategy:** hindari cascading delete agresif ke seluruh competition history. Event yang sudah punya registration sebaiknya di-`cancelled`/`archived`, bukan hard delete. Hard delete hanya untuk draft event tanpa data.

---

## 13. Security Baseline (minimum sebelum production)

```text
CSRF protection
Authorization Policies
Rate limiting (login, registration, public forms)
Input validation
File MIME validation + size limits
Private document storage + signed/private URLs
Judge access isolation
Organization tenant isolation
Mass assignment protection
Audit sensitive actions
API timeout + retry with backoff
Webhook signature validation
Secrets only in environment
```

---

## 14. Multi-Tenant, Public ID, File Storage, Search

- **Multi-tenant:** shared database (bukan DB terpisah per organizer). Semua resource punya `organization_id`/`event_id` jelas. **Laravel Policy wajib** memastikan organizer A tidak bisa akses event organizer B hanya dengan ganti URL ID.
- **Public IDs:** jangan expose `/events/14` — pakai slug (`/events/lkbb-garuda-2026`). Untuk API/internal action pakai `public_id` (`evt_01J...`, `team_01J...`), tapi FK database tetap integer.
- **File storage:** jangan simpan binary di MySQL — DB hanya simpan path/object key. Pisahkan public assets vs private participant documents (private dokumen **tidak boleh** punya direct public URL permanen).
- **Search:** v1 jangan Elasticsearch — MySQL cukup (index pada `events.name`, `teams.name`, `organizations.name`, LIKE/FULLTEXT kalau perlu). Jangan tambah search infra sebelum scale membuktikan kebutuhan.

---

## 15. Event Command Center (fase lanjutan, bukan MVP)

```text
EVENT LIVE
Teams 42 · Checked In 38 · Finished 27
Current: A28 — SMAN 7 Bandung
Next: A29 — SMAN 4 Bandung
Judges: 4/5 submitted
Voting: 142,800
Ticket Check-In: 1,420
```
Berpotensi jadi fitur premium organizer.

---

## 16. Product Roadmap (final, per-phase)

| Phase | Fokus | Goal |
|---|---|---|
| 1 | Foundation: Auth, Organizations, Teams, Events, Categories | Organizer bisa buat event + public event page |
| 2 | Registration: team registration, roster, dokumen, review, approval | Siapgrak gantikan Google Form pendaftaran |
| 3 | Competition Operations: entry, competition number, schedule, check-in | Dipakai panitia untuk operasional lomba |
| 4 | Judging: round, criteria, judge, assignment, score sheet, submit, lock, result calc | Full digital scoring |
| 5 | Public Results: leaderboard, awards, judge info, shareable results | Transparansi publik + distribusi organik |
| 6 | Kreenconnect: ticketing integration, voting integration, checkout session, summary | Bukan implementasi payment sendiri |
| 7 | Ecosystem: team profiles, competition history, organization profile, achievement archive | |
| 8 | Event Day: command center, live participant status, judge completion monitor, voting/ticketing summary | |

---

## 17. Fitur yang SENGAJA TIDAK Masuk MVP

```text
Chat, Forum, Social Feed
Native Livestream, Mobile App
AI Scoring
Complex Ranking Nasional
Automatic Certificates
Marketplace, Merchandise
Full CRM, Accounting
Online Meeting
Custom Website Builder
```
Semua bisa dipertimbangkan setelah event operation core terbukti.

---

## 18. Development Rule — Aturan Penentu Domain

Saat developer menemukan kebutuhan baru, tanyakan dulu: **"Apakah ini domain LKBB/competition?"**

- Ya → **Siapgrak**
- Payment / ticket transaction / paid voting transaction / refund / settlement → **Kreenconnect**

Jangan menduplikasi.

---

## 19. Core Product Definition (ringkasan)

```text
SIAPGRAK
Before Event: Discover, Promote, Register, Prepare
During Event: Check-in, Schedule, Compete, Judge, Monitor
After Event: Results, Awards, History, Achievements

KREENCONNECT melengkapi: Sell Tickets, Collect Payments, Run Paid Voting
```

### Final Architecture

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
                            │ integration
                            ▼
                     KREENCONNECT API
                  ┌─────────┴─────────┐
                  ▼                   ▼
              Ticketing             Voting
                  └─────────┬─────────┘
                            ▼
                     Kreen Checkout → Payment
```

---

## 20. Primary Success Metric

**Jangan** ukur keberhasilan awal dari page views/registered users saja.

**Primary operational metric:** *Events actively managed on Siapgrak*

Supporting metrics: Competition registrations, Teams with reusable profiles, Events completing digital judging, Published competition results, Returning organizers.

> Tujuan Siapgrak bukan sekadar traffic — tujuannya jadi **infrastruktur digital yang benar-benar dipakai untuk menjalankan ekosistem LKBB.**

---

## 21. Final Product Direction

**Jangan** bikin "Simpaskor clone". **Jangan** bikin "Kreenconnect LKBB edition".

**Bangun:** Siapgrak sebagai *operating system dan public ecosystem untuk Paskibra/LKBB* — pakai Simpaskor & platform lain sebagai benchmark workflow, pakai Kreenconnect untuk commerce, pakai Siapgrak untuk domain knowledge dan operational workflow.

```text
Kreenconnect = Transaction Infrastructure
Siapgrak     = Competition Infrastructure
```

Ini fondasi yang sebaiknya dipertahankan selama development. Blueprint ini sengaja **tidak membuat database terlalu generic** dan tidak mencoba menyatukan semua produk Kreen — untuk tahap pertama, struktur ini sudah cukup kuat untuk multi-organizer, event bertahun-tahun, histori tim, multi-category, multi-juri, audit nilai, sekaligus tetap ringan.

**Langkah berikutnya yang disarankan (belum dikerjakan):** pecah blueprint ini jadi development checklist per phase + urutan migration database + route/page yang harus dibuat, supaya implementasi tidak langsung bikin 20 tabel dan 30 halaman sekaligus tanpa urutan.
