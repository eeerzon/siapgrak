# Design Doc — Event + Competition Category + Event Staff (Phase A, Sprint 3)

**Status:** Implemented (2026-09-10) — 45 test hijau di MySQL, Pint clean, migrasi up/down bersih. Milestone Phase A tercapai.
**Author:** Claude
**Tanggal:** 2026-09-10
**Berkaitan dengan BRD:** `_docs/brd/brd-core.md` · **Kontrak:** `BACKEND-HANDOFF.md` §Sprint 3
**Definition of Ready:** ✅ — Sprint 1-2 PASS-review, B1 (MySQL lokal) resolved, Keputusan A-H dikonfirmasi Ersa ("lanjut aja", 2026-09-10).

## Catatan Implementasi (yang berubah/dikonkretkan dari draft)

- **Keputusan H direvisi saat implementasi:** TIDAK dibuat `EventCategoryPolicy`/`EventStaffPolicy` terpisah. Alasan: pola nyata di codebase (Sprint 2) adalah child resource digate lewat policy parent + assert-belongs-to — `TeamMemberController` tidak punya `TeamMemberPolicy`. Kategori & Staff digate lewat `EventPolicy::manageCategories` / `manageStaff` (authorize terhadap `{event}`) + `abort_unless($child->event_id === $event->id, 404)` di controller. Tenant isolation tetap tegak & teruji (7 test adversarial + belongs-to di `EventCategoryTest`/`EventStaffTest`/`TenantIsolationTest`).
- **Keputusan B direvisi:** transisi status invalid dikembalikan sebagai **validation error di field `status`** (302 back + error bag), bukan HTTP 422 mentah — konsisten dengan cara semua form input buruk lain ditangani di app. `InvalidEventStatusTransition` di-catch di `EventController::updateStatus` lalu di-`throw ValidationException::withMessages(['status' => ...])`.
- **`event_categories.status` & `event_staff.status` (active/inactive):** kolom dibuat (default `active`), tapi TIDAK di-wire di form Sprint 3 (toggle active/inactive kategori/staff = fitur kecil, ditunda). Menghindari masalah `convertEmptyStringsToNull` → NULL insert ke kolom non-nullable.
- **UI edit kategori & ganti-role staff:** endpoint (`PUT categories/{category}`, `PATCH staff/{staff}`) + test ada, tapi UI-nya baru add+delete di halaman event. Inline-edit kategori = F7, ganti-role staff = sprint terkait. Milestone "setup category" terpenuhi dengan add+delete.
- **Open Question 403 vs 404:** dikonfirmasi dari Sprint 1-2 — Policy deny → **403** (`assertForbidden`); child tak-milik-parent → **404** (`assertNotFound`). Diikuti persis.
- **Frontend:** view prototype `/dev/events` (F4) dibongkar; `EventController` + `dashboard.events.*` asli menggantikannya, view `resources/views/dashboard/events/*` dipakai ulang (rewire route + buang field poster + `show` jadi overview nyata dengan tab Overview/Kategori/Staff). "Events" ditambahkan ke sidebar + mobile nav.

---

> ⚠️ Cek `CLAUDE.md` → Constitution sebelum lanjut. Poin relevan Sprint 3:
> tenant isolation wajib via Policy · **tidak ada halaman publik** (Public Event Page DEFERRED, ADR-002) ·
> event dengan data turunan tidak boleh hard delete · nama tabel/kolom English · `DECIMAL` bukan FLOAT.

## TL;DR

Domain `Organization → Event → Event Category`, plus `event_staff` sebagai daftar panitia per-event. Organizer bisa: create event (form 3 section) → atur kategori lomba (nama, kuota, min/max anggota, jadwal pendaftaran, urutan) → kelola daftar staff event. Event punya status lifecycle 7-state dengan transisi eksplisit (bukan auto dari tanggal). Semua otorisasi diturunkan dari `events.organization_id → organization_users` — pola identik `TeamPolicy`, tidak ada mekanisme authz baru.

