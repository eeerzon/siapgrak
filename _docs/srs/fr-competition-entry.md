# Design Doc — Competition Entry + Competition Number (Phase C, Sprint 5)

**Status:** Implemented (2026-09-11) — 24 test hijau di MySQL (total 183), Pint clean, migrasi up/down bersih.
**Author:** Claude
**Tanggal:** 2026-09-11
**Berkaitan dengan BRD:** `_docs/brd/brd-core.md` · **Kontrak:** `BACKEND-HANDOFF.md` §Sprint 5
**Definition of Ready:** ✅ — Sprint 1-4 selesai (159 test hijau di MySQL). Keputusan di bawah ngikut precedent Sprint 3/4.

## Catatan Implementasi

- **Bug ketemu saat nulis test, bukan di kode aplikasi:** `CompetitionEntry::$fillable` sengaja tidak memasukkan `status`/`competition_number` (di-set service, bukan mass-assignment — sama prinsipnya dengan `RegistrationDocument::file_path` di Sprint 4). Test awal salah pakai `$entry->update(['status' => ...])`/`update(['competition_number' => ...])` untuk nyiapin fixture — di-drop diam-diam oleh guard, 4 test gagal dengan gejala membingungkan (nilai "tidak berubah" padahal harusnya). Root cause dikonfirmasi, diperbaiki jadi assignment langsung + `save()` (persis pola yang dipakai `CompetitionService`/`CompetitionNumberService` yang sebenarnya) — kode aplikasi tidak pernah salah, cuma test-nya.
- **Race condition (Keputusan D)** diimplementasikan (`DB::transaction()` + `lockForUpdate()`), tapi **tidak diuji dengan concurrency sungguhan** — PHPUnit satu proses tidak bisa. Test membuktikan alokasi benar (urut, unik, skip yang sudah bernomor); atomicity-nya sendiri adalah code-review, bukan test-proven. Dicatat eksplisit supaya tidak diklaim lebih dari yang benar-benar diverifikasi.
- **UI**: tab "Kompetisi" ditambah di `events/show.blade.php`, tab ke-4 (setelah Kategori). Mutasi (generate nomor, edit nomor, ubah status) redirect balik ke `events.show` yang me-reset tab Alpine ke Overview — **rough edge yang sama juga ada di tab Kategori/Staff/Peserta sejak Sprint 3/4** (bukan regresi baru), belum di-perbaiki karena butuh solusi konsisten (mis. `?tab=` di URL) yang lebih tepat dikerjakan sekali untuk semua tab bareng F5 (Event Workspace), bukan tambal satu-satu.
- **`performance_order`**: kolom ada, editable manual bareng nomor, tidak ada bulk-generate (Keputusan F) — itu genuinely Sprint 6 (Schedule).

---

> ⚠️ Constitution yang relevan: **race condition penomoran wajib atomic** (`srs/nfr.md` §Data Integrity) · tenant isolation via Policy · tidak ada hard delete data historis (competition entry adalah histori kompetisi).

## TL;DR

`Registration` yang `approved` otomatis dapat `CompetitionEntry` (1:1, dibuat oleh service, bukan tombol manual). Organizer assign nomor peserta — manual per entry atau generate sequential per kategori (prefix/start/digit, atomic via DB transaction+lock). Entry punya status lifecycle hari-H (`registered → checked_in → ready → on_deck → performing → finished`, + `withdrawn`/`disqualified`). UI: tab baru "Kompetisi" di halaman event, dikelompokkan per kategori (isolasi nomor per kategori).

**Requirement ID:** `FR-ENTRY-01`

---

## Context

Sprint 4 (Registration, 40 test) memberi `registrations` dengan status `approved`. Sprint 5 menyambungnya ke domain operasional hari-H: begitu registrasi disetujui, sistem **otomatis** membuat `competition_entries` — organizer tidak perlu bikin ulang manual (BACKEND-HANDOFF eksplisit). Scope dibatasi: **hanya** entry + nomor. **TIDAK** termasuk: Schedule/`event_schedule_slots` (Sprint 6), Judging (Sprint 7+), Event Command Center (DEFERRED).

