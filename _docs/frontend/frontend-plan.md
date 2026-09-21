# Frontend Plan — Siapgrak Admin

**Status:** SEMUA 13 STEP SELESAI 2026-09-16. Ditulis 2026-09-07. **F0 + F1 SELESAI 2026-09-09**, **F3 + F4 SELESAI 2026-09-10**, **F2 + F5 + F6 + F7 + F8 + F9 + F11 + F12 SELESAI 2026-09-16** — lihat §F0-§F4, §F2, §F5, §F6, §F7, §F8, §F9, §F11, §F12 Implementation Log. **Perubahan besar 2026-09-10:** track frontend & backend digabung atas instruksi Ersa ("jangan bikin prototype, buat Sprint sekalian") — F4 tidak lagi prototype mock, sudah backed by backend **Sprint 3** (Event+Category+Staff) yang dibangun di sesi yang sama. **Catatan urutan (2026-09-16):** rencana "F5 dulu baru F2" (ditulis 2026-09-10, sebelum Sprint 4-13 ada) sudah tidak akurat lagi — F2 dikerjakan duluan karena itu satu-satunya gap yang benar-benar kosong (lihat §F2 Implementation Log), lalu F5/F6/F7/F8/F9/F11/F12 menyusul berurutan di sesi yang sama. F10 ✅ (Sprint 9-10).

**Pembagian kerja mulai sekarang:**
```text
FRONTEND (Claude, sesi ini)     BACKEND (tim dev)
F0 → F12                         Sprint 3 → Sprint 13
Blade + Alpine + Tailwind        Database, domain, services, policies, tests
Design + UX + responsive         Lihat BACKEND-HANDOFF.md
Mock data allowed
```

Frontend TIDAK menunggu backend selesai — dibangun paralel di atas mock data dengan **shape yang sama** dengan kontrak data final (lihat §Backend Data Contract). Ketika backend Sprint-N siap, tinggal ganti sumber data, bukan re-desain halaman.

---

## 1. Design Principles

- Stack tetap **Blade + Alpine.js + Tailwind CSS** — bukan Next.js/SPA terpisah. Alasan: project sudah di jalur ini sejak Sprint 1-2, ganti stack sekarang cuma nambah biaya migrasi tanpa manfaat jelas di tahap ini.
- Reusable component di Blade (partial/component), bukan diulang copy-paste tiap halaman.
- **Create form jangan kompleks** — pisahkan "buat entity dasar" dari "setup lanjutan" (kategori, judging, dst masuk workspace, bukan form create).
- Table di desktop, **card di mobile** — jangan paksa table sempit di layar kecil.
- Empty/loading/error state wajib ada di setiap halaman list — bukan dianggap "polish nanti".
- Dashboard bukan halaman analitik ramai grafik — cukup ringkasan readiness + next action yang actionable.
- Judge interface harus jauh lebih sederhana dari organizer interface — tidak ada sidebar, tidak ada settings, cuma nilai → submit → lanjut.

## 2. Admin Shell

**Desktop:**
```text
┌───────────────┬───────────────────────────────────┐
│ SIAPGRAK      │ Header                            │
│               ├───────────────────────────────────┤
│ Dashboard     │                                   │
│ Events        │          Page Content             │
│ Teams         │                                   │
│ Organization  │                                   │
│               │                                   │
│ Settings      │                                   │
└───────────────┴───────────────────────────────────┘
```

**Mobile:** Header → Page Content → Bottom Navigation (`Dashboard / Events / Teams / More`). `More` berisi Organization/Settings/Logout.

**Catatan implementasi:** sidebar Sprint 1-2 saat ini cuma render `Teams`/`Organizations` (item yang punya route nyata — lihat `layouts/dashboard.blade.php`). "Events" ditambahkan begitu Sprint 3 backend live, bukan dirender lebih dulu sebagai link mati.

## 3. Responsive Rules

- Breakpoint mengikuti Tailwind default (`sm/md/lg`).
- Table → card di bawah `md`.
- Sidebar → stack ke atas / bottom nav di mobile (pola sudah ada di Sprint 1-2, lanjutkan).
- Form multi-kolom → 1 kolom di mobile.

## 4. Component Inventory

Primitives yang perlu dibangun reusable (Blade partials/components):
```text
Button, Icon Button
Input, Textarea, Select, Checkbox, Radio, Toggle
Badge, Alert, Tooltip
Card, Table, Tabs
Empty State, Skeleton, Pagination
Drawer, Modal, Dropdown
```

Sprint 1-2 sudah punya versi ad-hoc dari sebagian ini (form field pattern, flash message partial, table wrapper) — F0 merapikan jadi komponen reusable, bukan mulai dari nol.

### F0 Implementation Log (2026-09-09)

**Visual direction terpilih:** Minimalism/Swiss Style + Data-Dense Dashboard pattern (dipilih lewat skill `ui-ux-pro-max` — dua style ini secara eksplisit direkomendasikan untuk "enterprise apps, dashboards, admin panels" dan match dengan requirement "operational, bukan startup SaaS"). Palet warna: Government/Public Service palette — yang ternyata **persis** skala `slate` (navy/neutral) + `sky` (accent) bawaan Tailwind (`#0F172A` = `slate-900`, `#0369A1` = `sky-700`, dst) — jadi tidak ada hex baru yang diciptakan, cukup dipakai konsisten sebagai token semantik. Font: **Inter** tunggal (dipilih dari pairing "Minimal Swiss" — satu family dengan variasi weight, direkomendasikan untuk "dashboards, admin panels, enterprise apps", dan punya coverage glyph Bahasa Indonesia yang baik).

**File yang dibuat:**
```text
resources/css/app.css                          — font Inter, prefers-reduced-motion, x-cloak rule
resources/views/partials/head-fonts.blade.php   — Google Fonts <link>, di-include kedua layout
resources/js/app.js                             — Alpine.js init (BARU DIPASANG, belum ada dari scaffold)

resources/views/components/ui/
├── button.blade.php        (variant: primary/secondary/danger/ghost, size: sm/md, href-as-link)
├── badge.blade.php         (variant: neutral/info/success/warning/danger — status visibility)
├── card.blade.php
├── input.blade.php         (label+error+old() built-in)
├── select.blade.php
├── textarea.blade.php
├── table.blade.php + table-th.blade.php + table-td.blade.php
├── empty-state.blade.php
├── error-state.blade.php
├── skeleton.blade.php
├── modal.blade.php         (Alpine, shares `open` dari x-data ancestor)
└── drawer.blade.php        (Alpine, slide-over kanan, pola sama modal)

resources/views/dev/ui.blade.php  — component gallery, route /dev/ui (auth + local-env gated)
```

**Bug ditemukan+fix selama build:** teks deskripsi di gallery yang menyebut `@error()` (sebagai CONTOH, bukan kode sungguhan) ditafsirkan Blade sebagai directive nyata → parse error "unexpected end of file, expecting elseif/else/endif" di seluruh halaman. Fix: escape jadi `@@error()`. Pelajaran: teks apa pun di Blade view yang MENYEBUT nama directive (`@if`, `@error`, `@foreach`, dll) sebagai kata biasa harus di-escape dengan `@@`.

**Verifikasi:** Pint clean, 74 test Sprint 1-2 tetap hijau (no regression dari perubahan layout/CSS/JS), gallery `/dev/ui` di-render manual di browser — semua section (typography, color tokens, buttons, badges, form controls, cards, table, empty/loading/error states) tampil benar. **Catatan terbatas:** interaksi Modal/Drawer (klik tombol → Alpine toggle `open`) tidak bisa diverifikasi visual end-to-end di sesi ini karena Browser pane sempat backgrounded (`document.hidden: true`) — Chrome men-throttle CSS transition di tab tersembunyi, membuat animasi macet di state "belum keluar dari display:none" walau Alpine state (`open: true`) sudah benar. Root cause dikonfirmasi via `document.visibilityState`, bukan bug komponen — tapi **verifikasi visual manual dengan pane foreground disarankan sekali sebelum dipakai di F1+**.

