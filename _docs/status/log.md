# Status Log — Siapgrak

Log per sesi kerja (WAJIB diupdate, lihat CLAUDE.md §"Status Update"). Entry baru selalu di paling atas. Ini bukan pengganti `frontend-plan.md`/`BACKEND-HANDOFF.md` — ini ringkasan cepat "sampai mana, blocker apa, lanjut ke mana" untuk siapa pun yang buka session baru.

---

## 2026-11-25 — Cek bug dashboard

**Diselesaikan:**
- Crawl semua halaman dashboard sebagai admin + fuzz query (`page`, `status`, `search`, `tab`, `category`, array, karakter aneh). Sebelum fix ada 2 sumber error 500; sekarang 0.
- **Halaman Anggota Organisasi selalu error 500.** Penyebabnya, `belongsTo(User::class)` tanpa kolom membuat Laravel menebak `user_id_user`, karena PK User adalah `id_user`. Ditambahkan `'user_id'` eksplisit di 5 model: OrganizationUser, EventStaff, ActivityLog, Judge, TeamMember. Efek sampingnya ikut beres: nama staf event sebelumnya selalu tampil "—".
- Filter `?status[]=` / `?search[]=` di Events, Teams, Juri, dan Pendaftaran tidak lagi 500. Memakai helper `queryText`, dan view tidak lagi meng-echo array.
- Pencarian dashboard (Events, Teams, Juri): `%` dan `_` dicari literal, lewat helper baru `Controller::containsPattern` dengan `escape '!'`.
- Test baru `tests/Feature/Dashboard/DashboardEdgeCaseTest.php` (5 test).
- Catatan cakupan: modul admin yang aktif cuma `/dashboard` + `/dashboard/content/*`. Modul events/teams/org di atas adalah modul lama; perbaikannya dibiarkan atas persetujuan Ersa.
- **Bug content/penjurian:**
  - Tambah peserta (atau pindah kategori) sekarang ditolak kalau hasil babak pertama kategori itu sudah publik atau babak berikutnya sudah mulai dinilai. Aturannya sama dengan `teamListClosedReason` untuk babak lanjutan. Peserta baru juga mengosongkan daftar peserta babak berikutnya yang dipilih dari peringkat lama.
  - Juri nonaktif tidak bisa ditugaskan ke babak. Sebelumnya babak jadi macet karena juri itu tidak bisa login, jadi lembarnya tidak pernah lengkap.
  - Bobot kriteria maksimal 2 desimal, sesuai kolom `decimal(5,2)`. Sebelumnya 3 × 33,333 di MySQL tersimpan 99,99, jadi babak tidak pernah siap.
  - Pencarian juri content: `%`/`_` dicari literal.
  - `contact_persons[]` yang bukan array jadi error validasi, bukan 500.
- Test baru `tests/Feature/CompetitionJudging/ContentJudgingEdgeCaseTest.php` (6 test). Suite Feature (MySQL): 523 lulus, 5 skip.

**Blocker/keputusan:** tidak ada.

**Next:** perubahan belum di-commit (Ersa yang push).

---

## 2026-11-24 — Rapikan responsive site publik, dropdown dengan pencarian

**Diselesaikan:**
- Audit responsive semua halaman publik di 360/640/1024 px: tidak ada yang meluber ke samping.
- Header: Daftar + Masuk satu grup, tetap sebaris dengan logo di HP (dulu Masuk turun ke baris kedua). Saat login cukup satu tombol Dashboard (dulu dua). Subjudul logo disembunyikan di bawah 380 px.
- Footer: Jelajahi dan Ikuti Kami dua kolom di HP/tablet, padding lebih pendek.
- Filter Info Lomba: di tablet pencarian satu baris penuh, Jenjang + Provinsi berdampingan.
- Komponen baru `x-ui.combobox` (+ `resources/js/combobox.js`): dropdown dengan kotak pencarian di dalamnya, keyboard (panah, Enter, Esc). Dipakai untuk Provinsi (filter publik dan form Info Lomba di dashboard) serta Jenjang (tanpa pencarian, supaya tampilannya seragam).
- Kartu lomba di HP: padding dan teks lebih kecil, judul maksimal 3 baris (lengkapnya di `title`), chip jenjang/status lebih ringkas.
- Halaman hasil di HP: kotak nomor 44 px (dulu 56), kolom peringkat dan nilai lebih sempit, jadi nama tim dapat ruang lebih lebar. Tampilan md+ tetap sama.
- Audit responsive dashboard (14 halaman, 360/768 px):
  - Dashboard utama meluber ke 480 px di HP karena judul lomba panjang. Grid diberi `grid-cols-1`, judul dipotong dengan "…".
  - Tab konten (Info Lomba/Agenda/Kabar/Juri) dan tab Penjurian (Peserta/Babak & Juri/Lembar/Hasil) memakai label pendek di HP. Sebelumnya tab aktif "Juri"/"Hasil" tidak kelihatan.
  - Link hasil publik: di HP kolom link selebar penuh, tombol Salin/Buka di bawahnya.
  - Blok kode akses juri rata kiri di HP.
- Bug halaman publik:
  - Cari "%" atau "_" menampilkan semua konten karena dianggap wildcard LIKE. Sekarang di-escape di `IsPublicContent::scopeSearch`; ikut berlaku untuk pencarian di dashboard.
  - `?page=` melewati halaman terakhir menampilkan "Belum ada lomba…" padahal ada isinya. Sekarang redirect ke halaman terakhir (`Controller::redirectPastLastPage`, dipakai di Info Lomba, Agenda, Kabar).
  - Dropdown Provinsi berisi provinsi yang lombanya hanya ada di tab lain, jadi hasilnya kosong. Sekarang hanya provinsi yang punya lomba di tab aktif; provinsi yang sedang dipilih tetap ada di daftar.
  - Halaman hasil: tombol ganti tema disembunyikan, tapi mode gelap yang tersimpan di browser tetap terpasang tanpa cara mematikannya. Tanpa tombol, halaman sekarang selalu terang.
  - Batas pendaftaran Info Lomba tidak boleh setelah lomba selesai (`end_date`, atau `start_date` kalau lomba sehari). Sebelumnya lomba yang sudah lewat bisa tetap tampil "Buka s.d. …". Data lokal tidak ada yang bentrok.
  - Nama bulan di dashboard jadi bahasa Indonesia ("1 Des", bukan "1 Dec"): `Carbon::setLocale('id')` di `AppServiceProvider` (APP_LOCALE tidak diubah), serta `format('… M …')` → `translatedFormat` di lembar nilai, event, dan registrasi.
  - Halaman hasil: nama lomba di atas judul sekarang tautan balik ke halaman detail lomba.
  - Test baru: `tests/Feature/PublicContent/PublicSiteEdgeCaseTest.php`.
- Layout site sekarang memuat `app.js` (Alpine). Auto-filter mengabaikan field tanpa `name`, supaya kotak cari di dropdown tidak memicu submit.

**Test:** 506 lulus, 5 skip.

---

## 2026-11-19 — Modal buka ulang, fitur Kunci disembunyikan

**Diselesaikan:**
- "Buka ulang" di tab Lembar Nilai jadi modal (Batal, ×, Esc, klik luar menutup); popover `<details>` lama tidak bisa ditutup dan terpotong tabel.
- Fitur Kunci disembunyikan (di-comment di view; route `judgings.lock` dan `CompetitionJudgingService::lock()` tetap ada). Lembar berstatus locked tampil sebagai Terkirim di admin dan panel juri.
- Review bug aturan antarbabak: (1) hasil babak tidak bisa ditarik dari publik lagi setelah babak berikutnya mulai dinilai; (2) menarik hasil mengosongkan peserta babak berikutnya yang belum dinilai; (3) juri baru tidak bisa ditugaskan ke babak yang sedang dipublikasikan; (4) babak yang dipublikasikan sebelum semua juri selesai memberi arahan "tarik dulu", bukan "tunggu juri".
- 506 test lolos, 5 skipped.

---

## 2026-11-18 — Status selesai dinilai, urutan peserta per peringkat, perbaikan bug

**Diselesaikan:**
- Bug: route keluarkan peserta dari babak tidak mengecek kategori (nama peserta lomba lain bocor di pesan) → 404. Tim yang pindah kategori sekarang keluar dari babak lanjutan kategori lama.
- Juri kembali ke daftar peserta setelah Simpan Draft / Kirim Skor Final.
- "Pilih manual" dan chip peserta babak diurutkan menurut peringkat babak sebelumnya (badge peringkat; daftar pilih juga menampilkan nilai).
- Babak "selesai dinilai" (semua juri kirim untuk semua peserta): label status, form Tugaskan juri disembunyikan dan ditolak server; peserta babak berikutnya baru bisa dipilih setelah babak sebelumnya selesai (placeholder "masih berlangsung" + progres lembar).
- Peserta babak berikutnya baru bisa dipilih setelah babak sebelumnya selesai dinilai dan dipublikasikan (placeholder + ditolak server).
- Tambah peserta ke babak lanjutan: hanya dari peserta babak sebelumnya; ditolak kalau babak berikutnya sudah dinilai atau hasilnya sedang publik; peserta baru mengosongkan daftar peserta babak sesudahnya (yang belum dinilai).
- Data lokal: babak lama "Kejuaraan LKBB Nusantara Piala Garuda" dihapus, 10 peserta dummy ditambah, nilai Babak I diisi untuk kedua juri.
- 504 test lolos, 5 skipped.

---

## 2026-11-17 — Celah keamanan, konfirmasi SweetAlert, peringkat tunggu semua juri

**Diselesaikan:**
- Celah: teks `confirm()` inline rusak (dan bisa XSS) kalau nama mengandung tanda kutip; juri masih bisa kirim nilai setelah hasil dipublikasikan (`assertOpen()`); ganti kode akses sekarang mengeluarkan sesi juri yang masuk dengan kode lama (fingerprint kode di sesi). Batas login juri dinaikkan ke 10×/menit per IP (juri satu venue berbagi IP).
- Semua 24 konfirmasi pakai modal ala SweetAlert global (`resources/js/confirm-dialog.js`, atribut `data-confirm*` di form/tombol). Fokus awal di Batal.
- Publikasikan: popup menyebut lembar yang belum dikirim dan peserta yang belum dinilai semua juri; notifikasi sukses menyebut jumlah peserta yang diperingkat.
- "Belum diperingkat": ringkasan per juri, satu baris per peserta, 5 pertama lalu "Tampilkan semua" (scroll di dalam kartu).
- **Aturan peringkat diubah:** peserta baru diperingkat setelah semua juri yang ditugaskan mengirim/mengunci lembarnya (SRS Keputusan D). Hasil yang sudah tersimpan baru berubah saat dihitung ulang/dipublikasikan ulang.
- Link hasil publik per kategori: `/info-lomba/{slug}/hasil/{category}` (id kategori; 404 kalau bukan milik lomba itu atau belum punya babak). Halaman publik punya pilihan kategori; blok "Link hasil publik" di admin menampilkan link semua kategori + satu per kategori. Script tombol tema di halaman hasil tidak error lagi kalau tombolnya tidak ada.
- Admin penjurian: tab kategori di bawah tab utama, berlaku di keempat tab (`?kategori=id`, ikut terbawa saat pindah tab dan setelah simpan). Form tambah peserta/babak memakai kategori aktif.
- Halaman hasil publik: satu babak per kategori dengan pilihan babak; default babak terakhir yang dipublikasikan; link per babak `/hasil/{category}/{round}`. Pilihan kategori di halaman publik sengaja dikomentari (oleh user) dan dibiarkan.
- **Peserta per babak:** babak pertama = semua peserta kategori; babak berikutnya dipilih admin (manual atau "ambil N teratas dari babak sebelumnya", seri di batas ikut). Tabel baru `competition_round_participants` (+ backfill dari lembar yang sudah ada). Keluarkan peserta ditolak kalau sudah dinilai. Juri, peringkat, lembar nilai, dan "Belum diperingkat" hanya memakai peserta babak itu (SRS Keputusan I).
- Tab Lembar Nilai dan Hasil: babak yang sudah dipublikasikan tampil ringkas (Lembar: lembar terkirim/dikunci; Hasil: 3 besar), "Tampilkan detail" untuk membuka. Babak yang masih berlangsung tetap terbuka penuh. Tab Babak & Juri tidak diringkas, hanya diberi label "Selesai · hasil diumumkan".
- Seeder baru `CompetitionMultiRoundDemoSeeder` (Garuda Muda Cup: Grup → Penyisihan → Final per kategori).
- Seeder baru `CompetitionManyParticipantsDemoSeeder` (SMP Garuda Muda Cup jadi 44 peserta).
- 499 test lolos, 5 skipped.

**Catatan:** Modul juri lama (`/judge`) kemungkinan punya celah sesi yang sama (kode akses diganti, sesi tetap jalan); belum disentuh.

---

## 2026-11-17 — Kriteria terkunci total setelah penilaian dimulai

**Diselesaikan:**
- Nomor tampil wajib saat menambah peserta (form dan validasi server). Form edit tidak berubah.
- Kartu kriteria: setelah juri mulai menilai, tombol Edit disembunyikan dan kriteria hanya tampil sebagai info. Server menolak semua perubahan kriteria, termasuk nama dan keterangan yang sebelumnya masih boleh. Teks keterangan, test, SRS, dan panduan disesuaikan.
- 489 test lolos, 5 skipped.

---

## 2026-11-17 — Halaman hasil publik pakai desain judge-mobile

