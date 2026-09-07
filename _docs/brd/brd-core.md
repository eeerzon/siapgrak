# BRD — Siapgrak (Core)

**Status:** Draft
**Author:** Ersa (via riset + diskusi ChatGPT), disusun ulang oleh Claude
**Tanggal:** 2026-09-07
**Project:** Siapgrak

---

## 1. Latar Belakang

Kreen Connect sudah punya sistem voting dan ticketing yang mature di ekosistemnya. Terpisah dari itu, muncul kebutuhan untuk platform yang fokus khusus ke ekosistem **Paskibra dan LKBB (Lomba Ketangkasan Baris Berbaris)** — kompetisi baris-berbaris dan upacara pengibaran bendera antar sekolah (SD/SMP/SMA).

Riset kompetitor terhadap **Simpaskor.id** (2026-09-07) menunjukkan ada pemain existing di ruang ini dengan fitur event management, penilaian juri, dan e-voting berbayar. Riset lanjutan menemukan kompetitor lain: Evenan, Votingin, KORPS/Sireva — semua sudah menggabungkan ticketing+voting+penilaian dalam satu produk. Kalau Siapgrak dibangun sebagai "Simpaskor versi lain" dengan fitur yang sama, diferensiasinya tipis.

## 2. Masalah

| # | Masalah |
|---|---|
| P1 | Panitia LKBB masih mengelola pendaftaran peserta lewat Google Form/spreadsheet — rawan duplikasi entri, tidak ada validasi terstruktur, tidak reusable antar event. |
| P2 | Tidak ada sistem penilaian digital yang menangani struktur kriteria LKBB yang bervariasi (5 kriteria untuk LKBB biasa, 8 kriteria per-role untuk RUKIBRA/upacara) — kalau pun ada, sering hardcode satu skema penilaian. |
| P3 | Data historis tim/sekolah (prestasi, jumlah event diikuti) tidak tersimpan permanen — tiap event, nama tim/sekolah diketik ulang manual, sehingga tidak bisa diagregasi jadi rekam jejak. |
| P4 | Kompetitor existing menggabungkan transaksi (ticketing/voting) dengan operasional lomba dalam satu sistem monolitik, sehingga kalau Siapgrak coba menyaingi fitur-per-fitur, akan selalu tertinggal dari pemain yang sudah mature di sisi payment. |
| P5 | Tidak ada platform yang memposisikan diri sebagai "rumah digital" komunitas Paskibra/LKBB (discovery event, profil tim, histori prestasi) — kompetitor fokus ke tools transaksional per-event, bukan ekosistem jangka panjang. |

## 3. User Persona

| Persona | Role | Familiaritas Teknis | Goals | Pain Points | Frekuensi Pakai |
|---|---|---|---|---|---|
| Panitia/Organizer | Event Admin (mis. FORBASI Kota Bandung) | Menengah | Kelola pendaftaran, penilaian, dan publikasi hasil lomba tanpa spreadsheet manual | Google Form terpisah dari rekap nilai, tidak ada histori antar event, koordinasi juri manual via WA | Musiman (tiap event yang diselenggarakan, bisa beberapa kali/tahun) |
| Juri (Judge) | Penilai kriteria spesifik | Awam–menengah | Menilai peserta yang jadi tanggung jawabnya dengan cepat, tanpa salah input, tanpa bisa mengintip nilai juri lain | Form kertas atau spreadsheet rawan salah rekap, tidak ada validasi range skor | Per-event (hari-H saja) |
| Perwakilan Tim/Sekolah (Team Representative) | Pendaftar & pengelola roster tim | Awam–menengah | Daftar lomba dengan cepat, tahu status approval, lihat hasil & prestasi timnya dari waktu ke waktu | Isi form yang sama berulang tiap event, tidak ada rekam jejak prestasi tim yang bisa ditunjukkan ke pihak lain | Musiman, tapi representative sama sering daftar ke banyak event/tahun |
| Publik (penonton, alumni, orang tua) | Pengunjung tanpa login | Awam | Cari jadwal event, cek hasil lomba, lihat profil tim/sekolah favorit | Hasil lomba sering hanya tersebar di grup WA/Instagram Story, tidak ada arsip permanen yang mudah dicari | Sesekali, terutama sekitar tanggal event |
| Super Admin (internal Kreen) | Pengelola platform lintas-organizer | Tinggi | Pastikan tenant isolation aman, pantau kesehatan integrasi Kreenconnect | Perlu visibility lintas organizer tanpa membocorkan data organizer lain | Berkala |

## 4. User Journey (Organizer — journey utama)