### F1 Implementation Log (2026-09-09)

**Struktur shell diputuskan:** Sidebar (desktop, ≥md) berisi brand + org context + nav (Teams/Organizations, route-gated) + account row (bawah, dropdown Logout). Topbar (mobile, <md) hanya brand + org context — **tanpa hamburger**, karena nav mobile sepenuhnya lewat bottom nav, bukan slide-out drawer. Bottom nav (mobile) 3 tab: Teams, Organizations, Lainnya (Lainnya = account menu, sama seperti sidebar). Account menu adalah satu komponen reusable (`layout.account-menu`, slot `trigger` custom, panel sama) dipakai di kedua tempat — bukan dua implementasi dropdown terpisah.

**File yang dibuat:**
```text
resources/views/components/layout/
├── sidebar.blade.php       (desktop only, w-60, sticky)
├── topbar.blade.php        (mobile only, brand+org context)
├── mobile-nav.blade.php    (mobile only, fixed bottom, 3 tab)
├── account-menu.blade.php  (self-contained x-data, slot trigger, dipakai sidebar+mobile-nav)
├── page-header.blade.php   (title+description+actions slot — pattern resmi tiap halaman)
└── breadcrumb.blade.php    (items array, dipakai di halaman ≥1 level di bawah index)
```
`layouts/dashboard.blade.php` ditulis ulang untuk komposisi shell di atas (query `auth()->user()->organizations()` dihitung sekali di layout, di-share ke sidebar+topbar sebagai prop supaya tidak query dobel). Semua 8 halaman Sprint 1-2 (teams/organizations index/show/create/edit + kedua halaman members/index) diupdate memakai `<x-layout.page-header>` (dan breadcrumb di halaman show/edit/members) menggantikan header ad-hoc — isi form/table di dalamnya TIDAK disentuh (itu scope F3 Teams polish).

**Keputusan desain (tidak eksplisit di brief, diputuskan sendiri secara wajar):**
- **Organization context belum jadi switcher aktif** — backend belum punya konsep "active organization" (tidak ada session/`current_organization_id`). Ditampilkan sebagai teks statis (organisasi pertama user, atau "N organisasi" kalau >1) tanpa interaktivitas. Ini SENGAJA tidak dibangun jadi stateful switcher sekarang (sesuai instruksi Ersa: "bukan dropdown switcher kompleks dulu") — switcher nyata baru relevan begitu Events butuh org-scoping.
- **Account menu HANYA di satu tempat per breakpoint** (sidebar bawah utk desktop, tab "Lainnya" utk mobile) — topbar mobile sengaja tidak punya account trigger sendiri, supaya tidak ada dua entry point untuk hal yang sama.

**Verifikasi:** Pint clean, 74 test tetap hijau, manual browser check desktop (1280px — sidebar+nav+active state+account dropdown berfungsi) dan mobile (375px — topbar+bottom nav+active state+content tidak ketutup nav, dicek via screenshot beneran) keduanya benar. `document.documentElement.scrollWidth === clientWidth` di desktop (no horizontal overflow). **Catatan sama seperti F0:** saat mencoba klik tab "Lainnya" dengan Browser pane backgrounded, panel account-menu (pakai `x-transition`) macet di `display:none` walau Alpine state `open` sudah `true` — dikonfirmasi ulang via `document.hidden`/`Alpine.$data()` bahwa ini murni environment testing (Chrome throttle transition di tab tersembunyi), BUKAN regresi baru. Tidak sempat mendapat window pane foreground di sesi ini untuk re-check visual penuh Modal/Drawer (F0) maupun account-menu (F1) — **disarankan satu kali manual click-through oleh Ersa langsung di browser** (bukan lewat tool ini) sebelum halaman-halaman berikutnya (F3 Teams polish) bergantung ke pola dropdown/modal ini untuk aksi penting.
**Ingat rebuild asset:** perubahan Blade/CSS/JS di project ini butuh `npm run build` manual (tidak ada Vite dev server jalan) — kelupaan rebuild sempat bikin sidebar tidak muncul sama sekali saat verifikasi (class baru belum ke-compile ke `public/build`).

### F3 Implementation Log (2026-09-10)

**Scope:** poles 5 halaman Teams yang backend-nya sudah nyata (Sprint 2) jadi production-quality. Tidak menyentuh policy/validation/schema/route — murni view + 2 baris view-model prep di controller.

**File yang dibuat:**
```text
resources/views/components/ui/status-badge.blade.php   — enum/string status → warna badge, dipetakan di SATU tempat
                                                          (active→success/emerald, inactive→neutral/slate). Extensible untuk
                                                          status Event/Registration/ScoreSheet di F4+ — tambah match arm di sini, bukan di view.
resources/views/dashboard/teams/partials/roster.blade.php — tabel roster reusable (param $members, $team, $manageable)
```

**Perubahan:**
- **`teams/index`**: dari card-grid jadi **table di `≥md` + card di `<md`** (pola resmi tiap list F2-F12, sesuai §3 Responsive Rules). Kolom: `Team` (+ short_name inline) / `Organisasi` / `Lokasi` / `Anggota` (count) / `Status` (badge) / `Aksi`. Filter search+status pakai `<x-ui.input>`/`<x-ui.select>`/`<x-ui.button>`. **2 varian empty state** via `<x-ui.empty-state>`: belum ada data (dengan slot action → tombol Buat Team) vs hasil filter kosong (tanpa tombol, copy berbeda + saran reset).
- **`teams/show`**: header + meta row (org · status badge · N anggota) + **tab Overview / Anggota** (Alpine `x-data="{ tab }"`, panel non-default pakai `x-cloak` supaya tidak flash sebelum Alpine init). Overview = `<x-ui.card>` data + placeholder dashed Competition History (nunggu Event). Anggota = roster read-only (partial) + tombol "Tambah / Hapus" ke halaman roster (khusus yang `@can('manageMembers')`).
- **`teams/members/index`**: add-form di-`<x-ui.card>` + componentise; tabel roster diganti `@include` partial (`$manageable` = `can('manageMembers')`).
- **`teams/create` + `teams/edit`**: hand-rolled input diganti `<x-ui.input>`/`<x-ui.select>`/`<x-ui.textarea>` (old()+error built-in) — field, name, action, method **tidak berubah**.
- **`TeamController`**: `@index` +`->withCount('members')`; `@show` `->load(['organization', 'members' => ...])` — hindari N+1 + sediakan data buat tab Anggota. Tidak ada perubahan lain.

**Keputusan (tidak eksplisit di brief):**
- **Loading/error state (§6) tidak diterapkan** di list Teams — halaman SSR tanpa fetch async, tidak ada state "loading"/"error" yang nyata untuk ditampilkan. `<x-ui.skeleton>`/`<x-ui.error-state>` (F0) tetap ada, dipakai nanti di F6+ (drawer detail participant yang genuinely async). Kalau Ersa mau skeleton "buat konsistensi visual" walau sinkron, itu keputusan terpisah.
- **Roster tetap table (bukan card) di mobile** — 3-4 kolom pendek (Nama/Gender/Role/Aksi) muat di layar kecil dengan `overflow-x-auto`; card layout di sini marginal. Aturan "table→card `<md`" tetap dipegang untuk list utama (Teams index), bukan sub-tabel roster.
- **`status-badge` dibuat sekarang** (bukan inline match per view) — status badge adalah elemen paling berulang di produk ini ke depan; F0 `badge.blade.php` sudah antisipasi "status color = fixed meaning". Scope awal cuma enum `Status` (active/inactive), tumbuh saat F4.