**Diselesaikan:**
- `/info-lomba/{slug}/hasil` dibuat ulang sebagai halaman mandiri mengikuti `public_results_screen.dart`: hanya logo dan nama Siapgrak, warna dashboard admin (bar atas navy, konten terang, aksen merah) dengan tombol mode gelap opsional, kartu peserta (peringkat/SERI, kotak nomor, #nomor, nama, chip total juri, nilai akhir), tabel kriteria × juri berbobot dengan "maks", dan kartu "masih berlangsung" berikon.
- Dicek di desktop dan HP, mode gelap dan terang. 488 test lolos.

---

## 2026-11-17 — Kendala penjurian diperjelas di layar

**Diselesaikan:**
- Juri: "Kirim Skor Final" langsung menyimpan dan mengirim, nonaktif sampai semua kriteria terisi (dengan jumlah yang kurang). Kode akses menerima huruf kecil, spasi, dan tanda hubung.
- Admin:
  - Daftar cek kesiapan per babak di tab Lembar Nilai (bobot, juri, peserta), dan catatan saat menugaskan juri ke babak yang belum siap.
  - Daftar "Belum diperingkat" beserta alasan dan juri yang ditunggu di tab Hasil.
  - Salin Link nonaktif selama Info Lomba draft; Buka ulang tampil nonaktif selama hasil publik.
- Panduan `_docs/operations/alur-info-lomba-penjurian.md` disesuaikan.
- 4 test baru. Total 488 lolos, 5 skipped.

---

## 2026-11-17 — Halaman peringkat publik & salin link

**Diselesaikan:**
- Halaman publik `/info-lomba/{slug}/hasil` mengikuti prototipe judge-mobile: peringkat, nomor, nama, total per juri (anonim), nilai akhir, dan rincian kriteria × juri.
- Publikasi per babak (migrasi `2026_11_17_090001`, `competition_rounds.results_published_at`). Sebelum dipublikasikan, halaman menampilkan "masih berlangsung". Publikasi menghitung ulang dan ditolak kalau belum ada nilai lengkap. Hasil bisa ditarik lagi. Selama publik, lembar nilai babak itu tidak bisa dibuka ulang.
- Tombol "Salin Link" di tab Lembar Nilai dan Hasil, muncul kalau sudah ada lembar terkirim, dengan peringatan kalau Info Lomba masih draft. Kalau Clipboard API gagal, sistem memakai `execCommand`, lalu meminta Ctrl+C.
- Tautan "Lihat hasil penilaian" di detail Info Lomba publik kalau sudah ada babak yang diumumkan.
- Demo: hasil Babak Final SMA "LKBB Garuda Muda Cup" sudah dipublikasikan untuk pengecekan.
- 4 test baru. Total 484 lolos, 5 skipped.

**Keputusan user:** nilai tampil publik setelah admin mempublikasikan, bukan langsung (live), sesuai CLAUDE.md.

---

## 2026-11-16 — Bobot kriteria wajib 100% dan terkunci setelah penilaian dimulai

**Diselesaikan:**
- Juri baru bisa menilai kalau total bobot babak tepat 100% dan semua kriteria berbobot. Bobot sekarang wajib diisi.
- Setelah ada lembar nilai di sebuah babak, kriteria tidak bisa ditambah atau dihapus, dan bobot serta rentang nilai tidak bisa diubah. Nama dan keterangan masih boleh.
- Admin melihat status per babak: "Belum siap · bobot X% dari 100%", "Siap dinilai", atau "Penilaian berjalan · kriteria terkunci". Kolom bobot kriteria baru otomatis terisi sisa bobot.
- Juri melihat pemberitahuan "Babak ini belum bisa dinilai" dan tidak mendapat form nilai.
- 3 test baru. Total 480 lolos, 5 skipped.

**Alasan:** menambah kriteria berbobot 20% setelah total 100% membuat nilai akhir berskala 120, dan peserta dengan lembar yang sudah terkirim hilang dari peringkat karena tidak punya nilai untuk kriteria baru.

---

## 2026-11-16 — Penjurian pindah ke Info Lomba (tabel terpisah)

**Diselesaikan:**
- Modul penjurian baru yang terhubung ke `competition_listings`, dengan 10 tabel baru dan detail di `_docs/srs/fr-competition-judging.md`. Modul event lama tidak diubah.
- Ruang kerja admin `/dashboard/content/competitions/{id}/judging` dengan tab Kategori & Peserta, Babak & Juri, Lembar Nilai (matriks peserta × juri, kunci, buka ulang dengan alasan), dan Hasil. Tombol "Penjurian" ada di detail Info Lomba, tab "Juri" di menu konten.
- Panel Juri baru di `/juri` (login kode akses).
- Seeder `CompetitionJudgingDemoSeeder`: demo lengkap di "LKBB Garuda Muda Cup" (milik admin1, juri `GRDA2345`/`GRDB3456`/`GRDC4567`) dan demo kecil di "Lomba Contoh Milik Admin 2" (juri `ADMN2345`). Sudah dijalankan di DB lokal.
- Info Lomba yang sudah punya data penjurian tidak bisa dihapus.
- 9 test baru. Total 477 lolos, 5 skipped.

**Perubahan yang perlu diketahui:** prefix panel juri lama di `routes/web.php` sempat diubah ke `juri`, sehingga bentrok dengan panel baru. Prefix itu dikembalikan ke `judge`; `/juri` sekarang milik panel penjurian Info Lomba.

**Belum:** halaman event lama (`/dashboard/events/{id}`) tidak dipercantik karena data event diabaikan dulu. Fungsinya sudah digantikan ruang kerja penjurian di Info Lomba.

---

## 2026-11-16 — Data demo penjurian & tampilan Panel Juri

**Diselesaikan:**
- Seeder baru `JudgingDemoSeeder`: organisasi "Panitia Demo LKBB Garuda" (admin platform pertama owner, sisanya admin), event "LKBB Garuda Muda Cup (Demo)" berstatus ongoing, 2 kategori, 10 tim dan peserta bernomor tampil, babak final dengan 4 kriteria berbobot, dan 3 juri dengan kode tetap (`JURYA234`, `JURYB345`, `JURYC456`). Juri ketiga hanya ditugaskan ke kriteria Kepemimpinan Danton. Lembar nilai dibuat campuran draft, terkirim, dan dikunci. Sudah dijalankan di DB lokal (cadangan disimpan dulu).
- Panel Juri (`/judge`) mengikuti gaya baru (navy-merah, ramah HP):
  - halaman masuk dengan input kode besar;
  - beranda berisi progres per babak;
  - daftar peserta dengan nomor tampil, status tampil berbahasa Indonesia, dan chip status lembar nilai (Belum dinilai / Draft x/y / Terkirim / Dikunci);
  - form nilai dengan input angka besar, bobot, deskripsi kriteria, catatan yang bisa dilipat, dan bar aksi tetap di bawah.
- Pengaman baru: "Kirim Skor Final" ditahan kalau masih ada ketikan yang belum disimpan sebagai draft. Sebelumnya ketikan itu hilang diam-diam karena kirim memakai form terpisah.
- `ScoreSheetService`: `entriesForRound` menyertakan `sheet_status`, `assignedRounds` menyertakan `entries_count` dan `sent_count`. `label()` Indonesia di `CompetitionEntryStatus` dan `ScoreSheetStatus`. Tab halaman event memakai warna brand.

**Catatan:**
- Halaman panitia untuk penjurian (`/dashboard/events/{id}` tab Penjurian, `/dashboard/judges`) tidak ada di menu sejak Events diarahkan ke konten, jadi hanya bisa dibuka lewat URL. Label status di sana masih bahasa Inggris (Submitted, Active, dan seterusnya).
- Membuka halaman peserta di Panel Juri otomatis membuat lembar nilai draft kosong (perilaku lama).

---

## 2026-11-16 — Pagination info lomba, infinite scroll & poster agenda

**Diselesaikan:**
- `/info-lomba`: 8 per halaman dengan pagination.
- `/agenda`: tampil 8 dulu, lalu 8 berikutnya dimuat hanya setelah pengunjung scroll (scroll, wheel, sentuh, atau keyboard, jadi tetap jalan di layar yang tidak bisa di-scroll). Tanpa tombol atau link pagination, sesuai permintaan. Kalau gagal memuat, scroll berikutnya mencoba lagi (`site/partials/infinite-scroll-script.blade.php`).
- View pagination berbahasa Indonesia (`partials/pagination.blade.php`) dipakai di situs publik dan daftar konten admin. Bawaan Laravel berbahasa Inggris dan ikut mode gelap perangkat. `APP_LOCALE` sengaja tidak diubah ke `id` karena folder `lang/` belum ada.
- Agenda punya poster 4:5: migrasi `2026_11_16_090001` (`agenda_items.cover_path`), upload di form admin (validasi rasio 4:5, file dihapus ikut agenda), dan detail agenda publik dengan poster di kiri dan kartu info di kanan.
- Seeder baru `PaginationDemoSeeder` (create-only): 6 lomba dan 12 agenda dengan poster 4:5 bergambar (gradien, pita merah-putih, judul, tanggal). Sudah dijalankan di DB lokal, cadangan DB disimpan sebelumnya.
- 4 test baru. Total 468 lolos, 5 skipped.

---

## 2026-11-16 — Audit halaman, tampilan publik, pencarian, halaman error

**Audit:** semua halaman publik, auth, dan konten dashboard dicek di desktop dan HP (error JS, gambar rusak, scroll horizontal, status HTTP, parameter aneh).

**Bug yang diperbaiki:**
- `?q[]=x`, `?jenjang[]=x`, dan `?tipe[]=x` membuat error 500. Sekarang ditangani `Controller::queryText()`, yang hanya menerima string.
- Di HP, baris agenda melebar keluar layar (item grid tanpa `min-w-0`).
- 403/404/419/429/500/503 masih halaman bawaan Laravel berbahasa Inggris. Sekarang ada `resources/views/errors/*` berbahasa Indonesia, dengan layout yang berdiri sendiri (tidak butuh DB/auth).

**Tampilan:**
- Warna brand diambil dari logo (`brand-*` merah, `navy-*`) di `app.css`, menggantikan biru `sky` di situs publik, shell dashboard, login, dan tombol utama.
- Header sticky dengan garis merah, menu aktif bergaris merah, dan footer navy berisi link dan Instagram.
- Beranda: hero dengan kotak cari, jumlah lomba yang masih buka dan agenda mendatang, poster lomba terdekat, dan garis merah-putih ala poster.
- Kartu lomba, agenda, dan kabar: tanggal agenda bergaya kalender, status pendaftaran memakai titik, efek hover.
- Teks di beranda, intro halaman, empty state, dan tombol pendaftaran ditulis ulang.

**Pencarian:**
- `/agenda?q=` (judul, lokasi, deskripsi) dan `/kabar?q=` (judul, ringkasan, isi), otomatis seperti `/info-lomba`.
- Panel admin Info Lomba, Agenda, dan Kabar: kotak cari, dengan `withQueryString` di pagination.
- Scope bersama `search()` di `IsPublicContent`.

**Test:** 4 test baru (pencarian publik, parameter array, 404 Indonesia, pencarian admin). Total 464 lolos, 5 skipped.

**Perlu keputusan:**
- `<title>` di layout publik dan dashboard sengaja di-comment jadi "Siapgrak" saja, sehingga semua tab sama. Disarankan dikembalikan per halaman (SEO dan pengalaman berbagi link).
- User yang login dialihkan dari `/` ke dashboard, jadi admin tidak bisa melihat beranda publik.
- Link "Daftar" di halaman login membuka registrasi untuk siapa saja. Akun baru hanya melihat dashboard kosong.
- Poster demo berupa gradien polos. Ganti dengan poster asli sebelum demo.

---

## 2026-11-13 — Body lomba 9 & 10 tertimpa seeder, dipulihkan

**Kejadian:** editan body manual (lewat DB Browser) di `competition_listings` id 9 dan 10 hilang. Penyebabnya: pada 2026-11-10 `InstagramCompetitionSeeder` dan `PublicContentDemoSeeder` dijalankan ulang untuk mengetes perbaikan `orderBy('id_user')`, dan keduanya menimpa record yang judulnya sama (`firstOrNew` + `forceFill`).

**Diselesaikan:**
- Body id 9 dan 10 dipulihkan dari cadangan DB sebelum migrasi 2026-11-10. Kolom lain, agenda, dan kabar dicek tidak ikut berubah (selain tanggal data demo yang memang relatif terhadap hari ini).
- Kedua seeder sekarang hanya membuat record yang belum ada dan tidak pernah menimpa data yang sudah ada. Sudah diuji pada salinan DB.

**Catatan:** seeder tidak lagi memperbarui tanggal data demo saat dijalankan ulang. Untuk data demo yang segar, hapus dulu record-nya.

---

## 2026-11-10 — Primary key `users` jadi `id_user`, logo dashboard, menu Events = konten

**Diselesaikan:**
- Migrasi `2026_11_10_090001`: `users.id` di-rename menjadi `id_user`. FK di tabel lain tetap bernama `user_id`/`created_by`/dst, hanya referensinya yang ikut pindah ke `users.id_user` (MySQL 8 dan SQLite otomatis).
- `User`: `$primaryKey = 'id_user'`, `getForeignKey()` tetap `user_id`. Tidak ada alias `id`, jadi `$user->id` kini null.
- Semua pemakaian id user diganti ke `id_user`: query mentah `users.id` (`StoreEventStaffRequest`, `EventController@show`), policy (Event/Judge/Organization/Registration/Team/PublicContent), service (Event/Organization/Registration), controller (OrganizationMember, ScoreSheet, konten), scope `ownedBy`, view `staff.blade.php`, dua seeder konten, dan 48 baris test. Daftarnya dikumpulkan dengan mencatat setiap akses `$user->id` selama seluruh test berjalan, lalu dicek ulang sampai nol.
- Logo Siapgrak di sidebar desktop dan topbar mobile dashboard.
- 462 test hijau (MySQL), Pint lolos. DB lokal sudah dimigrasi, login dan pemisahan data admin2 dicek di browser.
- Menu dashboard (permintaan user, berlaku untuk semua user): **Events** sekarang membuka halaman konten (Info Lomba / Agenda / Prestasi & Kabar), menu "Konten" terpisah dihapus (desktop dan menu Lainnya di HP). Link ke modul event panitia lama (`dashboard.events.*`) dan **Teams** di-comment di sidebar dan nav HP; route dan kodenya tetap ada. Judul halaman konten jadi "Events".
- `/dashboard` untuk semua user: view baru `dashboard/content-overview.blade.php`, berisi ringkasan konten milik user yang login (lomba tayang/draft, pendaftaran dibuka, agenda mendatang, prestasi & kabar), 5 lomba terdekat, dan 5 agenda terdekat. Tombol "+ Tambah Info Lomba" hanya untuk admin platform. Dashboard organisasi lama (`dashboard.index`) di-comment di `DashboardController`, view-nya tidak dihapus, dan 5 test-nya di `IndexTest` ditandai skipped.
- Test baru `tests/Feature/Dashboard/ContentOverviewTest.php` (3 test). Test lama "link Konten hanya untuk admin" diganti jadi "menu Events menuju konten untuk semua user". Total 465 test hijau.

**Keputusan yang perlu dikonfirmasi Ersa:** CLAUDE.md menetapkan sidebar `Dashboard / Events / Teams / Organization / Settings` dengan Events = modul event panitia. Sekarang Events = konten publik dan Teams disembunyikan. User organizer non-admin yang klik Events akan mendapat 403.

**Catatan:** sesi login yang aktif sebelum migrasi mungkin perlu login ulang. Kode baru wajib memakai `$user->id_user` atau `$user->getKey()`; `$user->id` mengembalikan null tanpa error.

---

## 2026-11-09 — Info lomba: poster 4:5, filter, contact person

**Diselesaikan:**
- Migrasi baru `2026_11_09_090001`: `competition_listings.levels` (json), `province`, `registration_deadline`, `contact_persons` (json). Migrasi lama tidak diubah.
- Form admin: poster wajib 4:5, jenjang (checkbox), provinsi, batas pendaftaran, 3 slot contact person. "Link Info Resmi" diganti "Link Pendaftaran".
- `/info-lomba`: filter kata kunci, jenjang, provinsi, dan "pendaftaran dibuka". Kartu ringkas 4:5, detail lengkap dengan tombol contact person ke WhatsApp. Beranda menampilkan 4 lomba.
- Seeder demo ikut diisi kolom baru dan poster 4:5, lalu dijalankan di database lokal.
- Pendaftaran tutup: tombol link/Kreenconnect/contact person disembunyikan, diganti info "Pendaftaran sudah ditutup".
- Filter `/info-lomba` berjalan otomatis (dropdown/checkbox langsung, pencarian setelah 500 ms berhenti mengetik); tombol Cari hanya tampil tanpa JavaScript. Semua `<select>` di aplikasi memakai panah sendiri dengan jarak `pr-10` (`app.css`, `x-ui.select`, status kategori di workspace event).

- `InstagramCompetitionSeeder` (terpisah dari demo fiktif): LKBB Puncak Berbaris Jilid 1 dan LKBB Mirovement Season 7, caption disalin persis dari screenshot feed siapgrak.id. Poster dipotong dari screenshot dan disimpan di `database/seeders/assets/instagram/`.
- Migrasi `2026_11_09_090002`: `technical_meeting_date` + `technical_meeting_location` (opsional). Di detail publik baris "Technical Meeting" hanya tampil kalau tanggalnya terisi.
- Kepemilikan konten: migrasi `2026_11_09_090003` mengganti nama `created_by` → `id_user` di `competition_listings`, `agenda_items`, `community_posts`. List admin hanya menampilkan konten milik user yang login; akses ke milik admin lain 403 (`PublicContentPolicy`). Seeder mengisi `id_user` dengan admin platform pertama.
- Lokal saja: user `admin2@admin.com` (password `password`, admin platform) dengan 1 lomba, 1 agenda, 1 kabar untuk cek pembeda data. `users.id` sengaja tidak diganti nama (menunggu keputusan).
- Logo siapgrak.id (dipotong dari screenshot profil Instagram, ±460 px) di `public/images/`: header situs publik, halaman login/register, dan favicon semua layout (`partials/head-icons`).

**Blocker:** Instagram tidak bisa diakses dari sesi ini, jadi konten diambil dari screenshot yang dikirim tim. Logo dan poster masih resolusi screenshot; ganti dengan file asli kalau tersedia.

**Verifikasi:** 459 feature test hijau di MySQL · Pint pass · screenshot desktop dan mobile (390px, tanpa scroll horizontal).

---

## 2026-11-06 — Detail info lomba di panel admin

**Diselesaikan:**
- Halaman baru `dashboard.content.competitions.show`. Row tabel dan kartu mobile di list info lomba bisa diklik untuk membukanya; link dan tombol aksi di dalam row tetap berjalan sendiri.
- Judul di list sekarang mengarah ke detail, bukan ke form edit.

**Tidak diubah:** list agenda dan kabar komunitas.

---

## 2026-11-06 — Warna dashboard mengikuti beranda + perbaikan padding input

**Diselesaikan:**
- Sidebar, topbar mobile, dan halaman auth memakai warna gelap `slate-900` seperti hero beranda, dengan aksen `sky`. Tombol utama ikut `slate-900`.
- Aturan dasar field di `resources/css/app.css`. Root cause: input manual (login, register, reset password, organisasi) tidak punya class padding/lebar border, sehingga tampil tanpa keduanya setelah reset Tailwind.
- Padding field standar naik ke `px-3.5 py-2.5`. Field ringkas dengan padding sendiri tidak berubah.
- `admin@admin.com` di database lokal dijadikan admin platform.

**Tidak diubah:** header area juri (`layouts/judge`).

**Verifikasi:** Pint pass · 448 feature test hijau di MySQL · dicek lewat screenshot (list dan form admin, organisasi, login, tampilan mobile).

---

## 2026-11-05 — Halaman Utama Publik + Konten Admin Platform (ADR-003)

**Progress:** Fitur selesai dan terverifikasi. Belum di-commit.

**Diselesaikan:**
- Beranda publik di `/` dengan halaman list dan detail untuk Info Lomba, Agenda, serta Prestasi & Kabar.
- Panel admin di `/dashboard/content`, dengan menu "Konten" yang hanya tampil untuk admin platform.
- Kolom `users.is_platform_admin` + command `siapgrak:platform-admin`.
- Flag `is_managed` + `kreen_slug` pada Info Lomba, dengan tombol ke Kreenconnect. Tidak ada data transaksi yang disimpan.
- ADR-003 dan `srs/fr-public-content.md`.

**File lama yang diubah:** `routes/web.php` (route `/`), `sidebar.blade.php` dan `account-menu.blade.php` (menu Konten), `AuthenticationTest` (perilaku `/` untuk tamu), `.env.example`.

**Verifikasi:** Pint pass · 448 feature test hijau di MySQL (port 8065) dan SQLite · dicek lewat screenshot Chrome headless (beranda desktop dan mobile, detail, list dan form admin).

**Keputusan/blocker:**
- `KREENCONNECT_EVENT_BASE_URL` belum diketahui. Sampai diisi, tombol daftar tidak tampil.
- `CLAUDE.md` masih memuat larangan halaman publik dan aturan sidebar lama. Perlu diselaraskan dengan ADR-003 oleh Ersa.

**Next steps:** jalankan setup di `srs/fr-public-content.md`, lalu commit.

---

## 2026-09-16 — Frontend F12 (Responsive + UX Audit) — FRONTEND F0-F12 TUNTAS 13/13 STEP

**Progress:** **Frontend F0-F12 SELESAI SEMUA** (13/13 step). Lanjutan langsung dari F11 di sesi yang sama, dipilih atas instruksi "lanjut" — F12 adalah step TERAKHIR di build order.

**Keterbatasan metodologi, dinyatakan eksplisit (bukan diam-diam diabaikan):** dicek lewat pencarian tool — tidak ada browser/screenshot tool apa pun di environment sesi ini. F12 dikerjakan sebagai **audit struktural kode** (kelas Tailwind, atribut Alpine, ukuran tap target, konsistensi pola responsive lintas file) via curl+grep+test PHPUnit yang mengecek HTML yang dirender — BUKAN review visual piksel-demi-piksel. Ini konsisten instruksi CLAUDE.md sendiri: "kalau tidak bisa test UI, katakan eksplisit, jangan klaim berhasil."

**3 temuan nyata, semua diperbaiki:**
1. **Drawer/modal tidak mengunci scroll background** — `components/ui/drawer.blade.php`+`modal.blade.php` (dipakai F6/F7/F8/F9) tidak pernah mencegah halaman DI BELAKANG ikut ter-scroll selagi terbuka — bug UX mobile umum. **Fix:** `x-effect="document.body.classList.toggle('overflow-hidden', open)"` di 1 komponen bersama — otomatis berlaku ke SEMUA pemakaian drawer/modal di app, dampak ke 4 sprint sekaligus dari satu perbaikan.
2. **Tap target terlalu kecil** — tombol ↑/↓/✕ reorder-jadwal (`p-1`, dipakai berulang saat reorder jadwal hari-H di HP) dan tombol tutup drawer/modal (nol padding di sekitar ikon 20px) di bawah ukuran nyaman disentuh. **Fix:** diperbesar ke `p-2` (tombol tutup pakai `-m-2 p-2` — margin negatif meng-offset padding supaya tata letak visual tidak berubah, cuma area sentuhnya yang membesar).
3. **6 grid form 2-kolom melanggar aturan responsive project sendiri** — `frontend-plan.md` §3 eksplisit: "Form multi-kolom → 1 kolom di mobile," dan SEMUA form lain di app sudah benar. Tapi drawer F7 (min/max anggota, rentang tanggal) dan F9 (min/max skor, rentang tanggal) yang baru dibangun sesi ini pakai `grid-cols-2` polos tanpa fallback mobile — ditemukan lewat grep sistematis, bukan tebakan. **Fix:** diseragamkan `grid-cols-1 gap-3 sm:grid-cols-2`, persis pola yang sudah benar di file yang sama.

**Yang dicek tapi TIDAK jadi temuan** (dicatat transparan, bukan dilewati diam-diam): semua `<x-ui.table>` sudah `overflow-x-auto` otomatis sejak F0; tabel Hasil (raw table) sudah dibungkus manual; grid stat card F2 Dashboard (2-kolom di mobile) sengaja begitu — itu display KPI read-only, bukan form input; semua halaman list baru (F2/F6/F7/F8/F9/F11) sudah punya empty state.

**Diselesaikan:**
- `components/ui/{drawer,modal}.blade.php` — scroll-lock + tap target tombol tutup.
- `events/partials/{schedule,categories,judging}.blade.php` — tap target + grid mobile-first.
- **4 test baru** (`Event/ResponsiveAuditTest`): scroll-lock hadir, tap target drawer+schedule diperbesar, grid form F7 mobile-first.

**Verifikasi:** Pint `--test` pass · **415 test hijau** (411 + 4 baru) · manual end-to-end via curl: keempat perbaikan terkonfirmasi hadir di HTML mentah halaman event nyata · 3 run suite penuh berturut-turut bersih 415/415.

**FRONTEND F0-F12 TUNTAS — 13/13 STEP SELESAI.** Bersamaan dengan backend Sprint 1-13 + 4b yang sudah tuntas sebelumnya, kedua track (frontend + backend) sekarang SAMA-SAMA SELESAI per rencana masing-masing. Sisa kerja lanjutan yang benar-benar terbuka (tidak masuk F0-F12 maupun Sprint 1-13+4b): verifikasi visual manual oleh Ersa langsung di browser (disarankan sekali sebelum dipakai user asli, mengingat sesi ini sama sekali tidak punya akses browser visual) · `backup-restore.md` (belum dibuat) · keputusan platform hosting/runtime production · commit + push ke remote (belum pernah diminta sepanjang sesi ini).

---

## 2026-09-16 — Frontend F11 (Results) — cross-check spec + bug lintas-tab yang mempengaruhi F7-F9 sekaligus ditemukan+diperbaiki

**Progress:** Frontend **F0/F1/F2/F3/F4/F5/F6/F7/F8/F9/F10/F11 selesai** (12 dari 13 step — tinggal F12 audit). Lanjutan langsung dari F9 di sesi yang sama, dipilih atas instruksi "lanjut" — F11 adalah item berikutnya di build order numerik.

**Cross-check hasilnya:** ranking table ✓, Finalize ✓, Publish (muncul cuma setelah finalized) ✓ — semua sudah persis sesuai spec sejak Sprint 11-12, tidak perlu perubahan. Satu gap literal: spec sebut tombol "[Review Scores]", belum ada. Ditambahkan sebagai "Review Skor Juri" — lompat ke tab Penjurian (sudah py list Lembar Skor per-round dengan status+link detail sejak Sprint 9-10) lewat Alpine `@click="tab = 'judging'"`, bukan duplikasi list itu di tab Hasil.

**Bug kedua ditemukan saat cross-check (di luar checklist spec, tapi nyata):** form set-penalti per baris hasil TIDAK PERNAH punya `@error('penalty')` — submit penalti tidak valid (mis. negatif) gagal TOTAL DIAM-DIAM dari sudut pandang user. Karena tiap baris hasil punya `<form name="penalty">` sendiri-sendiri, fix naif akan bocor ke semua baris — hazard yang sama F7/F8/F9. **Fix:** hidden field `_result_id` + guard yang sama seperti pola lain.

**Bug KETIGA, paling signifikan — ditemukan saat verifikasi manual, ternyata mempengaruhi F7/F8/F9 SEKALIGUS sejak dibangun:** submit penalti gagal → redirect → halaman selalu reload ke tab **Overview**, bukan tab tempat form-nya berada (`events/show.blade.php` hardcode `x-data="{ tab: 'overview' }"`). Drawer/error yang sudah benar dihitung oleh fix F7 (Categories)/F8 (Schedule)/F9 (Judging) — `open`/`editing` correctly computed — TETAP TERSEMBUNYI di balik panel tab yang tidak aktif. Dari sudut pandang user: submit gagal, halaman reload, Overview tampil seolah tidak terjadi apa-apa sama sekali. Ini bug yang SUDAH ADA sejak F7 dibangun, baru ketahuan sekarang karena verifikasi manual F11 kebetulan memicunya.

**Root cause + fix:** tiap form yang relevan (Categories, Schedule, Judging, Results, Staff, dan Competition dari Sprint 5 — ikut ditutup demi konsistensi walau bug-nya sudah ada sejak awal) sekarang bawa hidden field `_tab`. `events/show.blade.php` baca `old('_tab')` untuk tab aktif awal — **di-whitelist ketat** terhadap 8 nama tab valid sebelum diinterpolasi ke `x-data="{ tab: '...' }"`, karena `old()` tidak divalidasi FormRequest manapun, dan menaruhnya mentah di dalam string Alpine ber-kutip-tunggal adalah celah JS-string-breakout nyata (HTML-entity escaping Blade TIDAK menutup ini — browser decode entity sebelum Alpine parse atribut sebagai JS). **Diverifikasi eksplisit dengan payload injeksi** (`_tab` = `x'}); alert(1); ({tab:'overview`) — terkonfirmasi jatuh ke `'overview'`, tidak pernah tembus mentah.

**Diselesaikan:**
- `events/show.blade.php` — tab aktif dari hardcoded 'overview' jadi whitelist `old('_tab')`.
- `partials/{results,categories,schedule,judging,staff,competition}.blade.php` — `_tab` ditambahkan ke semua form relevan.
- `partials/results.blade.php` — +tombol Review Skor Juri, +isolasi error penalti per baris.
- **9 test baru**: `Event/TabRestorationTest` (6 — default overview, restore ke 4 tab berbeda, whitelist menolak payload injeksi), `Result/ReviewAndPenaltyTest` (3 — tombol Review Skor, error penalti tampil, error tidak bocor ke baris lain).

**Verifikasi:** Pint `--test` pass · **411 test hijau** (402 + 9 baru) · setiap fix diverifikasi ketat dengan cara yang sama sejak F7 — sengaja di-broke sementara, konfirmasi test gagal seperti diharapkan, dikembalikan · manual end-to-end via curl: submit penalti negatif → redirect → halaman reload PERSIS ke tab Hasil (bukan Overview) dengan pesan error "Penalti tidak boleh negatif." tampil, payload injeksi `_tab` terkonfirmasi jatuh ke `'overview'` · 3 run suite penuh berturut-turut bersih 411/411.

**Next steps:** frontend **F12** — satu-satunya step tersisa: adversarial responsive+UX audit menyeluruh F0-F11 (semua sekarang punya konten nyata, bukan lagi placeholder) · commit + push ke remote (belum pernah diminta sepanjang sesi).

---

## 2026-09-16 — Frontend F9 (Judging Setup) — readiness checklist + kriteria jadi card grid+drawer

**Progress:** Frontend **F0/F1/F2/F3/F4/F5/F6/F7/F8/F9/F10 selesai** (11 dari 13 step). Lanjutan langsung dari F8 di sesi yang sama, dipilih atas instruksi "lanjut" — F9 adalah item berikutnya di build order numerik.

**Dua deliverable spec, keduanya dikerjakan:**
1. **Readiness checklist per kategori** — "✓/! Round configured · N kriteria · N juri", dihitung dari relasi yang sudah eager-loaded (nol query baru).
2. **Criteria Builder jadi card grid** (bukan `<ul>` list polos seperti sebelumnya) dengan subcriteria bersarang di dalam card yang sama, plus **aksi "Edit" lewat drawer yang tadinya sama sekali tidak ada** — `JudgingCriteriaController::update()` sudah ada+teruji sejak Sprint 7, ini gap KETIGA dengan pola persis sama F7 (Categories)/F8 (Schedule): endpoint ada, UI tidak pernah dibangun.

**Keputusan scope:** item ke-4 di contoh spec sketch ("! 1 judge has no assignment") TIDAK dibangun apa adanya — ditulis sebelum model `JudgeAssignment` (Sprint 8) final, dan begitu "N juri" dihitung dari assignment yang benar-benar ada, "juri tanpa assignment" jadi tidak well-defined lagi (kalau seorang juri terhitung sebagai bagian dari N, artinya dia MEMANG punya assignment). 3 metrik yang well-defined dipertahankan (Round/Kriteria/Juri), metrik ke-4 yang ambigu tidak dipaksakan — konsisten prinsip "jangan mengarang business rule yang datanya tidak ada" yang sudah dipakai berulang di F2/F5/F6.

**Bug arsitektur F7/F8 muncul lagi, dengan 1 lapis kompleksitas baru:** tab Penjurian bisa punya BEBERAPA round per kategori, dan tiap round punya form "Tambah Kriteria" sendiri-sendiri — F7/F8 cuma pernah punya SATU form create per halaman, jadi diskriminator `null` (vs id kriteria) sudah cukup untuk isolasi `old()`/`$errors`. Di sini, `null` saja jadi ambigu (round MANA yang gagal?) — solusinya nambah hidden field `_round_id` di samping `_criterion_id`, jadi form create tiap round bisa dibedakan dari form create round lain di halaman yang sama. **Diverifikasi ketat dengan cara yang sama seperti F7/F8**: `errorKey` sengaja di-broke sementara → dikonfirmasi 3 dari 6 test regresi baru memang gagal seperti diharapkan → dikembalikan ke fix yang benar.

**Diselesaikan:**
- `EventController::show()` — +`judgingReadinessFor()` per kategori.
- `events/partials/judging.blade.php` — kriteria jadi card grid + drawer. Rounds/Judge Assignment/Score Sheet TIDAK disentuh (di luar scope F9, tetap seperti Sprint 7-13).
- **6 test baru** (`JudgingCriteria/CriteriaDrawerTest`): checklist kosong vs lengkap, edit+prefill per kriteria, edit kriteria A tidak bocor ke B, create round A tidak bocor ke round B, edit tidak bocor ke form create manapun.

**Verifikasi:** Pint `--test` pass · **402 test hijau** (396 + 6 baru) · manual end-to-end via curl: checklist tampil "✓ Round configured · ✓ 2 kriteria · ✓ 1 juri" persis sesuai fixture, card kriteria menampilkan subcriteria bersarang dengan bobot benar, tombol Edit membuka drawer ter-prefill nilai sungguhan dari DB.

**Bonus — 2 flake pre-existing yang sudah disebut berkali-kali sepanjang sesi ("flake ScheduleSlot") akhirnya di-root-cause+diperbaiki beneran** (muncul lagi selama verifikasi F9, kali ini diselidiki tuntas bukan cuma dicatat ulang):
- **`ManageScheduleTest::test_owner_can_add_a_slot_appended_to_the_end`**: `EventScheduleSlotFactory`'s title default random dari `['Opening Ceremony', 'Technical Meeting', 'Break', null]` — 25% kemungkinan SAMA PERSIS dengan literal yang di-filter test (`where('title', 'Opening Ceremony')`). Kalau slot fixture (tanpa override title) kebetulan dapat "Opening Ceremony", `firstOrFail()` match 2 baris dan ambil yang SALAH (baris lama, bukan yang baru di-POST) — kelihatan seperti bug `display_order`, padahal salah ambil baris. **Fix:** kasih title eksplisit non-collide di fixture test (`'Existing Slot'`), bukan ubah factory.
- **`EventCategoryFactory`'s `code`**: `bothify('CAT-##')` cuma 100 kemungkinan, collision birthday-paradox nyata di test yang bikin beberapa kategori untuk event yang sama (`QueryCountTest` bikin 4, `CompetitionNumberTest` bikin 2+). **Fix:** perlebar ke 4 digit (`CAT-####`, 10.000 kemungkinan) — tidak ada test yang assert ke format literalnya.
- **Diverifikasi ketat:** 5 run suite penuh berturut-turut, 402/402 bersih setiap kali (sebelumnya intermiten gagal ~1 dari 3-4 run sepanjang sesi F8/F9).

**Next steps:** frontend F11 (cross-check Results yang sudah ada dari Sprint 11-12 ke spec F11, tandai resmi selesai kalau cocok) · F12 (adversarial responsive+UX audit menyeluruh — F0-F11 semua sekarang punya konten nyata untuk diaudit) · commit + push ke remote (belum pernah diminta sepanjang sesi).

---

## 2026-09-16 — Frontend F8 (Schedule) — aksi "Edit Slot" yang hilang ditambahkan, komponen select diperluas

**Progress:** Frontend **F0/F1/F2/F3/F4/F5/F6/F7/F8/F10 selesai** (10 dari 13 step). Lanjutan langsung dari F7 di sesi yang sama, dipilih atas instruksi "lanjut" — F8 adalah item berikutnya di build order numerik.

**Gap yang sama persis dengan F7:** list Jadwal sejak Sprint 6 sudah punya Add/Move Up/Move Down/Delete, tapi **"Edit Slot" — aksi eksplisit yang disebut spec F8 — tidak pernah ada UI-nya**, meski `EventScheduleSlotController::update()` sudah ada+teruji sejak Sprint 6. Ditambahkan: tombol "Edit" per slot yang membuka drawer, satu form per slot. Form "Tambah Slot" TETAP inline seperti sebelumnya (tidak dipindah ke drawer) — spec F8 tidak menyebut "drawer" sama sekali untuk Jadwal, beda dari F6/F7 yang eksplisit menyebutnya, jadi tidak diubah tanpa alasan.

**Bug arsitektur F7 muncul lagi di sini, plus 1 gap komponen baru ditemukan:** karena form Tambah (inline) + N form Edit (drawer) berbagi halaman yang sama, isolasi `old()`/`$errors` via `errorKey` (persis fix F7) diterapkan lagi ke semua field. Tapi Jadwal punya 2 field `<select>` (Jenis, Kategori) yang tidak ada sama sekali di form Kategori F7 — dicek, `x-ui.select` **belum punya prop `errorKey`** (cuma `x-ui.input`/`textarea` yang dapat itu di Sprint 9). Kalau dibiarkan, bug bocor-error-lintas-form yang sama bisa terjadi lewat field select. **Fix:** `x-ui.select` diperluas dengan `errorKey` opsional, persis pola input/textarea — backward-compatible penuh (default null → fallback ke nama asli field, semua pemakaian `x-ui.select` lain di app sama sekali tidak terpengaruh).

**Diselesaikan:**
- `resources/views/components/ui/select.blade.php` — +prop `errorKey`.
- `resources/views/dashboard/events/partials/schedule.blade.php` — +tombol Edit, +drawer 1 form per slot, isolasi `old()`/errors.
- **4 test baru** (`ScheduleSlot/ScheduleDrawerTest`): tombol edit+prefill tampil benar, drawer reopen ke slot yang benar, edit slot A gagal tidak bocor ke slot B, tidak bocor ke form Tambah inline.

**Verifikasi:** Pint `--test` pass · **396 test hijau** (392 + 4 baru) · manual end-to-end via curl: tombol Edit membuka drawer dengan field ter-prefill benar termasuk `<select>` Jenis ter-`selected` dengan benar (bukan cuma `<input>`). **2 flake pre-existing TIDAK TERKAIT sempat muncul** selama beberapa run suite penuh (`EventCategoryFactory` random 2-digit `code` collision di `QueryCountTest`, dan timing `display_order` di `ManageScheduleTest::test_owner_can_add_a_slot_appended_to_the_end`) — keduanya PASS konsisten saat dijalankan sendiri/terisolasi (dicoba berulang), dan F8 sama sekali tidak menyentuh kode `ScheduleService`/factory manapun yang terkait. 2 run suite penuh berturut-turut setelahnya bersih 396/396. Dicatat transparan sebagai kandidat investigasi terpisah di kemudian hari — TIDAK diperbaiki diam-diam di sesi ini karena di luar scope F8.

**Next steps:** frontend F9 polish (Judging Setup — readiness checklist + criteria builder, kontennya sudah ada tapi masih inline sederhana) · F11 (cross-check Results yang sudah ada dari Sprint 11-12 ke spec F11) · F12 (adversarial responsive+UX audit menyeluruh) · pertimbangkan investigasi 2 flake di atas kalau muncul lagi berulang · commit + push ke remote (belum pernah diminta).

---

## 2026-09-16 — Frontend F7 (Categories) — card list + drawer, plus bug isolasi form-ganda ditemukan+diperbaiki

**Progress:** Frontend **F0/F1/F2/F3/F4/F5/F6/F7/F10 selesai** (9 dari 13 step). Lanjutan langsung dari F6 di sesi yang sama, dipilih atas instruksi "lanjut" — F7 adalah item berikutnya di build order numerik.

**Gap yang ditutup bukan cuma kosmetik:** kolom Kategori sejak Sprint 3 cuma punya form tambah + tabel + tombol Hapus — **tidak ada UI edit sama sekali**, meski endpoint update-nya sudah ada dan sudah teruji sejak Sprint 3 (`EventCategoryController::update()`, dites via HTTP langsung). Organizer betul-betul tidak bisa perbaiki typo nama/kuota kategori dari UI sebelum ini. Diganti jadi card list (Nama/Kode, Tim terdaftar, Kuota, rentang Anggota) + 1 drawer berisi form Tambah + N form Edit (satu per kategori, toggle Alpine).

**Keputusan scope:** field "Status" di spec sketch F7 TIDAK ditambahkan — `fr-event-category.md` sudah menegaskan sejak Sprint 3 kalau toggle status aktif/nonaktif kategori sengaja ditunda (kolom ada di DB, form request tidak menerimanya). Menambah toggle di UI tanpa backend menerimanya cuma menghasilkan field yang diam-diam diabaikan.

**Bug arsitektur ditemukan+diperbaiki lewat penalaran SEBELUM lapor selesai** (bukan dari test gagal atau laporan pengguna): karena 1 form create + N form edit (satu per kategori) semua dirender di HALAMAN YANG SAMA sekaligus, dan Laravel `old()`/`$errors` itu GLOBAL per nama field (bukan per-form) — draft awal akan bikin gagal-validasi edit kategori A membocorkan nilai/pesan errornya ke form kategori B yang tersembunyi (karena `x-ui.input` internal manggil `old('name')` yang sama untuk SEMUA form bernama `name`). **Fix:** manfaatkan prop `errorKey` yang sudah ada di `x-ui.input`/`textarea` (dibangun Sprint 9 untuk kasus lain) — tiap field cuma dapat `errorKey` = nama field ASLI untuk SATU form yang benar-benar gagal submit (dideteksi dari hidden field `_category_id`), form lain dapat `errorKey` yang dijamin tidak pernah match apa pun di `old()`/`$errors`, otomatis fallback ke nilai `$category` yang sungguhan tersimpan. **Diverifikasi ketat**: sengaja di-broke sementara (`errorKey` selalu nama asli) untuk konfirmasi 2 dari 4 test regresi baru memang gagal seperti diharapkan, baru dikembalikan ke fix yang benar — pola verifikasi yang sama dipakai Sprint 13 untuk kalibrasi `QueryCountTest`.

**Diselesaikan:**
- `EventController::show()` — eager-load `categories.registrations` baru (hindari N+1 untuk hitung "Tim terdaftar" per kategori).
- `events/partials/categories.blade.php` ditulis ulang total.
- **4 test baru** (`Event/CategoryDrawerTest`): jumlah tim terdaftar tampil benar di card, edit gagal tidak salah buka form create, edit kategori A gagal tidak bocor ke form kategori B, create gagal tidak bocor ke form edit manapun.

**Verifikasi:** Pint `--test` pass · **392 test hijau** (388 + 4 baru, 0 regresi) · `EventCategoryTest` (backend, sudah ada sejak Sprint 3) tetap pass tanpa perubahan · `QueryCountTest` (regression guard Sprint 13) tetap pass · manual end-to-end via curl: card kategori tampil benar ("Tim terdaftar 3", "Kuota 20", "Anggota 13–16"), drawer edit terbuka dengan field ter-prefill nilai sungguhan dari DB.

**Next steps:** frontend F8-F9 polish (drawer/quick-view atas Jadwal/Penjurian, kontennya sudah ada tapi masih inline sederhana — F8/F9 kemungkinan tidak butuh fix seberat F7 karena Jadwal/Penjurian tidak pernah multi-form-per-halaman seperti Kategori) · F11 (cross-check Results yang sudah ada dari Sprint 11-12 ke spec F11) · F12 (adversarial responsive+UX audit menyeluruh) · commit + push ke remote (belum pernah diminta).

---

## 2026-09-16 — Frontend F6 (Participants) — drawer quick-view dieksekusi persis rencana F0

**Progress:** Frontend **F0/F1/F2/F3/F4/F5/F6/F10 selesai** (8 dari 13 step). Lanjutan langsung dari F5 di sesi yang sama, dipilih atas instruksi "lanjut" — F6 adalah item berikutnya di build order numerik.

**Konfirmasi kuat bahwa ini urutan yang benar:** `components/ui/drawer.blade.php` (dibangun di F0, 2026-09-09) sudah punya komentar eksplisit "used for quick-view (e.g. Participant detail, per frontend-plan.md F6)" — breadcrumb yang ditulis 7 hari sebelum dikerjakan, dieksekusi persis sesuai rencana itu.

**Diselesaikan:**
- List Peserta: klik nama tim / tombol "Lihat" sekarang membuka **drawer quick-view** (bukan navigasi ke halaman penuh) — isi: team, kategori, status, **No. Kompetisi** (data `CompetitionEntry` yang sudah ada sejak Sprint 5 tapi belum pernah ditampilkan di list ini), jumlah roster, daftar jenis dokumen yang sudah diunggah. Tombol "Buka Halaman Lengkap" di dalam drawer tetap ke halaman penuh existing — dipertahankan sebagai tempat aksi besar (approve/reject, edit roster, upload dokumen), bukan diganti.
- Tabel desktop + card mobile dapat kolom/baris **No. Kompetisi** baru.
- **2 penyesuaian scope dari spec, transparan:** (1) "checklist dokumen" di spec diganti jadi daftar jenis dokumen yang SUDAH diunggah — `fr-registration.md` Keputusan F menegaskan `document_type` bebas/tidak baku, tidak ada daftar wajib untuk dicocokkan jadi checklist beneran; (2) kolom "No." (nomor baris) TIDAK ditambahkan — tidak ada satu pun list lain di app (Events/Teams/Judges) yang punya nomor urut, menambah di sini saja tidak konsisten dengan bahasa desain established sejak F0-F4.
- Drawer dirender pre-render semua baris (bukan fetch-on-demand) — konsisten pola 8-tab `events/show.blade.php`, karena app ini sengaja tanpa endpoint JSON/AJAX di manapun; per halaman cuma 20 baris (paginate), jadi tidak ada biaya nyata.
- **5 test baru** (`Registration/IndexQuickViewTest`): kolom No. Kompetisi tampil, registrasi tanpa entry aman (no crash), drawer tampilkan roster+dokumen, dokumen kosong tampil pesan yang benar, Member role tetap bisa akses.

**Verifikasi:** Pint `--test` pass · **388 test hijau** (383 + 5 baru, 0 regresi — 1 kegagalan sempat muncul di run pertama tapi terbukti flake pre-existing TIDAK TERKAIT: `CompetitionNumberTest` kena collision `EventCategoryFactory`'s random 2-digit `code` di bawah unique constraint per-event, hilang bersih di run ulang, tidak disentuh) · manual end-to-end via curl: fixture 1 registrasi approved + 2 roster + 2 dokumen + 1 competition_number → kolom No. Kompetisi tampil benar di desktop+mobile → drawer menampilkan roster "2 orang" + 2 chip jenis dokumen + tombol "Buka Halaman Lengkap" mengarah ke URL halaman penuh yang benar.

**Next steps:** frontend F7-F9 polish (drawer/quick-view atas Kategori/Jadwal/Penjurian, kontennya sudah ada tapi masih inline sederhana) · F11 (cross-check Results yang sudah ada dari Sprint 11-12 ke spec F11, tandai resmi selesai kalau cocok) · F12 (adversarial responsive+UX audit menyeluruh) · commit + push ke remote (belum pernah diminta).

---

## 2026-09-16 — Frontend F5 (Event Workspace) — Overview dibangun ulang jadi readiness summary

**Progress:** Frontend **F0/F1/F2/F3/F4/F5/F10 selesai** (7 dari 13 step). Lanjutan langsung dari F2 di sesi yang sama, dipilih atas instruksi "lanjut" — F5 adalah item berikutnya di build order numerik, dan sudah disebut eksplisit sebagai "next steps" di entry F2 sebelumnya.

**Keputusan scope (penting, menyimpang dari bacaan literal spec):** spec `frontend-plan.md` §Event Workspace menyebut nav sebagai `Overview / COMPETITION: .../ OPERATIONS: .../ Settings` — bisa dibaca sebagai side-nav vertikal penuh gaya "workspace settings" (Stripe/GitHub). **Diputuskan TIDAK membangun itu.** Alasan: (1) tab bar horizontal yang sudah ada teruji lewat 10 sprint backend tanpa masalah — side-nav vertikal butuh treatment mobile terpisah (nested-nav 3 level, berat untuk admin tool yang sengaja lean); (2) nilai terbesar ada di isi Overview (readiness summary+next action — jelas dispesifikasikan di spec dengan contoh format), bukan di chrome navigasinya. **Kompromi diambil:** tab bar horizontal dipertahankan 100% (nol regresi risk ke pola yang sudah jalan), grup COMPETITION/OPERATIONS ditandai lewat divider visual tipis di antara cluster tab, bukan direstrukturisasi. "Settings" tidak ditambahkan sama sekali — belum ada konten apa pun di baliknya, konsisten pola "nav item cuma ditambah kalau route-nya nyata" yang sudah dipakai di sidebar utama sejak F0/F1.

**Diselesaikan:**
- **Overview dibangun ulang total** — isi lama cuma field list statis + placeholder teks yang sudah BASI (bilang "Jadwal/Penjurian/Hasil tersedia sprint berikutnya" padahal semua fitur itu sudah nyata sejak Sprint 6-12 — bug konten, bukan cuma kurang polish).
- **Readiness strip** (5 item: Kategori/Peserta/Jadwal/Juri/Penilaian) dihitung murni dari relasi yang SUDAH di-eager-load `EventController::show()` — collection math di memori, nol query tambahan per item. ✓/`!` sesuai ada-tidaknya isi.
- **Baris ringkasan pendaftaran** ("82 terdaftar, 76 disetujui, 4 menunggu, 2 revisi") — tampil kondisional (cuma kalau jendela pendaftaran sudah diatur). 1 query baru (`groupBy status`) — flat, tidak scale dengan jumlah round/kriteria.
- **Next Action** — satu item prioritas tertinggi, urutan tetap (bukan rule engine): registrasi pending → kategori kosong → jadwal kosong → round ada tapi juri belum ditugaskan → tidak tampil kalau semua clear. Tombolnya pakai Alpine `@click="tab = '...'"`, persis pola tab-bar sendiri — bukan reload halaman.
- **8 test baru** (`Event/OverviewTest`): readiness kosong vs lengkap, 4 level prioritas next-action, next-action null saat semua siap, breakdown registrasi conditional.

**Verifikasi:** Pint `--test` pass · **383 test hijau** (375 + 8 baru, 0 regresi) · `Event/QueryCountTest` (regression guard Sprint 13 N+1) tetap PASS — +1 query flat dari breakdown registrasi jauh di bawah ambang 30 · manual end-to-end via curl: fixture 1 kategori + 2 registrasi (1 submitted + 1 approved, tanpa jadwal/juri) → readiness strip benar persis (Kategori ✓, Peserta ✓, Jadwal `!`, Juri `!`, Penilaian `!`) → baris pendaftaran benar (2/1/1/0) → Next Action benar-benar memprioritaskan "1 registrasi menunggu direview" di atas gap jadwal/juri yang sama-sama ada.

**Next steps:** frontend F6-F9 polish (drawer/quick-view atas konten yang sudah ada, masih inline sederhana) · F11 (cross-check Results yang sudah ada dari Sprint 11-12 ke spec F11, tandai resmi selesai kalau cocok) · F12 (adversarial responsive+UX audit menyeluruh, termasuk cek tab-bar `overflow-x-auto` yang baru ditambah beneran nyaman dipakai di mobile) · commit + push ke remote (belum pernah diminta).

---

## 2026-09-16 — Frontend F2 (Dashboard) — nav reshuffle Dashboard/Events/Teams/More dieksekusi

**Progress:** Frontend **F0/F1/F2/F3/F4/F10 selesai** (6 dari 13 step). Dipilih atas instruksi "lanjut front end" — F2 adalah satu-satunya step yang BENAR-BENAR belum ada sama sekali (tidak ada route/controller/nav item "Dashboard" walau CLAUDE.md sudah mensyaratkannya di sidebar sejak awal), berbeda dari F5-F9/F11 yang kontennya sudah tersubstansi organik lewat tab inline per backend sprint. Catatan resequencing 2026-09-10 ("F5 dulu baru F2") sudah tidak akurat lagi setelah Sprint 4-13 backend selesai — didokumentasikan transparan di `frontend-plan.md` §Status, bukan diam-diam diabaikan.

**Diselesaikan:**
- **`DashboardController`** (BARU) — 4 angka ringkasan (Event Aktif/Tim/Peserta Terdaftar/Event Mendatang), 1 card event mendatang terdekat, list aktivitas terbaru. Semua di-scope `assignableOrganizationIds()` (helper sama yang sudah dipakai EventController/TeamController) — bukan query baru.
- **Keputusan desain:** "Event Aktif" (status bukan draft/cancelled/finished) dan "Event Mendatang" (`start_date >= today`, bukan cancelled) sengaja dua definisi independen — event draft yang tanggalnya masih di depan tetap dihitung "mendatang" supaya organizer sadar perlu segera disetup. "Aktivitas Terbaru" pakai `activity_logs` (baru ada dari Sprint 12/13 — kebetulan timing pas, tabel ini belum ada sama sekali saat resequencing note 2026-09-10 ditulis). `ActivityLog::label()`+`actorLabel()` baru — map 11 action string yang sudah dipakai `ActivityLogger` ke label Indonesia singkat.
- **Nav reshuffle "lands with F2"** — comment yang sudah ditulis di `mobile-nav.blade.php` sejak F1 dieksekusi persis: sidebar+mobile-nav sekarang Dashboard/Events/Teams (+account-menu), "Organizations" pindah ke panel account-menu (comment F1 sudah menyiapkan slot ini: "Settings/Account links go in the panel once those routes exist"). Desktop sidebar ikut direshuffle sama seperti mobile supaya konsisten, bukan cuma mobile.
- **`/dashboard` jadi home page baru** — login/home redirect diarahkan ke sana. **Kecuali register** — user baru belum punya organisasi, tetap ke Organizations index (empty state "buat organisasi pertama") karena Dashboard kosong tidak actionable untuk user tanpa org sama sekali. 4 assertion redirect di `AuthenticationTest` diupdate mengikuti (register TETAP `dashboard.organizations.index`, disengaja bukan salah).
- **6 test baru** (`Dashboard/IndexTest`): summary counts per-tenant, upcoming event terdekat (bukan yang terjauh), participants count, recent activity, **tenant isolation** (data organisasi lain tidak pernah muncul di angka/card/list manapun — constitution CLAUDE.md), guest redirect ke login.

**Verifikasi:** Pint `--test` pass (auto-fix `fully_qualified_strict_types` sekali) · **375 test hijau** (369 + 6 baru, 0 regresi) · manual end-to-end via curl: login → redirect `/dashboard` → 4 angka cocok fixture → card event mendatang pilih yang terdekat by `start_date` (bukan urutan create) → aktivitas terbaru tampil label Indonesia benar → dicek HTML mentah: "Dashboard" nav aktif di kedua breakpoint (highlight `bg-slate-100`/`text-slate-900`), "Organizations" cuma tersisa di dalam panel account-menu (2 instance — sidebar + mobile-nav masing-masing punya account-menu sendiri), tidak lagi jadi link nav utama di manapun.

**Next steps:** frontend F5 (restrukturisasi shell Event Workspace jadi Overview+nav grup COMPETITION/OPERATIONS — kontennya sudah ada, ini soal reorganisasi tampilan) · F6-F9 polish (drawer/quick-view, kontennya jalan tapi masih inline sederhana) · F11 (cross-check Results yang sudah ada dari Sprint 11-12 ke spec F11, tandai resmi selesai kalau cocok) · F12 (adversarial responsive+UX audit menyeluruh) · commit + push ke remote (belum pernah diminta).

---

## 2026-09-16 — Backend Sprint 4b (Import CSV Peserta) — Phase B ditutup penuh

**Progress:** Backend **Sprint 1-13 + 4b selesai — 369 test hijau di MariaDB** (356 + 13 baru). Sprint opsional terakhir yang masih terbuka setelah MVP backend "selesai" per Sprint 13 — dipilih atas instruksi eksplisit "lanjut sprint 4b dulu". Menutup Phase B secara penuh (alur manual ✅ Sprint 4 + import CSV ✅ Sprint 4b).

**Keputusan Desain** (didokumentasikan di `srs/fr-registration-import.md` Keputusan A-I, diputuskan sendiri berdasarkan pola sprint sebelumnya — tidak perlu tanya Ersa karena semuanya turunan langsung dari precedent yang sudah ada):
- **CSV saja, BUKAN Excel** — walau nama task di `tasks.md` menyebut "CSV/Excel". Parsing native `fgetcsv` = nol dependency baru; `.xlsx` asli butuh library berat (PhpSpreadsheet) untuk kompleksitas yang tidak sepadan kebutuhan nyata (semua tool spreadsheet sudah punya "Export as CSV"). Kalau Excel asli benar-benar dibutuhkan nanti, itu keputusan terpisah — pipeline mapping/preview/commit di sini tidak perlu berubah, cuma langkah parsing yang diganti.
- **Tim harus sudah ada — import TIDAK membuat Team baru**, ikut pola alur manual yang juga cuma punya dropdown pilih team existing (tidak ada opsi "buat tim baru" bahkan di form manual). Baris dengan nama tim tidak cocok → kelompok itu ditandai invalid, dilewati.
- **Hasil import langsung `submitted`, BUKAN `draft`** — kebalikan dari alur manual single-entry yang sengaja mulai `draft`. Maksud impor massal itu panitia sudah punya data siap dari spreadsheet/form kertas, mau masuk resmi sekaligus — kalau mendarat di `draft`, panitia harus klik submit satu-satu untuk ratusan baris, meniadakan manfaat impor massal. Diimplementasikan reuse `RegistrationService::create()`+`changeStatus()`, bukan bypass validasi transisi.
- **Granularitas validasi dua level**: per KELOMPOK TIM untuk keputusan go/no-go (tim tidak ditemukan / sudah terdaftar di kategori ini / tidak ada anggota valid → seluruh kelompok dilewati), per BARIS untuk anggota individual dalam kelompok valid (nama kosong → baris itu saja di-skip, sisanya tetap diproses).
- **Commit bersifat PARTIAL, bukan all-or-nothing** — kelompok invalid dilewati dengan ringkasan akhir ("N berhasil, M dilewati"), bukan menolak seluruh file kalau ada satu baris salah. Lebih berguna untuk file ratusan baris nyata.
- **File CSV sementara di disk privat `local`** (`imports/{event_id}/{ulid}.csv`), bukan session — alur 3-langkah butuh data yang sama dibaca ulang di request berikutnya, dan menyimpan seluruh isi CSV di session (driver `database`) berisiko membengkakkan baris session. Token akses = ULID di nama file. **Tidak ada pembersihan otomatis untuk file yang ditinggal di tengah alur** — keterbatasan v1 yang didokumentasikan eksplisit, dampak kecil (bukan risiko keamanan, tidak reachable tanpa token acak).
- **Delimiter CSV dideteksi otomatis** (koma vs titik-koma) — Excel locale Indonesia sering export pakai titik-koma. Deteksi sederhana: hitung kemunculan di baris header, pakai yang lebih banyak.
- **Tidak ada validasi kuota** saat commit — konsisten `fr-registration.md` Keputusan H (quota enforcement DEFERRED di semua jalur, bukan cuma manual).

**Diselesaikan:**
- **SRS `_docs/srs/fr-registration-import.md`** — Keputusan A-I lengkap.
- **Backend:** `RegistrationImportService` (storeUpload/headers/guessMapping/validateRows/commit), `UploadImportRequest`+`ApplyImportMappingRequest` (form request kedua dipakai bersama step map & commit — bentuk input sama, defense-in-depth re-validasi dari file bukan percaya data preview yang sudah di-render browser), `RegistrationImportController` (create/store/map/preview/commit, semua gated `manageRegistrations`), rute nested `events/{event}/registrations/import/*` (segmen statis "import" wajib didaftar SEBELUM wildcard `{registration}` supaya Laravel tidak salah bind "import" sebagai ID registrasi — diverifikasi `route:list`), 3 view Blade (upload/map/preview) + tombol "Import CSV" di index Peserta. **Tidak ada migration baru** — reuse tabel `registrations`/`registration_members` existing, `migrate:fresh` dicek tetap bersih sebagai sanity check.
- **13 test baru** (total 369): `Registration/ImportTest` (12 — upload valid/invalid, mapping auto-guess, preview per-kelompok/per-baris, commit submitted+roster+partial, delimiter titik-koma, member role diblokir total), `Registration/ImportTenantIsolationTest` (1 — organizer org lain tidak bisa akses token import event org lain, 403 lewat `FormRequest::authorize()`).

**2 bug ditemukan+diperbaiki sendiri lewat self-review SEBELUM testing** (bukan dari test failure — kebiasaan yang makin disengaja mengingat sesi ini beberapa kali menemukan bug nyata belakangan):
- **BOM-handling di `readRows()`**: draft awal punya `rewind()` tanpa syarat setelah deteksi delimiter, yang membatalkan posisi skip-BOM (UTF-8 byte-order-mark umum di CSV export Excel) yang sudah benar dihitung sebelumnya. Fix: satu variabel `$contentStart` (0 atau 3) dihitung sekali, dipakai konsisten di kedua `fseek()` — baik untuk peek delimiter maupun parsing asli.
- **Double-submit di `preview.blade.php`**: draft awal punya `onclick="this.disabled=true; this.form.submit()"` pada tombol `type="submit"` — submit native dari `type="submit"` DAN `.submit()` manual dari `onclick` sama-sama jalan, mengirim POST dua kali. Ketahuan saat membandingkan dengan pola yang sudah konsisten dipakai file lain (`judging.blade.php`): `onsubmit="this.querySelector('button[type=submit]').disabled = true;"` di elemen `<form>` itu sendiri, bukan di tombolnya.

**Verifikasi:** Pint `--test` pass (auto-fix `ordered_imports`+`fully_qualified_strict_types` sekali, lalu clean) · **369 test hijau, 0 regresi** dari 356 sebelumnya · `migrate:fresh` bersih (tidak ada migration baru) · **manual end-to-end via curl**: login → upload CSV nyata (3 baris, 2 tim — 1 exist 1 tidak) → mapping halaman menampilkan tebakan otomatis benar semua 4 kolom → preview menandai "1 tim siap diimpor" + "1 tim dilewati" dengan alasan benar ("Tim tidak ditemukan") → commit redirect sukses → dicek DB langsung: registration `submitted` dengan `submitted_at` terisi, 2 roster member dengan `team_member_id` NULL dan `member_role`/`gender` sesuai CSV, hanya 1 registration dibuat (tim tak dikenal dilewati, bukan sebagian dipaksa masuk) → file sementara di `imports/1/{token}.csv` terbukti terhapus dari disk setelah commit.

**Phase B TERCAPAI PENUH**: panitia kelola seluruh data peserta dari Siapgrak tanpa spreadsheet — alur manual ✅ (Sprint 4) DAN import CSV ✅ (Sprint 4b).

**Next steps:** kerja lanjutan yang masih terbuka (opsional, bukan blocker MVP — belum diminta Ersa): frontend F2/F5/F11/F12 (Dashboard, Workspace shell proper, Results UI, UX audit adversarial) · `backup-restore.md` (belum dibuat, perlu sebelum data user nyata masuk production) · keputusan platform hosting/runtime production (`operations/deployment.md` masih placeholder untuk bagian production) · commit + push ke remote (belum pernah diminta sepanjang sesi ini).

---

## 2026-09-16 — Backend Sprint 13 (Operational Polish) — MVP "Siapgrak Admin v1" BACKEND SELESAI

**Progress:** Backend **Sprint 1-13 selesai — 356 test hijau di MariaDB** (338 + 18 baru). **Phase A-E semua tuntas.** Ini penutup Phase E — MVP backend Siapgrak Admin v1 dianggap selesai per definisi ADR-002 (alur penuh Create Event → ... → Finalize Results, nol halaman publik). Dipilih sebagai kelanjutan sekuensial atas instruksi "lanjut"/"ready" setelah selesai memperbaiki 5 warning IDE di `ResultService.php` (lihat catatan terpisah di bawah).

Sprint ini beda karakter dari 12 sprint sebelumnya — **audit + retrofit**, bukan fitur baru. Dikerjakan sebagai 5 langkah audit sistematis:

**1. Retrofit activity log** — Sprint 12 cuma pakai `activity_logs` untuk aksi Sprint 12 sendiri (finalize/publish/reopen/award), berjanji retrofit ke sprint lama jadi scope Sprint 13. Ditambahkan persis 5 action yang disebut blueprint (bukan lebih): `registration_approved`/`registration_rejected` (di `RegistrationService::changeStatus`), `score_sheet_submitted`/`score_sheet_reopened` (di `ScoreSheetService`), `result_recalculated` (di `ResultService::calculateForRound`). **Catatan desain:** untuk `score_sheet_submitted` (aksi JURI, bukan User) — `user_id` tetap NULL (juri bukan Authenticatable, tidak pernah punya `Auth::id()`), identitas juri dikirim lewat `metadata.judge_id` — bukan bug, konsekuensi arsitektur sesi juri sejak Sprint 9.

**2. Audit permission** — semua `store`/`update`/`destroy` di semua Dashboard controller dicek satu per satu (inline `$this->authorize()` atau `FormRequest::authorize()`). **0 gap ditemukan.** Didokumentasikan sebagai temuan bersih, bukan dilewati diam-diam.

**3. Audit pagination** — Event/Registration/Judge/Team index (list tak terbatas) sudah `paginate(20)` sejak sprint masing-masing. Organization/OrganizationMember/TeamMember index sengaja TIDAK dipaginate (inherently kecil — puluhan, bukan ribuan). **0 perubahan diperlukan.**

**4. Audit N+1 — 3 masalah nyata ditemukan lewat pengukuran query count langsung** (bukan dugaan):
   - **`EventPolicy::membershipRole()` re-query 71x** di satu halaman event — Gate Laravel resolve instance `EventPolicy` BARU di setiap `@can()` call, dan halaman event manggil 8 ability berbeda (`manageJudging`, `manageResults`, dst) puluhan kali lewat nested loop (round/kriteria/award). Diukur: 111 query total untuk fixture 3 kategori/6 round/12 kriteria, 71 di antaranya query `organization_users` yang identik. **Fix:** memoize per (user, event) pakai `Cache::store('array')->rememberForever()` — cache cuma hidup selama satu request, jadi tidak ada risiko data basi lintas-request di production (dipertimbangkan alternatif "hitung boolean sekali di controller lalu ganti semua `@can` jadi `@if`" — ditolak karena cuma menutup 2 dari 8 ability, root cause tetap ada untuk yang lain).
   - **`judging_criteria.children` tidak di-eager-load** — `isLeaf()` akses `$this->children` per kriteria, 12 query tambahan untuk fixture yang sama. **Fix:** perpanjang eager load `EventController::show()` jadi `categories.judgingRounds.criteria.children`.
   - **`ResultCalculator` re-query kriteria round 2x per entry** — `calculateRawScore`/`calculateWeightedScore` masing-masing manggil `resolveCriteriaValues()` yang query ulang dari nol tiap kali, padahal struktur kriteria SAMA untuk semua entry dalam satu `calculateForRound()`. **Fix:** cache instance-level (`$leafCriteriaCache` keyed by round ID) — aman karena `ResultCalculator` di-resolve sekali per request.
   - Hasil: halaman event turun dari **111 → 20 query** untuk fixture uji yang sama. Regression test baru `tests/Feature/Event/QueryCountTest.php` — threshold dikalibrasi dari pengukuran nyata (20 sehat, 43 kalau eager-load di-revert, 119 kalau cache Policy di-revert; ambang 30 menangkap keduanya) supaya N+1 ini tidak diam-diam balik lagi di sprint depan.

**5. Integrity check** — 12 test baru menutup 3 domain yang sebelumnya 0 coverage FK/cascade: `Result/DatabaseIntegrityTest` (verifikasi CASCADE-semua yang jadi Keputusan Desain Sprint 11 — belum pernah dites sungguhan), `ScoreSheet/DatabaseIntegrityTest` (verifikasi RESTRICT-semua yang melindungi histori kompetisi), `JudgeAssignment/DatabaseIntegrityTest` (melengkapi cascade round + RESTRICT judge yang belum tertes sejak Sprint 8).

**Item yang TIDAK bisa dipenuhi harfiah:** "edge case dari testing panitia asli" — tidak ada sesi testing panitia sungguhan yang terjadi dalam pengembangan sesi ini. Daripada mengarang skenario seolah itu masukan nyata, item ini diselesaikan lewat audit sistematis di atas (permission, integrity, N+1) yang menutup kelas masalah yang sama biasanya ditemukan lewat testing nyata — dicatat transparan, bukan diam-diam dianggap selesai.

**Sebelum sprint ini dimulai:** memperbaiki 5 warning IDE ("Cannot implicitly convert 'float' to 'decimal|null'") di `ResultService.php` (Sprint 11/12) atas permintaan eksplisit Ersa. Root cause: editor punya inferensi tipe khusus ("decimal" pseudo-type) untuk properti model yang di-cast `decimal:N`, dan baik `float` maupun `(string)` cast tidak memuaskannya. **Fix:** ganti magic property assignment (`$model->prop = $value`) jadi `$model->setAttribute('prop', $value)` — method asli yang sama-sama dipanggil `__set()` di baliknya, parameter `mixed` jadi tidak kena inferensi tipe ketat itu. Perilaku runtime identik (diverifikasi: 33 test Result + full suite tetap hijau tanpa perubahan).

**Verifikasi:** Pint `--test` pass · **356 test hijau** (beberapa run tambahan mengonfirmasi flake `ScheduleSlot` lama yang sama, bukan regresi baru) · render manual `php artisan serve` + curl live: halaman event dengan data lengkap load **71ms** (dicek `time curl`) ✅, approve registrasi live menulis `activity_logs` dengan action+user+event benar ✅. Tidak ada migrasi baru sprint ini (murni perbaikan kode+test), jadi tidak ada migration-reversibility check yang relevan.

**MVP "Siapgrak Admin v1" backend dianggap SELESAI per ADR-002** — alur penuh Create Event → Setup Category → Input Participant → Review → Competition Number → Schedule → Judges → Criteria → Scoring → Finalize Results berjalan end-to-end, 356 test hijau, nol halaman publik.

**Next steps:** kerja lanjutan yang masih terbuka (opsional, bukan blocker MVP): Sprint 4b (CSV import peserta) · frontend F2/F5/F11/F12 (Dashboard, Workspace shell proper, Results UI, UX audit adversarial) · retrofit activity_logs lebih luas ke Sprint 1-8 kalau dibutuhkan nanti (keputusan baru, bukan diam-diam diperlebar dari sini) · commit + push ke remote (belum pernah diminta sepanjang sesi ini).

---

## 2026-09-16 — Backend Sprint 12 (Awards + Result Finalization) — Phase E lanjut

**Progress:** Backend **Sprint 1-12 selesai — 338 test hijau di MariaDB** (314 + 24 baru). **Phase A, C, D tuntas, Phase E jalan** (Sprint 13 belum: Polish). Dipilih sebagai kelanjutan sekuensial atas instruksi "lanjut".

**Keputusan Desain** (diputuskan sendiri berdasarkan pola Sprint 10, tidak perlu tanya Ersa):
- **Lifecycle hasil meniru persis `ScoreSheetStatus` Sprint 10** — `Draft→Finalized→Published` setara `Draft→Submitted→Locked`. Reopen dari `Finalized` = Owner+Admin (pola reopen `submitted`); reopen dari `Published` = **privileged, Owner-only** (`EventPolicy::reopenPublishedResult`, pola sama `reopenLockedScoreSheet`). Skor/angka TIDAK dihapus saat reopen (sama alasan Sprint 10 — koreksi, bukan mulai ulang).
- **Hitung Ulang Hasil (Sprint 11) diblokir begitu round tidak lagi `draft`** — mencegah organizer diam-diam ubah angka yang sudah "final" tanpa lewat alur reopen+alasan+audit.
- **Tidak ada `ResultPolicy`/`AwardPolicy` terpisah** — roadmap awal sebut "ResultPolicy" sebagai rencana, tapi konsisten pola 8 sprint terakhir (reuse `EventPolicy`, jangan bikin Policy class per sub-resource kalau logic otorisasinya identik), ditambah `EventPolicy::manageResults` (role check sama `manageJudging`, tapi domain Phase E sendiri — Sprint 11's `ResultController`/`UpdatePenaltyRequest` ikut direfactor pakai ini, transparan disebut bukan diam-diam).
- **`activity_logs` dibangun + dipakai HANYA untuk aksi Sprint 12 sendiri** (finalize/publish/reopen/award) — TIDAK di-retrofit ke aksi sprint-sprint lama (registration_approved/rejected, score_sheet_submitted/reopened, dst). Roadmap `M5` eksplisit memisahkan ini — "coverage audit log" adalah item tersendiri di Sprint 13.
- **Awards: `registration_member_id` ada di skema tapi tidak di-expose di form** — dukungan picker anggota tim spesifik ("Best Danton") butuh UI dependent-dropdown yang menambah kompleksitas nyata; workaround v1: nama individu ditulis langsung di field `name`.
- **`job CalculateCompetitionResults` dispatch via `dispatchSync()`, bukan `dispatch()`** — `QUEUE_CONNECTION=database` tapi tidak ada queue worker yang pernah dijalankan/didokumentasikan di project ini; `dispatch()` biasa akan bikin job nongkrong selamanya di tabel `jobs` tanpa error yang terlihat.

**Diselesaikan:**
- **SRS `_docs/srs/fr-awards-finalization.md`** — Keputusan A-J.
- **Backend Sprint 12:**
  - Migrasi 25/26: `awards` (SET NULL ke entry/member — histori award tetap ada walau link-nya lepas), `activity_logs` (`user_id` nullable+SET NULL, pola sama `score_sheet_revisions.requested_by`; tidak ada `updated_at`, immutable).
  - `ResultService::finalize/publish/reopen` (transisi SEMUA baris `competition_results` satu round sekaligus, bukan per-entry — finalisasi sebagian tidak masuk akal untuk ranking yang saling membandingkan) + guard draft-only di `calculateForRound`.
  - `ActivityLogger` service — dipanggil dari `ResultService` (finalize/publish/reopen) dan `AwardController` (create/update/delete).
  - `AwardController` (store/update/destroy, pola sama `EventCategoryController` — dikelola inline di tab Hasil, tidak ada index terpisah).
  - 2 factory (`AwardFactory`, `ActivityLogFactory`). **24 test baru:** `Result/FinalizeTest` (12 — finalize/publish/reopen lifecycle penuh, guard, privileged reopen, activity log), `Award/ManageAwardTest` (7), `ActivityLog/LoggingTest` (3), `Result/TenantIsolationTest` (+2 — finalize/publish/reopen + awards org lain).
- **Frontend:** tab "Hasil" dapat badge status per round (Draft/Finalized/Published) + tombol Finalize/Publish/Reopen sesuai status & role, seksi "Penghargaan" baru (list + form tambah, kaitkan ke kategori/peserta opsional).

**🐛 2 bug nyata ditemukan + diperbaiki (keduanya lewat test/verifikasi manual, sebelum lapor selesai):**
1. **`Eloquent\Builder::value()` ternyata tetap lewat cast model** (beda dari `DB::table()->value()` yang raw) — `ResultService::currentStatus()` awalnya memanggil `ResultStatus::from()` di atas hasil `->value('status')` yang SUDAH berupa instance `ResultStatus`, bukan string mentah, jadi `TypeError`. Ketahuan langsung dari regression test Sprint 11 yang gagal begitu guard baru ditambahkan. Fix: hapus pembungkus `::from()`.
2. **`@endif@if` tanpa spasi di Blade salah tokenisasi** — dua directive `@if...@endif` ditulis bersebelahan tanpa spasi di `results.blade.php` (seksi Awards). Blade cuma mengenali `@endif` pertama, `@if` kedua tertinggal jadi teks literal, tapi `@endif` penutupnya tetap ter-compile jadi PHP `endif;` yang berdiri sendiri → syntax error. **Jejaknya sempat membingungkan** karena muncul lewat test `ScheduleSlot` yang sama sekali tidak berhubungan (test itu me-render halaman event penuh, termasuk partial yang rusak). Ditelusuri dengan compile Blade manual + `php -l` pada hasilnya untuk dapat baris pasti. Fix: kasih spasi/baris baru di antara directive.

**Verifikasi:** Pint `--test` pass · **338 test hijau** (3 run tambahan mengonfirmasi flake `ScheduleSlot` lama yang sama, bukan regresi baru) · `migrate:fresh`/`migrate:reset`+`migrate` bersih · render manual `php artisan serve` + curl live dengan **3 persona** (owner, admin, member): finalize ditolak sebelum ada hasil (pesan persis) ✅, calculate→finalize→publish berurutan dengan badge status berubah tiap tahap ✅, **Admin ditolak reopen round published (403 langsung)** ✅, **Owner berhasil reopen** — status kembali draft, skor mentah tetap tersimpan, ketiga baris activity_logs (finalized/published/reopened) tercatat lengkap dengan user yang benar ✅, award create/delete jalan dengan linkage kategori+peserta tampil benar ✅, dan role member **sama sekali tidak melihat apapun** di tab Hasil (bukan cuma tombol yang hilang — termasuk seksi Penghargaan) ✅.

**Next steps:** Sprint 13 (Polish — coverage audit log retrofit ke Sprint 1-11, optimasi query/index, validasi status, integrity check, permission final, edge case) untuk menuntaskan **seluruh MVP "Siapgrak Admin v1"** — atau Sprint 4b (CSV import) · atau frontend catch-up (F2 Dashboard, F5 Workspace shell, F11 Results, F12 UX audit).

---

## 2026-09-16 — Backend Sprint 11 (Result Calculator + Ranking) — Phase E dimulai

**Progress:** Backend **Sprint 1-11 selesai — 314 test hijau di MariaDB** (295 + 19 baru). **Phase A, C, D tuntas, Phase E jalan** (Sprint 12-13 belum: Awards/Finalize/Publish, Polish). Dipilih sebagai kelanjutan sekuensial atas instruksi "lanjut".

**Keputusan Desain** (diputuskan sendiri berdasarkan blueprint + pola Sprint 9/10, tidak perlu tanya Ersa):
- **Hasil per-round saja — `judging_round_id` tidak pernah NULL.** Blueprint mengizinkan rollup lintas-round (kategori dengan >1 round), tapi kebanyakan event v1 cuma 1 round ("Round tetap dipakai walau v1 kebanyakan event cuma 1 round"), jadi hasil per-round sudah jadi hasil keseluruhan untuk kasus paling umum. Rollup lintas-round (perlu normalisasi bobot antar round) didefer eksplisit, bukan diam-diam diskip — dicatat di SRS kalau nanti ada event nyata multi-round yang butuh.
- **Entry dengan kriteria belum lengkap di-skip TOTAL dari hasil** — bukan ditampilkan dengan nilai 0/parsial. "Lengkap" = tiap kriteria leaf punya skor dari score sheet `submitted`/`locked` (draft tidak dihitung, konsisten Sprint 9/10).
- **Tie handling eksplisit**: standard competition ranking "1-2-2-4" (skip setelah seri), bukan dense ranking — dipilih karena ini konvensi paling umum di kompetisi olahraga.
- **Penalti diinput manual organizer** (bukan dihitung dari sumber lain — tidak ada tabel "pelanggaran" di skema manapun), dipertahankan lewat Hitung Ulang.
- **Tab "Hasil" di-gate penuh `manageJudging`**, sama seperti Lembar Skor Sprint 10 — ranking adalah agregat skor individual juri, tetap dianggap sensitif sampai ada status `finalized`/`published` resmi (Sprint 12).

**Diselesaikan:**
- **SRS `_docs/srs/fr-result-calculator.md`** — Keputusan A-I.
- **Backend Sprint 11:**
  - Migrasi 24: `competition_results` (FK CASCADE semua — beda dari `score_sheets`/`scores` yang RESTRICT, karena ini data turunan yang selalu bisa dihitung ulang, bukan histori/bukti). Nama unique index eksplisit pendek dari awal (pelajaran Sprint 8/9 diterapkan preventif).
  - `ResultCalculator` — 5 method sesuai kontrak blueprint (`calculateRawScore`, `calculateWeightedScore`, `calculatePenalty`, `calculateFinalScore`, `calculateRanking`). Kriteria dinilai >1 juri (overlap assignment, bukan pola LKBB standar tapi tidak dicegah skema Sprint 8) → dirata-rata. Bobot kriteria NULL diperlakukan 100% (bukan 0%, supaya kriteria tanpa bobot tetap berkontribusi penuh).
  - `ResultService::calculateForRound` (delete+rebuild per round, snapshot+terapkan ulang penalti existing sebelum delete) dan `::updatePenalty` (edit penalti → final_score & ranking re-kalkulasi otomatis).
  - `CompetitionResult` — `$fillable` KOSONG TOTAL, semua field (termasuk `penalty_score`) diset lewat properti langsung di service, bukan mass-assignment sama sekali (paling ketat dari semua model di project ini).
  - `ResultController` (calculate, updatePenalty) + `UpdatePenaltyRequest`. Routes: `judging-rounds/{round}/results/calculate`, `results/{result}/penalty`.
  - 1 factory (`CompetitionResultFactory`). **19 test baru:** `CalculationTest` (8 — raw/weighted/rata-rata multi-juri/skip-incomplete/draft-tidak-dihitung/locked-tetap-dihitung/exclude-withdrawn-disqualified/penalty), `RankingTest` (3 — urutan unik, seri 1-1-3, stabilitas tampilan), `RecalculateTest` (5 — trigger, preserve-penalty, entry-disqualified-hilang, edit-penalti-re-rank, member-forbidden), `TenantIsolationTest` (3).
- **Frontend:** tab baru "Hasil" di halaman event — per round: tombol "Hitung Ulang Hasil" + tabel ranking (rank, peserta, raw/weighted/penalti-editable/final). Seluruh tab disembunyikan total dari role `member` ("Tidak ada akses"), bukan cuma tombol aksinya.

**🐛 Gap ditemukan + diperbaiki sebelum lapor selesai (bukan dari verifikasi manual — dari test yang sengaja ditulis untuk memverifikasi Keputusan Desain sendiri):** relasi `JudgingRound::results()` awalnya cuma `orderBy('rank')`. `ResultCalculator::calculateRanking()` sudah menghitung urutan stabil (rank, lalu `competition_number`) secara in-memory sebelum menyimpan, tapi begitu hasil di-query ULANG (halaman event, test, kode lain manapun), entry yang seri (rank sama) tidak punya jaminan urutan tampilan — bisa beda tiap request meski angka rank-nya sama-sama benar. Ketahuan dari test `test_display_order_among_ties_is_stable_by_competition_number` yang sengaja ditulis untuk mengecek Keputusan D di level QUERY, bukan cuma di level kalkulator. **Fix:** relasi `results()` di-JOIN ke `competition_entries` + `orderBy` kedua eksplisit ke `competition_number`. Ini kelas bug baru dibanding sprint-sprint sebelumnya — bukan salah hitung, tapi jaminan desain yang didokumentasikan di SRS tidak benar-benar sampai ke level query.

**Verifikasi:** Pint `--test` pass · **314 test hijau** (3 run tambahan mengonfirmasi flake `ScheduleSlot` lama yang sama, bukan regresi baru) · `migrate:fresh`/`migrate:reset`+`migrate` bersih · render manual `php artisan serve` + curl live dengan skenario nyata (2 peserta lengkap + 1 sengaja tidak lengkap): hitung hasil ✅ (raw/weighted benar sesuai bobot kriteria), **peserta yang belum lengkap kriterianya tidak muncul sama sekali** ✅, edit penalti langsung mengubah final_score dan urutan ranking (peserta rank 1 kena penalti turun ke rank 2) ✅, hitung ulang **mempertahankan penalti yang sudah diinput** ✅, dan role member sama sekali tidak melihat isi tab Hasil (pesan "Tidak ada akses") + ditolak 403 kalau coba trigger hitung langsung lewat URL ✅.

**Next steps:** Sprint 12 (Awards + Result Finalization — `awards`, `activity_logs`, finalize `draft→finalized→published`, ResultPolicy) untuk lanjut Phase E — atau Sprint 4b (CSV import) · atau frontend catch-up.

---

## 2026-09-15 — Backend Sprint 10 (Submit / Lock / Reopen + Audit Trail) — Phase D TUNTAS

**Progress:** Backend **Sprint 1-10 selesai — 295 test hijau di MariaDB** (276 + 19 baru). **Phase A, C, D semua tuntas** (Phase E — Results — belum: Sprint 11-13). Dipilih sebagai kelanjutan sekuensial atas instruksi "lanjut".

**Keputusan Desain Kritis** (dijawab sendiri berdasarkan pola yang sudah ada + blueprint, tidak perlu tanya Ersa — beda dari Sprint 7/9 yang butuh keputusan arsitektur baru):
- **Reopen sheet `locked` = privileged action, didefinisikan Owner-only (bukan Admin).** Blueprint/dev-checklist eksplisit memisahkan "Locked sheet tidak bisa reopen tanpa privileged action" dari "Reopen wajib reason" sebagai DUA aturan berbeda, tapi tidak mendefinisikan siapa "privileged" itu. Dipilih Owner (role tertinggi yang sudah ada di `OrganizationRole`) — reopen sheet `submitted` (belum di-lock) tetap Owner+Admin seperti aksi Judging lainnya.
- **Scores TIDAK dihapus saat reopen** — reopen = "juri perlu koreksi", bukan "mulai dari nol". `submitted_at`/`locked_at` di-reset null, tapi baris `Score` yang sudah ada tetap ada untuk diedit ulang.
- **Halaman "Lembar Skor" organizer di-gate penuh `manageJudging`** (bukan `view` seperti Round/Criteria) — nilai skor individual juri adalah domain paling sensitif di project ini, jadi seluruh seksi (termasuk list status) disembunyikan total dari role `member`, bukan cuma detail/aksinya.
- **List Lembar Skor cuma status+progress, bukan angka skor** — detail nilai per kriteria baru terlihat di halaman detail satu sheet. Ini bukan halaman ranking (tidak ada sorting/agregat/perbandingan lintas juri) — organizer meninjau SATU lembar SATU juri untuk memutuskan reopen/lock, konsisten larangan Constitution soal kebocoran ranking sebelum finalize (Sprint 11).

**Diselesaikan:**
- **SRS `_docs/srs/fr-score-sheet-lifecycle.md`** — Keputusan A-H.
- **Backend Sprint 10:**
  - Migrasi 23: `score_sheet_revisions` (`requested_by` nullable+SET NULL — Constitution "deleted user tidak boleh merusak audit trail"; tidak ada `updated_at`, immutable).
  - `ScoreSheetService::submit()` (validasi semua scoreable criteria terisi, lempar `IncompleteScoreSheet` kalau tidak — pesan eksplisit sebut nama kriteria yang kosong, bukan ditebak dari form), `::lock()` (submitted→locked saja, tidak dari draft), `::reopen()` (submitted/locked→draft, catat `score_sheet_revisions`, `revision_count`+1, scores tetap).
  - `EventPolicy::reopenLockedScoreSheet` (Owner-only, method baru terpisah dari `manageJudging`).
  - `ScoreSheetController` (organizer: show/lock/reopen) + `ScoringController::submit` (judge). Routes `dashboard/score-sheets/{sheet}(/lock|/reopen)`, `judge/rounds/{round}/entries/{entry}/submit`.
  - Guard di `saveDraft`/`submit`/`store` — sheet non-draft ditolak 403 di endpoint, bukan cuma disembunyikan di UI.
  - 1 factory (`ScoreSheetRevisionFactory`). **19 test baru:** `SubmitTest` (6), `LockReopenTest` (8), `AuditTrailTest` (3), `TenantIsolationTest` (+2 — judge submit ke round lain, organizer org lain kelola sheet org lain).
- **Frontend:** form skor juri dapat tombol "Kirim Skor Final" + banner read-only begitu status bukan draft (input `disabled` sungguhan, bukan cuma visual). Sisi organizer: seksi "Lembar Skor" per round di tab Penjurian (status+progress saja) + halaman detail `dashboard/score-sheets/{sheet}` (nilai per kriteria, tombol Kunci/Buka Kembali sesuai role, riwayat reopen).

**🐛 Bug nyata ditemukan + diperbaiki (via test, sebelum verifikasi manual):** `ScoreSheet::firstOrCreate()` (dipakai `getOrCreateScoreSheet` sejak Sprint 9) tidak meng-hydrate default kolom DB (`status DEFAULT 'draft'`) ke instance PHP setelah INSERT — Eloquent tidak membaca ulang server-side default. Efeknya: `$sheet->status` pada baris yang BARU dibuat bernilai `null` sungguhan di PHP (walau DB-nya sudah benar `'draft'`), sampai model itu di-`refresh()`/dibaca ulang dari DB. Bug ini **laten sejak Sprint 9** — view lama tidak pernah membaca `$sheet->status` sama sekali, jadi tidak pernah ketahuan sampai `judge/entries/show.blade.php` sprint ini mulai bergantung padanya untuk logic read-only, muncul sebagai crash 500 di 4 test Sprint 9 yang sudah ada. **Fix:** tambah `protected $attributes = ['status' => 'draft']` di model — default level-PHP yang mencerminkan default level-DB.

**Catatan test yang diperkuat:** assertion awal `assertSee('disabled', false)` untuk cek form read-only ternyata tautologi — kelas `disabled:opacity-50`/`disabled:cursor-not-allowed` (Tailwind pseudo-class, bukan atribut HTML) di komponen tombol selalu ada di markup terlepas status sheet, jadi assertion itu selalu lolos walau input sungguhan tidak ter-disable. Diperbaiki jadi cek literal `disabled="disabled"` (atribut HTML asli yang dirender Laravel Blade untuk boolean attribute true) — ditemukan+diperbaiki sebelum verifikasi manual, dikonfirmasi ulang lewat curl live (grep khusus untuk bedakan keduanya).

**Verifikasi:** Pint `--test` pass · **295 test hijau** (2 run tambahan mengonfirmasi flake `ScheduleSlot` lama, bukan regresi baru) · `migrate:fresh`/`migrate:reset`+`migrate` bersih · render manual `php artisan serve` + curl live dengan **3 persona berbeda** (judge, org Admin, org Owner): submit ditolak kalau ada kriteria kosong dengan pesan persis ✅, submit berhasil + form otomatis read-only (dicek atribut HTML asli) ✅, juri tidak bisa edit lagi setelah submit (403 live, skor lama tidak berubah) ✅, Admin lihat detail skor + berhasil lock ✅, **Admin ditolak reopen sheet locked** (403 live, privileged action) ✅, **Owner berhasil reopen sheet locked** dengan reason — status kembali draft, timestamp ter-reset, `revision_count` naik, skor lama tetap tersimpan, riwayat tercatat benar ✅, dan form juri kembali bisa diedit + tombol submit muncul lagi setelah reopen ✅.

**Milestone Phase D tercapai:** full digital scoring dari judging setup sampai lock/audit, judge isolation tegak di setiap layer (session, service, policy).

**Next steps:** Sprint 11 (Result Calculator + Ranking) untuk mulai Phase E — atau Sprint 4b (CSV import) · atau frontend catch-up (F2 Dashboard, F5 Workspace shell proper, F7 Categories drawer, F11/F12 Results+UX audit).

---

## 2026-09-15 — Backend Sprint 9 (Scoring / Score Sheet, save draft) — Phase D lanjut

**Progress:** Backend **Sprint 1-9 selesai — 276 test hijau di MariaDB** (247 + 26 baru + 2 ditambah ke test existing). **Phase A & C tuntas, Phase D jalan** (Sprint 10 belum: Submit/Lock/Audit). Dipilih sebagai kelanjutan sekuensial atas instruksi "lanjut".

**Keputusan Desain Kritis dikonfirmasi Ersa SEBELUM coding** (via pertanyaan eksplisit — gap arsitektur autentikasi, bukan cuma tenant-isolation seperti sprint-sprint sebelumnya):
- **Mekanisme akses juri = kode akses per-judge (8 karakter), BUKAN akun User/email/password.** Sprint ini butuh area login terpisah `/judge/*` (sudah direncanakan di roadmap sejak awal), tapi **tidak ada satu pun dokumen** (`BACKEND-HANDOFF.md`, blueprint, dev-checklist, BRD) yang menentukan mekanismenya secara konkret — semua cuma bilang "area terpisah". Dicek semua file `.md` dulu sebelum bertanya ke Ersa (bukan langsung nebak). Faktor pendukung keputusan: `judges.user_id` sejak Sprint 7 nullable ("judge boleh belum punya akun"), `MAIL_MAILER=log` (belum ada pengiriman email nyata — bikin alur invite-by-email tidak benar-benar jalan), dan persona BRD eksplisit bilang juri itu **"Awam–menengah"** dan **"Per-event (hari-H saja)"**. Ersa memilih kode akses (opsi yang direkomendasikan) atas link-ke-akun-existing-via-email. Session murni pakai flag session biasa (`session('judge_id')`), **bukan** `Auth::guard()` baru — dihindari supaya tidak nambah abstraksi guard untuk satu kasus sempit.

**Diselesaikan:**
- **SRS `_docs/srs/fr-scoring.md`** — Keputusan A-J. Poin lain yang penting: cuma kriteria **leaf** (tanpa subcriteria) yang benar-benar punya baris `scores` — parent dengan anak murni pengelompokan tampilan, di-rollup nanti oleh `ResultCalculator` (Sprint 11). `score_sheets` dibuat **lazy** (on-demand saat juri pertama buka/simpan), bukan proaktif. Sprint ini **cuma mencapai status `draft`** — kolom `status/submitted_at/locked_at/revision_count` sudah dibuat sesuai skema blueprint penuh (hindari ALTER TABLE lagi), tapi tidak ada endpoint yang mengubahnya dari draft (submit/lock/reopen = Sprint 10, sudah direncanakan sejak sebelum sprint ini mulai). **Tidak ada halaman skor apapun di sisi organizer** — Constitution soal kebocoran ranking sebelum finalize membuat sprint ini sengaja tidak expose skor/progress juri ke organizer sama sekali.
- **Backend Sprint 9:**
  - Migrasi 20/21/22: `judges.access_code` (alter, plaintext+unique — organizer perlu menampilkan ulang, bukan cuma verifikasi sekali), `score_sheets` (3 FK RESTRICT — competition history, bukan config buang), `scores` (`DECIMAL(10,3)`, FK RESTRICT ke criteria). Nama unique index eksplisit pendek langsung dari awal (pelajaran Sprint 8 diterapkan preventif, tidak menunggu kena bug yang sama lagi).
  - `Judge::generateAccessCode()` — alfabet tanpa karakter ambigu (`0/O/1/I/L`, gampang salah baca/tulis di kartu meja juri hari-H), auto-generate saat create.
  - `EnsureJudgeSession` middleware + `InteractsWithJudgeSession` trait — session-only, redirect ke `/judge/access` kalau tidak valid/juri nonaktif.
  - `ScoreSheetService` — `assignedRounds`, `scoreableCriteriaFor` (union assignment whole-round/kriteria-spesifik → leaf criteria, termasuk ekspansi kalau assignment mengarah ke parent yang py anak), `entriesForRound` (exclude withdrawn/disqualified), `getOrCreateScoreSheet` (lazy), `saveDraft`.
  - `SaveScoreDraftRequest` — validasi range + keanggotaan kriteria di `withValidator`, pola sama `StoreJudgeAssignmentRequest` Sprint 8.
  - Controller `Judge\{AccessController,DashboardController,ScoringController}`, routes `/judge/*` (di luar grup middleware `auth` — sistem sesi berbeda total).
  - **Guard destroy Round/Criteria ditambahkan** (janji wajib Sprint 7) — `JudgingRoundController`/`JudgingCriteriaController::destroy` sekarang menolak kalau sudah ada skor tersimpan, pesan ramah pola sama `EventCategoryController`.
  - 2 factory (`ScoreSheetFactory`, `ScoreFactory`). **4 test file baru / 26 test:** `Judge/AccessCodeTest` (5), `ScoreSheet/JudgeSessionTest` (7), `ScoreSheet/ScoringTest` (10), `ScoreSheet/TenantIsolationTest` (4) — plus 2 test ditambahkan ke `ManageRoundTest`/`ManageCriteriaTest` existing untuk guard destroy baru.
- **Frontend:** area `/judge/*` penuh — layout minimal terpisah total (`layouts/judge.blade.php`, tanpa sidebar/settings), halaman akses kode, dashboard round assigned, list peserta per round (dengan progress "N/M"), form skor per peserta. Sisi organizer: seksi "Kode Akses Judge Area" + tombol Regenerate di halaman Judge show.

**🐛 2 bug ditemukan + diperbaiki (keduanya lewat test, sebelum verifikasi manual):**
1. **Fillable-guard (pola berulang ke-3, lihat Sprint 4/5)**: `JudgeController::regenerateAccessCode` awalnya pakai `$judge->update(['access_code' => ...])` — silently no-op karena `access_code` sengaja tidak dimasukkan ke `Judge::$fillable` (satu-satunya jalur pengubahnya memang cuma aksi ini). Ketahuan dari test `assertNotSame` yang gagal. Fix: set properti langsung + `save()`.
2. **Reusable component tidak siap untuk nama field array dinamis**: `x-ui.input`/`x-ui.textarea` pakai satu prop `$name` untuk HTML `name` attribute SEKALIGUS untuk `old()`/`$errors->first()` lookup. Form skor ini yang pertama di project pakai nama field bracket-array (`scores[5][score]`) — HTML butuh notasi bracket (parsing PHP jadi array bersarang), tapi `old()`/error bag Laravel butuh notasi dot (`scores.5.score`). Dua string itu beda, komponen lama cuma tahu satu jadi errors tidak ke-anchor ke field yang benar. Fix: tambah prop opsional `errorKey` (default `$name`, jadi tidak mengubah perilaku pemanggil lama manapun) di kedua komponen.

**Verifikasi:** Pint `--test` pass · **276 test hijau** (2 run tambahan mengonfirmasi flake `ScheduleSlot` yang sama dari Sprint 6-8, bukan regresi baru) · `migrate:fresh`/`migrate:reset`+`migrate` bersih · render manual `php artisan serve` + curl live lintas-2-organisasi: login kode akses (case-insensitive+trim, kode salah ditolak, judge inactive ditolak) ✅, dashboard tampilkan round assigned ✅, form skor cuma tampilkan kriteria leaf tanggung jawab juri (parent "Variasi" tidak muncul, "PBB"+"Formasi" muncul) ✅, simpan draft tersimpan presisi DECIMAL(10,3) + notes ✅, skor di luar range ditolak live ✅, kriteria di luar assignment ditolak live ✅, progress indikator update "2/2" ✅, logout membersihkan session ✅, organizer lihat+regenerate kode akses (kode lama langsung invalid) ✅, dan **juri organisasi B dapat 403 murni saat coba akses round organisasi A lewat URL langsung** ✅.

**Next steps:** Sprint 10 (Submit/Lock/Reopen + Audit Trail, `score_sheet_revisions`, ScoreSheetPolicy formal) untuk menuntaskan Phase D — atau Sprint 4b (CSV import) · atau frontend catch-up.

---

## 2026-09-14 — Backend Sprint 8 (Judge Assignment) — Phase D lanjut

**Progress:** Backend **Sprint 1-8 selesai — 247 test hijau di MariaDB** (233 + 14). **Phase A & C tuntas, Phase D jalan** (Sprint 9-10 belum: Scoring, Submit/Lock/Audit). Dipilih sebagai kelanjutan sekuensial atas instruksi "lanjut sprint 8" — sempat ditanya dulu apakah Sprint 4b (CSV import) perlu didahulukan, tapi tidak ada dependency teknis jadi lanjut Sprint 8.

**Diselesaikan:**
- **SRS `_docs/srs/fr-judge-assignment.md`** — Keputusan A-G. Poin utama: `event_category_id` sengaja di-omit dari tabel (100% derivable dari `judging_round.event_category_id`, dua sumber kebenaran berisiko desync). `judging_criteria_id` nullable → CASCADE (bukan SET NULL) saat kriteria dihapus, supaya assignment "menilai kriteria X" tidak diam-diam berubah makna jadi "menilai seluruh round". Tidak ada endpoint update (ganti scope = hapus + assign ulang).
- **Backend Sprint 8:**
  - Migrasi 19 `judge_assignments`: `judging_round_id` CASCADE, `judge_id` RESTRICT, `judging_criteria_id` nullable CASCADE, `status`. Unique 3-kolom + index `judge_id`.
  - Model `JudgeAssignment` + relasi `assignments()` di `JudgingRound`/`Judge`.
  - `StoreJudgeAssignmentRequest` — **RULE-3 (satu judge per round: seluruh-round ATAU N kriteria spesifik, tidak campur) ditegakkan di app-level**, bukan cuma DB unique. Alasan: MySQL/MariaDB `UNIQUE` menganggap `NULL != NULL`, jadi constraint DB **tidak mencegah** judge dapat 2 assignment "seluruh round" (`criteria_id NULL`) di round yang sama — persis peringatan yang sudah ada di blueprint asli.
  - `JudgingService::createAssignment`/`deleteAssignment`. `JudgeAssignmentController` (store, destroy saja). Routes: `judging-rounds/{round}/assignments`, `judge-assignments/{assignment}`. Reuse `EventPolicy::manageJudging` — tidak ada policy baru.
  - 1 factory. **2 test file / 14 test:** ManageAssignmentTest (11 — assign seluruh-round, assign kriteria spesifik, 3 varian penolakan campur-scope, 2 kriteria berbeda boleh, judge organisasi lain ditolak, kriteria round lain ditolak, hapus, cascade-delete-kriteria, member forbidden), TenantIsolationTest (3).
- **Frontend:** seksi "Juri Ditugaskan" ditambah di tab Penjurian, per round — list assignment existing (nama juri + cakupan: "Seluruh round" atau nama kriteria) dengan tombol "Lepas", plus form assign (pilih juri dari direktori organisasi + pilih cakupan/kriteria).

**🐛 Bug migrasi ditemukan + diperbaiki:** nama unique index auto-generate Laravel untuk 3 kolom (`judge_assignments_judging_round_id_judge_id_judging_criteria_id_unique`, 73 karakter) melebihi limit identifier MySQL/MariaDB (64 karakter) → `SQLSTATE[42000]: ... Identifier name ... is too long`. Efeknya membingungkan saat debug: `CREATE TABLE` sempat sukses (tabel fisik ada) sebelum `ALTER TABLE` index-nya gagal, tapi migrasi **tidak tercatat "Ran"** — jadi tiap retry gagal lagi dengan pesan "table already exists" yang menyesatkan (kelihatan seperti tabel bentrok, padahal migrasi sebelumnya gagal separuh jalan). Sempat beberapa langkah salah arah (cek proses nyangkut, MySQL processlist) sebelum jalanin `php artisan migrate` tanpa `tail` yang memotong output — baru kelihatan pesan error aslinya. **Fix:** beri nama eksplisit pendek pada unique constraint (`'judge_assignments_unique'`). **Pelajaran:** kalau error migrasi kelihatan tidak konsisten dengan state DB yang teramati, selalu ambil output lengkap/tidak terpotong dulu sebelum menduga-duga penyebab lain.

**Catatan flake test (bukan defect, berulang dari Sprint 6-7):** `ScheduleSlot/ManageScheduleTest::test_category_from_another_event_is_rejected` gagal 1x di salah satu full-suite run (247 test), lolos bersih di run berikutnya + selalu lolos terisolasi — test itu sendiri tidak disentuh sejak Sprint 6. Tetap dicatat transparan, belum di-root-cause.

**Verifikasi:** Pint `--test` pass · **247 test hijau** (2 run tambahan mengonfirmasi flake yang sama, bukan regresi baru) · `migrate:fresh`/`migrate:reset`+`migrate` bersih (termasuk migrasi 19 yang tadinya gagal) · render manual `php artisan serve` + curl live: assign judge ke seluruh round ✅, assign judge lain ke kriteria spesifik ✅ (tampil "Mayor Wati (PBB)"), **percobaan assign judge yang sama 2x ke seluruh round ditolak nyata** (pesan error tampil, DB count tidak nambah), **judge dari organisasi lain ditolak nyata**, hapus assignment via tombol "Lepas" ✅ (DB berkurang, UI update).

**Next steps:** Sprint 9 (Scoring/Score Sheet) untuk lanjut Phase D — **ingat tambah guard destroy Round/Criteria begitu `scores` ada** (dicatat sejak Sprint 7) — atau Sprint 4b (CSV import) · atau frontend catch-up.

---

## 2026-09-14 — Backend Sprint 7 (Judges + Judging Rounds + Criteria) — masuk Phase D

**Progress:** Backend **Sprint 1-7 selesai — 233 test hijau di MariaDB** (199 + 34). **Phase A & C tuntas, Phase D dimulai** (Sprint 8-10 belum: Assignment, Scoring, Submit/Lock/Audit). Dipilih sebagai kelanjutan sekuensial dari "lanjut" tanpa spesifik — Phase D adalah domain paling sensitif di project ini (Constitution).

**Keputusan Desain Kritis dikonfirmasi Ersa SEBELUM coding** (via pertanyaan eksplisit, bukan diasumsikan sendiri — konsisten aturan CLAUDE.md untuk gap tenant-isolation):
- **`judges.organization_id` ditambah** — blueprint asli `research/chatgpt-blueprint-v1.md` mendefinisikan `judges` **tanpa** kolom organisasi/event sama sekali. Tanpa itu, organizer A bisa lihat/edit data juri (nama/foto/bio) organizer B — persis kelas masalah `teams.organization_id` di Sprint 2. **Dipilih:** tambah kolom (nullable schema, wajib di validasi, pola sama Team) — bukan biarkan global sesuai blueprint literal.

**Diselesaikan:**
- **SRS `_docs/srs/fr-judging-setup.md`** — Keputusan A-G, termasuk **halaman `/dashboard/judges` sengaja TIDAK masuk sidebar global** (CLAUDE.md mengunci sidebar ke `Dashboard/Events/Teams/Organization/Settings`) — reachable lewat link "Kelola Juri Organisasi →" di tab Penjurian Event Workspace, bukan menu baru.
- **Backend Sprint 7:**
  - Migrasi 16/17/18: `judges` (+organization_id RESTRICT, user_id SET NULL — judge boleh belum punya akun), `judging_rounds` (CASCADE dari category), `judging_criteria` (CASCADE dari round, `parent_id` self-referential RESTRICT, **max nested depth 2**).
  - Model `Judge` (`HasPublicId` `judge_`), `JudgingRound`, `JudgingCriteria` (`parent()`/`children()` self-relation).
  - `JudgePolicy` (pola `TeamPolicy` persis) + `EventPolicy::manageJudging`. Tidak ada `JudgingRoundPolicy`/`JudgingCriteriaPolicy` terpisah (pola konsisten sejak Sprint 3).
  - `JudgeService` (mirror `TeamService`) + `JudgingService` (CRUD round/criteria — **depth-cap 2 ditegakkan penuh lewat SATU validation rule** `Rule::exists('judging_criteria','id')->where('judging_round_id',...)->whereNull('parent_id')`, bukan logic terpisah di service).
  - 6 Form Request, 3 controller (`JudgeController` full resource except destroy — pola Teams; `JudgingRoundController`, `JudgingCriteriaController` inline di event). Routes: `dashboard/judges/*`, `events/{event}/categories/{category}/rounds`, `judging-rounds/{round}(/criteria)`, `judging-criteria/{criterion}`.
  - 3 factory. **7 test file / 34 test:** Judge (Create/Update/TenantIsolation), JudgingRound (ManageRound), JudgingCriteria (ManageCriteria — termasuk kasus depth-cap & cross-round parent), JudgingSetup (TenantIsolation gabungan Round+Criteria, DatabaseIntegrity).
- **Frontend:** halaman `/dashboard/judges` penuh (index/create/show/edit — pola Teams). Tab baru **"Penjurian"** di `events/show` — per kategori: daftar round + form tambah, per round: tree kriteria (root+subcriteria terindentasi) + form tambah dengan pilihan "sub dari" dibatasi ke root criteria round yang sama.

**Catatan wajib untuk Sprint 9:** Round/Criteria destroy **masih bebas** (belum ada `scores` yang bergantung) — begitu Sprint 9 (Scoring) bikin tabel `scores`, WAJIB tambah guard yang sama seperti `EventCategoryController::destroy` (tolak hapus + flash error kalau sudah ada skor tersimpan), jangan biarkan `QueryException` mentah atau lebih parah menghapus skor diam-diam.

**Verifikasi:** Pint `--test` pass · **233 test hijau** · `migrate:reset`+`migrate` bersih · render manual `php artisan serve` + curl: tab Penjurian tampil round+criteria tree, sidebar dikonfirmasi TIDAK menampilkan "Juri" (dicek langsung di HTML, bukan asumsi), **percobaan bikin subcriteria-dari-subcriteria via curl nyata ditolak** dengan pesan benar (konsisten hasil test otomatis). Halaman Judges (index/create/show) semua 200.

**Catatan flake test (bukan defect):** `ScheduleSlot/ManageScheduleTest::test_category_from_another_event_is_rejected` sempat gagal 2x di run full-suite (233 test) tapi **selalu lolos** di run terisolasi + run ulang — dicurigai flake infrastruktur test pada kombinasi besar, bukan regresi dari Sprint 6/7 (test itu sendiri tidak diubah). Dicatat di sini transparan, tidak diabaikan diam-diam.

**Next steps:** Sprint 8 (Judge Assignment) untuk lanjut Phase D · atau Sprint 4b (CSV import) · atau frontend catch-up.

---

## 2026-09-14 — Backend Sprint 6 (Schedule) + tab Jadwal frontend, paralel

**Progress:** Backend **Sprint 1-6 selesai — Phase A & C tuntas** (Phase B alur manual tuntas, CSV = Sprint 4b). **199 test hijau di MariaDB (XAMPP)** (183 + 16 Schedule). Dikerjakan atas instruksi "lanjut secara paralel untuk sprint dan frontend-nya" — backend Sprint 6 + tab frontend-nya sekaligus, pola sama Sprint 3-5.

**Diselesaikan:**
- **SRS `_docs/srs/fr-schedule.md`** — domain lebih ringan (logistik, bukan histori kompetisi), jadi 2 keputusan sengaja **membalik** pola sprint sebelumnya: (A) semua field slot **boleh diedit bebas** setelah dibuat (beda dari Event/Registration yang mengunci field identitas), (B) **hard delete diizinkan** (satu-satunya resource di project ini yang punya route destroy, selain sub-resource kecil seperti roster/dokumen/staff).
- **Backend Sprint 6**: migrasi 14 `event_schedule_slots` (CASCADE dari event, SET NULL dari category/entry — general slot tidak ikut hilang kalau category/entry-nya dihapus). Enum `ScheduleSlotType` (6). Model `EventScheduleSlot`. `EventPolicy::manageSchedule`. `ScheduleService` (create=append ke akhir list, update=bebas, moveUp/moveDown=tukar `display_order` dengan tetangga). Controller + route (`events/{event}/schedule/*`, termasuk `destroy`). 1 factory. **3 test file / 16 test**: ManageSchedule, TenantIsolation, DatabaseIntegrity.
- **Frontend**: tab "Jadwal (N)" di `events/show` — form tambah slot (judul/jenis/kategori opsional/venue/waktu) + list linear terurut dengan tombol ↑/↓/hapus per baris. Tidak ada picker `competition_entry_id` di UI (kolom ada, backend validasi ada, picker ditunda — konsisten pola Sprint 3 poster/Sprint 4 per-doc review).

**🐛 Bug nyata ditemukan + diperbaiki (bukan lewat test — lewat klik manual di browser):** `ScheduleService::moveUp()`/`moveDown()` query lewat `$event->scheduleSlots()` (relasi yang punya `orderBy('display_order')` ASC bawaan), lalu `moveUp()` coba `orderByDesc()` di atasnya. **Eloquent `orderBy()` itu aditif, bukan mengganti** — ASC bawaan tetap menang, jadi `moveUp()` nuker slot dengan yang paling jauh (bukan tetangga langsung) begitu ada ≥3 slot. Test otomatis awal cuma pakai 2 slot dan **tidak bisa mendeteksi bug ini** (dengan 2 baris, "tetangga" dan "baris manapun yang cocok" itu baris yang sama — false negative). Ketahuan pas verifikasi manual 3-slot di browser. **Fix:** query langsung ke model (`EventScheduleSlot::query()->where('event_id', ...)`), hindari relasi yang bawa `orderBy` implisit. **Regression test ditambahkan** (`test_move_up_with_three_slots_swaps_only_the_immediate_neighbour`) supaya kelas bug serupa kepegang otomatis ke depan. **Pelajaran ditulis di SRS** untuk sprint berikutnya.

**Catatan:** satu kali full-suite run (199 test) sempat nunjuk 1 gagal, lolos lagi di 2 run berikutnya + run terisolasi — dicurigai flake `fake()->unique()` pada kombinasi besar, bukan defect Sprint 6.

**Verifikasi:** Pint `--test` pass · 199 test hijau (setelah fix) · `migrate:reset`+`migrate` bersih · render manual `php artisan serve` + curl: tab Jadwal tampil 3 slot, move-up diverifikasi ULANG lewat HTTP nyata (bukan cuma test) untuk mastiin fix beneran kepakai di jalur produksi, bukan cuma di test environment.

**Next steps:** Sprint 4b (CSV import) · Sprint 7-10 (Judging — paling sensitif) · atau frontend catch-up (F2 Dashboard, F5 Workspace shell proper, F7 Categories drawer).

---

## 2026-09-14 — Database lokal dipindah ke XAMPP

**Perubahan:** atas pertanyaan Ersa ("akun baru nyimpan ke mana, bisa ke XAMPP?") dan permintaan lanjutan, database lokal dipindah dari Homebrew MySQL (port 8065, setup 2026-09-10) ke **XAMPP MariaDB (port 3306)**.

**Kendala teknis dicatat:** menyalakan MySQL XAMPP butuh `sudo` (password admin Mac via prompt interaktif) — Claude/AI **tidak bisa** menjalankan ini sendiri di lingkungan non-interaktif. Ersa menyalakan manual lewat XAMPP Manager app, baru Claude lanjutkan sisanya.

**Dikerjakan:**
- Database `siapgrak` ternyata **sudah ada** di XAMPP (kosong, aman dipakai) — dibuat `siapgrak_testing` baru untuk phpunit.
- `.env`: `DB_PORT` 8065 → **3306**. Host/user/password tidak berubah (`127.0.0.1`, `root`, tanpa password — sama-sama default lokal).
- Verifikasi TCP `127.0.0.1:3306` (bukan cuma socket) — awalnya `lsof` sempat nunjuk "tidak ada listener" tapi itu keterbatasan izin `lsof` (mysqld XAMPP jalan sebagai user `_mysql`, beda dari user terminal) — dikonfirmasi koneksi TCP asli via `mysql --protocol=TCP` + `nc`, sukses.
- `migrate:fresh` di database XAMPP: 14 migrasi bersih. **Full test suite: 183 test tetap hijau** di MariaDB 10.4.17 (termasuk `UniqueConstraintViolationException` yang dipakai Sprint 5 — kelas exception yang sama berlaku baik di MySQL maupun MariaDB).
- **Verifikasi end-to-end nyata:** register akun via HTTP (`POST /register`) → dicek langsung row-nya muncul di `mysql -u root siapgrak` XAMPP. Berhasil, lalu di-`migrate:fresh` lagi biar bersih.
- `operations/deployment.md` §Local Development ditulis ulang (XAMPP jadi cara resmi, Homebrew MySQL dicatat sebagai riwayat, config-nya dibiarkan tapi disebut tidak dipakai lagi — service boleh di-stop kalau mau).
- **Bonus:** data sekarang bisa dilihat lewat **phpMyAdmin bawaan XAMPP** (`localhost/phpmyadmin`) tanpa setup tambahan — ini alasan utama pindah.

**Belum dilakukan:** Homebrew MySQL service (port 8065) dibiarkan tetap jalan (tidak di-stop) — tidak mengganggu, tapi nganggur. Boleh `brew services stop mysql` kalau mau beresin, bukan blocker apa pun.

---

## 2026-09-11 — Backend Sprint 5 (Competition Entry + Number)

**Progress:** Backend **Sprint 1-5 selesai — 183 test hijau di MySQL** (159 + 24 CompetitionEntry). Phase A tuntas, Phase B alur manual tuntas, **Phase C sebagian** (entry+nomor selesai, Schedule/Sprint 6 belum). Pint `--test` pass, migrasi `reset`+`migrate` bersih.

**Diselesaikan sesi ini (instruksi "lanjut" — dipilih Sprint 5 sebagai kelanjutan sekuensial, bukan 4b/F5):**
- **SRS `_docs/srs/fr-competition-entry.md`** — Keputusan A-J (entry otomatis+idempoten saat approve, entry ikut withdrawn saat registrasi ditarik kecuali sudah final, nomor manual+sequential atomic, isolasi nomor per kategori, status 8-state hari-H, owner/admin gate, no separate policy). Status → Implemented.
- **Backend Sprint 5:**
  - Migrasi 13: `competition_entries` (semua FK RESTRICT — histori kompetisi, UNIQUE `registration_id`, UNIQUE `(event_category_id, competition_number)`).
  - Enum `CompetitionEntryStatus` (8). Exception `InvalidCompetitionEntryStatusTransition`.
  - Model `CompetitionEntry` (`HasPublicId` `entry_`) + relasi `competitionEntries()` di Event/EventCategory/Team, `entry()` (HasOne) di Registration.
  - `EventPolicy::manageCompetition` (owner/admin, no separate CompetitionEntryPolicy — pola Sprint 3/4).
  - **`CompetitionService`**: `createEntryForRegistration` (idempoten, `firstOrCreate` by `registration_id`), `syncForRegistrationWithdrawal` (cascade ke `withdrawn` kecuali entry sudah final — bypass transition map, ini cross-domain cascade bukan transisi user), `changeStatus` + peta 8-state, `checked_in_at` sekali.
  - **`CompetitionNumberService`**: `assignManual`, `generateSequential` (**`DB::transaction()` + `lockForUpdate()`** — race condition rule `nfr.md`).
  - **`RegistrationService` di-hook**: `changeStatus()` sekarang panggil `CompetitionService` saat target `approved` (create entry) atau `withdrawn` (sync entry) — **tidak ada regresi** di 85 test Sprint 3/4 yang sudah ada.
  - 3 Form Request, `CompetitionEntryController` (`generateNumbers`/`updateNumber`/`updateStatus`; unique-violation saat generate ditangkap jadi flash error, bukan 500).
  - Route: `events/{event}/competition/numbers/generate` + `competition-entries/{entry}/{number,status}`.
  - 1 factory (chained: event→category→team→registration→entry tetap konsisten, pola `RegistrationFactory`). **5 test file / 24 test:** AutoCreateEntry (approve→entry, idempoten, withdraw-cascade, entry final tak tersentuh), CompetitionNumber (manual, duplikat ditolak per-kategori, sequential skip yang sudah bernomor), EntryStatus (8-state, terminal, check-in timestamp), TenantIsolation, DatabaseIntegrity.
- **Frontend:** tab "Kompetisi (N)" di `events/show` — per kategori: form Generate Nomor (owner/admin) + tabel entry (nomor/team/status/aksi inline: edit nomor + ubah status).

**Bug ketemu (di TEST, bukan aplikasi):** 4 test awal salah pakai `->update(['status'=>...])`/`update(['competition_number'=>...])` — kena guard `$fillable` yang sengaja tidak memasukkan kolom itu (pola sama `RegistrationDocument::file_path` Sprint 4). Diperbaiki jadi assignment langsung + `save()`. Kode aplikasi tidak pernah salah.

**Verifikasi:** Pint `--test` pass · **183 test hijau di MySQL** · `migrate:reset`+`migrate` bersih · render manual `php artisan serve` + curl: approve 2 registrasi via service → 2 entry otomatis (bukan lewat tombol), generate nomor A01/A02 real, check-in mengubah status+timestamp, tab Kompetisi tampil benar. **Belum diklik browser foreground.**

**Catatan jujur:** race-condition atomic (`DB::transaction`+`lockForUpdate`) **diimplementasikan tapi tidak diuji dengan concurrency sungguhan** — PHPUnit satu proses tidak bisa simulasikan itu; test cuma buktikan hasil alokasi benar. Tab-reset-ke-Overview setelah redirect (rough edge yang sama sejak Sprint 3) belum diperbaiki — nunggu solusi seragam di F5.

**Next steps:** Sprint 6 (Schedule, `event_schedule_slots`) untuk tuntaskan Phase C. Atau Sprint 4b (CSV import). Atau frontend F5 (Event Workspace shell — sekarang `events/show` sudah 5 tab: Overview/Peserta/Kategori/Kompetisi/Staff, saatnya dipikir ulang jadi shell besar produk kalau mau).

---

## 2026-09-10 — Backend Sprint 4 (Registration, admin-entry)

**Progress:** Backend **Sprint 1-4 selesai — 159 test hijau di MySQL** (119 + 40 Registration). Phase A tuntas, Phase B alur manual tuntas. **CSV/Excel import dipecah ke Sprint 4b** (keputusan Ersa). Pint `--test` pass, migrasi `reset`+`migrate` bersih.

**Diselesaikan sesi ini:**
- **SRS `_docs/srs/fr-registration.md`** — Keputusan A-K (admin-entry, `submitted_by` generik, 6-state lifecycle + peta, editable hanya draft/revision, roster snapshot, dokumen privat, owner/admin gate, no quota enforce, no hard delete, UNIQUE `(event_category_id, team_id)`). Status → Implemented + §Catatan Implementasi.
- **Backend Sprint 4:**
  - Migrasi 10/11/12: `registrations` (FK RESTRICT ke event/category/team — Constitution, UNIQUE `(event_category_id, team_id)`, `submitted_by`/`reviewed_by` SET NULL), `registration_members` (`team_member_id` SET NULL, name/role/gender snapshot), `registration_documents` (`file_path` disk `local` privat).
  - Enum `RegistrationStatus` (6), `RegistrationDocumentStatus` (3). Exception `InvalidRegistrationStatusTransition`.
  - Model `Registration` (`HasPublicId` `reg_`, `isEditable()`), `RegistrationMember`, `RegistrationDocument`. +relasi `registrations()` di `Event`/`EventCategory`/`Team`.
  - `RegistrationPolicy` (view + updateStatus, pola `EventPolicy`) + `EventPolicy::manageRegistrations`. `event_staff.role` masih belum jadi gate (Keputusan G).
  - `RegistrationService`: `create` (status draft, submitted_by), `changeStatus` (peta transisi + `submitted_at` sekali + `reviewed_at`/`reviewed_by`/`review_notes` pada review outcome), `allowedTransitions`.
  - 4 Form Request (`app/Http/Requests/Registration/`). 3 controller: `EventRegistrationController` (index/create/store/show/updateStatus), `RegistrationMemberController` (store/destroy), `RegistrationDocumentController` (store/**download** ber-Policy/destroy). `EventCategoryController::destroy` +guard "kategori ber-registrasi tak bisa dihapus" (flash `error`).
  - Routes nested: `events/{event}/registrations/*` + `registrations/{registration}/{members,documents}/*`. **No destroy** untuk registrasi (withdraw via status).
  - 3 factory. **6 test file / 40 test:** Create, Lifecycle, Roster (snapshot bertahan walau team_member dihapus), Document (disk privat, download owner-vs-outsider, mime, editable guard), TenantIsolation (adversarial), DatabaseIntegrity (UNIQUE, FK RESTRICT, cascade, public_id). +1 test di `EventCategoryTest` (hapus kategori ber-registrasi → error).
- **Frontend (UI fungsional, F6 polish nyusul):** tab "Peserta (N)" di `events/show` → `registrations.index`. `registrations/{index,create,show}` + `partials/{roster,documents}`. `status-badge` +6 status registrasi. `flash-messages` +varian `error`.

**Verifikasi:** Pint `--test` pass · **159 test hijau di MySQL** · `migrate:reset`+`migrate` bersih · render `php artisan serve` + curl: event show (tab Peserta), reg index/filter/create/show semua 200, status control + review_notes + roster/dokumen sections render, tak ada ViewException. Upload/download dokumen diverifikasi lewat test (`Storage::fake`). **Belum diklik browser foreground.**

**Catatan:** `file_path` sengaja tak masuk `$fillable` (server-computed) — controller set eksplisit. `db:seed` masih broken (pre-existing). Per-document approve/reject + team_member picker roster = ditunda ke F6/sprint terkait.

**Blocker/keputusan Ersa:** **Siapa lanjut Sprint 5 (Competition Entry + Number) atau Sprint 4b (CSV import)?** Track split `BACKEND-HANDOFF.md` masih nominal — sesi ini sudah kerjakan Sprint 3+4 atas instruksi. B2 (auth SSO) & B3 (hosting) tetap terbuka.

**Next steps:** Sprint 5 — `competition_entries` (approved registration → entry otomatis + competition number sequential atomic + performance order + status lifecycle hari-H). SRS `fr-competition-entry.md` dulu. ATAU Sprint 4b (CSV import). ATAU frontend F5/F6.

---

## 2026-09-10 — Backend Sprint 3 (Event + Category + Staff) + MySQL lokal + F4 jadi nyata

**Perubahan arah (instruksi Ersa):** "jangan bikin prototype, kalo Sprint belum ada buat Sprint sekalian." Track backend/frontend digabung untuk sesi ini — Sprint 3 dibangun penuh, prototype `/dev/events` dibongkar.

**Progress:** Backend **Sprint 1-3 selesai — Phase A tuntas. 119 test hijau di MySQL** (74 lama + 45 Event). Frontend F0+F1+F3+F4 (F4 sekarang di atas backend nyata, bukan mock). Pint clean, migrasi up/down (`migrate:reset` + `migrate`) bersih.

**Diselesaikan sesi ini:**
- **B1 resolved — MySQL lokal.** Homebrew MySQL 26.x di **port 8065** (XAMPP MariaDB sudah pakai 3306). `/opt/homebrew/etc/my.cnf` diset. DB `siapgrak` (dev) + `siapgrak_testing` (phpunit). `.env` + `phpunit.xml` → `mysql`. Detail: `operations/deployment.md` §Local Development. Roadmap B1 → ✅.
- **SRS `_docs/srs/fr-event-category.md`** (baru) — Keputusan A-H, dikonfirmasi Ersa. Status → Implemented + §Catatan Implementasi (H direvisi: tak ada CategoryPolicy/StaffPolicy terpisah; B direvisi: invalid transition = validation error bukan 422).
- **Backend Sprint 3:**
  - Migrasi 07/08/09: `events` (7-state status, `organization_id` RESTRICT, `created_by` SET NULL, `event_series_id`/lat-long/poster-pipeline **di-omit** — Keputusan C/D/E), `event_categories` (unique `event_id`+`code`, cascade), `event_staff` (unique `event_id`+`user_id`, cascade).
  - Enum `EventStatus` (7), `EventStaffRole` (5). Exception `InvalidEventStatusTransition`.
  - Model `Event`/`EventCategory`/`EventStaff` (+ `HasPublicId` prefix `evt_`, casts, relasi).
  - `EventPolicy` (pola `TeamPolicy` — role dari `event->organization->memberships`). Kategori/staff digate lewat `manageCategories`/`manageStaff` + `abort_unless($child->event_id === $event->id, 404)`.
  - `EventService`: `create` (status draft, created_by), `update`, `changeStatus` (peta transisi + `published_at` sekali), `allowedTransitions`, `assignableOrganizationIds`.
  - 7 Form Request (`app/Http/Requests/Event/`). 3 controller (`EventController` incl. `updateStatus`, `EventCategoryController`, `EventStaffController`). Routes: `resource events` (except destroy) + `events/{event}/status` + nested `categories`/`staff`.
  - 3 factory. **7 test file / 45 test:** Create, Update, EventStatus, EventCategory, EventStaff, TenantIsolation (adversarial 10x), DatabaseIntegrity (FK RESTRICT/CASCADE, unique, public_id).
- **Frontend rewire (F4 → nyata):** `Dev/EventPrototypeController` + `dev/events` route **dihapus**. `resources/views/dashboard/events/{index,create,edit,show}` + `partials/{date-range,categories,staff}` dipakai `EventController` asli. `create` buang field poster. `show` = Event overview: meta + kontrol ubah-status + tab Overview/Kategori/Staff (add+delete inline; edit-kategori & ganti-role = F7/nanti, endpoint siap). `status-badge` + `flash-messages` (varian `info`) sudah ada dari F4.
- **Nav:** "Events" ditambah ke `sidebar.blade.php` (atas Teams) + `mobile-nav.blade.php` (4 tab sekarang: Events/Teams/Organizations/Lainnya — reshuffle Dashboard/Events/Teams/More menyusul di F2).

**Verifikasi:** Pint `--test` (seluruh codebase) pass · 119 test hijau di MySQL · `migrate:fresh` + `migrate:reset` + `migrate` bersih · render manual `php artisan serve` + curl: index/filter/create/show/edit semua 200, badge 7-status benar, tab Overview/Kategori/Staff, transisi status valid (302→show) & invalid (302 back + error "Tidak bisa mengubah status"), add kategori via POST (count 2→3), sidebar punya "Events". Tidak ada ViewException/undefined var.

**Catatan / batas:** **Belum diklik di browser foreground** (kendala pane sama seperti F0/F1/F3) — interaksi tab Alpine + dropdown status belum di-klik manual. `php artisan db:seed` masih broken (pre-existing, `WithoutModelEvents`) — tidak difix sesi ini, tidak menghalangi apa pun (test & seed manual pakai factory).

**Blocker/keputusan Ersa:** **Siapa lanjut Sprint 4 (Registration)?** Track split di `BACKEND-HANDOFF.md` masih nominal — sesi ini menyimpang atas instruksi. Kalau tim dev lanjut, mereka baca `fr-event-category.md` sebagai pola SRS ke-3. Kalau sesi Claude lanjut, konfirmasi dulu. B2 (auth SSO) & B3 (hosting) tetap terbuka, belum menghalangi.

**Next steps:** Sprint 4 — Registration admin-entry (SRS `fr-registration.md` dulu; `submitted_by` kompatibel self-registration; snapshot roster; private doc storage). Paralel frontend: F5 Event Workspace shell (perluas `events/show` jadi shell besar) atau F6 Participants begitu Sprint 4 ada.

---

## 2026-09-10 — F4 Event List + Create (prototype)

**Progress:** Frontend F0 + F1 + F3 + **F4 selesai — 4 dari 13 step**. Backend tetap Sprint 1-2 (74 test hijau, tidak disentuh). Lanjut dari entry F3 hari yang sama di bawah.

**Diselesaikan sesi ini:**
- **F4 — Event List + Create** sebagai **prototype visual di atas mock data** (backend Event = Sprint 3, belum ada). Pola: view di path produksi final (`resources/views/dashboard/events/`), route + controller **dev-only** yang `abort_unless local` — persis pola `/dev/ui`. Begitu Sprint 3 landing, tim dev bikin `EventController` + resource route asli, view tidak berubah (`frontend-plan.md` §8 Backend Data Contract).
- **File baru:**
  ```text
  app/Http/Controllers/Dev/EventPrototypeController.php   — index/create/store/show, semua 404 di luar local;
                                                            sampleEvents() = 6 baris mock, key = kolom `events` final
                                                            (research/chatgpt-blueprint-v1.md §9), cover 7 status
  resources/views/dashboard/events/index.blade.php        — table ≥md + card <md (Event/Tanggal/Venue/Status/Peserta/Kategori),
                                                            filter search+status (7 status), 2 varian empty state
  resources/views/dashboard/events/create.blade.php       — form 3 section (Informasi / Tanggal & Lokasi / Pendaftaran),
                                                            jangan lebih (frontend-plan.md §Events F4). Poster = file input biasa.
  resources/views/dashboard/events/show.blade.php         — stub: meta + 3 stat card + catatan "Event Workspace = F5"
  resources/views/dashboard/events/partials/date-range.blade.php — "12 Oct 2026" / "8–9 Nov 2026" dari 2 tanggal
  ```
- **Route (dev-only, di `routes/web.php` grup `auth`):** `dev.events.index/store/create/show`. **Tidak** ditambah ke sidebar (aturan: "Events" ke sidebar baru begitu backend Sprint 3 live — `frontend-plan.md` §2). Prototype diakses langsung via URL `/dev/events` seperti `/dev/ui`.
- **`status-badge` diperluas** — 7 status Event (`draft/published/registration_open/registration_closed/ongoing/finished/cancelled`) → warna semantik (registration_open→emerald, published/ongoing→sky, registration_closed→amber, cancelled→red, draft/finished→slate). Match arm ditambah di komponen, bukan di view.
- **`partials/flash-messages.blade.php`** +varian `info` (sky) — dipakai `store()` prototype ("simpan Event beneran tersedia setelah Sprint 3"), berguna umum untuk F5+.

**Keputusan (tidak eksplisit di brief):**
- **Mock via controller dev-only, bukan factory/migration.** `frontend-plan.md` §Mock Data Strategy nyebut "lanjutkan pola factory" — tapi factory butuh model+tabel = masuk scope Sprint 3 (dilarang lompat phase, `CLAUDE.md`). Kompromi: view di path final + data dari `EventPrototypeController::sampleEvents()` yang `abort_unless local`. Mock tidak pernah kena controller produksi (belum ada `EventController`). Swap ke real = hapus dev route, tambah controller asli.
- **`show` sengaja stub minimal** — Event Workspace penuh (Overview + Competition + Operations + Settings) adalah F5. F4 cukup bikin list rows bisa diklik ke suatu tempat.
- **Poster pakai `<input type=file>` biasa**, bukan `<x-ui.input>` — komponen render `value="..."` yang invalid untuk file input.

**Verifikasi:** Pint clean, `view:cache` sukses, **74 test tetap hijau** (route dev 404 di env `testing`, tidak ganggu). Render manual `php artisan serve` + curl: index (6 baris, 7 badge warna benar, date-range same-day/same-month/cross-month benar), filter status (hanya yang cocok), search kosong (empty state + tombol page-header tetap, tombol empty-state hilang), create (3 section heading + semua field + datetime-local + file), show (garuda + draft), slug ngawur → **404**, store POST → redirect index + flash `info` sky. Tidak ada ViewException/undefined var di semua halaman.

**Catatan / batas verifikasi:** sama seperti F3 — **belum diklik di browser foreground**. Verifikasi F4 sebatas render HTML + cek markup via curl. CSS bundle naik ke 46 KB (8.6 KB gzip) — ini bukan dari F4 spesifik, tapi `view:cache` meng-compile SEMUA blade (termasuk `/dev/ui` gallery) jadi build pertama yang lihat set class lengkap; ukuran wajar untuk full app.

**Next steps:** F5 — Event Workspace shell (Overview + nav COMPETITION/OPERATIONS/Settings). Ini "80% visual language produk" (`frontend-plan.md` §Event Workspace F5) — pola nav terbesar, hati-hati. Tetap prototype (backend Sprint 3+ belum ada) — extend `EventPrototypeController` atau dev route baru.

---

## 2026-09-10 — F3 Teams polish + roadmap doc + env bootstrap

**Progress:** Frontend F0 + F1 + **F3 selesai — 3 dari 13 step** (F2 Dashboard sengaja dilewati dulu sesuai urutan revisi Ersa: F3 → F4 → F5 → F2). Backend tetap Sprint 1-2 (74 test hijau, tidak disentuh sesi ini).

**Diselesaikan sesi ini:**
- **Environment bootstrap** (mesin baru, `vendor/` belum ada): `composer install`, `brew install node` (Node 26), `npm install`, `.env` + `key:generate` + SQLite + `migrate`, `npm run build`. Working tree tetap bersih (semua gitignored). **Masih SQLite** — target project MySQL 8+, jadi B1 di roadmap.
- **`_docs/status/roadmap-to-mvp.md`** (baru) — sintesis milestone M0-M7 dari kondisi sekarang sampai MVP selesai, hasil audit 30 file `_docs` + kode nyata. Di-link dari `_docs/README.md`. Bukan pengganti `tasks.md`/`BACKEND-HANDOFF.md`/`frontend-plan.md`.
- **F3 — Teams polish** (production-quality, backend Sprint 2 nyata, bukan mock):
  - List: desktop **table** (`Team / Organisasi / Lokasi / Anggota / Status / Aksi`) + **card di `<md`** — pola resmi tiap halaman list F2-F12. Filter search+status pakai `<x-ui.input>`/`<x-ui.select>`. 2 varian empty state (belum ada data → ada tombol Buat Team; hasil filter kosong → tanpa tombol, copy beda).
  - Detail: tab **Overview / Anggota** (Alpine, `x-cloak` di panel non-default). Overview = card data + placeholder Competition History (nunggu Event). Anggota = roster read-only + tombol "Tambah / Hapus" ke halaman roster.
  - Roster: tabel dipindah ke partial `dashboard/teams/partials/roster.blade.php` — dipakai read-only di tab Anggota + dengan aksi hapus di halaman `/members` (param `$manageable`). Halaman `/members` add-form di-componentise.
  - Create/Edit: form pakai `<x-ui.input>`/`<x-ui.select>`/`<x-ui.textarea>`/`<x-ui.button>` (old()+error built-in) — field/nama/route tidak berubah.
  - Komponen baru: `<x-ui.status-badge :status>` — memetakan enum status → warna badge di satu tempat (extensible untuk status Event/Registration di F4+).
  - Controller: `TeamController@index` +`withCount('members')`, `@show` eager-load `organization`+`members` (hindari N+1). Tidak ada perubahan policy/validation/schema.
- **Verifikasi:** Pint clean, `view:cache` sukses (semua Blade compile), **74 test tetap hijau**, render manual via `php artisan serve` + curl 8 halaman/state (index populated desktop+mobile, kedua empty state, show 2 tab, members manageable + empty roster, create, edit) — semua 200, tidak ada ViewException/undefined var, badge warna benar (active→emerald, inactive→slate), member count benar, filter-empty tidak memunculkan tombol aksi.

**Catatan / batas verifikasi:**
- Loading/error state (`frontend-plan.md` §6) **tidak diterapkan** di list Teams — ini halaman SSR tanpa fetch async, jadi tidak ada state "loading"/"error" yang nyata. Komponen `<x-ui.skeleton>`/`<x-ui.error-state>` (F0) disiapkan untuk F6+ (drawer detail participant yang fetch async).
- Verifikasi visual sebatas render HTML + cek markup via curl — **belum diklik langsung di browser foreground** (kendala pane sama seperti F0/F1). Interaksi tab Alpine (`x-show`/`x-cloak`) belum di-klik manual. B4 di roadmap (Ersa klik-test F0/F1 + sekarang F3) masih relevan.
- `php artisan db:seed` (DatabaseSeeder) **broken** — `WithoutModelEvents` mematikan hook `HasPublicId` → `users.public_id` NOT NULL gagal. Bukan disebabkan sesi ini, tapi ketahuan saat bikin data demo. Test pakai factory langsung jadi tidak kena. Worth difix tim dev (kecil).

**Blocker/keputusan dari Ersa:** lihat `_docs/status/roadmap-to-mvp.md` §4 (B1-B6). Yang paling menahan langkah berikutnya: **B1** (MySQL lokal vs SQLite) sebelum backend Sprint 3 nulis migration, dan **B4** (klik-test manual F0/F1/F3).

**Next steps:** F4 — Event List + Create prototype (mock data, shape = kolom migration final `events`/`event_categories` di `research/chatgpt-blueprint-v1.md`). Lihat `frontend-plan.md` §Events (F4) — form Create 3 section, jangan lebih. Paralel: tim dev bisa mulai Sprint 3 (tulis `_docs/srs/fr-event-category.md` dulu).

---

## 2026-09-09 — F1 Admin Shell selesai

**Progress:** Backend Sprint 1-2 selesai (74 test hijau, PASS-review, tim dev lanjut Sprint 3-13 — lihat `BACKEND-HANDOFF.md`). Frontend: F0 (Design Foundation) + F1 (Admin Shell) selesai — **2 dari 13 step** build order frontend.

**Diselesaikan sesi ini (F1 — Admin Shell):**
- 6 komponen layout baru (`resources/views/components/layout/`): `sidebar`, `topbar`, `mobile-nav`, `account-menu`, `page-header`, `breadcrumb`
- Struktur nav final: sidebar desktop (brand + org context + nav + account bawah), topbar+bottom-nav mobile (3 tab: Teams/Organizations/Lainnya, TANPA hamburger/slide-out)
- Account menu satu komponen reusable dipakai di 2 tempat (sidebar + bottom-nav "Lainnya"), bukan dropdown terpisah
- Semua 8 halaman Sprint 1-2 dimigrasi ke `<x-layout.page-header>` (+ breadcrumb di show/edit/members) — isi form/table BELUM disentuh, itu scope F3
- Verifikasi: Pint clean, 74 test tetap hijau, manual check browser 1280px (desktop) + 375px (mobile) — nav aktif benar, tidak ada horizontal overflow, konten tidak ketutup bottom nav

**Keputusan yang diambil sendiri (didokumentasikan di frontend-plan.md §F1):**
- Organization context masih teks statis (nama org pertama user), BUKAN switcher aktif — backend belum punya konsep "active organization"/session state, dan ini sesuai instruksi Ersa untuk tidak bangun switcher kompleks dulu

**Blocker/keputusan yang dibutuhkan dari Ersa:**
- **Verifikasi visual manual belum tuntas** — Browser pane tool sempat backgrounded di sesi F0 maupun F1, jadi Modal/Drawer (F0) dan account-menu dropdown (F1) belum diklik langsung dengan pane foreground (root cause dikonfirmasi via `document.hidden`/Alpine state, bukan bug — tapi tetap perlu satu kali klik manual sungguhan). **Minta Ersa coba klik-coba sendiri di browser** sebelum F3 bikin fitur penting bergantung ke pola dropdown/modal ini.
- Tidak ada keputusan arsitektur baru yang butuh approval — F1 murni struktural/visual sesuai brief.

**Next steps:** **F3 — Teams polish** (halaman production-quality pertama, karena backend Team sudah nyata dari Sprint 2, bukan mock). Urutan setelahnya: F4 Event List/Create (mock) → F5 Event Workspace shell → F2 Dashboard → F6-F12.

Referensi: commit `ab292dc` (F0), `846d27f` (F1). Detail lengkap di `_docs/frontend/frontend-plan.md` §F0/§F1 Implementation Log.
