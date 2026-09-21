# Design Doc — Import CSV Peserta (Phase B, Sprint 4b)

**Status:** Implemented (2026-09-16)
**Author:** Claude
**Tanggal:** 2026-09-16
**Kontrak:** `tasks.md`: "Sprint 4b — Import CSV/Excel peserta (dipecah dari Sprint 4 atas keputusan Ersa 2026-09-10). Column mapping + preview + commit. Butuh lib parsing." · `fr-registration.md` Non-Goals Sprint 4.
**Definition of Ready:** ✅ — Sprint 4 selesai (alur manual sudah jalan, roster/status/dokumen semua ada). MVP backend (Sprint 1-13) sudah tuntas — ini kerja lanjutan opsional, bukan blocker.

## TL;DR

Panitia upload file CSV berisi daftar peserta (satu baris = satu anggota, dikelompokkan per nama tim) untuk SATU event+kategori sekaligus. Alur 3 langkah: **Upload** (pilih kategori + file) → **Mapping** (cocokkan kolom CSV ke field Siapgrak, ditebak otomatis) → **Preview & Commit** (lihat hasil validasi per kelompok tim, baris error dilewati bukan memblokir semua, baru commit). Registrasi yang berhasil diimpor langsung berstatus `submitted` (bukan `draft`) — konsisten dengan maksud "panitia sudah punya data siap, tinggal masuk antrean review", bukan "mulai dari nol".

**Requirement ID:** `FR-REGISTRATION-IMPORT-01`

## Keputusan Desain Kritis

**A. CSV saja, Excel (.xlsx) TIDAK didukung di v1** — walau nama sprint di `tasks.md` menyebut "CSV/Excel". Parsing CSV native PHP (`fgetcsv`) butuh **nol dependency baru**; parsing `.xlsx` asli butuh library berat (PhpSpreadsheet atau maatwebsite/excel yang membungkusnya) untuk kompleksitas (format ZIP+XML, multi-sheet, formula) yang tidak sepadan dengan kebutuhan nyata — semua tool spreadsheet (Excel, Google Sheets, Numbers) sudah punya tombol "Export as CSV" bawaan. Konsisten Karpathy #2 (Simplicity First) dan filosofi project "lean, bukan generic-platform". Kalau nanti Excel asli benar-benar dibutuhkan, itu keputusan terpisah (nambah dependency) — pipeline mapping/preview/commit di sini tidak perlu berubah, cuma langkah parsing-nya diganti.

**B. Tim HARUS SUDAH ADA — import TIDAK membuat Team baru.** Alur manual (`EventRegistrationController::create`) sendiri sudah mensyaratkan pilih team dari dropdown existing (`Team::whereIn('organization_id', ...)`), tidak ada opsi "buat tim baru" bahkan di form manual. CSV import ikut pola yang sama: baris dengan nama tim yang tidak cocok (case-insensitive) dengan Team manapun di organisasi ini ditandai **invalid, dilewati saat commit** — bukan diam-diam membuat Team baru dari data CSV yang minim (Team py field lain: `short_name`, `city_code`, `province_code`, dll yang tidak ada di CSV peserta). Mencegah proliferasi Team duplikat/typo dari kesalahan ketik di spreadsheet.

**C. Baris CSV yang di-import TIDAK ditautkan ke `team_members`** — `RegistrationMember` dibuat langsung dari data CSV (`name`/`member_role`/`gender`), `team_member_id` tetap NULL. Ini konsisten `fr-registration.md` Keputusan D: "name/member_role/gender adalah source of truth, team_member_id cuma provenance opsional". Mencocokkan nama CSV ke `team_members` yang sudah ada (fuzzy matching nama) menambah kompleksitas nyata untuk manfaat yang tidak esensial — roster snapshot sudah lengkap tanpa itu.

