# Roadmap to MVP — Siapgrak Admin v1

**Dibuat:** 2026-09-10 (Claude, dari audit 30 file `_docs` + kode nyata + test run)
**Sifat:** dokumen sintesis/rujukan — bukan pengganti `tasks/tasks.md` (roadmap resmi, dikurasi Ersa), `BACKEND-HANDOFF.md` (kontrak tim dev), atau `frontend/frontend-plan.md` (build order F0-F12). Kalau ada konflik, tiga dokumen itu yang menang.
**Tujuan:** satu tempat untuk lihat "dari kondisi sekarang sampai MVP selesai, jalurnya apa, urutannya bagaimana, gate-nya di mana".

---

## 1. Status terverifikasi (2026-09-10)

| Area | Kondisi nyata |
|---|---|
| Environment | Bootstrap lokal beres (composer, node 26, `npm run build`). **MySQL 26.x di port 8065** (Homebrew, coexist XAMPP MariaDB 3306) — dev DB `siapgrak`, test DB `siapgrak_testing`, 74 test hijau di MySQL. Lihat `operations/deployment.md` §Local Development |
| Backend | **Sprint 1-13 + 4b SELESAI — MVP "Siapgrak Admin v1" tuntas** (Phase A-E semua tuntas; Phase B alur manual + import CSV keduanya tuntas). **369 test hijau di MariaDB (XAMPP)**, Pint clean, migrasi up/down bersih, 3 N+1 nyata diperbaiki di Sprint 13 |
| Frontend | **SEMUA 13 STEP SELESAI (F0-F12)** 2026-09-16 — lihat `frontend-plan.md` untuk log per step |
| Git | Commit masih lokal — `tasks.md` catat belum push ke remote |
| Halaman publik | 0 (sesuai ADR-002) |

**Pembagian track (CLAUDE.md):** backend Sprint 3-13 = tim dev (`BACKEND-HANDOFF.md`), frontend F2-F12 = sesi Claude (`frontend-plan.md`). Paralel, disatukan lewat "Backend Data Contract" di `frontend-plan.md` §8.

---

## 2. Sudah selesai

**Backend**
- Sprint 1 — Organization/Tenancy: `organizations`, `organization_users`, OrganizationPolicy, OrganizationService, tenant isolation adversarial (41 test)
- Sprint 2 — Team/School Directory: `teams`, `team_members` (roster, bukan authorization), TeamPolicy, TeamService, `organization_id` nullable di schema tapi wajib di validasi (SRS `fr-team-directory.md` §Keputusan Desain Kritis)

**Frontend**
- F0 — token warna (slate + sky), font Inter, 15 komponen `components/ui/`, gallery `/dev/ui`
- F1 — shell: sidebar / topbar / mobile-nav / account-menu / page-header / breadcrumb; 8 halaman Sprint 1-2 dimigrasi ke `<x-layout.page-header>`

---

## 3. Sisa pekerjaan

### Backend — 6 sprint tersisa (13 total, Phase A-E; Sprint 1-7 ✅)

| Sprint | Domain | Tabel baru |
|---|---|---|
| ~~3~~ | ~~Event + Category + staff~~ | ✅ SELESAI 2026-09-10 (`srs/fr-event-category.md`) |
| ~~4~~ | ~~Registration (admin-entry)~~ | ✅ SELESAI 2026-09-10 (`srs/fr-registration.md`) — CSV import = Sprint 4b ✅ SELESAI 2026-09-16 |
| ~~5~~ | ~~Competition Entry + Number~~ | ✅ SELESAI 2026-09-11 (`srs/fr-competition-entry.md`) |
| ~~6~~ | ~~Schedule~~ | ✅ SELESAI 2026-09-14 (`srs/fr-schedule.md`) — `event_documents` belum (tidak ada di kontrak Sprint 6) |
| ~~7~~ | ~~Judges + Rounds + Criteria~~ | ✅ SELESAI 2026-09-14 (`srs/fr-judging-setup.md`) |
| 7 | Judges + Rounds + Criteria | `judges`, `judging_rounds`, `judging_criteria` |
| 8 | Judge Assignment | `judge_assignments` |
| 9 | Scoring (draft) | `score_sheets`, `scores` |
| 10 | Submit/Lock/Revision + Audit | `score_sheet_revisions` |
| 11 | Result Calculator + Ranking | `competition_results` |
| 12 | Awards + Finalization | `awards`, `activity_logs` |
| 13 | Operational Polish | — |

