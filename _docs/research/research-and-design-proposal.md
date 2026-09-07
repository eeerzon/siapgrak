# Siapgrak — Riset & Rancangan Awal

Status: **ideasi awal / belum digarap ("nanti")** — dokumen ini dibuat untuk didiskusikan lebih lanjut, bukan spek final.

## 1. Latar Belakang

Kreen Connect sudah punya sistem voting dan ticketing di ekosistemnya, dan juga sedang merombak total sistem judging pageant (`juri`, lihat catatan terpisah di project `judging-system-redesign-proposal.md` milik repo `juri`). Muncul ide untuk membuat produk baru yang fokus khusus ke ekosistem **Paskibra/LKBB (Lomba Ketangkasan Baris Berbaris)** — kompetisi baris-berbaris sekolah (SD/SMP/SMA) dan upacara pengibaran bendera.

Nama project: **siapgrak** (domain `siapgrak.id`, belum di-setup/masih kosong). Tagline informal dari pemilik project: *"semua tentang paskibra nanti disini"* — mengindikasikan visi jadi platform serba-ada untuk komunitas Paskibra, bukan cuma alat penilaian.

## 2. Riset Kompetitor: Simpaskor.id

Audit fitur dilakukan 2026-09-07 terhadap **Simpaskor.id** ("Sistem Paskibra Skor"), pemain existing di ruang yang sama.

### 2.1 Struktur Platform

- **Event → Rangkaian Event → Sub-event.** Event bisa berdiri sendiri, atau dikelompokkan sebagai "Rangkaian" — contoh: KEJURCAB FORBASI KOTA BANDUNG adalah satu rangkaian berisi 3 sub-event (RUKIBRA, VAFORMUS, LOBB), masing-masing kategori lomba berbeda dengan pendaftaran/biaya/kuota terpisah sendiri-sendiri.
- Tiap event punya 4 tab: **Informasi Event**, **Juri & Penilaian**, **Peserta**, **Hasil Perlombaan**.

### 2.2 Kriteria Penilaian — Dinamis per Event

Ini poin penting: kriteria BUKAN template fix satu-untuk-semua. Tiap event/kategori lomba set kriteria sendiri:

- **LKBB biasa** (contoh: Kejurcab Kab. Bogor): 5 kriteria — Danton, Variasi & Formasi, Makeup, PBB, Kostum.
- **RUKIBRA** (lomba upacara/pengibaran bendera): 8 kriteria berbasis peran individu — Komandan Pasukan, Pembawa Baki, Pasukan (per orang) di DP, Pengibar Bendera I, Pengibar Bendera II, Tutup Formasi, Teknis Pengibaran di Tiang, Buka Formasi.

Jadi struktur kriteria harus **per-kategori-lomba**, bukan hardcode.

### 2.3 Transparansi Publik (Tanpa Login)

- **Dewan Juri** ditampilkan publik: nama, foto, dan kriteria spesifik yang jadi tanggung jawab masing-masing juri (bukan cuma "juri A menilai semua").
- **Peserta**: kuota per kategori (SMA/SMP/SD/Purna) dengan progress bar terpisah, plus daftar lengkap tim terdaftar (nama sekolah, nama tim, kategori) — bisa difilter, semua publik tanpa login.
- **Hasil Perlombaan**: setelah event selesai, **rekapitulasi nilai & ranking publik** — tabel dengan medali 🥇🥈🥉 untuk top 3, total nilai per tim, asal sekolah. Ini kelihatan jadi fitur inti/selling point utama mereka.

### 2.4 E-Voting — Sistem Terpisah dari Penilaian Juri

Voting penonton itu modul yang **sama sekali terpisah** dari penilaian juri resmi:

- Kategori voting custom per event, bisa beda dari kriteria juri (contoh: "Komandan Terbaik", "Tim Terfavorit", "Best Vote").
- Mode gratis atau berbayar — model microtransaction **Rp 1.000/vote**.
- Halaman **"Grand Arena"** — live display bergaya esport untuk dipasang di layar besar venue: podium Juara 1-3 dengan animasi, leaderboard real-time, countdown timer, running-text komentar dukungan live ("Fahri mendukung Tim 1..."), opsi narator suara. Ini pattern UX yang cukup kuat — mengubah voting jadi tontonan panggung, bukan sekadar form.

### 2.5 Model Bisnis

