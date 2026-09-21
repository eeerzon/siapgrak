docs_tier: standard

# Siapgrak — Claude Code Instructions

Platform operasional untuk Paskibra/LKBB (Lomba Ketangkasan Baris Berbaris) — bagian dari ekosistem Kreen Connect. Laravel 12, PHP 8.3, MySQL 8+, Blade, Alpine.js, Tailwind CSS. Repo tunggal, multi-organizer (shared database, bukan DB terpisah per organizer).

**MVP saat ini: "Siapgrak Admin v1"** (ADR-002, 2026-09-07) — sistem internal panitia dari setup event sampai hasil akhir, **tanpa satu pun halaman publik**. Public site, participant self-registration, dan integrasi Kreenconnect (ticketing/voting) sengaja DEFERRED — tetap ada di desain (`_docs/research/`), tidak dikerjakan sekarang.

**Status saat ini (2026-09-16):** Backend Sprint 1-13 + 4b TUNTAS (415 test hijau) dan Frontend F0-F12 TUNTAS (13/13 step) — kedua track sama-sama selesai per rencana masing-masing. **Pembagian kerja backend/frontend** (lihat `_docs/frontend/frontend-plan.md` dan `BACKEND-HANDOFF.md`) tetap jadi rujukan kalau ada pekerjaan LANJUTAN baru dibuka di salah satu track, tapi tidak ada lagi sprint/step yang terbuka di kontrak awal manapun. Lihat `_docs/status/log.md` untuk detail per sesi dan kerja lanjutan opsional yang masih terbuka (verifikasi visual manual, backup-restore.md, keputusan hosting, dst).

---

## Shortcut: Source of Truth

```
Fitur spesifik (teknis)    → _docs/srs/fr-[nama].md
Fitur spesifik (bisnis)    → _docs/brd/brd-core.md
Arsitektur                 → _docs/architecture/system-overview.md
Integrasi Kreenconnect     → _docs/architecture/integration.md
Skema DB & kontrak API detail → _docs/research/chatgpt-blueprint-v1.md (rujukan, bukan SoT normatif)
Urutan migration & route map → _docs/research/chatgpt-dev-checklist-migration-routes.md
Code style & project rules  → _docs/referensi/coding-standards.md
Boleh mulai coding kalau    → _docs/quality/definition-of-ready.md
Definisi "selesai"          → _docs/quality/definition-of-done.md
Test strategy               → _docs/quality/test-strategy.md
Cara release/rollback       → _docs/quality/release-checklist.md + _docs/operations/rollback.md
Aturan keamanan             → _docs/security/security-baseline.md
Checklist sebelum lapor     → _docs/ai/review-checklist.md
Pekerjaan aktif              → _docs/tasks/tasks.md
Progress per sesi (handoff)  → _docs/status/log.md
Rencana & build order frontend → _docs/frontend/frontend-plan.md
Kontrak kerja tim dev (backend) → BACKEND-HANDOFF.md
```

## Prinsip Karpathy (Anti Kesalahan Umum LLM Coding)

Referensi lengkap: https://github.com/multica-ai/andrej-karpathy-skills

1. **Think Before Coding** — jangan asumsi diam-diam. Kalau ada >1 interpretasi, sajikan semua, jangan pilih sendiri. Kalau ada cara lebih simpel, bilang. Kalau bingung, berhenti dan tanya.
2. **Simplicity First** — kode minimum yang menyelesaikan masalah. Tidak ada fitur/abstraksi/error handling yang tidak diminta. Ini project yang sengaja dirancang **lean ~25 tabel**, bukan generic-platform — jangan tambah abstraksi "buat jaga-jaga".
3. **Surgical Changes** — sentuh hanya yang perlu. Jangan ikut "rapikan" kode/komentar di sekitarnya yang tidak diminta.
4. **Goal-Driven Execution** — ubah instruksi jadi kriteria sukses yang bisa diverifikasi, supaya bisa loop mandiri sampai benar.

(Sudah juga ada di `~/.claude/CLAUDE.md` global — section ini di sini supaya tetap terlihat kalau CLAUDE.md project ini dibaca terpisah.)

## Prinsip Tidak Bisa Diganggu Gugat (Constitution)

