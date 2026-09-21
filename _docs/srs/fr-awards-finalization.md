# Design Doc — Awards + Result Finalization (Phase E, Sprint 12)

**Status:** Implemented (2026-09-16) — 24 test baru hijau (total 338), verifikasi manual via curl selesai (3 persona: owner/admin/member)
**Author:** Claude
**Tanggal:** 2026-09-16
**Kontrak:** `BACKEND-HANDOFF.md` §Sprint 11-12 (Results) · `tasks.md` breakdown: Sprint 12 = Awards + Result Finalization
**Definition of Ready:** ✅ — Sprint 11 selesai (314 test). `competition_results` ada, status cuma pernah `draft`.

## TL;DR

Melengkapi lifecycle hasil yang di Sprint 11 cuma sampai `draft`: organizer **Finalize** (draft→finalized, per-round, semua entry sekaligus), **Publish** (finalized→published — status internal, BUKAN halaman publik, tetap DEFERRED per ADR-002), dan **Reopen** (balik ke draft, alasan wajib, privileged kalau dari `published`). Ditambah `awards` (CRUD manual organizer) dan `activity_logs` (infrastruktur audit — dipakai buat aksi Sprint 12 sendiri, BUKAN retrofit ke sprint-sprint lama).

**Requirement ID:** `FR-AWARDS-FINALIZE-01`

## Keputusan Desain Kritis

**A. Finalize/Publish/Reopen beroperasi per-ROUND, transisi SEMUA baris `competition_results` round itu sekaligus** — bukan per-entry. Finalisasi sebagian (sebagian entry `finalized`, sebagian masih `draft`) tidak masuk akal untuk sebuah ranking yang saling membandingkan; kalau organizer siap finalize, seluruh papan ranking round itu finalize bersamaan.

**B. Lifecycle status hasil MENIRU PERSIS pola `ScoreSheetStatus` Sprint 10** — `Draft→Finalized→Published` setara `Draft→Submitted→Locked`. Reopen dari `Finalized` = `manageResults` (Owner+Admin, pola sama reopen `submitted`); reopen dari `Published` = **privileged, Owner-only** (`EventPolicy::reopenPublishedResult`, pola sama `reopenLockedScoreSheet`). Skor/angka hasil TIDAK dihapus saat reopen (sama alasan Sprint 10 Keputusan B — reopen itu koreksi, bukan mulai ulang).

**C. Sekali `finalized`/`published`, `Hitung Ulang Hasil` (Sprint 11) diblokir sampai di-reopen.** `ResultService::calculateForRound()` sekarang menolak (403) kalau round-nya sudah tidak `draft` — mencegah organizer diam-diam mengubah angka yang sudah "final" tanpa lewat alur reopen+alasan+audit.

**D. Tidak ada `ResultPolicy`/`AwardPolicy` terpisah — perluas `EventPolicy` (pola konsisten sejak Sprint 3, ditegaskan lagi di Sprint 10 lewat `reopenLockedScoreSheet`).** Roadmap awal menyebut "ResultPolicy" sebagai nama rencana, tapi API otorisasi (Owner/Admin kelola, Owner-only untuk reopen published) identik dengan pola yang sudah 8 sprint dipakai — bikin class Policy baru cuma untuk penamaan tanpa logic berbeda menambah permukaan tanpa nilai. **Tambahan:** `EventPolicy::manageResults` (dipakai finalize/publish/reopen-finalized/awards CRUD) — method baru, terpisah dari `manageJudging` karena secara fase produk ini domain Phase E (Results) bukan Phase D (Judging), walau cek rolenya identik (Owner+Admin). **Sprint 11's `ResultController`/`UpdatePenaltyRequest` di-refactor ikut pakai `manageResults`** — perbaikan kecil menyusul distingsi ini baru ada sekarang (disebut transparan, bukan diam-diam).

**E. `activity_logs` dibangun sebagai infrastruktur + dipakai untuk aksi Sprint 12 SENDIRI (`result_finalized`, `result_published`, `result_reopened`, `award_created/updated/deleted`) — TIDAK di-retrofit ke aksi sprint-sprint sebelumnya** (`registration_approved/rejected`, `score_sheet_submitted/reopened`, dst). Roadmap `M5` eksplisit memisahkan ini: baris Sprint 12 cuma minta tabel dibuat + dipakai konteks Sprint 12, sementara **"coverage audit log" ditulis sebagai item tersendiri di Sprint 13 (Polish)** — jadi retrofit lengkap adalah scope Sprint 13, bukan diam-diam dilewati sekarang.

