# Design Doc — Organization & Tenancy (Phase 1, Sprint 1)

**Status:** Draft
**Author:** Claude (disusun dari riset ChatGPT)
**Reviewer:** —
**Tanggal:** 2026-09-07
**Berkaitan dengan BRD:** `_docs/brd/brd-core.md`
**Definition of Ready:** ⏳ belum diverifikasi — project belum masuk Fase 2B, ini draft awal sebelum sprint dimulai

---

> ⚠️ Sebelum menulis dokumen ini, cek bagian "Prinsip Tidak Bisa Diganggu Gugat" di `CLAUDE.md` project ini — desain di bawah tidak boleh melanggarnya. Poin relevan: tenant isolation wajib via Policy.

## TL;DR

Fitur pertama yang dibangun: entitas `organizations` + relasi many-to-many ke `users` lewat `organization_users`, dengan Laravel Policy yang menegakkan tenant isolation. Ini fondasi Multi-Tenant Strategy (`architecture/system-overview.md`) yang jadi prasyarat SEMUA fitur lain (event, team, registration, dst semua punya `organization_id`).

**Requirement ID:** `FR-ORG-01`

---

## Context

Belum ada kode sama sekali. Ini implementasi PERTAMA di project Siapgrak — tidak ada legacy yang perlu dipertimbangkan. Sesuai `research/chatgpt-dev-checklist-migration-routes.md` §"First Development Instruction", scope sprint ini **dibatasi eksplisit**: HANYA Organization + tenancy + policy. Jangan implementasi Team, Event, Registration, Judging, Ticketing, Voting di sprint ini — meskipun tabel-tabel itu terdokumentasi lengkap di blueprint, mereka masuk sprint-sprint berikutnya (lihat `tasks/tasks.md`).

Prasyarat: `users` table (Laravel default, migration paling pertama) dan Phase 0 (Auth: login/logout/forgot-password) harus sudah beres sebelum sprint ini.

---

## Goals & Non-Goals

**Goals:**
- Organizer bisa membuat Organization
- User bisa jadi anggota >1 Organization dengan role berbeda (owner/admin/member)
- Tenant isolation tegak: user hanya bisa akses/edit Organization yang dia jadi anggotanya
- Organization Settings page (dashboard) untuk edit data organization

**Non-Goals (sengaja tidak di sprint ini):**
- Team, Event, Registration, Judging — semua ini butuh `organization_id` sebagai FK, tapi implementasinya adalah sprint terpisah
- Public Organization page (`/organizations/{slug}`) — masuk Sprint 3 bersamaan Event Public Page
- Organization logo upload ke storage — boleh placeholder dulu, file upload sebagai fitur penuh (validasi MIME, size limit) baru diimplementasikan penuh saat dibutuhkan modul lain yang juga upload file (event poster, dst) — hindari membangun infra upload untuk satu field yang belum dipakai

---

## Arsitektur & Data Flow

```text
User → Dashboard → Create Organization → OrganizationService::create()
     → organizations row + organization_users row (role=owner) dibuat dalam 1 transaction
     → Response: redirect ke Organization Settings
```

```text
User → Organization Settings → Edit → OrganizationPolicy::update() dicek
     → kalau user bukan member organization ini → 403
     → kalau user member tapi role=member (bukan admin/owner) → 403
     → kalau lolos → OrganizationService::update()
```

## Perubahan Schema

### `organizations`
```text
id                BIGINT UNSIGNED, PK
public_id         string, unique
name              string
slug              string, unique
type              enum: school | association | community | organizer | agency | other
description       text, nullable
logo_path         string, nullable
email             string, nullable
phone             string, nullable
website           string, nullable
province_code     string, nullable
city_code         string, nullable
status            enum: active | inactive
created_at, updated_at
```
Index: unique(public_id), unique(slug), index(type), index(status), index(province_code), index(city_code), index(type, status).

### `organization_users`
```text
id                BIGINT UNSIGNED, PK
organization_id   BIGINT UNSIGNED, FK → organizations.id
user_id           BIGINT UNSIGNED, FK → users.id
role              enum: owner | admin | member
status            enum: active | inactive
created_at, updated_at
```
Index: unique(organization_id, user_id), index(user_id), index(organization_id, role), index(organization_id, status).