```
ENTRY POINT   : Organizer login ke dashboard Siapgrak
LANGKAH 1     : Buat Organization (kalau belum ada)
LANGKAH 2     : Buat Event (basic info + schedule + registration window)
LANGKAH 3     : Konfigurasi Event Category (mis. SMA/SMP/Purna, atau RUKIBRA/LKBB/VAFORMUS)
LANGKAH 4     : Publish event → muncul di public event page
LANGKAH 5     : Tim mendaftar (di luar kendali organizer, lihat journey Team Representative)
LANGKAH 6     : Organizer review registration → approve/request revision/reject
                ⚠️ Titik friksi kompetitor: kalau reject terjadi H-1, tim tidak sempat revisi — perlu deadline jelas
LANGKAH 7     : Approved registration otomatis jadi Competition Entry, organizer assign competition number
LANGKAH 8     : Organizer setup Judging Round + Criteria, assign Judges
LANGKAH 9     : Hari-H: entry di-check-in, status berjalan (ready → on_deck → performing → finished)
LANGKAH 10    : Juri submit score sheet per entry
LANGKAH 11    : Organizer calculate → review → finalize → publish result
EXIT          : Hasil lomba publik + organizer dapat dashboard summary integrasi Kreenconnect (kalau tersambung)
```

## 5. Tujuan & Success Metric

| Tujuan | Metric | Target |
|---|---|---|
| Gantikan Google Form untuk pendaftaran peserta | % event yang pendaftarannya lewat Siapgrak (bukan link eksternal) | 100% event yang dikelola di Siapgrak, tanpa link Google Form paralel |
| Penilaian digital tanpa spreadsheet | Jumlah score sheet yang di-submit lewat Siapgrak vs manual | 100% score sheet per event yang pakai Judging module |
| Transparansi hasil publik | Waktu dari `finalize` ke `publish` hasil | ≤ 1 jam setelah organizer klik finalize (bukan menunggu manual upload ke medsos) |
| Histori tim jadi aset jangka panjang | Jumlah team profile yang punya ≥2 event history setelah 1 tahun berjalan | Terukur setelah data cukup — baseline metric ini didefinisikan ulang begitu Phase 7 mulai |
| Boundary Siapgrak–Kreenconnect tetap bersih | Jumlah tabel transaksi (`tickets/payments/votes/*`) di database Siapgrak | 0 (selalu nol — lihat ADR-001) |

## 6. Scope

### In Scope (fondasi v1, lihat roadmap phase di `tasks/tasks.md`)
- Organization & Team sebagai entity permanen (bukan free-text)
- Event, Event Series (opsional), Event Category dengan kriteria kustom per event
- Competition Registration (pendaftaran peserta, bukan ticketing)
- Competition Operations (competition number, schedule, status hari-H)
- Judging System (round, criteria, judge assignment, score sheet dengan lifecycle draft/submitted/locked, audit trail revision)
- Result calculation, finalize, publish, awards
- Public transparency: event discovery, participants, judges, results — semua tanpa login
- Integrasi Kreenconnect untuk ticketing & voting (via API, bukan native)

### Out of Scope (sengaja, lihat ADR-001 dan `research/chatgpt-blueprint-v1.md` §"Tabel yang SENGAJA TIDAK dibuat")
- Ticketing, payment, paid voting — semua itu domain Kreenconnect
- Chat, forum, social feed, native livestream, mobile app
- AI scoring, automatic certificates, marketplace, merchandise, full CRM/accounting
- Multi-database per organizer (Siapgrak selalu shared database, single Laravel app)

## 7. User Stories

```
SEBAGAI Organizer,
SAYA INGIN membuat event dengan kategori kompetisi custom,
SUPAYA saya tidak terpaksa memaksakan struktur SMA/SMP/SD generik ke event yang punya kategori berbeda (mis. RUKIBRA/LKBB/VAFORMUS).

Acceptance Criteria:
- [ ] Organizer bisa membuat >1 Event Category dalam satu event, masing-masing dengan kuota & jadwal pendaftaran sendiri
- [ ] Kategori tidak dibatasi ke daftar tetap (SMA/SMP/SD) — nama & code bebas diisi organizer
```

```
SEBAGAI Juri,
SAYA INGIN hanya melihat entry dan kriteria yang jadi tanggung jawab saya,
SUPAYA saya tidak salah menilai kriteria yang bukan assignment saya, dan tidak bisa mengintip nilai juri lain.

Acceptance Criteria:
- [ ] Judge login area terpisah dari dashboard organizer
- [ ] Judge hanya melihat competition entry + criteria sesuai judge_assignments miliknya
- [ ] Judge tidak bisa membuka score sheet judge lain
```

```
SEBAGAI Perwakilan Tim,
SAYA INGIN mendaftarkan tim saya ke event tanpa mengetik ulang data sekolah/tim setiap kali,
SUPAYA pendaftaran lebih cepat dan histori tim saya konsisten dari tahun ke tahun.

Acceptance Criteria:
- [ ] Representative bisa memilih existing team (kalau sudah pernah daftar sebelumnya) atau create team baru
- [ ] Team reusable untuk event berikutnya tanpa re-entry data organisasi
```

