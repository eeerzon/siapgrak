# Design Doc — Result Calculator + Ranking (Phase E, Sprint 11)

**Status:** Implemented (2026-09-16) — 19 test baru hijau (total 314), verifikasi manual via curl selesai
**Author:** Claude
**Tanggal:** 2026-09-16
**Kontrak:** `BACKEND-HANDOFF.md` §Sprint 11-12 (Results) · `tasks.md` breakdown: Sprint 11 = Result Calculator + Ranking
**Definition of Ready:** ✅ — Sprint 10 selesai (295 test), Phase D (Judging) tuntas — `score_sheets`/`scores` dengan lifecycle penuh sudah ada.

## TL;DR

`competition_results` — organizer klik **Hitung/Hitung Ulang Hasil** per round, `ResultService` (lewat `ResultCalculator`) menjumlah skor dari score sheet yang sudah `submitted`/`locked`, terapkan bobot kriteria, kurangi penalti (kalau ada), lalu ranking dengan tie-handling eksplisit. **Sprint ini cuma sampai hasil `draft`** — finalize/publish/awards/audit trail tetap Sprint 12 (pola sama pemisahan Sprint 9/10).

**Requirement ID:** `FR-RESULT-CALC-01`

## Keputusan Desain Kritis

**A. `judging_round_id` selalu diisi (tidak pernah NULL) — hasil per-round saja, rollup lintas-round DIDEFER.** Blueprint mengizinkan `judging_round_id` nullable untuk hasil "keseluruhan kategori" (agregat lintas semua round). Tapi mayoritas event v1 cuma punya 1 round ("Round tetap dipakai walau v1 kebanyakan event cuma 1 round" — `chatgpt-blueprint-v1.md`), jadi hasil per-round SUDAH JADI hasil keseluruhan untuk kasus paling umum. Membangun rollup lintas-round (perlu normalisasi `judging_round.weight` antar round) menambah kompleksitas nyata untuk kasus yang jarang dipakai di v1. **Didefer** ke sprint lanjutan kalau ada event nyata multi-round yang butuh — dicatat eksplisit di sini, bukan diam-diam diskip.

**B. Entry dengan kriteria yang belum lengkap di-skip total dari kalkulasi (tidak dapat baris `competition_results`)** — bukan ditampilkan dengan nilai `0`/parsial yang menyesatkan. "Lengkap" = SETIAP kriteria leaf di round itu punya minimal satu skor dari score sheet berstatus `submitted`/`locked` (draft TIDAK dihitung — juri belum selesai). Ini konsisten prinsip "status eksplisit, jangan ditebak" yang sudah dipakai di Sprint 9/10.

**C. Kalau satu kriteria dinilai oleh LEBIH dari satu juri (assignment overlap — bukan pola LKBB standar tapi tidak dicegah skema Sprint 8), nilainya di-**rata-rata**.** Blueprint LKBB menyiratkan pembagian tugas per-kriteria (satu juri = satu kriteria/round), bukan multi-juri-konsensus gaya pageant, tapi skema tidak melarang overlap. Rata-rata adalah default paling wajar untuk kasus tepi ini, bukan "ambil yang pertama" (arbitrary).

**D. Tie handling = standard competition ranking ("1-2-2-4"), eksplisit ditulis di sini karena blueprint mewajibkan.** Dua entry dengan `final_score` SAMA persis dapat rank yang SAMA; entry berikutnya dapat rank = (jumlah entry dengan skor lebih tinggi) + 1 — bukan rank berurutan tanpa lompat ("1-2-3-4" / dense ranking). Urutan tampilan di antara entry yang seri (rank sama) stabil pakai `competition_number` menaik — bukan urutan DB yang tidak terjamin.

**E. Penalti diinput manual organizer (bukan dihitung dari sumber lain)** — tidak ada tabel "pelanggaran"/"penalty reasons" di skema manapun (blueprint maupun sprint sebelumnya), jadi `calculatePenalty()` bukan menghitung dari data baru, melainkan **mempertahankan nilai yang sudah pernah diinput organizer** melewati Hitung Ulang (default `0` untuk entry baru). Organizer bisa edit `penalty_score` langsung per baris hasil lewat form kecil di halaman Hasil — `final_score` dan ranking otomatis re-kalkulasi setelahnya.

**F. Hitung Ulang = delete-then-rebuild per round, bukan update selektif.** Menghapus semua baris `competition_results` round itu lalu insert ulang yang lolos syarat lengkap (Keputusan B) — jauh lebih sederhana dan tidak meninggalkan baris basi (entry yang baru saja `disqualified`/`withdrawn` otomatis hilang dari hasil). Satu pengecualian: `penalty_score` yang sudah diinput manual di-snapshot SEBELUM delete dan diterapkan ulang ke baris baru untuk entry yang sama — supaya penalti tidak hilang tiap kali di-hitung ulang.

