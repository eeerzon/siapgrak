# ADR-003 — Halaman Utama Publik + Konten yang Dikelola Admin Platform

**Status:** Accepted
**Tanggal:** 2026-11-05
**Men-supersede sebagian:** [ADR-002](adr-002-admin-first-mvp.md) — hanya butir "public homepage" dan larangan halaman publik untuk tiga jenis konten di bawah. Public event discovery, team directory, organization profile, hasil publik, dan self-registration tetap DEFERRED.

## Konteks

Siapgrak butuh halaman utama yang menampilkan Info Lomba & Kejuaraan, Agenda & Kegiatan, serta Prestasi & Kabar Komunitas, dengan data yang diatur admin. Sebagian lomba diselenggarakan sendiri dan transaksinya berjalan di Kreenconnect. Sebelumnya `/` hanya redirect ke login, dan sistem hanya punya peran per organisasi.

## Keputusan

1. **`/` menjadi beranda publik untuk tamu.** User yang sudah login tetap diarahkan ke dashboard.
2. **Konten bersifat global, dikelola admin platform.** Peran baru berupa kolom `users.is_platform_admin`, di luar peran organisasi. Nilainya hanya bisa diubah lewat `php artisan siapgrak:platform-admin`.
3. **Info Lomba yang kita kelola hanya menyimpan flag dan slug Kreenconnect.** Tombol daftar mengarah ke `{KREENCONNECT_EVENT_BASE_URL}/{kreen_slug}`, dan semua transaksi terjadi di Kreenconnect. Boundary ADR-001 tidak berubah: tidak ada tabel transaksi di Siapgrak.

Keputusan dipilih oleh user pada sesi 2026-11-05 saat ditanya soal konflik dengan ADR-002.

## Alternatif yang Dipertimbangkan

| Opsi | Keputusan | Alasan |
|---|---|---|
| Beranda di `/beranda`, `/` tetap redirect | ❌ | Halaman utama tidak terlihat di alamat utama |
| Admin lewat daftar email di `.env` | ❌ | Perubahan admin butuh edit konfigurasi server |
| Konten per organisasi | ❌ | Konten publik tidak melewati kurasi pusat |
| Ringkasan penjualan via API Kreenconnect | ❌ untuk sekarang | Kontrak API masih proposal (`architecture/integration.md`) |

## Konsekuensi

- Permukaan publik baru bersifat read-only dan hanya melayani konten berstatus `published`.
- Tautan transaksi baru tampil setelah `KREENCONNECT_EVENT_BASE_URL` diisi.
- Tambah 3 tabel: `competition_listings`, `agenda_items`, `community_posts`.