**Verifikasi:** Pint clean, `php artisan view:cache` sukses (semua Blade compile), **74 test tetap hijau** (tidak ada assertion di test yang bergantung markup lama; `TenantIsolationTest` `assertSee($teamA->name)`/`assertDontSee($teamB->name)` tetap lolos). Render manual `php artisan serve` + curl: index (populated desktop table + mobile card, badge warna benar, member count benar), kedua empty state, show (2 tab, `x-cloak` di panel Anggota), members (manageable + empty roster), create, edit (PUT + prefilled) — semua **200**, tidak ada ViewException/undefined var. **Belum di-klik langsung di browser foreground** — interaksi tab Alpine belum di-verifikasi manual (kendala pane sama seperti F0/F1). Saran ke Ersa: sekalian klik-test F0/F1/F3 dalam satu sesi browser.

### F4 Implementation Log (2026-09-10)

**Scope:** Event List + Create sebagai **prototype visual di atas mock data** — backend Event (Sprint 3) belum ada. View di path produksi final; route + controller dev-only.

**Pola mock (keputusan):** `frontend-plan.md` §Mock Data Strategy menyarankan "lanjutkan pola factory", tapi factory butuh model + tabel = scope Sprint 3 (dilarang lompat phase). Kompromi:
```text
resources/views/dashboard/events/*.blade.php          → path PRODUKSI final, tidak berubah saat Sprint 3 landing
app/Http/Controllers/Dev/EventPrototypeController.php  → dev-only, tiap action abort_unless(local), 404 di produksi/testing
routes/web.php  Route::prefix('dev/events')->name('dev.events.')   → diganti resource route asli di Sprint 3
```
Mock (`EventPrototypeController::sampleEvents()`, 6 baris, key = kolom `events` final di `research/chatgpt-blueprint-v1.md` §9) tidak pernah menyentuh controller produksi karena `EventController` belum ada. Swap ke real = hapus dev route + controller, tambah yang asli, view tetap.

**File baru:**
```text
app/Http/Controllers/Dev/EventPrototypeController.php
resources/views/dashboard/events/index.blade.php       — table ≥md + card <md (Event / Tanggal / Venue / Status / Peserta / Kategori),
                                                          filter search + status (7 nilai), 2 varian empty state
resources/views/dashboard/events/create.blade.php      — form 3 section (Informasi Event / Tanggal & Lokasi / Pendaftaran),
                                                          per section = <x-ui.card> + heading bernomor. Jangan lebih dari 3.
resources/views/dashboard/events/show.blade.php        — STUB minimal (meta + 3 stat card + catatan "Workspace = F5")
resources/views/dashboard/events/partials/date-range.blade.php  — "12 Oct 2026" / "8–9 Nov 2026" / "15 Aug – 2 Sep 2026"
```

**Perubahan lain:**
- `status-badge` +7 status Event: `registration_open`→success, `published`/`ongoing`→info, `registration_closed`→warning, `cancelled`→danger, `draft`/`finished`→neutral. Arm ditambah di komponen.
- `partials/flash-messages.blade.php` +varian `info` (sky). Dipakai `store()` prototype; berguna umum F5+.
- **Sidebar TIDAK disentuh** — "Events" masuk sidebar begitu backend Sprint 3 live (`frontend-plan.md` §2), bukan sekarang. `/dev/events` diakses via URL langsung.
- Poster: `<input type="file">` biasa, bukan `<x-ui.input>` (komponen render `value` yang invalid untuk file input).

**Verifikasi (tahap prototype):** Pint clean, `view:cache` sukses, 74 test hijau. Render curl: index/filter/create/show/404/store semua benar.

### F4 — Update 2026-09-10 sore: prototype → nyata

Backend Sprint 3 dibangun di sesi yang sama (lihat `srs/fr-event-category.md`, `status/log.md`). Yang berubah dari tahap prototype:
- **`Dev/EventPrototypeController` + route `dev/events` DIHAPUS.** Diganti `EventController` + `dashboard.events.*` resource route asli (+ `events/{event}/status`, nested `categories`/`staff`).
- `events/index` rewire ke paginator nyata (`withCount categories,staff`) — kolom Peserta diganti Kategori/Staff (registration = Sprint 4). `events/create` buang field poster (Keputusan C di SRS). **`events/edit` baru.** `events/show` jadi Event overview nyata: meta + kontrol ubah-status (peta transisi 7-state) + tab **Overview / Kategori / Staff** (add+delete inline; inline-edit kategori = F7).
- Partial baru: `partials/categories.blade.php`, `partials/staff.blade.php` (pola `teams/partials/roster`).
- `status-badge` sudah handle 7 status Event (dari tahap prototype). `flash-messages` varian `info` tetap dipakai.
- **"Events" masuk `sidebar.blade.php` + `mobile-nav.blade.php`** (backend live — sesuai §2). Mobile jadi 4 tab (Events/Teams/Organizations/Lainnya); reshuffle Dashboard/Events/Teams/More = F2.
- **Verifikasi:** 119 test hijau di MySQL, Pint `--test` pass, render curl index/create/show/edit + transisi status valid/invalid + add kategori semua benar. Belum diklik browser foreground.

### F2 Implementation Log (2026-09-16)

**Kenapa F2 dipilih duluan, bukan F5** (lihat catatan urutan di §Status atas) — F2 adalah satu-satunya gap yang benar-benar kosong (tidak ada route/controller/nav sama sekali), sementara konten F5 sudah tersubstansi lewat tab inline yang dibangun organik per backend sprint (Sprint 4-13).

**Isi halaman (persis spec §Detail Desain per Halaman — Dashboard):** 4 angka ringkasan (Event Aktif/Tim/Peserta Terdaftar/Event Mendatang), 1 card event mendatang terdekat, list aktivitas terbaru singkat. Bukan analytics ramai.

**Keputusan desain (diputuskan sendiri, wajar dari spec+kode existing):**
- **"Event Aktif" ≠ "Event Mendatang"** — dua definisi independen. Aktif = status bukan `draft`/`cancelled`/`finished` (workflow-based, EventStatus). Mendatang = `start_date >= today` dan bukan `cancelled` (date-based, tidak peduli status workflow — event `draft` yang tanggalnya masih jauh di depan tetap dihitung "mendatang" supaya organizer sadar ada event yang perlu segera di-setup). Diverifikasi manual: fixture dengan 1 event `registration_open` + 1 `draft` (keduanya tanggal masa depan, default factory) + 1 `cancelled` menghasilkan Aktif=1, Mendatang=2 — bukan bug, konsekuensi dari dua definisi yang sengaja beda.
- **"Aktivitas Terbaru" pakai `activity_logs`** (Sprint 12/13) — kebetulan timing bagus, tabel ini tidak ada sama sekali saat F5-reorder note ditulis 2026-09-10. Label Indonesia per action-type (`ActivityLog::label()`, match 11 action string yang sudah dipakai `ActivityLogger` di seluruh sprint) dan `actorLabel()` (nama User, atau "Juri" kalau `user_id` NULL — konsisten pola Sprint 13 untuk aksi score-sheet oleh juri).
- **Semua angka di-scope ke `assignableOrganizationIds()`** — helper yang sama persis dipakai `EventController`/`TeamController`, bukan query baru. Tenant isolation diverifikasi eksplisit (test khusus: data organisasi lain tidak pernah muncul di angka/card/list manapun).
- **Reshuffle nav "lands with F2"** — breadcrumb comment yang sudah ditulis sejak F1/F4 di `mobile-nav.blade.php` ("Dashboard/Events/Teams/More, Organizations moved into More, lands with F2") dieksekusi persis seperti direncanakan: sidebar+mobile-nav sekarang **Dashboard/Events/Teams** (+account-menu), "Organizations" pindah jadi item di panel account-menu (sudah disiapkan komentarnya sejak F1: "Settings/Account links go in the panel once those routes exist"). Desktop sidebar ikut pola yang sama supaya konsisten dengan mobile, bukan cuma mobile yang direshuffle.
- **`/dashboard` jadi home page baru** — route `dashboard.index` ditambah di awal grup route `dashboard.`, login/register/home redirect diarahkan ulang. **Kecuali register** — user baru belum punya organisasi apa pun, tetap diarahkan ke Organizations index (empty state "buat organisasi pertama") karena Dashboard kosong tidak actionable buat user yang belum py org sama sekali.

