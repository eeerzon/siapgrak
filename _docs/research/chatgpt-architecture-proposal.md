# Siapgrak — Rancangan Arsitektur (hasil diskusi ChatGPT)

Status: **Part 1** — masih ada lanjutan dari ChatGPT yang akan ditambahkan.
Konteks: respons ChatGPT terhadap [`research-and-design-proposal.md`](research-and-design-proposal.md) (dokumen riset awal yang dikirim untuk didiskusikan).

---

## Keputusan Inti: Jangan Bikin Ulang Ticketing & Voting

**Siapgrak = platform operasional & ekosistem LKBB/Paskibra**
**Kreenconnect = transaction engine untuk ticketing & voting**

User tetap merasa semuanya satu ekosistem Siapgrak, tapi secara backend fungsi transaksi yang sudah mature tetap memakai Kreenconnect lewat API. Alasan: menghindari project membesar, maintenance dobel, dan data terpecah antara dua sistem dengan fungsi sama.

### Landscape kompetitor (update)

Bukan cuma Simpaskor:
- **Evenan** — sudah menggabungkan voting, ticketing, QR check-in, dan penilaian LKBB.
- **Votingin** — sudah punya discovery event Paskibra + voting + hasil event.
- **KORPS (Sireva)** — voting LKBB Rp1.000/vote beserta invoice QRIS.

Kesimpulan: kalau Siapgrak cuma jadi "Simpaskor versi lain", diferensiasinya tipis. Perlu positioning lebih besar: **operating system untuk dunia LKBB**, bukan sekadar sistem skor.

---

## Arsitektur Baru

```text
                    SIAPGRAK.ID
         Paskibra & LKBB Ecosystem Platform
                        │
        ┌───────────────┼────────────────┐
        │               │                │
        ▼               ▼                ▼
   DISCOVERY        EVENT OPS        COMPETITION
   & PROMOTION      & INTERNAL       MANAGEMENT
 Event listing       Event setup       Participants
 Organizer page      Registration      Categories
 School/team page    Documents         Draw/order
 News/content        Rundown           Judges
 Results archive     Technical mtg      Scoring
 Achievements        Staff/task         Results
 Calendar            Check-in
                        │
                 Integration Layer
                        │
          ┌─────────────┴─────────────┐
          ▼                           ▼
     KREENCONNECT                 JURI ENGINE
     Ticketing                    (optional/reuse)
     Voting
     Payment
     Orders
```

**Perubahan penting dari dokumen awal:** ticketing dan voting tidak lagi masuk scope database Siapgrak — bahkan tidak masuk roadmap sebagai "nanti dibangun". Prinsip: `Siapgrak consumes Kreenconnect services.`

---

## 1. Tiga Fungsi Besar Siapgrak

### A. Public Ecosystem / Promotion

Yang membuat Siapgrak hidup bahkan saat tidak ada lomba berlangsung:

```text
siapgrak.id/
siapgrak.id/events
siapgrak.id/events/lkbb-garuda-2026
siapgrak.id/teams
siapgrak.id/teams/paskibra-sman-1-bandung
siapgrak.id/organizers
siapgrak.id/organizers/forbasi-bandung
siapgrak.id/results
siapgrak.id/rankings
```

Orang bisa menemukan: event LKBB, jadwal, lokasi, kategori, organizer, peserta, hasil pertandingan, profil sekolah/tim, prestasi, archive lomba sebelumnya.

**Ini moat jangka panjang Siapgrak** — ticketing dan voting gampang dicopy, tapi database historis ("SMAN X pernah ikut 24 event, juara 8 kali, Danton terbaik 3 kali") makin berharga seiring waktu.

### B. Event Operations (dashboard panitia)

Bukan sekadar `Buat Event → Tambah Peserta → Tambah Juri → Input Nilai`, tapi **Event Workspace**:

```text
LKBB GARUDA 2026
Overview
Competition
├── Categories
├── Participants
├── Competition Numbers
├── Schedule
├── Judges
├── Scoring
└── Results
Operations
├── Registration
├── Technical Meeting
├── Documents
├── Rundown
├── Check-in
└── Staff
Engagement
├── Ticketing      → Kreenconnect
├── Voting         → Kreenconnect
└── Public Page
Reports
```

Panitia bekerja dari satu workspace event, bukan berpindah-pindah sistem.

### C. Competition Management

