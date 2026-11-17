# Tasks — Siapgrak

Intake SEMUA request (bug, fitur, apapun). Diedit manual oleh Ersa saat brief baru masuk, dan oleh Claude saat status kerja berubah.

**Status project:** Sprint 1-13 + 4b selesai & terverifikasi — **369 test hijau di MariaDB**. **Phase A-E TUNTAS — MVP "Siapgrak Admin v1" backend selesai**, termasuk CSV import (Sprint 4b, opsional tapi sudah dikerjakan). Belum push ke remote.

**Roadmap direvisi 2026-09-07 (ADR-002): MVP sekarang "Siapgrak Admin v1"** — sistem internal panitia dari setup event sampai hasil akhir, tanpa satu pun halaman publik. Public site, self-registration peserta, dan integrasi Kreenconnect dipindah ke DEFERRED (tetap ada di blueprint desain, cuma tidak dikerjakan sekarang).

**🔴 Pembagian kerja tim (2026-09-07):** roadmap sprint di bawah ini sekarang murni **track backend**, dikerjakan tim dev — lihat `BACKEND-HANDOFF.md` (root project) untuk kontrak lengkap. **Track frontend/UI berjalan paralel, terpisah dari nomor sprint di bawah** — lihat `_docs/frontend/frontend-plan.md` (build order sendiri F0-F12, boleh pakai mock data, tidak menunggu sprint backend selesai). Dua track ini disatukan lewat "Backend Data Contract" di frontend-plan.md — begitu backend Sprint-N live, frontend tinggal ganti sumber data tanpa re-desain.

**Progress track frontend (2026-09-10):** F0 + F1 + F3 + F4 selesai — 4/13 step. F4 (Event List/Create) = prototype dev-only di atas mock (`/dev/events`, backend Event = Sprint 3). Urutan lanjutan: ~~F3~~ → ~~F4~~ → **F5 Event Workspace** → F2 Dashboard → F6-F12. Detail per-step di `_docs/frontend/frontend-plan.md` §F0-§F4 Implementation Log dan `_docs/status/log.md`. Jalur lengkap sampai MVP: `_docs/status/roadmap-to-mvp.md`.

## Blocking Dependency

- [x] **Strategi auth Sprint 1** — diputuskan sementara: Laravel session auth biasa (register/login/logout/forgot-password, hand-rolled, tidak pakai Breeze/Fortify). Tidak mengunci ke SSO Kreen Connect; migrasi ke SSO nanti hanya perlu ganti layer auth, skema `users`/`organizations` tidak perlu berubah. **Belum final decision dari Ersa** — re-evaluasi kalau SSO Kreen jadi prioritas sebelum Sprint 2+ menambah lebih banyak yang bergantung pada auth ini.
- [ ] **Putuskan platform hosting/runtime production** — dev lokal MySQL sudah jalan (Homebrew MySQL 26.x port 8065, lihat `operations/deployment.md` §Local Development), tapi `operations/deployment.md` masih placeholder untuk bagian production (platform, trigger deploy, CI).

**Dicabut dari blocking (ADR-002):** konfirmasi kontrak API Kreenconnect — sekarang tidak menghalangi sprint aktif manapun, cukup relevan lagi saat fase DEFERRED "Kreenconnect Integration" dimulai.

---

## Halaman Utama Publik (ADR-003)

- [x] **Beranda + konten admin platform** — SELESAI 2026-11-05. SRS: `srs/fr-public-content.md`. Info Lomba, Agenda, dan Prestasi & Kabar dengan panel admin. Event yang kita kelola menaut ke Kreenconnect lewat slug.
- [ ] **Isi `KREENCONNECT_EVENT_BASE_URL`** — format URL halaman event Kreenconnect perlu dikonfirmasi.

---

## ACTIVE — Siapgrak Admin v1

Aturan wajib: setiap sprint dikerjakan sendiri-sendiri, tidak digabung. Instruksi coding harus eksplisit menyebut apa yang TIDAK diimplementasikan di sprint itu. Lihat `quality/definition-of-ready.md` dan Phase Gate Rule di bawah sebelum memulai sprint manapun.

