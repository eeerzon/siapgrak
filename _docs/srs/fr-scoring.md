# Design Doc — Scoring / Score Sheet (Phase D, Sprint 9)

**Status:** Implemented (2026-09-14)
**Author:** Claude
**Tanggal:** 2026-09-14
**Kontrak:** `BACKEND-HANDOFF.md` §Sprint 7-10 (Judging) · `tasks.md` breakdown: Sprint 9 = Scoring (Score Sheet, save draft)
**Definition of Ready:** ✅ — Sprint 8 selesai (247 test). Judge + JudgingRound + JudgingCriteria + JudgeAssignment semua sudah ada.

## TL;DR

Juri masuk ke area terpisah (`/judge/*`, layout sendiri, tanpa sidebar/settings organizer), pilih round yang jadi tanggung jawabnya, pilih peserta (`competition_entry`), isi skor per kriteria yang jadi assignment-nya, **Simpan Draft**. Sprint ini **hanya draft** — submit/lock/reopen/audit trail masuk Sprint 10 (pemisahan eksplisit sudah tertulis di `tasks.md`/`roadmap-to-mvp.md` sebelum sprint ini dimulai).

**Requirement ID:** `FR-SCORING-01`

## Keputusan Desain Kritis

**A. Mekanisme akses juri: kode akses per-judge, BUKAN akun User/email/password — dikonfirmasi Ersa sebelum coding.** Tidak ada satupun dokumen (`BACKEND-HANDOFF.md`, blueprint, dev-checklist, BRD) yang menentukan mekanisme login juri secara konkret — semua cuma bilang "area terpisah". Sementara itu `judges.user_id` sejak Sprint 7 memang nullable ("judge boleh belum punya akun") dan `MAIL_MAILER=log` (belum ada pengiriman email nyata), jadi alur invite-by-email/reset-password tidak akan benar-benar jalan di kondisi sekarang. Persona BRD (`brd-core.md` baris 31): juri itu **"Awam–menengah"** dan **"Per-event (hari-H saja)"** — cocok dengan kode akses pendek yang dibagikan langsung ke juri hari-H, bukan akun yang harus didaftarkan/diingat lintas event. **Dipilih:** setiap `Judge` dapat `access_code` (8 karakter, auto-generate, alfabet tanpa karakter ambigu `0/O/1/I/L`). Juri buka `/judge/access`, masukkan kode → dapat session (`session('judge_id')`) khusus untuk Judge itu — **bukan** `Auth::guard()` berbasis `Authenticatable`, murni session flag + middleware, supaya tidak menambah abstraksi guard baru untuk satu kasus penggunaan sempit (Karpathy #2 Simplicity First). `judges.user_id` tetap ada apa adanya, disiapkan untuk SSO/Kreen Connect nanti — tidak dipakai jalur ini.

**B. Kode akses disimpan plaintext, bukan hash.** Beda dari password: organizer perlu **menampilkan ulang** kode itu ke juri (cetak/tulis di meja hari-H), bukan cuma verifikasi sekali. Risiko kebocoran dibatasi blast radius-nya — kode cuma buka akses ke assignment juri itu sendiri di organisasi ini, bukan ke seluruh akun/data organizer. Ada tombol **"Regenerate Kode"** (di halaman Juri, organizer-only) kalau kode perlu diganti (bocor/hilang) — meng-invalidate kode lama seketika.

**C. Hanya kriteria *leaf* (tanpa subcriteria) yang benar-benar punya baris `scores`.** Kalau sebuah kriteria punya subcriteria (mis. "PBB" → "Gerakan"/"Kekompakan"/"Ketepatan"), juri menilai di level subcriteria — parent-nya murni pengelompokan tampilan, skornya nanti di-rollup oleh `ResultCalculator` (Sprint 11), bukan diinput independen. Kriteria tanpa anak = leaf, dinilai langsung.

**D. Resolusi "kriteria mana yang jadi tanggung jawab juri ini" dari `judge_assignments` (Sprint 8):** kalau assignment `judging_criteria_id = NULL` ("seluruh round") → semua kriteria leaf di round itu. Kalau assignment mengarah ke kriteria tertentu → kalau kriteria itu leaf, itu saja; kalau kriteria itu ternyata punya anak (organizer sempat assign ke root yang lalu ditambah subcriteria), diperluas ke semua anaknya (leaf-nya). Juri bisa punya beberapa assignment kriteria berbeda di round yang sama (izin sejak Sprint 8) — hasil akhirnya union dari semua assignment tersebut.

**E. Daftar peserta yang dinilai = seluruh `competition_entries` di kategori round tersebut, KECUALI `withdrawn`/`disqualified`.** Tidak dibatasi ke status `performing`/`finished` saja — juri boleh mulai menilai/menulis draft kapan saja (mis. sambil menonton), bukan dipaksa urutan status hari-H. Entry yang mundur/didiskualifikasi tidak masuk daftar (skor untuk itu tidak relevan).

**F. `score_sheets` dibuat lazy (on-demand), bukan proaktif saat assignment dibuat.** Baris `score_sheets` (unique per round+entry+judge) baru tercipta saat juri pertama kali membuka/menyimpan skor entry tersebut (`firstOrCreate`). Menghindari baris kosong untuk entry yang belum tentu ada/valid saat assignment dibuat.

**G. Sprint ini cuma status `draft` yang bisa dicapai.** Kolom `status/submitted_at/locked_at/revision_count` di `score_sheets` sudah dibuat sesuai skema blueprint penuh (menghindari ALTER TABLE lagi di Sprint 10), tapi **tidak ada endpoint yang mengubah `status` dari `draft`** di sprint ini — `submit`/`lock`/`reopen` + `score_sheet_revisions` audit trail eksplisit didaftarkan sebagai Sprint 10 (sudah tertulis di `tasks.md` sebelum sprint ini dimulai, bukan keputusan baru sekarang).

**H. Guard hapus Round/Criteria ditambahkan sekarang** — sesuai catatan wajib di `fr-judging-setup.md`/Sprint 7: begitu `scores` ada, `JudgingRoundController::destroy` dan `JudgingCriteriaController::destroy` **wajib** menolak penghapusan kalau sudah ada skor tersimpan (pola sama `EventCategoryController::destroy` menolak hapus kategori yang sudah punya registrasi). FK `scores.judging_criteria_id`/`score_sheets.judging_round_id` tetap RESTRICT di level DB sebagai defense-in-depth, tapi guard app-level ini yang memberi pesan error ramah, bukan `QueryException` mentah.

**I. Tidak ada halaman skor apapun di sisi organizer sprint ini.** Constitution: "kebocoran ranking sementara sebelum finalize adalah insiden bisnis". Daripada membangun "Scoring Monitor" (disebut di dev-checklist sebagai halaman organizer) dengan risiko menampilkan nilai/preview-ranking sebelum waktunya, sprint ini sengaja tidak expose satupun skor ke sisi organizer — organizer hanya melihat kode akses juri (untuk dibagikan) dan status "punya draft atau belum" TIDAK sekalipun ditampilkan. Result/ranking review adalah domain Sprint 11+.

**J. `score_sheets`/`scores` tidak pakai `HasPublicId`.** Blueprint mencantumkan `public_id` di `score_sheets`, tapi tidak ada satupun route yang mengalamatkannya langsung (rute judge pakai `{round}/entries/{entry}`, bukan `{scoreSheet}`) — konsisten pola `JudgingRound`/`JudgingCriteria`/`JudgeAssignment` yang juga tidak pakai `public_id` karena alasan yang sama.

## Perubahan Schema

### `judges` (alter — tambah kolom)
```text
access_code   VARCHAR(8), nullable, UNIQUE   (Keputusan A/B)
```

### `score_sheets` (migration baru)
```text
id                    BIGINT UNSIGNED, PK
judging_round_id      FK → judging_rounds.id, RESTRICT
competition_entry_id  FK → competition_entries.id, RESTRICT
judge_id              FK → judges.id, RESTRICT
status                string, default 'draft'                 (Keputusan G — cuma draft dicapai sprint ini)
submitted_at          timestamp, nullable                     (disiapkan utk Sprint 10)
locked_at             timestamp, nullable                     (disiapkan utk Sprint 10)
revision_count        unsigned int, default 0                 (disiapkan utk Sprint 10)
timestamps
```
Unique: `(judging_round_id, competition_entry_id, judge_id)` — nama eksplisit pendek `score_sheets_unique` (pelajaran Sprint 8: nama auto-generate 3-kolom Laravel gampang lewat limit 64 karakter MySQL, jadi langsung dihindari dari awal).

### `scores` (migration baru)
```text
id                   BIGINT UNSIGNED, PK
score_sheet_id       FK → score_sheets.id, CASCADE
judging_criteria_id  FK → judging_criteria.id, RESTRICT
score                DECIMAL(10,3)         (CLAUDE.md — jangan pernah FLOAT)
notes                text, nullable
timestamps
```
Unique: `(score_sheet_id, judging_criteria_id)`, nama eksplisit `scores_unique`.

## Business Logic
```text
RULE-1  Akses /judge/* butuh session judge_id valid & Judge.status = active (EnsureJudgeSession).
RULE-2  Juri hanya bisa lihat round yang dia punya assignment aktif di situ (RULE-2 diverifikasi ulang
        di setiap layer: dashboard listing, index entries, show entry, save draft).
RULE-3  Kriteria yang boleh diisi = scoreable criteria hasil resolusi Keputusan D — submit criteria_id
        di luar itu ditolak (baik lewat form request maupun defense-in-depth di service).
RULE-4  Skor harus dalam rentang [minimum_score, maximum_score] kriteria terkait (skor 0 valid kalau
        minimum mengizinkan 0 — nullable numeric, bukan required, supaya draft boleh sebagian kosong).
RULE-5  Entry harus milik event_category yang sama dengan round (proteksi manipulasi URL entry dari
        kategori lain).
RULE-6  score_sheet dibuat lazy, status selalu 'draft' sprint ini (Keputusan F/G).
RULE-7  Round/Criteria yang sudah punya scores tidak bisa dihapus (Keputusan H).
```

## Permission
| Aksi | Judge (session sendiri) | Judge (round bukan miliknya) | Organizer (owner/admin) | Guest |
|---|---|---|---|---|
| Buka `/judge/access` | ✅ | ✅ | ✅ | ✅ |
| Lihat dashboard/round/entry/isi skor miliknya | ✅ | ❌ (403) | ❌ (beda area, Keputusan I) | ❌ (redirect ke access) |
| Lihat/regenerate kode akses judge | ❌ | ❌ | ✅ (via `JudgePolicy::update`) | ❌ |

## Testing Plan
```text
Judge/AccessCodeTest
  □ judge baru otomatis dapat access_code unik saat dibuat
  □ organizer bisa lihat kode akses di halaman judge (show)
  □ organizer regenerate kode → kode lama tidak lagi valid, kode baru valid

ScoreSheet/JudgeSessionTest
  □ kode valid → redirect ke dashboard, session judge_id ke-set
  □ kode salah → ditolak dengan pesan error
  □ judge status inactive → ditolak walau kode benar
  □ akses /judge/* tanpa session → redirect ke /judge/access
  □ logout → session judge_id hilang, /judge/* redirect lagi

ScoreSheet/ScoringTest
  □ juri whole-round lihat semua entry + semua kriteria leaf round itu
  □ juri kriteria-spesifik cuma lihat kriteria assignment-nya (bukan semua)
  □ entry withdrawn/disqualified tidak muncul di daftar
  □ simpan draft skor valid → tersimpan, score_sheet dibuat lazy
  □ skor di luar range minimum/maximum → ditolak
  □ submit criteria_id di luar assignment juri → ditolak
  □ entry dari kategori lain (manipulasi URL) → ditolak (404/403)
  □ decimal skor tersimpan presisi (DECIMAL 10,3, bukan FLOAT rounding)
  □ skor kosong (null) → tidak membuat baris Score / menghapus baris lama (draft parsial diizinkan)

ScoreSheet/TenantIsolationTest
  □ judge organisasi lain (bukan miliknya) tidak bisa lihat/isi round organizer lain via URL langsung
  □ judge dari org A tidak bisa mengisi skor entry milik event org B (round B, entry B, session judge A)

Judging/DestroyGuardTest (atau ditambahkan ke ManageRoundTest/ManageCriteriaTest existing)
  □ round yang sudah punya score_sheets/scores tidak bisa dihapus (pesan error ramah)
  □ criteria yang sudah punya scores tidak bisa dihapus
```

## Catatan Implementasi

**Bug fillable-guard ditemukan+diperbaiki (pola ke-3 di project ini, lihat Sprint 4/5).** `JudgeController::regenerateAccessCode` awalnya pakai `$judge->update(['access_code' => ...])` — silently no-op karena `access_code` sengaja TIDAK dimasukkan ke `Judge::$fillable` (satu-satunya jalur pengubahnya cuma aksi ini, bukan form generik). Ketahuan dari test `test_owner_can_regenerate_the_access_code` yang gagal (`assertNotSame` gagal — kode "baru" sama dengan kode lama). Fix: set properti langsung + `save()`, pola sama `CompetitionEntry::status`/`RegistrationDocument::file_path` di sprint-sprint sebelumnya.

**Bug reusable component ditemukan+diperbaiki:** `x-ui.input`/`x-ui.textarea` pakai satu `$name` prop untuk HTML `name` attribute SEKALIGUS untuk `old()`/`$errors->first()` lookup. Form scoring ini yang pertama di project pakai nama field array dinamis (`scores[5][score]`) — HTML butuh notasi bracket supaya PHP parse jadi array bersarang, tapi `old()`/error bag Laravel butuh notasi dot (`scores.5.score`). Dua string itu berbeda dan komponen lama cuma tahu satu. **Fix:** tambah prop opsional `errorKey` (default ke `$name`, jadi tidak mengubah perilaku pemanggil manapun yang sudah ada) di kedua komponen, dipakai judge/entries/show.blade.php.

Verifikasi manual (curl, live server, dua organisasi terpisah) mengonfirmasi seluruh alur: login kode akses (case-insensitive+trim, kode salah ditolak, judge inactive ditolak), dashboard menampilkan round assignment, form skor cuma menampilkan kriteria leaf yang jadi tanggung jawab (Variasi sebagai parent tidak muncul, PBB+Formasi muncul), simpan draft tersimpan presisi DECIMAL(10,3) dan notes ikut tersimpan, skor di luar range ditolak live, kriteria di luar assignment ditolak live, progress indikator update jadi "2/2", logout membersihkan session, kode akses organizer bisa dilihat+regenerate (kode lama langsung invalid), dan **tenant isolation cross-organisasi** — juri organisasi B mendapat 403 murni saat mencoba akses round organisasi A lewat URL langsung.

## File Dibuat/Diubah
```text
database/migrations/*_add_access_code_to_judges_table.php
database/migrations/*_create_score_sheets_table.php (20)
database/migrations/*_create_scores_table.php (21)
app/Enums/ScoreSheetStatus.php
app/Models/Judge.php               (+access_code, +generateAccessCode())
app/Models/ScoreSheet.php
app/Models/Score.php
app/Models/JudgingRound.php        (+scoreSheets())
app/Models/JudgingCriteria.php     (+scores(), +isLeaf())
app/Services/ScoreSheetService.php
app/Http/Middleware/EnsureJudgeSession.php
app/Http/Controllers/Dashboard/JudgeController.php      (+regenerateAccessCode)
app/Http/Controllers/Dashboard/JudgingRoundController.php    (+destroy guard)
app/Http/Controllers/Dashboard/JudgingCriteriaController.php (+destroy guard)
app/Http/Controllers/Judge/AccessController.php
app/Http/Controllers/Judge/DashboardController.php
app/Http/Controllers/Judge/ScoringController.php
app/Http/Controllers/Judge/Concerns/InteractsWithJudgeSession.php
app/Http/Requests/Judge/SaveScoreDraftRequest.php
database/factories/ScoreSheetFactory.php
database/factories/ScoreFactory.php
resources/views/layouts/judge.blade.php
resources/views/judge/access.blade.php
resources/views/judge/dashboard.blade.php
resources/views/judge/entries/index.blade.php
resources/views/judge/entries/show.blade.php
resources/views/dashboard/judges/show.blade.php   (+kode akses section)
resources/views/components/ui/input.blade.php     (+errorKey prop, lihat Catatan Implementasi)
resources/views/components/ui/textarea.blade.php  (+errorKey prop)
routes/web.php   (+judge/* group, +judges/{judge}/access-code)
tests/Feature/Judge/AccessCodeTest.php
tests/Feature/ScoreSheet/JudgeSessionTest.php
tests/Feature/ScoreSheet/ScoringTest.php
tests/Feature/ScoreSheet/TenantIsolationTest.php
```