**F. Awards: `registration_member_id` ada di skema (sesuai blueprint) tapi TIDAK di-expose di form Sprint 12** — cuma `competition_entry_id` (opsional) yang bisa dipilih organizer. Mendukung picker independen untuk anggota tim spesifik ("Best Danton") butuh UI dependent-dropdown (anggota tim tergantung entry yang dipilih) yang menambah kompleksitas nyata untuk sprint yang sudah padat. **Workaround yang cukup untuk v1:** nama individu bisa ditulis langsung di field `name` (mis. "Best Danton — Budi Santoso") tanpa link formal. Kolom tetap nullable di DB supaya tidak perlu migrasi lagi kalau UI-nya dibangun nanti.

**G. Awards TIDAK pakai `HasPublicId`** — sama alasan `ScoreSheet`/`CompetitionResult` (Sprint 9 Keputusan J): blueprint mencantumkan `public_id`, tapi tidak ada route yang mengalamatkannya (dashboard pakai `/dashboard/awards/{award}` numeric ID biasa, tidak ada halaman publik di Admin v1).

**H. Awards boleh hard delete** — bukan competition history yang harus dijaga permanen seperti scores/results, lebih dekat ke metadata operasional/seremonial (pola sama `event_schedule_slots` Sprint 6: "field bebas diedit + hard delete diizinkan").

**I. `source` awards default `manual` — nilai `judging`/`kreenconnect_voting` schema-only, belum ada mekanisme yang mengisinya otomatis.** Tidak ada auto-generate award dari rank (mis. rank 1 → "Juara 1" otomatis) — blueprint eksplisit minta ranking dan awards dipisah ("Pisahkan ranking utama dari awards"), jadi organizer selalu membuat award secara sadar/manual, bukan sistem menebak.

**J. `job CalculateCompetitionResults` di-dispatch via `dispatchSync()`, bukan `dispatch()` biasa.** `QUEUE_CONNECTION=database` di project ini, dan **tidak ada infrastruktur queue worker** yang pernah dijalankan/didokumentasikan (dev maupun production placeholder). Men-dispatch job biasa berarti butuh `php artisan queue:work` berjalan — kalau lupa, tombol "Hitung Ulang"/"Finalize" akan terlihat diam tanpa error (job nongkrong di tabel `jobs` selamanya). `dispatchSync()` menjalankan job di request yang sama (perilaku identik `ResultService` langsung dipanggil di Sprint 11) tapi tetap memenuhi kontrak "job CalculateCompetitionResults" — tinggal ganti ke `dispatch()` kapan pun worker sudah ada.

## Perubahan Schema

### `awards` (migration baru)
```text
id                     BIGINT UNSIGNED, PK
event_id               FK → events.id, CASCADE
event_category_id      FK → event_categories.id, nullable, CASCADE   (NULL = award seluruh event)
name                   string
award_type             string                                         (free-text, bukan enum — contoh sangat beragam)
competition_entry_id   FK → competition_entries.id, nullable, SET NULL
registration_member_id FK → registration_members.id, nullable, SET NULL   (skema saja, Keputusan F)
source                 string, default 'manual'
external_reference     string, nullable
display_order          unsigned int, default 0
timestamps
```
`competition_entry_id`/`registration_member_id` pakai SET NULL (bukan RESTRICT/CASCADE) — kalau entry/member-nya hilang, award historisnya tetap ada (nama pemenang biasanya sudah tercatat di `name`), cuma link-nya lepas.

### `activity_logs` (migration baru)
```text
id               BIGINT UNSIGNED, PK
user_id           FK → users.id, nullable, SET NULL   (pola sama score_sheet_revisions.requested_by)
organization_id   FK → organizations.id, nullable, CASCADE
event_id          FK → events.id, nullable, CASCADE
action            string
subject_type      string, nullable
subject_id        unsigned bigint, nullable             (bukan FK — polymorphic tanpa constraint, subject bisa apa saja)
metadata          json, nullable
ip_address        string, nullable
created_at        timestamp   (tidak ada updated_at — immutable, pola sama score_sheet_revisions)
```