### Phase A — Admin Foundation
- [x] **Sprint 1 — Organization / Tenancy** — SELESAI 2026-09-07 (commit `58d9d99`). Auth + Organization + organization_users + tenant isolation policy, 41 test hijau, Pint clean. Detail lengkap ada di laporan sesi (lihat riwayat percakapan Claude Code) — belum ada `status/log.md` terpisah, dipertimbangkan mulai Sprint 2.
- [x] **Sprint 2 — Team / School Directory** — SELESAI 2026-09-07 (commit lihat git log). SRS: `srs/fr-team-directory.md`. `teams`+`team_members`, `organization_id` nullable di schema tapi wajib di validasi (keputusan eksplisit Ersa, lihat SRS §Keputusan Desain Kritis — "team independen tanpa organisasi" resmi DEFERRED sampai model ownership didesain). 74 test hijau, Pint clean, tenant isolation diverifikasi manual di browser (Outsider User → Team milik user lain = 403, index tidak bocor).
- [x] **Sprint 3 — Event + Event Category + Event Staff** — SELESAI 2026-09-10. SRS: `srs/fr-event-category.md`. `events`+`event_categories`+`event_staff`, EventStatus 7-state lifecycle (transisi eksplisit + peta di `EventService`), EventPolicy (pola TeamPolicy), CRUD event/category/staff, tenant isolation adversarial. **45 test hijau di MySQL** (total 119), Pint clean, migrasi up/down bersih. `event_staff.role` dicatat tapi belum jadi authz (Keputusan A). Prototype `/dev/events` (F4) dibongkar → controller + route asli. "Events" masuk sidebar. Public Event Page tetap DEFERRED.
- [x] **Milestone Phase A:** organizer bisa login → create organization → create event → setup category. ✅

> **Catatan pembagian kerja:** Sprint 3 dikerjakan di sesi Claude (bukan tim dev) atas instruksi eksplisit Ersa 2026-09-10 ("jangan bikin prototype, kalo Sprint belum ada buat Sprint sekalian"). Track backend Sprint 4+ tetap mengikuti `BACKEND-HANDOFF.md` — konfirmasi ke Ersa siapa yang lanjut Sprint 4 sebelum mulai.

### Phase B — Registration Ops (Admin Entry Mode)
- [x] **Sprint 4 — Registration (admin-entry)** — SELESAI 2026-09-10. SRS: `srs/fr-registration.md`. `registrations`+`registration_members` (snapshot roster)+`registration_documents` (disk privat, download ber-Policy). `RegistrationStatus` 6-state lifecycle, `review_notes` wajib saat reject/revision, `submitted_by` generik utk self-registration nanti. `RegistrationPolicy` + `EventPolicy::manageRegistrations`. **40 test hijau di MySQL** (total 159). UI fungsional (tab Peserta di event → index/create/show + roster + dokumen). `submitted_by` compat self-registration ✓.
- [x] **Sprint 4b — Import CSV peserta** — SELESAI 2026-09-16 (dipecah dari Sprint 4 atas keputusan Ersa 2026-09-10). SRS: `srs/fr-registration-import.md`. **CSV saja, bukan Excel** (Keputusan A — parsing native `fgetcsv`, nol dependency baru; `.xlsx` asli didefer, bukan sepadan kompleksitasnya). Alur 3 langkah: upload → mapping kolom (tebakan otomatis) → preview & commit. Tim harus sudah ada (import tidak membuat Team baru), validasi per-kelompok-tim untuk go/no-go + per-baris untuk anggota individual, commit **partial** (kelompok invalid dilewati, bukan all-or-nothing). Registrasi hasil import langsung `submitted` (bukan `draft`) — reuse `RegistrationService::create()`+`changeStatus()`, bukan bypass. File sementara di disk privat `local` (`imports/{event_id}/{ulid}.csv`), auto-detect delimiter koma/titik-koma. **13 test hijau** (total 369). Pint clean, `migrate:fresh` bersih (tidak ada migration baru — reuse tabel `registrations`/`registration_members`), diverifikasi manual via curl end-to-end.
- [x] **Milestone Phase B:** panitia kelola seluruh data peserta dari Siapgrak tanpa spreadsheet — **alur manual ✅ (Sprint 4)**, **import CSV ✅ (Sprint 4b)**. ✅