**D. Registrasi hasil import langsung `submitted`, BUKAN `draft`.** Alur manual single-entry sengaja mulai dari `draft` (representasi "staf masih menyusun roster, belum siap direview"). Tapi maksud import massal itu KEBALIKANNYA — panitia sudah mengumpulkan data lengkap (form kertas/spreadsheet), mau masukkan resmi sekaligus banyak. Kalau semua hasil import mendarat di `draft`, panitia harus klik submit satu-satu untuk RATUSAN baris — meniadakan manfaat "impor massal" itu sendiri. Diimplementasikan dengan reuse `RegistrationService::create()` lalu `changeStatus()` ke `Submitted` — bukan bypass logic, tetap lewat validasi transisi yang sama.

**E. Granularitas validasi: per KELOMPOK TIM untuk keputusan go/no-go registrasi, per BARIS untuk anggota individual.** Tim tidak ditemukan / tim sudah py registrasi di kategori ini (unique constraint) → SELURUH kelompok baris tim itu dilewati. Tapi DALAM kelompok yang valid, baris anggota dengan nama kosong cuma di-skip individual (tidak menggagalkan seluruh registrasi tim itu) — supaya satu baris rusak tidak membuang seluruh roster tim yang datanya sebagian besar benar.

**F. Commit bersifat PARTIAL, bukan all-or-nothing.** Baris/kelompok invalid dilewati dengan ringkasan di akhir ("N registrasi berhasil, M kelompok dilewati") — bukan menolak seluruh file kalau ADA satu baris salah. Lebih berguna untuk file ratusan baris nyata (LKBB events bisa punya puluhan tim) dibanding all-or-nothing yang memaksa panitia membersihkan file sempurna dulu sebelum bisa impor apa pun.

**G. File CSV sementara disimpan di private disk (`local`), bukan session.** Alur 3-langkah (upload → mapping → commit) butuh data yang sama dibaca ulang di request berikutnya. Menyimpan seluruh isi CSV di session (driver `database` di project ini) berisiko membengkakkan baris session untuk file berukuran wajar; file sementara di disk (pola sama `RegistrationDocument`, Constitution: dokumen sensitif tidak pernah public URL) lebih murah dan konsisten. Token akses = ULID di nama file, path disertakan `event_id` (`imports/{event_id}/{ulid}.csv`) — dicek match event dari route sebelum dibaca. **Tidak ada pembersihan otomatis untuk file yang ditinggal di tengah alur** (mis. panitia upload lalu tidak lanjut mapping) — keterbatasan yang diketahui untuk v1, dampaknya kecil (file kecil, hilang saat storage displaisin/cache dibersihkan manual, bukan risiko keamanan karena tidak reachable tanpa token acak).

**H. Delimiter CSV dideteksi otomatis (koma ATAU titik-koma)** — Excel di locale Indonesia sering export pakai titik-koma (karena koma dipakai sebagai desimal), bukan format default Amerika. Deteksi: hitung kemunculan `,` vs `;` di baris header, pakai yang lebih banyak. Sederhana, tidak butuh library deteksi delimiter yang canggih.

**I. Tidak ada validasi kuota (`min_members`/`max_members` kategori) saat commit** — konsisten `fr-registration.md` Keputusan H (quota enforcement DEFERRED, belum ada di alur manual manapun). Import tidak menambah pembatasan yang tidak ada di jalur manual.

## Alur & Routes
```text
GET  events/{event}/registrations/import                 upload form (pilih kategori + file .csv)
POST events/{event}/registrations/import                 simpan file sementara, ekstrak header, redirect ke mapping
GET  events/{event}/registrations/import/{token}/map      form mapping kolom (tebakan otomatis dari nama header)
POST events/{event}/registrations/import/{token}/map      terapkan mapping + validasi, render halaman preview
POST events/{event}/registrations/import/{token}/commit   validasi ULANG (defense-in-depth) + buat records, hapus file sementara
```

Field yang di-mapping: `team_name` (wajib), `member_name` (wajib), `gender` (opsional), `member_role` (opsional).