### Frontend — 11 step (urutan revisi Ersa 2026-09-09: F3 → F4 → F5 → F2 → F6-F12)

F3 Teams polish · F4 Event List+Create · F5 Event Workspace shell · F2 Dashboard · F6 Participants · F7 Categories · F8 Schedule · F9 Judging Setup (organizer) · F10 Judge Scoring (UI terpisah) · F11 Results · F12 Responsive+UX audit

### DEFERRED (tetap di desain — ADR-002)

Public site · Participant self-registration · Kreenconnect ticketing/voting · Team ranking ecosystem · Event Command Center

---

## 4. Blocker / keputusan yang butuh Ersa

| # | Keputusan | Kenapa | Rekomendasi | Status |
|---|---|---|---|---|
| B1 | ~~Database lokal: MySQL vs SQLite~~ | — | — | ✅ **RESOLVED 2026-09-10, dipindah 2026-09-14** — sekarang XAMPP MariaDB port 3306 (semula sempat Homebrew MySQL port 8065, dipindah balik ke XAMPP atas permintaan Ersa biar gampang dicek phpMyAdmin). DB `siapgrak` + `siapgrak_testing`. 183 test hijau di MariaDB. Detail: `operations/deployment.md` §Local Development |
| B2 | Strategi auth final: session hand-rolled (sekarang) vs Kreen SSO | `tasks.md` §Blocking — makin banyak sprint numpuk di atas auth | Pertahankan session auth utk MVP; SSO nanti (layer terisolasi) | ☐ |
| B3 | Platform hosting production | `deployment.md` / `rollback.md` / `release-checklist.md` masih placeholder | Boleh ditunda s/d ~Sprint 8, wajib beres sebelum event nyata pertama | ☐ |
| B4 | Klik-test manual F0/F1 (Modal / Drawer / account-menu) di browser foreground | Belum diverifikasi end-to-end; F3+ gantung ke pola dropdown/modal | 10 menit Ersa langsung di browser | ☐ |
| B5 | `operations/backup-restore.md` belum ada | `nfr.md` §Recoverability: prasyarat sebelum Sprint 4 selesai (data user nyata masuk) | Tulis sebelum M2 | ☐ |
| B6 | Push ke remote | Dua track paralel butuh baseline bersama | Segera | ☐ |

---

## 5. Milestone

Aturan yang dipegang di SEMUA milestone (Constitution + checklist `_docs/quality/` + `_docs/ai/review-checklist.md`):

- 1 sprint / step = 1 unit; instruksi coding sebut eksplisit apa yang **tidak** dikerjakan
- SRS ditulis sebelum coding fitur baru (`_docs/srs/fr-*.md`, pola `fr-team-directory.md`)
- Tenant isolation Policy + test untuk tiap resource ber-`organization_id`/`event_id` (pola `tests/Feature/Team/TenantIsolationTest.php`)
- Migration per-sprint, `up()` + `down()` sama-sama jalan
- Skor `DECIMAL(10,3)` — **never FLOAT**; nama tabel/kolom English; PK `BIGINT UNSIGNED`
- **Tidak pernah** ada tabel transaksi Kreenconnect
- Judge isolation & score-sheet lock utuh; roster di-snapshot per registration; tidak ada cascade delete competition history
- Jalankan `review-checklist.md` + `definition-of-done.md` sebelum lapor selesai
- Update `status/log.md` tiap sesi; `tasks.md` tiap sprint tutup
- Kalau ketemu design gap otorisasi: **STOP, lapor pilihan desain** — jangan diam-diam nambah kolom/tabel

### M0 — Unblock (butuh Ersa)

Selesaikan B1-B6. Output: MySQL lokal jalan + `.env` benar, auth strategy dikunci, F0/F1 diverifikasi visual, push ke remote, `backup-restore.md` draft.