Domain asli LKBB, dibangun native di Siapgrak.

**Competition Category harus relational**, bukan field tunggal `event.category`:

```text
LKBB GARUDA 2026 punya:
SMP / SMA / Purna
```
atau
```text
LKBB / LFPB / Variasi Formasi / Danton
```
atau bahkan
```text
SMP Putra / SMP Putri / SMA
```

---

## 2. Event Series (revisi dari draft "Rangkaian" awal)

Draft awal (`Event → Rangkaian → Sub-event`) dinilai berpotensi membingungkan. Versi ChatGPT:

```text
Event Series
    │
    ├── Event
    ├── Event
    └── Event
```

Contoh:
```text
KEJURCAB FORBASI BANDUNG 2026
              │
              ├── RUKIBRA
              ├── LKBB
              └── VAFORMUS
```

**Event Series opsional** — event biasa tetap berdiri sendiri tanpa parent.

```text
event_series
events
events.event_series_id  nullable
```

---

## 3. Team / School Identity — Domain yang Diremehkan di Draft Awal

Masalah: kalau tiap event mengetik ulang nama tim/sekolah secara bebas teks (`Paskibra SMAN 1 Bandung` vs `Paskibra SMA Negeri 1 Bandung` vs `SMAN 1 BDG`), data historis rusak — tidak bisa diagregasi.

**Solusi: entity permanen**

```text
organizations
teams
```

Contoh:
```text
Organization: SMAN 1 Bandung
Team: Paskibra SMAN 1 Bandung
```
atau
```text
Organization: FORBASI Kota Bandung
```

Dengan `type`: `school | association | community | organizer | agency | other`.

Event participant mengacu ke `team_id`, bukan free-text.

Hasil jangka panjang:
```text
Paskibra SMAN 1 Bandung
32 Event · 11 Podium · 4 Juara 1 · 3 Best Danton
Prestasi:
2026  Juara 1 LKBB A
2026  Juara 3 LKBB B
2025  Juara 1 LKBB C
```

Catatan tambahan: prestasi Paskibra bisa dipakai sebagai bukti prestasi dalam konteks penerimaan murid tertentu (dengan syarat legalisasi & level kompetisi) — jadi archive prestasi yang tertata punya nilai di luar hari-H lomba.

---

## 4. Competition Registration ≠ Ticketing

Mendaftarkan **SMAN 1 Bandung sebagai peserta LKBB kategori SMA** beda dengan membeli **2 tiket penonton**.

```text
Siapgrak → competition registration   (native)
Kreenconnect → spectator tickets, payments, ticket orders, QR tickets   (external)
```

Alur registrasi peserta:
```text
Panitia membuka pendaftaran LKBB
        ↓
Tim mengisi registration
        ↓
Upload: nama sekolah, nama tim, daftar anggota, pembina, pasfoto/logo, dokumen
        ↓
Panitia review
        ↓
Approved
        ↓
Nomor peserta dibuat
```

---

## 5. Struktur Scoring — Lebih Generik dari "Input Nilai"

Bukan:
```text
events → criteria → scores
```

Tapi:
```text
competition_categories
judging_rounds
judging_criteria
judge_assignments
score_sheets
scores
```

Contoh:
```text
LKBB SMA
   └── Final
       ├── PBB 40%
       ├── Variasi Formasi 30%
       ├── Danton 20%
       └── Kostum 10%
```

Juri A → PBB, Juri B → Variasi Formasi, Juri C → Danton (pembagian tanggung jawab per juri, sesuai temuan riset Simpaskor).

---

## 6. Jangan Langsung Reuse Database `juri`

**Reuse concept, belum tentu reuse database.** Pageant judging dan LKBB judging adalah domain berbeda — memaksakan jadi satu skema generik (`competition_subject`, `evaluation_entity`, `evaluation_dimension`, `evaluation_target`) berisiko bikin developer sendiri bingung.

Untuk sekarang:
```text
juri     → pageant-oriented product
siapgrak → LKBB-oriented product
```

Tapi pola teknis bisa sama: `Round, Criteria, Judge Assignment, Score Sheet, Submission Lock, Audit Trail, Result Calculation`.

**Prinsip: jangan generic-kan sebelum benar-benar perlu.** Kalau nanti terbukti benar-benar identik, baru diekstrak jadi shared judging engine/API.

---