## Keputusan Desain Kritis

### A. Entry dibuat otomatis saat approve, idempoten
`RegistrationService::changeStatus()` — begitu transisi target `approved`, panggil `CompetitionService::createEntryForRegistration($registration)`. **Idempoten** (`firstOrCreate` by `registration_id`) supaya siklus `approved → withdrawn → draft → submitted → approved` (diizinkan Sprint 4 Keputusan B) tidak membuat entry dobel — cukup satu entry per registrasi selama hidupnya.

### B. Registrasi `withdrawn` → entry ikut `withdrawn` (kalau belum final)
Kalau registrasi yang sudah punya entry ditarik (`approved → withdrawn`), entry-nya di-set `withdrawn` juga (kecuali entry sudah `finished`/`disqualified` — status final tidak diubah diam-diam oleh event registrasi). Mencegah entry "hidup" mengambang tanpa registrasi aktif. Ditambahkan di `CompetitionService::syncForRegistrationWithdrawal()`, dipanggil dari `RegistrationService`.

### C. Competition Number: manual + sequential, konfigurasi TIDAK disimpan
V1 sesuai blueprint: assign manual per entry, atau generate sequential per **kategori** (prefix + start number + digit padding, mis. `A01`). Config generate **tidak disimpan** sebagai kolom/tabel — parameter sekali pakai per klik "Generate" (lean, tidak ada field DB nganggur). Random draw eksplisit belum dikerjakan (blueprint: fase berikutnya).

### D. Generate number ATOMIC — wajib DB transaction + lock
`srs/nfr.md` §Data Integrity: "dua entry tidak boleh dapat nomor sama saat digenerate bersamaan." `CompetitionNumberService::generateSequential()` membungkus seluruh alokasi dalam `DB::transaction()` + `lockForUpdate()` atas baris entry yang belum bernomor di kategori tsb — request kedua yang nyaris bersamaan akan menunggu commit pertama, sehingga tidak pernah melihat baris yang sama sebagai "belum bernomor". **Catatan uji:** PHPUnit satu-proses tidak bisa mensimulasikan concurrency sungguhan; test membuktikan alokasi benar (urut, unik, ter-skip yang sudah bernomor) dan menegaskan implementasi pakai transaction+lock (code review), bukan race sungguhan.

### E. Nomor unik PER KATEGORI, bukan per event
`UNIQUE (event_category_id, competition_number)` — kategori berbeda boleh punya nomor sama (`A01` di SMA Putra ≠ `A01` di SMP Putri). Sesuai blueprint.

### F. `performance_order` kolom ada, belum ada flow khusus
Kolom disiapkan (dipakai penuh di Sprint 6 Schedule). Sprint 5 kasih field edit manual sederhana bareng nomor (organizer bisa isi kalau mau), **tidak ada bulk-generate performance order** — itu genuinely bagian Schedule (urutan tampil bergantung venue/waktu, bukan sekadar angka berurut).

### G. Status lifecycle entry — 8 state, transisi eksplisit (pola sama Event/Registration)
```text
registered   → checked_in, withdrawn, disqualified
checked_in   → ready, withdrawn, disqualified
ready        → on_deck, withdrawn, disqualified
on_deck      → performing, withdrawn, disqualified
performing   → finished, disqualified
finished     → (terminal)
withdrawn    → (terminal)
disqualified → (terminal)
```
`checked_in_at` di-set saat transisi ke `checked_in` (sekali). Invalid transition → validation error field `status` (pola sama `EventController`/`EventRegistrationController`).

### H. Otorisasi: owner/admin org penyelenggara
Generate nomor, assign manual, ubah status → owner/admin (`EventPolicy::manageCompetition`, pola `manageCategories`/`manageRegistrations`). `member` cuma lihat. Tidak ada `CompetitionEntryPolicy` terpisah — derive dari `entry->event->organization`, konsisten Keputusan H Sprint 3 / G Sprint 4.