### M1 — Phase A selesai: Event Foundation ✅ (2026-09-10)

- **Backend Sprint 3** ✅ — `srs/fr-event-category.md`, `events`/`event_categories`/`event_staff`, `EventStatus` 7-state, `EventPolicy` (pola TeamPolicy; kategori/staff digate lewat parent, bukan policy sendiri), `EventService` (transisi status), 3 controller, 45 test. B1 (MySQL lokal) juga resolved di milestone ini.
- **Frontend F3** ✅ — Teams production-quality (table→card, 2 empty state, tab Overview/Anggota).
- **Frontend F4** ✅ — Event List + Create + Edit + Overview (tab Overview/Kategori/Staff). Awalnya prototype `/dev/events` (2026-09-10 pagi), lalu di-rewire ke `EventController` nyata saat Sprint 3 dibangun sesi yang sama. "Events" masuk sidebar + mobile nav.
- **Frontend F5** ✅ SELESAI 2026-09-16 — Overview dibangun ulang jadi readiness summary+Next Action (bukan field list statis), tab-bar dikelompokkan visual COMPETITION/OPERATIONS lewat divider (bukan side-nav vertikal penuh — keputusan scope, lihat `frontend-plan.md` §F5 Log). Ticketing/Voting **di-hide total**.
- **Milestone Phase A tercapai**: organizer login → create org → create event → setup category. ✅

### M2 — Phase B: Registration Ops (alur manual ✅ 2026-09-10)

- **Backend Sprint 4** ✅ — `srs/fr-registration.md`. `registrations`/`registration_members` (snapshot)/`registration_documents` (disk `local` privat, download ber-Policy). `RegistrationStatus` 6-state, `review_notes` wajib reject/revision, `submitted_by` generik. 40 test. UNIQUE `(event_category_id, team_id)`, FK RESTRICT ke event/category/team.
- **Backend Sprint 4b** ✅ (2026-09-16) — `srs/fr-registration-import.md`. **CSV saja** (bukan Excel — parsing native `fgetcsv`, nol dependency baru, `.xlsx` didefer sebagai keputusan terpisah kalau nanti benar-benar dibutuhkan). Alur 3 langkah upload→mapping(tebakan otomatis)→preview&commit. Tim harus sudah ada (tidak membuat Team baru), validasi per-kelompok-tim (go/no-go) + per-baris (anggota individual), commit **partial** bukan all-or-nothing. Hasil import langsung `submitted` (reuse `RegistrationService::create()`+`changeStatus()`). File sementara di disk privat `local` (`imports/{event_id}/{ulid}.csv`, token ULID, tanpa auto-cleanup file yang ditinggal — keterbatasan v1 terdokumentasi), delimiter koma/titik-koma auto-detect. 13 test (total 369). Tidak ada migration baru (reuse tabel `registrations`/`registration_members`). **2 bug ditemukan+diperbaiki sendiri lewat self-review sebelum testing** (bukan dari test failure): BOM-handling `readRows()` (rewind tanpa syarat membatalkan skip-BOM yang sudah benar — fix: satu `$contentStart` konsisten), double-submit di halaman preview (tombol submit + `.submit()` manual dobel — fix: ikut pola `onsubmit` yang sudah konsisten dipakai file lain). Diverifikasi juga manual end-to-end via curl (login→upload→mapping→preview→commit→cek DB+cleanup file).
- **Frontend F6** ✅ SELESAI 2026-09-16 — list Peserta dapat drawer quick-view (team/kategori/status/No. Kompetisi/roster/dokumen), full page tetap untuk aksi besar. Tombol "Import CSV" ditambah ke halaman index Peserta (Sprint 4b). `team_member_id` picker dan per-doc review tetap belum ada — bukan bagian F6, tidak disebut di spec asli.
- Prereq `backup-restore.md`: masih **belum dibuat** — perlu sebelum data user nyata masuk production (bukan blocker dev lokal).
- **Milestone Phase B TERCAPAI**: alur manual kelola peserta + approval ✅; import CSV ✅.

### M3 — Phase C: Competition Ops ✅ (2026-09-14)