Skema identik dengan yang sudah didokumentasikan di `research/chatgpt-blueprint-v1.md` §40-41 dan `research/chatgpt-dev-checklist-migration-routes.md` §Migration 02-03 — tidak diulang detail lebih jauh di sini, rujuk ke situ kalau ada pertanyaan kolom.

---

## Business Logic

```text
RULE-1: Organization pembuat pertama otomatis dapat role "owner" di organization_users — tidak ada state "organization tanpa owner".
RULE-2: Role owner tidak bisa dihapus dari organization_users kalau dia satu-satunya owner (harus ada minimal 1 owner selalu) — dicek di Service, bukan hanya di UI.
RULE-3: slug organization di-generate dari name saat create, tapi bisa diedit manual — validasi unique tetap berlaku saat diedit.
RULE-4: status=inactive pada organization TIDAK menghapus data — event/team yang dimiliki organization tetap ada, hanya organization tidak lagi bisa dipakai untuk membuat resource baru (soft-disable, bukan cascade apapun).
```

## Permission & Access Control

| Role | Bisa Lihat | Bisa Buat Organization | Bisa Edit Organization | Bisa Hapus Organization | Bisa Invite/Manage Member |
|---|---|---|---|---|---|
| Super Admin (internal) | ✅ (semua) | ✅ | ✅ (semua) | ✅ | ✅ |
| owner (organization_users.role) | ✅ (org miliknya) | ✅ (bikin org baru lain) | ✅ | ✅ (kalau bukan satu-satunya org yang dia kelola dengan data terkait — lihat RULE di DoD) | ✅ |
| admin | ✅ (org miliknya) | ✅ (bikin org baru lain) | ✅ | ❌ | ✅ (kecuali ubah role owner) |
| member | ✅ (org miliknya) | ✅ (bikin org baru lain) | ❌ | ❌ | ❌ |
| User tanpa membership | ❌ | — | ❌ | ❌ | ❌ |

Role ≤3 nilai per konteks (owner/admin/member) — tabel ini cukup, belum perlu naik ke `security/permission-matrix.md` terpisah untuk fitur ini.

## Validation Rules

| Field | Aturan | Layer | Pesan Error |
|---|---|---|---|
| `name` | wajib, max 255 char | client+server | "Nama organisasi wajib diisi" |
| `slug` | wajib, unique, format kebab-case | server | "Slug sudah dipakai organisasi lain" |
| `type` | wajib, salah satu dari enum | server | "Tipe organisasi tidak valid" |
| `email` | opsional, format email valid | client+server | "Format email tidak valid" |
| `website` | opsional, format URL valid | server | "Format URL tidak valid" |

## Testing Plan

```
□ Organizer create organization → row organizations + organization_users(role=owner) dibuat sekaligus (1 transaction, bukan 2 request terpisah yang bisa gagal di tengah)
□ Slug duplikat ditolak dengan pesan jelas
□ User yang bukan member organization TIDAK bisa GET/PUT organization tersebut (403, bukan 404 — supaya tidak leak informasi keberadaan organization, TAPI cek juga apakah 404 lebih aman untuk kasus ini sebelum finalize, lihat security-baseline.md soal information disclosure)
□ Role member TIDAK bisa edit organization (403)
□ Role admin BISA edit organization tapi TIDAK bisa hapus
□ Owner tunggal TIDAK bisa di-demote/dihapus dari organization_users kalau dia satu-satunya owner
□ organization.status=inactive tidak menghapus data terkait (test setelah Team/Event ada — skenario ini disiapkan sekarang tapi baru bisa full-test setelah Sprint 2-3)
```

---

## File yang HARUS Diubah

