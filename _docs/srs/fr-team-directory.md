# Design Doc — Team / School Directory (Phase A, Sprint 2)

**Status:** Draft → Implemented
**Author:** Claude
**Tanggal:** 2026-09-07
**Berkaitan dengan BRD:** `_docs/brd/brd-core.md`
**Definition of Ready:** ✅ — Sprint 1 reviewed PASS, design gap di bawah diselesaikan via keputusan eksplisit Ersa sebelum coding dimulai.

---

> ⚠️ Cek CLAUDE.md → Constitution sebelum baca lebih jauh. Poin relevan Sprint 2: tenant isolation wajib, tidak boleh ada data tanpa owner yang jelas.

## TL;DR

Master data `teams` (dan `team_members` sebagai roster, bukan authorization) yang reusable lintas Event/Registration/Judging/Results ke depannya. Team punya `organization_id` **nullable di schema** tapi **wajib diisi di validasi Sprint 2** — keputusan ini menutup celah tenant-ownership untuk team tanpa organisasi tanpa menambah tabel/kolom otorisasi baru. Team management sepenuhnya mengikuti tenant context Organization (pola sama seperti Sprint 1), bukan authorization terpisah.

**Requirement ID:** `FR-TEAM-01`

---

## Context

Sprint 1 (Organization/Tenancy) sudah PASS-review dan jadi fondasi tenant isolation yang terbukti bekerja (41 test hijau, termasuk adversarial cross-tenant suite). Sprint 2 membangun di atasnya: `teams` sekarang perlu tenant boundary yang SAMA solidnya, tapi blueprint awal (`research/chatgpt-blueprint-v1.md` §42) mendesain `teams.organization_id` sebagai nullable — dimaksudkan untuk merepresentasikan tim yang belum/tidak terafiliasi institusi resmi (mis. tim komunitas independen "Garuda Muda").

## Keputusan Desain Kritis (didokumentasikan sebelum coding, bukan sesudah)

**Masalah:** tiga batasan Sprint 2 saling bertentangan kalau dieksekusi naif:
1. Blueprint minta `organization_id` nullable
2. Team dengan `organization_id = NULL` tetap wajib punya tenant ownership yang jelas (tidak boleh jadi data cross-tenant-accessible atau orphan)
3. Sprint 2 eksplisit TIDAK membuat `team_users`/`team_representatives`/kolom `owner_id` baru

Tidak ada kombinasi tabel existing (`users`, `organizations`, `organization_users`) yang bisa menjawab "siapa pemilik tenant dari team ini" untuk baris `organization_id = NULL` — begitu FK itu kosong, tidak ada jalur ke `organization_users` untuk tenant-check.

**Opsi yang diajukan ke Ersa (2026-09-07, sebelum migration ditulis):**
- **A (dipilih):** `organization_id` tetap nullable di DB schema (sesuai blueprint jangka panjang), tapi form create Sprint 2 mewajibkannya via validasi (`required`). Tidak ada team orphan yang bisa dibuat sekarang. Fitur "team benar-benar independen tanpa organisasi" resmi **DEFERRED** sampai model ownership (`team_users`/`team_representatives` atau serupa) didesain di sprint mendatang — biasanya bersamaan dengan Participant Self-Registration.
- B: `organization_id` NOT NULL di level database sekarang (mengunci lebih keras, butuh migration terpisah kalau nanti mau dilonggarkan).

**Keputusan: Opsi A.** Alasan: DB schema tetap match blueprint jangka panjang (tidak perlu migration mengubah nullable→nullable lagi nanti), sementara validasi aplikasi mencegah orphan data sekarang. Tidak ada kolom/tabel otorisasi baru ditambahkan.

---

## Goals & Non-Goals

**Goals:**
- Organizer bisa CRUD Team di dalam tenant context organisasinya
- Team reusable (tidak perlu re-entry data tiap event — prasyarat untuk Sprint 4 Registration)
- Tenant isolation Team semutu Sprint 1 Organization (termasuk kasus manipulasi `organization_id` di payload)

**Non-Goals (Sprint 2):**
- Team tanpa organisasi (DEFERRED, lihat Keputusan Desain di atas)
- `team_users`/authorization terpisah dari organization tenant context
- Public team page (`/teams`, `/teams/{slug}`) — DEFERRED per ADR-002
- Participant self-registration / team representative self-service

---

## Arsitektur & Data Flow

```text
Organizer (member of Organization X)
    → Dashboard → Teams → Create Team
    → TeamService::create() — organization_id divalidasi milik tenant organizer
    → team row dibuat dengan organization_id = organization aktif
```

Tenant check: `TeamPolicy` meniru pola `OrganizationPolicy` — cek `organization_id` team terhadap membership aktif user di organization tersebut. Team TIDAK punya pivot ownership sendiri; ownership sepenuhnya derivative dari `organizations.id → organization_users`.

## Perubahan Schema

