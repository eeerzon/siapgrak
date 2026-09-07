# Tasks — Siapgrak

Intake SEMUA request (bug, fitur, apapun). Diedit manual oleh Ersa saat brief baru masuk, dan oleh Claude saat status kerja berubah.

**Status project:** Riset & desain selesai. `_docs` baseline v5 lengkap. **Belum ada satu baris kode pun.** Belum push ke remote (sudah `git init` + commit lokal).

**Roadmap direvisi 2026-09-07 (ADR-002): MVP sekarang "Siapgrak Admin v1"** — sistem internal panitia dari setup event sampai hasil akhir, tanpa satu pun halaman publik. Public site, self-registration peserta, dan integrasi Kreenconnect dipindah ke DEFERRED (tetap ada di blueprint desain, cuma tidak dikerjakan sekarang).

## Blocking Dependency (aktif — harus diselesaikan sebelum Sprint 1)

- [ ] **Putuskan strategi auth** untuk organizer/staff/judge. Boleh mulai sederhana dengan Laravel auth biasa dulu, asal arsitektur tidak mengikat terlalu erat ke satu strategi (supaya bisa diganti SSO Kreen Connect nanti tanpa migrasi besar). Lihat Open Question di `srs/fr-organization-tenancy.md`.
- [ ] **Putuskan platform hosting/runtime** — mempengaruhi `operations/deployment.md` yang masih placeholder.

**Dicabut dari blocking (ADR-002):** konfirmasi kontrak API Kreenconnect — sekarang tidak menghalangi sprint aktif manapun, cukup relevan lagi saat fase DEFERRED "Kreenconnect Integration" dimulai.

---

## ACTIVE — Siapgrak Admin v1

Aturan wajib: setiap sprint dikerjakan sendiri-sendiri, tidak digabung. Instruksi coding harus eksplisit menyebut apa yang TIDAK diimplementasikan di sprint itu. Lihat `quality/definition-of-ready.md` dan Phase Gate Rule di bawah sebelum memulai sprint manapun.

### Phase A — Admin Foundation
- [ ] **Sprint 1 — Organization / Tenancy** — SRS sudah siap: `srs/fr-organization-tenancy.md`. Termasuk Auth dasar (login/logout/forgot-password) sebagai prasyarat. **Jangan implement team/event/registration/judging.**
- [ ] Sprint 2 — Team / School Directory — SRS belum ditulis, buat dulu sebelum mulai (lihat CLAUDE.md §Wajib Sebelum Coding Fitur)
- [ ] Sprint 3 — Event + Event Category — SRS belum ditulis. **Public Event Page TIDAK termasuk sprint ini** (deferred, lihat di bawah) — cukup CRUD event di dashboard organizer.
- [ ] **Milestone Phase A:** organizer bisa login → create organization → create event → setup category.

### Phase B — Registration Ops (Admin Entry Mode)
- [ ] Sprint 4 — Admin Participant Management: `Add Participant/Team`, `Import CSV/Excel`, `Assign Category`, `Roster`, `Documents`, `Status`, `Approval`. **Mode admin-entry saja — panitia yang input/import peserta, BUKAN peserta daftar sendiri.** SRS wajib mendesain `submitted_by` supaya kompatibel dengan self-registration nanti tanpa migrasi skema (lihat ADR-002 §Konsekuensi).
- [ ] **Milestone Phase B:** panitia bisa kelola seluruh data peserta dari Siapgrak tanpa spreadsheet eksternal, termasuk approval workflow.

### Phase C — Competition Ops
- [ ] Sprint 5 — Competition Entry + Competition Number
- [ ] Sprint 6 — Schedule + Event-day Status
- [ ] **Milestone Phase C:** panitia kelola operasional lomba (nomor peserta, jadwal, status hari-H) tanpa spreadsheet eksternal.

### Phase D — Judging
- [ ] Sprint 7 — Judges + Judging Rounds + Criteria
- [ ] Sprint 8 — Judge Assignment
- [ ] Sprint 9 — Scoring (Score Sheet, save draft)
- [ ] Sprint 10 — Submit / Lock / Revision + Audit Trail
- [ ] **Milestone Phase D:** full digital scoring, judge isolation tegak.

### Phase E — Results
- [ ] Sprint 11 — Result Calculator + Ranking
- [ ] Sprint 12 — Awards + Result Finalization
- [ ] Sprint 13 — Operational Polish (bug fix, UX gap dari internal testing panitia asli)
- [ ] **Milestone Phase E — MVP "Siapgrak Admin v1" selesai.** Organizer bisa jalankan alur penuh: Create Event → Setup Category → Input Participant → Review → Competition Number → Schedule → Judges → Criteria → Scoring → Finalize Results. **Semua tanpa satu halaman publik.**

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
