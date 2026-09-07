# Test Strategy — Siapgrak

**Prinsip:** test sesuai risiko, bukan mengejar coverage percentage. Fitur yang menyentuh integritas kompetisi (score sheet, result calculation), tenant isolation, dan integrasi Kreenconnect ditest lebih ketat daripada halaman informational (mis. Organization About page).

## 6 Lapis Test

```text
1. Static      — Laravel Pint (lint) + PHP type declarations
2. Unit        — isolated business rules: ResultCalculator, CompetitionNumberService,
                  RegistrationService state transition
3. Integration — DB constraint (unique/FK), Kreenconnect API client (mocked)
4. Feature     — HTTP request → response → DB, pakai Laravel Feature Test
                  (contoh: POST /dashboard/events/{event}/registrations/{registration}/approve
                  → cek status berubah + competition_entry ter-create + activity_log tercatat)
5. E2E         — actual user journey (organizer create event → team register → approve →
                  judge score → publish result) — minimal 1 E2E test untuk full happy path
                  per Phase, dijalankan manual dulu sebelum ada waktu setup E2E automation
6. Production smoke test — minimal critical flow setelah deploy: login, buka event public
                  page, dashboard organizer accessible
```

## Area Berisiko Tinggi (test lebih ketat)

| Area | Kenapa berisiko tinggi | Test minimum |
|---|---|---|
| Tenant isolation (Policy) | Data leak lintas organizer = insiden bisnis | Setiap Policy method punya test "user bukan member → ditolak" |
| Judge isolation | Kebocoran ranking sementara = insiden integritas kompetisi | Test judge A tidak bisa buka score sheet judge B; judge tidak bisa lihat entry di luar assignment |
| Score sheet lifecycle | Nilai juri yang salah tidak bisa diperbaiki tanpa audit trail | Test locked sheet tidak bisa diedit; reopen wajib reason; revision_count bertambah |
| Competition number generation | Race condition → nomor duplikat | Test generate bersamaan (concurrent) tidak menghasilkan nomor sama |
| Result calculation | Salah hitung ranking = hasil lomba salah, sulit di-undo secara sosial (sudah dipublikasikan) | Test deterministic, tie handling eksplisit, weight calculation benar |
| Kreenconnect checkout session | Double-click bikin transaksi dobel | Test idempotency key mencegah duplicate checkout session |
| Registration duplicate | Tim daftar 2x ke kategori sama | Test unique constraint `(event_category_id, team_id)` ditegakkan di level aplikasi, bukan cuma DB error mentah |

## Area Berisiko Rendah (test minimal cukup)

Halaman informational (Organization About, Event Documents list, static public pages) — cukup smoke test render tanpa error.

## Kapan Test Wajib Ditulis

Setiap sprint (lihat `tasks/tasks.md`) sebelum ditandai selesai — lihat `quality/definition-of-done.md`. Tidak ada sprint yang selesai tanpa test untuk business rule di §Business Logic SRS terkait.