| Paket | Harga | Isi |
|---|---|---|
| Iklan | Gratis | Event tampil di landing page, fitur locked (mode demo) |
| Ticketing | Hubungi Admin | Revenue-share, dibahas per event |
| Voting | Hubungi Admin | Revenue-share, dibahas per event |
| Bundle Tiket+Voting | Hubungi Admin | Gabungan keduanya |
| **Bronze** ★ populer | Rp 500.000 | E-Ticketing, E-Voting, Sistem Penilaian, Pendaftaran Peserta, Technical Meeting App, Laporan Digital, tim pendamping online |
| **Gold** ★ populer | Rp 1.500.000 | Semua Bronze + tim pendamping **onsite**, **device tablet** untuk juri di lapangan, tim rekap, penyusunan materi penilaian |

Catatan: mereka handle operasional lapangan juga (hardware tablet, tim pendamping fisik), bukan cuma jual software.

## 3. Insight untuk Rancangan Siapgrak

1. **Kriteria per event, bukan template lomba generik** — skema data harus mendukung struktur kriteria custom per kategori lomba sejak awal (mirip pendekatan `judging_criteria` dengan `parent_id` yang lagi dirancang di project `juri`).
2. **Transparansi publik = fitur, bukan afterthought** — juri/peserta/hasil publik tanpa login membangun trust dan jadi alat marketing organik (orang share link hasil ke grup WA sekolah).
3. **Voting penonton dan penilaian juri harus dipisah total** — beda tujuan (hiburan/engagement vs kompetisi resmi), beda data model, jangan dicampur biar ranking resmi tidak bisa "dibeli".
4. **"Live display" venue adalah diferensiator visual** — worth dipertimbangkan sejak desain awal kalau siapgrak mau bersaing di pengalaman event-day, bukan cuma backend administratif.
5. **Rangkaian event (multi-sub-event) adalah pola umum di LKBB** — satu penyelenggara sering bikin beberapa kategori lomba dalam satu hari/venue (mis. LKBB + LOBB + Vaformus sekaligus). Struktur data harus akomodasi ini dari awal.
6. **Kompetisi bukan cuma software** — kalau siapgrak mau bersaing serius, pertimbangkan apakah perlu layanan pendampingan (paket berjenjang seperti Bronze/Gold) atau cukup fokus produk software murni dulu.

## 4. Pertanyaan Terbuka (untuk didiskusikan / dilempar ke ChatGPT)

1. **Relasi dengan project `juri`** — apakah siapgrak akan **reuse** fondasi Judging System yang lagi dirombak di project `juri` (Event → Round → Criteria → Score Sheet, lihat catatan redesign-nya), atau siapgrak jadi produk berdiri sendiri dengan skema sendiri? Kalau reuse, perlu redesign supaya cukup generic untuk pageant DAN LKBB (beda banget kompleksitas kriterianya — 5-8 kriteria per role vs kriteria pageant per babak).
2. **Diferensiasi vs Simpaskor** — apa yang bikin siapgrak beda dan lebih baik? Kandidat: harga lebih murah/transparan (bukan "hubungi admin"), UX lebih modern, integrasi dengan ekosistem Kreen Connect yang sudah ada (SSO, dsb), atau fitur yang Simpaskor belum punya (misal: live streaming terintegrasi, sertifikat digital otomatis, dsb)?
3. **Scope MVP** — mulai dari modul mana? Rekomendasi awal: **Sistem Penilaian + Hasil Perlombaan publik** dulu (ini value inti), baru Ticketing, baru E-Voting/Grand Arena (paling kompleks & butuh payment gateway).
4. **Model bisnis** — flat fee per event (kayak Bronze/Gold) atau subscription per organisasi penyelenggara, atau revenue-share dari ticketing/voting?
5. **Payment gateway** — untuk ticketing berbayar dan voting berbayar, perlu integrasi payment (lihat catatan lama soal riset Xendit/Stripe di memory lain — payment gateway pernah jadi blocker di beberapa project Kreen lain seperti Kreen Xperience dan Vota Vote, worth dicek dulu kenapa).
6. **Multi-tenant** — apakah satu instance siapgrak melayani banyak penyelenggara (multi-tenant, kayak Simpaskor), atau white-label per klien?

## 5. Stack yang Sudah Diputuskan

- **Laravel 12, PHP 8.3**
- Repo tunggal (bukan dipecah admin/public/pos seperti kreen-xperience) — semua modul (event, penilaian, ticketing, voting) dalam satu aplikasi.
- Struktur folder & docs akan mengikuti standar dokumentasi Kreen (`_docs/`) begitu project ini naik status dari "ideasi" ke "digarap".