### Phase C — Competition Ops
- [x] **Sprint 5 — Competition Entry + Competition Number** — SELESAI 2026-09-11. SRS: `srs/fr-competition-entry.md`. `competition_entries` (FK RESTRICT ke event/category/team/registration, UNIQUE registration_id, UNIQUE `(event_category_id, competition_number)`). Entry **otomatis** dibuat saat registration di-approve (idempoten), ikut `withdrawn` saat registration ditarik (kecuali sudah final). `CompetitionEntryStatus` 8-state hari-H. Nomor manual + generate sequential (atomic, isolasi per kategori). **24 test hijau di MySQL** (total 183). UI: tab "Kompetisi" di halaman event.
- [x] **Sprint 6 — Schedule** — SELESAI 2026-09-14. SRS: `srs/fr-schedule.md`. `event_schedule_slots` — list linear per-event (bukan per-kategori), `display_order` + Move Up/Down, slot general (nullable category+entry) atau spesifik. **Beda dari sprint lain: field bebas diedit + hard delete diizinkan** (bukan competition history, murni logistik). **16 test hijau** (total 199). UI: tab "Jadwal" di halaman event. `event_documents` TIDAK termasuk (tidak disebut di kontrak Sprint 6 `BACKEND-HANDOFF.md`, belum dikerjakan).
- [x] **Milestone Phase C:** panitia kelola operasional lomba (nomor peserta ✅, jadwal ✅, status hari-H ✅) tanpa spreadsheet eksternal. ✅

### Phase D — Judging
- [x] **Sprint 7 — Judges + Judging Rounds + Criteria** — SELESAI 2026-09-14. SRS: `srs/fr-judging-setup.md`. **Keputusan Desain Kritis dikonfirmasi Ersa:** `judges.organization_id` ditambah (blueprint aslinya tidak punya — gap tenant isolation, pola sama `teams` Sprint 2). Halaman `/dashboard/judges` sengaja **tidak masuk sidebar global** (CLAUDE.md mengunci ke 5 item) — reachable dari tab "Penjurian" di Event Workspace. `judging_rounds`+`judging_criteria` (max nested depth 2, ditegakkan via `Rule::exists()->whereNull('parent_id')`). **34 test hijau** (total 233). Round/Criteria destroy masih bebas — **WAJIB digembok Sprint 9** begitu `scores` ada (pola guard sama `EventCategoryController`).
- [x] **Sprint 8 — Judge Assignment** — SELESAI 2026-09-14. SRS: `srs/fr-judge-assignment.md`. `judge_assignments` — judge menilai **seluruh round** (`judging_criteria_id` NULL) atau **kriteria spesifik**, tidak keduanya (ditegakkan di Form Request, bukan cuma DB unique — MySQL `NULL != NULL` bikin unique constraint DB tidak cukup sendirian, lihat SRS Keputusan B). `event_category_id` di-omit dari tabel (100% derivable via round). Hard delete diizinkan, tidak ada endpoint update. **14 test hijau** (total 247). UI: seksi "Juri Ditugaskan" di tab Penjurian, per round. **Bug migrasi ditemukan & diperbaiki:** nama unique index auto-generate 3-kolom melebihi limit 64 karakter MySQL — lihat SRS §Catatan Implementasi.
- [x] **Sprint 9 — Scoring (Score Sheet, save draft)** — SELESAI 2026-09-15. SRS: `srs/fr-scoring.md`. **Keputusan Desain Kritis dikonfirmasi Ersa:** mekanisme akses juri = kode akses per-judge (8 karakter, session-based, BUKAN akun User/email/password) — tidak ada satu dokumen pun yang menentukan mekanisme konkret, persona BRD ("awam", "per-event hari-H") + `MAIL_MAILER=log` (belum ada email nyata) mengarah ke kode akses ketimbang akun. `score_sheets`+`scores` (DECIMAL 10,3), cuma kriteria **leaf** yang bisa diisi skor (parent w/ children = rollup, bukan diinput). Guard destroy Round/Criteria ditambahkan (WAJIB sejak Sprint 7) — tidak bisa hapus kalau sudah ada skor tersimpan. Sprint ini cuma capai status `draft` — submit/lock/reopen tetap Sprint 10. **26 test baru + 2 ditambah ke test existing hijau** (total 276). UI: area terpisah total `/judge/*` (layout sendiri, tanpa sidebar), form skor per peserta. **2 bug ditemukan & diperbaiki:** fillable-guard di `Judge::regenerateAccessCode` (pola sama Sprint 4/5), dan komponen `x-ui.input`/`x-ui.textarea` butuh prop `errorKey` baru untuk field array dinamis (`scores[5][score]`) — lihat SRS §Catatan Implementasi.
- [x] **Sprint 10 — Submit / Lock / Revision + Audit Trail** — SELESAI 2026-09-15. SRS: `srs/fr-score-sheet-lifecycle.md`. **Keputusan Desain Kritis:** reopen sheet `locked` = privileged action, didefinisikan **Owner-only** (bukan Admin) — beda dari reopen sheet `submitted` yang tetap Owner+Admin seperti aksi Judging lain. Submit (draft→submitted, wajib semua kriteria terisi) di sisi juri; Lock (submitted→locked) + Reopen (reason wajib, `score_sheet_revisions` audit trail, `revision_count` naik, Score existing TIDAK dihapus) di sisi organizer. Halaman "Lembar Skor" organizer di-gate penuh `manageJudging` (list cuma status, bukan angka — detail baru tampil di halaman per-sheet). **19 test baru hijau** (total 295). **Bug nyata ditemukan+diperbaiki:** `ScoreSheet::firstOrCreate()` tidak meng-hydrate DB default `status='draft'` ke instance PHP — laten sejak Sprint 9, ketahuan begitu view mulai baca `$sheet->status` — lihat SRS §Catatan Implementasi.
- [x] **Milestone Phase D:** full digital scoring, judge isolation tegak. ✅

