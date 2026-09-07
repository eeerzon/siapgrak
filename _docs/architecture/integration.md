# Integration — Kreenconnect

**Lapis 2, dipicu oleh:** integrasi pihak ketiga business-critical (ticketing & voting/payment). Lihat [ADR-001](../decisions/adr-001-siapgrak-kreenconnect-boundary.md) untuk keputusan boundary di baliknya.

**Status:** Proposal — kontrak API di bawah **belum dikonfirmasi tim Kreenconnect**. Jangan mulai implementasi Phase 6 sebelum kontrak ini diverifikasi terhadap API Kreenconnect yang sesungguhnya.

## Dependency Registry

| Field | Isi |
|---|---|
| Provider | Kreenconnect (internal, ekosistem Kreen) |
| Purpose | Ticketing (jual tiket penonton) dan Voting (voting berbayar/gratis penonton) |
| Lokasi credential | Environment variable server Siapgrak (`.env`) — **jangan pernah** disimpan di tabel `event_integrations.settings` sebagai plaintext token; kalau perlu per-organizer credential, gunakan credential store terpisah, bukan kolom JSON biasa |
| Timeout | Disarankan 5-10 detik per request API (nilai final ditentukan saat implementasi, sesuaikan dengan SLA nyata Kreenconnect) |
| Retry policy | Retry dengan backoff untuk request GET (summary/status); **tidak** retry otomatis untuk POST checkout session tanpa idempotency key |
| Rate limit | Belum diketahui — konfirmasi ke tim Kreenconnect sebelum Phase 6 |
| Webhook? | Opsional untuk MVP. Kalau dipakai: `ticketing.summary.updated`, `voting.summary.updated`, `checkout.completed` — TIDAK perlu payment lifecycle detail (`payment.pending/failed`, `refund.processing`, `gateway.callback`) |
| Idempotency? | **Wajib** untuk `POST /checkout-sessions` dan `POST /voting/checkout-sessions` — pakai `Idempotency-Key` header supaya double-click tidak membuat beberapa checkout session |
| Failure behavior | API Kreenconnect timeout/error → Siapgrak tampilkan status "tidak tersedia sementara" di UI, JANGAN block halaman event secara keseluruhan (ticketing/voting cuma satu bagian dari event page) |
| Fallback | Tidak ada fallback transaksi — kalau Kreenconnect down, fitur beli tiket/vote sementara tidak bisa dipakai (bukan didesain ulang jadi native Siapgrak, itu melanggar ADR-001) |
| Sandbox tersedia? | Belum diketahui — konfirmasi ke tim Kreenconnect |
| Production endpoint | Belum ditentukan (`api.kreenconnect.com` dipakai sebagai placeholder di kontrak proposal) |
| Owner | Tim Kreenconnect (internal) |

## Kontrak API (Proposal)

Detail lengkap payload request/response ada di `research/chatgpt-blueprint-v1.md` §69-76 — ringkasan endpoint yang dibutuhkan:

### Ticketing
```text
GET  /api/v1/events/{id}                          → info event + checkout_url
GET  /api/v1/events/{id}/tickets                   → daftar ticket type + harga
POST /api/v1/checkout-sessions                     → buat checkout session (idempotent)
GET  /api/v1/events/{id}/ticketing-summary          → agregat: tickets_sold, checked_in, gross_revenue
```

### Voting
```text
GET  /api/v1/voting/events/{id}                    → info voting event + checkout_url
GET  /api/v1/voting/events/{id}/candidates          → daftar kandidat + vote_count
POST /api/v1/voting/checkout-sessions               → buat checkout session vote (idempotent)
GET  /api/v1/voting/events/{id}/summary              → agregat: total_votes, candidate_count, gross_revenue
```

**Mapping penting:** `candidate_id` (Kreenconnect) ↔ `competition_entry_id` (Siapgrak) disimpan di tabel `voting_candidate_mappings` — supaya Siapgrak bisa tampilkan tombol "Vote" langsung di profil peserta tanpa Siapgrak tahu detail transaksi vote-nya.

## Data yang DISIMPAN di Siapgrak (boundary eksplisit)

```text
event_integrations         provider, service, external_event_id, status, last_synced_at
integration_snapshots      metric aggregat (ticket_sold, ticket_revenue, vote_count, ...) — CACHE, bukan transaksi
integration_sync_logs      audit request ke Kreenconnect (retensi 30-90 hari)
voting_candidate_mappings  candidate_id ↔ competition_entry_id
```

## Data yang TIDAK PERNAH disimpan di Siapgrak

```text
tickets, ticket_types, orders, payments, payment_transactions
votes, vote_orders, vote_transactions, voting_packages
invoices, refunds, settlements
```

Lihat CLAUDE.md → Constitution dan `security/security-baseline.md` untuk penegasan aturan ini.

## Caching Strategy

| Data | Cache duration |
|---|---|
| Ticket summary | 1-5 menit |
| Voting leaderboard (saat event aktif) | 15-60 detik |
| Voting summary | 1 menit |
| Historic/finished event | Jauh lebih lama (jam-an, karena datanya sudah tidak berubah) |

Event-day dashboard (Command Center, Phase 8) boleh lebih agresif — **tapi jangan pernah** sinkronisasi seluruh transaction database dari Kreenconnect ke Siapgrak.

## Authentication Lintas Sistem

Machine-to-machine (API Client ID + Secret, atau signed request) — **bukan** memakai session/login user Siapgrak sebagai auth API. Minimum: HTTPS, credential di environment, rate limiting, request ID, idempotency key untuk checkout creation, audit log tiap panggilan.

## Checkout Return — Aturan Penting

Query param seperti `?payment=success` pada return URL **hanya UX state, bukan payment truth**. Siapgrak tidak boleh menganggap transaksi berhasil hanya dari query param — payment truth selalu berasal dari API/webhook Kreenconnect yang terverifikasi signature-nya.