Tidak ada perubahan skema di `competition_results` — kolom `status`/`finalized_at` sudah ada dari Sprint 11, sprint ini baru mengaktifkan transisi finalized/published.

## Business Logic
```text
RULE-1  Finalize (manageResults, draft→finalized, per-round): butuh minimal 1 baris competition_results
        untuk round itu (harus sudah pernah Hitung Ulang). Semua baris ikut bertransisi bersamaan.
RULE-2  Publish (manageResults, finalized→published): status internal saja — TIDAK ada halaman publik
        yang muncul (ADR-002 tetap berlaku, "Publish" di sini murni penanda administratif).
RULE-3  Reopen finalized→draft: manageResults. Reopen published→draft: privileged, Owner-only
        (Keputusan B). Reason wajib di keduanya, dicatat ke activity_logs.
RULE-4  Hitung Ulang Hasil ditolak kalau round tidak dalam status draft (Keputusan C).
RULE-5  Award CRUD: manageResults. Hard delete diizinkan (Keputusan H).
RULE-6  Setiap finalize/publish/reopen/award create-update-delete dicatat ke activity_logs
        (Keputusan E) — action, user, event, subject, metadata seperlunya.
```

## Permission
| Aksi | Owner | Admin | Member |
|---|---|---|---|
| Lihat tab Hasil (termasuk Awards) | ✅ | ✅ | ❌ |
| Hitung Ulang (hanya saat draft) | ✅ | ✅ | ❌ |
| Finalize / Publish | ✅ | ✅ | ❌ |
| Reopen dari `finalized` | ✅ | ✅ | ❌ |
| Reopen dari `published` | ✅ | ❌ | ❌ |
| Kelola Awards (CRUD) | ✅ | ✅ | ❌ |

## Testing Plan
```text
Result/FinalizeTest
  □ finalize berhasil kalau ada hasil & status draft → semua baris jadi finalized, finalized_at terisi
  □ finalize ditolak kalau belum ada hasil sama sekali
  □ finalize ditolak kalau sudah finalized/published (tidak bisa finalize dobel)
  □ Hitung Ulang ditolak begitu status bukan draft
  □ publish dari finalized → berhasil
  □ publish ditolak kalau belum finalized
  □ reopen finalized→draft: Admin bisa, reason wajib, activity_logs tercatat
  □ reopen published→draft: Admin ditolak, Owner bisa
  □ scores/rank existing tetap ada setelah reopen

Award/ManageAwardTest
  □ organizer bisa buat/edit/hapus award
  □ award bisa dikaitkan ke competition_entry (opsional)
  □ award scoped ke event (nullable kategori)
  □ member role forbidden

ActivityLog/LoggingTest
  □ finalize/publish/reopen/award actions tercatat dengan action+user+event yang benar
  □ user yang dihapus tidak merusak baris log (user_id jadi null)

Result/TenantIsolationTest (tambahan)
  □ organizer org lain tidak bisa finalize/publish/reopen/kelola award event org lain
```

## Catatan Implementasi

**Bug nyata ditemukan+diperbaiki #1 — `Eloquent\Builder::value()` sudah lewat cast model, bukan raw DB value.** `ResultService::currentStatus()` awalnya menulis `ResultStatus::from(CompetitionResult::query()->where(...)->value('status'))` — asumsinya `->value()` mengembalikan string mentah dari DB seperti `DB::table()->value()`. Ternyata **`Illuminate\Database\Eloquent\Builder::value()` tetap lewat cast model** (beda dari query builder biasa) — jadi hasilnya sudah berupa instance `ResultStatus` (bukan string), dan memanggil `ResultStatus::from()` di atasnya melempar `TypeError`. Ketahuan langsung dari regression test Sprint 11 (`test_an_entry_disqualified_after_scoring_disappears_on_recalculate`) yang gagal begitu guard baru ditambahkan — laporan error jelas menyebut "Argument #1 ($value) must be of type string|int, App\Enums\ResultStatus given". **Fix:** hapus pembungkus `::from()`, langsung `return`-kan hasil `->value()` apa adanya (sudah `ResultStatus|null`).

