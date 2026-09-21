# Design Doc — Operational Polish (Sprint 13)

**Status:** Implemented (2026-09-16)
**Author:** Claude
**Tanggal:** 2026-09-16
**Kontrak:** `tasks.md`/`roadmap-to-mvp.md` M5: Sprint 13 = polish — coverage audit log retrofit ke Sprint 1-11, optimasi query/index (N+1, pagination 20), validasi status, integrity check, permission final, edge case dari testing panitia asli.
**Definition of Ready:** ✅ — Sprint 12 selesai (338 test). Ini bukan fitur baru — sprint terakhir sebelum MVP "Siapgrak Admin v1" dianggap selesai.

## TL;DR

Sprint terakhir Phase E, sifatnya **audit + retrofit**, bukan fitur baru: (1) `activity_logs` yang di Sprint 12 cuma dipakai untuk aksi Sprint 12 sendiri, sekarang di-retrofit ke `registration_approved/rejected`, `score_sheet_submitted/reopened`, `result_recalculated` — melengkapi daftar action canonical dari blueprint; (2) audit permission — 0 gap ditemukan (semua controller action sudah tergate benar, baik inline maupun lewat Form Request); (3) audit pagination — 0 perubahan diperlukan (list yang genuinely tak terbatas sudah `paginate(20)` sejak awal, list yang inherently kecil — anggota tim, staff organisasi — sengaja tidak dipaginate); (4) **2 N+1 query nyata ditemukan+diperbaiki**, keduanya lewat pengukuran query count langsung, bukan dugaan; (5) integrity check — 12 test baru menutup domain yang sebelumnya 0 coverage (Result, ScoreSheet, JudgeAssignment).

**Requirement ID:** `FR-OPERATIONAL-POLISH-01`

## Keputusan Desain Kritis

**A. Retrofit activity log dibatasi persis ke 5 action yang disebutkan blueprint (`registration_approved/rejected`, `score_sheet_submitted/reopened`, `result_recalculated`)** — bukan menambah logging ke SEMUA transisi status yang ada (mis. `registration_submitted`, `registration_withdrawn` tidak ikut dilog). Blueprint eksplisit cuma menyebut 8 action; 3 sudah dikerjakan Sprint 12 (`result_finalized/published`, `award_changed`), sisanya 5 ini scope Sprint 13. Menambah lebih dari itu tanpa diminta = scope creep di luar apa yang dijanjikan.

**B. Untuk aksi yang dilakukan JURI (bukan User) — `score_sheet_submitted`, `user_id` tetap NULL, identitas juri lewat `metadata.judge_id`.** Juri bukan `Authenticatable`, tidak pernah punya `Auth::id()` (fr-scoring.md Keputusan A) — memaksa `user_id` terisi untuk aksi juri akan salah secara skema (kolom itu FK ke `users`, bukan `judges`). Ini bukan bug, tapi konsekuensi langsung dari desain sesi juri sejak Sprint 9.

**C. Tidak ada retrofit ke aksi Sprint 1-8** (organization/team/event/schedule/judge-assignment CRUD) — di luar 5 action yang eksplisit disebutkan blueprint. Kalau nanti dibutuhkan audit trail lebih luas, itu keputusan baru yang perlu SRS sendiri, bukan diam-diam diperlebar di sini.

**D. Audit pagination dan permission tidak menghasilkan perubahan kode — didokumentasikan sebagai "sudah benar", bukan dilewati diam-diam.** Prinsip transparansi yang sama dipakai untuk bug: kalau audit tidak menemukan masalah, itu tetap dilaporkan (bukan cuma bug yang dilaporkan, temuan "bersih" juga bernilai buat pembaca SRS ini nanti).

**E. N+1 diperbaiki di level ROOT CAUSE (cache membership + eager load), bukan dengan menghitung ulang boolean per-view di controller.** Opsi yang dipertimbangkan: hitung `$canManageJudging`/`$canManageResults` sekali di controller lalu ganti semua `@can(...)` jadi `@if`. Ditolak karena cuma menutup 2 dari 8 kemampuan (`ability`) yang dicek di halaman event — root cause (query berulang di `EventPolicy::membershipRole()`) tetap ada untuk 6 ability lainnya dan halaman lain manapun yang nanti menambah `@can` baru. **Dipilih:** memoize `membershipRole()` per (user, event) pakai array cache store — cache HANYA hidup selama satu request/proses PHP (bukan disk/Redis), jadi tidak ada risiko data basi lintas-request di production. Satu catatan: dalam SATU test method yang mengubah role lalu langsung re-check permission pada request tersimulasi berikutnya (tanpa reboot aplikasi), cache bisa basi — tidak ada test yang melakukan pola ini sekarang, dicatat di komentar kode sebagai batasan yang perlu diingat.

**F. "Edge case dari testing panitia asli" TIDAK bisa dipenuhi secara harfiah** — tidak ada sesi testing panitia sungguhan yang terjadi dalam pengembangan ini. Daripada mengarang skenario seolah-olah itu masukan asli, item ini diselesaikan lewat audit sistematis (permission, integrity, N+1) yang menutup KELAS masalah yang sama biasanya ditemukan lewat testing nyata — dicatat transparan di sini, bukan diam-diam dianggap selesai.

## Temuan & Perbaikan

### N+1 #1 — `EventPolicy::membershipRole()` re-query per `@can()` call
Halaman event (`events/show` + partial-nya) memanggil 8 ability berbeda (`manageCategories`, `manageStaff`, `manageSchedule`, `manageCompetition`, `manageJudging`, `manageResults`, `update`, `updateStatus`) — semuanya lewat method yang sama, dan method itu MEMBUKA QUERY BARU setiap kali dipanggil karena Gate Laravel me-resolve instance `EventPolicy` BARU di setiap `@can()`, bukan reuse instance. Untuk event dengan banyak round/kriteria/award (tiap baris di loop punya `@can` sendiri), ini jadi puluhan query identik. **Diukur langsung** (bukan diduga): 111 query total untuk satu event dengan 3 kategori/6 round/12 kriteria — **71 di antaranya** adalah query `organization_users` yang PERSIS SAMA berulang-ulang.