### Phase E — Results
- [x] **Sprint 11 — Result Calculator + Ranking** — SELESAI 2026-09-16. SRS: `srs/fr-result-calculator.md`. **Keputusan Desain:** hasil per-round saja (`judging_round_id` tidak pernah NULL) — rollup lintas-round didefer, jarang dipakai di v1 (kebanyakan event cuma 1 round). Entry dengan kriteria belum lengkap (submitted/locked) di-skip total dari hasil, bukan ditampilkan parsial. Tie handling eksplisit: standard competition ranking "1-2-2-4", urutan tampilan antar-seri stabil via `competition_number`. Penalti diinput manual organizer, dipertahankan lewat Hitung Ulang (delete+rebuild per round). `$fillable` kosong total di `CompetitionResult` — semua field diset langsung di service. Tab "Hasil" di-gate penuh `manageJudging`, sama seperti Lembar Skor Sprint 10. **19 test baru hijau** (total 314). **Gap ditemukan+diperbaiki sebelum lapor selesai:** relasi `results()` awalnya cuma `orderBy('rank')` — urutan tampilan antar-seri tidak benar-benar terjamin di level query walau kalkulator sudah benar; di-join ke `competition_entries` + `orderBy` kedua eksplisit — lihat SRS §Catatan Implementasi.
- [x] **Sprint 12 — Awards + Result Finalization** — SELESAI 2026-09-16. SRS: `srs/fr-awards-finalization.md`. Lifecycle hasil meniru persis `ScoreSheetStatus` Sprint 10: `Draft→Finalized→Published`, reopen dari `Finalized`=Owner+Admin, reopen dari `Published`=**Owner-only privileged** (`EventPolicy::reopenPublishedResult`). Hitung Ulang diblokir begitu tidak lagi `draft`. `awards` CRUD manual organizer (hard delete diizinkan, `registration_member_id` skema-saja belum di-form). `activity_logs` dibangun + dipakai untuk aksi Sprint 12 sendiri saja (finalize/publish/reopen/award) — **bukan** retrofit ke sprint lama (itu scope Sprint 13). Tidak ada `ResultPolicy`/`AwardPolicy` terpisah — `EventPolicy::manageResults` ditambah (role check sama `manageJudging`, tapi domain Phase E sendiri; Sprint 11's `ResultController` ikut di-refactor pakai method baru ini). `CalculateCompetitionResults` job pakai `dispatchSync()` (bukan `dispatch()`) karena tidak ada queue worker yang pernah jalan di project ini. **24 test baru hijau** (total 338). **2 bug nyata ditemukan+diperbaiki:** `Eloquent\Builder::value()` ternyata tetap lewat cast model (bukan raw DB value seperti diasumsikan) — `ResultStatus::from()` dipanggil dobel di atas nilai yang sudah ter-cast, TypeError; dan `@endif@if` tanpa spasi di Blade bikin directive kedua tertinggal jadi teks literal sementara `@endif` penutupnya tetap ter-compile sendirian — keduanya lihat SRS §Catatan Implementasi.
- [x] **Sprint 13 — Operational Polish** — SELESAI 2026-09-16. SRS: `srs/fr-operational-polish.md`. Sifatnya audit+retrofit, bukan fitur baru. **Activity log retrofit** ke 5 action canonical yang belum tercover Sprint 12: `registration_approved/rejected`, `score_sheet_submitted/reopened` (juri: `user_id` NULL, identitas lewat `metadata.judge_id`), `result_recalculated`. **Audit permission**: 0 gap — semua controller action sudah tergate. **Audit pagination**: 0 perubahan — list tak terbatas (Event/Registration/Judge/Team) sudah `paginate(20)`, list inherently kecil (Organization/OrganizationMember/TeamMember) sengaja tidak. **3 N+1 nyata ditemukan+diperbaiki** (diukur langsung, bukan diduga): `EventPolicy::membershipRole()` re-query 71x di halaman event (fix: memoize per-request via array cache), `judging_criteria.children` tidak di-eager-load (fix: perpanjang eager load), `ResultCalculator` re-query kriteria round 2x per entry saat hitung hasil (fix: cache instance-level). Halaman event turun dari 111 → 20 query untuk fixture uji. Regression test `Event/QueryCountTest` dikalibrasi dari pengukuran nyata (30 sebagai ambang, antara 20 sehat dan 43/119 kalau salah satu fix di-revert). **Integrity check**: 12 test baru menutup domain yang sebelumnya 0 coverage (Result CASCADE-semua, ScoreSheet RESTRICT-semua, JudgeAssignment). "Edge case dari testing panitia asli" tidak bisa dipenuhi harfiah — tidak ada sesi testing nyata, diselesaikan lewat audit sistematis di atas, dicatat transparan bukan diam-diam dianggap selesai. **18 test baru hijau** (total 356).
- [x] **Milestone Phase E — MVP "Siapgrak Admin v1" SELESAI.** ✅ Organizer bisa jalankan alur penuh: Create Event → Setup Category → Input Participant → Review → Competition Number → Schedule → Judges → Criteria → Scoring → Finalize Results. **Semua tanpa satu halaman publik.**

---

## DEFERRED — later, tetap ada di blueprint desain

Detail lengkap tetap tersimpan di `research/chatgpt-blueprint-v1.md` dan `research/chatgpt-dev-checklist-migration-routes.md` — tidak hilang, cuma tidak dikerjakan sekarang. Re-evaluasi urutan ini setelah Phase E (MVP Admin v1) selesai dan sudah divalidasi oleh panitia LKBB asli.

```text
Public homepage
Public event discovery
Public team directory
Public organization profile
Public results page
Participant self-registration (frontend peserta di atas registration engine yang sama)
Kreenconnect ticketing integration
Kreenconnect voting integration
Kreenconnect checkout
Team ranking ecosystem
Event Command Center (advanced/live dashboard hari-H)
```

## Aturan UI Selama Fase Admin-First

- Sidebar dashboard: `Dashboard / Events / Teams / Organization / Settings` — tidak lebih dari ini.
- Event Workspace: `Overview` + **COMPETITION** (Participants/Categories/Schedule/Judging/Results) + **OPERATIONS** (Documents/Staff) + `Settings`.
- Menu `Ticketing`/`Voting` **di-hide total, BUKAN "Coming Soon"** — produk Admin v1 harus terasa fokus dan selesai, bukan setengah jadi. Lihat ADR-002.

## Phase Gate Rule

Tidak boleh lanjut ke sprint/phase berikutnya kalau:
- Test failing
- Migration rollback (`down()`) gagal
- Tenant isolation belum ditest
- Status transition (registration/entry/score sheet/result) belum jelas
- Existing flow yang sudah jalan jadi rusak

Setiap phase harus independently deployable — lihat `research/chatgpt-dev-checklist-migration-routes.md` §119.