- **Backend Sprint 5** ✅ — `srs/fr-competition-entry.md`. `competition_entries` auto-create saat registration approved (idempoten), competition number manual+sequential (**atomic** via transaction+lock), status 8-state hari-H (check-in dst). 24 test. Tidak ada `CompetitionEntryPolicy` terpisah (gate lewat `EventPolicy::manageCompetition`, pola Sprint 3/4).
- **Backend Sprint 6** ✅ — `srs/fr-schedule.md`. `event_schedule_slots` — list linear per-event, `display_order`+Move Up/Down, slot general/spesifik (kategori & entry nullable). **Beda pola sprint lain: field bebas diedit + hard delete diizinkan** (logistik, bukan histori kompetisi). 16 test (total 199). `event_documents` TIDAK termasuk (tidak ada di kontrak Sprint 6).
- **Frontend**: UI fungsional tab "Kompetisi" + tab "Jadwal" di `events/show` (generate/edit nomor, ubah status, tambah/reorder/hapus slot). **F7 (Categories) ✅ SELESAI 2026-09-16** — card list + drawer create/edit, lihat `frontend-plan.md` §F7 Log (termasuk fix bug isolasi form ganda di satu halaman). **F8 (Schedule) ✅ SELESAI 2026-09-16** — aksi "Edit Slot" yang belum pernah ada UI-nya ditambahkan (drawer, sama pola isolasi form ganda dari F7), `x-ui.select` ikut diperluas dukungan `errorKey`-nya.
- **Bug nyata ditemukan+diperbaiki**: `ScheduleService::moveUp/moveDown` salah tuker urutan kalau ≥3 slot — Eloquent `orderBy()` aditif bukan mengganti, jadi override `orderByDesc` di atas relasi yang sudah punya `orderBy` bawaan tidak berlaku. Ketahuan dari klik manual browser (test otomatis awal cuma 2 slot, tidak bisa mendeteksi kelas bug ini). Fix + regression test ditambahkan.
- **Milestone Phase C tercapai**: nomor peserta ✅, jadwal ✅, status hari-H ✅ — semua tanpa spreadsheet eksternal.

### M4 — Phase D: Judging (paling sensitif) — Sprint 7-10 ✅ tuntas

