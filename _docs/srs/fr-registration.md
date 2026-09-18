# Design Doc — Competition Registration (Admin-Entry Mode) (Phase B, Sprint 4)

**Status:** Implemented (2026-09-10) — 40 test hijau di MySQL (total 159), Pint clean, migrasi up/down bersih. **CSV/Excel import = Sprint 4b (belum).**
**Author:** Claude
**Tanggal:** 2026-09-10
**Berkaitan dengan BRD:** `_docs/brd/brd-core.md` · **Kontrak:** `BACKEND-HANDOFF.md` §Sprint 4 · **ADR:** ADR-002 (admin-first)
**Definition of Ready:** ✅ — Sprint 1-3 selesai, MySQL lokal jalan. CSV import dipisah ke Sprint 4b (keputusan Ersa 2026-09-10).

## Catatan Implementasi

- **`file_path` tidak masuk `$fillable`** `RegistrationDocument` (server-computed path, bukan input). Controller `store` bikin instance lalu set `->file_path` eksplisit + `->documents()->save()`, bukan `->create([...'file_path'...])` (yang bakal di-drop diam-diam → NOT NULL error).
- **Dokumen disk `local`** = `storage/app/private` (Laravel 11+ default). Route bawaan `storage.local` (`GET storage/{path}`) butuh signed URL, jadi bukan bypass — tapi tetap pakai route `registrations/{registration}/documents/{document}` yang di-`authorize('view', $registration)` untuk download (bukan `Storage::url()`).
- **Editable guard** = `abort_unless($registration->isEditable(), 403)` di controller (roster/dokumen). `RegistrationPolicy` cuma authz (owner/admin); editability dicek terpisah. Test roster/dokumen "submitted → 403".
- **Invalid transisi status** = validation error field `status` (302 back), sama pola `EventController::updateStatus` / Sprint 3.
- **UI Sprint 4 fungsional, bukan F6 penuh:** `registrations/index` (table/card + filter status+kategori), `create` (team+kategori), `show` (meta + status control + review_notes + roster + dokumen sections). Tab "Peserta (N)" di `events/show` → link ke `registrations.index`. Roster add = input teks (tanpa picker `team_member` — provenance link tetap didukung backend + test; picker = F6). Per-document approve/reject = ditunda (Keputusan F).
- **`flash-messages.blade.php` +varian `error`** (merah) — dipakai guard hapus kategori ber-registrasi.
- **`status-badge`** diperluas: 6 status registrasi (`submitted`→info, `approved`→success, `revision_requested`→warning, `rejected`→danger, `withdrawn`/`draft`→neutral).

---

> ⚠️ Constitution (`CLAUDE.md`) yang relevan Sprint 4:
> **Roster peserta di-snapshot per registration** (bukan sekadar refer `team_members`) ·
> Registration/competition history **tidak boleh hilang akibat cascade delete** — pakai status `withdrawn`, bukan hard delete ·
> Tenant isolation via Policy di setiap resource ber-`event_id` ·
> Dokumen registrasi **private** — tidak boleh punya direct public URL (`security/security-baseline.md`) ·
> **Mode admin-entry saja** — panitia yang input, BUKAN peserta daftar sendiri. Tidak ada login/form peserta.

## TL;DR

Domain `Event → Event Category → Registration → {Roster Members (snapshot), Documents (private)}`. Panitia (owner/admin org penyelenggara) input peserta: pilih/attach Team → pilih Category → isi roster (di-snapshot) → upload dokumen wajib (disk privat) → Submit → Review → Approve / Reject / Request Revision. Status lifecycle 6-state dengan transisi eksplisit (pola sama `EventStatus` Sprint 3). `submitted_by` didesain generik supaya self-registration nanti dipasang tanpa migrasi skema.

**Requirement ID:** `FR-REG-01`

**Milestone Phase B (selesai setelah sprint ini + 4b):** panitia kelola seluruh data peserta dari Siapgrak tanpa spreadsheet eksternal, termasuk approval workflow.

