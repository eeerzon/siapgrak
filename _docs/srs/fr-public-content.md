# FR — Halaman Utama Publik & Konten Platform

**Status:** Selesai (2026-11-05). Keputusan arsitektur: [ADR-003](../decisions/adr-003-public-homepage-platform-content.md).

## Ruang Lingkup

| Konten | Tabel | URL publik | URL admin |
|---|---|---|---|
| Info Lomba & Kejuaraan | `competition_listings` | `/info-lomba`, `/info-lomba/{slug}` | `/dashboard/content/competitions` |
| Agenda & Kegiatan | `agenda_items` | `/agenda`, `/agenda/{slug}` | `/dashboard/content/agenda` |
| Prestasi & Kabar Komunitas | `community_posts` | `/kabar`, `/kabar/{slug}` | `/dashboard/content/community` |

Beranda `/` menampilkan 4 lomba terdekat, 4 agenda terdekat, dan 3 kabar terbaru.

**Tidak termasuk:** rich text editor, transaksi di Siapgrak, ringkasan penjualan dari API Kreenconnect, relasi ke tabel `events`.

## Keputusan Desain

- **A. Akses:** middleware `EnsurePlatformAdmin` memeriksa `users.is_platform_admin`. Kolom ini tidak ada di `User::$fillable`, jadi tidak bisa diisi lewat form. Konten tidak punya `organization_id`, sehingga tidak memakai tenant Policy.
- **A2. Kepemilikan (2026-11-09):** setiap konten punya `id_user` (FK ke `users.id_user`, dulu `created_by`; primary key `users` di-rename `id` → `id_user` pada 2026-11-10), diisi otomatis dengan admin yang membuatnya. Di panel admin, list hanya menampilkan konten milik admin yang login (`ownedBy`), dan show/edit/update/hapus milik admin lain menghasilkan 403 (`PublicContentPolicy`). Konten yang pembuatnya sudah dihapus (`id_user` null) tidak bisa dikelola siapa pun. Halaman publik tidak terpengaruh.
- **B. Publikasi:** status `draft`/`published`. Draft menghasilkan 404 di publik. `published_at` terisi sekali saat pertama kali terbit.
- **C. Slug:** dibuat otomatis dari judul saat pertama kali dibuat dan tidak berubah walau judul diedit, supaya tautan yang sudah beredar tetap hidup.
- **D. Jenis Info Lomba:** `is_managed=true` mewajibkan `kreen_slug` yang unik dan mengosongkan `external_url`. `is_managed=false` melakukan sebaliknya, dan `external_url` hanya menerima http/https.
- **E. Isi artikel:** teks biasa, selalu di-escape saat ditampilkan.
- **F. Sampul:** JPG/PNG/WEBP maksimal 2 MB di disk `public`, file lama dihapus saat diganti atau kontennya dihapus. SVG ditolak.
- **G. Hapus:** hard delete diperbolehkan karena konten ini bukan histori kompetisi.
- **H. Lomba/agenda "akan datang":** berdasarkan tanggal selesai, atau tanggal mulai kalau tanggal selesai kosong. Yang sudah lewat masuk ke tab arsip (`?arsip=1`).
- **I. Poster lomba (2026-11-09):** sampul info lomba wajib rasio 4:5 seperti feed Instagram (`dimensions:ratio=4/5`, contoh 1080×1350), dan tampil 4:5 di kartu dan detail. Sejak 2026-11-16 agenda juga punya poster 4:5 (`agenda_items.cover_path`, aturan validasi sama) yang tampil di kiri halaman detail agenda. Kabar tetap 16:9.
- **J. Filter info lomba (2026-11-09):** `/info-lomba` menerima `q` (judul, penyelenggara, lokasi), `jenjang` (`CompetitionLevel`), `provinsi` (daftar di `config('siapgrak.provinces')`), dan `buka=1` (batas pendaftaran belum lewat, atau tanpa batas dan lomba belum mulai). Nilai yang tidak dikenal diabaikan, tidak ditolak. Pilihan provinsi hanya yang punya lomba published.
- **K. Kartu vs detail:** kartu hanya berisi poster, tanggal, lokasi, jenjang, dan status pendaftaran. Ringkasan, isi, penyelenggara, technical meeting, dan contact person hanya di halaman detail. Technical meeting (`technical_meeting_date`, `technical_meeting_location` opsional) disembunyikan kalau tanggalnya kosong.
- **M. Jumlah per halaman (2026-11-16):** `/info-lomba` menampilkan 8 per halaman dengan pagination biasa. `/agenda` menampilkan 8 agenda, lalu memuat 8 berikutnya hanya setelah pengunjung scroll (scroll, wheel, sentuh, atau keyboard) mendekati akhir daftar. Tidak ada tombol atau link pagination; `data-next` berisi URL halaman berikutnya. Kalau gagal memuat, scroll berikutnya mencoba lagi. `/agenda` dan `/kabar` bisa dicari dengan `q`.
- **L. Pendaftaran:** urutan tombol: Kreenconnect (event dikelola) → `external_url` ("Link Pendaftaran") → contact person. Contact person (`contact_persons`, maksimal 3, `{name, phone}`) tampil di `<details>` yang otomatis terbuka kalau tidak ada link pendaftaran, dengan tombol ke `wa.me` (awalan `0` diubah ke `62`). Tanpa JavaScript.

## Setup

```bash
php artisan migrate
php artisan storage:link
php artisan siapgrak:platform-admin email@domain.com
```

Isi `KREENCONNECT_EVENT_BASE_URL` di `.env` dengan URL halaman event Kreenconnect tanpa slug.

## Test

`tests/Feature/PublicContent/` mencakup akses per peran, draft tidak bocor, validasi Kreenconnect, skema URL, escaping, siklus file sampul, dan command admin.