## Business Logic
```text
RULE-1  File wajib .csv, maks 2MB (pola sama batas upload RegistrationDocument).
RULE-2  Baris dikelompokkan per team_name (case-insensitive, trim).
RULE-3  Kelompok invalid (skip total): team_name kosong / tim tidak ditemukan di organisasi event ini /
        tim sudah py registrasi di kategori ini / tidak ada satupun baris anggota valid dalam kelompok.
RULE-4  Dalam kelompok valid: baris dengan member_name kosong di-skip individual, sisanya tetap diproses.
RULE-5  Commit: per kelompok valid → Registration::create (draft) → changeStatus ke Submitted →
        RegistrationMember dibuat untuk tiap baris anggota valid.
RULE-6  Commit mem-validasi ULANG dari file (bukan percaya hasil preview yang sudah di-render ke browser)
        — defense-in-depth kalau ada perubahan data (mis. tim baru dihapus) di antara preview dan commit.
```

## Permission
| Aksi | Owner/Admin (manageRegistrations) | Member |
|---|---|---|
| Upload/map/preview/commit | ✅ | ❌ |

## Testing Plan
```text
Registration/ImportTest (12 test, PASS)
  ✅ upload file valid → redirect ke halaman mapping dengan header CSV terdeteksi
  ✅ upload file bukan CSV → ditolak validasi
  ✅ mapping dengan tebakan otomatis terisi benar untuk header yang mirip
  ✅ preview: tim tidak ditemukan → kelompok ditandai invalid
  ✅ preview: tim sudah py registrasi di kategori ini → kelompok ditandai invalid (duplikat)
  ✅ preview: baris anggota nama kosong → baris itu saja di-skip, kelompok lain tetap valid
  ✅ commit: kelompok valid → registrasi dibuat langsung berstatus submitted, submitted_at terisi
  ✅ commit: roster (RegistrationMember) sesuai baris valid, team_member_id null
  ✅ commit: kelompok invalid dilewati, tidak membuat registrasi
  ✅ commit bersifat partial — sebagian sukses sebagian dilewati dalam satu file
  ✅ delimiter titik-koma terdeteksi otomatis
  ✅ member role tidak bisa akses alur import sama sekali

Registration/ImportTenantIsolationTest (1 test, PASS)
  ✅ organizer org lain tidak bisa akses token import event org lain (403 — ditolak di form request authorize())
```

Total: 369 test hijau di seluruh suite (356 sebelumnya + 13 baru), 0 regresi. Diverifikasi juga manual via curl (login → upload CSV nyata → mapping tebakan otomatis benar → preview menandai 1 kelompok valid + 1 invalid dengan benar → commit membuat 1 registration `submitted` dengan 2 roster member, `team_member_id` null, file sementara terhapus).

## File Dibuat/Diubah
```text
app/Services/RegistrationImportService.php  (storeUpload, headers, guessMapping, validateRows, commit)
app/Http/Requests/Registration/UploadImportRequest.php
app/Http/Requests/Registration/ApplyImportMappingRequest.php   (dipakai bersama step map & commit — bentuk input sama)
app/Http/Controllers/Dashboard/RegistrationImportController.php  (create, store, map, preview, commit)
resources/views/dashboard/registrations/import/{upload,map,preview}.blade.php
resources/views/dashboard/registrations/index.blade.php  (+tombol "Import CSV")
routes/web.php  (+events/{event}/registrations/import/*)
tests/Feature/Registration/ImportTest.php
tests/Feature/Registration/ImportTenantIsolationTest.php
```

## Catatan Implementasi

Dua bug ditemukan & diperbaiki sendiri sebelum testing (self-review, bukan dari test failure):
- **BOM-handling di `readRows()`**: logic awal `rewind()` tanpa syarat setelah deteksi delimiter membatalkan posisi skip-BOM yang sudah benar. Diperbaiki dengan satu variabel `$contentStart` (0 atau 3) yang dipakai konsisten di kedua `fseek()` — deteksi delimiter maupun parsing asli.
- **Double-submit di `preview.blade.php`**: draft awal punya `onclick="this.disabled=true; this.form.submit()"` pada tombol `type="submit"` — submit native ganda dengan `.submit()` manual. Diperbaiki mengikuti pola yang sudah konsisten dipakai di file lain (`judging.blade.php`): `onsubmit="this.querySelector('button[type=submit]').disabled = true;"` di elemen `<form>`.
