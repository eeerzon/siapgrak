# Frontend Plan — Siapgrak Admin

**Status:** Active. Ditulis 2026-09-07 dari diskusi dengan Ersa (via ChatGPT), setelah Sprint 1-2 backend selesai & PASS-review. **F0 (Design Foundation) SELESAI 2026-09-09** — lihat §F0 Implementation Log di bawah. Lanjut F1 (Admin Shell).

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

## 5. Page Inventory & Build Order

```text
F0  Design Foundation      typography, color tokens, component primitives
F1  Admin Shell            sidebar, header, responsive shell
F2  Dashboard              readiness summary, next action, recent activity
F3  Teams                  polish halaman yang SUDAH ADA backend-nya (Sprint 2)
F4  Event List + Create    mock data (Sprint 3 backend belum ada)
F5  Event Workspace        shell utama — overview + nav Competition/Operations/Settings
F6  Participants           list + detail drawer, admin-entry flow
F7  Categories             card list + drawer create/edit
F8  Schedule               list linear dulu, drag-drop belakangan
F9  Judging Setup          readiness checklist (organizer side)
F10 Judge Scoring          interface terpisah, sangat minimal
F11 Results                ranking + finalize + publish (publish ke publik tetap DEFERRED)
F12 Responsive + UX audit  adversarial pass di semua halaman F0-F11
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