- **Siapgrak tidak pernah menyimpan transaksi Kreenconnect.** Dilarang membuat tabel `tickets, ticket_types, orders, payments, payment_transactions, votes, vote_orders, vote_transactions, invoices, refunds, settlements` — semua itu domain Kreenconnect. Siapgrak hanya menyimpan referensi (`event_integrations`, `integration_snapshots`) dan credential API tidak pernah disimpan di tabel `_docs`-terlihat, hanya di environment/config.
- **Judge tidak boleh melihat skor/ranking juri lain selama judging berjalan.** Kebocoran ranking sementara sebelum finalize adalah insiden bisnis (integritas kompetisi), bukan sekadar bug UX.
- **Score sheet yang sudah `submitted`/`locked` tidak boleh diedit diam-diam.** Perubahan wajib lewat alur reopen (reason wajib) dengan audit trail — tidak ada shortcut admin yang menimpa nilai juri langsung di database.
- **Registration/competition history tidak boleh hilang akibat cascade delete.** Event yang sudah punya registration di-`cancelled`/`archived`, bukan hard delete. Hard delete hanya untuk draft tanpa data turunan.
- **Roster peserta di-snapshot per registration**, tidak hanya mereferensikan `team_members` — perubahan anggota tim tahun depan tidak boleh mengubah histori tahun sebelumnya.
- **Organizer A tidak pernah bisa mengakses/mengubah data organizer B** hanya dengan mengganti ID di URL — tenant isolation via Laravel Policy wajib di setiap resource yang punya `organization_id`/`event_id`.
- **Selama fase Admin v1, tidak ada halaman publik yang dibangun** (event discovery, team/organization profile publik, hasil publik) — kalau ada permintaan yang mengarah ke situ, itu DEFERRED (lihat ADR-002), tanya dulu ke Ersa sebelum mulai, jangan diam-diam dikerjakan karena "kelihatan gampang sekalian".

Setiap Technical Design wajib dicek tidak melanggar bagian ini sebelum coding.

## Wajib Sebelum Coding Fitur

1. Cek fitur di `_docs/srs/` → baca SRS-nya
2. Cek BRD di `_docs/brd/brd-core.md` → pahami konteks bisnis, persona, journey
3. Jika fitur belum punya SRS → buat dulu (rujuk `_docs/research/chatgpt-blueprint-v1.md` untuk detail skema/kontrak API), update `_docs/README.md`
4. Cek `_docs/quality/definition-of-ready.md` — semua item tercentang sebelum mulai coding
5. Sebelum lapor selesai → jalankan `_docs/ai/review-checklist.md`

## Aturan Kritis

- **Jangan lompat phase.** Urutan aktif (ADR-002): Phase A Admin Foundation → Phase B Registration Ops (admin-entry) → Phase C Competition Ops → Phase D Judging → Phase E Results. Lihat `_docs/tasks/tasks.md` untuk breakdown 13 sprint. Instruksi coding pertama HARUS dibatasi eksplisit ke satu sprint.
- **Registration Phase B pakai mode admin-entry saja** — panitia yang input/import peserta (`Add Participant/Team`, `Import CSV/Excel`), BUKAN peserta daftar sendiri. Jangan bangun login/forgot-password/onboarding untuk team representative di fase ini — itu DEFERRED.
- **Sidebar dashboard tetap `Dashboard / Events / Teams / Organization / Settings`** — jangan ditambah menu lain tanpa diminta. Event Workspace: `Overview` + COMPETITION (Participants/Categories/Schedule/Judging/Results) + OPERATIONS (Documents/Staff) + `Settings`. **Menu Ticketing/Voting di-hide total, bukan "Coming Soon".**
- Semua nama tabel & kolom database **bahasa Inggris**.
- Field skor pakai `DECIMAL(10,3)`, **jangan pernah FLOAT**.
- Public URL pakai slug/`public_id`, bukan numeric ID auto-increment — berlaku untuk nanti kalau public site dikerjakan; untuk sekarang dashboard admin cukup pakai route resource biasa (`/dashboard/events/{event}`), tidak perlu prematurely desain slug publik.
- Jangan reuse database judging system project `juri` (produk pageant terpisah) — pola teknis boleh mirip, skema tetap terpisah sampai benar-benar terbukti identik (lihat ADR-001).

## Status Update (WAJIB)

Sebelum mengakhiri sesi kerja, tambah entry baru di paling atas `_docs/status/log.md` (buat filenya kalau belum ada — belum jadi baseline wajib untuk project ini karena belum ada kode berjalan, tapi mulai dipakai begitu Sprint 1 dimulai). Isi: progress %, yang diselesaikan sesi ini, blocker/keputusan yang dibutuhkan dari Ersa, next steps. Commit bareng perubahan kode.