### I. Tidak ada hard delete entry
Entry = histori kompetisi. Semua FK (`event_id`, `event_category_id`, `team_id`, `registration_id`) **RESTRICT**. Tidak ada route destroy.

### J. UI: tab "Kompetisi" di halaman event, dikelompokkan per kategori
Konsisten pola Sprint 3 (tab Overview/Peserta/Kategori/Staff di `events/show`) — bukan halaman terpisah baru, supaya "isolasi nomor per kategori" langsung terlihat di layout (form Generate + tabel entry per kategori). Halaman khusus Competition (dev-checklist) dianggap referensi, bukan wajib diikuti persis (`_docs/README.md` §research).

---

## Goals & Non-Goals

**Goals:** entry otomatis saat approve (idempoten) · entry ikut withdrawn saat registrasi ditarik · assign nomor manual & sequential (atomic, isolasi per kategori) · status lifecycle hari-H tervalidasi · tenant isolation semutu sprint sebelumnya.

**Non-Goals:** Schedule/`event_schedule_slots` (6) · random draw nomor · bulk performance-order generator · Event Command Center (DEFERRED) · `event_staff.role` sebagai gate (masih tertunda, konsisten Sprint 3/4).

---

## Perubahan Schema

### `competition_entries` (migration 13)
```text
id                  BIGINT UNSIGNED, PK
public_id           string, unique                      (HasPublicId, prefix "entry_")
event_id            BIGINT UNSIGNED, FK → events.id,             RESTRICT
event_category_id   BIGINT UNSIGNED, FK → event_categories.id,   RESTRICT
registration_id     BIGINT UNSIGNED, FK → registrations.id,      RESTRICT, UNIQUE
team_id             BIGINT UNSIGNED, FK → teams.id,               RESTRICT
competition_number  string, nullable
performance_order   int unsigned, nullable
status              string (enum app: CompetitionEntryStatus, 8), default 'registered'
checked_in_at       datetime, nullable
created_at, updated_at
```
Index: `unique(public_id)`, `unique(registration_id)`, **`unique(event_category_id, competition_number)`**, `index(event_id, status)`.

---

## Business Logic

```text
RULE-1  Entry dibuat HANYA lewat CompetitionService::createEntryForRegistration(), dipanggil dari
        RegistrationService saat transisi ke approved. firstOrCreate by registration_id (idempoten).
RULE-2  Registrasi → withdrawn dengan entry non-final → entry ikut withdrawn (Keputusan B).
RULE-3  competition_number unik per (event_category_id, competition_number); NULL dibolehkan berulang
        (entry belum dinomori).
RULE-4  Generate sequential: DB::transaction() + lockForUpdate() atas entry ber-competition_number NULL
        di kategori tsb, alokasi berurut dari $start, prefix + zero-pad $digits.
RULE-5  Transisi status entry hanya lewat CompetitionService::changeStatus(), sesuai peta Keputusan G.
RULE-6  checked_in_at di-set sekali, saat transisi pertama ke checked_in.
RULE-7  event/event_category/team/registration dengan entry → tak bisa hard delete (FK RESTRICT).
```

## Permission & Access Control

| Aksi | owner | admin | member | Non-member |
|---|---|---|---|---|
| Lihat entry & nomor | ✅ | ✅ | ✅ | ❌ (403) |
| Generate / assign nomor | ✅ | ✅ | ❌ | ❌ |
| Ubah status entry | ✅ | ✅ | ❌ | ❌ |

## Testing Plan (Feature, MySQL, RefreshDatabase)

