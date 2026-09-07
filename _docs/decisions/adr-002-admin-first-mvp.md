# ADR-002 — Admin-First MVP: Defer Public Site, Self-Registration, dan Kreenconnect Integration

**Status:** Accepted
**Tanggal:** 2026-09-07

## Konteks

Roadmap awal (`tasks/tasks.md` versi pertama, disusun dari `research/chatgpt-dev-checklist-migration-routes.md`) menyusun 14 sprint yang mencampur pembangunan admin/organizer workspace dengan public site (event discovery, team profile, hasil publik) dan participant self-registration sejak Phase 1-2, dengan Kreenconnect integration menyusul di Phase 6.

Setelah dipikir ulang, urutan itu berisiko: (1) public site dan self-registration (login peserta, forgot password, onboarding team representative, UX pendaftaran publik) menambah permukaan kerja besar sebelum inti bisnis (operasional kompetisi + judging) terbukti solid, (2) Kreenconnect integration dicatat sebagai blocking dependency padahal kontraknya belum dikonfirmasi — status blocking ini sebenarnya tidak menghalangi pembangunan modul inti sama sekali kalau modul itu dikerjakan belakangan.

## Keputusan

**MVP baru adalah "Siapgrak Admin v1": sistem internal panitia LKBB dari setup event sampai hasil akhir — tanpa satu pun halaman publik.** Alur yang harus bisa dijalankan penuh oleh organizer di akhir MVP ini:

```text
Create Event → Setup Category → Input Participant (admin entry) → Review Participant
→ Assign Competition Number → Build Schedule → Setup Judges → Setup Criteria
→ Run Scoring → Finalize Results
```

**Registration punya dua mode, dan hanya mode pertama yang dibangun sekarang:**
```text
ADMIN ENTRY        Panitia input/import peserta (dibangun sekarang, Sprint 4)
SELF REGISTRATION  Peserta daftar sendiri (DEFERRED — frontend baru ditambahkan
                    di atas registration engine yang sama, nanti)
```

**Berikut yang dipindah ke status `later` (tetap ada di blueprint, tidak dihapus dari desain, hanya tidak dikerjakan sekarang):**
```text
Public homepage, public event discovery, public team directory,
public organization profile, public results page
Ticketing integration (Kreenconnect)
Voting integration (Kreenconnect)
Team ranking ecosystem
Event Command Center advanced
```

**Kreenconnect API contract dicabut dari blocking dependency aktif** — tidak lagi menghalangi sprint manapun di roadmap aktif yang baru.

**Sidebar admin disederhanakan** menjadi `Dashboard / Events / Teams / Organization / Settings`. Event Workspace: `Overview` + **COMPETITION** (Participants/Categories/Schedule/Judging/Results) + **OPERATIONS** (Documents/Staff) + `Settings`. **Menu Ticketing/Voting di-hide total (bukan "Coming Soon")** — supaya produk Admin v1 terasa fokus, tidak terlihat setengah jadi.

## Alternatif yang Dipertimbangkan

| Opsi | Kelebihan | Kekurangan | Keputusan |
|---|---|---|---|
| A. Roadmap original — public site & self-registration mulai Phase 1-2, Kreenconnect Phase 6 | Produk "lengkap" terlihat lebih cepat dari luar | Permukaan kerja besar (auth peserta, public UX) dikerjakan sebelum inti bisnis terbukti; blocking dependency Kreenconnect membebani sprint yang sebenarnya tidak butuh itu | ❌ Ditolak |
| B. Admin-first MVP — publik & Kreenconnect deferred, registration admin-entry dulu | Fokus ke domain inti (kompetisi & judging) yang jadi diferensiasi sesungguhnya (lihat ADR-001); MVP lebih cepat dicapai dan lebih mudah divalidasi ke panitia asli; tidak ada permukaan kerja auth peserta yang dibangun sebelum dibutuhkan | Belum ada demo publik untuk marketing sampai fase belakangan; perlu pastikan registration engine tetap generic cukup untuk nanti diberi frontend self-service tanpa migrasi skema besar | ✅ Dipilih |

## Konsekuensi

**Positif:**
- Roadmap aktif jadi lebih pendek dan fokus — 13 sprint (dari sebelumnya 14 sprint yang mencampur banyak domain) menuju satu tujuan jelas: organizer bisa jalankan 1 event penuh dari Siapgrak
- Kreenconnect API contract yang belum terkonfirmasi tidak lagi memblokir progres — dependency itu baru relevan saat fase `later` dimulai
- Menghindari pembangunan auth peserta (login, forgot password, onboarding team representative) sebelum benar-benar dibutuhkan — sesuai Prinsip Karpathy #2 Simplicity First

**Negatif/Risiko:**
- `registrations`/`registration_members` di skema DB tetap sama (tidak berubah dari blueprint), tapi model data harus dipastikan tidak mengasumsikan `submitted_by` selalu user peserta — admin entry berarti `submitted_by` bisa jadi staff organizer. **Perlu diverifikasi saat Sprint 4 ditulis SRS-nya.**
- Public site & Kreenconnect integration yang "later" butuh di-revisit — risiko ini di-manage dengan tetap menyimpan detail lengkapnya di `research/chatgpt-blueprint-v1.md` (tidak dihapus), bukan dilupakan begitu saja
- BRD §User Journey (Team Representative) yang mengasumsikan self-service registration sekarang tidak berlaku untuk MVP — perlu ditandai sebagai journey masa depan, bukan journey MVP aktif

**Aturan turunan:**
- `tasks/tasks.md` diperbarui total: ACTIVE (13 sprint, Phase A-E) vs DEFERRED (public site, self-registration, Kreenconnect ticketing/voting, ecosystem, command center)
- `CLAUDE.md` §Aturan Kritis diperbarui: sidebar admin final, menu Ticketing/Voting **hidden total**, bukan "Coming Soon"
- SRS registration (belum ditulis, akan dibuat saat Sprint 4 dimulai) wajib mendesain untuk **admin-entry mode dulu**, dengan catatan eksplisit field yang harus tetap kompatibel kalau self-registration ditambahkan nanti (`submitted_by` bisa staff ATAU team representative — jangan asumsikan salah satu)
- Blocking Dependency "konfirmasi kontrak API Kreenconnect" dicabut dari daftar aktif di `tasks/tasks.md`, dipindah jadi catatan di bagian DEFERRED

---

*ADR tidak pernah dihapus/diedit setelah Accepted. Kalau keputusan berubah, buat ADR baru yang mem-supersede ADR ini.*