**Requirement ID:** `FR-EVENT-01`

**Milestone Phase A (selesai setelah sprint ini):** organizer login → create organization → create event → setup category.

---

## Context

Sprint 1 (Organization/Tenancy, 41 test) + Sprint 2 (Team/School Directory, +33 test = 74) sudah PASS-review dan jadi fondasi tenant isolation yang terbukti. Sprint 3 menambah domain Event di atasnya. `events.organization_id` **NOT NULL** (tidak ada konsep "event tanpa organizer") — jadi tidak ada celah tenant-ownership seperti yang ditemukan di Sprint 2 (`teams.organization_id` nullable). Tenant check langsung: event milik org X, hanya member aktif org X yang bisa akses.

Prasyarat: Sprint 1-2 selesai (ada `organizations`, `organization_users`, pola `OrganizationPolicy`/`TeamPolicy`/`OrganizationService`/`TeamService`).

Scope dibatasi eksplisit ke Sprint 3. **TIDAK** dikerjakan di sprint ini: Registration/Participant (Sprint 4), Competition Entry/Number (Sprint 5), Schedule (Sprint 6), Judging (Sprint 7+), Results (Sprint 11+), Public Event Page (DEFERRED, ADR-002), Event Series (lihat Keputusan D), integrasi Kreenconnect (DEFERRED).

---

## Keputusan Desain Kritis (didokumentasikan sebelum coding)

