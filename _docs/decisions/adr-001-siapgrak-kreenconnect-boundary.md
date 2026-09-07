# ADR-001 — Boundary Siapgrak vs Kreenconnect, dan Non-Reuse Database `juri`

**Status:** Accepted
**Tanggal:** 2026-09-07

## Konteks

Draft rancangan awal Siapgrak (`research/research-and-design-proposal.md`) mengasumsikan ticketing dan voting akan dibangun native di Siapgrak "nanti", sebagai roadmap fase lanjutan. Setelah riset lebih dalam ke landscape kompetitor (Simpaskor.id, Evenan, Votingin, KORPS/Sireva — semua sudah menggabungkan ticketing+voting+penilaian LKBB dalam satu produk), muncul risiko: kalau Siapgrak juga membangun ticketing/voting native, project akan (1) membesar tanpa perlu, (2) maintenance dobel dengan sistem Kreenconnect yang sudah mature untuk fungsi sama, (3) data transaksi terpecah antara dua sistem, dan (4) diferensiasi produk jadi tipis — cuma jadi "Simpaskor versi lain".

Pertanyaan kedua yang perlu diputuskan: apakah skema database judging Siapgrak (LKBB) sebaiknya di-generic-kan dengan skema judging system yang sedang dirombak di project `juri` (pageant), mengingat pola strukturalnya mirip (Round → Criteria → Judge Assignment → Score Sheet → Audit Trail).

## Keputusan

**1. Siapgrak tidak membangun ticketing/voting/payment sendiri.** Siapgrak mengonsumsi fungsi tersebut lewat API Kreenconnect. Positioning final:

```
SIAPGRAK      = platform operasional & ekosistem LKBB/Paskibra
                (discovery, organizations/teams, event ops, competition
                management, judging, achievement history)
KREENCONNECT  = transaction engine
                (ticketing, voting, payment)
```

**2. Skema database judging Siapgrak TIDAK di-generic-kan dengan project `juri`.** Reuse konsep/pola (Round, Criteria, Judge Assignment, Score Sheet, Submission Lock, Audit Trail, Result Calculation), tapi tabel database tetap terpisah — `judging_*` di Siapgrak, skema sendiri di `juri`. Ekstraksi ke shared judging engine hanya dilakukan kalau nanti benar-benar terbukti identik secara berulang, bukan diasumsikan di awal.

## Alternatif yang Dipertimbangkan

| Opsi | Kelebihan | Kekurangan | Keputusan |
|---|---|---|---|
| A. Bangun ticketing/voting native di Siapgrak | Kontrol penuh atas UX, tidak bergantung API eksternal | Maintenance dobel, data transaksi terpecah, project membesar, diferensiasi tipis vs kompetitor | ❌ Ditolak |
| B. Siapgrak konsumsi Kreenconnect via API, tidak simpan transaksi sendiri | Boundary bersih, tidak duplikasi effort, Siapgrak bisa fokus ke domain LKBB yang jadi moat sesungguhnya (histori tim, transparansi publik) | Bergantung pada kontrak API Kreenconnect yang harus dikonfirmasi & stabil; UX checkout sebagian ada di luar kendali Siapgrak | ✅ Dipilih |
| C. Generic-kan skema judging Siapgrak dengan `juri` (shared judging engine sejak awal) | Satu skema, satu maintenance, potensi reuse kode | Domain pageant vs LKBB cukup berbeda (kriteria per-role LKBB vs babak preliminary/final pageant); generic terlalu dini berisiko bikin skema sulit dipahami developer kedua sistem | ❌ Ditolak (untuk sekarang) |
| D. Skema judging terpisah, pola teknis sama, evaluasi ulang nanti | Sederhana untuk masing-masing domain, tidak menutup opsi ekstraksi di masa depan kalau benar-benar identik | Ada duplikasi struktural (dua tabel `judging_rounds`, dst di dua project) yang secara sadar diterima | ✅ Dipilih |

## Konsekuensi

**Positif:**
- Scope Siapgrak tetap fokus & lean (~25 tabel, bukan generic platform besar)
- Tidak ada risiko data transaksi (uang) tersebar di dua sistem yang bisa divergen
- Moat produk jangka panjang (histori tim/sekolah, transparansi publik) jadi fokus development, bukan tenggelam mengejar fitur transaksi yang sudah dikuasai kompetitor

**Negatif/Risiko:**
- Siapgrak bergantung pada Kreenconnect API tersedia & stabil sebelum Phase 6 bisa dikerjakan — **kontrak API di `research/chatgpt-blueprint-v1.md` masih proposal, belum dikonfirmasi tim Kreenconnect** (lihat BRD §8 Dependensi)
- UX checkout ticketing/voting sebagian ada di domain Kreenconnect (redirect ke `checkout.kreenconnect.com`) — Siapgrak tidak bisa fully mengontrol pengalaman pembayaran end-to-end
- Kalau nanti Siapgrak dan `juri` benar-benar butuh judging engine identik, akan ada biaya migrasi/ekstraksi yang tidak dikerjakan dari awal

**Aturan turunan:**
- Dilarang membuat tabel `tickets, ticket_types, orders, payments, payment_transactions, votes, vote_orders, vote_transactions, invoices, refunds, settlements` di database Siapgrak (lihat CLAUDE.md → Constitution, dan `security/security-baseline.md`)
- Setiap developer yang menemukan kebutuhan fitur baru wajib tanya dulu: "Apakah ini domain LKBB/competition, atau domain transaksi?" — kalau transaksi, jawabannya selalu Kreenconnect, tidak dibangun di Siapgrak
- Konfirmasi kontrak API Kreenconnect ke tim terkait adalah prasyarat wajib sebelum Phase 6 (Kreenconnect Integration) dimulai — dicatat sebagai blocking dependency di `tasks/tasks.md`

---

*ADR tidak pernah dihapus/diedit setelah Accepted. Kalau keputusan berubah, buat ADR baru yang mem-supersede ADR ini.*
