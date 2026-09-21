# Design Doc — Score Sheet Lifecycle: Submit / Lock / Reopen (Phase D, Sprint 10)

**Status:** Implemented (2026-09-15) — 19 test baru hijau (total 295), verifikasi manual via curl selesai (3 persona: judge/admin/owner)
**Author:** Claude
**Tanggal:** 2026-09-15
**Kontrak:** `BACKEND-HANDOFF.md` §Sprint 7-10 (Judging) · `tasks.md` breakdown: Sprint 10 = Submit / Lock / Revision + Audit Trail
**Definition of Ready:** ✅ — Sprint 9 selesai (276 test). `score_sheets`/`scores` sudah ada, cuma status `draft` yang reachable.

## TL;DR

Melengkapi lifecycle score sheet yang di Sprint 9 cuma sampai `draft`: juri **Submit** (draft→submitted, wajib semua kriteria terisi), organizer **Lock** (submitted→locked), organizer **Reopen** (submitted/locked→draft, **alasan wajib**, tercatat di `score_sheet_revisions`, `revision_count` naik). Reopen sheet yang sudah `locked` butuh hak lebih tinggi (**Owner saja**, bukan Admin) — "privileged action" sesuai catatan di blueprint.

**Requirement ID:** `FR-SCORE-LIFECYCLE-01`

## Keputusan Desain Kritis

**A. Reopen sheet `locked` = privileged action, didefinisikan sebagai Owner-only (bukan Admin).** Blueprint/dev-checklist eksplisit bilang "Locked sheet tidak bisa reopen tanpa privileged action" sebagai aturan TERPISAH dari "Reopen wajib reason" — berarti ada tingkat otorisasi ekstra di atas `manageJudging` (Owner+Admin) yang sudah dipakai semua aksi Judging lain sejak Sprint 7. Tidak ada dokumen yang mendefinisikan persis siapa "privileged" itu. **Dipilih:** Owner organisasi saja — role tertinggi yang sudah ada di `OrganizationRole`, tidak perlu bikin role/permission baru. Reopen sheet `submitted` (belum di-lock) tetap Owner+Admin seperti aksi Judging lainnya — hanya sheet yang SUDAH di-lock yang naik ke Owner-only. Ditambahkan sebagai `EventPolicy::reopenLockedScoreSheet`.

**B. Scores TIDAK dihapus saat reopen.** Reopen = "juri perlu koreksi", bukan "mulai dari nol" — baris `Score` yang sudah ada tetap ada, juri tinggal edit nilai yang salah lewat form draft yang sama. `submitted_at`/`locked_at` di-reset ke `null` (statusnya kembali `draft`, jadi timestamp itu tidak relevan lagi sampai submit/lock berikutnya).

**C. Submit mensyaratkan SEMUA kriteria yang scoreable (hasil resolusi Keputusan D di `fr-scoring.md`) sudah punya baris `Score`.** Skor `0` valid (baris `Score` ada = terisi, terlepas dari nilainya). Kalau ada yang kosong, submit ditolak dengan pesan yang menyebut kriteria mana yang belum diisi — bukanditebak dari "semua field form terisi" (kesalahan yang eksplisit dihindari di blueprint: "Status submitted harus eksplisit").

**D. Sheet `submitted`/`locked` read-only total untuk juri** — form scoring (`GET`) merender input dalam keadaan `disabled` dan sembunyikan tombol Simpan Draft/Kirim kalau status bukan `draft`; endpoint simpan draft (`POST .../scores`) dan submit (`POST .../submit`) sama-sama menolak (403) kalau sheet bukan `draft`. Satu-satunya jalan sheet balik bisa diedit juri adalah organizer reopen (Keputusan A/B).

