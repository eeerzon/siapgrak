# Design Doc — Event Schedule (Phase C, Sprint 6)

**Status:** Implemented (2026-09-14) — 16 test hijau di MariaDB (total 199), Pint clean, migrasi up/down bersih.
**Author:** Claude
**Tanggal:** 2026-09-14
**Kontrak:** `BACKEND-HANDOFF.md` §Sprint 6 (`event_schedule_slots` saja — `event_documents` TIDAK termasuk, tidak disebut di kontrak Sprint 6)
**Definition of Ready:** ✅ — Sprint 1-5 selesai (183 test). Domain ini risiko lebih rendah dari Registration/Entry (`quality/test-strategy.md` — data operasional/logistik, bukan histori kompetisi) sehingga SRS & test lebih ringkas, tapi tenant isolation tetap wajib penuh.

## TL;DR

`event_schedule_slots` — daftar linear waktu per event (Opening Ceremony, slot lomba per kategori/entry, break, TM, dst), diurut manual (`display_order`, Move Up/Down), bukan kalender. Slot bisa terikat ke kategori dan/atau entry tertentu (opsional keduanya — general slot seperti "Break" tidak terikat apa-apa). **Beda dari sprint sebelumnya:** slot bukan data historis kompetisi (bukan registration/entry/result) — jadi field-nya BOLEH diedit bebas setelah dibuat, dan **hard delete diizinkan** (organizer wajar bolak-balik atur ulang jadwal).

**Requirement ID:** `FR-SCHEDULE-01`

## Keputusan Desain

**A. Field bebas diedit, termasuk relasi kategori/entry** — beda dari Event/Registration (yang mengunci `organization_id`/`team_id` setelah create), slot jadwal bukan identitas resource — semua field (`title`, `start_at`, `end_at`, `venue_area`, `slot_type`, `event_category_id`, `competition_entry_id`) editable via `update()`. Alasan: jadwal genuinely "living document", tidak ada integritas historis yang dilanggar kalau organizer pindah-pindah slot.

**B. Hard delete diizinkan** — slot BUKAN "competition/registration history" yang disebut Constitution; ini logistik yang wajar dihapus/disusun ulang. `Route::resource` **dengan** `destroy` (beda dari events/registrations/teams/orgs yang semua `->except(['destroy'])`).

**C. Tidak ada `public_id`** — konsisten `EventCategory`/`EventStaff`/`RegistrationMember` (sub-resource operasional event, bukan entitas utama).

**D. `event_category_id` & `competition_entry_id` nullable, `SET NULL` on delete** — slot general (Break, Ceremony, TM) tidak terikat kategori/entry manapun. Kalau kategori/entry-nya kelak terhapus, slot tidak ikut hilang — cuma jadi "tidak terikat" lagi.

**E. `event_id` → CASCADE on delete** — slot adalah child config event (pola sama `event_categories`, bukan pola RESTRICT `registrations`/`competition_entries`). Event sendiri toh tidak punya route hard-delete (Sprint 3 Keputusan G), jadi ini murni konsistensi semantik.

**F. Ordering: `display_order` per-event (bukan per-kategori)** — satu timeline linear untuk seluruh event (`frontend-plan.md` §Schedule F8: "08:00 Opening Ceremony, 08:30 A01 — ..."), bukan per-kategori. Slot baru selalu masuk ke akhir list (`max(display_order)+1`); reorder lewat Move Up/Down (tukar `display_order` dengan tetangga) — bukan drag-drop (itu iterasi berikutnya, sesuai frontend-plan).

**G. Tidak ada picker `competition_entry_id` di UI V1** — kolom & validasi ada di backend, tapi form Sprint 6 cuma pakai `event_category_id` (dropdown pendek). Memetakan slot ke entry spesifik (ratusan entry per kategori) butuh picker/search UI tersendiri — ditunda, konsisten pola "jangan bangun UI kompleks untuk field yang belum genuinely dibutuhkan" (Sprint 3 poster, Sprint 4 per-doc review).

**H. Otorisasi**: owner/admin (`EventPolicy::manageSchedule`) — pola identik sprint sebelumnya, tidak ada `EventScheduleSlotPolicy` terpisah.

## Perubahan Schema