- **Backend Sprint 7** ✅ (2026-09-14) — `srs/fr-judging-setup.md`. `judges` (**+organization_id ditambah** — gap tenant isolation di blueprint asli, dikonfirmasi Ersa; halaman `/dashboard/judges` sengaja tidak masuk sidebar), `judging_rounds`, `judging_criteria` (**max nested depth 2** via `Rule::exists()->whereNull('parent_id')`). 34 test (total 233). **Catatan wajib Sprint 9:** tambah guard destroy Round/Criteria begitu `scores` ada (Round/Criteria masih bebas dihapus sekarang).
- **Backend Sprint 8** ✅ (2026-09-14) — `srs/fr-judge-assignment.md`. `judge_assignments` — judge menilai **seluruh round** (`judging_criteria_id` NULL) atau **kriteria spesifik**, tidak boleh dua-duanya (MySQL `NULL != NULL` bikin DB unique constraint tidak cukup sendiri — ditegakkan tambahan di Form Request). `event_category_id` sengaja di-omit dari tabel (derivable via round). Hard delete diizinkan, tidak ada endpoint update. 14 test (total 247). UI: seksi "Juri Ditugaskan" per round di tab Penjurian. **Bug migrasi ditemukan+diperbaiki**: nama unique index 3-kolom auto-generate melebihi limit 64 karakter MySQL, menyebabkan `CREATE TABLE` sukses tapi `ALTER TABLE` index gagal tanpa migrasi tercatat "Ran" — retry berikutnya keliru terbaca "table already exists". Fix: nama index eksplisit pendek.
- **Backend Sprint 9** ✅ (2026-09-15) — `srs/fr-scoring.md`. **Keputusan Desain Kritis dikonfirmasi Ersa:** akses juri pakai **kode akses per-judge** (8 karakter, session-based) — bukan akun User/email/password. Tidak ada dokumen yang menentukan mekanisme konkret; persona BRD (juri = awam, aktif cuma hari-H) + `MAIL_MAILER=log` (belum ada pengiriman email nyata) mengarahkan ke kode akses ketimbang alur invite-by-email. `score_sheets`+`scores` (`DECIMAL(10,3)`), hanya kriteria **leaf** (tanpa subcriteria) yang diisi langsung — parent dengan anak murni pengelompokan, skornya di-rollup nanti (Sprint 11). Sprint ini cuma capai status `draft` — submit/lock/reopen tetap Sprint 10 (pemisahan sudah direncanakan sejak awal). **Guard destroy Round/Criteria ditambahkan** (janji Sprint 7) — tidak bisa hapus kalau sudah ada skor tersimpan. Area `/judge/*` terpisah total (layout sendiri, tanpa sidebar/settings). 26 test baru + 2 ditambah ke test existing (total 276). **2 bug ditemukan+diperbaiki**: fillable-guard di `regenerateAccessCode` (pola sama Sprint 4/5 — set-langsung+save() bukan mass-assignment), dan komponen `x-ui.input`/`x-ui.textarea` butuh prop `errorKey` baru karena field array dinamis (`scores[5][score]`) butuh notasi HTML (bracket) berbeda dari notasi lookup `old()`/error bag Laravel (dot) — fix backward-compatible, prop opsional default ke nama lama.
- **Backend Sprint 10** ✅ (2026-09-15) — `srs/fr-score-sheet-lifecycle.md`. **Keputusan Desain Kritis:** reopen sheet `locked` = privileged action, **Owner-only** (bukan Admin) — beda dari reopen sheet `submitted` yang tetap Owner+Admin seperti aksi Judging lain. Submit (draft→submitted, wajib semua kriteria terisi, pesan eksplisit — bukan ditebak dari form) di sisi juri; Lock (submitted→locked) + Reopen (**reason wajib**, `score_sheet_revisions` audit trail, `revision_count` naik, Score existing TIDAK dihapus) di sisi organizer. Sheet non-draft read-only total untuk juri (render + endpoint). Halaman "Lembar Skor" organizer di-gate penuh `manageJudging` — list cuma status/progress (bukan angka, hindari kebocoran ranking), detail nilai per kriteria baru di halaman per-sheet. 19 test baru (total 295). **Bug nyata ditemukan+diperbaiki**: `ScoreSheet::firstOrCreate()` tidak meng-hydrate default DB `status='draft'` ke instance PHP — laten sejak Sprint 9, ketahuan begitu view mulai baca `$sheet->status` (4 test lama sempat crash 500). Fix: default level-model.
- **Frontend F9** ✅ SELESAI 2026-09-16 — readiness checklist per kategori (Round/Kriteria/Juri), kriteria jadi card grid + drawer add/edit (bukan spreadsheet). Aksi "Edit Kriteria" yang belum pernah ada UI-nya ditambahkan, sama pola isolasi form ganda F7/F8 — lihat `frontend-plan.md` §F9 Log.
- **Frontend F10**: Judge Scoring — interface terpisah total, tanpa sidebar/settings: nilai → Save Draft → Submit → lanjut. ✅ (dibangun langsung sebagai bagian Sprint 9-10 backend, bukan mock terpisah)
- **Test terberat**: judge A tak bisa buka sheet judge B ✅; tak bisa nilai criteria di luar assignment ✅; locked sheet immutable ✅; reopen wajib reason + `revision_count` naik ✅; tenant — organizer tak bisa manipulasi skor event lain ✅.
- **Milestone Phase D tercapai**: full digital scoring, judge isolation tegak. ✅

### M5 — Phase E: Results → MVP selesai