**G. Field yang dihitung (`raw_score`, `weighted_score`, `final_score`, `rank`, `status`, `finalized_at`) TIDAK mass-fillable — `$fillable` kosong total.** Semua diset lewat penetapan properti langsung di `ResultService`/`ResultCalculator` (pola sama `CompetitionEntry.status`/`ScoreSheet.status`). Bahkan `penalty_score` (satu-satunya field yang organizer edit) diset lewat properti langsung, bukan `update()` — konsisten "kalau bisa dihindari mass-assignment, hindari", tidak perlu form generik yang bisa nyerempet field lain.

**H. Tab "Hasil" di-gate penuh `manageJudging`, sama seperti "Lembar Skor" Sprint 10** — bukan `view` biasa seperti tab Kategori/Kompetisi. Ranking adalah agregat dari skor individual juri; sampai ada status `finalized`/`published` resmi (Sprint 12), ranking tetap dianggap data sensitif setingkat skor mentah (Constitution: kebocoran ranking sebelum finalize adalah insiden bisnis) — role `member` tidak melihat tab ini sama sekali.

**I. Bobot kriteria (`weight`) yang NULL diperlakukan sebagai 100%** (kontribusi penuh, tidak di-diskon) untuk `weighted_score` — bukan `0%` (yang akan membuat kriteria itu senyap tidak berkontribusi sama sekali, jelas salah). Organizer yang tidak repot mengisi bobot tetap dapat hasil yang masuk akal (weighted ≈ raw) — bukan wajib mengisi semua bobot supaya sistem "jalan benar".

## Perubahan Schema

### `competition_results` (migration baru)
```text
id                    BIGINT UNSIGNED, PK
event_category_id     FK → event_categories.id, CASCADE   (data turunan, bukan histori — selalu bisa dihitung ulang)
judging_round_id      FK → judging_rounds.id, CASCADE      (tidak pernah NULL — Keputusan A)
competition_entry_id  FK → competition_entries.id, CASCADE
raw_score             DECIMAL(10,3)
weighted_score        DECIMAL(10,3)
penalty_score         DECIMAL(10,3), default 0             (Keputusan E)
final_score           DECIMAL(10,3)
rank                  unsigned int, nullable
status                string, default 'draft'              (cuma 'draft' reachable sprint ini — finalize/publish Sprint 12)
finalized_at          timestamp, nullable                  (disiapkan utk Sprint 12)
timestamps
```
Unique `(event_category_id, judging_round_id, competition_entry_id)` — nama eksplisit pendek `competition_results_unique` dari awal (pelajaran Sprint 8/9, jangan biarkan Laravel auto-generate nama >64 karakter).

FK semua CASCADE (beda dari `score_sheets`/`scores` yang RESTRICT) — hasil ini murni data turunan yang selalu bisa dihitung ulang dari `scores`, bukan histori/bukti yang harus dijaga seperti skor mentah juri.

## Business Logic
```text
RULE-1  Hanya score dari ScoreSheet berstatus submitted/locked yang dihitung (RULE draft diabaikan, Keputusan B).
RULE-2  Entry dengan >=1 kriteria leaf belum ternilai (submitted/locked) di-skip total dari hasil round itu.
RULE-3  Entry withdrawn/disqualified tidak pernah masuk hasil (konsisten exclude di Sprint 9).
RULE-4  raw_score = jumlah skor per kriteria leaf (skor kriteria dg >1 juri = rata-rata, Keputusan C).
RULE-5  weighted_score = jumlah (skor kriteria * bobot/100), bobot NULL = 100% (Keputusan I).
RULE-6  final_score = weighted_score - penalty_score.
RULE-7  rank = standard competition ranking (1-2-2-4), urutan tampilan antar-seri stabil via competition_number
        (Keputusan D).
RULE-8  Hitung Ulang = delete+rebuild per round, penalty_score existing dipertahankan (Keputusan F).
```

## Permission
| Aksi | Owner/Admin | Member |
|---|---|---|
| Lihat tab Hasil | ✅ | ❌ |
| Hitung/Hitung Ulang | ✅ | ❌ |
| Edit penalti | ✅ | ❌ |