**Bug nyata ditemukan+diperbaiki #2 — `@endif@if` tanpa spasi bikin compiler Blade salah tokenisasi.** Di `results.blade.php`, dua directive `@if...@endif` ditulis bersebelahan langsung (`@endif@if`) tanpa spasi/baris baru. Blade **hanya mengenali `@endif` pertama** sebagai directive dan `@if` kedua tertinggal jadi teks literal di HTML — tapi `@endif` PENUTUP-nya tetap ter-compile jadi PHP `endif;` yang berdiri sendiri tanpa `if:` pasangannya, menghasilkan `syntax error, unexpected token "endif"` di PHP. Ketahuan dari test lain yang sama sekali tidak berhubungan (`ScheduleSlot::test_category_from_another_event_is_rejected`, gagal karena test itu me-render halaman event penuh termasuk partial ini) — **jejak error awalnya membingungkan** karena pesannya menunjuk ke test/file yang tidak disentuh sprint ini. Ditelusuri dengan compile Blade manual (`Blade::compileString()`) lalu `php -l` pada hasil compile-nya untuk dapat baris pasti. **Fix:** kasih spasi/baris baru di antara dua directive. **Pelajaran:** kalau ada error Blade/PHP yang jejaknya menunjuk ke test yang tidak berhubungan, curigai view yang baru diubah dan render-test-kan dulu sebelum menduga regresi di kode lain.

Verifikasi manual (curl, live server, 3 persona: owner, admin, member) mengonfirmasi seluruh siklus: finalize ditolak sebelum ada hasil (pesan persis) ✅, calculate→finalize→publish jalan berurutan dengan badge status berubah setiap tahap ✅, **Admin ditolak reopen round published (403 langsung, bukan cuma UI disembunyikan)** ✅, **Owner berhasil reopen** — status kembali draft, timestamp reset, skor mentah (90.000) tetap tersimpan, dan ketiga baris `activity_logs` (finalized/published/reopened) tercatat lengkap dengan user yang benar ✅, award create/delete jalan dengan linkage kategori+peserta tampil benar ✅, dan role member **sama sekali tidak melihat apapun** di tab Hasil (bukan cuma tombol yang hilang — seluruh konten termasuk Penghargaan tersembunyi) ✅.

## File Dibuat/Diubah
```text
database/migrations/*_create_awards_table.php (25)
database/migrations/*_create_activity_logs_table.php (26)
app/Enums/AwardSource.php
app/Exceptions/InvalidResultStatusTransition.php
app/Exceptions/NoResultsForRound.php
app/Models/Award.php
app/Models/ActivityLog.php
app/Models/Event.php                 (+awards() relation)
app/Services/ActivityLogger.php
app/Services/ResultService.php       (+finalize, +publish, +reopen; +guard draft-only di calculateForRound)
app/Jobs/CalculateCompetitionResults.php
app/Policies/EventPolicy.php         (+manageResults, +reopenPublishedResult)
app/Http/Requests/Award/{Store,Update}AwardRequest.php
app/Http/Requests/Result/ReopenResultRequest.php
app/Http/Controllers/Dashboard/AwardController.php
app/Http/Controllers/Dashboard/ResultController.php  (+finalize, +publish, +reopen; policy diganti manageResults)
app/Http/Requests/Result/UpdatePenaltyRequest.php     (policy diganti manageResults)
database/factories/AwardFactory.php
database/factories/ActivityLogFactory.php
resources/views/dashboard/events/partials/results.blade.php  (+aksi finalize/publish/reopen, +seksi Awards — lihat Catatan Implementasi #2)
resources/views/components/ui/status-badge.blade.php  (+arm 'finalized')
app/Http/Controllers/Dashboard/EventController.php   (+eager load awards.entry.team)
routes/web.php  (+judging-rounds/{round}/results/{finalize,publish,reopen}, +events/{event}/awards, +awards/{award})
tests/Feature/Result/FinalizeTest.php
tests/Feature/Award/ManageAwardTest.php
tests/Feature/ActivityLog/LoggingTest.php
tests/Feature/Result/TenantIsolationTest.php (+kasus finalize/publish/reopen/award)
```