```text
CompetitionEntry/AutoCreateEntryTest
  □ approve registration → entry dibuat (status registered, event/category/team/registration terhubung)
  □ approved → withdrawn → draft → submitted → approved lagi → TIDAK bikin entry kedua (unique registration_id)
  □ registration approved→withdrawn → entry ikut withdrawn
  □ entry yang sudah finished tidak diubah diam-diam saat registrasi ditarik

CompetitionEntry/CompetitionNumberTest
  □ assign manual unik per kategori; duplikat dalam kategori sama ditolak
  □ nomor sama boleh dipakai di kategori BERBEDA
  □ generate sequential: prefix+start+digits menghasilkan urutan benar, hanya isi yang NULL
  □ generate ulang tidak menimpa entry yang sudah bernomor
  □ member role tak bisa generate/assign (403)

CompetitionEntry/EntryStatusTest
  □ registered→checked_in set checked_in_at (sekali)
  □ transisi invalid (registered→performing) ditolak, status tak berubah
  □ finished/withdrawn/disqualified = terminal
  □ member role tak bisa ubah status (403)

CompetitionEntry/TenantIsolationTest
  □ user A tak bisa lihat/generate/ubah status entry event B
  □ child (entry) tak-milik-event via URL manipulasi → 404
  □ guest → login

CompetitionEntry/DatabaseIntegrityTest
  □ UNIQUE registration_id, UNIQUE (event_category_id, competition_number)
  □ FK RESTRICT: event/category/team/registration tak bisa hard-delete saat py entry
  □ public_id prefix "entry_"; migration up()/down() bersih
```

## File yang HARUS Dibuat / Diubah

| File | Perubahan |
|---|---|
| `database/migrations/*_create_competition_entries_table.php` (13) | baru |
| `app/Enums/CompetitionEntryStatus.php` | baru (8 case) |
| `app/Exceptions/InvalidCompetitionEntryStatusTransition.php` | baru |
| `app/Models/CompetitionEntry.php` | baru |
| `app/Models/Event.php`, `EventCategory.php`, `Team.php` | +relasi `competitionEntries()` |
| `app/Models/Registration.php` | +relasi `entry()` (HasOne) |
| `app/Policies/EventPolicy.php` | +`manageCompetition($user, $event)` |
| `app/Services/CompetitionService.php` | baru (`createEntryForRegistration`, `syncForRegistrationWithdrawal`, `changeStatus`, `allowedTransitions`) |
| `app/Services/CompetitionNumberService.php` | baru (`assignManual`, `generateSequential` — transaction+lock) |
| `app/Services/RegistrationService.php` | +dependency `CompetitionService`, panggil sync di `changeStatus()` |
| `app/Http/Requests/Competition/*` | `GenerateCompetitionNumbersRequest`, `UpdateCompetitionNumberRequest`, `UpdateCompetitionEntryStatusRequest` |
| `app/Http/Controllers/Dashboard/CompetitionEntryController.php` | baru (`generateNumbers`, `updateNumber`, `updateStatus`) |
| `routes/web.php` | +`events/{event}/competition/numbers/generate` + `competition-entries/{entry}/{number,status}` |
| `database/factories/CompetitionEntryFactory.php` | baru |
| `resources/views/dashboard/events/show.blade.php` | +tab "Kompetisi (N)" |
| `resources/views/dashboard/events/partials/competition.blade.php` | baru — per kategori: form generate + tabel entry |
| `tests/Feature/CompetitionEntry/*` | 5 file (lihat Testing Plan) |
| `_docs/tasks/tasks.md`, `_docs/README.md`, `_docs/status/log.md`, `_docs/status/roadmap-to-mvp.md` | update status |

## Security Considerations

- [ ] Semua mutasi entry gated `manageCompetition` (owner/admin) — tak ada endpoint tanpa `authorize()`
- [ ] `event_category_id`/`event_id`/`team_id`/`registration_id` tak pernah dari request mentah — entry dibuat server-side dari `Registration`, nomor/status lewat Form Request tervalidasi
- [ ] Tenant isolation diuji adversarial (pola sprint sebelumnya)

## Open Questions

Tidak ada — Keputusan A-J ngikut precedent Sprint 3/4 (owner/admin gate, no separate child policy, status map eksplisit, no config table nganggur). Diproses langsung sesuai instruksi "lanjut".

## Sisa untuk Phase C penuh

Sprint 5 selesai (Competition Entry + Number). **Sprint 6 — Schedule** (`event_schedule_slots`) belum dikerjakan — itu yang menutup Milestone Phase C ("panitia kelola operasional lomba — nomor peserta, jadwal, status hari-H — tanpa spreadsheet").