## Testing Plan
```text
ResultCalculator/CalculationTest
  □ raw_score = jumlah skor semua kriteria leaf
  □ weighted_score menerapkan bobot kriteria; bobot NULL dihitung 100%
  □ kriteria dinilai >1 juri → dirata-rata
  □ entry dengan kriteria belum submitted/locked → di-skip (tidak dapat baris hasil)
  □ score sheet masih draft tidak dihitung
  □ entry withdrawn/disqualified tidak pernah masuk hasil
  □ final_score = weighted - penalty

ResultCalculator/RankingTest
  □ ranking benar untuk skor unik (1,2,3,...)
  □ dua entry final_score sama → rank sama, entry berikutnya lompat sesuai jumlah di atasnya (1,1,3)
  □ urutan tampilan antar-seri stabil (competition_number)

ResultService/RecalculateTest (feature, via route)
  □ hitung ulang meng-upsert hasil yang benar
  □ hitung ulang mempertahankan penalty_score yang sudah diinput manual
  □ entry yang baru disqualified hilang dari hasil setelah hitung ulang
  □ organizer edit penalti → final_score & rank re-kalkulasi otomatis
  □ member role tidak bisa lihat tab Hasil / trigger hitung / edit penalti

ResultService/TenantIsolationTest
  □ organizer org lain tidak bisa trigger hitung / edit penalti hasil event org lain
```

## Catatan Implementasi

**Gap ditemukan+diperbaiki sebelum lapor selesai: urutan tampilan antar-seri (Keputusan D) tidak benar-benar ditegakkan di query.** `ResultCalculator::calculateRanking()` sudah menghitung urutan stabil (rank, lalu `competition_number`) secara in-memory sebelum menyimpan tiap baris — tapi relasi `JudgingRound::results()` yang dipakai untuk MENAMPILKAN ulang hasil (di halaman event maupun test) awalnya cuma `orderBy('rank')`. Untuk entry yang seri (rank sama), MySQL tidak menjamin urutan tanpa secondary sort — jadi tampilan bisa beda tiap kali di-query ulang meski hasil kalkulasi sendiri benar. Ketahuan dari test `test_display_order_among_ties_is_stable_by_competition_number` yang ditulis khusus untuk memverifikasi Keputusan D di level query, bukan cuma di level kalkulator. **Fix:** relasi `results()` di-JOIN ke `competition_entries` dan diberi `orderBy` kedua eksplisit ke `competition_number` — sekarang urutan stabil dijamin di level query, bukan cuma kebetulan sesuai urutan insert dari kalkulator.

Verifikasi manual (curl, live server) mengonfirmasi seluruh alur dengan data 3 peserta (2 lengkap, 1 sengaja kriteria: entry incomplete): hitung hasil ✅ (raw/weighted benar sesuai bobot kriteria), entry yang belum lengkap kriterianya **tidak muncul sama sekali** di hasil ✅, edit penalti langsung mengubah final_score dan urutan ranking ✅ (peserta yang tadinya rank 1 kena penalti turun ke rank 2), hitung ulang **mempertahankan penalti yang sudah diinput** ✅, dan role member sama sekali tidak melihat isi tab Hasil (pesan "Tidak ada akses", bukan cuma tombol yang disembunyikan) + ditolak 403 kalau coba trigger hitung langsung lewat URL ✅.

**Update Sprint 12:** `ResultController`/`UpdatePenaltyRequest` di-refactor pakai `EventPolicy::manageResults` (bukan `manageJudging` lagi) — lihat `fr-awards-finalization.md` Keputusan D untuk alasannya (Results resmi jadi domain Phase E terpisah begitu Sprint 12 menambah finalize/publish/reopen). Role check yang mendasarinya tidak berubah (tetap Owner+Admin).

## File Dibuat/Diubah
```text
database/migrations/*_create_competition_results_table.php (24)
app/Enums/ResultStatus.php
app/Models/CompetitionResult.php
app/Models/JudgingRound.php          (+results() relation, joined+ordered for stable ties — lihat Catatan Implementasi)
app/Services/ResultCalculator.php    (calculateRawScore/calculateWeightedScore/calculatePenalty/calculateFinalScore/calculateRanking)
app/Services/ResultService.php       (calculateForRound, updatePenalty)
app/Http/Requests/Result/UpdatePenaltyRequest.php
app/Http/Controllers/Dashboard/ResultController.php  (calculate, updatePenalty)
database/factories/CompetitionResultFactory.php
resources/views/dashboard/events/partials/results.blade.php
resources/views/dashboard/events/show.blade.php   (+tab "Hasil")
app/Http/Controllers/Dashboard/EventController.php (+eager load categories.judgingRounds.results.entry.team)
routes/web.php  (+judging-rounds/{round}/results/calculate, +results/{result}/penalty)
tests/Feature/Result/CalculationTest.php
tests/Feature/Result/RankingTest.php
tests/Feature/Result/RecalculateTest.php
tests/Feature/Result/TenantIsolationTest.php
```
