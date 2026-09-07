# Coding Standards — Siapgrak

## 1. Code Style

- **Indentation:** 4 spasi (default Laravel/PSR-12)
- **Linter/formatter:** Laravel Pint (default config, `./vendor/bin/pint`)
- **Struktur folder:** lihat `architecture/system-overview.md` §Struktur Kode — Domain-oriented opsional, atau flat Laravel standar. Konsisten dalam satu project, jangan campur setengah-setengah.
- **Naming convention:**
  - Tabel & kolom database: `snake_case`, **bahasa Inggris** (lihat CLAUDE.md → Aturan Kritis)
  - Model: `PascalCase` singular (`Organization`, bukan `Organizations`)
  - Controller: `PascalCase` + suffix `Controller` (`OrganizationController`)
  - Service: `PascalCase` + suffix `Service` (`OrganizationService`)
  - Route name: `dot.case` (`dashboard.organizations.edit`)
  - Blade view: `kebab-case` (`organization-settings.blade.php`)
- **Pola framework:** Controller tipis — business logic di Service, bukan di Controller atau Model. Blade hanya presentation, tidak ada business logic di view.

## 2. Project Rules

Aturan spesifik project ini yang paling sering dilanggar AI karena tidak bisa ditebak dari konvensi umum:

- **Semua nama tabel & kolom database bahasa Inggris** — tidak ada `acara`, `pendaftaran`, `peserta_lomba`. Lihat contoh benar/salah di `research/chatgpt-dev-checklist-migration-routes.md` §Rule 3.
- **Skor selalu `DECIMAL(10,3)`, tidak pernah FLOAT.**
- **Primary key `BIGINT UNSIGNED`.** Public-facing identifier pakai `public_id` (string) atau `slug` — jangan expose numeric ID di URL publik.
- **Format response API internal (kalau ada endpoint JSON):** `{success: bool, message: string, data: object|array|null}` — konsisten di semua endpoint.
- **Timezone aplikasi:** `Asia/Jakarta`. Timestamp disimpan sesuai config timezone Laravel (lihat `operations/deployment.md`).
- **Pagination default:** 20 item per halaman untuk semua list (events, registrations, participants, activity logs) — jangan load data besar sekaligus.
- **Soft-status, bukan hard delete** untuk: `events` (setelah published), `registrations` (setelah submitted), `score_sheets` (setelah submitted), `competition_results` (setelah finalized). Hard delete hanya untuk draft tanpa data turunan. Lihat CLAUDE.md → Constitution.
- **Migration dibuat per-sprint, bukan sekaligus.** Lihat `research/chatgpt-dev-checklist-migration-routes.md` §Rule 1 dan urutan nomor migration 01-30 — jangan buat tabel dari sprint yang belum dikerjakan.
- **Dilarang membuat tabel domain Kreenconnect** (`tickets, orders, payments, votes, invoices, refunds, settlements`, dst) — lihat ADR-001.
- **Tenant isolation via Policy di SETIAP resource yang punya `organization_id`/`event_id`** — tidak ada exception "sementara skip dulu".
- **Judge area (`/judge/*`) terpisah total dari dashboard organizer** — tidak reuse layout/controller yang sama, supaya tidak ada risiko judge kebobolan akses menu organizer.