### `event_schedule_slots` (migration 14)
```text
id                   BIGINT UNSIGNED, PK
event_id             FK → events.id,             CASCADE
event_category_id    FK → event_categories.id,   nullable, SET NULL
competition_entry_id FK → competition_entries.id, nullable, SET NULL
title                string, nullable
start_at             datetime
end_at               datetime, nullable
venue_area           string, nullable
slot_type            string (enum app: ScheduleSlotType — competition, technical_meeting,
                      break, ceremony, announcement, other)
display_order        unsigned int, default 0
created_at, updated_at
```
Index: `index(event_id, display_order)`.

## Business Logic
```text
RULE-1  event_category_id (kalau diisi) harus milik {event} dari route.
RULE-2  competition_entry_id (kalau diisi) harus milik {event} dari route.
RULE-3  Slot baru: display_order = max(display_order di event ini) + 1.
RULE-4  Move Up/Down menukar display_order dengan slot tetangga (dalam event yang sama).
RULE-5  end_at (kalau diisi) >= start_at.
```

## Permission

| Aksi | owner/admin | member | non-member |
|---|---|---|---|
| Lihat jadwal | ✅ | ✅ | ❌ 403 |
| Tambah/edit/hapus/reorder | ✅ | ❌ | ❌ |

## Testing Plan
```text
ScheduleSlot/ManageScheduleTest
  □ owner tambah slot → display_order = akhir list
  □ event_category_id/competition_entry_id dari event lain ditolak
  □ end_at < start_at ditolak
  □ owner edit slot (semua field, termasuk pindah kategori) — berhasil
  □ move up/down menukar urutan dua slot bertetangga
  □ owner hapus slot (hard delete) — berhasil
  □ member role tak bisa mutasi (403), tapi bisa lihat

ScheduleSlot/TenantIsolationTest
  □ user A tak bisa lihat/mutasi jadwal event B (403)
  □ slot event B tak bisa dimanipulasi via URL event A (404)
  □ guest → login

ScheduleSlot/DatabaseIntegrityTest
  □ SET NULL saat category/entry terkait dihapus (bukan slot ikut hilang)
  □ CASCADE saat event dihapus (test level model, event sendiri tak punya route hapus)
  □ migration up()/down() bersih
```

## File yang Dibuat/Diubah
```text
database/migrations/*_create_event_schedule_slots_table.php
app/Enums/ScheduleSlotType.php
app/Models/EventScheduleSlot.php (+relasi di Event/EventCategory/CompetitionEntry)
app/Policies/EventPolicy.php            (+manageSchedule)
app/Services/ScheduleService.php        (create/update/delete/moveUp/moveDown)
app/Http/Requests/Schedule/{Store,Update}EventScheduleSlotRequest.php
app/Http/Controllers/Dashboard/EventScheduleSlotController.php
routes/web.php                           (+events/{event}/schedule/*)
database/factories/EventScheduleSlotFactory.php
resources/views/dashboard/events/show.blade.php   (+tab "Jadwal")
resources/views/dashboard/events/partials/schedule.blade.php
tests/Feature/ScheduleSlot/*  (3 file)
```

## Catatan Implementasi

- **Bug nyata ketemu manual (bukan lewat test) saat verifikasi browser:** `moveUp()`/`moveDown()` awalnya query lewat `$event->scheduleSlots()` (relasi yang punya `orderBy('display_order')` ASC bawaan) lalu coba override dengan `orderByDesc('display_order')` di `moveUp()`. **Eloquent/query builder `orderBy()` itu aditif, bukan mengganti** — jadi ASC bawaan relasi tetap menang, `moveUp()` malah nuker slot dengan yang PALING JAUH (display_order terkecil), bukan tetangga langsung. Ketauan pas manual test 3-slot di browser (test otomatis awal cuma pakai 2 slot, yang kebetulan tidak bisa mendeteksi bug ini karena dengan 2 baris "tetangga" dan "baris manapun yang cocok" itu baris yang sama). **Fix:** query langsung ke model (`EventScheduleSlot::query()->where('event_id', ...)`), bukan lewat relasi. **Regression test ditambah** (`test_move_up_with_three_slots_swaps_only_the_immediate_neighbour`) supaya kelas bug ini kepegang otomatis ke depannya. Pelajaran: kalau ada relasi dengan `orderBy` bawaan, jangan asumsikan `orderBy`/`orderByDesc` tambahan di query builder akan "mengganti" itu.
- Satu kali full-suite run sempat nunjuk 1 test gagal tapi lolos di run berikutnya + run terisolasi — dicurigai flake `fake()->unique()` di kombinasi besar seluruh suite (199 test), bukan defect Sprint 6 (3x run berturut-turut setelahnya semua hijau).