**E. Halaman "Lembar Skor" organizer (list + detail) di-gate penuh di belakang `manageJudging`** (Owner+Admin), **bukan** sekadar `view` event seperti listing Round/Criteria yang sudah ada. Round/Criteria adalah metadata setup (rendah sensitivitas, semua member org boleh lihat), tapi nilai skor individual juri adalah domain paling sensitif di project ini (Constitution). Daripada menambah nuance baru ("member boleh lihat status tapi tidak nilai"), seluruh seksi Lembar Skor — termasuk baris status di list — disembunyikan total dari role `member`. Lebih konservatif, lebih simpel (Karpathy #2).

**F. List Lembar Skor (organizer) cuma menampilkan STATUS + progress, bukan angka skor** — detail nilai per kriteria baru terlihat di halaman detail satu sheet (`dashboard/score-sheets/{sheet}`). Ini **bukan** halaman ranking/perbandingan lintas juri atau lintas peserta (tidak ada sorting-by-score, tidak ada agregat) — organizer meninjau SATU lembar skor SATU juri untuk memutuskan perlu di-reopen/lock atau tidak, bukan melihat siapa unggul. Konsisten dengan larangan Constitution soal kebocoran ranking sebelum finalize (ranking sungguhan baru ada di Sprint 11 `ResultCalculator`).

**G. Lock hanya dari status `submitted`** (bukan langsung dari `draft`) — memaksa juri benar-benar submit dulu (draft tidak pernah bisa "keburu dikunci" organizer sebelum juri selesai).

**H. `score_sheet_revisions.requested_by` nullable + `SET NULL`** (bukan RESTRICT/CASCADE) — kalau user organizer yang mem-reopen suatu saat dihapus, baris audit riwayat reopen itu **tidak boleh ikut hilang atau memblokir penghapusan user** (Constitution: "Deleted user tidak boleh merusak audit trail", pola sama `activity_logs` di blueprint). Tidak ada `updated_at` di tabel ini — sekali tercatat, baris revisi immutable.

## Perubahan Schema

### `score_sheet_revisions` (migration baru)
```text
id              BIGINT UNSIGNED, PK
score_sheet_id  FK → score_sheets.id, CASCADE
requested_by    FK → users.id, nullable, SET NULL          (Keputusan H)
reason          TEXT, NOT NULL
previous_status string (status sebelum di-reopen: 'submitted' atau 'locked')
created_at      timestamp (tidak ada updated_at — immutable)
```

Tidak ada perubahan skema di `score_sheets`/`scores` — kolom `status`/`submitted_at`/`locked_at`/`revision_count` sudah dibuat lengkap di Sprint 9, sprint ini baru mengaktifkan transisinya.

## Business Logic
```text
RULE-1  Submit (judge, draft→submitted): semua scoreable criteria harus sudah punya Score row
        (Keputusan C). Gagal → error menyebutkan kriteria yang belum diisi, status tetap draft.
RULE-2  Lock (organizer manageJudging, submitted→locked): gagal kalau status bukan submitted
        (Keputusan G).
RULE-3  Reopen (submitted→draft: organizer manageJudging; locked→draft: organizer Owner-only —
        Keputusan A): alasan (reason) wajib diisi, dicatat sebagai baris score_sheet_revisions
        (requested_by, reason, previous_status), revision_count+1, submitted_at/locked_at direset
        null, Score existing TIDAK dihapus (Keputusan B).
RULE-4  Sheet non-draft read-only total untuk juri (Keputusan D) — baik render maupun endpoint.
RULE-5  List/detail Lembar Skor organizer di-gate manageJudging (Keputusan E), list cuma status
        (Keputusan F).
```

## Permission
| Aksi | Judge (sheet miliknya) | Owner | Admin | Member |
|---|---|---|---|---|
| Simpan draft / submit (sheet draft) | ✅ | – | – | – |
| Edit sheet submitted/locked | ❌ (selalu) | – | – | – |
| Lihat list/detail Lembar Skor | – | ✅ | ✅ | ❌ |
| Lock (submitted→locked) | – | ✅ | ✅ | ❌ |
| Reopen submitted→draft | – | ✅ | ✅ | ❌ |
| Reopen locked→draft | – | ✅ | ❌ | ❌ |

## Testing Plan
```text
ScoreSheet/SubmitTest (judge area)
  □ submit berhasil kalau semua kriteria terisi → status submitted, submitted_at terisi
  □ submit gagal kalau ada kriteria kosong → tetap draft, pesan error jelas
  □ submit sheet yang sudah submitted/locked → ditolak (403)
  □ juri tidak bisa simpan draft / submit ulang setelah status bukan draft
  □ form render read-only (input disabled, tombol hilang) saat status bukan draft

ScoreSheet/LockReopenTest (organizer area)
  □ lock dari submitted → berhasil, locked_at terisi
  □ lock ditolak kalau status masih draft
  □ reopen submitted→draft: reason wajib, revision_count naik, baris score_sheet_revisions
    tercatat benar (requested_by/reason/previous_status), Score existing tetap ada
  □ reopen locked→draft oleh Admin → ditolak (403, privileged action)
  □ reopen locked→draft oleh Owner → berhasil
  □ reopen tanpa reason → validation error
  □ member role tidak bisa akses list/detail Lembar Skor sama sekali

ScoreSheet/AuditTrailTest
  □ dua kali reopen berturut-turut menghasilkan dua baris revisions independen
  □ user yang di-reopen dihapus → baris revisions tetap ada, requested_by jadi null

ScoreSheet/TenantIsolationTest (tambahan)
  □ organizer org lain tidak bisa lock/reopen/lihat detail sheet event org lain
```

## Catatan Implementasi

**Bug nyata ditemukan+diperbaiki: `ScoreSheet::firstOrCreate()` tidak meng-hydrate DB default ke instance PHP.** `ScoreSheetService::getOrCreateScoreSheet` (Sprint 9) membuat baris baru lewat `firstOrCreate([...])` tanpa menyertakan `status` (sengaja tidak fillable). MySQL menerapkan `DEFAULT 'draft'` di level kolom saat INSERT, tapi Eloquent **tidak** membaca ulang default itu ke atribut model setelah insert — jadi `$sheet->status` pada instance yang BARU dibuat (dalam request yang sama) bernilai `null` sungguhan, walau baris di DB sudah benar `'draft'`. Bug ini **laten sejak Sprint 9** (view lama tidak pernah membaca `$sheet->status`) dan baru ketahuan sekarang begitu `judge/entries/show.blade.php` mulai bergantung padanya (`$isDraft = $sheet->status->value === 'draft'`) — muncul sebagai crash 500 "`Attempt to read property "value" on null`" di 4 test Sprint 9 yang sudah ada. **Fix:** tambah `protected $attributes = ['status' => 'draft'];` di model — default level-PHP yang mencerminkan default level-DB, supaya instance baru selalu punya nilai `status` yang benar tanpa perlu `refresh()` tambahan.

**Verifikasi manual (curl, live server, 3 persona: judge, admin, owner)** mengonfirmasi seluruh siklus hidup: submit ditolak kalau ada kriteria kosong (pesan persis "Belum semua kriteria diisi: PBB.") ✅, submit berhasil setelah semua terisi + form otomatis read-only (dicek via atribut `disabled="disabled"` sungguhan, bukan cuma substring "disabled" yang juga muncul di class Tailwind `disabled:opacity-50` pada tombol — assertion test awal sempat jadi tautologi karena ini, sudah diperbaiki jadi cek atribut HTML persis) ✅, juri tidak bisa edit/save draft lagi setelah submit (403 live) ✅, organizer (Admin) lihat detail skor + lock ✅, Admin ditolak reopen sheet yang sudah locked (403, privileged action) ✅, Owner berhasil reopen sheet locked dengan reason ✅ — status kembali draft, submitted_at/locked_at ter-reset null, revision_count naik, skor lama (88.000) tetap tersimpan, riwayat reopen tercatat dengan reason+previous_status yang benar, dan form juri kembali bisa diedit + tombol "Kirim Skor Final" muncul lagi.

## File Dibuat/Diubah
```text
database/migrations/*_create_score_sheet_revisions_table.php (23)
app/Models/ScoreSheetRevision.php
app/Models/ScoreSheet.php            (+revisions() relation, +status default attribute — lihat Catatan Implementasi)
app/Exceptions/IncompleteScoreSheet.php
app/Services/ScoreSheetService.php   (+submit, +lock, +reopen)
app/Policies/EventPolicy.php         (+reopenLockedScoreSheet)
app/Http/Requests/ScoreSheet/ReopenScoreSheetRequest.php
app/Http/Controllers/Dashboard/ScoreSheetController.php  (show, lock, reopen)
app/Http/Controllers/Judge/ScoringController.php   (+submit)
database/factories/ScoreSheetRevisionFactory.php
resources/views/dashboard/score-sheets/show.blade.php
resources/views/dashboard/events/partials/judging.blade.php  (+seksi Lembar Skor per round)
resources/views/judge/entries/show.blade.php  (+tombol Submit, +read-only state)
resources/views/components/ui/status-badge.blade.php  (+arm 'locked')
routes/web.php  (+dashboard/score-sheets/*, +judge/rounds/{round}/entries/{entry}/submit)
tests/Feature/ScoreSheet/SubmitTest.php
tests/Feature/ScoreSheet/LockReopenTest.php
tests/Feature/ScoreSheet/AuditTrailTest.php
tests/Feature/ScoreSheet/TenantIsolationTest.php  (+2 test: judge submit ke round lain, organizer org lain)
```