### `teams`
```text
id                BIGINT UNSIGNED, PK
public_id         string, unique
organization_id   BIGINT UNSIGNED, FK → organizations.id, NULLABLE secara schema
                   (RESTRICT on delete — organisasi tidak bisa dihapus keras kalau
                   masih ada team; sejalan dengan prinsip "preserve historical team
                   identity", bukan CASCADE yang bisa membuang identitas team)
name              string
slug              string, unique
short_name        string, nullable
logo_path         string, nullable
province_code     string, nullable
city_code         string, nullable
description       text, nullable
status            enum: active | inactive
created_at, updated_at
```
Index: unique(`public_id`), unique(`slug`), index(`organization_id`).
**Sengaja TIDAK diindex** (belum ada query Sprint 2 yang butuh): `province_code`, `city_code`, `status`.

### `team_members`
```text
id             BIGINT UNSIGNED, PK
team_id        BIGINT UNSIGNED, FK → teams.id, CASCADE on delete
user_id        BIGINT UNSIGNED, FK → users.id, NULLABLE, SET NULL on delete
name           string
gender         string, nullable
birth_date     date, nullable
member_role    string, nullable
status         enum: active | inactive
created_at, updated_at
```
Index: index(`team_id`).
**team_members adalah roster, BUKAN authorization** — tidak pernah dipakai untuk menentukan siapa yang boleh mengelola team (lihat Constitution).

---

## Business Logic

```text
RULE-1: organization_id WAJIB diisi saat create team (validasi, bukan DB constraint) — lihat Keputusan Desain.
RULE-2: User hanya bisa assign team ke organization yang dia jadi member aktifnya — tidak bisa "titip" team ke organization lain lewat manipulasi payload.
RULE-3: slug di-generate dari name saat create (sama seperti Organization), bisa diedit manual, unique.
RULE-4: Organization tidak bisa di-hard-delete kalau masih punya team (FK RESTRICT) — konsisten dengan "preserve historical data" di Constitution.
RULE-5: team_members tidak pernah dipakai untuk cek otorisasi — hanya data roster.
```

## Permission & Access Control

| Role (di organization pemilik team) | Lihat | Buat | Edit | Kelola Members |
|---|---|---|---|---|
| owner | ✅ | ✅ | ✅ | ✅ |
| admin | ✅ | ✅ | ✅ | ✅ |
| member | ✅ | ✅ | ❌ | ❌ |
| Bukan member organization pemilik | ❌ | — | ❌ | ❌ |

Sama persis pola `OrganizationPolicy` — `TeamPolicy` menurunkan role dari `organizations.memberships` milik `team.organization_id`.

## Validation Rules

| Field | Aturan | Layer | Pesan Error |
|---|---|---|---|
| `organization_id` | wajib, harus organization tempat user jadi member aktif | server | "Anda harus memilih organisasi tempat Anda menjadi anggota." |
| `name` | wajib, max 255 | client+server | "Nama team wajib diisi." |
| `slug` | opsional saat create (auto-generate), unique | server | "Slug sudah dipakai." |

## Testing Plan

```
□ Organizer buat team dalam organisasinya sendiri → berhasil
□ Organizer coba assign team ke organization yang bukan miliknya (payload manipulation) → ditolak
□ User A (member Organization A) tidak bisa lihat/edit Team B (milik Organization B) — direct URL, edit form, PUT payload, route model binding
□ Slug/public_id unique ditegakkan
□ Organization tidak bisa dihapus keras kalau masih punya team (FK RESTRICT)
□ team_members: anggota cross-team tidak bisa dimanipulasi via URL organisasi lain
```

---

## File yang HARUS Diubah

| File | Perubahan | Kenapa |
|---|---|---|
| `database/migrations/*_create_teams_table.php` | baru | Skema di atas |
| `database/migrations/*_create_team_members_table.php` | baru | Skema di atas |
| `app/Models/Team.php`, `TeamMember.php` | baru | Model + relasi |
| `app/Enums/TeamMemberRole.php` (kalau perlu) atau string bebas | — | dipertimbangkan saat implementasi |
| `app/Policies/TeamPolicy.php` | baru | Pola sama `OrganizationPolicy` |
| `app/Services/TeamService.php` | baru | create/update dengan RULE-2 |
| `app/Http/Controllers/Dashboard/TeamController.php`, `TeamMemberController.php` | baru | CRUD |
| `routes/web.php` | update | `/dashboard/teams/*` |
| Views `resources/views/dashboard/teams/**` | baru | List (search+status+empty state), create, show, edit, members |

---

## Security Considerations

- [ ] `organization_id` di request tidak dipercaya mentah — divalidasi user benar-benar member aktif organization tsb sebelum create/update
- [ ] Tenant isolation Team diuji sama ketatnya dengan Organization (Sprint 1)
- [ ] `team_members` tidak pernah dipakai sebagai sumber otorisasi

## Open Questions

| Pertanyaan | Siapa yang harus menjawab | Deadline |
|---|---|---|
| Kapan model ownership team-independen (tanpa organisasi) didesain? | Ersa | Sebelum Participant Self-Registration (fase DEFERRED) dimulai |
