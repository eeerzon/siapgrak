# _docs — Master Index

Siapgrak — platform operasional & ekosistem publik Paskibra/LKBB. Laravel 12 / PHP 8.3, repo tunggal. Belum ada kode; dokumen ini adalah baseline sebelum baris kode fitur pertama ditulis (standar v5, Lapis 1 — 18 file).

**Terakhir diupdate:** 2026-09-07

## Navigasi Cepat

| Butuh apa? | Buka ini |
|---|---|
| "Kenapa Siapgrak dibangun, siapa penggunanya?" | [brd/brd-core.md](brd/brd-core.md) |
| "Bagaimana fitur pertama (Organization/Tenancy) harus bekerja?" | [srs/fr-organization-tenancy.md](srs/fr-organization-tenancy.md) |
| "NFR yang berlaku global?" | [srs/nfr.md](srs/nfr.md) |
| "Kenapa keputusan arsitektur ini diambil?" | [decisions/README.md](decisions/) |
| "Arsitektur sistem tingkat tinggi?" | [architecture/system-overview.md](architecture/system-overview.md) |
| "Bagaimana Siapgrak bicara ke Kreenconnect?" | [architecture/integration.md](architecture/integration.md) |
| "Code style & aturan project?" | [referensi/coding-standards.md](referensi/coding-standards.md) |
| "Kapan boleh mulai coding?" | [quality/definition-of-ready.md](quality/definition-of-ready.md) |
| "Kapan fitur dianggap selesai?" | [quality/definition-of-done.md](quality/definition-of-done.md) |
| "Test strategy?" | [quality/test-strategy.md](quality/test-strategy.md) |
| "Checklist sebelum/sesudah deploy?" | [quality/release-checklist.md](quality/release-checklist.md) |
| "Aturan keamanan?" | [security/security-baseline.md](security/security-baseline.md) |
| "Cara deploy?" | [operations/deployment.md](operations/deployment.md) |
| "Cara rollback kalau ada yang rusak?" | [operations/rollback.md](operations/rollback.md) |
| "Diagnosis+recovery incident yang pernah terjadi?" | [operations/runbook.md](operations/runbook.md) |
| "Checklist AI sebelum lapor selesai?" | [ai/review-checklist.md](ai/review-checklist.md) |
| "Apa yang perlu dikerjakan, sprint mana sekarang?" | [tasks/tasks.md](tasks/tasks.md) |
| "Detail skema ~25 tabel, kontrak API Kreenconnect lengkap, route map penuh" | [research/](research/) — rujukan teknis, bukan SoT normatif (lihat catatan di bawah) |

## Tentang Folder `research/`

Berisi 4 dokumen hasil riset & diskusi dengan ChatGPT sebelum baseline `_docs` ini ditulis:

1. `research-and-design-proposal.md` — riset kompetitor Simpaskor.id + insight awal. **Bagian ticketing/voting native sudah superseded** oleh keputusan di ADR-001.
2. `chatgpt-architecture-proposal.md` — keputusan pivot: Siapgrak vs Kreenconnect boundary, insight domain (organizations/teams, event series, dst).
3. `chatgpt-blueprint-v1.md` — **skema database lengkap** (~25 tabel dengan kolom/index/FK), ERD, kontrak API Kreenconnect (endpoint+payload).
4. `chatgpt-dev-checklist-migration-routes.md` — urutan migration 01-30, route map lengkap, status matrix, sprint sequence.

**Cara pakai:** dokumen `_docs/` baseline ini (BRD, SRS, ADR, dst) adalah **source of truth normatif** — kalau ada konflik antara isi `research/` dan `_docs/brd|srs|decisions`, yang di baseline yang menang (biasanya karena sudah direview/disederhanakan). Folder `research/` tetap dipertahankan sebagai referensi detail teknis (skema kolom, payload API) yang terlalu panjang untuk diulang di tiap SRS — SRS akan me-link balik ke bagian relevan di `research/` alih-alih menyalin ulang.

## Struktur Lengkap

```
_docs/
├── README.md                          (file ini)
├── research/                           4 dokumen riset ChatGPT (lihat di atas)
├── brd/
│   └── brd-core.md
├── srs/
│   ├── fr-organization-tenancy.md      (fitur pertama — Phase 1 Sprint 1)
│   └── nfr.md
├── decisions/
│   ├── README.md
│   └── adr-001-siapgrak-kreenconnect-boundary.md
├── architecture/
│   ├── system-overview.md
│   └── integration.md                  (Lapis 2 — dipicu integrasi Kreenconnect)
├── referensi/
│   └── coding-standards.md
├── quality/
│   ├── definition-of-ready.md
│   ├── definition-of-done.md
│   ├── test-strategy.md
│   └── release-checklist.md
├── security/
│   └── security-baseline.md
├── operations/
│   ├── deployment.md
│   ├── rollback.md
│   └── runbook.md
├── ai/
│   └── review-checklist.md
└── tasks/
    └── tasks.md
```

**Belum dibuat (Lapis 2, tunda sampai dibutuhkan):** `database/erd.md` (untuk sekarang cukup rujuk `research/chatgpt-blueprint-v1.md` §"Simplified ERD"), `security/permission-matrix.md` (7 role sudah cukup terdokumentasi di BRD §User Persona + SRS §Permission, naikkan jadi file terpisah kalau role bertambah kompleks), `security/abuse-cases.md` (belum ada fitur uang/kuota native di Siapgrak — approval registration bukan target abuse tinggi; re-evaluasi begitu Phase 6 Kreenconnect integration mulai dikerjakan).