## 7. Integrasi Kreenconnect

Di UI Siapgrak, panitia bisa lihat:
```text
Voting
Status: ● Active
Total Votes: 148,291
Revenue: Rp148.291.000
[Manage Voting]
```

Tapi datanya berasal dari `GET api.kreenconnect.com/...`. Siapgrak cukup simpan referensi:

```text
event_integrations
  id
  event_id
  provider          (kreenconnect)
  service           (voting | ticketing)
  external_event_id
  status
  settings
  last_synced_at
  created_at
  updated_at
```

**Tidak perlu replicate vote/order/payment ke DB Siapgrak.** Kalau butuh dashboard cepat, boleh cache aggregate (`ticket_sold`, `ticket_revenue`, `vote_count`, `vote_revenue`) di Redis/tabel snapshot — bukan mirror semua transaksi.

---

## 8. Skema Database yang Disarankan (lean, ~20 tabel)

```text
AUTH & TENANCY
  users
  organizations
  organization_users

PASKIBRA DIRECTORY
  teams
  team_members

EVENT
  event_series
  events
  event_categories

REGISTRATION
  registrations
  registration_members
  registration_documents

COMPETITION
  competition_entries
  competition_numbers
  judging_rounds
  judging_criteria
  judges
  judge_assignments
  score_sheets
  scores

RESULT
  competition_results
  awards

OPERATIONS
  event_schedules
  event_documents
  event_staff

INTEGRATION
  event_integrations
  integration_sync_logs

SYSTEM
  activity_logs
```

### Tabel yang SENGAJA TIDAK dibuat (karena Kreenconnect sudah punya)

```text
❌ tickets
❌ ticket_types
❌ orders
❌ payments
❌ payment_transactions
❌ votes
❌ vote_transactions
❌ voting_packages
❌ invoices
❌ payment_callbacks
```

Siapgrak cukup tahu: Kreenconnect voting event ID, Kreenconnect ticketing event ID, status, summary, link.

---

## 9. Public Page — Porsinya Harus Besar

Contoh halaman event:
```text
SIAPGRAK
LKBB GARUDA 2026
12 September 2026 · GOR Pajajaran Bandung
[Daftar Lomba]  [Beli Tiket]  [Vote Favorit]
Overview / Participants / Schedule / Judges / Results
```

Setelah event selesai:
```text
RESULT
🥇 SMAN 1 Bandung   9,240
🥈 SMAN 3 Bandung   9,198
🥉 SMAN 5 Bandung   9,044
Best Danton: Muhammad ...
Best PBB: SMAN ...
Full Results →
```

Link ini tersebar di WhatsApp group peserta, Instagram Story, sekolah, alumni, organizer — **tiap event jadi acquisition channel Siapgrak**. Dinilai lebih penting daripada bikin "Grand Arena" duluan.

### Discovery bisa berkembang ke

```text
Upcoming LKBB
Results
Teams
Organizations
News
Competition Calendar
Achievements
Rankings
```

Contoh pencarian: "Cari LKBB di Jawa Barat", "Cari event tingkat nasional", "Event bulan Oktober" — ini yang bikin orang buka Siapgrak walau tidak sedang jadi panitia.

---

## 10. Fitur Internal Panitia yang Lebih Valuable dari Sekadar Meniru Simpaskor

**Technical Meeting** — panitia upload juknis, rundown, denah, surat undangan; peserta lihat versi terbaru.

**Competition Number / Drawing** — nomor urut otomatis (`A01, A02, A03...`), bisa random draw atau manual.

**Performance Schedule** — `08:00 A01, 08:12 A02, 08:24 A03...`

**Hari-H tracking per peserta:**
```text
A07
Checked In ✓
Ready Room ✓
On Deck
Performing
Finished ✓
```

Ini benar-benar event operation yang sulit digantikan Google Sheet.

---

## 11. Event Command Center (konsep tambahan)

Saat event berlangsung, panitia membuka dashboard live:

```text
LKBB GARUDA 2026 — LIVE
34 / 42 teams performed
Current: A28 — SMAN 7 Bandung
Next: A29 — SMAN 4 Cimahi
Judging: 4 / 5 judges submitted
Issues: 2 items
Voting: 128,421 votes ↗       (dari Kreenconnect API)
Ticket Check-in: 1,884 / 2,200  (dari Kreenconnect API)
```

