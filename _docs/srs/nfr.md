# Non-Functional Requirements — Siapgrak

Berlaku global untuk seluruh project — bukan per-fitur.

## Reliability

Sistem harus bekerja benar dalam kondisi normal untuk alur-alur kritis: registration submit, score sheet submit/lock, result calculation. Kalkulasi hasil (`ResultCalculator`) harus **deterministic** — input skor yang sama selalu menghasilkan ranking yang sama, termasuk penanganan tie yang eksplisit (bukan arbitrary order berdasarkan urutan insert).

## Resilience

Sistem harus tetap berjalan untuk domain intinya (event, registration, judging) meskipun **Kreenconnect API sedang down** — ticketing/voting widget di halaman event boleh tampil "tidak tersedia sementara", tapi seluruh alur registrasi/judging/hasil TIDAK BOLEH terganggu oleh downtime Kreenconnect. Lihat `architecture/integration.md` §Failure behavior.

## Recoverability

Data registration, score sheet, dan hasil lomba adalah data yang harus bisa dipulihkan — backup database rutin wajib ada sebelum event pertama live di production (lihat `operations/backup-restore.md` — belum dibuat, jadi prasyarat sebelum Phase 2 selesai karena di situ data user mulai masuk sungguhan). Delete strategy default adalah soft-status (`cancelled`/`archived`), bukan hard delete — lihat CLAUDE.md → Constitution.

## Observability

Minimum untuk MVP: `activity_logs` mencatat semua aksi high-value (`registration_approved/rejected`, `score_sheet_submitted/reopened`, `result_finalized/published`, `award_changed`) — lihat `research/chatgpt-blueprint-v1.md` §Migration 25. `integration_sync_logs` mencatat setiap panggilan ke Kreenconnect API beserta status/error. Belum ada dashboard observability terpisah (Lapis 2 `operations/observability.md`) — dipertimbangkan begitu Phase 6 (integrasi) mulai dikerjakan dan ada kegagalan API eksternal yang perlu dipantau proaktif.

## Performance

- Public event page dan hasil lomba (`/events/{slug}/results`) — target render < 1 detik untuk kondisi normal (data sudah final, tidak query berat real-time)
- Dashboard organizer (list events/registrations) — dipaginate, tidak boleh load semua data sekaligus (lihat `referensi/coding-standards.md` §pagination default)
- Integrasi Kreenconnect API — timeout 5-10 detik per call (lihat `architecture/integration.md`), dengan cache 1-5 menit untuk summary data supaya public page tidak menunggu API eksternal tiap load

## Security

Lihat `security/security-baseline.md` untuk daftar lengkap. Poin NFR-level yang paling kritis: tenant isolation (organizer A tidak bisa akses data organizer B), judge isolation (juri tidak bisa lihat skor juri lain sebelum finalize), dan boundary Kreenconnect (tidak ada data transaksi tersimpan di Siapgrak).

## Availability

Belum ada target uptime formal (belum production). Begitu event pertama live di production, target awal: tidak ada downtime yang direncanakan selama jam kompetisi berlangsung (hari-H event) — maintenance window dijadwalkan di luar periode event aktif manapun.

## Data Integrity

- **Uniqueness:** `(event_category_id, team_id)` unique di `registrations` — satu tim tidak bisa daftar dobel ke kategori sama. `(event_category_id, competition_number)` unique di `competition_entries`. `(judging_round_id, competition_entry_id, judge_id)` unique di `score_sheets`. `(score_sheet_id, judging_criteria_id)` unique di `scores`.
- **Foreign key:** semua relasi didefinisikan eksplisit dengan `ON DELETE` behavior yang disengaja (lihat catatan per tabel di `research/chatgpt-blueprint-v1.md`) — default `RESTRICT` kecuali ada alasan spesifik untuk `SET NULL`/`CASCADE`.
- **Idempotency:** wajib untuk `POST /checkout-sessions` ke Kreenconnect (`Idempotency-Key` header) — double-click tidak boleh membuat 2 checkout session.
- **Race condition:** competition number generation (sequential) harus atomic — dua entry tidak boleh dapat nomor sama saat digenerate bersamaan (pakai DB transaction + lock, bukan cek-lalu-insert terpisah).
- **Soft delete/status behavior:** event/registration/score yang sudah punya data turunan tidak pernah hard delete — lihat Constitution di CLAUDE.md.
- **Money precision:** N/A untuk Siapgrak langsung (uang ditangani Kreenconnect) — tapi `integration_snapshots.value_decimal` untuk revenue cache tetap harus DECIMAL, bukan FLOAT, demi konsistensi kalau ditampilkan di dashboard.
- **Score precision:** semua kolom skor `DECIMAL(10,3)`, tidak pernah FLOAT (lihat CLAUDE.md → Aturan Kritis).

## Timezone

Semua timestamp disimpan sesuai default Laravel (`Asia/Jakarta` di level aplikasi — lihat `operations/deployment.md`), ditampilkan konsisten dengan timezone tersebut. Belum ada requirement multi-timezone (event LKBB semuanya di Indonesia).

## Constraint Teknis

- Repo tunggal, tidak microservices (lihat `architecture/system-overview.md`)
- Tidak ada database terpisah per organizer — shared DB dengan Policy-based isolation
- Tidak menyimpan data transaksi Kreenconnect (lihat ADR-001) — constraint ini tidak bisa dilanggar oleh keputusan performa/kemudahan apapun
