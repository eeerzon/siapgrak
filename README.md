# Siapgrak

Platform operasional untuk Paskibra/LKBB (Lomba Ketangkasan Baris Berbaris) — bagian dari ekosistem Kreen Connect.

## Konteks

Siapgrak dibuat karena panitia LKBB saat ini menjalankan seluruh operasional lomba (pendaftaran peserta, penomoran, jadwal, penilaian juri, rekap hasil) manual lewat spreadsheet dan grup WhatsApp. Referensi kompetitor: Simpaskor.id (riset lengkap ada di `_docs/research/`).

**Batas arsitektur penting (ADR-001):** Siapgrak **tidak pernah** membangun ticketing/voting/payment sendiri. Itu semua domain **Kreenconnect** (transaction engine ekosistem Kreen), diakses lewat API nanti. Siapgrak fokus jadi platform operasional lomba: organisasi/tim, event, kategori, peserta, penjadwalan, penilaian juri, dan hasil.

**MVP saat ini: "Siapgrak Admin v1" (ADR-002).** Sistem internal panitia — dari setup event sampai hasil akhir — **tanpa satu pun halaman publik**. Alur MVP:

```
Create Event → Setup Category → Input Participant (admin entry)
  → Review → Competition Number → Schedule → Judges → Criteria
  → Scoring → Finalize Results
```

Yang sengaja **DEFERRED** (tetap ada di blueprint desain, tidak dikerjakan sekarang): halaman publik (event discovery, profil tim/organisasi, hasil publik), pendaftaran mandiri peserta (self-registration), dan integrasi Kreenconnect (ticketing/voting/checkout).

## Stack

Laravel 12, PHP 8.3, MySQL 8+, Blade, Alpine.js, Tailwind CSS. Repo tunggal, multi-organizer (shared database, tenant isolation via Laravel Policy — bukan DB terpisah per organizer).

## Status Saat Ini (2026-09-09)

Repo ini dikerjakan dua track paralel yang **sengaja dipisah** supaya tidak saling tunggu:

| Track | Siapa | Status | Kontrak kerja |
|---|---|---|---|
| **Backend** | Tim dev | Sprint 1-2 selesai (74 test hijau, PASS-review). Lanjut Sprint 3 dari domain Event/Category. | [`BACKEND-HANDOFF.md`](BACKEND-HANDOFF.md) |
| **Frontend** | Claude Code | F0 (Design Foundation) + F1 (Admin Shell) selesai — 2/13 step. Lanjut F3 Teams polish. | [`_docs/frontend/frontend-plan.md`](_docs/frontend/frontend-plan.md) |

Progress detail per sesi ada di [`_docs/status/log.md`](_docs/status/log.md) — baca ini duluan kalau mau tahu "sampai mana, blocker apa".

## Rencana Kerja (Tim Dev — Backend)

Roadmap penuh 13 sprint dalam 5 phase, satu sprint dikerjakan sendiri-sendiri (tidak digabung), setiap sprint wajib punya SRS + tenant isolation test sebelum dianggap selesai. Detail lengkap tiap sprint (skema tabel, business rule, urutan) ada di **[`BACKEND-HANDOFF.md`](BACKEND-HANDOFF.md)** — dokumen itu adalah kontrak kerja resmi, baca sebelum mulai coding apa pun.

| Phase | Sprint | Domain |
|---|---|---|
| A — Admin Foundation | 1-2 ✅ selesai, 3 (aktif) | Organization/Tenancy, Team/School Directory, Event + Category |
| B — Registration Ops | 4 | Participant management, mode admin-entry |
| C — Competition Ops | 5-6 | Competition Number, Schedule |
| D — Judging | 7-10 | Judges, Criteria, Score Sheet, Submit/Lock + Audit Trail |
| E — Results | 11-13 | Result Calculator, Awards, Operational Polish |

Aturan yang tidak bisa ditawar (lihat `CLAUDE.md` §Constitution untuk daftar lengkap): tenant isolation wajib di setiap resource baru, score sheet yang `submitted`/`locked` tidak boleh diedit tanpa alur reopen + audit trail, judge tidak boleh melihat skor juri lain selama judging berjalan, dan tidak ada tabel transaksi Kreenconnect (`tickets`, `orders`, `payments`, `votes`, dst) yang pernah disimpan di database Siapgrak.

## Dokumentasi

Baseline dokumentasi lengkap (standar v5) ada di [`_docs/`](_docs/README.md). Titik masuk yang paling relevan:

- [`CLAUDE.md`](CLAUDE.md) — instruksi kerja, Constitution, aturan kritis project (wajib dibaca sebelum coding fitur apa pun)
- [`BACKEND-HANDOFF.md`](BACKEND-HANDOFF.md) — kontrak kerja tim dev backend, roadmap Sprint 3-13 detail
- [`_docs/frontend/frontend-plan.md`](_docs/frontend/frontend-plan.md) — build order frontend F0-F12
- [`_docs/brd/brd-core.md`](_docs/brd/brd-core.md) — kenapa Siapgrak dibangun, siapa penggunanya
- [`_docs/decisions/`](_docs/decisions/README.md) — ADR-001 (batas Kreenconnect) dan ADR-002 (admin-first MVP)
- [`_docs/tasks/tasks.md`](_docs/tasks/tasks.md) — roadmap sprint lengkap + Phase Gate Rule
- [`_docs/status/log.md`](_docs/status/log.md) — log progress per sesi kerja
- [`_docs/research/`](_docs/research/) — riset kompetitor (Simpaskor.id) + diskusi desain arsitektur (skema DB, kontrak API, referensi teknis detail)

## Setup Lokal

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build
php artisan serve
```

Konfigurasi database (MySQL disarankan untuk paritas dengan dev environment yang sudah berjalan) ada di `.env` — lihat `_docs/operations/` untuk detail environment. Jalankan test dengan `php artisan test`.

## Prinsip Kerja

Project ini mengikuti [Karpathy Coding Guidelines](https://github.com/multica-ai/andrej-karpathy-skills) — lean, tidak ada abstraksi/fitur "jaga-jaga", surgical changes, dan setiap instruksi coding harus punya kriteria sukses yang bisa diverifikasi. Detail lengkap ada di `CLAUDE.md`.