**File dibuat/diubah:**
```text
app/Http/Controllers/Dashboard/DashboardController.php   — BARU
app/Models/ActivityLog.php                                — +label(), +actorLabel()
resources/views/dashboard/index.blade.php                 — BARU
resources/views/components/layout/sidebar.blade.php       — +Dashboard, -Organizations (pindah account-menu)
resources/views/components/layout/mobile-nav.blade.php    — +Dashboard, -Organizations (pindah account-menu)
resources/views/components/layout/account-menu.blade.php  — +link Organizations
routes/web.php                                             — +dashboard.index (GET /dashboard), home route redirect target
app/Http/Controllers/Auth/AuthenticatedSessionController.php  — login redirect → dashboard.index
tests/Feature/Dashboard/IndexTest.php                      — BARU (6 test: summary counts, upcoming event terdekat,
                                                               participants count, recent activity, tenant isolation, guest redirect)
tests/Feature/Auth/AuthenticationTest.php                  — 4 assertion redirect target diupdate ke dashboard.index
                                                               (register TETAP dashboard.organizations.index, lihat keputusan di atas)
```

**Verifikasi:** Pint `--test` pass · **375 test hijau** (369 + 6 baru, 0 regresi) · manual end-to-end via curl (login → redirect ke `/dashboard` → 4 angka benar sesuai fixture → card event mendatang menampilkan event terdekat sesuai `start_date`, bukan yang terjauh → aktivitas terbaru menampilkan label Indonesia benar → nav sidebar+mobile-nav dicek langsung dari HTML mentah: "Dashboard" muncul aktif-state di kedua breakpoint, "Organizations" cuma muncul di dalam panel account-menu, tidak lagi jadi link nav utama).

### F5 Implementation Log (2026-09-16)

**Scope diambil, bukan spec literal:** spec asli §Detail Desain menyebut nav grup sebagai sketsa `Overview / COMPETITION: .../ OPERATIONS: .../ Settings` yang bisa dibaca sebagai side-nav vertikal penuh (produk "workspace" ala Stripe/GitHub settings). Diputuskan TIDAK membangun itu — pertimbangan: (1) tab bar horizontal existing sudah jalan dan teruji di 10 sprint backend, side-nav vertikal butuh treatment mobile terpisah (nested-nav 3 level jadi berat untuk admin tool yang sengaja lean); (2) nilai terbesar dari F5 ada di Overview (readiness summary+next action, spesifik+jelas di spec), bukan di chrome nav-nya. **Kompromi:** tab bar horizontal existing dipertahankan penuh (nol regresi risk), grup COMPETITION/OPERATIONS ditandai lewat divider visual tipis, bukan direstrukturisasi jadi side-nav. "Settings" tidak ditambahkan — belum ada konten apa pun di baliknya (pola sama seperti nav sidebar utama: item cuma ditambah begitu route-nya nyata).

**Overview dibangun ulang total** (isi lama cuma field list statis + placeholder teks yang sudah basi — bilang "Jadwal/Penjurian/Hasil tersedia sprint berikutnya" padahal semua sudah ada sejak Sprint 6-12):
- **Readiness strip** (5 item, dari relasi yang SUDAH di-eager-load `EventController::show()` — murni collection math, tidak ada query baru per item): Kategori, Peserta, Jadwal (jumlah slot), Juri (judge unik yang ter-assign lintas semua round), Penilaian (`{selesai}/{total} lembar skor`, "Belum mulai" kalau nol). ✓ hijau kalau ada isi, `!` amber kalau kosong.
- **Baris ringkasan pendaftaran** ("Pendaftaran: 15 Sep s/d 22 Sep — 82 terdaftar, 76 disetujui, 4 menunggu, 2 revisi") — cuma tampil kalau `registration_start_at`/`registration_end_at` sudah diisi. Butuh 1 query baru (`registrations()->selectRaw('status, count(*)')->groupBy('status')`) — flat, tidak scale dengan jumlah round/kriteria, tidak mengubah hasil `QueryCountTest` (masih di bawah ambang 30, lihat verifikasi).
- **Next Action** — SATU item prioritas tertinggi, urutan tetap (bukan rule engine generik): registrasi pending → kategori kosong → jadwal kosong → ada round tapi juri belum ditugaskan → (kalau semua clear) tidak tampil sama sekali. Tombol CTA-nya pakai `@click="tab = '...'"` Alpine, persis pola yang sudah dipakai tab-bar itu sendiri — bukan `href`/reload halaman.

**File dibuat/diubah:**
```text
app/Http/Controllers/Dashboard/EventController.php   — +readiness(), +nextAction() (private helper), +query breakdown registrasi
resources/views/dashboard/events/show.blade.php       — Overview dibangun ulang, tab-bar +divider grup +overflow-x-auto (mobile)
tests/Feature/Event/OverviewTest.php                   — BARU, 8 test (readiness kosong/lengkap, 4 next-action priority level,
                                                          next-action null saat semua siap, breakdown registrasi conditional)
```

**Verifikasi:** Pint `--test` pass · **383 test hijau** (375 + 8 baru, 0 regresi) · `QueryCountTest` tetap PASS (ambang 30, +1 query flat dari breakdown registrasi tidak mendekati batas) · manual end-to-end via curl: fixture 1 kategori + 2 registrasi (1 submitted + 1 approved, tanpa jadwal/juri) → readiness strip benar (Kategori ✓, Peserta ✓, Jadwal !, Juri !, Penilaian !) → baris pendaftaran benar (2 terdaftar/1 disetujui/1 menunggu/0 revisi) → Next Action benar-benar prioritaskan "1 registrasi menunggu direview" di atas gap jadwal/juri yang juga ada → tab-bar divider + `overflow-x-auto` muncul di HTML mentah.

### F6 Implementation Log (2026-09-16)

**Drawer component sudah disiapkan sejak F0** — `components/ui/drawer.blade.php` py komentar eksplisit "used for quick-view (e.g. Participant detail, per frontend-plan.md F6)" ditulis jauh sebelum F6 dikerjakan. Dieksekusi persis sesuai rencana itu.

**Perubahan:** klik nama tim / tombol "Lihat" di list Peserta sekarang membuka **drawer quick-view** (bukan navigasi ke halaman penuh) berisi: team, kategori, status, **No. Kompetisi** (BARU — data `CompetitionEntry` yang sudah ada sejak Sprint 5 tapi belum pernah ditampilkan di list ini), jumlah roster, dan daftar jenis dokumen yang sudah diunggah. Tombol "Buka Halaman Lengkap" di dalam drawer tetap mengarah ke halaman penuh existing (`registrations.show`) — dipertahankan sebagai tempat aksi besar (submit/approve/reject, edit roster, upload dokumen), bukan diganti. Tabel desktop + card mobile juga dapat kolom/baris **No. Kompetisi** baru.