| File | Perubahan | Kenapa |
|---|---|---|
| `database/migrations/xxxx_create_organizations_table.php` | baru | Skema di atas |
| `database/migrations/xxxx_create_organization_users_table.php` | baru | Skema di atas |
| `app/Models/Organization.php` | baru | Model + relasi `hasMany(User::class, through: organization_users)` |
| `app/Models/OrganizationUser.php` | baru | Pivot model |
| `app/Policies/OrganizationPolicy.php` | baru | Rules di §Permission |
| `app/Services/OrganizationService.php` | baru | `create()`, `update()`, `addMember()`, `removeMember()`, `changeRole()` |
| `app/Http/Controllers/Dashboard/OrganizationController.php` | baru | CRUD dashboard |
| `resources/views/dashboard/organizations/*.blade.php` | baru | Organization list, create, settings page |
| `routes/web.php` | update | Tambah route dashboard organization (lihat route map di `research/chatgpt-dev-checklist-migration-routes.md` §Phase 1) |
| `database/factories/OrganizationFactory.php` | baru | Untuk seeder & test |

---

## Alternatif yang Dipertimbangkan

| Opsi | Kelebihan | Kekurangan | Kenapa Tidak Dipilih |
|---|---|---|---|
| Database terpisah per organizer (multi-tenant DB) | Isolasi data secara fisik, tidak mungkin bocor lewat bug policy | Kompleksitas operasional tinggi (migration per-DB, cross-tenant reporting sulit) | Ditolak — sudah diputuskan di `architecture/system-overview.md` §Multi-Tenant Strategy, shared DB dengan Policy sudah cukup untuk skala yang dibayangkan |
| Shared database, isolasi via Policy — dipilih | Sederhana dioperasikan, cukup untuk skala LKBB (bukan SaaS besar) | Bug di Policy = risiko data leak lintas tenant | Dipilih, dengan mitigasi: test tenant isolation wajib ada di setiap Policy (lihat Testing Plan) |

---

## Error Handling

| Skenario | Error | Perilaku |
|---|---|---|
| Slug sudah dipakai | 422 validation error | Tampilkan pesan, sarankan slug alternatif (opsional, boleh manual retry dulu) |
| User akses organization bukan miliknya | 403 (lihat catatan di Testing Plan soal 403 vs 404) | Redirect ke dashboard dengan flash message |
| Hapus organization yang masih punya event aktif | Ditolak di level Service (bukan DB constraint keras) | Pesan: "Tidak bisa hapus organisasi yang masih punya event aktif" |

---

## Security Considerations

- [ ] Input `name`/`description` di-sanitize sebelum ditampilkan (Blade escaping default sudah cukup, asal tidak pakai `{!! !!}` sembarangan)
- [ ] `logo_path` tidak boleh accept path traversal — validasi lewat Laravel Storage API, bukan string manipulation manual
- [ ] Endpoint dashboard organization wajib auth + policy — tidak ada endpoint organization yang bisa diakses tanpa login
- [ ] `organization_id` di request TIDAK dipercaya mentah dari client untuk operasi write — selalu divalidasi lewat Policy sebelum Service dipanggil

## Performance Considerations

- [ ] Query organization list untuk dashboard di-paginate (default 20/halaman, sesuai `coding-standards.md`)
- [ ] Relasi `organization_users` di-eager-load saat menampilkan member list (hindari N+1)

## Rollout Plan

- [ ] Tidak perlu feature flag — ini fondasi awal, belum ada user produksi
- [ ] Migration backward-compatible: N/A (migration pertama)
- [ ] Rollback: `php artisan migrate:rollback` untuk 2 migration ini aman selama belum ada data (lihat `operations/rollback.md`)

## Open Questions

| Pertanyaan | Siapa yang harus menjawab | Deadline |
|---|---|---|
| Apakah Siapgrak pakai SSO Kreen Connect existing untuk auth, atau Laravel auth mandiri? | Ersa | Sebelum Phase 0 (Auth) dimulai — mempengaruhi struktur `users` table |
| Response 403 vs 404 untuk akses organization bukan milik user — mana yang dipilih sebagai konvensi project? | Tim dev saat implementasi | Sebelum Sprint 1 selesai, dicatat di `coding-standards.md` begitu diputuskan |

---

## Review Checklist

- [ ] TL;DR jelas tanpa baca seluruh dokumen
- [ ] Semua file yang diubah terdaftar
- [ ] Business Logic, Permission, Validation, Testing Plan terisi
- [ ] Error handling ter-cover
- [ ] Security sudah dipertimbangkan
- [ ] Reviewer kedua sudah baca dan setuju — **belum**, dokumen ini draft pertama