Semua di bawah adalah pilihan yang bisa dibalik murah nanti, tapi ditulis eksplisit supaya tidak jadi asumsi diam-diam (Prinsip Karpathy #1). **Butuh konfirmasi Ersa.**

### A. `event_staff` = tabel + CRUD, TAPI belum jadi sumber otorisasi
`event_staff` dibuat (tabel + CRUD) di sprint ini sesuai daftar `BACKEND-HANDOFF.md` Sprint 3. Kolom `role` (`event_admin/registration_staff/competition_staff/scoring_admin/viewer`) **dicatat tapi tidak nge-gate apa pun** di Sprint 3. Semua Policy (Event/EventCategory/EventStaff) menurunkan izin dari `events.organization_id → organization_users` (member aktif = lihat; owner/admin = ubah) — **persis pola `TeamPolicy`**. Role `event_staff` baru dipakai sebagai gate di sprint yang membutuhkannya (`registration_staff` → Sprint 4, `scoring_admin` → Sprint 9-10).
**Alasan:** menghindari dua mekanisme authz setengah-jadi sebelum dibutuhkan (Constitution + Karpathy #2). Sama seperti keputusan Sprint 2 "`team_members` = roster, bukan authorization".
**Konsekuensi:** sprint yang nanti pakai `event_staff.role` sebagai gate **wajib** menambah cek itu eksplisit + test-nya sendiri — jangan asumsikan sudah ditegakkan di Sprint 3.

### B. Status Event: 7-state penuh sesuai blueprint, transisi eksplisit
`draft · published · registration_open · registration_closed · ongoing · finished · cancelled`. Schema sejalan `research/chatgpt-blueprint-v1.md` §9 — tidak perlu migrasi ubah enum saat public site (DEFERRED) dikerjakan. Selama belum ada halaman publik, `published` = "event dikonfirmasi & diumumkan internal" (bukan "tayang ke publik").
Transisi **manual & eksplisit** lewat 1 action (`PATCH .../status`), bukan auto dari tanggal (`dev-checklist` §553 "sistem boleh kasih suggestion, stored status tetap eksplisit"). Peta transisi yang diizinkan:
```text
draft                → published, cancelled
published            → registration_open, draft, cancelled
registration_open    → registration_closed, cancelled
registration_closed  → registration_open, ongoing, cancelled
ongoing              → finished, cancelled
finished             → (terminal)
cancelled            → draft            (undo — aman di Sprint 3, belum ada data turunan)
```
`published_at` di-set saat pertama kali masuk `published`, tidak di-reset saat keluar-masuk lagi. Transisi di luar peta → `422` (domain exception di `EventService`, bukan cek di controller).

### C. Poster upload DITUNDA (kolom ada, wiring belanti)
Kolom `poster_path` ada di schema (sejalan blueprint), tapi **form create/edit Sprint 3 tidak punya field poster** dan tidak ada pipeline upload. Sama persis keputusan `fr-organization-tenancy.md` untuk `logo_path`: "hindari membangun infra upload untuk satu field yang belum dipakai". Upload (MIME validation, size limit, disk `public`, `storage:link`) diimplementasikan sekali nanti saat komponen upload dibangun untuk beberapa kebutuhan sekaligus (poster event, logo org, `event_documents` Sprint 6).

### D. Event Series: tabel + kolom `event_series_id` TIDAK dibuat sekarang
`BACKEND-HANDOFF.md` Sprint 3 hanya menyebut `events, event_categories, event_staff` — bukan `event_series`. `brd-core.md`: "Event Series tetap opsional di skema, UI-nya boleh menyusul". Keputusan: **omit tabel `event_series` DAN kolom `events.event_series_id`** sekarang. Keduanya ditambahkan bareng (satu migrasi) saat fitur Event Series benar-benar dikerjakan — menambah FK column nullable ke tabel existing itu migrasi backward-compatible yang aman (`release-checklist.md`).

### E. Kolom `latitude`/`longitude` TIDAK dibuat sekarang
Blueprint punya keduanya, tapi murni untuk tampilan peta di public event page (DEFERRED). Nol kegunaan di Admin v1. Pola sama `fr-team-directory.md` §"Sengaja TIDAK diindex" — jangan tambah yang belum dipakai. `venue_name/venue_address/province_code/city_code` **tetap dibuat** (dipakai di form create + list Admin v1).

### F. Siapa boleh create Event
`EventPolicy::create` = `true` untuk semua user terautentikasi; `organization_id` di request divalidasi ∈ organisasi tempat user jadi **member aktif** (`Rule::in`, di Form Request — pola sama `StoreTeamRequest`). Artinya **member biasa pun boleh create event** di organisasinya. `update` / `updateStatus` / kelola kategori & staff → hanya `owner`/`admin`. Konsisten dengan Sprint 2 (Team).

### G. Tidak ada route `destroy` untuk Event; pakai transisi `cancelled`
Sprint 1 (`organizations`) & Sprint 2 (`teams`) sama-sama `Route::resource(...)->except(['destroy'])`. Sprint 3 ikut: **tidak ada hard delete event**. "Batalkan event" = transisi status ke `cancelled` (soft, reversible). Hard delete draft-tanpa-turunan dipertimbangkan nanti kalau benar-benar diminta. `event_categories` **boleh** di-`destroy` (kategori salah ketik saat setup) — guard "tak bisa hapus kategori yang punya registration" ditambah di Sprint 4 saat registration ada.

### H. Kategori & Staff diakses via Policy sendiri, otorisasi diturunkan dari Event
`EventCategoryPolicy` + `EventStaffPolicy` dibuat (dipanggil dari controller & `@can` di Blade), tapi isinya cuma "turunkan role dari `category->event->organization` / `staff->event->organization`". `dev-checklist` §"Required Policies" memang mendaftarkan `EventCategoryPolicy` terpisah. Setiap action kategori/staff: `authorize(...)` + assert child benar-benar milik `{event}` di route (pola `assertMemberBelongsToTeam` dari `TeamMemberController`).

---

## Goals & Non-Goals

**Goals:**
- Organizer (member aktif org) bisa CRUD Event dalam tenant context organisasinya (kecuali hard delete — lihat Keputusan G)
- Status lifecycle event dengan transisi tervalidasi
- CRUD Event Category per event (nama, code, kuota, min/max members, jadwal pendaftaran, display_order, status)
- CRUD Event Staff per event (user + role), user harus member org event tsb
- Tenant isolation Event/Category/Staff semutu Sprint 1-2 (termasuk manipulasi `organization_id`/`event_id` di payload & route binding)

**Non-Goals (Sprint 3):**
- Public Event Page (`/events/{slug}`) — DEFERRED (ADR-002)
- Registration, Competition Entry, Schedule, Judging, Results — sprint berikutnya
- Event Series (Keputusan D) · Poster upload (C) · lat/long (E) · hard delete event (G)
- `event_staff.role` sebagai sumber otorisasi (A)
- Auto-set status dari tanggal (B)
- Frontend polish penuh (drawer kategori F7, Event Workspace shell F5) — Sprint 3 kasih UI fungsional minimal di halaman event, polish nyusul

---

## Arsitektur & Data Flow

```text
User (member aktif Organization X)
  → Dashboard → Events → Create Event
  → StoreEventRequest: organization_id divalidasi ∈ org aktif user
  → EventService::create(): events row (status=draft, created_by=user, slug dari name) dalam 1 transaction
  → redirect ke Event overview (dashboard.events.show)

User → Event overview → tab Kategori → Add Category
  → EventCategoryPolicy::create (owner/admin org pemilik event) → validasi → event_categories row

User → Event overview → Ubah Status (draft → published)
  → UpdateEventStatusRequest → EventService::changeStatus(): cek peta transisi (Keputusan B)
  → invalid → 422; valid → status berubah (+ published_at kalau perlu)
```

`EventPolicy` meniru `TeamPolicy`: `membershipRole($user, $event)` = role user di `event->organization->memberships` (status aktif). `null` → bukan member → 403.

---

## Perubahan Schema

### `events` (migration 07)
```text
id                     BIGINT UNSIGNED, PK
public_id              string, unique                (HasPublicId, prefix "evt_")
organization_id        BIGINT UNSIGNED, FK → organizations.id, ON DELETE RESTRICT
                        (org tak bisa hard-delete kalau masih punya event — konsisten
                         Constitution "preserve history", pola sama teams)
name                   string
slug                   string, unique                (auto dari name saat create, editable, unique)
short_description      string, nullable               (maks 500 — dipakai di kartu/list)
description            text, nullable
poster_path            string, nullable               (Keputusan C — kolom ada, belum di-wire)
start_date             date
end_date               date
registration_start_at  datetime, nullable
registration_end_at    datetime, nullable
venue_name             string, nullable
venue_address          string, nullable
province_code          string, nullable
city_code              string, nullable
status                 string (enum di app: EventStatus, 7 nilai), default 'draft'
published_at           datetime, nullable
created_by             BIGINT UNSIGNED, FK → users.id, nullable, ON DELETE SET NULL
                        (jejak "siapa buat"; user tak di-hard-delete di sistem ini,
                         SET NULL supaya tak pernah jadi blocker)
created_at, updated_at
```
Index: `unique(public_id)`, `unique(slug)`, `index(organization_id, status)` (query list dashboard).
**Sengaja TIDAK diindex** (belum ada query Sprint 3 yang butuh): `status` sendirian, `start_date`, `city_code`. `(city_code, start_date)` di blueprint itu untuk public discovery (DEFERRED).
**Kolom enum `status`:** disimpan sebagai `string` + `$casts` ke `EventStatus`, **bukan** kolom `ENUM` MySQL — konsisten dengan `organizations.status`/`type` di Sprint 1 (yang juga string + cast). Menghindari `ALTER TABLE ... MODIFY ENUM` yang mahal kalau nilai bertambah.

### `event_categories` (migration 08)
```text
id                     BIGINT UNSIGNED, PK
event_id               BIGINT UNSIGNED, FK → events.id, ON DELETE CASCADE
                        (kategori tanpa event tak berarti; cascade hanya kena event
                         yang boleh dihapus — Sprint 3 tak ada hard delete event,
                         jadi praktis cascade hanya lewat migrasi rollback dev)
name                   string
code                   string, nullable                (mis. "SMA-PA", "MADYA")
description            text, nullable
quota                  int unsigned, nullable
min_members            int unsigned, nullable
max_members            int unsigned, nullable
registration_start_at  datetime, nullable              (override jadwal event-level, opsional)
registration_end_at    datetime, nullable
status                 string (enum di app: Status active|inactive), default 'active'
display_order          int unsigned, default 0
created_at, updated_at
```
Index: `index(event_id, display_order)`, `unique(event_id, code)`.
Catatan MySQL: `unique(event_id, code)` dengan `code` NULL → MySQL izinkan banyak baris `code = NULL` per event (kategori tanpa code boleh banyak; kategori ber-code unik per event). Sesuai keinginan.

### `event_staff` (migration 09)
```text
id          BIGINT UNSIGNED, PK
event_id    BIGINT UNSIGNED, FK → events.id, ON DELETE CASCADE
user_id     BIGINT UNSIGNED, FK → users.id, ON DELETE CASCADE
             (baris ini link penugasan, bukan histori — user hilang = penugasan hilang)
role        string (enum di app: EventStaffRole, 5 nilai)
status      string (enum di app: Status active|inactive), default 'active'
created_at, updated_at
```
Index: `unique(event_id, user_id)` (1 baris per user per event), `index(event_id)`.

---

## Business Logic

```text
RULE-1  organization_id WAJIB & harus organisasi tempat user member aktif (validasi Form Request, Keputusan F).
RULE-2  organization_id TIDAK bisa diubah lewat update (event pindah organizer = di luar scope, pola sama Team).
RULE-3  slug di-generate dari name saat create (Str::slug), editable manual, unique se-tabel.
RULE-4  Event dibuat selalu status = draft. created_by = auth id. published_at = null.
RULE-5  Transisi status hanya lewat EventService::changeStatus() sesuai peta Keputusan B. Di luar peta → InvalidEventStatusTransition (422).
RULE-6  published_at di-set saat transisi pertama ke 'published'; tidak berubah pada transisi berikutnya.
RULE-7  end_date >= start_date (validasi). registration_end_at >= registration_start_at kalau keduanya diisi.
RULE-8  Category: max_members >= min_members kalau keduanya diisi. code unik per event kalau diisi.
RULE-9  Category registration_end_at >= registration_start_at kalau keduanya diisi.
RULE-10 Event Staff: user_id harus member aktif organisasi pemilik event. Tak boleh menambah user acak.
RULE-11 Event Staff: 1 user maksimal 1 baris per event (unique). Ganti role = update baris, bukan insert baru.
RULE-12 Organization tak bisa di-hard-delete kalau masih punya event (FK RESTRICT).
```

## Permission & Access Control

| Aksi | owner | admin | member | Bukan member org pemilik |
|---|---|---|---|---|
| Lihat event / kategori / staff | ✅ | ✅ | ✅ | ❌ (403) |
| Create event (di org-nya) | ✅ | ✅ | ✅ | — |
| Update event | ✅ | ✅ | ❌ | ❌ |
| Ubah status event | ✅ | ✅ | ❌ | ❌ |
| CRUD kategori | ✅ | ✅ | ❌ | ❌ |
| CRUD staff | ✅ | ✅ | ❌ | ❌ |

Diturunkan dari `organization_users` milik `event.organization_id` — `EventPolicy` copy pola `TeamPolicy` persis. `EventCategoryPolicy`/`EventStaffPolicy` turunkan lewat `->event->organization`.

## Validation Rules (ringkas)

| Field | Aturan | Layer |
|---|---|---|
| `organization_id` (store) | required, `Rule::in(org aktif user)` | server |
| `name` | required, string, max 255 | client+server |
| `slug` (store) | nullable, `alpha_dash`, unique:events | server |
| `slug` (update) | required, `alpha_dash`, unique ignore self | server |
| `short_description` | nullable, string, max 500 | server |
| `start_date` / `end_date` | required, date; `end_date >= start_date` | client+server |
| `registration_start_at` / `_end_at` | nullable, date; end `>=` start | server |
| `venue_name/venue_address/province_code/city_code` | nullable, string | server |
| `status` (updateStatus) | required, `Rule::enum(EventStatus)`; transisi dicek di service | server |
| category `name` | required, max 255 | server |
| category `code` | nullable, `alpha_dash`, max 50, unique per `event_id` | server |
| category `quota/min_members/max_members` | nullable, integer, min 1; `max_members >= min_members` | server |
| category `display_order` | nullable, integer, min 0 | server |
| staff `user_id` | required, exists, harus member aktif org event, unique per `event_id` | server |
| staff `role` | required, `Rule::enum(EventStaffRole)` | server |

## Testing Plan (Feature tests, MySQL, RefreshDatabase)

```text
Event/CreateEventTest
  □ member create event di org-nya → draft + created_by + redirect ke show
  □ name / start_date / end_date required
  □ end_date < start_date ditolak
  □ organization_id required; tak bisa attach ke org asing (payload manip) → error
  □ organization_id invalid (id ngawur) → error
  □ slug duplikat ditolak
  □ guest → redirect login

Event/UpdateEventTest
  □ owner update; member role tak bisa update (403)
  □ organization_id tak bisa diubah lewat payload
  □ slug unique ignore self

Event/EventStatusTest
  □ draft→published set published_at; published_at tak berubah saat published→draft→published lagi
  □ transisi invalid (draft→finished) → 422, status tak berubah
  □ ongoing→cancelled OK; finished = terminal (finished→apapun → 422)
  □ member role tak bisa ubah status (403)

Event/EventCategoryTest
  □ owner add category; member view OK tapi mutate → 403
  □ code duplikat dalam 1 event ditolak; code sama di event beda OK
  □ max_members < min_members ditolak
  □ delete category OK
  □ category event B tak reachable via URL event A (route binding + assert belongs)

Event/EventStaffTest
  □ owner add staff (user = member org) OK
  □ tambah user non-member org sebagai staff → ditolak
  □ user duplikat di event sama → ditolak (unique)
  □ ganti role lewat update; remove staff
  □ member role tak bisa manage staff (403)
  □ staff event B tak manipulable via URL event A

Event/TenantIsolationTest  (adversarial, pola sama Team/TenantIsolationTest)
  □ user A tak bisa view/update event B (direct URL, edit form, PUT payload, route binding)
  □ user A tak bisa add kategori/staff ke event B
  □ user A tak bisa ubah status event B
  □ index events hanya tampilkan event dari org user
  □ guest → redirect login untuk semua route event

Event/DatabaseIntegrityTest
  □ FK RESTRICT: organization tak bisa dihapus kalau masih punya event
  □ CASCADE: hapus event → kategori & staff ikut terhapus (uji lewat model, bukan route)
  □ unique: slug, (event_id, code), (event_id, user_id)
  □ migration up() lalu down() jalan bersih
```

## File yang HARUS Diubah / Dibuat

| File | Perubahan |
|---|---|
| `database/migrations/*_create_events_table.php` | baru (07) |
| `database/migrations/*_create_event_categories_table.php` | baru (08) |
| `database/migrations/*_create_event_staff_table.php` | baru (09) |
| `app/Enums/EventStatus.php` | baru (7 case) |
| `app/Enums/EventStaffRole.php` | baru (5 case) |
| `app/Models/Event.php`, `EventCategory.php`, `EventStaff.php` | baru (model + relasi + casts + HasPublicId di Event) |
| `app/Policies/EventPolicy.php`, `EventCategoryPolicy.php`, `EventStaffPolicy.php` | baru (pola `TeamPolicy`) |
| `app/Services/EventService.php` | baru (`create`, `update`, `changeStatus`, `assignableOrganizationIds` — mirror TeamService) |
| `app/Exceptions/InvalidEventStatusTransition.php` | baru (→ 422) |
| `app/Http/Requests/Event/*` | `StoreEventRequest`, `UpdateEventRequest`, `UpdateEventStatusRequest`, `StoreEventCategoryRequest`, `UpdateEventCategoryRequest`, `StoreEventStaffRequest`, `UpdateEventStaffRequest` |
| `app/Http/Controllers/Dashboard/EventController.php` | baru (`index/create/store/show/edit/update/updateStatus`) |
| `app/Http/Controllers/Dashboard/EventCategoryController.php` | baru (`index/store/update/destroy`) |
| `app/Http/Controllers/Dashboard/EventStaffController.php` | baru (`index/store/update/destroy`) |
| `routes/web.php` | +resource `events` (except destroy) + `events/{event}/status` + nested `categories` & `staff`; **hapus** `dev/events` + import `EventPrototypeController` |
| `app/Http/Controllers/Dev/EventPrototypeController.php` | **hapus** (diganti controller asli) |
| `database/factories/EventFactory.php`, `EventCategoryFactory.php`, `EventStaffFactory.php` | baru |
| `resources/views/dashboard/events/index.blade.php` | rewire ke data nyata (buang mock; kolom Event/Tanggal/Venue/Status/Kategori — Peserta nyusul Sprint 4) |
| `resources/views/dashboard/events/create.blade.php` | rewire ke `dashboard.events.store`; **buang field poster** (Keputusan C) |
| `resources/views/dashboard/events/edit.blade.php` | baru (form edit, pola create minus organization) |
| `resources/views/dashboard/events/show.blade.php` | jadi Event overview nyata — meta + kontrol ubah status + tab Overview/Kategori/Staff (fungsional minimal; polish F5/F7) |
| `resources/views/dashboard/events/partials/*` | `category-form`, `staff-form` inline (pola `teams/partials/roster`) |
| `resources/views/components/layout/sidebar.blade.php` + `mobile-nav.blade.php` | +item "Events" (backend live sekarang — `frontend-plan.md` §2) |
| `_docs/tasks/tasks.md` | Sprint 3 → selesai |
| `_docs/README.md` | daftar SRS +`fr-event-category.md` |

## Security Considerations

- [ ] `organization_id` di request tidak dipercaya mentah — divalidasi user member aktif (RULE-1)
- [ ] Tenant isolation Event/Category/Staff diuji seketat Sprint 1-2 (direct URL, edit form, payload manip, route binding)
- [ ] `event_staff.user_id` divalidasi member org — tak bisa nge-inject user luar sebagai staff (potensi privilege escalation nanti kalau role jadi gate)
- [ ] Mass assignment: `$fillable` eksplisit di semua model; `status`, `organization_id`, `created_by`, `published_at` **tidak** di-fill dari request (di-set service)
- [ ] `EventPolicy` gate SEMUA action write; tidak ada endpoint event tanpa `authorize()`
- [ ] Blade `{{ }}` untuk semua output user-generated (nama event, deskripsi, dst)

## Open Questions

| Pertanyaan | Penjawab | Status |
|---|---|---|
| Konfirmasi Keputusan A-H | Ersa | ✅ Dikonfirmasi 2026-09-10 ("lanjut aja"). H direvisi saat implementasi — lihat §Catatan Implementasi |
| Response 403 vs 404 untuk akses cross-tenant | cek kode Sprint 1-2 | ✅ Policy deny → 403; child tak-milik-parent → 404 |

## Review Checklist

- [ ] TL;DR jelas tanpa baca seluruh dokumen
- [ ] Semua file yang diubah terdaftar
- [ ] Business Logic, Permission, Validation, Testing Plan terisi
- [ ] Tidak melanggar Constitution (tenant isolation, no public page, no hard-delete history, English names, DECIMAL)
- [ ] Reviewer kedua (Ersa) sudah baca §Keputusan Desain Kritis dan setuju — **belum**