**Fix:** `membershipRole()` di-memoize pakai `Cache::store('array')->rememberForever("event-membership:{user}:{event}", ...)`. Turun ke **20 query** untuk fixture yang sama (Keputusan E untuk detail kenapa cara ini, bukan alternatif lain).

### N+1 #2 — `judging_criteria.children` tidak di-eager-load
`JudgingCriteria::isLeaf()` mengakses `$this->children` — kalau relasi ini belum di-load, tiap panggilan bikin query baru. `EventController::show()` eager-load `categories.judgingRounds.criteria` tapi TIDAK `.children`-nya. Untuk 12 kriteria di fixture uji, ini 12 query tambahan.

**Fix:** eager load diperpanjang jadi `categories.judgingRounds.criteria.children`.

### N+1 #3 — `ResultCalculator::resolveCriteriaValues()` re-query kriteria per entry
`calculateRawScore()` dan `calculateWeightedScore()` masing-masing manggil `resolveCriteriaValues()`, dan method itu query ulang `$round->criteria()->with('children')->get()` dari NOL setiap kali — padahal struktur kriteria round itu SAMA untuk semua entry yang dihitung dalam satu `calculateForRound()`. Untuk N entry, ini `2N` query kriteria yang identik.

**Fix:** cache instance-level (`$leafCriteriaCache` keyed by round ID) di `ResultCalculator` — aman karena instance ini di-resolve sekali per request lewat container, dipakai berulang untuk semua entry dalam satu `calculateForRound()` call.

### Regression test
`tests/Feature/Event/QueryCountTest.php` — dikalibrasi LANGSUNG dari pengukuran nyata (20 query dengan kedua fix, 43 kalau cuma eager-load yang di-revert, 119 kalau cuma cache Policy yang di-revert), threshold `< 30` menangkap kedua regresi tanpa rentan ke perubahan query count kecil yang sah.

## Audit yang Tidak Menghasilkan Perubahan (didokumentasikan, Keputusan D)

- **Permission**: setiap `store`/`update`/`destroy` di semua Dashboard controller dicek — semua tergate lewat `$this->authorize()` inline atau `FormRequest::authorize()`. 0 gap.
- **Pagination**: `Event`/`Registration`/`Judge`/`Team` index (list yang tumbuh tanpa batas seiring waktu) sudah `paginate(20)` sejak sprint masing-masing. `Organization`/`OrganizationMember`/`TeamMember` index TIDAK dipaginate — sengaja, karena inherently kecil (jumlah organisasi yang diikuti satu user, staff satu organisasi, roster satu tim — puluhan, bukan ribuan).
- **Validasi status / mass-assignment bypass**: 6 model dengan lifecycle status kompleks (`Event`, `CompetitionEntry`, `CompetitionResult`, `Registration`, `RegistrationDocument`, `ScoreSheet`) dicek — tidak satupun punya `status` di `$fillable`, semua transisi HARUS lewat service yang menegakkan `TRANSITIONS`/guard eksplisit. 0 gap.

## Testing Plan
```text
ActivityLog/LoggingTest (+5 test baru)
  □ approve registration → log registration_approved dengan user+event+subject benar
  □ reject registration → log registration_rejected dengan review_notes di metadata
  □ juri submit score sheet → log score_sheet_submitted, user_id NULL, judge_id di metadata
  □ organizer reopen score sheet → log score_sheet_reopened dengan reason
  □ hitung hasil → log result_recalculated dengan judging_round_id

Result/DatabaseIntegrityTest (baru, 4 test)
  □ hapus category/round/entry → CASCADE ke competition_results (bukan RESTRICT)
  □ unique constraint (category, round, entry)

ScoreSheet/DatabaseIntegrityTest (baru, 6 test)
  □ round/entry/judge tidak bisa dihapus selama ada score sheet (RESTRICT)
  □ kriteria tidak bisa dihapus selama ada score (RESTRICT)
  □ hapus sheet → cascade ke scores
  □ unique constraint (round, entry, judge)

JudgeAssignment/DatabaseIntegrityTest (baru, 2 test)
  □ hapus round → cascade ke assignment
  □ judge tidak bisa dihapus selama ada assignment (RESTRICT, forward-looking — Judge belum punya route destroy)

Event/QueryCountTest (baru, 1 test)
  □ query count halaman event tidak scale dengan jumlah round/kriteria (regression guard N+1)
```

## File Dibuat/Diubah
```text
app/Services/RegistrationService.php   (+ActivityLogger, +log registration_approved/rejected)
app/Services/ScoreSheetService.php     (+ActivityLogger, +log score_sheet_submitted/reopened)
app/Services/ResultService.php         (+log result_recalculated)
app/Services/ResultCalculator.php      (+leafCriteriaCache — N+1 #3 fix)
app/Policies/EventPolicy.php           (membershipRole() di-memoize — N+1 #1 fix)
app/Http/Controllers/Dashboard/EventController.php  (+criteria.children eager load — N+1 #2 fix)
tests/Feature/ActivityLog/LoggingTest.php   (+5 test)
tests/Feature/Result/DatabaseIntegrityTest.php   (baru)
tests/Feature/ScoreSheet/DatabaseIntegrityTest.php   (baru)
tests/Feature/JudgeAssignment/DatabaseIntegrityTest.php   (baru)
tests/Feature/Event/QueryCountTest.php   (baru)
```