---

## Context

Sprint 3 (Event + Category + Staff, 45 test) memberi `events` + `event_categories`. Sprint 4 menambah lapis registrasi di atasnya. **Admin-entry only** (ADR-002): tidak ada UI/route/auth untuk peserta. `submitted_by` = staff yang input; kolomnya nullable FK ke `users` dan tidak diasumsikan "selalu perwakilan tim" — begitu self-registration (DEFERRED) dikerjakan, tinggal isi `submitted_by` dengan user peserta, tanpa ubah skema.

Scope dibatasi ke Sprint 4. **TIDAK** dikerjakan: Import CSV/Excel (Sprint 4b), Competition Entry / Competition Number (Sprint 5 — termasuk auto-create entry saat approve), Schedule (Sprint 6), per-document approve/reject workflow (lihat Keputusan F), quota enforcement (Keputusan H), self-registration & public pages (DEFERRED).

---

## Keputusan Desain Kritis

Semua reversible murah; ditulis eksplisit (Karpathy #1). Grounding: blueprint §9, dev-checklist Phase 2, precedent Sprint 1-3.

### A. Admin-entry only — `submitted_by` generik
Tidak ada route/controller/view untuk peserta. `RegistrationService::create` set `submitted_by = auth()->id()` (staff). Kolom `submitted_by` nullable FK `users` SET NULL. Tidak ada logika yang mengasumsikan `submitted_by` = pemilik team / perwakilan. Self-registration nanti: layer auth peserta + form publik di atas engine yang sama.

### B. Status lifecycle — 6 state, transisi eksplisit
`draft · submitted · revision_requested · approved · rejected · withdrawn`. Transisi lewat 1 action (`PATCH .../status`), peta di `RegistrationService`:
```text
draft               → submitted, withdrawn
submitted           → approved, rejected, revision_requested, withdrawn
revision_requested  → submitted, withdrawn
approved            → withdrawn
rejected            → submitted, withdrawn
withdrawn           → draft            (reaktivasi — aman di Sprint 4, belum ada turunan;
                                        menjaga UNIQUE (event_category_id, team_id) tetap konsisten
                                        tanpa perlu baris baru)
```
- `submitted_at` di-set saat pertama kali → `submitted`, tidak di-reset.
- `reviewed_at` + `reviewed_by` di-set saat → `approved` / `rejected` / `revision_requested`.
- `review_notes` **wajib** saat → `rejected` / `revision_requested` (`required` di Form Request).
- Transisi di luar peta → `InvalidRegistrationStatusTransition` → di-catch jadi validation error di field `status` (pola sama `EventController::updateStatus`).

### C. Editable hanya saat `draft` / `revision_requested`
- `team_id` + `event_category_id` **fixed saat create** (tidak editable — pola sama `organization_id` di Event). Ganti team/kategori = withdraw + buat baru.
- Roster & dokumen: tambah/hapus **hanya** saat status ∈ {`draft`, `revision_requested`}. Setelah `submitted` → dikunci. Guard di controller: `abort_unless($registration->isEditable(), 403)`.
- Registrasi sendiri tidak punya field yang diedit langsung → **tidak ada route `edit`/`update`** untuk `registrations`. Resource = `index/create/store/show` + `status` + nested roster/dokumen.

### D. Roster di-snapshot
`registration_members` menyimpan `name` / `member_role` / `gender` sebagai **snapshot** + `team_member_id` nullable (provenance saja, SET NULL on delete). Saat admin tambah anggota roster, bisa pre-fill dari `team_members` team tsb ATAU ketik manual. Perubahan/penghapusan `team_members` **tidak pernah** menyentuh `registration_members`. Diuji eksplisit.

### E. Dokumen private
`registration_documents.file_path` → disk **`local`** (privat, `storage/app/private`), **bukan** `public`. Upload: `mimes:pdf,jpg,jpeg,png`, `max:5120` (5 MB). Download lewat route khusus `GET registrations/{registration}/documents/{document}` → `authorize('view', $registration)` → `Storage::disk('local')->download()`. Tidak ada URL publik/permanen. `destroy` menghapus file fisik juga. (`security/security-baseline.md` §File Upload.)

### F. `document_type` string bebas; per-document review DITUNDA
`document_type` string `max:100` (mis. `surat_tugas`, `kartu_pelajar`, `pakta_integritas`) — beda event beda kebutuhan dokumen. UI kasih `<datalist>` saran. Kolom `registration_documents.status` (`uploaded`/`approved`/`rejected`, default `uploaded`) **dibuat** tapi endpoint/UI approve-reject **per dokumen ditunda** — review level-registrasi (`approved`/`rejected`/`revision_requested` + `review_notes`) sudah menutup milestone. Pola sama penundaan toggle status kategori di Sprint 3.

### G. Otorisasi: owner/admin org penyelenggara saja
Semua mutasi registrasi (create, status, roster, dokumen) + review → **owner/admin** organisasi pemilik `event`. `member` boleh **view**. Gate lewat `EventPolicy::manageRegistrations($user, $event)` (tambah method, pola `manageCategories`) untuk create/list, dan `RegistrationPolicy` (`view`/`updateStatus`) untuk level-registrasi. **`event_staff.role = registration_staff` BELUM jadi gate** (konsisten Keputusan A `fr-event-category.md`) — mengaktifkannya = tambahan tersendiri ke `RegistrationPolicy` di sprint mendatang, dengan test-nya sendiri.

### H. Quota tidak ditegakkan di Sprint 4
`event_categories.quota` disimpan + ditampilkan ("N / quota") tapi **tidak** memblok registrasi baru. Penegakan butuh definisi jelas "apa yang dihitung" (submitted? approved? draft?) — ditunda.

### I. Tidak ada hard delete registrasi
`Route::resource('...registrations')->except(['destroy'])`. "Hapus peserta" = status `withdrawn`. `event`/`event_category`/`team` yang sudah punya registrasi → **FK RESTRICT** (tak bisa hard delete). `EventCategoryController::destroy` (Sprint 3) ditambah guard ramah: tolak + flash error kalau kategori sudah punya registrasi (bukan lempar `QueryException` mentah). Roster member & dokumen boleh dihapus **hanya saat registrasi editable**.

### J. UNIQUE (event_category_id, team_id) absolut
Sesuai blueprint. Satu team tak bisa daftar 2x ke kategori sama. Kalau team pernah `withdrawn` lalu mau ikut lagi: reaktivasi baris lama (`withdrawn → draft`, Keputusan B), bukan baris baru. Pesan validasi jelas kalau coba create duplikat.

### K. Auto-create `competition_entry` saat approve = Sprint 5
Blueprint/dev-checklist bilang approve → langsung create `competition_entry`. Tabel itu Sprint 5. Sprint 4: approve cuma set `status = approved`. Hook pembuatan entry ditambah di `RegistrationService::changeStatus` saat Sprint 5. (Forward-reference, bukan gap.)

---

## Goals & Non-Goals

**Goals:**
- Panitia (owner/admin) input registrasi peserta: attach team + kategori, isi roster (snapshot), upload dokumen (privat)
- Lifecycle registrasi tervalidasi (draft → submitted → review outcome), `review_notes` wajib saat reject/revision
- Tenant isolation Registration/Roster/Document semutu Sprint 1-3
- Dokumen tidak pernah bocor lewat URL publik

**Non-Goals (Sprint 4):** Import CSV/Excel (4b) · Competition Entry/Number (5) · per-document review (F) · quota enforcement (H) · `registration_staff` sebagai gate (G) · self-registration / public (DEFERRED) · frontend polish penuh F6 (Sprint 4 kasih UI fungsional)

---

## Perubahan Schema

### `registrations` (migration 10)
```text
id                  BIGINT UNSIGNED, PK
public_id           string, unique                     (HasPublicId, prefix "reg_")
event_id            BIGINT UNSIGNED, FK → events.id,            ON DELETE RESTRICT
event_category_id   BIGINT UNSIGNED, FK → event_categories.id,  ON DELETE RESTRICT
team_id             BIGINT UNSIGNED, FK → teams.id,             ON DELETE RESTRICT
submitted_by        BIGINT UNSIGNED, FK → users.id, nullable,   ON DELETE SET NULL
status              string (enum app: RegistrationStatus, 6), default 'draft'
submitted_at        datetime, nullable
reviewed_at         datetime, nullable
reviewed_by         BIGINT UNSIGNED, FK → users.id, nullable,   ON DELETE SET NULL
review_notes        text, nullable
created_at, updated_at
```
Index: `unique(public_id)`, **`unique(event_category_id, team_id)`** (Keputusan J), `index(event_id, status)`.

### `registration_members` (migration 11)
```text
id               BIGINT UNSIGNED, PK
registration_id  BIGINT UNSIGNED, FK → registrations.id, ON DELETE CASCADE
team_member_id   BIGINT UNSIGNED, FK → team_members.id, nullable, ON DELETE SET NULL
name             string
member_role      string, nullable
gender           string, nullable
created_at, updated_at
```
Index: `index(registration_id)`. **Snapshot** — `name`/`member_role`/`gender` adalah source of truth, `team_member_id` cuma jejak asal.

### `registration_documents` (migration 12)
```text
id               BIGINT UNSIGNED, PK
registration_id  BIGINT UNSIGNED, FK → registrations.id, ON DELETE CASCADE
document_type    string                             (bebas, mis. "surat_tugas")
title            string, nullable
file_path        string                             (path di disk 'local' — privat)
status           string (enum app: RegistrationDocumentStatus), default 'uploaded'
review_notes     text, nullable
created_at, updated_at
```
Index: `index(registration_id)`.

---

## Business Logic

```text
RULE-1  team_id harus team di salah satu organisasi tempat user member aktif (validasi Form Request).
RULE-2  event_category_id harus milik {event} dari route (validasi: exists where event_id = event.id).
RULE-3  UNIQUE (event_category_id, team_id) — ditegakkan DB + pesan validasi app.
RULE-4  create selalu status=draft, submitted_by=auth id, submitted_at/reviewed_* null.
RULE-5  team_id & event_category_id tidak editable setelah create.
RULE-6  Roster/dokumen: tambah/hapus hanya saat status ∈ {draft, revision_requested}.
RULE-7  Transisi status hanya lewat RegistrationService::changeStatus(), sesuai peta Keputusan B.
RULE-8  → rejected / revision_requested wajib review_notes.
RULE-9  submitted_at di-set sekali (transisi pertama ke submitted). reviewed_at/by di-set tiap review outcome.
RULE-10 Dokumen disimpan di disk 'local'; akses hanya lewat route ber-Policy; destroy hapus file fisik.
RULE-11 registration_members.name di-snapshot; ubah/hapus team_members tidak mengubahnya.
RULE-12 event / event_category / team dengan registrasi → tak bisa hard delete (FK RESTRICT).
```

## Permission & Access Control

| Aksi | owner | admin | member | Non-member org penyelenggara |
|---|---|---|---|---|
| Lihat registrasi / roster / dokumen (+ download) | ✅ | ✅ | ✅ | ❌ (403) |
| Create registrasi | ✅ | ✅ | ❌ | ❌ |
| Ubah status (submit/approve/reject/revision/withdraw) | ✅ | ✅ | ❌ | ❌ |
| Tambah/hapus roster & dokumen | ✅ | ✅ | ❌ | ❌ |

Diturunkan dari `organization_users` milik `registration.event.organization_id`. `RegistrationPolicy` copy pola `EventPolicy`. `event_staff.role` belum berpengaruh (Keputusan G).

## Validation Rules (ringkas)

| Field | Aturan |
|---|---|
| `team_id` (store) | required, `Rule::in(team di org aktif user)` |
| `event_category_id` (store) | required, `exists:event_categories,id` where `event_id` = route event; UNIQUE cek `(event_category_id, team_id)` |
| roster `name` | required, string, max 255 |
| roster `member_role` / `gender` | nullable, string |
| roster `team_member_id` | nullable, harus anggota `team` milik registrasi |
| doc `document_type` | required, string, max 100 |
| doc `title` | nullable, string, max 255 |
| doc `file` | required, file, `mimes:pdf,jpg,jpeg,png`, `max:5120` |
| status `status` | required, `Rule::enum(RegistrationStatus)`; transisi dicek di service |
| status `review_notes` | `required_if:status,rejected,revision_requested`, string |

## Testing Plan (Feature, MySQL, RefreshDatabase, `Storage::fake('local')` untuk dokumen)

```text
Registration/CreateRegistrationTest
  □ owner create → status draft + submitted_by + redirect ke show
  □ team_id required; team dari org asing ditolak; category harus milik event
  □ duplikat (event_category_id, team_id) ditolak (pesan app, bukan QueryException mentah)
  □ member role tak bisa create (403); guest → login

Registration/RegistrationLifecycleTest
  □ draft→submitted set submitted_at (sekali, tak reset saat withdrawn→draft→submitted lagi)
  □ submitted→approved set reviewed_at/by
  □ submitted→rejected / →revision_requested tanpa review_notes → error
  □ rejected→submitted, revision_requested→submitted OK
  □ withdrawn→draft (reaktivasi) OK
  □ transisi invalid (draft→approved, approved→draft) → error, status tak berubah
  □ member role tak bisa ubah status (403)

Registration/RegistrationRosterTest
  □ tambah anggota roster saat draft → snapshot tersimpan
  □ snapshot bertahan walau team_member sumbernya diubah/dihapus
  □ tak bisa tambah roster saat submitted (403); hapus roster saat draft OK
  □ member role tak bisa manage roster (403)
  □ team_member_id dari team lain ditolak

Registration/RegistrationDocumentTest
  □ upload (Storage::fake) saat draft → file di disk 'local', row tersimpan
  □ mime/size invalid ditolak
  □ download oleh owner → 200 + file; oleh outsider → 403
  □ tak bisa upload saat submitted (403)
  □ destroy hapus row + file fisik
  □ member role tak bisa upload (403)

Registration/TenantIsolationTest (adversarial)
  □ user A tak bisa view/create/status/roster/dokumen di registrasi event B
  □ user A tak bisa download dokumen registrasi event B (403)
  □ registrasi index event A hanya tampil registrasi org A
  □ child (member/document) tak-milik-parent → 404
  □ guest → login untuk semua route

Registration/DatabaseIntegrityTest
  □ UNIQUE (event_category_id, team_id)
  □ FK RESTRICT: event / event_category / team tak bisa hard-delete saat punya registrasi
  □ CASCADE: hapus registrasi → registration_members + registration_documents ikut
  □ public_id prefix "reg_"
  □ migration up() + down() bersih

Event/EventCategoryTest (tambahan Sprint 3)
  □ hapus kategori yang punya registrasi → ditolak + flash error (bukan 500)
```

## File yang HARUS Dibuat / Diubah

| File | Perubahan |
|---|---|
| `database/migrations/*_create_registrations_table.php` (10) | baru |
| `database/migrations/*_create_registration_members_table.php` (11) | baru |
| `database/migrations/*_create_registration_documents_table.php` (12) | baru |
| `app/Enums/RegistrationStatus.php`, `RegistrationDocumentStatus.php` | baru |
| `app/Models/Registration.php`, `RegistrationMember.php`, `RegistrationDocument.php` | baru (relasi, casts, `HasPublicId` di Registration, `isEditable()`) |
| `app/Models/EventCategory.php`, `Event.php`, `Team.php` | +relasi `registrations()` (untuk guard hapus & FK check) |
| `app/Policies/RegistrationPolicy.php` | baru (pola `EventPolicy`) |
| `app/Policies/EventPolicy.php` | +`manageRegistrations($user, $event)` |
| `app/Services/RegistrationService.php` | baru (`create`, `changeStatus` + peta, `allowedTransitions`) |
| `app/Exceptions/InvalidRegistrationStatusTransition.php` | baru |
| `app/Http/Requests/Registration/*` | `StoreRegistrationRequest`, `UpdateRegistrationStatusRequest`, `StoreRegistrationMemberRequest`, `StoreRegistrationDocumentRequest` |
| `app/Http/Controllers/Dashboard/EventRegistrationController.php` | baru (`index/create/store/show/updateStatus`) |
| `app/Http/Controllers/Dashboard/RegistrationMemberController.php` | baru (`store/destroy`) |
| `app/Http/Controllers/Dashboard/RegistrationDocumentController.php` | baru (`store/download/destroy`) |
| `app/Http/Controllers/Dashboard/EventCategoryController.php` | +guard hapus kategori ber-registrasi |
| `routes/web.php` | +nested `events/{event}/registrations` (index/create/store/show) + `.../status` + `registrations/{registration}/members` + `registrations/{registration}/documents(/{document})` |
| `resources/views/partials/flash-messages.blade.php` | +varian `error` (merah) |
| `database/factories/RegistrationFactory.php`, `RegistrationMemberFactory.php`, `RegistrationDocumentFactory.php` | baru |
| `resources/views/dashboard/events/show.blade.php` | +tab "Peserta (N)" → link ke registrations.index |
| `resources/views/dashboard/registrations/{index,create,show}.blade.php` | baru (UI fungsional; polish F6) |
| `resources/views/dashboard/registrations/partials/*` | roster + documents section |
| `tests/Feature/Registration/*` | 6 file (lihat Testing Plan) |
| `_docs/tasks/tasks.md`, `_docs/README.md`, `_docs/status/log.md`, `_docs/status/roadmap-to-mvp.md` | update status |

## Security Considerations

- [ ] Dokumen di disk `local` (privat) — tidak pernah di `public`, tidak ada `Storage::url()` permanen
- [ ] Download dokumen lewat route ber-`authorize('view', $registration)` — outsider 403, diuji
- [ ] `team_id` divalidasi milik org user — tak bisa attach team asing
- [ ] `submitted_by` / `reviewed_by` di-set server (auth id), tak pernah dari request
- [ ] Mass assignment: `$fillable` eksplisit; `status`/`submitted_by`/`submitted_at`/`reviewed_*` bukan fillable
- [ ] MIME + size limit di upload; nama file di-generate (`Str::ulid()`), bukan pakai nama asli mentah
- [ ] Tenant isolation Registration diuji adversarial (pola `Event/TenantIsolationTest`)

## Open Questions

| Pertanyaan | Penjawab | Status |
|---|---|---|
| CSV/Excel import Sprint 4 vs 4b | Ersa | ✅ Sprint 4b (2026-09-10) — belum dikerjakan |
| Konfirmasi Keputusan A-K | Ersa | ✅ Diproses saat implementasi ("lanjut") — semua ngikut precedent Sprint 1-3 + blueprint |

## Sisa untuk Milestone Phase B penuh

- **Sprint 4b — Import CSV/Excel** (SRS bagian sendiri / `fr-registration-import.md`): upload → column mapping → preview per-baris + validasi → commit (create/attach team, isi roster). Butuh lib parsing.
- Per-document approve/reject (Keputusan F) + team_member picker di roster UI (F6) — nice-to-have, bukan blocker milestone.