Data competition dari Siapgrak, data ticket/voting dari Kreenconnect API — di sinilah integrasi dua produk terasa powerful.

---

## 12. Diferensiasi & Positioning

**Bukan:** "Sistem penilaian digital LKBB" (terlalu sempit, sudah banyak pesaing).

**Tapi:** *Platform untuk menjalankan, menemukan, dan mengikuti kompetisi Paskibra.*

Konsep produk: `Discover → Compete → Manage → Celebrate`

Alasan: kompetitor sudah punya voting berbayar, leaderboard, event discovery, sistem scoring — perang fitur di level itu saja akan cepat jadi komoditas.

---

## 13. Roadmap Revisi (mengganti urutan draft awal)

Draft awal: Sistem Penilaian → Ticketing → Voting. **ChatGPT tidak setuju** dengan urutan ini.

| Phase | Fokus |
|---|---|
| 1 | Organizations + Teams + Event |
| 2 | Categories + Competition Registration |
| 3 | Participants + Competition Number + Schedule |
| 4 | Judges + Scoring + Results |
| 5 | Public Event + Public Results |
| 6 | Kreenconnect Ticketing Integration |
| 7 | Kreenconnect Voting Integration |
| 8 | Event-day Command Center |
| 9 | Team Profiles + Achievement History |
| 10 | Ranking / ecosystem features |

**Siapgrak sudah usable di Phase 3** — tidak perlu menunggu payment/voting selesai dulu.

---

## 14. Laravel Architecture

Laravel 12 / PHP 8.3, **repo tunggal** tetap benar. Jangan microservices.

Struktur logical (opsional, jangan over-engineer):
```text
app/
├── Domain/
│   ├── Organization/
│   ├── Team/
│   ├── Event/
│   ├── Registration/
│   ├── Competition/
│   ├── Judging/
│   ├── Result/
│   └── Integration/
├── Http/
├── Models/
├── Services/
└── Jobs/
```

Kalau tim lebih nyaman Laravel biasa (`Models/Controllers/Services/Jobs/Policies` flat), juga cukup — **jangan DDD berlebihan**. Yang penting adalah boundary database-nya, bukan folder cantik.

---

## 15. Abstraksi API Kreenconnect

Jangan Siapgrak bergantung langsung ke format response Kreenconnect di controller. Buat abstraction layer:

```php
interface TicketingProvider
{
    public function getEventSummary(string $externalId);
}

interface VotingProvider
{
    public function getEventSummary(string $externalId);
}
```

Implementasi:
```php
KreenConnectTicketingProvider
KreenConnectVotingProvider
```

Logic domain Siapgrak tetap bicara ke `VotingProvider`, bukan `KreenconnectAPIController` — kalau sistem Kreenconnect berubah, domain Siapgrak tidak ikut berantakan.

---

## Kesimpulan

```text
KREENCONNECT = Commerce / transaction
  Ticket, Payment, Voting, Vote transaction

SIAPGRAK = Paskibra / LKBB operating system
  Discovery, Organizations, Teams, Events, Registration,
  Competition, Judging, Schedules, Results, Operations,
  Achievement history
```

Simpaskor dijadikan benchmark **workflow lomba**, tapi Siapgrak tidak perlu mengikuti batas produk mereka.

> **Siapgrak = "rumah digital ekosistem Paskibra/LKBB"; Kreenconnect = mesin komersial di belakangnya.**

### Langkah berikutnya yang disarankan (belum dikerjakan)

Sebelum coding, bikin **Blueprint Siapgrak v1**:
1. Menu public
2. Menu dashboard organizer
3. Alur peserta
4. Struktur event/LKBB
5. ERD/database lengkap (kolom + index + foreign key)
6. Kontrak API yang harus diminta dari Kreenconnect

---

## Sumber yang direferensikan ChatGPT

- Evenan — https://evenan.id/ (voting, ticketing, QR check-in, penilaian LKBB dalam satu produk)
- KORPS / Sireva — https://korps.my.id/lkbb.korps.my.id/ (voting LKBB Rp1.000/vote + invoice QRIS)
- Juknis SPMB (contoh regulasi prestasi non-akademik untuk PPDB) — https://smpn1cibadak.sch.id/wp-content/uploads/2026/06/Juknis-SPMB-Tahun-2026.pdf

*(Votingin disebut di teks asli tapi tidak disertai link sumber.)*