**2 penyesuaian scope dari spec, didokumentasikan transparan:**
- **"Checklist dokumen" ≠ checklist beneran** — spec bilang "checklist dokumen" tapi `fr-registration.md` Keputusan F eksplisit menegaskan `document_type` bebas/tidak baku (beda event beda kebutuhan dokumen, tidak ada daftar wajib yang bisa dicocokkan). Tidak ada "wajib vs terpenuhi" untuk dicek. Diganti jadi daftar jenis dokumen yang SUDAH diunggah (jujur ke data nyata) — bukan checklist wajib/opsional yang datanya tidak ada.
- **Kolom "No." (nomor baris) tidak ditambahkan** — spec sketch tulis "No/Team/School/...", tapi TIDAK ADA halaman list lain di app ini (Events, Teams, Judges, dst) yang punya kolom nomor urut — menambah di sini saja akan tidak konsisten dengan bahasa desain yang sudah established sejak F0-F4. "School" juga di-skip — Team di domain ini MEMANG sekolahnya, bukan entitas terpisah.
- **Drawer dirender pre-render semua baris** (bukan fetch-on-demand) — konsisten pola `events/show.blade.php`'s 8-tab (semua konten dirender sekali, visibility di-toggle Alpine) karena app ini sengaja tanpa endpoint JSON/AJAX di manapun; per halaman cuma 20 baris (paginate), jadi tidak ada biaya nyata.

**File dibuat/diubah:**
```text
app/Http/Controllers/Dashboard/EventRegistrationController.php   — +eager-load entry,documents
resources/views/dashboard/registrations/index.blade.php          — kolom No. Kompetisi, trigger jadi drawer, drawer quick-view baru
tests/Feature/Registration/IndexQuickViewTest.php                 — BARU, 5 test (kolom no. kompetisi, entry null aman,
                                                                     roster+dokumen di drawer, dokumen kosong, akses Member role)
```