```
SEBAGAI Publik,
SAYA INGIN melihat hasil lomba dan profil tim tanpa perlu login,
SUPAYA saya bisa share link hasil ke grup WA/Instagram Story tanpa hambatan.

Acceptance Criteria:
- [ ] Halaman /events/{slug}/results, /teams/{slug} dapat diakses tanpa autentikasi
- [ ] Data personal anggota tim TIDAK terekspos di halaman publik (hanya team/school/category/competition number)
```

## 8. Asumsi & Dependensi

| Asumsi | Risiko kalau asumsi salah |
|---|---|
| Kreenconnect API sudah/akan menyediakan kontrak endpoint sesuai proposal di `research/chatgpt-blueprint-v1.md` §69-76 | Kontrak API itu masih **proposal ChatGPT, belum dikonfirmasi tim Kreenconnect** — Phase 6 (integrasi) bisa perlu redesign kontrak kalau API asli berbeda |
| Struktur kriteria judging LKBB (per-role atau per-aspek, dengan weight) cukup direpresentasikan sebagai `judging_criteria` dengan `parent_id` max depth 2 | Kalau ada federasi LKBB dengan struktur kriteria lebih dari 2 level, model perlu direvisi |
| Satu organizer = satu organisasi penyelenggara (mis. FORBASI Kota Bandung), bukan individu | Kalau ada kasus organizer perorangan tanpa badan formal, `organization.type` perlu kategori tambahan (sudah ada `other`, kemungkinan cukup) |

| Dependensi | Status |
|---|---|
| Kreenconnect API (ticketing, voting, checkout session, webhook) | **Belum dikonfirmasi** — proposal kontrak ada, implementasi nyata belum diverifikasi tim Kreenconnect |
| Domain `siapgrak.id` | Belum di-setup |
| SSO Kreen Connect existing (opsional, untuk auth) | Belum diputuskan apakah Siapgrak pakai SSO existing atau auth Laravel mandiri — lihat Open Question |

## 9. Risiko

| Risiko | Kemungkinan | Dampak | Mitigasi |
|---|---|---|---|
| Kontrak API Kreenconnect proposal ternyata beda jauh dari implementasi asli | Sedang | Tinggi (blocking Phase 6) | Konfirmasi kontrak API ke tim Kreenconnect SEBELUM mulai Phase 6, bukan saat itu juga |
| Developer tergoda generic-kan skema judging dengan project `juri` terlalu dini | Sedang | Sedang (kompleksitas naik, dua domain beda jadi susah dipahami) | ADR-001 eksplisit melarang ini sampai terbukti benar-benar identik |
| Scope creep — mulai dari fitur ticketing/voting native "karena kelihatan gampang" | Rendah (sudah ada Constitution di CLAUDE.md) | Tinggi (balik ke masalah P4) | Constitution + review checklist eksplisit cek pelanggaran ini |
| Struktur kriteria judging kompetitor berubah/lebih kompleks dari riset 2026-09-07 | Rendah-Sedang | Sedang | Re-verify riset Simpaskor kalau project ini mulai dieksekusi jauh setelah tanggal riset |

## 10. Keputusan

| Keputusan | Pilihan yang Dipertimbangkan | Pilihan yang Dipilih | Alasan |
|---|---|---|---|
| Boundary ticketing/voting | (A) Bangun native di Siapgrak, (B) Konsumsi API Kreenconnect | B | Hindari maintenance dobel, data terpecah, dan Siapgrak jadi "Simpaskor clone" tanpa diferensiasi — lihat ADR-001 |
| Struktur Event Series | (A) Event → Rangkaian (wajib) → Sub-event, (B) Event Series opsional | B | Draft awal (A) dinilai membingungkan; Event Series sebagai relasi opsional (`event_series_id` nullable) lebih simpel dan tidak memaksa event biasa punya parent |
| Reuse database judging `juri` | (A) Generic-kan skema judging pageant+LKBB, (B) Skema terpisah, pola teknis sama | B | Domain pageant dan LKBB cukup berbeda; generic terlalu dini berisiko bikin skema membingungkan developer — lihat ADR-001 |

> Keputusan boundary Kreenconnect dan reuse `juri` cukup besar dampaknya dan sulit di-reverse setelah coding dimulai — ditulis ulang lengkap sebagai [ADR-001](../decisions/adr-001-siapgrak-kreenconnect-boundary.md).

## 11. Sign-off

| Peran | Nama | Status | Tanggal |
|---|---|---|---|
| Product Owner | Ersa | ☐ Approved | — |
