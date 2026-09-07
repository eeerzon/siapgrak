# Security Baseline — Siapgrak

Aturan keamanan UNIVERSAL, berlaku dari hari pertama coding — tidak menunggu project "punya data sensitif".

## Authentication
- Login/logout/forgot-password/reset-password standar Laravel, rate-limited (lihat Phase 0 di `tasks/tasks.md`)
- Judge boleh dibuat tanpa `user_id` (account belum ada) — tapi judge area (`/judge/*`) tetap butuh authentication begitu account di-link

## Authorization / Object Ownership
- **Setiap resource yang punya `organization_id`/`event_id` WAJIB dicek lewat Laravel Policy** sebelum operasi read/write — tidak ada exception "sementara skip dulu untuk MVP"
- Organizer A tidak boleh bisa mengakses/mengubah event organizer B hanya dengan mengganti ID di URL — ini ditest eksplisit, bukan diasumsikan aman karena "kan ada auth"
- Judge hanya bisa lihat/edit score sheet miliknya sendiri (assignment eksplisit di `judge_assignments`) — tidak ada judge yang bisa melihat entry di luar assignment
- Score sheet berstatus `locked` tidak bisa diedit oleh siapapun kecuali lewat alur reopen resmi (reason wajib, tercatat di `score_sheet_revisions`)

## Public Identifier
- URL publik pakai slug/`public_id`, bukan numeric auto-increment ID — mencegah enumeration (`/events/1`, `/events/2`, dst bisa di-scan attacker untuk temukan event yang belum dipublikasikan)

## Input Validation
- Semua input divalidasi di server (Laravel Form Request), bukan cuma client-side
- File upload (poster event, logo organization, dokumen registrasi): validasi MIME type + size limit, simpan di storage dengan path yang tidak bisa ditebak/di-traverse

## Output Escaping
- Blade default escaping (`{{ }}`) dipakai untuk semua output user-generated content — `{!! !!}` hanya untuk konten yang sudah diverifikasi aman (bukan input user langsung)

## CSRF
- Laravel default CSRF protection aktif untuk semua form — **kecuali** webhook endpoint Kreenconnect (`POST /webhooks/kreenconnect`), yang sebagai gantinya wajib pakai signature verification (lihat di bawah)

## Session/Cookie Security
- Session cookie `httpOnly`, `secure` (production), `sameSite` sesuai default Laravel — tidak ada override yang melemahkan ini tanpa alasan eksplisit

## Rate Limiting
- Login, forgot-password, registration submission (public form) — rate-limited untuk cegah brute-force/spam

## File Upload
- Private dokumen registrasi (`registration_documents`) **tidak boleh** punya direct public URL permanen — akses lewat signed URL atau route yang dicek Policy, bukan link langsung ke storage public
- Public assets (poster, logo) boleh public tapi tetap validasi MIME/size saat upload

## Secrets
- Kreenconnect API credential (Client ID/Secret) **hanya** di environment variable — tidak pernah di kolom `event_integrations.settings` sebagai plaintext, tidak pernah di kode/commit
- `.env` tidak masuk git (default `.gitignore` Laravel, jangan diubah)

## SQL Injection
- Selalu pakai Eloquent/Query Builder parameter binding — tidak ada raw SQL string concatenation dari input user

## XSS
- Lihat Output Escaping di atas

## SSRF
- Kalau ada fitur yang fetch URL dari input user (belum ada di scope v1) — perlu whitelist domain sebelum diimplementasikan. Dicatat sebagai catatan preventif, bukan requirement aktif saat ini.

## Open Redirect
- Return URL untuk Kreenconnect checkout (`return_url`) harus divalidasi hanya mengarah ke domain Siapgrak sendiri — tidak menerima redirect URL arbitrary dari client

## Mass Assignment
- Model Eloquent pakai `$fillable` eksplisit, bukan `$guarded = []` — terutama untuk `organizations`, `events`, `score_sheets` yang punya field sensitif (`status`, `organization_id`) yang tidak boleh di-mass-assign dari request langsung

## Sensitive Logging
- `activity_logs.metadata` tidak boleh menyimpan credential/token
- Log aplikasi tidak boleh menampilkan password/token di plaintext

## Dependency Vulnerability
- `composer audit` dijalankan berkala (belum ada jadwal formal — dicatat sebagai practice yang harus mulai begitu Phase 0 selesai)

## Security Headers
- Header standar Laravel (belum ada kustomisasi khusus yang diketahui dibutuhkan saat ini) — re-evaluasi begitu domain production ditentukan

## Webhook Signature Verification
- `POST /webhooks/kreenconnect` (kalau dipakai, opsional untuk MVP — lihat `architecture/integration.md`) wajib validasi `X-Kreen-Signature` (HMAC SHA-256) + `X-Kreen-Timestamp` (tolerance window) + `X-Kreen-Event-ID` (replay protection, simpan di `processed_webhooks` dengan unique constraint)

## Boundary Kreenconnect (spesifik project ini, lihat ADR-001)
- Siapgrak **tidak pernah** menyimpan detail transaksi (nomor kartu, token pembayaran, riwayat transaksi granular) — hanya referensi (`external_event_id`) dan aggregate cache (`integration_snapshots`)
- Checkout return query param (`?payment=success`) **tidak pernah** dipercaya sebagai payment truth — lihat `architecture/integration.md` §Checkout Return