- **Backend Sprint 11** ✅ (2026-09-16) — `srs/fr-result-calculator.md`. **Keputusan Desain:** hasil per-round saja (`judging_round_id` tidak pernah NULL) — rollup lintas-round didefer, kebanyakan event v1 cuma 1 round jadi per-round SUDAH JADI hasil keseluruhan. Entry dengan kriteria belum lengkap di-skip total dari hasil (bukan ditampilkan parsial). **Tie handling eksplisit**: standard competition ranking "1-2-2-4", urutan tampilan antar-seri stabil via `competition_number` (bukan urutan DB yang tidak terjamin — sempat jadi gap nyata, lihat catatan bug di bawah). Penalti diinput manual organizer, dipertahankan lewat Hitung Ulang (delete+rebuild per round, `$fillable` kosong total — semua field diset langsung di service). Tab "Hasil" di-gate penuh `manageJudging`, sama seperti Lembar Skor Sprint 10. 19 test (total 314). **Gap ditemukan+diperbaiki sebelum lapor selesai**: relasi `results()` awalnya cuma `orderBy('rank')` — urutan tampilan antar-seri tidak benar-benar terjamin di level query walau kalkulator sudah benar secara in-memory; di-join ke `competition_entries` + `orderBy` kedua eksplisit ke `competition_number`.
- **Backend Sprint 12** ✅ (2026-09-16) — `srs/fr-awards-finalization.md`. Lifecycle hasil meniru persis `ScoreSheetStatus` Sprint 10: `Draft→Finalized→Published`. Reopen dari `Finalized` = Owner+Admin (`manageResults`); reopen dari `Published` = **privileged, Owner-only** (`reopenPublishedResult`, pola sama `reopenLockedScoreSheet`). Hitung Ulang diblokir begitu round tidak lagi `draft`. `awards` CRUD manual organizer (hard delete diizinkan, `registration_member_id` ada di skema tapi belum di-form). `activity_logs` dibangun + dipakai untuk aksi Sprint 12 sendiri (finalize/publish/reopen/award) — **retrofit ke sprint lama tetap scope Sprint 13**, bukan diam-diam dilewati. Tidak ada `ResultPolicy`/`AwardPolicy` terpisah — `EventPolicy::manageResults` ditambah (role check sama, domain Phase E sendiri; Sprint 11's `ResultController` ikut direfactor pakai ini). Job `CalculateCompetitionResults` pakai `dispatchSync()` (tidak ada queue worker yang pernah jalan di project ini). 24 test (total 338). **2 bug nyata ditemukan+diperbaiki**: `Eloquent\Builder::value()` ternyata tetap lewat cast model (bukan raw DB value) — `ResultStatus::from()` dipanggil dobel di atas nilai yang sudah ter-cast, TypeError; dan `@endif@if` tanpa spasi di Blade bikin directive kedua tertinggal jadi teks literal, `@endif` penutupnya ter-compile sendirian jadi PHP syntax error (jejaknya sempat membingungkan karena muncul di test lain yang tidak berhubungan).
- **Backend Sprint 13** ✅ (2026-09-16) — `srs/fr-operational-polish.md`. Audit+retrofit, bukan fitur baru. **Activity log retrofit** ke 5 action canonical (`registration_approved/rejected`, `score_sheet_submitted/reopened` — juri: `user_id` NULL, identitas lewat `metadata.judge_id`, `result_recalculated`). **Audit permission & pagination: 0 gap** (didokumentasikan sebagai temuan bersih, bukan dilewati diam-diam). **3 N+1 nyata ditemukan+diperbaiki, diukur langsung bukan diduga**: `EventPolicy::membershipRole()` re-query 71x per halaman event (Gate resolve instance Policy baru tiap `@can()`) → di-memoize per-request via array cache store; `judging_criteria.children` tidak di-eager-load → diperpanjang; `ResultCalculator` re-query kriteria round 2x per entry → cache instance-level. Halaman event: 111 → 20 query untuk fixture uji. Regression test `Event/QueryCountTest` dikalibrasi dari pengukuran nyata (ambang 30, antara 20 sehat vs 43/119 kalau salah satu fix di-revert). **Integrity check**: 12 test baru menutup domain yang 0 coverage sebelumnya (Result CASCADE-semua, ScoreSheet RESTRICT-semua, JudgeAssignment). "Edge case dari testing panitia asli" tidak bisa dipenuhi harfiah (tidak ada sesi testing nyata) — diselesaikan lewat audit sistematis di atas, dicatat transparan. 18 test baru (total 356).
- **Frontend F11** ✅ SELESAI 2026-09-16 — ranking table, Finalize, Publish (setelah finalized) semua sudah cocok spec sejak Sprint 11-12; ditambahkan tombol "Review Skor Juri" (lompat ke tab Penjurian) yang belum ada. Bonus: ditemukan+diperbaiki bug lintas-tab — halaman selalu reload ke Overview setelah submit gagal di tab manapun (Categories/Schedule/Judging/Results/Staff/Competition), jadi drawer/error yang sudah benar dihitung F7-F9 tersembunyi di balik tab yang salah. Publish-ke-publik tetap DEFERRED (ADR-002).
- **Frontend F12** ✅ SELESAI 2026-09-16 — audit struktural (bukan visual, tidak ada tool browser di sesi ini): drawer/modal tidak pernah kunci scroll background (fix di komponen bersama, dampak ke F6-F9 sekaligus), beberapa tap target di bawah ukuran nyaman disentuh, 6 grid form F7/F9 melanggar aturan mobile-first sendiri. Lihat `frontend-plan.md` §F12 Log. **Frontend F0-F12 tuntas 13/13 step.**
- **Milestone Phase E TERCAPAI**: **MVP "Siapgrak Admin v1" backend SELESAI** ✅ — alur penuh Create Event → Category → Participant → Review → Number → Schedule → Judges → Criteria → Scoring → Finalize berjalan end-to-end, 356 test hijau (415 setelah Sprint 4b + F2/F5-F9/F11/F12 menyusul). Nol halaman publik. **Frontend F0-F12 juga TUNTAS 13/13 step** (2026-09-16) — bukan lagi kerja lanjutan terbuka.

### M6 — Hardening & validasi event nyata (gate sebelum DEFERRED)

- Isi `deployment.md` / `release-checklist.md` / `rollback.md` konkret sesuai platform terpilih.
- `backup-restore.md` diuji dengan restore drill nyata.
- Production smoke test (`test-strategy.md` lapis 6) + ≥1 E2E happy-path per phase (lapis 5).
- `composer audit` di CI + `security-review` pass.
- **Jalankan ≥1 event LKBB nyata end-to-end bareng panitia asli** (metric BRD §5). Kumpulkan UX gap → backlog polish.
- Baru setelah ini: pertimbangkan fase DEFERRED.

### M7 — Post-MVP / DEFERRED (re-evaluasi urutan setelah M6; butuh Ersa + tim Kreenconnect)

1. **Public site** — homepage, event discovery, public event page, team/org profile, public results (URL slug/`public_id` — kolom sudah ada).
2. **Participant self-registration** — frontend peserta di atas registration engine yang sama (`submitted_by` sudah didesain untuk ini) + auth team representative.
3. **Kreenconnect Phase 6** — **konfirmasi kontrak API ke tim Kreenconnect dulu** (masih proposal). `event_integrations` / `integration_snapshots` / `integration_sync_logs` / `voting_candidate_mappings`, KreenconnectClient + Ticketing/Voting service, webhook signature verification, checkout return **jangan percaya `?payment=success`**.
4. **Team ranking ecosystem** (Phase 7) — hitung dari canonical data, **jangan bikin tabel `team_statistics`**.
5. **Event Command Center** (Phase 8) — live dashboard, **polling 10-30 dtk, bukan WebSocket**.

---

## 6. Koordinasi dua track

```
Fase   Backend (tim dev)              Frontend (Claude)
M1     Sprint 3 (Event/Category)      F3 Teams -> F4 Events(mock) -> F5 Workspace
M2     Sprint 4 (Registration)       F6 Participants -> F2 Dashboard
M3     Sprint 5-6 (Entry/Schedule)   F7 Categories -> F8 Schedule
M4     Sprint 7-10 (Judging)         F9 Judging Setup -> F10 Judge Scoring
M5     Sprint 11-13 (Results/Polish) F11 Results -> F12 UX audit
```

Sinkron: tiap Sprint-N backend landing → frontend cross-check field view terhadap migration sprint itu, update §Backend Data Contract di `frontend-plan.md`. Frontend selalu pakai factory dgn nama kolom = skema final → swap mock→real tanpa re-desain.