**Verifikasi:** Pint `--test` pass · **388 test hijau** (383 + 5 baru, 0 regresi — 1 kegagalan sempat muncul di run pertama tapi terbukti flake pre-existing tidak terkait: `CompetitionNumberTest` kena collision `EventCategoryFactory`'s random 2-digit `code` di bawah unique constraint, hilang di run ulang) · manual end-to-end via curl: fixture 1 registrasi approved + 2 roster + 2 dokumen + 1 competition_number → kolom No. Kompetisi tampil benar di desktop+mobile → drawer terbuka menampilkan roster "2 orang" + 2 chip jenis dokumen + tombol "Buka Halaman Lengkap" mengarah ke URL halaman penuh yang benar.

### F7 Implementation Log (2026-09-16)

**Sebelumnya cuma tambah+hapus** — kolom "Kategori" di tab event sejak Sprint 3 punya form tambah + tabel + tombol Hapus, TAPI **tidak ada UI edit sama sekali** meski endpoint update-nya sudah ada dan sudah teruji di backend (`EventCategoryController::update()`, dites via HTTP langsung di `EventCategoryTest`). Ini bukan cuma "belum di-polish" — organizer benar-benar tidak bisa perbaiki typo nama/kuota kategori dari UI sebelum F7. Diganti jadi card list + 1 drawer berisi form Tambah + N form Edit (satu per kategori, di-toggle Alpine).

**Keputusan scope:** Field "Status" di spec sketch TIDAK ditambahkan — `fr-event-category.md` sudah menegaskan sejak Sprint 3 bahwa toggle status aktif/nonaktif kategori sengaja ditunda (kolom ada, form request tidak menerimanya). Menambah toggle status di UI tanpa backend menerimanya cuma akan menghasilkan field yang diam-diam diabaikan — tidak ditambahkan.

**Bug arsitektur ditemukan+diperbaiki SEBELUM lapor selesai (bukan dari laporan/test gagal):** karena 1 form create + N form edit (satu per kategori) semua dirender di HALAMAN YANG SAMA, dan Laravel `old()`/`$errors` itu GLOBAL per nama field (bukan per-form) — draft awal akan bikin: edit kategori A gagal validasi → nilai/pesan error yang ditolak itu BOCOR ke form kategori B yang tersembunyi (karena `x-ui.input` internal manggil `old('name')` yang sama untuk SEMUA form bernama `name`). Ditemukan lewat penalaran, bukan kebetulan ketemu test gagal. **Fix:** manfaatkan prop `errorKey` yang sudah ada di `x-ui.input`/`textarea` (awalnya dibangun Sprint 9 untuk kasus lain) — tiap field cuma dapat `errorKey` = nama field ASLI untuk SATU form yang benar-benar gagal submit (dideteksi dari hidden field `_category_id`), form lainnya dapat `errorKey` yang dijamin tidak pernah match apa pun di `old()`/`$errors`, sehingga otomatis fallback ke nilai `$category` yang sungguhan tersimpan di DB. **Diverifikasi ketat**: sengaja di-broke sementara (`errorKey` selalu nama asli) untuk konfirmasi 2 dari 4 test regresi memang gagal seperti diharapkan, baru dikembalikan ke fix yang benar — pola verifikasi yang sama dipakai Sprint 13 buat kalibrasi `QueryCountTest`.

**File dibuat/diubah:**
```text
app/Http/Controllers/Dashboard/EventController.php          — +eager-load categories.registrations (hindari N+1 utk "Tim terdaftar")
resources/views/dashboard/events/partials/categories.blade.php  — ditulis ulang total: card list, drawer create+edit, fix isolasi old()/errors
tests/Feature/Event/CategoryDrawerTest.php                   — BARU, 4 test (card count, edit gagal tidak buka form create,
                                                                edit A gagal tidak bocor ke form B, create gagal tidak bocor ke form edit)
```

**Verifikasi:** Pint `--test` pass · **392 test hijau** (388 + 4 baru, 0 regresi) · `EventCategoryTest` (backend, sudah ada sejak Sprint 3) tetap pass tanpa perubahan · `QueryCountTest` tetap pass (eager-load baru flat, tidak scale per kategori) · manual end-to-end via curl: card kategori tampil benar ("Tim terdaftar 3", "Kuota 20", "Anggota 13–16"), drawer edit terbuka dengan field ter-prefill nilai sungguhan dari DB.

### F8 Implementation Log (2026-09-16)

**Sama persis pola gap F7:** list Jadwal sejak Sprint 6 sudah punya Add/Move Up/Move Down/Delete — tapi **"Edit Slot" (aksi eksplisit di spec F8) tidak pernah ada UI-nya**, meski `EventScheduleSlotController::update()` sudah ada dan sudah teruji sejak Sprint 6. Ditambahkan: tombol "Edit" per slot yang buka drawer, satu form per slot (form Tambah tetap inline seperti sebelumnya — tidak dipindah ke drawer, karena spec F8 tidak menyebut drawer sama sekali untuk Jadwal, beda dari F6/F7 yang eksplisit menyebutnya).

**Bug arsitektur yang sama dengan F7 ditemukan+diperbaiki lagi di sini — plus satu gap baru:** karena form Tambah (inline) + N form Edit (drawer) sama-sama di halaman yang sama, isolasi `old()`/`$errors` via `errorKey` (persis pola F7) diterapkan lagi ke SEMUA field. Tapi Jadwal punya 2 field `<select>` (Jenis, Kategori) yang TIDAK ADA di form Kategori F7 — dicek `x-ui.select` component ternyata **belum punya prop `errorKey`** sama sekali (cuma `x-ui.input`/`textarea` yang dapat itu di Sprint 9). Kalau dibiarkan, pesan error utk field select bisa bocor lintas-form sama seperti bug F7. **Fix:** `x-ui.select` diperluas dengan `errorKey` opsional, persis pola input/textarea — backward-compatible penuh (default null → fallback ke nama asli, SEMUA pemakaian `x-ui.select` lain di app tidak terpengaruh).

**File dibuat/diubah:**
```text
resources/views/components/ui/select.blade.php               — +prop errorKey (baru, backward-compatible)
resources/views/dashboard/events/partials/schedule.blade.php — +tombol Edit, +drawer 1 form per slot, isolasi old()/errors
tests/Feature/ScheduleSlot/ScheduleDrawerTest.php              — BARU, 4 test (edit button+prefill, reopen drawer benar,
                                                                  edit A gagal tidak bocor ke slot B, tidak bocor ke form create inline)
```

**Verifikasi:** Pint `--test` pass · **396 test hijau** (392 + 4 baru) · manual end-to-end via curl: tombol Edit membuka drawer dengan field ter-prefill benar (termasuk `<select>` Jenis ter-`selected` dengan benar, bukan cuma `<input>`) · **2 flake pre-existing TIDAK TERKAIT** sempat muncul selama beberapa run suite penuh (`EventCategoryFactory` random 2-digit `code` collision di `QueryCountTest`, dan timing `display_order` di `ManageScheduleTest::test_owner_can_add_a_slot_appended_to_the_end`) — keduanya PASS konsisten saat dijalankan sendiri/terisolasi, dan tidak disentuh kode apa pun oleh F8 (F8 murni kerja view + 1 prop komponen baru); 2 run suite penuh berturut-turut setelahnya bersih 396/396. Dicatat transparan sebagai kandidat investigasi terpisah, bukan diperbaiki diam-diam di sesi ini karena di luar scope F8.

### F9 Implementation Log (2026-09-16)

**Dua deliverable spec, keduanya dikerjakan:** (1) readiness checklist per kategori (✓/! Round configured, N kriteria, N juri — dihitung dari relasi yang sudah eager-loaded, nol query baru), (2) kriteria diubah dari `<ul>` list polos jadi **card grid** dengan subcriteria bersarang di dalam card yang sama, plus **"edit via drawer"** yang tadinya sama sekali tidak ada (`JudgingCriteriaController::update()` sudah ada+teruji sejak Sprint 7, ini gap ketiga dengan pola persis sama F7/F8).

**Keputusan scope — item ke-4 di spec sketch tidak dibuat**: "! 1 judge has no assignment" di contoh spec tidak dibangun apa adanya — ditulis sebelum model `JudgeAssignment` (Sprint 8) final, dan begitu "N juri" dihitung dari assignment yang benar-benar ada, "juri tanpa assignment" jadi tidak well-defined lagi (kalau terhitung sebagai salah satu dari N juri, artinya dia PUNYA assignment). 3 metrik yang well-defined (Round/Kriteria/Juri) dipertahankan, metrik ke-4 yang ambigu tidak dipaksakan — konsisten prinsip "jangan mengarang business rule yang datanya tidak ada" yang sudah dipakai berulang di F2/F5/F6.

**Bug arsitektur F7/F8 muncul lagi, dengan 1 lapis kompleksitas baru:** karena tab Penjurian bisa punya BEBERAPA round per kategori, dan tiap round py form "Tambah Kriteria" sendiri — F7/F8 cuma pernah punya SATU form create per halaman, jadi diskriminator `null` (vs id) sudah cukup. Di sini, `null` saja ambigu (round mana yang gagal?) — ditambah hidden field `_round_id` di samping `_criterion_id`, jadi tiap form create per-round bisa dibedakan dari form create round lain di halaman yang sama. Diverifikasi ketat dengan cara yang sama (`errorKey` sengaja di-broke sementara → konfirmasi 3 dari 6 test regresi baru memang gagal seperti diharapkan → dikembalikan).

**File dibuat/diubah:**
```text
app/Http/Controllers/Dashboard/EventController.php              — +judgingReadinessFor() per kategori
resources/views/dashboard/events/partials/judging.blade.php     — kriteria jadi card grid + drawer (Rounds/Assignments/
                                                                    Score Sheets TIDAK disentuh, di luar scope F9)
tests/Feature/JudgingCriteria/CriteriaDrawerTest.php              — BARU, 6 test (checklist kosong/lengkap, edit+prefill,
                                                                    edit A tidak bocor ke B, create round A tidak bocor
                                                                    ke round B, edit tidak bocor ke form create manapun)
```

**Verifikasi:** Pint `--test` pass · **402 test hijau** (396 + 6 baru) · manual end-to-end via curl: checklist tampil "✓ Round configured · ✓ 2 kriteria · ✓ 1 juri" persis sesuai fixture, card kriteria menampilkan subcriteria bersarang dengan bobot benar, tombol Edit membuka drawer ter-prefill nilai sungguhan.

**Bonus: 2 flake pre-existing yang sudah disebut sejak berkali-kali sepanjang sesi ("flake ScheduleSlot") akhirnya di-root-cause+diperbaiki** — F9 sendiri tidak menyentuh kodenya, tapi kegagalan intermiten yang berulang selama verifikasi memberi kesempatan untuk benar-benar diselidiki (bukan cuma dicatat lagi untuk ke-4 kalinya):
- **`ManageScheduleTest::test_owner_can_add_a_slot_appended_to_the_end`** — `EventScheduleSlotFactory`'s default title diambil random dari `['Opening Ceremony', 'Technical Meeting', 'Break', null]`, 25% kemungkinan SAMA PERSIS dengan literal string yang di-filter test ini (`where('title', 'Opening Ceremony')`). Kalau slot fixture (dibuat tanpa override title eksplisit) kebetulan dapat "Opening Ceremony", query `firstOrFail()` match 2 baris dan mengambil yang SALAH (baris lama, bukan yang baru di-POST) — kelihatan seperti bug urutan `display_order`, padahal cuma salah ambil baris. **Fix:** kasih title eksplisit non-collide (`'Existing Slot'`) di fixture test, bukan ubah factory (supaya distribusi title random-nya tetap berguna untuk test lain).
- **`EventCategoryFactory`'s `code`** — `bothify('CAT-##')` cuma 100 kemungkinan (2 digit), collision birthday-paradox nyata di test manapun yang bikin beberapa kategori untuk event yang sama (`QueryCountTest` bikin 4, `CompetitionNumberTest` bikin 2+). **Fix:** perlebar jadi 4 digit (`CAT-####`, 10.000 kemungkinan) — tidak ada test yang assert ke format literalnya, jadi aman diubah.
- **Diverifikasi ketat:** 5 run suite penuh berturut-turut, 402/402 bersih setiap kali (sebelumnya intermiten gagal di ~1 dari 3-4 run).

### F11 Implementation Log (2026-09-16)

**Cross-check hasilnya:** ranking table ✓, Finalize ✓, Publish (muncul cuma setelah finalized) ✓ — semua sudah persis sesuai spec sejak Sprint 11-12. Satu gap literal: spec sebut tombol **"[Review Scores]"**, belum ada. Ditambahkan sebagai "Review Skor Juri" — lompat ke tab Penjurian (yang sudah py list Lembar Skor per-round dengan status+link detail sejak Sprint 9-10) lewat `@click="tab = 'judging'"`, BUKAN duplikasi list itu di tab Hasil.

**Bug ditemukan saat cross-check, di luar checklist spec tapi nyata:** form set-penalti per baris hasil TIDAK PERNAH punya `@error('penalty')` — submit penalti tidak valid (mis. negatif) gagal TOTAL DIAM-DIAM dari sudut pandang user (redirect balik, tidak ada pesan apa pun). Dan karena tiap baris hasil py `<form name="penalty">` sendiri-sendiri, fix naif (`$errors->first('penalty')` polos) akan bocor ke SEMUA baris — hazard yang sama F7/F8/F9. **Fix:** hidden field `_result_id` + guard sama seperti pola lain, pesan error cuma tampil di baris yang benar-benar gagal.

**Temuan lebih besar, cross-cutting ke F7/F8/F9 sekaligus:** saat verifikasi manual gagal-submit penalti, ditemukan tab Hasil TIDAK PERNAH benar-benar terbuka setelah redirect — halaman selalu reload ke tab Overview (`x-data="{ tab: 'overview' }"` hardcoded di `events/show.blade.php`), jadi drawer/pesan error yang sudah benar dihitung oleh fix F7/F8/F9 (`open`/`editing` correctly computed) TETAP TERSEMBUNYI di balik panel tab yang tidak aktif. Dari sudut pandang user: submit gagal → halaman reload → Overview tampil kosong seolah tidak terjadi apa-apa. **Root cause + fix:** setiap form yang relevan (Categories, Schedule, Judging, Results, Staff, Competition) sekarang bawa hidden field `_tab`; `events/show.blade.php` membaca `old('_tab')` untuk menentukan tab aktif awal — **di-whitelist ketat** terhadap 8 nama tab yang valid sebelum di-interpolasi ke `x-data="{ tab: '...' }"`, karena `old()` adalah input user yang TIDAK divalidasi FormRequest manapun, dan menaruhnya mentah-mentah di dalam string Alpine ber-tanda-kutip-tunggal adalah celah JS-string-breakout nyata (HTML-entity escaping Blade tidak menutup ini — browser men-decode entity SEBELUM Alpine mem-parse atribut sebagai JS). Diverifikasi eksplisit: payload `_tab` berisi `x'}); alert(1); ({tab:'overview` tetap jatuh ke `'overview'`, tidak pernah tembus mentah ke HTML.

**File dibuat/diubah:**
```text
resources/views/dashboard/events/show.blade.php                  — x-data tab dari hardcoded 'overview' jadi whitelist old('_tab')
resources/views/dashboard/events/partials/results.blade.php      — +tombol Review Skor Juri, +isolasi error penalti per baris, +_tab di semua form
resources/views/dashboard/events/partials/categories.blade.php   — +_tab (retrofit F7)
resources/views/dashboard/events/partials/schedule.blade.php     — +_tab (retrofit F8)
resources/views/dashboard/events/partials/judging.blade.php      — +_tab di semua form (retrofit F9)
resources/views/dashboard/events/partials/staff.blade.php        — +_tab (form add-staff belum pernah py isolasi sebelumnya)
resources/views/dashboard/events/partials/competition.blade.php  — +_tab (Sprint 5, bug yang sama ada sejak awal, ikut ditutup demi konsistensi)
tests/Feature/Event/TabRestorationTest.php                        — BARU, 6 test (default overview, restore ke 4 tab
                                                                     berbeda, whitelist menolak payload injeksi)
tests/Feature/Result/ReviewAndPenaltyTest.php                     — BARU, 3 test (tombol Review Skor, error penalti tampil,
                                                                     error tidak bocor ke baris hasil lain)
```

**Verifikasi:** Pint `--test` pass · **411 test hijau** (402 + 9 baru) · setiap fix (isolasi penalti, whitelist tab) diverifikasi ketat dengan cara yang sama sejak F7 — sengaja di-broke sementara, konfirmasi test gagal seperti diharapkan, dikembalikan · manual end-to-end via curl: submit penalti negatif → redirect → halaman reload persis ke tab Hasil (bukan Overview) DENGAN pesan error "Penalti tidak boleh negatif." tampil · payload `_tab` berisi upaya injeksi JS terkonfirmasi jatuh ke `'overview'`, tidak pernah tembus mentah · 3 run suite penuh berturut-turut bersih (411/411 setiap kali, 2 flake dari sesi F9 tetap tidak muncul lagi).

### F12 Implementation Log (2026-09-16)

**Keterbatasan metodologi, dinyatakan eksplisit:** tidak ada tool browser/screenshot di environment sesi ini (dicek via pencarian tool — nihil), jadi F12 dikerjakan sebagai **audit struktural kode** (kelas Tailwind, atribut Alpine, ukuran tap target, konsistensi pola responsive lintas file) — BUKAN review visual piksel-demi-piksel. Ini konsisten instruksi CLAUDE.md sendiri: "kalau tidak bisa test UI, katakan eksplisit, jangan klaim berhasil." Temuan di bawah semua diverifikasi lewat isi HTML yang dirender (curl) dan/atau test PHPUnit yang mengecek string/kelas yang benar ada — bukan lewat screenshot.

**3 temuan nyata, semua diperbaiki:**

1. **Drawer/modal tidak mengunci scroll background** — `components/ui/drawer.blade.php` dan `modal.blade.php` (dipakai F6/F7/F8/F9) tidak pernah mencegah halaman DI BELAKANG drawer ikut ter-scroll selagi drawer terbuka — bug UX mobile yang umum (drag di area backdrop bisa nge-scroll konten belakang, halaman terasa "lepas" dari overlay-nya). **Fix:** `x-effect="document.body.classList.toggle('overflow-hidden', open)"` ditambah ke wrapper terluar kedua komponen — otomatis berlaku di SEMUA pemakaian drawer/modal di seluruh app tanpa perlu sentuh tiap halaman satu-satu (perbaikan di 1 komponen bersama, dampak ke 4 sprint sekaligus).
2. **Tap target terlalu kecil** — tombol ↑/↓/✕ reorder-jadwal (`p-1`, dipakai berulang-ulang saat reorder jadwal hari-H di HP) dan tombol tutup drawer/modal (nol padding sama sekali di sekitar ikon 20px) sama-sama di bawah ukuran nyaman disentuh. **Fix:** diperbesar ke `p-2` (dan `-m-2 p-2` untuk tombol tutup, supaya area sentuh membesar tanpa mengganggu tata letak visual — margin negatif meng-offset padding yang ditambahkan).
3. **6 grid form 2-kolom melanggar aturan sendiri** — `frontend-plan.md` §3 eksplisit bilang "Form multi-kolom → 1 kolom di mobile," dan SEMUA form lain di app (Teams, Organizations, dst) sudah benar `grid-cols-1 ... sm:grid-cols-2`. Tapi drawer F7 (min/max anggota, rentang tanggal) dan F9 (min/max skor, rentang tanggal) yang baru dibangun sesi ini pakai `grid-cols-2` polos tanpa fallback mobile — ditemukan lewat grep sistematis pola `grid-cols-[2-9]` tanpa prefix breakpoint di seluruh `resources/views/dashboard/`, bukan tebakan. **Fix:** diseragamkan jadi `grid-cols-1 gap-3 sm:grid-cols-2`, persis pola yang sudah benar di file yang sama.

**Yang DICEK tapi TIDAK jadi temuan (dicatat supaya jelas ini bukan dilewati begitu saja):** semua pemakaian `<x-ui.table>` sudah otomatis `overflow-x-auto` sejak F0 (komponen bersama); tabel Hasil (raw `<table>`, bukan komponen) juga sudah dibungkus manual; grid stat card F2 Dashboard (`grid-cols-2 lg:grid-cols-4`) SENGAJA 2-kolom di mobile — itu display KPI read-only bukan form input, tidak melanggar aturan yang sama; semua halaman list baru (F2/F6/F7/F8/F9/F11) sudah punya empty state yang jelas.

**File dibuat/diubah:**
```text
resources/views/components/ui/drawer.blade.php    — +x-effect scroll-lock, tombol tutup +tap target
resources/views/components/ui/modal.blade.php     — +x-effect scroll-lock, tombol tutup +tap target
resources/views/dashboard/events/partials/schedule.blade.php    — tombol reorder p-1 -> p-2
resources/views/dashboard/events/partials/categories.blade.php  — 2x grid-cols-2 -> grid-cols-1 sm:grid-cols-2
resources/views/dashboard/events/partials/judging.blade.php     — 2x grid-cols-2 -> grid-cols-1 sm:grid-cols-2
tests/Feature/Event/ResponsiveAuditTest.php        — BARU, 4 test (scroll-lock hadir, tap target drawer+schedule
                                                       diperbesar, grid form F7 mobile-first)
```

**Verifikasi:** Pint `--test` pass · **415 test hijau** (411 + 4 baru) · manual end-to-end via curl: keempat perbaikan terkonfirmasi hadir di HTML mentah halaman event nyata · 3 run suite penuh berturut-turut bersih 415/415.

**Frontend F0-F12 — 13 dari 13 step SELESAI.** Sisa kerja lanjutan opsional (bukan bagian F0-F12): commit + push ke remote (belum pernah diminta sepanjang sesi); verifikasi visual manual oleh Ersa langsung di browser tetap disarankan sekali sebelum dipakai user asli, mengingat sesi ini tidak punya akses browser visual.

## 5. Page Inventory & Build Order

```text
F0  Design Foundation      typography, color tokens, component primitives
F1  Admin Shell            sidebar, header, responsive shell
F2  Dashboard              readiness summary, next action, recent activity — ✅ SELESAI 2026-09-16
F3  Teams                  polish halaman yang SUDAH ADA backend-nya (Sprint 2)
F4  Event List + Create    mock data (Sprint 3 backend belum ada)
F5  Event Workspace        shell utama — overview + nav Competition/Operations/Settings — ✅ SELESAI 2026-09-16 (lihat catatan scope di §F5 Log)
F6  Participants           list + detail drawer, admin-entry flow — ✅ SELESAI 2026-09-16
F7  Categories             card list + drawer create/edit — ✅ SELESAI 2026-09-16
F8  Schedule               list linear dulu, drag-drop belakangan — ✅ SELESAI 2026-09-16
F9  Judging Setup          readiness checklist (organizer side) — ✅ SELESAI 2026-09-16
F10 Judge Scoring          interface terpisah, sangat minimal
F11 Results                ranking + finalize + publish (publish ke publik tetap DEFERRED) — ✅ SELESAI 2026-09-16
F12 Responsive + UX audit  adversarial pass di semua halaman F0-F11 — ✅ SELESAI 2026-09-16
```

**F3 (Teams) adalah kandidat page pertama yang bisa dipoles sampai production-quality** karena backend-nya sudah nyata (Sprint 2) — bukan mock.

## 6. Page States (wajib per halaman list/detail)

```text
□ Empty state (belum ada data) — sudah ada pola di Sprint 1-2, pertahankan
□ Empty state (hasil filter kosong) — beda copy dari empty state murni
□ Loading state
□ Error state
□ Populated state (desktop + mobile)
```

## 7. Mock Data Strategy

- Mock **tidak boleh masuk production controller**. Gunakan development fixture/demo seeder terpisah — Sprint 1-2 sudah punya pola `database/factories/*Factory.php`, lanjutkan pola yang sama untuk entity yang backend-nya belum ada (Event, Category, dst) supaya frontend bisa jalan di atas data yang shape-nya konsisten dengan skema final.
- Contoh shape View Model Event (dipakai frontend sebelum Sprint 3 backend live):
```php
$event = [
    'id' => 12,
    'public_id' => 'evt_xxx',
    'name' => 'LKBB Garuda 2026',
    'status' => 'registration_open',
    'start_date' => '2026-10-12',
    'end_date' => '2026-10-12',
    'venue_name' => 'GOR Bandung',
    'categories_count' => 4,
    'participants_count' => 82,
];
```
Field/nama harus **sama persis** dengan kolom migration final di `research/chatgpt-blueprint-v1.md` §45-46 — supaya swap mock→real data tidak perlu re-desain view.

## 8. Backend Data Contract

Bukan REST API formal — Blade tetap terima data lewat Controller seperti biasa. Kontraknya adalah **shape data yang konsisten**, disepakati di sini supaya frontend (mock) dan backend (real) menghasilkan struktur yang sama persis ke view. Setiap kali backend Sprint-N selesai, cross-check field di view terhadap migration sprint itu — kalau ada field baru/beda nama, update di sini juga.

---

## Detail Desain per Halaman

### Dashboard (F2)
Bukan analytics ramai. Cukup: ringkasan angka (Active Events/Teams/Participants/Upcoming), 1 upcoming event card, recent activity list singkat.

### Teams (F3)
List: search + table (`Team/Organization/Location/Members/Status/Action`), mobile → card. Detail: header + tab Overview/Members (Competition History menyusul setelah Event ada).

### Events (F4)
List: card/table hybrid (`Name/Date/Venue/Status/Participants/Categories`). Status: `draft/registration_open/registration_closed/ongoing/finished/cancelled`.

**Create Event** — 3 section saja (matches SRS Sprint 3 nanti, jangan lebih):
```text
1. Event Information (name, organizer, description, poster)
2. Date & Location (start/end date, venue, province, city, address)
3. Registration (registration start/end)
```
Setelah create → langsung ke Event Workspace. Jangan setup category/judging/participant di form create.

### Event Workspace (F5) — shell utama, 80% visual language produk
```text
Overview
COMPETITION: Participants / Categories / Schedule / Judging / Results
OPERATIONS: Documents / Staff
Settings
```
Overview = readiness summary + next action, bukan grafik:
```text
Categories 4 ✓ · Participants 82 ✓ · Schedule 76% ! · Judges 5 ✓ · Scoring — Not started
Registration: Open until 8 Oct 2026 — 82 registered, 76 approved, 4 pending, 2 revision
Next Action: 6 participant registrations need review. [Review Participants]
```

### Participants (F6)
List: search + filter (category/status) + table (`No/Team/School/Category/Members/Competition No./Status/Action`). Status: `draft/pending_review/revision/approved/rejected`.
Detail: **drawer untuk quick view**, full page hanya untuk edit besar. Drawer isi: team, category, status, competition number, roster count, checklist dokumen.

### Categories (F7)
Card list (`Name, Teams count, Quota`) + drawer create/edit (`Name/Code/Description/Quota/Min-Max Members/Registration Date/Status`).

### Schedule (F8)
V1: list linear per waktu (`08:00 Opening Ceremony`, `08:30 A01 — ...`, dst), bukan calendar kompleks. Aksi: Add Slot/Edit Slot/Move Up/Move Down. Drag-drop belakangan.

### Judging Setup — organizer side (F9)
Readiness checklist per kategori:
```text
SMA Utama
✓ Round configured · ✓ 8 criteria · ✓ 5 judges · ! 1 judge has no assignment
```
Criteria Builder: card per kriteria dengan weight + subcriteria, edit via drawer — bukan tampilan spreadsheet.

### Judge Scoring (F10) — interface terpisah total
Minimal, tanpa sidebar/settings/analytics:
```text
SMA Utama — Participant A12 — SMAN 1 Bandung
PBB: Gerakan Dasar [ ], Keseragaman [ ], Ketepatan [ ]
[Save Draft]  [Submit Score]
```

### Results (F11)
Organizer: ranking table + `[Review Scores]` `[Finalize Results]` → setelah finalized baru `[Publish]` muncul (publish ke publik tetap nunggu fase DEFERRED public site).

---

## Referensi

- Struktur data/skema final: `research/chatgpt-blueprint-v1.md`
- Urutan sprint backend & SRS per fitur: `tasks/tasks.md`, `srs/fr-*.md`
- Kontrak kerja tim dev: `BACKEND-HANDOFF.md` (root project)
